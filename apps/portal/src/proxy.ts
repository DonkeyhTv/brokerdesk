import { NextResponse, type NextRequest } from "next/server";
import { SESSION_COOKIE } from "@/lib/constants";

/**
 * A first, cheap check: private pages need a session cookie. The API still
 * decides on every call whether the token inside is valid.
 */
export function proxy(request: NextRequest) {
  if (!request.cookies.has(SESSION_COOKIE)) {
    return NextResponse.redirect(new URL("/login", request.url));
  }

  return NextResponse.next();
}

export const config = {
  matcher: ["/contracts/:path*", "/claims/:path*", "/documents/:path*"],
};
