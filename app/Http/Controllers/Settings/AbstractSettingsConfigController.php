<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreSettingRequest;
use App\Http\Requests\Settings\UpdateSettingRequest;
use App\Models\Settings\Config;
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
        $search = trim((string) $request->string('search'));

        $configs = Config::query()
            ->where('category', $this->category)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nestedQuery) use ($search) {
                    $nestedQuery
                        ->where('key', 'like', "%{$search}%")
                        ->orWhere('remark', 'like', "%{$search}%");
                });
            })
            ->orderBy('key')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Settings/Configs/Index', [
            'category' => $this->category,
            'filters' => ['search' => $search],
            'configs' => $configs,
        ]);
    }

    protected function createConfig(): Response
    {
        return Inertia::render('Settings/Configs/Create', [
            'category' => $this->category,
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
