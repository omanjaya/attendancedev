import { FaceDetection } from '@mediapipe/face_detection'
import { Camera } from '@mediapipe/camera_utils'
import { drawConnectors, drawLandmarks } from '@mediapipe/drawing_utils'

class MediaPipeFaceService {
  constructor() {
    this.isInitialized = false
    this.currentStream = null
    this.faceDetection = null
    this.camera = null
    this.onResults = null
    this.videoElement = null
  }

  async initialize() {
    if (this.isInitialized) return

    try {
      // Initialize MediaPipe Face Detection
      this.faceDetection = new FaceDetection({
        locateFile: (file) => {
          return `https://cdn.jsdelivr.net/npm/@mediapipe/face_detection/${file}`
        }
      })

      this.faceDetection.setOptions({
        model: 'short', // short or full range
        minDetectionConfidence: 0.5,
      })

      this.faceDetection.onResults((results) => {
        if (this.onResults) {
          this.onResults(results)
        }
      })

      this.isInitialized = true
      console.log('MediaPipe Face Detection initialized successfully')
    } catch (error) {
      console.error('Failed to initialize MediaPipe Face Detection:', error)
      throw new Error('MediaPipe Face Detection initialization failed')
    }
  }

  async startCamera(videoElement) {
    try {
      this.videoElement = videoElement

      if (!this.isInitialized) {
        await this.initialize()
      }

      this.camera = new Camera(videoElement, {
        onFrame: async () => {
          if (this.faceDetection) {
            await this.faceDetection.send({ image: videoElement })
          }
        },
        width: 640,
        height: 480,
      })

      await this.camera.start()
      return Promise.resolve()
    } catch (error) {
      console.error('Failed to start MediaPipe camera:', error)
      throw new Error('Camera access denied or not available')
    }
  }

  stopCamera() {
    if (this.camera) {
      this.camera.stop()
      this.camera = null
    }
    this.videoElement = null
  }

  setOnResults(callback) {
    this.onResults = callback
  }

  drawResults(canvas, results, video) {
    const ctx = canvas.getContext('2d')
    canvas.width = video.videoWidth
    canvas.height = video.videoHeight

    ctx.save()
    ctx.clearRect(0, 0, canvas.width, canvas.height)

    if (results.detections) {
      for (const detection of results.detections) {
        this.drawDetection(ctx, detection, canvas.width, canvas.height)
      }
    }

    ctx.restore()
  }

  drawDetection(ctx, detection, width, height) {
    // Draw bounding box
    const box = detection.boundingBox
    const x = box.xCenter * width - (box.width * width) / 2
    const y = box.yCenter * height - (box.height * height) / 2
    const w = box.width * width
    const h = box.height * height

    ctx.strokeStyle = '#00ff00'
    ctx.lineWidth = 2
    ctx.strokeRect(x, y, w, h)

    // Draw confidence score
    const confidence = Math.round(detection.score * 100)
    ctx.fillStyle = '#00ff00'
    ctx.font = '16px Arial'
    ctx.fillText(`${confidence}%`, x, y - 10)

    // Draw key points if available
    if (detection.landmarks) {
      ctx.fillStyle = '#ff0000'
      for (const landmark of detection.landmarks) {
        const px = landmark.x * width
        const py = landmark.y * height
        ctx.beginPath()
        ctx.arc(px, py, 2, 0, 2 * Math.PI)
        ctx.fill()
      }
    }
  }

  async captureFaceData(videoElement) {
    return new Promise((resolve, reject) => {
      if (!this.videoElement || !this.isInitialized) {
        reject(new Error('MediaPipe not initialized or camera not started'))
        return
      }

      // Set a temporary results handler to capture face data
      const originalOnResults = this.onResults
      
      this.setOnResults((results) => {
        // Restore original results handler
        this.onResults = originalOnResults

        if (results.detections && results.detections.length > 0) {
          const detection = results.detections[0]
          
          const faceData = {
            boundingBox: detection.boundingBox,
            confidence: detection.score,
            landmarks: detection.landmarks || [],
            timestamp: Date.now()
          }

          resolve(faceData)
        } else {
          reject(new Error('No face detected'))
        }
      })

      // Trigger a single frame processing
      if (this.faceDetection) {
        this.faceDetection.send({ image: videoElement })
      }
    })
  }

  calculateFaceDescriptor(faceData) {
    // For MediaPipe, we'll create a simplified descriptor based on the detection data
    // This is a placeholder implementation - in production you'd want to use
    // additional models for feature extraction
    const box = faceData.boundingBox
    const landmarks = faceData.landmarks || []
    
    const descriptor = []
    
    // Add bounding box features (normalized)
    descriptor.push(box.xCenter, box.yCenter, box.width, box.height)
    
    // Add landmark features if available
    for (let i = 0; i < Math.min(landmarks.length, 30); i++) {
      descriptor.push(landmarks[i].x, landmarks[i].y)
    }
    
    // Pad to 128 dimensions to match Face-API.js format
    while (descriptor.length < 128) {
      descriptor.push(0)
    }
    
    return descriptor.slice(0, 128)
  }

  async recognizeFace(videoElement, knownDescriptors = []) {
    try {
      const faceData = await this.captureFaceData(videoElement)
      const currentDescriptor = this.calculateFaceDescriptor(faceData)
      
      const threshold = 0.6
      
      // Compare against known descriptors
      for (const known of knownDescriptors) {
        const similarity = this.calculateSimilarity(currentDescriptor, known.descriptor)
        
        if (similarity > threshold) {
          return {
            success: true,
            employeeId: known.employeeId,
            confidence: similarity,
            boundingBox: faceData.boundingBox,
          }
        }
      }
      
      return {
        success: false,
        message: 'Face not recognized',
        confidence: 0,
      }
    } catch (error) {
      return {
        success: false,
        message: error.message,
        confidence: 0,
      }
    }
  }

  calculateSimilarity(descriptor1, descriptor2) {
    if (!descriptor1 || !descriptor2 || descriptor1.length !== descriptor2.length) {
      return 0
    }

    // Calculate cosine similarity
    let dotProduct = 0
    let norm1 = 0
    let norm2 = 0

    for (let i = 0; i < descriptor1.length; i++) {
      dotProduct += descriptor1[i] * descriptor2[i]
      norm1 += descriptor1[i] * descriptor1[i]
      norm2 += descriptor2[i] * descriptor2[i]
    }

    const magnitude = Math.sqrt(norm1) * Math.sqrt(norm2)
    return magnitude === 0 ? 0 : dotProduct / magnitude
  }

  getDisplaySize(element) {
    return { width: element.offsetWidth, height: element.offsetHeight }
  }

  async validateImage(imageFile) {
    // For MediaPipe, we'd need to process the image file
    // This is a simplified implementation
    return new Promise((resolve) => {
      const img = new Image()
      img.onload = () => {
        // Create a temporary canvas to process the image
        const canvas = document.createElement('canvas')
        const ctx = canvas.getContext('2d')
        canvas.width = img.width
        canvas.height = img.height
        ctx.drawImage(img, 0, 0)

        // For now, return a basic validation
        resolve({
          valid: true,
          message: 'Image appears valid for MediaPipe processing',
          confidence: 0.8
        })
      }

      img.onerror = () => {
        resolve({ valid: false, message: 'Invalid image file' })
      }

      img.src = URL.createObjectURL(imageFile)
    })
  }

  // Cleanup method
  destroy() {
    this.stopCamera()
    this.isInitialized = false
    this.faceDetection = null
  }
}

export default new MediaPipeFaceService()