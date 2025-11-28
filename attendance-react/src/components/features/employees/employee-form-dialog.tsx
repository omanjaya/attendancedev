import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { Loader2 } from 'lucide-react';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import type { Employee, EmployeeFormData } from '@/types';

// Form validation schema
const employeeSchema = z.object({
  name: z.string().min(2, 'Nama minimal 2 karakter'),
  email: z.string().email('Email tidak valid'),
  phone: z.string().optional(),
  position: z.string().min(1, 'Jabatan harus diisi'),
  department: z.string().min(1, 'Departemen harus diisi'),
  status: z.enum(['active', 'inactive', 'on_leave', 'terminated']),
  join_date: z.string().min(1, 'Tanggal bergabung harus diisi'),
  address: z.string().optional(),
  birth_date: z.string().optional(),
  gender: z.enum(['male', 'female']).optional(),
});

type FormData = z.infer<typeof employeeSchema>;

interface EmployeeFormDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  employee?: Employee | null;
  onSubmit: (data: EmployeeFormData) => Promise<void>;
  isLoading?: boolean;
}

const departments = [
  'IT',
  'HR',
  'Finance',
  'Marketing',
  'Sales',
  'Operations',
  'Legal',
  'R&D',
];

const positions = [
  'Staff',
  'Senior Staff',
  'Supervisor',
  'Manager',
  'Senior Manager',
  'Director',
  'VP',
  'C-Level',
];

export function EmployeeFormDialog({
  open,
  onOpenChange,
  employee,
  onSubmit,
  isLoading = false,
}: EmployeeFormDialogProps) {
  const isEdit = !!employee;

  const {
    register,
    handleSubmit,
    setValue,
    watch,
    reset,
    formState: { errors },
  } = useForm<FormData>({
    resolver: zodResolver(employeeSchema),
    defaultValues: {
      name: employee?.name || '',
      email: employee?.email || '',
      phone: employee?.phone || '',
      position: employee?.position || '',
      department: employee?.department || '',
      status: employee?.status || 'active',
      join_date: employee?.join_date || new Date().toISOString().split('T')[0],
      address: employee?.address || '',
      birth_date: employee?.birth_date || '',
      gender: employee?.gender,
    },
  });

  const handleFormSubmit = async (data: FormData) => {
    await onSubmit(data as EmployeeFormData);
    reset();
    onOpenChange(false);
  };

  const handleClose = () => {
    reset();
    onOpenChange(false);
  };

  return (
    <Dialog open={open} onOpenChange={handleClose}>
      <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle>
            {isEdit ? 'Edit Karyawan' : 'Tambah Karyawan Baru'}
          </DialogTitle>
          <DialogDescription>
            {isEdit
              ? 'Perbarui informasi karyawan di bawah ini.'
              : 'Isi informasi karyawan baru di bawah ini.'}
          </DialogDescription>
        </DialogHeader>

        <form onSubmit={handleSubmit(handleFormSubmit)} className="space-y-6">
          {/* Personal Info */}
          <div className="space-y-4">
            <h3 className="text-sm font-medium text-muted-foreground">
              Informasi Pribadi
            </h3>
            <div className="grid gap-4 sm:grid-cols-2">
              <div className="space-y-2">
                <Label htmlFor="name">Nama Lengkap *</Label>
                <Input
                  id="name"
                  placeholder="Masukkan nama lengkap"
                  {...register('name')}
                />
                {errors.name && (
                  <p className="text-xs text-destructive">{errors.name.message}</p>
                )}
              </div>
              <div className="space-y-2">
                <Label htmlFor="email">Email *</Label>
                <Input
                  id="email"
                  type="email"
                  placeholder="nama@email.com"
                  {...register('email')}
                />
                {errors.email && (
                  <p className="text-xs text-destructive">{errors.email.message}</p>
                )}
              </div>
              <div className="space-y-2">
                <Label htmlFor="phone">No. Telepon</Label>
                <Input
                  id="phone"
                  placeholder="08xxxxxxxxxx"
                  {...register('phone')}
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="gender">Jenis Kelamin</Label>
                <Select
                  value={watch('gender')}
                  onValueChange={(value) => setValue('gender', value as 'male' | 'female')}
                >
                  <SelectTrigger>
                    <SelectValue placeholder="Pilih jenis kelamin" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="male">Laki-laki</SelectItem>
                    <SelectItem value="female">Perempuan</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div className="space-y-2">
                <Label htmlFor="birth_date">Tanggal Lahir</Label>
                <Input
                  id="birth_date"
                  type="date"
                  {...register('birth_date')}
                />
              </div>
              <div className="space-y-2 sm:col-span-2">
                <Label htmlFor="address">Alamat</Label>
                <Textarea
                  id="address"
                  placeholder="Masukkan alamat lengkap"
                  rows={2}
                  {...register('address')}
                />
              </div>
            </div>
          </div>

          {/* Employment Info */}
          <div className="space-y-4">
            <h3 className="text-sm font-medium text-muted-foreground">
              Informasi Pekerjaan
            </h3>
            <div className="grid gap-4 sm:grid-cols-2">
              <div className="space-y-2">
                <Label htmlFor="department">Departemen *</Label>
                <Select
                  value={watch('department')}
                  onValueChange={(value) => setValue('department', value)}
                >
                  <SelectTrigger>
                    <SelectValue placeholder="Pilih departemen" />
                  </SelectTrigger>
                  <SelectContent>
                    {departments.map((dept) => (
                      <SelectItem key={dept} value={dept}>
                        {dept}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
                {errors.department && (
                  <p className="text-xs text-destructive">{errors.department.message}</p>
                )}
              </div>
              <div className="space-y-2">
                <Label htmlFor="position">Jabatan *</Label>
                <Select
                  value={watch('position')}
                  onValueChange={(value) => setValue('position', value)}
                >
                  <SelectTrigger>
                    <SelectValue placeholder="Pilih jabatan" />
                  </SelectTrigger>
                  <SelectContent>
                    {positions.map((pos) => (
                      <SelectItem key={pos} value={pos}>
                        {pos}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
                {errors.position && (
                  <p className="text-xs text-destructive">{errors.position.message}</p>
                )}
              </div>
              <div className="space-y-2">
                <Label htmlFor="join_date">Tanggal Bergabung *</Label>
                <Input
                  id="join_date"
                  type="date"
                  {...register('join_date')}
                />
                {errors.join_date && (
                  <p className="text-xs text-destructive">{errors.join_date.message}</p>
                )}
              </div>
              <div className="space-y-2">
                <Label htmlFor="status">Status *</Label>
                <Select
                  value={watch('status')}
                  onValueChange={(value) =>
                    setValue('status', value as FormData['status'])
                  }
                >
                  <SelectTrigger>
                    <SelectValue placeholder="Pilih status" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="active">Aktif</SelectItem>
                    <SelectItem value="inactive">Nonaktif</SelectItem>
                    <SelectItem value="on_leave">Cuti</SelectItem>
                    <SelectItem value="terminated">Berhenti</SelectItem>
                  </SelectContent>
                </Select>
                {errors.status && (
                  <p className="text-xs text-destructive">{errors.status.message}</p>
                )}
              </div>
            </div>
          </div>

          <DialogFooter>
            <Button type="button" variant="outline" onClick={handleClose}>
              Batal
            </Button>
            <Button type="submit" disabled={isLoading}>
              {isLoading && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
              {isEdit ? 'Simpan Perubahan' : 'Tambah Karyawan'}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}
