@extends('layouts.authenticated-unified')

@section('title', 'Manajemen Karyawan')

@section('page-content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <div class="p-6 lg:p-8">
        <!-- Page Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Manajemen Karyawan</h1>
                    <p class="employee-page-desc">Kelola data karyawan, status, dan informasi kepegawaian</p>
                </div>
                <div class="flex items-center space-x-3">
                    @can('view_employees_analytics')
                    <button onclick="showEmployeeAnalytics()" class="btn-analytics">
                        <x-icons.analytics />
                        Analytics
                    </button>
                    @endcan
                    @can('export_employees_data')
                    <button onclick="exportEmployees()" class="btn-export">
                        <x-icons.download />
                        Ekspor Data
                    </button>
                    @endcan
                    @canany(['manage_employees', 'create_employees', 'import_employees_data'])
                    <button onclick="showImportModal()" class="btn-import">
                        <x-icons.upload />
                        Import Data
                    </button>
                    @endcanany
                    @can('create_employees')
                    <a href="{{ route('employees.create') }}" class="btn-create">
                        <x-icons.plus />
                        Tambah Karyawan
                    </a>
                    @endcan
                </div>
            </div>
        </div>

        <!-- Employee Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total Employees -->
            <x-ui.card class="employee-card">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-blue-600 rounded-lg shadow-md">
                        <x-icons.users />
                    </div>
                    <span class="employee-stat-badge employee-stat-badge-blue">Total</span>
                </div>
                <h3 class="employee-stat-value">{{ $statistics['total'] ?? 0 }}</h3>
                <p class="employee-stat-label">Total Karyawan</p>
                <div class="employee-stat-detail">
                    Semua departemen
                </div>
            </x-ui.card>

            <!-- Active Today -->
            <x-ui.card class="employee-card">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-green-600 rounded-lg shadow-md">
                        <x-icons.check-circle />
                    </div>
                    <span class="employee-stat-badge employee-stat-badge-green">Aktif</span>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-1">{{ $statistics['active_today'] ?? 0 }}</h3>
                <p class="employee-stat-label">Aktif Hari Ini</p>
                @if(($statistics['active_today'] ?? 0) > 0)
                <div class="mt-3">
                    <span class="employee-attendance-badge">
                        {{ round((($statistics['active_today'] ?? 0) / max($statistics['total'] ?? 1, 1)) * 100) }}% tingkat kehadiran
                    </span>
                </div>
                @endif
            </x-ui.card>

            <!-- Permanent Staff -->
            <x-ui.card class="employee-card">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-purple-600 rounded-lg shadow-md">
                        <x-icons.briefcase />
                    </div>
                    <span class="employee-stat-badge employee-stat-badge-purple">Tetap</span>
                </div>
                <h3 class="employee-stat-value">{{ $statistics['permanent'] ?? 0 }}</h3>
                <p class="employee-stat-label">Pegawai Tetap</p>
                <div class="employee-stat-detail">
                    Staff full-time
                </div>
            </x-ui.card>

            <!-- Honorary Teachers -->
            <x-ui.card class="employee-card">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-orange-600 rounded-lg shadow-md">
                        <x-icons.book-open />
                    </div>
                    <span class="employee-stat-badge employee-stat-badge-orange">Kontrak</span>
                </div>
                <h3 class="employee-stat-value">{{ $statistics['honorary'] ?? 0 }}</h3>
                <p class="employee-stat-label">Guru Honorer</p>
                <div class="employee-stat-detail">
                    Tenaga kontrak
                </div>
            </x-ui.card>
        </div>

        <!-- Employee Data Table -->
        <x-ui.card class="employee-card">
            <div class="employee-table-header">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="employee-table-title">Data Karyawan</h3>
                        <p class="employee-table-desc">Kelola dan pantau data karyawan</p>
                    </div>
                    <div class="flex items-center space-x-3">
                        <button onclick="refreshEmployeeData()" class="btn-action-small" title="Refresh Data">
                            <x-icons.refresh />
                        </button>
                        <button onclick="toggleTableSettings()" class="btn-action-small" title="Table Settings">
                            <x-icons.cog />
                        </button>
                    </div>
                </div>
            </div>
            <div class="p-6">
                <!-- Bulk Actions Toolbar -->
                <div id="bulkActionsToolbar" class="hidden employee-bulk-toolbar">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <span id="selectedCount" class="employee-bulk-count">0 item dipilih</span>
                            <button type="button" onclick="clearSelection()" class="employee-bulk-clear">
                                Batalkan pilihan
                            </button>
                        </div>
                        <div class="flex items-center space-x-2">
                            <button onclick="bulkExport()" class="btn-bulk-export">
                                <x-icons.download class="w-4 h-4 mr-1.5" />
                                Ekspor
                            </button>
                            <button onclick="bulkDelete()" class="btn-bulk-delete-light">
                                <x-icons.trash class="w-4 h-4 mr-1.5" />
                                Hapus
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Advanced Search and Filters -->
                <div class="space-y-4 mb-6">
                    <div class="flex flex-col lg:grid lg:grid-cols-4 gap-4">
                        <!-- Search Input -->
                        <div class="lg:col-span-2">
                            <div class="relative">
                                <input type="text" id="employee-search" 
                                       placeholder="Cari nama, email, atau ID karyawan..." 
                                       class="form-search">
                                <div class="absolute left-3 top-1/2 -translate-y-1/2">
                                    <x-icons.search />
                                </div>
                            </div>
                        </div>
                        
                        <!-- Department Filter -->
                        <div>
                            <select id="department-filter" class="form-filter">
                                <option value="">Semua Departemen</option>
                                @foreach($departments ?? [] as $dept)
                                    <option value="{{ $dept['id'] }}">{{ $dept['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <!-- Status Filter -->
                        <div>
                            <select id="status-filter" class="form-filter">
                                <option value="">Semua Status</option>
                                <option value="active">Aktif</option>
                                <option value="inactive">Tidak Aktif</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Table Header with Count -->
                <div class="employee-count-header">
                    <div class="flex items-center space-x-4">
                        <h3 class="employee-count-title">Data Karyawan</h3>
                        <div class="flex items-center space-x-2">
                            <div class="employee-count-badge employee-count-badge-blue">
                                <span class="employee-count-text employee-count-text-blue" id="total-employees">
                                    <x-icons.users class="w-4 h-4 inline mr-1" />
                                    Total: <span id="employee-count">0</span>
                                </span>
                            </div>
                            <div class="employee-count-badge employee-count-badge-green">
                                <span class="employee-count-text employee-count-text-green" id="active-employees">
                                    <x-icons.check-circle class="w-4 h-4 inline mr-1" />
                                    Aktif: <span id="active-count">0</span>
                                </span>
                            </div>
                            <div class="employee-count-badge employee-count-badge-gray">
                                <span class="employee-count-text employee-count-text-gray" id="inactive-employees">
                                    <x-icons.x class="w-4 h-4 inline mr-1" />
                                    Tidak Aktif: <span id="inactive-count">0</span>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="employee-view-info" id="current-view-info">Memuat data...</span>
                    </div>
                </div>

                <!-- Enhanced Table -->
                <div class="employee-table-container">
                    <table class="employee-table" id="employeesTable">
                        <thead class="employee-table-header-row">
                            <tr>
                                <th class="employee-table-th">
                                    <input type="checkbox" id="selectAll" class="h-4 w-4 rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500">
                                </th>
                                <th class="employee-table-th">Karyawan</th>
                                <th class="employee-table-th">Kontak</th>
                                <th class="employee-table-th">Status</th>
                                <th class="employee-table-th">Departemen</th>
                                <th class="px-6 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700"></tbody>
                    </table>
                </div>

                <!-- Enhanced Pagination -->
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-b-lg px-6 py-4">
                    <div class="flex flex-col sm:flex-row items-center justify-between space-y-3 sm:space-y-0">
                        <!-- Left side - Info and Page Size -->
                        <div class="flex items-center space-x-4">
                            <span class="text-sm text-gray-500 dark:text-gray-400" id="table-info">
                                Menampilkan data karyawan
                            </span>
                            <div class="flex items-center space-x-2">
                                <label class="text-sm text-gray-500 dark:text-gray-400">Tampilkan:</label>
                                <select id="page-length" class="form-select-small">
                                    <option value="10">10</option>
                                    <option value="25" selected>25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                                <span class="text-sm text-gray-500 dark:text-gray-400">data per halaman</span>
                            </div>
                        </div>
                        
                        <!-- Right side - Pagination Controls -->
                        <div class="flex items-center space-x-2">
                            <button id="prev-btn" disabled class="btn-pagination">
                                <x-icons.chevron-left class="w-4 h-4 mr-1" />
                                Sebelumnya
                            </button>
                            
                            <div class="flex items-center space-x-1" id="pagination-numbers">
                                <!-- Page numbers will be inserted here -->
                            </div>
                            
                            <button id="next-btn" class="btn-pagination">
                                Selanjutnya
                                <x-icons.chevron-right class="w-4 h-4 ml-1" />
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Bulk Actions Bar -->
                <div id="bulk-actions-bar" class="hidden mt-4 bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <span class="text-sm font-medium text-blue-900 dark:text-blue-100">
                                <span id="selected-count">0</span> karyawan dipilih
                            </span>
                            <button onclick="clearSelection()" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-200">
                                Batal pilih semua
                            </button>
                        </div>
                        <div class="flex items-center space-x-2">
                            @can('manage_employees')
                            <button onclick="confirmBulkDelete()" class="btn-bulk-delete">
                                <x-icons.trash class="w-4 h-4 mr-1" />
                                Hapus Terpilih
                            </button>
                            @endcan
                        </div>
                    </div>
                </div>
            </div>
        </x-ui.card>
    </div>
</div>

<!-- Import Modal -->
<div id="importModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 hidden">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-lg bg-white dark:bg-gray-800">
        <!-- Modal Header -->
        <div class="flex items-center justify-between pb-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Import Data Karyawan</h3>
            <button onclick="hideImportModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Modal Body -->
        <form id="importForm" action="{{ route('employees.import') }}" method="POST" enctype="multipart/form-data" class="mt-4">
            @csrf
            
            <!-- Template Download Section -->
            <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="text-sm font-medium text-green-900 dark:text-green-100 mb-1">📥 Download Template</h4>
                        <p class="text-xs text-green-700 dark:text-green-300">Download template Excel untuk panduan format yang benar</p>
                    </div>
                    <a href="{{ route('employees.template') }}" 
                       class="btn-success-small">
                        <x-icons.download class="w-4 h-4 mr-1" />
                        Download Template
                    </a>
                </div>
            </div>
            
            <!-- Instructions -->
            <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                <h4 class="text-sm font-medium text-blue-900 dark:text-blue-100 mb-3">📋 Petunjuk Import Data Karyawan:</h4>
                <ul class="text-sm text-blue-800 dark:text-blue-200 space-y-1">
                    <li>• Download template Excel/CSV terlebih dahulu</li>
                    <li>• Isi data sesuai format: 
                        <code class="text-xs bg-blue-100 dark:bg-blue-800 px-1 rounded">
                            full_name, email, phone, employee_type, role, salary_type, salary_amount, hourly_rate, hire_date, department, position, status
                        </code>
                    </li>
                    <li>• Employee type: <strong>permanent</strong>, <strong>honorary</strong>, atau <strong>staff</strong></li>
                    <li>• Role: <strong>super_admin</strong>, <strong>admin</strong>, <strong>kepala_sekolah</strong>, <strong>guru</strong>, atau <strong>pegawai</strong></li>
                    <li>• Salary type: <strong>monthly</strong>, <strong>hourly</strong>, atau <strong>fixed</strong></li>
                    <li>• Format tanggal: <strong>dd/mm/yyyy</strong> (contoh: 20/07/2025)</li>
                    <li>• Status: <strong>Aktif</strong> atau <strong>Tidak Aktif</strong></li>
                    <li>• Maksimal ukuran file: <strong>5MB</strong></li>
                    <li>• Password default: <code class="bg-blue-100 dark:bg-blue-800 px-1 rounded font-mono">password123</code></li>
                    <li>• Employee ID akan digenerate otomatis berdasarkan role & tipe</li>
                    <li>• Format email harus valid dan unik</li>
                </ul>
            </div>

            <!-- File Upload with Drag & Drop -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Upload File</label>
                <div id="dropZone" class="dropzone">
                    <input type="file" name="file" id="importFile" accept=".xlsx,.xls,.csv" required 
                           class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                    <div id="dropZoneContent">
                        <svg class="w-10 h-10 mx-auto mb-3 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"/>
                        </svg>
                        <p class="text-gray-600 dark:text-gray-400 mb-1">
                            <span class="font-medium">Klik untuk upload</span> atau drag & drop file
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-500">Excel (.xlsx, .xls) atau CSV (Max: 5MB)</p>
                    </div>
                    <div id="fileInfo" class="hidden">
                        <svg class="w-8 h-8 mx-auto mb-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p id="fileName" class="font-medium text-gray-900 dark:text-gray-100"></p>
                        <p id="fileSize" class="text-xs text-gray-500 dark:text-gray-400"></p>
                    </div>
                </div>
                <div id="fileError" class="mt-2 text-sm text-red-600 dark:text-red-400 hidden"></div>
            </div>

            <!-- Validation Options -->
            <div class="mb-6 p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200 dark:border-amber-800">
                <h4 class="text-sm font-medium text-amber-900 dark:text-amber-100 mb-3">⚙️ Opsi Import:</h4>
                <div class="space-y-2">
                    <label class="flex items-center">
                        <input type="checkbox" name="skip_duplicates" value="1" checked 
                               class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <span class="ml-2 text-sm text-amber-800 dark:text-amber-200">Skip email yang sudah ada (hindari duplikasi)</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="update_existing" value="1" 
                               class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <span class="ml-2 text-sm text-amber-800 dark:text-amber-200">Update data karyawan yang sudah ada (berdasarkan email)</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="send_welcome_email" value="1" 
                               class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <span class="ml-2 text-sm text-amber-800 dark:text-amber-200">Kirim email selamat datang ke karyawan baru</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="validate_only" value="1" id="validateOnly"
                               class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <span class="ml-2 text-sm text-amber-800 dark:text-amber-200">Validasi saja (preview tanpa menyimpan data)</span>
                    </label>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex justify-between items-center">
                <button type="button" onclick="previewImport()" id="previewBtn"
                        class="btn-preview">
                    <x-icons.eye class="w-4 h-4 inline mr-1" />
                    Preview
                </button>
                <div class="flex space-x-3">
                    <button type="button" onclick="hideImportModal()" 
                            class="px-4 py-2 text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-gray-600 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-500 transition-colors">
                        Batal
                    </button>
                    <button type="submit" id="importSubmitBtn"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                        <span id="importBtnText">Import Data</span>
                        <svg id="importBtnSpinner" class="w-4 h-4 inline ml-1 animate-spin hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal with Countdown -->
<div id="deleteConfirmModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 hidden">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-lg bg-white dark:bg-gray-800">
        <div class="flex items-center justify-between pb-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-red-900 dark:text-red-100">
                <svg class="w-6 h-6 inline mr-2 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 15.5c-.77.833.192 2.5 1.732 2.5z"/>
                </svg>
                Konfirmasi Hapus
            </h3>
            <button onclick="closeDeleteModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        
        <div class="py-6">
            <!-- Warning Icon and Message -->
            <div class="text-center mb-6">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 dark:bg-red-900/20 mb-4">
                    <svg class="h-8 w-8 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </div>
                
                <h4 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">Yakin ingin menghapus?</h4>
                <div id="deleteMessage" class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    <!-- Will be populated with employee info -->
                </div>
                
                <!-- Countdown Display -->
                <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4 mb-4">
                    <div class="flex items-center justify-center space-x-2">
                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="text-sm font-medium text-red-800 dark:text-red-200">
                            Waktu tersisa: 
                            <span id="countdownTimer" class="text-lg font-bold">10</span> 
                            detik
                        </span>
                    </div>
                    <div class="mt-2 bg-red-200 dark:bg-red-800 rounded-full h-2">
                        <div id="countdownProgress" class="bg-red-600 h-2 rounded-full transition-all duration-1000" style="width: 100%"></div>
                    </div>
                </div>
                
                <div class="text-xs text-red-600 dark:text-red-400 mb-4">
                    ⚠️ <strong>Peringatan:</strong> Data akan dihapus permanen dan tidak dapat dikembalikan!
                </div>
            </div>
        </div>
        
        <div class="flex justify-between space-x-3 pt-4 border-t border-gray-200 dark:border-gray-700">
            <button onclick="closeDeleteModal()" 
                    class="flex-1 px-4 py-2 text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-gray-600 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-500 transition-colors">
                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                Batal
            </button>
            <button id="confirmDeleteBtn" onclick="confirmDelete()" disabled
                    class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                <span id="deleteButtonText">Tunggu...</span>
            </button>
        </div>
    </div>
</div>

<!-- Success Modal for Delete Confirmation -->
<div id="deleteSuccessModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 hidden">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-lg bg-white dark:bg-gray-800">
        <div class="text-center">
            <!-- Success Icon with Animation -->
            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 dark:bg-green-900/20 mb-4 animate-bounce">
                <svg class="h-8 w-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">
                🎉 Berhasil Dihapus!
            </h3>
            
            <div id="successMessage" class="text-sm text-gray-600 dark:text-gray-400 mb-6">
                <!-- Will be populated with delete details -->
            </div>
            
            <!-- Celebration Elements -->
            <div class="flex justify-center space-x-2 mb-6">
                <span class="inline-block animate-pulse">✨</span>
                <span class="inline-block animate-bounce delay-100">🎉</span>
                <span class="inline-block animate-pulse delay-200">✨</span>
            </div>
            
            <button onclick="closeSuccessModal()" 
                    class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors focus:outline-none focus:ring-2 focus:ring-green-500">
                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Oke, Mengerti
            </button>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
/* Ensure smooth animations for countdown modal */
.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: .5;
    }
}

/* Progress bar smooth transitions */
#countdownProgress {
    transition: width 1s ease-in-out;
}

/* Modal backdrop blur */
.backdrop-blur-sm {
    backdrop-filter: blur(4px);
}

/* Success modal animations */
.animate-bounce {
    animation: bounce 1s infinite;
}

@keyframes bounce {
    0%, 20%, 53%, 80%, 100% {
        transform: translate3d(0,0,0);
    }
    40%, 43% {
        transform: translate3d(0, -10px, 0);
    }
    70% {
        transform: translate3d(0, -5px, 0);
    }
    90% {
        transform: translate3d(0, -2px, 0);
    }
}

/* Delay classes for celebration */
.delay-100 {
    animation-delay: 0.1s;
}

.delay-200 {
    animation-delay: 0.2s;
}
</style>
@endpush

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>

<script>
// Show Import Modal
function showImportModal() {
    document.getElementById('importModal').classList.remove('hidden');
}

// Hide Import Modal
function hideImportModal() {
    document.getElementById('importModal').classList.add('hidden');
}

// Enhanced notification system
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    
    const icons = {
        success: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
        error: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
        warning: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16c-.77.833.192 2.5 1.732 2.5z"/></svg>',
        info: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'
    };
    
    const styles = {
        success: 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-700 text-green-800 dark:text-green-200',
        error: 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-700 text-red-800 dark:text-red-200',
        warning: 'bg-amber-50 dark:bg-amber-900/20 border-amber-200 dark:border-amber-700 text-amber-800 dark:text-amber-200',
        info: 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-700 text-blue-800 dark:text-blue-200'
    };
    
    notification.className = `fixed top-4 right-4 z-50 max-w-sm w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-lg transform translate-x-full transition-all duration-300 ease-out`;
    notification.innerHTML = `
        <div class="p-4">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <div class="p-1 ${styles[type]} rounded-lg">
                        ${icons[type]}
                    </div>
                </div>
                <div class="ml-3 w-0 flex-1">
                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">${message}</p>
                </div>
                <div class="ml-4 flex-shrink-0 flex">
                    <button onclick="this.closest('.fixed').remove()" class="inline-flex text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 focus:outline-none transition-colors">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 100);
    
    const duration = type === 'error' ? 5000 : 3000;
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 300);
    }, duration);
}

$(document).ready(function() {
    // Initialize DataTable
    if (!$.fn.dataTable.isDataTable('#employeesTable')) {
        window.employeesTable = $('#employeesTable').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            dom: 't',
            pageLength: 25,
            ajax: {
                url: '{{ route("employees.data") }}',
                type: 'GET',
                data: function(d) {
                    d.search_value = $('#employee-search').val();
                    d.department = $('#department-filter').val();
                    d.status = $('#status-filter').val();
                },
                dataSrc: function(json) {
                    console.log('DataTable response:', json);
                    
                    // Update employee count and statistics
                    updateEmployeeStatistics(json);
                    
                    // Update pagination info
                    updatePaginationInfo(json);
                    
                    return json.data;
                },
                error: function(xhr, error, code) {
                    console.error('DataTable AJAX Error:', {
                        xhr: xhr,
                        error: error, 
                        code: code,
                        response: xhr.responseText
                    });
                    
                    showNotification('Error loading employee data: ' + error, 'error');
                }
            },
            drawCallback: function(settings) {
                // Update pagination controls
                updatePaginationControls(this.api());
                
                // Update selection handlers
                initializeRowSelections();
                
                // Update bulk actions
                updateBulkActionsVisibility();
            },
            columns: [
                {
                    data: 'id',
                    name: 'checkbox',
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row) {
                        return `<input type="checkbox" class="row-checkbox h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500" value="${data}">`;
                    }
                },
                { 
                    data: 'name', 
                    name: 'name',
                    render: function(data, type, row) {
                        const photoUrl = row.photo || `https://ui-avatars.com/api/?name=${encodeURIComponent(row.name)}&color=3b82f6&background=dbeafe`;
                        return `<div class="flex items-center py-3">
                            <div class="flex-shrink-0">
                                <img src="${photoUrl}" alt="${row.name}" class="w-10 h-10 rounded-full object-cover border-2 border-gray-200 dark:border-gray-600">
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">${data}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 font-mono">ID: ${row.employee_id}</div>
                            </div>
                        </div>`;
                    }
                },
                { 
                    data: 'email', 
                    name: 'email',
                    render: function(data, type, row) {
                        return `<div class="py-3">
                            <div class="text-sm text-gray-900 dark:text-gray-100">${data}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">${row.phone || 'Tidak ada telepon'}</div>
                        </div>`;
                    }
                },
                { 
                    data: 'status', 
                    name: 'status',
                    render: function(data, type, row) {
                        const statusLabel = data === 'active' ? 'Aktif' : 'Tidak Aktif';
                        const statusColor = data === 'active' 
                            ? 'bg-green-50 text-green-700 ring-green-600/20' 
                            : 'bg-red-50 text-red-700 ring-red-600/20';
                        
                        return `<div class="flex flex-col">
                            <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium ring-1 ring-inset ${statusColor}">${statusLabel}</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400 mt-1">${row.employee_type === 'permanent' ? 'Tetap' : row.employee_type === 'honorary' ? 'Honorer' : 'Part Time'}</span>
                        </div>`;
                    }
                },
                { 
                    data: 'department', 
                    name: 'department',
                    render: function(data, type, row) {
                        return `<div class="py-3">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                ${data || 'Belum diatur'}
                            </span>
                        </div>`;
                    }
                },
                {
                    data: 'actions',
                    name: 'actions',
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row) {
                        let editUrl = `/employees/${row.id}/edit`;
                        return `
                            <div class="flex items-center justify-end space-x-1 py-3">
                                <button onclick="window.location.href='${editUrl}'" 
                                        class="p-2 text-gray-400 hover:text-green-600 dark:hover:text-green-400 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-lg transition-colors" 
                                        title="Edit Karyawan">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </button>
                                <button onclick="deleteEmployee('${row.id}', event)" 
                                        class="p-2 text-gray-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors" 
                                        title="Hapus Karyawan">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>`;
                    }
                }
            ],
            order: [[1, 'asc']],
            language: { 
                processing: "Memuat data...",
                emptyTable: "Tidak ada data karyawan"
            }
        });

        // Enhanced filter handlers
        $('#employee-search').on('input', function() {
            window.employeesTable.ajax.reload();
        });
        
        $('#department-filter').on('change', function() {
            window.employeesTable.ajax.reload();
        });
        
        $('#status-filter').on('change', function() {
            window.employeesTable.ajax.reload();
        });
        
        // Page length change handler
        $('#page-length').on('change', function() {
            const newLength = parseInt($(this).val());
            window.employeesTable.page.len(newLength).draw();
        });
        
        // Pagination button handlers
        $('#prev-btn').on('click', function() {
            if (!$(this).prop('disabled')) {
                window.employeesTable.page('previous').draw('page');
            }
        });
        
        $('#next-btn').on('click', function() {
            if (!$(this).prop('disabled')) {
                window.employeesTable.page('next').draw('page');
            }
        });
    }

    // Bulk selection
    $(document).on('change', '#selectAll', function() {
        $('.row-checkbox').prop('checked', this.checked);
        updateBulkActionsToolbar();
    });

    $(document).on('change', '.row-checkbox', function() {
        updateBulkActionsToolbar();
    });
});

