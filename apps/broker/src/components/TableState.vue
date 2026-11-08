<script setup lang="ts">
import { t } from '@/i18n'

defineProps<{
  isLoading: boolean
  isEmpty: boolean
  columns: number
  emptyMessage?: string
}>()
</script>

<template>
  <tr v-if="isEmpty">
    <td :colspan="columns" class="px-4 py-12 text-center text-sm text-slate-500" role="status">
      {{ isLoading ? t.common.loading : (emptyMessage ?? t.common.noResults) }}
    </td>
  </tr>
</template>
