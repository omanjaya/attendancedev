import { useState, useEffect, useCallback } from 'react';
import {
  Clock,
  LogIn,
  LogOut,
  Calendar,
  MapPin,
  ScanFace,
  Filter,
  Camera,
  CameraOff,
  CheckCircle2,
  XCircle,
  Loader2,
  X,
  Shield,
  UserCheck,
  CalendarOff,
} from 'lucide-react';
import { StatsGrid } from '@/components/dashboard';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
import { Progress } from '@/components/ui/progress';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { DataTable, type Column } from '@/components/shared';
import { useFaceDetection } from '@/hooks/use-face-detection';
import { useRegisteredFaces, useVerifyFace } from '@/hooks/use-face-recognition-api';
import { useFaceStore } from '@/stores';
import { faceDetectionService } from '@/lib/services/face-detection';
import { findBestMatch } from '@/lib/api/face-recognition';
import { useAuthStore } from '@/stores';
import apiClient from '@/lib/api/client';
import type { Attendance, AttendanceStatus } from '@/types';

// Mock data
const mockAttendance: Attendance[] = [
  {
    id: 1,
    employee_id: 1,
    employee_name: 'Ahmad Rizki',
    date: '2024-01-15',
    check_in: '08:02:00',
    check_out: '17:05:00',
    status: 'present',
    work_hours: 9.05,
    face_verified: true,
    created_at: '2024-01-15T08:02:00',
    updated_at: '2024-01-15T17:05:00',
  },
  {
    id: 2,
    employee_id: 2,
    employee_name: 'Siti Nurhaliza',
    date: '2024-01-15',
    check_in: '08:15:00',
    check_out: '17:10:00',
    status: 'present',
    work_hours: 8.92,
    face_verified: true,
    created_at: '2024-01-15T08:15:00',
    updated_at: '2024-01-15T17:10:00',
  },
  {
    id: 3,
    employee_id: 3,
    employee_name: 'Budi Santoso',
    date: '2024-01-15',
    check_in: '08:45:00',
    check_out: '17:00:00',
    status: 'late',
    late_minutes: 45,
    work_hours: 8.25,
    face_verified: true,
    created_at: '2024-01-15T08:45:00',
    updated_at: '2024-01-15T17:00:00',
  },
  {
    id: 4,
    employee_id: 4,
    employee_name: 'Dewi Anggraini',
    date: '2024-01-15',
    status: 'leave',
    face_verified: false,
    created_at: '2024-01-15T00:00:00',
    updated_at: '2024-01-15T00:00:00',
  },
  {
    id: 5,
    employee_id: 5,
    employee_name: 'Eko Prasetyo',
    date: '2024-01-15',
    check_in: '07:55:00',
    check_out: '18:30:00',
    status: 'present',
    overtime_minutes: 90,
    work_hours: 10.58,
    face_verified: true,
    created_at: '2024-01-15T07:55:00',
    updated_at: '2024-01-15T18:30:00',
  },
];

// Status badge
const getStatusBadge = (status: AttendanceStatus) => {
  switch (status) {
    case 'present':
      return <Badge className="bg-success text-success-foreground">Hadir</Badge>;
    case 'late':
      return <Badge className="bg-warning text-warning-foreground">Terlambat</Badge>;
    case 'absent':
      return <Badge variant="destructive">Tidak Hadir</Badge>;
    case 'leave':
      return <Badge variant="secondary">Cuti</Badge>;
    case 'holiday':
      return <Badge className="bg-primary text-primary-foreground">Libur</Badge>;
    default:
      return <Badge variant="secondary">{status}</Badge>;
  }
};

type CheckInMode = 'check_in' | 'check_out';
type VerificationStatus = 'idle' | 'detecting' | 'verifying' | 'success' | 'failed';

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

