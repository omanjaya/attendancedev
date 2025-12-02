import { useNavigate } from '@tanstack/react-router';
import {
    ChevronLeft,
    Download,
    DollarSign,
    Calendar,
    Clock,
    TrendingUp,
    TrendingDown,
    Share2
} from 'lucide-react';
import { LoadingState } from '@/components/states';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { payrollStatusLabels, type PayrollEmployee } from '@/types/payroll';

interface MobilePayrollShowPageProps {
    payroll: PayrollEmployee | null;
    isLoading: boolean;
    error: Error | null;
}

export function MobilePayrollShowPage({ payroll, isLoading, error }: MobilePayrollShowPageProps) {
    const navigate = useNavigate();

    const formatCurrency = (amount: number) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
        }).format(amount);
    };

    if (isLoading) {
        return (
            <div className="min-h-screen flex items-center justify-center bg-background">
                <LoadingState message="Memuat detail gaji..." />
            </div>
        );
    }

    if (error || !payroll) {
        return (
            <div className="min-h-screen flex flex-col items-center justify-center p-6 text-center bg-background">
                <div className="h-16 w-16 bg-muted rounded-full flex items-center justify-center mb-4">
                    <DollarSign className="h-8 w-8 text-muted-foreground" />
                </div>
                <h2 className="text-lg font-bold mb-2">Data Tidak Ditemukan</h2>
                <p className="text-muted-foreground text-sm mb-6">
                    {error?.message || 'Detail slip gaji tidak tersedia.'}
                </p>
                <Button onClick={() => navigate({ to: '/payroll' })}>
                    Kembali
                </Button>
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
        <div className="min-h-screen bg-gradient-to-b from-muted/30 via-background to-background dark:from-gray-950 dark:via-gray-900 dark:to-gray-900 pb-24">
            {/* Header Wrapper */}
            <div className="px-4 pt-3 pb-3 sticky top-0 z-20">
                <div className="bg-card/80 dark:bg-card/60 backdrop-blur-md rounded-3xl p-1.5 shadow-xl border border-border/40 dark:border-border/30">
                    <div className="bg-gradient-to-r from-emerald-600 to-teal-600 dark:from-emerald-900 dark:to-teal-800 px-4 py-3 rounded-[20px] flex items-center gap-3 shadow-lg">
                        <button
                            onClick={() => navigate({ to: '/payroll' })}
                            className="p-2 -ml-2 hover:bg-white/10 rounded-full transition-colors active:scale-95"
                        >
                            <ChevronLeft className="h-5 w-5 text-white" />
                        </button>
                        <h1 className="text-base font-bold text-white flex-1">Detail Gaji</h1>
                        <button className="p-2 hover:bg-white/10 rounded-full transition-colors active:scale-95">
                            <Share2 className="h-5 w-5 text-white" />
                        </button>
                    </div>
                </div>
            </div>

            {/* Content */}
            <div className="px-4 space-y-4">
                {/* Net Salary Card */}
                <div className="bg-white dark:bg-gray-900 rounded-3xl p-6 shadow-lg border border-border/50 relative overflow-hidden">
                    <div className="absolute top-0 right-0 p-6 opacity-5">
                        <DollarSign className="h-32 w-32" />
                    </div>
                    <div className="relative z-10 text-center space-y-2">
                        <p className="text-sm text-muted-foreground font-medium uppercase tracking-wider">Gaji Bersih</p>
                        <h2 className="text-3xl font-bold text-emerald-600 dark:text-emerald-400">
                            {formatCurrency(payroll.net_salary)}
                        </h2>
                        <div className="flex items-center justify-center gap-2 pt-2">
                            <Badge
                                variant="outline"
                                className="bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-900/20 dark:text-emerald-400"
                            >
                                {payrollStatusLabels[payroll.status]}
                            </Badge>
                            <span className="text-xs text-muted-foreground">
                                {new Date(payroll.created_at).toLocaleDateString('id-ID', { month: 'long', year: 'numeric' })}
                            </span>
                        </div>
                    </div>
                </div>

                {/* Employee Info */}
                <div className="bg-white dark:bg-gray-900 rounded-3xl p-5 shadow-sm border border-border/50">
                    <div className="flex items-center gap-4">
                        <div className="h-12 w-12 rounded-2xl bg-gradient-to-br from-primary to-primary/80 flex items-center justify-center text-white font-bold text-lg shadow-lg shadow-primary/20">
                            {payroll.employee_name.charAt(0).toUpperCase()}
                        </div>
                        <div className="flex-1 min-w-0">
                            <p className="font-bold text-foreground text-sm truncate">{payroll.employee_name}</p>
                            <p className="text-xs text-muted-foreground truncate">{payroll.position}</p>
                            <p className="text-xs text-muted-foreground truncate">{payroll.department}</p>
                        </div>
                    </div>
                </div>

                {/* Attendance Summary */}
                <div className="bg-white dark:bg-gray-900 rounded-3xl p-5 shadow-sm border border-border/50">
                    <h3 className="text-sm font-bold text-foreground mb-4 flex items-center gap-2">
                        <Clock className="h-4 w-4 text-primary" />
                        Ringkasan Kehadiran
                    </h3>
                    <div className="grid grid-cols-3 gap-3">
                        <div className="bg-muted/30 rounded-2xl p-3 text-center">
                            <p className="text-lg font-bold text-foreground">{payroll.present_days}</p>
                            <p className="text-[10px] text-muted-foreground uppercase tracking-wider">Hadir</p>
                        </div>
                        <div className="bg-muted/30 rounded-2xl p-3 text-center">
                            <p className="text-lg font-bold text-rose-500">{payroll.absent_days}</p>
                            <p className="text-[10px] text-muted-foreground uppercase tracking-wider">Alpha</p>
                        </div>
                        <div className="bg-muted/30 rounded-2xl p-3 text-center">
                            <p className="text-lg font-bold text-orange-500">{payroll.late_days}</p>
                            <p className="text-[10px] text-muted-foreground uppercase tracking-wider">Telat</p>
                        </div>
                    </div>
                </div>

                {/* Earnings */}
                <div className="bg-white dark:bg-gray-900 rounded-3xl p-5 shadow-sm border border-border/50">
                    <h3 className="text-sm font-bold text-foreground mb-4 flex items-center gap-2 text-emerald-600">
                        <TrendingUp className="h-4 w-4" />
                        Pendapatan
                    </h3>
                    <div className="space-y-3">
                        <DetailRow label="Gaji Pokok" value={payroll.base_salary} />
                        <DetailRow label="Tunjangan Jabatan" value={payroll.position_allowance} />
                        <DetailRow label="Tunjangan Transport" value={payroll.transport_allowance} />
                        <DetailRow label="Tunjangan Makan" value={payroll.meal_allowance} />
                        <DetailRow label="Lembur" value={payroll.overtime_pay} />
                        {payroll.bonus > 0 && <DetailRow label="Bonus" value={payroll.bonus} />}
                        {payroll.other_allowances > 0 && <DetailRow label="Lainnya" value={payroll.other_allowances} />}
                        <div className="h-px bg-border/50 my-2" />
                        <div className="flex justify-between items-center">
                            <span className="text-sm font-bold text-foreground">Total Pendapatan</span>
                            <span className="text-sm font-bold text-emerald-600">{formatCurrency(totalEarnings)}</span>
                        </div>
                    </div>
                </div>

                {/* Deductions */}
                <div className="bg-white dark:bg-gray-900 rounded-3xl p-5 shadow-sm border border-border/50">
                    <h3 className="text-sm font-bold text-foreground mb-4 flex items-center gap-2 text-rose-600">
                        <TrendingDown className="h-4 w-4" />
                        Potongan
                    </h3>
                    <div className="space-y-3">
                        <DetailRow label="BPJS Kesehatan" value={payroll.bpjs_kesehatan} />
                        <DetailRow label="BPJS Ketenagakerjaan" value={payroll.bpjs_ketenagakerjaan} />
                        <DetailRow label="PPh 21" value={payroll.tax} />
                        {payroll.late_deduction > 0 && <DetailRow label="Denda Terlambat" value={payroll.late_deduction} />}
                        {payroll.absence_deduction > 0 && <DetailRow label="Potongan Absen" value={payroll.absence_deduction} />}
                        {payroll.loan_deduction > 0 && <DetailRow label="Pinjaman" value={payroll.loan_deduction} />}
                        {payroll.other_deductions > 0 && <DetailRow label="Lainnya" value={payroll.other_deductions} />}
                        <div className="h-px bg-border/50 my-2" />
                        <div className="flex justify-between items-center">
                            <span className="text-sm font-bold text-foreground">Total Potongan</span>
                            <span className="text-sm font-bold text-rose-600">{formatCurrency(payroll.total_deductions)}</span>
                        </div>
                    </div>
                </div>
            </div>

            {/* Floating Download Button */}
            <div className="fixed bottom-6 right-6 z-30">
                <Button
                    className="h-14 w-14 rounded-full shadow-xl bg-primary hover:bg-primary/90 text-primary-foreground p-0 flex items-center justify-center"
                    onClick={() => {
                        alert('Download PDF feature coming soon!');
                    }}
                >
                    <Download className="h-6 w-6" />
                </Button>
            </div>
        </div>
    );
}

function DetailRow({ label, value }: { label: string, value: number }) {
    const formatCurrency = (amount: number) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
        }).format(amount);
    };

    return (
        <div className="flex justify-between items-center text-sm">
            <span className="text-muted-foreground">{label}</span>
            <span className="font-medium text-foreground">{formatCurrency(value)}</span>
        </div>
    );
}
