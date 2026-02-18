import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import filamentPreset from './vendor/filament/filament/tailwind.config.preset.js';

/** @type {import('tailwindcss').Config} */
export default {
    presets: [filamentPreset],
    darkMode: 'class', // Habilita dark mode via classe no HTML
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/filament/**/*.blade.php',
        './vendor/filament/**/*.php',
        './storage/framework/views/*.php',
        './app/Filament/**/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                wedding: {
                    50: '#fdf8f6',
                    100: '#f2e8e5',
                    200: '#eaddd7',
                    300: '#e0cec7',
                    400: '#d2bab0',
                    500: '#bfa094',
                    600: '#a18072',
                    700: '#977669',
                    800: '#846358',
                    900: '#43302b',
                },
            },
        },
    },

    plugins: [forms],
};
