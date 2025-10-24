/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{js,ts,jsx,tsx}'],
  theme: {
    extend: {
      colors: {
        primary: {
          50: '#E8EBF7',   // Lightest tint of Royal Blue
          100: '#D1D7EF',  // Very light Royal Blue
          200: '#A3AFE0',  // Light Royal Blue
          300: '#7587D0',  // Medium-light Royal Blue
          400: '#475FC1',  // Medium Royal Blue
          500: '#1E2A70',  // Royal Blue (main brand color)
          600: '#1A2560',  // Darker Royal Blue
          700: '#151E50',  // Deep Royal Blue
          800: '#121B4A',  // Midnight Blue
          900: '#0D1433',  // Dark Navy
        },
        accent: {
          gold: '#F4C22D',     // Gold Yellow (main accent)
          yellow: '#F4C22D',   // Alias for gold
          cream: '#FDF3D1',    // Light Cream
          sky: '#3A75C4',      // Sky Blue
          red: '#ef4444',      // Keep existing red for errors
          black: '#1f2937',    // Keep existing black
        },
      },
      fontFamily: {
        sans: ['Inter', 'system-ui', 'sans-serif'],
        serif: ['Lora', 'Georgia', 'serif'],
        heading: ['Montserrat', 'sans-serif'],
      },
      boxShadow: {
        card: '0 2px 8px rgba(0, 0, 0, 0.1)',
        hover: '0 4px 16px rgba(0, 0, 0, 0.15)',
      },
    },
  },
  plugins: [],
};
