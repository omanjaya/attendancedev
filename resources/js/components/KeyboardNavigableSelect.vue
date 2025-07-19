<!--
  Keyboard Navigable Select Component

  Demonstrates advanced keyboard navigation patterns including:
  - Arrow key navigation through options
  - Type-ahead search
  - Enter/Space selection
  - Escape to close
  - Home/End navigation
-->

<template>
  <div ref="containerRef" class="relative">
    <!-- Select Button -->
    <button
      ref="triggerRef"
      type="button"
      class="select-trigger"
      :class="{
        'select-trigger--open': isOpen,
        'select-trigger--error': hasError,
        'select-trigger--disabled': disabled,
      }"
      :disabled="disabled"
      :aria-haspopup="'listbox'"
      :aria-expanded="isOpen"
      :aria-labelledby="labelId"
      :aria-describedby="describedBy"
      :aria-invalid="hasError"
      @click="toggleOpen"
      @keydown="handleTriggerKeydown"
    >
      <span class="select-value">
        {{ displayValue || placeholder || 'Select an option' }}
      </span>
      <ChevronDownIcon
        class="select-icon"
        :class="{ 'select-icon--open': isOpen }"
        aria-hidden="true"
      />
    </button>

    <!-- Options Dropdown -->
    <Transition name="dropdown">
      <div
        v-if="isOpen"
        ref="dropdownRef"
        class="select-dropdown"
        role="listbox"
        :aria-labelledby="labelId"
        :aria-activedescendant="activeOptionId"
        @keydown="handleDropdownKeydown"
      >
        <!-- Search Input (if searchable) -->
        <div v-if="searchable" class="select-search">
          <input
            ref="searchInputRef"
            v-model="searchQuery"
            type="text"
            class="select-search-input"
            placeholder="Type to search..."
            :aria-label="`Search ${label || 'options'}`"
            @keydown="handleSearchKeydown"
          >
        </div>

        <!-- Options List -->
        <div class="select-options" role="presentation">
          <template v-if="filteredOptions.length > 0">
            <div
              v-for="(option, index) in filteredOptions"
              :id="getOptionId(index)"
              :key="getOptionKey(option)"
              class="select-option"
              :class="{
                'select-option--selected': isSelected(option),
                'select-option--highlighted': index === highlightedIndex,
              }"
              role="option"
              :aria-selected="isSelected(option)"
              :tabindex="-1"
              @click="selectOption(option, index)"
              @mouseenter="highlightedIndex = index"
            >
              <!-- Option Content -->
              <div class="select-option-content">
                <div class="select-option-main">
                  {{ getOptionLabel(option) }}
                </div>
                <div v-if="getOptionDescription(option)" class="select-option-description">
                  {{ getOptionDescription(option) }}
                </div>
              </div>

              <!-- Selection Indicator -->
              <CheckIcon v-if="isSelected(option)" class="select-option-check" aria-hidden="true" />
            </div>
          </template>

          <!-- No Options Message -->
          <div v-else class="select-no-options">
            {{ searchQuery ? 'No matching options found' : 'No options available' }}
          </div>
        </div>

        <!-- Custom Footer Slot -->
        <div v-if="$slots.footer" class="select-footer">
          <slot name="footer" />
        </div>
      </div>
    </Transition>

    <!-- Screen Reader Instructions -->
    <div :id="`${id}-instructions`" class="sr-only">
      Use arrow keys to navigate options, Enter or Space to select, Escape to close.
      {{ searchable ? 'Type to search options.' : '' }}
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue'
import { ChevronDownIcon, CheckIcon } from '@heroicons/vue/24/outline'
import { useListboxNavigation } from '@/composables/useKeyboardNavigation'

export interface SelectOption {
  value: any
  label: string
  description?: string
  disabled?: boolean
  [key: string]: any
}

export interface SelectProps {
  modelValue?: any
  options: SelectOption[]
  placeholder?: string
  label?: string
  disabled?: boolean
  searchable?: boolean
  clearable?: boolean
  multiple?: boolean
  hasError?: boolean
  describedBy?: string
  valueKey?: string
  labelKey?: string
  descriptionKey?: string
}

const props = withDefaults(defineProps<SelectProps>(), {
  valueKey: 'value',
  labelKey: 'label',
  descriptionKey: 'description',
  searchable: false,
  clearable: false,
  multiple: false,
  hasError: false,
})

const emit = defineEmits<{
  'update:modelValue': [value: any]
  change: [value: any, option: SelectOption | null]
  search: [query: string]
  open: []
  close: []
}>()

// Refs
const containerRef = ref<HTMLElement | null>(null)
const triggerRef = ref<HTMLElement | null>(null)
const dropdownRef = ref<HTMLElement | null>(null)
const searchInputRef = ref<HTMLInputElement | null>(null)

