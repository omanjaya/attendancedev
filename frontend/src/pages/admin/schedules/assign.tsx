import { useState } from 'react';
import { Link } from '@tanstack/react-router';
import {
  ArrowLeft,
  Users,
  Calendar,
  UserPlus,
  Search,
  Check,
  X,
  Loader2,
  Filter,
} from 'lucide-react';

import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { LoadingState } from '@/components/states';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
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
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { useNotificationStore } from '@/stores';

// Mock data
const mockSchedules = [
  { id: '1', name: 'Jadwal Reguler Pagi', type: 'regular', shift: 'pagi', startTime: '07:00', endTime: '15:00', employeeCount: 25 },
  { id: '2', name: 'Jadwal Reguler Siang', type: 'regular', shift: 'siang', startTime: '13:00', endTime: '21:00', employeeCount: 18 },
  { id: '3', name: 'Jadwal Fleksibel', type: 'flexible', shift: 'fleksibel', startTime: '08:00', endTime: '17:00', employeeCount: 12 },
  { id: '4', name: 'Jadwal Shift Malam', type: 'shift', shift: 'malam', startTime: '21:00', endTime: '07:00', employeeCount: 8 },
];

const mockEmployees = [
  { id: '1', name: 'Ahmad Rizki', department: 'IT', position: 'Developer', scheduleId: '1' },
  { id: '2', name: 'Siti Nurhaliza', department: 'HR', position: 'Manager', scheduleId: '1' },
  { id: '3', name: 'Budi Santoso', department: 'Finance', position: 'Accountant', scheduleId: '2' },
  { id: '4', name: 'Dewi Anggraini', department: 'Marketing', position: 'Specialist', scheduleId: null },
  { id: '5', name: 'Eko Prasetyo', department: 'Operations', position: 'Manager', scheduleId: '3' },
  { id: '6', name: 'Fitri Handayani', department: 'IT', position: 'Designer', scheduleId: null },
  { id: '7', name: 'Gunawan Wibowo', department: 'Sales', position: 'Executive', scheduleId: null },
  { id: '8', name: 'Hana Pertiwi', department: 'HR', position: 'Staff', scheduleId: '1' },
];

const departments = ['Semua', 'IT', 'HR', 'Finance', 'Marketing', 'Operations', 'Sales'];

