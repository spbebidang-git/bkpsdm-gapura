import forms from '@tailwindcss/forms';
import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.{js,jsx,ts,tsx}',
        './node_modules/flowbite/**/*.js',
    ],

    theme: {
        container: {
            center: true,
        },
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                primary: ['Poppins', ...defaultTheme.fontFamily.sans],
                secondary: ['Inter', ...defaultTheme.fontFamily.sans],
                poppins: ['Poppins', ...defaultTheme.fontFamily.sans],
                inter: ['Inter', ...defaultTheme.fontFamily.sans],
                poly: ['Poly', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                primary: {
                    DEFAULT: '#018083',
                    pressed: '#016668',
                    mist: '#e6f3f3',
                },
                secondary: {
                    DEFAULT: '#F68104',
                    soft: '#fff0e0',
                },
                success: {
                    DEFAULT: '#246A3C',
                    pressed: '#1B5230',
                    mist: '#EAF7EE',
                },
                warning: {
                    DEFAULT: '#B45309',
                    pressed: '#92400E',
                    mist: '#FFF4E8',
                },
                danger: {
                    DEFAULT: '#A43B2F',
                    pressed: '#862E24',
                    mist: '#FFF4F1',
                },
                canvas: '#ffffff',
                surface: {
                    DEFAULT: '#f6f5f4',
                    soft: '#fafaf9',
                },
                hairline: {
                    DEFAULT: '#e5e3df',
                    soft: '#ede9e4',
                    strong: '#c8c4be',
                },
                ink: {
                    DEFAULT: '#1a1a1a',
                    deep: '#000000',
                },
                charcoal: '#37352f',
                steel: '#787671',
                stone: '#a4a097',
                muted: '#bbb8b1',
                tint: {
                    mint: '#d9f3e1',
                    sky: '#dcecfa',
                    lavender: '#e6e0f5',
                    peach: '#ffe8d4',
                    rose: '#fde0ec',
                    yellow: '#fef7d6',
                    cream: '#f8f5e8',
                    gray: '#f0eeec',
                },
            },
            boxShadow: {
                panel: 'rgba(15, 15, 15, 0.04) 0px 1px 2px 0px',
            },
        },
    },

    plugins: [forms, require('flowbite/plugin'), require('@tailwindcss/typography')],
};
