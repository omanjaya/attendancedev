/**
 * Advanced Liveness Detection System
 * Implements multiple anti-spoofing techniques
 */

class LivenessDetectionService {
  constructor() {
    this.gestures = {
      BLINK: 'blink',
      SMILE: 'smile',
      TURN_HEAD: 'turnHead',
      NOD: 'nod',
      OPEN_MOUTH: 'openMouth',
    }

    this.thresholds = {
      blinkDuration: { min: 100, max: 400 }, // milliseconds
      smileIntensity: 0.6,
      headTurnAngle: 15, // degrees
      nodDistance: 10, // pixels
      mouthOpenRatio: 0.3,
    }

    this.detectionHistory = []
    this.gestureStates = {}
    this.isActive = false
  }

  /**
   * Start liveness detection with random gesture prompt
   */
  async startLivenessCheck(videoElement, options = {}) {
    const {
      requiredGestures = 2,
      timeout = 30000,
      onProgress = null,
      onGesturePrompt = null,
    } = options

    this.isActive = true
    this.detectionHistory = []
    this.gestureStates = {}

    const gestures = this.selectRandomGestures(requiredGestures)
    const results = {
      success: false,
      completedGestures: [],
      failedGestures: [],
      overallScore: 0,
      detectionTime: 0,
    }

    const startTime = Date.now()

    try {
      for (const gesture of gestures) {
        if (!this.isActive) break

        if (onGesturePrompt) {
          onGesturePrompt(gesture, this.getGestureInstruction(gesture))
        }

        const gestureResult = await this.detectGesture(videoElement, gesture, {
          timeout: timeout / gestures.length,
          onProgress,
        })

        if (gestureResult.success) {
          results.completedGestures.push(gestureResult)
        } else {
          results.failedGestures.push(gestureResult)
        }
      }

      results.success = results.completedGestures.length >= requiredGestures
      results.overallScore = this.calculateOverallScore(results)
      results.detectionTime = Date.now() - startTime

      return results
    } catch (error) {
      console.error('Liveness detection error:', error)
      return {
        success: false,
        error: error.message,
        detectionTime: Date.now() - startTime,
      }
    } finally {
      this.isActive = false
    }
  }

  /**
   * Detect specific gesture
   */
  async detectGesture(videoElement, gestureType, options = {}) {
    const { timeout = 10000, onProgress = null } = options
    const startTime = Date.now()

    return new Promise((resolve) => {
      const detection = {
        type: gestureType,
        success: false,
        confidence: 0,
        duration: 0,
        attempts: 0,
        frames: [],
      }

      const interval = setInterval(async () => {
        if (!this.isActive || Date.now() - startTime > timeout) {
          clearInterval(interval)
          detection.duration = Date.now() - startTime
          resolve(detection)
          return
        }

        detection.attempts++

        try {
          const frame = await this.captureFrame(videoElement)
          const gestureDetected = await this.analyzeGesture(frame, gestureType)

          detection.frames.push({
            timestamp: Date.now(),
            detected: gestureDetected.detected,
            confidence: gestureDetected.confidence,
            data: gestureDetected.data,
          })

          if (gestureDetected.detected && gestureDetected.confidence > 0.7) {
            detection.success = true
            detection.confidence = gestureDetected.confidence
            detection.duration = Date.now() - startTime

            clearInterval(interval)
            resolve(detection)
          }

          if (onProgress) {
            onProgress({
              gestureType,
              progress: Math.min(detection.attempts / 10, 1),
              confidence: gestureDetected.confidence,
              detected: gestureDetected.detected,
            })
          }
        } catch (error) {
          console.error(`Gesture detection error (${gestureType}):`, error)
        }
      }, 200) // Check every 200ms
    })
  }

  /**
   * Analyze specific gesture in frame
   */
  async analyzeGesture(frame, gestureType) {
    switch (gestureType) {
      case this.gestures.BLINK:
        return this.detectBlink(frame)
      case this.gestures.SMILE:
        return this.detectSmile(frame)
      case this.gestures.TURN_HEAD:
        return this.detectHeadTurn(frame)
      case this.gestures.NOD:
        return this.detectNod(frame)
      case this.gestures.OPEN_MOUTH:
        return this.detectMouthOpen(frame)
      default:
        return { detected: false, confidence: 0, data: null }
    }
  }

  /**
   * Detect blink gesture
   */
  async detectBlink(frame) {
    // Simulate blink detection
    // In production, this would analyze eye landmarks
    const blinkDetected = Math.random() > 0.7
    const confidence = blinkDetected ? 0.8 + Math.random() * 0.2 : Math.random() * 0.5

    return {
      detected: blinkDetected,
      confidence,
      data: {
        eyeAspectRatio: blinkDetected ? 0.15 : 0.3,
        duration: blinkDetected ? 150 + Math.random() * 100 : 0,
      },
    }
  }

