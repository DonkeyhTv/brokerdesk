import type { Metadata } from "next";
import Link from "next/link";
import { Logo } from "@/components/Logo";
import { TechStack } from "@/components/TechStack";
import { SOURCE_URL } from "@/lib/stack";

export const metadata: Metadata = {
  title: "Comment c'est construit",
  description:
    "Les technologies et les choix techniques derrière BrokerDesk : Laravel, Vue, Next.js et PostgreSQL.",
  alternates: { canonical: "/a-propos" },
};

const BACK_OFFICE_URL =
  process.env.NEXT_PUBLIC_BACK_OFFICE_URL ?? "http://localhost:5173";

const DECISIONS = [
  {
    title: "Chaque cabinet est isolé deux fois",
    text: "Les requêtes et les règles d’accès filtrent par cabinet, et la base de données refuse elle-même un contrat rattaché au client d’un autre cabinet.",
  },
  {
    title: "Deux connexions pour deux publics",
    text: "Ce portail garde la clé du client sur son serveur, hors de portée des scripts du navigateur. L’espace courtier utilise une session protégée contre le CSRF.",
  },
  {
    title: "Ce qui est interdit reste invisible",
    text: "Un dossier auquel vous n’avez pas accès répond « introuvable » plutôt que « interdit », pour ne pas révéler qu’il existe.",
  },
  {
    title: "Les règles belges sont dans le code",
    text: "Numéros d’entreprise vérifiés par leur chiffre de contrôle, préavis de trois mois avant la reconduction tacite, montants stockés en centimes.",
  },
];

export default function AboutPage() {
  return (
    <div className="flex flex-1 flex-col">
      <header className="mx-auto flex w-full max-w-5xl items-center justify-between px-4 py-5 sm:px-6">
        <Link href="/" aria-label="Accueil">
          <Logo />
        </Link>
        <Link href="/login" className="btn-secondary">
          Se connecter
        </Link>
      </header>

      <main className="mx-auto w-full max-w-5xl flex-1 px-4 pt-6 pb-16 sm:px-6">
        <h1 className="text-3xl font-semibold tracking-tight text-slate-900">
          Comment c&apos;est construit
        </h1>
        <p className="mt-2 text-slate-600">
          Les technologies et les choix derrière cette démonstration.
        </p>
        <div className="mt-5 mb-8 flex flex-wrap gap-2">
          <a href={SOURCE_URL} className="btn-primary" rel="noopener">
            Code source sur GitHub
          </a>
          <a href={BACK_OFFICE_URL} className="btn-secondary">
            Espace courtier →
          </a>
        </div>

        <TechStack current="portal" />

        <h2 className="mt-12 mb-4 text-lg font-semibold text-slate-900">
          Choix techniques
        </h2>
        <div className="grid gap-4 sm:grid-cols-2">
          {DECISIONS.map((decision) => (
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
