<script setup lang="ts">
import { onMounted, ref, shallowRef } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { api, ApiError } from '@/api/http'
import type { Client, Contract, Paginated, Resource } from '@/api/types'
import { formatDate, formatMoney } from '@/utils/format'
import { CLIENT_TYPES, CONTRACT_TYPES } from '@/utils/labels'
import AlertMessage from '@/components/AlertMessage.vue'
import DetailItem from '@/components/DetailItem.vue'
import PageHeader from '@/components/PageHeader.vue'
import StatusBadge from '@/components/StatusBadge.vue'

const props = defineProps<{ id: string }>()
const router = useRouter()

const client = ref<Client | null>(null)
const contracts = shallowRef<Contract[]>([])
const error = ref<string | null>(null)

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
        ? 'Ce client est introuvable.'
        : 'Ce client n’a pas pu être chargé.'
  }
})

async function remove(): Promise<void> {
  if (!window.confirm('Supprimer ce client ?')) {
    return
  }

  try {
    await api(`/clients/${props.id}`, { method: 'DELETE' })
    await router.push({ name: 'clients' })
  } catch (caught) {
    error.value =
      caught instanceof ApiError && caught.status === 409
        ? 'Ce client a encore des contrats actifs : il ne peut pas être supprimé.'
        : caught instanceof ApiError && caught.status === 403
          ? 'Ce client se connecte au portail avec un compte de démo : il est conservé pour les visiteurs suivants.'
          : 'La suppression a échoué.'
  }
}
</script>

<template>
  <AlertMessage :message="error" />

  <template v-if="client">
    <PageHeader :title="client.name" :subtitle="CLIENT_TYPES[client.type]">
      <RouterLink :to="{ name: 'client-edit', params: { id: client.id } }" class="btn-secondary">
        Modifier
      </RouterLink>
      <button type="button" class="btn-danger" @click="remove">Supprimer</button>
    </PageHeader>

    <dl class="card grid gap-5 p-5 sm:grid-cols-2 lg:grid-cols-3">
      <DetailItem label="E-mail" :value="client.email" />
      <DetailItem label="Téléphone" :value="client.phone" />
      <DetailItem label="Adresse">
        {{ client.street }}<br />{{ client.postal_code }} {{ client.city }}
      </DetailItem>
      <DetailItem
        v-if="client.type === 'company'"
        label="N° d'entreprise"
        :value="client.enterprise_number"
      />
      <DetailItem label="Courtier" :value="client.broker?.name" />
    </dl>

    <div class="mt-8 mb-3 flex items-center justify-between gap-3">
      <h2 class="text-base font-semibold text-slate-900">Contrats</h2>
      <RouterLink
        :to="{ name: 'contract-new', query: { client: client.id } }"
        class="btn-secondary"
      >
        Nouveau contrat
      </RouterLink>
    </div>

    <div class="card overflow-x-auto">
      <table class="min-w-full divide-y divide-slate-200">
        <thead class="bg-slate-50">
          <tr>
            <th scope="col" class="table-head">Police</th>
            <th scope="col" class="table-head">Branche</th>
            <th scope="col" class="table-head">Échéance</th>
            <th scope="col" class="table-head">Statut</th>
            <th scope="col" class="table-head text-right">Prime</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-if="contracts.length === 0">
            <td colspan="5" class="px-4 py-10 text-center text-sm text-slate-500">
              Aucun contrat pour ce client.
            </td>
          </tr>
          <tr v-for="contract in contracts" :key="contract.id" class="hover:bg-slate-50">
            <td class="table-cell">
              <RouterLink :to="{ name: 'contract', params: { id: contract.id } }" class="link">
                {{ contract.policy_number }}
              </RouterLink>
            </td>
            <td class="table-cell">{{ CONTRACT_TYPES[contract.type] }}</td>
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