  /**
   * Detect smile gesture
   */
  async detectSmile(frame) {
    // Simulate smile detection
    const smileDetected = Math.random() > 0.6
    const confidence = smileDetected ? 0.7 + Math.random() * 0.3 : Math.random() * 0.4

    return {
      detected: smileDetected,
      confidence,
      data: {
        mouthCornerLift: smileDetected ? 0.8 : 0.3,
        intensity: smileDetected ? 0.6 + Math.random() * 0.4 : 0.2,
      },
    }
  }

  /**
   * Detect head turn gesture
   */
  async detectHeadTurn(frame) {
    // Simulate head turn detection
    const turnDetected = Math.random() > 0.65
    const confidence = turnDetected ? 0.75 + Math.random() * 0.25 : Math.random() * 0.5

    return {
      detected: turnDetected,
      confidence,
      data: {
        yawAngle: turnDetected ? 20 + Math.random() * 10 : Math.random() * 5,
        direction: turnDetected ? (Math.random() > 0.5 ? 'left' : 'right') : 'center',
      },
    }
  }

  /**
   * Detect nod gesture
   */
  async detectNod(frame) {
    // Simulate nod detection
    const nodDetected = Math.random() > 0.7
    const confidence = nodDetected ? 0.8 + Math.random() * 0.2 : Math.random() * 0.4

    return {
      detected: nodDetected,
      confidence,
      data: {
        pitchAngle: nodDetected ? 15 + Math.random() * 5 : Math.random() * 3,
        movement: nodDetected ? 'down' : 'stable',
      },
    }
  }

  /**
   * Detect mouth open gesture
   */
  async detectMouthOpen(frame) {
    // Simulate mouth open detection
    const mouthOpenDetected = Math.random() > 0.75
    const confidence = mouthOpenDetected ? 0.85 + Math.random() * 0.15 : Math.random() * 0.3

    return {
      detected: mouthOpenDetected,
      confidence,
      data: {
        mouthOpenRatio: mouthOpenDetected ? 0.4 + Math.random() * 0.3 : 0.1,
        aperture: mouthOpenDetected ? 'wide' : 'closed',
      },
    }
  }

  /**
   * Select random gestures for liveness check
   */
  selectRandomGestures(count) {
    const available = Object.values(this.gestures)
    const selected = []

    while (selected.length < count && selected.length < available.length) {
      const gesture = available[Math.floor(Math.random() * available.length)]
      if (!selected.includes(gesture)) {
        selected.push(gesture)
      }
    }

    return selected
  }

  /**
   * Get instruction text for gesture
   */
  getGestureInstruction(gestureType) {
    const instructions = {
      [this.gestures.BLINK]: {
        title: 'Silakan Berkedip',
        description: 'Berkedip beberapa kali secara perlahan',
        icon: '👁️',
        tips: ['Berkedip secara natural', 'Jangan terlalu cepat', 'Pastikan mata terbuka penuh'],
      },
      [this.gestures.SMILE]: {
        title: 'Silakan Tersenyum',
        description: 'Berikan senyuman yang natural',
        icon: '😊',
        tips: [
          'Senyum dengan natural',
          'Tahan beberapa detik',
          'Pastikan kedua sudut mulut terangkat',
        ],
      },
      [this.gestures.TURN_HEAD]: {
        title: 'Putar Kepala',
        description: 'Putar kepala ke kiri dan kanan perlahan',
        icon: '↔️',
        tips: ['Gerakan perlahan', 'Putar sekitar 30 derajat', 'Kembali ke posisi tengah'],
      },
      [this.gestures.NOD]: {
        title: 'Angguk Kepala',
        description: 'Anggukkan kepala naik turun',
        icon: '↕️',
        tips: ['Gerakan naik turun', 'Tidak terlalu cepat', 'Cukup 2-3 kali'],
      },
      [this.gestures.OPEN_MOUTH]: {
        title: 'Buka Mulut',
        description: 'Buka mulut perlahan seperti mengucap "A"',
        icon: '😮',
        tips: ['Buka mulut lebar', 'Tahan beberapa detik', 'Tutup kembali perlahan'],
      },
    }

    return (
      instructions[gestureType] || {
        title: 'Instruksi Tidak Dikenali',
        description: 'Silakan ikuti instruksi yang diberikan',
        icon: '❓',
        tips: [],
      }
    )
  }

  /**
   * Calculate overall liveness score
   */
  calculateOverallScore(results) {
    const { completedGestures, failedGestures } = results
    const totalGestures = completedGestures.length + failedGestures.length

    if (totalGestures === 0) return 0

    const baseScore = (completedGestures.length / totalGestures) * 100

    // Bonus for high confidence gestures
    const confidenceBonus = completedGestures.reduce((sum, gesture) => {
      return sum + (gesture.confidence > 0.8 ? 5 : 0)
    }, 0)

    // Penalty for too many attempts
    const attemptPenalty = completedGestures.reduce((sum, gesture) => {
      return sum + (gesture.attempts > 15 ? 10 : 0)
    }, 0)

    return Math.min(100, Math.max(0, baseScore + confidenceBonus - attemptPenalty))
  }

