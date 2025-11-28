import { useState, useCallback } from 'react';
import type {
  Schedule,
  ScheduleConflict,
  TimeSlot,
  AcademicClass,
  Subject,
  AvailableTeacher,
  ScheduleFormData,
  ScheduleStatistics,
  DayOfWeek,
} from '@/types/schedule';

// Mock data for development
const mockTimeSlots: TimeSlot[] = [
  { id: '1', name: 'Jam 1', start_time: '07:00', end_time: '07:45' },
  { id: '2', name: 'Jam 2', start_time: '07:45', end_time: '08:30' },
  { id: '3', name: 'Jam 3', start_time: '08:30', end_time: '09:15' },
  { id: '4', name: 'Istirahat', start_time: '09:15', end_time: '09:30', is_break: true },
  { id: '5', name: 'Jam 4', start_time: '09:30', end_time: '10:15' },
  { id: '6', name: 'Jam 5', start_time: '10:15', end_time: '11:00' },
  { id: '7', name: 'Jam 6', start_time: '11:00', end_time: '11:45' },
  { id: '8', name: 'Istirahat', start_time: '11:45', end_time: '12:30', is_break: true },
  { id: '9', name: 'Jam 7', start_time: '12:30', end_time: '13:15' },
  { id: '10', name: 'Jam 8', start_time: '13:15', end_time: '14:00' },
];

const mockClasses: AcademicClass[] = [
  { id: '1', name: 'X-A', grade_level: '10', homeroom_teacher_name: 'Budi Santoso', student_count: 32 },
  { id: '2', name: 'X-B', grade_level: '10', homeroom_teacher_name: 'Siti Rahayu', student_count: 30 },
  { id: '3', name: 'XI-A', grade_level: '11', homeroom_teacher_name: 'Ahmad Yani', student_count: 28 },
  { id: '4', name: 'XI-B', grade_level: '11', homeroom_teacher_name: 'Dewi Lestari', student_count: 31 },
  { id: '5', name: 'XII-A', grade_level: '12', homeroom_teacher_name: 'Eko Prasetyo', student_count: 29 },
];

const mockSubjects: Subject[] = [
  { id: '1', code: 'MTK', name: 'Matematika', category: 'Exact', weekly_hours: 5, color: '#3B82F6' },
  { id: '2', code: 'BIN', name: 'Bahasa Indonesia', category: 'Language', weekly_hours: 4, color: '#10B981' },
  { id: '3', code: 'BIG', name: 'Bahasa Inggris', category: 'Language', weekly_hours: 4, color: '#8B5CF6' },
  { id: '4', code: 'FIS', name: 'Fisika', category: 'Exact', weekly_hours: 4, requires_lab: true, color: '#F59E0B' },
  { id: '5', code: 'KIM', name: 'Kimia', category: 'Exact', weekly_hours: 3, requires_lab: true, color: '#EF4444' },
  { id: '6', code: 'BIO', name: 'Biologi', category: 'Exact', weekly_hours: 3, requires_lab: true, color: '#06B6D4' },
  { id: '7', code: 'SEJ', name: 'Sejarah', category: 'Social', weekly_hours: 2, color: '#84CC16' },
  { id: '8', code: 'PKN', name: 'PKN', category: 'Social', weekly_hours: 2, color: '#EC4899' },
];

const mockTeachers: AvailableTeacher[] = [
  { id: '1', name: 'Budi Santoso, S.Pd', nip: '198501012010011001', is_available: true, current_load: 20, max_load: 24 },
  { id: '2', name: 'Siti Rahayu, M.Pd', nip: '198702032011012002', is_available: true, current_load: 18, max_load: 24 },
  { id: '3', name: 'Ahmad Yani, S.Pd', nip: '199003052012011003', is_available: false, conflict_reason: 'Sudah mengajar di waktu ini', current_load: 22, max_load: 24 },
  { id: '4', name: 'Dewi Lestari, M.Pd', nip: '198805152013012004', is_available: true, current_load: 16, max_load: 24 },
];

const generateMockSchedules = (classId: string): Schedule[] => {
  const days: DayOfWeek[] = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
  const schedules: Schedule[] = [];

  days.forEach((day) => {
    // Generate 4-6 schedules per day
    const numSchedules = Math.floor(Math.random() * 3) + 4;
    const usedSlots = new Set<string>();

    for (let i = 0; i < numSchedules; i++) {
      let slotIndex: number;
      do {
        slotIndex = Math.floor(Math.random() * mockTimeSlots.filter(s => !s.is_break).length);
      } while (usedSlots.has(String(slotIndex)));
      usedSlots.add(String(slotIndex));

      const slot = mockTimeSlots.filter(s => !s.is_break)[slotIndex];
      const subject = mockSubjects[Math.floor(Math.random() * mockSubjects.length)];
      const teacher = mockTeachers[Math.floor(Math.random() * mockTeachers.length)];

      schedules.push({
        id: `${classId}-${day}-${slot.id}`,
        academic_class_id: classId,
        subject_id: subject.id,
        employee_id: teacher.id,
        time_slot_id: slot.id,
        day_of_week: day,
        room: `R.${Math.floor(Math.random() * 20) + 1}`,
        effective_from: '2024-01-01',
        is_active: true,
        is_locked: Math.random() > 0.8,
        status: 'active',
        created_at: '2024-01-01T00:00:00',
        updated_at: '2024-01-01T00:00:00',
        subject,
        employee: {
          id: teacher.id,
          nip: teacher.nip,
          name: teacher.name,
        },
        time_slot: slot,
      });
    }
  });

  return schedules;
};

