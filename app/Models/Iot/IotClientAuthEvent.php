<?php

namespace App\Models\Iot;

use App\Models\Concerns\ModelSupport;
use App\Support\ListQueryFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $ts
 * @property string $result
 * @property string $reason
 * @property null|string $client_id
 * @property null|string $username
 * @property null|string $peer
 * @property null|string $protocol
 */
class IotClientAuthEvent extends Model
{
    use ModelSupport;

    public const CREATED_AT = null;

    public const UPDATED_AT = null;

    protected $table = 'client_auth_events';

    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    public static function indexQuery(array $queryParameters): Builder
    {
        $query = self::query()
            ->latest('ts')
            ->latest('id');

        (new ListQueryFilters(
            query: $queryParameters,
            fieldDefinitions: [
                'id' => ['integer'],
                'result',
                'reason',
                'client_id',
                'username',
                'peer',
                'protocol',
            ],
            callbacks: [
                'search' => function (Builder $query, mixed $value): void {
                    $search = trim((string) $value);
                    $likeSearch = "%{$search}%";

                    $query->where(function (Builder $builder) use ($likeSearch): void {
                        $builder
                            ->whereRaw('LOWER(result) LIKE LOWER(?)', [$likeSearch])
                            ->orWhereRaw('LOWER(reason) LIKE LOWER(?)', [$likeSearch])
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

    protected function casts(): array
    {
        return [
            'ts' => 'datetime',
        ];
    }
}
