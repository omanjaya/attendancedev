// Shared constants between frontend and backend

export const ATTENDANCE_STATUS = {
  PRESENT: 'present',
  LATE: 'late',
  ABSENT: 'absent',
  ON_LEAVE: 'on_leave',
} as const;

export const USER_ROLES = {
  ADMIN: 'admin',
  MANAGER: 'manager',
  EMPLOYEE: 'employee',
} as const;

export const DAYS_OF_WEEK = {
  SUNDAY: 0,
  MONDAY: 1,
  TUESDAY: 2,
  WEDNESDAY: 3,
  THURSDAY: 4,
  FRIDAY: 5,
  SATURDAY: 6,
} as const;

export const API_VERSION = 'v1' as const;

export const API_ENDPOINTS = {
  AUTH: {
    LOGIN: '/api/auth/login',
    LOGOUT: '/api/auth/logout',
    REGISTER: '/api/auth/register',
    ME: '/api/auth/me',
  },
  EMPLOYEES: {
    LIST: '/api/employees',
    SHOW: (id: number) => `/api/employees/${id}`,
    CREATE: '/api/employees',
    UPDATE: (id: number) => `/api/employees/${id}`,
    DELETE: (id: number) => `/api/employees/${id}`,
  },
  ATTENDANCE: {
    LIST: '/api/attendance',
    CHECK_IN: '/api/attendance/check-in',
    CHECK_OUT: '/api/attendance/check-out',
  },
  SCHEDULES: {
    LIST: '/api/schedules',
    SHOW: (id: number) => `/api/schedules/${id}`,
  },
} as const;
