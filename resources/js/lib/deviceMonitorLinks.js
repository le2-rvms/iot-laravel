import { buildRouteQueryHref } from './routes.js';

export function buildDeviceMonitorOverviewHref(terminalId) {
    return buildRouteQueryHref('client-monitor.device-overview', {
        client_id__eq: terminalId,
    });
}
