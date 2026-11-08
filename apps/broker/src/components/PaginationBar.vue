<script setup lang="ts">
import type { PageMeta } from '@/api/types'
import { t } from '@/i18n'

defineProps<{ meta: PageMeta | null }>()
const page = defineModel<number>({ required: true })
</script>

<template>
  <nav
    v-if="meta && meta.total > 0"
    :aria-label="t.common.pagination"
    class="flex items-center justify-between gap-3 border-t border-slate-200 px-4 py-3 text-sm text-slate-600"
  >
    <p>{{ t.common.range(meta.from, meta.to, meta.total) }}</p>
    <div class="flex items-center gap-2">
      <button
        type="button"
        class="btn-secondary"
        :disabled="meta.current_page <= 1"
        @click="page = meta.current_page - 1"
      >
        {{ t.common.previous }}
      </button>
      <span>{{ t.common.page(meta.current_page, meta.last_page) }}</span>
      <button
        type="button"
        class="btn-secondary"
        :disabled="meta.current_page >= meta.last_page"
        @click="page = meta.current_page + 1"
      >
        {{ t.common.next }}
      </button>
    </div>
  </nav>
</template>
