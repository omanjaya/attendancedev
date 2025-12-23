/**
 * Excel Schedule Importer
 * 
 * Parses Excel files with teacher schedule data in the format:
 * - Sheet "KODE GURU": No | Nama Guru | Jabatan
 * - Sheet "Kelas X": Grid with format "{KODE}-{MAPEL}" 
 */

import React, { useState, useCallback, useRef } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { ScrollArea } from '@/components/ui/scroll-area';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    FileSpreadsheet,
    Upload,
    Users,
    Calendar,
    AlertTriangle,
    CheckCircle2,
    Loader2,
    Eye,
} from 'lucide-react';
import { useNotificationStore } from '@/stores/notification-store';

// Types
interface TeacherCode {
    code: string;
    name: string;
    subject: string;
}

interface ParsedScheduleCell {
    day: string;
    period: number;
    className: string;
    teacherCode: string;
    subject: string;
    rawValue: string;
}

interface ParsedScheduleData {
    teachers: TeacherCode[];
    schedules: ParsedScheduleCell[];
    classes: string[];
    days: string[];
    errors: string[];
}

interface ExcelScheduleImporterProps {
    onImportComplete?: (data: ParsedScheduleData) => void;
}

// Day mapping from Indonesian
const DAY_MAPPING: Record<string, string> = {
    'SENIN': 'Senin',
    'SELASA': 'Selasa',
    'RABU': 'Rabu',
    'KAMIS': 'Kamis',
    'JUMAT': 'Jumat',
    'SABTU': 'Sabtu',
};

// Parse teacher code from cell value like "6-IPA" or "27-Seni Rupa"
const parseTeacherCell = (value: string): { code: string; subject: string } | null => {
    if (!value || typeof value !== 'string') return null;

    const trimmed = value.trim();
    if (!trimmed || trimmed === '-' || trimmed.toLowerCase().includes('istirahat')) {
        return null;
    }

    // Match pattern: number-text (e.g., "6-IPA", "27-Seni Rupa")
    const match = trimmed.match(/^(\d+)-(.+)$/);
    if (match) {
        return {
            code: match[1],
            subject: match[2].trim()
        };
    }

    return null;
};

