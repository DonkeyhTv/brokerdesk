import Link from "next/link";

export default function NotFound() {
  return (
    <main className="flex flex-1 flex-col items-center justify-center px-4 py-20 text-center">
      <h1 className="text-2xl font-semibold text-slate-900">
        Page introuvable
      </h1>
      <p className="mt-2 text-sm text-slate-500">
        Cette page n&apos;existe pas, ou vous n&apos;y avez pas accès.
      </p>
      <Link href="/contracts" className="btn-primary mt-6">
        Retour à mes contrats
      </Link>
    </main>
  );
}
