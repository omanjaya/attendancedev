import { useState, useEffect } from 'react';
import {
  Shield,
  ShieldCheck,
  ShieldOff,
  Smartphone,
  Monitor,
  Tablet,
  Lock,
  Unlock,
  Loader2,
  Copy,
  Check,
  AlertTriangle,
  LogOut,
  Trash2,
  FileText,
  Clock,
  User,
  Globe,
  Activity,
  Download,
  Filter,
  Users,
  AlertCircle,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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
  DialogFooter,
} from '@/components/ui/dialog';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
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
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { useSecurity } from '@/hooks/use-security';
import {
  auditActionLabels,
  auditActionColors,
  type SecurityOverview,
  type TwoFactorSetup,
  type AuditAction,
} from '@/types/security';

// Device icon component
function DeviceIcon({ type }: { type: 'desktop' | 'mobile' | 'tablet' }) {
  const icons = {
    desktop: Monitor,
    mobile: Smartphone,
    tablet: Tablet,
  };
  const Icon = icons[type];
  return <Icon className="h-5 w-5" />;
}

// Action badge component
function ActionBadge({ action }: { action: AuditAction }) {
  return (
    <Badge
      variant="outline"
      style={{
        borderColor: auditActionColors[action],
        color: auditActionColors[action],
        backgroundColor: `${auditActionColors[action]}10`,
      }}
    >
      {auditActionLabels[action]}
    </Badge>
  );
}

