<?php

namespace App\Services\Dashboard;

use App\Models\Attendance;
use App\Models\Leave;

class DashboardDataService
{
    /**
     * Get recent activities for dashboard
     */
    public function getRecentActivities(): array
    {
        $recentAttendances = Attendance::with('employee.user')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $activities = [];

        foreach ($recentAttendances as $attendance) {
            if ($attendance->check_in_time) {
                $activities[] = [
                    'id' => $attendance->id.'_checkin',
                    'type' => 'check-in',
                    'user' => ['name' => $attendance->employee->first_name.' '.$attendance->employee->last_name],
                    'description' => 'checked in',
                    'details' => $attendance->status === 'late' ? 'Late arrival' : 'On time',
                    'location' => $attendance->location ?? 'Unknown',
                    'device' => 'Attendance System',
                    'status' => $attendance->status === 'late' ? 'warning' : 'success',
                    'timestamp' => $attendance->check_in_time?->toISOString(),
                ];
            }

            if ($attendance->check_out_time) {
                $activities[] = [
                    'id' => $attendance->id.'_checkout',
                    'type' => 'check-out',
                    'user' => ['name' => $attendance->employee->first_name.' '.$attendance->employee->last_name],
                    'description' => 'checked out',
                    'details' => 'Working hours: '.($attendance->total_hours ? round($attendance->total_hours, 1).'h' : 'N/A'),
                    'location' => $attendance->location ?? 'Unknown',
                    'status' => 'success',
                    'timestamp' => $attendance->check_out_time?->toISOString(),
                ];
            }
        }

        // Add recent leave requests
        $recentLeaves = Leave::with('employee.user')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        foreach ($recentLeaves as $leave) {
            $activities[] = [
                'id' => 'leave_'.$leave->id,
                'type' => 'leave-request',
                'user' => ['name' => $leave->employee->first_name.' '.$leave->employee->last_name],
                'description' => 'submitted a leave request',
                'details' => ucfirst($leave->leave_type).' leave for '.$leave->start_date->diffInDays($leave->end_date) + 1 .' day(s)',
                'status' => $leave->status,
                'timestamp' => $leave->created_at->toISOString(),
            ];
        }

        // Sort by timestamp and return latest 10
        usort($activities, function ($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });

        return array_slice($activities, 0, 10);
    }

    /**
     * Get today's schedule for user
     */
    public function getTodaySchedule($user): array
    {
        $today = now()->format('Y-m-d');
        $employee = $user->employee;

        if (! $employee) {
            return [];
        }

        // Get today's schedule for the employee
        $schedules = [];

        // Check if there's a monthly schedule assigned
        $monthlySchedule = $employee->employeeSchedules()
            ->whereDate('date', $today)
            ->first();

        if ($monthlySchedule) {
            $schedules[] = [
                'id' => $monthlySchedule->id,
                'type' => 'work',
                'title' => 'Work Schedule',
                'time' => $monthlySchedule->start_time->format('H:i').' - '.$monthlySchedule->end_time->format('H:i'),
                'location' => $monthlySchedule->location->name ?? 'N/A',
                'status' => 'scheduled',
            ];
        }

        // Get teaching schedules if employee is a teacher
        if ($employee->employee_type === 'teacher') {
            $dayOfWeek = now()->format('l');
            $teachingSchedules = $employee->weeklySchedules()
                ->where('day_of_week', strtolower($dayOfWeek))
                ->with(['subject', 'academicClass', 'timeSlot'])
                ->get();

            foreach ($teachingSchedules as $schedule) {
                $schedules[] = [
                    'id' => 'teaching_'.$schedule->id,
                    'type' => 'teaching',
                    'title' => $schedule->subject->name.' - '.$schedule->academicClass->name,
                    'time' => $schedule->timeSlot->start_time->format('H:i').' - '.$schedule->timeSlot->end_time->format('H:i'),
                    'location' => $schedule->room ?? 'N/A',
                    'status' => 'scheduled',
                ];
            }
        }

        return $schedules;
    }
}
