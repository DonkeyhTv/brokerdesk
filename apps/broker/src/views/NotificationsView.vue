<script setup lang="ts">
import { onMounted, ref, shallowRef } from 'vue'
import { RouterLink } from 'vue-router'
import { api } from '@/api/http'
import type { AppNotification, PageMeta, Paginated } from '@/api/types'
import { t } from '@/i18n'
import { useNotificationsStore } from '@/stores/notifications'
import { formatDate, formatDateTime } from '@/utils/format'
import AlertMessage from '@/components/AlertMessage.vue'
import PageHeader from '@/components/PageHeader.vue'
import PaginationBar from '@/components/PaginationBar.vue'

const store = useNotificationsStore()

const notifications = shallowRef<AppNotification[]>([])
const meta = ref<PageMeta | null>(null)
const page = ref(1)
const error = ref<string | null>(null)

async function load(): Promise<void> {
  try {
    const response = await api<Paginated<AppNotification>>('/notifications', {
      query: { page: page.value },
    })

    notifications.value = response.data
    meta.value = response.meta
    await store.refresh()
  } catch {
    error.value = t.value.notifications.loadFailed
  }
}

async function markRead(notification: AppNotification): Promise<void> {
  if (notification.read_at === null) {
    await api(`/notifications/${notification.id}/read`, { method: 'POST' })
    await load()
  }
}

async function markAllRead(): Promise<void> {
  await api('/notifications/read-all', { method: 'POST' })
  await load()
}

function changePage(value: number): void {
  page.value = value
  void load()
}

onMounted(load)
</script>

<template>
  <PageHeader :title="t.notifications.title" :subtitle="t.notifications.subtitle">
    <button
      type="button"
      class="btn-secondary"
      :disabled="store.unreadCount === 0"
      @click="markAllRead"
    >
      {{ t.notifications.markAllRead }}
    </button>
  </PageHeader>

  <AlertMessage :message="error" />

  <div class="card overflow-hidden">
    <p v-if="notifications.length === 0" class="px-4 py-12 text-center text-sm text-slate-500">
      {{ t.notifications.empty }}
    </p>
    <ul class="divide-y divide-slate-100">
      <li
        v-for="notification in notifications"
        :key="notification.id"
        class="flex flex-wrap items-start justify-between gap-3 px-4 py-3"
        :class="notification.read_at === null ? 'bg-brand-50/60' : ''"
      >
        <div class="min-w-0">
          <p class="text-sm text-slate-900">
            <span v-if="notification.read_at === null" class="font-semibold">{{
              t.notifications.unread
            }}</span>
            {{ t.notifications.before }}
            <RouterLink
              :to="{ name: 'contract', params: { id: notification.data.contract_id } }"
              class="link"
              @click="markRead(notification)"
            >
              {{ notification.data.policy_number }}
            </RouterLink>
            {{
              t.notifications.after(
                notification.data.client_name,
                formatDate(notification.data.expires_on),
              )
            }}
          </p>
          <p class="mt-0.5 text-xs text-slate-500">
            {{
              t.notifications.details(
                notification.data.threshold_days,
                formatDate(notification.data.notice_deadline),
              )
            }}
            ·
            {{ formatDateTime(notification.created_at) }}
          </p>
        </div>
        <button
          v-if="notification.read_at === null"
          type="button"
          class="btn-secondary"
          @click="markRead(notification)"
        >
          {{ t.notifications.markRead }}
        </button>
      </li>
    </ul>
    <PaginationBar :model-value="page" :meta="meta" @update:model-value="changePage" />
  </div>
</template>
