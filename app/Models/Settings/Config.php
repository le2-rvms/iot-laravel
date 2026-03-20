<?php

namespace App\Models\Settings;

use App\Concerns\ResolvesAttributeLabelsFromDocBlocks;
use App\Values\Settings\ConfigCategory;
use App\Values\Settings\ConfigMaskState;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id 配置ID
 * @property string $key 配置键
 * @property string $value 配置值
 * @property ConfigCategory $category 配置分类
 * @property ConfigMaskState $is_masked 是否打码
 * @property string $remark 备注
 * @property Carbon|null $created_at 创建时间
 * @property Carbon|null $updated_at 更新时间
 */
class Config extends Model
{
    use ResolvesAttributeLabelsFromDocBlocks;

    protected $guarded = ['id'];

    protected $appends = [
        'category_label',
        'value_display',
        'is_masked_label',
    ];

    protected $casts = [
        'category' => ConfigCategory::class,
        'is_masked' => ConfigMaskState::class,
    ];

    public function getCategoryLabelAttribute(): string
    {
        /** @var ConfigCategory|null $category */
        $category = $this->category;

        return $category?->label ?? '';
    }

    public function getValueDisplayAttribute(): string
    {
        /** @var ConfigMaskState|null $isMasked */
        $isMasked = $this->is_masked;

        return $isMasked?->isMasked() ? '*****' : (string) $this->value;
    }

    public function getIsMaskedLabelAttribute(): string
    {
        /** @var ConfigMaskState|null $isMasked */
        $isMasked = $this->is_masked;

        return $isMasked?->label ?? '';
    }
}
