# 🔧 Technical Implementation Guide
## Frontend Integration Architecture & Best Practices

### 📐 System Architecture Overview

```
┌─────────────────────────────────────────────────────────────┐
│                    Frontend Layer (Vue.js 3)                │
├─────────────────────────────────────────────────────────────┤
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐ │
│  │   Components    │  │  State Store    │  │   Services      │ │
│  │   (Vue 3 SFC)   │  │   (Pinia)       │  │  (API Layer)    │ │
│  └─────────────────┘  └─────────────────┘  └─────────────────┘ │
├─────────────────────────────────────────────────────────────┤
│                    API Gateway Layer                        │
├─────────────────────────────────────────────────────────────┤
│                    Backend Services Layer                   │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐ │
│  │   Controllers   │  │    Services     │  │    Models       │ │
│  │   (Laravel)     │  │  (Business      │  │  (Eloquent)     │ │
│  │                 │  │   Logic)        │  │                 │ │
│  └─────────────────┘  └─────────────────┘  └─────────────────┘ │
├─────────────────────────────────────────────────────────────┤
│                    Database Layer                           │
└─────────────────────────────────────────────────────────────┘
```

---

## 🎯 Integration Implementation Strategy

### 1. Component Architecture Pattern

#### 1.1 Component Hierarchy
```
App.vue
├── Layout/
│   ├── AppLayout.vue
│   ├── Sidebar.vue
│   └── Header.vue
├── Pages/
│   ├── Dashboard/
│   │   ├── DashboardPage.vue
│   │   ├── StatsCards.vue
│   │   └── Charts/
│   ├── Attendance/
│   │   ├── AttendanceList.vue
│   │   ├── FaceRecognition.vue
│   │   └── CheckIn.vue
│   └── Security/
│       ├── TwoFactorSetup.vue
│       ├── SecurityDashboard.vue
│       └── AuditLogs.vue
└── Shared/
    ├── UI/
    │   ├── Button.vue
    │   ├── Modal.vue
    │   └── Table.vue
    └── Utils/
        ├── DatePicker.vue
        └── FileUpload.vue
```

#### 1.2 Component Communication Pattern
```javascript
// Parent-Child Communication
<template>
  <ChildComponent 
    :data="parentData" 
    @update="handleUpdate"
  />
</template>

// Store-based Communication
import { useAttendanceStore } from '@/stores/attendance'

// Service-based Data Fetching
import { attendanceService } from '@/services/attendance'
```

### 2. State Management Architecture

#### 2.1 Pinia Store Structure
```javascript
// stores/index.js
export { useAuthStore } from './auth'
export { useAttendanceStore } from './attendance'
export { useFaceRecognitionStore } from './faceRecognition'
export { usePayrollStore } from './payroll'
export { useSecurityStore } from './security'

// stores/attendance.js
import { defineStore } from 'pinia'
import { attendanceService } from '@/services/attendance'

export const useAttendanceStore = defineStore('attendance', {
  state: () => ({
    attendances: [],
    currentAttendance: null,
    statistics: {},
    loading: false,
    error: null
  }),
  
  getters: {
    todayAttendances: (state) => state.attendances.filter(/* logic */),
    attendanceStats: (state) => state.statistics
  },
  
  actions: {
    async fetchAttendances() {
      this.loading = true
      try {
        this.attendances = await attendanceService.getAll()
      } catch (error) {
        this.error = error.message
      } finally {
        this.loading = false
      }
    }
  }
})
```

#### 2.2 Service Layer Pattern
```javascript
// services/base.js
class BaseService {
  constructor(baseURL) {
    this.api = axios.create({
      baseURL,
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
    
    this.setupInterceptors()
  }
  
  setupInterceptors() {
    // Request interceptor
    this.api.interceptors.request.use(
      config => {
        const token = localStorage.getItem('auth_token')
        if (token) {
          config.headers.Authorization = `Bearer ${token}`
        }
        return config
      },
      error => Promise.reject(error)
    )
    
    // Response interceptor
    this.api.interceptors.response.use(
      response => response.data,
      error => {
        if (error.response?.status === 401) {
          // Handle unauthorized
          useAuthStore().logout()
        }
        return Promise.reject(error)
      }
    )
  }
}

// services/attendance.js
class AttendanceService extends BaseService {
  constructor() {
    super('/api/v1/attendance')
  }
  
  async getAll(params = {}) {
    return this.api.get('/', { params })
  }
  
  async checkIn(data) {
    return this.api.post('/check-in', data)
  }
  
  async getStatistics() {
    return this.api.get('/statistics')
  }
}

export const attendanceService = new AttendanceService()
```