export default function AttendancePage() {
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState<string>('all');
  const [page, setPage] = useState(1);
  const [pageSize, setPageSize] = useState(10);

  // Face verification dialog
  const [isDialogOpen, setIsDialogOpen] = useState(false);
  const [checkMode, setCheckMode] = useState<CheckInMode>('check_in');
  const [verificationStatus, setVerificationStatus] = useState<VerificationStatus>('idle');
  const [verificationProgress, setVerificationProgress] = useState(0);
  const [verifiedEmployee, setVerifiedEmployee] = useState<VerifiedEmployee | null>(null);
  const [gpsLocation, setGpsLocation] = useState<GPSLocation | null>(null);
  const [, setGpsError] = useState<string | null>(null);

  // Current time state
  const [currentTime, setCurrentTime] = useState('');
  const [currentDate, setCurrentDate] = useState('');

  // Face recognition hooks
  const { data: registeredFaces } = useRegisteredFaces();
  const verifyFaceMutation = useVerifyFace();
  const { settings: faceSettings } = useFaceStore();
  const { user } = useAuthStore();

  // Face detection hook
  const {
    videoRef,
    canvasRef,
    isInitialized,
    cameraStatus,
    detectionStatus,
    error,
    confidence,
    isProcessing,
    initialize,
    startCamera,
    stopCamera,
    startDetection,
    stopDetection,
  } = useFaceDetection({
    onError: (err) => console.error('Face detection error:', err),
  });

  // Update time every second
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

  // NOTE: Face detection is now initialized on-demand when opening check dialog
  // This improves initial page load performance

  // Open dialog and start camera
  const openCheckDialog = async (mode: CheckInMode) => {
    setCheckMode(mode);
    setVerificationStatus('idle');
    setVerificationProgress(0);
    setIsDialogOpen(true);

    // Initialize face detection on-demand (lazy load)
    if (!isInitialized) {
      await initialize();
    }

    // Wait for dialog to open then start camera
    setTimeout(async () => {
      await startCamera();
      startDetection();
    }, 300);
  };

  // Close dialog and stop camera
  const closeDialog = () => {
    stopDetection();
    stopCamera();
    setIsDialogOpen(false);
    setVerificationStatus('idle');
    setVerificationProgress(0);
  };

  // Get GPS location
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
              reject(new Error('Izin lokasi ditolak'));
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

  // Handle verification with real face recognition
  const handleVerification = async () => {
    if (detectionStatus !== 'detected' || !videoRef.current) return;

    setVerificationStatus('detecting');
    setVerificationProgress(10);
    setVerifiedEmployee(null);
    setGpsError(null);

    try {
      // Step 1: Get GPS location (parallel with face detection)
      setVerificationProgress(20);
      let location: GPSLocation | null = null;
      try {
        location = await getGPSLocation();
        setGpsLocation(location);
      } catch (gpsErr) {
        console.warn('GPS error:', gpsErr);
        setGpsError(gpsErr instanceof Error ? gpsErr.message : 'Gagal mendapatkan lokasi');
        // Continue without GPS - some systems may allow this
      }

      // Step 2: Capture face descriptor
      setVerificationProgress(40);
      setVerificationStatus('verifying');

      const faceData = await faceDetectionService.captureFaceDescriptor(
        videoRef.current,
        user?.id || 'unknown'
      );

      if (faceData.confidence < 0.6) {
        throw new Error('Kualitas deteksi wajah kurang baik. Pastikan pencahayaan cukup.');
      }

      setVerificationProgress(60);

      // Step 3: Try local matching first for speed
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

      setVerificationProgress(75);

      // Step 4: Verify with backend (fallback or confirmation)
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

      setVerificationProgress(90);

      if (!matchedEmployee) {
        throw new Error('Wajah tidak dikenali dalam sistem');
      }

      // Step 5: Submit attendance to backend
      const attendanceData = {
        employee_id: matchedEmployee.id,
        face_descriptor: faceData.descriptor,
        face_confidence: faceData.confidence,
        location: location ? {
          latitude: location.latitude,
          longitude: location.longitude,
          accuracy: location.accuracy,
        } : undefined,
        device_info: {
          browser: navigator.userAgent,
          platform: navigator.platform,
        },
      };

      const endpoint = checkMode === 'check_in' ? '/attendance/check-in' : '/attendance/check-out';
      await apiClient.post(endpoint, attendanceData);

      setVerificationProgress(100);
      setVerifiedEmployee(matchedEmployee);
      setVerificationStatus('success');

      // Auto close after success
      setTimeout(() => {
        closeDialog();
      }, 3000);

    } catch (err) {
      console.error('Verification error:', err);
      setVerificationStatus('failed');
      setVerificationProgress(0);
    }
  };

  // Filter data
  const filteredData = mockAttendance.filter((att) => {
    const matchesSearch = att.employee_name.toLowerCase().includes(search.toLowerCase());
    const matchesStatus = statusFilter === 'all' || att.status === statusFilter;
    return matchesSearch && matchesStatus;
  });

  // Define columns
  const columns: Column<Attendance>[] = [
    {
      key: 'employee_name',
      header: 'Karyawan',
      cell: (row) => (
        <div className="flex items-center gap-3">
          <div className="flex h-9 w-9 items-center justify-center rounded-full bg-muted">
            <span className="text-xs font-medium">
              {row.employee_name.split(' ').map((n) => n[0]).join('')}
            </span>
          </div>
          <span className="font-medium">{row.employee_name}</span>
        </div>
      ),
    },
    {
      key: 'date',
      header: 'Tanggal',
      cell: (row) => (
        <span className="text-muted-foreground">
          {new Date(row.date).toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
          })}
        </span>
      ),
    },
    {
      key: 'check_in',
      header: 'Masuk',
      cell: (row) => (
        <div className="flex items-center gap-1">
          <LogIn className="h-3 w-3 text-success" />
          <span>{row.check_in || '-'}</span>
        </div>
      ),
    },
    {
      key: 'check_out',
      header: 'Keluar',
      cell: (row) => (
        <div className="flex items-center gap-1">
          <LogOut className="h-3 w-3 text-primary" />
          <span>{row.check_out || '-'}</span>
        </div>
      ),
    },
    {
      key: 'work_hours',
      header: 'Jam Kerja',
      cell: (row) => (
        <span>{row.work_hours ? `${row.work_hours.toFixed(1)} jam` : '-'}</span>
      ),
    },
    {
      key: 'status',
      header: 'Status',
      cell: (row) => (
        <div className="flex items-center gap-2">
          {getStatusBadge(row.status)}
          {row.late_minutes && (
            <span className="text-xs text-warning">+{row.late_minutes}m</span>
          )}
          {row.overtime_minutes && (
            <span className="text-xs text-success">OT {row.overtime_minutes}m</span>
          )}
        </div>
      ),
    },
    {
      key: 'face_verified',
      header: 'Verifikasi',
      cell: (row) => (
        <ScanFace
          className={`h-4 w-4 ${
            row.face_verified ? 'text-success' : 'text-muted-foreground'
          }`}
        />
      ),
    },
  ];

  // Stats
  const stats = {
    present: mockAttendance.filter((a) => a.status === 'present').length,
    late: mockAttendance.filter((a) => a.status === 'late').length,
    absent: mockAttendance.filter((a) => a.status === 'absent').length,
    leave: mockAttendance.filter((a) => a.status === 'leave').length,
  };

  return (
    <div className="p-6">
      {/* Header */}
      <div className="mb-6">
        <h1 className="text-macos-2xl font-bold text-foreground">Absensi</h1>
        <p className="text-macos-sm text-muted-foreground">
          Kelola kehadiran karyawan
        </p>
      </div>

      {/* Check-in Widget & Stats */}
      <div className="mb-6 grid gap-6 lg:grid-cols-3">
        {/* Check-in Widget */}
        <Card className="lg:col-span-1 overflow-hidden">
          <CardHeader className="pb-2 bg-gradient-to-br from-primary/5 to-transparent">
            <CardTitle className="flex items-center gap-2 text-macos-base">
              <Clock className="h-5 w-5 text-primary" />
              Absen Hari Ini
            </CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="text-center py-2">
              <p className="text-4xl font-bold text-foreground tabular-nums">{currentTime}</p>
              <p className="text-sm text-muted-foreground mt-1">{currentDate}</p>
            </div>
            <div className="grid grid-cols-2 gap-2">
              <Button
                className="w-full bg-emerald-600 hover:bg-emerald-700"
                onClick={() => openCheckDialog('check_in')}
              >
                <LogIn className="mr-2 h-4 w-4" />
                Check In
              </Button>
              <Button
                variant="outline"
                className="w-full"
                onClick={() => openCheckDialog('check_out')}
              >
                <LogOut className="mr-2 h-4 w-4" />
                Check Out
              </Button>
            </div>
            <div className="flex items-center justify-center gap-4 text-xs text-muted-foreground pt-2 border-t">
              <div className="flex items-center gap-1">
                <MapPin className="h-3 w-3 text-emerald-500" />
                GPS Aktif
              </div>
              <div className="flex items-center gap-1">
                <ScanFace className="h-3 w-3 text-blue-500" />
                Face ID Ready
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Stats - using StatsGrid component */}
        <Card className="lg:col-span-2">
          <CardHeader className="pb-2">
            <CardTitle className="flex items-center gap-2 text-macos-base">
              <Calendar className="h-5 w-5 text-primary" />
              Ringkasan Hari Ini
            </CardTitle>
          </CardHeader>
          <CardContent>
            <StatsGrid
              stats={[
                { id: 'present', title: 'Hadir', value: stats.present, icon: UserCheck, color: 'success' },
                { id: 'late', title: 'Terlambat', value: stats.late, icon: Clock, color: 'warning' },
                { id: 'absent', title: 'Tidak Hadir', value: stats.absent, icon: XCircle, color: 'destructive' },
                { id: 'leave', title: 'Cuti', value: stats.leave, icon: CalendarOff, color: 'default' },
              ]}
              columns={4}
              variant="compact"
            />
          </CardContent>
        </Card>
      </div>

      {/* Filters */}
      <div className="mb-4 flex items-center gap-4">
        <div className="flex items-center gap-2">
          <Filter className="h-4 w-4 text-muted-foreground" />
          <Select value={statusFilter} onValueChange={setStatusFilter}>
            <SelectTrigger className="w-[150px]">
              <SelectValue placeholder="Status" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">Semua Status</SelectItem>
              <SelectItem value="present">Hadir</SelectItem>
              <SelectItem value="late">Terlambat</SelectItem>
              <SelectItem value="absent">Tidak Hadir</SelectItem>
              <SelectItem value="leave">Cuti</SelectItem>
            </SelectContent>
          </Select>
        </div>
      </div>

      {/* Data Table */}
      <div className="rounded-lg border border-border bg-card p-4">
        <DataTable
          columns={columns}
          data={filteredData}
          searchPlaceholder="Cari karyawan..."
          searchValue={search}
          onSearchChange={setSearch}
          page={page}
          pageSize={pageSize}
          totalPages={Math.ceil(filteredData.length / pageSize)}
          totalItems={filteredData.length}
          onPageChange={setPage}
          onPageSizeChange={(size) => {
            setPageSize(size);
            setPage(1);
          }}
          emptyMessage="Tidak ada data absensi"
        />
      </div>

      {/* Face Verification Dialog */}
      <Dialog open={isDialogOpen} onOpenChange={(open) => !open && closeDialog()}>
        <DialogContent className="sm:max-w-lg">
          <DialogHeader>
            <DialogTitle className="flex items-center gap-2">
              <ScanFace className="h-5 w-5 text-primary" />
              {checkMode === 'check_in' ? 'Check In' : 'Check Out'} dengan Face ID
            </DialogTitle>
            <DialogDescription>
              Posisikan wajah Anda di tengah kamera untuk verifikasi
            </DialogDescription>
          </DialogHeader>

          <div className="space-y-4">
            {/* Camera View */}
            <div className="relative aspect-video overflow-hidden rounded-lg bg-neutral-900">
              {cameraStatus === 'active' ? (
                <>
                  <video
                    ref={videoRef}
                    autoPlay
                    playsInline
                    muted
                    className="h-full w-full object-cover"
                  />
                  <canvas
                    ref={canvasRef}
                    className="absolute inset-0 h-full w-full"
                  />

                  {/* Detection status overlays */}
                  {detectionStatus === 'no_face' && verificationStatus === 'idle' && (
                    <div className="absolute inset-0 flex items-center justify-center bg-black/50">
                      <div className="text-center">
                        <ScanFace className="mx-auto h-12 w-12 text-white/70 animate-pulse" />
                        <p className="mt-2 text-sm text-white/70">Mencari wajah...</p>
                      </div>
                    </div>
                  )}

                  {detectionStatus === 'detected' && verificationStatus === 'idle' && (
                    <div className="absolute bottom-4 left-1/2 -translate-x-1/2">
                      <Badge className="bg-emerald-500 gap-1">
                        <CheckCircle2 className="h-3 w-3" />
                        Wajah terdeteksi - {Math.round(confidence * 100)}%
                      </Badge>
                    </div>
                  )}

                  {(verificationStatus === 'detecting' || verificationStatus === 'verifying') && (
                    <div className="absolute inset-0 flex items-center justify-center bg-black/60">
                      <div className="text-center">
                        <Loader2 className="mx-auto h-12 w-12 text-primary animate-spin" />
                        <p className="mt-2 text-sm text-white">
                          {verificationStatus === 'detecting' ? 'Mendeteksi wajah...' : 'Memverifikasi...'}
                        </p>
                      </div>
                    </div>
                  )}

                  {verificationStatus === 'success' && (
                    <div className="absolute inset-0 flex items-center justify-center bg-emerald-500/30">
                      <div className="text-center">
                        <CheckCircle2 className="mx-auto h-16 w-16 text-emerald-500" />
                        <p className="mt-2 text-lg font-semibold text-white">Berhasil!</p>
                      </div>
                    </div>
                  )}

                  {verificationStatus === 'failed' && (
                    <div className="absolute inset-0 flex items-center justify-center bg-red-500/30">
                      <div className="text-center">
                        <XCircle className="mx-auto h-16 w-16 text-red-500" />
                        <p className="mt-2 text-lg font-semibold text-white">Gagal</p>
                      </div>
                    </div>
                  )}
                </>
              ) : (
                <div className="flex h-full flex-col items-center justify-center gap-4">
                  <CameraOff className="h-12 w-12 text-muted-foreground" />
                  <p className="text-muted-foreground">Mengaktifkan kamera...</p>
                </div>
              )}
            </div>

            {/* Error */}
            {error && (
              <Alert variant="destructive">
                <XCircle className="h-4 w-4" />
                <AlertTitle>Error</AlertTitle>
                <AlertDescription>{error}</AlertDescription>
              </Alert>
            )}

            {/* Progress */}
            {verificationStatus !== 'idle' && verificationStatus !== 'success' && verificationStatus !== 'failed' && (
              <div className="space-y-2">
                <div className="flex items-center justify-between text-sm">
                  <span className="text-muted-foreground">
                    {verificationStatus === 'detecting' ? 'Mendeteksi wajah...' : 'Memverifikasi...'}
                  </span>
                  <span className="font-medium">{verificationProgress}%</span>
                </div>
                <Progress value={verificationProgress} className="h-2" />
              </div>
            )}

            {/* Success message */}
            {verificationStatus === 'success' && verifiedEmployee && (
              <Alert className="border-emerald-500/50 bg-emerald-500/10">
                <UserCheck className="h-4 w-4 text-emerald-500" />
                <AlertTitle className="text-emerald-600">
                  {checkMode === 'check_in' ? 'Check In Berhasil!' : 'Check Out Berhasil!'}
                </AlertTitle>
                <AlertDescription className="space-y-1">
                  <p className="font-medium">{verifiedEmployee.name}</p>
                  <p className="text-sm">Kecocokan: {Math.round(verifiedEmployee.similarity * 100)}%</p>
                  <p className="text-sm">Waktu: {currentTime}</p>
                  {gpsLocation && (
                    <p className="text-xs text-muted-foreground">
                      GPS: {gpsLocation.latitude.toFixed(6)}, {gpsLocation.longitude.toFixed(6)}
                    </p>
                  )}
                </AlertDescription>
              </Alert>
            )}

            {/* Failed message */}
            {verificationStatus === 'failed' && (
              <Alert variant="destructive">
                <XCircle className="h-4 w-4" />
                <AlertTitle>Verifikasi Gagal</AlertTitle>
                <AlertDescription>
                  Wajah tidak dapat diverifikasi. Silakan coba lagi.
                </AlertDescription>
              </Alert>
            )}

            {/* Actions */}
            <div className="flex gap-2">
              {verificationStatus === 'idle' && (
                <Button
                  onClick={handleVerification}
                  disabled={detectionStatus !== 'detected' || isProcessing}
                  className="flex-1"
                >
                  <Shield className="mr-2 h-4 w-4" />
                  Verifikasi
                </Button>
              )}

              {verificationStatus === 'failed' && (
                <Button
                  onClick={() => setVerificationStatus('idle')}
                  className="flex-1"
                >
                  <Camera className="mr-2 h-4 w-4" />
                  Coba Lagi
                </Button>
              )}

              <Button variant="outline" onClick={closeDialog}>
                <X className="mr-2 h-4 w-4" />
                Tutup
              </Button>
            </div>
          </div>
        </DialogContent>
      </Dialog>
    </div>
  );
}
