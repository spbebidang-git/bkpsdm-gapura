import { initFlowbite } from 'flowbite';
import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/react';
import 'boxicons';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot, hydrateRoot } from 'react-dom/client';
import { ToastContainer } from 'react-toastify';
import 'react-toastify/dist/ReactToastify.css';
import { route } from '../../vendor/tightenco/ziggy';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(`./Pages/${name}.jsx`, import.meta.glob('./Pages/**/*.jsx')),
    setup({ el, App, props }) {
        const ziggy = props.initialPage.props.ziggy;

        globalThis.route = (name, params, absolute) =>
            route(name, params, absolute, {
                ...ziggy,
                location: new URL(ziggy.location),
            });

        const app = (
            <>
                {/* Render Inertia App */}
                <App {...props} />

                {/* Global Toast Container */}
                <ToastContainer
                    position="top-center"
                    autoClose={3000}
                    hideProgressBar={false}
                    newestOnTop={false}
                    closeOnClick
                    rtl={false}
                    pauseOnFocusLoss
                    draggable
                    pauseOnHover
                    theme="light"
                    transition:Zoom
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
