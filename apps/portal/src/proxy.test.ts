import { describe, expect, it } from "vitest";
import { NextRequest } from "next/server";
import { contentSecurityPolicy, proxy } from "./proxy";

describe("proxy", () => {
  it.each(["/contracts", "/contracts/12", "/claims/new", "/documents/3"])(
    "sends visitors without a session from %s to the login page",
    (path) => {
      const response = proxy(new NextRequest(`http://localhost:3000${path}`));

      expect(response.status).toBe(307);
      expect(response.headers.get("location")).toBe(
        "http://localhost:3000/login",
      );
    },
  );

  it("lets a session through, with a content security policy", () => {
    const request = new NextRequest("http://localhost:3000/contracts", {
      headers: { cookie: "bd_session=abc" },
    });
    const response = proxy(request);

    expect(response.headers.get("x-middleware-next")).toBe("1");
    expect(response.headers.get("content-security-policy")).toMatch(
      /script-src 'self' 'nonce-[A-Za-z0-9+/=]+' 'strict-dynamic'/,
    );
  });

  it("gives every page view its own nonce, public pages included", () => {
    const nonceOf = () =>
      proxy(new NextRequest("http://localhost:3000/login"))
        .headers.get("content-security-policy")
        ?.match(/'nonce-([^']+)'/)?.[1];

    const first = nonceOf();

    expect(first).toBeTruthy();
    expect(nonceOf()).not.toBe(first);
  });

  it("relaxes only what the development server needs, and upgrades insecure requests in production", () => {
    expect(contentSecurityPolicy("n", true)).toContain("'unsafe-eval'");
    expect(contentSecurityPolicy("n", false)).not.toContain("'unsafe-eval'");
    expect(contentSecurityPolicy("n", false)).not.toContain("'unsafe-inline'");
    expect(contentSecurityPolicy("n", false)).toContain(
      "style-src 'self' 'nonce-n'",
    );
    expect(contentSecurityPolicy("n", false)).toContain(
      "upgrade-insecure-requests",
    );
    expect(contentSecurityPolicy("n", false)).toContain(
      "frame-ancestors 'none'",
    );
  });
});