// State
const isOpen = ref(false)
const searchQuery = ref('')
const highlightedIndex = ref(-1)
const typeaheadQuery = ref('')
const typeaheadTimeout = ref<NodeJS.Timeout | null>(null)

// Generate unique IDs
const id = `select-${Math.random().toString(36).substr(2, 9)}`
const labelId = `${id}-label`
const activeOptionId = computed(() =>
  highlightedIndex.value >= 0 ? getOptionId(highlightedIndex.value) : undefined
)

// Computed properties
const filteredOptions = computed(() => {
  if (!searchQuery.value.trim()) {return props.options}

  const query = searchQuery.value.toLowerCase()
  return props.options.filter(
    (option) =>
      getOptionLabel(option).toLowerCase().includes(query) ||
      getOptionDescription(option)?.toLowerCase().includes(query)
  )
})

const selectedOption = computed(() => {
  if (props.modelValue == null) {return null}
  return props.options.find((option) => getOptionValue(option) === props.modelValue) || null
})

const displayValue = computed(() => {
  return selectedOption.value ? getOptionLabel(selectedOption.value) : ''
})

// Helper functions
const getOptionValue = (option: SelectOption) => option[props.valueKey]
const getOptionLabel = (option: SelectOption) => option[props.labelKey]
const getOptionDescription = (option: SelectOption) => option[props.descriptionKey]
const getOptionKey = (option: SelectOption) =>
  getOptionValue(option)?.toString() || getOptionLabel(option)
const getOptionId = (index: number) => `${id}-option-${index}`

const isSelected = (option: SelectOption) => {
  return getOptionValue(option) === props.modelValue
}

// Keyboard navigation setup
const { focusIndex, currentIndex } = useListboxNavigation(dropdownRef, {
  onSelect: (element, index) => {
    const option = filteredOptions.value[index]
    if (option) {
      selectOption(option, index)
    }
  },
  selector: '.select-option:not(.select-option--disabled)',
})

// Watch for highlighted index changes
watch(currentIndex, (newIndex) => {
  highlightedIndex.value = newIndex
})

// Methods
const toggleOpen = () => {
  if (props.disabled) {return}

  if (isOpen.value) {
    closeDropdown()
  } else {
    openDropdown()
  }
}

const openDropdown = async () => {
  if (props.disabled || isOpen.value) {return}

  isOpen.value = true
  searchQuery.value = ''

  await nextTick()

  // Focus search input if searchable, otherwise focus the dropdown
  if (props.searchable && searchInputRef.value) {
    searchInputRef.value.focus()
  } else if (dropdownRef.value) {
    dropdownRef.value.focus()
  }

  // Highlight the selected option or first option
  const selectedIndex = filteredOptions.value.findIndex((option) => isSelected(option))
  highlightedIndex.value = selectedIndex >= 0 ? selectedIndex : 0

  if (highlightedIndex.value >= 0) {
    focusIndex(highlightedIndex.value)
  }

  emit('open')
}

const closeDropdown = () => {
  if (!isOpen.value) {return}

  isOpen.value = false
  highlightedIndex.value = -1
  searchQuery.value = ''

  // Return focus to trigger
  triggerRef.value?.focus()

  emit('close')
}

const selectOption = (option: SelectOption, index: number) => {
  if (option.disabled) {return}

  emit('update:modelValue', getOptionValue(option))
  emit('change', getOptionValue(option), option)

  if (!props.multiple) {
    closeDropdown()
  }
}

const handleTriggerKeydown = (event: KeyboardEvent) => {
  switch (event.key) {
    case 'ArrowDown':
    case 'ArrowUp':
    case 'Enter':
    case ' ':
      event.preventDefault()
      if (!isOpen.value) {
        openDropdown()
      }
      break

    case 'Escape':
      if (isOpen.value) {
        event.preventDefault()
        closeDropdown()
      }
      break

    default:
      // Typeahead functionality
      if (event.key.length === 1 && !event.ctrlKey && !event.altKey && !event.metaKey) {
        handleTypeahead(event.key)
      }
      break
  }
}

const handleDropdownKeydown = (event: KeyboardEvent) => {
  switch (event.key) {
    case 'Escape':
      event.preventDefault()
      closeDropdown()
      break

    case 'Tab':
      // Allow tab to close dropdown and move to next element
      closeDropdown()
      break
  }
}

