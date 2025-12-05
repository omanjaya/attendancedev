import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import {
  FileText,
  BarChart2,
  Download,
  Calendar,
  Users,
  TrendingUp,
  Filter,
  RefreshCw,
  CheckCircle,
  Clock,
  Loader2,
  History,
  LayoutTemplate,
  Plus,
  Trash2,
  Edit,
  Eye,
} from 'lucide-react';
import { PageHeader, StatsGrid } from '@/components/shared';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { toast } from 'sonner';
import {
  getReportSummary,
  getMonthlyAttendance,
  getWeeklyTrend,
  getDepartmentStats,
  getLeaveStats,
  generateReport,
  downloadReport,
  getGeneratedReports,
  getReportTemplates,
  createReportTemplate,
  deleteReportTemplate,
  waitForReportCompletion,
} from '@/lib/api/reports';
import type { ReportFormat, ReportTemplate } from '@/types/reports';
import { format, subMonths } from 'date-fns';
import { id } from 'date-fns/locale';

export function DesktopReportsPage() {
  const [timeRange, setTimeRange] = useState('30');
  const [activeTab, setActiveTab] = useState('export');
  const [isExporting, setIsExporting] = useState(false);

  // Export filters
  const [reportType, setReportType] = useState<'attendance' | 'leave' | 'payroll'>('attendance');
  const [selectedColumns, setSelectedColumns] = useState<string[]>([
    'employee_name', 'date', 'check_in', 'check_out', 'status', 'work_hours'
  ]);

  // Template dialog states
  const [isTemplateDialogOpen, setIsTemplateDialogOpen] = useState(false);
  const [templateName, setTemplateName] = useState('');
  const [templateDescription, setTemplateDescription] = useState('');
  const [templateType, setTemplateType] = useState<'attendance' | 'leave' | 'payroll'>('attendance');

  console.log('DesktopReportsPage rendering, checking API calls...');

  // Calculate date range
  const endDate = new Date();
  const startDate = subMonths(endDate, parseInt(timeRange) / 30);

  const filters = {
    start_date: format(startDate, 'yyyy-MM-dd'),
    end_date: format(endDate, 'yyyy-MM-dd'),
  };

  // Fetch report data
  const { data: summary } = useQuery({
    queryKey: ['reports', 'summary', timeRange],
    queryFn: () => getReportSummary(filters),
  });

  const { data: monthlyData } = useQuery({
    queryKey: ['reports', 'monthly-attendance', timeRange],
    queryFn: () => getMonthlyAttendance(filters),
  });

  const { data: weeklyData } = useQuery({
    queryKey: ['reports', 'weekly-trend', timeRange],
    queryFn: () => getWeeklyTrend(filters),
  });

  const { data: departmentData } = useQuery({
    queryKey: ['reports', 'department-stats', timeRange],
    queryFn: () => getDepartmentStats(filters),
  });

  const { data: leaveData } = useQuery({
    queryKey: ['reports', 'leave-stats', timeRange],
    queryFn: () => getLeaveStats(filters),
  });

  // Fetch generated reports history
  const { data: generatedReports, refetch: refetchHistory } = useQuery({
    queryKey: ['reports', 'generated'],
    queryFn: getGeneratedReports,
  });

  // Fetch report templates
  const { data: templates, refetch: refetchTemplates } = useQuery({
    queryKey: ['reports', 'templates'],
    queryFn: getReportTemplates,
  });

  const summaryStats = [
    {
      label: 'Total Karyawan',
      value: summary?.total_employees || 0,
      icon: Users,
      color: 'info' as const,
      description: `Karyawan aktif`,
    },
    {
      label: 'Rata-rata Kehadiran',
      value: `${summary?.attendance?.rate || 0}%`,
      icon: TrendingUp,
      color: 'success' as const,
      trend: summary?.attendance?.rate && summary.attendance.rate >= 90 ? 'up' as const : 'down' as const,
      trendValue: `${summary?.work_days || 0} hari kerja`,
      description: 'Tingkat kehadiran',
    },
    {
      label: 'Hadir',
      value: summary?.attendance?.present || 0,
      icon: CheckCircle,
      color: 'success' as const,
      description: `${summary?.attendance?.late || 0} terlambat`,
    },
    {
      label: 'Rata-rata Jam Kerja',
      value: `${summary?.avg_work_hours || 0}h`,
      icon: Clock,
      color: 'primary' as const,
      description: 'Per hari',
    },
  ];

  const handleExport = async (reportFormat: ReportFormat) => {
    setIsExporting(true);
    try {
      // Generate the report with custom filters
      const response = await generateReport({
        type: reportType, // Changed from report_type
        format: reportFormat,
        start_date: filters.start_date,
        end_date: filters.end_date,
        filters: { columns: selectedColumns }, // Changed from columns to filters
      });

      console.log('DEBUG: Initial report response:', response);
      toast.loading('Laporan sedang diproses. Mohon tunggu...');

      // The API returns { report: GeneratedReport, download_url: string, ... }
      const reportData = response.report;
      const downloadUrl = response.download_url || reportData.download_url;

      let report = reportData;

      // Only poll if not already completed AND we don't have a download URL
      if (report.status !== 'completed' && !downloadUrl) {
        report = await waitForReportCompletion(report.id);
      }

      // If we have a direct download URL, use it
      if (downloadUrl) {
        toast.dismiss();
        window.open(downloadUrl, '_blank');
        setTimeout(() => {
          toast.success(`Export berhasil! Laporan ${reportFormat.toUpperCase()} sedang diunduh.`);
        }, 500);
      } else if (report.download_url) {
        toast.dismiss();
        window.open(report.download_url, '_blank');
        setTimeout(() => {
          toast.success(`Export berhasil! Laporan ${reportFormat.toUpperCase()} sedang diunduh.`);
        }, 500);
      } else {
        // Fallback to API download
        const blob = await downloadReport(report.id);

        // Create download link
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;

        // Set filename based on format
        const extension = reportFormat === 'excel' ? 'xlsx' : reportFormat;
        const filename = `laporan-kehadiran-${format(startDate, 'yyyy-MM-dd')}-${format(endDate, 'yyyy-MM-dd')}.${extension}`;
        link.setAttribute('download', filename);

        // Trigger download
        document.body.appendChild(link);
        link.click();

        // Cleanup
        link.parentNode?.removeChild(link);
        window.URL.revokeObjectURL(url);

        toast.dismiss();
        setTimeout(() => {
          toast.success(`Export berhasil! Laporan ${reportFormat.toUpperCase()} berhasil diunduh.`);
        }, 500);
      }

      refetchHistory(); // Refresh history after export
    } catch (error: any) {
      console.error('Export error:', error);
      toast.dismiss(); // Dismiss all toasts on error just in case, or we should track the ID
      toast.error(error.message || error.response?.data?.message || 'Export gagal. Gagal membuat laporan. Silakan coba lagi.');
    } finally {
      setIsExporting(false);
    }
  };

  // Download report from history
  const handleDownloadHistory = async (reportId: string, reportName: string) => {
    try {
      toast.loading('Mengunduh laporan...');
      const blob = await downloadReport(reportId);

      const url = window.URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', reportName || `laporan-${reportId}.pdf`);

      document.body.appendChild(link);
      link.click();
      link.parentNode?.removeChild(link);
      window.URL.revokeObjectURL(url);

      toast.success('Laporan berhasil diunduh!');
    } catch (error: any) {
      console.error('Download error:', error);
      toast.error('Gagal mengunduh laporan');
    }
  };

  // Delete template
  const handleDeleteTemplate = async (templateId: string, templateName: string) => {
    if (!confirm(`Apakah Anda yakin ingin menghapus template "${templateName}"?`)) {
      return;
    }

    try {
      await deleteReportTemplate(templateId);
      toast.success('Template berhasil dihapus!');
      refetchTemplates();
    } catch (error: any) {
      console.error('Delete error:', error);
      toast.error('Gagal menghapus template');
    }
  };

  // Open create template dialog
  const handleCreateTemplate = () => {
    setTemplateName('');
    setTemplateDescription('');
    setTemplateType('attendance');
    setIsTemplateDialogOpen(true);
  };

  // Submit create template
  const handleSubmitTemplate = async () => {
    if (!templateName.trim()) {
      toast.error('Nama template harus diisi!');
      return;
    }

    try {
      await createReportTemplate({
        name: templateName,
        description: templateDescription,
        report_type: templateType,
        columns: selectedColumns,
        filters: {},
      });
      toast.success(`Template "${templateName}" berhasil dibuat!`);
      setIsTemplateDialogOpen(false);
      refetchTemplates();
    } catch (error: any) {
      console.error('Create template error:', error);
      toast.error(error.response?.data?.message || 'Gagal membuat template');
    }
  };

  // Generate report from template
  const handleGenerateFromTemplate = async (template: ReportTemplate) => {
    setIsExporting(true);
    try {
      const response = await generateReport({
        type: template.report_type, // Changed from report_type
        format: 'pdf',
        start_date: filters.start_date,
        end_date: filters.end_date,
        filters: { columns: template.columns }, // Changed from columns to filters
      });

      console.log('DEBUG: Initial template report response:', response);
      toast.loading('Laporan dari template sedang diproses...');

      let report = response.report;

      // Only poll if not already completed
      if (report.status !== 'completed') {
        report = await waitForReportCompletion(report.id);
      }

      // If we have a direct download URL, use it
      const downloadUrl = response.download_url || report.download_url;
      if (downloadUrl) {
        window.open(downloadUrl, '_blank');
        toast.success(`Laporan dari template "${template.name}" sedang diunduh!`);
      } else {
        const blob = await downloadReport(report.id);
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', `${template.name}-${format(new Date(), 'yyyy-MM-dd')}.pdf`);

        document.body.appendChild(link);
        link.click();
        link.parentNode?.removeChild(link);
        window.URL.revokeObjectURL(url);

        toast.success(`Laporan dari template "${template.name}" berhasil diunduh!`);
      }

      refetchHistory();
    } catch (error: any) {
      console.error('Generate from template error:', error);
      toast.error(error.message || 'Gagal generate laporan dari template');
    } finally {
      setIsExporting(false);
    }
  };

  return (
    <div className="space-y-6 p-6">
      <PageHeader
        title="Laporan & Analitik"
        description="Analisis kehadiran, cuti, dan performa karyawan"
        icon={BarChart2}
        actions={
          <div className="flex items-center gap-2">
            <Select value={timeRange} onValueChange={setTimeRange}>
              <SelectTrigger className="w-[180px]">
                <SelectValue placeholder="Pilih periode" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="7">7 Hari Terakhir</SelectItem>
                <SelectItem value="30">30 Hari Terakhir</SelectItem>
                <SelectItem value="90">90 Hari Terakhir</SelectItem>
                <SelectItem value="180">6 Bulan Terakhir</SelectItem>
                <SelectItem value="365">1 Tahun Terakhir</SelectItem>
              </SelectContent>
            </Select>
            <Button variant="outline" size="sm" onClick={() => window.location.reload()}>
              <RefreshCw className="mr-2 h-4 w-4" />
              Refresh
            </Button>
          </div>
        }
      />

      {/* Summary Stats */}
      <StatsGrid stats={summaryStats} />

      {/* Main Content Tabs */}
      <Tabs value={activeTab} onValueChange={setActiveTab} className="space-y-6">
        <TabsList className="w-full h-12 p-1.5 gap-1 grid grid-cols-7">
          <TabsTrigger value="overview" className="py-2.5 text-sm font-medium">
            Overview
          </TabsTrigger>
          <TabsTrigger value="attendance" className="py-2.5 text-sm font-medium">
            Kehadiran
          </TabsTrigger>
          <TabsTrigger value="departments" className="py-2.5 text-sm font-medium">
            Departemen
          </TabsTrigger>
          <TabsTrigger value="leave" className="py-2.5 text-sm font-medium">
            Cuti
          </TabsTrigger>
          <TabsTrigger value="export" className="py-2.5 text-sm font-medium">
            Export
          </TabsTrigger>
          <TabsTrigger value="history" className="py-2.5 text-sm font-medium">
            <History className="mr-2 h-4 w-4" />
            Riwayat
          </TabsTrigger>
          <TabsTrigger value="templates" className="py-2.5 text-sm font-medium">
            <LayoutTemplate className="mr-2 h-4 w-4" />
            Template
          </TabsTrigger>
        </TabsList>

        {/* Overview Tab */}
        <TabsContent value="overview" className="space-y-4">
          <div className="grid gap-4 md:grid-cols-2">
            <Card>
              <CardHeader>
                <CardTitle>Trend Kehadiran Mingguan</CardTitle>
                <CardDescription>Data kehadiran 4 minggu terakhir</CardDescription>
              </CardHeader>
              <CardContent>
                {weeklyData && weeklyData.length > 0 ? (
                  <div className="space-y-3">
                    {weeklyData.map((week, idx) => (
                      <div key={idx} className="flex items-center justify-between">
                        <span className="text-sm text-muted-foreground">
                          {week.week}
                        </span>
                        <div className="flex items-center gap-2">
                          <div className="w-32 bg-muted rounded-full h-2">
                            <div
                              className="bg-primary h-2 rounded-full transition-all"
                              style={{ width: `${week.attendance_rate}%` }}
                            />
                          </div>
                          <span className="text-sm font-medium w-12">{week.attendance_rate}%</span>
                        </div>
                      </div>
                    ))}
                  </div>
                ) : (
                  <p className="text-sm text-muted-foreground text-center py-8">
                    Tidak ada data tersedia
                  </p>
                )}
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle>Kehadiran per Departemen</CardTitle>
                <CardDescription>Perbandingan antar departemen</CardDescription>
              </CardHeader>
              <CardContent>
                {departmentData && departmentData.length > 0 ? (
                  <div className="space-y-3">
                    {departmentData.slice(0, 5).map((dept) => (
                      <div key={dept.department} className="flex items-center justify-between">
                        <span className="text-sm font-medium">{dept.department}</span>
                        <div className="flex items-center gap-2">
                          <Badge variant={dept.attendance_rate >= 90 ? 'default' : 'secondary'}>
                            {dept.attendance_rate}%
                          </Badge>
                          <span className="text-xs text-muted-foreground">
                            {dept.employee_count} karyawan
                          </span>
                        </div>
                      </div>
                    ))}
                  </div>
                ) : (
                  <p className="text-sm text-muted-foreground text-center py-8">
                    Tidak ada data tersedia
                  </p>
                )}
              </CardContent>
            </Card>
          </div>
        </TabsContent>

        {/* Attendance Tab */}
        <TabsContent value="attendance" className="space-y-4">
          <Card>
            <CardHeader>
              <CardTitle>Data Kehadiran Bulanan</CardTitle>
              <CardDescription>Rincian kehadiran per bulan</CardDescription>
            </CardHeader>
            <CardContent>
              {monthlyData && monthlyData.length > 0 ? (
                <div className="space-y-4">
                  {monthlyData.map((month) => {
                    const total = (month.present || 0) + (month.late || 0) + (month.absent || 0);
                    const attendanceRate = total > 0
                      ? Math.round(((month.present || 0) + (month.late || 0)) / total * 100)
                      : 0;

                    return (<div key={month.month} className="border rounded-lg p-4">
                      <div className="flex items-center justify-between mb-3">
                        <h4 className="font-semibold">{month.month}</h4>
                        <Badge>{attendanceRate}% Kehadiran</Badge>
                      </div>
                      <div className="grid grid-cols-3 gap-4 text-sm">
                        <div>
                          <p className="text-muted-foreground">Hadir</p>
                          <p className="font-semibold text-green-600">{month.present}</p>
                        </div>
                        <div>
                          <p className="text-muted-foreground">Terlambat</p>
                          <p className="font-semibold text-yellow-600">{month.late}</p>
                        </div>
                        <div>
                          <p className="text-muted-foreground">Tidak Hadir</p>
                          <p className="font-semibold text-red-600">{month.absent}</p>
                        </div>
                      </div>
                    </div>);
                  })}
                </div>
              ) : (
                <p className="text-sm text-muted-foreground text-center py-12">
                  Tidak ada data kehadiran
                </p>
              )}
            </CardContent>
          </Card>
        </TabsContent>

        {/* Departments Tab */}
        <TabsContent value="departments" className="space-y-4">
          <Card>
            <CardHeader>
              <CardTitle>Statistik Departemen</CardTitle>
              <CardDescription>Performa kehadiran per departemen</CardDescription>
            </CardHeader>
            <CardContent>
              {departmentData && departmentData.length > 0 ? (
                <div className="space-y-3">
                  {departmentData.map((dept) => (
                    <div key={dept.department} className="border rounded-lg p-4">
                      <div className="flex items-center justify-between mb-2">
                        <h4 className="font-semibold">{dept.department}</h4>
                        <Badge variant={dept.attendance_rate >= 90 ? 'default' : 'destructive'}>
                          {dept.attendance_rate}%
                        </Badge>
                      </div>
                      <div className="grid grid-cols-3 gap-2 text-xs">
                        <div>
                          <p className="text-muted-foreground">Total</p>
                          <p className="font-medium">{dept.employee_count} karyawan</p>
                        </div>
                        <div>
                          <p className="text-muted-foreground">Hadir</p>
                          <p className="font-medium">{dept.present} kali</p>
                        </div>
                        <div>
                          <p className="text-muted-foreground">Terlambat</p>
                          <p className="font-medium">{dept.late} kali</p>
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
              ) : (
                <p className="text-sm text-muted-foreground text-center py-12">
                  Tidak ada data departemen
                </p>
              )}
            </CardContent>
          </Card>
        </TabsContent>

        {/* Leave Tab */}
        <TabsContent value="leave" className="space-y-4">
          <Card>
            <CardHeader>
              <CardTitle>Statistik Cuti</CardTitle>
              <CardDescription>Data pengajuan cuti karyawan</CardDescription>
            </CardHeader>
            <CardContent>
              {leaveData && leaveData.length > 0 ? (
                <div className="space-y-3">
                  {leaveData.map((leave) => (
                    <div key={leave.leave_type} className="flex items-center justify-between p-3 border rounded-lg">
                      <div>
                        <p className="font-medium">{leave.leave_type}</p>
                        <p className="text-xs text-muted-foreground">
                          {leave.count} pengajuan
                        </p>
                      </div>
                      <div className="text-right">
                        <p className="font-semibold">{leave.total_days} hari</p>
                        <p className="text-xs text-muted-foreground">
                          Disetujui
                        </p>
                      </div>
                    </div>
                  ))}
                </div>
              ) : (
                <p className="text-sm text-muted-foreground text-center py-12">
                  Tidak ada data cuti
                </p>
              )}
            </CardContent>
          </Card>
        </TabsContent>

        {/* Export Tab */}
        <TabsContent value="export" className="space-y-6">
          {/* Export Filters */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <Filter className="h-5 w-5" />
                Kustomisasi Laporan
              </CardTitle>
              <CardDescription>Pilih tipe laporan dan kolom yang ingin ditampilkan</CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
              {/* Report Type Selection */}
              <div className="space-y-3">
                <label className="text-sm font-semibold">Tipe Laporan</label>
                <Select value={reportType} onValueChange={(value) => setReportType(value as any)}>
                  <SelectTrigger className="h-11">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="attendance">
                      <div className="flex items-center gap-2">
                        <FileText className="h-4 w-4" />
                        <span>Laporan Kehadiran</span>
                      </div>
                    </SelectItem>
                    <SelectItem value="leave">
                      <div className="flex items-center gap-2">
                        <Calendar className="h-4 w-4" />
                        <span>Laporan Cuti</span>
                      </div>
                    </SelectItem>
                    <SelectItem value="payroll">
                      <div className="flex items-center gap-2">
                        <BarChart2 className="h-4 w-4" />
                        <span>Laporan Gaji</span>
                      </div>
                    </SelectItem>
                  </SelectContent>
                </Select>
              </div>

              {/* Quick Column Presets */}
              <div className="space-y-3">
                <label className="text-sm font-semibold">Preset Kolom</label>
                <div className="grid grid-cols-3 gap-3">
                  <Button
                    variant={selectedColumns.length === 4 ? 'default' : 'outline'}
                    className="h-20 flex-col gap-2"
                    onClick={() => setSelectedColumns(['employee_name', 'date', 'check_in', 'check_out'])}
                  >
                    <span className="font-semibold">Ringkas</span>
                    <span className="text-xs opacity-80">4 kolom</span>
                  </Button>
                  <Button
                    variant={selectedColumns.length === 6 ? 'default' : 'outline'}
                    className="h-20 flex-col gap-2"
                    onClick={() => setSelectedColumns(['employee_name', 'date', 'check_in', 'check_out', 'status', 'work_hours'])}
                  >
                    <span className="font-semibold">Standar</span>
                    <span className="text-xs opacity-80">6 kolom</span>
                  </Button>
                  <Button
                    variant={selectedColumns.length > 6 ? 'default' : 'outline'}
                    className="h-20 flex-col gap-2"
                    onClick={() => setSelectedColumns(['employee_name', 'date', 'check_in', 'check_out', 'status', 'work_hours', 'overtime', 'notes'])}
                  >
                    <span className="font-semibold">Lengkap</span>
                    <span className="text-xs opacity-80">8 kolom</span>
                  </Button>
                </div>
              </div>

              {/* Columns Selection Info */}
              <div className="space-y-3 pt-2 border-t">
                <label className="text-sm font-semibold">Kolom Terpilih ({selectedColumns.length})</label>
                <div className="flex flex-wrap gap-2">
                  {selectedColumns.map((col) => (
                    <Badge key={col} variant="secondary" className="text-xs px-3 py-1">
                      {col.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}
                    </Badge>
                  ))}
                </div>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <Download className="h-5 w-5" />
                Unduh Laporan
              </CardTitle>
              <CardDescription>Pilih format file untuk mengunduh laporan</CardDescription>
            </CardHeader>
            <CardContent>
              <div className="grid gap-4 md:grid-cols-2">
                <Button
                  size="lg"
                  className="h-24 flex-col gap-2"
                  onClick={() => handleExport('pdf')}
                  disabled={isExporting}
                >
                  <FileText className="h-8 w-8" />
                  <div className="flex flex-col items-center">
                    <span className="font-semibold">PDF</span>
                    <span className="text-xs opacity-90">Format dokumen portabel</span>
                  </div>
                </Button>

                <Button
                  size="lg"
                  variant="outline"
                  className="h-24 flex-col gap-2"
                  onClick={() => handleExport('excel')}
                  disabled={isExporting}
                >
                  <FileText className="h-8 w-8" />
                  <div className="flex flex-col items-center">
                    <span className="font-semibold">Excel</span>
                    <span className="text-xs opacity-90">Format spreadsheet</span>
                  </div>
                </Button>
              </div>

              {isExporting && (
                <div className="mt-4 flex items-center justify-center gap-2 text-sm text-muted-foreground">
                  <Loader2 className="h-4 w-4 animate-spin" />
                  <span>Sedang memproses laporan...</span>
                </div>
              )}
            </CardContent>
          </Card>
        </TabsContent>

        {/* History Tab - Riwayat Laporan */}
        <TabsContent value="history" className="space-y-4">
          <Card>
            <CardHeader>
              <div className="flex items-center justify-between">
                <div>
                  <CardTitle className="flex items-center gap-2">
                    <History className="h-5 w-5" />
                    Riwayat Laporan
                  </CardTitle>
                  <CardDescription>Laporan yang pernah di-generate sebelumnya</CardDescription>
                </div>
                <Button size="sm" onClick={() => refetchHistory()}>
                  <RefreshCw className="mr-2 h-4 w-4" />
                  Refresh
                </Button>
              </div>
            </CardHeader>
            <CardContent>
              {generatedReports && generatedReports.length > 0 ? (
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                  {generatedReports.map((report) => (
                    <Card key={report.id} className="hover:shadow-md transition-shadow">
                      <CardHeader className="pb-3">
                        <div className="flex items-center gap-3">
                          <div className="p-2 rounded-lg bg-primary/10">
                            <FileText className="h-5 w-5 text-primary" />
                          </div>
                          <div className="flex-1 min-w-0">
                            <h4 className="font-semibold text-sm capitalize truncate">
                              {report.report_type}
                            </h4>
                            <p className="text-xs text-muted-foreground">
                              {report.created_at ? format(new Date(report.created_at), 'dd MMM yyyy HH:mm', { locale: id }) : 'N/A'}
                            </p>
                          </div>
                        </div>
                      </CardHeader>
                      <CardContent className="space-y-3">
                        <div className="flex items-center gap-2">
                          <Badge variant="outline" className="text-xs">
                            {report.format?.toUpperCase()}
                          </Badge>
                          <Badge variant={report.status === 'completed' ? 'default' : 'secondary'} className="text-xs">
                            {report.status || 'completed'}
                          </Badge>
                        </div>
                        <Button
                          className="w-full"
                          size="sm"
                          variant="outline"
                          onClick={() => handleDownloadHistory(report.id, `${report.report_type}-${report.id}.${report.format}`)}
                        >
                          <Download className="mr-2 h-4 w-4" />
                          Unduh
                        </Button>
                      </CardContent>
                    </Card>
                  ))}
                </div>
              ) : (
                <div className="text-center py-12">
                  <FileText className="h-12 w-12 text-muted-foreground mx-auto mb-4" />
                  <p className="text-muted-foreground font-medium">Belum ada riwayat laporan</p>
                  <p className="text-sm text-muted-foreground mt-1">
                    Generate laporan baru di tab Export
                  </p>
                </div>
              )}
            </CardContent>
          </Card>
        </TabsContent>

        {/* Templates Tab - Template Laporan */}
        <TabsContent value="templates" className="space-y-4">
          <Card>
            <CardHeader>
              <div className="flex items-center justify-between">
                <div>
                  <CardTitle className="flex items-center gap-2">
                    <LayoutTemplate className="h-5 w-5" />
                    Template Laporan
                  </CardTitle>
                  <CardDescription>Kelola template laporan custom</CardDescription>
                </div>
                <Button size="sm" onClick={handleCreateTemplate}>
                  <Plus className="mr-2 h-4 w-4" />
                  Buat Template Baru
                </Button>
              </div>
            </CardHeader>
            <CardContent>
              {templates && templates.length > 0 ? (
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                  {templates.map((template) => (
                    <Card key={template.id} className="hover:shadow-md transition-shadow">
                      <CardHeader className="pb-3">
                        <div className="flex items-start justify-between">
                          <div className="flex items-center gap-2">
                            <LayoutTemplate className="h-4 w-4 text-primary" />
                            <CardTitle className="text-base">{template.name}</CardTitle>
                          </div>
                          <div className="flex gap-1">
                            <Button
                              size="sm"
                              variant="ghost"
                              className="h-8 w-8 p-0"
                              onClick={() => toast.info('Fitur edit template segera hadir')}
                            >
                              <Edit className="h-4 w-4" />
                            </Button>
                            <Button
                              size="sm"
                              variant="ghost"
                              className="h-8 w-8 p-0 text-destructive"
                              onClick={() => handleDeleteTemplate(template.id, template.name)}
                            >
                              <Trash2 className="h-4 w-4" />
                            </Button>
                          </div>
                        </div>
                      </CardHeader>
                      <CardContent className="space-y-3">
                        <p className="text-sm text-muted-foreground line-clamp-2">
                          {template.description || 'Tidak ada deskripsi'}
                        </p>
                        <div className="flex items-center gap-2">
                          <Badge variant="outline">{template.report_type}</Badge>
                          <span className="text-xs text-muted-foreground">
                            {template.columns?.length || 0} kolom
                          </span>
                        </div>
                        <Button
                          className="w-full"
                          size="sm"
                          onClick={() => handleGenerateFromTemplate(template)}
                          disabled={isExporting}
                        >
                          {isExporting ? (
                            <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                          ) : (
                            <Eye className="mr-2 h-4 w-4" />
                          )}
                          {isExporting ? 'Memproses...' : 'Generate'}
                        </Button>
                      </CardContent>
                    </Card>
                  ))}
                </div>
              ) : (
                <div className="text-center py-12">
                  <LayoutTemplate className="h-12 w-12 text-muted-foreground mx-auto mb-4" />
                  <p className="text-muted-foreground">Belum ada template</p>
                  <p className="text-sm text-muted-foreground mt-1 mb-4">
                    Buat template untuk mempercepat pembuatan laporan
                  </p>
                  <Button onClick={handleCreateTemplate}>
                    <Plus className="mr-2 h-4 w-4" />
                    Buat Template Pertama
                  </Button>
                </div>
              )}
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>

      {/* Create Template Dialog */}
      <Dialog open={isTemplateDialogOpen} onOpenChange={setIsTemplateDialogOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Buat Template Baru</DialogTitle>
            <DialogDescription>
              Template akan menggunakan kolom yang sedang dipilih di tab Export
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-4 py-4">
            <div className="space-y-2">
              <Label htmlFor="template-name">Nama Template *</Label>
              <Input
                id="template-name"
                placeholder="Contoh: Laporan Kehadiran Bulanan"
                value={templateName}
                onChange={(e) => setTemplateName(e.target.value)}
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="template-description">Deskripsi</Label>
              <Textarea
                id="template-description"
                placeholder="Deskripsi singkat tentang template ini..."
                value={templateDescription}
                onChange={(e) => setTemplateDescription(e.target.value)}
                rows={3}
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="template-type">Tipe Laporan</Label>
              <Select value={templateType} onValueChange={(value) => setTemplateType(value as any)}>
                <SelectTrigger id="template-type">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="attendance">Laporan Kehadiran</SelectItem>
                  <SelectItem value="leave">Laporan Cuti</SelectItem>
                  <SelectItem value="payroll">Laporan Gaji</SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-2">
              <Label>Kolom yang Akan Disimpan</Label>
              <div className="flex flex-wrap gap-1 p-3 border rounded-md bg-muted/30">
                {selectedColumns.map((col) => (
                  <Badge key={col} variant="secondary" className="text-xs">
                    {col.replace(/_/g, ' ')}
                  </Badge>
                ))}
              </div>
              <p className="text-xs text-muted-foreground">
                Ubah kolom di tab Export untuk menyesuaikan
              </p>
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setIsTemplateDialogOpen(false)}>
              Batal
            </Button>
            <Button onClick={handleSubmitTemplate}>
              <Plus className="mr-2 h-4 w-4" />
              Buat Template
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
