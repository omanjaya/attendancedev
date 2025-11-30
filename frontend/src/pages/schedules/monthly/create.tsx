import { useState } from 'react';
import { Link } from '@tanstack/react-router';
import {
  ArrowLeft,
  Calendar,
  Users,
  Save,
  Loader2,
  Plus,
  X,
  Copy,
  Check,
} from 'lucide-react';

import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
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
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from '@/components/ui/tooltip';
import { useNotificationStore } from '@/stores';

// Mock data
const mockEmployees = [
  { id: '1', name: 'Ahmad Rizki', department: 'IT', position: 'Developer' },
  { id: '2', name: 'Siti Nurhaliza', department: 'HR', position: 'Manager' },
  { id: '3', name: 'Budi Santoso', department: 'Finance', position: 'Accountant' },
  { id: '4', name: 'Dewi Anggraini', department: 'Marketing', position: 'Specialist' },
  { id: '5', name: 'Eko Prasetyo', department: 'Operations', position: 'Manager' },
];

const mockScheduleTypes = [
  { id: '1', name: 'Pagi', startTime: '07:00', endTime: '15:00', color: 'bg-primary' },
  { id: '2', name: 'Siang', startTime: '13:00', endTime: '21:00', color: 'bg-warning' },
  { id: '3', name: 'Malam', startTime: '21:00', endTime: '07:00', color: 'bg-chart-5' },
  { id: 'off', name: 'Libur', startTime: '-', endTime: '-', color: 'bg-muted-foreground' },
];

const months = [
  'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

const dayNames = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];

interface ScheduleEntry {
  date: string;
  scheduleTypeId: string;
}

interface EmployeeSchedule {
  employeeId: string;
  entries: ScheduleEntry[];
}

