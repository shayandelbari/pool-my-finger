/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./src/frontend/pages/**/*.php",
    "./src/frontend/components/**/*.php",
  ],
  darkMode: "class",
  theme: {
    extend: {
      colors: {
        // primary: "#2563eb",
        // secondary: "#f97316"
      },
    },
  },
  plugins: [],
};
