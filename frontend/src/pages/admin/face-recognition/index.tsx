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
import { PageHeader, StatsGrid, type StatItem } from '@/components/shared';
import { LoadingState } from '@/components/states';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useAuthStore } from '@/stores';
import { useCameraCapture } from '@/hooks/use-camera-capture';
import { verifyFaceDeepFace, extractEmbeddingDeepFace } from '@/lib/api/face-recognition';

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
  const [capturedDescriptors, setCapturedDescriptors] = useState<any[]>([]);
  const [verifyStatus, setVerifyStatus] = useState<'idle' | 'verifying' | 'success' | 'failed'>('idle');
  const [verifyResult, setVerifyResult] = useState<any>(null);
  const [error, setError] = useState<string | null>(null);

  // Mock verification history
  const [verificationHistory] = useState<VerificationHistory[]>([
    { id: 1, timestamp: '2024-01-15 08:30:15', status: 'success', confidence: 0.94, method: 'deepface' },
    { id: 2, timestamp: '2024-01-15 17:05:22', status: 'success', confidence: 0.91, method: 'deepface' },
    { id: 3, timestamp: '2024-01-14 08:28:45', status: 'failed', confidence: 0.42, method: 'deepface' },
    { id: 4, timestamp: '2024-01-14 08:29:10', status: 'success', confidence: 0.89, method: 'deepface' },
  ]);

  const {
    videoRef,
    cameraStatus,
    errorMessage,
    startCamera,
    stopCamera,
    captureImage,
  } = useCameraCapture();

  // Handle verification with DeepFace
  const handleVerification = async () => {
    if (cameraStatus !== 'active') return;

    setVerifyStatus('verifying');
    setError(null);

    try {
      const imageFile = await captureImage();
      const result = await verifyFaceDeepFace(imageFile);

      if (result.success && result.matched) {
        setVerifyStatus('success');
        setVerifyResult(result);
        console.log('Verified:', result.employee?.name, 'Confidence:', result.confidence);
      } else {
        setVerifyStatus('failed');
        setError(result.message || 'Wajah tidak dikenali');
        console.log('Verification failed:', result.message);
      }
    } catch (err: any) {
      console.error('Verification error:', err);
      setVerifyStatus('failed');
      setError(err.response?.data?.message || 'Gagal memverifikasi wajah');
    }
  };

  // Handle enrollment capture with DeepFace
  const handleEnrollmentCapture = async () => {
    if (!user?.id || cameraStatus !== 'active') return;

    setEnrollmentStep('capturing');
    setError(null);

    try {
      const imageFile = await captureImage();

      setEnrollmentStep('processing');
      const result = await extractEmbeddingDeepFace(imageFile);

      if (result.success && result.embedding) {
        setCapturedDescriptors(prev => [...prev, result]);

        if (capturedDescriptors.length >= 2) {
          setEnrollmentStep('complete');
        } else {
          setEnrollmentStep('idle');
        }
      } else {
        throw new Error(result.message || 'Gagal mengekstrak data wajah');
      }
    } catch (err: any) {
      console.error('Capture error:', err);
      setError(err.response?.data?.message || err.message || 'Gagal mengambil foto');
      setEnrollmentStep('idle');
    }
  };

  // Start camera
  const handleStartCamera = async () => {
    setError(null);
    try {
      await startCamera();
    } catch (err) {
      setError(errorMessage || 'Gagal mengaktifkan kamera');
    }
  };

  // Stop camera
  const handleStopCamera = () => {
    stopCamera();
    setVerifyStatus('idle');
    setVerifyResult(null);
  };

  // Stats data
  const stats: StatItem[] = [
    {
      label: 'Verifikasi Sukses',
      value: '98.5%',
      description: 'Tingkat keberhasilan',
      icon: CheckCircle2,
      color: 'success',
    },
    {
      label: 'Total Verifikasi',
      value: '1,247',
      description: 'Bulan ini',
      icon: Activity,
      color: 'primary',
    },
    {
      label: 'Waktu Rata-rata',
      value: '1.2s',
      description: 'Per verifikasi',
      icon: Clock,
      color: 'warning',
    },
  ];

  return (
    <div className="space-y-4 p-4 sm:space-y-6 sm:p-6">
      {/* Page Header */}
      <PageHeader
        title="Face Recognition"
        description="Verifikasi biometrik dengan AI-powered face detection"
        icon={Scan}
      />

      {/* Stats Grid */}
      <StatsGrid stats={stats} columns={3} variant="cards" />

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
                        <CardTitle className="text-base">Camera - DeepFace ArcFace</CardTitle>
                        <CardDescription className="text-xs">
                          Server-side face recognition (99.82% accuracy)
                        </CardDescription>
                      </div>
                    </div>
                    <div className="flex items-center gap-2">
                      {cameraStatus === 'active' && (
                        <Badge variant="secondary" className="animate-pulse gap-1.5">
                          <span className="h-2 w-2 rounded-full bg-success" />
                          Ready
                        </Badge>
                      )}
                      {verifyStatus === 'success' && verifyResult && (
                        <Badge className="gap-1.5 bg-success/10 text-success">
                          <Eye className="h-3 w-3" />
                          {Math.round((verifyResult.confidence || verifyResult.similarity) * 100)}%
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

                        {/* Status overlay */}
                        {verifyStatus === 'verifying' && (
                          <div className="absolute inset-0 flex items-center justify-center bg-black/50">
                            <div className="text-center">
                              <Loader2 className="mx-auto h-12 w-12 text-white animate-spin" />
                              <p className="mt-2 text-sm text-white">Memverifikasi dengan DeepFace...</p>
                            </div>
                          </div>
                        )}

                        {verifyStatus === 'success' && verifyResult && (
                          <div className="absolute inset-0 flex items-center justify-center bg-success/20">
                            <div className="text-center bg-background/90 p-6 rounded-lg">
                              <CheckCircle2 className="mx-auto h-12 w-12 text-success" />
                              <p className="mt-2 text-sm font-semibold">{verifyResult.employee?.name || 'Terverifikasi'}</p>
                              <p className="text-xs text-muted-foreground">
                                Confidence: {Math.round((verifyResult.confidence || verifyResult.similarity) * 100)}%
                              </p>
                            </div>
                          </div>
                        )}

                        {verifyStatus === 'failed' && (
                          <div className="absolute inset-0 flex items-center justify-center bg-destructive/20">
                            <div className="text-center bg-background/90 p-6 rounded-lg">
                              <XCircle className="mx-auto h-12 w-12 text-destructive" />
                              <p className="mt-2 text-sm font-semibold">Verifikasi Gagal</p>
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
                          onClick={() => setError(null)}
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
                        disabled={cameraStatus === 'starting'}
                        className="flex-1 gap-2"
                      >
                        {cameraStatus === 'starting' ? (
                          <Loader2 className="h-4 w-4 animate-spin" />
                        ) : (
                          <Camera className="h-4 w-4" />
                        )}
                        {cameraStatus === 'starting' ? 'Memuat...' : 'Aktifkan Kamera'}
                      </Button>
                    ) : (
                      <>
                        <Button
                          onClick={handleVerification}
                          disabled={verifyStatus === 'verifying'}
                          className="flex-1 gap-2"
                        >
                          {verifyStatus === 'verifying' ? (
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
                        {user?.name ? user.name.split(' ').map((n) => n[0]).join('').slice(0, 2) : 'U'}
                      </div>
                      <span className="absolute -bottom-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full border-2 border-background bg-success">
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
                      <Badge variant="secondary" className="gap-1 bg-success/10 text-success">
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
                    Status DeepFace
                  </CardTitle>
                </CardHeader>
                <CardContent className="space-y-3">
                  <div className="flex items-center justify-between">
                    <span className="text-sm text-muted-foreground">Model</span>
                    <Badge variant="default">
                      ArcFace 512-d
                    </Badge>
                  </div>
                  <div className="flex items-center justify-between">
                    <span className="text-sm text-muted-foreground">Kamera</span>
                    <Badge variant={cameraStatus === 'active' ? 'default' : 'secondary'}>
                      {cameraStatus === 'active' ? 'Active' : cameraStatus === 'starting' ? 'Starting...' : 'Off'}
                    </Badge>
                  </div>
                  <div className="flex items-center justify-between">
                    <span className="text-sm text-muted-foreground">Status</span>
                    <Badge
                      variant={verifyStatus === 'success' ? 'default' : 'secondary'}
                      className={verifyStatus === 'success' ? 'bg-success' : verifyStatus === 'failed' ? 'bg-destructive' : ''}
                    >
                      {verifyStatus === 'verifying' ? 'Processing' :
                        verifyStatus === 'success' ? 'Verified' :
                          verifyStatus === 'failed' ? 'Failed' : 'Ready'}
                    </Badge>
                  </div>
                  {verifyStatus === 'success' && verifyResult && (
                    <div className="pt-2">
                      <div className="flex items-center justify-between text-sm">
                        <span className="text-muted-foreground">Confidence</span>
                        <span className="font-medium">{Math.round((verifyResult.confidence || verifyResult.similarity) * 100)}%</span>
                      </div>
                      <Progress value={(verifyResult.confidence || verifyResult.similarity) * 100} className="mt-2 h-2" />
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
                      <CheckCircle2 className="mt-0.5 h-4 w-4 shrink-0 text-success" />
                      Pastikan pencahayaan cukup terang
                    </li>
                    <li className="flex items-start gap-2">
                      <CheckCircle2 className="mt-0.5 h-4 w-4 shrink-0 text-success" />
                      Lepas kacamata hitam atau masker
                    </li>
                    <li className="flex items-start gap-2">
                      <CheckCircle2 className="mt-0.5 h-4 w-4 shrink-0 text-success" />
                      Jaga jarak 30-50cm dari kamera
                    </li>
                    <li className="flex items-start gap-2">
                      <CheckCircle2 className="mt-0.5 h-4 w-4 shrink-0 text-success" />
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
                          className={`flex h-8 w-8 items-center justify-center rounded-full text-sm font-medium ${capturedDescriptors.length >= step
                            ? 'bg-success text-white'
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
                          <div className={`h-0.5 w-12 ${capturedDescriptors.length >= step ? 'bg-success' : 'bg-muted'
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
                        {/* Capture guide overlay */}
                        <div className="absolute inset-0 flex items-center justify-center pointer-events-none">
                          <div className="h-64 w-48 rounded-full border-4 border-dashed border-white/30" />
                        </div>

                        {/* Processing overlay */}
                        {(enrollmentStep === 'capturing' || enrollmentStep === 'processing') && (
                          <div className="absolute inset-0 flex items-center justify-center bg-black/50">
                            <div className="text-center">
                              <Loader2 className="mx-auto h-12 w-12 text-white animate-spin" />
                              <p className="mt-2 text-sm text-white">
                                {enrollmentStep === 'capturing' ? 'Mengambil foto...' : 'Memproses dengan DeepFace...'}
                              </p>
                            </div>
                          </div>
                        )}
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
                            enrollmentStep === 'processing' ||
                            capturedDescriptors.length >= 3
                          }
                          className="flex-1 gap-2"
                        >
                          {(enrollmentStep === 'capturing' || enrollmentStep === 'processing') ? (
                            <Loader2 className="h-4 w-4 animate-spin" />
                          ) : (
                            <Camera className="h-4 w-4" />
                          )}
                          {enrollmentStep === 'capturing' || enrollmentStep === 'processing'
                            ? 'Memproses...'
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
                    <Alert className="border-success/50 bg-success/10">
                      <CheckCircle2 className="h-4 w-4 text-success" />
                      <AlertTitle className="text-success">Pendaftaran Selesai!</AlertTitle>
                      <AlertDescription>
                        Wajah Anda telah berhasil didaftarkan dengan DeepFace ArcFace (512-d) dan siap digunakan untuk verifikasi.
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
                        className={`flex h-10 w-10 items-center justify-center rounded-full ${item.status === 'success'
                          ? 'bg-success/10 text-success'
                          : 'bg-destructive/10 text-destructive'
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
                        className={item.status === 'success' ? 'bg-success' : ''}
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
