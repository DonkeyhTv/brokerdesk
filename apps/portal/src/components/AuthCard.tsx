import Link from "next/link";
import { getMessages } from "@/i18n/server";
import { LocaleSwitcher } from "./LocaleSwitcher";
import { Logo } from "./Logo";

/** The frame of the pages a visitor sees before signing in. */
export async function AuthCard({
  title,
  intro,
  children,
}: {
  title: string;
  intro?: string;
  children: React.ReactNode;
}) {
  const t = await getMessages();

  return (
    <main className="flex flex-1 items-center justify-center bg-slate-100 px-4 py-10">
      <div className="w-full max-w-md">
        <div className="mb-6 flex items-center justify-between gap-3">
          <Link href="/" aria-label={t.home.homeLabel}>
            <Logo />
          </Link>
          <LocaleSwitcher />
        </div>
        <div className="card p-6 sm:p-8">
          <h1 className="text-lg font-semibold text-slate-900">{title}</h1>
          {intro && <p className="mt-1 mb-5 text-sm text-slate-500">{intro}</p>}
          {children}
        </div>
        <Link href="/login" className="link mt-4 inline-block text-sm">
          {t.password.backToLogin}
        </Link>
      </div>
    </main>
  );
}
