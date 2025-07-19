<template>
  <div class="schedule-grid-container">
    <!-- Header Controls -->
    <div
      class="mb-6 rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800"
    >
      <div
        class="flex flex-col space-y-4 lg:flex-row lg:items-center lg:justify-between lg:space-y-0"
      >
        <!-- Class Selection -->
        <div class="flex items-center space-x-4">
          <label class="text-sm font-medium text-gray-700 dark:text-gray-300"> Pilih Kelas: </label>
          <select
            v-model="selectedClassId"
            class="rounded-lg border border-gray-300 bg-white px-3 py-2 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-700"
            @change="loadScheduleGrid"
          >
            <option value="">
              -- Pilih Kelas --
            </option>
            <option
              v-for="academicClass in academicClasses"
              :key="academicClass.id"
              :value="academicClass.id"
            >
              {{ academicClass.full_name }}
            </option>
          </select>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center space-x-3">
          <button
            :disabled="!selectedClassId"
            class="inline-flex items-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50"
            @click="showAddModal = true"
          >
            <PlusIcon class="mr-2 h-4 w-4" />
            Tambah Jadwal
          </button>

          <button
            :disabled="!selectedClassId"
            class="inline-flex items-center rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-200 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
            @click="exportSchedule"
          >
            <ArrowDownTrayIcon class="mr-2 h-4 w-4" />
            Export JSON
          </button>

          <button
            :disabled="!selectedClassId"
            :class="[
              'inline-flex items-center rounded-lg px-4 py-2 text-sm font-medium transition-colors disabled:cursor-not-allowed disabled:opacity-50',
              showConflicts
                ? 'bg-red-600 text-white hover:bg-red-700'
                : 'bg-red-50 text-red-600 hover:bg-red-100 dark:bg-red-900/30 dark:hover:bg-red-900/50',
            ]"
            @click="toggleConflictsView"
          >
            <ExclamationTriangleIcon class="mr-2 h-4 w-4" />
            Konflik ({{ conflictCount }})
          </button>
        </div>
      </div>
    </div>

    <!-- Schedule Grid -->
    <div
      v-if="selectedClassId && gridData"
      class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800"
    >
      <!-- Mobile View -->
      <div class="lg:hidden">
        <div
          v-for="(dayData, dayKey) in gridData"
          :key="dayKey"
          class="border-b border-gray-200 dark:border-gray-600"
        >
          <div class="bg-gray-50 px-4 py-3 dark:bg-gray-700">
            <h3 class="font-semibold text-gray-900 dark:text-gray-100">
              {{ dayData.day_name }}
            </h3>
          </div>

          <div class="divide-y divide-gray-200 dark:divide-gray-600">
            <div v-for="(slotData, slotId) in dayData.slots" :key="slotId" class="p-4">
              <div class="mb-2 flex items-center justify-between">
                <span class="text-sm font-medium text-gray-600 dark:text-gray-400">
                  {{ slotData.time_slot.name }}
                </span>
                <span class="text-xs text-gray-500 dark:text-gray-400">
                  {{ slotData.time_slot.start_time }} - {{ slotData.time_slot.end_time }}
                </span>
              </div>

              <div
                v-if="slotData.schedule"
                :class="[
                  'cursor-pointer rounded-lg p-3 transition-all duration-200',
                  getScheduleClass(slotData.status, slotData.display?.color),
                ]"
                @click="showScheduleDetail(slotData.schedule)"
              >
                <div class="text-sm font-medium">
                  {{ slotData.display?.subject_code }}
                </div>
                <div class="text-xs opacity-90">
                  {{ slotData.display?.teacher_name }}
                </div>
                <div v-if="slotData.display?.room" class="text-xs opacity-75">
                  {{ slotData.display?.room }}
                </div>
              </div>

              <div
                v-else
                class="cursor-pointer rounded-lg border-2 border-dashed border-gray-300 p-3 transition-colors hover:border-emerald-400 dark:border-gray-600 dark:hover:border-emerald-500"
                @click="addSchedule(dayKey, slotId)"
              >
                <div class="text-center text-gray-500 dark:text-gray-400">
                  <PlusIcon class="mx-auto mb-1 h-4 w-4" />
                  <span class="text-xs">Tambah</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Desktop Grid View -->
      <div class="hidden overflow-x-auto lg:block">
        <table class="w-full">
          <!-- Header -->
          <thead>
            <tr class="bg-gray-50 dark:bg-gray-700">
              <th
                class="w-32 px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400"
              >
                Jam
              </th>
              <th
                v-for="(dayData, dayKey) in gridData"
                :key="dayKey"
                class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400"
              >
                {{ dayData.day_name }}
              </th>
            </tr>
          </thead>

          <!-- Body -->
          <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
            <tr
              v-for="timeSlot in timeSlots"
              :key="timeSlot.id"
              class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-700"
            >
              <!-- Time Column -->
              <td
                class="border-r border-gray-200 px-4 py-4 text-sm font-medium text-gray-900 dark:border-gray-600 dark:text-gray-100"
              >
                <div>{{ timeSlot.name }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">
                  {{ timeSlot.start_time }} - {{ timeSlot.end_time }}
                </div>
              </td>

              <!-- Schedule Cells -->
              <td
                v-for="(dayData, dayKey) in gridData"
                :key="`${dayKey}-${timeSlot.id}`"
                class="relative border-r border-gray-200 px-2 py-2 dark:border-gray-600"
              >
                <div class="flex min-h-[80px] items-center justify-center">
                  <!-- Scheduled Item -->
                  <div
                    v-if="dayData.slots[timeSlot.id]?.schedule"
                    :class="[
                      'group w-full cursor-pointer rounded-lg p-3 transition-all duration-200',
                      getScheduleClass(
                        dayData.slots[timeSlot.id].status,
                        dayData.slots[timeSlot.id].display?.color
                      ),
                    ]"
                    draggable="true"
                    @click="showScheduleDetail(dayData.slots[timeSlot.id].schedule)"
                    @contextmenu.prevent="
                      showContextMenu(
                        $event,
                        dayData.slots[timeSlot.id].schedule,
                        dayKey,
                        timeSlot.id
                      )
                    "
                    @dragstart="onDragStart($event, dayData.slots[timeSlot.id].schedule)"
                    @dragover.prevent
                    @drop="onDrop($event, dayKey, timeSlot.id)"
                  >
                    <div class="text-center">
                      <div class="mb-1 text-sm font-semibold">
                        {{ dayData.slots[timeSlot.id].display?.subject_code }}
                      </div>
                      <div class="mb-1 text-xs opacity-90">
                        {{ dayData.slots[timeSlot.id].display?.teacher_name }}
                      </div>
                      <div
                        v-if="dayData.slots[timeSlot.id].display?.room"
                        class="text-xs opacity-75"
                      >
                        {{ dayData.slots[timeSlot.id].display?.room }}
                      </div>
                    </div>

                    <!-- Status Indicators -->
                    <div class="absolute right-1 top-1 flex space-x-1">
                      <div
                        v-if="dayData.slots[timeSlot.id].display?.is_locked"
                        class="h-3 w-3 rounded-full bg-red-500"
                        title="Terkunci"
                      />
                      <div
                        v-if="dayData.slots[timeSlot.id].status === 'conflict'"
                        class="h-3 w-3 rounded-full bg-yellow-500"
                        title="Konflik"
                      />
                    </div>
                  </div>

                  <!-- Empty Slot -->
                  <div
                    v-else
                    class="group flex min-h-[60px] w-full cursor-pointer items-center justify-center rounded-lg border-2 border-dashed border-gray-300 transition-colors hover:border-emerald-400 dark:border-gray-600 dark:hover:border-emerald-500"
                    @click="addSchedule(dayKey, timeSlot.id)"
                    @dragover.prevent
                    @drop="onDrop($event, dayKey, timeSlot.id)"
                  >
                    <div
                      class="text-center text-gray-500 group-hover:text-emerald-500 dark:text-gray-400 dark:group-hover:text-emerald-400"
                    >
                      <PlusIcon class="mx-auto mb-1 h-5 w-5" />
                      <span class="text-xs">Tambah</span>
                    </div>
                  </div>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Conflicts Panel -->
    <div
      v-if="showConflicts && conflicts.length > 0"
      class="mt-6 rounded-xl border border-red-200 bg-red-50 p-6 dark:border-red-700/50 dark:bg-red-900/30"
    >
      <h3 class="mb-4 text-lg font-semibold text-red-800 dark:text-red-200">
        Konflik Jadwal Terdeteksi
      </h3>

      <div class="space-y-3">
        <div
          v-for="conflict in conflicts"
          :key="conflict.id"
          class="rounded-lg border border-red-200 bg-white p-4 dark:border-red-700 dark:bg-gray-800"
        >
          <div class="flex items-start justify-between">
            <div class="flex-1">
              <div class="mb-2 flex items-center space-x-2">
                <span
                  :class="[
                    'rounded-full px-2 py-1 text-xs font-medium',
                    conflict.severity === 'critical'
                      ? 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-200'
                      : conflict.severity === 'high'
                        ? 'bg-orange-100 text-orange-800 dark:bg-orange-800 dark:text-orange-200'
                        : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-200',
                  ]"
                >
                  {{ conflict.severity.toUpperCase() }}
                </span>
                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                  {{ conflict.conflict_type_name }}
                </span>
              </div>
              <p class="text-sm text-gray-600 dark:text-gray-300">
                {{ conflict.description }}
              </p>
              <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                Terdeteksi: {{ formatDateTime(conflict.detected_at) }}
              </p>
            </div>

            <button
              class="ml-4 rounded-lg bg-red-100 px-3 py-1 text-xs font-medium text-red-600 transition-colors hover:bg-red-200 dark:bg-red-900/50 dark:text-red-400 dark:hover:bg-red-900/70"
              @click="resolveConflict(conflict)"
            >
              Selesaikan
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Add/Edit Schedule Modal -->
    <ScheduleModal
      v-if="showAddModal || showEditModal"
      :schedule="selectedSchedule"
      :academicClassId="selectedClassId"
      :dayOfWeek="modalData.dayOfWeek"
      :timeSlotId="modalData.timeSlotId"
      :subjects="subjects"
      @close="closeModal"
      @saved="onScheduleSaved"
    />

    <!-- Schedule Detail Modal -->
    <ScheduleDetailModal
      v-if="showDetailModal"
      :schedule="selectedSchedule"
      @close="showDetailModal = false"
      @edit="editSchedule"
      @delete="deleteSchedule"
      @toggleLock="toggleScheduleLock"
    />

    <!-- Context Menu -->
    <ContextMenu
      v-if="showContextMenuFlag"
      :x="contextMenu.x"
      :y="contextMenu.y"
      :schedule="contextMenu.schedule"
      @close="showContextMenuFlag = false"
      @edit="editSchedule"
      @delete="deleteSchedule"
      @toggleLock="toggleScheduleLock"
      @swap="startSwapMode"
    />

    <!-- Loading Overlay -->
    <div
      v-if="loading"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
    >
      <div class="rounded-lg bg-white p-6 dark:bg-gray-800">
        <div class="flex items-center space-x-3">
          <div class="h-6 w-6 animate-spin rounded-full border-b-2 border-emerald-600" />
          <span class="text-gray-900 dark:text-gray-100">{{ loadingMessage }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { PlusIcon, ArrowDownTrayIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline'
import ScheduleModal from './ScheduleModal.vue'
import ScheduleDetailModal from './ScheduleDetailModal.vue'
import ContextMenu from './ScheduleContextMenu.vue'

// Props
const props = defineProps({
  academicClasses: {
    type: Array,
    default: () => [],
  },
  subjects: {
    type: Array,
    default: () => [],
  },
})

// Reactive state
const selectedClassId = ref('')
const gridData = ref(null)
const timeSlots = ref([])
const conflicts = ref([])
const loading = ref(false)
const loadingMessage = ref('')

// Modal states
const showAddModal = ref(false)
const showEditModal = ref(false)
const showDetailModal = ref(false)
const showConflicts = ref(false)
const selectedSchedule = ref(null)

// Context menu
const showContextMenuFlag = ref(false)
const contextMenu = reactive({
  x: 0,
  y: 0,
  schedule: null,
})

// Modal data
const modalData = reactive({
  dayOfWeek: '',
  timeSlotId: '',
})

// Drag and drop
const draggedSchedule = ref(null)
const swapMode = ref(false)
const swapSchedule1 = ref(null)

// Computed
const conflictCount = computed(() => conflicts.value.length)

// Methods
const loadScheduleGrid = async () => {
  if (!selectedClassId.value) {
    gridData.value = null
    return
  }

  loading.value = true
  loadingMessage.value = 'Memuat jadwal...'

  try {
    const response = await fetch(`/api/academic-schedules/grid/${selectedClassId.value}`)
    const data = await response.json()

    if (data.success) {
      gridData.value = data.data.grid
      timeSlots.value = data.data.time_slots

      // Load conflicts
      await loadConflicts()
    }
  } catch (error) {
    console.error('Error loading schedule grid:', error)
  } finally {
    loading.value = false
  }
}

const loadConflicts = async () => {
  if (!selectedClassId.value) {return}

  try {
    const response = await fetch(`/api/academic-schedules/conflicts/${selectedClassId.value}`)
    const data = await response.json()

    if (data.success) {
      conflicts.value = data.data
    }
  } catch (error) {
    console.error('Error loading conflicts:', error)
  }
}

const addSchedule = (dayOfWeek, timeSlotId) => {
  modalData.dayOfWeek = dayOfWeek
  modalData.timeSlotId = timeSlotId
  selectedSchedule.value = null
  showAddModal.value = true
}

const editSchedule = (schedule) => {
  selectedSchedule.value = schedule
  showEditModal.value = true
  closeContextMenu()
}

const showScheduleDetail = (schedule) => {
  selectedSchedule.value = schedule
  showDetailModal.value = true
}

const deleteSchedule = async (schedule) => {
  if (!confirm('Apakah Anda yakin ingin menghapus jadwal ini?')) {return}

  loading.value = true
  loadingMessage.value = 'Menghapus jadwal...'

  try {
    const response = await fetch(`/api/academic-schedules/${schedule.id}`, {
      method: 'DELETE',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
      },
    })

    const data = await response.json()

    if (data.success) {
      await loadScheduleGrid()
      showDetailModal.value = false
    } else {
      alert(data.message || 'Gagal menghapus jadwal')
    }
  } catch (error) {
    console.error('Error deleting schedule:', error)
    alert('Terjadi kesalahan saat menghapus jadwal')
  } finally {
    loading.value = false
  }

  closeContextMenu()
}

const toggleScheduleLock = async (schedule) => {
  const reason = prompt(`${schedule.is_locked ? 'Buka' : 'Kunci'} jadwal. Masukkan alasan:`)
  if (!reason) {return}

  loading.value = true
  loadingMessage.value = `${schedule.is_locked ? 'Membuka' : 'Mengunci'} jadwal...`

  try {
    const response = await fetch(`/api/academic-schedules/${schedule.id}/toggle-lock`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
      },
      body: JSON.stringify({ reason }),
    })

    const data = await response.json()

    if (data.success) {
      await loadScheduleGrid()
      showDetailModal.value = false
    } else {
      alert(data.message || 'Gagal mengubah status kunci')
    }
  } catch (error) {
    console.error('Error toggling lock:', error)
    alert('Terjadi kesalahan saat mengubah status kunci')
  } finally {
    loading.value = false
  }

  closeContextMenu()
}

