const euros = new Intl.NumberFormat('fr-BE', { style: 'currency', currency: 'EUR' })
const wholeEuros = new Intl.NumberFormat('fr-BE', {
  style: 'currency',
  currency: 'EUR',
  maximumFractionDigits: 0,
})

export function formatMoney(cents: number | null | undefined): string {
  return cents === null || cents === undefined ? '—' : euros.format(cents / 100)
}

export function formatWholeMoney(cents: number): string {
  return wholeEuros.format(cents / 100)
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
  return new Date(value).toLocaleString('fr-BE', { dateStyle: 'short', timeStyle: 'short' })
}

export function formatFileSize(bytes: number): string {
  return bytes < 1024 * 1024
    ? `${Math.max(1, Math.round(bytes / 1024))} Ko`
    : `${(bytes / (1024 * 1024)).toFixed(1).replace('.', ',')} Mo`
}

export function daysUntil(date: string, today = new Date()): number {
  const [year = 0, month = 1, day = 1] = date.split('-').map(Number)
  const start = Date.UTC(today.getFullYear(), today.getMonth(), today.getDate())

  return Math.round((Date.UTC(year, month - 1, day) - start) / 86_400_000)
}

/** Reads what people type ("1 234,50" or "1234.5") as a number of cents; null when it is not one. */
export function eurosToCents(value: string): number | null {
  const normalized = value.replace(/[\s €]/g, '').replace(',', '.')

  if (!/^\d+(\.\d{1,2})?$/.test(normalized)) {
    return null
  }

  return Math.round(Number(normalized) * 100)
}

export function centsToEuros(cents: number | null | undefined): string {
  return cents === null || cents === undefined ? '' : (cents / 100).toFixed(2).replace('.', ',')
}
