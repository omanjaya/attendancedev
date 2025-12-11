import { Link, useParams, useNavigate } from '@tanstack/react-router';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import {
  ArrowLeft,
  Calendar,
  Flag,
  Star,
  Palmtree,
  Edit,
  Trash2,
  Clock,
  RefreshCw,
  Loader2,
} from 'lucide-react';

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { useNotificationStore } from '@/stores';
import { getHoliday, deleteHoliday } from '@/lib/api/holidays';

const holidayTypeConfig: Record<string, { label: string; icon: typeof Flag; color: string }> = {
  public_holiday: { label: 'Nasional', icon: Flag, color: 'bg-destructive/10 text-destructive border-destructive/20' },
  religious_holiday: { label: 'Keagamaan', icon: Star, color: 'bg-chart-5/10 text-chart-5 border-chart-5/20' },
  school_holiday: { label: 'Sekolah', icon: Palmtree, color: 'bg-success/10 text-success border-success/20' },
  substitute_holiday: { label: 'Cuti Bersama', icon: Calendar, color: 'bg-primary/10 text-primary border-primary/20' },
  // Legacy types for backward compatibility
  national: { label: 'Nasional', icon: Flag, color: 'bg-destructive/10 text-destructive border-destructive/20' },
  religious: { label: 'Keagamaan', icon: Star, color: 'bg-chart-5/10 text-chart-5 border-chart-5/20' },
  company: { label: 'Perusahaan', icon: Palmtree, color: 'bg-success/10 text-success border-success/20' },
};

