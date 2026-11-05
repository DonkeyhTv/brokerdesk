import { ref } from 'vue'
import { defineStore } from 'pinia'
import { api } from '@/api/http'
import type { AppNotification, Paginated } from '@/api/types'

export const useNotificationsStore = defineStore('notifications', () => {
  const unreadCount = ref(0)

  async function refresh(): Promise<void> {
    const response = await api<Paginated<AppNotification> & { unread_count: number }>(
      '/notifications',
      { query: { per_page: 1 } },
    )

    unreadCount.value = response.unread_count
  }

  return { unreadCount, refresh }
})
