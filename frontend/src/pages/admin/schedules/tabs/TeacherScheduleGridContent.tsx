/**
 * Teacher Schedule Grid Builder
 * 
 * A grid-based teacher scheduling system integrated with the existing system:
 * - Fetches teachers from API
 * - Supports teacher code generation/assignment
 * - Interactive grid (Rows: Days, Cols: Classes x Periods)
 * - Validation: Max 1 per day, Max 2 per week
 * - Context menu: Lock cells, Swap cells
 * - JSON Save/Load, Excel Export
 */

import React, { useState, useCallback, useRef, useEffect, useMemo } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { Label } from '@/components/ui/label';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/components/ui/command';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import {
    Upload,
    FileJson,
    FileSpreadsheet,
    Lock,
    Unlock,
    ArrowRightLeft,
    Trash2,
    CheckCircle2,
    AlertTriangle,
    GripVertical,
    Users,
    Plus,
    Check,
    ChevronsUpDown,
    Loader2,
    RefreshCw,
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { useNotificationStore } from '@/stores/notification-store';
import { useEmployees } from '@/hooks/use-employees';
import { useAcademicClasses } from '@/hooks/use-schedules';
import type { Employee } from '@/types/employee';
import type { AcademicClass } from '@/types/schedule';
import { ExcelScheduleImporterIntegrated } from './ExcelScheduleImporterIntegrated';

// Types for Excel import
interface ParsedScheduleCell {
    day: string;
    period: number;
    className: string;
    teacherCode: string;
    subject: string;
    rawValue: string;
}

interface ParsedScheduleData {
    teachers: { code: string; name: string; subject: string }[];
    schedules: ParsedScheduleCell[];
    classes: string[];
    days: string[];
    errors: string[];
}

// Types
interface TeacherWithCode {
    id: string;
    name: string;
    employeeId: string;
    code: string;
    color: string;
}

interface ScheduleCell {
    teacherId: string;
    teacherCode: string;
    teacherName: string;
    isLocked: boolean;
    color: string;
}

type ScheduleData = {
    [day: string]: {
        [classKey: string]: {
            [period: number]: ScheduleCell | null;
        };
    };
};

interface ContextMenuItem {
    label: string;
    icon: React.ReactNode;
    onClick: () => void;
    disabled?: boolean;
}

// Constants
const DAYS = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
const PERIODS = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];

// Generate deterministic pastel color based on string
const generateColor = (code: string): string => {
    let hash = 0;
    for (let i = 0; i < code.length; i++) {
        hash = code.charCodeAt(i) + ((hash << 5) - hash);
    }
    const h = Math.abs(hash % 360);
    return `hsl(${h}, 70%, 80%)`;
};

// Generate short code from name
const generateCodeFromName = (name: string, existingCodes: string[]): string => {
    // Get initials
    const words = name.split(' ').filter(w => w.length > 0);
    let code = '';

    if (words.length >= 2) {
        code = words[0][0].toUpperCase() + words[1][0].toUpperCase();
    } else if (words.length === 1) {
        code = words[0].substring(0, 2).toUpperCase();
    }

    // If code exists, add number suffix
    let finalCode = code;
    let counter = 1;
    while (existingCodes.includes(finalCode)) {
        finalCode = code + counter;
        counter++;
    }

    return finalCode;
};

// Initialize empty schedule
const initializeSchedule = (classes: string[]): ScheduleData => {
    const schedule: ScheduleData = {};
    DAYS.forEach(day => {
        schedule[day] = {};
        classes.forEach(classKey => {
            schedule[day][classKey] = {};
            PERIODS.forEach(period => {
                schedule[day][classKey][period] = null;
            });
        });
    });
    return schedule;
};

