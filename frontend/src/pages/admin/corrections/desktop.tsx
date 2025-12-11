import { useState } from 'react';
import {
    CheckCircle,
    XCircle,
    Clock,
    Loader2,
    FileText,
    Calendar,
    AlertCircle,
} from 'lucide-react';
import { PageHeader } from '@/components/shared/PageHeader';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Textarea } from '@/components/ui/textarea';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
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

export default function DesktopCorrectionsPage() {
    const [searchQuery, setSearchQuery] = useState('');
    const [statusFilter, setStatusFilter] = useState<'all' | 'pending' | 'approved' | 'rejected' | 'cancelled'>('all');
    const [selectedCorrection, setSelectedCorrection] = useState<AttendanceCorrection | null>(null);
    const [isDetailDialogOpen, setIsDetailDialogOpen] = useState(false);
    const [isActionDialogOpen, setIsActionDialogOpen] = useState(false);
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
        setIsActionDialogOpen(true);
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

            setIsActionDialogOpen(false);
            setIsDetailDialogOpen(false);
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
                {CORRECTION_STATUS_LABELS[status] || status}
            </Badge>
        );
    };

    const pendingCorrections = corrections.filter((c) => c.status === 'pending');

    return (
        <div className="space-y-6 p-6">
            <PageHeader
                title="Manajemen Koreksi Absensi"
                description="Kelola permintaan koreksi absensi dari karyawan"
            />

            {/* Statistics */}
            <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                <Card className="p-4">
                    <div className="flex items-center gap-2">
                        <FileText className="h-5 w-5 text-blue-500" />
                        <div>
                            <p className="text-sm text-muted-foreground">Total</p>
                            <p className="text-xl font-bold">{stats?.total || 0}</p>
                        </div>
                    </div>
                </Card>
                <Card className="p-4">
                    <div className="flex items-center gap-2">
                        <Clock className="h-5 w-5 text-yellow-500" />
                        <div>
                            <p className="text-sm text-muted-foreground">Pending</p>
                            <p className="text-xl font-bold">{stats?.pending || 0}</p>
                        </div>
                    </div>
                </Card>
                <Card className="p-4">
                    <div className="flex items-center gap-2">
                        <CheckCircle className="h-5 w-5 text-green-500" />
                        <div>
                            <p className="text-sm text-muted-foreground">Disetujui</p>
                            <p className="text-xl font-bold">{stats?.approved || 0}</p>
                        </div>
                    </div>
                </Card>
                <Card className="p-4">
                    <div className="flex items-center gap-2">
                        <XCircle className="h-5 w-5 text-red-500" />
                        <div>
                            <p className="text-sm text-muted-foreground">Ditolak</p>
                            <p className="text-xl font-bold">{stats?.rejected || 0}</p>
                        </div>
                    </div>
                </Card>
            </div>

            {/* Filters */}
            <Card>
                <CardHeader>
                    <CardTitle className="text-lg">Filter & Pencarian</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="flex gap-4">
                        <div className="flex-1">
                            <Input
                                placeholder="Cari nama karyawan atau ID..."
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                                className="w-full"
                            />
                        </div>
                        <Select value={statusFilter} onValueChange={(v) => setStatusFilter(v as typeof statusFilter)}>
                            <SelectTrigger className="w-48">
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
                </CardContent>
            </Card>

            {/* Pending Corrections */}
            {pendingCorrections.length > 0 && (
                <Card>
                    <CardHeader>
                        <CardTitle className="text-lg flex items-center gap-2">
                            <Clock className="h-5 w-5 text-yellow-500" />
                            Menunggu Persetujuan ({pendingCorrections.length})
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Karyawan</TableHead>
                                    <TableHead>Tanggal</TableHead>
                                    <TableHead>Jenis Koreksi</TableHead>
                                    <TableHead>Diajukan</TableHead>
                                    <TableHead>Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {pendingCorrections.map((correction) => (
                                    <TableRow key={correction.id}>
                                        <TableCell>
                                            <div>
                                                <p className="font-medium">{correction.employee?.full_name}</p>
                                                <p className="text-sm text-muted-foreground">{correction.employee?.employee_id}</p>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex items-center gap-2">
                                                <Calendar className="h-4 w-4 text-muted-foreground" />
                                                {correction.correction_date}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            {CORRECTION_TYPE_LABELS[correction.correction_type]}
                                        </TableCell>
                                        <TableCell>
                                            {new Date(correction.created_at).toLocaleDateString('id-ID')}
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex gap-2">
                                                <Button
                                                    size="sm"
                                                    onClick={() => {
                                                        setSelectedCorrection(correction);
                                                        setIsDetailDialogOpen(true);
                                                    }}
                                                >
                                                    Lihat
                                                </Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            )}

            {/* All Corrections */}
            <Card>
                <CardHeader>
                    <CardTitle className="text-lg">Semua Koreksi</CardTitle>
                </CardHeader>
                <CardContent>
                    {isLoading ? (
                        <div className="flex justify-center py-8">
                            <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
                        </div>
                    ) : corrections.length === 0 ? (
                        <div className="text-center py-8 text-muted-foreground">
                            <FileText className="h-12 w-12 mx-auto mb-4 opacity-50" />
                            <p>Belum ada permintaan koreksi</p>
                        </div>
                    ) : (
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Karyawan</TableHead>
                                    <TableHead>Tanggal</TableHead>
                                    <TableHead>Jenis Koreksi</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Diajukan</TableHead>
                                    <TableHead>Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {corrections.map((correction) => (
                                    <TableRow key={correction.id}>
                                        <TableCell>
                                            <div>
                                                <p className="font-medium">{correction.employee?.full_name}</p>
                                                <p className="text-sm text-muted-foreground">{correction.employee?.employee_id}</p>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex items-center gap-2">
                                                <Calendar className="h-4 w-4 text-muted-foreground" />
                                                {correction.correction_date}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            {CORRECTION_TYPE_LABELS[correction.correction_type]}
                                        </TableCell>
                                        <TableCell>{getStatusBadge(correction.status)}</TableCell>
                                        <TableCell>
                                            {new Date(correction.created_at).toLocaleDateString('id-ID')}
                                        </TableCell>
                                        <TableCell>
                                            <Button
                                                size="sm"
                                                onClick={() => {
                                                    setSelectedCorrection(correction);
                                                    setIsDetailDialogOpen(true);
                                                }}
                                            >
                                                Lihat
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    )}
                </CardContent>
            </Card>

            {/* Detail Dialog */}
            <Dialog open={isDetailDialogOpen} onOpenChange={setIsDetailDialogOpen}>
                <DialogContent className="max-w-lg">
                    <DialogHeader>
                        <DialogTitle>Detail Koreksi</DialogTitle>
                    </DialogHeader>
                    {selectedCorrection && (
                        <div className="space-y-4">
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <p className="text-sm text-muted-foreground">Karyawan</p>
                                    <p className="font-medium">{selectedCorrection.employee?.full_name}</p>
                                    <p className="text-sm text-muted-foreground">{selectedCorrection.employee?.employee_id}</p>
                                </div>
                                <div className="text-right">
                                    <p className="text-sm text-muted-foreground">Tanggal</p>
                                    <p className="font-medium">{selectedCorrection.correction_date}</p>
                                    {getStatusBadge(selectedCorrection.status)}
                                </div>
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
                                        <p className="text-sm text-blue-600">
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
                    )}
                    <DialogFooter>
                        {selectedCorrection?.status === 'pending' && (
                            <>
                                <Button
                                    variant="outline"
                                    onClick={() => handleAction(selectedCorrection, 'reject')}
                                    className="text-red-600 border-red-600 hover:bg-red-50"
                                >
                                    Tolak
                                </Button>
                                <Button
                                    onClick={() => handleAction(selectedCorrection, 'approve')}
                                    className="text-green-600 bg-green-50 hover:bg-green-100"
                                >
                                    Setujui
                                </Button>
                            </>
                        )}
                        <Button variant="outline" onClick={() => setIsDetailDialogOpen(false)}>
                            Tutup
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Action Dialog */}
            <Dialog open={isActionDialogOpen} onOpenChange={setIsActionDialogOpen}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle>
                            {actionType === 'approve' ? 'Setujui Koreksi' : 'Tolak Koreksi'}
                        </DialogTitle>
                        <DialogDescription>
                            {actionType === 'approve'
                                ? 'Apakah Anda yakin ingin menyetujui koreksi ini?'
                                : 'Berikan alasan penolakan koreksi ini.'}
                        </DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4">
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
                                    rows={3}
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
                                    rows={3}
                                />
                            </div>
                        )}
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setIsActionDialogOpen(false)}>
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
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    );
}
