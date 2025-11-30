import { useState } from 'react';
import { Link } from '@tanstack/react-router';
import {
  ArrowLeft,
  Shield,
  Smartphone,
  Key,
  QrCode,
  CheckCircle,
  Copy,
  RefreshCw,
  AlertTriangle,
} from 'lucide-react';

import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { useNotificationStore } from '@/stores';

// Mock 2FA status
const mock2FAStatus = {
  enabled: true,
  method: 'authenticator',
  enabled_at: '2024-11-20T10:30:00',
  backup_codes_remaining: 8,
};

const backupCodes = [
  'ABCD-1234-EFGH',
  'IJKL-5678-MNOP',
  'QRST-9012-UVWX',
  'YZAB-3456-CDEF',
  'GHIJ-7890-KLMN',
  'OPQR-1234-STUV',
  'WXYZ-5678-ABCD',
  'EFGH-9012-IJKL',
];

export default function TwoFactorPage() {
  const { success } = useNotificationStore();
  const [is2FAEnabled, setIs2FAEnabled] = useState(mock2FAStatus.enabled);
  const [showSetup, setShowSetup] = useState(false);
  const [verificationCode, setVerificationCode] = useState('');
  const [showBackupCodes, setShowBackupCodes] = useState(false);

  const handleCopyCode = (code: string) => {
    navigator.clipboard.writeText(code);
    success('Disalin', 'Kode backup berhasil disalin');
  };

  const handleCopyAllCodes = () => {
    navigator.clipboard.writeText(backupCodes.join('\n'));
    success('Disalin', 'Semua kode backup berhasil disalin');
  };

  return (
    <div className="p-6 max-w-3xl mx-auto">
      {/* Header */}
      <div className="mb-6">
        <Link
          to="/security"
          className="inline-flex items-center text-sm text-muted-foreground hover:text-foreground mb-4"
        >
          <ArrowLeft className="h-4 w-4 mr-2" />
          Kembali ke keamanan
        </Link>
        <h1 className="text-2xl font-bold text-foreground">Two-Factor Authentication</h1>
        <p className="text-sm text-muted-foreground">
          Tambahkan lapisan keamanan ekstra ke akun Anda
        </p>
      </div>

      <div className="space-y-6">
        {/* Status Card */}
        <Card className={is2FAEnabled ? 'border-success' : 'border-warning'}>
          <CardContent className="p-6">
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-4">
                <div className={`p-3 rounded-full ${is2FAEnabled ? 'bg-success/10' : 'bg-warning/10'}`}>
                  <Shield className={`h-8 w-8 ${is2FAEnabled ? 'text-success' : 'text-warning'}`} />
                </div>
                <div>
                  <div className="flex items-center gap-2">
                    <h2 className="text-lg font-semibold">Two-Factor Authentication</h2>
                    <Badge variant={is2FAEnabled ? 'default' : 'secondary'}>
                      {is2FAEnabled ? 'Aktif' : 'Tidak Aktif'}
                    </Badge>
                  </div>
                  {is2FAEnabled ? (
                    <p className="text-sm text-muted-foreground">
                      Diaktifkan pada {new Date(mock2FAStatus.enabled_at).toLocaleDateString('id-ID')}
                    </p>
                  ) : (
                    <p className="text-sm text-muted-foreground">
                      Lindungi akun Anda dengan verifikasi dua langkah
                    </p>
                  )}
                </div>
              </div>
              {is2FAEnabled ? (
                <AlertDialog>
                  <AlertDialogTrigger asChild>
                    <Button variant="destructive">Nonaktifkan</Button>
                  </AlertDialogTrigger>
                  <AlertDialogContent>
                    <AlertDialogHeader>
                      <AlertDialogTitle>Nonaktifkan 2FA?</AlertDialogTitle>
                      <AlertDialogDescription>
                        Ini akan mengurangi keamanan akun Anda. Anda yakin ingin menonaktifkan Two-Factor Authentication?
                      </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                      <AlertDialogCancel>Batal</AlertDialogCancel>
                      <AlertDialogAction
                        className="bg-destructive text-destructive-foreground"
                        onClick={() => setIs2FAEnabled(false)}
                      >
                        Nonaktifkan
                      </AlertDialogAction>
                    </AlertDialogFooter>
                  </AlertDialogContent>
                </AlertDialog>
              ) : (
                <Button onClick={() => setShowSetup(true)}>
                  <Shield className="h-4 w-4 mr-2" />
                  Aktifkan
                </Button>
              )}
            </div>
          </CardContent>
        </Card>

        {/* Setup Form */}
        {showSetup && !is2FAEnabled && (
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-lg">
                <QrCode className="h-5 w-5 text-primary" />
                Setup Authenticator
              </CardTitle>
              <CardDescription>
                Scan QR code dengan aplikasi authenticator seperti Google Authenticator atau Authy
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
              {/* QR Code Placeholder */}
              <div className="flex justify-center">
                <div className="w-48 h-48 bg-muted rounded-lg flex items-center justify-center border-2 border-dashed">
                  <QrCode className="h-24 w-24 text-muted-foreground" />
                </div>
              </div>

              {/* Manual Code */}
              <div className="p-4 rounded-lg bg-muted">
                <p className="text-sm font-medium mb-2">Atau masukkan kode manual:</p>
                <div className="flex items-center gap-2">
                  <code className="flex-1 p-2 bg-background rounded font-mono text-sm">
                    JBSWY3DPEHPK3PXP
                  </code>
                  <Button variant="outline" size="icon">
                    <Copy className="h-4 w-4" />
                  </Button>
                </div>
              </div>

              {/* Verification */}
              <div className="space-y-2">
                <label className="text-sm font-medium">Masukkan kode verifikasi</label>
                <div className="flex gap-2">
                  <Input
                    placeholder="000000"
                    value={verificationCode}
                    onChange={(e) => setVerificationCode(e.target.value)}
                    maxLength={6}
                    className="font-mono text-center text-lg tracking-widest"
                  />
                  <Button
                    onClick={() => {
                      setIs2FAEnabled(true);
                      setShowSetup(false);
                      setShowBackupCodes(true);
                    }}
                    disabled={verificationCode.length !== 6}
                  >
                    Verifikasi
                  </Button>
                </div>
              </div>
            </CardContent>
          </Card>
        )}

        {/* Backup Codes */}
        {is2FAEnabled && (
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-lg">
                <Key className="h-5 w-5 text-primary" />
                Kode Backup
              </CardTitle>
              <CardDescription>
                Gunakan kode ini jika Anda kehilangan akses ke authenticator
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="flex items-center justify-between p-4 rounded-lg bg-muted">
                <div className="flex items-center gap-3">
                  <AlertTriangle className="h-5 w-5 text-warning" />
                  <div>
                    <p className="font-medium">Sisa Kode Backup</p>
                    <p className="text-sm text-muted-foreground">
                      {mock2FAStatus.backup_codes_remaining} dari 8 kode tersisa
                    </p>
                  </div>
                </div>
                <Button variant="outline" size="sm" onClick={() => setShowBackupCodes(!showBackupCodes)}>
                  {showBackupCodes ? 'Sembunyikan' : 'Lihat Kode'}
                </Button>
              </div>

              {showBackupCodes && (
                <div className="space-y-4">
                  <div className="grid grid-cols-2 gap-2">
                    {backupCodes.map((code, index) => (
                      <div
                        key={index}
                        className="flex items-center justify-between p-2 rounded bg-muted font-mono text-sm"
                      >
                        <span>{code}</span>
                        <Button
                          variant="ghost"
                          size="icon"
                          className="h-6 w-6"
                          onClick={() => handleCopyCode(code)}
                        >
                          <Copy className="h-3 w-3" />
                        </Button>
                      </div>
                    ))}
                  </div>
                  <div className="flex gap-2">
                    <Button variant="outline" className="flex-1" onClick={handleCopyAllCodes}>
                      <Copy className="h-4 w-4 mr-2" />
                      Salin Semua
                    </Button>
                    <Button variant="outline" className="flex-1">
                      <RefreshCw className="h-4 w-4 mr-2" />
                      Generate Baru
                    </Button>
                  </div>
                </div>
              )}
            </CardContent>
          </Card>
        )}

        {/* Trusted Devices */}
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2 text-lg">
              <Smartphone className="h-5 w-5 text-primary" />
              Metode Verifikasi
            </CardTitle>
          </CardHeader>
          <CardContent className="space-y-3">
            <div className="flex items-center justify-between p-4 rounded-lg border">
              <div className="flex items-center gap-3">
                <div className="p-2 rounded-lg bg-primary/10">
                  <Smartphone className="h-5 w-5 text-primary" />
                </div>
                <div>
                  <p className="font-medium">Authenticator App</p>
                  <p className="text-sm text-muted-foreground">Google Authenticator, Authy, dll</p>
                </div>
              </div>
              {is2FAEnabled && mock2FAStatus.method === 'authenticator' && (
                <Badge>
                  <CheckCircle className="h-3 w-3 mr-1" />
                  Aktif
                </Badge>
              )}
            </div>

            <div className="flex items-center justify-between p-4 rounded-lg border opacity-50">
              <div className="flex items-center gap-3">
                <div className="p-2 rounded-lg bg-muted">
                  <Key className="h-5 w-5 text-muted-foreground" />
                </div>
                <div>
                  <p className="font-medium">Security Key</p>
                  <p className="text-sm text-muted-foreground">YubiKey, hardware key lainnya</p>
                </div>
              </div>
              <Badge variant="outline">Segera Hadir</Badge>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
