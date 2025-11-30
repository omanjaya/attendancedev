import { useState } from 'react';
import {
  Smartphone,
  Laptop,
  Monitor,
  Trash2,
  MapPin,
  Clock,
  Shield,
  AlertTriangle,
  CheckCircle,
} from 'lucide-react';

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
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

// Mock device data
const mockDevices = [
  {
    id: 1,
    name: 'iPhone 14 Pro',
    type: 'mobile',
    browser: 'Safari',
    os: 'iOS 17.1',
    location: 'Jakarta, Indonesia',
    last_active: '2024-11-28T10:30:00',
    is_current: true,
    trusted: true,
  },
  {
    id: 2,
    name: 'MacBook Pro',
    type: 'desktop',
    browser: 'Chrome 119',
    os: 'macOS Sonoma',
    location: 'Jakarta, Indonesia',
    last_active: '2024-11-28T09:15:00',
    is_current: false,
    trusted: true,
  },
  {
    id: 3,
    name: 'Windows PC',
    type: 'desktop',
    browser: 'Edge 119',
    os: 'Windows 11',
    location: 'Bandung, Indonesia',
    last_active: '2024-11-25T14:20:00',
    is_current: false,
    trusted: false,
  },
  {
    id: 4,
    name: 'Samsung Galaxy S23',
    type: 'mobile',
    browser: 'Chrome Mobile',
    os: 'Android 14',
    location: 'Surabaya, Indonesia',
    last_active: '2024-11-20T08:45:00',
    is_current: false,
    trusted: false,
  },
];

const deviceIcons = {
  mobile: Smartphone,
  desktop: Monitor,
  tablet: Laptop,
};

