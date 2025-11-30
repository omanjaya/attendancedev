import { useState } from 'react';
import {
  ChevronLeft,
  ChevronRight,
  Calendar as CalendarIcon,
  Users,
  Palmtree,
  Stethoscope,
  Baby,
  User,
} from 'lucide-react';

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';

// Mock leave data
const mockLeaves: Record<string, Array<{
  id: number;
  employee: string;
  type: 'annual' | 'sick' | 'maternity' | 'personal';
  status: 'approved' | 'pending';
}>> = {
  '2024-11-25': [
    { id: 1, employee: 'Ahmad Fauzi', type: 'annual', status: 'approved' },
  ],
  '2024-11-26': [
    { id: 2, employee: 'Siti Rahayu', type: 'sick', status: 'approved' },
    { id: 3, employee: 'Ahmad Fauzi', type: 'annual', status: 'approved' },
  ],
  '2024-11-27': [
    { id: 4, employee: 'Budi Santoso', type: 'personal', status: 'pending' },
    { id: 5, employee: 'Siti Rahayu', type: 'sick', status: 'approved' },
    { id: 6, employee: 'Ahmad Fauzi', type: 'annual', status: 'approved' },
  ],
  '2024-11-28': [
    { id: 7, employee: 'Dewi Putri', type: 'maternity', status: 'approved' },
    { id: 8, employee: 'Budi Santoso', type: 'personal', status: 'pending' },
  ],
  '2024-11-29': [
    { id: 9, employee: 'Dewi Putri', type: 'maternity', status: 'approved' },
  ],
};

const daysOfWeek = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
const months = [
  'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

const leaveTypeConfig = {
  annual: { label: 'Cuti Tahunan', icon: Palmtree, color: 'bg-primary/10 text-primary' },
  sick: { label: 'Cuti Sakit', icon: Stethoscope, color: 'bg-destructive/10 text-destructive' },
  maternity: { label: 'Cuti Melahirkan', icon: Baby, color: 'bg-pink-100 text-pink-600' },
  personal: { label: 'Cuti Pribadi', icon: User, color: 'bg-warning/10 text-warning' },
};

export default function LeaveCalendarPage() {
  const [currentDate, setCurrentDate] = useState(new Date(2024, 10, 1));
  const [selectedDate, setSelectedDate] = useState<string | null>(null);

  const getDaysInMonth = (date: Date) => {
    const year = date.getFullYear();
    const month = date.getMonth();
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const daysInMonth = lastDay.getDate();
    const startingDay = firstDay.getDay();

    const days: Array<{ date: number; isCurrentMonth: boolean; dateString: string }> = [];

    const prevMonthLastDay = new Date(year, month, 0).getDate();
    for (let i = startingDay - 1; i >= 0; i--) {
      const day = prevMonthLastDay - i;
      days.push({
        date: day,
        isCurrentMonth: false,
        dateString: `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`,
      });
    }

    for (let i = 1; i <= daysInMonth; i++) {
      days.push({
        date: i,
        isCurrentMonth: true,
        dateString: `${year}-${String(month + 1).padStart(2, '0')}-${String(i).padStart(2, '0')}`,
      });
    }

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

  const days = getDaysInMonth(currentDate);
  const today = new Date().toISOString().split('T')[0];

  return (
    <div className="p-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
          <h1 className="text-2xl font-bold text-foreground">Kalender Cuti</h1>
          <p className="text-sm text-muted-foreground">
            Lihat jadwal cuti karyawan dalam tampilan kalender
          </p>
        </div>
        <Button asChild>
          <a href="/leave/create">Ajukan Cuti</a>
        </Button>
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
              <div className="grid grid-cols-7 gap-px bg-border rounded-lg overflow-hidden">
                {daysOfWeek.map((day) => (
                  <div
                    key={day}
                    className="bg-muted p-2 text-center text-sm font-medium text-muted-foreground"
                  >
                    {day}
                  </div>
                ))}

                {days.map((day, index) => {
                  const leaves = mockLeaves[day.dateString] || [];
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
                        {leaves.slice(0, 2).map((leave) => {
                          const config = leaveTypeConfig[leave.type];
                          return (
                            <div
                              key={leave.id}
                              className={`text-xs p-1 rounded truncate flex items-center gap-1 ${config.color}`}
                            >
                              <config.icon className="h-3 w-3" />
                              {leave.employee.split(' ')[0]}
                            </div>
                          );
                        })}
                        {leaves.length > 2 && (
                          <div className="text-xs text-muted-foreground">
                            +{leaves.length - 2} lainnya
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
          {/* Selected Date */}
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
              {selectedDate && mockLeaves[selectedDate] ? (
                <div className="space-y-3">
                  {mockLeaves[selectedDate].map((leave) => {
                    const config = leaveTypeConfig[leave.type];
                    return (
                      <div
                        key={leave.id}
                        className="flex items-center gap-3 p-3 rounded-lg bg-muted"
                      >
                        <Avatar className="h-8 w-8">
                          <AvatarFallback className="text-xs">
                            {leave.employee.split(' ').map(n => n[0]).join('')}
                          </AvatarFallback>
                        </Avatar>
                        <div className="flex-1 min-w-0">
                          <p className="text-sm font-medium truncate">{leave.employee}</p>
                          <p className="text-xs text-muted-foreground">{config.label}</p>
                        </div>
                        <Badge
                          variant="outline"
                          className={leave.status === 'approved' ? 'bg-success/10 text-success' : 'bg-warning/10 text-warning'}
                        >
                          {leave.status === 'approved' ? 'Disetujui' : 'Pending'}
                        </Badge>
                      </div>
                    );
                  })}
                </div>
              ) : (
                <p className="text-sm text-muted-foreground text-center py-4">
                  {selectedDate ? 'Tidak ada cuti' : 'Klik tanggal untuk melihat detail'}
                </p>
              )}
            </CardContent>
          </Card>

          {/* Legend */}
          <Card>
            <CardHeader className="pb-2">
              <CardTitle className="text-base">Jenis Cuti</CardTitle>
            </CardHeader>
            <CardContent className="space-y-2">
              {Object.entries(leaveTypeConfig).map(([key, config]) => (
                <div key={key} className="flex items-center gap-2">
                  <div className={`p-1.5 rounded ${config.color}`}>
                    <config.icon className="h-3 w-3" />
                  </div>
                  <span className="text-sm">{config.label}</span>
                </div>
              ))}
            </CardContent>
          </Card>

          {/* Stats */}
          <Card>
            <CardHeader className="pb-2">
              <CardTitle className="flex items-center gap-2 text-base">
                <Users className="h-4 w-4 text-primary" />
                Bulan Ini
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-3">
              <div className="flex justify-between">
                <span className="text-sm text-muted-foreground">Total Cuti</span>
                <Badge variant="outline">15</Badge>
              </div>
              <div className="flex justify-between">
                <span className="text-sm text-muted-foreground">Disetujui</span>
                <Badge className="bg-success/10 text-success">12</Badge>
              </div>
              <div className="flex justify-between">
                <span className="text-sm text-muted-foreground">Pending</span>
                <Badge className="bg-warning/10 text-warning">3</Badge>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  );
}
