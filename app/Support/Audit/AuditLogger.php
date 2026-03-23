<?php

namespace App\Support\Audit;

use App\Models\Audit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class AuditLogger
{
    public static function created(Model $model): void
    {
        self::write(
            model: $model,
            event: 'created',
            old: null,
            new: self::snapshot($model),
        );
    }

    public static function updated(Model $model): void
    {
        $changes = self::filter($model, $model->getChanges());

        if ($changes === []) {
            return;
        }

        $fieldNames = array_keys($changes);
        $previous = method_exists($model, 'getPrevious')
            ? $model->getPrevious()
            : Arr::only($model->getOriginal(), $fieldNames);

        $old = self::filter($model, Arr::only($previous, $fieldNames));
        $new = Arr::only($changes, array_keys($old + $changes));

        if ($old === [] && $new === []) {
            return;
        }

        self::write(
            model: $model,
            event: 'updated',
            old: $old,
            new: $new,
        );
    }

    /**
     * @param  array<string, mixed>  $snapshot
     */
    public static function deleted(Model $model, array $snapshot): void
    {
        self::write(
            model: $model,
            event: 'deleted',
            old: $snapshot,
            new: null,
        );
    }

    public static function restored(Model $model): void
    {
        $deletedAtColumn = method_exists($model, 'getDeletedAtColumn')
            ? $model->getDeletedAtColumn()
            : 'deleted_at';

        $previous = method_exists($model, 'getPrevious')
            ? $model->getPrevious()
            : [];

        self::write(
            model: $model,
            event: 'restored',
            old: Arr::only($previous, [$deletedAtColumn]),
            new: [$deletedAtColumn => null],
        );
    }

    /**
     * @param  array<string, mixed>  $snapshot
     */
    public static function forceDeleted(Model $model, array $snapshot): void
    {
        self::write(
            model: $model,
            event: 'force_deleted',
            old: $snapshot,
            new: null,
        );
    }

    /**
     * @param  array<string, mixed>|null  $old
     * @param  array<string, mixed>|null  $new
     * @param  array<string, mixed>  $meta
     */
    public static function custom(
        Model $model,
        string $event,
        ?array $old = null,
        ?array $new = null,
        array $meta = [],
    ): void {
        self::write(
            model: $model,
            event: $event,
            old: $old === null ? null : self::filter($model, $old),
            new: $new === null ? null : self::filter($model, $new),
            extraMeta: $meta,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function snapshot(Model $model): array
    {
        return self::filter($model, $model->attributesToArray());
    }

    /**
     * @param  array<string, mixed>|null  $old
     * @param  array<string, mixed>|null  $new
     * @param  array<string, mixed>  $extraMeta
     */
    protected static function write(
        Model $model,
        string $event,
        ?array $old,
        ?array $new,
        array $extraMeta = [],
    ): void {
        $actorId = auth()->id();

        Audit::query()->create([
            'auditable_type' => $model->getMorphClass(),
            'auditable_id' => $model->getKey(),
            'actor_id' => is_numeric($actorId) ? (int) $actorId : null,
            'event' => $event,
            'old_values' => $old,
            'new_values' => $new,
            'meta' => array_merge([
                'model' => get_class($model),
                'table' => $model->getTable(),
                'url' => request()?->fullUrl(),
                'method' => request()?->method(),
                'ip' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
                'route' => request()?->route()?->getName(),
            ], $extraMeta),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected static function filter(Model $model, array $data): array
    {
        $except = method_exists($model, 'auditExcept') ? $model->auditExcept() : [];

        if ($except !== []) {
            $data = Arr::except($data, $except);
        }

        return $data;
    }
}
