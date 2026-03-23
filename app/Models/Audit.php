<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Auth\AdminRole;
use App\Models\Auth\AdminUser;
use App\Models\Iot\MqttAccount;
use App\Models\Settings\Config;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $auditable_type
 * @property int|string $auditable_id
 * @property int|null $actor_id
 * @property string $event
 * @property array<string, mixed>|null $old_values
 * @property array<string, mixed>|null $new_values
 * @property array<string, mixed>|null $meta
 * @property Carbon $created_at
 */
class Audit extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected $appends = [
        'event_label',
        'resource_type_label',
        'route',
        'method',
        'ip',
        'changed_fields',
        'changes_count',
        'change_summary',
    ];

    protected $hidden = [
        'old_values',
        'new_values',
        'meta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'meta' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'actor_id');
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function eventOptions(): array
    {
        return self::query()
            ->select('event')
            ->distinct()
            ->orderBy('event')
            ->pluck('event')
            ->map(fn (string $event): array => [
                'value' => $event,
                'label' => (new self(['event' => $event]))->event_label,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function resourceTypeOptions(): array
    {
        return self::query()
            ->select('auditable_type')
            ->distinct()
            ->orderBy('auditable_type')
            ->pluck('auditable_type')
            ->map(fn (string $type): array => [
                'value' => $type,
                'label' => (new self(['auditable_type' => $type]))->resource_type_label,
            ])
            ->values()
            ->all();
    }

    public function getEventLabelAttribute(): string
    {
        return Lang::has('audit.events.'.$this->event)
            ? __('audit.events.'.$this->event)
            : (string) $this->event;
    }

    public function getResourceTypeLabelAttribute(): string
    {
        $resourceKey = [
            AdminUser::class => 'admin_user',
            AdminRole::class => 'admin_role',
            MqttAccount::class => 'mqtt_account',
            Config::class => 'config',
        ][(string) $this->auditable_type] ?? null;

        return $resourceKey !== null && Lang::has('audit.resources.'.$resourceKey)
            ? __('audit.resources.'.$resourceKey)
            : Str::headline(class_basename((string) $this->auditable_type));
    }

    public function getRouteAttribute(): ?string
    {
        return is_array($this->meta) ? ($this->meta['route'] ?? null) : null;
    }

    public function getMethodAttribute(): ?string
    {
        return is_array($this->meta) ? ($this->meta['method'] ?? null) : null;
    }

    public function getIpAttribute(): ?string
    {
        return is_array($this->meta) ? ($this->meta['ip'] ?? null) : null;
    }

    /**
     * @return array<int, string>
     */
    public function getChangedFieldsAttribute(): array
    {
        $oldValues = is_array($this->old_values) ? array_keys($this->old_values) : [];
        $newValues = is_array($this->new_values) ? array_keys($this->new_values) : [];

        return Collection::make(array_merge($oldValues, $newValues))
            ->unique()
            ->values()
            ->all();
    }

    public function getChangesCountAttribute(): int
    {
        return count($this->changed_fields);
    }

    public function getChangeSummaryAttribute(): string
    {
        $oldValues = is_array($this->old_values) ? $this->old_values : [];
        $newValues = is_array($this->new_values) ? $this->new_values : [];

        if ($oldValues === [] && $newValues === []) {
            return '';
        }

        if ($oldValues === []) {
            return $this->encodeAuditJson($newValues);
        }

        if ($newValues === []) {
            return $this->encodeAuditJson($oldValues);
        }

        $changes = collect($this->changed_fields)
            ->mapWithKeys(fn (string $field): array => [
                $field => [
                    'old' => $oldValues[$field] ?? null,
                    'new' => $newValues[$field] ?? null,
                ],
            ])
            ->all();

        return $this->encodeAuditJson($changes);
    }

    /**
     * @param  array<string, mixed>  $value
     */
    protected function encodeAuditJson(array $value): string
    {
        return Str::limit(
            json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}',
            160,
        );
    }
}
