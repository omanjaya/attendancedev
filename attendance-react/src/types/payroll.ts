// Payroll Types

export type PayrollStatus = 'draft' | 'calculated' | 'approved' | 'paid' | 'cancelled';

export type PayrollPeriodType = 'monthly' | 'weekly' | 'biweekly';

export interface PayrollPeriod {
  id: string;
  name: string;
  type: PayrollPeriodType;
  start_date: string;
  end_date: string;
  pay_date: string;
  status: PayrollStatus;
  total_employees: number;
  total_gross: number;
  total_deductions: number;
  total_net: number;
  created_at: string;
  updated_at: string;
  approved_by?: string;
  approved_at?: string;
}

export interface PayrollEmployee {
  id: string;
  payroll_period_id: string;
  employee_id: string;
  employee_name: string;
  employee_nip: string;
  department: string;
  position: string;

  // Attendance summary
  working_days: number;
  present_days: number;
  absent_days: number;
  late_days: number;
  overtime_hours: number;

  // Earnings
  base_salary: number;
  position_allowance: number;
  transport_allowance: number;
  meal_allowance: number;
  overtime_pay: number;
  bonus: number;
  other_allowances: number;
  gross_salary: number;

  // Deductions
  tax: number;
  bpjs_kesehatan: number;
  bpjs_ketenagakerjaan: number;
  loan_deduction: number;
  absence_deduction: number;
  late_deduction: number;
  other_deductions: number;
  total_deductions: number;

  // Net
  net_salary: number;

  // Status
  status: PayrollStatus;
  notes?: string;

  created_at: string;
  updated_at: string;
}

export interface PayrollSummary {
  total_employees: number;
  total_gross_salary: number;
  total_deductions: number;
  total_net_salary: number;
  average_salary: number;
  highest_salary: number;
  lowest_salary: number;
  total_overtime_pay: number;
  total_bonus: number;
  total_tax: number;
  total_bpjs: number;
}

export interface TaxBracket {
  id: string;
  name: string;
  min_income: number;
  max_income: number | null;
  rate: number;
  is_active: boolean;
}

export interface PayrollConfig {
  overtime_rate_weekday: number;
  overtime_rate_weekend: number;
  overtime_rate_holiday: number;
  late_deduction_per_minute: number;
  absence_deduction_per_day: number;
  bpjs_kesehatan_rate: number;
  bpjs_ketenagakerjaan_rate: number;
  tax_brackets: TaxBracket[];
}

// Statistics
export interface PayrollStatistics {
  total_payrolls_this_year: number;
  total_paid_this_year: number;
  average_monthly_payroll: number;
  pending_approvals: number;
  upcoming_pay_date?: string;
  year_to_date_tax: number;
  year_to_date_bpjs: number;
}

// Form types
export interface PayrollCalculateFormData {
  employee_ids?: string[];
  include_overtime: boolean;
  include_bonus: boolean;
  bonus_amount?: number;
  notes?: string;
}

// Labels
export const payrollStatusLabels: Record<PayrollStatus, string> = {
  draft: 'Draft',
  calculated: 'Terhitung',
  approved: 'Disetujui',
  paid: 'Dibayar',
  cancelled: 'Dibatalkan',
};

export const payrollStatusColors: Record<PayrollStatus, string> = {
  draft: '#9CA3AF',
  calculated: '#3B82F6',
  approved: '#10B981',
  paid: '#8B5CF6',
  cancelled: '#EF4444',
};
