/**
 * Client-side Input Validation and Sanitization Utilities
 *
 * Provides comprehensive form validation, input sanitization,
 * and security measures for the attendance system frontend.
 */

import { ref, computed, readonly } from 'vue'

export interface ValidationRule {
  required?: boolean
  minLength?: number
  maxLength?: number
  min?: number
  max?: number
  pattern?: RegExp
  email?: boolean
  phone?: boolean
  employeeId?: boolean
  custom?: (value: any) => boolean | string
  sanitize?: boolean
}

export interface ValidationResult {
  isValid: boolean
  errors: string[]
  sanitizedValue?: any
}

export interface FormValidationResult {
  isValid: boolean
  errors: Record<string, string[]>
  sanitizedData: Record<string, any>
}

/**
 * Input Sanitization Functions
 */
export class InputSanitizer {
  /**
   * Remove HTML tags and encode special characters
   */
  static sanitizeText(input: string): string {
    if (typeof input !== 'string') {return ''}

    return input
      .replace(/<[^>]*>/g, '') // Remove HTML tags
      .replace(/[<>&"']/g, (match) => {
        // Encode special characters
        const entities: Record<string, string> = {
          '<': '&lt;',
          '>': '&gt;',
          '&': '&amp;',
          '"': '&quot;',
          '\'': '&#x27;',
        }
        return entities[match] || match
      })
      .trim()
  }

  /**
   * Sanitize email addresses
   */
  static sanitizeEmail(input: string): string {
    if (typeof input !== 'string') {return ''}

    return input
      .toLowerCase()
      .trim()
      .replace(/[^\w@.-]/g, '') // Keep only valid email characters
  }

  /**
   * Sanitize phone numbers (keep only digits, spaces, +, -, ())
   */
  static sanitizePhone(input: string): string {
    if (typeof input !== 'string') {return ''}

    return input.replace(/[^\d\s+()-]/g, '').trim()
  }

  /**
   * Sanitize employee ID (alphanumeric only)
   */
  static sanitizeEmployeeId(input: string): string {
    if (typeof input !== 'string') {return ''}

    return input
      .toUpperCase()
      .replace(/[^A-Z0-9]/g, '')
      .trim()
  }

  /**
   * Sanitize numeric input
   */
  static sanitizeNumber(input: string | number): number | null {
    if (typeof input === 'number') {return input}
    if (typeof input !== 'string') {return null}

    const cleaned = input.replace(/[^\d.-]/g, '')
    const parsed = parseFloat(cleaned)

    return isNaN(parsed) ? null : parsed
  }

  /**
   * Sanitize URL
   */
  static sanitizeUrl(input: string): string {
    if (typeof input !== 'string') {return ''}

    try {
      const url = new URL(input.trim())
      // Only allow http/https protocols
      if (!['http:', 'https:'].includes(url.protocol)) {
        return ''
      }
      return url.toString()
    } catch {
      return ''
    }
  }

  /**
   * Remove SQL injection patterns (basic protection)
   */
  static sanitizeSql(input: string): string {
    if (typeof input !== 'string') {return ''}

    const sqlPatterns = [
      /(\b(union|select|insert|update|delete|drop|create|alter|exec|execute)\b)/gi,
      /(--|\/\*|\*\/|;|'|")/g,
      /(\bor\b|\band\b)(\s+\d+\s*=\s*\d+)/gi,
    ]

    let sanitized = input
    sqlPatterns.forEach((pattern) => {
      sanitized = sanitized.replace(pattern, '')
    })

    return sanitized.trim()
  }
}

/**
 * Validation Rules and Functions
 */
export class InputValidator {
  /**
   * Validate required field
   */
  static validateRequired(value: any): boolean {
    if (value === null || value === undefined) {return false}
    if (typeof value === 'string') {return value.trim().length > 0}
    if (Array.isArray(value)) {return value.length > 0}
    return true
  }

  /**
   * Validate string length
   */
  static validateLength(value: string, min?: number, max?: number): boolean {
    if (typeof value !== 'string') {return false}
    const length = value.trim().length

    if (min !== undefined && length < min) {return false}
    if (max !== undefined && length > max) {return false}

    return true
  }

  /**
   * Validate email format
   */
  static validateEmail(value: string): boolean {
    if (typeof value !== 'string') {return false}

    const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/
    return emailRegex.test(value.trim())
  }

  /**
   * Validate Indonesian phone number
   */
  static validatePhone(value: string): boolean {
    if (typeof value !== 'string') {return false}

    // Indonesian phone patterns: +62, 0, or direct number
    const phoneRegex = /^(\+62|62|0)?[8-9]\d{7,11}$/
    const cleaned = value.replace(/[\s()-]/g, '')

    return phoneRegex.test(cleaned)
  }

