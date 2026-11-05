import type { Metadata } from "next";
import Link from "next/link";
import { StatusBadge } from "@/components/StatusBadge";
import { api } from "@/lib/api";
import { formatDate, formatMoney } from "@/lib/format";
import { CONTRACT_TYPES } from "@/lib/labels";
import type { Claim, Paginated } from "@/lib/types";

export const metadata: Metadata = { title: "Mes sinistres" };

export default async function ClaimsPage({
  searchParams,
}: PageProps<"/claims">) {
  const { declared } = await searchParams;
  const { data: claims } = await api<Paginated<Claim>>("/claims?per_page=100");

  return (
    <>
      <div className="flex flex-wrap items-start justify-between gap-3">
        <div>
          <h1 className="text-2xl font-semibold tracking-tight">
            Mes sinistres
          </h1>
          <p className="mt-1 text-sm text-slate-500">
            {claims.length} sinistre{claims.length > 1 ? "s" : ""}
          </p>
        </div>
        <Link href="/claims/new" className="btn-primary">
          Déclarer un sinistre
        </Link>
      </div>

      {typeof declared === "string" && (
        <p
          role="status"
          className="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800"
        >
          Votre sinistre a bien été déclaré sous la référence {declared}. Votre
          courtier revient vers vous.
        </p>
      )}

      {claims.length === 0 ? (
        <p className="card mt-6 p-8 text-center text-sm text-slate-500">
          Vous n&apos;avez déclaré aucun sinistre.
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
                      ? `${CONTRACT_TYPES[claim.contract.type]} · ${claim.contract.policy_number}`
                      : null}
                    {" · "}survenu le {formatDate(claim.occurred_on)}
                  </p>
                </div>
                <StatusBadge status={claim.status} />
              </div>
              <p className="mt-3 text-sm whitespace-pre-line text-slate-700">
                {claim.description}
              </p>
              {claim.settled_amount_cents !== null && (
                <p className="mt-3 text-sm font-medium text-slate-900">
                  Montant réglé : {formatMoney(claim.settled_amount_cents)}
                </p>
              )}
            </li>
          ))}
        </ul>
      )}
    </>
  );
}
