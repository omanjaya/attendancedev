@extends('layouts.authenticated')

@section('title', 'Dashboard Super Admin')

@section('page-content')
<div class="min-h-screen bg-slate-50 dark:bg-slate-900">
    <div class="max-w-7xl mx-auto p-6 lg:p-8 space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900 dark:text-white">Pusat Kontrol</h1>
                <p class="text-slate-500 dark:text-slate-400">{{ now()->format('l, d M Y') }}</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="flex items-center gap-1.5 px-2.5 py-1 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 text-xs font-medium rounded-full">
                    <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-pulse"></span>
                    Sistem Online
                </span>
            </div>
        </div>

        <!-- Statistik Utama -->
        <div class="grid grid-cols-5 gap-2 sm:gap-4">
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-2 sm:p-4">
                <div class="flex flex-col items-center sm:items-start sm:block">
                    <svg class="w-5 h-5 text-blue-500 sm:hidden mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <p class="text-xs text-slate-500 dark:text-slate-400 hidden sm:block">Total Pegawai</p>
                    <p class="text-base sm:text-2xl font-semibold text-slate-900 dark:text-white sm:mt-1">{{ $dashboardData['realtime_status']['total_employees'] ?? 0 }}</p>
                </div>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-2 sm:p-4">
                <div class="flex flex-col items-center sm:items-start sm:block">
                    <svg class="w-5 h-5 text-emerald-500 sm:hidden mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-xs text-slate-500 dark:text-slate-400 hidden sm:block">Hadir</p>
                    <p class="text-base sm:text-2xl font-semibold text-emerald-600 dark:text-emerald-400 sm:mt-1">{{ $dashboardData['realtime_status']['checked_in_today'] ?? 0 }}</p>
                </div>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-2 sm:p-4">
                <div class="flex flex-col items-center sm:items-start sm:block">
                    <svg class="w-5 h-5 text-amber-500 sm:hidden mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-xs text-slate-500 dark:text-slate-400 hidden sm:block">Terlambat</p>
                    <p class="text-base sm:text-2xl font-semibold text-amber-600 dark:text-amber-400 sm:mt-1">{{ $dashboardData['realtime_status']['late_arrivals'] ?? 0 }}</p>
                </div>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-2 sm:p-4">
                <div class="flex flex-col items-center sm:items-start sm:block">
                    <svg class="w-5 h-5 text-blue-500 sm:hidden mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    <p class="text-xs text-slate-500 dark:text-slate-400 hidden sm:block">Kehadiran</p>
                    <p class="text-base sm:text-2xl font-semibold text-slate-900 dark:text-white sm:mt-1">{{ $dashboardData['realtime_status']['attendance_rate'] ?? 0 }}%</p>
                </div>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-2 sm:p-4">
                <div class="flex flex-col items-center sm:items-start sm:block">
                    <svg class="w-5 h-5 text-purple-500 sm:hidden mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <p class="text-xs text-slate-500 dark:text-slate-400 hidden sm:block">Cuti</p>
                    <p class="text-base sm:text-2xl font-semibold text-purple-600 dark:text-purple-400 sm:mt-1">{{ $dashboardData['leave_management']['pending_requests'] ?? 0 }}</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Aktivitas Terbaru -->
            <div class="lg:col-span-2 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700">
                <div class="p-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <h2 class="font-medium text-slate-900 dark:text-white">Aktivitas Terbaru</h2>
                        <span class="flex items-center gap-1 text-xs text-emerald-600 dark:text-emerald-400">
                            <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-pulse"></span>
                            Live
                        </span>
                    </div>
                    <a href="{{ route('attendance.index') }}" class="text-sm text-emerald-600 dark:text-emerald-400 hover:underline">Lihat Semua</a>
                </div>
                <div class="divide-y divide-slate-200 dark:divide-slate-700 max-h-96 overflow-y-auto">
                    @php
                    $recentCheckIns = \App\Models\Attendance::with('employee')
                        ->whereDate('date', today())
                        ->whereNotNull('check_in_time')
                        ->orderBy('check_in_time', 'desc')
                        ->limit(8)
                        ->get();
                    @endphp
                    @forelse($recentCheckIns as $attendance)
                    <div class="p-4 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center">
                                <span class="text-sm font-medium text-slate-600 dark:text-slate-300">{{ substr($attendance->employee->first_name ?? 'U', 0, 1) }}{{ substr($attendance->employee->last_name ?? 'N', 0, 1) }}</span>
                            </div>
                            <div>
                                <p class="font-medium text-slate-900 dark:text-white">{{ $attendance->employee->first_name ?? '' }} {{ $attendance->employee->last_name ?? '' }}</p>
                                <p class="text-sm text-slate-500 dark:text-slate-400">{{ $attendance->employee->department ?? 'Staff' }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-medium text-slate-900 dark:text-white">{{ $attendance->check_in_time?->format('H:i') }}</p>
                            <span class="text-xs {{ $attendance->status === 'late' ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                                {{ $attendance->status === 'late' ? 'Terlambat' : 'Tepat Waktu' }}
                            </span>
                        </div>
                    </div>
                    @empty
                    <div class="p-8 text-center text-slate-500 dark:text-slate-400">
                        Belum ada aktivitas hari ini
                    </div>
                    @endforelse
                </div>
            </div>

            <!-- Status Sistem -->
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700">
                <div class="p-4 border-b border-slate-200 dark:border-slate-700">
                    <h2 class="font-medium text-slate-900 dark:text-white">Status Sistem</h2>
                </div>
                <div class="p-4 space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-slate-600 dark:text-slate-400">Database</span>
                        <span class="px-2 py-0.5 text-xs font-medium bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 rounded">Sehat</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-slate-600 dark:text-slate-400">Face Recognition</span>
                        <span class="px-2 py-0.5 text-xs font-medium bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 rounded">Aktif</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-slate-600 dark:text-slate-400">Sesi Aktif</span>
                        <span class="text-sm font-medium text-slate-900 dark:text-white">{{ $dashboardData['system_health']['active_sessions'] ?? 0 }}</span>
                    </div>
                    <hr class="border-slate-200 dark:border-slate-700">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-slate-600 dark:text-slate-400">Total Guru</span>
                            <span class="text-sm font-medium text-slate-900 dark:text-white">{{ $dashboardData['school_overview']['total_teachers'] ?? 0 }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-slate-600 dark:text-slate-400">Total Staff</span>
                            <span class="text-sm font-medium text-slate-900 dark:text-white">{{ $dashboardData['school_overview']['total_staff'] ?? 0 }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-slate-600 dark:text-slate-400">Cuti Hari Ini</span>
                            <span class="text-sm font-medium text-amber-600 dark:text-amber-400">{{ $dashboardData['school_overview']['on_leave_today'] ?? 0 }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Menu Cepat -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <a href="{{ route('employees.index') }}" class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 hover:border-slate-300 dark:hover:border-slate-600 transition-colors">
                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <p class="font-medium text-slate-900 dark:text-white">Pegawai</p>
            </a>
            <a href="{{ route('reports.attendance') }}" class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 hover:border-slate-300 dark:hover:border-slate-600 transition-colors">
                <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                <p class="font-medium text-slate-900 dark:text-white">Laporan</p>
            </a>
            <a href="{{ route('holidays.index') }}" class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 hover:border-slate-300 dark:hover:border-slate-600 transition-colors">
                <svg class="w-6 h-6 text-purple-600 dark:text-purple-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <p class="font-medium text-slate-900 dark:text-white">Hari Libur</p>
            </a>
            <a href="{{ route('security.dashboard') }}" class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 hover:border-slate-300 dark:hover:border-slate-600 transition-colors">
                <svg class="w-6 h-6 text-red-600 dark:text-red-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
                <p class="font-medium text-slate-900 dark:text-white">Keamanan</p>
            </a>
        </div>
    </div>
</div>
@endsection
