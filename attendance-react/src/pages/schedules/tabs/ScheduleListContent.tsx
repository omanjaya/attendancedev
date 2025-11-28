import { useState, useEffect } from 'react';
import {
  Calendar,
  Clock,
  Users,
  BookOpen,
  AlertTriangle,
  Lock,
  Plus,
  Filter,
  Grid3X3,
  CalendarDays,
  Download,
  Upload,
  Settings,
  ChevronRight,
  Loader2,
  XCircle,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { useSchedules } from '@/hooks/use-schedules';
import type { DayOfWeek, Schedule, TimeSlot } from '@/types/schedule';

// Day labels in Indonesian
const dayLabels: Record<DayOfWeek, string> = {
  monday: 'Senin',
  tuesday: 'Selasa',
  wednesday: 'Rabu',
  thursday: 'Kamis',
  friday: 'Jumat',
  saturday: 'Sabtu',
};

const days: DayOfWeek[] = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];

// Schedule Cell Component
function ScheduleCell({
  schedule,
  timeSlot,
  onEdit,
  onDelete,
  onToggleLock,
}: {
  schedule?: Schedule;
  timeSlot: TimeSlot;
  onEdit: (schedule: Schedule) => void;
  onDelete: (id: string) => void;
  onToggleLock: (id: string) => void;
}) {
  if (timeSlot.is_break) {
    return (
      <td className="border border-border bg-muted/30 p-2 text-center text-xs text-muted-foreground">
        Istirahat
      </td>
    );
  }

  if (!schedule) {
    return (
      <td className="border border-border p-2 hover:bg-muted/20 transition-colors cursor-pointer group">
        <div className="h-16 flex items-center justify-center">
          <Plus className="h-4 w-4 text-muted-foreground/50 opacity-0 group-hover:opacity-100 transition-opacity" />
        </div>
      </td>
    );
  }

  return (
    <td
      className={`border border-border p-1 transition-colors ${
        schedule.is_locked ? 'bg-red-50 dark:bg-red-950/20' : 'hover:bg-muted/20'
      }`}
    >
      <div
        className="h-16 rounded-md p-2 text-xs cursor-pointer group relative"
        style={{ backgroundColor: schedule.subject?.color ? `${schedule.subject.color}20` : undefined }}
        onClick={() => !schedule.is_locked && onEdit(schedule)}
      >
        {schedule.is_locked && (
          <Lock className="absolute top-1 right-1 h-3 w-3 text-red-500" />
        )}
        <div className="font-semibold truncate" style={{ color: schedule.subject?.color }}>
          {schedule.subject?.code}
        </div>
        <div className="text-muted-foreground truncate text-[10px]">
          {schedule.employee?.name?.split(',')[0]}
        </div>
        <div className="text-muted-foreground/70 text-[10px]">{schedule.room}</div>

        {/* Hover actions */}
        {!schedule.is_locked && (
          <div className="absolute inset-0 bg-background/80 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-1 rounded-md">
            <Button size="sm" variant="ghost" className="h-6 px-2" onClick={(e) => { e.stopPropagation(); onEdit(schedule); }}>
              Edit
            </Button>
            <Button size="sm" variant="ghost" className="h-6 px-2" onClick={(e) => { e.stopPropagation(); onToggleLock(schedule.id); }}>
              <Lock className="h-3 w-3" />
            </Button>
            <Button size="sm" variant="ghost" className="h-6 px-2 text-red-500" onClick={(e) => { e.stopPropagation(); onDelete(schedule.id); }}>
              <XCircle className="h-3 w-3" />
            </Button>
          </div>
        )}
      </div>
    </td>
  );
}

