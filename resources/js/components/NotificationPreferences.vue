<template>
  <div class="space-y-6">
    <!-- Security Notifications -->
    <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg">
      <div class="px-4 py-5 sm:p-6">
        <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white mb-4">
          🔐 Security Notifications
        </h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
          Configure how you want to be notified about security events and account changes.
        </p>

        <div class="space-y-4" v-if="preferences.security_notifications">
          <div 
            v-for="(config, type) in preferences.security_notifications" 
            :key="type"
            class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-600 rounded-lg"
          >
            <div class="flex-1">
              <h4 class="text-sm font-medium text-gray-900 dark:text-white">
                {{ getNotificationTitle(type) }}
              </h4>
              <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                {{ getNotificationDescription(type) }}
              </p>
            </div>
            <div class="flex space-x-4 ml-4">
              <label class="flex items-center">
                <input 
                  type="checkbox" 
                  v-model="config.email"
                  @change="updatePreferences"
                  class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Email</span>
              </label>
              <label class="flex items-center">
                <input 
                  type="checkbox" 
                  v-model="config.browser"
                  @change="updatePreferences"
                  class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Browser</span>
              </label>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- System Notifications -->
    <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg">
      <div class="px-4 py-5 sm:p-6">
        <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white mb-4">
          🔔 System Notifications
        </h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
          Configure notifications for attendance, leave, payroll, and system updates.
        </p>

        <div class="space-y-4" v-if="preferences.system_notifications">
          <div 
            v-for="(config, type) in preferences.system_notifications" 
            :key="type"
            class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-600 rounded-lg"
          >
            <div class="flex-1">
              <h4 class="text-sm font-medium text-gray-900 dark:text-white">
                {{ getNotificationTitle(type) }}
              </h4>
              <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                {{ getNotificationDescription(type) }}
              </p>
            </div>
            <div class="flex space-x-4 ml-4">
              <label class="flex items-center">
                <input 
                  type="checkbox" 
                  v-model="config.email"
                  @change="updatePreferences"
                  class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Email</span>
              </label>
              <label class="flex items-center">
                <input 
                  type="checkbox" 
                  v-model="config.browser"
                  @change="updatePreferences"
                  class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Browser</span>
              </label>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Quiet Hours -->
    <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg">
      <div class="px-4 py-5 sm:p-6">
        <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white mb-4">
          🌙 Quiet Hours
        </h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
          Set hours when you don't want to receive non-critical notifications.
        </p>

        <div class="space-y-4">
          <label class="flex items-center">
            <input 
              type="checkbox" 
              v-model="quietHoursEnabled"
              @change="updateQuietHours"
              class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            >
            <span class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">
              Enable quiet hours
            </span>
          </label>

          <div v-if="quietHoursEnabled" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Start Time
              </label>
              <input 
                type="time" 
                v-model="quietHours.start"
                @change="updateQuietHours"
                class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-white"
              >
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                End Time
              </label>
              <input 
                type="time" 
                v-model="quietHours.end"
                @change="updateQuietHours"
                class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-white"
              >
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Timezone
              </label>
              <select 
                v-model="quietHours.timezone"
                @change="updateQuietHours"
                class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-white"
              >
                <option value="UTC">UTC</option>
                <option value="America/New_York">Eastern Time</option>
                <option value="America/Chicago">Central Time</option>
                <option value="America/Denver">Mountain Time</option>
                <option value="America/Los_Angeles">Pacific Time</option>
              </select>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Digest Frequency -->
    <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg">
      <div class="px-4 py-5 sm:p-6">
        <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white mb-4">
          📊 Digest Frequency
        </h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
          Choose how often you want to receive summary notifications for different categories.
        </p>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Security Notifications
            </label>
            <select 
              v-model="digestFrequency.security"
              @change="updateDigestFrequency"
              class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-white"
            >
              <option value="immediate">Immediate</option>
              <option value="daily">Daily Digest</option>
              <option value="weekly">Weekly Digest</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              System Notifications
            </label>
            <select 
              v-model="digestFrequency.system"
              @change="updateDigestFrequency"
              class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-white"
            >
              <option value="immediate">Immediate</option>
              <option value="daily">Daily Digest</option>
              <option value="weekly">Weekly Digest</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Attendance Notifications
            </label>
            <select 
              v-model="digestFrequency.attendance"
              @change="updateDigestFrequency"
              class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-white"
            >
              <option value="immediate">Immediate</option>
              <option value="daily">Daily Digest</option>
              <option value="weekly">Weekly Digest</option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <!-- Test Notifications -->
    <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg">
      <div class="px-4 py-5 sm:p-6">
        <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white mb-4">
          🧪 Test Notifications
        </h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
          Send test notifications to verify your settings are working correctly.
        </p>

        <div class="flex space-x-4">
          <button
            @click="sendTestNotification('email')"
            :disabled="sendingTest"
            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
          >
            {{ sendingTest ? 'Sending...' : 'Test Email' }}
          </button>
          <button
            @click="sendTestNotification('browser')"
            :disabled="sendingTest"
            class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md shadow-sm text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
          >
            {{ sendingTest ? 'Sending...' : 'Test Browser' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'

// Reactive data
const preferences = reactive({
  security_notifications: {},
  system_notifications: {},
  quiet_hours: null,
  digest_frequency: {}
})

const quietHours = reactive({
  start: '22:00',
  end: '08:00',
  timezone: 'UTC'
})

const digestFrequency = reactive({
  security: 'immediate',
  system: 'daily',
  attendance: 'daily'
})

const loading = ref(true)
const updating = ref(false)
const sendingTest = ref(false)

// Computed
const quietHoursEnabled = computed({
  get: () => !!preferences.quiet_hours,
  set: (value) => {
    if (!value) {
      preferences.quiet_hours = null
    } else {
      preferences.quiet_hours = { ...quietHours }
    }
  }
})

// Methods
const loadPreferences = async () => {
  try {
    loading.value = true
    const response = await fetch('/api/notification-preferences')
    const data = await response.json()
    
    Object.assign(preferences, data.preferences)
    
    if (preferences.quiet_hours) {
      Object.assign(quietHours, preferences.quiet_hours)
    }
    
    if (preferences.digest_frequency) {
      Object.assign(digestFrequency, preferences.digest_frequency)
    }
  } catch (error) {
    console.error('Failed to load notification preferences:', error)
  } finally {
    loading.value = false
  }
}

const updatePreferences = async () => {
  try {
    updating.value = true
    
    // Flatten the preferences for the API
    const payload = {}
    
    for (const [type, config] of Object.entries(preferences.security_notifications)) {
      payload[`${type}_email`] = config.email
      payload[`${type}_browser`] = config.browser
    }
    
    for (const [type, config] of Object.entries(preferences.system_notifications)) {
      payload[`${type}_email`] = config.email
      payload[`${type}_browser`] = config.browser
    }
    
    const response = await fetch('/api/notification-preferences', {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      },
      body: JSON.stringify(payload)
    })
    
    if (response.ok) {
      // Show success notification
      window.showNotification?.('Notification preferences updated successfully', 'success')
    } else {
      console.error('Failed to update preferences')
    }
  } catch (error) {
    console.error('Error updating preferences:', error)
  } finally {
    updating.value = false
  }
}

const updateQuietHours = async () => {
  try {
    const payload = {
      enabled: quietHoursEnabled.value,
      start: quietHours.start,
      end: quietHours.end,
      timezone: quietHours.timezone
    }
    
    const response = await fetch('/api/notification-preferences/quiet-hours', {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      },
      body: JSON.stringify(payload)
    })
    
    if (response.ok) {
      const data = await response.json()
      preferences.quiet_hours = data.quiet_hours
      window.showNotification?.('Quiet hours updated successfully', 'success')
    }
  } catch (error) {
    console.error('Error updating quiet hours:', error)
  }
}

