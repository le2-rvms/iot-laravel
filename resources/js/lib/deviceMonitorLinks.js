import { buildQueryHref } from './utils.js';

export function buildDeviceMonitorOverviewHref(terminalId) {
    return buildQueryHref('/admin/client-monitor/device-overview', {
        client_id__eq: terminalId,
    });
}
