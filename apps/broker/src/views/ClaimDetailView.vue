<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { api, ApiError } from '@/api/http'
import type { Claim, ClaimStatus, Resource } from '@/api/types'
import { useAuthStore } from '@/stores/auth'
import { centsToEuros, eurosToCents, formatDate, formatDateTime, formatMoney } from '@/utils/format'
import { t } from '@/i18n'
import AlertMessage from '@/components/AlertMessage.vue'
import DetailItem from '@/components/DetailItem.vue'
import FormField from '@/components/FormField.vue'
import PageHeader from '@/components/PageHeader.vue'
import StatusBadge from '@/components/StatusBadge.vue'

const props = defineProps<{ id: string }>()
const auth = useAuthStore()
const router = useRouter()

const claim = ref<Claim | null>(null)
const error = ref<string | null>(null)
const success = ref<string | null>(null)
const errors = ref<Record<string, string[]>>({})
const isSubmitting = ref(false)

const form = reactive({
  status: 'open' as ClaimStatus,
  estimate: '',
  settled: '',
  description: '',
  occurred_on: '',
})

const isClosed = computed(() => claim.value?.closed_at != null)

function fill(data: Claim): void {
  claim.value = data
  Object.assign(form, {
    status: data.status,
    estimate: centsToEuros(data.estimated_amount_cents),
    settled: centsToEuros(data.settled_amount_cents),
    description: data.description,
    occurred_on: data.occurred_on,
  })
}

onMounted(async () => {
  try {
    fill((await api<Resource<Claim>>(`/claims/${props.id}`)).data)
  } catch (caught) {
    error.value =
      caught instanceof ApiError && caught.status === 404
        ? t.value.claims.notFound
        : t.value.claims.loadFailed
  }
})

async function save(): Promise<void> {
  errors.value = {}
  error.value = null
  success.value = null

  const estimate = form.estimate === '' ? null : eurosToCents(form.estimate)
  const settled = form.settled === '' ? null : eurosToCents(form.settled)

  if (form.estimate !== '' && estimate === null) {
    errors.value = { estimated_amount_cents: [t.value.common.amountInEuros] }
    return
  }

  if (form.status === 'settled' && settled === null) {
    errors.value = { settled_amount_cents: [t.value.claims.settledAmountRequired] }
    return
  }

  isSubmitting.value = true

  try {
    const response = await api<Resource<Claim>>(`/claims/${props.id}`, {
      method: 'PUT',
      body: {
        occurred_on: form.occurred_on,
        description: form.description,
        status: form.status,
        estimated_amount_cents: estimate,
        ...(form.status === 'settled' ? { settled_amount_cents: settled } : {}),
      },
    })

    fill(response.data)
    success.value = t.value.claims.updated
  } catch (caught) {
    if (caught instanceof ApiError && caught.status === 422) {
      errors.value = caught.errors
    } else {
      error.value = t.value.common.saveFailed
    }
  } finally {
    isSubmitting.value = false
  }
}

async function remove(): Promise<void> {
  if (window.confirm(t.value.claims.confirmDelete)) {
    await api(`/claims/${props.id}`, { method: 'DELETE' })
    await router.push({ name: 'claims' })
  }
}
</script>

<template>
  <AlertMessage :message="error" />
  <AlertMessage :message="success" tone="success" />

  <template v-if="claim">
    <PageHeader :title="claim.reference" :subtitle="claim.contract?.client?.name">
      <button v-if="auth.isAdmin" type="button" class="btn-danger" @click="remove">
        {{ t.common.delete }}
      </button>
    </PageHeader>

    <dl class="card grid gap-5 p-5 sm:grid-cols-2 lg:grid-cols-4">
      <DetailItem :label="t.claims.status"><StatusBadge :status="claim.status" /></DetailItem>
      <DetailItem :label="t.claims.contract">
        <RouterLink
          v-if="claim.contract"
          :to="{ name: 'contract', params: { id: claim.contract.id } }"
          class="link"
        >
          {{ claim.contract.policy_number }}
        </RouterLink>
      </DetailItem>
      <DetailItem :label="t.claims.occurredOn" :value="formatDate(claim.occurred_on)" />
      <DetailItem :label="t.claims.declaredBy" :value="claim.declared_by?.name" />
      <DetailItem :label="t.claims.estimate" :value="formatMoney(claim.estimated_amount_cents)" />
      <DetailItem
        :label="t.claims.settledAmount"
        :value="formatMoney(claim.settled_amount_cents)"
      />
      <DetailItem
        v-if="claim.closed_at"
        :label="t.claims.closedOn"
        :value="formatDateTime(claim.closed_at)"
      />
      <div class="sm:col-span-2 lg:col-span-4">
        <DetailItem :label="t.claims.description">
          <span class="whitespace-pre-line">{{ claim.description }}</span>
        </DetailItem>
      </div>
    </dl>

    <p v-if="isClosed" class="mt-6 text-sm text-slate-500">
      {{ t.claims.closed }}
    </p>

    <form v-else class="card mt-6 max-w-3xl space-y-5 p-6" novalidate @submit.prevent="save">
      <h2 class="text-base font-semibold text-slate-900">{{ t.claims.followUp }}</h2>

      <div class="grid gap-4 sm:grid-cols-3">
        <FormField :label="t.claims.status" name="status" :errors="errors">
          <select id="status" v-model="form.status" class="input">
            <option v-for="(label, value) in t.labels.claimStatuses" :key="value" :value="value">
              {{ label }}
            </option>
          </select>
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
        <FormField
          v-if="form.status === 'settled'"
          :label="t.claims.settledAmountEuros"
          name="settled_amount_cents"
          :errors="errors"
        >
          <input
            id="settled_amount_cents"
            v-model="form.settled"
            class="input"
            inputmode="decimal"
            autocomplete="off"
          />
        </FormField>
      </div>

      <FormField :label="t.claims.description" name="description" :errors="errors">
        <textarea id="description" v-model="form.description" class="input" rows="4" />
      </FormField>

      <p
        v-if="form.status === 'settled' || form.status === 'rejected'"
        class="text-sm text-slate-500"
      >
        {{ t.claims.closingWarning }}
      </p>

      <button type="submit" class="btn-primary" :disabled="isSubmitting">
        {{ isSubmitting ? t.common.saving : t.common.save }}
      </button>
    </form>
  </template>
</template>
