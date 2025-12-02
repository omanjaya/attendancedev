import { useState } from 'react';
import { useNavigate } from '@tanstack/react-router';
import {
    ChevronLeft,
    FileText,
    BarChart2,
    PieChart,
    Download,
    Calendar,
    Loader2,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { toast } from 'sonner';
import { generateReport, downloadReport, waitForReportCompletion } from '@/lib/api/reports';
import type { ReportType } from '@/types/reports';
import { format, subMonths } from 'date-fns';

export function MobileReportsPage() {
    const navigate = useNavigate();
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
            const initialReport = await generateReport({
                type: reportType, // Changed from report_type
                format: 'pdf',
                start_date: format(startDate, 'yyyy-MM-dd'),
                end_date: format(endDate, 'yyyy-MM-dd'),
                filters: { columns: ['employee_name', 'date', 'check_in', 'check_out', 'status', 'work_hours'] }, // Changed to filters
            });

            console.log('DEBUG: Initial mobile report response:', initialReport);
            toast.loading('Laporan sedang diproses. Mohon tunggu...');

            let report = initialReport;

            // Only poll if not already completed
            if (initialReport.status !== 'completed') {
                report = await waitForReportCompletion(initialReport.id);
            }

            // If we have a direct download URL, use it
            if (report.download_url) {
                window.open(report.download_url, '_blank');
                toast.success(`${reportTitle} sedang diunduh!`);
            } else {
                // Download the generated report
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
        <div className="min-h-screen bg-gradient-to-b from-muted/30 via-background to-background dark:from-gray-950 dark:via-gray-900 dark:to-gray-900 pb-20">
            {/* Header */}
            <div className="px-4 pt-3 pb-3 sticky top-0 z-20">
                <div className="bg-card/80 dark:bg-card/60 backdrop-blur-md rounded-3xl p-1.5 shadow-xl border border-border/40 dark:border-border/30">
                    <div className="bg-gradient-to-r from-indigo-600 to-purple-600 dark:from-indigo-900 dark:to-purple-800 px-4 py-3 rounded-[20px] flex items-center gap-3 shadow-lg">
                        <button
                            onClick={() => navigate({ to: '/admin/dashboard' })}
                            className="p-2 -ml-2 hover:bg-white/10 rounded-full transition-colors active:scale-95"
                        >
                            <ChevronLeft className="h-5 w-5 text-white" />
                        </button>
                        <h1 className="text-base font-bold text-white flex-1">Laporan</h1>
                    </div>
                </div>
            </div>

            {/* Report Cards - 2x2 Grid (Touch-Friendly) */}
            <div className="px-4 mt-4 space-y-3">
                <div className="grid grid-cols-2 gap-3">
                    {reports.map((report) => (
                        <button
                            key={report.id}
                            onClick={() => handleExport(report.reportType, report.title)}
                            disabled={isExporting}
                            className="bg-gradient-to-br from-white to-gray-50 dark:from-gray-900 dark:to-gray-800 p-5 rounded-3xl border border-border/50 shadow-lg active:scale-95 transition-all disabled:opacity-60 min-h-[160px] flex flex-col items-start text-left"
                        >
                            <div className={`h-12 w-12 rounded-2xl ${report.color} flex items-center justify-center mb-4 shadow-sm`}>
                                <report.icon className="h-6 w-6" />
                            </div>
                            <h3 className="font-bold text-sm mb-1 text-foreground">{report.title}</h3>
                            <p className="text-xs text-muted-foreground mb-auto">30 hari terakhir</p>
                            <div className="w-full flex items-center justify-center gap-2 mt-3 pt-3 border-t border-border/30">
                                {isExporting ? (
                                    <>
                                        <Loader2 className="h-4 w-4 animate-spin text-primary" />
                                        <span className="text-xs font-medium text-primary">Memproses...</span>
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
