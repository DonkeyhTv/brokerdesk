import { afterEach, describe, expect, it, vi } from "vitest";
import { cleanup, render, screen } from "@testing-library/react";
import { ApiStatus } from "./ApiStatus";

describe("ApiStatus", () => {
  afterEach(() => {
    cleanup();
    vi.unstubAllGlobals();
  });

  it("shows the status returned by the API", async () => {
    vi.stubGlobal(
      "fetch",
      vi.fn().mockResolvedValue({ ok: true, json: async () => ({ status: "ok" }) }),
    );

    render(<ApiStatus />);

    expect(await screen.findByText("API : ok")).toBeTruthy();
  });

  it("shows an error when the API is unreachable", async () => {
    vi.stubGlobal("fetch", vi.fn().mockRejectedValue(new Error("down")));

    render(<ApiStatus />);

    expect(await screen.findByText("API : error")).toBeTruthy();
  });
});