const exportSchedule = async () => {
  if (!selectedClassId.value) {return}

  try {
    const response = await fetch(`/api/academic-schedules/export/${selectedClassId.value}`)
    const data = await response.json()

    // Create download
    const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' })
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `schedule_${selectedClassId.value}_${new Date().toISOString().split('T')[0]}.json`
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
    URL.revokeObjectURL(url)
  } catch (error) {
    console.error('Error exporting schedule:', error)
    alert('Gagal mengexport jadwal')
  }
}

const toggleConflictsView = () => {
  showConflicts.value = !showConflicts.value
}

const resolveConflict = async (conflict) => {
  const notes = prompt('Masukkan catatan penyelesaian konflik:')
  if (!notes) {return}

  try {
    const response = await fetch(`/api/academic-schedules/conflicts/${conflict.id}/resolve`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
      },
      body: JSON.stringify({ resolution_notes: notes }),
    })

    const data = await response.json()

    if (data.success) {
      await loadConflicts()
    } else {
      alert(data.message || 'Gagal menyelesaikan konflik')
    }
  } catch (error) {
    console.error('Error resolving conflict:', error)
    alert('Terjadi kesalahan saat menyelesaikan konflik')
  }
}

const closeModal = () => {
  showAddModal.value = false
  showEditModal.value = false
  selectedSchedule.value = null
}

