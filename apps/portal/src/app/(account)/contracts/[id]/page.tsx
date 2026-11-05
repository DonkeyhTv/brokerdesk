import type { Metadata } from "next";
import Link from "next/link";
import { notFound } from "next/navigation";
import { StatusBadge } from "@/components/StatusBadge";
import { api, ApiError } from "@/lib/api";
import { formatDate, formatFileSize, formatMoney } from "@/lib/format";
import { CONTRACT_TYPES, PAYMENT_FREQUENCIES } from "@/lib/labels";
import type {
  Claim,
  Contract,
  ContractDocument,
  Paginated,
  Resource,
} from "@/lib/types";

export const metadata: Metadata = { title: "Mon contrat" };

async function load(id: string) {
  // The id ends up in an API path: only digits may get there.
  if (!/^\d+$/.test(id)) {
    notFound();
  }

  try {
    return await Promise.all([
      api<Resource<Contract>>(`/contracts/${id}`),
      api<{ data: ContractDocument[] }>(`/contracts/${id}/documents`),
      api<Paginated<Claim>>(`/claims?filter[contract_id]=${id}&per_page=100`),
    ]);
  } catch (error) {
    if (error instanceof ApiError && error.status === 404) {
      notFound();
    }

    throw error;
  }
}

export default async function ContractPage({
  params,
}: PageProps<"/contracts/[id]">) {
  const { id } = await params;
  const [{ data: contract }, { data: documents }, { data: claims }] =
    await load(id);

  return (
    <>
      <Link href="/contracts" className="link text-sm">
        ← Mes contrats
      </Link>

      <div className="mt-3 flex flex-wrap items-start justify-between gap-3">
        <div>
          <h1 className="text-2xl font-semibold tracking-tight">
            {CONTRACT_TYPES[contract.type]}
          </h1>
          <p className="mt-1 text-sm text-slate-500">
            {contract.insurer?.name} · {contract.policy_number}
          </p>
        </div>
        <StatusBadge status={contract.status} />
      </div>

      <dl className="card mt-6 grid gap-5 p-5 text-sm sm:grid-cols-3">
        {[
          ["Début", formatDate(contract.starts_on)],
          ["Échéance", formatDate(contract.expires_on)],
          ["Date limite de résiliation", formatDate(contract.notice_deadline)],
          ["Prime annuelle", formatMoney(contract.annual_premium_cents)],
          ["Fractionnement", PAYMENT_FREQUENCIES[contract.payment_frequency]],
        ].map(([label, value]) => (
          <div key={label}>
            <dt className="text-xs text-slate-500">{label}</dt>
            <dd className="mt-0.5 font-medium text-slate-900 tabular-nums">
              {value}
            </dd>
          </div>
        ))}
      </dl>

      <section className="mt-8" aria-labelledby="documents-title">
        <h2 id="documents-title" className="text-lg font-semibold">
          Documents
        </h2>
        <div className="card mt-3 divide-y divide-slate-100">
          {documents.length === 0 ? (
            <p className="p-6 text-center text-sm text-slate-500">
              Aucun document disponible.
            </p>
          ) : (
            documents.map((document) => (
              <div
                key={document.id}
                className="flex items-center justify-between gap-3 px-5 py-3"
              >
                {/* A plain link: the browser downloads the file, no client-side navigation. */}
                <a
                  href={`/documents/${document.id}`}
                  className="link truncate text-sm"
                >
                  {document.original_name}
                </a>
                <span className="shrink-0 text-xs text-slate-500">
                  {formatFileSize(document.size_bytes)}
                </span>
              </div>
            ))
          )}
        </div>
      </section>

      <section className="mt-8" aria-labelledby="claims-title">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <h2 id="claims-title" className="text-lg font-semibold">
            Sinistres
          </h2>
          {contract.status === "active" && (
            <Link
              href={`/claims/new?contract=${contract.id}`}
              className="btn-primary"
            >
              Déclarer un sinistre
            </Link>
          )}
        </div>
        <div className="card mt-3 divide-y divide-slate-100">
          {claims.length === 0 ? (
            <p className="p-6 text-center text-sm text-slate-500">
              Aucun sinistre sur ce contrat.
            </p>
          ) : (
            claims.map((claim) => (
              <div
                key={claim.id}
                className="flex items-center justify-between gap-3 px-5 py-3"
              >
                <div className="min-w-0">
                  <p className="text-sm font-medium text-slate-900">
                    {claim.reference}
                  </p>
                  <p className="truncate text-xs text-slate-500">
                    {claim.description}
                  </p>
                </div>
                <StatusBadge status={claim.status} />
              </div>
            ))
          )}
        </div>
      </section>
    </>
  );
}
