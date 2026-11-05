<script setup lang="ts">
import { RouterLink, useRoute } from 'vue-router'
import type { Claim } from '@/api/types'
import { useList } from '@/composables/useList'
import { formatDate, formatMoney } from '@/utils/format'
import { CLAIM_STATUSES } from '@/utils/labels'
import AlertMessage from '@/components/AlertMessage.vue'
import PageHeader from '@/components/PageHeader.vue'
import PaginationBar from '@/components/PaginationBar.vue'
import SortHeader from '@/components/SortHeader.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import TableState from '@/components/TableState.vue'

const route = useRoute()

const list = useList<Claim>(
  '/claims',
  { search: '', status: typeof route.query.status === 'string' ? route.query.status : '' },
  '-occurred_on',
)
</script>

<template>
  <PageHeader title="Sinistres" subtitle="Déclarations et suivi">
    <RouterLink :to="{ name: 'claim-new' }" class="btn-primary">Déclarer un sinistre</RouterLink>
  </PageHeader>

  <div class="mb-4 flex flex-wrap gap-3">
    <input
      v-model="list.filters.search"
      type="search"
      class="input max-w-xs"
      placeholder="Référence"
      aria-label="Rechercher un sinistre"
    />
    <select v-model="list.filters.status" class="input w-auto" aria-label="Filtrer par statut">
      <option value="">Tous les statuts</option>
      <option v-for="(label, value) in CLAIM_STATUSES" :key="value" :value="value">
        {{ label }}
      </option>
    </select>
  </div>

  <AlertMessage :message="list.error.value" />

  <div class="card overflow-hidden">
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-slate-200">
        <thead class="bg-slate-50">
          <tr>
            <SortHeader
              label="Référence"
              field="reference"
              :sort="list.sort.value"
              @toggle="list.toggleSort"
            />
            <th scope="col" class="table-head">Client</th>
            <th scope="col" class="table-head">Police</th>
            <SortHeader
              label="Survenu le"
              field="occurred_on"
              :sort="list.sort.value"
              @toggle="list.toggleSort"
            />
            <SortHeader
              label="Statut"
              field="status"
              :sort="list.sort.value"
              @toggle="list.toggleSort"
            />
            <th scope="col" class="table-head text-right">Estimation</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <TableState
            :is-loading="list.isLoading.value"
            :is-empty="list.rows.value.length === 0"
            :columns="6"
            empty-message="Aucun sinistre ne correspond."
          />
          <tr v-for="claim in list.rows.value" :key="claim.id" class="hover:bg-slate-50">
            <td class="table-cell whitespace-nowrap">
              <RouterLink :to="{ name: 'claim', params: { id: claim.id } }" class="link">
                {{ claim.reference }}
              </RouterLink>
            </td>
            <td class="table-cell">{{ claim.contract?.client?.name }}</td>
            <td class="table-cell whitespace-nowrap">{{ claim.contract?.policy_number }}</td>
            <td class="table-cell tabular-nums">{{ formatDate(claim.occurred_on) }}</td>
            <td class="table-cell"><StatusBadge :status="claim.status" /></td>
            <td class="table-cell text-right whitespace-nowrap tabular-nums">
              {{ formatMoney(claim.estimated_amount_cents) }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <PaginationBar v-model="list.page.value" :meta="list.meta.value" />
  </div>
</template>
