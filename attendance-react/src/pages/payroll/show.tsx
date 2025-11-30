import { Link, useParams } from '@tanstack/react-router';
import {
  ArrowLeft,
  Download,
  Printer,
  DollarSign,
  Calendar,
  User,
  Building,
  Clock,
  TrendingUp,
  TrendingDown,
  Loader2,
} from 'lucide-react';

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { usePayrollEmployee } from '@/hooks/use-payroll';
import { payrollStatusLabels, payrollStatusColors } from '@/types/payroll';

export default function PayrollShowPage() {
  const params = useParams({
    from: '/authenticated/payroll/$periodId/employee/$employeeId',
  }) as {
    periodId: string;
    employeeId: string;
  };

  const { data: payroll, isLoading, error } = usePayrollEmployee(
    params.periodId,
    params.employeeId
  );

  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      minimumFractionDigits: 0,
    }).format(amount);
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
      <div className="p-6 max-w-4xl mx-auto">
        <Link
          to="/payroll"
          className="inline-flex items-center text-sm text-muted-foreground hover:text-foreground mb-4"
        >
          <ArrowLeft className="h-4 w-4 mr-2" />
          Kembali ke daftar payroll
        </Link>
        <Card>
          <CardContent className="p-6 text-center">
            <p className="text-muted-foreground">
              {error?.message || 'Data payroll tidak ditemukan'}
            </p>
          </CardContent>
        </Card>
      </div>
    );
  }

  const totalEarnings =
    payroll.base_salary +
    payroll.position_allowance +
    payroll.transport_allowance +
    payroll.meal_allowance +
    payroll.overtime_pay +
    payroll.bonus +
    payroll.other_allowances;

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

        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <h1 className="text-2xl font-bold text-foreground">Slip Gaji</h1>
            <p className="text-sm text-muted-foreground">{payroll.employee_name}</p>
          </div>
          <div className="flex items-center gap-2">
            <Button variant="outline" size="sm">
              <Printer className="h-4 w-4 mr-2" />
              Print
            </Button>
            <Button size="sm">
              <Download className="h-4 w-4 mr-2" />
              Download PDF
            </Button>
          </div>
        </div>
      </div>

      <div className="space-y-6">
        {/* Employee Info */}
        <Card>
          <CardContent className="p-6">
            <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
              <div className="flex items-center gap-4">
                <div className="h-16 w-16 rounded-full bg-primary/10 flex items-center justify-center">
                  <User className="h-8 w-8 text-primary" />
                </div>
                <div>
                  <h2 className="text-xl font-bold">{payroll.employee_name}</h2>
                  <p className="text-sm text-muted-foreground">
                    {payroll.employee_nip} | {payroll.position}
                  </p>
                  <div className="flex items-center gap-1 text-sm text-muted-foreground mt-1">
                    <Building className="h-3 w-3" />
                    {payroll.department}
                  </div>
                </div>
              </div>
              <Badge
                variant="outline"
                style={{
                  backgroundColor: `${payrollStatusColors[payroll.status]}20`,
                  borderColor: payrollStatusColors[payroll.status],
                  color: payrollStatusColors[payroll.status],
                }}
              >
                {payrollStatusLabels[payroll.status]}
              </Badge>
            </div>
          </CardContent>
        </Card>

        {/* Attendance Summary */}
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2 text-lg">
              <Clock className="h-5 w-5 text-primary" />
              Rekap Kehadiran
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="grid grid-cols-2 sm:grid-cols-5 gap-4">
              <div className="text-center p-3 rounded-lg bg-muted">
                <p className="text-2xl font-bold">{payroll.working_days}</p>
                <p className="text-xs text-muted-foreground">Hari Kerja</p>
              </div>
              <div className="text-center p-3 rounded-lg bg-success/10">
                <p className="text-2xl font-bold text-success">{payroll.present_days}</p>
                <p className="text-xs text-muted-foreground">Hadir</p>
              </div>
              <div className="text-center p-3 rounded-lg bg-warning/10">
                <p className="text-2xl font-bold text-warning">{payroll.late_days}</p>
                <p className="text-xs text-muted-foreground">Terlambat</p>
              </div>
              <div className="text-center p-3 rounded-lg bg-destructive/10">
                <p className="text-2xl font-bold text-destructive">{payroll.absent_days}</p>
                <p className="text-xs text-muted-foreground">Tidak Hadir</p>
              </div>
              <div className="text-center p-3 rounded-lg bg-primary/10">
                <p className="text-2xl font-bold text-primary">{payroll.overtime_hours}h</p>
                <p className="text-xs text-muted-foreground">Lembur</p>
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Earnings & Deductions */}
        <div className="grid gap-6 md:grid-cols-2">
          {/* Earnings */}
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-lg text-success">
                <TrendingUp className="h-5 w-5" />
                Pendapatan
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-3">
              <div className="flex justify-between">
                <span className="text-sm">Gaji Pokok</span>
                <span className="font-medium">{formatCurrency(payroll.base_salary)}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-sm">Tunjangan Jabatan</span>
                <span className="font-medium">{formatCurrency(payroll.position_allowance)}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-sm">Tunjangan Transport</span>
                <span className="font-medium">{formatCurrency(payroll.transport_allowance)}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-sm">Tunjangan Makan</span>
                <span className="font-medium">{formatCurrency(payroll.meal_allowance)}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-sm">Lembur</span>
                <span className="font-medium">{formatCurrency(payroll.overtime_pay)}</span>
              </div>
              {payroll.bonus > 0 && (
                <div className="flex justify-between">
                  <span className="text-sm">Bonus</span>
                  <span className="font-medium">{formatCurrency(payroll.bonus)}</span>
                </div>
              )}
              {payroll.other_allowances > 0 && (
                <div className="flex justify-between">
                  <span className="text-sm">Tunjangan Lainnya</span>
                  <span className="font-medium">{formatCurrency(payroll.other_allowances)}</span>
                </div>
              )}
              <Separator />
              <div className="flex justify-between font-bold">
                <span>Total Pendapatan</span>
                <span className="text-success">{formatCurrency(totalEarnings)}</span>
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
            <CardContent className="space-y-3">
              <div className="flex justify-between">
                <span className="text-sm">BPJS Kesehatan</span>
                <span className="font-medium">{formatCurrency(payroll.bpjs_kesehatan)}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-sm">BPJS Ketenagakerjaan</span>
                <span className="font-medium">{formatCurrency(payroll.bpjs_ketenagakerjaan)}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-sm">PPh 21</span>
                <span className="font-medium">{formatCurrency(payroll.tax)}</span>
              </div>
              {payroll.late_deduction > 0 && (
                <div className="flex justify-between">
                  <span className="text-sm">Denda Terlambat</span>
                  <span className="font-medium">{formatCurrency(payroll.late_deduction)}</span>
                </div>
              )}
              {payroll.absence_deduction > 0 && (
                <div className="flex justify-between">
                  <span className="text-sm">Potongan Tidak Hadir</span>
                  <span className="font-medium">{formatCurrency(payroll.absence_deduction)}</span>
                </div>
              )}
              {payroll.loan_deduction > 0 && (
                <div className="flex justify-between">
                  <span className="text-sm">Potongan Pinjaman</span>
                  <span className="font-medium">{formatCurrency(payroll.loan_deduction)}</span>
                </div>
              )}
              {payroll.other_deductions > 0 && (
                <div className="flex justify-between">
                  <span className="text-sm">Potongan Lainnya</span>
                  <span className="font-medium">{formatCurrency(payroll.other_deductions)}</span>
                </div>
              )}
              <Separator />
              <div className="flex justify-between font-bold">
                <span>Total Potongan</span>
                <span className="text-destructive">{formatCurrency(payroll.total_deductions)}</span>
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Net Salary */}
        <Card className="bg-primary/5 border-primary/20">
          <CardContent className="p-6">
            <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
              <div className="flex items-center gap-3">
                <div className="h-12 w-12 rounded-full bg-primary/10 flex items-center justify-center">
                  <DollarSign className="h-6 w-6 text-primary" />
                </div>
                <div>
                  <p className="text-sm text-muted-foreground">Gaji Bersih</p>
                  <p className="text-3xl font-bold text-primary">
                    {formatCurrency(payroll.net_salary)}
                  </p>
                </div>
              </div>
              <div className="flex items-center gap-2 text-sm text-muted-foreground">
                <Calendar className="h-4 w-4" />
                Dibuat: {new Date(payroll.created_at).toLocaleDateString('id-ID')}
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
