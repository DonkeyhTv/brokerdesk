import { intlLocale, type Locale } from "@/i18n";

export function formatMoney(cents: number, locale: Locale): string {
  return new Intl.NumberFormat(intlLocale(locale), {
    style: "currency",
    currency: "EUR",
  }).format(cents / 100);
}

/** Dates arrive as YYYY-MM-DD: they are read without any time zone, to never shift a day. */
export function formatDate(value: string): string {
  const [year, month, day] = value.slice(0, 10).split("-");

  return `${day}/${month}/${year}`;
}

export function formatFileSize(bytes: number, locale: Locale): string {
  const [kilo, mega] = locale === "nl" ? ["KB", "MB"] : ["Ko", "Mo"];

  return bytes < 1024 * 1024
    ? `${Math.max(1, Math.round(bytes / 1024))} ${kilo}`
    : `${(bytes / (1024 * 1024)).toFixed(1).replace(".", ",")} ${mega}`;
}
