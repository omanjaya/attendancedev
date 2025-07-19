<?php

namespace App\Exports;

use App\Models\Attendance;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class AttendanceExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    use Exportable;

    private array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query()
    {
        $query = Attendance::query()
            ->with(['employee.user', 'employee.location']);

        if (isset($this->filters['employee_id'])) {
            $query->where('employee_id', $this->filters['employee_id']);
        }

        if (isset($this->filters['date_from'])) {
            $query->whereDate('date', '>=', $this->filters['date_from']);
        }

        if (isset($this->filters['date_to'])) {
            $query->whereDate('date', '<=', $this->filters['date_to']);
        }

        if (isset($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        return $query->orderBy('date', 'desc')->orderBy('check_in', 'desc');
    }

    public function headings(): array
    {
        return [
            'Employee ID',
            'Employee Name',
            'Department',
            'Date',
            'Check In',
            'Check Out',
            'Status',
            'Working Hours',
            'Overtime Hours',
            'Location',
            'Manual Entry',
            'Reason',
        ];
    }

    public function map($attendance): array
    {
        return [
            $attendance->employee->employee_id,
            $attendance->employee->full_name,
            $attendance->employee->department,
            Carbon::parse($attendance->date)->format('Y-m-d'),
            $attendance->check_in ? Carbon::parse($attendance->check_in)->format('H:i:s') : '-',
            $attendance->check_out ? Carbon::parse($attendance->check_out)->format('H:i:s') : '-',
            ucfirst($attendance->status),
            $attendance->working_hours ?? 0,
            $attendance->overtime_hours ?? 0,
            $attendance->employee->location->name ?? '-',
            $attendance->manual_entry ? 'Yes' : 'No',
            $attendance->manual_entry_reason ?? '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Header row styling
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E8F5E9'],
                ],
            ],
        ];
    }
}