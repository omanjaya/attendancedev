@extends('layouts.authenticated-unified')

@section('title', 'Manajemen Penggajian')

@section('page-content')
<!-- Page Header -->
<div class="mb-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Manajemen Penggajian</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Kelola perhitungan dan pembayaran gaji karyawan</p>
        </div>
        <div class="flex items-center space-x-3">
            @can('export_payroll_reports')
                <x-ui.button variant="secondary" href="{{ route('payroll.summary') }}"
                    class="bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Laporan
                </x-ui.button>
            @endcan
            
            @can('create_payroll')
                <x-ui.button variant="secondary" href="{{ route('payroll.bulk-calculate') }}"
                    class="bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    Hitung Massal
                </x-ui.button>
                
                <x-ui.button variant="primary" href="{{ route('payroll.create') }}"
                    class="bg-blue-600 hover:bg-blue-700 text-white">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    Hitung Penggajian
                </x-ui.button>
            @endcan
        </div>
    </div>
</div>
        
<!-- Statistics Cards Section -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Total Gaji Kotor -->
    <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between mb-4">
            <div class="p-3 bg-blue-600 rounded-lg shadow-md">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </div>
            <span class="text-sm text-blue-600">Kotor</span>
        </div>
        <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-1" id="totalGrossSalary">$0.00</h3>
        <p class="text-gray-600 dark:text-gray-400 text-sm">Total Gaji Kotor</p>
    </x-ui.card>

    <!-- Total Gaji Bersih -->
    <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between mb-4">
            <div class="p-3 bg-green-600 rounded-lg shadow-md">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/></svg>
            </div>
            <span class="text-sm text-green-600">Bersih</span>
        </div>
        <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-1" id="totalNetSalary">$0.00</h3>
        <p class="text-gray-600 dark:text-gray-400 text-sm">Total Gaji Bersih</p>
    </x-ui.card>

    <!-- Total Potongan -->
    <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between mb-4">
            <div class="p-3 bg-red-600 rounded-lg shadow-md">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
            </div>
            <span class="text-sm text-red-600">Potongan</span>
        </div>
        <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-1" id="totalDeductions">$0.00</h3>
        <p class="text-gray-600 dark:text-gray-400 text-sm">Total Potongan</p>
    </x-ui.card>

    <!-- Total Karyawan -->
    <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between mb-4">
            <div class="p-3 bg-purple-600 rounded-lg shadow-md">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            </div>
            <span class="text-sm text-purple-600">Karyawan</span>
        </div>
        <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-1" id="totalEmployees">0</h3>
        <p class="text-gray-600 dark:text-gray-400 text-sm">Total Karyawan</p>
    </x-ui.card>
</div>
        
<!-- Filters -->
<x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-8">
    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Filter Catatan Penggajian</h3>
        <p class="text-gray-600 dark:text-gray-400">Filter catatan berdasarkan karyawan, status, dan periode</p>
    </div>
    <div class="p-6">
            
            <form method="GET" id="filtersForm" class="space-y-6">
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-5">
            <div class="space-y-2">
                <label for="employeeFilter" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Karyawan</label>
                <select id="employeeFilter" name="employee_id" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Semua Karyawan</option>
                    <!-- Employee options would be populated from backend -->
                </select>
            </div>
            
            <div class="space-y-2">
                <label for="statusFilter" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                <select id="statusFilter" name="status" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Semua Status</option>
                    <option value="draft">Draf</option>
                    <option value="pending">Tertunda</option>
                    <option value="approved">Disetujui</option>
                    <option value="processed">Diproses</option>
                    <option value="paid">Dibayar</option>
                    <option value="cancelled">Dibatalkan</option>
                </select>
            </div>
            
            <div class="space-y-2">
                <label for="periodStartFilter" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Mulai Periode</label>
                <input type="date" id="periodStartFilter" name="period_start" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
            </div>
            
            <div class="space-y-2">
                <label for="periodEndFilter" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Akhir Periode</label>
                <input type="date" id="periodEndFilter" name="period_end" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
            </div>
            
            <div class="flex items-end space-x-2">
                <button type="button" id="applyFilters" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors duration-200">
                    <svg class="w-5 h-5 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                    Filter
                </button>
                
                <button type="button" id="setCurrentMonth" class="px-4 py-2 bg-gray-100 dark:bg-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-500 rounded-lg font-medium transition-colors duration-200">
                    Bulan Ini
                </button>
            </div>
                </div>
        </form>
    </div>
</x-ui.card>
        
