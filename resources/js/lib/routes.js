import { route as ziggyRoute } from 'ziggy-js';

function normalizeRoutePayload(payload) {
    return payload && typeof payload === 'object' && !Array.isArray(payload)
        ? payload
        : {};
}

function normalizeZiggyConfig(config) {
    if (!config) {
        return null;
    }

    const location = config.location instanceof URL
        ? config.location
        : new URL(config.location);

    return {
        ...config,
        location,
    };
}

export function setZiggyConfig(config) {
    globalThis.Ziggy = normalizeZiggyConfig(config);

    return globalThis.Ziggy;
}

export function updateZiggyLocation(location) {
    if (!globalThis.Ziggy) {
        return null;
    }

    const baseUrl = globalThis.Ziggy.url ?? window.location.origin;

    globalThis.Ziggy = {
        ...globalThis.Ziggy,
        location: location instanceof URL ? location : new URL(location, baseUrl),
    };

    return globalThis.Ziggy;
}

export function route(name, params = {}, absolute = false) {
    return ziggyRoute(name, params, absolute, globalThis.Ziggy);
}

export function buildRouteHref(name, params = {}, query = {}) {
    const hasQuery = Object.keys(query ?? {}).length > 0;

    return route(
        name,
        hasQuery ? { ...params, _query: query } : params,
        false,
    );
}

export function buildRouteQueryHref(name, query = {}) {
    return route(name, query, false);
}

export function hrefForRouteTarget(target) {
    if (!target?.routeName) {
        return '';
    }

    return buildRouteHref(
        target.routeName,
        normalizeRoutePayload(target.routeParams),
        normalizeRoutePayload(target.routeQuery),
    );
}
