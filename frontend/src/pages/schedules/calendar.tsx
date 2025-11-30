import { useState } from 'react';
import {
  ChevronLeft,
  ChevronRight,
  Plus,
  Calendar as CalendarIcon,
  Clock,
  Users,
  LayoutGrid,
  List,
} from 'lucide-react';

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';

// Mock schedule data
const mockSchedules: Record<string, Array<{
  id: number;
  title: string;
  time: string;
  type: 'shift' | 'meeting' | 'event';
  employees?: number;
}>> = {
  '2024-11-25': [
    { id: 1, title: 'Shift Pagi', time: '08:00 - 17:00', type: 'shift', employees: 15 },
  ],
  '2024-11-26': [
    { id: 2, title: 'Shift Pagi', time: '08:00 - 17:00', type: 'shift', employees: 15 },
    { id: 3, title: 'Meeting Tim IT', time: '10:00 - 11:00', type: 'meeting', employees: 5 },
  ],
  '2024-11-27': [
    { id: 4, title: 'Shift Pagi', time: '08:00 - 17:00', type: 'shift', employees: 15 },
  ],
  '2024-11-28': [
    { id: 5, title: 'Shift Pagi', time: '08:00 - 17:00', type: 'shift', employees: 15 },
    { id: 6, title: 'Training Karyawan', time: '14:00 - 16:00', type: 'event', employees: 20 },
  ],
  '2024-11-29': [
    { id: 7, title: 'Shift Pagi', time: '08:00 - 17:00', type: 'shift', employees: 12 },
  ],
};

