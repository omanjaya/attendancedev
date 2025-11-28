import { useState } from 'react';
import { Link, useNavigate } from '@tanstack/react-router';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import {
  ArrowLeft,
  DollarSign,
  TrendingUp,
  TrendingDown,
  Loader2,
  Save,
  Calculator,
} from 'lucide-react';

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Separator } from '@/components/ui/separator';
import { useNotificationStore } from '@/stores';

const payrollSchema = z.object({
  basic_salary: z.string().min(1, 'Masukkan gaji pokok'),
  transport_allowance: z.string(),
  meal_allowance: z.string(),
  overtime_pay: z.string(),
  bonus: z.string(),
  bpjs_kesehatan: z.string(),
  bpjs_ketenagakerjaan: z.string(),
  tax: z.string(),
  late_penalty: z.string(),
  absent_deduction: z.string(),
});

type PayrollForm = z.infer<typeof payrollSchema>;

// Mock payroll data
const mockPayroll = {
  id: 1,
  employee: {
    name: 'Ahmad Fauzi',
    employee_id: 'EMP001',
    department: 'IT & Development',
  },
  period: 'November 2024',
  basic_salary: '15000000',
  transport_allowance: '1500000',
  meal_allowance: '1000000',
  overtime_pay: '900000',
  bonus: '0',
  bpjs_kesehatan: '150000',
  bpjs_ketenagakerjaan: '300000',
  tax: '920000',
  late_penalty: '100000',
  absent_deduction: '500000',
};

