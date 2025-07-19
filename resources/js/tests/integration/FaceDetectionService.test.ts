import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import FaceDetectionService from '@/services/FaceDetectionService.js'

// Mock fetch globally
const mockFetch = vi.fn()
global.fetch = mockFetch

// Mock MediaPipe and Face-API
vi.mock('@mediapipe/face_detection', () => ({
  FaceDetection: vi.fn().mockImplementation(() => ({
    setOptions: vi.fn(),
    onResults: vi.fn(),
    send: vi.fn(),
    close: vi.fn(),
  })),
}))

vi.mock('face-api.js', () => ({
  nets: {
    tinyFaceDetector: {
      loadFromUri: vi.fn().mockResolvedValue(true),
    },
    faceLandmark68Net: {
      loadFromUri: vi.fn().mockResolvedValue(true),
    },
    faceRecognitionNet: {
      loadFromUri: vi.fn().mockResolvedValue(true),
    },
  },
  detectAllFaces: vi.fn(),
  TinyFaceDetectorOptions: vi.fn(),
}))

describe('FaceDetectionService Integration Tests', () => {
  let service: any

  beforeEach(() => {
    vi.clearAllMocks()
    service = new FaceDetectionService()

    // Mock CSRF token
    const mockMetaElement = {
      content: 'mock-csrf-token',
    } as HTMLMetaElement

    vi.spyOn(document, 'querySelector').mockImplementation((selector) => {
      if (selector === 'meta[name="csrf-token"]') {
        return mockMetaElement
      }
      return null
    })
  })

  afterEach(() => {
    vi.restoreAllMocks()
  })

  describe('Service Initialization', () => {
    it('should initialize with default options', () => {
      expect(service).toBeDefined()
      expect(service.isInitialized).toBe(false)
      expect(service.currentMethod).toBe('face-api')
    })

    it('should initialize Face-API models', async () => {
      const mockLoadFromUri = vi.fn().mockResolvedValue(true)
      vi.doMock('face-api.js', () => ({
        nets: {
          tinyFaceDetector: { loadFromUri: mockLoadFromUri },
          faceLandmark68Net: { loadFromUri: mockLoadFromUri },
          faceRecognitionNet: { loadFromUri: mockLoadFromUri },
        },
      }))

      await service.initializeFaceAPI()

      expect(service.isInitialized).toBe(true)
    })

    it('should handle initialization errors gracefully', async () => {
      const mockLoadFromUri = vi.fn().mockRejectedValue(new Error('Network error'))
      vi.doMock('face-api.js', () => ({
        nets: {
          tinyFaceDetector: { loadFromUri: mockLoadFromUri },
          faceLandmark68Net: { loadFromUri: mockLoadFromUri },
          faceRecognitionNet: { loadFromUri: mockLoadFromUri },
        },
      }))

      await expect(service.initializeFaceAPI()).rejects.toThrow(
        'Failed to initialize Face-API models'
      )
    })
  })

  describe('Face Detection Methods', () => {
    beforeEach(async () => {
      // Mock successful initialization
      service.isInitialized = true
    })

    it('should detect faces with Face-API method', async () => {
      const mockVideoElement = document.createElement('video')
      const mockDetections = [
        {
          detection: {
            box: { x: 100, y: 100, width: 150, height: 150 },
            score: 0.95,
          },
          landmarks: [],
          descriptor: new Float32Array([1, 2, 3]),
        },
      ]

      vi.doMock('face-api.js', () => ({
        detectAllFaces: vi.fn().mockResolvedValue(mockDetections),
        TinyFaceDetectorOptions: vi.fn(),
      }))

      const result = await service.detectFaces(mockVideoElement, 'face-api')

      expect(result).toBeDefined()
      expect(result.faces).toHaveLength(1)
      expect(result.faces[0].confidence).toBe(95)
      expect(result.faces[0].boundingBox).toEqual({
        x: 100,
        y: 100,
        width: 150,
        height: 150,
      })
    })

    it('should handle no faces detected', async () => {
      const mockVideoElement = document.createElement('video')

      vi.doMock('face-api.js', () => ({
        detectAllFaces: vi.fn().mockResolvedValue([]),
        TinyFaceDetectorOptions: vi.fn(),
      }))

      const result = await service.detectFaces(mockVideoElement, 'face-api')

      expect(result.faces).toHaveLength(0)
      expect(result.confidence).toBe(0)
    })

    it('should validate detection confidence thresholds', async () => {
      const mockVideoElement = document.createElement('video')
      const lowConfidenceDetections = [
        {
          detection: {
            box: { x: 100, y: 100, width: 150, height: 150 },
            score: 0.3, // Below threshold
          },
          landmarks: [],
          descriptor: new Float32Array([1, 2, 3]),
        },
      ]

      vi.doMock('face-api.js', () => ({
        detectAllFaces: vi.fn().mockResolvedValue(lowConfidenceDetections),
        TinyFaceDetectorOptions: vi.fn(),
      }))

      const result = await service.detectFaces(mockVideoElement, 'face-api', {
        confidenceThreshold: 0.5,
      })

      expect(result.faces).toHaveLength(0) // Should filter out low confidence
    })
  })

  describe('API Integration', () => {
    beforeEach(() => {
      service.isInitialized = true
    })

    it('should verify face against enrolled templates', async () => {
      const mockResponse = {
        success: true,
        data: {
          match: true,
          confidence: 0.92,
          employee_id: 'EMP001',
          template_id: 'template-123',
        },
      }

      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve(mockResponse),
      })

      const faceEmbeddings = new Float32Array([1, 2, 3, 4, 5])
      const result = await service.verifyFace(faceEmbeddings, 'EMP001')

      expect(mockFetch).toHaveBeenCalledWith('/api/face-recognition/verify', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': 'mock-csrf-token',
        },
        body: JSON.stringify({
          face_embeddings: Array.from(faceEmbeddings),
          employee_id: 'EMP001',
        }),
      })

      expect(result.match).toBe(true)
      expect(result.confidence).toBe(92)
    })

    it('should handle verification API errors', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 422,
        json: () =>
          Promise.resolve({
            message: 'Invalid face embeddings format',
          }),
      })

      const faceEmbeddings = new Float32Array([1, 2, 3])

      await expect(service.verifyFace(faceEmbeddings, 'EMP001')).rejects.toThrow(
        'Face verification failed'
      )
    })

    it('should enroll new face template', async () => {
      const mockResponse = {
        success: true,
        data: {
          template_id: 'template-456',
          employee_id: 'EMP002',
          created_at: '2025-01-01T00:00:00Z',
        },
      }

      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve(mockResponse),
      })

      const faceData = {
        embeddings: new Float32Array([1, 2, 3, 4, 5]),
        boundingBox: { x: 100, y: 100, width: 150, height: 150 },
        landmarks: [],
      }

      const result = await service.enrollFace('EMP002', faceData)

      expect(mockFetch).toHaveBeenCalledWith('/api/face-recognition/enroll', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': 'mock-csrf-token',
        },
        body: JSON.stringify({
          employee_id: 'EMP002',
          face_embeddings: Array.from(faceData.embeddings),
          bounding_box: faceData.boundingBox,
          landmarks: faceData.landmarks,
        }),
      })

      expect(result.template_id).toBe('template-456')
    })

    it('should handle network errors gracefully', async () => {
      mockFetch.mockRejectedValueOnce(new Error('Network connection failed'))

      const faceEmbeddings = new Float32Array([1, 2, 3])

      await expect(service.verifyFace(faceEmbeddings, 'EMP001')).rejects.toThrow(
        'Network connection failed'
      )
    })
  })

  describe('Performance and Liveness Detection', () => {
    beforeEach(() => {
      service.isInitialized = true
    })

    it('should measure detection performance', async () => {
      const mockVideoElement = document.createElement('video')
      const startTime = Date.now()

      vi.doMock('face-api.js', () => ({
        detectAllFaces: vi.fn().mockImplementation(() => {
          // Simulate processing time
          return new Promise((resolve) => {
            setTimeout(() => resolve([]), 100)
          })
        }),
        TinyFaceDetectorOptions: vi.fn(),
      }))

      const result = await service.detectFaces(mockVideoElement)

      expect(result.processingTime).toBeGreaterThan(90)
      expect(result.processingTime).toBeLessThan(200)
    })

    it('should implement basic liveness detection', async () => {
      const mockVideoElement = document.createElement('video')
      const mockDetections = [
        {
          detection: {
            box: { x: 100, y: 100, width: 150, height: 150 },
            score: 0.95,
          },
          landmarks: {
            positions: [
              { x: 125, y: 125 }, // Eye positions for blink detection
              { x: 175, y: 125 },
            ],
          },
          descriptor: new Float32Array([1, 2, 3]),
        },
      ]

      vi.doMock('face-api.js', () => ({
        detectAllFaces: vi.fn().mockResolvedValue(mockDetections),
        TinyFaceDetectorOptions: vi.fn(),
      }))

      const result = await service.detectFaces(mockVideoElement, 'face-api', {
        enableLiveness: true,
      })

      expect(result.liveness).toBeDefined()
      expect(typeof result.liveness.score).toBe('number')
    })
  })

  describe('Error Handling and Recovery', () => {
    it('should handle invalid video element', async () => {
      await expect(service.detectFaces(null, 'face-api')).rejects.toThrow('Invalid video element')
    })

    it('should handle unsupported detection method', async () => {
      const mockVideoElement = document.createElement('video')

      await expect(service.detectFaces(mockVideoElement, 'unknown-method' as any)).rejects.toThrow(
        'Unsupported detection method'
      )
    })

    it('should retry failed detections', async () => {
      const mockVideoElement = document.createElement('video')
      let callCount = 0

      vi.doMock('face-api.js', () => ({
        detectAllFaces: vi.fn().mockImplementation(() => {
          callCount++
          if (callCount < 3) {
            throw new Error('Temporary failure')
          }
          return Promise.resolve([])
        }),
        TinyFaceDetectorOptions: vi.fn(),
      }))

      const result = await service.detectFaces(mockVideoElement, 'face-api', {
        retryAttempts: 3,
      })

      expect(callCount).toBe(3)
      expect(result.faces).toHaveLength(0)
    })
  })

  describe('Memory Management', () => {
    it('should cleanup resources properly', async () => {
      const mockVideoElement = document.createElement('video')

      // Create some detection instances
      await service.detectFaces(mockVideoElement)

      // Cleanup
      service.cleanup()

      expect(service.isInitialized).toBe(false)
    })

    it('should handle multiple concurrent detections', async () => {
      const mockVideoElement = document.createElement('video')

      vi.doMock('face-api.js', () => ({
        detectAllFaces: vi.fn().mockResolvedValue([]),
        TinyFaceDetectorOptions: vi.fn(),
      }))

      // Run multiple detections concurrently
      const promises = Array(5)
        .fill(null)
        .map(() => service.detectFaces(mockVideoElement))

      const results = await Promise.all(promises)

      expect(results).toHaveLength(5)
      results.forEach((result) => {
        expect(result.faces).toHaveLength(0)
      })
    })
  })
})
