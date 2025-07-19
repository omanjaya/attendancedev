import './bootstrap'
import './theme-toggle'
import './utils/notifications'

import Alpine from 'alpinejs'

// Type augmentation for global Alpine
declare global {
  interface Window {
    Alpine: typeof Alpine
  }
}

window.Alpine = Alpine

Alpine.start()
