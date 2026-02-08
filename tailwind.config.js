/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./index.html",
    "./assets/js/**/*.js",
  ],
  theme: {
    extend: {
      colors: {
        primary: {
          DEFAULT: '#4f46e5',
          hover: '#4338ca',
          light: '#e0e7ff',
        },
        secondary: {
          DEFAULT: '#9333ea',
          light: '#f3e8ff',
        },
      },
    },
  },
  plugins: [],
}
