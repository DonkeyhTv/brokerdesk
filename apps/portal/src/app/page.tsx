import type { Metadata } from "next";
import Link from "next/link";
import { Logo } from "@/components/Logo";

export const metadata: Metadata = {
  title: "BrokerDesk – Vos assurances, au même endroit",
  alternates: { canonical: "/" },
};

const FEATURES = [
  {
    title: "Vos contrats",
    text: "Auto, habitation, RC familiale… Retrouvez vos garanties, vos primes et vos échéances.",
  },
  {
    title: "Vos documents",
    text: "Téléchargez à tout moment les documents que votre courtier met à votre disposition.",
  },
  {
    title: "Vos sinistres",
    text: "Déclarez un sinistre en quelques minutes et suivez son avancement.",
  },
];

export default function Home() {
  return (
    <div className="flex flex-1 flex-col">
      <header className="mx-auto flex w-full max-w-5xl items-center justify-between px-4 py-5 sm:px-6">
        <Logo />
        <Link href="/login" className="btn-secondary">
          Se connecter
        </Link>
      </header>

      <main className="flex-1">
        <section className="mx-auto max-w-5xl px-4 pt-14 pb-16 sm:px-6 sm:pt-20">
          <h1 className="max-w-2xl text-4xl font-semibold tracking-tight text-balance text-slate-900 sm:text-5xl">
            Vos assurances, au même endroit
          </h1>
          <p className="mt-5 max-w-xl text-lg text-pretty text-slate-600">
            Consultez vos contrats, téléchargez vos documents et déclarez un
            sinistre en ligne, en lien direct avec votre courtier.
          </p>
          <div className="mt-8 flex flex-wrap gap-3">
            <Link href="/login" className="btn-primary">
              Accéder à mon espace
            </Link>
          </div>
        </section>

        <section
          aria-label="Ce que vous pouvez faire"
          className="mx-auto grid max-w-5xl gap-4 px-4 pb-20 sm:grid-cols-3 sm:px-6"
        >
          {FEATURES.map((feature) => (
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
        BrokerDesk est un projet de démonstration : les cabinets, les assureurs
        et les données sont fictifs.
      </footer>
    </div>
  );
}