const onScheduleSaved = () => {
  closeModal()
  loadScheduleGrid()
}

const showContextMenu = (event, schedule, dayKey, timeSlotId) => {
  contextMenu.x = event.clientX
  contextMenu.y = event.clientY
  contextMenu.schedule = schedule
  showContextMenuFlag.value = true
}

const closeContextMenu = () => {
  showContextMenuFlag.value = false
  contextMenu.schedule = null
}

// Drag and Drop
const onDragStart = (event, schedule) => {
  draggedSchedule.value = schedule
  event.dataTransfer.effectAllowed = 'move'
}

const onDrop = async (event, dayOfWeek, timeSlotId) => {
  event.preventDefault()

  if (!draggedSchedule.value) {return}

  // Check if dropping on same cell
  if (
    draggedSchedule.value.day_of_week === dayOfWeek &&
    draggedSchedule.value.time_slot_id === timeSlotId
  ) {
    draggedSchedule.value = null
    return
  }

  // Check if target cell is occupied
  const targetSlot = gridData.value[dayOfWeek]?.slots[timeSlotId]
  if (targetSlot?.schedule) {
    // Swap schedules
    await swapSchedules(draggedSchedule.value, targetSlot.schedule)
  } else {
    // Move schedule to empty slot
    await moveSchedule(draggedSchedule.value, dayOfWeek, timeSlotId)
  }

  draggedSchedule.value = null
}

