import { useState } from 'react';
import { useNavigate } from '@tanstack/react-router';
import { motion, AnimatePresence } from 'framer-motion';
import {
    ChevronLeft,
    Search,
    Plus,
    User,
    Phone,
    Building2,
    Filter,
    MoreVertical,
    Edit,
    Trash2,
    Eye,
    KeyRound,
    Copy,
    EyeOff,
} from 'lucide-react';
import { useEmployees, useDeleteEmployee } from '@/hooks/use-employees';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { LoadingState } from '@/components/states';
import {
    Drawer,
    DrawerContent,
    DrawerHeader,
    DrawerTitle,
    DrawerDescription,
    DrawerFooter,
    DrawerClose,
} from "@/components/ui/drawer";
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from "@/components/ui/alert-dialog";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog";
import { resetEmployeePassword, type ResetPasswordResponse } from '@/lib/api/employees';
import { toast } from 'sonner';


export function MobileEmployeesPage() {
    const navigate = useNavigate();
    const [searchQuery, setSearchQuery] = useState('');
    const [selectedEmployee, setSelectedEmployee] = useState<{ id: string; name: string; email?: string } | null>(null);
    const [employeeToDelete, setEmployeeToDelete] = useState<{ id: string; name: string } | null>(null);
    const [isDrawerOpen, setIsDrawerOpen] = useState(false);

    // Reset password states
    const [employeeToReset, setEmployeeToReset] = useState<{ id: string; name: string; email?: string } | null>(null);
    const [isResetting, setIsResetting] = useState(false);
    const [resetResult, setResetResult] = useState<ResetPasswordResponse | null>(null);
    const [showPassword, setShowPassword] = useState(false);
    const [customPassword, setCustomPassword] = useState('');

    const deleteEmployeeMutation = useDeleteEmployee();

    const handleDelete = async () => {
        if (!employeeToDelete) return;
        try {
            await deleteEmployeeMutation.mutateAsync(employeeToDelete.id);
            setEmployeeToDelete(null);
            setIsDrawerOpen(false);
        } catch (error) {
            console.error('Failed to delete employee:', error);
        }
    };

    const handleResetPassword = async () => {
        if (!employeeToReset) return;

        setIsResetting(true);
        try {
            const result = await resetEmployeePassword(
                employeeToReset.id,
                customPassword || undefined
            );
            setResetResult(result);
            toast.success('Password berhasil direset!');
        } catch (error) {
            console.error('Failed to reset password:', error);
            toast.error('Gagal mereset password');
        } finally {
            setIsResetting(false);
        }
    };

    const handleCopyPassword = () => {
        if (resetResult?.temporary_password) {
            navigator.clipboard.writeText(resetResult.temporary_password);
            toast.success('Password disalin');
        }
    };

    const handleCloseResetDialog = () => {
        setEmployeeToReset(null);
        setResetResult(null);
        setCustomPassword('');
        setShowPassword(false);
    };

    const openOptions = (e: React.MouseEvent, employee: { id: string; name: string; email?: string }) => {
        e.stopPropagation();
        setSelectedEmployee(employee);
        setIsDrawerOpen(true);
    };

    const { data: employeesData, isLoading } = useEmployees({
        search: searchQuery,
        per_page: 20,
    });

    const employees = employeesData?.data || [];

    const getInitials = (name: string) => {
        if (!name) return 'U';
        return name
            .split(' ')
            .map((n) => n[0])
            .join('')
            .toUpperCase()
            .slice(0, 2);
    };

    const getStatusColor = (status: string) => {
        switch (status) {
            case 'active':
                return 'bg-emerald-100 text-emerald-700 border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-400';
            case 'inactive':
                return 'bg-red-100 text-red-700 border-red-200 dark:bg-red-900/30 dark:text-red-400';
            case 'on_leave':
                return 'bg-amber-100 text-amber-700 border-amber-200 dark:bg-amber-900/30 dark:text-amber-400';
            default:
                return 'bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-900/30 dark:text-gray-400';
        }
    };

    return (
        <div className="min-h-screen bg-gradient-to-b from-muted/30 via-background to-background dark:from-gray-950 dark:via-gray-900 dark:to-gray-900 pb-20 relative">
            {/* Header */}
            <div className="px-4 pt-3 pb-3 sticky top-0 z-20">
                <div className="bg-card/80 dark:bg-card/60 backdrop-blur-md rounded-3xl p-1.5 shadow-xl border border-border/40 dark:border-border/30">
                    <div className="bg-gradient-to-r from-blue-600 to-cyan-600 dark:from-blue-900 dark:to-cyan-800 px-4 py-3 rounded-[20px] flex items-center gap-3 shadow-lg">
                        <button
                            onClick={() => navigate({ to: '/admin/dashboard' })}
                            className="p-2 -ml-2 hover:bg-white/10 rounded-full transition-colors active:scale-95"
                            title="Kembali"
                        >
                            <ChevronLeft className="h-5 w-5 text-white" />
                        </button>
                        <h1 className="text-base font-bold text-white flex-1">Karyawan</h1>
                        <button className="p-2 hover:bg-white/10 rounded-full transition-colors active:scale-95" title="Filter">
                            <Filter className="h-5 w-5 text-white" />
                        </button>
                    </div>
                </div>
            </div>

            {/* Search Bar */}
            <div className="px-4 mt-2 mb-4">
                <div className="relative">
                    <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                    <Input
                        placeholder="Cari karyawan..."
                        value={searchQuery}
                        onChange={(e) => setSearchQuery(e.target.value)}
                        className="pl-9 bg-white dark:bg-gray-900 border-border/50 rounded-2xl shadow-sm h-11 focus-visible:ring-primary"
                    />
                </div>
            </div>

            {/* Employee List */}
            <div className="px-4 space-y-3">
                {isLoading ? (
                    <div className="flex justify-center py-12">
                        <LoadingState message="Memuat data karyawan..." size="sm" />
                    </div>
                ) : employees.length > 0 ? (
                    <AnimatePresence>
                        {employees.map((employee, index) => (
                            <motion.div
                                key={employee.id}
                                initial={{ opacity: 0, y: 20 }}
                                animate={{ opacity: 1, y: 0 }}
                                exit={{ opacity: 0, scale: 0.95 }}
                                transition={{ delay: index * 0.05 }}
                                onClick={() => navigate({ to: `/admin/employees/${employee.id}` })}
                                className="bg-white dark:bg-gray-900 rounded-2xl p-4 shadow-sm border border-border/50 active:scale-[0.99] transition-transform cursor-pointer relative"
                            >
                                <div className="flex items-start gap-3">
                                    <Avatar className="h-12 w-12 border border-border/50">
                                        <AvatarImage src={employee.avatar || undefined} alt={employee.name} />
                                        <AvatarFallback className="bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400 font-medium">
                                            {getInitials(employee.name)}
                                        </AvatarFallback>
                                    </Avatar>
                                    <div className="flex-1 min-w-0">
                                        <div className="flex items-start justify-between gap-2">
                                            <div className="min-w-0">
                                                <h3 className="font-bold text-foreground truncate">{employee.name}</h3>
                                                <p className="text-xs text-muted-foreground">{employee.position}</p>
                                            </div>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                className="h-8 w-8 -mt-1 -mr-2 text-muted-foreground hover:text-foreground shrink-0"
                                                onClick={(e) => openOptions(e, { id: employee.id, name: employee.name, email: employee.email })}
                                                title="Opsi"
                                            >
                                                <MoreVertical className="h-4 w-4" />
                                            </Button>
                                        </div>

                                        <div className="mt-2 flex items-center justify-between gap-2">
                                            <div className="flex items-center gap-3 text-xs text-muted-foreground min-w-0">
                                                <div className="flex items-center gap-1 min-w-0">
                                                    <Building2 className="h-3 w-3 shrink-0" />
                                                    <span className="truncate">{employee.department}</span>
                                                </div>
                                                {employee.phone && (
                                                    <div className="flex items-center gap-1 shrink-0">
                                                        <Phone className="h-3 w-3" />
                                                        <span>{employee.phone}</span>
                                                    </div>
                                                )}
                                            </div>
                                            <Badge variant="secondary" className={`${getStatusColor(employee.status)} shrink-0 border`}>
                                                {employee.status === 'on_leave' ? 'Cuti' : employee.status}
                                            </Badge>
                                        </div>
                                    </div>
                                </div>
                            </motion.div>
                        ))}
                    </AnimatePresence>
                ) : (
                    <div className="text-center py-12 text-muted-foreground">
                        <User className="h-12 w-12 mx-auto mb-3 opacity-20" />
                        <p>Tidak ada karyawan ditemukan</p>
                    </div>
                )}
            </div>

            {/* Options Drawer */}
            <Drawer open={isDrawerOpen} onOpenChange={setIsDrawerOpen}>
                <DrawerContent>
                    <DrawerHeader>
                        <DrawerTitle>Pilihan Aksi</DrawerTitle>
                        <DrawerDescription>
                            Tindakan untuk {selectedEmployee?.name}
                        </DrawerDescription>
                    </DrawerHeader>
                    <div className="p-4 space-y-2">
                        <Button
                            variant="outline"
                            className="w-full justify-start h-12 text-base"
                            onClick={() => {
                                if (selectedEmployee) {
                                    navigate({ to: `/admin/employees/${selectedEmployee.id}` });
                                    setIsDrawerOpen(false);
                                }
                            }}
                        >
                            <Eye className="mr-3 h-5 w-5 text-blue-500" />
                            Lihat Detail
                        </Button>
                        <Button
                            variant="outline"
                            className="w-full justify-start h-12 text-base"
                            onClick={() => {
                                if (selectedEmployee) {
                                    navigate({ to: `/admin/employees/${selectedEmployee.id}/edit` });
                                    setIsDrawerOpen(false);
                                }
                            }}
                        >
                            <Edit className="mr-3 h-5 w-5 text-amber-500" />
                            Edit Data
                        </Button>
                        <Button
                            variant="outline"
                            className="w-full justify-start h-12 text-base"
                            onClick={() => {
                                if (selectedEmployee) {
                                    setEmployeeToReset(selectedEmployee);
                                    setIsDrawerOpen(false);
                                }
                            }}
                        >
                            <KeyRound className="mr-3 h-5 w-5 text-purple-500" />
                            Reset Password
                        </Button>
                        <Button
                            variant="outline"
                            className="w-full justify-start h-12 text-base text-destructive hover:text-destructive hover:bg-destructive/10 border-destructive/20"
                            onClick={() => {
                                if (selectedEmployee) {
                                    setEmployeeToDelete(selectedEmployee);
                                    setIsDrawerOpen(false);
                                }
                            }}
                        >
                            <Trash2 className="mr-3 h-5 w-5" />
                            Hapus Karyawan
                        </Button>
                    </div>
                    <DrawerFooter>
                        <DrawerClose asChild>
                            <Button variant="ghost">Batal</Button>
                        </DrawerClose>
                    </DrawerFooter>
                </DrawerContent>
            </Drawer>

            {/* Delete Confirmation */}
            <AlertDialog open={!!employeeToDelete} onOpenChange={(open) => !open && setEmployeeToDelete(null)}>
                <AlertDialogContent className="max-w-[90vw] rounded-2xl">
                    <AlertDialogHeader>
                        <AlertDialogTitle>Hapus Karyawan?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Apakah Anda yakin ingin menghapus karyawan <strong>{employeeToDelete?.name}</strong>?
                            Tindakan ini tidak dapat dibatalkan.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Batal</AlertDialogCancel>
                        <AlertDialogAction
                            onClick={handleDelete}
                            className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                            disabled={deleteEmployeeMutation.isPending}
                        >
                            {deleteEmployeeMutation.isPending ? 'Menghapus...' : 'Hapus'}
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>

            {/* Reset Password Dialog */}
            <Dialog open={!!employeeToReset} onOpenChange={(open) => !open && handleCloseResetDialog()}>
                <DialogContent className="max-w-[90vw] rounded-2xl">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <KeyRound className="h-5 w-5 text-primary" />
                            Reset Password
                        </DialogTitle>
                        <DialogDescription>
                            Reset password untuk <strong>{employeeToReset?.name}</strong>
                        </DialogDescription>
                    </DialogHeader>

                    {resetResult ? (
                        // Show result after reset
                        <div className="space-y-4 py-4">
                            <div className="rounded-lg bg-green-50 dark:bg-green-950/30 border border-green-200 dark:border-green-800 p-4">
                                <p className="text-sm text-green-800 dark:text-green-200 mb-3">
                                    Password berhasil direset!
                                </p>
                                <div className="flex items-center gap-2">
                                    <div className="flex-1 relative">
                                        <Input
                                            type={showPassword ? 'text' : 'password'}
                                            value={resetResult.temporary_password}
                                            readOnly
                                            className="pr-20 font-mono text-sm"
                                        />
                                        <div className="absolute right-2 top-1/2 -translate-y-1/2 flex gap-1">
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                className="h-7 w-7"
                                                onClick={() => setShowPassword(!showPassword)}
                                            >
                                                {showPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                                            </Button>
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="icon"
                                                className="h-7 w-7"
                                                onClick={handleCopyPassword}
                                            >
                                                <Copy className="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <p className="text-xs text-muted-foreground">
                                ⚠️ User harus mengubah password saat login berikutnya.
                            </p>
                        </div>
                    ) : (
                        // Show form before reset
                        <div className="space-y-4 py-4">
                            <div className="space-y-2">
                                <Label htmlFor="custom-password-mobile">Password Baru (Opsional)</Label>
                                <div className="relative">
                                    <Input
                                        id="custom-password-mobile"
                                        type={showPassword ? 'text' : 'password'}
                                        placeholder="Kosongkan untuk otomatis..."
                                        value={customPassword}
                                        onChange={(e) => setCustomPassword(e.target.value)}
                                        className="pr-10"
                                    />
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        className="absolute right-1 top-1/2 -translate-y-1/2 h-7 w-7"
                                        onClick={() => setShowPassword(!showPassword)}
                                    >
                                        {showPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                                    </Button>
                                </div>
                                <p className="text-xs text-muted-foreground">
                                    Minimal 8 karakter.
                                </p>
                            </div>
                        </div>
                    )}

                    <DialogFooter className="flex-col gap-2 sm:flex-row">
                        {resetResult ? (
                            <Button onClick={handleCloseResetDialog} className="w-full">
                                Selesai
                            </Button>
                        ) : (
                            <>
                                <Button variant="outline" onClick={handleCloseResetDialog} disabled={isResetting} className="w-full sm:w-auto">
                                    Batal
                                </Button>
                                <Button
                                    onClick={handleResetPassword}
                                    disabled={isResetting || (customPassword.length > 0 && customPassword.length < 8)}
                                    className="w-full sm:w-auto"
                                >
                                    {isResetting ? 'Mereset...' : 'Reset Password'}
                                </Button>
                            </>
                        )}
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* FAB */}
            <motion.div
                initial={{ scale: 0 }}
                animate={{ scale: 1 }}
                transition={{ delay: 0.5, type: 'spring' }}
                className="fixed bottom-6 right-6 z-30"
            >
                <Button
                    onClick={() => navigate({ to: '/admin/employees/create' })}
                    className="h-14 w-14 rounded-full shadow-xl bg-blue-600 hover:bg-blue-700 text-white p-0"
                >
                    <Plus className="h-6 w-6" />
                </Button>
            </motion.div>
        </div>
    );
}
