/**
 * Two-Factor Authentication Service
 * Handles all 2FA related API calls and operations
 */

import axios from 'axios'

class TwoFactorService {
  constructor() {
    this.baseURL = '/api/v1/two-factor'
    this.setupAxiosInterceptors()
  }

  setupAxiosInterceptors() {
    // Request interceptor to add CSRF token
    axios.interceptors.request.use(
      config => {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        if (token) {
          config.headers['X-CSRF-TOKEN'] = token
        }
        return config
      },
      error => Promise.reject(error)
    )

    // Response interceptor for error handling
    axios.interceptors.response.use(
      response => response,
      error => {
        if (error.response?.status === 401) {
          // Handle unauthorized access
          window.location.href = '/login'
        }
        return Promise.reject(error)
      }
    )
  }

  /**
   * Initialize 2FA setup - Generate QR code and secret key
   * @returns {Promise<Object>} Setup data including QR code and secret
   */
  async initializeSetup() {
    try {
      const response = await axios.post(`${this.baseURL}/setup/initialize`)
      return response.data
    } catch (error) {
      throw this.handleError(error)
    }
  }

  /**
   * Verify setup code and enable 2FA
   * @param {string} code - 6-digit verification code
   * @returns {Promise<Object>} Success response with backup codes
   */
  async verifySetup(code) {
    try {
      const response = await axios.post(`${this.baseURL}/setup/verify`, {
        code: code.toString().padStart(6, '0')
      })
      return response.data
    } catch (error) {
      throw this.handleError(error)
    }
  }

  /**
   * Verify 2FA code during login
   * @param {string} code - 6-digit code or recovery code
   * @param {string} type - 'totp', 'recovery', or 'sms'
   * @returns {Promise<Object>} Verification result
   */
  async verify(code, type = 'totp') {
    try {
      const response = await axios.post(`${this.baseURL}/verify`, {
        code: code.toString(),
        type
      })
      return response.data
    } catch (error) {
      throw this.handleError(error)
    }
  }

  /**
   * Disable 2FA for current user
   * @param {string} password - Current user password
   * @param {string} confirmationCode - Optional 2FA confirmation code
   * @returns {Promise<Object>} Disable result
   */
  async disable(password, confirmationCode = null) {
    try {
      const data = { password }
      if (confirmationCode) {
        data.confirmation_code = confirmationCode
      }

      const response = await axios.delete(`${this.baseURL}/disable`, { data })
      return response.data
    } catch (error) {
      throw this.handleError(error)
    }
  }

  /**
   * Regenerate backup recovery codes
   * @returns {Promise<Object>} New backup codes
   */
  async regenerateBackupCodes() {
    try {
      const response = await axios.post(`${this.baseURL}/recovery-codes/regenerate`)
      return response.data
    } catch (error) {
      throw this.handleError(error)
    }
  }

  /**
   * Get current 2FA status for user
   * @returns {Promise<Object>} 2FA status information
   */
  async getStatus() {
    try {
      const response = await axios.get(`${this.baseURL}/status`)
      return response.data
    } catch (error) {
      throw this.handleError(error)
    }
  }

  /**
   * Send SMS verification code
   * @returns {Promise<Object>} SMS send result
   */
  async sendSMS() {
    try {
      const response = await axios.post(`${this.baseURL}/sms/send`)
      return response.data
    } catch (error) {
      throw this.handleError(error)
    }
  }

  /**
   * Get QR code for existing 2FA setup
   * @returns {Promise<Object>} QR code data
   */
  async getQRCode() {
    try {
      const response = await axios.get(`${this.baseURL}/qr-code`)
      return response.data
    } catch (error) {
      throw this.handleError(error)
    }
  }

  /**
   * Validate 2FA code format
   * @param {string} code - Code to validate
   * @param {string} type - Type of code ('totp', 'recovery', 'sms')
   * @returns {boolean} Whether code format is valid
   */
  validateCodeFormat(code, type = 'totp') {
    if (!code || typeof code !== 'string') {
      return false
    }

    switch (type) {
      case 'totp':
      case 'sms':
        // 6-digit numeric code
        return /^\d{6}$/.test(code)
      case 'recovery':
        // 8-character alphanumeric recovery code
        return /^[A-Z0-9]{8}$/.test(code.toUpperCase())
      default:
        return false
    }
  }

  /**
   * Format code for display
   * @param {string} code - Code to format
   * @param {string} type - Type of code
   * @returns {string} Formatted code
   */
  formatCode(code, type = 'totp') {
    if (!code) return ''

    switch (type) {
      case 'totp':
      case 'sms':
        // Format as XXX-XXX
        return code.replace(/(\d{3})(\d{3})/, '$1-$2')
      case 'recovery':
        // Format as XXXX-XXXX
        return code.toUpperCase().replace(/(.{4})(.{4})/, '$1-$2')
      default:
        return code
    }
  }

  /**
   * Generate device fingerprint for security
   * @returns {string} Device fingerprint
   */
  generateDeviceFingerprint() {
    const canvas = document.createElement('canvas')
    const ctx = canvas.getContext('2d')
    ctx.textBaseline = 'top'
    ctx.font = '14px Arial'
    ctx.fillText('Device fingerprint', 2, 2)
    
    const fingerprint = [
      navigator.userAgent,
      navigator.language,
      screen.width + 'x' + screen.height,
      new Date().getTimezoneOffset(),
      canvas.toDataURL()
    ].join('|')
    
    return btoa(fingerprint).slice(0, 32)
  }

