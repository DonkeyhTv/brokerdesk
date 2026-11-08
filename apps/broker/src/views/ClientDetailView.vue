<script setup lang="ts">
import { onMounted, ref, shallowRef } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { api, ApiError } from '@/api/http'
import type { Client, Contract, Paginated, Resource } from '@/api/types'
import { formatDate, formatDateTime, formatMoney } from '@/utils/format'
import { t } from '@/i18n'
import AlertMessage from '@/components/AlertMessage.vue'
import DetailItem from '@/components/DetailItem.vue'
import PageHeader from '@/components/PageHeader.vue'
import StatusBadge from '@/components/StatusBadge.vue'

const props = defineProps<{ id: string }>()
const router = useRouter()

const client = ref<Client | null>(null)
const contracts = shallowRef<Contract[]>([])
const error = ref<string | null>(null)
const invitation = ref<{ message: string; url: string | null } | null>(null)
const isInviting = ref(false)

onMounted(async () => {
  try {
    const [clientResponse, contractsResponse] = await Promise.all([
      api<Resource<Client>>(`/clients/${props.id}`),
      api<Paginated<Contract>>('/contracts', {
        query: { 'filter[client_id]': props.id, per_page: 100 },
      }),
    ])

    client.value = clientResponse.data
    contracts.value = contractsResponse.data
  } catch (caught) {
    error.value =
      caught instanceof ApiError && caught.status === 404
        ? t.value.clients.notFound
        : t.value.clients.loadFailed
  }
})

async function invite(): Promise<void> {
  isInviting.value = true
  error.value = null

  try {
    const response = await api<{ data: Client; invitation_url: string | null }>(
      `/clients/${props.id}/invitation`,
      { method: 'POST' },
    )

    client.value = response.data
    invitation.value = {
      message: t.value.clients.portal.sent(response.data.email),
      url: response.invitation_url,
    }
  } catch (caught) {
    // A conflict comes with the API's own explanation, in the interface's language.
    error.value =
      caught instanceof ApiError && caught.status === 409
        ? caught.message
        : t.value.clients.portal.failed
  } finally {
    isInviting.value = false
  }
}

async function remove(): Promise<void> {
  if (!window.confirm(t.value.clients.confirmDelete)) {
    return
  }

  try {
    await api(`/clients/${props.id}`, { method: 'DELETE' })
    await router.push({ name: 'clients' })
  } catch (caught) {
    error.value =
      caught instanceof ApiError && caught.status === 409
        ? t.value.clients.hasActiveContracts
        : caught instanceof ApiError && caught.status === 403
          ? t.value.clients.demoAccount
          : t.value.common.deleteFailed
  }
}
</script>

<template>
  <AlertMessage :message="error" />

  <template v-if="client">
    <PageHeader :title="client.name" :subtitle="t.labels.clientTypes[client.type]">
      <RouterLink :to="{ name: 'client-edit', params: { id: client.id } }" class="btn-secondary">
        {{ t.common.edit }}
      </RouterLink>
      <button type="button" class="btn-danger" @click="remove">{{ t.common.delete }}</button>
    </PageHeader>

    <dl class="card grid gap-5 p-5 sm:grid-cols-2 lg:grid-cols-3">
      <DetailItem :label="t.clients.email" :value="client.email" />
      <DetailItem :label="t.clients.phone" :value="client.phone" />
      <DetailItem :label="t.clients.address">
        {{ client.street }}<br />{{ client.postal_code }} {{ client.city }}
      </DetailItem>
      <DetailItem
        v-if="client.type === 'company'"
        :label="t.clients.enterpriseNumber"
        :value="client.enterprise_number"
      />
      <DetailItem :label="t.clients.broker" :value="client.broker?.name" />
    </dl>

    <section v-if="client.portal" class="card mt-4 p-5" aria-labelledby="portal-title">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h2 id="portal-title" class="text-sm font-semibold text-slate-900">
            {{ t.clients.portal.title }}
          </h2>
          <p class="mt-1 text-sm text-slate-600">
            <template v-if="client.portal.status === 'active'">
              {{ t.clients.portal.active }}
            </template>
            <template v-else-if="client.portal.status === 'invited' && client.portal.invited_at">
              {{ t.clients.portal.invited(formatDateTime(client.portal.invited_at)) }}
            </template>
            <template v-else>{{ t.clients.portal.none }}</template>
          </p>
        </div>
        <button
          v-if="client.portal.status !== 'active'"
          type="button"
          class="btn-secondary"
          :disabled="isInviting"
          @click="invite"
        >
          {{
            client.portal.status === 'invited' ? t.clients.portal.resend : t.clients.portal.invite
          }}
        </button>
      </div>
      <div v-if="invitation" role="status" class="mt-3 text-sm text-emerald-800">
        <p>{{ invitation.message }}</p>
        <p v-if="invitation.url" class="mt-1 text-slate-600">
          {{ t.clients.portal.demoLink }}
          <a :href="invitation.url" class="link break-all" target="_blank" rel="noopener">
            {{ invitation.url }}
          </a>
        </p>
      </div>
    </section>

    <div class="mt-8 mb-3 flex items-center justify-between gap-3">
      <h2 class="text-base font-semibold text-slate-900">{{ t.clients.contracts }}</h2>
      <RouterLink
        :to="{ name: 'contract-new', query: { client: client.id } }"
        class="btn-secondary"
      >
        {{ t.contracts.new }}
      </RouterLink>
    </div>

    <div class="card overflow-x-auto">
      <table class="min-w-full divide-y divide-slate-200">
        <thead class="bg-slate-50">
          <tr>
            <th scope="col" class="table-head">{{ t.contracts.policy }}</th>
            <th scope="col" class="table-head">{{ t.contracts.type }}</th>
            <th scope="col" class="table-head">{{ t.contracts.expiry }}</th>
            <th scope="col" class="table-head">{{ t.contracts.status }}</th>
            <th scope="col" class="table-head text-right">{{ t.contracts.premium }}</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-if="contracts.length === 0">
            <td colspan="5" class="px-4 py-10 text-center text-sm text-slate-500">
              {{ t.clients.noContracts }}
            </td>
          </tr>
          <tr v-for="contract in contracts" :key="contract.id" class="hover:bg-slate-50">
            <td class="table-cell">
              <RouterLink :to="{ name: 'contract', params: { id: contract.id } }" class="link">
                {{ contract.policy_number }}
              </RouterLink>
            </td>
            <td class="table-cell">{{ t.labels.contractTypes[contract.type] }}</td>
            <td class="table-cell tabular-nums">{{ formatDate(contract.expires_on) }}</td>
            <td class="table-cell"><StatusBadge :status="contract.status" /></td>
            <td class="table-cell text-right tabular-nums">
              {{ formatMoney(contract.annual_premium_cents) }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </template>
</template>
