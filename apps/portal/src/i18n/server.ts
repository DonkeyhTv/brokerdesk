import "server-only";

import { cookies, headers } from "next/headers";
import {
  LOCALE_COOKIE,
  messagesFor,
  negotiateLocale,
  type Locale,
  type Messages,
} from ".";

/** The language of the current request: the visitor's choice, or their browser's. */
export async function getLocale(): Promise<Locale> {
  const saved = (await cookies()).get(LOCALE_COOKIE)?.value;

  return negotiateLocale(saved, (await headers()).get("accept-language"));
}

export async function getMessages(): Promise<Messages> {
  return messagesFor(await getLocale());
}
