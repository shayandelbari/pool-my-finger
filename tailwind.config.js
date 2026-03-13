/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./src/pages/**/*.php",
    "./src/components/**/*.php"
  ],
  theme: {
    extend: {
      colors: {
        // primary: "#2563eb",
        // secondary: "#f97316"
      }
    }
  },
  plugins: []
}