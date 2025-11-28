@extends('layouts.authenticated')

@section('title', 'Dashboard Admin')

@section('page-content')
<div class="min-h-screen bg-slate-50 dark:bg-slate-900">
    <div class="max-w-6xl mx-auto p-6 lg:p-8 space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900 dark:text-white">Dashboard Admin</h1>
                <p class="text-slate-500 dark:text-slate-400">{{ now()->format('l, d M Y') }}</p>
            </div>
        </div>

        <!-- Statistik Utama -->
        @php
            $teachers = $dashboardData['teacher_status']['teachers'] ?? [];
            $presentTeachers = array_filter($teachers, fn($teacher) => $teacher['status'] !== 'absent');
            $totalTeachers = count($teachers);
            $presentCount = count($presentTeachers);
            $pendingLeaves = is_array($dashboardData['leave_processing']) ? count($dashboardData['leave_processing']) : ($dashboardData['leave_processing']->count() ?? 0);
        @endphp
        <div class="grid grid-cols-4 gap-2 sm:gap-4">
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-3 sm:p-4">
                <div class="flex items-center gap-2 sm:block">
                    <svg class="w-5 h-5 text-emerald-500 sm:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 hidden sm:block">Guru Hadir</p>
                    <p class="text-lg sm:text-2xl font-semibold text-slate-900 dark:text-white sm:mt-1">{{ $presentCount }}<span class="text-xs sm:text-sm font-normal text-slate-400">/{{ $totalTeachers }}</span></p>
                </div>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-3 sm:p-4">
                <div class="flex items-center gap-2 sm:block">
                    <svg class="w-5 h-5 text-blue-500 sm:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 hidden sm:block">Cakupan</p>
                    <p class="text-lg sm:text-2xl font-semibold text-slate-900 dark:text-white sm:mt-1">{{ $dashboardData['teacher_status']['teaching_coverage'] ?? 0 }}%</p>
                </div>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-3 sm:p-4">
                <div class="flex items-center gap-2 sm:block">
                    <svg class="w-5 h-5 text-amber-500 sm:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 hidden sm:block">Cuti</p>
                    <p class="text-lg sm:text-2xl font-semibold text-amber-600 dark:text-amber-400 sm:mt-1">{{ $pendingLeaves }}</p>
                </div>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-3 sm:p-4">
                <div class="flex items-center gap-2 sm:block">
                    <svg class="w-5 h-5 {{ count($dashboardData['schedule_status']['conflicts'] ?? []) > 0 ? 'text-red-500' : 'text-slate-400' }} sm:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 hidden sm:block">Konflik</p>
                    <p class="text-lg sm:text-2xl font-semibold {{ count($dashboardData['schedule_status']['conflicts'] ?? []) > 0 ? 'text-red-600 dark:text-red-400' : 'text-slate-900 dark:text-white' }} sm:mt-1">{{ count($dashboardData['schedule_status']['conflicts'] ?? []) }}</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Status Guru -->
            <div class="lg:col-span-2 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700">
                <div class="p-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                    <h2 class="font-medium text-slate-900 dark:text-white">Status Guru Hari Ini</h2>
                    <a href="{{ route('attendance.index') }}" class="text-sm text-emerald-600 dark:text-emerald-400 hover:underline">Lihat Semua</a>
                </div>
                <div class="divide-y divide-slate-200 dark:divide-slate-700 max-h-96 overflow-y-auto">
                    @forelse($dashboardData['teacher_status']['teachers'] ?? [] as $teacher)
                    <div class="p-4 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center">
                                <span class="text-sm font-medium text-slate-600 dark:text-slate-300">{{ substr($teacher['name'], 0, 2) }}</span>
                            </div>
                            <div>
                                <p class="font-medium text-slate-900 dark:text-white">{{ $teacher['name'] }}</p>
                                <p class="text-sm text-slate-500 dark:text-slate-400">{{ $teacher['employee_id'] }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full {{ $teacher['status'] === 'present' ? 'bg-emerald-500' : ($teacher['status'] === 'late' ? 'bg-amber-500' : 'bg-red-500') }}"></span>
                            <span class="text-sm {{ $teacher['status'] === 'present' ? 'text-emerald-600 dark:text-emerald-400' : ($teacher['status'] === 'late' ? 'text-amber-600 dark:text-amber-400' : 'text-red-600 dark:text-red-400') }}">
                                {{ $teacher['status'] === 'present' ? 'Hadir' : ($teacher['status'] === 'late' ? 'Terlambat' : 'Absen') }}
                            </span>
                            @if($teacher['check_in_time'])
                            <span class="text-xs text-slate-400">{{ \Carbon\Carbon::parse($teacher['check_in_time'])->format('H:i') }}</span>
                            @endif
                        </div>
                    </div>
                    @empty
                    <div class="p-8 text-center text-slate-500 dark:text-slate-400">
                        Tidak ada data guru
                    </div>
                    @endforelse
                </div>
            </div>

            <!-- Cuti Tertunda -->
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700">
                <div class="p-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                    <h2 class="font-medium text-slate-900 dark:text-white">Permintaan Cuti</h2>
                    <a href="{{ route('leave.index') }}" class="text-sm text-emerald-600 dark:text-emerald-400 hover:underline">Lihat Semua</a>
                </div>
                <div class="divide-y divide-slate-200 dark:divide-slate-700 max-h-96 overflow-y-auto">
                    @forelse($dashboardData['leave_processing'] ?? [] as $leave)
                    <div class="p-4">
                        <div class="flex items-start justify-between mb-2">
                            <div>
                                <p class="font-medium text-slate-900 dark:text-white">{{ $leave->employee->first_name ?? 'Unknown' }} {{ $leave->employee->last_name ?? '' }}</p>
                                <p class="text-sm text-slate-500 dark:text-slate-400">{{ $leave->leaveType->name ?? 'Cuti' }}</p>
                            </div>
                            <span class="text-xs text-slate-500 dark:text-slate-400">{{ $leave->days_requested }} hari</span>
                        </div>
                        <p class="text-xs text-slate-400 mb-3">{{ \Carbon\Carbon::parse($leave->start_date)->format('d M') }} - {{ \Carbon\Carbon::parse($leave->end_date)->format('d M Y') }}</p>
                        <div class="flex gap-2">
                            <button class="flex-1 px-3 py-1.5 text-xs font-medium bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg transition-colors">Setujui</button>
                            <button class="flex-1 px-3 py-1.5 text-xs font-medium bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-300 rounded-lg transition-colors">Tolak</button>
                        </div>
                    </div>
                    @empty
                    <div class="p-8 text-center text-slate-500 dark:text-slate-400">
                        Tidak ada permintaan cuti
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Menu Cepat -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <a href="{{ route('attendance.index') }}" class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 hover:border-slate-300 dark:hover:border-slate-600 transition-colors">
                <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                </svg>
                <p class="font-medium text-slate-900 dark:text-white">Kehadiran</p>
            </a>
            <a href="{{ route('leave.index') }}" class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 hover:border-slate-300 dark:hover:border-slate-600 transition-colors">
                <svg class="w-6 h-6 text-amber-600 dark:text-amber-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <p class="font-medium text-slate-900 dark:text-white">Kelola Cuti</p>
            </a>
            <a href="{{ route('employees.index') }}" class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 hover:border-slate-300 dark:hover:border-slate-600 transition-colors">
                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <p class="font-medium text-slate-900 dark:text-white">Pegawai</p>
            </a>
            <a href="{{ route('reports.attendance') }}" class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 hover:border-slate-300 dark:hover:border-slate-600 transition-colors">
                <svg class="w-6 h-6 text-purple-600 dark:text-purple-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                <p class="font-medium text-slate-900 dark:text-white">Laporan</p>
            </a>
        </div>
    </div>
</div>
@endsection
