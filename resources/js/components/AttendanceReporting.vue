<template>
  <div class="attendance-reporting-container">
    <!-- Header -->
    <div class="mb-8">
      <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-2">
        Laporan Absensi
      </h2>
      <p class="text-gray-600 dark:text-gray-300">
        Buat dan kelola laporan absensi dengan berbagai format dan filter
      </p>
    </div>

    <!-- Report Configuration -->
    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700 mb-6">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
        Konfigurasi Laporan
      </h3>
      
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Date Range -->
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            Rentang Tanggal
          </label>
          <div class="space-y-2">
            <input
              v-model="reportConfig.startDate"
              type="date"
              class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
            >
            <input
              v-model="reportConfig.endDate"
              type="date"
              class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
            >
          </div>
        </div>

        <!-- Department Filter -->
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            Departemen
          </label>
          <select
            v-model="reportConfig.department"
            class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
          >
            <option value="">Semua Departemen</option>
            <option v-for="dept in departments" :key="dept" :value="dept">
              {{ dept }}
            </option>
          </select>
        </div>

        <!-- Employee Filter -->
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            Karyawan
          </label>
          <select
            v-model="reportConfig.employee"
            class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
          >
            <option value="">Semua Karyawan</option>
            <option v-for="emp in employees" :key="emp.id" :value="emp.id">
              {{ emp.name }}
            </option>
          </select>
        </div>
      </div>

      <!-- Report Type -->
      <div class="mt-6">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
          Jenis Laporan
        </label>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
          <div
            v-for="type in reportTypes"
            :key="type.value"
            @click="reportConfig.type = type.value"
            :class="[
              'p-4 border-2 rounded-lg cursor-pointer transition-colors',
              reportConfig.type === type.value
                ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/30'
                : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500'
            ]"
          >
            <div class="flex items-center space-x-3">
              <div :class="[
                'w-10 h-10 rounded-lg flex items-center justify-center',
                reportConfig.type === type.value
                  ? 'bg-emerald-500 text-white'
                  : 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400'
              ]">
                <component :is="type.icon" class="w-5 h-5" />
              </div>
              <div>
                <div class="font-medium text-gray-900 dark:text-gray-100">
                  {{ type.label }}
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                  {{ type.description }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Export Options -->
      <div class="mt-6">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
          Format Export
        </label>
        <div class="flex flex-wrap gap-2">
          <button
            v-for="format in exportFormats"
            :key="format.value"
            @click="reportConfig.format = format.value"
            :class="[
              'px-4 py-2 rounded-lg text-sm font-medium transition-colors',
              reportConfig.format === format.value
                ? 'bg-emerald-500 text-white'
                : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
            ]"
          >
            {{ format.label }}
          </button>
        </div>
      </div>

      <!-- Generate Button -->
      <div class="mt-6 flex justify-end">
        <button
          @click="generateReport"
          :disabled="generating"
          class="inline-flex items-center px-6 py-3 text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <DocumentArrowDownIcon class="w-5 h-5 mr-2" />
          {{ generating ? 'Generating...' : 'Generate Laporan' }}
        </button>
      </div>
    </div>

    <!-- Report Preview -->
    <div v-if="reportData" class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700 mb-6">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
          Preview Laporan
        </h3>
        <div class="flex items-center space-x-2">
          <button
            @click="downloadReport"
            class="inline-flex items-center px-4 py-2 text-sm font-medium text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/30 hover:bg-emerald-100 dark:hover:bg-emerald-900/50 rounded-lg transition-colors"
          >
            <ArrowDownTrayIcon class="w-4 h-4 mr-1" />
            Download
          </button>
          <button
            @click="printReport"
            class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 rounded-lg transition-colors"
          >
            <PrinterIcon class="w-4 h-4 mr-1" />
            Print
          </button>
        </div>
      </div>

      <!-- Report Summary -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-emerald-50 dark:bg-emerald-900/30 rounded-lg p-4">
          <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">
            {{ reportData.summary.totalEmployees }}
          </div>
          <div class="text-sm text-emerald-600 dark:text-emerald-400">
            Total Karyawan
          </div>
        </div>
        <div class="bg-blue-50 dark:bg-blue-900/30 rounded-lg p-4">
          <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
            {{ reportData.summary.totalDays }}
          </div>
          <div class="text-sm text-blue-600 dark:text-blue-400">
            Total Hari
          </div>
        </div>
        <div class="bg-amber-50 dark:bg-amber-900/30 rounded-lg p-4">
          <div class="text-2xl font-bold text-amber-600 dark:text-amber-400">
            {{ reportData.summary.averageAttendance }}%
          </div>
          <div class="text-sm text-amber-600 dark:text-amber-400">
            Rata-rata Kehadiran
          </div>
        </div>
        <div class="bg-purple-50 dark:bg-purple-900/30 rounded-lg p-4">
          <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">
            {{ reportData.summary.totalHours }}
          </div>
          <div class="text-sm text-purple-600 dark:text-purple-400">
            Total Jam Kerja
          </div>
        </div>
      </div>

      <!-- Report Table -->
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead>
            <tr class="bg-gray-50 dark:bg-gray-700">
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Karyawan
              </th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Departemen
              </th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Hadir
              </th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Terlambat
              </th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Pulang Awal
              </th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Total Jam
              </th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Kehadiran
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
            <tr v-for="record in reportData.records" :key="record.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
              <td class="px-4 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">
                {{ record.employee }}
              </td>
              <td class="px-4 py-4 text-sm text-gray-600 dark:text-gray-300">
                {{ record.department }}
              </td>
              <td class="px-4 py-4 text-sm text-gray-600 dark:text-gray-300">
                {{ record.present }}
              </td>
              <td class="px-4 py-4 text-sm text-gray-600 dark:text-gray-300">
                {{ record.late }}
              </td>
              <td class="px-4 py-4 text-sm text-gray-600 dark:text-gray-300">
                {{ record.earlyLeave }}
              </td>
              <td class="px-4 py-4 text-sm text-gray-600 dark:text-gray-300">
                {{ record.totalHours }}
              </td>
              <td class="px-4 py-4 text-sm">
                <div class="flex items-center">
                  <div class="w-16 bg-gray-200 dark:bg-gray-700 rounded-full h-2 mr-2">
                    <div 
                      :class="[
                        'h-2 rounded-full',
                        record.attendanceRate >= 90 ? 'bg-emerald-500' :
                        record.attendanceRate >= 70 ? 'bg-amber-500' : 'bg-red-500'
                      ]"
                      :style="{ width: `${record.attendanceRate}%` }"
                    ></div>
                  </div>
                  <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                    {{ record.attendanceRate }}%
                  </span>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Saved Reports -->
    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
          Laporan Tersimpan
        </h3>
        <button
          @click="refreshSavedReports"
          :disabled="loadingSavedReports"
          class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 rounded-lg transition-colors disabled:opacity-50"
        >
          <ArrowPathIcon :class="['w-4 h-4 mr-1', { 'animate-spin': loadingSavedReports }]" />
          Refresh
        </button>
      </div>

      <div class="space-y-3">
        <div v-for="saved in savedReports" :key="saved.id" class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
          <div class="flex items-center space-x-4">
            <div class="w-10 h-10 bg-emerald-100 dark:bg-emerald-800 rounded-lg flex items-center justify-center">
              <DocumentTextIcon class="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
            </div>
            <div>
              <div class="font-medium text-gray-900 dark:text-gray-100">
                {{ saved.name }}
              </div>
              <div class="text-sm text-gray-500 dark:text-gray-400">
                {{ saved.description }} • {{ formatDate(saved.createdAt) }}
              </div>
            </div>
          </div>
          <div class="flex items-center space-x-2">
            <button
              @click="downloadSavedReport(saved)"
              class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/30 hover:bg-emerald-100 dark:hover:bg-emerald-900/50 rounded-lg transition-colors"
            >
              <ArrowDownTrayIcon class="w-4 h-4 mr-1" />
              Download
            </button>
            <button
              @click="deleteSavedReport(saved.id)"
              class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/30 hover:bg-red-100 dark:hover:bg-red-900/50 rounded-lg transition-colors"
            >
              <TrashIcon class="w-4 h-4 mr-1" />
              Hapus
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { 
  DocumentArrowDownIcon, 
  ArrowDownTrayIcon, 
  PrinterIcon, 
  DocumentTextIcon,
  ArrowPathIcon,
  TrashIcon,
  ChartBarIcon,
  CalendarDaysIcon,
  ClockIcon,
  UserGroupIcon
} from '@heroicons/vue/24/outline'

