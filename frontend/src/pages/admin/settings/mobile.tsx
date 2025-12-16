import { useState } from 'react';
import {
    Sun,
    Moon,
    Monitor,
    Globe,
    Bell,
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
    Settings,
} from 'lucide-react';
import { MobilePageHeader } from '@/components/mobile';
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
import { cn } from '@/lib/utils';

export function MobileSettingsPage() {
    const { data: settings, isLoading } = useSettings();
    const updateAppearance = useUpdateAppearance();
    const updateNotifications = useUpdateNotifications();
    const updatePrivacy = useUpdatePrivacy();
    const resetSettings = useResetSettings();
    const exportSettings = useExportSettings();

    const [activeTab, setActiveTab] = useState("appearance");
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
            <div className="min-h-screen flex items-center justify-center bg-background">
                <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
            </div>
        );
    }

    return (
        <div className="min-h-screen bg-background pb-24">
            <MobilePageHeader
                title="Pengaturan"
                backTo="/admin/dashboard"
                gradient="gray"
                rightAction={
                    <div className="flex gap-1">
                        <button
                            onClick={handleExportSettings}
                            disabled={exportSettings.isPending}
                            className="p-2 hover:bg-white/10 rounded-full transition-colors active:scale-95"
                            title="Ekspor Pengaturan"
                            aria-label="Ekspor Pengaturan"
                        >
                            {exportSettings.isPending ? (
                                <Loader2 className="h-5 w-5 text-white animate-spin" />
                            ) : (
                                <Download className="h-5 w-5 text-white" />
                            )}
                        </button>
                        <button
                            onClick={() => setResetDialog(true)}
                            className="p-2 hover:bg-white/10 rounded-full transition-colors active:scale-95"
                            title="Reset Pengaturan"
                            aria-label="Reset Pengaturan"
                        >
                            <RotateCcw className="h-5 w-5 text-white" />
                        </button>
                    </div>
                }
            />

            {/* Success Message */}
            {successMessage && (
                <div className="px-4 mb-4">
                    <Alert className="bg-emerald-500/10 border-emerald-500/20 backdrop-blur-sm">
                        <Check className="h-4 w-4 text-emerald-500" />
                        <AlertDescription className="text-emerald-500 font-medium">{successMessage}</AlertDescription>
                    </Alert>
                </div>
            )}

            {/* Settings Tabs */}
            <Tabs value={activeTab} onValueChange={setActiveTab} className="w-full">
                <div className="px-4 mb-4 overflow-x-auto no-scrollbar">
                    <TabsList className="w-auto inline-flex h-10 items-center justify-center rounded-full bg-muted p-1 text-muted-foreground">
                        <TabsTrigger value="appearance" className="rounded-full px-4">
                            <Palette className="h-4 w-4 mr-2" />
                            Tampilan
                        </TabsTrigger>
                        <TabsTrigger value="notifications" className="rounded-full px-4">
                            <Bell className="h-4 w-4 mr-2" />
                            Notifikasi
                        </TabsTrigger>
                        <TabsTrigger value="privacy" className="rounded-full px-4">
                            <Shield className="h-4 w-4 mr-2" />
                            Privasi
                        </TabsTrigger>
                    </TabsList>
                </div>

                {/* Appearance Tab */}
                <TabsContent value="appearance" className="px-4 space-y-4 mt-0">
                    <div className="bg-card rounded-2xl shadow-sm dark:border dark:border-border/50 p-4">
                        <h3 className="text-sm font-bold text-foreground mb-4">Tema Aplikasi</h3>
                        <div className="grid grid-cols-3 gap-3">
                            {(['light', 'dark', 'system'] as Theme[]).map((theme) => (
                                <button
                                    key={theme}
                                    onClick={() => handleThemeChange(theme)}
                                    className={cn(
                                        "flex flex-col items-center gap-2 p-3 rounded-2xl border transition-all active:scale-95",
                                        settings.appearance.theme === theme
                                            ? "border-primary bg-primary/5 shadow-sm"
                                            : "border-border/50 hover:bg-muted/50"
                                    )}
                                >
                                    <div
                                        className={cn(
                                            "p-2.5 rounded-xl",
                                            settings.appearance.theme === theme
                                                ? "bg-primary text-primary-foreground"
                                                : "bg-muted text-muted-foreground"
                                        )}
                                    >
                                        {getThemeIcon(theme)}
                                    </div>
                                    <span className="text-xs font-medium">{themeLabels[theme]}</span>
                                </button>
                            ))}
                        </div>
                    </div>

                    <div className="bg-white dark:bg-gray-900 rounded-3xl p-5 shadow-sm border border-border/50 space-y-5">
                        <h3 className="text-sm font-bold text-foreground">Regional</h3>

                        <div className="space-y-4">
                            <div className="flex items-center justify-between">
                                <div className="flex items-center gap-3">
                                    <div className="h-8 w-8 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 dark:text-blue-400">
                                        <Globe className="h-4 w-4" />
                                    </div>
                                    <Label className="text-sm font-medium">Bahasa</Label>
                                </div>
                                <Select
                                    value={settings.appearance.language}
                                    onValueChange={(value) => handleLanguageChange(value as Language)}
                                >
                                    <SelectTrigger className="w-[140px] h-8 text-xs">
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
                                    <div className="h-8 w-8 rounded-full bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center text-orange-600 dark:text-orange-400">
                                        <Calendar className="h-4 w-4" />
                                    </div>
                                    <Label className="text-sm font-medium">Format Tanggal</Label>
                                </div>
                                <Select
                                    value={settings.appearance.date_format}
                                    onValueChange={(value) => handleDateFormatChange(value as DateFormat)}
                                >
                                    <SelectTrigger className="w-[140px] h-8 text-xs">
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
                                    <div className="h-8 w-8 rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center text-purple-600 dark:text-purple-400">
                                        <Clock className="h-4 w-4" />
                                    </div>
                                    <Label className="text-sm font-medium">Format Waktu</Label>
                                </div>
                                <Select
                                    value={settings.appearance.time_format}
                                    onValueChange={(value) => handleTimeFormatChange(value as TimeFormat)}
                                >
                                    <SelectTrigger className="w-[140px] h-8 text-xs">
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
                        </div>
                    </div>

                    <div className="bg-white dark:bg-gray-900 rounded-3xl p-5 shadow-sm border border-border/50 space-y-5">
                        <h3 className="text-sm font-bold text-foreground">Lainnya</h3>
                        <div className="space-y-4">
                            <div className="flex items-center justify-between">
                                <div className="flex items-center gap-3">
                                    <div className="h-8 w-8 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-gray-600 dark:text-gray-400">
                                        <Monitor className="h-4 w-4" />
                                    </div>
                                    <div>
                                        <Label className="text-sm font-medium">Mode Kompak</Label>
                                        <p className="text-[10px] text-muted-foreground">Tampilan lebih padat</p>
                                    </div>
                                </div>
                                <Switch
                                    checked={settings.appearance.compact_mode}
                                    onCheckedChange={(checked) => handleAppearanceToggle('compact_mode', checked)}
                                />
                            </div>
                            <div className="flex items-center justify-between">
                                <div className="flex items-center gap-3">
                                    <div className="h-8 w-8 rounded-full bg-pink-100 dark:bg-pink-900/30 flex items-center justify-center text-pink-600 dark:text-pink-400">
                                        <Settings className="h-4 w-4" />
                                    </div>
                                    <div>
                                        <Label className="text-sm font-medium">Animasi</Label>
                                        <p className="text-[10px] text-muted-foreground">Efek transisi halus</p>
                                    </div>
                                </div>
                                <Switch
                                    checked={settings.appearance.animations_enabled}
                                    onCheckedChange={(checked) => handleAppearanceToggle('animations_enabled', checked)}
                                />
                            </div>
                        </div>
                    </div>
                </TabsContent>

                {/* Notifications Tab */}
                <TabsContent value="notifications" className="px-4 space-y-4 mt-0">
                    <div className="bg-white dark:bg-gray-900 rounded-3xl p-5 shadow-sm border border-border/50 space-y-5">
                        <h3 className="text-sm font-bold text-foreground">Saluran Notifikasi</h3>
                        <div className="space-y-4">
                            <div className="flex items-center justify-between">
                                <div className="flex items-center gap-3">
                                    <div className="h-8 w-8 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 dark:text-blue-400">
                                        <Mail className="h-4 w-4" />
                                    </div>
                                    <div>
                                        <Label className="text-sm font-medium">Email</Label>
                                        <p className="text-[10px] text-muted-foreground">Terima via email</p>
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
                                    <div className="h-8 w-8 rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center text-purple-600 dark:text-purple-400">
                                        <Smartphone className="h-4 w-4" />
                                    </div>
                                    <div>
                                        <Label className="text-sm font-medium">Push Notification</Label>
                                        <p className="text-[10px] text-muted-foreground">Notifikasi di HP/Browser</p>
                                    </div>
                                </div>
                                <Switch
                                    checked={settings.notifications.push_notifications}
                                    onCheckedChange={(checked) =>
                                        handleNotificationToggle('push_notifications', checked)
                                    }
                                />
                            </div>
                        </div>
                    </div>

                    <div className="bg-white dark:bg-gray-900 rounded-3xl p-5 shadow-sm border border-border/50 space-y-5">
                        <h3 className="text-sm font-bold text-foreground">Jenis Aktivitas</h3>
                        <div className="space-y-4">
                            <div className="flex items-center justify-between">
                                <div className="flex items-center gap-3">
                                    <Clock className="h-4 w-4 text-muted-foreground" />
                                    <Label className="text-sm font-medium">Pengingat Absensi</Label>
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
                                    <Calendar className="h-4 w-4 text-muted-foreground" />
                                    <Label className="text-sm font-medium">Update Cuti</Label>
                                </div>
                                <Switch
                                    checked={settings.notifications.leave_updates}
                                    onCheckedChange={(checked) => handleNotificationToggle('leave_updates', checked)}
                                />
                            </div>
                            <div className="flex items-center justify-between">
                                <div className="flex items-center gap-3">
                                    <Bell className="h-4 w-4 text-muted-foreground" />
                                    <Label className="text-sm font-medium">Info Gaji</Label>
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
                                    <Calendar className="h-4 w-4 text-muted-foreground" />
                                    <Label className="text-sm font-medium">Jadwal Berubah</Label>
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
                                    <Shield className="h-4 w-4 text-muted-foreground" />
                                    <Label className="text-sm font-medium">Keamanan</Label>
                                </div>
                                <Switch
                                    checked={settings.notifications.security_alerts}
                                    onCheckedChange={(checked) => handleNotificationToggle('security_alerts', checked)}
                                />
                            </div>
                        </div>
                    </div>
                </TabsContent>

                {/* Privacy Tab */}
                <TabsContent value="privacy" className="px-4 space-y-4 mt-0">
                    <div className="bg-white dark:bg-gray-900 rounded-3xl p-5 shadow-sm border border-border/50 space-y-5">
                        <h3 className="text-sm font-bold text-foreground">Visibilitas</h3>
                        <div className="space-y-4">
                            <div className="flex items-center justify-between">
                                <div className="flex items-center gap-3">
                                    <div className="h-8 w-8 rounded-full bg-teal-100 dark:bg-teal-900/30 flex items-center justify-center text-teal-600 dark:text-teal-400">
                                        <Users className="h-4 w-4" />
                                    </div>
                                    <div>
                                        <Label className="text-sm font-medium">Profil Publik</Label>
                                        <p className="text-[10px] text-muted-foreground">Dilihat rekan kerja</p>
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
                                    <div className="h-8 w-8 rounded-full bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                                        {settings.privacy.show_attendance_to_manager ? (
                                            <Eye className="h-4 w-4" />
                                        ) : (
                                            <EyeOff className="h-4 w-4" />
                                        )}
                                    </div>
                                    <div>
                                        <Label className="text-sm font-medium">Kehadiran</Label>
                                        <p className="text-[10px] text-muted-foreground">Dilihat atasan</p>
                                    </div>
                                </div>
                                <Switch
                                    checked={settings.privacy.show_attendance_to_manager}
                                    onCheckedChange={(checked) =>
                                        handlePrivacyToggle('show_attendance_to_manager', checked)
                                    }
                                />
                            </div>
                        </div>
                    </div>

                    <div className="bg-white dark:bg-gray-900 rounded-3xl p-5 shadow-sm border border-border/50 space-y-5">
                        <h3 className="text-sm font-bold text-foreground">Data & Izin</h3>
                        <div className="space-y-4">
                            <div className="flex items-center justify-between">
                                <div className="flex items-center gap-3">
                                    <div className="h-8 w-8 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center text-red-600 dark:text-red-400">
                                        <MapPin className="h-4 w-4" />
                                    </div>
                                    <div>
                                        <Label className="text-sm font-medium">Lokasi</Label>
                                        <p className="text-[10px] text-muted-foreground">Verifikasi GPS</p>
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
                                    <div className="h-8 w-8 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 dark:text-blue-400">
                                        <Shield className="h-4 w-4" />
                                    </div>
                                    <div>
                                        <Label className="text-sm font-medium">Data Wajah</Label>
                                        <p className="text-[10px] text-muted-foreground">Untuk Face Recognition</p>
                                    </div>
                                </div>
                                <Switch
                                    checked={settings.privacy.share_face_data}
                                    onCheckedChange={(checked) => handlePrivacyToggle('share_face_data', checked)}
                                />
                            </div>
                        </div>
                    </div>

                    <Alert className="bg-amber-500/10 border-amber-500/20">
                        <AlertTriangle className="h-4 w-4 text-amber-500" />
                        <AlertDescription className="text-amber-600 dark:text-amber-400 text-xs">
                            Menonaktifkan lokasi atau data wajah dapat membatasi fitur absensi.
                        </AlertDescription>
                    </Alert>
                </TabsContent>
            </Tabs>

            {/* Reset Settings Dialog */}
            <AlertDialog open={resetDialog} onOpenChange={setResetDialog}>
                <AlertDialogContent className="w-[90%] rounded-2xl">
                    <AlertDialogHeader>
                        <AlertDialogTitle>Reset Pengaturan</AlertDialogTitle>
                        <AlertDialogDescription>
                            Kembalikan semua pengaturan ke awal?
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter className="flex-row gap-2 justify-end">
                        <AlertDialogCancel className="mt-0">Batal</AlertDialogCancel>
                        <AlertDialogAction onClick={handleResetSettings} disabled={resetSettings.isPending}>
                            {resetSettings.isPending && <Loader2 className="h-4 w-4 mr-2 animate-spin" />}
                            Reset
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </div>
    );
}
