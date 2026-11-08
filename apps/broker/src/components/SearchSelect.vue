<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'

export interface SearchOption {
  id: number
  label: string
}

const props = withDefaults(
  defineProps<{
    /** Matches the label of the surrounding FormField. */
    id: string
    search: (term: string) => Promise<SearchOption[]>
    /** Finds the label of a value set from outside, such as a link that preselects it. */
    resolve?: (id: number) => Promise<SearchOption | null>
    placeholder?: string
    disabled?: boolean
  }>(),
  { resolve: undefined, placeholder: 'Rechercher…', disabled: false },
)

const model = defineModel<number | ''>({ required: true })

const text = ref('')
const selectedLabel = ref('')
const options = ref<SearchOption[]>([])
const isOpen = ref(false)
const isLoading = ref(false)
const activeIndex = ref(-1)
let timer: ReturnType<typeof setTimeout> | undefined
let lastRequest = 0

const listId = computed(() => `${props.id}-options`)
const activeId = computed(() =>
  activeIndex.value >= 0 ? `${props.id}-option-${options.value[activeIndex.value]?.id}` : undefined,
)

async function load(term: string): Promise<void> {
  const request = ++lastRequest
  isLoading.value = true

  try {
    const results = await props.search(term)

    // A slower, older answer must not replace the results of what was typed since.
    if (request === lastRequest) {
      options.value = results
      activeIndex.value = results.length > 0 ? 0 : -1
    }
  } catch {
    if (request === lastRequest) {
      options.value = []
    }
  } finally {
    if (request === lastRequest) {
      isLoading.value = false
    }
  }
}

function onInput(): void {
  if (model.value !== '' && text.value !== selectedLabel.value) {
    model.value = ''
  }

  isOpen.value = true
  clearTimeout(timer)
  timer = setTimeout(() => void load(text.value.trim()), 250)
}

function open(): void {
  if (props.disabled) {
    return
  }

  isOpen.value = true

  if (options.value.length === 0) {
    void load(model.value === '' ? text.value.trim() : '')
  }
}

function choose(option: SearchOption): void {
  model.value = option.id
  selectedLabel.value = option.label
  text.value = option.label
  isOpen.value = false
}

function close(): void {
  isOpen.value = false
  // Leaving the field without choosing puts back the current choice.
  text.value = model.value === '' ? text.value : selectedLabel.value
}

function onKeydown(event: KeyboardEvent): void {
  if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
    event.preventDefault()

    if (!isOpen.value) {
      open()
      return
    }

    const step = event.key === 'ArrowDown' ? 1 : -1
    const count = options.value.length
    activeIndex.value = count === 0 ? -1 : (activeIndex.value + step + count) % count
  } else if (event.key === 'Enter' && isOpen.value) {
    // Choosing an option must not submit the form around it.
    event.preventDefault()
    const option = options.value[activeIndex.value]

    if (option) {
      choose(option)
    }
  } else if (event.key === 'Escape' && isOpen.value) {
    event.preventDefault()
    close()
  }
}

async function showValue(id: number | ''): Promise<void> {
  if (id === '' || !props.resolve) {
    return
  }

  const option = await props.resolve(id).catch(() => null)

  if (option && model.value === id) {
    selectedLabel.value = option.label
    text.value = option.label
  }
}

onMounted(() => void showValue(model.value))
watch(model, (id) => {
  if (id !== '' && text.value !== selectedLabel.value) {
    void showValue(id)
  }
})
onBeforeUnmount(() => clearTimeout(timer))
</script>

<template>
  <div class="relative">
    <input
      :id="id"
      v-model="text"
      class="input"
      type="text"
      role="combobox"
      autocomplete="off"
      aria-autocomplete="list"
      :aria-expanded="isOpen"
      :aria-controls="listId"
      :aria-activedescendant="isOpen ? activeId : undefined"
      :placeholder="placeholder"
      :disabled="disabled"
      @input="onInput"
      @focus="open"
      @click="open"
      @blur="close"
      @keydown="onKeydown"
    />
    <ul
      v-show="isOpen"
      :id="listId"
      role="listbox"
      class="absolute z-20 mt-1 max-h-64 w-full overflow-auto rounded-lg border border-slate-200 bg-white py-1 text-sm shadow-lg"
    >
      <li
        v-for="(option, index) in options"
        :id="`${id}-option-${option.id}`"
        :key="option.id"
        role="option"
        :aria-selected="option.id === model"
        class="cursor-pointer px-3 py-2"
        :class="index === activeIndex ? 'bg-brand-50 text-brand-700' : 'text-slate-700'"
        @mousedown.prevent="choose(option)"
        @mousemove="activeIndex = index"
      >
        {{ option.label }}
      </li>
      <li v-if="!isLoading && options.length === 0" class="px-3 py-2 text-slate-500">
        Aucun résultat
      </li>
      <li v-if="isLoading && options.length === 0" class="px-3 py-2 text-slate-500">Recherche…</li>
    </ul>
  </div>
</template>
