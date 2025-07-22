@extends('layouts.authenticated-unified')
@section('title', 'Manajemen User & Password')

@section('content')
<div class="container mx-auto px-6 py-8">
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Manajemen User & Password</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Kelola akun user untuk guru dan karyawan yang telah di-import</p>
            </div>
            <div class="flex items-center space-x-3">
                <a href="{{ route('employees.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg flex items-center space-x-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    <span>Kembali ke Daftar Karyawan</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Employees -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-blue-600 rounded-lg shadow-md">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5 0a9 9 0 01-3-7.5A5.999 5.999 0 0018 4.354a4 4 0 11-3 3.5z"/>
                    </svg>
                </div>
                <span class="text-sm text-blue-600">Total Karyawan</span>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $stats['total_employees'] }}</h3>
            <p class="text-gray-600 dark:text-gray-400 text-sm">Karyawan Aktif</p>
        </div>

        <!-- With User Accounts -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-green-600 rounded-lg shadow-md">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="text-sm text-green-600">Sudah Punya User</span>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $stats['with_users'] }}</h3>
            <p class="text-gray-600 dark:text-gray-400 text-sm">{{ $stats['percentage_with_users'] }}% dari total</p>
        </div>

        <!-- Without User Accounts -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-red-600 rounded-lg shadow-md">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                    </svg>
                </div>
                <span class="text-sm text-red-600">Belum Punya User</span>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $stats['without_users'] }}</h3>
            <p class="text-gray-600 dark:text-gray-400 text-sm">Perlu dibuatkan akun</p>
        </div>

        <!-- Action Progress -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-purple-600 rounded-lg shadow-md">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <span class="text-sm text-purple-600">Progress</span>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100" id="progress-percentage">{{ $stats['percentage_with_users'] }}%</h3>
            <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                <div class="bg-purple-600 h-2 rounded-full" style="width: {{ $stats['percentage_with_users'] }}%"></div>
            </div>
        </div>
    </div>

    <!-- Tab Navigation -->
    <div class="mb-6">
        <div class="flex border-b border-gray-200 dark:border-gray-700">
            <button id="tab-create-users" class="tab-button active px-6 py-3 text-sm font-medium text-blue-600 border-b-2 border-blue-600">
                Buat User Baru
                <span class="ml-2 px-2 py-1 bg-red-100 text-red-600 text-xs rounded-full">{{ $stats['without_users'] }}</span>
            </button>
            <button id="tab-reset-passwords" class="tab-button px-6 py-3 text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 border-b-2 border-transparent">
                Reset Password
                <span class="ml-2 px-2 py-1 bg-green-100 text-green-600 text-xs rounded-full">{{ $stats['with_users'] }}</span>
            </button>
        </div>
    </div>

    <!-- Create Users Tab -->
    <div id="content-create-users" class="tab-content">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Karyawan Tanpa Akun User</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Pilih karyawan untuk dibuatkan akun user dan password</p>
                
                @if($employeesWithoutUsers->count() > 0)
                <!-- Bulk Actions -->
                <div class="flex items-center justify-between mt-4">
                    <div class="flex items-center space-x-3">
                        <label class="inline-flex items-center">
                            <input type="checkbox" id="select-all-create" class="form-checkbox h-4 w-4 text-blue-600">
                            <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Pilih Semua</span>
                        </label>
                        <span class="text-sm text-gray-500" id="selected-count-create">0 dipilih</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button id="bulk-create-users" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            Buat User Terpilih
                        </button>
                    </div>
                </div>
                @endif
            </div>
            
            <div class="p-6">
                @if($employeesWithoutUsers->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="w-8 px-6 py-3 text-left">
                                    <input type="checkbox" class="form-checkbox h-4 w-4 text-blue-600">
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nama</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tipe</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Departemen</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tanggal Masuk</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700" id="create-users-table">
                            @foreach($employeesWithoutUsers as $employee)
                            <tr data-employee-id="{{ $employee->id }}">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <input type="checkbox" class="employee-checkbox form-checkbox h-4 w-4 text-blue-600" value="{{ $employee->id }}">
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $employee->full_name }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $employee->email }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100">
                                        {{ ucfirst(str_replace('_', ' ', $employee->employee_type)) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $employee->location->name ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $employee->hire_date?->format('d/m/Y') ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button class="create-single-user text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300" data-employee-id="{{ $employee->id }}">
                                        Buat User
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Semua Karyawan Sudah Punya User</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Semua karyawan aktif sudah memiliki akun user.</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Reset Passwords Tab -->
    <div id="content-reset-passwords" class="tab-content hidden">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Karyawan dengan Akun User</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Reset password untuk karyawan yang sudah memiliki akun user</p>
                
                @if($employeesWithUsers->count() > 0)
                <!-- Bulk Actions -->
                <div class="flex items-center justify-between mt-4">
                    <div class="flex items-center space-x-3">
                        <label class="inline-flex items-center">
                            <input type="checkbox" id="select-all-reset" class="form-checkbox h-4 w-4 text-blue-600">
                            <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Pilih Semua</span>
                        </label>
                        <span class="text-sm text-gray-500" id="selected-count-reset">0 dipilih</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button id="bulk-reset-passwords" class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-lg text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                            </svg>
                            Reset Password Terpilih
                        </button>
                    </div>
                </div>
                @endif
            </div>
            
            <div class="p-6">
                @if($employeesWithUsers->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="w-8 px-6 py-3 text-left">
                                    <input type="checkbox" class="form-checkbox h-4 w-4 text-blue-600">
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nama</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Role</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Last Login</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">User Dibuat</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700" id="reset-passwords-table">
                            @foreach($employeesWithUsers as $employee)
                            <tr data-employee-id="{{ $employee->id }}">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <input type="checkbox" class="employee-checkbox-reset form-checkbox h-4 w-4 text-blue-600" value="{{ $employee->id }}">
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $employee->full_name }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $employee->email }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                                        {{ ucfirst($employee->user?->roles->first()?->name ?? 'N/A') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $employee->user?->last_login_at?->format('d/m/Y H:i') ?? 'Belum Login' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $employee->user?->created_at?->format('d/m/Y H:i') ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button class="reset-single-password text-orange-600 hover:text-orange-900 dark:text-orange-400 dark:hover:text-orange-300" data-employee-id="{{ $employee->id }}">
                                        Reset Password
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5 0a9 9 0 01-3-7.5A5.999 5.999 0 0018 4.354a4 4 0 11-3 3.5z"/>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Belum Ada Karyawan dengan User</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Buatlah user terlebih dahulu untuk karyawan di tab "Buat User Baru".</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Results Display Area -->
    <div id="results-container" class="mt-8 hidden">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white" id="results-title">Hasil Operasi</h3>
                    <button id="export-results" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                        </svg>
                        Export Excel
                    </button>
                </div>
            </div>
            <div class="p-6" id="results-content">
                <!-- Results will be populated here -->
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    let lastOperationData = null;
    let lastOperationType = null;

    // Tab switching
    $('.tab-button').click(function() {
        $('.tab-button').removeClass('active text-blue-600 border-blue-600').addClass('text-gray-500 border-transparent');
        $(this).addClass('active text-blue-600 border-blue-600').removeClass('text-gray-500 border-transparent');
        
        $('.tab-content').addClass('hidden');
        const targetId = $(this).attr('id').replace('tab-', 'content-');
        $('#' + targetId).removeClass('hidden');
        
        // Hide results when switching tabs
        $('#results-container').addClass('hidden');
    });

    // Select all functionality for create users
    $('#select-all-create').change(function() {
        const isChecked = $(this).prop('checked');
        $('#create-users-table .employee-checkbox').prop('checked', isChecked);
        updateSelectedCount('create');
        toggleBulkButton('create');
    });

    // Select all functionality for reset passwords
    $('#select-all-reset').change(function() {
        const isChecked = $(this).prop('checked');
        $('#reset-passwords-table .employee-checkbox-reset').prop('checked', isChecked);
        updateSelectedCount('reset');
        toggleBulkButton('reset');
    });

    // Individual checkbox change for create users
    $(document).on('change', '.employee-checkbox', function() {
        updateSelectedCount('create');
        toggleBulkButton('create');
    });

    // Individual checkbox change for reset passwords
    $(document).on('change', '.employee-checkbox-reset', function() {
        updateSelectedCount('reset');
        toggleBulkButton('reset');
    });

    // Update selected count
    function updateSelectedCount(type) {
        const checkboxClass = type === 'create' ? '.employee-checkbox' : '.employee-checkbox-reset';
        const countId = type === 'create' ? 'selected-count-create' : 'selected-count-reset';
        
        const selected = $(checkboxClass + ':checked').length;
        $('#' + countId).text(selected + ' dipilih');
    }

    // Toggle bulk button state
    function toggleBulkButton(type) {
        const checkboxClass = type === 'create' ? '.employee-checkbox' : '.employee-checkbox-reset';
        const buttonId = type === 'create' ? 'bulk-create-users' : 'bulk-reset-passwords';
        
        const selected = $(checkboxClass + ':checked').length;
        $('#' + buttonId).prop('disabled', selected === 0);
    }

    // Single user creation
    $(document).on('click', '.create-single-user', function() {
        const employeeId = $(this).data('employee-id');
        createUser([employeeId], false);
    });

    // Bulk user creation
    $('#bulk-create-users').click(function() {
        const selectedIds = $('.employee-checkbox:checked').map(function() {
            return $(this).val();
        }).get();
        
        if (selectedIds.length === 0) {
            showNotification('Pilih minimal satu karyawan', 'warning');
            return;
        }

        createUser(selectedIds, true);
    });

    // Single password reset
    $(document).on('click', '.reset-single-password', function() {
        const employeeId = $(this).data('employee-id');
        resetPassword([employeeId], false);
    });

    // Bulk password reset
    $('#bulk-reset-passwords').click(function() {
        const selectedIds = $('.employee-checkbox-reset:checked').map(function() {
            return $(this).val();
        }).get();
        
        if (selectedIds.length === 0) {
            showNotification('Pilih minimal satu karyawan', 'warning');
            return;
        }

        resetPassword(selectedIds, true);
    });

    // Create user function
    function createUser(employeeIds, isBulk) {
        const url = isBulk ? '{{ route("employees.credentials.bulk.create-users") }}' : '{{ route("employees.credentials.create-user") }}';
        const data = isBulk ? { employee_ids: employeeIds } : { employee_id: employeeIds[0] };

        showLoading('Sedang membuat user accounts...');

        $.ajax({
            url: url,
            type: 'POST',
            data: data,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                hideLoading();
                
                if (response.success) {
                    showNotification(response.message, 'success');
                    
                    // Store results for export
                    lastOperationData = isBulk ? response.data.success : [response.data];
                    lastOperationType = 'new_users';
                    
                    // Display results
                    displayResults(response.data, 'User Accounts Berhasil Dibuat', isBulk);
                    
                    // Remove processed rows from table
                    employeeIds.forEach(id => {
                        $(`#create-users-table tr[data-employee-id="${id}"]`).fadeOut();
                    });
                    
                    // Update statistics
                    updateStatistics();
                    
                } else {
                    showNotification(response.message, 'error');
                }
            },
            error: function(xhr) {
                hideLoading();
                const response = xhr.responseJSON;
                showNotification(response?.message || 'Gagal membuat user accounts', 'error');
            }
        });
    }

    // Reset password function
    function resetPassword(employeeIds, isBulk) {
        const url = isBulk ? '{{ route("employees.credentials.bulk.reset-passwords") }}' : '{{ route("employees.credentials.reset-password") }}';
        const data = isBulk ? { employee_ids: employeeIds } : { employee_id: employeeIds[0] };

        showLoading('Sedang reset passwords...');

        $.ajax({
            url: url,
            type: 'POST',
            data: data,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                hideLoading();
                
                if (response.success) {
                    showNotification(response.message, 'success');
                    
                    // Store results for export
                    lastOperationData = isBulk ? response.data.success : [response.data];
                    lastOperationType = 'password_reset';
                    
                    // Display results
                    displayResults(response.data, 'Password Berhasil Direset', isBulk);
                    
                } else {
                    showNotification(response.message, 'error');
                }
            },
            error: function(xhr) {
                hideLoading();
                const response = xhr.responseJSON;
                showNotification(response?.message || 'Gagal reset passwords', 'error');
            }
        });
    }

    // Display operation results
    function displayResults(data, title, isBulk) {
        $('#results-title').text(title);
        
        let html = '<div class="space-y-4">';
        
        if (isBulk) {
            // Summary for bulk operations
            html += '<div class="bg-blue-50 dark:bg-blue-900 p-4 rounded-lg">';
            html += '<h4 class="font-medium text-blue-800 dark:text-blue-200 mb-2">Ringkasan:</h4>';
            html += `<p class="text-sm text-blue-700 dark:text-blue-300">Berhasil: ${data.success?.length || 0}</p>`;
            if (data.failed) {
                html += `<p class="text-sm text-red-700 dark:text-red-300">Gagal: ${data.failed.length}</p>`;
            }
            if (data.skipped) {
                html += `<p class="text-sm text-yellow-700 dark:text-yellow-300">Dilewati: ${data.skipped.length}</p>`;
            }
            html += '</div>';
        }

        // Success results
        const successData = isBulk ? (data.success || []) : [data];
        if (successData.length > 0) {
            html += '<div><h4 class="font-medium text-green-800 dark:text-green-200 mb-3">✅ Berhasil:</h4>';
            html += '<div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-200">';
            html += '<thead class="bg-gray-50 dark:bg-gray-900">';
            html += '<tr><th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Nama</th>';
            html += '<th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Email</th>';
            html += '<th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Password</th>';
            html += '<th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Role</th></tr>';
            html += '</thead><tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200">';
            
            successData.forEach(item => {
                html += '<tr>';
                html += `<td class="px-4 py-2 text-sm">${item.employee_name}</td>`;
                html += `<td class="px-4 py-2 text-sm">${item.employee_email}</td>`;
                html += `<td class="px-4 py-2 text-sm"><code class="bg-red-100 text-red-800 px-2 py-1 rounded">${item.password || item.new_password}</code></td>`;
                html += `<td class="px-4 py-2 text-sm">${item.role}</td>`;
                html += '</tr>';
            });
            
            html += '</tbody></table></div></div>';
        }

        // Failed results
        if (isBulk && data.failed && data.failed.length > 0) {
            html += '<div><h4 class="font-medium text-red-800 dark:text-red-200 mb-3">❌ Gagal:</h4>';
            html += '<ul class="list-disc list-inside space-y-1">';
            data.failed.forEach(item => {
                html += `<li class="text-sm text-red-600">${item.employee_name}: ${item.error || item.message}</li>`;
            });
            html += '</ul></div>';
        }

        html += '</div>';
        
        $('#results-content').html(html);
        $('#results-container').removeClass('hidden');
    }

    // Export results
    $('#export-results').click(function() {
        if (!lastOperationData || lastOperationData.length === 0) {
            showNotification('Tidak ada data untuk di-export', 'warning');
            return;
        }

        // Create form and submit
        const form = $('<form>', {
            'method': 'POST',
            'action': '{{ route("employees.credentials.export") }}'
        });
        
        form.append($('<input>', {
            'type': 'hidden',
            'name': '_token',
            'value': $('meta[name="csrf-token"]').attr('content')
        }));
        
        form.append($('<input>', {
            'type': 'hidden',
            'name': 'type',
            'value': lastOperationType
        }));
        
        form.append($('<input>', {
            'type': 'hidden',
            'name': 'data',
            'value': JSON.stringify(lastOperationData)
        }));
        
        $('body').append(form);
        form.submit();
        form.remove();
        
        showNotification('Mengunduh file Excel...', 'info');
    });

    // Update statistics
    function updateStatistics() {
        // This would typically reload the page or make an AJAX call to get updated stats
        // For now, we'll just show a message
        setTimeout(() => {
            location.reload();
        }, 2000);
    }

    // Utility functions
    function showLoading(message) {
        // You can implement a loading spinner here
        showNotification(message, 'info');
    }

    function hideLoading() {
        // Hide loading spinner
    }

    // Notification function
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 z-50 px-6 py-4 rounded-lg shadow-lg transition-all transform ${
            type === 'success' ? 'bg-green-500 text-white' :
            type === 'error' ? 'bg-red-500 text-white' :
            type === 'warning' ? 'bg-yellow-500 text-white' :
            'bg-blue-500 text-white'
        }`;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 5000);
    }
});
</script>
@endpush
@endsection