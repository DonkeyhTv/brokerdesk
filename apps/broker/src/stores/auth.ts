import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import { api, ApiError } from '@/api/http'
import type { Resource, User } from '@/api/types'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const isLoaded = ref(false)

  const isAdmin = computed(() => user.value?.role === 'admin')

  function clear(): void {
    user.value = null
  }

  async function ensureLoaded(): Promise<void> {
    if (isLoaded.value) {
      return
    }

    try {
      user.value = (await api<Resource<User>>('/me')).data
    } catch (error) {
      if (!(error instanceof ApiError) || error.status !== 401) {
        throw error
      }
    } finally {
      isLoaded.value = true
    }
  }

  async function login(email: string, password: string): Promise<void> {
    const response = await api<Resource<User>>('/auth/session', {
      method: 'POST',
      body: { email, password },
    })

    user.value = response.data
    isLoaded.value = true
  }

  async function logout(): Promise<void> {
    await api('/auth/session', { method: 'DELETE' })
    clear()
  }

  return { user, isAdmin, clear, ensureLoaded, login, logout }
})
