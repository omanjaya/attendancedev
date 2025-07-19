/**
 * Keyboard Navigation Composable
 *
 * Provides comprehensive keyboard navigation utilities for Vue components
 * including arrow key navigation, focus management, and accessibility shortcuts.
 */

import { ref, computed, onMounted, onUnmounted, nextTick, type Ref } from 'vue'

export interface KeyboardNavigationOptions {
  // Navigation options
  orientation?: 'horizontal' | 'vertical' | 'grid'
  wrap?: boolean // wrap around to first/last item
  loop?: boolean // continuous loop navigation

  // Element selection
  selector?: string // CSS selector for navigable elements
  excludeSelector?: string // Elements to exclude from navigation

  // Grid specific options
  columns?: number // for grid navigation

  // Behavior options
  preventDefaultOnKeys?: string[] // keys to preventDefault on
  focusOnMount?: boolean
  trapFocus?: boolean // trap focus within container

  // Custom handlers
  onNavigate?: (element: HTMLElement, index: number, direction: string) => void
  onEscape?: () => void
  onEnter?: (element: HTMLElement, index: number) => void
  onSpace?: (element: HTMLElement, index: number) => void
}

export interface UseKeyboardNavigationReturn {
  currentIndex: Ref<number>
  elements: Ref<HTMLElement[]>
  focusCurrent: () => void
  focusFirst: () => void
  focusLast: () => void
  focusNext: () => void
  focusPrevious: () => void
  focusIndex: (index: number) => void
  updateElements: () => void
  isFirstElement: Ref<boolean>
  isLastElement: Ref<boolean>
}

