/**
 * Teacher Schedule Grid Content
 * 
 * Grid-based teacher scheduling system with:
 * - Select ONE class at a time
 * - Rows: Days (Senin-Sabtu)
 * - Columns: Periods (1-10)
 * - Validation: Max 1 per day, Max 2 per week
 * - Features: Toggle input, Lock/Unlock, Swap, Save/Load JSON
 */

import React, { useState, useCallback, useRef, useEffect, useMemo } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
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
    ContextMenu,
    ContextMenuContent,
    ContextMenuItem,
    ContextMenuTrigger,
    ContextMenuSeparator,
} from '@/components/ui/context-menu';
import {
    Upload,
    Download,
    Lock,
    Unlock,
    ArrowRightLeft,
    Trash2,
    GripVertical,
    Users,
    Plus,
    Check,
    ChevronsUpDown,
    RefreshCw,
    Calendar,
    BookOpen,
    Filter,
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { useNotificationStore } from '@/stores/notification-store';
import { useEmployees } from '@/hooks/use-employees';
import { useAcademicClasses } from '@/hooks/use-schedules';
import type { Employee } from '@/types/employee';
import type { AcademicClass } from '@/types/schedule';

// ============ TYPES ============
interface ScheduleCell {
    code: string | null;
    isLocked: boolean;
    teacherName?: string;
    color?: string;
}

interface TeacherInfo {
    id: string;
    code: string;
    name: string;
    subject: string;
    color: string;
}

interface SwapSource {
    day: string;
    period: number;
}

type ScheduleGrid = {
    [classKey: string]: {
        [day: string]: {
            [period: number]: ScheduleCell;
        };
    };
};

// ============ CONSTANTS ============
const DAYS = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
const PERIODS = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];

const generateColor = (code: string): string => {
    let hash = 0;
    for (let i = 0; i < code.length; i++) {
        hash = code.charCodeAt(i) + ((hash << 5) - hash);
    }
    const h = Math.abs(hash % 360);
    return `hsl(${h}, 70%, 80%)`;
};

