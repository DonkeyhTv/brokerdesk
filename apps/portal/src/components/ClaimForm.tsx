"use client";

import { useActionState } from "react";
import { declareClaim, type FormState } from "@/lib/actions";
import { CONTRACT_TYPES } from "@/lib/labels";
import type { Contract } from "@/lib/types";
import { FieldError } from "./FieldError";

const initialState: FormState = {};

export function ClaimForm({
  contracts,
  initialContractId,
}: {
  contracts: Contract[];
  initialContractId: string;
}) {
  const [state, action, isPending] = useActionState(declareClaim, initialState);

  if (contracts.length === 0) {
    return (
      <p className="card mt-6 p-8 text-center text-sm text-slate-500">
        Un sinistre se déclare sur un contrat actif, et vous n&apos;en avez
        aucun pour le moment.
      </p>
    );
  }

  return (
    <form action={action} className="card mt-6 space-y-5 p-6">
      {state.message && (
        <p
          role="alert"
          className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"
        >
          {state.message}
        </p>
      )}

      <div>
        <label
          htmlFor="contract_id"
          className="mb-1 block text-sm font-medium text-slate-700"
        >
          Contrat concerné
        </label>
        <select
          id="contract_id"
          name="contract_id"
          className="input"
          defaultValue={initialContractId}
        >
          <option value="">Choisir un contrat</option>
          {contracts.map((contract) => (
            <option key={contract.id} value={contract.id}>
              {CONTRACT_TYPES[contract.type]} – {contract.policy_number}
            </option>
          ))}
        </select>
        <FieldError name="contract_id" errors={state.errors} />
      </div>

      <div>
        <label
          htmlFor="occurred_on"
          className="mb-1 block text-sm font-medium text-slate-700"
        >
          Date du sinistre
        </label>
        <input
          id="occurred_on"
          name="occurred_on"
          type="date"
          className="input max-w-xs"
        />
        <FieldError name="occurred_on" errors={state.errors} />
      </div>

      <div>
        <label
          htmlFor="description"
          className="mb-1 block text-sm font-medium text-slate-700"
        >
          Que s&apos;est-il passé ?
        </label>
        <textarea
          id="description"
          name="description"
          rows={6}
          className="input"
        />
        <FieldError name="description" errors={state.errors} />
      </div>

      <button type="submit" className="btn-primary" disabled={isPending}>
        {isPending ? "Envoi…" : "Envoyer ma déclaration"}
      </button>
    </form>
  );
}
