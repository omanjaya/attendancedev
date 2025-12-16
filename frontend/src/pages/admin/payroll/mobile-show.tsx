import { useNavigate } from '@tanstack/react-router';
import {
    ChevronLeft,
    Download,
    DollarSign,
    Clock,
    TrendingUp,
    TrendingDown,
    Share2,
    Calendar
} from 'lucide-react';
import { LoadingState } from '@/components/states';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { payrollStatusLabels, type PayrollEmployeeDetail } from '@/types/payroll';

interface MobilePayrollShowPageProps {
    payroll: PayrollEmployeeDetail | null;
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
        }).format(amount || 0);
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
                <Button onClick={() => navigate({ to: '/admin/payroll' })}>
                    Kembali
                </Button>
            </div>
        );
    }

    return (
        <div className="min-h-screen bg-gradient-to-b from-muted/30 via-background to-background dark:from-gray-950 dark:via-gray-900 dark:to-gray-900 pb-24">
            {/* Header Wrapper */}
            <div className="px-4 pt-3 pb-3 sticky top-0 z-20">
                <div className="bg-card/80 dark:bg-card/60 backdrop-blur-md rounded-3xl p-1.5 shadow-xl border border-border/40 dark:border-border/30">
                    <div className="bg-gradient-to-r from-emerald-600 to-teal-600 dark:from-emerald-900 dark:to-teal-800 px-4 py-3 rounded-[20px] flex items-center gap-3 shadow-lg">
                        <button
                            type="button"
                            title="Kembali"
                            aria-label="Kembali"
                            onClick={() => navigate({ to: '/admin/payroll' })}
                            className="p-2 -ml-2 hover:bg-white/10 rounded-full transition-colors active:scale-95"
                        >
                            <ChevronLeft className="h-5 w-5 text-white" />
                        </button>
                        <h1 className="text-base font-bold text-white flex-1">Detail Gaji</h1>
                        <button
                            type="button"
                            title="Bagikan"
                            aria-label="Bagikan"
                            className="p-2 hover:bg-white/10 rounded-full transition-colors active:scale-95"
                        >
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
                                {payrollStatusLabels[payroll.status] || payroll.status}
                            </Badge>
                        </div>
                    </div>
                </div>

                {/* Employee Info */}
                <div className="bg-white dark:bg-gray-900 rounded-3xl p-5 shadow-sm border border-border/50">
                    <div className="flex items-center gap-4">
                        <div className="h-12 w-12 rounded-2xl bg-gradient-to-br from-primary to-primary/80 flex items-center justify-center text-white font-bold text-lg shadow-lg shadow-primary/20">
                            {payroll.employee?.name?.charAt(0).toUpperCase() || 'E'}
                        </div>
                        <div className="flex-1 min-w-0">
                            <p className="font-bold text-foreground text-sm truncate">{payroll.employee?.name}</p>
                            <p className="text-xs text-muted-foreground truncate">{payroll.employee?.position || '-'}</p>
                            <p className="text-xs text-muted-foreground truncate">{payroll.employee?.department || '-'}</p>
                        </div>
                    </div>
                </div>

                {/* Period Info */}
                <div className="bg-white dark:bg-gray-900 rounded-3xl p-5 shadow-sm border border-border/50">
                    <h3 className="text-sm font-bold text-foreground mb-4 flex items-center gap-2">
                        <Calendar className="h-4 w-4 text-primary" />
                        Periode Gaji
                    </h3>
                    <div className="grid grid-cols-2 gap-3">
                        <div className="bg-muted/30 rounded-2xl p-3 text-center">
                            <p className="text-xs font-medium text-foreground">{payroll.period?.name}</p>
                            <p className="text-[10px] text-muted-foreground uppercase tracking-wider">Periode</p>
                        </div>
                        <div className="bg-muted/30 rounded-2xl p-3 text-center">
                            <p className="text-xs font-medium text-foreground">{payroll.period?.pay_date || '-'}</p>
                            <p className="text-[10px] text-muted-foreground uppercase tracking-wider">Tanggal Bayar</p>
                        </div>
                    </div>
                </div>

                {/* Work Summary */}
                <div className="bg-white dark:bg-gray-900 rounded-3xl p-5 shadow-sm border border-border/50">
                    <h3 className="text-sm font-bold text-foreground mb-4 flex items-center gap-2">
                        <Clock className="h-4 w-4 text-primary" />
                        Ringkasan Kerja
                    </h3>
                    <div className="grid grid-cols-2 gap-3">
                        <div className="bg-muted/30 rounded-2xl p-3 text-center">
                            <p className="text-lg font-bold text-foreground">{payroll.worked_hours || 0}h</p>
                            <p className="text-[10px] text-muted-foreground uppercase tracking-wider">Jam Kerja</p>
                        </div>
                        <div className="bg-muted/30 rounded-2xl p-3 text-center">
                            <p className="text-lg font-bold text-primary">{payroll.overtime_hours || 0}h</p>
                            <p className="text-[10px] text-muted-foreground uppercase tracking-wider">Lembur</p>
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
                        {payroll.earnings?.length > 0 ? (
                            payroll.earnings.map((item, index) => (
                                <DetailRow key={item.id || index} label={item.name || 'Item'} value={item.amount} />
                            ))
                        ) : (
                            <p className="text-sm text-muted-foreground">Tidak ada data pendapatan</p>
                        )}
                        {payroll.bonuses?.length > 0 && (
                            <>
                                <div className="h-px bg-border/50 my-2" />
                                <p className="text-xs text-muted-foreground font-medium">Bonus</p>
                                {payroll.bonuses.map((item, index) => (
                                    <DetailRow key={item.id || index} label={item.name || 'Bonus'} value={item.amount} />
                                ))}
                            </>
                        )}
                        <div className="h-px bg-border/50 my-2" />
                        <div className="flex justify-between items-center">
                            <span className="text-sm font-bold text-foreground">Total Pendapatan</span>
                            <span className="text-sm font-bold text-emerald-600">{formatCurrency(payroll.gross_salary)}</span>
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
                        {payroll.deductions?.length > 0 ? (
                            payroll.deductions.map((item, index) => (
                                <DetailRow key={item.id || index} label={item.name || 'Potongan'} value={item.amount} />
                            ))
                        ) : (
                            <p className="text-sm text-muted-foreground">Tidak ada potongan</p>
                        )}
                        <div className="h-px bg-border/50 my-2" />
                        <div className="flex justify-between items-center">
                            <span className="text-sm font-bold text-foreground">Total Potongan</span>
                            <span className="text-sm font-bold text-rose-600">{formatCurrency(payroll.total_deductions)}</span>
                        </div>
                    </div>
                </div>

                {/* Notes */}
                {payroll.notes && (
                    <div className="bg-white dark:bg-gray-900 rounded-3xl p-5 shadow-sm border border-border/50">
                        <h3 className="text-sm font-bold text-foreground mb-2">Catatan</h3>
                        <p className="text-sm text-muted-foreground">{payroll.notes}</p>
                    </div>
                )}
            </div>

            {/* Floating Download Button */}
            <div className="fixed bottom-6 right-6 z-30">
                <Button
                    type="button"
                    title="Download PDF"
                    aria-label="Download PDF"
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
        }).format(amount || 0);
    };

    return (
        <div className="flex justify-between items-center">
            <span className="text-sm text-muted-foreground">{label}</span>
            <span className="text-sm font-medium text-foreground">{formatCurrency(value)}</span>
        </div>
    );
}
