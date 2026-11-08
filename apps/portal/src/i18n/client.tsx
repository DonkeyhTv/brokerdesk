"use client";

import { createContext, useContext } from "react";
import { messagesFor, type Locale } from ".";

const LocaleContext = createContext<Locale>("fr");

/** Client components read the language chosen on the server, so both render the same text. */
export function I18nProvider({
  locale,
  children,
}: {
  locale: Locale;
  children: React.ReactNode;
}) {
  return <LocaleContext value={locale}>{children}</LocaleContext>;
}

export function useI18n() {
  const locale = useContext(LocaleContext);

  return { locale, t: messagesFor(locale) };
}
