@extends('layouts.authenticated-unified')

@section('title', 'Manajemen Absensi')

@section('page-content')
<!-- Page Header -->
<div class="mb-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Manajemen Absensi</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Pantau dan kelola absensi karyawan dengan pelacakan real-time</p>
        </div>
        <div class="flex items-center space-x-3">
            <x-ui.button variant="secondary" onclick="window.location.href='{{ route('attendance.history') }}'"
                class="bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Riwayat Absensi
            </x-ui.button>
            <x-ui.button variant="primary" onclick="window.location.href='{{ route('attendance.check-in') }}'"
                class="bg-blue-600 hover:bg-blue-700 text-white">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Check-in/out
            </x-ui.button>
        </div>
    </div>
</div>

<!-- Statistics Cards Section -->
@can('view_attendance_reports')
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between mb-4">
            <div class="p-3 bg-green-600 rounded-lg shadow-md">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <span class="text-sm text-green-600" id="today-present-status">Terkini</span>
        </div>
        <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100" id="today-present">-</h3>
        <p class="text-gray-600 dark:text-gray-400 text-sm">Hadir Hari Ini</p>
    </x-ui.card>

    <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between mb-4">
            <div class="p-3 bg-amber-600 rounded-lg shadow-md">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <span class="text-sm text-amber-600" id="today-late-status">Terlambat</span>
        </div>
        <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100" id="today-late">-</h3>
        <p class="text-gray-600 dark:text-gray-400 text-sm">Terlambat</p>
    </x-ui.card>

    <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between mb-4">
            <div class="p-3 bg-blue-600 rounded-lg shadow-md">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1.01M15 10h1.01m4.828 4.828A9 9 0 119.172 9.172a9 9 0 0110.656 10.656z"/></svg>
            </div>
            <span class="text-sm text-blue-600" id="today-incomplete-status">Aktif</span>
        </div>
        <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100" id="today-incomplete">-</h3>
        <p class="text-gray-600 dark:text-gray-400 text-sm">Masih Bekerja</p>
    </x-ui.card>

    <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between mb-4">
            <div class="p-3 bg-purple-600 rounded-lg shadow-md">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </div>
            <span class="text-sm text-purple-600" id="total-employees-status">Total</span>
        </div>
        <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100" id="total-employees">-</h3>
        <p class="text-gray-600 dark:text-gray-400 text-sm">Total Karyawan</p>
    </x-ui.card>
</div>
@endcan

