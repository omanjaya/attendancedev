<template>
  <div class="face-template-management-container">
    <!-- Header -->
    <div class="mb-8">
      <h2 class="mb-2 text-2xl font-bold text-gray-900 dark:text-gray-100">
        Manajemen Template Wajah
      </h2>
      <p class="text-gray-600 dark:text-gray-300">
        Kelola template wajah karyawan dan data biometrik
      </p>
    </div>

    <!-- Statistics Cards -->
    <div class="mb-8 grid grid-cols-1 gap-6 md:grid-cols-4">
      <div
        class="rounded-xl border border-emerald-200 bg-gradient-to-br from-emerald-50 to-emerald-100 p-6 dark:border-emerald-700/50 dark:from-emerald-900/30 dark:to-emerald-800/20"
      >
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-emerald-600 dark:text-emerald-400">
              Total Template
            </p>
            <p class="text-2xl font-bold text-emerald-900 dark:text-emerald-100">
              {{ statistics.totalTemplates }}
            </p>
          </div>
          <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-500">
            <FaceSmileIcon class="h-6 w-6 text-white" />
          </div>
        </div>
      </div>

      <div
        class="rounded-xl border border-blue-200 bg-gradient-to-br from-blue-50 to-blue-100 p-6 dark:border-blue-700/50 dark:from-blue-900/30 dark:to-blue-800/20"
      >
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-blue-600 dark:text-blue-400">
              Template Aktif
            </p>
            <p class="text-2xl font-bold text-blue-900 dark:text-blue-100">
              {{ statistics.activeTemplates }}
            </p>
          </div>
          <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-500">
            <CheckCircleIcon class="h-6 w-6 text-white" />
          </div>
        </div>
      </div>

      <div
        class="rounded-xl border border-amber-200 bg-gradient-to-br from-amber-50 to-amber-100 p-6 dark:border-amber-700/50 dark:from-amber-900/30 dark:to-amber-800/20"
      >
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-amber-600 dark:text-amber-400">
              Kualitas Rata-rata
            </p>
            <p class="text-2xl font-bold text-amber-900 dark:text-amber-100">
              {{ statistics.averageQuality }}%
            </p>
          </div>
          <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-500">
            <ChartBarIcon class="h-6 w-6 text-white" />
          </div>
        </div>
      </div>

      <div
        class="rounded-xl border border-purple-200 bg-gradient-to-br from-purple-50 to-purple-100 p-6 dark:border-purple-700/50 dark:from-purple-900/30 dark:to-purple-800/20"
      >
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-purple-600 dark:text-purple-400">
              Perlu Update
            </p>
            <p class="text-2xl font-bold text-purple-900 dark:text-purple-100">
              {{ statistics.needsUpdate }}
            </p>
          </div>
          <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-purple-500">
            <ExclamationTriangleIcon class="h-6 w-6 text-white" />
          </div>
        </div>
      </div>
    </div>

    <!-- Search and Filters -->
    <div
      class="mb-6 rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800"
    >
      <div
        class="flex flex-col space-y-4 md:flex-row md:items-center md:justify-between md:space-y-0"
      >
        <div class="flex flex-col space-y-4 md:flex-row md:space-x-4 md:space-y-0">
          <!-- Search -->
          <div class="relative">
            <MagnifyingGlassIcon
              class="absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 transform text-gray-400"
            />
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Cari karyawan atau ID..."
              class="w-full rounded-lg border border-gray-300 bg-white py-2 pl-10 pr-4 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-700 md:w-64"
            >
          </div>

          <!-- Status Filter -->
          <select
            v-model="statusFilter"
            class="rounded-lg border border-gray-300 bg-white px-4 py-2 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-700"
          >
            <option value="">
              Semua Status
            </option>
            <option value="active">
              Aktif
            </option>
            <option value="inactive">
              Tidak Aktif
            </option>
            <option value="needs_update">
              Perlu Update
            </option>
          </select>

          <!-- Quality Filter -->
          <select
            v-model="qualityFilter"
            class="rounded-lg border border-gray-300 bg-white px-4 py-2 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-700"
          >
            <option value="">
              Semua Kualitas
            </option>
            <option value="high">
              Tinggi (>90%)
            </option>
            <option value="medium">
              Sedang (70-90%)
            </option>
            <option value="low">
              Rendah (<70%)
            </option>
          </select>
        </div>

        <div class="flex space-x-3">
          <button
            :disabled="selectedTemplates.length === 0 || bulkUpdating"
            class="inline-flex items-center rounded-lg bg-emerald-50 px-4 py-2 text-sm font-medium text-emerald-600 transition-colors hover:bg-emerald-100 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-emerald-900/30 dark:text-emerald-400 dark:hover:bg-emerald-900/50"
            @click="bulkUpdateTemplates"
          >
            <ArrowPathIcon class="mr-2 h-4 w-4" />
            {{ bulkUpdating ? 'Updating...' : 'Update Terpilih' }}
          </button>

          <button
            class="inline-flex items-center rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
            @click="exportTemplates"
          >
            <ArrowDownTrayIcon class="mr-2 h-4 w-4" />
            Export
          </button>
        </div>
      </div>
    </div>

    <!-- Templates Table -->
    <div
      class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800"
    >
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead>
            <tr class="bg-gray-50 dark:bg-gray-700">
              <th class="px-6 py-3 text-left">
                <input
                  v-model="selectAll"
                  type="checkbox"
                  class="h-4 w-4 rounded border-gray-300 bg-gray-100 text-emerald-600 focus:ring-emerald-500"
                  @change="toggleSelectAll"
                >
              </th>
              <th
                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400"
              >
                Karyawan
              </th>
              <th
                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400"
              >
                Template
              </th>
              <th
                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400"
              >
                Kualitas
              </th>
              <th
                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400"
              >
                Status
              </th>
              <th
                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400"
              >
                Terakhir Update
              </th>
              <th
                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400"
              >
                Aksi
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
            <tr
              v-for="template in filteredTemplates"
              :key="template.id"
              class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-700"
            >
              <td class="px-6 py-4">
                <input
                  v-model="selectedTemplates"
                  type="checkbox"
                  :value="template.id"
                  class="h-4 w-4 rounded border-gray-300 bg-gray-100 text-emerald-600 focus:ring-emerald-500"
                >
              </td>

              <td class="px-6 py-4">
                <div class="flex items-center space-x-3">
                  <div class="flex-shrink-0">
                    <div
                      class="flex h-10 w-10 items-center justify-center overflow-hidden rounded-full bg-gray-300 dark:bg-gray-600"
                    >
                      <img
                        v-if="template.avatar"
                        :src="template.avatar"
                        :alt="template.employee_name"
                        class="h-full w-full object-cover"
                      >
                      <span v-else class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        {{ template.employee_name.charAt(0) }}
                      </span>
                    </div>
                  </div>
                  <div>
                    <div class="font-medium text-gray-900 dark:text-gray-100">
                      {{ template.employee_name }}
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                      {{ template.employee_id }}
                    </div>
                  </div>
                </div>
              </td>

              <td class="px-6 py-4">
                <div class="flex items-center space-x-3">
                  <div
                    class="flex h-16 w-16 items-center justify-center overflow-hidden rounded-lg bg-gray-100 dark:bg-gray-700"
                  >
                    <img
                      v-if="template.face_sample"
                      :src="template.face_sample"
                      :alt="template.employee_name"
                      class="h-full w-full object-cover"
                    >
                    <FaceSmileIcon v-else class="h-8 w-8 text-gray-400" />
                  </div>
                  <div>
                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                      {{ template.template_count }} template
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                      {{ template.algorithm }}
                    </div>
                  </div>
                </div>
              </td>

              <td class="px-6 py-4">
                <div class="flex items-center space-x-2">
                  <div class="h-2 w-16 rounded-full bg-gray-200 dark:bg-gray-700">
                    <div
                      :class="[
                        'h-2 rounded-full transition-all duration-300',
                        template.quality >= 90
                          ? 'bg-emerald-500'
                          : template.quality >= 70
                            ? 'bg-amber-500'
                            : 'bg-red-500',
                      ]"
                      :style="{ width: `${template.quality}%` }"
                    />
                  </div>
                  <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                    {{ template.quality }}%
                  </span>
                </div>
              </td>

              <td class="px-6 py-4">
                <span
                  :class="[
                    'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                    template.status === 'active'
                      ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-800 dark:text-emerald-200'
                      : template.status === 'inactive'
                        ? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200'
                        : 'bg-amber-100 text-amber-800 dark:bg-amber-800 dark:text-amber-200',
                  ]"
                >
                  {{
                    template.status === 'active'
                      ? 'Aktif'
                      : template.status === 'inactive'
                        ? 'Tidak Aktif'
                        : 'Perlu Update'
                  }}
                </span>
              </td>

              <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                <div>{{ formatDate(template.last_updated) }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">
                  {{ template.updated_by }}
                </div>
              </td>

              <td class="px-6 py-4">
                <div class="flex items-center space-x-2">
                  <button
                    class="text-gray-600 transition-colors hover:text-emerald-600 dark:text-gray-400 dark:hover:text-emerald-400"
                    title="Lihat Detail"
                    @click="viewTemplate(template)"
                  >
                    <EyeIcon class="h-4 w-4" />
                  </button>
                  <button
                    class="text-gray-600 transition-colors hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-400"
                    title="Update Template"
                    @click="updateTemplate(template)"
                  >
                    <ArrowPathIcon class="h-4 w-4" />
                  </button>
                  <button
                    class="text-gray-600 transition-colors hover:text-red-600 dark:text-gray-400 dark:hover:text-red-400"
                    title="Hapus Template"
                    @click="deleteTemplate(template)"
                  >
                    <TrashIcon class="h-4 w-4" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Pagination -->
    <div class="mt-6 flex items-center justify-between">
      <div class="text-sm text-gray-700 dark:text-gray-300">
        Menampilkan {{ startIndex + 1 }} hingga
        {{ Math.min(endIndex, filteredTemplates.length) }} dari
        {{ filteredTemplates.length }} template
      </div>

      <div class="flex items-center space-x-2">
        <button
          :disabled="currentPage === 1"
          class="rounded-lg border border-gray-300 bg-white px-3 py-1 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
          @click="previousPage"
        >
          Sebelumnya
        </button>

        <span class="text-sm text-gray-700 dark:text-gray-300">
          {{ currentPage }} dari {{ totalPages }}
        </span>

        <button
          :disabled="currentPage === totalPages"
          class="rounded-lg border border-gray-300 bg-white px-3 py-1 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
          @click="nextPage"
        >
          Selanjutnya
        </button>
      </div>
    </div>

    <!-- Template Detail Modal -->
    <div
      v-if="selectedTemplate"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
      @click="selectedTemplate = null"
    >
      <div
        class="mx-4 max-h-[80vh] w-full max-w-2xl overflow-y-auto rounded-xl bg-white p-6 dark:bg-gray-800"
        @click.stop
      >
        <div class="mb-4 flex items-center justify-between">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
            Detail Template - {{ selectedTemplate.employee_name }}
          </h3>
          <button
            class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300"
            @click="selectedTemplate = null"
          >
            <XMarkIcon class="h-6 w-6" />
          </button>
        </div>

        <div class="space-y-6">
          <!-- Face Samples -->
          <div>
            <h4 class="mb-3 font-medium text-gray-900 dark:text-gray-100">
              Sample Wajah ({{ selectedTemplate.face_samples?.length || 0 }})
            </h4>
            <div class="grid grid-cols-3 gap-3">
              <div
                v-for="(sample, index) in selectedTemplate.face_samples"
                :key="index"
                class="aspect-square overflow-hidden rounded-lg bg-gray-100 dark:bg-gray-700"
              >
                <img
                  :src="sample.url"
                  :alt="`Sample ${index + 1}`"
                  class="h-full w-full object-cover"
                >
              </div>
            </div>
          </div>

          <!-- Template Information -->
          <div>
            <h4 class="mb-3 font-medium text-gray-900 dark:text-gray-100">
              Informasi Template
            </h4>
            <div class="space-y-3 rounded-lg bg-gray-50 p-4 dark:bg-gray-700">
              <div class="flex justify-between">
                <span class="text-sm text-gray-600 dark:text-gray-400">Algoritma:</span>
                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                  {{ selectedTemplate.algorithm }}
                </span>
              </div>
              <div class="flex justify-between">
                <span class="text-sm text-gray-600 dark:text-gray-400">Kualitas:</span>
                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                  {{ selectedTemplate.quality }}%
                </span>
              </div>
              <div class="flex justify-between">
                <span class="text-sm text-gray-600 dark:text-gray-400">Confidence:</span>
                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                  {{ selectedTemplate.confidence }}%
                </span>
              </div>
              <div class="flex justify-between">
                <span class="text-sm text-gray-600 dark:text-gray-400">Liveness Score:</span>
                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                  {{ selectedTemplate.liveness_score }}%
                </span>
              </div>
              <div class="flex justify-between">
                <span class="text-sm text-gray-600 dark:text-gray-400">Ukuran Template:</span>
                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                  {{ selectedTemplate.template_size }} KB
                </span>
              </div>
            </div>
          </div>

          <!-- Usage Statistics -->
          <div>
            <h4 class="mb-3 font-medium text-gray-900 dark:text-gray-100">
              Statistik Penggunaan
            </h4>
            <div class="grid grid-cols-2 gap-4">
              <div class="rounded-lg bg-emerald-50 p-3 dark:bg-emerald-900/30">
                <div class="text-lg font-bold text-emerald-600 dark:text-emerald-400">
                  {{ selectedTemplate.usage_stats?.successful_detections || 0 }}
                </div>
                <div class="text-sm text-emerald-600 dark:text-emerald-400">
                  Deteksi Berhasil
                </div>
              </div>
              <div class="rounded-lg bg-red-50 p-3 dark:bg-red-900/30">
                <div class="text-lg font-bold text-red-600 dark:text-red-400">
                  {{ selectedTemplate.usage_stats?.failed_detections || 0 }}
                </div>
                <div class="text-sm text-red-600 dark:text-red-400">
                  Deteksi Gagal
                </div>
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div
            class="flex justify-end space-x-3 border-t border-gray-200 pt-4 dark:border-gray-600"
          >
            <button
              class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-emerald-700"
              @click="updateTemplate(selectedTemplate)"
            >
              Update Template
            </button>
            <button
              class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
              @click="selectedTemplate = null"
            >
              Tutup
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import {
  FaceSmileIcon,
  CheckCircleIcon,
  ChartBarIcon,
  ExclamationTriangleIcon,
  MagnifyingGlassIcon,
  ArrowPathIcon,
  ArrowDownTrayIcon,
  EyeIcon,
  TrashIcon,
  XMarkIcon,
} from '@heroicons/vue/24/outline'

// Reactive state
const searchQuery = ref('')
const statusFilter = ref('')
const qualityFilter = ref('')
const selectedTemplates = ref([])
const selectAll = ref(false)
const bulkUpdating = ref(false)
const selectedTemplate = ref(null)
const currentPage = ref(1)
const itemsPerPage = 10

const statistics = reactive({
  totalTemplates: 245,
  activeTemplates: 231,
  averageQuality: 87,
  needsUpdate: 14,
})

const templates = ref([
  {
    id: 1,
    employee_name: 'John Doe',
    employee_id: 'EMP001',
    avatar: null,
    face_sample: null,
    template_count: 3,
    algorithm: 'Face-API.js',
    quality: 94,
    confidence: 92,
    liveness_score: 89,
    template_size: 15.2,
    status: 'active',
    last_updated: new Date('2024-01-15'),
    updated_by: 'Admin',
    face_samples: [
      { url: '/api/face-samples/1-1.jpg' },
      { url: '/api/face-samples/1-2.jpg' },
      { url: '/api/face-samples/1-3.jpg' },
    ],
    usage_stats: {
      successful_detections: 156,
      failed_detections: 8,
    },
  },
  {
    id: 2,
    employee_name: 'Jane Smith',
    employee_id: 'EMP002',
    avatar: null,
    face_sample: null,
    template_count: 2,
    algorithm: 'MediaPipe',
    quality: 78,
    confidence: 85,
    liveness_score: 92,
    template_size: 12.8,
    status: 'needs_update',
    last_updated: new Date('2024-01-10'),
    updated_by: 'HR Manager',
    face_samples: [{ url: '/api/face-samples/2-1.jpg' }, { url: '/api/face-samples/2-2.jpg' }],
    usage_stats: {
      successful_detections: 89,
      failed_detections: 23,
    },
  },
  {
    id: 3,
    employee_name: 'Mike Johnson',
    employee_id: 'EMP003',
    avatar: null,
    face_sample: null,
    template_count: 4,
    algorithm: 'Face-API.js',
    quality: 96,
    confidence: 98,
    liveness_score: 95,
    template_size: 18.5,
    status: 'active',
    last_updated: new Date('2024-01-18'),
    updated_by: 'Admin',
    face_samples: [
      { url: '/api/face-samples/3-1.jpg' },
      { url: '/api/face-samples/3-2.jpg' },
      { url: '/api/face-samples/3-3.jpg' },
      { url: '/api/face-samples/3-4.jpg' },
    ],
    usage_stats: {
      successful_detections: 234,
      failed_detections: 3,
    },
  },
  {
    id: 4,
    employee_name: 'Sarah Wilson',
    employee_id: 'EMP004',
    avatar: null,
    face_sample: null,
    template_count: 1,
    algorithm: 'MediaPipe',
    quality: 68,
    confidence: 72,
    liveness_score: 78,
    template_size: 9.3,
    status: 'needs_update',
    last_updated: new Date('2024-01-05'),
    updated_by: 'HR Manager',
    face_samples: [{ url: '/api/face-samples/4-1.jpg' }],
    usage_stats: {
      successful_detections: 45,
      failed_detections: 67,
    },
  },
])

// Computed
const filteredTemplates = computed(() => {
  let filtered = templates.value

  // Search filter
  if (searchQuery.value) {
    filtered = filtered.filter(
      (template) =>
        template.employee_name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
        template.employee_id.toLowerCase().includes(searchQuery.value.toLowerCase())
    )
  }

  // Status filter
  if (statusFilter.value) {
    filtered = filtered.filter((template) => template.status === statusFilter.value)
  }

  // Quality filter
  if (qualityFilter.value) {
    filtered = filtered.filter((template) => {
      if (qualityFilter.value === 'high') {return template.quality > 90}
      if (qualityFilter.value === 'medium') {return template.quality >= 70 && template.quality <= 90}
      if (qualityFilter.value === 'low') {return template.quality < 70}
      return true
    })
  }

  return filtered
})

const totalPages = computed(() => {
  return Math.ceil(filteredTemplates.value.length / itemsPerPage)
})

const startIndex = computed(() => {
  return (currentPage.value - 1) * itemsPerPage
})

const endIndex = computed(() => {
  return startIndex.value + itemsPerPage
})

const paginatedTemplates = computed(() => {
  return filteredTemplates.value.slice(startIndex.value, endIndex.value)
})

// Methods
const formatDate = (date) => {
  return new Intl.DateTimeFormat('id-ID', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  }).format(date)
}

