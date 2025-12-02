import { useState, useRef, useEffect } from 'react';
import { useNavigate } from '@tanstack/react-router';
import { useQueryClient } from '@tanstack/react-query';
import {
    ChevronLeft,
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
import { useAuthStore } from '@/stores';
import {
    useProfile,
    useProfileStatistics,
    useUpdateProfile,
    useChangePassword,
    useUploadAvatar,
} from '@/hooks/use-profile';
import { useCameraCapture } from '@/hooks/use-camera-capture';
import { useFaceData, useDeleteFace, faceRecognitionKeys } from '@/hooks/use-face-recognition-api';
import { extractEmbeddingDeepFace, registerFace, updateFace } from '@/lib/api/face-recognition';
import { Button } from '@/components/ui/button';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
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
    const queryClient = useQueryClient();
    const { logout } = useAuthStore();
    const { data: profile, isLoading } = useProfile();
    const { data: statistics } = useProfileStatistics();
    const updateProfile = useUpdateProfile();
    const changePassword = useChangePassword();
    const uploadAvatar = useUploadAvatar();

    // Face recognition hooks
    const { data: faceData, refetch: refetchFaceData } = useFaceData(profile?.employee_pk || '');
    const deleteFaceMutation = useDeleteFace();
    const {
        videoRef,
        errorMessage,
        startCamera,
        stopCamera,
        captureImage,
    } = useCameraCapture();

    const fileInputRef = useRef<HTMLInputElement>(null);

    // State
    const [isEditOpen, setIsEditOpen] = useState(false);
    const [isPasswordOpen, setIsPasswordOpen] = useState(false);
    const [isLogoutOpen, setIsLogoutOpen] = useState(false);
    const [isFaceEnrollmentOpen, setIsFaceEnrollmentOpen] = useState(false);
    const [isDeleteFaceOpen, setIsDeleteFaceOpen] = useState(false);
    const [enrollmentStep, setEnrollmentStep] = useState<'idle' | 'ready' | 'countdown' | 'processing' | 'success' | 'error'>('idle');
    const [enrollmentError, setEnrollmentError] = useState<string | null>(null);
    const [countdown, setCountdown] = useState<number>(3);
    const [registrationAction, setRegistrationAction] = useState<'register' | 'update' | null>(null);

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

    // Auto-refetch face data when profile loads to ensure fresh data
    useEffect(() => {
        if (profile?.employee_pk) {
            console.log('Profile loaded, employee_pk:', profile.employee_pk);
            console.log('Fetching face data for employee:', profile.employee_pk);
            refetchFaceData();
        }
    }, [profile?.employee_pk, refetchFaceData]);

    // Auto-countdown effect for face enrollment
    useEffect(() => {
        let timer: ReturnType<typeof setTimeout>;
        if (enrollmentStep === 'countdown' && countdown > 0) {
            timer = setTimeout(() => {
                setCountdown(prev => prev - 1);
            }, 1000);
        } else if (enrollmentStep === 'countdown' && countdown === 0) {
            handleCaptureFace(); // Trigger capture when countdown finishes
        }
        return () => clearTimeout(timer);
    }, [enrollmentStep, countdown]); // handleCaptureFace is defined below, so it's not a dependency here

    // Face enrollment handlers
    const handleStartFaceEnrollment = async () => {
        setIsFaceEnrollmentOpen(true);
        setEnrollmentStep('ready');
        setEnrollmentError(null);
        setCountdown(3); // Reset countdown

        try {
            await startCamera();
        } catch (error) {
            console.error('Face Registration: Camera error:', error);
            setEnrollmentStep('error');
            setEnrollmentError(errorMessage || 'Gagal mengakses kamera');
        }
    };

    const handleCaptureNow = () => {
        setEnrollmentStep('countdown');
    };

    const handleCaptureFace = async () => {
        if (!profile?.employee_pk) {
            console.error('Face Registration: Missing employee_pk');
            return;
        }

        console.log('Face Registration: Starting capture for employee:', profile.employee_pk);
        setEnrollmentStep('processing');
        setEnrollmentError(null);

        try {
            // Capture image from video
            console.log('Face Registration: Capturing image...');
            const imageFile = await captureImage();
            console.log('Face Registration: Image captured, size:', imageFile.size);

            // Send to DeepFace for processing
            console.log('Face Registration: Sending to DeepFace...');
            const result = await extractEmbeddingDeepFace(imageFile);
            console.log('Face Registration: DeepFace response:', result);

            if (!result.success || !result.embedding) {
                throw new Error(result.message || 'Gagal mengekstrak data wajah');
            }

            // Check image quality from DeepFace
            if (result.quality && !result.quality.quality_ok) {
                throw new Error('Kualitas gambar tidak memenuhi standar. Coba lagi dengan pencahayaan lebih baik.');
            }

            console.log('Face Registration: Saving to database...');

            // Check if face is already registered, use update instead of register
            const hasFaceData = faceData?.has_face_data;
            console.log('Face Registration: Has existing face data:', hasFaceData);

            // Set registration action for success message
            const action = hasFaceData ? 'update' : 'register';
            setRegistrationAction(action);

            let saveResult;
            if (hasFaceData) {
                // Update existing face data
                console.log('Face Registration: Updating existing face data...');
                saveResult = await updateFace({
                    employee_id: profile.employee_pk,
                    descriptor: result.embedding,
                    confidence: result.confidence || 0.95,
                });
            } else {
                // Register new face data
                console.log('Face Registration: Registering new face data...');
                saveResult = await registerFace({
                    employee_id: profile.employee_pk,
                    descriptor: result.embedding,
                    confidence: result.confidence || 0.95,
                    algorithm: 'deepface-arcface',
                    model_version: result.model || 'ArcFace',
                });
            }

            if (!saveResult.success) {
                throw new Error(saveResult.message || 'Gagal menyimpan data wajah');
            }

            console.log('Face Registration: Successful!', action === 'update' ? '(Updated)' : '(Registered)');
            setEnrollmentStep('success');

            // Force invalidate and refetch face data
            console.log('Face Registration: Invalidating and refetching face data for employee:', profile.employee_pk);
            await queryClient.invalidateQueries({
                queryKey: faceRecognitionKeys.faceData(profile.employee_pk)
            });
            await refetchFaceData();

            // Increase delay to 3 seconds to ensure UI updates
            setTimeout(() => {
                handleCloseFaceEnrollment();
            }, 3000);
        } catch (error) {
            console.error('Face Registration: Error:', error);
            setEnrollmentStep('error');
            setEnrollmentError(error instanceof Error ? error.message : 'Gagal mendaftarkan wajah');
        }
    };

    const handleCloseFaceEnrollment = () => {
        stopCamera();
        setIsFaceEnrollmentOpen(false);
        setEnrollmentStep('idle');
        setEnrollmentError(null);
        setCountdown(3);
    };

    const handleDeleteFace = async () => {
        if (!profile?.employee_pk) return;

        try {
            await deleteFaceMutation.mutateAsync(profile.employee_pk);
            setIsDeleteFaceOpen(false);
            await refetchFaceData();
        } catch {
            // Error handled by mutation
        }
    };

    // Cleanup on unmount
    useEffect(() => {
        return () => {
            stopCamera();
        };
    }, []);

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
                            onClick={() => navigate({ to: '/employee/dashboard' })}
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

                    <div className="p-4 border-b border-border/50">
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-3">
                                <div className={cn(
                                    "h-9 w-9 rounded-full flex items-center justify-center",
                                    faceData?.has_face_data
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
                            {faceData?.has_face_data && (
                                <button
                                    onClick={(e: React.MouseEvent) => {
                                        e.stopPropagation();
                                        setIsDeleteFaceOpen(true);
                                    }}
                                    className="p-1.5 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-full transition-colors"
                                >
                                    <Trash2 className="h-4 w-4 text-red-500" />
                                </button>
                            )}
                        </div>

                        {/* Status Info */}
                        <div className="mt-3">
                            {faceData?.has_face_data ? (
                                <div className="bg-emerald-50 dark:bg-emerald-900/10 border border-emerald-200 dark:border-emerald-800 rounded-lg p-3 space-y-2">
                                    <div className="flex items-center gap-2">
                                        <ShieldCheck className="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                                        <span className="text-sm font-medium text-emerald-700 dark:text-emerald-300">
                                            Data wajah Anda sudah terdaftar
                                        </span>
                                    </div>
                                    {faceData.face_data && (
                                        <div className="text-xs text-emerald-600 dark:text-emerald-400 space-y-1 pl-6">
                                            {faceData.face_data.algorithm && (
                                                <div>Model: {faceData.face_data.algorithm}</div>
                                            )}
                                            {faceData.face_data.confidence && (
                                                <div>Confidence: {(faceData.face_data.confidence * 100).toFixed(1)}%</div>
                                            )}
                                            {faceData.face_data.registered_at && (
                                                <div>Terdaftar: {new Date(faceData.face_data.registered_at).toLocaleDateString('id-ID')}</div>
                                            )}
                                        </div>
                                    )}
                                    <Button
                                        onClick={handleStartFaceEnrollment}
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
                                        onClick={handleStartFaceEnrollment}
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
            </div >

            {/* Edit Profile Drawer */}
            < Drawer open={isEditOpen} onOpenChange={setIsEditOpen} >
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
                                onChange={(e: React.ChangeEvent<HTMLInputElement>) => setFormData({ ...formData, name: e.target.value })}
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="email">Email</Label>
                            <Input
                                id="email"
                                type="email"
                                value={formData.email}
                                onChange={(e: React.ChangeEvent<HTMLInputElement>) => setFormData({ ...formData, email: e.target.value })}
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="phone">No. Telepon</Label>
                            <Input
                                id="phone"
                                value={formData.phone}
                                onChange={(e: React.ChangeEvent<HTMLInputElement>) => setFormData({ ...formData, phone: e.target.value })}
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
            </Drawer >

            {/* Change Password Drawer */}
            < Drawer open={isPasswordOpen} onOpenChange={setIsPasswordOpen} >
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
                                onChange={(e: React.ChangeEvent<HTMLInputElement>) => setPasswordForm({ ...passwordForm, current_password: e.target.value })}
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="new_password">Password Baru</Label>
                            <Input
                                id="new_password"
                                type="password"
                                value={passwordForm.password}
                                onChange={(e: React.ChangeEvent<HTMLInputElement>) => setPasswordForm({ ...passwordForm, password: e.target.value })}
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="confirm_password">Konfirmasi Password</Label>
                            <Input
                                id="confirm_password"
                                type="password"
                                value={passwordForm.password_confirmation}
                                onChange={(e: React.ChangeEvent<HTMLInputElement>) => setPasswordForm({ ...passwordForm, password_confirmation: e.target.value })}
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
            < Drawer open={isFaceEnrollmentOpen} onOpenChange={(open: boolean) => !open && handleCloseFaceEnrollment()
            }>
                <DrawerContent>
                    <DrawerHeader>
                        <DrawerTitle>
                            {faceData?.has_face_data ? 'Perbarui Data Wajah' : 'Daftar Wajah'} - DeepFace ArcFace
                        </DrawerTitle>
                        <DrawerDescription>
                            {enrollmentStep === 'success'
                                ? faceData?.has_face_data
                                    ? 'Data wajah Anda berhasil diperbarui!'
                                    : 'Wajah Anda berhasil didaftarkan!'
                                : faceData?.has_face_data
                                    ? 'Perbarui data wajah untuk meningkatkan akurasi pengenalan saat absensi.'
                                    : 'Posisikan wajah Anda di tengah, sistem akan mengambil gambar otomatis.'}
                        </DrawerDescription>
                    </DrawerHeader>

                    <div className="p-4 space-y-4">
                        {/* Camera Preview */}
                        <div className="relative w-full aspect-[4/3] bg-black rounded-xl overflow-hidden">
                            <video
                                ref={videoRef}
                                autoPlay
                                playsInline
                                muted
                                className="w-full h-full object-cover"
                                style={{ transform: 'scaleX(-1)' }}
                            />


                            {/* Ready State - Show Capture Button */}
                            {enrollmentStep === 'ready' && (
                                <div className="absolute inset-0 flex items-center justify-center">
                                    <div className="absolute top-4 left-1/2 transform -translate-x-1/2">
                                        <div className="bg-blue-500/90 text-white px-4 py-2 rounded-full text-sm font-medium shadow-lg">
                                            👤 Posisikan wajah Anda di tengah
                                        </div>
                                    </div>
                                </div>
                            )}

                            {/* Countdown State */}
                            {enrollmentStep === 'countdown' && (
                                <div className="absolute inset-0 flex items-center justify-center bg-black/40">
                                    <div className="text-center">
                                        <div className="bg-white rounded-full h-32 w-32 flex items-center justify-center border-4 border-emerald-500 shadow-2xl">
                                            <span className="text-6xl font-bold text-emerald-600">{countdown}</span>
                                        </div>
                                        <p className="text-white text-lg font-semibold mt-4">Tahan posisi...</p>
                                    </div>
                                </div>
                            )}

                            {/* Processing Overlay */}
                            {enrollmentStep === 'processing' && (
                                <div className="absolute inset-0 flex items-center justify-center bg-black/80">
                                    <div className="text-center text-white">
                                        <Loader2 className="h-12 w-12 mx-auto mb-3 animate-spin" />
                                        <p className="text-lg font-semibold">Memproses dengan DeepFace...</p>
                                        <p className="text-sm mt-1 text-white/70">Mengekstrak 512-d embedding</p>
                                    </div>
                                </div>
                            )}

                            {/* Success Overlay */}
                            {enrollmentStep === 'success' && (
                                <div className="absolute inset-0 flex items-center justify-center bg-emerald-500/95">
                                    <div className="text-center text-white">
                                        <CheckCircle2 className="h-16 w-16 mx-auto mb-3" />
                                        <p className="text-2xl font-bold">Berhasil!</p>
                                        <p className="text-sm mt-2">
                                            {registrationAction === 'update' ? 'Data wajah telah diperbarui' : 'Wajah Anda telah terdaftar'}
                                        </p>
                                        <p className="text-xs mt-1 text-white/80">ArcFace 512-d {registrationAction === 'update' ? 'updated' : 'registered'}</p>
                                    </div>
                                </div>
                            )}

                            {/* Error Overlay */}
                            {enrollmentStep === 'error' && (
                                <div className="absolute inset-0 flex items-center justify-center bg-red-500/95">
                                    <div className="text-center text-white px-6">
                                        <XCircle className="h-16 w-16 mx-auto mb-3" />
                                        <p className="text-2xl font-bold">Gagal!</p>
                                        {enrollmentError && (
                                            <p className="text-sm mt-2">{enrollmentError}</p>
                                        )}
                                        <p className="text-xs mt-2 text-white/80">Mencoba lagi dalam 3 detik...</p>
                                    </div>
                                </div>
                            )}
                        </div>

                        {/* Instructions */}
                        {enrollmentStep === 'ready' && (
                            <Alert>
                                <AlertTriangle className="h-4 w-4" />
                                <AlertDescription>
                                    <strong>Panduan:</strong>
                                    <ul className="list-disc list-inside mt-1 text-sm space-y-1">
                                        <li>Posisikan wajah di tengah kamera</li>
                                        <li>Pastikan pencahayaan cukup terang</li>
                                        <li>Tekan tombol "Mulai" untuk countdown 3 detik</li>
                                        <li>Tahan posisi saat countdown berlangsung</li>
                                    </ul>
                                </AlertDescription>
                            </Alert>
                        )}
                    </div>

                    <DrawerFooter>
                        {enrollmentStep === 'ready' && (
                            <Button
                                onClick={handleCaptureNow}
                                className="bg-emerald-600 hover:bg-emerald-700 w-full"
                                size="lg"
                            >
                                <Camera className="h-5 w-5 mr-2" />
                                Mulai (3 Detik Countdown)
                            </Button>
                        )}
                        {enrollmentStep === 'error' && (
                            <Button
                                onClick={handleStartFaceEnrollment}
                                variant="destructive"
                                className="w-full"
                            >
                                <RefreshCw className="h-4 w-4 mr-2" />
                                Coba Lagi
                            </Button>
                        )}
                        <DrawerClose asChild>
                            <Button
                                variant="outline"
                                onClick={handleCloseFaceEnrollment}
                                disabled={enrollmentStep === 'processing' || enrollmentStep === 'countdown'}
                                className="w-full"
                            >
                                {enrollmentStep === 'success' ? 'Selesai' : 'Batal'}
                            </Button>
                        </DrawerClose>
                    </DrawerFooter>
                </DrawerContent>
            </Drawer >

            {/* Delete Face Alert */}
            < AlertDialog open={isDeleteFaceOpen} onOpenChange={setIsDeleteFaceOpen} >
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
                            onClick={handleDeleteFace}
                            disabled={deleteFaceMutation.isPending}
                            className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                        >
                            {deleteFaceMutation.isPending ? (
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
