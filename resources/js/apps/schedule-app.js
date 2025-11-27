import { createApp } from 'vue'
import ScheduleGrid from './ScheduleGrid.vue'
import ScheduleModal from './ScheduleModal.vue'
import ScheduleDetailModal from './ScheduleDetailModal.vue'
import ScheduleContextMenu from './ScheduleContextMenu.vue'

// Create Vue app for schedule management
const scheduleApp = createApp({
  components: {
    ScheduleGrid,
    ScheduleModal,
    ScheduleDetailModal,
    ScheduleContextMenu,
  },
  data() {
    return {
      academicClasses: window.scheduleData?.academicClasses || [],
      subjects: window.scheduleData?.subjects || [],
    }
  },
  mounted() {
    // Add any global event listeners or initialization here
    console.log('Schedule Management App initialized')

    // Add keyboard shortcuts
    document.addEventListener('keydown', this.handleKeyboardShortcuts)
  },
  beforeUnmount() {
    document.removeEventListener('keydown', this.handleKeyboardShortcuts)
  },
  methods: {
    handleKeyboardShortcuts(event) {
      // ESC to close modals
      if (event.key === 'Escape') {
        // Let child components handle this
        return
      }

      // Ctrl+S to save (prevent default save dialog)
      if (event.ctrlKey && event.key === 's') {
        event.preventDefault()
        // Focus on save button if visible
        const saveBtn = document.querySelector('button[type="submit"]:focus-within')
        if (saveBtn) {
          saveBtn.click()
        }
      }

      // Ctrl+E to export
      if (event.ctrlKey && event.key === 'e') {
        event.preventDefault()
        const exportBtn = document.querySelector('[id*="export"], [class*="export"]')
        if (exportBtn) {
          exportBtn.click()
        }
      }
    },
  },
})

// Mount the app
scheduleApp.mount('#schedule-app')
