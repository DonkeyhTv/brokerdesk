import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { api, ApiError, buildUrl, onUnauthenticated } from '../http'

function jsonResponse(status: number, body: unknown = {}): Response {
  return new Response(JSON.stringify(body), { status })
}

describe('buildUrl', () => {
  it('prefixes the API version and leaves out empty values', () => {
    expect(buildUrl('/clients', { page: 2, 'filter[search]': '', sort: null })).toBe(
      '/api/v1/clients?page=2',
    )
    expect(buildUrl('/clients')).toBe('/api/v1/clients')
  })
})

describe('api', () => {
  const fetchMock = vi.fn()

  beforeEach(() => {
    fetchMock.mockReset()
    vi.stubGlobal('fetch', fetchMock)
    document.cookie = 'XSRF-TOKEN=abc%3D; path=/'
  })

  afterEach(() => {
    vi.unstubAllGlobals()
    onUnauthenticated(() => {})
  })

  it('sends the decoded CSRF token on writes, as JSON', async () => {
    fetchMock.mockResolvedValue(jsonResponse(200, { data: 1 }))

    await expect(api('/clients', { method: 'POST', body: { a: 1 } })).resolves.toEqual({ data: 1 })

    const [url, init] = fetchMock.mock.calls[0]!
    expect(url).toBe('/api/v1/clients')
    expect(init.headers['X-XSRF-TOKEN']).toBe('abc=')
    expect(init.headers['Content-Type']).toBe('application/json')
    expect(init.body).toBe('{"a":1}')
  })

  it('lets the browser set the content type of an upload', async () => {
    fetchMock.mockResolvedValue(jsonResponse(201))
    const body = new FormData()

    await api('/contracts/1/documents', { method: 'POST', body })

    const init = fetchMock.mock.calls[0]![1]
    expect(init.headers['Content-Type']).toBeUndefined()
    expect(init.body).toBe(body)
  })

  it('does not send a CSRF token on reads', async () => {
    fetchMock.mockResolvedValue(jsonResponse(200))

    await api('/clients')

    expect(fetchMock.mock.calls[0]![1].headers['X-XSRF-TOKEN']).toBeUndefined()
  })

  it('exposes validation errors', async () => {
    fetchMock.mockResolvedValue(jsonResponse(422, { message: 'Invalid', errors: { email: ['x'] } }))

    const error = await api('/clients', { method: 'POST', body: {} }).catch((caught) => caught)

    expect(error).toBeInstanceOf(ApiError)
    expect(error.status).toBe(422)
    expect(error.errors).toEqual({ email: ['x'] })
  })

  it('reports an expired session', async () => {
    const handler = vi.fn()
    onUnauthenticated(handler)
    fetchMock.mockResolvedValue(jsonResponse(401, { message: 'Unauthenticated.' }))

    await expect(api('/me')).rejects.toBeInstanceOf(ApiError)

    expect(handler).toHaveBeenCalledOnce()
  })

  it('refreshes an expired CSRF token and tries once more', async () => {
    fetchMock
      .mockResolvedValueOnce(jsonResponse(419))
      .mockResolvedValueOnce(new Response(null, { status: 204 }))
      .mockResolvedValueOnce(jsonResponse(200, { ok: true }))

    await expect(api('/clients', { method: 'POST', body: {} })).resolves.toEqual({ ok: true })

    expect(fetchMock.mock.calls[1]![0]).toBe('/sanctum/csrf-cookie')
    expect(fetchMock).toHaveBeenCalledTimes(3)
  })

  it('answers undefined on 204', async () => {
    fetchMock.mockResolvedValue(new Response(null, { status: 204 }))

    await expect(api('/clients/1', { method: 'DELETE' })).resolves.toBeUndefined()
  })
})
