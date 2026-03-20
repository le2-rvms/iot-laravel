export const THEME_STORAGE_KEYS = Object.freeze({
    name: 'theme.name',
    mode: 'theme.mode',
});

export const THEME_NAMES = Object.freeze(['neutral', 'cool', 'warm']);
export const THEME_MODES = Object.freeze(['light', 'dark']);

export const THEME_LABELS = Object.freeze({
    neutral: '中性',
    cool: '蓝绿',
    warm: '暖砂',
});

export const MODE_LABELS = Object.freeze({
    light: '浅色',
    dark: '深色',
});

export const DEFAULT_THEME = Object.freeze({
    name: 'neutral',
    mode: 'light',
});

let currentTheme = { ...DEFAULT_THEME };

function resolveStorage(storage) {
    if (storage) {
        return storage;
    }

    try {
        return window.localStorage;
    } catch {
        return null;
    }
}

function toggleClass(target, className, force) {
    if (!target?.classList) {
        return;
    }

    if (typeof target.classList.toggle === 'function') {
        target.classList.toggle(className, force);

        return;
    }

    if (force) {
        target.classList.add(className);
    } else {
        target.classList.remove(className);
    }
}

export function normalizeThemeName(name) {
    return THEME_NAMES.includes(name) ? name : DEFAULT_THEME.name;
}

export function normalizeThemeMode(mode) {
    return THEME_MODES.includes(mode) ? mode : DEFAULT_THEME.mode;
}

export function resolveThemePreference(theme = {}) {
    return {
        name: normalizeThemeName(theme.name),
        mode: normalizeThemeMode(theme.mode),
    };
}

export function readStoredTheme(storage) {
    const resolvedStorage = resolveStorage(storage);

    if (!resolvedStorage) {
        return { ...DEFAULT_THEME };
    }

    return resolveThemePreference({
        name: resolvedStorage.getItem(THEME_STORAGE_KEYS.name),
        mode: resolvedStorage.getItem(THEME_STORAGE_KEYS.mode),
    });
}

export function persistThemePreference(theme, storage) {
    const resolvedTheme = resolveThemePreference(theme);
    const resolvedStorage = resolveStorage(storage);

    if (!resolvedStorage) {
        return resolvedTheme;
    }

    resolvedStorage.setItem(THEME_STORAGE_KEYS.name, resolvedTheme.name);
    resolvedStorage.setItem(THEME_STORAGE_KEYS.mode, resolvedTheme.mode);

    return resolvedTheme;
}

export function applyThemePreference(theme, root = document.documentElement) {
    const resolvedTheme = resolveThemePreference(theme);

    if (!root) {
        return resolvedTheme;
    }

    root.dataset.theme = resolvedTheme.name;
    toggleClass(root, 'dark', resolvedTheme.mode === 'dark');

    return resolvedTheme;
}

export function initializeTheme({ root = document.documentElement, storage } = {}) {
    currentTheme = readStoredTheme(storage);

    applyThemePreference(currentTheme, root);

    return { ...currentTheme };
}

export function getCurrentTheme() {
    return { ...currentTheme };
}

export function setThemePreference(nextTheme, { root = document.documentElement, storage } = {}) {
    currentTheme = resolveThemePreference({
        ...currentTheme,
        ...nextTheme,
    });

    applyThemePreference(currentTheme, root);
    persistThemePreference(currentTheme, storage);

    return { ...currentTheme };
}