export default function HolidayShowPage() {
  const { id } = useParams({ strict: false }) as { id: string };
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const { success, error: showError } = useNotificationStore();

  // Fetch holiday data
  const { data: holiday, isLoading, error } = useQuery({
    queryKey: ['holidays', id],
    queryFn: () => getHoliday(id),
    enabled: !!id,
  });

  // Delete mutation
  const deleteMutation = useMutation({
    mutationFn: () => deleteHoliday(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['holidays'] });
      success('Berhasil', 'Hari libur berhasil dihapus');
      navigate({ to: '/admin/holidays' });
    },
    onError: () => {
      showError('Gagal', 'Gagal menghapus hari libur');
    },
  });

  if (isLoading) {
    return (
      <div className="flex items-center justify-center min-h-[400px]">
        <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
      </div>
    );
  }

  if (error || !holiday) {
    return (
      <div className="p-4 sm:p-6 text-center">
        <p className="text-muted-foreground">Hari libur tidak ditemukan</p>
        <Button asChild className="mt-4">
          <Link to="/admin/holidays">Kembali</Link>
        </Button>
      </div>
    );
  }

  const config = holidayTypeConfig[holiday.type] || holidayTypeConfig.national;
  const TypeIcon = config.icon;

  const getDuration = () => {
    if (!holiday.end_date) return '1 hari';
    const start = new Date(holiday.date);
    const end = new Date(holiday.end_date);
    const diff = Math.ceil((end.getTime() - start.getTime()) / (1000 * 60 * 60 * 24)) + 1;
    return `${diff} hari`;
  };

  return (
    <div className="p-4 sm:p-6 max-w-3xl mx-auto">
      {/* Header */}
      <div className="mb-6">
        <Link
          to="/admin/holidays"
          className="inline-flex items-center text-sm text-muted-foreground hover:text-foreground mb-4"
        >
          <ArrowLeft className="h-4 w-4 mr-2" />
          Kembali ke daftar hari libur
        </Link>

        <div className="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
          <div>
            <div className="flex items-center gap-3 mb-2">
              <h1 className="text-2xl font-bold text-foreground">{holiday.name}</h1>
              <Badge className={config.color}>
                <TypeIcon className="h-3 w-3 mr-1" />
                {config.label}
              </Badge>
            </div>
            <p className="text-sm text-muted-foreground">
              ID: #{holiday.id}
            </p>
          </div>
          <div className="flex items-center gap-2">
            <Button variant="outline" asChild>
              <a href={`/admin/holidays/${holiday.id}/edit`}>
                <Edit className="h-4 w-4 mr-2" />
                Edit
              </a>
            </Button>
            <AlertDialog>
              <AlertDialogTrigger asChild>
                <Button variant="destructive" disabled={deleteMutation.isPending}>
                  {deleteMutation.isPending ? (
                    <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                  ) : (
                    <Trash2 className="h-4 w-4 mr-2" />
                  )}
                  Hapus
                </Button>
              </AlertDialogTrigger>
              <AlertDialogContent>
                <AlertDialogHeader>
                  <AlertDialogTitle>Hapus Hari Libur?</AlertDialogTitle>
                  <AlertDialogDescription>
                    Tindakan ini tidak dapat dibatalkan. Hari libur "{holiday.name}" akan dihapus dari kalender.
                  </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                  <AlertDialogCancel>Batal</AlertDialogCancel>
                  <AlertDialogAction
                    className="bg-destructive text-destructive-foreground"
                    onClick={() => deleteMutation.mutate()}
                  >
                    Hapus
                  </AlertDialogAction>
                </AlertDialogFooter>
              </AlertDialogContent>
            </AlertDialog>
          </div>
        </div>
      </div>

      <div className="space-y-6">
        {/* Main Info */}
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2 text-lg">
              <Calendar className="h-5 w-5 text-primary" />
              Informasi Hari Libur
            </CardTitle>
          </CardHeader>
          <CardContent className="space-y-6">
            {/* Date Display */}
            <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
              <div className="text-center p-4 rounded-lg bg-primary/10">
                <p className="text-3xl font-bold text-primary">
                  {new Date(holiday.date).getDate()}
                </p>
                <p className="text-sm text-muted-foreground">
                  {new Date(holiday.date).toLocaleDateString('id-ID', { month: 'short' })}
                </p>
              </div>
              {holiday.end_date && (
                <>
                  <div className="flex items-center justify-center text-muted-foreground">
                    sampai
                  </div>
                  <div className="text-center p-4 rounded-lg bg-primary/10">
                    <p className="text-3xl font-bold text-primary">
                      {new Date(holiday.end_date).getDate()}
                    </p>
                    <p className="text-sm text-muted-foreground">
                      {new Date(holiday.end_date).toLocaleDateString('id-ID', { month: 'short' })}
                    </p>
                  </div>
                </>
              )}
              <div className="text-center p-4 rounded-lg bg-muted">
                <p className="text-2xl font-bold">{getDuration()}</p>
                <p className="text-sm text-muted-foreground">Durasi</p>
              </div>
            </div>

            {/* Description */}
            {holiday.description && (
              <div>
                <h4 className="text-sm font-medium mb-2">Deskripsi</h4>
                <p className="text-sm text-muted-foreground bg-muted p-4 rounded-lg">
                  {holiday.description}
                </p>
              </div>
            )}

            {/* Recurring Badge */}
            {holiday.is_recurring && (
              <div className="flex items-center gap-3 p-4 rounded-lg bg-success/10 border border-success/20">
                <RefreshCw className="h-5 w-5 text-success" />
                <div>
                  <p className="font-medium text-success">Berulang Setiap Tahun</p>
                  <p className="text-sm text-muted-foreground">
                    Hari libur ini akan otomatis muncul setiap tahun
                  </p>
                </div>
              </div>
            )}
          </CardContent>
        </Card>

        {/* Type Info */}
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2 text-lg">
              <TypeIcon className="h-5 w-5 text-primary" />
              Jenis Hari Libur
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className={`p-4 rounded-lg border ${config.color}`}>
              <div className="flex items-center gap-3">
                <TypeIcon className="h-6 w-6" />
                <div>
                  <p className="font-semibold">{config.label}</p>
                  <p className="text-sm opacity-80">
                    {(['national', 'public_holiday'].includes(holiday.type)) && 'Hari libur resmi yang ditetapkan oleh pemerintah'}
                    {(['religious', 'religious_holiday'].includes(holiday.type)) && 'Hari raya keagamaan yang diakui sebagai libur nasional'}
                    {(['company', 'school_holiday'].includes(holiday.type)) && 'Hari libur tambahan yang ditetapkan oleh perusahaan'}
                    {holiday.type === 'substitute_holiday' && 'Cuti bersama yang ditetapkan pemerintah'}
                  </p>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Metadata */}
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2 text-lg">
              <Clock className="h-5 w-5 text-primary" />
              Informasi Sistem
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="grid gap-4 sm:grid-cols-2">
              <div>
                <p className="text-xs text-muted-foreground">Dibuat</p>
                <p className="text-sm font-medium">
                  {new Date(holiday.created_at).toLocaleDateString('id-ID', {
                    day: 'numeric',
                    month: 'long',
                    year: 'numeric',
                  })}
                </p>
              </div>
              <div>
                <p className="text-xs text-muted-foreground">Terakhir Diperbarui</p>
                <p className="text-sm font-medium">
                  {new Date(holiday.updated_at).toLocaleDateString('id-ID', {
                    day: 'numeric',
                    month: 'long',
                    year: 'numeric',
                  })}
                </p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
