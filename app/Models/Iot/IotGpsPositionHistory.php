<?php

namespace App\Models\Iot;

use App\Models\Concerns\ModelSupport;
use App\Support\ListQueryFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $terminal_id
 * @property string $gps_time
 * @property float $latitude
 * @property float $longitude
 * @property float $latitude_gcj
 * @property float $longitude_gcj
 * @property int $altitude
 * @property float $speed
 * @property int $direction
 * @property int $status
 * @property int $alarm
 * @property array $extra
 * @property string $created_at
 */
class IotGpsPositionHistory extends Model
{
    use ModelSupport;

    public const CREATED_AT = 'created_at';

    public const UPDATED_AT = null;

    public $incrementing = false;

    protected $table = 'gps_position_histories';

    protected $primaryKey;

    protected $guarded = [];

    public static function indexQuery(array $queryParameters): Builder
    {
        $query = self::query()
            ->with('device')
            ->latest('gps_time');

        (new ListQueryFilters(
            query: $queryParameters,
            fieldDefinitions: [
                'terminal_id',
                'gps_time',
                'status' => ['integer'],
                'alarm' => ['integer'],
            ],
            callbacks: [
                'search' => function (Builder $query, mixed $value): void {
                    $search = trim((string) $value);
                    $likeSearch = "%{$search}%";

                    $query->where(function (Builder $builder) use ($likeSearch): void {
                        $builder
                            ->whereRaw('LOWER(terminal_id) LIKE LOWER(?)', [$likeSearch])
                            ->orWhereHas('device', function (Builder $deviceQuery) use ($likeSearch): void {
                                $deviceQuery->whereRaw('LOWER(dev_name) LIKE LOWER(?)', [$likeSearch]);
                            });
                    });
                },
            ],
        ))->apply($query);

        return $query;
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(IotDevice::class, 'terminal_id', 'terminal_id');
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
            'gps_time' => 'datetime',
            'created_at' => 'datetime',
            'latitude' => 'float',
            'longitude' => 'float',
            'latitude_gcj' => 'float',
            'longitude_gcj' => 'float',
            'altitude' => 'integer',
            'speed' => 'float',
            'direction' => 'integer',
            'status' => 'integer',
            'alarm' => 'integer',
            'extra' => 'array',
        ];
    }
}
