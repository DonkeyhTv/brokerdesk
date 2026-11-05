"use client";

export default function ErrorPage({ reset }: { reset: () => void }) {
  return (
    <main className="flex flex-1 flex-col items-center justify-center px-4 py-20 text-center">
      <h1 className="text-2xl font-semibold text-slate-900">
        Une erreur est survenue
      </h1>
      <p className="mt-2 text-sm text-slate-500">
        Le service ne répond pas pour le moment.
      </p>
      <button type="button" className="btn-primary mt-6" onClick={reset}>
        Réessayer
      </button>
    </main>
  );
}
