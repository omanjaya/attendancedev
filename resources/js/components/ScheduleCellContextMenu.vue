<template>
  <div
    class="fixed z-50 min-w-[200px] rounded-lg border border-gray-200 bg-white py-2 shadow-lg dark:border-gray-600 dark:bg-gray-800"
    :style="{ top: y + 'px', left: x + 'px' }"
    @click.stop
  >
    <!-- Cell Info Header -->
    <div class="border-b border-gray-100 px-4 py-2 dark:border-gray-700">
      <div class="text-xs text-gray-500 dark:text-gray-400">
        {{ getTimeSlotName() }} • {{ getClassName() }}
      </div>
      <div v-if="cellData.data" class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-100">
        {{ cellData.data.teacher_name }}
      </div>
    </div>

    <!-- Menu Items -->
    <div class="py-1">
      <!-- Edit/Assign Teacher -->
      <button
        class="flex w-full items-center space-x-3 px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
        @click="handleEdit"
      >
        <PencilIcon class="h-4 w-4" />
        <span>{{ cellData.data ? 'Edit Guru' : 'Pilih Guru' }}</span>
      </button>

      <!-- Clear Cell -->
      <button
        v-if="cellData.data"
        class="flex w-full items-center space-x-3 px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
        @click="handleClear"
      >
        <TrashIcon class="h-4 w-4" />
        <span>Hapus Jadwal</span>
      </button>

      <!-- Divider -->
      <div v-if="cellData.data" class="my-1 border-t border-gray-100 dark:border-gray-700" />

      <!-- Lock/Unlock -->
      <button
        v-if="cellData.data"
        class="flex w-full items-center space-x-3 px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
        @click="handleLock"
      >
        <LockClosedIcon v-if="!cellData.isLocked" class="h-4 w-4" />
        <LockOpenIcon v-else class="h-4 w-4" />
        <span>{{ cellData.isLocked ? 'Buka Kunci' : 'Kunci Sel' }}</span>
      </button>

      <!-- Copy Schedule -->
      <button
        v-if="cellData.data"
        class="flex w-full items-center space-x-3 px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
        @click="handleCopy"
      >
        <DocumentDuplicateIcon class="h-4 w-4" />
        <span>Salin Jadwal</span>
      </button>

      <!-- View Details -->
      <button
        v-if="cellData.data"
        class="flex w-full items-center space-x-3 px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
        @click="handleViewDetails"
      >
        <InformationCircleIcon class="h-4 w-4" />
        <span>Lihat Detail</span>
      </button>

      <!-- Divider -->
      <div class="my-1 border-t border-gray-100 dark:border-gray-700" />

      <!-- Mark as Break -->
      <button
        :class="[
          'flex w-full items-center space-x-3 px-4 py-2 text-left text-sm hover:bg-gray-100 dark:hover:bg-gray-700',
          isBreakTime ? 'text-orange-600 dark:text-orange-400' : 'text-gray-700 dark:text-gray-300',
        ]"
        @click="handleMarkBreak"
      >
        <ClockIcon class="h-4 w-4" />
        <span>{{ isBreakTime ? 'Batal Istirahat' : 'Tandai Istirahat' }}</span>
      </button>

      <!-- Export This Slot -->
      <button
        class="flex w-full items-center space-x-3 px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
        @click="handleExportSlot"
      >
        <ArrowDownTrayIcon class="h-4 w-4" />
        <span>Export Slot Ini</span>
      </button>
    </div>

    <!-- Quick Actions -->
    <div class="border-t border-gray-100 py-1 dark:border-gray-700">
      <div class="px-4 py-2">
        <div class="mb-2 text-xs text-gray-500 dark:text-gray-400">
          Quick Actions
        </div>
        <div class="flex space-x-2">
          <button
            v-if="cellData.data"
            class="flex-1 rounded bg-blue-50 px-2 py-1 text-xs text-blue-600 hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-400 dark:hover:bg-blue-900/50"
            @click="handleQuickAction('copy_to_all')"
          >
            Copy ke Semua
          </button>
          <button
            v-if="cellData.data"
            class="flex-1 rounded bg-green-50 px-2 py-1 text-xs text-green-600 hover:bg-green-100 dark:bg-green-900/30 dark:text-green-400 dark:hover:bg-green-900/50"
            @click="handleQuickAction('repeat_weekly')"
          >
            Ulangi Mingguan
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import {
  PencilIcon,
  TrashIcon,
  LockClosedIcon,
  LockOpenIcon,
  DocumentDuplicateIcon,
  InformationCircleIcon,
  ClockIcon,
  ArrowDownTrayIcon,
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
  cellData: {
    type: Object,
    required: true,
  },
})

// Emits
const emit = defineEmits([
  'close',
  'edit',
  'clear',
  'lock',
  'unlock',
  'copy',
  'view-details',
  'mark-break',
  'export-slot',
  'quick-action',
])

// Computed
const isBreakTime = computed(() => {
  return props.cellData.data?.type === 'break'
})

// Methods
const getTimeSlotName = () => {
  // This would need to be passed from parent or fetched
  return `Jam ${props.cellData.timeSlotId}`
}

const getClassName = () => {
  // This would need to be passed from parent or fetched
  return `Kelas ${props.cellData.classId}`
}

const handleEdit = () => {
  emit('edit', props.cellData.timeSlotId, props.cellData.classId)
}

const handleClear = () => {
  if (confirm('Hapus jadwal ini?')) {
    emit('clear', props.cellData.timeSlotId, props.cellData.classId)
  }
}

const handleLock = () => {
  if (props.cellData.isLocked) {
    emit('unlock', props.cellData.timeSlotId, props.cellData.classId)
  } else {
    emit('lock', props.cellData.timeSlotId, props.cellData.classId)
  }
}

const handleCopy = () => {
  emit('copy', props.cellData)
}

const handleViewDetails = () => {
  emit('view-details', props.cellData)
}

const handleMarkBreak = () => {
  emit('mark-break', props.cellData.timeSlotId, props.cellData.classId, !isBreakTime.value)
}

const handleExportSlot = () => {
  emit('export-slot', props.cellData)
}

const handleQuickAction = (action) => {
  emit('quick-action', action, props.cellData)
}
</script>

<style scoped>
/* Ensure menu appears above other elements */
.fixed {
  z-index: 9999;
}

/* Smooth hover transitions */
button:hover {
  transition: background-color 0.15s ease;
}

/* Custom menu shadow */
.shadow-lg {
  box-shadow:
    0 10px 25px -3px rgba(0, 0, 0, 0.1),
    0 4px 6px -2px rgba(0, 0, 0, 0.05);
}
</style>