---

## 🔐 Security Integration Implementation

### 1. Two-Factor Authentication (2FA)

#### 1.1 2FA Setup Component
```vue
<template>
  <div class="2fa-setup">
    <div v-if="step === 'qr'" class="qr-step">
      <h3>Scan QR Code</h3>
      <div class="qr-container">
        <img :src="qrCodeUrl" alt="2FA QR Code" />
      </div>
      <p>Secret Key: {{ secretKey }}</p>
    </div>
    
    <div v-if="step === 'verify'" class="verify-step">
      <h3>Verify Setup</h3>
      <input 
        v-model="verificationCode" 
        placeholder="Enter 6-digit code"
        maxlength="6"
      />
      <button @click="verify2FA">Verify</button>
    </div>
    
    <div v-if="step === 'backup'" class="backup-step">
      <h3>Save Backup Codes</h3>
      <div class="backup-codes">
        <code v-for="code in backupCodes" :key="code">
          {{ code }}
        </code>
      </div>
      <button @click="downloadBackupCodes">Download Codes</button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { twoFactorService } from '@/services/twoFactor'

const step = ref('qr')
const qrCodeUrl = ref('')
const secretKey = ref('')
const verificationCode = ref('')
const backupCodes = ref([])

onMounted(async () => {
  const setup = await twoFactorService.initializeSetup()
  qrCodeUrl.value = setup.qr_code_url
  secretKey.value = setup.secret_key
})

const verify2FA = async () => {
  try {
    const result = await twoFactorService.verifySetup(verificationCode.value)
    if (result.success) {
      backupCodes.value = result.backup_codes
      step.value = 'backup'
    }
  } catch (error) {
    // Handle error
  }
}
</script>
```

#### 1.2 2FA Service Implementation
```javascript
// services/twoFactor.js
class TwoFactorService extends BaseService {
  constructor() {
    super('/api/v1/two-factor')
  }
  
  async initializeSetup() {
    return this.api.post('/setup')
  }
  
  async verifySetup(code) {
    return this.api.post('/verify-setup', { code })
  }
  
  async verify(code) {
    return this.api.post('/verify', { code })
  }
  
  async disable() {
    return this.api.delete('/disable')
  }
  
  async regenerateBackupCodes() {
    return this.api.post('/backup-codes/regenerate')
  }
}

export const twoFactorService = new TwoFactorService()
```

### 2. Face Recognition Integration

#### 2.1 Face Recognition Component
```vue
<template>
  <div class="face-recognition">
    <div class="camera-container">
      <video ref="videoRef" autoplay muted></video>
      <canvas ref="canvasRef" class="overlay"></canvas>
    </div>
    
    <div class="controls">
      <button @click="startCamera" :disabled="cameraActive">
        Start Camera
      </button>
      <button @click="capture" :disabled="!cameraActive">
        Capture Face
      </button>
    </div>
    
    <div v-if="recognition.status" class="status">
      <p>Status: {{ recognition.status }}</p>
      <p>Confidence: {{ recognition.confidence }}%</p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import * as faceapi from 'face-api.js'
import { faceRecognitionService } from '@/services/faceRecognition'

const videoRef = ref(null)
const canvasRef = ref(null)
const cameraActive = ref(false)
const recognition = ref({ status: null, confidence: 0 })

onMounted(async () => {
  await loadFaceModels()
})

const loadFaceModels = async () => {
  await faceapi.nets.tinyFaceDetector.loadFromUri('/models')
  await faceapi.nets.faceLandmark68Net.loadFromUri('/models')
  await faceapi.nets.faceRecognitionNet.loadFromUri('/models')
}

const startCamera = async () => {
  try {
    const stream = await navigator.mediaDevices.getUserMedia({ 
      video: { width: 640, height: 480 } 
    })
    videoRef.value.srcObject = stream
    cameraActive.value = true
    startFaceDetection()
  } catch (error) {
    console.error('Camera access error:', error)
  }
}

const startFaceDetection = () => {
  const video = videoRef.value
  const canvas = canvasRef.value
  
  setInterval(async () => {
    if (video && canvas && cameraActive.value) {
      const detections = await faceapi
        .detectAllFaces(video, new faceapi.TinyFaceDetectorOptions())
        .withFaceLandmarks()
        .withFaceDescriptors()
      
      // Clear canvas and draw detections
      const ctx = canvas.getContext('2d')
      ctx.clearRect(0, 0, canvas.width, canvas.height)
      
      if (detections.length > 0) {
        faceapi.draw.drawDetections(canvas, detections)
        faceapi.draw.drawFaceLandmarks(canvas, detections)
      }
    }
  }, 100)
}

const capture = async () => {
  const video = videoRef.value
  const canvas = document.createElement('canvas')
  const ctx = canvas.getContext('2d')
  
  canvas.width = video.videoWidth
  canvas.height = video.videoHeight
  ctx.drawImage(video, 0, 0)
  
  // Convert to base64
  const imageData = canvas.toDataURL('image/jpeg', 0.8)
  
  try {
    const result = await faceRecognitionService.verify(imageData)
    recognition.value = result
  } catch (error) {
    console.error('Face recognition error:', error)
  }
}
</script>
```

