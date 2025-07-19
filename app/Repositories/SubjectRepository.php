<?php

namespace App\Repositories;

use App\Models\Employee;
use App\Models\Subject;
use App\Models\TeacherSubject;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Subject Repository
 *
 * Handles all subject-related database operations
 */
class SubjectRepository extends BaseRepository
{
    public function __construct(Subject $subject)
    {
        parent::__construct($subject);
    }

    /**
     * Get active subjects
     */
    public function getActiveSubjects(): Collection
    {
        $cacheKey = $this->getCacheKey('active_subjects');

        return cache()->remember($cacheKey, 3600, function () {
            return $this->model
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
        });
    }

    /**
     * Get subjects with teacher assignments
     */
    public function getSubjectsWithTeachers(): Collection
    {
        $cacheKey = $this->getCacheKey('subjects_with_teachers');

        return cache()->remember($cacheKey, 1800, function () {
            return $this->model
                ->with(['teachers.employee'])
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
        });
    }

    /**
     * Get subjects by department
     */
    public function getSubjectsByDepartment(?string $department = null): Collection
    {
        $cacheKey = $this->getCacheKey('subjects_by_department', [$department]);

        return cache()->remember($cacheKey, 1800, function () use ($department) {
            $query = $this->model->where('is_active', true);

            if ($department) {
                $query->where('department', $department);
            }

            return $query->orderBy('name')->get();
        });
    }

    /**
     * Get subjects by level
     */
    public function getSubjectsByLevel(string $level): Collection
    {
        $cacheKey = $this->getCacheKey('subjects_by_level', [$level]);

        return cache()->remember($cacheKey, 1800, function () use ($level) {
            return $this->model
                ->where('level', $level)
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
        });
    }

    /**
     * Get subjects for a teacher
     */
    public function getTeacherSubjects(string $teacherId): Collection
    {
        $cacheKey = $this->getCacheKey('teacher_subjects', [$teacherId]);

        return cache()->remember($cacheKey, 1800, function () use ($teacherId) {
            return $this->model
                ->whereHas('teachers', function ($query) use ($teacherId) {
                    $query->where('employee_id', $teacherId);
                })
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
        });
    }

    /**
     * Get subject statistics
     */
    public function getSubjectStatistics(): array
    {
        $cacheKey = $this->getCacheKey('subject_statistics');

        return cache()->remember($cacheKey, 3600, function () {
            $totalSubjects = $this->model->count();
            $activeSubjects = $this->model->where('is_active', true)->count();
            $inactiveSubjects = $totalSubjects - $activeSubjects;

            // Department distribution
            $departmentStats = $this->model
                ->where('is_active', true)
                ->select('department', DB::raw('COUNT(*) as count'))
                ->groupBy('department')
                ->orderBy('count', 'desc')
                ->get()
                ->pluck('count', 'department')
                ->toArray();

            // Level distribution
            $levelStats = $this->model
                ->where('is_active', true)
                ->select('level', DB::raw('COUNT(*) as count'))
                ->groupBy('level')
                ->orderBy('level')
                ->get()
                ->pluck('count', 'level')
                ->toArray();

            // Teacher assignments
            $teacherAssignments = DB::table('teacher_subjects')
                ->join('subjects', 'teacher_subjects.subject_id', '=', 'subjects.id')
                ->where('subjects.is_active', true)
                ->count();

            $subjectsWithTeachers = $this->model
                ->where('is_active', true)
                ->whereHas('teachers')
                ->count();

            $subjectsWithoutTeachers = $activeSubjects - $subjectsWithTeachers;

            return [
                'total_subjects' => $totalSubjects,
                'active_subjects' => $activeSubjects,
                'inactive_subjects' => $inactiveSubjects,
                'subjects_with_teachers' => $subjectsWithTeachers,
                'subjects_without_teachers' => $subjectsWithoutTeachers,
                'teacher_assignments' => $teacherAssignments,
                'department_stats' => $departmentStats,
                'level_stats' => $levelStats,
                'average_teachers_per_subject' => $subjectsWithTeachers > 0 ? round($teacherAssignments / $subjectsWithTeachers, 1) : 0,
            ];
        });
    }

    /**
     * Get subject by code
     */
    public function getByCode(string $code): ?Subject
    {
        $cacheKey = $this->getCacheKey('by_code', [$code]);

        return cache()->remember($cacheKey, 1800, function () use ($code) {
            return $this->model
                ->where('code', $code)
                ->first();
        });
    }

