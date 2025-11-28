import { Link } from '@tanstack/react-router';
import {
  ArrowLeft,
  Mail,
  Shield,
  Calendar,
  Edit,
  Trash2,
  CheckCircle,
  Clock,
  Key,
  LogIn,
} from 'lucide-react';

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Separator } from '@/components/ui/separator';
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

// Mock user data
const mockUser = {
  id: 1,
  name: 'Admin User',
  email: 'admin@example.com',
  role: 'admin',
  role_label: 'Administrator',
  status: 'active',
  email_verified_at: '2024-01-15T10:00:00',
  two_factor_enabled: true,
  created_at: '2024-01-15T10:00:00',
  last_login: '2024-11-28T08:30:00',
  permissions: [
    'dashboard.view',
    'employees.view',
    'employees.create',
    'employees.edit',
    'attendance.view',
    'attendance.manage',
    'leave.view',
    'leave.approve',
    'reports.view',
  ],
};

const loginHistory = [
  { date: '2024-11-28T08:30:00', ip: '192.168.1.100', device: 'Chrome / Windows', status: 'success' },
  { date: '2024-11-27T09:15:00', ip: '192.168.1.100', device: 'Chrome / Windows', status: 'success' },
  { date: '2024-11-26T08:45:00', ip: '192.168.1.105', device: 'Safari / MacOS', status: 'success' },
  { date: '2024-11-25T10:00:00', ip: '103.123.45.67', device: 'Firefox / Linux', status: 'failed' },
];

const permissionLabels: Record<string, string> = {
  'dashboard.view': 'Lihat Dashboard',
  'employees.view': 'Lihat Karyawan',
  'employees.create': 'Tambah Karyawan',
  'employees.edit': 'Edit Karyawan',
  'attendance.view': 'Lihat Absensi',
  'attendance.manage': 'Kelola Absensi',
  'leave.view': 'Lihat Cuti',
  'leave.approve': 'Approve Cuti',
  'reports.view': 'Lihat Laporan',
};

