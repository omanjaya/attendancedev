import { useState } from 'react';
import { Loader2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

interface RejectDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    onConfirm: (reason: string) => void;
    isLoading: boolean;
}

export function RejectDialog({
    open,
    onOpenChange,
    onConfirm,
    isLoading,
}: RejectDialogProps) {
    const [reason, setReason] = useState('');

    const handleSubmit = () => {
        onConfirm(reason);
        setReason('');
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle>Tolak Pengajuan Cuti</DialogTitle>
                    <DialogDescription>
                        Apakah Anda yakin ingin menolak pengajuan cuti ini? Berikan alasan penolakan.
                    </DialogDescription>
                </DialogHeader>
                <div className="grid gap-4 py-4">
                    <div className="grid gap-2">
                        <Label htmlFor="reason">Alasan Penolakan</Label>
                        <Textarea
                            id="reason"
                            value={reason}
                            onChange={(e) => setReason(e.target.value)}
                            placeholder="Contoh: Kuota cuti habis, jadwal padat, dll."
                            className="col-span-3"
                        />
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="outline" onClick={() => onOpenChange(false)}>
                        Batal
                    </Button>
                    <Button
                        variant="destructive"
                        onClick={handleSubmit}
                        disabled={isLoading || !reason.trim()}
                    >
                        {isLoading && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                        Tolak Pengajuan
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
