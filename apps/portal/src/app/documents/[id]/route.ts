import { NextResponse, type NextRequest } from "next/server";
import { apiFetch } from "@/lib/api";

/**
 * Downloads go through this server, because the browser never holds the API token.
 */
export async function GET(
  request: NextRequest,
  { params }: RouteContext<"/documents/[id]">,
) {
  const { id } = await params;

  if (!/^\d+$/.test(id)) {
    return new NextResponse("Not found", { status: 404 });
  }

  const upstream = await apiFetch(`/documents/${id}/download`);

  if (upstream.status === 401) {
    return NextResponse.redirect(new URL("/login?expired=1", request.url));
  }

  if (!upstream.ok) {
    return new NextResponse("Not found", { status: 404 });
  }

  return new NextResponse(upstream.body, {
    headers: {
      "Content-Type": "application/pdf",
      "Content-Disposition":
        upstream.headers.get("content-disposition") ?? "attachment",
      "Cache-Control": "private, no-store",
      "X-Content-Type-Options": "nosniff",
    },
  });
}
