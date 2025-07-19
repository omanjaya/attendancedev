import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { User, LoginCredentials, AuthState } from '@/types/auth'
import { useAuthErrorHandler } from '@/composables/useAuthErrorHandler'

export const useAuthStore = defineStore('auth', () => {
  // Error handler
  const authErrorHandler = useAuthErrorHandler()

  // State
  const user = ref<User | null>(null)
  const token = ref<string | null>(localStorage.getItem('auth_token'))
  const isLoading = ref<boolean>(false)
  const error = ref<string | null>(null)
  const lastActivity = ref<Date>(new Date())

  // Device and session info
  const currentDevice = ref<any>(null)
  const sessionTimeout = ref<number>(30 * 60 * 1000) // 30 minutes
  const sessionTimer = ref<NodeJS.Timeout | null>(null)

  // Getters (computed)
  const isAuthenticated = computed(() => !!user.value && !!token.value)
  const isAdmin = computed(() => user.value?.roles?.includes('admin') || false)
  const isSuperAdmin = computed(() => user.value?.roles?.includes('super-admin') || false)
  const isEmployee = computed(() => user.value?.roles?.includes('employee') || false)
  const isManager = computed(() => user.value?.roles?.includes('manager') || false)

  const hasPermission = computed(() => (permission: string) => {
    return user.value?.permissions?.includes(permission) || false
  })

  const hasRole = computed(() => (role: string) => {
    return user.value?.roles?.includes(role) || false
  })

  const fullName = computed(() => {
    if (!user.value) {return ''}
    return `${user.value.first_name} ${user.value.last_name}`.trim()
  })

  // Actions
  const login = async (credentials: LoginCredentials): Promise<void> => {
    isLoading.value = true
    error.value = null
    authErrorHandler.clearError()

    try {
      const response = await fetch('/api/auth/login', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': getCSRFToken(),
        },
        body: JSON.stringify(credentials),
      })

      if (!response.ok) {
        const errorData = await response.json()
        const errorObj = {
          response: {
            status: response.status,
            data: errorData,
          },
        }
        throw errorObj
      }

      const data = await response.json()

      // Store user and token
      user.value = data.user
      token.value = data.token
      localStorage.setItem('auth_token', data.token)

      // Update last activity and start session timer
      updateActivity()
      startSessionTimer()

      // Store device info if provided
      if (data.device) {
        currentDevice.value = data.device
      }

      // Reset retry count on successful login
      authErrorHandler.resetRetryCount()
    } catch (err) {
      // Handle error with enhanced error handler
      const authError = await authErrorHandler.handleLoginError(err, credentials.email)
      error.value = authError.userMessage
      throw authError
    } finally {
      isLoading.value = false
    }
  }

  const logout = async (): Promise<void> => {
    isLoading.value = true

    try {
      // Call logout API
      if (token.value) {
        await fetch('/api/auth/logout', {
          method: 'POST',
          headers: {
            Authorization: `Bearer ${token.value}`,
            'X-CSRF-TOKEN': getCSRFToken(),
          },
        })
      }
    } catch (err) {
      console.error('Logout API call failed:', err)
    } finally {
      // Clear state regardless of API call result
      clearAuthState()
      isLoading.value = false
    }
  }

  const fetchUser = async (): Promise<void> => {
    if (!token.value) {return}

    isLoading.value = true
    error.value = null

    try {
      const response = await fetch('/api/auth/me', {
        headers: {
          Authorization: `Bearer ${token.value}`,
          'X-CSRF-TOKEN': getCSRFToken(),
        },
      })

      if (!response.ok) {
        if (response.status === 401) {
          // Handle session expiration
          await authErrorHandler.handleAuthError(
            { code: 'session_expired', message: 'Session expired' },
            { action: 'fetch_user', timestamp: new Date() }
          )
          clearAuthState()
          return
        }

        const errorData = await response.json()
        const errorObj = {
          response: {
            status: response.status,
            data: errorData,
          },
        }
        throw errorObj
      }

      const data = await response.json()
      user.value = data.user
      updateActivity()
    } catch (err) {
      // Handle error with enhanced error handler
      const authError = await authErrorHandler.handleAuthError(err, {
        action: 'fetch_user',
        userId: user.value?.id,
        timestamp: new Date(),
      })
      error.value = authError.userMessage
      clearAuthState()
    } finally {
      isLoading.value = false
    }
  }

  const updateActivity = (): void => {
    lastActivity.value = new Date()
    resetSessionTimer()
  }

  const startSessionTimer = (): void => {
    if (sessionTimer.value) {
      clearTimeout(sessionTimer.value)
    }

    sessionTimer.value = setTimeout(() => {
      // Auto logout when session expires
      logout()
      error.value = 'Session expired. Please login again.'
    }, sessionTimeout.value)
  }

  const resetSessionTimer = (): void => {
    if (sessionTimer.value) {
      clearTimeout(sessionTimer.value)
      startSessionTimer()
    }
  }

  const clearAuthState = (): void => {
    user.value = null
    token.value = null
    currentDevice.value = null
    error.value = null
    localStorage.removeItem('auth_token')

    if (sessionTimer.value) {
      clearTimeout(sessionTimer.value)
      sessionTimer.value = null
    }
  }

  const updateProfile = async (profileData: Partial<User>): Promise<void> => {
    if (!token.value) {throw new Error('Not authenticated')}

    isLoading.value = true
    error.value = null

    try {
      const response = await fetch('/api/auth/profile', {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          Authorization: `Bearer ${token.value}`,
          'X-CSRF-TOKEN': getCSRFToken(),
        },
        body: JSON.stringify(profileData),
      })

      if (!response.ok) {
        const errorData = await response.json()
        throw new Error(errorData.message || 'Profile update failed')
      }

      const data = await response.json()
      user.value = { ...user.value, ...data.user }
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Profile update failed'
      throw err
    } finally {
      isLoading.value = false
    }
  }

  const changePassword = async (oldPassword: string, newPassword: string): Promise<void> => {
    if (!token.value) {throw new Error('Not authenticated')}

    isLoading.value = true
    error.value = null

    try {
      const response = await fetch('/api/auth/change-password', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Authorization: `Bearer ${token.value}`,
          'X-CSRF-TOKEN': getCSRFToken(),
        },
        body: JSON.stringify({
          current_password: oldPassword,
          new_password: newPassword,
        }),
      })

      if (!response.ok) {
        const errorData = await response.json()
        const errorObj = {
          response: {
            status: response.status,
            data: errorData,
          },
        }
        throw errorObj
      }
    } catch (err) {
      // Handle error with enhanced error handler
      const authError = await authErrorHandler.handlePasswordResetError(err)
      error.value = authError.userMessage
      throw authError
    } finally {
      isLoading.value = false
    }
  }

  // Helper function to get CSRF token
  const getCSRFToken = (): string => {
    const token = document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement
    return token?.content || ''
  }

  // Initialize store
  const init = async (): Promise<void> => {
    if (token.value) {
      await fetchUser()
      if (user.value) {
        startSessionTimer()
      }
    }
  }

  return {
    // State
    user,
    token,
    isLoading,
    error,
    lastActivity,
    currentDevice,
    sessionTimeout,

    // Getters
    isAuthenticated,
    isAdmin,
    isSuperAdmin,
    isEmployee,
    isManager,
    hasPermission,
    hasRole,
    fullName,

    // Actions
    login,
    logout,
    fetchUser,
    updateActivity,
    clearAuthState,
    updateProfile,
    changePassword,
    init,

    // Error handling
    authErrorHandler,
  }
})
