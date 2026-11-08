<script setup lang="ts">
import { computed, onMounted, ref, shallowRef } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { api, ApiError, buildUrl } from '@/api/http'
import type { Claim, Contract, ContractDocument, Paginated, Resource } from '@/api/types'
import {
  centsToEuros,
  daysUntil,
  eurosToCents,
  formatDate,
  formatFileSize,
  formatMoney,
  oneYearAfter,
} from '@/utils/format'
import { CONTRACT_TYPES, PAYMENT_FREQUENCIES } from '@/utils/labels'
import AlertMessage from '@/components/AlertMessage.vue'
import DetailItem from '@/components/DetailItem.vue'
import FormField from '@/components/FormField.vue'
import PageHeader from '@/components/PageHeader.vue'
import StatusBadge from '@/components/StatusBadge.vue'

const props = defineProps<{ id: string }>()
const router = useRouter()

const contract = ref<Contract | null>(null)
const documents = shallowRef<ContractDocument[]>([])
const claims = shallowRef<Claim[]>([])
const error = ref<string | null>(null)
const uploadError = ref<string | null>(null)
const isUploading = ref(false)
const visibleToClient = ref(true)
const fileInput = ref<HTMLInputElement | null>(null)

const isRenewing = ref(false)
const renewalPremium = ref('')
const renewalErrors = ref<Record<string, string[]>>({})
const renewed = ref<string | null>(null)

const noticeHasPassed = computed(
  () => contract.value?.status === 'active' && daysUntil(contract.value.notice_deadline) < 0,
)

async function loadDocuments(): Promise<void> {
  documents.value = (
    await api<{ data: ContractDocument[] }>(`/contracts/${props.id}/documents`)
  ).data
}

onMounted(async () => {
  try {
    const [contractResponse, claimsResponse] = await Promise.all([
      api<Resource<Contract>>(`/contracts/${props.id}`),
      api<Paginated<Claim>>('/claims', {
        query: { 'filter[contract_id]': props.id, per_page: 100 },
      }),
      loadDocuments(),
    ])

    contract.value = contractResponse.data
    claims.value = claimsResponse.data
  } catch (caught) {
    error.value =
      caught instanceof ApiError && caught.status === 404
        ? 'Ce contrat est introuvable.'
        : 'Ce contrat n’a pas pu être chargé.'
  }
})

async function upload(): Promise<void> {
  const file = fileInput.value?.files?.[0]

  if (!file) {
    uploadError.value = 'Choisissez d’abord un fichier PDF.'
    return
  }

  const body = new FormData()
  body.append('file', file)
  body.append('visible_to_client', visibleToClient.value ? '1' : '0')

  isUploading.value = true
  uploadError.value = null

  try {
    await api(`/contracts/${props.id}/documents`, { method: 'POST', body })
    await loadDocuments()

    if (fileInput.value) {
      fileInput.value.value = ''
    }
  } catch (caught) {
    uploadError.value =
      caught instanceof ApiError && caught.status === 422
        ? 'Seuls les fichiers PDF de 10 Mo maximum sont acceptés.'
        : 'L’envoi a échoué.'
  } finally {
    isUploading.value = false
  }
}

async function toggleVisibility(document: ContractDocument): Promise<void> {
  await api(`/documents/${document.id}`, {
    method: 'PATCH',
    body: { visible_to_client: !document.visible_to_client },
  })
  await loadDocuments()
}

async function removeDocument(document: ContractDocument): Promise<void> {
  if (window.confirm(`Supprimer « ${document.original_name} » ?`)) {
    await api(`/documents/${document.id}`, { method: 'DELETE' })
    await loadDocuments()
  }
}

function openRenewal(): void {
  renewalPremium.value = centsToEuros(contract.value?.annual_premium_cents)
  renewalErrors.value = {}
  renewed.value = null
  isRenewing.value = true
}

