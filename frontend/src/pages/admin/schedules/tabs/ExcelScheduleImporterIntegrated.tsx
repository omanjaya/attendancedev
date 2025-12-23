/**
 * Excel Schedule Importer (Integrated with Backend)
 * 
 * This component:
 * 1. Parses Excel files with teacher schedule data
 * 2. Matches teachers to existing employees via API
 * 3. Sends data to backend for bulk import into TeachingSchedule
 * 4. Integrates with the attendance system via teaching schedules
 */

import React, { useState, useCallback, useRef } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
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
    Check,
    X,
    Link,
} from 'lucide-react';
import { useNotificationStore } from '@/stores/notification-store';
import {
    matchTeachersFromExcel,
    bulkImportTeachingSchedules,
    type TeacherMatchResult,
    type BulkImportResult
} from '@/lib/api/schedules';

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

interface SemesterPeriod {
    label: string;
    semester: 1 | 2;
    academic_year: string;
    effective_from: string;
    effective_until: string;
}

interface ExcelScheduleImporterIntegratedProps {
    onImportComplete?: (result: BulkImportResult) => void;
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

// Get current academic year
const getCurrentAcademicYear = (): string => {
    const now = new Date();
    const month = now.getMonth() + 1; // 1-12
    const year = now.getFullYear();

    // Academic year starts in July
    if (month >= 7) {
        return `${year}/${year + 1}`;
    }
    return `${year - 1}/${year}`;
};

// Get preset semester periods
const getSemesterPeriods = (): SemesterPeriod[] => {
    const now = new Date();
    const year = now.getFullYear();

    return [
        {
            label: `Semester 1 - ${year}/${year + 1}`,
            semester: 1,
            academic_year: `${year}/${year + 1}`,
            effective_from: `${year}-07-15`,
            effective_until: `${year}-12-20`,
        },
        {
            label: `Semester 2 - ${year}/${year + 1}`,
            semester: 2,
            academic_year: `${year}/${year + 1}`,
            effective_from: `${year + 1}-01-05`,
            effective_until: `${year + 1}-06-20`,
        },
        {
            label: `Semester 1 - ${year - 1}/${year}`,
            semester: 1,
            academic_year: `${year - 1}/${year}`,
            effective_from: `${year - 1}-07-15`,
            effective_until: `${year - 1}-12-20`,
        },
        {
            label: `Semester 2 - ${year - 1}/${year}`,
            semester: 2,
            academic_year: `${year - 1}/${year}`,
            effective_from: `${year}-01-05`,
            effective_until: `${year}-06-20`,
        },
    ];
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

export function ExcelScheduleImporterIntegrated({ onImportComplete }: ExcelScheduleImporterIntegratedProps) {
    const { success, error: showError, warning } = useNotificationStore();

    const [isLoading, setIsLoading] = useState(false);
    const [isImporting, setIsImporting] = useState(false);
    const [parsedData, setParsedData] = useState<ParsedScheduleData | null>(null);
    const [showPreview, setShowPreview] = useState(false);
    const [teacherMatches, setTeacherMatches] = useState<TeacherMatchResult[]>([]);
    const [selectedPeriod, setSelectedPeriod] = useState<SemesterPeriod | null>(null);
    const [customPeriod, setCustomPeriod] = useState({
        effective_from: '',
        effective_until: '',
        semester: 1 as 1 | 2,
        academic_year: getCurrentAcademicYear(),
    });
    const [useCustomPeriod, setUseCustomPeriod] = useState(false);

    const fileInputRef = useRef<HTMLInputElement>(null);
    const semesterPeriods = getSemesterPeriods();

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
                (name: string) => name.toLowerCase().includes('kode') || name.toLowerCase().includes('guru')
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
                (name: string) => name.toLowerCase().includes('kelas')
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
                    if (row.some((cell: string) => String(cell).match(/^[A-H](\s*\(.*\))?$/))) {
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

            // Auto-match teachers with backend
            if (teachers.length > 0) {
                try {
                    const matches = await matchTeachersFromExcel(teachers);
                    setTeacherMatches(matches);

                    const matchedCount = matches.filter(m => m.matched).length;
                    if (matchedCount < teachers.length) {
                        warning('Perhatian', `${matchedCount}/${teachers.length} guru berhasil di-match dengan database`);
                    }
                } catch (err) {
                    console.error('Error matching teachers:', err);
                    // Continue without matches
                }
            }

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
    }, [success, showError, warning]);

    // Get effective period for import
    const getEffectivePeriod = () => {
        if (useCustomPeriod) {
            return {
                effective_from: customPeriod.effective_from,
                effective_until: customPeriod.effective_until || undefined,
                semester: customPeriod.semester,
                academic_year: customPeriod.academic_year,
            };
        }
        if (selectedPeriod) {
            return {
                effective_from: selectedPeriod.effective_from,
                effective_until: selectedPeriod.effective_until,
                semester: selectedPeriod.semester,
                academic_year: selectedPeriod.academic_year,
            };
        }
        return null;
    };

    // Submit import to backend
    const handleImportToBackend = useCallback(async () => {
        if (!parsedData) return;

        const period = getEffectivePeriod();
        if (!period || !period.effective_from) {
            showError('Error', 'Silakan pilih periode semester terlebih dahulu');
            return;
        }

        setIsImporting(true);

        try {
            const result = await bulkImportTeachingSchedules({
                teachers: parsedData.teachers,
                schedules: parsedData.schedules.map(s => ({
                    day: s.day,
                    period: s.period,
                    className: s.className,
                    teacherCode: s.teacherCode,
                    subject: s.subject,
                })),
                effective_from: period.effective_from,
                effective_until: period.effective_until,
                semester: period.semester,
                academic_year: period.academic_year,
            });

            success(
                'Import Berhasil!',
                `${result.created_schedules} jadwal dibuat, ${result.matched_teachers.length} guru di-match`
            );

            if (result.unmatched_teachers.length > 0) {
                warning(
                    'Perhatian',
                    `${result.unmatched_teachers.length} guru tidak ditemukan di database`
                );
            }

            setShowPreview(false);
            setParsedData(null);
            setTeacherMatches([]);

            if (onImportComplete) {
                onImportComplete(result);
            }

        } catch (err) {
            console.error('Import error:', err);
            showError('Error', 'Gagal mengimport jadwal ke database');
        } finally {
            setIsImporting(false);
        }
    }, [parsedData, selectedPeriod, customPeriod, useCustomPeriod, success, showError, warning, onImportComplete]);

    const matchedCount = teacherMatches.filter(m => m.matched).length;
    const unmatchedCount = teacherMatches.filter(m => !m.matched).length;

    return (
        <div className="space-y-4">
            {/* Step 1: Upload Card */}
            <Card>
                <CardHeader>
                    <CardTitle className="text-lg flex items-center gap-2">
                        <FileSpreadsheet className="h-5 w-5 text-primary" />
                        Import Jadwal Mengajar dari Excel
                    </CardTitle>
                    <CardDescription>
                        Upload file Excel jadwal mengajar. Data akan diimport ke database dan terintegrasi dengan sistem absensi.
                    </CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
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
                    <div className="p-4 bg-muted/50 rounded-lg text-sm">
                        <h4 className="font-medium mb-2">Format Excel yang Didukung:</h4>
                        <ul className="list-disc list-inside space-y-1 text-muted-foreground">
                            <li>Sheet <strong>"KODE GURU"</strong>: Kolom No, Nama Guru, Jabatan</li>
                            <li>Sheet <strong>"Kelas 7/8/9"</strong>: Grid jadwal dengan format "KODE-MAPEL"</li>
                            <li>Contoh: <code className="bg-muted px-1 rounded">6-IPA</code>, <code className="bg-muted px-1 rounded">27-Seni Rupa</code></li>
                        </ul>
                    </div>

                    {/* Integration Info */}
                    <div className="p-4 bg-blue-50 dark:bg-blue-950/30 rounded-lg text-sm border border-blue-200 dark:border-blue-800">
                        <div className="flex items-start gap-2">
                            <Link className="h-4 w-4 text-blue-600 dark:text-blue-400 mt-0.5" />
                            <div>
                                <h4 className="font-medium text-blue-800 dark:text-blue-300">Integrasi Absensi</h4>
                                <p className="text-blue-700 dark:text-blue-400 mt-1">
                                    Jadwal yang diimport akan digunakan untuk menghitung keterlambatan guru honor.
                                    Check-in berdasarkan jam mengajar pertama, check-out berdasarkan jam mengajar terakhir.
                                </p>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            {/* Preview Dialog */}
            <Dialog open={showPreview} onOpenChange={setShowPreview}>
                <DialogContent className="max-w-5xl max-h-[90vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>Preview & Import Jadwal Mengajar</DialogTitle>
                        <DialogDescription>
                            Periksa data dan pilih periode semester sebelum import ke database
                        </DialogDescription>
                    </DialogHeader>

                    {parsedData && (
                        <div className="space-y-6">
                            {/* Stats */}
                            <div className="grid grid-cols-5 gap-4">
                                <Card>
                                    <CardContent className="p-4 text-center">
                                        <Users className="h-6 w-6 mx-auto text-primary/50 mb-1" />
                                        <p className="text-xl font-bold">{parsedData.teachers.length}</p>
                                        <p className="text-xs text-muted-foreground">Guru</p>
                                    </CardContent>
                                </Card>
                                <Card>
                                    <CardContent className="p-4 text-center">
                                        <Check className="h-6 w-6 mx-auto text-green-500/50 mb-1" />
                                        <p className="text-xl font-bold text-green-600">{matchedCount}</p>
                                        <p className="text-xs text-muted-foreground">Matched</p>
                                    </CardContent>
                                </Card>
                                <Card>
                                    <CardContent className="p-4 text-center">
                                        <X className="h-6 w-6 mx-auto text-red-500/50 mb-1" />
                                        <p className="text-xl font-bold text-red-600">{unmatchedCount}</p>
                                        <p className="text-xs text-muted-foreground">Unmatched</p>
                                    </CardContent>
                                </Card>
                                <Card>
                                    <CardContent className="p-4 text-center">
                                        <Calendar className="h-6 w-6 mx-auto text-blue-500/50 mb-1" />
                                        <p className="text-xl font-bold">{parsedData.schedules.length}</p>
                                        <p className="text-xs text-muted-foreground">Jadwal</p>
                                    </CardContent>
                                </Card>
                                <Card>
                                    <CardContent className="p-4 text-center">
                                        <FileSpreadsheet className="h-6 w-6 mx-auto text-purple-500/50 mb-1" />
                                        <p className="text-xl font-bold">{parsedData.classes.length}</p>
                                        <p className="text-xs text-muted-foreground">Kelas</p>
                                    </CardContent>
                                </Card>
                            </div>

                            {/* Semester Period Selection */}
                            <Card>
                                <CardHeader className="pb-3">
                                    <CardTitle className="text-sm font-medium">Pilih Periode Semester</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div className="flex items-center gap-4">
                                        <div className="flex-1">
                                            <Label>Preset Semester</Label>
                                            <Select
                                                value={selectedPeriod?.label || ''}
                                                onValueChange={(value) => {
                                                    const period = semesterPeriods.find(p => p.label === value);
                                                    setSelectedPeriod(period || null);
                                                    setUseCustomPeriod(false);
                                                }}
                                                disabled={useCustomPeriod}
                                            >
                                                <SelectTrigger>
                                                    <SelectValue placeholder="Pilih semester..." />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {semesterPeriods.map((period) => (
                                                        <SelectItem key={period.label} value={period.label}>
                                                            {period.label}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                        </div>
                                        <div className="pt-6">
                                            <Button
                                                variant={useCustomPeriod ? "default" : "outline"}
                                                size="sm"
                                                onClick={() => setUseCustomPeriod(!useCustomPeriod)}
                                            >
                                                Custom
                                            </Button>
                                        </div>
                                    </div>

                                    {useCustomPeriod && (
                                        <div className="grid grid-cols-4 gap-4 p-4 bg-muted/50 rounded-lg">
                                            <div>
                                                <Label>Tahun Ajaran</Label>
                                                <Input
                                                    value={customPeriod.academic_year}
                                                    onChange={(e) => setCustomPeriod({
                                                        ...customPeriod,
                                                        academic_year: e.target.value
                                                    })}
                                                    placeholder="2024/2025"
                                                />
                                            </div>
                                            <div>
                                                <Label>Semester</Label>
                                                <Select
                                                    value={String(customPeriod.semester)}
                                                    onValueChange={(v) => setCustomPeriod({
                                                        ...customPeriod,
                                                        semester: parseInt(v) as 1 | 2
                                                    })}
                                                >
                                                    <SelectTrigger>
                                                        <SelectValue />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem value="1">Semester 1 (Ganjil)</SelectItem>
                                                        <SelectItem value="2">Semester 2 (Genap)</SelectItem>
                                                    </SelectContent>
                                                </Select>
                                            </div>
                                            <div>
                                                <Label>Tanggal Mulai</Label>
                                                <Input
                                                    type="date"
                                                    value={customPeriod.effective_from}
                                                    onChange={(e) => setCustomPeriod({
                                                        ...customPeriod,
                                                        effective_from: e.target.value
                                                    })}
                                                />
                                            </div>
                                            <div>
                                                <Label>Tanggal Selesai</Label>
                                                <Input
                                                    type="date"
                                                    value={customPeriod.effective_until}
                                                    onChange={(e) => setCustomPeriod({
                                                        ...customPeriod,
                                                        effective_until: e.target.value
                                                    })}
                                                />
                                            </div>
                                        </div>
                                    )}
                                </CardContent>
                            </Card>

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

                            {/* Teacher Matching Results */}
                            <div>
                                <h4 className="font-medium mb-2">Status Matching Guru</h4>
                                <ScrollArea className="h-[200px]">
                                    <div className="flex flex-wrap gap-2 p-1">
                                        {teacherMatches.map((match) => (
                                            <Badge
                                                key={match.code}
                                                variant={match.matched ? "default" : "destructive"}
                                                className="text-xs"
                                            >
                                                {match.matched ? (
                                                    <Check className="h-3 w-3 mr-1" />
                                                ) : (
                                                    <X className="h-3 w-3 mr-1" />
                                                )}
                                                <span className="font-mono mr-1">{match.code}</span>
                                                {match.matched
                                                    ? match.employee_name?.split(' ').slice(0, 2).join(' ')
                                                    : match.excel_name.split(' ').slice(0, 2).join(' ')
                                                }
                                            </Badge>
                                        ))}
                                    </div>
                                </ScrollArea>
                            </div>

                            <Separator />

                            {/* Schedule Preview */}
                            <div>
                                <h4 className="font-medium mb-2">Sample Jadwal ({Math.min(20, parsedData.schedules.length)} dari {parsedData.schedules.length})</h4>
                                <ScrollArea className="h-[200px]">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Hari</TableHead>
                                                <TableHead>Jam</TableHead>
                                                <TableHead>Kelas</TableHead>
                                                <TableHead>Kode</TableHead>
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

                    <DialogFooter className="flex-col sm:flex-row gap-2">
                        <div className="flex-1 text-sm text-muted-foreground">
                            {unmatchedCount > 0 && (
                                <span className="text-amber-600">
                                    ⚠️ {unmatchedCount} guru tidak ter-match akan dilewati
                                </span>
                            )}
                        </div>
                        <Button variant="outline" onClick={() => setShowPreview(false)}>
                            Batal
                        </Button>
                        <Button
                            onClick={handleImportToBackend}
                            disabled={isImporting || (!selectedPeriod && !useCustomPeriod) || (useCustomPeriod && !customPeriod.effective_from)}
                        >
                            {isImporting ? (
                                <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                            ) : (
                                <CheckCircle2 className="h-4 w-4 mr-2" />
                            )}
                            {isImporting ? 'Mengimport...' : 'Import ke Database'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    );
}
