<?php

namespace App\Models\Iot;

use App\Models\Concerns\ModelSupport;
use App\Support\ListQueryFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $device_id
 * @property int $group_id
 */
class IotDeviceGroupMapping extends Model
{
    use ModelSupport;

    public const CREATED_AT = null;

    public const UPDATED_AT = null;

    protected $table = 'device_group_mappings';

    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    public static function indexQuery(array $queryParameters): Builder
    {
        $query = self::query()
            ->with(['group', 'mqttAccount'])
            ->latest('id');

        (new ListQueryFilters(
            query: $queryParameters,
            fieldDefinitions: [
                'id' => ['integer'],
                'device_id',
                'group_id' => ['integer'],
            ],
            callbacks: [
                'search' => function (Builder $query, mixed $value): void {
                    $search = trim((string) $value);
                    $likeSearch = "%{$search}%";

                    $query->where(function (Builder $builder) use ($likeSearch): void {
                        $builder
                            ->whereRaw('LOWER(device_id) LIKE LOWER(?)', [$likeSearch]);
                    });
                },
            ],
        ))->apply($query);

        return $query;
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(IotDeviceGroup::class, 'group_id', 'group_id');
    }

    public function mqttAccount(): BelongsTo
    {
        return $this->belongsTo(IotMqttAccount::class, 'device_id', 'user_name');
    }
}
