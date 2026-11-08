<script setup lang="ts">
import { t } from '@/i18n'
import PageHeader from '@/components/PageHeader.vue'
import TechStack from '@/components/TechStack.vue'
import { SOURCE_URL } from '@/utils/stack'
</script>

<template>
  <PageHeader :title="t.about.title" :subtitle="t.about.subtitle">
    <a :href="SOURCE_URL" class="btn-primary" rel="noopener">{{ t.about.source }}</a>
    <a href="/docs/api" class="btn-secondary">{{ t.about.apiDocs }}</a>
  </PageHeader>

  <TechStack current="broker" />

  <h2 class="mt-10 mb-4 text-lg font-semibold text-slate-900">{{ t.about.decisionsTitle }}</h2>
  <div class="grid gap-4 sm:grid-cols-2">
    <section v-for="decision in t.about.decisions" :key="decision.title" class="card p-5">
      <h3 class="text-sm font-semibold text-slate-900">{{ decision.title }}</h3>
      <p class="mt-2 text-sm text-slate-600">{{ decision.text }}</p>
    </section>
  </div>
</template>
