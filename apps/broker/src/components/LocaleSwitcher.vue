<script setup lang="ts">
import { LOCALES, useI18n } from '@/i18n'

const { t, locale, setLocale } = useI18n()
</script>

<template>
  <div
    role="group"
    :aria-label="t.app.language"
    class="inline-flex rounded-lg ring-1 ring-slate-300"
  >
    <button
      v-for="option in LOCALES"
      :key="option"
      type="button"
      class="px-2.5 py-1.5 text-xs font-semibold uppercase first:rounded-l-lg last:rounded-r-lg"
      :class="option === locale ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100'"
      :aria-pressed="option === locale"
      :lang="option"
      @click="setLocale(option)"
    >
      {{ option }}
    </button>
  </div>
</template>