export function useKeyboardNavigation(
  containerRef: Ref<HTMLElement | null>,
  options: KeyboardNavigationOptions = {}
): UseKeyboardNavigationReturn {
  const {
    orientation = 'vertical',
    wrap = true,
    loop = false,
    selector = '[tabindex]:not([tabindex="-1"]), button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), a[href], [role="button"], [role="menuitem"], [role="option"]',
    excludeSelector = '[disabled], [aria-disabled="true"], [hidden]',
    columns = 1,
    preventDefaultOnKeys = ['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight', 'Home', 'End'],
    focusOnMount = false,
    trapFocus = false,
    onNavigate,
    onEscape,
    onEnter,
    onSpace,
  } = options

  const currentIndex = ref(-1)
  const elements = ref<HTMLElement[]>([])

  // Computed properties
  const isFirstElement = computed(() => currentIndex.value === 0)
  const isLastElement = computed(() => currentIndex.value === elements.value.length - 1)

  /**
   * Update the list of navigable elements
   */
  const updateElements = () => {
    if (!containerRef.value) {
      elements.value = []
      return
    }

    const allElements = Array.from(containerRef.value.querySelectorAll(selector)) as HTMLElement[]

    // Filter out excluded elements
    elements.value = allElements.filter((el) => {
      if (excludeSelector && el.matches(excludeSelector)) {return false}

      // Check if element is visible
      const rect = el.getBoundingClientRect()
      const style = getComputedStyle(el)

      return (
        rect.width > 0 &&
        rect.height > 0 &&
        style.visibility !== 'hidden' &&
        style.display !== 'none'
      )
    })

    // Update current index if it's out of bounds
    if (currentIndex.value >= elements.value.length) {
      currentIndex.value = elements.value.length - 1
    }
    if (currentIndex.value < 0 && elements.value.length > 0) {
      currentIndex.value = 0
    }
  }

  /**
   * Focus management methods
   */
  const focusElement = (element: HTMLElement, index: number) => {
    if (!element) {return}

    currentIndex.value = index
    element.focus()

    if (onNavigate) {
      onNavigate(element, index, 'focus')
    }
  }

  const focusCurrent = () => {
    const element = elements.value[currentIndex.value]
    if (element) {
      focusElement(element, currentIndex.value)
    }
  }

  const focusFirst = () => {
    if (elements.value.length > 0) {
      focusElement(elements.value[0], 0)
    }
  }

  const focusLast = () => {
    if (elements.value.length > 0) {
      const lastIndex = elements.value.length - 1
      focusElement(elements.value[lastIndex], lastIndex)
    }
  }

  const focusNext = () => {
    if (elements.value.length === 0) {return}

    let nextIndex = currentIndex.value + 1

    if (nextIndex >= elements.value.length) {
      if (wrap || loop) {
        nextIndex = 0
      } else {
        return // Stay at current position
      }
    }

    focusElement(elements.value[nextIndex], nextIndex)
  }

  const focusPrevious = () => {
    if (elements.value.length === 0) {return}

    let prevIndex = currentIndex.value - 1

    if (prevIndex < 0) {
      if (wrap || loop) {
        prevIndex = elements.value.length - 1
      } else {
        return // Stay at current position
      }
    }

    focusElement(elements.value[prevIndex], prevIndex)
  }

  const focusIndex = (index: number) => {
    if (index >= 0 && index < elements.value.length) {
      focusElement(elements.value[index], index)
    }
  }

  /**
   * Grid navigation helpers
   */
  const focusUp = () => {
    if (orientation !== 'grid') {return focusPrevious()}

    const newIndex = currentIndex.value - columns
    if (newIndex >= 0) {
      focusIndex(newIndex)
    } else if (wrap) {
      // Wrap to bottom row
      const bottomRowStart = Math.floor((elements.value.length - 1) / columns) * columns
      const columnOffset = currentIndex.value % columns
      const targetIndex = Math.min(bottomRowStart + columnOffset, elements.value.length - 1)
      focusIndex(targetIndex)
    }
  }

  const focusDown = () => {
    if (orientation !== 'grid') {return focusNext()}

    const newIndex = currentIndex.value + columns
    if (newIndex < elements.value.length) {
      focusIndex(newIndex)
    } else if (wrap) {
      // Wrap to top row
      const columnOffset = currentIndex.value % columns
      focusIndex(columnOffset)
    }
  }

  const focusLeft = () => {
    if (orientation === 'horizontal' || orientation === 'grid') {
      focusPrevious()
    }
  }

  const focusRight = () => {
    if (orientation === 'horizontal' || orientation === 'grid') {
      focusNext()
    }
  }

  /**
   * Keyboard event handler
   */
  const handleKeyDown = (event: KeyboardEvent) => {
    const { key, ctrlKey, altKey, metaKey } = event

    // Skip if modifier keys are pressed (except for specific shortcuts)
    if (ctrlKey || altKey || metaKey) {return}

    let handled = false

    switch (key) {
      case 'ArrowUp':
        focusUp()
        handled = true
        break

      case 'ArrowDown':
        focusDown()
        handled = true
        break

      case 'ArrowLeft':
        focusLeft()
        handled = true
        break

      case 'ArrowRight':
        focusRight()
        handled = true
        break

      case 'Home':
        focusFirst()
        handled = true
        break

      case 'End':
        focusLast()
        handled = true
        break

      case 'Enter':
        if (onEnter && currentIndex.value >= 0) {
          const element = elements.value[currentIndex.value]
          if (element) {
            onEnter(element, currentIndex.value)
            handled = true
          }
        }
        break

      case ' ': // Space
        if (onSpace && currentIndex.value >= 0) {
          const element = elements.value[currentIndex.value]
          if (element) {
            onSpace(element, currentIndex.value)
            handled = true
          }
        }
        break

      case 'Escape':
        if (onEscape) {
          onEscape()
          handled = true
        }
        break

      case 'Tab':
        if (trapFocus) {
          // Handle focus trapping
          if (event.shiftKey) {
            if (isFirstElement.value) {
              event.preventDefault()
              focusLast()
              handled = true
            }
          } else {
            if (isLastElement.value) {
              event.preventDefault()
              focusFirst()
              handled = true
            }
          }
        }
        break
    }

    // Prevent default behavior for specified keys
    if (handled && preventDefaultOnKeys.includes(key)) {
      event.preventDefault()
      event.stopPropagation()
    }
  }

  /**
   * Focus event handler to track current index
   */
  const handleFocus = (event: FocusEvent) => {
    const target = event.target as HTMLElement
    const index = elements.value.indexOf(target)

    if (index >= 0) {
      currentIndex.value = index
    }
  }

  /**
   * Mutation observer to watch for DOM changes
   */
  let mutationObserver: MutationObserver | null = null

  const startObserving = () => {
    if (!containerRef.value || mutationObserver) {return}

    mutationObserver = new MutationObserver(() => {
      updateElements()
    })

    mutationObserver.observe(containerRef.value, {
      childList: true,
      subtree: true,
      attributes: true,
      attributeFilter: ['disabled', 'hidden', 'aria-disabled', 'tabindex'],
    })
  }

  const stopObserving = () => {
    if (mutationObserver) {
      mutationObserver.disconnect()
      mutationObserver = null
    }
  }

  /**
   * Setup and cleanup
   */
  onMounted(async () => {
    await nextTick()

    updateElements()
    startObserving()

    if (containerRef.value) {
      containerRef.value.addEventListener('keydown', handleKeyDown)
      containerRef.value.addEventListener('focusin', handleFocus)
    }

    if (focusOnMount && elements.value.length > 0) {
      focusFirst()
    }
  })

  onUnmounted(() => {
    stopObserving()

    if (containerRef.value) {
      containerRef.value.removeEventListener('keydown', handleKeyDown)
      containerRef.value.removeEventListener('focusin', handleFocus)
    }
  })

  return {
    currentIndex,
    elements,
    focusCurrent,
    focusFirst,
    focusLast,
    focusNext,
    focusPrevious,
    focusIndex,
    updateElements,
    isFirstElement,
    isLastElement,
  }
}