export function ScheduleListContent() {
  const [activeView, setActiveView] = useState<'grid' | 'list'>('grid');
  const [, setSelectedSchedule] = useState<Schedule | null>(null);

  const {
    isLoading,
    error,
    schedules,
    conflicts,
    selectedClassId,
    timeSlots,
    classes,
    subjects,
    fetchScheduleGrid,
    deleteSchedule,
    toggleLock,
    setSelectedClassId,
    clearError,
  } = useSchedules();

  // Load initial data
  useEffect(() => {
    if (classes.length > 0 && !selectedClassId) {
      setSelectedClassId(classes[0].id);
      fetchScheduleGrid(classes[0].id);
    }
  }, [classes, selectedClassId, setSelectedClassId, fetchScheduleGrid]);

  // Handle class change
  const handleClassChange = (classId: string) => {
    setSelectedClassId(classId);
    fetchScheduleGrid(classId);
  };

  // Get schedule for a specific cell
  const getScheduleForCell = (dayOfWeek: DayOfWeek, timeSlotId: string): Schedule | undefined => {
    return schedules.find(
      (s) => s.day_of_week === dayOfWeek && s.time_slot_id === timeSlotId
    );
  };

  // Stats
  const stats = {
    total: schedules.length,
    locked: schedules.filter((s) => s.is_locked).length,
    conflicts: conflicts.length,
    subjects: new Set(schedules.map((s) => s.subject_id)).size,
    teachers: new Set(schedules.map((s) => s.employee_id)).size,
  };

  const selectedClass = classes.find((c) => c.id === selectedClassId);

  return (
    <div className="space-y-6">
      {/* Action Bar */}
      <div className="flex flex-wrap items-center justify-between gap-4">
        <div className="flex items-center gap-2">
          <Button variant="outline" size="sm" className="gap-2">
            <Upload className="h-4 w-4" />
            Import
          </Button>
          <Button variant="outline" size="sm" className="gap-2">
            <Download className="h-4 w-4" />
            Export
          </Button>
        </div>
        <Button size="sm" className="gap-2">
          <Plus className="h-4 w-4" />
          Tambah Jadwal
        </Button>
      </div>

      {/* Quick Stats */}
      <div className="grid gap-4 sm:grid-cols-5">
        <Card className="bg-card/50">
          <CardContent className="p-4">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-xs text-muted-foreground">Total Jadwal</p>
                <p className="text-2xl font-bold">{stats.total}</p>
              </div>
              <BookOpen className="h-8 w-8 text-primary/30" />
            </div>
          </CardContent>
        </Card>

        <Card className="bg-card/50">
          <CardContent className="p-4">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-xs text-muted-foreground">Mata Pelajaran</p>
                <p className="text-2xl font-bold">{stats.subjects}</p>
              </div>
              <Grid3X3 className="h-8 w-8 text-blue-500/30" />
            </div>
          </CardContent>
        </Card>

        <Card className="bg-card/50">
          <CardContent className="p-4">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-xs text-muted-foreground">Guru Mengajar</p>
                <p className="text-2xl font-bold">{stats.teachers}</p>
              </div>
              <Users className="h-8 w-8 text-emerald-500/30" />
            </div>
          </CardContent>
        </Card>

        <Card className="bg-card/50">
          <CardContent className="p-4">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-xs text-muted-foreground">Terkunci</p>
                <p className="text-2xl font-bold">{stats.locked}</p>
              </div>
              <Lock className="h-8 w-8 text-amber-500/30" />
            </div>
          </CardContent>
        </Card>

        <Card className="bg-card/50">
          <CardContent className="p-4">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-xs text-muted-foreground">Konflik</p>
                <p className={`text-2xl font-bold ${stats.conflicts > 0 ? 'text-red-500' : ''}`}>
                  {stats.conflicts}
                </p>
              </div>
              <AlertTriangle className={`h-8 w-8 ${stats.conflicts > 0 ? 'text-red-500/30' : 'text-muted-foreground/30'}`} />
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Conflicts Alert */}
      {conflicts.length > 0 && (
        <Alert variant="destructive">
          <AlertTriangle className="h-4 w-4" />
          <AlertTitle>Terdapat {conflicts.length} Konflik Jadwal</AlertTitle>
          <AlertDescription>
            {conflicts[0].description}
            <Button variant="link" className="h-auto p-0 ml-2">
              Lihat semua konflik <ChevronRight className="h-3 w-3" />
            </Button>
          </AlertDescription>
        </Alert>
      )}

      {/* Error Alert */}
      {error && (
        <Alert variant="destructive">
          <XCircle className="h-4 w-4" />
          <AlertTitle>Error</AlertTitle>
          <AlertDescription>{error}</AlertDescription>
          <Button variant="ghost" size="sm" onClick={clearError}>
            Tutup
          </Button>
        </Alert>
      )}

      {/* Class Selector & View Toggle */}
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div className="flex items-center gap-4">
          <div className="flex items-center gap-2">
            <Filter className="h-4 w-4 text-muted-foreground" />
            <Select value={selectedClassId || ''} onValueChange={handleClassChange}>
              <SelectTrigger className="w-[200px]">
                <SelectValue placeholder="Pilih Kelas" />
              </SelectTrigger>
              <SelectContent>
                {classes.map((cls) => (
                  <SelectItem key={cls.id} value={cls.id}>
                    {cls.name} - {cls.homeroom_teacher_name}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>

          {selectedClass && (
            <Badge variant="secondary" className="gap-1">
              <Users className="h-3 w-3" />
              {selectedClass.student_count} Siswa
            </Badge>
          )}
        </div>

        <Tabs value={activeView} onValueChange={(v) => setActiveView(v as 'grid' | 'list')}>
          <TabsList>
            <TabsTrigger value="grid" className="gap-2">
              <Grid3X3 className="h-4 w-4" />
              Grid
            </TabsTrigger>
            <TabsTrigger value="list" className="gap-2">
              <CalendarDays className="h-4 w-4" />
              Calendar
            </TabsTrigger>
          </TabsList>
        </Tabs>
      </div>

      {/* Main Content */}
      {isLoading ? (
        <Card>
          <CardContent className="flex items-center justify-center py-20">
            <div className="text-center">
              <Loader2 className="h-8 w-8 animate-spin text-primary mx-auto" />
              <p className="mt-2 text-sm text-muted-foreground">Memuat jadwal...</p>
            </div>
          </CardContent>
        </Card>
      ) : (
        <Card>
          <CardHeader className="pb-4">
            <div className="flex items-center justify-between">
              <div>
                <CardTitle className="flex items-center gap-2">
                  <Calendar className="h-5 w-5 text-primary" />
                  Jadwal Kelas {selectedClass?.name}
                </CardTitle>
                <CardDescription>
                  Tahun Ajaran 2024/2025 - Semester Ganjil
                </CardDescription>
              </div>
              <Button variant="outline" size="sm" className="gap-2">
                <Settings className="h-4 w-4" />
                Pengaturan
              </Button>
            </div>
          </CardHeader>
          <CardContent>
            {activeView === 'grid' ? (
              <div className="overflow-x-auto">
                <table className="w-full border-collapse text-sm">
                  <thead>
                    <tr>
                      <th className="border border-border bg-muted/50 p-2 text-left font-medium w-24">
                        <Clock className="h-4 w-4 inline-block mr-1" />
                        Jam
                      </th>
                      {days.map((day) => (
                        <th key={day} className="border border-border bg-muted/50 p-2 text-center font-medium">
                          {dayLabels[day]}
                        </th>
                      ))}
                    </tr>
                  </thead>
                  <tbody>
                    {timeSlots.map((slot) => (
                      <tr key={slot.id}>
                        <td className="border border-border bg-muted/30 p-2">
                          <div className="font-medium text-xs">{slot.name}</div>
                          <div className="text-[10px] text-muted-foreground">
                            {slot.start_time} - {slot.end_time}
                          </div>
                        </td>
                        {days.map((day) => (
                          <ScheduleCell
                            key={`${day}-${slot.id}`}
                            schedule={getScheduleForCell(day, slot.id)}
                            timeSlot={slot}
                            onEdit={setSelectedSchedule}
                            onDelete={deleteSchedule}
                            onToggleLock={toggleLock}
                          />
                        ))}
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            ) : (
              <div className="space-y-4">
                {days.map((day) => {
                  const daySchedules = schedules
                    .filter((s) => s.day_of_week === day)
                    .sort((a, b) => {
                      const slotA = timeSlots.find((t) => t.id === a.time_slot_id);
                      const slotB = timeSlots.find((t) => t.id === b.time_slot_id);
                      return (slotA?.start_time || '').localeCompare(slotB?.start_time || '');
                    });

                  return (
                    <div key={day} className="space-y-2">
                      <h3 className="font-semibold text-foreground flex items-center gap-2">
                        <CalendarDays className="h-4 w-4 text-primary" />
                        {dayLabels[day]}
                        <Badge variant="secondary" className="ml-2">
                          {daySchedules.length} jadwal
                        </Badge>
                      </h3>
                      <div className="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        {daySchedules.map((schedule) => (
                          <Card
                            key={schedule.id}
                            className={`cursor-pointer transition-colors ${
                              schedule.is_locked
                                ? 'bg-red-50 dark:bg-red-950/20 border-red-200'
                                : 'hover:bg-muted/50'
                            }`}
                            onClick={() => !schedule.is_locked && setSelectedSchedule(schedule)}
                          >
                            <CardContent className="p-3">
                              <div className="flex items-start justify-between">
                                <div>
                                  <div
                                    className="font-semibold"
                                    style={{ color: schedule.subject?.color }}
                                  >
                                    {schedule.subject?.name}
                                  </div>
                                  <div className="text-xs text-muted-foreground">
                                    {schedule.time_slot?.start_time} - {schedule.time_slot?.end_time}
                                  </div>
                                  <div className="text-xs text-muted-foreground mt-1">
                                    {schedule.employee?.name}
                                  </div>
                                </div>
                                <div className="flex items-center gap-1">
                                  {schedule.is_locked && (
                                    <Lock className="h-4 w-4 text-red-500" />
                                  )}
                                  <Badge variant="outline" className="text-[10px]">
                                    {schedule.room}
                                  </Badge>
                                </div>
                              </div>
                            </CardContent>
                          </Card>
                        ))}
                        {daySchedules.length === 0 && (
                          <p className="text-sm text-muted-foreground col-span-full py-4 text-center">
                            Belum ada jadwal untuk hari ini
                          </p>
                        )}
                      </div>
                    </div>
                  );
                })}
              </div>
            )}
          </CardContent>
        </Card>
      )}

      {/* Legend */}
      <Card>
        <CardHeader className="pb-2">
          <CardTitle className="text-sm">Keterangan Warna Mata Pelajaran</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="flex flex-wrap gap-3">
            {subjects.map((subject) => (
              <div key={subject.id} className="flex items-center gap-2">
                <div
                  className="h-3 w-3 rounded"
                  style={{ backgroundColor: subject.color }}
                />
                <span className="text-xs text-muted-foreground">
                  {subject.code} - {subject.name}
                </span>
              </div>
            ))}
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
