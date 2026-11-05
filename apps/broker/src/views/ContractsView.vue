<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { buildUrl } from '@/api/http'
import type { Contract } from '@/api/types'
import { useList } from '@/composables/useList'
import { daysUntil, formatDate, formatMoney } from '@/utils/format'
import { CONTRACT_STATUSES, CONTRACT_TYPES } from '@/utils/labels'
import AlertMessage from '@/components/AlertMessage.vue'
import PageHeader from '@/components/PageHeader.vue'
import PaginationBar from '@/components/PaginationBar.vue'
import SortHeader from '@/components/SortHeader.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import TableState from '@/components/TableState.vue'

const route = useRoute()

const list = useList<Contract>(
  '/contracts',
  {
    search: '',
    status: typeof route.query.status === 'string' ? route.query.status : 'active',
    type: '',
    expires_within:
      typeof route.query.expires_within === 'string' ? route.query.expires_within : '',
  },
  'expires_on',
)

const exportUrl = computed(() => {
  const query = list.query()
  delete query.page

  return buildUrl('/contracts/export', query)
})
</script>

<template>
  <PageHeader title="Contrats" subtitle="Polices, échéances et primes">
    <a :href="exportUrl" class="btn-secondary">Exporter en CSV</a>
    <RouterLink :to="{ name: 'contract-new' }" class="btn-primary">Nouveau contrat</RouterLink>
  </PageHeader>

  <div class="mb-4 flex flex-wrap gap-3">
    <input
      v-model="list.filters.search"
      type="search"
      class="input max-w-xs"
      placeholder="N° de police ou client"
      aria-label="Rechercher un contrat"
    />
    <select v-model="list.filters.status" class="input w-auto" aria-label="Filtrer par statut">
      <option value="">Tous les statuts</option>
      <option v-for="(label, value) in CONTRACT_STATUSES" :key="value" :value="value">
        {{ label }}
      </option>
    </select>
    <select v-model="list.filters.type" class="input w-auto" aria-label="Filtrer par branche">
      <option value="">Toutes les branches</option>
      <option v-for="(label, value) in CONTRACT_TYPES" :key="value" :value="value">
        {{ label }}
      </option>
    </select>
    <select
      v-model="list.filters.expires_within"
      class="input w-auto"
      aria-label="Filtrer par échéance"
    >
      <option value="">Toutes les échéances</option>
      <option value="30">Sous 30 jours</option>
      <option value="60">Sous 60 jours</option>
      <option value="90">Sous 90 jours</option>
    </select>
  </div>

  <AlertMessage :message="list.error.value" />

  <div class="card overflow-hidden">
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-slate-200">
        <thead class="bg-slate-50">
          <tr>
            <SortHeader
              label="Police"
              field="policy_number"
              :sort="list.sort.value"
              @toggle="list.toggleSort"
            />
            <th scope="col" class="table-head">Client</th>
            <th scope="col" class="table-head">Branche</th>
            <th scope="col" class="table-head">Assureur</th>
            <SortHeader
              label="Échéance"
              field="expires_on"
              :sort="list.sort.value"
              @toggle="list.toggleSort"
            />
            <th scope="col" class="table-head">Statut</th>
            <SortHeader
              label="Prime"
              field="annual_premium_cents"
              :sort="list.sort.value"
              @toggle="list.toggleSort"
            />
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <TableState
            :is-loading="list.isLoading.value"
            :is-empty="list.rows.value.length === 0"
            :columns="7"
            empty-message="Aucun contrat ne correspond."
          />
          <tr v-for="contract in list.rows.value" :key="contract.id" class="hover:bg-slate-50">
            <td class="table-cell whitespace-nowrap">
              <RouterLink :to="{ name: 'contract', params: { id: contract.id } }" class="link">
                {{ contract.policy_number }}
              </RouterLink>
            </td>
            <td class="table-cell">{{ contract.client?.name }}</td>
            <td class="table-cell">{{ CONTRACT_TYPES[contract.type] }}</td>
            <td class="table-cell">{{ contract.insurer?.name }}</td>
            <td class="table-cell whitespace-nowrap tabular-nums">
              {{ formatDate(contract.expires_on) }}
              <span
                v-if="
                  contract.status === 'active' &&
                  daysUntil(contract.expires_on) >= 0 &&
                  daysUntil(contract.expires_on) <= 90
                "
                class="block text-xs text-slate-500"
              >
                dans {{ daysUntil(contract.expires_on) }} j
              </span>
            </td>
            <td class="table-cell"><StatusBadge :status="contract.status" /></td>
            <td class="table-cell text-right whitespace-nowrap tabular-nums">
              {{ formatMoney(contract.annual_premium_cents) }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <PaginationBar v-model="list.page.value" :meta="list.meta.value" />
  </div>
</template>
