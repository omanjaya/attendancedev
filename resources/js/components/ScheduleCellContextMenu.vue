<template>
  <div 
    class="fixed z-50 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-600 py-2 min-w-[200px]"
    :style="{ top: y + 'px', left: x + 'px' }"
    @click.stop
  >
    <!-- Cell Info Header -->
    <div class="px-4 py-2 border-b border-gray-100 dark:border-gray-700">
      <div class="text-xs text-gray-500 dark:text-gray-400">
        {{ getTimeSlotName() }} • {{ getClassName() }}
      </div>
      <div v-if="cellData.data" class="text-sm font-medium text-gray-900 dark:text-gray-100 mt-1">
        {{ cellData.data.teacher_name }}
      </div>
    </div>

    <!-- Menu Items -->
    <div class="py-1">
      <!-- Edit/Assign Teacher -->
      <button
        @click="handleEdit"
        class="w-full px-4 py-2 text-left text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center space-x-3"
      >
        <PencilIcon class="w-4 h-4" />
        <span>{{ cellData.data ? 'Edit Guru' : 'Pilih Guru' }}</span>
      </button>

      <!-- Clear Cell -->
      <button
        v-if="cellData.data"
        @click="handleClear"
        class="w-full px-4 py-2 text-left text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center space-x-3"
      >
        <TrashIcon class="w-4 h-4" />
        <span>Hapus Jadwal</span>
      </button>

      <!-- Divider -->
      <div v-if="cellData.data" class="border-t border-gray-100 dark:border-gray-700 my-1"></div>

      <!-- Lock/Unlock -->
      <button
        v-if="cellData.data"
        @click="handleLock"
        class="w-full px-4 py-2 text-left text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center space-x-3"
      >
        <LockClosedIcon v-if="!cellData.isLocked" class="w-4 h-4" />
        <LockOpenIcon v-else class="w-4 h-4" />
        <span>{{ cellData.isLocked ? 'Buka Kunci' : 'Kunci Sel' }}</span>
      </button>

      <!-- Copy Schedule -->
      <button
        v-if="cellData.data"
        @click="handleCopy"
        class="w-full px-4 py-2 text-left text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center space-x-3"
      >
        <DocumentDuplicateIcon class="w-4 h-4" />
        <span>Salin Jadwal</span>
      </button>

      <!-- View Details -->
      <button
        v-if="cellData.data"
        @click="handleViewDetails"
        class="w-full px-4 py-2 text-left text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center space-x-3"
      >
        <InformationCircleIcon class="w-4 h-4" />
        <span>Lihat Detail</span>
      </button>

      <!-- Divider -->
      <div class="border-t border-gray-100 dark:border-gray-700 my-1"></div>

      <!-- Mark as Break -->
      <button
        @click="handleMarkBreak"
        :class="[
          'w-full px-4 py-2 text-left text-sm hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center space-x-3',
          isBreakTime ? 'text-orange-600 dark:text-orange-400' : 'text-gray-700 dark:text-gray-300'
        ]"
      >
        <ClockIcon class="w-4 h-4" />
        <span>{{ isBreakTime ? 'Batal Istirahat' : 'Tandai Istirahat' }}</span>
      </button>

      <!-- Export This Slot -->
      <button
        @click="handleExportSlot"
        class="w-full px-4 py-2 text-left text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center space-x-3"
      >
        <ArrowDownTrayIcon class="w-4 h-4" />
        <span>Export Slot Ini</span>
      </button>
    </div>

    <!-- Quick Actions -->
    <div class="border-t border-gray-100 dark:border-gray-700 py-1">
      <div class="px-4 py-2">
        <div class="text-xs text-gray-500 dark:text-gray-400 mb-2">Quick Actions</div>
        <div class="flex space-x-2">
          <button
            @click="handleQuickAction('copy_to_all')"
            class="flex-1 px-2 py-1 text-xs bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded hover:bg-blue-100 dark:hover:bg-blue-900/50"
            v-if="cellData.data"
          >
            Copy ke Semua
          </button>
          <button
            @click="handleQuickAction('repeat_weekly')"
            class="flex-1 px-2 py-1 text-xs bg-green-50 dark:bg-green-900/30 text-green-600 dark:text-green-400 rounded hover:bg-green-100 dark:hover:bg-green-900/50"
            v-if="cellData.data"
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
  ArrowDownTrayIcon
} from '@heroicons/vue/24/outline'

// Props
const props = defineProps({
  x: {
    type: Number,
    required: true
  },
  y: {
    type: Number,
    required: true
  },
  cellData: {
    type: Object,
    required: true
  }
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
  'quick-action'
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
  box-shadow: 0 10px 25px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
}
</style>