<?php

namespace App\Services\Reports;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\Report;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AttendanceReportExport;

class ReportExportService
{
    /**
     * Generate report (sync or async based on size)
     */
    public function generate(array $validated, array $filters, int $userId): array
    {
        $reportType = $validated['type'];
        $format = $validated['format'];
        $selectedColumns = $filters['filters']['columns'] ?? [];

        // Check cache for identical recent exports
        $cacheKey = 'report:' . md5(json_encode($validated) . $userId);
        $cachedReport = Cache::get($cacheKey);

        if ($cachedReport && file_exists(storage_path('app/public/' . str_replace('storage/', '', $cachedReport['file_path'])))) {
            Log::info('Returning cached report', ['user_id' => $userId, 'cache_key' => $cacheKey]);
            return [
                'cached' => true,
                'report' => $cachedReport['report'],
                'download_url' => $cachedReport['download_url'],
                'expires_at' => $cachedReport['expires_at'],
            ];
        }

        // Estimate dataset size
        $estimatedRows = $this->estimateRowCount($reportType, $validated['start_date'], $validated['end_date']);

        Log::info('Report generation requested', [
            'user_id' => $userId,
            'type' => $reportType,
            'format' => $format,
            'estimated_rows' => $estimatedRows
        ]);

        // Smart routing: Auto-queue for large exports
        if ($estimatedRows > 1000) {
            return $this->generateAsync($validated, $reportType, $format, $selectedColumns, $filters, $userId);
        }

        // Synchronous generation for small datasets
        return $this->generateSync($validated, $reportType, $format, $selectedColumns, $filters, $cacheKey, $userId);
    }

