import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { nextTick, ref } from 'vue'
import AttendanceWidget from '@/attendance/AttendanceWidget.vue'
import type { AttendanceStatus } from '@/types/attendance'

// Mock the useAttendance composable
const mockFetchAttendanceStatus = vi.fn()
const mockAttendanceStatus = ref<AttendanceStatus | null>(null)
const mockLoading = ref(false)
const mockProcessing = ref(false)

vi.mock('@/composables/useAttendance', () => ({
  useAttendance: () => ({
    attendanceStatus: mockAttendanceStatus,
    loading: mockLoading,
    processing: mockProcessing,
    fetchAttendanceStatus: mockFetchAttendanceStatus,
  }),
}))

describe('AttendanceWidget.vue', () => {
  let wrapper: any

  beforeEach(() => {
    vi.clearAllMocks()
    mockAttendanceStatus.value = null
    mockLoading.value = false
    mockProcessing.value = false
  })

  const createWrapper = () => {
    return mount(AttendanceWidget, {
      global: {
        stubs: {
          // Stub any child components if needed
        },
      },
    })
  }

  describe('Component Initialization', () => {
    it('should render correctly', () => {
      wrapper = createWrapper()
      expect(wrapper.exists()).toBe(true)
      expect(wrapper.find('.attendance-widget').exists()).toBe(true)
    })

    it('should call fetchAttendanceStatus on mount', () => {
      wrapper = createWrapper()
      expect(mockFetchAttendanceStatus).toHaveBeenCalledOnce()
    })

    it('should display the correct title', () => {
      wrapper = createWrapper()
      expect(wrapper.text()).toContain('Attendance Status')
    })
  })

  describe('Loading State', () => {
    it('should show loading spinner when loading is true', async () => {
      mockLoading.value = true
      wrapper = createWrapper()
      await nextTick()

      const spinner = wrapper.find('.animate-spin')
      expect(spinner.exists()).toBe(true)
      expect(wrapper.text()).toContain('Loading...')
    })

    it('should hide loading spinner when loading is false', async () => {
      mockLoading.value = false
      wrapper = createWrapper()
      await nextTick()

      const spinner = wrapper.find('.animate-spin')
      expect(spinner.exists()).toBe(false)
    })
  })

  describe('Attendance Status Display', () => {
    beforeEach(() => {
      mockLoading.value = false
    })

    it('should display attendance status when available', async () => {
      const mockStatus: AttendanceStatus = {
        status: 'present',
        check_in_time: '08:30:00',
        check_out_time: null,
        working_hours: 4.5,
        date: '2025-07-14',
        employee_id: 'test-employee',
      }

      mockAttendanceStatus.value = mockStatus
      wrapper = createWrapper()
      await nextTick()

      expect(wrapper.text()).toContain('present')
      expect(wrapper.text()).toContain('08:30:00')
      expect(wrapper.text()).toContain('4.5h')
      expect(wrapper.text()).toContain('Not checked out')
    })

    it('should show "Not checked in" when check_in_time is null', async () => {
      const mockStatus: AttendanceStatus = {
        status: 'absent',
        check_in_time: null,
        check_out_time: null,
        working_hours: 0,
        date: '2025-07-14',
        employee_id: 'test-employee',
      }

      mockAttendanceStatus.value = mockStatus
      wrapper = createWrapper()
      await nextTick()

      expect(wrapper.text()).toContain('Not checked in')
    })

    it('should display both check-in and check-out times when both are present', async () => {
      const mockStatus: AttendanceStatus = {
        status: 'present',
        check_in_time: '08:30:00',
        check_out_time: '17:00:00',
        working_hours: 8.5,
        date: '2025-07-14',
        employee_id: 'test-employee',
      }

      mockAttendanceStatus.value = mockStatus
      wrapper = createWrapper()
      await nextTick()

      expect(wrapper.text()).toContain('08:30:00')
      expect(wrapper.text()).toContain('17:00:00')
      expect(wrapper.text()).toContain('8.5h')
    })
  })

  describe('Check-in Button', () => {
    beforeEach(() => {
      mockLoading.value = false
    })

    it('should show check-in button when not checked in', async () => {
      const mockStatus: AttendanceStatus = {
        status: 'absent',
        check_in_time: null,
        check_out_time: null,
        working_hours: 0,
        date: '2025-07-14',
        employee_id: 'test-employee',
      }

      mockAttendanceStatus.value = mockStatus
      wrapper = createWrapper()
      await nextTick()

      const checkInButton = wrapper.find('button')
      expect(checkInButton.exists()).toBe(true)
      expect(checkInButton.text()).toBe('Check In')
      expect(checkInButton.classes()).toContain('bg-primary')
    })

    it('should call startCheckIn when check-in button is clicked', async () => {
      const mockStatus: AttendanceStatus = {
        status: 'absent',
        check_in_time: null,
        check_out_time: null,
        working_hours: 0,
        date: '2025-07-14',
        employee_id: 'test-employee',
      }

      mockAttendanceStatus.value = mockStatus
      wrapper = createWrapper()
      await nextTick()

      const consoleSpy = vi.spyOn(console, 'log').mockImplementation(() => {})
      const checkInButton = wrapper.find('button')
      await checkInButton.trigger('click')

      expect(consoleSpy).toHaveBeenCalledWith('Starting check-in process...')
      consoleSpy.mockRestore()
    })

    it('should disable check-in button when processing', async () => {
      const mockStatus: AttendanceStatus = {
        status: 'absent',
        check_in_time: null,
        check_out_time: null,
        working_hours: 0,
        date: '2025-07-14',
        employee_id: 'test-employee',
      }

      mockAttendanceStatus.value = mockStatus
      mockProcessing.value = true
      wrapper = createWrapper()
      await nextTick()

      const checkInButton = wrapper.find('button')
      expect(checkInButton.attributes('disabled')).toBeDefined()
    })
  })

  describe('Check-out Button', () => {
    beforeEach(() => {
      mockLoading.value = false
    })

    it('should show check-out button when checked in but not checked out', async () => {
      const mockStatus: AttendanceStatus = {
        status: 'present',
        check_in_time: '08:30:00',
        check_out_time: null,
        working_hours: 4.5,
        date: '2025-07-14',
        employee_id: 'test-employee',
      }

      mockAttendanceStatus.value = mockStatus
      wrapper = createWrapper()
      await nextTick()

      const checkOutButton = wrapper.find('button')
      expect(checkOutButton.exists()).toBe(true)
      expect(checkOutButton.text()).toBe('Check Out')
      expect(checkOutButton.classes()).toContain('bg-destructive')
    })

    it('should call startCheckOut when check-out button is clicked', async () => {
      const mockStatus: AttendanceStatus = {
        status: 'present',
        check_in_time: '08:30:00',
        check_out_time: null,
        working_hours: 4.5,
        date: '2025-07-14',
        employee_id: 'test-employee',
      }

      mockAttendanceStatus.value = mockStatus
      wrapper = createWrapper()
      await nextTick()

      const consoleSpy = vi.spyOn(console, 'log').mockImplementation(() => {})
      const checkOutButton = wrapper.find('button')
      await checkOutButton.trigger('click')

      expect(consoleSpy).toHaveBeenCalledWith('Starting check-out process...')
      consoleSpy.mockRestore()
    })

    it('should disable check-out button when processing', async () => {
      const mockStatus: AttendanceStatus = {
        status: 'present',
        check_in_time: '08:30:00',
        check_out_time: null,
        working_hours: 4.5,
        date: '2025-07-14',
        employee_id: 'test-employee',
      }

      mockAttendanceStatus.value = mockStatus
      mockProcessing.value = true
      wrapper = createWrapper()
      await nextTick()

      const checkOutButton = wrapper.find('button')
      expect(checkOutButton.attributes('disabled')).toBeDefined()
    })

    it('should not show any button when both check-in and check-out are completed', async () => {
      const mockStatus: AttendanceStatus = {
        status: 'present',
        check_in_time: '08:30:00',
        check_out_time: '17:00:00',
        working_hours: 8.5,
        date: '2025-07-14',
        employee_id: 'test-employee',
      }

      mockAttendanceStatus.value = mockStatus
      wrapper = createWrapper()
      await nextTick()

      const buttons = wrapper.findAll('button')
      expect(buttons.length).toBe(0)
    })
  })

  describe('Responsive Layout', () => {
    it('should have responsive grid classes', () => {
      wrapper = createWrapper()
      const grid = wrapper.find('.grid')
      expect(grid.classes()).toContain('grid-cols-1')
      expect(grid.classes()).toContain('md:grid-cols-2')
    })
  })

  describe('Accessibility', () => {
    beforeEach(() => {
      mockLoading.value = false
    })

    it('should have proper loading state accessibility', async () => {
      mockLoading.value = true
      wrapper = createWrapper()
      await nextTick()

      const loadingSpinner = wrapper.find('[role="status"]')
      expect(loadingSpinner.exists()).toBe(true)

      const srText = wrapper.find('.sr-only')
      expect(srText.text()).toBe('Loading...')
    })

    it('should have semantic button elements', async () => {
      const mockStatus: AttendanceStatus = {
        status: 'absent',
        check_in_time: null,
        check_out_time: null,
        working_hours: 0,
        date: '2025-07-14',
        employee_id: 'test-employee',
      }

      mockAttendanceStatus.value = mockStatus
      wrapper = createWrapper()
      await nextTick()

      const button = wrapper.find('button')
      expect(button.exists()).toBe(true)
      expect(button.element.tagName).toBe('BUTTON')
    })

    it('should have proper focus management classes', async () => {
      const mockStatus: AttendanceStatus = {
        status: 'absent',
        check_in_time: null,
        check_out_time: null,
        working_hours: 0,
        date: '2025-07-14',
        employee_id: 'test-employee',
      }

      mockAttendanceStatus.value = mockStatus
      wrapper = createWrapper()
      await nextTick()

      const button = wrapper.find('button')
      expect(button.classes()).toContain('focus-visible:outline-none')
      expect(button.classes()).toContain('focus-visible:ring-2')
    })
  })

  describe('Widget Styling', () => {
    it('should have proper card styling classes', () => {
      wrapper = createWrapper()

      const card = wrapper.find('.rounded-lg.border')
      expect(card.exists()).toBe(true)
      expect(card.classes()).toContain('bg-card')
      expect(card.classes()).toContain('shadow-sm')
    })

    it('should center the widget with max width', () => {
      wrapper = createWrapper()

      const widget = wrapper.find('.attendance-widget')
      expect(widget.exists()).toBe(true)

      // Check if CSS is applied (we can't directly test CSS, but we can verify the class exists)
      expect(wrapper.html()).toContain('attendance-widget')
    })
  })
})