// Update bulk actions toolbar
function updateBulkActionsToolbar() {
    const checkedRows = $('.row-checkbox:checked').length;
    const toolbar = $('#bulkActionsToolbar');
    const countSpan = $('#selectedCount');
    
    if (checkedRows > 0) {
        toolbar.removeClass('hidden');
        countSpan.text(`${checkedRows} item dipilih`);
    } else {
        toolbar.addClass('hidden');
    }
}

// Clear selection
function clearSelection() {
    $('.row-checkbox, #selectAll').prop('checked', false);
    updateBulkActionsToolbar();
    updateBulkActionsVisibility();
}

// Employee Statistics Functions
function updateEmployeeStatistics(data) {
    // Update total count
    const totalCount = data.recordsTotal || 0;
    $('#employee-count').text(totalCount);
    
    // Update filtered view info
    const filteredCount = data.recordsFiltered || 0;
    const viewInfo = filteredCount !== totalCount ? 
        `Menampilkan ${filteredCount} dari ${totalCount} karyawan` : 
        `${totalCount} karyawan`;
    $('#current-view-info').text(viewInfo);
    
    // Update status counts if available
    if (data.stats) {
        $('#active-count').text(data.stats.active || 0);
        $('#inactive-count').text(data.stats.inactive || 0);
    } else {
        // Fallback: calculate from current page data
        const activeCount = data.data.filter(emp => emp.is_active).length;
        const inactiveCount = data.data.filter(emp => !emp.is_active).length;
        $('#active-count').text(activeCount);
        $('#inactive-count').text(inactiveCount);
    }
}

