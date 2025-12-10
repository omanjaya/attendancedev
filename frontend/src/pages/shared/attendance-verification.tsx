import React, { useState, useEffect, useCallback } from 'react';
import { useNavigate, useSearch } from '@tanstack/react-router';
import {
    MapPin,
    Loader2,
    CheckCircle2,
    XCircle,
    ArrowLeft,
    Camera,
    Navigation,
    User,
    AlertTriangle,
    ChevronRight,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Progress } from '@/components/ui/progress';
import { verifyLocation } from '@/lib/api/attendance';
import { checkIn, checkOut } from '@/lib/api/attendance';
import { useAuthStore } from '@/stores';
import { AutoCaptureFace } from '@/components/attendance/auto-capture-face';
import { cn } from '@/lib/utils';
import { useQuery } from '@tanstack/react-query';
import { getEmployeeDashboardData } from '@/lib/api/employees';
import type { CheckRequest } from '@/types/attendance';

type Step = 'location' | 'location_verified' | 'face' | 'submitting' | 'success' | 'error';

interface VerificationState {
    step: Step;
    // Location
    locationLoading: boolean;
    locationVerified: boolean;
    locationError: string | null;
    distance: number | null;
    maxRadius: number | null;
    locationName: string | null;
    latitude: number | null;
    longitude: number | null;
    // Face
    faceVerified: boolean;
    faceError: string | null;
    employeeName: string | null;
    confidence: number | null;
    // General
    message: string;
    progress: number;
}

