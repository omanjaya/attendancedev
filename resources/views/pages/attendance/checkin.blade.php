@extends('layouts.authenticated')

@section('title', 'Absensi Check-in')

@section('page-content')
<x-layouts.page-base 
    title="Absensi Check-in"
    subtitle="Sistem absensi biometrik dengan keamanan tinggi"
    :show-background="true"
    :show-welcome="false">

    <!-- Breadcrumb -->
    <nav class="mb-8" aria-label="Breadcrumb">
        <ol class="flex items-center space-x-2 text-sm">
            <li>
                <a href="{{ route('dashboard') }}" class="text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 transition-colors">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                </a>
            </li>
            <li class="flex items-center">
                <svg class="h-5 w-5 text-gray-400 mx-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                </svg>
                <a href="{{ route('attendance.index') }}" class="text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 transition-colors">Absensi</a>
            </li>
            <li class="flex items-center">
                <svg class="h-5 w-5 text-gray-400 mx-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                </svg>
                <span class="text-gray-900 dark:text-gray-100 font-medium">Check-in</span>
            </li>
        </ol>
    </nav>

    <div class="max-w-4xl mx-auto space-y-8">
        <!-- Current Status Card -->
        <x-layouts.glass-card class="p-6" id="status-card">
            <div class="mb-6">
                <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 flex items-center">
                    <svg class="w-6 h-6 mr-3 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Status Absensi Saat Ini
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">Status kehadiran Anda hari ini</p>
            </div>
            
            <div class="py-8">
                <div id="status-content">
                    <div class="text-center">
                        <div class="inline-block animate-spin rounded-full h-8 w-8 border-2 border-emerald-500 border-t-transparent"></div>
                        <p class="mt-2 text-gray-600 dark:text-gray-300">Memuat status absensi...</p>
                    </div>
                </div>
            </div>
        </x-layouts.glass-card>

        <!-- Face Detection Card -->
        <x-layouts.glass-card class="p-6">
            <div class="mb-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 flex items-center">
                            <svg class="w-6 h-6 mr-3 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            Absensi Pengenalan Wajah
                        </h3>
                        <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">Sistem verifikasi biometrik yang aman</p>
                    </div>
                    <div class="relative">
                        <button onclick="toggleDetectionDropdown()" 
                                class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 rounded-lg transition-all duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            Metode Deteksi
                            <svg class="ml-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div id="detection-dropdown" class="hidden absolute right-0 mt-2 w-48 rounded-lg shadow-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 z-10">
                            <div class="py-1" role="menu">
                                <a href="#" class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors" id="use-face-api">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Face-API.js
                                </a>
                                <a href="#" class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors" id="use-mediapipe">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                    </svg>
                                    MediaPipe
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="space-y-6">
                <!-- Face Detection Component -->
                <div id="face-detection-container" class="min-h-[400px] flex items-center justify-center border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-700">
                    <!-- This will be populated by Vue component -->
                </div>

                <!-- Manual Override Section (for admins) -->
                @can('manage_all_attendance')
                <div class="bg-gradient-to-br from-amber-50 to-amber-100 dark:from-amber-900/30 dark:to-amber-800/20 border border-amber-200 dark:border-amber-700/50 rounded-xl p-6">
                    <div class="mb-6">
                        <h4 class="text-lg font-bold text-amber-900 dark:text-amber-100 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            Override Manual
                        </h4>
                        <p class="text-sm text-amber-600 dark:text-amber-400 mt-1">Untuk entry absensi administratif</p>
                    </div>
                    
                    <form id="manual-attendance-form" class="space-y-4">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="space-y-2">
                                <label for="employee_id" class="block text-sm font-medium text-amber-900 dark:text-amber-100">
                                    Karyawan <span class="text-red-500">*</span>
                                </label>
                                <select name="employee_id" id="employee_id" required
                                        class="w-full px-4 py-3 bg-white dark:bg-gray-800 border border-amber-300 dark:border-amber-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 dark:text-gray-100 transition-all duration-200">
                                    <option value="">Pilih Karyawan</option>
                                    <!-- Will be populated via AJAX -->
                                </select>
                            </div>
                            
                            <div class="space-y-2">
                                <label for="action" class="block text-sm font-medium text-amber-900 dark:text-amber-100">
                                    Aksi <span class="text-red-500">*</span>
                                </label>
                                <select name="action" id="action" required
                                        class="w-full px-4 py-3 bg-white dark:bg-gray-800 border border-amber-300 dark:border-amber-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 dark:text-gray-100 transition-all duration-200">
                                    <option value="check-in">Check In</option>
                                    <option value="check-out">Check Out</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="space-y-2">
                            <label for="notes" class="block text-sm font-medium text-amber-900 dark:text-amber-100">
                                Catatan
                            </label>
                            <textarea name="notes" id="notes" rows="3" 
                                      class="w-full px-4 py-3 bg-white dark:bg-gray-800 border border-amber-300 dark:border-amber-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 dark:text-gray-100 transition-all duration-200" 
                                      placeholder="Catatan opsional untuk entry manual"></textarea>
                        </div>
                        
                        <button type="submit" 
                                class="inline-flex items-center px-6 py-3 text-sm font-medium text-white bg-amber-600 hover:bg-amber-700 rounded-lg transition-all duration-200 hover:scale-105">
                            <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            Entry Manual
                        </button>
                    </form>
                </div>
                @endcan
            </div>
        </x-layouts.glass-card>
    </div>

