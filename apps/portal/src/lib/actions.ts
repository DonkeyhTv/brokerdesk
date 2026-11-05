"use server";

import { cookies } from "next/headers";
import { redirect } from "next/navigation";
import { ApiError, apiFetch, api } from "./api";
import { SESSION_COOKIE } from "./constants";
import type { Claim, Resource } from "./types";

const ONE_WEEK_IN_SECONDS = 60 * 60 * 24 * 7;

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

  if (response === null) {
    return { message: "Le service ne répond pas. Réessayez dans un instant." };
  }

  if (response.status === 422) {
    const { errors } = await response.json();

    return { errors };
  }

  if (response.status === 403) {
    return {
      message:
        "Ce compte est un compte courtier : connectez-vous à l'espace courtier.",
    };
  }

  if (response.status === 429) {
    return { message: "Trop de tentatives. Réessayez dans une minute." };
  }

  if (!response.ok) {
    return { message: "La connexion a échoué. Réessayez." };
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

    return { message: "La déclaration a échoué. Réessayez." };
  }

  redirect(`/claims?declared=${encodeURIComponent(reference)}`);
}
