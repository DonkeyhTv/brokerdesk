import type { Metadata } from "next";
import Link from "next/link";
import { LocaleSwitcher } from "@/components/LocaleSwitcher";
import { Logo } from "@/components/Logo";
import { getMessages } from "@/i18n/server";

export async function generateMetadata(): Promise<Metadata> {
  const { home } = await getMessages();

  return { title: home.metaTitle, alternates: { canonical: "/" } };
}

export default async function Home() {
  const { home } = await getMessages();

  return (
    <div className="flex flex-1 flex-col">
      <header className="mx-auto flex w-full max-w-5xl items-center justify-between gap-3 px-4 py-5 sm:px-6">
        <Logo />
        <div className="flex items-center gap-3">
          <LocaleSwitcher />
          <Link href="/login" className="btn-secondary">
            {home.signIn}
          </Link>
        </div>
      </header>

      <main className="flex-1">
        <section className="mx-auto max-w-5xl px-4 pt-14 pb-16 sm:px-6 sm:pt-20">
          <h1 className="max-w-2xl text-4xl font-semibold tracking-tight text-balance text-slate-900 sm:text-5xl">
            {home.headline}
          </h1>
          <p className="mt-5 max-w-xl text-lg text-pretty text-slate-600">
            {home.intro}
          </p>
          <div className="mt-8 flex flex-wrap gap-3">
            <Link href="/login" className="btn-primary">
              {home.cta}
            </Link>
          </div>
        </section>

        <section
          aria-label={home.featuresLabel}
          className="mx-auto grid max-w-5xl gap-4 px-4 pb-20 sm:grid-cols-3 sm:px-6"
        >
          {home.features.map((feature) => (
            <article key={feature.title} className="card p-6">
              <h2 className="text-base font-semibold text-slate-900">
                {feature.title}
              </h2>
              <p className="mt-2 text-sm text-slate-600">{feature.text}</p>
            </article>
          ))}
        </section>
      </main>

      <footer className="border-t border-slate-200 px-4 py-6 text-center text-xs text-slate-500">
        {home.footer}{" "}
        <Link href="/a-propos" className="link">
          {home.howBuilt}
        </Link>
      </footer>
    </div>
  );
}
