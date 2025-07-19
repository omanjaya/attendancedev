<template>
  <div class="two-factor-dashboard">
    <!-- Header Section -->
    <div class="dashboard-header">
      <div class="header-content">
        <div class="header-info">
          <h1 class="dashboard-title">
            Two-Factor Authentication
          </h1>
          <p class="dashboard-subtitle">
            Manage your account security and two-factor authentication settings
          </p>
        </div>
        <div class="header-status">
          <div class="status-badge" :class="statusBadgeClass">
            <Icon :name="statusIcon" class="h-4 w-4" />
            <span>{{ statusText }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Security Overview -->
    <div class="security-overview">
      <div class="overview-cards">
        <!-- 2FA Status Card -->
        <div class="overview-card">
          <div class="card-icon" :class="status.enabled ? 'enabled' : 'disabled'">
            <Icon name="shield-check" class="h-6 w-6" />
          </div>
          <div class="card-content">
            <h3 class="card-title">
              Two-Factor Authentication
            </h3>
            <p class="card-description">
              {{ status.enabled ? 'Active and protecting your account' : 'Not yet configured' }}
            </p>
            <div v-if="status.enabled" class="card-meta">
              <span class="meta-item">
                <Icon name="calendar" class="h-3 w-3" />
                Enabled {{ formatDate(user.two_factor_enabled_at) }}
              </span>
            </div>
          </div>
        </div>

        <!-- Recovery Codes Card -->
        <div class="overview-card">
          <div class="card-icon" :class="recoveryCodesStatus">
            <Icon name="key" class="h-6 w-6" />
          </div>
          <div class="card-content">
            <h3 class="card-title">
              Recovery Codes
            </h3>
            <p class="card-description">
              {{ recoveryCodesCount }} unused codes remaining
            </p>
            <div v-if="recoveryCodesCount <= 2" class="card-warning">
              <Icon name="exclamation-triangle" class="h-3 w-3" />
              <span>Running low - consider regenerating</span>
            </div>
          </div>
        </div>

        <!-- Security Score Card -->
        <div class="overview-card">
          <div class="card-icon score">
            <Icon name="trending-up" class="h-6 w-6" />
          </div>
          <div class="card-content">
            <h3 class="card-title">
              Security Score
            </h3>
            <div class="score-display">
              <div class="score-value">
                {{ securityScore }}/100
              </div>
              <div class="score-bar">
                <div
                  class="score-progress"
                  :style="{ width: `${securityScore}%` }"
                  :class="scoreClass"
                />
              </div>
            </div>
            <p class="card-description">
              {{ securityScoreText }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Management Section -->
    <div class="management-section">
      <div class="management-grid">
        <!-- Setup/Status Panel -->
        <div class="management-panel">
          <div class="panel-header">
            <h2 class="panel-title">
              Authentication Setup
            </h2>
            <div v-if="status.required" class="required-badge">
              <Icon name="exclamation" class="h-3 w-3" />
              <span>Required</span>
            </div>
          </div>

          <div class="panel-content">
            <!-- 2FA Not Enabled -->
            <div v-if="!status.enabled" class="setup-section">
              <div class="setup-info">
                <Icon name="smartphone" class="h-8 w-8 text-blue-600" />
                <div>
                  <h3 class="setup-title">
                    Set up two-factor authentication
                  </h3>
                  <p class="setup-description">
                    Add an extra layer of security to your account by requiring a code from your
                    phone.
                  </p>
                </div>
              </div>

              <div class="setup-steps">
                <div class="step">
                  <div class="step-number">
                    1
                  </div>
                  <span>Install authenticator app</span>
                </div>
                <div class="step">
                  <div class="step-number">
                    2
                  </div>
                  <span>Scan QR code or enter secret key</span>
                </div>
                <div class="step">
                  <div class="step-number">
                    3
                  </div>
                  <span>Verify with generated code</span>
                </div>
              </div>

              <div class="setup-actions">
                <button class="btn-primary" :disabled="loading" @click="startSetup">
                  <div v-if="loading" class="spinner" />
                  <Icon v-else name="plus" class="h-4 w-4" />
                  {{ loading ? 'Setting up...' : 'Enable Two-Factor Authentication' }}
                </button>
              </div>
            </div>

            <!-- 2FA Enabled -->
            <div v-else class="enabled-section">
              <div class="enabled-info">
                <Icon name="check-circle" class="h-8 w-8 text-green-600" />
                <div>
                  <h3 class="enabled-title">
                    Two-factor authentication is active
                  </h3>
                  <p class="enabled-description">
                    Your account is secured with two-factor authentication.
                  </p>
                  <div class="enabled-meta">
                    <span class="meta-item">
                      <Icon name="calendar" class="h-3 w-3" />
                      Enabled {{ formatDate(user.two_factor_enabled_at) }}
                    </span>
                    <span class="meta-item">
                      <Icon name="smartphone" class="h-3 w-3" />
                      Authenticator app
                    </span>
                  </div>
                </div>
              </div>

              <div class="enabled-actions">
                <button class="btn-secondary" @click="showQRCode">
                  <Icon name="qr-code" class="h-4 w-4" />
                  Show QR Code
                </button>
                <button
                  v-if="!status.required"
                  class="btn-danger"
                  :disabled="loading"
                  @click="confirmDisable"
                >
                  <div v-if="loading" class="spinner" />
                  <Icon v-else name="x" class="h-4 w-4" />
                  {{ loading ? 'Disabling...' : 'Disable 2FA' }}
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Recovery Codes Panel -->
        <div class="management-panel">
          <div class="panel-header">
            <h2 class="panel-title">
              Recovery Codes
            </h2>
            <div class="recovery-status" :class="recoveryCodesStatus">
              <Icon :name="recoveryCodesIcon" class="h-3 w-3" />
              <span>{{ recoveryCodesCount }} remaining</span>
            </div>
          </div>

          <div class="panel-content">
            <div class="recovery-info">
              <p class="recovery-description">
                Recovery codes allow you to access your account if you lose your authenticator
                device. Each code can only be used once.
              </p>

              <div v-if="recoveryCodesCount <= 2" class="recovery-warning">
                <Icon name="exclamation-triangle" class="h-4 w-4" />
                <div>
                  <h4>Running low on recovery codes</h4>
                  <p>You should regenerate new codes before you run out.</p>
                </div>
              </div>
            </div>

            <div class="recovery-actions">
              <button
                class="btn-secondary"
                :disabled="!status.enabled || loading"
                @click="showRecoveryCodes"
              >
                <Icon name="eye" class="h-4 w-4" />
                View Recovery Codes
              </button>
              <button
                class="btn-primary"
                :disabled="!status.enabled || loading"
                @click="regenerateRecoveryCodes"
              >
                <div v-if="loading" class="spinner" />
                <Icon v-else name="refresh" class="h-4 w-4" />
                {{ loading ? 'Generating...' : 'Generate New Codes' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Activity -->
    <div class="activity-section">
      <div class="activity-header">
        <h2 class="activity-title">
          Recent Security Activity
        </h2>
        <button class="refresh-btn" @click="refreshActivity">
          <Icon name="refresh" class="h-4 w-4" />
          <span>Refresh</span>
        </button>
      </div>

      <div class="activity-list">
        <div v-for="activity in recentActivity" :key="activity.id" class="activity-item">
          <div class="activity-icon" :class="activity.type">
            <Icon :name="getActivityIcon(activity.type)" class="h-4 w-4" />
          </div>
          <div class="activity-content">
            <div class="activity-description">
              {{ activity.description }}
            </div>
            <div class="activity-meta">
              <span class="activity-time">{{ formatDateTime(activity.created_at) }}</span>
              <span class="activity-location">{{ activity.location }}</span>
            </div>
          </div>
          <div v-if="activity.status" class="activity-status" :class="activity.status">
            {{ activity.status }}
          </div>
        </div>

        <div v-if="recentActivity.length === 0" class="no-activity">
          <Icon name="clock" class="h-8 w-8 text-gray-400" />
          <p>No recent security activity</p>
        </div>
      </div>
    </div>

    <!-- Modals -->
    <TwoFactorSetupModal
      v-if="showSetupModal"
      @close="showSetupModal = false"
      @complete="handleSetupComplete"
    />

    <QRCodeModal v-if="showQRModal" :qrCode="qrCodeData" @close="showQRModal = false" />

    <RecoveryCodesModal
      v-if="showRecoveryModal"
      :codes="displayRecoveryCodes"
      :isNew="isNewCodes"
      @close="showRecoveryModal = false"
      @downloaded="handleCodesDownloaded"
    />

    <ConfirmationModal
      v-if="showConfirmModal"
      :title="confirmModal.title"
      :message="confirmModal.message"
      :confirmText="confirmModal.confirmText"
      :danger="confirmModal.danger"
      @confirm="handleConfirmAction"
      @cancel="showConfirmModal = false"
    />
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useToast } from '@/composables/useToast'
import { twoFactorService } from '@/services/twoFactor'
import TwoFactorSetupModal from './TwoFactorSetupModal.vue'
import QRCodeModal from './QRCodeModal.vue'
import RecoveryCodesModal from './RecoveryCodesModal.vue'
import ConfirmationModal from './ConfirmationModal.vue'

// Props
const props = defineProps({
  user: {
    type: Object,
    required: true,
  },
  initialStatus: {
    type: Object,
    default: () => ({}),
  },
})

// Reactive data
const loading = ref(false)
const showSetupModal = ref(false)
const showQRModal = ref(false)
const showRecoveryModal = ref(false)
const showConfirmModal = ref(false)
const qrCodeData = ref('')
const displayRecoveryCodes = ref([])
const isNewCodes = ref(false)
const recentActivity = ref([])

const status = reactive({
  enabled: false,
  required: false,
  verified: false,
  hasRecoveryCodes: false,
  recoveryCodesCount: 0,
  ...props.initialStatus,
})

const confirmModal = reactive({
  title: '',
  message: '',
  confirmText: '',
  danger: false,
  action: null,
})

// Composables
const { toast } = useToast()

// Computed
const statusBadgeClass = computed(() => {
  if (status.enabled) {return 'status-enabled'}
  if (status.required) {return 'status-warning'}
  return 'status-disabled'
})

const statusIcon = computed(() => {
  if (status.enabled) {return 'check-circle'}
  if (status.required) {return 'exclamation-triangle'}
  return 'x-circle'
})

const statusText = computed(() => {
  if (status.enabled) {return 'Active'}
  if (status.required) {return 'Required - Not Set Up'}
  return 'Disabled'
})

const recoveryCodesCount = computed(() => status.recoveryCodesCount || 0)

const recoveryCodesStatus = computed(() => {
  if (recoveryCodesCount.value === 0) {return 'critical'}
  if (recoveryCodesCount.value <= 2) {return 'warning'}
  return 'good'
})

const recoveryCodesIcon = computed(() => {
  if (recoveryCodesCount.value === 0) {return 'exclamation-triangle'}
  if (recoveryCodesCount.value <= 2) {return 'exclamation'}
  return 'check-circle'
})

const securityScore = computed(() => {
  let score = 0
  if (status.enabled) {score += 70}
  if (status.hasRecoveryCodes && recoveryCodesCount.value > 2) {score += 20}
  if (props.user.phone) {score += 10}
  return Math.min(score, 100)
})

const scoreClass = computed(() => {
  if (securityScore.value >= 80) {return 'score-excellent'}
  if (securityScore.value >= 60) {return 'score-good'}
  if (securityScore.value >= 40) {return 'score-fair'}
  return 'score-poor'
})

const securityScoreText = computed(() => {
  if (securityScore.value >= 80) {return 'Excellent security'}
  if (securityScore.value >= 60) {return 'Good security'}
  if (securityScore.value >= 40) {return 'Fair security'}
  return 'Poor security'
})

// Methods
const startSetup = () => {
  showSetupModal.value = true
}

const handleSetupComplete = (result) => {
  showSetupModal.value = false
  status.enabled = true
  status.hasRecoveryCodes = true
  status.recoveryCodesCount = result.recovery_codes?.length || 0

  // Show recovery codes
  displayRecoveryCodes.value = result.recovery_codes || []
  isNewCodes.value = true
  showRecoveryModal.value = true

  toast.success('Two-factor authentication enabled successfully!')
  refreshStatus()
}

const showQRCode = async () => {
  loading.value = true
  try {
    const response = await twoFactorService.getQRCode()
    qrCodeData.value = response.qr_code
    showQRModal.value = true
  } catch (error) {
    toast.error('Failed to load QR code')
  } finally {
    loading.value = false
  }
}

const showRecoveryCodes = async () => {
  loading.value = true
  try {
    const response = await twoFactorService.getRecoveryCodes()
    displayRecoveryCodes.value = response.codes
    isNewCodes.value = false
    showRecoveryModal.value = true
  } catch (error) {
    toast.error('Failed to load recovery codes')
  } finally {
    loading.value = false
  }
}

const regenerateRecoveryCodes = () => {
  confirmModal.title = 'Regenerate Recovery Codes'
  confirmModal.message =
    'This will invalidate all existing recovery codes and generate new ones. Make sure to save the new codes securely.'
  confirmModal.confirmText = 'Regenerate Codes'
  confirmModal.danger = true
  confirmModal.action = 'regenerate-codes'
  showConfirmModal.value = true
}

const confirmDisable = () => {
  confirmModal.title = 'Disable Two-Factor Authentication'
  confirmModal.message =
    'This will remove two-factor authentication from your account. Your account will be less secure.'
  confirmModal.confirmText = 'Disable 2FA'
  confirmModal.danger = true
  confirmModal.action = 'disable-2fa'
  showConfirmModal.value = true
}

const handleConfirmAction = async (password) => {
  loading.value = true
  showConfirmModal.value = false

  try {
    if (confirmModal.action === 'regenerate-codes') {
      const response = await twoFactorService.regenerateRecoveryCodes(password)
      displayRecoveryCodes.value = response.recovery_codes
      isNewCodes.value = true
      showRecoveryModal.value = true
      status.recoveryCodesCount = response.recovery_codes.length
      toast.success('Recovery codes regenerated successfully!')
    } else if (confirmModal.action === 'disable-2fa') {
      await twoFactorService.disable(password)
      status.enabled = false
      status.hasRecoveryCodes = false
      status.recoveryCodesCount = 0
      toast.success('Two-factor authentication disabled')
    }

    refreshStatus()
  } catch (error) {
    toast.error(error.message || 'Operation failed')
  } finally {
    loading.value = false
  }
}

const handleCodesDownloaded = () => {
  // Track that user has downloaded their codes
  toast.success('Recovery codes downloaded. Store them securely!')
}

const refreshStatus = async () => {
  try {
    const response = await twoFactorService.getStatus()
    Object.assign(status, response)
  } catch (error) {
    console.error('Failed to refresh status:', error)
  }
}

const refreshActivity = async () => {
  try {
    const response = await fetch('/api/security/activity')
    const data = await response.json()
    recentActivity.value = data.activities || []
  } catch (error) {
    console.error('Failed to load activity:', error)
  }
}

const getActivityIcon = (type) => {
  const icons = {
    login: 'log-in',
    failed_login: 'x-circle',
    '2fa_verified': 'check-circle',
    '2fa_failed': 'x-circle',
    '2fa_enabled': 'shield-check',
    '2fa_disabled': 'shield-off',
    recovery_used: 'key',
    codes_regenerated: 'refresh',
  }
  return icons[type] || 'activity'
}

const formatDate = (dateString) => {
  if (!dateString) {return 'Unknown'}
  return new Date(dateString).toLocaleDateString()
}

const formatDateTime = (dateString) => {
  if (!dateString) {return 'Unknown'}
  return new Date(dateString).toLocaleString()
}

// Lifecycle
onMounted(() => {
  refreshStatus()
  refreshActivity()
})
</script>

<style scoped>
.two-factor-dashboard {
  @apply mx-auto max-w-7xl space-y-8 p-6;
}

.dashboard-header {
  @apply rounded-lg bg-white p-6 shadow-sm;
}

.header-content {
  @apply flex items-center justify-between;
}

.dashboard-title {
  @apply text-2xl font-bold text-gray-900;
}

.dashboard-subtitle {
  @apply mt-1 text-gray-600;
}

.status-badge {
  @apply inline-flex items-center space-x-2 rounded-full px-3 py-1 text-sm font-medium;
}

.status-badge.status-enabled {
  @apply bg-green-100 text-green-800;
}

.status-badge.status-warning {
  @apply bg-yellow-100 text-yellow-800;
}

.status-badge.status-disabled {
  @apply bg-red-100 text-red-800;
}

.security-overview {
  @apply rounded-lg bg-white p-6 shadow-sm;
}

.overview-cards {
  @apply grid grid-cols-1 gap-6 md:grid-cols-3;
}

.overview-card {
  @apply flex items-start space-x-4 rounded-lg border border-gray-200 p-4;
}

.card-icon {
  @apply flex h-12 w-12 items-center justify-center rounded-lg;
}

.card-icon.enabled {
  @apply bg-green-100 text-green-600;
}

.card-icon.disabled {
  @apply bg-red-100 text-red-600;
}

.card-icon.good {
  @apply bg-green-100 text-green-600;
}

.card-icon.warning {
  @apply bg-yellow-100 text-yellow-600;
}

.card-icon.critical {
  @apply bg-red-100 text-red-600;
}

.card-icon.score {
  @apply bg-blue-100 text-blue-600;
}

.card-title {
  @apply font-semibold text-gray-900;
}

.card-description {
  @apply mt-1 text-sm text-gray-600;
}

.card-meta {
  @apply mt-2 flex items-center space-x-4 text-xs text-gray-500;
}

.meta-item {
  @apply flex items-center space-x-1;
}

.card-warning {
  @apply mt-2 flex items-center space-x-1 text-xs text-yellow-600;
}

.score-display {
  @apply mt-2;
}

.score-value {
  @apply text-lg font-bold text-gray-900;
}

.score-bar {
  @apply mt-1 h-2 w-full rounded-full bg-gray-200;
}

.score-progress {
  @apply h-full rounded-full transition-all duration-300;
}

.score-progress.score-excellent {
  @apply bg-green-500;
}

.score-progress.score-good {
  @apply bg-blue-500;
}

.score-progress.score-fair {
  @apply bg-yellow-500;
}

.score-progress.score-poor {
  @apply bg-red-500;
}

.management-section {
  @apply space-y-6;
}

.management-grid {
  @apply grid grid-cols-1 gap-6 lg:grid-cols-2;
}

.management-panel {
  @apply rounded-lg bg-white shadow-sm;
}

.panel-header {
  @apply flex items-center justify-between border-b border-gray-200 p-6;
}

.panel-title {
  @apply text-lg font-semibold text-gray-900;
}

.required-badge {
  @apply inline-flex items-center space-x-1 rounded bg-yellow-100 px-2 py-1 text-xs font-medium text-yellow-800;
}

.recovery-status {
  @apply inline-flex items-center space-x-1 rounded px-2 py-1 text-xs font-medium;
}

.recovery-status.good {
  @apply bg-green-100 text-green-800;
}

.recovery-status.warning {
  @apply bg-yellow-100 text-yellow-800;
}

.recovery-status.critical {
  @apply bg-red-100 text-red-800;
}

.panel-content {
  @apply p-6;
}

.setup-section,
.enabled-section {
  @apply space-y-6;
}

.setup-info,
.enabled-info {
  @apply flex items-start space-x-4;
}

.setup-title,
.enabled-title {
  @apply text-lg font-semibold text-gray-900;
}

.setup-description,
.enabled-description {
  @apply mt-1 text-gray-600;
}

.enabled-meta {
  @apply mt-2 flex items-center space-x-4 text-sm text-gray-500;
}

.setup-steps {
  @apply space-y-3;
}

.step {
  @apply flex items-center space-x-3;
}

.step-number {
  @apply flex h-6 w-6 items-center justify-center rounded-full bg-blue-600 text-sm font-medium text-white;
}

.setup-actions,
.enabled-actions,
.recovery-actions {
  @apply flex space-x-3;
}

.btn-primary {
  @apply inline-flex items-center space-x-2 rounded-lg bg-blue-600 px-4 py-2 font-medium text-white transition-colors duration-200 hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50;
}

.btn-secondary {
  @apply inline-flex items-center space-x-2 rounded-lg border border-gray-300 bg-white px-4 py-2 font-medium text-gray-700 transition-colors duration-200 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50;
}

.btn-danger {
  @apply inline-flex items-center space-x-2 rounded-lg bg-red-600 px-4 py-2 font-medium text-white transition-colors duration-200 hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-50;
}

.spinner {
  @apply h-4 w-4 animate-spin rounded-full border-b-2 border-white;
}

.recovery-info {
  @apply space-y-4;
}

.recovery-description {
  @apply text-gray-600;
}

.recovery-warning {
  @apply flex items-start space-x-3 rounded-lg border border-yellow-200 bg-yellow-50 p-3;
}

.recovery-warning h4 {
  @apply font-medium text-yellow-900;
}

.recovery-warning p {
  @apply mt-1 text-sm text-yellow-800;
}

.activity-section {
  @apply rounded-lg bg-white shadow-sm;
}

.activity-header {
  @apply flex items-center justify-between border-b border-gray-200 p-6;
}

.activity-title {
  @apply text-lg font-semibold text-gray-900;
}

.refresh-btn {
  @apply inline-flex items-center space-x-2 text-sm text-gray-600 transition-colors duration-200 hover:text-gray-900;
}

.activity-list {
  @apply space-y-4 p-6;
}

.activity-item {
  @apply flex items-start space-x-4 rounded-lg border border-gray-200 p-3;
}

.activity-icon {
  @apply flex h-8 w-8 items-center justify-center rounded-full;
}

.activity-icon.login {
  @apply bg-green-100 text-green-600;
}

.activity-icon.failed_login,
.activity-icon.2fa_failed {
  @apply bg-red-100 text-red-600;
}

.activity-icon.2fa_verified,
.activity-icon.2fa_enabled {
  @apply bg-blue-100 text-blue-600;
}

.activity-content {
  @apply flex-1;
}

.activity-description {
  @apply font-medium text-gray-900;
}

.activity-meta {
  @apply mt-1 flex items-center space-x-4 text-sm text-gray-500;
}

.activity-status {
  @apply rounded px-2 py-1 text-xs font-medium;
}

.activity-status.success {
  @apply bg-green-100 text-green-800;
}

.activity-status.failed {
  @apply bg-red-100 text-red-800;
}

.no-activity {
  @apply py-8 text-center text-gray-500;
}

/* Mobile optimizations */
@media (max-width: 768px) {
  .two-factor-dashboard {
    @apply p-4;
  }

  .header-content {
    @apply flex-col items-start space-y-4;
  }

  .overview-cards {
    @apply grid-cols-1;
  }

  .management-grid {
    @apply grid-cols-1;
  }

  .setup-actions,
  .enabled-actions,
  .recovery-actions {
    @apply flex-col space-x-0 space-y-2;
  }
}
</style>
