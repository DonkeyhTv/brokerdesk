import { afterEach, describe, expect, it } from "vitest";
import { cleanup, render, screen } from "@testing-library/react";
import { TechStack } from "./TechStack";

describe("TechStack", () => {
  afterEach(cleanup);

  it("lists every layer and marks the one in use", () => {
    render(<TechStack current="portal" />);

    expect(screen.getByText("Next.js 16")).toBeTruthy();
    expect(screen.getByText("Laravel 13")).toBeTruthy();
    expect(screen.getByText("PostgreSQL 17")).toBeTruthy();
    expect(
      screen.getByText("vous êtes ici").closest("dt")?.textContent,
    ).toContain("Portail client");
  });

  it("leaves out the explanations when compact", () => {
    render(<TechStack compact />);

    expect(screen.queryByText(/clés étrangères composites/)).toBeNull();
    expect(screen.queryByText("vous êtes ici")).toBeNull();
  });
});
