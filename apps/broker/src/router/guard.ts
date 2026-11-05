import type { NavigationGuard } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

export const authGuard: NavigationGuard = async (to) => {
  const auth = useAuthStore()

  await auth.ensureLoaded()

  if (to.name === 'login' && auth.user) {
    return { name: 'dashboard' }
  }

  if (to.meta.public) {
    return true
  }

  if (!auth.user) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }

  if (to.meta.admin && !auth.isAdmin) {
    return { name: 'dashboard' }
  }

  return true
}
