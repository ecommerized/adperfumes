import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Instrument Sans', ...defaultTheme.fontFamily.sans],
                display: ['Cormorant Garamond', 'Georgia', ...defaultTheme.fontFamily.serif],
            },
            colors: {
                brand: {
                    primary: '#C9A96E',        // Luxury gold accent
                    'primary-soft': '#D4BC8B', // Soft gold hover
                    dark: '#0A0A0A',           // Obsidian black
                    charcoal: '#1A1A1A',       // Card bg on dark
                    ivory: '#FAFAF8',          // Page backgrounds
                    light: '#F5F4F0',          // Section alt / card bg
                    text: '#2D2D2D',           // Body text
                    gray: '#6B6B6B',           // Secondary text
                    muted: '#9A9A9A',          // Captions / meta
                    border: '#E8E6E1',         // Warm borders
                    divider: '#EDEBE6',        // Lighter dividers
                    hover: '#F0EEE9',          // Hover bg
                    sale: '#8B2020',           // Deep burgundy
                    success: '#2D5A3D',        // Forest green
                },
            },
            maxWidth: {
                '8xl': '1600px',
            },
            borderRadius: {
                'luxury': '0px',
            },
            letterSpacing: {
                'luxury': '0.08em',
                'editorial': '0.12em',
            },
            boxShadow: {
                'luxury': '0 1px 3px rgba(0,0,0,0.04)',
                'luxury-hover': '0 4px 12px rgba(0,0,0,0.06)',
            },
        },
    },
    plugins: [],
};