const updateDigestFrequency = async () => {
  try {
    const response = await fetch('/api/notification-preferences/digest-frequency', {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      },
      body: JSON.stringify(digestFrequency)
    })
    
    if (response.ok) {
      const data = await response.json()
      Object.assign(preferences.digest_frequency, data.digest_frequency)
      window.showNotification?.('Digest frequency updated successfully', 'success')
    }
  } catch (error) {
    console.error('Error updating digest frequency:', error)
  }
}

const sendTestNotification = async (type) => {
  try {
    sendingTest.value = true
    
    const response = await fetch('/api/notification-preferences/test', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      },
      body: JSON.stringify({ type, category: 'security' })
    })
    
    if (response.ok) {
      window.showNotification?.(`Test ${type} notification sent successfully`, 'success')
    } else {
      window.showNotification?.(`Failed to send test ${type} notification`, 'error')
    }
  } catch (error) {
    console.error('Error sending test notification:', error)
    window.showNotification?.('Error sending test notification', 'error')
  } finally {
    sendingTest.value = false
  }
}

const getNotificationTitle = (type) => {
  const titles = {
    new_device_login: 'New Device Login',
    failed_login_attempts: 'Failed Login Attempts',
    suspicious_activity: 'Suspicious Activity',
    account_locked: 'Account Locked',
    password_changed: 'Password Changed',
    two_factor_changes: '2FA Settings Changed',
    device_trusted: 'Device Trusted',
    admin_access: 'Admin Access',
    attendance_reminders: 'Attendance Reminders',
    leave_status: 'Leave Status Updates',
    payroll_notifications: 'Payroll Notifications',
    system_maintenance: 'System Maintenance'
  }
  return titles[type] || type.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const getNotificationDescription = (type) => {
  const descriptions = {
    new_device_login: 'Get notified when you log in from a new device',
    failed_login_attempts: 'Alerts for multiple failed login attempts',
    suspicious_activity: 'Notifications about unusual account activity',
    account_locked: 'Immediate alerts when your account is locked',
    password_changed: 'Confirmation when your password is changed',
    two_factor_changes: 'Alerts for 2FA setup, disable, or recovery code changes',
    device_trusted: 'Notifications when devices are marked as trusted',
    admin_access: 'Alerts for administrative actions on your account',
    attendance_reminders: 'Reminders for check-in/check-out and missing attendance',
    leave_status: 'Updates on leave request approvals and rejections',
    payroll_notifications: 'Payroll processing and payment notifications',
    system_maintenance: 'Advance notice of planned system maintenance'
  }
  return descriptions[type] || 'Configure notification preferences for this event type'
}

// Initialize
onMounted(() => {
  loadPreferences()
})
</script>