import * as faceapi from 'face-api.js'

class FaceDetectionService {
  constructor() {
    this.isInitialized = false
    this.currentStream = null
    this.faceDescriptors = new Map()
    this.livenessDetection = {
      isActive: false,
      gesturePrompts: ['blink', 'smile', 'nod'],
      currentGesture: null,
      gestureStartTime: null,
      gestureTimeout: 5000, // 5 seconds
      minLivenessScore: 0.8
    }
  }

  async initialize() {
    if (this.isInitialized) return

    try {
      // Load face-api.js models
      await Promise.all([
        faceapi.nets.tinyFaceDetector.loadFromUri('/models'),
        faceapi.nets.faceLandmark68Net.loadFromUri('/models'),
        faceapi.nets.faceRecognitionNet.loadFromUri('/models'),
        faceapi.nets.faceExpressionNet.loadFromUri('/models'),
        faceapi.nets.ssdMobilenetv1.loadFromUri('/models'),
      ])

      this.isInitialized = true
      console.log('Face detection models loaded successfully')
    } catch (error) {
      console.error('Failed to load face detection models:', error)
      throw new Error('Face detection initialization failed')
    }
  }

  async startCamera(videoElement) {
    try {
      const constraints = {
        video: {
          width: { ideal: 640 },
          height: { ideal: 480 },
          facingMode: 'user',
        },
      }

      this.currentStream = await navigator.mediaDevices.getUserMedia(constraints)
      videoElement.srcObject = this.currentStream

      return new Promise((resolve) => {
        videoElement.onloadedmetadata = () => {
          videoElement.play()
          resolve()
        }
      })
    } catch (error) {
      console.error('Failed to start camera:', error)
      throw new Error('Camera access denied or not available')
    }
  }

  stopCamera() {
    if (this.currentStream) {
      this.currentStream.getTracks().forEach((track) => track.stop())
      this.currentStream = null
    }
  }

  async detectFaces(input) {
    if (!this.isInitialized) {
      throw new Error('Face detection not initialized')
    }

    try {
      const detections = await faceapi
        .detectAllFaces(input, new faceapi.TinyFaceDetectorOptions())
        .withFaceLandmarks()
        .withFaceDescriptors()
        .withFaceExpressions()

      return detections
    } catch (error) {
      console.error('Face detection failed:', error)
      return []
    }
  }

  async captureFaceDescriptor(videoElement, employeeId, options = {}) {
    const detections = await this.detectFaces(videoElement)

    if (detections.length === 0) {
      throw new Error('No face detected. Please ensure your face is clearly visible.')
    }

    if (detections.length > 1) {
      throw new Error('Multiple faces detected. Please ensure only one person is in frame.')
    }

    const detection = detections[0]
    const faceDescriptor = detection.descriptor
    this.faceDescriptors.set(employeeId, faceDescriptor)

    // Enhanced face data with liveness detection
    const faceData = {
      descriptor: Array.from(faceDescriptor),
      landmarks: detection.landmarks.positions,
      confidence: detection.detection.score,
      pose: this.extractPoseData(detection),
      expressions: detection.expressions ? detection.expressions.asSortedArray() : null,
      face_bounds: {
        x: detection.detection.box.x,
        y: detection.detection.box.y,
        width: detection.detection.box.width,
        height: detection.detection.box.height
      },
      timestamp: Date.now()
    }

    // Add liveness detection if enabled
    if (options.requireLiveness) {
      const livenessResult = await this.performLivenessCheck(videoElement)
      faceData.liveness = livenessResult
    }

    return faceData
  }

  async recognizeFace(videoElement, knownDescriptors = []) {
    const detections = await this.detectFaces(videoElement)

    if (detections.length === 0) {
      return { success: false, message: 'No face detected' }
    }

    if (detections.length > 1) {
      return { success: false, message: 'Multiple faces detected' }
    }

    const detection = detections[0]
    const threshold = 0.6 // Similarity threshold

    // Compare against known descriptors
    for (const known of knownDescriptors) {
      const distance = faceapi.euclideanDistance(detection.descriptor, known.descriptor)
      const similarity = 1 - distance

      if (similarity > threshold) {
        return {
          success: true,
          employeeId: known.employeeId,
          confidence: similarity,
          landmarks: detection.landmarks.positions,
        }
      }
    }

    return {
      success: false,
      message: 'Face not recognized',
      confidence: 0,
    }
  }

