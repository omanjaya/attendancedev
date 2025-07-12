<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AttendanceDashboardController extends Controller
{
    /**
     * Get dashboard data for Vue component
     */
    public function dashboard(): JsonResponse
    {
        try {
            // In a real application, you would fetch this data from the database
            // Example queries:
            // $presentToday = Attendance::whereDate('created_at', today())->where('status', 'present')->count();
            // $lateToday = Attendance::whereDate('created_at', today())->where('status', 'late')->count();
            // etc.

            $data = [
                'present_today' => 8,
                'late_today' => 5,
                'weekly_rate' => 92,
                'face_registrations' => [
                    'completed' => 0,
                    'total' => 12
                ],
                'employees_summary' => [
                    'total' => 13,
                    'present' => 8,
                    'late' => 5,
                    'absent' => 0
                ],
                'system_status' => [
                    'face_recognition' => 'active',
                    'database' => 'connected',
                    'last_backup' => '2 hours ago'
                ],
                'recent_activities' => [
                    [
                        'employee_name' => 'John Doe',
                        'action' => 'Check In',
                        'time' => '09:15 AM',
                        'status' => 'on_time'
                    ],
                    [
                        'employee_name' => 'Jane Smith', 
                        'action' => 'Check In',
                        'time' => '09:30 AM',
                        'status' => 'late'
                    ]
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch dashboard data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get real-time stats
     */
    public function stats(): JsonResponse
    {
        try {
            $stats = [
                'present_today' => 8,
                'late_today' => 5,
                'absent_today' => 0,
                'total_employees' => 13,
                'weekly_rate' => 92,
                'monthly_rate' => 89,
                'face_registration_rate' => 0
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch stats',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}