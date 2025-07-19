<template>
  <div
    v-if="schedule"
    :style="{ left: x + 'px', top: y + 'px' }"
    class="fixed z-50 min-w-[180px] rounded-lg border border-gray-200 bg-white py-2 shadow-lg dark:border-gray-600 dark:bg-gray-800"
    @click.stop
  >
    <!-- Schedule Info Header -->
    <div class="border-b border-gray-200 px-4 py-2 dark:border-gray-600">
      <div class="flex items-center space-x-2">
        <div
          class="h-3 w-3 rounded"
          :style="{ backgroundColor: schedule.subject?.color || '#3B82F6' }"
        />
        <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
          {{ schedule.subject?.code }}
        </span>
      </div>
      <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
        {{ schedule.employee?.full_name }}
      </p>
    </div>

    <!-- Menu Items -->
    <div class="py-1">
      <!-- View Details -->
      <button
        class="flex w-full items-center px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
        @click="handleView"
      >
        <EyeIcon class="mr-3 h-4 w-4" />
        Lihat Detail
      </button>

      <!-- Edit (if not locked) -->
      <button
        v-if="!schedule.is_locked"
        class="flex w-full items-center px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
        @click="handleEdit"
      >
        <PencilIcon class="mr-3 h-4 w-4" />
        Edit Jadwal
      </button>

      <!-- Separator -->
      <div class="my-1 border-t border-gray-200 dark:border-gray-600" />

      <!-- Copy/Duplicate -->
      <button
        class="flex w-full items-center px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
        @click="handleDuplicate"
      >
        <DocumentDuplicateIcon class="mr-3 h-4 w-4" />
        Duplikasi
      </button>

      <!-- Move (Swap Mode) -->
      <button
        v-if="!schedule.is_locked"
        class="flex w-full items-center px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
        @click="handleSwap"
      >
        <ArrowsRightLeftIcon class="mr-3 h-4 w-4" />
        Tukar Posisi
      </button>

      <!-- Separator -->
      <div class="my-1 border-t border-gray-200 dark:border-gray-600" />

      <!-- Lock/Unlock -->
      <button
        :class="[
          'flex w-full items-center px-4 py-2 text-left text-sm hover:bg-gray-100 dark:hover:bg-gray-700',
          schedule.is_locked
            ? 'text-green-600 dark:text-green-400'
            : 'text-red-600 dark:text-red-400',
        ]"
        @click="handleToggleLock"
      >
        <LockOpenIcon v-if="schedule.is_locked" class="mr-3 h-4 w-4" />
        <LockClosedIcon v-else class="mr-3 h-4 w-4" />
        {{ schedule.is_locked ? 'Buka Kunci' : 'Kunci Jadwal' }}
      </button>

      <!-- Separator -->
      <div class="my-1 border-t border-gray-200 dark:border-gray-600" />

      <!-- Export -->
      <button
        class="flex w-full items-center px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
        @click="handleExport"
      >
        <ArrowDownTrayIcon class="mr-3 h-4 w-4" />
        Export Detail
      </button>

      <!-- View History -->
      <button
        class="flex w-full items-center px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
        @click="handleViewHistory"
      >
        <ClockIcon class="mr-3 h-4 w-4" />
        Riwayat Perubahan
      </button>

      <!-- Separator -->
      <div class="my-1 border-t border-gray-200 dark:border-gray-600" />

      <!-- Delete -->
      <button
        class="flex w-full items-center px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20"
        @click="handleDelete"
      >
        <TrashIcon class="mr-3 h-4 w-4" />
        Hapus Jadwal
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import {
  EyeIcon,
  PencilIcon,
  TrashIcon,
  LockClosedIcon,
  LockOpenIcon,
  DocumentDuplicateIcon,
  ArrowsRightLeftIcon,
  ArrowDownTrayIcon,
  ClockIcon,
} from '@heroicons/vue/24/outline'

// Props
const props = defineProps({
  x: {
    type: Number,
    required: true,
  },
  y: {
    type: Number,
    required: true,
  },
  schedule: {
    type: Object,
    default: null,
  },
})

// Emits
const emit = defineEmits([
  'close',
  'view',
  'edit',
  'delete',
  'toggleLock',
  'swap',
  'duplicate',
  'export',
  'viewHistory',
])

// Methods
const handleView = () => {
  emit('view', props.schedule)
  emit('close')
}

const handleEdit = () => {
  emit('edit', props.schedule)
  emit('close')
}

const handleDelete = () => {
  if (confirm(`Apakah Anda yakin ingin menghapus jadwal ${props.schedule.subject?.name}?`)) {
    emit('delete', props.schedule)
  }
  emit('close')
}

const handleToggleLock = () => {
  emit('toggleLock', props.schedule)
  emit('close')
}

const handleSwap = () => {
  emit('swap', props.schedule)
  emit('close')
}

const handleDuplicate = async () => {
  try {
    const response = await fetch(`/api/academic-schedules/${props.schedule.id}/duplicate`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
      },
    })

    const data = await response.json()

    if (data.success) {
      // Show notification or refresh grid
      alert('Jadwal berhasil diduplikasi. Silakan edit untuk menyesuaikan waktu dan hari.')
      window.location.reload() // Refresh for now, better to emit event to parent
    } else {
      alert(data.message || 'Gagal menduplikasi jadwal')
    }
  } catch (error) {
    console.error('Error duplicating schedule:', error)
    alert('Terjadi kesalahan saat menduplikasi jadwal')
  }

  emit('close')
}

const handleExport = async () => {
  try {
    const response = await fetch(`/api/academic-schedules/${props.schedule.id}/export`)
    const data = await response.json()

    // Create download
    const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' })
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `schedule_${props.schedule.id}_${new Date().toISOString().split('T')[0]}.json`
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
    URL.revokeObjectURL(url)
  } catch (error) {
    console.error('Error exporting schedule:', error)
    alert('Gagal mengexport jadwal')
  }

  emit('close')
}

const handleViewHistory = () => {
  emit('viewHistory', props.schedule)
  emit('close')
}
</script>

<style scoped>
/* Ensure context menu appears above other elements */
.fixed {
  z-index: 9999;
}

/* Smooth animation */
.context-menu {
  animation: contextMenuFadeIn 0.1s ease-out;
}

@keyframes contextMenuFadeIn {
  from {
    opacity: 0;
    transform: scale(0.95);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

/* Prevent text selection */
button {
  user-select: none;
}

/* Hover effects */
button:hover {
  transition: background-color 0.1s ease;
}
</style>