function updatePaginationInfo(data) {
    const start = data.start + 1;
    const end = Math.min(data.start + data.length, data.recordsFiltered);
    const total = data.recordsFiltered;
    
    const info = `Menampilkan ${start} - ${end} dari ${total} data`;
    $('#table-info').text(info);
}

function updatePaginationControls(api) {
    const info = api.page.info();
    const currentPage = info.page + 1;
    const totalPages = info.pages;
    
    // Update prev/next buttons
    $('#prev-btn').prop('disabled', currentPage === 1);
    $('#next-btn').prop('disabled', currentPage === totalPages);
    
    // Generate page numbers
    generatePageNumbers(currentPage, totalPages);
}

function generatePageNumbers(currentPage, totalPages) {
    const container = $('#pagination-numbers');
    container.empty();
    
    if (totalPages <= 1) return;
    
    const maxVisible = 5;
    let startPage = Math.max(1, currentPage - Math.floor(maxVisible / 2));
    let endPage = Math.min(totalPages, startPage + maxVisible - 1);
    
    // Adjust start if we're near the end
    if (endPage - startPage + 1 < maxVisible) {
        startPage = Math.max(1, endPage - maxVisible + 1);
    }
    
    // Add first page and ellipsis if needed
    if (startPage > 1) {
        addPageButton(container, 1, currentPage);
        if (startPage > 2) {
            container.append('<span class="px-2 text-gray-500">...</span>');
        }
    }
    
    // Add visible pages
    for (let i = startPage; i <= endPage; i++) {
        addPageButton(container, i, currentPage);
    }
    
    // Add ellipsis and last page if needed
    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            container.append('<span class="px-2 text-gray-500">...</span>');
        }
        addPageButton(container, totalPages, currentPage);
    }
}