export function TeacherScheduleGridContent() {
    const { success, error: showError, warning } = useNotificationStore();

    // Fetch employees (teachers)
    const { data: employeesData, isLoading: loadingEmployees, refetch: refetchEmployees } = useEmployees({
        status: 'active',
        per_page: 200,
    });

    // Fetch classes
    const { data: classesData, isLoading: loadingClasses } = useAcademicClasses();

    // State
    const [teacherCodes, setTeacherCodes] = useState<Map<string, TeacherWithCode>>(new Map());
    const [schedule, setSchedule] = useState<ScheduleData>({});
    const [activeTeacher, setActiveTeacher] = useState<TeacherWithCode | null>(null);
    const [contextMenu, setContextMenu] = useState<{
        x: number;
        y: number;
        day: string;
        classKey: string;
        period: number;
    } | null>(null);
    const [swapMode, setSwapMode] = useState<{
        active: boolean;
        source?: { day: string; classKey: string; period: number };
    }>({ active: false });
    const [showAddTeacherDialog, setShowAddTeacherDialog] = useState(false);
    const [newTeacherData, setNewTeacherData] = useState<{
        employeeId: string;
        customCode: string;
    }>({ employeeId: '', customCode: '' });
    const [teacherSelectOpen, setTeacherSelectOpen] = useState(false);
    const [classFilter, setClassFilter] = useState<string[]>([]);
    const [showExcelImport, setShowExcelImport] = useState(false);

    const fileInputRef = useRef<HTMLInputElement>(null);
    const contextMenuRef = useRef<HTMLDivElement>(null);

    // Process employees into teacher options
    const employeeOptions = useMemo(() => {
        if (!employeesData?.data) return [];
        return employeesData.data
            .filter((emp: Employee) =>
                // Filter teachers - you can customize this condition
                emp.position?.toLowerCase().includes('guru') ||
                emp.department?.toLowerCase().includes('pengajar') ||
                emp.employee_type_id // Has employee type assigned
            )
            .map((emp: Employee) => ({
                id: emp.id,
                name: emp.name,
                employeeId: emp.employee_id,
                position: emp.position,
                department: emp.department,
            }));
    }, [employeesData]);

    // Get class names from API or use defaults
    const classList = useMemo(() => {
        if (classesData && classesData.length > 0) {
            return classesData.map((c: AcademicClass) => c.name);
        }
        // Default classes if API doesn't return any
        return ['Kelas X', 'Kelas XI', 'Kelas XII'];
    }, [classesData]);

    // Initialize schedule when classes load
    useEffect(() => {
        if (classList.length > 0 && Object.keys(schedule).length === 0) {
            setSchedule(initializeSchedule(classList));
        }
    }, [classList, schedule]);

    // Filter classes based on selection
    const displayedClasses = useMemo(() => {
        if (classFilter.length === 0) return classList;
        return classList.filter((c: string) => classFilter.includes(c));
    }, [classList, classFilter]);

    // Get teacher stats
    const getTeacherStats = useCallback(() => {
        const stats: { [id: string]: { total: number; byDay: { [day: string]: number } } } = {};

        DAYS.forEach(day => {
            displayedClasses.forEach((classKey: string) => {
                PERIODS.forEach(period => {
                    const cell = schedule[day]?.[classKey]?.[period];
                    if (cell?.teacherId) {
                        if (!stats[cell.teacherId]) {
                            stats[cell.teacherId] = { total: 0, byDay: {} };
                        }
                        stats[cell.teacherId].total++;
                        stats[cell.teacherId].byDay[day] = (stats[cell.teacherId].byDay[day] || 0) + 1;
                    }
                });
            });
        });

        return stats;
    }, [schedule, displayedClasses]);

    // Validation
    const validatePlacement = useCallback((day: string, teacherId: string): { valid: boolean; message?: string } => {
        if (!teacherId) return { valid: false, message: 'Pilih guru terlebih dahulu' };

        const stats = getTeacherStats();
        const teacherStats = stats[teacherId] || { total: 0, byDay: {} };

        // Rule 1: Max 1 per day
        const currentDayCount = teacherStats.byDay[day] || 0;
        if (currentDayCount >= 1) {
            return {
                valid: false,
                message: 'Validasi Gagal: Maksimal pertemuan guru dalam satu hari adalah satu kali.'
            };
        }

        // Rule 2: Max 2 per week
        if (teacherStats.total >= 2) {
            return {
                valid: false,
                message: 'Validasi Gagal: Maksimal pertemuan guru dalam satu minggu adalah dua kali.'
            };
        }

        return { valid: true };
    }, [getTeacherStats]);

    // Handle cell click
    const handleCellClick = useCallback((day: string, classKey: string, period: number) => {
        if (!activeTeacher) {
            warning('Peringatan', 'Silakan pilih guru terlebih dahulu');
            return;
        }

        const currentCell = schedule[day]?.[classKey]?.[period];

        if (currentCell?.isLocked) {
            warning('Peringatan', 'Sel ini terkunci. Klik kanan untuk membuka kunci.');
            return;
        }

        // Swap mode
        if (swapMode.active && swapMode.source) {
            const source = swapMode.source;
            const sourceCell = schedule[source.day]?.[source.classKey]?.[source.period];

            setSchedule(prev => {
                const newSchedule = { ...prev };
                newSchedule[source.day] = { ...newSchedule[source.day] };
                newSchedule[source.day][source.classKey] = { ...newSchedule[source.day][source.classKey] };
                newSchedule[day] = { ...newSchedule[day] };
                newSchedule[day][classKey] = { ...newSchedule[day][classKey] };

                newSchedule[source.day][source.classKey][source.period] = currentCell;
                newSchedule[day][classKey][period] = sourceCell;

                return newSchedule;
            });

            setSwapMode({ active: false });
            success('Berhasil', 'Sel berhasil ditukar');
            return;
        }

        // Toggle: If same teacher, clear. Otherwise, place.
        if (currentCell?.teacherId === activeTeacher.id) {
            setSchedule(prev => {
                const newSchedule = { ...prev };
                newSchedule[day] = { ...newSchedule[day] };
                newSchedule[day][classKey] = { ...newSchedule[day][classKey] };
                newSchedule[day][classKey][period] = null;
                return newSchedule;
            });
        } else {
            const validation = validatePlacement(day, activeTeacher.id);
            if (!validation.valid) {
                showError('Validasi Gagal', validation.message || 'Tidak dapat menempatkan guru');
                return;
            }

            setSchedule(prev => {
                const newSchedule = { ...prev };
                newSchedule[day] = { ...newSchedule[day] };
                newSchedule[day][classKey] = { ...newSchedule[day][classKey] };
                newSchedule[day][classKey][period] = {
                    teacherId: activeTeacher.id,
                    teacherCode: activeTeacher.code,
                    teacherName: activeTeacher.name,
                    isLocked: false,
                    color: activeTeacher.color
                };
                return newSchedule;
            });
        }
    }, [activeTeacher, schedule, swapMode, validatePlacement, success, showError, warning]);

    // Context menu handlers
    const handleContextMenu = useCallback((
        e: React.MouseEvent,
        day: string,
        classKey: string,
        period: number
    ) => {
        e.preventDefault();
        setContextMenu({ x: e.clientX, y: e.clientY, day, classKey, period });
    }, []);

    useEffect(() => {
        const handleClickOutside = (e: MouseEvent) => {
            if (contextMenuRef.current && !contextMenuRef.current.contains(e.target as Node)) {
                setContextMenu(null);
            }
        };
        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    const handleToggleLock = useCallback(() => {
        if (!contextMenu) return;

        const { day, classKey, period } = contextMenu;
        setSchedule(prev => {
            const newSchedule = { ...prev };
            newSchedule[day] = { ...newSchedule[day] };
            newSchedule[day][classKey] = { ...newSchedule[day][classKey] };

            const currentCell = newSchedule[day][classKey][period];
            if (currentCell) {
                newSchedule[day][classKey][period] = {
                    ...currentCell,
                    isLocked: !currentCell.isLocked
                };
            }
            return newSchedule;
        });
        setContextMenu(null);
    }, [contextMenu]);

    const handleStartSwap = useCallback(() => {
        if (!contextMenu) return;

        const { day, classKey, period } = contextMenu;
        setSwapMode({
            active: true,
            source: { day, classKey, period }
        });
        setContextMenu(null);
        success('Mode Tukar Aktif', 'Klik sel lain untuk menukar posisi');
    }, [contextMenu, success]);

    const handleCancelSwap = useCallback(() => {
        setSwapMode({ active: false });
    }, []);

    // Add teacher with code
    const handleAddTeacher = useCallback(() => {
        const employee = employeeOptions.find(e => e.id === newTeacherData.employeeId);
        if (!employee) {
            showError('Error', 'Pilih guru dari daftar');
            return;
        }

        const existingCodes = Array.from(teacherCodes.values()).map(t => t.code);
        const code = newTeacherData.customCode.trim().toUpperCase() ||
            generateCodeFromName(employee.name, existingCodes);

        if (existingCodes.includes(code)) {
            showError('Error', `Kode "${code}" sudah digunakan. Gunakan kode lain.`);
            return;
        }

        const newTeacher: TeacherWithCode = {
            id: employee.id,
            name: employee.name,
            employeeId: employee.employeeId,
            code: code,
            color: generateColor(code),
        };

        setTeacherCodes(prev => new Map(prev).set(employee.id, newTeacher));
        setActiveTeacher(newTeacher);
        setShowAddTeacherDialog(false);
        setNewTeacherData({ employeeId: '', customCode: '' });
        success('Berhasil', `Guru "${employee.name}" ditambahkan dengan kode "${code}"`);
    }, [employeeOptions, newTeacherData, teacherCodes, success, showError]);

    // Save/Load JSON
    const handleSaveJson = useCallback(() => {
        const exportData = {
            schedule,
            teacherCodes: Array.from(teacherCodes.entries()),
            exportedAt: new Date().toISOString(),
        };
        const dataStr = JSON.stringify(exportData, null, 2);
        const blob = new Blob([dataStr], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `jadwal-mengajar-${new Date().toISOString().split('T')[0]}.json`;
        link.click();
        URL.revokeObjectURL(url);
        success('Berhasil', 'Jadwal berhasil disimpan sebagai JSON');
    }, [schedule, teacherCodes, success]);

    const handleLoadJson = useCallback((event: React.ChangeEvent<HTMLInputElement>) => {
        const file = event.target.files?.[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = (e) => {
            try {
                const content = e.target?.result as string;
                const data = JSON.parse(content);

                if (data.schedule) {
                    setSchedule(data.schedule);
                }
                if (data.teacherCodes) {
                    setTeacherCodes(new Map(data.teacherCodes));
                }
                success('Berhasil', 'Jadwal berhasil dimuat dari file');
            } catch (err) {
                showError('Error', 'Gagal memuat file JSON. Pastikan format file benar.');
            }
        };
        reader.readAsText(file);

        if (fileInputRef.current) {
            fileInputRef.current.value = '';
        }
    }, [success, showError]);

    // Export Excel
    const handleExportExcel = useCallback(async () => {
        try {
            const XLSX = await import('xlsx');
            const wb = XLSX.utils.book_new();

            const data: (string | number | null)[][] = [];

            const headerRow1: (string | null)[] = ['Hari'];
            displayedClasses.forEach((classKey: string) => {
                headerRow1.push(classKey);
                for (let i = 1; i < PERIODS.length; i++) {
                    headerRow1.push(null);
                }
            });
            data.push(headerRow1);

            const headerRow2: (string | number)[] = [''];
            displayedClasses.forEach(() => {
                PERIODS.forEach(period => {
                    headerRow2.push(period);
                });
            });
            data.push(headerRow2);

            DAYS.forEach(day => {
                const row: (string | number)[] = [day];
                displayedClasses.forEach((classKey: string) => {
                    PERIODS.forEach(period => {
                        const cell = schedule[day]?.[classKey]?.[period];
                        row.push(cell?.teacherCode || '');
                    });
                });
                data.push(row);
            });

            const ws = XLSX.utils.aoa_to_sheet(data);
            ws['!cols'] = [
                { wch: 10 },
                ...Array(displayedClasses.length * PERIODS.length).fill({ wch: 5 })
            ];

            ws['!merges'] = [];
            displayedClasses.forEach((_: string, classIndex: number) => {
                const startCol = 1 + classIndex * PERIODS.length;
                const endCol = startCol + PERIODS.length - 1;
                ws['!merges']!.push({
                    s: { r: 0, c: startCol },
                    e: { r: 0, c: endCol }
                });
            });

            XLSX.utils.book_append_sheet(wb, ws, 'Jadwal Mengajar');
            XLSX.writeFile(wb, `jadwal-mengajar-${new Date().toISOString().split('T')[0]}.xlsx`);
            success('Berhasil', 'Jadwal berhasil diekspor ke Excel');
        } catch (err) {
            showError('Error', 'Gagal mengekspor ke Excel');
            console.error(err);
        }
    }, [schedule, displayedClasses, success, showError]);

    const handleClearAll = useCallback(() => {
        if (confirm('Apakah Anda yakin ingin menghapus semua jadwal?')) {
            setSchedule(initializeSchedule(classList));
            success('Berhasil', 'Semua jadwal berhasil dihapus');
        }
    }, [classList, success]);

    // Handle Excel Import
    const handleExcelImport = useCallback((data: ParsedScheduleData) => {
        // 1. Register teachers from Excel
        const newTeacherCodes = new Map(teacherCodes);

        data.teachers.forEach(teacher => {
            if (!Array.from(newTeacherCodes.values()).some(t => t.code === teacher.code)) {
                // Generate a unique ID for this teacher (will be replaced if matched to system employee)
                const id = `excel-${teacher.code}`;
                newTeacherCodes.set(id, {
                    id: id,
                    name: teacher.name,
                    employeeId: '', // Will be empty for imported teachers
                    code: teacher.code,
                    color: generateColor(teacher.code),
                });
            }
        });

        setTeacherCodes(newTeacherCodes);

        // 2. Build new schedule from imported data
        // First, get unique classes from import
        const importedClasses = data.classes;
        const newSchedule = initializeSchedule(importedClasses.length > 0 ? importedClasses : classList);

        // 3. Populate schedule cells
        let placedCount = 0;
        data.schedules.forEach(cell => {
            const teacher = Array.from(newTeacherCodes.values()).find(t => t.code === cell.teacherCode);
            if (teacher && newSchedule[cell.day]?.[cell.className]) {
                newSchedule[cell.day][cell.className][cell.period] = {
                    teacherId: teacher.id,
                    teacherCode: teacher.code,
                    teacherName: teacher.name,
                    isLocked: false,
                    color: teacher.color,
                };
                placedCount++;
            }
        });

        setSchedule(newSchedule);

        // Update class filter to show new classes if needed
        if (importedClasses.length > 0) {
            setClassFilter([]);
        }

        setShowExcelImport(false);
        success('Import Berhasil', `${data.teachers.length} guru dan ${placedCount} jadwal berhasil diimport`);
    }, [teacherCodes, classList, success]);

    // Context menu items
    const getContextMenuItems = useCallback((): ContextMenuItem[] => {
        if (!contextMenu) return [];

        const { day, classKey, period } = contextMenu;
        const cell = schedule[day]?.[classKey]?.[period];

        const items: ContextMenuItem[] = [];

        if (cell) {
            items.push({
                label: cell.isLocked ? 'Buka Kunci' : 'Kunci Sel',
                icon: cell.isLocked ? <Unlock className="h-4 w-4" /> : <Lock className="h-4 w-4" />,
                onClick: handleToggleLock
            });
        }

        if (!swapMode.active) {
            items.push({
                label: 'Mulai Proses Tukar',
                icon: <ArrowRightLeft className="h-4 w-4" />,
                onClick: handleStartSwap,
                disabled: !cell
            });
        } else {
            items.push({
                label: 'Tukar dengan Sel Ini',
                icon: <ArrowRightLeft className="h-4 w-4" />,
                onClick: () => {
                    handleCellClick(day, classKey, period);
                    setContextMenu(null);
                }
            });
        }

        return items;
    }, [contextMenu, schedule, swapMode, handleToggleLock, handleStartSwap, handleCellClick]);

    const teacherStats = getTeacherStats();
    const registeredTeachers = Array.from(teacherCodes.values());

    if (loadingEmployees || loadingClasses) {
        return (
            <Card>
                <CardContent className="flex items-center justify-center py-12">
                    <Loader2 className="h-8 w-8 animate-spin text-primary" />
                    <span className="ml-2">Memuat data...</span>
                </CardContent>
            </Card>
        );
    }

    return (
        <div className="space-y-6">
            {/* Header */}
            <Card>
                <CardHeader className="pb-3">
                    <CardTitle className="text-xl flex items-center gap-2">
                        <GripVertical className="h-5 w-5 text-primary" />
                        Penyusunan Jadwal Mengajar Guru
                    </CardTitle>
                    <CardDescription>
                        Susun jadwal mengajar guru dengan sistem grid interaktif. Pilih atau tambahkan guru terlebih dahulu.
                    </CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                    {/* Teacher Selection */}
                    <div className="flex flex-wrap items-center gap-4">
                        <div className="flex items-center gap-2">
                            <Popover open={teacherSelectOpen} onOpenChange={setTeacherSelectOpen}>
                                <PopoverTrigger asChild>
                                    <Button
                                        variant="outline"
                                        role="combobox"
                                        aria-expanded={teacherSelectOpen}
                                        className="w-[300px] justify-between"
                                    >
                                        {activeTeacher ? (
                                            <span className="flex items-center gap-2">
                                                <div
                                                    className="w-4 h-4 rounded"
                                                    style={{ backgroundColor: activeTeacher.color }}
                                                />
                                                {activeTeacher.code} - {activeTeacher.name}
                                            </span>
                                        ) : (
                                            "Pilih Guru..."
                                        )}
                                        <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                                    </Button>
                                </PopoverTrigger>
                                <PopoverContent className="w-[300px] p-0">
                                    <Command>
                                        <CommandInput placeholder="Cari guru..." />
                                        <CommandList>
                                            <CommandEmpty>Tidak ada guru ditemukan.</CommandEmpty>
                                            <CommandGroup heading="Guru Terdaftar">
                                                {registeredTeachers.map((teacher) => (
                                                    <CommandItem
                                                        key={teacher.id}
                                                        value={teacher.name}
                                                        onSelect={() => {
                                                            setActiveTeacher(teacher);
                                                            setTeacherSelectOpen(false);
                                                        }}
                                                    >
                                                        <Check
                                                            className={cn(
                                                                "mr-2 h-4 w-4",
                                                                activeTeacher?.id === teacher.id ? "opacity-100" : "opacity-0"
                                                            )}
                                                        />
                                                        <div
                                                            className="w-4 h-4 rounded mr-2"
                                                            style={{ backgroundColor: teacher.color }}
                                                        />
                                                        <span className="font-mono mr-2">{teacher.code}</span>
                                                        <span className="truncate">{teacher.name}</span>
                                                    </CommandItem>
                                                ))}
                                            </CommandGroup>
                                        </CommandList>
                                    </Command>
                                </PopoverContent>
                            </Popover>

                            <Button onClick={() => setShowAddTeacherDialog(true)} variant="outline">
                                <Plus className="h-4 w-4 mr-2" />
                                Tambah Guru
                            </Button>
                        </div>

                        {activeTeacher && (
                            <Badge
                                className="text-sm py-1 px-3"
                                style={{ backgroundColor: activeTeacher.color }}
                            >
                                Guru Aktif: <strong className="ml-1">{activeTeacher.code}</strong>
                            </Badge>
                        )}

                        {swapMode.active && (
                            <Badge variant="destructive" className="text-sm py-1 px-3 animate-pulse">
                                <ArrowRightLeft className="h-4 w-4 mr-2" />
                                Mode Tukar Aktif
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    className="ml-2 h-5 px-1"
                                    onClick={handleCancelSwap}
                                >
                                    Batal
                                </Button>
                            </Badge>
                        )}
                    </div>

                    {/* Class Filter */}
                    <div className="flex flex-wrap items-center gap-2">
                        <Label className="text-sm font-medium">Filter Kelas:</Label>
                        {classList.map((cls: string) => (
                            <Button
                                key={cls}
                                size="sm"
                                variant={classFilter.includes(cls) || classFilter.length === 0 ? "default" : "outline"}
                                onClick={() => {
                                    if (classFilter.includes(cls)) {
                                        setClassFilter(classFilter.filter(c => c !== cls));
                                    } else {
                                        setClassFilter([...classFilter, cls]);
                                    }
                                }}
                            >
                                {cls}
                            </Button>
                        ))}
                        {classFilter.length > 0 && (
                            <Button size="sm" variant="ghost" onClick={() => setClassFilter([])}>
                                Tampilkan Semua
                            </Button>
                        )}
                    </div>

                    <Separator />

                    {/* Action Buttons */}
                    <div className="flex flex-wrap items-center gap-2">
                        <Button variant="outline" onClick={handleSaveJson}>
                            <FileJson className="h-4 w-4 mr-2" />
                            Simpan (JSON)
                        </Button>
                        <Button variant="outline" onClick={() => fileInputRef.current?.click()}>
                            <Upload className="h-4 w-4 mr-2" />
                            Muat (JSON)
                        </Button>
                        <input
                            ref={fileInputRef}
                            type="file"
                            accept=".json"
                            onChange={handleLoadJson}
                            className="hidden"
                            title="Muat file jadwal JSON"
                            aria-label="Muat file jadwal JSON"
                        />
                        <Button variant="outline" onClick={handleExportExcel}>
                            <FileSpreadsheet className="h-4 w-4 mr-2" />
                            Export Excel
                        </Button>
                        <Button variant="default" onClick={() => setShowExcelImport(true)}>
                            <FileSpreadsheet className="h-4 w-4 mr-2" />
                            Import Excel
                        </Button>
                        <Button variant="outline" onClick={() => refetchEmployees()}>
                            <RefreshCw className="h-4 w-4 mr-2" />
                            Refresh Data
                        </Button>
                        <Button variant="destructive" onClick={handleClearAll}>
                            <Trash2 className="h-4 w-4 mr-2" />
                            Hapus Semua
                        </Button>
                    </div>
                </CardContent>
            </Card>

            {/* Registered Teachers */}
            {registeredTeachers.length > 0 && (
                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="text-sm font-medium flex items-center gap-2">
                            <Users className="h-4 w-4" />
                            Guru Terdaftar ({registeredTeachers.length})
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="flex flex-wrap gap-2">
                            {registeredTeachers.map((teacher) => {
                                const stat = teacherStats[teacher.id];
                                const count = stat?.total || 0;
                                return (
                                    <Badge
                                        key={teacher.id}
                                        variant={activeTeacher?.id === teacher.id ? "default" : "outline"}
                                        className={cn(
                                            "cursor-pointer hover:opacity-80 transition-opacity",
                                            count >= 2 && "border-destructive"
                                        )}
                                        style={{
                                            backgroundColor: activeTeacher?.id === teacher.id ? teacher.color : undefined,
                                            borderColor: count >= 2 ? 'hsl(var(--destructive))' : undefined
                                        }}
                                        onClick={() => setActiveTeacher(teacher)}
                                    >
                                        <span className="font-mono mr-1">{teacher.code}</span>
                                        <span className="truncate max-w-[100px]">{teacher.name.split(' ')[0]}</span>
                                        <span className="ml-2 text-xs opacity-75">{count}/2</span>
                                        {count >= 2 && <AlertTriangle className="h-3 w-3 ml-1" />}
                                    </Badge>
                                );
                            })}
                        </div>
                    </CardContent>
                </Card>
            )}

            {/* Schedule Grid */}
            <Card>
                <CardContent className="p-4 overflow-x-auto">
                    <table className="w-full min-w-[1200px] border-collapse">
                        <thead>
                            <tr>
                                <th className="border border-border bg-muted p-2 text-left font-semibold sticky left-0 z-10" rowSpan={2}>
                                    Hari
                                </th>
                                {displayedClasses.map((classKey: string) => (
                                    <th
                                        key={classKey}
                                        className="border border-border bg-muted p-2 text-center font-semibold"
                                        colSpan={PERIODS.length}
                                    >
                                        {classKey}
                                    </th>
                                ))}
                            </tr>
                            <tr>
                                {displayedClasses.map((classKey: string) =>
                                    PERIODS.map(period => (
                                        <th
                                            key={`${classKey}-${period}`}
                                            className="border border-border bg-muted/50 p-1 text-center text-xs font-medium w-10"
                                        >
                                            {period}
                                        </th>
                                    ))
                                )}
                            </tr>
                        </thead>
                        <tbody>
                            {DAYS.map(day => (
                                <tr key={day}>
                                    <td className="border border-border bg-muted p-2 font-medium sticky left-0 z-10">
                                        {day}
                                    </td>
                                    {displayedClasses.map((classKey: string) =>
                                        PERIODS.map(period => {
                                            const cell = schedule[day]?.[classKey]?.[period];
                                            const isSwapSource = swapMode.source?.day === day &&
                                                swapMode.source?.classKey === classKey &&
                                                swapMode.source?.period === period;

                                            return (
                                                <td
                                                    key={`${day}-${classKey}-${period}`}
                                                    className={cn(
                                                        "border border-border p-0 text-center cursor-pointer transition-all",
                                                        "hover:bg-accent/50",
                                                        cell?.isLocked && "bg-muted/80",
                                                        isSwapSource && "ring-2 ring-primary ring-inset animate-pulse"
                                                    )}
                                                    style={{
                                                        backgroundColor: cell?.color && !cell.isLocked ? cell.color : undefined
                                                    }}
                                                    onClick={() => handleCellClick(day, classKey, period)}
                                                    onContextMenu={(e) => handleContextMenu(e, day, classKey, period)}
                                                    title={cell ? `${cell.teacherName} (${cell.teacherCode})` : 'Klik untuk menambah'}
                                                >
                                                    <div className="relative w-10 h-8 flex items-center justify-center text-xs font-medium">
                                                        {cell?.teacherCode || ''}
                                                        {cell?.isLocked && (
                                                            <Lock className="absolute top-0 right-0 h-3 w-3 text-muted-foreground" />
                                                        )}
                                                    </div>
                                                </td>
                                            );
                                        })
                                    )}
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </CardContent>
            </Card>

            {/* Legend */}
            <Card>
                <CardContent className="p-4">
                    <div className="flex flex-wrap items-center gap-6 text-sm">
                        <div className="flex items-center gap-2">
                            <div className="w-6 h-6 border rounded bg-blue-200" />
                            <span>Sel Terisi (Warna berdasarkan guru)</span>
                        </div>
                        <div className="flex items-center gap-2">
                            <div className="w-6 h-6 border rounded bg-muted/80 flex items-center justify-center">
                                <Lock className="h-3 w-3" />
                            </div>
                            <span>Sel Terkunci</span>
                        </div>
                        <div className="flex items-center gap-2">
                            <div className="w-6 h-6 border rounded ring-2 ring-primary" />
                            <span>Sel Sumber Tukar</span>
                        </div>
                    </div>
                </CardContent>
            </Card>

            {/* Context Menu */}
            {contextMenu && (
                <div
                    ref={contextMenuRef}
                    className="fixed bg-popover border rounded-md shadow-lg py-1 z-50 min-w-[180px]"
                    style={{
                        left: contextMenu.x,
                        top: contextMenu.y,
                        transform: 'translate(-50%, 0)'
                    }}
                >
                    {getContextMenuItems().map((item, index) => (
                        <button
                            key={index}
                            className={cn(
                                "w-full px-3 py-2 text-left text-sm flex items-center gap-2 hover:bg-accent",
                                item.disabled && "opacity-50 cursor-not-allowed"
                            )}
                            onClick={item.onClick}
                            disabled={item.disabled}
                        >
                            {item.icon}
                            {item.label}
                        </button>
                    ))}
                </div>
            )}

            {/* Add Teacher Dialog */}
            <Dialog open={showAddTeacherDialog} onOpenChange={setShowAddTeacherDialog}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Tambah Guru ke Jadwal</DialogTitle>
                        <DialogDescription>
                            Pilih guru dari daftar karyawan dan berikan kode singkat (opsional).
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-4 py-4">
                        <div className="space-y-2">
                            <Label>Pilih Guru</Label>
                            <Select
                                value={newTeacherData.employeeId}
                                onValueChange={(value) => setNewTeacherData(prev => ({ ...prev, employeeId: value }))}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Pilih guru..." />
                                </SelectTrigger>
                                <SelectContent>
                                    {employeeOptions
                                        .filter(emp => !teacherCodes.has(emp.id))
                                        .map(emp => (
                                            <SelectItem key={emp.id} value={emp.id}>
                                                {emp.name} - {emp.position || emp.department}
                                            </SelectItem>
                                        ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="space-y-2">
                            <Label>Kode Guru (Opsional)</Label>
                            <Input
                                placeholder="Contoh: AB, G1, DSN"
                                value={newTeacherData.customCode}
                                onChange={(e) => setNewTeacherData(prev => ({
                                    ...prev,
                                    customCode: e.target.value.toUpperCase().slice(0, 4)
                                }))}
                                maxLength={4}
                            />
                            <p className="text-xs text-muted-foreground">
                                Jika dikosongkan, kode akan dibuat otomatis dari nama guru.
                            </p>
                        </div>
                    </div>

                    <DialogFooter>
                        <Button variant="outline" onClick={() => setShowAddTeacherDialog(false)}>
                            Batal
                        </Button>
                        <Button onClick={handleAddTeacher} disabled={!newTeacherData.employeeId}>
                            <CheckCircle2 className="h-4 w-4 mr-2" />
                            Tambah Guru
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Excel Import Dialog */}
            <Dialog open={showExcelImport} onOpenChange={setShowExcelImport}>
                <DialogContent className="max-w-5xl max-h-[90vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>Import Jadwal Mengajar dari Excel</DialogTitle>
                        <DialogDescription>
                            Import jadwal dari file Excel. Data akan tersimpan di database dan terintegrasi dengan sistem absensi.
                        </DialogDescription>
                    </DialogHeader>

                    <ExcelScheduleImporterIntegrated
                        onImportComplete={(result) => {
                            // Close dialog and refresh data
                            setShowExcelImport(false);
                            // Apply to local grid as well for preview
                            handleExcelImport({
                                teachers: result.matched_teachers.map(t => ({
                                    code: t.code,
                                    name: t.employee_name,
                                    subject: ''
                                })),
                                schedules: [],
                                classes: [],
                                days: [],
                                errors: []
                            });
                        }}
                    />
                </DialogContent>
            </Dialog>

            {/* Instructions */}
            <Card>
                <CardHeader className="pb-2">
                    <CardTitle className="text-sm font-medium">Petunjuk Penggunaan</CardTitle>
                </CardHeader>
                <CardContent className="text-sm text-muted-foreground space-y-2">
                    <p>1. Klik "Tambah Guru" untuk mendaftarkan guru dari database sistem dengan kode singkat</p>
                    <p>2. Atau gunakan "Import Excel" untuk import jadwal dari file Excel</p>
                    <p>3. Pilih guru dari dropdown, lalu klik sel kosong untuk menempatkan</p>
                    <p>4. Klik sel yang sudah terisi dengan guru yang sama untuk menghapus</p>
                    <p>5. Klik kanan pada sel untuk membuka menu konteks (Kunci/Tukar)</p>
                    <p>6. Validasi: Maksimal 1x per hari, Maksimal 2x per minggu untuk setiap guru</p>
                    <p>7. Gunakan "Simpan (JSON)" untuk menyimpan progres yang bisa dilanjutkan nanti</p>
                </CardContent>
            </Card>
        </div>
    );
}
