/**
 * Face Detection Store
 *
 * Centralized state management for face detection functionality,
 * camera management, detection statistics, and processing state.
 */

import { defineStore } from 'pinia'
import { ref, computed, reactive } from 'vue'
import type { Ref } from 'vue'

export interface CameraStatus {
  title: string
  message: string
}

export interface DetectionStatus {
  type: 'success' | 'error' | 'searching' | 'loading'
  message: string
}

export interface FaceDetectionResult {
  confidence: number
  liveness: number
  boundingBox: {
    x: number
    y: number
    width: number
    height: number
  } | null
}

export interface Statistics {
  facesDetected: number
  confidenceSum: number
  processingTimes: number[]
  averageConfidence: number
  averageProcessingTime: number
}

export interface DetectionSettings {
  detectionMethod: 'face-api' | 'mediapipe'
  confidenceThreshold: number
  livenessThreshold: number
  enableLiveness: boolean
  retryAttempts: number
}

export interface CaptureResult {
  image: Blob
  confidence: number
  liveness: number
  detectionMethod: string
  employeeId: string
  timestamp: string
}

export const useFaceDetectionStore = defineStore('faceDetection', () => {
  // State
  const cameraActive = ref<boolean>(false)
  const loading = ref<boolean>(false)
  const processing = ref<boolean>(false)
  const showGuide = ref<boolean>(true)
  const videoStream: Ref<MediaStream | null> = ref(null)
  const detectionInterval: Ref<NodeJS.Timeout | null> = ref(null)
  const isInitialized = ref<boolean>(false)

  const cameraStatus = reactive<CameraStatus>({
    title: 'Kamera Siap',
    message: 'Klik tombol mulai untuk mengaktifkan kamera',
  })

  const detectionStatus: Ref<DetectionStatus | null> = ref(null)

  const currentDetection = reactive<FaceDetectionResult>({
    confidence: 0,
    liveness: 0,
    boundingBox: null,
  })

  const statistics = reactive<Statistics>({
    facesDetected: 0,
    confidenceSum: 0,
    processingTimes: [],
    averageConfidence: 0,
    averageProcessingTime: 0,
  })

  const settings = reactive<DetectionSettings>({
    detectionMethod: 'face-api',
    confidenceThreshold: 70,
    livenessThreshold: 60,
    enableLiveness: true,
    retryAttempts: 3,
  })

  const errors = ref<string[]>([])
  const lastError = ref<string | null>(null)
  const captureHistory = ref<CaptureResult[]>([])

  // Computed
  const canCapture = computed<boolean>(() => {
    return (
      cameraActive.value &&
      currentDetection.confidence > settings.confidenceThreshold &&
      !processing.value
    )
  })

  const isReady = computed<boolean>(() => {
    return isInitialized.value && !loading.value
  })

  const hasActiveSession = computed<boolean>(() => {
    return cameraActive.value && !!videoStream.value
  })

  const detectionQuality = computed<string>(() => {
    const confidence = currentDetection.confidence
    if (confidence >= 90) {return 'excellent'}
    if (confidence >= 80) {return 'good'}
    if (confidence >= 70) {return 'fair'}
    if (confidence >= 60) {return 'poor'}
    return 'very-poor'
  })

  const sessionStats = computed(() => ({
    totalDetections: statistics.facesDetected,
    averageConfidence: statistics.averageConfidence,
    averageProcessingTime: statistics.averageProcessingTime,
    successRate:
      statistics.facesDetected > 0
        ? Math.round((statistics.facesDetected / (statistics.processingTimes.length || 1)) * 100)
        : 0,
  }))

  // Actions
  const updateSettings = (newSettings: Partial<DetectionSettings>) => {
    Object.assign(settings, newSettings)
  }

  const setCameraStatus = (title: string, message: string) => {
    cameraStatus.title = title
    cameraStatus.message = message
  }

  const setDetectionStatus = (status: DetectionStatus | null) => {
    detectionStatus.value = status
  }

  const updateCurrentDetection = (detection: Partial<FaceDetectionResult>) => {
    Object.assign(currentDetection, detection)
  }

  const updateStatistics = (detection: {
    confidence: number
    processingTime: number
    faceDetected: boolean
  }) => {
    if (detection.faceDetected) {
      statistics.facesDetected++
      statistics.confidenceSum += detection.confidence
    }

    statistics.processingTimes.push(detection.processingTime)

    // Keep only last 20 processing times for rolling average
    if (statistics.processingTimes.length > 20) {
      statistics.processingTimes.shift()
    }

    // Update averages
    statistics.averageConfidence =
      statistics.facesDetected > 0
        ? Math.round(statistics.confidenceSum / statistics.facesDetected)
        : 0

    statistics.averageProcessingTime =
      statistics.processingTimes.length > 0
        ? Math.round(
            statistics.processingTimes.reduce((a, b) => a + b, 0) /
              statistics.processingTimes.length
          )
        : 0
  }

  const addError = (error: string) => {
    errors.value.push(error)
    lastError.value = error

    // Keep only last 10 errors
    if (errors.value.length > 10) {
      errors.value.shift()
    }
  }

  const clearErrors = () => {
    errors.value = []
    lastError.value = null
  }

  const startCamera = async (constraints?: MediaStreamConstraints): Promise<MediaStream> => {
    try {
      loading.value = true
      setCameraStatus('Mengaktifkan kamera...', 'Mohon tunggu sebentar')

      const defaultConstraints: MediaStreamConstraints = {
        video: {
          width: { ideal: 640 },
          height: { ideal: 480 },
          facingMode: 'user',
        },
        audio: false,
      }

      const stream = await navigator.mediaDevices.getUserMedia(constraints || defaultConstraints)

      videoStream.value = stream
      cameraActive.value = true
      showGuide.value = true

      setCameraStatus('Kamera Aktif', 'Posisikan wajah dalam frame')
      clearErrors()

      return stream
    } catch (error: any) {
      const errorMessage = getErrorMessage(error)
      setCameraStatus('Error Kamera', errorMessage)
      addError(errorMessage)
      throw error
    } finally {
      loading.value = false
    }
  }

  const stopCamera = () => {
    if (videoStream.value) {
      videoStream.value.getTracks().forEach((track) => track.stop())
      videoStream.value = null
    }

    if (detectionInterval.value) {
      clearInterval(detectionInterval.value)
      detectionInterval.value = null
    }

    cameraActive.value = false
    showGuide.value = false
    processing.value = false
    setDetectionStatus(null)

    // Reset detection state
    updateCurrentDetection({
      confidence: 0,
      liveness: 0,
      boundingBox: null,
    })

    setCameraStatus('Kamera Siap', 'Klik tombol mulai untuk mengaktifkan kamera')
  }

  const startDetection = (detectionFunction: () => Promise<void>, intervalMs = 500) => {
    if (detectionInterval.value) {
      clearInterval(detectionInterval.value)
    }

    setDetectionStatus({
      type: 'loading',
      message: 'Memulai deteksi wajah...',
    })

    detectionInterval.value = setInterval(async () => {
      try {
        await detectionFunction()
      } catch (error: any) {
        console.error('Detection error:', error)
        setDetectionStatus({
          type: 'error',
          message: 'Error deteksi wajah',
        })
        addError(`Detection failed: ${error.message}`)
      }
    }, intervalMs)
  }

  const stopDetection = () => {
    if (detectionInterval.value) {
      clearInterval(detectionInterval.value)
      detectionInterval.value = null
    }
    setDetectionStatus(null)
  }

  const captureImage = async (videoElement: HTMLVideoElement): Promise<CaptureResult> => {
    if (!videoElement || processing.value) {
      throw new Error('Cannot capture: video not ready or already processing')
    }

    try {
      processing.value = true

      setDetectionStatus({
        type: 'loading',
        message: 'Memproses gambar...',
      })

      const canvas = document.createElement('canvas')
      const ctx = canvas.getContext('2d')

      if (!ctx) {
        throw new Error('Could not get canvas context')
      }

      canvas.width = videoElement.videoWidth
      canvas.height = videoElement.videoHeight
      ctx.drawImage(videoElement, 0, 0)

      const blob = await new Promise<Blob>((resolve, reject) => {
        canvas.toBlob(
          (blob) => {
            if (blob) {
              resolve(blob)
            } else {
              reject(new Error('Failed to create blob from canvas'))
            }
          },
          'image/jpeg',
          0.8
        )
      })

      const result: CaptureResult = {
        image: blob,
        confidence: currentDetection.confidence,
        liveness: currentDetection.liveness,
        detectionMethod: settings.detectionMethod,
        employeeId: '', // Will be set by caller
        timestamp: new Date().toISOString(),
      }

      // Add to capture history
      captureHistory.value.unshift(result)
      if (captureHistory.value.length > 10) {
        captureHistory.value.pop()
      }

      setDetectionStatus({
        type: 'success',
        message: 'Gambar berhasil diproses',
      })

      return result
    } catch (error: any) {
      const errorMessage = `Failed to capture image: ${error.message}`
      addError(errorMessage)
      setDetectionStatus({
        type: 'error',
        message: 'Gagal memproses gambar',
      })
      throw error
    } finally {
      processing.value = false
    }
  }

  const reset = () => {
    stopCamera()

    // Reset all state
    isInitialized.value = false
    Object.assign(statistics, {
      facesDetected: 0,
      confidenceSum: 0,
      processingTimes: [],
      averageConfidence: 0,
      averageProcessingTime: 0,
    })

    clearErrors()
    captureHistory.value = []

    setCameraStatus('Kamera Siap', 'Klik tombol mulai untuk mengaktifkan kamera')
  }

  const initialize = async (method: 'face-api' | 'mediapipe' = 'face-api') => {
    try {
      loading.value = true
      settings.detectionMethod = method

      // Simulate model loading
      await new Promise((resolve) => setTimeout(resolve, 1000))

      isInitialized.value = true
      console.log(`${method} detection model initialized`)
    } catch (error: any) {
      addError(`Failed to initialize ${method}: ${error.message}`)
      throw error
    } finally {
      loading.value = false
    }
  }

  const getErrorMessage = (error: any): string => {
    if (error.name === 'NotAllowedError') {
      return 'Akses kamera ditolak. Silakan berikan izin kamera.'
    } else if (error.name === 'NotFoundError') {
      return 'Kamera tidak ditemukan'
    } else if (error.name === 'NotReadableError') {
      return 'Kamera sedang digunakan aplikasi lain'
    } else if (error.name === 'OverconstrainedError') {
      return 'Kamera tidak mendukung pengaturan yang diminta'
    } else if (error.name === 'SecurityError') {
      return 'Akses kamera diblokir karena alasan keamanan'
    }
    return error.message || 'Gagal mengakses kamera'
  }

  // Export store interface
  return {
    // State
    cameraActive,
    loading,
    processing,
    showGuide,
    videoStream,
    isInitialized,
    cameraStatus,
    detectionStatus,
    currentDetection,
    statistics,
    settings,
    errors,
    lastError,
    captureHistory,

    // Computed
    canCapture,
    isReady,
    hasActiveSession,
    detectionQuality,
    sessionStats,

    // Actions
    updateSettings,
    setCameraStatus,
    setDetectionStatus,
    updateCurrentDetection,
    updateStatistics,
    addError,
    clearErrors,
    startCamera,
    stopCamera,
    startDetection,
    stopDetection,
    captureImage,
    reset,
    initialize,
  }
})
