import {
    Plane,
    Plus,
    Clock,
} from 'lucide-react';
import { MobilePageHeader } from '@/components/mobile';
import { format, parseISO } from 'date-fns';
import { id } from 'date-fns/locale';
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { useEmployeeLeavePage } from '@/hooks/use-employee-leave-page';

export function MobileEmployeeLeavePage() {
    // Use shared hook for all logic
    const logic = useEmployeeLeavePage();

    const getStatusColor = (status: string) => {
        switch (status) {
            case 'pending': return 'bg-yellow-100 text-yellow-700 border-yellow-200';
            case 'approved': return 'bg-green-100 text-green-700 border-green-200';
            case 'rejected': return 'bg-red-100 text-red-700 border-red-200';
            default: return 'bg-gray-100 text-gray-700 border-gray-200';
        }
    };


    return (
        <div className="min-h-screen bg-background pb-20">
            {/* Header */}
            <MobilePageHeader
                title="Cuti Saya"
                onBack={() => window.history.back()}
                gradient="teal"
                actions={
                    <Sheet open={logic.showRequestForm} onOpenChange={logic.setShowRequestForm}>
                        <SheetTrigger asChild>
                            <button className="p-2 hover:bg-white/10 rounded-full transition-colors active:scale-95">
                                <Plus className="h-5 w-5 text-white" />
                            </button>
                        </SheetTrigger>
                    <SheetContent side="bottom" className="h-[90vh] rounded-t-xl">
                        <SheetHeader>
                            <SheetTitle>Ajukan Cuti Baru</SheetTitle>
                        </SheetHeader>
                        <form onSubmit={logic.handleSubmitRequest} className="space-y-4 mt-4 overflow-y-auto h-full pb-20">
                            <div className="space-y-2">
                                <label className="text-sm font-medium">Jenis Cuti</label>
                                <select
                                    value={logic.leaveType}
                                    onChange={(e) => logic.setLeaveType(e.target.value)}
                                    className="w-full p-2 border rounded-md"
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
                                    <label className="text-sm font-medium">Mulai</label>
                                    <Input
                                        type="date"
                                        value={logic.startDate}
                                        onChange={(e) => logic.setStartDate(e.target.value)}
                                        required
                                    />
                                </div>
                                <div className="space-y-2">
                                    <label className="text-sm font-medium">Selesai</label>
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
                                <div className="p-3 bg-blue-50 rounded-lg flex justify-between items-center">
                                    <span className="text-sm">Durasi Cuti</span>
                                    <span className="font-bold text-blue-700">{logic.calculateDays()} hari</span>
                                </div>
                            )}

                            <div className="space-y-2">
                                <label className="text-sm font-medium">Alasan</label>
                                <textarea
                                    value={logic.reason}
                                    onChange={(e) => logic.setReason(e.target.value)}
                                    className="w-full p-2 border rounded-md resize-none"
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

            {/* Balance Cards */}
            <div className="p-4 grid grid-cols-2 gap-3">
                <div className="p-4 bg-blue-50 border border-blue-100 rounded-xl">
                    <div className="flex items-center gap-2 mb-2">
                        <Plane className="h-4 w-4 text-blue-600" />
                        <span className="text-xs font-medium text-blue-700">Sisa Cuti Tahunan</span>
                    </div>
                    <p className="text-2xl font-bold text-blue-700">{logic.leaveBalance?.annual_remaining || 0}</p>
                    <p className="text-xs text-blue-600">dari {logic.leaveBalance?.annual_total || 0} hari</p>
                </div>
                <div className="p-4 bg-yellow-50 border border-yellow-100 rounded-xl">
                    <div className="flex items-center gap-2 mb-2">
                        <Clock className="h-4 w-4 text-yellow-600" />
                        <span className="text-xs font-medium text-yellow-700">Cuti Sakit</span>
                    </div>
                    <p className="text-2xl font-bold text-yellow-700">{logic.leaveBalance?.sick_remaining || 0}</p>
                    <p className="text-xs text-yellow-600">dari {logic.leaveBalance?.sick_total || 0} hari</p>
                </div>
            </div>

            {/* Filter */}
            <div className="px-4 mb-4 overflow-x-auto">
                <div className="flex gap-2">
                    {(['all', 'pending', 'approved', 'rejected'] as const).map((status) => (
                        <Badge
                            key={status}
                            variant={logic.filterStatus === status ? 'default' : 'outline'}
                            className="cursor-pointer whitespace-nowrap"
                            onClick={() => logic.setFilterStatus(status)}
                        >
                            {status === 'all' ? 'Semua' : logic.getStatusLabel(status)}
                        </Badge>
                    ))}
                </div>
            </div>

            {/* List */}
            <div className="px-4 space-y-3">
                {logic.isLoading ? (
                    <div className="text-center py-8 text-muted-foreground">Memuat data...</div>
                ) : logic.filteredRequests?.length === 0 ? (
                    <div className="text-center py-8 text-muted-foreground">Belum ada pengajuan cuti</div>
                ) : logic.filteredRequests?.map((request) => (
                    <div
                        key={request.id}
                        className="bg-card border rounded-xl p-4 shadow-sm space-y-3"
                    >
                        <div className="flex items-start justify-between">
                            <div>
                                <h3 className="font-semibold">{request.leave_type?.name || request.leave_type_id}</h3>
                                <p className="text-xs text-muted-foreground">
                                    {format(parseISO(request.start_date), 'dd MMM', { locale: id })} -{' '}
                                    {format(parseISO(request.end_date), 'dd MMM yyyy', { locale: id })}
                                </p>
                            </div>
                            <Badge variant="outline" className={getStatusColor(request.status)}>
                                {logic.getStatusLabel(request.status)}
                            </Badge>
                        </div>

                        <div className="text-sm bg-muted/50 p-2 rounded">
                            <p className="text-muted-foreground">{request.reason}</p>
                        </div>

                        {request.status === 'pending' && (
                            <Button
                                variant="outline"
                                size="sm"
                                className="w-full text-red-600 border-red-200 hover:bg-red-50"
                                onClick={() => logic.handleCancelRequest(request.id)}
                                disabled={logic.cancelLeaveMutation.isPending}
                            >
                                Batalkan Pengajuan
                            </Button>
                        )}
                    </div>
                ))}
            </div>
        </div>
    );
}
