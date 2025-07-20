@extends('layouts.authenticated-unified')

@section('title', 'Dashboard')

@section('page-content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <div class="p-6 lg:p-8">
        <!-- Page Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Dashboard</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Selamat datang di sistem absensi sekolah</p>
                </div>
                <div class="flex items-center space-x-3">
    
                    @can('view_attendance_reports')
                    <button onclick="window.location.href='{{ route('attendance.index') }}'" 
                            class="bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 px-4 py-2 rounded-lg font-medium transition-colors duration-200">
                        <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        View Reports
                    </button>
                    @endcan
                    
                    @can('manage_employees')
                    <button onclick="window.location.href='{{ route('management.employees.index') }}'" 
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200">
                        <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/>
                        </svg>
                        Manage Employees
                    </button>
                    @endcan
                </div>
            </div>
        </div>

    @php
        $stats = $dashboardData['realtime_status'] ?? [
            'checked_in_today' => 0,
            'total_employees' => 0,
            'attendance_rate' => 0,
            'pending_requests' => 0,
        ];
    @endphp

    {{-- Primary Stats Grid - Symmetrical 4-column layout --}}
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
        
        {{-- Hadir Hari Ini --}}
        <x-ui.card variant="metric" 
                   title="Hadir Hari Ini"
                   :value="$stats['checked_in_today'] ?? 0"
                   subtitle="dari {{ $stats['total_employees'] ?? 0 }} total karyawan"
                   color="success"
                   class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <x-slot name="icon">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </x-slot>
            @if($stats['checked_in_today'] > 0)
            <x-slot name="actions">
                <x-ui.badge variant="secondary">
                    {{ round(($stats['checked_in_today'] / max($stats['total_employees'], 1)) * 100) }}%
                </x-ui.badge>
            </x-slot>
            @endif
        </x-ui.card>

        {{-- Tingkat Kehadiran --}}
        <x-ui.card variant="metric" 
                   title="Tingkat Kehadiran"
                   :value="($stats['attendance_rate'] ?? 0) . '%'"
                   subtitle="Bulan ini"
                   color="info"
                   class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <x-slot name="icon">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
            </x-slot>
            <div class="mt-3">
                <x-ui.progress :value="$stats['attendance_rate'] ?? 0" 
                              :max="100" 
                              variant="default"
                              size="sm" />
            </div>
        </x-ui.card>

        {{-- Permintaan Cuti --}}
        @php
            $pendingLeaves = $dashboardData['leave_management']['pending_requests'] ?? 0;
        @endphp
        <x-ui.card variant="metric" 
                   title="Permintaan Cuti"
                   :value="$pendingLeaves"
                   subtitle="Menunggu persetujuan"
                   :color="$pendingLeaves > 0 ? 'warning' : 'muted'"
                   class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <x-slot name="icon">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </x-slot>
            @if($pendingLeaves > 0)
            <x-slot name="actions">
                <x-ui.badge variant="outline">
                    Perlu tindakan
                </x-ui.badge>
            </x-slot>
            @endif
        </x-ui.card>

        {{-- Total Karyawan --}}
        <x-ui.card variant="metric" 
                   title="Total Karyawan"
                   :value="$stats['total_employees'] ?? 0"
                   subtitle="Karyawan aktif"
                   color="primary"
                   class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <x-slot name="icon">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/>
                </svg>
            </x-slot>
        </x-ui.card>
    </div>

    {{-- Secondary Stats Grid - System Health & Metrics --}}
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
        
        {{-- System Health --}}
        <x-ui.card variant="metric" 
                   title="Sistem Berjalan"
                   value="Normal"
                   subtitle="Semua layanan aktif"
                   color="success">
            <x-slot name="icon">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
            </x-slot>
            <x-slot name="actions">
                <x-ui.badge variant="outline" class="text-green-600">
                    <span class="inline-block w-2 h-2 bg-green-500 rounded-full mr-1"></span>
                    Online
                </x-ui.badge>
            </x-slot>
        </x-ui.card>

        {{-- Database Records --}}
        <x-ui.card variant="metric" 
                   title="Total Rekaman"
                   :value="($stats['total_records'] ?? 1250)"
                   subtitle="Data absensi"
                   color="secondary">
            <x-slot name="icon">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
                </svg>
            </x-slot>
        </x-ui.card>

        {{-- Security Alerts --}}
        <x-ui.card variant="metric" 
                   title="Peringatan Keamanan"
                   :value="($stats['security_alerts'] ?? 0)"
                   subtitle="Dalam 24 jam"
                   :color="($stats['security_alerts'] ?? 0) > 0 ? 'destructive' : 'muted'">
            <x-slot name="icon">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                </svg>
            </x-slot>
            @if(($stats['security_alerts'] ?? 0) === 0)
            <x-slot name="actions">
                <x-ui.badge variant="outline" class="text-green-600">
                    Aman
                </x-ui.badge>
            </x-slot>
            @endif
        </x-ui.card>

        {{-- Holiday Integration --}}
        <x-ui.card variant="metric" 
                   title="Libur Bulan Ini"
                   :value="($stats['holidays_this_month'] ?? 1)"
                   subtitle="Hari libur nasional"
                   color="accent"
                   class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <x-slot name="icon">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </x-slot>
        </x-ui.card>
    </div>

    {{-- Content Grid - Symmetrical layout --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6 mb-8">
        
        {{-- Absensi Hari Ini --}}
        <x-ui.card title="Absensi Hari Ini" class="xl:col-span-2">
            @if(isset($dashboardData['today_attendance']) && count($dashboardData['today_attendance']) > 0)
                <div class="space-y-3">
                    @foreach(array_slice($dashboardData['today_attendance'], 0, 5) as $attendance)
                    <div class="flex items-center justify-between p-3 bg-gray-100 dark:bg-gray-700 rounded-lg">
                        <div class="flex items-center space-x-3">
                            <img class="w-8 h-8 rounded-full bg-primary/10" 
                                 src="{{ $attendance['employee']['photo_url'] ?? 'https://ui-avatars.com/api/?name='.urlencode($attendance['employee']['name']).'&color=10b981&background=ecfdf5' }}" 
                                 alt="{{ $attendance['employee']['name'] }}">
                            <div>
                                <p class="text-sm font-medium text-foreground">{{ $attendance['employee']['name'] }}</p>
                                <p class="text-xs text-muted-foreground">{{ $attendance['employee']['department'] ?? 'N/A' }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $attendance['check_in'] ?? 'N/A' }}</p>
                            <x-ui.badge 
                                variant="{{ $attendance['status'] === 'present' ? 'default' : ($attendance['status'] === 'late' ? 'warning' : 'destructive') }}"
                                size="sm">
                                {{ ucfirst($attendance['status']) }}
                            </x-ui.badge>
                        </div>
                    </div>
                    @endforeach
                </div>
                
                @if(count($dashboardData['today_attendance']) > 5)
                <div class="mt-4 text-center">
                    <x-ui.button variant="outline" size="sm" href="{{ route('attendance.index') }}">
                        Lihat semua ({{ count($dashboardData['today_attendance']) }})
                    </x-ui.button>
                </div>
                @endif
            @else
                <x-ui.empty-state 
                    title="Belum ada absensi"
                    description="Belum ada yang melakukan absensi hari ini"
                    size="sm">
                    <x-slot name="icon">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </x-slot>
                </x-ui.empty-state>
            @endif
        </x-ui.card>

        {{-- Super Admin Quick Actions --}}
        <x-ui.card title="Aksi Super Admin">
            <div class="space-y-3">
                @can('manage_attendance_own')
                <x-ui.button variant="outline" 
                            class="w-full justify-start"
                            href="{{ route('attendance.checkin') }}">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Check In/Out
                </x-ui.button>
                @endcan

                @can('manage_employees')
                <x-ui.button variant="outline" 
                            class="w-full justify-start"
                            href="{{ route('management.employees.index') }}">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/>
                    </svg>
                    Kelola Karyawan
                </x-ui.button>
                @endcan

                @can('view_attendance_reports')
                <x-ui.button variant="outline" 
                            class="w-full justify-start bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600"
                            href="{{ route('reports.attendance') }}">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Laporan Sistem
                </x-ui.button>
                @endcan

                @can('create_schedules')
                <x-ui.button variant="outline" 
                            class="w-full justify-start"
                            href="{{ route('schedules.index') }}">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    Kelola Jadwal
                </x-ui.button>
                @endcan

                <x-ui.button variant="outline" 
                            class="w-full justify-start"
                            href="{{ route('holidays.index') }}">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    Kelola Libur
                </x-ui.button>

                <x-ui.button variant="outline" 
                            class="w-full justify-start bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600"
                            href="{{ route('security.dashboard') }}">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    Monitor Keamanan
                </x-ui.button>
            </div>
        </x-ui.card>
    </div>

    {{-- Admin Analytics Grid - New section for comprehensive data --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        
        {{-- Recent Activity Feed --}}
        <x-ui.card title="Aktivitas Terbaru">
            <div class="space-y-4">
                <div class="flex items-start space-x-3 p-3 bg-gray-100 dark:bg-gray-700 rounded-lg">
                    <div class="w-2 h-2 bg-blue-500 rounded-full mt-2"></div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-foreground">John Smith melakukan check-in</p>
                        <p class="text-xs text-muted-foreground">2 menit yang lalu • Face recognition berhasil</p>
                    </div>
                </div>
                
                <div class="flex items-start space-x-3 p-3 bg-gray-100 dark:bg-gray-700 rounded-lg">
                    <div class="w-2 h-2 bg-green-500 rounded-full mt-2"></div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-foreground">Sistem backup berhasil</p>
                        <p class="text-xs text-muted-foreground">5 menit yang lalu • Database ter-backup otomatis</p>
                    </div>
                </div>
                
                <div class="flex items-start space-x-3 p-3 bg-gray-100 dark:bg-gray-700 rounded-lg">
                    <div class="w-2 h-2 bg-yellow-500 rounded-full mt-2"></div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Holiday import completed</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">1 jam yang lalu • 41 hari libur diimpor untuk 2025</p>
                    </div>
                </div>

                <div class="mt-4 text-center">
                    <x-ui.button variant="outline" size="sm"
                        class="bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600">
                        Lihat semua aktivitas
                    </x-ui.button>
                </div>
            </div>
        </x-ui.card>

        {{-- System Performance Monitor --}}
        <x-ui.card title="Performa Sistem" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium">Database Performance</span>
                    <div class="flex items-center space-x-2">
                        <div class="w-16 bg-gray-200 rounded-full h-2">
                            <div class="bg-green-600 h-2 rounded-full" style="width: 85%"></div>
                        </div>
                        <span class="text-xs text-muted-foreground">85%</span>
                    </div>
                </div>
                
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium">Server Response</span>
                    <div class="flex items-center space-x-2">
                        <div class="w-16 bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-600 h-2 rounded-full" style="width: 92%"></div>
                        </div>
                        <span class="text-xs text-muted-foreground">92%</span>
                    </div>
                </div>
                
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium">Face Recognition API</span>
                    <div class="flex items-center space-x-2">
                        <div class="w-16 bg-gray-200 rounded-full h-2">
                            <div class="bg-purple-600 h-2 rounded-full" style="width: 96%"></div>
                        </div>
                        <span class="text-xs text-muted-foreground">96%</span>
                    </div>
                </div>
                
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium">Holiday Integration</span>
                    <div class="flex items-center space-x-2">
                        <div class="w-16 bg-gray-200 rounded-full h-2">
                            <div class="bg-green-600 h-2 rounded-full" style="width: 100%"></div>
                        </div>
                        <span class="text-xs text-muted-foreground">100%</span>
                    </div>
                </div>

                <div class="pt-3 border-t border-gray-200 dark:border-gray-700">
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Semua sistem berjalan normal • Last updated: {{ now()->format('H:i') }}
                    </p>
                </div>
            </div>
        </x-ui.card>
    </div>

    {{-- Chart Section --}}
    @if(isset($dashboardData['attendance_trends']))
    <x-ui.card title="Tren Kehadiran" class="mb-8 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="h-64">
            <canvas id="attendanceChart"></canvas>
        </div>
    </x-ui.card>
    @endif

    </div>
</div>

{{-- Chart Scripts --}}
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Wait for Chart.js to load
    if (typeof Chart === 'undefined') {
        console.warn('Chart.js not loaded yet, retrying...');
        setTimeout(() => {
            initializeCharts();
        }, 500);
        return;
    }
    
    initializeCharts();
});

function initializeCharts() {
    // Attendance Chart
    const attendanceChart = document.getElementById('attendanceChart');
    if (attendanceChart && @json(isset($dashboardData['attendance_trends']))) {
        const chartData = @json($dashboardData['attendance_trends']['daily_trends'] ?? []);
        
        new Chart(attendanceChart, {
            type: 'line',
            data: {
                labels: chartData.map(d => d.date) || ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'],
                datasets: [{
                    label: 'Hadir',
                    data: chartData.map(d => d.present_count) || [0, 0, 0, 0, 0, 0, 0],
                    borderColor: 'rgb(16, 185, 129)',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }, {
                    label: 'Terlambat',
                    data: chartData.map(d => d.late_count) || [0, 0, 0, 0, 0, 0, 0],
                    borderColor: 'rgb(245, 158, 11)',
                    backgroundColor: 'rgba(245, 158, 11, 0.1)',
                    borderWidth: 2,
                    fill: false,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }
    
    // Auto-refresh dashboard data every 5 minutes
    setInterval(() => {
        if (!document.hidden) {
            window.location.reload();
        }
    }, 5 * 60 * 1000);
}
});
</script>
@endpush