---

## 💼 Payroll Integration Implementation

### 1. Payroll Calculation Interface

#### 1.1 Payroll Calculator Component
```vue
<template>
  <div class="payroll-calculator">
    <div class="calculation-form">
      <h3>Payroll Calculation</h3>
      
      <div class="form-section">
        <h4>Period Selection</h4>
        <DateRangePicker 
          v-model="period" 
          @update="fetchAttendanceData"
        />
      </div>
      
      <div class="form-section">
        <h4>Employee Selection</h4>
        <EmployeeMultiSelect 
          v-model="selectedEmployees"
          :employees="employees"
        />
      </div>
      
      <div class="calculation-summary">
        <h4>Calculation Summary</h4>
        <table class="summary-table">
          <thead>
            <tr>
              <th>Employee</th>
              <th>Work Days</th>
              <th>Overtime Hours</th>
              <th>Base Salary</th>
              <th>Overtime Pay</th>
              <th>Deductions</th>
              <th>Net Pay</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="calc in calculations" :key="calc.employee_id">
              <td>{{ calc.employee_name }}</td>
              <td>{{ calc.work_days }}</td>
              <td>{{ calc.overtime_hours }}</td>
              <td>{{ formatCurrency(calc.base_salary) }}</td>
              <td>{{ formatCurrency(calc.overtime_pay) }}</td>
              <td>{{ formatCurrency(calc.deductions) }}</td>
              <td>{{ formatCurrency(calc.net_pay) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      
      <div class="actions">
        <button @click="calculate" :loading="calculating">
          Calculate Payroll
        </button>
        <button @click="generatePayslips" :disabled="!calculations.length">
          Generate Payslips
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { payrollService } from '@/services/payroll'
import { useEmployeeStore } from '@/stores/employee'

const employeeStore = useEmployeeStore()
const period = ref({ start: null, end: null })
const selectedEmployees = ref([])
const calculations = ref([])
const calculating = ref(false)

const employees = computed(() => employeeStore.employees)

const calculate = async () => {
  calculating.value = true
  try {
    const result = await payrollService.bulkCalculate({
      period: period.value,
      employee_ids: selectedEmployees.value
    })
    calculations.value = result.calculations
  } catch (error) {
    console.error('Calculation error:', error)
  } finally {
    calculating.value = false
  }
}

const generatePayslips = async () => {
  try {
    const result = await payrollService.generatePayslips(
      calculations.value.map(c => c.id)
    )
    // Handle PDF download
    window.open(result.download_url, '_blank')
  } catch (error) {
    console.error('Payslip generation error:', error)
  }
}
</script>
```

#### 1.2 Payroll Service Implementation
```javascript
// services/payroll.js
class PayrollService extends BaseService {
  constructor() {
    super('/api/v1/payroll')
  }
  
  async bulkCalculate(data) {
    return this.api.post('/bulk-calculate', data)
  }
  
  async generatePayslips(payrollIds) {
    return this.api.post('/generate-payslips', { payroll_ids: payrollIds })
  }
  
  async getApprovalWorkflow(payrollId) {
    return this.api.get(`/${payrollId}/approval-workflow`)
  }
  
  async approve(payrollId, data) {
    return this.api.post(`/${payrollId}/approve`, data)
  }
  
  async reject(payrollId, data) {
    return this.api.post(`/${payrollId}/reject`, data)
  }
}

export const payrollService = new PayrollService()
```

