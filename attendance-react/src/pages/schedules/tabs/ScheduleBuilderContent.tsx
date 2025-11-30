import { useState } from 'react';
import {
  Calendar,
  Clock,
  Users,
  GripVertical,
  Plus,
  Trash2,
  Save,
  Copy,
  AlertTriangle,
  CheckCircle,
  Loader2,
  Filter,
} from 'lucide-react';

import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Skeleton } from '@/components/ui/skeleton';
import {
  useTimeSlots,
  useSubjects,
  useAcademicClasses,
  useSchedulesByClass,
  useCreateSchedule,
  useUpdateSchedule,
  useDeleteSchedule,
} from '@/hooks';
import { useEmployees } from '@/hooks/use-employees';
import type { DayOfWeek, Schedule, ScheduleFormData } from '@/types/schedule';

// Types
interface ScheduleItem {
  id: string;
  dayOfWeek: number;
  timeSlotId: string;
  subjectId: string;
  teacherId: string;
  room: string;
}

const dayNames = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
const daysOfWeek: DayOfWeek[] = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];

// Loading skeleton
function BuilderSkeleton() {
  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <Skeleton className="h-9 w-24" />
        <Skeleton className="h-9 w-32" />
      </div>
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
        {[...Array(4)].map((_, i) => (
          <Skeleton key={i} className="h-20" />
        ))}
      </div>
      <Skeleton className="h-96" />
    </div>
  );
}