async function renew(): Promise<void> {
  const cents = eurosToCents(renewalPremium.value)

  if (cents === null) {
    renewalErrors.value = { annual_premium_cents: ['Indiquez un montant en euros.'] }
    return
  }

  try {
    const { data } = await api<Resource<Contract>>(`/contracts/${props.id}/renewal`, {
      method: 'POST',
      body: { annual_premium_cents: cents },
    })

    contract.value = data
    isRenewing.value = false
    renewed.value = `Contrat renouvelé jusqu'au ${formatDate(data.expires_on)}.`
  } catch (caught) {
    if (caught instanceof ApiError && caught.status === 422) {
      renewalErrors.value = caught.errors
    } else {
      error.value =
        caught instanceof ApiError && caught.status === 409
          ? 'Ce contrat ne peut plus être renouvelé.'
          : 'Le renouvellement a échoué.'
      isRenewing.value = false
    }
  }
}

async function remove(): Promise<void> {
  if (!window.confirm('Supprimer ce contrat ?')) {
    return
  }

  try {
    await api(`/contracts/${props.id}`, { method: 'DELETE' })
    await router.push({ name: 'contracts' })
  } catch (caught) {
    error.value =
      caught instanceof ApiError && caught.status === 409
        ? 'Ce contrat a encore des sinistres en cours : il ne peut pas être supprimé.'
        : 'La suppression a échoué.'
  }
}
</script>

