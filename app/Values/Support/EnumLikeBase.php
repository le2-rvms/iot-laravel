<?php

namespace App\Values\Support;

use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

#[\AllowDynamicProperties]
abstract class EnumLikeBase implements Castable
{
    public readonly mixed $value;

    /**
     * @var array<string, string>
     */
    public const LABELS = [];

    public function __construct(mixed $value = null)
    {
        if ($value === null) {
            $this->value = null;

            return;
        }

        $resolvedKey = static::resolveLabelKey($value);

        $this->value = $resolvedKey;
        $this->label = static::LABELS[$resolvedKey];
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

                try {
                    return new ($this->enumClass)($this->enumClass::resolveLabelKey($value));
                } catch (InvalidArgumentException) {
                    return null;
                }
            }

            public function set(Model $model, string $key, mixed $value, array $attributes): mixed
            {
                if ($value === null) {
                    return null;
                }

                if ($value instanceof $this->enumClass) {
                    return $value->value;
                }

                return $this->enumClass::resolveLabelKey($value);
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

        return new static(static::resolveLabelKey($key));
    }

    public static function toCaseSQL(bool $hasAs = true, ?string $fieldName = null, ?string $alias = null): string
    {
        // 默认按类名推导字段名，让 Category / Enabled 这类值对象能直接和字段约定对齐。
        $fieldName ??= static::inferFieldName();

        if (! is_string($fieldName) || $fieldName === '') {
            throw new InvalidArgumentException('Missing enum field name for CASE SQL generation.');
        }

        $alias ??= preg_replace('/^[^.]*\./', '', $fieldName).'_label';

        if (DB::getDriverName() === 'pgsql') {
            $entries = [];

            foreach (static::LABELS as $key => $label) {
                $entries[] = sprintf(
                    "'%s', '%s'",
                    self::escapeSqlString((string) $key),
                    self::escapeSqlString($label),
                );
            }

            // PostgreSQL 目标环境下直接在查询层产出 label，前端列表无需再做值到文案的判断。
            return sprintf(
                "(jsonb_build_object(%s) ->> %s::text)%s",
                implode(', ', $entries),
                $fieldName,
                $hasAs ? " as {$alias}" : '',
            );
        }

        $caseSql = "CASE {$fieldName} ";

        foreach (static::LABELS as $key => $label) {
            $caseSql .= sprintf(
                "WHEN %s THEN '%s' ",
                self::quoteSqlLiteral($key),
                self::escapeSqlString($label),
            );
        }

        $caseSql .= "ELSE '' END";

        if ($hasAs) {
            $caseSql .= " as {$alias}";
        }

        return $caseSql;
    }

    private static function quoteSqlLiteral(mixed $value): string
    {
        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return "'".self::escapeSqlString((string) $value)."'";
    }

    private static function escapeSqlString(string $value): string
    {
        return str_replace("'", "''", $value);
    }

    public static function resolveLabelKey(mixed $value): mixed
    {
        if (is_bool($value)) {
            // 兼容表单/勾选框传入的 true/false，同时仍以 LABELS 中定义的 0/1 为准。
            $boolKey = $value ? 1 : 0;

            if (array_key_exists($boolKey, static::LABELS)) {
                return $boolKey;
            }
        }

        if (is_string($value) && preg_match('/^-?(0|[1-9]\d*)$/', $value) === 1) {
            // 兼容数据库或请求里常见的数字字符串输入，但只接受 LABELS 里实际存在的 key。
            $intKey = (int) $value;

            if (array_key_exists($intKey, static::LABELS)) {
                return $intKey;
            }
        }

        if (array_key_exists($value, static::LABELS)) {
            return $value;
        }

        throw new InvalidArgumentException("Invalid {$value} value: ".static::class);
    }

    private static function inferFieldName(): string
    {
        return Str::of(class_basename(static::class))
            ->snake()
            ->value();
    }
}
