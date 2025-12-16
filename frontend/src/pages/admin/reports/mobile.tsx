import { useState } from 'react';
import {
    FileText,
    BarChart2,
    PieChart,
    Download,
    Calendar,
    Loader2,
} from 'lucide-react';
import { MobilePageHeader } from '@/components/mobile';

import { toast } from 'sonner';
import { generateReport, downloadReport, waitForReportCompletion } from '@/lib/api/reports';
import type { ReportType } from '@/types/reports';
import { format, subMonths } from 'date-fns';

export function MobileReportsPage() {
    const [isExporting, setIsExporting] = useState(false);

    const reports = [
        { id: 'attendance', title: 'Laporan Kehadiran', icon: FileText, color: 'text-blue-600 bg-blue-50 dark:bg-blue-900/20', reportType: 'attendance' as const },
        { id: 'leave', title: 'Laporan Cuti', icon: Calendar, color: 'text-orange-600 bg-orange-50 dark:bg-orange-900/20', reportType: 'leave' as const },
        { id: 'payroll', title: 'Laporan Gaji', icon: BarChart2, color: 'text-emerald-600 bg-emerald-50 dark:bg-emerald-900/20', reportType: 'payroll' as const },
        { id: 'performance', title: 'Laporan Performa', icon: PieChart, color: 'text-violet-600 bg-violet-50 dark:bg-violet-900/20', reportType: 'attendance' as const },
    ];

    // Calculate date range (last 30 days)
    const endDate = new Date();
    const startDate = subMonths(endDate, 1);

    const handleExport = async (reportType: ReportType, reportTitle: string) => {
        setIsExporting(true);
        try {
            // Generate the report (default PDF format for mobile)
            const response = await generateReport({
                type: reportType, // Changed from report_type
                format: 'pdf',
                start_date: format(startDate, 'yyyy-MM-dd'),
                end_date: format(endDate, 'yyyy-MM-dd'),
                filters: { columns: ['employee_name', 'date', 'check_in', 'check_out', 'status', 'work_hours'] }, // Changed to filters
            });

            console.log('DEBUG: Initial mobile report response:', response);
            toast.loading('Laporan sedang diproses. Mohon tunggu...');

            // Handle nested response structure: { report: {...}, download_url: ... }
            const reportData = response.report;
            const downloadUrl = response.download_url || reportData.download_url;

            let report = reportData;

            // Only poll if not already completed
            if (report.status !== 'completed') {
                report = await waitForReportCompletion(report.id);
            }

            // If we have a direct download URL, use it
            if (downloadUrl) {
                window.open(downloadUrl, '_blank');
                toast.success(`${reportTitle} sedang diunduh!`);
            } else if (report.download_url) {
                window.open(report.download_url, '_blank');
                toast.success(`${reportTitle} sedang diunduh!`);
            } else {
                // Fallback: Download the generated report
                const blob = await downloadReport(report.id);

                // Create download link
                const url = window.URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;

                const filename = `${reportType}-${format(startDate, 'yyyy-MM-dd')}-${format(endDate, 'yyyy-MM-dd')}.pdf`;
                link.setAttribute('download', filename);

                // Trigger download
                document.body.appendChild(link);
                link.click();

                // Cleanup
                link.parentNode?.removeChild(link);
                window.URL.revokeObjectURL(url);

                toast.success(`${reportTitle} berhasil diunduh!`);
            }
        } catch (error: any) {
            console.error('Export error:', error);
            toast.error(error.message || error.response?.data?.message || 'Gagal membuat laporan. Silakan coba lagi.');
        } finally {
            setIsExporting(false);
        }
    };

    return (
        <div className="min-h-screen bg-background pb-24">
            <MobilePageHeader
                title="Laporan"
                backTo="/admin/dashboard"
                gradient="rose"
            />

            <div className="px-4 space-y-4">
                <div className="grid grid-cols-2 gap-3">
                    {reports.map((report) => (
                        <button
                            key={report.id}
                            onClick={() => handleExport(report.reportType, report.title)}
                            disabled={isExporting}
                            className="bg-card rounded-2xl shadow-sm dark:border dark:border-border/50 p-4 active:scale-95 transition-all disabled:opacity-60 min-h-[140px] flex flex-col items-start text-left"
                        >
                            <div className={`h-10 w-10 rounded-xl ${report.color} flex items-center justify-center mb-3`}>
                                <report.icon className="h-5 w-5" />
                            </div>
                            <h3 className="font-bold text-sm mb-1 text-foreground">{report.title}</h3>
                            <p className="text-xs text-muted-foreground mb-auto">30 hari terakhir</p>
                            <div className="w-full flex items-center justify-center gap-2 mt-3 pt-3 border-t border-border/50">
                                {isExporting ? (
                                    <>
                                        <Loader2 className="h-4 w-4 animate-spin text-rose-600" />
                                        <span className="text-xs font-medium text-rose-600">Memproses...</span>
                                    </>
                                ) : (
                                    <>
                                        <Download className="h-4 w-4 text-muted-foreground" />
                                        <span className="text-xs font-medium text-muted-foreground">Unduh PDF</span>
                                    </>
                                )}
                            </div>
                        </button>
                    ))}
                </div>
            </div>
        </div>
    );
}
