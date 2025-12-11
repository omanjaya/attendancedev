/**
 * Grade Schedule Builder Constants
 *
 * Konstanta untuk struktur jadwal berdasarkan format Excel SMP
 */

import type { ScheduleRow } from './types';

// Mapping hari Indonesia ke English
export const DAY_MAPPING: Record<string, string> = {
  'Senin': 'monday',
  'Selasa': 'tuesday',
  'Rabu': 'wednesday',
  'Kamis': 'thursday',
  'Jumat': 'friday',
  'Sabtu': 'saturday',
};

// Daftar hari
export const DAYS = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'] as const;
export const DAYS_EN = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'] as const;

// Time slots berdasarkan Excel
export const TIME_SLOTS = [
  { period: 1, start: '07:00', end: '07:45', isBreak: false },
  { period: 2, start: '07:45', end: '08:30', isBreak: false },
  { period: 3, start: '08:30', end: '09:15', isBreak: false },
  { period: 4, start: '09:15', end: '09:30', isBreak: true, label: 'Istirahat 1' },
  { period: 5, start: '09:30', end: '10:15', isBreak: false },
  { period: 6, start: '10:15', end: '11:00', isBreak: false },
  { period: 7, start: '11:00', end: '11:45', isBreak: false },
  { period: 8, start: '11:45', end: '12:30', isBreak: true, label: 'Istirahat 2' },
  { period: 9, start: '12:30', end: '13:15', isBreak: false },
  { period: 10, start: '13:15', end: '14:00', isBreak: false },
] as const;

// Generate all schedule rows (Day x Period)
export function generateScheduleRows(): ScheduleRow[] {
  const rows: ScheduleRow[] = [];

  for (const day of DAYS) {
    for (const slot of TIME_SLOTS) {
      rows.push({
        day,
        dayEn: DAY_MAPPING[day],
        period: slot.period,
        timeStart: slot.start,
        timeEnd: slot.end,
        isBreak: slot.isBreak,
      });
    }
  }

  return rows;
}

// Get row key format
export function getRowKey(day: string, period: number): string {
  return `${day}-${period}`;
}

// Parse row key
export function parseRowKey(rowKey: string): { day: string; period: number } {
  const [day, periodStr] = rowKey.split('-');
  return { day, period: parseInt(periodStr, 10) };
}

// Default classes per grade
export const DEFAULT_CLASSES: Record<string, string[]> = {
  '7': ['7A', '7B', '7C', '7D'],
  '8': ['8A', '8B', '8C', '8D'],
  '9': ['9A', '9B', '9C', '9D', '9E'],
};

// Grade options
export const GRADES = ['7', '8', '9'] as const;

// Generate color from teacher code
export function generateTeacherColor(code: string): string {
  let hash = 0;
  for (let i = 0; i < code.length; i++) {
    hash = code.charCodeAt(i) + ((hash << 5) - hash);
  }
  const h = Math.abs(hash % 360);
  return `hsl(${h}, 70%, 85%)`;
}

// Initialize empty grid for a grade
export function initializeEmptyGrid(classes: string[]): Record<string, Record<string, { teacherCode: string | null; subject: string | null; isLocked: boolean }>> {
  const grid: Record<string, Record<string, { teacherCode: string | null; subject: string | null; isLocked: boolean }>> = {};

  for (const className of classes) {
    grid[className] = {};
    for (const day of DAYS) {
      for (const slot of TIME_SLOTS) {
        if (!slot.isBreak) {
          const rowKey = getRowKey(day, slot.period);
          grid[className][rowKey] = {
            teacherCode: null,
            subject: null,
            isLocked: false,
          };
        }
      }
    }
  }

  return grid;
}

// Semester options
export const SEMESTER_OPTIONS = [
  { value: 1, label: 'Semester 1 (Ganjil)' },
  { value: 2, label: 'Semester 2 (Genap)' },
] as const;

// Academic year options (generate dynamically)
export function generateAcademicYearOptions(): { value: string; label: string }[] {
  const currentYear = new Date().getFullYear();
  const options: { value: string; label: string }[] = [];

  for (let i = -1; i <= 2; i++) {
    const startYear = currentYear + i;
    const endYear = startYear + 1;
    const value = `${startYear}/${endYear}`;
    options.push({ value, label: value });
  }

  return options;
}

// Excel sheet names
export const EXCEL_SHEET_NAMES = {
  TEACHERS: 'KODE GURU',
  GRADE_7: 'Kelas 7',
  GRADE_8: 'Kelas 8',
  GRADE_9: 'Kelas 9',
} as const;

// Regex to parse cell value like "6-IPA" or "28-Bahasa Indonesia"
export const CELL_VALUE_REGEX = /^(\d+)-(.+)$/;
