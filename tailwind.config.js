module.exports = {
  content: [
    "./src/**/*.{html,js}",
    "./views/**/*.{html,js}",
    "./*.php",
    // Add any other paths to your templates here
  ],
  theme: {
    extend: {
      fontFamily: {
        sans: ['BYekan', 'sans-serif'], // Updated to use local BYekan font
      },
    },
  },
  plugins: [],
}
