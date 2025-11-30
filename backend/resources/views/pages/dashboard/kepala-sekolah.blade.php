@extends('layouts.authenticated')

@section('title', 'Dashboard Kepala Sekolah')

@section('page-content')
<div class="min-h-screen bg-slate-50 dark:bg-slate-900">
    <div class="max-w-6xl mx-auto p-6 lg:p-8 space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900 dark:text-white">Dashboard Eksekutif</h1>
                <p class="text-slate-500 dark:text-slate-400">{{ now()->format('l, d M Y') }}</p>
            </div>
        </div>

        <!-- KPI Utama -->
        <div class="grid grid-cols-4 gap-2 sm:gap-4">
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-3 sm:p-4">
                <div class="flex items-center gap-2 sm:block">
                    <svg class="w-5 h-5 text-emerald-500 sm:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 hidden sm:block">Kehadiran</p>
                    <p class="text-lg sm:text-2xl font-semibold text-emerald-600 dark:text-emerald-400 sm:mt-1">{{ $dashboardData['school_performance']['average_attendance_rate'] ?? 0 }}%</p>
                </div>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-3 sm:p-4">
                <div class="flex items-center gap-2 sm:block">
                    <svg class="w-5 h-5 text-blue-500 sm:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 hidden sm:block">Ketepatan</p>
                    <p class="text-lg sm:text-2xl font-semibold text-blue-600 dark:text-blue-400 sm:mt-1">{{ $dashboardData['teacher_performance']['average_punctuality'] ?? 0 }}%</p>
                </div>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-3 sm:p-4">
                <div class="flex items-center gap-2 sm:block">
                    <svg class="w-5 h-5 text-slate-500 sm:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 hidden sm:block">Guru</p>
                    <p class="text-lg sm:text-2xl font-semibold text-slate-900 dark:text-white sm:mt-1">{{ $dashboardData['school_overview']['total_teachers'] ?? 0 }}</p>
                </div>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-3 sm:p-4">
                <div class="flex items-center gap-2 sm:block">
                    <svg class="w-5 h-5 text-slate-500 sm:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 hidden sm:block">Staf</p>
                    <p class="text-lg sm:text-2xl font-semibold text-slate-900 dark:text-white sm:mt-1">{{ $dashboardData['school_overview']['total_staff'] ?? 0 }}</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Distribusi Kinerja -->
            <div class="lg:col-span-2 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700">
                <div class="p-4 border-b border-slate-200 dark:border-slate-700">
                    <h2 class="font-medium text-slate-900 dark:text-white">Distribusi Kinerja Staf</h2>
                </div>
                <div class="p-4 space-y-4">
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-slate-600 dark:text-slate-400">Kinerja Sangat Baik</span>
                            <span class="font-medium text-emerald-600 dark:text-emerald-400">{{ $dashboardData['teacher_performance']['excellent_count'] ?? 0 }} staf</span>
                        </div>
                        <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-2">
                            <div class="bg-emerald-500 h-2 rounded-full" style="width: {{ ($dashboardData['teacher_performance']['excellent_count'] ?? 0) / max(($dashboardData['school_overview']['total_teachers'] ?? 1) + ($dashboardData['school_overview']['total_staff'] ?? 1), 1) * 100 }}%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-slate-600 dark:text-slate-400">Kinerja Baik</span>
                            <span class="font-medium text-blue-600 dark:text-blue-400">{{ $dashboardData['teacher_performance']['good_count'] ?? 0 }} staf</span>
                        </div>
                        <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-2">
                            <div class="bg-blue-500 h-2 rounded-full" style="width: {{ ($dashboardData['teacher_performance']['good_count'] ?? 0) / max(($dashboardData['school_overview']['total_teachers'] ?? 1) + ($dashboardData['school_overview']['total_staff'] ?? 1), 1) * 100 }}%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-slate-600 dark:text-slate-400">Perlu Pengembangan</span>
                            <span class="font-medium text-amber-600 dark:text-amber-400">{{ $dashboardData['teacher_performance']['improvement_count'] ?? 0 }} staf</span>
                        </div>
                        <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-2">
                            <div class="bg-amber-500 h-2 rounded-full" style="width: {{ ($dashboardData['teacher_performance']['improvement_count'] ?? 0) / max(($dashboardData['school_overview']['total_teachers'] ?? 1) + ($dashboardData['school_overview']['total_staff'] ?? 1), 1) * 100 }}%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ringkasan Sekolah -->
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700">
                <div class="p-4 border-b border-slate-200 dark:border-slate-700">
                    <h2 class="font-medium text-slate-900 dark:text-white">Ikhtisar</h2>
                </div>
                <div class="p-4 space-y-3">
                    <div class="flex justify-between">
                        <span class="text-sm text-slate-600 dark:text-slate-400">Kelas Aktif</span>
                        <span class="text-sm font-medium text-slate-900 dark:text-white">{{ $dashboardData['academic_status']['active_classes'] ?? 0 }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-slate-600 dark:text-slate-400">Total Siswa</span>
                        <span class="text-sm font-medium text-slate-900 dark:text-white">{{ $dashboardData['academic_status']['total_students'] ?? 0 }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-slate-600 dark:text-slate-400">Progres Kurikulum</span>
                        <span class="text-sm font-medium text-blue-600 dark:text-blue-400">{{ $dashboardData['academic_status']['curriculum_progress'] ?? 0 }}%</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-slate-600 dark:text-slate-400">Kepuasan Orang Tua</span>
                        <span class="text-sm font-medium text-emerald-600 dark:text-emerald-400">{{ $dashboardData['strategic_metrics']['parent_satisfaction'] ?? 0 }}%</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Menu Cepat -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <a href="{{ route('reports.index') }}" class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 hover:border-slate-300 dark:hover:border-slate-600 transition-colors">
                <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                <p class="font-medium text-slate-900 dark:text-white">Analitik</p>
            </a>
            <a href="{{ route('employees.index') }}" class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 hover:border-slate-300 dark:hover:border-slate-600 transition-colors">
                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <p class="font-medium text-slate-900 dark:text-white">Tinjauan Staf</p>
            </a>
            <a href="{{ route('attendance.check-in') }}" class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 hover:border-slate-300 dark:hover:border-slate-600 transition-colors">
                <svg class="w-6 h-6 text-purple-600 dark:text-purple-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                <p class="font-medium text-slate-900 dark:text-white">Check-in Saya</p>
            </a>
            <a href="{{ route('attendance.index') }}" class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 hover:border-slate-300 dark:hover:border-slate-600 transition-colors">
                <svg class="w-6 h-6 text-amber-600 dark:text-amber-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                </svg>
                <p class="font-medium text-slate-900 dark:text-white">Kehadiran</p>
            </a>
        </div>
    </div>
</div>
@endsection
