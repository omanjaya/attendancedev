<template>
  <div class="face-enrollment-container">
    <!-- Progress Steps -->
    <div class="mb-8">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">Pendaftaran Wajah Karyawan</h2>
        <div class="text-sm text-gray-500 dark:text-gray-400">
          Langkah {{ currentStep }} dari {{ totalSteps }}
        </div>
      </div>
      
      <!-- Progress Bar -->
      <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
        <div 
          class="bg-gradient-to-r from-emerald-400 to-emerald-600 h-2 rounded-full transition-all duration-500"
          :style="{ width: `${(currentStep / totalSteps) * 100}%` }"
        ></div>
      </div>
      
      <!-- Step Labels -->
      <div class="flex justify-between mt-2 text-xs text-gray-500 dark:text-gray-400">
        <span :class="{ 'text-emerald-600 font-medium': currentStep >= 1 }">Persiapan</span>
        <span :class="{ 'text-emerald-600 font-medium': currentStep >= 2 }">Deteksi</span>
        <span :class="{ 'text-emerald-600 font-medium': currentStep >= 3 }">Verifikasi</span>
        <span :class="{ 'text-emerald-600 font-medium': currentStep >= 4 }">Selesai</span>
      </div>
    </div>

    <!-- Step 1: Employee Selection -->
    <div v-if="currentStep === 1" class="space-y-6">
      <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
          Pilih Karyawan
        </h3>
        
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Karyawan
            </label>
            <select 
              v-model="selectedEmployee"
              class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 dark:text-gray-100"
              :disabled="loading"
            >
              <option value="">Pilih karyawan...</option>
              <option 
                v-for="employee in employees" 
                :key="employee.id" 
                :value="employee"
              >
                {{ employee.name }} - {{ employee.employee_id }}
              </option>
            </select>
          </div>
          
          <div v-if="selectedEmployee" class="bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-700/50 rounded-lg p-4">
            <div class="flex items-center space-x-4">
              <div class="flex-shrink-0">
                <div class="w-12 h-12 rounded-full bg-emerald-100 dark:bg-emerald-800 flex items-center justify-center">
                  <span class="text-emerald-600 dark:text-emerald-400 font-semibold">
                    {{ selectedEmployee.name.charAt(0) }}
                  </span>
                </div>
              </div>
              <div>
                <div class="font-medium text-emerald-900 dark:text-emerald-100">
                  {{ selectedEmployee.name }}
                </div>
                <div class="text-sm text-emerald-600 dark:text-emerald-400">
                  {{ selectedEmployee.employee_id }} - {{ selectedEmployee.department }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Instructions -->
      <div class="bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-700/50 rounded-lg p-4">
        <h4 class="font-semibold text-blue-900 dark:text-blue-100 mb-2 flex items-center">
          <InformationCircleIcon class="w-5 h-5 mr-2" />
          Petunjuk Pendaftaran
        </h4>
        <div class="text-sm text-blue-700 dark:text-blue-300 space-y-1">
          <p>• Pilih karyawan yang akan didaftarkan wajahnya</p>
          <p>• Pastikan karyawan yang bersangkutan hadir saat proses pendaftaran</p>
          <p>• Proses pendaftaran akan memakan waktu sekitar 2-3 menit</p>
          <p>• Pastikan pencahayaan ruangan cukup terang</p>
        </div>
      </div>

      <div class="flex justify-end space-x-3">
        <button
          @click="$emit('cancel')"
          class="px-6 py-3 text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 rounded-lg transition-colors"
        >
          Batal
        </button>
        <button
          @click="nextStep"
          :disabled="!selectedEmployee"
          class="px-6 py-3 text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
        >
          Lanjutkan
        </button>
      </div>
    </div>

    <!-- Step 2: Face Detection -->
    <div v-if="currentStep === 2" class="space-y-6">
      <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
          Deteksi Wajah
        </h3>
        
        <!-- Face Recognition Component -->
        <FaceRecognition
          :detection-method="detectionMethod"
          :employee-id="selectedEmployee?.id"
          @face-detected="handleFaceDetected"
          @attendance-processed="handleFaceCapture"
          @error="handleError"
        />
      </div>

      <!-- Capture Results -->
      <div v-if="capturedFaces.length > 0" class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
        <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">
          Wajah Terdeteksi ({{ capturedFaces.length }}/{{ requiredCaptures }})
        </h4>
        
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div 
            v-for="(face, index) in capturedFaces" 
            :key="index"
            class="relative group"
          >
            <div class="aspect-square bg-gray-100 dark:bg-gray-700 rounded-lg overflow-hidden">
              <img 
                :src="face.imageUrl" 
                :alt="`Capture ${index + 1}`"
                class="w-full h-full object-cover"
              >
              <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-50 transition-opacity flex items-center justify-center">
                <div class="text-white text-sm font-medium opacity-0 group-hover:opacity-100 transition-opacity">
                  {{ Math.round(face.confidence) }}% akurasi
                </div>
              </div>
            </div>
            <div class="mt-2 text-xs text-center text-gray-500 dark:text-gray-400">
              Capture {{ index + 1 }}
            </div>
          </div>
        </div>
        
        <div class="mt-4 flex justify-between items-center">
          <div class="text-sm text-gray-600 dark:text-gray-400">
            Akurasi rata-rata: {{ averageConfidence }}%
          </div>
          <button
            @click="clearCaptures"
            class="text-sm text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
          >
            Hapus Semua
          </button>
        </div>
      </div>

      <div class="flex justify-between">
        <button
          @click="prevStep"
          class="px-6 py-3 text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 rounded-lg transition-colors"
        >
          Kembali
        </button>
        <button
          @click="nextStep"
          :disabled="capturedFaces.length < requiredCaptures"
          class="px-6 py-3 text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
        >
          Lanjutkan
        </button>
      </div>
    </div>

    <!-- Step 3: Liveness Verification -->
    <div v-if="currentStep === 3" class="space-y-6">
      <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
          Verifikasi Liveness
        </h3>
        
        <div v-if="!livenessStarted" class="text-center space-y-4">
          <div class="mx-auto w-16 h-16 bg-purple-100 dark:bg-purple-800 rounded-full flex items-center justify-center">
            <ShieldCheckIcon class="w-8 h-8 text-purple-600 dark:text-purple-400" />
          </div>
          <div>
            <h4 class="font-semibold text-gray-900 dark:text-gray-100">Verifikasi Keaktifan</h4>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
              Sistem akan meminta Anda melakukan beberapa gerakan sederhana untuk memverifikasi bahwa Anda adalah orang yang sebenarnya.
            </p>
          </div>
          <button
            @click="startLivenessCheck"
            :disabled="livenessProcessing"
            class="px-6 py-3 text-sm font-medium text-white bg-purple-600 hover:bg-purple-700 rounded-lg transition-colors disabled:opacity-50"
          >
            {{ livenessProcessing ? 'Memproses...' : 'Mulai Verifikasi' }}
          </button>
        </div>

        <div v-if="livenessStarted && !livenessCompleted" class="text-center space-y-4">
          <div class="mx-auto w-16 h-16 bg-blue-100 dark:bg-blue-800 rounded-full flex items-center justify-center">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
          </div>
          <div>
            <h4 class="font-semibold text-gray-900 dark:text-gray-100">
              {{ currentGesture?.title || 'Memproses...' }}
            </h4>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
              {{ currentGesture?.description || 'Mohon tunggu sebentar...' }}
            </p>
          </div>
          <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
            <div 
              class="bg-blue-600 h-2 rounded-full transition-all duration-300"
              :style="{ width: `${livenessProgress}%` }"
            ></div>
          </div>
        </div>

        <div v-if="livenessCompleted" class="text-center space-y-4">
          <div class="mx-auto w-16 h-16 bg-green-100 dark:bg-green-800 rounded-full flex items-center justify-center">
            <CheckCircleIcon class="w-8 h-8 text-green-600 dark:text-green-400" />
          </div>
          <div>
            <h4 class="font-semibold text-gray-900 dark:text-gray-100">Verifikasi Berhasil!</h4>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
              Skor liveness: {{ livenessScore }}% ({{ livenessScore >= 75 ? 'Lulus' : 'Gagal' }})
            </p>
          </div>
          
          <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
            <div class="grid grid-cols-2 gap-4 text-sm">
              <div class="text-center">
                <div class="font-semibold text-gray-900 dark:text-gray-100">
                  {{ completedGestures }}
                </div>
                <div class="text-gray-600 dark:text-gray-400">Gerakan Berhasil</div>
              </div>
              <div class="text-center">
                <div class="font-semibold text-gray-900 dark:text-gray-100">
                  {{ livenessTime }}ms
                </div>
                <div class="text-gray-600 dark:text-gray-400">Waktu Proses</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="flex justify-between">
        <button
          @click="prevStep"
          :disabled="livenessProcessing"
          class="px-6 py-3 text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 rounded-lg transition-colors disabled:opacity-50"
        >
          Kembali
        </button>
        <button
          @click="nextStep"
          :disabled="!livenessCompleted || livenessScore < 75"
          class="px-6 py-3 text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
        >
          Selesaikan Pendaftaran
        </button>
      </div>
    </div>

    <!-- Step 4: Completion -->
    <div v-if="currentStep === 4" class="space-y-6">
      <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700 text-center">
        <div class="mx-auto w-20 h-20 bg-green-100 dark:bg-green-800 rounded-full flex items-center justify-center mb-6">
          <CheckCircleIcon class="w-10 h-10 text-green-600 dark:text-green-400" />
        </div>
        
        <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-2">
          Pendaftaran Berhasil!
        </h3>
        <p class="text-gray-600 dark:text-gray-400 mb-6">
          Wajah {{ selectedEmployee?.name }} telah berhasil didaftarkan ke sistem.
        </p>

        <!-- Enrollment Summary -->
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 mb-6">
          <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
            <div class="text-center">
              <div class="font-semibold text-gray-900 dark:text-gray-100">
                {{ capturedFaces.length }}
              </div>
              <div class="text-gray-600 dark:text-gray-400">Foto Wajah</div>
            </div>
            <div class="text-center">
              <div class="font-semibold text-gray-900 dark:text-gray-100">
                {{ averageConfidence }}%
              </div>
              <div class="text-gray-600 dark:text-gray-400">Akurasi</div>
            </div>
            <div class="text-center">
              <div class="font-semibold text-gray-900 dark:text-gray-100">
                {{ livenessScore }}%
              </div>
              <div class="text-gray-600 dark:text-gray-400">Liveness</div>
            </div>
            <div class="text-center">
              <div class="font-semibold text-gray-900 dark:text-gray-100">
                {{ detectionMethod.toUpperCase() }}
              </div>
              <div class="text-gray-600 dark:text-gray-400">Metode</div>
            </div>
          </div>
        </div>

        <div class="flex justify-center space-x-3">
          <button
            @click="enrollAnother"
            class="px-6 py-3 text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 rounded-lg transition-colors"
          >
            Daftarkan Lagi
          </button>
          <button
            @click="$emit('complete', enrollmentResult)"
            class="px-6 py-3 text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-colors"
          >
            Selesai
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { 
  InformationCircleIcon, 
  ShieldCheckIcon, 
  CheckCircleIcon 
} from '@heroicons/vue/24/outline'
import FaceRecognition from './FaceRecognition.vue'
import livenessDetection from '../utils/livenessDetection.js'

// Props
const props = defineProps({
  detectionMethod: {
    type: String,
    default: 'face-api'
  },
  requiredCaptures: {
    type: Number,
    default: 3
  }
})

// Emits
const emit = defineEmits(['complete', 'cancel'])

// Reactive state
const currentStep = ref(1)
const totalSteps = ref(4)
const loading = ref(false)
const employees = ref([])
const selectedEmployee = ref(null)
const capturedFaces = ref([])
const livenessStarted = ref(false)
const livenessCompleted = ref(false)
const livenessProcessing = ref(false)
const livenessProgress = ref(0)
const livenessScore = ref(0)
const livenessTime = ref(0)
const completedGestures = ref(0)
const currentGesture = ref(null)

// Computed
const averageConfidence = computed(() => {
  if (capturedFaces.value.length === 0) return 0
  const sum = capturedFaces.value.reduce((acc, face) => acc + face.confidence, 0)
  return Math.round(sum / capturedFaces.value.length)
})

const enrollmentResult = computed(() => ({
  employee: selectedEmployee.value,
  captures: capturedFaces.value,
  averageConfidence: averageConfidence.value,
  livenessScore: livenessScore.value,
  detectionMethod: props.detectionMethod,
  enrollmentId: `enroll_${Date.now()}_${selectedEmployee.value?.id}`,
  timestamp: new Date().toISOString()
}))

// Methods
const nextStep = () => {
  if (currentStep.value < totalSteps.value) {
    currentStep.value++
  }
}

const prevStep = () => {
  if (currentStep.value > 1) {
    currentStep.value--
  }
}

const handleFaceDetected = (detection) => {
  // Handle face detection events
  console.log('Face detected:', detection)
}

const handleFaceCapture = (captureData) => {
  // Convert blob to URL for preview
  const imageUrl = URL.createObjectURL(captureData.image)
  
  capturedFaces.value.push({
    id: Date.now(),
    imageUrl,
    confidence: captureData.confidence,
    liveness: captureData.liveness,
    timestamp: new Date().toISOString(),
    blob: captureData.image
  })
  
  // Auto-advance if we have enough captures
  if (capturedFaces.value.length >= props.requiredCaptures) {
    setTimeout(() => {
      nextStep()
    }, 1000)
  }
}

const handleError = (error) => {
  console.error('Face enrollment error:', error)
  // Handle error appropriately
}

const clearCaptures = () => {
  // Clean up object URLs
  capturedFaces.value.forEach(face => {
    URL.revokeObjectURL(face.imageUrl)
  })
  capturedFaces.value = []
}

const startLivenessCheck = async () => {
  livenessStarted.value = true
  livenessProcessing.value = true
  livenessProgress.value = 0
  
  try {
    const videoElement = document.querySelector('video')
    if (!videoElement) {
      throw new Error('Video element not found')
    }
    
    const result = await livenessDetection.startLivenessCheck(videoElement, {
      requiredGestures: 2,
      timeout: 30000,
      onProgress: (progress) => {
        livenessProgress.value = progress.progress * 100
      },
      onGesturePrompt: (gestureType, instruction) => {
        currentGesture.value = instruction
      }
    })
    
    livenessCompleted.value = result.success
    livenessScore.value = result.overallScore
    livenessTime.value = result.detectionTime
    completedGestures.value = result.completedGestures.length
    
  } catch (error) {
    console.error('Liveness check error:', error)
    livenessCompleted.value = false
    livenessScore.value = 0
  } finally {
    livenessProcessing.value = false
  }
}

const enrollAnother = () => {
  // Reset state for new enrollment
  currentStep.value = 1
  selectedEmployee.value = null
  clearCaptures()
  livenessStarted.value = false
  livenessCompleted.value = false
  livenessScore.value = 0
  livenessTime.value = 0
  completedGestures.value = 0
  currentGesture.value = null
}

const loadEmployees = async () => {
  loading.value = true
  try {
    // Simulate API call
    await new Promise(resolve => setTimeout(resolve, 1000))
    
    employees.value = [
      { id: 1, name: 'John Doe', employee_id: 'EMP001', department: 'IT' },
      { id: 2, name: 'Jane Smith', employee_id: 'EMP002', department: 'HR' },
      { id: 3, name: 'Mike Johnson', employee_id: 'EMP003', department: 'Finance' },
      { id: 4, name: 'Sarah Wilson', employee_id: 'EMP004', department: 'Marketing' }
    ]
  } catch (error) {
    console.error('Failed to load employees:', error)
  } finally {
    loading.value = false
  }
}

// Lifecycle
onMounted(() => {
  loadEmployees()
})
</script>

<style scoped>
.face-enrollment-container {
  @apply max-w-4xl mx-auto;
}

/* Custom animations */
.step-enter-active,
.step-leave-active {
  transition: all 0.3s ease;
}

.step-enter-from {
  opacity: 0;
  transform: translateX(30px);
}

.step-leave-to {
  opacity: 0;
  transform: translateX(-30px);
}
</style>