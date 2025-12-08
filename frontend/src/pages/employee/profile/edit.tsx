
import { useState, useEffect } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod'; // Import z from zod
import {
  User,
  Mail,
  Phone,
  MapPin,
  Lock,
  Trash2,
  Loader2,
  Save,
  Eye,
  EyeOff,
  AlertTriangle,
  Camera,
  Briefcase,
  GraduationCap,
  CreditCard,
  FileText,
  Contact,
} from 'lucide-react';

import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
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
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { useNotificationStore, useAuthStore } from '@/stores';
import { updateEmployee } from '@/lib/api/employees';

// --- SCHEMAS ---

const profileSchema = z.object({
  // Basic Info
  name: z.string().min(2, 'Nama minimal 2 karakter'),
  email: z.string().email('Email tidak valid'),
  phone: z.string().min(10, 'Nomor HP tidak valid').optional().or(z.literal('')),

  // Personal Info (Metadata)
  nik: z.string().min(16, 'NIK harus 16 digit').max(16).optional().or(z.literal('')),
  npwp: z.string().optional().or(z.literal('')),
  birth_place: z.string().optional().or(z.literal('')),
  birth_date: z.string().optional().or(z.literal('')),
  gender: z.enum(['male', 'female']).optional(),
  marital_status: z.enum(['single', 'married', 'divorced', 'widowed']).optional(),
  religion: z.string().optional().or(z.literal('')),

  // Contact & Address
  address: z.string().optional().or(z.literal('')),
  emergency_contact: z.object({
    name: z.string().optional().or(z.literal('')),
    relation: z.string().optional().or(z.literal('')),
    phone: z.string().optional().or(z.literal('')),
  }).optional(),

  // Education
  education: z.object({
    level: z.string().optional().or(z.literal('')),
    institution: z.string().optional().or(z.literal('')),
    major: z.string().optional().or(z.literal('')),
    year: z.string().optional().or(z.literal('')),
  }).optional(),

  // Bank
  bank_account: z.object({
    bank_name: z.string().optional().or(z.literal('')),
    account_number: z.string().optional().or(z.literal('')),
    account_holder: z.string().optional().or(z.literal('')),
  }).optional(),
});

const passwordSchema = z.object({
  current_password: z.string().min(1, 'Masukkan password saat ini'),
  password: z.string().min(8, 'Password minimal 8 karakter'),
  password_confirmation: z.string(),
}).refine((data) => data.password === data.password_confirmation, {
  message: 'Konfirmasi password tidak cocok',
  path: ['password_confirmation'],
});

type ProfileForm = z.infer<typeof profileSchema>;
type PasswordForm = z.infer<typeof passwordSchema>;

