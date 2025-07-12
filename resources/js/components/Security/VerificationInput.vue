<template>
  <div class="verification-input">
    <!-- Input Label -->
    <label v-if="label" :for="inputId" class="input-label">
      {{ label }}
      <span v-if="required" class="required-indicator">*</span>
    </label>

    <!-- Code Input Container -->
    <div class="input-container" :class="containerClasses">
      <!-- Single Input Mode -->
      <div v-if="mode === 'single'" class="single-input-wrapper">
        <input
          :id="inputId"
          v-model="inputValue"
          type="text"
          :maxlength="maxLength"
          :pattern="pattern"
          :inputmode="inputMode"
          :placeholder="placeholder"
          :disabled="disabled"
          :readonly="readonly"
          class="single-input"
          :class="inputClasses"
          @input="handleInput"
          @keydown="handleKeydown"
          @paste="handlePaste"
          @focus="handleFocus"
          @blur="handleBlur"
        />
        
        <!-- Input Addon -->
        <div v-if="showAddon" class="input-addon">
          <slot name="addon">
            <Icon v-if="addonIcon" :name="addonIcon" class="w-4 h-4" />
          </slot>
        </div>
      </div>

      <!-- Individual Digits Mode -->
      <div v-else class="digits-input-wrapper">
        <input
          v-for="(digit, index) in digits"
          :key="index"
          :id="`${inputId}-${index}`"
          v-model="digits[index]"
          type="text"
          maxlength="1"
          :pattern="digitPattern"
          inputmode="numeric"
          class="digit-input"
          :class="getDigitClasses(index)"
          :disabled="disabled"
          :readonly="readonly"
          @input="handleDigitInput(index, $event)"
          @keydown="handleDigitKeydown(index, $event)"
          @focus="handleDigitFocus(index)"
          @blur="handleDigitBlur(index)"
          @paste="handlePaste"
        />
      </div>
    </div>

    <!-- Helper Text -->
    <div v-if="showHelperText" class="helper-text">
      <div v-if="error" class="error-text">
        <Icon name="x-circle" class="w-4 h-4 mr-1" />
        {{ error }}
      </div>
      <div v-else-if="helpText" class="help-text">
        <Icon name="info" class="w-4 h-4 mr-1" />
        {{ helpText }}
      </div>
    </div>

    <!-- Timer Display (for TOTP) -->
    <div v-if="showTimer && codeType === 'totp'" class="timer-display">
      <div class="timer-wrapper">
        <div class="timer-circle" :style="timerStyle">
          <svg class="timer-svg" viewBox="0 0 36 36">
            <path
              class="timer-bg"
              d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
            />
            <path
              class="timer-progress"
              :stroke-dasharray="`${timerProgress}, 100`"
              d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
            />
          </svg>
          <div class="timer-text">{{ remainingTime }}s</div>
        </div>
        <p class="timer-label">Code expires in</p>
      </div>
    </div>

    <!-- Alternative Options -->
    <div v-if="showAlternatives" class="alternatives">
      <div class="alternatives-divider">
        <span class="divider-text">Or use alternative method</span>
      </div>
      
      <div class="alternatives-buttons">
        <button
          v-if="allowRecoveryCode"
          @click="switchToRecovery"
          class="alternative-btn"
          :class="{ 'active': codeType === 'recovery' }"
        >
          <Icon name="key" class="w-4 h-4 mr-2" />
          Use Recovery Code
        </button>
        
        <button
          v-if="allowSMS"
          @click="requestSMS"
          class="alternative-btn"
          :disabled="smsLoading"
        >
          <div v-if="smsLoading" class="spinner mr-2" />
          <Icon v-else name="phone" class="w-4 h-4 mr-2" />
          Send SMS Code
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import { useToast } from '@/composables/useToast'

// Props
const props = defineProps({
  modelValue: {
    type: String,
    default: ''
  },
  label: {
    type: String,
    default: ''
  },
  placeholder: {
    type: String,
    default: '000000'
  },
  maxLength: {
    type: Number,
    default: 6
  },
  mode: {
    type: String,
    default: 'single', // 'single' or 'digits'
    validator: value => ['single', 'digits'].includes(value)
  },
  codeType: {
    type: String,
    default: 'totp', // 'totp', 'recovery', 'sms'
    validator: value => ['totp', 'recovery', 'sms'].includes(value)
  },
  disabled: {
    type: Boolean,
    default: false
  },
  readonly: {
    type: Boolean,
    default: false
  },
  required: {
    type: Boolean,
    default: false
  },
  error: {
    type: String,
    default: ''
  },
  helpText: {
    type: String,
    default: ''
  },
  showTimer: {
    type: Boolean,
    default: false
  },
  showAlternatives: {
    type: Boolean,
    default: false
  },
  allowRecoveryCode: {
    type: Boolean,
    default: true
  },
  allowSMS: {
    type: Boolean,
    default: false
  },
  autoFocus: {
    type: Boolean,
    default: true
  },
  autoSubmit: {
    type: Boolean,
    default: false
  }
})

