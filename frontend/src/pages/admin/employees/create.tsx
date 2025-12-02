import { useState, useRef } from 'react';
import { useNavigate, Link } from '@tanstack/react-router';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import {
  ArrowLeft,
  User,
  Briefcase,
  Loader2,
  Save,
  Shield,
  CreditCard,
  Upload,
  X,
  Check,
  Copy,
  CheckCheck,
  Key,
  Zap,
} from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Label } from '@/components/ui/label';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Switch } from '@/components/ui/switch';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
  DialogFooter,
} from '@/components/ui/dialog';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { useCreateEmployee } from '@/hooks';
import { useNotificationStore } from '@/stores';

// Quick mode schema - minimal required fields
const quickEmployeeSchema = z.object({
  name: z.string().min(2, 'Nama minimal 2 karakter'),
  email: z.string().email('Email tidak valid'),
  department_id: z.string().min(1, 'Pilih departemen'),
  position: z.string().min(2, 'Posisi minimal 2 karakter'),
  role: z.enum(['pegawai', 'guru', 'admin', 'kepala-sekolah'], { message: 'Pilih role' }),
});

// Full mode schema - all fields
const fullEmployeeSchema = quickEmployeeSchema.extend({
  nip: z.string().min(5, 'NIP minimal 5 karakter'),
  phone: z.string().min(10, 'Nomor telepon minimal 10 digit'),
  birth_date: z.string().min(1, 'Pilih tanggal lahir'),
  gender: z.enum(['male', 'female'], { message: 'Pilih jenis kelamin' }),
  address: z.string().optional(),
  employment_type: z.enum(['permanent', 'contract', 'intern'], { message: 'Pilih tipe' }),
  join_date: z.string().min(1, 'Pilih tanggal bergabung'),
  emergency_name: z.string().optional(),
  emergency_phone: z.string().optional(),
  emergency_relation: z.string().optional(),
  bank_name: z.string().optional(),
  bank_account: z.string().optional(),
  bank_holder: z.string().optional(),
});

type QuickEmployeeForm = z.infer<typeof quickEmployeeSchema>;
type FullEmployeeForm = z.infer<typeof fullEmployeeSchema>;

const departments = [
  { id: '1', name: 'IT & Development' },
  { id: '2', name: 'Human Resources' },
  { id: '3', name: 'Finance & Accounting' },
  { id: '4', name: 'Marketing' },
  { id: '5', name: 'Operations' },
  { id: '6', name: 'Academic' },
];

const banks = [
  { value: 'bca', label: 'Bank BCA' },
  { value: 'bni', label: 'Bank BNI' },
  { value: 'bri', label: 'Bank BRI' },
  { value: 'mandiri', label: 'Bank Mandiri' },
  { value: 'cimb', label: 'Bank CIMB Niaga' },
];

// Password generator - 8 characters, easy to type but secure
const generatePassword = (length = 8) => {
  const uppercase = 'ABCDEFGHJKLMNPQRSTUVWXYZ'; // Removed confusing letters I, O
  const lowercase = 'abcdefghijkmnpqrstuvwxyz'; // Removed confusing letters l, o
  const numbers = '23456789'; // Removed confusing numbers 0, 1
  const symbols = '@#$%&*!';
  const allChars = uppercase + lowercase + numbers + symbols;

  let password = '';
  // Ensure at least one of each type
  password += uppercase[Math.floor(Math.random() * uppercase.length)];
  password += lowercase[Math.floor(Math.random() * lowercase.length)];
  password += numbers[Math.floor(Math.random() * numbers.length)];
  password += symbols[Math.floor(Math.random() * symbols.length)];

  // Fill the rest randomly (4 more characters to make 8 total)
  for (let i = password.length; i < length; i++) {
    password += allChars[Math.floor(Math.random() * allChars.length)];
  }

  // Shuffle the password
  return password.split('').sort(() => Math.random() - 0.5).join('');
};

