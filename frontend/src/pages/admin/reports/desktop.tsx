import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import {
  BarChart2,
  History,
  Download,
  RefreshCw,
  FileText,
  CalendarDays,
  GraduationCap,
} from 'lucide-react';
import { PageHeader } from '@/components/shared';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Badge } from '@/components/ui/badge';
import { toast } from 'sonner';
import { getGeneratedReports, downloadReport } from '@/lib/api/reports';
import { format } from 'date-fns';
import { id } from 'date-fns/locale';
import { MonthlyRecapTab } from './tabs/MonthlyRecapTab';
import { TeachingScheduleTab } from './tabs/TeachingScheduleTab';

export function DesktopReportsPage() {
  const [activeTab, setActiveTab] = useState('recap');

  // Fetch generated reports history
  const { data: generatedReports, refetch: refetchHistory } = useQuery({
    queryKey: ['reports', 'generated'],
    queryFn: getGeneratedReports,
  });

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

      toast.dismiss();
      toast.success('Laporan berhasil diunduh!');
    } catch (error: any) {
      console.error('Download error:', error);
      toast.dismiss();
      toast.error('Gagal mengunduh laporan');
    }
  };

  return (
    <div className="space-y-6 p-6">
      <PageHeader
        title="Laporan Kehadiran"
        description="Rekap kehadiran bulanan dengan status A/I/S/D/C"
        icon={BarChart2}
      />

      {/* Main Content Tabs */}
      <Tabs value={activeTab} onValueChange={setActiveTab} className="space-y-6">
        <TabsList className="w-full max-w-2xl h-12 p-1.5 gap-1 grid grid-cols-3">
          <TabsTrigger value="recap" className="py-2.5 text-sm font-medium">
            <CalendarDays className="mr-2 h-4 w-4" />
            Rekap Bulanan
          </TabsTrigger>
          <TabsTrigger value="teaching" className="py-2.5 text-sm font-medium">
            <GraduationCap className="mr-2 h-4 w-4" />
            Jadwal Mengajar
          </TabsTrigger>
          <TabsTrigger value="history" className="py-2.5 text-sm font-medium">
            <History className="mr-2 h-4 w-4" />
            Riwayat
          </TabsTrigger>
        </TabsList>

        {/* Monthly Recap Tab - with Guru/Pegawai sub-tabs */}
        <TabsContent value="recap" className="space-y-4">
          <MonthlyRecapTab />
        </TabsContent>

        {/* Teaching Schedule Tab */}
        <TabsContent value="teaching" className="space-y-4">
          <TeachingScheduleTab />
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
                  <CardDescription>Laporan yang pernah di-export sebelumnya</CardDescription>
                </div>
                <Button size="sm" variant="outline" onClick={() => refetchHistory()}>
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
                    Export laporan dari tab Rekap Bulanan
                  </p>
                </div>
              )}
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  );
}
