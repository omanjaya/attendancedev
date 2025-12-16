import { useState } from 'react';
import { format } from 'date-fns';
import { id } from 'date-fns/locale';
import {
    Plus,
    Clock,
    FileText,
    AlertCircle,
    Loader2,
    Calendar,
    Upload,
    X,
    ChevronRight,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
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
import { MobilePageHeader, MobileStatusBadge, MobileEmptyState } from '@/components/mobile';
import { toast } from 'sonner';
import {
    useAttendanceCorrections,
    useCreateCorrection,
    useCancelCorrection,
} from '@/hooks/use-attendance-corrections';
import {
    CORRECTION_TYPE_LABELS,
    type AttendanceCorrection,
} from '@/lib/api/attendance-corrections';

export default function MobileCorrectionsPage() {
    const [isCreateSheetOpen, setIsCreateSheetOpen] = useState(false);
    const [selectedCorrection, setSelectedCorrection] = useState<AttendanceCorrection | null>(null);
    const [isDetailSheetOpen, setIsDetailSheetOpen] = useState(false);

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
            setIsCreateSheetOpen(false);
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
            setIsDetailSheetOpen(false);
        } catch {
            toast.error('Gagal membatalkan koreksi');
        }
    };

    // Group corrections by status
    const pendingCorrections = corrections.filter((c) => c.status === 'pending');
    const processedCorrections = corrections.filter((c) => c.status !== 'pending');

    return (
        <div className="min-h-screen bg-background pb-24">
            <MobilePageHeader
                title="Koreksi Absensi"
                gradient="emerald"
                subtitle={
                    <p className="text-white/80 text-xs">
                        Ajukan koreksi jika ada kesalahan pada absensi
                    </p>
                }
                rightAction={
                    <button
                        onClick={() => setIsCreateSheetOpen(true)}
                        className="p-2 hover:bg-white/10 rounded-full transition-colors active:scale-95"
                    >
                        <Plus className="h-5 w-5 text-white" />
                    </button>
                }
            />

            <div className="px-4 space-y-4">
                {/* Quick Stats */}
                <div className="grid grid-cols-2 gap-3">
                    <div className="bg-card rounded-2xl shadow-sm dark:border dark:border-border/50 p-4">
                        <div className="flex flex-col items-center text-center">
                            <div className="h-12 w-12 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center mb-2">
                                <Clock className="h-6 w-6 text-amber-600 dark:text-amber-400" />
                            </div>
                            <p className="text-xs font-medium text-muted-foreground mb-1">Menunggu</p>
                            <p className="text-2xl font-bold text-foreground">{pendingCorrections.length}</p>
                        </div>
                    </div>
                    <div className="bg-card rounded-2xl shadow-sm dark:border dark:border-border/50 p-4">
                        <div className="flex flex-col items-center text-center">
                            <div className="h-12 w-12 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center mb-2">
                                <FileText className="h-6 w-6 text-blue-600 dark:text-blue-400" />
                            </div>
                            <p className="text-xs font-medium text-muted-foreground mb-1">Total</p>
                            <p className="text-2xl font-bold text-foreground">{corrections.length}</p>
                        </div>
                    </div>
                </div>

                {/* Pending Corrections */}
                {pendingCorrections.length > 0 && (
                    <div className="bg-card rounded-2xl shadow-sm dark:border dark:border-border/50 p-4 space-y-3">
                        <div className="flex items-center gap-2 mb-3">
                            <Clock className="h-4 w-4 text-amber-600 dark:text-amber-400" />
                            <h3 className="text-sm font-bold text-foreground">Menunggu Persetujuan</h3>
                        </div>
                        <div className="space-y-2">
                            {pendingCorrections.map((correction) => (
                                <div
                                    key={correction.id}
                                    className="bg-muted/30 rounded-xl p-3 border border-border/50 active:bg-muted/50 cursor-pointer transition-colors"
                                    onClick={() => {
                                        setSelectedCorrection(correction);
                                        setIsDetailSheetOpen(true);
                                    }}
                                >
                                    <div className="flex items-center justify-between">
                                        <div className="flex-1 min-w-0">
                                            <div className="flex items-center gap-2 mb-1">
                                                <Calendar className="h-3 w-3 text-muted-foreground flex-shrink-0" />
                                                <span className="text-sm font-semibold text-foreground truncate">
                                                    {format(new Date(correction.correction_date), 'd MMM yyyy', { locale: id })}
                                                </span>
                                            </div>
                                            <p className="text-xs text-muted-foreground truncate">
                                                {CORRECTION_TYPE_LABELS[correction.correction_type]}
                                            </p>
                                        </div>
                                        <MobileStatusBadge status={correction.status as any} />
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                )}

                {/* History */}
                <div className="bg-card rounded-2xl shadow-sm dark:border dark:border-border/50 p-4 space-y-3">
                    <div className="flex items-center gap-2">
                        <FileText className="h-4 w-4 text-blue-600 dark:text-blue-400" />
                        <h3 className="text-sm font-bold text-foreground">Riwayat Koreksi</h3>
                    </div>
                    {isLoading ? (
                        <div className="text-center py-8">
                            <Loader2 className="h-6 w-6 animate-spin text-muted-foreground mx-auto" />
                            <p className="text-sm text-muted-foreground mt-2">Memuat data...</p>
                        </div>
                    ) : corrections.length === 0 ? (
                        <MobileEmptyState
                            icon={FileText}
                            title="Belum Ada Permintaan Koreksi"
                            description="Mulai dengan mengajukan koreksi absensi"
                            action={{
                                label: "Ajukan Koreksi",
                                onClick: () => setIsCreateSheetOpen(true),
                                icon: Plus
                            }}
                        />
                    ) : (
                        <div className="space-y-2">
                            {processedCorrections.map((correction) => (
                                <div
                                    key={correction.id}
                                    className="bg-muted/30 rounded-xl p-3 border border-border/50 active:bg-muted/50 cursor-pointer transition-colors"
                                    onClick={() => {
                                        setSelectedCorrection(correction);
                                        setIsDetailSheetOpen(true);
                                    }}
                                >
                                    <div className="flex items-center justify-between">
                                        <div className="flex-1 min-w-0">
                                            <div className="flex items-center gap-2 mb-1">
                                                <Calendar className="h-3 w-3 text-muted-foreground flex-shrink-0" />
                                                <span className="text-sm font-semibold text-foreground truncate">
                                                    {format(new Date(correction.correction_date), 'd MMM yyyy', { locale: id })}
                                                </span>
                                            </div>
                                            <p className="text-xs text-muted-foreground truncate">
                                                {CORRECTION_TYPE_LABELS[correction.correction_type]}
                                            </p>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <MobileStatusBadge status={correction.status as any} />
                                            <ChevronRight className="h-4 w-4 text-muted-foreground flex-shrink-0" />
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            </div>

            {/* Create Sheet */}
            <Sheet open={isCreateSheetOpen} onOpenChange={setIsCreateSheetOpen}>
                <SheetContent side="bottom" className="h-[85vh] rounded-t-3xl">
                    <SheetHeader>
                        <SheetTitle>Ajukan Koreksi Absensi</SheetTitle>
                        <SheetDescription>
                            Isi form di bawah untuk mengajukan koreksi absensi
                        </SheetDescription>
                    </SheetHeader>
                    <div className="mt-6 space-y-4 overflow-y-auto">
                        <div className="space-y-2">
                            <Label htmlFor="correction_date" className="text-sm font-medium text-foreground">Tanggal yang Dikoreksi *</Label>
                            <Input
                                id="correction_date"
                                type="date"
                                value={formData.correction_date}
                                onChange={(e) => setFormData({ ...formData, correction_date: e.target.value })}
                                max={format(new Date(), 'yyyy-MM-dd')}
                            />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="correction_type" className="text-sm font-medium text-foreground">Jenis Koreksi *</Label>
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
                            <div className="space-y-4">
                                {['check_in', 'both', 'add_missing'].includes(formData.correction_type) && (
                                    <div className="space-y-2">
                                        <Label htmlFor="requested_check_in" className="text-sm font-medium text-foreground">Jam Masuk</Label>
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
                                        <Label htmlFor="requested_check_out" className="text-sm font-medium text-foreground">Jam Keluar</Label>
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
                            <Label htmlFor="reason" className="text-sm font-medium text-foreground">Alasan Koreksi * (min. 10 karakter)</Label>
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
                            <Label className="text-sm font-medium text-foreground">Dokumen Pendukung (Opsional)</Label>
                            <div className="border-2 border-dashed rounded-xl p-4 text-center border-border">
                                {documentFile ? (
                                    <div className="flex items-center justify-between bg-muted/30 rounded-lg p-2">
                                        <span className="text-sm truncate flex-1">{documentFile.name}</span>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            onClick={() => setDocumentFile(null)}
                                            className="hover:bg-destructive/10"
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
                                        <p className="text-xs text-muted-foreground">
                                            Klik untuk upload (PDF, JPG, PNG, max 5MB)
                                        </p>
                                    </label>
                                )}
                            </div>
                        </div>
                    </div>
                    <SheetFooter className="mt-6">
                        <Button variant="outline" onClick={() => setIsCreateSheetOpen(false)}>
                            Batal
                        </Button>
                        <Button
                            onClick={handleCreateSubmit}
                            disabled={createMutation.isPending}
                        >
                            {createMutation.isPending && <Loader2 className="h-4 w-4 mr-2 animate-spin" />}
                            Ajukan Koreksi
                        </Button>
                    </SheetFooter>
                </SheetContent>
            </Sheet>

            {/* Detail Sheet */}
            <Sheet open={isDetailSheetOpen} onOpenChange={setIsDetailSheetOpen}>
                <SheetContent side="bottom" className="h-[80vh] rounded-t-3xl">
                    <SheetHeader>
                        <SheetTitle>Detail Koreksi</SheetTitle>
                    </SheetHeader>
                    {selectedCorrection && (
                        <div className="mt-6 space-y-4 overflow-y-auto">
                            <div className="flex justify-between items-start">
                                <div>
                                    <p className="text-xs text-muted-foreground">Tanggal</p>
                                    <p className="text-sm font-semibold text-foreground">
                                        {format(new Date(selectedCorrection.correction_date), 'EEEE, d MMMM yyyy', { locale: id })}
                                    </p>
                                </div>
                                <MobileStatusBadge status={selectedCorrection.status as any} />
                            </div>

                            <div>
                                <p className="text-xs text-muted-foreground">Jenis Koreksi</p>
                                <p className="text-sm font-semibold text-foreground">
                                    {CORRECTION_TYPE_LABELS[selectedCorrection.correction_type]}
                                </p>
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <p className="text-xs text-muted-foreground">Jam Asli</p>
                                    <p className="text-sm text-foreground">
                                        Masuk: {selectedCorrection.original_check_in || '-'}<br />
                                        Keluar: {selectedCorrection.original_check_out || '-'}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-xs text-muted-foreground">Jam yang Diminta</p>
                                    <p className="text-sm text-foreground">
                                        Masuk: {selectedCorrection.requested_check_in || '-'}<br />
                                        Keluar: {selectedCorrection.requested_check_out || '-'}
                                    </p>
                                </div>
                            </div>

                            <div>
                                <p className="text-xs text-muted-foreground">Alasan</p>
                                <div className="bg-muted/30 rounded-xl p-3 border border-border/50 mt-1">
                                    <p className="text-sm text-foreground">
                                        {selectedCorrection.reason}
                                    </p>
                                </div>
                            </div>

                            {selectedCorrection.review_notes && (
                                <div className="p-3 rounded-xl border-l-4 border-primary bg-primary/5">
                                    <p className="text-sm font-semibold flex items-center gap-2 text-foreground">
                                        <AlertCircle className="h-4 w-4" />
                                        Catatan dari Admin
                                    </p>
                                    <p className="text-sm mt-1 text-muted-foreground">{selectedCorrection.review_notes}</p>
                                </div>
                            )}

                            {selectedCorrection.supporting_document && (
                                <div>
                                    <p className="text-xs text-muted-foreground">Dokumen Pendukung</p>
                                    <div className="bg-muted/30 rounded-xl p-3 border border-border/50 mt-1">
                                        <p className="text-xs text-blue-600 dark:text-blue-400">
                                            📎 {selectedCorrection.supporting_document}
                                        </p>
                                    </div>
                                </div>
                            )}
                        </div>
                    )}
                    <SheetFooter className="mt-6">
                        {selectedCorrection?.status === 'pending' && (
                            <Button
                                variant="outline"
                                onClick={() => handleCancel(selectedCorrection)}
                                disabled={cancelMutation.isPending}
                                className="w-full text-red-600 border-red-200 hover:bg-red-50 dark:border-red-800 dark:hover:bg-red-950/30"
                            >
                                {cancelMutation.isPending && <Loader2 className="h-4 w-4 mr-2 animate-spin" />}
                                Batalkan Permintaan
                            </Button>
                        )}
                        <Button variant="outline" onClick={() => setIsDetailSheetOpen(false)} className="w-full">
                            Tutup
                        </Button>
                    </SheetFooter>
                </SheetContent>
            </Sheet>
        </div>
    );
}
