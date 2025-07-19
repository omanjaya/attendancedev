<template>
  <div class="schedule-calendar-container">
    <!-- Header Controls -->
    <div
      class="mb-6 rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800"
    >
      <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <!-- Title and Info -->
        <div>
          <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
            Jadwal Pelajaran
          </h2>
          <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            Klik sel untuk mengatur jadwal • Kode guru akan muncul di sel yang dipilih
          </p>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-wrap items-center gap-3">
          <button
            class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700"
            @click="showTeacherSelector = true"
          >
            <UserIcon class="mr-2 h-4 w-4" />
            Pilih Guru
          </button>

          <button
            :class="[
              'inline-flex items-center rounded-lg px-4 py-2 text-sm font-medium transition-colors',
              showConflicts
                ? 'bg-red-600 text-white hover:bg-red-700'
                : 'bg-red-50 text-red-600 hover:bg-red-100 dark:bg-red-900/30 dark:hover:bg-red-900/50',
            ]"
            @click="toggleConflictMode"
          >
            <ExclamationTriangleIcon class="mr-2 h-4 w-4" />
            Konflik ({{ conflictCells.length }})
          </button>

          <button
            class="inline-flex items-center rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
            @click="exportJSON"
          >
            <DocumentArrowDownIcon class="mr-2 h-4 w-4" />
            Export JSON
          </button>

          <button
            class="inline-flex items-center rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
            @click="importJSON"
          >
            <DocumentArrowUpIcon class="mr-2 h-4 w-4" />
            Import JSON
          </button>

          <button
            :disabled="!canUndo"
            class="inline-flex items-center rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-200 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
            @click="undoLastAction"
          >
            <ArrowUturnLeftIcon class="mr-2 h-4 w-4" />
            Undo
          </button>

          <button
            class="inline-flex items-center rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-red-700"
            @click="resetSchedule"
          >
            <ArrowPathIcon class="mr-2 h-4 w-4" />
            Reset
          </button>
        </div>
      </div>
    </div>

    <!-- Schedule Calendar Grid -->
    <div
      class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800"
    >
      <!-- Desktop View -->
      <div class="hidden lg:block">
        <div class="overflow-x-auto">
          <table class="w-full border-collapse">
            <!-- Header -->
            <thead>
              <tr class="bg-gray-50 dark:bg-gray-700">
                <th
                  class="w-32 border-r border-gray-200 px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:border-gray-600 dark:text-gray-400"
                >
                  Jam
                </th>
                <th
                  v-for="grade in grades"
                  :key="grade"
                  class="border-r border-gray-200 px-4 py-3 text-center text-xs font-medium uppercase text-gray-500 dark:border-gray-600 dark:text-gray-400"
                  :colspan="classesByGrade[grade].length"
                >
                  Kelas {{ grade }}
                </th>
              </tr>
              <tr class="bg-gray-100 dark:bg-gray-700/50">
                <th
                  class="border-r border-gray-200 px-4 py-2 text-xs font-medium text-gray-500 dark:border-gray-600 dark:text-gray-400"
                >
                  Waktu
                </th>
                <th
                  v-for="academicClass in academicClasses"
                  :key="academicClass.id"
                  class="min-w-[100px] border-r border-gray-200 px-2 py-2 text-xs font-medium text-gray-500 dark:border-gray-600 dark:text-gray-400"
                >
                  {{ academicClass.name }}
                </th>
              </tr>
            </thead>

            <!-- Body -->
            <tbody>
              <tr
                v-for="timeSlot in timeSlots"
                :key="timeSlot.id"
                class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/30"
              >
                <!-- Time Column -->
                <td
                  class="border-r border-gray-200 bg-gray-50 px-4 py-3 text-sm font-medium text-gray-900 dark:border-gray-600 dark:bg-gray-700/30 dark:text-gray-100"
                >
                  <div class="text-center">
                    <div class="font-semibold">
                      {{ timeSlot.name }}
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                      {{ timeSlot.start_time }} - {{ timeSlot.end_time }}
                    </div>
                  </div>
                </td>

                <!-- Schedule Cells -->
                <td
                  v-for="academicClass in academicClasses"
                  :key="`${timeSlot.id}-${academicClass.id}`"
                  class="relative border-b border-r border-gray-200 p-1 dark:border-gray-600"
                >
                  <div
                    :class="getCellClass(timeSlot.id, academicClass.id)"
                    class="group relative flex min-h-[60px] cursor-pointer items-center justify-center rounded transition-all duration-200"
                    @click="handleCellClick(timeSlot.id, academicClass.id)"
                    @contextmenu.prevent="
                      handleCellRightClick($event, timeSlot.id, academicClass.id)
                    "
                  >
                    <!-- Cell Content -->
                    <div class="w-full text-center">
                      <div
                        v-if="getCellData(timeSlot.id, academicClass.id)"
                        class="text-sm font-medium"
                      >
                        {{ getCellData(timeSlot.id, academicClass.id).teacher_code }}
                      </div>
                      <div
                        v-if="getCellData(timeSlot.id, academicClass.id)"
                        class="mt-1 text-xs opacity-80"
                      >
                        {{ getCellData(timeSlot.id, academicClass.id).subject_code }}
                      </div>
                      <div v-else class="text-xs text-gray-400 dark:text-gray-500">
                        Klik untuk isi
                      </div>
                    </div>

                    <!-- Lock Indicator -->
                    <div
                      v-if="isCellLocked(timeSlot.id, academicClass.id)"
                      class="absolute right-1 top-1"
                    >
                      <LockClosedIcon class="h-3 w-3 text-red-500" />
                    </div>

                    <!-- Conflict Indicator -->
                    <div
                      v-if="isCellConflict(timeSlot.id, academicClass.id)"
                      class="absolute left-1 top-1"
                    >
                      <ExclamationTriangleIcon class="h-3 w-3 text-yellow-500" />
                    </div>

                    <!-- Hover Actions -->
                    <div
                      class="absolute inset-0 rounded bg-black bg-opacity-0 transition-all duration-200 group-hover:bg-opacity-5"
                    />
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Mobile View -->
      <div class="p-4 lg:hidden">
        <div class="text-center text-gray-500 dark:text-gray-400">
          <ComputerDesktopIcon class="mx-auto mb-4 h-12 w-12" />
          <p class="text-lg font-medium">
            Desktop Only
          </p>
          <p class="text-sm">
            Calendar view is optimized for desktop use. Please use a larger screen.
          </p>
        </div>
      </div>
    </div>

    <!-- Selected Teacher Info -->
    <div
      v-if="selectedTeacher"
      class="mt-4 rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-700 dark:bg-blue-900/30"
    >
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-3">
          <div class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-600">
            <span class="text-sm font-bold text-white">{{ selectedTeacher.teacher_code }}</span>
          </div>
          <div>
            <p class="font-medium text-blue-900 dark:text-blue-200">
              {{ selectedTeacher.name }}
            </p>
            <p class="text-sm text-blue-700 dark:text-blue-300">
              {{ selectedTeacher.subjects.map((s) => s.name).join(', ') }}
            </p>
          </div>
        </div>
        <button
          class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-200"
          @click="selectedTeacher = null"
        >
          <XMarkIcon class="h-5 w-5" />
        </button>
      </div>
    </div>

    <!-- Teacher Selector Modal -->
    <TeacherSelectorModal
      v-if="showTeacherSelector"
      @close="showTeacherSelector = false"
      @select="onTeacherSelected"
    />

    <!-- Context Menu -->
    <ScheduleCellContextMenu
      v-if="showContextMenu"
      :x="contextMenu.x"
      :y="contextMenu.y"
      :cellData="contextMenu.cellData"
      @close="showContextMenu = false"
      @lock="lockCell"
      @unlock="unlockCell"
      @clear="clearCell"
      @edit="editCell"
    />

    <!-- Conflict Details Modal -->
    <ConflictDetailsModal
      v-if="showConflictDetails"
      :conflicts="currentConflicts"
      @close="showConflictDetails = false"
      @resolve="resolveConflict"
    />
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import {
  UserIcon,
  ExclamationTriangleIcon,
  DocumentArrowDownIcon,
  DocumentArrowUpIcon,
  ArrowUturnLeftIcon,
  ArrowPathIcon,
  LockClosedIcon,
  XMarkIcon,
  ComputerDesktopIcon,
} from '@heroicons/vue/24/outline'
import TeacherSelectorModal from './TeacherSelectorModal.vue'
import ScheduleCellContextMenu from './ScheduleCellContextMenu.vue'
import ConflictDetailsModal from './ConflictDetailsModal.vue'