// Reactive state
const generating = ref(false)
const loadingSavedReports = ref(false)
const reportData = ref(null)

const reportConfig = reactive({
  startDate: new Date().toISOString().split('T')[0],
  endDate: new Date().toISOString().split('T')[0],
  department: '',
  employee: '',
  type: 'attendance',
  format: 'xlsx'
})

const departments = ref([
  'IT', 'HR', 'Finance', 'Marketing', 'Operations', 'Sales'
])

const employees = ref([
  { id: 1, name: 'John Doe' },
  { id: 2, name: 'Jane Smith' },
  { id: 3, name: 'Mike Johnson' },
  { id: 4, name: 'Sarah Wilson' }
])

const reportTypes = ref([
  {
    value: 'attendance',
    label: 'Laporan Kehadiran',
    description: 'Ringkasan kehadiran karyawan',
    icon: UserGroupIcon
  },
  {
    value: 'detailed',
    label: 'Laporan Detail',
    description: 'Data lengkap per karyawan',
    icon: DocumentTextIcon
  },
  {
    value: 'summary',
    label: 'Ringkasan Bulanan',
    description: 'Statistik per bulan',
    icon: ChartBarIcon
  },
  {
    value: 'overtime',
    label: 'Laporan Lembur',
    description: 'Jam kerja tambahan',
    icon: ClockIcon
  }
])

