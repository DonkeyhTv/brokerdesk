"use client";

import { useI18n } from "@/i18n/client";

export default function ErrorPage({ reset }: { reset: () => void }) {
  const { t } = useI18n();

  return (
    <main className="flex flex-1 flex-col items-center justify-center px-4 py-20 text-center">
      <h1 className="text-2xl font-semibold text-slate-900">
        {t.errors.title}
      </h1>
      <p className="mt-2 text-sm text-slate-500">{t.errors.text}</p>
      <button type="button" className="btn-primary mt-6" onClick={reset}>
        {t.errors.retry}
      </button>
    </main>
  );
}