export default function ScheduleAssignPage() {
  const { success } = useNotificationStore();
  const [selectedSchedule, setSelectedSchedule] = useState<string>('');
  const [searchQuery, setSearchQuery] = useState('');
  const [filterDepartment, setFilterDepartment] = useState('Semua');
  const [selectedEmployees, setSelectedEmployees] = useState<string[]>([]);
  const [isLoading, setIsLoading] = useState(false);
  const [employees, setEmployees] = useState(mockEmployees);

  // Filter employees
  const filteredEmployees = employees.filter((emp) => {
    const matchesSearch =
      emp.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
      emp.department.toLowerCase().includes(searchQuery.toLowerCase());
    const matchesDept = filterDepartment === 'Semua' || emp.department === filterDepartment;
    return matchesSearch && matchesDept;
  });

  // Get unassigned employees
  const unassignedEmployees = filteredEmployees.filter((emp) => !emp.scheduleId);
  const assignedEmployees = filteredEmployees.filter((emp) => emp.scheduleId);

  // Handle select all
  const handleSelectAll = (checked: boolean) => {
    if (checked) {
      setSelectedEmployees(unassignedEmployees.map((e) => e.id));
    } else {
      setSelectedEmployees([]);
    }
  };

  // Handle assign
  const handleAssign = async () => {
    if (!selectedSchedule || selectedEmployees.length === 0) return;

    setIsLoading(true);
    await new Promise((resolve) => setTimeout(resolve, 1000));

    setEmployees(
      employees.map((emp) =>
        selectedEmployees.includes(emp.id) ? { ...emp, scheduleId: selectedSchedule } : emp
      )
    );
    setSelectedEmployees([]);
    setIsLoading(false);
    success('Berhasil', `${selectedEmployees.length} karyawan berhasil ditugaskan`);
  };

  // Handle remove assignment
  const handleRemoveAssignment = async (employeeId: string) => {
    setEmployees(
      employees.map((emp) => (emp.id === employeeId ? { ...emp, scheduleId: null } : emp))
    );
    success('Berhasil', 'Penugasan berhasil dihapus');
  };

  // Get schedule name
  const getScheduleName = (scheduleId: string | null) => {
    if (!scheduleId) return '-';
    return mockSchedules.find((s) => s.id === scheduleId)?.name || '-';
  };

  // Get initials
  const getInitials = (name: string) => {
    return name
      .split(' ')
      .map((n) => n[0])
      .join('')
      .toUpperCase()
      .slice(0, 2);
  };

  return (
    <div className="p-4 sm:p-6">
      {/* Header */}
      <div className="mb-6">
        <Link
          to="/admin/schedules"
          className="inline-flex items-center text-sm text-muted-foreground hover:text-foreground mb-4"
        >
          <ArrowLeft className="h-4 w-4 mr-2" />
          Kembali ke jadwal
        </Link>
        <h1 className="text-2xl font-bold text-foreground">Penugasan Karyawan</h1>
        <p className="text-sm text-muted-foreground">
          Tugaskan karyawan ke jadwal kerja
        </p>
      </div>

      {/* Stats */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <Card>
          <CardContent className="p-4">
            <div className="flex items-center gap-3">
              <Users className="h-8 w-8 text-primary/30" />
              <div>
                <p className="text-2xl font-bold">{employees.length}</p>
                <p className="text-xs text-muted-foreground">Total Karyawan</p>
              </div>
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="p-4">
            <div className="flex items-center gap-3">
              <Check className="h-8 w-8 text-success/30" />
              <div>
                <p className="text-2xl font-bold">{assignedEmployees.length}</p>
                <p className="text-xs text-muted-foreground">Sudah Ditugaskan</p>
              </div>
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="p-4">
            <div className="flex items-center gap-3">
              <UserPlus className="h-8 w-8 text-warning/30" />
              <div>
                <p className="text-2xl font-bold">{unassignedEmployees.length}</p>
                <p className="text-xs text-muted-foreground">Belum Ditugaskan</p>
              </div>
            </div>
          </CardContent>
        </Card>
        <Card>
          <CardContent className="p-4">
            <div className="flex items-center gap-3">
              <Calendar className="h-8 w-8 text-primary/30" />
              <div>
                <p className="text-2xl font-bold">{mockSchedules.length}</p>
                <p className="text-xs text-muted-foreground">Jadwal Tersedia</p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <div className="grid gap-6 lg:grid-cols-3">
        {/* Schedule Selection */}
        <Card className="lg:col-span-1">
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <Calendar className="h-5 w-5 text-primary" />
              Pilih Jadwal
            </CardTitle>
            <CardDescription>Pilih jadwal untuk penugasan</CardDescription>
          </CardHeader>
          <CardContent className="space-y-3">
            {mockSchedules.map((schedule) => (
              <div
                key={schedule.id}
                className={`p-4 rounded-lg border cursor-pointer transition-colors ${
                  selectedSchedule === schedule.id
                    ? 'border-primary bg-primary/5'
                    : 'border-border hover:bg-muted/50'
                }`}
                onClick={() => setSelectedSchedule(schedule.id)}
              >
                <div className="flex items-center justify-between mb-2">
                  <h4 className="font-medium text-sm">{schedule.name}</h4>
                  <Badge variant="secondary">{schedule.shift}</Badge>
                </div>
                <div className="flex items-center justify-between text-xs text-muted-foreground">
                  <span>
                    {schedule.startTime} - {schedule.endTime}
                  </span>
                  <span>{schedule.employeeCount} karyawan</span>
                </div>
              </div>
            ))}
          </CardContent>
        </Card>

        {/* Employee Assignment */}
        <Card className="lg:col-span-2">
          <CardHeader>
            <div className="flex items-center justify-between">
              <div>
                <CardTitle className="flex items-center gap-2">
                  <Users className="h-5 w-5 text-primary" />
                  Karyawan Belum Ditugaskan
                </CardTitle>
                <CardDescription>
                  Pilih karyawan untuk ditugaskan ke jadwal
                </CardDescription>
              </div>
              <Button
                onClick={handleAssign}
                disabled={!selectedSchedule || selectedEmployees.length === 0 || isLoading}
              >
                {isLoading ? (
                  <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                ) : (
                  <UserPlus className="h-4 w-4 mr-2" />
                )}
                Tugaskan ({selectedEmployees.length})
              </Button>
            </div>
          </CardHeader>
          <CardContent>
            {/* Filters */}
            <div className="flex gap-4 mb-4">
              <div className="relative flex-1">
                <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                <Input
                  placeholder="Cari karyawan..."
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  className="pl-10"
                />
              </div>
              <Select value={filterDepartment} onValueChange={setFilterDepartment}>
                <SelectTrigger className="w-[180px]">
                  <Filter className="h-4 w-4 mr-2" />
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {departments.map((dept) => (
                    <SelectItem key={dept} value={dept}>
                      {dept}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            {/* Employee Table */}
            <div className="border rounded-lg">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead className="w-12">
                      <Checkbox
                        checked={
                          selectedEmployees.length === unassignedEmployees.length &&
                          unassignedEmployees.length > 0
                        }
                        onCheckedChange={handleSelectAll}
                      />
                    </TableHead>
                    <TableHead>Karyawan</TableHead>
                    <TableHead>Departemen</TableHead>
                    <TableHead>Posisi</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {unassignedEmployees.map((employee) => (
                    <TableRow key={employee.id}>
                      <TableCell>
                        <Checkbox
                          checked={selectedEmployees.includes(employee.id)}
                          onCheckedChange={(checked) => {
                            if (checked) {
                              setSelectedEmployees([...selectedEmployees, employee.id]);
                            } else {
                              setSelectedEmployees(
                                selectedEmployees.filter((id) => id !== employee.id)
                              );
                            }
                          }}
                        />
                      </TableCell>
                      <TableCell>
                        <div className="flex items-center gap-3">
                          <Avatar className="h-8 w-8">
                            <AvatarImage src="" />
                            <AvatarFallback className="text-xs">
                              {getInitials(employee.name)}
                            </AvatarFallback>
                          </Avatar>
                          <span className="font-medium">{employee.name}</span>
                        </div>
                      </TableCell>
                      <TableCell>{employee.department}</TableCell>
                      <TableCell>{employee.position}</TableCell>
                    </TableRow>
                  ))}
                  {unassignedEmployees.length === 0 && (
                    <TableRow>
                      <TableCell colSpan={4} className="text-center py-8 text-muted-foreground">
                        <Check className="h-8 w-8 mx-auto mb-2" />
                        Semua karyawan sudah ditugaskan
                      </TableCell>
                    </TableRow>
                  )}
                </TableBody>
              </Table>
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Assigned Employees */}
      <Card className="mt-6">
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Check className="h-5 w-5 text-success" />
            Karyawan Sudah Ditugaskan
          </CardTitle>
          <CardDescription>Daftar karyawan yang sudah memiliki jadwal</CardDescription>
        </CardHeader>
        <CardContent>
          <div className="border rounded-lg">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Karyawan</TableHead>
                  <TableHead>Departemen</TableHead>
                  <TableHead>Posisi</TableHead>
                  <TableHead>Jadwal</TableHead>
                  <TableHead className="w-20">Aksi</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {assignedEmployees.map((employee) => (
                  <TableRow key={employee.id}>
                    <TableCell>
                      <div className="flex items-center gap-3">
                        <Avatar className="h-8 w-8">
                          <AvatarImage src="" />
                          <AvatarFallback className="text-xs">
                            {getInitials(employee.name)}
                          </AvatarFallback>
                        </Avatar>
                        <span className="font-medium">{employee.name}</span>
                      </div>
                    </TableCell>
                    <TableCell>{employee.department}</TableCell>
                    <TableCell>{employee.position}</TableCell>
                    <TableCell>
                      <Badge variant="outline">{getScheduleName(employee.scheduleId)}</Badge>
                    </TableCell>
                    <TableCell>
                      <Button
                        variant="ghost"
                        size="icon"
                        onClick={() => handleRemoveAssignment(employee.id)}
                        className="text-destructive hover:text-destructive"
                      >
                        <X className="h-4 w-4" />
                      </Button>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
