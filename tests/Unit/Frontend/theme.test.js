import test from 'node:test';
import assert from 'node:assert/strict';

import {
    DEFAULT_THEME,
    THEME_STORAGE_KEYS,
    applyThemePreference,
    readStoredTheme,
    resolveThemePreference,
    setThemePreference,
} from '../../../resources/js/theme.js';

function createStorage(initial = {}) {
    const store = new Map(Object.entries(initial));

    return {
        getItem(key) {
            return store.has(key) ? store.get(key) : null;
        },
        setItem(key, value) {
            store.set(key, String(value));
        },
    };
}

function createRoot() {
    const classes = new Set();

    return {
        dataset: {},
        classList: {
            toggle(className, force) {
                if (force) {
                    classes.add(className);
                } else {
                    classes.delete(className);
                }
            },
            contains(className) {
                return classes.has(className);
            },
        },
    };
}

test('resolveThemePreference falls back to defaults for invalid values', () => {
    assert.deepEqual(resolveThemePreference({ name: 'invalid', mode: 'invalid' }), DEFAULT_THEME);
});

test('readStoredTheme returns stored theme when values are valid', () => {
    const storage = createStorage({
        [THEME_STORAGE_KEYS.name]: 'warm',
        [THEME_STORAGE_KEYS.mode]: 'dark',
    });

    assert.deepEqual(readStoredTheme(storage), {
        name: 'warm',
        mode: 'dark',
    });
});

test('applyThemePreference writes dataset and dark class', () => {
    const root = createRoot();

    applyThemePreference({ name: 'cool', mode: 'dark' }, root);

    assert.equal(root.dataset.theme, 'cool');
    assert.equal(root.classList.contains('dark'), true);
});

test('setThemePreference persists theme and updates the root state', () => {
    const storage = createStorage();
    const root = createRoot();

    const theme = setThemePreference({ name: 'cool', mode: 'dark' }, {
        root,
        storage,
    });

    assert.deepEqual(theme, { name: 'cool', mode: 'dark' });
    assert.equal(storage.getItem(THEME_STORAGE_KEYS.name), 'cool');
    assert.equal(storage.getItem(THEME_STORAGE_KEYS.mode), 'dark');
    assert.equal(root.dataset.theme, 'cool');
    assert.equal(root.classList.contains('dark'), true);
});
