<template>
  <div class="face-recognition-container">
    <!-- Camera Preview -->
    <div class="relative w-full h-80 bg-black rounded-xl overflow-hidden border-4 border-gray-300 dark:border-gray-600">
      <video
        ref="videoElement"
        :class="{ hidden: !cameraActive }"
        class="w-full h-full object-cover"
        autoplay
        muted
        playsinline
      ></video>
      
      <canvas
        ref="overlayCanvas"
        :class="{ hidden: !cameraActive }"
        class="absolute top-0 left-0 w-full h-full pointer-events-none"
      ></canvas>
      
      <!-- Camera Placeholder -->
      <div
        v-if="!cameraActive"
        class="flex items-center justify-center h-full bg-gradient-to-br from-gray-800 to-gray-900"
      >
        <div class="text-center">
          <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-white/10 backdrop-blur-sm mb-4">
            <CameraIcon class="h-8 w-8 text-white" />
          </div>
          <h3 class="text-lg font-semibold text-white mb-2">{{ cameraStatus.title }}</h3>
          <p class="text-gray-300 text-sm">{{ cameraStatus.message }}</p>
        </div>
      </div>
      
      <!-- Detection Status Overlay -->
      <div
        v-if="cameraActive && detectionStatus"
        class="absolute top-4 left-4 right-4"
      >
        <div class="bg-black/70 backdrop-blur-sm rounded-lg p-3 text-white">
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-2">
              <div
                :class="[
                  'w-3 h-3 rounded-full',
                  detectionStatus.type === 'success' ? 'bg-green-500' : 
                  detectionStatus.type === 'error' ? 'bg-red-500' : 
                  'bg-yellow-500 animate-pulse'
                ]"
              ></div>
              <span class="text-sm font-medium">{{ detectionStatus.message }}</span>
            </div>
            <div class="text-xs bg-white/20 px-2 py-1 rounded">
              {{ currentDetection.confidence }}%
            </div>
          </div>
        </div>
      </div>
      
      <!-- Face Guide Frame -->
      <div
        v-if="cameraActive && showGuide"
        class="absolute inset-0 flex items-center justify-center pointer-events-none"
      >
        <div class="relative">
          <svg width="200" height="240" viewBox="0 0 200 240" class="text-white/60">
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
            <path d="M30 40 L30 20 L50 20" stroke="currentColor" stroke-width="3" fill="none"/>
            <path d="M170 40 L170 20 L150 20" stroke="currentColor" stroke-width="3" fill="none"/>
            <path d="M30 200 L30 220 L50 220" stroke="currentColor" stroke-width="3" fill="none"/>
            <path d="M170 200 L170 220 L150 220" stroke="currentColor" stroke-width="3" fill="none"/>
          </svg>
          <div class="absolute -bottom-8 left-1/2 transform -translate-x-1/2 text-white text-xs text-center">
            Posisikan wajah di dalam frame
          </div>
        </div>
      </div>
      
      <!-- Detection Method Badge -->
      <div class="absolute top-2 right-2 bg-purple-600 text-white text-xs px-3 py-1 rounded-full font-medium">
        {{ detectionMethod === 'face-api' ? 'Face-API.js' : 'MediaPipe' }}
      </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">
      <div class="bg-white dark:bg-gray-800 rounded-lg p-3 text-center border border-gray-200 dark:border-gray-700">
        <div class="text-lg font-bold text-emerald-600 dark:text-emerald-400">
          {{ statistics.facesDetected }}
        </div>
        <div class="text-xs text-gray-600 dark:text-gray-400">Wajah Terdeteksi</div>
      </div>
      
      <div class="bg-white dark:bg-gray-800 rounded-lg p-3 text-center border border-gray-200 dark:border-gray-700">
        <div class="text-lg font-bold text-blue-600 dark:text-blue-400">
          {{ statistics.averageConfidence }}%
        </div>
        <div class="text-xs text-gray-600 dark:text-gray-400">Akurasi Rata-rata</div>
      </div>
      
      <div class="bg-white dark:bg-gray-800 rounded-lg p-3 text-center border border-gray-200 dark:border-gray-700">
        <div class="text-lg font-bold text-amber-600 dark:text-amber-400">
          {{ statistics.averageProcessingTime }}ms
        </div>
        <div class="text-xs text-gray-600 dark:text-gray-400">Waktu Proses</div>
      </div>
      
      <div class="bg-white dark:bg-gray-800 rounded-lg p-3 text-center border border-gray-200 dark:border-gray-700">
        <div class="text-lg font-bold text-purple-600 dark:text-purple-400">
          {{ currentDetection.liveness }}%
        </div>
        <div class="text-xs text-gray-600 dark:text-gray-400">Liveness Score</div>
      </div>
    </div>
    
    <!-- Control Buttons -->
    <div class="flex flex-wrap gap-3 justify-center mt-6">
      <button
        v-if="!cameraActive"
        @click="startCamera"
        :disabled="loading"
        class="inline-flex items-center px-6 py-3 text-sm font-medium text-white bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 rounded-lg transition-all duration-200 hover:scale-105 shadow-sm disabled:opacity-50"
      >
        <VideoCameraIcon class="h-5 w-5 mr-2" />
        {{ loading ? 'Memuat...' : 'Mulai Kamera' }}
      </button>
      
      <button
        v-if="cameraActive"
        @click="stopCamera"
        class="inline-flex items-center px-6 py-3 text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 rounded-lg transition-all duration-200"
      >
        <StopIcon class="h-5 w-5 mr-2" />
        Hentikan
      </button>
      
      <button
        v-if="cameraActive && canCapture"
        @click="captureAndProcess"
        :disabled="processing"
        class="inline-flex items-center px-6 py-3 text-sm font-medium text-white bg-gradient-to-r from-purple-500 to-purple-600 hover:from-purple-600 hover:to-purple-700 rounded-lg transition-all duration-200 hover:scale-105 shadow-sm disabled:opacity-50"
      >
        <CameraIcon class="h-5 w-5 mr-2" />
        {{ processing ? 'Memproses...' : 'Ambil Foto & Proses' }}
      </button>
      
      <button
        @click="$emit('simulate-attendance')"
        class="inline-flex items-center px-6 py-3 text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 rounded-lg transition-all duration-200"
      >
        <BoltIcon class="h-5 w-5 mr-2" />
        Simulasi (Demo)
      </button>
    </div>
    
    <!-- Instructions -->
    <div class="mt-6 bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-700/50 rounded-lg p-4">
      <h4 class="font-semibold text-blue-900 dark:text-blue-100 mb-2 flex items-center">
        <InformationCircleIcon class="w-5 h-5 mr-2" />
        Petunjuk Penggunaan
      </h4>
      <div class="text-sm text-blue-700 dark:text-blue-300 space-y-1">
        <p>📱 Pastikan pencahayaan cukup terang dan wajah terlihat jelas</p>
        <p>👤 Posisikan wajah di tengah frame dan hindari gerakan berlebihan</p>
        <p>🔒 Sistem akan melakukan verifikasi liveness untuk keamanan ekstra</p>
        <p>⚡ Proses deteksi otomatis akan berjalan setelah kamera aktif</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted, watch } from 'vue'
