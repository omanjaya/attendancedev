<template>
  <div class="face-recognition-container">
    <!-- Camera Preview -->
    <div
      class="relative h-80 w-full overflow-hidden rounded-xl border-4 border-gray-300 bg-black dark:border-gray-600"
      role="region"
      aria-label="Face recognition camera preview"
    >
      <video
        ref="videoElement"
        :class="{ hidden: !faceDetectionStore.cameraActive }"
        class="h-full w-full object-cover"
        autoplay
        muted
        playsinline
        aria-label="Live camera feed for face recognition"
      />

      <canvas
        ref="overlayCanvas"
        :class="{ hidden: !faceDetectionStore.cameraActive }"
        class="pointer-events-none absolute left-0 top-0 h-full w-full"
        aria-label="Face detection overlay"
        role="img"
      />

      <!-- Camera Placeholder -->
      <div
        v-if="!faceDetectionStore.cameraActive"
        class="flex h-full items-center justify-center bg-gradient-to-br from-gray-800 to-gray-900"
      >
        <div class="text-center">
          <div
            class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-white/10 backdrop-blur-sm"
          >
            <CameraIcon class="h-8 w-8 text-white" />
          </div>
          <h3 class="mb-2 text-lg font-semibold text-white">
            {{ faceDetectionStore.cameraStatus.title }}
          </h3>
          <p class="text-sm text-gray-300">
            {{ faceDetectionStore.cameraStatus.message }}
          </p>
        </div>
      </div>

      <!-- Detection Status Overlay -->
      <div
        v-if="faceDetectionStore.cameraActive && faceDetectionStore.detectionStatus"
        class="absolute left-4 right-4 top-4"
        role="status"
        aria-live="polite"
        :aria-label="faceDetectionStore.detectionStatus?.message || ''"
      >
        <div class="rounded-lg bg-black/70 p-3 text-white backdrop-blur-sm">
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-2">
              <div
                :class="[
                  'h-3 w-3 rounded-full',
                  faceDetectionStore.detectionStatus?.type === 'success'
                    ? 'bg-green-500'
                    : faceDetectionStore.detectionStatus?.type === 'error'
                      ? 'bg-red-500'
                      : 'animate-pulse bg-yellow-500',
                ]"
              />
              <span class="text-sm font-medium">{{
                faceDetectionStore.detectionStatus?.message
              }}</span>
            </div>
            <div class="rounded bg-white/20 px-2 py-1 text-xs">
              {{ faceDetectionStore.currentDetection.confidence }}%
            </div>
          </div>
        </div>
      </div>

      <!-- Face Guide Frame -->
      <div
        v-if="faceDetectionStore.cameraActive && faceDetectionStore.showGuide"
        class="pointer-events-none absolute inset-0 flex items-center justify-center"
      >
        <div class="relative">
          <svg
            width="200"
            height="240"
            viewBox="0 0 200 240"
            class="text-white/60"
          >
            <ellipse
              cx="100"
              cy="120"
              rx="80"
              ry="100"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-dasharray="5,5"
              class="animate-pulse"
            />
            <!-- Corner guides -->
            <path
              d="M30 40 L30 20 L50 20"
              stroke="currentColor"
              stroke-width="3"
              fill="none"
            />
            <path
              d="M170 40 L170 20 L150 20"
              stroke="currentColor"
              stroke-width="3"
              fill="none"
            />
            <path
              d="M30 200 L30 220 L50 220"
              stroke="currentColor"
              stroke-width="3"
              fill="none"
            />
            <path
              d="M170 200 L170 220 L150 220"
              stroke="currentColor"
              stroke-width="3"
              fill="none"
            />
          </svg>
          <div
            class="absolute -bottom-8 left-1/2 -translate-x-1/2 transform text-center text-xs text-white"
          >
            Posisikan wajah di dalam frame
          </div>
        </div>
      </div>

      <!-- Detection Method Badge -->
      <div
        class="absolute right-2 top-2 rounded-full bg-purple-600 px-3 py-1 text-xs font-medium text-white"
      >
        {{ detectionMethod === 'face-api' ? 'Face-API.js' : 'MediaPipe' }}
      </div>
    </div>

    <!-- Statistics Cards -->
    <div
      class="mt-6 grid grid-cols-2 gap-4 md:grid-cols-4"
      role="region"
      aria-label="Face detection statistics"
    >
      <div
        class="rounded-lg border border-gray-200 bg-white p-3 text-center dark:border-gray-700 dark:bg-gray-800"
        role="status"
        aria-label="Faces detected count"
      >
        <div class="text-lg font-bold text-emerald-600 dark:text-emerald-400">
          {{ faceDetectionStore.statistics.facesDetected }}
        </div>
        <div class="text-xs text-gray-600 dark:text-gray-400">
          Wajah Terdeteksi
        </div>
      </div>

      <div
        class="rounded-lg border border-gray-200 bg-white p-3 text-center dark:border-gray-700 dark:bg-gray-800"
        role="status"
        aria-label="Average accuracy percentage"
      >
        <div class="text-lg font-bold text-blue-600 dark:text-blue-400">
          {{ faceDetectionStore.statistics.averageConfidence }}%
        </div>
        <div class="text-xs text-gray-600 dark:text-gray-400">
          Akurasi Rata-rata
        </div>
      </div>

      <div
        class="rounded-lg border border-gray-200 bg-white p-3 text-center dark:border-gray-700 dark:bg-gray-800"
        role="status"
        aria-label="Average processing time"
      >
        <div class="text-lg font-bold text-amber-600 dark:text-amber-400">
          {{ faceDetectionStore.statistics.averageProcessingTime }}ms
        </div>
        <div class="text-xs text-gray-600 dark:text-gray-400">
          Waktu Proses
        </div>
      </div>

      <div
        class="rounded-lg border border-gray-200 bg-white p-3 text-center dark:border-gray-700 dark:bg-gray-800"
        role="status"
        aria-label="Liveness detection score"
      >
        <div class="text-lg font-bold text-purple-600 dark:text-purple-400">
          {{ faceDetectionStore.currentDetection.liveness }}%
        </div>
        <div class="text-xs text-gray-600 dark:text-gray-400">
          Liveness Score
        </div>
      </div>
    </div>

    <!-- Control Buttons -->
    <div class="mt-6 flex flex-wrap justify-center gap-3">
      <button
        v-if="!faceDetectionStore.cameraActive"
        :disabled="faceDetectionStore.loading"
        :aria-label="
          faceDetectionStore.loading ? 'Loading camera...' : 'Start camera for face recognition'
        "
        class="inline-flex items-center rounded-lg bg-gradient-to-r from-emerald-500 to-emerald-600 px-6 py-3 text-sm font-medium text-white shadow-sm transition-all duration-200 hover:scale-105 hover:from-emerald-600 hover:to-emerald-700 disabled:opacity-50"
        @click="startCamera"
      >
        <VideoCameraIcon class="mr-2 h-5 w-5" />
        {{ faceDetectionStore.loading ? 'Memuat...' : 'Mulai Kamera' }}
      </button>

      <button
        v-if="faceDetectionStore.cameraActive"
        aria-label="Stop camera"
        class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-6 py-3 text-sm font-medium text-gray-700 transition-all duration-200 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
        @click="stopCamera"
      >
        <StopIcon class="mr-2 h-5 w-5" />
        Hentikan
      </button>

      <button
        v-if="faceDetectionStore.cameraActive && canCapture"
        :disabled="faceDetectionStore.processing"
        :aria-label="
          faceDetectionStore.processing
            ? 'Processing face capture...'
            : 'Capture and process face for attendance'
        "
        class="inline-flex items-center rounded-lg bg-gradient-to-r from-purple-500 to-purple-600 px-6 py-3 text-sm font-medium text-white shadow-sm transition-all duration-200 hover:scale-105 hover:from-purple-600 hover:to-purple-700 disabled:opacity-50"
        @click="captureAndProcess"
      >
        <CameraIcon class="mr-2 h-5 w-5" />
        {{ faceDetectionStore.processing ? 'Memproses...' : 'Ambil Foto & Proses' }}
      </button>

      <button
        aria-label="Simulate attendance for demo purposes"
        class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-6 py-3 text-sm font-medium text-gray-700 transition-all duration-200 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
        @click="$emit('simulate-attendance')"
      >
        <BoltIcon class="mr-2 h-5 w-5" />
        Simulasi (Demo)
      </button>
    </div>

    <!-- Instructions -->
    <div
      class="mt-6 rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-700/50 dark:bg-blue-900/30"
    >
      <h4 class="mb-2 flex items-center font-semibold text-blue-900 dark:text-blue-100">
        <InformationCircleIcon class="mr-2 h-5 w-5" />
        Petunjuk Penggunaan
      </h4>
      <div class="space-y-1 text-sm text-blue-700 dark:text-blue-300">
        <p>📱 Pastikan pencahayaan cukup terang dan wajah terlihat jelas</p>
        <p>👤 Posisikan wajah di tengah frame dan hindari gerakan berlebihan</p>
        <p>🔒 Sistem akan melakukan verifikasi liveness untuk keamanan ekstra</p>
        <p>⚡ Proses deteksi otomatis akan berjalan setelah kamera aktif</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue'
