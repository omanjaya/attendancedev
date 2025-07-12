<template>
  <div class="audit-logs-container">
    <!-- Header -->
    <div class="mb-8">
      <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-2">
        Audit Log Face Recognition
      </h2>
      <p class="text-gray-600 dark:text-gray-300">
        Pantau dan analisis semua aktivitas sistem pengenalan wajah untuk keamanan dan compliance
      </p>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
      <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/30 dark:to-blue-800/20 rounded-xl p-6 border border-blue-200 dark:border-blue-700/50">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-blue-600 dark:text-blue-400">Total Events</p>
            <p class="text-2xl font-bold text-blue-900 dark:text-blue-100">
              {{ statistics.totalEvents }}
            </p>
            <div class="flex items-center mt-1">
              <ArrowUpIcon class="w-4 h-4 text-blue-500 mr-1" />
              <span class="text-sm text-blue-600 dark:text-blue-400">+{{ statistics.eventsGrowth }}%</span>
            </div>
          </div>
          <div class="w-12 h-12 rounded-xl bg-blue-500 flex items-center justify-center">
            <DocumentTextIcon class="w-6 h-6 text-white" />
          </div>
        </div>
      </div>

      <div class="bg-gradient-to-br from-emerald-50 to-emerald-100 dark:from-emerald-900/30 dark:to-emerald-800/20 rounded-xl p-6 border border-emerald-200 dark:border-emerald-700/50">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-emerald-600 dark:text-emerald-400">Successful</p>
            <p class="text-2xl font-bold text-emerald-900 dark:text-emerald-100">
              {{ statistics.successfulEvents }}
            </p>
            <div class="flex items-center mt-1">
              <span class="text-sm text-emerald-600 dark:text-emerald-400">
                {{ Math.round((statistics.successfulEvents / statistics.totalEvents) * 100) }}% dari total
              </span>
            </div>
          </div>
          <div class="w-12 h-12 rounded-xl bg-emerald-500 flex items-center justify-center">
            <CheckCircleIcon class="w-6 h-6 text-white" />
          </div>
        </div>
      </div>

      <div class="bg-gradient-to-br from-red-50 to-red-100 dark:from-red-900/30 dark:to-red-800/20 rounded-xl p-6 border border-red-200 dark:border-red-700/50">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-red-600 dark:text-red-400">Failed/Errors</p>
            <p class="text-2xl font-bold text-red-900 dark:text-red-100">
              {{ statistics.failedEvents }}
            </p>
            <div class="flex items-center mt-1">
              <span class="text-sm text-red-600 dark:text-red-400">
                {{ Math.round((statistics.failedEvents / statistics.totalEvents) * 100) }}% dari total
              </span>
            </div>
          </div>
          <div class="w-12 h-12 rounded-xl bg-red-500 flex items-center justify-center">
            <ExclamationTriangleIcon class="w-6 h-6 text-white" />
          </div>
        </div>
      </div>

      <div class="bg-gradient-to-br from-amber-50 to-amber-100 dark:from-amber-900/30 dark:to-amber-800/20 rounded-xl p-6 border border-amber-200 dark:border-amber-700/50">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-amber-600 dark:text-amber-400">Security Events</p>
            <p class="text-2xl font-bold text-amber-900 dark:text-amber-100">
              {{ statistics.securityEvents }}
            </p>
            <div class="flex items-center mt-1">
              <span class="text-sm text-amber-600 dark:text-amber-400">
                {{ statistics.securityEvents > 0 ? 'Perlu perhatian' : 'Aman' }}
              </span>
            </div>
          </div>
          <div class="w-12 h-12 rounded-xl bg-amber-500 flex items-center justify-center">
            <ShieldExclamationIcon class="w-6 h-6 text-white" />
          </div>
        </div>
      </div>
    </div>

    <!-- Filters and Actions -->
    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700 mb-6">
      <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between space-y-4 lg:space-y-0">
        <div class="flex flex-col md:flex-row space-y-4 md:space-y-0 md:space-x-4">
          <!-- Date Range -->
          <div class="flex items-center space-x-2">
            <CalendarIcon class="w-5 h-5 text-gray-400" />
            <input
              v-model="filters.startDate"
              type="date"
              class="px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-sm"
            >
            <span class="text-gray-500 dark:text-gray-400">-</span>
            <input
              v-model="filters.endDate"
              type="date"
              class="px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-sm"
            >
          </div>

          <!-- Event Type Filter -->
          <select
            v-model="filters.eventType"
            class="px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-sm"
          >
            <option value="">Semua Event</option>
            <option value="detection">Deteksi Wajah</option>
            <option value="enrollment">Pendaftaran</option>
            <option value="authentication">Autentikasi</option>
            <option value="liveness">Liveness Check</option>
            <option value="template_update">Update Template</option>
            <option value="system">Sistem</option>
          </select>

          <!-- Status Filter -->
          <select
            v-model="filters.status"
            class="px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-sm"
          >
            <option value="">Semua Status</option>
            <option value="success">Berhasil</option>
            <option value="failed">Gagal</option>
            <option value="warning">Peringatan</option>
            <option value="error">Error</option>
          </select>

          <!-- Risk Level Filter -->
          <select
            v-model="filters.riskLevel"
            class="px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-sm"
          >
            <option value="">Semua Level</option>
            <option value="low">Low Risk</option>
            <option value="medium">Medium Risk</option>
            <option value="high">High Risk</option>
            <option value="critical">Critical</option>
          </select>

          <!-- Search -->
          <div class="relative">
            <MagnifyingGlassIcon class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" />
            <input
              v-model="filters.searchQuery"
              type="text"
              placeholder="Cari user, IP, atau event..."
              class="pl-10 pr-4 py-2 w-full md:w-64 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-sm"
            >
          </div>
        </div>

        <div class="flex items-center space-x-3">
          <!-- Real-time toggle -->
          <label class="flex items-center space-x-2">
            <input
              v-model="realTimeEnabled"
              type="checkbox"
              class="w-4 h-4 text-emerald-600 bg-gray-100 border-gray-300 rounded focus:ring-emerald-500"
            >
            <span class="text-sm text-gray-700 dark:text-gray-300">Real-time</span>
          </label>

          <!-- Export Button -->
          <button
            @click="exportLogs"
            class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors"
          >
            <ArrowDownTrayIcon class="w-4 h-4 mr-2" />
            Export
          </button>

          <!-- Refresh Button -->
          <button
            @click="refreshLogs"
            :disabled="loading"
            class="inline-flex items-center px-4 py-2 text-sm font-medium text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/30 hover:bg-emerald-100 dark:hover:bg-emerald-900/50 rounded-lg transition-colors disabled:opacity-50"
          >
            <ArrowPathIcon :class="['w-4 h-4 mr-2', { 'animate-spin': loading }]" />
            {{ loading ? 'Loading...' : 'Refresh' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Activity Chart -->
    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700 mb-6">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
        Aktivitas Harian
      </h3>
      <div class="h-64">
        <canvas ref="activityChart"></canvas>
      </div>
    </div>

    <!-- Logs Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead>
            <tr class="bg-gray-50 dark:bg-gray-700">
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Waktu
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Event
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                User
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Status
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Risk Level
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                IP Address
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Aksi
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
            <tr
              v-for="log in filteredLogs"
              :key="log.id"
              class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
            >
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                <div>{{ formatDateTime(log.timestamp) }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">
                  {{ formatTimeAgo(log.timestamp) }}
                </div>
              </td>
              
              <td class="px-6 py-4">
                <div class="flex items-center space-x-3">
                  <div :class="[
                    'w-8 h-8 rounded-full flex items-center justify-center',
                    getEventTypeColor(log.event_type)
                  ]">
                    <component :is="getEventTypeIcon(log.event_type)" class="w-4 h-4" />
                  </div>
                  <div>
                    <div class="font-medium text-gray-900 dark:text-gray-100">
                      {{ getEventTypeName(log.event_type) }}
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                      {{ log.description }}
                    </div>
                  </div>
                </div>
              </td>
              
              <td class="px-6 py-4">
                <div class="flex items-center space-x-3">
                  <div class="w-8 h-8 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center">
                    <span class="text-xs font-medium text-gray-700 dark:text-gray-300">
                      {{ log.user_name?.charAt(0) || 'S' }}
                    </span>
                  </div>
                  <div>
                    <div class="font-medium text-gray-900 dark:text-gray-100">
                      {{ log.user_name || 'System' }}
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                      {{ log.user_id || 'system' }}
                    </div>
                  </div>
                </div>
              </td>
              
              <td class="px-6 py-4">
                <span :class="[
                  'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                  getStatusColor(log.status)
                ]">
                  {{ getStatusText(log.status) }}
                </span>
              </td>
              
              <td class="px-6 py-4">
                <span :class="[
                  'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                  getRiskLevelColor(log.risk_level)
                ]">
                  {{ getRiskLevelText(log.risk_level) }}
                </span>
              </td>
              
              <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                <div>{{ log.ip_address }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">
                  {{ log.user_agent_summary || 'Unknown' }}
                </div>
              </td>
              
              <td class="px-6 py-4">
                <button
                  @click="viewLogDetails(log)"
                  class="text-emerald-600 dark:text-emerald-400 hover:text-emerald-500 dark:hover:text-emerald-300 font-medium text-sm"
                >
                  Detail
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Pagination -->
    <div class="flex items-center justify-between mt-6">
      <div class="text-sm text-gray-700 dark:text-gray-300">
        Menampilkan {{ startIndex + 1 }} hingga {{ Math.min(endIndex, filteredLogs.length) }} dari {{ filteredLogs.length }} log
      </div>
      
      <div class="flex items-center space-x-2">
        <button
          @click="previousPage"
          :disabled="currentPage === 1"
          class="px-3 py-1 text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          Sebelumnya
        </button>
        
        <span class="text-sm text-gray-700 dark:text-gray-300">
          {{ currentPage }} dari {{ totalPages }}
        </span>
        
        <button
          @click="nextPage"
          :disabled="currentPage === totalPages"
          class="px-3 py-1 text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          Selanjutnya
        </button>
      </div>
    </div>

    <!-- Log Detail Modal -->
    <div
      v-if="selectedLog"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
      @click="selectedLog = null"
    >
      <div
        class="bg-white dark:bg-gray-800 rounded-xl p-6 max-w-4xl w-full mx-4 max-h-[80vh] overflow-y-auto"
        @click.stop
      >
        <div class="flex items-center justify-between mb-6">
          <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
            Detail Audit Log
          </h3>
          <button
            @click="selectedLog = null"
            class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300"
          >
            <XMarkIcon class="w-6 h-6" />
          </button>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <!-- Basic Information -->
          <div>
            <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-3">
              Informasi Dasar
            </h4>
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 space-y-3">
              <div class="flex justify-between">
                <span class="text-sm text-gray-600 dark:text-gray-400">Event ID:</span>
                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                  {{ selectedLog.id }}
                </span>
              </div>
              <div class="flex justify-between">
                <span class="text-sm text-gray-600 dark:text-gray-400">Timestamp:</span>
                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                  {{ formatDateTime(selectedLog.timestamp) }}
                </span>
              </div>
              <div class="flex justify-between">
                <span class="text-sm text-gray-600 dark:text-gray-400">Event Type:</span>
                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                  {{ getEventTypeName(selectedLog.event_type) }}
                </span>
              </div>
              <div class="flex justify-between">
                <span class="text-sm text-gray-600 dark:text-gray-400">Status:</span>
                <span :class="[
                  'text-sm font-medium',
                  selectedLog.status === 'success' ? 'text-emerald-600 dark:text-emerald-400' :
                  selectedLog.status === 'failed' ? 'text-red-600 dark:text-red-400' :
                  'text-amber-600 dark:text-amber-400'
                ]">
                  {{ getStatusText(selectedLog.status) }}
                </span>
              </div>
              <div class="flex justify-between">
                <span class="text-sm text-gray-600 dark:text-gray-400">Risk Level:</span>
                <span :class="[
                  'text-sm font-medium',
                  selectedLog.risk_level === 'low' ? 'text-green-600 dark:text-green-400' :
                  selectedLog.risk_level === 'medium' ? 'text-yellow-600 dark:text-yellow-400' :
                  selectedLog.risk_level === 'high' ? 'text-orange-600 dark:text-orange-400' :
                  'text-red-600 dark:text-red-400'
                ]">
                  {{ getRiskLevelText(selectedLog.risk_level) }}
                </span>
              </div>
            </div>
          </div>

          <!-- Technical Details -->
          <div>
            <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-3">
              Detail Teknis
            </h4>
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 space-y-3">
              <div class="flex justify-between">
                <span class="text-sm text-gray-600 dark:text-gray-400">IP Address:</span>
                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                  {{ selectedLog.ip_address }}
                </span>
              </div>
              <div class="flex justify-between">
                <span class="text-sm text-gray-600 dark:text-gray-400">User Agent:</span>
                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                  {{ selectedLog.user_agent_summary }}
                </span>
              </div>
              <div class="flex justify-between">
                <span class="text-sm text-gray-600 dark:text-gray-400">Session ID:</span>
                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                  {{ selectedLog.session_id }}
                </span>
              </div>
              <div class="flex justify-between">
                <span class="text-sm text-gray-600 dark:text-gray-400">Detection Time:</span>
                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                  {{ selectedLog.detection_time }}ms
                </span>
              </div>
              <div class="flex justify-between">
                <span class="text-sm text-gray-600 dark:text-gray-400">Confidence:</span>
                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                  {{ selectedLog.confidence }}%
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Event Details -->
        <div class="mt-6">
          <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-3">
            Detail Event
          </h4>
          <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
            <p class="text-sm text-gray-700 dark:text-gray-300 mb-3">
              {{ selectedLog.description }}
            </p>
            
            <div v-if="selectedLog.metadata" class="space-y-2">
              <div
                v-for="(value, key) in selectedLog.metadata"
                :key="key"
                class="flex justify-between text-sm"
              >
                <span class="text-gray-600 dark:text-gray-400 capitalize">{{ key.replace(/_/g, ' ') }}:</span>
                <span class="text-gray-900 dark:text-gray-100 font-medium">
                  {{ typeof value === 'object' ? JSON.stringify(value) : value }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Error Details (if any) -->
        <div v-if="selectedLog.error_details" class="mt-6">
          <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-3">
            Error Details
          </h4>
          <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700/50 rounded-lg p-4">
            <pre class="text-sm text-red-800 dark:text-red-200 whitespace-pre-wrap">{{ selectedLog.error_details }}</pre>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-end space-x-3 mt-6 pt-4 border-t border-gray-200 dark:border-gray-600">
          <button
            v-if="selectedLog.risk_level === 'high' || selectedLog.risk_level === 'critical'"
            @click="flagForInvestigation(selectedLog)"
            class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors"
          >
            Flag for Investigation
          </button>
          <button
            @click="selectedLog = null"
            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors"
          >
            Tutup
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted, watch } from 'vue'
import { 
  DocumentTextIcon,
  CheckCircleIcon,
  ExclamationTriangleIcon,
  ShieldExclamationIcon,
  CalendarIcon,
  MagnifyingGlassIcon,
  ArrowDownTrayIcon,
  ArrowPathIcon,
  ArrowUpIcon,
  XMarkIcon,
  FaceSmileIcon,
  UserPlusIcon,
  KeyIcon,
  ShieldCheckIcon,
  Cog6ToothIcon,
  ClockIcon
} from '@heroicons/vue/24/outline'

