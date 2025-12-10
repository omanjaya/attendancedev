import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useCallback } from 'react';
import type {
  Holiday,
  HolidayFormData,
} from '@/types/holiday';
import {
  getHolidays as fetchHolidaysApi,
  getHolidaysByMonth,
  getHolidayStatistics,
  createHoliday as createHolidayApi,
  updateHoliday as updateHolidayApi,
  deleteHoliday as deleteHolidayApi,
  type HolidayFilters,
} from '@/lib/api/holidays';
import { useNotificationStore } from '@/stores';

export function useHolidays(filters?: HolidayFilters) {
  const queryClient = useQueryClient();
  const { success, error: showError } = useNotificationStore();

  // Fetch holidays with pagination
  const {
    data: holidaysData,
    isLoading,
    error,
    refetch,
  } = useQuery({
    queryKey: ['holidays', filters],
    queryFn: () => fetchHolidaysApi(filters),
    staleTime: 30000, // 30 seconds
  });

  const holidays = holidaysData?.data || [];
  const meta = holidaysData?.meta;

  // Create holiday mutation
  const createHolidayMutation = useMutation({
    mutationFn: (data: HolidayFormData) => createHolidayApi(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['holidays'] });
      success('Berhasil', 'Hari libur berhasil dibuat');
    },
    onError: (err: any) => {
      const message = err?.response?.data?.message || 'Gagal membuat hari libur';
      showError('Error', message);
    },
  });

  // Update holiday mutation
  const updateHolidayMutation = useMutation({
    mutationFn: ({ id, data }: { id: string; data: Partial<HolidayFormData> }) =>
      updateHolidayApi(id, data),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ['holidays'] });
      success('Berhasil', 'Hari libur berhasil diupdate');
    },
    // onError removed - global error handler will catch it
  });

  // Delete holiday mutation
  const deleteHolidayMutation = useMutation({
    mutationFn: (id: string) => deleteHolidayApi(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['holidays'] });
      success('Berhasil', 'Hari libur berhasil dihapus');
    },
    onError: (err: any) => {
      const message = err?.response?.data?.message || 'Gagal menghapus hari libur';
      showError('Error', message);
    },
  });

  // Fetch holidays (wrapper for refetch)
  const fetchHolidays = useCallback(
    async (newFilters?: HolidayFilters) => {
      if (newFilters) {
        // If new filters provided, we need to update the query key
        await queryClient.invalidateQueries({ queryKey: ['holidays'] });
      }
      await refetch();
    },
    [refetch, queryClient]
  );

  // Create holiday
  const createHoliday = useCallback(
    async (data: HolidayFormData) => {
      return await createHolidayMutation.mutateAsync(data);
    },
    [createHolidayMutation]
  );

  // Update holiday
  const updateHoliday = useCallback(
    async (id: string, data: Partial<HolidayFormData>) => {
      return await updateHolidayMutation.mutateAsync({ id, data });
    },
    [updateHolidayMutation]
  );

  // Delete holiday
  const deleteHoliday = useCallback(
    async (id: string) => {
      return await deleteHolidayMutation.mutateAsync(id);
    },
    [deleteHolidayMutation]
  );

  // Cancel holiday (update status to cancelled)
  const cancelHoliday = useCallback(
    async (id: string) => {
      return await updateHolidayMutation.mutateAsync({ id, data: { status: 'cancelled' } });
    },
    [updateHolidayMutation]
  );

  // Get holidays in date range
  const getHolidaysInRange = useCallback(
    async (startDate: string, endDate: string): Promise<Holiday[]> => {
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
    },
    [holidays]
  );

  // Check if date is holiday
  const isHoliday = useCallback(
    (date: string): boolean => {
      const checkDate = new Date(date);
      return holidays.some((h) => {
        if (h.status !== 'active') return false;
        const holidayStart = new Date(h.date);
        const holidayEnd = h.end_date ? new Date(h.end_date) : holidayStart;
        return checkDate >= holidayStart && checkDate <= holidayEnd;
      });
    },
    [holidays]
  );

  // Get statistics (using React Query)
  const useStatistics = (year?: number) => {
    return useQuery({
      queryKey: ['holiday-statistics', year],
      queryFn: () => getHolidayStatistics(year),
      staleTime: 60000, // 1 minute
    });
  };

  // Get statistics (imperative)
  const getStatistics = useCallback(async (year?: number) => {
    return await getHolidayStatistics(year);
  }, []);

  return {
    // State
    isLoading: isLoading || createHolidayMutation.isPending || updateHolidayMutation.isPending || deleteHolidayMutation.isPending,
    error: error?.message || null,
    holidays,
    meta,

    // Actions
    fetchHolidays,
    createHoliday,
    updateHoliday,
    deleteHoliday,
    cancelHoliday,
    getHolidaysInRange,
    isHoliday,
    useStatistics,
    getStatistics,
    bulkImport: async (holidays: any[]) => {
      // We don't use mutation here to keep it simple, but we could
      const result = await import('@/lib/api/holidays').then(m => m.bulkImportHolidays(holidays));
      await refetch();
      return result;
    },
    refetch,
  };
}

// Hook for fetching holidays by month (used in monthly schedule creation)
export function useHolidaysByMonth(month: number, year: number) {
  return useQuery({
    queryKey: ['holidays-by-month', month, year],
    queryFn: () => getHolidaysByMonth(month, year),
    enabled: Boolean(month && year),
    staleTime: 300000, // 5 minutes
  });
}
