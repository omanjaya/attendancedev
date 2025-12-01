import { useState } from 'react';
import { Link } from '@tanstack/react-router';
import {
  ArrowLeft,
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
} from 'lucide-react';

import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { LoadingState } from '@/components/states';
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
import { useNotificationStore } from '@/stores';

// Types
interface TimeSlot {
  id: string;
  name: string;
  startTime: string;
  endTime: string;
  isBreak: boolean;
}

interface ScheduleItem {
  id: string;
  dayOfWeek: number;
  timeSlotId: string;
  subjectId: string;
  teacherId: string;
  room: string;
}

// Mock data
const mockSubjects = [
  { id: '1', name: 'Matematika', code: 'MTK', color: '#3B82F6' },
  { id: '2', name: 'Bahasa Indonesia', code: 'BIN', color: '#10B981' },
  { id: '3', name: 'Bahasa Inggris', code: 'BIG', color: '#8B5CF6' },
  { id: '4', name: 'Fisika', code: 'FIS', color: '#F59E0B' },
  { id: '5', name: 'Kimia', code: 'KIM', color: '#EF4444' },
  { id: '6', name: 'Biologi', code: 'BIO', color: '#06B6D4' },
];

const mockTeachers = [
  { id: '1', name: 'Ahmad Fauzi, S.Pd' },
  { id: '2', name: 'Siti Rahayu, M.Pd' },
  { id: '3', name: 'Budi Santoso, S.Pd' },
  { id: '4', name: 'Dewi Lestari, M.Pd' },
  { id: '5', name: 'Eko Prasetyo, S.Pd' },
];

const defaultTimeSlots: TimeSlot[] = [
  { id: '1', name: 'Jam 1', startTime: '07:00', endTime: '07:45', isBreak: false },
  { id: '2', name: 'Jam 2', startTime: '07:45', endTime: '08:30', isBreak: false },
  { id: '3', name: 'Jam 3', startTime: '08:30', endTime: '09:15', isBreak: false },
  { id: 'break1', name: 'Istirahat', startTime: '09:15', endTime: '09:30', isBreak: true },
  { id: '4', name: 'Jam 4', startTime: '09:30', endTime: '10:15', isBreak: false },
  { id: '5', name: 'Jam 5', startTime: '10:15', endTime: '11:00', isBreak: false },
  { id: '6', name: 'Jam 6', startTime: '11:00', endTime: '11:45', isBreak: false },
  { id: 'break2', name: 'Istirahat', startTime: '11:45', endTime: '12:30', isBreak: true },
  { id: '7', name: 'Jam 7', startTime: '12:30', endTime: '13:15', isBreak: false },
  { id: '8', name: 'Jam 8', startTime: '13:15', endTime: '14:00', isBreak: false },
];

const dayNames = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