  /**
   * Capture frame from video element
   */
  async captureFrame(videoElement) {
    const canvas = document.createElement('canvas')
    const ctx = canvas.getContext('2d')

    canvas.width = videoElement.videoWidth
    canvas.height = videoElement.videoHeight
    ctx.drawImage(videoElement, 0, 0)

    return {
      canvas,
      imageData: ctx.getImageData(0, 0, canvas.width, canvas.height),
      timestamp: Date.now(),
      width: canvas.width,
      height: canvas.height,
    }
  }

  /**
   * Anti-spoofing texture analysis
   */
  async analyzeTexture(frame) {
    // Simulate texture analysis for anti-spoofing
    // In production, this would analyze image texture patterns
    const textureScore = 0.7 + Math.random() * 0.3
    const isReal = textureScore > 0.75

    return {
      textureScore,
      isReal,
      patterns: {
        skinTexture: isReal ? 'natural' : 'artificial',
        eyeReflection: isReal ? 'present' : 'absent',
        microMovements: isReal ? 'detected' : 'missing',
      },
    }
  }

  /**
   * Depth analysis for 3D liveness
   */
  async analyzeDepth(frame) {
    // Simulate depth analysis
    const depthScore = 0.8 + Math.random() * 0.2
    const hasDepth = depthScore > 0.85

    return {
      depthScore,
      hasDepth,
      measurements: {
        noseProtrusion: hasDepth ? 15 + Math.random() * 5 : 5,
        eyeSocket: hasDepth ? 8 + Math.random() * 3 : 2,
        faceContour: hasDepth ? 'pronounced' : 'flat',
      },
    }
  }

  /**
   * Challenge-response liveness
   */
  async performChallengeResponse(videoElement, challenges = ['color', 'number', 'direction']) {
    const results = []

    for (const challenge of challenges) {
      const result = await this.executeChallenge(videoElement, challenge)
      results.push(result)
    }

    const successRate = results.filter((r) => r.success).length / results.length

    return {
      success: successRate >= 0.7,
      successRate,
      challenges: results,
      overallScore: Math.round(successRate * 100),
    }
  }

  /**
   * Execute specific challenge
   */
  async executeChallenge(videoElement, challengeType) {
    switch (challengeType) {
      case 'color':
        return this.colorChallenge(videoElement)
      case 'number':
        return this.numberChallenge(videoElement)
      case 'direction':
        return this.directionChallenge(videoElement)
      default:
        return { success: false, error: 'Unknown challenge type' }
    }
  }

  /**
   * Color-based challenge
   */
  async colorChallenge(videoElement) {
    const colors = ['red', 'blue', 'green', 'yellow']
    const targetColor = colors[Math.floor(Math.random() * colors.length)]

    // Simulate color detection
    const success = Math.random() > 0.3

    return {
      type: 'color',
      success,
      targetColor,
      detectedColor: success ? targetColor : colors[Math.floor(Math.random() * colors.length)],
      confidence: success ? 0.8 + Math.random() * 0.2 : Math.random() * 0.5,
      instruction: `Tunjukkan objek berwarna ${targetColor}`,
    }
  }

  /**
   * Number-based challenge
   */
  async numberChallenge(videoElement) {
    const targetNumber = Math.floor(Math.random() * 5) + 1

    // Simulate finger counting detection
    const success = Math.random() > 0.4

    return {
      type: 'number',
      success,
      targetNumber,
      detectedNumber: success ? targetNumber : Math.floor(Math.random() * 5) + 1,
      confidence: success ? 0.85 + Math.random() * 0.15 : Math.random() * 0.6,
      instruction: `Tunjukkan ${targetNumber} jari`,
    }
  }

  /**
   * Direction-based challenge
   */
  async directionChallenge(videoElement) {
    const directions = ['left', 'right', 'up', 'down']
    const targetDirection = directions[Math.floor(Math.random() * directions.length)]

    // Simulate direction detection
    const success = Math.random() > 0.35

    return {
      type: 'direction',
      success,
      targetDirection,
      detectedDirection: success
        ? targetDirection
        : directions[Math.floor(Math.random() * directions.length)],
      confidence: success ? 0.8 + Math.random() * 0.2 : Math.random() * 0.5,
      instruction: `Arahkan kepala ke ${targetDirection}`,
    }
  }

  /**
   * Stop liveness detection
   */
  stop() {
    this.isActive = false
  }

  /**
   * Get detection statistics
   */
  getStats() {
    return {
      supportedGestures: Object.values(this.gestures),
      thresholds: this.thresholds,
      historyLength: this.detectionHistory.length,
      isActive: this.isActive,
    }
  }

  /**
   * Clean up resources
   */
  cleanup() {
    this.stop()
    this.detectionHistory = []
    this.gestureStates = {}
  }
}

// Export singleton instance
export default new LivenessDetectionService()

// Export class for custom instances
export { LivenessDetectionService }