export function ExcelScheduleImporter({ onImportComplete }: ExcelScheduleImporterProps) {
    const { success, error: showError } = useNotificationStore();

    const [isLoading, setIsLoading] = useState(false);
    const [parsedData, setParsedData] = useState<ParsedScheduleData | null>(null);
    const [showPreview, setShowPreview] = useState(false);

    const fileInputRef = useRef<HTMLInputElement>(null);

    // Helper function to convert ExcelJS worksheet to 2D array
    const worksheetTo2DArray = (worksheet: any): string[][] => {
        const data: string[][] = [];
        worksheet.eachRow((row: any, rowNumber: number) => {
            const rowData: string[] = [];
            row.eachCell({ includeEmpty: true }, (cell: any, colNumber: number) => {
                // Ensure array has enough slots
                while (rowData.length < colNumber) {
                    rowData.push('');
                }
                let value = cell.value;
                if (value instanceof Date) {
                    value = value.toISOString().split('T')[0];
                }
                if (typeof value === 'object' && value !== null && 'result' in value) {
                    value = value.result;
                }
                rowData[colNumber - 1] = String(value ?? '');
            });
            data[rowNumber - 1] = rowData;
        });
        return data;
    };

    // Parse the Excel file
    const handleFileUpload = useCallback(async (event: React.ChangeEvent<HTMLInputElement>) => {
        const file = event.target.files?.[0];
        if (!file) return;

        setIsLoading(true);

        try {
            const ExcelJS = await import('exceljs');
            const arrayBuffer = await file.arrayBuffer();
            const workbook = new ExcelJS.Workbook();
            await workbook.xlsx.load(arrayBuffer);

            const teachers: TeacherCode[] = [];
            const schedules: ParsedScheduleCell[] = [];
            const classesSet = new Set<string>();
            const daysSet = new Set<string>();
            const errors: string[] = [];

            // Get sheet names
            const sheetNames = workbook.worksheets.map(ws => ws.name);

            // 1. Parse KODE GURU sheet
            const guruSheetName = sheetNames.find(
                name => name.toLowerCase().includes('kode') || name.toLowerCase().includes('guru')
            );

            if (guruSheetName) {
                const guruSheet = workbook.getWorksheet(guruSheetName);
                const guruData = worksheetTo2DArray(guruSheet);

                guruData.forEach((row: string[], index: number) => {
                    if (index < 2) return; // Skip headers

                    const no = String(row[0] || '').trim();
                    const name = String(row[1] || '').trim();
                    const subject = String(row[2] || '').trim();

                    if (no && !isNaN(Number(no)) && name) {
                        teachers.push({
                            code: no,
                            name: name,
                            subject: subject,
                        });
                    }
                });
            } else {
                errors.push('Sheet KODE GURU tidak ditemukan');
            }

            // 2. Parse schedule sheets (Kelas 7, Kelas 8, Kelas 9, etc.)
            const scheduleSheets = sheetNames.filter(
                name => name.toLowerCase().includes('kelas')
            );

            for (const sheetName of scheduleSheets) {
                const sheet = workbook.getWorksheet(sheetName);
                const data = worksheetTo2DArray(sheet);

                let currentDay = '';
                const classColumns: { index: number; name: string }[] = [];

                // Find class header row and columns
                for (let rowIdx = 0; rowIdx < Math.min(10, data.length); rowIdx++) {
                    const row = data[rowIdx];

                    // Look for class row (contains A, B, C, D, etc.)
                    if (row.some(cell => String(cell).match(/^[A-H](\s*\(.*\))?$/))) {
                        // This is the class header row
                        row.forEach((cell: string, colIdx: number) => {
                            const cellStr = String(cell).trim();
                            if (cellStr && cellStr !== '-' && colIdx > 2) {
                                // Extract class name from sheet name + column
                                const klassName = `${sheetName} ${cellStr}`.replace(/\s+/g, ' ').trim();
                                classColumns.push({ index: colIdx, name: klassName });
                                classesSet.add(klassName);
                            }
                        });
                        break;
                    }
                }

                // Parse schedule data
                for (let rowIdx = 0; rowIdx < data.length; rowIdx++) {
                    const row = data[rowIdx];
                    const firstCell = String(row[0] || '').trim().toUpperCase().replace(/\r?\n/g, '');

                    // Check if this is a day row
                    if (Object.keys(DAY_MAPPING).some(day => firstCell.includes(day))) {
                        currentDay = DAY_MAPPING[Object.keys(DAY_MAPPING).find(day => firstCell.includes(day)) || ''] || firstCell;
                        daysSet.add(currentDay);
                        continue;
                    }

                    // Check if this is a period row (has number in column 1)
                    const period = parseInt(String(row[1] || ''));
                    if (!isNaN(period) && period >= 1 && period <= 12 && currentDay) {
                        // Parse each class column
                        for (const col of classColumns) {
                            const cellValue = String(row[col.index] || '').trim();
                            const parsed = parseTeacherCell(cellValue);

                            if (parsed) {
                                schedules.push({
                                    day: currentDay,
                                    period: period,
                                    className: col.name,
                                    teacherCode: parsed.code,
                                    subject: parsed.subject,
                                    rawValue: cellValue,
                                });
                            }
                        }
                    }
                }
            }

            const result: ParsedScheduleData = {
                teachers,
                schedules,
                classes: Array.from(classesSet).sort(),
                days: Array.from(daysSet),
                errors,
            };

            setParsedData(result);
            setShowPreview(true);

            success('Berhasil', `Berhasil membaca ${teachers.length} guru dan ${schedules.length} jadwal`);

        } catch (err) {
            console.error('Error parsing Excel:', err);
            showError('Error', 'Gagal membaca file Excel. Pastikan format file benar.');
        } finally {
            setIsLoading(false);
            if (fileInputRef.current) {
                fileInputRef.current.value = '';
            }
        }
    }, [success, showError]);

    // Apply imported data
    const handleApplyImport = useCallback(() => {
        if (!parsedData) return;

        if (onImportComplete) {
            onImportComplete(parsedData);
        }

        setShowPreview(false);
        success('Berhasil', 'Data jadwal berhasil diimport');
    }, [parsedData, onImportComplete, success]);

    return (
        <div className="space-y-4">
            {/* Upload Card */}
            <Card>
                <CardHeader>
                    <CardTitle className="text-lg flex items-center gap-2">
                        <FileSpreadsheet className="h-5 w-5 text-primary" />
                        Import Jadwal dari Excel
                    </CardTitle>
                    <CardDescription>
                        Upload file Excel jadwal mengajar dengan format standar SMP (Sheet KODE GURU + Sheet Kelas)
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div className="flex items-center gap-4">
                        <Button
                            variant="outline"
                            onClick={() => fileInputRef.current?.click()}
                            disabled={isLoading}
                        >
                            {isLoading ? (
                                <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                            ) : (
                                <Upload className="h-4 w-4 mr-2" />
                            )}
                            {isLoading ? 'Membaca File...' : 'Pilih File Excel'}
                        </Button>
                        <input
                            ref={fileInputRef}
                            type="file"
                            accept=".xlsx,.xls"
                            onChange={handleFileUpload}
                            className="hidden"
                            title="Upload file Excel jadwal"
                            aria-label="Upload file Excel jadwal"
                        />

                        {parsedData && (
                            <Button variant="secondary" onClick={() => setShowPreview(true)}>
                                <Eye className="h-4 w-4 mr-2" />
                                Lihat Preview
                            </Button>
                        )}
                    </div>

                    {/* Format Info */}
                    <div className="mt-4 p-4 bg-muted/50 rounded-lg text-sm">
                        <h4 className="font-medium mb-2">Format Excel yang Didukung:</h4>
                        <ul className="list-disc list-inside space-y-1 text-muted-foreground">
                            <li>Sheet <strong>"KODE GURU"</strong>: Kolom No, Nama Guru, Jabatan</li>
                            <li>Sheet <strong>"Kelas 7/8/9"</strong>: Grid jadwal dengan format "KODE-MAPEL"</li>
                            <li>Contoh sel jadwal: <code className="bg-muted px-1 rounded">6-IPA</code>, <code className="bg-muted px-1 rounded">27-Seni Rupa</code></li>
                        </ul>
                    </div>
                </CardContent>
            </Card>

            {/* Preview Dialog */}
            <Dialog open={showPreview} onOpenChange={setShowPreview}>
                <DialogContent className="max-w-4xl max-h-[80vh]">
                    <DialogHeader>
                        <DialogTitle>Preview Data Import</DialogTitle>
                        <DialogDescription>
                            Periksa data yang akan diimport sebelum melanjutkan
                        </DialogDescription>
                    </DialogHeader>

                    {parsedData && (
                        <div className="space-y-4">
                            {/* Stats */}
                            <div className="grid grid-cols-4 gap-4">
                                <Card>
                                    <CardContent className="p-4 text-center">
                                        <Users className="h-8 w-8 mx-auto text-primary/50 mb-2" />
                                        <p className="text-2xl font-bold">{parsedData.teachers.length}</p>
                                        <p className="text-xs text-muted-foreground">Guru</p>
                                    </CardContent>
                                </Card>
                                <Card>
                                    <CardContent className="p-4 text-center">
                                        <Calendar className="h-8 w-8 mx-auto text-blue-500/50 mb-2" />
                                        <p className="text-2xl font-bold">{parsedData.schedules.length}</p>
                                        <p className="text-xs text-muted-foreground">Jadwal</p>
                                    </CardContent>
                                </Card>
                                <Card>
                                    <CardContent className="p-4 text-center">
                                        <FileSpreadsheet className="h-8 w-8 mx-auto text-green-500/50 mb-2" />
                                        <p className="text-2xl font-bold">{parsedData.classes.length}</p>
                                        <p className="text-xs text-muted-foreground">Kelas</p>
                                    </CardContent>
                                </Card>
                                <Card>
                                    <CardContent className="p-4 text-center">
                                        <CheckCircle2 className="h-8 w-8 mx-auto text-emerald-500/50 mb-2" />
                                        <p className="text-2xl font-bold">{parsedData.days.length}</p>
                                        <p className="text-xs text-muted-foreground">Hari</p>
                                    </CardContent>
                                </Card>
                            </div>

                            {/* Errors */}
                            {parsedData.errors.length > 0 && (
                                <div className="p-3 bg-destructive/10 rounded-lg border border-destructive/20">
                                    <div className="flex items-center gap-2 text-destructive mb-2">
                                        <AlertTriangle className="h-4 w-4" />
                                        <span className="font-medium">Peringatan</span>
                                    </div>
                                    <ul className="text-sm text-destructive/80 list-disc list-inside">
                                        {parsedData.errors.map((err, i) => (
                                            <li key={i}>{err}</li>
                                        ))}
                                    </ul>
                                </div>
                            )}

                            {/* Teachers Preview */}
                            <div>
                                <h4 className="font-medium mb-2">Daftar Guru (Sample)</h4>
                                <div className="flex flex-wrap gap-2">
                                    {parsedData.teachers.slice(0, 15).map((teacher) => (
                                        <Badge key={teacher.code} variant="outline" className="text-xs">
                                            <span className="font-mono mr-1">{teacher.code}</span>
                                            {teacher.name.split(',')[0].split(' ').slice(0, 2).join(' ')}
                                        </Badge>
                                    ))}
                                    {parsedData.teachers.length > 15 && (
                                        <Badge variant="secondary">+{parsedData.teachers.length - 15} lainnya</Badge>
                                    )}
                                </div>
                            </div>

                            <Separator />

                            {/* Schedule Preview */}
                            <div>
                                <h4 className="font-medium mb-2">Sample Jadwal</h4>
                                <ScrollArea className="h-[200px]">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Hari</TableHead>
                                                <TableHead>Jam</TableHead>
                                                <TableHead>Kelas</TableHead>
                                                <TableHead>Kode Guru</TableHead>
                                                <TableHead>Mata Pelajaran</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {parsedData.schedules.slice(0, 20).map((schedule, idx) => (
                                                <TableRow key={idx}>
                                                    <TableCell>{schedule.day}</TableCell>
                                                    <TableCell>{schedule.period}</TableCell>
                                                    <TableCell>{schedule.className}</TableCell>
                                                    <TableCell className="font-mono">{schedule.teacherCode}</TableCell>
                                                    <TableCell>{schedule.subject}</TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                </ScrollArea>
                            </div>
                        </div>
                    )}

                    <DialogFooter>
                        <Button variant="outline" onClick={() => setShowPreview(false)}>
                            Batal
                        </Button>
                        <Button onClick={handleApplyImport}>
                            <CheckCircle2 className="h-4 w-4 mr-2" />
                            Import Data
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    );
}
