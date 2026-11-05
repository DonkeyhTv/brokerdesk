import { afterEach, describe, expect, it, vi } from "vitest";
import { cleanup, render, screen } from "@testing-library/react";
import type { Contract } from "@/lib/types";
import { ClaimForm } from "./ClaimForm";

vi.mock("@/lib/actions", () => ({ declareClaim: vi.fn() }));

const contract = {
  id: 12,
  type: "motor",
  policy_number: "BD-1",
} as Contract;

describe("ClaimForm", () => {
  afterEach(cleanup);

  it("explains why a claim needs an active contract", () => {
    render(<ClaimForm contracts={[]} initialContractId="" />);

    expect(screen.getByText(/contrat actif/)).toBeTruthy();
    expect(screen.queryByRole("button")).toBeNull();
  });

  it("preselects the contract the visitor came from", () => {
    render(<ClaimForm contracts={[contract]} initialContractId="12" />);

    const select = screen.getByLabelText<HTMLSelectElement>("Contrat concerné");

    expect(select.value).toBe("12");
    expect(select.textContent).toContain("Auto – BD-1");
  });
});
