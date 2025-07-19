<?php

namespace App\Imports;

use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\Importable;

class AttendanceImport implements ToCollection, WithHeadingRow, WithValidation
{
    use Importable;

    private $results = [
        'success' => 0,
        'errors' => [],
        'skipped' => 0,
        'warnings' => []
    ];

    private $options = [];

    public function __construct(array $options = [])
    {
        $this->options = array_merge([
            'skip_duplicates' => true,
            'update_existing' => false,
            'default_status' => 'present',
            'validate_employees' => true,
        ], $options);
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            try {
                $this->processRow($row->toArray(), $index + 2); // +2 for header and 0-based index
            } catch (\Exception $e) {
                $this->results['errors'][] = [
                    'row' => $index + 2,
                    'message' => $e->getMessage(),
                    'data' => $row->toArray()
                ];
            }
        }
    }

    private function processRow(array $row, int $rowNumber)
    {
        // Map and validate row data
        $mappedData = $this->mapRowData($row);
        $validation = $this->validateRowData($mappedData, $rowNumber);
        
        if (!$validation['valid']) {
            $this->results['errors'][] = [
                'row' => $rowNumber,
                'message' => implode(', ', $validation['errors']),
                'data' => $row
            ];
            return;
        }

        // Check for employee existence
        $employee = Employee::where('employee_id', $mappedData['employee_id'])->first();
        if (!$employee && $this->options['validate_employees']) {
            $this->results['errors'][] = [
                'row' => $rowNumber,
                'message' => "Employee with ID '{$mappedData['employee_id']}' not found",
                'data' => $row
            ];
            return;
        }

        // Check for existing attendance
        $existing = Attendance::where('employee_id', $employee->id)
            ->whereDate('date', $mappedData['date'])
            ->first();

        if ($existing) {
            if ($this->options['skip_duplicates']) {
                $this->results['skipped']++;
                $this->results['warnings'][] = [
                    'row' => $rowNumber,
                    'message' => "Attendance for employee '{$mappedData['employee_id']}' on date '{$mappedData['date']}' already exists - skipped",
                ];
                return;
            } elseif ($this->options['update_existing']) {
                $this->updateExistingAttendance($existing, $mappedData);
                $this->results['success']++;
                return;
            } else {
                $this->results['errors'][] = [
                    'row' => $rowNumber,
                    'message' => "Attendance already exists for employee '{$mappedData['employee_id']}' on date '{$mappedData['date']}'",
                    'data' => $row
                ];
                return;
            }
        }

        // Create new attendance record
        $this->createAttendance($employee, $mappedData);
        $this->results['success']++;
    }

    private function mapRowData(array $row): array
    {
        return [
            'employee_id' => $row['employee_id'] ?? $row['id_karyawan'] ?? null,
            'date' => $this->parseDate($row['date'] ?? $row['tanggal'] ?? null),
            'check_in_time' => $this->parseTime($row['check_in'] ?? $row['masuk'] ?? null),
            'check_out_time' => $this->parseTime($row['check_out'] ?? $row['keluar'] ?? null),
            'status' => strtolower($row['status'] ?? $this->options['default_status']),
            'notes' => $row['notes'] ?? $row['catatan'] ?? null,
            'is_manual_entry' => true,
            'manual_entry_reason' => $row['reason'] ?? $row['alasan'] ?? 'Bulk import',
            'working_hours' => $this->parseFloat($row['working_hours'] ?? $row['jam_kerja'] ?? null),
        ];
    }

    private function validateRowData(array $data, int $rowNumber): array
    {
        $validator = Validator::make($data, [
            'employee_id' => 'required|string',
            'date' => 'required|date',
            'check_in_time' => 'nullable|date_format:H:i:s',
            'check_out_time' => 'nullable|date_format:H:i:s|after:check_in_time',
            'status' => 'in:present,absent,late,early_departure,incomplete',
            'working_hours' => 'nullable|numeric|min:0|max:24',
        ]);

        if ($validator->fails()) {
            return [
                'valid' => false,
                'errors' => $validator->errors()->all()
            ];
        }

        return ['valid' => true, 'errors' => []];
    }

    private function createAttendance(Employee $employee, array $data)
    {
        $attendanceData = [
            'employee_id' => $employee->id,
            'date' => $data['date'],
            'status' => $data['status'],
            'is_manual_entry' => true,
            'manual_entry_reason' => $data['manual_entry_reason'],
            'manual_entry_by' => auth()->id(),
            'notes' => $data['notes'] ? ['import_notes' => $data['notes']] : null,
        ];

        // Add check-in data
        if ($data['check_in_time']) {
            $attendanceData['check_in'] = Carbon::parse($data['date'] . ' ' . $data['check_in_time']);
            $attendanceData['check_in_time'] = $attendanceData['check_in'];
        }

        // Add check-out data
        if ($data['check_out_time']) {
            $attendanceData['check_out'] = Carbon::parse($data['date'] . ' ' . $data['check_out_time']);
            $attendanceData['check_out_time'] = $attendanceData['check_out'];
        }

        // Calculate working hours if not provided
        if ($data['working_hours']) {
            $attendanceData['working_hours'] = $data['working_hours'];
            $attendanceData['total_hours'] = $data['working_hours'];
        } elseif ($attendanceData['check_in'] && $attendanceData['check_out']) {
            $workingHours = $attendanceData['check_out']->diffInMinutes($attendanceData['check_in']) / 60;
            $attendanceData['working_hours'] = $workingHours;
            $attendanceData['total_hours'] = $workingHours;
        }

        Attendance::create($attendanceData);
    }

    private function updateExistingAttendance(Attendance $attendance, array $data)
    {
        $updateData = [
            'is_manual_entry' => true,
            'manual_entry_reason' => $data['manual_entry_reason'] . ' (Updated via import)',
            'updated_by' => auth()->id(),
        ];

        if ($data['check_in_time']) {
            $updateData['check_in'] = Carbon::parse($data['date'] . ' ' . $data['check_in_time']);
            $updateData['check_in_time'] = $updateData['check_in'];
        }

        if ($data['check_out_time']) {
            $updateData['check_out'] = Carbon::parse($data['date'] . ' ' . $data['check_out_time']);
            $updateData['check_out_time'] = $updateData['check_out'];
        }

        if ($data['status']) {
            $updateData['status'] = $data['status'];
        }

        if ($data['working_hours']) {
            $updateData['working_hours'] = $data['working_hours'];
            $updateData['total_hours'] = $data['working_hours'];
        } elseif (isset($updateData['check_in']) && isset($updateData['check_out'])) {
            $workingHours = $updateData['check_out']->diffInMinutes($updateData['check_in']) / 60;
            $updateData['working_hours'] = $workingHours;
            $updateData['total_hours'] = $workingHours;
        }

        $attendance->update($updateData);
    }

    private function parseDate($date): ?string
    {
        if (empty($date)) {
            return null;
        }

        try {
            return Carbon::parse($date)->format('Y-m-d');
        } catch (\Exception $e) {
            throw new \Exception("Invalid date format: {$date}");
        }
    }

    private function parseTime($time): ?string
    {
        if (empty($time)) {
            return null;
        }

        try {
            return Carbon::parse($time)->format('H:i:s');
        } catch (\Exception $e) {
            throw new \Exception("Invalid time format: {$time}");
        }
    }

    private function parseFloat($value): ?float
    {
        if (empty($value)) {
            return null;
        }

        return (float) str_replace(',', '.', $value);
    }

    public function rules(): array
    {
        return [
            '*.employee_id' => 'required|string',
            '*.date' => 'required|date',
            '*.check_in' => 'nullable|date_format:H:i',
            '*.check_out' => 'nullable|date_format:H:i',
        ];
    }

    public function getResults(): array
    {
        return $this->results;
    }
}