export default function ScheduleBuilderPage() {
  const { success } = useNotificationStore();
  const [timeSlots] = useState<TimeSlot[]>(defaultTimeSlots);
  const [scheduleItems, setScheduleItems] = useState<ScheduleItem[]>([]);
  const [selectedCell, setSelectedCell] = useState<{ day: number; slotId: string } | null>(null);
  const [dialogOpen, setDialogOpen] = useState(false);
  const [isLoading, setIsLoading] = useState(false);
  const [conflicts, setConflicts] = useState<string[]>([]);

  const [formData, setFormData] = useState({
    subjectId: '',
    teacherId: '',
    room: '',
  });

  const getScheduleItem = (dayOfWeek: number, timeSlotId: string) => {
    return scheduleItems.find(
      (item) => item.dayOfWeek === dayOfWeek && item.timeSlotId === timeSlotId
    );
  };

  const handleCellClick = (day: number, slotId: string) => {
    const slot = timeSlots.find((s) => s.id === slotId);
    if (slot?.isBreak) return;

    const existing = getScheduleItem(day, slotId);
    if (existing) {
      setFormData({
        subjectId: existing.subjectId,
        teacherId: existing.teacherId,
        room: existing.room,
      });
    } else {
      setFormData({ subjectId: '', teacherId: '', room: '' });
    }
    setSelectedCell({ day, slotId });
    setConflicts([]);
    setDialogOpen(true);
  };

  const handleSaveItem = () => {
    if (!selectedCell || !formData.subjectId || !formData.teacherId || !formData.room) return;

    const existingItem = getScheduleItem(selectedCell.day, selectedCell.slotId);

    if (existingItem) {
      setScheduleItems(
        scheduleItems.map((item) =>
          item.id === existingItem.id ? { ...item, ...formData } : item
        )
      );
    } else {
      const newId = String(new Date().getTime());
      setScheduleItems([
        ...scheduleItems,
        {
          id: newId,
          dayOfWeek: selectedCell.day,
          timeSlotId: selectedCell.slotId,
          ...formData,
        },
      ]);
    }

    setDialogOpen(false);
    success('Berhasil', 'Jadwal berhasil disimpan');
  };

  const handleDeleteItem = () => {
    if (!selectedCell) return;
    const existing = getScheduleItem(selectedCell.day, selectedCell.slotId);
    if (existing) {
      setScheduleItems(scheduleItems.filter((item) => item.id !== existing.id));
      setDialogOpen(false);
      success('Berhasil', 'Jadwal berhasil dihapus');
    }
  };

  const handleCopyDay = (fromDay: number, toDay: number) => {
    const dayItems = scheduleItems.filter((item) => item.dayOfWeek === fromDay);
    const newItems = dayItems.map((item, idx) => ({
      ...item,
      id: String(new Date().getTime()) + idx,
      dayOfWeek: toDay,
    }));
    setScheduleItems([
      ...scheduleItems.filter((item) => item.dayOfWeek !== toDay),
      ...newItems,
    ]);
    success('Berhasil', `Jadwal hari ${dayNames[fromDay]} disalin ke ${dayNames[toDay]}`);
  };

  const handleSaveAll = async () => {
    setIsLoading(true);
    await new Promise((resolve) => setTimeout(resolve, 1500));
    setIsLoading(false);
    success('Berhasil', 'Semua jadwal berhasil disimpan');
  };

  return (
    <div className="p-4 sm:p-6">
      <div className="mb-6">
        <Link
          to="/schedules"
          className="inline-flex items-center text-sm text-muted-foreground hover:text-foreground mb-4"
        >
          <ArrowLeft className="h-4 w-4 mr-2" />
          Kembali ke jadwal
        </Link>
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-2xl font-bold text-foreground">Schedule Builder</h1>
            <p className="text-sm text-muted-foreground">Buat dan atur jadwal dengan mudah</p>
          </div>
          <div className="flex gap-2">
            <Button variant="outline" onClick={() => setScheduleItems([])}>
              <Trash2 className="h-4 w-4 mr-2" />
              Reset
            </Button>
            <Button onClick={handleSaveAll} disabled={isLoading}>
              {isLoading ? (
                <Loader2 className="h-4 w-4 mr-2 animate-spin" />
              ) : (
                <Save className="h-4 w-4 mr-2" />
              )}
              Simpan Semua
            </Button>
          </div>
        </div>
      </div>

      <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <Card>
          <CardContent className="p-4">
            <div className="flex items-center gap-3">
              <Calendar className="h-8 w-8 text-primary/30" />
              <div>
                <p className="text-2xl font-bold">{scheduleItems.length}</p>
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
                <p className="text-2xl font-bold">
                  {new Set(scheduleItems.map((i) => i.teacherId)).size}
                </p>
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
                <p className="text-2xl font-bold">{timeSlots.filter((s) => !s.isBreak).length}</p>
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
                <p className="text-2xl font-bold">
                  {new Set(scheduleItems.map((i) => i.subjectId)).size}
                </p>
                <p className="text-xs text-muted-foreground">Mata Pelajaran</p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

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
                        {slot.startTime} - {slot.endTime}
                      </div>
                    </td>
                    {dayNames.slice(0, 5).map((_, dayIndex) => {
                      if (slot.isBreak) {
                        return (
                          <td
                            key={dayIndex}
                            className="border bg-muted/20 p-2 text-center text-xs text-muted-foreground"
                          >
                            Istirahat
                          </td>
                        );
                      }

                      const item = getScheduleItem(dayIndex, slot.id);
                      const subject = item ? mockSubjects.find((s) => s.id === item.subjectId) : null;
                      const teacher = item ? mockTeachers.find((t) => t.id === item.teacherId) : null;

                      return (
                        <td
                          key={dayIndex}
                          className="border p-1 cursor-pointer hover:bg-muted/20 transition-colors"
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
                                {teacher?.name.split(',')[0]}
                              </div>
                              <div className="text-muted-foreground/70 text-[10px]">{item.room}</div>
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

      <Card className="mt-6">
        <CardHeader className="pb-2">
          <CardTitle className="text-sm">Keterangan Warna</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="flex flex-wrap gap-3">
            {mockSubjects.map((subject) => (
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

      <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>
              {selectedCell && getScheduleItem(selectedCell.day, selectedCell.slotId)
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
                  {mockSubjects.map((subject) => (
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
                  {mockTeachers.map((teacher) => (
                    <SelectItem key={teacher.id} value={teacher.id}>
                      {teacher.name}
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
            {selectedCell && getScheduleItem(selectedCell.day, selectedCell.slotId) && (
              <Button variant="destructive" onClick={handleDeleteItem}>
                <Trash2 className="h-4 w-4 mr-2" />
                Hapus
              </Button>
            )}
            <Button variant="outline" onClick={() => setDialogOpen(false)}>
              Batal
            </Button>
            <Button onClick={handleSaveItem}>
              <Save className="h-4 w-4 mr-2" />
              Simpan
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
