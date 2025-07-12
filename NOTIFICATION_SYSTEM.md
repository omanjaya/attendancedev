# Real-time Notification System Documentation

## 🚀 Overview

This comprehensive notification system provides real-time communications through multiple channels including Server-Sent Events (SSE), browser push notifications, toast notifications, and security monitoring. Built with Laravel 12 backend and Vue 3 frontend.

## 📋 Features

### ✅ Real-time Notifications (SSE)
- **Server-Sent Events streaming** for instant notification delivery
- **Auto-reconnection** with exponential backoff
- **Heartbeat monitoring** to maintain connection health
- **Connection status indicators** for user awareness
- **Offline graceful degradation**

### ✅ Browser Push Notifications
- **Web Push API integration** with VAPID authentication
- **Service Worker** for background notification handling
- **Permission management** with user-friendly prompts
- **Click tracking and analytics**
- **Cross-platform compatibility**

### ✅ Toast Notification System
- **Multiple notification types** (info, success, warning, error, security)
- **Auto-dismiss with progress indicators**
- **Mobile-responsive positioning**
- **Smooth animations and transitions**
- **Global API for easy integration**

### ✅ Security Dashboard
- **Real-time security metrics** and monitoring
- **Security alert management** with acknowledgment
- **2FA adoption tracking** with progress visualization
- **Live event monitoring** with risk classification
- **Auto-refresh capabilities**

### ✅ Device Management
- **Device fingerprinting and tracking**
- **Trust management** with 2FA verification
- **Real-time device status updates**
- **Browser and OS detection**
- **IP tracking and geolocation**

### ✅ Notification Preferences
- **Granular notification controls** per event type
- **Quiet hours management** with timezone support
- **Digest frequency settings**
- **Email and browser notification toggles**
- **Priority-based overrides**

## 🛠 Installation & Setup

### Backend Setup

1. **Install Required Packages**
```bash
composer require jenssegers/agent
```

2. **Run Database Migrations**
```bash
php artisan migrate
```

3. **Add to your `routes/api.php`**
```php
// Real-time Notification Streaming API routes
Route::prefix('notifications')->middleware(['auth', 'verified'])->group(function () {
    Route::get('/stream', [App\Http\Controllers\Api\NotificationStreamController::class, 'stream']);
    Route::get('/status', [App\Http\Controllers\Api\NotificationStreamController::class, 'status']);
    Route::post('/test', [App\Http\Controllers\Api\NotificationStreamController::class, 'sendTestNotification']);
});

// Device Management API routes
Route::prefix('devices')->middleware(['auth', 'verified'])->group(function () {
    Route::get('/', [App\Http\Controllers\Api\DeviceController::class, 'index']);
    Route::get('/current', [App\Http\Controllers\Api\DeviceController::class, 'current']);
    Route::patch('/{device}/name', [App\Http\Controllers\Api\DeviceController::class, 'updateName']);
    Route::post('/{device}/trust', [App\Http\Controllers\Api\DeviceController::class, 'trust']);
    Route::delete('/{device}/trust', [App\Http\Controllers\Api\DeviceController::class, 'revokeTrust']);
    Route::delete('/{device}', [App\Http\Controllers\Api\DeviceController::class, 'destroy']);
    Route::delete('/all', [App\Http\Controllers\Api\DeviceController::class, 'removeAll']);
});

// Notification Preferences API routes
Route::prefix('notification-preferences')->middleware(['auth', 'verified'])->group(function () {
    Route::get('/', [App\Http\Controllers\Api\NotificationPreferencesController::class, 'index']);
    Route::put('/', [App\Http\Controllers\Api\NotificationPreferencesController::class, 'update']);
    Route::put('/quiet-hours', [App\Http\Controllers\Api\NotificationPreferencesController::class, 'updateQuietHours']);
    Route::put('/digest-frequency', [App\Http\Controllers\Api\NotificationPreferencesController::class, 'updateDigestFrequency']);
    Route::post('/test', [App\Http\Controllers\Api\NotificationPreferencesController::class, 'testNotification']);
    Route::get('/history', [App\Http\Controllers\Api\NotificationPreferencesController::class, 'history']);
    Route::post('/mark-read', [App\Http\Controllers\Api\NotificationPreferencesController::class, 'markAsRead']);
});
```

