const defaultTheme = require('tailwindcss/defaultTheme');
const forms = require('@tailwindcss/forms');

/** @type {import('tailwindcss').Config} */
module.exports = {
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
                    DEFAULT: "#f25270",
                    dark: "#f2527d",
                    light: "#f285a2",
                },
                neutral: {
                    black: "#172124",
                    gray: "#ca79a1ff",
                    soft: "#f2f3f1",
                    white: "#ffffff",
                },
                text: {
                    DEFAULT: "#172124",
                    light: "#666666",
                    white: "#ffffff",
                },
            },
        },
    },

    plugins: [forms],
};
