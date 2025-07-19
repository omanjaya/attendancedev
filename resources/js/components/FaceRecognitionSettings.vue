<template>
  <div class="face-recognition-settings-container">
    <!-- Header -->
    <div class="mb-8">
      <h2 class="mb-2 text-2xl font-bold text-gray-900 dark:text-gray-100">
        Pengaturan Face Recognition
      </h2>
      <p class="text-gray-600 dark:text-gray-300">
        Kelola konfigurasi sistem pengenalan wajah dan keamanan
      </p>
    </div>

    <!-- Settings Categories -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
      <!-- Settings Navigation -->
      <div class="lg:col-span-1">
        <div
          class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800"
        >
          <h3 class="mb-4 font-semibold text-gray-900 dark:text-gray-100">
            Kategori Pengaturan
          </h3>

          <nav class="space-y-2">
            <button
              v-for="category in settingsCategories"
              :key="category.id"
              :class="[
                'flex w-full items-center rounded-lg px-3 py-2 text-left text-sm font-medium transition-colors',
                activeCategory === category.id
                  ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400'
                  : 'text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700',
              ]"
              @click="activeCategory = category.id"
            >
              <component :is="category.icon" class="mr-3 h-5 w-5 flex-shrink-0" />
              {{ category.name }}
            </button>
          </nav>
        </div>
      </div>

      <!-- Settings Content -->
      <div class="lg:col-span-2">
        <!-- Detection Settings -->
        <div v-if="activeCategory === 'detection'" class="space-y-6">
          <div
            class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800"
          >
            <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100">
              Pengaturan Deteksi
            </h3>

            <div class="space-y-4">
              <!-- Detection Method -->
              <div>
                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                  Metode Deteksi
                </label>
                <select
                  v-model="settings.detection.method"
                  class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-700"
                >
                  <option value="face-api">
                    Face-API.js
                  </option>
                  <option value="mediapipe">
                    MediaPipe
                  </option>
                  <option value="auto">
                    Otomatis (Terbaik)
                  </option>
                </select>
              </div>

              <!-- Confidence Threshold -->
              <div>
                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                  Ambang Batas Kepercayaan: {{ settings.detection.confidence }}%
                </label>
                <input
                  v-model="settings.detection.confidence"
                  type="range"
                  min="50"
                  max="100"
                  step="1"
                  class="h-2 w-full cursor-pointer appearance-none rounded-lg bg-gray-200 dark:bg-gray-700"
                >
                <div class="mt-1 flex justify-between text-xs text-gray-500 dark:text-gray-400">
                  <span>50%</span>
                  <span>75%</span>
                  <span>100%</span>
                </div>
              </div>

              <!-- Detection Interval -->
              <div>
                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                  Interval Deteksi (ms): {{ settings.detection.interval }}
                </label>
                <input
                  v-model="settings.detection.interval"
                  type="range"
                  min="100"
                  max="2000"
                  step="100"
                  class="h-2 w-full cursor-pointer appearance-none rounded-lg bg-gray-200 dark:bg-gray-700"
                >
                <div class="mt-1 flex justify-between text-xs text-gray-500 dark:text-gray-400">
                  <span>100ms</span>
                  <span>1000ms</span>
                  <span>2000ms</span>
                </div>
              </div>

              <!-- Max Detection Time -->
              <div>
                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                  Waktu Maksimal Deteksi (detik)
                </label>
                <input
                  v-model="settings.detection.maxDetectionTime"
                  type="number"
                  min="5"
                  max="60"
                  class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-700"
                >
              </div>

              <!-- Face Size Requirements -->
              <div>
                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                  Ukuran Wajah Minimum (px)
                </label>
                <input
                  v-model="settings.detection.minFaceSize"
                  type="number"
                  min="50"
                  max="500"
                  class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-700"
                >
              </div>
            </div>
          </div>
        </div>

        <!-- Liveness Settings -->
        <div v-if="activeCategory === 'liveness'" class="space-y-6">
          <div
            class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800"
          >
            <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100">
              Pengaturan Liveness Detection
            </h3>

            <div class="space-y-4">
              <!-- Enable Liveness -->
              <div class="flex items-center justify-between">
                <div>
                  <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    Aktifkan Liveness Detection
                  </label>
                  <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Verifikasi keaktifan untuk mencegah spoofing
                  </p>
                </div>
                <label class="relative inline-flex cursor-pointer items-center">
                  <input v-model="settings.liveness.enabled" type="checkbox" class="peer sr-only">
                  <div
                    class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-emerald-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-emerald-300 dark:border-gray-600 dark:bg-gray-700 dark:peer-focus:ring-emerald-800"
                  />
                </label>
              </div>

              <!-- Required Gestures -->
              <div v-if="settings.liveness.enabled">
                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                  Jumlah Gerakan Wajib
                </label>
                <input
                  v-model="settings.liveness.requiredGestures"
                  type="number"
                  min="1"
                  max="5"
                  class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-700"
                >
              </div>

              <!-- Liveness Timeout -->
              <div v-if="settings.liveness.enabled">
                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                  Timeout Liveness (detik)
                </label>
                <input
                  v-model="settings.liveness.timeout"
                  type="number"
                  min="10"
                  max="60"
                  class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-700"
                >
              </div>

              <!-- Enabled Gestures -->
              <div v-if="settings.liveness.enabled">
                <label class="mb-3 block text-sm font-medium text-gray-700 dark:text-gray-300">
                  Gerakan yang Diaktifkan
                </label>
                <div class="space-y-2">
                  <div
                    v-for="gesture in availableGestures"
                    :key="gesture.id"
                    class="flex items-center space-x-3 rounded-lg bg-gray-50 p-3 dark:bg-gray-700"
                  >
                    <input
                      v-model="settings.liveness.enabledGestures"
                      :value="gesture.id"
                      type="checkbox"
                      class="h-4 w-4 rounded border-gray-300 bg-gray-100 text-emerald-600 focus:ring-2 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-emerald-600"
                    >
                    <div class="flex-1">
                      <div class="flex items-center">
                        <span class="mr-2 text-lg">{{ gesture.icon }}</span>
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                          {{ gesture.name }}
                        </label>
                      </div>
                      <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ gesture.description }}
                      </p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Security Settings -->
        <div v-if="activeCategory === 'security'" class="space-y-6">
          <div
            class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800"
          >
            <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100">
              Pengaturan Keamanan
            </h3>

            <div class="space-y-4">
              <!-- Face Data Encryption -->
              <div class="flex items-center justify-between">
                <div>
                  <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    Enkripsi Data Wajah
                  </label>
                  <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Enkripsi template wajah yang tersimpan
                  </p>
                </div>
                <label class="relative inline-flex cursor-pointer items-center">
                  <input
                    v-model="settings.security.encryptFaceData"
                    type="checkbox"
                    class="peer sr-only"
                  >
                  <div
                    class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-emerald-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-emerald-300 dark:border-gray-600 dark:bg-gray-700 dark:peer-focus:ring-emerald-800"
                  />
                </label>
              </div>

              <!-- Audit Logging -->
              <div class="flex items-center justify-between">
                <div>
                  <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    Audit Logging
                  </label>
                  <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Catat semua aktivitas face recognition
                  </p>
                </div>
                <label class="relative inline-flex cursor-pointer items-center">
                  <input
                    v-model="settings.security.auditLogging"
                    type="checkbox"
                    class="peer sr-only"
                  >
                  <div
                    class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-emerald-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-emerald-300 dark:border-gray-600 dark:bg-gray-700 dark:peer-focus:ring-emerald-800"
                  />
                </label>
              </div>

              <!-- Failed Attempts Limit -->
              <div>
                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                  Batas Percobaan Gagal
                </label>
                <input
                  v-model="settings.security.maxFailedAttempts"
                  type="number"
                  min="1"
                  max="10"
                  class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-700"
                >
              </div>

              <!-- Session Timeout -->
              <div>
                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                  Timeout Sesi (menit)
                </label>
                <input
                  v-model="settings.security.sessionTimeout"
                  type="number"
                  min="5"
                  max="60"
                  class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-700"
                >
              </div>

              <!-- Data Retention -->
              <div>
                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                  Retensi Data (hari)
                </label>
                <input
                  v-model="settings.security.dataRetention"
                  type="number"
                  min="30"
                  max="365"
                  class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-700"
                >
              </div>
            </div>
          </div>
        </div>

        <!-- Performance Settings -->
        <div v-if="activeCategory === 'performance'" class="space-y-6">
          <div
            class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800"
          >
            <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100">
              Pengaturan Performa
            </h3>

            <div class="space-y-4">
              <!-- Processing Quality -->
              <div>
                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                  Kualitas Pemrosesan
                </label>
                <select
                  v-model="settings.performance.processingQuality"
                  class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-700"
                >
                  <option value="low">
                    Rendah (Cepat)
                  </option>
                  <option value="medium">
                    Sedang (Seimbang)
                  </option>
                  <option value="high">
                    Tinggi (Akurat)
                  </option>
                </select>
              </div>

              <!-- Concurrent Processing -->
              <div>
                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                  Maksimal Pemrosesan Bersamaan
                </label>
                <input
                  v-model="settings.performance.maxConcurrentProcessing"
                  type="number"
                  min="1"
                  max="10"
                  class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-700"
                >
              </div>

              <!-- Cache Settings -->
              <div class="flex items-center justify-between">
                <div>
                  <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    Cache Template Wajah
                  </label>
                  <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Simpan template di memori untuk performa lebih baik
                  </p>
                </div>
                <label class="relative inline-flex cursor-pointer items-center">
                  <input
                    v-model="settings.performance.cacheFaceTemplates"
                    type="checkbox"
                    class="peer sr-only"
                  >
                  <div
                    class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-emerald-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-emerald-300 dark:border-gray-600 dark:bg-gray-700 dark:peer-focus:ring-emerald-800"
                  />
                </label>
              </div>

              <!-- GPU Acceleration -->
              <div class="flex items-center justify-between">
                <div>
                  <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    Akselerasi GPU
                  </label>
                  <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Gunakan GPU untuk pemrosesan lebih cepat
                  </p>
                </div>
                <label class="relative inline-flex cursor-pointer items-center">
                  <input
                    v-model="settings.performance.gpuAcceleration"
                    type="checkbox"
                    class="peer sr-only"
                  >
                  <div
                    class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-emerald-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-emerald-300 dark:border-gray-600 dark:bg-gray-700 dark:peer-focus:ring-emerald-800"
                  />
                </label>
              </div>

              <!-- Memory Limit -->
              <div>
                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                  Batas Memori (MB)
                </label>
                <input
                  v-model="settings.performance.memoryLimit"
                  type="number"
                  min="128"
                  max="2048"
                  step="128"
                  class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-700"
                >
              </div>
            </div>
          </div>
        </div>

        <!-- Camera Settings -->
        <div v-if="activeCategory === 'camera'" class="space-y-6">
          <div
            class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800"
          >
            <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100">
              Pengaturan Kamera
            </h3>

            <div class="space-y-4">
              <!-- Camera Resolution -->
              <div>
                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                  Resolusi Kamera
                </label>
                <select
                  v-model="settings.camera.resolution"
                  class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-700"
                >
                  <option value="480p">
                    480p (640x480)
                  </option>
                  <option value="720p">
                    720p (1280x720)
                  </option>
                  <option value="1080p">
                    1080p (1920x1080)
                  </option>
                </select>
              </div>

              <!-- Frame Rate -->
              <div>
                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                  Frame Rate (FPS)
                </label>
                <select
                  v-model="settings.camera.frameRate"
                  class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-700"
                >
                  <option value="15">
                    15 FPS
                  </option>
                  <option value="24">
                    24 FPS
                  </option>
                  <option value="30">
                    30 FPS
                  </option>
                </select>
              </div>

              <!-- Auto Focus -->
              <div class="flex items-center justify-between">
                <div>
                  <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    Auto Focus
                  </label>
                  <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Fokus otomatis untuk kualitas gambar optimal
                  </p>
                </div>
                <label class="relative inline-flex cursor-pointer items-center">
                  <input v-model="settings.camera.autoFocus" type="checkbox" class="peer sr-only">
                  <div
                    class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-emerald-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-emerald-300 dark:border-gray-600 dark:bg-gray-700 dark:peer-focus:ring-emerald-800"
                  />
                </label>
              </div>

              <!-- Mirror Mode -->
              <div class="flex items-center justify-between">
                <div>
                  <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    Mode Mirror
                  </label>
                  <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Tampilkan gambar kamera secara terbalik
                  </p>
                </div>
                <label class="relative inline-flex cursor-pointer items-center">
                  <input
                    v-model="settings.camera.mirrorMode"
                    type="checkbox"
                    class="peer sr-only"
                  >
                  <div
                    class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-emerald-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-emerald-300 dark:border-gray-600 dark:bg-gray-700 dark:peer-focus:ring-emerald-800"
                  />
                </label>
              </div>

              <!-- Brightness -->
              <div>
                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                  Kecerahan: {{ settings.camera.brightness }}%
                </label>
                <input
                  v-model="settings.camera.brightness"
                  type="range"
                  min="0"
                  max="100"
                  step="5"
                  class="h-2 w-full cursor-pointer appearance-none rounded-lg bg-gray-200 dark:bg-gray-700"
                >
              </div>

              <!-- Contrast -->
              <div>
                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                  Kontras: {{ settings.camera.contrast }}%
                </label>
                <input
                  v-model="settings.camera.contrast"
                  type="range"
                  min="0"
                  max="100"
                  step="5"
                  class="h-2 w-full cursor-pointer appearance-none rounded-lg bg-gray-200 dark:bg-gray-700"
                >
              </div>
            </div>
          </div>
        </div>

        <!-- System Status -->
        <div v-if="activeCategory === 'system'" class="space-y-6">
          <div
            class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800"
          >
            <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100">
              Status Sistem
            </h3>

            <div class="space-y-4">
              <!-- System Health -->
              <div class="grid grid-cols-2 gap-4">
                <div class="rounded-lg bg-emerald-50 p-4 dark:bg-emerald-900/30">
                  <div class="flex items-center justify-between">
                    <div>
                      <p class="text-sm font-medium text-emerald-600 dark:text-emerald-400">
                        Status Deteksi
                      </p>
                      <p class="text-lg font-bold text-emerald-900 dark:text-emerald-100">
                        {{ systemStatus.detectionStatus }}
                      </p>
                    </div>
                    <div
                      class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-500"
                    >
                      <CheckCircleIcon class="h-5 w-5 text-white" />
                    </div>
                  </div>
                </div>

                <div class="rounded-lg bg-blue-50 p-4 dark:bg-blue-900/30">
                  <div class="flex items-center justify-between">
                    <div>
                      <p class="text-sm font-medium text-blue-600 dark:text-blue-400">
                        Template Tersimpan
                      </p>
                      <p class="text-lg font-bold text-blue-900 dark:text-blue-100">
                        {{ systemStatus.storedTemplates }}
                      </p>
                    </div>
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-500">
                      <FaceSmileIcon class="h-5 w-5 text-white" />
                    </div>
                  </div>
                </div>
              </div>

              <!-- Performance Metrics -->
              <div class="space-y-3">
                <div class="flex items-center justify-between">
                  <span class="text-sm text-gray-600 dark:text-gray-400">CPU Usage</span>
                  <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                    {{ systemStatus.cpuUsage }}%
                  </span>
                </div>
                <div class="h-2 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                  <div
                    :class="[
                      'h-2 rounded-full transition-all duration-500',
                      systemStatus.cpuUsage > 80
                        ? 'bg-red-500'
                        : systemStatus.cpuUsage > 60
                          ? 'bg-amber-500'
                          : 'bg-emerald-500',
                    ]"
                    :style="{ width: `${systemStatus.cpuUsage}%` }"
                  />
                </div>

                <div class="flex items-center justify-between">
                  <span class="text-sm text-gray-600 dark:text-gray-400">Memory Usage</span>
                  <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                    {{ systemStatus.memoryUsage }}%
                  </span>
                </div>
                <div class="h-2 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                  <div
                    :class="[
                      'h-2 rounded-full transition-all duration-500',
                      systemStatus.memoryUsage > 80
                        ? 'bg-red-500'
                        : systemStatus.memoryUsage > 60
                          ? 'bg-amber-500'
                          : 'bg-emerald-500',
                    ]"
                    :style="{ width: `${systemStatus.memoryUsage}%` }"
                  />
                </div>
              </div>

              <!-- System Actions -->
              <div class="flex space-x-3 border-t border-gray-200 pt-4 dark:border-gray-600">
                <button
                  :disabled="clearing"
                  class="flex-1 rounded-lg bg-amber-50 px-4 py-2 text-sm font-medium text-amber-600 transition-colors hover:bg-amber-100 disabled:opacity-50 dark:bg-amber-900/30 dark:text-amber-400 dark:hover:bg-amber-900/50"
                  @click="clearCache"
                >
                  {{ clearing ? 'Clearing...' : 'Clear Cache' }}
                </button>
                <button
                  :disabled="diagnosing"
                  class="flex-1 rounded-lg bg-blue-50 px-4 py-2 text-sm font-medium text-blue-600 transition-colors hover:bg-blue-100 disabled:opacity-50 dark:bg-blue-900/30 dark:text-blue-400 dark:hover:bg-blue-900/50"
                  @click="runDiagnostics"
                >
                  {{ diagnosing ? 'Running...' : 'Run Diagnostics' }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Action Buttons -->
    <div class="mt-8 flex justify-end space-x-3">
      <button
        class="rounded-lg border border-gray-300 bg-white px-6 py-3 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
        @click="resetSettings"
      >
        Reset ke Default
      </button>
      <button
        :disabled="saving"
        class="rounded-lg bg-emerald-600 px-6 py-3 text-sm font-medium text-white transition-colors hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50"
        @click="saveSettings"
      >
        {{ saving ? 'Menyimpan...' : 'Simpan Pengaturan' }}
      </button>
    </div>

    <!-- Success Message -->
    <div v-if="showSuccess" class="fixed right-4 top-4 z-50">
      <div
        class="rounded-lg border border-emerald-200 bg-emerald-100 p-4 shadow-lg dark:border-emerald-700 dark:bg-emerald-800"
      >
        <div class="flex items-center">
          <CheckCircleIcon class="mr-2 h-5 w-5 text-emerald-600 dark:text-emerald-400" />
          <p class="text-sm font-medium text-emerald-900 dark:text-emerald-100">
            Pengaturan berhasil disimpan!
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, watch } from 'vue'
import {
  CameraIcon,
  ShieldCheckIcon,
  Cog6ToothIcon,
  ChartBarIcon,
  FaceSmileIcon,
  CheckCircleIcon,
  ComputerDesktopIcon,
} from '@heroicons/vue/24/outline'

