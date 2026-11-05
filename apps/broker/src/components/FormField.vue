<script setup lang="ts">
defineProps<{ label: string; name: string; errors?: Record<string, string[]>; hint?: string }>()
</script>

<template>
  <div>
    <label :for="name" class="mb-1 block text-sm font-medium text-slate-700">{{ label }}</label>
    <slot />
    <p v-if="hint" class="mt-1 text-xs text-slate-500">{{ hint }}</p>
    <p v-if="errors?.[name]" :id="`${name}-error`" role="alert" class="mt-1 text-sm text-red-700">
      {{ errors[name]?.[0] }}
    </p>
  </div>
</template>
