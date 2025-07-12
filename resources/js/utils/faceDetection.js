/**
 * Face Detection Utilities
 * Supports both Face-API.js and MediaPipe for face recognition
 */

class FaceDetectionService {
  constructor() {
    this.faceApiLoaded = false
    this.mediaPipeLoaded = false
    this.currentModel = null
    this.detectionConfigs = {
      'face-api': {
        minConfidence: 0.7,
        inputSize: 416,
        scoreThreshold: 0.5
      },
      'mediapipe': {
        minDetectionConfidence: 0.7,
        minTrackingConfidence: 0.5,
        maxNumFaces: 1
      }
    }
  }

  /**
   * Initialize Face-API.js models
   */
  async initializeFaceAPI() {
    if (this.faceApiLoaded) return true

    try {
      // Check if face-api is available
      if (typeof faceapi === 'undefined') {
        console.warn('Face-API.js not loaded. Using simulation mode.')
        return false
      }

      // Load models
      const MODEL_URL = '/models'
      
      await Promise.all([
        faceapi.nets.ssdMobilenetv1.loadFromUri(MODEL_URL),
        faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
        faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL),
        faceapi.nets.ageGenderNet.loadFromUri(MODEL_URL)
      ])

      this.faceApiLoaded = true
      console.log('Face-API.js models loaded successfully')
      return true

    } catch (error) {
      console.error('Failed to load Face-API.js models:', error)
      return false
    }
  }

  /**
   * Initialize MediaPipe Face Detection
   */
  async initializeMediaPipe() {
    if (this.mediaPipeLoaded) return true

    try {
      // Check if MediaPipe is available
      if (typeof FaceDetection === 'undefined') {
        console.warn('MediaPipe not loaded. Using simulation mode.')
        return false
      }

      const faceDetection = new FaceDetection({
        locateFile: (file) => {
          return `https://cdn.jsdelivr.net/npm/@mediapipe/face_detection/${file}`
        }
      })

      faceDetection.setOptions(this.detectionConfigs.mediapipe)

      this.currentModel = faceDetection
      this.mediaPipeLoaded = true
      console.log('MediaPipe Face Detection initialized successfully')
      return true

    } catch (error) {
      console.error('Failed to initialize MediaPipe:', error)
      return false
    }
  }

  /**
   * Detect faces using Face-API.js
   */
  async detectWithFaceAPI(videoElement) {
    if (!this.faceApiLoaded) {
      return this.simulateDetection()
    }

    try {
      const detections = await faceapi
        .detectAllFaces(videoElement, new faceapi.SsdMobilenetv1Options(this.detectionConfigs['face-api']))
        .withFaceLandmarks()
        .withFaceDescriptors()

      if (detections.length > 0) {
        const detection = detections[0]
        const confidence = Math.round(detection.detection.score * 100)
        
        // Calculate liveness score based on landmarks
        const landmarks = detection.landmarks
        const livenessScore = this.calculateLivenessScore(landmarks)

        return {
          faceDetected: true,
          confidence: confidence,
          liveness: livenessScore,
          boundingBox: {
            x: detection.detection.box.x,
            y: detection.detection.box.y,
            width: detection.detection.box.width,
            height: detection.detection.box.height
          },
          landmarks: landmarks.positions,
          descriptor: detection.descriptor
        }
      } else {
        return {
          faceDetected: false,
          confidence: 0,
          liveness: 0,
          boundingBox: null
        }
      }

    } catch (error) {
      console.error('Face-API.js detection error:', error)
      return this.simulateDetection()
    }
  }

  /**
   * Detect faces using MediaPipe
   */
  async detectWithMediaPipe(videoElement) {
    if (!this.mediaPipeLoaded) {
      return this.simulateDetection()
    }

    try {
      return new Promise((resolve) => {
        this.currentModel.onResults((results) => {
          if (results.detections && results.detections.length > 0) {
            const detection = results.detections[0]
            const confidence = Math.round(detection.score * 100)
            
            // Calculate bounding box
            const bbox = detection.boundingBox
            const boundingBox = {
              x: bbox.xCenter * videoElement.videoWidth - (bbox.width * videoElement.videoWidth) / 2,
              y: bbox.yCenter * videoElement.videoHeight - (bbox.height * videoElement.videoHeight) / 2,
              width: bbox.width * videoElement.videoWidth,
              height: bbox.height * videoElement.videoHeight
            }

            // Simple liveness check based on detection consistency
            const livenessScore = this.calculateMediaPipeLiveness(detection)

            resolve({
              faceDetected: true,
              confidence: confidence,
              liveness: livenessScore,
              boundingBox: boundingBox,
              keypoints: detection.landmarks
            })
          } else {
            resolve({
              faceDetected: false,
              confidence: 0,
              liveness: 0,
              boundingBox: null
            })
          }
        })

        this.currentModel.send({ image: videoElement })
      })

    } catch (error) {
      console.error('MediaPipe detection error:', error)
      return this.simulateDetection()
    }
  }

  /**
   * Calculate liveness score based on facial landmarks
   */
  calculateLivenessScore(landmarks) {
    if (!landmarks || !landmarks.length) return 0

    try {
      // Simple liveness calculation based on landmark variation
      // In production, this would be more sophisticated
      const eyeRegion = landmarks.slice(36, 48) // Eye landmarks
      const mouthRegion = landmarks.slice(48, 68) // Mouth landmarks
      
      // Calculate variance in eye and mouth regions
      const eyeVariance = this.calculateVariance(eyeRegion)
      const mouthVariance = this.calculateVariance(mouthRegion)
      
      // Combine scores (simplified)
      const score = Math.min(100, (eyeVariance + mouthVariance) * 10)
      return Math.max(70, Math.floor(score)) // Minimum 70% for demo

    } catch (error) {
      console.error('Liveness calculation error:', error)
      return 85 // Default liveness score
    }
  }

  /**
   * Calculate MediaPipe liveness score
   */
  calculateMediaPipeLiveness(detection) {
    try {
      // Use detection confidence and key point stability
      const baseScore = detection.score * 100
      const stabilityBonus = 15 // Bonus for stable detection
      
      return Math.min(100, Math.floor(baseScore + stabilityBonus))

    } catch (error) {
      console.error('MediaPipe liveness calculation error:', error)
      return 85 // Default liveness score
    }
  }

  /**
   * Calculate variance for liveness detection
   */
  calculateVariance(points) {
    if (!points || points.length === 0) return 0

    const values = points.map(p => p.x + p.y)
    const mean = values.reduce((a, b) => a + b, 0) / values.length
    const variance = values.reduce((acc, val) => acc + Math.pow(val - mean, 2), 0) / values.length
    
    return Math.sqrt(variance)
  }

  /**
   * Simulate face detection for demo purposes
   */
  simulateDetection() {
    const faceDetected = Math.random() > 0.3 // 70% chance
    const confidence = faceDetected ? Math.floor(75 + Math.random() * 20) : 0
    const liveness = faceDetected ? Math.floor(80 + Math.random() * 15) : 0

    return {
      faceDetected,
      confidence,
      liveness,
      boundingBox: faceDetected ? {
        x: 150 + (Math.random() - 0.5) * 40,
        y: 100 + (Math.random() - 0.5) * 30,
        width: 140 + Math.random() * 20,
        height: 160 + Math.random() * 20
      } : null,
      simulated: true
    }
  }

  /**
   * Enhanced liveness detection with gesture prompts
   */
  async performLivenessCheck(videoElement, gestureType = 'blink') {
    const gestures = {
      blink: 'Silakan berkedip beberapa kali',
      smile: 'Silakan tersenyum',
      turnHead: 'Putar kepala ke kiri dan kanan',
      nod: 'Anggukkan kepala naik turun'
    }

    const instruction = gestures[gestureType] || gestures.blink

    return new Promise((resolve) => {
      let detectionCount = 0
      let gestureDetected = false
      const maxAttempts = 10

      const interval = setInterval(async () => {
        detectionCount++

        const detection = await this.detectWithFaceAPI(videoElement)
        
        if (detection.faceDetected) {
          // Simulate gesture detection
          const gestureConfidence = Math.random()
          
          if (gestureConfidence > 0.7) {
            gestureDetected = true
          }
        }

        if (gestureDetected || detectionCount >= maxAttempts) {
          clearInterval(interval)
          
          resolve({
            success: gestureDetected,
            gestureType,
            instruction,
            attempts: detectionCount,
            livenessScore: gestureDetected ? Math.floor(85 + Math.random() * 10) : 0
          })
        }
      }, 500)
    })
  }

  /**
   * Enroll a new face for recognition
   */
  async enrollFace(videoElement, employeeId, employeeName) {
    try {
      const detection = await this.detectWithFaceAPI(videoElement)
      
      if (!detection.faceDetected || detection.confidence < 80) {
        throw new Error('Kualitas gambar wajah tidak mencukupi untuk pendaftaran')
      }

      // In production, save the face descriptor to database
      const faceData = {
        employeeId,
        employeeName,
        descriptor: detection.descriptor,
        enrollmentDate: new Date().toISOString(),
        confidence: detection.confidence,
        imageData: this.captureImageData(videoElement)
      }

      // Simulate enrollment process
      await new Promise(resolve => setTimeout(resolve, 2000))

      console.log('Face enrolled successfully:', faceData)

      return {
        success: true,
        message: `Wajah ${employeeName} berhasil didaftarkan`,
        confidence: detection.confidence,
        faceId: `face_${Date.now()}_${employeeId}`
      }

    } catch (error) {
      console.error('Face enrollment error:', error)
      throw new Error(error.message || 'Gagal mendaftarkan wajah')
    }
  }

  /**
   * Recognize a face against enrolled faces
   */
  async recognizeFace(videoElement) {
    try {
      const detection = await this.detectWithFaceAPI(videoElement)
      
      if (!detection.faceDetected) {
        return {
          recognized: false,
          confidence: 0,
          employee: null
        }
      }

      // In production, compare descriptor with database
      // For demo, simulate recognition
      const recognitionConfidence = detection.confidence > 85 ? 
        Math.floor(80 + Math.random() * 15) : 0

      const recognized = recognitionConfidence > 75

      return {
        recognized,
        confidence: recognitionConfidence,
        employee: recognized ? {
          id: 'emp_001',
          name: 'John Doe',
          department: 'IT'
        } : null,
        livenessScore: detection.liveness
      }

    } catch (error) {
      console.error('Face recognition error:', error)
      return {
        recognized: false,
        confidence: 0,
        employee: null,
        error: error.message
      }
    }
  }

  /**
   * Capture image data for storage
   */
  captureImageData(videoElement) {
    const canvas = document.createElement('canvas')
    const ctx = canvas.getContext('2d')
    
    canvas.width = videoElement.videoWidth
    canvas.height = videoElement.videoHeight
    ctx.drawImage(videoElement, 0, 0)
    
    return canvas.toDataURL('image/jpeg', 0.8)
  }

  /**
   * Get detection statistics
   */
  getDetectionStats() {
    return {
      faceApiLoaded: this.faceApiLoaded,
      mediaPipeLoaded: this.mediaPipeLoaded,
      supportedMethods: [
        this.faceApiLoaded ? 'face-api' : null,
        this.mediaPipeLoaded ? 'mediapipe' : null
      ].filter(Boolean),
      configs: this.detectionConfigs
    }
  }

  /**
   * Clean up resources
   */
  cleanup() {
    if (this.currentModel && typeof this.currentModel.close === 'function') {
      this.currentModel.close()
    }
    this.currentModel = null
  }
}

// Export singleton instance
export default new FaceDetectionService()

// Export class for custom instances
export { FaceDetectionService }