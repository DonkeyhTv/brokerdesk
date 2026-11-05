import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { nextTick } from 'vue'
import { useList } from '../useList'

const apiMock = vi.hoisted(() => vi.fn())

vi.mock('@/api/http', async (original) => ({
  ...(await original<typeof import('@/api/http')>()),
  api: apiMock,
}))

function page(rows: number[]) {
  return { data: rows, meta: { current_page: 1, last_page: 2, from: 1, to: 1, total: 2 } }
}

describe('useList', () => {
  beforeEach(() => {
    apiMock.mockReset()
    apiMock.mockResolvedValue(page([1]))
    vi.useFakeTimers()
  })

  afterEach(() => vi.useRealTimers())

  it('loads with its filters and sort', async () => {
    const list = useList('/clients', { search: 'x' }, 'name')
    await vi.runAllTimersAsync()

    expect(apiMock).toHaveBeenCalledWith('/clients', {
      query: { page: 1, sort: 'name', 'filter[search]': 'x' },
    })
    expect(list.rows.value).toEqual([1])
  })

  it('debounces filter changes and goes back to the first page', async () => {
    const list = useList('/clients', { search: '' })
    await vi.runAllTimersAsync()
    list.page.value = 2
    await vi.runAllTimersAsync()
    apiMock.mockClear()

    list.filters.search = 'a'
    await nextTick()
    list.filters.search = 'ab'
    await vi.runAllTimersAsync()

    expect(apiMock).toHaveBeenCalledTimes(1)
    expect(apiMock.mock.calls[0]![1].query).toMatchObject({ page: 1, 'filter[search]': 'ab' })
  })

  it('toggles the sort direction', async () => {
    const list = useList('/clients', {}, 'name')
    await vi.runAllTimersAsync()

    list.toggleSort('name')
    expect(list.sort.value).toBe('-name')

    list.toggleSort('name')
    expect(list.sort.value).toBe('name')

    list.toggleSort('city')
    expect(list.sort.value).toBe('city')
  })

  it('ignores an answer that arrives after a newer one', async () => {
    let resolveFirst: (value: unknown) => void = () => {}
    apiMock.mockReturnValueOnce(new Promise((resolve) => (resolveFirst = resolve)))
    apiMock.mockResolvedValueOnce(page([2]))

    const list = useList('/clients')
    list.page.value = 2
    await vi.runAllTimersAsync()

    resolveFirst(page([1]))
    await vi.runAllTimersAsync()

    expect(list.rows.value).toEqual([2])
  })

  it('exposes errors', async () => {
    apiMock.mockRejectedValue(new Error('Boom'))

    const list = useList('/clients')
    await vi.runAllTimersAsync()

    expect(list.error.value).toBe('Boom')
    expect(list.isLoading.value).toBe(false)
  })
})