<!-- Payroll Records Table -->
<x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Catatan Penggajian</h3>
        <p class="text-gray-600 dark:text-gray-400">Perhitungan dan pembayaran gaji karyawan</p>
    </div>
    
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Karyawan</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Periode</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Gaji Kotor</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Potongan</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Gaji Bersih</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($records ?? [] as $record)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center text-white text-sm font-bold">
                                        {{ substr($record['employee_name'], 0, 2) }}
                                    </div>
                                    <div class="ml-3">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $record['employee_name'] }}</div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ $record['employee_id'] }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $record['period'] }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">${{ number_format($record['gross_salary'], 2) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600 dark:text-red-400">-${{ number_format($record['deductions'], 2) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-green-600 dark:text-green-400">${{ number_format($record['net_salary'], 2) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $statusColorClass = match($record['status']) {
                                        'Paid' => 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400',
                                        'Approved' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400',
                                        'Pending' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/20 dark:text-amber-400',
                                        'Draft' => 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400',
                                        'Processed' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/20 dark:text-purple-400',
                                        'Cancelled' => 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400',
                                        default => 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400'
                                    };
                                @endphp
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ $statusColorClass }}">
                                    {{ $record['status'] }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end space-x-2">
                                    <a href="{{ route('payroll.show', $record['id']) }}" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 hover:bg-blue-200 dark:hover:bg-blue-900/40 transition-colors duration-200">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    </a>
                                    @if($record['status'] === 'Pending')
                                    <button onclick="approvePayroll({{ $record['id'] }})" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-green-100 dark:bg-green-900/20 text-green-600 dark:text-green-400 hover:bg-green-200 dark:hover:bg-green-900/40 transition-colors duration-200">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    </button>
                                    @endif
                                    @if($record['status'] === 'Approved')
                                    <button onclick="processPayroll({{ $record['id'] }})" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-purple-100 dark:bg-purple-900/20 text-purple-600 dark:text-purple-400 hover:bg-purple-200 dark:hover:bg-purple-900/40 transition-colors duration-200">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    </button>
                                    @endif
                                    @if(in_array($record['status'], ['Draft', 'Pending']))
                                    <button onclick="editPayroll({{ $record['id'] }})" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-amber-100 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400 hover:bg-amber-200 dark:hover:bg-amber-900/40 transition-colors duration-200">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-600 dark:text-gray-400">
                                <svg class="w-12 h-12 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                <p class="text-lg font-medium">Tidak ada catatan penggajian ditemukan</p>
                                <p class="text-sm">Buat perhitungan penggajian pertama Anda untuk memulai</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
            </table>
        </div>
</x-ui.card>
@endsection

@push('scripts')
<script>
    // Initialize page
    document.addEventListener('DOMContentLoaded', function() {
        // Set current month button
        document.getElementById('setCurrentMonth').addEventListener('click', function() {
            const now = new Date();
            const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
            const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);
            
            document.getElementById('periodStartFilter').value = firstDay.toISOString().split('T')[0];
            document.getElementById('periodEndFilter').value = lastDay.toISOString().split('T')[0];
        });

        // Apply filters button
        document.getElementById('applyFilters').addEventListener('click', function() {
            // In a real implementation, this would reload the data with filters
            updateSummaryCards();
            showAlert('success', 'Filter berhasil diterapkan');
        });

        // Clear filters button
        document.getElementById('clearFilters').addEventListener('click', function() {
            document.getElementById('filtersForm').reset();
            updateSummaryCards();
            showAlert('info', 'Filter dihapus');
        });

        // Initial load
        updateSummaryCards();
    });

    // Approve payroll
    function approvePayroll(id) {
        if (confirm('Apakah Anda yakin ingin menyetujui penggajian ini?')) {
            // In a real implementation, this would make an API call
            showAlert('success', 'Penggajian berhasil disetujui');
            // Reload table data
            setTimeout(() => {
                location.reload();
            }, 1000);
        }
    }

    // Process payroll
    function processPayroll(id) {
        if (confirm('Apakah Anda yakin ingin memproses penggajian ini?')) {
            // In a real implementation, this would make an an API call
            showAlert('success', 'Penggajian berhasil diproses');
            // Reload table data
            setTimeout(() => {
                location.reload();
            }, 1000);
        }
    }

    // Edit payroll
    function editPayroll(id) {
        window.location.href = '/payroll/' + id + '/edit';
    }

    // Export payroll
    function exportPayroll() {
        // In a real implementation, this would export the data
        showAlert('info', 'Ekspor penggajian dimulai');
    }

    // Update summary cards
    function updateSummaryCards() {
        // In a real implementation, this would fetch data from an API
        document.getElementById('totalGrossSalary').textContent = '$13,000.00';
        document.getElementById('totalNetSalary').textContent = '$11,050.00';
        document.getElementById('totalDeductions').textContent = '$1,950.00';
        document.getElementById('totalEmployees').textContent = '3';
    }

    // Show alert function
    function showAlert(type, message) {
        const alertColors = {
            success: 'bg-green-500/20 border-green-500/30 text-green-800 dark:text-green-200',
            error: 'bg-red-500/20 border-red-500/30 text-red-800 dark:text-red-200',
            info: 'bg-blue-500/20 border-blue-500/30 text-blue-800 dark:text-blue-200',
            warning: 'bg-amber-500/20 border-amber-500/30 text-amber-800 dark:text-amber-200'
        };

        const alert = document.createElement('div');
        alert.className = `fixed top-4 right-4 p-4 rounded-lg border ${alertColors[type]} shadow-lg z-50`;
        alert.innerHTML = `
            <div class="flex items-center">
                <span>${message}</span>
                <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-lg text-slate-600 dark:text-slate-400">&times;</button>
            </div>
        `;

        document.body.appendChild(alert);

        setTimeout(() => {
            if (alert.parentElement) {
                alert.remove();
            }
        }, 5000);
    }
</script>
@endpush