// Reactive state
const loading = ref(false)
const realTimeEnabled = ref(false)
const selectedLog = ref(null)
const currentPage = ref(1)
const itemsPerPage = 20
const realTimeInterval = ref(null)

const filters = reactive({
  startDate: new Date().toISOString().split('T')[0],
  endDate: new Date().toISOString().split('T')[0],
  eventType: '',
  status: '',
  riskLevel: '',
  searchQuery: ''
})

const statistics = reactive({
  totalEvents: 1247,
  eventsGrowth: 8.5,
  successfulEvents: 1089,
  failedEvents: 158,
  securityEvents: 12
})

const auditLogs = ref([
  {
    id: 'LOG001',
    timestamp: new Date(Date.now() - 300000),
    event_type: 'detection',
    description: 'Face detection successful for employee check-in',
    user_name: 'John Doe',
    user_id: 'EMP001',
    status: 'success',
    risk_level: 'low',
    ip_address: '192.168.1.100',
    user_agent_summary: 'Chrome 120.0',
    session_id: 'sess_abc123',
    detection_time: 234,
    confidence: 94,
    metadata: {
      algorithm: 'Face-API.js',
      liveness_score: 89,
      device_info: 'Camera HD 1080p'
    }
  },
  {
    id: 'LOG002',
    timestamp: new Date(Date.now() - 600000),
    event_type: 'authentication',
    description: 'Failed authentication attempt - low confidence',
    user_name: 'Jane Smith',
    user_id: 'EMP002',
    status: 'failed',
    risk_level: 'medium',
    ip_address: '192.168.1.101',
    user_agent_summary: 'Firefox 119.0',
    session_id: 'sess_def456',
    detection_time: 1234,
    confidence: 45,
    metadata: {
      algorithm: 'MediaPipe',
      liveness_score: 32,
      failure_reason: 'Insufficient lighting'
    },
    error_details: 'Face detection confidence below threshold (45% < 70%)'
  },
  {
    id: 'LOG003',
    timestamp: new Date(Date.now() - 900000),
    event_type: 'enrollment',
    description: 'New employee face template enrollment',
    user_name: 'Mike Johnson',
    user_id: 'EMP003',
    status: 'success',
    risk_level: 'low',
    ip_address: '192.168.1.102',
    user_agent_summary: 'Chrome 120.0',
    session_id: 'sess_ghi789',
    detection_time: 456,
    confidence: 96,
    metadata: {
      algorithm: 'Face-API.js',
      template_count: 4,
      enrollment_quality: 'High'
    }
  },
  {
    id: 'LOG004',
    timestamp: new Date(Date.now() - 1200000),
    event_type: 'liveness',
    description: 'Liveness detection failed - potential spoofing attempt',
    user_name: null,
    user_id: null,
    status: 'failed',
    risk_level: 'high',
    ip_address: '203.0.113.45',
    user_agent_summary: 'Unknown',
    session_id: 'sess_jkl012',
    detection_time: 2345,
    confidence: 78,
    metadata: {
      algorithm: 'Face-API.js',
      liveness_score: 23,
      failed_gestures: ['blink', 'smile'],
      suspicious_patterns: ['static_image', 'no_depth']
    },
    error_details: 'Multiple liveness checks failed. Potential spoofing attempt detected.'
  },
  {
    id: 'LOG005',
    timestamp: new Date(Date.now() - 1800000),
    event_type: 'system',
    description: 'Face recognition system maintenance completed',
    user_name: null,
    user_id: null,
    status: 'success',
    risk_level: 'low',
    ip_address: 'localhost',
    user_agent_summary: 'System',
    session_id: 'sys_maint',
    detection_time: 0,
    confidence: 100,
    metadata: {
      maintenance_type: 'template_optimization',
      processed_templates: 245,
      duration: '15 minutes'
    }
  }
])

