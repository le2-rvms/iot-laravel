<?php

namespace App\Enum;

use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

#[\AllowDynamicProperties]
abstract class EnumLikeBase implements Castable
{
    /**
     * @var array<string, string>
     */
    public const LABELS = [];

    public function __construct(
        public readonly mixed $value = null,
    ) {
        if ($value !== null) {
            $this->label = static::LABELS[$value] ?? null;
        }
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }

    public static function castUsing(array $arguments): CastsAttributes
    {
        $enumClass = static::class;

        return new readonly class($enumClass) implements CastsAttributes {
            public function __construct(
                private string $enumClass,
            ) {}

            public function get(Model $model, string $key, mixed $value, array $attributes): ?object
            {
                if ($value === null) {
                    return null;
                }

                $labels = $this->enumClass::LABELS;

                if (array_key_exists($value, $labels)) {
                    return new ($this->enumClass)($value);
                }

                return null;
            }

            public function set(Model $model, string $key, mixed $value, array $attributes): mixed
            {
                if ($value === null) {
                    return null;
                }

                if ($value instanceof $this->enumClass) {
                    return $value->value;
                }

                $labels = $this->enumClass::LABELS;

                if (array_key_exists($value, $labels)) {
                    return $value;
                }

                throw new InvalidArgumentException("Invalid {$key} value: ".var_export($value, true));
            }

            public function serialize(Model $model, string $key, mixed $value, array $attributes): mixed
            {
                return $value instanceof $this->enumClass ? $value->value : $value;
            }
        };
    }

    /**
     * @return array<int, string>
     */
    public static function labelKeys(): array
    {
        return array_keys(static::LABELS);
    }

    public static function tryFrom(mixed $key): ?static
    {
        if ($key === null || $key === '') {
            return null;
        }

        if (array_key_exists($key, static::LABELS)) {
            return new static($key);
        }

        throw new InvalidArgumentException("Invalid {$key} value: ".static::class);
    }
}
