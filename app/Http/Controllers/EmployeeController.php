<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmployeeRequest;
use App\Models\Employee;
use App\Services\EmployeeService;
use App\Services\ExcelTemplateService;
use Illuminate\Http\Request;
use ReflectionClass;

/**
 * Employee Management Controller
 *
 * Handles all employee-related HTTP requests with minimal complexity
 * Following single responsibility principle
 */
class EmployeeController extends Controller
{
    public function __construct(
        private EmployeeService $service,
        private ExcelTemplateService $templateService
    ) {}

    /**
     * Display employee list
     */
    public function index()
    {
        return view('pages.management.employees.index', $this->service->getIndexData());
    }

    /**
     * Show create form
     */
    public function create()
    {
        return view('pages.management.employees.create', $this->service->getFormData());
    }

    /**
     * Store new employee
     */
    public function store(EmployeeRequest $request)
    {
        try {
            $employee = $this->service->create($request->validated());

            return redirect()->route('employees.index')
                ->with('success', "Employee '{$employee->name}' has been created successfully.");
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to create employee. Please check your data and try again.')
                ->withInput();
        }
    }

    /**
     * Display employee details
     */
    public function show(Employee $employee)
    {
        return view('pages.management.employees.show', compact('employee'));
    }

    /**
     * Show edit form
     */
    public function edit(Employee $employee)
    {
        $employee->load(['user.roles', 'location']);

        return view('pages.management.employees.edit', [
            'employee' => $employee,
            ...$this->service->getFormData(),
        ]);
    }

    /**
     * Update employee
     */
    public function update(EmployeeRequest $request, Employee $employee)
    {
        try {
            $this->service->update($employee, $request->validated());

            return redirect()->route('employees.index')
                ->with('success', "Employee '{$employee->name}' has been updated successfully.");
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to update employee. Please check your data and try again.')
                ->withInput();
        }
    }

    /**
     * Delete employee
     */
    public function destroy(Employee $employee)
    {
        \Log::info('Delete employee method called', [
            'employee_id' => $employee->id,
            'employee_name' => $employee->full_name,
            'request_method' => request()->method(),
            'request_url' => request()->fullUrl(),
            'user_id' => auth()->id()
        ]);

        try {
            $employeeName = $employee->full_name;
            $userId = $employee->user_id;
            
            // Delete employee record permanently
            $employee->forceDelete();
            
            // Delete associated user account if exists
            if ($userId) {
                \App\Models\User::where('id', $userId)->forceDelete();
            }

            if (request()->expectsJson() || request()->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Employee '{$employeeName}' has been deleted permanently."
                ]);
            }

