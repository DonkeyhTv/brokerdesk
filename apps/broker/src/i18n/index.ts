import { computed, ref } from 'vue'
import { fr, type Messages } from './fr'
import { nl } from './nl'

export type Locale = 'fr' | 'nl'

export const LOCALES: Locale[] = ['fr', 'nl']

const STORAGE_KEY = 'brokerdesk.locale'
const CATALOGS: Record<Locale, Messages> = { fr, nl }

/** A saved choice first; otherwise Dutch for Dutch-speaking browsers, French for everyone else. */
export function detectLocale(
  saved: string | null,
  browserLanguages: readonly string[] = [],
): Locale {
  if (saved === 'fr' || saved === 'nl') {
    return saved
  }

  const first = browserLanguages.find((language) => /^(fr|nl)\b/i.test(language))

  return first?.slice(0, 2).toLowerCase() === 'nl' ? 'nl' : 'fr'
}

function readSaved(): string | null {
  try {
    return localStorage.getItem(STORAGE_KEY)
  } catch {
    return null
  }
}

export const locale = ref<Locale>(
  detectLocale(readSaved(), typeof navigator === 'undefined' ? [] : navigator.languages),
)

/** The messages of the current language: reading them inside a template or a computed follows changes. */
export const t = computed<Messages>(() => CATALOGS[locale.value])

/** For Intl: dates, amounts and numbers as written in Belgium in that language. */
export function intlLocale(): string {
  return locale.value === 'nl' ? 'nl-BE' : 'fr-BE'
}

export function setLocale(next: Locale): void {
  locale.value = next

  try {
    localStorage.setItem(STORAGE_KEY, next)
  } catch {
    // Private browsing: the choice lasts until the page closes.
  }

  applyToDocument()
}

export function applyToDocument(): void {
  document.documentElement.lang = locale.value
  document.title = t.value.app.title
}

export function useI18n() {
  return { t, locale, setLocale }
}
