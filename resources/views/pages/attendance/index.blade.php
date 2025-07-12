@extends('layouts.authenticated')

@section('title', 'Manajemen Absensi')

@section('page-content')
<x-layouts.page-base 
    title="Manajemen Absensi"
    subtitle="Pantau dan kelola absensi karyawan dengan pelacakan real-time"
    :show-background="true"
    :show-welcome="true"
    welcome-title="Manajemen Absensi"
    welcome-subtitle="Pantau dan kelola absensi karyawan dengan pelacakan real-time">

        <!-- Enhanced Stats Grid -->
        @can('view_attendance_reports')
        <x-layouts.glass-card class="mb-6 p-6">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Hadir Hari Ini -->
                <div class="group relative overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-50 to-emerald-100 dark:from-emerald-900/30 dark:to-emerald-800/20 p-4 transition-all duration-300 hover:scale-105 hover:shadow-lg">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-emerald-600 dark:text-emerald-400">Hadir Hari Ini</p>
                            <p class="text-2xl font-bold text-emerald-900 dark:text-emerald-100" id="today-present">-</p>
                            <div class="flex items-center mt-1">
                                <span class="text-xs text-emerald-600 dark:text-emerald-400">Karyawan hadir</span>
                            </div>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-emerald-500 flex items-center justify-center group-hover:rotate-12 transition-transform duration-300">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Terlambat -->
                <div class="group relative overflow-hidden rounded-2xl bg-gradient-to-br from-amber-50 to-amber-100 dark:from-amber-900/30 dark:to-amber-800/20 p-4 transition-all duration-300 hover:scale-105 hover:shadow-lg">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-amber-600 dark:text-amber-400">Terlambat</p>
                            <p class="text-2xl font-bold text-amber-900 dark:text-amber-100" id="today-late">-</p>
                            <div class="flex items-center mt-1">
                                <span class="text-xs text-amber-600 dark:text-amber-400">Datang terlambat</span>
                            </div>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-amber-500 flex items-center justify-center group-hover:rotate-12 transition-transform duration-300">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Belum Check Out -->
                <div class="group relative overflow-hidden rounded-2xl bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/30 dark:to-blue-800/20 p-4 transition-all duration-300 hover:scale-105 hover:shadow-lg">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-blue-600 dark:text-blue-400">Masih Bekerja</p>
                            <p class="text-2xl font-bold text-blue-900 dark:text-blue-100" id="today-incomplete">-</p>
                            <div class="flex items-center mt-1">
                                <span class="text-xs text-blue-600 dark:text-blue-400">Belum checkout</span>
                            </div>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-blue-500 flex items-center justify-center group-hover:rotate-12 transition-transform duration-300">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1.01M15 10h1.01m4.828 4.828A9 9 0 119.172 9.172a9 9 0 0110.656 10.656z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Total Karyawan -->
                <div class="group relative overflow-hidden rounded-2xl bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/30 dark:to-purple-800/20 p-4 transition-all duration-300 hover:scale-105 hover:shadow-lg">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-purple-600 dark:text-purple-400">Total Karyawan</p>
                            <p class="text-2xl font-bold text-purple-900 dark:text-purple-100" id="total-employees">-</p>
                            <div class="flex items-center mt-1">
                                <span class="text-xs text-purple-600 dark:text-purple-400">Karyawan aktif</span>
                            </div>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-purple-500 flex items-center justify-center group-hover:rotate-12 transition-transform duration-300">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </x-layouts.glass-card>
        @endcan

        <!-- Content Row -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 sm:gap-8">
            <!-- Today's Attendance Table -->
            <div class="lg:col-span-2">
                <x-layouts.glass-card class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 flex items-center">
                                <svg class="w-6 h-6 mr-3 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                                Absensi Hari Ini
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">Pelacakan absensi real-time dan monitoring karyawan</p>
                        </div>
                        <div class="flex items-center space-x-3">
                            <button onclick="refreshTodayAttendance()" 
                                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 rounded-lg transition-all duration-200 hover:scale-105">
                                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                                Refresh
                            </button>
                            <button onclick="exportTodayAttendance()" 
                                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-all duration-200 hover:scale-105">
                                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                Ekspor
                            </button>
                        </div>
                    </div>
                    
                    <!-- Enhanced Table -->
                    <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                        <table id="today-attendance-table" class="w-full">
                            <thead>
                                <tr class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-700">
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider">
                                        <div class="flex items-center space-x-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                            </svg>
                                            <span>Karyawan</span>
                                        </div>
                                    </th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider">
                                        <div class="flex items-center space-x-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                                            </svg>
                                            <span>Masuk</span>
                                        </div>
                                    </th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider">
                                        <div class="flex items-center space-x-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                            </svg>
                                            <span>Keluar</span>
                                        </div>
                                    </th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider">
                                        <div class="flex items-center space-x-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            <span>Durasi</span>
                                        </div>
                                    </th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider">
                                        <div class="flex items-center space-x-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            <span>Status</span>
                                        </div>
                                    </th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider">
                                        <div class="flex items-center space-x-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
                                            </svg>
                                            <span>Aksi</span>
                                        </div>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                                <!-- Content loaded via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </x-layouts.glass-card>
        </div>

            <!-- Sidebar with Quick Actions -->
            <div class="lg:col-span-1 space-y-6">
                <!-- Quick Actions Card -->
                <x-layouts.glass-card class="p-6">
                    <div class="mb-6">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                            Aksi Cepat
                        </h3>
                        <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">Tugas absensi yang umum dilakukan</p>
                    </div>
                
                    <div class="space-y-4">
                        <!-- Face Recognition Check-in -->
                        <a href="{{ route('attendance.check-in') }}" 
                           class="group relative overflow-hidden rounded-xl bg-gradient-to-br from-emerald-50 to-emerald-100 dark:from-emerald-900/30 dark:to-emerald-800/20 p-4 transition-all duration-300 hover:scale-105 hover:shadow-lg border border-emerald-200 dark:border-emerald-700/50">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-12 h-12 rounded-xl bg-emerald-500 flex items-center justify-center group-hover:rotate-12 transition-transform duration-300">
                                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-4 flex-1">
                                    <h4 class="font-semibold text-emerald-900 dark:text-emerald-100 group-hover:text-emerald-700 dark:group-hover:text-emerald-200 transition-colors">Absensi Wajah</h4>
                                    <p class="text-sm text-emerald-600 dark:text-emerald-400">Gunakan kamera untuk verifikasi absensi</p>
                                </div>
                                <svg class="w-5 h-5 text-emerald-500 group-hover:translate-x-1 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </div>
                        </a>

                        <!-- Attendance History -->
                        <a href="{{ route('attendance.history') }}" 
                           class="group relative overflow-hidden rounded-xl bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/30 dark:to-blue-800/20 p-4 transition-all duration-300 hover:scale-105 hover:shadow-lg border border-blue-200 dark:border-blue-700/50">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-12 h-12 rounded-xl bg-blue-500 flex items-center justify-center group-hover:rotate-12 transition-transform duration-300">
                                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-4 flex-1">
                                    <h4 class="font-semibold text-blue-900 dark:text-blue-100 group-hover:text-blue-700 dark:group-hover:text-blue-200 transition-colors">Riwayat Absensi</h4>
                                    <p class="text-sm text-blue-600 dark:text-blue-400">Catatan absensi detail dan laporan</p>
                                </div>
                                <svg class="w-5 h-5 text-blue-500 group-hover:translate-x-1 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </div>
                        </a>

                        <!-- Manual Entry -->
                        <button onclick="openManualEntryModal()" 
                                class="group relative overflow-hidden rounded-xl bg-gradient-to-br from-amber-50 to-amber-100 dark:from-amber-900/30 dark:to-amber-800/20 p-4 transition-all duration-300 hover:scale-105 hover:shadow-lg border border-amber-200 dark:border-amber-700/50 w-full">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-12 h-12 rounded-xl bg-amber-500 flex items-center justify-center group-hover:rotate-12 transition-transform duration-300">
                                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-4 flex-1 text-left">
                                    <h4 class="font-semibold text-amber-900 dark:text-amber-100 group-hover:text-amber-700 dark:group-hover:text-amber-200 transition-colors">Entry Manual</h4>
                                    <p class="text-sm text-amber-600 dark:text-amber-400">Input absensi secara manual</p>
                                </div>
                                <svg class="w-5 h-5 text-amber-500 group-hover:translate-x-1 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </div>
                        </button>
                    </div>
                </x-layouts.glass-card>
            
                <!-- Face Detection Status Card -->
                <x-layouts.glass-card class="p-6">
                    <div class="mb-6">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                            </svg>
                            Status Deteksi Wajah
                        </h3>
                        <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">Status sistem dan pendaftaran wajah</p>
                    </div>
                    
                    <div id="face-detection-status">
                        <!-- Loading skeleton -->
                        <div class="animate-pulse">
                            <div class="h-16 bg-gray-200 dark:bg-gray-700 rounded-lg mb-4"></div>
                            <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-3/4 mb-2"></div>
                            <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/2"></div>
                        </div>
                    </div>
                </x-layouts.glass-card>
            </div>
        </div>
    </div>