const activityChart = ref(null)

// Computed
const filteredLogs = computed(() => {
  let filtered = auditLogs.value

  // Date range filter
  if (filters.startDate && filters.endDate) {
    const start = new Date(filters.startDate)
    const end = new Date(filters.endDate)
    end.setHours(23, 59, 59, 999)
    
    filtered = filtered.filter(log => {
      const logDate = new Date(log.timestamp)
      return logDate >= start && logDate <= end
    })
  }

  // Event type filter
  if (filters.eventType) {
    filtered = filtered.filter(log => log.event_type === filters.eventType)
  }

  // Status filter
  if (filters.status) {
    filtered = filtered.filter(log => log.status === filters.status)
  }

  // Risk level filter
  if (filters.riskLevel) {
    filtered = filtered.filter(log => log.risk_level === filters.riskLevel)
  }

  // Search filter
  if (filters.searchQuery) {
    const query = filters.searchQuery.toLowerCase()
    filtered = filtered.filter(log => 
      log.user_name?.toLowerCase().includes(query) ||
      log.user_id?.toLowerCase().includes(query) ||
      log.ip_address.includes(query) ||
      log.description.toLowerCase().includes(query)
    )
  }

  // Sort by timestamp (newest first)
  return filtered.sort((a, b) => new Date(b.timestamp) - new Date(a.timestamp))
})

