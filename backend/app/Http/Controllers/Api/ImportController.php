<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Imports\EmployeesImport;
use App\Models\EmployeeType;
use App\Models\Department;
use App\Models\Position;
use App\Models\Subject;
use App\Models\Classroom;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportController extends Controller
{
    /**
     * Import employees from Excel file
     */
    public function employees(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240', // Max 10MB
        ]);

        try {
            $import = new EmployeesImport();
            Excel::import($import, $request->file('file'));
            
            $results = $import->getResults();
            
            Log::info('Employee import completed', $results);

            return response()->json([
                'success' => true,
                'message' => 'Import berhasil',
                'total' => $results['imported'] + $results['updated'] + $results['skipped'],
                'imported' => $results['imported'],
                'updated' => $results['updated'],
                'skipped' => $results['skipped'],
                'errors' => $results['errors'],
            ]);
        } catch (\Exception $e) {
            Log::error('Employee import failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Import gagal: ' . $e->getMessage(),
                'total' => 0,
                'imported' => 0,
                'updated' => 0,
                'skipped' => 0,
                'errors' => [[
                    'row' => 0,
                    'column' => '',
                    'value' => '',
                    'message' => $e->getMessage(),
                ]],
            ], 500);
        }
    }

    /**
     * Download employee import template
     */
    public function employeesTemplate(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Import');

        // Headers
        $headers = [
            'A1' => 'Nama Lengkap*',
            'B1' => 'Email*',
            'C1' => 'Telepon',
            'D1' => 'NIP/NIK',
            'E1' => 'Jenis Pegawai*',
            'F1' => 'Unit Kerja',
            'G1' => 'Jabatan',
            'H1' => 'Tanggal Masuk*',
            'I1' => 'Tanggal Lahir',
            'J1' => 'Jenis Kelamin',
            'K1' => 'Alamat',
        ];

        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
            $sheet->getStyle($cell)->getFont()->setBold(true);
            $sheet->getStyle($cell)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFE2E8F0');
        }

        // Example data
        $exampleData = [
            ['Ahmad Fauzi', 'ahmad.fauzi@school.edu', '081234567890', 'EMP001', 'GURU', 'AKADEMIK', 'GURU', '2024-01-15', '1990-05-20', 'Laki-laki', 'Jl. Pendidikan No. 1'],
            ['Siti Aminah', 'siti.aminah@school.edu', '081234567891', 'EMP002', 'GURU', 'AKADEMIK', 'GURU', '2024-01-15', '1992-08-10', 'Perempuan', 'Jl. Merdeka No. 2'],
            ['Budi Santoso', 'budi.santoso@school.edu', '081234567892', 'EMP003', 'TU', 'TU', 'STAFF', '2024-02-01', '1988-12-05', 'Laki-laki', 'Jl. Admin No. 3'],
        ];

        $row = 2;
        foreach ($exampleData as $data) {
            $col = 'A';
            foreach ($data as $value) {
                $sheet->setCellValue($col . $row, $value);
                $col++;
            }
            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'K') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Add reference sheet
        $refSheet = $spreadsheet->createSheet();
        $refSheet->setTitle('Referensi');

        // Employee Types
        $refSheet->setCellValue('A1', 'Kode Jenis Pegawai');
        $refSheet->setCellValue('B1', 'Nama');
        $refSheet->getStyle('A1:B1')->getFont()->setBold(true);
        
        $types = EmployeeType::where('is_active', true)->get();
        $row = 2;
        foreach ($types as $type) {
            $refSheet->setCellValue('A' . $row, $type->code);
            $refSheet->setCellValue('B' . $row, $type->name);
            $row++;
        }

        // Departments
        $refSheet->setCellValue('D1', 'Kode Unit Kerja');
        $refSheet->setCellValue('E1', 'Nama');
        $refSheet->getStyle('D1:E1')->getFont()->setBold(true);
        
        $depts = Department::where('is_active', true)->get();
        $row = 2;
        foreach ($depts as $dept) {
            $refSheet->setCellValue('D' . $row, $dept->code);
            $refSheet->setCellValue('E' . $row, $dept->name);
            $row++;
        }

        // Positions
        $refSheet->setCellValue('G1', 'Kode Jabatan');
        $refSheet->setCellValue('H1', 'Nama');
        $refSheet->getStyle('G1:H1')->getFont()->setBold(true);
        
        $positions = Position::where('is_active', true)->get();
        $row = 2;
        foreach ($positions as $pos) {
            $refSheet->setCellValue('G' . $row, $pos->code);
            $refSheet->setCellValue('H' . $row, $pos->name);
            $row++;
        }

        // Auto-size reference columns
        foreach (range('A', 'H') as $col) {
            $refSheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Set active sheet back to first
        $spreadsheet->setActiveSheetIndex(0);

        $writer = new Xlsx($spreadsheet);
        
        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, 'template_import_pegawai.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Import subjects from Excel file
     */
    public function subjects(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ]);

        try {
            $data = $this->parseExcelFile($request->file('file'));
            
            $results = [
                'imported' => 0,
                'updated' => 0,
                'skipped' => 0,
                'errors' => [],
            ];

            foreach ($data as $index => $row) {
                $rowNumber = $index + 2;
                
                try {
                    $code = $row['code'] ?? $row['kode'] ?? null;
                    $name = $row['name'] ?? $row['nama'] ?? null;
                    $description = $row['description'] ?? $row['deskripsi'] ?? null;

                    if (empty($name)) {
                        $results['errors'][] = [
                            'row' => $rowNumber,
                            'column' => 'name',
                            'value' => '',
                            'message' => 'Nama mata pelajaran wajib diisi',
                        ];
                        $results['skipped']++;
                        continue;
                    }

                    if (empty($code)) {
                        $code = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $name), 0, 10));
                    }

                    $existing = Subject::where('code', $code)->first();
                    
                    if ($existing) {
                        $existing->update([
                            'name' => $name,
                            'description' => $description,
                        ]);
                        $results['updated']++;
                    } else {
                        Subject::create([
                            'code' => $code,
                            'name' => $name,
                            'description' => $description,
                            'is_active' => true,
                        ]);
                        $results['imported']++;
                    }
                } catch (\Exception $e) {
                    $results['errors'][] = [
                        'row' => $rowNumber,
                        'column' => '',
                        'value' => '',
                        'message' => $e->getMessage(),
                    ];
                    $results['skipped']++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Import berhasil',
                'total' => count($data),
                ...$results,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Import gagal: ' . $e->getMessage(),
                'total' => 0,
                'imported' => 0,
                'updated' => 0,
                'skipped' => 0,
                'errors' => [['row' => 0, 'column' => '', 'value' => '', 'message' => $e->getMessage()]],
            ], 500);
        }
    }

    /**
     * Import positions from Excel file
     */
    public function positions(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ]);

        try {
            $data = $this->parseExcelFile($request->file('file'));
            
            $results = [
                'imported' => 0,
                'updated' => 0,
                'skipped' => 0,
                'errors' => [],
            ];

            foreach ($data as $index => $row) {
                $rowNumber = $index + 2;
                
                try {
                    $code = $row['code'] ?? $row['kode'] ?? null;
                    $name = $row['name'] ?? $row['nama'] ?? null;
                    $description = $row['description'] ?? $row['deskripsi'] ?? null;

                    if (empty($name)) {
                        $results['errors'][] = [
                            'row' => $rowNumber,
                            'column' => 'name',
                            'value' => '',
                            'message' => 'Nama jabatan wajib diisi',
                        ];
                        $results['skipped']++;
                        continue;
                    }

                    if (empty($code)) {
                        $code = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $name), 0, 10));
                    }

                    $existing = Position::where('code', $code)->first();
                    
                    if ($existing) {
                        $existing->update(['name' => $name, 'description' => $description]);
                        $results['updated']++;
                    } else {
                        Position::create([
                            'code' => $code,
                            'name' => $name,
                            'description' => $description,
                            'is_active' => true,
                        ]);
                        $results['imported']++;
                    }
                } catch (\Exception $e) {
                    $results['errors'][] = [
                        'row' => $rowNumber,
                        'column' => '',
                        'value' => '',
                        'message' => $e->getMessage(),
                    ];
                    $results['skipped']++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Import berhasil',
                'total' => count($data),
                ...$results,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Import gagal: ' . $e->getMessage(),
                'total' => 0,
                'imported' => 0,
                'updated' => 0,
                'skipped' => 0,
                'errors' => [['row' => 0, 'column' => '', 'value' => '', 'message' => $e->getMessage()]],
            ], 500);
        }
    }

    /**
     * Import departments from Excel file
     */
    public function departments(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ]);

        try {
            $data = $this->parseExcelFile($request->file('file'));
            
            $results = [
                'imported' => 0,
                'updated' => 0,
                'skipped' => 0,
                'errors' => [],
            ];

            foreach ($data as $index => $row) {
                $rowNumber = $index + 2;
                
                try {
                    $code = $row['code'] ?? $row['kode'] ?? null;
                    $name = $row['name'] ?? $row['nama'] ?? null;
                    $description = $row['description'] ?? $row['deskripsi'] ?? null;

                    if (empty($name)) {
                        $results['errors'][] = [
                            'row' => $rowNumber,
                            'column' => 'name',
                            'value' => '',
                            'message' => 'Nama unit kerja wajib diisi',
                        ];
                        $results['skipped']++;
                        continue;
                    }

                    if (empty($code)) {
                        $code = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $name), 0, 10));
                    }

                    $existing = Department::where('code', $code)->first();
                    
                    if ($existing) {
                        $existing->update(['name' => $name, 'description' => $description]);
                        $results['updated']++;
                    } else {
                        Department::create([
                            'code' => $code,
                            'name' => $name,
                            'description' => $description,
                            'is_active' => true,
                        ]);
                        $results['imported']++;
                    }
                } catch (\Exception $e) {
                    $results['errors'][] = [
                        'row' => $rowNumber,
                        'column' => '',
                        'value' => '',
                        'message' => $e->getMessage(),
                    ];
                    $results['skipped']++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Import berhasil',
                'total' => count($data),
                ...$results,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Import gagal: ' . $e->getMessage(),
                'total' => 0,
                'imported' => 0,
                'updated' => 0,
                'skipped' => 0,
                'errors' => [['row' => 0, 'column' => '', 'value' => '', 'message' => $e->getMessage()]],
            ], 500);
        }
    }

    /**
     * Import classrooms from Excel file
     */
    public function classrooms(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ]);

        try {
            $data = $this->parseExcelFile($request->file('file'));
            
            $results = [
                'imported' => 0,
                'updated' => 0,
                'skipped' => 0,
                'errors' => [],
            ];

            foreach ($data as $index => $row) {
                $rowNumber = $index + 2;
                
                try {
                    $name = $row['name'] ?? $row['nama'] ?? $row['kelas'] ?? null;
                    $gradeLevel = $row['grade_level'] ?? $row['tingkat'] ?? null;
                    $major = $row['major'] ?? $row['jurusan'] ?? null;
                    $classNumber = $row['class_number'] ?? $row['nomor'] ?? null;
                    $capacity = $row['capacity'] ?? $row['kapasitas'] ?? 30;
                    $room = $row['room'] ?? $row['ruangan'] ?? null;

                    if (empty($name)) {
                        $results['errors'][] = [
                            'row' => $rowNumber,
                            'column' => 'name',
                            'value' => '',
                            'message' => 'Nama kelas wajib diisi',
                        ];
                        $results['skipped']++;
                        continue;
                    }

                    $existing = Classroom::where('name', $name)->first();
                    
                    if ($existing) {
                        $existing->update([
                            'grade_level' => $gradeLevel,
                            'major' => $major,
                            'class_number' => $classNumber,
                            'capacity' => (int) $capacity,
                            'room' => $room,
                        ]);
                        $results['updated']++;
                    } else {
                        Classroom::create([
                            'name' => $name,
                            'grade_level' => $gradeLevel,
                            'major' => $major,
                            'class_number' => $classNumber,
                            'capacity' => (int) $capacity,
                            'room' => $room,
                            'is_active' => true,
                        ]);
                        $results['imported']++;
                    }
                } catch (\Exception $e) {
                    $results['errors'][] = [
                        'row' => $rowNumber,
                        'column' => '',
                        'value' => '',
                        'message' => $e->getMessage(),
                    ];
                    $results['skipped']++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Import berhasil',
                'total' => count($data),
                ...$results,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Import gagal: ' . $e->getMessage(),
                'total' => 0,
                'imported' => 0,
                'updated' => 0,
                'skipped' => 0,
                'errors' => [['row' => 0, 'column' => '', 'value' => '', 'message' => $e->getMessage()]],
            ], 500);
        }
    }

    /**
     * Parse Excel file to array
     */
    protected function parseExcelFile($file): array
    {
        $data = [];
        $rows = Excel::toArray(new class implements \Maatwebsite\Excel\Concerns\ToArray {
            public function array(array $array)
            {
                return $array;
            }
        }, $file);

        if (!empty($rows[0])) {
            $headers = array_shift($rows[0]); // First row is header
            $headers = array_map(fn($h) => strtolower(trim($h)), $headers);
            
            foreach ($rows[0] as $row) {
                $item = [];
                foreach ($headers as $index => $header) {
                    $item[$header] = $row[$index] ?? null;
                }
                $data[] = $item;
            }
        }

        return $data;
    }
}