function addPageButton(container, pageNum, currentPage) {
    const isActive = pageNum === currentPage;
    const classes = isActive ? 
        'inline-flex items-center px-3 py-2 text-sm font-medium text-white bg-blue-600 border border-blue-600 rounded-lg' :
        'inline-flex items-center px-3 py-2 text-sm font-medium text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors';
    
    const button = $(`<button class="${classes}" ${isActive ? 'disabled' : ''}>${pageNum}</button>`);
    
    if (!isActive) {
        button.on('click', function() {
            window.employeesTable.page(pageNum - 1).draw('page');
        });
    }
    
    container.append(button);
}

function initializeRowSelections() {
    // Handle individual checkbox changes
    $('.row-checkbox').off('change').on('change', function() {
        updateBulkActionsVisibility();
    });
    
    // Handle select all checkbox
    $('#selectAll').off('change').on('change', function() {
        const isChecked = $(this).is(':checked');
        $('.row-checkbox').prop('checked', isChecked);
        updateBulkActionsVisibility();
    });
}

function updateBulkActionsVisibility() {
    const selectedCount = $('.row-checkbox:checked').length;
    const bulkActionsBar = $('#bulk-actions-bar');
    const selectedCountSpan = $('#selected-count');
    
    selectedCountSpan.text(selectedCount);
    
    if (selectedCount > 0) {
        bulkActionsBar.removeClass('hidden');
    } else {
        bulkActionsBar.addClass('hidden');
    }
    
    // Update select all checkbox state
    const totalVisible = $('.row-checkbox').length;
    const selectAllCheckbox = $('#selectAll');
    
    if (selectedCount === 0) {
        selectAllCheckbox.prop('indeterminate', false).prop('checked', false);
    } else if (selectedCount === totalVisible) {
        selectAllCheckbox.prop('indeterminate', false).prop('checked', true);
    } else {
        selectAllCheckbox.prop('indeterminate', true).prop('checked', false);
    }
}

function confirmBulkDelete() {
    const selectedEmployees = [];
    $('.row-checkbox:checked').each(function() {
        const row = $(this).closest('tr');
        const data = window.employeesTable.row(row).data();
        selectedEmployees.push({
            id: $(this).val(),
            name: data.name || data.full_name || 'Unknown',
            email: data.email || 'No email'
        });
    });
    
    if (selectedEmployees.length === 0) {
        showNotification('Pilih karyawan yang ingin dihapus', 'warning');
        return;
    }
    
    showDeleteModal(selectedEmployees, 'bulk');
}