const totalPages = computed(() => {
  return Math.ceil(filteredLogs.value.length / itemsPerPage)
})

const startIndex = computed(() => {
  return (currentPage.value - 1) * itemsPerPage
})

const endIndex = computed(() => {
  return startIndex.value + itemsPerPage
})

const paginatedLogs = computed(() => {
  return filteredLogs.value.slice(startIndex.value, endIndex.value)
})

// Methods
const formatDateTime = (date) => {
  return new Intl.DateTimeFormat('id-ID', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit'
  }).format(date)
}

const formatTimeAgo = (date) => {
  const now = new Date()
  const diff = now - date
  const minutes = Math.floor(diff / 60000)
  const hours = Math.floor(minutes / 60)
  const days = Math.floor(hours / 24)

  if (days > 0) return `${days} hari lalu`
  if (hours > 0) return `${hours} jam lalu`
  if (minutes > 0) return `${minutes} menit lalu`
  return 'Baru saja'
}

const getEventTypeIcon = (type) => {
  const icons = {
    detection: FaceSmileIcon,
    enrollment: UserPlusIcon,
    authentication: KeyIcon,
    liveness: ShieldCheckIcon,
    template_update: ArrowPathIcon,
    system: Cog6ToothIcon
  }
  return icons[type] || DocumentTextIcon
}

