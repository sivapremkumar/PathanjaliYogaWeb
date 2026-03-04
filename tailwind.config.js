/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        "./src/**/*.{html,ts}",
    ],
    theme: {
        extend: {
            colors: {
                primary: "#2C5F2D",
                secondary: "#97BC62",
                accent: "#FFE77A",
                background: "#F9FBF9",
                text: "#1A1A1A",
            },
            fontFamily: {
                sans: ['Inter', 'Roboto', 'system-ui', 'sans-serif'],
            }
        },
    },
    plugins: [],
}
