<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white dark:bg-gray-800 rounded-xl max-w-2xl w-full mx-4 max-h-[80vh] overflow-hidden">
      <!-- Header -->
      <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-600">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
          Pilih Guru
        </h3>
        <button
          @click="$emit('close')"
          class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300"
        >
          <XMarkIcon class="w-6 h-6" />
        </button>
      </div>

      <!-- Search -->
      <div class="p-6 border-b border-gray-200 dark:border-gray-600">
        <div class="relative">
          <MagnifyingGlassIcon class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" />
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Cari nama guru atau kode..."
            class="w-full pl-10 pr-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          >
        </div>
      </div>

      <!-- Teacher List -->
      <div class="overflow-y-auto max-h-96">
        <div v-if="loading" class="p-6 text-center">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
          <p class="mt-2 text-gray-600 dark:text-gray-400">Memuat data guru...</p>
        </div>

        <div v-else-if="filteredTeachers.length === 0" class="p-6 text-center">
          <UserIcon class="w-12 h-12 text-gray-400 mx-auto mb-4" />
          <p class="text-gray-600 dark:text-gray-400">
            {{ searchQuery ? 'Tidak ada guru yang ditemukan' : 'Tidak ada data guru' }}
          </p>
        </div>

        <div v-else class="divide-y divide-gray-200 dark:divide-gray-600">
          <div
            v-for="teacher in filteredTeachers"
            :key="teacher.id"
            @click="selectTeacher(teacher)"
            class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer transition-colors"
          >
            <div class="flex items-center space-x-4">
              <!-- Teacher Code Badge -->
              <div class="w-12 h-12 bg-blue-600 rounded-full flex items-center justify-center flex-shrink-0">
                <span class="text-white font-bold text-sm">{{ teacher.teacher_code }}</span>
              </div>

              <!-- Teacher Info -->
              <div class="flex-1 min-w-0">
                <div class="flex items-center space-x-2">
                  <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                    {{ teacher.name }}
                  </h4>
                  <span 
                    :class="[
                      'px-2 py-1 text-xs rounded-full',
                      teacher.employee_type === 'permanent' 
                        ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-200' 
                        : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-200'
                    ]"
                  >
                    {{ teacher.employee_type === 'permanent' ? 'Tetap' : 'Honorer' }}
                  </span>
                </div>
                
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                  ID: {{ teacher.employee_id }} • {{ teacher.department }}
                </p>
                
                <!-- Subjects -->
                <div class="flex flex-wrap gap-1 mt-2" v-if="teacher.subjects.length > 0">
                  <span
                    v-for="subject in teacher.subjects.slice(0, 3)"
                    :key="subject.id"
                    class="px-2 py-1 text-xs bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded"
                  >
                    {{ subject.code }}
                  </span>
                  <span
                    v-if="teacher.subjects.length > 3"
                    class="px-2 py-1 text-xs bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded"
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
                <ChevronRightIcon class="w-5 h-5 text-gray-400" />
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="p-6 border-t border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50">
        <div class="flex items-center justify-between text-sm text-gray-600 dark:text-gray-400">
          <span>{{ filteredTeachers.length }} guru tersedia</span>
          <button
            @click="$emit('close')"
            class="px-4 py-2 text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors"
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
  ChevronRightIcon 
} from '@heroicons/vue/24/outline'

// Emits
const emit = defineEmits(['close', 'select'])

// Reactive state
const teachers = ref([])
const loading = ref(true)
const searchQuery = ref('')

// Computed
const filteredTeachers = computed(() => {
  if (!searchQuery.value) return teachers.value
  
  const query = searchQuery.value.toLowerCase()
  return teachers.value.filter(teacher => 
    teacher.name.toLowerCase().includes(query) ||
    teacher.teacher_code.toLowerCase().includes(query) ||
    teacher.employee_id.toLowerCase().includes(query) ||
    teacher.subjects.some(subject => 
      subject.name.toLowerCase().includes(query) ||
      subject.code.toLowerCase().includes(query)
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
      teachers.value = data.data.map(teacher => ({
        id: teacher.id,
        name: teacher.full_name,
        employee_id: teacher.employee_id,
        teacher_code: teacher.teacher_code || teacher.employee_id.substring(0, 3).toUpperCase(),
        employee_type: teacher.employee_type,
        department: teacher.metadata?.department || 'N/A',
        subjects: teacher.subjects || []
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
        subjects: [
          { id: 1, code: 'MTK', name: 'Matematika' }
        ]
      },
      {
        id: 2,
        name: 'Dewi Lestari',
        employee_id: 'TCH002',
        teacher_code: 'DLS',
        employee_type: 'permanent',
        department: 'Language',
        subjects: [
          { id: 2, code: 'BIN', name: 'Bahasa Indonesia' }
        ]
      },
      {
        id: 3,
        name: 'Andi Pratama',
        employee_id: 'TCH003',
        teacher_code: 'APR',
        employee_type: 'permanent',
        department: 'Language',
        subjects: [
          { id: 3, code: 'BING', name: 'Bahasa Inggris' }
        ]
      }
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