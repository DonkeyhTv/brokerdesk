<script setup lang="ts">
import { reactive, ref } from 'vue'
import { api, ApiError } from '@/api/http'
import type { User } from '@/api/types'
import { useAuthStore } from '@/stores/auth'
import { useList } from '@/composables/useList'
import { USER_ROLES } from '@/utils/labels'
import AlertMessage from '@/components/AlertMessage.vue'
import FormField from '@/components/FormField.vue'
import PageHeader from '@/components/PageHeader.vue'
import PaginationBar from '@/components/PaginationBar.vue'
import TableState from '@/components/TableState.vue'

const auth = useAuthStore()
const list = useList<User>('/users', { role: '' })

const emptyForm = { name: '', email: '', password: '', role: 'broker' as 'admin' | 'broker' }
const form = reactive({ ...emptyForm })
const editing = ref<User | null>(null)
const isFormOpen = ref(false)
const errors = ref<Record<string, string[]>>({})
const message = ref<string | null>(null)
const isSubmitting = ref(false)

function openNew(): void {
  Object.assign(form, emptyForm)
  editing.value = null
  errors.value = {}
  isFormOpen.value = true
}

function openEdit(user: User): void {
  Object.assign(form, {
    name: user.name,
    email: user.email,
    password: '',
    role: user.role,
  })
  editing.value = user
  errors.value = {}
  isFormOpen.value = true
}

async function submit(): Promise<void> {
  isSubmitting.value = true
  errors.value = {}
  message.value = null

  try {
    await api(editing.value ? `/users/${editing.value.id}` : '/users', {
      method: editing.value ? 'PUT' : 'POST',
      body: { ...form, password: form.password || undefined },
    })

    isFormOpen.value = false
    await list.load()
  } catch (error) {
    if (error instanceof ApiError && error.status === 422) {
      errors.value = error.errors
    } else {
      message.value = 'L’enregistrement a échoué.'
    }
  } finally {
    isSubmitting.value = false
  }
}

async function remove(user: User): Promise<void> {
  if (!window.confirm(`Retirer ${user.name} de l'équipe ?`)) {
    return
  }

  message.value = null

  try {
    await api(`/users/${user.id}`, { method: 'DELETE' })
    await list.load()
  } catch (error) {
    message.value =
      error instanceof ApiError && error.status === 409
        ? `${user.name} a encore des clients ou des dossiers : réassignez-les d'abord.`
        : 'La suppression a échoué.'
  }
}
</script>

<template>
  <PageHeader title="Équipe" :subtitle="auth.user?.brokerage.name">
    <button type="button" class="btn-primary" @click="openNew">Ajouter un membre</button>
  </PageHeader>

  <AlertMessage :message="message ?? list.error.value" />

  <form v-if="isFormOpen" class="card mb-6 space-y-4 p-6" novalidate @submit.prevent="submit">
    <h2 class="text-base font-semibold text-slate-900">
      {{ editing ? `Modifier ${editing.name}` : 'Nouveau membre' }}
    </h2>
    <div class="grid gap-4 sm:grid-cols-2">
      <FormField label="Nom" name="name" :errors="errors">
        <input id="name" v-model="form.name" class="input" autocomplete="off" />
      </FormField>
      <FormField label="E-mail" name="email" :errors="errors">
        <input id="email" v-model="form.email" class="input" type="email" autocomplete="off" />
      </FormField>
      <FormField
        label="Mot de passe"
        name="password"
        :errors="errors"
        :hint="
          editing
            ? 'Laisser vide pour le conserver. 10 caractères minimum.'
            : '10 caractères minimum.'
        "
      >
        <input
          id="password"
          v-model="form.password"
          class="input"
          type="password"
          autocomplete="new-password"
        />
      </FormField>
      <FormField label="Rôle" name="role" :errors="errors">
        <select id="role" v-model="form.role" class="input">
          <option value="broker">{{ USER_ROLES.broker }}</option>
          <option value="admin">{{ USER_ROLES.admin }}</option>
        </select>
      </FormField>
    </div>
    <div class="flex gap-2">
      <button type="submit" class="btn-primary" :disabled="isSubmitting">Enregistrer</button>
      <button type="button" class="btn-secondary" @click="isFormOpen = false">Annuler</button>
    </div>
  </form>

  <div class="card overflow-hidden">
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-slate-200">
        <thead class="bg-slate-50">
          <tr>
            <th scope="col" class="table-head">Nom</th>
            <th scope="col" class="table-head">E-mail</th>
            <th scope="col" class="table-head">Rôle</th>
            <th scope="col" class="table-head"><span class="sr-only">Actions</span></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <TableState
            :is-loading="list.isLoading.value"
            :is-empty="list.rows.value.length === 0"
            :columns="4"
          />
          <tr v-for="user in list.rows.value" :key="user.id" class="hover:bg-slate-50">
            <td class="table-cell font-medium text-slate-900">{{ user.name }}</td>
            <td class="table-cell">{{ user.email }}</td>
            <td class="table-cell">{{ USER_ROLES[user.role] }}</td>
            <td class="table-cell text-right whitespace-nowrap">
              <span
                v-if="user.is_demo_account"
                class="rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-700"
                title="Partagé par tous les visiteurs de la démo : il ne peut être ni modifié ni retiré."
              >
                Compte de démo
              </span>
              <template v-else>
                <button type="button" class="link mr-3" @click="openEdit(user)">Modifier</button>
                <button
                  v-if="user.id !== auth.user?.id"
                  type="button"
                  class="font-medium text-red-700 hover:underline"
                  @click="remove(user)"
                >
                  Retirer
                </button>
              </template>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <PaginationBar v-model="list.page.value" :meta="list.meta.value" />
  </div>
</template>
