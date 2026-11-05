import "server-only";

import { cookies } from "next/headers";
import { redirect } from "next/navigation";
import { SESSION_COOKIE } from "./constants";
import type { DemoAccounts } from "./types";

export class ApiError extends Error {
  constructor(
    public readonly status: number,
    message: string,
    public readonly errors: Record<string, string[]> = {},
  ) {
    super(message);
    this.name = "ApiError";
  }
}

function apiUrl(path: string): string {
  return `${process.env.API_URL ?? "http://localhost:8000"}/api/v1${path}`;
}

/** A call to the API with the token of the current visitor, if any. */
export async function apiFetch(
  path: string,
  init: RequestInit = {},
): Promise<Response> {
  const token = (await cookies()).get(SESSION_COOKIE)?.value;

  return fetch(apiUrl(path), {
    ...init,
    cache: "no-store",
    headers: {
      Accept: "application/json",
      ...(init.body ? { "Content-Type": "application/json" } : {}),
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
      ...init.headers,
    },
  });
}

/** A JSON call: an expired session goes back to the login page, other failures throw. */
export async function api<T>(path: string, init: RequestInit = {}): Promise<T> {
  const response = await apiFetch(path, init);

  if (response.status === 401) {
    redirect("/login?expired=1");
  }

  const payload = await response.json().catch(() => null);

  if (!response.ok) {
    throw new ApiError(
      response.status,
      payload?.message ?? "API error",
      payload?.errors,
    );
  }

  return payload as T;
}

/** The accounts to offer on the login page, or null outside demo mode or if the API is down. */
export async function demoAccounts(): Promise<DemoAccounts | null> {
  try {
    const response = await fetch(apiUrl("/demo/accounts"), {
      cache: "no-store",
      headers: { Accept: "application/json" },
    });

    return response.ok ? ((await response.json()) as DemoAccounts) : null;
  } catch {
    return null;
  }
}
