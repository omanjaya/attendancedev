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
import { PageLayout } from '@/components/shared/PageLayout';

interface ReportCard {
  id: string;
  title: string;
  icon: typeof FileText;
  color: string;
  description: string;
  iconColor: string;
  action: () => void;
}

export function DesktopEmployeeReportsPage() {
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
      description: 'Riwayat kehadiran dan absensi Anda',
      action: () => navigate({ to: '/employee/attendance' }),
    },
    {
      id: 'leave',
      title: 'Laporan Cuti',
      icon: Calendar,
      iconColor: 'text-sky-600 dark:text-sky-400',
      color: 'bg-sky-50 dark:bg-sky-950/50 border-sky-100 dark:border-sky-900',
      description: 'Riwayat pengajuan dan persetujuan cuti',
      action: () => navigate({ to: '/employee/leave' }),
    },
    {
      id: 'payroll',
      title: 'Slip Gaji',
      icon: Wallet,
      iconColor: 'text-violet-600 dark:text-violet-400',
      color: 'bg-violet-50 dark:bg-violet-950/50 border-violet-100 dark:border-violet-900',
      description: 'Riwayat slip gaji dan penggajian',
      action: () => navigate({ to: '/employee/payroll' }),
    },
  ];

  return (
    <PageLayout title="Laporan Pribadi" description="Akses laporan kehadiran, cuti, dan slip gaji Anda">

      <div className="space-y-6">
        {/* Quick Stats */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <Card className="p-6">
            <div className="flex items-center gap-3">
              <div className="bg-emerald-500/10 rounded-xl p-3">
                <TrendingUp className="h-6 w-6 text-emerald-600 dark:text-emerald-400" />
              </div>
              <div className="flex-1">
                <p className="text-sm text-muted-foreground">Kehadiran Bulan Ini</p>
                <p className="text-2xl font-bold">-</p>
              </div>
            </div>
          </Card>

          <Card className="p-6">
            <div className="flex items-center gap-3">
              <div className="bg-sky-500/10 rounded-xl p-3">
                <Calendar className="h-6 w-6 text-sky-600 dark:text-sky-400" />
              </div>
              <div className="flex-1">
                <p className="text-sm text-muted-foreground">Sisa Cuti</p>
                <p className="text-2xl font-bold">-</p>
              </div>
            </div>
          </Card>

          <Card className="p-6">
            <div className="flex items-center gap-3">
              <div className="bg-violet-500/10 rounded-xl p-3">
                <Wallet className="h-6 w-6 text-violet-600 dark:text-violet-400" />
              </div>
              <div className="flex-1">
                <p className="text-sm text-muted-foreground">Gaji Bulan Ini</p>
                <p className="text-2xl font-bold">-</p>
              </div>
            </div>
          </Card>
        </div>

        {/* Reports Grid */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {reports.map((report) => {
            const Icon = report.icon;
            const isDownloading = downloading === report.id;

            return (
              <Card
                key={report.id}
                className={`p-6 border rounded-2xl ${report.color} transition-all hover:shadow-lg`}
              >
                <div className="space-y-4">
                  <div className="flex items-start justify-between">
                    <div className="rounded-xl bg-white/80 dark:bg-gray-900/80 p-3 border border-white/50 dark:border-gray-800">
                      <Icon className={`h-7 w-7 ${report.iconColor}`} />
                    </div>
                  </div>

                  <div>
                    <h3 className="font-bold text-lg mb-2 text-foreground">
                      {report.title}
                    </h3>
                    <p className="text-sm text-muted-foreground">
                      {report.description}
                    </p>
                  </div>

                  <div className="flex gap-2">
                    <Button
                      size="sm"
                      variant="outline"
                      onClick={() => handleDownload(report.id)}
                      disabled={isDownloading}
                      className="flex-1 bg-white/50 dark:bg-gray-900/50 hover:bg-white dark:hover:bg-gray-900"
                    >
                      <Download className="h-4 w-4 mr-2" />
                      {isDownloading ? 'Mengunduh...' : 'Unduh PDF'}
                    </Button>

                    <Button size="sm" onClick={report.action} className="flex-1">
                      Lihat Detail
                      <ChevronRight className="h-4 w-4 ml-2" />
                    </Button>
                  </div>
                </div>
              </Card>
            );
          })}
        </div>

        {/* Info Card */}
        <Card className="p-6 bg-muted/30">
          <div className="flex items-start gap-4">
            <div className="bg-primary/10 rounded-xl p-3">
              <FileText className="h-6 w-6 text-primary" />
            </div>
            <div className="flex-1">
              <h3 className="font-semibold text-base mb-2">Informasi Laporan</h3>
              <p className="text-sm text-muted-foreground leading-relaxed">
                Semua laporan dapat diunduh dalam format PDF. Data yang ditampilkan adalah data pribadi Anda.
                Untuk informasi lebih detail, silakan klik tombol "Lihat Detail" pada setiap laporan.
              </p>
            </div>
          </div>
        </Card>
      </div>
    </PageLayout>
  );
}
