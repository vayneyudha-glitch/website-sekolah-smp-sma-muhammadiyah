import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                'muhammadiyah-hijau': '#0F7A3E',
                'muhammadiyah-hijau-tua': '#0A5C2E',
                'muhammadiyah-hijau-muda': '#E8F5EC',
                'muhammadiyah-kuning': '#F2B705',
            },
        },
    },

    plugins: [forms],
};
