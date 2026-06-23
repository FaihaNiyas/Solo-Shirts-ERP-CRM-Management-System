import { clsx, type ClassValue } from 'clsx'
import { twMerge } from 'tailwind-merge'

export function cn(...inputs: ClassValue[]): string {
  return twMerge(clsx(inputs))
}

// Indian currency formatting: ₹1,42,500
export function formatINR(amount: number | string): string {
  const n = typeof amount === 'string' ? parseFloat(amount) : amount
  if (isNaN(n)) return '₹0'
  return '₹' + n.toLocaleString('en-IN', { maximumFractionDigits: 2 })
}

// Format a request_id for display (first 8 chars)
export function shortRequestId(id: string): string {
  return id ? id.slice(0, 8) + '…' : ''
}
