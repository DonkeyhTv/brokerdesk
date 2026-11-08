<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { buildUrl } from '@/api/http'
import type { Contract } from '@/api/types'
import { useList } from '@/composables/useList'
import { daysUntil, formatDate, formatMoney } from '@/utils/format'
import { t } from '@/i18n'
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
  <PageHeader :title="t.contracts.title" :subtitle="t.contracts.subtitle">
    <a :href="exportUrl" class="btn-secondary">{{ t.contracts.export }}</a>
    <RouterLink :to="{ name: 'contract-new' }" class="btn-primary">{{
      t.contracts.new
    }}</RouterLink>
  </PageHeader>

  <div class="mb-4 flex flex-wrap gap-3">
    <input
      v-model="list.filters.search"
      type="search"
      class="input max-w-xs"
      :placeholder="t.contracts.searchPlaceholder"
      :aria-label="t.contracts.searchLabel"
    />
    <select
      v-model="list.filters.status"
      class="input w-auto"
      :aria-label="t.contracts.statusFilter"
    >
      <option value="">{{ t.contracts.allStatuses }}</option>
      <option v-for="(label, value) in t.labels.contractStatuses" :key="value" :value="value">
        {{ label }}
      </option>
    </select>
    <select v-model="list.filters.type" class="input w-auto" :aria-label="t.contracts.typeFilter">
      <option value="">{{ t.contracts.allTypes }}</option>
      <option v-for="(label, value) in t.labels.contractTypes" :key="value" :value="value">
        {{ label }}
      </option>
    </select>
    <select
      v-model="list.filters.expires_within"
      class="input w-auto"
      :aria-label="t.contracts.expiryFilter"
    >
      <option value="">{{ t.contracts.allExpiries }}</option>
      <option v-for="days in [30, 60, 90]" :key="days" :value="String(days)">
        {{ t.contracts.within(days) }}
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
              :label="t.contracts.policy"
              field="policy_number"
              :sort="list.sort.value"
              @toggle="list.toggleSort"
            />
            <th scope="col" class="table-head">{{ t.contracts.client }}</th>
            <th scope="col" class="table-head">{{ t.contracts.type }}</th>
            <th scope="col" class="table-head">{{ t.contracts.insurer }}</th>
            <SortHeader
              :label="t.contracts.expiry"
              field="expires_on"
              :sort="list.sort.value"
              @toggle="list.toggleSort"
            />
            <th scope="col" class="table-head">{{ t.contracts.status }}</th>
            <SortHeader
              :label="t.contracts.premium"
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
            :empty-message="t.contracts.empty"
          />
          <tr v-for="contract in list.rows.value" :key="contract.id" class="hover:bg-slate-50">
            <td class="table-cell whitespace-nowrap">
              <RouterLink :to="{ name: 'contract', params: { id: contract.id } }" class="link">
                {{ contract.policy_number }}
              </RouterLink>
            </td>
            <td class="table-cell">{{ contract.client?.name }}</td>
            <td class="table-cell">{{ t.labels.contractTypes[contract.type] }}</td>
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
                {{ t.common.inDays(daysUntil(contract.expires_on)) }}
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
