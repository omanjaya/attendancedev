@extends('layouts.authenticated-unified')

@section('title', 'Manajemen Karyawan')

@section('page-content')
<div class="mb-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Manajemen Karyawan</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Kelola data karyawan, status, dan informasi kepegawaian</p>
        </div>
        <div class="flex items-center space-x-3">
            @can('view_employees_analytics')
            <button onclick="showEmployeeAnalytics()" class="bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 px-4 py-2 rounded-lg font-medium transition-colors duration-200">
                <svg class="w-5 h-5 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                Analytics
            </button>
            @endcan
            @can('export_employees_data')
            <button onclick="exportEmployees()" class="bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 px-4 py-2 rounded-lg font-medium transition-colors duration-200">
                <svg class="w-5 h-5 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Ekspor Data
            </button>
            @endcan
            <button onclick="showBulkActions()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200">
                <svg class="w-5 h-5 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                </svg>
                Bulk Actions
            </button>
            @can('create_employees')
            <a href="{{ route('employees.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200">
                <svg class="w-5 h-5 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Tambah Karyawan
            </a>
            @endcan
        </div>
    </div>
</div>
<!-- Employee Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Total Employees -->
    <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between mb-4">
            <div class="p-3 bg-blue-600 rounded-lg shadow-md">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/>
                </svg>
            </div>
            <span class="text-sm text-blue-600 font-medium px-3 py-1 bg-blue-100 dark:bg-blue-900 rounded-full">Total</span>
        </div>
        <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-1">{{ $statistics['total'] ?? 0 }}</h3>
        <p class="text-gray-600 dark:text-gray-400 text-sm">Total Karyawan</p>
        <div class="mt-3 text-xs text-gray-500 dark:text-gray-400">
            Semua departemen
        </div>
    </x-ui.card>

    <!-- Active Today -->
    <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between mb-4">
            <div class="p-3 bg-green-600 rounded-lg shadow-md">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <span class="text-sm text-green-600 font-medium px-3 py-1 bg-green-100 dark:bg-green-900 rounded-full">Aktif</span>
        </div>
        <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-1">{{ $statistics['active_today'] ?? 0 }}</h3>
        <p class="text-gray-600 dark:text-gray-400 text-sm">Aktif Hari Ini</p>
        @if(($statistics['active_today'] ?? 0) > 0)
        <div class="mt-3">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400">
                {{ round((($statistics['active_today'] ?? 0) / max($statistics['total'] ?? 1, 1)) * 100) }}% tingkat kehadiran
            </span>
        </div>
        @endif
    </x-ui.card>

    <!-- Permanent Staff -->
    <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between mb-4">
            <div class="p-3 bg-purple-600 rounded-lg shadow-md">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 012 2v6a2 2 0 01-2 2H6a2 2 0 01-2-2V8a2 2 0 012-2z"/>
                </svg>
            </div>
            <span class="text-sm text-purple-600 font-medium px-3 py-1 bg-purple-100 dark:bg-purple-900 rounded-full">Tetap</span>
        </div>
        <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-1">{{ $statistics['permanent'] ?? 0 }}</h3>
        <p class="text-gray-600 dark:text-gray-400 text-sm">Pegawai Tetap</p>
        <div class="mt-3 text-xs text-gray-500 dark:text-gray-400">
            Staff full-time
        </div>
    </x-ui.card>

    <!-- Honorary Teachers -->
    <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between mb-4">
            <div class="p-3 bg-orange-600 rounded-lg shadow-md">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
            </div>
            <span class="text-sm text-orange-600 font-medium px-3 py-1 bg-orange-100 dark:bg-orange-900 rounded-full">Kontrak</span>
        </div>
        <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-1">{{ $statistics['honorary'] ?? 0 }}</h3>
        <p class="text-gray-600 dark:text-gray-400 text-sm">Guru Honorer</p>
        <div class="mt-3 text-xs text-gray-500 dark:text-gray-400">
            Tenaga kontrak
        </div>
    </x-ui.card>
</div>

