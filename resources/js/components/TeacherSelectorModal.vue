<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
    <div
      class="mx-4 max-h-[80vh] w-full max-w-2xl overflow-hidden rounded-xl bg-white dark:bg-gray-800"
    >
      <!-- Header -->
      <div
        class="flex items-center justify-between border-b border-gray-200 p-6 dark:border-gray-600"
      >
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
          Pilih Guru
        </h3>
        <button
          class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300"
          @click="$emit('close')"
        >
          <XMarkIcon class="h-6 w-6" />
        </button>
      </div>

      <!-- Search -->
      <div class="border-b border-gray-200 p-6 dark:border-gray-600">
        <div class="relative">
          <MagnifyingGlassIcon
            class="absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 transform text-gray-400"
          />
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Cari nama guru atau kode..."
            class="w-full rounded-lg border border-gray-300 bg-gray-50 py-3 pl-10 pr-4 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700"
          >
        </div>
      </div>

      <!-- Teacher List -->
      <div class="max-h-96 overflow-y-auto">
        <div v-if="loading" class="p-6 text-center">
          <div class="mx-auto h-8 w-8 animate-spin rounded-full border-b-2 border-blue-600" />
          <p class="mt-2 text-gray-600 dark:text-gray-400">
            Memuat data guru...
          </p>
        </div>

        <div v-else-if="filteredTeachers.length === 0" class="p-6 text-center">
          <UserIcon class="mx-auto mb-4 h-12 w-12 text-gray-400" />
          <p class="text-gray-600 dark:text-gray-400">
            {{ searchQuery ? 'Tidak ada guru yang ditemukan' : 'Tidak ada data guru' }}
          </p>
        </div>

        <div v-else class="divide-y divide-gray-200 dark:divide-gray-600">
          <div
            v-for="teacher in filteredTeachers"
            :key="teacher.id"
            class="cursor-pointer p-4 transition-colors hover:bg-gray-50 dark:hover:bg-gray-700"
            @click="selectTeacher(teacher)"
          >
            <div class="flex items-center space-x-4">
              <!-- Teacher Code Badge -->
              <div
                class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-blue-600"
              >
                <span class="text-sm font-bold text-white">{{ teacher.teacher_code }}</span>
              </div>

              <!-- Teacher Info -->
              <div class="min-w-0 flex-1">
                <div class="flex items-center space-x-2">
                  <h4 class="truncate text-sm font-medium text-gray-900 dark:text-gray-100">
                    {{ teacher.name }}
                  </h4>
                  <span
                    :class="[
                      'rounded-full px-2 py-1 text-xs',
                      teacher.employee_type === 'permanent'
                        ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-200'
                        : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-200',
                    ]"
                  >
                    {{ teacher.employee_type === 'permanent' ? 'Tetap' : 'Honorer' }}
                  </span>
                </div>

                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                  ID: {{ teacher.employee_id }} • {{ teacher.department }}
                </p>

                <!-- Subjects -->
                <div v-if="teacher.subjects.length > 0" class="mt-2 flex flex-wrap gap-1">
                  <span
                    v-for="subject in teacher.subjects.slice(0, 3)"
                    :key="subject.id"
                    class="rounded bg-gray-100 px-2 py-1 text-xs text-gray-700 dark:bg-gray-700 dark:text-gray-300"
                  >
                    {{ subject.code }}
                  </span>
                  <span
                    v-if="teacher.subjects.length > 3"
                    class="rounded bg-gray-100 px-2 py-1 text-xs text-gray-700 dark:bg-gray-700 dark:text-gray-300"
                  >
                    +{{ teacher.subjects.length - 3 }} lainnya
                  </span>
                </div>

                <div v-else class="mt-2">
                  <span class="text-xs text-gray-500 dark:text-gray-400">Belum ada mata pelajaran</span>
                </div>
              </div>

              <!-- Selection Indicator -->
              <div class="flex-shrink-0">
                <ChevronRightIcon class="h-5 w-5 text-gray-400" />
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="border-t border-gray-200 bg-gray-50 p-6 dark:border-gray-600 dark:bg-gray-700/50">
        <div class="flex items-center justify-between text-sm text-gray-600 dark:text-gray-400">
          <span>{{ filteredTeachers.length }} guru tersedia</span>
          <button
            class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
            @click="$emit('close')"
          >
            Batal
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import {
  XMarkIcon,
  MagnifyingGlassIcon,
  UserIcon,
  ChevronRightIcon,
} from '@heroicons/vue/24/outline'

// Emits
const emit = defineEmits(['close', 'select'])

// Reactive state
const teachers = ref([])
const loading = ref(true)
const searchQuery = ref('')

// Computed
const filteredTeachers = computed(() => {
  if (!searchQuery.value) {return teachers.value}

  const query = searchQuery.value.toLowerCase()
  return teachers.value.filter(
    (teacher) =>
      teacher.name.toLowerCase().includes(query) ||
      teacher.teacher_code.toLowerCase().includes(query) ||
      teacher.employee_id.toLowerCase().includes(query) ||
      teacher.subjects.some(
        (subject) =>
          subject.name.toLowerCase().includes(query) || subject.code.toLowerCase().includes(query)
      )
  )
})

// Methods
const loadTeachers = async () => {
  try {
    loading.value = true

    // Simulate API call - replace with actual API endpoint
    const response = await fetch('/api/teachers-with-subjects')
    const data = await response.json()

    if (data.success) {
      teachers.value = data.data.map((teacher) => ({
        id: teacher.id,
        name: teacher.full_name,
        employee_id: teacher.employee_id,
        teacher_code: teacher.teacher_code || teacher.employee_id.substring(0, 3).toUpperCase(),
        employee_type: teacher.employee_type,
        department: teacher.metadata?.department || 'N/A',
        subjects: teacher.subjects || [],
      }))
    }
  } catch (error) {
    console.error('Error loading teachers:', error)
    // Fallback data for development
    teachers.value = [
      {
        id: 1,
        name: 'Ahmad Wijaya',
        employee_id: 'TCH001',
        teacher_code: 'AWJ',
        employee_type: 'permanent',
        department: 'Exact Sciences',
        subjects: [{ id: 1, code: 'MTK', name: 'Matematika' }],
      },
      {
        id: 2,
        name: 'Dewi Lestari',
        employee_id: 'TCH002',
        teacher_code: 'DLS',
        employee_type: 'permanent',
        department: 'Language',
        subjects: [{ id: 2, code: 'BIN', name: 'Bahasa Indonesia' }],
      },
      {
        id: 3,
        name: 'Andi Pratama',
        employee_id: 'TCH003',
        teacher_code: 'APR',
        employee_type: 'permanent',
        department: 'Language',
        subjects: [{ id: 3, code: 'BING', name: 'Bahasa Inggris' }],
      },
    ]
  } finally {
    loading.value = false
  }
}

const selectTeacher = (teacher) => {
  emit('select', teacher)
}

// Lifecycle
onMounted(() => {
  loadTeachers()
})
</script>

<style scoped>
/* Custom scrollbar */
.overflow-y-auto::-webkit-scrollbar {
  width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
  background: #f1f1f1;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}
</style>