export function useSchedules() {
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [schedules, setSchedules] = useState<Schedule[]>([]);
  const [conflicts, setConflicts] = useState<ScheduleConflict[]>([]);
  const [selectedClassId, setSelectedClassId] = useState<string | null>(null);

  // Fetch schedule grid for a class
  const fetchScheduleGrid = useCallback(async (classId: string) => {
    setIsLoading(true);
    setError(null);

    try {
      // Simulate API call
      await new Promise((resolve) => setTimeout(resolve, 500));

      const mockSchedules = generateMockSchedules(classId);
      setSchedules(mockSchedules);
      setSelectedClassId(classId);

      // Generate some mock conflicts
      if (Math.random() > 0.7) {
        setConflicts([
          {
            id: '1',
            type: 'teacher_double_booking',
            severity: 'high',
            schedule_a_id: mockSchedules[0]?.id || '',
            description: 'Guru Budi Santoso sudah mengajar di kelas lain pada waktu yang sama',
            suggestion: 'Pindahkan jadwal ke slot waktu lain atau pilih guru pengganti',
            is_resolved: false,
            created_at: new Date().toISOString(),
          },
        ]);
      } else {
        setConflicts([]);
      }
    } catch (err) {
      setError('Gagal memuat jadwal');
      console.error('Error fetching schedule grid:', err);
    } finally {
      setIsLoading(false);
    }
  }, []);

  // Create schedule
  const createSchedule = useCallback(async (data: ScheduleFormData) => {
    setIsLoading(true);
    try {
      await new Promise((resolve) => setTimeout(resolve, 500));

      const subject = mockSubjects.find((s) => s.id === data.subject_id);
      const teacher = mockTeachers.find((t) => t.id === data.employee_id);
      const timeSlot = mockTimeSlots.find((t) => t.id === data.time_slot_id);

      const newSchedule: Schedule = {
        id: `new-${Date.now()}`,
        academic_class_id: selectedClassId || '',
        ...data,
        is_active: true,
        is_locked: false,
        status: 'active',
        created_at: new Date().toISOString(),
        updated_at: new Date().toISOString(),
        subject,
        employee: teacher ? { id: teacher.id, nip: teacher.nip, name: teacher.name } : undefined,
        time_slot: timeSlot,
      };

      setSchedules((prev) => [...prev, newSchedule]);
      return newSchedule;
    } catch (err) {
      setError('Gagal membuat jadwal');
      throw err;
    } finally {
      setIsLoading(false);
    }
  }, [selectedClassId]);

  // Update schedule
  const updateSchedule = useCallback(async (id: string, data: Partial<ScheduleFormData>) => {
    setIsLoading(true);
    try {
      await new Promise((resolve) => setTimeout(resolve, 500));

      setSchedules((prev) =>
        prev.map((schedule) =>
          schedule.id === id
            ? { ...schedule, ...data, updated_at: new Date().toISOString() }
            : schedule
        )
      );
    } catch (err) {
      setError('Gagal mengupdate jadwal');
      throw err;
    } finally {
      setIsLoading(false);
    }
  }, []);

  // Delete schedule
  const deleteSchedule = useCallback(async (id: string) => {
    setIsLoading(true);
    try {
      await new Promise((resolve) => setTimeout(resolve, 300));
      setSchedules((prev) => prev.filter((s) => s.id !== id));
    } catch (err) {
      setError('Gagal menghapus jadwal');
      throw err;
    } finally {
      setIsLoading(false);
    }
  }, []);

  // Toggle lock
  const toggleLock = useCallback(async (id: string, reason?: string) => {
    setIsLoading(true);
    try {
      await new Promise((resolve) => setTimeout(resolve, 300));

      setSchedules((prev) =>
        prev.map((schedule) =>
          schedule.id === id
            ? {
                ...schedule,
                is_locked: !schedule.is_locked,
                lock_reason: !schedule.is_locked ? reason : undefined,
              }
            : schedule
        )
      );
    } catch (err) {
      setError('Gagal mengubah status kunci');
      throw err;
    } finally {
      setIsLoading(false);
    }
  }, []);

  // Get available teachers
  const getAvailableTeachers = useCallback(
    async (_subjectId: string, _dayOfWeek: DayOfWeek, _timeSlotId: string) => {
      await new Promise((resolve) => setTimeout(resolve, 300));
      return mockTeachers;
    },
    []
  );

  // Get statistics
  const getStatistics = useCallback(async (): Promise<ScheduleStatistics> => {
    await new Promise((resolve) => setTimeout(resolve, 300));
    return {
      total_schedules: schedules.length,
      active_schedules: schedules.filter((s) => s.is_active).length,
      locked_schedules: schedules.filter((s) => s.is_locked).length,
      conflicts_count: conflicts.length,
      classes_with_schedules: mockClasses.length,
      teachers_assigned: new Set(schedules.map((s) => s.employee_id)).size,
      average_weekly_hours: 22.5,
    };
  }, [schedules, conflicts]);

  return {
    // State
    isLoading,
    error,
    schedules,
    conflicts,
    selectedClassId,

    // Data
    timeSlots: mockTimeSlots,
    classes: mockClasses,
    subjects: mockSubjects,

    // Actions
    fetchScheduleGrid,
    createSchedule,
    updateSchedule,
    deleteSchedule,
    toggleLock,
    getAvailableTeachers,
    getStatistics,
    setSelectedClassId,
    clearError: () => setError(null),
  };
}