</x-layouts.page-base>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Load dashboard data
    loadTodayStatistics();
    loadTodayAttendance();
    loadFaceDetectionStatus();
    
    // Auto-refresh every 30 seconds
    setInterval(function() {
        loadTodayStatistics();
        refreshTodayAttendance();
    }, 30000);
});

function loadTodayStatistics() {
    const today = new Date().toISOString().split('T')[0];
    
    $.get('/api/v1/attendance/statistics', {
        start_date: today,
        end_date: today
    })
    .done(function(response) {
        if (response.success) {
            const stats = response.statistics;
            $('#today-present').text(stats.present_count);
            $('#today-late').text(stats.late_count);
            $('#today-incomplete').text(stats.incomplete_count);
            
            // Calculate total employees (would need separate endpoint)
            $('#total-employees').text(stats.total_records || '-');
        }
    })
    .fail(function() {
        console.error('Failed to load today\'s statistics');
    });
}

function loadTodayAttendance() {
    const today = new Date().toISOString().split('T')[0];
    
    $.get('/api/v1/attendance/data', {
        start_date: today,
        end_date: today,
        length: 50, // Show more records
        start: 0
    })
    .done(function(response) {
        displayTodayAttendance(response.data);
    })
    .fail(function() {
        $('#today-attendance-table tbody').html('<tr><td colspan="6" class="px-6 py-8 text-center text-muted-foreground">Failed to load attendance data</td></tr>');
    });
}

