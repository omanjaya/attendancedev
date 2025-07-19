<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class AttendanceExportTemplate implements FromArray, WithHeadings, WithStyles, WithColumnWidths, WithTitle
{
    public function array(): array
    {
        return [
            [
                'EMP001',
                '2025-01-20',
                '08:00',
                '17:00',
                'present',
                '9.0',
                'Regular working day',
                'Bulk import example'
            ],
            [
                'EMP002',
                '2025-01-20',
                '08:30',
                '17:30',
                'late',
                '9.0',
                'Late arrival',
                'Traffic jam'
            ],
            [
                'EMP003',
                '2025-01-20',
                '09:00',
                '',
                'incomplete',
                '',
                'Forgot to check out',
                'System issue'
            ]
        ];
    }

    public function headings(): array
    {
        return [
            'Employee ID',
            'Date',
            'Check In',
            'Check Out', 
            'Status',
            'Working Hours',
            'Notes',
            'Reason'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Header styling
        $sheet->getStyle('A1:H1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4F46E5'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);

        // Data styling
        $sheet->getStyle('A2:H4')->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Add data validation and comments
        $sheet->getComment('A2')->getText()->createTextRun('Employee ID must exist in system');
        $sheet->getComment('B2')->getText()->createTextRun('Format: YYYY-MM-DD');
        $sheet->getComment('C2')->getText()->createTextRun('Format: HH:MM (24-hour)');
        $sheet->getComment('D2')->getText()->createTextRun('Format: HH:MM (24-hour), leave empty if incomplete');
        $sheet->getComment('E2')->getText()->createTextRun('Options: present, absent, late, early_departure, incomplete');
        $sheet->getComment('F2')->getText()->createTextRun('Decimal hours (e.g., 8.5 for 8 hours 30 minutes)');
        $sheet->getComment('G2')->getText()->createTextRun('Optional notes for the attendance record');
        $sheet->getComment('H2')->getText()->createTextRun('Reason for manual entry (required)');

        return $sheet;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15, // Employee ID
            'B' => 12, // Date
            'C' => 10, // Check In
            'D' => 10, // Check Out
            'E' => 15, // Status
            'F' => 15, // Working Hours
            'G' => 25, // Notes
            'H' => 20, // Reason
        ];
    }

    public function title(): string
    {
        return 'Attendance Import Template';
    }
}