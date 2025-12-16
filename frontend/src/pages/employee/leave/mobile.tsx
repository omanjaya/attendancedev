import {
    Plane,
    Plus,
    Clock,
    Calendar,
} from 'lucide-react';
import { MobilePageHeader, MobileStatusBadge, MobileEmptyState } from '@/components/mobile';
import { format, parseISO } from 'date-fns';
import { id } from 'date-fns/locale';
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useEmployeeLeavePage } from '@/hooks/use-employee-leave-page';

export function MobileEmployeeLeavePage() {
    // Use shared hook for all logic
    const logic = useEmployeeLeavePage();

    return (
        <div className="min-h-screen bg-background pb-24">
            <MobilePageHeader
                title="Cuti Saya"
                gradient="teal"
                rightAction={
                    <Sheet open={logic.showRequestForm} onOpenChange={logic.setShowRequestForm}>
                        <SheetTrigger asChild>
                            <button className="p-2 hover:bg-white/10 rounded-full transition-colors active:scale-95">
                                <Plus className="h-5 w-5 text-white" />
                            </button>
                        </SheetTrigger>
                    <SheetContent side="bottom" className="h-[85vh] rounded-t-3xl">
                        <SheetHeader>
                            <SheetTitle>Ajukan Cuti Baru</SheetTitle>
                        </SheetHeader>
                        <form onSubmit={logic.handleSubmitRequest} className="space-y-4 mt-4 overflow-y-auto">
                            <div className="space-y-2">
                                <label className="text-sm font-medium text-foreground">Jenis Cuti</label>
                                <select
                                    value={logic.leaveType}
                                    onChange={(e) => logic.setLeaveType(e.target.value)}
                                    className="w-full p-2 border rounded-md bg-background"
                                    required
                                    aria-label="Pilih jenis cuti"
                                >
                                    <option value="">Pilih jenis cuti</option>
                                    <option value="annual">Cuti Tahunan</option>
                                    <option value="sick">Cuti Sakit</option>
                                    <option value="special">Cuti Khusus</option>
                                    <option value="maternity">Cuti Melahirkan</option>
                                </select>
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <label className="text-sm font-medium text-foreground">Mulai</label>
                                    <Input
                                        type="date"
                                        value={logic.startDate}
                                        onChange={(e) => logic.setStartDate(e.target.value)}
                                        required
                                    />
                                </div>
                                <div className="space-y-2">
                                    <label className="text-sm font-medium text-foreground">Selesai</label>
                                    <Input
                                        type="date"
                                        value={logic.endDate}
                                        onChange={(e) => logic.setEndDate(e.target.value)}
                                        min={logic.startDate}
                                        required
                                    />
                                </div>
                            </div>

                            {logic.startDate && logic.endDate && (
                                <div className="p-3 bg-teal-50 dark:bg-teal-900/20 rounded-xl border border-teal-200 dark:border-teal-800 space-y-2">
                                    {logic.isLoadingWorkingDays ? (
                                        <div className="flex justify-between items-center">
                                            <span className="text-sm text-muted-foreground">Menghitung...</span>
                                            <span className="text-sm text-muted-foreground">⏳</span>
                                        </div>
                                    ) : logic.workingDaysPreview ? (
                                        <>
                                            <div className="flex justify-between items-center">
                                                <span className="text-sm font-medium text-foreground">Hari Kerja</span>
                                                <span className="font-bold text-teal-600 dark:text-teal-400 text-lg">
                                                    {logic.workingDaysPreview.working_days} hari
                                                </span>
                                            </div>
                                            <div className="text-xs text-muted-foreground space-y-0.5 border-t pt-2">
                                                <div className="flex justify-between">
                                                    <span>Total kalender</span>
                                                    <span>{logic.workingDaysPreview.total_calendar_days} hari</span>
                                                </div>
                                                {logic.workingDaysPreview.skipped_weekends.length > 0 && (
                                                    <div className="flex justify-between">
                                                        <span>Weekend</span>
                                                        <span>-{logic.workingDaysPreview.skipped_weekends.length} hari</span>
                                                    </div>
                                                )}
                                                {logic.workingDaysPreview.skipped_holidays.length > 0 && (
                                                    <div className="space-y-1">
                                                        <div className="flex justify-between">
                                                            <span>Hari Libur</span>
                                                            <span>-{logic.workingDaysPreview.skipped_holidays.length} hari</span>
                                                        </div>
                                                        <div className="text-amber-600 dark:text-amber-400 pl-2">
                                                            {logic.workingDaysPreview.skipped_holidays.map((h) => (
                                                                <div key={h.date}>• {h.holiday_name}</div>
                                                            ))}
                                                        </div>
                                                    </div>
                                                )}
                                            </div>
                                        </>
                                    ) : (
                                        <div className="flex justify-between items-center">
                                            <span className="text-sm text-muted-foreground">Durasi Cuti</span>
                                            <span className="font-bold text-teal-600">{logic.calculateDays()} hari</span>
                                        </div>
                                    )}
                                </div>
                            )}

                            <div className="space-y-2">
                                <label className="text-sm font-medium text-foreground">Alasan</label>
                                <textarea
                                    value={logic.reason}
                                    onChange={(e) => logic.setReason(e.target.value)}
                                    className="w-full p-2 border rounded-md resize-none bg-background"
                                    rows={4}
                                    placeholder="Jelaskan alasan..."
                                    required
                                />
                            </div>

                            <Button type="submit" className="w-full" disabled={logic.createLeaveMutation.isPending}>
                                {logic.createLeaveMutation.isPending ? 'Mengirim...' : 'Ajukan Cuti'}
                            </Button>
                        </form>
                    </SheetContent>
                </Sheet>
                }
            />

            <div className="px-4 space-y-4">
                {/* Balance Cards */}
                <div className="grid grid-cols-2 gap-3">
                    <div className="bg-card rounded-2xl shadow-sm dark:border dark:border-border/50 p-4">
                        <div className="flex flex-col items-center text-center">
                            <div className="h-12 w-12 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center mb-2">
                                <Plane className="h-6 w-6 text-blue-600 dark:text-blue-400" />
                            </div>
                            <p className="text-xs font-medium text-muted-foreground mb-1">Sisa Cuti Tahunan</p>
                            <p className="text-2xl font-bold text-foreground">{logic.leaveBalance?.annual_remaining || 0}</p>
                            <p className="text-xs text-muted-foreground">dari {logic.leaveBalance?.annual_total || 0} hari</p>
                        </div>
                    </div>
                    <div className="bg-card rounded-2xl shadow-sm dark:border dark:border-border/50 p-4">
                        <div className="flex flex-col items-center text-center">
                            <div className="h-12 w-12 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center mb-2">
                                <Clock className="h-6 w-6 text-amber-600 dark:text-amber-400" />
                            </div>
                            <p className="text-xs font-medium text-muted-foreground mb-1">Cuti Sakit</p>
                            <p className="text-2xl font-bold text-foreground">{logic.leaveBalance?.sick_remaining || 0}</p>
                            <p className="text-xs text-muted-foreground">dari {logic.leaveBalance?.sick_total || 0} hari</p>
                        </div>
                    </div>
                </div>

                {/* Filter */}
                <div className="bg-card rounded-2xl shadow-sm dark:border dark:border-border/50 p-4">
                    <h3 className="text-sm font-bold text-foreground mb-3">Filter</h3>
                    <div className="flex gap-2 overflow-x-auto pb-2">
                        {(['all', 'pending', 'approved', 'rejected'] as const).map((status) => (
                            <button
                                key={status}
                                onClick={() => logic.setFilterStatus(status)}
                                className={`px-3 py-1 text-xs rounded-full border transition-colors ${
                                    logic.filterStatus === status
                                        ? 'bg-primary text-primary-foreground border-primary'
                                        : 'hover:bg-muted border-border'
                                }`}
                            >
                                {status === 'all' ? 'Semua' : logic.getStatusLabel(status)}
                            </button>
                        ))}
                    </div>
                </div>

                {/* List */}
                <div className="space-y-3">
                    {logic.isLoading ? (
                        <div className="bg-card rounded-2xl shadow-sm dark:border dark:border-border/50 p-4">
                            <div className="text-center py-8 text-muted-foreground">Memuat data...</div>
                        </div>
                    ) : logic.filteredRequests?.length === 0 ? (
                        <MobileEmptyState
                            icon={Calendar}
                            title="Belum Ada Pengajuan Cuti"
                            description="Mulai dengan mengajukan cuti baru menggunakan tombol di atas"
                            action={{
                                label: "Ajukan Cuti",
                                onClick: () => logic.setShowRequestForm(true),
                                icon: Plus
                            }}
                        />
                    ) : (
                        logic.filteredRequests?.map((request) => (
                            <div
                                key={request.id}
                                className="bg-card rounded-2xl shadow-sm dark:border dark:border-border/50 p-4 space-y-3"
                            >
                                <div className="flex items-start justify-between">
                                    <div className="flex-1">
                                        <h3 className="text-sm font-semibold text-foreground mb-1">
                                            {request.leave_type?.name || request.leave_type_id}
                                        </h3>
                                        <p className="text-xs text-muted-foreground">
                                            {format(parseISO(request.start_date), 'dd MMM', { locale: id })} -{' '}
                                            {format(parseISO(request.end_date), 'dd MMM yyyy', { locale: id })}
                                        </p>
                                    </div>
                                    <MobileStatusBadge status={request.status as any} />
                                </div>

                                <div className="bg-muted/30 rounded-xl p-3 border border-border/50">
                                    <p className="text-xs text-muted-foreground line-clamp-3">{request.reason}</p>
                                </div>

                                {request.status === 'pending' && (
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        className="w-full text-red-600 border-red-200 hover:bg-red-50 dark:border-red-800 dark:hover:bg-red-950/30"
                                        onClick={() => logic.handleCancelRequest(request.id)}
                                        disabled={logic.cancelLeaveMutation.isPending}
                                    >
                                        Batalkan Pengajuan
                                    </Button>
                                )}
                            </div>
                        ))
                    )}
                </div>
            </div>
        </div>
    );
}
