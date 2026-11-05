import { beforeEach, describe, expect, it, vi } from 'vitest'
import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { ApiError } from '@/api/http'
import { useAuthStore } from '@/stores/auth'
import ClientFormView from '../ClientFormView.vue'

const apiMock = vi.hoisted(() => vi.fn())
const push = vi.hoisted(() => vi.fn())

vi.mock('@/api/http', async (original) => ({
  ...(await original<typeof import('@/api/http')>()),
  api: apiMock,
}))

vi.mock('vue-router', () => ({
  useRouter: () => ({ push }),
  RouterLink: { template: '<a><slot /></a>' },
}))

function signIn(role: 'admin' | 'broker'): void {
  useAuthStore().user = {
    id: 1,
    name: 'x',
    email: 'x@example.be',
    role,
    brokerage: { id: 1, name: 'x' },
    client_id: null,
  }
}

describe('ClientFormView', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    apiMock.mockReset()
    push.mockReset()
  })

  it('sends only the fields of a company', async () => {
    signIn('broker')
    apiMock.mockResolvedValue({ data: { id: 7 } })
    const wrapper = mount(ClientFormView)
    await flushPromises()

    await wrapper.get('input[value="company"]').setValue(true)
    await wrapper.get('#company_name').setValue('Garage Maes BV')
    await wrapper.get('#enterprise_number').setValue('0123.456.749')
    await wrapper.get('form').trigger('submit')
    await flushPromises()

    const body = apiMock.mock.calls[0]![1].body
    expect(apiMock.mock.calls[0]![0]).toBe('/clients')
    expect(body).toMatchObject({ type: 'company', company_name: 'Garage Maes BV' })
    expect(body).not.toHaveProperty('first_name')
    expect(body).not.toHaveProperty('broker_id')
    expect(push).toHaveBeenCalledWith({ name: 'client', params: { id: 7 } })
  })

  it('shows the API validation errors next to their fields', async () => {
    signIn('broker')
    apiMock.mockRejectedValue(new ApiError(422, 'Invalid', { email: ['Adresse invalide.'] }))
    const wrapper = mount(ClientFormView)
    await flushPromises()

    await wrapper.get('form').trigger('submit')
    await flushPromises()

    expect(wrapper.get('#email-error').text()).toBe('Adresse invalide.')
    expect(push).not.toHaveBeenCalled()
  })

  it('lets an admin choose the broker', async () => {
    signIn('admin')
    apiMock.mockResolvedValue({ data: [{ id: 3, name: 'Nora' }] })
    const wrapper = mount(ClientFormView)
    await flushPromises()

    expect(wrapper.get('#broker_id').text()).toContain('Nora')
  })

  it('loads an existing client and updates it', async () => {
    signIn('broker')
    apiMock.mockResolvedValue({
      data: {
        id: 9,
        type: 'individual',
        first_name: 'Émilie',
        last_name: 'Lefèvre',
        email: 'e@example.be',
        street: 'Rue 1',
        postal_code: '4000',
        city: 'Liège',
      },
    })
    const wrapper = mount(ClientFormView, { props: { id: '9' } })
    await flushPromises()

    expect((wrapper.get('#first_name').element as HTMLInputElement).value).toBe('Émilie')

    await wrapper.get('form').trigger('submit')
    await flushPromises()

    expect(apiMock).toHaveBeenLastCalledWith(
      '/clients/9',
      expect.objectContaining({ method: 'PUT' }),
    )
  })
})
