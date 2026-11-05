import { afterEach, describe, expect, it, vi } from "vitest";
import { cleanup, render, screen } from "@testing-library/react";
import { LoginForm } from "./LoginForm";

vi.mock("@/lib/actions", () => ({ login: vi.fn() }));

const demo = {
  password: "password",
  accounts: [
    { label: "Courtier", app: "broker" as const, email: "broker@example.be" },
    {
      label: "Client société",
      app: "portal" as const,
      email: "company@example.be",
    },
  ],
};

describe("LoginForm", () => {
  afterEach(cleanup);

  it("offers the demo accounts of this application only", () => {
    render(<LoginForm demo={demo} />);

    expect(screen.getByRole("button", { name: "Client société" })).toBeTruthy();
    expect(screen.queryByRole("button", { name: "Courtier" })).toBeNull();
  });

  it("offers nothing outside demo mode", () => {
    render(<LoginForm demo={null} />);

    expect(screen.queryByText(/Vous évaluez/)).toBeNull();
    expect(screen.getByRole("button", { name: "Se connecter" })).toBeTruthy();
  });

  it("explains an expired session", () => {
    render(<LoginForm demo={null} notice="Votre session a expiré." />);

    expect(screen.getByRole("alert").textContent).toBe(
      "Votre session a expiré.",
    );
  });
});
