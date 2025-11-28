import { useState, useCallback } from 'react';
import type {
  LeaveRequest,
  LeaveBalance,
  LeaveApproval,
  LeaveStatistics,
  LeaveRequestFormData,
  LeaveStatus,
  LeaveType,
} from '@/types/leave';

// Mock data
const mockLeaveRequests: LeaveRequest[] = [
  {
    id: '1',
    employee_id: '1',
    employee_name: 'Ahmad Rizki',
    employee_department: 'IT',
    type: 'annual',
    status: 'approved',
    start_date: '2024-01-20',
    end_date: '2024-01-22',
    duration_type: 'full_day',
    total_days: 3,
    reason: 'Liburan keluarga',
    approved_by: '5',
    approved_by_name: 'Manager IT',
    approved_at: '2024-01-15T10:30:00',
    created_at: '2024-01-10T08:00:00',
    updated_at: '2024-01-15T10:30:00',
  },
  {
    id: '2',
    employee_id: '2',
    employee_name: 'Siti Nurhaliza',
    employee_department: 'HR',
    type: 'sick',
    status: 'approved',
    start_date: '2024-01-18',
    end_date: '2024-01-18',
    duration_type: 'full_day',
    total_days: 1,
    reason: 'Demam',
    attachment_url: '/uploads/surat-dokter.pdf',
    approved_by: '6',
    approved_by_name: 'Manager HR',
    approved_at: '2024-01-18T09:00:00',
    created_at: '2024-01-18T07:30:00',
    updated_at: '2024-01-18T09:00:00',
  },
  {
    id: '3',
    employee_id: '3',
    employee_name: 'Budi Santoso',
    employee_department: 'Finance',
    type: 'annual',
    status: 'pending',
    start_date: '2024-02-01',
    end_date: '2024-02-05',
    duration_type: 'full_day',
    total_days: 5,
    reason: 'Pernikahan adik',
    emergency_contact: 'Ibu Budi',
    emergency_phone: '08123456789',
    created_at: '2024-01-25T14:00:00',
    updated_at: '2024-01-25T14:00:00',
  },
  {
    id: '4',
    employee_id: '4',
    employee_name: 'Dewi Anggraini',
    employee_department: 'Marketing',
    type: 'maternity',
    status: 'approved',
    start_date: '2024-02-15',
    end_date: '2024-05-15',
    duration_type: 'full_day',
    total_days: 90,
    reason: 'Cuti melahirkan',
    attachment_url: '/uploads/surat-keterangan.pdf',
    approved_by: '7',
    approved_by_name: 'Director',
    approved_at: '2024-02-01T11:00:00',
    created_at: '2024-01-20T10:00:00',
    updated_at: '2024-02-01T11:00:00',
  },
  {
    id: '5',
    employee_id: '5',
    employee_name: 'Eko Prasetyo',
    employee_department: 'Operations',
    type: 'annual',
    status: 'rejected',
    start_date: '2024-01-25',
    end_date: '2024-01-30',
    duration_type: 'full_day',
    total_days: 6,
    reason: 'Liburan',
    rejection_reason: 'Periode sibuk, mohon ajukan di waktu lain',
    approved_by: '8',
    approved_by_name: 'Manager Operations',
    approved_at: '2024-01-23T15:00:00',
    created_at: '2024-01-22T09:00:00',
    updated_at: '2024-01-23T15:00:00',
  },
];

const mockLeaveBalance: LeaveBalance = {
  id: '1',
  employee_id: '1',
  employee_name: 'Ahmad Rizki',
  year: 2024,
  annual_total: 12,
  annual_used: 3,
  annual_remaining: 9,
  sick_total: 12,
  sick_used: 1,
  sick_remaining: 11,
  special_total: 5,
  special_used: 0,
  special_remaining: 5,
  carry_forward: 2,
  carry_forward_expiry: '2024-03-31',
  updated_at: '2024-01-15T10:30:00',
};