const getEventTypeColor = (type) => {
  const colors = {
    detection: 'bg-blue-100 text-blue-600 dark:bg-blue-800 dark:text-blue-400',
    enrollment: 'bg-green-100 text-green-600 dark:bg-green-800 dark:text-green-400',
    authentication: 'bg-purple-100 text-purple-600 dark:bg-purple-800 dark:text-purple-400',
    liveness: 'bg-amber-100 text-amber-600 dark:bg-amber-800 dark:text-amber-400',
    template_update: 'bg-indigo-100 text-indigo-600 dark:bg-indigo-800 dark:text-indigo-400',
    system: 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'
  }
  return colors[type] || 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'
}

const getEventTypeName = (type) => {
  const names = {
    detection: 'Deteksi Wajah',
    enrollment: 'Pendaftaran',
    authentication: 'Autentikasi',
    liveness: 'Liveness Check',
    template_update: 'Update Template',
    system: 'Sistem'
  }
  return names[type] || 'Unknown'
}

const getStatusColor = (status) => {
  const colors = {
    success: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-800 dark:text-emerald-200',
    failed: 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-200',
    warning: 'bg-amber-100 text-amber-800 dark:bg-amber-800 dark:text-amber-200',
    error: 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-200'
  }
  return colors[status] || 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200'
}

