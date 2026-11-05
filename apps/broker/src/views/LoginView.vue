<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { api, ApiError } from '@/api/http'
import type { DemoAccounts } from '@/api/types'
import { useAuthStore } from '@/stores/auth'
import AlertMessage from '@/components/AlertMessage.vue'
import FormField from '@/components/FormField.vue'

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

onMounted(async () => {
  try {
    demo.value = await api<DemoAccounts>('/demo/accounts')
  } catch {
    // Demo mode is off: the page simply offers no shortcut.
  }
})

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
      message.value = 'Ce compte est un compte client : connectez-vous au portail client.'
    } else if (error instanceof ApiError && error.status === 429) {
      message.value = 'Trop de tentatives. Réessayez dans une minute.'
    } else {
      message.value = 'La connexion a échoué. Réessayez.'
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
      <div class="mb-6 flex items-center justify-center gap-2.5">
        <img src="/favicon.svg" alt="" class="size-8" />
        <span class="text-xl font-semibold text-slate-900">BrokerDesk</span>
      </div>

      <div class="card p-6 sm:p-8">
        <h1 class="text-lg font-semibold text-slate-900">Espace courtier</h1>
        <p class="mt-1 mb-5 text-sm text-slate-500">
          Gérez vos clients, leurs contrats et leurs sinistres.
        </p>

        <AlertMessage :message="message" />

        <form class="space-y-4" novalidate @submit.prevent="submit">
          <FormField label="Adresse e-mail" name="email" :errors="errors">
            <input
              id="email"
              v-model="email"
              class="input"
              type="email"
              autocomplete="username"
              required
            />
          </FormField>
          <FormField label="Mot de passe" name="password" :errors="errors">
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
            {{ isSubmitting ? 'Connexion…' : 'Se connecter' }}
          </button>
        </form>
      </div>

      <section
        v-if="demo"
        class="card mt-4 border-brand-100 bg-brand-50 p-5"
        aria-labelledby="demo-title"
      >
        <h2 id="demo-title" class="text-sm font-semibold text-brand-700">
          Vous évaluez l'application ?
        </h2>
        <p class="mt-1 text-sm text-slate-600">
          Connectez-vous en un clic. Les données sont fictives et réinitialisées chaque nuit.
        </p>
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
          <a :href="PORTAL_URL" class="btn-secondary">Portail client →</a>
        </div>
      </section>
    </div>
  </main>
</template>
