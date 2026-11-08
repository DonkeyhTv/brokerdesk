import { beforeEach, describe, expect, it, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { useAuthStore } from '@/stores/auth'
import AppLayout from '../AppLayout.vue'

vi.mock('@/api/http', async (original) => ({
  ...(await original<typeof import('@/api/http')>()),
  api: vi.fn().mockResolvedValue({ data: [], meta: { unread: 0 } }),
}))

vi.mock('vue-router', () => ({
  useRoute: () => ({ name: 'dashboard', fullPath: '/' }),
  useRouter: () => ({ push: vi.fn() }),
  RouterLink: { props: ['to'], template: '<a><slot /></a>' },
}))

function signIn(isDemoAccount: boolean): void {
  useAuthStore().user = {
    id: 1,
    name: 'Demo Broker',
    email: 'broker@example.be',
    role: 'broker',
    brokerage: { id: 1, name: 'Cabinet' },
    client_id: null,
    is_demo_account: isDemoAccount,
  }
}

describe('AppLayout', () => {
  beforeEach(() => setActivePinia(createPinia()))

  it.each([
    [true, true],
    [false, false],
  ])('links to the technical overview for demo accounts only (%s)', (isDemoAccount, shown) => {
    signIn(isDemoAccount)
    const wrapper = mount(AppLayout)

    expect(wrapper.text().includes('Comment c’est construit')).toBe(shown)
  })
})
