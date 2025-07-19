/**
 * Enhanced notification utilities for the attendance system
 * Provides common notification patterns for CRUD operations, loading states, and user feedback
 */

// Form submission helpers
export const FormNotifications = {
  /**
   * Show loading state for form submission
   */
  showLoading(message = 'Processing your request...') {
    return showToast({
      type: 'info',
      title: 'Please wait',
      message,
      duration: 0,
      dismissible: false,
      progress: false,
      icon: false,
    })
  },

  /**
   * Show success after form submission
   */
  showSuccess(message = 'Operation completed successfully', options = {}) {
    return toast.success(message, {
      title: 'Success',
      duration: 4000,
      progress: true,
      ...options,
    })
  },

  /**
   * Show validation errors
   */
  showValidationErrors(errors, title = 'Please fix the following errors:') {
    const errorList = Array.isArray(errors)
      ? errors.join(', ')
      : Object.values(errors).flat().join(', ')

    return toast.error(errorList, {
      title,
      duration: 8000,
      progress: false,
    })
  },

  /**
   * Show network/server errors
   */
  showServerError(message = 'An unexpected error occurred. Please try again.') {
    return toast.error(message, {
      title: 'Server Error',
      duration: 8000,
      actions: [
        {
          label: 'Retry',
          style: 'primary',
          callback: () => window.location.reload(),
        },
      ],
    })
  },
}

// CRUD operation helpers
export const CrudNotifications = {
  /**
   * Show notifications for create operations
   */
  created(entityName, options = {}) {
    return toast.success(`${entityName} has been created successfully`, {
      title: 'Created',
      duration: 4000,
      progress: true,
      ...options,
    })
  },

  /**
   * Show notifications for update operations
   */
  updated(entityName, options = {}) {
    return toast.success(`${entityName} has been updated successfully`, {
      title: 'Updated',
      duration: 4000,
      progress: true,
      ...options,
    })
  },

  /**
   * Show notifications for delete operations
   */
  deleted(entityName, options = {}) {
    return toast.success(`${entityName} has been deleted successfully`, {
      title: 'Deleted',
      duration: 4000,
      progress: true,
      ...options,
    })
  },

  /**
   * Show confirmation for destructive actions
   */
  confirmDelete(entityName, onConfirm, onCancel = null) {
    return showToast({
      type: 'warning',
      title: 'Confirm Delete',
      message: `Are you sure you want to delete this ${entityName}? This action cannot be undone.`,
      duration: 15000,
      progress: true,
      actions: [
        {
          label: 'Delete',
          style: 'primary',
          callback: onConfirm,
        },
        {
          label: 'Cancel',
          style: 'secondary',
          callback: onCancel || (() => {}),
        },
      ],
    })
  },
}

// File upload helpers
export const FileNotifications = {
  /**
   * Show upload progress
   */
  showUploadProgress(filename, progress = 0) {
    const message = `Uploading ${filename}... ${Math.round(progress)}%`
    return showToast({
      type: 'info',
      title: 'Upload in Progress',
      message,
      duration: 0,
      progress: true,
      progressWidth: progress,
      dismissible: false,
    })
  },

  /**
   * Show upload success
   */
  showUploadSuccess(filename, options = {}) {
    return toast.success(`${filename} uploaded successfully`, {
      title: 'Upload Complete',
      duration: 4000,
      progress: true,
      ...options,
    })
  },

  /**
   * Show upload error
   */
  showUploadError(filename, error = 'Upload failed') {
    return toast.error(`Failed to upload ${filename}: ${error}`, {
      title: 'Upload Failed',
      duration: 8000,
      actions: [
        {
          label: 'Retry',
          style: 'primary',
          callback: () => console.log('Retry upload'),
        },
      ],
    })
  },
}

// System notifications
export const SystemNotifications = {
  /**
   * Show session expiry warning
   */
  sessionExpiring(minutesLeft = 5) {
    return showToast({
      type: 'warning',
      title: 'Session Expiry Warning',
      message: `Your session will expire in ${minutesLeft} minutes. Click to extend.`,
      duration: 30000,
      progress: true,
      actions: [
        {
          label: 'Extend Session',
          style: 'primary',
          callback: () => {
            // Make AJAX call to extend session
            fetch('/api/extend-session', { method: 'POST' })
              .then(() => toast.success('Session extended successfully'))
              .catch(() => toast.error('Failed to extend session'))
          },
        },
        {
          label: 'Logout',
          style: 'secondary',
          callback: () => (window.location.href = '/logout'),
        },
      ],
    })
  },

  /**
   * Show connection lost notification
   */
  connectionLost() {
    return showToast({
      type: 'error',
      title: 'Connection Lost',
      message: 'Network connection lost. Retrying automatically...',
      duration: 0,
      dismissible: false,
      actions: [
        {
          label: 'Retry Now',
          style: 'primary',
          callback: () => window.location.reload(),
        },
      ],
    })
  },

  /**
   * Show maintenance mode notification
   */
  maintenanceMode(message = 'System maintenance in progress') {
    return showToast({
      type: 'warning',
      title: 'Maintenance Mode',
      message,
      duration: 0,
      dismissible: false,
      progress: false,
    })
  },

  /**
   * Show update available notification
   */
  updateAvailable() {
    return showToast({
      type: 'info',
      title: 'Update Available',
      message: 'A new version is available. Refresh to update.',
      duration: 0,
      actions: [
        {
          label: 'Refresh Now',
          style: 'primary',
          callback: () => window.location.reload(),
        },
        {
          label: 'Later',
          style: 'secondary',
          callback: () => {},
        },
      ],
    })
  },
}

// Attendance-specific notifications
export const AttendanceNotifications = {
  /**
   * Show check-in success
   */
  checkedIn(time = null) {
    const timeStr = time || new Date().toLocaleTimeString()
    return toast.success(`Checked in successfully at ${timeStr}`, {
      title: 'Welcome!',
      duration: 5000,
      progress: true,
    })
  },

  /**
   * Show check-out success
   */
  checkedOut(time = null) {
    const timeStr = time || new Date().toLocaleTimeString()
    return toast.success(`Checked out successfully at ${timeStr}`, {
      title: 'Have a great day!',
      duration: 5000,
      progress: true,
    })
  },

  /**
   * Show late arrival warning
   */
  lateArrival(minutesLate) {
    return toast.warning(`You are ${minutesLate} minutes late`, {
      title: 'Late Arrival',
      duration: 8000,
      progress: true,
    })
  },

  /**
   * Show location verification error
   */
  locationError(message = 'You are not at an authorized location') {
    return toast.error(message, {
      title: 'Location Verification Failed',
      duration: 8000,
      actions: [
        {
          label: 'Check Location',
          style: 'primary',
          callback: () => {
            if (navigator.geolocation) {
              navigator.geolocation.getCurrentPosition(
                () => toast.info('Location services are working'),
                () => toast.error('Please enable location services')
              )
            }
          },
        },
      ],
    })
  },

  /**
   * Show face detection error
   */
  faceDetectionError(message = 'Face detection failed') {
    return toast.error(message, {
      title: 'Biometric Verification Failed',
      duration: 8000,
      actions: [
        {
          label: 'Try Again',
          style: 'primary',
          callback: () => console.log('Retry face detection'),
        },
      ],
    })
  },
}

// Export convenience functions to global scope
if (typeof window !== 'undefined') {
  window.FormNotifications = FormNotifications
  window.CrudNotifications = CrudNotifications
  window.FileNotifications = FileNotifications
  window.SystemNotifications = SystemNotifications
  window.AttendanceNotifications = AttendanceNotifications
}
