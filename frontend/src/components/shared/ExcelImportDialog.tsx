import { useState, useCallback, useRef } from 'react';
import ExcelJS from 'exceljs';
import {
    Upload,
    FileSpreadsheet,
    Download,
    X,
    AlertCircle,
    CheckCircle2,
    Loader2,
    FileWarning,
    Trash2,
} from 'lucide-react';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Progress } from '@/components/ui/progress';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { ScrollArea } from '@/components/ui/scroll-area';
import { cn } from '@/lib/utils';

// Types
export interface ExcelColumn {
    key: string;
    label: string;
    required: boolean;
    type: 'string' | 'number' | 'date' | 'email' | 'boolean';
    width?: number;
    validation?: (value: any) => string | null;
}

export interface ImportError {
    row: number;
    column: string;
    value: any;
    message: string;
}

export interface ImportResult {
    success: boolean;
    total: number;
    imported: number;
    updated: number;
    skipped: number;
    errors: ImportError[];
}

interface ParsedRow {
    rowNumber: number;
    data: Record<string, any>;
    errors: ImportError[];
    isValid: boolean;
}

interface ExcelImportDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    title: string;
    description?: string;
    templateUrl?: string;
    templateName?: string;
    expectedColumns: ExcelColumn[];
    onImport?: (data: Record<string, any>[]) => Promise<ImportResult>;
    onFileImport?: (file: File) => Promise<ImportResult>;
    onSuccess?: () => void;
    maxRows?: number;
    exampleData?: Record<string, any>[];
}

