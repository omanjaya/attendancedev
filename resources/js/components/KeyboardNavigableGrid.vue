<!--
  Keyboard Navigable Grid Component

  Demonstrates 2D keyboard navigation patterns:
  - Arrow keys for grid navigation
  - Home/End for row navigation
  - Ctrl+Home/End for grid navigation
  - Page Up/Down for section navigation
  - Space/Enter for selection
-->

<template>
  <div class="grid-container">
    <!-- Grid Header -->
    <div class="grid-header">
      <h3 class="grid-title">
        {{ title }}
      </h3>
      <div class="grid-info">
        <span class="grid-selected-count">
          {{ selectedItems.length }} of {{ items.length }} selected
        </span>
        <button
          v-if="clearable && selectedItems.length > 0"
          class="grid-clear-btn"
          type="button"
          @click="clearSelection"
        >
          Clear Selection
        </button>
      </div>
    </div>

    <!-- Grid Instructions -->
    <div :id="`${gridId}-instructions`" class="grid-instructions">
      Use arrow keys to navigate, Space to select/deselect, Enter to activate. Home/End for row
      navigation, Ctrl+Home/End for grid navigation.
    </div>

    <!-- Grid Container -->
    <div
      ref="gridRef"
      class="grid"
      :style="{ '--columns': columns }"
      role="grid"
      :aria-label="title"
      :aria-describedby="`${gridId}-instructions`"
      :aria-multiselectable="multiSelect"
    >
      <!-- Grid Items -->
      <div
        v-for="(item, index) in items"
        :id="`${gridId}-cell-${index}`"
        :key="getItemKey(item, index)"
        class="grid-cell"
        :class="{
          'grid-cell--selected': isSelected(item),
          'grid-cell--focused': index === focusedIndex,
          'grid-cell--disabled': isDisabled(item),
        }"
        role="gridcell"
        :tabindex="index === 0 ? 0 : -1"
        :aria-selected="multiSelect ? isSelected(item) : undefined"
        :aria-disabled="isDisabled(item)"
        @click="handleClick(item, index)"
        @dblclick="handleDoubleClick(item, index)"
      >
        <!-- Custom Item Content -->
        <slot
          name="item"
          :item="item"
          :index="index"
          :selected="isSelected(item)"
        >
          <div class="grid-item-default">
            <div class="grid-item-icon">
              <component :is="getItemIcon(item)" v-if="getItemIcon(item)" class="h-6 w-6" />
              <div v-else class="grid-item-placeholder" />
            </div>
            <div class="grid-item-content">
              <div class="grid-item-title">
                {{ getItemTitle(item) }}
              </div>
              <div v-if="getItemSubtitle(item)" class="grid-item-subtitle">
                {{ getItemSubtitle(item) }}
              </div>
            </div>
            <div v-if="showSelection" class="grid-item-selection">
              <CheckCircleIcon v-if="isSelected(item)" class="h-5 w-5 text-blue-600" />
              <div v-else class="h-5 w-5 rounded-full border-2 border-gray-300" />
            </div>
          </div>
        </slot>
      </div>

      <!-- Empty State -->
      <div v-if="items.length === 0" class="grid-empty">
        <slot name="empty">
          <div class="grid-empty-content">
            <div class="grid-empty-icon">
              <InboxIcon class="h-12 w-12 text-gray-400" />
            </div>
            <div class="grid-empty-text">
              No items to display
            </div>
          </div>
        </slot>
      </div>
    </div>

    <!-- Grid Footer -->
    <div v-if="$slots.footer" class="grid-footer">
      <slot name="footer" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { CheckCircleIcon, InboxIcon } from '@heroicons/vue/24/outline'
import { useGridNavigation } from '@/composables/useKeyboardNavigation'

export interface GridItem {
  id?: string | number
  title?: string
  subtitle?: string
  icon?: any
  disabled?: boolean
  [key: string]: any
}

export interface GridProps {
  items: GridItem[]
  columns?: number
  title?: string
  multiSelect?: boolean
  clearable?: boolean
  showSelection?: boolean
  selectedItems?: GridItem[]
  itemKey?: string
  titleKey?: string
  subtitleKey?: string
  iconKey?: string
  disabledKey?: string
}

