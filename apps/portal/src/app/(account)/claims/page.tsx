import type { Metadata } from "next";
import Link from "next/link";
import { StatusBadge } from "@/components/StatusBadge";
import { getLocale, getMessages } from "@/i18n/server";
import { api } from "@/lib/api";
import { formatDate, formatMoney } from "@/lib/format";
import type { Claim, Paginated } from "@/lib/types";

export async function generateMetadata(): Promise<Metadata> {
  return { title: (await getMessages()).claims.title };
}

export default async function ClaimsPage({
  searchParams,
}: PageProps<"/claims">) {
  const { declared } = await searchParams;
  const [{ data: claims }, locale, t] = await Promise.all([
    api<Paginated<Claim>>("/claims?per_page=100"),
    getLocale(),
    getMessages(),
  ]);

  return (
    <>
      <div className="flex flex-wrap items-start justify-between gap-3">
        <div>
          <h1 className="text-2xl font-semibold tracking-tight">
            {t.claims.title}
          </h1>
          <p className="mt-1 text-sm text-slate-500">
            {t.claims.count(claims.length)}
          </p>
        </div>
        <Link href="/claims/new" className="btn-primary">
          {t.claims.declare}
        </Link>
      </div>

      {typeof declared === "string" && (
        <p
          role="status"
          className="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800"
        >
          {t.claims.declared(declared)}
        </p>
      )}

      {claims.length === 0 ? (
        <p className="card mt-6 p-8 text-center text-sm text-slate-500">
          {t.claims.empty}
        </p>
      ) : (
        <ul className="mt-6 space-y-4">
          {claims.map((claim) => (
            <li key={claim.id} className="card p-5">
              <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                  <h2 className="text-base font-semibold text-slate-900">
                    {claim.reference}
                  </h2>
                  <p className="mt-0.5 text-sm text-slate-500">
                    {claim.contract
                      ? `${t.labels.contractTypes[claim.contract.type]} · ${claim.contract.policy_number}`
                      : null}
                    {" · "}
                    {t.claims.occurredOn(formatDate(claim.occurred_on))}
                  </p>
                </div>
                <StatusBadge status={claim.status} />
              </div>
              <p className="mt-3 text-sm whitespace-pre-line text-slate-700">
                {claim.description}
              </p>
              {claim.settled_amount_cents !== null && (
                <p className="mt-3 text-sm font-medium text-slate-900">
                  {t.claims.settledAmount(
                    formatMoney(claim.settled_amount_cents, locale),
                  )}
                </p>
              )}
            </li>
          ))}
        </ul>
      )}
    </>
  );
}
