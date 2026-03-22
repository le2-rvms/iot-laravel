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
        $class = Str::after(static::class, 'App\\Models\\');
        $segments = array_map(
            static fn (string $segment): string => Str::snake($segment),
            explode('\\', $class),
        );

        return 'models.'.implode('.', $segments).'.attributes';
    }
}
