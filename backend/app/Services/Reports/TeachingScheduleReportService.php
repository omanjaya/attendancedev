<?php

namespace App\Services\Reports;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\TeachingSchedule;
use Carbon\Carbon;

class TeachingScheduleReportService
{
    /**
     * Get teaching schedule report with attendance statistics
     *
     * @param int $month
     * @param int $year
     * @param string|null $subjectFilter
     * @return array
     */
    public function getTeachingReport(int $month, int $year, ?string $subjectFilter = null): array
    {
        // Calculate date range
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        // Only count up to today if current month
        if ($endDate->isFuture()) {
            $endDate = Carbon::today();
        }

        // Get all teachers with active teaching schedules
        $teachersQuery = Employee::where('is_active', true)
            ->whereHas('teachingSchedules', function ($q) use ($startDate, $endDate) {
                $q->where('is_active', true)
                    ->where(function ($q2) use ($startDate, $endDate) {
                        $q2->where('effective_from', '<=', $endDate)
                            ->where(function ($q3) use ($startDate) {
                                $q3->whereNull('effective_until')
                                    ->orWhere('effective_until', '>=', $startDate);
                            });
                    });
            })
            ->with(['teachingSchedules' => function ($q) use ($startDate, $endDate, $subjectFilter) {
                $q->where('is_active', true)
                    ->where('effective_from', '<=', $endDate)
                    ->where(function ($q2) use ($startDate) {
                        $q2->whereNull('effective_until')
                            ->orWhere('effective_until', '>=', $startDate);
                    })
                    ->with('subject');

                if ($subjectFilter) {
                    $q->whereHas('subject', function ($sq) use ($subjectFilter) {
                        $sq->where('id', $subjectFilter)
                            ->orWhere('name', 'like', "%{$subjectFilter}%");
                    });
                }
            }])
            ->orderBy('full_name');

        $teachers = $teachersQuery->get();

        // Process each teacher
        $teacherData = [];
        $summary = [
            'total_teachers' => 0,
            'total_sessions_per_week' => 0,
            'total_hours_per_week' => 0,
            'avg_hours_per_teacher' => 0,
            'avg_attendance_rate' => 0,
            'total_sessions_scheduled' => 0,
            'total_sessions_taught' => 0,
            'total_sessions_missed' => 0,
        ];

        foreach ($teachers as $teacher) {
            $schedules = $teacher->teachingSchedules;

            if ($schedules->isEmpty()) {
                continue;
            }

            // Calculate weekly stats
            $sessionsPerWeek = $schedules->count();
            $hoursPerWeek = $schedules->sum(function ($schedule) {
                $start = Carbon::parse($schedule->teaching_start_time);
                $end = Carbon::parse($schedule->teaching_end_time);
                return $start->diffInMinutes($end) / 60;
            });

            // Get unique subjects and classes
            $subjects = $schedules->pluck('subject.name')->filter()->unique()->values()->toArray();
            $classes = $schedules->pluck('class_name')->filter()->unique()->values()->toArray();

            // Calculate sessions in the period
            $scheduledSessions = $this->calculateScheduledSessions($schedules, $startDate, $endDate);

            // Get attendance for this teacher in the period
            $attendanceStats = $this->getTeacherAttendanceStats($teacher->id, $startDate, $endDate);

            // Calculate sessions taught/missed based on attendance
            $sessionsTaught = $attendanceStats['present'] + $attendanceStats['late'];
            $sessionsMissed = max(0, $scheduledSessions - $sessionsTaught);

            // Attendance rate
            $attendanceRate = $scheduledSessions > 0
                ? min(100, round(($sessionsTaught / $scheduledSessions) * 100, 1))
                : 0;

            $teacherData[] = [
                'employee_id' => $teacher->id,
                'employee_code' => $teacher->employee_code,
                'name' => $teacher->full_name,
                'department' => $teacher->department ?? '-',
                'sessions_per_week' => $sessionsPerWeek,
                'hours_per_week' => round($hoursPerWeek, 1),
                'subjects' => $subjects,
                'subjects_count' => count($subjects),
                'classes' => $classes,
                'classes_count' => count($classes),
                'scheduled_sessions' => $scheduledSessions,
                'sessions_taught' => $sessionsTaught,
                'sessions_missed' => $sessionsMissed,
                'attendance_rate' => $attendanceRate,
                'attendance_details' => [
                    'present' => $attendanceStats['present'],
                    'late' => $attendanceStats['late'],
                    'absent' => $attendanceStats['absent'],
                ],
            ];

            // Update summary
            $summary['total_teachers']++;
            $summary['total_sessions_per_week'] += $sessionsPerWeek;
            $summary['total_hours_per_week'] += $hoursPerWeek;
            $summary['total_sessions_scheduled'] += $scheduledSessions;
            $summary['total_sessions_taught'] += $sessionsTaught;
            $summary['total_sessions_missed'] += $sessionsMissed;
        }

        // Calculate averages
        if ($summary['total_teachers'] > 0) {
            $summary['avg_hours_per_teacher'] = round($summary['total_hours_per_week'] / $summary['total_teachers'], 1);
            $summary['avg_attendance_rate'] = $summary['total_sessions_scheduled'] > 0
                ? round(($summary['total_sessions_taught'] / $summary['total_sessions_scheduled']) * 100, 1)
                : 0;
        }

        return [
            'period' => [
                'month' => (int) $month,
                'year' => (int) $year,
                'month_name' => Carbon::create($year, $month, 1)->translatedFormat('F'),
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ],
            'filters' => [
                'subject' => $subjectFilter,
            ],
            'summary' => $summary,
            'data' => $teacherData,
        ];
    }

