import { useState, useEffect, useCallback } from 'react';
import {
    LogIn,
    LogOut,
    MapPin,
    ScanFace,
    CheckCircle2,
    XCircle,
    Loader2,
    MapPinOff,
    UserCheck,
    AlertTriangle,
} from 'lucide-react';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Progress } from '@/components/ui/progress';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
} from '@/components/ui/dialog';
import { AutoCaptureFace } from '@/components/attendance/auto-capture-face';
import { useCheckIn, useCheckOut, useTodayAttendance } from '@/hooks';
import { verifyLocation, type LocationVerificationResponse } from '@/lib/api/attendance';
import { faceDetectionService } from '@/lib/services/face-detection';
import { findBestMatch } from '@/lib/api/face-recognition';
import { useRegisteredFaces, useVerifyFace } from '@/hooks/use-face-recognition-api';
import { useFaceStore } from '@/stores';
import type { TodayAttendance } from '@/types';

type CheckMode = 'check_in' | 'check_out';
type FlowStep = 'select' | 'location' | 'face' | 'processing' | 'success' | 'error' | 'confirm_reattend';

interface VerifiedEmployee {
    id: string;
    name: string;
    similarity: number;
}

interface GPSLocation {
    latitude: number;
    longitude: number;
    accuracy: number;
}