const daysOfWeek = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
const months = [
  'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

export default function ScheduleCalendarPage() {
  const [currentDate, setCurrentDate] = useState(new Date(2024, 10, 1)); // November 2024
  const [viewMode, setViewMode] = useState<'month' | 'week'>('month');
  const [selectedDate, setSelectedDate] = useState<string | null>(null);

  const getDaysInMonth = (date: Date) => {
    const year = date.getFullYear();
    const month = date.getMonth();
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const daysInMonth = lastDay.getDate();
    const startingDay = firstDay.getDay();

    const days: Array<{ date: number; isCurrentMonth: boolean; dateString: string }> = [];

    // Previous month days
    const prevMonthLastDay = new Date(year, month, 0).getDate();
    for (let i = startingDay - 1; i >= 0; i--) {
      const day = prevMonthLastDay - i;
      days.push({
        date: day,
        isCurrentMonth: false,
        dateString: `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`,
      });
    }

    // Current month days
    for (let i = 1; i <= daysInMonth; i++) {
      days.push({
        date: i,
        isCurrentMonth: true,
        dateString: `${year}-${String(month + 1).padStart(2, '0')}-${String(i).padStart(2, '0')}`,
      });
    }

    // Next month days
    const remainingDays = 42 - days.length;
    for (let i = 1; i <= remainingDays; i++) {
      days.push({
        date: i,
        isCurrentMonth: false,
        dateString: `${year}-${String(month + 2).padStart(2, '0')}-${String(i).padStart(2, '0')}`,
      });
    }

    return days;
  };

  const navigateMonth = (direction: 'prev' | 'next') => {
    setCurrentDate(prev => {
      const newDate = new Date(prev);
      if (direction === 'prev') {
        newDate.setMonth(prev.getMonth() - 1);
      } else {
        newDate.setMonth(prev.getMonth() + 1);
      }
      return newDate;
    });
  };

  const getScheduleTypeColor = (type: string) => {
    switch (type) {
      case 'shift':
        return 'bg-primary/10 text-primary border-primary/20';
      case 'meeting':
        return 'bg-warning/10 text-warning border-warning/20';
      case 'event':
        return 'bg-success/10 text-success border-success/20';
      default:
        return 'bg-muted';
    }
  };

  const days = getDaysInMonth(currentDate);
  const today = new Date().toISOString().split('T')[0];

  return (
    <div className="p-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
          <h1 className="text-2xl font-bold text-foreground">Kalender Jadwal</h1>
          <p className="text-sm text-muted-foreground">
            Kelola jadwal kerja dan shift karyawan
          </p>
        </div>
        <div className="flex items-center gap-2">
          <Select value={viewMode} onValueChange={(v) => setViewMode(v as 'month' | 'week')}>
            <SelectTrigger className="w-[130px]">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="month">
                <div className="flex items-center">
                  <LayoutGrid className="h-4 w-4 mr-2" />
                  Bulan
                </div>
              </SelectItem>
              <SelectItem value="week">
                <div className="flex items-center">
                  <List className="h-4 w-4 mr-2" />
                  Minggu
                </div>
              </SelectItem>
            </SelectContent>
          </Select>
          <Dialog>
            <DialogTrigger asChild>
              <Button>
                <Plus className="h-4 w-4 mr-2" />
                Tambah Jadwal
              </Button>
            </DialogTrigger>
            <DialogContent>
              <DialogHeader>
                <DialogTitle>Tambah Jadwal Baru</DialogTitle>
              </DialogHeader>
              <div className="space-y-4 pt-4">
                <p className="text-sm text-muted-foreground">
                  Form tambah jadwal akan ditampilkan di sini.
                </p>
              </div>
            </DialogContent>
          </Dialog>
        </div>
      </div>

      <div className="grid gap-6 lg:grid-cols-4">
        {/* Calendar */}
        <div className="lg:col-span-3">
          <Card>
            <CardHeader className="flex flex-row items-center justify-between pb-2">
              <div className="flex items-center gap-4">
                <Button variant="outline" size="icon" onClick={() => navigateMonth('prev')}>
                  <ChevronLeft className="h-4 w-4" />
                </Button>
                <h2 className="text-lg font-semibold">
                  {months[currentDate.getMonth()]} {currentDate.getFullYear()}
                </h2>
                <Button variant="outline" size="icon" onClick={() => navigateMonth('next')}>
                  <ChevronRight className="h-4 w-4" />
                </Button>
              </div>
              <Button
                variant="outline"
                size="sm"
                onClick={() => setCurrentDate(new Date())}
              >
                Hari Ini
              </Button>
            </CardHeader>
            <CardContent>
              {/* Calendar Grid */}
              <div className="grid grid-cols-7 gap-px bg-border rounded-lg overflow-hidden">
                {/* Day Headers */}
                {daysOfWeek.map((day) => (
                  <div
                    key={day}
                    className="bg-muted p-2 text-center text-sm font-medium text-muted-foreground"
                  >
                    {day}
                  </div>
                ))}

                {/* Days */}
                {days.map((day, index) => {
                  const schedules = mockSchedules[day.dateString] || [];
                  const isToday = day.dateString === today;

                  return (
                    <div
                      key={index}
                      onClick={() => setSelectedDate(day.dateString)}
                      className={`
                        min-h-[100px] p-2 bg-background cursor-pointer transition-colors hover:bg-muted/50
                        ${!day.isCurrentMonth ? 'opacity-40' : ''}
                        ${selectedDate === day.dateString ? 'ring-2 ring-primary ring-inset' : ''}
                      `}
                    >
                      <div className={`
                        text-sm font-medium mb-1
                        ${isToday ? 'bg-primary text-primary-foreground rounded-full w-7 h-7 flex items-center justify-center' : ''}
                      `}>
                        {day.date}
                      </div>
                      <div className="space-y-1">
                        {schedules.slice(0, 2).map((schedule) => (
                          <div
                            key={schedule.id}
                            className={`text-xs p-1 rounded border truncate ${getScheduleTypeColor(schedule.type)}`}
                          >
                            {schedule.title}
                          </div>
                        ))}
                        {schedules.length > 2 && (
                          <div className="text-xs text-muted-foreground">
                            +{schedules.length - 2} lainnya
                          </div>
                        )}
                      </div>
                    </div>
                  );
                })}
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Sidebar */}
        <div className="space-y-6">
          {/* Selected Date Details */}
          <Card>
            <CardHeader className="pb-2">
              <CardTitle className="flex items-center gap-2 text-base">
                <CalendarIcon className="h-4 w-4 text-primary" />
                {selectedDate
                  ? new Date(selectedDate).toLocaleDateString('id-ID', {
                      weekday: 'long',
                      day: 'numeric',
                      month: 'long',
                    })
                  : 'Pilih Tanggal'}
              </CardTitle>
            </CardHeader>
            <CardContent>
              {selectedDate && mockSchedules[selectedDate] ? (
                <div className="space-y-3">
                  {mockSchedules[selectedDate].map((schedule) => (
                    <div
                      key={schedule.id}
                      className={`p-3 rounded-lg border ${getScheduleTypeColor(schedule.type)}`}
                    >
                      <p className="font-medium text-sm">{schedule.title}</p>
                      <div className="flex items-center gap-2 mt-1 text-xs opacity-80">
                        <Clock className="h-3 w-3" />
                        {schedule.time}
                      </div>
                      {schedule.employees && (
                        <div className="flex items-center gap-2 mt-1 text-xs opacity-80">
                          <Users className="h-3 w-3" />
                          {schedule.employees} karyawan
                        </div>
                      )}
                    </div>
                  ))}
                </div>
              ) : (
                <p className="text-sm text-muted-foreground text-center py-4">
                  {selectedDate ? 'Tidak ada jadwal' : 'Klik tanggal untuk melihat detail'}
                </p>
              )}
            </CardContent>
          </Card>

          {/* Legend */}
          <Card>
            <CardHeader className="pb-2">
              <CardTitle className="text-base">Keterangan</CardTitle>
            </CardHeader>
            <CardContent className="space-y-2">
              <div className="flex items-center gap-2">
                <div className="w-3 h-3 rounded bg-primary/50" />
                <span className="text-sm">Shift Kerja</span>
              </div>
              <div className="flex items-center gap-2">
                <div className="w-3 h-3 rounded bg-warning/50" />
                <span className="text-sm">Meeting</span>
              </div>
              <div className="flex items-center gap-2">
                <div className="w-3 h-3 rounded bg-success/50" />
                <span className="text-sm">Event</span>
              </div>
            </CardContent>
          </Card>

          {/* Quick Stats */}
          <Card>
            <CardHeader className="pb-2">
              <CardTitle className="text-base">Statistik Bulan Ini</CardTitle>
            </CardHeader>
            <CardContent className="space-y-3">
              <div className="flex justify-between">
                <span className="text-sm text-muted-foreground">Total Jadwal</span>
                <Badge variant="outline">28</Badge>
              </div>
              <div className="flex justify-between">
                <span className="text-sm text-muted-foreground">Shift</span>
                <Badge variant="outline">22</Badge>
              </div>
              <div className="flex justify-between">
                <span className="text-sm text-muted-foreground">Meeting</span>
                <Badge variant="outline">4</Badge>
              </div>
              <div className="flex justify-between">
                <span className="text-sm text-muted-foreground">Event</span>
                <Badge variant="outline">2</Badge>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  );
}