import { 
  CameraIcon, 
  VideoCameraIcon, 
  StopIcon, 
  BoltIcon, 
  InformationCircleIcon 
} from '@heroicons/vue/24/outline'

// Props
const props = defineProps({
  detectionMethod: {
    type: String,
    default: 'face-api',
    validator: (value) => ['face-api', 'mediapipe'].includes(value)
  },
  employeeId: {
    type: String,
    default: ''
  }
})

// Emits
const emit = defineEmits(['face-detected', 'attendance-processed', 'simulate-attendance', 'error'])

// Refs
const videoElement = ref(null)
const overlayCanvas = ref(null)

// Reactive state
const cameraActive = ref(false)
const loading = ref(false)
const processing = ref(false)
const showGuide = ref(true)
const videoStream = ref(null)
const detectionInterval = ref(null)

const cameraStatus = reactive({
  title: 'Kamera Siap',
  message: 'Klik tombol mulai untuk mengaktifkan kamera'
})

const detectionStatus = ref(null)

const currentDetection = reactive({
  confidence: 0,
  liveness: 0,
  boundingBox: null
})

const statistics = reactive({
  facesDetected: 0,
  confidenceSum: 0,
  processingTimes: [],
  averageConfidence: 0,
  averageProcessingTime: 0
})

// Computed
const canCapture = computed(() => {
  return cameraActive.value && currentDetection.confidence > 70
})

