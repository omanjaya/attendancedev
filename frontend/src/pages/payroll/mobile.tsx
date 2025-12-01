import { useState } from 'react';
import { useNavigate } from '@tanstack/react-router';
import { useQuery } from '@tanstack/react-query';
import {
    ChevronLeft,
    Search,
    Filter,
    DollarSign,
    Calendar,
    ChevronRight,
    Download,
    TrendingUp,
    Clock,
    AlertCircle
} from 'lucide-react';
import { useAuthStore } from '@/stores';
import { getPayrollPeriods } from '@/lib/api/payroll';
import { cn } from '@/lib/utils';
import { Badge } from '@/components/ui/badge';
import { LoadingState } from '@/components/states';

export function MobilePayrollPage() {
    const navigate = useNavigate();
    const { user } = useAuthStore();
    const [year, setYear] = useState(new Date().getFullYear());

    // Fetch payroll periods
    const { data: periodsData, isLoading } = useQuery({
        queryKey: ['payroll-periods', year],
        queryFn: () => getPayrollPeriods({ year }),
    });

    const periods = periodsData?.data || [];

    const getStatusColor = (status: string) => {
        switch (status) {
            case 'completed':
            case 'paid':
                return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400';
            case 'pending':
            case 'draft':
                return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400';
            case 'processing':
                return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400';
            default:
                return 'bg-gray-100 text-gray-700 dark:bg-gray-900/30 dark:text-gray-400';
        }
    };

    const getStatusLabel = (status: string) => {
        switch (status) {
            case 'completed': return 'Selesai';
            case 'paid': return 'Dibayar';
            case 'pending': return 'Menunggu';
            case 'draft': return 'Draft';
            case 'processing': return 'Diproses';
            default: return status;
        }
    };

    const formatCurrency = (amount: number) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        }).format(amount);
    };

    return (
        <div className="min-h-screen bg-gradient-to-b from-muted/30 via-background to-background dark:from-gray-950 dark:via-gray-900 dark:to-gray-900 pb-20">
            {/* Header Wrapper */}
            <div className="px-4 pt-3 pb-3 sticky top-0 z-20">
                <div className="bg-card/80 dark:bg-card/60 backdrop-blur-md rounded-3xl p-1.5 shadow-xl border border-border/40 dark:border-border/30">
                    <div className="bg-gradient-to-r from-emerald-600 to-emerald-500 dark:from-emerald-900 dark:to-emerald-800 px-4 py-3 rounded-[20px] flex items-center gap-3 shadow-lg">
                        <button
                            onClick={() => navigate({ to: '/dashboard' })}
                            className="p-2 -ml-2 hover:bg-white/10 rounded-full transition-colors active:scale-95"
                        >
                            <ChevronLeft className="h-5 w-5 text-white" />
                        </button>
                        <h1 className="text-base font-bold text-white flex-1">Slip Gaji</h1>
                        <button className="p-2 hover:bg-white/10 rounded-full transition-colors active:scale-95">
                            <Search className="h-5 w-5 text-white" />
                        </button>
                    </div>
                </div>
            </div>

            {/* Content */}
            <div className="px-4 space-y-4">
                {/* Summary Card */}
                <div className="bg-white dark:bg-gray-900 rounded-3xl p-5 shadow-sm border border-border/50 relative overflow-hidden">
                    <div className="absolute top-0 right-0 p-4 opacity-5">
                        <DollarSign className="h-24 w-24" />
                    </div>
                    <div className="relative z-10">
                        <div className="flex items-center gap-3 mb-4">
                            <div className="h-10 w-10 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                                <TrendingUp className="h-5 w-5" />
                            </div>
                            <div>
                                <p className="text-xs text-muted-foreground">Total Pendapatan Tahun Ini</p>
                                <p className="text-lg font-bold text-foreground">Rp 0</p>
                            </div>
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <div className="bg-muted/30 rounded-2xl p-3 border border-border/50">
                                <p className="text-xs text-muted-foreground mb-1">Gaji Terakhir</p>
                                <p className="text-sm font-bold text-foreground">-</p>
                            </div>
                            <div className="bg-muted/30 rounded-2xl p-3 border border-border/50">
                                <p className="text-xs text-muted-foreground mb-1">Bonus</p>
                                <p className="text-sm font-bold text-foreground">Rp 0</p>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Year Selector */}
                <div className="flex items-center justify-between">
                    <h2 className="text-sm font-bold text-foreground">Riwayat Penggajian</h2>
                    <div className="flex items-center gap-2 bg-white dark:bg-gray-900 rounded-full px-3 py-1.5 border border-border/50 shadow-sm">
                        <button
                            onClick={() => setYear(year - 1)}
                            className="p-1 hover:bg-muted rounded-full transition-colors"
                        >
                            <ChevronLeft className="h-3 w-3" />
                        </button>
                        <span className="text-xs font-bold">{year}</span>
                        <button
                            onClick={() => setYear(year + 1)}
                            className="p-1 hover:bg-muted rounded-full transition-colors"
                        >
                            <ChevronRight className="h-3 w-3" />
                        </button>
                    </div>
                </div>

                {/* Payroll List */}
                <div className="space-y-3">
                    {isLoading ? (
                        <div className="flex items-center justify-center py-12">
                            <LoadingState message="Memuat data gaji..." size="sm" />
                        </div>
                    ) : periods.length > 0 ? (
                        periods.map((period) => (
                            <div
                                key={period.id}
                                onClick={() => {
                                    // Navigate to detail. Assuming we can construct the URL.
                                    // We need the employee ID.
                                    if (user?.id) {
                                        navigate({ to: `/payroll/${period.id}/employee/${user.id}` });
                                    }
                                }}
                                className="bg-white dark:bg-gray-900 rounded-2xl p-4 shadow-sm border border-border/50 active:scale-[0.99] transition-transform cursor-pointer group"
                            >
                                <div className="flex items-center justify-between mb-3">
                                    <div className="flex items-center gap-3">
                                        <div className="h-10 w-10 rounded-xl bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center text-blue-600 dark:text-blue-400">
                                            <Calendar className="h-5 w-5" />
                                        </div>
                                        <div>
                                            <h3 className="text-sm font-bold text-foreground">{period.name}</h3>
                                            <p className="text-xs text-muted-foreground">
                                                {new Date(period.start_date).toLocaleDateString('id-ID', { month: 'long', year: 'numeric' })}
                                            </p>
                                        </div>
                                    </div>
                                    <Badge variant="outline" className={cn("border-0", getStatusColor(period.status))}>
                                        {getStatusLabel(period.status)}
                                    </Badge>
                                </div>

                                <div className="flex items-center justify-between pt-3 border-t border-border/50">
                                    <div className="flex items-center gap-1.5 text-xs text-muted-foreground">
                                        <Clock className="h-3.5 w-3.5" />
                                        <span>Dibayarkan: {period.pay_date ? new Date(period.pay_date).toLocaleDateString('id-ID') : '-'}</span>
                                    </div>
                                    <div className="flex items-center gap-1 text-primary font-bold text-sm">
                                        <span>Lihat Slip</span>
                                        <ChevronRight className="h-4 w-4 group-hover:translate-x-1 transition-transform" />
                                    </div>
                                </div>
                            </div>
                        ))
                    ) : (
                        <div className="bg-white dark:bg-gray-900 rounded-3xl p-8 text-center shadow-sm border border-border/50">
                            <div className="h-16 w-16 bg-muted/50 rounded-full flex items-center justify-center mx-auto mb-4">
                                <AlertCircle className="h-8 w-8 text-muted-foreground/50" />
                            </div>
                            <p className="text-sm font-bold text-foreground mb-1">Tidak Ada Data</p>
                            <p className="text-xs text-muted-foreground">
                                Tidak ada riwayat penggajian untuk tahun {year}.
                            </p>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}
