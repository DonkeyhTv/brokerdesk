<script setup lang="ts">
defineProps<{ label: string; value?: string | number | null }>()
</script>

<template>
  <div>
    <dt class="text-xs font-medium tracking-wide text-slate-500 uppercase">{{ label }}</dt>
    <dd class="mt-1 text-sm text-slate-900">
      <slot>{{ value ?? '—' }}</slot>
    </dd>
  </div>
</template>
