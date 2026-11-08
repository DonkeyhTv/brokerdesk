"use client";

import { useTransition } from "react";
import { useRouter } from "next/navigation";
import { LOCALES } from "@/i18n";
import { useI18n } from "@/i18n/client";
import { setLocale } from "@/lib/actions";

export function LocaleSwitcher() {
  const { locale, t } = useI18n();
  const router = useRouter();
  const [isPending, startTransition] = useTransition();

  return (
    <div
      role="group"
      aria-label={t.app.language}
      className="inline-flex rounded-lg ring-1 ring-slate-300"
    >
      {LOCALES.map((option) => (
        <button
          key={option}
          type="button"
          lang={option}
          aria-pressed={option === locale}
          disabled={isPending}
          className={`px-2.5 py-1.5 text-xs font-semibold uppercase first:rounded-l-lg last:rounded-r-lg ${
            option === locale
              ? "bg-slate-900 text-white"
              : "text-slate-600 hover:bg-slate-100"
          }`}
          onClick={() =>
            startTransition(async () => {
              await setLocale(option);
              router.refresh();
            })
          }
        >
          {option}
        </button>
      ))}
    </div>
  );
}