---

## 📊 Real-time Features Implementation

### 1. WebSocket Integration

#### 1.1 WebSocket Service
```javascript
// services/websocket.js
class WebSocketService {
  constructor() {
    this.ws = null
    this.listeners = new Map()
    this.reconnectAttempts = 0
    this.maxReconnectAttempts = 5
  }
  
  connect() {
    const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:'
    const wsUrl = `${protocol}//${window.location.host}/ws`
    
    this.ws = new WebSocket(wsUrl)
    
    this.ws.onopen = () => {
      console.log('WebSocket connected')
      this.reconnectAttempts = 0
      this.authenticate()
    }
    
    this.ws.onmessage = (event) => {
      const data = JSON.parse(event.data)
      this.handleMessage(data)
    }
    
    this.ws.onclose = () => {
      console.log('WebSocket disconnected')
      this.reconnect()
    }
    
    this.ws.onerror = (error) => {
      console.error('WebSocket error:', error)
    }
  }
  
  authenticate() {
    const token = localStorage.getItem('auth_token')
    this.send('auth', { token })
  }
  
  subscribe(channel, callback) {
    if (!this.listeners.has(channel)) {
      this.listeners.set(channel, new Set())
    }
    this.listeners.get(channel).add(callback)
    
    // Subscribe to channel
    this.send('subscribe', { channel })
  }
  
  unsubscribe(channel, callback) {
    if (this.listeners.has(channel)) {
      this.listeners.get(channel).delete(callback)
    }
  }
  
  send(type, data) {
    if (this.ws && this.ws.readyState === WebSocket.OPEN) {
      this.ws.send(JSON.stringify({ type, data }))
    }
  }
  
  handleMessage(message) {
    const { channel, data } = message
    if (this.listeners.has(channel)) {
      this.listeners.get(channel).forEach(callback => {
        callback(data)
      })
    }
  }
  
  reconnect() {
    if (this.reconnectAttempts < this.maxReconnectAttempts) {
      this.reconnectAttempts++
      setTimeout(() => {
        this.connect()
      }, 1000 * this.reconnectAttempts)
    }
  }
}

