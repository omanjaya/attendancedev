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
import { Card } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { useAuthStore } from '@/stores';

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
  const { user } = useAuthStore();
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

  // Get user initials for avatar
  const getInitials = (name: string) => {
    return name
      .split(' ')
      .map((n) => n[0])
      .join('')
      .toUpperCase()
      .slice(0, 2);
  };

  return (
    <div className="min-h-screen bg-gradient-to-b from-muted/30 via-background to-background dark:from-gray-950 dark:via-gray-900 dark:to-gray-900 pb-20">
      {/* Header Wrapper */}
      <div className="px-4 pt-3 pb-3">
        <div className="bg-card/80 dark:bg-card/60 backdrop-blur-md rounded-3xl p-1.5 shadow-xl border border-border/40 dark:border-border/30">
          {/* Header Section */}
          <div className="bg-gradient-to-br from-primary/95 via-primary to-primary/90 dark:from-primary/90 dark:via-primary/95 dark:to-primary px-5 pt-8 pb-4 space-y-2.5 shadow-lg shadow-primary/10 rounded-[20px]">
            {/* User Info */}
            <div className="flex items-center gap-3">
              <div className="h-11 w-11 rounded-full bg-gradient-to-br from-white/20 to-white/10 backdrop-blur-sm flex items-center justify-center text-white font-bold shadow-lg border-2 border-white/20">
                {user?.name ? getInitials(user.name) : 'U'}
              </div>
              <div className="flex-1">
                <p className="text-xs text-white/70">Laporan Pribadi</p>
                <p className="text-white font-semibold text-sm">
                  {user?.name || 'User'}
                </p>
              </div>
            </div>

            {/* Info Card */}
            <div className="bg-white/10 backdrop-blur-md rounded-xl px-3 py-2.5 border border-white/20 shadow-sm">
              <div className="flex items-center gap-2">
                <FileText className="h-4 w-4 text-white/80" />
                <span className="text-xs text-white/90">
                  Akses laporan kehadiran, cuti, dan slip gaji Anda
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Main Content */}
      <div className="px-4 pb-4 space-y-4">
        {/* Quick Stats */}
        <div className="grid grid-cols-2 gap-3">
          <Card className="p-4 border-border/50 rounded-2xl bg-gradient-to-br from-card to-card/80">
            <div className="flex items-center gap-2 mb-1">
              <div className="bg-emerald-500/10 rounded-lg p-1.5">
                <TrendingUp className="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
              </div>
              <span className="text-xs text-muted-foreground">Bulan Ini</span>
            </div>
            <p className="text-2xl font-bold">-</p>
            <p className="text-xs text-muted-foreground mt-0.5">Hari Hadir</p>
          </Card>

          <Card className="p-4 border-border/50 rounded-2xl bg-gradient-to-br from-card to-card/80">
            <div className="flex items-center gap-2 mb-1">
              <div className="bg-sky-500/10 rounded-lg p-1.5">
                <Calendar className="h-4 w-4 text-sky-600 dark:text-sky-400" />
              </div>
              <span className="text-xs text-muted-foreground">Sisa Cuti</span>
            </div>
            <p className="text-2xl font-bold">-</p>
            <p className="text-xs text-muted-foreground mt-0.5">Hari</p>
          </Card>
        </div>

        {/* Reports Section */}
        <div className="space-y-3">
          <h2 className="text-sm font-bold text-foreground px-1">Laporan Tersedia</h2>

          {reports.map((report) => {
            const Icon = report.icon;
            const isDownloading = downloading === report.id;

            return (
              <Card
                key={report.id}
                className={`p-4 border rounded-2xl shadow-lg ${report.color} transition-all`}
              >
                <div className="flex items-start gap-4">
                  <div className={`rounded-xl bg-white/80 dark:bg-gray-900/80 p-3 border border-white/50 dark:border-gray-800`}>
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
                        className="h-8 text-xs bg-white/50 dark:bg-gray-900/50 hover:bg-white dark:hover:bg-gray-900"
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
              </Card>
            );
          })}
        </div>

        {/* Info Footer */}
        <Card className="p-4 border-border/50 rounded-2xl bg-muted/30">
          <div className="flex items-start gap-3">
            <div className="bg-primary/10 rounded-lg p-2 mt-0.5">
              <FileText className="h-4 w-4 text-primary" />
            </div>
            <div className="flex-1">
              <h3 className="font-semibold text-sm mb-1">Informasi</h3>
              <p className="text-xs text-muted-foreground leading-relaxed">
                Semua laporan dapat diunduh dalam format PDF. Data yang ditampilkan adalah data pribadi Anda.
              </p>
            </div>
          </div>
        </Card>
      </div>
    </div>
  );
}
