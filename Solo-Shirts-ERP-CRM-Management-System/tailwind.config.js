import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
    ],
    darkMode: 'class',
    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                mono: ['JetBrains Mono', ...defaultTheme.fontFamily.mono],
            },
            colors: {
                brand: {
                    DEFAULT: '#D97706',
                    light:   '#FEF3C7',
                    dark:    '#B45309',
                    muted:   '#FDE68A',
                    50:      '#FFFBEB',
                },
                surface: {
                    DEFAULT: '#FFFFFF',
                    alt:     '#F9FAFB',
                    bg:      '#FFFCF5',
                    sidebar: '#FFFFFF',
                },
                'sidebar-active': '#FEF3C7',
                'border-mid':     '#E5E7EB',
                status: {
                    success: '#16A34A',
                    'success-bg': '#DCFCE7',
                    warning: '#D97706',
                    'warning-bg': '#FEF3C7',
                    danger:  '#DC2626',
                    'danger-bg': '#FEE2E2',
                    info:    '#2563EB',
                    'info-bg': '#DBEAFE',
                    neutral: '#6B7280',
                    'neutral-bg': '#F3F4F6',
                },
                dark: {
                    bg:          '#0C0A09',
                    sidebar:     '#1C1917',
                    surface:     '#1C1917',
                    'surface-alt': '#292524',
                    border:      '#3F3F46',
                    'text-pri':  '#FAFAF9',
                    'text-sec':  '#A8A29E',
                    'brand-bg':  '#451A03',
                },
            },
            borderRadius: {
                sm:   '4px',
                md:   '8px',
                lg:   '12px',
                xl:   '16px',
                '2xl': '20px',
                '3xl': '28px',
                full: '9999px',
            },
            boxShadow: {
                xs: '0 1px 2px rgba(0,0,0,0.04)',
                sm: '0 2px 8px rgba(0,0,0,0.06)',
                md: '0 4px 16px rgba(0,0,0,0.08)',
                lg: '0 8px 24px rgba(0,0,0,0.10)',
                xl: '0 16px 40px rgba(0,0,0,0.12)',
            },
            fontSize: {
                '2xs': ['10px', { lineHeight: '1.4' }],
                xs:    ['12px', { lineHeight: '1.4' }],
                sm:    ['13px', { lineHeight: '1.4' }],
                base:  ['14px', { lineHeight: '1.5' }],
                md:    ['16px', { lineHeight: '1.6' }],
                lg:    ['20px', { lineHeight: '1.4' }],
                xl:    ['24px', { lineHeight: '1.3' }],
                '2xl': ['40px', { lineHeight: '1.05' }],
            },
            letterSpacing: {
                tight:  '-0.02em',
                tighter: '-0.025em',
            },
        },
    },
    plugins: [],
};