// Props
const props = defineProps({
  academicClasses: {
    type: Array,
    default: () => [],
  },
  timeSlots: {
    type: Array,
    default: () => [],
  },
})

// Reactive state
const scheduleData = ref({})
const selectedTeacher = ref(null)
const showTeacherSelector = ref(false)
const showConflicts = ref(false)
const conflictCells = ref([])
const lockedCells = ref(new Set())
const actionHistory = ref([])
const showContextMenu = ref(false)
const showConflictDetails = ref(false)
const currentConflicts = ref([])

// Context menu state
const contextMenu = reactive({
  x: 0,
  y: 0,
  cellData: null,
})

// Computed properties
const grades = computed(() => {
  const gradeSet = new Set(props.academicClasses.map((c) => c.grade_level))
  return Array.from(gradeSet).sort()
})

const classesByGrade = computed(() => {
  return props.academicClasses.reduce((acc, cls) => {
    if (!acc[cls.grade_level]) {acc[cls.grade_level] = []}
    acc[cls.grade_level].push(cls)
    return acc
  }, {})
})

const canUndo = computed(() => actionHistory.value.length > 0)

// Methods
const getCellClass = (timeSlotId, classId) => {
  const key = `${timeSlotId}-${classId}`
  const hasData = getCellData(timeSlotId, classId)
  const isLocked = isCellLocked(timeSlotId, classId)
  const isConflict = isCellConflict(timeSlotId, classId)

  const classes = ['border border-gray-300 dark:border-gray-600']

  if (hasData) {
    if (isConflict && showConflicts.value) {
      classes.push(
        'bg-red-100 text-red-800 border-red-300 dark:bg-red-900/30 dark:text-red-200 dark:border-red-600'
      )
    } else if (isLocked) {
      classes.push('bg-gray-100 text-gray-600 border-gray-400 dark:bg-gray-700 dark:text-gray-300')
    } else {
      classes.push(
        'bg-green-50 text-green-800 border-green-300 hover:bg-green-100 dark:bg-green-900/30 dark:text-green-200 dark:border-green-600'
      )
    }
  } else {
    classes.push('bg-white hover:bg-gray-50 border-dashed dark:bg-gray-800 dark:hover:bg-gray-700')
  }

  return classes.join(' ')
}

