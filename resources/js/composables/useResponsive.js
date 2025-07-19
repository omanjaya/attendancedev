import { ref, computed, onMounted, onUnmounted } from 'vue'

/**
 * Composable for responsive design utilities
 */
export function useResponsive() {
  const windowWidth = ref(window.innerWidth)
  const windowHeight = ref(window.innerHeight)

  // Breakpoints (matching Tailwind CSS)
  const breakpoints = {
    sm: 640,
    md: 768,
    lg: 1024,
    xl: 1280,
    '2xl': 1536,
  }

  // Update window dimensions
  const updateDimensions = () => {
    windowWidth.value = window.innerWidth
    windowHeight.value = window.innerHeight
  }

  // Device type detection
  const isMobile = computed(() => windowWidth.value < breakpoints.md)
  const isTablet = computed(
    () => windowWidth.value >= breakpoints.md && windowWidth.value < breakpoints.lg
  )
  const isDesktop = computed(() => windowWidth.value >= breakpoints.lg)
  const isLargeScreen = computed(() => windowWidth.value >= breakpoints.xl)

  // Breakpoint checks
  const isXs = computed(() => windowWidth.value < breakpoints.sm)
  const isSm = computed(
    () => windowWidth.value >= breakpoints.sm && windowWidth.value < breakpoints.md
  )
  const isMd = computed(
    () => windowWidth.value >= breakpoints.md && windowWidth.value < breakpoints.lg
  )
  const isLg = computed(
    () => windowWidth.value >= breakpoints.lg && windowWidth.value < breakpoints.xl
  )
  const isXl = computed(
    () => windowWidth.value >= breakpoints.xl && windowWidth.value < breakpoints['2xl']
  )
  const is2Xl = computed(() => windowWidth.value >= breakpoints['2xl'])

  // Utility functions
  const isScreenSize = (size) => {
    switch (size) {
      case 'xs':
        return isXs.value
      case 'sm':
        return isSm.value
      case 'md':
        return isMd.value
      case 'lg':
        return isLg.value
      case 'xl':
        return isXl.value
      case '2xl':
        return is2Xl.value
      default:
        return false
    }
  }

  const isScreenSizeAndUp = (size) => {
    const breakpointValue = breakpoints[size] || 0
    return windowWidth.value >= breakpointValue
  }

  const isScreenSizeAndDown = (size) => {
    const breakpointValue = breakpoints[size] || Infinity
    return windowWidth.value < breakpointValue
  }

  // Touch device detection
  const isTouchDevice = computed(() => {
    return 'ontouchstart' in window || navigator.maxTouchPoints > 0
  })

  // Orientation detection
  const orientation = computed(() => {
    return windowWidth.value > windowHeight.value ? 'landscape' : 'portrait'
  })

  // Notification positioning based on screen size
  const notificationPosition = computed(() => {
    if (isMobile.value) {
      return {
        position: 'bottom',
        align: 'center',
        maxWidth: '90vw',
        margin: '1rem',
      }
    } else if (isTablet.value) {
      return {
        position: 'top-right',
        align: 'end',
        maxWidth: '400px',
        margin: '1rem',
      }
    } else {
      return {
        position: 'top-right',
        align: 'end',
        maxWidth: '420px',
        margin: '1.5rem',
      }
    }
  })

  // Notification center responsive config
  const notificationCenterConfig = computed(() => {
    if (isMobile.value) {
      return {
        fullscreen: true,
        width: '100vw',
        height: '100vh',
        position: 'fixed',
        top: 0,
        left: 0,
        zIndex: 9999,
      }
    } else {
      return {
        fullscreen: false,
        width: '380px',
        maxHeight: '500px',
        position: 'absolute',
        top: '100%',
        right: 0,
        zIndex: 50,
      }
    }
  })

  // Security dashboard responsive config
  const dashboardConfig = computed(() => {
    const cols = isMobile.value ? 1 : isTablet.value ? 2 : 4
    const spacing = isMobile.value ? '1rem' : '1.5rem'

    return {
      gridCols: cols,
      spacing,
      cardPadding: isMobile.value ? '1rem' : '1.5rem',
      fontSize: isMobile.value ? 'sm' : 'base',
    }
  })

  // Device management responsive config
  const deviceManagementConfig = computed(() => {
    return {
      listView: isMobile.value,
      gridView: !isMobile.value,
      itemsPerRow: isMobile.value ? 1 : isTablet.value ? 2 : 3,
      showDetails: !isMobile.value,
      compactMode: isMobile.value,
    }
  })

  // Toast notification responsive config
  const toastConfig = computed(() => {
    if (isMobile.value) {
      return {
        position: 'bottom-center',
        width: 'calc(100vw - 2rem)',
        margin: '1rem',
        maxWidth: 'none',
      }
    } else {
      return {
        position: 'top-right',
        width: 'auto',
        margin: '1rem',
        maxWidth: '400px',
      }
    }
  })

  // Adaptive text size
  const getAdaptiveTextSize = (baseSize = 'base') => {
    const sizeMap = {
      xs: isMobile.value ? 'text-xs' : 'text-xs',
      sm: isMobile.value ? 'text-xs' : 'text-sm',
      base: isMobile.value ? 'text-sm' : 'text-base',
      lg: isMobile.value ? 'text-base' : 'text-lg',
      xl: isMobile.value ? 'text-lg' : 'text-xl',
    }
    return sizeMap[baseSize] || sizeMap.base
  }

  // Adaptive spacing
  const getAdaptiveSpacing = (baseSpacing = 4) => {
    return isMobile.value ? Math.max(2, baseSpacing - 2) : baseSpacing
  }

  // Adaptive padding
  const getAdaptivePadding = (basePadding = 4) => {
    return isMobile.value ? Math.max(2, basePadding - 1) : basePadding
  }

  // CSS classes for responsive behavior
  const responsiveClasses = computed(() => {
    return {
      container: isMobile.value ? 'px-4 py-2' : isTablet.value ? 'px-6 py-4' : 'px-8 py-6',

      card: isMobile.value ? 'p-4 rounded-lg' : 'p-6 rounded-xl',

      button: isMobile.value ? 'px-3 py-2 text-sm' : 'px-4 py-2 text-base',

      modal: isMobile.value ? 'w-full h-full rounded-none' : 'w-auto max-w-lg rounded-lg',

      dropdown: isMobile.value ? 'w-screen left-0 right-0' : 'w-80 right-0',
    }
  })

  // Lifecycle
  onMounted(() => {
    window.addEventListener('resize', updateDimensions)
    window.addEventListener('orientationchange', updateDimensions)
  })

  onUnmounted(() => {
    window.removeEventListener('resize', updateDimensions)
    window.removeEventListener('orientationchange', updateDimensions)
  })

  return {
    // Dimensions
    windowWidth,
    windowHeight,

    // Device types
    isMobile,
    isTablet,
    isDesktop,
    isLargeScreen,
    isTouchDevice,

    // Breakpoints
    isXs,
    isSm,
    isMd,
    isLg,
    isXl,
    is2Xl,

    // Utilities
    isScreenSize,
    isScreenSizeAndUp,
    isScreenSizeAndDown,
    orientation,

    // Component configs
    notificationPosition,
    notificationCenterConfig,
    dashboardConfig,
    deviceManagementConfig,
    toastConfig,

    // Adaptive helpers
    getAdaptiveTextSize,
    getAdaptiveSpacing,
    getAdaptivePadding,
    responsiveClasses,

    // Breakpoints object
    breakpoints,
  }
}
