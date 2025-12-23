/**
 * Excel Import Dialog Component
 *
 * Dialog untuk import jadwal dari file Excel dengan format:
 * - Sheet "KODE GURU": Daftar guru dengan JP
 * - Sheet "Kelas 7/8/9": Jadwal per kelas
 */

import { useState, useCallback, useRef } from 'react';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Progress } from '@/components/ui/progress';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import {
  Upload,
  FileSpreadsheet,
  CheckCircle,
  AlertTriangle,
  Loader2,
  Users,
  Calendar,
  Download,
} from 'lucide-react';
import { useGradeScheduleStore } from '@/stores/grade-schedule-store';
import { useNotificationStore } from '@/stores/notification-store';
import {
  CELL_VALUE_REGEX,
  EXCEL_SHEET_NAMES,
  DAY_MAPPING,
  DAYS,
  TIME_SLOTS,
  DEFAULT_CLASSES,
  GRADES,
} from './constants';
import type { ParsedExcelData } from './types';

interface ExcelImportDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
}

type ImportStep = 'upload' | 'parsing' | 'preview' | 'importing' | 'done';

export function ExcelImportDialog({ open, onOpenChange }: ExcelImportDialogProps) {
  const { success, error: showError } = useNotificationStore();
  const importFromExcel = useGradeScheduleStore((s) => s.importFromExcel);

  const [step, setStep] = useState<ImportStep>('upload');
  const [progress, setProgress] = useState(0);
  const [parsedData, setParsedData] = useState<ParsedExcelData | null>(null);
  const [errors, setErrors] = useState<string[]>([]);
  const [isDownloading, setIsDownloading] = useState(false);

  const fileInputRef = useRef<HTMLInputElement>(null);

  // Download template Excel
  const handleDownloadTemplate = useCallback(async () => {
    setIsDownloading(true);
    try {
      const ExcelJS = await import('exceljs');
      const workbook = new ExcelJS.Workbook();
      workbook.creator = 'Attendance System';
      workbook.created = new Date();

      // ========== Sheet: KODE GURU ==========
      const teacherSheet = workbook.addWorksheet(EXCEL_SHEET_NAMES.TEACHERS);

      teacherSheet.columns = [
        { header: 'No', key: 'no', width: 8 },
        { header: 'Nama Guru', key: 'name', width: 30 },
        { header: 'Mapel', key: 'subject', width: 25 },
        { header: 'Jam Pelajaran', key: 'jp', width: 15 },
        { header: 'Keterangan', key: 'note', width: 20 },
      ];

      teacherSheet.getRow(1).font = { bold: true };
      teacherSheet.getRow(1).fill = {
        type: 'pattern',
        pattern: 'solid',
        fgColor: { argb: 'FFE0E0E0' },
      };

      // Sample data
      const sampleTeachers = [
        { no: 1, name: 'Budi Santoso, S.Pd', subject: 'Matematika', jp: '24 JP', note: '' },
        { no: 2, name: 'Siti Rahayu, S.Pd', subject: 'Bahasa Indonesia', jp: '24 JP', note: '' },
        { no: 3, name: 'Ahmad Fauzi, S.Pd', subject: 'IPA', jp: '20 JP', note: '' },
        { no: 4, name: 'Dewi Lestari, S.Pd', subject: 'IPS', jp: '20 JP', note: '' },
        { no: 5, name: 'Eko Prasetyo, S.Pd', subject: 'Bahasa Inggris', jp: '24 JP', note: '' },
      ];
      sampleTeachers.forEach((t) => teacherSheet.addRow(t));

      teacherSheet.addRow([]);
      teacherSheet.addRow(['Petunjuk:']);
      teacherSheet.addRow(['- Kolom "No" akan menjadi kode guru (contoh: 1, 2, 3)']);
      teacherSheet.addRow(['- Format "Jam Pelajaran": angka diikuti " JP" (contoh: 24 JP)']);
      teacherSheet.addRow(['- Kode guru digunakan di sheet jadwal dengan format: KODE-MAPEL']);

      // ========== Sheet: Kelas 7, 8, 9 ==========
      for (const grade of GRADES) {
        const sheetName = grade === '7' ? EXCEL_SHEET_NAMES.GRADE_7
          : grade === '8' ? EXCEL_SHEET_NAMES.GRADE_8
          : EXCEL_SHEET_NAMES.GRADE_9;

        const gradeSheet = workbook.addWorksheet(sheetName);
        const classes = DEFAULT_CLASSES[grade];

        const columns: { header: string; key: string; width: number }[] = [
          { header: 'Hari', key: 'day', width: 12 },
          { header: 'Jam', key: 'period', width: 6 },
          { header: 'Waktu', key: 'time', width: 14 },
        ];
        classes.forEach((cls) => {
          columns.push({ header: cls, key: cls, width: 18 });
        });
        gradeSheet.columns = columns;

        gradeSheet.getRow(1).font = { bold: true };
        gradeSheet.getRow(1).fill = {
          type: 'pattern',
          pattern: 'solid',
          fgColor: { argb: 'FFD0E8FF' },
        };
        gradeSheet.getRow(1).alignment = { horizontal: 'center' };

        for (const day of DAYS) {
          for (const slot of TIME_SLOTS) {
            const row: Record<string, string | number> = {
              day: day,
              period: slot.period,
              time: `${slot.start}-${slot.end}`,
            };

            if (slot.isBreak) {
              classes.forEach((cls) => {
                row[cls] = 'Istirahat';
              });
            } else {
              classes.forEach((cls) => {
                row[cls] = '';
              });
            }

            const addedRow = gradeSheet.addRow(row);

            if (slot.isBreak) {
              addedRow.fill = {
                type: 'pattern',
                pattern: 'solid',
                fgColor: { argb: 'FFFFF0CC' },
              };
              addedRow.font = { italic: true, color: { argb: 'FF666666' } };
            }
          }
        }

        gradeSheet.addRow([]);
        gradeSheet.addRow(['Format isi jadwal: KODE_GURU-MAPEL']);
        gradeSheet.addRow(['Contoh: 1-Matematika, 2-Bahasa Indonesia, 3-IPA']);
        gradeSheet.addRow(['Kode guru sesuai dengan kolom "No" di sheet KODE GURU']);
      }

      const buffer = await workbook.xlsx.writeBuffer();
      const blob = new Blob([buffer], {
        type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
      });
      const url = URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = `template-jadwal-mengajar-${new Date().toISOString().split('T')[0]}.xlsx`;
      link.click();
      URL.revokeObjectURL(url);

      success('Berhasil', 'Template Excel berhasil diunduh');
    } catch (err) {
      console.error('Error generating template:', err);
      showError('Gagal', 'Gagal membuat template Excel');
    } finally {
      setIsDownloading(false);
    }
  }, [success, showError]);

  // Reset state when dialog closes
  const handleOpenChange = useCallback((newOpen: boolean) => {
    if (!newOpen) {
      setStep('upload');
      setProgress(0);
      setParsedData(null);
      setErrors([]);
    }
    onOpenChange(newOpen);
  }, [onOpenChange]);

  // Parse Excel file
  const parseExcelFile = useCallback(async (file: File) => {
    setStep('parsing');
    setProgress(10);
    setErrors([]);

    try {
      // Dynamic import ExcelJS
      const ExcelJS = await import('exceljs');
      setProgress(20);

      const workbook = new ExcelJS.Workbook();
      const arrayBuffer = await file.arrayBuffer();
      await workbook.xlsx.load(arrayBuffer);
      setProgress(40);

      const result: ParsedExcelData = {
        teachers: [],
        schedules: [],
        classes: [],
        grades: [],
        errors: [],
      };

      // Parse KODE GURU sheet
      const teacherSheet = workbook.getWorksheet(EXCEL_SHEET_NAMES.TEACHERS);
      if (teacherSheet) {
        parseTeacherSheet(teacherSheet, result);
      } else {
        result.errors.push('Sheet "KODE GURU" tidak ditemukan');
      }
      setProgress(60);

      // Parse grade sheets
      const gradeSheets = [
        { name: EXCEL_SHEET_NAMES.GRADE_7, grade: '7' },
        { name: EXCEL_SHEET_NAMES.GRADE_8, grade: '8' },
        { name: EXCEL_SHEET_NAMES.GRADE_9, grade: '9' },
      ];

      for (const { name, grade } of gradeSheets) {
        const sheet = workbook.getWorksheet(name);
        if (sheet) {
          parseGradeSheet(sheet, grade, result);
          result.grades.push(grade);
        }
      }
      setProgress(90);

      // Validate
      if (result.teachers.length === 0) {
        result.errors.push('Tidak ada data guru yang ditemukan');
      }
      if (result.schedules.length === 0) {
        result.errors.push('Tidak ada data jadwal yang ditemukan');
      }

      setParsedData(result);
      setErrors(result.errors);
      setStep('preview');
      setProgress(100);
    } catch (err) {
      console.error('Excel parse error:', err);
      setErrors(['Gagal membaca file Excel: ' + (err as Error).message]);
      setStep('upload');
    }
  }, []);

  // Parse teacher sheet
  const parseTeacherSheet = (
    sheet: import('exceljs').Worksheet,
    result: ParsedExcelData
  ) => {
    let headerRowIdx = -1;

    // Find header row (contains "No" and "Nama Guru")
    sheet.eachRow((row, rowNum) => {
      const values = row.values as (string | number | undefined)[];
      if (
        values.some((v) => String(v).toLowerCase().includes('nama guru')) ||
        values.some((v) => String(v).toLowerCase() === 'no')
      ) {
        headerRowIdx = rowNum;
      }
    });

    if (headerRowIdx === -1) {
      result.errors.push('Header sheet KODE GURU tidak ditemukan');
      return;
    }

    // Parse data rows
    sheet.eachRow((row, rowNum) => {
      if (rowNum <= headerRowIdx) return;

      const values = row.values as (string | number | undefined)[];
      const no = values[1];
      const name = values[2];
      const subject = values[3];
      const jamPel = values[4];

      // Skip empty rows
      if (!no || !name) return;

      // Parse JP from "25 JP" format
      let maxJP: number | null = null;
      if (jamPel) {
        const jpMatch = String(jamPel).match(/(\d+)/);
        if (jpMatch) {
          maxJP = parseInt(jpMatch[1], 10);
        }
      }

      result.teachers.push({
        no: typeof no === 'number' ? no : parseInt(String(no), 10),
        code: String(typeof no === 'number' ? no : parseInt(String(no), 10)),
        name: String(name).trim(),
        subject: subject ? String(subject).trim() : '',
        maxJP,
        classSpread: values[5] ? String(values[5]) : '',
      });
    });
  };

  // Parse grade sheet
  const parseGradeSheet = (
    sheet: import('exceljs').Worksheet,
    grade: string,
    result: ParsedExcelData
  ) => {
    let currentDay = '';
    const classColumns: Map<number, string> = new Map();

    // Find header row with class names
    sheet.eachRow((row) => {
      const values = row.values as (string | number | undefined)[];

      // Look for class headers (A, B, C, D, or 7A, 7B, etc.)
      values.forEach((val, colIdx) => {
        if (!val) return;
        const valStr = String(val).trim();

        // Match class names like "A", "B (Bilingual)", "C", "D"
        if (/^[A-H](\s*\(.+\))?$/.test(valStr)) {
          classColumns.set(colIdx, `${grade}${valStr.charAt(0)}`);
          if (!result.classes.includes(`${grade}${valStr.charAt(0)}`)) {
            result.classes.push(`${grade}${valStr.charAt(0)}`);
          }
        }
        // Or already prefixed like "7A", "7B"
        if (new RegExp(`^${grade}[A-H]$`).test(valStr)) {
          classColumns.set(colIdx, valStr);
          if (!result.classes.includes(valStr)) {
            result.classes.push(valStr);
          }
        }
      });
    });

    // Parse schedule rows
    sheet.eachRow((row) => {
      const values = row.values as (string | number | undefined)[];

      // Check for day name
      const firstCell = values[1];
      if (firstCell) {
        const cellStr = String(firstCell).trim().replace(/\\n/g, '').trim();
        if (DAY_MAPPING[cellStr]) {
          currentDay = cellStr;
          return;
        }
      }

      // Get period number (column 2)
      const periodCell = values[2];
      if (!periodCell || !currentDay) return;

      const period = typeof periodCell === 'number'
        ? periodCell
        : parseInt(String(periodCell), 10);

      if (isNaN(period) || period < 1 || period > 10) return;

      // Parse each class column
      classColumns.forEach((className, colIdx) => {
        const cellValue = values[colIdx];
        if (!cellValue) return;

        const cellStr = String(cellValue).trim();
        if (cellStr === 'Istirahat' || cellStr === '-' || !cellStr) return;

        // Match pattern like "6-IPA" or "28-Bahasa Indonesia"
        const match = cellStr.match(CELL_VALUE_REGEX);
        if (match) {
          result.schedules.push({
            day: currentDay,
            period,
            className,
            teacherCode: match[1],
            subject: match[2].trim(),
            rawValue: cellStr,
          });
        }
      });
    });
  };

  // Handle file select
  const handleFileSelect = useCallback((e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;

    if (!file.name.endsWith('.xlsx') && !file.name.endsWith('.xls')) {
      showError('Error', 'File harus berformat Excel (.xlsx atau .xls)');
      return;
    }

    parseExcelFile(file);

    // Reset input
    if (fileInputRef.current) {
      fileInputRef.current.value = '';
    }
  }, [parseExcelFile, showError]);

  // Handle import
  const handleImport = useCallback(() => {
    if (!parsedData) return;

    setStep('importing');
    setProgress(50);

    try {
      importFromExcel(parsedData);
      setProgress(100);
      setStep('done');
      success('Berhasil', `${parsedData.teachers.length} guru dan ${parsedData.schedules.length} jadwal diimport`);
    } catch (_err) {
      showError('Gagal', 'Terjadi kesalahan saat import');
      setStep('preview');
    }
  }, [parsedData, importFromExcel, success, showError]);

  return (
    <Dialog open={open} onOpenChange={handleOpenChange}>
      <DialogContent className="max-w-2xl max-h-[80vh] flex flex-col">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2">
            <FileSpreadsheet className="h-5 w-5" />
            Import dari Excel
          </DialogTitle>
          <DialogDescription>
            Upload file Excel jadwal dengan format standar (Sheet: KODE GURU, Kelas 7, Kelas 8, Kelas 9)
          </DialogDescription>
        </DialogHeader>

        <div className="flex-1 overflow-auto py-4">
          {/* Upload Step */}
          {step === 'upload' && (
            <div className="space-y-4">
              {/* Download Template Button */}
              <div className="flex items-center justify-between p-3 bg-muted/50 rounded-lg border">
                <div className="flex items-center gap-3">
                  <FileSpreadsheet className="h-5 w-5 text-primary" />
                  <div>
                    <p className="text-sm font-medium">Belum punya template?</p>
                    <p className="text-xs text-muted-foreground">Download template Excel untuk diisi</p>
                  </div>
                </div>
                <Button
                  variant="outline"
                  size="sm"
                  onClick={handleDownloadTemplate}
                  disabled={isDownloading}
                >
                  {isDownloading ? (
                    <Loader2 className="h-4 w-4 mr-1 animate-spin" />
                  ) : (
                    <Download className="h-4 w-4 mr-1" />
                  )}
                  Download
                </Button>
              </div>

              <div
                className="border-2 border-dashed rounded-lg p-8 text-center cursor-pointer hover:bg-muted/50 transition-colors"
                onClick={() => fileInputRef.current?.click()}
              >
                <Upload className="h-10 w-10 mx-auto text-muted-foreground mb-3" />
                <p className="text-sm font-medium">Klik atau drag file Excel ke sini</p>
                <p className="text-xs text-muted-foreground mt-1">
                  Format: .xlsx atau .xls
                </p>
              </div>
              <input
                type="file"
                ref={fileInputRef}
                onChange={handleFileSelect}
                accept=".xlsx,.xls"
                className="hidden"
              />

              {errors.length > 0 && (
                <Alert variant="destructive">
                  <AlertTriangle className="h-4 w-4" />
                  <AlertDescription>
                    {errors.map((err, i) => (
                      <div key={i}>{err}</div>
                    ))}
                  </AlertDescription>
                </Alert>
              )}
            </div>
          )}

          {/* Parsing Step */}
          {step === 'parsing' && (
            <div className="space-y-4 text-center py-8">
              <Loader2 className="h-10 w-10 mx-auto animate-spin text-primary" />
              <p className="text-sm font-medium">Membaca file Excel...</p>
              <Progress value={progress} className="w-48 mx-auto" />
            </div>
          )}

          {/* Preview Step */}
          {step === 'preview' && parsedData && (
            <div className="space-y-4">
              {/* Summary */}
              <div className="grid grid-cols-3 gap-3">
                <div className="p-3 rounded-lg bg-muted/50 text-center">
                  <Users className="h-5 w-5 mx-auto mb-1 text-primary" />
                  <p className="text-2xl font-bold">{parsedData.teachers.length}</p>
                  <p className="text-xs text-muted-foreground">Guru</p>
                </div>
                <div className="p-3 rounded-lg bg-muted/50 text-center">
                  <Calendar className="h-5 w-5 mx-auto mb-1 text-primary" />
                  <p className="text-2xl font-bold">{parsedData.schedules.length}</p>
                  <p className="text-xs text-muted-foreground">Jadwal</p>
                </div>
                <div className="p-3 rounded-lg bg-muted/50 text-center">
                  <FileSpreadsheet className="h-5 w-5 mx-auto mb-1 text-primary" />
                  <p className="text-2xl font-bold">{parsedData.grades.length}</p>
                  <p className="text-xs text-muted-foreground">Grade</p>
                </div>
              </div>

              {/* Warnings */}
              {errors.length > 0 && (
                <Alert variant="default" className="border-warning bg-warning/10">
                  <AlertTriangle className="h-4 w-4 text-warning" />
                  <AlertDescription>
                    <p className="font-medium mb-1">Peringatan:</p>
                    {errors.map((err, i) => (
                      <div key={i} className="text-sm">{err}</div>
                    ))}
                  </AlertDescription>
                </Alert>
              )}

              {/* Teachers Preview */}
              <div>
                <h4 className="font-medium text-sm mb-2">Preview Guru (10 pertama)</h4>
                <ScrollArea className="h-40 border rounded-md">
                  <Table>
                    <TableHeader>
                      <TableRow>
                        <TableHead className="w-12">Kode</TableHead>
                        <TableHead>Nama</TableHead>
                        <TableHead>Mapel</TableHead>
                        <TableHead className="w-16">Max JP</TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      {parsedData.teachers.slice(0, 10).map((t) => (
                        <TableRow key={t.code}>
                          <TableCell className="font-mono">{t.code}</TableCell>
                          <TableCell>{t.name}</TableCell>
                          <TableCell>{t.subject}</TableCell>
                          <TableCell>
                            {t.maxJP !== null ? (
                              <Badge variant="secondary">{t.maxJP}</Badge>
                            ) : (
                              <span className="text-muted-foreground">-</span>
                            )}
                          </TableCell>
                        </TableRow>
                      ))}
                    </TableBody>
                  </Table>
                </ScrollArea>
              </div>

              {/* Schedules Preview */}
              <div>
                <h4 className="font-medium text-sm mb-2">Preview Jadwal (10 pertama)</h4>
                <ScrollArea className="h-40 border rounded-md">
                  <Table>
                    <TableHeader>
                      <TableRow>
                        <TableHead>Hari</TableHead>
                        <TableHead className="w-12">Jam</TableHead>
                        <TableHead>Kelas</TableHead>
                        <TableHead>Guru-Mapel</TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      {parsedData.schedules.slice(0, 10).map((s, i) => (
                        <TableRow key={i}>
                          <TableCell>{s.day}</TableCell>
                          <TableCell>{s.period}</TableCell>
                          <TableCell>{s.className}</TableCell>
                          <TableCell className="font-mono">{s.rawValue}</TableCell>
                        </TableRow>
                      ))}
                    </TableBody>
                  </Table>
                </ScrollArea>
              </div>
            </div>
          )}

          {/* Importing Step */}
          {step === 'importing' && (
            <div className="space-y-4 text-center py-8">
              <Loader2 className="h-10 w-10 mx-auto animate-spin text-primary" />
              <p className="text-sm font-medium">Mengimport data...</p>
              <Progress value={progress} className="w-48 mx-auto" />
            </div>
          )}

          {/* Done Step */}
          {step === 'done' && (
            <div className="space-y-4 text-center py-8">
              <CheckCircle className="h-12 w-12 mx-auto text-success" />
              <p className="text-lg font-medium">Import Berhasil!</p>
              <p className="text-sm text-muted-foreground">
                Data jadwal sudah dimuat ke grid. Anda bisa mulai mengedit.
              </p>
            </div>
          )}
        </div>

        <DialogFooter>
          {step === 'upload' && (
            <Button variant="outline" onClick={() => handleOpenChange(false)}>
              Batal
            </Button>
          )}

          {step === 'preview' && (
            <>
              <Button variant="outline" onClick={() => setStep('upload')}>
                Pilih File Lain
              </Button>
              <Button onClick={handleImport}>
                <Upload className="h-4 w-4 mr-1" />
                Import Data
              </Button>
            </>
          )}

          {step === 'done' && (
            <Button onClick={() => handleOpenChange(false)}>
              Selesai
            </Button>
          )}
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