// Delete employee function
async function deleteEmployee(id, event) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    // Get employee data from DataTable row
    try {
        const table = $('#employeesTable').DataTable();
        const rows = table.rows().data();
        let employeeData = null;
        
        // Find the employee data by ID
        for (let i = 0; i < rows.length; i++) {
            if (rows[i].id == id) {
                employeeData = {
                    id: id,
                    name: rows[i].full_name || rows[i].name || 'Unknown Employee',
                    email: rows[i].email || 'No email'
                };
                break;
            }
        }
        
        // Fallback if not found in DataTable
        if (!employeeData) {
            employeeData = {
                id: id,
                name: 'Unknown Employee',
                email: 'No email'
            };
        }
        
        // Show countdown modal instead of simple confirm
        showDeleteModal(employeeData, 'single');
        
    } catch (error) {
        console.error('Error getting employee data:', error);
        // Fallback to simple modal with basic info
        showDeleteModal({
            id: id,
            name: 'Unknown Employee',
            email: 'No email'
        }, 'single');
    }
}

// Bulk delete - now uses countdown modal
async function bulkDelete() {
    const selectedCheckboxes = $('.row-checkbox:checked');
    
    if (selectedCheckboxes.length === 0) {
        showNotification('Pilih karyawan yang akan dihapus', 'warning');
        return;
    }
    
    // Get employee data for selected items
    const selectedEmployees = [];
    selectedCheckboxes.each(function() {
        const row = $(this).closest('tr');
        const employeeData = {
            id: this.value,
            name: row.find('.employee-name').text().trim() || 'Unknown Employee',
            email: row.find('.employee-email').text().trim() || 'No email'
        };
        selectedEmployees.push(employeeData);
    });
    
    // Show countdown modal for bulk delete
    showDeleteModal(selectedEmployees, 'bulk');
}

// Export functions
function exportEmployees() {
    window.location.href = '{{ route("employees.export") }}';
}

function bulkExport() {
    const selectedIds = $('.row-checkbox:checked').map(function() {
        return this.value;
    }).get();
    
    if (selectedIds.length === 0) {
        showNotification('Pilih karyawan yang akan diekspor', 'warning');
        return;
    }
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '{{ route("employees.bulk-export") }}';
    
    const csrfToken = document.createElement('input');
    csrfToken.type = 'hidden';
    csrfToken.name = '_token';
    csrfToken.value = '{{ csrf_token() }}';
    form.appendChild(csrfToken);
    
    selectedIds.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'employee_ids[]';
        input.value = id;
        form.appendChild(input);
    });
    
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}

function refreshEmployeeData() {
    $('#employeesTable').DataTable().ajax.reload();
    showNotification('Data karyawan berhasil diperbarui', 'success');
}

function showEmployeeAnalytics() {
    showNotification('Membuka analytics karyawan...', 'info');
}

function toggleTableSettings() {
    showNotification('Membuka pengaturan tabel...', 'info');
}

// Enhanced Import Modal Functions
document.addEventListener('DOMContentLoaded', function() {
    initializeImportModal();
    
    // Handle mutual exclusivity of skip_duplicates and update_existing
    const skipDuplicates = document.querySelector('input[name="skip_duplicates"]');
    const updateExisting = document.querySelector('input[name="update_existing"]');
    
    if (skipDuplicates && updateExisting) {
        skipDuplicates.addEventListener('change', function() {
            if (this.checked) {
                updateExisting.checked = false;
            }
        });
        
        updateExisting.addEventListener('change', function() {
            if (this.checked) {
                skipDuplicates.checked = false;
            }
        });
    }
});

function initializeImportModal() {
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('importFile');
    const dropZoneContent = document.getElementById('dropZoneContent');
    const fileInfo = document.getElementById('fileInfo');
    const fileName = document.getElementById('fileName');
    const fileSize = document.getElementById('fileSize');
    const fileError = document.getElementById('fileError');
    
    // Drag and drop events
    dropZone.addEventListener('dragover', function(e) {
        e.preventDefault();
        dropZone.classList.add('border-blue-500', 'bg-blue-50', 'dark:bg-blue-900/10');
    });
    
    dropZone.addEventListener('dragleave', function(e) {
        e.preventDefault();
        dropZone.classList.remove('border-blue-500', 'bg-blue-50', 'dark:bg-blue-900/10');
    });
    
    dropZone.addEventListener('drop', function(e) {
        e.preventDefault();
        dropZone.classList.remove('border-blue-500', 'bg-blue-50', 'dark:bg-blue-900/10');
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            fileInput.files = files;
            handleFileSelection(files[0]);
        }
    });
    
    // File input change
    fileInput.addEventListener('change', function(e) {
        if (e.target.files.length > 0) {
            handleFileSelection(e.target.files[0]);
        }
    });
    
    // Import form submission
    document.getElementById('importForm').addEventListener('submit', function(e) {
        e.preventDefault();
        handleImportSubmission();
    });
}

function handleFileSelection(file) {
    const dropZoneContent = document.getElementById('dropZoneContent');
    const fileInfo = document.getElementById('fileInfo');
    const fileName = document.getElementById('fileName');
    const fileSize = document.getElementById('fileSize');
    const fileError = document.getElementById('fileError');
    const importSubmitBtn = document.getElementById('importSubmitBtn');
    const previewBtn = document.getElementById('previewBtn');
    
    // Reset error state
    fileError.classList.add('hidden');
    fileError.textContent = '';
    
    // Validate file type
    const allowedTypes = [
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // .xlsx
        'application/vnd.ms-excel', // .xls
        'text/csv' // .csv
    ];
    
    if (!allowedTypes.includes(file.type) && !file.name.match(/\.(xlsx|xls|csv)$/i)) {
        showFileError('File harus berformat Excel (.xlsx, .xls) atau CSV');
        return;
    }
    
    // Validate file size (5MB max)
    const maxSize = 5 * 1024 * 1024; // 5MB in bytes
    if (file.size > maxSize) {
        showFileError('Ukuran file terlalu besar. Maksimal 5MB');
        return;
    }
    
    // Show file info
    dropZoneContent.classList.add('hidden');
    fileInfo.classList.remove('hidden');
    fileName.textContent = file.name;
    fileSize.textContent = formatFileSize(file.size);
    
    // Enable buttons
    importSubmitBtn.disabled = false;
    previewBtn.disabled = false;
    
    showNotification('File berhasil dipilih: ' + file.name, 'success');
}

