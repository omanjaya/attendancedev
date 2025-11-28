import apiClient from './client';
import type { DashboardData, ActivityItem } from '@/types';

const ENDPOINTS = {
  dashboard: '/reports/dashboard',
} as const;

// Get dashboard data
export async function getDashboardData(): Promise<DashboardData> {
  const response = await apiClient.get<{ data: DashboardData }>(ENDPOINTS.dashboard);
  return response.data.data;
}

// Mock dashboard data for development (when API is not available)
export function getMockDashboardData(): DashboardData {
  const now = new Date();
  const today = now.toISOString().split('T')[0];

  return {
    summary: {
      attendance: {
        total_employees: 125,
        present: 118,
        late: 5,
        absent: 2,
        on_leave: 5,
        attendance_rate: 94.4,
      },
      employees: {
        total: 125,
        active: 120,
        inactive: 3,
        on_leave: 2,
        by_department: {
          'IT': 25,
          'HR': 10,
          'Finance': 15,
          'Operations': 45,
          'Marketing': 20,
          'Sales': 10,
        },
      },
      pending_leaves: 3,
      upcoming_holidays: 2,
    },
    recent_activity: [
      {
        id: 1,
        type: 'check_in',
        employee_id: 1,
        employee_name: 'Ahmad Rizki',
        description: 'Check-in',
        timestamp: `${today}T08:02:00`,
      },
      {
        id: 2,
        type: 'check_in',
        employee_id: 2,
        employee_name: 'Siti Nurhaliza',
        description: 'Check-in',
        timestamp: `${today}T08:15:00`,
      },
      {
        id: 3,
        type: 'check_in',
        employee_id: 3,
        employee_name: 'Budi Santoso',
        description: 'Check-in (Terlambat 45 menit)',
        timestamp: `${today}T08:45:00`,
      },
      {
        id: 4,
        type: 'leave_request',
        employee_id: 4,
        employee_name: 'Dewi Anggraini',
        description: 'Mengajukan cuti tahunan',
        timestamp: `${today}T09:00:00`,
      },
      {
        id: 5,
        type: 'check_out',
        employee_id: 5,
        employee_name: 'Eko Prasetyo',
        description: 'Check-out',
        timestamp: `${today}T17:05:00`,
      },
    ] as ActivityItem[],
    attendance_trends: Array.from({ length: 7 }, (_, i) => {
      const date = new Date(now);
      date.setDate(date.getDate() - (6 - i));
      return {
        date: date.toISOString().split('T')[0],
        present: 110 + Math.floor(Math.random() * 10),
        late: 3 + Math.floor(Math.random() * 5),
        absent: 1 + Math.floor(Math.random() * 3),
        on_leave: 2 + Math.floor(Math.random() * 4),
      };
    }),
    today_schedule: {
      shift_name: 'Shift Pagi',
      start_time: '08:00',
      end_time: '17:00',
    },
  };
}
