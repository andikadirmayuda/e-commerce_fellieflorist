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
                primary: {
                    DEFAULT: "#f25270", // Pink utama
                    dark: "#f2527d",    // Pink variasi (hover/active)
                    light: "#f285a2",   // Pink terang (accent/soft bg)
                },
                neutral: {
                    black: "#172124",   // Hitam elegan
                    soft: "#f2f3f1",    // Putih soft (background)
                    white: "#ffffff",   // Putih bersih
                },
                text: {
                    DEFAULT: "#172124", // Text utama (hitam)
                    light: "#666666",   // Text sekunder
                    white: "#ffffff",   // Text putih
                },
            },
        },
    },

    plugins: [forms],
};
