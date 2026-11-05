import { describe, expect, it } from "vitest";
import { formatDate, formatFileSize, formatMoney } from "./format";

describe("format", () => {
  it("formats money the Belgian way", () => {
    expect(formatMoney(123456).replace(/\s/g, " ")).toBe("1 234,56 €");
  });

  it("never shifts a date across time zones", () => {
    expect(formatDate("2026-12-31")).toBe("31/12/2026");
  });

  it("shows readable file sizes", () => {
    expect(formatFileSize(10)).toBe("1 Ko");
    expect(formatFileSize(2.5 * 1024 * 1024)).toBe("2,5 Mo");
  });
});