### Frontend Setup

1. **Install Dependencies**
```bash
npm install vue@^3.0.0 date-fns
```

2. **Import CSS Framework**
Ensure Tailwind CSS is properly configured for responsive design.

3. **Add to your main layout**
```html
<!-- In your main layout file -->
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- Other head content -->
</head>

<body>
    <!-- Your content -->
    
    <!-- Notification Center (add to header/navigation) -->
    <div id="notification-center"></div>
    
    <!-- Toast Container (automatically created) -->
    
    @vite(['resources/js/app.js'])
    @stack('scripts')
</body>
```

4. **Initialize Notification System**
```javascript
// In your main app.js or layout
import './app-integration.js'
```

## 🔧 Configuration

### Environment Variables
```env
# Push Notification Settings (optional)
VAPID_PUBLIC_KEY=your_vapid_public_key
VAPID_PRIVATE_KEY=your_vapid_private_key
VAPID_SUBJECT=mailto:your-email@domain.com

# Notification Settings
NOTIFICATION_RATE_LIMIT=60
NOTIFICATION_BATCH_SIZE=10
```

### Service Worker Registration

The service worker is automatically registered at `/sw.js`. Ensure your web server serves this file with the correct MIME type:

**Nginx Configuration:**
```nginx
location = /sw.js {
    add_header Cache-Control "no-cache, no-store, must-revalidate";
    add_header Content-Type "application/javascript";
}
```

**Apache Configuration:**
```apache
<Files "sw.js">
    Header set Cache-Control "no-cache, no-store, must-revalidate"
    Header set Content-Type "application/javascript"
</Files>
```

## 📱 Usage Examples

### Basic Usage

1. **Show Toast Notification**
```javascript
// Available globally after initialization
window.toast.success('Success!', 'Operation completed successfully')
window.toast.error('Error!', 'Something went wrong')
window.toast.warning('Warning!', 'Please review this action')
window.toast.info('Info', 'Here is some information')
window.toast.security('Security Alert', 'Suspicious activity detected')
```

2. **Send Real-time Notification**
```php
// In your controller or service
$user->notify(new \App\Notifications\SecurityNotification([
    'title' => 'New Device Login',
    'message' => 'Login detected from new device',
    'type' => 'security_login',
    'priority' => 'high'
]));
```

3. **Check Notification System Status**
```javascript
const status = window.notificationSystem.getStatus()
console.log('SSE Connected:', status.sse.isConnected)
console.log('Push Supported:', status.push.supported)
console.log('Push Permission:', status.push.permission)
```

### Advanced Usage

1. **Custom Notification Types**
```php
// Create custom notification class
class CustomNotification extends Notification
{
    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        return [
            'type' => 'custom_event',
            'title' => 'Custom Event',
            'message' => 'This is a custom notification',
            'priority' => 'medium',
            'data' => [
                'url' => '/custom-page',
                'action' => 'view_details'
            ]
        ];
    }
}
```

2. **Responsive Component Integration**
```vue
<template>
  <div>
    <NotificationCenter />
    <SecurityDashboard v-if="hasSecurityPermission" />
    <DeviceManagement />
  </div>
</template>

<script setup>
import { useResponsive } from '@/composables/useResponsive'
import NotificationCenter from '@/components/NotificationCenter.vue'
import SecurityDashboard from '@/components/SecurityDashboard.vue'
import DeviceManagement from '@/components/DeviceManagement.vue'

const { isMobile, isTablet } = useResponsive()
const hasSecurityPermission = computed(() => {
  // Check user permissions
})
</script>
```

## 🔐 Security Considerations

