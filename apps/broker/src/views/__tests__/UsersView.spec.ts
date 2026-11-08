import { beforeEach, describe, expect, it, vi } from 'vitest'
import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import type { User } from '@/api/types'
import { useAuthStore } from '@/stores/auth'
import UsersView from '../UsersView.vue'

const apiMock = vi.hoisted(() => vi.fn())

vi.mock('@/api/http', async (original) => ({
  ...(await original<typeof import('@/api/http')>()),
  api: apiMock,
}))

function member(id: number, name: string, isDemoAccount: boolean): User {
  return {
    id,
    name,
    email: `${name.toLowerCase()}@example.be`,
    role: 'broker',
    brokerage: { id: 1, name: 'Cabinet' },
    client_id: null,
    is_demo_account: isDemoAccount,
  }
}

describe('UsersView', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    apiMock.mockReset()
    useAuthStore().user = { ...member(1, 'Admin', true), role: 'admin' }
  })

  it('locks the demo accounts and keeps the others editable', async () => {
    apiMock.mockResolvedValue({
      data: [member(2, 'Demo', true), member(3, 'Nora', false)],
      meta: { current_page: 1, last_page: 1, per_page: 25, total: 2, from: 1, to: 2 },
    })
    const wrapper = mount(UsersView)
    await flushPromises()

    const [demo, nora] = wrapper.findAll('tbody tr').filter((row) => row.text().includes('@'))

    expect(demo!.text()).toContain('Compte de démo')
    expect(demo!.text()).not.toContain('Modifier')
    expect(nora!.text()).toContain('Modifier')
    expect(nora!.text()).toContain('Retirer')
  })
})
