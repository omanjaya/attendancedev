import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import {
  DollarSign,
  Download,
  FileText,
  Calendar,
  TrendingUp,
  TrendingDown,
  Eye,
  X,
} from 'lucide-react';
import { PageLayout } from '@/components/shared/PageLayout';
import { ContentCard } from '@/components/shared/ContentCard';
import { useAuthStore } from '@/stores';
import { format, parseISO } from 'date-fns';
import { id } from 'date-fns/locale';
import { getMyPayroll, downloadMyPayslip, type EmployeePayrollItem } from '@/lib/api/payroll';

interface PayrollItem {
  id: number;
  period: string; // YYYY-MM
  payDate: string | null;
  grossSalary: number;
  totalDeductions: number;
  totalBonuses: number;
  netSalary: number;
  status: 'draft' | 'calculated' | 'approved' | 'paid' | 'cancelled';
  paidAt?: string;
}

/**
 * Employee Payroll Page
 * View personal payslips (read-only)
 */
export function DesktopEmployeePayrollPage() {
  const { user } = useAuthStore();
  const [selectedYear, setSelectedYear] = useState(new Date().getFullYear());
  const [selectedPayroll, setSelectedPayroll] = useState<PayrollItem | null>(null);

  // Fetch payroll data
  const { data: payrollData, isLoading } = useQuery({
    queryKey: ['employee', 'payroll', selectedYear],
    queryFn: () => getMyPayroll(selectedYear),
    select: (data): PayrollItem[] => data.map((item: EmployeePayrollItem) => ({
      id: item.id,
      period: `${item.year}-${String(new Date(`${item.month} 1, ${item.year}`).getMonth() + 1).padStart(2, '0')}`,
      payDate: item.pay_date,
      grossSalary: item.gross_salary,
      totalDeductions: item.total_deductions,
      totalBonuses: item.total_bonuses,
      netSalary: item.net_salary,
      status: item.status,
      paidAt: item.approved_at || undefined,
    })),
  });

  const handleDownloadPayslip = async (payroll: PayrollItem) => {
    try {
      const blob = await downloadMyPayslip(payroll.id);
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `payslip-${payroll.period}.pdf`;
      document.body.appendChild(a);
      a.click();
      window.URL.revokeObjectURL(url);
      document.body.removeChild(a);
    } catch (error) {
      console.error('Failed to download payslip:', error);
    }
  };

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'paid':
        return 'text-green-700 dark:text-green-300 bg-green-100 dark:bg-green-900/30';
      case 'approved':
        return 'text-blue-700 dark:text-blue-300 bg-blue-100 dark:bg-blue-900/30';
      case 'calculated':
        return 'text-yellow-700 dark:text-yellow-300 bg-yellow-100 dark:bg-yellow-900/30';
      case 'draft':
        return 'text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-900/30';
      case 'cancelled':
        return 'text-red-700 dark:text-red-300 bg-red-100 dark:bg-red-900/30';
      default:
        return 'text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-900/30';
    }
  };

  const getStatusLabel = (status: string) => {
    switch (status) {
      case 'paid':
        return 'Dibayar';
      case 'approved':
        return 'Disetujui';
      case 'calculated':
        return 'Dihitung';
      case 'draft':
        return 'Draft';
      case 'cancelled':
        return 'Dibatalkan';
      default:
        return status;
    }
  };

  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      minimumFractionDigits: 0,
    }).format(amount);
  };

  const latestPayroll = payrollData?.[0];

  return (
    <PageLayout
      title="Slip Gaji Saya"
      description="Lihat riwayat slip gaji pribadi"
    >
      {/* Latest Payroll Summary */}
      {latestPayroll && (
        <ContentCard className="mb-6">
          <div className="flex items-start justify-between gap-4 mb-4">
            <div className="flex-1">
              <h3 className="text-sm font-medium text-muted-foreground mb-1">
                Gaji Bulan {format(parseISO(latestPayroll.period + '-01'), 'MMMM yyyy', { locale: id })}
              </h3>
              <p className="text-3xl font-bold text-green-600 dark:text-green-400">
                {formatCurrency(latestPayroll.netSalary)}
              </p>
              <div className="flex items-center gap-2 mt-2">
                <span className={`inline-block px-2 py-1 rounded-full text-xs font-medium ${getStatusColor(latestPayroll.status)}`}>
                  {getStatusLabel(latestPayroll.status)}
                </span>
                {latestPayroll.paidAt && (
                  <span className="text-xs text-muted-foreground">
                    Dibayar {format(parseISO(latestPayroll.paidAt), 'dd MMM yyyy', { locale: id })}
                  </span>
                )}
              </div>
            </div>
            <div className="text-right">
              <button
                onClick={() => handleDownloadPayslip(latestPayroll)}
                className="inline-flex items-center gap-2 px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors text-sm font-medium"
              >
                <Download className="h-4 w-4" />
                Download PDF
              </button>
            </div>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-3 gap-4 pt-4 border-t">
            <div className="flex items-center gap-3">
              <div className="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                <TrendingUp className="h-5 w-5 text-blue-600 dark:text-blue-400" />
              </div>
              <div>
                <p className="text-xs text-muted-foreground">Pendapatan Kotor</p>
                <p className="text-lg font-semibold">{formatCurrency(latestPayroll.grossSalary)}</p>
              </div>
            </div>

            <div className="flex items-center gap-3">
              <div className="p-2 bg-red-100 dark:bg-red-900/30 rounded-lg">
                <TrendingDown className="h-5 w-5 text-red-600 dark:text-red-400" />
              </div>
              <div>
                <p className="text-xs text-muted-foreground">Total Potongan</p>
                <p className="text-lg font-semibold">{formatCurrency(latestPayroll.totalDeductions)}</p>
              </div>
            </div>

            <div className="flex items-center gap-3">
              <div className="p-2 bg-green-100 dark:bg-green-900/30 rounded-lg">
                <DollarSign className="h-5 w-5 text-green-600 dark:text-green-400" />
              </div>
              <div>
                <p className="text-xs text-muted-foreground">Gaji Bersih</p>
                <p className="text-lg font-semibold text-green-600 dark:text-green-400">
                  {formatCurrency(latestPayroll.netSalary)}
                </p>
              </div>
            </div>
          </div>
        </ContentCard>
      )}

      {/* Year Filter */}
      <div className="flex items-center gap-2 mb-4">
        <span className="text-sm font-medium">Tahun:</span>
        <select
          value={selectedYear}
          onChange={(e) => setSelectedYear(parseInt(e.target.value))}
          className="px-3 py-1.5 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary"
        >
          {Array.from({ length: 5 }, (_, i) => new Date().getFullYear() - i).map((year) => (
            <option key={year} value={year}>
              {year}
            </option>
          ))}
        </select>
      </div>

      {/* Payroll History */}
      <ContentCard title="Riwayat Slip Gaji">
        <div className="space-y-3">
          {isLoading ? (
            <div className="flex items-center justify-center py-12">
              <div className="text-center">
                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto mb-3" />
                <p className="text-sm text-muted-foreground">Memuat data...</p>
              </div>
            </div>
          ) : payrollData && payrollData.length > 0 ? (
            payrollData.map((payroll) => (
              <div
                key={payroll.id}
                className="p-4 rounded-lg border bg-card hover:bg-muted/50 transition-colors"
              >
                <div className="flex items-start justify-between gap-4">
                  <div className="flex-1">
                    <div className="flex items-center gap-2 mb-2">
                      <h3 className="text-sm font-semibold">
                        {format(parseISO(payroll.period + '-01'), 'MMMM yyyy', { locale: id })}
                      </h3>
                      <span className={`inline-block px-2 py-0.5 rounded-full text-xs font-medium ${getStatusColor(payroll.status)}`}>
                        {getStatusLabel(payroll.status)}
                      </span>
                    </div>

                    <div className="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
                      <div>
                        <p className="text-xs text-muted-foreground">Pendapatan Kotor</p>
                        <p className="font-medium">{formatCurrency(payroll.grossSalary)}</p>
                      </div>
                      <div>
                        <p className="text-xs text-muted-foreground">Bonus</p>
                        <p className="font-medium">{formatCurrency(payroll.totalBonuses)}</p>
                      </div>
                      <div>
                        <p className="text-xs text-muted-foreground">Potongan</p>
                        <p className="font-medium text-red-600 dark:text-red-400">
                          -{formatCurrency(payroll.totalDeductions)}
                        </p>
                      </div>
                      <div>
                        <p className="text-xs text-muted-foreground">Gaji Bersih</p>
                        <p className="font-semibold text-green-600 dark:text-green-400">
                          {formatCurrency(payroll.netSalary)}
                        </p>
                      </div>
                    </div>

                    {payroll.paidAt && (
                      <div className="mt-2 text-xs text-muted-foreground">
                        <Calendar className="h-3 w-3 inline mr-1" />
                        Tanggal Bayar: {format(parseISO(payroll.paidAt), 'dd MMMM yyyy', { locale: id })}
                      </div>
                    )}
                  </div>

                  <div className="flex flex-col gap-2">
                    <button
                      onClick={() => setSelectedPayroll(payroll)}
                      className="inline-flex items-center gap-1 px-3 py-1.5 border rounded-lg hover:bg-muted transition-colors text-xs font-medium"
                    >
                      <Eye className="h-3 w-3" />
                      Detail
                    </button>
                    <button
                      onClick={() => handleDownloadPayslip(payroll)}
                      className="inline-flex items-center gap-1 px-3 py-1.5 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors text-xs font-medium"
                    >
                      <Download className="h-3 w-3" />
                      PDF
                    </button>
                  </div>
                </div>
              </div>
            ))
          ) : (
            <div className="text-center py-12">
              <FileText className="h-12 w-12 text-muted-foreground mx-auto mb-3 opacity-50" />
              <p className="text-sm text-muted-foreground">Tidak ada data slip gaji untuk tahun {selectedYear}</p>
            </div>
          )}
        </div>
      </ContentCard>

      {/* Payroll Detail Modal */}
      {selectedPayroll && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50">
          <div className="bg-background rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div className="flex items-center justify-between p-4 border-b sticky top-0 bg-background">
              <h2 className="text-lg font-semibold">
                Detail Slip Gaji - {format(parseISO(selectedPayroll.period + '-01'), 'MMMM yyyy', { locale: id })}
              </h2>
              <button
                onClick={() => setSelectedPayroll(null)}
                className="p-1 hover:bg-muted rounded"
              >
                <X className="h-5 w-5" />
              </button>
            </div>

            <div className="p-6 space-y-6">
              {/* Employee Info */}
              <div className="p-4 bg-muted/30 rounded-lg">
                <h3 className="text-sm font-semibold mb-2">Informasi Karyawan</h3>
                <div className="grid grid-cols-2 gap-2 text-sm">
                  <div>
                    <span className="text-muted-foreground">Nama:</span>
                    <span className="ml-2 font-medium">{user?.name}</span>
                  </div>
                  <div>
                    <span className="text-muted-foreground">Posisi:</span>
                    <span className="ml-2 font-medium">{user?.email}</span>
                  </div>
                </div>
              </div>

              {/* Earnings */}
              <div>
                <h3 className="text-sm font-semibold mb-3 flex items-center gap-2">
                  <TrendingUp className="h-4 w-4 text-green-600" />
                  Pendapatan
                </h3>
                <div className="space-y-2">
                  <div className="flex justify-between text-sm p-2 bg-muted/20 rounded">
                    <span>Gaji Kotor</span>
                    <span className="font-medium">{formatCurrency(selectedPayroll.grossSalary)}</span>
                  </div>
                  {selectedPayroll.totalBonuses > 0 && (
                    <div className="flex justify-between text-sm p-2">
                      <span>Bonus</span>
                      <span className="font-medium">{formatCurrency(selectedPayroll.totalBonuses)}</span>
                    </div>
                  )}
                  <div className="flex justify-between text-sm font-semibold p-2 bg-green-50 dark:bg-green-900/20 rounded border-t-2 border-green-200 dark:border-green-800 mt-2">
                    <span>Total Pendapatan</span>
                    <span className="text-green-600 dark:text-green-400">{formatCurrency(selectedPayroll.grossSalary + selectedPayroll.totalBonuses)}</span>
                  </div>
                </div>
              </div>

              {/* Deductions */}
              <div>
                <h3 className="text-sm font-semibold mb-3 flex items-center gap-2">
                  <TrendingDown className="h-4 w-4 text-red-600" />
                  Potongan
                </h3>
                <div className="space-y-2">
                  <div className="flex justify-between text-sm font-semibold p-2 bg-red-50 dark:bg-red-900/20 rounded border-t-2 border-red-200 dark:border-red-800">
                    <span>Total Potongan</span>
                    <span className="text-red-600 dark:text-red-400">
                      -{formatCurrency(selectedPayroll.totalDeductions)}
                    </span>
                  </div>
                </div>
              </div>

              {/* Net Salary */}
              <div className="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border-2 border-green-200 dark:border-green-800">
                <div className="flex justify-between items-center">
                  <span className="text-lg font-semibold">Gaji Bersih (Take Home Pay)</span>
                  <span className="text-2xl font-bold text-green-600 dark:text-green-400">
                    {formatCurrency(selectedPayroll.netSalary)}
                  </span>
                </div>
              </div>

              {/* Download Button */}
              <button
                onClick={() => handleDownloadPayslip(selectedPayroll)}
                className="w-full flex items-center justify-center gap-2 px-4 py-3 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors font-medium"
              >
                <Download className="h-5 w-5" />
                Download Slip Gaji (PDF)
              </button>
            </div>
          </div>
        </div>
      )}
    </PageLayout>
  );
}
