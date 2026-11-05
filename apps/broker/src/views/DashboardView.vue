<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { api } from '@/api/http'
import type { Dashboard } from '@/api/types'
import { useAuthStore } from '@/stores/auth'
import { daysUntil, formatDate, formatMoney, formatWholeMoney } from '@/utils/format'
import { CONTRACT_TYPES } from '@/utils/labels'
import AlertMessage from '@/components/AlertMessage.vue'
import PageHeader from '@/components/PageHeader.vue'
import PremiumChart from '@/components/PremiumChart.vue'

const auth = useAuthStore()
const dashboard = ref<Dashboard | null>(null)
const error = ref<string | null>(null)

onMounted(async () => {
  try {
    dashboard.value = await api<Dashboard>('/dashboard')
  } catch {
    error.value = 'Le tableau de bord n’a pas pu être chargé.'
  }
})
</script>

<template>
  <PageHeader
    title="Tableau de bord"
    :subtitle="auth.isAdmin ? `Tout le cabinet ${auth.user?.brokerage.name}` : 'Votre portefeuille'"
  />

  <AlertMessage :message="error" />

  <template v-if="dashboard">
    <section aria-labelledby="renewals-title">
      <h2 id="renewals-title" class="mb-3 text-sm font-semibold text-slate-700">
        Contrats à échéance
      </h2>
      <div class="grid gap-4 sm:grid-cols-3">
        <RouterLink
          v-for="tile in [
            { label: 'Dans 30 jours', days: 30, value: dashboard.expiring.within_30 },
            { label: 'Entre 31 et 60 jours', days: 60, value: dashboard.expiring.from_31_to_60 },
            { label: 'Entre 61 et 90 jours', days: 90, value: dashboard.expiring.from_61_to_90 },
          ]"
          :key="tile.days"
          :to="{ name: 'contracts', query: { expires_within: tile.days } }"
          class="card block p-5 transition-shadow hover:shadow-md"
        >
          <p class="text-sm text-slate-500">{{ tile.label }}</p>
          <p class="mt-1 text-3xl font-semibold text-slate-900 tabular-nums">{{ tile.value }}</p>
        </RouterLink>
      </div>
    </section>

    <section class="mt-4 grid gap-4 sm:grid-cols-3" aria-label="Chiffres clés">
      <RouterLink
        :to="{ name: 'claims', query: { status: 'open' } }"
        class="card block p-5 transition-shadow hover:shadow-md"
      >
        <p class="text-sm text-slate-500">Sinistres en cours</p>
        <p class="mt-1 text-3xl font-semibold text-slate-900 tabular-nums">
          {{ dashboard.open_claims }}
        </p>
      </RouterLink>
      <div class="card p-5">
        <p class="text-sm text-slate-500">Contrats actifs</p>
        <p class="mt-1 text-3xl font-semibold text-slate-900 tabular-nums">
          {{ dashboard.active_contracts }}
        </p>
      </div>
      <div class="card p-5">
        <p class="text-sm text-slate-500">Volume de primes annuel</p>
        <p class="mt-1 text-3xl font-semibold text-slate-900 tabular-nums">
          {{ formatWholeMoney(dashboard.annual_premium_cents) }}
        </p>
      </div>
    </section>

    <div class="mt-4 grid gap-4 lg:grid-cols-2">
      <PremiumChart :rows="dashboard.premium_by_type" />

      <section class="card p-5" aria-labelledby="next-title">
        <h2 id="next-title" class="text-base font-semibold text-slate-900">Prochaines échéances</h2>
        <p v-if="dashboard.next_expiries.length === 0" class="mt-6 text-sm text-slate-500">
          Aucune échéance à venir.
        </p>
        <ul v-else class="mt-3 divide-y divide-slate-100">
          <li v-for="contract in dashboard.next_expiries" :key="contract.id">
            <RouterLink
              :to="{ name: 'contract', params: { id: contract.id } }"
              class="flex items-center justify-between gap-3 py-2.5 hover:bg-slate-50"
            >
              <span class="min-w-0">
                <span class="block truncate text-sm font-medium text-slate-900">
                  {{ contract.client?.name }}
                </span>
                <span class="block truncate text-xs text-slate-500">
                  {{ CONTRACT_TYPES[contract.type] }} · {{ contract.policy_number }} ·
                  {{ formatMoney(contract.annual_premium_cents) }}
                </span>
              </span>
              <span class="shrink-0 text-right">
                <span class="block text-sm text-slate-900 tabular-nums">
                  {{ formatDate(contract.expires_on) }}
                </span>
                <span class="block text-xs text-slate-500">
                  dans {{ daysUntil(contract.expires_on) }} j
                </span>
              </span>
            </RouterLink>
          </li>
        </ul>
      </section>
    </div>
  </template>
</template>