// Emits
const emit = defineEmits([
  'update:modelValue',
  'complete',
  'change',
  'focus',
  'blur',
  'switchToRecovery',
  'requestSMS'
])

// Reactive data
const inputId = ref(`verification-input-${Math.random().toString(36).substr(2, 9)}`)
const inputValue = ref(props.modelValue)
const digits = ref(Array(props.maxLength).fill(''))
const focused = ref(false)
const currentDigitIndex = ref(0)
const remainingTime = ref(30)
const timerInterval = ref(null)
const smsLoading = ref(false)

// Composables
const { toast } = useToast()

// Computed
const pattern = computed(() => {
  switch (props.codeType) {
    case 'totp':
    case 'sms':
      return '[0-9]*'
    case 'recovery':
      return '[A-Za-z0-9]*'
    default:
      return '[0-9]*'
  }
})

const digitPattern = computed(() => {
  switch (props.codeType) {
    case 'totp':
    case 'sms':
      return '[0-9]'
    case 'recovery':
      return '[A-Za-z0-9]'
    default:
      return '[0-9]'
  }
})

const inputMode = computed(() => {
  return props.codeType === 'recovery' ? 'text' : 'numeric'
})

const containerClasses = computed(() => ({
  'error': props.error,
  'disabled': props.disabled,
  'focused': focused.value
}))

const inputClasses = computed(() => ({
  'text-center': true,
  'font-mono': true,
  'tracking-wider': props.codeType !== 'recovery'
}))

const showAddon = computed(() => {
  return !!props.addonIcon || !!slots.addon
})

const addonIcon = computed(() => {
  switch (props.codeType) {
    case 'totp':
      return 'clock'
    case 'recovery':
      return 'key'
    case 'sms':
      return 'phone'
    default:
      return null
  }
})

const showHelperText = computed(() => {
  return props.error || props.helpText
})

const timerProgress = computed(() => {
  return (remainingTime.value / 30) * 100
})

const timerStyle = computed(() => ({
  '--progress': timerProgress.value
}))

// Methods
const handleInput = (event) => {
  let value = event.target.value
  
  // Apply input filtering based on code type
  if (props.codeType === 'totp' || props.codeType === 'sms') {
    value = value.replace(/[^0-9]/g, '')
  } else if (props.codeType === 'recovery') {
    value = value.replace(/[^A-Za-z0-9]/g, '').toUpperCase()
  }
  
  // Limit length
  value = value.slice(0, props.maxLength)
  
  inputValue.value = value
  emit('update:modelValue', value)
  emit('change', value)
  
  // Auto-submit when complete
  if (props.autoSubmit && value.length === props.maxLength) {
    emit('complete', value)
  }
}

const handleKeydown = (event) => {
  // Allow backspace, delete, tab, escape, enter
  if ([8, 9, 27, 13, 46].includes(event.keyCode)) {
    return
  }
  
  // Allow Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X
  if (event.ctrlKey && [65, 67, 86, 88].includes(event.keyCode)) {
    return
  }
  
  // For numeric codes, only allow numbers
  if (props.codeType === 'totp' || props.codeType === 'sms') {
    if (!(event.keyCode >= 48 && event.keyCode <= 57) && !(event.keyCode >= 96 && event.keyCode <= 105)) {
      event.preventDefault()
    }
  }
}

const handlePaste = (event) => {
  event.preventDefault()
  const paste = (event.clipboardData || window.clipboardData).getData('text')
  
  let cleanPaste = paste.replace(/\s/g, '')
  
  if (props.codeType === 'totp' || props.codeType === 'sms') {
    cleanPaste = cleanPaste.replace(/[^0-9]/g, '')
  } else if (props.codeType === 'recovery') {
    cleanPaste = cleanPaste.replace(/[^A-Za-z0-9]/g, '').toUpperCase()
  }
  
  cleanPaste = cleanPaste.slice(0, props.maxLength)
  
  if (props.mode === 'single') {
    inputValue.value = cleanPaste
    emit('update:modelValue', cleanPaste)
  } else {
    // Fill individual digit inputs
    for (let i = 0; i < props.maxLength; i++) {
      digits.value[i] = cleanPaste[i] || ''
    }
    updateModelFromDigits()
  }
  
  emit('change', cleanPaste)
  
  if (props.autoSubmit && cleanPaste.length === props.maxLength) {
    emit('complete', cleanPaste)
  }
}