// 2FA Setup Dialog
function TwoFactorSetupDialog({
  open,
  onOpenChange,
  setup,
  onVerify,
  isLoading,
}: {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  setup: TwoFactorSetup | null;
  onVerify: (code: string) => Promise<void>;
  isLoading: boolean;
}) {
  const [code, setCode] = useState('');
  const [step, setStep] = useState<'setup' | 'verify' | 'recovery'>('setup');
  const [copiedIndex, setCopiedIndex] = useState<number | null>(null);

  const handleCopyCode = async (recoveryCode: string, index: number) => {
    await navigator.clipboard.writeText(recoveryCode);
    setCopiedIndex(index);
    setTimeout(() => setCopiedIndex(null), 2000);
  };

  const handleVerify = async () => {
    await onVerify(code);
    setStep('recovery');
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-[500px]">
        <DialogHeader>
          <DialogTitle>
            {step === 'setup' && 'Aktifkan Two-Factor Authentication'}
            {step === 'verify' && 'Verifikasi Kode'}
            {step === 'recovery' && 'Simpan Recovery Codes'}
          </DialogTitle>
          <DialogDescription>
            {step === 'setup' && 'Scan QR code dengan aplikasi authenticator Anda'}
            {step === 'verify' && 'Masukkan kode 6 digit dari aplikasi authenticator'}
            {step === 'recovery' && 'Simpan kode recovery ini di tempat yang aman'}
          </DialogDescription>
        </DialogHeader>

        {step === 'setup' && setup && (
          <div className="space-y-4 py-4">
            <div className="flex justify-center">
              <div className="rounded-lg border bg-white p-4">
                {/* QR Code placeholder - in real app, generate actual QR */}
                <div className="flex h-48 w-48 items-center justify-center bg-gray-100 text-center text-sm text-gray-500">
                  <div>
                    <Shield className="mx-auto h-12 w-12 text-gray-400" />
                    <p className="mt-2">QR Code</p>
                    <p className="text-xs">Scan dengan Authenticator App</p>
                  </div>
                </div>
              </div>
            </div>
            <div className="space-y-2">
              <Label>Atau masukkan kode manual:</Label>
              <div className="flex items-center gap-2">
                <code className="flex-1 rounded bg-muted p-2 text-sm font-mono">
                  {setup.secret}
                </code>
                <Button
                  variant="outline"
                  size="icon"
                  onClick={() => navigator.clipboard.writeText(setup.secret)}
                >
                  <Copy className="h-4 w-4" />
                </Button>
              </div>
            </div>
            <DialogFooter>
              <Button onClick={() => setStep('verify')}>Lanjutkan</Button>
            </DialogFooter>
          </div>
        )}

        {step === 'verify' && (
          <div className="space-y-4 py-4">
            <div className="grid gap-2">
              <Label htmlFor="code">Kode Verifikasi</Label>
              <Input
                id="code"
                value={code}
                onChange={(e) => setCode(e.target.value.replace(/\D/g, '').slice(0, 6))}
                placeholder="000000"
                className="text-center text-2xl tracking-widest"
                maxLength={6}
              />
            </div>
            <DialogFooter>
              <Button variant="outline" onClick={() => setStep('setup')}>
                Kembali
              </Button>
              <Button onClick={handleVerify} disabled={code.length !== 6 || isLoading}>
                {isLoading && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                Verifikasi
              </Button>
            </DialogFooter>
          </div>
        )}

        {step === 'recovery' && setup && (
          <div className="space-y-4 py-4">
            <Alert variant="destructive">
              <AlertTriangle className="h-4 w-4" />
              <AlertTitle>Penting!</AlertTitle>
              <AlertDescription>
                Simpan kode-kode ini di tempat yang aman. Anda akan membutuhkannya jika
                kehilangan akses ke aplikasi authenticator.
              </AlertDescription>
            </Alert>
            <div className="grid grid-cols-2 gap-2">
              {setup.recovery_codes.map((recoveryCode, index) => (
                <div
                  key={index}
                  className="flex items-center justify-between rounded border bg-muted p-2"
                >
                  <code className="text-sm font-mono">{recoveryCode}</code>
                  <Button
                    variant="ghost"
                    size="icon"
                    className="h-6 w-6"
                    onClick={() => handleCopyCode(recoveryCode, index)}
                  >
                    {copiedIndex === index ? (
                      <Check className="h-3 w-3 text-green-500" />
                    ) : (
                      <Copy className="h-3 w-3" />
                    )}
                  </Button>
                </div>
              ))}
            </div>
            <DialogFooter>
              <Button onClick={() => onOpenChange(false)}>Selesai</Button>
            </DialogFooter>
          </div>
        )}
      </DialogContent>
    </Dialog>
  );
}

export default function SecurityPage() {
  const {
    isLoading,
    devices,
    sessions,
    auditLogs,
    alerts,
    twoFactorEnabled,
    getOverview,
    initiate2FA,
    enable2FA,
    disable2FA,
    fetchDevices,
    removeDevice,
    toggleDeviceTrust,
    fetchSessions,
    terminateSession,
    terminateAllSessions,
    fetchAuditLogs,
    fetchAlerts,
    dismissAlert,
  } = useSecurity();

  const [overview, setOverview] = useState<SecurityOverview | null>(null);
  const [is2FASetupOpen, setIs2FASetupOpen] = useState(false);
  const [twoFactorSetup, setTwoFactorSetup] = useState<TwoFactorSetup | null>(null);
  const [disabling2FA, setDisabling2FA] = useState(false);
  const [disablePassword, setDisablePassword] = useState('');
  const [removingDevice, setRemovingDevice] = useState<string | null>(null);
  const [terminatingSession, setTerminatingSession] = useState<string | null>(null);
  const [actionFilter, setActionFilter] = useState<AuditAction | 'all'>('all');

  useEffect(() => {
    loadData();
  }, []);

  const loadData = async () => {
    const [overviewData] = await Promise.all([
      getOverview(),
      fetchDevices(),
      fetchSessions(),
      fetchAuditLogs({ limit: 20 }),
      fetchAlerts(),
    ]);
    setOverview(overviewData);
  };

  const handleStart2FA = async () => {
    const setup = await initiate2FA();
    setTwoFactorSetup(setup);
    setIs2FASetupOpen(true);
  };

  const handleVerify2FA = async (code: string) => {
    await enable2FA(code);
    loadData();
  };

  const handleDisable2FA = async () => {
    await disable2FA(disablePassword);
    setDisabling2FA(false);
    setDisablePassword('');
    loadData();
  };

  const handleRemoveDevice = async () => {
    if (removingDevice) {
      await removeDevice(removingDevice);
      setRemovingDevice(null);
    }
  };

  const handleTerminateSession = async () => {
    if (terminatingSession) {
      await terminateSession(terminatingSession);
      setTerminatingSession(null);
    }
  };

  const handleFilterLogs = (action: AuditAction | 'all') => {
    setActionFilter(action);
    fetchAuditLogs({
      action: action !== 'all' ? action : undefined,
      limit: 50,
    });
  };

  const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('id-ID', {
      day: '2-digit',
      month: 'short',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    });
  };

  const formatRelativeTime = (date: string) => {
    const now = new Date();
    const then = new Date(date);
    const diffMs = now.getTime() - then.getTime();
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffMins < 1) return 'Baru saja';
    if (diffMins < 60) return `${diffMins} menit lalu`;
    if (diffHours < 24) return `${diffHours} jam lalu`;
    return `${diffDays} hari lalu`;
  };

  return (
    <div className="space-y-6 p-6">
      {/* Header */}
      <div className="relative overflow-hidden rounded-xl bg-gradient-to-br from-emerald-600 via-teal-600 to-cyan-600 p-6 text-white shadow-lg">
        <div className="absolute inset-0 bg-grid-white/10" />
        <div className="relative">
          <div className="flex items-center justify-between">
            <div>
              <h1 className="text-2xl font-bold">Keamanan</h1>
              <p className="mt-1 text-emerald-100">
                Kelola keamanan akun dan perangkat Anda
              </p>
            </div>
            <div className="flex items-center gap-2">
              {twoFactorEnabled ? (
                <Badge className="bg-green-500 text-white">
                  <ShieldCheck className="mr-1 h-3 w-3" />
                  2FA Aktif
                </Badge>
              ) : (
                <Badge variant="secondary" className="bg-yellow-500 text-white">
                  <ShieldOff className="mr-1 h-3 w-3" />
                  2FA Nonaktif
                </Badge>
              )}
            </div>
          </div>

          {/* Stats */}
          {overview && (
            <div className="mt-6 grid grid-cols-2 gap-4 md:grid-cols-4">
              <div className="rounded-lg bg-white/10 p-4 backdrop-blur-sm">
                <div className="flex items-center gap-3">
                  <div className="rounded-lg bg-white/20 p-2">
                    <Users className="h-5 w-5" />
                  </div>
                  <div>
                    <p className="text-sm text-emerald-100">2FA Users</p>
                    <p className="text-2xl font-bold">{overview.users_with_2fa}</p>
                  </div>
                </div>
              </div>
              <div className="rounded-lg bg-white/10 p-4 backdrop-blur-sm">
                <div className="flex items-center gap-3">
                  <div className="rounded-lg bg-green-500/20 p-2">
                    <Activity className="h-5 w-5" />
                  </div>
                  <div>
                    <p className="text-sm text-emerald-100">Active Sessions</p>
                    <p className="text-2xl font-bold">{overview.active_sessions}</p>
                  </div>
                </div>
              </div>
              <div className="rounded-lg bg-white/10 p-4 backdrop-blur-sm">
                <div className="flex items-center gap-3">
                  <div className="rounded-lg bg-red-500/20 p-2">
                    <Lock className="h-5 w-5" />
                  </div>
                  <div>
                    <p className="text-sm text-emerald-100">Locked</p>
                    <p className="text-2xl font-bold">{overview.locked_accounts}</p>
                  </div>
                </div>
              </div>
              <div className="rounded-lg bg-white/10 p-4 backdrop-blur-sm">
                <div className="flex items-center gap-3">
                  <div className="rounded-lg bg-yellow-500/20 p-2">
                    <AlertTriangle className="h-5 w-5" />
                  </div>
                  <div>
                    <p className="text-sm text-emerald-100">Failed Today</p>
                    <p className="text-2xl font-bold">{overview.failed_logins_today}</p>
                  </div>
                </div>
              </div>
            </div>
          )}
        </div>
      </div>

      {/* Security Alerts */}
      {alerts.filter((a) => !a.resolved_at).length > 0 && (
        <div className="space-y-2">
          {alerts
            .filter((a) => !a.resolved_at)
            .map((alert) => (
              <Alert
                key={alert.id}
                variant={alert.type === 'critical' ? 'destructive' : 'default'}
              >
                <AlertCircle className="h-4 w-4" />
                <AlertTitle>{alert.title}</AlertTitle>
                <AlertDescription className="flex items-center justify-between">
                  <span>{alert.message}</span>
                  <Button
                    variant="ghost"
                    size="sm"
                    onClick={() => dismissAlert(alert.id)}
                  >
                    Dismiss
                  </Button>
                </AlertDescription>
              </Alert>
            ))}
        </div>
      )}

      {/* Tabs */}
      <Tabs defaultValue="2fa">
        <TabsList>
          <TabsTrigger value="2fa">Two-Factor Auth</TabsTrigger>
          <TabsTrigger value="devices">Perangkat</TabsTrigger>
          <TabsTrigger value="sessions">Sesi Aktif</TabsTrigger>
          <TabsTrigger value="audit">Audit Log</TabsTrigger>
        </TabsList>

        {/* 2FA Tab */}
        <TabsContent value="2fa" className="space-y-4">
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <Shield className="h-5 w-5" />
                Two-Factor Authentication
              </CardTitle>
              <CardDescription>
                Tambahkan lapisan keamanan ekstra dengan verifikasi 2 langkah
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="flex items-center justify-between rounded-lg border p-4">
                <div className="flex items-center gap-4">
                  <div className={`rounded-full p-3 ${twoFactorEnabled ? 'bg-green-100' : 'bg-gray-100'}`}>
                    {twoFactorEnabled ? (
                      <ShieldCheck className="h-6 w-6 text-green-600" />
                    ) : (
                      <ShieldOff className="h-6 w-6 text-gray-400" />
                    )}
                  </div>
                  <div>
                    <h3 className="font-medium">
                      {twoFactorEnabled ? '2FA Aktif' : '2FA Tidak Aktif'}
                    </h3>
                    <p className="text-sm text-muted-foreground">
                      {twoFactorEnabled
                        ? 'Akun Anda dilindungi dengan verifikasi 2 langkah'
                        : 'Aktifkan 2FA untuk keamanan ekstra'}
                    </p>
                  </div>
                </div>
                {twoFactorEnabled ? (
                  <Button variant="destructive" onClick={() => setDisabling2FA(true)}>
                    <ShieldOff className="mr-2 h-4 w-4" />
                    Nonaktifkan
                  </Button>
                ) : (
                  <Button onClick={handleStart2FA}>
                    <ShieldCheck className="mr-2 h-4 w-4" />
                    Aktifkan 2FA
                  </Button>
                )}
              </div>

              <div className="rounded-lg border p-4">
                <h4 className="font-medium">Cara Kerja 2FA</h4>
                <ol className="mt-2 list-inside list-decimal space-y-1 text-sm text-muted-foreground">
                  <li>Download aplikasi authenticator (Google Authenticator, Authy, dll)</li>
                  <li>Scan QR code yang ditampilkan saat setup</li>
                  <li>Masukkan kode 6 digit dari aplikasi saat login</li>
                  <li>Simpan recovery codes sebagai backup</li>
                </ol>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        {/* Devices Tab */}
        <TabsContent value="devices" className="space-y-4">
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <Monitor className="h-5 w-5" />
                Perangkat Terdaftar ({devices.length})
              </CardTitle>
              <CardDescription>
                Perangkat yang pernah digunakan untuk login
              </CardDescription>
            </CardHeader>
            <CardContent>
              {isLoading ? (
                <div className="flex items-center justify-center py-8">
                  <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
                </div>
              ) : (
                <div className="space-y-3">
                  {devices.map((device) => (
                    <div
                      key={device.id}
                      className={`flex items-center justify-between rounded-lg border p-4 ${
                        device.is_current ? 'border-green-500 bg-green-50' : ''
                      }`}
                    >
                      <div className="flex items-center gap-4">
                        <div className="rounded-lg bg-muted p-2">
                          <DeviceIcon type={device.type} />
                        </div>
                        <div>
                          <div className="flex items-center gap-2">
                            <span className="font-medium">{device.name}</span>
                            {device.is_current && (
                              <Badge variant="secondary" className="text-xs">
                                Perangkat ini
                              </Badge>
                            )}
                            {device.is_trusted && (
                              <Badge variant="outline" className="border-green-500 text-green-500 text-xs">
                                Trusted
                              </Badge>
                            )}
                          </div>
                          <p className="text-sm text-muted-foreground">
                            {device.browser} • {device.os}
                          </p>
                          <p className="text-xs text-muted-foreground">
                            {device.location} • {formatRelativeTime(device.last_used_at)}
                          </p>
                        </div>
                      </div>
                      {!device.is_current && (
                        <div className="flex items-center gap-2">
                          <Button
                            variant="outline"
                            size="sm"
                            onClick={() => toggleDeviceTrust(device.id)}
                          >
                            {device.is_trusted ? (
                              <>
                                <Unlock className="mr-1 h-3 w-3" />
                                Untrust
                              </>
                            ) : (
                              <>
                                <Lock className="mr-1 h-3 w-3" />
                                Trust
                              </>
                            )}
                          </Button>
                          <Button
                            variant="destructive"
                            size="sm"
                            onClick={() => setRemovingDevice(device.id)}
                          >
                            <Trash2 className="h-4 w-4" />
                          </Button>
                        </div>
                      )}
                    </div>
                  ))}
                </div>
              )}
            </CardContent>
          </Card>
        </TabsContent>

        {/* Sessions Tab */}
        <TabsContent value="sessions" className="space-y-4">
          <Card>
            <CardHeader className="flex flex-row items-center justify-between">
              <div>
                <CardTitle className="flex items-center gap-2">
                  <Activity className="h-5 w-5" />
                  Sesi Aktif ({sessions.length})
                </CardTitle>
                <CardDescription>
                  Sesi yang sedang aktif di berbagai perangkat
                </CardDescription>
              </div>
              {sessions.filter((s) => !s.is_current).length > 0 && (
                <Button variant="destructive" size="sm" onClick={terminateAllSessions}>
                  <LogOut className="mr-2 h-4 w-4" />
                  Logout Semua
                </Button>
              )}
            </CardHeader>
            <CardContent>
              {isLoading ? (
                <div className="flex items-center justify-center py-8">
                  <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
                </div>
              ) : (
                <div className="space-y-3">
                  {sessions.map((session) => (
                    <div
                      key={session.id}
                      className={`flex items-center justify-between rounded-lg border p-4 ${
                        session.is_current ? 'border-green-500 bg-green-50' : ''
                      }`}
                    >
                      <div className="flex items-center gap-4">
                        <div className="rounded-lg bg-muted p-2">
                          <Monitor className="h-5 w-5" />
                        </div>
                        <div>
                          <div className="flex items-center gap-2">
                            <span className="font-medium">{session.device_name}</span>
                            {session.is_current && (
                              <Badge variant="secondary" className="text-xs">
                                Sesi ini
                              </Badge>
                            )}
                          </div>
                          <div className="flex items-center gap-2 text-sm text-muted-foreground">
                            <Globe className="h-3 w-3" />
                            {session.ip_address}
                            {session.location && ` • ${session.location}`}
                          </div>
                          <div className="flex items-center gap-2 text-xs text-muted-foreground">
                            <Clock className="h-3 w-3" />
                            Aktif {formatRelativeTime(session.last_activity)}
                          </div>
                        </div>
                      </div>
                      {!session.is_current && (
                        <Button
                          variant="destructive"
                          size="sm"
                          onClick={() => setTerminatingSession(session.id)}
                        >
                          <LogOut className="mr-1 h-3 w-3" />
                          Logout
                        </Button>
                      )}
                    </div>
                  ))}
                </div>
              )}
            </CardContent>
          </Card>
        </TabsContent>

        {/* Audit Log Tab */}
        <TabsContent value="audit" className="space-y-4">
          <Card>
            <CardHeader>
              <div className="flex items-center justify-between">
                <div>
                  <CardTitle className="flex items-center gap-2">
                    <FileText className="h-5 w-5" />
                    Audit Log
                  </CardTitle>
                  <CardDescription>
                    Riwayat aktivitas dan perubahan sistem
                  </CardDescription>
                </div>
                <div className="flex items-center gap-2">
                  <Select
                    value={actionFilter}
                    onValueChange={(value) => handleFilterLogs(value as AuditAction | 'all')}
                  >
                    <SelectTrigger className="w-[150px]">
                      <Filter className="mr-2 h-4 w-4" />
                      <SelectValue placeholder="Filter" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="all">Semua</SelectItem>
                      {Object.entries(auditActionLabels).map(([value, label]) => (
                        <SelectItem key={value} value={value}>
                          {label}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                  <Button variant="outline" size="icon">
                    <Download className="h-4 w-4" />
                  </Button>
                </div>
              </div>
            </CardHeader>
            <CardContent>
              {isLoading ? (
                <div className="flex items-center justify-center py-8">
                  <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
                </div>
              ) : (
                <div className="overflow-x-auto">
                  <Table>
                    <TableHeader>
                      <TableRow>
                        <TableHead>Waktu</TableHead>
                        <TableHead>User</TableHead>
                        <TableHead>Aksi</TableHead>
                        <TableHead>Deskripsi</TableHead>
                        <TableHead>IP</TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      {auditLogs.map((log) => (
                        <TableRow key={log.id}>
                          <TableCell className="whitespace-nowrap">
                            <div className="flex items-center gap-1 text-sm">
                              <Clock className="h-3 w-3 text-muted-foreground" />
                              {formatDate(log.created_at)}
                            </div>
                          </TableCell>
                          <TableCell>
                            <div className="flex items-center gap-2">
                              <div className="flex h-7 w-7 items-center justify-center rounded-full bg-primary/10">
                                <User className="h-4 w-4 text-primary" />
                              </div>
                              <div>
                                <p className="text-sm font-medium">{log.user_name}</p>
                                <p className="text-xs text-muted-foreground">{log.user_email}</p>
                              </div>
                            </div>
                          </TableCell>
                          <TableCell>
                            <ActionBadge action={log.action} />
                          </TableCell>
                          <TableCell className="max-w-[300px]">
                            <p className="truncate text-sm">{log.description}</p>
                          </TableCell>
                          <TableCell>
                            <code className="text-xs">{log.ip_address}</code>
                          </TableCell>
                        </TableRow>
                      ))}
                    </TableBody>
                  </Table>
                </div>
              )}
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>

      {/* 2FA Setup Dialog */}
      <TwoFactorSetupDialog
        open={is2FASetupOpen}
        onOpenChange={setIs2FASetupOpen}
        setup={twoFactorSetup}
        onVerify={handleVerify2FA}
        isLoading={isLoading}
      />

      {/* Disable 2FA Dialog */}
      <Dialog open={disabling2FA} onOpenChange={setDisabling2FA}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Nonaktifkan 2FA?</DialogTitle>
            <DialogDescription>
              Masukkan password Anda untuk menonaktifkan two-factor authentication.
              Ini akan mengurangi keamanan akun Anda.
            </DialogDescription>
          </DialogHeader>
          <div className="py-4">
            <Label htmlFor="password">Password</Label>
            <Input
              id="password"
              type="password"
              value={disablePassword}
              onChange={(e) => setDisablePassword(e.target.value)}
              placeholder="Masukkan password"
            />
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setDisabling2FA(false)}>
              Batal
            </Button>
            <Button
              variant="destructive"
              onClick={handleDisable2FA}
              disabled={!disablePassword || isLoading}
            >
              {isLoading && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
              Nonaktifkan 2FA
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Remove Device Confirmation */}
      <AlertDialog open={!!removingDevice} onOpenChange={() => setRemovingDevice(null)}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Hapus Perangkat?</AlertDialogTitle>
            <AlertDialogDescription>
              Perangkat ini akan dihapus dari daftar perangkat terpercaya.
              Anda perlu login ulang jika menggunakan perangkat ini lagi.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Batal</AlertDialogCancel>
            <AlertDialogAction onClick={handleRemoveDevice} className="bg-red-600 hover:bg-red-700">
              Hapus
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>

      {/* Terminate Session Confirmation */}
      <AlertDialog open={!!terminatingSession} onOpenChange={() => setTerminatingSession(null)}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Logout Sesi Ini?</AlertDialogTitle>
            <AlertDialogDescription>
              Sesi ini akan diakhiri dan pengguna harus login kembali.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Batal</AlertDialogCancel>
            <AlertDialogAction onClick={handleTerminateSession} className="bg-red-600 hover:bg-red-700">
              Logout
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  );
}
