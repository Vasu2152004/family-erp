/**
 * Tailwind CSS Configuration for CDN fallback
 * This file configures Tailwind when using CDN instead of Vite build
 */

if (typeof tailwind !== 'undefined') {
    tailwind.config = {
        theme: {
            extend: {
                fontFamily: {
                    sans: ['Instrument Sans', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                },
                colors: {
                    primary: {
                        DEFAULT: '#4f46e5',
                        dark: '#3730a3',
                        light: '#a5b4fc',
                    },
                    secondary: {
                        DEFAULT: '#0ea5e9',
                        dark: '#0284c7',
                        light: '#7dd3fc',
                    },
                    success: {
                        DEFAULT: '#10b981',
                        dark: '#047857',
                        light: '#34d399',
                    },
                    error: {
                        DEFAULT: '#f87171',
                        dark: '#dc2626',
                        light: '#fecdd3',
                    },
                    warning: {
                        DEFAULT: '#f59e0b',
                        dark: '#d97706',
                        light: '#fcd34d',
                    },
                    info: {
                        DEFAULT: '#06b6d4',
                        dark: '#0ea5e9',
                        light: '#67e8f9',
                    },
                    surface: '#111827',
                    'surface-alt': '#0b1224',
                    border: '#1f2937',
                },
                boxShadow: {
                    soft: '0 10px 40px rgba(15, 23, 42, 0.4)',
                    card: '0 8px 24px rgba(15, 23, 42, 0.32)',
                },
                borderRadius: {
                    lg: '18px',
                    md: '14px',
                    sm: '10px',
                },
            },
        },
    };
}










