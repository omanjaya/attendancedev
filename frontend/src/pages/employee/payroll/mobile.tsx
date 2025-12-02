import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import {
    DollarSign,
    Download,
    TrendingUp,
    TrendingDown,
    ArrowLeft,
} from 'lucide-react';
import { useAuthStore } from '@/stores';
import { format, parseISO } from 'date-fns';
import { id } from 'date-fns/locale';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { LoadingState } from '@/components/states';
import { Sheet, SheetContent, SheetHeader, SheetTitle } from '@/components/ui/sheet';

interface PayrollItem {
    id: number;
    period: string; // YYYY-MM
    payDate: string;
    basicSalary: number;
    allowances: {
        transport: number;
        meal: number;
        position: number;
        other: number;
    };
    deductions: {
        tax: number;
        insurance: number;
        bpjs: number;
        other: number;
    };
    overtime: number;
    bonus: number;
    grossSalary: number;
    totalDeductions: number;
    netSalary: number;
    status: 'paid' | 'pending' | 'processing';
    paidAt?: string;
}

export function MobileEmployeePayrollPage() {
    const { user } = useAuthStore();
    const [selectedYear, setSelectedYear] = useState(new Date().getFullYear());
    const [selectedPayroll, setSelectedPayroll] = useState<PayrollItem | null>(null);

    // Fetch payroll data
    const { data: payrollData, isLoading } = useQuery({
        queryKey: ['employee', 'payroll', user?.id, selectedYear],
        queryFn: async () => {
            // TODO: Replace with actual API call
            return [
                {
                    id: 1,
                    period: '2025-12',
                    payDate: '2025-12-25',
                    basicSalary: 8000000,
                    allowances: { transport: 500000, meal: 400000, position: 1000000, other: 100000 },
                    deductions: { tax: 850000, insurance: 200000, bpjs: 150000, other: 0 },
                    overtime: 300000,
                    bonus: 0,
                    grossSalary: 10300000,
                    totalDeductions: 1200000,
                    netSalary: 9100000,
                    status: 'processing' as const,
                },
                {
                    id: 2,
                    period: '2025-11',
                    payDate: '2025-11-25',
                    basicSalary: 8000000,
                    allowances: { transport: 500000, meal: 400000, position: 1000000, other: 100000 },
                    deductions: { tax: 850000, insurance: 200000, bpjs: 150000, other: 0 },
                    overtime: 200000,
                    bonus: 0,
                    grossSalary: 10200000,
                    totalDeductions: 1200000,
                    netSalary: 9000000,
                    status: 'paid' as const,
                    paidAt: '2025-11-25T10:00:00',
                },
                {
                    id: 3,
                    period: '2025-10',
                    payDate: '2025-10-25',
                    basicSalary: 8000000,
                    allowances: { transport: 500000, meal: 400000, position: 1000000, other: 100000 },
                    deductions: { tax: 850000, insurance: 200000, bpjs: 150000, other: 0 },
                    overtime: 150000,
                    bonus: 500000,
                    grossSalary: 10650000,
                    totalDeductions: 1200000,
                    netSalary: 9450000,
                    status: 'paid' as const,
                    paidAt: '2025-10-25T10:00:00',
                },
            ] as PayrollItem[];
        },
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
            case 'processing': return 'bg-blue-100 text-blue-700 border-blue-200';
            case 'pending': return 'bg-yellow-100 text-yellow-700 border-yellow-200';
            default: return 'bg-gray-100 text-gray-700 border-gray-200';
        }
    };

    const getStatusLabel = (status: string) => {
        switch (status) {
            case 'paid': return 'Dibayar';
            case 'processing': return 'Diproses';
            case 'pending': return 'Menunggu';
            default: return status;
        }
    };

    const latestPayroll = payrollData?.[0];

    return (
        <div className="min-h-screen bg-background pb-20">
            {/* Header */}
            <div className="sticky top-0 z-10 bg-background/80 backdrop-blur-md border-b p-4 flex items-center justify-between">
                <div className="flex items-center gap-3">
                    <Button variant="ghost" size="icon" onClick={() => window.history.back()}>
                        <ArrowLeft className="h-5 w-5" />
                    </Button>
                    <div>
                        <h1 className="text-lg font-bold">Slip Gaji</h1>
                        <p className="text-xs text-muted-foreground">Riwayat pembayaran gaji</p>
                    </div>
                </div>
                <Button size="icon" variant="ghost">
                    <DollarSign className="h-5 w-5" />
                </Button>
            </div>

            {/* Year Selector */}
            <div className="px-4 py-4 flex items-center justify-between">
                <h2 className="text-base font-semibold">Tahun {selectedYear}</h2>
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
                <div className="px-4 mb-6">
                    <div className="bg-gradient-to-br from-primary to-primary/80 rounded-2xl p-5 text-primary-foreground shadow-lg">
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
                </div>
            )}

            {/* List */}
            <div className="px-4 space-y-3">
                <h3 className="text-sm font-semibold text-muted-foreground mb-2">Riwayat Pembayaran</h3>

                {isLoading ? (
                    <div className="flex justify-center py-8">
                        <LoadingState message="Memuat data..." size="sm" />
                    </div>
                ) : payrollData && payrollData.length > 0 ? (
                    payrollData.map((payroll) => (
                        <div
                            key={payroll.id}
                            onClick={() => setSelectedPayroll(payroll)}
                            className="bg-card border rounded-xl p-4 shadow-sm active:scale-[0.99] transition-transform"
                        >
                            <div className="flex justify-between items-start mb-3">
                                <div className="flex items-center gap-3">
                                    <div className="h-10 w-10 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center text-green-600 dark:text-green-400">
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
                                <span className="font-bold text-primary">{formatCurrency(payroll.netSalary)}</span>
                            </div>
                        </div>
                    ))
                ) : (
                    <div className="text-center py-12 bg-muted/20 rounded-xl border border-dashed">
                        <DollarSign className="h-10 w-10 text-muted-foreground mx-auto mb-3 opacity-30" />
                        <p className="text-sm font-medium">Tidak ada data</p>
                        <p className="text-xs text-muted-foreground">Belum ada slip gaji untuk tahun ini</p>
                    </div>
                )}
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
                                            <span className="text-muted-foreground">Gaji Pokok</span>
                                            <span className="font-medium">{formatCurrency(selectedPayroll.basicSalary)}</span>
                                        </div>
                                        <div className="flex justify-between py-1 border-b border-border/50">
                                            <span className="text-muted-foreground">Tunjangan Transport</span>
                                            <span className="font-medium">{formatCurrency(selectedPayroll.allowances.transport)}</span>
                                        </div>
                                        <div className="flex justify-between py-1 border-b border-border/50">
                                            <span className="text-muted-foreground">Tunjangan Makan</span>
                                            <span className="font-medium">{formatCurrency(selectedPayroll.allowances.meal)}</span>
                                        </div>
                                        <div className="flex justify-between py-1 border-b border-border/50">
                                            <span className="text-muted-foreground">Tunjangan Jabatan</span>
                                            <span className="font-medium">{formatCurrency(selectedPayroll.allowances.position)}</span>
                                        </div>
                                        {selectedPayroll.overtime > 0 && (
                                            <div className="flex justify-between py-1 border-b border-border/50">
                                                <span className="text-muted-foreground">Lembur</span>
                                                <span className="font-medium">{formatCurrency(selectedPayroll.overtime)}</span>
                                            </div>
                                        )}
                                        <div className="flex justify-between py-2 font-bold mt-1">
                                            <span>Total Pendapatan</span>
                                            <span className="text-green-600">{formatCurrency(selectedPayroll.grossSalary)}</span>
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
                                        <div className="flex justify-between py-1 border-b border-border/50">
                                            <span className="text-muted-foreground">Pajak (PPh 21)</span>
                                            <span className="font-medium text-red-600">-{formatCurrency(selectedPayroll.deductions.tax)}</span>
                                        </div>
                                        <div className="flex justify-between py-1 border-b border-border/50">
                                            <span className="text-muted-foreground">BPJS & Asuransi</span>
                                            <span className="font-medium text-red-600">
                                                -{formatCurrency(selectedPayroll.deductions.insurance + selectedPayroll.deductions.bpjs)}
                                            </span>
                                        </div>
                                        <div className="flex justify-between py-2 font-bold mt-1">
                                            <span>Total Potongan</span>
                                            <span className="text-red-600">-{formatCurrency(selectedPayroll.totalDeductions)}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div className="p-4 border-t bg-background">
                                <Button className="w-full" onClick={() => console.log('Download PDF')}>
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