function displayTodayAttendance(attendanceData) {
    const tbody = $('#today-attendance-table tbody');
    
    if (!attendanceData || attendanceData.length === 0) {
        tbody.html('<tr><td colspan="6" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">Tidak ada catatan absensi hari ini</td></tr>');
        return;
    }
    
    let html = '';
    attendanceData.forEach(function(record) {
        const checkIn = record.check_in_formatted || '-';
        const checkOut = record.check_out_formatted || '-';
        const hours = record.working_hours_formatted || '0h 0m';
        const statusColor = getStatusColor(record.status);
        const statusText = getStatusText(record.status);
        
        html += `
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-200">
                <td class="px-6 py-4">
                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0">
                            <div class="h-10 w-10 rounded-full bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-600 dark:to-gray-700 flex items-center justify-center ring-2 ring-white dark:ring-gray-800">
                                <span class="text-sm font-semibold text-gray-600 dark:text-gray-300">${record.employee_name.charAt(0).toUpperCase()}</span>
                            </div>
                        </div>
                        <div>
                            <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">${record.employee_name}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">${record.employee_id}</div>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4">
                    <div class="flex items-center space-x-2">
                        <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14"/>
                        </svg>
                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100">${checkIn}</span>
                    </div>
                </td>
                <td class="px-6 py-4">
                    <div class="flex items-center space-x-2">
                        <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7"/>
                        </svg>
                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100">${checkOut}</span>
                    </div>
                </td>
                <td class="px-6 py-4">
                    <div class="flex items-center space-x-2">
                        <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100">${hours}</span>
                    </div>
                </td>
                <td class="px-6 py-4">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-${statusColor}-100 text-${statusColor}-800 dark:bg-${statusColor}-800/20 dark:text-${statusColor}-300">
                        ${statusText}
                    </span>
                </td>
                <td class="px-6 py-4">
                    <div class="flex items-center space-x-2">
                        <button class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 rounded-lg transition-all duration-200 hover:scale-105 view-details" data-id="${record.id}">
                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            Lihat
                        </button>
                        ${record.status === 'incomplete' && record.actions && record.actions.includes('manual-checkout') ? 
                            `<button class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-amber-600 hover:bg-amber-700 rounded-lg transition-all duration-200 hover:scale-105 manual-checkout" data-id="${record.id}">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Selesai
                            </button>` : ''
                        }
                    </div>
                </td>
            </tr>
        `;
    });
    
    tbody.html(html);
}

function refreshTodayAttendance() {
    loadTodayAttendance();
    toastr.success('Data absensi telah diperbarui', 'Berhasil!');
}

function exportTodayAttendance() {
    const today = new Date().toISOString().split('T')[0];
    window.open(`/api/v1/attendance/export?start_date=${today}&end_date=${today}&format=excel`, '_blank');
    toastr.info('Ekspor laporan absensi sedang diproses...', 'Export');
}

function openManualEntryModal() {
    // This would open a modal for manual attendance entry
    toastr.info('Fitur entry manual akan segera hadir', 'Coming Soon');
}

