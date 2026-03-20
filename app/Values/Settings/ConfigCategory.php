<?php

namespace App\Values\Settings;

use App\Enum\EnumLikeBase;
use InvalidArgumentException;

class ConfigCategory extends EnumLikeBase
{
    public const APPLICATION = 'application';

    public const SYSTEM = 'system';

    /**
     * @var array<string, string>
     */
    public const LABELS = [
        self::APPLICATION => '应用配置',
        self::SYSTEM => '系统配置',
    ];

    public function __construct(mixed $value = null)
    {
        parent::__construct($value);

        if (! array_key_exists($value, self::LABELS)) {
            throw new InvalidArgumentException("Invalid config category [{$value}].");
        }
    }
}
