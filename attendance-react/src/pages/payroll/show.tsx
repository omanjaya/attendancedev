import { Link } from '@tanstack/react-router';
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
} from 'lucide-react';

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';

// Mock payroll data
const mockPayroll = {
  id: 1,
  employee: {
    name: 'Ahmad Fauzi',
    employee_id: 'EMP001',
    department: 'IT & Development',
    position: 'Senior Developer',
  },
  period: {
    month: 'November',
    year: 2024,
    start_date: '2024-11-01',
    end_date: '2024-11-30',
  },
  attendance: {
    working_days: 22,
    present_days: 21,
    late_days: 2,
    absent_days: 1,
    overtime_hours: 12,
  },
  earnings: {
    basic_salary: 15000000,
    transport_allowance: 1500000,
    meal_allowance: 1000000,
    overtime_pay: 900000,
    bonus: 0,
    total: 18400000,
  },
  deductions: {
    bpjs_kesehatan: 150000,
    bpjs_ketenagakerjaan: 300000,
    tax: 920000,
    late_penalty: 100000,
    absent_deduction: 500000,
    total: 1970000,
  },
  net_salary: 16430000,
  status: 'paid',
  paid_date: '2024-11-28',
};

export default function PayrollShowPage() {
  const payroll = mockPayroll; // In real app, fetch by id

  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      minimumFractionDigits: 0,
    }).format(amount);
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

        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <h1 className="text-2xl font-bold text-foreground">Slip Gaji</h1>
            <p className="text-sm text-muted-foreground">
              {payroll.period.month} {payroll.period.year}
            </p>
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
                  <h2 className="text-xl font-bold">{payroll.employee.name}</h2>
                  <p className="text-sm text-muted-foreground">
                    {payroll.employee.employee_id} | {payroll.employee.position}
                  </p>
                  <div className="flex items-center gap-1 text-sm text-muted-foreground mt-1">
                    <Building className="h-3 w-3" />
                    {payroll.employee.department}
                  </div>
                </div>
              </div>
              <Badge
                variant="outline"
                className={payroll.status === 'paid' ? 'bg-success/10 text-success' : 'bg-warning/10 text-warning'}
              >
                {payroll.status === 'paid' ? 'Sudah Dibayar' : 'Pending'}
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
                <p className="text-2xl font-bold">{payroll.attendance.working_days}</p>
                <p className="text-xs text-muted-foreground">Hari Kerja</p>
              </div>
              <div className="text-center p-3 rounded-lg bg-success/10">
                <p className="text-2xl font-bold text-success">{payroll.attendance.present_days}</p>
                <p className="text-xs text-muted-foreground">Hadir</p>
              </div>
              <div className="text-center p-3 rounded-lg bg-warning/10">
                <p className="text-2xl font-bold text-warning">{payroll.attendance.late_days}</p>
                <p className="text-xs text-muted-foreground">Terlambat</p>
              </div>
              <div className="text-center p-3 rounded-lg bg-destructive/10">
                <p className="text-2xl font-bold text-destructive">{payroll.attendance.absent_days}</p>
                <p className="text-xs text-muted-foreground">Tidak Hadir</p>
              </div>
              <div className="text-center p-3 rounded-lg bg-primary/10">
                <p className="text-2xl font-bold text-primary">{payroll.attendance.overtime_hours}h</p>
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
                <span className="font-medium">{formatCurrency(payroll.earnings.basic_salary)}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-sm">Tunjangan Transport</span>
                <span className="font-medium">{formatCurrency(payroll.earnings.transport_allowance)}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-sm">Tunjangan Makan</span>
                <span className="font-medium">{formatCurrency(payroll.earnings.meal_allowance)}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-sm">Lembur</span>
                <span className="font-medium">{formatCurrency(payroll.earnings.overtime_pay)}</span>
              </div>
              {payroll.earnings.bonus > 0 && (
                <div className="flex justify-between">
                  <span className="text-sm">Bonus</span>
                  <span className="font-medium">{formatCurrency(payroll.earnings.bonus)}</span>
                </div>
              )}
              <Separator />
              <div className="flex justify-between font-bold">
                <span>Total Pendapatan</span>
                <span className="text-success">{formatCurrency(payroll.earnings.total)}</span>
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
                <span className="font-medium">{formatCurrency(payroll.deductions.bpjs_kesehatan)}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-sm">BPJS Ketenagakerjaan</span>
                <span className="font-medium">{formatCurrency(payroll.deductions.bpjs_ketenagakerjaan)}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-sm">PPh 21</span>
                <span className="font-medium">{formatCurrency(payroll.deductions.tax)}</span>
              </div>
              {payroll.deductions.late_penalty > 0 && (
                <div className="flex justify-between">
                  <span className="text-sm">Denda Terlambat</span>
                  <span className="font-medium">{formatCurrency(payroll.deductions.late_penalty)}</span>
                </div>
              )}
              {payroll.deductions.absent_deduction > 0 && (
                <div className="flex justify-between">
                  <span className="text-sm">Potongan Tidak Hadir</span>
                  <span className="font-medium">{formatCurrency(payroll.deductions.absent_deduction)}</span>
                </div>
              )}
              <Separator />
              <div className="flex justify-between font-bold">
                <span>Total Potongan</span>
                <span className="text-destructive">{formatCurrency(payroll.deductions.total)}</span>
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
              {payroll.paid_date && (
                <div className="flex items-center gap-2 text-sm text-muted-foreground">
                  <Calendar className="h-4 w-4" />
                  Dibayar: {new Date(payroll.paid_date).toLocaleDateString('id-ID')}
                </div>
              )}
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
