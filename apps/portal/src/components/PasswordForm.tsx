"use client";

import { useActionState } from "react";
import { useI18n } from "@/i18n/client";
import { acceptInvitation, resetPassword, type FormState } from "@/lib/actions";
import { FieldError } from "./FieldError";

const initialState: FormState = {};

/** A password chosen from a link sent by mail: an invitation, or a reset. */
export function PasswordForm({
  purpose,
  token,
  email,
}: {
  purpose: "invitation" | "reset";
  token: string;
  email: string;
}) {
  const [state, action, isPending] = useActionState(
    purpose === "invitation" ? acceptInvitation : resetPassword,
    initialState,
  );
  const { t } = useI18n();

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

      <input type="hidden" name="token" value={token} />
      <input type="hidden" name="email" value={email} />
      {/* Lets password managers save the new password with its account. */}
      <input
        type="text"
        name="username"
        value={email}
        autoComplete="username"
        readOnly
        hidden
      />

      <div>
        <label
          htmlFor="password"
          className="mb-1 block text-sm font-medium text-slate-700"
        >
          {t.password.password}
        </label>
        <input
          id="password"
          name="password"
          type="password"
          autoComplete="new-password"
          className="input"
          aria-describedby="password-hint"
          required
        />
        <p id="password-hint" className="mt-1 text-xs text-slate-500">
          {t.password.hint}
        </p>
        <FieldError name="password" errors={state.errors} />
      </div>

      <div>
        <label
          htmlFor="password_confirmation"
          className="mb-1 block text-sm font-medium text-slate-700"
        >
          {t.password.confirmation}
        </label>
        <input
          id="password_confirmation"
          name="password_confirmation"
          type="password"
          autoComplete="new-password"
          className="input"
          required
        />
      </div>

      <button type="submit" className="btn-primary w-full" disabled={isPending}>
        {isPending
          ? t.password.saving
          : purpose === "invitation"
            ? t.password.activate
            : t.password.save}
      </button>
    </form>
  );
}
