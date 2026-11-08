<script setup lang="ts">
import { reactive, ref } from 'vue'
import { api, ApiError } from '@/api/http'
import type { User } from '@/api/types'
import { useAuthStore } from '@/stores/auth'
import { useList } from '@/composables/useList'
import { t } from '@/i18n'
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
      message.value = t.value.common.saveFailed
    }
  } finally {
    isSubmitting.value = false
  }
}

async function remove(user: User): Promise<void> {
  if (!window.confirm(t.value.users.confirmRemove(user.name))) {
    return
  }

  message.value = null

  try {
    await api(`/users/${user.id}`, { method: 'DELETE' })
    await list.load()
  } catch (error) {
    message.value =
      error instanceof ApiError && error.status === 409
        ? t.value.users.stillReferenced(user.name)
        : t.value.common.deleteFailed
  }
}
</script>

<template>
  <PageHeader :title="t.users.title" :subtitle="auth.user?.brokerage.name">
    <button type="button" class="btn-primary" @click="openNew">{{ t.users.add }}</button>
  </PageHeader>

  <AlertMessage :message="message ?? list.error.value" />

  <form v-if="isFormOpen" class="card mb-6 space-y-4 p-6" novalidate @submit.prevent="submit">
    <h2 class="text-base font-semibold text-slate-900">
      {{ editing ? t.users.editMember(editing.name) : t.users.newMember }}
    </h2>
    <div class="grid gap-4 sm:grid-cols-2">
      <FormField :label="t.users.name" name="name" :errors="errors">
        <input id="name" v-model="form.name" class="input" autocomplete="off" />
      </FormField>
      <FormField :label="t.users.email" name="email" :errors="errors">
        <input id="email" v-model="form.email" class="input" type="email" autocomplete="off" />
      </FormField>
      <FormField
        :label="t.users.password"
        name="password"
        :errors="errors"
        :hint="editing ? t.users.passwordKeepHint : t.users.passwordHint"
      >
        <input
          id="password"
          v-model="form.password"
          class="input"
          type="password"
          autocomplete="new-password"
        />
      </FormField>
      <FormField :label="t.users.role" name="role" :errors="errors">
        <select id="role" v-model="form.role" class="input">
          <option value="broker">{{ t.labels.userRoles.broker }}</option>
          <option value="admin">{{ t.labels.userRoles.admin }}</option>
        </select>
      </FormField>
    </div>
    <div class="flex gap-2">
      <button type="submit" class="btn-primary" :disabled="isSubmitting">
        {{ t.common.save }}
      </button>
      <button type="button" class="btn-secondary" @click="isFormOpen = false">
        {{ t.common.cancel }}
      </button>
    </div>
  </form>

  <div class="card overflow-hidden">
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-slate-200">
        <thead class="bg-slate-50">
          <tr>
            <th scope="col" class="table-head">{{ t.users.name }}</th>
            <th scope="col" class="table-head">{{ t.users.email }}</th>
            <th scope="col" class="table-head">{{ t.users.role }}</th>
            <th scope="col" class="table-head">
              <span class="sr-only">{{ t.users.actions }}</span>
            </th>
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
            <td class="table-cell">{{ t.labels.userRoles[user.role] }}</td>
            <td class="table-cell text-right whitespace-nowrap">
              <span
                v-if="user.is_demo_account"
                class="rounded-full bg-brand-50 px-2 py-0.5 text-xs font-medium text-brand-700"
                :title="t.users.demoAccountTitle"
              >
                {{ t.users.demoAccount }}
              </span>
              <template v-else>
                <button type="button" class="link mr-3" @click="openEdit(user)">
                  {{ t.common.edit }}
                </button>
                <button
                  v-if="user.id !== auth.user?.id"
                  type="button"
                  class="font-medium text-red-700 hover:underline"
                  @click="remove(user)"
                >
                  {{ t.users.remove }}
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
