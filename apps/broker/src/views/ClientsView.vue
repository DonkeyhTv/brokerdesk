<script setup lang="ts">
import { RouterLink } from 'vue-router'
import type { Client } from '@/api/types'
import { useList } from '@/composables/useList'
import { CLIENT_TYPES } from '@/utils/labels'
import AlertMessage from '@/components/AlertMessage.vue'
import PageHeader from '@/components/PageHeader.vue'
import PaginationBar from '@/components/PaginationBar.vue'
import SortHeader from '@/components/SortHeader.vue'
import TableState from '@/components/TableState.vue'

const list = useList<Client>('/clients', { search: '', type: '' }, 'name')
</script>

<template>
  <PageHeader title="Clients" subtitle="Particuliers et entreprises">
    <RouterLink :to="{ name: 'client-new' }" class="btn-primary">Nouveau client</RouterLink>
  </PageHeader>

  <div class="mb-4 flex flex-wrap gap-3">
    <input
      v-model="list.filters.search"
      type="search"
      class="input max-w-sm"
      placeholder="Nom, e-mail ou n° d'entreprise"
      aria-label="Rechercher un client"
    />
    <select v-model="list.filters.type" class="input w-auto" aria-label="Filtrer par type">
      <option value="">Tous les types</option>
      <option v-for="(label, value) in CLIENT_TYPES" :key="value" :value="value">
        {{ label }}
      </option>
    </select>
  </div>

  <AlertMessage :message="list.error.value" />

  <div class="card overflow-hidden">
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-slate-200">
        <thead class="bg-slate-50">
          <tr>
            <SortHeader
              label="Nom"
              field="name"
              :sort="list.sort.value"
              @toggle="list.toggleSort"
            />
            <th scope="col" class="table-head">Type</th>
            <SortHeader
              label="Commune"
              field="city"
              :sort="list.sort.value"
              @toggle="list.toggleSort"
            />
            <th scope="col" class="table-head">Courtier</th>
            <th scope="col" class="table-head text-right">Contrats</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <TableState
            :is-loading="list.isLoading.value"
            :is-empty="list.rows.value.length === 0"
            :columns="5"
            empty-message="Aucun client ne correspond."
          />
          <tr v-for="client in list.rows.value" :key="client.id" class="hover:bg-slate-50">
            <td class="table-cell">
              <RouterLink :to="{ name: 'client', params: { id: client.id } }" class="link">
                {{ client.name }}
              </RouterLink>
              <span class="block text-xs text-slate-500">{{ client.email }}</span>
            </td>
            <td class="table-cell">{{ CLIENT_TYPES[client.type] }}</td>
            <td class="table-cell">{{ client.postal_code }} {{ client.city }}</td>
            <td class="table-cell">{{ client.broker?.name }}</td>
            <td class="table-cell text-right tabular-nums">{{ client.contracts_count }}</td>
          </tr>
        </tbody>
      </table>
    </div>
    <PaginationBar v-model="list.page.value" :meta="list.meta.value" />
  </div>
</template>