  drawDetections(canvas, detections, displaySize) {
    const ctx = canvas.getContext('2d')
    ctx.clearRect(0, 0, canvas.width, canvas.height)

    if (detections.length === 0) return

    // Resize detections to match display size
    const resizedDetections = faceapi.resizeResults(detections, displaySize)

    // Draw face boxes
    resizedDetections.forEach((detection) => {
      const box = detection.detection.box
      ctx.strokeStyle = '#00ff00'
      ctx.lineWidth = 2
      ctx.strokeRect(box.x, box.y, box.width, box.height)

      // Draw confidence score
      const confidence = Math.round(detection.detection.score * 100)
      ctx.fillStyle = '#00ff00'
      ctx.font = '16px Arial'
      ctx.fillText(`${confidence}%`, box.x, box.y - 10)

      // Draw landmarks
      if (detection.landmarks) {
        const landmarks = detection.landmarks.positions
        ctx.fillStyle = '#ff0000'
        landmarks.forEach((point) => {
          ctx.beginPath()
          ctx.arc(point.x, point.y, 1, 0, 2 * Math.PI)
          ctx.fill()
        })
      }

      // Draw expressions
      if (detection.expressions) {
        const expressions = detection.expressions.asSortedArray()
        const topExpression = expressions[0]
        ctx.fillStyle = '#0099ff'
        ctx.fillText(
          `${topExpression.expression}: ${Math.round(topExpression.probability * 100)}%`,
          box.x,
          box.y + box.height + 20
        )
      }
    })
  }

  async validateImage(imageFile) {
    return new Promise((resolve) => {
      const img = new Image()
      img.onload = async () => {
        try {
          const detections = await this.detectFaces(img)

          if (detections.length === 0) {
            resolve({ valid: false, message: 'No face detected in image' })
            return
          }

          if (detections.length > 1) {
            resolve({ valid: false, message: 'Multiple faces detected in image' })
            return
          }

          const detection = detections[0]
          const minConfidence = 0.5

          if (detection.detection.score < minConfidence) {
            resolve({
              valid: false,
              message: `Face detection confidence too low: ${Math.round(detection.detection.score * 100)}%`,
            })
            return
          }

          resolve({
            valid: true,
            descriptor: Array.from(detection.descriptor),
            confidence: detection.detection.score,
          })
        } catch (error) {
          resolve({ valid: false, message: 'Error processing image' })
        }
      }

      img.onerror = () => {
        resolve({ valid: false, message: 'Invalid image file' })
      }

      img.src = URL.createObjectURL(imageFile)
    })
  }

  // Utility methods
  getDisplaySize(element) {
    return { width: element.offsetWidth, height: element.offsetHeight }
  }

  async downloadModels() {
    // This would be called during app initialization to ensure models are cached
    if (!this.isInitialized) {
      await this.initialize()
    }
  }

  // Enhanced liveness detection methods
  async performLivenessCheck(videoElement) {
    return new Promise((resolve) => {
      const livenessData = {
        blink_detected: false,
        head_movement: 0,
        gesture_completed: false,
        liveness_score: 0,
        checks_performed: []
      }

      // Simple blink detection placeholder
      // In production, implement actual computer vision algorithms
      const blinkDetection = this.detectBlink(videoElement)
      livenessData.blink_detected = blinkDetection.detected
      livenessData.checks_performed.push('blink')

      // Calculate liveness score
      let score = 0.5 // Base score
      if (livenessData.blink_detected) score += 0.3
      
      livenessData.liveness_score = Math.min(score, 1.0)
      livenessData.is_live = score >= this.livenessDetection.minLivenessScore

      resolve(livenessData)
    })
  }

  detectBlink(videoElement) {
    // Placeholder for blink detection
    // In production, analyze eye aspect ratio from landmarks
    return {
      detected: Math.random() > 0.3, // Simulate blink detection
      confidence: Math.random() * 0.4 + 0.6
    }
  }

