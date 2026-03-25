<?php

namespace App\Models\Iot;

use App\Models\Concerns\ModelSupport;
use App\Support\ListQueryFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $group_id
 * @property string $group_name
 * @property null|string $description
 * @property string $product_key
 * @property null|string $created_at
 * @property null|string $updated_at
 */
class IotDeviceGroup extends Model
{
    use ModelSupport;

    public const CREATED_AT = 'created_at';

    public const UPDATED_AT = 'updated_at';

    protected $table = 'device_groups';

    protected $primaryKey = 'group_id';

    protected $guarded = ['group_id'];

    public static function indexQuery(array $queryParameters): Builder
    {
        $query = self::query()
            ->with('product')
            ->latest('group_id');

        (new ListQueryFilters(
            query: $queryParameters,
            fieldDefinitions: [
                'group_id' => ['integer'],
                'group_name',
                'description',
                'product_key',
            ],
            callbacks: [
                'search' => function (Builder $query, mixed $value): void {
                    $search = trim((string) $value);
                    $likeSearch = "%{$search}%";

                    $query->where(function (Builder $builder) use ($likeSearch): void {
                        $builder
                            ->whereRaw('LOWER(group_name) LIKE LOWER(?)', [$likeSearch])
                            ->orWhereRaw('LOWER(description) LIKE LOWER(?)', [$likeSearch])
                            ->orWhereRaw('LOWER(product_key) LIKE LOWER(?)', [$likeSearch]);
                    });
                },
            ],
        ))->apply($query);

        return $query;
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(IotDeviceProduct::class, 'product_key', 'product_key');
    }

    public function mappings(): HasMany
    {
        return $this->hasMany(IotDeviceGroupMapping::class, 'group_id', 'group_id');
    }

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
