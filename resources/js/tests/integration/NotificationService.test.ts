import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import NotificationService from '@/services/NotificationService.js'

// Mock EventSource for SSE testing
class MockEventSource {
  static instances: MockEventSource[] = []

  url: string
  readyState: number = 0
  onopen: ((event: Event) => void) | null = null
  onmessage: ((event: MessageEvent) => void) | null = null
  onerror: ((event: Event) => void) | null = null

  constructor(url: string) {
    this.url = url
    MockEventSource.instances.push(this)
  }

  close() {
    this.readyState = 2
  }

  // Helper methods for testing
  simulateOpen() {
    this.readyState = 1
    if (this.onopen) {
      this.onopen(new Event('open'))
    }
  }

  simulateMessage(data: any) {
    if (this.onmessage) {
      const event = new MessageEvent('message', {
        data: JSON.stringify(data),
      })
      this.onmessage(event)
    }
  }

  simulateError() {
    if (this.onerror) {
      this.onerror(new Event('error'))
    }
  }

  static reset() {
    MockEventSource.instances = []
  }
}

// Mock fetch globally
const mockFetch = vi.fn()
global.fetch = mockFetch
global.EventSource = MockEventSource as any

describe('NotificationService Integration Tests', () => {
  let service: any

  beforeEach(() => {
    vi.clearAllMocks()
    MockEventSource.reset()
    service = new NotificationService()

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

    // Mock localStorage
    const mockStorage = {
      getItem: vi.fn(),
      setItem: vi.fn(),
      removeItem: vi.fn(),
      clear: vi.fn(),
    }
    Object.defineProperty(window, 'localStorage', { value: mockStorage })
  })

  afterEach(() => {
    vi.restoreAllMocks()
    service.disconnect()
  })

  describe('Service Initialization', () => {
    it('should initialize with default configuration', () => {
      expect(service).toBeDefined()
      expect(service.isConnected()).toBe(false)
      expect(service.getUnreadCount()).toBe(0)
    })

    it('should load persisted unread count from localStorage', () => {
      window.localStorage.getItem = vi.fn().mockReturnValue('5')

      const newService = new NotificationService()
      expect(newService.getUnreadCount()).toBe(5)
    })

    it('should handle invalid localStorage data gracefully', () => {
      window.localStorage.getItem = vi.fn().mockReturnValue('invalid-json')

      const newService = new NotificationService()
      expect(newService.getUnreadCount()).toBe(0)
    })
  })

  describe('Real-time Connection (SSE)', () => {
    it('should establish SSE connection successfully', async () => {
      const connectPromise = service.connect()

      // Simulate successful connection
      const eventSource = MockEventSource.instances[0]
      expect(eventSource).toBeDefined()
      expect(eventSource.url).toContain('/api/notifications/stream')

      eventSource.simulateOpen()

      await connectPromise
      expect(service.isConnected()).toBe(true)
    })

    it('should handle connection errors and retry', async () => {
      const connectPromise = service.connect()

      const eventSource = MockEventSource.instances[0]
      eventSource.simulateError()

      // Should attempt reconnection
      await new Promise((resolve) => setTimeout(resolve, 100))
      expect(MockEventSource.instances.length).toBeGreaterThan(1)
    })

    it('should receive and process notification messages', async () => {
      const mockNotification = {
        id: 'notif-123',
        type: 'security.login',
        title: 'New Login Detected',
        message: 'Login from new device detected',
        data: {
          device: 'iPhone 13',
          location: 'Jakarta',
        },
        priority: 'high',
        created_at: '2025-01-01T00:00:00Z',
      }

      const notificationReceived = vi.fn()
      service.on('notification', notificationReceived)

      await service.connect()
      const eventSource = MockEventSource.instances[0]
      eventSource.simulateOpen()

      // Simulate receiving notification
      eventSource.simulateMessage({
        type: 'notification',
        data: mockNotification,
      })

      expect(notificationReceived).toHaveBeenCalledWith(mockNotification)
      expect(service.getUnreadCount()).toBe(1)
    })

    it('should handle different message types', async () => {
      const heartbeatReceived = vi.fn()
      const statusReceived = vi.fn()

      service.on('heartbeat', heartbeatReceived)
      service.on('status', statusReceived)

      await service.connect()
      const eventSource = MockEventSource.instances[0]
      eventSource.simulateOpen()

      // Test heartbeat
      eventSource.simulateMessage({
        type: 'heartbeat',
        data: { timestamp: Date.now() },
      })

      // Test status update
      eventSource.simulateMessage({
        type: 'status',
        data: { unread_count: 3 },
      })

      expect(heartbeatReceived).toHaveBeenCalled()
      expect(statusReceived).toHaveBeenCalled()
      expect(service.getUnreadCount()).toBe(3)
    })

    it('should disconnect properly', async () => {
      await service.connect()
      const eventSource = MockEventSource.instances[0]
      eventSource.simulateOpen()

      expect(service.isConnected()).toBe(true)

      service.disconnect()
      expect(service.isConnected()).toBe(false)
      expect(eventSource.readyState).toBe(2) // CLOSED
    })
  })

  describe('HTTP API Integration', () => {
    it('should fetch notifications from API', async () => {
      const mockNotifications = [
        {
          id: 'notif-1',
          type: 'attendance.checkin',
          title: 'Check-in Successful',
          message: 'You have successfully checked in',
          read_at: null,
          created_at: '2025-01-01T08:00:00Z',
        },
        {
          id: 'notif-2',
          type: 'security.device',
          title: 'New Device Registered',
          message: 'A new device has been registered to your account',
          read_at: '2025-01-01T09:00:00Z',
          created_at: '2025-01-01T08:30:00Z',
        },
      ]

      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () =>
          Promise.resolve({
            data: mockNotifications,
            unread_count: 1,
          }),
      })

      const result = await service.fetchNotifications({ limit: 10 })

      expect(mockFetch).toHaveBeenCalledWith('/api/notifications?limit=10', {
        headers: {
          Authorization: 'Bearer null',
          'X-CSRF-TOKEN': 'mock-csrf-token',
        },
      })

      expect(result.notifications).toEqual(mockNotifications)
      expect(result.unread_count).toBe(1)
    })

    it('should mark notification as read', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve({ success: true }),
      })

      await service.markAsRead('notif-123')

      expect(mockFetch).toHaveBeenCalledWith('/api/notifications/notif-123/read', {
        method: 'POST',
        headers: {
          Authorization: 'Bearer null',
          'X-CSRF-TOKEN': 'mock-csrf-token',
        },
      })
    })

    it('should mark all notifications as read', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve({ success: true }),
      })

      // Set initial unread count
      service.updateUnreadCount(5)
      expect(service.getUnreadCount()).toBe(5)

      await service.markAllAsRead()

      expect(mockFetch).toHaveBeenCalledWith('/api/notifications/mark-all-read', {
        method: 'POST',
        headers: {
          Authorization: 'Bearer null',
          'X-CSRF-TOKEN': 'mock-csrf-token',
        },
      })

      expect(service.getUnreadCount()).toBe(0)
    })

    it('should handle API errors gracefully', async () => {
      mockFetch.mockResolvedValueOnce({
        ok: false,
        status: 422,
        json: () =>
          Promise.resolve({
            message: 'Validation failed',
          }),
      })

      await expect(service.fetchNotifications()).rejects.toThrow('Failed to fetch notifications')
    })

    it('should handle network errors', async () => {
      mockFetch.mockRejectedValueOnce(new Error('Network error'))

      await expect(service.markAsRead('notif-123')).rejects.toThrow('Network error')
    })
  })

  describe('Event System', () => {
    it('should register and trigger event listeners', () => {
      const listener1 = vi.fn()
      const listener2 = vi.fn()

      service.on('test-event', listener1)
      service.on('test-event', listener2)

      service.emit('test-event', { data: 'test' })

      expect(listener1).toHaveBeenCalledWith({ data: 'test' })
      expect(listener2).toHaveBeenCalledWith({ data: 'test' })
    })

    it('should remove event listeners', () => {
      const listener = vi.fn()

      service.on('test-event', listener)
      service.off('test-event', listener)
      service.emit('test-event', { data: 'test' })

      expect(listener).not.toHaveBeenCalled()
    })

    it('should handle one-time event listeners', () => {
      const listener = vi.fn()

      service.once('test-event', listener)
      service.emit('test-event', { data: 'test1' })
      service.emit('test-event', { data: 'test2' })

      expect(listener).toHaveBeenCalledTimes(1)
      expect(listener).toHaveBeenCalledWith({ data: 'test1' })
    })
  })

  describe('Notification Management', () => {
    it('should update unread count and persist to localStorage', () => {
      service.updateUnreadCount(10)

      expect(service.getUnreadCount()).toBe(10)
      expect(window.localStorage.setItem).toHaveBeenCalledWith('notification_unread_count', '10')
    })

    it('should increment unread count when receiving new notification', async () => {
      await service.connect()
      const eventSource = MockEventSource.instances[0]
      eventSource.simulateOpen()

      const initialCount = service.getUnreadCount()

      eventSource.simulateMessage({
        type: 'notification',
        data: {
          id: 'new-notif',
          type: 'test',
          title: 'Test',
          message: 'Test notification',
          read_at: null,
        },
      })

      expect(service.getUnreadCount()).toBe(initialCount + 1)
    })

    it('should not increment count for already read notifications', async () => {
      await service.connect()
      const eventSource = MockEventSource.instances[0]
      eventSource.simulateOpen()

      const initialCount = service.getUnreadCount()

      eventSource.simulateMessage({
        type: 'notification',
        data: {
          id: 'read-notif',
          type: 'test',
          title: 'Test',
          message: 'Test notification',
          read_at: '2025-01-01T00:00:00Z',
        },
      })

      expect(service.getUnreadCount()).toBe(initialCount)
    })
  })

  describe('Connection Recovery', () => {
    it('should implement exponential backoff for reconnection', async () => {
      const originalSetTimeout = global.setTimeout
      const setTimeoutSpy = vi.fn((callback, delay) => {
        return originalSetTimeout(callback, 0) // Execute immediately for testing
      })
      global.setTimeout = setTimeoutSpy

      service.connect()

      const eventSource = MockEventSource.instances[0]
      eventSource.simulateError()

      // Wait for reconnection attempts
      await new Promise((resolve) => setTimeout(resolve, 10))

      expect(setTimeoutSpy).toHaveBeenCalled()
      expect(MockEventSource.instances.length).toBeGreaterThan(1)

      global.setTimeout = originalSetTimeout
    })

    it('should stop reconnection attempts after max retries', async () => {
      service.maxReconnectAttempts = 2

      service.connect()

      // Simulate multiple errors
      for (let i = 0; i < 3; i++) {
        const eventSource = MockEventSource.instances[i]
        if (eventSource) {
          eventSource.simulateError()
          await new Promise((resolve) => setTimeout(resolve, 10))
        }
      }

      expect(MockEventSource.instances.length).toBeLessThanOrEqual(3)
    })

    it('should reset reconnection attempts on successful connection', async () => {
      service.connect()

      let eventSource = MockEventSource.instances[0]
      eventSource.simulateError()

      await new Promise((resolve) => setTimeout(resolve, 10))

      eventSource = MockEventSource.instances[1]
      eventSource.simulateOpen()

      expect(service.isConnected()).toBe(true)
      expect(service.reconnectAttempts).toBe(0)
    })
  })

  describe('Cleanup and Memory Management', () => {
    it('should cleanup event listeners and connections', () => {
      const listener = vi.fn()
      service.on('test', listener)

      service.cleanup()

      service.emit('test', {})
      expect(listener).not.toHaveBeenCalled()
      expect(service.isConnected()).toBe(false)
    })

    it('should handle cleanup when not connected', () => {
      expect(() => service.cleanup()).not.toThrow()
      expect(service.isConnected()).toBe(false)
    })
  })
})
