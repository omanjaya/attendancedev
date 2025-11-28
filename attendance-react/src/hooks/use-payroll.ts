import { useState, useCallback } from 'react';
import type {
  PayrollPeriod,
  PayrollEmployee,
  PayrollSummary,
  PayrollStatistics,
  PayrollStatus,
} from '@/types/payroll';

// Mock data
const mockPayrollPeriods: PayrollPeriod[] = [
  {
    id: '1',
    name: 'Januari 2024',
    type: 'monthly',
    start_date: '2024-01-01',
    end_date: '2024-01-31',
    pay_date: '2024-02-01',
    status: 'paid',
    total_employees: 45,
    total_gross: 450000000,
    total_deductions: 67500000,
    total_net: 382500000,
    created_at: '2024-01-25T00:00:00',
    updated_at: '2024-02-01T10:00:00',
    approved_by: 'Director',
    approved_at: '2024-01-30T14:00:00',
  },
  {
    id: '2',
    name: 'Februari 2024',
    type: 'monthly',
    start_date: '2024-02-01',
    end_date: '2024-02-29',
    pay_date: '2024-03-01',
    status: 'approved',
    total_employees: 46,
    total_gross: 460000000,
    total_deductions: 69000000,
    total_net: 391000000,
    created_at: '2024-02-25T00:00:00',
    updated_at: '2024-02-28T15:00:00',
    approved_by: 'Director',
    approved_at: '2024-02-28T15:00:00',
  },
  {
    id: '3',
    name: 'Maret 2024',
    type: 'monthly',
    start_date: '2024-03-01',
    end_date: '2024-03-31',
    pay_date: '2024-04-01',
    status: 'calculated',
    total_employees: 46,
    total_gross: 465000000,
    total_deductions: 69750000,
    total_net: 395250000,
    created_at: '2024-03-25T00:00:00',
    updated_at: '2024-03-28T10:00:00',
  },
  {
    id: '4',
    name: 'April 2024',
    type: 'monthly',
    start_date: '2024-04-01',
    end_date: '2024-04-30',
    pay_date: '2024-05-01',
    status: 'draft',
    total_employees: 0,
    total_gross: 0,
    total_deductions: 0,
    total_net: 0,
    created_at: '2024-03-30T00:00:00',
    updated_at: '2024-03-30T00:00:00',
  },
];

const mockPayrollEmployees: PayrollEmployee[] = [
  {
    id: '1',
    payroll_period_id: '3',
    employee_id: '1',
    employee_name: 'Ahmad Rizki',
    employee_nip: '198501012010011001',
    department: 'IT',
    position: 'Senior Developer',
    working_days: 22,
    present_days: 21,
    absent_days: 1,
    late_days: 2,
    overtime_hours: 10,
    base_salary: 15000000,
    position_allowance: 2000000,
    transport_allowance: 1000000,
    meal_allowance: 500000,
    overtime_pay: 750000,
    bonus: 0,
    other_allowances: 0,
    gross_salary: 19250000,
    tax: 2887500,
    bpjs_kesehatan: 192500,
    bpjs_ketenagakerjaan: 385000,
    loan_deduction: 0,
    absence_deduction: 500000,
    late_deduction: 50000,
    other_deductions: 0,
    total_deductions: 4015000,
    net_salary: 15235000,
    status: 'calculated',
    created_at: '2024-03-25T00:00:00',
    updated_at: '2024-03-28T10:00:00',
  },
  {
    id: '2',
    payroll_period_id: '3',
    employee_id: '2',
    employee_name: 'Siti Nurhaliza',
    employee_nip: '198702032011012002',
    department: 'HR',
    position: 'HR Manager',
    working_days: 22,
    present_days: 22,
    absent_days: 0,
    late_days: 0,
    overtime_hours: 5,
    base_salary: 12000000,
    position_allowance: 1500000,
    transport_allowance: 1000000,
    meal_allowance: 500000,
    overtime_pay: 375000,
    bonus: 500000,
    other_allowances: 0,
    gross_salary: 15875000,
    tax: 2381250,
    bpjs_kesehatan: 158750,
    bpjs_ketenagakerjaan: 317500,
    loan_deduction: 0,
    absence_deduction: 0,
    late_deduction: 0,
    other_deductions: 0,
    total_deductions: 2857500,
    net_salary: 13017500,
    status: 'calculated',
    created_at: '2024-03-25T00:00:00',
    updated_at: '2024-03-28T10:00:00',
  },
];

