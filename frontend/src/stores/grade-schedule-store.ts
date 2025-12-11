/**
 * Grade Schedule Store
 *
 * Zustand store untuk manajemen state jadwal per grade
 */

import { create } from 'zustand';
import type {
  GradeScheduleCell,
  GradeGrid,
  TeacherWithJP,
  ValidationResult,
  ParsedExcelData,
  ScheduleMetadata,
} from '@/pages/admin/schedules/grade-builder/types';
import {
  GRADES,
  DEFAULT_CLASSES,
  initializeEmptyGrid,
  getRowKey,
  parseRowKey,
  generateTeacherColor,
  DAYS,
  TIME_SLOTS,
} from '@/pages/admin/schedules/grade-builder/constants';

interface SwapSource {
  grade: string;
  className: string;
  rowKey: string;
}

interface GradeScheduleState {
  // Current active grade tab
  activeGrade: string;

  // Grid data for all grades
  grids: Record<string, GradeGrid>; // { '7': GradeGrid, '8': GradeGrid, '9': GradeGrid }

  // Teachers registry with JP settings
  teachers: Map<string, TeacherWithJP>;

  // Active teacher for toggle mode
  activeTeacher: TeacherWithJP | null;

  // Swap mode
  swapSource: SwapSource | null;

  // Metadata for saving
  metadata: ScheduleMetadata;

  // Dirty state tracking
  hasUnsavedChanges: boolean;
  dirtyGrades: Set<string>; // Track which grades have changes
  lastSavedAt: Date | null; // Last successful save timestamp

  // Loading states
  isLoading: boolean;
  isSaving: boolean;

  // Selected cell for keyboard operations
  selectedCell: { grade: string; className: string; rowKey: string } | null;

  // Actions
  setActiveGrade: (grade: string) => void;
  setActiveTeacher: (teacher: TeacherWithJP | null) => void;

  // Cell operations
  setCell: (grade: string, className: string, rowKey: string, cell: GradeScheduleCell) => void;
  toggleCell: (grade: string, className: string, rowKey: string) => ValidationResult;
  clearCell: (grade: string, className: string, rowKey: string) => void;
  lockCell: (grade: string, className: string, rowKey: string) => void;
  unlockCell: (grade: string, className: string, rowKey: string) => void;

  // Swap operations
  startSwap: (grade: string, className: string, rowKey: string) => void;
  cancelSwap: () => void;
  completeSwap: (grade: string, className: string, rowKey: string) => ValidationResult;

  // Teacher operations
  addTeacher: (teacher: TeacherWithJP) => void;
  updateTeacherMaxJP: (code: string, maxJP: number | null) => void;
  removeTeacher: (code: string) => void;

  // Bulk operations
  importFromExcel: (data: ParsedExcelData) => void;
  resetGrade: (grade: string) => void;
  resetAll: () => void;

  // Metadata operations
  setMetadata: (metadata: Partial<ScheduleMetadata>) => void;

  // State management
  markSaved: () => void;
  markGradesSaved: (grades: string[]) => void;
  setLoading: (loading: boolean) => void;
  setSaving: (saving: boolean) => void;
  setSelectedCell: (cell: { grade: string; className: string; rowKey: string } | null) => void;
  getDirtyGrades: () => string[];

  // Validation helpers
  validatePlacement: (teacherCode: string, grade: string, className: string, rowKey: string) => ValidationResult;
  getTeacherJPUsage: (teacherCode: string) => { current: number; max: number | null };
  checkTimeConflict: (teacherCode: string, day: string, period: number, excludeGrade?: string, excludeClass?: string) => ValidationResult;
}

// Initialize grids for all grades
function initializeAllGrids(): Record<string, GradeGrid> {
  const grids: Record<string, GradeGrid> = {};
  for (const grade of GRADES) {
    grids[grade] = initializeEmptyGrid(DEFAULT_CLASSES[grade]);
  }
  return grids;
}

// Get current academic year
function getCurrentAcademicYear(): string {
  const now = new Date();
  const year = now.getFullYear();
  const month = now.getMonth() + 1;
  // Academic year starts in July
  if (month >= 7) {
    return `${year}/${year + 1}`;
  }
  return `${year - 1}/${year}`;
}

// Get current semester
function getCurrentSemester(): 1 | 2 {
  const month = new Date().getMonth() + 1;
  // Semester 1: July - December, Semester 2: January - June
  return month >= 7 ? 1 : 2;
}

