<?php

namespace App\Imports;

use App\Models\Leave;
use App\Models\Employee;
use App\Models\LeaveType;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\Importable;

class LeaveImport implements ToCollection, WithHeadingRow, WithValidation
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
            'auto_approve' => false,
            'default_status' => 'pending',
            'validate_employees' => true,
            'validate_balance' => true,
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

        // Check for leave type existence
        $leaveType = LeaveType::where('name', $mappedData['leave_type'])->first();
        if (!$leaveType) {
            $this->results['errors'][] = [
                'row' => $rowNumber,
                'message' => "Leave type '{$mappedData['leave_type']}' not found",
                'data' => $row
            ];
            return;
        }

        // Check for existing leave request
        $existing = Leave::where('employee_id', $employee->id)
            ->whereBetween('start_date', [$mappedData['start_date'], $mappedData['end_date']])
            ->orWhereBetween('end_date', [$mappedData['start_date'], $mappedData['end_date']])
            ->first();

        if ($existing) {
            if ($this->options['skip_duplicates']) {
                $this->results['skipped']++;
                $this->results['warnings'][] = [
                    'row' => $rowNumber,
                    'message' => "Leave request for employee '{$mappedData['employee_id']}' overlaps with existing request - skipped",
                ];
                return;
            } else {
                $this->results['errors'][] = [
                    'row' => $rowNumber,
                    'message' => "Overlapping leave request exists for employee '{$mappedData['employee_id']}'",
                    'data' => $row
                ];
                return;
            }
        }

        // Validate leave balance if required
        if ($this->options['validate_balance']) {
            $balanceCheck = $this->validateLeaveBalance($employee, $leaveType, $mappedData['days_requested']);
            if (!$balanceCheck['valid']) {
                $this->results['errors'][] = [
                    'row' => $rowNumber,
                    'message' => $balanceCheck['message'],
                    'data' => $row
                ];
                return;
            }
        }

        // Create new leave request
        $this->createLeaveRequest($employee, $leaveType, $mappedData);
        $this->results['success']++;
    }

    private function mapRowData(array $row): array
    {
        $startDate = $this->parseDate($row['start_date'] ?? $row['tanggal_mulai'] ?? null);
        $endDate = $this->parseDate($row['end_date'] ?? $row['tanggal_selesai'] ?? null);
        
        // Calculate days if not provided
        $daysRequested = null;
        if (isset($row['days']) || isset($row['hari'])) {
            $daysRequested = (int) ($row['days'] ?? $row['hari']);
        } elseif ($startDate && $endDate) {
            $daysRequested = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1;
        }

        return [
            'employee_id' => $row['employee_id'] ?? $row['id_karyawan'] ?? null,
            'leave_type' => $row['leave_type'] ?? $row['jenis_cuti'] ?? 'annual',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'days_requested' => $daysRequested,
            'reason' => $row['reason'] ?? $row['alasan'] ?? '',
            'status' => strtolower($row['status'] ?? $this->options['default_status']),
            'applied_at' => $this->parseDate($row['applied_date'] ?? $row['tanggal_pengajuan'] ?? null) ?? now()->format('Y-m-d'),
            'notes' => $row['notes'] ?? $row['catatan'] ?? null,
            'emergency' => $this->parseBoolean($row['emergency'] ?? $row['darurat'] ?? false),
            'half_day' => $this->parseBoolean($row['half_day'] ?? $row['setengah_hari'] ?? false),
        ];
    }

    private function validateRowData(array $data, int $rowNumber): array
    {
        $validator = Validator::make($data, [
            'employee_id' => 'required|string',
            'leave_type' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'days_requested' => 'required|integer|min:1|max:365',
            'reason' => 'required|string|min:10|max:1000',
            'status' => 'in:pending,approved,rejected,cancelled',
            'applied_at' => 'required|date',
        ]);

        if ($validator->fails()) {
            return [
                'valid' => false,
                'errors' => $validator->errors()->all()
            ];
        }

        // Additional business rule validations
        $startDate = Carbon::parse($data['start_date']);
        $endDate = Carbon::parse($data['end_date']);
        $appliedDate = Carbon::parse($data['applied_at']);

        // Check if applied date is not in the future (unless it's a backdated import)
        if ($appliedDate->isFuture() && !($this->options['allow_future_applications'] ?? false)) {
            return [
                'valid' => false,
                'errors' => ['Applied date cannot be in the future']
            ];
        }

        // Check if leave dates are reasonable (not too far in the past or future)
        if ($startDate->isPast() && $startDate->diffInMonths(now()) > 12) {
            return [
                'valid' => false,
                'errors' => ['Leave start date is too far in the past (more than 12 months)']
            ];
        }

        if ($startDate->isFuture() && $startDate->diffInMonths(now()) > 24) {
            return [
                'valid' => false,
                'errors' => ['Leave start date is too far in the future (more than 24 months)']
            ];
        }

        return ['valid' => true, 'errors' => []];
    }

    private function createLeaveRequest(Employee $employee, LeaveType $leaveType, array $data)
    {
        $leaveData = [
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'days_requested' => $data['days_requested'],
            'reason' => $data['reason'],
            'status' => $data['status'],
            'applied_at' => $data['applied_at'],
            'emergency' => $data['emergency'],
            'half_day' => $data['half_day'],
            'metadata' => [
                'imported' => true,
                'imported_at' => now()->toISOString(),
                'imported_by' => auth()->id(),
                'notes' => $data['notes']
            ]
        ];

        // Set approval data if status is approved
        if ($data['status'] === 'approved') {
            $leaveData['approved_at'] = $data['applied_at']; // Use applied date as approval date
            $leaveData['approved_by'] = auth()->id();
            $leaveData['days_approved'] = $data['days_requested'];
        }

        // Set rejection data if status is rejected
        if ($data['status'] === 'rejected') {
            $leaveData['rejected_at'] = $data['applied_at'];
            $leaveData['rejected_by'] = auth()->id();
            $leaveData['rejection_reason'] = $data['notes'] ?? 'Imported as rejected';
        }

        Leave::create($leaveData);
    }

    private function validateLeaveBalance(Employee $employee, LeaveType $leaveType, int $daysRequested): array
    {
        // Get current leave balance for the employee and leave type
        $currentYear = now()->year;
        $usedDays = Leave::where('employee_id', $employee->id)
            ->where('leave_type_id', $leaveType->id)
            ->where('status', 'approved')
            ->whereYear('start_date', $currentYear)
            ->sum('days_approved');

        $totalAllowance = $leaveType->days_per_year ?? 12; // Default 12 days annual leave
        $remainingDays = $totalAllowance - $usedDays;

        if ($daysRequested > $remainingDays) {
            return [
                'valid' => false,
                'message' => "Insufficient leave balance. Requested: {$daysRequested} days, Available: {$remainingDays} days"
            ];
        }

        return ['valid' => true, 'message' => ''];
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

    private function parseBoolean($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $stringValue = strtolower(trim((string) $value));
        return in_array($stringValue, ['1', 'true', 'yes', 'ya', 'y']);
    }

    public function rules(): array
    {
        return [
            '*.employee_id' => 'required|string',
            '*.leave_type' => 'required|string',
            '*.start_date' => 'required|date',
            '*.end_date' => 'required|date',
            '*.reason' => 'required|string|min:10',
        ];
    }

    public function getResults(): array
    {
        return $this->results;
    }
}