function loadFaceDetectionStatus() {
    $.get('/api/v1/face-detection/statistics')
        .done(function(response) {
            if (response.success) {
                displayFaceDetectionStatus(response.statistics);
            }
        })
        .fail(function() {
            $('#face-detection-status').html(`
                <div class="bg-gradient-to-r from-red-50 to-red-100 dark:from-red-900/30 dark:to-red-800/20 border border-red-200 dark:border-red-700/50 text-red-800 dark:text-red-200 px-4 py-3 rounded-xl" role="alert">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                        </svg>
                        <div>
                            <h4 class="font-semibold">Deteksi Wajah Tidak Tersedia</h4>
                            <p class="text-sm mt-1">Tidak dapat terhubung ke layanan deteksi wajah.</p>
                        </div>
                    </div>
                </div>
            `);
        });
}

function displayFaceDetectionStatus(stats) {
    const registrationPercentage = stats.registration_percentage || 0;
    const algorithms = stats.algorithms_used || {};
    
    let algorithmsHtml = '';
    Object.keys(algorithms).forEach(function(algorithm) {
        algorithmsHtml += `<div class="flex justify-between text-sm text-gray-600 dark:text-gray-400 mb-1">
            <span>${algorithm}:</span>
            <span class="font-medium">${algorithms[algorithm]} karyawan</span>
        </div>`;
    });
    
    const statusColor = registrationPercentage >= 80 ? 'emerald' : registrationPercentage >= 50 ? 'amber' : 'red';
    
    const html = `
        <div class="grid grid-cols-2 gap-4 mb-4">
            <div class="text-center p-3 bg-gradient-to-br from-emerald-50 to-emerald-100 dark:from-emerald-900/30 dark:to-emerald-800/20 rounded-xl">
                <div class="text-2xl font-bold text-emerald-900 dark:text-emerald-100">${stats.registered_faces || 0}</div>
                <div class="text-xs text-emerald-600 dark:text-emerald-400">Terdaftar</div>
            </div>
            <div class="text-center p-3 bg-gradient-to-br from-${statusColor}-50 to-${statusColor}-100 dark:from-${statusColor}-900/30 dark:to-${statusColor}-800/20 rounded-xl">
                <div class="text-2xl font-bold text-${statusColor}-900 dark:text-${statusColor}-100">${registrationPercentage}%</div>
                <div class="text-xs text-${statusColor}-600 dark:text-${statusColor}-400">Cakupan</div>
            </div>
        </div>
        
        <div class="mb-4">
            <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400 mb-2">
                <span>Progress Pendaftaran</span>
                <span class="font-medium">${registrationPercentage}%</span>
            </div>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                <div class="bg-gradient-to-r from-${statusColor}-400 to-${statusColor}-600 rounded-full h-3 transition-all duration-500 shadow-sm" style="width: ${registrationPercentage}%"></div>
            </div>
        </div>
        
        ${algorithmsHtml ? `<div class="border-t border-gray-200 dark:border-gray-600 pt-3 mt-3">${algorithmsHtml}</div>` : ''}
        
        <div class="mt-4">
            @can('view_employees')
            <a href="{{ route('employees.index') }}" class="inline-flex items-center justify-center w-full px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-purple-500 to-purple-600 hover:from-purple-600 hover:to-purple-700 rounded-lg transition-all duration-200 hover:scale-105 shadow-sm">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Daftarkan Lebih Banyak Wajah
            </a>
            @endcan
        </div>
    `;
    
    $('#face-detection-status').html(html);
}

function getStatusColor(status) {
    const colors = {
        'present': 'emerald',
        'absent': 'red', 
        'late': 'amber',
        'early_departure': 'blue',
        'incomplete': 'gray'
    };
    return colors[status] || 'gray';
}

function getStatusText(status) {
    const texts = {
        'present': 'Hadir',
        'absent': 'Tidak Hadir',
        'late': 'Terlambat', 
        'early_departure': 'Pulang Awal',
        'incomplete': 'Belum Selesai'
    };
    return texts[status] || status;
}

// Handle view details click (global event handler)
$(document).on('click', '.view-details', function() {
    const attendanceId = $(this).data('id');
    // This would open a modal or redirect to details page
    console.log('View details for attendance:', attendanceId);
});

// Handle manual checkout click (global event handler)
$(document).on('click', '.manual-checkout', function() {
    const attendanceId = $(this).data('id');
    // This would open a manual checkout modal
    console.log('Manual checkout for attendance:', attendanceId);
});
</script>
@endsection