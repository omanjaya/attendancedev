import { useState } from 'react';
import { format } from 'date-fns';
import { id } from 'date-fns/locale';
import {
    Plus,
    Clock,
    CheckCircle,
    XCircle,
    AlertCircle,
    Loader2,
    Calendar,
    FileText,
    Upload,
    X,
} from 'lucide-react';
import { PageHeader } from '@/components/shared/PageHeader';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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
import { toast } from 'sonner';
import {
    useAttendanceCorrections,
    useCreateCorrection,
    useCancelCorrection,
} from '@/hooks/use-attendance-corrections';
import {
    CORRECTION_STATUS_LABELS,
    CORRECTION_TYPE_LABELS,
    type AttendanceCorrection,
} from '@/lib/api/attendance-corrections';

export default function EmployeeCorrectionsPage() {
    const [isCreateDialogOpen, setIsCreateDialogOpen] = useState(false);
    const [selectedCorrection, setSelectedCorrection] = useState<AttendanceCorrection | null>(null);
    const [isDetailDialogOpen, setIsDetailDialogOpen] = useState(false);

    // Form state
    const [formData, setFormData] = useState({
        correction_date: '',
        correction_type: 'both' as 'check_in' | 'check_out' | 'both' | 'add_missing' | 'delete',
        requested_check_in: '',
        requested_check_out: '',
        reason: '',
    });
    const [documentFile, setDocumentFile] = useState<File | null>(null);

    // Fetch corrections
    const { data: correctionsData, isLoading } = useAttendanceCorrections({
        per_page: 50,
    });
    const corrections = correctionsData?.data || [];

    // Mutations
    const createMutation = useCreateCorrection();
    const cancelMutation = useCancelCorrection();

    const resetForm = () => {
        setFormData({
            correction_date: '',
            correction_type: 'both',
            requested_check_in: '',
            requested_check_out: '',
            reason: '',
        });
        setDocumentFile(null);
    };

    const handleCreateSubmit = async () => {
        if (!formData.correction_date || !formData.reason) {
            toast.error('Tanggal dan alasan wajib diisi');
            return;
        }

        if (formData.reason.length < 10) {
            toast.error('Alasan minimal 10 karakter');
            return;
        }

        try {
            await createMutation.mutateAsync({
                correction_date: formData.correction_date,
                correction_type: formData.correction_type,
                requested_check_in: formData.requested_check_in || undefined,
                requested_check_out: formData.requested_check_out || undefined,
                reason: formData.reason,
                supporting_document: documentFile || undefined,
            });
            toast.success('Permintaan koreksi berhasil diajukan');
            setIsCreateDialogOpen(false);
            resetForm();
        } catch (error: unknown) {
            const err = error as { response?: { data?: { message?: string } } };
            toast.error(err.response?.data?.message || 'Gagal mengajukan koreksi');
        }
    };

    const handleCancel = async (correction: AttendanceCorrection) => {
        try {
            await cancelMutation.mutateAsync(correction.id);
            toast.success('Permintaan koreksi dibatalkan');
            setIsDetailDialogOpen(false);
        } catch {
            toast.error('Gagal membatalkan koreksi');
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
            cancelled: <X className="h-3 w-3 mr-1" />,
        };
        return (
            <Badge variant={variants[status] || 'outline'} className="flex items-center w-fit">
                {icons[status]}
                {CORRECTION_STATUS_LABELS[status] || status}
            </Badge>
        );
    };

    // Group corrections by status
    const pendingCorrections = corrections.filter((c) => c.status === 'pending');
    const processedCorrections = corrections.filter((c) => c.status !== 'pending');

    return (
        <div className="space-y-6 p-6">
            <PageHeader
                title="Koreksi Absensi"
                description="Ajukan koreksi jika ada kesalahan pada absensi Anda"
            />

            {/* Quick Actions */}
            <div className="flex justify-between items-center">
                <div className="flex gap-4">
                    <Card className="p-4">
                        <div className="flex items-center gap-2">
                            <Clock className="h-5 w-5 text-yellow-500" />
                            <div>
                                <p className="text-sm text-muted-foreground">Menunggu</p>
                                <p className="text-xl font-bold">{pendingCorrections.length}</p>
                            </div>
                        </div>
                    </Card>
                    <Card className="p-4">
                        <div className="flex items-center gap-2">
                            <FileText className="h-5 w-5 text-blue-500" />
                            <div>
                                <p className="text-sm text-muted-foreground">Total</p>
                                <p className="text-xl font-bold">{corrections.length}</p>
                            </div>
                        </div>
                    </Card>
                </div>
                <Button onClick={() => setIsCreateDialogOpen(true)}>
                    <Plus className="h-4 w-4 mr-2" />
                    Ajukan Koreksi
                </Button>
            </div>

            {/* Pending Corrections */}
            {pendingCorrections.length > 0 && (
                <Card>
                    <CardHeader>
                        <CardTitle className="text-lg flex items-center gap-2">
                            <Clock className="h-5 w-5 text-yellow-500" />
                            Menunggu Persetujuan
                        </CardTitle>
                        <CardDescription>
                            Permintaan koreksi yang sedang diproses
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {pendingCorrections.map((correction) => (
                            <div
                                key={correction.id}
                                className="flex items-center justify-between p-4 border rounded-lg hover:bg-muted/50 cursor-pointer"
                                onClick={() => {
                                    setSelectedCorrection(correction);
                                    setIsDetailDialogOpen(true);
                                }}
                            >
                                <div className="space-y-1">
                                    <div className="flex items-center gap-2">
                                        <Calendar className="h-4 w-4 text-muted-foreground" />
                                        <span className="font-medium">
                                            {format(new Date(correction.correction_date), 'EEEE, d MMMM yyyy', { locale: id })}
                                        </span>
                                    </div>
                                    <p className="text-sm text-muted-foreground">
                                        {CORRECTION_TYPE_LABELS[correction.correction_type]}
                                    </p>
                                </div>
                                <div className="flex items-center gap-4">
                                    {getStatusBadge(correction.status)}
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={(e) => {
                                            e.stopPropagation();
                                            handleCancel(correction);
                                        }}
                                        disabled={cancelMutation.isPending}
                                    >
                                        Batalkan
                                    </Button>
                                </div>
                            </div>
                        ))}
                    </CardContent>
                </Card>
            )}

            {/* History */}
            <Card>
                <CardHeader>
                    <CardTitle className="text-lg">Riwayat Koreksi</CardTitle>
                    <CardDescription>
                        Semua permintaan koreksi yang pernah diajukan
                    </CardDescription>
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
                        <div className="space-y-3">
                            {processedCorrections.map((correction) => (
                                <div
                                    key={correction.id}
                                    className="flex items-center justify-between p-4 border rounded-lg hover:bg-muted/50 cursor-pointer"
                                    onClick={() => {
                                        setSelectedCorrection(correction);
                                        setIsDetailDialogOpen(true);
                                    }}
                                >
                                    <div className="space-y-1">
                                        <div className="flex items-center gap-2">
                                            <Calendar className="h-4 w-4 text-muted-foreground" />
                                            <span className="font-medium">
                                                {format(new Date(correction.correction_date), 'd MMM yyyy', { locale: id })}
                                            </span>
                                        </div>
                                        <p className="text-sm text-muted-foreground">
                                            {CORRECTION_TYPE_LABELS[correction.correction_type]}
                                        </p>
                                    </div>
                                    {getStatusBadge(correction.status)}
                                </div>
                            ))}
                        </div>
                    )}
                </CardContent>
            </Card>

            {/* Create Dialog */}
            <Dialog open={isCreateDialogOpen} onOpenChange={setIsCreateDialogOpen}>
                <DialogContent className="max-w-lg">
                    <DialogHeader>
                        <DialogTitle>Ajukan Koreksi Absensi</DialogTitle>
                        <DialogDescription>
                            Isi form di bawah untuk mengajukan koreksi absensi
                        </DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="correction_date">Tanggal yang Dikoreksi *</Label>
                            <Input
                                id="correction_date"
                                type="date"
                                value={formData.correction_date}
                                onChange={(e) => setFormData({ ...formData, correction_date: e.target.value })}
                                max={format(new Date(), 'yyyy-MM-dd')}
                            />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="correction_type">Jenis Koreksi *</Label>
                            <Select
                                value={formData.correction_type}
                                onValueChange={(value) => setFormData({ ...formData, correction_type: value as typeof formData.correction_type })}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Pilih jenis koreksi" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="check_in">Koreksi Jam Masuk</SelectItem>
                                    <SelectItem value="check_out">Koreksi Jam Keluar</SelectItem>
                                    <SelectItem value="both">Koreksi Jam Masuk & Keluar</SelectItem>
                                    <SelectItem value="add_missing">Tambah Absensi (Lupa Absen)</SelectItem>
                                    <SelectItem value="delete">Hapus Absensi</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        {formData.correction_type !== 'delete' && (
                            <div className="grid grid-cols-2 gap-4">
                                {['check_in', 'both', 'add_missing'].includes(formData.correction_type) && (
                                    <div className="space-y-2">
                                        <Label htmlFor="requested_check_in">Jam Masuk</Label>
                                        <Input
                                            id="requested_check_in"
                                            type="time"
                                            value={formData.requested_check_in}
                                            onChange={(e) => setFormData({ ...formData, requested_check_in: e.target.value })}
                                        />
                                    </div>
                                )}
                                {['check_out', 'both', 'add_missing'].includes(formData.correction_type) && (
                                    <div className="space-y-2">
                                        <Label htmlFor="requested_check_out">Jam Keluar</Label>
                                        <Input
                                            id="requested_check_out"
                                            type="time"
                                            value={formData.requested_check_out}
                                            onChange={(e) => setFormData({ ...formData, requested_check_out: e.target.value })}
                                        />
                                    </div>
                                )}
                            </div>
                        )}

                        <div className="space-y-2">
                            <Label htmlFor="reason">Alasan Koreksi * (min. 10 karakter)</Label>
                            <Textarea
                                id="reason"
                                placeholder="Jelaskan alasan mengapa Anda mengajukan koreksi..."
                                value={formData.reason}
                                onChange={(e) => setFormData({ ...formData, reason: e.target.value })}
                                rows={4}
                            />
                            <p className="text-xs text-muted-foreground text-right">
                                {formData.reason.length}/10+ karakter
                            </p>
                        </div>

                        <div className="space-y-2">
                            <Label>Dokumen Pendukung (Opsional)</Label>
                            <div className="border-2 border-dashed rounded-lg p-4 text-center">
                                {documentFile ? (
                                    <div className="flex items-center justify-between">
                                        <span className="text-sm truncate">{documentFile.name}</span>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            onClick={() => setDocumentFile(null)}
                                        >
                                            <X className="h-4 w-4" />
                                        </Button>
                                    </div>
                                ) : (
                                    <label className="cursor-pointer">
                                        <input
                                            type="file"
                                            accept=".pdf,.jpg,.jpeg,.png"
                                            className="hidden"
                                            onChange={(e) => setDocumentFile(e.target.files?.[0] || null)}
                                        />
                                        <Upload className="h-8 w-8 mx-auto mb-2 text-muted-foreground" />
                                        <p className="text-sm text-muted-foreground">
                                            Klik untuk upload (PDF, JPG, PNG, max 5MB)
                                        </p>
                                    </label>
                                )}
                            </div>
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setIsCreateDialogOpen(false)}>
                            Batal
                        </Button>
                        <Button
                            onClick={handleCreateSubmit}
                            disabled={createMutation.isPending}
                        >
                            {createMutation.isPending && <Loader2 className="h-4 w-4 mr-2 animate-spin" />}
                            Ajukan Koreksi
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Detail Dialog */}
            <Dialog open={isDetailDialogOpen} onOpenChange={setIsDetailDialogOpen}>
                <DialogContent className="max-w-lg">
                    <DialogHeader>
                        <DialogTitle>Detail Koreksi</DialogTitle>
                    </DialogHeader>
                    {selectedCorrection && (
                        <div className="space-y-4">
                            <div className="flex justify-between items-start">
                                <div>
                                    <p className="text-sm text-muted-foreground">Tanggal</p>
                                    <p className="font-medium">
                                        {format(new Date(selectedCorrection.correction_date), 'EEEE, d MMMM yyyy', { locale: id })}
                                    </p>
                                </div>
                                {getStatusBadge(selectedCorrection.status)}
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

                            {selectedCorrection.review_notes && (
                                <div className="p-3 rounded-md border-l-4 border-primary bg-primary/5">
                                    <p className="text-sm font-medium flex items-center gap-2">
                                        <AlertCircle className="h-4 w-4" />
                                        Catatan dari Admin
                                    </p>
                                    <p className="text-sm mt-1">{selectedCorrection.review_notes}</p>
                                </div>
                            )}
                        </div>
                    )}
                    <DialogFooter>
                        {selectedCorrection?.status === 'pending' && (
                            <Button
                                variant="outline"
                                onClick={() => handleCancel(selectedCorrection)}
                                disabled={cancelMutation.isPending}
                            >
                                {cancelMutation.isPending && <Loader2 className="h-4 w-4 mr-2 animate-spin" />}
                                Batalkan Permintaan
                            </Button>
                        )}
                        <Button variant="outline" onClick={() => setIsDetailDialogOpen(false)}>
                            Tutup
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    );
}