    /**
     * Get teachers for a subject
     */
    public function getSubjectTeachers(string $subjectId): Collection
    {
        $cacheKey = $this->getCacheKey('subject_teachers', [$subjectId]);

        return cache()->remember($cacheKey, 1800, function () use ($subjectId) {
            return Employee::whereHas('subjects', function ($query) use ($subjectId) {
                $query->where('subject_id', $subjectId);
            })
                ->where('is_active', true)
                ->with(['user'])
                ->orderBy('full_name')
                ->get();
        });
    }

    /**
     * Assign teacher to subject
     */
    public function assignTeacher(string $subjectId, string $teacherId): bool
    {
        return DB::transaction(function () use ($subjectId, $teacherId) {
            $subject = $this->findOrFail($subjectId);
            $teacher = Employee::findOrFail($teacherId);

            // Check if assignment already exists
            $exists = TeacherSubject::where('subject_id', $subjectId)
                ->where('employee_id', $teacherId)
                ->exists();

            if ($exists) {
                throw new \Exception('Teacher is already assigned to this subject');
            }

            TeacherSubject::create([
                'subject_id' => $subjectId,
                'employee_id' => $teacherId,
                'assigned_at' => now(),
            ]);

            $this->clearCache();

            return true;
        });
    }

    /**
     * Remove teacher from subject
     */
    public function removeTeacher(string $subjectId, string $teacherId): bool
    {
        return DB::transaction(function () use ($subjectId, $teacherId) {
            $deleted = TeacherSubject::where('subject_id', $subjectId)
                ->where('employee_id', $teacherId)
                ->delete();

            $this->clearCache();

            return $deleted > 0;
        });
    }

    /**
     * Get subject schedule
     */
    public function getSubjectSchedule(string $subjectId): Collection
    {
        $cacheKey = $this->getCacheKey('subject_schedule', [$subjectId]);

        return cache()->remember($cacheKey, 1800, function () use ($subjectId) {
            return DB::table('employee_schedules')
                ->join('subjects', 'employee_schedules.subject_id', '=', 'subjects.id')
                ->join('employees', 'employee_schedules.employee_id', '=', 'employees.id')
                ->join('periods', 'employee_schedules.period_id', '=', 'periods.id')
                ->where('subjects.id', $subjectId)
                ->select(
                    'employee_schedules.*',
                    'employees.full_name as teacher_name',
                    'periods.name as period_name',
                    'periods.start_time',
                    'periods.end_time'
                )
                ->orderBy('employee_schedules.day_of_week')
                ->orderBy('periods.start_time')
                ->get();
        });
    }

    /**
     * Get unassigned subjects
     */
    public function getUnassignedSubjects(): Collection
    {
        $cacheKey = $this->getCacheKey('unassigned_subjects');

        return cache()->remember($cacheKey, 1800, function () {
            return $this->model
                ->where('is_active', true)
                ->whereDoesntHave('teachers')
                ->orderBy('name')
                ->get();
        });
    }

    /**
     * Get subject workload
     */
    public function getSubjectWorkload(string $subjectId): array
    {
        $cacheKey = $this->getCacheKey('subject_workload', [$subjectId]);

        return cache()->remember($cacheKey, 1800, function () use ($subjectId) {
            $subject = $this->findOrFail($subjectId);

            // Get weekly schedule hours
            $weeklyHours = DB::table('employee_schedules')
                ->join('periods', 'employee_schedules.period_id', '=', 'periods.id')
                ->where('employee_schedules.subject_id', $subjectId)
                ->sum(DB::raw('TIMESTAMPDIFF(MINUTE, periods.start_time, periods.end_time)'));

            $weeklyHours = round($weeklyHours / 60, 2); // Convert to hours

            // Get teacher count
            $teacherCount = TeacherSubject::where('subject_id', $subjectId)->count();

            // Get class count (from schedules)
            $classCount = DB::table('employee_schedules')
                ->where('subject_id', $subjectId)
                ->distinct('class_id')
                ->count('class_id');

            return [
                'subject_name' => $subject->name,
                'weekly_hours' => $weeklyHours,
                'teacher_count' => $teacherCount,
                'class_count' => $classCount,
                'hours_per_teacher' => $teacherCount > 0 ? round($weeklyHours / $teacherCount, 2) : 0,
                'hours_per_class' => $classCount > 0 ? round($weeklyHours / $classCount, 2) : 0,
            ];
        });
    }

    /**
     * Search subjects
     */
    public function searchSubjects(string $query): Collection
    {
        $cacheKey = $this->getCacheKey('search_subjects', [$query]);

        return cache()->remember($cacheKey, 600, function () use ($query) {
            return $this->model
                ->where(function ($q) use ($query) {
                    $q->where('name', 'LIKE', "%{$query}%")
                        ->orWhere('code', 'LIKE', "%{$query}%")
                        ->orWhere('description', 'LIKE', "%{$query}%");
                })
                ->where('is_active', true)
                ->orderBy('name')
                ->limit(10)
                ->get();
        });
    }