// Face detection models
let faceApiModel = null
let mediaPipeModel = null

// Methods
const startCamera = async () => {
  loading.value = true
  
  try {
    const stream = await navigator.mediaDevices.getUserMedia({
      video: {
        width: { ideal: 640 },
        height: { ideal: 480 },
        facingMode: 'user'
      },
      audio: false
    })
    
    videoStream.value = stream
    videoElement.value.srcObject = stream
    
    // Wait for video to be ready
    await new Promise((resolve) => {
      videoElement.value.onloadedmetadata = resolve
    })
    
    cameraActive.value = true
    showGuide.value = true
    
    // Start detection
    startFaceDetection()
    
    // Initialize detection model if needed
    await initializeDetectionModel()
    
    emit('face-detected', { status: 'camera-started' })
    
  } catch (error) {
    console.error('Camera access error:', error)
    
    let errorMessage = 'Gagal mengakses kamera'
    if (error.name === 'NotAllowedError') {
      errorMessage = 'Akses kamera ditolak. Silakan berikan izin kamera.'
    } else if (error.name === 'NotFoundError') {
      errorMessage = 'Kamera tidak ditemukan'
    }
    
    cameraStatus.title = 'Error Kamera'
    cameraStatus.message = errorMessage
    
    emit('error', { type: 'camera', message: errorMessage })
  } finally {
    loading.value = false
  }
}

const stopCamera = () => {
  if (videoStream.value) {
    videoStream.value.getTracks().forEach(track => track.stop())
    videoStream.value = null
  }
  
  if (detectionInterval.value) {
    clearInterval(detectionInterval.value)
    detectionInterval.value = null
  }
  
  cameraActive.value = false
  showGuide.value = false
  detectionStatus.value = null
  
  // Reset detection state
  currentDetection.confidence = 0
  currentDetection.liveness = 0
  currentDetection.boundingBox = null
  
  // Reset camera status
  cameraStatus.title = 'Kamera Siap'
  cameraStatus.message = 'Klik tombol mulai untuk mengaktifkan kamera'
  
  // Clear canvas
  if (overlayCanvas.value) {
    const ctx = overlayCanvas.value.getContext('2d')
    ctx.clearRect(0, 0, overlayCanvas.value.width, overlayCanvas.value.height)
  }
}

const startFaceDetection = () => {
  detectionStatus.value = {
    type: 'loading',
    message: 'Memulai deteksi wajah...'
  }
  
  detectionInterval.value = setInterval(() => {
    performFaceDetection()
  }, 500) // Detection every 500ms
}

const performFaceDetection = async () => {
  if (!cameraActive.value || !videoElement.value) return
  
  const startTime = performance.now()
  
  try {
    let detection
    
    if (props.detectionMethod === 'face-api') {
      detection = await detectWithFaceAPI()
    } else {
      detection = await detectWithMediaPipe()
    }
    
    const processingTime = Math.round(performance.now() - startTime)
    
    if (detection.faceDetected) {
      detectionStatus.value = {
        type: 'success',
        message: `Wajah terdeteksi (${detection.confidence}%)`
      }
      
      currentDetection.confidence = detection.confidence
      currentDetection.liveness = detection.liveness
      currentDetection.boundingBox = detection.boundingBox
      
      updateStatistics(detection, processingTime)
      drawFaceBox(detection.boundingBox)
      
      emit('face-detected', {
        status: 'detected',
        confidence: detection.confidence,
        liveness: detection.liveness,
        processingTime
      })
      
    } else {
      detectionStatus.value = {
        type: 'searching',
        message: 'Mencari wajah...'
      }
      
      currentDetection.confidence = 0
      currentDetection.liveness = 0
      clearFaceBox()
    }
    
  } catch (error) {
    console.error('Face detection error:', error)
    detectionStatus.value = {
      type: 'error',
      message: 'Error deteksi wajah'
    }
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
    boundingBox: faceDetected ? generateRandomBoundingBox() : null
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
    boundingBox: faceDetected ? generateRandomBoundingBox() : null
  }
}