export function useLeave() {
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [leaveRequests, setLeaveRequests] = useState<LeaveRequest[]>(mockLeaveRequests);
  const [leaveBalance, setLeaveBalance] = useState<LeaveBalance>(mockLeaveBalance);
  const [pendingApprovals] = useState<LeaveApproval[]>([]);

  // Fetch leave requests
  const fetchLeaveRequests = useCallback(async (filters?: {
    status?: LeaveStatus;
    type?: LeaveType;
    employee_id?: string;
    start_date?: string;
    end_date?: string;
  }) => {
    setIsLoading(true);
    setError(null);

    try {
      await new Promise((resolve) => setTimeout(resolve, 500));

      let filtered = [...mockLeaveRequests];

      if (filters?.status) {
        filtered = filtered.filter((r) => r.status === filters.status);
      }
      if (filters?.type) {
        filtered = filtered.filter((r) => r.type === filters.type);
      }
      if (filters?.employee_id) {
        filtered = filtered.filter((r) => r.employee_id === filters.employee_id);
      }

      setLeaveRequests(filtered);
    } catch (err) {
      setError('Gagal memuat data cuti');
      console.error('Error fetching leave requests:', err);
    } finally {
      setIsLoading(false);
    }
  }, []);

  // Fetch leave balance for current user
  const fetchLeaveBalance = useCallback(async () => {
    setIsLoading(true);
    try {
      await new Promise((resolve) => setTimeout(resolve, 300));
      setLeaveBalance(mockLeaveBalance);
    } catch (err) {
      setError('Gagal memuat saldo cuti');
    } finally {
      setIsLoading(false);
    }
  }, []);

  // Create leave request
  const createLeaveRequest = useCallback(async (data: LeaveRequestFormData) => {
    setIsLoading(true);
    try {
      await new Promise((resolve) => setTimeout(resolve, 500));

      const newRequest: LeaveRequest = {
        id: `new-${Date.now()}`,
        employee_id: '1',
        employee_name: 'Current User',
        employee_department: 'IT',
        type: data.type,
        status: 'pending',
        start_date: data.start_date,
        end_date: data.end_date,
        duration_type: data.duration_type,
        total_days: calculateDays(data.start_date, data.end_date, data.duration_type),
        reason: data.reason,
        emergency_contact: data.emergency_contact,
        emergency_phone: data.emergency_phone,
        created_at: new Date().toISOString(),
        updated_at: new Date().toISOString(),
      };

      setLeaveRequests((prev) => [newRequest, ...prev]);
      return newRequest;
    } catch (err) {
      setError('Gagal mengajukan cuti');
      throw err;
    } finally {
      setIsLoading(false);
    }
  }, []);

  // Approve leave request
  const approveLeaveRequest = useCallback(async (id: string, _notes?: string) => {
    setIsLoading(true);
    try {
      await new Promise((resolve) => setTimeout(resolve, 300));

      setLeaveRequests((prev) =>
        prev.map((req) =>
          req.id === id
            ? {
                ...req,
                status: 'approved' as LeaveStatus,
                approved_by: 'current-user',
                approved_by_name: 'Current User',
                approved_at: new Date().toISOString(),
                updated_at: new Date().toISOString(),
              }
            : req
        )
      );
    } catch (err) {
      setError('Gagal menyetujui cuti');
      throw err;
    } finally {
      setIsLoading(false);
    }
  }, []);

  // Reject leave request
  const rejectLeaveRequest = useCallback(async (id: string, reason: string) => {
    setIsLoading(true);
    try {
      await new Promise((resolve) => setTimeout(resolve, 300));

      setLeaveRequests((prev) =>
        prev.map((req) =>
          req.id === id
            ? {
                ...req,
                status: 'rejected' as LeaveStatus,
                rejection_reason: reason,
                approved_by: 'current-user',
                approved_by_name: 'Current User',
                approved_at: new Date().toISOString(),
                updated_at: new Date().toISOString(),
              }
            : req
        )
      );
    } catch (err) {
      setError('Gagal menolak cuti');
      throw err;
    } finally {
      setIsLoading(false);
    }
  }, []);

  // Cancel leave request
  const cancelLeaveRequest = useCallback(async (id: string) => {
    setIsLoading(true);
    try {
      await new Promise((resolve) => setTimeout(resolve, 300));

      setLeaveRequests((prev) =>
        prev.map((req) =>
          req.id === id
            ? { ...req, status: 'cancelled' as LeaveStatus, updated_at: new Date().toISOString() }
            : req
        )
      );
    } catch (err) {
      setError('Gagal membatalkan cuti');
      throw err;
    } finally {
      setIsLoading(false);
    }
  }, []);

  // Get statistics
  const getStatistics = useCallback(async (): Promise<LeaveStatistics> => {
    await new Promise((resolve) => setTimeout(resolve, 300));

    const pending = leaveRequests.filter((r) => r.status === 'pending').length;
    const approved = leaveRequests.filter((r) => r.status === 'approved').length;
    const rejected = leaveRequests.filter((r) => r.status === 'rejected').length;

    return {
      total_requests: leaveRequests.length,
      pending_requests: pending,
      approved_requests: approved,
      rejected_requests: rejected,
      average_days_per_request: leaveRequests.reduce((acc, r) => acc + r.total_days, 0) / leaveRequests.length,
      most_common_type: 'annual',
      employees_on_leave_today: 2,
    };
  }, [leaveRequests]);

  return {
    // State
    isLoading,
    error,
    leaveRequests,
    leaveBalance,
    pendingApprovals,

    // Actions
    fetchLeaveRequests,
    fetchLeaveBalance,
    createLeaveRequest,
    approveLeaveRequest,
    rejectLeaveRequest,
    cancelLeaveRequest,
    getStatistics,
    clearError: () => setError(null),
  };
}

// Helper function to calculate days
function calculateDays(startDate: string, endDate: string, durationType: string): number {
  const start = new Date(startDate);
  const end = new Date(endDate);
  const diffTime = Math.abs(end.getTime() - start.getTime());
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;

  if (durationType === 'half_day_am' || durationType === 'half_day_pm') {
    return 0.5;
  }

  return diffDays;
}
