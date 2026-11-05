import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { ApiError } from '@/api/http'
import { useAuthStore } from '../auth'

const apiMock = vi.hoisted(() => vi.fn())

vi.mock('@/api/http', async (original) => ({
  ...(await original<typeof import('@/api/http')>()),
  api: apiMock,
}))

const broker = { id: 1, name: 'Demo', role: 'broker' }

describe('auth store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    apiMock.mockReset()
  })

  it('loads the current user once', async () => {
    apiMock.mockResolvedValue({ data: broker })
    const auth = useAuthStore()

    await auth.ensureLoaded()
    await auth.ensureLoaded()

    expect(auth.user).toEqual(broker)
    expect(apiMock).toHaveBeenCalledOnce()
  })

  it('treats a 401 as a guest', async () => {
    apiMock.mockRejectedValue(new ApiError(401, 'Unauthenticated.'))
    const auth = useAuthStore()

    await auth.ensureLoaded()

    expect(auth.user).toBeNull()
  })

  it('does not hide other failures', async () => {
    apiMock.mockRejectedValue(new ApiError(500, 'Boom'))

    await expect(useAuthStore().ensureLoaded()).rejects.toBeInstanceOf(ApiError)
  })

  it('knows admins', async () => {
    apiMock.mockResolvedValue({ data: { ...broker, role: 'admin' } })
    const auth = useAuthStore()

    await auth.login('a@example.be', 'password')

    expect(auth.isAdmin).toBe(true)
    expect(apiMock).toHaveBeenCalledWith('/auth/session', {
      method: 'POST',
      body: { email: 'a@example.be', password: 'password' },
    })
  })

  it('forgets the user on logout', async () => {
    apiMock.mockResolvedValue({ data: broker })
    const auth = useAuthStore()
    await auth.login('a@example.be', 'password')

    await auth.logout()

    expect(auth.user).toBeNull()
  })
})
