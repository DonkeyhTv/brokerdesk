<script setup lang="ts">
import { computed, onMounted, reactive, ref, shallowRef } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { api, ApiError } from '@/api/http'
import type {
  Client,
  Contract,
  ContractStatus,
  ContractType,
  NamedRef,
  Paginated,
  PaymentFrequency,
  Resource,
} from '@/api/types'
import { centsToEuros, eurosToCents } from '@/utils/format'
import { CONTRACT_STATUSES, CONTRACT_TYPES, PAYMENT_FREQUENCIES } from '@/utils/labels'
import AlertMessage from '@/components/AlertMessage.vue'
import FormField from '@/components/FormField.vue'
import PageHeader from '@/components/PageHeader.vue'

const props = defineProps<{ id?: string }>()
const route = useRoute()
const router = useRouter()

const form = reactive({
  client_id: (typeof route.query.client === 'string' ? Number(route.query.client) : '') as
    number | '',
  insurer_id: '' as number | '',
  policy_number: '',
  type: 'motor' as ContractType,
  status: 'active' as ContractStatus,
  starts_on: '',
  expires_on: '',
  premium: '',
  payment_frequency: 'annual' as PaymentFrequency,
})

const clients = shallowRef<Client[]>([])
const insurers = shallowRef<NamedRef[]>([])
const errors = ref<Record<string, string[]>>({})
const message = ref<string | null>(null)
const isSubmitting = ref(false)
const isEditing = computed(() => props.id !== undefined)

onMounted(async () => {
  try {
    const [insurersResponse, clientsResponse] = await Promise.all([
      api<{ data: NamedRef[] }>('/insurers'),
      api<Paginated<Client>>('/clients', { query: { per_page: 100 } }),
    ])

    insurers.value = insurersResponse.data
    clients.value = clientsResponse.data

    if (props.id) {
      const { data } = await api<Resource<Contract>>(`/contracts/${props.id}`)

      Object.assign(form, {
        client_id: data.client?.id ?? '',
        insurer_id: data.insurer?.id ?? '',
        policy_number: data.policy_number,
        type: data.type,
        status: data.status,
        starts_on: data.starts_on,
        expires_on: data.expires_on,
        premium: centsToEuros(data.annual_premium_cents),
        payment_frequency: data.payment_frequency,
      })
    }
  } catch {
    message.value = 'Le formulaire n’a pas pu être chargé.'
  }
})

async function submit(): Promise<void> {
  errors.value = {}
  message.value = null

  const cents = eurosToCents(form.premium)

  if (cents === null) {
    errors.value = { annual_premium_cents: ['Indiquez un montant en euros, par exemple 850,00.'] }
    return
  }

  isSubmitting.value = true

  try {
    const { data } = await api<Resource<Contract>>(
      props.id ? `/contracts/${props.id}` : '/contracts',
      {
        method: props.id ? 'PUT' : 'POST',
        body: {
          insurer_id: form.insurer_id,
          policy_number: form.policy_number,
          type: form.type,
          status: form.status,
          starts_on: form.starts_on,
          expires_on: form.expires_on,
          payment_frequency: form.payment_frequency,
          annual_premium_cents: cents,
          ...(props.id ? {} : { client_id: form.client_id }),
        },
      },
    )

    await router.push({ name: 'contract', params: { id: data.id } })
  } catch (error) {
    if (error instanceof ApiError && error.status === 422) {
      errors.value = error.errors
      message.value = 'Veuillez corriger les champs signalés.'
    } else {
      message.value = 'L’enregistrement a échoué.'
    }
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <PageHeader :title="isEditing ? 'Modifier le contrat' : 'Nouveau contrat'" />

  <AlertMessage :message="message" />

  <form class="card max-w-3xl space-y-5 p-6" novalidate @submit.prevent="submit">
    <div class="grid gap-4 sm:grid-cols-2">
      <FormField label="Client" name="client_id" :errors="errors">
        <select id="client_id" v-model="form.client_id" class="input" :disabled="isEditing">
          <option value="" disabled>Choisir un client</option>
          <option v-for="client in clients" :key="client.id" :value="client.id">
            {{ client.name }}
          </option>
        </select>
      </FormField>
      <FormField label="Assureur" name="insurer_id" :errors="errors">
        <select id="insurer_id" v-model="form.insurer_id" class="input">
          <option value="" disabled>Choisir un assureur</option>
          <option v-for="insurer in insurers" :key="insurer.id" :value="insurer.id">
            {{ insurer.name }}
          </option>
        </select>
      </FormField>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
      <FormField label="N° de police" name="policy_number" :errors="errors">
        <input id="policy_number" v-model="form.policy_number" class="input" autocomplete="off" />
      </FormField>
      <FormField label="Branche" name="type" :errors="errors">
        <select id="type" v-model="form.type" class="input">
          <option v-for="(label, value) in CONTRACT_TYPES" :key="value" :value="value">
            {{ label }}
          </option>
        </select>
      </FormField>
      <FormField label="Statut" name="status" :errors="errors">
        <select id="status" v-model="form.status" class="input">
          <option v-for="(label, value) in CONTRACT_STATUSES" :key="value" :value="value">
            {{ label }}
          </option>
        </select>
      </FormField>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
      <FormField label="Date de début" name="starts_on" :errors="errors">
        <input id="starts_on" v-model="form.starts_on" class="input" type="date" />
      </FormField>
      <FormField label="Date d'échéance" name="expires_on" :errors="errors">
        <input id="expires_on" v-model="form.expires_on" class="input" type="date" />
      </FormField>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
      <FormField label="Prime annuelle (€)" name="annual_premium_cents" :errors="errors">
        <input
          id="annual_premium_cents"
          v-model="form.premium"
          class="input"
          inputmode="decimal"
          autocomplete="off"
        />
      </FormField>
      <FormField label="Fractionnement" name="payment_frequency" :errors="errors">
        <select id="payment_frequency" v-model="form.payment_frequency" class="input">
          <option v-for="(label, value) in PAYMENT_FREQUENCIES" :key="value" :value="value">
            {{ label }}
          </option>
        </select>
      </FormField>
    </div>

    <div class="flex gap-2 pt-2">
      <button type="submit" class="btn-primary" :disabled="isSubmitting">
        {{ isSubmitting ? 'Enregistrement…' : 'Enregistrer' }}
      </button>
      <RouterLink
        :to="id ? { name: 'contract', params: { id } } : { name: 'contracts' }"
        class="btn-secondary"
      >
        Annuler
      </RouterLink>
    </div>
  </form>
</template>
