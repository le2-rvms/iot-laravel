<?php

namespace App\Models\Iot;

use App\Models\Concerns\ModelSupport;
use App\Support\ListQueryFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $product_id
 * @property string $product_key
 * @property string $product_name
 * @property null|string $description
 * @property null|string $manufacturer
 * @property null|string $protocol
 * @property null|string $category
 * @property null|string $created_at
 * @property null|string $updated_at
 * @property int $devices_count
 * @property int $groups_count
 */
class IotDeviceProduct extends Model
{
    use ModelSupport;

    public const CREATED_AT = 'created_at';

    public const UPDATED_AT = 'updated_at';

    protected $table = 'device_products';

    protected $primaryKey = 'product_id';

    protected $guarded = ['product_id'];

    public static function indexQuery(array $queryParameters): Builder
    {
        $query = self::query()
            ->withCount(['devices', 'groups'])
            ->latest('product_id');

        (new ListQueryFilters(
            query: $queryParameters,
            fieldDefinitions: [
                'product_id' => ['integer'],
                'product_key',
                'product_name',
                'manufacturer',
                'protocol',
                'category',
            ],
            callbacks: [
                'search' => function (Builder $query, mixed $value): void {
                    $search = trim((string) $value);
                    $likeSearch = "%{$search}%";

                    $query->where(function (Builder $builder) use ($likeSearch): void {
                        $builder
                            ->whereRaw('LOWER(product_key) LIKE LOWER(?)', [$likeSearch])
                            ->orWhereRaw('LOWER(product_name) LIKE LOWER(?)', [$likeSearch])
                            ->orWhereRaw('LOWER(description) LIKE LOWER(?)', [$likeSearch])
                            ->orWhereRaw('LOWER(manufacturer) LIKE LOWER(?)', [$likeSearch])
                            ->orWhereRaw('LOWER(protocol) LIKE LOWER(?)', [$likeSearch])
                            ->orWhereRaw('LOWER(category) LIKE LOWER(?)', [$likeSearch]);
                    });
                },
            ],
        ))->apply($query);

        return $query;
    }

    public function devices(): HasMany
    {
        return $this->hasMany(IotDevice::class, 'product_key', 'product_key');
    }

    public function groups(): HasMany
    {
        return $this->hasMany(IotDeviceGroup::class, 'product_key', 'product_key');
    }

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
