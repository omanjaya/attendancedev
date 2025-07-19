import { ref, nextTick } from 'vue'
import FaceDetectionService from '@/services/FaceDetectionService'
import MediaPipeFaceService from '@/services/MediaPipeFaceService'

export function useFaceDetection(options = {}) {
  const useMediaPipe = options.useMediaPipe || false
  const cameraActive = ref(false)
  const faceDetected = ref(false)
  const processing = ref(false)
  const error = ref(null)
  const gesturePrompt = ref(null)
  const gestureProgress = ref(0)

  const videoElement = ref(null)
  const canvasElement = ref(null)
  const currentService = useMediaPipe ? MediaPipeFaceService : FaceDetectionService
  const detectionLoop = ref(null)
  const knownFaces = ref([])

  const startCamera = async () => {
    try {
      error.value = null
      processing.value = true

      // Initialize the face detection service
      await currentService.initialize()

      if (videoElement.value) {
        await currentService.startCamera(videoElement.value)
        cameraActive.value = true

        // Wait for video to be ready
        await nextTick()

        // Set up canvas
        setupCanvas()

        // Start face detection loop
        startFaceDetectionLoop()
      }
    } catch (err) {
      error.value = 'Failed to access camera: ' + err.message
      console.error('Camera error:', err)
    } finally {
      processing.value = false
    }
  }

  const setupCanvas = () => {
    if (canvasElement.value && videoElement.value) {
      const video = videoElement.value
      const canvas = canvasElement.value

      canvas.width = video.videoWidth || 640
      canvas.height = video.videoHeight || 480

      // For MediaPipe, set up result callback
      if (useMediaPipe) {
        currentService.setOnResults((results) => {
          currentService.drawResults(canvas, results, video)
          faceDetected.value = results.detections && results.detections.length > 0
        })
      }
    }
  }

  const stopCamera = () => {
    // Stop detection loop
    if (detectionLoop.value) {
      cancelAnimationFrame(detectionLoop.value)
      detectionLoop.value = null
    }

    // Stop camera service
    currentService.stopCamera()

    if (videoElement.value) {
      videoElement.value.srcObject = null
    }

    cameraActive.value = false
    faceDetected.value = false
    gesturePrompt.value = null
    gestureProgress.value = 0
  }

  const startFaceDetectionLoop = async () => {
    if (!cameraActive.value || !videoElement.value) return

    const detectFaces = async () => {
      try {
        if (!useMediaPipe) {
          // Face-API.js detection
          const detections = await currentService.detectFaces(videoElement.value)

          faceDetected.value = detections.length > 0

          // Draw detections on canvas
          if (canvasElement.value) {
            const displaySize = currentService.getDisplaySize(videoElement.value)
            currentService.drawDetections(canvasElement.value, detections, displaySize)
          }

          if (detections.length > 0 && !gesturePrompt.value) {
            promptGesture()
          }
        }
        // MediaPipe handles detection in the callback
      } catch (err) {
        console.error('Face detection error:', err)
      }

      // Continue detection loop
      if (cameraActive.value) {
        detectionLoop.value = requestAnimationFrame(detectFaces)
      }
    }

    detectionLoop.value = requestAnimationFrame(detectFaces)
  }

  const promptGesture = () => {
    const gestures = ['nod your head', 'shake your head', 'smile', 'blink']
    const randomGesture = gestures[Math.floor(Math.random() * gestures.length)]

    gesturePrompt.value = {
      action: randomGesture,
      detected: false,
    }

    // Simulate gesture detection progress
    const progressInterval = setInterval(() => {
      gestureProgress.value += 10
      if (gestureProgress.value >= 100) {
        clearInterval(progressInterval)
        gesturePrompt.value.detected = true
      }
    }, 200)
  }

  const captureAndVerify = async () => {
    processing.value = true
    error.value = null

    try {
      if (!videoElement.value) {
        throw new Error('Camera not available')
      }

      // Capture face data using the current service
      const faceData = useMediaPipe
        ? await currentService.captureFaceData(videoElement.value)
        : await currentService.captureFaceDescriptor(videoElement.value, 'current')

      // Try to recognize against known faces
      const recognitionResult = await currentService.recognizeFace(
        videoElement.value,
        knownFaces.value
      )

      if (recognitionResult.success) {
        console.log('Face recognized:', recognitionResult.employeeId)
        return {
          success: true,
          employeeId: recognitionResult.employeeId,
          confidence: recognitionResult.confidence,
          faceData: faceData,
          gestureCompleted: gesturePrompt.value?.detected || false,
        }
      } else {
        // Face not recognized - could be registration mode
        return {
          success: false,
          message: recognitionResult.message,
          faceData: faceData,
          gestureCompleted: gesturePrompt.value?.detected || false,
        }
      }
    } catch (err) {
      error.value = 'Face verification failed: ' + err.message
      console.error('Face verification error:', err)
      throw err
    } finally {
      processing.value = false
    }
  }

  const registerFace = async (employeeId) => {
    try {
      if (!videoElement.value) {
        throw new Error('Camera not available')
      }

      const faceData = useMediaPipe
        ? await currentService.captureFaceData(videoElement.value)
        : await currentService.captureFaceDescriptor(videoElement.value, employeeId)

      // Add to known faces
      const descriptor = useMediaPipe
        ? currentService.calculateFaceDescriptor(faceData)
        : faceData.descriptor

      knownFaces.value.push({
        employeeId: employeeId,
        descriptor: descriptor,
      })

      return {
        success: true,
        faceData: faceData,
        message: 'Face registered successfully',
      }
    } catch (err) {
      error.value = 'Face registration failed: ' + err.message
      throw err
    }
  }

  const loadKnownFaces = async (faces) => {
    knownFaces.value = faces
  }

  const verifyLocation = async () => {
    return new Promise((resolve, reject) => {
      if (!navigator.geolocation) {
        reject(new Error('Geolocation not supported'))
        return
      }

      navigator.geolocation.getCurrentPosition(
        (position) => {
          const locationData = {
            latitude: position.coords.latitude,
            longitude: position.coords.longitude,
            accuracy: position.coords.accuracy,
          }
          resolve(locationData)
        },
        (err) => {
          reject(new Error('Location access denied: ' + err.message))
        },
        {
          enableHighAccuracy: true,
          timeout: 10000,
          maximumAge: 0,
        }
      )
    })
  }

  return {
    cameraActive,
    faceDetected,
    processing,
    error,
    gesturePrompt,
    gestureProgress,
    videoElement,
    canvasElement,
    startCamera,
    stopCamera,
    captureAndVerify,
    registerFace,
    loadKnownFaces,
    verifyLocation,
    knownFaces,
    currentService,
  }
}