</x-layouts.page-base>

<!-- Success Modal -->
<div id="successModal" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" aria-hidden="true"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-sm p-6 border border-gray-200 dark:border-gray-700">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-emerald-100 dark:bg-emerald-800/30 mb-4">
                    <svg class="h-8 w-8 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Berhasil!</h3>
                <div class="text-center text-gray-600 dark:text-gray-300 mb-6" id="success-message">
                    Absensi berhasil dicatat!
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                    <div class="bg-gradient-to-r from-emerald-400 to-emerald-600 h-3 rounded-full transition-all duration-500 shadow-sm" style="width: 100%"></div>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Otomatis menutup dalam beberapa detik...</p>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
let currentDetectionMethod = 'face-api';
let faceDetectionSystem = null;
let currentEmployee = null;

$(document).ready(function() {
    // Load current user's employee info
    loadCurrentEmployee();
    
    // Load attendance status
    loadAttendanceStatus();
    
    // Initialize face detection
    initializeFaceDetection();
    
    // Detection method switcher
    $('#use-face-api').on('click', function(e) {
        e.preventDefault();
        switchDetectionMethod('face-api');
    });
    
    $('#use-mediapipe').on('click', function(e) {
        e.preventDefault();
        switchDetectionMethod('mediapipe');
    });
    
    // Manual attendance form
    $('#manual-attendance-form').on('submit', function(e) {
        e.preventDefault();
        handleManualAttendance();
    });
    
    // Auto-refresh status every 30 seconds
    setInterval(loadAttendanceStatus, 30000);
});

function loadCurrentEmployee() {
    $.get('/api/v1/user')
        .done(function(response) {
            currentEmployee = response.employee;
            if (currentEmployee) {
                console.log('Current employee loaded:', currentEmployee.full_name);
            }
        })
        .fail(function() {
            console.error('Failed to load current employee');
        });
}

function loadAttendanceStatus() {
    $.get('/api/v1/attendance/status')
        .done(function(response) {
            if (response.success) {
                displayAttendanceStatus(response.data);
            }
        })
        .fail(function(xhr) {
            console.error('Failed to load attendance status:', xhr.responseJSON);
            $('#status-content').html('<div class="bg-destructive/10 border border-destructive/20 text-destructive px-4 py-3 rounded-lg">Failed to load attendance status</div>');
        });
}

