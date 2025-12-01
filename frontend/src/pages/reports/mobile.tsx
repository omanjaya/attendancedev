import { useNavigate } from '@tanstack/react-router';
import {
    ChevronLeft,
    FileText,
    BarChart2,
    PieChart,
    Download,
    Calendar
} from 'lucide-react';
import { Button } from '@/components/ui/button';

export function MobileReportsPage() {
    const navigate = useNavigate();

    const reports = [
        { id: 'attendance', title: 'Laporan Kehadiran', icon: FileText, color: 'text-blue-600 bg-blue-50 dark:bg-blue-900/20' },
        { id: 'leave', title: 'Laporan Cuti', icon: Calendar, color: 'text-orange-600 bg-orange-50 dark:bg-orange-900/20' },
        { id: 'payroll', title: 'Laporan Gaji', icon: BarChart2, color: 'text-emerald-600 bg-emerald-50 dark:bg-emerald-900/20' },
        { id: 'performance', title: 'Laporan Performa', icon: PieChart, color: 'text-violet-600 bg-violet-50 dark:bg-violet-900/20' },
    ];

    return (
        <div className="min-h-screen bg-gradient-to-b from-muted/30 via-background to-background dark:from-gray-950 dark:via-gray-900 dark:to-gray-900 pb-20">
            {/* Header */}
            <div className="px-4 pt-3 pb-3 sticky top-0 z-20">
                <div className="bg-card/80 dark:bg-card/60 backdrop-blur-md rounded-3xl p-1.5 shadow-xl border border-border/40 dark:border-border/30">
                    <div className="bg-gradient-to-r from-indigo-600 to-purple-600 dark:from-indigo-900 dark:to-purple-800 px-4 py-3 rounded-[20px] flex items-center gap-3 shadow-lg">
                        <button
                            onClick={() => navigate({ to: '/dashboard' })}
                            className="p-2 -ml-2 hover:bg-white/10 rounded-full transition-colors active:scale-95"
                        >
                            <ChevronLeft className="h-5 w-5 text-white" />
                        </button>
                        <h1 className="text-base font-bold text-white flex-1">Laporan</h1>
                    </div>
                </div>
            </div>

            <div className="px-4 mt-4 space-y-4">
                <div className="grid grid-cols-2 gap-3">
                    {reports.map((report) => (
                        <div
                            key={report.id}
                            className="bg-white dark:bg-gray-900 p-4 rounded-2xl border border-border/50 shadow-sm active:scale-[0.98] transition-transform"
                        >
                            <div className={`h-10 w-10 rounded-xl ${report.color} flex items-center justify-center mb-3`}>
                                <report.icon className="h-5 w-5" />
                            </div>
                            <h3 className="font-bold text-sm mb-1">{report.title}</h3>
                            <p className="text-xs text-muted-foreground mb-3">Lihat detail laporan</p>
                            <Button size="sm" variant="outline" className="w-full h-8 text-xs">
                                <Download className="h-3 w-3 mr-1.5" />
                                Unduh
                            </Button>
                        </div>
                    ))}
                </div>
            </div>
        </div>
    );
}
