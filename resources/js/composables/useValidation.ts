/**
 * Vue Composable for Form Validation
 *
 * Provides reactive form validation with real-time feedback
 * and automatic sanitization.
 */

import { ref, reactive, computed, watch } from 'vue'
import type { Ref } from 'vue'
import {
  validateField,
  validateForm,
  CommonValidationRules,
  type ValidationRule,
  type ValidationResult,
  type FormValidationResult,
} from '@/utils/validation'

export interface UseValidationOptions {
  validateOnChange?: boolean
  validateOnBlur?: boolean
  debounceMs?: number
}

export interface ValidatedField {
  value: Ref<any>
  error: Ref<string | null>
  isValid: Ref<boolean>
  isDirty: Ref<boolean>
  isTouched: Ref<boolean>
  validate: () => ValidationResult
  sanitize: () => void
  reset: () => void
}

export interface UseValidationReturn {
  fields: Record<string, ValidatedField>
  errors: Ref<Record<string, string[]>>
  isValid: Ref<boolean>
  isDirty: Ref<boolean>
  isTouched: Ref<boolean>
  isValidating: Ref<boolean>
  validateAll: () => FormValidationResult
  validateField: (fieldName: string) => ValidationResult | null
  sanitizeAll: () => void
  reset: () => void
  clearErrors: (fieldName?: string) => void
  setFieldValue: (fieldName: string, value: any) => void
  getFieldError: (fieldName: string) => string | null
  getSanitizedData: () => Record<string, any>
}

/**
 * Main validation composable
 */
export function useValidation(
  schema: Record<string, ValidationRule>,
  initialValues: Record<string, any> = {},
  options: UseValidationOptions = {}
): UseValidationReturn {
  const { validateOnChange = true, validateOnBlur = true, debounceMs = 300 } = options

  // Reactive state
  const fields = reactive<Record<string, ValidatedField>>({})
  const errors = ref<Record<string, string[]>>({})
  const isValidating = ref(false)
  const globalDirty = ref(false)
  const globalTouched = ref(false)

  // Debounce timer
  let debounceTimer: NodeJS.Timeout | null = null

  // Initialize fields
  for (const [fieldName, rules] of Object.entries(schema)) {
    const initialValue = initialValues[fieldName] || ''

    fields[fieldName] = {
      value: ref(initialValue),
      error: ref<string | null>(null),
      isValid: ref(true),
      isDirty: ref(false),
      isTouched: ref(false),

      validate() {
        const result = validateField(this.value.value, rules)
        this.isValid.value = result.isValid
        this.error.value = result.errors[0] || null

        if (result.isValid) {
          delete errors.value[fieldName]
        } else {
          errors.value[fieldName] = result.errors
        }

        return result
      },

      sanitize() {
        const result = validateField(this.value.value, { ...rules, sanitize: true })
        if (result.sanitizedValue !== undefined) {
          this.value.value = result.sanitizedValue
        }
      },

      reset() {
        this.value.value = initialValues[fieldName] || ''
        this.error.value = null
        this.isValid.value = true
        this.isDirty.value = false
        this.isTouched.value = false
        delete errors.value[fieldName]
      },
    }

    // Watch for changes
    if (validateOnChange) {
      watch(
        () => fields[fieldName].value.value,
        (newValue, oldValue) => {
          if (newValue !== oldValue) {
            fields[fieldName].isDirty.value = true
            globalDirty.value = true

            // Debounced validation
            if (debounceTimer) {
              clearTimeout(debounceTimer)
            }

            debounceTimer = setTimeout(() => {
              if (fields[fieldName].isTouched.value) {
                fields[fieldName].validate()
              }
            }, debounceMs)
          }
        }
      )
    }
  }

  // Computed properties
  const isValid = computed(() => {
    return (
      Object.values(fields).every((field) => field.isValid.value) &&
      Object.keys(errors.value).length === 0
    )
  })

  const isDirty = computed(() => {
    return globalDirty.value || Object.values(fields).some((field) => field.isDirty.value)
  })

  const isTouched = computed(() => {
    return globalTouched.value || Object.values(fields).some((field) => field.isTouched.value)
  })

  // Methods
  const validateAll = (): FormValidationResult => {
    isValidating.value = true

    const data: Record<string, any> = {}
    for (const [fieldName, field] of Object.entries(fields)) {
      data[fieldName] = field.value.value
    }

    const result = validateForm(data, schema)
    errors.value = result.errors

    // Update individual field states
    for (const [fieldName, field] of Object.entries(fields)) {
      field.isValid.value = !result.errors[fieldName]
      field.error.value = result.errors[fieldName]?.[0] || null
      field.isTouched.value = true
    }

    globalTouched.value = true
    isValidating.value = false

    return result
  }

  const validateSingleField = (fieldName: string): ValidationResult | null => {
    const field = fields[fieldName]
    if (!field) {return null}

    field.isTouched.value = true
    globalTouched.value = true

    return field.validate()
  }

  const sanitizeAll = (): void => {
    for (const field of Object.values(fields)) {
      field.sanitize()
    }
  }

  const reset = (): void => {
    for (const field of Object.values(fields)) {
      field.reset()
    }
    errors.value = {}
    globalDirty.value = false
    globalTouched.value = false
  }

  const clearErrors = (fieldName?: string): void => {
    if (fieldName) {
      const field = fields[fieldName]
      if (field) {
        field.error.value = null
        field.isValid.value = true
      }
      delete errors.value[fieldName]
    } else {
      for (const field of Object.values(fields)) {
        field.error.value = null
        field.isValid.value = true
      }
      errors.value = {}
    }
  }

  const setFieldValue = (fieldName: string, value: any): void => {
    const field = fields[fieldName]
    if (field) {
      field.value.value = value
      field.isDirty.value = true
      globalDirty.value = true
    }
  }

  const getFieldError = (fieldName: string): string | null => {
    return fields[fieldName]?.error.value || null
  }

  const getSanitizedData = (): Record<string, any> => {
    const data: Record<string, any> = {}
    for (const [fieldName, field] of Object.entries(fields)) {
      field.sanitize()
      data[fieldName] = field.value.value
    }
    return data
  }

  // Blur handler helper
  const createBlurHandler = (fieldName: string) => {
    return () => {
      const field = fields[fieldName]
      if (field && validateOnBlur) {
        field.isTouched.value = true
        globalTouched.value = true
        field.validate()
      }
    }
  }

  return {
    fields,
    errors,
    isValid,
    isDirty,
    isTouched,
    isValidating,
    validateAll,
    validateField: validateSingleField,
    sanitizeAll,
    reset,
    clearErrors,
    setFieldValue,
    getFieldError,
    getSanitizedData,
  }
}

