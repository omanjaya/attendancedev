// Holiday Types

export type HolidayType = 'public_holiday' | 'religious_holiday' | 'school_holiday' | 'substitute_holiday';

export type HolidayStatus = 'active' | 'cancelled' | 'moved';

export interface Holiday {
  id: string;
  name: string;
  description?: string;
  date: string;
  end_date?: string;
  type: HolidayType;
  status: HolidayStatus;
  is_recurring: boolean;
  recurring_pattern?: RecurringPattern;
  affected_roles?: string[];
  source?: 'manual' | 'auto_generated' | 'api';
  color?: string;
  is_paid: boolean;
  metadata?: {
    notes?: string;
    created_by?: string;
  };
  created_at: string;
  updated_at: string;
}

export interface RecurringPattern {
  type: 'yearly' | 'relative' | 'islamic' | 'lunar';
  month?: number;
  week?: number;
  day?: number;
}

export interface HolidayFormData {
  name: string;
  description?: string;
  date: string;
  end_date?: string;
  type: HolidayType;
  is_recurring: boolean;
  recurring_pattern?: RecurringPattern;
  affected_roles?: string[];
  color?: string;
  is_paid: boolean;
}

export interface HolidayStatistics {
  total_holidays: number;
  active_holidays: number;
  holidays_this_month: number;
  holidays_this_year: number;
  paid_holidays: number;
  unpaid_holidays: number;
  recurring_holidays: number;
  upcoming_holiday?: Holiday;
}

// Labels
export const holidayTypeLabels: Record<HolidayType, string> = {
  public_holiday: 'Libur Nasional',
  religious_holiday: 'Libur Keagamaan',
  school_holiday: 'Libur Sekolah',
  substitute_holiday: 'Cuti Bersama',
};

export const holidayTypeColors: Record<HolidayType, string> = {
  public_holiday: '#DC2626',
  religious_holiday: '#7C3AED',
  school_holiday: '#2563EB',
  substitute_holiday: '#059669',
};

export const holidayStatusLabels: Record<HolidayStatus, string> = {
  active: 'Aktif',
  cancelled: 'Dibatalkan',
  moved: 'Dipindah',
};

export const holidayStatusColors: Record<HolidayStatus, string> = {
  active: '#10B981',
  cancelled: '#EF4444',
  moved: '#F59E0B',
};
