import { useState, useEffect } from 'react';
import {
    Users,
    Plus,
    Search,
    MoreHorizontal,
    Edit,
    Trash2,
    Loader2,
    UserCheck,
    UserX,
    Lock,
    Unlock,
    Key,
    ChevronLeft,
    Filter,
} from 'lucide-react';
import { useNavigate } from '@tanstack/react-router';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
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
    DialogHeader,
    DialogTitle,
    DialogDescription,
} from '@/components/ui/dialog';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetDescription,
    SheetFooter,
} from '@/components/ui/sheet';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
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
import { Switch } from '@/components/ui/switch';
import { useUsers } from '@/hooks/use-users';
import {
    adminUserRoleLabels,
    adminUserRoleColors,
    type AdminUser,
    type AdminUserRole,
    type AdminUserFormData,
} from '@/types/user';
import { cn } from '@/lib/utils';

// Role badge component
function RoleBadge({ role }: { role: AdminUserRole }) {
    return (
        <Badge
            variant="outline"
            className="text-[10px] px-2 py-0.5 h-5"
            style={{
                borderColor: adminUserRoleColors[role],
                color: adminUserRoleColors[role],
                backgroundColor: `${adminUserRoleColors[role]}10`,
            }}
        >
            {adminUserRoleLabels[role]}
        </Badge>
    );
}

