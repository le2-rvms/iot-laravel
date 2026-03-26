import test from 'node:test';
import assert from 'node:assert/strict';
import { buildDeviceMonitorOverviewHref } from './deviceMonitorLinks.js';
import { route, setZiggyConfig } from './routes.js';

setZiggyConfig({
    url: 'https://example.test',
    port: null,
    defaults: {},
    location: 'https://example.test/monitor-overview',
    routes: {
        'client-monitor.device-overview': {
            uri: 'monitor-overview',
            methods: ['GET', 'HEAD'],
        },
    },
});

test('device monitor overview href contains client_id__eq', () => {
    assert.equal(
        buildDeviceMonitorOverviewHref('terminal-001'),
        route('client-monitor.device-overview', { client_id__eq: 'terminal-001' }, false),
    );
});

test('device monitor overview href encodes terminal ids safely', () => {
    assert.equal(
        buildDeviceMonitorOverviewHref('terminal 001/alpha'),
        route('client-monitor.device-overview', { client_id__eq: 'terminal 001/alpha' }, false),
    );
});
