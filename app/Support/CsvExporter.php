<?php

namespace App\Support;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Lang;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvExporter
{
    /**
     * @param  array<string, Closure>  $columns
     */
    public static function download(Builder $query, array $columns, string $fileName): StreamedResponse
    {
        return response()->streamDownload(function () use ($query, $columns): void {
            $handle = fopen('php://output', 'wb');

            if ($handle === false) {
                abort(500, '无法创建导出文件。');
            }

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, array_map(
                static fn (string $labelKey): string => self::resolveLabel($labelKey),
                array_keys($columns),
            ));

            foreach ($query->lazy(200) as $model) {
                $row = [];

                foreach ($columns as $resolver) {
                    $row[] = self::normalizeValue($resolver($model));
                }

                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private static function resolveLabel(string $labelKey): string
    {
        return Lang::has($labelKey)
            ? Lang::get($labelKey)
            : $labelKey;
    }

    private static function normalizeValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        if ($value instanceof \Stringable) {
            return (string) $value;
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
    }
}
