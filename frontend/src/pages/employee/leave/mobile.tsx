import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import {
    Plane,
    Plus,
    Clock,
    ArrowLeft,
} from 'lucide-react';
import { format, parseISO, differenceInDays } from 'date-fns';
import { id } from 'date-fns/locale';
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { useAuthStore } from '@/stores';

interface LeaveRequest {
    id: number;
    leaveType: string;
    startDate: string;
    endDate: string;
    days: number;
    reason: string;
    status: 'pending' | 'approved' | 'rejected';
    approvedBy?: string;
    approvedAt?: string;
    rejectedReason?: string;
    createdAt: string;
}

export function MobileEmployeeLeavePage() {
    const { user } = useAuthStore();
    const queryClient = useQueryClient();
    const [showRequestForm, setShowRequestForm] = useState(false);
    const [filterStatus, setFilterStatus] = useState<'all' | 'pending' | 'approved' | 'rejected'>('all');

    // Form state
    const [leaveType, setLeaveType] = useState('');
    const [startDate, setStartDate] = useState('');
    const [endDate, setEndDate] = useState('');
    const [reason, setReason] = useState('');

    // Fetch leave balance
    const { data: leaveBalance } = useQuery({
        queryKey: ['employee', 'leave-balance', user?.id],
        queryFn: async () => {
            // TODO: Replace with actual API call
            return {
                total: 12,
                used: 3,
                pending: 2,
                remaining: 7,
            };
        },
    });

    // Fetch leave requests
    const { data: leaveRequests, isLoading } = useQuery({
        queryKey: ['employee', 'leave-requests', user?.id],
        queryFn: async () => {
            // TODO: Replace with actual API call
            return [
                {
                    id: 1,
                    leaveType: 'Cuti Tahunan',
                    startDate: '2025-12-10',
                    endDate: '2025-12-12',
                    days: 3,
                    reason: 'Liburan keluarga',
                    status: 'pending' as const,
                    createdAt: '2025-12-01T08:00:00',
                },
                {
                    id: 2,
                    leaveType: 'Cuti Sakit',
                    startDate: '2025-11-28',
                    endDate: '2025-11-29',
                    days: 2,
                    reason: 'Sakit demam',
                    status: 'approved' as const,
                    approvedBy: 'Manager HR',
                    approvedAt: '2025-11-27T10:30:00',
                    createdAt: '2025-11-27T08:00:00',
                },
                {
                    id: 3,
                    leaveType: 'Cuti Tahunan',
                    startDate: '2025-11-15',
                    endDate: '2025-11-16',
                    days: 2,
                    reason: 'Keperluan pribadi',
                    status: 'rejected' as const,
                    approvedBy: 'Manager HR',
                    rejectedReason: 'Sudah ada karyawan lain yang cuti pada tanggal tersebut',
                    createdAt: '2025-11-10T08:00:00',
                },
            ] as LeaveRequest[];
        },
    });

    // Create leave request mutation
    const createLeaveMutation = useMutation({
        mutationFn: async (data: { leaveType: string; startDate: string; endDate: string; reason: string }) => {
            // TODO: Replace with actual API call
            console.log('Creating leave request:', data);
            return { success: true };
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['employee', 'leave-requests'] });
            queryClient.invalidateQueries({ queryKey: ['employee', 'leave-balance'] });
            setShowRequestForm(false);
            resetForm();
        },
    });

    // Cancel leave request mutation
    const cancelLeaveMutation = useMutation({
        mutationFn: async (id: number) => {
            // TODO: Replace with actual API call
            console.log('Canceling leave request:', id);
            return { success: true };
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['employee', 'leave-requests'] });
            queryClient.invalidateQueries({ queryKey: ['employee', 'leave-balance'] });
        },
    });

    const resetForm = () => {
        setLeaveType('');
        setStartDate('');
        setEndDate('');
        setReason('');
    };

    const handleSubmitRequest = (e: React.FormEvent) => {
        e.preventDefault();
        createLeaveMutation.mutate({
            leaveType,
            startDate,
            endDate,
            reason,
        });
    };

    const handleCancelRequest = (id: number) => {
        if (confirm('Apakah Anda yakin ingin membatalkan pengajuan cuti ini?')) {
            cancelLeaveMutation.mutate(id);
        }
    };

    const getStatusColor = (status: string) => {
        switch (status) {
            case 'pending': return 'bg-yellow-100 text-yellow-700 border-yellow-200';
            case 'approved': return 'bg-green-100 text-green-700 border-green-200';
            case 'rejected': return 'bg-red-100 text-red-700 border-red-200';
            default: return 'bg-gray-100 text-gray-700 border-gray-200';
        }
    };

    const getStatusLabel = (status: string) => {
        switch (status) {
            case 'pending': return 'Menunggu';
            case 'approved': return 'Disetujui';
            case 'rejected': return 'Ditolak';
            default: return status;
        }
    };

    const filteredRequests = leaveRequests?.filter((request) => {
        if (filterStatus === 'all') return true;
        return request.status === filterStatus;
    });

    const calculateDays = () => {
        if (startDate && endDate) {
            const days = differenceInDays(parseISO(endDate), parseISO(startDate)) + 1;
            return days > 0 ? days : 0;
        }
        return 0;
    };

    return (
        <div className="min-h-screen bg-background pb-20">
            {/* Header */}
            <div className="sticky top-0 z-10 bg-background/80 backdrop-blur-md border-b p-4 flex items-center justify-between">
                <div className="flex items-center gap-3">
                    <Button variant="ghost" size="icon" onClick={() => window.history.back()}>
                        <ArrowLeft className="h-5 w-5" />
                    </Button>
                    <div>
                        <h1 className="text-lg font-bold">Cuti Saya</h1>
                        <p className="text-xs text-muted-foreground">Kelola pengajuan cuti</p>
                    </div>
                </div>
                <Sheet open={showRequestForm} onOpenChange={setShowRequestForm}>
                    <SheetTrigger asChild>
                        <Button size="icon" className="rounded-full">
                            <Plus className="h-5 w-5" />
                        </Button>
                    </SheetTrigger>
                    <SheetContent side="bottom" className="h-[90vh] rounded-t-xl">
                        <SheetHeader>
                            <SheetTitle>Ajukan Cuti Baru</SheetTitle>
                        </SheetHeader>
                        <form onSubmit={handleSubmitRequest} className="space-y-4 mt-4 overflow-y-auto h-full pb-20">
                            <div className="space-y-2">
                                <label className="text-sm font-medium">Jenis Cuti</label>
                                <select
                                    value={leaveType}
                                    onChange={(e) => setLeaveType(e.target.value)}
                                    className="w-full p-2 border rounded-md"
                                    required
                                >
                                    <option value="">Pilih jenis cuti</option>
                                    <option value="Cuti Tahunan">Cuti Tahunan</option>
                                    <option value="Cuti Sakit">Cuti Sakit</option>
                                    <option value="Cuti Penting">Cuti Penting</option>
                                    <option value="Cuti Melahirkan">Cuti Melahirkan</option>
                                </select>
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <label className="text-sm font-medium">Mulai</label>
                                    <Input
                                        type="date"
                                        value={startDate}
                                        onChange={(e) => setStartDate(e.target.value)}
                                        required
                                    />
                                </div>
                                <div className="space-y-2">
                                    <label className="text-sm font-medium">Selesai</label>
                                    <Input
                                        type="date"
                                        value={endDate}
                                        onChange={(e) => setEndDate(e.target.value)}
                                        min={startDate}
                                        required
                                    />
                                </div>
                            </div>

                            {startDate && endDate && (
                                <div className="p-3 bg-blue-50 rounded-lg flex justify-between items-center">
                                    <span className="text-sm">Durasi Cuti</span>
                                    <span className="font-bold text-blue-700">{calculateDays()} hari</span>
                                </div>
                            )}

                            <div className="space-y-2">
                                <label className="text-sm font-medium">Alasan</label>
                                <textarea
                                    value={reason}
                                    onChange={(e) => setReason(e.target.value)}
                                    className="w-full p-2 border rounded-md resize-none"
                                    rows={4}
                                    placeholder="Jelaskan alasan..."
                                    required
                                />
                            </div>

                            <Button type="submit" className="w-full" disabled={createLeaveMutation.isPending}>
                                {createLeaveMutation.isPending ? 'Mengirim...' : 'Ajukan Cuti'}
                            </Button>
                        </form>
                    </SheetContent>
                </Sheet>
            </div>

            {/* Balance Cards */}
            <div className="p-4 grid grid-cols-2 gap-3">
                <div className="p-4 bg-blue-50 border border-blue-100 rounded-xl">
                    <div className="flex items-center gap-2 mb-2">
                        <Plane className="h-4 w-4 text-blue-600" />
                        <span className="text-xs font-medium text-blue-700">Sisa Cuti</span>
                    </div>
                    <p className="text-2xl font-bold text-blue-700">{leaveBalance?.remaining || 0}</p>
                    <p className="text-xs text-blue-600">dari {leaveBalance?.total || 0} hari</p>
                </div>
                <div className="p-4 bg-yellow-50 border border-yellow-100 rounded-xl">
                    <div className="flex items-center gap-2 mb-2">
                        <Clock className="h-4 w-4 text-yellow-600" />
                        <span className="text-xs font-medium text-yellow-700">Pending</span>
                    </div>
                    <p className="text-2xl font-bold text-yellow-700">{leaveBalance?.pending || 0}</p>
                    <p className="text-xs text-yellow-600">pengajuan</p>
                </div>
            </div>

            {/* Filter */}
            <div className="px-4 mb-4 overflow-x-auto">
                <div className="flex gap-2">
                    {(['all', 'pending', 'approved', 'rejected'] as const).map((status) => (
                        <Badge
                            key={status}
                            variant={filterStatus === status ? 'default' : 'outline'}
                            className="cursor-pointer whitespace-nowrap"
                            onClick={() => setFilterStatus(status)}
                        >
                            {status === 'all' ? 'Semua' : getStatusLabel(status)}
                        </Badge>
                    ))}
                </div>
            </div>

            {/* List */}
            <div className="px-4 space-y-3">
                {isLoading ? (
                    <div className="text-center py-8 text-muted-foreground">Memuat data...</div>
                ) : filteredRequests?.map((request) => (
                    <div
                        key={request.id}
                        className="bg-card border rounded-xl p-4 shadow-sm space-y-3"
                    >
                        <div className="flex items-start justify-between">
                            <div>
                                <h3 className="font-semibold">{request.leaveType}</h3>
                                <p className="text-xs text-muted-foreground">
                                    {format(parseISO(request.startDate), 'dd MMM', { locale: id })} -{' '}
                                    {format(parseISO(request.endDate), 'dd MMM yyyy', { locale: id })}
                                </p>
                            </div>
                            <Badge variant="outline" className={getStatusColor(request.status)}>
                                {getStatusLabel(request.status)}
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
                                onClick={() => handleCancelRequest(request.id)}
                                disabled={cancelLeaveMutation.isPending}
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