const generateRandomBoundingBox = () => {
  const video = videoElement.value
  if (!video) return null
  
  const videoWidth = video.videoWidth || video.offsetWidth
  const videoHeight = video.videoHeight || video.offsetHeight
  
  return {
    x: (videoWidth * 0.3) + (Math.random() - 0.5) * 40,
    y: (videoHeight * 0.25) + (Math.random() - 0.5) * 30,
    width: videoWidth * 0.25 + Math.random() * 20,
    height: videoHeight * 0.35 + Math.random() * 20
  }
}

const updateStatistics = (detection, processingTime) => {
  if (detection.faceDetected) {
    statistics.facesDetected++
    statistics.confidenceSum += detection.confidence
  }
  
  statistics.processingTimes.push(processingTime)
  if (statistics.processingTimes.length > 10) {
    statistics.processingTimes.shift()
  }
  
  statistics.averageConfidence = statistics.facesDetected > 0 
    ? Math.round(statistics.confidenceSum / statistics.facesDetected) 
    : 0
    
  statistics.averageProcessingTime = statistics.processingTimes.length > 0
    ? Math.round(statistics.processingTimes.reduce((a, b) => a + b, 0) / statistics.processingTimes.length)
    : 0
}

const drawFaceBox = (boundingBox) => {
  if (!boundingBox || !overlayCanvas.value) return
  
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
  if (!videoElement.value || processing.value) return
  
  processing.value = true
  
  try {
    detectionStatus.value = {
      type: 'loading',
      message: 'Memproses gambar...'
    }
    
    const canvas = document.createElement('canvas')
    const ctx = canvas.getContext('2d')
    const video = videoElement.value
    
    canvas.width = video.videoWidth
    canvas.height = video.videoHeight
    ctx.drawImage(video, 0, 0)
    
    // Convert to blob
    const blob = await new Promise(resolve => {
      canvas.toBlob(resolve, 'image/jpeg', 0.8)
    })
    
    // Simulate processing delay
    await new Promise(resolve => setTimeout(resolve, 2000))
    
    emit('attendance-processed', {
      image: blob,
      confidence: currentDetection.confidence,
      liveness: currentDetection.liveness,
      detectionMethod: props.detectionMethod,
      employeeId: props.employeeId
    })
    
  } catch (error) {
    console.error('Capture error:', error)
    emit('error', { type: 'capture', message: 'Gagal memproses gambar' })
  } finally {
    processing.value = false
  }
}

const initializeDetectionModel = async () => {
  // In real implementation, load Face-API.js or MediaPipe models here
  try {
    if (props.detectionMethod === 'face-api' && !faceApiModel) {
      // await faceapi.loadSsdMobilenetv1Model('/models')
      // await faceapi.loadFaceLandmarkModel('/models')
      // await faceapi.loadFaceRecognitionModel('/models')
      console.log('Face-API.js model loaded (simulated)')
    } else if (props.detectionMethod === 'mediapipe' && !mediaPipeModel) {
      // Initialize MediaPipe Face Detection
      console.log('MediaPipe model loaded (simulated)')
    }
  } catch (error) {
    console.error('Model loading error:', error)
    emit('error', { type: 'model', message: 'Gagal memuat model deteksi' })
  }
}

// Watchers
watch(() => props.detectionMethod, (newMethod) => {
  if (cameraActive.value) {
    initializeDetectionModel()
  }
})

// Lifecycle
onMounted(() => {
  initializeDetectionModel()
})

onUnmounted(() => {
  stopCamera()
})
</script>

<style scoped>
.face-recognition-container {
  @apply w-full;
}

/* Custom animations */
@keyframes pulse-border {
  0%, 100% {
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