<script setup lang="ts">
import { RouterLink, useRoute } from 'vue-router'
import type { Claim } from '@/api/types'
import { useList } from '@/composables/useList'
import { formatDate, formatMoney } from '@/utils/format'
import { t } from '@/i18n'
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
  <PageHeader :title="t.claims.title" :subtitle="t.claims.subtitle">
    <RouterLink :to="{ name: 'claim-new' }" class="btn-primary">{{ t.claims.declare }}</RouterLink>
  </PageHeader>

  <div class="mb-4 flex flex-wrap gap-3">
    <input
      v-model="list.filters.search"
      type="search"
      class="input max-w-xs"
      :placeholder="t.claims.searchPlaceholder"
      :aria-label="t.claims.searchLabel"
    />
    <select v-model="list.filters.status" class="input w-auto" :aria-label="t.claims.statusFilter">
      <option value="">{{ t.claims.allStatuses }}</option>
      <option v-for="(label, value) in t.labels.claimStatuses" :key="value" :value="value">
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
              :label="t.claims.reference"
              field="reference"
              :sort="list.sort.value"
              @toggle="list.toggleSort"
            />
            <th scope="col" class="table-head">{{ t.claims.client }}</th>
            <th scope="col" class="table-head">{{ t.claims.policy }}</th>
            <SortHeader
              :label="t.claims.occurredOn"
              field="occurred_on"
              :sort="list.sort.value"
              @toggle="list.toggleSort"
            />
            <SortHeader
              :label="t.claims.status"
              field="status"
              :sort="list.sort.value"
              @toggle="list.toggleSort"
            />
            <th scope="col" class="table-head text-right">{{ t.claims.estimate }}</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <TableState
            :is-loading="list.isLoading.value"
            :is-empty="list.rows.value.length === 0"
            :columns="6"
            :empty-message="t.claims.empty"
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
