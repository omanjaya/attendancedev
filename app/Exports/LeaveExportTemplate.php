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

class LeaveExportTemplate implements FromArray, WithHeadings, WithStyles, WithColumnWidths, WithTitle
{
    public function array(): array
    {
        return [
            [
                'EMP001',
                'annual',
                '2025-02-01',
                '2025-02-05',
                5,
                'Family vacation planned for months',
                'pending',
                '2025-01-15',
                'Personal time off',
                'false',
                'false'
            ],
            [
                'EMP002',
                'sick',
                '2025-01-22',
                '2025-01-24',
                3,
                'Medical treatment required for back injury',
                'approved',
                '2025-01-21',
                'Medical certificate provided',
                'true',
                'false'
            ],
            [
                'EMP003',
                'maternity',
                '2025-03-01',
                '2025-05-30',
                90,
                'Maternity leave for newborn care',
                'approved',
                '2025-01-10',
                'HR approved extended leave',
                'false',
                'false'
            ]
        ];
    }

    public function headings(): array
    {
        return [
            'Employee ID',
            'Leave Type',
            'Start Date',
            'End Date',
            'Days',
            'Reason',
            'Status',
            'Applied Date',
            'Notes',
            'Emergency',
            'Half Day'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Header styling
        $sheet->getStyle('A1:K1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '059669'], // Green for leave
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
        $sheet->getStyle('A2:K4')->applyFromArray([
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
        $sheet->getComment('B2')->getText()->createTextRun('Options: annual, sick, maternity, paternity, personal, study, emergency');
        $sheet->getComment('C2')->getText()->createTextRun('Format: YYYY-MM-DD');
        $sheet->getComment('D2')->getText()->createTextRun('Format: YYYY-MM-DD (must be >= start date)');
        $sheet->getComment('E2')->getText()->createTextRun('Number of leave days (will be calculated if empty)');
        $sheet->getComment('F2')->getText()->createTextRun('Detailed reason for leave (minimum 10 characters)');
        $sheet->getComment('G2')->getText()->createTextRun('Options: pending, approved, rejected, cancelled');
        $sheet->getComment('H2')->getText()->createTextRun('Date when leave was applied (format: YYYY-MM-DD)');
        $sheet->getComment('I2')->getText()->createTextRun('Additional notes or comments');
        $sheet->getComment('J2')->getText()->createTextRun('Emergency leave flag (true/false)');
        $sheet->getComment('K2')->getText()->createTextRun('Half day leave flag (true/false)');

        return $sheet;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15, // Employee ID
            'B' => 12, // Leave Type
            'C' => 12, // Start Date
            'D' => 12, // End Date
            'E' => 8,  // Days
            'F' => 30, // Reason
            'G' => 12, // Status
            'H' => 12, // Applied Date
            'I' => 20, // Notes
            'J' => 10, // Emergency
            'K' => 10, // Half Day
        ];
    }

    public function title(): string
    {
        return 'Leave Import Template';
    }
}