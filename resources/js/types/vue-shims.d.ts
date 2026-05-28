declare module '*.vue' {
    import type { DefineComponent } from 'vue';
    const component: DefineComponent;
    export default component;
}

/**
 * Type declarations for leaflet.heat library.
 * This is a side-effect module that extends the Leaflet namespace.
 */
declare module 'leaflet.heat' {
    // Side-effect import that extends L with heatLayer functionality
}
