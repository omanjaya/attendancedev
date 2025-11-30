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
import { useCreateEmployee } from '@/hooks';

const employeeSchema = z.object({
  nip: z.string().min(5, 'NIP minimal 5 karakter'),
  name: z.string().min(2, 'Nama minimal 2 karakter'),
  email: z.string().email('Email tidak valid'),
  phone: z.string().min(10, 'Nomor telepon minimal 10 digit'),
  birth_date: z.string().min(1, 'Pilih tanggal lahir'),
  gender: z.enum(['male', 'female'], { message: 'Pilih jenis kelamin' }),
  address: z.string().optional(),
  department_id: z.string().min(1, 'Pilih departemen'),
  position: z.string().min(2, 'Posisi minimal 2 karakter'),
  employment_type: z.enum(['permanent', 'contract', 'intern'], { message: 'Pilih tipe' }),
  join_date: z.string().min(1, 'Pilih tanggal bergabung'),
  emergency_name: z.string().optional(),
  emergency_phone: z.string().optional(),
  emergency_relation: z.string().optional(),
  bank_name: z.string().optional(),
  bank_account: z.string().optional(),
  bank_holder: z.string().optional(),
});

type EmployeeForm = z.infer<typeof employeeSchema>;

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

export default function EmployeeCreatePage() {
  const navigate = useNavigate();
  const [photoPreview, setPhotoPreview] = useState<string | null>(null);
  const fileInputRef = useRef<HTMLInputElement>(null);

  // Use create employee mutation
  const createEmployeeMutation = useCreateEmployee();

  const {
    register,
    handleSubmit,
    setValue,
    watch,
    formState: { errors },
  } = useForm<EmployeeForm>({
    resolver: zodResolver(employeeSchema),
    defaultValues: {
      employment_type: 'permanent',
    },
  });

  const watchGender = watch('gender');
  const watchEmploymentType = watch('employment_type');

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

  const onSubmit = async (data: EmployeeForm) => {
    // Map form data to API format
    const department = departments.find(d => d.id === data.department_id);

    const apiData = {
      name: data.name,
      email: data.email,
      phone: data.phone,
      position: data.position,
      department: department?.name || data.department_id,
      status: 'active' as const,
      join_date: data.join_date,
      address: data.address,
      birth_date: data.birth_date,
      gender: data.gender,
    };

    try {
      await createEmployeeMutation.mutateAsync(apiData);
      navigate({ to: '/employees' });
    } catch {
      // Error handled by mutation hook
    }
  };

  const generateNIP = () => {
    const year = new Date().getFullYear();
    const random = Math.floor(Math.random() * 9000) + 1000;
    setValue('nip', `EMP${year}${random}`);
  };

  return (
    <section className="relative py-16">
      {/* Background pattern - shadcnblocks style */}
      <div className="pointer-events-none absolute inset-x-0 -bottom-20 -top-20 bg-[radial-gradient(ellipse_35%_15%_at_40%_55%,hsl(var(--accent))_0%,transparent_100%)] lg:bg-[radial-gradient(ellipse_12%_20%_at_60%_45%,hsl(var(--accent))_0%,transparent_100%)]"></div>
      <div className="pointer-events-none absolute inset-x-0 -bottom-20 -top-20 bg-[radial-gradient(hsl(var(--accent-foreground)/0.1)_1px,transparent_1px)] [background-size:8px_8px] [mask-image:radial-gradient(ellipse_60%_60%_at_65%_50%,#000_0%,transparent_80%)]"></div>

      <div className="container grid w-full grid-cols-1 gap-x-16 overflow-hidden lg:grid-cols-2">
        {/* Left Side - Info */}
        <div className="w-full pb-10 md:space-y-10 md:pb-0">
          <div className="space-y-4 md:max-w-[40rem]">
            <Link
              to="/employees"
              className="inline-flex items-center text-sm text-muted-foreground hover:text-foreground mb-4"
            >
              <ArrowLeft className="h-4 w-4 mr-2" />
              Kembali ke daftar
            </Link>
            <h1 className="text-4xl font-medium lg:text-5xl">
              Tambah Karyawan Baru
            </h1>
            <p className="text-muted-foreground md:text-base lg:text-lg lg:leading-7">
              Lengkapi formulir untuk mendaftarkan karyawan baru ke dalam sistem.
            </p>
          </div>

          {/* What you get section */}
          <div className="hidden md:block">
            <div className="space-y-6 mt-16">
              <p className="text-sm font-semibold">Data yang akan tersimpan:</p>
              <div className="space-y-4">
                <div className="flex items-center space-x-2.5">
                  <Check className="text-muted-foreground size-5 shrink-0" />
                  <p className="text-sm">Informasi pribadi dan kontak</p>
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
              </div>
            </div>
          </div>
        </div>

        {/* Right Side - Form */}
        <div className="flex w-full justify-center lg:mt-2.5">
          <div className="relative flex w-full max-w-[32rem] flex-col items-center overflow-visible">
            <form onSubmit={handleSubmit(onSubmit)} className="z-10 w-full space-y-6">

              {/* Photo Upload */}
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

              {/* Personal Info - shadcnblocks contact1 style */}
              <div className="border-border bg-background w-full space-y-6 rounded-xl border px-6 py-8 shadow-sm">
                <div className="flex items-center gap-2 mb-4">
                  <User className="w-5 h-5" />
                  <h2 className="text-lg font-semibold">Informasi Pribadi</h2>
                </div>

                <div className="grid gap-4 sm:grid-cols-2">
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

                  <div>
                    <div className="mb-2.5 text-sm font-medium">
                      <label htmlFor="name">Nama Lengkap *</label>
                    </div>
                    <Input id="name" placeholder="Masukkan nama lengkap" {...register('name')} />
                    {errors.name && <p className="text-xs text-destructive mt-1">{errors.name.message}</p>}
                  </div>
                </div>

                <div className="grid gap-4 sm:grid-cols-2">
                  <div>
                    <div className="mb-2.5 text-sm font-medium">
                      <label htmlFor="email">Email *</label>
                    </div>
                    <Input id="email" type="email" placeholder="email@contoh.com" {...register('email')} />
                    {errors.email && <p className="text-xs text-destructive mt-1">{errors.email.message}</p>}
                  </div>

                  <div>
                    <div className="mb-2.5 text-sm font-medium">
                      <label htmlFor="phone">No. Telepon *</label>
                    </div>
                    <Input id="phone" placeholder="08123456789" {...register('phone')} />
                    {errors.phone && <p className="text-xs text-destructive mt-1">{errors.phone.message}</p>}
                  </div>
                </div>

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

                <div>
                  <div className="mb-2.5 text-sm font-medium">
                    <label htmlFor="address">Alamat <span className="text-muted-foreground">(Opsional)</span></label>
                  </div>
                  <Textarea id="address" placeholder="Masukkan alamat lengkap" {...register('address')} />
                </div>
              </div>

              {/* Employment Info */}
              <div className="border-border bg-background w-full space-y-6 rounded-xl border px-6 py-8 shadow-sm">
                <div className="flex items-center gap-2 mb-4">
                  <Briefcase className="w-5 h-5" />
                  <h2 className="text-lg font-semibold">Informasi Pekerjaan</h2>
                </div>

                <div className="grid gap-4 sm:grid-cols-2">
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

                  <div>
                    <div className="mb-2.5 text-sm font-medium">
                      <label htmlFor="position">Posisi/Jabatan *</label>
                    </div>
                    <Input id="position" placeholder="Contoh: Staff IT" {...register('position')} />
                    {errors.position && <p className="text-xs text-destructive mt-1">{errors.position.message}</p>}
                  </div>
                </div>

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
              </div>

              {/* Emergency Contact */}
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

              {/* Bank Info */}
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
                      Simpan Karyawan
                    </>
                  )}
                </Button>
                <Button type="button" variant="outline" onClick={() => navigate({ to: '/employees' })}>
                  Batal
                </Button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </section>
  );
}