<!-- Main Content Area -->
<div class="grid grid-cols-1 xl:grid-cols-4 gap-8">
    <!-- Employee Table and Search (Main Panel) -->
    <div class="xl:col-span-3">

        <!-- Employee Data Table -->
        <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Data Karyawan</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Kelola dan pantau data karyawan</p>
                    </div>
                    <div class="flex items-center space-x-3">
                        <button onclick="refreshEmployeeData()" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg transition-colors duration-200" title="Refresh Data">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                        </button>
                        <button onclick="toggleTableSettings()" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg transition-colors duration-200" title="Table Settings">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
            <div class="p-6">
                <!-- Bulk Actions Toolbar -->
                <div id="bulkActionsToolbar" class="hidden bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-700 mb-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <span id="selectedCount" class="text-sm font-medium text-blue-800 dark:text-blue-200">0 item dipilih</span>
                            <button type="button" onclick="clearSelection()" class="text-sm text-blue-600 dark:text-blue-300 hover:text-blue-800 dark:hover:text-blue-100 font-medium">
                                Batalkan pilihan
                            </button>
                        </div>
                        <div class="flex items-center space-x-2">
                            <button onclick="bulkExport()" class="inline-flex items-center px-3 py-1.5 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 rounded-lg transition-colors">
                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                Ekspor
                            </button>
                            <button onclick="bulkDelete()" class="inline-flex items-center px-3 py-1.5 text-sm bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 text-red-700 dark:text-red-300 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-lg transition-colors">
                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
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
                                       class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 px-4 py-3 pl-10 text-sm text-gray-900 dark:text-gray-100 placeholder:text-gray-500 dark:placeholder:text-gray-400 focus:border-blue-500 dark:focus:border-blue-400 focus:ring-2 focus:ring-blue-500/20">
                                <div class="absolute left-3 top-1/2 -translate-y-1/2">
                                    <svg class="h-5 w-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Department Filter -->
                        <div>
                            <select id="department-filter" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 px-4 py-3 text-sm text-gray-900 dark:text-gray-100 focus:border-blue-500 dark:focus:border-blue-400 focus:ring-2 focus:ring-blue-500/20">
                                <option value="">Semua Departemen</option>
                                @foreach($departments ?? [] as $dept)
                                    <option value="{{ $dept['id'] }}">{{ $dept['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <!-- Status Filter -->
                        <div>
                            <select id="status-filter" class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 px-4 py-3 text-sm text-gray-900 dark:text-gray-100 focus:border-blue-500 dark:focus:border-blue-400 focus:ring-2 focus:ring-blue-500/20">
                                <option value="">Semua Status</option>
                                <option value="active">Aktif</option>
                                <option value="inactive">Tidak Aktif</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Enhanced Table -->
                <div class="overflow-x-auto border border-gray-200 dark:border-gray-700 rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700" id="employeesTable">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    <input type="checkbox" id="selectAll" class="h-4 w-4 rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500">
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Karyawan</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Kontak</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Departemen</th>
                                <th class="px-6 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700"></tbody>
                    </table>
                </div>

                <!-- Custom Pagination -->
                <div class="mt-6 flex items-center justify-between border-t border-gray-200 dark:border-gray-700 pt-4">
                    <div class="flex items-center space-x-4">
                        <span class="text-sm text-gray-500 dark:text-gray-400" id="table-info">
                            Menampilkan data karyawan
                        </span>
                        <div class="text-xs text-gray-400 dark:text-gray-500" id="table-summary">
                            <!-- Will be populated with summary stats -->
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button id="prev-btn" disabled class="px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                            <svg class="w-4 h-4 mr-1 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                            Sebelumnya
                        </button>
                        <button id="next-btn" class="px-3 py-2 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                            Selanjutnya
                            <svg class="w-4 h-4 ml-1 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </x-ui.card>
    </div>

    <!-- Quick Actions Sidebar -->
    <div class="xl:col-span-1">
        <!-- Quick Actions Card -->
        <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-6">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Quick Actions</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Aksi cepat untuk manajemen karyawan</p>
            </div>
            <div class="p-6 space-y-3">
                @can('create_employees')
                <a href="{{ route('employees.create') }}" class="w-full text-left px-4 py-3 bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-100 dark:hover:bg-blue-900/30 rounded-lg transition-colors duration-200 block">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        <div>
                            <div class="font-medium text-gray-900 dark:text-white">Tambah Karyawan</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Daftarkan karyawan baru</div>
                        </div>
                    </div>
                </a>
                @endcan
                
                @can('export_employees_data')
                <button onclick="exportEmployees()" class="w-full text-left px-4 py-3 bg-green-50 dark:bg-green-900/20 hover:bg-green-100 dark:hover:bg-green-900/30 rounded-lg transition-colors duration-200">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-green-600 dark:text-green-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <div>
                            <div class="font-medium text-gray-900 dark:text-white">Ekspor Data</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Download laporan Excel</div>
                        </div>
                    </div>
                </button>
                @endcan
                
                <a href="{{ route('employees.template') }}" class="w-full text-left px-4 py-3 bg-purple-50 dark:bg-purple-900/20 hover:bg-purple-100 dark:hover:bg-purple-900/30 rounded-lg transition-colors duration-200 block">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-purple-600 dark:text-purple-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <div>
                            <div class="font-medium text-gray-900 dark:text-white">Template CSV</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Format import data</div>
                        </div>
                    </div>
                </a>
                
                <a href="{{ route('schedules.index') }}" class="w-full text-left px-4 py-3 bg-orange-50 dark:bg-orange-900/20 hover:bg-orange-100 dark:hover:bg-orange-900/30 rounded-lg transition-colors duration-200 block">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-orange-600 dark:text-orange-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <div>
                            <div class="font-medium text-gray-900 dark:text-white">Kelola Jadwal</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Atur jadwal kerja</div>
                        </div>
                    </div>
                </a>
            </div>
        </x-ui.card>
        
        <!-- Department Summary Card -->
        <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Ringkasan Departemen</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Distribusi karyawan per departemen</p>
            </div>
            <div class="p-6">
                <div class="space-y-4" id="department-summary">
                    <!-- Will be populated by JavaScript -->
                    <div class="text-center text-gray-500 dark:text-gray-400 py-8">
                        <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        <p class="text-sm">Memuat data departemen...</p>
                    </div>
                </div>
            </div>
        </x-ui.card>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    if (!$.fn.dataTable.isDataTable('#employeesTable')) {
        let table = $('#employeesTable').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            dom: 't', // Only show table, hide default search and pagination
            ajax: {
                url: '{{ route("employees.data") }}',
                data: function(d) {
                    d.search_value = $('#employee-search').val();
                    d.department = $('#department-filter').val();
                    d.status = $('#status-filter').val();
                    d.type = $('#type-filter').val();
                }
            },
            columns: [
                {
                    data: 'id',
                    name: 'checkbox',
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row) {
                        return `<input type="checkbox" class="row-checkbox h-4 w-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500" value="${data}">`;
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
                    data: 'status', 
                    name: 'status',
                    render: function(data, type, row) {
                        const statusVariant = data === 'active' 
                            ? 'default' 
                            : 'destructive';
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
                    data: 'actions',
                    name: 'actions',
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row) {
                        let viewUrl = `/employees/${row.id}`;
                        let editUrl = `/employees/${row.id}/edit`;
                        return `
                            <div class="flex items-center justify-end space-x-1 py-3">
                                <button onclick="showEmployeeDetails('${row.id}')" 
                                        class="p-2 text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors" 
                                        title="Lihat Detail">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </button>
                                <button onclick="window.location.href='${editUrl}'" 
                                        class="p-2 text-gray-400 hover:text-green-600 dark:hover:text-green-400 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-lg transition-colors" 
                                        title="Edit Karyawan">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </button>
                                <button onclick="deleteEmployee('${row.id}')" 
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
            pageLength: 10,
            order: [[1, 'asc']], // Order by name column
            language: { 
                processing: "Memuat data...",
                emptyTable: "Tidak ada data karyawan"
            },
            drawCallback: function(settings) {
                var api = this.api();
                var info = api.page.info();
                
                // Update pagination info
                $('#table-info').text(`Menampilkan ${info.start + 1} sampai ${info.end} dari ${info.recordsTotal} karyawan`);
                
                // Update table summary
                const activeCount = api.column(3).data().filter(function(value, index) {
                    return value && value.includes('Aktif');
                }).length;
                $('#table-summary').text(`${activeCount} aktif dari ${info.recordsTotal} total`);
                
                // Update pagination buttons
                $('#prev-btn').prop('disabled', info.page === 0);
                $('#next-btn').prop('disabled', info.page >= info.pages - 1);
                
                // Load department summary
                loadDepartmentSummary();
            }
        });

        // Custom pagination handlers
        $('#prev-btn').on('click', function() {
            table.page('previous').draw('page');
        });

        $('#next-btn').on('click', function() {
            table.page('next').draw('page');
        });
    }

    // Filter events - single handler for all filters
    $('#employee-search, #department-filter, #status-filter, #type-filter').on('change keyup', function() {
        table.draw();
    });

    // Bulk selection handlers
    $(document).on('change', '#selectAll', function() {
        $('.row-checkbox').prop('checked', this.checked);
        updateBulkActionsToolbar();
    });

    $(document).on('change', '.row-checkbox', function() {
        updateBulkActionsToolbar();
        const totalRows = $('.row-checkbox').length;
        const checkedRows = $('.row-checkbox:checked').length;
        $('#selectAll').prop('indeterminate', checkedRows > 0 && checkedRows < totalRows);
        $('#selectAll').prop('checked', checkedRows === totalRows);
    });
});

// Enhanced interactive features
function showEmployeeAnalytics() {
    showNotification('Membuka analytics karyawan...', 'info');
    // Implementation for employee analytics
}

function showBulkActions() {
    showNotification('Menampilkan bulk actions...', 'info');
    // Implementation for bulk actions
}

function refreshEmployeeData() {
    $('#employeesTable').DataTable().ajax.reload();
    showNotification('Data karyawan berhasil diperbarui', 'success');
}

function toggleTableSettings() {
    showNotification('Membuka pengaturan tabel...', 'info');
    // Implementation for table settings
}

function showEmployeeDetails(employeeId) {
    window.location.href = `/employees/${employeeId}`;
}

// Load department summary
function loadDepartmentSummary() {
    const departments = [
        { name: 'Guru Kelas', count: 25, color: 'blue' },
        { name: 'Administrasi', count: 8, color: 'green' },
        { name: 'Tenaga Pendukung', count: 12, color: 'purple' },
        { name: 'Manajemen', count: 5, color: 'orange' }
    ];
    
    const colorMap = {
        blue: 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400',
        green: 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400',
        purple: 'bg-purple-100 text-purple-800 dark:bg-purple-900/20 dark:text-purple-400',
        orange: 'bg-orange-100 text-orange-800 dark:bg-orange-900/20 dark:text-orange-400'
    };
    
    const summaryContainer = document.getElementById('department-summary');
    summaryContainer.innerHTML = departments.map(dept => `
        <div class="flex items-center justify-between p-3 rounded-lg bg-gray-50 dark:bg-gray-700">
            <div class="flex items-center space-x-3">
                <div class="w-3 h-3 rounded-full bg-${dept.color}-500"></div>
                <span class="text-sm font-medium text-gray-900 dark:text-white">${dept.name}</span>
            </div>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${colorMap[dept.color]}">
                ${dept.count}
            </span>
        </div>
    `).join('');
}

// Export employees function
function exportEmployees() {
    window.location.href = '{{ route("employees.export") }}';
}

// Delete employee function with loading state
async function deleteEmployee(id) {
    if (!confirm('Apakah Anda yakin ingin menghapus karyawan ini?\n\nTindakan ini tidak dapat dibatalkan.')) {
        return;
    }
    
    // Show loading state
    const deleteButton = event.target.closest('button');
    const originalHTML = deleteButton.innerHTML;
    deleteButton.disabled = true;
    deleteButton.innerHTML = `
        <svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
        </svg>
    `;
    
    try {
        const response = await fetch(`/employees/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        });
        
        const result = await response.json();
        
        if (response.ok) {
            showNotification(result.message || 'Karyawan berhasil dihapus', 'success');
            $('#employeesTable').DataTable().ajax.reload();
        } else {
            showNotification(result.message || 'Gagal menghapus karyawan', 'error');
        }
    } catch (error) {
        console.error('Error deleting employee:', error);
        showNotification('Terjadi kesalahan saat menghapus karyawan', 'error');
    } finally {
        // Restore button state
        deleteButton.disabled = false;
        deleteButton.innerHTML = originalHTML;
    }
}

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

// Get selected employee IDs
function getSelectedIds() {
    return $('.row-checkbox:checked').map(function() {
        return this.value;
    }).get();
}

// Clear selection
function clearSelection() {
    $('.row-checkbox, #selectAll').prop('checked', false);
    updateBulkActionsToolbar();
}

// Bulk export
function bulkExport() {
    const selectedIds = getSelectedIds();
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

// Bulk activate with loading state
async function bulkActivate() {
    const selectedIds = getSelectedIds();
    if (selectedIds.length === 0) {
        showNotification('Pilih karyawan yang akan diaktifkan', 'warning');
        return;
    }
    
    if (!confirm(`Apakah Anda yakin ingin mengaktifkan ${selectedIds.length} karyawan?`)) {
        return;
    }
    
    // Show loading state
    const button = event.target.closest('button');
    const originalHTML = button.innerHTML;
    button.disabled = true;
    button.innerHTML = `
        <svg class="w-3 h-3 mr-1.5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
        </svg>
        Memproses...
    `;
    
    try {
        const response = await fetch('{{ route("employees.bulk-activate") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ employee_ids: selectedIds })
        });
        
        const result = await response.json();
        
        if (response.ok) {
            showNotification(result.message || 'Karyawan berhasil diaktifkan', 'success');
            $('#employeesTable').DataTable().ajax.reload();
            clearSelection();
        } else {
            showNotification(result.message || 'Gagal mengaktifkan karyawan', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Terjadi kesalahan', 'error');
    } finally {
        // Restore button state
        button.disabled = false;
        button.innerHTML = originalHTML;
    }
}

// Bulk deactivate with loading state
async function bulkDeactivate() {
    const selectedIds = getSelectedIds();
    if (selectedIds.length === 0) {
        showNotification('Pilih karyawan yang akan dinonaktifkan', 'warning');
        return;
    }
    
    if (!confirm(`Apakah Anda yakin ingin menonaktifkan ${selectedIds.length} karyawan?`)) {
        return;
    }
    
    // Show loading state
    const button = event.target.closest('button');
    const originalHTML = button.innerHTML;
    button.disabled = true;
    button.innerHTML = `
        <svg class="w-3 h-3 mr-1.5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
        </svg>
        Memproses...
    `;
    
    try {
        const response = await fetch('{{ route("employees.bulk-deactivate") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ employee_ids: selectedIds })
        });
        
        const result = await response.json();
        
        if (response.ok) {
            showNotification(result.message || 'Karyawan berhasil dinonaktifkan', 'success');
            $('#employeesTable').DataTable().ajax.reload();
            clearSelection();
        } else {
            showNotification(result.message || 'Gagal menonaktifkan karyawan', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Terjadi kesalahan', 'error');
    } finally {
        // Restore button state
        button.disabled = false;
        button.innerHTML = originalHTML;
    }
}

// Bulk delete with loading state
async function bulkDelete() {
    const selectedIds = getSelectedIds();
    if (selectedIds.length === 0) {
        showNotification('Pilih karyawan yang akan dihapus', 'warning');
        return;
    }
    
    if (!confirm(`Apakah Anda yakin ingin menghapus ${selectedIds.length} karyawan?\n\nTindakan ini tidak dapat dibatalkan.`)) {
        return;
    }
    
    // Show loading state
    const button = event.target.closest('button');
    const originalHTML = button.innerHTML;
    button.disabled = true;
    button.innerHTML = `
        <svg class="w-3 h-3 mr-1.5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
        </svg>
        Menghapus...
    `;
    
    try {
        const response = await fetch('{{ route("employees.bulk-delete") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ employee_ids: selectedIds })
        });
        
        const result = await response.json();
        
        if (response.ok) {
            showNotification(result.message || 'Karyawan berhasil dihapus', 'success');
            $('#employeesTable').DataTable().ajax.reload();
            clearSelection();
        } else {
            showNotification(result.message || 'Gagal menghapus karyawan', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Terjadi kesalahan', 'error');
    } finally {
        // Restore button state
        button.disabled = false;
        button.innerHTML = originalHTML;
    }
}

// Enhanced notification system with modern styling
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    
    // Modern notification styling with icons
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
    
    // Trigger entrance animation
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 100);
    
    // Auto-remove notification
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

// Initialize department summary on page load
$(document).ready(function() {
    setTimeout(() => {
        loadDepartmentSummary();
    }, 1000);
}
</script>
@endpush