<!-- Main Content -->
<div class="grid grid-cols-1 xl:grid-cols-4 gap-8">
    <!-- Today's Attendance Table -->
    <div class="xl:col-span-3">
        <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
                <div class="flex-1">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center mb-2">
                        <div class="p-2 bg-green-600 rounded-lg mr-3">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        </div>
                        Absensi Hari Ini
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400 text-sm ml-11">Pelacakan absensi real-time dan monitoring karyawan</p>
                </div>
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center space-y-3 sm:space-y-0 sm:space-x-3 mt-4 sm:mt-0">
                    <x-ui.button variant="secondary" onclick="refreshTodayAttendance()"
                        class="bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Segarkan Data
                    </x-ui.button>
                    @can('view_attendance_reports')
                        <x-ui.button variant="primary" onclick="exportTodayAttendance()"
                            class="bg-blue-600 hover:bg-blue-700 text-white">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Ekspor Laporan
                        </x-ui.button>
                    @endcan
                </div>
            </div>
            
            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                <table id="today-attendance-table" class="min-w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">
                                <div class="flex items-center space-x-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    <span>Karyawan</span>
                                </div>
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">
                                <div class="flex items-center space-x-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                                    <span>Masuk</span>
                                </div>
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">
                                <div class="flex items-center space-x-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                    <span>Keluar</span>
                                </div>
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">
                                <div class="flex items-center space-x-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <span>Durasi</span>
                                </div>
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">
                                <div class="flex items-center space-x-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <span>Status</span>
                                </div>
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">
                                <div class="flex items-center space-x-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/></svg>
                                    <span>Aksi</span>
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <!-- Content loaded via AJAX -->
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    </div>

    <!-- Sidebar with Quick Actions -->
    <div class="xl:col-span-1 space-y-6">
        <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="mb-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center mb-3">
                    <div class="p-2 bg-emerald-600 rounded-lg mr-3">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </div>
                    Aksi Cepat
                </h3>
                <p class="text-gray-600 dark:text-gray-400 text-sm ml-11">Tugas absensi yang umum dilakukan</p>
            </div>
            
            <div class="space-y-3">
                <a href="{{ route('attendance.check-in') }}" 
                   class="group block rounded-lg border border-gray-200 dark:border-gray-700 p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-200">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center flex-1">
                            <div class="p-2 bg-emerald-100 dark:bg-emerald-900/20 rounded-lg">
                                <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </div>
                            <div class="ml-3">
                                <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Absensi Wajah</h4>
                                <p class="text-xs text-gray-600 dark:text-gray-400">Gunakan kamera untuk verifikasi absensi</p>
                            </div>
                        </div>
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </div>
                </a>

                <a href="{{ route('attendance.history') }}" 
                   class="group block rounded-lg border border-gray-200 dark:border-gray-700 p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-200">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center flex-1">
                            <div class="p-2 bg-blue-100 dark:bg-blue-900/20 rounded-lg">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </div>
                            <div class="ml-3">
                                <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Riwayat Absensi</h4>
                                <p class="text-xs text-gray-600 dark:text-gray-400">Catatan absensi detail dan laporan</p>
                            </div>
                        </div>
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </div>
                </a>

                <button onclick="openManualEntryModal()" 
                        class="group block w-full rounded-lg border border-gray-200 dark:border-gray-700 p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-200 text-left">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center flex-1">
                            <div class="p-2 bg-amber-100 dark:bg-amber-900/20 rounded-lg">
                                <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </div>
                            <div class="ml-3">
                                <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Entry Manual</h4>
                                <p class="text-xs text-gray-600 dark:text-gray-400">Input absensi secara manual</p>
                            </div>
                        </div>
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </div>
                </button>
            </div>
        </x-ui.card>
    
        <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="mb-4">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center mb-3">
                    <div class="p-2 bg-purple-600 rounded-lg mr-3">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                    </div>
                    Status Deteksi Wajah
                </h3>
                <p class="text-gray-600 dark:text-gray-400 text-sm ml-11">Status sistem dan pendaftaran wajah</p>
            </div>
            
            <div id="face-detection-status">
                <div class="animate-pulse">
                    <div class="h-16 bg-gray-200 dark:bg-gray-700 rounded-lg mb-4"></div>
                    <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-3/4 mb-2"></div>
                    <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/2"></div>
                </div>
            </div>
        </x-ui.card>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    loadTodayStatistics();
    loadTodayAttendance();
    loadFaceDetectionStatus();
    
    setInterval(function() {
        loadTodayStatistics();
        refreshTodayAttendance();
    }, 30000);
});

function loadTodayStatistics() {
    const today = new Date().toISOString().split('T')[0];
    
    $.get('/api/vue/dashboard/stats', {
        start_date: today,
        end_date: today
    })
    .done(function(response) {
        if (response.success) {
            const stats = response.statistics;
            $('#today-present').text(stats.present_count);
            $('#today-late').text(stats.late_count);
            $('#today-incomplete').text(stats.incomplete_count);
            $('#total-employees').text(stats.total_records || '-');
        }
    })
    .fail(function(xhr, status, error) {
        console.error('Gagal memuat statistik hari ini:', error);
        // Set default values on error
        $('#today-present').text('0');
        $('#today-late').text('0');
        $('#today-incomplete').text('0');
        $('#total-employees').text('0');
    });
}

