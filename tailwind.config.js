import 'dotenv/config';

const projectPath = process.env.VITE_PROJECT_PATH ?? 'EF30';
const theme = require('./resources/js/Projects/' + projectPath + '/theme.js');

/** @type {import('tailwindcss').Config} */
export default {
  mode: 'jit',
  content: [
    './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
    './storage/framework/views/*.php',
    './resources/views/**/*.blade.php',
    './resources/js/**/*.vue',
  ],
  safelist: [{ pattern: /^grid-cols-[1-8]$/ }, { pattern: /^text-(?:[3-9]|1[0-4])xl$/ }],
  theme: {
    extend: {
      ...theme,
      fontSize: {
        '10xl': '10rem',
        '11xl': '11rem',
        '12xl': '12rem',
        '13xl': '13rem',
        '14xl': '14rem',
      },
    },
  },
};
