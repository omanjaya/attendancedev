import { useState } from 'react';
import { Link } from '@tanstack/react-router';
import {
    User,
    Mail,
    Phone,
    Building2,
    Briefcase,
    Calendar,
    Shield,
    ShieldCheck,
    Camera,
    Trash2,
    Loader2,
    Save,
    Lock,
    AlertTriangle,
    CheckCircle,
    Clock,
    TrendingUp,
    UserCheck,
    AlertCircle,
    Pencil,
} from 'lucide-react';
import { StatsGrid, type StatItem } from '@/components/shared';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogFooter,
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
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Progress } from '@/components/ui/progress';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useProfilePage } from '@/hooks/use-profile-page';
import { AvatarUploadDialog } from '@/components/profile/AvatarUploadDialog';

export function DesktopProfilePage() {
    // All logic extracted to shared hook
    const logic = useProfilePage();
    const [avatarDialogOpen, setAvatarDialogOpen] = useState(false);

    const handleAvatarUpload = async (file: File) => {
        await logic.uploadAvatarMutation.mutateAsync(file);
    };

    const handleAvatarDelete = async () => {
        await logic.deleteAvatarMutation.mutateAsync();
    };

    const formatDate = (dateStr: string | null) => {
        if (!dateStr) return '-';
        return new Date(dateStr).toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'long',
            year: 'numeric',
        });
    };

    if (logic.isLoading) {
        return (
            <div className="flex h-[50vh] items-center justify-center">
                <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
            </div>
        );
    }

    if (!logic.profile) {
        return (
            <div className="p-4 sm:p-6">
                <Alert variant="destructive">
                    <AlertTriangle className="h-4 w-4" />
                    <AlertDescription>Gagal memuat profil</AlertDescription>
                </Alert>
            </div>
        );
    }

    return (
        <div className="p-4 space-y-4 sm:p-6 sm:space-y-6">
            {/* Success Message */}
            {logic.successMessage && (
                <Alert className="bg-success/10 border-success/20">
                    <CheckCircle className="h-4 w-4 text-success" />
                    <AlertDescription className="text-success">{logic.successMessage}</AlertDescription>
                </Alert>
            )}

            {/* Profile Header */}
            <div className="flex flex-col md:flex-row gap-6">
                <Card className="flex-1">
                    <CardContent className="p-4 sm:p-6">
                        <div className="flex flex-col md:flex-row items-center md:items-start gap-6">
                            {/* Avatar Section - Enhanced */}
                            <div
                                className="relative group cursor-pointer"
                                onClick={() => setAvatarDialogOpen(true)}
                            >
                                <Avatar className="h-32 w-32 ring-4 ring-background shadow-xl transition-all duration-300 group-hover:ring-primary/20 group-hover:shadow-2xl">
                                    <AvatarImage
                                        src={logic.profile.avatar || undefined}
                                        alt={logic.profile.name}
                                        className="object-cover"
                                    />
                                    <AvatarFallback className="text-3xl bg-gradient-to-br from-primary/20 via-primary/10 to-emerald-500/10 text-primary font-semibold">
                                        {logic.getInitials(logic.profile.name)}
                                    </AvatarFallback>
                                </Avatar>

                                {/* Hover Overlay */}
                                <div className="absolute inset-0 rounded-full bg-black/0 group-hover:bg-black/40 transition-all duration-300 flex items-center justify-center opacity-0 group-hover:opacity-100">
                                    <div className="flex flex-col items-center gap-1 text-white transform scale-90 group-hover:scale-100 transition-transform duration-300">
                                        <Camera className="h-6 w-6" />
                                        <span className="text-xs font-medium">Ubah Foto</span>
                                    </div>
                                </div>

                                {/* Edit Badge */}
                                <div className="absolute -bottom-1 -right-1 bg-primary text-primary-foreground rounded-full p-2 shadow-lg ring-2 ring-background transition-transform duration-300 group-hover:scale-110">
                                    <Pencil className="h-3.5 w-3.5" />
                                </div>
                            </div>

                            {/* Profile Info */}
                            <div className="flex-1 text-center md:text-left space-y-2">
                                <div className="flex flex-col md:flex-row md:items-center gap-2">
                                    <h1 className="text-2xl font-bold">{logic.profile.name}</h1>
                                    {logic.profile.two_factor_enabled && (
                                        <Badge variant="secondary" className="w-fit mx-auto md:mx-0">
                                            <ShieldCheck className="h-3 w-3 mr-1" />
                                            2FA Aktif
                                        </Badge>
                                    )}
                                </div>
                                <p className="text-muted-foreground">{logic.profile.email}</p>
                                <div className="flex flex-wrap justify-center md:justify-start gap-2 mt-2">
                                    <Badge variant="outline">{logic.profile.role}</Badge>
                                    {logic.profile.department && <Badge variant="outline">{logic.profile.department}</Badge>}
                                    {logic.profile.position && <Badge variant="secondary">{logic.profile.position}</Badge>}
                                </div>
                                <p className="text-sm text-muted-foreground mt-2">
                                    ID Karyawan: {logic.profile.employee_id || '-'}
                                </p>
                            </div>

                            {/* Actions */}
                            <div className="flex flex-col gap-2">
                                {!logic.editMode ? (
                                    <Button onClick={logic.handleEditClick}>Edit Profil</Button>
                                ) : (
                                    <Button variant="outline" onClick={() => logic.setEditMode(false)}>
                                        Batal
                                    </Button>
                                )}
                                <Button variant="outline" onClick={() => logic.setPasswordDialog(true)}>
                                    <Lock className="h-4 w-4 mr-2" />
                                    Ubah Password
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            {/* Statistics Cards */}
            {logic.statistics && (
                <StatsGrid
                    stats={[
                        {
                            label: 'Hadir',
                            value: logic.statistics.present_days,
                            icon: UserCheck,
                            color: 'success',
                        },
                        {
                            label: 'Terlambat',
                            value: logic.statistics.late_days,
                            icon: Clock,
                            color: 'warning',
                        },
                        {
                            label: 'Cuti',
                            value: logic.statistics.leave_days,
                            icon: Calendar,
                            color: 'primary',
                        },
                        {
                            label: 'Kehadiran',
                            value: `${logic.statistics.current_month_attendance_rate}%`,
                            icon: TrendingUp,
                            color: 'info',
                        },
                    ] as StatItem[]}
                    columns={4}
                    variant="cards"
                />
            )}

            {/* Tabs */}
            <Tabs defaultValue="info" className="space-y-4">
                <TabsList>
                    <TabsTrigger value="info">
                        <User className="h-4 w-4 mr-2" />
                        Informasi
                    </TabsTrigger>
                    <TabsTrigger value="security">
                        <Shield className="h-4 w-4 mr-2" />
                        Keamanan
                    </TabsTrigger>
                    <TabsTrigger value="danger">
                        <AlertTriangle className="h-4 w-4 mr-2" />
                        Zona Bahaya
                    </TabsTrigger>
                </TabsList>

                {/* Info Tab */}
                <TabsContent value="info" className="space-y-4">
                    <Card>
                        <CardHeader>
                            <CardTitle>Informasi Pribadi</CardTitle>
                            <CardDescription>Kelola informasi pribadi Anda</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {logic.editMode ? (
                                <>
                                    <div className="grid gap-4 md:grid-cols-2">
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
                                    <div className="flex justify-end">
                                        <Button onClick={logic.handleSaveProfile} disabled={logic.updateProfileMutation.isPending}>
                                            {logic.updateProfileMutation.isPending ? (
                                                <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                                            ) : (
                                                <Save className="h-4 w-4 mr-2" />
                                            )}
                                            Simpan Perubahan
                                        </Button>
                                    </div>
                                </>
                            ) : (
                                <div className="grid gap-4 md:grid-cols-2">
                                    <div className="flex items-center gap-3">
                                        <User className="h-5 w-5 text-muted-foreground" />
                                        <div>
                                            <p className="text-sm text-muted-foreground">Nama Lengkap</p>
                                            <p className="font-medium">{logic.profile.name}</p>
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-3">
                                        <Mail className="h-5 w-5 text-muted-foreground" />
                                        <div>
                                            <p className="text-sm text-muted-foreground">Email</p>
                                            <p className="font-medium">{logic.profile.email}</p>
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-3">
                                        <Phone className="h-5 w-5 text-muted-foreground" />
                                        <div>
                                            <p className="text-sm text-muted-foreground">No. Telepon</p>
                                            <p className="font-medium">{logic.profile.phone || '-'}</p>
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-3">
                                        <Building2 className="h-5 w-5 text-muted-foreground" />
                                        <div>
                                            <p className="text-sm text-muted-foreground">Departemen</p>
                                            <p className="font-medium">{logic.profile.department || '-'}</p>
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-3">
                                        <Briefcase className="h-5 w-5 text-muted-foreground" />
                                        <div>
                                            <p className="text-sm text-muted-foreground">Jabatan</p>
                                            <p className="font-medium">{logic.profile.position || '-'}</p>
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-3">
                                        <Calendar className="h-5 w-5 text-muted-foreground" />
                                        <div>
                                            <p className="text-sm text-muted-foreground">Tanggal Bergabung</p>
                                            <p className="font-medium">{formatDate(logic.profile.joined_at)}</p>
                                        </div>
                                    </div>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Attendance Progress */}
                    {logic.statistics && (
                        <Card>
                            <CardHeader>
                                <CardTitle>Statistik Kehadiran</CardTitle>
                                <CardDescription>Ringkasan kehadiran Anda</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="space-y-2">
                                    <div className="flex justify-between text-sm">
                                        <span>Tingkat Kehadiran Bulan Ini</span>
                                        <span className="font-medium">{logic.statistics.current_month_attendance_rate}%</span>
                                    </div>
                                    <Progress value={logic.statistics.current_month_attendance_rate} className="h-2" />
                                </div>
                                <div className="grid grid-cols-3 gap-4 pt-4 border-t">
                                    <div className="text-center">
                                        <p className="text-2xl font-bold text-success">{logic.statistics.present_days}</p>
                                        <p className="text-sm text-muted-foreground">Hari Hadir</p>
                                    </div>
                                    <div className="text-center">
                                        <p className="text-2xl font-bold text-warning">{logic.statistics.late_days}</p>
                                        <p className="text-sm text-muted-foreground">Terlambat</p>
                                    </div>
                                    <div className="text-center">
                                        <p className="text-2xl font-bold text-destructive">{logic.statistics.absent_days}</p>
                                        <p className="text-sm text-muted-foreground">Tidak Hadir</p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    )}
                </TabsContent>

                {/* Security Tab */}
                <TabsContent value="security" className="space-y-4">
                    <Card>
                        <CardHeader>
                            <CardTitle>Keamanan Akun</CardTitle>
                            <CardDescription>Pengaturan keamanan dan autentikasi</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {/* Face Recognition Section */}
                            <div className="flex items-center justify-between p-4 rounded-lg border">
                                <div className="flex items-center gap-3">
                                    <div className={`p-2 rounded-lg ${logic.faceData?.has_face_data ? 'bg-success/10' : 'bg-muted'}`}>
                                        {logic.faceData?.has_face_data ? (
                                            <CheckCircle className="h-5 w-5 text-success" />
                                        ) : (
                                            <Camera className="h-5 w-5 text-muted-foreground" />
                                        )}
                                    </div>
                                    <div>
                                        <p className="font-medium">Face Recognition</p>
                                        <p className="text-sm text-muted-foreground">
                                            {logic.faceData?.has_face_data
                                                ? 'Wajah Anda telah terdaftar'
                                                : 'Daftarkan wajah untuk absensi lebih cepat'}
                                        </p>
                                    </div>
                                </div>
                                <div className="flex gap-2">
                                    {logic.faceData?.has_face_data ? (
                                        <>
                                            <Button variant="destructive" size="sm" onClick={() => logic.setDeleteFaceOpen(true)}>
                                                Hapus Data
                                            </Button>
                                            <Button onClick={logic.handleStartFaceEnrollment} size="sm" variant="outline" className="ml-2">
                                                Perbarui Wajah
                                            </Button>
                                        </>
                                    ) : (
                                        <Button onClick={logic.handleStartFaceEnrollment} size="sm">
                                            Daftarkan Wajah
                                        </Button>
                                    )}
                                </div>
                            </div>

                            <div className="flex items-center justify-between p-4 rounded-lg border">
                                <div className="flex items-center gap-3">
                                    <div
                                        className={`p-2 rounded-lg ${logic.profile.two_factor_enabled ? 'bg-success/10' : 'bg-muted'}`}
                                    >
                                        {logic.profile.two_factor_enabled ? (
                                            <ShieldCheck className="h-5 w-5 text-success" />
                                        ) : (
                                            <Shield className="h-5 w-5 text-muted-foreground" />
                                        )}
                                    </div>
                                    <div>
                                        <p className="font-medium">Autentikasi Dua Faktor</p>
                                        <p className="text-sm text-muted-foreground">
                                            {logic.profile.two_factor_enabled
                                                ? 'Akun Anda dilindungi dengan 2FA'
                                                : 'Tambahkan lapisan keamanan ekstra'}
                                        </p>
                                    </div>
                                </div>
                                <Button variant={logic.profile.two_factor_enabled ? 'outline' : 'default'} asChild>
                                    <Link to="/admin/security/two-factor">
                                        {logic.profile.two_factor_enabled ? 'Kelola' : 'Aktifkan'}
                                    </Link>
                                </Button>
                            </div>

                            <div className="flex items-center justify-between p-4 rounded-lg border">
                                <div className="flex items-center gap-3">
                                    <div className="p-2 rounded-lg bg-primary/10">
                                        <Lock className="h-5 w-5 text-primary" />
                                    </div>
                                    <div>
                                        <p className="font-medium">Password</p>
                                        <p className="text-sm text-muted-foreground">Ubah password akun Anda</p>
                                    </div>
                                </div>
                                <Button variant="outline" onClick={() => logic.setPasswordDialog(true)}>
                                    Ubah Password
                                </Button>
                            </div>

                            <div className="flex items-center justify-between p-4 rounded-lg border">
                                <div className="flex items-center gap-3">
                                    <div
                                        className={`p-2 rounded-lg ${logic.profile.email_verified_at ? 'bg-success/10' : 'bg-warning/10'}`}
                                    >
                                        {logic.profile.email_verified_at ? (
                                            <CheckCircle className="h-5 w-5 text-success" />
                                        ) : (
                                            <AlertCircle className="h-5 w-5 text-warning" />
                                        )}
                                    </div>
                                    <div>
                                        <p className="font-medium">Verifikasi Email</p>
                                        <p className="text-sm text-muted-foreground">
                                            {logic.profile.email_verified_at
                                                ? `Diverifikasi pada ${formatDate(logic.profile.email_verified_at)}`
                                                : 'Email belum diverifikasi'}
                                        </p>
                                    </div>
                                </div>
                                {!logic.profile.email_verified_at && (
                                    <Button variant="outline">Kirim Ulang</Button>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>

                {/* Danger Zone Tab */}
                <TabsContent value="danger">
                    <Card className="border-destructive/30">
                        <CardHeader>
                            <CardTitle className="text-destructive">Zona Bahaya</CardTitle>
                            <CardDescription>
                                Tindakan di bawah ini bersifat permanen dan tidak dapat dibatalkan
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="flex items-center justify-between p-4 rounded-lg border border-destructive/30 bg-destructive/5">
                                <div>
                                    <p className="font-medium text-destructive">Hapus Akun</p>
                                    <p className="text-sm text-muted-foreground">
                                        Menghapus akun akan menghapus semua data Anda secara permanen
                                    </p>
                                </div>
                                <Button variant="destructive" onClick={() => logic.setDeleteDialog(true)}>
                                    <Trash2 className="h-4 w-4 mr-2" />
                                    Hapus Akun
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>
            </Tabs>

            {/* Face Enrollment Dialog - DeepFace */}
            <Dialog open={logic.faceEnrollmentOpen} onOpenChange={(open: boolean) => !open && logic.handleCloseFaceEnrollment()}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Daftar Wajah - DeepFace ArcFace</DialogTitle>
                        <DialogDescription>
                            {logic.enrollmentStep === 'success'
                                ? (logic.registrationAction === 'update' ? 'Data wajah berhasil diperbarui!' : 'Wajah Anda berhasil didaftarkan dengan DeepFace!')
                                : 'Posisikan wajah Anda dengan jelas dan tekan Tangkap'}
                        </DialogDescription>
                    </DialogHeader>

                    <div className="p-4 space-y-4">
                        {/* Camera Preview */}
                        <div className="relative w-full aspect-[4/3] bg-black rounded-xl overflow-hidden">
                            <video
                                ref={logic.videoRef}
                                autoPlay
                                playsInline
                                muted
                                className="w-full h-full object-cover"
                            />

                            {/* Status Overlay */}
                            {logic.enrollmentStep === 'camera' && (
                                <div className="absolute inset-0 flex items-center justify-center bg-black/60">
                                    <div className="text-center text-white">
                                        <Loader2 className="h-8 w-8 mx-auto mb-2 animate-spin" />
                                        <p className="text-sm">Mengaktifkan kamera...</p>
                                    </div>
                                </div>
                            )}

                            {logic.enrollmentStep === 'capturing' && (
                                <div className="absolute inset-0 flex items-center justify-center bg-black/60">
                                    <div className="text-center text-white">
                                        <Loader2 className="h-8 w-8 mx-auto mb-2 animate-spin" />
                                        <p className="text-sm">Menangkap gambar...</p>
                                    </div>
                                </div>
                            )}

                            {logic.enrollmentStep === 'processing' && (
                                <div className="absolute inset-0 flex items-center justify-center bg-black/60">
                                    <div className="text-center text-white">
                                        <Loader2 className="h-8 w-8 mx-auto mb-2 animate-spin" />
                                        <p className="text-sm">Memproses dengan DeepFace...</p>
                                        <p className="text-xs mt-1 text-white/70">Mengekstrak 512-d embedding</p>
                                    </div>
                                </div>
                            )}

                            {logic.enrollmentStep === 'success' && (
                                <div className="absolute inset-0 flex items-center justify-center bg-emerald-500/90">
                                    <div className="text-center text-white">
                                        <CheckCircle className="h-12 w-12 mx-auto mb-2" />
                                        <p className="text-lg font-semibold">Berhasil!</p>
                                        <p className="text-sm mt-1">ArcFace 512-d registered</p>
                                    </div>
                                </div>
                            )}

                            {logic.enrollmentStep === 'error' && (
                                <div className="absolute inset-0 flex items-center justify-center bg-red-500/90">
                                    <div className="text-center text-white">
                                        <AlertCircle className="h-12 w-12 mx-auto mb-2" />
                                        <p className="text-lg font-semibold">Gagal!</p>
                                        {logic.enrollmentError && (
                                            <p className="text-sm mt-1 px-4">{logic.enrollmentError}</p>
                                        )}
                                    </div>
                                </div>
                            )}

                            {/* Guide Overlay for Ready State */}
                            {logic.enrollmentStep === 'ready' && (
                                <div className="absolute top-4 left-1/2 transform -translate-x-1/2">
                                    <div className="bg-emerald-500/90 text-white px-4 py-2 rounded-full text-sm font-medium shadow-lg">
                                        Siap - Tekan tombol Tangkap
                                    </div>
                                </div>
                            )}
                        </div>

                        {/* Instructions */}
                        {logic.enrollmentStep === 'ready' && (
                            <Alert>
                                <AlertTriangle className="h-4 w-4" />
                                <AlertDescription>
                                    Pastikan wajah Anda terlihat jelas, pencahayaan cukup, dan tidak ada bayangan.
                                    DeepFace akan melakukan deteksi kualitas gambar otomatis.
                                </AlertDescription>
                            </Alert>
                        )}
                    </div>

                    <DialogFooter>
                        {logic.enrollmentStep === 'ready' && (
                            <Button
                                onClick={logic.handleCaptureFace}
                                className="bg-emerald-600 hover:bg-emerald-700 w-full sm:w-auto"
                            >
                                <Camera className="h-4 w-4 mr-2" />
                                Tangkap Wajah
                            </Button>
                        )}
                        {logic.enrollmentStep === 'error' && (
                            <Button onClick={logic.handleStartFaceEnrollment} variant="destructive" className="w-full sm:w-auto">
                                <Loader2 className="h-4 w-4 mr-2" />
                                Coba Lagi
                            </Button>
                        )}
                        <Button variant="outline" onClick={logic.handleCloseFaceEnrollment} className="w-full sm:w-auto">
                            {logic.enrollmentStep === 'success' ? 'Selesai' : 'Batal'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Delete Face Alert */}
            <AlertDialog open={logic.deleteFaceOpen} onOpenChange={logic.setDeleteFaceOpen}>
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
            </AlertDialog>

            {/* Change Password Dialog */}
            <Dialog open={logic.passwordDialog} onOpenChange={logic.setPasswordDialog}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Ubah Password</DialogTitle>
                        <DialogDescription>
                            Masukkan password saat ini dan password baru Anda
                        </DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4 py-4">
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
                                onChange={(e: React.ChangeEvent<HTMLInputElement>) =>
                                    logic.setPasswordForm({ ...logic.passwordForm, current_password: e.target.value })
                                }
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
                            <Label htmlFor="confirm_password">Konfirmasi Password Baru</Label>
                            <Input
                                id="confirm_password"
                                type="password"
                                value={logic.passwordForm.password_confirmation}
                                onChange={(e: React.ChangeEvent<HTMLInputElement>) =>
                                    logic.setPasswordForm({ ...logic.passwordForm, password_confirmation: e.target.value })
                                }
                            />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => logic.setPasswordDialog(false)}>
                            Batal
                        </Button>
                        <Button onClick={logic.handleChangePassword} disabled={logic.changePasswordMutation.isPending}>
                            {logic.changePasswordMutation.isPending && <Loader2 className="h-4 w-4 mr-2 animate-spin" />}
                            Simpan Password
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Delete Account Dialog */}
            <AlertDialog open={logic.deleteDialog} onOpenChange={logic.setDeleteDialog}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle className="text-destructive">Hapus Akun</AlertDialogTitle>
                        <AlertDialogDescription className="space-y-4">
                            <p>
                                Apakah Anda yakin ingin menghapus akun? Tindakan ini tidak dapat dibatalkan dan
                                semua data Anda akan dihapus secara permanen.
                            </p>
                            {logic.deleteError && (
                                <Alert variant="destructive">
                                    <AlertTriangle className="h-4 w-4" />
                                    <AlertDescription>{logic.deleteError}</AlertDescription>
                                </Alert>
                            )}
                            <div className="space-y-2">
                                <Label htmlFor="delete_password">Masukkan password untuk konfirmasi</Label>
                                <Input
                                    id="delete_password"
                                    type="password"
                                    value={logic.deletePassword}
                                    onChange={(e: React.ChangeEvent<HTMLInputElement>) => logic.setDeletePassword(e.target.value)}
                                    placeholder="Password"
                                />
                            </div>
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel onClick={() => logic.setDeletePassword('')}>Batal</AlertDialogCancel>
                        <AlertDialogAction
                            className="bg-destructive hover:bg-destructive/90"
                            onClick={logic.handleDeleteAccount}
                            disabled={logic.deleteAccountMutation.isPending || !logic.deletePassword}
                        >
                            {logic.deleteAccountMutation.isPending && <Loader2 className="h-4 w-4 mr-2 animate-spin" />}
                            Hapus Akun Saya
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>

            {/* Avatar Upload Dialog */}
            <AvatarUploadDialog
                open={avatarDialogOpen}
                onOpenChange={setAvatarDialogOpen}
                currentAvatar={logic.profile.avatar}
                userName={logic.profile.name}
                onUpload={handleAvatarUpload}
                onDelete={handleAvatarDelete}
                isUploading={logic.uploadAvatarMutation.isPending}
                isDeleting={logic.deleteAvatarMutation.isPending}
            />
        </div>
    );
}
