<?php

namespace App\Concerns;

use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;

trait HasTranslatedAttributeLabels
{
    /**
     * @return array<string, string>
     */
    public static function attributeLabels(): array
    {
        $labels = Lang::get(static::attributeLabelsTranslationKey());

        return is_array($labels) ? $labels : [];
    }

    protected static function attributeLabelsTranslationKey(): string
    {
        return 'models.'.Str::snake(class_basename(static::class));
    }
}
