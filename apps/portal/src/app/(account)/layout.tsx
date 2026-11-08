import type { Metadata } from "next";
import Link from "next/link";
import { LocaleSwitcher } from "@/components/LocaleSwitcher";
import { Logo } from "@/components/Logo";
import { getMessages } from "@/i18n/server";
import { api } from "@/lib/api";
import { logout } from "@/lib/actions";
import type { Resource, User } from "@/lib/types";

// Private pages: rendered on the server for each person, and never indexed.
export const metadata: Metadata = {
  robots: { index: false, follow: false },
};

export default async function AccountLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  const [{ data: user }, { account: t }] = await Promise.all([
    api<Resource<User>>("/me"),
    getMessages(),
  ]);

  return (
    <div className="flex flex-1 flex-col">
      <header className="border-b border-slate-200 bg-white">
        <div className="mx-auto flex max-w-4xl flex-wrap items-center justify-between gap-3 px-4 py-3 sm:px-6">
          <div className="flex items-center gap-6">
            <Link href="/contracts" aria-label={t.contractsLabel}>
              <Logo />
            </Link>
            <nav aria-label={t.mainNavigation} className="flex gap-4">
              <Link
                href="/contracts"
                className="text-sm font-medium text-slate-700 hover:text-brand-600"
              >
                {t.contracts}
              </Link>
              <Link
                href="/claims"
                className="text-sm font-medium text-slate-700 hover:text-brand-600"
              >
                {t.claims}
              </Link>
            </nav>
          </div>
          <div className="flex items-center gap-3">
            <span className="hidden text-sm text-slate-600 sm:inline">
              {user.name}
            </span>
            <LocaleSwitcher />
            <form action={logout}>
              <button type="submit" className="btn-secondary">
                {t.logout}
              </button>
            </form>
          </div>
        </div>
      </header>

      <main className="mx-auto w-full max-w-4xl flex-1 px-4 py-8 sm:px-6">
        {children}
      </main>

      <footer className="px-4 py-6 text-center text-xs text-slate-500">
        {t.yourBroker(user.brokerage.name)}
        {user.is_demo_account && (
          <>
            {" · "}
            <Link href="/a-propos" className="link">
              {t.howBuilt}
            </Link>
          </>
        )}
      </footer>
    </div>
  );
}
