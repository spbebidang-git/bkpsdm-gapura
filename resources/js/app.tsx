import { initFlowbite } from 'flowbite';
import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/react';
import 'boxicons';
import 'boxicons/css/boxicons.css';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

// Self-host SVG — default //unpkg.com becomes http:// on local HTTP pages and CSP blocks it
const BoxIcon = customElements.get('box-icon');
if (BoxIcon) {
    Object.defineProperty(BoxIcon, 'cdnUrl', {
        configurable: true,
        get: () => `${window.location.origin}/vendor/boxicons/svg`,
    });
}
import { createRoot, hydrateRoot } from 'react-dom/client';
import { ToastContainer } from 'react-toastify';
import 'react-toastify/dist/ReactToastify.css';
import '../css/gapura-toast.css';
import { route } from '../../vendor/tightenco/ziggy';
import FlashMessageToast, { GapuraToastTransition } from './Components/FlashMessageToast';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(`./Pages/${name}.tsx`, import.meta.glob('./Pages/**/*.tsx')),
    setup({ el, App, props }) {
        const ziggy: any = props.initialPage.props.ziggy;

        // Always resolve against live window URL — frozen initial ziggy.location
        // breaks route().current() after Inertia client navigations.
        globalThis.route = ((name, params, absolute, config) =>
            route(name, params, absolute, {
                ...ziggy,
                ...config,
                location:
                    typeof window !== 'undefined'
                        ? window.location
                        : new URL(ziggy.location),
            })) as unknown as typeof globalThis.route;

        const app = (
            <>
                {/* Render Inertia App */}
                <App {...props} />

                {/* Global Flash Message Handler */}
                <FlashMessageToast initialFlash={props.initialPage.props.flash ?? {}} initialComponent={props.initialPage.component} />

                {/* Global Toast Container */}
                <ToastContainer
                    containerId="course-activation-success"
                    className="course-activation-success-container"
                    position="top-center"
                    limit={1}
                    pauseOnFocusLoss
                    pauseOnHover
                    draggable={false}
                    transition={GapuraToastTransition}
                    theme="light"
                    style={{ zIndex: 9999 }}
                />
                <ToastContainer
                    position="top-center"
                    autoClose={3000}
                    hideProgressBar={false}
                    newestOnTop
                    closeOnClick
                    rtl={false}
                    pauseOnFocusLoss
                    draggable
                    pauseOnHover
                    transition={GapuraToastTransition}
                    theme="light"
                    toastClassName="gapura-toast"
                    style={{ zIndex: 9999 }}
                />
            </>
        );

        if (import.meta.env.SSR) {
            hydrateRoot(el, app);
        } else {
            createRoot(el).render(app);
        }
    },
    progress: {
        color: '#4B5563',
    },
}).then(() => {
    initFlowbite();
});