// Reactive state
const activeCategory = ref('detection')
const saving = ref(false)
const clearing = ref(false)
const diagnosing = ref(false)
const showSuccess = ref(false)

const settingsCategories = ref([
  {
    id: 'detection',
    name: 'Deteksi',
    icon: FaceSmileIcon,
  },
  {
    id: 'liveness',
    name: 'Liveness',
    icon: ShieldCheckIcon,
  },
  {
    id: 'security',
    name: 'Keamanan',
    icon: ShieldCheckIcon,
  },
  {
    id: 'performance',
    name: 'Performa',
    icon: ChartBarIcon,
  },
  {
    id: 'camera',
    name: 'Kamera',
    icon: CameraIcon,
  },
  {
    id: 'system',
    name: 'Sistem',
    icon: ComputerDesktopIcon,
  },
])

const settings = reactive({
  detection: {
    method: 'face-api',
    confidence: 75,
    interval: 500,
    maxDetectionTime: 30,
    minFaceSize: 100,
  },
  liveness: {
    enabled: true,
    requiredGestures: 2,
    timeout: 30,
    enabledGestures: ['blink', 'smile', 'turnHead'],
  },
  security: {
    encryptFaceData: true,
    auditLogging: true,
    maxFailedAttempts: 3,
    sessionTimeout: 30,
    dataRetention: 90,
  },
  performance: {
    processingQuality: 'medium',
    maxConcurrentProcessing: 3,
    cacheFaceTemplates: true,
    gpuAcceleration: false,
    memoryLimit: 512,
  },
  camera: {
    resolution: '720p',
    frameRate: 24,
    autoFocus: true,
    mirrorMode: true,
    brightness: 50,
    contrast: 50,
  },
})