const handleFocus = () => {
  focused.value = true
  emit('focus')
}

const handleBlur = () => {
  focused.value = false
  emit('blur')
}

// Digit-specific methods
const handleDigitInput = (index, event) => {
  let value = event.target.value
  
  if (props.codeType === 'totp' || props.codeType === 'sms') {
    value = value.replace(/[^0-9]/g, '')
  } else if (props.codeType === 'recovery') {
    value = value.replace(/[^A-Za-z0-9]/g, '').toUpperCase()
  }
  
  digits.value[index] = value.slice(-1) // Take only the last character
  
  // Move to next input if value was entered
  if (value && index < props.maxLength - 1) {
    const nextInput = document.getElementById(`${inputId.value}-${index + 1}`)
    if (nextInput) {
      nextInput.focus()
    }
  }
  
  updateModelFromDigits()
}

const handleDigitKeydown = (index, event) => {
  // Handle backspace
  if (event.keyCode === 8 && !digits.value[index] && index > 0) {
    const prevInput = document.getElementById(`${inputId.value}-${index - 1}`)
    if (prevInput) {
      prevInput.focus()
      digits.value[index - 1] = ''
      updateModelFromDigits()
    }
  }
}

const handleDigitFocus = (index) => {
  currentDigitIndex.value = index
  focused.value = true
  emit('focus')
}

const handleDigitBlur = (index) => {
  // Only set focused to false if no other digit input is focused
  setTimeout(() => {
    const anyFocused = Array.from({ length: props.maxLength }, (_, i) => {
      const input = document.getElementById(`${inputId.value}-${i}`)
      return input === document.activeElement
    }).some(Boolean)
    
    if (!anyFocused) {
      focused.value = false
      emit('blur')
    }
  }, 10)
}

const getDigitClasses = (index) => ({
  'focused': currentDigitIndex.value === index && focused.value,
  'filled': !!digits.value[index],
  'error': props.error
})

const updateModelFromDigits = () => {
  const value = digits.value.join('')
  emit('update:modelValue', value)
  emit('change', value)
  
  if (props.autoSubmit && value.length === props.maxLength) {
    emit('complete', value)
  }
}

// Alternative methods
const switchToRecovery = () => {
  emit('switchToRecovery')
}

const requestSMS = async () => {
  smsLoading.value = true
  try {
    emit('requestSMS')
    // Simulate SMS request delay
    await new Promise(resolve => setTimeout(resolve, 1000))
    toast.success('SMS code sent successfully')
  } catch (error) {
    toast.error('Failed to send SMS code')
  } finally {
    smsLoading.value = false
  }
}

// Timer methods
const startTimer = () => {
  if (timerInterval.value) {
    clearInterval(timerInterval.value)
  }
  
  remainingTime.value = 30
  timerInterval.value = setInterval(() => {
    remainingTime.value--
    if (remainingTime.value <= 0) {
      clearInterval(timerInterval.value)
      remainingTime.value = 30
      startTimer() // Restart timer for TOTP
    }
  }, 1000)
}

const stopTimer = () => {
  if (timerInterval.value) {
    clearInterval(timerInterval.value)
    timerInterval.value = null
  }
}

// Watch for model value changes
watch(() => props.modelValue, (newValue) => {
  if (props.mode === 'single') {
    inputValue.value = newValue
  } else {
    // Update digits array
    for (let i = 0; i < props.maxLength; i++) {
      digits.value[i] = newValue[i] || ''
    }
  }
})

// Watch for code type changes
watch(() => props.codeType, (newType) => {
  if (newType === 'totp' && props.showTimer) {
    startTimer()
  } else {
    stopTimer()
  }
})

// Lifecycle
onMounted(() => {
  // Auto-focus first input
  if (props.autoFocus && !props.disabled && !props.readonly) {
    setTimeout(() => {
      const firstInput = props.mode === 'single' 
        ? document.getElementById(inputId.value)
        : document.getElementById(`${inputId.value}-0`)
      
      if (firstInput) {
        firstInput.focus()
      }
    }, 100)
  }
  
  // Start timer for TOTP
  if (props.codeType === 'totp' && props.showTimer) {
    startTimer()
  }
})

