import { useState } from 'react';
import { useNavigate } from '@tanstack/react-router';
import {
  FileText,
  Calendar,
  Wallet,
  Download,
  ChevronRight,
  Clock,
  TrendingUp,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { MobilePageHeader } from '@/components/mobile';

interface ReportCard {
  id: string;
  title: string;
  icon: typeof FileText;
  color: string;
  description: string;
  iconColor: string;
  action: () => void;
}

export function MobileEmployeeReportsPage() {
  const navigate = useNavigate();
  const [downloading, setDownloading] = useState<string | null>(null);

  const handleDownload = async (reportId: string) => {
    setDownloading(reportId);
    // Simulate download
    await new Promise((resolve) => setTimeout(resolve, 1500));
    setDownloading(null);
  };

  const reports: ReportCard[] = [
    {
      id: 'attendance',
      title: 'Laporan Kehadiran',
      icon: Clock,
      iconColor: 'text-emerald-600 dark:text-emerald-400',
      color: 'bg-emerald-50 dark:bg-emerald-950/50 border-emerald-100 dark:border-emerald-900',
      description: 'Riwayat kehadiran Anda',
      action: () => navigate({ to: '/employee/attendance' }),
    },
    {
      id: 'leave',
      title: 'Laporan Cuti',
      icon: Calendar,
      iconColor: 'text-sky-600 dark:text-sky-400',
      color: 'bg-sky-50 dark:bg-sky-950/50 border-sky-100 dark:border-sky-900',
      description: 'Riwayat pengajuan cuti',
      action: () => navigate({ to: '/employee/leave' }),
    },
    {
      id: 'payroll',
      title: 'Slip Gaji',
      icon: Wallet,
      iconColor: 'text-violet-600 dark:text-violet-400',
      color: 'bg-violet-50 dark:bg-violet-950/50 border-violet-100 dark:border-violet-900',
      description: 'Riwayat slip gaji',
      action: () => navigate({ to: '/employee/payroll' }),
    },
  ];

  return (
    <div className="min-h-screen bg-background pb-24">
      <MobilePageHeader title="Laporan" gradient="rose" backTo="/employee/dashboard" />

      <div className="px-4 space-y-4">
        {/* Quick Stats */}
        <div className="grid grid-cols-2 gap-3">
          <div className="bg-emerald-50 dark:bg-emerald-900/20 rounded-2xl shadow-sm dark:border dark:border-border/50 p-4">
            <div className="flex items-center gap-2 mb-1">
              <div className="bg-emerald-500/10 rounded-lg p-1.5">
                <TrendingUp className="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
              </div>
              <span className="text-xs text-muted-foreground">Bulan Ini</span>
            </div>
            <p className="text-2xl font-bold text-foreground">-</p>
            <p className="text-xs text-muted-foreground mt-0.5">Hari Hadir</p>
          </div>

          <div className="bg-teal-50 dark:bg-teal-900/20 rounded-2xl shadow-sm dark:border dark:border-border/50 p-4">
            <div className="flex items-center gap-2 mb-1">
              <div className="bg-teal-500/10 rounded-lg p-1.5">
                <Calendar className="h-4 w-4 text-teal-600 dark:text-teal-400" />
              </div>
              <span className="text-xs text-muted-foreground">Sisa Cuti</span>
            </div>
            <p className="text-2xl font-bold text-foreground">-</p>
            <p className="text-xs text-muted-foreground mt-0.5">Hari</p>
          </div>
        </div>

        {/* Reports Section */}
        <div className="space-y-3">
          <h2 className="text-sm font-bold text-foreground">Laporan Tersedia</h2>

          {reports.map((report) => {
            const Icon = report.icon;
            const isDownloading = downloading === report.id;

            return (
              <div
                key={report.id}
                className="bg-card rounded-2xl shadow-sm dark:border dark:border-border/50 p-4"
              >
                <div className="flex items-start gap-4">
                  <div className={`rounded-xl ${report.color} p-3`}>
                    <Icon className={`h-6 w-6 ${report.iconColor}`} />
                  </div>

                  <div className="flex-1 min-w-0">
                    <h3 className="font-bold text-base mb-1 text-foreground">
                      {report.title}
                    </h3>
                    <p className="text-xs text-muted-foreground mb-3">
                      {report.description}
                    </p>

                    <div className="flex gap-2">
                      <Button
                        size="sm"
                        variant="outline"
                        onClick={() => handleDownload(report.id)}
                        disabled={isDownloading}
                        className="h-8 text-xs"
                      >
                        <Download className="h-3.5 w-3.5 mr-1.5" />
                        {isDownloading ? 'Mengunduh...' : 'Unduh PDF'}
                      </Button>

                      <Button
                        size="sm"
                        onClick={report.action}
                        className="h-8 text-xs"
                      >
                        Lihat Detail
                        <ChevronRight className="h-3.5 w-3.5 ml-1" />
                      </Button>
                    </div>
                  </div>
                </div>
              </div>
            );
          })}
        </div>

        {/* Info Footer */}
        <div className="bg-muted/30 rounded-2xl shadow-sm dark:border dark:border-border/50 p-4">
          <div className="flex items-start gap-3">
            <div className="bg-rose-100 dark:bg-rose-900/30 rounded-lg p-2 mt-0.5">
              <FileText className="h-4 w-4 text-rose-600 dark:text-rose-400" />
            </div>
            <div className="flex-1">
              <h3 className="font-semibold text-sm mb-1">Informasi</h3>
              <p className="text-xs text-muted-foreground leading-relaxed">
                Semua laporan dapat diunduh dalam format PDF. Data yang ditampilkan adalah data pribadi Anda.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
