<script setup lang="ts">
import { RouterLink } from 'vue-router'
import { t } from '@/i18n'
</script>

<template>
  <div class="py-16 text-center">
    <h1 class="text-2xl font-semibold text-slate-900">{{ t.notFound.title }}</h1>
    <p class="mt-2 text-sm text-slate-500">{{ t.notFound.text }}</p>
    <RouterLink :to="{ name: 'dashboard' }" class="btn-primary mt-6">
      {{ t.notFound.back }}
    </RouterLink>
  </div>
</template>
