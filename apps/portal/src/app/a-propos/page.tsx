import type { Metadata } from "next";
import Link from "next/link";
import { LocaleSwitcher } from "@/components/LocaleSwitcher";
import { Logo } from "@/components/Logo";
import { TechStack } from "@/components/TechStack";
import { getMessages } from "@/i18n/server";
import { SOURCE_URL } from "@/lib/stack";

export async function generateMetadata(): Promise<Metadata> {
  const { about } = await getMessages();

  return {
    title: about.title,
    description: about.metaDescription,
    alternates: { canonical: "/a-propos" },
  };
}

const BACK_OFFICE_URL =
  process.env.NEXT_PUBLIC_BACK_OFFICE_URL ?? "http://localhost:5173";

export default async function AboutPage() {
  const t = await getMessages();

  return (
    <div className="flex flex-1 flex-col">
      <header className="mx-auto flex w-full max-w-5xl items-center justify-between gap-3 px-4 py-5 sm:px-6">
        <Link href="/" aria-label={t.home.homeLabel}>
          <Logo />
        </Link>
        <div className="flex items-center gap-3">
          <LocaleSwitcher />
          <Link href="/login" className="btn-secondary">
            {t.home.signIn}
          </Link>
        </div>
      </header>

      <main className="mx-auto w-full max-w-5xl flex-1 px-4 pt-6 pb-16 sm:px-6">
        <h1 className="text-3xl font-semibold tracking-tight text-slate-900">
          {t.about.title}
        </h1>
        <p className="mt-2 text-slate-600">{t.about.subtitle}</p>
        <div className="mt-5 mb-8 flex flex-wrap gap-2">
          <a href={SOURCE_URL} className="btn-primary" rel="noopener">
            {t.about.source}
          </a>
          <a href={BACK_OFFICE_URL} className="btn-secondary">
            {t.about.backOffice}
          </a>
        </div>

        <TechStack t={t.about} current="portal" />

        <h2 className="mt-12 mb-4 text-lg font-semibold text-slate-900">
          {t.about.decisionsTitle}
        </h2>
        <div className="grid gap-4 sm:grid-cols-2">
          {t.about.decisions.map((decision) => (
            <section key={decision.title} className="card p-5">
              <h3 className="text-sm font-semibold text-slate-900">
                {decision.title}
              </h3>
              <p className="mt-2 text-sm text-slate-600">{decision.text}</p>
            </section>
          ))}
        </div>
      </main>
    </div>
  );
}