<template>
  <AlertMessage :message="error" />

  <template v-if="contract">
    <PageHeader
      :title="contract.policy_number"
      :subtitle="`${CONTRACT_TYPES[contract.type]} · ${contract.client?.name}`"
    >
      <button
        v-if="contract.is_renewable && !isRenewing"
        type="button"
        class="btn-primary"
        @click="openRenewal"
      >
        Renouveler
      </button>
      <RouterLink
        :to="{ name: 'contract-edit', params: { id: contract.id } }"
        class="btn-secondary"
      >
        Modifier
      </RouterLink>
      <button type="button" class="btn-danger" @click="remove">Supprimer</button>
    </PageHeader>

    <AlertMessage :message="renewed" tone="success" />

    <form
      v-if="isRenewing"
      class="card mb-6 space-y-4 p-5"
      aria-labelledby="renewal-title"
      novalidate
      @submit.prevent="renew"
    >
      <div>
        <h2 id="renewal-title" class="text-base font-semibold text-slate-900">
          Renouveler jusqu'au {{ formatDate(oneYearAfter(contract.expires_on)) }}
        </h2>
        <p class="mt-1 text-sm text-slate-500">
          Le contrat repart pour un an à partir de son échéance, et les alertes reprennent pour la
          nouvelle date.
        </p>
      </div>
      <FormField
        label="Prime annuelle du nouveau terme"
        name="annual_premium_cents"
        :errors="renewalErrors"
        hint="À adapter si l'assureur indexe la prime."
      >
        <input
          id="annual_premium_cents"
          v-model="renewalPremium"
          class="input max-w-48"
          inputmode="decimal"
        />
      </FormField>
      <div class="flex gap-2">
        <button type="submit" class="btn-primary">Confirmer le renouvellement</button>
        <button type="button" class="btn-secondary" @click="isRenewing = false">Annuler</button>
      </div>
    </form>

    <dl class="card grid gap-5 p-5 sm:grid-cols-2 lg:grid-cols-4">
      <DetailItem label="Statut"><StatusBadge :status="contract.status" /></DetailItem>
      <DetailItem label="Client">
        <RouterLink
          v-if="contract.client"
          :to="{ name: 'client', params: { id: contract.client.id } }"
          class="link"
        >
          {{ contract.client.name }}
        </RouterLink>
      </DetailItem>
      <DetailItem label="Assureur" :value="contract.insurer?.name" />
      <DetailItem label="Prime annuelle" :value="formatMoney(contract.annual_premium_cents)" />
      <DetailItem label="Début" :value="formatDate(contract.starts_on)" />
      <DetailItem label="Échéance" :value="formatDate(contract.expires_on)" />
      <DetailItem label="Préavis jusqu'au">
        {{ formatDate(contract.notice_deadline) }}
        <span v-if="noticeHasPassed" class="block text-xs text-slate-500">
          Dépassé : reconduction tacite
        </span>
      </DetailItem>
      <DetailItem label="Fractionnement" :value="PAYMENT_FREQUENCIES[contract.payment_frequency]" />
    </dl>

    <section class="mt-8" aria-labelledby="documents-title">
      <h2 id="documents-title" class="mb-3 text-base font-semibold text-slate-900">Documents</h2>

      <div class="card divide-y divide-slate-100">
        <p v-if="documents.length === 0" class="px-4 py-8 text-center text-sm text-slate-500">
          Aucun document.
        </p>
        <div
          v-for="document in documents"
          :key="document.id"
          class="flex flex-wrap items-center justify-between gap-3 px-4 py-3"
        >
          <div class="min-w-0">
            <a
              :href="buildUrl(`/documents/${document.id}/download`)"
              class="link block truncate text-sm"
            >
              {{ document.original_name }}
            </a>
            <p class="text-xs text-slate-500">
              {{ formatFileSize(document.size_bytes) }} ·
              {{ document.visible_to_client ? 'Visible par le client' : 'Interne' }}
            </p>
          </div>
          <div class="flex gap-2">
            <button type="button" class="btn-secondary" @click="toggleVisibility(document)">
              {{ document.visible_to_client ? 'Rendre interne' : 'Partager au client' }}
            </button>
            <button
              type="button"
              class="btn-danger"
              :aria-label="`Supprimer ${document.original_name}`"
              @click="removeDocument(document)"
            >
              Supprimer
            </button>
          </div>
        </div>

        <form class="px-4 py-4" @submit.prevent="upload">
          <AlertMessage :message="uploadError" />
          <div class="flex flex-wrap items-center gap-3">
            <label for="document-file" class="sr-only">Fichier PDF</label>
            <input
              id="document-file"
              ref="fileInput"
              type="file"
              accept="application/pdf,.pdf"
              class="max-w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-slate-200"
            />
            <label class="flex items-center gap-2 text-sm text-slate-700">
              <input v-model="visibleToClient" type="checkbox" />
              Visible par le client
            </label>
            <button type="submit" class="btn-primary" :disabled="isUploading">
              {{ isUploading ? 'Envoi…' : 'Ajouter le PDF' }}
            </button>
          </div>
        </form>
      </div>
    </section>

    <section class="mt-8" aria-labelledby="claims-title">
      <div class="mb-3 flex items-center justify-between gap-3">
        <h2 id="claims-title" class="text-base font-semibold text-slate-900">Sinistres</h2>
        <RouterLink
          v-if="contract.status === 'active'"
          :to="{ name: 'claim-new', query: { contract: contract.id } }"
          class="btn-secondary"
        >
          Déclarer un sinistre
        </RouterLink>
      </div>
      <div class="card divide-y divide-slate-100">
        <p v-if="claims.length === 0" class="px-4 py-8 text-center text-sm text-slate-500">
          Aucun sinistre sur ce contrat.
        </p>
        <RouterLink
          v-for="claim in claims"
          :key="claim.id"
          :to="{ name: 'claim', params: { id: claim.id } }"
          class="flex items-center justify-between gap-3 px-4 py-3 hover:bg-slate-50"
        >
          <span class="min-w-0">
            <span class="block text-sm font-medium text-slate-900">{{ claim.reference }}</span>
            <span class="block truncate text-xs text-slate-500">{{ claim.description }}</span>
          </span>
          <StatusBadge :status="claim.status" />
        </RouterLink>
      </div>
    </section>
  </template>
</template>
