class PushNotificationService {
    constructor() {
        this.registration = null
        this.subscription = null
        this.publicVapidKey = null
        this.isSupported = 'serviceWorker' in navigator && 'PushManager' in window
        this.permission = Notification.permission
    }

    /**
     * Initialize push notification service
     */
    async init() {
        if (!this.isSupported) {
            console.warn('Push notifications are not supported in this browser')
            return false
        }

        try {
            // Register service worker
            await this.registerServiceWorker()
            
            // Get VAPID public key from server
            await this.getVapidKey()
            
            // Get existing subscription or create new one
            await this.getSubscription()
            
            return true
        } catch (error) {
            console.error('Failed to initialize push notifications:', error)
            return false
        }
    }

    /**
     * Register service worker for push notifications
     */
    async registerServiceWorker() {
        if (!('serviceWorker' in navigator)) {
            throw new Error('Service workers are not supported')
        }

        try {
            this.registration = await navigator.serviceWorker.register('/sw.js')
            console.log('Service worker registered:', this.registration)
            
            // Wait for service worker to be ready
            await navigator.serviceWorker.ready
        } catch (error) {
            console.error('Service worker registration failed:', error)
            throw error
        }
    }

    /**
     * Get VAPID public key from server
     */
    async getVapidKey() {
        try {
            const response = await fetch('/api/push-notifications/vapid-key')
            if (response.ok) {
                const data = await response.json()
                this.publicVapidKey = data.publicKey
            } else {
                console.warn('Could not get VAPID key from server')
            }
        } catch (error) {
            console.error('Failed to get VAPID key:', error)
        }
    }

    /**
     * Request notification permission
     */
    async requestPermission() {
        if (!this.isSupported) {
            return 'notsupported'
        }

        if (this.permission === 'granted') {
            return 'granted'
        }

        if (this.permission === 'denied') {
            return 'denied'
        }

        try {
            this.permission = await Notification.requestPermission()
            return this.permission
        } catch (error) {
            console.error('Failed to request notification permission:', error)
            return 'denied'
        }
    }

    /**
     * Get or create push subscription
     */
    async getSubscription() {
        if (!this.registration) {
            throw new Error('Service worker not registered')
        }

        try {
            // Check for existing subscription
            this.subscription = await this.registration.pushManager.getSubscription()
            
            if (!this.subscription && this.permission === 'granted') {
                // Create new subscription
                await this.subscribe()
            }

            return this.subscription
        } catch (error) {
            console.error('Failed to get push subscription:', error)
            throw error
        }
    }

    /**
     * Subscribe to push notifications
     */
    async subscribe() {
        if (!this.registration) {
            throw new Error('Service worker not registered')
        }

        if (this.permission !== 'granted') {
            const permission = await this.requestPermission()
            if (permission !== 'granted') {
                throw new Error('Notification permission not granted')
            }
        }

        try {
            const options = {
                userVisibleOnly: true,
                applicationServerKey: this.urlBase64ToUint8Array(this.publicVapidKey || '')
            }

            this.subscription = await this.registration.pushManager.subscribe(options)
            
            // Send subscription to server
            await this.sendSubscriptionToServer(this.subscription)
            
            console.log('Push notification subscription created:', this.subscription)
            return this.subscription
        } catch (error) {
            console.error('Failed to subscribe to push notifications:', error)
            throw error
        }
    }

    /**
     * Unsubscribe from push notifications
     */
    async unsubscribe() {
        if (!this.subscription) {
            return true
        }

        try {
            // Unsubscribe from browser
            const result = await this.subscription.unsubscribe()
            
            if (result) {
                // Remove subscription from server
                await this.removeSubscriptionFromServer(this.subscription)
                this.subscription = null
                console.log('Push notification subscription removed')
            }
            
            return result
        } catch (error) {
            console.error('Failed to unsubscribe from push notifications:', error)
            throw error
        }
    }

    /**
     * Send subscription to server
     */
    async sendSubscriptionToServer(subscription) {
        try {
            const response = await fetch('/api/push-notifications/subscribe', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                },
                body: JSON.stringify({
                    subscription: subscription.toJSON()
                })
            })

            if (!response.ok) {
                throw new Error('Failed to send subscription to server')
            }

            console.log('Subscription sent to server')
        } catch (error) {
            console.error('Failed to send subscription to server:', error)
            throw error
        }
    }

    /**
     * Remove subscription from server
     */
    async removeSubscriptionFromServer(subscription) {
        try {
            const response = await fetch('/api/push-notifications/unsubscribe', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                },
                body: JSON.stringify({
                    subscription: subscription.toJSON()
                })
            })

            if (!response.ok) {
                throw new Error('Failed to remove subscription from server')
            }

            console.log('Subscription removed from server')
        } catch (error) {
            console.error('Failed to remove subscription from server:', error)
            throw error
        }
    }

    /**
     * Send test push notification
     */
    async sendTestNotification() {
        if (!this.subscription) {
            throw new Error('No push subscription available')
        }

        try {
            const response = await fetch('/api/push-notifications/test', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                }
            })

            if (!response.ok) {
                throw new Error('Failed to send test notification')
            }

            console.log('Test push notification sent')
            return true
        } catch (error) {
            console.error('Failed to send test push notification:', error)
            throw error
        }
    }

    /**
     * Convert VAPID key to Uint8Array
     */
    urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4)
        const base64 = (base64String + padding)
            .replace(/\-/g, '+')
            .replace(/_/g, '/')

        const rawData = window.atob(base64)
        const outputArray = new Uint8Array(rawData.length)

        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i)
        }
        return outputArray
    }

    /**
     * Check if push notifications are supported
     */
    isSupported() {
        return this.isSupported
    }

    /**
     * Get current permission status
     */
    getPermission() {
        return this.permission
    }

    /**
     * Get subscription status
     */
    isSubscribed() {
        return !!this.subscription
    }

    /**
     * Get subscription details
     */
    getSubscription() {
        return this.subscription
    }
}

// Create and export singleton instance
const pushNotificationService = new PushNotificationService()

export default pushNotificationService