<script setup lang="ts">
import { computed, onMounted, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { t } from '@/i18n'
import { useNotificationsStore } from '@/stores/notifications'
import LocaleSwitcher from './LocaleSwitcher.vue'

const auth = useAuthStore()
const notifications = useNotificationsStore()
const route = useRoute()
const router = useRouter()

const links = computed(() => [
  { to: { name: 'dashboard' }, label: t.value.nav.dashboard, section: 'dashboard' },
  { to: { name: 'clients' }, label: t.value.nav.clients, section: 'client' },
  { to: { name: 'contracts' }, label: t.value.nav.contracts, section: 'contract' },
  { to: { name: 'claims' }, label: t.value.nav.claims, section: 'claim' },
  ...(auth.isAdmin
    ? [
        { to: { name: 'users' }, label: t.value.nav.team, section: 'users' },
        { to: { name: 'activity' }, label: t.value.nav.activity, section: 'activity' },
      ]
    : []),
  // Only for people evaluating the demo, who sign in with its shared accounts.
  ...(auth.user?.is_demo_account
    ? [{ to: { name: 'about' }, label: t.value.nav.about, section: 'about' }]
    : []),
])

function isActive(section: string): boolean {
  return String(route.name ?? '').startsWith(section)
}

async function logout(): Promise<void> {
  await auth.logout()
  await router.push({ name: 'login' })
}

onMounted(() => void notifications.refresh())
watch(
  () => route.fullPath,
  () => void notifications.refresh(),
)
</script>

<template>
  <div class="min-h-screen lg:flex">
    <aside
      class="flex shrink-0 flex-col border-b border-slate-800 bg-slate-900 text-slate-300 lg:sticky lg:top-0 lg:h-screen lg:w-60 lg:border-r lg:border-b-0"
    >
      <div class="flex items-center gap-2.5 px-5 py-4 lg:py-5">
        <img src="/favicon.svg" alt="" class="size-7" />
        <span class="text-base font-semibold text-white">BrokerDesk</span>
      </div>

      <nav
        :aria-label="t.nav.main"
        class="flex gap-1 overflow-x-auto px-3 pb-3 lg:flex-1 lg:flex-col lg:overflow-visible lg:pb-0"
      >
        <RouterLink
          v-for="link in links"
          :key="link.section"
          :to="link.to"
          class="rounded-lg px-3 py-2 text-sm font-medium whitespace-nowrap transition-colors"
          :class="
            isActive(link.section)
              ? 'bg-slate-800 text-white'
              : 'hover:bg-slate-800/60 hover:text-white'
          "
          :aria-current="isActive(link.section) ? 'page' : undefined"
        >
          {{ link.label }}
        </RouterLink>
      </nav>

      <div class="hidden border-t border-slate-800 p-4 lg:block">
        <p class="truncate text-sm font-medium text-white">{{ auth.user?.name }}</p>
        <p class="truncate text-xs text-slate-400">
          {{ auth.user ? t.labels.userRoles[auth.user.role] : '' }} ·
          {{ auth.user?.brokerage.name }}
        </p>
      </div>
    </aside>

    <div class="min-w-0 flex-1">
      <header
        class="flex items-center justify-end gap-2 border-b border-slate-200 bg-white px-4 py-2.5 sm:px-8"
      >
        <LocaleSwitcher />
        <RouterLink :to="{ name: 'notifications' }" class="btn-secondary">
          {{ t.nav.notifications }}
          <span
            v-if="notifications.unreadCount > 0"
            class="rounded-full bg-brand-600 px-1.5 py-0.5 text-xs font-semibold text-white"
          >
            {{ notifications.unreadCount }}
            <span class="sr-only">{{ t.nav.unread }}</span>
          </span>
        </RouterLink>
        <button type="button" class="btn-secondary" @click="logout">{{ t.nav.logout }}</button>
      </header>

      <main class="mx-auto max-w-6xl px-4 py-6 sm:px-8 sm:py-8">
        <slot />
      </main>
    </div>
  </div>
</template>
