<template>
  <div class="space-y-6">
    <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg">
      <div class="px-4 py-5 sm:p-6">
        <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white">
          Trusted Devices
        </h3>
        <div class="mt-2 max-w-xl text-sm text-gray-500 dark:text-gray-400">
          <p>
            Manage devices you trust for two-factor authentication. Trusted devices won't require 2FA codes for login.
          </p>
        </div>
        
        <div class="mt-5" v-if="loading">
          <div class="flex items-center justify-center py-8">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
          </div>
        </div>

        <div class="mt-5" v-else-if="devices.length === 0">
          <div class="text-center py-8">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No devices</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
              You haven't trusted any devices yet.
            </p>
          </div>
        </div>

        <div class="mt-5 space-y-3" v-else>
          <div
            v-for="device in devices"
            :key="device.id"
            class="relative rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-6 py-5 shadow-sm focus-within:ring-2 focus-within:ring-indigo-500 focus-within:ring-offset-2 hover:border-gray-400 dark:hover:border-gray-500"
          >
            <div class="flex items-center justify-between">
              <div class="flex items-center space-x-3">
                <div class="flex-shrink-0">
                  <component 
                    :is="getDeviceIcon(device.device_type)" 
                    class="h-6 w-6 text-gray-400 dark:text-gray-300"
                  />
                </div>
                <div class="min-w-0 flex-1">
                  <div class="flex items-center space-x-2">
                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                      {{ device.display_name }}
                    </p>
                    <span 
                      v-if="device.is_current"
                      class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200"
                    >
                      Current Device
                    </span>
                    <span 
                      v-if="device.is_trusted"
                      class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200"
                    >
                      Trusted
                    </span>
                  </div>
                  <div class="text-sm text-gray-500 dark:text-gray-400">
                    {{ device.browser_name }} on {{ device.os_name }}
                  </div>
                  <div class="text-xs text-gray-400 dark:text-gray-500">
                    Last seen {{ formatDate(device.last_seen_at) }} from {{ device.last_ip_address }}
                  </div>
                </div>
              </div>
              
              <div class="flex items-center space-x-2">
                <button
                  @click="editDeviceName(device)"
                  class="inline-flex items-center px-2.5 py-1.5 border border-gray-300 dark:border-gray-600 shadow-sm text-xs font-medium rounded text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-600 hover:bg-gray-50 dark:hover:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                >
                  Rename
                </button>
                
                <button
                  v-if="!device.is_trusted"
                  @click="trustDevice(device)"
                  class="inline-flex items-center px-2.5 py-1.5 border border-transparent text-xs font-medium rounded shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                >
                  Trust Device
                </button>
                
                <button
                  v-else
                  @click="revokeTrust(device)"
                  class="inline-flex items-center px-2.5 py-1.5 border border-transparent text-xs font-medium rounded shadow-sm text-white bg-yellow-600 hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500"
                >
                  Revoke Trust
                </button>
                
                <button
                  v-if="!device.is_current"
                  @click="removeDevice(device)"
                  class="inline-flex items-center px-2.5 py-1.5 border border-transparent text-xs font-medium rounded shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                >
                  Remove
                </button>
              </div>
            </div>
          </div>
        </div>

        <div class="mt-6 flex flex-col sm:flex-row gap-3" v-if="devices.length > 1">
          <button
            @click="removeAllDevices"
            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
          >
            Remove All Other Devices
          </button>
        </div>
      </div>
    </div>

    <!-- Device Name Modal -->
    <div v-if="showNameModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
      <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
        <div class="mt-3">
          <h3 class="text-lg font-medium text-gray-900 dark:text-white">
            Rename Device
          </h3>
          <div class="mt-4">
            <input
              v-model="editingDevice.name"
              type="text"
              class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-white"
              placeholder="Enter device name"
              maxlength="100"
              @keyup.enter="saveDeviceName"
            />
          </div>
          <div class="mt-4 flex space-x-3">
            <button
              @click="saveDeviceName"
              :disabled="saving"
              class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
            >
              {{ saving ? 'Saving...' : 'Save' }}
            </button>
            <button
              @click="closeNameModal"
              class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md shadow-sm text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              Cancel
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- 2FA Verification Modal -->
    <div v-if="show2FAModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
      <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
        <div class="mt-3">
          <h3 class="text-lg font-medium text-gray-900 dark:text-white">
            Verify with 2FA
          </h3>
          <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
            Enter your two-factor authentication code to confirm this action.
          </p>
          <div class="mt-4">
            <input
              v-model="twoFactorCode"
              type="text"
              class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-white"
              placeholder="Enter 6-digit code"
              maxlength="6"
              @keyup.enter="confirm2FA"
            />
          </div>
          <div class="mt-4 flex space-x-3">
            <button
              @click="confirm2FA"
              :disabled="processing"
              class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
            >
              {{ processing ? 'Verifying...' : 'Verify' }}
            </button>
            <button
              @click="close2FAModal"
              class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md shadow-sm text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              Cancel
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { formatDistanceToNow } from 'date-fns'

