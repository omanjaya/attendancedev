import { useState } from 'react';
import {
    Plus,
    Search,
    Filter,
    MoreHorizontal,
    Download,
    Upload,
    Users,
    Building2,
    Mail,
    Trash2,
    Phone,
    UserPlus,
    UserCheck,
    UserX,
    KeyRound,
    Copy,
    Eye,
    EyeOff,
    CheckSquare,
    Power,
    PowerOff,
    ChevronLeft,
    ChevronRight,
} from 'lucide-react';
import { Link, useNavigate } from '@tanstack/react-router';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
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
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { PageHeader } from '@/components/shared';
import { useEmployees } from '@/hooks/use-employees';
import { useEmployeesPage } from '@/hooks/use-employees-page';
import { LoadingState } from '@/components/states';
import { bulkEmployeeAction, type BulkActionResult } from '@/lib/api/employees';
import { toast } from 'sonner';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { useQueryClient } from '@tanstack/react-query';
import { ExcelImportDialog, type ExcelColumn, type ImportResult } from '@/components/shared/ExcelImportDialog';
import { importEmployees } from '@/lib/api/imports';
export function DesktopEmployeesPage() {
    const navigate = useNavigate();
    const queryClient = useQueryClient();

    // Use shared hook for common logic
    const logic = useEmployeesPage();

    // Desktop-specific state
    const [currentPage, setCurrentPage] = useState(1);
    const perPage = 50;

    const { data: employeesData, isLoading, refetch } = useEmployees({
        search: logic.searchQuery,
        per_page: perPage,
        page: currentPage,
    });

    // Bulk selection states
    const [selectedEmployees, setSelectedEmployees] = useState<string[]>([]);
    const [bulkActionDialog, setBulkActionDialog] = useState<{
        open: boolean;
        action: 'delete' | 'reset_password' | 'activate' | 'deactivate' | null;
    }>({ open: false, action: null });
    const [isBulkProcessing, setIsBulkProcessing] = useState(false);
    const [bulkResult, setBulkResult] = useState<BulkActionResult | null>(null);

    // Import dialog state
    const [isImportDialogOpen, setIsImportDialogOpen] = useState(false);

    // Employee import columns definition
    const employeeImportColumns: ExcelColumn[] = [
        { key: 'full_name', label: 'Nama Lengkap', required: true, type: 'string', width: 25 },
        { key: 'email', label: 'Email', required: true, type: 'email', width: 25 },
        { key: 'password', label: 'Password', required: true, type: 'string', width: 15 },
        { key: 'employee_type', label: 'Jenis Pegawai', required: true, type: 'string', width: 15 },
        { key: 'hire_date', label: 'Tanggal Masuk', required: true, type: 'date', width: 15 },
    ];

    // Handle employee file import using backend API
    const handleFileImport = async (file: File): Promise<ImportResult> => {
        const result = await importEmployees(file);
        return result;
    };

    // Handle successful import - refresh data
    const handleImportSuccess = () => {
        refetch();
        queryClient.invalidateQueries({ queryKey: ['employees'] });
        toast.success('Import berhasil!', {
            description: 'Data karyawan telah diperbarui.',
        });
    };

    // Bulk selection handlers
    const handleSelectAll = (checked: boolean) => {
        if (checked) {
            setSelectedEmployees(employees.map(e => e.id));
        } else {
            setSelectedEmployees([]);
        }
    };

    const handleSelectEmployee = (employeeId: string, checked: boolean) => {
        if (checked) {
            setSelectedEmployees(prev => [...prev, employeeId]);
        } else {
            setSelectedEmployees(prev => prev.filter(id => id !== employeeId));
        }
    };

    const handleBulkAction = async () => {
        if (!bulkActionDialog.action || selectedEmployees.length === 0) return;

        setIsBulkProcessing(true);
        try {
            const result = await bulkEmployeeAction(bulkActionDialog.action, selectedEmployees);
            setBulkResult(result);

            if (result.success > 0) {
                const actionLabel = {
                    delete: 'dihapus',
                    reset_password: 'password direset',
                    activate: 'diaktifkan',
                    deactivate: 'dinonaktifkan'
                }[bulkActionDialog.action];

                toast.success(`${result.success} karyawan berhasil ${actionLabel}`, {
                    description: result.failed > 0 ? `${result.failed} gagal` : undefined,
                });

                // Refresh data and clear selection
                await refetch();
                queryClient.invalidateQueries({ queryKey: ['employees'] });

                if (bulkActionDialog.action !== 'reset_password') {
                    setSelectedEmployees([]);
                    setBulkActionDialog({ open: false, action: null });
                    setBulkResult(null);
                }
            }
        } catch (error) {
            console.error('Bulk action failed:', error);
            toast.error('Gagal melakukan aksi massal', {
                description: error instanceof Error ? error.message : 'Terjadi kesalahan.',
            });
            // Close dialog on error so user isn't stuck
            setBulkActionDialog({ open: false, action: null });
        } finally {
            setIsBulkProcessing(false);
        }
    };

    const handleCloseBulkDialog = () => {
        setBulkActionDialog({ open: false, action: null });
        setBulkResult(null);
        if (bulkResult?.success && bulkResult.success > 0) {
            setSelectedEmployees([]);
        }
    };

    const employees = employeesData?.data || [];
    const totalEmployees = employeesData?.meta?.total || 0;
    const totalPages = employeesData?.meta?.last_page || 1;
    const isAllSelected = employees.length > 0 && selectedEmployees.length === employees.length;
    const isSomeSelected = selectedEmployees.length > 0 && selectedEmployees.length < employees.length;

    // Pagination handlers
    const handlePageChange = (page: number) => {
        setCurrentPage(page);
        setSelectedEmployees([]); // Clear selection when changing page
    };

    const getStatusBadge = (status: string) => {
        switch (status) {
            case 'active':
                return <Badge className="bg-emerald-100 text-emerald-700 hover:bg-emerald-100 border-emerald-200">Aktif</Badge>;
            case 'inactive':
                return <Badge variant="destructive" className="bg-red-100 text-red-700 hover:bg-red-100 border-red-200">Tidak Aktif</Badge>;
            case 'on_leave':
                return <Badge variant="secondary" className="bg-amber-100 text-amber-700 hover:bg-amber-100 border-amber-200">Cuti</Badge>;
            default:
                return <Badge variant="outline">{status}</Badge>;
        }
    };

    // Quick stats for the top of the page
    const quickStats = [
        { label: 'Total Karyawan', value: totalEmployees, icon: Users, color: 'text-primary', bg: 'bg-primary/10' },
        { label: 'Karyawan Aktif', value: employees.filter(e => e.status === 'active').length, icon: UserCheck, color: 'text-emerald-600', bg: 'bg-emerald-100' },
        { label: 'Sedang Cuti', value: employees.filter(e => e.status === 'on_leave').length, icon: UserX, color: 'text-amber-600', bg: 'bg-amber-100' },
    ];

    const handleExport = () => {
        if (!employeesData?.data) return;

        // Define CSV headers
        const headers = ['ID', 'NIP', 'Nama', 'Email', 'Telepon', 'Departemen', 'Jabatan', 'Status', 'Tanggal Bergabung'];

        // Map employee data to rows
        const rows = employeesData.data.map(emp => [
            emp.id,
            emp.employee_id,
            emp.name,
            emp.email,
            emp.phone || '-',
            emp.department,
            emp.position,
            emp.status,
            emp.join_date
        ]);

        // Combine headers and rows
        const csvContent = [
            headers.join(','),
            ...rows.map(row => row.map(cell => `"${cell}"`).join(','))
        ].join('\n');

        // Create download link
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        if (link.download !== undefined) {
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', `employees_export_${new Date().toISOString().split('T')[0]}.csv`);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    };

    return (
        <div className="space-y-8 p-8 min-h-screen bg-background/50">
            {/* Background Gradients */}
            <div className="pointer-events-none fixed inset-0 -z-10 overflow-hidden">
                <div className="absolute top-0 left-1/4 w-96 h-96 bg-primary/5 rounded-full blur-3xl" />
                <div className="absolute bottom-0 right-1/4 w-96 h-96 bg-secondary/20 rounded-full blur-3xl" />
            </div>

            <PageHeader
                title="Karyawan"
                description="Kelola data karyawan, departemen, dan jabatan"
                icon={Users}
                actions={
                    <div className="flex items-center gap-3">
                        <Button variant="outline" size="sm" className="h-9 shadow-sm hover:bg-accent" onClick={handleExport}>
                            <Download className="mr-2 h-4 w-4" />
                            Export
                        </Button>
                        <Button variant="outline" size="sm" className="h-9 shadow-sm hover:bg-accent" onClick={() => setIsImportDialogOpen(true)}>
                            <Upload className="mr-2 h-4 w-4" />
                            Import Excel
                        </Button>
                        <Button size="sm" asChild className="h-9 shadow-md bg-primary hover:bg-primary/90 transition-all hover:scale-105">
                            <Link to="/admin/employees/create">
                                <Plus className="mr-2 h-4 w-4" />
                                Tambah Karyawan
                            </Link>
                        </Button>
                    </div>
                }
            />

            {/* Quick Stats */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                {quickStats.map((stat) => (
                    <div key={stat.label}>
                        <Card className="border-none shadow-sm hover:shadow-md transition-shadow bg-card/50 backdrop-blur-sm">
                            <CardContent className="p-6 flex items-center justify-between">
                                <div>
                                    <p className="text-sm font-medium text-muted-foreground mb-1">{stat.label}</p>
                                    <h3 className="text-2xl font-bold">{stat.value}</h3>
                                </div>
                                <div className={`p-3 rounded-xl ${stat.bg}`}>
                                    <stat.icon className={`h-6 w-6 ${stat.color}`} />
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                ))}
            </div>

            {/* Main Content Card */}
            <div>
                <Card className="border-none shadow-lg bg-card/80 backdrop-blur-md overflow-hidden">
                    <CardHeader className="border-b bg-muted/30 px-6 py-4">
                        <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div>
                                <CardTitle className="text-lg font-semibold">Daftar Karyawan</CardTitle>
                                <CardDescription>Total {totalEmployees} karyawan terdaftar</CardDescription>
                            </div>

                            {/* Search & Filter */}
                            <div className="flex items-center gap-3 w-full md:w-auto">
                                <div className="relative w-full md:w-72">
                                    <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                                    <Input
                                        type="search"
                                        placeholder="Cari nama, NIP, atau email..."
                                        className="pl-9 w-full bg-background/50 border-muted-foreground/20 focus:bg-background transition-all"
                                        value={logic.searchQuery}
                                        onChange={(e) => logic.setSearchQuery(e.target.value)}
                                    />
                                </div>
                                <Button variant="outline" size="icon" className="shrink-0 bg-background/50 border-muted-foreground/20">
                                    <Filter className="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                    </CardHeader>

                    {/* Bulk Action Bar */}
                    {selectedEmployees.length > 0 && (
                        <div className="px-6 py-3 bg-primary/5 border-b flex items-center justify-between">
                            <div className="flex items-center gap-3">
                                <CheckSquare className="h-5 w-5 text-primary" />
                                <span className="text-sm font-medium">
                                    {selectedEmployees.length} karyawan dipilih
                                </span>
                            </div>
                            <div className="flex items-center gap-2">
                                <Button
                                    size="sm"
                                    variant="outline"
                                    className="gap-2"
                                    onClick={() => setBulkActionDialog({ open: true, action: 'reset_password' })}
                                >
                                    <KeyRound className="h-4 w-4" />
                                    Reset Password
                                </Button>
                                <Button
                                    size="sm"
                                    variant="outline"
                                    className="gap-2 text-green-600 hover:text-green-700"
                                    onClick={() => setBulkActionDialog({ open: true, action: 'activate' })}
                                >
                                    <Power className="h-4 w-4" />
                                    Aktifkan
                                </Button>
                                <Button
                                    size="sm"
                                    variant="outline"
                                    className="gap-2 text-amber-600 hover:text-amber-700"
                                    onClick={() => setBulkActionDialog({ open: true, action: 'deactivate' })}
                                >
                                    <PowerOff className="h-4 w-4" />
                                    Nonaktifkan
                                </Button>
                                <Button
                                    size="sm"
                                    variant="outline"
                                    className="gap-2 text-destructive hover:text-destructive"
                                    onClick={() => setBulkActionDialog({ open: true, action: 'delete' })}
                                >
                                    <Trash2 className="h-4 w-4" />
                                    Hapus
                                </Button>
                                <Button
                                    size="sm"
                                    variant="ghost"
                                    onClick={() => setSelectedEmployees([])}
                                >
                                    Batal
                                </Button>
                            </div>
                        </div>
                    )}

                    <CardContent className="p-0">
                        {isLoading ? (
                            <div className="py-12">
                                <LoadingState message="Memuat data karyawan..." />
                            </div>
                        ) : employees.length === 0 ? (
                            <div className="text-center py-16 text-muted-foreground flex flex-col items-center gap-3">
                                <div className="p-4 rounded-full bg-muted">
                                    <Users className="h-8 w-8 text-muted-foreground/50" />
                                </div>
                                <p>Tidak ada data karyawan ditemukan</p>
                                {logic.searchQuery && (
                                    <Button variant="link" onClick={() => logic.setSearchQuery('')} className="text-primary">
                                        Hapus pencarian
                                    </Button>
                                )}
                            </div>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow className="bg-muted/30 hover:bg-muted/30">
                                        <TableHead className="w-12 pl-6">
                                            <Checkbox
                                                checked={isAllSelected}
                                                onCheckedChange={(checked) => handleSelectAll(checked === true)}
                                                aria-label="Select all"
                                                className={isSomeSelected ? "data-[state=checked]:bg-primary/50" : ""}
                                            />
                                        </TableHead>
                                        <TableHead className="w-[280px]">Karyawan</TableHead>
                                        <TableHead>Kontak</TableHead>
                                        <TableHead>Departemen & Jabatan</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead className="text-right pr-6">Aksi</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {employees.map((employee) => (
                                        <TableRow
                                            key={employee.id}
                                            className={`group hover:bg-muted/40 transition-colors ${selectedEmployees.includes(employee.id) ? 'bg-primary/5' : ''}`}
                                        >
                                            <TableCell className="pl-6 py-4">
                                                <Checkbox
                                                    checked={selectedEmployees.includes(employee.id)}
                                                    onCheckedChange={(checked) => handleSelectEmployee(employee.id, checked === true)}
                                                    aria-label={`Select ${employee.name}`}
                                                />
                                            </TableCell>
                                            <TableCell className="py-4">
                                                <div className="flex items-center gap-4">
                                                    <Avatar className="h-10 w-10 border-2 border-background shadow-sm group-hover:scale-105 transition-transform">
                                                        <AvatarImage src={employee.avatar || undefined} alt={employee.name} className="object-cover" />
                                                        <AvatarFallback className="bg-primary/10 text-primary font-medium">
                                                            {logic.getInitials(employee.name)}
                                                        </AvatarFallback>
                                                    </Avatar>
                                                    <div>
                                                        <div className="font-semibold text-foreground group-hover:text-primary transition-colors">
                                                            {employee.name}
                                                        </div>
                                                        <div className="text-xs text-muted-foreground font-mono mt-0.5">
                                                            {employee.employee_id}
                                                        </div>
                                                    </div>
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <div className="space-y-1.5 text-sm">
                                                    <div className="flex items-center gap-2 text-muted-foreground group-hover:text-foreground transition-colors">
                                                        <Mail className="h-3.5 w-3.5" />
                                                        {employee.email}
                                                    </div>
                                                    {employee.phone && (
                                                        <div className="flex items-center gap-2 text-muted-foreground group-hover:text-foreground transition-colors">
                                                            <Phone className="h-3.5 w-3.5" />
                                                            {employee.phone}
                                                        </div>
                                                    )}
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <div className="space-y-1.5">
                                                    <div className="font-medium">{employee.position}</div>
                                                    <div className="flex items-center gap-1.5 text-xs text-muted-foreground">
                                                        <Building2 className="h-3.5 w-3.5" />
                                                        {employee.department}
                                                    </div>
                                                </div>
                                            </TableCell>
                                            <TableCell>{getStatusBadge(employee.status)}</TableCell>
                                            <TableCell className="text-right pr-6">
                                                <DropdownMenu>
                                                    <DropdownMenuTrigger asChild>
                                                        <Button variant="ghost" size="icon" className="h-8 w-8">
                                                            <MoreHorizontal className="h-4 w-4" />
                                                        </Button>
                                                    </DropdownMenuTrigger>
                                                    <DropdownMenuContent align="end" className="w-48">
                                                        <DropdownMenuItem
                                                            className="cursor-pointer"
                                                            onClick={() => navigate({ to: '/admin/employees/$id', params: { id: employee.id } })}
                                                        >
                                                            <Users className="mr-2 h-4 w-4" />
                                                            Lihat Detail
                                                        </DropdownMenuItem>
                                                        <DropdownMenuItem
                                                            className="cursor-pointer"
                                                            onClick={() => navigate({ to: '/admin/employees/$id/edit', params: { id: employee.id } })}
                                                        >
                                                            <UserPlus className="mr-2 h-4 w-4" />
                                                            Edit Data
                                                        </DropdownMenuItem>
                                                        <DropdownMenuSeparator />
                                                        <DropdownMenuItem
                                                            className="cursor-pointer"
                                                            onClick={() => logic.setEmployeeToReset({
                                                                id: employee.id,
                                                                name: employee.name,
                                                                email: employee.email
                                                            })}
                                                        >
                                                            <KeyRound className="mr-2 h-4 w-4" />
                                                            Reset Password
                                                        </DropdownMenuItem>
                                                        <DropdownMenuSeparator />
                                                        <DropdownMenuItem
                                                            className="text-destructive focus:text-destructive cursor-pointer"
                                                            onClick={() => logic.setEmployeeToDelete({ id: employee.id, name: employee.name })}
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
                        )}

                        {/* Pagination */}
                        {totalPages > 1 && (
                            <div className="flex items-center justify-between px-6 py-4 border-t">
                                <div className="text-sm text-muted-foreground">
                                    Menampilkan {((currentPage - 1) * perPage) + 1} - {Math.min(currentPage * perPage, totalEmployees)} dari {totalEmployees} karyawan
                                </div>
                                <div className="flex items-center gap-2">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() => handlePageChange(currentPage - 1)}
                                        disabled={currentPage === 1}
                                    >
                                        <ChevronLeft className="h-4 w-4 mr-1" />
                                        Sebelumnya
                                    </Button>
                                    <div className="flex items-center gap-1">
                                        {Array.from({ length: Math.min(5, totalPages) }, (_, i) => {
                                            let pageNum: number;
                                            if (totalPages <= 5) {
                                                pageNum = i + 1;
                                            } else if (currentPage <= 3) {
                                                pageNum = i + 1;
                                            } else if (currentPage >= totalPages - 2) {
                                                pageNum = totalPages - 4 + i;
                                            } else {
                                                pageNum = currentPage - 2 + i;
                                            }
                                            return (
                                                <Button
                                                    key={pageNum}
                                                    variant={currentPage === pageNum ? "default" : "outline"}
                                                    size="sm"
                                                    className="w-8 h-8 p-0"
                                                    onClick={() => handlePageChange(pageNum)}
                                                >
                                                    {pageNum}
                                                </Button>
                                            );
                                        })}
                                    </div>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() => handlePageChange(currentPage + 1)}
                                        disabled={currentPage === totalPages}
                                    >
                                        Selanjutnya
                                        <ChevronRight className="h-4 w-4 ml-1" />
                                    </Button>
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>

            {/* Delete Confirmation Dialog */}
            <AlertDialog open={!!logic.employeeToDelete} onOpenChange={(open) => !open && logic.setEmployeeToDelete(null)}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Hapus Karyawan?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Apakah Anda yakin ingin menghapus karyawan <strong>{logic.employeeToDelete?.name}</strong>?
                            Tindakan ini tidak dapat dibatalkan dan akan menghapus semua data terkait.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Batal</AlertDialogCancel>
                        <AlertDialogAction
                            onClick={() => logic.handleDelete()}
                            className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                            disabled={logic.deleteEmployeeMutation.isPending}
                        >
                            {logic.deleteEmployeeMutation.isPending ? 'Menghapus...' : 'Hapus'}
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>

            {/* Reset Password Dialog */}
            <Dialog open={!!logic.employeeToReset} onOpenChange={(open) => !open && logic.handleCloseResetDialog()}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <KeyRound className="h-5 w-5 text-primary" />
                            Reset Password
                        </DialogTitle>
                        <DialogDescription>
                            Reset password untuk karyawan <strong>{logic.employeeToReset?.name}</strong>
                            {logic.employeeToReset?.email && (
                                <span className="block text-xs text-muted-foreground mt-1">
                                    ({logic.employeeToReset.email})
                                </span>
                            )}
                        </DialogDescription>
                    </DialogHeader>

                    {logic.resetResult ? (
                        // Show result after reset
                        <div className="space-y-4 py-4">
                            <div className="rounded-lg bg-green-50 dark:bg-green-950/30 border border-green-200 dark:border-green-800 p-4">
                                <p className="text-sm text-green-800 dark:text-green-200 mb-3">
                                    Password berhasil direset! Berikut adalah password sementara:
                                </p>
                                <div className="flex items-center gap-2">
                                    <div className="flex-1 relative">
                                        <Input
                                            type={logic.showPassword ? 'text' : 'password'}
                                            value={logic.resetResult.temporary_password}
                                            readOnly
                                            className="pr-20 font-mono bg-white dark:bg-gray-900"
                                        />
                                        <div className="absolute right-2 top-1/2 -translate-y-1/2 flex gap-1">
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                className="h-7 w-7"
                                                onClick={() => logic.setShowPassword(!logic.showPassword)}
                                            >
                                                {logic.showPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                                            </Button>
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                className="h-7 w-7"
                                                onClick={logic.handleCopyPassword}
                                            >
                                                <Copy className="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div className="rounded-lg bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 p-4">
                                <p className="text-sm text-amber-800 dark:text-amber-200">
                                    ⚠️ <strong>Penting:</strong> User akan diminta untuk mengubah password saat login berikutnya.
                                    Pastikan Anda mencatat atau menyalin password ini sebelum menutup dialog.
                                </p>
                            </div>
                        </div>
                    ) : (
                        // Show form before reset
                        <div className="space-y-4 py-4">
                            <div className="space-y-2">
                                <Label htmlFor="custom-password">Password Baru (Opsional)</Label>
                                <div className="relative">
                                    <Input
                                        id="custom-password"
                                        type={logic.showPassword ? 'text' : 'password'}
                                        placeholder="Kosongkan untuk generate otomatis..."
                                        value={logic.customPassword}
                                        onChange={(e) => logic.setCustomPassword(e.target.value)}
                                        className="pr-10"
                                    />
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        className="absolute right-1 top-1/2 -translate-y-1/2 h-7 w-7"
                                        onClick={() => logic.setShowPassword(!logic.showPassword)}
                                    >
                                        {logic.showPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                                    </Button>
                                </div>
                                <p className="text-xs text-muted-foreground">
                                    Minimal 8 karakter. Jika dikosongkan, password akan di-generate secara otomatis.
                                </p>
                            </div>
                            <div className="rounded-lg bg-muted/50 p-4">
                                <p className="text-sm text-muted-foreground">
                                    Setelah reset, user harus mengubah password saat login berikutnya.
                                </p>
                            </div>
                        </div>
                    )}

                    <DialogFooter>
                        {logic.resetResult ? (
                            <Button onClick={logic.handleCloseResetDialog} className="w-full">
                                Selesai
                            </Button>
                        ) : (
                            <>
                                <Button variant="outline" onClick={logic.handleCloseResetDialog} disabled={logic.isResetting}>
                                    Batal
                                </Button>
                                <Button
                                    onClick={logic.handleResetPassword}
                                    disabled={logic.isResetting || (logic.customPassword.length > 0 && logic.customPassword.length < 8)}
                                >
                                    {logic.isResetting ? 'Mereset...' : 'Reset Password'}
                                </Button>
                            </>
                        )}
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Bulk Action Confirmation Dialog */}
            <AlertDialog open={bulkActionDialog.open} onOpenChange={(open) => !open && handleCloseBulkDialog()}>
                <AlertDialogContent className="max-w-lg">
                    <AlertDialogHeader>
                        <AlertDialogTitle>
                            {bulkActionDialog.action === 'delete' && 'Hapus Karyawan?'}
                            {bulkActionDialog.action === 'reset_password' && 'Reset Password Karyawan?'}
                            {bulkActionDialog.action === 'activate' && 'Aktifkan Karyawan?'}
                            {bulkActionDialog.action === 'deactivate' && 'Nonaktifkan Karyawan?'}
                        </AlertDialogTitle>
                        <AlertDialogDescription>
                            {bulkActionDialog.action === 'delete' && (
                                <>
                                    Apakah Anda yakin ingin menghapus <strong>{selectedEmployees.length}</strong> karyawan?
                                    Tindakan ini tidak dapat dibatalkan dan akan menghapus semua data terkait.
                                </>
                            )}
                            {bulkActionDialog.action === 'reset_password' && (
                                <>
                                    Apakah Anda yakin ingin mereset password untuk <strong>{selectedEmployees.length}</strong> karyawan?
                                    Password baru akan di-generate secara otomatis.
                                </>
                            )}
                            {bulkActionDialog.action === 'activate' && (
                                <>
                                    Apakah Anda yakin ingin mengaktifkan <strong>{selectedEmployees.length}</strong> karyawan?
                                </>
                            )}
                            {bulkActionDialog.action === 'deactivate' && (
                                <>
                                    Apakah Anda yakin ingin menonaktifkan <strong>{selectedEmployees.length}</strong> karyawan?
                                </>
                            )}
                        </AlertDialogDescription>
                    </AlertDialogHeader>

                    {/* Show bulk reset password results */}
                    {bulkResult?.reset_passwords && bulkResult.reset_passwords.length > 0 && (
                        <div className="max-h-60 overflow-y-auto space-y-2 my-4">
                            <p className="text-sm font-medium text-green-600 mb-2">
                                ✓ Password berhasil direset untuk {bulkResult.success} karyawan:
                            </p>
                            <div className="space-y-2">
                                {bulkResult.reset_passwords.map((item) => (
                                    <div key={item.employee_id} className="p-3 bg-muted/50 rounded-lg text-sm">
                                        <div className="flex justify-between items-center">
                                            <div>
                                                <span className="font-medium">{item.name}</span>
                                                <span className="text-muted-foreground ml-2">({item.email})</span>
                                            </div>
                                            <div className="flex items-center gap-2">
                                                <code className="bg-background px-2 py-1 rounded font-mono text-xs">
                                                    {item.temporary_password}
                                                </code>
                                                <Button
                                                    size="icon"
                                                    variant="ghost"
                                                    className="h-6 w-6"
                                                    onClick={() => {
                                                        navigator.clipboard.writeText(item.temporary_password);
                                                        toast.success('Password disalin');
                                                    }}
                                                >
                                                    <Copy className="h-3 w-3" />
                                                </Button>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                            <p className="text-xs text-amber-600 mt-3">
                                ⚠️ Simpan password ini! User harus mengubah password saat login berikutnya.
                            </p>
                        </div>
                    )}

                    <AlertDialogFooter>
                        {bulkResult?.success && bulkResult.success > 0 ? (
                            <AlertDialogAction onClick={handleCloseBulkDialog}>
                                Selesai
                            </AlertDialogAction>
                        ) : (
                            <>
                                <AlertDialogCancel disabled={isBulkProcessing}>Batal</AlertDialogCancel>
                                <AlertDialogAction
                                    onClick={(e) => {
                                        e.preventDefault();
                                        handleBulkAction();
                                    }}
                                    className={
                                        bulkActionDialog.action === 'delete'
                                            ? 'bg-destructive text-destructive-foreground hover:bg-destructive/90'
                                            : ''
                                    }
                                    disabled={isBulkProcessing}
                                >
                                    {isBulkProcessing ? 'Memproses...' : 'Ya, Lanjutkan'}
                                </AlertDialogAction>
                            </>
                        )}
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>

            {/* Excel Import Dialog */}
            <ExcelImportDialog
                open={isImportDialogOpen}
                onOpenChange={setIsImportDialogOpen}
                title="Import Data Karyawan"
                description="Upload file Excel untuk menambahkan data karyawan secara massal. Password sementara akan otomatis dibuat untuk setiap karyawan baru."
                templateName="template-import-karyawan.xlsx"
                expectedColumns={employeeImportColumns}
                onFileImport={handleFileImport}
                onSuccess={handleImportSuccess}
                maxRows={500}
            />
        </div>
    );
}
