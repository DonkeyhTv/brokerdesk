import { intlLocale, locale } from '@/i18n'

const formatters = new Map<string, Intl.NumberFormat>()

function currency(wholeEuros: boolean): Intl.NumberFormat {
  const key = `${intlLocale()}:${wholeEuros}`
  let formatter = formatters.get(key)

  if (!formatter) {
    formatter = new Intl.NumberFormat(intlLocale(), {
      style: 'currency',
      currency: 'EUR',
      ...(wholeEuros ? { maximumFractionDigits: 0 } : {}),
    })
    formatters.set(key, formatter)
  }

  return formatter
}

export function formatMoney(cents: number | null | undefined): string {
  return cents === null || cents === undefined ? '—' : currency(false).format(cents / 100)
}

export function formatWholeMoney(cents: number): string {
  return currency(true).format(cents / 100)
}

/** Dates arrive as YYYY-MM-DD: they are read without any time zone, to never shift a day. */
export function formatDate(value: string | null | undefined): string {
  if (!value) {
    return '—'
  }

  const [year, month, day] = value.slice(0, 10).split('-')

  return `${day}/${month}/${year}`
}

export function formatDateTime(value: string): string {
  return new Date(value).toLocaleString(intlLocale(), { dateStyle: 'short', timeStyle: 'short' })
}

export function formatFileSize(bytes: number): string {
  const [kilo, mega] = locale.value === 'nl' ? ['KB', 'MB'] : ['Ko', 'Mo']

  return bytes < 1024 * 1024
    ? `${Math.max(1, Math.round(bytes / 1024))} ${kilo}`
    : `${(bytes / (1024 * 1024)).toFixed(1).replace('.', ',')} ${mega}`
}

export function daysUntil(date: string, today = new Date()): number {
  const [year = 0, month = 1, day = 1] = date.split('-').map(Number)
  const start = Date.UTC(today.getFullYear(), today.getMonth(), today.getDate())

  return Math.round((Date.UTC(year, month - 1, day) - start) / 86_400_000)
}

/**
 * Reads what people type ("1 234,50", "1.234,50" or "1234.5") as a number of cents; null when it
 * is not one. With a comma, dots can only separate thousands.
 */
export function eurosToCents(value: string): number | null {
  let normalized = value.replace(/[\s €]/g, '')

  if (normalized.includes(',')) {
    normalized = normalized.replace(/\.(?=\d{3}(\D|$))/g, '').replace(',', '.')
  }

  if (!/^\d+(\.\d{1,2})?$/.test(normalized)) {
    return null
  }

  return Math.round(Number(normalized) * 100)
}

export function centsToEuros(cents: number | null | undefined): string {
  return cents === null || cents === undefined ? '' : (cents / 100).toFixed(2).replace('.', ',')
}

/** The same day one year later, like the API: 29 February becomes 28 February. */
export function oneYearAfter(date: string): string {
  const [year = 0, month = 1, day = 1] = date.split('-').map(Number)
  const lastDay = new Date(Date.UTC(year + 1, month, 0)).getUTCDate()

  return [year + 1, month, Math.min(day, lastDay)]
    .map((part, index) => String(part).padStart(index === 0 ? 4 : 2, '0'))
    .join('-')
}
