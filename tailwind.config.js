module.exports = {
    purge: [
        './**/*.php',
        './src/**/*.js',
    ],
    darkMode: false,
    theme: {
        extend: {
            colors: {
                'esper-yellow': '#FFC715',
            },
            screens: {
                'xxl': '1600px', // This adds a new breakpoint named xxl
            },
        },
    },
    variants: {
        extend: {},
    },
    plugins: [],
};
