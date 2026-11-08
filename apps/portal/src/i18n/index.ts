import { fr, type Messages } from "./fr";
import { nl } from "./nl";

export type { Messages };
export type Locale = "fr" | "nl";

export const LOCALES: Locale[] = ["fr", "nl"];
export const LOCALE_COOKIE = "bd_locale";

const CATALOGS: Record<Locale, Messages> = { fr, nl };

export function isLocale(value: unknown): value is Locale {
  return value === "fr" || value === "nl";
}

export function messagesFor(locale: Locale): Messages {
  return CATALOGS[locale];
}

/** Dates, amounts and numbers as written in Belgium in that language. */
export function intlLocale(locale: Locale): string {
  return locale === "nl" ? "nl-BE" : "fr-BE";
}

/**
 * A saved choice first; otherwise the first of French or Dutch in the browser's
 * Accept-Language header, French when it names neither.
 */
export function negotiateLocale(
  saved: string | undefined,
  acceptLanguage: string | null,
): Locale {
  if (isLocale(saved)) {
    return saved;
  }

  const preferred = (acceptLanguage ?? "")
    .split(",")
    .map((part) => {
      const [tag = "", ...parameters] = part.trim().split(";");
      const quality = parameters
        .map((parameter) => parameter.trim())
        .find((parameter) => parameter.startsWith("q="));

      return {
        language: tag.slice(0, 2).toLowerCase(),
        quality: quality ? Number(quality.slice(2)) : 1,
      };
    })
    .filter((entry) => isLocale(entry.language) && entry.quality > 0)
    .sort((a, b) => b.quality - a.quality)[0];

  return preferred && isLocale(preferred.language) ? preferred.language : "fr";
}
