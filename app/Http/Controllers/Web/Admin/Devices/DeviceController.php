<?php

namespace App\Http\Controllers\Web\Admin\Devices;

use App\Attributes\PermissionAction;
use App\Attributes\PermissionGroup;
use App\Http\Controllers\Web\Admin\Controller;
use App\Http\Requests\Devices\StoreDeviceRequest;
use App\Http\Requests\Devices\UpdateDeviceRequest;
use App\Models\Iot\IotDevice;
use App\Support\CsvExporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Response;
use LogicException;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[PermissionGroup]
class DeviceController extends Controller
{
    #[PermissionAction('read')]
    public function index(Request $request): Response
    {
        $query = IotDevice::indexQuery($request->query());
        $filters = $request->except('page');

        $devices = $query
            ->paginate(10)
            ->withQueryString();

        return $this->renderPage([
            'devices' => $devices,
            'filters' => $filters,
        ]);
    }

    #[PermissionAction('read')]
    public function export(Request $request): StreamedResponse
    {
        $query = IotDevice::indexQuery($request->query());

        return CsvExporter::download(
            query: $query,
            columns: [
                'models.iot_device.dev_id' => static fn (IotDevice $device): string => (string) ($device->dev_id ?? ''),
                'models.iot_device.terminal_id' => static fn (IotDevice $device): string => $device->terminal_id,
                'models.iot_device.dev_name' => static fn (IotDevice $device): string => $device->dev_name,
                'models.iot_device.company_id' => static fn (IotDevice $device): string => (string) ($device->company_id ?? ''),
                'models.iot_device.manufacturer_id' => static fn (IotDevice $device): string => (string) ($device->manufacturer_id ?? ''),
                'models.iot_device.product_key' => static fn (IotDevice $device): string => (string) ($device->product_key ?? ''),
                'models.iot_device.sim_number' => static fn (IotDevice $device): string => (string) ($device->sim_number ?? ''),
                'models.iot_device._vehicle_plate' => static fn (IotDevice $device): string => (string) ($device->_vehicle_plate ?? ''),
                'models.iot_device._vehicle_vin' => static fn (IotDevice $device): string => (string) ($device->_vehicle_vin ?? ''),
                'models.iot_device._bind_status' => static fn (IotDevice $device): string => (string) ($device->_bind_status ?? ''),
                'models.iot_device.device_status' => static fn (IotDevice $device): string => (string) ($device->device_status ?? ''),
                'models.iot_device.review_status' => static fn (IotDevice $device): string => (string) ($device->review_status ?? ''),
                'models.iot_device.city_relation_id' => static fn (IotDevice $device): string => (string) ($device->city_relation_id ?? ''),
                'models.iot_device.created_at' => static fn (IotDevice $device): string => $device->created_at?->format('Y-m-d H:i:s') ?? '',
            ],
            fileName: 'devices-'.now()->format('Ymd-His').'.csv',
        );
    }

    #[PermissionAction('write')]
    public function create(): Response
    {
        return $this->renderPage([
            'device' => new IotDevice,
        ]);
    }

    #[PermissionAction('write')]
    public function store(StoreDeviceRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $device = new IotDevice;
            $device->fill($request->validated());
            $device->save();
        });

        return to_route('devices.index')->with('success', '设备已创建。');
    }

    #[PermissionAction('write')]
    public function edit(IotDevice $device): Response
    {
        return $this->renderPage([
            'device' => $device->load('deviceProduct'),
        ]);
    }

    #[PermissionAction('write')]
    public function update(UpdateDeviceRequest $request, IotDevice $device): RedirectResponse
    {
        $device = DB::transaction(function () use ($request, $device): IotDevice {
            $attributes = $request->validated();
            unset($attributes['terminal_id']);

            $device->update($attributes);

            return $device->fresh('deviceProduct');
        });

        return to_route('devices.edit', $device)->with('success', '设备已更新。');
    }

    #[PermissionAction('write')]
    public function destroy(IotDevice $device): RedirectResponse
    {
        try {
            DB::transaction(function () use ($device): void {
                if ($device->gpsCommands()->exists()) {
                    throw new LogicException('该设备仍有关联指令记录，无法删除。');
                }

                $device->delete();
            });
        } catch (LogicException $exception) {
            return to_route('devices.index')->with('error', $exception->getMessage());
        }

        return to_route('devices.index')->with('success', '设备已删除。');
    }
}
