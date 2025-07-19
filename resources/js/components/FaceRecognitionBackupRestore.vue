<template>
  <div class="backup-restore-container">
    <!-- Header -->
    <div class="mb-8">
      <h2 class="mb-2 text-2xl font-bold text-gray-900 dark:text-gray-100">
        Backup & Restore Face Recognition
      </h2>
      <p class="text-gray-600 dark:text-gray-300">
        Kelola backup data face recognition untuk keamanan dan disaster recovery
      </p>
    </div>

    <!-- Storage Statistics -->
    <div class="mb-8 grid grid-cols-1 gap-6 md:grid-cols-4">
      <div
        class="rounded-xl border border-blue-200 bg-gradient-to-br from-blue-50 to-blue-100 p-6 dark:border-blue-700/50 dark:from-blue-900/30 dark:to-blue-800/20"
      >
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-blue-600 dark:text-blue-400">
              Template Count
            </p>
            <p class="text-2xl font-bold text-blue-900 dark:text-blue-100">
              {{ statistics.totalTemplates }}
            </p>
          </div>
          <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-500">
            <FaceSmileIcon class="h-6 w-6 text-white" />
          </div>
        </div>
      </div>

      <div
        class="rounded-xl border border-emerald-200 bg-gradient-to-br from-emerald-50 to-emerald-100 p-6 dark:border-emerald-700/50 dark:from-emerald-900/30 dark:to-emerald-800/20"
      >
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-emerald-600 dark:text-emerald-400">
              Storage Used
            </p>
            <p class="text-2xl font-bold text-emerald-900 dark:text-emerald-100">
              {{ formatFileSize(statistics.storageUsed) }}
            </p>
          </div>
          <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-500">
            <CircleStackIcon class="h-6 w-6 text-white" />
          </div>
        </div>
      </div>

      <div
        class="rounded-xl border border-purple-200 bg-gradient-to-br from-purple-50 to-purple-100 p-6 dark:border-purple-700/50 dark:from-purple-900/30 dark:to-purple-800/20"
      >
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-purple-600 dark:text-purple-400">
              Total Backups
            </p>
            <p class="text-2xl font-bold text-purple-900 dark:text-purple-100">
              {{ statistics.totalBackups }}
            </p>
          </div>
          <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-purple-500">
            <ArchiveBoxIcon class="h-6 w-6 text-white" />
          </div>
        </div>
      </div>

      <div
        class="rounded-xl border border-amber-200 bg-gradient-to-br from-amber-50 to-amber-100 p-6 dark:border-amber-700/50 dark:from-amber-900/30 dark:to-amber-800/20"
      >
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-amber-600 dark:text-amber-400">
              Last Backup
            </p>
            <p class="text-2xl font-bold text-amber-900 dark:text-amber-100">
              {{ formatTimeAgo(statistics.lastBackup) }}
            </p>
          </div>
          <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-500">
            <ClockIcon class="h-6 w-6 text-white" />
          </div>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
      <!-- Backup Section -->
      <div class="space-y-6">
        <!-- Create Backup -->
        <div
          class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800"
        >
          <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100">
            Buat Backup Baru
          </h3>

          <div class="space-y-4">
            <div>
              <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                Nama Backup
              </label>
              <input
                v-model="backupForm.name"
                type="text"
                placeholder="Masukkan nama backup..."
                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-700"
              >
            </div>

            <div>
              <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                Deskripsi
              </label>
              <textarea
                v-model="backupForm.description"
                rows="3"
                placeholder="Deskripsi backup (opsional)..."
                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-700"
              />
            </div>

            <div>
              <label class="mb-3 block text-sm font-medium text-gray-700 dark:text-gray-300">
                Tipe Backup
              </label>
              <div class="space-y-2">
                <label class="flex items-center">
                  <input
                    v-model="backupForm.type"
                    type="radio"
                    value="full"
                    class="h-4 w-4 border-gray-300 bg-gray-100 text-emerald-600 focus:ring-emerald-500"
                  >
                  <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                    Full Backup (Semua data template wajah)
                  </span>
                </label>
                <label class="flex items-center">
                  <input
                    v-model="backupForm.type"
                    type="radio"
                    value="selective"
                    class="h-4 w-4 border-gray-300 bg-gray-100 text-emerald-600 focus:ring-emerald-500"
                  >
                  <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                    Selective Backup (Pilih template tertentu)
                  </span>
                </label>
                <label class="flex items-center">
                  <input
                    v-model="backupForm.type"
                    type="radio"
                    value="settings"
                    class="h-4 w-4 border-gray-300 bg-gray-100 text-emerald-600 focus:ring-emerald-500"
                  >
                  <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                    Settings Only (Hanya pengaturan sistem)
                  </span>
                </label>
              </div>
            </div>

            <div v-if="backupForm.type === 'selective'">
              <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                Pilih Template
              </label>
              <div
                class="max-h-32 overflow-y-auto rounded-lg border border-gray-300 p-3 dark:border-gray-600"
              >
                <div
                  v-for="template in availableTemplates"
                  :key="template.id"
                  class="flex items-center space-x-2 py-1"
                >
                  <input
                    v-model="backupForm.selectedTemplates"
                    :value="template.id"
                    type="checkbox"
                    class="h-4 w-4 rounded border-gray-300 bg-gray-100 text-emerald-600 focus:ring-emerald-500"
                  >
                  <span class="text-sm text-gray-700 dark:text-gray-300">
                    {{ template.employee_name }} ({{ template.employee_id }})
                  </span>
                </div>
              </div>
            </div>

            <div class="flex items-center space-x-2">
              <input
                v-model="backupForm.encrypt"
                type="checkbox"
                class="h-4 w-4 rounded border-gray-300 bg-gray-100 text-emerald-600 focus:ring-emerald-500"
              >
              <label class="text-sm text-gray-700 dark:text-gray-300">
                Enkripsi backup dengan password
              </label>
            </div>

            <div v-if="backupForm.encrypt">
              <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                Password Enkripsi
              </label>
              <input
                v-model="backupForm.password"
                type="password"
                placeholder="Masukkan password..."
                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-700"
              >
            </div>

            <div v-if="backupProgress > 0 && backupProgress < 100" class="space-y-2">
              <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400">
                <span>Creating backup...</span>
                <span>{{ backupProgress }}%</span>
              </div>
              <div class="h-2 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                <div
                  class="h-2 rounded-full bg-emerald-600 transition-all duration-300"
                  :style="{ width: `${backupProgress}%` }"
                />
              </div>
            </div>

            <button
              :disabled="!backupForm.name || creatingBackup"
              class="w-full rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50"
              @click="createBackup"
            >
              <span v-if="creatingBackup" class="flex items-center justify-center">
                <svg
                  class="-ml-1 mr-3 h-4 w-4 animate-spin text-white"
                  xmlns="http://www.w3.org/2000/svg"
                  fill="none"
                  viewBox="0 0 24 24"
                >
                  <circle
                    class="opacity-25"
                    cx="12"
                    cy="12"
                    r="10"
                    stroke="currentColor"
                    stroke-width="4"
                  />
                  <path
                    class="opacity-75"
                    fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                  />
                </svg>
                Creating Backup...
              </span>
              <span v-else>Buat Backup</span>
            </button>
          </div>
        </div>

        <!-- Scheduled Backups -->
        <div
          class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800"
        >
          <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100">
            Backup Otomatis
          </h3>

          <div class="space-y-4">
            <div class="flex items-center justify-between">
              <div>
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                  Aktifkan Backup Otomatis
                </label>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                  Backup otomatis akan dibuat sesuai jadwal
                </p>
              </div>
              <label class="relative inline-flex cursor-pointer items-center">
                <input v-model="scheduleSettings.enabled" type="checkbox" class="peer sr-only">
                <div
                  class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-emerald-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-emerald-300 dark:border-gray-600 dark:bg-gray-700 dark:peer-focus:ring-emerald-800"
                />
              </label>
            </div>

            <div v-if="scheduleSettings.enabled" class="space-y-4">
              <div>
                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                  Frekuensi Backup
                </label>
                <select
                  v-model="scheduleSettings.frequency"
                  class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-700"
                >
                  <option value="daily">
                    Harian
                  </option>
                  <option value="weekly">
                    Mingguan
                  </option>
                  <option value="monthly">
                    Bulanan
                  </option>
                </select>
              </div>

              <div>
                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                  Waktu Backup
                </label>
                <input
                  v-model="scheduleSettings.time"
                  type="time"
                  class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-700"
                >
              </div>

              <div>
                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                  Maksimal Backup Tersimpan
                </label>
                <input
                  v-model="scheduleSettings.maxBackups"
                  type="number"
                  min="1"
                  max="30"
                  class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-700"
                >
              </div>

              <button
                :disabled="savingSchedule"
                class="w-full rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700 disabled:opacity-50"
                @click="saveScheduleSettings"
              >
                {{ savingSchedule ? 'Saving...' : 'Simpan Pengaturan' }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Backup History & Restore -->
      <div class="space-y-6">
        <!-- Search and Filter -->
        <div
          class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800"
        >
          <div
            class="flex flex-col space-y-4 md:flex-row md:items-center md:justify-between md:space-y-0"
          >
            <div class="relative max-w-md flex-1">
              <MagnifyingGlassIcon
                class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 transform text-gray-400"
              />
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Cari backup..."
                class="w-full rounded-lg border border-gray-300 bg-white py-2 pl-10 pr-4 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-700"
              >
            </div>

            <button
              :disabled="loading"
              class="inline-flex items-center rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-200 disabled:opacity-50 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
              @click="refreshBackups"
            >
              <ArrowPathIcon :class="['mr-2 h-4 w-4', { 'animate-spin': loading }]" />
              {{ loading ? 'Loading...' : 'Refresh' }}
            </button>
          </div>
        </div>

        <!-- Backup List -->
        <div
          class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800"
        >
          <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100">
            Riwayat Backup
          </h3>

          <div class="space-y-3">
            <div
              v-for="backup in filteredBackups"
              :key="backup.id"
              class="flex items-center justify-between rounded-lg bg-gray-50 p-4 dark:bg-gray-700"
            >
              <div class="flex items-center space-x-4">
                <div class="flex-shrink-0">
                  <div
                    :class="[
                      'flex h-10 w-10 items-center justify-center rounded-full',
                      backup.status === 'completed'
                        ? 'bg-emerald-100 text-emerald-600 dark:bg-emerald-800 dark:text-emerald-400'
                        : backup.status === 'failed'
                          ? 'bg-red-100 text-red-600 dark:bg-red-800 dark:text-red-400'
                          : 'bg-amber-100 text-amber-600 dark:bg-amber-800 dark:text-amber-400',
                    ]"
                  >
                    <ArchiveBoxIcon class="h-5 w-5" />
                  </div>
                </div>

                <div class="min-w-0 flex-1">
                  <div class="flex items-center space-x-2">
                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                      {{ backup.name }}
                    </p>
                    <span
                      :class="[
                        'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
                        backup.status === 'completed'
                          ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-800 dark:text-emerald-200'
                          : backup.status === 'failed'
                            ? 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-200'
                            : 'bg-amber-100 text-amber-800 dark:bg-amber-800 dark:text-amber-200',
                      ]"
                    >
                      {{ getStatusText(backup.status) }}
                    </span>
                  </div>

                  <div class="mt-1 flex items-center space-x-4">
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                      {{ formatDateTime(backup.created_at) }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                      {{ formatFileSize(backup.size) }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                      {{ backup.template_count }} templates
                    </p>
                  </div>

                  <p
                    v-if="backup.description"
                    class="mt-1 text-xs text-gray-500 dark:text-gray-400"
                  >
                    {{ backup.description }}
                  </p>
                </div>
              </div>

              <div class="flex items-center space-x-2">
                <button
                  class="text-gray-600 transition-colors hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-400"
                  title="Lihat Detail"
                  @click="viewBackupDetails(backup)"
                >
                  <EyeIcon class="h-4 w-4" />
                </button>

                <button
                  v-if="backup.status === 'completed'"
                  class="text-gray-600 transition-colors hover:text-emerald-600 dark:text-gray-400 dark:hover:text-emerald-400"
                  title="Download"
                  @click="downloadBackup(backup)"
                >
                  <ArrowDownTrayIcon class="h-4 w-4" />
                </button>

                <button
                  v-if="backup.status === 'completed'"
                  class="text-gray-600 transition-colors hover:text-amber-600 dark:text-gray-400 dark:hover:text-amber-400"
                  title="Restore"
                  @click="restoreBackup(backup)"
                >
                  <ArrowUturnUpIcon class="h-4 w-4" />
                </button>

                <button
                  class="text-gray-600 transition-colors hover:text-red-600 dark:text-gray-400 dark:hover:text-red-400"
                  title="Hapus"
                  @click="deleteBackup(backup)"
                >
                  <TrashIcon class="h-4 w-4" />
                </button>
              </div>
            </div>

            <div v-if="filteredBackups.length === 0" class="py-8 text-center">
              <ArchiveBoxIcon class="mx-auto mb-4 h-12 w-12 text-gray-400" />
              <p class="text-gray-500 dark:text-gray-400">
                Tidak ada backup ditemukan
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Backup Detail Modal -->
    <div
      v-if="selectedBackup"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
      @click="selectedBackup = null"
    >
      <div
        class="mx-4 max-h-[80vh] w-full max-w-2xl overflow-y-auto rounded-xl bg-white p-6 dark:bg-gray-800"
        @click.stop
      >
        <div class="mb-6 flex items-center justify-between">
          <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
            Detail Backup
          </h3>
          <button
            class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300"
            @click="selectedBackup = null"
          >
            <XMarkIcon class="h-6 w-6" />
          </button>
        </div>

        <div class="space-y-6">
          <!-- Basic Information -->
          <div>
            <h4 class="mb-3 font-medium text-gray-900 dark:text-gray-100">
              Informasi Dasar
            </h4>
            <div class="space-y-3 rounded-lg bg-gray-50 p-4 dark:bg-gray-700">
              <div class="flex justify-between">
                <span class="text-sm text-gray-600 dark:text-gray-400">Nama:</span>
                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                  {{ selectedBackup.name }}
                </span>
              </div>
              <div class="flex justify-between">
                <span class="text-sm text-gray-600 dark:text-gray-400">Tanggal:</span>
                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                  {{ formatDateTime(selectedBackup.created_at) }}
                </span>
              </div>
              <div class="flex justify-between">
                <span class="text-sm text-gray-600 dark:text-gray-400">Ukuran:</span>
                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                  {{ formatFileSize(selectedBackup.size) }}
                </span>
              </div>
              <div class="flex justify-between">
                <span class="text-sm text-gray-600 dark:text-gray-400">Template:</span>
                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                  {{ selectedBackup.template_count }}
                </span>
              </div>
              <div class="flex justify-between">
                <span class="text-sm text-gray-600 dark:text-gray-400">Status:</span>
                <span
                  :class="[
                    'text-sm font-medium',
                    selectedBackup.status === 'completed'
                      ? 'text-emerald-600 dark:text-emerald-400'
                      : selectedBackup.status === 'failed'
                        ? 'text-red-600 dark:text-red-400'
                        : 'text-amber-600 dark:text-amber-400',
                  ]"
                >
                  {{ getStatusText(selectedBackup.status) }}
                </span>
              </div>
            </div>
          </div>

          <!-- Backup Contents -->
          <div>
            <h4 class="mb-3 font-medium text-gray-900 dark:text-gray-100">
              Konten Backup
            </h4>
            <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-700">
              <div class="grid grid-cols-2 gap-4">
                <div class="text-center">
                  <div class="text-lg font-bold text-gray-900 dark:text-gray-100">
                    {{ selectedBackup.includes?.templates || 0 }}
                  </div>
                  <div class="text-sm text-gray-600 dark:text-gray-400">
                    Face Templates
                  </div>
                </div>
                <div class="text-center">
                  <div class="text-lg font-bold text-gray-900 dark:text-gray-100">
                    {{ selectedBackup.includes?.settings || 0 }}
                  </div>
                  <div class="text-sm text-gray-600 dark:text-gray-400">
                    Settings
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div
            class="flex justify-end space-x-3 border-t border-gray-200 pt-4 dark:border-gray-600"
          >
            <button
              v-if="selectedBackup.status === 'completed'"
              class="rounded-lg bg-emerald-50 px-4 py-2 text-sm font-medium text-emerald-600 transition-colors hover:bg-emerald-100 dark:bg-emerald-900/30 dark:text-emerald-400 dark:hover:bg-emerald-900/50"
              @click="downloadBackup(selectedBackup)"
            >
              Download
            </button>
            <button
              v-if="selectedBackup.status === 'completed'"
              class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-amber-700"
              @click="restoreBackup(selectedBackup)"
            >
              Restore
            </button>
            <button
              class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
              @click="selectedBackup = null"
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
  CircleStackIcon,
  ArchiveBoxIcon,
  ClockIcon,
  MagnifyingGlassIcon,
  ArrowPathIcon,
  EyeIcon,
  ArrowDownTrayIcon,
  ArrowUturnUpIcon,
  TrashIcon,
  XMarkIcon,
} from '@heroicons/vue/24/outline'

