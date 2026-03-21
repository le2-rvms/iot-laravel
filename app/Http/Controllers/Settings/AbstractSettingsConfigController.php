<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreSettingRequest;
use App\Http\Requests\Settings\UpdateSettingRequest;
use App\Models\Settings\Config;
use App\Support\ListQueryFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

abstract class AbstractSettingsConfigController extends Controller
{
    public function __construct(
        private readonly string $category,
        private readonly string $categoryLabel,
        private readonly string $indexRouteName,
    ) {}

    protected function indexConfigs(Request $request): Response
    {
        $query = Config::query()
            ->where('category', $this->category)
            ->orderBy('key');

        $filters = (new ListQueryFilters(
            request: $request,
            fieldDefinitions: [
                // 配置列表只开放展示层需要的少数字段给查询 DSL。
                'key',
                'remark',
                'is_masked' => ['boolean'],
            ],
            callbacks: [
                // search__func 承接配置列表的关键字搜索，不把 category 这类固定约束暴露到 URL。
                'search' => function (Builder $query, mixed $value): void {
                    $search = trim((string) $value);
                    $likeSearch = "%{$search}%";

                    $query->where(function (Builder $nestedQuery) use ($likeSearch): void {
                        // 与 MQTT 列表保持一致，配置搜索也按大小写不敏感处理，避免系统内同类列表行为分裂。
                        $nestedQuery
                            ->whereRaw('LOWER(key) LIKE LOWER(?)', [$likeSearch])
                            ->orWhereRaw('LOWER(remark) LIKE LOWER(?)', [$likeSearch]);
                    });
                },
            ],
        ))->apply($query);

        $configs = $query
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Settings/Configs/Index', [
            'category' => $this->category,
            'filters' => $filters,
            'configs' => $configs,
        ]);
    }

    protected function createConfig(): Response
    {
        return Inertia::render('Settings/Configs/Create', [
            'category' => $this->category,
            // 新建页直接带入固定分类，避免前端再次判断应用配置/系统配置属于哪一路由。
            'config' => new Config([
                'category' => $this->category,
                'is_masked' => false,
            ]),
        ]);
    }

    protected function storeConfig(StoreSettingRequest $request): RedirectResponse
    {
        $validated = [
            ...$request->validated(),
            'category' => $this->category,
        ];

        Config::query()->create($validated);

        return to_route($this->indexRouteName)->with('success', "{$this->categoryLabel}已创建。");
    }

    protected function editConfig(Config $config): Response
    {
        $this->ensureCategoryMatches($config);

        return Inertia::render('Settings/Configs/Edit', [
            'category' => $this->category,
            'config' => $config,
        ]);
    }

    protected function updateConfig(UpdateSettingRequest $request, Config $config): RedirectResponse
    {
        $this->ensureCategoryMatches($config);

        $validated = [
            ...$request->validated(),
            'category' => $this->category,
        ];

        $config->update($validated);

        return to_route($this->indexRouteName)->with('success', "{$this->categoryLabel}已更新。");
    }

    protected function destroyConfig(Config $config): RedirectResponse
    {
        $this->ensureCategoryMatches($config);

        $config->delete();

        return to_route($this->indexRouteName)->with('success', "{$this->categoryLabel}已删除。");
    }

    protected function ensureCategoryMatches(Config $config): void
    {
        abort_unless((string) $config->category === $this->category, 404);
    }
}