const props = withDefaults(defineProps<GridProps>(), {
  columns: 4,
  title: 'Grid',
  multiSelect: true,
  clearable: true,
  showSelection: true,
  selectedItems: () => [],
  itemKey: 'id',
  titleKey: 'title',
  subtitleKey: 'subtitle',
  iconKey: 'icon',
  disabledKey: 'disabled',
})

const emit = defineEmits<{
  'update:selectedItems': [items: GridItem[]]
  select: [item: GridItem, index: number]
  deselect: [item: GridItem, index: number]
  activate: [item: GridItem, index: number]
  focus: [item: GridItem, index: number]
}>()

// Refs
const gridRef = ref<HTMLElement | null>(null)
const focusedIndex = ref(0)
const selectedItems = ref<GridItem[]>([...props.selectedItems])

// Generate unique grid ID
const gridId = `grid-${Math.random().toString(36).substr(2, 9)}`

// Helper functions
const getItemKey = (item: GridItem, index: number) =>
  item[props.itemKey]?.toString() || index.toString()

const getItemTitle = (item: GridItem) => item[props.titleKey] || `Item ${getItemKey(item, 0)}`

const getItemSubtitle = (item: GridItem) => item[props.subtitleKey]

const getItemIcon = (item: GridItem) => item[props.iconKey]

const isDisabled = (item: GridItem) => Boolean(item[props.disabledKey])

const isSelected = (item: GridItem) =>
  selectedItems.value.some((selected) => getItemKey(selected, 0) === getItemKey(item, 0))

// Grid keyboard navigation
const { focusIndex, currentIndex } = useGridNavigation(gridRef, props.columns, {
  onSelect: (element, index) => {
    const item = props.items[index]
    if (item && !isDisabled(item)) {
      handleActivate(item, index)
    }
  },
  selector: '.grid-cell:not(.grid-cell--disabled)',
})

// Watch for focus changes
watch(currentIndex, (newIndex) => {
  if (newIndex >= 0 && newIndex < props.items.length) {
    focusedIndex.value = newIndex
    const item = props.items[newIndex]
    if (item) {
      emit('focus', item, newIndex)
    }
  }
})

// Selection methods
const toggleSelection = (item: GridItem, index: number) => {
  if (isDisabled(item)) {return}

  if (isSelected(item)) {
    deselectItem(item, index)
  } else {
    selectItem(item, index)
  }
}

const selectItem = (item: GridItem, index: number) => {
  if (isDisabled(item)) {return}

  if (!props.multiSelect) {
    selectedItems.value = [item]
  } else if (!isSelected(item)) {
    selectedItems.value.push(item)
  }

  emit('update:selectedItems', selectedItems.value)
  emit('select', item, index)
}

const deselectItem = (item: GridItem, index: number) => {
  selectedItems.value = selectedItems.value.filter(
    (selected) => getItemKey(selected, 0) !== getItemKey(item, 0)
  )

  emit('update:selectedItems', selectedItems.value)
  emit('deselect', item, index)
}

const clearSelection = () => {
  selectedItems.value = []
  emit('update:selectedItems', selectedItems.value)
}

// Event handlers
const handleClick = (item: GridItem, index: number) => {
  if (isDisabled(item)) {return}

  // Update focus
  focusedIndex.value = index
  focusIndex(index)

  // Toggle selection
  toggleSelection(item, index)
}

const handleDoubleClick = (item: GridItem, index: number) => {
  if (isDisabled(item)) {return}
  handleActivate(item, index)
}

const handleActivate = (item: GridItem, index: number) => {
  if (isDisabled(item)) {return}
  emit('activate', item, index)
}

