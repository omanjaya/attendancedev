import { useState, useRef } from 'react';
import { useNavigate } from '@tanstack/react-router';
import {
    ChevronLeft,
    Camera,
    User,
    Mail,
    Phone,
    Building2,
    Briefcase,
    Calendar,
    Lock,
    LogOut,
    ChevronRight,
    ShieldCheck,
    Shield,
    Trash2,
    Loader2,
    Save,
    AlertTriangle,
    UserCheck,
    Clock,
    TrendingUp
} from 'lucide-react';
import { useAuthStore } from '@/stores';
import {
    useProfile,
    useProfileStatistics,
    useUpdateProfile,
    useChangePassword,
    useUploadAvatar,
    useDeleteAvatar,
} from '@/hooks/use-profile';
import { Button } from '@/components/ui/button';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Drawer,
    DrawerClose,
    DrawerContent,
    DrawerDescription,
    DrawerFooter,
    DrawerHeader,
    DrawerTitle,
    DrawerTrigger,
} from '@/components/ui/drawer';
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
import { Alert, AlertDescription } from '@/components/ui/alert';
import { cn } from '@/lib/utils';
import { LoadingState } from '@/components/states';

export function MobileProfilePage() {
    const navigate = useNavigate();
    const { logout } = useAuthStore();
    const { data: profile, isLoading } = useProfile();
    const { data: statistics } = useProfileStatistics();
    const updateProfile = useUpdateProfile();
    const changePassword = useChangePassword();
    const uploadAvatar = useUploadAvatar();
    const deleteAvatar = useDeleteAvatar();

    const fileInputRef = useRef<HTMLInputElement>(null);

    // State
    const [isEditOpen, setIsEditOpen] = useState(false);
    const [isPasswordOpen, setIsPasswordOpen] = useState(false);
    const [isLogoutOpen, setIsLogoutOpen] = useState(false);

    const [formData, setFormData] = useState({
        name: '',
        email: '',
        phone: '',
    });

    const [passwordForm, setPasswordForm] = useState({
        current_password: '',
        password: '',
        password_confirmation: '',
    });
    const [passwordError, setPasswordError] = useState<string | null>(null);

    const handleEditOpen = () => {
        if (profile) {
            setFormData({
                name: profile.name,
                email: profile.email,
                phone: profile.phone || '',
            });
            setIsEditOpen(true);
        }
    };

    const handleSaveProfile = async () => {
        try {
            await updateProfile.mutateAsync(formData);
            setIsEditOpen(false);
        } catch {
            // Error handled by mutation
        }
    };

    const handleChangePassword = async () => {
        setPasswordError(null);
        try {
            await changePassword.mutateAsync(passwordForm);
            setIsPasswordOpen(false);
            setPasswordForm({ current_password: '', password: '', password_confirmation: '' });
        } catch (err) {
            setPasswordError(err instanceof Error ? err.message : 'Gagal mengubah password');
        }
    };

    const handleLogout = async () => {
        await logout();
        navigate({ to: '/login' });
    };

    const handleAvatarUpload = async (event: React.ChangeEvent<HTMLInputElement>) => {
        const file = event.target.files?.[0];
        if (file) {
            try {
                await uploadAvatar.mutateAsync(file);
            } catch {
                // Error handled by mutation
            }
        }
    };

    const getInitials = (name: string) => {
        return name
            .split(' ')
            .map((n) => n[0])
            .join('')
            .toUpperCase()
            .slice(0, 2);
    };

    if (isLoading) {
        return (
            <div className="min-h-screen flex items-center justify-center bg-background">
                <LoadingState message="Memuat profil..." />
            </div>
        );
    }

    if (!profile) return null;

    return (
        <div className="min-h-screen bg-gradient-to-b from-muted/30 via-background to-background dark:from-gray-950 dark:via-gray-900 dark:to-gray-900 pb-20">
            {/* Header */}
            <div className="px-4 pt-3 pb-3 sticky top-0 z-20">
                <div className="bg-card/80 dark:bg-card/60 backdrop-blur-md rounded-3xl p-1.5 shadow-xl border border-border/40 dark:border-border/30">
                    <div className="bg-gradient-to-r from-violet-600 to-indigo-600 dark:from-violet-900 dark:to-indigo-800 px-4 py-3 rounded-[20px] flex items-center gap-3 shadow-lg">
                        <button
                            onClick={() => navigate({ to: '/dashboard' })}
                            className="p-2 -ml-2 hover:bg-white/10 rounded-full transition-colors active:scale-95"
                        >
                            <ChevronLeft className="h-5 w-5 text-white" />
                        </button>
                        <h1 className="text-base font-bold text-white flex-1">Profil Saya</h1>
                    </div>
                </div>
            </div>

            <div className="px-4 space-y-5 mt-2">
                {/* Profile Card */}
                <div className="bg-white dark:bg-gray-900 rounded-3xl p-6 shadow-sm border border-border/50 flex flex-col items-center text-center relative overflow-hidden">
                    <div className="absolute top-0 left-0 w-full h-24 bg-gradient-to-b from-violet-50 to-transparent dark:from-violet-900/20 dark:to-transparent" />

                    <div className="relative mb-4">
                        <Avatar className="h-24 w-24 border-4 border-white dark:border-gray-900 shadow-lg">
                            <AvatarImage src={profile.avatar || undefined} alt={profile.name} />
                            <AvatarFallback className="text-2xl bg-violet-100 text-violet-600">
                                {getInitials(profile.name)}
                            </AvatarFallback>
                        </Avatar>
                        <button
                            onClick={() => fileInputRef.current?.click()}
                            className="absolute bottom-0 right-0 p-2 bg-primary text-primary-foreground rounded-full shadow-md hover:bg-primary/90 transition-colors"
                        >
                            <Camera className="h-4 w-4" />
                        </button>
                        <input
                            type="file"
                            ref={fileInputRef}
                            className="hidden"
                            accept="image/*"
                            onChange={handleAvatarUpload}
                        />
                    </div>

                    <h2 className="text-xl font-bold text-foreground">{profile.name}</h2>
                    <p className="text-sm text-muted-foreground mb-3">{profile.email}</p>

                    <div className="flex flex-wrap justify-center gap-2">
                        <Badge variant="secondary" className="bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400 border-0">
                            {profile.role}
                        </Badge>
                        {profile.department && (
                            <Badge variant="outline" className="border-border/60">
                                {profile.department}
                            </Badge>
                        )}
                    </div>
                </div>

                {/* Stats Grid */}
                {statistics && (
                    <div className="grid grid-cols-2 gap-3">
                        <div className="bg-white dark:bg-gray-900 p-4 rounded-2xl border border-border/50 shadow-sm">
                            <div className="flex items-center gap-2 mb-2 text-emerald-600 dark:text-emerald-400">
                                <UserCheck className="h-5 w-5" />
                                <span className="text-xs font-medium">Hadir</span>
                            </div>
                            <p className="text-2xl font-bold">{statistics.present_days}</p>
                            <p className="text-xs text-muted-foreground">Hari</p>
                        </div>
                        <div className="bg-white dark:bg-gray-900 p-4 rounded-2xl border border-border/50 shadow-sm">
                            <div className="flex items-center gap-2 mb-2 text-amber-600 dark:text-amber-400">
                                <Clock className="h-5 w-5" />
                                <span className="text-xs font-medium">Terlambat</span>
                            </div>
                            <p className="text-2xl font-bold">{statistics.late_days}</p>
                            <p className="text-xs text-muted-foreground">Kali</p>
                        </div>
                        <div className="bg-white dark:bg-gray-900 p-4 rounded-2xl border border-border/50 shadow-sm">
                            <div className="flex items-center gap-2 mb-2 text-blue-600 dark:text-blue-400">
                                <Calendar className="h-5 w-5" />
                                <span className="text-xs font-medium">Cuti</span>
                            </div>
                            <p className="text-2xl font-bold">{statistics.leave_days}</p>
                            <p className="text-xs text-muted-foreground">Hari</p>
                        </div>
                        <div className="bg-white dark:bg-gray-900 p-4 rounded-2xl border border-border/50 shadow-sm">
                            <div className="flex items-center gap-2 mb-2 text-violet-600 dark:text-violet-400">
                                <TrendingUp className="h-5 w-5" />
                                <span className="text-xs font-medium">Rate</span>
                            </div>
                            <p className="text-2xl font-bold">{statistics.current_month_attendance_rate}%</p>
                            <p className="text-xs text-muted-foreground">Bulan Ini</p>
                        </div>
                    </div>
                )}

                {/* Menu List */}
                <div className="bg-white dark:bg-gray-900 rounded-3xl shadow-sm border border-border/50 overflow-hidden">
                    <div
                        onClick={handleEditOpen}
                        className="p-4 flex items-center justify-between border-b border-border/50 active:bg-muted/50 transition-colors cursor-pointer"
                    >
                        <div className="flex items-center gap-3">
                            <div className="h-9 w-9 rounded-full bg-violet-50 dark:bg-violet-900/20 flex items-center justify-center text-violet-600 dark:text-violet-400">
                                <User className="h-5 w-5" />
                            </div>
                            <span className="font-medium text-sm">Edit Profil</span>
                        </div>
                        <ChevronRight className="h-5 w-5 text-muted-foreground" />
                    </div>

                    <div
                        onClick={() => setIsPasswordOpen(true)}
                        className="p-4 flex items-center justify-between border-b border-border/50 active:bg-muted/50 transition-colors cursor-pointer"
                    >
                        <div className="flex items-center gap-3">
                            <div className="h-9 w-9 rounded-full bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center text-blue-600 dark:text-blue-400">
                                <Lock className="h-5 w-5" />
                            </div>
                            <span className="font-medium text-sm">Ubah Password</span>
                        </div>
                        <ChevronRight className="h-5 w-5 text-muted-foreground" />
                    </div>

                    <div className="p-4 flex items-center justify-between border-b border-border/50">
                        <div className="flex items-center gap-3">
                            <div className={cn(
                                "h-9 w-9 rounded-full flex items-center justify-center",
                                profile.two_factor_enabled
                                    ? "bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400"
                                    : "bg-gray-100 dark:bg-gray-800 text-gray-500"
                            )}>
                                {profile.two_factor_enabled ? <ShieldCheck className="h-5 w-5" /> : <Shield className="h-5 w-5" />}
                            </div>
                            <div className="flex flex-col">
                                <span className="font-medium text-sm">Two-Factor Auth</span>
                                <span className="text-xs text-muted-foreground">
                                    {profile.two_factor_enabled ? 'Aktif' : 'Tidak Aktif'}
                                </span>
                            </div>
                        </div>
                        {/* Navigate to security page if needed, or just show status */}
                    </div>

                    <div
                        onClick={() => setIsLogoutOpen(true)}
                        className="p-4 flex items-center justify-between active:bg-red-50 dark:active:bg-red-900/10 transition-colors cursor-pointer"
                    >
                        <div className="flex items-center gap-3">
                            <div className="h-9 w-9 rounded-full bg-red-50 dark:bg-red-900/20 flex items-center justify-center text-red-600 dark:text-red-400">
                                <LogOut className="h-5 w-5" />
                            </div>
                            <span className="font-medium text-sm text-red-600 dark:text-red-400">Keluar</span>
                        </div>
                    </div>
                </div>
            </div>

            {/* Edit Profile Drawer */}
            <Drawer open={isEditOpen} onOpenChange={setIsEditOpen}>
                <DrawerContent>
                    <DrawerHeader>
                        <DrawerTitle>Edit Profil</DrawerTitle>
                        <DrawerDescription>Perbarui informasi pribadi Anda.</DrawerDescription>
                    </DrawerHeader>
                    <div className="p-4 space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="name">Nama Lengkap</Label>
                            <Input
                                id="name"
                                value={formData.name}
                                onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="email">Email</Label>
                            <Input
                                id="email"
                                type="email"
                                value={formData.email}
                                onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="phone">No. Telepon</Label>
                            <Input
                                id="phone"
                                value={formData.phone}
                                onChange={(e) => setFormData({ ...formData, phone: e.target.value })}
                            />
                        </div>
                    </div>
                    <DrawerFooter>
                        <Button onClick={handleSaveProfile} disabled={updateProfile.isPending}>
                            {updateProfile.isPending ? <Loader2 className="h-4 w-4 mr-2 animate-spin" /> : <Save className="h-4 w-4 mr-2" />}
                            Simpan
                        </Button>
                        <DrawerClose asChild>
                            <Button variant="outline">Batal</Button>
                        </DrawerClose>
                    </DrawerFooter>
                </DrawerContent>
            </Drawer>

            {/* Change Password Drawer */}
            <Drawer open={isPasswordOpen} onOpenChange={setIsPasswordOpen}>
                <DrawerContent>
                    <DrawerHeader>
                        <DrawerTitle>Ubah Password</DrawerTitle>
                        <DrawerDescription>Pastikan password baru Anda aman.</DrawerDescription>
                    </DrawerHeader>
                    <div className="p-4 space-y-4">
                        {passwordError && (
                            <Alert variant="destructive">
                                <AlertTriangle className="h-4 w-4" />
                                <AlertDescription>{passwordError}</AlertDescription>
                            </Alert>
                        )}
                        <div className="space-y-2">
                            <Label htmlFor="current_password">Password Saat Ini</Label>
                            <Input
                                id="current_password"
                                type="password"
                                value={passwordForm.current_password}
                                onChange={(e) => setPasswordForm({ ...passwordForm, current_password: e.target.value })}
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="new_password">Password Baru</Label>
                            <Input
                                id="new_password"
                                type="password"
                                value={passwordForm.password}
                                onChange={(e) => setPasswordForm({ ...passwordForm, password: e.target.value })}
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="confirm_password">Konfirmasi Password</Label>
                            <Input
                                id="confirm_password"
                                type="password"
                                value={passwordForm.password_confirmation}
                                onChange={(e) => setPasswordForm({ ...passwordForm, password_confirmation: e.target.value })}
                            />
                        </div>
                    </div>
                    <DrawerFooter>
                        <Button onClick={handleChangePassword} disabled={changePassword.isPending}>
                            {changePassword.isPending && <Loader2 className="h-4 w-4 mr-2 animate-spin" />}
                            Ubah Password
                        </Button>
                        <DrawerClose asChild>
                            <Button variant="outline">Batal</Button>
                        </DrawerClose>
                    </DrawerFooter>
                </DrawerContent>
            </Drawer>

            {/* Logout Alert */}
            <AlertDialog open={isLogoutOpen} onOpenChange={setIsLogoutOpen}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Keluar Aplikasi?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Anda harus login kembali untuk mengakses akun Anda.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Batal</AlertDialogCancel>
                        <AlertDialogAction onClick={handleLogout} className="bg-destructive text-destructive-foreground hover:bg-destructive/90">
                            Keluar
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </div>
    );
}
