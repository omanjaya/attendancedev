@extends('layouts.authenticated')

@section('title', 'Dashboard')

@section('page-content')
<div class="min-h-screen bg-slate-50 dark:bg-slate-900">
    <div class="max-w-5xl mx-auto p-6 lg:p-8 space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900 dark:text-white">Selamat Datang</h1>
                <p class="text-slate-500 dark:text-slate-400">{{ auth()->user()->name }}</p>
            </div>
            <span class="text-sm text-slate-500 dark:text-slate-400">{{ now()->format('l, d M Y') }}</span>
        </div>

        <!-- Status Hari Ini -->
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    @if($dashboardData['personal_status']['today_status']['checked_in'] ?? false)
                        <div class="w-12 h-12 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center">
                            <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <div>
                            <p class="font-medium text-slate-900 dark:text-white">Sudah Check-in</p>
                            <p class="text-sm text-slate-500 dark:text-slate-400">
                                {{ \Carbon\Carbon::parse($dashboardData['personal_status']['today_status']['check_in_time'])->format('H:i') }}
                                @if($dashboardData['personal_status']['today_status']['check_out_time'] ?? false)
                                    - {{ \Carbon\Carbon::parse($dashboardData['personal_status']['today_status']['check_out_time'])->format('H:i') }}
                                @endif
                            </p>
                        </div>
                    @else
                        <div class="w-12 h-12 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                            <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="font-medium text-slate-900 dark:text-white">Belum Check-in</p>
                            <p class="text-sm text-slate-500 dark:text-slate-400">Silakan check-in untuk memulai</p>
                        </div>
                    @endif
                </div>
                @if(!($dashboardData['personal_status']['today_status']['checked_in'] ?? false))
                    <a href="{{ route('attendance.check-in') }}" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg transition-colors">
                        Check In
                    </a>
                @elseif(!($dashboardData['personal_status']['today_status']['check_out_time'] ?? false))
                    <a href="{{ route('attendance.check-in') }}" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-lg transition-colors">
                        Check Out
                    </a>
                @endif
            </div>
        </div>

        <!-- Ringkasan -->
        <div class="grid grid-cols-4 gap-2 sm:gap-4">
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-3 sm:p-4">
                <div class="flex items-center gap-2 sm:block">
                    <svg class="w-5 h-5 text-emerald-500 sm:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 hidden sm:block">Kehadiran</p>
                    <p class="text-lg sm:text-2xl font-semibold text-slate-900 dark:text-white sm:mt-1">{{ $dashboardData['personal_status']['monthly_summary']['attendance_rate'] ?? 0 }}%</p>
                </div>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-3 sm:p-4">
                <div class="flex items-center gap-2 sm:block">
                    <svg class="w-5 h-5 text-blue-500 sm:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 hidden sm:block">Hari Hadir</p>
                    <p class="text-lg sm:text-2xl font-semibold text-slate-900 dark:text-white sm:mt-1">{{ $dashboardData['personal_status']['monthly_summary']['attended_days'] ?? 0 }}</p>
                </div>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-3 sm:p-4">
                <div class="flex items-center gap-2 sm:block">
                    <svg class="w-5 h-5 text-amber-500 sm:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 hidden sm:block">Terlambat</p>
                    <p class="text-lg sm:text-2xl font-semibold text-slate-900 dark:text-white sm:mt-1">{{ $dashboardData['personal_status']['monthly_summary']['late_days'] ?? 0 }}</p>
                </div>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-3 sm:p-4">
                <div class="flex items-center gap-2 sm:block">
                    <svg class="w-5 h-5 text-purple-500 sm:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 hidden sm:block">Sisa Cuti</p>
                    <p class="text-lg sm:text-2xl font-semibold text-slate-900 dark:text-white sm:mt-1">{{ $dashboardData['leave_balance']['remaining_days'] ?? 0 }}</p>
                </div>
            </div>
        </div>

        <!-- Jadwal Hari Ini -->
        @if(count($dashboardData['today_schedule']['classes_today'] ?? []) > 0)
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700">
            <div class="p-4 border-b border-slate-200 dark:border-slate-700">
                <h2 class="font-medium text-slate-900 dark:text-white">Jadwal Hari Ini</h2>
            </div>
            <div class="divide-y divide-slate-200 dark:divide-slate-700">
                @foreach($dashboardData['today_schedule']['classes_today'] ?? [] as $class)
                <div class="p-4 flex items-center justify-between">
                    <div>
                        <p class="font-medium text-slate-900 dark:text-white">{{ $class['subject'] ?? '-' }}</p>
                        <p class="text-sm text-slate-500 dark:text-slate-400">{{ $class['class_name'] ?? '-' }} • {{ $class['time'] ?? '-' }}</p>
                    </div>
                    <span class="px-2.5 py-1 text-xs font-medium rounded-full
                        {{ $class['status'] === 'completed' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 
                           ($class['status'] === 'ongoing' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400') }}">
                        {{ $class['status'] === 'completed' ? 'Selesai' : ($class['status'] === 'ongoing' ? 'Berlangsung' : 'Akan Datang') }}
                    </span>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Menu Cepat -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <a href="{{ route('leave.index') }}#request" class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 hover:border-slate-300 dark:hover:border-slate-600 transition-colors">
                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <p class="font-medium text-slate-900 dark:text-white">Ajukan Cuti</p>
            </a>
            <a href="{{ route('schedules.index') }}" class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 hover:border-slate-300 dark:hover:border-slate-600 transition-colors">
                <svg class="w-6 h-6 text-purple-600 dark:text-purple-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="font-medium text-slate-900 dark:text-white">Lihat Jadwal</p>
            </a>
            <a href="{{ route('reports.employee') }}" class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 hover:border-slate-300 dark:hover:border-slate-600 transition-colors">
                <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                <p class="font-medium text-slate-900 dark:text-white">Laporan Saya</p>
            </a>
            <a href="{{ route('profile.edit') }}" class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-4 hover:border-slate-300 dark:hover:border-slate-600 transition-colors">
                <svg class="w-6 h-6 text-slate-600 dark:text-slate-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                <p class="font-medium text-slate-900 dark:text-white">Profil</p>
            </a>
        </div>
    </div>
</div>
@endsection
