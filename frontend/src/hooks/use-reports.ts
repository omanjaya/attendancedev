import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import {
  getReportData,
  getReportSummary,
  getMonthlyAttendance,
  getWeeklyTrend,
  getDepartmentStats,
  getLeaveStats,
  getMonthlyRecap,
  getTeachingScheduleReport,
  getTeachingSubjects,
  generateReport,
  downloadReport,
  getGeneratedReports,
  getGeneratedReport,
  getReportTemplates,
  createReportTemplate,
  updateReportTemplate,
  deleteReportTemplate,
} from '@/lib/api/reports';
import type { ReportFilters, GenerateReportRequest, ReportTemplate } from '@/types/reports';
import { useNotificationStore } from '@/stores';

// Query keys
export const reportKeys = {
  all: ['reports'] as const,
  data: (filters?: ReportFilters) => [...reportKeys.all, 'data', filters] as const,
  summary: (filters?: ReportFilters) => [...reportKeys.all, 'summary', filters] as const,
  monthlyAttendance: (filters?: ReportFilters) =>
    [...reportKeys.all, 'monthly-attendance', filters] as const,
  weeklyTrend: (filters?: ReportFilters) => [...reportKeys.all, 'weekly-trend', filters] as const,
  departmentStats: (filters?: ReportFilters) =>
    [...reportKeys.all, 'department-stats', filters] as const,
  leaveStats: (filters?: ReportFilters) => [...reportKeys.all, 'leave-stats', filters] as const,
  monthlyRecap: (filters?: { month?: number; year?: number; department?: string; employee_type?: 'guru' | 'pegawai' | null }) =>
    [...reportKeys.all, 'monthly-recap', filters] as const,
  teachingSchedules: (filters?: { month?: number; year?: number; subject?: string }) =>
    [...reportKeys.all, 'teaching-schedules', filters] as const,
  teachingSubjects: () => [...reportKeys.all, 'teaching-subjects'] as const,
  templates: () => [...reportKeys.all, 'templates'] as const,
  generatedReports: () => [...reportKeys.all, 'generated'] as const,
  generatedReport: (id: string) => [...reportKeys.all, 'generated', id] as const,
};

// Get full report data
export function useReportData(filters?: ReportFilters) {
  return useQuery({
    queryKey: reportKeys.data(filters),
    queryFn: () => getReportData(filters),
    staleTime: 1000 * 60 * 5, // Cache for 5 minutes
  });
}

// Get report summary
export function useReportSummary(filters?: ReportFilters) {
  return useQuery({
    queryKey: reportKeys.summary(filters),
    queryFn: () => getReportSummary(filters),
    staleTime: 1000 * 60 * 5,
  });
}

// Get monthly attendance data
export function useMonthlyAttendance(filters?: ReportFilters) {
  return useQuery({
    queryKey: reportKeys.monthlyAttendance(filters),
    queryFn: () => getMonthlyAttendance(filters),
    staleTime: 1000 * 60 * 5,
  });
}

// Get weekly trend data
export function useWeeklyTrend(filters?: ReportFilters) {
  return useQuery({
    queryKey: reportKeys.weeklyTrend(filters),
    queryFn: () => getWeeklyTrend(filters),
    staleTime: 1000 * 60 * 5,
  });
}

// Get department stats
export function useDepartmentStats(filters?: ReportFilters) {
  return useQuery({
    queryKey: reportKeys.departmentStats(filters),
    queryFn: () => getDepartmentStats(filters),
    staleTime: 1000 * 60 * 5,
  });
}

// Get leave stats
export function useLeaveStats(filters?: ReportFilters) {
  return useQuery({
    queryKey: reportKeys.leaveStats(filters),
    queryFn: () => getLeaveStats(filters),
    staleTime: 1000 * 60 * 5,
  });
}

// Get monthly recap with employee type filter
export function useMonthlyRecap(filters?: {
  month?: number;
  year?: number;
  department?: string;
  employee_type?: 'guru' | 'pegawai' | null;
}) {
  return useQuery({
    queryKey: reportKeys.monthlyRecap(filters),
    queryFn: () => getMonthlyRecap(filters),
    staleTime: 1000 * 60 * 5,
  });
}

// Get teaching schedule report
export function useTeachingScheduleReport(filters?: {
  month?: number;
  year?: number;
  subject?: string;
}) {
  return useQuery({
    queryKey: reportKeys.teachingSchedules(filters),
    queryFn: () => getTeachingScheduleReport(filters),
    staleTime: 1000 * 60 * 5,
  });
}

// Get teaching subjects for filter dropdown
export function useTeachingSubjects() {
  return useQuery({
    queryKey: reportKeys.teachingSubjects(),
    queryFn: getTeachingSubjects,
    staleTime: 1000 * 60 * 30, // Cache for 30 minutes
  });
}

