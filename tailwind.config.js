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
        // Add minHeight and maxHeight entries
        minHeight: {
          '96': '24rem',  // same as h-96
        },
        maxHeight: {
          '96': '24rem',  // same as h-96
        },
      },
    },
    variants: {
      extend: {},
    },
    plugins: [],
  };
  