export function CheckInFlow() {
    const [currentStep, setCurrentStep] = useState<FlowStep>('select');
    const [checkMode, setCheckMode] = useState<CheckMode>('check_in');
    const [gpsLocation, setGpsLocation] = useState<GPSLocation | null>(null);
    const [locationVerification, setLocationVerification] = useState<LocationVerificationResponse | null>(null);
    const [verifiedEmployee, setVerifiedEmployee] = useState<VerifiedEmployee | null>(null);
    const [errorMessage, setErrorMessage] = useState<string>('');
    const [processingProgress, setProcessingProgress] = useState(0);
    const [existingAttendance, setExistingAttendance] = useState<TodayAttendance | null>(null);
    const [shouldOverwrite, setShouldOverwrite] = useState(false);

    const [currentTime, setCurrentTime] = useState('');
    const [currentDate, setCurrentDate] = useState('');

    // const { user } = useAuthStore();
    const { data: registeredFaces } = useRegisteredFaces();
    const verifyFaceMutation = useVerifyFace();
    const { settings: faceSettings } = useFaceStore();
    const checkInMutation = useCheckIn();
    const checkOutMutation = useCheckOut();
    const { data: todayAttendanceData, refetch: refetchTodayAttendance } = useTodayAttendance();

    // Update time
    useEffect(() => {
        const updateTime = () => {
            const now = new Date();
            setCurrentTime(
                now.toLocaleTimeString('id-ID', {
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                })
            );
            setCurrentDate(
                now.toLocaleDateString('id-ID', {
                    weekday: 'long',
                    day: 'numeric',
                    month: 'long',
                    year: 'numeric',
                })
            );
        };

        updateTime();
        const interval = setInterval(updateTime, 1000);
        return () => clearInterval(interval);
    }, []);

    // Helper to format time
    const formatTime = (timeString?: string) => {
        if (!timeString) return '-';
        try {
            const date = new Date(timeString);
            return date.toLocaleTimeString('id-ID', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
            });
        } catch {
            return timeString;
        }
    };

    const handleModeSelect = async (mode: CheckMode) => {
        // RESET STATE FIRST!
        // This is critical to prevent bypassing the check if user previously confirmed overwrite
        setShouldOverwrite(false);

        setCheckMode(mode);
        setErrorMessage('');
        setProcessingProgress(10);

        try {
            // Use the data already available from the hook first
            let currentAttendance = todayAttendanceData;
            console.log('[DEBUG] Initial todayAttendanceData:', currentAttendance);

            // Only refetch if data is missing, just to be safe
            if (!currentAttendance) {
                console.log('[DEBUG] Data missing, refetching...');
                const { data } = await refetchTodayAttendance();
                currentAttendance = data;
                console.log('[DEBUG] Refetched data:', currentAttendance);
            }

            if (currentAttendance) {
                setExistingAttendance(currentAttendance);

                // Check if already checked in/out based on mode
                // Check if already checked in/out based on mode
                const alreadyAttended = mode === 'check_in'
                    ? (currentAttendance.has_checked_in || !!currentAttendance.attendance?.check_in_time || !!currentAttendance.attendance?.check_in)
                    : (currentAttendance.has_checked_out || !!currentAttendance.attendance?.check_out_time || !!currentAttendance.attendance?.check_out);

                console.log('[DEBUG] Check Mode:', mode);
                console.log('[DEBUG] Already Attended:', alreadyAttended);

                // ALWAYS check validation when starting fresh (clicking the button)
                // We don't care about the previous 'shouldOverwrite' state here because this is a new attempt
                if (alreadyAttended) {
                    console.log('[DEBUG] BLOCKED: Showing confirmation modal');
                    // Show confirmation modal
                    setCurrentStep('confirm_reattend');
                    return;
                }
            } else {
                console.log('[DEBUG] No attendance data found at all');
            }

            console.log('[DEBUG] PROCEEDING to location verification');
            // Proceed with location verification
            setCurrentStep('location');
            await proceedWithLocationVerification();
        } catch (err) {
            console.error('Error checking attendance:', err);
            setErrorMessage(err instanceof Error ? err.message : 'Gagal mengecek data absensi');
            setCurrentStep('error');
        }
    };

    const proceedWithLocationVerification = async () => {
        try {
            const location = await getGPSLocation();
            setGpsLocation(location);
            setProcessingProgress(30);

            // Verify location with backend
            const verification = await verifyLocation({
                latitude: location.latitude,
                longitude: location.longitude,
            });

            setLocationVerification(verification);
            setProcessingProgress(50);

            if (!verification.verified) {
                setErrorMessage(
                    `Anda berada di luar area absensi.Jarak: ${Math.round(verification.distance)}m dari ${verification.location?.name || 'lokasi terdekat'} `
                );
                setCurrentStep('error');
                return;
            }

            // Location verified, proceed to face verification
            setCurrentStep('face');
        } catch (err) {
            console.error('Location error:', err);
            setErrorMessage(err instanceof Error ? err.message : 'Gagal memverifikasi lokasi');
            setCurrentStep('error');
        }
    };

    const getGPSLocation = useCallback((): Promise<GPSLocation> => {
        return new Promise((resolve, reject) => {
            if (!navigator.geolocation) {
                reject(new Error('GPS tidak tersedia di browser ini'));
                return;
            }

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    resolve({
                        latitude: position.coords.latitude,
                        longitude: position.coords.longitude,
                        accuracy: position.coords.accuracy,
                    });
                },
                (error) => {
                    switch (error.code) {
                        case error.PERMISSION_DENIED:
                            reject(new Error('Izin lokasi ditolak. Mohon aktifkan izin lokasi.'));
                            break;
                        case error.POSITION_UNAVAILABLE:
                            reject(new Error('Lokasi tidak tersedia'));
                            break;
                        case error.TIMEOUT:
                            reject(new Error('Waktu permintaan lokasi habis'));
                            break;
                        default:
                            reject(new Error('Gagal mendapatkan lokasi'));
                    }
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
            );
        });
    }, []);

    const handleFaceCapture = async (videoElement: HTMLVideoElement) => {
        setCurrentStep('processing');
        setProcessingProgress(60);

        try {
            // Capture face descriptor
            const faceData = await faceDetectionService.captureFaceDescriptor(
                videoElement
            );

            if (faceData.confidence < 0.6) {
                throw new Error('Kualitas deteksi wajah kurang baik. Pastikan pencahayaan cukup.');
            }

            setProcessingProgress(70);

            // Try local matching first
            let matchedEmployee: VerifiedEmployee | null = null;

            if (registeredFaces && registeredFaces.length > 0) {
                const localMatch = findBestMatch(
                    faceData.descriptor,
                    registeredFaces,
                    faceSettings.confidence_threshold
                );

                if (localMatch.match) {
                    matchedEmployee = {
                        id: String(localMatch.match.employeeId),
                        name: localMatch.match.name || 'Unknown',
                        similarity: localMatch.similarity,
                    };
                }
            }

            setProcessingProgress(80);

            // If no local match, try API verification
            if (!matchedEmployee) {
                const response = await verifyFaceMutation.mutateAsync({
                    descriptor: faceData.descriptor,
                    confidence: faceData.confidence,
                    threshold: faceSettings.confidence_threshold,
                    require_liveness: faceSettings.enable_liveness,
                });

                if (response.data.success && response.data.employee) {
                    matchedEmployee = {
                        id: response.data.employee.id,
                        name: response.data.employee.name,
                        similarity: response.data.confidence,
                    };
                }
            }

            setProcessingProgress(90);

            if (!matchedEmployee) {
                throw new Error('Wajah tidak dikenali dalam sistem');
            }

            // Submit attendance
            if (checkMode === 'check_in') {
                await checkInMutation.mutateAsync({
                    type: 'check_in',
                    latitude: gpsLocation?.latitude,
                    longitude: gpsLocation?.longitude,
                    face_image: faceData.descriptor.join(','),
                    overwrite: shouldOverwrite,
                });
            } else {
                await checkOutMutation.mutateAsync({
                    type: 'check_out',
                    latitude: gpsLocation?.latitude,
                    longitude: gpsLocation?.longitude,
                    face_image: faceData.descriptor.join(','),
                    overwrite: shouldOverwrite,
                });
            }

            setProcessingProgress(100);
            setVerifiedEmployee(matchedEmployee);
            setCurrentStep('success');

            // Auto close after 3 seconds
            setTimeout(() => {
                handleReset();
            }, 3000);
        } catch (err) {
            console.error('Face verification error:', err);
            setErrorMessage(err instanceof Error ? err.message : 'Gagal memverifikasi wajah');
            setCurrentStep('error');
        }
    };

    const handleConfirmReattend = async () => {
        setShouldOverwrite(true);
        setCurrentStep('location');
        await proceedWithLocationVerification();
    };

    const handleCancelReattend = () => {
        setShouldOverwrite(false);
        handleReset();
    };

    const handleReset = () => {
        setCurrentStep('select');
        setGpsLocation(null);
        setLocationVerification(null);
        setVerifiedEmployee(null);
        setErrorMessage('');
        setProcessingProgress(0);
        setExistingAttendance(null);
        setShouldOverwrite(false);
    };

    const handleRetry = () => {
        if (currentStep === 'error' && gpsLocation && locationVerification?.verified) {
            // If location was verified, go back to face step
            setCurrentStep('face');
            setErrorMessage('');
        } else {
            // Otherwise, start from beginning
            handleReset();
        }
    };

    return (
        <>
            {/* Main Card - Mode Selection */}
            <Card>
                <CardContent className="p-6 space-y-4">
                    <div className="text-center">
                        <p className="text-4xl font-bold tabular-nums">{currentTime}</p>
                        <p className="text-sm text-muted-foreground mt-1">{currentDate}</p>
                    </div>

                    <div className="grid grid-cols-2 gap-3">
                        <Button
                            size="lg"
                            className="bg-success hover:bg-success/90 h-20 flex-col gap-2"
                            onClick={() => handleModeSelect('check_in')}
                            disabled={currentStep !== 'select'}
                        >
                            <LogIn className="h-6 w-6" />
                            <span className="text-base font-semibold">DATANG</span>
                        </Button>
                        <Button
                            size="lg"
                            variant="outline"
                            className="h-20 flex-col gap-2 border-2"
                            onClick={() => handleModeSelect('check_out')}
                            disabled={currentStep !== 'select'}
                        >
                            <LogOut className="h-6 w-6" />
                            <span className="text-base font-semibold">PULANG</span>
                        </Button>
                    </div>

                    {/* Attendance Status Indicator */}
                    {todayAttendanceData && (
                        <div className="grid grid-cols-2 gap-3 text-xs">
                            <div className={`p-2 rounded border text-center ${todayAttendanceData.has_checked_in || todayAttendanceData.attendance?.check_in_time
                                ? 'bg-success/10 border-success/20 text-success'
                                : 'bg-muted/50 border-muted text-muted-foreground'
                                }`}>
                                <p className="font-medium">Datang</p>
                                <p>{formatTime(todayAttendanceData.attendance?.check_in_time || todayAttendanceData.attendance?.check_in)}</p>
                            </div>
                            <div className={`p-2 rounded border text-center ${todayAttendanceData.has_checked_out || todayAttendanceData.attendance?.check_out_time
                                ? 'bg-success/10 border-success/20 text-success'
                                : 'bg-muted/50 border-muted text-muted-foreground'
                                }`}>
                                <p className="font-medium">Pulang</p>
                                <p>{formatTime(todayAttendanceData.attendance?.check_out_time || todayAttendanceData.attendance?.check_out)}</p>
                            </div>
                        </div>
                    )}

                    <div className="flex items-center justify-center gap-4 text-xs text-muted-foreground pt-2 border-t">
                        <div className="flex items-center gap-1">
                            <MapPin className="h-3 w-3 text-success" />
                            GPS Verification
                        </div>
                        <div className="flex items-center gap-1">
                            <ScanFace className="h-3 w-3 text-primary" />
                            Face Recognition
                        </div>
                    </div>
                </CardContent>
            </Card>

            {/* Location Verification Dialog */}
            <Dialog open={currentStep === 'location'} onOpenChange={() => { }}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <MapPin className="h-5 w-5 text-primary" />
                            Memverifikasi Lokasi
                        </DialogTitle>
                        <DialogDescription>
                            Mohon tunggu, sedang memverifikasi lokasi Anda...
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-4">
                        <div className="flex items-center justify-center py-8">
                            <Loader2 className="h-12 w-12 text-primary animate-spin" />
                        </div>

                        <div className="space-y-2">
                            <div className="flex items-center justify-between text-sm">
                                <span className="text-muted-foreground">Mendapatkan lokasi GPS...</span>
                                <span className="font-medium">{processingProgress}%</span>
                            </div>
                            <Progress value={processingProgress} className="h-2" />
                        </div>
                    </div>
                </DialogContent>
            </Dialog>

            {/* Face Verification Dialog */}
            <Dialog open={currentStep === 'face'} onOpenChange={() => { }}>
                <DialogContent className="sm:max-w-lg">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <ScanFace className="h-5 w-5 text-primary" />
                            Verifikasi Wajah
                        </DialogTitle>
                        <DialogDescription>
                            Posisikan wajah Anda di tengah kamera. Foto akan diambil secara otomatis.
                        </DialogDescription>
                    </DialogHeader>

                    <AutoCaptureFace
                        onCapture={handleFaceCapture}
                        onError={(err) => {
                            setErrorMessage(err);
                            setCurrentStep('error');
                        }}
                        autoCapture={true}
                        confidenceThreshold={0.7}
                    />

                    {locationVerification && (
                        <Alert className="border-success/50 bg-success/10">
                            <MapPin className="h-4 w-4 text-success" />
                            <AlertTitle className="text-success">Lokasi Terverifikasi</AlertTitle>
                            <AlertDescription className="text-sm">
                                {locationVerification.location?.name} - {Math.round(locationVerification.distance)}m
                            </AlertDescription>
                        </Alert>
                    )}
                </DialogContent>
            </Dialog>

            {/* Processing Dialog */}
            <Dialog open={currentStep === 'processing'} onOpenChange={() => { }}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <Loader2 className="h-5 w-5 text-primary animate-spin" />
                            Memproses Absensi
                        </DialogTitle>
                        <DialogDescription>
                            Mohon tunggu, sedang memproses {checkMode === 'check_in' ? 'check-in' : 'check-out'} Anda...
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-4 py-4">
                        <div className="flex items-center justify-center">
                            <Loader2 className="h-16 w-16 text-primary animate-spin" />
                        </div>

                        <div className="space-y-2">
                            <div className="flex items-center justify-between text-sm">
                                <span className="text-muted-foreground">
                                    {processingProgress < 70 ? 'Mengenali wajah...' : 'Menyimpan data...'}
                                </span>
                                <span className="font-medium">{processingProgress}%</span>
                            </div>
                            <Progress value={processingProgress} className="h-2" />
                        </div>
                    </div>
                </DialogContent>
            </Dialog>

            {/* Success Dialog */}
            <Dialog open={currentStep === 'success'} onOpenChange={() => { }}>
                <DialogContent className="sm:max-w-md">
                    <div className="space-y-4 py-4">
                        <div className="flex items-center justify-center">
                            <div className="rounded-full bg-success/10 p-4">
                                <CheckCircle2 className="h-16 w-16 text-success" />
                            </div>
                        </div>

                        <div className="text-center space-y-2">
                            <h3 className="text-2xl font-bold text-success">
                                {checkMode === 'check_in' ? 'Check-In Berhasil!' : 'Check-Out Berhasil!'}
                            </h3>
                            <p className="text-muted-foreground">
                                {checkMode === 'check_in' ? 'Selamat bekerja!' : 'Hati-hati di jalan!'}
                            </p>
                        </div>

                        {verifiedEmployee && (
                            <Alert className="border-success/50 bg-success/10">
                                <UserCheck className="h-4 w-4 text-success" />
                                <AlertTitle className="text-success">Identitas Terverifikasi</AlertTitle>
                                <AlertDescription className="space-y-1">
                                    <p className="font-medium">{verifiedEmployee.name}</p>
                                    <p className="text-sm">Kecocokan: {Math.round(verifiedEmployee.similarity * 100)}%</p>
                                    <p className="text-sm">Waktu: {currentTime}</p>
                                    {locationVerification?.location && (
                                        <p className="text-xs text-muted-foreground">
                                            Lokasi: {locationVerification.location.name}
                                        </p>
                                    )}
                                </AlertDescription>
                            </Alert>
                        )}

                        <p className="text-center text-sm text-muted-foreground">
                            Menutup otomatis dalam 3 detik...
                        </p>
                    </div>
                </DialogContent>
            </Dialog>

            {/* Re-Attendance Confirmation Dialog */}
            <Dialog open={currentStep === 'confirm_reattend'} onOpenChange={handleCancelReattend}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <AlertTriangle className="h-5 w-5 text-warning" />
                            Konfirmasi Absen Ulang
                        </DialogTitle>
                        <DialogDescription>
                            Anda sudah melakukan absensi sebelumnya
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-4 py-4">
                        <Alert className="border-warning/50 bg-warning/10">
                            <AlertTriangle className="h-4 w-4 text-warning" />
                            <AlertTitle className="text-warning">Sudah Absen</AlertTitle>
                            <AlertDescription className="space-y-2">
                                <p className="font-medium">
                                    Anda sudah absen {checkMode === 'check_in' ? 'datang' : 'pulang'} pada:
                                </p>
                                <p className="text-lg font-bold">
                                    {formatTime(checkMode === 'check_in'
                                        ? (existingAttendance?.attendance?.check_in_time || existingAttendance?.attendance?.check_in)
                                        : (existingAttendance?.attendance?.check_out_time || existingAttendance?.attendance?.check_out))}
                                </p>
                                <p className="text-sm text-muted-foreground mt-2">
                                    Apakah Anda ingin absen ulang? Data absensi lama akan diganti dengan yang baru.
                                </p>
                            </AlertDescription>
                        </Alert>

                        <div className="flex gap-2">
                            <Button
                                onClick={handleConfirmReattend}
                                className="flex-1"
                                variant="default"
                            >
                                Lanjut Absen
                            </Button>
                            <Button
                                variant="outline"
                                onClick={handleCancelReattend}
                                className="flex-1"
                            >
                                Batal
                            </Button>
                        </div>
                    </div>
                </DialogContent>
            </Dialog>

            {/* Error Dialog */}
            <Dialog open={currentStep === 'error'} onOpenChange={handleReset}>
                <DialogContent className="sm:max-w-md">
                    <div className="space-y-4 py-4">
                        <div className="flex items-center justify-center">
                            <div className="rounded-full bg-destructive/10 p-4">
                                {errorMessage.includes('lokasi') ? (
                                    <MapPinOff className="h-16 w-16 text-destructive" />
                                ) : (
                                    <XCircle className="h-16 w-16 text-destructive" />
                                )}
                            </div>
                        </div>

                        <div className="text-center space-y-2">
                            <h3 className="text-2xl font-bold text-destructive">Gagal</h3>
                            <p className="text-muted-foreground">{errorMessage}</p>
                        </div>

                        <Alert variant="destructive">
                            <AlertTriangle className="h-4 w-4" />
                            <AlertTitle>Terjadi Kesalahan</AlertTitle>
                            <AlertDescription>{errorMessage}</AlertDescription>
                        </Alert>

                        <div className="flex gap-2">
                            <Button onClick={handleRetry} className="flex-1">
                                Coba Lagi
                            </Button>
                            <Button variant="outline" onClick={handleReset} className="flex-1">
                                Batal
                            </Button>
                        </div>
                    </div>
                </DialogContent>
            </Dialog>
        </>
    );
}
