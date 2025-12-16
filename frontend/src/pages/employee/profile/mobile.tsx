import { useState } from 'react';
import { useNavigate } from '@tanstack/react-router';
import {
    Camera,
    User,
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
    TrendingUp,
    Scan,
    CheckCircle2,
    XCircle,
    RefreshCw,
} from 'lucide-react';
import { MobilePageHeader } from '@/components/mobile';
import { useAuthStore } from '@/stores';
import { useProfilePage } from '@/hooks/use-profile-page';
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

    // All logic extracted to shared hook
    const logic = useProfilePage();
    const [isLogoutOpen, setIsLogoutOpen] = useState(false);

    const handleLogout = () => {
        logout();
        navigate({ to: '/login' });
    };


    if (logic.isLoading) {
        return (
            <div className="min-h-screen flex items-center justify-center bg-background">
                <LoadingState message="Memuat profil..." />
            </div>
        );
    }

    if (!logic.profile) return null;

    return (
        <div className="min-h-screen bg-background pb-24">
            <MobilePageHeader
                title="Profil Saya"
                gradient="violet"
                backTo="/employee/dashboard"
            />

            <div className="px-4 space-y-4">
                {/* Profile Card */}
                <div className="bg-card rounded-2xl shadow-sm dark:border dark:border-border/50 p-6 flex flex-col items-center text-center relative overflow-hidden">
                    <div className="absolute top-0 left-0 w-full h-20 bg-gradient-to-b from-violet-100/50 to-transparent dark:from-violet-900/30 dark:to-transparent" />

                    <div className="relative mb-4">
                        <Avatar className="h-20 w-20 border-4 border-white dark:border-gray-900 shadow-lg">
                            <AvatarImage src={logic.profile.avatar || undefined} alt={logic.profile.name} />
                            <AvatarFallback className="text-2xl bg-violet-100 dark:bg-violet-900/30 text-violet-600 dark:text-violet-400">
                                {logic.getInitials(logic.profile.name)}
                            </AvatarFallback>
                        </Avatar>
                        <button
                            onClick={() => logic.fileInputRef.current?.click()}
                            className="absolute bottom-0 right-0 p-2.5 bg-primary text-primary-foreground rounded-full shadow-lg hover:bg-primary/90 transition-colors active:scale-95"
                            aria-label="Ubah Foto Profil"
                        >
                            <Camera className="h-4 w-4" />
                        </button>
                        <input
                            type="file"
                            ref={logic.fileInputRef}
                            className="hidden"
                            accept="image/*"
                            onChange={logic.handleAvatarUpload}
                            title="Ubah Foto Profil"
                            aria-label="Ubah Foto Profil"
                        />
                    </div>

                    <h2 className="text-lg font-bold text-foreground mb-1">{logic.profile.name}</h2>
                    <p className="text-sm text-muted-foreground mb-3">{logic.profile.email}</p>

                    <div className="flex flex-wrap justify-center gap-2">
                        <Badge variant="secondary" className="bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400">
                            {logic.profile.role}
                        </Badge>
                        {logic.profile.department && (
                            <Badge variant="outline" className="border-border/60">
                                {logic.profile.department}
                            </Badge>
                        )}
                    </div>
                </div>

                {/* Stats Grid */}
                {logic.statistics && (
                    <div className="grid grid-cols-2 gap-3">
                        <div className="bg-white dark:bg-gray-900 p-4 rounded-2xl border border-border/50 shadow-sm">
                            <div className="flex items-center gap-2 mb-2 text-emerald-600 dark:text-emerald-400">
                                <UserCheck className="h-5 w-5" />
                                <span className="text-xs font-medium">Hadir</span>
                            </div>
                            <p className="text-2xl font-bold">{logic.statistics.present_days}</p>
                            <p className="text-xs text-muted-foreground">Hari</p>
                        </div>
                        <div className="bg-white dark:bg-gray-900 p-4 rounded-2xl border border-border/50 shadow-sm">
                            <div className="flex items-center gap-2 mb-2 text-amber-600 dark:text-amber-400">
                                <Clock className="h-5 w-5" />
                                <span className="text-xs font-medium">Terlambat</span>
                            </div>
                            <p className="text-2xl font-bold">{logic.statistics.late_days}</p>
                            <p className="text-xs text-muted-foreground">Kali</p>
                        </div>
                        <div className="bg-white dark:bg-gray-900 p-4 rounded-2xl border border-border/50 shadow-sm">
                            <div className="flex items-center gap-2 mb-2 text-blue-600 dark:text-blue-400">
                                <Calendar className="h-5 w-5" />
                                <span className="text-xs font-medium">Cuti</span>
                            </div>
                            <p className="text-2xl font-bold">{logic.statistics.leave_days}</p>
                            <p className="text-xs text-muted-foreground">Hari</p>
                        </div>
                        <div className="bg-white dark:bg-gray-900 p-4 rounded-2xl border border-border/50 shadow-sm">
                            <div className="flex items-center gap-2 mb-2 text-violet-600 dark:text-violet-400">
                                <TrendingUp className="h-5 w-5" />
                                <span className="text-xs font-medium">Rate</span>
                            </div>
                            <p className="text-2xl font-bold">{logic.statistics.current_month_attendance_rate}%</p>
                            <p className="text-xs text-muted-foreground">Bulan Ini</p>
                        </div>
                    </div>
                )}

                {/* Menu List */}
                <div className="bg-card rounded-2xl shadow-sm dark:border dark:border-border/50 p-4 space-y-2">
                    <div
                        onClick={logic.handleEditClick}
                        className="p-3 flex items-center justify-between border border-border/50 rounded-xl active:bg-muted/50 cursor-pointer transition-all"
                    >
                        <div className="flex items-center gap-3">
                            <div className="h-8 w-8 rounded-full bg-violet-50 dark:bg-violet-900/20 flex items-center justify-center text-violet-600 dark:text-violet-400">
                                <User className="h-4 w-4" />
                            </div>
                            <span className="text-sm font-medium text-foreground">Edit Profil</span>
                        </div>
                        <ChevronRight className="h-4 w-4 text-muted-foreground" />
                    </div>

                    <div className="p-4 border-b border-border/50">
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-3">
                                <div className={cn(
                                    "h-9 w-9 rounded-full flex items-center justify-center",
                                    logic.faceData?.has_face_data
                                        ? "bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400"
                                        : "bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400"
                                )}>
                                    <Scan className="h-5 w-5" />
                                </div>
                                <div className="flex flex-col">
                                    <span className="font-medium text-sm">Daftar Wajah</span>
                                    <span className="text-xs text-muted-foreground">
                                        Face Recognition
                                    </span>
                                </div>
                            </div>
                            {logic.faceData?.has_face_data && (
                                <button
                                    onClick={(e: React.MouseEvent) => {
                                        e.stopPropagation();
                                        logic.setDeleteFaceOpen(true);
                                    }}
                                    className="p-1.5 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-full transition-colors"
                                    aria-label="Hapus Data Wajah"
                                >
                                    <Trash2 className="h-4 w-4 text-red-500" />
                                </button>
                            )}
                        </div>

                        {/* Status Info */}
                        <div className="mt-3">
                            {logic.faceData?.has_face_data ? (
                                <div className="bg-emerald-50 dark:bg-emerald-900/10 border border-emerald-200 dark:border-emerald-800 rounded-lg p-3 space-y-2">
                                    <div className="flex items-center gap-2">
                                        <ShieldCheck className="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                                        <span className="text-sm font-medium text-emerald-700 dark:text-emerald-300">
                                            Data wajah Anda sudah terdaftar
                                        </span>
                                    </div>
                                    {logic.faceData.face_data && (
                                        <div className="text-xs text-emerald-600 dark:text-emerald-400 space-y-1 pl-6">
                                            {logic.faceData.face_data.algorithm && (
                                                <div>Model: {logic.faceData.face_data.algorithm}</div>
                                            )}
                                            {logic.faceData.face_data.confidence && (
                                                <div>Confidence: {(logic.faceData.face_data.confidence * 100).toFixed(1)}%</div>
                                            )}
                                            {logic.faceData.face_data.registered_at && (
                                                <div>Terdaftar: {new Date(logic.faceData.face_data.registered_at).toLocaleDateString('id-ID')}</div>
                                            )}
                                        </div>
                                    )}
                                    <Button
                                        onClick={logic.handleStartFaceEnrollment}
                                        variant="outline"
                                        size="sm"
                                        className="w-full mt-2 border-emerald-300 hover:bg-emerald-100 dark:border-emerald-700 dark:hover:bg-emerald-900/20"
                                    >
                                        <Scan className="h-3.5 w-3.5 mr-2" />
                                        Perbarui Data Wajah
                                    </Button>
                                </div>
                            ) : (
                                <div className="bg-amber-50 dark:bg-amber-900/10 border border-amber-200 dark:border-amber-800 rounded-lg p-3 space-y-2">
                                    <div className="flex items-center gap-2">
                                        <AlertTriangle className="h-4 w-4 text-amber-600 dark:text-amber-400" />
                                        <span className="text-sm font-medium text-amber-700 dark:text-amber-300">
                                            Belum terdaftar
                                        </span>
                                    </div>
                                    <p className="text-xs text-amber-600 dark:text-amber-400 pl-6">
                                        Daftarkan wajah Anda untuk menggunakan fitur face recognition saat absensi.
                                    </p>
                                    <Button
                                        onClick={logic.handleStartFaceEnrollment}
                                        size="sm"
                                        className="w-full mt-2 bg-amber-600 hover:bg-amber-700"
                                    >
                                        <Camera className="h-3.5 w-3.5 mr-2" />
                                        Daftar Sekarang
                                    </Button>
                                </div>
                            )}
                        </div>
                    </div>

                    <div
                        onClick={() => logic.setPasswordDialog(true)}
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
                                logic.profile.two_factor_enabled
                                    ? "bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400"
                                    : "bg-gray-100 dark:bg-gray-800 text-gray-500"
                            )}>
                                {logic.profile.two_factor_enabled ? <ShieldCheck className="h-5 w-5" /> : <Shield className="h-5 w-5" />}
                            </div>
                            <div className="flex flex-col">
                                <span className="font-medium text-sm">Two-Factor Auth</span>
                                <span className="text-xs text-muted-foreground">
                                    {logic.profile.two_factor_enabled ? 'Aktif' : 'Tidak Aktif'}
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
            </div >

            {/* Edit Profile Drawer */}
            < Drawer open={logic.editMode} onOpenChange={logic.setEditMode} >
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
                                value={logic.formData.name}
                                onChange={(e: React.ChangeEvent<HTMLInputElement>) => logic.setFormData({ ...logic.formData, name: e.target.value })}
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="email">Email</Label>
                            <Input
                                id="email"
                                type="email"
                                value={logic.formData.email}
                                onChange={(e: React.ChangeEvent<HTMLInputElement>) => logic.setFormData({ ...logic.formData, email: e.target.value })}
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="phone">No. Telepon</Label>
                            <Input
                                id="phone"
                                value={logic.formData.phone}
                                onChange={(e: React.ChangeEvent<HTMLInputElement>) => logic.setFormData({ ...logic.formData, phone: e.target.value })}
                            />
                        </div>
                    </div>
                    <DrawerFooter>
                        <Button onClick={logic.handleSaveProfile} disabled={logic.updateProfileMutation.isPending}>
                            {logic.updateProfileMutation.isPending ? <Loader2 className="h-4 w-4 mr-2 animate-spin" /> : <Save className="h-4 w-4 mr-2" />}
                            Simpan
                        </Button>
                        <DrawerClose asChild>
                            <Button variant="outline">Batal</Button>
                        </DrawerClose>
                    </DrawerFooter>
                </DrawerContent>
            </Drawer >

            {/* Change Password Drawer */}
            < Drawer open={logic.passwordDialog} onOpenChange={logic.setPasswordDialog} >
                <DrawerContent>
                    <DrawerHeader>
                        <DrawerTitle>Ubah Password</DrawerTitle>
                        <DrawerDescription>Pastikan password baru Anda aman.</DrawerDescription>
                    </DrawerHeader>
                    <div className="p-4 space-y-4">
                        {logic.passwordError && (
                            <Alert variant="destructive">
                                <AlertTriangle className="h-4 w-4" />
                                <AlertDescription>{logic.passwordError}</AlertDescription>
                            </Alert>
                        )}
                        <div className="space-y-2">
                            <Label htmlFor="current_password">Password Saat Ini</Label>
                            <Input
                                id="current_password"
                                type="password"
                                value={logic.passwordForm.current_password}
                                onChange={(e: React.ChangeEvent<HTMLInputElement>) => logic.setPasswordForm({ ...logic.passwordForm, current_password: e.target.value })}
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="new_password">Password Baru</Label>
                            <Input
                                id="new_password"
                                type="password"
                                value={logic.passwordForm.password}
                                onChange={(e: React.ChangeEvent<HTMLInputElement>) => logic.setPasswordForm({ ...logic.passwordForm, password: e.target.value })}
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="confirm_password">Konfirmasi Password</Label>
                            <Input
                                id="confirm_password"
                                type="password"
                                value={logic.passwordForm.password_confirmation}
                                onChange={(e: React.ChangeEvent<HTMLInputElement>) => logic.setPasswordForm({ ...logic.passwordForm, password_confirmation: e.target.value })}
                            />
                        </div>
                    </div>
                    <DrawerFooter>
                        <Button onClick={logic.handleChangePassword} disabled={logic.changePasswordMutation.isPending}>
                            {logic.changePasswordMutation.isPending && <Loader2 className="h-4 w-4 mr-2 animate-spin" />}
                            Ubah Password
                        </Button>
                        <DrawerClose asChild>
                            <Button variant="outline">Batal</Button>
                        </DrawerClose>
                    </DrawerFooter>
                </DrawerContent>
            </Drawer >

            {/* Logout Alert */}
            < AlertDialog open={isLogoutOpen} onOpenChange={setIsLogoutOpen} >
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
            </AlertDialog >

            {/* Face Enrollment Drawer - DeepFace */}
            < Drawer open={logic.faceEnrollmentOpen} onOpenChange={(open: boolean) => !open && logic.handleCloseFaceEnrollment()
            }>
                <DrawerContent>
                    <DrawerHeader>
                        <DrawerTitle>
                            {logic.faceData?.has_face_data ? 'Perbarui Data Wajah' : 'Daftar Wajah'} - DeepFace ArcFace
                        </DrawerTitle>
                        <DrawerDescription>
                            {logic.enrollmentStep === 'success'
                                ? logic.faceData?.has_face_data
                                    ? 'Data wajah Anda berhasil diperbarui!'
                                    : 'Wajah Anda berhasil didaftarkan!'
                                : logic.faceData?.has_face_data
                                    ? 'Perbarui data wajah untuk meningkatkan akurasi pengenalan saat absensi.'
                                    : 'Posisikan wajah Anda di tengah, sistem akan mengambil gambar otomatis.'}
                        </DrawerDescription>
                    </DrawerHeader>

                    <div className="p-4 space-y-4 flex-1 overflow-y-auto">
                        {/* Camera Preview */}
                        <div className="relative w-full aspect-[4/3] bg-black rounded-xl overflow-hidden">
                            <video
                                ref={logic.videoRef}
                                autoPlay
                                playsInline
                                muted
                                className="w-full h-full object-cover"
                                style={{ transform: 'scaleX(-1)' }}
                            />


                            {/* Ready State - Show Capture Button */}
                            {logic.enrollmentStep === 'ready' && (
                                <div className="absolute inset-0 flex items-center justify-center">
                                    <div className="absolute top-4 left-1/2 transform -translate-x-1/2">
                                        <div className="bg-blue-500/90 text-white px-4 py-2 rounded-full text-sm font-medium shadow-lg">
                                            👤 Posisikan wajah Anda di tengah
                                        </div>
                                    </div>
                                </div>
                            )}

                            {/* Processing Overlay */}
                            {logic.enrollmentStep === 'processing' && (
                                <div className="absolute inset-0 flex items-center justify-center bg-black/80">
                                    <div className="text-center text-white">
                                        <Loader2 className="h-12 w-12 mx-auto mb-3 animate-spin" />
                                        <p className="text-lg font-semibold">Memproses dengan DeepFace...</p>
                                        <p className="text-sm mt-1 text-white/70">Mengekstrak 512-d embedding</p>
                                    </div>
                                </div>
                            )}

                            {/* Success Overlay */}
                            {logic.enrollmentStep === 'success' && (
                                <div className="absolute inset-0 flex items-center justify-center bg-emerald-500/95">
                                    <div className="text-center text-white">
                                        <CheckCircle2 className="h-16 w-16 mx-auto mb-3" />
                                        <p className="text-2xl font-bold">Berhasil!</p>
                                        <p className="text-sm mt-2">
                                            {logic.registrationAction === 'update' ? 'Data wajah telah diperbarui' : 'Wajah Anda telah terdaftar'}
                                        </p>
                                        <p className="text-xs mt-1 text-white/80">ArcFace 512-d {logic.registrationAction === 'update' ? 'updated' : 'registered'}</p>
                                    </div>
                                </div>
                            )}

                            {/* Error Overlay */}
                            {logic.enrollmentStep === 'error' && (
                                <div className="absolute inset-0 flex items-center justify-center bg-red-500/95">
                                    <div className="text-center text-white px-6">
                                        <XCircle className="h-16 w-16 mx-auto mb-3" />
                                        <p className="text-2xl font-bold">Gagal!</p>
                                        {logic.enrollmentError && (
                                            <p className="text-sm mt-2">{logic.enrollmentError}</p>
                                        )}
                                        <p className="text-xs mt-2 text-white/80">Mencoba lagi dalam 3 detik...</p>
                                    </div>
                                </div>
                            )}
                        </div>

                        {/* Instructions */}
                        {logic.enrollmentStep === 'ready' && (
                            <Alert>
                                <AlertTriangle className="h-4 w-4" />
                                <AlertDescription>
                                    <strong>Panduan:</strong>
                                    <ul className="list-disc list-inside mt-1 text-sm space-y-1">
                                        <li>Posisikan wajah di tengah kamera</li>
                                        <li>Pastikan pencahayaan cukup terang</li>
                                        <li>Tekan tombol "Ambil Foto" untuk memotret</li>
                                        <li>Tahan posisi saat foto diambil</li>
                                    </ul>
                                </AlertDescription>
                            </Alert>
                        )}

                        {/* Action Buttons - Moved here for better visibility */}
                        {logic.enrollmentStep === 'ready' && (
                            <Button
                                onClick={logic.handleCaptureFace}
                                className="bg-emerald-600 hover:bg-emerald-700 w-full"
                                size="lg"
                            >
                                <Camera className="h-5 w-5 mr-2" />
                                Ambil Foto
                            </Button>
                        )}
                        {logic.enrollmentStep === 'error' && (
                            <Button
                                onClick={logic.handleStartFaceEnrollment}
                                variant="destructive"
                                className="w-full"
                            >
                                <RefreshCw className="h-4 w-4 mr-2" />
                                Coba Lagi
                            </Button>
                        )}
                    </div>

                    <DrawerFooter>
                        <DrawerClose asChild>
                            <Button
                                variant="outline"
                                onClick={logic.handleCloseFaceEnrollment}
                                disabled={logic.enrollmentStep === 'processing'}
                                className="w-full"
                            >
                                {logic.enrollmentStep === 'success' ? 'Selesai' : 'Batal'}
                            </Button>
                        </DrawerClose>
                    </DrawerFooter>
                </DrawerContent>
            </Drawer >

            {/* Delete Face Alert */}
            < AlertDialog open={logic.deleteFaceOpen} onOpenChange={logic.setDeleteFaceOpen} >
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Hapus Data Wajah?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Data wajah Anda akan dihapus dan Anda perlu mendaftar ulang untuk menggunakan fitur face recognition.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Batal</AlertDialogCancel>
                        <AlertDialogAction
                            onClick={logic.handleDeleteFace}
                            disabled={logic.deleteFaceMutation.isPending}
                            className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                        >
                            {logic.deleteFaceMutation.isPending ? (
                                <>
                                    <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                                    Menghapus...
                                </>
                            ) : (
                                'Hapus'
                            )}
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog >
        </div >
    );
}