// Get report templates
export function useReportTemplates() {
  return useQuery({
    queryKey: reportKeys.templates(),
    queryFn: getReportTemplates,
  });
}

// Get generated reports
export function useGeneratedReports() {
  return useQuery({
    queryKey: reportKeys.generatedReports(),
    queryFn: getGeneratedReports,
  });
}

// Get a specific generated report
export function useGeneratedReport(id: string) {
  return useQuery({
    queryKey: reportKeys.generatedReport(id),
    queryFn: () => getGeneratedReport(id),
    enabled: !!id,
    refetchInterval: (query) => {
      // Poll while report is processing
      const status = query.state.data?.status;
      if (status === 'pending' || status === 'processing') {
        return 5000; // Poll every 5 seconds
      }
      return false;
    },
  });
}

// Generate report mutation
export function useGenerateReport() {
  const queryClient = useQueryClient();
  const { success, error } = useNotificationStore();

  return useMutation({
    mutationFn: (data: GenerateReportRequest) => generateReport(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: reportKeys.generatedReports() });
      success('Berhasil', 'Laporan sedang diproses');
    },
    onError: (err: Error) => {
      error('Gagal', err.message || 'Gagal membuat laporan');
    },
  });
}

// Download report mutation
export function useDownloadReport() {
  const { success, error } = useNotificationStore();

  return useMutation({
    mutationFn: (reportId: string) => downloadReport(reportId),
    onSuccess: (blob, reportId) => {
      // Create download link
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = `report-${reportId}.${blob.type.includes('pdf') ? 'pdf' : 'xlsx'}`;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      window.URL.revokeObjectURL(url);
      success('Berhasil', 'Laporan berhasil diunduh');
    },
    onError: (err: Error) => {
      error('Gagal', err.message || 'Gagal mengunduh laporan');
    },
  });
}

// Create template mutation
export function useCreateReportTemplate() {
  const queryClient = useQueryClient();
  const { success, error } = useNotificationStore();

  return useMutation({
    mutationFn: (data: Omit<ReportTemplate, 'id' | 'created_at' | 'updated_at'>) =>
      createReportTemplate(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: reportKeys.templates() });
      success('Berhasil', 'Template laporan berhasil dibuat');
    },
    onError: (err: Error) => {
      error('Gagal', err.message || 'Gagal membuat template');
    },
  });
}

// Update template mutation
export function useUpdateReportTemplate() {
  const queryClient = useQueryClient();
  const { success } = useNotificationStore();

  return useMutation({
    mutationFn: ({
      id,
      data,
    }: {
      id: string;
      data: Partial<Omit<ReportTemplate, 'id' | 'created_at' | 'updated_at'>>;
    }) => updateReportTemplate(id, data),
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: reportKeys.templates() });
      success('Berhasil', 'Template laporan berhasil diperbarui');
    },
    // onError removed - global error handler will catch it
  });
}

// Delete template mutation
export function useDeleteReportTemplate() {
  const queryClient = useQueryClient();
  const { success, error } = useNotificationStore();

  return useMutation({
    mutationFn: (id: string) => deleteReportTemplate(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: reportKeys.templates() });
      success('Berhasil', 'Template laporan berhasil dihapus');
    },
    onError: (err: Error) => {
      error('Gagal', err.message || 'Gagal menghapus template');
    },
  });
}

// Legacy hook for backward compatibility
export function useReports(filters?: ReportFilters) {
  const summaryQuery = useReportSummary(filters);
  const monthlyQuery = useMonthlyAttendance(filters);
  const weeklyQuery = useWeeklyTrend(filters);
  const departmentQuery = useDepartmentStats(filters);
  const leaveQuery = useLeaveStats(filters);

  return {
    // Summary data
    summary: summaryQuery.data,
    isLoadingSummary: summaryQuery.isLoading,

    // Chart data
    monthlyAttendance: monthlyQuery.data ?? [],
    weeklyTrend: weeklyQuery.data ?? [],
    departmentStats: departmentQuery.data ?? [],
    leaveStats: leaveQuery.data ?? [],

    // Loading states
    isLoading:
      summaryQuery.isLoading ||
      monthlyQuery.isLoading ||
      weeklyQuery.isLoading ||
      departmentQuery.isLoading ||
      leaveQuery.isLoading,

    // Errors
    error:
      summaryQuery.error?.message ||
      monthlyQuery.error?.message ||
      weeklyQuery.error?.message ||
      departmentQuery.error?.message ||
      leaveQuery.error?.message ||
      null,

    // Refetch
    refetch: () => {
      summaryQuery.refetch();
      monthlyQuery.refetch();
      weeklyQuery.refetch();
      departmentQuery.refetch();
      leaveQuery.refetch();
    },
  };
}
