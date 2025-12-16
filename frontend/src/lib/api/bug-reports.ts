import apiClient from './client';

// ============================================
// Types
// ============================================

export interface BugReport {
  id: string;
  user_id?: string;
  title: string;
  description: string;
  type: 'bug' | 'error' | 'suggestion' | 'question';
  severity: 'low' | 'medium' | 'high' | 'critical';
  status: 'open' | 'in_progress' | 'resolved' | 'closed' | 'wont_fix';
  page_url?: string;
  error_message?: string;
  error_stack?: string;
  browser_info?: BrowserInfo;
  screenshots?: string[];
  screenshot_urls?: string[];
  ip_address?: string;
  user_agent?: string;
  admin_notes?: string;
  resolved_at?: string;
  resolved_by?: string;
  created_at: string;
  updated_at: string;
  user?: {
    id: string;
    name: string;
    email: string;
  };
  resolver?: {
    id: string;
    name: string;
    email: string;
  };
}

export interface BrowserInfo {
  userAgent?: string;
  platform?: string;
  language?: string;
  screenWidth?: number;
  screenHeight?: number;
  windowWidth?: number;
  windowHeight?: number;
  colorDepth?: number;
  pixelRatio?: number;
  timezone?: string;
  cookiesEnabled?: boolean;
  onLine?: boolean;
}

export interface BugReportInput {
  title: string;
  description: string;
  type: 'bug' | 'error' | 'suggestion' | 'question';
  severity?: 'low' | 'medium' | 'high' | 'critical';
  page_url?: string;
  error_message?: string;
  error_stack?: string;
  browser_info?: BrowserInfo;
  screenshots?: string[]; // Base64 encoded images
}

export interface BugReportStatistics {
  total: number;
  open: number;
  in_progress: number;
  resolved: number;
  closed: number;
  by_type: {
    bug: number;
    error: number;
    suggestion: number;
    question: number;
  };
  by_severity: {
    critical: number;
    high: number;
    medium: number;
    low: number;
  };
  recent_7_days: number;
}

export interface PaginatedResponse<T> {
  data: T[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

// ============================================
// Helper Functions
// ============================================

/**
 * Get current browser info for bug report
 */
export function getBrowserInfo(): BrowserInfo {
  return {
    userAgent: navigator.userAgent,
    platform: navigator.platform,
    language: navigator.language,
    screenWidth: window.screen.width,
    screenHeight: window.screen.height,
    windowWidth: window.innerWidth,
    windowHeight: window.innerHeight,
    colorDepth: window.screen.colorDepth,
    pixelRatio: window.devicePixelRatio,
    timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
    cookiesEnabled: navigator.cookieEnabled,
    onLine: navigator.onLine,
  };
}

/**
 * Capture screenshot of current page (requires html2canvas)
 */
export async function captureScreenshot(): Promise<string | null> {
  try {
    const html2canvas = (await import('html2canvas')).default;
    const canvas = await html2canvas(document.body, {
      scale: 0.5, // Reduce size
      logging: false,
      useCORS: true,
      allowTaint: true,
    });
    return canvas.toDataURL('image/jpeg', 0.7);
  } catch (error) {
    console.error('Failed to capture screenshot:', error);
    return null;
  }
}

// ============================================
// API Functions
// ============================================

/**
 * Submit a new bug report
 */
export async function submitBugReport(input: BugReportInput): Promise<{
  id: string;
  title: string;
  status: string;
}> {
  const response = await apiClient.post<{
    success: boolean;
    message: string;
    data: { id: string; title: string; status: string };
  }>('/bug-reports', {
    ...input,
    browser_info: input.browser_info || getBrowserInfo(),
    page_url: input.page_url || window.location.href,
  });
  return response.data.data;
}

/**
 * Get my submitted bug reports
 */
export async function getMyBugReports(
  page = 1,
  perPage = 10
): Promise<PaginatedResponse<BugReport>> {
  const response = await apiClient.get<{
    success: boolean;
    data: PaginatedResponse<BugReport>;
  }>('/bug-reports/my-reports', {
    params: { page, per_page: perPage },
  });
  return response.data.data;
}

/**
 * Get all bug reports (Admin only)
 */
export async function getBugReports(filters?: {
  status?: string;
  type?: string;
  severity?: string;
  search?: string;
  page?: number;
  per_page?: number;
}): Promise<PaginatedResponse<BugReport>> {
  const response = await apiClient.get<{
    success: boolean;
    data: PaginatedResponse<BugReport>;
  }>('/bug-reports', { params: filters });
  return response.data.data;
}

/**
 * Get a specific bug report (Admin only)
 */
export async function getBugReport(id: string): Promise<BugReport> {
  const response = await apiClient.get<{
    success: boolean;
    data: BugReport;
  }>(`/bug-reports/${id}`);
  return response.data.data;
}

/**
 * Update bug report status (Admin only)
 */
export async function updateBugReportStatus(
  id: string,
  status: BugReport['status'],
  adminNotes?: string
): Promise<BugReport> {
  const response = await apiClient.patch<{
    success: boolean;
    data: BugReport;
  }>(`/bug-reports/${id}/status`, {
    status,
    admin_notes: adminNotes,
  });
  return response.data.data;
}

/**
 * Get bug report statistics (Admin only)
 */
export async function getBugReportStatistics(): Promise<BugReportStatistics> {
  const response = await apiClient.get<{
    success: boolean;
    data: BugReportStatistics;
  }>('/bug-reports/statistics');
  return response.data.data;
}

/**
 * Delete a bug report (Admin only)
 */
export async function deleteBugReport(id: string): Promise<void> {
  await apiClient.delete(`/bug-reports/${id}`);
}

// ============================================
// Labels & Colors
// ============================================

export const bugReportTypeLabels: Record<BugReport['type'], string> = {
  bug: 'Bug',
  error: 'Error',
  suggestion: 'Saran',
  question: 'Pertanyaan',
};

export const bugReportSeverityLabels: Record<BugReport['severity'], string> = {
  low: 'Rendah',
  medium: 'Sedang',
  high: 'Tinggi',
  critical: 'Kritis',
};

export const bugReportStatusLabels: Record<BugReport['status'], string> = {
  open: 'Terbuka',
  in_progress: 'Dalam Proses',
  resolved: 'Selesai',
  closed: 'Ditutup',
  wont_fix: 'Tidak Diperbaiki',
};

export const bugReportSeverityColors: Record<BugReport['severity'], string> = {
  low: 'bg-green-100 text-green-800',
  medium: 'bg-yellow-100 text-yellow-800',
  high: 'bg-orange-100 text-orange-800',
  critical: 'bg-red-100 text-red-800',
};

export const bugReportStatusColors: Record<BugReport['status'], string> = {
  open: 'bg-blue-100 text-blue-800',
  in_progress: 'bg-yellow-100 text-yellow-800',
  resolved: 'bg-green-100 text-green-800',
  closed: 'bg-gray-100 text-gray-800',
  wont_fix: 'bg-red-100 text-red-800',
};