export default function PayrollEditPage() {
  const navigate = useNavigate();
  const { success, error: showError } = useNotificationStore();
  const [isLoading, setIsLoading] = useState(false);

  const {
    register,
    handleSubmit,
    watch,
    formState: { errors },
  } = useForm<PayrollForm>({
    resolver: zodResolver(payrollSchema),
    defaultValues: {
      basic_salary: mockPayroll.basic_salary,
      transport_allowance: mockPayroll.transport_allowance,
      meal_allowance: mockPayroll.meal_allowance,
      overtime_pay: mockPayroll.overtime_pay,
      bonus: mockPayroll.bonus,
      bpjs_kesehatan: mockPayroll.bpjs_kesehatan,
      bpjs_ketenagakerjaan: mockPayroll.bpjs_ketenagakerjaan,
      tax: mockPayroll.tax,
      late_penalty: mockPayroll.late_penalty,
      absent_deduction: mockPayroll.absent_deduction,
    },
  });

  const watchedValues = watch();

  const calculateTotals = () => {
    const earnings =
      Number(watchedValues.basic_salary || 0) +
      Number(watchedValues.transport_allowance || 0) +
      Number(watchedValues.meal_allowance || 0) +
      Number(watchedValues.overtime_pay || 0) +
      Number(watchedValues.bonus || 0);

    const deductions =
      Number(watchedValues.bpjs_kesehatan || 0) +
      Number(watchedValues.bpjs_ketenagakerjaan || 0) +
      Number(watchedValues.tax || 0) +
      Number(watchedValues.late_penalty || 0) +
      Number(watchedValues.absent_deduction || 0);

    return {
      earnings,
      deductions,
      net: earnings - deductions,
    };
  };

  const totals = calculateTotals();

  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      minimumFractionDigits: 0,
    }).format(amount);
  };

  const onSubmit = async (data: PayrollForm) => {
    setIsLoading(true);
    try {
      console.log('Updating payroll:', data);
      await new Promise(resolve => setTimeout(resolve, 1000));
      success('Berhasil', 'Data payroll berhasil diperbarui');
      navigate({ to: '/payroll' });
    } catch (err) {
      const message = err instanceof Error ? err.message : 'Gagal memperbarui data payroll';
      showError('Error', message);
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="p-6 max-w-4xl mx-auto">
      {/* Header */}
      <div className="mb-6">
        <Link
          to="/payroll"
          className="inline-flex items-center text-sm text-muted-foreground hover:text-foreground mb-4"
        >
          <ArrowLeft className="h-4 w-4 mr-2" />
          Kembali ke daftar payroll
        </Link>
        <h1 className="text-2xl font-bold text-foreground">Edit Payroll</h1>
        <p className="text-sm text-muted-foreground">
          {mockPayroll.employee.name} - {mockPayroll.period}
        </p>
      </div>

      <form onSubmit={handleSubmit(onSubmit)}>
        <div className="grid gap-6 lg:grid-cols-3">
          {/* Main Form */}
          <div className="lg:col-span-2 space-y-6">
            {/* Earnings */}
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2 text-lg text-success">
                  <TrendingUp className="h-5 w-5" />
                  Pendapatan
                </CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="grid gap-4 sm:grid-cols-2">
                  <div className="space-y-2">
                    <label className="text-sm font-medium">
                      Gaji Pokok <span className="text-destructive">*</span>
                    </label>
                    <div className="relative">
                      <span className="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-muted-foreground">
                        Rp
                      </span>
                      <Input
                        type="number"
                        className="pl-10"
                        {...register('basic_salary')}
                      />
                    </div>
                    {errors.basic_salary && (
                      <p className="text-xs text-destructive">{errors.basic_salary.message}</p>
                    )}
                  </div>

                  <div className="space-y-2">
                    <label className="text-sm font-medium">Tunjangan Transport</label>
                    <div className="relative">
                      <span className="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-muted-foreground">
                        Rp
                      </span>
                      <Input
                        type="number"
                        className="pl-10"
                        {...register('transport_allowance')}
                      />
                    </div>
                  </div>

                  <div className="space-y-2">
                    <label className="text-sm font-medium">Tunjangan Makan</label>
                    <div className="relative">
                      <span className="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-muted-foreground">
                        Rp
                      </span>
                      <Input
                        type="number"
                        className="pl-10"
                        {...register('meal_allowance')}
                      />
                    </div>
                  </div>

                  <div className="space-y-2">
                    <label className="text-sm font-medium">Lembur</label>
                    <div className="relative">
                      <span className="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-muted-foreground">
                        Rp
                      </span>
                      <Input
                        type="number"
                        className="pl-10"
                        {...register('overtime_pay')}
                      />
                    </div>
                  </div>

                  <div className="space-y-2">
                    <label className="text-sm font-medium">Bonus</label>
                    <div className="relative">
                      <span className="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-muted-foreground">
                        Rp
                      </span>
                      <Input
                        type="number"
                        className="pl-10"
                        {...register('bonus')}
                      />
                    </div>
                  </div>
                </div>
              </CardContent>
            </Card>

            {/* Deductions */}
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2 text-lg text-destructive">
                  <TrendingDown className="h-5 w-5" />
                  Potongan
                </CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="grid gap-4 sm:grid-cols-2">
                  <div className="space-y-2">
                    <label className="text-sm font-medium">BPJS Kesehatan</label>
                    <div className="relative">
                      <span className="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-muted-foreground">
                        Rp
                      </span>
                      <Input
                        type="number"
                        className="pl-10"
                        {...register('bpjs_kesehatan')}
                      />
                    </div>
                  </div>

                  <div className="space-y-2">
                    <label className="text-sm font-medium">BPJS Ketenagakerjaan</label>
                    <div className="relative">
                      <span className="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-muted-foreground">
                        Rp
                      </span>
                      <Input
                        type="number"
                        className="pl-10"
                        {...register('bpjs_ketenagakerjaan')}
                      />
                    </div>
                  </div>

                  <div className="space-y-2">
                    <label className="text-sm font-medium">PPh 21</label>
                    <div className="relative">
                      <span className="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-muted-foreground">
                        Rp
                      </span>
                      <Input
                        type="number"
                        className="pl-10"
                        {...register('tax')}
                      />
                    </div>
                  </div>

                  <div className="space-y-2">
                    <label className="text-sm font-medium">Denda Terlambat</label>
                    <div className="relative">
                      <span className="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-muted-foreground">
                        Rp
                      </span>
                      <Input
                        type="number"
                        className="pl-10"
                        {...register('late_penalty')}
                      />
                    </div>
                  </div>

                  <div className="space-y-2">
                    <label className="text-sm font-medium">Potongan Tidak Hadir</label>
                    <div className="relative">
                      <span className="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-muted-foreground">
                        Rp
                      </span>
                      <Input
                        type="number"
                        className="pl-10"
                        {...register('absent_deduction')}
                      />
                    </div>
                  </div>
                </div>
              </CardContent>
            </Card>
          </div>

          {/* Summary Sidebar */}
          <div className="space-y-6">
            {/* Summary Card */}
            <Card className="sticky top-6">
              <CardHeader>
                <CardTitle className="flex items-center gap-2 text-lg">
                  <Calculator className="h-5 w-5 text-primary" />
                  Ringkasan
                </CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="space-y-2">
                  <div className="flex justify-between text-sm">
                    <span className="text-muted-foreground">Total Pendapatan</span>
                    <span className="font-medium text-success">
                      {formatCurrency(totals.earnings)}
                    </span>
                  </div>
                  <div className="flex justify-between text-sm">
                    <span className="text-muted-foreground">Total Potongan</span>
                    <span className="font-medium text-destructive">
                      {formatCurrency(totals.deductions)}
                    </span>
                  </div>
                </div>

                <Separator />

                <div className="p-4 rounded-lg bg-primary/10">
                  <div className="flex items-center gap-2 mb-2">
                    <DollarSign className="h-5 w-5 text-primary" />
                    <span className="font-medium">Gaji Bersih</span>
                  </div>
                  <p className="text-2xl font-bold text-primary">
                    {formatCurrency(totals.net)}
                  </p>
                </div>

                <div className="space-y-2">
                  <Button type="submit" className="w-full" disabled={isLoading}>
                    {isLoading ? (
                      <>
                        <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                        Menyimpan...
                      </>
                    ) : (
                      <>
                        <Save className="mr-2 h-4 w-4" />
                        Simpan Perubahan
                      </>
                    )}
                  </Button>
                  <Button
                    type="button"
                    variant="outline"
                    className="w-full"
                    onClick={() => navigate({ to: '/payroll' })}
                  >
                    Batal
                  </Button>
                </div>
              </CardContent>
            </Card>
          </div>
        </div>
      </form>
    </div>
  );
}