### Data Privacy
- **Device fingerprints** are hashed and encrypted
- **No personal data** stored in browser localStorage
- **CSRF protection** on all API endpoints
- **Rate limiting** prevents notification spam

### Permission Management
- **Role-based access control** for security features
- **User-controlled preferences** for all notification types
- **Graceful permission handling** for browser APIs
- **Secure WebSocket connections** (optional upgrade from SSE)

### Security Monitoring
- **Device tracking** with trust management
- **Login anomaly detection**
- **2FA enforcement monitoring**
- **Security event logging** with risk assessment

## 📊 Performance Optimization

### Backend Optimizations
- **Connection pooling** for SSE streams
- **Efficient database queries** with proper indexing
- **Caching strategies** for frequently accessed data
- **Queue processing** for bulk notifications

### Frontend Optimizations
- **Lazy loading** of Vue components
- **Virtual scrolling** for large notification lists
- **Debounced API calls** for real-time updates
- **Service worker caching** for offline functionality

### Mobile Optimizations
- **Touch-friendly interfaces** with larger tap targets
- **Responsive breakpoints** for different screen sizes
- **Optimized animations** for smooth performance
- **Battery-efficient polling** strategies

## 🧪 Testing

### Run Tests
```bash
# Backend tests
php artisan test --filter NotificationSystemTest

# Frontend tests (if using Jest/Vitest)
npm run test
```

### Manual Testing
1. Visit `/demo/notifications` for comprehensive testing interface
2. Test real-time notifications with SSE connection
3. Verify push notification permissions and delivery
4. Test responsive design on different screen sizes
5. Validate security features and device management

## 📈 Monitoring & Analytics

### Key Metrics to Monitor
- **SSE connection success rate**
- **Push notification delivery rate**
- **User engagement with notifications**
- **Device trust adoption rate**
- **Security alert response times**

### Built-in Analytics
- **Notification click tracking**
- **Connection retry statistics**
- **User preference analytics**
- **Security event correlation**
- **Performance metrics** (connection latency, delivery time)

## 🔄 Maintenance

### Regular Maintenance Tasks
1. **Clean up old notifications** (older than 30 days)
2. **Monitor SSE connection health**
3. **Update push notification certificates**
4. **Review security alert patterns**
5. **Optimize database performance**

### Troubleshooting

**Common Issues:**

1. **SSE Connection Fails**
   - Check nginx/Apache proxy settings
   - Verify CSRF token validity
   - Review network firewall rules

2. **Push Notifications Not Working**
   - Verify VAPID key configuration
   - Check browser permissions
   - Ensure HTTPS is enabled

3. **Mobile Responsiveness Issues**
   - Update Tailwind CSS breakpoints
   - Test on actual devices
   - Review viewport meta tag

## 🚀 Deployment

### Production Checklist
- [ ] Configure web server for SSE (`text/event-stream` MIME type)
- [ ] Set up SSL certificates for push notifications
- [ ] Configure rate limiting and DDoS protection
- [ ] Set up monitoring and alerting
- [ ] Test notification delivery across different browsers
- [ ] Configure content security policy (CSP) headers
- [ ] Set up log rotation for notification events
- [ ] Test mobile responsiveness on actual devices

### Performance Tuning
- [ ] Enable Redis for session storage and caching
- [ ] Configure queue workers for notification processing
- [ ] Set up CDN for static assets
- [ ] Optimize database queries with proper indexing
- [ ] Configure HTTP/2 for better SSE performance

## 🆘 Support

For issues and questions:
1. Check the demo page at `/demo/notifications`
2. Review browser console for error messages
3. Verify API endpoints are accessible
4. Check database migrations are complete
5. Ensure all required permissions are set

## 📝 Changelog

### v1.0.0 - Initial Release
- Real-time notification system with SSE
- Browser push notification support
- Security dashboard and device management
- Mobile-responsive design
- Comprehensive testing suite

---

**Built with ❤️ using Laravel 12, Vue 3, and modern web technologies.**