export default function MonthlyScheduleCreatePage() {
  const { success } = useNotificationStore();
  const currentDate = new Date();
  const [selectedMonth, setSelectedMonth] = useState(currentDate.getMonth() + 1);
  const [selectedYear, setSelectedYear] = useState(currentDate.getFullYear());
  const [scheduleName, setScheduleName] = useState('');
  const [selectedEmployees, setSelectedEmployees] = useState<string[]>([]);
  const [employeeSchedules, setEmployeeSchedules] = useState<EmployeeSchedule[]>([]);
  const [isLoading, setIsLoading] = useState(false);
  const [showEmployeeDialog, setShowEmployeeDialog] = useState(false);
  const [selectedCell, setSelectedCell] = useState<{ employeeId: string; date: string } | null>(null);
  const [showScheduleDialog, setShowScheduleDialog] = useState(false);

  // Generate year options
  const yearOptions = Array.from({ length: 5 }, (_, i) => currentDate.getFullYear() - 1 + i);

  // Get days in month
  const getDaysInMonth = (month: number, year: number) => {
    return new Date(year, month, 0).getDate();
  };

  // Generate calendar days
  const daysInMonth = getDaysInMonth(selectedMonth, selectedYear);
  const calendarDays = Array.from({ length: daysInMonth }, (_, i) => {
    const day = i + 1;
    const date = `${selectedYear}-${String(selectedMonth).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
    const dayOfWeek = new Date(selectedYear, selectedMonth - 1, day).getDay();
    return { day, date, dayOfWeek, isWeekend: dayOfWeek === 0 || dayOfWeek === 6 };
  });

  // Get initials
  const getInitials = (name: string) => {
    return name.split(' ').map((n) => n[0]).join('').toUpperCase().slice(0, 2);
  };

  // Get schedule for cell
  const getScheduleForCell = (employeeId: string, date: string) => {
    const empSchedule = employeeSchedules.find((s) => s.employeeId === employeeId);
    if (!empSchedule) return null;
    const entry = empSchedule.entries.find((e) => e.date === date);
    if (!entry) return null;
    return mockScheduleTypes.find((s) => s.id === entry.scheduleTypeId);
  };

  // Handle add employees
  const handleAddEmployees = () => {
    const newSchedules = selectedEmployees
      .filter((empId) => !employeeSchedules.some((s) => s.employeeId === empId))
      .map((empId) => ({ employeeId: empId, entries: [] }));
    setEmployeeSchedules([...employeeSchedules, ...newSchedules]);
    setShowEmployeeDialog(false);
  };

  // Handle remove employee
  const handleRemoveEmployee = (employeeId: string) => {
    setEmployeeSchedules(employeeSchedules.filter((s) => s.employeeId !== employeeId));
    setSelectedEmployees(selectedEmployees.filter((id) => id !== employeeId));
  };

  // Handle cell click
  const handleCellClick = (employeeId: string, date: string) => {
    setSelectedCell({ employeeId, date });
    setShowScheduleDialog(true);
  };

  // Handle set schedule
  const handleSetSchedule = (scheduleTypeId: string) => {
    if (!selectedCell) return;

    setEmployeeSchedules((prev) => {
      const existing = prev.find((s) => s.employeeId === selectedCell.employeeId);
      if (existing) {
        const entryIndex = existing.entries.findIndex((e) => e.date === selectedCell.date);
        if (entryIndex >= 0) {
          existing.entries[entryIndex].scheduleTypeId = scheduleTypeId;
        } else {
          existing.entries.push({ date: selectedCell.date, scheduleTypeId });
        }
        return [...prev];
      }
      return [
        ...prev,
        { employeeId: selectedCell.employeeId, entries: [{ date: selectedCell.date, scheduleTypeId }] },
      ];
    });
    setShowScheduleDialog(false);
    setSelectedCell(null);
  };

  // Handle copy week
  const handleCopyWeek = (employeeId: string, weekStart: number) => {
    const empSchedule = employeeSchedules.find((s) => s.employeeId === employeeId);
    if (!empSchedule) return;

    // Get entries for the week (7 days starting from weekStart)
    const weekEntries = empSchedule.entries.filter((entry) => {
      const day = parseInt(entry.date.split('-')[2]);
      return day >= weekStart && day < weekStart + 7;
    });

    if (weekEntries.length === 0) return;

    // Copy to next week
    const nextWeekStart = weekStart + 7;
    if (nextWeekStart > daysInMonth) return;

    setEmployeeSchedules((prev) => {
      const existing = prev.find((s) => s.employeeId === employeeId);
      if (existing) {
        weekEntries.forEach((entry) => {
          const originalDay = parseInt(entry.date.split('-')[2]);
          const newDay = originalDay + 7;
          if (newDay <= daysInMonth) {
            const newDate = `${selectedYear}-${String(selectedMonth).padStart(2, '0')}-${String(newDay).padStart(2, '0')}`;
            const entryIndex = existing.entries.findIndex((e) => e.date === newDate);
            if (entryIndex >= 0) {
              existing.entries[entryIndex].scheduleTypeId = entry.scheduleTypeId;
            } else {
              existing.entries.push({ date: newDate, scheduleTypeId: entry.scheduleTypeId });
            }
          }
        });
        return [...prev];
      }
      return prev;
    });

    success('Berhasil', 'Jadwal minggu berhasil disalin');
  };

  // Handle save
  const handleSave = async (publish = false) => {
    setIsLoading(true);
    await new Promise((resolve) => setTimeout(resolve, 1500));
    setIsLoading(false);
    success('Berhasil', publish ? 'Jadwal berhasil dipublikasi' : 'Jadwal berhasil disimpan sebagai draft');
  };

  // Get employee by ID
  const getEmployee = (id: string) => mockEmployees.find((e) => e.id === id);

  return (
    <div className="p-6">
      {/* Header */}
      <div className="mb-6">
        <Link
          to="/schedules/monthly"
          className="inline-flex items-center text-sm text-muted-foreground hover:text-foreground mb-4"
        >
          <ArrowLeft className="h-4 w-4 mr-2" />
          Kembali ke jadwal bulanan
        </Link>
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-2xl font-bold text-foreground">Buat Jadwal Bulanan</h1>
            <p className="text-sm text-muted-foreground">
              Atur jadwal kerja karyawan untuk bulan tertentu
            </p>
          </div>
          <div className="flex items-center gap-2">
            <Button variant="outline" onClick={() => handleSave(false)} disabled={isLoading}>
              {isLoading ? <Loader2 className="h-4 w-4 mr-2 animate-spin" /> : <Save className="h-4 w-4 mr-2" />}
              Simpan Draft
            </Button>
            <Button onClick={() => handleSave(true)} disabled={isLoading}>
              {isLoading ? <Loader2 className="h-4 w-4 mr-2 animate-spin" /> : <Check className="h-4 w-4 mr-2" />}
              Publikasi
            </Button>
          </div>
        </div>
      </div>

      {/* Settings */}
      <Card className="mb-6">
        <CardHeader>
          <CardTitle>Pengaturan Jadwal</CardTitle>
          <CardDescription>Atur periode dan nama jadwal</CardDescription>
        </CardHeader>
        <CardContent>
          <div className="grid gap-4 md:grid-cols-3">
            <div className="space-y-2">
              <Label htmlFor="name">Nama Jadwal</Label>
              <Input
                id="name"
                placeholder="Contoh: Jadwal Januari 2025"
                value={scheduleName}
                onChange={(e) => setScheduleName(e.target.value)}
              />
            </div>
            <div className="space-y-2">
              <Label>Bulan</Label>
              <Select
                value={selectedMonth.toString()}
                onValueChange={(v) => setSelectedMonth(parseInt(v))}
              >
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {months.map((month, index) => (
                    <SelectItem key={index} value={(index + 1).toString()}>
                      {month}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-2">
              <Label>Tahun</Label>
              <Select
                value={selectedYear.toString()}
                onValueChange={(v) => setSelectedYear(parseInt(v))}
              >
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {yearOptions.map((year) => (
                    <SelectItem key={year} value={year.toString()}>
                      {year}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Legend */}
      <Card className="mb-6">
        <CardContent className="p-4">
          <div className="flex items-center gap-6">
            <span className="text-sm font-medium">Legenda:</span>
            {mockScheduleTypes.map((type) => (
              <div key={type.id} className="flex items-center gap-2">
                <div className={`w-4 h-4 rounded ${type.color}`} />
                <span className="text-sm">{type.name}</span>
                {type.startTime !== '-' && (
                  <span className="text-xs text-muted-foreground">
                    ({type.startTime}-{type.endTime})
                  </span>
                )}
              </div>
            ))}
          </div>
        </CardContent>
      </Card>

      {/* Schedule Grid */}
      <Card>
        <CardHeader>
          <div className="flex items-center justify-between">
            <div>
              <CardTitle className="flex items-center gap-2">
                <Calendar className="h-5 w-5 text-primary" />
                {months[selectedMonth - 1]} {selectedYear}
              </CardTitle>
              <CardDescription>
                {employeeSchedules.length} karyawan terdaftar
              </CardDescription>
            </div>
            <Button onClick={() => setShowEmployeeDialog(true)}>
              <Plus className="h-4 w-4 mr-2" />
              Tambah Karyawan
            </Button>
          </div>
        </CardHeader>
        <CardContent>
          {employeeSchedules.length === 0 ? (
            <div className="flex flex-col items-center justify-center py-12 text-muted-foreground">
              <Users className="h-12 w-12 mb-4" />
              <p className="text-lg font-medium">Belum ada karyawan</p>
              <p className="text-sm">Tambahkan karyawan untuk mulai membuat jadwal</p>
              <Button className="mt-4" onClick={() => setShowEmployeeDialog(true)}>
                <Plus className="h-4 w-4 mr-2" />
                Tambah Karyawan
              </Button>
            </div>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full border-collapse">
                <thead>
                  <tr>
                    <th className="sticky left-0 bg-background z-10 p-2 text-left min-w-[180px] border-b">
                      Karyawan
                    </th>
                    {calendarDays.map(({ day, dayOfWeek, isWeekend }) => (
                      <th
                        key={day}
                        className={`p-1 text-center min-w-[40px] border-b ${
                          isWeekend ? 'bg-muted/50' : ''
                        }`}
                      >
                        <div className="text-xs text-muted-foreground">{dayNames[dayOfWeek]}</div>
                        <div className="font-medium">{day}</div>
                      </th>
                    ))}
                    <th className="p-2 min-w-[80px] border-b">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  {employeeSchedules.map(({ employeeId }) => {
                    const employee = getEmployee(employeeId);
                    if (!employee) return null;
                    return (
                      <tr key={employeeId} className="border-b hover:bg-muted/30">
                        <td className="sticky left-0 bg-background z-10 p-2">
                          <div className="flex items-center gap-2">
                            <Avatar className="h-8 w-8">
                              <AvatarImage src="" />
                              <AvatarFallback className="text-xs">
                                {getInitials(employee.name)}
                              </AvatarFallback>
                            </Avatar>
                            <div>
                              <div className="font-medium text-sm">{employee.name}</div>
                              <div className="text-xs text-muted-foreground">{employee.department}</div>
                            </div>
                          </div>
                        </td>
                        {calendarDays.map(({ date, isWeekend }) => {
                          const schedule = getScheduleForCell(employeeId, date);
                          return (
                            <TooltipProvider key={date}>
                              <Tooltip>
                                <TooltipTrigger asChild>
                                  <td
                                    className={`p-1 text-center cursor-pointer hover:bg-muted/50 ${
                                      isWeekend ? 'bg-muted/30' : ''
                                    }`}
                                    onClick={() => handleCellClick(employeeId, date)}
                                  >
                                    {schedule ? (
                                      <div
                                        className={`w-full h-8 rounded flex items-center justify-center text-white text-xs font-medium ${schedule.color}`}
                                      >
                                        {schedule.name.charAt(0)}
                                      </div>
                                    ) : (
                                      <div className="w-full h-8 rounded border-2 border-dashed border-muted-foreground/30 flex items-center justify-center text-muted-foreground/50">
                                        +
                                      </div>
                                    )}
                                  </td>
                                </TooltipTrigger>
                                <TooltipContent>
                                  {schedule ? (
                                    <p>
                                      {schedule.name} ({schedule.startTime} - {schedule.endTime})
                                    </p>
                                  ) : (
                                    <p>Klik untuk menambah jadwal</p>
                                  )}
                                </TooltipContent>
                              </Tooltip>
                            </TooltipProvider>
                          );
                        })}
                        <td className="p-2">
                          <div className="flex items-center gap-1">
                            <Button
                              variant="ghost"
                              size="icon"
                              onClick={() => handleCopyWeek(employeeId, 1)}
                              title="Salin minggu pertama"
                            >
                              <Copy className="h-4 w-4" />
                            </Button>
                            <Button
                              variant="ghost"
                              size="icon"
                              onClick={() => handleRemoveEmployee(employeeId)}
                              className="text-destructive hover:text-destructive"
                            >
                              <X className="h-4 w-4" />
                            </Button>
                          </div>
                        </td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            </div>
          )}
        </CardContent>
      </Card>

      {/* Add Employee Dialog */}
      <Dialog open={showEmployeeDialog} onOpenChange={setShowEmployeeDialog}>
        <DialogContent className="max-w-md">
          <DialogHeader>
            <DialogTitle>Tambah Karyawan</DialogTitle>
            <DialogDescription>Pilih karyawan untuk ditambahkan ke jadwal</DialogDescription>
          </DialogHeader>
          <div className="space-y-4 max-h-[400px] overflow-y-auto">
            {mockEmployees.map((employee) => {
              const isAdded = employeeSchedules.some((s) => s.employeeId === employee.id);
              return (
                <div
                  key={employee.id}
                  className={`flex items-center gap-3 p-3 rounded-lg border ${
                    isAdded ? 'bg-muted/50' : 'hover:bg-muted/30'
                  }`}
                >
                  <Checkbox
                    checked={selectedEmployees.includes(employee.id) || isAdded}
                    disabled={isAdded}
                    onCheckedChange={(checked) => {
                      if (checked) {
                        setSelectedEmployees([...selectedEmployees, employee.id]);
                      } else {
                        setSelectedEmployees(selectedEmployees.filter((id) => id !== employee.id));
                      }
                    }}
                  />
                  <Avatar className="h-10 w-10">
                    <AvatarImage src="" />
                    <AvatarFallback>{getInitials(employee.name)}</AvatarFallback>
                  </Avatar>
                  <div className="flex-1">
                    <div className="font-medium">{employee.name}</div>
                    <div className="text-sm text-muted-foreground">
                      {employee.department} - {employee.position}
                    </div>
                  </div>
                  {isAdded && <Badge variant="outline">Ditambahkan</Badge>}
                </div>
              );
            })}
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setShowEmployeeDialog(false)}>
              Batal
            </Button>
            <Button
              onClick={handleAddEmployees}
              disabled={selectedEmployees.filter((id) => !employeeSchedules.some((s) => s.employeeId === id)).length === 0}
            >
              Tambah ({selectedEmployees.filter((id) => !employeeSchedules.some((s) => s.employeeId === id)).length})
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Schedule Type Dialog */}
      <Dialog open={showScheduleDialog} onOpenChange={setShowScheduleDialog}>
        <DialogContent className="max-w-sm">
          <DialogHeader>
            <DialogTitle>Pilih Jadwal</DialogTitle>
            <DialogDescription>
              Pilih tipe jadwal untuk tanggal ini
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-2">
            {mockScheduleTypes.map((type) => (
              <div
                key={type.id}
                className="flex items-center gap-3 p-3 rounded-lg border cursor-pointer hover:bg-muted/50"
                onClick={() => handleSetSchedule(type.id)}
              >
                <div className={`w-8 h-8 rounded flex items-center justify-center text-white ${type.color}`}>
                  {type.name.charAt(0)}
                </div>
                <div>
                  <div className="font-medium">{type.name}</div>
                  {type.startTime !== '-' && (
                    <div className="text-sm text-muted-foreground">
                      {type.startTime} - {type.endTime}
                    </div>
                  )}
                </div>
              </div>
            ))}
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setShowScheduleDialog(false)}>
              Batal
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