export function usePayroll() {
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [payrollPeriods, setPayrollPeriods] = useState<PayrollPeriod[]>(mockPayrollPeriods);
  const [payrollEmployees, setPayrollEmployees] = useState<PayrollEmployee[]>(mockPayrollEmployees);
  const [selectedPeriod, setSelectedPeriod] = useState<PayrollPeriod | null>(null);

  // Fetch payroll periods
  const fetchPayrollPeriods = useCallback(async () => {
    setIsLoading(true);
    setError(null);
    try {
      await new Promise((resolve) => setTimeout(resolve, 500));
      setPayrollPeriods(mockPayrollPeriods);
    } catch (err) {
      setError('Gagal memuat data payroll');
    } finally {
      setIsLoading(false);
    }
  }, []);

  // Fetch employees for a period
  const fetchPayrollEmployees = useCallback(async (periodId: string) => {
    setIsLoading(true);
    try {
      await new Promise((resolve) => setTimeout(resolve, 500));
      const filtered = mockPayrollEmployees.filter((e) => e.payroll_period_id === periodId);
      setPayrollEmployees(filtered);
      setSelectedPeriod(payrollPeriods.find((p) => p.id === periodId) || null);
    } catch (err) {
      setError('Gagal memuat data karyawan');
    } finally {
      setIsLoading(false);
    }
  }, [payrollPeriods]);

  // Calculate payroll
  const calculatePayroll = useCallback(async (periodId: string) => {
    setIsLoading(true);
    try {
      await new Promise((resolve) => setTimeout(resolve, 1000));
      setPayrollPeriods((prev) =>
        prev.map((p) =>
          p.id === periodId
            ? { ...p, status: 'calculated' as PayrollStatus, updated_at: new Date().toISOString() }
            : p
        )
      );
    } catch (err) {
      setError('Gagal menghitung payroll');
      throw err;
    } finally {
      setIsLoading(false);
    }
  }, []);

  // Approve payroll
  const approvePayroll = useCallback(async (periodId: string) => {
    setIsLoading(true);
    try {
      await new Promise((resolve) => setTimeout(resolve, 500));
      setPayrollPeriods((prev) =>
        prev.map((p) =>
          p.id === periodId
            ? {
                ...p,
                status: 'approved' as PayrollStatus,
                approved_by: 'Current User',
                approved_at: new Date().toISOString(),
                updated_at: new Date().toISOString(),
              }
            : p
        )
      );
    } catch (err) {
      setError('Gagal menyetujui payroll');
      throw err;
    } finally {
      setIsLoading(false);
    }
  }, []);

  // Mark as paid
  const markAsPaid = useCallback(async (periodId: string) => {
    setIsLoading(true);
    try {
      await new Promise((resolve) => setTimeout(resolve, 500));
      setPayrollPeriods((prev) =>
        prev.map((p) =>
          p.id === periodId
            ? { ...p, status: 'paid' as PayrollStatus, updated_at: new Date().toISOString() }
            : p
        )
      );
    } catch (err) {
      setError('Gagal mengupdate status pembayaran');
      throw err;
    } finally {
      setIsLoading(false);
    }
  }, []);

  // Get summary
  const getSummary = useCallback(async (periodId: string): Promise<PayrollSummary> => {
    const period = payrollPeriods.find((p) => p.id === periodId);
    const employees = mockPayrollEmployees.filter((e) => e.payroll_period_id === periodId);

    return {
      total_employees: period?.total_employees || employees.length,
      total_gross_salary: period?.total_gross || employees.reduce((sum, e) => sum + e.gross_salary, 0),
      total_deductions: period?.total_deductions || employees.reduce((sum, e) => sum + e.total_deductions, 0),
      total_net_salary: period?.total_net || employees.reduce((sum, e) => sum + e.net_salary, 0),
      average_salary: employees.length > 0 ? employees.reduce((sum, e) => sum + e.net_salary, 0) / employees.length : 0,
      highest_salary: Math.max(...employees.map((e) => e.net_salary), 0),
      lowest_salary: Math.min(...employees.map((e) => e.net_salary), 0),
      total_overtime_pay: employees.reduce((sum, e) => sum + e.overtime_pay, 0),
      total_bonus: employees.reduce((sum, e) => sum + e.bonus, 0),
      total_tax: employees.reduce((sum, e) => sum + e.tax, 0),
      total_bpjs: employees.reduce((sum, e) => sum + e.bpjs_kesehatan + e.bpjs_ketenagakerjaan, 0),
    };
  }, [payrollPeriods]);

  // Get statistics
  const getStatistics = useCallback(async (): Promise<PayrollStatistics> => {
    const paidPeriods = payrollPeriods.filter((p) => p.status === 'paid');
    const totalPaid = paidPeriods.reduce((sum, p) => sum + p.total_net, 0);

    return {
      total_payrolls_this_year: paidPeriods.length,
      total_paid_this_year: totalPaid,
      average_monthly_payroll: paidPeriods.length > 0 ? totalPaid / paidPeriods.length : 0,
      pending_approvals: payrollPeriods.filter((p) => p.status === 'calculated').length,
      upcoming_pay_date: payrollPeriods.find((p) => p.status === 'approved')?.pay_date,
      year_to_date_tax: 15000000,
      year_to_date_bpjs: 5000000,
    };
  }, [payrollPeriods]);

  return {
    // State
    isLoading,
    error,
    payrollPeriods,
    payrollEmployees,
    selectedPeriod,

    // Actions
    fetchPayrollPeriods,
    fetchPayrollEmployees,
    calculatePayroll,
    approvePayroll,
    markAsPaid,
    getSummary,
    getStatistics,
    clearError: () => setError(null),
  };
}
