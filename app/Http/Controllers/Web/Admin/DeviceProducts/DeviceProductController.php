<?php

namespace App\Http\Controllers\Web\Admin\DeviceProducts;

use App\Attributes\PermissionAction;
use App\Attributes\PermissionGroup;
use App\Http\Controllers\Web\Admin\Controller;
use App\Http\Requests\DeviceProducts\StoreDeviceProductRequest;
use App\Http\Requests\DeviceProducts\UpdateDeviceProductRequest;
use App\Models\Iot\IotDeviceProduct;
use App\Support\CsvExporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Response;
use LogicException;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[PermissionGroup]
class DeviceProductController extends Controller
{
    #[PermissionAction('read')]
    public function index(Request $request): Response
    {
        $query = IotDeviceProduct::indexQuery($request->query());
        $filters = $request->except('page');

        $products = $query
            ->paginate(10)
            ->withQueryString();

        return $this->renderPage([
            'filters' => $filters,
            'products' => $products,
        ]);
    }

    #[PermissionAction('read')]
    public function export(Request $request): StreamedResponse
    {
        $query = IotDeviceProduct::indexQuery($request->query());

        return CsvExporter::download(
            query: $query,
            columns: [
                'models.iot_device_product.product_id' => static fn (IotDeviceProduct $product): int => $product->product_id,
                'models.iot_device_product.product_key' => static fn (IotDeviceProduct $product): string => $product->product_key,
                'models.iot_device_product.product_name' => static fn (IotDeviceProduct $product): string => $product->product_name,
                'models.iot_device_product.manufacturer' => static fn (IotDeviceProduct $product): string => (string) ($product->manufacturer ?? ''),
                'models.iot_device_product.protocol' => static fn (IotDeviceProduct $product): string => (string) ($product->protocol ?? ''),
                'models.iot_device_product.category' => static fn (IotDeviceProduct $product): string => (string) ($product->category ?? ''),
                'models.iot_device_product.devices_count' => static fn (IotDeviceProduct $product): int => (int) $product->devices_count,
                'models.iot_device_product.groups_count' => static fn (IotDeviceProduct $product): int => (int) $product->groups_count,
                'models.iot_device_product.created_at' => static fn (IotDeviceProduct $product): string => $product->created_at?->format('Y-m-d H:i:s') ?? '',
            ],
            fileName: 'device-products-'.now()->format('Ymd-His').'.csv',
        );
    }

    #[PermissionAction('write')]
    public function create(): Response
    {
        return $this->renderPage([
            'product' => new IotDeviceProduct,
        ]);
    }

    #[PermissionAction('write')]
    public function store(StoreDeviceProductRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $product = new IotDeviceProduct;
            $product->fill($request->validated());
            $product->save();
        });

        return to_route('device-products.index')->with('success', '设备产品已创建。');
    }

    #[PermissionAction('write')]
    public function edit(IotDeviceProduct $deviceProduct): Response
    {
        return $this->renderPage([
            'product' => $deviceProduct,
        ]);
    }

    #[PermissionAction('write')]
    public function update(UpdateDeviceProductRequest $request, IotDeviceProduct $deviceProduct): RedirectResponse
    {
        $deviceProduct = DB::transaction(function () use ($request, $deviceProduct): IotDeviceProduct {
            $attributes = $request->validated();
            unset($attributes['product_key']);

            $deviceProduct->update($attributes);

            return $deviceProduct->fresh();
        });

        return to_route('device-products.edit', $deviceProduct)->with('success', '设备产品已更新。');
    }

    #[PermissionAction('write')]
    public function destroy(IotDeviceProduct $deviceProduct): RedirectResponse
    {
        try {
            DB::transaction(function () use ($deviceProduct): void {
                if ($deviceProduct->devices()->exists()) {
                    throw new LogicException('该设备产品仍有关联设备，无法删除。');
                }

                if ($deviceProduct->groups()->exists()) {
                    throw new LogicException('该设备产品仍有关联分组，无法删除。');
                }

                $deviceProduct->delete();
            });
        } catch (LogicException $exception) {
            return to_route('device-products.index')->with('error', $exception->getMessage());
        }

        return to_route('device-products.index')->with('success', '设备产品已删除。');
    }
}