export default function EmployeeCreatePage() {
  const navigate = useNavigate();
  const { success } = useNotificationStore();
  const [isFullMode, setIsFullMode] = useState(false);
  const [photoPreview, setPhotoPreview] = useState<string | null>(null);
  const [generatedPassword, setGeneratedPassword] = useState<string>('');
  const [showSuccessDialog, setShowSuccessDialog] = useState(false);
  const [createdEmployeeData, setCreatedEmployeeData] = useState<{ name: string; email: string } | null>(null);
  const [passwordCopied, setPasswordCopied] = useState(false);
  const fileInputRef = useRef<HTMLInputElement>(null);

  // Use create employee mutation
  const createEmployeeMutation = useCreateEmployee();

  const {
    register,
    handleSubmit,
    setValue,
    watch,
    formState: { errors },
  } = useForm<FullEmployeeForm>({
    resolver: zodResolver(isFullMode ? fullEmployeeSchema : quickEmployeeSchema),
    defaultValues: {
      employment_type: 'permanent',
      role: 'pegawai',
    },
  });

  const watchGender = watch('gender');
  const watchEmploymentType = watch('employment_type');
  const watchRole = watch('role');

  const handlePhotoChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (file) {
      if (file.size > 2 * 1024 * 1024) {
        return;
      }
      const reader = new FileReader();
      reader.onloadend = () => setPhotoPreview(reader.result as string);
      reader.readAsDataURL(file);
    }
  };

  const onSubmit = async (data: QuickEmployeeForm | FullEmployeeForm) => {
    // Map form data to API format
    const department = departments.find(d => d.id === data.department_id);

    // Generate password
    const password = generatePassword();
    setGeneratedPassword(password);

    // Generate NIP if not in full mode or not provided
    const nip = isFullMode && 'nip' in data && data.nip
      ? data.nip
      : `EMP${new Date().getFullYear()}${Math.floor(Math.random() * 9000) + 1000}`;

    const apiData = {
      full_name: data.name, // Backend expects full_name not name
      email: data.email,
      phone: isFullMode && 'phone' in data ? data.phone : undefined, // Use undefined instead of empty string
      position: data.position,
      department: department?.name || data.department_id,
      hire_date: isFullMode && 'join_date' in data ? data.join_date : new Date().toISOString().split('T')[0],
      is_active: true,
      password: password, // Send auto-generated password
      role: data.role, // Use selected role from form
    };

    try {
      // Create employee record (user account is created automatically by backend)
      await createEmployeeMutation.mutateAsync(apiData);

      // Success - show password dialog
      setCreatedEmployeeData({
        name: data.name,
        email: data.email,
      });
      setShowSuccessDialog(true);
    } catch {
      // Creation failed - error already handled by mutation hook
    }
  };

  const generateNIP = () => {
    const year = new Date().getFullYear();
    const random = Math.floor(Math.random() * 9000) + 1000;
    setValue('nip', `EMP${year}${random}`);
  };

  const copyPassword = async () => {
    // Format full information to copy
    const fullInfo = `Informasi Akun Karyawan Baru
━━━━━━━━━━━━━━━━━━━━━━━━━━
Nama: ${createdEmployeeData?.name}
Email: ${createdEmployeeData?.email}
Password: ${generatedPassword}
━━━━━━━━━━━━━━━━━━━━━━━━━━

Silakan login menggunakan email dan password di atas.
Password harus diganti saat login pertama kali.`;

    try {
      await navigator.clipboard.writeText(fullInfo);
      setPasswordCopied(true);
      success('Berhasil!', 'Informasi akun berhasil disalin ke clipboard');
      setTimeout(() => setPasswordCopied(false), 2000);
    } catch {
      // Fallback for older browsers
      const textArea = document.createElement('textarea');
      textArea.value = fullInfo;
      document.body.appendChild(textArea);
      textArea.select();
      document.execCommand('copy');
      document.body.removeChild(textArea);
      setPasswordCopied(true);
      success('Berhasil!', 'Informasi akun berhasil disalin');
      setTimeout(() => setPasswordCopied(false), 2000);
    }
  };

  const handleSuccessDialogClose = () => {
    setShowSuccessDialog(false);
    setPasswordCopied(false);
    navigate({ to: '/admin/employees' });
  };

  return (
    <>
      <section className="relative py-8 sm:py-16">
        {/* Background pattern - shadcnblocks style */}
        <div className="pointer-events-none absolute inset-x-0 -bottom-20 -top-20 bg-[radial-gradient(ellipse_35%_15%_at_40%_55%,hsl(var(--accent))_0%,transparent_100%)] lg:bg-[radial-gradient(ellipse_12%_20%_at_60%_45%,hsl(var(--accent))_0%,transparent_100%)]"></div>
        <div className="pointer-events-none absolute inset-x-0 -bottom-20 -top-20 bg-[radial-gradient(hsl(var(--accent-foreground)/0.1)_1px,transparent_1px)] [background-size:8px_8px] [mask-image:radial-gradient(ellipse_60%_60%_at_65%_50%,#000_0%,transparent_80%)]"></div>

        <div className="container grid w-full grid-cols-1 gap-x-16 overflow-hidden lg:grid-cols-2">
          {/* Left Side - Info */}
          <div className="w-full pb-10 md:space-y-10 md:pb-0">
            <div className="space-y-4 md:max-w-[40rem]">
              <Link
                to="/admin/employees"
                className="inline-flex items-center text-sm text-muted-foreground hover:text-foreground mb-4"
              >
                <ArrowLeft className="h-4 w-4 mr-2" />
                Kembali ke daftar
              </Link>
              <h1 className="text-4xl font-medium lg:text-5xl">
                Tambah Karyawan Baru
              </h1>
              <p className="text-muted-foreground md:text-base lg:text-lg lg:leading-7">
                {isFullMode
                  ? 'Lengkapi semua informasi untuk mendaftarkan karyawan baru.'
                  : 'Buat akun dengan cepat - informasi lengkap bisa diisi nanti.'}
              </p>
            </div>

            {/* What you get section */}
            <div className="hidden md:block">
              <div className="space-y-6 mt-16">
                <p className="text-sm font-semibold">
                  {isFullMode ? 'Data yang akan tersimpan:' : 'Fitur Quick Add:'}
                </p>
                <div className="space-y-4">
                  {isFullMode ? (
                    <>
                      <div className="flex items-center space-x-2.5">
                        <Check className="text-muted-foreground size-5 shrink-0" />
                        <p className="text-sm">Informasi pribadi dan kontak lengkap</p>
                      </div>
                      <div className="flex items-center space-x-2.5">
                        <Check className="text-muted-foreground size-5 shrink-0" />
                        <p className="text-sm">Data pekerjaan dan departemen</p>
                      </div>
                      <div className="flex items-center space-x-2.5">
                        <Check className="text-muted-foreground size-5 shrink-0" />
                        <p className="text-sm">Kontak darurat untuk keamanan</p>
                      </div>
                      <div className="flex items-center space-x-2.5">
                        <Check className="text-muted-foreground size-5 shrink-0" />
                        <p className="text-sm">Informasi rekening untuk penggajian</p>
                      </div>
                    </>
                  ) : (
                    <>
                      <div className="flex items-center space-x-2.5">
                        <Zap className="text-primary size-5 shrink-0" />
                        <p className="text-sm">Buat akun dalam hitungan detik</p>
                      </div>
                      <div className="flex items-center space-x-2.5">
                        <Key className="text-primary size-5 shrink-0" />
                        <p className="text-sm">Password otomatis ter-generate & aman</p>
                      </div>
                      <div className="flex items-center space-x-2.5">
                        <Copy className="text-primary size-5 shrink-0" />
                        <p className="text-sm">Copy password untuk dibagikan</p>
                      </div>
                      <div className="flex items-center space-x-2.5">
                        <User className="text-primary size-5 shrink-0" />
                        <p className="text-sm">Data lengkap bisa diisi nanti</p>
                      </div>
                    </>
                  )}
                </div>
              </div>
            </div>
          </div>

          {/* Right Side - Form */}
          <div className="flex w-full justify-center lg:mt-2.5">
            <div className="relative flex w-full max-w-[32rem] flex-col items-center overflow-visible">
              <form onSubmit={handleSubmit(onSubmit)} className="z-10 w-full space-y-6">

                {/* Mode Toggle */}
                <div className="border-border bg-background w-full rounded-xl border px-6 py-4 shadow-sm">
                  <div className="flex items-center justify-between">
                    <div className="space-y-0.5">
                      <Label htmlFor="mode-toggle" className="text-base font-medium">
                        {isFullMode ? 'Full Form' : 'Quick Add'}
                      </Label>
                      <p className="text-xs text-muted-foreground">
                        {isFullMode ? 'Isi semua informasi lengkap' : 'Buat akun cepat - isi data esensial saja'}
                      </p>
                    </div>
                    <Switch
                      id="mode-toggle"
                      checked={isFullMode}
                      onCheckedChange={setIsFullMode}
                    />
                  </div>
                </div>

                {/* Photo Upload - Only in Full Mode */}
                {isFullMode && (
                  <div className="border-border bg-background w-full space-y-4 rounded-xl border px-6 py-6 shadow-sm">
                    <div className="flex items-center gap-4">
                      <Avatar className="h-16 w-16">
                        <AvatarImage src={photoPreview || ''} />
                        <AvatarFallback className="bg-muted">
                          <User className="h-6 w-6 text-muted-foreground" />
                        </AvatarFallback>
                      </Avatar>
                      <div className="space-y-1">
                        <input
                          ref={fileInputRef}
                          type="file"
                          accept="image/*"
                          onChange={handlePhotoChange}
                          className="hidden"
                        />
                        <div className="flex gap-2">
                          <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={() => fileInputRef.current?.click()}
                          >
                            <Upload className="h-4 w-4 mr-2" />
                            Upload
                          </Button>
                          {photoPreview && (
                            <Button
                              type="button"
                              variant="ghost"
                              size="sm"
                              onClick={() => setPhotoPreview(null)}
                            >
                              <X className="h-4 w-4" />
                            </Button>
                          )}
                        </div>
                        <p className="text-xs text-muted-foreground">JPG, PNG. Max 2MB</p>
                      </div>
                    </div>
                  </div>
                )}

                {/* Essential Info - Always Visible */}
                <div className="border-border bg-background w-full space-y-6 rounded-xl border px-6 py-8 shadow-sm">
                  <div className="flex items-center gap-2 mb-4">
                    <User className="w-5 h-5" />
                    <h2 className="text-lg font-semibold">
                      {isFullMode ? 'Informasi Pribadi' : 'Informasi Akun'}
                    </h2>
                  </div>

                  {/* NIP - Full Mode Only with Auto Generate */}
                  {isFullMode && (
                    <div>
                      <div className="mb-2.5 text-sm font-medium">
                        <label htmlFor="nip">NIP / ID Karyawan *</label>
                      </div>
                      <div className="flex gap-2">
                        <Input id="nip" placeholder="EMP2024XXXX" {...register('nip')} className="flex-1" />
                        <Button type="button" variant="outline" size="sm" onClick={generateNIP}>
                          Auto
                        </Button>
                      </div>
                      {errors.nip && <p className="text-xs text-destructive mt-1">{errors.nip.message}</p>}
                    </div>
                  )}

                  {/* Name - Always Required */}
                  <div>
                    <div className="mb-2.5 text-sm font-medium">
                      <label htmlFor="name">Nama Lengkap *</label>
                    </div>
                    <Input id="name" placeholder="Masukkan nama lengkap" {...register('name')} />
                    {errors.name && <p className="text-xs text-destructive mt-1">{errors.name.message}</p>}
                  </div>

                  {/* Email - Always Required */}
                  <div>
                    <div className="mb-2.5 text-sm font-medium">
                      <label htmlFor="email">Email *</label>
                    </div>
                    <Input id="email" type="email" placeholder="email@contoh.com" {...register('email')} />
                    {errors.email && <p className="text-xs text-destructive mt-1">{errors.email.message}</p>}
                  </div>

                  {/* Role - Always Required */}
                  <div>
                    <div className="mb-2.5 text-sm font-medium">
                      <label htmlFor="role">Role/Hak Akses *</label>
                    </div>
                    <Select value={watchRole} onValueChange={(v) => setValue('role', v as 'pegawai' | 'guru' | 'admin' | 'kepala-sekolah')}>
                      <SelectTrigger id="role">
                        <SelectValue placeholder="Pilih role" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="pegawai">Pegawai</SelectItem>
                        <SelectItem value="guru">Guru</SelectItem>
                        <SelectItem value="admin">Admin</SelectItem>
                        <SelectItem value="kepala-sekolah">Kepala Sekolah</SelectItem>
                      </SelectContent>
                    </Select>
                    {errors.role && <p className="text-xs text-destructive mt-1">{errors.role.message}</p>}
                  </div>

                  {/* Phone - Full Mode Only */}
                  {isFullMode && (
                    <div>
                      <div className="mb-2.5 text-sm font-medium">
                        <label htmlFor="phone">No. Telepon *</label>
                      </div>
                      <Input id="phone" placeholder="08123456789" {...register('phone')} />
                      {errors.phone && <p className="text-xs text-destructive mt-1">{errors.phone.message}</p>}
                    </div>
                  )}

                  {/* Birth Date & Gender - Full Mode Only */}
                  {isFullMode && (
                    <div className="grid gap-4 sm:grid-cols-2">
                      <div>
                        <div className="mb-2.5 text-sm font-medium">
                          <label htmlFor="birth_date">Tanggal Lahir *</label>
                        </div>
                        <Input id="birth_date" type="date" {...register('birth_date')} />
                        {errors.birth_date && <p className="text-xs text-destructive mt-1">{errors.birth_date.message}</p>}
                      </div>

                      <div>
                        <div className="mb-2.5 text-sm font-medium">
                          <label>Jenis Kelamin *</label>
                        </div>
                        <RadioGroup
                          value={watchGender}
                          onValueChange={(v) => setValue('gender', v as 'male' | 'female')}
                          className="flex gap-6 pt-2"
                        >
                          <div className="flex items-center space-x-2">
                            <RadioGroupItem value="male" id="male" />
                            <Label htmlFor="male" className="font-normal">Laki-laki</Label>
                          </div>
                          <div className="flex items-center space-x-2">
                            <RadioGroupItem value="female" id="female" />
                            <Label htmlFor="female" className="font-normal">Perempuan</Label>
                          </div>
                        </RadioGroup>
                        {errors.gender && <p className="text-xs text-destructive mt-1">{errors.gender.message}</p>}
                      </div>
                    </div>
                  )}

                  {/* Address - Full Mode Only */}
                  {isFullMode && (
                    <div>
                      <div className="mb-2.5 text-sm font-medium">
                        <label htmlFor="address">Alamat <span className="text-muted-foreground">(Opsional)</span></label>
                      </div>
                      <Textarea id="address" placeholder="Masukkan alamat lengkap" {...register('address')} />
                    </div>
                  )}
                </div>

                {/* Employment Info - Always Visible */}
                <div className="border-border bg-background w-full space-y-6 rounded-xl border px-6 py-8 shadow-sm">
                  <div className="flex items-center gap-2 mb-4">
                    <Briefcase className="w-5 h-5" />
                    <h2 className="text-lg font-semibold">Informasi Pekerjaan</h2>
                  </div>

                  <div className="grid gap-4 sm:grid-cols-2">
                    {/* Department - Always Required */}
                    <div>
                      <div className="mb-2.5 text-sm font-medium">
                        <label htmlFor="department">Departemen *</label>
                      </div>
                      <Select onValueChange={(v) => setValue('department_id', v)}>
                        <SelectTrigger id="department">
                          <SelectValue placeholder="Pilih departemen" />
                        </SelectTrigger>
                        <SelectContent>
                          {departments.map((d) => (
                            <SelectItem key={d.id} value={d.id}>{d.name}</SelectItem>
                          ))}
                        </SelectContent>
                      </Select>
                      {errors.department_id && <p className="text-xs text-destructive mt-1">{errors.department_id.message}</p>}
                    </div>

                    {/* Position - Always Required */}
                    <div>
                      <div className="mb-2.5 text-sm font-medium">
                        <label htmlFor="position">Posisi/Jabatan *</label>
                      </div>
                      <Input id="position" placeholder="Contoh: Staff IT" {...register('position')} />
                      {errors.position && <p className="text-xs text-destructive mt-1">{errors.position.message}</p>}
                    </div>
                  </div>

                  {/* Employment Type & Join Date - Full Mode Only */}
                  {isFullMode && (
                    <div className="grid gap-4 sm:grid-cols-2">
                      <div>
                        <div className="mb-2.5 text-sm font-medium">
                          <label>Tipe Karyawan *</label>
                        </div>
                        <Select value={watchEmploymentType} onValueChange={(v) => setValue('employment_type', v as 'permanent' | 'contract' | 'intern')}>
                          <SelectTrigger>
                            <SelectValue />
                          </SelectTrigger>
                          <SelectContent>
                            <SelectItem value="permanent">Tetap</SelectItem>
                            <SelectItem value="contract">Kontrak</SelectItem>
                            <SelectItem value="intern">Magang</SelectItem>
                          </SelectContent>
                        </Select>
                      </div>

                      <div>
                        <div className="mb-2.5 text-sm font-medium">
                          <label htmlFor="join_date">Tanggal Bergabung *</label>
                        </div>
                        <Input id="join_date" type="date" {...register('join_date')} />
                        {errors.join_date && <p className="text-xs text-destructive mt-1">{errors.join_date.message}</p>}
                      </div>
                    </div>
                  )}
                </div>

                {/* Emergency Contact - Full Mode Only */}
                {isFullMode && (
                  <div className="border-border bg-background w-full space-y-6 rounded-xl border px-6 py-8 shadow-sm">
                    <div className="flex items-center gap-2 mb-4">
                      <Shield className="w-5 h-5" />
                      <h2 className="text-lg font-semibold">Kontak Darurat</h2>
                      <span className="text-xs text-muted-foreground">(Opsional)</span>
                    </div>

                    <div className="grid gap-4 sm:grid-cols-3">
                      <div>
                        <div className="mb-2.5 text-sm font-medium">
                          <label htmlFor="emergency_name">Nama</label>
                        </div>
                        <Input id="emergency_name" placeholder="Nama kontak" {...register('emergency_name')} />
                      </div>
                      <div>
                        <div className="mb-2.5 text-sm font-medium">
                          <label htmlFor="emergency_phone">No. Telepon</label>
                        </div>
                        <Input id="emergency_phone" placeholder="08xxx" {...register('emergency_phone')} />
                      </div>
                      <div>
                        <div className="mb-2.5 text-sm font-medium">
                          <label htmlFor="emergency_relation">Hubungan</label>
                        </div>
                        <Select onValueChange={(v) => setValue('emergency_relation', v)}>
                          <SelectTrigger id="emergency_relation">
                            <SelectValue placeholder="Pilih" />
                          </SelectTrigger>
                          <SelectContent>
                            <SelectItem value="spouse">Suami/Istri</SelectItem>
                            <SelectItem value="parent">Orang Tua</SelectItem>
                            <SelectItem value="sibling">Saudara</SelectItem>
                            <SelectItem value="other">Lainnya</SelectItem>
                          </SelectContent>
                        </Select>
                      </div>
                    </div>
                  </div>
                )}

                {/* Bank Info - Full Mode Only */}
                {isFullMode && (
                  <div className="border-border bg-background w-full space-y-6 rounded-xl border px-6 py-8 shadow-sm">
                    <div className="flex items-center gap-2 mb-4">
                      <CreditCard className="w-5 h-5" />
                      <h2 className="text-lg font-semibold">Rekening Bank</h2>
                      <span className="text-xs text-muted-foreground">(Opsional)</span>
                    </div>

                    <div className="grid gap-4 sm:grid-cols-3">
                      <div>
                        <div className="mb-2.5 text-sm font-medium">
                          <label htmlFor="bank_name">Nama Bank</label>
                        </div>
                        <Select onValueChange={(v) => setValue('bank_name', v)}>
                          <SelectTrigger id="bank_name">
                            <SelectValue placeholder="Pilih bank" />
                          </SelectTrigger>
                          <SelectContent>
                            {banks.map((b) => (
                              <SelectItem key={b.value} value={b.value}>{b.label}</SelectItem>
                            ))}
                          </SelectContent>
                        </Select>
                      </div>
                      <div>
                        <div className="mb-2.5 text-sm font-medium">
                          <label htmlFor="bank_account">No. Rekening</label>
                        </div>
                        <Input id="bank_account" placeholder="Nomor rekening" {...register('bank_account')} />
                      </div>
                      <div>
                        <div className="mb-2.5 text-sm font-medium">
                          <label htmlFor="bank_holder">Nama Pemilik</label>
                        </div>
                        <Input id="bank_holder" placeholder="Nama di rekening" {...register('bank_holder')} />
                      </div>
                    </div>
                  </div>
                )}

                {/* Password Info Alert - Quick Mode */}
                {!isFullMode && (
                  <Alert>
                    <Key className="h-4 w-4" />
                    <AlertDescription>
                      Password akan otomatis di-generate secara aman dan akun user akan dibuat.
                      Password akan ditampilkan setelah proses selesai untuk Anda salin dan berikan ke karyawan.
                    </AlertDescription>
                  </Alert>
                )}

                {/* Actions */}
                <div className="flex w-full flex-col justify-end space-y-3 pt-2">
                  <Button type="submit" disabled={createEmployeeMutation.isPending}>
                    {createEmployeeMutation.isPending ? (
                      <>
                        <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                        Menyimpan...
                      </>
                    ) : (
                      <>
                        <Save className="mr-2 h-4 w-4" />
                        {isFullMode ? 'Simpan Karyawan' : 'Buat Akun'}
                      </>
                    )}
                  </Button>
                  <Button type="button" variant="outline" onClick={() => navigate({ to: '/admin/employees' })}>
                    Batal
                  </Button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </section>

      {/* Success Dialog with Password */}
      <Dialog open={showSuccessDialog} onOpenChange={setShowSuccessDialog}>
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle className="flex items-center gap-2">
              <CheckCheck className="h-5 w-5 text-success" />
              Akun Berhasil Dibuat!
            </DialogTitle>
            <DialogDescription>
              Akun untuk {createdEmployeeData?.name} telah berhasil dibuat.
            </DialogDescription>
          </DialogHeader>

          <div className="space-y-4">
            {/* Employee Info */}
            <div className="rounded-lg bg-muted/50 p-4 space-y-2">
              <div>
                <p className="text-xs text-muted-foreground">Nama</p>
                <p className="font-medium">{createdEmployeeData?.name}</p>
              </div>
              <div>
                <p className="text-xs text-muted-foreground">Email</p>
                <p className="font-medium">{createdEmployeeData?.email}</p>
              </div>
            </div>

            {/* Generated Password */}
            <div>
              <Label className="text-sm font-medium mb-2 block">
                Password (Auto-generated)
              </Label>
              <div className="flex gap-2">
                <Input
                  value={generatedPassword}
                  readOnly
                  className="font-mono text-sm bg-muted"
                />
                <Button
                  type="button"
                  variant={passwordCopied ? "default" : "outline"}
                  size="icon"
                  onClick={copyPassword}
                  className="shrink-0"
                >
                  {passwordCopied ? (
                    <CheckCheck className="h-4 w-4" />
                  ) : (
                    <Copy className="h-4 w-4" />
                  )}
                </Button>
              </div>
              <p className="text-xs text-muted-foreground mt-2">
                Klik tombol salin untuk menyalin semua informasi (nama, email, password)
              </p>
            </div>

            {/* Security Notice */}
            <Alert>
              <Shield className="h-4 w-4" />
              <AlertDescription className="text-xs">
                <strong>Keamanan:</strong> Password ini hanya ditampilkan sekali.
                Akun user sudah dibuat dan karyawan bisa langsung login menggunakan email dan password ini.
                Pastikan Anda sudah menyalin informasi lengkap sebelum menutup dialog.
              </AlertDescription>
            </Alert>
          </div>

          <DialogFooter className="flex-col sm:flex-row gap-2">
            <Button
              type="button"
              variant="outline"
              onClick={copyPassword}
              className="w-full sm:w-auto"
            >
              <Copy className="mr-2 h-4 w-4" />
              {passwordCopied ? 'Tersalin!' : 'Salin Semua Informasi'}
            </Button>
            <Button
              type="button"
              onClick={handleSuccessDialogClose}
              className="w-full sm:w-auto"
            >
              Selesai
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </>
  );
}
