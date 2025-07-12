class NotificationService {
    constructor() {
        this.eventSource = null
        this.callbacks = new Map()
        this.reconnectAttempts = 0
        this.maxReconnectAttempts = 5
        this.reconnectInterval = 1000 // Start with 1 second
        this.isConnected = false
        this.unreadCount = 0
        
        // Bind methods to maintain context
        this.connect = this.connect.bind(this)
        this.disconnect = this.disconnect.bind(this)
        this.handleMessage = this.handleMessage.bind(this)
        this.handleError = this.handleError.bind(this)
        this.handleOpen = this.handleOpen.bind(this)
    }

    /**
     * Connect to the SSE notification stream
     */
    connect() {
        if (this.eventSource) {
            this.disconnect()
        }

        try {
            this.eventSource = new EventSource('/api/notifications/stream')
            
            this.eventSource.onopen = this.handleOpen
            this.eventSource.onmessage = this.handleMessage
            this.eventSource.onerror = this.handleError

            console.log('NotificationService: Connecting to SSE stream...')
        } catch (error) {
            console.error('NotificationService: Failed to connect to SSE stream:', error)
            this.scheduleReconnect()
        }
    }

    /**
     * Disconnect from the SSE stream
     */
    disconnect() {
        if (this.eventSource) {
            this.eventSource.close()
            this.eventSource = null
            this.isConnected = false
            console.log('NotificationService: Disconnected from SSE stream')
        }
    }

    /**
     * Handle successful connection
     */
    handleOpen() {
        this.isConnected = true
        this.reconnectAttempts = 0
        this.reconnectInterval = 1000
        console.log('NotificationService: Connected to SSE stream')
        
        // Emit connection event
        this.emit('connected', { timestamp: new Date() })
    }

    /**
     * Handle incoming SSE messages
     */
    handleMessage(event) {
        try {
            const data = JSON.parse(event.data)
            
            switch (data.type) {
                case 'connected':
                    console.log('NotificationService: Stream connection confirmed')
                    break
                    
                case 'notification':
                    this.handleNotification(data)
                    break
                    
                case 'heartbeat':
                    this.handleHeartbeat(data)
                    break
                    
                default:
                    console.warn('NotificationService: Unknown message type:', data.type)
            }
        } catch (error) {
            console.error('NotificationService: Failed to parse SSE message:', error)
        }
    }

    /**
     * Handle notification messages
     */
    handleNotification(data) {
        console.log('NotificationService: New notification received:', data)
        
        // Update unread count
        this.updateUnreadCount()
        
        // Emit notification event
        this.emit('notification', data)
        
        // Show browser notification if permission granted
        this.showBrowserNotification(data)
        
        // Play notification sound
        this.playNotificationSound()
    }

    /**
     * Handle heartbeat messages
     */
    handleHeartbeat(data) {
        // Update unread count from heartbeat
        if (data.unread_count !== undefined) {
            this.unreadCount = data.unread_count
            this.emit('unreadCountChanged', this.unreadCount)
        }
        
        // Emit heartbeat event for connection monitoring
        this.emit('heartbeat', data)
    }

    /**
     * Handle SSE errors and reconnection
     */
    handleError(error) {
        console.error('NotificationService: SSE error:', error)
        this.isConnected = false
        
        // Don't reconnect if manually disconnected
        if (this.eventSource && this.eventSource.readyState === EventSource.CLOSED) {
            return
        }
        
        this.scheduleReconnect()
    }

    /**
     * Schedule reconnection with exponential backoff
     */
    scheduleReconnect() {
        if (this.reconnectAttempts >= this.maxReconnectAttempts) {
            console.error('NotificationService: Max reconnection attempts reached')
            this.emit('connectionLost', { attempts: this.reconnectAttempts })
            return
        }

        this.reconnectAttempts++
        const delay = Math.min(this.reconnectInterval * Math.pow(2, this.reconnectAttempts - 1), 30000)
        
        console.log(`NotificationService: Reconnecting in ${delay}ms (attempt ${this.reconnectAttempts})`)
        
        setTimeout(() => {
            if (!this.isConnected) {
                this.connect()
            }
        }, delay)
    }

    /**
     * Register event callback
     */
    on(event, callback) {
        if (!this.callbacks.has(event)) {
            this.callbacks.set(event, new Set())
        }
        this.callbacks.get(event).add(callback)
    }

    /**
     * Unregister event callback
     */
    off(event, callback) {
        if (this.callbacks.has(event)) {
            this.callbacks.get(event).delete(callback)
        }
    }

    /**
     * Emit event to registered callbacks
     */
    emit(event, data) {
        if (this.callbacks.has(event)) {
            this.callbacks.get(event).forEach(callback => {
                try {
                    callback(data)
                } catch (error) {
                    console.error(`NotificationService: Error in ${event} callback:`, error)
                }
            })
        }
    }

    /**
     * Show browser notification
     */
    async showBrowserNotification(notification) {
        // Check if browser notifications are supported and permitted
        if (!('Notification' in window)) {
            return
        }

        if (Notification.permission === 'granted') {
            const notificationData = notification.data || {}
            
            new Notification(notificationData.title || 'New Notification', {
                body: notificationData.message || 'You have a new notification',
                icon: '/favicon.ico',
                badge: '/favicon.ico',
                tag: notification.id,
                requireInteraction: notificationData.priority === 'high',
                silent: false
            })
        } else if (Notification.permission === 'default') {
            // Request permission for future notifications
            await Notification.requestPermission()
        }
    }

    /**
     * Play notification sound
     */
    playNotificationSound() {
        try {
            // Create audio element for notification sound
            const audio = new Audio('/sounds/notification.mp3')
            audio.volume = 0.5
            audio.play().catch(error => {
                // Audio play failed (e.g., user hasn't interacted with page yet)
                console.log('NotificationService: Audio play failed:', error.message)
            })
        } catch (error) {
            console.error('NotificationService: Failed to play notification sound:', error)
        }
    }

    /**
     * Get current notification status
     */
    async getStatus() {
        try {
            const response = await fetch('/api/notifications/status')
            if (response.ok) {
                const data = await response.json()
                this.unreadCount = data.unread_count
                this.emit('unreadCountChanged', this.unreadCount)
                return data
            }
        } catch (error) {
            console.error('NotificationService: Failed to get status:', error)
        }
        return null
    }

    /**
     * Update unread count from server
     */
    async updateUnreadCount() {
        const status = await this.getStatus()
        return status?.unread_count || 0
    }

    /**
     * Send test notification
     */
    async sendTestNotification() {
        try {
            const response = await fetch('/api/notifications/test', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                }
            })
            
            if (response.ok) {
                const data = await response.json()
                console.log('NotificationService: Test notification sent:', data)
                return true
            }
        } catch (error) {
            console.error('NotificationService: Failed to send test notification:', error)
        }
        return false
    }

    /**
     * Request browser notification permission
     */
    async requestNotificationPermission() {
        if (!('Notification' in window)) {
            return 'notsupported'
        }

        if (Notification.permission === 'granted') {
            return 'granted'
        }

        if (Notification.permission === 'denied') {
            return 'denied'
        }

        const permission = await Notification.requestPermission()
        return permission
    }

    /**
     * Get connection status
     */
    getConnectionStatus() {
        return {
            isConnected: this.isConnected,
            reconnectAttempts: this.reconnectAttempts,
            unreadCount: this.unreadCount
        }
    }

    /**
     * Initialize the service (call this when app starts)
     */
    async init() {
        // Get initial status
        await this.getStatus()
        
        // Connect to SSE stream
        this.connect()
        
        // Request notification permission if needed
        await this.requestNotificationPermission()
        
        // Handle page visibility changes
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible' && !this.isConnected) {
                this.connect()
            }
        })
        
        // Handle beforeunload to cleanly disconnect
        window.addEventListener('beforeunload', () => {
            this.disconnect()
        })
    }
}

// Create and export singleton instance
const notificationService = new NotificationService()

export default notificationService