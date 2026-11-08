import type { Metadata } from "next";
import Link from "next/link";
import { LocaleSwitcher } from "@/components/LocaleSwitcher";
import { LoginForm } from "@/components/LoginForm";
import { Logo } from "@/components/Logo";
import { TechStack } from "@/components/TechStack";
import { getMessages } from "@/i18n/server";
import { demoAccounts } from "@/lib/api";

export async function generateMetadata(): Promise<Metadata> {
  const { login } = await getMessages();

  return {
    title: login.metaTitle,
    description: login.metaDescription,
    alternates: { canonical: "/login" },
  };
}

export default async function LoginPage({ searchParams }: PageProps<"/login">) {
  const { expired } = await searchParams;
  const [demo, t] = await Promise.all([demoAccounts(), getMessages()]);

  return (
    <main className="flex flex-1 items-center justify-center bg-slate-100 px-4 py-10">
      <div className="w-full max-w-md">
        <div className="mb-6 flex items-center justify-between gap-3">
          <Link href="/" aria-label={t.home.homeLabel}>
            <Logo />
          </Link>
          <LocaleSwitcher />
        </div>
        <LoginForm demo={demo} notice={expired ? t.login.expired : undefined} />
        {demo && (
          <section aria-labelledby="stack-title" className="card mt-4 p-5">
            <h2
              id="stack-title"
              className="mb-3 text-sm font-semibold text-slate-900"
            >
              {t.login.stackTitle}
            </h2>
            <TechStack t={t.about} current="portal" compact />
            <Link href="/a-propos" className="link mt-4 inline-block text-sm">
              {t.login.seeDecisions}
            </Link>
          </section>
        )}
      </div>
    </main>
  );
}
