import type { Config } from 'tailwindcss'

const config: Config = {
  content: [
    './src/pages/**/*.{js,ts,jsx,tsx,mdx}',
    './src/components/**/*.{js,ts,jsx,tsx,mdx}',
    './src/app/**/*.{js,ts,jsx,tsx,mdx}',
  ],
  darkMode: 'class',
  theme: {
    extend: {
      fontFamily: {
        sans: ['var(--font-sans)', 'Inter', 'system-ui', '-apple-system', 'sans-serif'],
        mono: ['var(--font-mono)', 'JetBrains Mono', 'ui-monospace', 'monospace'],
      },
      colors: {
        brand: {
          DEFAULT: '#D97706',
          light:   '#FEF3C7',
          dark:    '#B45309',
          muted:   '#FDE68A',
          50:      '#FFFBEB',
        },
        erp: {
          bg:      '#FFFCF5',
          sidebar: '#FFFFFF',
          'sidebar-active': '#FEF3C7',
          surface: '#FFFFFF',
          'surface-alt': '#F9FAFB',
          ink:     '#111827',
          'ink-secondary': '#6B7280',
          'ink-muted':     '#9CA3AF',
          inverse: '#FFFFFF',
          border:  '#F3F4F6',
          'border-mid': '#E5E7EB',
        },
        status: {
          success:      '#16A34A',
          'success-bg': '#DCFCE7',
          warning:      '#D97706',
          'warning-bg': '#FEF3C7',
          danger:       '#DC2626',
          'danger-bg':  '#FEE2E2',
          info:         '#2563EB',
          'info-bg':    '#DBEAFE',
          neutral:      '#6B7280',
          'neutral-bg': '#F3F4F6',
        },
      },
      borderRadius: {
        sm:   '4px',
        md:   '8px',
        lg:   '12px',
        xl:   '16px',
        '2xl': '20px',
        full: '9999px',
      },
      boxShadow: {
        xs: '0 1px 2px rgba(0,0,0,0.04)',
        sm: '0 2px 8px rgba(0,0,0,0.06)',
        md: '0 4px 16px rgba(0,0,0,0.08)',
        lg: '0 8px 24px rgba(0,0,0,0.10)',
        xl: '0 16px 40px rgba(0,0,0,0.12)',
      },
      keyframes: {
        'fade-up': {
          from: { opacity: '0', transform: 'translateY(8px)' },
          to:   { opacity: '1', transform: 'none' },
        },
        shimmer: {
          from: { backgroundPosition: '200% 0' },
          to:   { backgroundPosition: '-200% 0' },
        },
      },
      animation: {
        'fade-up': 'fade-up 0.28s ease both',
        shimmer: 'shimmer 1.4s infinite linear',
      },
    },
  },
  plugins: [],
}

export default config
