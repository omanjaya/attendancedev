import { useState } from 'react';
import {
  Search,
  Filter,
  Shield,
  AlertTriangle,
  CheckCircle,
  LogIn,
  LogOut,
  Key,
  Smartphone,
  MapPin,
  Clock,
} from 'lucide-react';

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';

// Mock security events
const mockEvents = [
  {
    id: 1,
    type: 'login_success',
    message: 'Login berhasil dari Chrome di Windows',
    ip: '192.168.1.100',
    location: 'Jakarta, Indonesia',
    device: 'Chrome / Windows',
    timestamp: '2024-11-28T10:30:00',
    severity: 'info',
  },
  {
    id: 2,
    type: 'login_failed',
    message: 'Percobaan login gagal - password salah',
    ip: '192.168.1.105',
    location: 'Jakarta, Indonesia',
    device: 'Firefox / MacOS',
    timestamp: '2024-11-28T09:15:00',
    severity: 'warning',
  },
  {
    id: 3,
    type: 'password_changed',
    message: 'Password berhasil diubah',
    ip: '192.168.1.100',
    location: 'Jakarta, Indonesia',
    device: 'Chrome / Windows',
    timestamp: '2024-11-27T14:20:00',
    severity: 'info',
  },
  {
    id: 4,
    type: 'new_device',
    message: 'Login dari perangkat baru terdeteksi',
    ip: '103.123.45.67',
    location: 'Surabaya, Indonesia',
    device: 'Safari / iPhone',
    timestamp: '2024-11-27T08:45:00',
    severity: 'warning',
  },
  {
    id: 5,
    type: '2fa_enabled',
    message: 'Two-Factor Authentication diaktifkan',
    ip: '192.168.1.100',
    location: 'Jakarta, Indonesia',
    device: 'Chrome / Windows',
    timestamp: '2024-11-26T16:30:00',
    severity: 'success',
  },
  {
    id: 6,
    type: 'logout',
    message: 'Logout dari semua perangkat',
    ip: '192.168.1.100',
    location: 'Jakarta, Indonesia',
    device: 'Chrome / Windows',
    timestamp: '2024-11-26T11:00:00',
    severity: 'info',
  },
  {
    id: 7,
    type: 'suspicious_activity',
    message: 'Aktivitas mencurigakan terdeteksi - multiple failed logins',
    ip: '45.67.89.123',
    location: 'Unknown',
    device: 'Unknown',
    timestamp: '2024-11-25T23:45:00',
    severity: 'danger',
  },
];

const eventIcons: Record<string, React.ElementType> = {
  login_success: LogIn,
  login_failed: AlertTriangle,
  password_changed: Key,
  new_device: Smartphone,
  '2fa_enabled': Shield,
  logout: LogOut,
  suspicious_activity: AlertTriangle,
};

const severityColors: Record<string, string> = {
  info: 'bg-blue-100 text-blue-700',
  success: 'bg-green-100 text-green-700',
  warning: 'bg-yellow-100 text-yellow-700',
  danger: 'bg-red-100 text-red-700',
};

