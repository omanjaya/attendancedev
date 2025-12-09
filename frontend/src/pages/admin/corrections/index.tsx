import { useState } from 'react';
import {
    CheckCircle,
    XCircle,
    Clock,
    Loader2,
    FileText,
    User,
    Calendar,
    AlertCircle,
    Search,
    Filter,
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

export default function AdminCorrectionsPage() {
    const [statusFilter, setStatusFilter] = useState<string>('pending');
    const [searchQuery, setSearchQuery] = useState('');
    const [selectedCorrection, setSelectedCorrection] = useState<AttendanceCorrection | null>(null);
    const [isDetailDialogOpen, setIsDetailDialogOpen] = useState(false);
    const [isRejectDialogOpen, setIsRejectDialogOpen] = useState(false);
    const [rejectNotes, setRejectNotes] = useState('');
    const [approveNotes, setApproveNotes] = useState('');

    // Fetch data
    const { data: correctionsData, isLoading } = useAttendanceCorrections({
        status: statusFilter === 'all' ? undefined : statusFilter as 'pending' | 'approved' | 'rejected',
        per_page: 50,
    });
    const { data: stats } = useCorrectionStatistics();

    // Mutations
    const approveMutation = useApproveCorrection();
    const rejectMutation = useRejectCorrection();

    const corrections = correctionsData?.data || [];

    // Filter by search
    const filteredCorrections = corrections.filter((c) => {
        if (!searchQuery) return true;
        const search = searchQuery.toLowerCase();
        return (
            c.employee?.full_name?.toLowerCase().includes(search) ||
            c.employee?.employee_id?.toLowerCase().includes(search) ||
            c.reason?.toLowerCase().includes(search)
        );
    });

    const handleViewDetail = (correction: AttendanceCorrection) => {
        setSelectedCorrection(correction);
        setIsDetailDialogOpen(true);
    };

    const handleApprove = async (correction: AttendanceCorrection) => {
        try {
            await approveMutation.mutateAsync({ id: correction.id, notes: approveNotes || undefined });
            toast.success('Koreksi berhasil disetujui');
            setIsDetailDialogOpen(false);
            setApproveNotes('');
        } catch {
            toast.error('Gagal menyetujui koreksi');
        }
    };

    const handleOpenReject = (correction: AttendanceCorrection) => {
        setSelectedCorrection(correction);
        setIsRejectDialogOpen(true);
    };

    const handleReject = async () => {
        if (!selectedCorrection) return;
        if (!rejectNotes || rejectNotes.length < 10) {
            toast.error('Alasan penolakan minimal 10 karakter');
            return;
        }

        try {
            await rejectMutation.mutateAsync({ id: selectedCorrection.id, notes: rejectNotes });
            toast.success('Koreksi berhasil ditolak');
            setIsRejectDialogOpen(false);
            setIsDetailDialogOpen(false);
            setRejectNotes('');
        } catch {
            toast.error('Gagal menolak koreksi');
        }
    };

    const getStatusBadge = (status: string) => {
        const variants: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
            pending: 'secondary',
            approved: 'default',
            rejected: 'destructive',
            cancelled: 'outline',
        };
        return (
            <Badge variant={variants[status] || 'outline'}>
                {CORRECTION_STATUS_LABELS[status] || status}
            </Badge>
        );
    };

    return (
        <div className="space-y-6 p-6">
            <PageHeader
                title="Koreksi Absensi"
                description="Kelola permintaan koreksi absensi dari karyawan"
            />

            {/* Statistics Cards */}
            <div className="grid gap-4 md:grid-cols-4">
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle className="text-sm font-medium">Menunggu</CardTitle>
                        <Clock className="h-4 w-4 text-yellow-500" />
                    </CardHeader>
                    <CardContent>
                        <div className="text-2xl font-bold">{stats?.pending || 0}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle className="text-sm font-medium">Disetujui</CardTitle>
                        <CheckCircle className="h-4 w-4 text-green-500" />
                    </CardHeader>
                    <CardContent>
                        <div className="text-2xl font-bold">{stats?.approved || 0}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle className="text-sm font-medium">Ditolak</CardTitle>
                        <XCircle className="h-4 w-4 text-red-500" />
                    </CardHeader>
                    <CardContent>
                        <div className="text-2xl font-bold">{stats?.rejected || 0}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle className="text-sm font-medium">Bulan Ini</CardTitle>
                        <Calendar className="h-4 w-4 text-blue-500" />
                    </CardHeader>
                    <CardContent>
                        <div className="text-2xl font-bold">{stats?.this_month || 0}</div>
                    </CardContent>
                </Card>
            </div>

            {/* Filters */}
            <Card>
                <CardContent className="pt-6">
                    <div className="flex flex-col sm:flex-row gap-4">
                        <div className="relative flex-1 max-w-md">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                            <Input
                                placeholder="Cari nama karyawan atau alasan..."
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                                className="pl-9"
                            />
                        </div>
                        <div className="flex gap-2 items-center">
                            <Filter className="h-4 w-4 text-muted-foreground" />
                            <Select value={statusFilter} onValueChange={setStatusFilter}>
                                <SelectTrigger className="w-[180px]">
                                    <SelectValue placeholder="Filter Status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Semua Status</SelectItem>
                                    <SelectItem value="pending">Menunggu</SelectItem>
                                    <SelectItem value="approved">Disetujui</SelectItem>
                                    <SelectItem value="rejected">Ditolak</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </CardContent>
            </Card>

            {/* Corrections Table */}
            <Card>
                <CardContent className="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Karyawan</TableHead>
                                <TableHead>Tanggal</TableHead>
                                <TableHead>Jenis Koreksi</TableHead>
                                <TableHead>Alasan</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="text-right">Aksi</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {isLoading ? (
                                <TableRow>
                                    <TableCell colSpan={6} className="text-center py-10">
                                        <Loader2 className="h-6 w-6 animate-spin mx-auto text-muted-foreground" />
                                    </TableCell>
                                </TableRow>
                            ) : filteredCorrections.length === 0 ? (
                                <TableRow>
                                    <TableCell colSpan={6} className="text-center py-10 text-muted-foreground">
                                        Tidak ada permintaan koreksi
                                    </TableCell>
                                </TableRow>
                            ) : (
                                filteredCorrections.map((correction) => (
                                    <TableRow key={correction.id}>
                                        <TableCell>
                                            <div className="flex items-center gap-2">
                                                <User className="h-4 w-4 text-muted-foreground" />
                                                <div>
                                                    <div className="font-medium">
                                                        {correction.employee?.full_name || '-'}
                                                    </div>
                                                    <div className="text-xs text-muted-foreground">
                                                        {correction.employee?.employee_id || '-'}
                                                    </div>
                                                </div>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            {new Date(correction.correction_date).toLocaleDateString('id-ID', {
                                                day: 'numeric',
                                                month: 'short',
                                                year: 'numeric',
                                            })}
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant="outline">
                                                {CORRECTION_TYPE_LABELS[correction.correction_type] || correction.correction_type}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="max-w-[200px] truncate">
                                            {correction.reason}
                                        </TableCell>
                                        <TableCell>{getStatusBadge(correction.status)}</TableCell>
                                        <TableCell className="text-right">
                                            <div className="flex justify-end gap-2">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() => handleViewDetail(correction)}
                                                >
                                                    <FileText className="h-4 w-4" />
                                                </Button>
                                                {correction.status === 'pending' && (
                                                    <>
                                                        <Button
                                                            variant="default"
                                                            size="sm"
                                                            onClick={() => handleApprove(correction)}
                                                            disabled={approveMutation.isPending}
                                                        >
                                                            <CheckCircle className="h-4 w-4" />
                                                        </Button>
                                                        <Button
                                                            variant="destructive"
                                                            size="sm"
                                                            onClick={() => handleOpenReject(correction)}
                                                            disabled={rejectMutation.isPending}
                                                        >
                                                            <XCircle className="h-4 w-4" />
                                                        </Button>
                                                    </>
                                                )}
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            {/* Detail Dialog */}
            <Dialog open={isDetailDialogOpen} onOpenChange={setIsDetailDialogOpen}>
                <DialogContent className="max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>Detail Permintaan Koreksi</DialogTitle>
                        <DialogDescription>
                            Diajukan oleh {selectedCorrection?.employee?.full_name}
                        </DialogDescription>
                    </DialogHeader>
                    {selectedCorrection && (
                        <div className="space-y-4">
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className="text-sm font-medium text-muted-foreground">Tanggal Koreksi</label>
                                    <p className="text-sm">
                                        {new Date(selectedCorrection.correction_date).toLocaleDateString('id-ID', {
                                            weekday: 'long',
                                            day: 'numeric',
                                            month: 'long',
                                            year: 'numeric',
                                        })}
                                    </p>
                                </div>
                                <div>
                                    <label className="text-sm font-medium text-muted-foreground">Jenis Koreksi</label>
                                    <p className="text-sm">
                                        {CORRECTION_TYPE_LABELS[selectedCorrection.correction_type]}
                                    </p>
                                </div>
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className="text-sm font-medium text-muted-foreground">Jam Asli</label>
                                    <p className="text-sm">
                                        Check-in: {selectedCorrection.original_check_in || '-'}<br />
                                        Check-out: {selectedCorrection.original_check_out || '-'}
                                    </p>
                                </div>
                                <div>
                                    <label className="text-sm font-medium text-muted-foreground">Jam yang Diminta</label>
                                    <p className="text-sm">
                                        Check-in: {selectedCorrection.requested_check_in || '-'}<br />
                                        Check-out: {selectedCorrection.requested_check_out || '-'}
                                    </p>
                                </div>
                            </div>

                            <div>
                                <label className="text-sm font-medium text-muted-foreground">Alasan</label>
                                <p className="text-sm bg-muted p-3 rounded-md mt-1">
                                    {selectedCorrection.reason}
                                </p>
                            </div>

                            {selectedCorrection.supporting_document && (
                                <div>
                                    <label className="text-sm font-medium text-muted-foreground">Dokumen Pendukung</label>
                                    <Button variant="link" className="p-0 h-auto" asChild>
                                        <a
                                            href={`/api/v1/attendance-corrections/${selectedCorrection.id}/document`}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                        >
                                            Lihat Dokumen
                                        </a>
                                    </Button>
                                </div>
                            )}

                            <div className="flex items-center gap-2">
                                <label className="text-sm font-medium text-muted-foreground">Status:</label>
                                {getStatusBadge(selectedCorrection.status)}
                            </div>

                            {selectedCorrection.review_notes && (
                                <div>
                                    <label className="text-sm font-medium text-muted-foreground">Catatan Review</label>
                                    <p className="text-sm bg-muted p-3 rounded-md mt-1">
                                        {selectedCorrection.review_notes}
                                    </p>
                                </div>
                            )}

                            {selectedCorrection.status === 'pending' && (
                                <div>
                                    <label className="text-sm font-medium text-muted-foreground">
                                        Catatan (Opsional)
                                    </label>
                                    <Textarea
                                        placeholder="Tambahkan catatan untuk approval..."
                                        value={approveNotes}
                                        onChange={(e) => setApproveNotes(e.target.value)}
                                        className="mt-1"
                                    />
                                </div>
                            )}
                        </div>
                    )}
                    <DialogFooter>
                        {selectedCorrection?.status === 'pending' && (
                            <>
                                <Button
                                    variant="outline"
                                    onClick={() => handleOpenReject(selectedCorrection)}
                                    disabled={rejectMutation.isPending}
                                >
                                    <XCircle className="h-4 w-4 mr-2" />
                                    Tolak
                                </Button>
                                <Button
                                    onClick={() => handleApprove(selectedCorrection)}
                                    disabled={approveMutation.isPending}
                                >
                                    {approveMutation.isPending && <Loader2 className="h-4 w-4 mr-2 animate-spin" />}
                                    <CheckCircle className="h-4 w-4 mr-2" />
                                    Setujui
                                </Button>
                            </>
                        )}
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Reject Dialog */}
            <Dialog open={isRejectDialogOpen} onOpenChange={setIsRejectDialogOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <AlertCircle className="h-5 w-5 text-destructive" />
                            Tolak Permintaan Koreksi
                        </DialogTitle>
                        <DialogDescription>
                            Berikan alasan penolakan untuk karyawan.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4">
                        <div>
                            <label className="text-sm font-medium">Alasan Penolakan *</label>
                            <Textarea
                                placeholder="Jelaskan alasan penolakan (minimal 10 karakter)..."
                                value={rejectNotes}
                                onChange={(e) => setRejectNotes(e.target.value)}
                                className="mt-1"
                                rows={4}
                            />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setIsRejectDialogOpen(false)}>
                            Batal
                        </Button>
                        <Button
                            variant="destructive"
                            onClick={handleReject}
                            disabled={rejectMutation.isPending || rejectNotes.length < 10}
                        >
                            {rejectMutation.isPending && <Loader2 className="h-4 w-4 mr-2 animate-spin" />}
                            Tolak Koreksi
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    );
}
