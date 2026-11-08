import Link from "next/link";
import { getMessages } from "@/i18n/server";

export default async function NotFound() {
  const { errors } = await getMessages();

  return (
    <main className="flex flex-1 flex-col items-center justify-center px-4 py-20 text-center">
      <h1 className="text-2xl font-semibold text-slate-900">
        {errors.notFoundTitle}
      </h1>
      <p className="mt-2 text-sm text-slate-500">{errors.notFoundText}</p>
      <Link href="/contracts" className="btn-primary mt-6">
        {errors.notFoundBack}
      </Link>
    </main>
  );
}
