<?php

namespace App\Models\Settings;

use App\Models\Concerns\HasTranslatedAttributesAndUpdatedBy;
use App\Values\Settings\Category;
use App\Values\Settings\IsMasked;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * @property int $id 配置ID
 * @property string $key 配置键
 * @property string $value 配置值
 * @property Category $category 配置分类
 * @property IsMasked $is_masked 是否打码
 * @property string $remark 备注
 * @property Carbon|null $created_at 创建时间
 * @property Carbon|null $updated_at 更新时间
 */
class Config extends Model
{
    use HasTranslatedAttributesAndUpdatedBy;

    protected $guarded = ['id'];

    // 列表和表单直接依赖这些展示字段，避免前端重复判断分类文案和打码逻辑。
    protected $appends = [
        'category_label',
        'value_display',
        'is_masked_label',
    ];

    protected $casts = [
        'category' => Category::class,
        'is_masked' => IsMasked::class,
    ];

    public function getCategoryLabelAttribute(): string
    {
        /** @var Category|null $category */
        $category = $this->category;

        // 分类文案在模型层统一产出，列表和表单都不需要再关心枚举到文案的映射。
        return $category?->label ?? '';
    }

    public function getValueDisplayAttribute(): string
    {
        /** @var IsMasked|null $isMasked */
        $isMasked = $this->is_masked;

        // 打码展示放在模型层统一处理，避免前端误把真实配置值直接渲染出来。
        return $isMasked?->isMasked() ? '*****' : (string) $this->value;
    }

    public function getIsMaskedLabelAttribute(): string
    {
        /** @var IsMasked|null $isMasked */
        $isMasked = $this->is_masked;

        return $isMasked?->label ?? '';
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function createConfig(array $attributes): self
    {
        return DB::transaction(function () use ($attributes): self {
            $config = (new self)->fill($attributes);

            $config->save();

            return $config;
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function updateConfig(array $attributes): self
    {
        return DB::transaction(function () use ($attributes): self {
            $this->update($attributes);

            return $this->fresh();
        });
    }

    public function deleteConfig(): void
    {
        DB::transaction(function (): void {
            $this->delete();
        });
    }
}