function showFileError(message) {
    const fileError = document.getElementById('fileError');
    const importSubmitBtn = document.getElementById('importSubmitBtn');
    const previewBtn = document.getElementById('previewBtn');
    
    fileError.textContent = message;
    fileError.classList.remove('hidden');
    
    // Disable buttons
    importSubmitBtn.disabled = true;
    previewBtn.disabled = true;
    
    showNotification(message, 'error');
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function previewImport() {
    const fileInput = document.getElementById('importFile');
    
    if (!fileInput.files.length) {
        showNotification('Pilih file terlebih dahulu', 'warning');
        return;
    }
    
    const file = fileInput.files[0];
    
    // Client-side basic validation first
    const allowedTypes = [
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-excel', 
        'text/csv'
    ];
    
    if (!allowedTypes.includes(file.type) && !file.name.match(/\.(xlsx|xls|csv)$/i)) {
        showNotification('File harus berformat Excel (.xlsx, .xls) atau CSV', 'error');
        return;
    }
    
    const formData = new FormData();
    formData.append('file', file);
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
    
    // Show loading state
    const previewBtn = document.getElementById('previewBtn');
    const originalText = previewBtn.innerHTML;
    previewBtn.disabled = true;
    previewBtn.innerHTML = `
        <svg class="w-4 h-4 inline mr-1 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
        </svg>
        Memproses...
    `;
    
    const previewUrl = '{{ route("employees.preview-import") }}';
    
    fetch(previewUrl, {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => {
        if (!response.ok) {
            if (response.status === 404) {
                throw new Error('Preview endpoint not found');
            }
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            showImportPreview(data.preview || {});
        } else {
            showNotification(data.message || 'Gagal memproses file', 'error');
            if (data.errors) {
                showValidationErrors(data.errors);
            }
        }
    })
    .catch(error => {
        console.error('Preview Error:', error);
        
        if (error.message.includes('Preview endpoint not found')) {
            // Fallback: Show basic file info without server-side validation
            showBasicFilePreview(file);
        } else {
            showNotification('Terjadi kesalahan saat memproses file. Pastikan format file sesuai template.', 'error');
        }
    })
    .finally(() => {
        previewBtn.disabled = false;
        previewBtn.innerHTML = originalText;
    });
}

function showBasicFilePreview(file) {
    const basicPreview = {
        total_rows: 'Unknown',
        valid_rows: 'Unknown', 
        error_rows: 'Unknown',
        errors: {},
        sample_data: []
    };
    
    const previewModal = document.createElement('div');
    previewModal.className = 'fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50';
    previewModal.innerHTML = `
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-1/2 shadow-lg rounded-lg bg-white dark:bg-gray-800">
            <div class="flex items-center justify-between pb-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">File Information</h3>
                <button onclick="this.closest('.fixed').remove()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <div class="py-4">
                <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg mb-4">
                    <h4 class="font-medium text-blue-900 dark:text-blue-100 mb-2">File Details</h4>
                    <div class="text-sm text-blue-800 dark:text-blue-200">
                        <p><strong>Nama:</strong> ${file.name}</p>
                        <p><strong>Ukuran:</strong> ${formatFileSize(file.size)}</p>
                        <p><strong>Tipe:</strong> ${file.type || 'Unknown'}</p>
                    </div>
                </div>
                
                <div class="bg-amber-50 dark:bg-amber-900/20 p-4 rounded-lg">
                    <h4 class="font-medium text-amber-900 dark:text-amber-100 mb-2">⚠️ Catatan</h4>
                    <p class="text-sm text-amber-800 dark:text-amber-200">
                        Preview server tidak tersedia. File akan divalidasi saat import. 
                        Pastikan format file sesuai dengan template yang telah didownload.
                    </p>
                </div>
            </div>
            
            <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                <button onclick="this.closest('.fixed').remove()" class="px-4 py-2 text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-gray-600 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-500">
                    Tutup
                </button>
                <button onclick="this.closest('.fixed').remove(); proceedWithImport();" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Lanjutkan Import
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(previewModal);
    showNotification('Preview dasar file berhasil. Validasi penuh akan dilakukan saat import.', 'info');
}

function showImportPreview(preview) {
    // Ensure preview object has default values
    const safePreview = {
        total_rows: preview.total_rows || 0,
        valid_rows: preview.valid_rows || 0,
        error_rows: preview.error_rows || 0,
        errors: preview.errors || {},
        sample_data: preview.sample_data || [],
        ...preview
    };
    
    // Create preview modal
    const previewModal = document.createElement('div');
    previewModal.className = 'fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50';
    previewModal.innerHTML = `
        <div class="relative top-10 mx-auto p-5 border w-11/12 md:w-4/5 lg:w-3/4 shadow-lg rounded-lg bg-white dark:bg-gray-800 max-h-[90vh] overflow-hidden flex flex-col">
            <div class="flex items-center justify-between pb-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Preview Import Data</h3>
                <button onclick="this.closest('.fixed').remove()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <div class="flex-1 overflow-y-auto py-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                        <h4 class="font-medium text-blue-900 dark:text-blue-100">Total Baris</h4>
                        <p class="text-2xl font-bold text-blue-600">${safePreview.total_rows}</p>
                    </div>
                    <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
                        <h4 class="font-medium text-green-900 dark:text-green-100">Valid</h4>
                        <p class="text-2xl font-bold text-green-600">${safePreview.valid_rows}</p>
                    </div>
                    <div class="bg-red-50 dark:bg-red-900/20 p-4 rounded-lg">
                        <h4 class="font-medium text-red-900 dark:text-red-100">Error</h4>
                        <p class="text-2xl font-bold text-red-600">${safePreview.error_rows}</p>
                    </div>
                </div>
                
                ${safePreview.errors && Object.keys(safePreview.errors).length > 0 ? `
                    <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                        <h4 class="font-medium text-red-900 dark:text-red-100 mb-2">Error Validasi:</h4>
                        <div class="text-sm text-red-800 dark:text-red-200 max-h-40 overflow-y-auto">
                            ${Object.entries(safePreview.errors).map(([row, errors]) => 
                                `<div class="mb-1"><strong>${row}:</strong> ${Array.isArray(errors) ? errors.join(', ') : errors}</div>`
                            ).join('')}
                        </div>
                    </div>
                ` : ''}
                
                ${safePreview.sample_data && safePreview.sample_data.length > 0 ? `
                    <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                        <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-2">Sample Data (${Math.min(safePreview.sample_data.length, 5)} baris pertama):</h4>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-xs">
                                <thead class="bg-gray-100 dark:bg-gray-600">
                                    <tr>
                                        <th class="px-2 py-1 text-left">Nama</th>
                                        <th class="px-2 py-1 text-left">Email</th>
                                        <th class="px-2 py-1 text-left">Tipe</th>
                                        <th class="px-2 py-1 text-left">Role</th>
                                        <th class="px-2 py-1 text-left">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="text-gray-900 dark:text-gray-100">
                                    ${safePreview.sample_data.slice(0, 5).map((row, index) => `
                                        <tr class="border-b border-gray-200 dark:border-gray-600">
                                            <td class="px-2 py-1">${row.full_name || row['Nama Lengkap'] || '-'}</td>
                                            <td class="px-2 py-1">${row.email || row['Email'] || '-'}</td>
                                            <td class="px-2 py-1">${row.employee_type || row['Tipe Karyawan'] || '-'}</td>
                                            <td class="px-2 py-1">${row.role || row['Role'] || '-'}</td>
                                            <td class="px-2 py-1">
                                                <span class="px-2 py-1 text-xs rounded bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-200">
                                                    Baris ${index + 2}
                                                </span>
                                            </td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                ` : `
                    <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg text-center">
                        <div class="text-gray-500 dark:text-gray-400">
                            <svg class="w-12 h-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <p class="font-medium">Tidak ada data sample untuk ditampilkan</p>
                            <p class="text-sm">File mungkin kosong atau format tidak sesuai</p>
                        </div>
                    </div>
                `}
            </div>
            
            <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                <button onclick="this.closest('.fixed').remove()" class="px-4 py-2 text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-gray-600 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-500">
                    Tutup
                </button>
                ${safePreview.valid_rows > 0 ? `
                    <button onclick="this.closest('.fixed').remove(); proceedWithImport();" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Lanjutkan Import (${safePreview.valid_rows} data)
                    </button>
                ` : ''}
            </div>
        </div>
    `;
    
    document.body.appendChild(previewModal);
}

function proceedWithImport() {
    document.getElementById('validateOnly').checked = false;
    document.getElementById('importForm').submit();
}

function handleImportSubmission() {
    const submitBtn = document.getElementById('importSubmitBtn');
    const btnText = document.getElementById('importBtnText');
    const btnSpinner = document.getElementById('importBtnSpinner');
    
    // Show loading state
    submitBtn.disabled = true;
    btnText.textContent = 'Mengimpor...';
    btnSpinner.classList.remove('hidden');
    
    // Submit form normally - let Laravel handle the processing
    document.getElementById('importForm').submit();
}

function showValidationErrors(errors) {
    let errorMessage = 'Ditemukan kesalahan validasi:\n\n';
    Object.entries(errors).forEach(([row, rowErrors]) => {
        errorMessage += `${row}: ${rowErrors.join(', ')}\n`;
    });
    
    const errorModal = document.createElement('div');
    errorModal.className = 'fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50';
    errorModal.innerHTML = `
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-1/2 shadow-lg rounded-lg bg-white dark:bg-gray-800">
            <div class="flex items-center justify-between pb-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-red-900 dark:text-red-100">Error Validasi</h3>
                <button onclick="this.closest('.fixed').remove()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="mt-4 max-h-96 overflow-y-auto">
                <pre class="text-sm text-red-800 dark:text-red-200 whitespace-pre-wrap">${errorMessage}</pre>
            </div>
            <div class="flex justify-end pt-4">
                <button onclick="this.closest('.fixed').remove()" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                    Tutup
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(errorModal);
}

// ========== DELETE CONFIRMATION MODAL WITH COUNTDOWN ==========

// Wrap in namespace to avoid conflicts
window.EmployeeDelete = {
    countdown: null,
    target: null,
    type: 'single', // 'single' or 'bulk'
    successTimer: null
};

function resetDeleteModal() {
    // Find countdown container and reset it to initial state
    const countdownContainer = document.querySelector('#deleteConfirmModal .bg-red-50');
    if (countdownContainer) {
        countdownContainer.innerHTML = `
            <div class="flex items-center justify-center space-x-2">
                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-sm font-medium text-red-800 dark:text-red-200">
                    Waktu tersisa: 
                    <span id="countdownTimer" class="text-lg font-bold">10</span> 
                    detik
                </span>
            </div>
            <div class="mt-2 bg-red-200 dark:bg-red-800 rounded-full h-2">
                <div id="countdownProgress" class="bg-red-600 h-2 rounded-full transition-all duration-1000" style="width: 100%"></div>
            </div>
        `;
    }
    
    // Reset button state
    const confirmBtn = document.getElementById('confirmDeleteBtn');
    const buttonText = document.getElementById('deleteButtonText');
    
    if (confirmBtn) {
        confirmBtn.disabled = true;
        confirmBtn.classList.remove('animate-pulse');
    }
    
    if (buttonText) {
        buttonText.textContent = 'Tunggu...';
    }
}

function showDeleteModal(employeeData, type = 'single') {
    // Check if success modal is still open
    const successModal = document.getElementById('deleteSuccessModal');
    if (successModal && !successModal.classList.contains('hidden')) {
        // Close success modal first, then show delete modal after delay
        closeSuccessModal();
        setTimeout(() => showDeleteModal(employeeData, type), 300);
        return;
    }
    
    // Clear any existing countdown
    if (window.EmployeeDelete.countdown) {
        clearInterval(window.EmployeeDelete.countdown);
        window.EmployeeDelete.countdown = null;
    }
    
    window.EmployeeDelete.target = employeeData;
    window.EmployeeDelete.type = type;
    
    const modal = document.getElementById('deleteConfirmModal');
    const messageEl = document.getElementById('deleteMessage');
    
    // Reset modal to initial state before showing
    resetDeleteModal();
    
    // Set message based on delete type
    if (type === 'single') {
        // Safe extraction of employee data
        const employeeName = employeeData.name || employeeData.full_name || 'Unknown Employee';
        const employeeEmail = employeeData.email || 'No email';
        
        // Generate initials safely
        let initials = 'UN';
        try {
            if (employeeName && typeof employeeName === 'string') {
                const nameParts = employeeName.trim().split(' ').filter(part => part.length > 0);
                initials = nameParts.map(part => part[0]).join('').substring(0, 2).toUpperCase();
            }
        } catch (e) {
            console.warn('Error generating initials:', e);
            initials = 'UN';
        }
        
        messageEl.innerHTML = `
            <div class="flex items-center justify-center space-x-3 mb-2">
                <div class="w-10 h-10 bg-gray-200 dark:bg-gray-600 rounded-full flex items-center justify-center">
                    <span class="text-sm font-medium text-gray-600 dark:text-gray-300">
                        ${initials}
                    </span>
                </div>
                <div class="text-left">
                    <div class="font-medium text-gray-900 dark:text-gray-100">${employeeName}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">${employeeEmail}</div>
                </div>
            </div>
        `;
    } else {
        const count = employeeData.length;
        // Safe name extraction for bulk display
        const displayNames = employeeData.slice(0, 3).map(emp => {
            return emp.name || emp.full_name || 'Unknown Employee';
        });
        
        messageEl.innerHTML = `
            <div class="text-center">
                <div class="font-medium text-gray-900 dark:text-gray-100">${count} karyawan akan dihapus</div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    ${displayNames.join(', ')}
                    ${count > 3 ? ` dan ${count - 3} lainnya` : ''}
                </div>
            </div>
        `;
    }
    
    modal.classList.remove('hidden');
    
    // Small delay to ensure DOM is updated after reset
    setTimeout(() => {
        startCountdown();
    }, 50);
}

function startCountdown(isRetry = false) {
    let timeLeft = 10;
    const timerEl = document.getElementById('countdownTimer');
    const progressEl = document.getElementById('countdownProgress');
    const confirmBtn = document.getElementById('confirmDeleteBtn');
    const buttonText = document.getElementById('deleteButtonText');
    
    // Check if all required elements exist
    if (!timerEl || !progressEl || !confirmBtn || !buttonText) {
        console.error('Countdown elements not found:', {
            timerEl: !!timerEl,
            progressEl: !!progressEl,
            confirmBtn: !!confirmBtn,
            buttonText: !!buttonText
        });
        
        // Try to reset modal and retry once (prevent infinite recursion)
        if (!isRetry) {
            console.log('Attempting to reset modal and retry...');
            resetDeleteModal();
            setTimeout(() => {
                const retryTimerEl = document.getElementById('countdownTimer');
                const retryProgressEl = document.getElementById('countdownProgress');
                const retryConfirmBtn = document.getElementById('confirmDeleteBtn');
                const retryButtonText = document.getElementById('deleteButtonText');
                
                if (retryTimerEl && retryProgressEl && retryConfirmBtn && retryButtonText) {
                    console.log('Retry successful, starting countdown');
                    startCountdown(true); // Mark as retry to prevent recursion
                } else {
                    console.error('Retry failed, elements still missing');
                }
            }, 100);
        } else {
            console.error('Already retried once, giving up');
        }
        return;
    }
    
    console.log('All countdown elements found, starting countdown');
    
    // Reset button state
    confirmBtn.disabled = true;
    buttonText.textContent = 'Tunggu...';
    
    // Update initial display
    timerEl.textContent = timeLeft;
    progressEl.style.width = '100%';
    
    window.EmployeeDelete.countdown = setInterval(() => {
        timeLeft--;
        
        // Verify elements still exist before updating
        const currentTimerEl = document.getElementById('countdownTimer');
        const currentProgressEl = document.getElementById('countdownProgress');
        const currentConfirmBtn = document.getElementById('confirmDeleteBtn');
        const currentButtonText = document.getElementById('deleteButtonText');
        
        if (!currentTimerEl || !currentProgressEl || !currentConfirmBtn || !currentButtonText) {
            console.warn('Countdown elements missing during interval, clearing countdown');
            clearInterval(window.EmployeeDelete.countdown);
            return;
        }
        
        currentTimerEl.textContent = timeLeft;
        
        // Update progress bar
        const percentage = (timeLeft / 10) * 100;
        currentProgressEl.style.width = percentage + '%';
        
        // Change colors as time runs out
        if (timeLeft <= 3) {
            currentProgressEl.classList.remove('bg-red-600');
            currentProgressEl.classList.add('bg-red-700');
            currentTimerEl.classList.add('text-red-700', 'animate-pulse');
        }
        
        if (timeLeft <= 0) {
            clearInterval(window.EmployeeDelete.countdown);
            
            // Enable delete button
            currentConfirmBtn.disabled = false;
            currentButtonText.textContent = 'Hapus Sekarang';
            currentConfirmBtn.classList.add('animate-pulse');
            
            // Hide countdown, show ready message
            const timerParent = currentTimerEl.parentElement;
            if (timerParent) {
                timerParent.innerHTML = `
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span class="text-sm font-medium text-green-800 dark:text-green-200">
                        Siap untuk dihapus
                    </span>
                `;
            }
            currentProgressEl.style.width = '0%';
            currentProgressEl.classList.remove('bg-red-700');
            currentProgressEl.classList.add('bg-green-600');
        }
    }, 1000);
}

function closeDeleteModal() {
    const modal = document.getElementById('deleteConfirmModal');
    modal.classList.add('hidden');
    
    // Clear countdown
    if (window.EmployeeDelete.countdown) {
        clearInterval(window.EmployeeDelete.countdown);
        window.EmployeeDelete.countdown = null;
    }
    
    // Reset states
    window.EmployeeDelete.target = null;
    window.EmployeeDelete.type = 'single';
    
    // Use the shared reset function
    resetDeleteModal();
}

function confirmDelete() {
    const confirmBtn = document.getElementById('confirmDeleteBtn');
    const buttonText = document.getElementById('deleteButtonText');
    
    // Show loading state
    confirmBtn.disabled = true;
    buttonText.textContent = 'Menghapus...';
    confirmBtn.classList.remove('animate-pulse');
    
    if (window.EmployeeDelete.type === 'single') {
        // Single delete
        performSingleDelete(window.EmployeeDelete.target.id);
    } else {
        // Bulk delete  
        performBulkDelete(window.EmployeeDelete.target);
    }
}

function performSingleDelete(employeeId) {
    fetch(`/employees/${employeeId}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        closeDeleteModal();
        
        if (data.success) {
            console.log('Single delete successful:', data);
            
            // Show success modal with celebration
            showDeleteSuccessModal(window.EmployeeDelete.target, 'single');
            
            // Reload DataTable safely
            reloadEmployeeTable();
        } else {
            console.error('Delete failed:', data);
            showNotification(data.message || 'Gagal menghapus karyawan', 'error');
        }
    })
    .catch(error => {
        console.error('Delete error:', error);
        closeDeleteModal();
        showNotification('Terjadi kesalahan saat menghapus karyawan', 'error');
    });
}

function performBulkDelete(employees) {
    const employeeIds = employees.map(emp => emp.id);
    
    fetch('/employees/api/bulk', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            action: 'delete',
            employee_ids: employeeIds
        })
    })
    .then(response => response.json())
    .then(data => {
        closeDeleteModal();
        
        if (data.success) {
            console.log('Bulk delete successful:', data);
            
            // Show success modal for bulk delete with employee count
            showDeleteSuccessModal(employees, 'bulk');
            
            // Reload DataTable safely
            reloadEmployeeTable();
            
            // Clear selection
            clearEmployeeSelection();
        } else {
            console.error('Bulk delete failed:', data);
            showNotification(data.message || 'Gagal menghapus karyawan', 'error');
        }
    })
    .catch(error => {
        console.error('Bulk delete error:', error);
        closeDeleteModal();
        showNotification('Terjadi kesalahan saat menghapus karyawan', 'error');
    });
}

