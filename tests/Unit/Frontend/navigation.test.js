import test from 'node:test';
import assert from 'node:assert/strict';
import {
    canAccessNavigationItem,
    resolveNavigationSections,
} from '../../../resources/js/lib/navigation.js';

test('sidebar only keeps items explicitly allowed by access map', () => {
    const sections = resolveNavigationSections({
        'dashboard.read': true,
        'user.read': false,
        'role.read': false,
        'settings.read': false,
    });

    assert.equal(sections.length, 1);
    assert.equal(sections[0].title, '工作台');
    assert.deepEqual(
        sections[0].items.map((item) => item.href),
        ['/dashboard'],
    );
});

test('sidebar hides every protected item when access map is empty', () => {
    assert.deepEqual(resolveNavigationSections({}), []);
});

test('navigation items without permission remain visible', () => {
    assert.equal(
        canAccessNavigationItem({
            title: '公开入口',
            href: '/public',
        }),
        true,
    );
});

test('sidebar removes sections whose items are all filtered out', () => {
    const sections = resolveNavigationSections({
        'dashboard.read': false,
        'user.read': false,
        'role.read': false,
        'settings.read': true,
    });

    assert.equal(sections.length, 1);
    assert.equal(sections[0].title, '系统管理');
    assert.deepEqual(
        sections[0].items.map((item) => item.href),
        ['/settings'],
    );
});
