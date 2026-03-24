<?php

namespace App\Models\Concerns;

use App\Models\Audit;
use App\Support\Audit\AuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

trait Auditable
{
    /**
     * @var array<string, array<string, mixed>>
     */
    protected static array $auditSnapshots = [];

    public function audits(): MorphMany
    {
        return $this->morphMany(Audit::class, 'auditable');
    }

    /**
     * @return array<int, string>
     */
    public function auditExcept(): array
    {
        $fields = [
            $this->getKeyName(),
        ];

        $createdAtColumn = $this->getCreatedAtColumn();

        if (is_string($createdAtColumn) && $createdAtColumn !== '') {
            $fields[] = $createdAtColumn;
        }

        $updatedAtColumn = $this->getUpdatedAtColumn();

        if (is_string($updatedAtColumn) && $updatedAtColumn !== '') {
            $fields[] = $updatedAtColumn;
        }

        if (method_exists($this, 'getDeletedAtColumn')) {
            $deletedAtColumn = $this->getDeletedAtColumn();

            if (is_string($deletedAtColumn) && $deletedAtColumn !== '') {
                $fields[] = $deletedAtColumn;
            }
        }

        return array_values(array_unique($fields));
    }

    /**
     * @return array<int, string>
     */
    public function auditMask(): array
    {
        return [
            'password',
            'remember_token',
        ];
    }

    protected static function bootAuditable(): void
    {
        static::created(function (Model $model): void {
            AuditLogger::created($model);
        });

        static::updated(function (Model $model): void {
            AuditLogger::updated($model);
        });

        static::deleting(function (Model $model): void {
            $event = static::isForceDeletingModel($model) ? 'force_deleted' : 'deleted';

            static::storeAuditSnapshot($model, $event, AuditLogger::snapshot($model));
        });

        static::deleted(function (Model $model): void {
            if (static::isForceDeletingModel($model)) {
                return;
            }

            AuditLogger::deleted($model, static::pullAuditSnapshot($model, 'deleted'));
        });

        if (in_array(SoftDeletes::class, class_uses_recursive(static::class), true)) {
            static::restored(function (Model $model): void {
                AuditLogger::restored($model);
            });

            static::forceDeleted(function (Model $model): void {
                AuditLogger::forceDeleted($model, static::pullAuditSnapshot($model, 'force_deleted'));
            });
        }
    }

    protected static function isForceDeletingModel(Model $model): bool
    {
        return method_exists($model, 'isForceDeleting') && $model->isForceDeleting();
    }

    /**
     * @param  array<string, mixed>  $snapshot
     */
    protected static function storeAuditSnapshot(Model $model, string $event, array $snapshot): void
    {
        static::$auditSnapshots[static::snapshotKey($model, $event)] = $snapshot;
    }

    /**
     * @return array<string, mixed>
     */
    protected static function pullAuditSnapshot(Model $model, string $event): array
    {
        $key = static::snapshotKey($model, $event);
        $snapshot = static::$auditSnapshots[$key] ?? [];

        unset(static::$auditSnapshots[$key]);

        return $snapshot;
    }

    protected static function snapshotKey(Model $model, string $event): string
    {
        return spl_object_id($model).':'.$event;
    }
}