const getCellData = (timeSlotId, classId) => {
  const key = `${timeSlotId}-${classId}`
  return scheduleData.value[key] || null
}

const isCellLocked = (timeSlotId, classId) => {
  const key = `${timeSlotId}-${classId}`
  return lockedCells.value.has(key)
}

const isCellConflict = (timeSlotId, classId) => {
  const key = `${timeSlotId}-${classId}`
  return conflictCells.value.includes(key)
}

const handleCellClick = (timeSlotId, classId) => {
  if (isCellLocked(timeSlotId, classId)) {
    alert('Sel ini terkunci dan tidak dapat diubah')
    return
  }

  if (!selectedTeacher.value) {
    showTeacherSelector.value = true
    return
  }

  const key = `${timeSlotId}-${classId}`
  const existingData = getCellData(timeSlotId, classId)

  // Save action for undo
  actionHistory.value.push({
    type: existingData ? 'update' : 'create',
    key,
    oldData: existingData,
    newData: {
      teacher_code: selectedTeacher.value.teacher_code,
      teacher_name: selectedTeacher.value.name,
      subject_code: selectedTeacher.value.subjects[0]?.code || 'N/A',
      subject_name: selectedTeacher.value.subjects[0]?.name || 'No Subject',
      time_slot_id: timeSlotId,
      class_id: classId,
      created_at: new Date().toISOString(),
    },
  })

  // Update cell
  scheduleData.value[key] = actionHistory.value[actionHistory.value.length - 1].newData

  // Check for conflicts
  detectConflicts()

  // Auto-save to browser
  saveToLocalStorage()
}