// ============ COMPONENT ============
export function TeacherScheduleGridContent() {
    const { success, error: showError, warning } = useNotificationStore();

    // Fetch data
    const { data: employeesData, isLoading: loadingEmployees } = useEmployees({
        status: 'active',
        per_page: 200,
    });
    const { data: classesData, isLoading: loadingClasses } = useAcademicClasses();

    // ============ STATE ============
    const [teachers, setTeachers] = useState<Map<string, TeacherInfo>>(new Map());
    const [grid, setGrid] = useState<ScheduleGrid>({});
    const [activeTeacher, setActiveTeacher] = useState<TeacherInfo | null>(null);
    const [selectedClass, setSelectedClass] = useState<string>('');
    const [swapSource, setSwapSource] = useState<SwapSource | null>(null);
    const [teacherSelectOpen, setTeacherSelectOpen] = useState(false);
    const [showAddTeacherDialog, setShowAddTeacherDialog] = useState(false);
    const [newTeacherCode, setNewTeacherCode] = useState('');
    const [selectedEmployeeId, setSelectedEmployeeId] = useState('');

    const fileInputRef = useRef<HTMLInputElement>(null);

    // Get class list
    const classList = useMemo(() => {
        if (classesData && classesData.length > 0) {
            return classesData.map((c: AcademicClass) => c.name);
        }
        return ['VII-A', 'VII-B', 'VII-C', 'VII-D', 'VIII-A', 'VIII-B', 'VIII-C', 'VIII-D', 'IX-A', 'IX-B', 'IX-C', 'IX-D', 'IX-E'];
    }, [classesData]);

    // Filter employees to only show teachers
    const teacherEmployees = useMemo(() => {
        if (!employeesData?.data) return [];
        return employeesData.data.filter((emp: Employee) => {
            const roles = (emp as any).roles as string[] | undefined;
            const role = (emp as any).role as string | undefined;
            if (roles && Array.isArray(roles)) {
                return roles.some(r => r.toLowerCase().includes('guru'));
            }
            if (role) {
                return role.toLowerCase().includes('guru');
            }
            return emp.position?.toLowerCase().includes('guru');
        });
    }, [employeesData]);

    // Set first class as default
    useEffect(() => {
        if (classList.length > 0 && !selectedClass) {
            setSelectedClass(classList[0]);
        }
    }, [classList, selectedClass]);

    // Initialize grid
    useEffect(() => {
        if (classList.length > 0 && Object.keys(grid).length === 0) {
            const newGrid: ScheduleGrid = {};
            classList.forEach((classKey: string) => {
                newGrid[classKey] = {};
                DAYS.forEach(day => {
                    newGrid[classKey][day] = {};
                    PERIODS.forEach(period => {
                        newGrid[classKey][day][period] = { code: null, isLocked: false };
                    });
                });
            });
            setGrid(newGrid);
        }
    }, [classList, grid]);

    // ============ VALIDATION ============
    const getWeeklyCount = useCallback((teacherCode: string): number => {
        let count = 0;
        Object.values(grid).forEach(classData => {
            DAYS.forEach(day => {
                PERIODS.forEach(period => {
                    if (classData[day]?.[period]?.code === teacherCode) {
                        count++;
                    }
                });
            });
        });
        return count;
    }, [grid]);

    const getDailyCount = useCallback((teacherCode: string, day: string): number => {
        let count = 0;
        Object.values(grid).forEach(classData => {
            PERIODS.forEach(period => {
                if (classData[day]?.[period]?.code === teacherCode) {
                    count++;
                }
            });
        });
        return count;
    }, [grid]);

    const validatePlacement = useCallback((teacherCode: string, day: string): { valid: boolean; message?: string } => {
        const dailyCount = getDailyCount(teacherCode, day);
        if (dailyCount >= 1) {
            return {
                valid: false,
                message: `Guru "${teacherCode}" sudah mengajar di hari ${day}. Maksimal 1 pertemuan per hari.`
            };
        }

        const weeklyCount = getWeeklyCount(teacherCode);
        if (weeklyCount >= 2) {
            return {
                valid: false,
                message: `Guru "${teacherCode}" sudah mengajar 2 kali minggu ini. Maksimal 2 pertemuan per minggu.`
            };
        }

        return { valid: true };
    }, [getDailyCount, getWeeklyCount]);

    // ============ CELL CLICK ============
    const handleCellClick = useCallback((day: string, period: number) => {
        if (!selectedClass) return;

        const cell = grid[selectedClass]?.[day]?.[period];

        if (cell?.isLocked) {
            warning('Sel Terkunci', 'Sel ini terkunci. Klik kanan untuk membuka kunci.');
            return;
        }

        // Handle swap
        if (swapSource) {
            const sourceCell = grid[selectedClass]?.[swapSource.day]?.[swapSource.period];

            setGrid(prev => {
                const newGrid = { ...prev };
                newGrid[selectedClass] = { ...newGrid[selectedClass] };
                newGrid[selectedClass][swapSource.day] = { ...newGrid[selectedClass][swapSource.day] };
                newGrid[selectedClass][day] = { ...newGrid[selectedClass][day] };

                newGrid[selectedClass][swapSource.day][swapSource.period] = { ...cell! };
                newGrid[selectedClass][day][period] = { ...sourceCell! };

                return newGrid;
            });

            setSwapSource(null);
            success('Berhasil', 'Sel berhasil ditukar');
            return;
        }

        if (!activeTeacher) {
            warning('Pilih Guru', 'Silakan pilih guru terlebih dahulu');
            return;
        }

        if (cell?.code === null) {
            const validation = validatePlacement(activeTeacher.code, day);
            if (!validation.valid) {
                showError('Validasi Gagal', validation.message || '');
                return;
            }

            setGrid(prev => {
                const newGrid = { ...prev };
                newGrid[selectedClass] = { ...newGrid[selectedClass] };
                newGrid[selectedClass][day] = { ...newGrid[selectedClass][day] };
                newGrid[selectedClass][day][period] = {
                    code: activeTeacher.code,
                    isLocked: false,
                    teacherName: activeTeacher.name,
                    color: activeTeacher.color,
                };
                return newGrid;
            });
        } else if (cell?.code === activeTeacher.code) {
            setGrid(prev => {
                const newGrid = { ...prev };
                newGrid[selectedClass] = { ...newGrid[selectedClass] };
                newGrid[selectedClass][day] = { ...newGrid[selectedClass][day] };
                newGrid[selectedClass][day][period] = { code: null, isLocked: false };
                return newGrid;
            });
        } else {
            warning('Sel Terisi', `Sel ini sudah terisi oleh guru "${cell?.code}".`);
        }
    }, [grid, selectedClass, swapSource, activeTeacher, validatePlacement, success, showError, warning]);

    // ============ CONTEXT MENU ============
    const handleToggleLock = useCallback((day: string, period: number) => {
        if (!selectedClass) return;
        setGrid(prev => {
            const newGrid = { ...prev };
            newGrid[selectedClass] = { ...newGrid[selectedClass] };
            newGrid[selectedClass][day] = { ...newGrid[selectedClass][day] };
            const cell = newGrid[selectedClass][day][period];
            newGrid[selectedClass][day][period] = { ...cell, isLocked: !cell.isLocked };
            return newGrid;
        });
    }, [selectedClass]);

    const handleStartSwap = useCallback((day: string, period: number) => {
        setSwapSource({ day, period });
        success('Mode Tukar', 'Klik sel lain untuk menukar');
    }, [success]);

    const handleClearCell = useCallback((day: string, period: number) => {
        if (!selectedClass) return;
        const cell = grid[selectedClass]?.[day]?.[period];
        if (cell?.isLocked) {
            warning('Sel Terkunci', 'Buka kunci terlebih dahulu');
            return;
        }
        setGrid(prev => {
            const newGrid = { ...prev };
            newGrid[selectedClass] = { ...newGrid[selectedClass] };
            newGrid[selectedClass][day] = { ...newGrid[selectedClass][day] };
            newGrid[selectedClass][day][period] = { code: null, isLocked: false };
            return newGrid;
        });
    }, [grid, selectedClass, warning]);

    // ============ TEACHER MANAGEMENT ============
    const handleAddTeacher = useCallback(() => {
        if (!newTeacherCode.trim()) {
            showError('Error', 'Masukkan kode guru');
            return;
        }

        const code = newTeacherCode.trim().toUpperCase();
        if (teachers.has(code)) {
            showError('Error', `Kode "${code}" sudah digunakan`);
            return;
        }

        let name = code;
        let subject = '';

        if (selectedEmployeeId) {
            const emp = teacherEmployees.find((e: Employee) => e.id === selectedEmployeeId);
            if (emp) {
                name = emp.name;
                subject = emp.position || '';
            }
        }

        const newTeacher: TeacherInfo = {
            id: selectedEmployeeId || code,
            code,
            name,
            subject,
            color: generateColor(code),
        };

        setTeachers(prev => new Map(prev).set(code, newTeacher));
        setActiveTeacher(newTeacher);
        setShowAddTeacherDialog(false);
        setNewTeacherCode('');
        setSelectedEmployeeId('');
        success('Berhasil', `Guru "${name}" ditambahkan`);
    }, [newTeacherCode, selectedEmployeeId, teachers, teacherEmployees, success, showError]);

    // ============ SAVE/LOAD ============
    const handleSaveJson = useCallback(() => {
        const data = {
            grid,
            teachers: Array.from(teachers.entries()),
            exportedAt: new Date().toISOString(),
        };
        const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `jadwal-${new Date().toISOString().split('T')[0]}.json`;
        link.click();
        URL.revokeObjectURL(url);
        success('Berhasil', 'Jadwal disimpan');
    }, [grid, teachers, success]);

    const handleLoadJson = useCallback((e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = (evt) => {
            try {
                const data = JSON.parse(evt.target?.result as string);
                if (data.grid) setGrid(data.grid);
                if (data.teachers) setTeachers(new Map(data.teachers));
                success('Berhasil', 'Jadwal dimuat');
            } catch {
                showError('Error', 'Gagal memuat file');
            }
        };
        reader.readAsText(file);
        if (fileInputRef.current) fileInputRef.current.value = '';
    }, [success, showError]);

    const handleClearAll = useCallback(() => {
        if (!confirm('Hapus semua jadwal?')) return;
        const newGrid: ScheduleGrid = {};
        classList.forEach((classKey: string) => {
            newGrid[classKey] = {};
            DAYS.forEach(day => {
                newGrid[classKey][day] = {};
                PERIODS.forEach(period => {
                    newGrid[classKey][day][period] = { code: null, isLocked: false };
                });
            });
        });
        setGrid(newGrid);
        success('Berhasil', 'Jadwal dihapus');
    }, [classList, success]);

    // ============ STATS ============
    const stats = useMemo(() => {
        let filled = 0, locked = 0;
        const teacherSet = new Set<string>();

        if (selectedClass && grid[selectedClass]) {
            DAYS.forEach(day => {
                PERIODS.forEach(period => {
                    const cell = grid[selectedClass]?.[day]?.[period];
                    if (cell?.code) {
                        filled++;
                        teacherSet.add(cell.code);
                    }
                    if (cell?.isLocked) locked++;
                });
            });
        }

        return { filled, locked, teachers: teacherSet.size, totalSlots: DAYS.length * PERIODS.length };
    }, [grid, selectedClass]);

    const registeredTeachers = Array.from(teachers.values());

    if (loadingEmployees || loadingClasses) {
        return (
            <Card>
                <CardContent className="flex items-center justify-center py-12">
                    <RefreshCw className="h-8 w-8 animate-spin text-primary" />
                    <span className="ml-2">Memuat...</span>
                </CardContent>
            </Card>
        );
    }

    return (
        <div className="space-y-4">
            {/* Header */}
            <Card>
                <CardHeader className="pb-3">
                    <CardTitle className="flex items-center gap-2">
                        <GripVertical className="h-5 w-5 text-primary" />
                        Penyusunan Jadwal Mengajar
                    </CardTitle>
                    <CardDescription>
                        Pilih kelas, pilih guru, lalu klik sel untuk mengisi. Klik kanan untuk opsi.
                        <span className="block text-warning font-medium mt-1">
                            Aturan: Max 1×/hari, Max 2×/minggu per guru.
                        </span>
                    </CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                    {/* Class & Teacher Selection */}
                    <div className="flex flex-wrap items-center gap-4">
                        {/* Class Selector */}
                        <div className="flex items-center gap-2">
                            <Filter className="h-4 w-4 text-muted-foreground" />
                            <Select value={selectedClass} onValueChange={setSelectedClass}>
                                <SelectTrigger className="w-[160px]">
                                    <SelectValue placeholder="Pilih Kelas" />
                                </SelectTrigger>
                                <SelectContent>
                                    {classList.map((cls: string) => (
                                        <SelectItem key={cls} value={cls}>
                                            <span className="flex items-center gap-2">
                                                <BookOpen className="h-3 w-3" />
                                                {cls}
                                            </span>
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        {/* Teacher Selector */}
                        <Popover open={teacherSelectOpen} onOpenChange={setTeacherSelectOpen}>
                            <PopoverTrigger asChild>
                                <Button variant="outline" className="w-[220px] justify-between">
                                    {activeTeacher ? (
                                        <span className="flex items-center gap-2">
                                            <div className="w-3 h-3 rounded" style={{ backgroundColor: activeTeacher.color }} />
                                            <span className="font-mono font-bold">{activeTeacher.code}</span>
                                            <span className="truncate">{activeTeacher.name}</span>
                                        </span>
                                    ) : "Pilih Guru..."}
                                    <ChevronsUpDown className="h-4 w-4 opacity-50" />
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent className="w-[220px] p-0">
                                <Command>
                                    <CommandInput placeholder="Cari..." />
                                    <CommandList>
                                        <CommandEmpty>Tidak ada guru.</CommandEmpty>
                                        <CommandGroup>
                                            {registeredTeachers.map(t => (
                                                <CommandItem
                                                    key={t.code}
                                                    value={t.name}
                                                    onSelect={() => {
                                                        setActiveTeacher(t);
                                                        setTeacherSelectOpen(false);
                                                    }}
                                                >
                                                    <Check className={cn("mr-2 h-4 w-4", activeTeacher?.code === t.code ? "opacity-100" : "opacity-0")} />
                                                    <div className="w-3 h-3 rounded mr-2" style={{ backgroundColor: t.color }} />
                                                    <span className="font-mono font-bold mr-1">{t.code}</span>
                                                    <span className="truncate">{t.name}</span>
                                                </CommandItem>
                                            ))}
                                        </CommandGroup>
                                    </CommandList>
                                </Command>
                            </PopoverContent>
                        </Popover>

                        <Button variant="outline" size="sm" onClick={() => setShowAddTeacherDialog(true)}>
                            <Plus className="h-4 w-4 mr-1" />
                            Tambah Guru
                        </Button>

                        {activeTeacher && (
                            <Badge style={{ backgroundColor: activeTeacher.color }} className="text-foreground">
                                Aktif: <strong className="font-mono ml-1">{activeTeacher.code}</strong>
                            </Badge>
                        )}

                        {swapSource && (
                            <Badge variant="destructive" className="animate-pulse">
                                <ArrowRightLeft className="h-3 w-3 mr-1" />
                                Mode Tukar
                                <button className="ml-2 underline" onClick={() => setSwapSource(null)}>Batal</button>
                            </Badge>
                        )}
                    </div>

                    {/* Actions */}
                    <div className="flex flex-wrap gap-2">
                        <input type="file" ref={fileInputRef} onChange={handleLoadJson} accept=".json" className="hidden" />
                        <Button variant="outline" size="sm" onClick={() => fileInputRef.current?.click()}>
                            <Upload className="h-4 w-4 mr-1" />
                            Muat
                        </Button>
                        <Button variant="outline" size="sm" onClick={handleSaveJson}>
                            <Download className="h-4 w-4 mr-1" />
                            Simpan
                        </Button>
                        <Button variant="outline" size="sm" onClick={handleClearAll}>
                            <Trash2 className="h-4 w-4 mr-1" />
                            Hapus Semua
                        </Button>
                    </div>
                </CardContent>
            </Card>

            {/* Stats */}
            <div className="grid grid-cols-4 gap-3">
                <Card>
                    <CardContent className="p-3 flex items-center gap-2">
                        <Calendar className="h-6 w-6 text-primary/40" />
                        <div>
                            <p className="text-xl font-bold">{stats.filled}</p>
                            <p className="text-[10px] text-muted-foreground">Terisi</p>
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent className="p-3 flex items-center gap-2">
                        <Users className="h-6 w-6 text-success/40" />
                        <div>
                            <p className="text-xl font-bold">{stats.teachers}</p>
                            <p className="text-[10px] text-muted-foreground">Guru</p>
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent className="p-3 flex items-center gap-2">
                        <Lock className="h-6 w-6 text-warning/40" />
                        <div>
                            <p className="text-xl font-bold">{stats.locked}</p>
                            <p className="text-[10px] text-muted-foreground">Terkunci</p>
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent className="p-3 flex items-center gap-2">
                        <GripVertical className="h-6 w-6 text-muted-foreground/40" />
                        <div>
                            <p className="text-xl font-bold">{stats.totalSlots}</p>
                            <p className="text-[10px] text-muted-foreground">Total</p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            {/* Grid */}
            <Card>
                <CardHeader className="pb-2">
                    <CardTitle className="text-base flex items-center gap-2">
                        <BookOpen className="h-4 w-4 text-primary" />
                        Jadwal Kelas {selectedClass}
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="overflow-x-auto">
                        <table className="w-full border-collapse">
                            <thead>
                                <tr>
                                    <th className="border bg-muted/50 p-2 text-sm font-medium w-20">Hari</th>
                                    {PERIODS.map(p => (
                                        <th key={p} className="border bg-muted/50 p-2 text-sm font-medium w-12 text-center">
                                            {p}
                                        </th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody>
                                {DAYS.map(day => (
                                    <tr key={day}>
                                        <td className="border bg-muted/30 p-2 text-sm font-medium">{day}</td>
                                        {PERIODS.map(period => {
                                            const cell = grid[selectedClass]?.[day]?.[period];
                                            const isSwapSrc = swapSource?.day === day && swapSource?.period === period;

                                            return (
                                                <ContextMenu key={period}>
                                                    <ContextMenuTrigger asChild>
                                                        <td
                                                            className={cn(
                                                                "border p-1 cursor-pointer transition-colors hover:bg-muted/30 text-center h-12",
                                                                cell?.isLocked && "bg-muted/50",
                                                                isSwapSrc && "ring-2 ring-primary"
                                                            )}
                                                            style={{ backgroundColor: cell?.color || undefined }}
                                                            onClick={() => handleCellClick(day, period)}
                                                        >
                                                            <div className="relative flex items-center justify-center h-full">
                                                                {cell?.code ? (
                                                                    <span className="font-mono font-bold text-sm">{cell.code}</span>
                                                                ) : (
                                                                    <span className="text-muted-foreground/20">-</span>
                                                                )}
                                                                {cell?.isLocked && (
                                                                    <Lock className="h-2.5 w-2.5 absolute top-0.5 right-0.5 text-muted-foreground" />
                                                                )}
                                                            </div>
                                                        </td>
                                                    </ContextMenuTrigger>
                                                    <ContextMenuContent>
                                                        <ContextMenuItem onClick={() => handleToggleLock(day, period)}>
                                                            {cell?.isLocked ? <><Unlock className="h-4 w-4 mr-2" />Buka Kunci</> : <><Lock className="h-4 w-4 mr-2" />Kunci</>}
                                                        </ContextMenuItem>
                                                        <ContextMenuSeparator />
                                                        {!swapSource ? (
                                                            <ContextMenuItem onClick={() => handleStartSwap(day, period)} disabled={!cell?.code}>
                                                                <ArrowRightLeft className="h-4 w-4 mr-2" />Mulai Tukar
                                                            </ContextMenuItem>
                                                        ) : (
                                                            <ContextMenuItem onClick={() => handleCellClick(day, period)}>
                                                                <ArrowRightLeft className="h-4 w-4 mr-2" />Tukar di Sini
                                                            </ContextMenuItem>
                                                        )}
                                                        <ContextMenuSeparator />
                                                        <ContextMenuItem onClick={() => handleClearCell(day, period)} disabled={!cell?.code || cell?.isLocked} className="text-destructive">
                                                            <Trash2 className="h-4 w-4 mr-2" />Hapus
                                                        </ContextMenuItem>
                                                    </ContextMenuContent>
                                                </ContextMenu>
                                            );
                                        })}
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>

            {/* Legend */}
            <Card>
                <CardHeader className="pb-2">
                    <CardTitle className="text-sm">Daftar Guru ({registeredTeachers.length})</CardTitle>
                </CardHeader>
                <CardContent>
                    {registeredTeachers.length === 0 ? (
                        <p className="text-sm text-muted-foreground">Belum ada guru. Klik "Tambah Guru".</p>
                    ) : (
                        <div className="flex flex-wrap gap-2">
                            {registeredTeachers.map(t => (
                                <Badge
                                    key={t.code}
                                    variant="outline"
                                    className="cursor-pointer text-foreground text-xs"
                                    style={{ backgroundColor: t.color }}
                                    onClick={() => setActiveTeacher(t)}
                                >
                                    <span className="font-mono font-bold mr-1">{t.code}</span>
                                    {t.name}
                                    <span className="ml-1 opacity-70">({getWeeklyCount(t.code)}/2)</span>
                                </Badge>
                            ))}
                        </div>
                    )}
                </CardContent>
            </Card>

            {/* Add Teacher Dialog */}
            <Dialog open={showAddTeacherDialog} onOpenChange={setShowAddTeacherDialog}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Tambah Guru</DialogTitle>
                        <DialogDescription>Masukkan kode guru dan pilih data dari sistem.</DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4 py-4">
                        <div className="space-y-2">
                            <Label>Kode Guru</Label>
                            <Input
                                value={newTeacherCode}
                                onChange={e => setNewTeacherCode(e.target.value.toUpperCase())}
                                placeholder="Contoh: 1, 2, AB"
                                className="font-mono"
                            />
                        </div>
                        <div className="space-y-2">
                            <Label>Guru dari Sistem (Opsional)</Label>
                            <Select value={selectedEmployeeId} onValueChange={setSelectedEmployeeId}>
                                <SelectTrigger>
                                    <SelectValue placeholder="Pilih guru..." />
                                </SelectTrigger>
                                <SelectContent>
                                    {teacherEmployees.map((emp: Employee) => (
                                        <SelectItem key={emp.id} value={emp.id}>
                                            {emp.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setShowAddTeacherDialog(false)}>Batal</Button>
                        <Button onClick={handleAddTeacher}>
                            <Plus className="h-4 w-4 mr-1" />
                            Tambah
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    );
}