export const websocketService = new WebSocketService()
```

#### 1.2 Real-time Dashboard Component
```vue
<template>
  <div class="real-time-dashboard">
    <div class="stats-grid">
      <StatsCard 
        v-for="stat in stats" 
        :key="stat.id"
        :title="stat.title"
        :value="stat.value"
        :trend="stat.trend"
        :icon="stat.icon"
      />
    </div>
    
    <div class="live-activity">
      <h3>Live Activity</h3>
      <div class="activity-feed">
        <div 
          v-for="activity in recentActivities" 
          :key="activity.id"
          class="activity-item"
          :class="activity.type"
        >
          <div class="activity-icon">
            <Icon :name="activity.icon" />
          </div>
          <div class="activity-content">
            <p>{{ activity.message }}</p>
            <span class="timestamp">{{ formatTime(activity.timestamp) }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { websocketService } from '@/services/websocket'

const stats = ref([])
const recentActivities = ref([])

onMounted(() => {
  // Subscribe to real-time updates
  websocketService.subscribe('dashboard.stats', updateStats)
  websocketService.subscribe('dashboard.activity', addActivity)
  
  // Initial data load
  loadInitialData()
})

onBeforeUnmount(() => {
  websocketService.unsubscribe('dashboard.stats', updateStats)
  websocketService.unsubscribe('dashboard.activity', addActivity)
})

const updateStats = (newStats) => {
  stats.value = newStats
}

const addActivity = (activity) => {
  recentActivities.value.unshift(activity)
  // Keep only last 50 activities
  if (recentActivities.value.length > 50) {
    recentActivities.value = recentActivities.value.slice(0, 50)
  }
}
</script>
```

---

## 🧪 Testing Strategy Implementation

### 1. Component Testing

#### 1.1 Unit Test Example
```javascript
// tests/components/FaceRecognition.test.js
import { mount } from '@vue/test-utils'
import { vi } from 'vitest'
import FaceRecognition from '@/components/FaceRecognition.vue'

describe('FaceRecognition', () => {
  let wrapper
  
  beforeEach(() => {
    // Mock navigator.mediaDevices
    global.navigator.mediaDevices = {
      getUserMedia: vi.fn().mockResolvedValue({
        getTracks: () => [{ stop: vi.fn() }]
      })
    }
    
    wrapper = mount(FaceRecognition)
  })
  
  afterEach(() => {
    wrapper.unmount()
  })
  
  it('should request camera permission on start', async () => {
    await wrapper.find('[data-test="start-camera"]').trigger('click')
    
    expect(navigator.mediaDevices.getUserMedia).toHaveBeenCalledWith({
      video: { width: 640, height: 480 }
    })
  })
  
  it('should show error when camera access fails', async () => {
    navigator.mediaDevices.getUserMedia.mockRejectedValue(new Error('Permission denied'))
    
    await wrapper.find('[data-test="start-camera"]').trigger('click')
    await wrapper.vm.$nextTick()
    
    expect(wrapper.find('[data-test="error-message"]').text()).toContain('Camera access error')
  })
})
```

### 2. Integration Testing

#### 2.1 API Integration Test
```javascript
// tests/integration/attendance.test.js
import { attendanceService } from '@/services/attendance'
import { setupTestServer } from '@/tests/utils/server'

describe('Attendance Integration', () => {
  let server
  
  beforeAll(() => {
    server = setupTestServer()
  })
  
  afterAll(() => {
    server.close()
  })
  
  it('should fetch attendance data correctly', async () => {
    const mockAttendances = [
      { id: 1, employee_id: 1, check_in: '09:00', status: 'present' }
    ]
    
    server.use(
      http.get('/api/v1/attendance', () => {
        return HttpResponse.json({ data: mockAttendances })
      })
    )
    
    const result = await attendanceService.getAll()
    expect(result.data).toEqual(mockAttendances)
  })
})
```

---

## 📈 Performance Optimization

### 1. Code Splitting
```javascript
// router/index.js
const routes = [
  {
    path: '/dashboard',
    component: () => import('@/pages/Dashboard.vue')
  },
  {
    path: '/attendance',
    component: () => import('@/pages/Attendance.vue')
  },
  {
    path: '/face-recognition',
    component: () => import('@/pages/FaceRecognition.vue')
  }
]
```

### 2. Virtual Scrolling for Large Lists
```vue
<template>
  <VirtualList
    :items="employees"
    :item-height="60"
    :container-height="400"
    v-slot="{ item }"
  >
    <EmployeeCard :employee="item" />
  </VirtualList>
</template>
```

### 3. Caching Strategy
```javascript
// services/cache.js
class CacheService {
  constructor() {
    this.cache = new Map()
    this.ttl = new Map()
  }
  
  set(key, value, duration = 300000) { // 5 minutes default
    this.cache.set(key, value)
    this.ttl.set(key, Date.now() + duration)
  }
  
  get(key) {
    if (this.ttl.get(key) < Date.now()) {
      this.cache.delete(key)
      this.ttl.delete(key)
      return null
    }
    return this.cache.get(key)
  }
}

export const cacheService = new CacheService()
```

---

## 🔒 Security Best Practices

### 1. Input Validation
```javascript
// utils/validation.js
export const validateInput = {
  email: (email) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email),
  phone: (phone) => /^\+?[\d\s-()]+$/.test(phone),
  sanitizeHtml: (html) => DOMPurify.sanitize(html)
}
```

### 2. CSRF Protection
```javascript
// services/base.js
axios.defaults.headers.common['X-CSRF-TOKEN'] = 
  document.querySelector('meta[name="csrf-token"]').getAttribute('content')
```

### 3. Content Security Policy
```html
<!-- In blade template -->
<meta http-equiv="Content-Security-Policy" 
      content="default-src 'self'; 
               script-src 'self' 'unsafe-inline' 'unsafe-eval'; 
               style-src 'self' 'unsafe-inline';">
```

---

Panduan implementasi teknis ini memberikan struktur yang solid untuk mengintegrasikan semua fitur backend ke frontend dengan arsitektur yang scalable dan maintainable.