export default function UserShowPage() {
  const user = mockUser;

  return (
    <div className="p-6 max-w-4xl mx-auto">
      {/* Header */}
      <div className="mb-6">
        <Link
          to="/admin/users"
          className="inline-flex items-center text-sm text-muted-foreground hover:text-foreground mb-4"
        >
          <ArrowLeft className="h-4 w-4 mr-2" />
          Kembali ke daftar user
        </Link>

        <div className="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
          <div className="flex items-center gap-4">
            <Avatar className="h-16 w-16">
              <AvatarFallback className="text-xl bg-primary/10 text-primary">
                {user.name.split(' ').map(n => n[0]).join('')}
              </AvatarFallback>
            </Avatar>
            <div>
              <div className="flex items-center gap-2">
                <h1 className="text-2xl font-bold text-foreground">{user.name}</h1>
                <Badge variant={user.status === 'active' ? 'default' : 'secondary'}>
                  {user.status === 'active' ? 'Aktif' : 'Tidak Aktif'}
                </Badge>
              </div>
              <p className="text-sm text-muted-foreground">{user.email}</p>
            </div>
          </div>
          <div className="flex items-center gap-2">
            <Button variant="outline" asChild>
              <a href={`/admin/users/${user.id}/edit`}>
                <Edit className="h-4 w-4 mr-2" />
                Edit
              </a>
            </Button>
            <AlertDialog>
              <AlertDialogTrigger asChild>
                <Button variant="destructive">
                  <Trash2 className="h-4 w-4 mr-2" />
                  Hapus
                </Button>
              </AlertDialogTrigger>
              <AlertDialogContent>
                <AlertDialogHeader>
                  <AlertDialogTitle>Hapus User?</AlertDialogTitle>
                  <AlertDialogDescription>
                    Tindakan ini tidak dapat dibatalkan. User "{user.name}" akan dihapus dari sistem.
                  </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                  <AlertDialogCancel>Batal</AlertDialogCancel>
                  <AlertDialogAction className="bg-destructive text-destructive-foreground">
                    Hapus
                  </AlertDialogAction>
                </AlertDialogFooter>
              </AlertDialogContent>
            </AlertDialog>
          </div>
        </div>
      </div>

      <div className="grid gap-6 lg:grid-cols-3">
        {/* Main Content */}
        <div className="lg:col-span-2 space-y-6">
          {/* Role & Permissions */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-lg">
                <Shield className="h-5 w-5 text-primary" />
                Role & Permission
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="flex items-center gap-3 p-4 rounded-lg bg-primary/10">
                <Shield className="h-6 w-6 text-primary" />
                <div>
                  <p className="font-semibold">{user.role_label}</p>
                  <p className="text-sm text-muted-foreground">Role utama user</p>
                </div>
              </div>

              <Separator />

              <div>
                <p className="text-sm font-medium mb-3">Permission</p>
                <div className="flex flex-wrap gap-2">
                  {user.permissions.map((permission) => (
                    <Badge key={permission} variant="outline">
                      <CheckCircle className="h-3 w-3 mr-1 text-success" />
                      {permissionLabels[permission] || permission}
                    </Badge>
                  ))}
                </div>
              </div>
            </CardContent>
          </Card>

          {/* Login History */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-lg">
                <LogIn className="h-5 w-5 text-primary" />
                Riwayat Login
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="space-y-3">
                {loginHistory.map((login, index) => (
                  <div
                    key={index}
                    className={`p-3 rounded-lg border ${
                      login.status === 'failed' ? 'border-destructive/50 bg-destructive/5' : ''
                    }`}
                  >
                    <div className="flex items-center justify-between">
                      <div className="flex items-center gap-3">
                        <div className={`p-2 rounded-lg ${
                          login.status === 'success' ? 'bg-success/10' : 'bg-destructive/10'
                        }`}>
                          <LogIn className={`h-4 w-4 ${
                            login.status === 'success' ? 'text-success' : 'text-destructive'
                          }`} />
                        </div>
                        <div>
                          <p className="text-sm font-medium">{login.device}</p>
                          <p className="text-xs text-muted-foreground">IP: {login.ip}</p>
                        </div>
                      </div>
                      <div className="text-right">
                        <Badge variant={login.status === 'success' ? 'default' : 'destructive'}>
                          {login.status === 'success' ? 'Berhasil' : 'Gagal'}
                        </Badge>
                        <p className="text-xs text-muted-foreground mt-1">
                          {new Date(login.date).toLocaleString('id-ID')}
                        </p>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Sidebar */}
        <div className="space-y-6">
          {/* Account Info */}
          <Card>
            <CardHeader>
              <CardTitle className="text-lg">Informasi Akun</CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="flex items-center gap-3">
                <Mail className="h-4 w-4 text-muted-foreground" />
                <div>
                  <p className="text-xs text-muted-foreground">Email</p>
                  <p className="text-sm">{user.email}</p>
                </div>
              </div>
              <div className="flex items-center gap-3">
                <CheckCircle className="h-4 w-4 text-success" />
                <div>
                  <p className="text-xs text-muted-foreground">Email Terverifikasi</p>
                  <p className="text-sm">
                    {new Date(user.email_verified_at).toLocaleDateString('id-ID')}
                  </p>
                </div>
              </div>
              <div className="flex items-center gap-3">
                <Calendar className="h-4 w-4 text-muted-foreground" />
                <div>
                  <p className="text-xs text-muted-foreground">Dibuat</p>
                  <p className="text-sm">
                    {new Date(user.created_at).toLocaleDateString('id-ID')}
                  </p>
                </div>
              </div>
              <div className="flex items-center gap-3">
                <Clock className="h-4 w-4 text-muted-foreground" />
                <div>
                  <p className="text-xs text-muted-foreground">Login Terakhir</p>
                  <p className="text-sm">
                    {new Date(user.last_login).toLocaleString('id-ID')}
                  </p>
                </div>
              </div>
            </CardContent>
          </Card>

          {/* Security */}
          <Card>
            <CardHeader>
              <CardTitle className="text-lg">Keamanan</CardTitle>
            </CardHeader>
            <CardContent className="space-y-3">
              <div className={`p-3 rounded-lg ${user.two_factor_enabled ? 'bg-success/10' : 'bg-warning/10'}`}>
                <div className="flex items-center gap-2">
                  <Key className={`h-4 w-4 ${user.two_factor_enabled ? 'text-success' : 'text-warning'}`} />
                  <div>
                    <p className="text-sm font-medium">Two-Factor Auth</p>
                    <p className="text-xs text-muted-foreground">
                      {user.two_factor_enabled ? 'Aktif' : 'Tidak aktif'}
                    </p>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>

          {/* Quick Actions */}
          <Card>
            <CardHeader>
              <CardTitle className="text-lg">Aksi Cepat</CardTitle>
            </CardHeader>
            <CardContent className="space-y-2">
              <Button variant="outline" className="w-full justify-start">
                <Key className="h-4 w-4 mr-2" />
                Reset Password
              </Button>
              <Button variant="outline" className="w-full justify-start">
                <Shield className="h-4 w-4 mr-2" />
                Kelola Permission
              </Button>
              <Button variant="outline" className="w-full justify-start text-destructive">
                <LogIn className="h-4 w-4 mr-2" />
                Logout Semua Sesi
              </Button>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  );
}