  /**
   * Validate employee ID format
   */
  static validateEmployeeId(value: string): boolean {
    if (typeof value !== 'string') {return false}

    // Pattern: EMP followed by 3-6 digits
    const empIdRegex = /^EMP\d{3,6}$/
    return empIdRegex.test(value.toUpperCase().trim())
  }

  /**
   * Validate numeric range
   */
  static validateNumberRange(value: number, min?: number, max?: number): boolean {
    if (typeof value !== 'number' || isNaN(value)) {return false}

    if (min !== undefined && value < min) {return false}
    if (max !== undefined && value > max) {return false}

    return true
  }

  /**
   * Validate date format and range
   */
  static validateDate(value: string, minDate?: Date, maxDate?: Date): boolean {
    if (typeof value !== 'string') {return false}

    const date = new Date(value)
    if (isNaN(date.getTime())) {return false}

    if (minDate && date < minDate) {return false}
    if (maxDate && date > maxDate) {return false}

    return true
  }

  /**
   * Validate password strength
   */
  static validatePassword(value: string): { isValid: boolean; score: number; feedback: string[] } {
    if (typeof value !== 'string') {
      return { isValid: false, score: 0, feedback: ['Password must be a string'] }
    }

    const feedback: string[] = []
    let score = 0

    // Length check
    if (value.length < 8) {
      feedback.push('Password must be at least 8 characters long')
    } else {
      score += 1
    }

    // Complexity checks
    if (!/[a-z]/.test(value)) {
      feedback.push('Password must contain lowercase letters')
    } else {
      score += 1
    }

    if (!/[A-Z]/.test(value)) {
      feedback.push('Password must contain uppercase letters')
    } else {
      score += 1
    }

    if (!/\d/.test(value)) {
      feedback.push('Password must contain numbers')
    } else {
      score += 1
    }

    if (!/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(value)) {
      feedback.push('Password must contain special characters')
    } else {
      score += 1
    }

    // Common password check
    const commonPasswords = ['password', '123456', 'qwerty', 'admin', 'letmein']
    if (commonPasswords.includes(value.toLowerCase())) {
      feedback.push('Password is too common')
      score = Math.max(0, score - 2)
    }

    return {
      isValid: score >= 4 && feedback.length === 0,
      score,
      feedback,
    }
  }
}

/**
 * Main validation function
 */
export function validateField(value: any, rules: ValidationRule): ValidationResult {
  const errors: string[] = []
  let sanitizedValue = value

  // Sanitization
  if (rules.sanitize && typeof value === 'string') {
    sanitizedValue = InputSanitizer.sanitizeText(value)
  }

  // Required validation
  if (rules.required && !InputValidator.validateRequired(sanitizedValue)) {
    errors.push('This field is required')
  }

  // Skip other validations if field is empty and not required
  if (!InputValidator.validateRequired(sanitizedValue) && !rules.required) {
    return { isValid: true, errors: [], sanitizedValue }
  }

  // Length validation
  if (typeof sanitizedValue === 'string') {
    if (!InputValidator.validateLength(sanitizedValue, rules.minLength, rules.maxLength)) {
      if (rules.minLength && rules.maxLength) {
        errors.push(`Must be between ${rules.minLength} and ${rules.maxLength} characters`)
      } else if (rules.minLength) {
        errors.push(`Must be at least ${rules.minLength} characters`)
      } else if (rules.maxLength) {
        errors.push(`Must be no more than ${rules.maxLength} characters`)
      }
    }
  }

  // Pattern validation
  if (rules.pattern && typeof sanitizedValue === 'string') {
    if (!rules.pattern.test(sanitizedValue)) {
      errors.push('Invalid format')
    }
  }

  // Email validation
  if (rules.email && typeof sanitizedValue === 'string') {
    sanitizedValue = InputSanitizer.sanitizeEmail(sanitizedValue)
    if (!InputValidator.validateEmail(sanitizedValue)) {
      errors.push('Invalid email format')
    }
  }

  // Phone validation
  if (rules.phone && typeof sanitizedValue === 'string') {
    sanitizedValue = InputSanitizer.sanitizePhone(sanitizedValue)
    if (!InputValidator.validatePhone(sanitizedValue)) {
      errors.push('Invalid phone number format')
    }
  }

  // Employee ID validation
  if (rules.employeeId && typeof sanitizedValue === 'string') {
    sanitizedValue = InputSanitizer.sanitizeEmployeeId(sanitizedValue)
    if (!InputValidator.validateEmployeeId(sanitizedValue)) {
      errors.push('Invalid employee ID format (e.g., EMP001)')
    }
  }

  // Numeric validation
  if (
    typeof sanitizedValue === 'number' ||
    (typeof sanitizedValue === 'string' && !isNaN(Number(sanitizedValue)))
  ) {
    const numValue = typeof sanitizedValue === 'number' ? sanitizedValue : Number(sanitizedValue)
    if (!InputValidator.validateNumberRange(numValue, rules.min, rules.max)) {
      if (rules.min !== undefined && rules.max !== undefined) {
        errors.push(`Must be between ${rules.min} and ${rules.max}`)
      } else if (rules.min !== undefined) {
        errors.push(`Must be at least ${rules.min}`)
      } else if (rules.max !== undefined) {
        errors.push(`Must be no more than ${rules.max}`)
      }
    }
  }

  // Custom validation
  if (rules.custom) {
    const customResult = rules.custom(sanitizedValue)
    if (typeof customResult === 'string') {
      errors.push(customResult)
    } else if (!customResult) {
      errors.push('Invalid value')
    }
  }

  return {
    isValid: errors.length === 0,
    errors,
    sanitizedValue,
  }
}

/**
 * Validate entire form
 */
export function validateForm(
  data: Record<string, any>,
  rules: Record<string, ValidationRule>
): FormValidationResult {
  const errors: Record<string, string[]> = {}
  const sanitizedData: Record<string, any> = {}
  let isValid = true

  for (const [field, value] of Object.entries(data)) {
    const fieldRules = rules[field]
    if (!fieldRules) {
      sanitizedData[field] = value
      continue
    }

    const result = validateField(value, fieldRules)
    sanitizedData[field] = result.sanitizedValue

    if (!result.isValid) {
      errors[field] = result.errors
      isValid = false
    }
  }

  return {
    isValid,
    errors,
    sanitizedData,
  }
}

/**
 * Common validation rule sets
 */
export const CommonValidationRules = {
  // User registration
  username: {
    required: true,
    minLength: 3,
    maxLength: 20,
    pattern: /^[a-zA-Z0-9_]+$/,
    sanitize: true,
  },

  email: {
    required: true,
    email: true,
    maxLength: 255,
    sanitize: true,
  },

  password: {
    required: true,
    minLength: 8,
    maxLength: 128,
    custom: (value: string) => {
      const result = InputValidator.validatePassword(value)
      return result.isValid || result.feedback.join(', ')
    },
  },

  // Employee data
  employeeId: {
    required: true,
    employeeId: true,
    sanitize: true,
  },

  fullName: {
    required: true,
    minLength: 2,
    maxLength: 100,
    pattern: /^[a-zA-Z\s.'-]+$/,
    sanitize: true,
  },

  phone: {
    required: true,
    phone: true,
    sanitize: true,
  },

  // Attendance data
  attendanceDate: {
    required: true,
    custom: (value: string) => {
      const date = new Date(value)
      const today = new Date()
      const oneMonthAgo = new Date(today.getTime() - 30 * 24 * 60 * 60 * 1000)

      return (
        InputValidator.validateDate(value, oneMonthAgo, today) ||
        'Date must be within the last 30 days'
      )
    },
  },

  // General text
  shortText: {
    maxLength: 255,
    sanitize: true,
  },

  longText: {
    maxLength: 1000,
    sanitize: true,
  },

  // Numeric
  percentage: {
    min: 0,
    max: 100,
  },

  positiveInteger: {
    min: 1,
    custom: (value: any) => Number.isInteger(Number(value)) || 'Must be a whole number',
  },
}

/**
 * Real-time validation composable for Vue components
 */
export function useFormValidation(initialRules: Record<string, ValidationRule>) {
  const rules = ref(initialRules)
  const errors = ref<Record<string, string[]>>({})
  const isValidating = ref(false)

  const validateField = (field: string, value: any) => {
    const fieldRules = rules.value[field]
    if (!fieldRules) {return}

    const result = validateField(value, fieldRules)

    if (result.isValid) {
      delete errors.value[field]
    } else {
      errors.value[field] = result.errors
    }

    return result
  }

  const validateAll = (data: Record<string, any>) => {
    isValidating.value = true
    const result = validateForm(data, rules.value)
    errors.value = result.errors
    isValidating.value = false
    return result
  }

  const clearErrors = (field?: string) => {
    if (field) {
      delete errors.value[field]
    } else {
      errors.value = {}
    }
  }

  const hasErrors = computed(() => Object.keys(errors.value).length > 0)
  const getFieldError = (field: string) => errors.value[field]?.[0] || null

  return {
    rules,
    errors: readonly(errors),
    isValidating: readonly(isValidating),
    hasErrors,
    validateField,
    validateAll,
    clearErrors,
    getFieldError,
  }
}
