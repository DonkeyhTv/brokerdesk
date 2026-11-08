import { describe, expect, it } from "vitest";
import { messagesFor, negotiateLocale } from ".";

describe("negotiateLocale", () => {
  it.each([
    [undefined, "nl-BE,nl;q=0.9,fr;q=0.8", "nl"],
    [undefined, "fr-BE,fr;q=0.9,nl;q=0.8", "fr"],
    [undefined, "en-US,en;q=0.9,nl;q=0.5", "nl"],
    [undefined, "fr;q=0.3,nl;q=0.7", "nl"],
    [undefined, "de-DE", "fr"],
    [undefined, null, "fr"],
    ["nl", "fr-BE", "nl"],
    ["xx", "nl-BE", "nl"],
  ])(
    "with the cookie %s and the header %s, picks %s",
    (saved, header, expected) => {
      expect(negotiateLocale(saved, header)).toBe(expected);
    },
  );

  it("has a catalog for every language", () => {
    expect(messagesFor("fr").login.submit).toBe("Se connecter");
    expect(messagesFor("nl").login.submit).toBe("Aanmelden");
  });
});