// Reactive state
const creatingBackup = ref(false)
const backupProgress = ref(0)
const loading = ref(false)
const savingSchedule = ref(false)
const selectedBackup = ref(null)
const searchQuery = ref('')

const backupForm = reactive({
  name: '',
  description: '',
  type: 'full',
  selectedTemplates: [],
  encrypt: false,
  password: '',
})

const scheduleSettings = reactive({
  enabled: false,
  frequency: 'daily',
  time: '02:00',
  maxBackups: 7,
})

const statistics = reactive({
  totalTemplates: 245,
  storageUsed: 1024 * 1024 * 250, // 250MB
  totalBackups: 12,
  lastBackup: new Date(Date.now() - 24 * 60 * 60 * 1000), // 1 day ago
})

const availableTemplates = ref([
  { id: 1, employee_name: 'John Doe', employee_id: 'EMP001' },
  { id: 2, employee_name: 'Jane Smith', employee_id: 'EMP002' },
  { id: 3, employee_name: 'Mike Johnson', employee_id: 'EMP003' },
  { id: 4, employee_name: 'Sarah Wilson', employee_id: 'EMP004' },
])

const backups = ref([
  {
    id: 1,
    name: 'Full Backup - January 2024',
    description: 'Complete backup of all face templates',
    type: 'full',
    status: 'completed',
    created_at: new Date('2024-01-15T02:00:00'),
    size: 1024 * 1024 * 45, // 45MB
    template_count: 245,
    includes: {
      templates: 245,
      settings: 1,
    },
  },
  {
    id: 2,
    name: 'Emergency Backup - New Employees',
    description: 'Backup for newly enrolled employees',
    type: 'selective',
    status: 'completed',
    created_at: new Date('2024-01-10T14:30:00'),
    size: 1024 * 1024 * 12, // 12MB
    template_count: 15,
    includes: {
      templates: 15,
      settings: 0,
    },
  },
  {
    id: 3,
    name: 'Settings Backup',
    description: 'System configuration backup',
    type: 'settings',
    status: 'completed',
    created_at: new Date('2024-01-08T09:15:00'),
    size: 1024 * 256, // 256KB
    template_count: 0,
    includes: {
      templates: 0,
      settings: 1,
    },
  },
  {
    id: 4,
    name: 'Failed Backup Attempt',
    description: 'Backup failed due to insufficient storage',
    type: 'full',
    status: 'failed',
    created_at: new Date('2024-01-05T02:00:00'),
    size: 0,
    template_count: 0,
    includes: {
      templates: 0,
      settings: 0,
    },
  },
])

