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
} from 'lucide-react';
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
import { DataTable, type Column } from '@/components/shared';
import { EmployeeFormDialog } from '@/components/features/employees';
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
import { useNotificationStore } from '@/stores';
import type { Employee, EmployeeStatus, EmployeeFormData } from '@/types';

// Mock data
const mockEmployees: Employee[] = [
  {
    id: 1,
    employee_id: 'EMP001',
    name: 'Ahmad Rizki',
    email: 'ahmad.rizki@company.com',
    phone: '081234567890',
    position: 'Software Engineer',
    department: 'IT',
    status: 'active',
    join_date: '2023-01-15',
    face_registered: true,
    created_at: '2023-01-15T00:00:00',
    updated_at: '2023-01-15T00:00:00',
  },
  {
    id: 2,
    employee_id: 'EMP002',
    name: 'Siti Nurhaliza',
    email: 'siti.nurhaliza@company.com',
    phone: '081234567891',
    position: 'HR Manager',
    department: 'HR',
    status: 'active',
    join_date: '2022-06-01',
    face_registered: true,
    created_at: '2022-06-01T00:00:00',
    updated_at: '2022-06-01T00:00:00',
  },
  {
    id: 3,
    employee_id: 'EMP003',
    name: 'Budi Santoso',
    email: 'budi.santoso@company.com',
    phone: '081234567892',
    position: 'Accountant',
    department: 'Finance',
    status: 'active',
    join_date: '2023-03-10',
    face_registered: false,
    created_at: '2023-03-10T00:00:00',
    updated_at: '2023-03-10T00:00:00',
  },
  {
    id: 4,
    employee_id: 'EMP004',
    name: 'Dewi Anggraini',
    email: 'dewi.anggraini@company.com',
    phone: '081234567893',
    position: 'Marketing Specialist',
    department: 'Marketing',
    status: 'on_leave',
    join_date: '2022-09-20',
    face_registered: true,
    created_at: '2022-09-20T00:00:00',
    updated_at: '2022-09-20T00:00:00',
  },
  {
    id: 5,
    employee_id: 'EMP005',
    name: 'Eko Prasetyo',
    email: 'eko.prasetyo@company.com',
    phone: '081234567894',
    position: 'Operations Manager',
    department: 'Operations',
    status: 'active',
    join_date: '2021-11-05',
    face_registered: true,
    created_at: '2021-11-05T00:00:00',
    updated_at: '2021-11-05T00:00:00',
  },
];

// Status badge styles
const getStatusBadge = (status: EmployeeStatus) => {
  switch (status) {
    case 'active':
      return <Badge className="bg-success text-success-foreground">Aktif</Badge>;
    case 'inactive':
      return <Badge variant="secondary">Nonaktif</Badge>;
    case 'on_leave':
      return <Badge className="bg-warning text-warning-foreground">Cuti</Badge>;
    case 'terminated':
      return <Badge variant="destructive">Berhenti</Badge>;
    default:
      return <Badge variant="secondary">{status}</Badge>;
  }
};