const toggleSelectAll = () => {
  if (selectAll.value) {
    selectedTemplates.value = paginatedTemplates.value.map((t) => t.id)
  } else {
    selectedTemplates.value = []
  }
}

const viewTemplate = (template) => {
  selectedTemplate.value = template
}

const updateTemplate = async (template) => {
  try {
    // Simulate API call
    await new Promise((resolve) => setTimeout(resolve, 1000))

    // Update template quality and last updated
    template.quality = Math.min(100, template.quality + Math.floor(Math.random() * 10))
    template.last_updated = new Date()
    template.updated_by = 'System'

    if (template.status === 'needs_update') {
      template.status = 'active'
    }

    console.log('Template updated:', template)
  } catch (error) {
    console.error('Failed to update template:', error)
  }
}

const deleteTemplate = async (template) => {
  if (confirm(`Apakah Anda yakin ingin menghapus template ${template.employee_name}?`)) {
    try {
      // Simulate API call
      await new Promise((resolve) => setTimeout(resolve, 500))

      // Remove from templates array
      const index = templates.value.findIndex((t) => t.id === template.id)
      if (index > -1) {
        templates.value.splice(index, 1)
      }

      // Update statistics
      statistics.totalTemplates--
      if (template.status === 'active') {
        statistics.activeTemplates--
      } else if (template.status === 'needs_update') {
        statistics.needsUpdate--
      }

      console.log('Template deleted:', template)
    } catch (error) {
      console.error('Failed to delete template:', error)
    }
  }
}