const handleCellRightClick = (event, timeSlotId, classId) => {
  event.preventDefault()

  contextMenu.x = event.clientX
  contextMenu.y = event.clientY
  contextMenu.cellData = {
    timeSlotId,
    classId,
    data: getCellData(timeSlotId, classId),
    isLocked: isCellLocked(timeSlotId, classId),
  }

  showContextMenu.value = true
}

const onTeacherSelected = (teacher) => {
  selectedTeacher.value = teacher
  showTeacherSelector.value = false
}

const detectConflicts = () => {
  const conflicts = []
  const teacherSlots = {}

  // Group by teacher and time slot
  Object.entries(scheduleData.value).forEach(([key, data]) => {
    if (!data) {return}

    const teacherKey = `${data.teacher_code}-${data.time_slot_id}`
    if (!teacherSlots[teacherKey]) {
      teacherSlots[teacherKey] = []
    }
    teacherSlots[teacherKey].push(key)
  })

  // Find conflicts (teacher teaching multiple classes at same time)
  Object.values(teacherSlots).forEach((slots) => {
    if (slots.length > 1) {
      conflicts.push(...slots)
    }
  })

  conflictCells.value = conflicts
}

const toggleConflictMode = () => {
  showConflicts.value = !showConflicts.value
  if (showConflicts.value && conflictCells.value.length > 0) {
    showConflictDetails.value = true
    currentConflicts.value = getConflictDetails()
  }
}

const getConflictDetails = () => {
  const conflicts = []
  const teacherSlots = {}

  Object.entries(scheduleData.value).forEach(([key, data]) => {
    if (!data) {return}

    const teacherKey = `${data.teacher_code}-${data.time_slot_id}`
    if (!teacherSlots[teacherKey]) {
      teacherSlots[teacherKey] = []
    }
    teacherSlots[teacherKey].push({ key, data })
  })

  Object.values(teacherSlots).forEach((slots) => {
    if (slots.length > 1) {
      conflicts.push({
        teacher: slots[0].data.teacher_name,
        teacher_code: slots[0].data.teacher_code,
        time_slot: slots[0].data.time_slot_id,
        classes: slots.map((s) => s.data),
        type: 'teacher_double_booking',
      })
    }
  })

  return conflicts
}

const lockCell = (timeSlotId, classId) => {
  const key = `${timeSlotId}-${classId}`
  lockedCells.value.add(key)
  showContextMenu.value = false
}

const unlockCell = (timeSlotId, classId) => {
  const key = `${timeSlotId}-${classId}`
  lockedCells.value.delete(key)
  showContextMenu.value = false
}

const clearCell = (timeSlotId, classId) => {
  const key = `${timeSlotId}-${classId}`
  const existingData = getCellData(timeSlotId, classId)

  if (existingData) {
    actionHistory.value.push({
      type: 'delete',
      key,
      oldData: existingData,
      newData: null,
    })

    delete scheduleData.value[key]
    detectConflicts()
    saveToLocalStorage()
  }

  showContextMenu.value = false
}

const editCell = (timeSlotId, classId) => {
  showTeacherSelector.value = true
  showContextMenu.value = false
}

