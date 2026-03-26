<?php

namespace App\Http\Controllers\Web\Admin\Settings;

use App\Attributes\PermissionAction;
use App\Http\Controllers\Web\Admin\Controller;
use App\Http\Requests\Settings\StoreSettingRequest;
use App\Http\Requests\Settings\UpdateSettingRequest;
use App\Models\Settings\Config;
use App\Support\CsvExporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

abstract class AbstractSettingsConfigController extends Controller
{
    public function __construct(
        private readonly int $category,
        private readonly string $categoryLabel,
        private readonly string $indexRouteName,
    ) {}

    #[PermissionAction('read')]
    public function index(Request $request): Response
    {
        $query = Config::indexQuery($request->query())
            ->where('category', $this->category);
        $filters = $request->except('page');

        $configs = $query
            ->paginate(10)
            ->withQueryString();

        return $this->renderPage([
            'category' => $this->category,
            'filters' => $filters,
            'configs' => $configs,
        ]);
    }

    #[PermissionAction('read')]
    public function export(Request $request): StreamedResponse
    {
        $query = Config::indexQuery($request->query())
            ->where('category', $this->category);

        return CsvExporter::download(
            query: $query,
            columns: [
                'models.config.id' => static fn (Config $config): int => $config->id,
                'models.config.key' => static fn (Config $config): string => $config->key,
                'models.config.value_display' => static fn (Config $config): string => $config->value_display,
                'models.config.category_label' => static fn (Config $config): string => $config->category_label,
                'models.config.is_masked_label' => static fn (Config $config): string => $config->is_masked_label,
                'models.config.remark' => static fn (Config $config): string => $config->remark,
                'models.config.updated_at' => static fn (Config $config): string => $config->updated_at?->format('Y-m-d H:i:s') ?? '',
            ],
            fileName: 'settings-'.$this->category.'-configs-'.now()->format('Ymd-His').'.csv',
        );
    }

    #[PermissionAction('write')]
    public function create(): Response
    {
        return $this->renderPage([
            'category' => $this->category,
            // 新建页直接带入固定分类，避免前端再次判断应用配置/系统配置属于哪一路由。
            'config' => new Config([
                'category' => $this->category,
                'is_masked' => false,
            ]),
        ]);
    }

    #[PermissionAction('write')]
    public function store(StoreSettingRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['category'] = $this->category;

        Config::createConfig($validated);

        return to_route($this->indexRouteName)->with('success', "{$this->categoryLabel}已创建。");
    }

    #[PermissionAction('write')]
    public function edit(Config $config): Response
    {
        $this->ensureCategoryMatches($config);

        return $this->renderPage([
            'category' => $this->category,
            'config' => $config,
        ]);
    }

    #[PermissionAction('write')]
    public function update(UpdateSettingRequest $request, Config $config): RedirectResponse
    {
        $this->ensureCategoryMatches($config);

        $validated = $request->validated();
        $validated['category'] = $this->category;

        $config->updateConfig($validated);

        return to_route($this->indexRouteName)->with('success', "{$this->categoryLabel}已更新。");
    }

    #[PermissionAction('write')]
    public function destroy(Config $config): RedirectResponse
    {
        $this->ensureCategoryMatches($config);

        $config->deleteConfig();

        return to_route($this->indexRouteName)->with('success', "{$this->categoryLabel}已删除。");
    }

    protected function ensureCategoryMatches(Config $config): void
    {
        abort_unless((int) (string) $config->category === $this->category, 404);
    }
}
