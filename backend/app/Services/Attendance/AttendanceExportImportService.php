<?php

namespace App\Services\Attendance;

use App\Models\Attendance;
use App\Imports\AttendanceImport;
use App\Exports\AttendanceExportTemplate;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Facades\Excel;

class AttendanceExportImportService
{
    /**
     * Export attendance data to CSV
     */
    public function exportToCSV(Builder $query)
    {
        $attendances = $query->get();

        // Create CSV content
        $csvData = [];
        $csvData[] = [
            'Date',
            'Employee Name',
            'Employee ID',
            'Check In Time',
            'Check Out Time',
            'Total Hours',
            'Status',
            'Location Verified',
            'Check In Confidence',
            'Check Out Confidence',
            'Notes',
        ];

        foreach ($attendances as $attendance) {
            $csvData[] = [
                $attendance->date->format('Y-m-d'),
                $attendance->employee->full_name,
                $attendance->employee->employee_id,
                $attendance->check_in_time?->format('Y-m-d H:i:s') ?? '',
                $attendance->check_out_time?->format('Y-m-d H:i:s') ?? '',
                $attendance->total_hours ?? 0,
                ucfirst(str_replace('_', ' ', $attendance->status)),
                $attendance->location_verified ? 'Yes' : 'No',
                $attendance->check_in_confidence
                ? round($attendance->check_in_confidence * 100, 1) . '%'
                : '',
                $attendance->check_out_confidence
                ? round($attendance->check_out_confidence * 100, 1) . '%'
                : '',
                trim(($attendance->check_in_notes ?? '') . ' ' . ($attendance->check_out_notes ?? '')),
            ];
        }

        // Generate filename
        $filename = 'attendance_export_' . now()->format('Y-m-d_H-i-s') . '.csv';

        // Create response
        return response()->streamDownload(
            function () use ($csvData) {
                $handle = fopen('php://output', 'w');

                foreach ($csvData as $row) {
                    fputcsv($handle, $row);
                }

                fclose($handle);
            },
            $filename,
            [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ],
        );
    }

    /**
     * Download import template
     */
    public function downloadTemplate(string $format = 'excel')
    {
        if ($format === 'excel') {
            return Excel::download(new AttendanceExportTemplate(), 'attendance_import_template.xlsx');
        } else {
            // Generate CSV template
            $csvData = [
                ['Employee ID', 'Date', 'Check In', 'Check Out', 'Status', 'Working Hours', 'Notes', 'Reason'],
                ['EMP001', '2025-01-20', '08:00', '17:00', 'present', '9.0', 'Regular working day', 'Bulk import example'],
                ['EMP002', '2025-01-20', '08:30', '17:30', 'late', '9.0', 'Late arrival', 'Traffic jam'],
                ['EMP003', '2025-01-20', '09:00', '', 'incomplete', '', 'Forgot to check out', 'System issue']
            ];

            return response()->streamDownload(
                function () use ($csvData) {
                    $handle = fopen('php://output', 'w');
                    foreach ($csvData as $row) {
                        fputcsv($handle, $row);
                    }
                    fclose($handle);
                },
                'attendance_import_template.csv',
                ['Content-Type' => 'text/csv']
            );
        }
    }

    /**
     * Import attendance data from file
     */
    public function importAttendance($file, array $options = []): array
    {
        $import = new AttendanceImport($options);
        Excel::import($import, $file);

        $results = $import->getResults();

        $message = "Import completed! {$results['success']} records imported successfully.";

        if ($results['skipped'] > 0) {
            $message .= " {$results['skipped']} records skipped.";
        }

        if (count($results['errors']) > 0) {
            $message .= " " . count($results['errors']) . " errors occurred.";
        }

        return [
            'success' => true,
            'message' => $message,
            'data' => [
                'summary' => [
                    'total_processed' => $results['success'] + $results['skipped'] + count($results['errors']),
                    'successful' => $results['success'],
                    'skipped' => $results['skipped'],
                    'failed' => count($results['errors']),
                ],
                'errors' => $results['errors'],
                'warnings' => $results['warnings'] ?? []
            ]
        ];
    }
}