const availableGestures = ref([
  {
    id: 'blink',
    name: 'Berkedip',
    icon: '👁️',
    description: 'Berkedip beberapa kali',
  },
  {
    id: 'smile',
    name: 'Tersenyum',
    icon: '😊',
    description: 'Memberikan senyuman',
  },
  {
    id: 'turnHead',
    name: 'Putar Kepala',
    icon: '↔️',
    description: 'Putar kepala ke kiri dan kanan',
  },
  {
    id: 'nod',
    name: 'Angguk',
    icon: '↕️',
    description: 'Anggukkan kepala',
  },
  {
    id: 'openMouth',
    name: 'Buka Mulut',
    icon: '😮',
    description: 'Buka mulut lebar',
  },
])

const systemStatus = reactive({
  detectionStatus: 'Aktif',
  storedTemplates: 245,
  cpuUsage: 45,
  memoryUsage: 67,
})

// Methods
const saveSettings = async () => {
  saving.value = true
  try {
    // Simulate API call
    await new Promise((resolve) => setTimeout(resolve, 1500))

    // In production, save to backend
    console.log('Saving settings:', settings)

    showSuccess.value = true
    setTimeout(() => {
      showSuccess.value = false
    }, 3000)
  } catch (error) {
    console.error('Failed to save settings:', error)
  } finally {
    saving.value = false
  }
}