            return redirect()->route('employees.index')
                ->with('success', "Employee '{$employeeName}' has been deleted permanently.");
        } catch (\Exception $e) {
            \Log::error('Failed to delete employee', [
                'employee_id' => $employee->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if (request()->expectsJson() || request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete employee. Please try again.'
                ], 500);
            }

            return redirect()->route('employees.index')
                ->with('error', 'Failed to delete employee. Please try again.');
        }
    }

    /**
     * DataTables AJAX endpoint
     */
    public function data(Request $request)
    {
        return $this->service->getDataTableResponse($request);
    }

    /**
     * Bulk operations handler
     */
    public function bulk(Request $request)
    {
        // Force JSON response for all requests to this endpoint
        $request->headers->set('Accept', 'application/json');
        
        \Log::info('Bulk operation called', [
            'action' => $request->input('action'),
            'employee_ids' => $request->input('employee_ids'),
            'method' => $request->method(),
            'content_type' => $request->header('Content-Type'),
            'user_id' => auth()->id()
        ]);

        try {
            $result = $this->service->handleBulkOperation($request);
            
            \Log::info('Bulk operation result', [
                'result' => $result
            ]);

            return response()->json($result);
        } catch (\Exception $e) {
            \Log::error('Bulk operation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Bulk operation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download template for employee import (Excel or CSV)
     */
    public function template(Request $request)
    {
        try {
            $format = $request->get('format', 'excel');
            
            if ($format === 'csv') {
                return $this->generateCSVTemplate();
            }
            
            // Default: Excel template
            $templatePath = $this->templateService->generateEmployeeTemplate();
            
            return response()->download($templatePath, 'template_import_karyawan.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal membuat template: ' . $e->getMessage());
        }
    }
    
    /**
     * Generate CSV template for employee import
     */
    private function generateCSVTemplate()
    {
        $headers = [
            'full_name', 
            'email',
            'phone',
            'employee_type',
            'role',
            'salary_type',
            'salary_amount',
            'hourly_rate',
            'hire_date',
            'department',
            'position',
            'status'
        ];
        
        $sampleData = [
            [
                'Pande Putu Sekar Ariwidiantari, S.Si',
                'pande.sekar@slub.ac.id',
                '081234567890',
                'permanent',
                'kepala_sekolah',
                'monthly',
                '8000000',
                '',
                '2024-01-01',
                'SMP SARASWATI',
                'Kepala Sekolah',
                'Aktif'
            ],
            [
                'Putu Sri Rahayu Kasumawati, ST.',
                'putu.rahayu@slub.ac.id',
                '081234567891',
                'permanent',
                'guru',
                'monthly',
                '5000000',
                '',
                '2024-01-15',
                'SMP SARASWATI',
                'Waka Kesiswaan',
                'Aktif'
            ],
            [
                'I Made Ngurah Wijaya, S.Pd',
                'made.wijaya@slub.ac.id',
                '081234567892',
                'honorary',
                'guru',
                'hourly',
                '',
                '75000',
                '2024-02-01',
                'SMP SARASWATI',
                'Guru Matematika',
                'Aktif'
            ],
            [
                'Ni Ketut Sari Dewi, S.Kom',
                'ketut.sari@slub.ac.id',
                '081234567893',
                'staff',
                'pegawai',
                'monthly',
                '3500000',
                '',
                '2024-02-15',
                'SMP SARASWATI',
                'Staff TU',
                'Aktif'
            ],
            [
                'I Gede Agus Suryawan, S.Pd',
                'gede.agus@slub.ac.id',
                '081234567894',
                'permanent',
                'guru',
                'monthly',
                '5200000',
                '',
                '2024-03-01',
                'SMP SARASWATI',
                'Guru IPA',
                'Aktif'
            ]
        ];
        
        $callback = function () use ($headers, $sampleData) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for proper UTF-8 encoding in Excel
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Write headers
            fputcsv($file, $headers);
            
            // Write sample data
            foreach ($sampleData as $row) {
                fputcsv($file, $row);
            }
            
            // Add blank row
            fputcsv($file, []);
            
            // Add instructions
            fputcsv($file, ['INSTRUKSI PENGISIAN:']);
            fputcsv($file, ['PENTING: HAPUS SEMUA DATA CONTOH SEBELUM IMPORT!']);
            fputcsv($file, ['SETIAP EMAIL HARUS UNIK - TIDAK BOLEH ADA YANG SAMA!']);
            fputcsv($file, ['- Employee ID akan digenerate otomatis berdasarkan Role + Tipe Karyawan']);
            fputcsv($file, ['- Tipe Karyawan: permanent, honorary, staff']);  
            fputcsv($file, ['- Role: Super Admin, Admin, kepala_sekolah, guru, pegawai']);
            fputcsv($file, ['- Format ID yang akan digenerate:']);
            fputcsv($file, ['  * Super Admin = SADM001, SADM002, dst']);
            fputcsv($file, ['  * Admin = ADMN001, ADMN002, dst']);
            fputcsv($file, ['  * Kepala Sekolah = KPS001, KPS002, dst']);
            fputcsv($file, ['  * Guru Tetap (permanent) = GTTP001, GTTP002, dst']);
            fputcsv($file, ['  * Guru Honor (honorary) = GHNR001, GHNR002, dst']);
            fputcsv($file, ['  * Pegawai = PGWI001, PGWI002, dst']);
            fputcsv($file, ['  * Pegawai Honor = PHNR001, PHNR002, dst']);
            fputcsv($file, ['- Password default: password123']);
            fputcsv($file, ['- Contoh email yang BENAR: nama.lengkap@domain.com (semua berbeda)']);
            fputcsv($file, ['- Contoh email yang SALAH: sample@domain.com (dipakai berulang)']);
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="template_import_karyawan.csv"',
        ]);
    }

    /**
     * Export employees data
     */
    public function export(Request $request)
    {
        try {
            $format = $request->get('format', 'csv');
            $filters = $request->only(['search', 'department', 'status', 'type', 'face_registered', 'date_from', 'date_to']);

            return $this->service->exportEmployees($format, $filters);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal mengekspor data: '.$e->getMessage());
        }
    }

    /**
     * Import employees from Excel/CSV file
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls|max:5120', // Max 5MB
        ], [
            'file.required' => 'File import wajib dipilih',
            'file.mimes' => 'File harus berformat CSV, Excel (.xlsx) atau Excel (.xls)',
            'file.max' => 'Ukuran file maksimal 5MB'
        ]);

        try {
            $file = $request->file('file');
            
            // Get import options from form
            $options = [
                'skip_duplicates' => $request->boolean('skip_duplicates', false),
                'update_existing' => $request->boolean('update_existing', false),
                'validate_only' => $request->boolean('validate_only', false)
            ];
            
            // Parse file first to validate
            $data = $this->service->parseImportFile($file);
            $validation = $this->templateService->validateImportData($data);
            
            if (!$validation['valid']) {
                $errorMessage = "Validasi gagal pada " . $validation['error_rows'] . " baris:\n";
                foreach ($validation['errors'] as $row => $errors) {
                    $errorMessage .= "{$row}: " . implode(', ', $errors) . "\n";
                }
                
                return redirect()->back()
                    ->with('error', $errorMessage)
                    ->with('validation_errors', $validation['errors']);
            }
            
            // If validation passes, proceed with import
            $results = $this->service->importEmployees($file, $options);

            $message = "Import berhasil! {$results['success']} karyawan berhasil diimpor";
            
            if ($results['skipped'] > 0) {
                $message .= ", {$results['skipped']} data dilewati";
            }
            
            if (!empty($results['errors'])) {
                $message .= " dengan " . count($results['errors']) . " error";
                $message .= ":\n" . implode("\n", $results['errors']);
            }

            return redirect()->route('employees.index')
                ->with('success', $message)
                ->with('import_summary', [
                    'total' => $validation['total_rows'],
                    'success' => $results['success'],
                    'skipped' => $results['skipped'],
                    'errors' => count($results['errors'])
                ]);
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal mengimpor data: ' . $e->getMessage())
                ->withInput();
        }
    }
    
    /**
     * Preview import data before processing
     */
    public function previewImport(Request $request)
    {
        // Force JSON response for all requests to this endpoint
        $request->headers->set('Accept', 'application/json');
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');

        \Log::info('Preview import called', [
            'method' => $request->method(),
            'content_type' => $request->header('Content-Type'),
            'accept' => $request->header('Accept'),
            'xhr' => $request->header('X-Requested-With'),
            'has_file' => $request->hasFile('file'),
            'file_name' => $request->hasFile('file') ? $request->file('file')->getClientOriginalName() : null,
            'file_extension' => $request->hasFile('file') ? $request->file('file')->getClientOriginalExtension() : null,
            'file_mime' => $request->hasFile('file') ? $request->file('file')->getMimeType() : null,
            'user_id' => auth()->id()
        ]);

        try {
            $request->validate([
                'file' => 'required|file|mimes:csv,xlsx,xls|max:5120',
            ], [
                'file.required' => 'File import wajib dipilih',
                'file.mimes' => 'File harus berformat CSV, Excel (.xlsx) atau Excel (.xls)',
                'file.max' => 'Ukuran file maksimal 5MB'
            ]);

            $file = $request->file('file');
            \Log::info('File validation passed, parsing file', [
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'file_path' => $file->getPathname()
            ]);
            
            $data = $this->service->parseImportFile($file);
            \Log::info('File parsed successfully', [
                'rows_count' => count($data),
                'first_row' => $data[0] ?? null
            ]);
            
            $validation = $this->templateService->validateImportData($data);
            \Log::info('Validation completed', [
                'validation_result' => $validation
            ]);
            
            // Get parsed data for preview (first 10 rows)
            $preview = [];
            foreach (array_slice($data, 0, 10) as $row) {
                try {
                    $reflection = new ReflectionClass($this->service);
                    $method = $reflection->getMethod('mapImportData');
                    $method->setAccessible(true);
                    $mappedData = $method->invoke($this->service, $row);
                    $preview[] = $mappedData;
                } catch (\Exception $e) {
                    // If mapping fails, use original row data
                    $preview[] = $row;
                }
            }
            
            return response()->json([
                'success' => true,
                'preview' => [
                    'total_rows' => $validation['total_rows'],
                    'valid_rows' => $validation['valid_rows'],
                    'error_rows' => $validation['error_rows'],
                    'errors' => $validation['errors'],
                    'sample_data' => $preview
                ],
                'validation' => $validation,
                'total_rows' => count($data),
                'filename' => $file->getClientOriginalName()
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::warning('Validation error in preview import', ['errors' => $e->errors()]);
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Preview import error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error parsing file: ' . $e->getMessage()
            ], 500);
        }
    }
}
