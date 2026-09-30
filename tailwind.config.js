import defaultTheme from "tailwindcss/defaultTheme";
import forms from "@tailwindcss/forms";

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
        "./storage/framework/views/*.php",
        "./resources/views/**/*.blade.php",
        "./node_modules/flowbite/**/*.js",
    ],

    theme: {
        extend: {
            // Two families: Inter for body, UI and numbers; Plus Jakarta Sans for headings, buttons and labels.
            // `sans` is Tailwind's default (the <body> font), so text without an explicit font class gets Inter too.
            fontFamily: {
                sans: ["Inter", ...defaultTheme.fontFamily.sans],
                main: ["Inter", ...defaultTheme.fontFamily.sans],
                secondary: ["Plus Jakarta Sans", ...defaultTheme.fontFamily.sans],
                tertiary: ["Plus Jakarta Sans", ...defaultTheme.fontFamily.sans],
            },
            colors: {
                primary: "#1E3A8A", // Deep blue for a strong, modern base
                secondary: "#14B8A6", // Teal for interactive and accent elements
                tertiary: "#6B7280", // Cool gray for secondary text and UI components
                paper: "#F7F3ED", // Warm off-white matching the logo artwork background (auth brand panel)
                accent: "#F59E0B", // Warm amber for CTAs and alerts
                neutral: {
                    50: "#F9FAFB",
                    100: "#F3F4F6",
                    200: "#E5E7EB",
                    300: "#D1D5DB",
                    400: "#9CA3AF",
                    500: "#6B7280",
                    600: "#4B5563",
                    700: "#374151",
                    800: "#1F2937",
                    900: "#111827",
                },
            },
            zIndex: {
                60: "60",
                70: "70",
                80: "80",
                90: "90",
                100: "100",
                auto: "auto",
            },
        },
    },

    plugins: [require("flowbite/plugin"), forms],
};
