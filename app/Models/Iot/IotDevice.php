<?php

namespace App\Models\Iot;

use App\Models\Concerns\ModelSupport;
use App\Support\ListQueryFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $dev_id
 * @property string $terminal_id
 * @property string $dev_name
 * @property null|string $company_id
 * @property null|string $manufacturer_id
 * @property null|string $product_key
 * @property null|string $sim_number
 * @property null|string $_vehicle_plate
 * @property null|string $_vehicle_vin
 * @property null|string $_bind_status
 * @property null|string $device_status
 * @property null|string $review_status
 * @property null|string $auth_code_seed
 * @property null|string $auth_code_issued_at
 * @property null|string $auth_code_expires_at
 * @property null|int $auth_failures
 * @property null|string $auth_block_until
 * @property null|int $city_relation_id
 * @property string $created_at
 */
class IotDevice extends Model
{
    use ModelSupport;

    public const CREATED_AT = 'created_at';

    public const UPDATED_AT = null;

    public $incrementing = false;

    protected $table = 'devices';

    protected $keyType = 'string';

    protected $primaryKey = 'terminal_id';

    protected $fillable = [
        'terminal_id',
        'dev_name',
        'company_id',
        'manufacturer_id',
        'product_key',
        'sim_number',
        '_vehicle_plate',
        '_vehicle_vin',
        '_bind_status',
        'device_status',
        'review_status',
        'auth_code_seed',
        'auth_code_issued_at',
        'auth_code_expires_at',
        'auth_failures',
        'auth_block_until',
        'city_relation_id',
    ];

    public static function indexQuery(array $queryParameters): Builder
    {
        $query = self::query()
            ->with('deviceProduct')
            ->latest('dev_id');

        (new ListQueryFilters(
            query: $queryParameters,
            fieldDefinitions: [
                'terminal_id',
                'dev_name',
                'product_key',
                'sim_number',
                'device_status',
                'review_status',
                'city_relation_id' => ['integer'],
            ],
            callbacks: [
                'search' => function (Builder $query, mixed $value): void {
                    $search = trim((string) $value);
                    $likeSearch = "%{$search}%";

                    $query->where(function (Builder $builder) use ($likeSearch): void {
                        $builder
                            ->whereRaw('LOWER(terminal_id) LIKE LOWER(?)', [$likeSearch])
                            ->orWhereRaw('LOWER(dev_name) LIKE LOWER(?)', [$likeSearch])
                            ->orWhereRaw('LOWER(product_key) LIKE LOWER(?)', [$likeSearch])
                            ->orWhereRaw('LOWER(sim_number) LIKE LOWER(?)', [$likeSearch])
                            ->orWhereRaw('LOWER(_vehicle_plate) LIKE LOWER(?)', [$likeSearch])
                            ->orWhereRaw('LOWER(_vehicle_vin) LIKE LOWER(?)', [$likeSearch]);
                    });
                },
            ],
        ))->apply($query);

        return $query;
    }

    public function deviceProduct(): BelongsTo
    {
        return $this->belongsTo(IotDeviceProduct::class, 'product_key', 'product_key');
    }

    public function gpsCommands(): HasMany
    {
        return $this->hasMany(IotGpsCommand::class, 'device_id', 'dev_id');
    }

    public function gpsPositionHistories(): HasMany
    {
        return $this->hasMany(IotGpsPositionHistory::class, 'terminal_id', 'terminal_id');
    }

    public function gpsPositionLast(): HasOne
    {
        return $this->hasOne(IotGpsPositionLast::class, 'terminal_id', 'terminal_id');
    }

    /**
     * @return array<int, string>
     */
    public function auditMask(): array
    {
        return [
            'auth_code_seed',
        ];
    }

    protected function casts(): array
    {
        return [
            'auth_code_issued_at' => 'datetime',
            'auth_code_expires_at' => 'datetime:Y-m-d H:i:s',
            'auth_block_until' => 'datetime:Y-m-d H:i:s',
            'created_at' => 'datetime',
            'auth_failures' => 'integer',
            'city_relation_id' => 'integer',
        ];
    }
}