  /**
   * Check if 2FA is supported by browser
   * @returns {boolean} Whether 2FA is supported
   */
  isSupported() {
    return !!(navigator.clipboard && window.crypto && window.crypto.subtle)
  }

  /**
   * Get 2FA setup instructions for different authenticator apps
   * @returns {Array} Array of app instructions
   */
  getAppInstructions() {
    return [
      {
        name: 'Google Authenticator',
        platforms: ['iOS', 'Android'],
        steps: [
          'Download Google Authenticator from your app store',
          'Open the app and tap the "+" button',
          'Select "Scan a QR code"',
          'Point your camera at the QR code above',
          'Enter the 6-digit code when prompted'
        ]
      },
      {
        name: 'Authy',
        platforms: ['iOS', 'Android', 'Desktop'],
        steps: [
          'Download Authy from your app store',
          'Create an account and verify your phone number',
          'Tap "Add Account" and select "Scan QR Code"',
          'Scan the QR code with your camera',
          'Enter the generated code to verify'
        ]
      },
      {
        name: 'Microsoft Authenticator',
        platforms: ['iOS', 'Android'],
        steps: [
          'Download Microsoft Authenticator',
          'Open the app and tap "Add account"',
          'Select "Other account (Google, Facebook, etc.)"',
          'Scan the QR code',
          'Use the generated code to verify setup'
        ]
      }
    ]
  }

  /**
   * Handle API errors consistently
   * @param {Error} error - Axios error object
   * @returns {Error} Formatted error
   */
  handleError(error) {
    if (error.response?.data) {
      const { data } = error.response
      const message = data.message || data.error || 'An unknown error occurred'
      const formattedError = new Error(message)
      formattedError.status = error.response.status
      formattedError.errors = data.errors || {}
      return formattedError
    }
    
    if (error.request) {
      return new Error('Network error: Please check your internet connection')
    }
    
    return new Error(error.message || 'An unexpected error occurred')
  }

  /**
   * Storage helpers for temporary data
   */
  storage = {
    /**
     * Store setup data temporarily
     * @param {Object} data - Setup data to store
     */
    setSetupData(data) {
      sessionStorage.setItem('2fa_setup_data', JSON.stringify(data))
    },

    /**
     * Get stored setup data
     * @returns {Object|null} Setup data or null
     */
    getSetupData() {
      const data = sessionStorage.getItem('2fa_setup_data')
      return data ? JSON.parse(data) : null
    },

    /**
     * Clear setup data
     */
    clearSetupData() {
      sessionStorage.removeItem('2fa_setup_data')
    },

    /**
     * Store verification attempts
     * @param {number} attempts - Number of attempts
     */
    setVerificationAttempts(attempts) {
      sessionStorage.setItem('2fa_verify_attempts', attempts.toString())
    },

    /**
     * Get verification attempts
     * @returns {number} Number of attempts
     */
    getVerificationAttempts() {
      return parseInt(sessionStorage.getItem('2fa_verify_attempts') || '0', 10)
    },

    /**
     * Clear verification attempts
     */
    clearVerificationAttempts() {
      sessionStorage.removeItem('2fa_verify_attempts')
    }
  }

  /**
   * Utility methods
   */
  utils = {
    /**
     * Copy text to clipboard
     * @param {string} text - Text to copy
     * @returns {Promise<boolean>} Success status
     */
    async copyToClipboard(text) {
      try {
        await navigator.clipboard.writeText(text)
        return true
      } catch (error) {
        // Fallback for older browsers
        const textArea = document.createElement('textarea')
        textArea.value = text
        document.body.appendChild(textArea)
        textArea.select()
        document.execCommand('copy')
        document.body.removeChild(textArea)
        return true
      }
    },

    /**
     * Download text as file
     * @param {string} content - File content
     * @param {string} filename - File name
     * @param {string} mimeType - MIME type
     */
    downloadAsFile(content, filename, mimeType = 'text/plain') {
      const blob = new Blob([content], { type: mimeType })
      const url = window.URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = url
      link.download = filename
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
      window.URL.revokeObjectURL(url)
    },

    /**
     * Format backup codes for display
     * @param {Array} codes - Array of backup codes
     * @returns {string} Formatted codes text
     */
    formatBackupCodes(codes) {
      const timestamp = new Date().toLocaleString()
      return `Two-Factor Authentication Backup Codes
Generated: ${timestamp}

${codes.join('\n')}

IMPORTANT SECURITY NOTICE:
- Each code can only be used once
- Store these codes in a safe, secure location
- Do not share these codes with anyone
- You can regenerate new codes from your security settings

If you suspect these codes have been compromised, 
regenerate them immediately from your account settings.`
    },

    /**
     * Generate secure random string
     * @param {number} length - String length
     * @returns {string} Random string
     */
    generateRandomString(length = 32) {
      const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789'
      let result = ''
      const randomArray = new Uint8Array(length)
      window.crypto.getRandomValues(randomArray)
      
      for (let i = 0; i < length; i++) {
        result += chars[randomArray[i] % chars.length]
      }
      
      return result
    }
  }
}

// Create and export singleton instance
export const twoFactorService = new TwoFactorService()
export default twoFactorService