function loadTodayAttendance() {
    const today = new Date().toISOString().split('T')[0];
    
    $.get('/api/vue/dashboard/attendance', {
        start_date: today,
        end_date: today,
        length: 50,
        start: 0
    })
    .done(function(response) {
        displayTodayAttendance(response.data);
    })
    .fail(function(xhr, status, error) {
        console.error('Gagal memuat data absensi:', error);
        $('#today-attendance-table tbody').html('<tr><td colspan="6" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">Gagal memuat data absensi</td></tr>');
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
        const hours = record.working_hours_formatted || '0j 0m';
        const statusColor = getStatusColor(record.status);
        const statusText = getStatusText(record.status);
        
        html += `
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-200">
                <td class="px-6 py-4">
                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0">
                            <div class="h-10 w-10 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center ring-2 ring-gray-200 dark:ring-gray-600">
                                <span class="text-sm font-semibold text-gray-600 dark:text-gray-300">${record.employee_name.charAt(0).toUpperCase()}</span>
                            </div>
                        </div>
                        <div>
                            <div class="text-sm font-semibold text-gray-900 dark:text-white">${record.employee_name}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">${record.employee_id}</div>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4">
                    <div class="flex items-center space-x-2">
                        <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14"/></svg>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">${checkIn}</span>
                    </div>
                </td>
                <td class="px-6 py-4">
                    <div class="flex items-center space-x-2">
                        <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7"/></svg>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">${checkOut}</span>
                    </div>
                </td>
                <td class="px-6 py-4">
                    <div class="flex items-center space-x-2">
                        <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">${hours}</span>
                    </div>
                </td>
                <td class="px-6 py-4">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium ${statusColor}">
                        ${statusText}
                    </span>
                </td>
                <td class="px-6 py-4">
                    <div class="flex items-center space-x-2">
                        <button class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 hover:bg-blue-200 dark:hover:bg-blue-900/40 transition-colors duration-200 view-details" data-id="${record.id}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        </button>
                        ${record.status === 'incomplete' && record.actions && record.actions.includes('manual-checkout') ? 
                            `<button class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-amber-100 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400 hover:bg-amber-200 dark:hover:bg-amber-900/40 transition-colors duration-200 manual-checkout" data-id="${record.id}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
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
    window.open(`/api/vue/attendance/export?start_date=${today}&end_date=${today}&format=excel`, '_blank');
    toastr.info('Ekspor laporan absensi sedang diproses...', 'Ekspor');
}

function openManualEntryModal() {
    toastr.info('Fitur entry manual akan segera hadir', 'Segera Hadir');
}

function loadFaceDetectionStatus() {
    $.get('/api/vue/face-detection/statistics')
        .done(function(response) {
            if (response.success) {
                displayFaceDetectionStatus(response.statistics);
            }
        })
        .fail(function(xhr, status, error) {
            console.error('Gagal memuat status deteksi wajah:', error);
            $('#face-detection-status').html(`
                <div class="flex items-center p-3 rounded-lg border border-red-200 dark:border-red-700 bg-red-50 dark:bg-red-900/20">
                    <svg class="w-5 h-5 mr-2 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                    </svg>
                    <div>
                        <h4 class="font-medium text-red-800 dark:text-red-200">Deteksi Wajah Tidak Tersedia</h4>
                        <p class="text-sm text-red-600 dark:text-red-300 mt-1">Tidak dapat terhubung ke layanan deteksi wajah.</p>
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
        <div class="grid grid-cols-2 gap-3 mb-4">
            <div class="text-center p-3 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg">
                <div class="text-xl font-bold text-emerald-900 dark:text-emerald-100">${stats.registered_faces || 0}</div>
                <div class="text-xs text-emerald-600 dark:text-emerald-400">Terdaftar</div>
            </div>
            <div class="text-center p-3 bg-${statusColor}-50 dark:bg-${statusColor}-900/20 rounded-lg">
                <div class="text-xl font-bold text-${statusColor}-900 dark:text-${statusColor}-100">${registrationPercentage}%</div>
                <div class="text-xs text-${statusColor}-600 dark:text-${statusColor}-400">Cakupan</div>
            </div>
        </div>
        
        <div class="mb-4">
            <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400 mb-2">
                <span>Progress Pendaftaran</span>
                <span class="font-medium">${registrationPercentage}%</span>
            </div>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                <div class="bg-${statusColor}-600 rounded-full h-2 transition-all duration-500" style="width: ${registrationPercentage}%"></div>
            </div>
        </div>
        
        ${algorithmsHtml ? `<div class="border-t border-gray-200 dark:border-gray-700 pt-3 mt-3">${algorithmsHtml}</div>` : ''}
        
        <div class="mt-4">
            @can('view_employees')
            <a href="{{ route('employees.index') }}" class="inline-flex items-center justify-center w-full px-3 py-2 text-sm font-medium text-white bg-purple-600 hover:bg-purple-700 rounded-lg transition-colors duration-200">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                Daftarkan Lebih Banyak Wajah
            </a>
            @endcan
        </div>
    `;
    
    $('#face-detection-status').html(html);
}

function getStatusColor(status) {
    const colors = {
        'present': 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400',
        'absent': 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400', 
        'late': 'bg-amber-100 text-amber-800 dark:bg-amber-900/20 dark:text-amber-400',
        'early_departure': 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400',
        'incomplete': 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400'
    };
    return colors[status] || 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400';
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
    // Placeholder for view details functionality
    toastr.info('Detail absensi akan segera hadir', 'Detail');
});

// Handle manual checkout click (global event handler)
$(document).on('click', '.manual-checkout', function() {
    const attendanceId = $(this).data('id');
    // Placeholder for manual checkout functionality
    toastr.info('Fitur checkout manual akan segera hadir', 'Manual Checkout');
});
</script>
@endpush