onBeforeUnmount(() => {
  stopTimer()
})
</script>

<style scoped>
.verification-input {
  @apply space-y-3;
}

.input-label {
  @apply block text-sm font-medium text-gray-700 mb-2;
}

.required-indicator {
  @apply text-red-500 ml-1;
}

.input-container {
  @apply relative;
}

.input-container.error {
  @apply ring-2 ring-red-500 ring-opacity-50 rounded-lg;
}

.input-container.focused {
  @apply ring-2 ring-blue-500 ring-opacity-50 rounded-lg;
}

/* Single Input Mode */
.single-input-wrapper {
  @apply relative flex items-center;
}

.single-input {
  @apply w-full px-4 py-3 text-xl border border-gray-300 rounded-lg 
         focus:ring-2 focus:ring-blue-500 focus:border-blue-500 
         disabled:bg-gray-100 disabled:cursor-not-allowed
         transition-all duration-200;
}

.input-addon {
  @apply absolute right-3 text-gray-400;
}

/* Digits Input Mode */
.digits-input-wrapper {
  @apply flex justify-center space-x-2;
}

.digit-input {
  @apply w-12 h-12 text-center text-xl font-mono border border-gray-300 rounded-lg
         focus:ring-2 focus:ring-blue-500 focus:border-blue-500
         disabled:bg-gray-100 disabled:cursor-not-allowed
         transition-all duration-200;
}

.digit-input.focused {
  @apply ring-2 ring-blue-500 border-blue-500;
}

.digit-input.filled {
  @apply bg-blue-50 border-blue-300;
}

.digit-input.error {
  @apply border-red-500 ring-red-500;
}

/* Helper Text */
.helper-text {
  @apply mt-1;
}

.error-text {
  @apply text-sm text-red-600 flex items-center;
}

.help-text {
  @apply text-sm text-gray-500 flex items-center;
}

/* Timer Display */
.timer-display {
  @apply flex justify-center mt-4;
}

.timer-wrapper {
  @apply text-center;
}

.timer-circle {
  @apply relative inline-block;
}

.timer-svg {
  @apply w-16 h-16 transform -rotate-90;
}

.timer-bg {
  @apply fill-none stroke-gray-200;
  stroke-width: 3;
}

.timer-progress {
  @apply fill-none stroke-blue-500 transition-all duration-1000 ease-linear;
  stroke-width: 3;
  stroke-linecap: round;
}

.timer-text {
  @apply absolute inset-0 flex items-center justify-center text-sm font-mono font-medium;
}

.timer-label {
  @apply text-xs text-gray-500 mt-2;
}

/* Alternatives */
.alternatives {
  @apply mt-6 space-y-4;
}

.alternatives-divider {
  @apply relative;
}

.alternatives-divider::before {
  @apply absolute inset-0 flex items-center;
  content: '';
}

.alternatives-divider::before {
  @apply border-t border-gray-300;
}

.divider-text {
  @apply relative bg-white px-3 text-sm text-gray-500;
}

.alternatives-buttons {
  @apply flex flex-col sm:flex-row gap-2 justify-center;
}

.alternative-btn {
  @apply px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 
         rounded-lg hover:bg-gray-50 focus:ring-2 focus:ring-blue-500 focus:border-blue-500
         disabled:opacity-50 disabled:cursor-not-allowed
         transition-all duration-200 inline-flex items-center justify-center;
}

.alternative-btn.active {
  @apply bg-blue-50 border-blue-300 text-blue-700;
}

.spinner {
  @apply animate-spin rounded-full h-4 w-4 border-b-2 border-current;
}

/* Mobile optimizations */
@media (max-width: 640px) {
  .digits-input-wrapper {
    @apply space-x-1;
  }
  
  .digit-input {
    @apply w-10 h-10 text-lg;
  }
  
  .single-input {
    @apply text-lg py-4;
  }
  
  .alternatives-buttons {
    @apply flex-col;
  }
}

/* High contrast mode */
@media (prefers-contrast: high) {
  .digit-input,
  .single-input {
    @apply border-2;
  }
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {
  .timer-progress {
    @apply transition-none;
  }
  
  .digit-input,
  .single-input,
  .alternative-btn {
    @apply transition-none;
  }
}
</style>