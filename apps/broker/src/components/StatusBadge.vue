<script setup lang="ts">
import { computed } from 'vue'
import { t } from '@/i18n'
import type { ClaimStatus, ContractStatus } from '@/api/types'

const props = defineProps<{ status: ClaimStatus | ContractStatus }>()

const STYLES: Record<ClaimStatus | ContractStatus, string> = {
  active: 'bg-emerald-50 text-emerald-800 ring-emerald-200',
  suspended: 'bg-amber-50 text-amber-800 ring-amber-200',
  cancelled: 'bg-slate-100 text-slate-700 ring-slate-200',
  expired: 'bg-slate-100 text-slate-700 ring-slate-200',
  open: 'bg-amber-50 text-amber-800 ring-amber-200',
  in_review: 'bg-sky-50 text-sky-800 ring-sky-200',
  settled: 'bg-emerald-50 text-emerald-800 ring-emerald-200',
  rejected: 'bg-red-50 text-red-800 ring-red-200',
}

const label = computed(
  () =>
    ({ ...t.value.labels.contractStatuses, ...t.value.labels.claimStatuses })[
      props.status
    ] satisfies string,
)
</script>

<template>
  <span
    class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium whitespace-nowrap ring-1 ring-inset"
    :class="STYLES[status]"
  >
    {{ label }}
  </span>
</template>