// Integration with existing delete functions
// (Removed duplicate function - using the main deleteEmployee function above)

function handleBulkDelete() {
    const selectedCheckboxes = document.querySelectorAll('input[name="employee_ids[]"]:checked');
    
    if (selectedCheckboxes.length === 0) {
        showNotification('Pilih karyawan yang ingin dihapus', 'warning');
        return;
    }
    
    const selectedEmployees = Array.from(selectedCheckboxes).map(cb => {
        const row = cb.closest('tr');
        return {
            id: cb.value,
            name: row.querySelector('.employee-name')?.textContent?.trim() || 'Unknown',
            email: row.querySelector('.employee-email')?.textContent?.trim() || 'Unknown'
        };
    });
    
    showDeleteModal(selectedEmployees, 'bulk');
}

// ========== UTILITY FUNCTIONS ==========

function reloadEmployeeTable() {
    try {
        // Try jQuery DataTable first
        if ($.fn.DataTable && $.fn.DataTable.isDataTable('#employeesTable')) {
            $('#employeesTable').DataTable().ajax.reload(null, false);
            console.log('DataTable reloaded via jQuery');
            return;
        }
        
        // Try global DataTable variable
        if (window.employeesTable && typeof window.employeesTable.ajax === 'object') {
            window.employeesTable.ajax.reload(null, false);
            console.log('DataTable reloaded via global variable');
            return;
        }
        
        // Try to get DataTable instance
        const table = $('#employeesTable').DataTable();
        if (table && typeof table.ajax === 'object') {
            table.ajax.reload(null, false);
            console.log('DataTable reloaded via instance');
            return;
        }
        
        // Fallback: page reload
        console.warn('DataTable not found, reloading page');
        setTimeout(() => {
            window.location.reload();
        }, 1000);
        
    } catch (error) {
        console.error('Error reloading DataTable:', error);
        // Ultimate fallback: page reload
        setTimeout(() => {
            window.location.reload();
        }, 1000);
    }
}

