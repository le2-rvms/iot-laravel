<?php

namespace App\Models\Iot;

use App\Models\Concerns\ModelSupport;
use App\Support\ListQueryFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $terminal_id
 * @property string $alarm_type
 * @property string $description
 * @property string $gps_time
 * @property string $created_at
 */
class IotGpsAlarm extends Model
{
    use ModelSupport;

    public const CREATED_AT = 'created_at';

    public const UPDATED_AT = null;

    protected $table = 'gps_alarms';

    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    public static function indexQuery(array $queryParameters): Builder
    {
        $query = self::query()
            ->latest('gps_time')
            ->latest('id');

        (new ListQueryFilters(
            query: $queryParameters,
            fieldDefinitions: [
                'id' => ['integer'],
                'terminal_id',
                'alarm_type',
                'description',
            ],
            callbacks: [
                'search' => function (Builder $query, mixed $value): void {
                    $search = trim((string) $value);
                    $likeSearch = "%{$search}%";

                    $query->where(function (Builder $builder) use ($likeSearch): void {
                        $builder
                            ->whereRaw('LOWER(terminal_id) LIKE LOWER(?)', [$likeSearch])
                            ->orWhereRaw('LOWER(alarm_type) LIKE LOWER(?)', [$likeSearch])
                            ->orWhereRaw('LOWER(description) LIKE LOWER(?)', [$likeSearch]);
                    });
                },
            ],
        ))->apply($query);

        return $query;
    }

    protected function casts(): array
    {
        return [
            'gps_time' => 'datetime',
            'created_at' => 'datetime',
        ];
    }
}