export function ExcelImportDialog({
    open,
    onOpenChange,
    title,
    description,
    templateUrl,
    templateName = 'template.xlsx',
    expectedColumns,
    onImport,
    onFileImport,
    onSuccess,
    maxRows = 1000,
    exampleData,
}: ExcelImportDialogProps) {
    const [file, setFile] = useState<File | null>(null);
    const [parsedData, setParsedData] = useState<ParsedRow[]>([]);
    const [step, setStep] = useState<'upload' | 'preview' | 'importing' | 'result'>('upload');
    const [importProgress, setImportProgress] = useState(0);
    const [importResult, setImportResult] = useState<ImportResult | null>(null);
    const [parseError, setParseError] = useState<string | null>(null);
    const [isDragging, setIsDragging] = useState(false);
    const fileInputRef = useRef<HTMLInputElement>(null);

    // Reset state when dialog opens/closes
    const handleOpenChange = (isOpen: boolean) => {
        if (!isOpen) {
            resetState();
        }
        onOpenChange(isOpen);
    };

    const resetState = () => {
        setFile(null);
        setParsedData([]);
        setStep('upload');
        setImportProgress(0);
        setImportResult(null);
        setParseError(null);
        setIsDragging(false);
    };

    // Validate a single cell value
    const validateCell = (value: any, column: ExcelColumn, rowNumber: number): ImportError | null => {
        // Check required
        if (column.required && (value === null || value === undefined || value === '')) {
            return {
                row: rowNumber,
                column: column.key,
                value,
                message: `${column.label} wajib diisi`,
            };
        }

        if (value === null || value === undefined || value === '') {
            return null;
        }

        // Type validation
        switch (column.type) {
            case 'email':
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(String(value))) {
                    return {
                        row: rowNumber,
                        column: column.key,
                        value,
                        message: `Format email tidak valid`,
                    };
                }
                break;
            case 'number':
                if (isNaN(Number(value))) {
                    return {
                        row: rowNumber,
                        column: column.key,
                        value,
                        message: `Harus berupa angka`,
                    };
                }
                break;
            case 'date':
                const dateValue = new Date(value);
                if (isNaN(dateValue.getTime())) {
                    return {
                        row: rowNumber,
                        column: column.key,
                        value,
                        message: `Format tanggal tidak valid`,
                    };
                }
                break;
        }

        // Custom validation
        if (column.validation) {
            const error = column.validation(value);
            if (error) {
                return {
                    row: rowNumber,
                    column: column.key,
                    value,
                    message: error,
                };
            }
        }

        return null;
    };

    // Helper function to convert ExcelJS worksheet to JSON
    const worksheetToJson = (worksheet: ExcelJS.Worksheet): Record<string, any>[] => {
        const jsonData: Record<string, any>[] = [];
        const headers: string[] = [];

        worksheet.eachRow((row, rowNumber) => {
            if (rowNumber === 1) {
                // First row is headers
                row.eachCell((cell, colNumber) => {
                    headers[colNumber - 1] = String(cell.value || '');
                });
            } else {
                // Data rows
                const rowData: Record<string, any> = {};
                row.eachCell((cell, colNumber) => {
                    const header = headers[colNumber - 1];
                    if (header) {
                        let value = cell.value;
                        // Handle date values
                        if (value instanceof Date) {
                            value = value.toISOString().split('T')[0];
                        }
                        // Handle formula results
                        if (typeof value === 'object' && value !== null && 'result' in value) {
                            value = (value as { result: any }).result;
                        }
                        rowData[header] = value;
                    }
                });
                if (Object.keys(rowData).length > 0) {
                    jsonData.push(rowData);
                }
            }
        });

        return jsonData;
    };

    // Parse Excel file
    const parseExcelFile = useCallback(async (file: File) => {
        setParseError(null);

        try {
            const data = await file.arrayBuffer();
            const workbook = new ExcelJS.Workbook();
            await workbook.xlsx.load(data);

            const worksheet = workbook.worksheets[0];
            if (!worksheet) {
                setParseError('File Excel kosong atau tidak memiliki worksheet');
                return;
            }

            // Convert to JSON
            const jsonData = worksheetToJson(worksheet);

            if (jsonData.length === 0) {
                setParseError('File Excel kosong atau tidak memiliki data');
                return;
            }

            if (jsonData.length > maxRows) {
                setParseError(`Jumlah baris melebihi batas maksimal (${maxRows} baris)`);
                return;
            }

            // Get headers from first row
            const headers = Object.keys(jsonData[0] as object);

            // Check required columns
            const missingColumns = expectedColumns
                .filter(col => col.required)
                .filter(col => !headers.some(h =>
                    h.toLowerCase().replace(/\s+/g, '_') === col.key.toLowerCase() ||
                    h.toLowerCase() === col.label.toLowerCase()
                ));

            if (missingColumns.length > 0) {
                setParseError(
                    `Kolom wajib tidak ditemukan: ${missingColumns.map(c => c.label).join(', ')}`
                );
                return;
            }

            // Parse and validate each row
            const parsed: ParsedRow[] = jsonData.map((row: any, index) => {
                const rowNumber = index + 2; // +2 because of header row and 0-index
                const errors: ImportError[] = [];
                const normalizedData: Record<string, any> = {};

                // Map headers to expected columns
                expectedColumns.forEach(column => {
                    // Find matching header (flexible matching)
                    const matchingHeader = Object.keys(row).find(h =>
                        h.toLowerCase().replace(/\s+/g, '_') === column.key.toLowerCase() ||
                        h.toLowerCase() === column.label.toLowerCase() ||
                        h.toLowerCase() === column.key.toLowerCase()
                    );

                    const value = matchingHeader ? row[matchingHeader] : null;
                    normalizedData[column.key] = value;

                    // Validate
                    const error = validateCell(value, column, rowNumber);
                    if (error) {
                        errors.push(error);
                    }
                });

                return {
                    rowNumber,
                    data: normalizedData,
                    errors,
                    isValid: errors.length === 0,
                };
            });

            setParsedData(parsed);
            setStep('preview');
        } catch (error) {
            console.error('Parse error:', error);
            setParseError('Gagal membaca file Excel. Pastikan format file benar.');
        }
    }, [expectedColumns, maxRows]);

    // Handle file drop
    const handleDrop = useCallback((e: React.DragEvent) => {
        e.preventDefault();
        setIsDragging(false);

        const droppedFile = e.dataTransfer.files[0];
        if (droppedFile && isValidFileType(droppedFile)) {
            setFile(droppedFile);
            parseExcelFile(droppedFile);
        } else {
            setParseError('Format file tidak didukung. Gunakan .xlsx, .xls, atau .csv');
        }
    }, [parseExcelFile]);

    const isValidFileType = (file: File) => {
        const validTypes = [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-excel',
            'text/csv',
        ];
        const validExtensions = ['.xlsx', '.xls', '.csv'];

        return validTypes.includes(file.type) ||
            validExtensions.some(ext => file.name.toLowerCase().endsWith(ext));
    };

    // Handle file input change
    const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const selectedFile = e.target.files?.[0];
        if (selectedFile) {
            if (isValidFileType(selectedFile)) {
                setFile(selectedFile);
                parseExcelFile(selectedFile);
            } else {
                setParseError('Format file tidak didukung. Gunakan .xlsx, .xls, atau .csv');
            }
        }
    };

    // Handle import
    const handleImport = async () => {
        const validData = parsedData.filter(row => row.isValid).map(row => row.data);

        if (validData.length === 0 && !onFileImport) {
            setParseError('Tidak ada data valid untuk diimport');
            return;
        }

        if (!file && onFileImport) {
            setParseError('File tidak ditemukan');
            return;
        }

        setStep('importing');
        setImportProgress(0);

        try {
            // Simulate progress
            const progressInterval = setInterval(() => {
                setImportProgress(prev => Math.min(prev + 10, 90));
            }, 200);

            let result: ImportResult;

            // If onFileImport is provided, use file-based import (send file to backend)
            if (onFileImport && file) {
                result = await onFileImport(file);
            } else if (onImport) {
                // Otherwise use data-based import
                result = await onImport(validData);
            } else {
                throw new Error('No import handler provided');
            }

            clearInterval(progressInterval);
            setImportProgress(100);
            setImportResult(result);
            setStep('result');

            // Call onSuccess callback after successful import
            if (result.success && onSuccess) {
                onSuccess();
            }
        } catch (error: any) {
            setImportResult({
                success: false,
                total: validData.length,
                imported: 0,
                updated: 0,
                skipped: 0,
                errors: [{
                    row: 0,
                    column: '',
                    value: '',
                    message: error.message || 'Terjadi kesalahan saat import',
                }],
            });
            setStep('result');
        }
    };

    // Download template
    const handleDownloadTemplate = async () => {
        if (templateUrl) {
            window.open(templateUrl, '_blank');
            return;
        }

        // Generate template from columns using ExcelJS
        const workbook = new ExcelJS.Workbook();
        const worksheet = workbook.addWorksheet('Template');

        // Set headers
        worksheet.columns = expectedColumns.map(col => ({
            header: col.label,
            key: col.key,
            width: col.width || 15,
        }));

        // Add example data
        const templateData = exampleData || [
            expectedColumns.reduce((acc, col) => {
                acc[col.key] = col.type === 'date' ? '2024-01-01' :
                    col.type === 'number' ? '0' :
                        col.type === 'email' ? 'email@example.com' :
                            col.type === 'boolean' ? 'Ya' : '';
                return acc;
            }, {} as Record<string, string>)
        ];

        templateData.forEach(row => {
            worksheet.addRow(row);
        });

        // Style header row
        worksheet.getRow(1).font = { bold: true };
        worksheet.getRow(1).fill = {
            type: 'pattern',
            pattern: 'solid',
            fgColor: { argb: 'FFE0E0E0' },
        };

        // Generate and download file
        const buffer = await workbook.xlsx.writeBuffer();
        const blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = templateName;
        link.click();
        URL.revokeObjectURL(url);
    };

    // Statistics
    const validCount = parsedData.filter(r => r.isValid).length;
    const errorCount = parsedData.filter(r => !r.isValid).length;
    const totalErrors = parsedData.reduce((sum, r) => sum + r.errors.length, 0);

    return (
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogContent className="max-w-4xl max-h-[90vh] flex flex-col">
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2">
                        <FileSpreadsheet className="h-5 w-5 text-primary" />
                        {title}
                    </DialogTitle>
                    {description && <DialogDescription>{description}</DialogDescription>}
                </DialogHeader>

                {/* Upload Step */}
                {step === 'upload' && (
                    <div className="flex-1 space-y-4">
                        {/* Drag & Drop Zone */}
                        <div
                            className={cn(
                                "border-2 border-dashed rounded-lg p-8 text-center transition-colors cursor-pointer",
                                isDragging ? "border-primary bg-primary/5" : "border-muted-foreground/25 hover:border-primary/50",
                                parseError && "border-destructive"
                            )}
                            onDragOver={(e) => { e.preventDefault(); setIsDragging(true); }}
                            onDragLeave={() => setIsDragging(false)}
                            onDrop={handleDrop}
                            onClick={() => fileInputRef.current?.click()}
                        >
                            <input
                                ref={fileInputRef}
                                type="file"
                                accept=".xlsx,.xls,.csv"
                                onChange={handleFileChange}
                                className="hidden"
                                title="Pilih file Excel untuk import"
                                aria-label="Pilih file Excel untuk import"
                            />

                            {file ? (
                                <div className="flex items-center justify-center gap-3">
                                    <FileSpreadsheet className="h-10 w-10 text-primary" />
                                    <div className="text-left">
                                        <p className="font-medium">{file.name}</p>
                                        <p className="text-sm text-muted-foreground">
                                            {(file.size / 1024).toFixed(1)} KB
                                        </p>
                                    </div>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        onClick={(e) => {
                                            e.stopPropagation();
                                            setFile(null);
                                            setParsedData([]);
                                            setParseError(null);
                                        }}
                                    >
                                        <Trash2 className="h-4 w-4 text-muted-foreground" />
                                    </Button>
                                </div>
                            ) : (
                                <>
                                    <Upload className="h-12 w-12 mx-auto text-muted-foreground mb-4" />
                                    <p className="text-lg font-medium mb-1">
                                        Drag & drop file Excel di sini
                                    </p>
                                    <p className="text-sm text-muted-foreground mb-4">
                                        atau klik untuk memilih file
                                    </p>
                                    <div className="flex gap-2 justify-center">
                                        <Badge variant="outline">.xlsx</Badge>
                                        <Badge variant="outline">.xls</Badge>
                                        <Badge variant="outline">.csv</Badge>
                                    </div>
                                </>
                            )}
                        </div>

                        {parseError && (
                            <Alert variant="destructive">
                                <AlertCircle className="h-4 w-4" />
                                <AlertTitle>Error</AlertTitle>
                                <AlertDescription>{parseError}</AlertDescription>
                            </Alert>
                        )}

                        {/* Expected Columns */}
                        <div className="bg-muted/50 rounded-lg p-4">
                            <h4 className="font-medium mb-2 flex items-center gap-2">
                                <FileWarning className="h-4 w-4" />
                                Kolom yang diharapkan:
                            </h4>
                            <div className="flex flex-wrap gap-2">
                                {expectedColumns.map(col => (
                                    <Badge
                                        key={col.key}
                                        variant={col.required ? "default" : "secondary"}
                                    >
                                        {col.label}
                                        {col.required && <span className="text-red-300 ml-1">*</span>}
                                    </Badge>
                                ))}
                            </div>
                        </div>

                        <Button variant="outline" onClick={handleDownloadTemplate} className="w-full">
                            <Download className="h-4 w-4 mr-2" />
                            Download Template Excel
                        </Button>
                    </div>
                )}

                {/* Preview Step */}
                {step === 'preview' && (
                    <div className="flex-1 flex flex-col space-y-4 min-h-0">
                        {/* Stats */}
                        <div className="flex gap-4">
                            <div className="flex items-center gap-2 px-3 py-2 bg-muted rounded-lg">
                                <span className="text-sm text-muted-foreground">Total:</span>
                                <span className="font-semibold">{parsedData.length}</span>
                            </div>
                            <div className="flex items-center gap-2 px-3 py-2 bg-green-500/10 text-green-600 rounded-lg">
                                <CheckCircle2 className="h-4 w-4" />
                                <span className="font-semibold">{validCount} valid</span>
                            </div>
                            {errorCount > 0 && (
                                <div className="flex items-center gap-2 px-3 py-2 bg-destructive/10 text-destructive rounded-lg">
                                    <AlertCircle className="h-4 w-4" />
                                    <span className="font-semibold">{errorCount} error ({totalErrors} masalah)</span>
                                </div>
                            )}
                        </div>

                        {/* Preview Table */}
                        <ScrollArea className="flex-1 border rounded-lg">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="w-12 sticky left-0 bg-background">#</TableHead>
                                        <TableHead className="w-20 sticky left-12 bg-background">Status</TableHead>
                                        {expectedColumns.map(col => (
                                            <TableHead key={col.key} className="min-w-[120px]">
                                                {col.label}
                                                {col.required && <span className="text-destructive ml-1">*</span>}
                                            </TableHead>
                                        ))}
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {parsedData.slice(0, 50).map((row) => (
                                        <TableRow
                                            key={row.rowNumber}
                                            className={!row.isValid ? 'bg-destructive/5' : ''}
                                        >
                                            <TableCell className="sticky left-0 bg-background font-mono text-xs">
                                                {row.rowNumber}
                                            </TableCell>
                                            <TableCell className="sticky left-12 bg-background">
                                                {row.isValid ? (
                                                    <CheckCircle2 className="h-4 w-4 text-green-500" />
                                                ) : (
                                                    <AlertCircle className="h-4 w-4 text-destructive" />
                                                )}
                                            </TableCell>
                                            {expectedColumns.map(col => {
                                                const cellError = row.errors.find(e => e.column === col.key);
                                                return (
                                                    <TableCell
                                                        key={col.key}
                                                        className={cellError ? 'text-destructive' : ''}
                                                        title={cellError?.message}
                                                    >
                                                        <span className="truncate block max-w-[200px]">
                                                            {row.data[col.key] || '-'}
                                                        </span>
                                                        {cellError && (
                                                            <span className="text-xs block text-destructive">
                                                                {cellError.message}
                                                            </span>
                                                        )}
                                                    </TableCell>
                                                );
                                            })}
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                            {parsedData.length > 50 && (
                                <div className="text-center py-4 text-sm text-muted-foreground bg-muted/50">
                                    Menampilkan 50 dari {parsedData.length} baris
                                </div>
                            )}
                        </ScrollArea>
                    </div>
                )}

                {/* Importing Step */}
                {step === 'importing' && (
                    <div className="flex-1 flex flex-col items-center justify-center py-12 space-y-4">
                        <Loader2 className="h-12 w-12 animate-spin text-primary" />
                        <div className="text-center">
                            <p className="font-medium mb-2">Mengimport data...</p>
                            <p className="text-sm text-muted-foreground">
                                {validCount} data sedang diproses
                            </p>
                        </div>
                        <Progress value={importProgress} className="w-64" />
                    </div>
                )}

                {/* Result Step */}
                {step === 'result' && importResult && (
                    <div className="flex-1 space-y-4">
                        {importResult.success ? (
                            <Alert className="bg-green-500/10 border-green-500/20">
                                <CheckCircle2 className="h-4 w-4 text-green-500" />
                                <AlertTitle className="text-green-600">Import Berhasil!</AlertTitle>
                                <AlertDescription>
                                    Data berhasil diimport ke sistem.
                                </AlertDescription>
                            </Alert>
                        ) : (
                            <Alert variant="destructive">
                                <AlertCircle className="h-4 w-4" />
                                <AlertTitle>Import Gagal</AlertTitle>
                                <AlertDescription>
                                    Terjadi kesalahan saat mengimport data.
                                </AlertDescription>
                            </Alert>
                        )}

                        {/* Statistics */}
                        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div className="bg-muted rounded-lg p-4 text-center">
                                <p className="text-2xl font-bold">{importResult.total}</p>
                                <p className="text-sm text-muted-foreground">Total</p>
                            </div>
                            <div className="bg-green-500/10 rounded-lg p-4 text-center">
                                <p className="text-2xl font-bold text-green-600">{importResult.imported}</p>
                                <p className="text-sm text-muted-foreground">Ditambahkan</p>
                            </div>
                            <div className="bg-blue-500/10 rounded-lg p-4 text-center">
                                <p className="text-2xl font-bold text-blue-600">{importResult.updated}</p>
                                <p className="text-sm text-muted-foreground">Diperbarui</p>
                            </div>
                            <div className="bg-amber-500/10 rounded-lg p-4 text-center">
                                <p className="text-2xl font-bold text-amber-600">{importResult.skipped}</p>
                                <p className="text-sm text-muted-foreground">Dilewati</p>
                            </div>
                        </div>

                        {/* Errors */}
                        {importResult.errors.length > 0 && (
                            <div className="space-y-2">
                                <h4 className="font-medium text-destructive">
                                    Error ({importResult.errors.length}):
                                </h4>
                                <ScrollArea className="h-40 border border-destructive/20 rounded-lg p-3">
                                    {importResult.errors.map((error, i) => (
                                        <div key={i} className="text-sm py-1 border-b last:border-0">
                                            {error.row > 0 && (
                                                <span className="text-muted-foreground">Baris {error.row}: </span>
                                            )}
                                            {error.message}
                                        </div>
                                    ))}
                                </ScrollArea>
                            </div>
                        )}
                    </div>
                )}

                <DialogFooter>
                    {step === 'upload' && (
                        <Button variant="outline" onClick={() => handleOpenChange(false)}>
                            Batal
                        </Button>
                    )}

                    {step === 'preview' && (
                        <>
                            <Button variant="outline" onClick={() => { setStep('upload'); setFile(null); setParsedData([]); }}>
                                <X className="h-4 w-4 mr-2" />
                                Ganti File
                            </Button>
                            <Button
                                onClick={handleImport}
                                disabled={validCount === 0}
                            >
                                <Upload className="h-4 w-4 mr-2" />
                                Import {validCount} Data
                            </Button>
                        </>
                    )}

                    {step === 'result' && (
                        <Button onClick={() => handleOpenChange(false)}>
                            Selesai
                        </Button>
                    )}
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
