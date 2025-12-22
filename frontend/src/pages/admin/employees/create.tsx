import { useState, useMemo, useCallback, useRef } from 'react';
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
  Check,
  Copy,
  CheckCheck,
  Key,
  Zap,
  BookOpen,
  AlertCircle,
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
import { useLocations } from '@/hooks/use-locations';
import type { Location } from '@/types/location';
import { useNotificationStore } from '@/stores';
import { useEmployeeTypes, useDepartments, usePositions, useSubjects } from '@/hooks/use-master-data';
import { checkUnique } from '@/lib/api/employees';

// Quick mode schema - minimal required fields
const quickEmployeeSchema = z.object({
  name: z.string().min(2, 'Nama minimal 2 karakter'),
  email: z.string().email('Email tidak valid'),
  employee_type_id: z.string().min(1, 'Pilih jenis pegawai'),
  // Dynamic fields based on employee type
  subject_id: z.string().optional(), // For teachers (flexible schedule)
  department_id: z.string().optional(), // For staff (fixed schedule)
  position_id: z.string().optional(), // For staff (fixed schedule)
  position: z.string().optional(), // Fallback text field
  role: z.enum(['pegawai', 'guru', 'admin', 'kepala-sekolah'], { message: 'Pilih role' }),
  location_id: z.string().min(1, 'Pilih lokasi'),
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
  const [generatedPassword, setGeneratedPassword] = useState<string>('');
  const [showSuccessDialog, setShowSuccessDialog] = useState(false);
  const [createdEmployeeData, setCreatedEmployeeData] = useState<{ name: string; email: string } | null>(null);
  const [passwordCopied, setPasswordCopied] = useState(false);

  // Async validation states
  const [emailValidation, setEmailValidation] = useState<{
    checking: boolean;
    message: string;
    isValid: boolean | null;
  }>({ checking: false, message: '', isValid: null });

  const [nipValidation, setNipValidation] = useState<{
    checking: boolean;
    message: string;
    isValid: boolean | null;
  }>({ checking: false, message: '', isValid: null });

  // Debounce timers
  const emailDebounceTimer = useRef<ReturnType<typeof setTimeout> | null>(null);
  const nipDebounceTimer = useRef<ReturnType<typeof setTimeout> | null>(null);

  // Use create employee mutation
  const createEmployeeMutation = useCreateEmployee();

  // Fetch locations for dropdown
  const { data: locations = [] } = useLocations();

  // Employee Types hook
  const { data: employeeTypesData } = useEmployeeTypes({ is_active: true });
  const employeeTypes = employeeTypesData?.data || [];

  // Departments hook (Unit Kerja)
  const { data: departmentsData } = useDepartments({ is_active: true });
  const departments = departmentsData?.data || [];

  // Positions hook (Jabatan)
  const { data: positionsData } = usePositions({ is_active: true });
  const positions = positionsData?.data || [];

  // Subjects hook (Mata Pelajaran)
  const { data: subjectsData } = useSubjects({ is_active: true });
  const subjects = subjectsData?.data || [];

  const {
    register,
    handleSubmit,
    setValue,
    watch,
    formState: { errors },
  } = useForm<FullEmployeeForm | QuickEmployeeForm>({
    resolver: zodResolver(isFullMode ? fullEmployeeSchema : quickEmployeeSchema),
    defaultValues: {
      employment_type: 'permanent',
      role: 'pegawai',
    },
  });

  const watchGender = watch('gender');
  const watchEmploymentType = watch('employment_type');
  const watchRole = watch('role');
  const watchEmployeeTypeId = watch('employee_type_id');

  // Get selected employee type to determine schedule_mode
  const selectedEmployeeType = useMemo(() => {
    return employeeTypes.find((t: any) => t.id === watchEmployeeTypeId);
  }, [employeeTypes, watchEmployeeTypeId]);

  // Determine if this is a teacher type (flexible schedule OR name contains 'guru') or staff type
  const isTeacherType = selectedEmployeeType?.schedule_mode === 'flexible' ||
    (selectedEmployeeType?.name?.toLowerCase().includes('guru') ?? false);

  // Staff type is explicitly NOT a teacher type, but has a selected type
  const isStaffType = !!selectedEmployeeType && !isTeacherType;

  // Debounced email validation
  const validateEmailUnique = useCallback((email: string) => {
    if (!email || email.length < 3) {
      setEmailValidation({ checking: false, message: '', isValid: null });
      return;
    }

    // Clear previous timer
    if (emailDebounceTimer.current) {
      clearTimeout(emailDebounceTimer.current);
    }

    setEmailValidation({ checking: true, message: 'Memeriksa email...', isValid: null });

    // Debounce 500ms
    emailDebounceTimer.current = setTimeout(async () => {
      try {
        const result = await checkUnique({ field: 'email', value: email });
        setEmailValidation({
          checking: false,
          message: result.message,
          isValid: result.is_available,
        });
      } catch (error) {
        setEmailValidation({
          checking: false,
          message: 'Gagal memeriksa email',
          isValid: null,
        });
      }
    }, 500);
  }, []);

  // Debounced NIP validation
  const validateNipUnique = useCallback((nip: string) => {
    if (!nip || nip.length < 3) {
      setNipValidation({ checking: false, message: '', isValid: null });
      return;
    }

    // Clear previous timer
    if (nipDebounceTimer.current) {
      clearTimeout(nipDebounceTimer.current);
    }

    setNipValidation({ checking: true, message: 'Memeriksa NIP...', isValid: null });

    // Debounce 500ms
    nipDebounceTimer.current = setTimeout(async () => {
      try {
        const result = await checkUnique({ field: 'employee_code', value: nip });
        setNipValidation({
          checking: false,
          message: result.message,
          isValid: result.is_available,
        });
      } catch (error) {
        setNipValidation({
          checking: false,
          message: 'Gagal memeriksa NIP',
          isValid: null,
        });
      }
    }, 500);
  }, []);

  const onSubmit = async (data: QuickEmployeeForm | FullEmployeeForm) => {
    // Check async validations before submit
    if (emailValidation.isValid === false) {
      return; // Prevent submit if email is not available
    }
    if (isFullMode && 'nip' in data && data.nip && nipValidation.isValid === false) {
      return; // Prevent submit if NIP is not available
    }
    // Map form data to API format
    // Generate password
    const password = generatePassword();
    setGeneratedPassword(password);

    // Generate NIP if not in full mode or not provided
    const nip = isFullMode && 'nip' in data && data.nip
      ? data.nip
      : `EMP${new Date().getFullYear()}${Math.floor(Math.random() * 9000) + 1000}`;

    // Get schedule mode from selected type to condition fields
    const typeInfo = employeeTypes.find((t: any) => t.id === data.employee_type_id);
    const isTeacher = typeInfo?.schedule_mode === 'flexible' ||
      (typeInfo?.name?.toLowerCase().includes('guru') ?? false);

    const apiData = {
      employee_code: nip, // Send NIP/Employee ID
      full_name: data.name, // Backend expects full_name not name
      email: data.email,
      phone: isFullMode && 'phone' in data ? data.phone : undefined,
      position: data.position || undefined,
      employee_type_id: data.employee_type_id,

      // Dynamic fields based on CONFIRMED employee type
      // Ensure we send undefined (not empty string) and only relevant fields
      subject_id: isTeacher && data.subject_id && data.subject_id.length > 0 ? data.subject_id : undefined,
      department_id: !isTeacher && data.department_id && data.department_id.length > 0 ? data.department_id : undefined,
      position_id: !isTeacher && data.position_id && data.position_id.length > 0 ? data.position_id : undefined,

      hire_date: isFullMode && 'join_date' in data ? data.join_date : new Date().toISOString().split('T')[0],
      is_active: true,
      password: password, // Send auto-generated password
      role: data.role, // Use selected role from form
      location_id: data.location_id && data.location_id.length > 0 ? data.location_id : undefined, // Ensure valid UUID or undefined
    };

    try {
      // Create employee record (user account is created automatically by backend)
      await createEmployeeMutation.mutateAsync(apiData as any);

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
    const newNip = `EMP${year}${random}`;
    setValue('nip', newNip);
    // Trigger validation for the generated NIP
    validateNipUnique(newNip);
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
                        <div className="relative flex-1">
                          <Input
                            id="nip"
                            placeholder="EMP2024XXXX"
                            {...register('nip')}
                            onChange={(e) => {
                              register('nip').onChange(e);
                              validateNipUnique(e.target.value);
                            }}
                            className={
                              nipValidation.isValid === false
                                ? 'border-destructive pr-10'
                                : nipValidation.isValid === true
                                ? 'border-green-500 pr-10'
                                : ''
                            }
                          />
                          {nipValidation.checking && (
                            <div className="absolute right-3 top-1/2 -translate-y-1/2">
                              <Loader2 className="h-4 w-4 animate-spin text-muted-foreground" />
                            </div>
                          )}
                          {!nipValidation.checking && nipValidation.isValid === true && (
                            <div className="absolute right-3 top-1/2 -translate-y-1/2">
                              <Check className="h-4 w-4 text-green-500" />
                            </div>
                          )}
                          {!nipValidation.checking && nipValidation.isValid === false && (
                            <div className="absolute right-3 top-1/2 -translate-y-1/2">
                              <AlertCircle className="h-4 w-4 text-destructive" />
                            </div>
                          )}
                        </div>
                        <Button type="button" variant="outline" size="sm" onClick={generateNIP}>
                          Auto
                        </Button>
                      </div>
                      {(errors as any).nip && <p className="text-xs text-destructive mt-1">{(errors as any).nip.message}</p>}
                      {!(errors as any).nip && nipValidation.message && (
                        <p
                          className={`text-xs mt-1 ${
                            nipValidation.isValid === false
                              ? 'text-destructive'
                              : nipValidation.isValid === true
                              ? 'text-green-600'
                              : 'text-muted-foreground'
                          }`}
                        >
                          {nipValidation.message}
                        </p>
                      )}
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
                    <div className="relative">
                      <Input
                        id="email"
                        type="email"
                        placeholder="email@contoh.com"
                        {...register('email')}
                        onChange={(e) => {
                          register('email').onChange(e);
                          validateEmailUnique(e.target.value);
                        }}
                        className={
                          emailValidation.isValid === false
                            ? 'border-destructive pr-10'
                            : emailValidation.isValid === true
                            ? 'border-green-500 pr-10'
                            : ''
                        }
                      />
                      {emailValidation.checking && (
                        <div className="absolute right-3 top-1/2 -translate-y-1/2">
                          <Loader2 className="h-4 w-4 animate-spin text-muted-foreground" />
                        </div>
                      )}
                      {!emailValidation.checking && emailValidation.isValid === true && (
                        <div className="absolute right-3 top-1/2 -translate-y-1/2">
                          <Check className="h-4 w-4 text-green-500" />
                        </div>
                      )}
                      {!emailValidation.checking && emailValidation.isValid === false && (
                        <div className="absolute right-3 top-1/2 -translate-y-1/2">
                          <AlertCircle className="h-4 w-4 text-destructive" />
                        </div>
                      )}
                    </div>
                    {errors.email && <p className="text-xs text-destructive mt-1">{errors.email.message}</p>}
                    {!errors.email && emailValidation.message && (
                      <p
                        className={`text-xs mt-1 ${
                          emailValidation.isValid === false
                            ? 'text-destructive'
                            : emailValidation.isValid === true
                            ? 'text-green-600'
                            : 'text-muted-foreground'
                        }`}
                      >
                        {emailValidation.message}
                      </p>
                    )}
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
                      <Input id="phone" placeholder="08123456789" {...register('phone' as any)} />
                      {(errors as any).phone && <p className="text-xs text-destructive mt-1">{(errors as any).phone.message}</p>}
                    </div>
                  )}

                  {/* Birth Date & Gender - Full Mode Only */}
                  {isFullMode && (
                    <div className="grid gap-4 sm:grid-cols-2">
                      <div>
                        <div className="mb-2.5 text-sm font-medium">
                          <label htmlFor="birth_date">Tanggal Lahir *</label>
                        </div>
                        <Input id="birth_date" type="date" {...register('birth_date' as any)} />
                        {(errors as any).birth_date && <p className="text-xs text-destructive mt-1">{(errors as any).birth_date.message}</p>}
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
                        {(errors as any).gender && <p className="text-xs text-destructive mt-1">{(errors as any).gender.message}</p>}
                      </div>
                    </div>
                  )}

                  {/* Address - Full Mode Only */}
                  {isFullMode && (
                    <div>
                      <div className="mb-2.5 text-sm font-medium">
                        <label htmlFor="address">Alamat <span className="text-muted-foreground">(Opsional)</span></label>
                      </div>
                      <Textarea id="address" placeholder="Masukkan alamat lengkap" {...register('address' as any)} />
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
                    {/* Employee Type - Always Required */}
                    <div>
                      <div className="mb-2.5 text-sm font-medium">
                        <label htmlFor="employee_type">Jenis Pegawai *</label>
                      </div>
                      <Select onValueChange={(v) => setValue('employee_type_id', v)}>
                        <SelectTrigger id="employee_type">
                          <SelectValue placeholder="Pilih jenis pegawai" />
                        </SelectTrigger>
                        <SelectContent>
                          {employeeTypes.map((type: any) => (
                            <SelectItem key={type.id} value={type.id}>
                              <div className="flex items-center gap-2">
                                <span>{type.name}</span>
                                <span className="text-xs text-muted-foreground">
                                  ({type.schedule_mode === 'flexible' ? 'Fleksibel' : 'Tetap'})
                                </span>
                              </div>
                            </SelectItem>
                          ))}
                        </SelectContent>
                      </Select>
                      {errors.employee_type_id && <p className="text-xs text-destructive mt-1">{errors.employee_type_id.message}</p>}
                      {selectedEmployeeType && (
                        <p className="text-xs text-muted-foreground mt-1">
                          Mode jadwal: {selectedEmployeeType.schedule_mode === 'flexible' ? 'Fleksibel (Guru)' : 'Tetap (Staff)'}
                        </p>
                      )}
                    </div>

                    {/* Dynamic Fields based on Employee Type */}
                    {/* For Teachers (flexible schedule) - Show Mata Pelajaran */}
                    {isTeacherType && (
                      <div>
                        <div className="mb-2.5 text-sm font-medium">
                          <label htmlFor="subject">
                            <BookOpen className="w-4 h-4 inline mr-1" />
                            Mata Pelajaran *
                          </label>
                        </div>
                        <Select onValueChange={(v) => setValue('subject_id', v)}>
                          <SelectTrigger id="subject">
                            <SelectValue placeholder="Pilih mata pelajaran" />
                          </SelectTrigger>
                          <SelectContent>
                            {subjects.map((s: any) => (
                              <SelectItem key={s.id} value={s.id}>{s.name}</SelectItem>
                            ))}
                          </SelectContent>
                        </Select>
                        <p className="text-xs text-muted-foreground mt-1">
                          Mata pelajaran utama yang diajarkan
                        </p>
                      </div>
                    )}

                    {/* For Staff (fixed schedule) - Show Unit Kerja */}
                    {isStaffType && (
                      <div>
                        <div className="mb-2.5 text-sm font-medium">
                          <label htmlFor="department">Unit Kerja</label>
                        </div>
                        <Select onValueChange={(v) => setValue('department_id', v)}>
                          <SelectTrigger id="department">
                            <SelectValue placeholder="Pilih unit kerja" />
                          </SelectTrigger>
                          <SelectContent>
                            {departments.map((d: any) => (
                              <SelectItem key={d.id} value={d.id}>{d.name}</SelectItem>
                            ))}
                          </SelectContent>
                        </Select>
                        {errors.department_id && <p className="text-xs text-destructive mt-1">{errors.department_id.message}</p>}
                      </div>
                    )}

                    {/* For Staff (fixed schedule) - Show Jabatan from dropdown */}
                    {isStaffType && (
                      <div>
                        <div className="mb-2.5 text-sm font-medium">
                          <label htmlFor="position_dropdown">Jabatan</label>
                        </div>
                        <Select onValueChange={(v) => setValue('position_id', v)}>
                          <SelectTrigger id="position_dropdown">
                            <SelectValue placeholder="Pilih jabatan" />
                          </SelectTrigger>
                          <SelectContent>
                            {positions.map((p: any) => (
                              <SelectItem key={p.id} value={p.id}>{p.name}</SelectItem>
                            ))}
                          </SelectContent>
                        </Select>
                      </div>
                    )}

                    {/* Fallback Position field if no employee type selected */}
                    {!isTeacherType && !isStaffType && (
                      <div>
                        <div className="mb-2.5 text-sm font-medium">
                          <label htmlFor="position">Posisi/Jabatan</label>
                        </div>
                        <Input id="position" placeholder="Pilih jenis pegawai terlebih dahulu" {...register('position')} disabled />
                        <p className="text-xs text-muted-foreground mt-1">
                          Pilih jenis pegawai untuk melihat opsi posisi
                        </p>
                      </div>
                    )}
                  </div>

                  {/* Location - Always Required */}
                  <div>
                    <div className="mb-2.5 text-sm font-medium">
                      <label htmlFor="location">Lokasi *</label>
                    </div>
                    <Select onValueChange={(v) => setValue('location_id', v)}>
                      <SelectTrigger id="location">
                        <SelectValue placeholder="Pilih lokasi" />
                      </SelectTrigger>
                      <SelectContent>
                        {locations.map((location: Location) => (
                          <SelectItem key={location.id} value={location.id}>
                            {location.name}
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                    {errors.location_id && <p className="text-xs text-destructive mt-1">{errors.location_id.message}</p>}
                    <p className="text-xs text-muted-foreground mt-1">
                      Lokasi akan digunakan untuk validasi GPS saat check-in
                    </p>
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
                        <Input id="join_date" type="date" {...register('join_date' as any)} />
                        {(errors as any).join_date && <p className="text-xs text-destructive mt-1">{(errors as any).join_date.message}</p>}
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
                        <Input id="emergency_name" placeholder="Nama kontak" {...register('emergency_name' as any)} />
                      </div>
                      <div>
                        <div className="mb-2.5 text-sm font-medium">
                          <label htmlFor="emergency_phone">No. Telepon</label>
                        </div>
                        <Input id="emergency_phone" placeholder="08xxx" {...register('emergency_phone' as any)} />
                      </div>
                      <div>
                        <div className="mb-2.5 text-sm font-medium">
                          <label htmlFor="emergency_relation">Hubungan</label>
                        </div>
                        <Select onValueChange={(v) => setValue('emergency_relation' as any, v)}>
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
                        <Select onValueChange={(v) => setValue('bank_name' as any, v)}>
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
                        <Input id="bank_account" placeholder="Nomor rekening" {...register('bank_account' as any)} />
                      </div>
                      <div>
                        <div className="mb-2.5 text-sm font-medium">
                          <label htmlFor="bank_holder">Nama Pemilik</label>
                        </div>
                        <Input id="bank_holder" placeholder="Nama di rekening" {...register('bank_holder' as any)} />
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