import type { Ref } from 'vue'
import {
  CameraIcon,
  VideoCameraIcon,
  StopIcon,
  BoltIcon,
  InformationCircleIcon,
} from '@heroicons/vue/24/outline'
import { useFaceDetectionStore } from '@/stores/faceDetection'
import type {
  FaceRecognitionProps,
  FaceRecognitionEmits,
  DetectionMethod,
} from '@/types/face-recognition'

// Props
const props = withDefaults(defineProps<FaceRecognitionProps>(), {
  detectionMethod: 'face-api',
  employeeId: '',
  confidenceThreshold: 70,
  livenessThreshold: 60,
})

// Emits
const emit = defineEmits<FaceRecognitionEmits>()

// Store
const faceDetectionStore = useFaceDetectionStore()

// Refs
const videoElement: Ref<HTMLVideoElement | null> = ref(null)
const overlayCanvas: Ref<HTMLCanvasElement | null> = ref(null)

// Computed
const canCapture = computed<boolean>(() => {
  return faceDetectionStore.canCapture
})

const detectionMethod = computed(() => {
  return faceDetectionStore.settings.detectionMethod
})

// Face detection models
const faceApiModel: any = null
const mediaPipeModel: any = null

// Methods
const startCamera = async () => {
  try {
    const stream = await faceDetectionStore.startCamera()

    if (videoElement.value) {
      videoElement.value.srcObject = stream

      // Wait for video to be ready
      await new Promise((resolve) => {
        if (videoElement.value) {
          videoElement.value.onloadedmetadata = resolve
        }
      })
    }

    // Start detection
    startFaceDetection()

    // Initialize detection model if needed
    await initializeDetectionModel()

    emit('face-detected', { status: 'camera-started' })
  } catch (error: any) {
    console.error('Camera access error:', error)
    emit('error', { type: 'camera', message: error.message })
  }
}

