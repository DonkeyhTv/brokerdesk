<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { api, ApiError } from '@/api/http'
import type { DemoAccounts } from '@/api/types'
import { locale, t } from '@/i18n'
import { useAuthStore } from '@/stores/auth'
import AlertMessage from '@/components/AlertMessage.vue'
import FormField from '@/components/FormField.vue'
import LocaleSwitcher from '@/components/LocaleSwitcher.vue'
import TechStack from '@/components/TechStack.vue'
import { SOURCE_URL } from '@/utils/stack'

const PORTAL_URL = import.meta.env.VITE_PORTAL_URL ?? 'http://localhost:3000'

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()

const email = ref('')
const password = ref('')
const errors = ref<Record<string, string[]>>({})
const message = ref<string | null>(null)
const isSubmitting = ref(false)
const demo = ref<DemoAccounts | null>(null)

const staffAccounts = computed(
  () => demo.value?.accounts.filter((account) => account.app === 'broker') ?? [],
)

async function loadDemoAccounts(): Promise<void> {
  try {
    demo.value = await api<DemoAccounts>('/demo/accounts')
  } catch {
    // Demo mode is off: the page simply offers no shortcut.
  }
}

onMounted(loadDemoAccounts)
// The API names the accounts in the current language.
watch(locale, loadDemoAccounts)

async function submit(): Promise<void> {
  isSubmitting.value = true
  errors.value = {}
  message.value = null

  try {
    await auth.login(email.value, password.value)

    const redirect = route.query.redirect

    // Only same-site paths: a crafted link must not send people elsewhere after they sign in.
    await router.push(
      typeof redirect === 'string' && /^\/(?!\/)/.test(redirect) ? redirect : { name: 'dashboard' },
    )
  } catch (error) {
    if (error instanceof ApiError && error.status === 422) {
      errors.value = error.errors
    } else if (error instanceof ApiError && error.status === 403) {
      message.value = t.value.login.clientAccount
    } else if (error instanceof ApiError && error.status === 429) {
      message.value = t.value.login.tooManyAttempts
    } else {
      message.value = t.value.login.failed
    }
  } finally {
    isSubmitting.value = false
  }
}

function useAccount(accountEmail: string): void {
  email.value = accountEmail
  password.value = demo.value?.password ?? ''
  void submit()
}
</script>

<template>
  <main class="flex min-h-screen items-center justify-center bg-slate-100 px-4 py-10">
    <div class="w-full max-w-md">
      <div class="mb-6 flex items-center justify-between gap-2.5">
        <div class="flex items-center gap-2.5">
          <img src="/favicon.svg" alt="" class="size-8" />
          <span class="text-xl font-semibold text-slate-900">BrokerDesk</span>
        </div>
        <LocaleSwitcher />
      </div>

      <div class="card p-6 sm:p-8">
        <h1 class="text-lg font-semibold text-slate-900">{{ t.login.title }}</h1>
        <p class="mt-1 mb-5 text-sm text-slate-500">{{ t.login.subtitle }}</p>

        <AlertMessage :message="message" />

        <form class="space-y-4" novalidate @submit.prevent="submit">
          <FormField :label="t.login.email" name="email" :errors="errors">
            <input
              id="email"
              v-model="email"
              class="input"
              type="email"
              autocomplete="username"
              required
            />
          </FormField>
          <FormField :label="t.login.password" name="password" :errors="errors">
            <input
              id="password"
              v-model="password"
              class="input"
              type="password"
              autocomplete="current-password"
              required
            />
          </FormField>
          <button type="submit" class="btn-primary w-full" :disabled="isSubmitting">
            {{ isSubmitting ? t.login.submitting : t.login.submit }}
          </button>
        </form>
      </div>

      <section
        v-if="demo"
        class="card mt-4 border-brand-100 bg-brand-50 p-5"
        aria-labelledby="demo-title"
      >
        <h2 id="demo-title" class="text-sm font-semibold text-brand-700">
          {{ t.login.demoTitle }}
        </h2>
        <p class="mt-1 text-sm text-slate-600">{{ t.login.demoText }}</p>
        <div class="mt-3 flex flex-wrap gap-2">
          <button
            v-for="account in staffAccounts"
            :key="account.email"
            type="button"
            class="btn-secondary"
            :disabled="isSubmitting"
            @click="useAccount(account.email)"
          >
            {{ account.label }}
          </button>
          <a :href="PORTAL_URL" class="btn-secondary">{{ t.login.portalLink }}</a>
        </div>
      </section>

      <section v-if="demo" class="card mt-4 p-5" aria-labelledby="stack-title">
        <h2 id="stack-title" class="mb-3 text-sm font-semibold text-slate-900">
          {{ t.login.stackTitle }}
        </h2>
        <TechStack current="broker" compact />
        <a :href="SOURCE_URL" class="link mt-4 inline-block text-sm" rel="noopener">
          {{ t.login.sourceLink }}
        </a>
      </section>
    </div>
  </main>
</template>
