<script setup lang="ts">
import { RouterLink } from 'vue-router'
</script>

<template>
  <div class="py-16 text-center">
    <h1 class="text-2xl font-semibold text-slate-900">Page introuvable</h1>
    <p class="mt-2 text-sm text-slate-500">Cette page n'existe pas, ou vous n'y avez pas accès.</p>
    <RouterLink :to="{ name: 'dashboard' }" class="btn-primary mt-6">
      Retour au tableau de bord
    </RouterLink>
  </div>
</template>
