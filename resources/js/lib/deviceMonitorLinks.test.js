import test from 'node:test';
import assert from 'node:assert/strict';
import { buildDeviceMonitorOverviewHref } from './deviceMonitorLinks.js';

test('device monitor overview href contains client_id__eq', () => {
    assert.equal(
        buildDeviceMonitorOverviewHref('terminal-001'),
        '/admin/client-monitor/device-overview?client_id__eq=terminal-001',
    );
});

test('device monitor overview href encodes terminal ids safely', () => {
    assert.equal(
        buildDeviceMonitorOverviewHref('terminal 001/alpha'),
        '/admin/client-monitor/device-overview?client_id__eq=terminal+001%2Falpha',
    );
});
