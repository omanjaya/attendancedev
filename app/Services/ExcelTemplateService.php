<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Font;
use App\Models\Location;

/**
 * Excel Template Service
 * 
 * Generates Excel templates for importing employees with proper validation
 */
class ExcelTemplateService
{
    /**
     * Generate employee import template
     */
    public function generateEmployeeTemplate(): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set sheet name
        $sheet->setTitle('Template Import Karyawan');
        
        // Define headers (Indonesian)
        $headers = [
            'full_name' => 'Nama Lengkap', 
            'email' => 'Email',
            'phone' => 'Telepon',
            'employee_type' => 'Tipe Karyawan',
            'role' => 'Role',
            'salary_type' => 'Tipe Gaji',
            'salary_amount' => 'Gaji Bulanan',
            'hourly_rate' => 'Tarif Per Jam',
            'hire_date' => 'Tanggal Bergabung',
            'department' => 'Departemen',
            'position' => 'Posisi',
            'status' => 'Status'
        ];
        
        // Set headers
        $column = 'A';
        foreach ($headers as $key => $header) {
            $sheet->setCellValue($column . '1', $header);
            $column++;
        }
        
        // Style headers
        $headerRange = 'A1:' . chr(ord('A') + count($headers) - 1) . '1';
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2563eb'] // Blue-600
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN
                ]
            ]
        ]);
        
        // Add sample data rows with unique emails and more examples
        $sampleData = [
            [
                'Pande Putu Sekar Ariwidiantari, S.Si', 'pande.sekar@slub.ac.id', '081234567890',
                'permanent', 'kepala_sekolah', 'monthly', '8000000', '', '01/01/2024',
                'SMP SARASWATI', 'Kepala Sekolah', 'Aktif'
            ],
            [
                'Putu Sri Rahayu Kasumawati, ST.', 'putu.rahayu@slub.ac.id', '081234567891', 
                'permanent', 'guru', 'monthly', '5000000', '', '15/01/2024',
                'SMP SARASWATI', 'Waka Kesiswaan', 'Aktif'
            ],
            [
                'I Made Ngurah Wijaya, S.Pd', 'made.wijaya@slub.ac.id', '081234567892',
                'honorary', 'guru', 'hourly', '', '75000', '01/02/2024',
                'SMP SARASWATI', 'Guru Matematika', 'Aktif'
            ],
            [
                'Ni Ketut Sari Dewi, S.Kom', 'ketut.sari@slub.ac.id', '081234567893',
                'staff', 'pegawai', 'monthly', '3500000', '', '15/02/2024',
                'SMP SARASWATI', 'Staff TU', 'Aktif'
            ],
            [
                'I Gede Agus Suryawan, S.Pd', 'gede.agus@slub.ac.id', '081234567894',
                'permanent', 'guru', 'monthly', '5200000', '', '01/03/2024',
                'SMP SARASWATI', 'Guru IPA', 'Aktif'
            ]
        ];
        
        // Insert sample data
        $row = 2;
        foreach ($sampleData as $data) {
            $column = 'A';
            foreach ($data as $value) {
                $sheet->setCellValue($column . $row, $value);
                $column++;
            }
            $row++;
        }
        
        // Style data rows
        $dataRange = 'A2:' . chr(ord('A') + count($headers) - 1) . ($row - 1);
        $sheet->getStyle($dataRange)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'E5E7EB']
                ]
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER
            ]
        ]);
        
        // Auto-size columns
        foreach (range('A', chr(ord('A') + count($headers) - 1)) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Set row height
        $sheet->getRowDimension('1')->setRowHeight(25);
        
        // Create instructions sheet
        $this->createInstructionsSheet($spreadsheet);
        
        // Create validation sheet
        $this->createValidationSheet($spreadsheet);
        
        // Save to temp file
        $tempFile = storage_path('app/temp/template_import_karyawan_' . date('Y-m-d_H-i-s') . '.xlsx');
        
        // Ensure temp directory exists
        if (!file_exists(dirname($tempFile))) {
            mkdir(dirname($tempFile), 0755, true);
        }
        
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);
        
        return $tempFile;
    }
    
    /**
     * Create instructions sheet
     */
    private function createInstructionsSheet(Spreadsheet $spreadsheet): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Petunjuk Import');
        
        $instructions = [
            ['PETUNJUK IMPORT KARYAWAN', ''],
            ['', ''],
            ['⚠️  PENTING: HAPUS SEMUA DATA CONTOH SEBELUM IMPORT!', ''],
            ['', ''],
            ['1. Format File', ''],
            ['   • Gunakan file Excel (.xlsx) atau CSV', ''],
            ['   • Jangan ubah nama kolom di baris pertama', ''],
            ['   • Pastikan setiap email UNIK (tidak boleh sama)', ''],
            ['   • Hapus data contoh sebelum menambah data asli', ''],
            ['', ''],
            ['2. Kolom Wajib', ''],
            ['   • Nama Lengkap: Nama lengkap karyawan', ''],
            ['   • Email: Alamat email valid dan unique', ''],
            ['   • Role: Peran pengguna dalam sistem', ''],
            ['', ''],
            ['3. Tipe Karyawan', ''],
            ['   • permanent: Karyawan tetap', ''],
            ['   • honorary: Guru honorer', ''],
            ['   • staff: Staff non-mengajar', ''],
            ['', ''],
            ['4. Role Pengguna', ''],
            ['   • Super Admin: Akses penuh sistem', ''],
            ['   • Admin: Manajemen karyawan dan sistem', ''],
            ['   • kepala_sekolah: Kepala sekolah', ''],
            ['   • guru: Guru/pengajar', ''],
            ['   • pegawai: Staff/pegawai', ''],
            ['', ''],
            ['5. Tipe Gaji', ''],
            ['   • monthly: Gaji bulanan (isi kolom Gaji Bulanan)', ''],
            ['   • hourly: Gaji per jam (isi kolom Tarif Per Jam)', ''],
            ['   • fixed: Gaji tetap', ''],
            ['', ''],
            ['6. Format Tanggal', ''],
            ['   • Gunakan format: YYYY-MM-DD', ''],
            ['   • Contoh: 2024-01-15', ''],
            ['', ''],
            ['7. Departemen', ''],
            ['   • Nama lokasi/sekolah yang sudah terdaftar', ''],
            ['   • Jika tidak ada, akan diisi kosong', ''],
            ['', ''],
            ['8. Status', ''],
            ['   • Aktif: Karyawan aktif', ''],
            ['   • Tidak Aktif: Karyawan non-aktif', ''],
            ['', ''],
            ['9. Tips Import', ''],
            ['   • Maksimal 1000 karyawan per import', ''],
            ['   • ⚠️  SETIAP EMAIL HARUS UNIK - TIDAK BOLEH ADA YANG SAMA!', ''],
            ['   • Gunakan format email: nama.lengkap@domain.com', ''],
            ['   • Employee ID akan digenerate otomatis berdasarkan Role + Tipe Karyawan', ''],
            ['   • Password default: password123', ''],
            ['   • Sistem akan auto-generate ID seperti:', ''],
            ['     - Super Admin: SADM001, SADM002, dst', ''],
            ['     - Admin: ADMN001, ADMN002, dst', ''],
            ['     - Kepala Sekolah: KPS001, KPS002, dst', ''],
            ['     - Guru Tetap: GTTP001, GTTP002, dst', ''],
            ['     - Guru Honor: GHNR001, GHNR002, dst', ''],
            ['     - Pegawai: PGWI001, PGWI002, dst', ''],
            ['', ''],
            ['❌ CONTOH SALAH (EMAIL DUPLIKAT):', ''],
            ['   • john@domain.com, jane@domain.com, john@domain.com ← SALAH!', ''],
            ['', ''],
            ['✅ CONTOH BENAR (EMAIL UNIK):', ''],
            ['   • john.doe@domain.com, jane.smith@domain.com, bob.wilson@domain.com', '']
        ];
        
        $row = 1;
        foreach ($instructions as $instruction) {
            $sheet->setCellValue('A' . $row, $instruction[0]);
            $sheet->setCellValue('B' . $row, $instruction[1]);
            $row++;
        }
        
        // Style title
        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 16,
                'color' => ['rgb' => '1F2937']
            ]
        ]);
        
        // Style section headers
        foreach ([3, 8, 13, 18, 25, 30, 35, 40, 45] as $row) {
            if ($row <= $sheet->getHighestRow()) {
                $sheet->getStyle('A' . $row)->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => '374151']
                    ]
                ]);
            }
        }
        
        // Auto-size columns
        $sheet->getColumnDimension('A')->setWidth(50);
        $sheet->getColumnDimension('B')->setWidth(30);
    }
    
    /**
     * Create validation reference sheet
     */
    private function createValidationSheet(Spreadsheet $spreadsheet): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Data Referensi');
        
        // Employee types
        $sheet->setCellValue('A1', 'Tipe Karyawan');
        $sheet->setCellValue('A2', 'permanent');
        $sheet->setCellValue('A3', 'honorary');
        $sheet->setCellValue('A4', 'staff');
        
        // Salary types
        $sheet->setCellValue('C1', 'Tipe Gaji');
        $sheet->setCellValue('C2', 'monthly');
        $sheet->setCellValue('C3', 'hourly');
        $sheet->setCellValue('C4', 'fixed');
        
        // Status options
        $sheet->setCellValue('E1', 'Status');
        $sheet->setCellValue('E2', 'Aktif');
        $sheet->setCellValue('E3', 'Tidak Aktif');
        
        // Role options
        $sheet->setCellValue('G1', 'Role');
        $sheet->setCellValue('G2', 'Super Admin');
        $sheet->setCellValue('G3', 'Admin');
        $sheet->setCellValue('G4', 'kepala_sekolah');
        $sheet->setCellValue('G5', 'guru');
        $sheet->setCellValue('G6', 'pegawai');
        
        // Available locations
        $sheet->setCellValue('I1', 'Departemen Tersedia');
        $locations = Location::select('name')->get();
        $row = 2;
        foreach ($locations as $location) {
            $sheet->setCellValue('I' . $row, $location->name);
            $row++;
        }
        
        // Style headers
        foreach (['A1', 'C1', 'E1', 'G1', 'I1'] as $cell) {
            $sheet->getStyle($cell)->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF']
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '059669'] // Green-600
                ]
            ]);
        }
        
        // Auto-size columns
        foreach (['A', 'C', 'E', 'G', 'I'] as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }
    
    /**
     * Validate import data against template
     */
    public function validateImportData(array $data): array
    {
        $errors = [];
        $validatedData = [];
        
        // Track emails within the import file for duplicate detection
        $emailsInFile = [];
        
        // Helper function to safely get values from row
        $getValue = function($row, $primaryKey, $fallbackKey = null, $default = '') {
            return $row[$primaryKey] ?? ($fallbackKey ? ($row[$fallbackKey] ?? $default) : $default);
        };
        
        foreach ($data as $index => $row) {
            $rowNumber = $index + 2; // Excel row number (accounting for header)
            $rowErrors = [];
            
            // Validate required fields            
            $fullName = $getValue($row, 'full_name', 'Nama Lengkap');
            if (empty($fullName)) {
                $rowErrors[] = 'Nama Lengkap wajib diisi';
            }
            
            $email = $getValue($row, 'email', 'Email');
            if (empty($email)) {
                $rowErrors[] = 'Email wajib diisi';
            } else {
                $email = strtolower(trim($email));
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $rowErrors[] = 'Format email tidak valid';
                } else {
                    // Check for duplicates within the import file
                    if (isset($emailsInFile[$email])) {
                        $rowErrors[] = "Email '{$email}' sudah digunakan di baris {$emailsInFile[$email]}";
                    } else {
                        // Check if email already exists in database
                        if (\App\Models\User::where('email', $email)->exists()) {
                            $rowErrors[] = "Email '{$email}' sudah terdaftar di database";
                        } else {
                            $emailsInFile[$email] = $rowNumber;
                        }
                    }
                }
            }
            
            // Validate phone (optional but check format if provided)
            $phone = $getValue($row, 'phone', 'Telepon');
            if (!empty($phone)) {
                // Remove common phone formatting
                $cleanPhone = preg_replace('/[\s\-\(\)]+/', '', $phone);
                if (!preg_match('/^[+]?[0-9]{10,15}$/', $cleanPhone)) {
                    $rowErrors[] = 'Format nomor telepon tidak valid (10-15 digit)';
                }
            }
            
            // Validate employee type
            $employeeType = strtolower($getValue($row, 'employee_type', 'Tipe Karyawan'));
            if (!in_array($employeeType, ['permanent', 'honorary', 'staff'])) {
                $rowErrors[] = 'Tipe Karyawan harus: permanent, honorary, atau staff';
            }
            
            // Validate role
            $role = strtolower(str_replace(' ', '_', $getValue($row, 'role', 'Role')));
            if (!in_array($role, ['super_admin', 'admin', 'kepala_sekolah', 'guru', 'pegawai'])) {
                $rowErrors[] = 'Role harus: super_admin, admin, kepala_sekolah, guru, atau pegawai';
            }
            
            // Validate salary type
            $salaryType = strtolower($getValue($row, 'salary_type', 'Tipe Gaji'));
            if (!in_array($salaryType, ['hourly', 'monthly', 'fixed'])) {
                $rowErrors[] = 'Tipe Gaji harus: hourly, monthly, atau fixed';
            }
            
            // Validate salary amount based on salary type
            $salaryAmount = $getValue($row, 'salary_amount', 'Gaji Bulanan');
            $hourlyRate = $getValue($row, 'hourly_rate', 'Tarif Per Jam');
            
            if (in_array($salaryType, ['monthly', 'fixed']) && empty($salaryAmount)) {
                $rowErrors[] = 'Gaji Bulanan wajib diisi untuk tipe gaji monthly/fixed';
            } elseif (!empty($salaryAmount) && !is_numeric($salaryAmount)) {
                $rowErrors[] = 'Gaji Bulanan harus berupa angka';
            } elseif (!empty($salaryAmount) && $salaryAmount < 0) {
                $rowErrors[] = 'Gaji Bulanan tidak boleh negatif';
            }
            
            if ($salaryType === 'hourly' && empty($hourlyRate)) {
                $rowErrors[] = 'Tarif Per Jam wajib diisi untuk tipe gaji hourly';
            } elseif (!empty($hourlyRate) && !is_numeric($hourlyRate)) {
                $rowErrors[] = 'Tarif Per Jam harus berupa angka';
            } elseif (!empty($hourlyRate) && $hourlyRate < 0) {
                $rowErrors[] = 'Tarif Per Jam tidak boleh negatif';
            }
            
            // Validate hire date
            $hireDate = $getValue($row, 'hire_date', 'Tanggal Bergabung');
            if (!empty($hireDate)) {
                $parsedDate = null;
                
                // Check if it's an Excel serial number (numeric value)
                if (is_numeric($hireDate)) {
                    try {
                        // Convert Excel serial number to date
                        // Excel uses 1900-01-01 as day 1, but has a leap year bug (treats 1900 as leap year)
                        // PHP's Excel date handling: serial number days since 1900-01-01
                        $excelBaseDate = new \DateTime('1900-01-01');
                        $excelBaseDate->modify('-2 days'); // Adjust for Excel's leap year bug
                        $parsedDate = clone $excelBaseDate;
                        $parsedDate->modify('+' . intval($hireDate) . ' days');
                        
                        \Log::info('Excel date conversion', [
                            'serial' => $hireDate,
                            'converted' => $parsedDate->format('Y-m-d')
                        ]);
                    } catch (\Exception $e) {
                        \Log::warning('Excel date conversion failed', [
                            'serial' => $hireDate,
                            'error' => $e->getMessage()
                        ]);
                    }
                } else {
                    // Try to parse various string date formats
                    $dateFormats = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'Y/m/d', 'm/d/Y'];
                    
                    foreach ($dateFormats as $format) {
                        $date = \DateTime::createFromFormat($format, $hireDate);
                        if ($date && $date->format($format) === $hireDate) {
                            $parsedDate = $date;
                            break;
                        }
                    }
                }
                
                if (!$parsedDate) {
                    $rowErrors[] = 'Format tanggal bergabung tidak valid (gunakan: dd/mm/yyyy, yyyy-mm-dd, atau biarkan Excel mengelola format tanggal)';
                } elseif ($parsedDate > new \DateTime()) {
                    $rowErrors[] = 'Tanggal bergabung tidak boleh di masa depan';
                } elseif ($parsedDate < new \DateTime('1950-01-01')) {
                    $rowErrors[] = 'Tanggal bergabung terlalu lama (minimal tahun 1950)';
                }
            }
            
            // Validate status
            $status = strtolower($getValue($row, 'status', 'Status'));
            if (!empty($status) && !in_array($status, ['aktif', 'tidak aktif', 'active', 'inactive', '1', '0'])) {
                $rowErrors[] = 'Status harus: Aktif atau Tidak Aktif';
            }
            
            // Validate department (optional)
            $department = $getValue($row, 'department', 'Departemen');
            if (!empty($department) && strlen($department) > 100) {
                $rowErrors[] = 'Nama departemen terlalu panjang (maksimal 100 karakter)';
            }
            
            // Validate position (optional)
            $position = $getValue($row, 'position', 'Posisi');
            if (!empty($position) && strlen($position) > 100) {
                $rowErrors[] = 'Nama posisi terlalu panjang (maksimal 100 karakter)';
            }
            
            if (!empty($rowErrors)) {
                $errors["Baris {$rowNumber}"] = $rowErrors;
            } else {
                $validatedData[] = $row;
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'data' => $validatedData,
            'total_rows' => count($data),
            'valid_rows' => count($validatedData),
            'error_rows' => count($errors)
        ];
    }

    /**
     * Generate user credentials export file
     */
    public function generateCredentialExport(array $headers, array $data, string $title, string $filename): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set sheet name
        $sheet->setTitle('User Credentials');
        
        // Title row
        $sheet->setCellValue('A1', $title);
        $sheet->mergeCells('A1:' . $this->getColumnLetter(count($headers)) . '1');
        
        // Style title
        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 16,
                'color' => ['rgb' => '1f2937'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'f3f4f6'],
            ],
        ]);
        
        // Subtitle with timestamp
        $sheet->setCellValue('A2', 'Digenerate pada: ' . now()->format('d/m/Y H:i:s') . ' oleh: ' . auth()->user()->name);
        $sheet->mergeCells('A2:' . $this->getColumnLetter(count($headers)) . '2');
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['size' => 10, 'italic' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        
        // Headers row (row 4)
        $headerRow = 4;
        foreach ($headers as $index => $header) {
            $column = $this->getColumnLetter($index + 1);
            $sheet->setCellValue($column . $headerRow, $header);
        }
        
        // Style headers
        $headerRange = 'A' . $headerRow . ':' . $this->getColumnLetter(count($headers)) . $headerRow;
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'ffffff'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '3b82f6'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);
        
        // Data rows
        $dataStartRow = $headerRow + 1;
        foreach ($data as $rowIndex => $row) {
            $currentRow = $dataStartRow + $rowIndex;
            
            foreach ($row as $colIndex => $value) {
                $column = $this->getColumnLetter($colIndex + 1);
                $sheet->setCellValue($column . $currentRow, $value);
            }
            
            // Style data rows with alternating colors
            $rowRange = 'A' . $currentRow . ':' . $this->getColumnLetter(count($headers)) . $currentRow;
            $fillColor = $rowIndex % 2 === 0 ? 'f9fafb' : 'ffffff';
            
            $sheet->getStyle($rowRange)->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $fillColor],
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'd1d5db'],
                    ],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);
            
            // Special styling for password column (usually column C or D)
            if (isset($row[3]) && !empty($row[3])) { // Password column
                $passwordColumn = $this->getColumnLetter(4);
                $sheet->getStyle($passwordColumn . $currentRow)->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'dc2626'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'fef2f2'],
                    ],
                ]);
            }
        }
        
        // Auto-size columns
        foreach (range('A', $this->getColumnLetter(count($headers))) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        
        // Set row heights
        $sheet->getRowDimension(1)->setRowHeight(30);
        $sheet->getRowDimension(2)->setRowHeight(20);
        $sheet->getRowDimension($headerRow)->setRowHeight(25);
        
        // Add warning note
        $warningRow = $dataStartRow + count($data) + 2;
        $sheet->setCellValue('A' . $warningRow, '⚠️ PENTING: Password ini hanya ditampilkan sekali. Pastikan untuk menyimpan dengan aman!');
        $sheet->mergeCells('A' . $warningRow . ':' . $this->getColumnLetter(count($headers)) . $warningRow);
        $sheet->getStyle('A' . $warningRow)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'dc2626'],
                'size' => 12,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'fef2f2'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THICK,
                    'color' => ['rgb' => 'dc2626'],
                ],
            ],
        ]);
        
        // Save file
        $tempDir = storage_path('app/temp');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        
        $filePath = $tempDir . '/' . $filename;
        
        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);
        
        return $filePath;
    }
    
    /**
     * Get column letter from number (A, B, C, ...)
     */
    private function getColumnLetter(int $number): string
    {
        $letter = '';
        while ($number > 0) {
            $number--;
            $letter = chr(65 + ($number % 26)) . $letter;
            $number = intval($number / 26);
        }
        return $letter;
    }
}