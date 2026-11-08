import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import type { RouteLocationNormalized } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { authGuard } from '../guard'

vi.mock('@/api/http', async (original) => ({
  ...(await original<typeof import('@/api/http')>()),
  api: vi
    .fn()
    .mockRejectedValue(new (await original<typeof import('@/api/http')>()).ApiError(401, '')),
}))

function route(partial: Partial<RouteLocationNormalized>): RouteLocationNormalized {
  return { meta: {}, fullPath: '/', ...partial } as RouteLocationNormalized
}

async function guard(to: RouteLocationNormalized) {
  return (authGuard as (to: RouteLocationNormalized) => unknown)(to)
}

function signIn(role: 'admin' | 'broker'): void {
  useAuthStore().user = {
    id: 1,
    name: 'x',
    email: 'x@example.be',
    role,
    brokerage: { id: 1, name: 'x' },
    client_id: null,
    is_demo_account: false,
  }
}

describe('authGuard', () => {
  beforeEach(() => setActivePinia(createPinia()))

  it('sends guests to the login page and remembers where they were going', async () => {
    await expect(guard(route({ name: 'clients', fullPath: '/clients?page=2' }))).resolves.toEqual({
      name: 'login',
      query: { redirect: '/clients?page=2' },
    })
  })

  it('lets guests reach public pages', async () => {
    await expect(guard(route({ name: 'login', meta: { public: true } }))).resolves.toBe(true)
  })

  it('takes signed-in people away from the login page', async () => {
    await guard(route({ name: 'login', meta: { public: true } }))
    signIn('broker')

    await expect(guard(route({ name: 'login', meta: { public: true } }))).resolves.toEqual({
      name: 'dashboard',
    })
  })

  it('keeps brokers away from admin pages', async () => {
    await guard(route({ name: 'clients' }))
    signIn('broker')

    await expect(guard(route({ name: 'users', meta: { admin: true } }))).resolves.toEqual({
      name: 'dashboard',
    })
  })

  it('opens admin pages to admins', async () => {
    await guard(route({ name: 'clients' }))
    signIn('admin')

    await expect(guard(route({ name: 'users', meta: { admin: true } }))).resolves.toBe(true)
  })
})
