/**
 * Face Recognition API Integration Service
 * Handles all API communications for face recognition features
 */

class FaceRecognitionAPIService {
  constructor() {
    this.baseURL = '/api/face-recognition'
    this.defaultHeaders = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-Requested-With': 'XMLHttpRequest'
    }
    
    // Add CSRF token if available
    const token = document.querySelector('meta[name="csrf-token"]')
    if (token) {
      this.defaultHeaders['X-CSRF-TOKEN'] = token.getAttribute('content')
    }
    
    this.requestInterceptors = []
    this.responseInterceptors = []
  }

  /**
   * Add request interceptor
   */
  addRequestInterceptor(interceptor) {
    this.requestInterceptors.push(interceptor)
  }

  /**
   * Add response interceptor
   */
  addResponseInterceptor(interceptor) {
    this.responseInterceptors.push(interceptor)
  }

  /**
   * Make HTTP request with interceptors
   */
  async request(url, options = {}) {
    // Prepare request
    const config = {
      method: 'GET',
      headers: { ...this.defaultHeaders },
      ...options
    }

    // Apply request interceptors
    for (const interceptor of this.requestInterceptors) {
      if (interceptor.request) {
        await interceptor.request(config)
      }
    }

    try {
      const response = await fetch(url, config)
      
      // Apply response interceptors
      for (const interceptor of this.responseInterceptors) {
        if (interceptor.response) {
          await interceptor.response(response)
        }
      }

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }

      const data = await response.json()
      return data
    } catch (error) {
      // Apply error interceptors
      for (const interceptor of this.responseInterceptors) {
        if (interceptor.error) {
          await interceptor.error(error)
        }
      }
      throw error
    }
  }

  /**
   * Employee Face Management
   */
  async enrollEmployee(employeeData) {
    return this.request(`${this.baseURL}/employees/enroll`, {
      method: 'POST',
      body: JSON.stringify(employeeData)
    })
  }

  async getEmployeeTemplate(employeeId) {
    return this.request(`${this.baseURL}/employees/${employeeId}/template`)
  }

  async updateEmployeeTemplate(employeeId, templateData) {
    return this.request(`${this.baseURL}/employees/${employeeId}/template`, {
      method: 'PUT',
      body: JSON.stringify(templateData)
    })
  }

  async deleteEmployeeTemplate(employeeId) {
    return this.request(`${this.baseURL}/employees/${employeeId}/template`, {
      method: 'DELETE'
    })
  }

  async getEmployeeTemplates(filters = {}) {
    const params = new URLSearchParams(filters)
    return this.request(`${this.baseURL}/employees/templates?${params}`)
  }

  /**
   * Face Detection & Recognition
   */
  async detectFace(imageData, options = {}) {
    const formData = new FormData()
    formData.append('image', imageData)
    
    Object.keys(options).forEach(key => {
      formData.append(key, options[key])
    })

    return this.request(`${this.baseURL}/detect`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': this.defaultHeaders['X-CSRF-TOKEN']
      },
      body: formData
    })
  }

  async recognizeFace(imageData, options = {}) {
    const formData = new FormData()
    formData.append('image', imageData)
    
    Object.keys(options).forEach(key => {
      formData.append(key, options[key])
    })

    return this.request(`${this.baseURL}/recognize`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': this.defaultHeaders['X-CSRF-TOKEN']
      },
      body: formData
    })
  }

  async verifyFace(imageData, employeeId, options = {}) {
    const formData = new FormData()
    formData.append('image', imageData)
    formData.append('employee_id', employeeId)
    
    Object.keys(options).forEach(key => {
      formData.append(key, options[key])
    })

    return this.request(`${this.baseURL}/verify`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': this.defaultHeaders['X-CSRF-TOKEN']
      },
      body: formData
    })
  }

  /**
   * Liveness Detection
   */
  async startLivenessSession(options = {}) {
    return this.request(`${this.baseURL}/liveness/start`, {
      method: 'POST',
      body: JSON.stringify(options)
    })
  }

  async submitLivenessGesture(sessionId, gestureData) {
    const formData = new FormData()
    formData.append('session_id', sessionId)
    formData.append('gesture_type', gestureData.type)
    formData.append('image', gestureData.image)
    formData.append('confidence', gestureData.confidence)

    return this.request(`${this.baseURL}/liveness/gesture`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': this.defaultHeaders['X-CSRF-TOKEN']
      },
      body: formData
    })
  }

  async completeLivenessSession(sessionId) {
    return this.request(`${this.baseURL}/liveness/complete`, {
      method: 'POST',
      body: JSON.stringify({ session_id: sessionId })
    })
  }

  /**
   * Attendance Integration
   */
  async processAttendance(attendanceData) {
    return this.request(`${this.baseURL}/attendance/process`, {
      method: 'POST',
      body: JSON.stringify(attendanceData)
    })
  }

  async getAttendanceHistory(employeeId, filters = {}) {
    const params = new URLSearchParams({ employee_id: employeeId, ...filters })
    return this.request(`${this.baseURL}/attendance/history?${params}`)
  }

  /**
   * Analytics & Reporting
   */
  async getAnalytics(timeRange = '7d') {
    return this.request(`${this.baseURL}/analytics?range=${timeRange}`)
  }

  async getPerformanceMetrics(timeRange = '24h') {
    return this.request(`${this.baseURL}/performance?range=${timeRange}`)
  }

  async getAuditLogs(filters = {}) {
    const params = new URLSearchParams(filters)
    return this.request(`${this.baseURL}/audit-logs?${params}`)
  }

  async generateReport(reportType, options = {}) {
    return this.request(`${this.baseURL}/reports/${reportType}`, {
      method: 'POST',
      body: JSON.stringify(options)
    })
  }

  async exportReport(reportId, format = 'pdf') {
    const response = await fetch(`${this.baseURL}/reports/${reportId}/export?format=${format}`, {
      method: 'GET',
      headers: {
        'Accept': 'application/octet-stream',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': this.defaultHeaders['X-CSRF-TOKEN']
      }
    })

    if (!response.ok) {
      throw new Error(`Export failed: ${response.status}`)
    }

    return response.blob()
  }

  /**
   * System Configuration
   */
  async getSettings() {
    return this.request(`${this.baseURL}/settings`)
  }

  async updateSettings(settings) {
    return this.request(`${this.baseURL}/settings`, {
      method: 'PUT',
      body: JSON.stringify(settings)
    })
  }

  async getSystemStatus() {
    return this.request(`${this.baseURL}/system/status`)
  }

  async runDiagnostics() {
    return this.request(`${this.baseURL}/system/diagnostics`, {
      method: 'POST'
    })
  }

  async clearCache() {
    return this.request(`${this.baseURL}/system/cache`, {
      method: 'DELETE'
    })
  }

  /**
   * Backup & Restore
   */
  async createBackup(options = {}) {
    return this.request(`${this.baseURL}/backup/create`, {
      method: 'POST',
      body: JSON.stringify(options)
    })
  }

  async getBackups() {
    return this.request(`${this.baseURL}/backup/list`)
  }

  async downloadBackup(backupId) {
    const response = await fetch(`${this.baseURL}/backup/${backupId}/download`, {
      method: 'GET',
      headers: {
        'Accept': 'application/octet-stream',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': this.defaultHeaders['X-CSRF-TOKEN']
      }
    })

    if (!response.ok) {
      throw new Error(`Download failed: ${response.status}`)
    }

    return response.blob()
  }

  async restoreBackup(backupId) {
    return this.request(`${this.baseURL}/backup/${backupId}/restore`, {
      method: 'POST'
    })
  }

  async deleteBackup(backupId) {
    return this.request(`${this.baseURL}/backup/${backupId}`, {
      method: 'DELETE'
    })
  }

  /**
   * Utility Methods
   */
  async uploadFaceImage(imageBlob, metadata = {}) {
    const formData = new FormData()
    formData.append('image', imageBlob)
    
    Object.keys(metadata).forEach(key => {
      formData.append(key, metadata[key])
    })

    return this.request(`${this.baseURL}/images/upload`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': this.defaultHeaders['X-CSRF-TOKEN']
      },
      body: formData
    })
  }

  async getFaceImage(imageId) {
    const response = await fetch(`${this.baseURL}/images/${imageId}`, {
      method: 'GET',
      headers: {
        'Accept': 'image/*',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': this.defaultHeaders['X-CSRF-TOKEN']
      }
    })

    if (!response.ok) {
      throw new Error(`Image fetch failed: ${response.status}`)
    }

    return response.blob()
  }

  async deleteFaceImage(imageId) {
    return this.request(`${this.baseURL}/images/${imageId}`, {
      method: 'DELETE'
    })
  }

  /**
   * Batch Operations
   */
  async batchUpdateTemplates(templateIds, updates) {
    return this.request(`${this.baseURL}/templates/batch-update`, {
      method: 'POST',
      body: JSON.stringify({
        template_ids: templateIds,
        updates: updates
      })
    })
  }

  async batchDeleteTemplates(templateIds) {
    return this.request(`${this.baseURL}/templates/batch-delete`, {
      method: 'POST',
      body: JSON.stringify({
        template_ids: templateIds
      })
    })
  }

  async batchExportTemplates(templateIds, format = 'json') {
    const response = await fetch(`${this.baseURL}/templates/batch-export`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/octet-stream',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': this.defaultHeaders['X-CSRF-TOKEN']
      },
      body: JSON.stringify({
        template_ids: templateIds,
        format: format
      })
    })

    if (!response.ok) {
      throw new Error(`Batch export failed: ${response.status}`)
    }

    return response.blob()
  }

  /**
   * Real-time Updates
   */
  async subscribeToUpdates(callback) {
    if (!window.EventSource) {
      throw new Error('EventSource not supported')
    }

    const eventSource = new EventSource(`${this.baseURL}/stream/updates`)
    
    eventSource.onmessage = (event) => {
      try {
        const data = JSON.parse(event.data)
        callback(data)
      } catch (error) {
        console.error('Error parsing SSE data:', error)
      }
    }

    eventSource.onerror = (error) => {
      console.error('SSE error:', error)
    }

    return eventSource
  }

  /**
   * Health Check
   */
  async healthCheck() {
    return this.request(`${this.baseURL}/health`)
  }

  /**
   * Error Handling Utilities
   */
  isNetworkError(error) {
    return error.name === 'TypeError' && error.message.includes('fetch')
  }

  isAuthError(error) {
    return error.message.includes('401') || error.message.includes('403')
  }

  isServerError(error) {
    return error.message.includes('5') // 5xx errors
  }

  /**
   * Retry Logic
   */
  async withRetry(operation, maxRetries = 3, delay = 1000) {
    for (let attempt = 1; attempt <= maxRetries; attempt++) {
      try {
        return await operation()
      } catch (error) {
        if (attempt === maxRetries) {
          throw error
        }
        
        // Only retry on network errors or server errors
        if (this.isNetworkError(error) || this.isServerError(error)) {
          await new Promise(resolve => setTimeout(resolve, delay * attempt))
          continue
        }
        
        throw error
      }
    }
  }

  /**
   * File Upload with Progress
   */
  async uploadWithProgress(file, metadata = {}, onProgress = null) {
    return new Promise((resolve, reject) => {
      const xhr = new XMLHttpRequest()
      const formData = new FormData()
      
      formData.append('file', file)
      Object.keys(metadata).forEach(key => {
        formData.append(key, metadata[key])
      })

      xhr.upload.addEventListener('progress', (event) => {
        if (event.lengthComputable && onProgress) {
          const percentComplete = (event.loaded / event.total) * 100
          onProgress(percentComplete)
        }
      })

      xhr.addEventListener('load', () => {
        if (xhr.status >= 200 && xhr.status < 300) {
          try {
            const response = JSON.parse(xhr.responseText)
            resolve(response)
          } catch (error) {
            reject(new Error('Invalid JSON response'))
          }
        } else {
          reject(new Error(`Upload failed: ${xhr.status}`))
        }
      })

      xhr.addEventListener('error', () => {
        reject(new Error('Upload failed'))
      })

      xhr.addEventListener('abort', () => {
        reject(new Error('Upload aborted'))
      })

      xhr.open('POST', `${this.baseURL}/upload`)
      xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest')
      xhr.setRequestHeader('X-CSRF-TOKEN', this.defaultHeaders['X-CSRF-TOKEN'])
      xhr.send(formData)
    })
  }

  /**
   * Connection Management
   */
  async testConnection() {
    const startTime = Date.now()
    try {
      await this.healthCheck()
      const endTime = Date.now()
      return {
        success: true,
        latency: endTime - startTime,
        timestamp: new Date().toISOString()
      }
    } catch (error) {
      return {
        success: false,
        error: error.message,
        timestamp: new Date().toISOString()
      }
    }
  }

  /**
   * Data Validation
   */
  validateImageData(imageData) {
    if (!imageData) {
      throw new Error('Image data is required')
    }

    if (imageData instanceof Blob) {
      if (!imageData.type.startsWith('image/')) {
        throw new Error('Invalid image format')
      }
      
      if (imageData.size > 10 * 1024 * 1024) { // 10MB limit
        throw new Error('Image size too large (max 10MB)')
      }
    }

    return true
  }

  validateEmployeeData(employeeData) {
    const required = ['name', 'employee_id']
    const missing = required.filter(field => !employeeData[field])
    
    if (missing.length > 0) {
      throw new Error(`Missing required fields: ${missing.join(', ')}`)
    }

    return true
  }

  /**
   * Logging & Monitoring
   */
  logAPICall(endpoint, method, duration, success) {
    const logData = {
      endpoint,
      method,
      duration,
      success,
      timestamp: new Date().toISOString(),
      user_agent: navigator.userAgent
    }

    // Send to monitoring service if available
    if (window.monitoringService) {
      window.monitoringService.logAPICall(logData)
    }

    console.log(`API Call: ${method} ${endpoint} - ${duration}ms - ${success ? 'Success' : 'Failed'}`)
  }
}

// Export singleton instance
const faceRecognitionAPI = new FaceRecognitionAPIService()

// Add default interceptors
faceRecognitionAPI.addRequestInterceptor({
  request: async (config) => {
    // Add timing
    config.startTime = Date.now()
    
    // Add authentication token if available
    const token = localStorage.getItem('auth_token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
  }
})

faceRecognitionAPI.addResponseInterceptor({
  response: async (response) => {
    // Log successful API calls
    const duration = Date.now() - response.config?.startTime || 0
    faceRecognitionAPI.logAPICall(response.url, response.method || 'GET', duration, true)
  },
  error: async (error) => {
    // Log failed API calls
    const duration = Date.now() - error.config?.startTime || 0
    faceRecognitionAPI.logAPICall(error.config?.url || 'unknown', error.config?.method || 'GET', duration, false)
    
    // Handle specific error types
    if (faceRecognitionAPI.isAuthError(error)) {
      // Redirect to login or refresh token
      window.location.href = '/login'
    }
  }
})

export default faceRecognitionAPI
export { FaceRecognitionAPIService }