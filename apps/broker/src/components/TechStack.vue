<script setup lang="ts">
import { STACK, type StackLayerId } from '@/utils/stack'

defineProps<{
  /** The layer the visitor is using right now. */
  current?: StackLayerId
  /** Names and tools only, for the login page. */
  compact?: boolean
}>()
</script>

<template>
  <dl :class="compact ? 'space-y-2' : 'grid gap-4 sm:grid-cols-2'">
    <div
      v-for="layer in STACK"
      :key="layer.id"
      :class="compact ? 'flex flex-wrap items-baseline gap-x-2 gap-y-1' : 'card p-5'"
    >
      <dt
        class="font-semibold text-slate-900"
        :class="compact ? 'w-full text-xs sm:w-32 sm:shrink-0' : 'text-sm'"
      >
        {{ layer.name }}
        <span
          v-if="layer.id === current"
          class="rounded-full bg-brand-100 px-1.5 py-0.5 text-[10px] font-medium whitespace-nowrap text-brand-700"
          :class="compact ? 'mt-0.5 block w-fit' : 'ml-1'"
        >
          vous êtes ici
        </span>
      </dt>
      <dd class="flex flex-wrap gap-1" :class="compact ? 'sm:flex-1' : 'mt-2'">
        <span
          v-for="tool in layer.tools"
          :key="tool"
          class="rounded-md bg-white px-1.5 py-0.5 text-xs font-medium text-slate-700 ring-1 ring-slate-200"
        >
          {{ tool }}
        </span>
      </dd>
      <dd v-if="!compact" class="mt-3 text-sm text-slate-600">{{ layer.role }}</dd>
    </div>
  </dl>
</template>
