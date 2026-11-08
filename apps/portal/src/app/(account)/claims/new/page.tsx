import type { Metadata } from "next";
import Link from "next/link";
import { ClaimForm } from "@/components/ClaimForm";
import { getMessages } from "@/i18n/server";
import { api } from "@/lib/api";
import type { Contract, Paginated } from "@/lib/types";

export async function generateMetadata(): Promise<Metadata> {
  return { title: (await getMessages()).claims.declare };
}

export default async function NewClaimPage({
  searchParams,
}: PageProps<"/claims/new">) {
  const { contract } = await searchParams;
  const [{ data: contracts }, t] = await Promise.all([
    api<Paginated<Contract>>("/contracts?filter[status]=active&per_page=100"),
    getMessages(),
  ]);

  return (
    <>
      <Link href="/claims" className="link text-sm">
        {t.claims.back}
      </Link>
      <h1 className="mt-3 text-2xl font-semibold tracking-tight">
        {t.claims.declare}
      </h1>
      <p className="mt-1 text-sm text-slate-500">{t.claims.newIntro}</p>
      <ClaimForm
        contracts={contracts}
        initialContractId={typeof contract === "string" ? contract : ""}
      />
    </>
  );
}
