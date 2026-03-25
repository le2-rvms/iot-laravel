<?php

namespace App\Models\Iot;

use App\Models\Concerns\ModelSupport;
use App\Support\ListQueryFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property float $center_lat
 * @property float $center_lon
 * @property float $radius_meters
 * @property bool $active
 * @property string $created_at
 */
class IotGpsGeofence extends Model
{
    use ModelSupport;

    public const CREATED_AT = 'created_at';

    public const UPDATED_AT = null;

    protected $table = 'gps_geofences';

    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    public static function indexQuery(array $queryParameters): Builder
    {
        $query = self::query()
            ->latest('id');

        (new ListQueryFilters(
            query: $queryParameters,
            fieldDefinitions: [
                'id' => ['integer'],
                'name',
                'active' => ['boolean'],
            ],
            callbacks: [
                'search' => function (Builder $query, mixed $value): void {
                    $search = trim((string) $value);

                    $query->whereRaw('LOWER(name) LIKE LOWER(?)', ["%{$search}%"]);
                },
            ],
        ))->apply($query);

        return $query;
    }

    protected function casts(): array
    {
        return [
            'center_lat' => 'float',
            'center_lon' => 'float',
            'radius_meters' => 'float',
            'active' => 'boolean',
            'created_at' => 'datetime',
        ];
    }
}
