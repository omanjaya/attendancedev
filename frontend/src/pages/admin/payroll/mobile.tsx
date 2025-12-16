import { useState } from 'react';
import { useNavigate } from '@tanstack/react-router';
import { useQuery } from '@tanstack/react-query';
import {
    DollarSign,
    Calendar,
    ChevronLeft,
    ChevronRight,
    TrendingUp,
    Clock,
} from 'lucide-react';
import { MobilePageHeader, MobileEmptyState } from '@/components/mobile';
import { useAuthStore } from '@/stores';
import { getPayrollPeriods } from '@/lib/api/payroll';
import { cn } from '@/lib/utils';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';

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
            case 'approved':
            case 'paid':
                return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400';
            case 'pending':
            case 'draft':
                return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400';
            case 'processed':
                return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400';
            case 'cancelled':
                return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400';
            default:
                return 'bg-gray-100 text-gray-700 dark:bg-gray-900/30 dark:text-gray-400';
        }
    };

    const getStatusLabel = (status: string) => {
        switch (status) {
            case 'approved': return 'Disetujui';
            case 'paid': return 'Dibayar';
            case 'pending': return 'Menunggu';
            case 'draft': return 'Draft';
            case 'processed': return 'Diproses';
            case 'cancelled': return 'Dibatalkan';
            default: return status;
        }
    };



    if (isLoading) {
        return (
            <div className="min-h-screen bg-background pb-24">
                <MobilePageHeader title="Kelola Payroll" gradient="amber" backTo="/admin/dashboard" />
                <div className="px-4 space-y-4">
                    <Skeleton className="h-32 rounded-2xl" />
                    <Skeleton className="h-8 w-48" />
                    {[1, 2, 3].map((i) => (
                        <Skeleton key={i} className="h-24 rounded-2xl" />
                    ))}
                </div>
            </div>
        );
    }

    return (
        <div className="min-h-screen bg-background pb-24">
            <MobilePageHeader title="Kelola Payroll" gradient="amber" backTo="/admin/dashboard" />

            <div className="px-4 space-y-4">
                {/* Summary Card */}
                <div className="bg-gradient-to-br from-amber-500 to-amber-600 rounded-2xl p-5 text-white shadow-lg relative overflow-hidden">
                    <div className="absolute top-0 right-0 p-4 opacity-10">
                        <DollarSign className="h-20 w-20" />
                    </div>
                    <div className="relative z-10">
                        <div className="flex items-center gap-3 mb-4">
                            <div className="h-10 w-10 rounded-full bg-white/20 flex items-center justify-center">
                                <TrendingUp className="h-5 w-5 text-white" />
                            </div>
                            <div>
                                <p className="text-xs text-white/80">Total Pendapatan Tahun Ini</p>
                                <p className="text-lg font-bold">Rp 0</p>
                            </div>
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <div className="bg-white/10 rounded-xl p-3">
                                <p className="text-xs text-white/80 mb-1">Gaji Terakhir</p>
                                <p className="text-sm font-bold">-</p>
                            </div>
                            <div className="bg-white/10 rounded-xl p-3">
                                <p className="text-xs text-white/80 mb-1">Bonus</p>
                                <p className="text-sm font-bold">Rp 0</p>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Year Selector */}
                <div className="flex items-center justify-between">
                    <h2 className="text-sm font-bold text-foreground">Riwayat Penggajian</h2>
                    <div className="flex items-center gap-2 bg-card rounded-full px-3 py-1.5 shadow-sm dark:border dark:border-border/50">
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
                    {periods.length > 0 ? (
                        periods.map((period) => (
                            <div
                                key={period.id}
                                onClick={() => {
                                    if (user?.id) {
                                        navigate({ to: `/payroll/${period.id}/employee/${user.id}` });
                                    }
                                }}
                                className="bg-card rounded-2xl shadow-sm dark:border dark:border-border/50 p-4 active:scale-[0.99] transition-transform cursor-pointer group"
                            >
                                <div className="flex items-center justify-between mb-3">
                                    <div className="flex items-center gap-3">
                                        <div className="h-10 w-10 rounded-xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center text-amber-600 dark:text-amber-400">
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
                                    <div className="flex items-center gap-1 text-amber-600 dark:text-amber-400 font-bold text-sm">
                                        <span>Lihat Slip</span>
                                        <ChevronRight className="h-4 w-4 group-hover:translate-x-1 transition-transform" />
                                    </div>
                                </div>
                            </div>
                        ))
                    ) : (
                        <MobileEmptyState
                            icon={DollarSign}
                            title="Tidak Ada Data"
                            description={`Tidak ada riwayat penggajian untuk tahun ${year}.`}
                        />
                    )}
                </div>
            </div>
        </div>
    );
}
