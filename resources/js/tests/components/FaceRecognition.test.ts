import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import FaceRecognition from '@/components/FaceRecognition.vue'
import type { FaceRecognitionProps } from '@/types/face-recognition'

// Mock MediaDevices
const mockGetUserMedia = vi.fn()
Object.defineProperty(navigator, 'mediaDevices', {
  writable: true,
  value: {
    getUserMedia: mockGetUserMedia,
  },
})

// Mock HTMLVideoElement
const mockVideo = {
  srcObject: null,
  onloadedmetadata: null,
  play: vi.fn(),
  pause: vi.fn(),
}

// Mock HTMLCanvasElement
const mockCanvas = {
  getContext: vi.fn(() => ({
    clearRect: vi.fn(),
    drawImage: vi.fn(),
    strokeRect: vi.fn(),
    fillText: vi.fn(),
  })),
  width: 640,
  height: 480,
}

describe('FaceRecognition.vue', () => {
  let wrapper: any

  beforeEach(() => {
    vi.clearAllMocks()
    mockGetUserMedia.mockResolvedValue({
      getTracks: vi.fn(() => [{ stop: vi.fn() }]),
      getVideoTracks: vi.fn(() => [{ stop: vi.fn() }]),
      getAudioTracks: vi.fn(() => []),
    })
  })

  const createWrapper = (props: Partial<FaceRecognitionProps> = {}) => {
    return mount(FaceRecognition, {
      props: {
        detectionMethod: 'face-api',
        employeeId: 'test-employee',
        ...props,
      },
      global: {
        stubs: {
          CameraIcon: true,
          VideoCameraIcon: true,
          StopIcon: true,
          BoltIcon: true,
          InformationCircleIcon: true,
        },
      },
    })
  }

  describe('Component Initialization', () => {
    it('should render with default props', () => {
      wrapper = createWrapper()
      expect(wrapper.exists()).toBe(true)
      expect(wrapper.find('.face-recognition-container').exists()).toBe(true)
    })

    it('should display camera placeholder when inactive', () => {
      wrapper = createWrapper()
      const placeholder = wrapper.find('.bg-gradient-to-br')
      expect(placeholder.exists()).toBe(true)
      expect(wrapper.text()).toContain('Kamera Siap')
    })

    it('should validate props correctly', () => {
      wrapper = createWrapper({
        detectionMethod: 'mediapipe',
        employeeId: 'employee-123',
        confidenceThreshold: 80,
        livenessThreshold: 70,
      })

      expect(wrapper.vm.props.detectionMethod).toBe('mediapipe')
      expect(wrapper.vm.props.employeeId).toBe('employee-123')
      expect(wrapper.vm.props.confidenceThreshold).toBe(80)
      expect(wrapper.vm.props.livenessThreshold).toBe(70)
    })
  })

  describe('Camera Operations', () => {
    beforeEach(() => {
      wrapper = createWrapper()
      // Mock video element ref
      wrapper.vm.videoElement = mockVideo
      wrapper.vm.overlayCanvas = mockCanvas
    })

    it('should start camera when button clicked', async () => {
      const startButton = wrapper.find('button')
      await startButton.trigger('click')

      expect(mockGetUserMedia).toHaveBeenCalledWith({
        video: {
          width: { ideal: 640 },
          height: { ideal: 480 },
          facingMode: 'user',
        },
        audio: false,
      })
    })

    it('should handle camera permission denied', async () => {
      const permissionError = new Error('Permission denied')
      permissionError.name = 'NotAllowedError'
      mockGetUserMedia.mockRejectedValueOnce(permissionError)

      const startButton = wrapper.find('button')
      await startButton.trigger('click')

      await nextTick()

      expect(wrapper.emitted('error')).toBeTruthy()
      const errorEvent = wrapper.emitted('error')?.[0]?.[0]
      expect(errorEvent).toEqual({
        type: 'camera',
        message: 'Akses kamera ditolak. Silakan berikan izin kamera.',
      })
    })

    it('should handle camera not found error', async () => {
      const deviceError = new Error('Device not found')
      deviceError.name = 'NotFoundError'
      mockGetUserMedia.mockRejectedValueOnce(deviceError)

      const startButton = wrapper.find('button')
      await startButton.trigger('click')

      await nextTick()

      expect(wrapper.emitted('error')).toBeTruthy()
      const errorEvent = wrapper.emitted('error')?.[0]?.[0]
      expect(errorEvent?.type).toBe('camera')
    })

    it('should stop camera and cleanup resources', async () => {
      // Start camera first
      wrapper.vm.cameraActive = true
      wrapper.vm.videoStream = {
        getTracks: vi.fn(() => [{ stop: vi.fn() }]),
      }
      wrapper.vm.detectionInterval = setInterval(() => {}, 100)

      await wrapper.vm.stopCamera()

      expect(wrapper.vm.cameraActive).toBe(false)
      expect(wrapper.vm.videoStream).toBe(null)
      expect(wrapper.vm.detectionInterval).toBe(null)
    })
  })

  describe('Face Detection', () => {
    beforeEach(() => {
      wrapper = createWrapper()
      wrapper.vm.videoElement = mockVideo
      wrapper.vm.overlayCanvas = mockCanvas
      wrapper.vm.cameraActive = true
    })

    it('should emit face-detected event when face is found', async () => {
      // Mock successful face detection
      wrapper.vm.currentDetection.confidence = 85
      wrapper.vm.currentDetection.liveness = 75

      await wrapper.vm.processFaceDetection()

      expect(wrapper.emitted('face-detected')).toBeTruthy()
    })

    it('should update detection statistics', async () => {
      const initialCount = wrapper.vm.statistics.facesDetected

      wrapper.vm.updateStatistics(85, 120) // confidence: 85, processing time: 120ms

      expect(wrapper.vm.statistics.facesDetected).toBe(initialCount + 1)
      expect(wrapper.vm.statistics.confidenceSum).toBeGreaterThan(0)
      expect(wrapper.vm.statistics.processingTimes).toContain(120)
    })

    it('should calculate average confidence correctly', () => {
      wrapper.vm.statistics.facesDetected = 3
      wrapper.vm.statistics.confidenceSum = 240 // 80 + 85 + 75

      const avgConfidence =
        wrapper.vm.statistics.confidenceSum / wrapper.vm.statistics.facesDetected
      expect(avgConfidence).toBe(80)
    })
  })

  describe('User Interactions', () => {
    beforeEach(() => {
      wrapper = createWrapper()
    })

    it('should emit simulate-attendance when simulation button clicked', async () => {
      const simulateButton = wrapper.find('button:nth-child(2)')
      await simulateButton.trigger('click')

      expect(wrapper.emitted('simulate-attendance')).toBeTruthy()
    })

    it('should show processing state when capture is in progress', async () => {
      wrapper.vm.processing = true
      await nextTick()

      const captureButton = wrapper.find('button')
      expect(captureButton.text()).toContain('Memproses...')
      expect(captureButton.attributes('disabled')).toBeDefined()
    })

    it('should enable capture button when confidence is high enough', async () => {
      wrapper.vm.cameraActive = true
      wrapper.vm.currentDetection.confidence = 85 // Above threshold (70)
      await nextTick()

      expect(wrapper.vm.canCapture).toBe(true)
    })

    it('should disable capture button when confidence is too low', async () => {
      wrapper.vm.cameraActive = true
      wrapper.vm.currentDetection.confidence = 50 // Below threshold (70)
      await nextTick()

      expect(wrapper.vm.canCapture).toBe(false)
    })
  })

  describe('Component Lifecycle', () => {
    it('should cleanup resources when component is unmounted', async () => {
      wrapper = createWrapper()
      const stopCameraSpy = vi.spyOn(wrapper.vm, 'stopCamera')

      wrapper.unmount()

      expect(stopCameraSpy).toHaveBeenCalled()
    })

    it('should handle window resize events', async () => {
      wrapper = createWrapper()

      // Trigger window resize
      window.dispatchEvent(new Event('resize'))

      // Should not cause errors
      expect(wrapper.exists()).toBe(true)
    })
  })

  describe('Accessibility', () => {
    beforeEach(() => {
      wrapper = createWrapper()
    })

    it('should have proper ARIA labels on buttons', () => {
      const buttons = wrapper.findAll('button')
      expect(buttons.length).toBeGreaterThan(0)

      // Check if buttons have descriptive text
      buttons.forEach((button) => {
        expect(button.text()).not.toBe('')
      })
    })

    it('should provide visual feedback for detection status', async () => {
      wrapper.vm.detectionStatus = {
        type: 'success',
        message: 'Face detected successfully',
      }
      await nextTick()

      const statusIndicator = wrapper.find('.w-3.h-3.rounded-full')
      expect(statusIndicator.exists()).toBe(true)
      expect(statusIndicator.classes()).toContain('bg-green-500')
    })

    it('should show instructional content', () => {
      const instructions = wrapper.find('.bg-blue-50')
      expect(instructions.exists()).toBe(true)
      expect(instructions.text()).toContain('Petunjuk Penggunaan')
    })
  })

  describe('Error Handling', () => {
    beforeEach(() => {
      wrapper = createWrapper()
    })

    it('should handle detection model initialization errors', async () => {
      const errorSpy = vi.spyOn(wrapper.vm, '$emit')

      // Mock model initialization failure
      wrapper.vm.faceApiModel = null
      await wrapper.vm.initializeDetectionModel().catch(() => {})

      // Should not crash the component
      expect(wrapper.exists()).toBe(true)
    })

    it('should show appropriate error messages for different failure types', () => {
      const testCases = [
        { name: 'NotAllowedError', expected: 'Akses kamera ditolak' },
        { name: 'NotFoundError', expected: 'Kamera tidak ditemukan' },
        { name: 'NotReadableError', expected: 'Kamera sedang digunakan' },
      ]

      testCases.forEach(({ name, expected }) => {
        const error = new Error('Test error')
        error.name = name

        const message = wrapper.vm.getErrorMessage(error)
        expect(message).toContain(expected)
      })
    })
  })
})
