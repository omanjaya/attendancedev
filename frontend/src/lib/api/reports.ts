import apiClient from './client';
import type {
  ReportData,
  ReportSummary,
  MonthlyAttendanceData,
  WeeklyTrendData,
  DepartmentStats,
  LeaveStats,
  GenerateReportRequest,
  GeneratedReport,
  ReportTemplate,
  ReportFilters,
} from '@/types/reports';

export interface ReportsFilters extends ReportFilters {
  page?: number;
  per_page?: number;
}

const ENDPOINTS = {
  data: '/reports/data',
  summary: '/reports/summary',
  monthlyAttendance: '/reports/attendance/monthly',
  weeklyTrend: '/reports/attendance/weekly',
  departmentStats: '/reports/departments',
  leaveStats: '/reports/leave',
  generate: '/reports/generate',
  templates: '/reports/templates',
  templateDetail: (id: string) => `/reports/templates/${id}`,
  generatedReports: '/reports/generated',
  generatedReportDetail: (id: string) => `/reports/generated/${id}`,
  download: (id: string) => `/reports/generated/${id}/download`,
} as const;

// Get full report data (all charts)
export async function getReportData(filters?: ReportFilters): Promise<ReportData> {
  const response = await apiClient.get<{ data: ReportData }>('/reports/data', {
    params: filters,
  });
  return response.data.data;
}

// Get report summary statistics
export async function getReportSummary(filters?: ReportFilters): Promise<ReportSummary> {
  const response = await apiClient.get<{ data: ReportSummary }>('/reports/summary', {
    params: filters,
  });
  return response.data.data;
}

// Get monthly attendance data for charts
export async function getMonthlyAttendance(
  filters?: ReportFilters
): Promise<MonthlyAttendanceData[]> {
  const response = await apiClient.get<{ data: MonthlyAttendanceData[] }>(
    ENDPOINTS.monthlyAttendance,
    { params: filters }
  );
  return response.data.data;
}

// Get weekly trend data for charts
export async function getWeeklyTrend(filters?: ReportFilters): Promise<WeeklyTrendData[]> {
  const response = await apiClient.get<{ data: WeeklyTrendData[] }>(ENDPOINTS.weeklyTrend, {
    params: filters,
  });
  return response.data.data;
}

// Get department statistics
export async function getDepartmentStats(filters?: ReportFilters): Promise<DepartmentStats[]> {
  const response = await apiClient.get<{ data: DepartmentStats[] }>(ENDPOINTS.departmentStats, {
    params: filters,
  });
  return response.data.data;
}

// Get leave statistics
export async function getLeaveStats(filters?: ReportFilters): Promise<LeaveStats[]> {
  const response = await apiClient.get<{ data: LeaveStats[] }>(ENDPOINTS.leaveStats, {
    params: filters,
  });
  return response.data.data;
}

// Generate a new report
export async function generateReport(data: GenerateReportRequest): Promise<GeneratedReport> {
  const response = await apiClient.post<{ data: GeneratedReport }>(ENDPOINTS.generate, data);
  return response.data.data;
}

// Download a generated report
export async function downloadReport(reportId: string): Promise<Blob> {
  const response = await apiClient.get(ENDPOINTS.download(reportId), {
    responseType: 'blob',
  });
  return response.data;
}

// Get list of generated reports
export async function getGeneratedReports(): Promise<GeneratedReport[]> {
  const response = await apiClient.get<{ data: GeneratedReport[] }>(ENDPOINTS.generatedReports);
  return response.data.data;
}

// Get a specific generated report
export async function getGeneratedReport(id: string): Promise<GeneratedReport> {
  const response = await apiClient.get<{ data: GeneratedReport }>(
    ENDPOINTS.generatedReportDetail(id)
  );
  return response.data.data;
}

// Get list of report templates
export async function getReportTemplates(): Promise<ReportTemplate[]> {
  const response = await apiClient.get<{ data: ReportTemplate[] }>(ENDPOINTS.templates);
  return response.data.data;
}

// Create a new report template
export async function createReportTemplate(
  data: Omit<ReportTemplate, 'id' | 'created_at' | 'updated_at'>
): Promise<ReportTemplate> {
  const response = await apiClient.post<{ data: ReportTemplate }>(ENDPOINTS.templates, data);
  return response.data.data;
}

// Update a report template
export async function updateReportTemplate(
  id: string,
  data: Partial<Omit<ReportTemplate, 'id' | 'created_at' | 'updated_at'>>
): Promise<ReportTemplate> {
  const response = await apiClient.put<{ data: ReportTemplate }>(
    ENDPOINTS.templateDetail(id),
    data
  );
  return response.data.data;
}

// Delete a report template
export async function deleteReportTemplate(id: string): Promise<void> {
  await apiClient.delete(ENDPOINTS.templateDetail(id));
}

// Helper to wait for report completion
export async function waitForReportCompletion(
  reportId: string,
  maxAttempts = 30, // Wait up to 60 seconds
  interval = 2000
): Promise<GeneratedReport> {
  for (let i = 0; i < maxAttempts; i++) {
    try {
      const report = await getGeneratedReport(reportId);

      if (report.status === 'completed') {
        return report;
      }

      if (report.status === 'failed') {
        throw new Error(report.error_message || 'Report generation failed');
      }

      // If pending or processing, wait and try again
      await new Promise((resolve) => setTimeout(resolve, interval));
    } catch (error) {
      // If it's the custom error, rethrow it
      if (error instanceof Error && (error.message.includes('Report generation failed') || error.message.includes('timed out'))) {
        throw error;
      }
      // Ignore network errors during polling and try again
      console.warn(`Polling attempt ${i + 1} failed:`, error);
      await new Promise((resolve) => setTimeout(resolve, interval));
    }
  }

  throw new Error('Report generation timed out. Please check the history tab later.');
}
