/**
 * Grade Schedule Builder - Main Component
 *
 * Halaman utama untuk penyusunan jadwal per grade dengan fitur:
 * - Tab untuk Grade 7, 8, 9
 * - Auto-save dengan debounce 2 detik
 * - Import Excel
 * - Save/Load ke Server (TeachingSchedule)
 * - Export/Import JSON
 * - Grid editing dengan toggle/swap/lock
 * - Keyboard shortcuts (Ctrl+S, Escape, Delete, 1-9)
 */

import { useState, useCallback, useRef, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Alert, AlertDescription } from '@/components/ui/alert';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
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
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from '@/components/ui/tooltip';
import {
  Upload,
  Download,
  Trash2,
  GripVertical,
  ArrowRightLeft,
  FileSpreadsheet,
  Save,
  CloudDownload,
  Loader2,
  CheckCircle2,
  Keyboard,
} from 'lucide-react';
import { useGradeScheduleStore } from '@/stores/grade-schedule-store';
import { useNotificationStore } from '@/stores/notification-store';
import { useAutoSave } from '@/hooks/use-auto-save';
import { GradeScheduleGrid } from './GradeScheduleGrid';
import { TeacherSidebar } from './TeacherSidebar';
import { ExcelImportDialog } from './ExcelImportDialog';
import { StatusBar } from './StatusBar';
import { GRADES, TIME_SLOTS, DAY_MAPPING, parseRowKey } from './constants';
import { saveGradeSchedule, loadGradeSchedule } from '@/lib/api/schedules';

