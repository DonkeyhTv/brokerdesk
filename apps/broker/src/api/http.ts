export class ApiError extends Error {
  constructor(
    public readonly status: number,
    message: string,
    public readonly errors: Record<string, string[]> = {},
  ) {
    super(message)
    this.name = 'ApiError'
  }
}

export type QueryValue = string | number | boolean | null | undefined

interface RequestOptions {
  method?: 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE'
  body?: unknown
  query?: Record<string, QueryValue>
}

let unauthenticatedHandler: () => void = () => {}

export function onUnauthenticated(handler: () => void): void {
  unauthenticatedHandler = handler
}

export function buildUrl(path: string, query: Record<string, QueryValue> = {}): string {
  const params = new URLSearchParams()

  for (const [key, value] of Object.entries(query)) {
    if (value !== null && value !== undefined && value !== '') {
      params.set(key, String(value))
    }
  }

  const queryString = params.toString()

  return `/api/v1${path}${queryString ? `?${queryString}` : ''}`
}

function xsrfToken(): string | null {
  const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/)

  return match?.[1] ? decodeURIComponent(match[1]) : null
}

async function refreshCsrfCookie(): Promise<void> {
  await fetch('/sanctum/csrf-cookie', { credentials: 'same-origin' })
}

async function send(url: string, method: string, body: unknown): Promise<Response> {
  const isForm = body instanceof FormData
  const headers: Record<string, string> = { Accept: 'application/json' }

  if (body !== undefined && !isForm) {
    headers['Content-Type'] = 'application/json'
  }

  if (method !== 'GET') {
    if (xsrfToken() === null) {
      await refreshCsrfCookie()
    }

    headers['X-XSRF-TOKEN'] = xsrfToken() ?? ''
  }

  return fetch(url, {
    method,
    headers,
    credentials: 'same-origin',
    body: body === undefined ? undefined : isForm ? body : JSON.stringify(body),
  })
}

export async function api<T = void>(path: string, options: RequestOptions = {}): Promise<T> {
  const { method = 'GET', body, query } = options
  const url = buildUrl(path, query)

  let response = await send(url, method, body)

  // An expired CSRF token: get a new one and try once more.
  if (response.status === 419) {
    await refreshCsrfCookie()
    response = await send(url, method, body)
  }

  if (response.status === 204) {
    return undefined as T
  }

  const payload = await response.json().catch(() => null)

  if (!response.ok) {
    if (response.status === 401) {
      unauthenticatedHandler()
    }

    throw new ApiError(
      response.status,
      payload?.message ?? 'Une erreur est survenue.',
      payload?.errors,
    )
  }

  return payload as T
}