const resetSettings = async () => {
  if (confirm('Apakah Anda yakin ingin reset ke pengaturan default?')) {
    // Reset to default values
    Object.assign(settings, {
      detection: {
        method: 'face-api',
        confidence: 75,
        interval: 500,
        maxDetectionTime: 30,
        minFaceSize: 100,
      },
      liveness: {
        enabled: true,
        requiredGestures: 2,
        timeout: 30,
        enabledGestures: ['blink', 'smile', 'turnHead'],
      },
      security: {
        encryptFaceData: true,
        auditLogging: true,
        maxFailedAttempts: 3,
        sessionTimeout: 30,
        dataRetention: 90,
      },
      performance: {
        processingQuality: 'medium',
        maxConcurrentProcessing: 3,
        cacheFaceTemplates: true,
        gpuAcceleration: false,
        memoryLimit: 512,
      },
      camera: {
        resolution: '720p',
        frameRate: 24,
        autoFocus: true,
        mirrorMode: true,
        brightness: 50,
        contrast: 50,
      },
    })
  }
}

const clearCache = async () => {
  clearing.value = true
  try {
    await new Promise((resolve) => setTimeout(resolve, 2000))
    console.log('Cache cleared')
  } catch (error) {
    console.error('Failed to clear cache:', error)
  } finally {
    clearing.value = false
  }
}

