import { Link, useNavigate, useParams } from '@tanstack/react-router';
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
import { LoadingState } from '@/components/states';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Separator } from '@/components/ui/separator';
import { Textarea } from '@/components/ui/textarea';
import { usePayrollEmployee, useUpdatePayrollEmployee } from '@/hooks/use-payroll';

const payrollSchema = z.object({
  bonus: z.number().min(0, 'Tidak boleh negatif'),
  other_allowances: z.number().min(0, 'Tidak boleh negatif'),
  loan_deduction: z.number().min(0, 'Tidak boleh negatif'),
  other_deductions: z.number().min(0, 'Tidak boleh negatif'),
  notes: z.string().optional(),
});

type PayrollForm = z.infer<typeof payrollSchema>;

export default function PayrollEditPage() {
  const navigate = useNavigate();
  const params = useParams({
    from: '/authenticated/payroll/$periodId/employee/$employeeId/edit',
  }) as {
    periodId: string;
    employeeId: string;
  };

  const { data: payroll, isLoading, error } = usePayrollEmployee(
    params.periodId,
    params.employeeId
  );

  const updateMutation = useUpdatePayrollEmployee();

  const {
    register,
    handleSubmit,
    watch,
    formState: { errors },
  } = useForm<PayrollForm>({
    resolver: zodResolver(payrollSchema),
    values: payroll
      ? {
          bonus: payroll.bonus,
          other_allowances: payroll.other_allowances,
          loan_deduction: payroll.loan_deduction,
          other_deductions: payroll.other_deductions,
          notes: payroll.notes || '',
        }
      : undefined,
  });

  const watchedValues = watch();

  const calculateTotals = () => {
    if (!payroll) return { earnings: 0, deductions: 0, net: 0 };

    const earnings =
      payroll.base_salary +
      payroll.position_allowance +
      payroll.transport_allowance +
      payroll.meal_allowance +
      payroll.overtime_pay +
      (watchedValues.bonus || 0) +
      (watchedValues.other_allowances || 0);

    const deductions =
      payroll.bpjs_kesehatan +
      payroll.bpjs_ketenagakerjaan +
      payroll.tax +
      payroll.late_deduction +
      payroll.absence_deduction +
      (watchedValues.loan_deduction || 0) +
      (watchedValues.other_deductions || 0);

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
    updateMutation.mutate(
      {
        periodId: params.periodId,
        employeeId: params.employeeId,
        data,
      },
      {
        onSuccess: () => {
          navigate({ to: '/payroll' });
        },
      }
    );
  };

  if (isLoading) {
    return (
      <div className="flex items-center justify-center min-h-[400px]">
        <Loader2 className="h-8 w-8 animate-spin text-primary" />
      </div>
    );
  }

  if (error || !payroll) {
    return (
      <div className="p-4 sm:p-6 max-w-4xl mx-auto">
        <Link
          to="/admin/payroll"
          className="inline-flex items-center text-sm text-muted-foreground hover:text-foreground mb-4"
        >
          <ArrowLeft className="h-4 w-4 mr-2" />
          Kembali ke daftar payroll
        </Link>
        <Card>
          <CardContent className="p-4 sm:p-6 text-center">
            <p className="text-muted-foreground">
              {error?.message || 'Data payroll tidak ditemukan'}
            </p>
          </CardContent>
        </Card>
      </div>
    );
  }

  return (
    <div className="p-4 sm:p-6 max-w-4xl mx-auto">
      {/* Header */}
      <div className="mb-6">
        <Link
          to="/admin/payroll"
          className="inline-flex items-center text-sm text-muted-foreground hover:text-foreground mb-4"
        >
          <ArrowLeft className="h-4 w-4 mr-2" />
          Kembali ke daftar payroll
        </Link>
        <h1 className="text-2xl font-bold text-foreground">Edit Payroll</h1>
        <p className="text-sm text-muted-foreground">
          {payroll.employee_name} - {payroll.employee_nip}
        </p>
      </div>

      <form onSubmit={handleSubmit(onSubmit)}>
        <div className="grid gap-6 lg:grid-cols-3">
          {/* Main Form */}
          <div className="lg:col-span-2 space-y-6">
            {/* Fixed Earnings */}
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2 text-lg text-success">
                  <TrendingUp className="h-5 w-5" />
                  Pendapatan Tetap
                </CardTitle>
              </CardHeader>
              <CardContent className="space-y-3">
                <div className="flex justify-between text-sm">
                  <span className="text-muted-foreground">Gaji Pokok</span>
                  <span className="font-medium">{formatCurrency(payroll.base_salary)}</span>
                </div>
                <div className="flex justify-between text-sm">
                  <span className="text-muted-foreground">Tunjangan Jabatan</span>
                  <span className="font-medium">{formatCurrency(payroll.position_allowance)}</span>
                </div>
                <div className="flex justify-between text-sm">
                  <span className="text-muted-foreground">Tunjangan Transport</span>
                  <span className="font-medium">{formatCurrency(payroll.transport_allowance)}</span>
                </div>
                <div className="flex justify-between text-sm">
                  <span className="text-muted-foreground">Tunjangan Makan</span>
                  <span className="font-medium">{formatCurrency(payroll.meal_allowance)}</span>
                </div>
                <div className="flex justify-between text-sm">
                  <span className="text-muted-foreground">Lembur</span>
                  <span className="font-medium">{formatCurrency(payroll.overtime_pay)}</span>
                </div>
              </CardContent>
            </Card>

            {/* Adjustable Earnings */}
            <Card>
              <CardHeader>
                <CardTitle className="text-lg">Pendapatan Tambahan</CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="grid gap-4 sm:grid-cols-2">
                  <div className="space-y-2">
                    <label className="text-sm font-medium">Bonus</label>
                    <div className="relative">
                      <span className="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-muted-foreground">
                        Rp
                      </span>
                      <Input
                        type="number"
                        className="pl-10"
                        {...register('bonus', { valueAsNumber: true })}
                      />
                    </div>
                    {errors.bonus && (
                      <p className="text-xs text-destructive">{errors.bonus.message}</p>
                    )}
                  </div>

                  <div className="space-y-2">
                    <label className="text-sm font-medium">Tunjangan Lainnya</label>
                    <div className="relative">
                      <span className="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-muted-foreground">
                        Rp
                      </span>
                      <Input
                        type="number"
                        className="pl-10"
                        {...register('other_allowances', { valueAsNumber: true })}
                      />
                    </div>
                    {errors.other_allowances && (
                      <p className="text-xs text-destructive">{errors.other_allowances.message}</p>
                    )}
                  </div>
                </div>
              </CardContent>
            </Card>

            {/* Fixed Deductions */}
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2 text-lg text-destructive">
                  <TrendingDown className="h-5 w-5" />
                  Potongan Tetap
                </CardTitle>
              </CardHeader>
              <CardContent className="space-y-3">
                <div className="flex justify-between text-sm">
                  <span className="text-muted-foreground">BPJS Kesehatan</span>
                  <span className="font-medium">{formatCurrency(payroll.bpjs_kesehatan)}</span>
                </div>
                <div className="flex justify-between text-sm">
                  <span className="text-muted-foreground">BPJS Ketenagakerjaan</span>
                  <span className="font-medium">{formatCurrency(payroll.bpjs_ketenagakerjaan)}</span>
                </div>
                <div className="flex justify-between text-sm">
                  <span className="text-muted-foreground">PPh 21</span>
                  <span className="font-medium">{formatCurrency(payroll.tax)}</span>
                </div>
                <div className="flex justify-between text-sm">
                  <span className="text-muted-foreground">Denda Terlambat</span>
                  <span className="font-medium">{formatCurrency(payroll.late_deduction)}</span>
                </div>
                <div className="flex justify-between text-sm">
                  <span className="text-muted-foreground">Potongan Tidak Hadir</span>
                  <span className="font-medium">{formatCurrency(payroll.absence_deduction)}</span>
                </div>
              </CardContent>
            </Card>

            {/* Adjustable Deductions */}
            <Card>
              <CardHeader>
                <CardTitle className="text-lg">Potongan Tambahan</CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="grid gap-4 sm:grid-cols-2">
                  <div className="space-y-2">
                    <label className="text-sm font-medium">Potongan Pinjaman</label>
                    <div className="relative">
                      <span className="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-muted-foreground">
                        Rp
                      </span>
                      <Input
                        type="number"
                        className="pl-10"
                        {...register('loan_deduction', { valueAsNumber: true })}
                      />
                    </div>
                    {errors.loan_deduction && (
                      <p className="text-xs text-destructive">{errors.loan_deduction.message}</p>
                    )}
                  </div>

                  <div className="space-y-2">
                    <label className="text-sm font-medium">Potongan Lainnya</label>
                    <div className="relative">
                      <span className="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-muted-foreground">
                        Rp
                      </span>
                      <Input
                        type="number"
                        className="pl-10"
                        {...register('other_deductions', { valueAsNumber: true })}
                      />
                    </div>
                    {errors.other_deductions && (
                      <p className="text-xs text-destructive">{errors.other_deductions.message}</p>
                    )}
                  </div>
                </div>

                <div className="space-y-2">
                  <label className="text-sm font-medium">Catatan</label>
                  <Textarea
                    placeholder="Tambahkan catatan..."
                    {...register('notes')}
                    rows={3}
                  />
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
                  <p className="text-2xl font-bold text-primary">{formatCurrency(totals.net)}</p>
                </div>

                <div className="space-y-2">
                  <Button
                    type="submit"
                    className="w-full"
                    disabled={updateMutation.isPending}
                  >
                    {updateMutation.isPending ? (
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