const stopCamera = () => {
  faceDetectionStore.stopCamera()

  // Clear canvas
  if (overlayCanvas.value) {
    const ctx = overlayCanvas.value.getContext('2d')
    if (ctx) {
      ctx.clearRect(0, 0, overlayCanvas.value.width, overlayCanvas.value.height)
    }
  }
}

const startFaceDetection = () => {
  faceDetectionStore.startDetection(performFaceDetection, 500)
}

const performFaceDetection = async () => {
  if (!faceDetectionStore.cameraActive || !videoElement.value) {return}

  const startTime = performance.now()

  try {
    let detection

    if (faceDetectionStore.settings.detectionMethod === 'face-api') {
      detection = await detectWithFaceAPI()
    } else {
      detection = await detectWithMediaPipe()
    }

    const processingTime = Math.round(performance.now() - startTime)

    if (detection.faceDetected) {
      faceDetectionStore.setDetectionStatus({
        type: 'success',
        message: `Wajah terdeteksi (${detection.confidence}%)`,
      })

      faceDetectionStore.updateCurrentDetection({
        confidence: detection.confidence,
        liveness: detection.liveness,
        boundingBox: detection.boundingBox,
      })

      faceDetectionStore.updateStatistics({
        faceDetected: true,
        confidence: detection.confidence,
        processingTime,
      })

      drawFaceBox(detection.boundingBox)

      emit('face-detected', {
        status: 'detected',
        confidence: detection.confidence,
        liveness: detection.liveness,
        processingTime,
      })
    } else {
      faceDetectionStore.setDetectionStatus({
        type: 'searching',
        message: 'Mencari wajah...',
      })

      faceDetectionStore.updateCurrentDetection({
        confidence: 0,
        liveness: 0,
        boundingBox: null,
      })

      clearFaceBox()
    }
  } catch (error: any) {
    const detectionError = error instanceof Error ? error : new Error(String(error))

    errorTracking.captureError(detectionError, {
      action: 'face_detection_failed',
      metadata: {
        detectionMethod: faceDetectionStore.settings.detectionMethod,
        cameraActive: faceDetectionStore.cameraActive,
        videoElementExists: !!videoElement.value,
      },
    })

    console.error('Face detection error:', detectionError)
    faceDetectionStore.setDetectionStatus({
      type: 'error',
      message: 'Error deteksi wajah',
    })
    faceDetectionStore.addError(`Detection failed: ${detectionError.message}`)
  }
}

