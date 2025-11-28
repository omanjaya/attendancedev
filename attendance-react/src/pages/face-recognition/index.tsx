import { useState, useEffect } from 'react';
import {
  Camera,
  CameraOff,
  CheckCircle2,
  XCircle,
  User,
  Shield,
  Loader2,
  AlertTriangle,
  Scan,
  UserPlus,
  History,
  Sparkles,
  Eye,
  Activity,
  Clock,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useAuthStore } from '@/stores';
import { useFaceDetection } from '@/hooks/use-face-detection';
import type { KnownFaceDescriptor } from '@/types/face-recognition';

interface VerificationHistory {
  id: number;
  timestamp: string;
  status: 'success' | 'failed';
  confidence: number;
  method: string;
}

export default function FaceRecognitionPage() {
  const { user } = useAuthStore();
  const [activeTab, setActiveTab] = useState('verify');
  const [enrollmentStep, setEnrollmentStep] = useState<'idle' | 'capturing' | 'processing' | 'complete'>('idle');
  const [capturedDescriptors, setCapturedDescriptors] = useState<number[][]>([]);

  // Mock verification history
  const [verificationHistory] = useState<VerificationHistory[]>([
    { id: 1, timestamp: '2024-01-15 08:30:15', status: 'success', confidence: 0.94, method: 'face' },
    { id: 2, timestamp: '2024-01-15 17:05:22', status: 'success', confidence: 0.91, method: 'face' },
    { id: 3, timestamp: '2024-01-14 08:28:45', status: 'failed', confidence: 0.42, method: 'face' },
    { id: 4, timestamp: '2024-01-14 08:29:10', status: 'success', confidence: 0.89, method: 'face' },
  ]);

  // Mock known descriptors (would come from API)
  const [knownDescriptors] = useState<KnownFaceDescriptor[]>([]);

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
    captureDescriptor,
    recognizeFace,
    clearError,
  } = useFaceDetection({
    onError: (err) => console.error('Face detection error:', err),
  });

  // Initialize on mount
  useEffect(() => {
    initialize();
  }, [initialize]);

  // Handle verification
  const handleVerification = async () => {
    if (!isInitialized || cameraStatus !== 'active') return;

    try {
      const result = await recognizeFace(knownDescriptors);
      if (result.success) {
        console.log('Verified:', result.employeeId, 'Confidence:', result.confidence);
      } else {
        console.log('Verification failed:', result.message);
      }
    } catch (err) {
      console.error('Verification error:', err);
    }
  };

  // Handle enrollment capture
  const handleEnrollmentCapture = async () => {
    if (!user?.id) return;

    setEnrollmentStep('capturing');
    try {
      const faceData = await captureDescriptor(user.id, true);
      setCapturedDescriptors(prev => [...prev, faceData.descriptor]);

      if (capturedDescriptors.length >= 2) {
        setEnrollmentStep('complete');
      } else {
        setEnrollmentStep('idle');
      }
    } catch (err) {
      console.error('Capture error:', err);
      setEnrollmentStep('idle');
    }
  };

  // Start camera and detection
  const handleStartCamera = async () => {
    await startCamera();
    if (isInitialized) {
      startDetection();
    }
  };

  // Stop everything
  const handleStopCamera = () => {
    stopDetection();
    stopCamera();
  };

  // Stats data
  const stats = [
    {
      label: 'Verifikasi Sukses',
      value: '98.5%',
      description: 'Tingkat keberhasilan',
      icon: CheckCircle2,
      color: 'text-emerald-500',
      bgColor: 'bg-emerald-500/10',
    },
    {
      label: 'Total Verifikasi',
      value: '1,247',
      description: 'Bulan ini',
      icon: Activity,
      color: 'text-blue-500',
      bgColor: 'bg-blue-500/10',
    },
    {
      label: 'Waktu Rata-rata',
      value: '1.2s',
      description: 'Per verifikasi',
      icon: Clock,
      color: 'text-amber-500',
      bgColor: 'bg-amber-500/10',
    },
  ];

  return (
    <div className="space-y-6 p-6">
      {/* Premium Header Section */}
      <div className="relative overflow-hidden rounded-2xl bg-gradient-to-br from-primary/10 via-primary/5 to-background p-8">
        <div className="absolute -right-20 -top-20 h-64 w-64 rounded-full bg-primary/10 blur-3xl" />
        <div className="absolute -bottom-20 -left-20 h-64 w-64 rounded-full bg-primary/5 blur-3xl" />

        <div className="relative">
          <div className="flex items-center gap-3">
            <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-primary/10">
              <Scan className="h-6 w-6 text-primary" />
            </div>
            <div>
              <h1 className="text-2xl font-bold text-foreground">Face Recognition</h1>
              <p className="text-sm text-muted-foreground">
                Verifikasi biometrik dengan AI-powered face detection
              </p>
            </div>
          </div>

          {/* Stats Grid */}
          <div className="mt-8 grid gap-4 sm:grid-cols-3">
            {stats.map((stat, index) => (
              <div
                key={index}
                className="group relative overflow-hidden rounded-xl border border-border/50 bg-card/50 p-4 backdrop-blur-sm transition-all hover:border-primary/20 hover:shadow-lg"
              >
                <div className="flex items-start justify-between">
                  <div>
                    <p className="text-sm text-muted-foreground">{stat.label}</p>
                    <p className="mt-1 text-3xl font-bold tracking-tight">{stat.value}</p>
                    <p className="mt-0.5 text-xs text-muted-foreground">{stat.description}</p>
                  </div>
                  <div className={`rounded-lg p-2 ${stat.bgColor}`}>
                    <stat.icon className={`h-5 w-5 ${stat.color}`} />
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>

      {/* Main Content Tabs */}
      <Tabs value={activeTab} onValueChange={setActiveTab} className="space-y-6">
        <TabsList className="grid w-full grid-cols-3 lg:w-[400px]">
          <TabsTrigger value="verify" className="gap-2">
            <Shield className="h-4 w-4" />
            Verifikasi
          </TabsTrigger>
          <TabsTrigger value="enroll" className="gap-2">
            <UserPlus className="h-4 w-4" />
            Pendaftaran
          </TabsTrigger>
          <TabsTrigger value="history" className="gap-2">
            <History className="h-4 w-4" />
            Riwayat
          </TabsTrigger>
        </TabsList>

        {/* Verification Tab */}
        <TabsContent value="verify" className="space-y-6">
          <div className="grid gap-6 lg:grid-cols-3">
            {/* Camera Section */}
            <div className="lg:col-span-2">
              <Card className="overflow-hidden">
                <CardHeader className="border-b bg-muted/30 pb-4">
                  <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                      <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10">
                        <Camera className="h-5 w-5 text-primary" />
                      </div>
                      <div>
                        <CardTitle className="text-base">Live Camera</CardTitle>
                        <CardDescription className="text-xs">
                          {isInitialized ? 'Model AI siap digunakan' : 'Memuat model AI...'}
                        </CardDescription>
                      </div>
                    </div>
                    <div className="flex items-center gap-2">
                      {cameraStatus === 'active' && (
                        <Badge variant="secondary" className="animate-pulse gap-1.5">
                          <span className="h-2 w-2 rounded-full bg-emerald-500" />
                          Live
                        </Badge>
                      )}
                      {detectionStatus === 'detected' && (
                        <Badge className="gap-1.5 bg-emerald-500/10 text-emerald-600">
                          <Eye className="h-3 w-3" />
                          {Math.round(confidence * 100)}%
                        </Badge>
                      )}
                    </div>
                  </div>
                </CardHeader>
                <CardContent className="p-0">
                  {/* Camera View */}
                  <div className="relative aspect-video bg-neutral-900">
                    {cameraStatus === 'active' ? (
                      <>
                        <video
                          ref={videoRef}
                          autoPlay
                          playsInline
                          muted
                          className="h-full w-full object-cover"
                        />
                        {/* Canvas overlay for face detection drawings */}
                        <canvas
                          ref={canvasRef}
                          className="absolute inset-0 h-full w-full"
                        />

                        {/* Status overlay */}
                        {detectionStatus === 'no_face' && (
                          <div className="absolute inset-0 flex items-center justify-center bg-black/50">
                            <div className="text-center">
                              <Scan className="mx-auto h-12 w-12 text-white/70 animate-pulse" />
                              <p className="mt-2 text-sm text-white/70">Mencari wajah...</p>
                            </div>
                          </div>
                        )}

                        {detectionStatus === 'multiple_faces' && (
                          <div className="absolute inset-0 flex items-center justify-center bg-amber-500/20">
                            <div className="text-center">
                              <AlertTriangle className="mx-auto h-12 w-12 text-amber-500" />
                              <p className="mt-2 text-sm text-white">Terdeteksi lebih dari 1 wajah</p>
                            </div>
                          </div>
                        )}
                      </>
                    ) : (
                      <div className="flex h-full flex-col items-center justify-center gap-4">
                        <div className="rounded-full bg-muted p-6">
                          <CameraOff className="h-12 w-12 text-muted-foreground" />
                        </div>
                        <div className="text-center">
                          <p className="font-medium text-muted-foreground">Kamera tidak aktif</p>
                          <p className="mt-1 text-sm text-muted-foreground/70">
                            Klik tombol di bawah untuk memulai
                          </p>
                        </div>
                      </div>
                    )}
                  </div>

                  {/* Error Alert */}
                  {error && (
                    <div className="border-t p-4">
                      <Alert variant="destructive">
                        <AlertTriangle className="h-4 w-4" />
                        <AlertTitle>Error</AlertTitle>
                        <AlertDescription>{error}</AlertDescription>
                        <Button
                          variant="ghost"
                          size="sm"
                          className="mt-2"
                          onClick={clearError}
                        >
                          Tutup
                        </Button>
                      </Alert>
                    </div>
                  )}

                  {/* Actions */}
                  <div className="flex gap-2 border-t p-4">
                    {cameraStatus !== 'active' ? (
                      <Button
                        onClick={handleStartCamera}
                        disabled={!isInitialized || isProcessing}
                        className="flex-1 gap-2"
                      >
                        {isProcessing ? (
                          <Loader2 className="h-4 w-4 animate-spin" />
                        ) : (
                          <Camera className="h-4 w-4" />
                        )}
                        {isProcessing ? 'Memuat...' : 'Aktifkan Kamera'}
                      </Button>
                    ) : (
                      <>
                        <Button
                          onClick={handleVerification}
                          disabled={detectionStatus !== 'detected' || isProcessing}
                          className="flex-1 gap-2"
                        >
                          {isProcessing ? (
                            <Loader2 className="h-4 w-4 animate-spin" />
                          ) : (
                            <Shield className="h-4 w-4" />
                          )}
                          Verifikasi Wajah
                        </Button>
                        <Button onClick={handleStopCamera} variant="outline" className="gap-2">
                          <CameraOff className="h-4 w-4" />
                          Matikan
                        </Button>
                      </>
                    )}
                  </div>
                </CardContent>
              </Card>
            </div>

            {/* Side Panel */}
            <div className="space-y-6">
              {/* User Info Card */}
              <Card>
                <CardHeader className="pb-3">
                  <CardTitle className="flex items-center gap-2 text-base">
                    <User className="h-4 w-4 text-primary" />
                    Informasi Pengguna
                  </CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                  <div className="flex items-center gap-4">
                    <div className="relative">
                      <div className="flex h-14 w-14 items-center justify-center rounded-full bg-gradient-to-br from-primary to-primary/70 text-lg font-semibold text-primary-foreground">
                        {user?.name?.split(' ').map((n) => n[0]).join('').slice(0, 2)}
                      </div>
                      <span className="absolute -bottom-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full border-2 border-background bg-emerald-500">
                        <CheckCircle2 className="h-3 w-3 text-white" />
                      </span>
                    </div>
                    <div>
                      <p className="font-semibold">{user?.name}</p>
                      <p className="text-sm text-muted-foreground capitalize">
                        {user?.role?.replace('-', ' ')}
                      </p>
                    </div>
                  </div>

                  <div className="space-y-2 rounded-lg bg-muted/50 p-3">
                    <div className="flex items-center justify-between text-sm">
                      <span className="text-muted-foreground">Status Face ID</span>
                      <Badge variant="secondary" className="gap-1 bg-emerald-500/10 text-emerald-600">
                        <CheckCircle2 className="h-3 w-3" />
                        Terdaftar
                      </Badge>
                    </div>
                    <div className="flex items-center justify-between text-sm">
                      <span className="text-muted-foreground">Verifikasi terakhir</span>
                      <span className="font-medium">Hari ini, 08:30</span>
                    </div>
                  </div>
                </CardContent>
              </Card>

              {/* Detection Status Card */}
              <Card>
                <CardHeader className="pb-3">
                  <CardTitle className="flex items-center gap-2 text-base">
                    <Activity className="h-4 w-4 text-primary" />
                    Status Deteksi
                  </CardTitle>
                </CardHeader>
                <CardContent className="space-y-3">
                  <div className="flex items-center justify-between">
                    <span className="text-sm text-muted-foreground">Model AI</span>
                    <Badge variant={isInitialized ? 'default' : 'secondary'}>
                      {isInitialized ? 'Ready' : 'Loading...'}
                    </Badge>
                  </div>
                  <div className="flex items-center justify-between">
                    <span className="text-sm text-muted-foreground">Kamera</span>
                    <Badge variant={cameraStatus === 'active' ? 'default' : 'secondary'}>
                      {cameraStatus === 'active' ? 'Active' : cameraStatus === 'starting' ? 'Starting...' : 'Off'}
                    </Badge>
                  </div>
                  <div className="flex items-center justify-between">
                    <span className="text-sm text-muted-foreground">Wajah</span>
                    <Badge
                      variant={detectionStatus === 'detected' ? 'default' : 'secondary'}
                      className={detectionStatus === 'detected' ? 'bg-emerald-500' : ''}
                    >
                      {detectionStatus === 'detected' ? 'Terdeteksi' :
                       detectionStatus === 'no_face' ? 'Tidak ada' :
                       detectionStatus === 'multiple_faces' ? 'Multiple' : 'Idle'}
                    </Badge>
                  </div>
                  {detectionStatus === 'detected' && (
                    <div className="pt-2">
                      <div className="flex items-center justify-between text-sm">
                        <span className="text-muted-foreground">Confidence</span>
                        <span className="font-medium">{Math.round(confidence * 100)}%</span>
                      </div>
                      <Progress value={confidence * 100} className="mt-2 h-2" />
                    </div>
                  )}
                </CardContent>
              </Card>

              {/* Quick Tips */}
              <Card>
                <CardHeader className="pb-3">
                  <CardTitle className="flex items-center gap-2 text-base">
                    <Sparkles className="h-4 w-4 text-primary" />
                    Tips Verifikasi
                  </CardTitle>
                </CardHeader>
                <CardContent>
                  <ul className="space-y-2 text-sm text-muted-foreground">
                    <li className="flex items-start gap-2">
                      <CheckCircle2 className="mt-0.5 h-4 w-4 shrink-0 text-emerald-500" />
                      Pastikan pencahayaan cukup terang
                    </li>
                    <li className="flex items-start gap-2">
                      <CheckCircle2 className="mt-0.5 h-4 w-4 shrink-0 text-emerald-500" />
                      Lepas kacamata hitam atau masker
                    </li>
                    <li className="flex items-start gap-2">
                      <CheckCircle2 className="mt-0.5 h-4 w-4 shrink-0 text-emerald-500" />
                      Jaga jarak 30-50cm dari kamera
                    </li>
                    <li className="flex items-start gap-2">
                      <CheckCircle2 className="mt-0.5 h-4 w-4 shrink-0 text-emerald-500" />
                      Hadap langsung ke kamera
                    </li>
                  </ul>
                </CardContent>
              </Card>
            </div>
          </div>
        </TabsContent>

        {/* Enrollment Tab */}
        <TabsContent value="enroll" className="space-y-6">
          <div className="grid gap-6 lg:grid-cols-3">
            <div className="lg:col-span-2">
              <Card>
                <CardHeader>
                  <CardTitle className="flex items-center gap-2">
                    <UserPlus className="h-5 w-5 text-primary" />
                    Pendaftaran Wajah Baru
                  </CardTitle>
                  <CardDescription>
                    Daftarkan wajah Anda untuk verifikasi biometrik. Diperlukan minimal 3 foto dari sudut berbeda.
                  </CardDescription>
                </CardHeader>
                <CardContent className="space-y-6">
                  {/* Progress Steps */}
                  <div className="flex items-center justify-between">
                    {[1, 2, 3].map((step) => (
                      <div key={step} className="flex items-center gap-2">
                        <div
                          className={`flex h-8 w-8 items-center justify-center rounded-full text-sm font-medium ${
                            capturedDescriptors.length >= step
                              ? 'bg-emerald-500 text-white'
                              : capturedDescriptors.length === step - 1
                              ? 'bg-primary text-primary-foreground'
                              : 'bg-muted text-muted-foreground'
                          }`}
                        >
                          {capturedDescriptors.length >= step ? (
                            <CheckCircle2 className="h-4 w-4" />
                          ) : (
                            step
                          )}
                        </div>
                        <span className="text-sm text-muted-foreground">
                          Foto {step}
                        </span>
                        {step < 3 && (
                          <div className={`h-0.5 w-12 ${
                            capturedDescriptors.length >= step ? 'bg-emerald-500' : 'bg-muted'
                          }`} />
                        )}
                      </div>
                    ))}
                  </div>

                  {/* Camera for enrollment */}
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
                        {/* Capture guide overlay */}
                        <div className="absolute inset-0 flex items-center justify-center">
                          <div className="h-64 w-48 rounded-full border-4 border-dashed border-white/30" />
                        </div>
                      </>
                    ) : (
                      <div className="flex h-full flex-col items-center justify-center gap-4">
                        <CameraOff className="h-12 w-12 text-muted-foreground" />
                        <p className="text-muted-foreground">Aktifkan kamera untuk memulai</p>
                      </div>
                    )}
                  </div>

                  {/* Capture Button */}
                  <div className="flex gap-2">
                    {cameraStatus !== 'active' ? (
                      <Button onClick={handleStartCamera} className="flex-1 gap-2">
                        <Camera className="h-4 w-4" />
                        Aktifkan Kamera
                      </Button>
                    ) : (
                      <>
                        <Button
                          onClick={handleEnrollmentCapture}
                          disabled={
                            enrollmentStep === 'capturing' ||
                            detectionStatus !== 'detected' ||
                            capturedDescriptors.length >= 3
                          }
                          className="flex-1 gap-2"
                        >
                          {enrollmentStep === 'capturing' ? (
                            <Loader2 className="h-4 w-4 animate-spin" />
                          ) : (
                            <Camera className="h-4 w-4" />
                          )}
                          {enrollmentStep === 'capturing'
                            ? 'Mengambil...'
                            : `Ambil Foto ${capturedDescriptors.length + 1}/3`}
                        </Button>
                        <Button onClick={handleStopCamera} variant="outline">
                          <CameraOff className="h-4 w-4" />
                        </Button>
                      </>
                    )}
                  </div>

                  {/* Completion message */}
                  {capturedDescriptors.length >= 3 && (
                    <Alert className="border-emerald-500/50 bg-emerald-500/10">
                      <CheckCircle2 className="h-4 w-4 text-emerald-500" />
                      <AlertTitle className="text-emerald-600">Pendaftaran Selesai!</AlertTitle>
                      <AlertDescription>
                        Wajah Anda telah berhasil didaftarkan dan siap digunakan untuk verifikasi.
                      </AlertDescription>
                    </Alert>
                  )}
                </CardContent>
              </Card>
            </div>

            {/* Enrollment Instructions */}
            <Card>
              <CardHeader>
                <CardTitle className="text-base">Panduan Pendaftaran</CardTitle>
              </CardHeader>
              <CardContent>
                <ol className="space-y-4 text-sm">
                  <li className="flex gap-3">
                    <span className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary text-xs text-primary-foreground">
                      1
                    </span>
                    <div>
                      <p className="font-medium">Posisi wajah menghadap depan</p>
                      <p className="text-muted-foreground">Pastikan wajah terlihat jelas</p>
                    </div>
                  </li>
                  <li className="flex gap-3">
                    <span className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary text-xs text-primary-foreground">
                      2
                    </span>
                    <div>
                      <p className="font-medium">Miringkan sedikit ke kiri</p>
                      <p className="text-muted-foreground">Sekitar 15-20 derajat</p>
                    </div>
                  </li>
                  <li className="flex gap-3">
                    <span className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary text-xs text-primary-foreground">
                      3
                    </span>
                    <div>
                      <p className="font-medium">Miringkan sedikit ke kanan</p>
                      <p className="text-muted-foreground">Sekitar 15-20 derajat</p>
                    </div>
                  </li>
                </ol>
              </CardContent>
            </Card>
          </div>
        </TabsContent>

        {/* History Tab */}
        <TabsContent value="history" className="space-y-6">
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <History className="h-5 w-5 text-primary" />
                Riwayat Verifikasi
              </CardTitle>
              <CardDescription>
                Catatan verifikasi wajah 7 hari terakhir
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div className="space-y-4">
                {verificationHistory.map((item) => (
                  <div
                    key={item.id}
                    className="flex items-center justify-between rounded-lg border p-4 transition-colors hover:bg-muted/50"
                  >
                    <div className="flex items-center gap-4">
                      <div
                        className={`flex h-10 w-10 items-center justify-center rounded-full ${
                          item.status === 'success'
                            ? 'bg-emerald-500/10 text-emerald-500'
                            : 'bg-red-500/10 text-red-500'
                        }`}
                      >
                        {item.status === 'success' ? (
                          <CheckCircle2 className="h-5 w-5" />
                        ) : (
                          <XCircle className="h-5 w-5" />
                        )}
                      </div>
                      <div>
                        <p className="font-medium">
                          {item.status === 'success' ? 'Verifikasi Berhasil' : 'Verifikasi Gagal'}
                        </p>
                        <p className="text-sm text-muted-foreground">{item.timestamp}</p>
                      </div>
                    </div>
                    <div className="text-right">
                      <Badge
                        variant={item.status === 'success' ? 'default' : 'destructive'}
                        className={item.status === 'success' ? 'bg-emerald-500' : ''}
                      >
                        {Math.round(item.confidence * 100)}%
                      </Badge>
                      <p className="mt-1 text-xs text-muted-foreground capitalize">
                        {item.method} recognition
                      </p>
                    </div>
                  </div>
                ))}
              </div>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  );
}
