/// <reference types="vite/client" />

/**
 * Web-component & global augmentations used across the app.
 */

// `box-icon` is a Boxicons custom element used inside JSX.
declare module 'react' {
    namespace JSX {
        interface IntrinsicElements {
            'box-icon': {
                name?: string;
                type?: string;
                color?: string;
                size?: string;
                animation?: string;
                rotate?: number | string;
                flip?: string;
                border?: string;
                class?: string;
                className?: string;
                [key: string]: any;
            };
        }
    }
}

export {};