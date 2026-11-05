<script setup lang="ts">
import type { ActivityLog } from '@/api/types'
import { useList } from '@/composables/useList'
import { formatDateTime } from '@/utils/format'
import { ACTIVITY_ACTIONS, ACTIVITY_SUBJECTS } from '@/utils/labels'
import AlertMessage from '@/components/AlertMessage.vue'
import PageHeader from '@/components/PageHeader.vue'
import PaginationBar from '@/components/PaginationBar.vue'

const list = useList<ActivityLog>('/activity-logs', { subject_type: '', action: '' })

function display(value: unknown): string {
  return value === null || value === undefined || value === '' ? 'vide' : String(value)
}
</script>

<template>
  <PageHeader title="Journal d'activité" subtitle="Qui a modifié quoi, et quand" />

  <div class="mb-4 flex flex-wrap gap-3">
    <select
      v-model="list.filters.subject_type"
      class="input w-auto"
      aria-label="Filtrer par élément"
    >
      <option value="">Tous les éléments</option>
      <option value="client">Clients</option>
      <option value="contract">Contrats</option>
      <option value="claim">Sinistres</option>
      <option value="document">Documents</option>
    </select>
    <select v-model="list.filters.action" class="input w-auto" aria-label="Filtrer par action">
      <option value="">Toutes les actions</option>
      <option value="created">Créations</option>
      <option value="updated">Modifications</option>
      <option value="deleted">Suppressions</option>
    </select>
  </div>

  <AlertMessage :message="list.error.value" />

  <div class="card overflow-hidden">
    <p
      v-if="list.rows.value.length === 0"
      class="px-4 py-12 text-center text-sm text-slate-500"
      role="status"
    >
      {{ list.isLoading.value ? 'Chargement…' : 'Aucune activité.' }}
    </p>
    <ul class="divide-y divide-slate-100">
      <li v-for="log in list.rows.value" :key="log.id" class="px-4 py-3">
        <p class="text-sm text-slate-900">
          <span class="font-medium">{{ log.user?.name ?? 'Système' }}</span>
          {{ ACTIVITY_ACTIONS[log.action] }} {{ ACTIVITY_SUBJECTS[log.subject_type] }}
          <span class="font-medium">{{ log.subject_label }}</span>
        </p>
        <ul v-if="log.changes" class="mt-1 space-y-0.5 text-xs text-slate-500">
          <li v-for="(change, field) in log.changes" :key="field">
            {{ field }} : {{ display(change.old) }} → {{ display(change.new) }}
          </li>
        </ul>
        <p class="mt-1 text-xs text-slate-400">{{ formatDateTime(log.created_at) }}</p>
      </li>
    </ul>
    <PaginationBar v-model="list.page.value" :meta="list.meta.value" />
  </div>
</template>
