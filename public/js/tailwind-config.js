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
                        DEFAULT: '#2563eb',
                        dark: '#1e40af',
                        light: '#3b82f6',
                    },
                    secondary: {
                        DEFAULT: '#64748b',
                        dark: '#475569',
                        light: '#94a3b8',
                    },
                    success: {
                        DEFAULT: '#10b981',
                        dark: '#059669',
                        light: '#34d399',
                    },
                    error: {
                        DEFAULT: '#ef4444',
                        dark: '#dc2626',
                        light: '#f87171',
                    },
                    warning: {
                        DEFAULT: '#f59e0b',
                        dark: '#d97706',
                        light: '#fbbf24',
                    },
                    info: {
                        DEFAULT: '#06b6d4',
                        dark: '#0891b2',
                        light: '#22d3ee',
                    },
                },
            },
        },
    };
}







