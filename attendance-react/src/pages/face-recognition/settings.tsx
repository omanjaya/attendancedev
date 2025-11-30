import { useState } from 'react';
import {
  Settings,
  Shield,
  Camera,
  Eye,
  AlertTriangle,
  Save,
  RotateCcw,
  Info,
  Sliders,
  CheckCircle2,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Slider } from '@/components/ui/slider';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import type { FaceRecognitionSettings } from '@/types/face-recognition';

export default function FaceRecognitionSettingsPage() {
  const [settings, setSettings] = useState<FaceRecognitionSettings>({
    confidence_threshold: 0.6,
    liveness_threshold: 0.8,
    max_detection_distance: 2.0,
    enable_liveness: true,
    enable_expressions: true,
    camera_resolution: 'medium',
  });

  const [isSaving, setIsSaving] = useState(false);
  const [showSavedAlert, setShowSavedAlert] = useState(false);

  const handleSave = async () => {
    setIsSaving(true);
    // Simulate API call
    await new Promise((resolve) => setTimeout(resolve, 1000));
    setIsSaving(false);
    setShowSavedAlert(true);
    setTimeout(() => setShowSavedAlert(false), 3000);
    console.log('Settings saved:', settings);
  };

  const handleReset = () => {
    setSettings({
      confidence_threshold: 0.6,
      liveness_threshold: 0.8,
      max_detection_distance: 2.0,
      enable_liveness: true,
      enable_expressions: true,
      camera_resolution: 'medium',
    });
  };

  const getConfidenceLevel = (value: number) => {
    if (value >= 0.8) return { label: 'Tinggi', color: 'text-success' };
    if (value >= 0.6) return { label: 'Sedang', color: 'text-warning' };
    return { label: 'Rendah', color: 'text-destructive' };
  };

  return (
    <div className="space-y-6 p-6">
      {/* Header */}
      <div className="relative overflow-hidden rounded-2xl bg-gradient-to-br from-primary/10 via-primary/5 to-background p-8">
        <div className="absolute -right-20 -top-20 h-64 w-64 rounded-full bg-primary/10 blur-3xl" />
        <div className="absolute -bottom-20 -left-20 h-64 w-64 rounded-full bg-primary/5 blur-3xl" />

        <div className="relative flex items-center justify-between">
          <div className="flex items-center gap-3">
            <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-primary/10">
              <Settings className="h-6 w-6 text-primary" />
            </div>
            <div>
              <h1 className="text-2xl font-bold text-foreground">Pengaturan Face Recognition</h1>
              <p className="text-sm text-muted-foreground">
                Konfigurasi parameter deteksi dan verifikasi wajah
              </p>
            </div>
          </div>

          <div className="flex gap-2">
            <Button variant="outline" onClick={handleReset} className="gap-2">
              <RotateCcw className="h-4 w-4" />
              Reset
            </Button>
            <Button onClick={handleSave} disabled={isSaving} className="gap-2">
              {isSaving ? (
                <span className="h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent" />
              ) : (
                <Save className="h-4 w-4" />
              )}
              Simpan
            </Button>
          </div>
        </div>
      </div>

      {/* Saved Alert */}
      {showSavedAlert && (
        <Alert className="border-success/50 bg-success/10">
          <CheckCircle2 className="h-4 w-4 text-success" />
          <AlertTitle className="text-success">Berhasil!</AlertTitle>
          <AlertDescription>
            Pengaturan berhasil disimpan dan akan diterapkan pada verifikasi selanjutnya.
          </AlertDescription>
        </Alert>
      )}

      <div className="grid gap-6 lg:grid-cols-2">
        {/* Detection Settings */}
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <Shield className="h-5 w-5 text-primary" />
              Pengaturan Deteksi
            </CardTitle>
            <CardDescription>
              Konfigurasi akurasi dan sensitivitas deteksi wajah
            </CardDescription>
          </CardHeader>
          <CardContent className="space-y-6">
            {/* Confidence Threshold */}
            <div className="space-y-4">
              <div className="flex items-center justify-between">
                <div>
                  <Label className="text-sm font-medium">Confidence Threshold</Label>
                  <p className="text-xs text-muted-foreground">
                    Tingkat kepercayaan minimum untuk verifikasi
                  </p>
                </div>
                <div className="flex items-center gap-2">
                  <span className="text-lg font-bold">{Math.round(settings.confidence_threshold * 100)}%</span>
                  <Badge
                    variant="secondary"
                    className={getConfidenceLevel(settings.confidence_threshold).color}
                  >
                    {getConfidenceLevel(settings.confidence_threshold).label}
                  </Badge>
                </div>
              </div>
              <Slider
                value={[settings.confidence_threshold * 100]}
                onValueChange={(values: number[]) =>
                  setSettings((prev) => ({ ...prev, confidence_threshold: values[0] / 100 }))
                }
                max={100}
                min={40}
                step={5}
                className="w-full"
              />
              <div className="flex justify-between text-xs text-muted-foreground">
                <span>40% (Longgar)</span>
                <span>100% (Ketat)</span>
              </div>
            </div>

            <Separator />

            {/* Max Detection Distance */}
            <div className="space-y-4">
              <div className="flex items-center justify-between">
                <div>
                  <Label className="text-sm font-medium">Jarak Deteksi Maksimal</Label>
                  <p className="text-xs text-muted-foreground">
                    Toleransi jarak euclidean antar descriptor
                  </p>
                </div>
                <span className="text-lg font-bold">{settings.max_detection_distance.toFixed(1)}</span>
              </div>
              <Slider
                value={[settings.max_detection_distance * 10]}
                onValueChange={(values: number[]) =>
                  setSettings((prev) => ({ ...prev, max_detection_distance: values[0] / 10 }))
                }
                max={30}
                min={5}
                step={1}
                className="w-full"
              />
              <div className="flex justify-between text-xs text-muted-foreground">
                <span>0.5 (Sensitif)</span>
                <span>3.0 (Toleran)</span>
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Liveness Settings */}
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <Eye className="h-5 w-5 text-primary" />
              Pengaturan Liveness
            </CardTitle>
            <CardDescription>
              Deteksi anti-spoofing untuk mencegah foto/video palsu
            </CardDescription>
          </CardHeader>
          <CardContent className="space-y-6">
            {/* Enable Liveness */}
            <div className="flex items-center justify-between rounded-lg border p-4">
              <div className="space-y-0.5">
                <Label className="text-sm font-medium">Aktifkan Liveness Detection</Label>
                <p className="text-xs text-muted-foreground">
                  Verifikasi bahwa wajah adalah orang asli, bukan foto
                </p>
              </div>
              <Switch
                checked={settings.enable_liveness}
                onCheckedChange={(checked) =>
                  setSettings((prev) => ({ ...prev, enable_liveness: checked }))
                }
              />
            </div>

            {/* Liveness Threshold */}
            {settings.enable_liveness && (
              <div className="space-y-4">
                <div className="flex items-center justify-between">
                  <div>
                    <Label className="text-sm font-medium">Liveness Threshold</Label>
                    <p className="text-xs text-muted-foreground">
                      Skor minimum untuk lulus liveness check
                    </p>
                  </div>
                  <span className="text-lg font-bold">{Math.round(settings.liveness_threshold * 100)}%</span>
                </div>
                <Slider
                  value={[settings.liveness_threshold * 100]}
                  onValueChange={(values: number[]) =>
                    setSettings((prev) => ({ ...prev, liveness_threshold: values[0] / 100 }))
                  }
                  max={100}
                  min={50}
                  step={5}
                  className="w-full"
                />
              </div>
            )}

            <Separator />

            {/* Enable Expressions */}
            <div className="flex items-center justify-between rounded-lg border p-4">
              <div className="space-y-0.5">
                <Label className="text-sm font-medium">Deteksi Ekspresi Wajah</Label>
                <p className="text-xs text-muted-foreground">
                  Analisis ekspresi seperti senyum, sedih, dll.
                </p>
              </div>
              <Switch
                checked={settings.enable_expressions}
                onCheckedChange={(checked) =>
                  setSettings((prev) => ({ ...prev, enable_expressions: checked }))
                }
              />
            </div>
          </CardContent>
        </Card>

        {/* Camera Settings */}
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <Camera className="h-5 w-5 text-primary" />
              Pengaturan Kamera
            </CardTitle>
            <CardDescription>
              Konfigurasi kualitas video dan resolusi
            </CardDescription>
          </CardHeader>
          <CardContent className="space-y-6">
            {/* Camera Resolution */}
            <div className="space-y-3">
              <Label className="text-sm font-medium">Resolusi Kamera</Label>
              <Select
                value={settings.camera_resolution}
                onValueChange={(value: 'low' | 'medium' | 'high') =>
                  setSettings((prev) => ({ ...prev, camera_resolution: value }))
                }
              >
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="low">
                    <div className="flex items-center gap-2">
                      <span>Rendah</span>
                      <span className="text-xs text-muted-foreground">(320x240)</span>
                    </div>
                  </SelectItem>
                  <SelectItem value="medium">
                    <div className="flex items-center gap-2">
                      <span>Sedang</span>
                      <span className="text-xs text-muted-foreground">(640x480)</span>
                    </div>
                  </SelectItem>
                  <SelectItem value="high">
                    <div className="flex items-center gap-2">
                      <span>Tinggi</span>
                      <span className="text-xs text-muted-foreground">(1280x720)</span>
                    </div>
                  </SelectItem>
                </SelectContent>
              </Select>
              <p className="text-xs text-muted-foreground">
                Resolusi lebih tinggi meningkatkan akurasi tapi memerlukan bandwidth lebih besar
              </p>
            </div>

            {/* Resolution Info */}
            <div className="rounded-lg bg-muted/50 p-4">
              <div className="flex items-start gap-3">
                <Info className="mt-0.5 h-4 w-4 text-primary" />
                <div className="space-y-1 text-sm">
                  <p className="font-medium">Tips Penggunaan:</p>
                  <ul className="list-inside list-disc space-y-0.5 text-muted-foreground">
                    <li>Gunakan "Rendah" untuk koneksi lambat</li>
                    <li>Gunakan "Sedang" untuk keseimbangan</li>
                    <li>Gunakan "Tinggi" untuk akurasi maksimal</li>
                  </ul>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Current Configuration Summary */}
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <Sliders className="h-5 w-5 text-primary" />
              Ringkasan Konfigurasi
            </CardTitle>
            <CardDescription>
              Status pengaturan saat ini
            </CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="space-y-3">
              <div className="flex items-center justify-between rounded-lg bg-muted/50 p-3">
                <span className="text-sm text-muted-foreground">Confidence Threshold</span>
                <Badge variant="secondary">{Math.round(settings.confidence_threshold * 100)}%</Badge>
              </div>
              <div className="flex items-center justify-between rounded-lg bg-muted/50 p-3">
                <span className="text-sm text-muted-foreground">Liveness Detection</span>
                <Badge variant={settings.enable_liveness ? 'default' : 'secondary'}>
                  {settings.enable_liveness ? 'Aktif' : 'Nonaktif'}
                </Badge>
              </div>
              <div className="flex items-center justify-between rounded-lg bg-muted/50 p-3">
                <span className="text-sm text-muted-foreground">Liveness Threshold</span>
                <Badge variant="secondary">{Math.round(settings.liveness_threshold * 100)}%</Badge>
              </div>
              <div className="flex items-center justify-between rounded-lg bg-muted/50 p-3">
                <span className="text-sm text-muted-foreground">Expression Detection</span>
                <Badge variant={settings.enable_expressions ? 'default' : 'secondary'}>
                  {settings.enable_expressions ? 'Aktif' : 'Nonaktif'}
                </Badge>
              </div>
              <div className="flex items-center justify-between rounded-lg bg-muted/50 p-3">
                <span className="text-sm text-muted-foreground">Camera Resolution</span>
                <Badge variant="secondary" className="capitalize">
                  {settings.camera_resolution}
                </Badge>
              </div>
            </div>

            {/* Warning */}
            {settings.confidence_threshold < 0.5 && (
              <Alert variant="destructive">
                <AlertTriangle className="h-4 w-4" />
                <AlertTitle>Peringatan</AlertTitle>
                <AlertDescription>
                  Confidence threshold yang terlalu rendah dapat menyebabkan false positive (wajah yang salah dianggap cocok).
                </AlertDescription>
              </Alert>
            )}
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
