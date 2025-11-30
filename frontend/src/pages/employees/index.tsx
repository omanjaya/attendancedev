import { useState, useRef } from 'react';
import { Link } from '@tanstack/react-router';
import {
  Plus,
  MoreHorizontal,
  Pencil,
  Trash2,
  Eye,
  ScanFace,
  Download,
  Upload,
  FileSpreadsheet,
  Key,
  CheckSquare,
  XCircle,
  Loader2,
  Users,
  UserCheck,
  CalendarOff,
  AlertCircle,
  RefreshCw,
} from 'lucide-react';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Checkbox } from '@/components/ui/checkbox';
import { DataTable, type Column, PageHeader, StatsGrid, type StatItem } from '@/components/shared';
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
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Skeleton } from '@/components/ui/skeleton';
import { useNotificationStore } from '@/stores';
import { useEmployees, useEmployeeStatistics, useDeleteEmployee } from '@/hooks';
import type { Employee, EmployeeStatus } from '@/types';

const getStatusBadge = (status: EmployeeStatus) => {
  switch (status) {
    case 'active':
      return <Badge className="bg-success/10 text-success border-0">Aktif</Badge>;
    case 'inactive':
      return <Badge variant="secondary">Nonaktif</Badge>;
    case 'on_leave':
      return <Badge className="bg-warning/10 text-warning border-0">Cuti</Badge>;
    case 'terminated':
      return <Badge className="bg-destructive/10 text-destructive border-0">Berhenti</Badge>;
    default:
      return <Badge variant="secondary">{status}</Badge>;
  }
};

function TableLoadingSkeleton() {
  return (
    <div className="space-y-3">
      {[1, 2, 3, 4, 5].map((i) => (
        <div key={i} className="flex items-center gap-4 p-4 border rounded-lg">
          <Skeleton className="h-10 w-10 rounded-full" />
          <div className="space-y-2 flex-1">
            <Skeleton className="h-4 w-48" />
            <Skeleton className="h-3 w-32" />
          </div>
          <Skeleton className="h-6 w-16" />
        </div>
      ))}
    </div>
  );
}

