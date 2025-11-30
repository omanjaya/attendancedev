import { useState, useEffect, useCallback } from 'react';
import {
  Clock,
  LogIn,
  LogOut,
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
  ClipboardList,
  Download,
} from 'lucide-react';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
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
import { Progress } from '@/components/ui/progress';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { DataTable, PageHeader, StatsGrid, type Column, type StatItem } from '@/components/shared';
import { useFaceDetection } from '@/hooks/use-face-detection';
import { useRegisteredFaces, useVerifyFace } from '@/hooks/use-face-recognition-api';
import { useFaceStore } from '@/stores';
import { faceDetectionService } from '@/lib/services/face-detection';
import { findBestMatch } from '@/lib/api/face-recognition';
import { useAuthStore } from '@/stores';
import { useAttendance, useAttendanceStatistics, useCheckIn, useCheckOut } from '@/hooks';
import type { Attendance, AttendanceStatus, AttendanceFilters } from '@/types';

const getStatusBadge = (status: AttendanceStatus) => {
  switch (status) {
    case 'present':
      return <Badge className="bg-success/10 text-success border-0">Hadir</Badge>;
    case 'late':
      return <Badge className="bg-warning/10 text-warning border-0">Terlambat</Badge>;
    case 'absent':
      return <Badge className="bg-destructive/10 text-destructive border-0">Tidak Hadir</Badge>;
    case 'leave':
      return <Badge variant="secondary">Cuti</Badge>;
    case 'holiday':
      return <Badge className="bg-primary/10 text-primary border-0">Libur</Badge>;
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

  const [isDialogOpen, setIsDialogOpen] = useState(false);
  const [checkMode, setCheckMode] = useState<CheckInMode>('check_in');
  const [verificationStatus, setVerificationStatus] = useState<VerificationStatus>('idle');
  const [verificationProgress, setVerificationProgress] = useState(0);
  const [verifiedEmployee, setVerifiedEmployee] = useState<VerifiedEmployee | null>(null);
  const [gpsLocation, setGpsLocation] = useState<GPSLocation | null>(null);
  const [, setGpsError] = useState<string | null>(null);

  const [currentTime, setCurrentTime] = useState('');
  const [currentDate, setCurrentDate] = useState('');

  const { data: registeredFaces } = useRegisteredFaces();
  const verifyFaceMutation = useVerifyFace();
  const { settings: faceSettings } = useFaceStore();
  const { user } = useAuthStore();

  const filters: AttendanceFilters = {
    status: statusFilter !== 'all' ? statusFilter as AttendanceStatus : undefined,
    page,
    per_page: pageSize,
  };
  const { data: attendanceData, isLoading: isLoadingAttendance } = useAttendance(filters);
  const { data: statistics } = useAttendanceStatistics();
  const checkInMutation = useCheckIn();
  const checkOutMutation = useCheckOut();

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

  const openCheckDialog = async (mode: CheckInMode) => {
    setCheckMode(mode);
    setVerificationStatus('idle');
    setVerificationProgress(0);
    setIsDialogOpen(true);

    if (!isInitialized) {
      await initialize();
    }

    setTimeout(async () => {
      await startCamera();
      startDetection();
    }, 300);
  };

  const closeDialog = () => {
    stopDetection();
    stopCamera();
    setIsDialogOpen(false);
    setVerificationStatus('idle');
    setVerificationProgress(0);
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

  const handleVerification = async () => {
    if (detectionStatus !== 'detected' || !videoRef.current) return;

    setVerificationStatus('detecting');
    setVerificationProgress(10);
    setVerifiedEmployee(null);
    setGpsError(null);

    try {
      setVerificationProgress(20);
      let location: GPSLocation | null = null;
      try {
        location = await getGPSLocation();
        setGpsLocation(location);
      } catch (gpsErr) {
        console.warn('GPS error:', gpsErr);
        setGpsError(gpsErr instanceof Error ? gpsErr.message : 'Gagal mendapatkan lokasi');
      }

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

      if (checkMode === 'check_in') {
        await checkInMutation.mutateAsync({
          type: 'check_in',
          latitude: location?.latitude,
          longitude: location?.longitude,
          face_image: faceData.descriptor.join(','),
        });
      } else {
        await checkOutMutation.mutateAsync({
          type: 'check_out',
          latitude: location?.latitude,
          longitude: location?.longitude,
          face_image: faceData.descriptor.join(','),
        });
      }

      setVerificationProgress(100);
      setVerifiedEmployee(matchedEmployee);
      setVerificationStatus('success');

      setTimeout(() => {
        closeDialog();
      }, 3000);
    } catch (err) {
      console.error('Verification error:', err);
      setVerificationStatus('failed');
      setVerificationProgress(0);
    }
  };

  const attendanceRecords = attendanceData?.data || [];
  const filteredData = search
    ? attendanceRecords.filter((att) =>
      att.employee_name.toLowerCase().includes(search.toLowerCase())
    )
    : attendanceRecords;
  const totalPages = attendanceData?.meta?.last_page || 1;
  const totalItems = attendanceData?.meta?.total || 0;

  const columns: Column<Attendance>[] = [
    {
      key: 'employee_name',
      header: 'Karyawan',
      cell: (row) => (
        <div className="flex items-center gap-3">
          <div className="flex h-9 w-9 items-center justify-center rounded-full bg-primary/10">
            <span className="text-xs font-medium text-primary">
              {(row.employee_name || '').split(' ').map((n) => n[0]).join('').slice(0, 2)}
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
          className={`h-4 w-4 ${row.face_verified ? 'text-success' : 'text-muted-foreground'}`}
        />
      ),
    },
  ];

  const stats: StatItem[] = [
    {
      label: 'Hadir',
      value: statistics?.present || 0,
      icon: UserCheck,
      color: 'success',
    },
    {
      label: 'Terlambat',
      value: statistics?.late || 0,
      icon: Clock,
      color: 'warning',
    },
    {
      label: 'Tidak Hadir',
      value: statistics?.absent || 0,
      icon: XCircle,
      color: 'destructive',
    },
    {
      label: 'Cuti',
      value: statistics?.on_leave || 0,
      icon: CalendarOff,
      color: 'primary',
    },
  ];

  return (
    <div className="space-y-6 p-6">
      {/* Page Header */}
      <PageHeader
        title="Absensi"
        description="Kelola kehadiran karyawan"
        icon={ClipboardList}
        actions={
          <Button variant="outline" size="sm">
            <Download className="mr-2 h-4 w-4" />
            Export
          </Button>
        }
      />

      {/* Stats + Check-in Widget */}
      <div className="grid gap-4 lg:grid-cols-5">
        {/* Check-in Widget */}
        <Card className="lg:col-span-1">
          <CardContent className="p-4 space-y-3">
            <div className="text-center">
              <p className="text-3xl font-bold tabular-nums">{currentTime}</p>
              <p className="text-xs text-muted-foreground mt-1">{currentDate}</p>
            </div>
            <div className="grid grid-cols-2 gap-2">
              <Button
                size="sm"
                className="bg-success hover:bg-success/90"
                onClick={() => openCheckDialog('check_in')}
              >
                <LogIn className="mr-1 h-3 w-3" />
                In
              </Button>
              <Button
                size="sm"
                variant="outline"
                onClick={() => openCheckDialog('check_out')}
              >
                <LogOut className="mr-1 h-3 w-3" />
                Out
              </Button>
            </div>
            <div className="flex items-center justify-center gap-3 text-xs text-muted-foreground pt-2 border-t">
              <div className="flex items-center gap-1">
                <MapPin className="h-3 w-3 text-success" />
                GPS
              </div>
              <div className="flex items-center gap-1">
                <ScanFace className="h-3 w-3 text-primary" />
                Face ID
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Stats */}
        <div className="lg:col-span-4">
          <StatsGrid stats={stats} columns={4} variant="cards" />
        </div>
      </div>

      {/* Filters */}
      <div className="flex items-center gap-4">
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
      <Card>
        <CardContent className="p-4">
          <DataTable
            columns={columns}
            data={filteredData}
            searchPlaceholder="Cari karyawan..."
            searchValue={search}
            onSearchChange={setSearch}
            page={page}
            pageSize={pageSize}
            totalPages={totalPages}
            totalItems={totalItems}
            onPageChange={setPage}
            onPageSizeChange={(size) => {
              setPageSize(size);
              setPage(1);
            }}
            emptyMessage="Tidak ada data absensi"
            isLoading={isLoadingAttendance}
          />
        </CardContent>
      </Card>

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
                      <Badge className="bg-success border-0 gap-1">
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
                    <div className="absolute inset-0 flex items-center justify-center bg-success/30">
                      <div className="text-center">
                        <CheckCircle2 className="mx-auto h-16 w-16 text-success" />
                        <p className="mt-2 text-lg font-semibold text-white">Berhasil!</p>
                      </div>
                    </div>
                  )}

                  {verificationStatus === 'failed' && (
                    <div className="absolute inset-0 flex items-center justify-center bg-destructive/30">
                      <div className="text-center">
                        <XCircle className="mx-auto h-16 w-16 text-destructive" />
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

            {error && (
              <Alert variant="destructive">
                <XCircle className="h-4 w-4" />
                <AlertTitle>Error</AlertTitle>
                <AlertDescription>{error}</AlertDescription>
              </Alert>
            )}

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

            {verificationStatus === 'success' && verifiedEmployee && (
              <Alert className="border-success/50 bg-success/10">
                <UserCheck className="h-4 w-4 text-success" />
                <AlertTitle className="text-success">
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

            {verificationStatus === 'failed' && (
              <Alert variant="destructive">
                <XCircle className="h-4 w-4" />
                <AlertTitle>Verifikasi Gagal</AlertTitle>
                <AlertDescription>
                  Wajah tidak dapat diverifikasi. Silakan coba lagi.
                </AlertDescription>
              </Alert>
            )}

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
