import { useState, useMemo } from 'react';
import { Link } from '@tanstack/react-router';
import { useQuery } from '@tanstack/react-query';
import {
  ChevronLeft,
  ChevronRight,
  Calendar as CalendarIcon,
  Users,
  Palmtree,
  Stethoscope,
  Baby,
  User,
  Loader2,
} from 'lucide-react';

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

import { Button } from '@/components/ui/button';

import { Badge } from '@/components/ui/badge';

import { Avatar, AvatarFallback } from '@/components/ui/avatar';

import { getLeaveRequests, getLeaveStatistics } from '@/lib/api/leave';
import type { LeaveType } from '@/types';

interface LeaveCalendarItem {
  id: string;
  employee: string;
  type: LeaveType;
  status: 'approved' | 'pending' | 'rejected' | 'cancelled';
}

const daysOfWeek = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
const months = [
  'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

const leaveTypeConfig: Record<string, { label: string; icon: typeof Palmtree; color: string }> = {
  annual: { label: 'Cuti Tahunan', icon: Palmtree, color: 'bg-primary/10 text-primary' },
  sick: { label: 'Cuti Sakit', icon: Stethoscope, color: 'bg-destructive/10 text-destructive' },
  maternity: { label: 'Cuti Melahirkan', icon: Baby, color: 'bg-pink-100 text-pink-600' },
  personal: { label: 'Cuti Pribadi', icon: User, color: 'bg-warning/10 text-warning' },
  other: { label: 'Cuti Lainnya', icon: User, color: 'bg-muted text-muted-foreground' },
};

export default function LeaveCalendarPage() {
  const [currentDate, setCurrentDate] = useState(new Date());
  const [selectedDate, setSelectedDate] = useState<string | null>(null);

  const year = currentDate.getFullYear();
  const month = currentDate.getMonth() + 1;

  // Calculate start and end dates for the month
  const startDate = `${year}-${String(month).padStart(2, '0')}-01`;
  const endDate = `${year}-${String(month).padStart(2, '0')}-${new Date(year, month, 0).getDate()}`;

  // Fetch leave requests for the month
  const { data: leavesData, isLoading: leavesLoading } = useQuery({
    queryKey: ['leave-requests', 'calendar', year, month],
    queryFn: () => getLeaveRequests({
      start_date: startDate,
      end_date: endDate,
      per_page: 100,
    }),
  });

  // Fetch leave statistics
  const { data: stats } = useQuery({
    queryKey: ['leave', 'statistics'],
    queryFn: getLeaveStatistics,
  });

  // Transform leave data to calendar format
  const leavesByDate = useMemo(() => {
    const result: Record<string, LeaveCalendarItem[]> = {};

    if (leavesData?.data) {
      leavesData.data.forEach((leave) => {
        // Add leave to each day in the range
        const start = new Date(leave.start_date);
        const end = new Date(leave.end_date);

        for (let d = new Date(start); d <= end; d.setDate(d.getDate() + 1)) {
          const dateStr = d.toISOString().split('T')[0];
          if (!result[dateStr]) {
            result[dateStr] = [];
          }
          result[dateStr].push({
            id: String(leave.id),
            employee: leave.employee_name || 'Unknown',
            type: (leave.type || leave.leave_type?.code || 'other') as LeaveType,
            status: leave.status as 'approved' | 'pending' | 'rejected' | 'cancelled',
          });
        }
      });
    }

    return result;
  }, [leavesData]);

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
    <div className="p-4 sm:p-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
          <h1 className="text-2xl font-bold text-foreground">Kalender Cuti</h1>
          <p className="text-sm text-muted-foreground">
            Lihat jadwal cuti karyawan dalam tampilan kalender
          </p>
        </div>
        <Button asChild>
          <Link to="/admin/leave/create">Ajukan Cuti</Link>
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
                  const leaves = leavesByDate[day.dateString] || [];
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
                        {leavesLoading ? (
                          <Loader2 className="h-3 w-3 animate-spin text-muted-foreground" />
                        ) : (
                          <>
                            {leaves.slice(0, 2).map((leave) => {
                              const config = leaveTypeConfig[leave.type] || leaveTypeConfig.personal;
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
                          </>
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
              {selectedDate && leavesByDate[selectedDate]?.length > 0 ? (
                <div className="space-y-3">
                  {leavesByDate[selectedDate].map((leave) => {
                    const config = leaveTypeConfig[leave.type] || leaveTypeConfig.personal;
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
                          className={
                            leave.status === 'approved' ? 'bg-success/10 text-success' :
                            leave.status === 'rejected' ? 'bg-destructive/10 text-destructive' :
                            'bg-warning/10 text-warning'
                          }
                        >
                          {leave.status === 'approved' ? 'Disetujui' :
                           leave.status === 'rejected' ? 'Ditolak' :
                           leave.status === 'cancelled' ? 'Dibatalkan' : 'Pending'}
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
                Statistik
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-3">
              <div className="flex justify-between">
                <span className="text-sm text-muted-foreground">Total Cuti</span>
                <Badge variant="outline">{stats?.total_requests || leavesData?.data?.length || 0}</Badge>
              </div>
              <div className="flex justify-between">
                <span className="text-sm text-muted-foreground">Disetujui</span>
                <Badge className="bg-success/10 text-success">{stats?.approved_requests || 0}</Badge>
              </div>
              <div className="flex justify-between">
                <span className="text-sm text-muted-foreground">Pending</span>
                <Badge className="bg-warning/10 text-warning">{stats?.pending_requests || 0}</Badge>
              </div>
              <div className="flex justify-between">
                <span className="text-sm text-muted-foreground">Ditolak</span>
                <Badge className="bg-destructive/10 text-destructive">{stats?.rejected_requests || 0}</Badge>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  );
}