function clearEmployeeSelection() {
    try {
        // Clear select all checkbox
        const selectAll = document.getElementById('selectAll');
        if (selectAll) selectAll.checked = false;
        
        // Clear individual checkboxes
        document.querySelectorAll('input[name="employee_ids[]"]').forEach(cb => cb.checked = false);
        document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = false);
        
        // Update bulk actions toolbar
        if (typeof updateBulkActionsToolbar === 'function') {
            updateBulkActionsToolbar();
        }
        
        console.log('Employee selection cleared');
    } catch (error) {
        console.error('Error clearing selection:', error);
    }
}

// ========== SUCCESS MODAL FUNCTIONS ==========

function showDeleteSuccessModal(employeeData, type = 'single') {
    const modal = document.getElementById('deleteSuccessModal');
    const messageEl = document.getElementById('successMessage');
    
    if (!modal || !messageEl) {
        console.warn('Success modal elements not found');
        // Fallback to regular notification
        const message = type === 'single' 
            ? `${employeeData?.name || 'Karyawan'} berhasil dihapus` 
            : `${employeeData?.length || 0} karyawan berhasil dihapus`;
        showNotification(message, 'success');
        return;
    }
    
    if (type === 'single') {
        const employeeName = employeeData?.name || employeeData?.full_name || 'Karyawan';
        messageEl.innerHTML = `
            <div class="space-y-2">
                <div class="font-medium text-green-800 dark:text-green-200">
                    <strong>${employeeName}</strong> telah dihapus dari sistem
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">
                    Data dan akun login telah dihapus permanen
                </div>
            </div>
        `;
    } else {
        const count = employeeData?.length || 0;
        messageEl.innerHTML = `
            <div class="space-y-2">
                <div class="font-medium text-green-800 dark:text-green-200">
                    <strong>${count} karyawan</strong> telah dihapus dari sistem
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">
                    Semua data dan akun login telah dihapus permanen
                </div>
            </div>
        `;
    }
    
    modal.classList.remove('hidden');
    
    // Clear any existing auto-close timer
    if (window.EmployeeDelete.successTimer) {
        clearTimeout(window.EmployeeDelete.successTimer);
    }
    
    // Auto close after 5 seconds
    window.EmployeeDelete.successTimer = setTimeout(() => {
        closeSuccessModal();
    }, 5000);
    
    // Also show regular notification
    const message = type === 'single' 
        ? `${employeeData?.name || 'Karyawan'} berhasil dihapus` 
        : `${employeeData?.length || 0} karyawan berhasil dihapus`;
    showNotification(message, 'success');
}

function closeSuccessModal() {
    const modal = document.getElementById('deleteSuccessModal');
    if (modal) {
        modal.classList.add('hidden');
    }
    
    // Clear auto-close timer
    if (window.EmployeeDelete.successTimer) {
        clearTimeout(window.EmployeeDelete.successTimer);
        window.EmployeeDelete.successTimer = null;
    }
}

// Close success modal when clicking outside
document.addEventListener('click', function(event) {
    const modal = document.getElementById('deleteSuccessModal');
    if (modal && event.target === modal) {
        closeSuccessModal();
    }
});

// Close success modal with Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const modal = document.getElementById('deleteSuccessModal');
        if (modal && !modal.classList.contains('hidden')) {
            closeSuccessModal();
        }
    }
});
</script>
@endpush