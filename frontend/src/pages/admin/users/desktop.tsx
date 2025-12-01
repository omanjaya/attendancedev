import { useState, useEffect } from 'react';
import {
    Users,
    Plus,
    Search,
    MoreHorizontal,
    Edit,
    Trash2,
    Shield,
    ShieldOff,
    Key,
    Lock,
    Unlock,
    UserCheck,
    UserX,
    Loader2,
    Mail,
    Phone,
    AlertCircle,
    Clock,
} from 'lucide-react';
import { PageHeader, StatsGrid, type StatItem } from '@/components/shared';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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
    DialogFooter,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
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
    type AdminUserStatistics,
} from '@/types/user';

// Role badge component
function RoleBadge({ role }: { role: AdminUserRole }) {
    return (
        <Badge
            variant="outline"
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

// Status indicator
function StatusIndicator({ user }: { user: AdminUser }) {
    if (user.account_locked) {
        return (
            <div className="flex items-center gap-1 text-destructive">
                <Lock className="h-3 w-3" />
                <span className="text-xs">Terkunci</span>
            </div>
        );
    }
    if (!user.is_active) {
        return (
            <div className="flex items-center gap-1 text-muted-foreground">
                <UserX className="h-3 w-3" />
                <span className="text-xs">Nonaktif</span>
            </div>
        );
    }
    return (
        <div className="flex items-center gap-1 text-success">
            <UserCheck className="h-3 w-3" />
            <span className="text-xs">Aktif</span>
        </div>
    );
}

// User form dialog
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
            <DialogContent className="sm:max-w-[500px]">
                <DialogHeader>
                    <DialogTitle>{user ? 'Edit Pengguna' : 'Tambah Pengguna Baru'}</DialogTitle>
                    <DialogDescription>
                        {user
                            ? 'Edit informasi pengguna yang ada'
                            : 'Tambahkan pengguna baru ke sistem'}
                    </DialogDescription>
                </DialogHeader>
                <form onSubmit={handleSubmit}>
                    <div className="grid gap-4 py-4">
                        <div className="grid gap-2">
                            <Label htmlFor="name">Nama Lengkap</Label>
                            <Input
                                id="name"
                                value={formData.name}
                                onChange={(e) => setFormData((prev) => ({ ...prev, name: e.target.value }))}
                                placeholder="Masukkan nama lengkap"
                                required
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="email">Email</Label>
                            <Input
                                id="email"
                                type="email"
                                value={formData.email}
                                onChange={(e) => setFormData((prev) => ({ ...prev, email: e.target.value }))}
                                placeholder="nama@sekolah.id"
                                required
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="phone">Nomor Telepon</Label>
                            <Input
                                id="phone"
                                value={formData.phone}
                                onChange={(e) => setFormData((prev) => ({ ...prev, phone: e.target.value }))}
                                placeholder="08xxxxxxxxxx"
                            />
                        </div>
                        <div className="grid gap-2">
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
                            <div className="grid gap-2">
                                <Label htmlFor="password">Password</Label>
                                <Input
                                    id="password"
                                    type="password"
                                    value={formData.password || ''}
                                    onChange={(e) =>
                                        setFormData((prev) => ({ ...prev, password: e.target.value }))
                                    }
                                    placeholder="Masukkan password"
                                    required={!user}
                                />
                            </div>
                        )}
                        <div className="flex items-center justify-between">
                            <Label htmlFor="is_active">Status Aktif</Label>
                            <Switch
                                id="is_active"
                                checked={formData.is_active}
                                onCheckedChange={(checked) =>
                                    setFormData((prev) => ({ ...prev, is_active: checked }))
                                }
                            />
                        </div>
                        <div className="flex items-center justify-between">
                            <Label htmlFor="force_password">Wajib Ganti Password</Label>
                            <Switch
                                id="force_password"
                                checked={formData.force_password_change}
                                onCheckedChange={(checked) =>
                                    setFormData((prev) => ({ ...prev, force_password_change: checked }))
                                }
                            />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>
                            Batal
                        </Button>
                        <Button type="submit" disabled={isLoading}>
                            {isLoading && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                            {user ? 'Simpan' : 'Tambah'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export function DesktopUsersPage() {
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
        getStatistics,
    } = useUsers();

    const [searchQuery, setSearchQuery] = useState('');
    const [roleFilter, setRoleFilter] = useState<AdminUserRole | 'all'>('all');
    const [statusFilter, setStatusFilter] = useState<'all' | 'active' | 'inactive' | 'locked'>('all');
    const [isFormOpen, setIsFormOpen] = useState(false);
    const [editingUser, setEditingUser] = useState<AdminUser | null>(null);
    const [deletingUser, setDeletingUser] = useState<AdminUser | null>(null);
    const [stats, setStats] = useState<AdminUserStatistics | null>(null);

    useEffect(() => {
        fetchUsers();
        loadStats();
    }, [fetchUsers]);

    const loadStats = async () => {
        const statistics = await getStatistics();
        setStats(statistics);
    };

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
        loadStats();
    };

    const handleUpdate = async (data: AdminUserFormData) => {
        if (editingUser) {
            await updateUser(editingUser.id, data);
            setEditingUser(null);
            loadStats();
        }
    };

    const handleDelete = async () => {
        if (deletingUser) {
            await deleteUser(deletingUser.id);
            setDeletingUser(null);
            loadStats();
        }
    };

    const filteredUsers = users.filter((user) => {
        if (statusFilter === 'locked' && !user.account_locked) return false;
        return true;
    });

    const formatDate = (date?: string) => {
        if (!date) return '-';
        return new Date(date).toLocaleDateString('id-ID', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    const statsItems: StatItem[] = stats ? [
        {
            label: 'Total',
            value: stats.total_users,
            icon: Users,
            color: 'primary',
        },
        {
            label: 'Aktif',
            value: stats.active_users,
            icon: UserCheck,
            color: 'success',
        },
        {
            label: '2FA Aktif',
            value: stats.users_with_2fa,
            icon: Shield,
            color: 'warning',
        },
        {
            label: 'Terkunci',
            value: stats.locked_users,
            icon: Lock,
            color: 'destructive',
        },
    ] : [];

    return (
        <div className="space-y-4 p-4 sm:space-y-6 sm:p-6">
            {/* Page Header */}
            <PageHeader
                title="Manajemen Pengguna"
                description="Kelola pengguna sistem dan hak akses"
                icon={Users}
                actions={
                    <Button
                        onClick={() => {
                            setEditingUser(null);
                            setIsFormOpen(true);
                        }}
                    >
                        <Plus className="mr-2 h-4 w-4" />
                        Tambah Pengguna
                    </Button>
                }
            />

            {/* Stats */}
            {stats && <StatsGrid stats={statsItems} columns={4} variant="cards" />}

            {/* Filters */}
            <Card>
                <CardContent className="p-4">
                    <div className="flex flex-col gap-4 md:flex-row md:items-center">
                        <div className="flex flex-1 gap-2">
                            <div className="relative flex-1">
                                <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    placeholder="Cari nama atau email..."
                                    value={searchQuery}
                                    onChange={(e) => setSearchQuery(e.target.value)}
                                    onKeyDown={(e) => e.key === 'Enter' && handleSearch()}
                                    className="pl-9"
                                />
                            </div>
                            <Button variant="outline" onClick={handleSearch}>
                                Cari
                            </Button>
                        </div>
                        <div className="flex gap-2">
                            <Select
                                value={roleFilter}
                                onValueChange={(value) => setRoleFilter(value as AdminUserRole | 'all')}
                            >
                                <SelectTrigger className="w-[150px]">
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
                            <Select
                                value={statusFilter}
                                onValueChange={(value) =>
                                    setStatusFilter(value as 'all' | 'active' | 'inactive' | 'locked')
                                }
                            >
                                <SelectTrigger className="w-[150px]">
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
                </CardContent>
            </Card>

            {/* Users Table */}
            <Card>
                <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                        <Users className="h-5 w-5" />
                        Daftar Pengguna ({filteredUsers.length})
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    {isLoading ? (
                        <div className="flex items-center justify-center py-8">
                            <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
                        </div>
                    ) : filteredUsers.length === 0 ? (
                        <div className="py-8 text-center text-muted-foreground">
                            Tidak ada pengguna ditemukan
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Pengguna</TableHead>
                                        <TableHead>Role</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Keamanan</TableHead>
                                        <TableHead>Login Terakhir</TableHead>
                                        <TableHead className="w-[50px]"></TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {filteredUsers.map((user) => (
                                        <TableRow key={user.id}>
                                            <TableCell>
                                                <div className="flex items-center gap-3">
                                                    <div className="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-violet-500 to-purple-600 font-semibold text-white">
                                                        {user.name.charAt(0).toUpperCase()}
                                                    </div>
                                                    <div>
                                                        <div className="font-medium">{user.name}</div>
                                                        <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                                            <Mail className="h-3 w-3" />
                                                            {user.email}
                                                        </div>
                                                        {user.phone && (
                                                            <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                                                <Phone className="h-3 w-3" />
                                                                {user.phone}
                                                            </div>
                                                        )}
                                                    </div>
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <RoleBadge role={user.role} />
                                            </TableCell>
                                            <TableCell>
                                                <StatusIndicator user={user} />
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex flex-col gap-1">
                                                    <div className="flex items-center gap-1">
                                                        {user.two_factor_enabled ? (
                                                            <Badge variant="outline" className="border-success text-success">
                                                                <Shield className="mr-1 h-3 w-3" />
                                                                2FA
                                                            </Badge>
                                                        ) : (
                                                            <Badge variant="outline" className="border-muted-foreground text-muted-foreground">
                                                                <ShieldOff className="mr-1 h-3 w-3" />
                                                                No 2FA
                                                            </Badge>
                                                        )}
                                                    </div>
                                                    {user.force_password_change && (
                                                        <div className="flex items-center gap-1 text-xs text-warning">
                                                            <AlertCircle className="h-3 w-3" />
                                                            Wajib ganti password
                                                        </div>
                                                    )}
                                                    {user.failed_login_attempts > 0 && (
                                                        <div className="flex items-center gap-1 text-xs text-destructive">
                                                            <AlertCircle className="h-3 w-3" />
                                                            {user.failed_login_attempts}x gagal login
                                                        </div>
                                                    )}
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                {user.last_login_at ? (
                                                    <div className="text-sm">
                                                        <div className="flex items-center gap-1">
                                                            <Clock className="h-3 w-3 text-muted-foreground" />
                                                            {formatDate(user.last_login_at)}
                                                        </div>
                                                        {user.last_login_ip && (
                                                            <div className="text-xs text-muted-foreground">
                                                                IP: {user.last_login_ip}
                                                            </div>
                                                        )}
                                                    </div>
                                                ) : (
                                                    <span className="text-sm text-muted-foreground">Belum pernah login</span>
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                <DropdownMenu>
                                                    <DropdownMenuTrigger asChild>
                                                        <Button variant="ghost" size="icon">
                                                            <MoreHorizontal className="h-4 w-4" />
                                                        </Button>
                                                    </DropdownMenuTrigger>
                                                    <DropdownMenuContent align="end">
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
                                                                    Unlock
                                                                </>
                                                            ) : (
                                                                <>
                                                                    <Lock className="mr-2 h-4 w-4" />
                                                                    Lock
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
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>
                    )}
                </CardContent>
            </Card>

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
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Hapus Pengguna?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Anda yakin ingin menghapus pengguna <strong>{deletingUser?.name}</strong>?
                            Tindakan ini tidak dapat dibatalkan.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Batal</AlertDialogCancel>
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