/**
 * Predefined validation schemas for common forms
 */
export const ValidationSchemas = {
  // User Registration
  userRegistration: {
    username: CommonValidationRules.username,
    email: CommonValidationRules.email,
    password: CommonValidationRules.password,
    confirmPassword: {
      required: true,
      custom: (value: string, data?: Record<string, any>) => {
        if (data && value !== data.password) {
          return 'Passwords do not match'
        }
        return true
      },
    },
  },

  // Employee Registration
  employeeRegistration: {
    employee_id: CommonValidationRules.employeeId,
    full_name: CommonValidationRules.fullName,
    email: CommonValidationRules.email,
    phone: CommonValidationRules.phone,
    position: {
      required: true,
      minLength: 2,
      maxLength: 100,
      sanitize: true,
    },
    department: {
      required: true,
      minLength: 2,
      maxLength: 100,
      sanitize: true,
    },
  },

  // Face Recognition Settings
  faceSettings: {
    confidence_threshold: {
      required: true,
      min: 0.5,
      max: 1.0,
      custom: (value: number) => {
        return (value >= 0.5 && value <= 1.0) || 'Confidence threshold must be between 0.5 and 1.0'
      },
    },
    liveness_threshold: {
      required: true,
      min: 0.3,
      max: 1.0,
      custom: (value: number) => {
        return (value >= 0.3 && value <= 1.0) || 'Liveness threshold must be between 0.3 and 1.0'
      },
    },
  },

  // Attendance Manual Entry
  manualAttendance: {
    employee_id: CommonValidationRules.employeeId,
    date: CommonValidationRules.attendanceDate,
    check_in_time: {
      required: true,
      pattern: /^([01]?[0-9]|2[0-3]):[0-5][0-9]$/,
      custom: (value: string) => {
        const timeRegex = /^([01]?[0-9]|2[0-3]):[0-5][0-9]$/
        return timeRegex.test(value) || 'Time must be in HH:MM format'
      },
    },
    check_out_time: {
      pattern: /^([01]?[0-9]|2[0-3]):[0-5][0-9]$/,
      custom: (value: string, data?: Record<string, any>) => {
        if (!value) {return true} // Optional field

        const timeRegex = /^([01]?[0-9]|2[0-3]):[0-5][0-9]$/
        if (!timeRegex.test(value)) {
          return 'Time must be in HH:MM format'
        }

        if (data && data.check_in_time) {
          const checkIn = new Date(`1970-01-01T${data.check_in_time}:00`)
          const checkOut = new Date(`1970-01-01T${value}:00`)

          if (checkOut <= checkIn) {
            return 'Check-out time must be after check-in time'
          }
        }

        return true
      },
    },
    reason: {
      maxLength: 500,
      sanitize: true,
    },
  },

  // Schedule Creation
  scheduleCreation: {
    subject_id: {
      required: true,
      pattern: /^[A-Z0-9]+$/,
      sanitize: true,
    },
    employee_id: CommonValidationRules.employeeId,
    time_slot_id: {
      required: true,
      pattern: /^slot_\d+$/,
    },
    day_of_week: {
      required: true,
      custom: (value: string) => {
        const validDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday']
        return validDays.includes(value.toLowerCase()) || 'Invalid day of week'
      },
    },
    room: {
      maxLength: 50,
      sanitize: true,
    },
    effective_from: {
      required: true,
      custom: (value: string) => {
        const date = new Date(value)
        const today = new Date()
        today.setHours(0, 0, 0, 0)

        return date >= today || 'Effective date cannot be in the past'
      },
    },
  },
}

/**
 * Helper function to create validation rules for dynamic forms
 */
export function createValidationSchema(
  fields: Array<{
    name: string
    type: 'text' | 'email' | 'phone' | 'number' | 'date' | 'password' | 'employeeId'
    required?: boolean
    minLength?: number
    maxLength?: number
    min?: number
    max?: number
    pattern?: RegExp
    custom?: (value: any) => boolean | string
  }>
): Record<string, ValidationRule> {
  const schema: Record<string, ValidationRule> = {}

  for (const field of fields) {
    const rule: ValidationRule = {
      required: field.required,
      minLength: field.minLength,
      maxLength: field.maxLength,
      min: field.min,
      max: field.max,
      pattern: field.pattern,
      custom: field.custom,
      sanitize: true,
    }

    // Apply type-specific rules
    switch (field.type) {
      case 'email':
        rule.email = true
        break
      case 'phone':
        rule.phone = true
        break
      case 'employeeId':
        rule.employeeId = true
        break
      case 'password':
        rule.minLength = rule.minLength || 8
        break
    }

    schema[field.name] = rule
  }

  return schema
}