export default function SecurityDevicesPage() {
  const [devices, setDevices] = useState(mockDevices);

  const handleRemoveDevice = (deviceId: number) => {
    setDevices(devices.filter(d => d.id !== deviceId));
  };

  const handleLogoutAllDevices = () => {
    setDevices(devices.filter(d => d.is_current));
  };

  const formatLastActive = (dateString: string) => {
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now.getTime() - date.getTime();
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffMins < 5) return 'Aktif sekarang';
    if (diffMins < 60) return `${diffMins} menit lalu`;
    if (diffHours < 24) return `${diffHours} jam lalu`;
    if (diffDays < 7) return `${diffDays} hari lalu`;
    return date.toLocaleDateString('id-ID');
  };

  return (
    <div className="p-6 max-w-4xl mx-auto">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
          <h1 className="text-2xl font-bold text-foreground">Perangkat Terdaftar</h1>
          <p className="text-sm text-muted-foreground">
            Kelola perangkat yang terhubung ke akun Anda
          </p>
        </div>
        <AlertDialog>
          <AlertDialogTrigger asChild>
            <Button variant="destructive" size="sm">
              <Shield className="h-4 w-4 mr-2" />
              Logout Semua Perangkat
            </Button>
          </AlertDialogTrigger>
          <AlertDialogContent>
            <AlertDialogHeader>
              <AlertDialogTitle>Logout dari Semua Perangkat?</AlertDialogTitle>
              <AlertDialogDescription>
                Anda akan keluar dari semua perangkat kecuali perangkat saat ini.
                Tindakan ini tidak dapat dibatalkan.
              </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
              <AlertDialogCancel>Batal</AlertDialogCancel>
              <AlertDialogAction
                onClick={handleLogoutAllDevices}
                className="bg-destructive text-destructive-foreground"
              >
                Logout Semua
              </AlertDialogAction>
            </AlertDialogFooter>
          </AlertDialogContent>
        </AlertDialog>
      </div>

      {/* Current Device Banner */}
      <Card className="mb-6 border-primary/20 bg-primary/5">
        <CardContent className="p-4">
          <div className="flex items-center gap-3">
            <div className="p-2 rounded-lg bg-primary/10">
              <Shield className="h-5 w-5 text-primary" />
            </div>
            <div>
              <p className="font-medium">Perangkat Saat Ini</p>
              <p className="text-sm text-muted-foreground">
                Anda login dari perangkat yang ditandai sebagai "Perangkat ini"
              </p>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Device List */}
      <div className="space-y-4">
        {devices.map((device) => {
          const DeviceIcon = deviceIcons[device.type as keyof typeof deviceIcons] || Monitor;

          return (
            <Card key={device.id} className={device.is_current ? 'border-primary/50' : ''}>
              <CardContent className="p-4">
                <div className="flex flex-col sm:flex-row sm:items-center gap-4">
                  <div className={`
                    p-3 rounded-lg shrink-0
                    ${device.is_current ? 'bg-primary/10' : 'bg-muted'}
                  `}>
                    <DeviceIcon className={`h-6 w-6 ${device.is_current ? 'text-primary' : 'text-muted-foreground'}`} />
                  </div>

                  <div className="flex-1 min-w-0">
                    <div className="flex items-center gap-2 flex-wrap">
                      <h3 className="font-medium">{device.name}</h3>
                      {device.is_current && (
                        <Badge className="bg-primary/10 text-primary">
                          Perangkat ini
                        </Badge>
                      )}
                      {device.trusted ? (
                        <Badge variant="outline" className="bg-success/10 text-success">
                          <CheckCircle className="h-3 w-3 mr-1" />
                          Terpercaya
                        </Badge>
                      ) : (
                        <Badge variant="outline" className="bg-warning/10 text-warning">
                          <AlertTriangle className="h-3 w-3 mr-1" />
                          Tidak Dikenal
                        </Badge>
                      )}
                    </div>
                    <p className="text-sm text-muted-foreground mt-1">
                      {device.browser} • {device.os}
                    </p>
                    <div className="flex items-center gap-4 mt-2 text-xs text-muted-foreground">
                      <span className="flex items-center gap-1">
                        <MapPin className="h-3 w-3" />
                        {device.location}
                      </span>
                      <span className="flex items-center gap-1">
                        <Clock className="h-3 w-3" />
                        {formatLastActive(device.last_active)}
                      </span>
                    </div>
                  </div>

                  {!device.is_current && (
                    <AlertDialog>
                      <AlertDialogTrigger asChild>
                        <Button variant="ghost" size="sm" className="text-destructive">
                          <Trash2 className="h-4 w-4" />
                        </Button>
                      </AlertDialogTrigger>
                      <AlertDialogContent>
                        <AlertDialogHeader>
                          <AlertDialogTitle>Hapus Perangkat?</AlertDialogTitle>
                          <AlertDialogDescription>
                            Perangkat "{device.name}" akan dihapus dan tidak dapat mengakses akun Anda lagi.
                          </AlertDialogDescription>
                        </AlertDialogHeader>
                        <AlertDialogFooter>
                          <AlertDialogCancel>Batal</AlertDialogCancel>
                          <AlertDialogAction
                            onClick={() => handleRemoveDevice(device.id)}
                            className="bg-destructive text-destructive-foreground"
                          >
                            Hapus
                          </AlertDialogAction>
                        </AlertDialogFooter>
                      </AlertDialogContent>
                    </AlertDialog>
                  )}
                </div>
              </CardContent>
            </Card>
          );
        })}
      </div>

      {/* Security Tips */}
      <Card className="mt-6 bg-muted/50">
        <CardHeader>
          <CardTitle className="flex items-center gap-2 text-base">
            <Shield className="h-4 w-4 text-primary" />
            Tips Keamanan
          </CardTitle>
        </CardHeader>
        <CardContent className="space-y-2 text-sm text-muted-foreground">
          <p>• Hapus perangkat yang tidak Anda kenali segera</p>
          <p>• Aktifkan autentikasi dua faktor untuk keamanan tambahan</p>
          <p>• Jangan login dari perangkat publik atau bersama</p>
          <p>• Logout dari perangkat lain jika mencurigai aktivitas tidak sah</p>
        </CardContent>
      </Card>
    </div>
  );
}