const runDiagnostics = async () => {
  diagnosing.value = true
  try {
    await new Promise((resolve) => setTimeout(resolve, 3000))
    console.log('Diagnostics completed')
  } catch (error) {
    console.error('Diagnostics failed:', error)
  } finally {
    diagnosing.value = false
  }
}

const loadSettings = async () => {
  try {
    // Simulate loading from backend
    await new Promise((resolve) => setTimeout(resolve, 1000))

    // In production, load from API
    console.log('Settings loaded')
  } catch (error) {
    console.error('Failed to load settings:', error)
  }
}

const updateSystemStatus = () => {
  // Simulate real-time system monitoring
  systemStatus.cpuUsage = Math.max(
    10,
    Math.min(90, systemStatus.cpuUsage + (Math.random() - 0.5) * 10)
  )
  systemStatus.memoryUsage = Math.max(
    20,
    Math.min(95, systemStatus.memoryUsage + (Math.random() - 0.5) * 8)
  )
  systemStatus.storedTemplates = Math.max(
    200,
    systemStatus.storedTemplates + Math.floor(Math.random() * 3)
  )
}

// Watchers
watch(
  () => settings.detection.method,
  (newMethod) => {
    console.log('Detection method changed to:', newMethod)
  }
)

watch(
  () => settings.liveness.enabled,
  (enabled) => {
    if (!enabled) {
      console.log('Liveness detection disabled')
    }
  }
)

// Lifecycle
onMounted(() => {
  loadSettings()

  // Update system status every 5 seconds
  setInterval(updateSystemStatus, 5000)
})
</script>

<style scoped>
.face-recognition-settings-container {
  @apply mx-auto max-w-7xl p-6;
}

/* Custom range slider styles */
input[type='range'] {
  -webkit-appearance: none;
  appearance: none;
}

input[type='range']::-webkit-slider-thumb {
  -webkit-appearance: none;
  appearance: none;
  height: 20px;
  width: 20px;
  border-radius: 50%;
  background: #10b981;
  cursor: pointer;
  border: 2px solid #ffffff;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

input[type='range']::-moz-range-thumb {
  height: 20px;
  width: 20px;
  border-radius: 50%;
  background: #10b981;
  cursor: pointer;
  border: 2px solid #ffffff;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

input[type='range']:focus {
  outline: none;
}

input[type='range']:focus::-webkit-slider-thumb {
  box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);
}

/* Toggle switch animations */
.peer-checked:after {
  transform: translateX(100%);
}

/* Smooth transitions */
.transition-colors {
  transition:
    color 0.15s ease-in-out,
    background-color 0.15s ease-in-out,
    border-color 0.15s ease-in-out;
}
</style>