const getStatusText = (status) => {
  const texts = {
    success: 'Berhasil',
    failed: 'Gagal',
    warning: 'Peringatan',
    error: 'Error'
  }
  return texts[status] || 'Unknown'
}

const getRiskLevelColor = (level) => {
  const colors = {
    low: 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-200',
    medium: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-200',
    high: 'bg-orange-100 text-orange-800 dark:bg-orange-800 dark:text-orange-200',
    critical: 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-200'
  }
  return colors[level] || 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200'
}

const getRiskLevelText = (level) => {
  const texts = {
    low: 'Low',
    medium: 'Medium',
    high: 'High',
    critical: 'Critical'
  }
  return texts[level] || 'Unknown'
}

const viewLogDetails = (log) => {
  selectedLog.value = log
}

const flagForInvestigation = (log) => {
  console.log('Flagging log for investigation:', log.id)
  // In production, this would trigger security team notification
  alert(`Log ${log.id} telah ditandai untuk investigasi`)
}

const refreshLogs = async () => {
  loading.value = true
  try {
    // Simulate API call
    await new Promise(resolve => setTimeout(resolve, 1000))
    
    // In production, fetch fresh logs from API
    console.log('Logs refreshed')
    
    // Add a new sample log to simulate real-time updates
    auditLogs.value.unshift({
      id: `LOG${Date.now()}`,
      timestamp: new Date(),
      event_type: 'detection',
      description: 'Real-time face detection event',
      user_name: 'Test User',
      user_id: 'TEST001',
      status: 'success',
      risk_level: 'low',
      ip_address: '192.168.1.200',
      user_agent_summary: 'Chrome 120.0',
      session_id: `sess_${Date.now()}`,
      detection_time: 156,
      confidence: 88,
      metadata: {
        algorithm: 'Face-API.js',
        liveness_score: 91
      }
    })
    
    // Update statistics
    statistics.totalEvents++
    statistics.successfulEvents++
    
  } catch (error) {
    console.error('Failed to refresh logs:', error)
  } finally {
    loading.value = false
  }
}

