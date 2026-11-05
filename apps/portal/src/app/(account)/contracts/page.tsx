import type { Metadata } from "next";
import Link from "next/link";
import { StatusBadge } from "@/components/StatusBadge";
import { api } from "@/lib/api";
import { formatDate, formatMoney } from "@/lib/format";
import { CONTRACT_TYPES } from "@/lib/labels";
import type { Contract, Paginated } from "@/lib/types";

export const metadata: Metadata = { title: "Mes contrats" };

export default async function ContractsPage() {
  const { data: contracts } = await api<Paginated<Contract>>(
    "/contracts?per_page=100",
  );

  return (
    <>
      <h1 className="text-2xl font-semibold tracking-tight">Mes contrats</h1>
      <p className="mt-1 text-sm text-slate-500">
        {contracts.length} contrat{contracts.length > 1 ? "s" : ""}
      </p>

      {contracts.length === 0 ? (
        <p className="card mt-6 p-8 text-center text-sm text-slate-500">
          Vous n&apos;avez pas encore de contrat.
        </p>
      ) : (
        <ul className="mt-6 grid gap-4 sm:grid-cols-2">
          {contracts.map((contract) => (
            <li key={contract.id}>
              <Link
                href={`/contracts/${contract.id}`}
                className="card block p-5 transition-shadow hover:shadow-md"
              >
                <div className="flex items-start justify-between gap-3">
                  <h2 className="text-base font-semibold text-slate-900">
                    {CONTRACT_TYPES[contract.type]}
                  </h2>
                  <StatusBadge status={contract.status} />
                </div>
                <p className="mt-1 text-sm text-slate-500">
                  {contract.insurer?.name} · {contract.policy_number}
                </p>
                <dl className="mt-4 grid grid-cols-2 gap-3 text-sm">
                  <div>
                    <dt className="text-xs text-slate-500">Échéance</dt>
                    <dd className="font-medium text-slate-900 tabular-nums">
                      {formatDate(contract.expires_on)}
                    </dd>
                  </div>
                  <div>
                    <dt className="text-xs text-slate-500">Prime annuelle</dt>
                    <dd className="font-medium text-slate-900 tabular-nums">
                      {formatMoney(contract.annual_premium_cents)}
                    </dd>
                  </div>
                </dl>
              </Link>
            </li>
          ))}
        </ul>
      )}
    </>
  );
}
