import { useState } from 'react';
import { Link } from '@tanstack/react-router';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import {
  ArrowLeft,
  Calendar,
  Plus,
  Search,
  Loader2,
  UserPlus,
  Edit,
  Trash2,
  MoreVertical,
  Users,
  CalendarDays,
} from 'lucide-react';

import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
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
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
  DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Checkbox } from '@/components/ui/checkbox';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { useNotificationStore } from '@/stores';
import {
  getMonthlyAttendanceSchedules,
  deleteMonthlyAttendanceSchedule,
  assignEmployeesToSchedule,
  getScheduleEmployees,
  type MonthlyAttendanceSchedule,
} from '@/lib/api/schedules';
import { getEmployees } from '@/lib/api/employees';

const months = [
  'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

export function MonthlyScheduleList({ showHeader = true }: { showHeader?: boolean }) {
  const queryClient = useQueryClient();
  const { success, error: showError } = useNotificationStore();
  const [searchQuery, setSearchQuery] = useState('');
  const [filterYear, setFilterYear] = useState(new Date().getFullYear().toString());
  const [deleteDialog, setDeleteDialog] = useState<{ open: boolean; schedule: MonthlyAttendanceSchedule | null }>({
    open: false,
    schedule: null,
  });
  const [assignDialog, setAssignDialog] = useState<{ open: boolean; schedule: MonthlyAttendanceSchedule | null }>({
    open: false,
    schedule: null,
  });
  const [viewDialog, setViewDialog] = useState<{ open: boolean; schedule: MonthlyAttendanceSchedule | null }>({
    open: false,
    schedule: null,
  });
  const [selectedEmployees, setSelectedEmployees] = useState<string[]>([]);

  // Generate year options
  const currentYear = new Date().getFullYear();
  const yearOptions = Array.from({ length: 5 }, (_, i) => currentYear - 2 + i);

  // Fetch monthly schedules
  const { data: schedulesData, isLoading } = useQuery({
    queryKey: ['monthly-schedules', filterYear],
    queryFn: () => getMonthlyAttendanceSchedules({ year: parseInt(filterYear), per_page: 50 }),
  });

  const schedules = schedulesData?.data || [];

  // Fetch employees for assignment
  const { data: employeesData } = useQuery({
    queryKey: ['employees-for-assignment'],
    queryFn: () => getEmployees({ per_page: 100 }),
    enabled: assignDialog.open,
  });

  const employees = employeesData?.data || [];

  // Fetch assigned employees for view dialog
  const { data: assignedEmployeesData, isLoading: isLoadingAssignedEmployees } = useQuery({
    queryKey: ['schedule-employees', viewDialog.schedule?.id],
    queryFn: () => getScheduleEmployees(viewDialog.schedule!.id),
    enabled: viewDialog.open && !!viewDialog.schedule,
  });

  const assignedEmployees = assignedEmployeesData || [];

  // Delete mutation
  const deleteMutation = useMutation({
    mutationFn: (id: string) => deleteMonthlyAttendanceSchedule(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['monthly-schedules'] });
      success('Berhasil', 'Jadwal bulanan berhasil dihapus');
      setDeleteDialog({ open: false, schedule: null });
    },
    onError: (err: any) => {
      const message = err?.response?.data?.message || 'Gagal menghapus jadwal';
      showError('Error', message);
    },
  });

  // Assign mutation
  const assignMutation = useMutation({
    mutationFn: ({ scheduleId, employeeIds }: { scheduleId: string; employeeIds: string[] }) =>
      assignEmployeesToSchedule(scheduleId, { employee_ids: employeeIds }),
    onSuccess: (data) => {
      queryClient.invalidateQueries({ queryKey: ['monthly-schedules'] });
      const replacedMsg = data.replaced_count > 0 ? ` (${data.replaced_count} jadwal di-replace)` : '';
      success(
        'Berhasil',
        `✓ Jadwal berhasil di-assign ke ${data.assigned_count} pegawai${replacedMsg}`
      );
      setAssignDialog({ open: false, schedule: null });
      setSelectedEmployees([]);
    },
    onError: (err: any) => {
      const message = err?.response?.data?.message || 'Gagal assign jadwal';
      showError('Error', message);
    },
  });

  // Filter schedules
  const filteredSchedules = schedules.filter((schedule) => {
    const matchesSearch = schedule.name.toLowerCase().includes(searchQuery.toLowerCase());
    return matchesSearch;
  });

  // Handle delete
  const handleDelete = () => {
    if (deleteDialog.schedule) {
      deleteMutation.mutate(deleteDialog.schedule.id);
    }
  };

  // Handle assign
  const handleAssign = () => {
    console.log('✅ handleAssign called');
    console.log('  Schedule:', assignDialog.schedule);
    console.log('  Selected employees:', selectedEmployees);
    console.log('  Selected count:', selectedEmployees.length);

    if (assignDialog.schedule && selectedEmployees.length > 0) {
      console.log('  ✓ Calling API with:', {
        scheduleId: assignDialog.schedule.id,
        employeeIds: selectedEmployees,
      });
      assignMutation.mutate({
        scheduleId: assignDialog.schedule.id,
        employeeIds: selectedEmployees,
      });
    } else {
      console.log('  ✗ Assignment blocked!');
      if (!assignDialog.schedule) console.log('    Reason: No schedule selected');
      if (selectedEmployees.length === 0) console.log('    Reason: No employees selected');
    }
  };

  // Toggle employee selection
  const toggleEmployee = (employeeId: string) => {
    console.log('🔄 toggleEmployee called with ID:', employeeId);
    setSelectedEmployees(prev => {
      const isSelected = prev.includes(employeeId);
      const newSelection = isSelected
        ? prev.filter(id => id !== employeeId)
        : [...prev, employeeId];
      console.log('  Previous selection:', prev);
      console.log('  Is selected:', isSelected);
      console.log('  New selection:', newSelection);
      console.log('  Count:', newSelection.length);
      return newSelection;
    });
  };

  // Select all employees
  const selectAllEmployees = () => {
    console.log('🔘 selectAllEmployees called');
    console.log('  Current selection:', selectedEmployees);
    console.log('  Total employees:', employees.length);
    console.log('  Selected count:', selectedEmployees.length);

    if (selectedEmployees.length === employees.length) {
      console.log('  Action: Deselect all');
      setSelectedEmployees([]);
    } else {
      const allIds = employees.map(e => String(e.id));
      console.log('  Action: Select all');
      console.log('  All employee IDs:', allIds);
      setSelectedEmployees(allIds);
    }
  };

  // Get initials
  const getInitials = (name: string | undefined) => {
    if (!name) return '??';
    return name.split(' ').map((n) => n[0]).join('').toUpperCase().slice(0, 2);
  };

  return (
    <div className={showHeader ? "p-4 sm:p-6" : ""}>
      {/* Header */}
      {showHeader && (
        <div className="mb-6">
          <div className="flex items-center justify-between mb-4">
            <Link
              to="/admin/dashboard"
              className="inline-flex items-center text-sm text-muted-foreground hover:text-foreground transition-colors"
            >
              <ArrowLeft className="h-4 w-4 mr-2" />
              Kembali ke Dashboard
            </Link>
          </div>
          <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
              <h1 className="text-2xl sm:text-3xl font-bold text-foreground">Jadwal Bulanan</h1>
              <p className="text-sm text-muted-foreground mt-1">
                Kelola jadwal absensi bulanan untuk karyawan
              </p>
            </div>
            <Link to="/admin/schedules/monthly/create" className="self-start sm:self-auto">
              <Button size="lg" className="h-10">
                <Plus className="h-4 w-4 mr-2" />
                Buat Jadwal Baru
              </Button>
            </Link>
          </div>
        </div>
      )}

      {!showHeader && (
        <div className="flex justify-end mb-4">
          <Link to="/admin/schedules/monthly/create">
            <Button>
              <Plus className="h-4 w-4 mr-2" />
              Buat Jadwal Baru
            </Button>
          </Link>
        </div>
      )}

      {/* Filters */}
      <Card className="mb-6">
        <CardContent className="py-6">
          <div className="flex flex-col sm:flex-row items-center gap-4">
            <div className="relative flex-1 w-full">
              <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
              <Input
                placeholder="Cari jadwal berdasarkan nama..."
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                className="pl-10 h-10"
              />
            </div>
            <Select value={filterYear} onValueChange={setFilterYear}>
              <SelectTrigger className="w-full sm:w-[140px] h-10">
                <Calendar className="mr-2 h-4 w-4 text-muted-foreground" />
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                {yearOptions.map((year) => (
                  <SelectItem key={year} value={year.toString()}>
                    Tahun {year}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
        </CardContent>
      </Card>

      {/* Schedules Table */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <CalendarDays className="h-5 w-5 text-primary" />
            Daftar Jadwal Bulanan
          </CardTitle>
          <CardDescription>
            {filteredSchedules.length} jadwal ditemukan
          </CardDescription>
        </CardHeader>
        <CardContent>
          {isLoading ? (
            <div className="flex items-center justify-center py-12">
              <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
            </div>
          ) : filteredSchedules.length === 0 ? (
            <div className="flex flex-col items-center justify-center py-12 text-muted-foreground">
              <CalendarDays className="h-12 w-12 mb-4" />
              <p className="text-lg font-medium">Belum ada jadwal bulanan</p>
              <p className="text-sm">Buat jadwal bulanan pertama Anda</p>
            </div>
          ) : (
            <div className="overflow-x-auto">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Nama Jadwal</TableHead>
                    <TableHead>Periode</TableHead>
                    <TableHead>Lokasi</TableHead>
                    <TableHead>Hari Kerja</TableHead>
                    <TableHead>Jam Kerja</TableHead>
                    <TableHead>Pegawai</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead className="text-right">Aksi</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {filteredSchedules.map((schedule) => (
                    <TableRow key={schedule.id}>
                      <TableCell className="font-medium">{schedule.name}</TableCell>
                      <TableCell>
                        {months[schedule.month - 1]} {schedule.year}
                      </TableCell>
                      <TableCell>{schedule.location?.name || '-'}</TableCell>
                      <TableCell>{schedule.total_working_days} hari</TableCell>
                      <TableCell className="text-sm">
                        {schedule.default_start_time} - {schedule.default_end_time}
                      </TableCell>
                      <TableCell>
                        <div className="flex items-center gap-2">
                          <Users className="h-4 w-4 text-muted-foreground" />
                          <span>{schedule.assigned_employees_count || 0}</span>
                        </div>
                      </TableCell>
                      <TableCell>
                        {schedule.is_active ? (
                          <Badge className="bg-success/10 text-success border-success/20">Aktif</Badge>
                        ) : (
                          <Badge variant="secondary">Non-aktif</Badge>
                        )}
                      </TableCell>
                      <TableCell className="text-right">
                        <DropdownMenu>
                          <DropdownMenuTrigger asChild>
                            <Button variant="ghost" size="icon">
                              <MoreVertical className="h-4 w-4" />
                            </Button>
                          </DropdownMenuTrigger>
                          <DropdownMenuContent align="end">
                            <DropdownMenuItem asChild>
                              <Link to={`/admin/schedules/monthly/${schedule.id}/edit` as any}>
                                <Edit className="mr-2 h-4 w-4" />
                                Edit Jadwal
                              </Link>
                            </DropdownMenuItem>
                            <DropdownMenuItem
                              onClick={() => {
                                setAssignDialog({ open: true, schedule });
                                setSelectedEmployees([]);
                              }}
                            >
                              <UserPlus className="mr-2 h-4 w-4" />
                              Assign Pegawai
                            </DropdownMenuItem>
                            <DropdownMenuItem
                              onClick={() => setViewDialog({ open: true, schedule })}
                            >
                              <Users className="mr-2 h-4 w-4" />
                              Lihat Pegawai Assigned
                            </DropdownMenuItem>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem
                              className="text-destructive"
                              onClick={() => setDeleteDialog({ open: true, schedule })}
                            >
                              <Trash2 className="mr-2 h-4 w-4" />
                              Hapus
                            </DropdownMenuItem>
                          </DropdownMenuContent>
                        </DropdownMenu>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </div>
          )}
        </CardContent>
      </Card>

      {/* Assign Dialog */}
      <Dialog open={assignDialog.open} onOpenChange={(open) => setAssignDialog({ open, schedule: null })}>
        <DialogContent className="max-w-2xl max-h-[80vh]">
          <DialogHeader>
            <DialogTitle>Assign Pegawai ke Jadwal</DialogTitle>
            <DialogDescription>
              Pilih pegawai yang akan di-assign ke "{assignDialog.schedule?.name}".
              Jika pegawai sudah memiliki jadwal untuk bulan/tahun yang sama, jadwal lama akan diganti otomatis.
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-4">
            <div className="flex items-center justify-between">
              <p className="text-sm font-medium">
                {selectedEmployees.length} dari {employees.length} pegawai dipilih
              </p>
              <Button
                variant="outline"
                size="sm"
                onClick={selectAllEmployees}
              >
                {selectedEmployees.length === employees.length ? 'Batal Pilih Semua' : 'Pilih Semua'}
              </Button>
            </div>
            <div className="border rounded-lg max-h-[400px] overflow-y-auto">
              {employees.length === 0 ? (
                <div className="p-8 text-center text-muted-foreground">
                  <Loader2 className="h-8 w-8 animate-spin mx-auto mb-2" />
                  <p>Memuat data pegawai...</p>
                </div>
              ) : (
                <div className="divide-y">
                  {employees.map((employee) => (
                    <div
                      key={employee.id}
                      className="flex items-center gap-3 p-4 hover:bg-muted/50 cursor-pointer transition-colors"
                      onClick={(e) => {
                        e.stopPropagation();
                        toggleEmployee(String(employee.id));
                      }}
                    >
                      <Checkbox
                        checked={selectedEmployees.includes(String(employee.id))}
                        onCheckedChange={() => toggleEmployee(String(employee.id))}
                      />
                      <Avatar className="h-10 w-10">
                        <AvatarFallback>{getInitials(employee.name)}</AvatarFallback>
                      </Avatar>
                      <div className="flex-1">
                        <div className="font-medium">{employee.name}</div>
                        <div className="text-sm text-muted-foreground">
                          {employee.position || 'No Position'} • {employee.location_name || 'No Location'}
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </div>
          </div>
          <DialogFooter>
            <Button
              variant="outline"
              onClick={() => {
                setAssignDialog({ open: false, schedule: null });
                setSelectedEmployees([]);
              }}
            >
              Batal
            </Button>
            <Button
              onClick={handleAssign}
              disabled={selectedEmployees.length === 0 || assignMutation.isPending}
            >
              {assignMutation.isPending ? (
                <>
                  <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                  Meng-assign...
                </>
              ) : (
                <>
                  <UserPlus className="mr-2 h-4 w-4" />
                  Assign {selectedEmployees.length} Pegawai
                </>
              )}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Delete Dialog */}
      <AlertDialog open={deleteDialog.open} onOpenChange={(open) => setDeleteDialog({ open, schedule: null })}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Hapus Jadwal Bulanan?</AlertDialogTitle>
            <AlertDialogDescription>
              Apakah Anda yakin ingin menghapus "{deleteDialog.schedule?.name}"?
              Semua assignment pegawai ke jadwal ini juga akan dihapus. Tindakan ini tidak dapat dibatalkan.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Batal</AlertDialogCancel>
            <AlertDialogAction
              onClick={handleDelete}
              className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
              disabled={deleteMutation.isPending}
            >
              {deleteMutation.isPending ? (
                <>
                  <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                  Menghapus...
                </>
              ) : (
                'Hapus'
              )}
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>

      {/* View Assigned Employees Dialog */}
      <Dialog open={viewDialog.open} onOpenChange={(open) => setViewDialog({ open, schedule: null })}>
        <DialogContent className="max-w-3xl max-h-[80vh]">
          <DialogHeader>
            <DialogTitle>Pegawai yang Sudah Assigned</DialogTitle>
            <DialogDescription>
              Daftar pegawai yang sudah di-assign ke jadwal "{viewDialog.schedule?.name}"
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-4">
            {isLoadingAssignedEmployees ? (
              <div className="flex items-center justify-center py-12">
                <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
                <p className="ml-3 text-muted-foreground">Memuat data pegawai...</p>
              </div>
            ) : assignedEmployees.length === 0 ? (
              <div className="flex flex-col items-center justify-center py-12 text-muted-foreground">
                <Users className="h-12 w-12 mb-4" />
                <p className="text-lg font-medium">Belum ada pegawai yang assigned</p>
                <p className="text-sm">Silakan assign pegawai terlebih dahulu</p>
              </div>
            ) : (
              <div className="border rounded-lg overflow-hidden">
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>Nama</TableHead>
                      <TableHead>ID Pegawai</TableHead>
                      <TableHead>Posisi</TableHead>
                      <TableHead>Departemen</TableHead>
                      <TableHead>Lokasi</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {assignedEmployees.map((employee: any) => (
                      <TableRow key={employee.id}>
                        <TableCell>
                          <div className="flex items-center gap-3">
                            <Avatar className="h-8 w-8">
                              <AvatarFallback>{getInitials(employee.full_name || employee.name)}</AvatarFallback>
                            </Avatar>
                            <span className="font-medium">{employee.full_name || employee.name}</span>
                          </div>
                        </TableCell>
                        <TableCell className="text-muted-foreground">{employee.employee_id || '-'}</TableCell>
                        <TableCell>{employee.position || 'Belum Diatur'}</TableCell>
                        <TableCell>{employee.department || 'Belum Diatur'}</TableCell>
                        <TableCell>{employee.location?.name || '-'}</TableCell>
                      </TableRow>
                    ))}
                  </TableBody>
                </Table>
              </div>
            )}
            <div className="flex items-center justify-between pt-2 text-sm text-muted-foreground border-t">
              <span>Total: {assignedEmployees.length} pegawai</span>
              {viewDialog.schedule && (
                <span>Periode: {months[viewDialog.schedule.month - 1]} {viewDialog.schedule.year}</span>
              )}
            </div>
          </div>
          <DialogFooter>
            <Button
              variant="outline"
              onClick={() => setViewDialog({ open: false, schedule: null })}
            >
              Tutup
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div >
  );
}

export default function MonthlyScheduleIndexPage() {
  return <MonthlyScheduleList showHeader={true} />;
}