export function ScheduleBuilderContent() {
  const [selectedClassId, setSelectedClassId] = useState<string>('');
  const [localScheduleItems, setLocalScheduleItems] = useState<ScheduleItem[]>([]);
  const [selectedCell, setSelectedCell] = useState<{ day: number; slotId: string } | null>(null);
  const [dialogOpen, setDialogOpen] = useState(false);
  const [isSavingAll, setIsSavingAll] = useState(false);
  const [conflicts, setConflicts] = useState<string[]>([]);

  const [formData, setFormData] = useState({
    subjectId: '',
    teacherId: '',
    room: '',
  });

  // Fetch data using React Query hooks
  const { data: classes = [], isLoading: classesLoading } = useAcademicClasses();
  const { data: timeSlots = [], isLoading: timeSlotsLoading } = useTimeSlots();
  const { data: subjects = [], isLoading: subjectsLoading } = useSubjects();
  const { data: employeesResponse } = useEmployees();
  const employees = employeesResponse?.data ?? [];
  const { data: existingSchedules = [] } = useSchedulesByClass(selectedClassId);

  // Mutations
  const createScheduleMutation = useCreateSchedule();
  const updateScheduleMutation = useUpdateSchedule();
  const deleteScheduleMutation = useDeleteSchedule();

  const isLoading = classesLoading || timeSlotsLoading || subjectsLoading;

  // Set initial class when classes are loaded
  if (classes.length > 0 && !selectedClassId) {
    setSelectedClassId(classes[0].id);
  }

  // Convert existing schedules to local format
  const getExistingScheduleItem = (dayOfWeek: number, timeSlotId: string): Schedule | undefined => {
    const dayKey = daysOfWeek[dayOfWeek];
    return existingSchedules.find(
      (s) => s.day_of_week === dayKey && s.time_slot_id === timeSlotId
    );
  };

  const getLocalScheduleItem = (dayOfWeek: number, timeSlotId: string) => {
    return localScheduleItems.find(
      (item) => item.dayOfWeek === dayOfWeek && item.timeSlotId === timeSlotId
    );
  };

  const handleCellClick = (day: number, slotId: string) => {
    const slot = timeSlots.find((s) => s.id === slotId);
    if (slot?.is_break) return;

    const existingFromApi = getExistingScheduleItem(day, slotId);
    const existingLocal = getLocalScheduleItem(day, slotId);

    if (existingFromApi) {
      setFormData({
        subjectId: existingFromApi.subject_id,
        teacherId: existingFromApi.employee_id,
        room: existingFromApi.room || '',
      });
    } else if (existingLocal) {
      setFormData({
        subjectId: existingLocal.subjectId,
        teacherId: existingLocal.teacherId,
        room: existingLocal.room,
      });
    } else {
      setFormData({ subjectId: '', teacherId: '', room: '' });
    }
    setSelectedCell({ day, slotId });
    setConflicts([]);
    setDialogOpen(true);
  };

  const handleSaveItem = async () => {
    if (!selectedCell || !formData.subjectId || !formData.teacherId || !formData.room) return;

    const existingFromApi = getExistingScheduleItem(selectedCell.day, selectedCell.slotId);

    if (existingFromApi) {
      // Update existing schedule via API
      await updateScheduleMutation.mutateAsync({
        id: existingFromApi.id,
        data: {
          subject_id: formData.subjectId,
          employee_id: formData.teacherId,
          room: formData.room,
        },
      });
    } else {
      // Add to local state for batch save
      const existingLocal = getLocalScheduleItem(selectedCell.day, selectedCell.slotId);

      if (existingLocal) {
        setLocalScheduleItems(
          localScheduleItems.map((item) =>
            item.id === existingLocal.id ? { ...item, ...formData } : item
          )
        );
      } else {
        const newId = String(new Date().getTime());
        setLocalScheduleItems([
          ...localScheduleItems,
          {
            id: newId,
            dayOfWeek: selectedCell.day,
            timeSlotId: selectedCell.slotId,
            subjectId: formData.subjectId,
            teacherId: formData.teacherId,
            room: formData.room,
          },
        ]);
      }
    }

    setDialogOpen(false);
  };

  const handleDeleteItem = async () => {
    if (!selectedCell) return;

    const existingFromApi = getExistingScheduleItem(selectedCell.day, selectedCell.slotId);

    if (existingFromApi) {
      await deleteScheduleMutation.mutateAsync(existingFromApi.id);
    } else {
      const existingLocal = getLocalScheduleItem(selectedCell.day, selectedCell.slotId);
      if (existingLocal) {
        setLocalScheduleItems(localScheduleItems.filter((item) => item.id !== existingLocal.id));
      }
    }

    setDialogOpen(false);
  };

  const handleCopyDay = (fromDay: number, toDay: number) => {
    const dayItems = localScheduleItems.filter((item) => item.dayOfWeek === fromDay);
    const newItems = dayItems.map((item, idx) => ({
      ...item,
      id: String(new Date().getTime()) + idx,
      dayOfWeek: toDay,
    }));
    setLocalScheduleItems([
      ...localScheduleItems.filter((item) => item.dayOfWeek !== toDay),
      ...newItems,
    ]);
  };

  const handleSaveAll = async () => {
    if (!selectedClassId || localScheduleItems.length === 0) return;

    setIsSavingAll(true);
    try {
      // Create all new schedules
      for (const item of localScheduleItems) {
        const scheduleData: ScheduleFormData = {
          subject_id: item.subjectId,
          employee_id: item.teacherId,
          time_slot_id: item.timeSlotId,
          day_of_week: daysOfWeek[item.dayOfWeek],
          room: item.room,
          effective_from: new Date().toISOString().split('T')[0], // Today's date
        };
        await createScheduleMutation.mutateAsync({
          classId: selectedClassId,
          data: scheduleData,
        });
      }
      // Clear local items after successful save
      setLocalScheduleItems([]);
    } finally {
      setIsSavingAll(false);
    }
  };

  // Stats combining API and local data
  const allScheduleItems = [
    ...existingSchedules.map((s) => ({
      id: s.id,
      subjectId: s.subject_id,
      teacherId: s.employee_id,
    })),
    ...localScheduleItems.map((s) => ({
      id: s.id,
      subjectId: s.subjectId,
      teacherId: s.teacherId,
    })),
  ];

  const stats = {
    total: allScheduleItems.length,
    teachers: new Set(allScheduleItems.map((i) => i.teacherId)).size,
    subjects: new Set(allScheduleItems.map((i) => i.subjectId)).size,
    lessonSlots: timeSlots.filter((s) => !s.is_break).length,
    unsaved: localScheduleItems.length,
  };

  if (isLoading) {
    return <BuilderSkeleton />;
  }

  return (
    <div className="space-y-6">
      {/* Class Selector & Action Bar */}
      <div className="flex flex-wrap items-center justify-between gap-4">
        <div className="flex items-center gap-4">
          <div className="flex items-center gap-2">
            <Filter className="h-4 w-4 text-muted-foreground" />
            <Select value={selectedClassId} onValueChange={setSelectedClassId}>
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

          {stats.unsaved > 0 && (
            <span className="text-sm text-warning">
              {stats.unsaved} jadwal belum disimpan
            </span>
          )}
        </div>

        <div className="flex gap-2">
          <Button variant="outline" onClick={() => setLocalScheduleItems([])}>
            <Trash2 className="h-4 w-4 mr-2" />
            Reset
          </Button>
          <Button
            onClick={handleSaveAll}
            disabled={isSavingAll || localScheduleItems.length === 0}
          >
            {isSavingAll ? (
              <Loader2 className="h-4 w-4 mr-2 animate-spin" />
            ) : (
              <Save className="h-4 w-4 mr-2" />
            )}
            Simpan Semua ({stats.unsaved})
          </Button>
        </div>
      </div>

      {/* Stats */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
        <Card>
          <CardContent className="p-4">
            <div className="flex items-center gap-3">
              <Calendar className="h-8 w-8 text-primary/30" />
              <div>
                <p className="text-2xl font-bold">{stats.total}</p>
                <p className="text-xs text-muted-foreground">Total Jadwal</p>
              </div>
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="p-4">
            <div className="flex items-center gap-3">
              <Users className="h-8 w-8 text-success/30" />
              <div>
                <p className="text-2xl font-bold">{stats.teachers}</p>
                <p className="text-xs text-muted-foreground">Guru</p>
              </div>
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="p-4">
            <div className="flex items-center gap-3">
              <Clock className="h-8 w-8 text-warning/30" />
              <div>
                <p className="text-2xl font-bold">{stats.lessonSlots}</p>
                <p className="text-xs text-muted-foreground">Jam Pelajaran</p>
              </div>
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="p-4">
            <div className="flex items-center gap-3">
              <CheckCircle className="h-8 w-8 text-primary/30" />
              <div>
                <p className="text-2xl font-bold">{stats.subjects}</p>
                <p className="text-xs text-muted-foreground">Mata Pelajaran</p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Schedule Grid */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <GripVertical className="h-5 w-5 text-primary" />
            Jadwal Mingguan
          </CardTitle>
          <CardDescription>Klik pada sel untuk menambah atau mengedit jadwal</CardDescription>
        </CardHeader>
        <CardContent>
          <div className="overflow-x-auto">
            <table className="w-full border-collapse">
              <thead>
                <tr>
                  <th className="border bg-muted/50 p-2 w-24">
                    <Clock className="h-4 w-4 inline-block mr-1" />
                    Jam
                  </th>
                  {dayNames.slice(0, 5).map((day, index) => (
                    <th key={day} className="border bg-muted/50 p-2 text-center min-w-[140px]">
                      <div className="flex items-center justify-between">
                        <span>{day}</span>
                        <Button
                          variant="ghost"
                          size="icon"
                          className="h-6 w-6"
                          onClick={() => handleCopyDay(index, (index + 1) % 5)}
                          title="Copy ke hari berikutnya"
                        >
                          <Copy className="h-3 w-3" />
                        </Button>
                      </div>
                    </th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {timeSlots.map((slot) => (
                  <tr key={slot.id}>
                    <td className="border bg-muted/30 p-2">
                      <div className="text-xs font-medium">{slot.name}</div>
                      <div className="text-[10px] text-muted-foreground">
                        {slot.start_time} - {slot.end_time}
                      </div>
                    </td>
                    {dayNames.slice(0, 5).map((_, dayIndex) => {
                      if (slot.is_break) {
                        return (
                          <td
                            key={dayIndex}
                            className="border bg-muted/20 p-2 text-center text-xs text-muted-foreground"
                          >
                            Istirahat
                          </td>
                        );
                      }

                      // Check API schedule first, then local
                      const existingFromApi = getExistingScheduleItem(dayIndex, slot.id);
                      const localItem = getLocalScheduleItem(dayIndex, slot.id);
                      const item = existingFromApi || localItem;

                      let subject;
                      let teacher;
                      let room;
                      let isFromApi = false;

                      if (existingFromApi) {
                        subject = subjects.find((s) => s.id === existingFromApi.subject_id);
                        teacher = employees.find((t) => String(t.id) === existingFromApi.employee_id);
                        room = existingFromApi.room;
                        isFromApi = true;
                      } else if (localItem) {
                        subject = subjects.find((s) => s.id === localItem.subjectId);
                        teacher = employees.find((t) => String(t.id) === localItem.teacherId);
                        room = localItem.room;
                      }

                      return (
                        <td
                          key={dayIndex}
                          className={`border p-1 cursor-pointer hover:bg-muted/20 transition-colors ${
                            localItem && !isFromApi ? 'bg-warning/10' : ''
                          }`}
                          onClick={() => handleCellClick(dayIndex, slot.id)}
                        >
                          {item ? (
                            <div
                              className="h-16 rounded p-2 text-xs"
                              style={{ backgroundColor: subject?.color ? subject.color + '20' : undefined }}
                            >
                              <div className="font-semibold truncate" style={{ color: subject?.color }}>
                                {subject?.code}
                              </div>
                              <div className="text-muted-foreground truncate text-[10px]">
                                {teacher?.name?.split(',')[0]}
                              </div>
                              <div className="text-muted-foreground/70 text-[10px]">{room}</div>
                            </div>
                          ) : (
                            <div className="h-16 flex items-center justify-center group">
                              <Plus className="h-4 w-4 text-muted-foreground/30 group-hover:text-muted-foreground transition-colors" />
                            </div>
                          )}
                        </td>
                      );
                    })}
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </CardContent>
      </Card>

      {/* Legend */}
      <Card>
        <CardHeader className="pb-2">
          <CardTitle className="text-sm">Keterangan Warna</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="flex flex-wrap gap-3">
            {subjects.map((subject) => (
              <div key={subject.id} className="flex items-center gap-2">
                <div className="h-3 w-3 rounded" style={{ backgroundColor: subject.color }} />
                <span className="text-xs text-muted-foreground">
                  {subject.code} - {subject.name}
                </span>
              </div>
            ))}
          </div>
        </CardContent>
      </Card>

      {/* Edit Dialog */}
      <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>
              {selectedCell && (getExistingScheduleItem(selectedCell.day, selectedCell.slotId) || getLocalScheduleItem(selectedCell.day, selectedCell.slotId))
                ? 'Edit Jadwal'
                : 'Tambah Jadwal'}
            </DialogTitle>
            <DialogDescription>
              {selectedCell && (
                <>
                  {dayNames[selectedCell.day]} -{' '}
                  {timeSlots.find((s) => s.id === selectedCell.slotId)?.name}
                </>
              )}
            </DialogDescription>
          </DialogHeader>

          {conflicts.length > 0 && (
            <Alert variant="destructive">
              <AlertTriangle className="h-4 w-4" />
              <AlertDescription>
                <ul className="list-disc list-inside">
                  {conflicts.map((conflict, i) => (
                    <li key={i}>{conflict}</li>
                  ))}
                </ul>
              </AlertDescription>
            </Alert>
          )}

          <div className="space-y-4">
            <div className="space-y-2">
              <label className="text-sm font-medium">Mata Pelajaran</label>
              <Select
                value={formData.subjectId}
                onValueChange={(value) => setFormData({ ...formData, subjectId: value })}
              >
                <SelectTrigger>
                  <SelectValue placeholder="Pilih mata pelajaran" />
                </SelectTrigger>
                <SelectContent>
                  {subjects.map((subject) => (
                    <SelectItem key={subject.id} value={subject.id}>
                      <div className="flex items-center gap-2">
                        <div className="h-2 w-2 rounded-full" style={{ backgroundColor: subject.color }} />
                        {subject.name}
                      </div>
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            <div className="space-y-2">
              <label className="text-sm font-medium">Guru</label>
              <Select
                value={formData.teacherId}
                onValueChange={(value) => setFormData({ ...formData, teacherId: value })}
              >
                <SelectTrigger>
                  <SelectValue placeholder="Pilih guru" />
                </SelectTrigger>
                <SelectContent>
                  {employees.map((employee) => (
                    <SelectItem key={employee.id} value={String(employee.id)}>
                      {employee.name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            <div className="space-y-2">
              <label className="text-sm font-medium">Ruangan</label>
              <Input
                placeholder="Contoh: R.101"
                value={formData.room}
                onChange={(e) => setFormData({ ...formData, room: e.target.value })}
              />
            </div>
          </div>

          <DialogFooter className="gap-2">
            {selectedCell && (getExistingScheduleItem(selectedCell.day, selectedCell.slotId) || getLocalScheduleItem(selectedCell.day, selectedCell.slotId)) && (
              <Button
                variant="destructive"
                onClick={handleDeleteItem}
                disabled={deleteScheduleMutation.isPending}
              >
                {deleteScheduleMutation.isPending ? (
                  <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                ) : (
                  <Trash2 className="h-4 w-4 mr-2" />
                )}
                Hapus
              </Button>
            )}
            <Button variant="outline" onClick={() => setDialogOpen(false)}>
              Batal
            </Button>
            <Button
              onClick={handleSaveItem}
              disabled={updateScheduleMutation.isPending || !formData.subjectId || !formData.teacherId || !formData.room}
            >
              {updateScheduleMutation.isPending ? (
                <Loader2 className="h-4 w-4 mr-2 animate-spin" />
              ) : (
                <Save className="h-4 w-4 mr-2" />
              )}
              Simpan
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
