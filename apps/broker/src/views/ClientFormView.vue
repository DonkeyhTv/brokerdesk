<script setup lang="ts">
import { computed, onMounted, reactive, ref, shallowRef } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { api, ApiError } from '@/api/http'
import type { Client, ClientType, Paginated, Resource, User } from '@/api/types'
import { useAuthStore } from '@/stores/auth'
import AlertMessage from '@/components/AlertMessage.vue'
import FormField from '@/components/FormField.vue'
import PageHeader from '@/components/PageHeader.vue'

const props = defineProps<{ id?: string }>()
const auth = useAuthStore()
const router = useRouter()

const form = reactive({
  type: 'individual' as ClientType,
  first_name: '',
  last_name: '',
  company_name: '',
  enterprise_number: '',
  email: '',
  phone: '',
  street: '',
  postal_code: '',
  city: '',
  broker_id: '' as number | '',
})

const staff = shallowRef<User[]>([])
const errors = ref<Record<string, string[]>>({})
const message = ref<string | null>(null)
const isSubmitting = ref(false)
const isEditing = computed(() => props.id !== undefined)

onMounted(async () => {
  try {
    if (auth.isAdmin) {
      staff.value = (await api<Paginated<User>>('/users', { query: { per_page: 100 } })).data
    }

    if (props.id) {
      const { data } = await api<Resource<Client>>(`/clients/${props.id}`)

      Object.assign(form, {
        type: data.type,
        first_name: data.first_name ?? '',
        last_name: data.last_name ?? '',
        company_name: data.company_name ?? '',
        enterprise_number: data.enterprise_number ?? '',
        email: data.email,
        phone: data.phone ?? '',
        street: data.street,
        postal_code: data.postal_code,
        city: data.city,
        broker_id: data.broker?.id ?? '',
      })
    }
  } catch {
    message.value = 'Le formulaire n’a pas pu être chargé.'
  }
})

function payload(): Record<string, unknown> {
  const isCompany = form.type === 'company'

  return {
    type: form.type,
    email: form.email,
    phone: form.phone || null,
    street: form.street,
    postal_code: form.postal_code,
    city: form.city,
    ...(isCompany
      ? { company_name: form.company_name, enterprise_number: form.enterprise_number }
      : { first_name: form.first_name, last_name: form.last_name }),
    ...(auth.isAdmin ? { broker_id: form.broker_id } : {}),
  }
}

async function submit(): Promise<void> {
  isSubmitting.value = true
  errors.value = {}
  message.value = null

  try {
    const { data } = await api<Resource<Client>>(props.id ? `/clients/${props.id}` : '/clients', {
      method: props.id ? 'PUT' : 'POST',
      body: payload(),
    })

    await router.push({ name: 'client', params: { id: data.id } })
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
  <PageHeader :title="isEditing ? 'Modifier le client' : 'Nouveau client'" />

  <AlertMessage :message="message" />

  <form class="card max-w-3xl space-y-5 p-6" novalidate @submit.prevent="submit">
    <fieldset>
      <legend class="mb-2 text-sm font-medium text-slate-700">Type de client</legend>
      <div class="flex gap-5">
        <label class="flex items-center gap-2 text-sm text-slate-700">
          <input v-model="form.type" type="radio" value="individual" name="type" />
          Particulier
        </label>
        <label class="flex items-center gap-2 text-sm text-slate-700">
          <input v-model="form.type" type="radio" value="company" name="type" />
          Entreprise
        </label>
      </div>
    </fieldset>

    <div v-if="form.type === 'individual'" class="grid gap-4 sm:grid-cols-2">
      <FormField label="Prénom" name="first_name" :errors="errors">
        <input id="first_name" v-model="form.first_name" class="input" autocomplete="off" />
      </FormField>
      <FormField label="Nom" name="last_name" :errors="errors">
        <input id="last_name" v-model="form.last_name" class="input" autocomplete="off" />
      </FormField>
    </div>

    <div v-else class="grid gap-4 sm:grid-cols-2">
      <FormField label="Dénomination" name="company_name" :errors="errors">
        <input id="company_name" v-model="form.company_name" class="input" autocomplete="off" />
      </FormField>
      <FormField
        label="N° d'entreprise"
        name="enterprise_number"
        :errors="errors"
        hint="Format BE 0123.456.749"
      >
        <input
          id="enterprise_number"
          v-model="form.enterprise_number"
          class="input"
          autocomplete="off"
        />
      </FormField>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
      <FormField label="E-mail" name="email" :errors="errors">
        <input id="email" v-model="form.email" class="input" type="email" autocomplete="off" />
      </FormField>
      <FormField label="Téléphone" name="phone" :errors="errors">
        <input id="phone" v-model="form.phone" class="input" type="tel" autocomplete="off" />
      </FormField>
    </div>

    <FormField label="Rue et numéro" name="street" :errors="errors">
      <input id="street" v-model="form.street" class="input" autocomplete="off" />
    </FormField>

    <div class="grid gap-4 sm:grid-cols-[10rem_1fr]">
      <FormField label="Code postal" name="postal_code" :errors="errors">
        <input
          id="postal_code"
          v-model="form.postal_code"
          class="input"
          inputmode="numeric"
          maxlength="4"
          autocomplete="off"
        />
      </FormField>
      <FormField label="Commune" name="city" :errors="errors">
        <input id="city" v-model="form.city" class="input" autocomplete="off" />
      </FormField>
    </div>

    <FormField v-if="auth.isAdmin" label="Courtier responsable" name="broker_id" :errors="errors">
      <select id="broker_id" v-model="form.broker_id" class="input">
        <option value="" disabled>Choisir un courtier</option>
        <option v-for="member in staff" :key="member.id" :value="member.id">
          {{ member.name }}
        </option>
      </select>
    </FormField>

    <div class="flex gap-2 pt-2">
      <button type="submit" class="btn-primary" :disabled="isSubmitting">
        {{ isSubmitting ? 'Enregistrement…' : 'Enregistrer' }}
      </button>
      <RouterLink
        :to="id ? { name: 'client', params: { id } } : { name: 'clients' }"
        class="btn-secondary"
      >
        Annuler
      </RouterLink>
    </div>
  </form>
</template>