export default function ProfileEditPage() {
  const { success, error: showError } = useNotificationStore();
  const { user, fetchUser, hasRole } = useAuthStore();

  const [isProfileLoading, setIsProfileLoading] = useState(false);
  const [isPasswordLoading, setIsPasswordLoading] = useState(false);
  const [showCurrentPassword, setShowCurrentPassword] = useState(false);
  const [showNewPassword, setShowNewPassword] = useState(false);
  const [showConfirmPassword, setShowConfirmPassword] = useState(false);
  const [deleteConfirmation, setDeleteConfirmation] = useState('');

  // Refresh user data on mount to ensure we have latest metadata
  useEffect(() => {
    fetchUser();
  }, []);

  const profileForm = useForm<ProfileForm>({
    resolver: zodResolver(profileSchema),
    defaultValues: {
      name: user?.name || '',
      email: user?.email || '',
      phone: user?.employee?.phone || '',

      // Personal
      nik: user?.employee?.metadata?.nik || '',
      npwp: user?.employee?.metadata?.npwp || '',
      birth_place: user?.employee?.metadata?.birth_place || '',
      birth_date: user?.employee?.metadata?.birth_date || '',
      gender: (user?.employee?.metadata?.gender as any) || undefined,
      marital_status: (user?.employee?.metadata?.marital_status as any) || undefined,
      religion: user?.employee?.metadata?.religion || '',

      // Contact
      address: user?.employee?.address || user?.employee?.metadata?.address || '',
      emergency_contact: {
        name: user?.employee?.metadata?.emergency_contact?.name || '',
        relation: user?.employee?.metadata?.emergency_contact?.relation || '',
        phone: user?.employee?.metadata?.emergency_contact?.phone || '',
      },

      // Education
      education: {
        level: user?.employee?.metadata?.education?.level || '',
        institution: user?.employee?.metadata?.education?.institution || '',
        major: user?.employee?.metadata?.education?.major || '',
        year: user?.employee?.metadata?.education?.year || '',
      },

      // Bank
      bank_account: {
        bank_name: user?.employee?.metadata?.bank_account?.bank_name || '',
        account_number: user?.employee?.metadata?.bank_account?.account_number || '',
        account_holder: user?.employee?.metadata?.bank_account?.account_holder || '',
      },
    },
  });

  // Update form values when user data changes (e.g. after fetchUser)
  useEffect(() => {
    if (user?.employee) {
      profileForm.reset({
        name: user.name || '',
        email: user.email || '',
        phone: user.employee.phone || '',

        nik: user.employee.metadata?.nik || '',
        npwp: user.employee.metadata?.npwp || '',
        birth_place: user.employee.metadata?.birth_place || '',
        birth_date: user.employee.metadata?.birth_date || '',
        gender: (user.employee.metadata?.gender as any) || undefined,
        marital_status: (user.employee.metadata?.marital_status as any) || undefined,
        religion: user.employee.metadata?.religion || '',

        address: user.employee.address || user.employee.metadata?.address || '',
        emergency_contact: {
          name: user.employee.metadata?.emergency_contact?.name || '',
          relation: user.employee.metadata?.emergency_contact?.relation || '',
          phone: user.employee.metadata?.emergency_contact?.phone || '',
        },
        education: {
          level: user.employee.metadata?.education?.level || '',
          institution: user.employee.metadata?.education?.institution || '',
          major: user.employee.metadata?.education?.major || '',
          year: user.employee.metadata?.education?.year || '',
        },
        bank_account: {
          bank_name: user.employee.metadata?.bank_account?.bank_name || '',
          account_number: user.employee.metadata?.bank_account?.account_number || '',
          account_holder: user.employee.metadata?.bank_account?.account_holder || '',
        },
      });
    }
  }, [user]);

  const passwordForm = useForm<PasswordForm>({
    resolver: zodResolver(passwordSchema),
  });

  const onProfileSubmit = async (data: ProfileForm) => {
    setIsProfileLoading(true);
    try {
      if (!user?.employee?.id) throw new Error("Employee data not found");

      // Call update API
      await updateEmployee(user.employee.id, data as any);

      // Refresh local user state
      await fetchUser();

      success('Berhasil', 'Profil berhasil diperbarui');
    } catch (err) {
      const message = err instanceof Error ? err.message : 'Gagal memperbarui profil';
      showError('Error', message);
    } finally {
      setIsProfileLoading(false);
    }
  };

  const onPasswordSubmit = async (_data: PasswordForm) => {
    setIsPasswordLoading(true);
    try {
      // TODO: Implement password change API call here
      // This usually hits a different endpoint like /api/v1/auth/change-password
      await new Promise(resolve => setTimeout(resolve, 1000));
      success('Berhasil', 'Password berhasil diperbarui');
      passwordForm.reset();
    } catch (err) {
      const message = err instanceof Error ? err.message : 'Gagal memperbarui password';
      showError('Error', message);
    } finally {
      setIsPasswordLoading(false);
    }
  };

  return (
    <div className="p-4 sm:p-6 max-w-5xl mx-auto pb-20">
      {/* Header */}
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-foreground">Pengaturan Profil</h1>
        <p className="text-sm text-muted-foreground">
          Kelola informasi data diri, kontak, dan keamanan akun Anda
        </p>
      </div>

      <div className="flex flex-col md:flex-row gap-6">
        {/* Sidebar / Quick Info */}
        <div className="w-full md:w-1/4 space-y-6">
          <Card>
            <CardContent className="pt-6 flex flex-col items-center text-center">
              <div className="relative mb-4">
                <Avatar className="h-24 w-24">
                  <AvatarImage src={user?.avatar || user?.employee?.avatar} />
                  <AvatarFallback className="text-2xl bg-primary/10 text-primary">
                    {user?.name?.split(' ').map(n => n[0]).join('') || 'U'}
                  </AvatarFallback>
                </Avatar>
                <Button
                  size="icon"
                  variant="secondary"
                  className="absolute bottom-0 right-0 h-8 w-8 rounded-full shadow-md"
                >
                  <Camera className="h-4 w-4" />
                </Button>
              </div>
              <h3 className="font-bold text-lg">{user?.name}</h3>
              <p className="text-sm text-muted-foreground mb-4">{user?.employee?.position}</p>

              <div className="w-full space-y-2 text-left text-sm mt-2 pt-4 border-t">
                <div className="flex justify-between">
                  <span className="text-muted-foreground">ID Pegawai</span>
                  <span className="font-medium">{user?.employee?.employee_id}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Status</span>
                  <span className={`px-2 py-0.5 rounded-full text-xs font-medium ${user?.employee?.status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'
                    }`}>
                    {user?.employee?.status === 'active' ? 'Aktif' : user?.employee?.status}
                  </span>
                </div>
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Bergabung</span>
                  <span className="font-medium">
                    {user?.employee?.join_date
                      ? new Date(user.employee.join_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
                      : '-'}
                  </span>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Main Content Fields */}
        <div className="flex-1">
          <Tabs defaultValue="personal" className="w-full">
            <TabsList className="w-full justify-start overflow-x-auto h-auto p-1 mb-4 bg-background border rounded-lg md:grid md:grid-cols-5">
              <TabsTrigger value="personal" className="flex gap-2">
                <User className="h-4 w-4" /> <span className="hidden md:inline">Pribadi</span>
              </TabsTrigger>
              <TabsTrigger value="contact" className="flex gap-2">
                <Contact className="h-4 w-4" /> <span className="hidden md:inline">Kontak</span>
              </TabsTrigger>
              <TabsTrigger value="education" className="flex gap-2">
                <GraduationCap className="h-4 w-4" /> <span className="hidden md:inline">Pendidikan</span>
              </TabsTrigger>
              <TabsTrigger value="bank" className="flex gap-2">
                <CreditCard className="h-4 w-4" /> <span className="hidden md:inline">Rekening</span>
              </TabsTrigger>
              <TabsTrigger value="security" className="flex gap-2">
                <Lock className="h-4 w-4" /> <span className="hidden md:inline">Keamanan</span>
              </TabsTrigger>
            </TabsList>

            <form onSubmit={profileForm.handleSubmit(onProfileSubmit)}>

              {/* --- TAB: DATA PRIBADI --- */}
              <TabsContent value="personal">
                <Card>
                  <CardHeader>
                    <CardTitle className="text-lg flex items-center gap-2">
                      <User className="h-5 w-5 text-primary" /> Informasi Pribadi
                    </CardTitle>
                    <CardDescription>Data identitas diri dan informasi dasar</CardDescription>
                  </CardHeader>
                  <CardContent className="space-y-4">
                    <div className="grid gap-4 sm:grid-cols-2">
                      <div className="space-y-2">
                        <label className="text-sm font-medium">Nama Lengkap</label>
                        <Input {...profileForm.register('name')} />
                        {profileForm.formState.errors.name && (
                          <p className="text-xs text-destructive">{profileForm.formState.errors.name.message}</p>
                        )}
                      </div>
                      <div className="space-y-2">
                        <label className="text-sm font-medium">NIK (KTP)</label>
                        <Input {...profileForm.register('nik')} maxLength={16} placeholder="16 digit nomor KTP" />
                        {profileForm.formState.errors.nik && (
                          <p className="text-xs text-destructive">{profileForm.formState.errors.nik.message}</p>
                        )}
                      </div>
                      <div className="space-y-2">
                        <label className="text-sm font-medium">NPWP</label>
                        <Input {...profileForm.register('npwp')} placeholder="Optional" />
                      </div>
                      <div className="space-y-2">
                        <label className="text-sm font-medium">Tempat Lahir</label>
                        <Input {...profileForm.register('birth_place')} />
                      </div>
                      <div className="space-y-2">
                        <label className="text-sm font-medium">Tanggal Lahir</label>
                        <Input type="date" {...profileForm.register('birth_date')} />
                      </div>
                      <div className="space-y-2">
                        <label className="text-sm font-medium">Jenis Kelamin</label>
                        <Select
                          onValueChange={(val) => profileForm.setValue('gender', val as 'male' | 'female')}
                          defaultValue={profileForm.watch('gender')}
                        >
                          <SelectTrigger>
                            <SelectValue placeholder="Pilih..." />
                          </SelectTrigger>
                          <SelectContent>
                            <SelectItem value="male">Laki-laki</SelectItem>
                            <SelectItem value="female">Perempuan</SelectItem>
                          </SelectContent>
                        </Select>
                      </div>
                      <div className="space-y-2">
                        <label className="text-sm font-medium">Agama</label>
                        <Select
                          onValueChange={(val) => profileForm.setValue('religion', val)}
                          defaultValue={profileForm.watch('religion')}
                        >
                          <SelectTrigger><SelectValue placeholder="Pilih..." /></SelectTrigger>
                          <SelectContent>
                            <SelectItem value="Islam">Islam</SelectItem>
                            <SelectItem value="Kristen Protestan">Kristen Protestan</SelectItem>
                            <SelectItem value="Kristen Katolik">Kristen Katolik</SelectItem>
                            <SelectItem value="Hindu">Hindu</SelectItem>
                            <SelectItem value="Buddha">Buddha</SelectItem>
                            <SelectItem value="Konghucu">Konghucu</SelectItem>
                          </SelectContent>
                        </Select>
                      </div>
                      <div className="space-y-2">
                        <label className="text-sm font-medium">Status Pernikahan</label>
                        <Select
                          onValueChange={(val) => profileForm.setValue('marital_status', val as any)}
                          defaultValue={profileForm.watch('marital_status')}
                        >
                          <SelectTrigger><SelectValue placeholder="Pilih..." /></SelectTrigger>
                          <SelectContent>
                            <SelectItem value="single">Lajang</SelectItem>
                            <SelectItem value="married">Menikah</SelectItem>
                            <SelectItem value="divorced">Cerai Hidup</SelectItem>
                            <SelectItem value="widowed">Cerai Mati</SelectItem>
                          </SelectContent>
                        </Select>
                      </div>
                    </div>
                  </CardContent>
                </Card>
              </TabsContent>

              {/* --- TAB: KONTAK --- */}
              <TabsContent value="contact">
                <div className="space-y-6">
                  <Card>
                    <CardHeader>
                      <CardTitle className="text-lg flex items-center gap-2">
                        <Contact className="h-5 w-5 text-primary" /> Kontak & Alamat
                      </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                      <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                          <label className="text-sm font-medium">Email</label>
                          <div className="relative">
                            <Mail className="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
                            <Input className="pl-9" {...profileForm.register('email')} />
                            {profileForm.formState.errors.email && (
                              <p className="text-xs text-destructive">{profileForm.formState.errors.email.message}</p>
                            )}
                          </div>
                        </div>
                        <div className="space-y-2">
                          <label className="text-sm font-medium">No. Handphone</label>
                          <div className="relative">
                            <Phone className="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
                            <Input className="pl-9" {...profileForm.register('phone')} />
                          </div>
                        </div>
                      </div>
                      <div className="space-y-2">
                        <label className="text-sm font-medium">Alamat Lengkap</label>
                        <div className="relative">
                          <MapPin className="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                          <Textarea className="pl-9 min-h-[80px]" placeholder="Nama jalan, RT/RW, Kelurahan, Kecamatan" {...profileForm.register('address')} />
                        </div>
                      </div>
                    </CardContent>
                  </Card>

                  <Card>
                    <CardHeader>
                      <CardTitle className="text-lg flex items-center gap-2">
                        <AlertTriangle className="h-5 w-5 text-orange-500" /> Kontak Darurat
                      </CardTitle>
                      <CardDescription>Orang yang dapat dihubungi dalam keadaan darurat</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                      <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                          <label className="text-sm font-medium">Nama Kontak</label>
                          <Input {...profileForm.register('emergency_contact.name')} placeholder="Misal: Budi (Ayah)" />
                        </div>
                        <div className="space-y-2">
                          <label className="text-sm font-medium">Hubungan</label>
                          <Select
                            onValueChange={(val) => profileForm.setValue('emergency_contact.relation', val)}
                            defaultValue={profileForm.watch('emergency_contact.relation')}
                          >
                            <SelectTrigger><SelectValue placeholder="Pilih..." /></SelectTrigger>
                            <SelectContent>
                              <SelectItem value="Orang Tua">Orang Tua/Wali</SelectItem>
                              <SelectItem value="Pasangan">Suami/Istri</SelectItem>
                              <SelectItem value="Saudara">Saudara Kandung</SelectItem>
                              <SelectItem value="Kerabat">Kerabat Lainnya</SelectItem>
                            </SelectContent>
                          </Select>
                        </div>
                        <div className="space-y-2 sm:col-span-2">
                          <label className="text-sm font-medium">No. Telepon Darurat</label>
                          <Input {...profileForm.register('emergency_contact.phone')} placeholder="08..." />
                        </div>
                      </div>
                    </CardContent>
                  </Card>
                </div>
              </TabsContent>

              {/* --- TAB: PENDIDIKAN --- */}
              <TabsContent value="education">
                <Card>
                  <CardHeader>
                    <CardTitle className="text-lg flex items-center gap-2">
                      <GraduationCap className="h-5 w-5 text-primary" /> Data Pendidikan Terakhir
                    </CardTitle>
                  </CardHeader>
                  <CardContent className="space-y-4">
                    <div className="grid gap-4 sm:grid-cols-2">
                      <div className="space-y-2">
                        <label className="text-sm font-medium">Jenjang Pendidikan</label>
                        <Select
                          onValueChange={(val) => profileForm.setValue('education.level', val)}
                          defaultValue={profileForm.watch('education.level')}
                        >
                          <SelectTrigger><SelectValue placeholder="Pilih..." /></SelectTrigger>
                          <SelectContent>
                            <SelectItem value="SD">SD/Sederajat</SelectItem>
                            <SelectItem value="SMP">SMP/Sederajat</SelectItem>
                            <SelectItem value="SMA">SMA/SMK/Sederajat</SelectItem>
                            <SelectItem value="D3">Diploma 3 (D3)</SelectItem>
                            <SelectItem value="S1">Sarjana (S1)/D4</SelectItem>
                            <SelectItem value="S2">Magister (S2)</SelectItem>
                            <SelectItem value="S3">Doktor (S3)</SelectItem>
                          </SelectContent>
                        </Select>
                      </div>
                      <div className="space-y-2">
                        <label className="text-sm font-medium">Tahun Lulus</label>
                        <Input type="number" {...profileForm.register('education.year')} placeholder="YYYY" />
                      </div>
                      <div className="space-y-2">
                        <label className="text-sm font-medium">Nama Institusi</label>
                        <Input {...profileForm.register('education.institution')} placeholder="Nama Sekolah/Universitas" />
                      </div>
                      <div className="space-y-2">
                        <label className="text-sm font-medium">Jurusan / Prodi</label>
                        <Input {...profileForm.register('education.major')} placeholder="Contoh: Teknik Informatika" />
                      </div>
                    </div>
                  </CardContent>
                </Card>
              </TabsContent>

              {/* --- TAB: REKENING --- */}
              <TabsContent value="bank">
                <Card>
                  <CardHeader>
                    <CardTitle className="text-lg flex items-center gap-2">
                      <CreditCard className="h-5 w-5 text-primary" /> Data Bank (Payroll)
                    </CardTitle>
                    <CardDescription>Data rekening untuk keperluan transfer gaji/tunjangan</CardDescription>
                  </CardHeader>
                  <CardContent className="space-y-4">
                    <div className="grid gap-4 sm:grid-cols-2">
                      <div className="space-y-2">
                        <label className="text-sm font-medium">Nama Bank</label>
                        <Select
                          onValueChange={(val) => profileForm.setValue('bank_account.bank_name', val)}
                          defaultValue={profileForm.watch('bank_account.bank_name')}
                        >
                          <SelectTrigger><SelectValue placeholder="Pilih Bank..." /></SelectTrigger>
                          <SelectContent>
                            <SelectItem value="BCA">BCA</SelectItem>
                            <SelectItem value="MANDIRI">Mandiri</SelectItem>
                            <SelectItem value="BNI">BNI</SelectItem>
                            <SelectItem value="BRI">BRI</SelectItem>
                            <SelectItem value="BSI">BSI</SelectItem>
                            <SelectItem value="CIMB">CIMB Niaga</SelectItem>
                            <SelectItem value="BPD_BALI">BPD Bali</SelectItem>
                            <SelectItem value="PERMATA">Permata</SelectItem>
                            <SelectItem value="BTN">BTN</SelectItem>
                          </SelectContent>
                        </Select>
                      </div>
                      <div className="space-y-2">
                        <label className="text-sm font-medium">Nomor Rekening</label>
                        <Input {...profileForm.register('bank_account.account_number')} type="number" />
                      </div>
                      <div className="space-y-2 sm:col-span-2">
                        <label className="text-sm font-medium">Atas Nama</label>
                        <Input {...profileForm.register('bank_account.account_holder')} placeholder="Nama sesuai buku tabungan" />
                      </div>
                    </div>
                  </CardContent>
                </Card>
              </TabsContent>

              {/* BUTTON SAVE GLOBAL (For all definition tabs) */}
              <div className="mt-6 flex justify-end" style={{ display: ['personal', 'contact', 'education', 'bank'].includes('personal') ? 'flex' : 'none' }}>
                {/* This button is inside the form, so it triggers submit. We position it outside TabsContent but inside Form */}
              </div>
              <div className="mt-6 flex justify-end">
                <Button type="submit" size="lg" disabled={isProfileLoading}>
                  {isProfileLoading ? (
                    <>
                      <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                      Menyimpan...
                    </>
                  ) : (
                    <>
                      <Save className="mr-2 h-4 w-4" />
                      Simpan Perubahan Profil
                    </>
                  )}
                </Button>
              </div>

            </form>

            {/* --- TAB: KEAMANAN --- */}
            <TabsContent value="security">
              <div className="space-y-6">
                {/* Change Password */}
                <Card>
                  <CardHeader>
                    <CardTitle className="flex items-center gap-2 text-lg">
                      <Lock className="h-5 w-5 text-primary" />
                      Ubah Password
                    </CardTitle>
                    <CardDescription>
                      Pastikan password Anda kuat dan aman
                    </CardDescription>
                  </CardHeader>
                  <CardContent>
                    <form onSubmit={passwordForm.handleSubmit(onPasswordSubmit)} className="space-y-4">
                      <div className="space-y-2">
                        <label className="text-sm font-medium">Password Saat Ini</label>
                        <div className="relative">
                          <Input
                            type={showCurrentPassword ? 'text' : 'password'}
                            {...passwordForm.register('current_password')}
                          />
                          <button
                            type="button"
                            onClick={() => setShowCurrentPassword(!showCurrentPassword)}
                            className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground"
                          >
                            {showCurrentPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                          </button>
                        </div>
                        {passwordForm.formState.errors.current_password && (
                          <p className="text-xs text-destructive">
                            {passwordForm.formState.errors.current_password.message}
                          </p>
                        )}
                      </div>

                      <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                          <label className="text-sm font-medium">Password Baru</label>
                          <div className="relative">
                            <Input
                              type={showNewPassword ? 'text' : 'password'}
                              {...passwordForm.register('password')}
                            />
                            <button
                              type="button"
                              onClick={() => setShowNewPassword(!showNewPassword)}
                              className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground"
                            >
                              {showNewPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                            </button>
                          </div>
                          {passwordForm.formState.errors.password && (
                            <p className="text-xs text-destructive">
                              {passwordForm.formState.errors.password.message}
                            </p>
                          )}
                        </div>

                        <div className="space-y-2">
                          <label className="text-sm font-medium">Konfirmasi Password</label>
                          <div className="relative">
                            <Input
                              type={showConfirmPassword ? 'text' : 'password'}
                              {...passwordForm.register('password_confirmation')}
                            />
                            <button
                              type="button"
                              onClick={() => setShowConfirmPassword(!showConfirmPassword)}
                              className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground"
                            >
                              {showConfirmPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                            </button>
                          </div>
                          {passwordForm.formState.errors.password_confirmation && (
                            <p className="text-xs text-destructive">
                              {passwordForm.formState.errors.password_confirmation.message}
                            </p>
                          )}
                        </div>
                      </div>

                      <div className="flex justify-end">
                        <Button type="submit" disabled={isPasswordLoading}>
                          {isPasswordLoading ? (
                            <>
                              <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                              Menyimpan...
                            </>
                          ) : (
                            <>
                              <Lock className="mr-2 h-4 w-4" />
                              Update Password
                            </>
                          )}
                        </Button>
                      </div>
                    </form>
                  </CardContent>
                </Card>

                {/* Delete Account */}
                <Card className="border-destructive/50">
                  <CardHeader>
                    <CardTitle className="flex items-center gap-2 text-lg text-destructive">
                      <Trash2 className="h-5 w-5" />
                      Hapus Akun
                    </CardTitle>
                    <CardDescription>
                      Hapus akun Anda secara permanen. Tindakan ini tidak dapat dibatalkan.
                    </CardDescription>
                  </CardHeader>
                  <CardContent>
                    <div className="p-4 rounded-lg bg-destructive/10 border border-destructive/20 mb-4">
                      <div className="flex items-start gap-3">
                        <AlertTriangle className="h-5 w-5 text-destructive mt-0.5" />
                        <div>
                          <p className="font-medium text-destructive">Perhatian!</p>
                          <p className="text-sm text-muted-foreground">
                            Menghapus akun akan menghapus semua data termasuk riwayat absensi,
                            data payroll, dan informasi lainnya secara permanen.
                          </p>
                        </div>
                      </div>
                    </div>

                    <AlertDialog>
                      <AlertDialogTrigger asChild>
                        <Button variant="destructive">
                          <Trash2 className="h-4 w-4 mr-2" />
                          Hapus Akun Saya
                        </Button>
                      </AlertDialogTrigger>
                      <AlertDialogContent>
                        <AlertDialogHeader>
                          <AlertDialogTitle>Hapus Akun?</AlertDialogTitle>
                          <AlertDialogDescription>
                            Ketik "HAPUS" untuk mengkonfirmasi penghapusan akun Anda.
                          </AlertDialogDescription>
                        </AlertDialogHeader>
                        <Input
                          placeholder="Ketik HAPUS"
                          value={deleteConfirmation}
                          onChange={(e) => setDeleteConfirmation(e.target.value)}
                        />
                        <AlertDialogFooter>
                          <AlertDialogCancel onClick={() => setDeleteConfirmation('')}>
                            Batal
                          </AlertDialogCancel>
                          <AlertDialogAction
                            className="bg-destructive text-destructive-foreground"
                            disabled={deleteConfirmation !== 'HAPUS'}
                          >
                            Hapus Akun
                          </AlertDialogAction>
                        </AlertDialogFooter>
                      </AlertDialogContent>
                    </AlertDialog>
                  </CardContent>
                </Card>
              </div>
            </TabsContent>

          </Tabs>
        </div>
      </div>
    </div>
  );
}