// Computed
const filteredBackups = computed(() => {
  if (!searchQuery.value) {return backups.value}

  return backups.value.filter(
    (backup) =>
      backup.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      backup.description?.toLowerCase().includes(searchQuery.value.toLowerCase())
  )
})

// Methods
const formatFileSize = (bytes) => {
  if (bytes === 0) {return '0 Bytes'}
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

const formatDateTime = (date) => {
  return new Intl.DateTimeFormat('id-ID', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }).format(date)
}

const formatTimeAgo = (date) => {
  const now = new Date()
  const diff = now - date
  const hours = Math.floor(diff / (60 * 60 * 1000))
  const days = Math.floor(hours / 24)

  if (days > 0) {return `${days} hari`}
  if (hours > 0) {return `${hours} jam`}
  return 'Baru saja'
}

const getStatusText = (status) => {
  const texts = {
    completed: 'Selesai',
    failed: 'Gagal',
    processing: 'Proses',
  }
  return texts[status] || status
}

const createBackup = async () => {
  creatingBackup.value = true
  backupProgress.value = 0

  try {
    // Simulate backup process with progress
    const progressInterval = setInterval(() => {
      backupProgress.value += Math.random() * 10
      if (backupProgress.value >= 100) {
        clearInterval(progressInterval)
        backupProgress.value = 100
      }
    }, 200)

    // Simulate API call
    await new Promise((resolve) => setTimeout(resolve, 3000))

    // Add new backup to list
    const newBackup = {
      id: Date.now(),
      name: backupForm.name,
      description: backupForm.description,
      type: backupForm.type,
      status: 'completed',
      created_at: new Date(),
      size: backupForm.type === 'full' ? 1024 * 1024 * 50 : 1024 * 1024 * 15,
      template_count:
        backupForm.type === 'full'
          ? 245
          : backupForm.type === 'selective'
            ? backupForm.selectedTemplates.length
            : 0,
      includes: {
        templates:
          backupForm.type === 'full'
            ? 245
            : backupForm.type === 'selective'
              ? backupForm.selectedTemplates.length
              : 0,
        settings: backupForm.type === 'settings' ? 1 : 0,
      },
    }

    backups.value.unshift(newBackup)

    // Update statistics
    statistics.totalBackups++
    statistics.lastBackup = new Date()

    // Reset form
    Object.assign(backupForm, {
      name: '',
      description: '',
      type: 'full',
      selectedTemplates: [],
      encrypt: false,
      password: '',
    })

    alert('Backup berhasil dibuat!')
  } catch (error) {
    console.error('Backup creation failed:', error)
    alert('Gagal membuat backup. Silakan coba lagi.')
  } finally {
    creatingBackup.value = false
    backupProgress.value = 0
  }
}

