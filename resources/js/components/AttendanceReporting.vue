<template>
  <div class="attendance-reporting-container">
    <!-- Header -->
    <div class="mb-8">
      <h2 class="mb-2 text-2xl font-bold text-gray-900 dark:text-gray-100">
        Laporan Absensi
      </h2>
      <p class="text-gray-600 dark:text-gray-300">
        Buat dan kelola laporan absensi dengan berbagai format dan filter
      </p>
    </div>

    <!-- Report Configuration -->
    <div
      class="mb-6 rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800"
    >
      <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100">
        Konfigurasi Laporan
      </h3>

      <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
        <!-- Date Range -->
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            Rentang Tanggal
          </label>
          <div class="space-y-2">
            <input
              v-model="reportConfig.startDate"
              type="date"
              class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-700"
            >
            <input
              v-model="reportConfig.endDate"
              type="date"
              class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-700"
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
            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-700"
          >
            <option value="">
              Semua Departemen
            </option>
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
            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-700"
          >
            <option value="">
              Semua Karyawan
            </option>
            <option v-for="emp in employees" :key="emp.id" :value="emp.id">
              {{ emp.name }}
            </option>
          </select>
        </div>
      </div>

      <!-- Report Type -->
      <div class="mt-6">
        <label class="mb-3 block text-sm font-medium text-gray-700 dark:text-gray-300">
          Jenis Laporan
        </label>
        <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
          <div
            v-for="type in reportTypes"
            :key="type.value"
            :class="[
              'cursor-pointer rounded-lg border-2 p-4 transition-colors',
              reportConfig.type === type.value
                ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/30'
                : 'border-gray-200 hover:border-gray-300 dark:border-gray-600 dark:hover:border-gray-500',
            ]"
            @click="reportConfig.type = type.value"
          >
            <div class="flex items-center space-x-3">
              <div
                :class="[
                  'flex h-10 w-10 items-center justify-center rounded-lg',
                  reportConfig.type === type.value
                    ? 'bg-emerald-500 text-white'
                    : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400',
                ]"
              >
                <component :is="type.icon" class="h-5 w-5" />
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
        <label class="mb-3 block text-sm font-medium text-gray-700 dark:text-gray-300">
          Format Export
        </label>
        <div class="flex flex-wrap gap-2">
          <button
            v-for="format in exportFormats"
            :key="format.value"
            :class="[
              'rounded-lg px-4 py-2 text-sm font-medium transition-colors',
              reportConfig.format === format.value
                ? 'bg-emerald-500 text-white'
                : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600',
            ]"
            @click="reportConfig.format = format.value"
          >
            {{ format.label }}
          </button>
        </div>
      </div>

      <!-- Generate Button -->
      <div class="mt-6 flex justify-end">
        <button
          :disabled="generating"
          class="inline-flex items-center rounded-lg bg-emerald-600 px-6 py-3 text-sm font-medium text-white transition-colors hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50"
          @click="generateReport"
        >
          <DocumentArrowDownIcon class="mr-2 h-5 w-5" />
          {{ generating ? 'Membuat...' : 'Buat Laporan' }}
        </button>
      </div>
    </div>

    <!-- Report Preview -->
    <div
      v-if="reportData"
      class="mb-6 rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800"
    >
      <div class="mb-4 flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
          Preview Laporan
        </h3>
        <div class="flex items-center space-x-2">
          <button
            class="inline-flex items-center rounded-lg bg-emerald-50 px-4 py-2 text-sm font-medium text-emerald-600 transition-colors hover:bg-emerald-100 dark:bg-emerald-900/30 dark:text-emerald-400 dark:hover:bg-emerald-900/50"
            @click="downloadReport"
          >
            <ArrowDownTrayIcon class="mr-1 h-4 w-4" />
            Download
          </button>
          <button
            class="inline-flex items-center rounded-lg bg-gray-50 px-4 py-2 text-sm font-medium text-gray-600 transition-colors hover:bg-gray-100 dark:bg-gray-700 dark:text-gray-400 dark:hover:bg-gray-600"
            @click="printReport"
          >
            <PrinterIcon class="mr-1 h-4 w-4" />
            Print
          </button>
        </div>
      </div>

      <!-- Report Summary -->
      <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-4">
        <div class="rounded-lg bg-emerald-50 p-4 dark:bg-emerald-900/30">
          <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">
            {{ reportData.summary.totalEmployees }}
          </div>
          <div class="text-sm text-emerald-600 dark:text-emerald-400">
            Total Karyawan
          </div>
        </div>
        <div class="rounded-lg bg-blue-50 p-4 dark:bg-blue-900/30">
          <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
            {{ reportData.summary.totalDays }}
          </div>
          <div class="text-sm text-blue-600 dark:text-blue-400">
            Total Hari
          </div>
        </div>
        <div class="rounded-lg bg-amber-50 p-4 dark:bg-amber-900/30">
          <div class="text-2xl font-bold text-amber-600 dark:text-amber-400">
            {{ reportData.summary.averageAttendance }}%
          </div>
          <div class="text-sm text-amber-600 dark:text-amber-400">
            Rata-rata Kehadiran
          </div>
        </div>
        <div class="rounded-lg bg-purple-50 p-4 dark:bg-purple-900/30">
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
              <th
                class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400"
              >
                Karyawan
              </th>
              <th
                class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400"
              >
                Departemen
              </th>
              <th
                class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400"
              >
                Hadir
              </th>
              <th
                class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400"
              >
                Terlambat
              </th>
              <th
                class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400"
              >
                Pulang Awal
              </th>
              <th
                class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400"
              >
                Total Jam
              </th>
              <th
                class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400"
              >
                Kehadiran
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
            <tr
              v-for="record in reportData.records"
              :key="record.id"
              class="hover:bg-gray-50 dark:hover:bg-gray-700"
            >
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
                  <div class="mr-2 h-2 w-16 rounded-full bg-gray-200 dark:bg-gray-700">
                    <div
                      :class="[
                        'h-2 rounded-full',
                        record.attendanceRate >= 90
                          ? 'bg-emerald-500'
                          : record.attendanceRate >= 70
                            ? 'bg-amber-500'
                            : 'bg-red-500',
                      ]"
                      :style="{ width: `${record.attendanceRate}%` }"
                    />
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
    <div
      class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800"
    >
      <div class="mb-4 flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
          Laporan Tersimpan
        </h3>
        <button
          :disabled="loadingSavedReports"
          class="inline-flex items-center rounded-lg bg-gray-50 px-3 py-1.5 text-sm font-medium text-gray-600 transition-colors hover:bg-gray-100 disabled:opacity-50 dark:bg-gray-700 dark:text-gray-400 dark:hover:bg-gray-600"
          @click="refreshSavedReports"
        >
          <ArrowPathIcon :class="['mr-1 h-4 w-4', { 'animate-spin': loadingSavedReports }]" />
          Refresh
        </button>
      </div>

      <div class="space-y-3">
        <div
          v-for="saved in savedReports"
          :key="saved.id"
          class="flex items-center justify-between rounded-lg bg-gray-50 p-4 dark:bg-gray-700"
        >
          <div class="flex items-center space-x-4">
            <div
              class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-100 dark:bg-emerald-800"
            >
              <DocumentTextIcon class="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
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
              class="inline-flex items-center rounded-lg bg-emerald-50 px-3 py-1.5 text-sm font-medium text-emerald-600 transition-colors hover:bg-emerald-100 dark:bg-emerald-900/30 dark:text-emerald-400 dark:hover:bg-emerald-900/50"
              @click="downloadSavedReport(saved)"
            >
              <ArrowDownTrayIcon class="mr-1 h-4 w-4" />
              Download
            </button>
            <button
              class="inline-flex items-center rounded-lg bg-red-50 px-3 py-1.5 text-sm font-medium text-red-600 transition-colors hover:bg-red-100 dark:bg-red-900/30 dark:text-red-400 dark:hover:bg-red-900/50"
              @click="deleteSavedReport(saved.id)"
            >
              <TrashIcon class="mr-1 h-4 w-4" />
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
  UserGroupIcon,
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
  format: 'xlsx',
})

