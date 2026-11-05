<script setup lang="ts">
import { computed } from 'vue'
import { CONTRACT_TYPES } from '@/utils/labels'
import { formatWholeMoney } from '@/utils/format'
import type { Dashboard } from '@/api/types'

const props = defineProps<{ rows: Dashboard['premium_by_type'] }>()

const maximum = computed(() => Math.max(1, ...props.rows.map((row) => row.annual_premium_cents)))
</script>

<template>
  <section class="card p-5" aria-labelledby="premium-title">
    <h2 id="premium-title" class="text-base font-semibold text-slate-900">
      Primes annuelles par branche
    </h2>
    <p class="mt-0.5 text-sm text-slate-500">Contrats actifs</p>

    <p v-if="rows.length === 0" class="mt-6 text-sm text-slate-500">Aucun contrat actif.</p>

    <ul v-else class="mt-5 space-y-1">
      <li
        v-for="row in rows"
        :key="row.type"
        class="group relative grid grid-cols-[8.5rem_1fr_5.5rem] items-center gap-3 rounded-md px-1 py-1.5 hover:bg-slate-50 focus-visible:bg-slate-50"
        tabindex="0"
      >
        <span class="truncate text-sm text-slate-700">{{ CONTRACT_TYPES[row.type] }}</span>
        <span class="block h-2.5" aria-hidden="true">
          <span
            class="block h-full rounded-r bg-brand-600"
            :style="{ width: `${(row.annual_premium_cents / maximum) * 100}%` }"
          />
        </span>
        <span class="text-right text-sm font-medium text-slate-900 tabular-nums">
          {{ formatWholeMoney(row.annual_premium_cents) }}
        </span>
        <span
          role="tooltip"
          class="pointer-events-none absolute -top-8 left-36 z-10 hidden rounded-md bg-slate-900 px-2.5 py-1 text-xs whitespace-nowrap text-white group-hover:block group-focus-visible:block"
        >
          {{ row.contracts }} contrat{{ row.contracts > 1 ? 's' : '' }} ·
          {{ formatWholeMoney(row.annual_premium_cents) }}
        </span>
      </li>
    </ul>
  </section>
</template>
