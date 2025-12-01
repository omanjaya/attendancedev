import { Link } from '@tanstack/react-router';
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

// Mock holiday data
const mockHoliday = {
  id: 1,
  name: 'Hari Raya Idul Fitri',
  date: '2024-04-10',
  end_date: '2024-04-11',
  type: 'religious',
  description: 'Hari Raya Idul Fitri 1445 Hijriyah. Libur nasional untuk seluruh karyawan.',
  is_recurring: true,
  created_at: '2024-01-15',
  updated_at: '2024-03-20',
};

const holidayTypeConfig = {
  national: { label: 'Nasional', icon: Flag, color: 'bg-destructive/10 text-destructive border-destructive/20' },
  religious: { label: 'Keagamaan', icon: Star, color: 'bg-chart-5/10 text-chart-5 border-chart-5/20' },
  company: { label: 'Perusahaan', icon: Palmtree, color: 'bg-success/10 text-success border-success/20' },
};

export default function HolidayShowPage() {
  const holiday = mockHoliday;
  const config = holidayTypeConfig[holiday.type as keyof typeof holidayTypeConfig];
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
                <Button variant="destructive">
                  <Trash2 className="h-4 w-4 mr-2" />
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
                  <AlertDialogAction className="bg-destructive text-destructive-foreground">
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
                    {holiday.type === 'national' && 'Hari libur resmi yang ditetapkan oleh pemerintah'}
                    {holiday.type === 'religious' && 'Hari raya keagamaan yang diakui sebagai libur nasional'}
                    {holiday.type === 'company' && 'Hari libur tambahan yang ditetapkan oleh perusahaan'}
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