const saveScheduleSettings = async () => {
  savingSchedule.value = true

  try {
    // Simulate API call
    await new Promise((resolve) => setTimeout(resolve, 1000))

    console.log('Schedule settings saved:', scheduleSettings)
    alert('Pengaturan backup otomatis berhasil disimpan!')
  } catch (error) {
    console.error('Failed to save schedule settings:', error)
    alert('Gagal menyimpan pengaturan.')
  } finally {
    savingSchedule.value = false
  }
}

const refreshBackups = async () => {
  loading.value = true

  try {
    // Simulate API call
    await new Promise((resolve) => setTimeout(resolve, 1000))

    console.log('Backups refreshed')
  } catch (error) {
    console.error('Failed to refresh backups:', error)
  } finally {
    loading.value = false
  }
}

const viewBackupDetails = (backup) => {
  selectedBackup.value = backup
}

const downloadBackup = async (backup) => {
  try {
    // Simulate download
    console.log('Downloading backup:', backup.name)

    // Create a dummy file for download
    const data = JSON.stringify(
      {
        name: backup.name,
        created_at: backup.created_at,
        type: backup.type,
        template_count: backup.template_count,
      },
      null,
      2
    )

    const blob = new Blob([data], { type: 'application/json' })
    const url = URL.createObjectURL(blob)

    const a = document.createElement('a')
    a.href = url
    a.download = `${backup.name.replace(/\s+/g, '_')}_backup.json`
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
    URL.revokeObjectURL(url)
  } catch (error) {
    console.error('Download failed:', error)
    alert('Gagal mengunduh backup.')
  }
}

