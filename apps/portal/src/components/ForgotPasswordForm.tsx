"use client";

import { useActionState } from "react";
import { useI18n } from "@/i18n/client";
import { requestPasswordReset, type FormState } from "@/lib/actions";
import { FieldError } from "./FieldError";

const initialState: FormState = {};

export function ForgotPasswordForm() {
  const [state, action, isPending] = useActionState(
    requestPasswordReset,
    initialState,
  );
  const { t } = useI18n();

  if (state.done) {
    return (
      <p
        role="status"
        className="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800"
      >
        {t.password.sent}
      </p>
    );
  }

  return (
    <form action={action} className="space-y-4">
      {state.message && (
        <p
          role="alert"
          className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"
        >
          {state.message}
        </p>
      )}
      <div>
        <label
          htmlFor="email"
          className="mb-1 block text-sm font-medium text-slate-700"
        >
          {t.password.email}
        </label>
        <input
          id="email"
          name="email"
          type="email"
          autoComplete="email"
          className="input"
          required
        />
        <FieldError name="email" errors={state.errors} />
      </div>
      <button type="submit" className="btn-primary w-full" disabled={isPending}>
        {isPending ? t.password.sending : t.password.send}
      </button>
    </form>
  );
}
