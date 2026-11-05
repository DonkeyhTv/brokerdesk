import { reactive, ref, shallowRef, watch } from 'vue'
import { api, type QueryValue } from '@/api/http'
import type { PageMeta, Paginated } from '@/api/types'

const SEARCH_DEBOUNCE_MS = 250

/**
 * A paginated API list: filters, sort and page in, rows out. Filter changes
 * go back to the first page, and a late answer never replaces a newer one.
 */
export function useList<T>(
  path: string,
  initialFilters: Record<string, string> = {},
  initialSort = '',
) {
  const filters = reactive<Record<string, string>>({ ...initialFilters })
  const sort = ref(initialSort)
  const page = ref(1)
  const rows = shallowRef<T[]>([])
  const meta = ref<PageMeta | null>(null)
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  let latestRequest = 0
  let debounce: ReturnType<typeof setTimeout> | undefined

  function query(): Record<string, QueryValue> {
    const params: Record<string, QueryValue> = { page: page.value, sort: sort.value }

    for (const [key, value] of Object.entries(filters)) {
      params[`filter[${key}]`] = value
    }

    return params
  }

  async function load(): Promise<void> {
    const request = ++latestRequest
    isLoading.value = true
    error.value = null

    try {
      const response = await api<Paginated<T>>(path, { query: query() })

      if (request === latestRequest) {
        rows.value = response.data
        meta.value = response.meta
      }
    } catch (caught) {
      if (request === latestRequest) {
        error.value = caught instanceof Error ? caught.message : 'Une erreur est survenue.'
      }
    } finally {
      if (request === latestRequest) {
        isLoading.value = false
      }
    }
  }

  function toggleSort(field: string): void {
    sort.value = sort.value === field ? `-${field}` : field
  }

  /** Changing the page already loads through its watcher: never load twice. */
  function backToFirstPage(): void {
    if (page.value === 1) {
      void load()
    } else {
      page.value = 1
    }
  }

  watch(filters, () => {
    clearTimeout(debounce)
    debounce = setTimeout(backToFirstPage, SEARCH_DEBOUNCE_MS)
  })

  watch(sort, backToFirstPage)

  watch(page, () => void load())

  void load()

  return { filters, sort, page, rows, meta, isLoading, error, load, query, toggleSort }
}