const undoLastAction = () => {
  if (actionHistory.value.length === 0) {return}

  const lastAction = actionHistory.value.pop()

  if (lastAction.type === 'create') {
    delete scheduleData.value[lastAction.key]
  } else if (lastAction.type === 'update' || lastAction.type === 'delete') {
    if (lastAction.oldData) {
      scheduleData.value[lastAction.key] = lastAction.oldData
    } else {
      delete scheduleData.value[lastAction.key]
    }
  }

  detectConflicts()
  saveToLocalStorage()
}

const resetSchedule = () => {
  if (
    confirm('Apakah Anda yakin ingin mereset semua jadwal? Tindakan ini tidak dapat dibatalkan.')
  ) {
    scheduleData.value = {}
    conflictCells.value = []
    lockedCells.value.clear()
    actionHistory.value = []
    selectedTeacher.value = null
    saveToLocalStorage()
  }
}

const exportJSON = () => {
  const exportData = {
    schedule: scheduleData.value,
    locked_cells: Array.from(lockedCells.value),
    exported_at: new Date().toISOString(),
    academic_classes: props.academicClasses,
    time_slots: props.timeSlots,
  }

  const blob = new Blob([JSON.stringify(exportData, null, 2)], { type: 'application/json' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `jadwal_${new Date().toISOString().split('T')[0]}.json`
  document.body.appendChild(a)
  a.click()
  document.body.removeChild(a)
  URL.revokeObjectURL(url)
}

const importJSON = () => {
  const input = document.createElement('input')
  input.type = 'file'
  input.accept = '.json'

  input.onchange = (e) => {
    const file = e.target.files[0]
    if (!file) {return}

    const reader = new FileReader()
    reader.onload = (e) => {
      try {
        const data = JSON.parse(e.target.result)

        if (confirm('Import akan mengganti jadwal yang ada. Lanjutkan?')) {
          scheduleData.value = data.schedule || {}
          lockedCells.value = new Set(data.locked_cells || [])
          detectConflicts()
          saveToLocalStorage()
        }
      } catch (error) {
        alert('File JSON tidak valid: ' + error.message)
      }
    }
    reader.readAsText(file)
  }

  input.click()
}

const resolveConflict = (conflict) => {
  // Implementation for resolving conflicts
  showConflictDetails.value = false
}

const saveToLocalStorage = () => {
  const saveData = {
    schedule: scheduleData.value,
    locked_cells: Array.from(lockedCells.value),
    last_saved: new Date().toISOString(),
  }
  localStorage.setItem('schedule_calendar_data', JSON.stringify(saveData))
}

const loadFromLocalStorage = () => {
  try {
    const saved = localStorage.getItem('schedule_calendar_data')
    if (saved) {
      const data = JSON.parse(saved)
      scheduleData.value = data.schedule || {}
      lockedCells.value = new Set(data.locked_cells || [])
      detectConflicts()
    }
  } catch (error) {
    console.error('Error loading from localStorage:', error)
  }
}

// Lifecycle
onMounted(() => {
  loadFromLocalStorage()

  // Auto-save every 30 seconds
  setInterval(saveToLocalStorage, 30000)

  // Handle click outside context menu
  document.addEventListener('click', () => {
    showContextMenu.value = false
  })
})

// Watchers
watch(
  scheduleData,
  () => {
    detectConflicts()
  },
  { deep: true }
)
</script>

<style scoped>
.schedule-calendar-container {
  @apply mx-auto max-w-full p-6;
}

/* Custom scrollbar */
.overflow-x-auto::-webkit-scrollbar {
  height: 8px;
}

.overflow-x-auto::-webkit-scrollbar-track {
  background: #f1f1f1;
}

.overflow-x-auto::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 4px;
}

.overflow-x-auto::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}

/* Cell hover effects */
.group:hover .absolute {
  opacity: 1;
}

/* Smooth transitions */
* {
  transition:
    background-color 0.2s ease,
    border-color 0.2s ease,
    color 0.2s ease;
}
</style>
