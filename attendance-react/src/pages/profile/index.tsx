import { useState, useRef } from 'react';
import {
  User,
  Mail,
  Phone,
  Building2,
  Briefcase,
  Calendar,
  Shield,
  ShieldCheck,
  Camera,
  Trash2,
  Loader2,
  Save,
  Lock,
  AlertTriangle,
  CheckCircle,
  Clock,
  TrendingUp,
  UserCheck,
  AlertCircle,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from '@/components/ui/dialog';
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
import { Progress } from '@/components/ui/progress';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import {
  useProfile,
  useProfileStatistics,
  useUpdateProfile,
  useChangePassword,
  useUploadAvatar,
  useDeleteAvatar,
  useDeleteAccount,
} from '@/hooks/use-profile';

export default function ProfilePage() {
  const { data: profile, isLoading } = useProfile();
  const { data: statistics } = useProfileStatistics();
  const updateProfile = useUpdateProfile();
  const changePassword = useChangePassword();
  const uploadAvatar = useUploadAvatar();
  const deleteAvatar = useDeleteAvatar();
  const deleteAccount = useDeleteAccount();

  const fileInputRef = useRef<HTMLInputElement>(null);

  // Form state
  const [editMode, setEditMode] = useState(false);
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    phone: '',
  });

  // Password dialog
  const [passwordDialog, setPasswordDialog] = useState(false);
  const [passwordForm, setPasswordForm] = useState({
    current_password: '',
    password: '',
    password_confirmation: '',
  });
  const [passwordError, setPasswordError] = useState<string | null>(null);

  // Delete account dialog
  const [deleteDialog, setDeleteDialog] = useState(false);
  const [deletePassword, setDeletePassword] = useState('');
  const [deleteError, setDeleteError] = useState<string | null>(null);

  // Success message
  const [successMessage, setSuccessMessage] = useState<string | null>(null);

  const handleEditClick = () => {
    if (profile) {
      setFormData({
        name: profile.name,
        email: profile.email,
        phone: profile.phone || '',
      });
      setEditMode(true);
    }
  };

  const handleSaveProfile = async () => {
    try {
      await updateProfile.mutateAsync(formData);
      setEditMode(false);
      setSuccessMessage('Profil berhasil diperbarui');
      setTimeout(() => setSuccessMessage(null), 3000);
    } catch {
      // Error handled by mutation
    }
  };

  const handleChangePassword = async () => {
    setPasswordError(null);
    try {
      await changePassword.mutateAsync(passwordForm);
      setPasswordDialog(false);
      setPasswordForm({ current_password: '', password: '', password_confirmation: '' });
      setSuccessMessage('Password berhasil diubah');
      setTimeout(() => setSuccessMessage(null), 3000);
    } catch (err) {
      setPasswordError(err instanceof Error ? err.message : 'Gagal mengubah password');
    }
  };

  const handleAvatarUpload = async (event: React.ChangeEvent<HTMLInputElement>) => {
    const file = event.target.files?.[0];
    if (file) {
      try {
        await uploadAvatar.mutateAsync(file);
        setSuccessMessage('Avatar berhasil diperbarui');
        setTimeout(() => setSuccessMessage(null), 3000);
      } catch {
        // Error handled by mutation
      }
    }
  };

  const handleDeleteAvatar = async () => {
    try {
      await deleteAvatar.mutateAsync();
      setSuccessMessage('Avatar berhasil dihapus');
      setTimeout(() => setSuccessMessage(null), 3000);
    } catch {
      // Error handled by mutation
    }
  };

  const handleDeleteAccount = async () => {
    setDeleteError(null);
    try {
      await deleteAccount.mutateAsync(deletePassword);
      // Redirect to login or home
      window.location.href = '/login';
    } catch (err) {
      setDeleteError(err instanceof Error ? err.message : 'Gagal menghapus akun');
    }
  };

  const getInitials = (name: string) => {
    return name
      .split(' ')
      .map((n) => n[0])
      .join('')
      .toUpperCase()
      .slice(0, 2);
  };

  const formatDate = (dateStr: string | null) => {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('id-ID', {
      day: 'numeric',
      month: 'long',
      year: 'numeric',
    });
  };

  if (isLoading) {
    return (
      <div className="flex h-[50vh] items-center justify-center">
        <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
      </div>
    );
  }

  if (!profile) {
    return (
      <div className="p-6">
        <Alert variant="destructive">
          <AlertTriangle className="h-4 w-4" />
          <AlertDescription>Gagal memuat profil</AlertDescription>
        </Alert>
      </div>
    );
  }

  return (
    <div className="p-6 space-y-6">
      {/* Success Message */}
      {successMessage && (
        <Alert className="bg-success/10 border-success/20">
          <CheckCircle className="h-4 w-4 text-success" />
          <AlertDescription className="text-success">{successMessage}</AlertDescription>
        </Alert>
      )}

      {/* Profile Header */}
      <div className="flex flex-col md:flex-row gap-6">
        <Card className="flex-1">
          <CardContent className="p-6">
            <div className="flex flex-col md:flex-row items-center md:items-start gap-6">
              {/* Avatar Section */}
              <div className="relative">
                <Avatar className="h-32 w-32">
                  <AvatarImage src={profile.avatar || undefined} alt={profile.name} />
                  <AvatarFallback className="text-2xl bg-primary/10 text-primary">
                    {getInitials(profile.name)}
                  </AvatarFallback>
                </Avatar>
                <input
                  type="file"
                  ref={fileInputRef}
                  className="hidden"
                  accept="image/*"
                  onChange={handleAvatarUpload}
                />
                <div className="absolute -bottom-2 left-1/2 -translate-x-1/2 flex gap-1">
                  <Button
                    size="icon"
                    variant="secondary"
                    className="h-8 w-8 rounded-full"
                    onClick={() => fileInputRef.current?.click()}
                    disabled={uploadAvatar.isPending}
                  >
                    {uploadAvatar.isPending ? (
                      <Loader2 className="h-4 w-4 animate-spin" />
                    ) : (
                      <Camera className="h-4 w-4" />
                    )}
                  </Button>
                  {profile.avatar && (
                    <Button
                      size="icon"
                      variant="destructive"
                      className="h-8 w-8 rounded-full"
                      onClick={handleDeleteAvatar}
                      disabled={deleteAvatar.isPending}
                    >
                      <Trash2 className="h-4 w-4" />
                    </Button>
                  )}
                </div>
              </div>

              {/* Profile Info */}
              <div className="flex-1 text-center md:text-left space-y-2">
                <div className="flex flex-col md:flex-row md:items-center gap-2">
                  <h1 className="text-2xl font-bold">{profile.name}</h1>
                  {profile.two_factor_enabled && (
                    <Badge variant="secondary" className="w-fit mx-auto md:mx-0">
                      <ShieldCheck className="h-3 w-3 mr-1" />
                      2FA Aktif
                    </Badge>
                  )}
                </div>
                <p className="text-muted-foreground">{profile.email}</p>
                <div className="flex flex-wrap justify-center md:justify-start gap-2 mt-2">
                  <Badge variant="outline">{profile.role}</Badge>
                  {profile.department && <Badge variant="outline">{profile.department}</Badge>}
                  {profile.position && <Badge variant="secondary">{profile.position}</Badge>}
                </div>
                <p className="text-sm text-muted-foreground mt-2">
                  ID Karyawan: {profile.employee_id || '-'}
                </p>
              </div>

              {/* Actions */}
              <div className="flex flex-col gap-2">
                {!editMode ? (
                  <Button onClick={handleEditClick}>Edit Profil</Button>
                ) : (
                  <Button variant="outline" onClick={() => setEditMode(false)}>
                    Batal
                  </Button>
                )}
                <Button variant="outline" onClick={() => setPasswordDialog(true)}>
                  <Lock className="h-4 w-4 mr-2" />
                  Ubah Password
                </Button>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Statistics Cards */}
      {statistics && (
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
          <Card>
            <CardContent className="p-4">
              <div className="flex items-center gap-3">
                <div className="p-2 rounded-lg bg-success/10">
                  <UserCheck className="h-5 w-5 text-success" />
                </div>
                <div>
                  <p className="text-sm text-muted-foreground">Hadir</p>
                  <p className="text-xl font-semibold">{statistics.present_days}</p>
                </div>
              </div>
            </CardContent>
          </Card>
          <Card>
            <CardContent className="p-4">
              <div className="flex items-center gap-3">
                <div className="p-2 rounded-lg bg-warning/10">
                  <Clock className="h-5 w-5 text-warning" />
                </div>
                <div>
                  <p className="text-sm text-muted-foreground">Terlambat</p>
                  <p className="text-xl font-semibold">{statistics.late_days}</p>
                </div>
              </div>
            </CardContent>
          </Card>
          <Card>
            <CardContent className="p-4">
              <div className="flex items-center gap-3">
                <div className="p-2 rounded-lg bg-primary/10">
                  <Calendar className="h-5 w-5 text-primary" />
                </div>
                <div>
                  <p className="text-sm text-muted-foreground">Cuti</p>
                  <p className="text-xl font-semibold">{statistics.leave_days}</p>
                </div>
              </div>
            </CardContent>
          </Card>
          <Card>
            <CardContent className="p-4">
              <div className="flex items-center gap-3">
                <div className="p-2 rounded-lg bg-chart-5/10">
                  <TrendingUp className="h-5 w-5 text-chart-5" />
                </div>
                <div>
                  <p className="text-sm text-muted-foreground">Kehadiran</p>
                  <p className="text-xl font-semibold">{statistics.current_month_attendance_rate}%</p>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>
      )}

      {/* Tabs */}
      <Tabs defaultValue="info" className="space-y-4">
        <TabsList>
          <TabsTrigger value="info">
            <User className="h-4 w-4 mr-2" />
            Informasi
          </TabsTrigger>
          <TabsTrigger value="security">
            <Shield className="h-4 w-4 mr-2" />
            Keamanan
          </TabsTrigger>
          <TabsTrigger value="danger">
            <AlertTriangle className="h-4 w-4 mr-2" />
            Zona Bahaya
          </TabsTrigger>
        </TabsList>

        {/* Info Tab */}
        <TabsContent value="info" className="space-y-4">
          <Card>
            <CardHeader>
              <CardTitle>Informasi Pribadi</CardTitle>
              <CardDescription>Kelola informasi pribadi Anda</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              {editMode ? (
                <>
                  <div className="grid gap-4 md:grid-cols-2">
                    <div className="space-y-2">
                      <Label htmlFor="name">Nama Lengkap</Label>
                      <Input
                        id="name"
                        value={formData.name}
                        onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                      />
                    </div>
                    <div className="space-y-2">
                      <Label htmlFor="email">Email</Label>
                      <Input
                        id="email"
                        type="email"
                        value={formData.email}
                        onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                      />
                    </div>
                    <div className="space-y-2">
                      <Label htmlFor="phone">No. Telepon</Label>
                      <Input
                        id="phone"
                        value={formData.phone}
                        onChange={(e) => setFormData({ ...formData, phone: e.target.value })}
                      />
                    </div>
                  </div>
                  <div className="flex justify-end">
                    <Button onClick={handleSaveProfile} disabled={updateProfile.isPending}>
                      {updateProfile.isPending ? (
                        <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                      ) : (
                        <Save className="h-4 w-4 mr-2" />
                      )}
                      Simpan Perubahan
                    </Button>
                  </div>
                </>
              ) : (
                <div className="grid gap-4 md:grid-cols-2">
                  <div className="flex items-center gap-3">
                    <User className="h-5 w-5 text-muted-foreground" />
                    <div>
                      <p className="text-sm text-muted-foreground">Nama Lengkap</p>
                      <p className="font-medium">{profile.name}</p>
                    </div>
                  </div>
                  <div className="flex items-center gap-3">
                    <Mail className="h-5 w-5 text-muted-foreground" />
                    <div>
                      <p className="text-sm text-muted-foreground">Email</p>
                      <p className="font-medium">{profile.email}</p>
                    </div>
                  </div>
                  <div className="flex items-center gap-3">
                    <Phone className="h-5 w-5 text-muted-foreground" />
                    <div>
                      <p className="text-sm text-muted-foreground">No. Telepon</p>
                      <p className="font-medium">{profile.phone || '-'}</p>
                    </div>
                  </div>
                  <div className="flex items-center gap-3">
                    <Building2 className="h-5 w-5 text-muted-foreground" />
                    <div>
                      <p className="text-sm text-muted-foreground">Departemen</p>
                      <p className="font-medium">{profile.department || '-'}</p>
                    </div>
                  </div>
                  <div className="flex items-center gap-3">
                    <Briefcase className="h-5 w-5 text-muted-foreground" />
                    <div>
                      <p className="text-sm text-muted-foreground">Jabatan</p>
                      <p className="font-medium">{profile.position || '-'}</p>
                    </div>
                  </div>
                  <div className="flex items-center gap-3">
                    <Calendar className="h-5 w-5 text-muted-foreground" />
                    <div>
                      <p className="text-sm text-muted-foreground">Tanggal Bergabung</p>
                      <p className="font-medium">{formatDate(profile.joined_at)}</p>
                    </div>
                  </div>
                </div>
              )}
            </CardContent>
          </Card>

          {/* Attendance Progress */}
          {statistics && (
            <Card>
              <CardHeader>
                <CardTitle>Statistik Kehadiran</CardTitle>
                <CardDescription>Ringkasan kehadiran Anda</CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="space-y-2">
                  <div className="flex justify-between text-sm">
                    <span>Tingkat Kehadiran Bulan Ini</span>
                    <span className="font-medium">{statistics.current_month_attendance_rate}%</span>
                  </div>
                  <Progress value={statistics.current_month_attendance_rate} className="h-2" />
                </div>
                <div className="grid grid-cols-3 gap-4 pt-4 border-t">
                  <div className="text-center">
                    <p className="text-2xl font-bold text-success">{statistics.present_days}</p>
                    <p className="text-sm text-muted-foreground">Hari Hadir</p>
                  </div>
                  <div className="text-center">
                    <p className="text-2xl font-bold text-warning">{statistics.late_days}</p>
                    <p className="text-sm text-muted-foreground">Terlambat</p>
                  </div>
                  <div className="text-center">
                    <p className="text-2xl font-bold text-destructive">{statistics.absent_days}</p>
                    <p className="text-sm text-muted-foreground">Tidak Hadir</p>
                  </div>
                </div>
              </CardContent>
            </Card>
          )}
        </TabsContent>

        {/* Security Tab */}
        <TabsContent value="security" className="space-y-4">
          <Card>
            <CardHeader>
              <CardTitle>Keamanan Akun</CardTitle>
              <CardDescription>Pengaturan keamanan dan autentikasi</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="flex items-center justify-between p-4 rounded-lg border">
                <div className="flex items-center gap-3">
                  <div
                    className={`p-2 rounded-lg ${profile.two_factor_enabled ? 'bg-success/10' : 'bg-muted'}`}
                  >
                    {profile.two_factor_enabled ? (
                      <ShieldCheck className="h-5 w-5 text-success" />
                    ) : (
                      <Shield className="h-5 w-5 text-muted-foreground" />
                    )}
                  </div>
                  <div>
                    <p className="font-medium">Autentikasi Dua Faktor</p>
                    <p className="text-sm text-muted-foreground">
                      {profile.two_factor_enabled
                        ? 'Akun Anda dilindungi dengan 2FA'
                        : 'Tambahkan lapisan keamanan ekstra'}
                    </p>
                  </div>
                </div>
                <Button variant={profile.two_factor_enabled ? 'outline' : 'default'} asChild>
                  <a href="/security">
                    {profile.two_factor_enabled ? 'Kelola' : 'Aktifkan'}
                  </a>
                </Button>
              </div>

              <div className="flex items-center justify-between p-4 rounded-lg border">
                <div className="flex items-center gap-3">
                  <div className="p-2 rounded-lg bg-primary/10">
                    <Lock className="h-5 w-5 text-primary" />
                  </div>
                  <div>
                    <p className="font-medium">Password</p>
                    <p className="text-sm text-muted-foreground">Ubah password akun Anda</p>
                  </div>
                </div>
                <Button variant="outline" onClick={() => setPasswordDialog(true)}>
                  Ubah Password
                </Button>
              </div>

              <div className="flex items-center justify-between p-4 rounded-lg border">
                <div className="flex items-center gap-3">
                  <div
                    className={`p-2 rounded-lg ${profile.email_verified_at ? 'bg-success/10' : 'bg-warning/10'}`}
                  >
                    {profile.email_verified_at ? (
                      <CheckCircle className="h-5 w-5 text-success" />
                    ) : (
                      <AlertCircle className="h-5 w-5 text-warning" />
                    )}
                  </div>
                  <div>
                    <p className="font-medium">Verifikasi Email</p>
                    <p className="text-sm text-muted-foreground">
                      {profile.email_verified_at
                        ? `Diverifikasi pada ${formatDate(profile.email_verified_at)}`
                        : 'Email belum diverifikasi'}
                    </p>
                  </div>
                </div>
                {!profile.email_verified_at && (
                  <Button variant="outline">Kirim Ulang</Button>
                )}
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        {/* Danger Zone Tab */}
        <TabsContent value="danger">
          <Card className="border-destructive/30">
            <CardHeader>
              <CardTitle className="text-destructive">Zona Bahaya</CardTitle>
              <CardDescription>
                Tindakan di bawah ini bersifat permanen dan tidak dapat dibatalkan
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div className="flex items-center justify-between p-4 rounded-lg border border-destructive/30 bg-destructive/5">
                <div>
                  <p className="font-medium text-destructive">Hapus Akun</p>
                  <p className="text-sm text-muted-foreground">
                    Menghapus akun akan menghapus semua data Anda secara permanen
                  </p>
                </div>
                <Button variant="destructive" onClick={() => setDeleteDialog(true)}>
                  <Trash2 className="h-4 w-4 mr-2" />
                  Hapus Akun
                </Button>
              </div>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>

      {/* Change Password Dialog */}
      <Dialog open={passwordDialog} onOpenChange={setPasswordDialog}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Ubah Password</DialogTitle>
            <DialogDescription>
              Masukkan password saat ini dan password baru Anda
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-4 py-4">
            {passwordError && (
              <Alert variant="destructive">
                <AlertTriangle className="h-4 w-4" />
                <AlertDescription>{passwordError}</AlertDescription>
              </Alert>
            )}
            <div className="space-y-2">
              <Label htmlFor="current_password">Password Saat Ini</Label>
              <Input
                id="current_password"
                type="password"
                value={passwordForm.current_password}
                onChange={(e) =>
                  setPasswordForm({ ...passwordForm, current_password: e.target.value })
                }
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="new_password">Password Baru</Label>
              <Input
                id="new_password"
                type="password"
                value={passwordForm.password}
                onChange={(e) => setPasswordForm({ ...passwordForm, password: e.target.value })}
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="confirm_password">Konfirmasi Password Baru</Label>
              <Input
                id="confirm_password"
                type="password"
                value={passwordForm.password_confirmation}
                onChange={(e) =>
                  setPasswordForm({ ...passwordForm, password_confirmation: e.target.value })
                }
              />
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setPasswordDialog(false)}>
              Batal
            </Button>
            <Button onClick={handleChangePassword} disabled={changePassword.isPending}>
              {changePassword.isPending && <Loader2 className="h-4 w-4 mr-2 animate-spin" />}
              Simpan Password
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Delete Account Dialog */}
      <AlertDialog open={deleteDialog} onOpenChange={setDeleteDialog}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle className="text-destructive">Hapus Akun</AlertDialogTitle>
            <AlertDialogDescription className="space-y-4">
              <p>
                Apakah Anda yakin ingin menghapus akun? Tindakan ini tidak dapat dibatalkan dan
                semua data Anda akan dihapus secara permanen.
              </p>
              {deleteError && (
                <Alert variant="destructive">
                  <AlertTriangle className="h-4 w-4" />
                  <AlertDescription>{deleteError}</AlertDescription>
                </Alert>
              )}
              <div className="space-y-2">
                <Label htmlFor="delete_password">Masukkan password untuk konfirmasi</Label>
                <Input
                  id="delete_password"
                  type="password"
                  value={deletePassword}
                  onChange={(e) => setDeletePassword(e.target.value)}
                  placeholder="Password"
                />
              </div>
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel onClick={() => setDeletePassword('')}>Batal</AlertDialogCancel>
            <AlertDialogAction
              className="bg-destructive hover:bg-destructive/90"
              onClick={handleDeleteAccount}
              disabled={deleteAccount.isPending || !deletePassword}
            >
              {deleteAccount.isPending && <Loader2 className="h-4 w-4 mr-2 animate-spin" />}
              Hapus Akun Saya
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  );
}
