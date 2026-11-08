import { beforeEach, describe, expect, it, vi } from "vitest";
import { declareClaim, login, logout } from "./actions";

const store = vi.hoisted(() => ({
  get: vi.fn(() => ({ value: "secret" })),
  set: vi.fn(),
  delete: vi.fn(),
}));
const redirect = vi.hoisted(() =>
  vi.fn((url: string) => {
    throw new Error(`REDIRECT ${url}`);
  }),
);

vi.mock("server-only", () => ({}));
vi.mock("next/navigation", () => ({ redirect }));
vi.mock("next/headers", () => ({
  cookies: async () => store,
  headers: async () => new Headers(),
}));

function form(fields: Record<string, string>): FormData {
  const data = new FormData();

  for (const [key, value] of Object.entries(fields)) {
    data.set(key, value);
  }

  return data;
}

function respond(status: number, body: unknown = {}): void {
  vi.stubGlobal(
    "fetch",
    vi.fn().mockResolvedValue(new Response(JSON.stringify(body), { status })),
  );
}

const credentials = form({ email: "a@example.be", password: "secret" });

describe("login", () => {
  beforeEach(() => {
    store.set.mockClear();
    store.delete.mockClear();
  });

  it("keeps the token in an httpOnly cookie, out of reach of scripts", async () => {
    respond(201, { token: "1|abc" });

    await expect(login({}, credentials)).rejects.toThrow("REDIRECT /contracts");

    expect(store.set).toHaveBeenCalledWith(
      "bd_session",
      "1|abc",
      expect.objectContaining({ httpOnly: true, sameSite: "lax", path: "/" }),
    );
  });

  it("returns validation errors, and sets no cookie", async () => {
    respond(422, { errors: { email: ["Identifiants incorrects."] } });

    await expect(login({}, credentials)).resolves.toEqual({
      errors: { email: ["Identifiants incorrects."] },
    });
    expect(store.set).not.toHaveBeenCalled();
  });

  it.each([
    [403, "espace courtier"],
    [429, "Trop de tentatives"],
    [500, "La connexion a échoué"],
  ])("explains a %i", async (status, text) => {
    respond(status);

    const state = await login({}, credentials);

    expect(state.message).toContain(text);
  });

  it("explains when the API is unreachable", async () => {
    vi.stubGlobal("fetch", vi.fn().mockRejectedValue(new Error("down")));

    const state = await login({}, credentials);

    expect(state.message).toContain("ne répond pas");
  });
});

describe("logout", () => {
  it("signs this browser out even if the API call fails", async () => {
    vi.stubGlobal("fetch", vi.fn().mockRejectedValue(new Error("down")));

    await expect(logout()).rejects.toThrow("REDIRECT /");

    expect(store.delete).toHaveBeenCalledWith("bd_session");
  });
});

describe("declareClaim", () => {
  const claim = form({
    contract_id: "12",
    occurred_on: "2026-11-01",
    description: "Pare-chocs abîmé.",
  });

  it("sends the declaration and shows its reference", async () => {
    respond(201, { data: { reference: "SIN-2026-000001" } });

    await expect(declareClaim({}, claim)).rejects.toThrow(
      "REDIRECT /claims?declared=SIN-2026-000001",
    );

    const init = vi.mocked(fetch).mock.calls[0]![1];
    expect(JSON.parse(String(init?.body))).toMatchObject({ contract_id: 12 });
  });

  it("returns validation errors", async () => {
    respond(422, { errors: { description: ["Obligatoire."] } });

    await expect(declareClaim({}, claim)).resolves.toEqual({
      errors: { description: ["Obligatoire."] },
    });
  });

  it("explains other failures", async () => {
    respond(500);

    const state = await declareClaim({}, claim);

    expect(state.message).toContain("La déclaration a échoué");
  });

  it("lets an expired session redirect to the login page", async () => {
    respond(401);

    await expect(declareClaim({}, claim)).rejects.toThrow(
      "REDIRECT /login?expired=1",
    );
  });
});