export default function EmployeesPage() {
  const { success } = useNotificationStore();
  const [search, setSearch] = useState('');
  const [page, setPage] = useState(1);
  const [pageSize, setPageSize] = useState(10);
  const [dialogOpen, setDialogOpen] = useState(false);
  const [selectedEmployee, setSelectedEmployee] = useState<Employee | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [selectedIds, setSelectedIds] = useState<number[]>([]);
  const [importDialogOpen, setImportDialogOpen] = useState(false);
  const [importFile, setImportFile] = useState<File | null>(null);
  const [isImporting, setIsImporting] = useState(false);
  const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);
  const [bulkAction, setBulkAction] = useState<'activate' | 'deactivate' | 'delete' | null>(null);
  const fileInputRef = useRef<HTMLInputElement>(null);

  // Handle form submit
  const handleFormSubmit = async (data: EmployeeFormData) => {
    setIsSubmitting(true);
    await new Promise((resolve) => setTimeout(resolve, 1000));
    console.log('Form data:', data);
    setIsSubmitting(false);
    setSelectedEmployee(null);
    success('Berhasil', 'Data karyawan berhasil disimpan');
  };

  // Filter data based on search
  const filteredData = mockEmployees.filter(
    (emp) =>
      emp.name.toLowerCase().includes(search.toLowerCase()) ||
      emp.email.toLowerCase().includes(search.toLowerCase()) ||
      emp.employee_id.toLowerCase().includes(search.toLowerCase()) ||
      emp.department.toLowerCase().includes(search.toLowerCase())
  );

  // Get initials for avatar
  const getInitials = (name: string) => {
    return name
      .split(' ')
      .map((n) => n[0])
      .join('')
      .toUpperCase()
      .slice(0, 2);
  };

  // Handle select all
  const handleSelectAll = (checked: boolean) => {
    if (checked) {
      setSelectedIds(filteredData.map((e) => e.id));
    } else {
      setSelectedIds([]);
    }
  };

  // Handle select single
  const handleSelect = (id: number, checked: boolean) => {
    if (checked) {
      setSelectedIds([...selectedIds, id]);
    } else {
      setSelectedIds(selectedIds.filter((i) => i !== id));
    }
  };

  // Export to CSV
  const handleExport = () => {
    const data = selectedIds.length > 0
      ? mockEmployees.filter((e) => selectedIds.includes(e.id))
      : mockEmployees;

    const csv = [
      ['ID', 'Nama', 'Email', 'Telepon', 'Departemen', 'Jabatan', 'Status', 'Tanggal Bergabung'],
      ...data.map((e) => [
        e.employee_id,
        e.name,
        e.email,
        e.phone,
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

  // Download template
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

  // Handle import
  const handleImport = async () => {
    if (!importFile) return;

    setIsImporting(true);
    await new Promise((resolve) => setTimeout(resolve, 2000));
    setIsImporting(false);
    setImportDialogOpen(false);
    setImportFile(null);
    success('Berhasil', 'Data karyawan berhasil diimport');
  };

  // Handle bulk action
  const handleBulkAction = async () => {
    if (!bulkAction || selectedIds.length === 0) return;

    setIsSubmitting(true);
    await new Promise((resolve) => setTimeout(resolve, 1000));

    const actionText =
      bulkAction === 'activate'
        ? 'diaktifkan'
        : bulkAction === 'deactivate'
        ? 'dinonaktifkan'
        : 'dihapus';

    success('Berhasil', `${selectedIds.length} karyawan berhasil ${actionText}`);
    setSelectedIds([]);
    setBulkAction(null);
    setDeleteDialogOpen(false);
    setIsSubmitting(false);
  };

  // Define columns
  const columns: Column<Employee>[] = [
    {
      key: 'select',
      header: () => (
        <Checkbox
          checked={selectedIds.length === filteredData.length && filteredData.length > 0}
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
        <a href={`/employees/${row.id}`} className="flex items-center gap-3 hover:opacity-80">
          <Avatar className="h-9 w-9">
            <AvatarImage src={row.avatar} alt={row.name} />
            <AvatarFallback className="bg-primary text-primary-foreground text-xs">
              {getInitials(row.name)}
            </AvatarFallback>
          </Avatar>
          <div>
            <p className="font-medium text-foreground">{row.name}</p>
            <p className="text-xs text-muted-foreground">{row.employee_id}</p>
          </div>
        </a>
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
            className={`h-4 w-4 ${
              row.face_registered ? 'text-success' : 'text-muted-foreground'
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
              <a href={`/employees/${row.id}`}>
                <Eye className="mr-2 h-4 w-4" />
                Lihat Detail
              </a>
            </DropdownMenuItem>
            <DropdownMenuItem asChild>
              <a href={`/employees/${row.id}/edit`}>
                <Pencil className="mr-2 h-4 w-4" />
                Edit
              </a>
            </DropdownMenuItem>
            {!row.face_registered && (
              <DropdownMenuItem>
                <ScanFace className="mr-2 h-4 w-4" />
                Daftarkan Wajah
              </DropdownMenuItem>
            )}
            <DropdownMenuSeparator />
            <DropdownMenuItem className="text-destructive">
              <Trash2 className="mr-2 h-4 w-4" />
              Hapus
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
      ),
    },
  ];

  return (
    <div className="p-6">
      {/* Header */}
      <div className="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold text-foreground">Karyawan</h1>
          <p className="text-sm text-muted-foreground">
            Kelola data karyawan perusahaan
          </p>
        </div>
        <div className="flex flex-wrap gap-2">
          {/* Import/Export Dropdown */}
          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <Button variant="outline">
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

          {/* Credentials Link */}
          <Button variant="outline" asChild>
            <Link to="/employees/credentials">
              <Key className="mr-2 h-4 w-4" />
              User & Password
            </Link>
          </Button>

          {/* Add New */}
          <Button asChild>
            <Link to="/employees/create">
              <Plus className="mr-2 h-4 w-4" />
              Tambah Karyawan
            </Link>
          </Button>
        </div>
      </div>

      {/* Bulk Actions Bar */}
      {selectedIds.length > 0 && (
        <div className="mb-4 flex items-center gap-4 rounded-lg border border-primary/20 bg-primary/5 p-3">
          <span className="text-sm font-medium">
            {selectedIds.length} karyawan dipilih
          </span>
          <div className="flex gap-2">
            <Button
              size="sm"
              variant="outline"
              onClick={() => {
                setBulkAction('activate');
                handleBulkAction();
              }}
            >
              <CheckSquare className="mr-1 h-4 w-4" />
              Aktifkan
            </Button>
            <Button
              size="sm"
              variant="outline"
              onClick={() => {
                setBulkAction('deactivate');
                handleBulkAction();
              }}
            >
              <XCircle className="mr-1 h-4 w-4" />
              Nonaktifkan
            </Button>
            <Button
              size="sm"
              variant="destructive"
              onClick={() => {
                setBulkAction('delete');
                setDeleteDialogOpen(true);
              }}
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
      )}

      {/* Stats */}
      <div className="mb-6 grid gap-4 sm:grid-cols-4">
        {[
          { label: 'Total', value: mockEmployees.length, color: 'text-foreground' },
          {
            label: 'Aktif',
            value: mockEmployees.filter((e) => e.status === 'active').length,
            color: 'text-success',
          },
          {
            label: 'Cuti',
            value: mockEmployees.filter((e) => e.status === 'on_leave').length,
            color: 'text-warning',
          },
          {
            label: 'Face ID',
            value: mockEmployees.filter((e) => e.face_registered).length,
            color: 'text-primary',
          },
        ].map((stat) => (
          <div key={stat.label} className="rounded-lg border border-border bg-card p-4">
            <p className="text-xs text-muted-foreground">{stat.label}</p>
            <p className={`text-xl font-bold ${stat.color}`}>{stat.value}</p>
          </div>
        ))}
      </div>

      {/* Data Table */}
      <div className="rounded-lg border border-border bg-card p-4">
        <DataTable
          columns={columns}
          data={filteredData}
          searchPlaceholder="Cari nama, email, atau departemen..."
          searchValue={search}
          onSearchChange={setSearch}
          page={page}
          pageSize={pageSize}
          totalPages={Math.ceil(filteredData.length / pageSize)}
          totalItems={filteredData.length}
          onPageChange={setPage}
          onPageSizeChange={(size) => {
            setPageSize(size);
            setPage(1);
          }}
          emptyMessage="Tidak ada karyawan ditemukan"
        />
      </div>

      {/* Employee Form Dialog */}
      <EmployeeFormDialog
        open={dialogOpen}
        onOpenChange={setDialogOpen}
        employee={selectedEmployee}
        onSubmit={handleFormSubmit}
        isLoading={isSubmitting}
      />

      {/* Import Dialog */}
      <Dialog open={importDialogOpen} onOpenChange={setImportDialogOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Import Data Karyawan</DialogTitle>
            <DialogDescription>
              Upload file CSV untuk import data karyawan secara massal
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-4">
            <div
              className="border-2 border-dashed rounded-lg p-8 text-center cursor-pointer hover:border-primary/50 transition-colors"
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
                <p className="text-muted-foreground">
                  Klik atau drag file CSV ke sini
                </p>
              )}
            </div>
            <p className="text-xs text-muted-foreground">
              Pastikan format file sesuai dengan template.{' '}
              <button
                className="text-primary underline"
                onClick={handleDownloadTemplate}
              >
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

      {/* Delete Confirmation Dialog */}
      <AlertDialog open={deleteDialogOpen} onOpenChange={setDeleteDialogOpen}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Hapus Karyawan?</AlertDialogTitle>
            <AlertDialogDescription>
              Anda akan menghapus {selectedIds.length} karyawan. Tindakan ini tidak
              dapat dibatalkan.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Batal</AlertDialogCancel>
            <AlertDialogAction
              onClick={handleBulkAction}
              className="bg-destructive text-destructive-foreground"
            >
              {isSubmitting ? (
                <Loader2 className="h-4 w-4 animate-spin" />
              ) : (
                'Hapus'
              )}
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  );
}
