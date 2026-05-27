
/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
  './index.html',
  './src/**/*.{js,ts,jsx,tsx}'
],
  theme: {
    extend: {
      colors: {
        brand: {
          cream: '#FAFAF7',
          teal: {
            DEFAULT: '#0F4C5C',
            light: '#1B6B7A',
            dark: '#0A333E'
          },
          amber: {
            DEFAULT: '#D97706',
            light: '#F59E0B',
            dark: '#B45309'
          }
        }
      },
      fontFamily: {
        sans: ['Inter', 'sans-serif'],
      }
    },
  },
  plugins: [],
}