export default function SecurityEventsPage() {
  const [searchQuery, setSearchQuery] = useState('');
  const [severityFilter, setSeverityFilter] = useState('all');

  const filteredEvents = mockEvents.filter(event => {
    const matchesSearch = event.message.toLowerCase().includes(searchQuery.toLowerCase()) ||
      event.ip.includes(searchQuery);
    const matchesSeverity = severityFilter === 'all' || event.severity === severityFilter;
    return matchesSearch && matchesSeverity;
  });

  const stats = {
    total: mockEvents.length,
    warnings: mockEvents.filter(e => e.severity === 'warning').length,
    dangers: mockEvents.filter(e => e.severity === 'danger').length,
  };

  return (
    <div className="p-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
          <h1 className="text-2xl font-bold text-foreground">Log Keamanan</h1>
          <p className="text-sm text-muted-foreground">
            Pantau semua aktivitas keamanan akun Anda
          </p>
        </div>
        <Button variant="outline">
          <Shield className="h-4 w-4 mr-2" />
          Pengaturan Keamanan
        </Button>
      </div>

      {/* Stats */}
      <div className="grid grid-cols-3 gap-4 mb-6">
        <Card>
          <CardContent className="p-4 text-center">
            <Clock className="h-8 w-8 mx-auto mb-2 text-primary" />
            <p className="text-2xl font-bold">{stats.total}</p>
            <p className="text-xs text-muted-foreground">Total Events</p>
          </CardContent>
        </Card>
        <Card className="bg-warning/10">
          <CardContent className="p-4 text-center">
            <AlertTriangle className="h-8 w-8 mx-auto mb-2 text-warning" />
            <p className="text-2xl font-bold">{stats.warnings}</p>
            <p className="text-xs text-muted-foreground">Peringatan</p>
          </CardContent>
        </Card>
        <Card className="bg-destructive/10">
          <CardContent className="p-4 text-center">
            <Shield className="h-8 w-8 mx-auto mb-2 text-destructive" />
            <p className="text-2xl font-bold">{stats.dangers}</p>
            <p className="text-xs text-muted-foreground">Ancaman</p>
          </CardContent>
        </Card>
      </div>

      {/* Filters */}
      <Card className="mb-6">
        <CardContent className="p-4">
          <div className="flex flex-col sm:flex-row gap-4">
            <div className="relative flex-1">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
              <Input
                placeholder="Cari event atau IP address..."
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                className="pl-10"
              />
            </div>
            <Select value={severityFilter} onValueChange={setSeverityFilter}>
              <SelectTrigger className="w-full sm:w-[180px]">
                <Filter className="h-4 w-4 mr-2" />
                <SelectValue placeholder="Semua Level" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">Semua Level</SelectItem>
                <SelectItem value="info">Info</SelectItem>
                <SelectItem value="success">Sukses</SelectItem>
                <SelectItem value="warning">Peringatan</SelectItem>
                <SelectItem value="danger">Bahaya</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </CardContent>
      </Card>

      {/* Events List */}
      <Card>
        <CardHeader>
          <CardTitle className="text-lg">Aktivitas Terbaru</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="space-y-4">
            {filteredEvents.map((event) => {
              const EventIcon = eventIcons[event.type] || Shield;
              return (
                <div
                  key={event.id}
                  className={`p-4 rounded-lg border ${
                    event.severity === 'danger' ? 'border-destructive bg-destructive/5' :
                    event.severity === 'warning' ? 'border-warning bg-warning/5' :
                    'border-border'
                  }`}
                >
                  <div className="flex items-start gap-4">
                    <div className={`p-2 rounded-lg ${severityColors[event.severity]}`}>
                      <EventIcon className="h-5 w-5" />
                    </div>
                    <div className="flex-1 min-w-0">
                      <div className="flex items-center gap-2 mb-1">
                        <p className="font-medium">{event.message}</p>
                        <Badge variant="outline" className={severityColors[event.severity]}>
                          {event.severity}
                        </Badge>
                      </div>
                      <div className="flex flex-wrap gap-4 text-sm text-muted-foreground">
                        <span className="flex items-center gap-1">
                          <Clock className="h-3 w-3" />
                          {new Date(event.timestamp).toLocaleString('id-ID')}
                        </span>
                        <span className="flex items-center gap-1">
                          <MapPin className="h-3 w-3" />
                          {event.location}
                        </span>
                        <span className="flex items-center gap-1">
                          <Smartphone className="h-3 w-3" />
                          {event.device}
                        </span>
                        <span className="font-mono text-xs">
                          IP: {event.ip}
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              );
            })}
          </div>

          {filteredEvents.length === 0 && (
            <div className="text-center py-12">
              <CheckCircle className="h-12 w-12 mx-auto mb-4 text-muted-foreground" />
              <p className="text-lg font-medium">Tidak ada event</p>
              <p className="text-sm text-muted-foreground">
                Tidak ada aktivitas keamanan yang cocok dengan filter
              </p>
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
}