// Custom keyboard handling for additional shortcuts
const handleGridKeydown = (event: KeyboardEvent) => {
  const { key, ctrlKey, metaKey, shiftKey } = event
  const modifierKey = ctrlKey || metaKey

  switch (key) {
    case ' ': // Space
      event.preventDefault()
      if (focusedIndex.value >= 0) {
        const item = props.items[focusedIndex.value]
        if (item && !isDisabled(item)) {
          toggleSelection(item, focusedIndex.value)
        }
      }
      break

    case 'Enter':
      event.preventDefault()
      if (focusedIndex.value >= 0) {
        const item = props.items[focusedIndex.value]
        if (item && !isDisabled(item)) {
          handleActivate(item, focusedIndex.value)
        }
      }
      break

    case 'a':
    case 'A':
      if (modifierKey && props.multiSelect) {
        event.preventDefault()
        // Select all non-disabled items
        selectedItems.value = props.items.filter((item) => !isDisabled(item))
        emit('update:selectedItems', selectedItems.value)
      }
      break

    case 'Escape':
      if (selectedItems.value.length > 0) {
        event.preventDefault()
        clearSelection()
      }
      break

    case 'PageUp':
      event.preventDefault()
      // Move up by one "page" (columns * 3 rows)
      const pageUpIndex = Math.max(0, focusedIndex.value - props.columns * 3)
      focusedIndex.value = pageUpIndex
      focusIndex(pageUpIndex)
      break

    case 'PageDown':
      event.preventDefault()
      // Move down by one "page" (columns * 3 rows)
      const pageDownIndex = Math.min(props.items.length - 1, focusedIndex.value + props.columns * 3)
      focusedIndex.value = pageDownIndex
      focusIndex(pageDownIndex)
      break
  }
}

// Watch for prop changes
watch(
  () => props.selectedItems,
  (newSelection) => {
    selectedItems.value = [...newSelection]
  },
  { deep: true }
)

// Setup keyboard handler
onMounted(() => {
  if (gridRef.value) {
    gridRef.value.addEventListener('keydown', handleGridKeydown)
  }
})
</script>

<style scoped>
.grid-container {
  @apply overflow-hidden rounded-lg border border-gray-200 bg-white;
}

.grid-header {
  @apply flex items-center justify-between border-b border-gray-200 bg-gray-50 p-4;
}

.grid-title {
  @apply text-lg font-semibold text-gray-900;
}

.grid-info {
  @apply flex items-center space-x-3;
}

.grid-selected-count {
  @apply text-sm text-gray-600;
}

.grid-clear-btn {
  @apply text-sm font-medium text-blue-600 transition-colors hover:text-blue-800;
}

.grid-instructions {
  @apply border-b border-gray-200 bg-gray-50 px-4 py-2 text-xs text-gray-500;
}

.grid {
  @apply p-4;
  display: grid;
  grid-template-columns: repeat(var(--columns), 1fr);
  gap: 1rem;
}

.grid-cell {
  @apply cursor-pointer rounded-lg border border-gray-200 p-3 transition-all duration-200 hover:border-gray-300 hover:shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500;
}

.grid-cell--selected {
  @apply border-blue-500 bg-blue-50;
}

.grid-cell--focused {
  @apply border-blue-500 ring-2 ring-blue-500;
}

.grid-cell--disabled {
  @apply cursor-not-allowed bg-gray-50 opacity-50;
}

.grid-item-default {
  @apply flex flex-col space-y-2;
}

.grid-item-icon {
  @apply flex justify-center;
}

.grid-item-placeholder {
  @apply h-6 w-6 rounded bg-gray-200;
}

.grid-item-content {
  @apply text-center;
}

.grid-item-title {
  @apply text-sm font-medium text-gray-900;
}

.grid-item-subtitle {
  @apply text-xs text-gray-500;
}

.grid-item-selection {
  @apply flex justify-center;
}

.grid-empty {
  @apply col-span-full py-12;
}

.grid-empty-content {
  @apply flex flex-col items-center text-center;
}

.grid-empty-icon {
  @apply mb-4;
}

.grid-empty-text {
  @apply font-medium text-gray-500;
}

.grid-footer {
  @apply border-t border-gray-200 bg-gray-50 p-4;
}

/* Responsive grid */
@media (max-width: 768px) {
  .grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 480px) {
  .grid {
    grid-template-columns: 1fr;
  }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
  .grid-container {
    @apply border-gray-600 bg-gray-800;
  }

  .grid-header,
  .grid-instructions,
  .grid-footer {
    @apply border-gray-600 bg-gray-700;
  }

  .grid-title {
    @apply text-gray-100;
  }

  .grid-cell {
    @apply border-gray-600 bg-gray-800;
  }

  .grid-cell--selected {
    @apply border-blue-400 bg-blue-900;
  }

  .grid-item-title {
    @apply text-gray-100;
  }

  .grid-item-subtitle {
    @apply text-gray-400;
  }
}
</style>
