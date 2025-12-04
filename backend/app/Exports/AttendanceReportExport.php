<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AttendanceReportExport implements FromCollection, WithHeadings, WithMapping
{
    protected $data;
    protected $headings;
    protected $columns;

    public function __construct($data, $headings = [], $columns = [])
    {
        $this->data = $data;
        $this->headings = $headings;
        $this->columns = $columns;
    }

    public function collection()
    {
        return collect($this->data);
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function map($row): array
    {
        // If specific columns are requested, map them
        if (!empty($this->columns)) {
            $mapped = [];
            foreach ($this->columns as $col) {
                $mapped[] = $row[$col] ?? '';
            }
            return $mapped;
        }

        // Otherwise return the whole row (values only)
        return array_values($row);
    }
}
