/**
 * Global runtime helpers injected by the app bootstrap.
 *
 * `route()` is registered on `globalThis` in `app.tsx` (CSR) and `ssr.tsx` (SSR)
 * from the Ziggy route definition, so it is available everywhere without an import.
 */
export {};

type RouteHelper = {
    (name: string, params?: Record<string, unknown> | string | number | unknown[], absolute?: boolean, config?: Record<string, unknown>): string;
    (): { current: (name?: string) => string };
};

declare global {
    // `var` (not `const`) so the bootstrap can assign `globalThis.route = ...`.
    var route: RouteHelper;

    /** Axios instance attached to `window` by `bootstrap.js` (used bare in some pages). */
    var axios: import('axios').AxiosStatic;

    interface Window {
        axios: import('axios').AxiosStatic;
        Ziggy: Record<string, unknown>;
        route: RouteHelper;
    }
}
