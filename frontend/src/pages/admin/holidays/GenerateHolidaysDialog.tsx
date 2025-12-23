import { useState } from 'react';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Loader2, Download, AlertCircle, CheckCircle } from 'lucide-react';
import { useHolidays } from '@/hooks/use-holidays';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { useNotificationStore } from '@/stores';

interface GenerateHolidaysDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
}

export function GenerateHolidaysDialog({ open, onOpenChange }: GenerateHolidaysDialogProps) {
    const [year, setYear] = useState(new Date().getFullYear());
    const [includeBali, setIncludeBali] = useState(true);
    const [isLoading, setIsLoading] = useState(false);
    const [previewData, setPreviewData] = useState<any[]>([]);
    const [error, setError] = useState<string | null>(null);
    const { bulkImport } = useHolidays();
    const { success } = useNotificationStore();

    const fetchPublicHolidays = async () => {
        setIsLoading(true);
        setError(null);
        setPreviewData([]);

        try {
            // Using api-harilibur.vercel.app which includes Indonesia National Holidays and Bali Regional Holidays
            const response = await fetch(`https://api-harilibur.vercel.app/api?year=${year}`);

            if (!response.ok) {
                throw new Error('Gagal mengambil data dari server API');
            }

            const data = await response.json();

            if (!Array.isArray(data)) {
                throw new Error('Format data tidak valid');
            }

            // Transform data to our format
            let holidays = data.map((h: any) => ({
                name: h.holiday_name,
                date: h.holiday_date,
                type: h.is_national_holiday ? 'public_holiday' : 'religious_holiday',
                description: h.is_national_holiday ? 'Libur Nasional' : 'Libur Lokal (Bali)',
                is_recurring: false,
                is_paid: true,
                // Add metadata to identify source
                metadata: {
                    is_national: h.is_national_holiday,
                    source: 'api-harilibur'
                }
            }));

            // Filter based on user preference
            if (!includeBali) {
                holidays = holidays.filter((h: any) => h.metadata.is_national);
            }

            if (holidays.length === 0) {
                setError(`Tidak ada data libur ditemukan untuk tahun ${year}`);
            } else {
                // Sort by date
                holidays.sort((a: any, b: any) => new Date(a.date).getTime() - new Date(b.date).getTime());
                setPreviewData(holidays);
            }
        } catch (err: any) {
            console.error(err);
            setError(err.message || 'Gagal mengambil data dari API publik.');
        } finally {
            setIsLoading(false);
        }
    };

    const handleGenerate = async () => {
        setIsLoading(true);
        try {
            const result = await bulkImport(previewData);
            success('Berhasil', `Berhasil mengimport ${result.imported} hari libur.`);
            onOpenChange(false);
        } catch (_err) {
            setError('Gagal menyimpan data ke database.');
        } finally {
            setIsLoading(false);
        }
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-[600px]">
                <DialogHeader>
                    <DialogTitle>Generate Hari Libur Otomatis</DialogTitle>
                    <DialogDescription>
                        Ambil data hari libur nasional dan lokal (Bali) secara otomatis.
                    </DialogDescription>
                </DialogHeader>

                <div className="grid gap-4 py-4">
                    <div className="flex flex-col gap-4">
                        <div className="flex items-end gap-4">
                            <div className="grid gap-2 flex-1">
                                <Label htmlFor="year-input">Tahun</Label>
                                <Input
                                    id="year-input"
                                    type="number"
                                    value={year}
                                    onChange={(e) => setYear(parseInt(e.target.value))}
                                    min={2020}
                                    max={2030}
                                />
                            </div>
                            <Button onClick={fetchPublicHolidays} disabled={isLoading}>
                                {isLoading ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : <Download className="mr-2 h-4 w-4" />}
                                Ambil Data
                            </Button>
                        </div>

                        <div className="flex items-center space-x-2">
                            <Checkbox
                                id="include-bali"
                                checked={includeBali}
                                onCheckedChange={(checked) => setIncludeBali(checked as boolean)}
                            />
                            <Label htmlFor="include-bali" className="cursor-pointer">
                                Sertakan Libur Lokal Bali (Galungan, Kuningan, Nyepi, dll)
                            </Label>
                        </div>
                    </div>

                    {error && (
                        <Alert variant="destructive">
                            <AlertCircle className="h-4 w-4" />
                            <AlertDescription>{error}</AlertDescription>
                        </Alert>
                    )}

                    {previewData.length > 0 && (
                        <div className="border rounded-md">
                            <div className="p-3 bg-muted border-b text-sm font-medium flex justify-between items-center">
                                <span>Ditemukan {previewData.length} hari libur</span>
                                <span className="text-xs text-muted-foreground">Preview</span>
                            </div>
                            <ScrollArea className="h-[300px]">
                                <div className="p-2 space-y-1">
                                    {previewData.map((h, i) => (
                                        <div key={i} className="flex justify-between items-center p-2 hover:bg-muted/50 rounded text-sm border-b last:border-0">
                                            <div className="flex flex-col">
                                                <div className="flex items-center gap-2">
                                                    <span className="font-medium">{h.name}</span>
                                                    {!h.metadata.is_national && (
                                                        <span className="text-[10px] bg-orange-100 text-orange-700 px-1.5 py-0.5 rounded-full">Bali</span>
                                                    )}
                                                </div>
                                                <span className="text-xs text-muted-foreground capitalize">{h.type.replace('_', ' ')}</span>
                                            </div>
                                            <span className="font-mono text-xs bg-primary/10 text-primary px-2 py-1 rounded">{h.date}</span>
                                        </div>
                                    ))}
                                </div>
                            </ScrollArea>
                        </div>
                    )}
                </div>

                <DialogFooter>
                    <Button variant="outline" onClick={() => onOpenChange(false)}>Batal</Button>
                    <Button onClick={handleGenerate} disabled={previewData.length === 0 || isLoading}>
                        {isLoading ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : <CheckCircle className="mr-2 h-4 w-4" />}
                        Simpan ke Database
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
