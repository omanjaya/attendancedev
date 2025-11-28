import { useState, useCallback } from 'react';
import type {
  Holiday,
  HolidayType,
  HolidayFormData,
  HolidayStatistics,
} from '@/types/holiday';

// Mock data for 2024
const mockHolidays: Holiday[] = [
  {
    id: '1',
    name: 'Tahun Baru 2024',
    description: 'Perayaan tahun baru masehi',
    date: '2024-01-01',
    type: 'public_holiday',
    status: 'active',
    is_recurring: true,
    recurring_pattern: { type: 'yearly' },
    is_paid: true,
    color: '#DC2626',
    source: 'manual',
    created_at: '2023-12-01T00:00:00',
    updated_at: '2023-12-01T00:00:00',
  },
  {
    id: '2',
    name: 'Isra Miraj',
    description: 'Memperingati Isra Miraj Nabi Muhammad SAW',
    date: '2024-02-08',
    type: 'religious_holiday',
    status: 'active',
    is_recurring: false,
    is_paid: true,
    color: '#7C3AED',
    source: 'api',
    created_at: '2024-01-01T00:00:00',
    updated_at: '2024-01-01T00:00:00',
  },
  {
    id: '3',
    name: 'Hari Raya Nyepi',
    description: 'Tahun Baru Saka',
    date: '2024-03-11',
    type: 'religious_holiday',
    status: 'active',
    is_recurring: false,
    is_paid: true,
    color: '#7C3AED',
    source: 'api',
    created_at: '2024-01-01T00:00:00',
    updated_at: '2024-01-01T00:00:00',
  },
  {
    id: '4',
    name: 'Wafat Isa Almasih',
    description: 'Memperingati wafatnya Isa Almasih',
    date: '2024-03-29',
    type: 'religious_holiday',
    status: 'active',
    is_recurring: false,
    is_paid: true,
    color: '#7C3AED',
    source: 'api',
    created_at: '2024-01-01T00:00:00',
    updated_at: '2024-01-01T00:00:00',
  },
  {
    id: '5',
    name: 'Idul Fitri 1445 H',
    description: 'Hari Raya Idul Fitri',
    date: '2024-04-10',
    end_date: '2024-04-11',
    type: 'religious_holiday',
    status: 'active',
    is_recurring: false,
    is_paid: true,
    color: '#7C3AED',
    source: 'api',
    created_at: '2024-01-01T00:00:00',
    updated_at: '2024-01-01T00:00:00',
  },
  {
    id: '6',
    name: 'Cuti Bersama Idul Fitri',
    description: 'Cuti bersama dalam rangka Idul Fitri',
    date: '2024-04-08',
    end_date: '2024-04-12',
    type: 'substitute_holiday',
    status: 'active',
    is_recurring: false,
    affected_roles: ['employee', 'teacher'],
    is_paid: true,
    color: '#059669',
    source: 'manual',
    created_at: '2024-02-01T00:00:00',
    updated_at: '2024-02-01T00:00:00',
  },
  {
    id: '7',
    name: 'Hari Buruh Internasional',
    description: 'May Day - Hari Buruh Internasional',
    date: '2024-05-01',
    type: 'public_holiday',
    status: 'active',
    is_recurring: true,
    recurring_pattern: { type: 'yearly' },
    is_paid: true,
    color: '#DC2626',
    source: 'manual',
    created_at: '2024-01-01T00:00:00',
    updated_at: '2024-01-01T00:00:00',
  },
  {
    id: '8',
    name: 'Kenaikan Isa Almasih',
    description: 'Memperingati kenaikan Isa Almasih',
    date: '2024-05-09',
    type: 'religious_holiday',
    status: 'active',
    is_recurring: false,
    is_paid: true,
    color: '#7C3AED',
    source: 'api',
    created_at: '2024-01-01T00:00:00',
    updated_at: '2024-01-01T00:00:00',
  },
  {
    id: '9',
    name: 'Hari Waisak',
    description: 'Hari Raya Waisak 2568 BE',
    date: '2024-05-23',
    type: 'religious_holiday',
    status: 'active',
    is_recurring: false,
    is_paid: true,
    color: '#7C3AED',
    source: 'api',
    created_at: '2024-01-01T00:00:00',
    updated_at: '2024-01-01T00:00:00',
  },
  {
    id: '10',
    name: 'Hari Lahir Pancasila',
    description: 'Memperingati lahirnya Pancasila',
    date: '2024-06-01',
    type: 'public_holiday',
    status: 'active',
    is_recurring: true,
    recurring_pattern: { type: 'yearly' },
    is_paid: true,
    color: '#DC2626',
    source: 'manual',
    created_at: '2024-01-01T00:00:00',
    updated_at: '2024-01-01T00:00:00',
  },
  {
    id: '11',
    name: 'Idul Adha 1445 H',
    description: 'Hari Raya Idul Adha',
    date: '2024-06-17',
    type: 'religious_holiday',
    status: 'active',
    is_recurring: false,
    is_paid: true,
    color: '#7C3AED',
    source: 'api',
    created_at: '2024-01-01T00:00:00',
    updated_at: '2024-01-01T00:00:00',
  },
  {
    id: '12',
    name: 'Libur Semester Ganjil',
    description: 'Libur akhir semester ganjil',
    date: '2024-12-23',
    end_date: '2025-01-04',
    type: 'school_holiday',
    status: 'active',
    is_recurring: false,
    affected_roles: ['teacher', 'employee'],
    is_paid: true,
    color: '#2563EB',
    source: 'manual',
    created_at: '2024-06-01T00:00:00',
    updated_at: '2024-06-01T00:00:00',
  },
  {
    id: '13',
    name: 'Hari Kemerdekaan RI',
    description: 'Peringatan Kemerdekaan Republik Indonesia',
    date: '2024-08-17',
    type: 'public_holiday',
    status: 'active',
    is_recurring: true,
    recurring_pattern: { type: 'yearly' },
    is_paid: true,
    color: '#DC2626',
    source: 'manual',
    created_at: '2024-01-01T00:00:00',
    updated_at: '2024-01-01T00:00:00',
  },
  {
    id: '14',
    name: 'Natal',
    description: 'Hari Raya Natal',
    date: '2024-12-25',
    type: 'religious_holiday',
    status: 'active',
    is_recurring: true,
    recurring_pattern: { type: 'yearly' },
    is_paid: true,
    color: '#7C3AED',
    source: 'manual',
    created_at: '2024-01-01T00:00:00',
    updated_at: '2024-01-01T00:00:00',
  },
];

