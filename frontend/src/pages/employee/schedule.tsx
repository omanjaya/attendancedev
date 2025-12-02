import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import {
  Calendar,
  Clock,
  MapPin,
  CalendarDays,
  AlertCircle,
  Loader2,
  ChevronLeft,
  ChevronRight,
} from 'lucide-react';

import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { getMySchedule } from '@/lib/api/schedules';

const months = [
  { value: 1, label: 'Januari' },
  { value: 2, label: 'Februari' },
  { value: 3, label: 'Maret' },
  { value: 4, label: 'April' },
  { value: 5, label: 'Mei' },
  { value: 6, label: 'Juni' },
  { value: 7, label: 'Juli' },
  { value: 8, label: 'Agustus' },
  { value: 9, label: 'September' },
  { value: 10, label: 'Oktober' },
  { value: 11, label: 'November' },
  { value: 12, label: 'Desember' },
];

export default function EmployeeSchedulePage() {
  const currentDate = new Date();
  const [selectedMonth, setSelectedMonth] = useState(currentDate.getMonth() + 1);
  const [selectedYear, setSelectedYear] = useState(currentDate.getFullYear());

  const years = Array.from({ length: 3 }, (_, i) => currentDate.getFullYear() - 1 + i);

  // Fetch employee's schedule
  const { data, isLoading, error } = useQuery({
    queryKey: ['my-schedule', selectedMonth, selectedYear],
    queryFn: () => getMySchedule({ month: selectedMonth, year: selectedYear }),
  });

  const schedule = data?.schedule;
  const employee = data?.employee;

  // Navigate month
  const navigateMonth = (direction: 'prev' | 'next') => {
    if (direction === 'prev') {
      if (selectedMonth === 1) {
        setSelectedMonth(12);
        setSelectedYear(selectedYear - 1);
      } else {
        setSelectedMonth(selectedMonth - 1);
      }
    } else {
      if (selectedMonth === 12) {
        setSelectedMonth(1);
        setSelectedYear(selectedYear + 1);
      } else {
        setSelectedMonth(selectedMonth + 1);
      }
    }
  };

  // Generate calendar grid
  const generateCalendarDays = () => {
    if (!schedule) return [];

    const firstDay = new Date(selectedYear, selectedMonth - 1, 1);
    const lastDay = new Date(selectedYear, selectedMonth, 0);
    const days = [];

    // Add empty cells for days before month starts
    const startDayOfWeek = firstDay.getDay();
    for (let i = 0; i < startDayOfWeek; i++) {
      days.push(null);
    }

    // Add days of month
    for (let day = 1; day <= lastDay.getDate(); day++) {
      const date = new Date(selectedYear, selectedMonth - 1, day);
      const dateStr = date.toISOString().split('T')[0];
      days.push({
        day,
        date: dateStr,
        isWorkingDay: schedule.working_days.includes(dateStr),
        isToday: dateStr === new Date().toISOString().split('T')[0],
      });
    }

    return days;
  };

  return (
    <div className="p-4 sm:p-6 max-w-6xl mx-auto">
      {/* Header */}
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-foreground">Jadwal Kerja Saya</h1>
        <p className="text-sm text-muted-foreground">
          Lihat jadwal kerja bulanan Anda
        </p>
      </div>

      {/* Month/Year Selector */}
      <Card className="mb-6">
        <CardContent className="py-6">
          <div className="flex items-center justify-center gap-4">
            <Button
              variant="outline"
              size="icon"
              onClick={() => navigateMonth('prev')}
              className="h-10 w-10"
            >
              <ChevronLeft className="h-5 w-5" />
            </Button>

            <div className="flex gap-3">
              <Select
                value={selectedMonth.toString()}
                onValueChange={(value) => setSelectedMonth(parseInt(value))}
              >
                <SelectTrigger className="w-[160px] h-10">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {months.map((month) => (
                    <SelectItem key={month.value} value={month.value.toString()}>
                      {month.label}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>

              <Select
                value={selectedYear.toString()}
                onValueChange={(value) => setSelectedYear(parseInt(value))}
              >
                <SelectTrigger className="w-[120px] h-10">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {years.map((year) => (
                    <SelectItem key={year} value={year.toString()}>
                      {year}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            <Button
              variant="outline"
              size="icon"
              onClick={() => navigateMonth('next')}
              className="h-10 w-10"
            >
              <ChevronRight className="h-5 w-5" />
            </Button>
          </div>
        </CardContent>
      </Card>

      {isLoading ? (
        <div className="flex items-center justify-center py-12">
          <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
        </div>
      ) : error ? (
        <Alert variant="destructive">
          <AlertCircle className="h-4 w-4" />
          <AlertDescription>
            Gagal memuat jadwal. Silakan coba lagi.
          </AlertDescription>
        </Alert>
      ) : !schedule ? (
        <Alert>
          <AlertCircle className="h-4 w-4" />
          <AlertDescription>
            Anda belum memiliki jadwal untuk periode ini. Silakan hubungi admin untuk pengaturan jadwal.
          </AlertDescription>
        </Alert>
      ) : (
        <div className="space-y-6">
          {/* Schedule Info */}
          <Card>
            <CardHeader className="pb-4">
              <CardTitle className="flex items-center gap-2 text-lg">
                <Calendar className="h-5 w-5 text-primary" />
                Informasi Jadwal
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="grid gap-6 md:grid-cols-4">
                <div className="space-y-1">
                  <p className="text-sm font-medium text-muted-foreground">Nama Jadwal</p>
                  <p className="text-base font-semibold">{schedule.name}</p>
                </div>
                <div className="space-y-1">
                  <p className="text-sm font-medium text-muted-foreground">Periode</p>
                  <p className="text-base font-semibold">
                    {months[schedule.month - 1].label} {schedule.year}
                  </p>
                </div>
                <div className="space-y-1">
                  <p className="text-sm font-medium text-muted-foreground">Lokasi</p>
                  <div className="flex items-center gap-2">
                    <MapPin className="h-4 w-4 text-primary" />
                    <p className="text-base font-semibold">{schedule.location?.name || '-'}</p>
                  </div>
                </div>
                <div className="space-y-1">
                  <p className="text-sm font-medium text-muted-foreground">Total Hari Kerja</p>
                  <Badge variant="secondary" className="text-base font-semibold px-3 py-1">
                    {schedule.total_working_days} hari
                  </Badge>
                </div>
              </div>
            </CardContent>
          </Card>

          {/* Calendar View */}
          <Card>
            <CardHeader className="pb-4">
              <div className="flex items-center justify-between">
                <CardTitle className="flex items-center gap-2">
                  <CalendarDays className="h-5 w-5 text-primary" />
                  Kalender Hari Kerja
                </CardTitle>
                <Badge variant="outline" className="font-normal">
                  {schedule.total_working_days} hari kerja
                </Badge>
              </div>
              <CardDescription>
                Tandai hari kerja Anda untuk bulan ini
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div className="grid grid-cols-7 gap-3">
                {/* Day headers */}
                <div className="text-center text-xs font-semibold text-muted-foreground py-2">Min</div>
                <div className="text-center text-xs font-semibold text-muted-foreground py-2">Sen</div>
                <div className="text-center text-xs font-semibold text-muted-foreground py-2">Sel</div>
                <div className="text-center text-xs font-semibold text-muted-foreground py-2">Rab</div>
                <div className="text-center text-xs font-semibold text-muted-foreground py-2">Kam</div>
                <div className="text-center text-xs font-semibold text-muted-foreground py-2">Jum</div>
                <div className="text-center text-xs font-semibold text-muted-foreground py-2">Sab</div>

                {/* Calendar days */}
                {generateCalendarDays().map((day, idx) =>
                  day === null ? (
                    <div key={`empty-${idx}`} />
                  ) : (
                    <div
                      key={day.date}
                      className={`
                        aspect-square flex items-center justify-center rounded-lg text-sm font-medium transition-all
                        ${day.isWorkingDay
                          ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 shadow-sm'
                          : 'bg-muted/50 text-muted-foreground'
                        }
                        ${day.isToday
                          ? 'ring-2 ring-primary ring-offset-2 ring-offset-background'
                          : ''
                        }
                      `}
                    >
                      {day.day}
                    </div>
                  )
                )}
              </div>

              <div className="flex items-center justify-center gap-6 mt-6 pt-6 border-t">
                <div className="flex items-center gap-2">
                  <div className="w-4 h-4 rounded bg-green-100 dark:bg-green-900/30 shadow-sm" />
                  <span className="text-sm font-medium">Hari kerja</span>
                </div>
                <div className="flex items-center gap-2">
                  <div className="w-4 h-4 rounded bg-muted/50" />
                  <span className="text-sm font-medium">Libur/weekend</span>
                </div>
                <div className="flex items-center gap-2">
                  <div className="w-4 h-4 rounded ring-2 ring-primary ring-offset-2 ring-offset-background" />
                  <span className="text-sm font-medium">Hari ini</span>
                </div>
              </div>
            </CardContent>
          </Card>

          {/* Time Settings */}
          <Card>
            <CardHeader className="pb-4">
              <CardTitle className="flex items-center gap-2 text-lg">
                <Clock className="h-5 w-5 text-primary" />
                Pengaturan Waktu
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="grid gap-6 md:grid-cols-3">
                <div className="space-y-1">
                  <p className="text-sm font-medium text-muted-foreground">Jam Kerja</p>
                  <p className="text-base font-semibold">
                    {schedule.default_start_time} - {schedule.default_end_time}
                  </p>
                </div>
                <div className="space-y-1">
                  <p className="text-sm font-medium text-muted-foreground">Window Absen Masuk</p>
                  <p className="text-base font-semibold">
                    {schedule.checkin_start_time} - {schedule.checkin_end_time}
                  </p>
                </div>
                <div className="space-y-1">
                  <p className="text-sm font-medium text-muted-foreground">Window Absen Pulang</p>
                  <p className="text-base font-semibold">
                    {schedule.checkout_start_time} - {schedule.checkout_end_time}
                  </p>
                </div>
              </div>
              {schedule.description && (
                <div className="mt-6 pt-6 border-t space-y-1">
                  <p className="text-sm font-medium text-muted-foreground">Catatan</p>
                  <p className="text-sm text-muted-foreground">{schedule.description}</p>
                </div>
              )}
            </CardContent>
          </Card>
        </div>
      )}
    </div>
  );
}