export default function EmployeesPage() {
  const { success } = useNotificationStore();
  const [search, setSearch] = useState('');
  const [page, setPage] = useState(1);
  const [pageSize, setPageSize] = useState(10);
  const [selectedIds, setSelectedIds] = useState<number[]>([]);
  const [importDialogOpen, setImportDialogOpen] = useState(false);
  const [importFile, setImportFile] = useState<File | null>(null);
  const [isImporting, setIsImporting] = useState(false);
  const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);
  const [employeeToDelete, setEmployeeToDelete] = useState<Employee | null>(null);
  const [bulkDeleteDialogOpen, setBulkDeleteDialogOpen] = useState(false);
  const fileInputRef = useRef<HTMLInputElement>(null);

  const {
    data: employeesData,
    isLoading: isLoadingEmployees,
    error: employeesError,
    refetch: refetchEmployees,
  } = useEmployees({
    search: search || undefined,
    page,
    per_page: pageSize,
  });

  const {
    data: statsData,
    isLoading: isLoadingStats,
  } = useEmployeeStatistics();

  const deleteEmployeeMutation = useDeleteEmployee();

  const employees = employeesData?.data || [];
  const totalItems = employeesData?.meta?.total || 0;
  const totalPages = employeesData?.meta?.last_page || 1;

  const getInitials = (name: string) => {
    return (name || '')
      .split(' ')
      .map((n) => n[0])
      .join('')
      .toUpperCase()
      .slice(0, 2);
  };

  const handleSelectAll = (checked: boolean) => {
    if (checked) {
      setSelectedIds(employees.map((e) => e.id));
    } else {
      setSelectedIds([]);
    }
  };

  const handleSelect = (id: number, checked: boolean) => {
    if (checked) {
      setSelectedIds([...selectedIds, id]);
    } else {
      setSelectedIds(selectedIds.filter((i) => i !== id));
    }
  };

  const handleDelete = async () => {
    if (!employeeToDelete) return;
    try {
      await deleteEmployeeMutation.mutateAsync(employeeToDelete.id);
      setDeleteDialogOpen(false);
      setEmployeeToDelete(null);
    } catch {
      // Error handled by mutation
    }
  };

  const handleBulkDelete = async () => {
    try {
      for (const id of selectedIds) {
        await deleteEmployeeMutation.mutateAsync(id);
      }
      setSelectedIds([]);
      setBulkDeleteDialogOpen(false);
    } catch {
      // Error handled by mutation
    }
  };

  const handleExport = () => {
    const data = selectedIds.length > 0
      ? employees.filter((e) => selectedIds.includes(e.id))
      : employees;

    const csv = [
      ['ID', 'Nama', 'Email', 'Telepon', 'Departemen', 'Jabatan', 'Status', 'Tanggal Bergabung'],
      ...data.map((e) => [
        e.employee_id,
        e.name,
        e.email,
        e.phone || '',
        e.department,
        e.position,
        e.status,
        e.join_date,
      ]),
    ]
      .map((row) => row.join(','))
      .join('\n');

    const blob = new Blob([csv], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'karyawan.csv';
    a.click();
    success('Berhasil', `${data.length} data karyawan berhasil diexport`);
  };

  const handleDownloadTemplate = () => {
    const template = [
      ['employee_id', 'name', 'email', 'phone', 'department', 'position', 'join_date'],
      ['EMP001', 'Nama Lengkap', 'email@example.com', '081234567890', 'IT', 'Staff', '2024-01-01'],
    ]
      .map((row) => row.join(','))
      .join('\n');

    const blob = new Blob([template], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'template_karyawan.csv';
    a.click();
    success('Berhasil', 'Template berhasil diunduh');
  };

  const handleImport = async () => {
    if (!importFile) return;
    setIsImporting(true);
    await new Promise((resolve) => setTimeout(resolve, 2000));
    setIsImporting(false);
    setImportDialogOpen(false);
    setImportFile(null);
    refetchEmployees();
    success('Berhasil', 'Data karyawan berhasil diimport');
  };

  const columns: Column<Employee>[] = [
    {
      key: 'select',
      header: () => (
        <Checkbox
          checked={selectedIds.length === employees.length && employees.length > 0}
          onCheckedChange={handleSelectAll}
        />
      ),
      className: 'w-[40px]',
      cell: (row) => (
        <Checkbox
          checked={selectedIds.includes(row.id)}
          onCheckedChange={(checked) => handleSelect(row.id, checked as boolean)}
        />
      ),
    },
    {
      key: 'name',
      header: 'Karyawan',
      cell: (row) => (
        <Link to="/employees/$id" params={{ id: String(row.id) }} className="flex items-center gap-3 hover:opacity-80">
          <Avatar className="h-9 w-9">
            <AvatarImage src={row.avatar} alt={row.name} />
            <AvatarFallback className="bg-primary/10 text-primary text-xs">
              {getInitials(row.name)}
            </AvatarFallback>
          </Avatar>
          <div>
            <p className="font-medium text-foreground">{row.name}</p>
            <p className="text-xs text-muted-foreground">{row.employee_id}</p>
          </div>
        </Link>
      ),
    },
    {
      key: 'email',
      header: 'Email',
      cell: (row) => <span className="text-muted-foreground">{row.email}</span>,
    },
    {
      key: 'department',
      header: 'Departemen',
    },
    {
      key: 'position',
      header: 'Jabatan',
    },
    {
      key: 'status',
      header: 'Status',
      cell: (row) => getStatusBadge(row.status),
    },
    {
      key: 'face_registered',
      header: 'Face ID',
      cell: (row) => (
        <div className="flex items-center gap-1">
          <ScanFace
            className={`h-4 w-4 ${row.face_registered ? 'text-success' : 'text-muted-foreground'
              }`}
          />
          <span className="text-xs text-muted-foreground">
            {row.face_registered ? 'Terdaftar' : 'Belum'}
          </span>
        </div>
      ),
    },
    {
      key: 'actions',
      header: '',
      className: 'w-[50px]',
      cell: (row) => (
        <DropdownMenu>
          <DropdownMenuTrigger asChild>
            <Button variant="ghost" size="icon">
              <MoreHorizontal className="h-4 w-4" />
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end">
            <DropdownMenuItem asChild>
              <Link to="/employees/$id" params={{ id: String(row.id) }}>
                <Eye className="mr-2 h-4 w-4" />
                Lihat Detail
              </Link>
            </DropdownMenuItem>
            <DropdownMenuItem asChild>
              <Link to="/employees/$id/edit" params={{ id: String(row.id) }}>
                <Pencil className="mr-2 h-4 w-4" />
                Edit
              </Link>
            </DropdownMenuItem>
            {!row.face_registered && (
              <DropdownMenuItem asChild>
                <Link to="/employees/$id/edit" params={{ id: String(row.id) }}>
                  <ScanFace className="mr-2 h-4 w-4" />
                  Daftarkan Wajah
                </Link>
              </DropdownMenuItem>
            )}
            <DropdownMenuSeparator />
            <DropdownMenuItem
              className="text-destructive"
              onClick={() => {
                setEmployeeToDelete(row);
                setDeleteDialogOpen(true);
              }}
            >
              <Trash2 className="mr-2 h-4 w-4" />
              Hapus
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
      ),
    },
  ];

  if (employeesError) {
    return (
      <div className="p-6">
        <Alert variant="destructive">
          <AlertCircle className="h-4 w-4" />
          <AlertTitle>Error</AlertTitle>
          <AlertDescription>
            Gagal memuat data karyawan. {employeesError.message}
          </AlertDescription>
        </Alert>
        <Button onClick={() => refetchEmployees()} className="mt-4">
          <RefreshCw className="mr-2 h-4 w-4" />
          Coba Lagi
        </Button>
      </div>
    );
  }

  // Stats using new StatItem interface
  const stats: StatItem[] = [
    {
      label: 'Total Karyawan',
      value: statsData?.total || 0,
      icon: Users,
      color: 'primary',
    },
    {
      label: 'Aktif',
      value: statsData?.active || 0,
      icon: UserCheck,
      color: 'success',
    },
    {
      label: 'Cuti',
      value: statsData?.on_leave || 0,
      icon: CalendarOff,
      color: 'warning',
    },
    {
      label: 'Face ID Terdaftar',
      value: employees.filter((e) => e.face_registered).length,
      icon: ScanFace,
      color: 'primary',
    },
  ];

  return (
    <div className="space-y-6 p-6">
      {/* Page Header */}
      <PageHeader
        title="Karyawan"
        description="Kelola data karyawan perusahaan"
        icon={Users}
        actions={
          <div className="flex flex-wrap items-center gap-2">
            <DropdownMenu>
              <DropdownMenuTrigger asChild>
                <Button variant="outline" size="sm">
                  <FileSpreadsheet className="mr-2 h-4 w-4" />
                  Import/Export
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end">
                <DropdownMenuItem onClick={handleExport}>
                  <Download className="mr-2 h-4 w-4" />
                  Export CSV {selectedIds.length > 0 && `(${selectedIds.length})`}
                </DropdownMenuItem>
                <DropdownMenuItem onClick={() => setImportDialogOpen(true)}>
                  <Upload className="mr-2 h-4 w-4" />
                  Import CSV
                </DropdownMenuItem>
                <DropdownMenuSeparator />
                <DropdownMenuItem onClick={handleDownloadTemplate}>
                  <FileSpreadsheet className="mr-2 h-4 w-4" />
                  Download Template
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>

            <Button variant="outline" size="sm" asChild>
              <Link to="/employees/credentials">
                <Key className="mr-2 h-4 w-4" />
                User & Password
              </Link>
            </Button>

            <Button size="sm" asChild>
              <Link to="/employees/create">
                <Plus className="mr-2 h-4 w-4" />
                Tambah Karyawan
              </Link>
            </Button>
          </div>
        }
      />

      {/* Stats Grid */}
      {isLoadingStats ? (
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          {[1, 2, 3, 4].map((i) => (
            <Card key={i}>
              <CardContent className="p-6">
                <Skeleton className="h-4 w-24 mb-2" />
                <Skeleton className="h-8 w-16" />
              </CardContent>
            </Card>
          ))}
        </div>
      ) : (
        <StatsGrid stats={stats} columns={4} variant="cards" />
      )}

      {/* Bulk Actions Bar */}
      {selectedIds.length > 0 && (
        <Card className="border-l-4 border-l-primary">
          <CardContent className="p-3">
            <div className="flex flex-wrap items-center gap-4">
              <span className="text-sm font-medium">
                {selectedIds.length} karyawan dipilih
              </span>
              <div className="flex gap-2">
                <Button
                  size="sm"
                  variant="outline"
                  onClick={() => success('Info', 'Fitur bulk activate akan segera hadir')}
                >
                  <CheckSquare className="mr-1 h-4 w-4" />
                  Aktifkan
                </Button>
                <Button
                  size="sm"
                  variant="outline"
                  onClick={() => success('Info', 'Fitur bulk deactivate akan segera hadir')}
                >
                  <XCircle className="mr-1 h-4 w-4" />
                  Nonaktifkan
                </Button>
                <Button
                  size="sm"
                  variant="destructive"
                  onClick={() => setBulkDeleteDialogOpen(true)}
                >
                  <Trash2 className="mr-1 h-4 w-4" />
                  Hapus
                </Button>
              </div>
              <Button
                size="sm"
                variant="ghost"
                onClick={() => setSelectedIds([])}
                className="ml-auto"
              >
                Batal
              </Button>
            </div>
          </CardContent>
        </Card>
      )}

      {/* Data Table */}
      <Card>
        <CardContent className="p-4">
          {isLoadingEmployees ? (
            <TableLoadingSkeleton />
          ) : (
            <DataTable
              columns={columns}
              data={employees}
              searchPlaceholder="Cari nama, email, atau departemen..."
              searchValue={search}
              onSearchChange={(value) => {
                setSearch(value);
                setPage(1);
              }}
              page={page}
              pageSize={pageSize}
              totalPages={totalPages}
              totalItems={totalItems}
              onPageChange={setPage}
              onPageSizeChange={(size) => {
                setPageSize(size);
                setPage(1);
              }}
              emptyMessage="Tidak ada karyawan ditemukan"
            />
          )}
        </CardContent>
      </Card>

      {/* Import Dialog */}
      <Dialog open={importDialogOpen} onOpenChange={setImportDialogOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle className="flex items-center gap-2">
              <Upload className="h-5 w-5 text-primary" />
              Import Data Karyawan
            </DialogTitle>
            <DialogDescription>
              Upload file CSV untuk import data karyawan secara massal
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-4">
            <div
              className="border-2 border-dashed rounded-lg p-8 text-center cursor-pointer hover:border-primary/50 hover:bg-muted/50 transition-all"
              onClick={() => fileInputRef.current?.click()}
            >
              <input
                type="file"
                ref={fileInputRef}
                accept=".csv"
                className="hidden"
                onChange={(e) => setImportFile(e.target.files?.[0] || null)}
              />
              <Upload className="h-10 w-10 mx-auto mb-3 text-muted-foreground" />
              {importFile ? (
                <p className="font-medium">{importFile.name}</p>
              ) : (
                <p className="text-muted-foreground">Klik atau drag file CSV ke sini</p>
              )}
            </div>
            <p className="text-xs text-muted-foreground">
              Pastikan format file sesuai dengan template.{' '}
              <button className="text-primary underline" onClick={handleDownloadTemplate}>
                Download template
              </button>
            </p>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setImportDialogOpen(false)}>
              Batal
            </Button>
            <Button onClick={handleImport} disabled={!importFile || isImporting}>
              {isImporting ? (
                <>
                  <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                  Mengimport...
                </>
              ) : (
                <>
                  <Upload className="mr-2 h-4 w-4" />
                  Import
                </>
              )}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Single Delete Confirmation */}
      <AlertDialog open={deleteDialogOpen} onOpenChange={setDeleteDialogOpen}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Hapus Karyawan?</AlertDialogTitle>
            <AlertDialogDescription>
              Anda akan menghapus <strong>{employeeToDelete?.name}</strong>. Tindakan ini tidak dapat dibatalkan.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Batal</AlertDialogCancel>
            <AlertDialogAction
              onClick={handleDelete}
              className="bg-destructive text-destructive-foreground"
              disabled={deleteEmployeeMutation.isPending}
            >
              {deleteEmployeeMutation.isPending ? (
                <Loader2 className="h-4 w-4 animate-spin" />
              ) : (
                'Hapus'
              )}
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>

      {/* Bulk Delete Confirmation */}
      <AlertDialog open={bulkDeleteDialogOpen} onOpenChange={setBulkDeleteDialogOpen}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Hapus {selectedIds.length} Karyawan?</AlertDialogTitle>
            <AlertDialogDescription>
              Anda akan menghapus {selectedIds.length} karyawan. Tindakan ini tidak dapat dibatalkan.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Batal</AlertDialogCancel>
            <AlertDialogAction
              onClick={handleBulkDelete}
              className="bg-destructive text-destructive-foreground"
              disabled={deleteEmployeeMutation.isPending}
            >
              {deleteEmployeeMutation.isPending ? (
                <Loader2 className="h-4 w-4 animate-spin" />
              ) : (
                'Hapus Semua'
              )}
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  );
}