const moveSchedule = async (schedule, dayOfWeek, timeSlotId) => {
  loading.value = true
  loadingMessage.value = 'Memindahkan jadwal...'

  try {
    const response = await fetch(`/api/academic-schedules/${schedule.id}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
      },
      body: JSON.stringify({
        day_of_week: dayOfWeek,
        time_slot_id: timeSlotId,
        reason: 'Moved via drag and drop',
      }),
    })

    const data = await response.json()

    if (data.success) {
      await loadScheduleGrid()
    } else {
      alert(data.message || 'Gagal memindahkan jadwal')
    }
  } catch (error) {
    console.error('Error moving schedule:', error)
    alert('Terjadi kesalahan saat memindahkan jadwal')
  } finally {
    loading.value = false
  }
}

const swapSchedules = async (schedule1, schedule2) => {
  if (!confirm('Tukar posisi kedua jadwal?')) {return}

  loading.value = true
  loadingMessage.value = 'Menukar jadwal...'

  try {
    const response = await fetch('/api/academic-schedules/swap', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
      },
      body: JSON.stringify({
        schedule_1_id: schedule1.id,
        schedule_2_id: schedule2.id,
        reason: 'Swapped via drag and drop',
      }),
    })

    const data = await response.json()

    if (data.success) {
      await loadScheduleGrid()
    } else {
      alert(data.message || 'Gagal menukar jadwal')
    }
  } catch (error) {
    console.error('Error swapping schedules:', error)
    alert('Terjadi kesalahan saat menukar jadwal')
  } finally {
    loading.value = false
  }
}

const startSwapMode = (schedule) => {
  if (!swapMode.value) {
    swapMode.value = true
    swapSchedule1.value = schedule
    alert('Mode tukar aktif. Klik jadwal lain untuk menukar posisi.')
  } else {
    swapSchedules(swapSchedule1.value, schedule)
    swapMode.value = false
    swapSchedule1.value = null
  }
  closeContextMenu()
}

const getScheduleClass = (status, color) => {
  const baseClass = 'border'

  if (status === 'locked') {
    return `${baseClass} bg-red-100 border-red-300 text-red-800 dark:bg-red-900/30 dark:border-red-600 dark:text-red-200`
  } else if (status === 'conflict') {
    return `${baseClass} bg-yellow-100 border-yellow-300 text-yellow-800 dark:bg-yellow-900/30 dark:border-yellow-600 dark:text-yellow-200`
  } else {
    // Use subject color if available
    if (color) {
      return `${baseClass} border-gray-300 dark:border-gray-600 text-white shadow-sm hover:shadow-md`
    } else {
      return `${baseClass} bg-blue-100 border-blue-300 text-blue-800 dark:bg-blue-900/30 dark:border-blue-600 dark:text-blue-200 hover:bg-blue-200 dark:hover:bg-blue-900/50`
    }
  }
}

const formatDateTime = (dateString) => {
  return new Date(dateString).toLocaleString('id-ID')
}

// Watchers
watch(selectedClassId, (newValue) => {
  if (newValue) {
    loadScheduleGrid()
  } else {
    gridData.value = null
    conflicts.value = []
  }
})

// Lifecycle
onMounted(() => {
  // Load first class if available
  if (props.academicClasses.length > 0) {
    selectedClassId.value = props.academicClasses[0].id
  }
})

// Handle click outside context menu
const handleClickOutside = (event) => {
  if (showContextMenuFlag.value) {
    closeContextMenu()
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

<style scoped>
.schedule-grid-container {
  @apply mx-auto max-w-7xl p-6;
}

/* Drag and drop styles */
.drag-over {
  @apply border-emerald-400 bg-emerald-50 dark:bg-emerald-900/20;
}

/* Schedule cell animations */
.schedule-cell {
  transition: all 0.2s ease-in-out;
}

.schedule-cell:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

/* Custom scrollbar for horizontal scroll */
.overflow-x-auto::-webkit-scrollbar {
  height: 6px;
}

.overflow-x-auto::-webkit-scrollbar-track {
  background: #f1f1f1;
}

.overflow-x-auto::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 3px;
}

.overflow-x-auto::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}

/* Mobile responsiveness */
@media (max-width: 1024px) {
  .schedule-grid-container {
    @apply p-4;
  }
}
</style>
