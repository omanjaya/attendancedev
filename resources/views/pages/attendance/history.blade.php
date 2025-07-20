@extends('layouts.authenticated-unified')

@section('title', 'Riwayat Kehadiran')

@section('page-content')
<div x-data="attendanceHistoryManager()">
    <!-- Modern Page Header with Enhanced Actions -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Riwayat Kehadiran</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    @can('view_attendance_all')
                        Kelola dan analisis data kehadiran semua karyawan
                    @else
                        Lacak kehadiran harian dan jam kerja Anda
                    @endcan
                </p>
            </div>
            <div class="flex items-center space-x-3">
                <!-- Secondary Actions -->
                <button type="button" @click="toggleView()" class="bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600 border border-gray-300 dark:border-gray-600 px-4 py-2 rounded-lg font-medium transition-all duration-200 flex items-center space-x-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                    </svg>
                    <span x-text="viewMode === 'table' ? 'Calendar View' : 'Table View'"></span>
                </button>
                <button type="button" @click="openFilterModal()" class="bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600 border border-gray-300 dark:border-gray-600 px-4 py-2 rounded-lg font-medium transition-all duration-200 flex items-center space-x-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                    </svg>
                    <span>Filter</span>
                </button>
                <!-- Primary Actions -->
                <button type="button" @click="exportAttendance()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-all duration-200 flex items-center space-x-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"/>
                    </svg>
                    <span>Export Data</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Enhanced Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Present Days Card -->
        <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-gradient-to-r from-green-500 to-emerald-600 rounded-lg shadow-md">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <span class="text-sm text-green-600 bg-green-100 dark:bg-green-900/30 px-2 py-1 rounded-full">Hadir</span>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100" id="stat-present">--</h3>
                <p class="text-gray-600 dark:text-gray-400 text-sm">Hari Hadir</p>
                <div class="mt-3 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    <div class="bg-gradient-to-r from-green-500 to-emerald-600 h-2 rounded-full transition-all duration-300" style="width: 85%"></div>
                </div>
            </div>
        </x-ui.card>

        <!-- Late Days Card -->
        <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-gradient-to-r from-amber-500 to-orange-600 rounded-lg shadow-md">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <span class="text-sm text-amber-600 bg-amber-100 dark:bg-amber-900/30 px-2 py-1 rounded-full">Terlambat</span>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100" id="stat-late">--</h3>
                <p class="text-gray-600 dark:text-gray-400 text-sm">Keterlambatan</p>
                <div class="mt-3 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    <div class="bg-gradient-to-r from-amber-500 to-orange-600 h-2 rounded-full transition-all duration-300" style="width: 15%"></div>
                </div>
            </div>
        </x-ui.card>

        <!-- Total Hours Card -->
        <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg shadow-md">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <span class="text-sm text-blue-600 bg-blue-100 dark:bg-blue-900/30 px-2 py-1 rounded-full">Jam Kerja</span>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100" id="stat-hours">--</h3>
                <p class="text-gray-600 dark:text-gray-400 text-sm">Total Jam Kerja</p>
                <div class="mt-3 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    <div class="bg-gradient-to-r from-blue-500 to-indigo-600 h-2 rounded-full transition-all duration-300" style="width: 92%"></div>
                </div>
            </div>
        </x-ui.card>

        <!-- Attendance Rate Card -->
        <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-gradient-to-r from-purple-500 to-pink-600 rounded-lg shadow-md">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <span class="text-sm text-purple-600 bg-purple-100 dark:bg-purple-900/30 px-2 py-1 rounded-full">Rate</span>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100" id="stat-rate">--</h3>
                <p class="text-gray-600 dark:text-gray-400 text-sm">Tingkat Kehadiran</p>
                <div class="mt-3 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    <div class="bg-gradient-to-r from-purple-500 to-pink-600 h-2 rounded-full transition-all duration-300" style="width: 88%"></div>
                </div>
            </div>
        </x-ui.card>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Attendance Data Table (3 columns) -->
        <div class="lg:col-span-3">
            <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                                @can('view_attendance_all')
                                    Catatan Kehadiran Karyawan
                                @else
                                    Catatan Kehadiran Saya
                                @endcan
                            </h3>
                            <p class="text-gray-600 dark:text-gray-400">Riwayat lengkap check-in dan check-out</p>
                        </div>
                        <div class="flex items-center space-x-2">
                            <button type="button" @click="refreshAttendanceData()" class="bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 p-2 rounded-lg transition-colors duration-200" title="Refresh Data">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table id="attendanceTable" class="min-w-full">
                            <thead>
                                <tr class="border-b border-gray-200 dark:border-gray-700">
                                    <th class="px-6 py-3 text-left text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tanggal</th>
                                    @can('view_attendance_all')
                                    <th class="px-6 py-3 text-left text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Karyawan</th>
                                    <th class="px-6 py-3 text-left text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ID</th>
                                    @endcan
                                    <th class="px-6 py-3 text-left text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Check In</th>
                                    <th class="px-6 py-3 text-left text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Check Out</th>
                                    <th class="px-6 py-3 text-left text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Jam</th>
                                    <th class="px-6 py-3 text-left text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <!-- Table content will be loaded via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </x-ui.card>
        </div>

        <!-- Quick Actions and Filters Sidebar -->
        <div class="space-y-6">
            <!-- Filter Panel -->
            <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Filter Cepat</h3>
                </div>
                <div class="p-6 space-y-4">
                    <button type="button" @click="quickFilter('today')" class="w-full text-left px-4 py-3 bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 rounded-lg transition-colors duration-200">
                        <div class="flex items-center space-x-3">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <div>
                                <div class="font-medium text-gray-900 dark:text-white">Hari Ini</div>
                                <div class="text-xs text-gray-500">{{ now()->format('d M Y') }}</div>
                            </div>
                        </div>
                    </button>

                    <button type="button" @click="quickFilter('week')" class="w-full text-left px-4 py-3 bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 rounded-lg transition-colors duration-200">
                        <div class="flex items-center space-x-3">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-.895 2-2 2s-2-.895-2-2 .895-2 2-2 2 .895 2 2zm12-3c0 1.105-.895 2-2 2s-2-.895-2-2 .895-2 2-2 2 .895 2 2z"/>
                            </svg>
                            <div>
                                <div class="font-medium text-gray-900 dark:text-white">Minggu Ini</div>
                                <div class="text-xs text-gray-500">7 hari terakhir</div>
                            </div>
                        </div>
                    </button>

                    <button type="button" @click="quickFilter('month')" class="w-full text-left px-4 py-3 bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 rounded-lg transition-colors duration-200">
                        <div class="flex items-center space-x-3">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <div>
                                <div class="font-medium text-gray-900 dark:text-white">Bulan Ini</div>
                                <div class="text-xs text-gray-500">{{ now()->format('F Y') }}</div>
                            </div>
                        </div>
                    </button>

                    <button type="button" @click="openFilterModal()" class="w-full text-left px-4 py-3 bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-100 dark:hover:bg-blue-900/30 rounded-lg transition-colors duration-200 border border-blue-200 dark:border-blue-800">
                        <div class="flex items-center space-x-3">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4"/>
                            </svg>
                            <div>
                                <div class="font-medium text-blue-900 dark:text-blue-100">Filter Advanced</div>
                                <div class="text-xs text-blue-700 dark:text-blue-300">Custom date range & status</div>
                            </div>
                        </div>
                    </button>
                </div>
            </x-ui.card>

            <!-- Status Legend -->
            <x-ui.card class="bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-900 border border-gray-200 dark:border-gray-700">
                <div class="p-6">
                    <div class="flex items-center space-x-3 mb-4">
                        <div class="p-2 bg-gray-600 rounded-lg">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-900 dark:text-gray-100">Status Legend</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Keterangan status kehadiran</p>
                        </div>
                    </div>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Hadir</span>
                            <span class="text-xs text-green-600 bg-green-100 dark:bg-green-900/30 px-2 py-1 rounded-full">Present</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Terlambat</span>
                            <span class="text-xs text-amber-600 bg-amber-100 dark:bg-amber-900/30 px-2 py-1 rounded-full">Late</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Tidak Hadir</span>
                            <span class="text-xs text-red-600 bg-red-100 dark:bg-red-900/30 px-2 py-1 rounded-full">Absent</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Pulang Awal</span>
                            <span class="text-xs text-orange-600 bg-orange-100 dark:bg-orange-900/30 px-2 py-1 rounded-full">Early</span>
                        </div>
                    </div>
                </div>
            </x-ui.card>

            <!-- Recent Activity -->
            <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Aktivitas Terbaru</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div class="flex items-center space-x-3">
                            <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                            <div class="flex-1">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">Check-in berhasil</div>
                                <div class="text-xs text-gray-500">Hari ini, 08:30</div>
                            </div>
                        </div>
                        <div class="flex items-center space-x-3">
                            <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                            <div class="flex-1">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">Data exported</div>
                                <div class="text-xs text-gray-500">Kemarin, 17:45</div>
                            </div>
                        </div>
                        <div class="flex items-center space-x-3">
                            <div class="w-2 h-2 bg-purple-500 rounded-full"></div>
                            <div class="flex-1">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">Check-out completed</div>
                                <div class="text-xs text-gray-500">Kemarin, 17:30</div>
                            </div>
                        </div>
                    </div>
                </div>
            </x-ui.card>
        </div>
    </div>

    <!-- Enhanced Modals -->
    <x-ui.modal name="filterModal" id="filterModal" title="Filter Kehadiran" size="2xl">
        <form id="filterForm" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Date Range -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tanggal Mulai</label>
                    <input type="date" name="start_date" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tanggal Selesai</label>
                    <input type="date" name="end_date" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200">
                </div>
            </div>
            
            @can('view_attendance_all')
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Karyawan</label>
                <select name="employee_id" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200">
                    <option value="">Semua Karyawan</option>
                    <!-- Employee options will be populated via AJAX -->
                </select>
            </div>
            @endcan

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status</label>
                <select name="status" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200">
                    <option value="">Semua Status</option>
                    <option value="present">Hadir</option>
                    <option value="late">Terlambat</option>
                    <option value="absent">Tidak Hadir</option>
                    <option value="early_departure">Pulang Awal</option>
                    <option value="incomplete">Tidak Lengkap</option>
                </select>
            </div>
            
            <div class="flex justify-end space-x-3 pt-4">
                <button type="button" @click="$dispatch('close-modal')" class="px-4 py-2 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors duration-200">
                    Batal
                </button>
                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors duration-200">
                    Terapkan Filter
                </button>
            </div>
        </form>
    </x-ui.modal>

    <x-ui.modal name="detailsModal" id="detailsModal" title="Detail Kehadiran" size="3xl">
        <div id="attendance-details" class="space-y-6">
            <!-- Details content will be loaded via AJAX -->
        </div>
    </x-ui.modal>

    <x-ui.modal name="manualCheckoutModal" id="manualCheckoutModal" title="Check-out Manual">
        <form id="manualCheckoutForm" class="space-y-6">
            <!-- Manual checkout form content -->
        </form>
    </x-ui.modal>
</div>

@push('scripts')
<script>
function attendanceHistoryManager() {
    return {
        viewMode: 'table',
        currentFilter: 'all',
        
        init() {
            console.log('Attendance history manager initialized');
            this.loadAttendanceData();
            this.loadStatistics();
        },
        
        toggleView() {
            this.viewMode = this.viewMode === 'table' ? 'calendar' : 'table';
            console.log('View mode changed to:', this.viewMode);
        },
        
        openFilterModal() {
            this.$dispatch('open-modal', 'filterModal');
        },
        
        quickFilter(period) {
            this.currentFilter = period;
            console.log('Quick filter applied:', period);
            this.loadAttendanceData();
        },
        
        exportAttendance() {
            console.log('Exporting attendance data...');
            // Implementation for export functionality
        },
        
        refreshAttendanceData() {
            console.log('Refreshing attendance data...');
            this.loadAttendanceData();
            this.loadStatistics();
        },
        
        loadAttendanceData() {
            // AJAX call to load attendance data
            console.log('Loading attendance data...');
        },
        
        loadStatistics() {
            // AJAX call to load statistics
            console.log('Loading statistics...');
        }
    }
}

// Initialize DataTable when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // DataTable initialization code here
});
</script>
@endpush
@endsection
