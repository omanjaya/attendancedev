import { useState, useMemo } from 'react';
import {
    User,
    Clock,
    Calendar,
    ChevronDown,
    ChevronUp,
    Filter,
    BookOpen,
} from 'lucide-react';

import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { useSchedules, useTimeSlots, useSubjects } from '@/hooks';
import { useEmployees } from '@/hooks/use-employees';
import type { Schedule, TimeSlot, Subject } from '@/types/schedule';

// Loading skeleton
function TeacherScheduleSkeleton() {
    return (
        <div className="space-y-4">
            {[...Array(3)].map((_, i) => (
                <Skeleton key={i} className="h-32 w-full" />
            ))}
        </div>
    );
}

const dayNames = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
const daysOfWeek = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];

interface TeacherScheduleGroup {
    teacherId: string;
    teacherName: string;
    schedules: Schedule[];
    totalHours: number;
    totalSessions: number;
}

export function TeacherScheduleContent() {
    const [selectedTeacherId, setSelectedTeacherId] = useState<string>('all');
    const [expandedTeachers, setExpandedTeachers] = useState<Set<string>>(new Set());

    // Fetch data
    const { data: schedulesResponse, isLoading: schedulesLoading } = useSchedules();
    const { data: timeSlots = [], isLoading: timeSlotsLoading } = useTimeSlots();
    const { data: subjects = [], isLoading: subjectsLoading } = useSubjects();
    const { data: employeesResponse, isLoading: employeesLoading } = useEmployees();

    const schedules = schedulesResponse?.data ?? [];
    const employees = employeesResponse?.data ?? [];

    const isLoading = schedulesLoading || timeSlotsLoading || subjectsLoading || employeesLoading;

    // Group schedules by teacher
    const teacherScheduleGroups = useMemo(() => {
        const groups: Record<string, TeacherScheduleGroup> = {};

        schedules.forEach((schedule) => {
            const teacherId = schedule.employee_id;
            const employee = employees.find((e) => String(e.id) === teacherId);
            const timeSlot = timeSlots.find((ts) => ts.id === schedule.time_slot_id);

            if (!groups[teacherId]) {
                groups[teacherId] = {
                    teacherId,
                    teacherName: employee?.name || schedule.employee?.name || 'Unknown',
                    schedules: [],
                    totalHours: 0,
                    totalSessions: 0,
                };
            }

            groups[teacherId].schedules.push(schedule);
            groups[teacherId].totalSessions++;

            // Calculate hours from time slot
            if (timeSlot) {
                const start = new Date(`2000-01-01T${timeSlot.start_time}`);
                const end = new Date(`2000-01-01T${timeSlot.end_time}`);
                const hours = (end.getTime() - start.getTime()) / (1000 * 60 * 60);
                groups[teacherId].totalHours += hours;
            }
        });

        return Object.values(groups).sort((a, b) => b.totalHours - a.totalHours);
    }, [schedules, employees, timeSlots]);

    // Filter by selected teacher
    const filteredGroups = useMemo(() => {
        if (selectedTeacherId === 'all') return teacherScheduleGroups;
        return teacherScheduleGroups.filter((g) => g.teacherId === selectedTeacherId);
    }, [teacherScheduleGroups, selectedTeacherId]);

    // Toggle expanded state
    const toggleExpanded = (teacherId: string) => {
        setExpandedTeachers((prev) => {
            const next = new Set(prev);
            if (next.has(teacherId)) {
                next.delete(teacherId);
            } else {
                next.add(teacherId);
            }
            return next;
        });
    };

    // Get subject info
    const getSubject = (subjectId: string): Subject | undefined => {
        return subjects.find((s) => s.id === subjectId);
    };

    // Get time slot info
    const getTimeSlot = (timeSlotId: string): TimeSlot | undefined => {
        return timeSlots.find((ts) => ts.id === timeSlotId);
    };

    // Group schedules by day
    const groupByDay = (scheduleList: Schedule[]) => {
        const byDay: Record<string, Schedule[]> = {};
        daysOfWeek.forEach((day) => {
            byDay[day] = scheduleList.filter((s) => s.day_of_week === day);
        });
        return byDay;
    };

    if (isLoading) {
        return <TeacherScheduleSkeleton />;
    }

    // Statistics
    const totalTeachers = teacherScheduleGroups.length;
    const totalHoursSum = teacherScheduleGroups.reduce((sum, g) => sum + g.totalHours, 0);
    const avgHoursPerTeacher = totalTeachers > 0 ? (totalHoursSum / totalTeachers).toFixed(1) : 0;

    return (
        <div className="space-y-6">
            {/* Header & Filter */}
            <div className="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 className="text-lg font-semibold">Jadwal Per Guru</h2>
                    <p className="text-sm text-muted-foreground">
                        Lihat jadwal mengajar dan beban kerja setiap guru
                    </p>
                </div>
                <div className="flex items-center gap-2">
                    <Filter className="h-4 w-4 text-muted-foreground" />
                    <Select value={selectedTeacherId} onValueChange={setSelectedTeacherId}>
                        <SelectTrigger className="w-[200px]">
                            <SelectValue placeholder="Pilih Guru" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Semua Guru</SelectItem>
                            {employees.map((emp) => (
                                <SelectItem key={emp.id} value={String(emp.id)}>
                                    {emp.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>
            </div>

            {/* Stats Summary */}
            <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                <Card>
                    <CardContent className="p-4">
                        <div className="flex items-center gap-3">
                            <User className="h-8 w-8 text-primary/30" />
                            <div>
                                <p className="text-2xl font-bold">{totalTeachers}</p>
                                <p className="text-xs text-muted-foreground">Total Guru</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent className="p-4">
                        <div className="flex items-center gap-3">
                            <Calendar className="h-8 w-8 text-success/30" />
                            <div>
                                <p className="text-2xl font-bold">{schedules.length}</p>
                                <p className="text-xs text-muted-foreground">Total Jadwal</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent className="p-4">
                        <div className="flex items-center gap-3">
                            <Clock className="h-8 w-8 text-warning/30" />
                            <div>
                                <p className="text-2xl font-bold">{totalHoursSum.toFixed(1)}</p>
                                <p className="text-xs text-muted-foreground">Total Jam/Minggu</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent className="p-4">
                        <div className="flex items-center gap-3">
                            <BookOpen className="h-8 w-8 text-info/30" />
                            <div>
                                <p className="text-2xl font-bold">{avgHoursPerTeacher}</p>
                                <p className="text-xs text-muted-foreground">Rata-rata Jam/Guru</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            {/* Teacher Schedule Cards */}
            <div className="space-y-4">
                {filteredGroups.length === 0 ? (
                    <Card>
                        <CardContent className="p-8 text-center text-muted-foreground">
                            <User className="h-12 w-12 mx-auto mb-2 opacity-30" />
                            <p>Tidak ada jadwal ditemukan</p>
                        </CardContent>
                    </Card>
                ) : (
                    filteredGroups.map((group) => {
                        const isExpanded = expandedTeachers.has(group.teacherId);
                        const schedulesByDay = groupByDay(group.schedules);

                        return (
                            <Card key={group.teacherId}>
                                <Collapsible open={isExpanded} onOpenChange={() => toggleExpanded(group.teacherId)}>
                                    <CollapsibleTrigger asChild>
                                        <CardHeader className="cursor-pointer hover:bg-muted/50 transition-colors">
                                            <div className="flex items-center justify-between">
                                                <div className="flex items-center gap-3">
                                                    <div className="h-10 w-10 rounded-full bg-primary/10 flex items-center justify-center">
                                                        <User className="h-5 w-5 text-primary" />
                                                    </div>
                                                    <div>
                                                        <CardTitle className="text-base">{group.teacherName}</CardTitle>
                                                        <CardDescription className="flex items-center gap-2">
                                                            <Clock className="h-3 w-3" />
                                                            {group.totalHours.toFixed(1)} jam/minggu
                                                            <span className="text-muted-foreground">•</span>
                                                            {group.totalSessions} sesi
                                                        </CardDescription>
                                                    </div>
                                                </div>
                                                <div className="flex items-center gap-2">
                                                    <Badge variant={group.totalHours > 24 ? 'destructive' : group.totalHours > 18 ? 'secondary' : 'outline'}>
                                                        {group.totalHours > 24 ? 'Overload' : group.totalHours > 18 ? 'Penuh' : 'Normal'}
                                                    </Badge>
                                                    {isExpanded ? (
                                                        <ChevronUp className="h-5 w-5 text-muted-foreground" />
                                                    ) : (
                                                        <ChevronDown className="h-5 w-5 text-muted-foreground" />
                                                    )}
                                                </div>
                                            </div>
                                        </CardHeader>
                                    </CollapsibleTrigger>
                                    <CollapsibleContent>
                                        <CardContent className="pt-0">
                                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                                {daysOfWeek.map((day, idx) => {
                                                    const daySchedules = schedulesByDay[day] || [];
                                                    if (daySchedules.length === 0) return null;

                                                    return (
                                                        <div key={day} className="border rounded-lg p-3">
                                                            <h4 className="font-medium text-sm mb-2">{dayNames[idx]}</h4>
                                                            <div className="space-y-2">
                                                                {daySchedules
                                                                    .sort((a, b) => {
                                                                        const tsA = getTimeSlot(a.time_slot_id);
                                                                        const tsB = getTimeSlot(b.time_slot_id);
                                                                        return (tsA?.start_time || '').localeCompare(tsB?.start_time || '');
                                                                    })
                                                                    .map((schedule) => {
                                                                        const subject = getSubject(schedule.subject_id);
                                                                        const timeSlot = getTimeSlot(schedule.time_slot_id);

                                                                        return (
                                                                            <div
                                                                                key={schedule.id}
                                                                                className="p-2 rounded text-xs"
                                                                                style={{
                                                                                    backgroundColor: subject?.color ? `${subject.color}20` : 'hsl(var(--muted))',
                                                                                    borderLeft: `3px solid ${subject?.color || 'hsl(var(--muted-foreground))'}`,
                                                                                }}
                                                                            >
                                                                                <div className="font-medium" style={{ color: subject?.color }}>
                                                                                    {subject?.name || 'Unknown'}
                                                                                </div>
                                                                                <div className="text-muted-foreground">
                                                                                    {timeSlot?.start_time} - {timeSlot?.end_time}
                                                                                </div>
                                                                                {schedule.room && (
                                                                                    <div className="text-muted-foreground/70">
                                                                                        Ruang: {schedule.room}
                                                                                    </div>
                                                                                )}
                                                                            </div>
                                                                        );
                                                                    })}
                                                            </div>
                                                        </div>
                                                    );
                                                })}
                                            </div>
                                        </CardContent>
                                    </CollapsibleContent>
                                </Collapsible>
                            </Card>
                        );
                    })
                )}
            </div>
        </div>
    );
}
