import { beforeEach, describe, expect, it, vi } from 'vitest'
import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { ApiError } from '@/api/http'
import LoginView from '../LoginView.vue'

const apiMock = vi.hoisted(() => vi.fn())
const push = vi.hoisted(() => vi.fn())
const query = vi.hoisted(() => ({ value: {} as Record<string, string> }))

vi.mock('@/api/http', async (original) => ({
  ...(await original<typeof import('@/api/http')>()),
  api: apiMock,
}))

vi.mock('vue-router', () => ({
  useRoute: () => ({ query: query.value }),
  useRouter: () => ({ push }),
}))

const demo = {
  password: 'password',
  accounts: [
    { label: 'Broker', app: 'broker', email: 'broker@example.be' },
    { label: 'Individual client', app: 'portal', email: 'client@example.be' },
  ],
}

function respond(login: () => unknown, demoAccounts: unknown = demo): void {
  apiMock.mockImplementation((path: string) => {
    if (path === '/demo/accounts') {
      return demoAccounts ? Promise.resolve(demoAccounts) : Promise.reject(new ApiError(404, ''))
    }

    return Promise.resolve().then(login)
  })
}

async function mountLogin() {
  const wrapper = mount(LoginView)
  await flushPromises()

  return wrapper
}

describe('LoginView', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    apiMock.mockReset()
    push.mockReset()
    query.value = {}
  })

  it('signs in and opens the dashboard', async () => {
    respond(() => ({ data: { id: 1, role: 'broker' } }))
    const wrapper = await mountLogin()

    await wrapper.get('#email').setValue('broker@example.be')
    await wrapper.get('#password').setValue('password')
    await wrapper.get('form').trigger('submit')
    await flushPromises()

    expect(apiMock).toHaveBeenCalledWith('/auth/session', {
      method: 'POST',
      body: { email: 'broker@example.be', password: 'password' },
    })
    expect(push).toHaveBeenCalledWith({ name: 'dashboard' })
  })

  it.each([
    ['/clients?page=2', '/clients?page=2'],
    ['https://evil.example', { name: 'dashboard' }],
    ['//evil.example', { name: 'dashboard' }],
  ])(
    'after signing in with redirect %s, goes to a same-site page only',
    async (redirect, target) => {
      query.value = { redirect }
      respond(() => ({ data: { id: 1, role: 'broker' } }))
      const wrapper = await mountLogin()

      await wrapper.get('form').trigger('submit')
      await flushPromises()

      expect(push).toHaveBeenCalledWith(target)
    },
  )

  it('shows field errors', async () => {
    respond(() => {
      throw new ApiError(422, 'Invalid', { email: ['These credentials do not match.'] })
    })
    const wrapper = await mountLogin()

    await wrapper.get('form').trigger('submit')
    await flushPromises()

    expect(wrapper.text()).toContain('These credentials do not match.')
    expect(push).not.toHaveBeenCalled()
  })

  it.each([
    [403, 'portail client'],
    [429, 'Trop de tentatives'],
    [500, 'La connexion a échoué'],
  ])('explains a %i', async (status, text) => {
    respond(() => {
      throw new ApiError(status, 'x')
    })
    const wrapper = await mountLogin()

    await wrapper.get('form').trigger('submit')
    await flushPromises()

    expect(wrapper.text()).toContain(text)
  })

  it('offers one-click demo accounts for this application only', async () => {
    respond(() => ({ data: { id: 1, role: 'broker' } }))
    const wrapper = await mountLogin()

    const buttons = wrapper.findAll('section button')
    expect(buttons.map((button) => button.text())).toEqual(['Broker'])

    await buttons[0]!.trigger('click')
    await flushPromises()

    expect(apiMock).toHaveBeenCalledWith('/auth/session', {
      method: 'POST',
      body: { email: 'broker@example.be', password: 'password' },
    })
  })

  it('offers nothing when demo mode is off', async () => {
    respond(() => ({}), null)
    const wrapper = await mountLogin()

    expect(wrapper.find('section').exists()).toBe(false)
  })
})