    /**
     * Generate report synchronously (for small datasets)
     */
    private function generateSync($validated, $reportType, $format, $selectedColumns, $filters, $cacheKey, $userId): array
    {
        // Set execution limits
        set_time_limit(60);
        ini_set('memory_limit', '256M');

        try {
            // Fetch Data
            $reportData = $this->getReportData($reportType, $validated['start_date'], $validated['end_date'], $selectedColumns);

            $data = $reportData['data'];
            $headings = $reportData['headings'];
            $columns = $reportData['columns'];

            // Generate File
            $extension = $format === 'pdf' ? 'pdf' : 'xlsx';
            $writerType = $format === 'pdf' ? \Maatwebsite\Excel\Excel::DOMPDF : \Maatwebsite\Excel\Excel::XLSX;

            $filename = "{$reportType}_" . now()->format('Ymd_His') . ".{$extension}";
            $path = "exports/{$filename}";

            // Ensure directory exists
            if (!file_exists(storage_path('app/public/exports'))) {
                mkdir(storage_path('app/public/exports'), 0755, true);
            }

            // Store file
            Excel::store(
                new AttendanceReportExport($data, $headings, $columns),
                $path,
                'public',
                $writerType
            );

            // Save to Database
            $report = Report::create([
                'type' => $reportType,
                'format' => $format,
                'filename' => $filename,
                'file_path' => "storage/{$path}",
                'status' => 'completed',
                'filters' => $filters,
                'generated_by' => $userId,
                'expires_at' => now()->addDays(7),
            ]);

            $appUrl = config('app.url');
            $downloadUrl = "{$appUrl}/storage/{$path}";

            // Cache the result (5 minutes)
            Cache::put($cacheKey, [
                'report' => $report,
                'file_path' => $report->file_path,
                'download_url' => $downloadUrl,
                'expires_at' => $report->expires_at
            ], 300);

            Log::info('Report generated successfully (sync)', [
                'user_id' => $userId,
                'report_id' => $report->id,
                'rows' => count($data)
            ]);

            return [
                'cached' => false,
                'generated_sync' => true,
                'report' => $report,
                'download_url' => $downloadUrl,
                'expires_at' => $report->expires_at,
            ];

        } catch (\Exception $e) {
            Log::error('Sync report generation failed', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Generate report asynchronously (for large datasets)
     */
    private function generateAsync($validated, $reportType, $format, $selectedColumns, $filters, $userId): array
    {
        // Create pending report record
        $report = Report::create([
            'type' => $reportType,
            'format' => $format,
            'status' => 'pending',
            'filters' => $filters,
            'generated_by' => $userId,
            'expires_at' => now()->addDays(7),
        ]);

        // Dispatch queue job
        \App\Jobs\GenerateReportJob::dispatch(
            $report,
            $reportType,
            $validated['start_date'],
            $validated['end_date'],
            $selectedColumns,
            $format
        );

        Log::info('Report queued for async generation', [
            'user_id' => $userId,
            'report_id' => $report->id
        ]);

        return [
            'generated_async' => true,
            'status' => 'pending',
            'report' => $report,
            'message' => 'Large export queued for processing. You will be notified when ready.',
        ];
    }

    /**
     * Estimate number of rows for smart routing
     */
    private function estimateRowCount(string $type, string $startDate, string $endDate): int
    {
        switch ($type) {
            case 'attendance':
                return Attendance::whereBetween('date', [$startDate, $endDate])->count();
            case 'leave':
                return Leave::whereBetween('start_date', [$startDate, $endDate])->count();
            case 'payroll':
                return Employee::where('is_active', true)->count();
            default:
                return 0;
        }
    }

    /**
     * Get report data by type
     */
    private function getReportData(string $type, string $startDate, string $endDate, array $selectedColumns): array
    {
        switch ($type) {
            case 'leave':
                return $this->getLeaveReportData($startDate, $endDate, $selectedColumns);
            case 'payroll':
                return $this->getPayrollReportData($startDate, $endDate, $selectedColumns);
            case 'attendance':
            default:
                return $this->getAttendanceReportData($startDate, $endDate, $selectedColumns);
        }
    }

    /**
     * Get attendance report data
     */
    private function getAttendanceReportData(string $startDate, string $endDate, array $selectedColumns): array
    {
        $columnMap = [
            'employee_name' => 'Nama Karyawan',
            'employee_code' => 'NIK',
            'date' => 'Tanggal',
            'check_in' => 'Jam Masuk',
            'check_out' => 'Jam Pulang',
            'status' => 'Status',
            'work_hours' => 'Jam Kerja',
            'late_duration' => 'Terlambat (Menit)',
            'notes' => 'Catatan',
        ];

        if (empty($selectedColumns)) {
            $selectedColumns = ['employee_name', 'date', 'check_in', 'check_out', 'status', 'work_hours'];
        }

        $validColumns = array_intersect($selectedColumns, array_keys($columnMap));
        $headings = array_map(fn($col) => $columnMap[$col], $validColumns);

        // Fetch attendance records
        $attendances = Attendance::with('employee')
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->orderBy('employee_id')
            ->get();

        $data = [];
        foreach ($attendances as $att) {
            $row = [
                'employee_name' => $att->employee->full_name ?? 'Unknown',
                'employee_code' => $att->employee->employee_code ?? '-',
                'date' => $att->date,
                'check_in' => $att->check_in_time ? Carbon::parse($att->check_in_time)->format('H:i:s') : '-',
                'check_out' => $att->check_out_time ? Carbon::parse($att->check_out_time)->format('H:i:s') : '-',
                'status' => ucfirst($att->status),
                'work_hours' => $att->total_hours ? round($att->total_hours, 2) : 0,
                'late_duration' => $att->late_duration_minutes ?? 0,
                'notes' => $att->notes ?? '-',
            ];
            $data[] = $row;
        }

        return [
            'data' => $data,
            'headings' => $headings,
            'columns' => $validColumns
        ];
    }

    /**
     * Get leave report data
     */
    private function getLeaveReportData(string $startDate, string $endDate, array $selectedColumns): array
    {
        $columnMap = [
            'employee_name' => 'Nama Karyawan',
            'employee_code' => 'NIK',
            'leave_type' => 'Tipe Cuti',
            'start_date' => 'Mulai',
            'end_date' => 'Selesai',
            'duration' => 'Durasi (Hari)',
            'reason' => 'Alasan',
            'status' => 'Status',
            'approved_by' => 'Disetujui Oleh',
        ];

        if (empty($selectedColumns)) {
            $selectedColumns = ['employee_name', 'leave_type', 'start_date', 'end_date', 'duration', 'status'];
        }

        $validColumns = array_intersect($selectedColumns, array_keys($columnMap));
        $headings = array_map(fn($col) => $columnMap[$col], $validColumns);

        $leaves = Leave::with(['employee', 'leaveType', 'approver'])
            ->whereBetween('start_date', [$startDate, $endDate])
            ->orderBy('start_date')
            ->get();

        $data = [];
        foreach ($leaves as $leave) {
            $start = Carbon::parse($leave->start_date);
            $end = Carbon::parse($leave->end_date);
            $duration = $start->diffInDays($end) + 1;

            $data[] = [
                'employee_name' => $leave->employee->full_name ?? 'Unknown',
                'employee_code' => $leave->employee->employee_code ?? '-',
                'leave_type' => $leave->leaveType->name ?? '-',
                'start_date' => $leave->start_date,
                'end_date' => $leave->end_date,
                'duration' => $duration,
                'reason' => $leave->reason,
                'status' => ucfirst($leave->status),
                'approved_by' => $leave->approver->name ?? '-',
            ];
        }

        return [
            'data' => $data,
            'headings' => $headings,
            'columns' => $validColumns
        ];
    }

    /**
     * Get payroll report data
     */
    private function getPayrollReportData(string $startDate, string $endDate, array $selectedColumns): array
    {
        $columnMap = [
            'employee_name' => 'Nama Karyawan',
            'employee_code' => 'NIK',
            'period' => 'Periode',
            'basic_salary' => 'Gaji Pokok',
            'allowances' => 'Tunjangan',
            'deductions' => 'Potongan',
            'net_salary' => 'Gaji Bersih',
            'status' => 'Status',
        ];

        if (empty($selectedColumns)) {
            $selectedColumns = ['employee_name', 'period', 'basic_salary', 'net_salary', 'status'];
        }

        $validColumns = array_intersect($selectedColumns, array_keys($columnMap));
        $headings = array_map(fn($col) => $columnMap[$col], $validColumns);

        $data = [];

        // Check if PayrollRecord model and table exist
        try {
            if (class_exists(\App\Models\PayrollRecord::class)) {
                $payrollRecords = \App\Models\PayrollRecord::with('employee')
                    ->whereBetween('payment_date', [$startDate, $endDate])
                    ->orderBy('payment_date', 'desc')
                    ->get();

                foreach ($payrollRecords as $record) {
                    $data[] = [
                        'employee_name' => $record->employee->full_name ?? 'Unknown',
                        'employee_code' => $record->employee->employee_code ?? '-',
                        'period' => $record->period ? Carbon::parse($record->period)->format('M Y') : Carbon::parse($record->payment_date)->format('M Y'),
                        'basic_salary' => $record->basic_salary ?? 0,
                        'allowances' => $record->allowances ?? 0,
                        'deductions' => $record->deductions ?? 0,
                        'net_salary' => $record->net_salary ?? 0,
                        'status' => ucfirst($record->status ?? 'pending'),
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::warning('Payroll records not available', [
                'error' => $e->getMessage(),
                'start_date' => $startDate,
                'end_date' => $endDate
            ]);
        }

        if (empty($data)) {
            Log::info('No payroll records found for date range', [
                'start_date' => $startDate,
                'end_date' => $endDate
            ]);
        }

        return [
            'data' => $data,
            'headings' => $headings,
            'columns' => $validColumns
        ];
    }

    /**
     * Get list of generated reports for a user
     */
    public function getGeneratedReports(int $userId): array
    {
        return Report::where('generated_by', $userId)
            ->where('expires_at', '>', now())
            ->latest()
            ->take(20)
            ->get()
            ->toArray();
    }

    /**
     * Get specific generated report
     */
    public function getGeneratedReport(string $id, int $userId): ?Report
    {
        $report = Report::where('id', $id)
            ->where('generated_by', $userId)
            ->first();

        if ($report && file_exists(storage_path('app/public/' . str_replace('storage/', '', $report->file_path)))) {
            $report->download_url = url($report->file_path);
        }

        return $report;
    }
}