const exportFormats = ref([
  { value: 'xlsx', label: 'Excel (.xlsx)' },
  { value: 'pdf', label: 'PDF (.pdf)' },
  { value: 'csv', label: 'CSV (.csv)' },
  { value: 'json', label: 'JSON (.json)' }
])

const savedReports = ref([
  {
    id: 1,
    name: 'Laporan Kehadiran Oktober 2024',
    description: 'Semua departemen • Excel',
    createdAt: new Date('2024-10-31'),
    format: 'xlsx',
    size: '2.4 MB'
  },
  {
    id: 2,
    name: 'Laporan Detail IT Department',
    description: 'Departemen IT • PDF',
    createdAt: new Date('2024-10-28'),
    format: 'pdf',
    size: '1.8 MB'
  },
  {
    id: 3,
    name: 'Ringkasan Bulanan September',
    description: 'Semua karyawan • CSV',
    createdAt: new Date('2024-09-30'),
    format: 'csv',
    size: '856 KB'
  }
])

// Methods
const generateReport = async () => {
  generating.value = true
  
  try {
    // Simulate API call
    await new Promise(resolve => setTimeout(resolve, 2000))
    
    // Generate sample report data
    reportData.value = {
      summary: {
        totalEmployees: 45,
        totalDays: getDaysBetween(reportConfig.startDate, reportConfig.endDate),
        averageAttendance: 87.5,
        totalHours: 3240
      },
      records: generateSampleRecords()
    }
    
  } catch (error) {
    console.error('Failed to generate report:', error)
  } finally {
    generating.value = false
  }
}

const generateSampleRecords = () => {
  const records = []
  const sampleEmployees = [
    { name: 'John Doe', department: 'IT' },
    { name: 'Jane Smith', department: 'HR' },
    { name: 'Mike Johnson', department: 'Finance' },
    { name: 'Sarah Wilson', department: 'Marketing' },
    { name: 'David Brown', department: 'Operations' }
  ]
  
  sampleEmployees.forEach((emp, index) => {
    const present = Math.floor(Math.random() * 5) + 15
    const late = Math.floor(Math.random() * 3)
    const earlyLeave = Math.floor(Math.random() * 2)
    const totalHours = present * 8 + Math.floor(Math.random() * 20)
    const attendanceRate = Math.floor((present / 20) * 100)
    
    records.push({
      id: index + 1,
      employee: emp.name,
      department: emp.department,
      present,
      late,
      earlyLeave,
      totalHours,
      attendanceRate
    })
  })
  
  return records
}

const getDaysBetween = (startDate, endDate) => {
  const start = new Date(startDate)
  const end = new Date(endDate)
  const timeDiff = end.getTime() - start.getTime()
  return Math.ceil(timeDiff / (1000 * 3600 * 24)) + 1
}

const downloadReport = () => {
  // Simulate download
  const filename = `attendance_report_${reportConfig.startDate}_${reportConfig.endDate}.${reportConfig.format}`
  console.log('Downloading report:', filename)
  
  // In production, trigger actual download
  alert(`Download started: ${filename}`)
}

const printReport = () => {
  // Simulate print
  console.log('Printing report...')
  window.print()
}

const refreshSavedReports = async () => {
  loadingSavedReports.value = true
  
  try {
    // Simulate API call
    await new Promise(resolve => setTimeout(resolve, 1000))
    
    // Add new sample report
    savedReports.value.unshift({
      id: Date.now(),
      name: 'Laporan Terbaru',
      description: 'Generated just now • Excel',
      createdAt: new Date(),
      format: 'xlsx',
      size: '1.2 MB'
    })
    
  } catch (error) {
    console.error('Failed to refresh saved reports:', error)
  } finally {
    loadingSavedReports.value = false
  }
}

const downloadSavedReport = (report) => {
  console.log('Downloading saved report:', report.name)
  alert(`Download started: ${report.name}`)
}

const deleteSavedReport = (reportId) => {
  if (confirm('Are you sure you want to delete this report?')) {
    savedReports.value = savedReports.value.filter(r => r.id !== reportId)
  }
}

const formatDate = (date) => {
  return new Intl.DateTimeFormat('id-ID', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  }).format(date)
}

// Lifecycle
onMounted(() => {
  // Set default end date to today
  reportConfig.endDate = new Date().toISOString().split('T')[0]
  
  // Set default start date to 30 days ago
  const thirtyDaysAgo = new Date()
  thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30)
  reportConfig.startDate = thirtyDaysAgo.toISOString().split('T')[0]
})
</script>

<style scoped>
.attendance-reporting-container {
  @apply max-w-7xl mx-auto p-6;
}

/* Print styles */
@media print {
  .no-print {
    display: none !important;
  }
  
  .print-only {
    display: block !important;
  }
}
</style>