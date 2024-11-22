import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },
    safelist: [
        'bg-black',
        'bg-gray-950',
        'text-white',
        'bg-white',
        'text-black',
        'border-gray-700',
        'border-gray-800',
        'transform',
        'transition-transform',
        'duration-300',
        'translate-x-full',
        'translate-x-0',
        'bg-indigo-600',
        'hover:bg-indigo-500',
    ],


    plugins: [forms],
};
