"use client";

import Link from "next/link";
import { useActionState } from "react";
import { useI18n } from "@/i18n/client";
import { login, type FormState } from "@/lib/actions";
import type { DemoAccounts } from "@/lib/types";
import { FieldError } from "./FieldError";

const BACK_OFFICE_URL =
  process.env.NEXT_PUBLIC_BACK_OFFICE_URL ?? "http://localhost:5173";

const initialState: FormState = {};

export function LoginForm({
  demo,
  notice,
}: {
  demo: DemoAccounts | null;
  notice?: string;
}) {
  const [state, action, isPending] = useActionState(login, initialState);
  const { t } = useI18n();
  const accounts =
    demo?.accounts.filter((account) => account.app === "portal") ?? [];

  return (
    <>
      <div className="card p-6 sm:p-8">
        <h1 className="text-lg font-semibold text-slate-900">
          {t.login.title}
        </h1>
        <p className="mt-1 mb-5 text-sm text-slate-500">{t.login.subtitle}</p>

        {(state.message ?? notice) && (
          <p
            role="alert"
            className="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"
          >
            {state.message ?? notice}
          </p>
        )}

        <form action={action} className="space-y-4">
          <div>
            <label
              htmlFor="email"
              className="mb-1 block text-sm font-medium text-slate-700"
            >
              {t.login.email}
            </label>
            <input
              id="email"
              name="email"
              type="email"
              autoComplete="username"
              className="input"
              required
            />
            <FieldError name="email" errors={state.errors} />
          </div>
          <div>
            <label
              htmlFor="password"
              className="mb-1 block text-sm font-medium text-slate-700"
            >
              {t.login.password}
            </label>
            <input
              id="password"
              name="password"
              type="password"
              autoComplete="current-password"
              className="input"
              required
            />
            <FieldError name="password" errors={state.errors} />
            <Link
              href="/mot-de-passe-oublie"
              className="link mt-1 inline-block text-xs"
            >
              {t.login.forgot}
            </Link>
          </div>
          <button
            type="submit"
            className="btn-primary w-full"
            disabled={isPending}
          >
            {isPending ? t.login.submitting : t.login.submit}
          </button>
        </form>
      </div>

      {demo && (
        <section
          aria-labelledby="demo-title"
          className="card mt-4 border-brand-100 bg-brand-50 p-5"
        >
          <h2 id="demo-title" className="text-sm font-semibold text-brand-700">
            {t.login.demoTitle}
          </h2>
          <p className="mt-1 text-sm text-slate-600">{t.login.demoText}</p>
          <div className="mt-3 flex flex-wrap gap-2">
            {accounts.map((account) => (
              <form key={account.email} action={action}>
                <input type="hidden" name="email" value={account.email} />
                <input type="hidden" name="password" value={demo.password} />
                <button
                  type="submit"
                  className="btn-secondary"
                  disabled={isPending}
                >
                  {account.label}
                </button>
              </form>
            ))}
            <a href={BACK_OFFICE_URL} className="btn-secondary">
              {t.login.backOffice}
            </a>
          </div>
        </section>
      )}
    </>
  );
}