export const useGradeScheduleStore = create<GradeScheduleState>((set, get) => ({
  activeGrade: '7',
  grids: initializeAllGrids(),
  teachers: new Map(),
  activeTeacher: null,
  swapSource: null,
  metadata: {
    semester: getCurrentSemester(),
    academicYear: getCurrentAcademicYear(),
    effectiveFrom: new Date().toISOString().split('T')[0],
  },
  hasUnsavedChanges: false,
  dirtyGrades: new Set(),
  lastSavedAt: null,
  isLoading: false,
  isSaving: false,
  selectedCell: null,

  setActiveGrade: (grade) => set({ activeGrade: grade }),

  setActiveTeacher: (teacher) => set({ activeTeacher: teacher }),

  setCell: (grade, className, rowKey, cell) => {
    set((state) => {
      const newGrids = { ...state.grids };
      newGrids[grade] = { ...newGrids[grade] };
      newGrids[grade][className] = { ...newGrids[grade][className] };
      newGrids[grade][className][rowKey] = cell;
      const newDirtyGrades = new Set(state.dirtyGrades);
      newDirtyGrades.add(grade);
      return { grids: newGrids, hasUnsavedChanges: true, dirtyGrades: newDirtyGrades };
    });
  },

  toggleCell: (grade, className, rowKey) => {
    const state = get();
    const cell = state.grids[grade]?.[className]?.[rowKey];

    // Check if locked
    if (cell?.isLocked) {
      return { valid: false, type: 'locked_cell', message: 'Sel ini terkunci. Buka kunci terlebih dahulu.' };
    }

    // No active teacher selected
    if (!state.activeTeacher) {
      return { valid: false, message: 'Pilih guru terlebih dahulu' };
    }

    // parseRowKey is called in validatePlacement

    // If cell is empty, try to assign teacher
    if (!cell?.teacherCode) {
      const validation = state.validatePlacement(state.activeTeacher.code, grade, className, rowKey);
      if (!validation.valid) {
        return validation;
      }

      // Assign teacher
      set((s) => {
        const newGrids = { ...s.grids };
        newGrids[grade] = { ...newGrids[grade] };
        newGrids[grade][className] = { ...newGrids[grade][className] };
        newGrids[grade][className][rowKey] = {
          teacherCode: state.activeTeacher!.code,
          subject: state.activeTeacher!.subject,
          isLocked: false,
        };
        const newDirtyGrades = new Set(s.dirtyGrades);
        newDirtyGrades.add(grade);
        return { grids: newGrids, hasUnsavedChanges: true, dirtyGrades: newDirtyGrades };
      });

      return { valid: true };
    }

    // If cell has same teacher, remove (toggle off)
    if (cell.teacherCode === state.activeTeacher.code) {
      set((s) => {
        const newGrids = { ...s.grids };
        newGrids[grade] = { ...newGrids[grade] };
        newGrids[grade][className] = { ...newGrids[grade][className] };
        newGrids[grade][className][rowKey] = {
          teacherCode: null,
          subject: null,
          isLocked: false,
        };
        const newDirtyGrades = new Set(s.dirtyGrades);
        newDirtyGrades.add(grade);
        return { grids: newGrids, hasUnsavedChanges: true, dirtyGrades: newDirtyGrades };
      });
      return { valid: true };
    }

    // Cell has different teacher
    return {
      valid: false,
      message: `Sel sudah terisi oleh guru "${cell.teacherCode}". Hapus terlebih dahulu.`,
    };
  },

  clearCell: (grade, className, rowKey) => {
    const state = get();
    const cell = state.grids[grade]?.[className]?.[rowKey];

    if (cell?.isLocked) return;

    set((s) => {
      const newGrids = { ...s.grids };
      newGrids[grade] = { ...newGrids[grade] };
      newGrids[grade][className] = { ...newGrids[grade][className] };
      newGrids[grade][className][rowKey] = {
        teacherCode: null,
        subject: null,
        isLocked: false,
      };
      const newDirtyGrades = new Set(s.dirtyGrades);
      newDirtyGrades.add(grade);
      return { grids: newGrids, hasUnsavedChanges: true, dirtyGrades: newDirtyGrades };
    });
  },

  lockCell: (grade, className, rowKey) => {
    set((state) => {
      const newGrids = { ...state.grids };
      newGrids[grade] = { ...newGrids[grade] };
      newGrids[grade][className] = { ...newGrids[grade][className] };
      const cell = newGrids[grade][className][rowKey];
      newGrids[grade][className][rowKey] = { ...cell, isLocked: true };
      const newDirtyGrades = new Set(state.dirtyGrades);
      newDirtyGrades.add(grade);
      return { grids: newGrids, hasUnsavedChanges: true, dirtyGrades: newDirtyGrades };
    });
  },

  unlockCell: (grade, className, rowKey) => {
    set((state) => {
      const newGrids = { ...state.grids };
      newGrids[grade] = { ...newGrids[grade] };
      newGrids[grade][className] = { ...newGrids[grade][className] };
      const cell = newGrids[grade][className][rowKey];
      newGrids[grade][className][rowKey] = { ...cell, isLocked: false };
      const newDirtyGrades = new Set(state.dirtyGrades);
      newDirtyGrades.add(grade);
      return { grids: newGrids, hasUnsavedChanges: true, dirtyGrades: newDirtyGrades };
    });
  },

  startSwap: (grade, className, rowKey) => {
    set({ swapSource: { grade, className, rowKey } });
  },

  cancelSwap: () => {
    set({ swapSource: null });
  },

  completeSwap: (targetGrade, targetClass, targetRowKey) => {
    const state = get();
    if (!state.swapSource) {
      return { valid: false, message: 'Tidak ada sel sumber untuk tukar' };
    }

    const { grade: srcGrade, className: srcClass, rowKey: srcRowKey } = state.swapSource;
    const srcCell = state.grids[srcGrade]?.[srcClass]?.[srcRowKey];
    const targetCell = state.grids[targetGrade]?.[targetClass]?.[targetRowKey];

    // Check if either cell is locked
    if (srcCell?.isLocked || targetCell?.isLocked) {
      return { valid: false, type: 'locked_cell', message: 'Tidak bisa menukar sel yang terkunci' };
    }

    // Perform swap
    set((s) => {
      const newGrids = { ...s.grids };

      // Update source grade/class
      newGrids[srcGrade] = { ...newGrids[srcGrade] };
      newGrids[srcGrade][srcClass] = { ...newGrids[srcGrade][srcClass] };

      // Update target grade/class
      newGrids[targetGrade] = { ...newGrids[targetGrade] };
      newGrids[targetGrade][targetClass] = { ...newGrids[targetGrade][targetClass] };

      // Swap
      const temp = { ...srcCell };
      newGrids[srcGrade][srcClass][srcRowKey] = { ...targetCell, isLocked: false };
      newGrids[targetGrade][targetClass][targetRowKey] = { ...temp, isLocked: false };

      // Mark both grades as dirty
      const newDirtyGrades = new Set(s.dirtyGrades);
      newDirtyGrades.add(srcGrade);
      newDirtyGrades.add(targetGrade);

      return { grids: newGrids, swapSource: null, hasUnsavedChanges: true, dirtyGrades: newDirtyGrades };
    });

    return { valid: true };
  },

  addTeacher: (teacher) => {
    set((state) => {
      const newTeachers = new Map(state.teachers);
      newTeachers.set(teacher.code, teacher);
      return { teachers: newTeachers };
    });
  },

  updateTeacherMaxJP: (code, maxJP) => {
    set((state) => {
      const newTeachers = new Map(state.teachers);
      const teacher = newTeachers.get(code);
      if (teacher) {
        newTeachers.set(code, { ...teacher, maxJP });
      }
      return { teachers: newTeachers };
    });
  },

  removeTeacher: (code) => {
    set((state) => {
      const newTeachers = new Map(state.teachers);
      newTeachers.delete(code);
      return { teachers: newTeachers };
    });
  },

  importFromExcel: (data) => {
    set((state) => {
      // Import teachers
      const newTeachers = new Map(state.teachers);
      for (const t of data.teachers) {
        newTeachers.set(t.code, {
          code: t.code,
          name: t.name,
          subject: t.subject,
          maxJP: t.maxJP,
          color: generateTeacherColor(t.code),
        });
      }

      // Initialize grids
      const newGrids = initializeAllGrids();

      // Populate from parsed schedules
      for (const entry of data.schedules) {
        // Extract grade from className (e.g., "7A" -> "7")
        const gradeMatch = entry.className.match(/^(\d+)/);
        if (!gradeMatch) continue;

        const grade = gradeMatch[1];
        if (!newGrids[grade]) continue;

        // Normalize class name
        const normalizedClass = entry.className.replace(/\s+/g, '');

        if (!newGrids[grade][normalizedClass]) {
          // Create class if not exists
          newGrids[grade][normalizedClass] = {};
          for (const day of DAYS) {
            for (const slot of TIME_SLOTS) {
              if (!slot.isBreak) {
                const rowKey = getRowKey(day, slot.period);
                newGrids[grade][normalizedClass][rowKey] = {
                  teacherCode: null,
                  subject: null,
                  isLocked: false,
                };
              }
            }
          }
        }

        const rowKey = getRowKey(entry.day, entry.period);
        if (newGrids[grade][normalizedClass][rowKey] !== undefined) {
          newGrids[grade][normalizedClass][rowKey] = {
            teacherCode: entry.teacherCode,
            subject: entry.subject,
            isLocked: false,
          };
        }
      }

      return {
        grids: newGrids,
        teachers: newTeachers,
        hasUnsavedChanges: true,
      };
    });
  },

  resetGrade: (grade) => {
    set((state) => {
      const newGrids = { ...state.grids };
      const classes = Object.keys(newGrids[grade] || {});
      newGrids[grade] = initializeEmptyGrid(classes.length > 0 ? classes : DEFAULT_CLASSES[grade]);
      return { grids: newGrids, hasUnsavedChanges: true };
    });
  },

  resetAll: () => {
    set({
      grids: initializeAllGrids(),
      teachers: new Map(),
      activeTeacher: null,
      swapSource: null,
      hasUnsavedChanges: false,
    });
  },

  setMetadata: (metadata) => {
    set((state) => ({
      metadata: { ...state.metadata, ...metadata },
      hasUnsavedChanges: true,
    }));
  },

  markSaved: () => {
    set({ hasUnsavedChanges: false, dirtyGrades: new Set(), lastSavedAt: new Date() });
  },

  markGradesSaved: (grades) => {
    set((state) => {
      const newDirtyGrades = new Set(state.dirtyGrades);
      for (const grade of grades) {
        newDirtyGrades.delete(grade);
      }
      const hasUnsavedChanges = newDirtyGrades.size > 0;
      return {
        dirtyGrades: newDirtyGrades,
        hasUnsavedChanges,
        lastSavedAt: new Date(),
      };
    });
  },

  setLoading: (loading) => {
    set({ isLoading: loading });
  },

  setSaving: (saving) => {
    set({ isSaving: saving });
  },

  setSelectedCell: (cell) => {
    set({ selectedCell: cell });
  },

  getDirtyGrades: () => {
    return Array.from(get().dirtyGrades);
  },

  validatePlacement: (teacherCode, grade, className, rowKey) => {
    const state = get();
    const { day, period } = parseRowKey(rowKey);

    // Check time conflict across ALL grades
    const conflict = state.checkTimeConflict(teacherCode, day, period, grade, className);
    if (!conflict.valid) {
      return conflict;
    }

    // Check JP limit
    const teacher = state.teachers.get(teacherCode);
    if (teacher?.maxJP !== null && teacher?.maxJP !== undefined) {
      const usage = state.getTeacherJPUsage(teacherCode);
      if (usage.current >= (teacher.maxJP || 0)) {
        return {
          valid: false,
          type: 'jp_exceeded',
          message: `${teacher.name} sudah mencapai batas ${teacher.maxJP} JP/minggu`,
        };
      }
    }

    return { valid: true };
  },

  getTeacherJPUsage: (teacherCode) => {
    const state = get();
    let current = 0;

    // Count across all grades
    for (const grade of GRADES) {
      const gradeGrid = state.grids[grade];
      if (!gradeGrid) continue;

      for (const className in gradeGrid) {
        for (const rowKey in gradeGrid[className]) {
          if (gradeGrid[className][rowKey]?.teacherCode === teacherCode) {
            current++;
          }
        }
      }
    }

    const teacher = state.teachers.get(teacherCode);
    return {
      current,
      max: teacher?.maxJP ?? null,
    };
  },

  checkTimeConflict: (teacherCode, day, period, excludeGrade, excludeClass) => {
    const state = get();
    const targetRowKey = getRowKey(day, period);

    // Check across ALL grades
    for (const grade of GRADES) {
      const gradeGrid = state.grids[grade];
      if (!gradeGrid) continue;

      for (const className in gradeGrid) {
        // Skip the cell being edited
        if (grade === excludeGrade && className === excludeClass) continue;

        const cell = gradeGrid[className][targetRowKey];
        if (cell?.teacherCode === teacherCode) {
          return {
            valid: false,
            type: 'time_conflict',
            message: `Guru "${teacherCode}" sudah mengajar di kelas ${className} pada ${day} jam ke-${period}`,
            conflictInfo: {
              teacherCode,
              className,
              day,
              period,
              grade,
            },
          };
        }
      }
    }

    return { valid: true };
  },
}));
