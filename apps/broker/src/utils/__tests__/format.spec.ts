import { describe, expect, it } from 'vitest'
import {
  centsToEuros,
  daysUntil,
  eurosToCents,
  formatDate,
  formatFileSize,
  formatMoney,
  oneYearAfter,
} from '../format'

describe('eurosToCents', () => {
  it.each([
    ['850', 85000],
    ['850,5', 85050],
    ['1 234,56', 123456],
    ['1234.56', 123456],
    ['0,07', 7],
    ['19,99 €', 1999],
  ])('reads %s', (input, cents) => {
    expect(eurosToCents(input)).toBe(cents)
  })

  it.each(['', 'abc', '-5', '1,234', '12,3,4'])('refuses %s', (input) => {
    expect(eurosToCents(input)).toBeNull()
  })
})

describe('centsToEuros', () => {
  it('round-trips with eurosToCents', () => {
    expect(eurosToCents(centsToEuros(123456))).toBe(123456)
    expect(centsToEuros(null)).toBe('')
  })
})

describe('formatting', () => {
  it('formats money the Belgian way, and shows absent amounts', () => {
    expect(formatMoney(123456).replace(/\s/g, ' ')).toBe('1 234,56 €')
    expect(formatMoney(null)).toBe('—')
  })

  it('never shifts a date across time zones', () => {
    expect(formatDate('2026-12-31')).toBe('31/12/2026')
    expect(formatDate(null)).toBe('—')
  })

  it('counts days from today', () => {
    expect(daysUntil('2026-11-15', new Date(2026, 10, 5, 23, 30))).toBe(10)
    expect(daysUntil('2026-11-01', new Date(2026, 10, 5))).toBe(-4)
  })

  it('shows readable file sizes', () => {
    expect(formatFileSize(2048)).toBe('2 Ko')
    expect(formatFileSize(2.5 * 1024 * 1024)).toBe('2,5 Mo')
  })
})

describe('oneYearAfter', () => {
  it.each([
    ['2026-11-30', '2027-11-30'],
    ['2028-02-29', '2029-02-28'],
    ['2026-01-05', '2027-01-05'],
  ])('moves %s to %s, like the API', (date, expected) => {
    expect(oneYearAfter(date)).toBe(expected)
  })
})