  extractPoseData(detection) {
    // Extract head pose information from landmarks
    if (!detection.landmarks) return null

    // Simplified pose estimation
    const landmarks = detection.landmarks.positions
    const leftEye = landmarks.slice(36, 42)
    const rightEye = landmarks.slice(42, 48)
    const nose = landmarks.slice(27, 36)

    // Calculate approximate yaw and pitch
    const eyeCenter = {
      x: (leftEye[0].x + rightEye[0].x) / 2,
      y: (leftEye[0].y + rightEye[0].y) / 2
    }
    const noseCenter = nose[3] // Nose tip

    const yaw = Math.atan2(noseCenter.x - eyeCenter.x, 100) * (180 / Math.PI)
    const pitch = Math.atan2(noseCenter.y - eyeCenter.y, 100) * (180 / Math.PI)

    return {
      yaw: yaw,
      pitch: pitch,
      roll: 0 // Simplified
    }
  }

  async analyzeImageQuality(videoElement) {
    // Placeholder for image quality analysis
    const canvas = document.createElement('canvas')
    const ctx = canvas.getContext('2d')
    
    canvas.width = videoElement.videoWidth || 640
    canvas.height = videoElement.videoHeight || 480
    ctx.drawImage(videoElement, 0, 0, canvas.width, canvas.height)
    
    const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height)
    
    return {
      lighting_score: this.calculateLightingScore(imageData),
      blur_score: this.calculateBlurScore(imageData),
      noise_level: this.calculateNoiseLevel(imageData)
    }
  }

  calculateLightingScore(imageData) {
    // Simple brightness analysis
    let total = 0
    const pixels = imageData.data
    
    for (let i = 0; i < pixels.length; i += 4) {
      const brightness = (pixels[i] + pixels[i + 1] + pixels[i + 2]) / 3
      total += brightness
    }
    
    const avgBrightness = total / (pixels.length / 4)
    // Score based on optimal brightness range (80-180)
    return Math.max(0, 1 - Math.abs(avgBrightness - 130) / 130)
  }

  calculateBlurScore(imageData) {
    // Simplified blur detection using variance
    const pixels = imageData.data
    let variance = 0
    let mean = 0
    
    // Calculate mean
    for (let i = 0; i < pixels.length; i += 4) {
      mean += (pixels[i] + pixels[i + 1] + pixels[i + 2]) / 3
    }
    mean /= (pixels.length / 4)
    
    // Calculate variance
    for (let i = 0; i < pixels.length; i += 4) {
      const brightness = (pixels[i] + pixels[i + 1] + pixels[i + 2]) / 3
      variance += Math.pow(brightness - mean, 2)
    }
    variance /= (pixels.length / 4)
    
    // Higher variance indicates less blur
    return Math.min(variance / 10000, 1)
  }

  calculateNoiseLevel(imageData) {
    // Simplified noise detection
    return Math.random() * 0.3 // Placeholder
  }

  // API integration methods
  async registerFaceAPI(employeeId, faceData, image = null) {
    const formData = new FormData()
    formData.append('employee_id', employeeId)
    formData.append('face_data', JSON.stringify(faceData))
    
    if (image) {
      formData.append('face_image', image)
    }

    const response = await fetch('/api/v1/face-detection/register', {
      method: 'POST',
      body: formData,
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      }
    })

    return response.json()
  }

  async verifyFaceAPI(faceData, options = {}) {
    const response = await fetch('/api/v1/face-detection/verify', {
      method: 'POST',
      body: JSON.stringify({
        face_data: faceData,
        require_liveness: options.requireLiveness || true,
        location: options.location || null
      }),
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      }
    })

    return response.json()
  }

  async getPerformanceMetrics(days = 30) {
    const response = await fetch(`/api/v1/face-detection/performance-metrics?days=${days}`, {
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      }
    })

    return response.json()
  }

  // Cleanup method
  destroy() {
    this.stopCamera()
    this.faceDescriptors.clear()
    this.livenessDetection.isActive = false
    this.isInitialized = false
  }
}

export default new FaceDetectionService()
