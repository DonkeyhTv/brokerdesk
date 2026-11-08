<script setup lang="ts">
import { reactive, ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { api, ApiError } from '@/api/http'
import type { Claim, Contract, Paginated, Resource } from '@/api/types'
import { eurosToCents } from '@/utils/format'
import { t } from '@/i18n'
import AlertMessage from '@/components/AlertMessage.vue'
import FormField from '@/components/FormField.vue'
import PageHeader from '@/components/PageHeader.vue'
import SearchSelect, { type SearchOption } from '@/components/SearchSelect.vue'

const route = useRoute()
const router = useRouter()

const form = reactive({
  contract_id: (typeof route.query.contract === 'string' ? Number(route.query.contract) : '') as
    number | '',
  occurred_on: new Date().toISOString().slice(0, 10),
  description: '',
  estimate: '',
})

const errors = ref<Record<string, string[]>>({})
const message = ref<string | null>(null)
const isSubmitting = ref(false)

function contractOption(contract: Contract): SearchOption {
  return {
    id: contract.id,
    label: `${contract.client?.name} – ${t.value.labels.contractTypes[contract.type]} – ${contract.policy_number}`,
  }
}

async function searchContracts(term: string): Promise<SearchOption[]> {
  const { data } = await api<Paginated<Contract>>('/contracts', {
    query: { 'filter[status]': 'active', 'filter[search]': term || undefined, per_page: 10 },
  })

  return data.map(contractOption)
}

async function resolveContract(id: number): Promise<SearchOption> {
  return contractOption((await api<Resource<Contract>>(`/contracts/${id}`)).data)
}

async function submit(): Promise<void> {
  errors.value = {}
  message.value = null

  const estimate = form.estimate === '' ? null : eurosToCents(form.estimate)

  if (form.estimate !== '' && estimate === null) {
    errors.value = { estimated_amount_cents: [t.value.common.amountInEuros] }
    return
  }

  isSubmitting.value = true

  try {
    const { data } = await api<Resource<Claim>>('/claims', {
      method: 'POST',
      body: {
        contract_id: form.contract_id,
        occurred_on: form.occurred_on,
        description: form.description,
        estimated_amount_cents: estimate,
      },
    })

    await router.push({ name: 'claim', params: { id: data.id } })
  } catch (error) {
    if (error instanceof ApiError && error.status === 422) {
      errors.value = error.errors
      message.value = t.value.common.fixFields
    } else {
      message.value = t.value.claims.declareFailed
    }
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <PageHeader :title="t.claims.declare" />

  <AlertMessage :message="message" />

  <form class="card max-w-2xl space-y-5 p-6" novalidate @submit.prevent="submit">
    <FormField :label="t.claims.contract" name="contract_id" :errors="errors">
      <SearchSelect
        id="contract_id"
        v-model="form.contract_id"
        :search="searchContracts"
        :resolve="resolveContract"
        :placeholder="t.claims.contractSearchPlaceholder"
      />
    </FormField>

    <div class="grid gap-4 sm:grid-cols-2">
      <FormField :label="t.claims.occurredDate" name="occurred_on" :errors="errors">
        <input id="occurred_on" v-model="form.occurred_on" class="input" type="date" />
      </FormField>
      <FormField :label="t.claims.estimateEuros" name="estimated_amount_cents" :errors="errors">
        <input
          id="estimated_amount_cents"
          v-model="form.estimate"
          class="input"
          inputmode="decimal"
          autocomplete="off"
        />
      </FormField>
    </div>

    <FormField :label="t.claims.description" name="description" :errors="errors">
      <textarea id="description" v-model="form.description" class="input" rows="5" />
    </FormField>

    <div class="flex gap-2 pt-2">
      <button type="submit" class="btn-primary" :disabled="isSubmitting">
        {{ isSubmitting ? t.common.sending : t.claims.submit }}
      </button>
      <RouterLink :to="{ name: 'claims' }" class="btn-secondary">{{ t.common.cancel }}</RouterLink>
    </div>
  </form>
</template>