    /**
     * Get subjects for dropdown
     */
    public function getSubjectsForDropdown(): Collection
    {
        $cacheKey = $this->getCacheKey('subjects_dropdown');

        return cache()->remember($cacheKey, 3600, function () {
            return $this->model
                ->select(['id', 'name', 'code'])
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->map(function ($subject) {
                    return [
                        'id' => $subject->id,
                        'text' => $subject->name.($subject->code ? " ({$subject->code})" : ''),
                        'name' => $subject->name,
                        'code' => $subject->code,
                    ];
                });
        });
    }

    /**
     * Get subject curriculum
     */
    public function getSubjectCurriculum(string $subjectId): array
    {
        $cacheKey = $this->getCacheKey('subject_curriculum', [$subjectId]);

        return cache()->remember($cacheKey, 3600, function () use ($subjectId) {
            $subject = $this->findOrFail($subjectId);

            // This would be expanded based on curriculum structure
            return [
                'subject_id' => $subject->id,
                'subject_name' => $subject->name,
                'level' => $subject->level,
                'department' => $subject->department,
                'credit_hours' => $subject->credit_hours,
                'description' => $subject->description,
                'objectives' => $subject->objectives ?? [],
                'prerequisites' => $subject->prerequisites ?? [],
                'topics' => $subject->topics ?? [],
                'assessment_methods' => $subject->assessment_methods ?? [],
            ];
        });
    }

    /**
     * Create subject with validation
     */
    public function createSubject(array $data): Subject
    {
        return DB::transaction(function () use ($data) {
            // Check for duplicate code
            if (isset($data['code']) && $this->getByCode($data['code'])) {
                throw new \Exception('Subject with this code already exists');
            }

            // Check for duplicate name in same department/level
            $existing = $this->model
                ->where('name', $data['name'])
                ->where('department', $data['department'] ?? null)
                ->where('level', $data['level'] ?? null)
                ->first();

            if ($existing) {
                throw new \Exception('Subject with this name already exists in the same department/level');
            }

            $subject = $this->create($data);

            $this->clearCache();

            return $subject;
        });
    }

    /**
     * Update subject with validation
     */
    public function updateSubject(string $subjectId, array $data): Subject
    {
        return DB::transaction(function () use ($subjectId, $data) {
            $subject = $this->findOrFail($subjectId);

            // Check for duplicate code (excluding current subject)
            if (isset($data['code'])) {
                $existing = $this->getByCode($data['code']);
                if ($existing && $existing->id !== $subjectId) {
                    throw new \Exception('Subject with this code already exists');
                }
            }

            // Check for duplicate name (excluding current subject)
            if (isset($data['name'])) {
                $existing = $this->model
                    ->where('name', $data['name'])
                    ->where('department', $data['department'] ?? $subject->department)
                    ->where('level', $data['level'] ?? $subject->level)
                    ->where('id', '!=', $subjectId)
                    ->first();

                if ($existing) {
                    throw new \Exception('Subject with this name already exists in the same department/level');
                }
            }

            $subject->update($data);

            $this->clearCache();

            return $subject->fresh();
        });
    }

    /**
     * Deactivate subject
     */
    public function deactivateSubject(string $subjectId): bool
    {
        return DB::transaction(function () use ($subjectId) {
            $subject = $this->findOrFail($subjectId);

            // Check if subject has active schedules
            $hasSchedules = DB::table('employee_schedules')
                ->where('subject_id', $subjectId)
                ->exists();

            if ($hasSchedules) {
                throw new \Exception('Cannot deactivate subject with active schedules');
            }

            $result = $subject->update(['is_active' => false]);

            $this->clearCache();

            return $result;
        });
    }

    /**
     * Get subject performance metrics
     */
    public function getSubjectPerformanceMetrics(string $subjectId): array
    {
        $cacheKey = $this->getCacheKey('subject_performance', [$subjectId]);

        return cache()->remember($cacheKey, 3600, function () use ($subjectId) {
            $subject = $this->findOrFail($subjectId);

            // This would be expanded based on performance tracking
            return [
                'subject_id' => $subject->id,
                'subject_name' => $subject->name,
                'total_classes' => 0, // To be calculated from schedule
                'classes_held' => 0, // To be calculated from attendance
                'completion_rate' => 0,
                'student_satisfaction' => 0,
                'teacher_feedback' => [],
                'improvement_suggestions' => [],
            ];
        });
    }
}
