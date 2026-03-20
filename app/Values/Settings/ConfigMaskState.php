<?php

namespace App\Values\Settings;

use App\Enum\EnumLikeBase;
use InvalidArgumentException;

class ConfigMaskState extends EnumLikeBase
{
    public const UNMASKED = 0;

    public const MASKED = 1;

    /**
     * @var array<int, string>
     */
    public const LABELS = [
        self::UNMASKED => '否',
        self::MASKED => '是',
    ];

    public function __construct(mixed $value = null)
    {
        $normalized = match (true) {
            is_bool($value) => $value ? self::MASKED : self::UNMASKED,
            is_numeric($value) => (int) $value,
            default => $value,
        };

        parent::__construct($normalized);

        if (! array_key_exists($normalized, self::LABELS)) {
            throw new InvalidArgumentException("Invalid config mask state [{$normalized}].");
        }
    }

    public function isMasked(): bool
    {
        return $this->value === self::MASKED;
    }
}