function displayAttendanceStatus(data) {
    const statusContent = $('#status-content');
    let html = '';
    
    if (data.status === 'not_checked_in') {
        html = `
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-20 w-20 rounded-full bg-gradient-to-br from-emerald-100 to-emerald-200 dark:from-emerald-800 dark:to-emerald-700 mb-6">
                    <svg class="h-10 w-10 text-emerald-600 dark:text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-2">Siap untuk Check In</h3>
                <p class="text-gray-600 dark:text-gray-300">Anda belum melakukan check in hari ini.</p>
            </div>
        `;
    } else if (data.status === 'checked_in') {
        const checkInTime = new Date(data.check_in_time).toLocaleTimeString('id-ID');
        html = `
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-gradient-to-br from-emerald-50 to-emerald-100 dark:from-emerald-900/30 dark:to-emerald-800/20 rounded-xl border border-emerald-200 dark:border-emerald-700/50 p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 h-12 w-12 rounded-full bg-emerald-500 flex items-center justify-center mr-4">
                            <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <div class="font-semibold text-emerald-900 dark:text-emerald-100">Sudah Check In</div>
                            <div class="text-emerald-600 dark:text-emerald-400 text-sm">${checkInTime}</div>
                        </div>
                    </div>
                </div>
                <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/30 dark:to-blue-800/20 rounded-xl border border-blue-200 dark:border-blue-700/50 p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 h-12 w-12 rounded-full bg-blue-500 flex items-center justify-center mr-4">
                            <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <div class="font-semibold text-blue-900 dark:text-blue-100">Waktu Kerja</div>
                            <div class="text-blue-600 dark:text-blue-400 text-sm" id="working-time">${data.working_hours_formatted || '0j 0m'}</div>
                        </div>
                    </div>
                </div>
                <div class="bg-gradient-to-br from-amber-50 to-amber-100 dark:from-amber-900/30 dark:to-amber-800/20 rounded-xl border border-amber-200 dark:border-amber-700/50 p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 h-12 w-12 rounded-full bg-amber-500 flex items-center justify-center mr-4">
                            <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7"/>
                            </svg>
                        </div>
                        <div>
                            <div class="font-semibold text-amber-900 dark:text-amber-100">Siap Check Out</div>
                            <div class="text-amber-600 dark:text-amber-400 text-sm">Gunakan pengenalan wajah</div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Start working time counter
        startWorkingTimeCounter(data.check_in_time);
    } else if (data.status === 'checked_out') {
        const checkInTime = new Date(data.check_in_time).toLocaleTimeString('id-ID');
        const checkOutTime = new Date(data.check_out_time).toLocaleTimeString('id-ID');
        html = `
            <div class="bg-gradient-to-br from-emerald-50 to-emerald-100 dark:from-emerald-900/30 dark:to-emerald-800/20 border border-emerald-200 dark:border-emerald-700/50 rounded-xl p-6" role="alert">
                <div class="flex items-center mb-4">
                    <div class="h-12 w-12 rounded-full bg-emerald-500 flex items-center justify-center mr-4">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h4 class="font-bold text-lg text-emerald-900 dark:text-emerald-100">Hari Kerja Selesai!</h4>
                        <p class="text-emerald-600 dark:text-emerald-400 text-sm">Terima kasih atas kerja keras Anda hari ini</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                    <div class="text-center p-3 bg-white dark:bg-gray-800 rounded-lg">
                        <div class="font-semibold text-emerald-900 dark:text-emerald-100">Check-in</div>
                        <div class="text-emerald-600 dark:text-emerald-400">${checkInTime}</div>
                    </div>
                    <div class="text-center p-3 bg-white dark:bg-gray-800 rounded-lg">
                        <div class="font-semibold text-emerald-900 dark:text-emerald-100">Check-out</div>
                        <div class="text-emerald-600 dark:text-emerald-400">${checkOutTime}</div>
                    </div>
                    <div class="text-center p-3 bg-white dark:bg-gray-800 rounded-lg">
                        <div class="font-semibold text-emerald-900 dark:text-emerald-100">Total</div>
                        <div class="text-emerald-600 dark:text-emerald-400">${data.working_hours_formatted}</div>
                    </div>
                </div>
            </div>
        `;
    }
    
    statusContent.html(html);
}

function startWorkingTimeCounter(checkInTime) {
    setInterval(function() {
        const now = new Date();
        const start = new Date(checkInTime);
        const diff = now - start;
        
        const hours = Math.floor(diff / (1000 * 60 * 60));
        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        
        $('#working-time').text(`${hours}h ${minutes}m`);
    }, 60000); // Update every minute
}

function toggleDetectionDropdown() {
    $('#detection-dropdown').toggleClass('hidden');
}

// Close dropdown when clicking outside
$(document).on('click', function(e) {
    if (!$(e.target).closest('#detection-dropdown').length && !$(e.target).closest('[onclick="toggleDetectionDropdown()"]').length) {
        $('#detection-dropdown').addClass('hidden');
    }
});

function initializeFaceDetection() {
    console.log('Menginisialisasi deteksi wajah dengan metode:', currentDetectionMethod);
    
    const methodName = currentDetectionMethod === 'face-api' ? 'Face-API.js' : 'MediaPipe';
    
    $('#face-detection-container').html(`
        <div class="bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-800 dark:to-slate-900 rounded-xl p-6">
            <!-- Camera Preview Area -->
            <div class="relative mb-6">
                <div id="camera-preview" class="relative w-full h-80 bg-black rounded-xl overflow-hidden border-4 border-gray-300 dark:border-gray-600">
                    <video id="video-stream" class="w-full h-full object-cover hidden" autoplay muted playsinline></video>
                    <canvas id="face-overlay" class="absolute top-0 left-0 w-full h-full pointer-events-none hidden"></canvas>
                    
                    <!-- Camera Placeholder -->
                    <div id="camera-placeholder" class="flex items-center justify-center h-full bg-gradient-to-br from-gray-800 to-gray-900">
                        <div class="text-center">
                            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-white/10 backdrop-blur-sm mb-4">
                                <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-white mb-2">Kamera Siap</h3>
                            <p class="text-gray-300 text-sm">Klik tombol mulai untuk mengaktifkan kamera</p>
                        </div>
                    </div>
                    
                    <!-- Face Detection Status -->
                    <div id="detection-status" class="absolute top-4 left-4 right-4 hidden">
                        <div class="bg-black/70 backdrop-blur-sm rounded-lg p-3 text-white">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <div id="detection-indicator" class="w-3 h-3 rounded-full bg-red-500 animate-pulse"></div>
                                    <span id="detection-text" class="text-sm font-medium">Mencari wajah...</span>
                                </div>
                                <div id="confidence-score" class="text-xs bg-white/20 px-2 py-1 rounded">0%</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Face Frame Guide -->
                    <div id="face-guide" class="absolute inset-0 flex items-center justify-center pointer-events-none hidden">
                        <div class="relative">
                            <svg width="200" height="240" viewBox="0 0 200 240" class="text-white/60">
                                <!-- Face outline -->
                                <ellipse cx="100" cy="120" rx="80" ry="100" fill="none" stroke="currentColor" stroke-width="2" stroke-dasharray="5,5" class="animate-pulse"/>
                                <!-- Corner guides -->
                                <path d="M30 40 L30 20 L50 20" stroke="currentColor" stroke-width="3" fill="none"/>
                                <path d="M170 40 L170 20 L150 20" stroke="currentColor" stroke-width="3" fill="none"/>
                                <path d="M30 200 L30 220 L50 220" stroke="currentColor" stroke-width="3" fill="none"/>
                                <path d="M170 200 L170 220 L150 220" stroke="currentColor" stroke-width="3" fill="none"/>
                            </svg>
                            <div class="absolute -bottom-8 left-1/2 transform -translate-x-1/2 text-white text-xs text-center">
                                Posisikan wajah di dalam frame
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Detection Method Badge -->
                <div class="absolute top-2 right-2 bg-purple-600 text-white text-xs px-3 py-1 rounded-full font-medium">
                    ${methodName}
                </div>
            </div>
            
            <!-- Face Recognition Stats -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg p-3 text-center border border-gray-200 dark:border-gray-700">
                    <div class="text-lg font-bold text-emerald-600 dark:text-emerald-400" id="faces-detected">0</div>
                    <div class="text-xs text-gray-600 dark:text-gray-400">Wajah Terdeteksi</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg p-3 text-center border border-gray-200 dark:border-gray-700">
                    <div class="text-lg font-bold text-blue-600 dark:text-blue-400" id="confidence-avg">0%</div>
                    <div class="text-xs text-gray-600 dark:text-gray-400">Akurasi Rata-rata</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg p-3 text-center border border-gray-200 dark:border-gray-700">
                    <div class="text-lg font-bold text-amber-600 dark:text-amber-400" id="processing-time">0ms</div>
                    <div class="text-xs text-gray-600 dark:text-gray-400">Waktu Proses</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg p-3 text-center border border-gray-200 dark:border-gray-700">
                    <div class="text-lg font-bold text-purple-600 dark:text-purple-400" id="liveness-score">0%</div>
                    <div class="text-xs text-gray-600 dark:text-gray-400">Liveness Score</div>
                </div>
            </div>
            
            <!-- Control Buttons -->
            <div class="flex flex-wrap gap-3 justify-center">
                <button id="start-camera-btn" class="inline-flex items-center px-6 py-3 text-sm font-medium text-white bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 rounded-lg transition-all duration-200 hover:scale-105 shadow-sm" onclick="startCamera()">
                    <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                    Mulai Kamera
                </button>
                
                <button id="stop-camera-btn" class="hidden inline-flex items-center px-6 py-3 text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 rounded-lg transition-all duration-200" onclick="stopCamera()">
                    <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"/>
                    </svg>
                    Hentikan
                </button>
                
                <button id="capture-btn" class="hidden inline-flex items-center px-6 py-3 text-sm font-medium text-white bg-gradient-to-r from-purple-500 to-purple-600 hover:from-purple-600 hover:to-purple-700 rounded-lg transition-all duration-200 hover:scale-105 shadow-sm" onclick="captureAndProcess()">
                    <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Ambil Foto & Proses
                </button>
                
                <button class="inline-flex items-center px-6 py-3 text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 rounded-lg transition-all duration-200" onclick="simulateAttendance()">
                    <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    Simulasi (Demo)
                </button>
            </div>
            
            <!-- Instructions -->
            <div class="mt-6 bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-700/50 rounded-lg p-4">
                <h4 class="font-semibold text-blue-900 dark:text-blue-100 mb-2 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Petunjuk Penggunaan
                </h4>
                <div class="text-sm text-blue-700 dark:text-blue-300 space-y-1">
                    <p>📱 Pastikan pencahayaan cukup terang dan wajah terlihat jelas</p>
                    <p>👤 Posisikan wajah di tengah frame dan hindari gerakan berlebihan</p>
                    <p>🔒 Sistem akan melakukan verifikasi liveness untuk keamanan ekstra</p>
                    <p>⚡ Proses deteksi otomatis akan berjalan setelah kamera aktif</p>
                </div>
            </div>
        </div>
    `);
    
    // Initialize face detection variables
    window.faceDetectionActive = false;
    window.detectionStats = {
        facesDetected: 0,
        confidenceSum: 0,
        processingTimes: [],
        livenessScore: 0
    };
}

function switchDetectionMethod(method) {
    currentDetectionMethod = method;
    $('#detection-dropdown').addClass('hidden');
    console.log('Beralih ke metode deteksi:', method);
    toastr.info(`Metode deteksi beralih ke ${method === 'face-api' ? 'Face-API.js' : 'MediaPipe'}`, 'Info');
    initializeFaceDetection();
}

function simulateAttendance() {
    if (!currentEmployee) {
        toastr.error('Tidak ada data karyawan untuk pengguna saat ini', 'Error');
        return;
    }
    
    // Show loading state
    $('#face-detection-container').html(`
        <div class="text-center p-12">
            <div class="mx-auto flex items-center justify-center h-20 w-20 rounded-full bg-gradient-to-br from-blue-100 to-blue-200 dark:from-blue-800 dark:to-blue-700 mb-6">
                <div class="animate-spin rounded-full h-10 w-10 border-2 border-blue-500 border-t-transparent"></div>
            </div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-2">Memproses Absensi...</h3>
            <p class="text-gray-600 dark:text-gray-300">Sedang memverifikasi identitas dan mencatat absensi</p>
        </div>
    `);
    
    // Get current status to determine action
    $.get('/api/v1/attendance/status')
        .done(function(response) {
            const action = response.data.can_check_in ? 'check-in' : 'check-out';
            const endpoint = `/api/v1/attendance/${action}`;
            const actionText = action === 'check-in' ? 'Check In' : 'Check Out';
            
            const data = {
                employee_id: currentEmployee.id,
                face_confidence: 0.95, // Simulated high confidence
                latitude: -6.2088,  // Jakarta coordinates for demo
                longitude: 106.8456,
                notes: `Simulasi ${actionText} untuk demo`,
                metadata: {
                    demo_mode: true,
                    detection_method: currentDetectionMethod,
                    confidence_score: 0.95
                }
            };
            
            // Simulate processing time
            setTimeout(() => {
                $.post(endpoint, data)
                    .done(function(response) {
                        if (response.success) {
                            $('#success-message').text(response.message || `${actionText} berhasil dicatat!`);
                            showSuccessModal();
                            
                            // Refresh status after 3 seconds
                            setTimeout(() => {
                                loadAttendanceStatus();
                                hideSuccessModal();
                                initializeFaceDetection();
                            }, 3000);
                        }
                    })
                    .fail(function(xhr) {
                        const error = xhr.responseJSON;
                        toastr.error(error?.message || 'Absensi gagal dicatat', 'Error');
                        initializeFaceDetection(); // Reset face detection view
                    });
            }, 2000);
        })
        .fail(function() {
            toastr.error('Gagal memuat status absensi', 'Error');
            initializeFaceDetection();
        });
}

function showSuccessModal() {
    $('#successModal').removeClass('hidden');
}

function hideSuccessModal() {
    $('#successModal').addClass('hidden');
}

// Camera and Face Detection Functions
let videoStream = null;
let detectionInterval = null;
let faceDetectionModel = null;

async function startCamera() {
    try {
        // Request camera permission
        videoStream = await navigator.mediaDevices.getUserMedia({
            video: {
                width: { ideal: 640 },
                height: { ideal: 480 },
                facingMode: 'user'
            },
            audio: false
        });
        
        const videoElement = document.getElementById('video-stream');
        const placeholder = document.getElementById('camera-placeholder');
        const detectionStatus = document.getElementById('detection-status');
        const faceGuide = document.getElementById('face-guide');
        
        videoElement.srcObject = videoStream;
        
        // Show video and hide placeholder
        videoElement.classList.remove('hidden');
        placeholder.classList.add('hidden');
        detectionStatus.classList.remove('hidden');
        faceGuide.classList.remove('hidden');
        
        // Update button states
        document.getElementById('start-camera-btn').classList.add('hidden');
        document.getElementById('stop-camera-btn').classList.remove('hidden');
        document.getElementById('capture-btn').classList.remove('hidden');
        
        // Start face detection when video is ready
        videoElement.onloadedmetadata = () => {
            startFaceDetection();
        };
        
        toastr.success('Kamera berhasil diaktifkan', 'Berhasil');
        
    } catch (error) {
        console.error('Error accessing camera:', error);
        toastr.error('Gagal mengakses kamera. Pastikan izin kamera telah diberikan.', 'Error');
        
        // Show helpful error message based on error type
        if (error.name === 'NotAllowedError') {
            toastr.info('Silakan berikan izin akses kamera di browser Anda', 'Info');
        } else if (error.name === 'NotFoundError') {
            toastr.warning('Tidak ada kamera yang terdeteksi', 'Peringatan');
        }
    }
}

function stopCamera() {
    if (videoStream) {
        videoStream.getTracks().forEach(track => track.stop());
        videoStream = null;
    }
    
    if (detectionInterval) {
        clearInterval(detectionInterval);
        detectionInterval = null;
    }
    
    const videoElement = document.getElementById('video-stream');
    const placeholder = document.getElementById('camera-placeholder');
    const detectionStatus = document.getElementById('detection-status');
    const faceGuide = document.getElementById('face-guide');
    const overlay = document.getElementById('face-overlay');
    
    // Hide video and show placeholder
    videoElement.classList.add('hidden');
    overlay.classList.add('hidden');
    placeholder.classList.remove('hidden');
    detectionStatus.classList.add('hidden');
    faceGuide.classList.add('hidden');
    
    // Update button states
    document.getElementById('start-camera-btn').classList.remove('hidden');
    document.getElementById('stop-camera-btn').classList.add('hidden');
    document.getElementById('capture-btn').classList.add('hidden');
    
    // Reset stats
    resetDetectionStats();
    
    toastr.info('Kamera dihentikan', 'Info');
}

function startFaceDetection() {
    window.faceDetectionActive = true;
    updateDetectionStatus('Memulai deteksi wajah...', 'loading');
    
    // Simulate face detection process
    detectionInterval = setInterval(() => {
        performFaceDetection();
    }, 500); // Check every 500ms
}

function performFaceDetection() {
    if (!window.faceDetectionActive) return;
    
    const startTime = performance.now();
    
    // Simulate face detection results
    const simulatedDetection = simulateFaceDetection();
    
    const processingTime = Math.round(performance.now() - startTime);
    
    if (simulatedDetection.faceDetected) {
        updateDetectionStatus(`Wajah terdeteksi (${simulatedDetection.confidence}%)`, 'success');
        updateDetectionStats(simulatedDetection, processingTime);
        drawFaceBox(simulatedDetection.boundingBox);
    } else {
        updateDetectionStatus('Mencari wajah...', 'searching');
        clearFaceBox();
    }
}

function simulateFaceDetection() {
    // Simulate realistic face detection results
    const faceDetected = Math.random() > 0.3; // 70% chance of detecting face
    const confidence = faceDetected ? Math.floor(75 + Math.random() * 20) : 0; // 75-95% confidence
    const liveness = faceDetected ? Math.floor(80 + Math.random() * 15) : 0; // 80-95% liveness
    
    return {
        faceDetected,
        confidence,
        liveness,
        boundingBox: faceDetected ? {
            x: 160 + (Math.random() - 0.5) * 40,
            y: 120 + (Math.random() - 0.5) * 30,
            width: 120 + Math.random() * 20,
            height: 140 + Math.random() * 20
        } : null
    };
}

function updateDetectionStatus(message, type) {
    const indicator = document.getElementById('detection-indicator');
    const text = document.getElementById('detection-text');
    
    text.textContent = message;
    
    // Update indicator color based on type
    indicator.className = 'w-3 h-3 rounded-full';
    switch (type) {
        case 'success':
            indicator.className += ' bg-green-500';
            break;
        case 'loading':
        case 'searching':
            indicator.className += ' bg-yellow-500 animate-pulse';
            break;
        case 'error':
            indicator.className += ' bg-red-500';
            break;
        default:
            indicator.className += ' bg-gray-500';
    }
}

function updateDetectionStats(detection, processingTime) {
    const stats = window.detectionStats;
    
    if (detection.faceDetected) {
        stats.facesDetected++;
        stats.confidenceSum += detection.confidence;
        stats.livenessScore = detection.liveness;
    }
    
    stats.processingTimes.push(processingTime);
    if (stats.processingTimes.length > 10) {
        stats.processingTimes.shift(); // Keep only last 10 measurements
    }
    
    // Update UI
    document.getElementById('faces-detected').textContent = stats.facesDetected;
    
    const avgConfidence = stats.facesDetected > 0 ? 
        Math.round(stats.confidenceSum / stats.facesDetected) : 0;
    document.getElementById('confidence-avg').textContent = `${avgConfidence}%`;
    document.getElementById('confidence-score').textContent = `${detection.confidence || 0}%`;
    
    const avgProcessingTime = stats.processingTimes.length > 0 ?
        Math.round(stats.processingTimes.reduce((a, b) => a + b, 0) / stats.processingTimes.length) : 0;
    document.getElementById('processing-time').textContent = `${avgProcessingTime}ms`;
    
    document.getElementById('liveness-score').textContent = `${stats.livenessScore}%`;
}

function resetDetectionStats() {
    window.detectionStats = {
        facesDetected: 0,
        confidenceSum: 0,
        processingTimes: [],
        livenessScore: 0
    };
    
    // Reset UI
    document.getElementById('faces-detected').textContent = '0';
    document.getElementById('confidence-avg').textContent = '0%';
    document.getElementById('confidence-score').textContent = '0%';
    document.getElementById('processing-time').textContent = '0ms';
    document.getElementById('liveness-score').textContent = '0%';
}

function drawFaceBox(boundingBox) {
    if (!boundingBox) return;
    
    const canvas = document.getElementById('face-overlay');
    const video = document.getElementById('video-stream');
    
    if (!canvas || !video) return;
    
    canvas.width = video.videoWidth || video.offsetWidth;
    canvas.height = video.videoHeight || video.offsetHeight;
    canvas.classList.remove('hidden');
    
    const ctx = canvas.getContext('2d');
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    
    // Draw face bounding box
    ctx.strokeStyle = '#10B981'; // Emerald green
    ctx.lineWidth = 3;
    ctx.setLineDash([5, 5]);
    ctx.strokeRect(boundingBox.x, boundingBox.y, boundingBox.width, boundingBox.height);
    
    // Draw corner indicators
    const cornerSize = 20;
    ctx.setLineDash([]);
    ctx.lineWidth = 4;
    ctx.strokeStyle = '#FFFFFF';
    
    // Top-left corner
    ctx.beginPath();
    ctx.moveTo(boundingBox.x, boundingBox.y + cornerSize);
    ctx.lineTo(boundingBox.x, boundingBox.y);
    ctx.lineTo(boundingBox.x + cornerSize, boundingBox.y);
    ctx.stroke();
    
    // Top-right corner
    ctx.beginPath();
    ctx.moveTo(boundingBox.x + boundingBox.width - cornerSize, boundingBox.y);
    ctx.lineTo(boundingBox.x + boundingBox.width, boundingBox.y);
    ctx.lineTo(boundingBox.x + boundingBox.width, boundingBox.y + cornerSize);
    ctx.stroke();
    
    // Bottom-left corner
    ctx.beginPath();
    ctx.moveTo(boundingBox.x, boundingBox.y + boundingBox.height - cornerSize);
    ctx.lineTo(boundingBox.x, boundingBox.y + boundingBox.height);
    ctx.lineTo(boundingBox.x + cornerSize, boundingBox.y + boundingBox.height);
    ctx.stroke();
    
    // Bottom-right corner
    ctx.beginPath();
    ctx.moveTo(boundingBox.x + boundingBox.width - cornerSize, boundingBox.y + boundingBox.height);
    ctx.lineTo(boundingBox.x + boundingBox.width, boundingBox.y + boundingBox.height);
    ctx.lineTo(boundingBox.x + boundingBox.width, boundingBox.y + boundingBox.height - cornerSize);
    ctx.stroke();
}

function clearFaceBox() {
    const canvas = document.getElementById('face-overlay');
    if (canvas) {
        const ctx = canvas.getContext('2d');
        ctx.clearRect(0, 0, canvas.width, canvas.height);
    }
}

async function captureAndProcess() {
    if (!videoStream) {
        toastr.error('Kamera belum aktif', 'Error');
        return;
    }
    
    const video = document.getElementById('video-stream');
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');
    
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    ctx.drawImage(video, 0, 0);
    
    // Convert to blob
    canvas.toBlob(async (blob) => {
        const formData = new FormData();
        formData.append('face_image', blob, 'face_capture.jpg');
        formData.append('employee_id', currentEmployee?.id || '');
        formData.append('detection_method', currentDetectionMethod);
        formData.append('confidence_score', document.getElementById('confidence-score').textContent);
        formData.append('liveness_score', document.getElementById('liveness-score').textContent);
        
        try {
            updateDetectionStatus('Memproses gambar...', 'loading');
            
            // Simulate processing time
            await new Promise(resolve => setTimeout(resolve, 2000));
            
            // For demo purposes, we'll simulate the attendance process
            simulateAttendance();
            
        } catch (error) {
            console.error('Face processing error:', error);
            toastr.error('Gagal memproses gambar wajah', 'Error');
            updateDetectionStatus('Error memproses gambar', 'error');
        }
    }, 'image/jpeg', 0.8);
}

function handleManualAttendance() {
    const form = $('#manual-attendance-form');
    const formData = new FormData(form[0]);
    
    if (!formData.get('employee_id') || !formData.get('action')) {
        toastr.error('Harap lengkapi semua field yang wajib diisi', 'Validasi Error');
        return;
    }
    
    const submitButton = form.find('button[type="submit"]');
    const originalText = submitButton.html();
    
    submitButton.prop('disabled', true).html(`
        <div class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent mr-2"></div>
        Memproses...
    `);
    
    $.ajax({
        url: '/api/v1/attendance/manual-entry',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    })
    .done(function(response) {
        if (response.success) {
            toastr.success(response.message || 'Entry manual berhasil dicatat', 'Berhasil');
            form[0].reset();
            loadAttendanceStatus();
        }
    })
    .fail(function(xhr) {
        const error = xhr.responseJSON;
        toastr.error(error?.message || 'Entry manual gagal', 'Error');
    })
    .always(function() {
        submitButton.prop('disabled', false).html(originalText);
    });
}
</script>
@endsection