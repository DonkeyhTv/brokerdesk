<script setup lang="ts">
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
      {{ isLoading ? 'Chargement…' : (emptyMessage ?? 'Aucun résultat.') }}
    </td>
  </tr>
</template>