const restoreBackup = async (backup) => {
  if (
    !confirm(
      `Apakah Anda yakin ingin restore backup "${backup.name}"? Ini akan menimpa data yang ada.`
    )
  ) {
    return
  }

  try {
    // Simulate restore process
    console.log('Restoring backup:', backup.name)

    // Show progress (in real implementation, this would be handled differently)
    const progressDialog = confirm('Restore sedang berlangsung... Klik OK untuk melanjutkan.')

    if (progressDialog) {
      await new Promise((resolve) => setTimeout(resolve, 2000))
      alert('Backup berhasil di-restore!')
    }
  } catch (error) {
    console.error('Restore failed:', error)
    alert('Gagal restore backup.')
  }
}

const deleteBackup = async (backup) => {
  if (!confirm(`Apakah Anda yakin ingin menghapus backup "${backup.name}"?`)) {
    return
  }

  try {
    // Simulate API call
    await new Promise((resolve) => setTimeout(resolve, 500))

    // Remove from list
    const index = backups.value.findIndex((b) => b.id === backup.id)
    if (index > -1) {
      backups.value.splice(index, 1)
    }

    // Update statistics
    statistics.totalBackups--

    if (selectedBackup.value?.id === backup.id) {
      selectedBackup.value = null
    }

    alert('Backup berhasil dihapus!')
  } catch (error) {
    console.error('Delete failed:', error)
    alert('Gagal menghapus backup.')
  }
}

// Lifecycle
onMounted(() => {
  // Load initial data
  console.log('Backup & Restore component mounted')
})
</script>

<style scoped>
.backup-restore-container {
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

/* Progress bar animation */
.transition-all {
  transition: all 0.3s ease-in-out;
}

/* Toggle switch animation */
.peer-checked:after {
  transform: translateX(100%);
}

/* Hover effects */
.transition-colors {
  transition:
    color 0.15s ease-in-out,
    background-color 0.15s ease-in-out;
}
</style>