// User form dialog (reused but styled for mobile if needed, or just standard dialog)
function UserFormDialog({
    open,
    onOpenChange,
    user,
    onSubmit,
    isLoading,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    user?: AdminUser | null;
    onSubmit: (data: AdminUserFormData) => Promise<void>;
    isLoading: boolean;
}) {
    const [formData, setFormData] = useState<AdminUserFormData>({
        name: '',
        email: '',
        phone: '',
        role: 'employee',
        is_active: true,
        force_password_change: true,
    });

    useEffect(() => {
        if (user) {
            setFormData({
                name: user.name,
                email: user.email,
                phone: user.phone || '',
                role: user.role,
                is_active: user.is_active,
                force_password_change: user.force_password_change,
            });
        } else {
            setFormData({
                name: '',
                email: '',
                phone: '',
                role: 'employee',
                is_active: true,
                force_password_change: true,
            });
        }
    }, [user, open]);

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        await onSubmit(formData);
        onOpenChange(false);
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="w-[90%] rounded-2xl max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>{user ? 'Edit Pengguna' : 'Tambah Pengguna'}</DialogTitle>
                    <DialogDescription>
                        {user ? 'Perbarui data pengguna' : 'Buat akun pengguna baru'}
                    </DialogDescription>
                </DialogHeader>
                <form onSubmit={handleSubmit} className="space-y-4 py-2">
                    <div className="space-y-2">
                        <Label htmlFor="name">Nama Lengkap</Label>
                        <Input
                            id="name"
                            value={formData.name}
                            onChange={(e) => setFormData((prev) => ({ ...prev, name: e.target.value }))}
                            placeholder="Nama lengkap"
                            required
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="email">Email</Label>
                        <Input
                            id="email"
                            type="email"
                            value={formData.email}
                            onChange={(e) => setFormData((prev) => ({ ...prev, email: e.target.value }))}
                            placeholder="email@sekolah.id"
                            required
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="phone">Telepon</Label>
                        <Input
                            id="phone"
                            value={formData.phone}
                            onChange={(e) => setFormData((prev) => ({ ...prev, phone: e.target.value }))}
                            placeholder="08xxxxxxxxxx"
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="role">Role</Label>
                        <Select
                            value={formData.role}
                            onValueChange={(value: AdminUserRole) =>
                                setFormData((prev) => ({ ...prev, role: value }))
                            }
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Pilih role" />
                            </SelectTrigger>
                            <SelectContent>
                                {Object.entries(adminUserRoleLabels).map(([value, label]) => (
                                    <SelectItem key={value} value={value}>
                                        {label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                    {!user && (
                        <div className="space-y-2">
                            <Label htmlFor="password">Password</Label>
                            <Input
                                id="password"
                                type="password"
                                value={formData.password || ''}
                                onChange={(e) =>
                                    setFormData((prev) => ({ ...prev, password: e.target.value }))
                                }
                                placeholder="Password"
                                required={!user}
                            />
                        </div>
                    )}
                    <div className="flex items-center justify-between py-2">
                        <Label htmlFor="is_active" className="text-sm">Status Aktif</Label>
                        <Switch
                            id="is_active"
                            checked={formData.is_active}
                            onCheckedChange={(checked) =>
                                setFormData((prev) => ({ ...prev, is_active: checked }))
                            }
                        />
                    </div>
                    <div className="flex items-center justify-between py-2">
                        <Label htmlFor="force_password" className="text-sm">Wajib Ganti Password</Label>
                        <Switch
                            id="force_password"
                            checked={formData.force_password_change}
                            onCheckedChange={(checked) =>
                                setFormData((prev) => ({ ...prev, force_password_change: checked }))
                            }
                        />
                    </div>
                    <div className="pt-4 flex gap-2 justify-end">
                        <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>
                            Batal
                        </Button>
                        <Button type="submit" disabled={isLoading}>
                            {isLoading && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                            Simpan
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export function MobileUsersPage() {
    const navigate = useNavigate();
    const {
        isLoading,
        users,
        fetchUsers,
        createUser,
        updateUser,
        deleteUser,
        toggleUserStatus,
        toggleLock,
        resetPassword,
    } = useUsers();

    const [searchQuery, setSearchQuery] = useState('');
    const [roleFilter, setRoleFilter] = useState<AdminUserRole | 'all'>('all');
    const [statusFilter, setStatusFilter] = useState<'all' | 'active' | 'inactive' | 'locked'>('all');
    const [isFormOpen, setIsFormOpen] = useState(false);
    const [editingUser, setEditingUser] = useState<AdminUser | null>(null);
    const [deletingUser, setDeletingUser] = useState<AdminUser | null>(null);
    const [isFilterOpen, setIsFilterOpen] = useState(false);

    useEffect(() => {
        fetchUsers();
    }, [fetchUsers]);

    const handleSearch = () => {
        fetchUsers({
            role: roleFilter !== 'all' ? roleFilter : undefined,
            is_active: statusFilter === 'active' ? true : statusFilter === 'inactive' ? false : undefined,
            search: searchQuery || undefined,
        });
    };

    useEffect(() => {
        handleSearch();
    }, [roleFilter, statusFilter]);

    const handleCreate = async (data: AdminUserFormData) => {
        await createUser(data);
    };

    const handleUpdate = async (data: AdminUserFormData) => {
        if (editingUser) {
            await updateUser(editingUser.id, data);
            setEditingUser(null);
        }
    };

    const handleDelete = async () => {
        if (deletingUser) {
            await deleteUser(deletingUser.id);
            setDeletingUser(null);
        }
    };

    const filteredUsers = users.filter((user) => {
        if (statusFilter === 'locked' && !user.account_locked) return false;
        return true;
    });

    const activeFiltersCount = (roleFilter !== 'all' ? 1 : 0) + (statusFilter !== 'all' ? 1 : 0);

    return (
        <div className="min-h-screen bg-gradient-to-b from-muted/30 via-background to-background dark:from-gray-950 dark:via-gray-900 dark:to-gray-900 pb-24">
            {/* Header Wrapper */}
            <div className="px-4 pt-3 pb-3 sticky top-0 z-20">
                <div className="bg-card/80 dark:bg-card/60 backdrop-blur-md rounded-3xl p-1.5 shadow-xl border border-border/40 dark:border-border/30">
                    <div className="bg-gradient-to-r from-violet-600 to-indigo-600 px-4 py-3 rounded-[20px] flex items-center gap-3 shadow-lg">
                        <button
                            onClick={() => navigate({ to: '/admin/dashboard' })}
                            className="p-2 -ml-2 hover:bg-white/10 rounded-full transition-colors active:scale-95"
                        >
                            <ChevronLeft className="h-5 w-5 text-white" />
                        </button>
                        <h1 className="text-base font-bold text-white flex-1">Manajemen Pengguna</h1>
                        <button
                            onClick={() => setIsFilterOpen(true)}
                            className="relative p-2 hover:bg-white/10 rounded-full transition-colors active:scale-95"
                        >
                            <Filter className="h-5 w-5 text-white" />
                            {activeFiltersCount > 0 && (
                                <span className="absolute top-1.5 right-1.5 h-2.5 w-2.5 bg-red-500 rounded-full border-2 border-indigo-600" />
                            )}
                        </button>
                    </div>
                </div>
            </div>

            {/* Search Bar */}
            <div className="px-4 mb-4">
                <div className="relative">
                    <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        placeholder="Cari pengguna..."
                        value={searchQuery}
                        onChange={(e) => setSearchQuery(e.target.value)}
                        onKeyDown={(e) => e.key === 'Enter' && handleSearch()}
                        className="pl-9 bg-white dark:bg-gray-900/50 rounded-2xl border-border/50 shadow-sm"
                    />
                </div>
            </div>

            {/* Users List */}
            <div className="px-4 space-y-3">
                {isLoading ? (
                    <div className="flex flex-col items-center justify-center py-12 gap-3">
                        <Loader2 className="h-8 w-8 animate-spin text-primary" />
                        <p className="text-sm text-muted-foreground">Memuat data...</p>
                    </div>
                ) : filteredUsers.length === 0 ? (
                    <div className="flex flex-col items-center justify-center py-12 gap-3 text-center">
                        <div className="h-16 w-16 rounded-full bg-muted flex items-center justify-center">
                            <Users className="h-8 w-8 text-muted-foreground" />
                        </div>
                        <div>
                            <p className="font-medium">Tidak ada pengguna</p>
                            <p className="text-sm text-muted-foreground">Coba ubah filter pencarian</p>
                        </div>
                    </div>
                ) : (
                    filteredUsers.map((user) => (
                        <div
                            key={user.id}
                            className="bg-white dark:bg-gray-900 rounded-2xl p-4 shadow-sm border border-border/50 flex flex-col gap-3"
                        >
                            <div className="flex items-start justify-between gap-3">
                                <div className="flex items-center gap-3">
                                    <div className="h-10 w-10 rounded-full bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center text-white font-bold text-sm shadow-sm">
                                        {user.name.charAt(0).toUpperCase()}
                                    </div>
                                    <div>
                                        <h3 className="font-semibold text-sm line-clamp-1">{user.name}</h3>
                                        <p className="text-xs text-muted-foreground line-clamp-1">{user.email}</p>
                                    </div>
                                </div>
                                <RoleBadge role={user.role} />
                            </div>

                            <div className="flex items-center justify-between text-xs text-muted-foreground border-t border-border/50 pt-3 mt-1">
                                <div className="flex items-center gap-3">
                                    <div className={cn("flex items-center gap-1", user.is_active ? "text-emerald-600" : "text-muted-foreground")}>
                                        {user.is_active ? <UserCheck className="h-3 w-3" /> : <UserX className="h-3 w-3" />}
                                        <span>{user.is_active ? "Aktif" : "Nonaktif"}</span>
                                    </div>
                                    {user.account_locked && (
                                        <div className="flex items-center gap-1 text-red-600">
                                            <Lock className="h-3 w-3" />
                                            <span>Terkunci</span>
                                        </div>
                                    )}
                                </div>

                                <DropdownMenu>
                                    <DropdownMenuTrigger asChild>
                                        <Button variant="ghost" size="sm" className="h-8 w-8 p-0 rounded-full">
                                            <MoreHorizontal className="h-4 w-4" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end" className="w-48">
                                        <DropdownMenuItem
                                            onClick={() => {
                                                setEditingUser(user);
                                                setIsFormOpen(true);
                                            }}
                                        >
                                            <Edit className="mr-2 h-4 w-4" />
                                            Edit
                                        </DropdownMenuItem>
                                        <DropdownMenuItem onClick={() => toggleUserStatus(user.id)}>
                                            {user.is_active ? (
                                                <>
                                                    <UserX className="mr-2 h-4 w-4" />
                                                    Nonaktifkan
                                                </>
                                            ) : (
                                                <>
                                                    <UserCheck className="mr-2 h-4 w-4" />
                                                    Aktifkan
                                                </>
                                            )}
                                        </DropdownMenuItem>
                                        <DropdownMenuItem onClick={() => toggleLock(user.id)}>
                                            {user.account_locked ? (
                                                <>
                                                    <Unlock className="mr-2 h-4 w-4" />
                                                    Buka Kunci
                                                </>
                                            ) : (
                                                <>
                                                    <Lock className="mr-2 h-4 w-4" />
                                                    Kunci Akun
                                                </>
                                            )}
                                        </DropdownMenuItem>
                                        <DropdownMenuItem onClick={() => resetPassword(user.id)}>
                                            <Key className="mr-2 h-4 w-4" />
                                            Reset Password
                                        </DropdownMenuItem>
                                        <DropdownMenuSeparator />
                                        <DropdownMenuItem
                                            className="text-destructive"
                                            onClick={() => setDeletingUser(user)}
                                        >
                                            <Trash2 className="mr-2 h-4 w-4" />
                                            Hapus
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>
                        </div>
                    ))
                )}
            </div>

            {/* FAB Add User */}
            <Button
                onClick={() => {
                    setEditingUser(null);
                    setIsFormOpen(true);
                }}
                className="fixed bottom-6 right-6 h-14 w-14 rounded-full shadow-xl bg-violet-600 hover:bg-violet-700 text-white z-50"
            >
                <Plus className="h-6 w-6" />
            </Button>

            {/* Filter Sheet */}
            <Sheet open={isFilterOpen} onOpenChange={setIsFilterOpen}>
                <SheetContent side="bottom" className="rounded-t-[20px]">
                    <SheetHeader>
                        <SheetTitle>Filter Pengguna</SheetTitle>
                        <SheetDescription>
                            Tampilkan pengguna berdasarkan kriteria tertentu
                        </SheetDescription>
                    </SheetHeader>
                    <div className="py-6 space-y-6">
                        <div className="space-y-2">
                            <Label>Role</Label>
                            <Select
                                value={roleFilter}
                                onValueChange={(value) => setRoleFilter(value as AdminUserRole | 'all')}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Semua Role" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Semua Role</SelectItem>
                                    {Object.entries(adminUserRoleLabels).map(([value, label]) => (
                                        <SelectItem key={value} value={value}>
                                            {label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="space-y-2">
                            <Label>Status</Label>
                            <Select
                                value={statusFilter}
                                onValueChange={(value) =>
                                    setStatusFilter(value as 'all' | 'active' | 'inactive' | 'locked')
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Semua Status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Semua Status</SelectItem>
                                    <SelectItem value="active">Aktif</SelectItem>
                                    <SelectItem value="inactive">Nonaktif</SelectItem>
                                    <SelectItem value="locked">Terkunci</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                    <SheetFooter>
                        <Button onClick={() => setIsFilterOpen(false)} className="w-full">
                            Terapkan Filter
                        </Button>
                    </SheetFooter>
                </SheetContent>
            </Sheet>

            {/* User Form Dialog */}
            <UserFormDialog
                open={isFormOpen}
                onOpenChange={(open) => {
                    setIsFormOpen(open);
                    if (!open) setEditingUser(null);
                }}
                user={editingUser}
                onSubmit={editingUser ? handleUpdate : handleCreate}
                isLoading={isLoading}
            />

            {/* Delete Confirmation */}
            <AlertDialog open={!!deletingUser} onOpenChange={() => setDeletingUser(null)}>
                <AlertDialogContent className="w-[90%] rounded-2xl">
                    <AlertDialogHeader>
                        <AlertDialogTitle>Hapus Pengguna?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Yakin ingin menghapus <strong>{deletingUser?.name}</strong>?
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter className="flex-row gap-2 justify-end">
                        <AlertDialogCancel className="mt-0">Batal</AlertDialogCancel>
                        <AlertDialogAction
                            onClick={handleDelete}
                            className="bg-destructive hover:bg-destructive/90"
                        >
                            Hapus
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </div>
    );
}
