const colors = require('tailwindcss/colors');

module.exports = {
  colors: {
    // Navy ramp built around the flat colour of Assets/images/background.png
    primary: {
      DEFAULT: '#091834',
      100: '#E4E8F1',
      200: '#C2CADD',
      300: '#94A1BF',
      400: '#5E6E96',
      500: '#3A4A73',
      600: '#263457',
      700: '#17233F',
      800: '#0F1A2F',
      900: '#091834',
    },
    main: '#F8D032', // yellow
    secondary: '#D51B5D', // magenta
    accent: '#3CA6D9', // blue
    danger: colors.rose,
    success: colors.green,
    warning: colors.yellow,
  },
};
