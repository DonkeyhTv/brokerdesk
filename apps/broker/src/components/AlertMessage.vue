<script setup lang="ts">
defineProps<{ message: string | null; tone?: 'error' | 'success' }>()
</script>

<template>
  <p
    v-if="message"
    role="alert"
    class="mb-4 rounded-lg border px-4 py-3 text-sm"
    :class="
      tone === 'success'
        ? 'border-emerald-200 bg-emerald-50 text-emerald-800'
        : 'border-red-200 bg-red-50 text-red-800'
    "
  >
    {{ message }}
  </p>
</template>
