@extends('layouts.authenticated-unified')

@section('title', 'Laporan & Analytics')

@section('page-content')
<div x-data="reportsManager()">
    <!-- Modern Page Header with Enhanced Actions -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Laporan & Analytics</h1>
                <p class="page-subtitle">Kelola laporan, analisis data, dan export informasi sistem</p>
            </div>
            <div class="flex items-center space-x-3">
                <!-- Schedule Report Button -->
                <button @click="openScheduleModal()" class="btn-schedule">
                    <x-icons.clock class="w-5 h-5 mr-2 inline" />
                    Jadwalkan
                </button>
                
                <!-- Analytics Dashboard Button -->
                <a href="{{ route('analytics.dashboard') }}" class="btn-analytics">
                    <x-icons.chart-bar class="w-5 h-5 mr-2 inline" />
                    Analytics
                </a>
                
                <!-- Custom Report Builder Button -->
                <a href="{{ route('reports.builder') }}" class="btn-builder">
                    <x-icons.edit class="w-5 h-5 mr-2 inline" />
                    Report Builder
                </a>
            </div>
        </div>
    </div>

    <!-- Enhanced Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
        <!-- Today's Attendance Card -->
        <x-ui.card>
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="metric-icon metric-icon-green">
                        <x-icons.check-circle class="w-6 h-6 text-white" />
                    </div>
                    <span class="text-sm font-medium px-3 py-1 bg-green-100 text-green-800 rounded-full">Live</span>
                </div>
                <h3 class="metric-heading">{{ $quickStats['todays_attendance'] ?? 0 }}</h3>
                <p class="text-gray-600 dark:text-gray-400 text-sm mt-1">Kehadiran Hari Ini</p>
                <div class="mt-3 text-sm">
                    <span class="text-green-600 font-medium">+12%</span>
                    <span class="text-gray-500 ml-1">dari kemarin</span>
                </div>
            </div>
        </x-ui.card>

        <!-- Pending Leaves Card -->
        <x-ui.card>
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="metric-icon metric-icon-amber">
                        <x-icons.clock class="w-6 h-6 text-white" />
                    </div>
                    <span class="status-badge status-badge-pending">Pending</span>
                </div>
                <h3 class="metric-heading">{{ $quickStats['pending_leaves'] ?? 0 }}</h3>
                <p class="text-gray-600 dark:text-gray-400 text-sm mt-1">Cuti Tertunda</p>
                <div class="mt-3 text-sm">
                    <span class="metric-value-orange">{{ $quickStats['pending_leaves'] ?? 0 > 0 ? 'Butuh Review' : 'Up to Date' }}</span>
                </div>
            </div>
        </x-ui.card>

        <!-- Monthly Payrolls Card -->
        <x-ui.card>
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="metric-icon metric-icon-blue">
                        <x-icons.currency-dollar class="w-6 h-6 text-white" />
                    </div>
                    <span class="text-sm font-medium px-3 py-1 bg-blue-100 text-blue-800 rounded-full">Monthly</span>
                </div>
                <h3 class="metric-heading">{{ $quickStats['monthly_payrolls'] ?? 0 }}</h3>
                <p class="text-gray-600 dark:text-gray-400 text-sm mt-1">Penggajian Bulanan</p>
                <div class="mt-3 text-sm">
                    <span class="metric-value-blue">Rp {{ number_format(($quickStats['payroll_total'] ?? 0), 0, ',', '.') }}</span>
                </div>
            </div>
        </x-ui.card>

        <!-- Total Employees Card -->
        <x-ui.card>
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="metric-icon metric-icon-purple">
                        <x-icons.users class="w-6 h-6 text-white" />
                    </div>
                    <span class="status-badge status-badge-active">Active</span>
                </div>
                <h3 class="metric-heading">{{ $quickStats['total_employees'] ?? 0 }}</h3>
                <p class="text-gray-600 dark:text-gray-400 text-sm mt-1">Total Karyawan</p>
                <div class="mt-3 text-sm">
                    <span class="text-purple-600 font-medium">{{ ($quickStats['active_employees'] ?? 0) }} Aktif</span>
                </div>
            </div>
        </x-ui.card>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 xl:grid-cols-4 gap-8">
        <!-- Report Types Grid (Main Panel) -->
        <div class="xl:col-span-3">
            <!-- Report Categories -->
            <div class="mb-6">
                <div class="flex items-center space-x-1 bg-gray-100 dark:bg-gray-700 rounded-lg p-1">
                    <button @click="selectedCategory = 'all'" :class="selectedCategory === 'all' ? 'category-btn-active' : ''" class="category-btn">
                        Semua
                    </button>
                    <button @click="selectedCategory = 'attendance'" :class="selectedCategory === 'attendance' ? 'category-btn-active' : ''" class="category-btn">
                        Kehadiran
                    </button>
                    <button @click="selectedCategory = 'payroll'" :class="selectedCategory === 'payroll' ? 'category-btn-active' : ''" class="category-btn">
                        Penggajian
                    </button>
                    <button @click="selectedCategory = 'employee'" :class="selectedCategory === 'employee' ? 'category-btn-active' : ''" class="category-btn">
                        Karyawan
                    </button>
                    <button @click="selectedCategory = 'analytics'" :class="selectedCategory === 'analytics' ? 'category-btn-active' : ''" class="category-btn">
                        Analytics
                    </button>
                </div>
            </div>

            <!-- Enhanced Report Types Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                @foreach($reportTypes as $key => $reportType)
                <x-ui.card class="report-card">
                    <div class="p-6">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center">
                                <div class="mr-4">
                                    <div class="w-12 h-12 rounded-lg bg-gradient-to-r @php
                                        $iconColors = [
                                            'calendar-check' => 'from-green-600 to-emerald-600',
                                            'calendar-x' => 'from-orange-600 to-orange-700', 
                                            'currency-dollar' => 'from-blue-600 to-blue-700',
                                            'users' => 'from-purple-600 to-purple-700',
                                            'chart-bar' => 'from-indigo-600 to-purple-600'
                                        ];
                                        echo $iconColors[$reportType['icon']] ?? 'from-gray-600 to-gray-700';
                                    @endphp flex items-center justify-center shadow-md">
                                        @switch($reportType['icon'])
                                            @case('calendar-check') <x-icons.calendar-check class="w-6 h-6 text-white" /> @break
                                            @case('calendar-x') <x-icons.calendar-x class="w-6 h-6 text-white" /> @break
                                            @case('currency-dollar') <x-icons.currency-dollar class="w-6 h-6 text-white" /> @break
                                            @case('users') <x-icons.users class="w-6 h-6 text-white" /> @break
                                            @case('chart-bar') <x-icons.chart-bar class="w-6 h-6 text-white" /> @break
                                        @endswitch
                                    </div>
                                </div>
                                <div>
                                    <h3 class="report-type-header">{{ $reportType['name'] }}</h3>
                                    <div class="flex items-center space-x-2">
                                        <span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-full">
                                            {{ ucfirst($reportType['category'] ?? 'general') }}
                                        </span>
                                        <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400 rounded-full">
                                            {{ $reportType['frequency'] ?? 'On-demand' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Report Status Indicator -->
                            <div class="flex items-center">
                                <div class="w-3 h-3 bg-green-400 rounded-full animate-pulse"></div>
                            </div>
                        </div>
                        
                        <p class="text-gray-600 dark:text-gray-400 mb-4 text-sm leading-relaxed">{{ $reportType['description'] }}</p>
                        
                        <!-- Report Metrics -->
                        <div class="flex items-center justify-between mb-6 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div class="text-center">
                                <div class="text-lg font-bold text-gray-900 dark:text-white">{{ $reportType['last_generated'] ?? 'N/A' }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Terakhir Dibuat</div>
                            </div>
                            <div class="text-center">
                                <div class="text-lg font-bold text-gray-900 dark:text-white">{{ $reportType['total_downloads'] ?? 0 }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Total Download</div>
                            </div>
                            <div class="text-center">
                                <div class="text-lg font-bold text-gray-900 dark:text-white">{{ $reportType['file_size'] ?? '—' }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Ukuran File</div>
                            </div>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="flex items-center space-x-3">
                            <a href="{{ route('reports.' . $key) }}" class="flex-1 inline-flex items-center justify-center px-4 py-2 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white rounded-lg font-medium transition-all duration-200 transform hover:scale-105 shadow-sm">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                Lihat Laporan
                            </a>
                            
                            <!-- Export Dropdown -->
                            <div class="relative" x-data="{ open: false }">
                                <button @click="open = !open" class="inline-flex items-center justify-center px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg font-medium transition-all duration-200 transform hover:scale-105">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    Export
                                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </button>
                                <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg z-20 border border-gray-200 dark:border-gray-700">
                                    <div class="py-2">
                                        <a href="#" class="flex items-center px-4 py-2 text-sm text-gray-900 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-200" onclick="exportReport('{{ $key }}', 'pdf')">
                                            <svg class="w-4 h-4 mr-3 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                            </svg>
                                            Export PDF
                                        </a>
                                        <a href="#" class="flex items-center px-4 py-2 text-sm text-gray-900 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-200" onclick="exportReport('{{ $key }}', 'excel')">
                                            <svg class="w-4 h-4 mr-3 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                            Export Excel
                                        </a>
                                        <a href="#" class="flex items-center px-4 py-2 text-sm text-gray-900 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-200" onclick="exportReport('{{ $key }}', 'csv')">
                                            <svg class="w-4 h-4 mr-3 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                            Export CSV
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </x-ui.card>
                @endforeach
            </div>
        </div>

        <!-- Quick Actions Sidebar -->
        <div class="xl:col-span-1">
            <!-- Quick Actions Card -->
            <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-6">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        Quick Actions
                    </h3>
                </div>
                <div class="p-6 space-y-3">
                    <a href="{{ route('reports.builder') }}" class="w-full text-left px-4 py-3 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 hover:from-green-100 hover:to-emerald-100 dark:hover:from-green-900/30 dark:hover:to-emerald-900/30 rounded-lg transition-all duration-200 transform hover:scale-105 border border-green-200 dark:border-green-700">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-3 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            <div>
                                <div class="font-medium text-gray-900 dark:text-white">Report Builder</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Buat laporan kustom</div>
                            </div>
                        </div>
                    </a>
                    
                    <button @click="openScheduleModal()" class="w-full text-left px-4 py-3 bg-purple-50 dark:bg-purple-900/20 hover:bg-purple-100 dark:hover:bg-purple-900/30 rounded-lg transition-all duration-200 transform hover:scale-105 border border-purple-200 dark:border-purple-700">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-3 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div>
                                <div class="font-medium text-gray-900 dark:text-white">Jadwal Laporan</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Otomatisasi laporan</div>
                            </div>
                        </div>
                    </button>
                    
                    <button @click="viewScheduledReports()" class="w-full text-left px-4 py-3 bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-100 dark:hover:bg-blue-900/30 rounded-lg transition-all duration-200 transform hover:scale-105 border border-blue-200 dark:border-blue-700">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-3 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                            </svg>
                            <div>
                                <div class="font-medium text-gray-900 dark:text-white">Laporan Terjadwal</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Lihat jadwal aktif</div>
                            </div>
                        </div>
                    </button>
                    
                    <div class="pt-3 border-t border-gray-200 dark:border-gray-700">
                        <button @click="bulkExport()" class="w-full text-left px-4 py-3 bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 rounded-lg transition-colors duration-200">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 mr-3 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-white">Bulk Export</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">Export multiple reports</div>
                                </div>
                            </div>
                        </button>
                    </div>
                </div>
            </x-ui.card>

            <!-- Recent Activity -->
            <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-6">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Aktivitas Terbaru
                    </h3>
                </div>
                <div class="p-6 space-y-4">
                    <div class="flex items-center space-x-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <div class="w-8 h-8 bg-green-600 rounded-full flex items-center justify-center">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <div class="text-sm font-medium text-gray-900 dark:text-white">Laporan Kehadiran</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Berhasil di-export • 2 menit yang lalu</div>
                        </div>
                    </div>
                    
                    <div class="flex items-center space-x-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <div class="text-sm font-medium text-gray-900 dark:text-white">Laporan Penggajian</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Terjadwal otomatis • 1 jam yang lalu</div>
                        </div>
                    </div>
                    
                    <div class="flex items-center space-x-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <div class="w-8 h-8 bg-purple-600 rounded-full flex items-center justify-center">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <div class="text-sm font-medium text-gray-900 dark:text-white">Analytics Report</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Sedang diproses • 3 jam yang lalu</div>
                        </div>
                    </div>
                </div>
            </x-ui.card>

            <!-- Export Statistics -->
            <x-ui.card>
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <svg class="w-5 h-5 mr-2 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        Statistik Export
                    </h3>
                </div>
                <div class="p-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Hari Ini</span>
                        <span class="text-sm font-bold text-gray-900 dark:text-white">24 exports</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Minggu Ini</span>
                        <span class="text-sm font-bold text-gray-900 dark:text-white">156 exports</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Bulan Ini</span>
                        <span class="text-sm font-bold text-gray-900 dark:text-white">1,243 exports</span>
                    </div>
                    
                    <div class="pt-3 border-t border-gray-200 dark:border-gray-700">
                        <div class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">Format Populer</div>
                        <div class="space-y-2">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-600 dark:text-gray-400">PDF</span>
                                <span class="font-semibold text-red-600">45%</span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-600 dark:text-gray-400">Excel</span>
                                <span class="font-semibold text-green-600">35%</span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-600 dark:text-gray-400">CSV</span>
                                <span class="font-semibold text-blue-600">20%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </x-ui.card>
        </div>
    </div>

<!-- Modals -->
<x-ui.modal id="scheduleReportModal" title="Jadwalkan Laporan Otomatis">
    <form id="scheduleReportForm" class="space-y-4">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <x-ui.label for="report_type" value="Tipe Laporan" />
                <x-ui.select name="report_type" required>
                    <option value="">Pilih Tipe Laporan</option>
                    @foreach($reportTypes as $key => $reportType)<option value="{{ $key }}">{{ $reportType['name'] }}</option>@endforeach
                </x-ui.select>
            </div>
            <div>
                <x-ui.label for="schedule_type" value="Jadwal" />
                <x-ui.select name="schedule_type" required>
                    <option value="">Pilih Jadwal</option>
                    <option value="daily">Harian</option>
                    <option value="weekly">Mingguan</option>
                    <option value="monthly">Bulanan</option>
                    <option value="quarterly">Triwulanan</option>
                </x-ui.select>
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <x-ui.label for="format" value="Format Ekspor" />
                <x-ui.select name="format" required>
                    <option value="">Pilih Format</option>
                    <option value="pdf">PDF</option>
                    <option value="csv">CSV</option>
                    <option value="excel">Excel</option>
                </x-ui.select>
            </div>
            <div>
                <x-ui.label for="recipients" value="Penerima" />
                <x-ui.input type="email" name="recipients[]" placeholder="Masukkan alamat email" required />
                <p class="text-xs text-muted-foreground mt-1">Pisahkan beberapa email dengan koma.</p>
            </div>
        </div>
        <div class="flex justify-end space-x-2 pt-4">
            <x-ui.button type="button" variant="outline" onclick="closeModal('scheduleReportModal')">Batal</x-ui.button>
            <x-ui.button type="submit">Jadwalkan Laporan</x-ui.button>
        </div>
    </form>
</x-ui.modal>

<x-ui.modal id="scheduledReportsModal" title="Laporan Terjadwal" size="4xl">
    <div id="scheduledReportsTable"></div>
    <div class="flex justify-end pt-4">
        <x-ui.button type="button" variant="outline" onclick="closeModal('scheduledReportsModal')">Tutup</x-ui.button>
    </div>
</x-ui.modal>

</div>
@endsection

@push('scripts')
<script>
function reportsManager() {
    return {
        selectedCategory: 'all',
        showScheduleModal: false,
        showScheduledReportsModal: false,
        
        openScheduleModal() {
            this.showScheduleModal = true;
        },
        
        closeScheduleModal() {
            this.showScheduleModal = false;
        },
        
        viewScheduledReports() {
            this.showScheduledReportsModal = true;
            // Load scheduled reports data
            this.loadScheduledReports();
        },
        
        closeScheduledReportsModal() {
            this.showScheduledReportsModal = false;
        },
        
        loadScheduledReports() {
            // Simulate loading scheduled reports
            console.log('Loading scheduled reports...');
        },
        
        bulkExport() {
            const formats = ['PDF', 'Excel', 'CSV'];
            const selectedFormat = prompt(`Select export format:\n${formats.map((f, i) => `${i+1}. ${f}`).join('\n')}`, '1');
            
            if (selectedFormat && selectedFormat >= 1 && selectedFormat <= 3) {
                const format = formats[selectedFormat - 1];
                alert(`Bulk export in ${format} format will start shortly!`);
            }
        }
    }
}

function exportReport(reportType, format) {
    // Show loading state
    const button = event.target;
    const originalText = button.textContent;
    button.textContent = 'Exporting...';
    button.disabled = true;
    
    // Simulate export process
    setTimeout(() => {
        alert(`Report "${reportType}" exported successfully as ${format.toUpperCase()}!`);
        button.textContent = originalText;
        button.disabled = false;
    }, 2000);
}

// Helper function for getting report icon colors
function getReportIconColor(icon) {
    const colorMap = {
        'calendar-check': 'from-green-600 to-emerald-600',
        'calendar-x': 'from-orange-600 to-orange-700',
        'currency-dollar': 'from-blue-600 to-blue-700',
        'users': 'from-purple-600 to-purple-700',
        'chart-bar': 'from-indigo-600 to-purple-600'
    };
    return colorMap[icon] || 'from-gray-600 to-gray-700';
}
</script>
@endpush
