import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import {
    DollarSign,
    Download,
    TrendingUp,
    TrendingDown,
} from 'lucide-react';
import { format, parseISO } from 'date-fns';
import { id } from 'date-fns/locale';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { Sheet, SheetContent, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import { getMyPayroll, downloadMyPayslip, type EmployeePayrollItem } from '@/lib/api/payroll';
import { MobilePageHeader, MobileEmptyState } from '@/components/mobile';

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

export function MobileEmployeePayrollPage() {
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

    const formatCurrency = (amount: number) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
        }).format(amount);
    };

    const getStatusColor = (status: string) => {
        switch (status) {
            case 'paid': return 'bg-green-100 text-green-700 border-green-200';
            case 'approved': return 'bg-blue-100 text-blue-700 border-blue-200';
            case 'calculated': return 'bg-yellow-100 text-yellow-700 border-yellow-200';
            case 'draft': return 'bg-gray-100 text-gray-700 border-gray-200';
            case 'cancelled': return 'bg-red-100 text-red-700 border-red-200';
            default: return 'bg-gray-100 text-gray-700 border-gray-200';
        }
    };

    const getStatusLabel = (status: string) => {
        switch (status) {
            case 'paid': return 'Dibayar';
            case 'approved': return 'Disetujui';
            case 'calculated': return 'Dihitung';
            case 'draft': return 'Draft';
            case 'cancelled': return 'Dibatalkan';
            default: return status;
        }
    };

    const handleDownload = async (payrollId: number) => {
        try {
            const blob = await downloadMyPayslip(payrollId);
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `payslip-${payrollId}.pdf`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
        } catch (error) {
            console.error('Failed to download payslip:', error);
        }
    };

    const latestPayroll = payrollData?.[0];

    if (isLoading) {
        return (
            <div className="min-h-screen bg-background pb-24">
                <MobilePageHeader title="Slip Gaji" gradient="amber" backTo="/employee/dashboard" />
                <div className="px-4 space-y-4">
                    <Skeleton className="h-32 rounded-2xl" />
                    <Skeleton className="h-8 w-48" />
                    {[1, 2, 3].map((i) => (
                        <Skeleton key={i} className="h-24 rounded-xl" />
                    ))}
                </div>
            </div>
        );
    }

    return (
        <div className="min-h-screen bg-background pb-24">
            <MobilePageHeader title="Slip Gaji" gradient="amber" backTo="/employee/dashboard" />

            <div className="px-4 space-y-4">
                {/* Year Selector */}
                <div className="flex items-center justify-between">
                    <h2 className="text-sm font-bold text-foreground">Tahun {selectedYear}</h2>
                    <select
                        value={selectedYear}
                        onChange={(e) => setSelectedYear(parseInt(e.target.value))}
                        className="px-3 py-1.5 border rounded-lg text-sm bg-background focus:outline-none focus:ring-2 focus:ring-primary"
                    >
                        {Array.from({ length: 5 }, (_, i) => new Date().getFullYear() - i).map((year) => (
                            <option key={year} value={year}>
                                {year}
                            </option>
                        ))}
                    </select>
                </div>

            {/* Latest Payroll Card */}
            {latestPayroll && (
                <div className="bg-gradient-to-br from-amber-500 to-amber-600 rounded-2xl p-5 text-white shadow-lg">
                    <div className="flex justify-between items-start mb-4">
                        <div>
                            <p className="text-xs font-medium opacity-80">Gaji Bersih Terakhir</p>
                            <h3 className="text-2xl font-bold mt-1">{formatCurrency(latestPayroll.netSalary)}</h3>
                        </div>
                        <Badge variant="secondary" className="bg-white/20 hover:bg-white/30 text-white border-none">
                            {format(parseISO(latestPayroll.period + '-01'), 'MMM yyyy', { locale: id })}
                        </Badge>
                    </div>

                    <div className="grid grid-cols-2 gap-4 pt-4 border-t border-white/20">
                        <div>
                            <p className="text-xs opacity-80 mb-1">Pendapatan</p>
                            <p className="font-semibold">{formatCurrency(latestPayroll.grossSalary)}</p>
                        </div>
                        <div>
                            <p className="text-xs opacity-80 mb-1">Potongan</p>
                            <p className="font-semibold">-{formatCurrency(latestPayroll.totalDeductions)}</p>
                        </div>
                    </div>
                </div>
            )}

            {/* List */}
            <div className="space-y-3">
                <h3 className="text-sm font-bold text-foreground">Riwayat Pembayaran</h3>

                {payrollData && payrollData.length > 0 ? (
                    payrollData.map((payroll) => (
                        <div
                            key={payroll.id}
                            onClick={() => setSelectedPayroll(payroll)}
                            className="bg-card rounded-2xl shadow-sm dark:border dark:border-border/50 p-4 active:scale-[0.99] transition-transform"
                        >
                            <div className="flex justify-between items-start mb-3">
                                <div className="flex items-center gap-3">
                                    <div className="h-10 w-10 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center text-amber-600 dark:text-amber-400">
                                        <DollarSign className="h-5 w-5" />
                                    </div>
                                    <div>
                                        <h4 className="font-semibold text-sm">
                                            {format(parseISO(payroll.period + '-01'), 'MMMM yyyy', { locale: id })}
                                        </h4>
                                        <p className="text-xs text-muted-foreground">
                                            {payroll.paidAt ? `Dibayar ${format(parseISO(payroll.paidAt), 'dd MMM', { locale: id })}` : 'Belum dibayar'}
                                        </p>
                                    </div>
                                </div>
                                <Badge variant="outline" className={getStatusColor(payroll.status)}>
                                    {getStatusLabel(payroll.status)}
                                </Badge>
                            </div>

                            <div className="flex justify-between items-center pt-2 border-t border-border/50">
                                <span className="text-sm text-muted-foreground">Total Terima</span>
                                <span className="font-bold text-amber-600 dark:text-amber-400">{formatCurrency(payroll.netSalary)}</span>
                            </div>
                        </div>
                    ))
                ) : (
                    <MobileEmptyState
                        icon={DollarSign}
                        title="Tidak Ada Data"
                        description="Belum ada slip gaji untuk tahun ini"
                    />
                )}
            </div>
            </div>

            {/* Detail Sheet */}
            <Sheet open={!!selectedPayroll} onOpenChange={(open) => !open && setSelectedPayroll(null)}>
                <SheetContent side="bottom" className="h-[85vh] rounded-t-xl p-0">
                    {selectedPayroll && (
                        <div className="h-full flex flex-col">
                            <div className="p-4 border-b">
                                <SheetHeader>
                                    <SheetTitle>Detail Slip Gaji</SheetTitle>
                                    <p className="text-sm text-muted-foreground">
                                        Periode {format(parseISO(selectedPayroll.period + '-01'), 'MMMM yyyy', { locale: id })}
                                    </p>
                                </SheetHeader>
                            </div>

                            <div className="flex-1 overflow-y-auto p-4 space-y-6">
                                {/* Summary */}
                                <div className="bg-muted/30 rounded-xl p-4 text-center">
                                    <p className="text-sm text-muted-foreground mb-1">Gaji Bersih (Take Home Pay)</p>
                                    <h2 className="text-3xl font-bold text-primary">{formatCurrency(selectedPayroll.netSalary)}</h2>
                                    <Badge className="mt-2" variant={selectedPayroll.status === 'paid' ? 'default' : 'secondary'}>
                                        Status: {getStatusLabel(selectedPayroll.status)}
                                    </Badge>
                                </div>

                                {/* Earnings */}
                                <div>
                                    <h3 className="text-sm font-semibold mb-3 flex items-center gap-2 text-green-600">
                                        <TrendingUp className="h-4 w-4" />
                                        Pendapatan
                                    </h3>
                                    <div className="space-y-2 text-sm">
                                        <div className="flex justify-between py-1 border-b border-border/50">
                                            <span className="text-muted-foreground">Gaji Kotor</span>
                                            <span className="font-medium">{formatCurrency(selectedPayroll.grossSalary)}</span>
                                        </div>
                                        {selectedPayroll.totalBonuses > 0 && (
                                            <div className="flex justify-between py-1 border-b border-border/50">
                                                <span className="text-muted-foreground">Bonus</span>
                                                <span className="font-medium">{formatCurrency(selectedPayroll.totalBonuses)}</span>
                                            </div>
                                        )}
                                        <div className="flex justify-between py-2 font-bold mt-1">
                                            <span>Total Pendapatan</span>
                                            <span className="text-green-600">{formatCurrency(selectedPayroll.grossSalary + selectedPayroll.totalBonuses)}</span>
                                        </div>
                                    </div>
                                </div>

                                {/* Deductions */}
                                <div>
                                    <h3 className="text-sm font-semibold mb-3 flex items-center gap-2 text-red-600">
                                        <TrendingDown className="h-4 w-4" />
                                        Potongan
                                    </h3>
                                    <div className="space-y-2 text-sm">
                                        <div className="flex justify-between py-2 font-bold">
                                            <span>Total Potongan</span>
                                            <span className="text-red-600">-{formatCurrency(selectedPayroll.totalDeductions)}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div className="p-4 border-t bg-background">
                                <Button className="w-full" onClick={() => handleDownload(selectedPayroll.id)}>
                                    <Download className="mr-2 h-4 w-4" />
                                    Download PDF
                                </Button>
                            </div>
                        </div>
                    )}
                </SheetContent>
            </Sheet>
        </div>
    );
}