const detectWithFaceAPI = async () => {
  // Simulate Face-API.js detection
  // In real implementation, this would use the actual Face-API.js library
  const faceDetected = Math.random() > 0.3
  const confidence = faceDetected ? Math.floor(75 + Math.random() * 20) : 0
  const liveness = faceDetected ? Math.floor(80 + Math.random() * 15) : 0

  return {
    faceDetected,
    confidence,
    liveness,
    boundingBox: faceDetected ? generateRandomBoundingBox() : null,
  }
}

const detectWithMediaPipe = async () => {
  // Simulate MediaPipe detection
  // In real implementation, this would use the actual MediaPipe library
  const faceDetected = Math.random() > 0.25
  const confidence = faceDetected ? Math.floor(80 + Math.random() * 15) : 0
  const liveness = faceDetected ? Math.floor(85 + Math.random() * 10) : 0

  return {
    faceDetected,
    confidence,
    liveness,
    boundingBox: faceDetected ? generateRandomBoundingBox() : null,
  }
}

const generateRandomBoundingBox = () => {
  const video = videoElement.value
  if (!video) {return null}

  const videoWidth = video.videoWidth || video.offsetWidth
  const videoHeight = video.videoHeight || video.offsetHeight

  return {
    x: videoWidth * 0.3 + (Math.random() - 0.5) * 40,
    y: videoHeight * 0.25 + (Math.random() - 0.5) * 30,
    width: videoWidth * 0.25 + Math.random() * 20,
    height: videoHeight * 0.35 + Math.random() * 20,
  }
}

