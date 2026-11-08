"use server";

import { cookies } from "next/headers";
import { redirect } from "next/navigation";
import { isLocale, LOCALE_COOKIE } from "@/i18n";
import { getMessages } from "@/i18n/server";
import { ApiError, apiFetch, api } from "./api";
import { SESSION_COOKIE } from "./constants";
import type { Claim, Resource } from "./types";

const ONE_WEEK_IN_SECONDS = 60 * 60 * 24 * 7;
const ONE_YEAR_IN_SECONDS = 60 * 60 * 24 * 365;

export interface FormState {
  message?: string;
  errors?: Record<string, string[]>;
  /** Set once the request went through, for forms that stay on screen. */
  done?: boolean;
}

/** The portal token stays in an httpOnly cookie, out of reach of scripts. */
async function startSession(token: string): Promise<void> {
  (await cookies()).set(SESSION_COOKIE, token, {
    httpOnly: true,
    sameSite: "lax",
    secure: process.env.NODE_ENV === "production",
    path: "/",
    maxAge: ONE_WEEK_IN_SECONDS,
  });
}

export async function login(
  _previous: FormState,
  formData: FormData,
): Promise<FormState> {
  const response = await apiFetch("/auth/tokens", {
    method: "POST",
    body: JSON.stringify({
      email: formData.get("email"),
      password: formData.get("password"),
    }),
  }).catch(() => null);
  const { login: t } = await getMessages();

  if (response === null) {
    return { message: t.unavailable };
  }

  if (response.status === 422) {
    const { errors } = await response.json();

    return { errors };
  }

  if (response.status === 403) {
    return { message: t.brokerAccount };
  }

  if (response.status === 429) {
    return { message: t.tooManyAttempts };
  }

  if (!response.ok) {
    return { message: t.failed };
  }

  const { token } = await response.json();
  await startSession(token);

  redirect("/contracts");
}

/** Sends a chosen password with the token of its link, and reads the API's answer. */
async function choosePassword(
  path: string,
  formData: FormData,
): Promise<{ response: Response | null; state: FormState }> {
  const response = await apiFetch(path, {
    method: "POST",
    body: JSON.stringify({
      email: formData.get("email"),
      token: formData.get("token"),
      password: formData.get("password"),
      password_confirmation: formData.get("password_confirmation"),
    }),
  }).catch(() => null);
  const { password: t, login } = await getMessages();

  if (response === null) {
    return { response, state: { message: login.unavailable } };
  }

  if (response.status === 422) {
    const { errors = {} } = await response.json();
    // A bad link is not a field to fix: say it above the form.
    const { token, email, ...fields } = errors as Record<string, string[]>;

    return {
      response,
      state: { message: token?.[0] ?? email?.[0], errors: fields },
    };
  }

  if (response.status === 429) {
    return { response, state: { message: login.tooManyAttempts } };
  }

  return response.ok
    ? { response, state: {} }
    : { response, state: { message: t.failed } };
}

export async function acceptInvitation(
  _previous: FormState,
  formData: FormData,
): Promise<FormState> {
  const { response, state } = await choosePassword(
    "/auth/invitation",
    formData,
  );

  if (!response?.ok) {
    return state;
  }

  const { token } = await response.json();
  await startSession(token);

  redirect("/contracts");
}

export async function resetPassword(
  _previous: FormState,
  formData: FormData,
): Promise<FormState> {
  const { response, state } = await choosePassword(
    "/auth/reset-password",
    formData,
  );

  if (!response?.ok) {
    return state;
  }

  redirect("/login?reset=1");
}

export async function requestPasswordReset(
  _previous: FormState,
  formData: FormData,
): Promise<FormState> {
  const response = await apiFetch("/auth/forgot-password", {
    method: "POST",
    body: JSON.stringify({ email: formData.get("email") }),
  }).catch(() => null);
  const { login } = await getMessages();

  if (response === null) {
    return { message: login.unavailable };
  }

  if (response.status === 422) {
    return { errors: (await response.json()).errors };
  }

  if (response.status === 429) {
    return { message: login.tooManyAttempts };
  }

  // The same answer whether or not an account exists: the API decides who gets a mail.
  return { done: true };
}

export async function logout(): Promise<void> {
  // Whatever the API answers, this browser must end up signed out.
  await apiFetch("/auth/tokens/current", { method: "DELETE" }).catch(
    () => null,
  );

  (await cookies()).delete(SESSION_COOKIE);

  redirect("/");
}

export async function declareClaim(
  _previous: FormState,
  formData: FormData,
): Promise<FormState> {
  let reference: string;

  try {
    const { data } = await api<Resource<Claim>>("/claims", {
      method: "POST",
      body: JSON.stringify({
        contract_id: Number(formData.get("contract_id")) || null,
        occurred_on: formData.get("occurred_on"),
        description: formData.get("description"),
      }),
    });

    reference = data.reference;
  } catch (error) {
    if (error instanceof ApiError && error.status === 422) {
      return { errors: error.errors };
    }

    // A redirect also travels as an exception, and must keep going.
    if (!(error instanceof ApiError)) {
      throw error;
    }

    return { message: (await getMessages()).claims.failed };
  }

  redirect(`/claims?declared=${encodeURIComponent(reference)}`);
}

export async function setLocale(locale: string): Promise<void> {
  if (!isLocale(locale)) {
    return;
  }

  (await cookies()).set(LOCALE_COOKIE, locale, {
    sameSite: "lax",
    secure: process.env.NODE_ENV === "production",
    path: "/",
    maxAge: ONE_YEAR_IN_SECONDS,
  });
}