/**
 * Specialized composables for common patterns
 */

/**
 * Menu navigation (vertical list with Enter/Space handlers)
 */
export function useMenuNavigation(
  containerRef: Ref<HTMLElement | null>,
  options: {
    onSelect?: (element: HTMLElement, index: number) => void
    onEscape?: () => void
    selector?: string
  } = {}
) {
  return useKeyboardNavigation(containerRef, {
    orientation: 'vertical',
    wrap: true,
    selector: options.selector || '[role="menuitem"], button, a',
    preventDefaultOnKeys: ['ArrowUp', 'ArrowDown', 'Enter', ' ', 'Escape'],
    onEnter: options.onSelect,
    onSpace: options.onSelect,
    onEscape: options.onEscape,
  })
}

/**
 * Tab navigation (horizontal with arrow keys)
 */
export function useTabNavigation(
  containerRef: Ref<HTMLElement | null>,
  options: {
    onSelect?: (element: HTMLElement, index: number) => void
    selector?: string
  } = {}
) {
  return useKeyboardNavigation(containerRef, {
    orientation: 'horizontal',
    wrap: true,
    selector: options.selector || '[role="tab"], button',
    preventDefaultOnKeys: ['ArrowLeft', 'ArrowRight', 'Home', 'End'],
    onNavigate: options.onSelect,
  })
}

/**
 * Grid navigation (2D arrow key navigation)
 */
export function useGridNavigation(
  containerRef: Ref<HTMLElement | null>,
  columns: number,
  options: {
    onSelect?: (element: HTMLElement, index: number) => void
    selector?: string
  } = {}
) {
  return useKeyboardNavigation(containerRef, {
    orientation: 'grid',
    columns,
    wrap: true,
    selector: options.selector || '[role="gridcell"], button, a',
    preventDefaultOnKeys: ['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight', 'Home', 'End'],
    onEnter: options.onSelect,
    onSpace: options.onSelect,
  })
}

/**
 * Listbox navigation (single/multi select)
 */
export function useListboxNavigation(
  containerRef: Ref<HTMLElement | null>,
  options: {
    multiSelect?: boolean
    onSelect?: (element: HTMLElement, index: number) => void
    onToggleSelect?: (element: HTMLElement, index: number) => void
    selector?: string
  } = {}
) {
  return useKeyboardNavigation(containerRef, {
    orientation: 'vertical',
    wrap: false,
    selector: options.selector || '[role="option"], li',
    preventDefaultOnKeys: ['ArrowUp', 'ArrowDown', 'Home', 'End', 'Enter', ' '],
    onEnter: options.onSelect,
    onSpace: options.multiSelect ? options.onToggleSelect : options.onSelect,
  })
}

/**
 * Modal/Dialog navigation with focus trapping
 */
export function useModalNavigation(
  containerRef: Ref<HTMLElement | null>,
  options: {
    onEscape?: () => void
    initialFocus?: string // selector for initial focus element
  } = {}
) {
  const navigation = useKeyboardNavigation(containerRef, {
    orientation: 'vertical',
    trapFocus: true,
    focusOnMount: true,
    onEscape: options.onEscape,
  })

  // Custom initial focus handling
  const focusInitialElement = async () => {
    await nextTick()

    if (options.initialFocus && containerRef.value) {
      const initialElement = containerRef.value.querySelector(options.initialFocus) as HTMLElement
      if (initialElement) {
        const index = navigation.elements.value.indexOf(initialElement)
        if (index >= 0) {
          navigation.focusIndex(index)
          return
        }
      }
    }

    navigation.focusFirst()
  }

  return {
    ...navigation,
    focusInitialElement,
  }
}
