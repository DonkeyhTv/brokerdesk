import { describe, expect, it } from "vitest";
import { NextRequest } from "next/server";
import { proxy } from "./proxy";

describe("proxy", () => {
  it("sends visitors without a session to the login page", () => {
    const response = proxy(new NextRequest("http://localhost:3000/contracts"));

    expect(response.status).toBe(307);
    expect(response.headers.get("location")).toBe(
      "http://localhost:3000/login",
    );
  });

  it("lets a session through", () => {
    const request = new NextRequest("http://localhost:3000/contracts", {
      headers: { cookie: "bd_session=abc" },
    });

    expect(proxy(request).headers.get("x-middleware-next")).toBe("1");
  });
});
