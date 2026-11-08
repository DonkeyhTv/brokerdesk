import type { Metadata } from "next";
import Link from "next/link";
import { LoginForm } from "@/components/LoginForm";
import { Logo } from "@/components/Logo";
import { TechStack } from "@/components/TechStack";
import { demoAccounts } from "@/lib/api";

export const metadata: Metadata = {
  title: "Connexion",
  description:
    "Connectez-vous à votre espace client BrokerDesk pour consulter vos contrats et vos documents.",
  alternates: { canonical: "/login" },
};

export default async function LoginPage({ searchParams }: PageProps<"/login">) {
  const { expired } = await searchParams;
  const demo = await demoAccounts();

  return (
    <main className="flex flex-1 items-center justify-center bg-slate-100 px-4 py-10">
      <div className="w-full max-w-md">
        <div className="mb-6 flex justify-center">
          <Link href="/" aria-label="Accueil">
            <Logo />
          </Link>
        </div>
        <LoginForm
          demo={demo}
          notice={
            expired ? "Votre session a expiré : reconnectez-vous." : undefined
          }
        />
        {demo && (
          <section aria-labelledby="stack-title" className="card mt-4 p-5">
            <h2
              id="stack-title"
              className="mb-3 text-sm font-semibold text-slate-900"
            >
              Comment c&apos;est construit
            </h2>
            <TechStack current="portal" compact />
            <Link href="/a-propos" className="link mt-4 inline-block text-sm">
              Voir les choix techniques →
            </Link>
          </section>
        )}
      </div>
    </main>
  );
}