export default function GradeScheduleBuilder() {
  const { success, error: showError, warning } = useNotificationStore();

  const activeGrade = useGradeScheduleStore((s) => s.activeGrade);
  const setActiveGrade = useGradeScheduleStore((s) => s.setActiveGrade);
  const grids = useGradeScheduleStore((s) => s.grids);
  const teachers = useGradeScheduleStore((s) => s.teachers);
  const swapSource = useGradeScheduleStore((s) => s.swapSource);
  const cancelSwap = useGradeScheduleStore((s) => s.cancelSwap);
  const hasUnsavedChanges = useGradeScheduleStore((s) => s.hasUnsavedChanges);
  const metadata = useGradeScheduleStore((s) => s.metadata);
  const setMetadata = useGradeScheduleStore((s) => s.setMetadata);
  const markSaved = useGradeScheduleStore((s) => s.markSaved);
  const resetGrade = useGradeScheduleStore((s) => s.resetGrade);
  const resetAll = useGradeScheduleStore((s) => s.resetAll);
  const setCell = useGradeScheduleStore((s) => s.setCell);
  const addTeacher = useGradeScheduleStore((s) => s.addTeacher);
  const setActiveTeacher = useGradeScheduleStore((s) => s.setActiveTeacher);
  const activeTeacher = useGradeScheduleStore((s) => s.activeTeacher);
  const selectedCell = useGradeScheduleStore((s) => s.selectedCell);
  const clearCell = useGradeScheduleStore((s) => s.clearCell);
  const getDirtyGrades = useGradeScheduleStore((s) => s.getDirtyGrades);

  const [showImportDialog, setShowImportDialog] = useState(false);
  const [showResetDialog, setShowResetDialog] = useState(false);
  const [showSaveDialog, setShowSaveDialog] = useState(false);
  const [resetTarget, setResetTarget] = useState<'grade' | 'all'>('grade');
  const [isLoading, setIsLoading] = useState(false);
  const [isInitialLoad, setIsInitialLoad] = useState(true);

  const fileInputRef = useRef<HTMLInputElement>(null);
  const hasLoadedRef = useRef(false);

  // Get time slot info from period
  const getTimeSlotInfo = useCallback((period: number) => {
    const slot = TIME_SLOTS.find((s) => s.period === period && !s.isBreak);
    return slot || { start: '07:00', end: '07:45' };
  }, []);

  // Auto-save callback
  const performAutoSave = useCallback(async (): Promise<void> => {
    // Only save dirty grades
    const dirtyGrades = getDirtyGrades();
    if (dirtyGrades.length === 0) return;

    // Ensure metadata has default values
    const effectiveFrom = metadata.effectiveFrom || new Date().toISOString().split('T')[0];

    for (const grade of dirtyGrades) {
      const gradeGrid = grids[grade] || {};
      const schedules: Array<{
        class_name: string;
        day: string;
        period: number;
        time_start: string;
        time_end: string;
        teacher_id?: string;
        teacher_code?: string;
        subject?: string;
        is_locked?: boolean;
      }> = [];

      // Convert grid to schedule entries
      for (const className in gradeGrid) {
        for (const rowKey in gradeGrid[className]) {
          const cell = gradeGrid[className][rowKey];
          const { day, period } = parseRowKey(rowKey);
          const dayEn = DAY_MAPPING[day] || 'monday';
          const timeSlot = getTimeSlotInfo(period);

          // Get teacher info
          const teacher = cell?.teacherCode ? teachers.get(cell.teacherCode) : null;

          schedules.push({
            class_name: className,
            day: dayEn,
            period,
            time_start: timeSlot.start,
            time_end: timeSlot.end,
            teacher_id: teacher?.employeeId,
            teacher_code: cell?.teacherCode || undefined,
            subject: cell?.subject || teacher?.subject || undefined,
            is_locked: cell?.isLocked,
          });
        }
      }

      if (schedules.length === 0) continue;

      // Call API
      await saveGradeSchedule({
        grade,
        academic_year: metadata.academicYear,
        semester: metadata.semester,
        effective_from: effectiveFrom,
        effective_until: metadata.effectiveUntil,
        schedules,
      });
    }

    // Mark all as saved
    markSaved();
  }, [grids, teachers, metadata, markSaved, getDirtyGrades, getTimeSlotInfo]);

  // Auto-save hook with 2 second debounce and 3 retries
  const autoSave = useAutoSave(hasUnsavedChanges && teachers.size > 0, {
    debounceMs: 2000,
    maxRetries: 3,
    retryDelayMs: 1000,
    onSave: performAutoSave,
    onError: (error) => {
      console.error('Auto-save error:', error);
    },
  });

  // Calculate stats
  const getGradeStats = useCallback((grade: string) => {
    const gradeGrid = grids[grade] || {};
    let filled = 0;
    let locked = 0;
    const teacherSet = new Set<string>();

    for (const className in gradeGrid) {
      for (const rowKey in gradeGrid[className]) {
        const cell = gradeGrid[className][rowKey];
        if (cell?.teacherCode) {
          filled++;
          teacherSet.add(cell.teacherCode);
        }
        if (cell?.isLocked) {
          locked++;
        }
      }
    }

    const classes = Object.keys(gradeGrid).length;
    // 5 days x 8 non-break periods = 40 slots per class
    const totalSlots = classes * 40;

    return { filled, locked, teachers: teacherSet.size, totalSlots, classes };
  }, [grids]);

  // Manual save to server (with dialog)
  const handleSaveToServer = useCallback(async () => {
    if (!metadata.effectiveFrom) {
      showError('Gagal', 'Tanggal efektif harus diisi');
      return;
    }

    let totalCreated = 0;
    const totalErrors: string[] = [];

    try {
      // Save each grade separately
      for (const grade of GRADES) {
        const gradeGrid = grids[grade] || {};
        const schedules: Array<{
          class_name: string;
          day: string;
          period: number;
          time_start: string;
          time_end: string;
          teacher_id?: string;
          teacher_code?: string;
          subject?: string;
          is_locked?: boolean;
        }> = [];

        // Convert grid to schedule entries
        for (const className in gradeGrid) {
          for (const rowKey in gradeGrid[className]) {
            const cell = gradeGrid[className][rowKey];
            const { day, period } = parseRowKey(rowKey);
            const dayEn = DAY_MAPPING[day] || 'monday';
            const timeSlot = getTimeSlotInfo(period);

            // Get teacher info
            const teacher = cell?.teacherCode ? teachers.get(cell.teacherCode) : null;

            schedules.push({
              class_name: className,
              day: dayEn,
              period,
              time_start: timeSlot.start,
              time_end: timeSlot.end,
              teacher_id: teacher?.employeeId,
              teacher_code: cell?.teacherCode || undefined,
              subject: cell?.subject || teacher?.subject || undefined,
              is_locked: cell?.isLocked,
            });
          }
        }

        if (schedules.length === 0) continue;

        // Call API
        const result = await saveGradeSchedule({
          grade,
          academic_year: metadata.academicYear,
          semester: metadata.semester,
          effective_from: metadata.effectiveFrom,
          effective_until: metadata.effectiveUntil,
          schedules,
        });

        totalCreated += result.created;
        if (result.errors.length > 0) {
          totalErrors.push(...result.errors.map((e) => `Grade ${grade}: ${e.error}`));
        }
      }

      markSaved();
      setShowSaveDialog(false);

      if (totalErrors.length > 0) {
        warning(
          'Sebagian Berhasil',
          `${totalCreated} jadwal tersimpan. ${totalErrors.length} error.`
        );
      } else {
        success('Berhasil', `${totalCreated} jadwal tersimpan ke server`);
      }
    } catch (err: unknown) {
      const message = err instanceof Error ? err.message : 'Gagal menyimpan ke server';
      showError('Gagal', message);
    }
  }, [grids, teachers, metadata, markSaved, success, showError, warning, getTimeSlotInfo]);

  // Load from server
  const handleLoadFromServer = useCallback(async (silent = false) => {
    setIsLoading(true);

    try {
      let totalLoaded = 0;

      for (const grade of GRADES) {
        const result = await loadGradeSchedule(grade);

        if (result.total_schedules === 0) continue;

        // Load teachers
        for (const teacher of result.teachers) {
          addTeacher({
            code: teacher.code,
            name: teacher.name,
            subject: teacher.position,
            maxJP: null,
            color: `hsl(${Math.random() * 360}, 70%, 85%)`,
            employeeId: teacher.id,
          });
        }

        // Load grid data
        for (const className in result.grid) {
          for (const rowKey in result.grid[className]) {
            const cellData = result.grid[className][rowKey];
            setCell(grade, className, rowKey, {
              teacherCode: cellData.teacherCode,
              subject: cellData.subject,
              isLocked: cellData.isLocked,
              teachingScheduleId: cellData.scheduleId,
            });
          }
        }

        totalLoaded += result.total_schedules;

        // Update metadata from first grade with data
        if (result.metadata.academic_year) {
          setMetadata({
            academicYear: result.metadata.academic_year,
            semester: (result.metadata.semester as 1 | 2) || 1,
            effectiveFrom: result.metadata.effective_from || '',
            effectiveUntil: result.metadata.effective_until || undefined,
          });
        }
      }

      // Mark as saved after loading (no unsaved changes)
      markSaved();

      if (!silent) {
        if (totalLoaded > 0) {
          success('Berhasil', `${totalLoaded} jadwal dimuat dari server`);
        } else {
          warning('Info', 'Tidak ada jadwal tersimpan di server');
        }
      }
    } catch (err: unknown) {
      if (!silent) {
        const message = err instanceof Error ? err.message : 'Gagal memuat dari server';
        showError('Gagal', message);
      }
    } finally {
      setIsLoading(false);
      setIsInitialLoad(false);
    }
  }, [addTeacher, setCell, setMetadata, markSaved, success, warning, showError]);

  // Auto-load from server on mount
  useEffect(() => {
    if (hasLoadedRef.current) return;
    hasLoadedRef.current = true;

    // Load data from server silently on first mount
    handleLoadFromServer(true);
  }, [handleLoadFromServer]);

  // Export to JSON
  const handleExportJSON = useCallback(() => {
    const data = {
      grids,
      teachers: Array.from(teachers.entries()),
      metadata,
      exportedAt: new Date().toISOString(),
      version: '1.0',
    };

    const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `jadwal-grade-${new Date().toISOString().split('T')[0]}.json`;
    link.click();
    URL.revokeObjectURL(url);
    success('Berhasil', 'Jadwal diekspor ke file JSON');
  }, [grids, teachers, metadata, success]);

  // Import from JSON
  const handleImportJSON = useCallback((e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = (evt) => {
      try {
        const data = JSON.parse(evt.target?.result as string);

        // Validate structure
        if (!data.grids || !data.teachers) {
          throw new Error('Format file tidak valid');
        }

        // Set grids directly
        useGradeScheduleStore.setState({
          grids: data.grids,
          teachers: new Map(data.teachers),
          metadata: data.metadata || metadata,
          hasUnsavedChanges: true,
          dirtyGrades: new Set(GRADES),
        });

        success('Berhasil', 'Jadwal dimuat dari file JSON');
      } catch {
        showError('Gagal', 'File tidak valid atau rusak');
      }
    };
    reader.readAsText(file);

    // Reset input
    if (fileInputRef.current) {
      fileInputRef.current.value = '';
    }
  }, [metadata, success, showError]);

  // Handle reset
  const handleReset = useCallback(() => {
    if (resetTarget === 'grade') {
      resetGrade(activeGrade);
      success('Berhasil', `Jadwal Kelas ${activeGrade} direset`);
    } else {
      resetAll();
      success('Berhasil', 'Semua jadwal direset');
    }
    setShowResetDialog(false);
  }, [resetTarget, activeGrade, resetGrade, resetAll, success]);

  // Keyboard shortcuts
  useEffect(() => {
    const handleKeyDown = (e: KeyboardEvent) => {
      // Don't trigger if typing in input/textarea
      const target = e.target as HTMLElement;
      if (target.tagName === 'INPUT' || target.tagName === 'TEXTAREA') {
        return;
      }

      // Ctrl+S - Force save
      if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        if (hasUnsavedChanges) {
          autoSave.saveNow();
        }
        return;
      }

      // Escape - Cancel swap or clear selected teacher
      if (e.key === 'Escape') {
        if (swapSource) {
          cancelSwap();
        } else if (activeTeacher) {
          setActiveTeacher(null);
        }
        return;
      }

      // Delete - Clear selected cell
      if (e.key === 'Delete' || e.key === 'Backspace') {
        if (selectedCell) {
          clearCell(selectedCell.grade, selectedCell.className, selectedCell.rowKey);
        }
        return;
      }

      // Number keys 1-9 - Quick teacher selection
      if (e.key >= '1' && e.key <= '9') {
        const index = parseInt(e.key) - 1;
        const teacherList = Array.from(teachers.values());
        if (teacherList[index]) {
          setActiveTeacher(teacherList[index]);
        }
        return;
      }
    };

    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, [
    hasUnsavedChanges,
    autoSave,
    swapSource,
    cancelSwap,
    activeTeacher,
    setActiveTeacher,
    selectedCell,
    clearCell,
    teachers,
  ]);

  const stats = getGradeStats(activeGrade);

  return (
    <TooltipProvider>
      <div className="flex flex-col h-full">
        {/* Header */}
        <div className="mb-4">
          <div className="flex items-center justify-between">
            <div>
              <h1 className="text-2xl font-bold flex items-center gap-2">
                <GripVertical className="h-6 w-6 text-primary" />
                Susun Jadwal Mengajar
              </h1>
              <p className="text-sm text-muted-foreground">
                Buat jadwal mengajar dengan tampilan Excel-like. Auto-save aktif.
              </p>
            </div>

            {/* Keyboard shortcuts hint */}
            <Tooltip>
              <TooltipTrigger asChild>
                <Button variant="ghost" size="sm" className="text-muted-foreground">
                  <Keyboard className="h-4 w-4 mr-1" />
                  Shortcuts
                </Button>
              </TooltipTrigger>
              <TooltipContent side="left" className="w-64">
                <div className="space-y-1 text-xs">
                  <div className="flex justify-between">
                    <span className="text-muted-foreground">Ctrl+S</span>
                    <span>Simpan sekarang</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-muted-foreground">Escape</span>
                    <span>Batal swap/pilih guru</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-muted-foreground">Delete</span>
                    <span>Hapus sel terpilih</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-muted-foreground">1-9</span>
                    <span>Pilih guru ke-n</span>
                  </div>
                </div>
              </TooltipContent>
            </Tooltip>
          </div>
        </div>

        {/* Swap Alert */}
        {swapSource && (
          <Alert className="mb-4 border-primary bg-primary/10">
            <ArrowRightLeft className="h-4 w-4 text-primary" />
            <AlertDescription className="flex items-center justify-between">
              <span>
                Mode Tukar: Klik sel lain untuk menukar dengan{' '}
                <strong>{swapSource.className}</strong>
              </span>
              <Button variant="outline" size="sm" onClick={cancelSwap}>
                Batal (Esc)
              </Button>
            </AlertDescription>
          </Alert>
        )}

        {/* Main Content */}
        <div className="flex-1 flex gap-4 min-h-0">
          {/* Left: Grid */}
          <div className="flex-1 flex flex-col min-w-0">
            <Card className="flex-1 flex flex-col">
              <CardHeader className="pb-3">
                <div className="flex items-center justify-between">
                  <div>
                    <CardTitle className="text-base">Jadwal Kelas {activeGrade}</CardTitle>
                    <CardDescription>
                      {stats.filled} terisi dari {stats.totalSlots} slot ({stats.classes} kelas)
                    </CardDescription>
                  </div>

                  {/* Toolbar */}
                  <div className="flex items-center gap-2 flex-wrap">
                    <input
                      type="file"
                      ref={fileInputRef}
                      onChange={handleImportJSON}
                      accept=".json"
                      className="hidden"
                    />

                    {/* Server operations */}
                    <Tooltip>
                      <TooltipTrigger asChild>
                        <Button
                          variant="outline"
                          size="sm"
                          onClick={() => setShowSaveDialog(true)}
                        >
                          <Save className="h-4 w-4 mr-1" />
                          Pengaturan
                        </Button>
                      </TooltipTrigger>
                      <TooltipContent>
                        Atur tahun ajaran dan semester
                      </TooltipContent>
                    </Tooltip>

                    <Button
                      variant="outline"
                      size="sm"
                      onClick={() => handleLoadFromServer(false)}
                      disabled={isLoading}
                    >
                      {isLoading ? (
                        <Loader2 className="h-4 w-4 mr-1 animate-spin" />
                      ) : (
                        <CloudDownload className="h-4 w-4 mr-1" />
                      )}
                      Muat
                    </Button>

                    <div className="w-px h-6 bg-border" />

                    {/* Local operations */}
                    <Button
                      variant="outline"
                      size="sm"
                      onClick={() => setShowImportDialog(true)}
                    >
                      <FileSpreadsheet className="h-4 w-4 mr-1" />
                      Excel
                    </Button>

                    <Button
                      variant="outline"
                      size="sm"
                      onClick={() => fileInputRef.current?.click()}
                    >
                      <Upload className="h-4 w-4 mr-1" />
                      JSON
                    </Button>

                    <Button variant="outline" size="sm" onClick={handleExportJSON}>
                      <Download className="h-4 w-4 mr-1" />
                      Export
                    </Button>

                    <Button
                      variant="outline"
                      size="sm"
                      onClick={() => {
                        setResetTarget('grade');
                        setShowResetDialog(true);
                      }}
                    >
                      <Trash2 className="h-4 w-4" />
                    </Button>
                  </div>
                </div>
              </CardHeader>

              <CardContent className="flex-1 p-3 pt-0 overflow-auto">
                <Tabs value={activeGrade} onValueChange={setActiveGrade}>
                  <TabsList className="mb-3">
                    {GRADES.map((grade) => {
                      const gradeStats = getGradeStats(grade);
                      return (
                        <TabsTrigger key={grade} value={grade} className="gap-2">
                          Kelas {grade}
                          <Badge variant="secondary" className="text-[10px] px-1.5">
                            {gradeStats.filled}
                          </Badge>
                        </TabsTrigger>
                      );
                    })}
                  </TabsList>

                  {GRADES.map((grade) => (
                    <TabsContent key={grade} value={grade} className="mt-0">
                      {isInitialLoad ? (
                        <div className="flex items-center justify-center py-20">
                          <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
                          <span className="ml-2 text-muted-foreground">Memuat jadwal...</span>
                        </div>
                      ) : (
                        <GradeScheduleGrid grade={grade} />
                      )}
                    </TabsContent>
                  ))}
                </Tabs>
              </CardContent>

              {/* Status Bar */}
              <StatusBar
                status={autoSave.status}
                lastSavedAt={autoSave.lastSavedAt}
                error={autoSave.error}
                retryCount={autoSave.retryCount}
                onRetry={autoSave.saveNow}
                onSaveNow={autoSave.saveNow}
              />
            </Card>
          </div>

          {/* Right: Teacher Sidebar */}
          <div className="w-64 shrink-0">
            <TeacherSidebar />
          </div>
        </div>

        {/* Excel Import Dialog */}
        <ExcelImportDialog
          open={showImportDialog}
          onOpenChange={setShowImportDialog}
        />

        {/* Save Settings Dialog */}
        <Dialog open={showSaveDialog} onOpenChange={setShowSaveDialog}>
          <DialogContent>
            <DialogHeader>
              <DialogTitle>Pengaturan Jadwal</DialogTitle>
              <DialogDescription>
                Atur tahun ajaran dan periode berlaku. Jadwal akan tersimpan otomatis.
              </DialogDescription>
            </DialogHeader>

            <div className="space-y-4 py-4">
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label>Tahun Ajaran</Label>
                  <Input
                    value={metadata.academicYear}
                    onChange={(e) => setMetadata({ academicYear: e.target.value })}
                    placeholder="2024/2025"
                  />
                </div>
                <div className="space-y-2">
                  <Label>Semester</Label>
                  <Select
                    value={String(metadata.semester)}
                    onValueChange={(v) => setMetadata({ semester: parseInt(v) as 1 | 2 })}
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
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label>Berlaku Dari</Label>
                  <Input
                    type="date"
                    value={metadata.effectiveFrom}
                    onChange={(e) => setMetadata({ effectiveFrom: e.target.value })}
                  />
                </div>
                <div className="space-y-2">
                  <Label>Berlaku Sampai</Label>
                  <Input
                    type="date"
                    value={metadata.effectiveUntil || ''}
                    onChange={(e) => setMetadata({ effectiveUntil: e.target.value || undefined })}
                  />
                </div>
              </div>

              <Alert>
                <CheckCircle2 className="h-4 w-4" />
                <AlertDescription>
                  Jadwal akan tersimpan otomatis setelah setiap perubahan.
                  Guru Honorer dapat absen sesuai jadwal mengajar mereka.
                </AlertDescription>
              </Alert>
            </div>

            <DialogFooter>
              <Button variant="outline" onClick={() => setShowSaveDialog(false)}>
                Tutup
              </Button>
              <Button onClick={handleSaveToServer}>
                <Save className="h-4 w-4 mr-1" />
                Simpan Sekarang
              </Button>
            </DialogFooter>
          </DialogContent>
        </Dialog>

        {/* Reset Confirmation Dialog */}
        <Dialog open={showResetDialog} onOpenChange={setShowResetDialog}>
          <DialogContent>
            <DialogHeader>
              <DialogTitle>Konfirmasi Reset</DialogTitle>
              <DialogDescription>
                {resetTarget === 'grade'
                  ? `Apakah Anda yakin ingin mereset jadwal Kelas ${activeGrade}? Semua data akan dihapus.`
                  : 'Apakah Anda yakin ingin mereset SEMUA jadwal? Semua data akan dihapus.'}
              </DialogDescription>
            </DialogHeader>
            <DialogFooter className="gap-2">
              <Button variant="outline" onClick={() => setShowResetDialog(false)}>
                Batal
              </Button>
              <Button variant="destructive" onClick={handleReset}>
                <Trash2 className="h-4 w-4 mr-1" />
                Reset
              </Button>
            </DialogFooter>
          </DialogContent>
        </Dialog>
      </div>
    </TooltipProvider>
  );
}
