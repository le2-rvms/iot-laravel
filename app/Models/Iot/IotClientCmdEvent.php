<?php

namespace App\Models\Iot;

use App\Models\Concerns\ModelSupport;
use App\Support\ListQueryFilters;
use App\Values\Iot\EventType_CMD;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $ts
 * @property EventType_CMD|string $event_type
 * @property string $client_id
 * @property null|string $username
 * @property null|string $peer
 * @property null|string $protocol
 * @property null|int $reason_code
 * @property null|array $extra
 */
class IotClientCmdEvent extends Model
{
    use ModelSupport;

    public const CREATED_AT = null;

    public const UPDATED_AT = null;

    protected $table = 'client_cmd_events';

    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    protected $appends = [
        'event_type_label',
    ];

    public static function indexQuery(array $queryParameters): Builder
    {
        $query = self::query()
            ->latest('ts')
            ->latest('id');

        (new ListQueryFilters(
            query: $queryParameters,
            fieldDefinitions: [
                'id' => ['integer'],
                'event_type',
                'client_id',
                'username',
                'peer',
                'protocol',
                'reason_code' => ['integer'],
            ],
            callbacks: [
                'search' => function (Builder $query, mixed $value): void {
                    $search = trim((string) $value);
                    $likeSearch = "%{$search}%";

                    $query->where(function (Builder $builder) use ($likeSearch): void {
                        $builder
                            ->whereRaw('LOWER(event_type) LIKE LOWER(?)', [$likeSearch])
                            ->orWhereRaw('LOWER(client_id) LIKE LOWER(?)', [$likeSearch])
                            ->orWhereRaw('LOWER(username) LIKE LOWER(?)', [$likeSearch])
                            ->orWhereRaw('LOWER(peer) LIKE LOWER(?)', [$likeSearch])
                            ->orWhereRaw('LOWER(protocol) LIKE LOWER(?)', [$likeSearch]);
                    });
                },
            ],
        ))->apply($query);

        return $query;
    }

    public function auditExcept(): array
    {
        return array_values(array_unique(array_filter([
            $this->getKeyName(),
            $this->getCreatedAtColumn(),
            $this->getUpdatedAtColumn(),
            'extra',
        ])));
    }

    protected function casts(): array
    {
        return [
            'ts' => 'datetime',
            'reason_code' => 'integer',
            'event_type' => EventType_CMD::class,
            'extra' => 'array',
        ];
    }

    public function getEventTypeLabelAttribute(): string
    {
        /** @var EventType_CMD|null $eventType */
        $eventType = $this->event_type;

        return $eventType?->label ?? '';
    }
}
