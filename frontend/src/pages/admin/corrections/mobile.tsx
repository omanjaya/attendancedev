import { useState } from 'react';
import {
    CheckCircle,
    XCircle,
    Clock,
    Loader2,
    FileText,
    AlertCircle,
    ChevronRight,
} from 'lucide-react';
import { MobilePageHeader, MobileEmptyState } from '@/components/mobile';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Textarea } from '@/components/ui/textarea';
import { Skeleton } from '@/components/ui/skeleton';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetFooter,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { toast } from 'sonner';
import {
    useAttendanceCorrections,
    useCorrectionStatistics,
    useApproveCorrection,
    useRejectCorrection,
} from '@/hooks/use-attendance-corrections';
import {
    CORRECTION_STATUS_LABELS,
    CORRECTION_TYPE_LABELS,
    type AttendanceCorrection,
} from '@/lib/api/attendance-corrections';

export default function MobileCorrectionsPage() {
    const [searchQuery, setSearchQuery] = useState('');
    const [statusFilter, setStatusFilter] = useState<'all' | 'pending' | 'approved' | 'rejected' | 'cancelled'>('all');
    const [selectedCorrection, setSelectedCorrection] = useState<AttendanceCorrection | null>(null);
    const [isDetailSheetOpen, setIsDetailSheetOpen] = useState(false);
    const [isActionSheetOpen, setIsActionSheetOpen] = useState(false);
    const [actionType, setActionType] = useState<'approve' | 'reject'>('approve');
    const [reviewNotes, setReviewNotes] = useState('');

    // Fetch corrections with filters
    const { data: correctionsData, isLoading, refetch } = useAttendanceCorrections({
        status: statusFilter === 'all' ? undefined : statusFilter,
        per_page: 50,
    });

    // Fetch statistics
    const { data: stats } = useCorrectionStatistics();

    const corrections = correctionsData?.data || [];

    // Mutations
    const approveMutation = useApproveCorrection();
    const rejectMutation = useRejectCorrection();

    const handleAction = async (correction: AttendanceCorrection, action: 'approve' | 'reject') => {
        setSelectedCorrection(correction);
        setActionType(action);
        setIsActionSheetOpen(true);
    };

    const submitAction = async () => {
        if (!selectedCorrection) return;

        try {
            if (actionType === 'approve') {
                await approveMutation.mutateAsync({
                    id: selectedCorrection.id,
                    notes: reviewNotes || undefined,
                });
                toast.success('Koreksi disetujui');
            } else {
                if (!reviewNotes.trim()) {
                    toast.error('Alasan penolakan wajib diisi');
                    return;
                }
                await rejectMutation.mutateAsync({
                    id: selectedCorrection.id,
                    notes: reviewNotes,
                });
                toast.success('Koreksi ditolak');
            }

            setIsActionSheetOpen(false);
            setIsDetailSheetOpen(false);
            setReviewNotes('');
            setSelectedCorrection(null);
            refetch();
        } catch (error: unknown) {
            const err = error as { response?: { data?: { message?: string } } };
            toast.error(err.response?.data?.message || 'Gagal memproses koreksi');
        }
    };

    const getStatusBadge = (status: string) => {
        const variants: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
            pending: 'secondary',
            approved: 'default',
            rejected: 'destructive',
            cancelled: 'outline',
        };
        const icons: Record<string, React.ReactNode> = {
            pending: <Clock className="h-3 w-3 mr-1" />,
            approved: <CheckCircle className="h-3 w-3 mr-1" />,
            rejected: <XCircle className="h-3 w-3 mr-1" />,
            cancelled: <Clock className="h-3 w-3 mr-1" />,
        };
        return (
            <Badge variant={variants[status] || 'outline'} className="flex items-center w-fit">
                {icons[status]}
                <span className="text-xs">{CORRECTION_STATUS_LABELS[status] || status}</span>
            </Badge>
        );
    };

    const pendingCorrections = corrections.filter((c) => c.status === 'pending');

    if (isLoading) {
        return (
            <div className="min-h-screen bg-background pb-24">
                <MobilePageHeader title="Manajemen Koreksi" gradient="blue" backTo="/admin/dashboard" />
                <div className="px-4 space-y-4">
                    <div className="grid grid-cols-2 gap-3">
                        <Skeleton className="h-16 rounded-2xl" />
                        <Skeleton className="h-16 rounded-2xl" />
                    </div>
                    <Skeleton className="h-24 rounded-2xl" />
                    {[1, 2, 3].map((i) => (
                        <Skeleton key={i} className="h-20 rounded-2xl" />
                    ))}
                </div>
            </div>
        );
    }

    return (
        <div className="min-h-screen bg-background pb-24">
            <MobilePageHeader title="Manajemen Koreksi" gradient="blue" backTo="/admin/dashboard" />

            <div className="px-4 space-y-4">
            {/* Statistics */}
            <div className="grid grid-cols-2 gap-3">
                <div className="p-4 rounded-2xl bg-amber-50 dark:bg-amber-900/20 shadow-sm dark:border dark:border-border/50">
                    <div className="flex items-center gap-2">
                        <Clock className="h-5 w-5 text-amber-600 dark:text-amber-400 flex-shrink-0" />
                        <div className="min-w-0">
                            <p className="text-xs text-muted-foreground">Pending</p>
                            <p className="text-lg font-bold text-foreground">{stats?.pending || 0}</p>
                        </div>
                    </div>
                </div>
                <div className="p-4 rounded-2xl bg-blue-50 dark:bg-blue-900/20 shadow-sm dark:border dark:border-border/50">
                    <div className="flex items-center gap-2">
                        <FileText className="h-5 w-5 text-blue-600 dark:text-blue-400 flex-shrink-0" />
                        <div className="min-w-0">
                            <p className="text-xs text-muted-foreground">Total</p>
                            <p className="text-lg font-bold text-foreground">{stats?.total || 0}</p>
                        </div>
                    </div>
                </div>
            </div>

            {/* Search and Filter */}
            <div className="bg-card rounded-2xl shadow-sm dark:border dark:border-border/50 p-4 space-y-3">
                <Input
                    placeholder="Cari nama karyawan..."
                    value={searchQuery}
                    onChange={(e) => setSearchQuery(e.target.value)}
                />
                <Select value={statusFilter} onValueChange={(v) => setStatusFilter(v as typeof statusFilter)}>
                    <SelectTrigger>
                        <SelectValue placeholder="Filter Status" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Semua Status</SelectItem>
                        <SelectItem value="pending">Pending</SelectItem>
                        <SelectItem value="approved">Disetujui</SelectItem>
                        <SelectItem value="rejected">Ditolak</SelectItem>
                        <SelectItem value="cancelled">Dibatalkan</SelectItem>
                    </SelectContent>
                </Select>
            </div>

            {/* Pending Corrections */}
            {pendingCorrections.length > 0 && (
                <div className="bg-card rounded-2xl shadow-sm dark:border dark:border-border/50">
                    <div className="p-4 border-b border-border/50">
                        <h3 className="text-sm font-bold text-foreground flex items-center gap-2">
                            <Clock className="h-4 w-4 text-amber-600 dark:text-amber-400" />
                            Pending ({pendingCorrections.length})
                        </h3>
                    </div>
                    <div className="p-4 space-y-2">
                        {pendingCorrections.map((correction) => (
                            <div
                                key={correction.id}
                                className="flex items-center justify-between p-3 border border-border/50 rounded-xl active:bg-muted/50"
                                onClick={() => {
                                    setSelectedCorrection(correction);
                                    setIsDetailSheetOpen(true);
                                }}
                            >
                                <div className="flex-1 min-w-0">
                                    <p className="font-medium text-sm truncate">{correction.employee?.full_name}</p>
                                    <p className="text-xs text-muted-foreground truncate">{correction.employee?.employee_id}</p>
                                    <div className="flex items-center gap-2 mt-1">
                                        <Badge variant="outline" className="text-xs">
                                            {correction.correction_date}
                                        </Badge>
                                        <span className="text-xs text-muted-foreground">
                                            {CORRECTION_TYPE_LABELS[correction.correction_type]}
                                        </span>
                                    </div>
                                </div>
                                <div className="flex items-center gap-2">
                                    {getStatusBadge(correction.status)}
                                    <ChevronRight className="h-4 w-4 text-muted-foreground flex-shrink-0" />
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            )}

            {/* All Corrections */}
            <div className="bg-card rounded-2xl shadow-sm dark:border dark:border-border/50">
                <div className="p-4 border-b border-border/50">
                    <h3 className="text-sm font-bold text-foreground">Semua Koreksi</h3>
                </div>
                <div className="p-4">
                    {corrections.length === 0 ? (
                        <MobileEmptyState
                            icon={FileText}
                            title="Belum Ada Koreksi"
                            description="Belum ada permintaan koreksi absensi"
                        />
                    ) : (
                        <div className="space-y-2">
                            {corrections.map((correction) => (
                                <div
                                    key={correction.id}
                                    className="flex items-center justify-between p-3 border border-border/50 rounded-xl active:bg-muted/50"
                                    onClick={() => {
                                        setSelectedCorrection(correction);
                                        setIsDetailSheetOpen(true);
                                    }}
                                >
                                    <div className="flex-1 min-w-0">
                                        <p className="font-medium text-sm truncate">{correction.employee?.full_name}</p>
                                        <p className="text-xs text-muted-foreground truncate">{correction.employee?.employee_id}</p>
                                        <div className="flex items-center gap-2 mt-1">
                                            <Badge variant="outline" className="text-xs">
                                                {correction.correction_date}
                                            </Badge>
                                            <span className="text-xs text-muted-foreground">
                                                {CORRECTION_TYPE_LABELS[correction.correction_type]}
                                            </span>
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        {getStatusBadge(correction.status)}
                                        <ChevronRight className="h-4 w-4 text-muted-foreground flex-shrink-0" />
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            </div>
            </div>

            {/* Detail Sheet */}
            <Sheet open={isDetailSheetOpen} onOpenChange={setIsDetailSheetOpen}>
                <SheetContent side="bottom" className="h-[90vh]">
                    <SheetHeader>
                        <SheetTitle>Detail Koreksi</SheetTitle>
                    </SheetHeader>
                    {selectedCorrection && (
                        <div className="mt-6 space-y-4 overflow-y-auto max-h-[calc(90vh-180px)]">
                            <div className="space-y-3">
                                <div className="flex justify-between items-start">
                                    <div className="flex-1 min-w-0">
                                        <p className="text-sm text-muted-foreground">Karyawan</p>
                                        <p className="font-medium text-sm">{selectedCorrection.employee?.full_name}</p>
                                        <p className="text-xs text-muted-foreground">{selectedCorrection.employee?.employee_id}</p>
                                    </div>
                                    {getStatusBadge(selectedCorrection.status)}
                                </div>

                                <div>
                                    <p className="text-sm text-muted-foreground">Tanggal</p>
                                    <p className="font-medium">{selectedCorrection.correction_date}</p>
                                </div>

                                <div>
                                    <p className="text-sm text-muted-foreground">Jenis Koreksi</p>
                                    <p className="font-medium">
                                        {CORRECTION_TYPE_LABELS[selectedCorrection.correction_type]}
                                    </p>
                                </div>

                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <p className="text-sm text-muted-foreground">Jam Asli</p>
                                        <p className="text-sm">
                                            Masuk: {selectedCorrection.original_check_in || '-'}<br />
                                            Keluar: {selectedCorrection.original_check_out || '-'}
                                        </p>
                                    </div>
                                    <div>
                                        <p className="text-sm text-muted-foreground">Jam yang Diminta</p>
                                        <p className="text-sm">
                                            Masuk: {selectedCorrection.requested_check_in || '-'}<br />
                                            Keluar: {selectedCorrection.requested_check_out || '-'}
                                        </p>
                                    </div>
                                </div>

                                <div>
                                    <p className="text-sm text-muted-foreground">Alasan</p>
                                    <p className="text-sm bg-muted p-3 rounded-md mt-1">
                                        {selectedCorrection.reason}
                                    </p>
                                </div>

                                {selectedCorrection.supporting_document && (
                                    <div>
                                        <p className="text-sm text-muted-foreground">Dokumen Pendukung</p>
                                        <div className="bg-muted p-2 rounded-md mt-1">
                                            <p className="text-xs text-blue-600">
                                                📎 {selectedCorrection.supporting_document}
                                            </p>
                                        </div>
                                    </div>
                                )}

                                {selectedCorrection.review_notes && (
                                    <div className="p-3 rounded-md border-l-4 border-primary bg-primary/5">
                                        <p className="text-sm font-medium flex items-center gap-2">
                                            <AlertCircle className="h-4 w-4" />
                                            Catatan Admin
                                        </p>
                                        <p className="text-sm mt-1">{selectedCorrection.review_notes}</p>
                                    </div>
                                )}
                            </div>
                        </div>
                    )}
                    <SheetFooter>
                        {selectedCorrection?.status === 'pending' && (
                            <div className="grid grid-cols-2 gap-3 w-full">
                                <Button
                                    variant="outline"
                                    onClick={() => handleAction(selectedCorrection, 'reject')}
                                    className="text-red-600 border-red-600 hover:bg-red-50"
                                >
                                    Tolak
                                </Button>
                                <Button
                                    onClick={() => handleAction(selectedCorrection, 'approve')}
                                    className="text-white bg-green-600 hover:bg-green-700"
                                >
                                    Setujui
                                </Button>
                            </div>
                        )}
                        <Button variant="outline" onClick={() => setIsDetailSheetOpen(false)} className="w-full">
                            Tutup
                        </Button>
                    </SheetFooter>
                </SheetContent>
            </Sheet>

            {/* Action Sheet */}
            <Sheet open={isActionSheetOpen} onOpenChange={setIsActionSheetOpen}>
                <SheetContent side="bottom" className="h-[60vh]">
                    <SheetHeader>
                        <SheetTitle>
                            {actionType === 'approve' ? 'Setujui Koreksi' : 'Tolak Koreksi'}
                        </SheetTitle>
                        <SheetDescription>
                            {actionType === 'approve'
                                ? 'Apakah Anda yakin ingin menyetujui koreksi ini?'
                                : 'Berikan alasan penolakan koreksi ini.'}
                        </SheetDescription>
                    </SheetHeader>
                    <div className="mt-6 space-y-4">
                        {actionType === 'reject' && (
                            <div className="space-y-2">
                                <label htmlFor="review-notes" className="text-sm font-medium">
                                    Alasan Penolakan *
                                </label>
                                <Textarea
                                    id="review-notes"
                                    placeholder="Jelaskan alasan penolakan..."
                                    value={reviewNotes}
                                    onChange={(e) => setReviewNotes(e.target.value)}
                                    rows={4}
                                />
                            </div>
                        )}
                        {actionType === 'approve' && (
                            <div className="space-y-2">
                                <label htmlFor="review-notes" className="text-sm font-medium">
                                    Catatan (Opsional)
                                </label>
                                <Textarea
                                    id="review-notes"
                                    placeholder="Tambahkan catatan jika diperlukan..."
                                    value={reviewNotes}
                                    onChange={(e) => setReviewNotes(e.target.value)}
                                    rows={4}
                                />
                            </div>
                        )}
                    </div>
                    <SheetFooter className="mt-6">
                        <div className="grid grid-cols-2 gap-3 w-full">
                            <Button variant="outline" onClick={() => setIsActionSheetOpen(false)}>
                                Batal
                            </Button>
                            <Button
                                onClick={submitAction}
                                disabled={approveMutation.isPending || rejectMutation.isPending}
                                className={actionType === 'approve' ? 'bg-green-600 hover:bg-green-700' : 'bg-red-600 hover:bg-red-700'}
                            >
                                {(approveMutation.isPending || rejectMutation.isPending) && (
                                    <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                                )}
                                {actionType === 'approve' ? 'Setujui' : 'Tolak'}
                            </Button>
                        </div>
                    </SheetFooter>
                </SheetContent>
            </Sheet>
        </div>
    );
}
