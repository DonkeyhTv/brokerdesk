<script setup lang="ts">
import PageHeader from '@/components/PageHeader.vue'
import TechStack from '@/components/TechStack.vue'
import { SOURCE_URL } from '@/utils/stack'

const decisions = [
  {
    title: 'Chaque cabinet est isolé deux fois',
    text: 'Les requêtes et les règles d’accès filtrent par cabinet, et la base de données refuse elle-même un contrat rattaché au client d’un autre cabinet.',
  },
  {
    title: 'Deux connexions pour deux publics',
    text: 'Cet espace utilise une session protégée contre le CSRF. Le portail garde la clé du client sur son serveur, hors de portée des scripts du navigateur.',
  },
  {
    title: 'Ce qui est interdit reste invisible',
    text: 'Un dossier auquel vous n’avez pas accès répond « introuvable » plutôt que « interdit », pour ne pas révéler qu’il existe.',
  },
  {
    title: 'Les règles belges sont dans le code',
    text: 'Numéros d’entreprise vérifiés par leur chiffre de contrôle, préavis de trois mois avant la reconduction tacite, montants stockés en centimes.',
  },
]
</script>

<template>
  <PageHeader
    title="Comment c'est construit"
    subtitle="Les technologies et les choix derrière cette démonstration."
  >
    <a :href="SOURCE_URL" class="btn-primary" rel="noopener">Code source sur GitHub</a>
    <a href="/docs/api" class="btn-secondary">Documentation de l'API</a>
  </PageHeader>

  <TechStack current="broker" />

  <h2 class="mt-10 mb-4 text-lg font-semibold text-slate-900">Choix techniques</h2>
  <div class="grid gap-4 sm:grid-cols-2">
    <section v-for="decision in decisions" :key="decision.title" class="card p-5">
      <h3 class="text-sm font-semibold text-slate-900">{{ decision.title }}</h3>
      <p class="mt-2 text-sm text-slate-600">{{ decision.text }}</p>
    </section>
  </div>
</template>
