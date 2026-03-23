<?php

namespace App\Support;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * 统一处理后台列表页的语义化查询参数。
 *
 * 组件职责只有三件事：解析 DSL、校验白名单/规则、把合法条件应用到 Builder。
 */
class ListQueryFilters
{
    // page 由分页器自己消费，不参与列表查询 DSL 校验。
    private const PASSTHROUGH_KEYS = [
        'page',
    ];

    private const DEFAULT_OPERATORS = [
        'eq',
        'ne',
        'gt',
        'gte',
        'lt',
        'lte',
        'like',
        'in',
    ];

    public function __construct(
        private readonly Request $request,
        private readonly array $fieldDefinitions,
        private readonly array $callbacks = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function apply(Builder $query): array
    {
        $filters = [];
        $errors = [];
        $normalizedFields = $this->normalizedFields();

        // 只接受语义化 query key，所有非法条件先聚合，再一次性返回 422。
        foreach ($this->request->query() as $key => $rawValue) {
            if (in_array($key, self::PASSTHROUGH_KEYS, true)) {
                continue;
            }

            $parsed = $this->parseFilterKey($key);

            if ($parsed === null) {
                $errors[$key][] = '筛选条件格式无效，必须使用 field__operator 形式。';

                continue;
            }

            [$name, $operator] = $parsed;

            if ($this->isSkippableValue($rawValue)) {
                continue;
            }

            if ($operator === 'func') {
                // func 走显式注册的 query builder 回调，不复用字段白名单。
                $callback = $this->callbacks[$name] ?? null;

                if (! $callback instanceof Closure) {
                    $errors[$key][] = '未注册该筛选回调。';

                    continue;
                }

                $callback($query, $rawValue);
                $filters[$key] = $rawValue;

                continue;
            }

            $field = $normalizedFields[$name] ?? null;

            if ($field === null) {
                $errors[$key][] = '该字段不允许作为筛选条件。';

                continue;
            }

            if (! in_array($operator, $field['operators'], true)) {
                $errors[$key][] = '该字段不支持当前筛选操作符。';

                continue;
            }

            $result = $this->normalizeValidatedValue($rawValue, $field['rules'], $operator);

            if ($result['errors'] !== []) {
                $errors[$key] = $result['errors'];

                continue;
            }

            $value = $result['value'];

            if ($this->isSkippableValue($value)) {
                continue;
            }

            $this->applyOperator($query, $name, $operator, $value);
            $filters[$key] = $rawValue;
        }

        if ($errors !== []) {
            // 列表筛选参数属于局部契约，直接在这里返回 JSON 422，避免影响全局异常流。
            throw new HttpResponseException(response()->json([
                'message' => '给定的数据无效。',
                'errors' => $errors,
            ], 422));
        }

        return $filters;
    }

    /**
     * @return array<string, array{operators: array<int, string>, rules: array<int, mixed>}>
     */
    private function normalizedFields(): array
    {
        $normalized = [];

        foreach ($this->fieldDefinitions as $key => $definition) {
            if (is_int($key)) {
                // 简写字段表示“允许查询，但不附带额外验证规则”。
                $normalized[$definition] = [
                    'operators' => self::DEFAULT_OPERATORS,
                    'rules' => [],
                ];

                continue;
            }

            $rules = $this->extractRules($definition);

            $normalized[$key] = [
                'operators' => $this->inferOperators($rules),
                'rules' => $rules,
            ];
        }

        return $normalized;
    }

    /**
     * @return array{string, string}|null
     */
    private function parseFilterKey(string $key): ?array
    {
        // 统一限制为 field__operator，避免把任意 query key 都当成 DSL 条件。
        if (! preg_match('/^([A-Za-z_][A-Za-z0-9_]*)__([A-Za-z_][A-Za-z0-9_]*)$/', $key, $matches)) {
            return null;
        }

        return [$matches[1], $matches[2]];
    }

    private function isSkippableValue(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        if (is_string($value)) {
            // 空白字符串视为“未传”，保持和前端清空搜索框后的行为一致。
            return trim($value) === '';
        }

        if (is_array($value)) {
            // 数组场景下，只要全部元素都是空值，就等同于未传。
            return collect($value)
                ->filter(fn (mixed $item): bool => ! $this->isSkippableValue($item))
                ->isEmpty();
        }

        return false;
    }

    /**
     * @param  array<int, mixed>  $rules
     * @return array{value: mixed, errors: array<int, string>}
     */
    private function normalizeValidatedValue(mixed $value, array $rules, string $operator): array
    {
        if ($operator === 'in') {
            // in 既接受重复 query 参数数组，也接受逗号分隔字符串。
            $values = is_array($value) ? $value : explode(',', (string) $value);

            $values = collect($values)
                ->map(fn (mixed $item) => is_string($item) ? trim($item) : $item)
                ->filter(fn (mixed $item): bool => ! $this->isSkippableValue($item))
                ->values()
                ->all();

            $errors = $this->validateValue($values, $rules, true);

            if ($errors !== []) {
                return [
                    'value' => null,
                    'errors' => $errors,
                ];
            }

            return [
                'value' => array_map(fn (mixed $item) => $this->normalizeValue($item, $rules), $values),
                'errors' => [],
            ];
        }

        $normalizedValue = is_string($value) ? trim($value) : $value;
        $errors = $this->validateValue($normalizedValue, $rules, false);

        if ($errors !== []) {
            return [
                'value' => null,
                'errors' => $errors,
            ];
        }

        return [
            'value' => $this->normalizeValue($normalizedValue, $rules),
            'errors' => [],
        ];
    }

    /**
     * @param  array<int, mixed>  $rules
     * @return array<int, string>
     */
    private function validateValue(mixed $value, array $rules, bool $isArray): array
    {
        if ($rules === []) {
            return [];
        }

        // 列表查询只做轻量字段校验，不引入完整 FormRequest。
        $rules = $isArray
            ? [
                'value' => ['array'],
                'value.*' => $rules,
            ]
            : [
                'value' => $rules,
            ];

        $validator = Validator::make(['value' => $value], $rules, [
            'value.integer' => '筛选值不是合法的整数。',
            'value.*.integer' => '筛选值不是合法的整数。',
            'value.numeric' => '筛选值不是合法的数字。',
            'value.*.numeric' => '筛选值不是合法的数字。',
            'value.boolean' => '筛选值不是合法的布尔值。',
            'value.*.boolean' => '筛选值不是合法的布尔值。',
        ]);

        return $validator->errors()->all();
    }

    /**
     * @param  array<int|string, mixed>  $definition
     * @return array<int, mixed>
     */
    private function extractRules(array $definition): array
    {
        return collect($definition)
            ->filter(fn (mixed $value, int|string $key): bool => is_int($key))
            ->values()
            ->all();
    }

    /**
     * @param  array<int, mixed>  $rules
     * @return array<int, string>
     */
    private function inferOperators(array $rules): array
    {
        if ($this->hasRule($rules, 'boolean')) {
            // 布尔字段只保留等值判断，避免出现 gt/like 这类无意义条件。
            return ['eq'];
        }

        if ($this->hasRule($rules, 'integer') || $this->hasRule($rules, 'numeric')) {
            // 数值字段允许范围比较和 in，但不默认开放 like。
            return ['eq', 'ne', 'gt', 'gte', 'lt', 'lte', 'in'];
        }

        return self::DEFAULT_OPERATORS;
    }

    /**
     * @param  array<int, mixed>  $rules
     */
    private function normalizeValue(mixed $value, array $rules): mixed
    {
        if ($this->hasRule($rules, 'boolean')) {
            // Validator 负责判定合法性；这里只做查询前的值归一化。
            return in_array($value, [true, 1, '1'], true);
        }

        if ($this->hasRule($rules, 'integer')) {
            return (int) $value;
        }

        if ($this->hasRule($rules, 'numeric')) {
            return (float) $value;
        }

        return $value;
    }

    /**
     * @param  array<int, mixed>  $rules
     */
    private function hasRule(array $rules, string $expectedRule): bool
    {
        foreach ($rules as $rule) {
            // 这里只识别字符串规则，足够覆盖当前列表查询的轻量类型声明。
            if (is_string($rule) && str_starts_with($rule, $expectedRule)) {
                return true;
            }
        }

        return false;
    }

    private function applyOperator(Builder $query, string $field, string $operator, mixed $value): void
    {
        // 这里只处理已经通过字段/操作符校验的条件，非法分支会在前面被拦下。
        match ($operator) {
            'eq' => $query->where($field, '=', $value),
            'ne' => $query->where($field, '!=', $value),
            'gt' => $query->where($field, '>', $value),
            'gte' => $query->where($field, '>=', $value),
            'lt' => $query->where($field, '<', $value),
            'lte' => $query->where($field, '<=', $value),
            'like' => $query->where($field, 'like', '%'.$value.'%'),
            'in' => $query->whereIn($field, $value),
            default => null,
        };
    }
}
