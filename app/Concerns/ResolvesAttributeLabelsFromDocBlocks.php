<?php

namespace App\Concerns;

use ReflectionClass;

trait ResolvesAttributeLabelsFromDocBlocks
{
    /**
     * @var array<class-string, array<string, string>>
     */
    private static array $attributeLabelsCache = [];

    /**
     * @return array<string, string>
     */
    public static function attributeLabels(): array
    {
        return self::$attributeLabelsCache[static::class] ??= self::resolveAttributeLabels();
    }

    /**
     * @return array<string, string>
     */
    private static function resolveAttributeLabels(): array
    {
        $docComment = (new ReflectionClass(static::class))->getDocComment();

        if ($docComment === false) {
            return [];
        }

        preg_match_all(
            '/^\s*\*\s*@property(?:-read|-write)?\s+(.+?)\s+\$([^\s]+)\s+(.+?)\s*$/m',
            $docComment,
            $matches,
            PREG_SET_ORDER,
        );

        $labels = [];

        foreach ($matches as $match) {
            $labels[$match[2]] = trim($match[3]);
        }

        return $labels;
    }
}