// Icons
const DevicePhoneMobileIcon = {
  template: `
    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a1 1 0 001-1V4a1 1 0 00-1-1H8a1 1 0 00-1 1v16a1 1 0 001 1z" />
    </svg>
  `
}

const DeviceTabletIcon = {
  template: `
    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M7 21h10a1 1 0 001-1V4a1 1 0 00-1-1H7a1 1 0 00-1 1v16a1 1 0 001 1z" />
    </svg>
  `
}

const ComputerDesktopIcon = {
  template: `
    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H4a1 1 0 01-1-1V4a1 1 0 011-1h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a1 1 0 01-1 1z" />
    </svg>
  `
}

// Reactive data
const devices = ref([])
const loading = ref(true)
const saving = ref(false)
const processing = ref(false)

// Modal states
const showNameModal = ref(false)
const show2FAModal = ref(false)
const editingDevice = ref({ id: null, name: '' })
const twoFactorCode = ref('')
const pendingAction = ref(null)

// Methods
const loadDevices = async () => {
  try {
    loading.value = true
    const response = await fetch('/api/devices')
    const data = await response.json()
    devices.value = data.devices
  } catch (error) {
    console.error('Failed to load devices:', error)
  } finally {
    loading.value = false
  }
}

const getDeviceIcon = (deviceType) => {
  switch (deviceType) {
    case 'mobile':
      return DevicePhoneMobileIcon
    case 'tablet':
      return DeviceTabletIcon
    default:
      return ComputerDesktopIcon
  }
}

const formatDate = (dateString) => {
  return formatDistanceToNow(new Date(dateString), { addSuffix: true })
}

const editDeviceName = (device) => {
  editingDevice.value = {
    id: device.id,
    name: device.display_name
  }
  showNameModal.value = true
}

const closeNameModal = () => {
  showNameModal.value = false
  editingDevice.value = { id: null, name: '' }
}

const saveDeviceName = async () => {
  if (!editingDevice.value.name.trim()) return

  try {
    saving.value = true
    const response = await fetch(`/api/devices/${editingDevice.value.id}/name`, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      },
      body: JSON.stringify({ name: editingDevice.value.name })
    })

    if (response.ok) {
      await loadDevices()
      closeNameModal()
    } else {
      console.error('Failed to update device name')
    }
  } catch (error) {
    console.error('Error updating device name:', error)
  } finally {
    saving.value = false
  }
}

const trustDevice = (device) => {
  pendingAction.value = { type: 'trust', device }
  show2FAModal.value = true
}

const revokeTrust = async (device) => {
  if (!confirm('Are you sure you want to revoke trust for this device?')) return

  try {
    const response = await fetch(`/api/devices/${device.id}/trust`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      }
    })

    if (response.ok) {
      await loadDevices()
    } else {
      console.error('Failed to revoke device trust')
    }
  } catch (error) {
    console.error('Error revoking device trust:', error)
  }
}

const removeDevice = async (device) => {
  if (!confirm('Are you sure you want to remove this device?')) return

  try {
    const response = await fetch(`/api/devices/${device.id}`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      }
    })

    if (response.ok) {
      await loadDevices()
    } else {
      console.error('Failed to remove device')
    }
  } catch (error) {
    console.error('Error removing device:', error)
  }
}

const removeAllDevices = () => {
  pendingAction.value = { type: 'removeAll' }
  show2FAModal.value = true
}

const close2FAModal = () => {
  show2FAModal.value = false
  twoFactorCode.value = ''
  pendingAction.value = null
}

const confirm2FA = async () => {
  if (!twoFactorCode.value.trim() || !pendingAction.value) return

  try {
    processing.value = true
    
    if (pendingAction.value.type === 'trust') {
      const response = await fetch(`/api/devices/${pendingAction.value.device.id}/trust`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ code: twoFactorCode.value })
      })

      if (response.ok) {
        await loadDevices()
        close2FAModal()
      } else {
        const data = await response.json()
        alert(data.message || 'Failed to trust device')
      }
    } else if (pendingAction.value.type === 'removeAll') {
      const response = await fetch('/api/devices/all', {
        method: 'DELETE',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ code: twoFactorCode.value })
      })

      if (response.ok) {
        await loadDevices()
        close2FAModal()
      } else {
        const data = await response.json()
        alert(data.message || 'Failed to remove devices')
      }
    }
  } catch (error) {
    console.error('Error confirming 2FA:', error)
  } finally {
    processing.value = false
  }
}

// Initialize
onMounted(() => {
  loadDevices()
})
</script>