const bulkUpdateTemplates = async () => {
  if (selectedTemplates.value.length === 0) {return}

  bulkUpdating.value = true

  try {
    // Simulate API calls
    for (const templateId of selectedTemplates.value) {
      const template = templates.value.find((t) => t.id === templateId)
      if (template) {
        await updateTemplate(template)
      }
    }

    selectedTemplates.value = []
    selectAll.value = false
  } catch (error) {
    console.error('Failed to bulk update templates:', error)
  } finally {
    bulkUpdating.value = false
  }
}

const exportTemplates = () => {
  // Simulate export
  const data = JSON.stringify(filteredTemplates.value, null, 2)
  const blob = new Blob([data], { type: 'application/json' })
  const url = URL.createObjectURL(blob)

  const a = document.createElement('a')
  a.href = url
  a.download = `face_templates_${new Date().toISOString().split('T')[0]}.json`
  document.body.appendChild(a)
  a.click()
  document.body.removeChild(a)
  URL.revokeObjectURL(url)
}

const previousPage = () => {
  if (currentPage.value > 1) {
    currentPage.value--
  }
}

const nextPage = () => {
  if (currentPage.value < totalPages.value) {
    currentPage.value++
  }
}

const loadTemplates = async () => {
  try {
    // Simulate loading from API
    await new Promise((resolve) => setTimeout(resolve, 1000))

    console.log('Templates loaded')
  } catch (error) {
    console.error('Failed to load templates:', error)
  }
}

// Lifecycle
onMounted(() => {
  loadTemplates()
})
</script>

<style scoped>
.face-template-management-container {
  @apply mx-auto max-w-7xl p-6;
}

/* Custom scrollbar for modal */
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

/* Transition animations */
.transition-colors {
  transition:
    color 0.15s ease-in-out,
    background-color 0.15s ease-in-out;
}

/* Progress bar animations */
.transition-all {
  transition: all 0.3s ease-in-out;
}
</style>
