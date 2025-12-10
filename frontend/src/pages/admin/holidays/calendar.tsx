import { useState } from 'react';
import { Link } from '@tanstack/react-router';
import {
  ChevronLeft,
  ChevronRight,
  Plus,
  Calendar as CalendarIcon,
  Flag,
  Star,
  Palmtree,
} from 'lucide-react';

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';

// Mock holidays data
const mockHolidays: Record<string, Array<{
  id: number;
  name: string;
  type: 'national' | 'religious' | 'company';
}>> = {
  '2024-11-01': [
    { id: 1, name: 'Hari Pahlawan', type: 'national' },
  ],
  '2024-11-10': [
    { id: 2, name: 'Hari Pahlawan', type: 'national' },
  ],
  '2024-12-25': [
    { id: 3, name: 'Hari Natal', type: 'religious' },
  ],
  '2024-12-26': [
    { id: 4, name: 'Cuti Bersama Natal', type: 'company' },
  ],
  '2025-01-01': [
    { id: 5, name: 'Tahun Baru', type: 'national' },
  ],
};

const daysOfWeek = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
const months = [
  'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

const holidayTypeConfig = {
  national: { label: 'Nasional', icon: Flag, color: 'bg-destructive/10 text-destructive border-destructive/20' },
  religious: { label: 'Keagamaan', icon: Star, color: 'bg-chart-5/10 text-chart-5 border-chart-5/20' },
  company: { label: 'Perusahaan', icon: Palmtree, color: 'bg-success/10 text-success border-success/20' },
};

export default function HolidaysCalendarPage() {
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

  // Get all holidays for the current month
  const monthHolidays = Object.entries(mockHolidays)
    .filter(([date]) => {
      const d = new Date(date);
      return d.getMonth() === currentDate.getMonth() && d.getFullYear() === currentDate.getFullYear();
    })
    .flatMap(([date, holidays]) => holidays.map(h => ({ ...h, date })));

  return (
    <div className="p-4 sm:p-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
          <h1 className="text-2xl font-bold text-foreground">Kalender Hari Libur</h1>
          <p className="text-sm text-muted-foreground">
            Lihat semua hari libur dalam tampilan kalender
          </p>
        </div>
        <Button asChild>
          <Link to="/admin/holidays/create">
            <Plus className="h-4 w-4 mr-2" />
            Tambah Hari Libur
          </Link>
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
                {daysOfWeek.map((day, index) => (
                  <div
                    key={day}
                    className={`
                      bg-muted p-2 text-center text-sm font-medium
                      ${index === 0 ? 'text-destructive' : 'text-muted-foreground'}
                    `}
                  >
                    {day}
                  </div>
                ))}

                {days.map((day, index) => {
                  const holidays = mockHolidays[day.dateString] || [];
                  const isToday = day.dateString === today;
                  const isSunday = index % 7 === 0;
                  const hasHoliday = holidays.length > 0;

                  return (
                    <div
                      key={index}
                      onClick={() => setSelectedDate(day.dateString)}
                      className={`
                        min-h-[100px] p-2 cursor-pointer transition-colors hover:bg-muted/50
                        ${!day.isCurrentMonth ? 'opacity-40 bg-muted/30' : 'bg-background'}
                        ${selectedDate === day.dateString ? 'ring-2 ring-primary ring-inset' : ''}
                        ${hasHoliday ? 'bg-destructive/5' : ''}
                      `}
                    >
                      <div className={`
                        text-sm font-medium mb-1
                        ${isToday ? 'bg-primary text-primary-foreground rounded-full w-7 h-7 flex items-center justify-center' : ''}
                        ${isSunday && !isToday ? 'text-destructive' : ''}
                      `}>
                        {day.date}
                      </div>
                      <div className="space-y-1">
                        {holidays.map((holiday) => {
                          const config = holidayTypeConfig[holiday.type];
                          return (
                            <div
                              key={holiday.id}
                              className={`text-xs p-1 rounded border truncate ${config.color}`}
                            >
                              {holiday.name}
                            </div>
                          );
                        })}
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
              {selectedDate && mockHolidays[selectedDate] ? (
                <div className="space-y-3">
                  {mockHolidays[selectedDate].map((holiday) => {
                    const config = holidayTypeConfig[holiday.type];
                    return (
                      <div
                        key={holiday.id}
                        className={`p-3 rounded-lg border ${config.color}`}
                      >
                        <div className="flex items-center gap-2">
                          <config.icon className="h-4 w-4" />
                          <span className="font-medium text-sm">{holiday.name}</span>
                        </div>
                        <p className="text-xs mt-1 opacity-80">{config.label}</p>
                      </div>
                    );
                  })}
                </div>
              ) : (
                <p className="text-sm text-muted-foreground text-center py-4">
                  {selectedDate ? 'Tidak ada hari libur' : 'Klik tanggal untuk melihat detail'}
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
              {Object.entries(holidayTypeConfig).map(([key, config]) => (
                <div key={key} className="flex items-center gap-2">
                  <div className={`p-1.5 rounded ${config.color}`}>
                    <config.icon className="h-3 w-3" />
                  </div>
                  <span className="text-sm">{config.label}</span>
                </div>
              ))}
            </CardContent>
          </Card>

          {/* Upcoming Holidays */}
          <Card>
            <CardHeader className="pb-2">
              <CardTitle className="text-base">Hari Libur Bulan Ini</CardTitle>
            </CardHeader>
            <CardContent>
              {monthHolidays.length > 0 ? (
                <div className="space-y-2">
                  {monthHolidays.map((holiday) => {
                    const config = holidayTypeConfig[holiday.type];
                    return (
                      <div key={holiday.id} className="flex items-center justify-between">
                        <div className="flex items-center gap-2">
                          <config.icon className={`h-4 w-4 ${config.color.split(' ')[1]}`} />
                          <span className="text-sm">{holiday.name}</span>
                        </div>
                        <Badge variant="outline" className="text-xs">
                          {new Date(holiday.date).getDate()}
                        </Badge>
                      </div>
                    );
                  })}
                </div>
              ) : (
                <p className="text-sm text-muted-foreground text-center py-2">
                  Tidak ada hari libur bulan ini
                </p>
              )}
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  );
}