const handleSearchKeydown = (event: KeyboardEvent) => {
  switch (event.key) {
    case 'ArrowDown':
      event.preventDefault()
      if (filteredOptions.value.length > 0) {
        highlightedIndex.value = 0
        focusIndex(0)
      }
      break

    case 'Escape':
      event.preventDefault()
      closeDropdown()
      break

    case 'Enter':
      event.preventDefault()
      if (highlightedIndex.value >= 0) {
        const option = filteredOptions.value[highlightedIndex.value]
        if (option) {
          selectOption(option, highlightedIndex.value)
        }
      }
      break
  }
}

const handleTypeahead = (char: string) => {
  // Clear previous timeout
  if (typeaheadTimeout.value) {
    clearTimeout(typeaheadTimeout.value)
  }

  // Add character to query
  typeaheadQuery.value += char.toLowerCase()

  // Find matching option
  const matchingIndex = filteredOptions.value.findIndex((option) =>
    getOptionLabel(option).toLowerCase().startsWith(typeaheadQuery.value)
  )

  if (matchingIndex >= 0) {
    if (!isOpen.value) {
      // If closed, select the option directly
      const option = filteredOptions.value[matchingIndex]
      selectOption(option, matchingIndex)
    } else {
      // If open, highlight the option
      highlightedIndex.value = matchingIndex
      focusIndex(matchingIndex)
    }
  }

  // Clear typeahead query after delay
  typeaheadTimeout.value = setTimeout(() => {
    typeaheadQuery.value = ''
  }, 1000)
}

// Click outside to close
const handleClickOutside = (event: Event) => {
  if (isOpen.value && containerRef.value && !containerRef.value.contains(event.target as Node)) {
    closeDropdown()
  }
}

// Watch for search query changes
watch(searchQuery, (newQuery) => {
  emit('search', newQuery)
  highlightedIndex.value = 0
})

// Lifecycle
onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
  if (typeaheadTimeout.value) {
    clearTimeout(typeaheadTimeout.value)
  }
})
</script>

<style scoped>
.select-trigger {
  @apply w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-left shadow-sm transition-colors duration-200 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:cursor-not-allowed disabled:bg-gray-50 disabled:text-gray-500;

  display: flex;
  align-items: center;
  justify-content: space-between;
  min-height: 40px;
}

.select-trigger--open {
  @apply border-blue-500 ring-2 ring-blue-500;
}

.select-trigger--error {
  @apply border-red-500 focus:border-red-500 focus:ring-red-500;
}

.select-value {
  @apply flex-1 truncate;
}

.select-icon {
  @apply h-5 w-5 text-gray-400 transition-transform duration-200;
}

.select-icon--open {
  @apply rotate-180 transform;
}

.select-dropdown {
  @apply absolute left-0 right-0 top-full z-50 mt-1 max-h-60 overflow-hidden rounded-lg border border-gray-300 bg-white shadow-lg focus:outline-none;
}

.select-search {
  @apply border-b border-gray-200 p-2;
}

.select-search-input {
  @apply w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500;
}

.select-options {
  @apply max-h-48 overflow-y-auto;
}

.select-option {
  @apply cursor-pointer px-3 py-2 transition-colors duration-150 hover:bg-gray-50 focus:bg-gray-50 focus:outline-none;

  display: flex;
  align-items: center;
  justify-content: space-between;
}

.select-option--highlighted {
  @apply bg-blue-50 text-blue-700;
}

.select-option--selected {
  @apply bg-blue-600 text-white;
}

.select-option--selected.select-option--highlighted {
  @apply bg-blue-700;
}

.select-option--disabled {
  @apply cursor-not-allowed text-gray-400;
}

.select-option-content {
  @apply min-w-0 flex-1;
}

.select-option-main {
  @apply font-medium;
}

.select-option-description {
  @apply mt-1 text-sm text-gray-500;
}

.select-option--selected .select-option-description {
  @apply text-blue-100;
}

.select-option-check {
  @apply ml-2 h-4 w-4 flex-shrink-0;
}

.select-no-options {
  @apply px-3 py-8 text-center text-sm text-gray-500;
}

.select-footer {
  @apply border-t border-gray-200 p-2;
}

/* Dropdown transition */
.dropdown-enter-active,
.dropdown-leave-active {
  @apply transition-all duration-200;
}

.dropdown-enter-from,
.dropdown-leave-to {
  @apply scale-95 transform opacity-0;
}

.dropdown-enter-to,
.dropdown-leave-from {
  @apply scale-100 transform opacity-100;
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
  .select-trigger {
    @apply border-gray-600 bg-gray-800 text-gray-100;
  }

  .select-dropdown {
    @apply border-gray-600 bg-gray-800;
  }

  .select-option {
    @apply hover:bg-gray-700 focus:bg-gray-700;
  }

  .select-option--highlighted {
    @apply bg-blue-900 text-blue-100;
  }
}
</style>
