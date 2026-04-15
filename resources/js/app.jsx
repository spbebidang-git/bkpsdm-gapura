import { initFlowbite } from 'flowbite';
import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/react';
import 'boxicons';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot, hydrateRoot } from 'react-dom/client';
import { Bounce, ToastContainer, Zoom } from 'react-toastify';
import 'react-toastify/dist/ReactToastify.css';
import { route } from '../../vendor/tightenco/ziggy';
import FlashMessageToast from './Components/FlashMessageToast';

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

                {/* Global Flash Message Handler */}
                <FlashMessageToast />

                {/* Global Toast Container */}
                <ToastContainer
                    position="top-right"
                    autoClose={3000}
                    hideProgressBar={false}
                    newestOnTop
                    closeOnClick
                    rtl={false}
                    pauseOnFocusLoss
                    draggable
                    pauseOnHover
                    theme="colored"
                    transition={Bounce}
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
