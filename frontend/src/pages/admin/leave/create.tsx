import { useState } from 'react';
import { useNavigate, Link } from '@tanstack/react-router';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import {
  ArrowLeft,
  Calendar,
  FileText,
  Loader2,
  Send,
  AlertCircle,
} from 'lucide-react';

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { LoadingState } from '@/components/states';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Alert, AlertDescription } from '@/components/ui/alert';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { useNotificationStore } from '@/stores';

const leaveSchema = z.object({
  type: z.string().min(1, 'Pilih jenis cuti'),
  start_date: z.string().min(1, 'Pilih tanggal mulai'),
  end_date: z.string().min(1, 'Pilih tanggal selesai'),
  reason: z.string().min(10, 'Alasan minimal 10 karakter'),
});

type LeaveForm = z.infer<typeof leaveSchema>;

const leaveTypes = [
  { value: 'annual', label: 'Cuti Tahunan', balance: 12 },
  { value: 'sick', label: 'Cuti Sakit', balance: 14 },
  { value: 'maternity', label: 'Cuti Melahirkan', balance: 90 },
  { value: 'paternity', label: 'Cuti Ayah', balance: 2 },
  { value: 'personal', label: 'Cuti Pribadi', balance: 3 },
  { value: 'unpaid', label: 'Cuti Tanpa Gaji', balance: null },
];

export default function LeaveCreatePage() {
  const navigate = useNavigate();
  const { success, error: showError } = useNotificationStore();
  const [isLoading, setIsLoading] = useState(false);
  const [selectedType, setSelectedType] = useState<string>('');

  const {
    register,
    handleSubmit,
    setValue,
    watch,
    formState: { errors },
  } = useForm<LeaveForm>({
    resolver: zodResolver(leaveSchema),
  });

  const startDate = watch('start_date');
  const endDate = watch('end_date');

  const calculateDays = () => {
    if (!startDate || !endDate) return 0;
    const start = new Date(startDate);
    const end = new Date(endDate);
    const diff = Math.ceil((end.getTime() - start.getTime()) / (1000 * 60 * 60 * 24)) + 1;
    return diff > 0 ? diff : 0;
  };

  const selectedLeaveType = leaveTypes.find(t => t.value === selectedType);

  const onSubmit = async (data: LeaveForm) => {
    setIsLoading(true);
    try {
      console.log('Submitting leave request:', data);
      await new Promise(resolve => setTimeout(resolve, 1000));
      success('Berhasil', 'Pengajuan cuti berhasil dikirim');
      navigate({ to: '/leave' });
    } catch (err) {
      const message = err instanceof Error ? err.message : 'Gagal mengajukan cuti';
      showError('Error', message);
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="p-4 sm:p-6 max-w-3xl mx-auto">
      {/* Header */}
      <div className="mb-6">
        <Link
          to="/admin/leave"
          className="inline-flex items-center text-sm text-muted-foreground hover:text-foreground mb-4"
        >
          <ArrowLeft className="h-4 w-4 mr-2" />
          Kembali ke daftar cuti
        </Link>
        <h1 className="text-2xl font-bold text-foreground">Ajukan Cuti</h1>
        <p className="text-sm text-muted-foreground">
          Isi form pengajuan cuti dengan lengkap
        </p>
      </div>

      <form onSubmit={handleSubmit(onSubmit)}>
        <div className="grid gap-6">
          {/* Leave Balance Info */}
          {selectedLeaveType && selectedLeaveType.balance !== null && (
            <Alert>
              <AlertCircle className="h-4 w-4" />
              <AlertDescription>
                Sisa cuti {selectedLeaveType.label}: <strong>{selectedLeaveType.balance} hari</strong>
              </AlertDescription>
            </Alert>
          )}

          {/* Leave Type */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-lg">
                <FileText className="h-5 w-5 text-primary" />
                Jenis Cuti
              </CardTitle>
            </CardHeader>
            <CardContent>
              <Select
                value={selectedType}
                onValueChange={(value) => {
                  setSelectedType(value);
                  setValue('type', value);
                }}
              >
                <SelectTrigger>
                  <SelectValue placeholder="Pilih jenis cuti" />
                </SelectTrigger>
                <SelectContent>
                  {leaveTypes.map((type) => (
                    <SelectItem key={type.value} value={type.value}>
                      <div className="flex items-center justify-between w-full">
                        <span>{type.label}</span>
                        {type.balance !== null && (
                          <span className="text-xs text-muted-foreground ml-4">
                            Sisa: {type.balance} hari
                          </span>
                        )}
                      </div>
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              {errors.type && (
                <p className="text-xs text-destructive mt-2">{errors.type.message}</p>
              )}
            </CardContent>
          </Card>

          {/* Date Range */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-lg">
                <Calendar className="h-5 w-5 text-primary" />
                Tanggal Cuti
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="grid gap-4 sm:grid-cols-2">
                <div className="space-y-2">
                  <label className="text-sm font-medium">
                    Tanggal Mulai <span className="text-destructive">*</span>
                  </label>
                  <Input type="date" {...register('start_date')} />
                  {errors.start_date && (
                    <p className="text-xs text-destructive">{errors.start_date.message}</p>
                  )}
                </div>

                <div className="space-y-2">
                  <label className="text-sm font-medium">
                    Tanggal Selesai <span className="text-destructive">*</span>
                  </label>
                  <Input type="date" {...register('end_date')} />
                  {errors.end_date && (
                    <p className="text-xs text-destructive">{errors.end_date.message}</p>
                  )}
                </div>
              </div>

              {calculateDays() > 0 && (
                <div className="mt-4 p-3 rounded-lg bg-primary/10 text-primary">
                  <p className="text-sm font-medium">
                    Total: {calculateDays()} hari kerja
                  </p>
                </div>
              )}
            </CardContent>
          </Card>

          {/* Reason */}
          <Card>
            <CardHeader>
              <CardTitle className="text-lg">Alasan Cuti</CardTitle>
            </CardHeader>
            <CardContent>
              <Textarea
                placeholder="Jelaskan alasan pengajuan cuti Anda..."
                className="min-h-[120px]"
                {...register('reason')}
              />
              {errors.reason && (
                <p className="text-xs text-destructive mt-2">{errors.reason.message}</p>
              )}
            </CardContent>
          </Card>

          {/* Actions */}
          <div className="flex justify-end gap-3">
            <Button
              type="button"
              variant="outline"
              onClick={() => navigate({ to: '/leave' })}
            >
              Batal
            </Button>
            <Button type="submit" disabled={isLoading}>
              {isLoading ? (
                <>
                  <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                  Mengirim...
                </>
              ) : (
                <>
                  <Send className="mr-2 h-4 w-4" />
                  Kirim Pengajuan
                </>
              )}
            </Button>
          </div>
        </div>
      </form>
    </div>
  );
}
