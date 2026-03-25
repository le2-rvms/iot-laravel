<?php

namespace App\Models\Iot;

use App\Models\Concerns\ModelSupport;
use App\Support\ListQueryFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $device_id
 * @property string $terminal_id
 * @property string $cmd_type
 * @property string $payload
 * @property int $flow_id
 * @property string $status
 * @property int $retries
 * @property int $max_retries
 * @property string $created_at
 * @property string $updated_at
 */
class IotGpsCommand extends Model
{
    use ModelSupport;

    public const CREATED_AT = 'created_at';

    public const UPDATED_AT = 'updated_at';

    protected $table = 'gps_commands';

    protected $primaryKey = 'id';

    protected $guarded = ['id'];

    public static function indexQuery(array $queryParameters): Builder
    {
        $query = self::query()
            ->with('device')
            ->latest('id');

        (new ListQueryFilters(
            query: $queryParameters,
            fieldDefinitions: [
                'id' => ['integer'],
                'device_id' => ['integer'],
                'terminal_id',
                'cmd_type',
                'flow_id' => ['integer'],
                'status',
            ],
            callbacks: [
                'search' => function (Builder $query, mixed $value): void {
                    $search = trim((string) $value);
                    $likeSearch = "%{$search}%";

                    $query->where(function (Builder $builder) use ($likeSearch): void {
                        $builder
                            ->whereRaw('LOWER(terminal_id) LIKE LOWER(?)', [$likeSearch])
                            ->orWhereRaw('LOWER(cmd_type) LIKE LOWER(?)', [$likeSearch])
                            ->orWhereRaw('LOWER(status) LIKE LOWER(?)', [$likeSearch]);
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
            'payload',
        ])));
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(IotDevice::class, 'device_id', 'dev_id');
    }

    protected function casts(): array
    {
        return [
            'device_id' => 'integer',
            'flow_id' => 'integer',
            'retries' => 'integer',
            'max_retries' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
