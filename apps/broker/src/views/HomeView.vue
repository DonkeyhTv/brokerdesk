<script setup lang="ts">
import { onMounted, ref } from 'vue'

const apiStatus = ref('checking')

onMounted(async () => {
  try {
    const response = await fetch('/api/v1/health')
    apiStatus.value = response.ok ? (await response.json()).status : 'error'
  } catch {
    apiStatus.value = 'error'
  }
})
</script>

<template>
  <main>
    <h1>BrokerDesk – Espace courtier</h1>
    <p>API : {{ apiStatus }}</p>
  </main>
</template>
