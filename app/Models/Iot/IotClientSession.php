<?php

namespace App\Models\Iot;

use App\Models\Concerns\ModelSupport;
use App\Support\ListQueryFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $client_id
 * @property null|string $username
 * @property string $last_event_ts
 * @property string $last_event_type
 * @property null|string $last_connect_ts
 * @property null|string $last_disconnect_ts
 * @property null|string $last_peer
 * @property null|string $last_protocol
 * @property null|int $last_reason_code
 * @property null|array $extra
 */
class IotClientSession extends Model
{
    use ModelSupport;

    public const CREATED_AT = null;

    public const UPDATED_AT = null;

    public $incrementing = false;

    protected $table = 'client_sessions';

    protected $primaryKey = 'client_id';

    protected $keyType = 'string';

    protected $guarded = ['client_id'];

    public static function indexQuery(array $queryParameters): Builder
    {
        $query = self::query()
            ->latest('last_event_ts');

        (new ListQueryFilters(
            query: $queryParameters,
            fieldDefinitions: [
                'client_id',
                'username',
                'last_event_type',
                'last_peer',
                'last_protocol',
                'last_reason_code' => ['integer'],
            ],
            callbacks: [
                'search' => function (Builder $query, mixed $value): void {
                    $search = trim((string) $value);
                    $likeSearch = "%{$search}%";

                    $query->where(function (Builder $builder) use ($likeSearch): void {
                        $builder
                            ->whereRaw('LOWER(client_id) LIKE LOWER(?)', [$likeSearch])
                            ->orWhereRaw('LOWER(username) LIKE LOWER(?)', [$likeSearch])
                            ->orWhereRaw('LOWER(last_event_type) LIKE LOWER(?)', [$likeSearch])
                            ->orWhereRaw('LOWER(last_peer) LIKE LOWER(?)', [$likeSearch])
                            ->orWhereRaw('LOWER(last_protocol) LIKE LOWER(?)', [$likeSearch]);
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
            'last_event_ts' => 'datetime',
            'last_connect_ts' => 'datetime',
            'last_disconnect_ts' => 'datetime',
            'extra' => 'array',
        ];
    }
}