export function AttendanceVerificationPage() {
    const navigate = useNavigate();
    const { user } = useAuthStore();
    const search = useSearch({ strict: false }) as {
        type?: 'check-in' | 'check-out';
        overwrite?: boolean;
    };
    const type = search.type || 'check-in';
    const overwrite = search.overwrite;

    const [state, setState] = useState<VerificationState>({
        step: 'location',
        locationLoading: true,
        locationVerified: false,
        locationError: null,
        distance: null,
        maxRadius: null,
        locationName: null,
        latitude: null,
        longitude: null,
        faceVerified: false,
        faceError: null,
        employeeName: null,
        confidence: null,
        message: 'Memeriksa jadwal...',
        progress: 5,
    });

    const [countdown, setCountdown] = useState(3);
    const [showOverwriteConfirm, setShowOverwriteConfirm] = useState(false);
    const [scheduleChecked, setScheduleChecked] = useState(false);

    // Use ref to store location data that persists across re-renders
    // This ensures location is available when submitAttendance is called
    const locationRef = React.useRef<{ latitude: number; longitude: number } | null>(null);

    // Get today's date for cache key (ensures fresh data each day)
    const today = new Date().toISOString().split('T')[0]; // YYYY-MM-DD format

    // Fetch schedule data to validate if employee can attend
    const { data: dashboardData, isLoading: isLoadingSchedule } = useQuery({
        queryKey: ['employee', 'dashboard-stats', user?.id, today],
        queryFn: getEmployeeDashboardData,
        enabled: !!user?.id,
        staleTime: 0, // Always refetch to ensure fresh schedule data
    });

    // Check schedule before starting location verification
    useEffect(() => {
        // Wait until dashboard data is loaded
        if (isLoadingSchedule) return;
        // Only check once
        if (scheduleChecked) return;
        
        // IMPORTANT: Default to FALSE (cannot attend) if data not available
        // This ensures we don't allow attendance without proper schedule validation
        const canAttend = dashboardData?.schedule?.today?.can_attend === true;
        const scheduleMessage = dashboardData?.schedule?.today?.message;
        const scheduleType = dashboardData?.schedule?.today?.schedule_type;

        if (!canAttend) {
            let errorMessage = scheduleMessage || 'Tidak memiliki jadwal untuk absen hari ini';
            
            // Provide more specific messages based on schedule type
            if (scheduleType === 'none') {
                errorMessage = 'Tidak ada jadwal yang di-assign untuk hari ini';
            } else if (scheduleType === 'holiday') {
                errorMessage = scheduleMessage || 'Hari ini adalah hari libur';
            } else if (scheduleType === 'no_teaching') {
                errorMessage = 'Tidak ada jadwal mengajar hari ini';
            }
            
            setState(prev => ({
                ...prev,
                step: 'error',
                locationError: errorMessage,
                message: 'Tidak Ada Jadwal',
                progress: 0,
                locationLoading: false,
            }));
        } else {
            setState(prev => ({
                ...prev,
                message: 'Mencari lokasi GPS...',
                progress: 10,
            }));
        }
        setScheduleChecked(true);
    }, [isLoadingSchedule, dashboardData, scheduleChecked]);

    // Get user's current location and verify
    useEffect(() => {
        if (state.step !== 'location' || !scheduleChecked || isLoadingSchedule) return;

        if ('geolocation' in navigator) {
            navigator.geolocation.getCurrentPosition(
                async (position) => {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;

                    // Store location in ref for later use (ensures availability in submitAttendance)
                    locationRef.current = { latitude: lat, longitude: lng };

                    setState(prev => ({
                        ...prev,
                        latitude: lat,
                        longitude: lng,
                        message: 'Memverifikasi radius kantor...',
                        progress: 25,
                    }));

                    try {
                        const result = await verifyLocation({ latitude: lat, longitude: lng });

                        if (result.verified) {
                            setState(prev => ({
                                ...prev,
                                locationLoading: false,
                                locationVerified: true,
                                distance: result.distance,
                                maxRadius: result.location?.radius_meters || null,
                                locationName: result.location?.name || null,
                                message: 'Lokasi terverifikasi!',
                                progress: 40,
                                step: 'location_verified', // Show button instead of auto-transition
                            }));
                        } else {
                            setState(prev => ({
                                ...prev,
                                locationLoading: false,
                                locationError: `Anda ${result.distance.toFixed(0)}m dari kantor (maks ${result.location?.radius_meters}m)`,
                                step: 'error',
                                message: 'Di luar jangkauan',
                                progress: 25,
                            }));
                        }
                    } catch (error: any) {
                        setState(prev => ({
                            ...prev,
                            locationLoading: false,
                            locationError: error.response?.data?.message || 'Gagal verifikasi lokasi',
                            step: 'error',
                            message: 'Error lokasi',
                            progress: 25,
                        }));
                    }
                },
                (error) => {
                    console.error('GPS Error', error);
                    setState(prev => ({
                        ...prev,
                        locationLoading: false,
                        locationError: 'GPS tidak aktif. Pastikan lokasi dan izin GPS diaktifkan.',
                        step: 'error',
                        message: 'GPS error',
                        progress: 10,
                    }));
                },
                { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
            );
        } else {
            setState(prev => ({
                ...prev,
                locationError: 'Browser tidak mendukung GPS',
                step: 'error',
            }));
        }
    }, [state.step, scheduleChecked, isLoadingSchedule]);

    // Handler for "Lanjut" button after location verified - request camera permission first
    const handleContinueToFace = useCallback(async () => {
        setState(prev => ({
            ...prev,
            message: 'Meminta izin kamera...',
            progress: 45,
        }));

        try {
            // Request camera permission before proceeding
            const stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'user' },
                audio: false
            });

            // Permission granted - stop the test stream
            stream.getTracks().forEach(track => track.stop());

            // Now proceed to face step
            setState(prev => ({
                ...prev,
                step: 'face',
                message: 'Silakan Senyum 😊',
                progress: 50,
            }));
        } catch (err) {
            console.error('Camera permission error:', err);

            let errorMessage = 'Gagal mengakses kamera';
            if (err instanceof Error) {
                if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
                    errorMessage = 'Izin kamera ditolak. Silakan aktifkan izin kamera di pengaturan browser.';
                } else if (err.name === 'NotFoundError') {
                    errorMessage = 'Kamera tidak ditemukan pada perangkat ini.';
                } else if (err.name === 'NotReadableError') {
                    errorMessage = 'Kamera sedang digunakan aplikasi lain.';
                }
            }

            setState(prev => ({
                ...prev,
                step: 'error',
                faceError: errorMessage,
                message: 'Error kamera',
                progress: 40,
            }));
        }
    }, []);

    // Face captured handler - verify face with DeepFace after liveness check
    const onFaceCaptured = useCallback(async (videoElement: HTMLVideoElement) => {
        // Safety check - ensure video is ready
        if (!videoElement.videoWidth || !videoElement.videoHeight) {
            setState(prev => ({
                ...prev,
                step: 'error',
                faceError: 'Kamera belum siap. Silakan coba lagi.',
                message: 'Error kamera',
            }));
            return;
        }

        setState(prev => ({
            ...prev,
            message: 'Memverifikasi wajah...',
            progress: 70,
        }));

        const canvas = document.createElement('canvas');
        canvas.width = videoElement.videoWidth;
        canvas.height = videoElement.videoHeight;
        const ctx = canvas.getContext('2d');

        if (!ctx) {
            setState(prev => ({
                ...prev,
                step: 'error',
                faceError: 'Gagal memproses gambar',
                message: 'Error kamera',
            }));
            return;
        }

        ctx.drawImage(videoElement, 0, 0);

        canvas.toBlob(async (blob) => {
            if (!blob) {
                setState(prev => ({
                    ...prev,
                    step: 'error',
                    faceError: 'Gagal mengambil gambar',
                    message: 'Error capture',
                }));
                return;
            }

            const imageFile = new File([blob], 'capture.jpg', { type: 'image/jpeg' });

            try {
                // Import and call verifyFaceDeepFace
                const { verifyFaceDeepFace } = await import('@/lib/api/face-recognition');
                const result = await verifyFaceDeepFace(imageFile);
                const resultData = result.data || result;
                const employee = result.employee || resultData.employee || resultData.employee_data;
                const isMatched = result.matched || resultData.matched;

                if (result.success && isMatched && employee) {
                    setState(prev => ({
                        ...prev,
                        faceVerified: true,
                        employeeName: employee.name || employee.full_name,
                        confidence: (result.confidence || resultData.confidence) ?? null,
                        message: 'Wajah terverifikasi!',
                        progress: 90,
                    }));

                    // Auto submit after face verified
                    setTimeout(() => submitAttendance(), 500);
                } else {
                    setState(prev => ({
                        ...prev,
                        step: 'error',
                        faceError: result.message || 'Wajah tidak dikenali',
                        message: 'Verifikasi gagal',
                        progress: 60,
                    }));
                }
            } catch (error: any) {
                setState(prev => ({
                    ...prev,
                    step: 'error',
                    faceError: error.response?.data?.message || 'Gagal verifikasi wajah',
                    message: 'Error verifikasi',
                    progress: 60,
                }));
            }
        }, 'image/jpeg', 0.95);
    }, []);

    // Submit attendance - use stored location from state (no need to re-fetch GPS)
    const submitAttendance = async (forceOverwrite: boolean = false) => {
        setState(prev => ({
            ...prev,
            step: 'submitting',
            message: 'Menyimpan absensi...',
            progress: 95,
        }));

        if (!user?.employee?.id) {
            setState(prev => ({
                ...prev,
                step: 'error',
                faceError: 'Data karyawan tidak ditemukan',
                message: 'Error data',
            }));
            return;
        }

        // Use location from ref (more reliable) or fallback to state
        const location = locationRef.current || { latitude: state.latitude, longitude: state.longitude };

        if (!location.latitude || !location.longitude) {
            setState(prev => ({
                ...prev,
                step: 'error',
                faceError: 'Data lokasi tidak tersedia',
                message: 'Error lokasi',
            }));
            return;
        }

        try {
            const attendanceData: CheckRequest = {
                employee_id: user.employee.id,
                action: type === 'check-in' ? 'check_in' : 'check_out',
                location: {
                    latitude: location.latitude,
                    longitude: location.longitude,
                },
                type: type === 'check-in' ? 'check_in' : 'check_out',
                face_confidence: state.confidence ?? 0,
                latitude: location.latitude,
                longitude: location.longitude,
                notes: `${type === 'check-in' ? 'Check-in' : 'Check-out'} via unified verification`,
                metadata: {
                    device: navigator.userAgent,
                    platform: navigator.platform,
                    type: type,
                },
                overwrite: forceOverwrite || overwrite,
            };

            if (type === 'check-in') {
                await checkIn(attendanceData);
            } else {
                await checkOut(attendanceData);
            }

            setState(prev => ({
                ...prev,
                step: 'success',
                message: type === 'check-in' ? 'Absen Datang Berhasil!' : 'Absen Pulang Berhasil!',
                progress: 100,
            }));

            // No auto countdown - user clicks "Selesai"
        } catch (error: any) {
            const errorMessage = error.response?.data?.message || 'Gagal menyimpan absensi';

            if (errorMessage.includes('Already checked')) {
                setShowOverwriteConfirm(true);
                setState(prev => ({
                    ...prev,
                    step: 'face', // Go back to allow retry
                    message: 'Konfirmasi absen ulang',
                    progress: 90,
                }));
            } else {
                setState(prev => ({
                    ...prev,
                    step: 'error',
                    faceError: errorMessage,
                    message: 'Gagal simpan',
                }));
            }
        }
    };

    // Countdown for success - auto navigate when countdown reaches 0
    useEffect(() => {
        if (state.step === 'success') {
            if (countdown > 0) {
                const timer = setTimeout(() => setCountdown(countdown - 1), 1000);
                return () => clearTimeout(timer);
            } else {
                // Auto navigate when countdown finishes
                handleBack();
            }
        }
    }, [state.step, countdown]);

    const handleBack = () => {
        const isAdmin = user?.role === 'admin' || user?.role === 'super-admin' || user?.role === 'kepala-sekolah';
        navigate({ to: isAdmin ? '/admin/attendance' : '/employee/attendance' });
    };

    const handleRetry = () => {
        setState({
            step: 'location',
            locationLoading: true,
            locationVerified: false,
            locationError: null,
            distance: null,
            maxRadius: null,
            locationName: null,
            latitude: null,
            longitude: null,
            faceVerified: false,
            faceError: null,
            employeeName: null,
            confidence: null,
            message: 'Mencari lokasi GPS...',
            progress: 10,
        });
        setShowOverwriteConfirm(false);
    };

    const handleForceOverwrite = () => {
        setShowOverwriteConfirm(false);
        submitAttendance(true);
    };

    const stepNumber = state.step === 'location' || state.step === 'location_verified' ? 1 : state.step === 'face' ? 2 : 3;

    return (
        <div className="min-h-screen bg-gradient-to-b from-gray-900 via-gray-900 to-black flex flex-col">
            {/* Compact Header */}
            <div className="px-4 pt-4 pb-2">
                <div className="flex items-center justify-between">
                    <Button
                        size="icon"
                        variant="ghost"
                        className="h-10 w-10 rounded-full text-white/80 hover:text-white hover:bg-white/10"
                        onClick={handleBack}
                    >
                        <ArrowLeft className="h-5 w-5" />
                    </Button>

                    <div className="text-center flex-1">
                        <h1 className="text-white font-bold text-sm">
                            {type === 'check-in' ? 'Absen Datang' : 'Absen Pulang'}
                        </h1>
                    </div>

                    <div className="w-10" /> {/* Spacer */}
                </div>

                {/* Step Indicator */}
                <div className="flex items-center justify-center gap-2 mt-3">
                    {[1, 2, 3].map((num) => (
                        <div key={num} className="flex items-center gap-2">
                            <div
                                className={cn(
                                    'h-7 w-7 rounded-full flex items-center justify-center text-xs font-bold transition-all',
                                    stepNumber >= num
                                        ? stepNumber === num
                                            ? 'bg-emerald-500 text-white scale-110'
                                            : 'bg-emerald-500/80 text-white'
                                        : 'bg-white/10 text-white/40'
                                )}
                            >
                                {stepNumber > num ? <CheckCircle2 className="h-4 w-4" /> : num}
                            </div>
                            {num < 3 && (
                                <div className={cn(
                                    'h-0.5 w-6 rounded transition-all',
                                    stepNumber > num ? 'bg-emerald-500' : 'bg-white/10'
                                )} />
                            )}
                        </div>
                    ))}
                </div>

                <div className="flex justify-center gap-8 mt-2 text-[10px] text-white/50">
                    <span className={cn(stepNumber >= 1 && 'text-white/80')}>Lokasi</span>
                    <span className={cn(stepNumber >= 2 && 'text-white/80')}>Wajah</span>
                    <span className={cn(stepNumber >= 3 && 'text-white/80')}>Selesai</span>
                </div>
            </div>

            {/* Progress Bar */}
            <div className="px-4 pb-2">
                <Progress value={state.progress} className="h-1 bg-white/10" />
            </div>

            {/* Main Content Area */}
            <div className="flex-1 flex flex-col">
                {/* Location Step */}
                {state.step === 'location' && (
                    <div className="flex-1 flex flex-col items-center justify-center px-6 text-center">
                        <div className="relative">
                            <div className="h-24 w-24 rounded-full bg-emerald-500/20 flex items-center justify-center animate-pulse">
                                <MapPin className="h-12 w-12 text-emerald-400" />
                            </div>
                            {state.locationLoading && (
                                <div className="absolute inset-0 flex items-center justify-center">
                                    <Loader2 className="h-8 w-8 text-white animate-spin" />
                                </div>
                            )}
                        </div>
                        <p className="text-white font-medium mt-6">{state.message}</p>
                        <p className="text-white/50 text-sm mt-2">Pastikan GPS aktif</p>
                    </div>
                )}

                {/* Location Verified Step - Show "Lanjut" button */}
                {state.step === 'location_verified' && (
                    <div className="flex-1 flex flex-col items-center justify-center px-6 text-center">
                        <div className="h-24 w-24 rounded-full bg-emerald-500 flex items-center justify-center animate-in zoom-in">
                            <CheckCircle2 className="h-12 w-12 text-white" />
                        </div>
                        <h2 className="text-white font-bold text-xl mt-6">Lokasi Terverifikasi</h2>
                        <div className="flex items-center gap-2 mt-3 bg-white/10 px-4 py-2 rounded-full">
                            <MapPin className="h-4 w-4 text-emerald-400" />
                            <span className="text-emerald-400 text-sm">
                                {state.locationName} • {state.distance?.toFixed(0)}m
                            </span>
                        </div>
                        <p className="text-white/50 text-sm mt-4 max-w-xs">
                            Lanjutkan untuk verifikasi liveness dengan senyuman
                        </p>
                        <Button
                            className="mt-6 bg-emerald-600 hover:bg-emerald-700 px-8 py-3 text-lg"
                            onClick={handleContinueToFace}
                        >
                            Lanjut
                            <ChevronRight className="ml-2 h-5 w-5" />
                        </Button>
                    </div>
                )}

                {/* Face Step */}
                {state.step === 'face' && !showOverwriteConfirm && (
                    <div className="flex-1 flex flex-col px-4">
                        {/* Location Badge */}
                        <div className="flex items-center justify-center gap-2 py-2">
                            <CheckCircle2 className="h-4 w-4 text-emerald-400" />
                            <span className="text-xs text-emerald-400">
                                {state.locationName} • {state.distance?.toFixed(0)}m
                            </span>
                        </div>

                        {/* Camera Area */}
                        <div className="flex-1 relative rounded-2xl overflow-hidden bg-black">
                            <AutoCaptureFace
                                onCapture={onFaceCaptured}
                                onError={(err) => setState(prev => ({
                                    ...prev,
                                    step: 'error',
                                    faceError: err,
                                    message: 'Kamera error',
                                }))}
                                autoCapture={true}
                                className="w-full h-full"
                            />
                        </div>

                        <p className="text-center text-white/80 text-sm py-4">{state.message}</p>
                    </div>
                )}

                {/* Overwrite Confirm */}
                {showOverwriteConfirm && (
                    <div className="flex-1 flex flex-col items-center justify-center px-6 text-center">
                        <div className="h-20 w-20 rounded-full bg-yellow-500/20 flex items-center justify-center">
                            <AlertTriangle className="h-10 w-10 text-yellow-400" />
                        </div>
                        <h2 className="text-white font-bold text-xl mt-6">Sudah Absen</h2>
                        <p className="text-white/60 text-sm mt-2">Ingin absen ulang? Data sebelumnya akan diganti.</p>
                        <div className="flex gap-3 mt-6 w-full max-w-xs">
                            <Button variant="outline" className="flex-1 border-white/20 text-white" onClick={handleBack}>
                                Batal
                            </Button>
                            <Button className="flex-1 bg-yellow-600 hover:bg-yellow-700" onClick={handleForceOverwrite}>
                                Lanjut
                            </Button>
                        </div>
                    </div>
                )}

                {/* Submitting Step */}
                {state.step === 'submitting' && (
                    <div className="flex-1 flex flex-col items-center justify-center px-6 text-center">
                        <Loader2 className="h-16 w-16 text-emerald-400 animate-spin" />
                        <p className="text-white font-medium mt-6">{state.message}</p>
                    </div>
                )}

                {/* Success Step */}
                {state.step === 'success' && (
                    <div className="flex-1 flex flex-col items-center justify-center px-6 text-center">
                        <div className="h-24 w-24 rounded-full bg-emerald-500 flex items-center justify-center animate-in zoom-in">
                            <CheckCircle2 className="h-14 w-14 text-white" />
                        </div>
                        <h2 className="text-white font-bold text-2xl mt-6">{state.message}</h2>
                        {state.employeeName && (
                            <div className="flex items-center gap-2 mt-4 bg-white/10 px-4 py-2 rounded-full">
                                <User className="h-4 w-4 text-white/70" />
                                <span className="text-white/90 text-sm">{state.employeeName}</span>
                            </div>
                        )}
                        <p className="text-white/50 text-sm mt-4">Kembali dalam {countdown}...</p>
                        <Button variant="outline" className="mt-6 border-white/20 text-white" onClick={handleBack}>
                            Selesai
                        </Button>
                    </div>
                )}

                {/* Error Step */}
                {state.step === 'error' && (
                    <div className="flex-1 flex flex-col items-center justify-center px-6 text-center">
                        <div className="h-20 w-20 rounded-full bg-red-500/20 flex items-center justify-center">
                            <XCircle className="h-10 w-10 text-red-400" />
                        </div>
                        <h2 className="text-white font-bold text-lg mt-6">{state.message}</h2>
                        <p className="text-white/50 text-sm mt-2 max-w-xs">
                            {state.locationError || state.faceError || 'Terjadi kesalahan'}
                        </p>
                        <div className="flex gap-3 mt-6">
                            <Button variant="outline" className="border-white/20 text-white" onClick={handleBack}>
                                Kembali
                            </Button>
                            <Button className="bg-emerald-600 hover:bg-emerald-700" onClick={handleRetry}>
                                <Navigation className="h-4 w-4 mr-2" />
                                Coba Lagi
                            </Button>
                        </div>
                    </div>
                )}
            </div>

            {/* Bottom Status */}
            <div className="px-4 py-3 flex items-center justify-center gap-4 text-xs text-white/40">
                <span className="flex items-center gap-1">
                    <MapPin className="h-3 w-3" />
                    GPS
                </span>
                <span className="flex items-center gap-1">
                    <Camera className="h-3 w-3" />
                    Face ID
                </span>
            </div>
        </div>
    );
}

export default AttendanceVerificationPage;
