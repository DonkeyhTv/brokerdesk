<script setup lang="ts">
import type { ActivityLog } from '@/api/types'
import { useList } from '@/composables/useList'
import { formatDateTime } from '@/utils/format'
import { t } from '@/i18n'
import AlertMessage from '@/components/AlertMessage.vue'
import PageHeader from '@/components/PageHeader.vue'
import PaginationBar from '@/components/PaginationBar.vue'

const list = useList<ActivityLog>('/activity-logs', { subject_type: '', action: '' })

function display(value: unknown): string {
  return value === null || value === undefined || value === ''
    ? t.value.common.emptyValue
    : String(value)
}
</script>

<template>
  <PageHeader :title="t.activity.title" :subtitle="t.activity.subtitle" />

  <div class="mb-4 flex flex-wrap gap-3">
    <select
      v-model="list.filters.subject_type"
      class="input w-auto"
      :aria-label="t.activity.subjectFilter"
    >
      <option value="">{{ t.activity.allSubjects }}</option>
      <option value="client">{{ t.activity.clients }}</option>
      <option value="contract">{{ t.activity.contracts }}</option>
      <option value="claim">{{ t.activity.claims }}</option>
      <option value="document">{{ t.activity.documents }}</option>
    </select>
    <select
      v-model="list.filters.action"
      class="input w-auto"
      :aria-label="t.activity.actionFilter"
    >
      <option value="">{{ t.activity.allActions }}</option>
      <option value="created">{{ t.activity.created }}</option>
      <option value="updated">{{ t.activity.updated }}</option>
      <option value="deleted">{{ t.activity.deleted }}</option>
    </select>
  </div>

  <AlertMessage :message="list.error.value" />

  <div class="card overflow-hidden">
    <p
      v-if="list.rows.value.length === 0"
      class="px-4 py-12 text-center text-sm text-slate-500"
      role="status"
    >
      {{ list.isLoading.value ? t.common.loading : t.activity.empty }}
    </p>
    <ul class="divide-y divide-slate-100">
      <li v-for="log in list.rows.value" :key="log.id" class="px-4 py-3">
        <p class="text-sm text-slate-900">
          <span class="font-medium">{{ log.user?.name ?? t.common.system }}</span>
          {{ t.labels.activityActions[log.action] }}
          {{ t.labels.activitySubjects[log.subject_type] }}
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