    /**
     * Calculate number of scheduled sessions in a date range
     */
    private function calculateScheduledSessions($schedules, Carbon $startDate, Carbon $endDate): int
    {
        $totalSessions = 0;

        foreach ($schedules as $schedule) {
            $effectiveFrom = Carbon::parse($schedule->effective_from);
            $effectiveUntil = $schedule->effective_until
                ? Carbon::parse($schedule->effective_until)
                : $endDate->copy();

            // Get overlap period
            $periodStart = $effectiveFrom->gt($startDate) ? $effectiveFrom : $startDate->copy();
            $periodEnd = $effectiveUntil->lt($endDate) ? $effectiveUntil : $endDate->copy();

            if ($periodStart->gt($periodEnd)) {
                continue;
            }

            // Count occurrences of the day_of_week in the period
            $dayOfWeek = $this->getDayOfWeekNumber($schedule->day_of_week);
            $current = $periodStart->copy();

            // Move to first occurrence
            while ($current->dayOfWeek !== $dayOfWeek && $current->lte($periodEnd)) {
                $current->addDay();
            }

            // Count occurrences
            while ($current->lte($periodEnd)) {
                $totalSessions++;
                $current->addWeek();
            }
        }

        return $totalSessions;
    }

    /**
     * Get teacher attendance stats for a period
     */
    private function getTeacherAttendanceStats(string $teacherId, Carbon $startDate, Carbon $endDate): array
    {
        $attendances = Attendance::where('employee_id', $teacherId)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        return [
            'present' => $attendances->where('status', 'present')->count(),
            'late' => $attendances->where('status', 'late')->count(),
            'absent' => $attendances->where('status', 'absent')->count(),
        ];
    }

    /**
     * Convert day name to Carbon day number
     */
    private function getDayOfWeekNumber(string $dayName): int
    {
        $days = [
            'sunday' => 0,
            'monday' => 1,
            'tuesday' => 2,
            'wednesday' => 3,
            'thursday' => 4,
            'friday' => 5,
            'saturday' => 6,
        ];

        return $days[strtolower($dayName)] ?? 1;
    }

    /**
     * Get all subjects with teaching schedules
     */
    public function getSubjectsWithSchedules(): array
    {
        return TeachingSchedule::where('is_active', true)
            ->with('subject')
            ->get()
            ->pluck('subject')
            ->filter()
            ->unique('id')
            ->map(fn($subject) => [
                'id' => $subject->id,
                'name' => $subject->name,
                'code' => $subject->code ?? null,
            ])
            ->values()
            ->toArray();
    }
}
