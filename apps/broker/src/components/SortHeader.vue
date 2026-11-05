<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{ label: string; field: string; sort: string }>()
defineEmits<{ toggle: [field: string] }>()

const direction = computed(() => {
  if (props.sort === props.field) return 'ascending'
  if (props.sort === `-${props.field}`) return 'descending'

  return 'none'
})
</script>

<template>
  <th scope="col" class="table-head" :aria-sort="direction">
    <button
      type="button"
      class="inline-flex items-center gap-1 tracking-wide uppercase hover:text-slate-900"
      @click="$emit('toggle', field)"
    >
      {{ label }}
      <span aria-hidden="true" class="text-slate-400">
        {{ direction === 'ascending' ? '↑' : direction === 'descending' ? '↓' : '↕' }}
      </span>
    </button>
  </th>
</template>
