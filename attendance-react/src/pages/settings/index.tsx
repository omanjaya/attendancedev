import { useState } from 'react';
import {
  Sun,
  Moon,
  Monitor,
  Globe,
  Bell,
  BellOff,
  Shield,
  Eye,
  EyeOff,
  Loader2,
  RotateCcw,
  Download,
  Check,
  AlertTriangle,
  Palette,
  Calendar,
  Clock,
  Mail,
  Smartphone,
  MapPin,
  Users,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
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
import {
  useSettings,
  useUpdateAppearance,
  useUpdateNotifications,
  useUpdatePrivacy,
  useResetSettings,
  useExportSettings,
} from '@/hooks/use-settings';
import {
  themeLabels,
  languageLabels,
  dateFormatLabels,
  timeFormatLabels,
  type Theme,
  type Language,
  type DateFormat,
  type TimeFormat,
} from '@/types/settings';

export default function SettingsPage() {
  const { data: settings, isLoading } = useSettings();
  const updateAppearance = useUpdateAppearance();
  const updateNotifications = useUpdateNotifications();
  const updatePrivacy = useUpdatePrivacy();
  const resetSettings = useResetSettings();
  const exportSettings = useExportSettings();

  const [resetDialog, setResetDialog] = useState(false);
  const [successMessage, setSuccessMessage] = useState<string | null>(null);

  const showSuccess = (message: string) => {
    setSuccessMessage(message);
    setTimeout(() => setSuccessMessage(null), 3000);
  };

  const handleThemeChange = (theme: Theme) => {
    updateAppearance.mutate({ theme }, {
      onSuccess: () => showSuccess('Tema berhasil diubah'),
    });
  };

  const handleLanguageChange = (language: Language) => {
    updateAppearance.mutate({ language }, {
      onSuccess: () => showSuccess('Bahasa berhasil diubah'),
    });
  };

  const handleDateFormatChange = (date_format: DateFormat) => {
    updateAppearance.mutate({ date_format }, {
      onSuccess: () => showSuccess('Format tanggal berhasil diubah'),
    });
  };

  const handleTimeFormatChange = (time_format: TimeFormat) => {
    updateAppearance.mutate({ time_format }, {
      onSuccess: () => showSuccess('Format waktu berhasil diubah'),
    });
  };

  const handleAppearanceToggle = (key: 'compact_mode' | 'animations_enabled', value: boolean) => {
    updateAppearance.mutate({ [key]: value }, {
      onSuccess: () => showSuccess('Pengaturan tampilan berhasil diubah'),
    });
  };

  const handleNotificationToggle = (key: string, value: boolean) => {
    updateNotifications.mutate({ [key]: value }, {
      onSuccess: () => showSuccess('Pengaturan notifikasi berhasil diubah'),
    });
  };

  const handlePrivacyToggle = (key: string, value: boolean) => {
    updatePrivacy.mutate({ [key]: value }, {
      onSuccess: () => showSuccess('Pengaturan privasi berhasil diubah'),
    });
  };

  const handleResetSettings = () => {
    resetSettings.mutate(undefined, {
      onSuccess: () => {
        setResetDialog(false);
        showSuccess('Pengaturan berhasil direset ke default');
      },
    });
  };

  const handleExportSettings = async () => {
    try {
      const json = await exportSettings.mutateAsync();
      const blob = new Blob([json], { type: 'application/json' });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = 'settings.json';
      a.click();
      URL.revokeObjectURL(url);
      showSuccess('Pengaturan berhasil diekspor');
    } catch {
      // Error handled
    }
  };

  const getThemeIcon = (theme: Theme) => {
    switch (theme) {
      case 'light':
        return <Sun className="h-4 w-4" />;
      case 'dark':
        return <Moon className="h-4 w-4" />;
      case 'system':
        return <Monitor className="h-4 w-4" />;
    }
  };

  if (isLoading || !settings) {
    return (
      <div className="flex h-[50vh] items-center justify-center">
        <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
      </div>
    );
  }

  return (
    <div className="p-6 space-y-6">
      {/* Header */}
      <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold">Pengaturan</h1>
          <p className="text-muted-foreground">Kelola preferensi aplikasi Anda</p>
        </div>
        <div className="flex gap-2">
          <Button variant="outline" onClick={handleExportSettings} disabled={exportSettings.isPending}>
            {exportSettings.isPending ? (
              <Loader2 className="h-4 w-4 mr-2 animate-spin" />
            ) : (
              <Download className="h-4 w-4 mr-2" />
            )}
            Ekspor
          </Button>
          <Button variant="outline" onClick={() => setResetDialog(true)}>
            <RotateCcw className="h-4 w-4 mr-2" />
            Reset
          </Button>
        </div>
      </div>

      {/* Success Message */}
      {successMessage && (
        <Alert className="bg-success/10 border-success/20">
          <Check className="h-4 w-4 text-success" />
          <AlertDescription className="text-success">{successMessage}</AlertDescription>
        </Alert>
      )}

      {/* Settings Tabs */}
      <Tabs defaultValue="appearance" className="space-y-4">
        <TabsList className="grid w-full grid-cols-3 md:w-auto md:inline-flex">
          <TabsTrigger value="appearance">
            <Palette className="h-4 w-4 mr-2" />
            Tampilan
          </TabsTrigger>
          <TabsTrigger value="notifications">
            <Bell className="h-4 w-4 mr-2" />
            Notifikasi
          </TabsTrigger>
          <TabsTrigger value="privacy">
            <Shield className="h-4 w-4 mr-2" />
            Privasi
          </TabsTrigger>
        </TabsList>

        {/* Appearance Tab */}
        <TabsContent value="appearance" className="space-y-4">
          <Card>
            <CardHeader>
              <CardTitle>Tema</CardTitle>
              <CardDescription>Sesuaikan tampilan aplikasi</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="grid grid-cols-3 gap-4">
                {(['light', 'dark', 'system'] as Theme[]).map((theme) => (
                  <button
                    key={theme}
                    onClick={() => handleThemeChange(theme)}
                    className={`flex flex-col items-center gap-2 p-4 rounded-lg border-2 transition-colors ${
                      settings.appearance.theme === theme
                        ? 'border-primary bg-primary/5'
                        : 'border-border hover:border-primary/50'
                    }`}
                  >
                    <div
                      className={`p-3 rounded-full ${
                        settings.appearance.theme === theme
                          ? 'bg-primary text-primary-foreground'
                          : 'bg-muted'
                      }`}
                    >
                      {getThemeIcon(theme)}
                    </div>
                    <span className="text-sm font-medium">{themeLabels[theme]}</span>
                  </button>
                ))}
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Regional</CardTitle>
              <CardDescription>Pengaturan bahasa dan format</CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-3">
                  <Globe className="h-5 w-5 text-muted-foreground" />
                  <div>
                    <Label>Bahasa</Label>
                    <p className="text-sm text-muted-foreground">Pilih bahasa aplikasi</p>
                  </div>
                </div>
                <Select
                  value={settings.appearance.language}
                  onValueChange={(value) => handleLanguageChange(value as Language)}
                >
                  <SelectTrigger className="w-[180px]">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    {Object.entries(languageLabels).map(([value, label]) => (
                      <SelectItem key={value} value={value}>
                        {label}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>

              <div className="flex items-center justify-between">
                <div className="flex items-center gap-3">
                  <Calendar className="h-5 w-5 text-muted-foreground" />
                  <div>
                    <Label>Format Tanggal</Label>
                    <p className="text-sm text-muted-foreground">Pilih format tanggal</p>
                  </div>
                </div>
                <Select
                  value={settings.appearance.date_format}
                  onValueChange={(value) => handleDateFormatChange(value as DateFormat)}
                >
                  <SelectTrigger className="w-[180px]">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    {Object.entries(dateFormatLabels).map(([value, label]) => (
                      <SelectItem key={value} value={value}>
                        {label}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>

              <div className="flex items-center justify-between">
                <div className="flex items-center gap-3">
                  <Clock className="h-5 w-5 text-muted-foreground" />
                  <div>
                    <Label>Format Waktu</Label>
                    <p className="text-sm text-muted-foreground">Pilih format waktu</p>
                  </div>
                </div>
                <Select
                  value={settings.appearance.time_format}
                  onValueChange={(value) => handleTimeFormatChange(value as TimeFormat)}
                >
                  <SelectTrigger className="w-[180px]">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    {Object.entries(timeFormatLabels).map(([value, label]) => (
                      <SelectItem key={value} value={value}>
                        {label}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Preferensi Tampilan</CardTitle>
              <CardDescription>Sesuaikan pengalaman visual</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="flex items-center justify-between">
                <div>
                  <Label>Mode Kompak</Label>
                  <p className="text-sm text-muted-foreground">Kurangi jarak antar elemen</p>
                </div>
                <Switch
                  checked={settings.appearance.compact_mode}
                  onCheckedChange={(checked) => handleAppearanceToggle('compact_mode', checked)}
                />
              </div>
              <div className="flex items-center justify-between">
                <div>
                  <Label>Animasi</Label>
                  <p className="text-sm text-muted-foreground">Aktifkan animasi transisi</p>
                </div>
                <Switch
                  checked={settings.appearance.animations_enabled}
                  onCheckedChange={(checked) => handleAppearanceToggle('animations_enabled', checked)}
                />
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        {/* Notifications Tab */}
        <TabsContent value="notifications" className="space-y-4">
          <Card>
            <CardHeader>
              <CardTitle>Notifikasi Umum</CardTitle>
              <CardDescription>Kelola cara Anda menerima notifikasi</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-3">
                  <Mail className="h-5 w-5 text-muted-foreground" />
                  <div>
                    <Label>Notifikasi Email</Label>
                    <p className="text-sm text-muted-foreground">Terima pemberitahuan via email</p>
                  </div>
                </div>
                <Switch
                  checked={settings.notifications.email_notifications}
                  onCheckedChange={(checked) =>
                    handleNotificationToggle('email_notifications', checked)
                  }
                />
              </div>
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-3">
                  <Smartphone className="h-5 w-5 text-muted-foreground" />
                  <div>
                    <Label>Notifikasi Push</Label>
                    <p className="text-sm text-muted-foreground">
                      Terima notifikasi push di browser
                    </p>
                  </div>
                </div>
                <Switch
                  checked={settings.notifications.push_notifications}
                  onCheckedChange={(checked) =>
                    handleNotificationToggle('push_notifications', checked)
                  }
                />
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Notifikasi Aktivitas</CardTitle>
              <CardDescription>Pilih aktivitas yang ingin Anda ketahui</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-3">
                  <Clock className="h-5 w-5 text-muted-foreground" />
                  <div>
                    <Label>Pengingat Absensi</Label>
                    <p className="text-sm text-muted-foreground">
                      Ingatkan untuk check-in/check-out
                    </p>
                  </div>
                </div>
                <Switch
                  checked={settings.notifications.attendance_reminders}
                  onCheckedChange={(checked) =>
                    handleNotificationToggle('attendance_reminders', checked)
                  }
                />
              </div>
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-3">
                  <Calendar className="h-5 w-5 text-muted-foreground" />
                  <div>
                    <Label>Update Cuti</Label>
                    <p className="text-sm text-muted-foreground">
                      Notifikasi status persetujuan cuti
                    </p>
                  </div>
                </div>
                <Switch
                  checked={settings.notifications.leave_updates}
                  onCheckedChange={(checked) => handleNotificationToggle('leave_updates', checked)}
                />
              </div>
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-3">
                  <Bell className="h-5 w-5 text-muted-foreground" />
                  <div>
                    <Label>Notifikasi Payroll</Label>
                    <p className="text-sm text-muted-foreground">Pemberitahuan slip gaji tersedia</p>
                  </div>
                </div>
                <Switch
                  checked={settings.notifications.payroll_notifications}
                  onCheckedChange={(checked) =>
                    handleNotificationToggle('payroll_notifications', checked)
                  }
                />
              </div>
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-3">
                  <Calendar className="h-5 w-5 text-muted-foreground" />
                  <div>
                    <Label>Perubahan Jadwal</Label>
                    <p className="text-sm text-muted-foreground">
                      Notifikasi jika jadwal berubah
                    </p>
                  </div>
                </div>
                <Switch
                  checked={settings.notifications.schedule_changes}
                  onCheckedChange={(checked) =>
                    handleNotificationToggle('schedule_changes', checked)
                  }
                />
              </div>
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-3">
                  <Shield className="h-5 w-5 text-muted-foreground" />
                  <div>
                    <Label>Alert Keamanan</Label>
                    <p className="text-sm text-muted-foreground">
                      Pemberitahuan aktivitas mencurigakan
                    </p>
                  </div>
                </div>
                <Switch
                  checked={settings.notifications.security_alerts}
                  onCheckedChange={(checked) => handleNotificationToggle('security_alerts', checked)}
                />
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Email Marketing</CardTitle>
              <CardDescription>Kelola komunikasi promosi</CardDescription>
            </CardHeader>
            <CardContent>
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-3">
                  {settings.notifications.marketing_emails ? (
                    <Bell className="h-5 w-5 text-muted-foreground" />
                  ) : (
                    <BellOff className="h-5 w-5 text-muted-foreground" />
                  )}
                  <div>
                    <Label>Email Marketing</Label>
                    <p className="text-sm text-muted-foreground">
                      Terima info produk dan penawaran
                    </p>
                  </div>
                </div>
                <Switch
                  checked={settings.notifications.marketing_emails}
                  onCheckedChange={(checked) => handleNotificationToggle('marketing_emails', checked)}
                />
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        {/* Privacy Tab */}
        <TabsContent value="privacy" className="space-y-4">
          <Card>
            <CardHeader>
              <CardTitle>Visibilitas Profil</CardTitle>
              <CardDescription>Kontrol siapa yang dapat melihat informasi Anda</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-3">
                  <Users className="h-5 w-5 text-muted-foreground" />
                  <div>
                    <Label>Tampilkan Profil ke Rekan Kerja</Label>
                    <p className="text-sm text-muted-foreground">
                      Izinkan rekan kerja melihat profil Anda
                    </p>
                  </div>
                </div>
                <Switch
                  checked={settings.privacy.show_profile_to_colleagues}
                  onCheckedChange={(checked) =>
                    handlePrivacyToggle('show_profile_to_colleagues', checked)
                  }
                />
              </div>
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-3">
                  {settings.privacy.show_attendance_to_manager ? (
                    <Eye className="h-5 w-5 text-muted-foreground" />
                  ) : (
                    <EyeOff className="h-5 w-5 text-muted-foreground" />
                  )}
                  <div>
                    <Label>Tampilkan Kehadiran ke Atasan</Label>
                    <p className="text-sm text-muted-foreground">
                      Izinkan atasan melihat detail kehadiran
                    </p>
                  </div>
                </div>
                <Switch
                  checked={settings.privacy.show_attendance_to_manager}
                  onCheckedChange={(checked) =>
                    handlePrivacyToggle('show_attendance_to_manager', checked)
                  }
                />
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Data & Penggunaan</CardTitle>
              <CardDescription>Kelola bagaimana data Anda digunakan</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-3">
                  <MapPin className="h-5 w-5 text-muted-foreground" />
                  <div>
                    <Label>Pelacakan Lokasi</Label>
                    <p className="text-sm text-muted-foreground">
                      Izinkan verifikasi lokasi saat absensi
                    </p>
                  </div>
                </div>
                <Switch
                  checked={settings.privacy.allow_location_tracking}
                  onCheckedChange={(checked) =>
                    handlePrivacyToggle('allow_location_tracking', checked)
                  }
                />
              </div>
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-3">
                  <Shield className="h-5 w-5 text-muted-foreground" />
                  <div>
                    <Label>Bagikan Data Wajah</Label>
                    <p className="text-sm text-muted-foreground">
                      Izinkan penggunaan data wajah untuk peningkatan sistem
                    </p>
                  </div>
                </div>
                <Switch
                  checked={settings.privacy.share_face_data}
                  onCheckedChange={(checked) => handlePrivacyToggle('share_face_data', checked)}
                />
              </div>
            </CardContent>
          </Card>

          <Alert>
            <AlertTriangle className="h-4 w-4" />
            <AlertDescription>
              Menonaktifkan pelacakan lokasi atau data wajah dapat mempengaruhi fitur absensi dengan
              face recognition dan GPS verification.
            </AlertDescription>
          </Alert>
        </TabsContent>
      </Tabs>

      {/* Reset Settings Dialog */}
      <AlertDialog open={resetDialog} onOpenChange={setResetDialog}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Reset Pengaturan</AlertDialogTitle>
            <AlertDialogDescription>
              Apakah Anda yakin ingin mereset semua pengaturan ke nilai default? Tindakan ini tidak
              dapat dibatalkan.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Batal</AlertDialogCancel>
            <AlertDialogAction onClick={handleResetSettings} disabled={resetSettings.isPending}>
              {resetSettings.isPending && <Loader2 className="h-4 w-4 mr-2 animate-spin" />}
              Reset Pengaturan
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  );
}