const exportLogs = () => {
  const data = JSON.stringify(filteredLogs.value, null, 2)
  const blob = new Blob([data], { type: 'application/json' })
  const url = URL.createObjectURL(blob)
  
  const a = document.createElement('a')
  a.href = url
  a.download = `audit_logs_${new Date().toISOString().split('T')[0]}.json`
  document.body.appendChild(a)
  a.click()
  document.body.removeChild(a)
  URL.revokeObjectURL(url)
}

const initializeChart = () => {
  if (!activityChart.value) return

  const ctx = activityChart.value.getContext('2d')
  const canvas = ctx.canvas
  const width = canvas.width
  const height = canvas.height

  ctx.clearRect(0, 0, width, height)

  // Sample data for the last 7 days
  const labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']
  const successData = [45, 67, 89, 123, 156, 78, 34]
  const failedData = [5, 8, 12, 15, 18, 9, 4]

  // Draw basic chart (in production, use Chart.js)
  drawActivityChart(ctx, labels, successData, failedData)
}

const drawActivityChart = (ctx, labels, successData, failedData) => {
  const canvas = ctx.canvas
  const width = canvas.width
  const height = canvas.height
  const padding = 40

  ctx.clearRect(0, 0, width, height)

  // Draw grid
  ctx.strokeStyle = '#E5E7EB'
  ctx.lineWidth = 1

  for (let i = 0; i <= 5; i++) {
    const y = padding + (height - 2 * padding) / 5 * i
    ctx.beginPath()
    ctx.moveTo(padding, y)
    ctx.lineTo(width - padding, y)
    ctx.stroke()
  }

  // Draw success line
  ctx.strokeStyle = '#10B981'
  ctx.lineWidth = 2
  ctx.beginPath()

  successData.forEach((value, index) => {
    const x = padding + (width - 2 * padding) / (successData.length - 1) * index
    const y = height - padding - (value / Math.max(...successData)) * (height - 2 * padding)
    
    if (index === 0) {
      ctx.moveTo(x, y)
    } else {
      ctx.lineTo(x, y)
    }
  })
  ctx.stroke()

  // Draw failed line
  ctx.strokeStyle = '#EF4444'
  ctx.lineWidth = 2
  ctx.beginPath()

  failedData.forEach((value, index) => {
    const x = padding + (width - 2 * padding) / (failedData.length - 1) * index
    const y = height - padding - (value / Math.max(...successData)) * (height - 2 * padding)
    
    if (index === 0) {
      ctx.moveTo(x, y)
    } else {
      ctx.lineTo(x, y)
    }
  })
  ctx.stroke()
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

const startRealTimeUpdates = () => {
  if (realTimeInterval.value) {
    clearInterval(realTimeInterval.value)
  }
  
  realTimeInterval.value = setInterval(() => {
    if (realTimeEnabled.value) {
      refreshLogs()
    }
  }, 30000) // Update every 30 seconds
}

const stopRealTimeUpdates = () => {
  if (realTimeInterval.value) {
    clearInterval(realTimeInterval.value)
    realTimeInterval.value = null
  }
}

// Watchers
watch(realTimeEnabled, (enabled) => {
  if (enabled) {
    startRealTimeUpdates()
  } else {
    stopRealTimeUpdates()
  }
})

// Lifecycle
onMounted(() => {
  // Set date range to last 7 days
  const endDate = new Date()
  const startDate = new Date(endDate.getTime() - 7 * 24 * 60 * 60 * 1000)
  
  filters.startDate = startDate.toISOString().split('T')[0]
  filters.endDate = endDate.toISOString().split('T')[0]
  
  // Initialize chart
  setTimeout(initializeChart, 100)
})

onUnmounted(() => {
  stopRealTimeUpdates()
})
</script>

<style scoped>
.audit-logs-container {
  @apply max-w-7xl mx-auto p-6;
}

/* Chart canvas styling */
canvas {
  width: 100%;
  height: 100%;
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

/* Transitions */
.transition-colors {
  transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out;
}

/* Pre formatting */
pre {
  font-family: 'Fira Code', 'Consolas', monospace;
  font-size: 0.875rem;
  line-height: 1.5;
}
</style>