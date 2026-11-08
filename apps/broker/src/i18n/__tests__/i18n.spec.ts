import { afterEach, describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import { detectLocale, locale, setLocale } from '@/i18n'
import { fr } from '@/i18n/fr'
import { nl } from '@/i18n/nl'
import { eurosToCents, formatFileSize, formatMoney } from '@/utils/format'
import LocaleSwitcher from '@/components/LocaleSwitcher.vue'
import StatusBadge from '@/components/StatusBadge.vue'

function leaves(catalog: object, prefix = ''): [string, unknown][] {
  return Object.entries(catalog).flatMap(([key, value]) =>
    value !== null && typeof value === 'object' && !Array.isArray(value)
      ? leaves(value, `${prefix}${key}.`)
      : [[`${prefix}${key}`, value]],
  )
}

describe('i18n', () => {
  afterEach(() => {
    setLocale('fr')
    localStorage.clear()
  })

  it.each([
    [null, ['nl-BE', 'fr-BE'], 'nl'],
    [null, ['en-US', 'nl'], 'nl'],
    [null, ['fr-BE', 'nl-BE'], 'fr'],
    [null, ['de-DE'], 'fr'],
    ['nl', ['fr-BE'], 'nl'],
    ['xx', ['nl-BE'], 'nl'],
  ])(
    'with the saved choice %s and the browser languages %j, starts in %s',
    (saved, languages, expected) => {
      expect(detectLocale(saved, languages)).toBe(expected)
    },
  )

  it('translates every message, and not with the French text', () => {
    const french = new Map(leaves(fr))
    const untouched = leaves(nl).filter(
      ([key, value]) => typeof value === 'string' && value === french.get(key),
    )

    // Words that are the same in both languages.
    expect(untouched.map(([key]) => key).sort()).toEqual(
      [
        'about.layers.api.name',
        'clients.email',
        'clients.type',
        'labels.clientTypes.individual',
        'labels.contractTypes.motor',
        'users.email',
      ].sort(),
    )
  })

  it('switches the whole interface at once, and remembers the choice', async () => {
    const badge = mount(StatusBadge, { props: { status: 'settled' } })
    const switcher = mount(LocaleSwitcher)
    expect(badge.text()).toBe('Réglé')

    await switcher.get('button[lang="nl"]').trigger('click')

    expect(locale.value).toBe('nl')
    expect(badge.text()).toBe('Vergoed')
    expect(document.documentElement.lang).toBe('nl')
    expect(localStorage.getItem('brokerdesk.locale')).toBe('nl')
    expect(switcher.get('button[lang="nl"]').attributes('aria-pressed')).toBe('true')
  })

  it('writes amounts and sizes as each language does in Belgium', () => {
    expect(formatMoney(123450)).toMatch(/^1\s234,50\s€$/)
    expect(formatFileSize(2048)).toBe('2 Ko')

    setLocale('nl')

    expect(formatMoney(123450)).toMatch(/^€\s1\.234,50$/)
    expect(formatFileSize(2048)).toBe('2 KB')
  })

  it('reads amounts typed with either thousands separator', () => {
    expect(eurosToCents('1 234,50')).toBe(123450)
    expect(eurosToCents('1.234,50')).toBe(123450)
    expect(eurosToCents('1234.5')).toBe(123450)
    expect(eurosToCents('12,3,4')).toBeNull()
  })
})