export function useHolidays() {
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [holidays, setHolidays] = useState<Holiday[]>(mockHolidays);
  const [selectedHoliday, setSelectedHoliday] = useState<Holiday | null>(null);

  // Fetch holidays
  const fetchHolidays = useCallback(async (filters?: {
    type?: HolidayType;
    year?: number;
    month?: number;
    search?: string;
  }) => {
    setIsLoading(true);
    setError(null);

    try {
      await new Promise((resolve) => setTimeout(resolve, 500));

      let filtered = [...mockHolidays];

      if (filters?.type) {
        filtered = filtered.filter((h) => h.type === filters.type);
      }
      if (filters?.year) {
        filtered = filtered.filter((h) => {
          const date = new Date(h.date);
          return date.getFullYear() === filters.year;
        });
      }
      if (filters?.month) {
        filtered = filtered.filter((h) => {
          const date = new Date(h.date);
          return date.getMonth() + 1 === filters.month;
        });
      }
      if (filters?.search) {
        const search = filters.search.toLowerCase();
        filtered = filtered.filter((h) =>
          h.name.toLowerCase().includes(search) ||
          h.description?.toLowerCase().includes(search)
        );
      }

      // Sort by date
      filtered.sort((a, b) => new Date(a.date).getTime() - new Date(b.date).getTime());

      setHolidays(filtered);
    } catch (err) {
      setError('Gagal memuat data hari libur');
      console.error('Error fetching holidays:', err);
    } finally {
      setIsLoading(false);
    }
  }, []);

  // Create holiday
  const createHoliday = useCallback(async (data: HolidayFormData) => {
    setIsLoading(true);
    try {
      await new Promise((resolve) => setTimeout(resolve, 500));

      const newHoliday: Holiday = {
        id: `new-${Date.now()}`,
        name: data.name,
        description: data.description,
        date: data.date,
        end_date: data.end_date,
        type: data.type,
        status: 'active',
        is_recurring: data.is_recurring,
        recurring_pattern: data.recurring_pattern,
        affected_roles: data.affected_roles,
        color: data.color,
        is_paid: data.is_paid,
        source: 'manual',
        created_at: new Date().toISOString(),
        updated_at: new Date().toISOString(),
      };

      setHolidays((prev) => [...prev, newHoliday].sort((a, b) =>
        new Date(a.date).getTime() - new Date(b.date).getTime()
      ));
      return newHoliday;
    } catch (err) {
      setError('Gagal membuat hari libur');
      throw err;
    } finally {
      setIsLoading(false);
    }
  }, []);

  // Update holiday
  const updateHoliday = useCallback(async (id: string, data: Partial<HolidayFormData>) => {
    setIsLoading(true);
    try {
      await new Promise((resolve) => setTimeout(resolve, 500));

      setHolidays((prev) =>
        prev.map((holiday) =>
          holiday.id === id
            ? {
                ...holiday,
                ...data,
                updated_at: new Date().toISOString(),
              }
            : holiday
        ).sort((a, b) => new Date(a.date).getTime() - new Date(b.date).getTime())
      );
    } catch (err) {
      setError('Gagal mengupdate hari libur');
      throw err;
    } finally {
      setIsLoading(false);
    }
  }, []);

  // Delete holiday
  const deleteHoliday = useCallback(async (id: string) => {
    setIsLoading(true);
    try {
      await new Promise((resolve) => setTimeout(resolve, 500));
      setHolidays((prev) => prev.filter((h) => h.id !== id));
    } catch (err) {
      setError('Gagal menghapus hari libur');
      throw err;
    } finally {
      setIsLoading(false);
    }
  }, []);

  // Cancel holiday
  const cancelHoliday = useCallback(async (id: string) => {
    setIsLoading(true);
    try {
      await new Promise((resolve) => setTimeout(resolve, 300));

      setHolidays((prev) =>
        prev.map((holiday) =>
          holiday.id === id
            ? { ...holiday, status: 'cancelled', updated_at: new Date().toISOString() }
            : holiday
        )
      );
    } catch (err) {
      setError('Gagal membatalkan hari libur');
      throw err;
    } finally {
      setIsLoading(false);
    }
  }, []);

  // Get holidays for date range
  const getHolidaysInRange = useCallback(async (startDate: string, endDate: string): Promise<Holiday[]> => {
    await new Promise((resolve) => setTimeout(resolve, 300));

    const start = new Date(startDate);
    const end = new Date(endDate);

    return holidays.filter((h) => {
      const holidayStart = new Date(h.date);
      const holidayEnd = h.end_date ? new Date(h.end_date) : holidayStart;
      return (
        h.status === 'active' &&
        ((holidayStart >= start && holidayStart <= end) ||
          (holidayEnd >= start && holidayEnd <= end) ||
          (holidayStart <= start && holidayEnd >= end))
      );
    });
  }, [holidays]);

  // Check if date is holiday
  const isHoliday = useCallback(async (date: string): Promise<boolean> => {
    const checkDate = new Date(date);
    return holidays.some((h) => {
      if (h.status !== 'active') return false;
      const holidayStart = new Date(h.date);
      const holidayEnd = h.end_date ? new Date(h.end_date) : holidayStart;
      return checkDate >= holidayStart && checkDate <= holidayEnd;
    });
  }, [holidays]);

  // Get statistics
  const getStatistics = useCallback(async (): Promise<HolidayStatistics> => {
    await new Promise((resolve) => setTimeout(resolve, 300));

    const now = new Date();
    const thisMonth = holidays.filter((h) => {
      const date = new Date(h.date);
      return (
        date.getMonth() === now.getMonth() &&
        date.getFullYear() === now.getFullYear() &&
        h.status === 'active'
      );
    });

    const thisYear = holidays.filter((h) => {
      const date = new Date(h.date);
      return date.getFullYear() === now.getFullYear() && h.status === 'active';
    });

    const upcoming = holidays
      .filter((h) => new Date(h.date) > now && h.status === 'active')
      .sort((a, b) => new Date(a.date).getTime() - new Date(b.date).getTime())[0];

    return {
      total_holidays: holidays.length,
      active_holidays: holidays.filter((h) => h.status === 'active').length,
      holidays_this_month: thisMonth.length,
      holidays_this_year: thisYear.length,
      paid_holidays: holidays.filter((h) => h.is_paid && h.status === 'active').length,
      unpaid_holidays: holidays.filter((h) => !h.is_paid && h.status === 'active').length,
      recurring_holidays: holidays.filter((h) => h.is_recurring).length,
      upcoming_holiday: upcoming,
    };
  }, [holidays]);

  return {
    // State
    isLoading,
    error,
    holidays,
    selectedHoliday,

    // Actions
    fetchHolidays,
    createHoliday,
    updateHoliday,
    deleteHoliday,
    cancelHoliday,
    getHolidaysInRange,
    isHoliday,
    getStatistics,
    setSelectedHoliday,
    clearError: () => setError(null),
  };
}
