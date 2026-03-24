<?php

declare(strict_types=1);

namespace App\Models;

use App\Enum\EnumLikeBase;
use App\Models\Auth\AdminRole;
use App\Models\Auth\AdminUser;
use App\Models\Iot\MqttAccount;
use App\Models\Settings\Config;
use App\Support\ListQueryFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
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

    protected bool $hasResolvedAuditableModel = false;

    protected ?Model $auditableModelInstance = null;

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
     * @return Builder<self>
     */
    public static function indexQuery(array $queryParameters): Builder
    {
        $query = self::query()
            ->with('actor:id,name,email')
            ->latest('created_at')
            ->latest('id');

        (new ListQueryFilters(
            query: $queryParameters,
            fieldDefinitions: [
                'event',
                'auditable_type',
            ],
            callbacks: [
                'search' => function (Builder $query, mixed $value): void {
                    $search = trim((string) $value);

                    $query->where(function (Builder $nestedQuery) use ($search): void {
                        $nestedQuery->whereHas('actor', function (Builder $actorQuery) use ($search): void {
                            $actorQuery
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        })->orWhere('meta->route', 'like', "%{$search}%");

                        if (ctype_digit($search)) {
                            $nestedQuery->orWhere('auditable_id', (int) $search);
                        }
                    });
                },
            ],
        ))->apply($query);

        return $query;
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
        return Lang::has('controllers.events.'.$this->event)
            ? __('controllers.events.'.$this->event)
            : (string) $this->event;
    }

    public function getResourceTypeLabelAttribute(): string
    {
        $groupKey = [
            AdminUser::class => 'admin-user',
            AdminRole::class => 'admin-role',
            MqttAccount::class => 'mqtt-account',
            Config::class => 'config',
        ][(string) $this->auditable_type] ?? null;

        return $groupKey !== null && Lang::has('controllers.groups.'.$groupKey)
            ? __('controllers.groups.'.$groupKey)
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

        return array_values(array_unique(array_map(
            fn (string $field): string => $this->displayField($field),
            array_merge($oldValues, $newValues),
        )));
    }

    public function getChangesCountAttribute(): int
    {
        return count($this->changed_fields);
    }

    public function getChangeSummaryAttribute(): string
    {
        $changes = $this->displaySummaryChanges();

        if ($changes === []) {
            return '';
        }

        return $this->encodeAuditJson($changes);
    }

    /**
     * @return array<string, mixed>
     */
    protected function displaySummaryChanges(): array
    {
        $oldValues = is_array($this->old_values) ? $this->old_values : [];
        $newValues = is_array($this->new_values) ? $this->new_values : [];

        if ($oldValues === [] && $newValues === []) {
            return [];
        }

        if ($oldValues === []) {
            return $this->translateSummaryFields($this->displaySingleSideSummary($newValues, '[已设置]'));
        }

        if ($newValues === []) {
            return $this->translateSummaryFields($this->displaySingleSideSummary($oldValues, '[已隐藏]'));
        }

        $changes = [];

        foreach (array_unique(array_merge(array_keys($oldValues), array_keys($newValues))) as $field) {
            $displayField = $this->displayField((string) $field);
            $oldValue = $oldValues[$field] ?? null;
            $newValue = $newValues[$field] ?? null;

            if ($oldValue === '[已隐藏]' && $newValue === '[已隐藏]') {
                $changes[$displayField] = '[已修改]';

                continue;
            }

            if (! array_key_exists($displayField, $changes)) {
                $changes[$displayField] = $this->displayTransition((string) $field, $oldValue, $newValue);
            }
        }

        return $this->translateSummaryFields($changes);
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    protected function displaySingleSideSummary(array $values, string $maskedValue): array
    {
        $changes = [];

        foreach ($values as $field => $value) {
            $displayField = $this->displayField((string) $field);

            if ($value === '[已隐藏]') {
                $changes[$displayField] = $maskedValue;

                continue;
            }

            if (! array_key_exists($displayField, $changes)) {
                $changes[$displayField] = $value;
            }
        }

        return $changes;
    }

    protected function displayField(string $field): string
    {
        if ($this->auditable_type === MqttAccount::class && in_array($field, ['password_hash', 'salt'], true)) {
            return 'password';
        }

        return $field;
    }

    /**
     * @param  array<string, mixed>  $changes
     * @return array<string, mixed>
     */
    protected function translateSummaryFields(array $changes): array
    {
        $translated = [];

        foreach ($changes as $field => $value) {
            $translated[$this->summaryFieldLabel($field)] = $value;
        }

        return $translated;
    }

    protected function summaryFieldLabel(string $field): string
    {
        $model = $this->auditableModel();

        if ($model !== null && method_exists($model, 'attributeLabels')) {
            $labels = $model::attributeLabels();

            if (isset($labels[$field]) && is_string($labels[$field])) {
                return $labels[$field];
            }
        }

        return $field;
    }

    protected function displayTransition(string $field, mixed $oldValue, mixed $newValue): string
    {
        return $this->displayValue($field, $oldValue).' → '.$this->displayValue($field, $newValue);
    }

    protected function displayValue(string $field, mixed $value): string
    {
        if ($value === null) {
            return '空';
        }

        $labeledValue = $this->displayLabeledValue($field, $value);

        if ($labeledValue !== null) {
            return $labeledValue;
        }

        if (is_bool($value)) {
            return $value ? '是' : '否';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
    }

    protected function displayLabeledValue(string $field, mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $labels = $this->fieldValueLabels($field);

        if (! is_array($labels)) {
            return null;
        }

        return $labels[(int) $value] ?? null;
    }

    /**
     * @return array<int|string, string>|null
     */
    protected function fieldValueLabels(string $field): ?array
    {
        $model = $this->auditableModel();

        if ($model === null) {
            return null;
        }

        $casts = $model->getCasts();
        $castClass = $casts[$field] ?? null;

        if (! is_string($castClass) || ! class_exists($castClass)) {
            return null;
        }

        if (! is_subclass_of($castClass, EnumLikeBase::class)) {
            return null;
        }

        /** @var class-string<EnumLikeBase> $castClass */
        return $castClass::LABELS;
    }

    protected function auditableModel(): ?Model
    {
        if ($this->hasResolvedAuditableModel) {
            return $this->auditableModelInstance;
        }

        $this->hasResolvedAuditableModel = true;

        $auditableType = (string) $this->auditable_type;

        if (! class_exists($auditableType)) {
            return null;
        }

        $this->auditableModelInstance = new $auditableType;

        return $this->auditableModelInstance;
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
