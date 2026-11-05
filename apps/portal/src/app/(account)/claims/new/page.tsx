import type { Metadata } from "next";
import Link from "next/link";
import { ClaimForm } from "@/components/ClaimForm";
import { api } from "@/lib/api";
import type { Contract, Paginated } from "@/lib/types";

export const metadata: Metadata = { title: "Déclarer un sinistre" };

export default async function NewClaimPage({
  searchParams,
}: PageProps<"/claims/new">) {
  const { contract } = await searchParams;
  const { data: contracts } = await api<Paginated<Contract>>(
    "/contracts?filter[status]=active&per_page=100",
  );

  return (
    <>
      <Link href="/claims" className="link text-sm">
        ← Mes sinistres
      </Link>
      <h1 className="mt-3 text-2xl font-semibold tracking-tight">
        Déclarer un sinistre
      </h1>
      <p className="mt-1 text-sm text-slate-500">
        Votre courtier reçoit votre déclaration et revient vers vous.
      </p>
      <ClaimForm
        contracts={contracts}
        initialContractId={typeof contract === "string" ? contract : ""}
      />
    </>
  );
}
