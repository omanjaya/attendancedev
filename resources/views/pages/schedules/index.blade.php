@extends('layouts.authenticated')
@section('title', 'Schedule Management')

@section('page-content')
<div class="min-h-screen bg-background">
    <div class="p-4 sm:p-6 lg:p-8">
        <!-- Page Header - macOS Style -->
        <div class="page-header">
            <div class="page-header-flex">
                <div>
                    <h1 class="page-title">Manajemen Jadwal</h1>
                    <p class="page-desc">Kelola jadwal dan penugasan</p>
                </div>
                <div class="page-actions">
                    <x-ui.button variant="outline" href="/schedules/dashboard">
                        <x-icons.chart-bar class="w-4 h-4" />
                        <span class="hidden sm:inline">Dashboard</span>
                    </x-ui.button>
                    <x-ui.button variant="primary" href="{{ route('schedule-management.monthly.create') }}">
                        <x-icons.plus class="w-4 h-4" />
                        <span class="hidden sm:inline">Buat Jadwal</span>
                    </x-ui.button>
                </div>
            </div>
        </div>

        <!-- Quick Stats - macOS Style (Responsive) -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4 mb-6">
            <x-ui.card class="p-3 sm:p-4">
                <div class="flex items-center justify-between mb-2 sm:mb-3">
                    <x-icons.calendar class="w-4 h-4 sm:w-5 sm:h-5 text-primary" />
                    <span class="badge badge-info text-[10px] sm:text-xs">Aktif</span>
                </div>
                <p class="metric-label mb-0.5 sm:mb-1">Jadwal Bulanan</p>
                <p class="metric-value">0</p>
            </x-ui.card>

            <x-ui.card class="p-3 sm:p-4">
                <div class="flex items-center justify-between mb-2 sm:mb-3">
                    <x-icons.users class="w-4 h-4 sm:w-5 sm:h-5 text-success" />
                    <span class="badge badge-success text-[10px] sm:text-xs">Online</span>
                </div>
                <p class="metric-label mb-0.5 sm:mb-1">Jadwal Mengajar</p>
                <p class="metric-value">0</p>
            </x-ui.card>

            <x-ui.card class="p-3 sm:p-4">
                <div class="flex items-center justify-between mb-2 sm:mb-3">
                    <x-icons.calendar class="w-4 h-4 sm:w-5 sm:h-5 text-warning" />
                    <span class="badge badge-warning text-[10px] sm:text-xs">Libur</span>
                </div>
                <p class="metric-label mb-0.5 sm:mb-1">Hari Libur</p>
                <p class="metric-value">0</p>
            </x-ui.card>

            <x-ui.card class="p-3 sm:p-4">
                <div class="flex items-center justify-between mb-2 sm:mb-3">
                    <x-icons.users class="w-4 h-4 sm:w-5 sm:h-5 text-purple-500" />
                    <span class="badge badge-purple text-[10px] sm:text-xs">Staff</span>
                </div>
                <p class="metric-label mb-0.5 sm:mb-1">Terjadwal</p>
                <p class="metric-value">0</p>
            </x-ui.card>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
            <!-- Schedule Management Cards -->
            <div class="lg:col-span-2">
                <x-ui.card>
                    <div class="table-header">
                        <h3 class="section-title">Manajemen Jadwal</h3>
                        <p class="section-desc">Kelola berbagai jenis jadwal</p>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Monthly Schedule Card -->
                            <a href="{{ route('schedule-management.monthly.create') }}" class="group block">
                                <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/30 dark:to-blue-800/30 border border-blue-200 dark:border-blue-700 rounded-lg p-6 hover:shadow-lg transition-all duration-300">
                                    <div class="flex items-center space-x-4">
                                        <div class="p-3 bg-blue-600 rounded-lg shadow-md group-hover:bg-blue-700 transition-colors">
                                            <i class="fas fa-calendar-alt text-white text-xl"></i>
                                        </div>
                                        <div>
                                            <h4 class="text-lg font-semibold text-blue-900 dark:text-blue-100">Jadwal Bulanan</h4>
                                            <p class="text-sm text-blue-600 dark:text-blue-300">Buat & kelola jadwal kerja bulanan</p>
                                        </div>
                                    </div>
                                    <div class="mt-4 text-right">
                                        <span class="text-xs text-blue-600 dark:text-blue-300">0 jadwal aktif</span>
                                    </div>
                                </div>
                            </a>

                            <!-- Teaching Schedule Card -->
                            <a href="{{ route('schedule-management.teaching.index') }}" class="group block">
                                <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/30 dark:to-green-800/30 border border-green-200 dark:border-green-700 rounded-lg p-6 hover:shadow-lg transition-all duration-300">
                                    <div class="flex items-center space-x-4">
                                        <div class="p-3 bg-green-600 rounded-lg shadow-md group-hover:bg-green-700 transition-colors">
                                            <i class="fas fa-chalkboard-teacher text-white text-xl"></i>
                                        </div>
                                        <div>
                                            <h4 class="text-lg font-semibold text-green-900 dark:text-green-100">Jadwal Mengajar</h4>
                                            <p class="text-sm text-green-600 dark:text-green-300">Atur jadwal mengajar guru</p>
                                        </div>
                                    </div>
                                    <div class="mt-4 text-right">
                                        <span class="text-xs text-green-600 dark:text-green-300">0 jadwal aktif</span>
                                    </div>
                                </div>
                            </a>

                            <!-- Holiday Management Card -->
                            <a href="{{ route('holidays.index') }}" class="group block">
                                <div class="bg-gradient-to-br from-amber-50 to-amber-100 dark:from-amber-900/30 dark:to-amber-800/30 border border-amber-200 dark:border-amber-700 rounded-lg p-6 hover:shadow-lg transition-all duration-300">
                                    <div class="flex items-center space-x-4">
                                        <div class="p-3 bg-amber-600 rounded-lg shadow-md group-hover:bg-amber-700 transition-colors">
                                            <i class="fas fa-calendar-times text-white text-xl"></i>
                                        </div>
                                        <div>
                                            <h4 class="text-lg font-semibold text-amber-900 dark:text-amber-100">Hari Libur</h4>
                                            <p class="text-sm text-amber-600 dark:text-amber-300">Kelola kalender libur nasional</p>
                                        </div>
                                    </div>
                                    <div class="mt-4 text-right">
                                        <span class="text-xs text-amber-600 dark:text-amber-300">0 hari libur</span>
                                    </div>
                                </div>
                            </a>

                            <!-- Employee Assignment Card -->
                            <a href="/schedule-management/assign" class="group block">
                                <div class="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/30 dark:to-purple-800/30 border border-purple-200 dark:border-purple-700 rounded-lg p-6 hover:shadow-lg transition-all duration-300">
                                    <div class="flex items-center space-x-4">
                                        <div class="p-3 bg-purple-600 rounded-lg shadow-md group-hover:bg-purple-700 transition-colors">
                                            <i class="fas fa-user-plus text-white text-xl"></i>
                                        </div>
                                        <div>
                                            <h4 class="text-lg font-semibold text-purple-900 dark:text-purple-100">Penugasan</h4>
                                            <p class="text-sm text-purple-600 dark:text-purple-300">Tugaskan karyawan ke jadwal</p>
                                        </div>
                                    </div>
                                    <div class="mt-4 text-right">
                                        <span class="text-xs text-purple-600 dark:text-purple-300">0 karyawan</span>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions Sidebar -->
            <div class="space-y-6">
                <!-- Quick Actions -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Quick Actions</h3>
                    </div>
                    <div class="p-6 space-y-3">
                        <a href="{{ route('schedule-management.monthly.create') }}" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-center block transition-colors">
                            <i class="fas fa-plus mr-2"></i>Buat Jadwal Baru
                        </a>
                        <a href="/schedule-management/assign" class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-center block transition-colors">
                            <i class="fas fa-user-plus mr-2"></i>Tugaskan Karyawan
                        </a>
                        <a href="{{ route('holidays.index') }}" class="w-full bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded-lg text-center block transition-colors">
                            <i class="fas fa-calendar-times mr-2"></i>Kelola Libur
                        </a>
                        <a href="/schedule-management/dashboard" class="w-full bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-center block transition-colors">
                            <i class="fas fa-chart-line mr-2"></i>Lihat Dashboard
                        </a>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Aktivitas Terbaru</h3>
                    </div>
                    <div class="p-6">
                        <p class="text-gray-500 dark:text-gray-400 text-sm">Belum ada aktivitas terbaru</p>
                    </div>
                </div>

                <!-- System Status -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Status Sistem</h3>
                    </div>
                    <div class="p-6 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Database</span>
                            <span class="text-xs bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 px-2 py-1 rounded-full">
                                <i class="fas fa-check-circle mr-1"></i>Online
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Background Jobs</span>
                            <span class="text-xs bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 px-2 py-1 rounded-full">
                                <i class="fas fa-check-circle mr-1"></i>Running
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Schedule Sync</span>
                            <span class="text-xs bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 px-2 py-1 rounded-full">
                                <i class="fas fa-sync-alt mr-1"></i>Synced
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection