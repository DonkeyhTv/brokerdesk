import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { api, ApiError, apiFetch, demoAccounts } from "./api";

const token = vi.hoisted(() => ({ value: "secret" as string | undefined }));
const redirect = vi.hoisted(() =>
  vi.fn((url: string) => {
    throw new Error(`REDIRECT ${url}`);
  }),
);

vi.mock("server-only", () => ({}));
vi.mock("next/navigation", () => ({ redirect }));
vi.mock("next/headers", () => ({
  cookies: async () => ({
    get: () => (token.value ? { value: token.value } : undefined),
  }),
}));

describe("api", () => {
  const fetchMock = vi.fn<typeof fetch>();

  beforeEach(() => {
    token.value = "secret";
    fetchMock.mockReset();
    redirect.mockClear();
    vi.stubGlobal("fetch", fetchMock);
    vi.stubEnv("API_URL", "http://nginx");
  });

  afterEach(() => {
    vi.unstubAllGlobals();
    vi.unstubAllEnvs();
  });

  it("calls the versioned API with the token and without any cache", async () => {
    fetchMock.mockResolvedValue(new Response("{}"));

    await apiFetch("/contracts");

    const [url, init] = fetchMock.mock.calls[0]!;
    expect(url).toBe("http://nginx/api/v1/contracts");
    expect(init?.cache).toBe("no-store");
    expect(init?.headers).toMatchObject({
      Authorization: "Bearer secret",
      Accept: "application/json",
    });
  });

  it("sends no Authorization header to a visitor without a session", async () => {
    token.value = undefined;
    fetchMock.mockResolvedValue(new Response("{}"));

    await apiFetch("/auth/tokens", { method: "POST", body: "{}" });

    const headers = fetchMock.mock.calls[0]![1]?.headers as Record<
      string,
      string
    >;
    expect(headers.Authorization).toBeUndefined();
    expect(headers["Content-Type"]).toBe("application/json");
  });

  it("goes back to the login page when the session has expired", async () => {
    fetchMock.mockResolvedValue(new Response("{}", { status: 401 }));

    await expect(api("/me")).rejects.toThrow("REDIRECT /login?expired=1");
  });

  it("throws API errors with their validation details", async () => {
    fetchMock.mockResolvedValue(
      new Response(
        JSON.stringify({ message: "Invalid", errors: { a: ["b"] } }),
        { status: 422 },
      ),
    );

    const error = await api("/claims").catch((caught: ApiError) => caught);

    expect(error).toBeInstanceOf(ApiError);
    expect((error as ApiError).errors).toEqual({ a: ["b"] });
  });

  it("offers no demo accounts outside demo mode, or when the API is down", async () => {
    fetchMock.mockResolvedValueOnce(new Response("{}", { status: 404 }));
    await expect(demoAccounts()).resolves.toBeNull();

    fetchMock.mockRejectedValueOnce(new Error("down"));
    await expect(demoAccounts()).resolves.toBeNull();
  });
});
