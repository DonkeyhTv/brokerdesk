<script setup lang="ts">
import type { PageMeta } from '@/api/types'

defineProps<{ meta: PageMeta | null }>()
const page = defineModel<number>({ required: true })
</script>

<template>
  <nav
    v-if="meta && meta.total > 0"
    aria-label="Pagination"
    class="flex items-center justify-between gap-3 border-t border-slate-200 px-4 py-3 text-sm text-slate-600"
  >
    <p>{{ meta.from }}–{{ meta.to }} sur {{ meta.total }}</p>
    <div class="flex items-center gap-2">
      <button
        type="button"
        class="btn-secondary"
        :disabled="meta.current_page <= 1"
        @click="page = meta.current_page - 1"
      >
        Précédent
      </button>
      <span>Page {{ meta.current_page }} / {{ meta.last_page }}</span>
      <button
        type="button"
        class="btn-secondary"
        :disabled="meta.current_page >= meta.last_page"
        @click="page = meta.current_page + 1"
      >
        Suivant
      </button>
    </div>
  </nav>
</template>
