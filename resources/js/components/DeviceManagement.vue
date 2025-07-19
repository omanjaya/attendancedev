<template>
  <div class="space-y-6">
    <div class="bg-white shadow dark:bg-gray-800 sm:rounded-lg">
      <div class="px-4 py-5 sm:p-6">
        <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white">
          Trusted Devices
        </h3>
        <div class="mt-2 max-w-xl text-sm text-gray-500 dark:text-gray-400">
          <p>
            Manage devices you trust for two-factor authentication. Trusted devices won't require
            2FA codes for login.
          </p>
        </div>

        <div v-if="loading" class="mt-5">
          <div class="flex items-center justify-center py-8">
            <div class="h-8 w-8 animate-spin rounded-full border-b-2 border-indigo-600" />
          </div>
        </div>

        <div v-else-if="devices.length === 0" class="mt-5">
          <div class="py-8 text-center">
            <svg
              class="mx-auto h-12 w-12 text-gray-400"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
              aria-hidden="true"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
              />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
              No devices
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
              You haven't trusted any devices yet.
            </p>
          </div>
        </div>

        <div v-else class="mt-5 space-y-3">
          <div
            v-for="device in devices"
            :key="device.id"
            class="relative rounded-lg border border-gray-300 bg-white px-6 py-5 shadow-sm focus-within:ring-2 focus-within:ring-indigo-500 focus-within:ring-offset-2 hover:border-gray-400 dark:border-gray-600 dark:bg-gray-700 dark:hover:border-gray-500"
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
                    <p class="truncate text-sm font-medium text-gray-900 dark:text-white">
                      {{ device.display_name }}
                    </p>
                    <span
                      v-if="device.is_current"
                      class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-200"
                    >
                      Current Device
                    </span>
                    <span
                      v-if="device.is_trusted"
                      class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-200"
                    >
                      Trusted
                    </span>
                  </div>
                  <div class="text-sm text-gray-500 dark:text-gray-400">
                    {{ device.browser_name }} on {{ device.os_name }}
                  </div>
                  <div class="text-xs text-gray-400 dark:text-gray-500">
                    Last seen {{ formatDate(device.last_seen_at) }} from
                    {{ device.last_ip_address }}
                  </div>
                </div>
              </div>

              <div class="flex items-center space-x-2">
                <button
                  class="inline-flex items-center rounded border border-gray-300 bg-white px-2.5 py-1.5 text-xs font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:border-gray-600 dark:bg-gray-600 dark:text-gray-300 dark:hover:bg-gray-500"
                  @click="editDeviceName(device)"
                >
                  Rename
                </button>

                <button
                  v-if="!device.is_trusted"
                  class="inline-flex items-center rounded border border-transparent bg-indigo-600 px-2.5 py-1.5 text-xs font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                  @click="trustDevice(device)"
                >
                  Trust Device
                </button>

                <button
                  v-else
                  class="inline-flex items-center rounded border border-transparent bg-yellow-600 px-2.5 py-1.5 text-xs font-medium text-white shadow-sm hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2"
                  @click="revokeTrust(device)"
                >
                  Revoke Trust
                </button>

                <button
                  v-if="!device.is_current"
                  class="inline-flex items-center rounded border border-transparent bg-red-600 px-2.5 py-1.5 text-xs font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
                  @click="removeDevice(device)"
                >
                  Remove
                </button>
              </div>
            </div>
          </div>
        </div>

        <div v-if="devices.length > 1" class="mt-6 flex flex-col gap-3 sm:flex-row">
          <button
            class="inline-flex items-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
            @click="removeAllDevices"
          >
            Remove All Other Devices
          </button>
        </div>
      </div>
    </div>

    <!-- Device Name Modal -->
    <div
      v-if="showNameModal"
      class="fixed inset-0 z-50 h-full w-full overflow-y-auto bg-gray-600 bg-opacity-50"
    >
      <div
        class="relative top-20 mx-auto w-96 rounded-md border bg-white p-5 shadow-lg dark:bg-gray-800"
      >
        <div class="mt-3">
          <h3 class="text-lg font-medium text-gray-900 dark:text-white">
            Rename Device
          </h3>
          <div class="mt-4">
            <input
              v-model="editingDevice.name"
              type="text"
              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
              placeholder="Enter device name"
              maxlength="100"
              @keyup.enter="saveDeviceName"
            >
          </div>
          <div class="mt-4 flex space-x-3">
            <button
              :disabled="saving"
              class="inline-flex items-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50"
              @click="saveDeviceName"
            >
              {{ saving ? 'Saving...' : 'Save' }}
            </button>
            <button
              class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
              @click="closeNameModal"
            >
              Cancel
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- 2FA Verification Modal -->
    <div
      v-if="show2FAModal"
      class="fixed inset-0 z-50 h-full w-full overflow-y-auto bg-gray-600 bg-opacity-50"
    >
      <div
        class="relative top-20 mx-auto w-96 rounded-md border bg-white p-5 shadow-lg dark:bg-gray-800"
      >
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
              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
              placeholder="Enter 6-digit code"
              maxlength="6"
              @keyup.enter="confirm2FA"
            >
          </div>
          <div class="mt-4 flex space-x-3">
            <button
              :disabled="processing"
              class="inline-flex items-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50"
              @click="confirm2FA"
            >
              {{ processing ? 'Verifying...' : 'Verify' }}
            </button>
            <button
              class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
              @click="close2FAModal"
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
  `,
}

const DeviceTabletIcon = {
  template: `
    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M7 21h10a1 1 0 001-1V4a1 1 0 00-1-1H7a1 1 0 00-1 1v16a1 1 0 001 1z" />
    </svg>
  `,
}

const ComputerDesktopIcon = {
  template: `
    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H4a1 1 0 01-1-1V4a1 1 0 011-1h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a1 1 0 01-1 1z" />
    </svg>
  `,
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
    name: device.display_name,
  }
  showNameModal.value = true
}

const closeNameModal = () => {
  showNameModal.value = false
  editingDevice.value = { id: null, name: '' }
}

const saveDeviceName = async () => {
  if (!editingDevice.value.name.trim()) {return}

  try {
    saving.value = true
    const response = await fetch(`/api/devices/${editingDevice.value.id}/name`, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
      },
      body: JSON.stringify({ name: editingDevice.value.name }),
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
  if (!confirm('Are you sure you want to revoke trust for this device?')) {return}

  try {
    const response = await fetch(`/api/devices/${device.id}/trust`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
      },
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
  if (!confirm('Are you sure you want to remove this device?')) {return}

  try {
    const response = await fetch(`/api/devices/${device.id}`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
      },
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
  if (!twoFactorCode.value.trim() || !pendingAction.value) {return}

  try {
    processing.value = true

    if (pendingAction.value.type === 'trust') {
      const response = await fetch(`/api/devices/${pendingAction.value.device.id}/trust`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({ code: twoFactorCode.value }),
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
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({ code: twoFactorCode.value }),
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