const drawFaceBox = (boundingBox) => {
  if (!boundingBox || !overlayCanvas.value) {return}

  const canvas = overlayCanvas.value
  const video = videoElement.value

  canvas.width = video.videoWidth || video.offsetWidth
  canvas.height = video.videoHeight || video.offsetHeight

  const ctx = canvas.getContext('2d')
  ctx.clearRect(0, 0, canvas.width, canvas.height)

  // Draw face bounding box
  ctx.strokeStyle = '#10B981' // Emerald green
  ctx.lineWidth = 3
  ctx.setLineDash([5, 5])
  ctx.strokeRect(boundingBox.x, boundingBox.y, boundingBox.width, boundingBox.height)

  // Draw corner indicators
  const cornerSize = 20
  ctx.setLineDash([])
  ctx.lineWidth = 4
  ctx.strokeStyle = '#FFFFFF'

  // Top-left corner
  ctx.beginPath()
  ctx.moveTo(boundingBox.x, boundingBox.y + cornerSize)
  ctx.lineTo(boundingBox.x, boundingBox.y)
  ctx.lineTo(boundingBox.x + cornerSize, boundingBox.y)
  ctx.stroke()

  // Top-right corner
  ctx.beginPath()
  ctx.moveTo(boundingBox.x + boundingBox.width - cornerSize, boundingBox.y)
  ctx.lineTo(boundingBox.x + boundingBox.width, boundingBox.y)
  ctx.lineTo(boundingBox.x + boundingBox.width, boundingBox.y + cornerSize)
  ctx.stroke()

  // Bottom-left corner
  ctx.beginPath()
  ctx.moveTo(boundingBox.x, boundingBox.y + boundingBox.height - cornerSize)
  ctx.lineTo(boundingBox.x, boundingBox.y + boundingBox.height)
  ctx.lineTo(boundingBox.x + cornerSize, boundingBox.y + boundingBox.height)
  ctx.stroke()

  // Bottom-right corner
  ctx.beginPath()
  ctx.moveTo(boundingBox.x + boundingBox.width - cornerSize, boundingBox.y + boundingBox.height)
  ctx.lineTo(boundingBox.x + boundingBox.width, boundingBox.y + boundingBox.height)
  ctx.lineTo(boundingBox.x + boundingBox.width, boundingBox.y + boundingBox.height - cornerSize)
  ctx.stroke()
}

const clearFaceBox = () => {
  if (overlayCanvas.value) {
    const ctx = overlayCanvas.value.getContext('2d')
    ctx.clearRect(0, 0, overlayCanvas.value.width, overlayCanvas.value.height)
  }
}

const captureAndProcess = async () => {
  if (!videoElement.value || faceDetectionStore.processing) {return}

  try {
    const result = await faceDetectionStore.captureImage(videoElement.value)

    // Simulate processing delay
    await new Promise((resolve) => setTimeout(resolve, 2000))

    emit('attendance-processed', {
      image: result.image,
      confidence: result.confidence,
      liveness: result.liveness,
      detectionMethod: result.detectionMethod,
      employeeId: props.employeeId,
    })
  } catch (error: any) {
    console.error('Capture error:', error)
    emit('error', { type: 'capture', message: 'Gagal memproses gambar' })
  }
}

const initializeDetectionModel = async () => {
  try {
    await faceDetectionStore.initialize(props.detectionMethod)
  } catch (error: any) {
    console.error('Model loading error:', error)
    emit('error', { type: 'model', message: 'Gagal memuat model deteksi' })
  }
}

// Watchers
watch(
  () => props.detectionMethod,
  (newMethod) => {
    faceDetectionStore.updateSettings({ detectionMethod: newMethod })
    if (faceDetectionStore.cameraActive) {
      initializeDetectionModel()
    }
  }
)

watch(
  () => props.confidenceThreshold,
  (newThreshold) => {
    faceDetectionStore.updateSettings({ confidenceThreshold: newThreshold })
  }
)

watch(
  () => props.livenessThreshold,
  (newThreshold) => {
    faceDetectionStore.updateSettings({ livenessThreshold: newThreshold })
  }
)

// Lifecycle
onMounted(() => {
  initializeDetectionModel()
})

onUnmounted(() => {
  faceDetectionStore.stopCamera()
})
</script>

<style scoped>
.face-recognition-container {
  @apply w-full;
}

/* Custom animations */
@keyframes pulse-border {
  0%,
  100% {
    border-color: rgba(34, 197, 94, 0.3);
  }
  50% {
    border-color: rgba(34, 197, 94, 0.8);
  }
}

.pulse-border {
  animation: pulse-border 2s ease-in-out infinite;
}
</style>