const departments = ref(['IT', 'HR', 'Finance', 'Marketing', 'Operations', 'Sales'])

const employees = ref([
  { id: 1, name: 'John Doe' },
  { id: 2, name: 'Jane Smith' },
  { id: 3, name: 'Mike Johnson' },
  { id: 4, name: 'Sarah Wilson' },
])

const reportTypes = ref([
  {
    value: 'attendance',
    label: 'Laporan Kehadiran',
    description: 'Ringkasan kehadiran karyawan',
    icon: UserGroupIcon,
  },
  {
    value: 'detailed',
    label: 'Laporan Detail',
    description: 'Data lengkap per karyawan',
    icon: DocumentTextIcon,
  },
  {
    value: 'summary',
    label: 'Ringkasan Bulanan',
    description: 'Statistik per bulan',
    icon: ChartBarIcon,
  },
  {
    value: 'overtime',
    label: 'Laporan Lembur',
    description: 'Jam kerja tambahan',
    icon: ClockIcon,
  },
])

const exportFormats = ref([
  { value: 'xlsx', label: 'Excel (.xlsx)' },
  { value: 'pdf', label: 'PDF (.pdf)' },
  { value: 'csv', label: 'CSV (.csv)' },
  { value: 'json', label: 'JSON (.json)' },
])

const savedReports = ref([
  {
    id: 1,
    name: 'Laporan Kehadiran Oktober 2024',
    description: 'Semua departemen • Excel',
    createdAt: new Date('2024-10-31'),
    format: 'xlsx',
    size: '2.4 MB',
  },
  {
    id: 2,
    name: 'Laporan Detail IT Department',
    description: 'Departemen IT • PDF',
    createdAt: new Date('2024-10-28'),
    format: 'pdf',
    size: '1.8 MB',
  },
  {
    id: 3,
    name: 'Ringkasan Bulanan September',
    description: 'Semua karyawan • CSV',
    createdAt: new Date('2024-09-30'),
    format: 'csv',
    size: '856 KB',
  },
])

// Methods
const generateReport = async () => {
  generating.value = true

  try {
    // Simulate API call
    await new Promise((resolve) => setTimeout(resolve, 2000))

    // Generate sample report data
    reportData.value = {
      summary: {
        totalEmployees: 45,
        totalDays: getDaysBetween(reportConfig.startDate, reportConfig.endDate),
        averageAttendance: 87.5,
        totalHours: 3240,
      },
      records: generateSampleRecords(),
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
    { name: 'David Brown', department: 'Operations' },
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
      attendanceRate,
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
    await new Promise((resolve) => setTimeout(resolve, 1000))

    // Add new sample report
    savedReports.value.unshift({
      id: Date.now(),
      name: 'Laporan Terbaru',
      description: 'Generated just now • Excel',
      createdAt: new Date(),
      format: 'xlsx',
      size: '1.2 MB',
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
  if (confirm('Apakah Anda yakin ingin menghapus laporan ini?')) {
    savedReports.value = savedReports.value.filter((r) => r.id !== reportId)
  }
}

const formatDate = (date) => {
  return new Intl.DateTimeFormat('id-ID', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
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
  @apply mx-auto max-w-7xl p-6;
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
