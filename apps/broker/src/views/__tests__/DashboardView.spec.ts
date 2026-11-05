import { beforeEach, describe, expect, it, vi } from 'vitest'
import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import DashboardView from '../DashboardView.vue'

const apiMock = vi.hoisted(() => vi.fn())

vi.mock('@/api/http', async (original) => ({
  ...(await original<typeof import('@/api/http')>()),
  api: apiMock,
}))

const stubs = { RouterLink: { template: '<a><slot /></a>' } }

describe('DashboardView', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    apiMock.mockReset()
  })

  it('shows the renewal windows, key figures and premium by branch', async () => {
    apiMock.mockResolvedValue({
      expiring: { within_30: 7, from_31_to_60: 10, from_61_to_90: 6 },
      open_claims: 12,
      active_contracts: 66,
      annual_premium_cents: 11569941,
      premium_by_type: [{ type: 'motor', contracts: 20, annual_premium_cents: 4000000 }],
      next_expiries: [],
    })

    const wrapper = mount(DashboardView, { global: { stubs } })
    await flushPromises()

    const text = wrapper.text()
    expect(text).toContain('Dans 30 jours7')
    expect(text).toContain('Entre 31 et 60 jours10')
    expect(text).toContain('Sinistres en cours12')
    expect(text).toContain('Auto')
    expect(text).toContain('20 contrats')
  })

  it('says so when the dashboard cannot load', async () => {
    apiMock.mockRejectedValue(new Error('Boom'))

    const wrapper = mount(DashboardView, { global: { stubs } })
    await flushPromises()

    expect(wrapper.get('[role="alert"]').text()).toContain('n’a pas pu être chargé')
  })
})
