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

  (await cookies()).set(SESSION_COOKIE, token, {
    httpOnly: true,
    sameSite: "lax",
    secure: process.env.NODE_ENV === "production",
    path: "/",
    maxAge: ONE_WEEK_IN_SECONDS,
  });

  redirect("/contracts");
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
