@extends('layouts.authenticated-unified')

@section('title', 'Pengaturan Sistem')

@section('page-content')
<!-- Page Header -->
<div class="mb-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">System Settings</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Konfigurasi pengaturan sistem untuk manajemen absensi Anda</p>
        </div>
        <div class="flex items-center space-x-3">
            <button type="button" onclick="resetToDefaults()" class="bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 px-4 py-2 rounded-lg font-medium transition-colors duration-200">
                Reset ke Default
            </button>
            <button type="submit" form="settings-form" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200">
                Simpan Pengaturan
            </button>
        </div>
    </div>
</div>
        
@can('manage_system_settings')
<form id="settings-form" action="{{ route('system.settings.update') }}" method="POST" class="space-y-8">
    @csrf
    @method('PUT')
                
                <!-- General Settings -->
<x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-8">
    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Pengaturan Umum</h3>
        <p class="text-gray-600 dark:text-gray-400">Konfigurasi sistem dasar</p>
    </div>
    <div class="p-6">
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
                <label for="system_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Nama Sistem</label>
                <input 
                    type="text"
                    name="system_name"
                    id="system_name"
                    value="{{ config('app.name', 'AttendanceHub') }}"
                    placeholder="AttendanceHub" 
                    class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Nama organisasi atau sistem Anda</p>
            </div>
            
            <div>
                <label for="timezone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Zona Waktu Sistem</label>
                <select name="timezone" id="timezone" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="America/New_York">Waktu Timur (UTC-5)</option>
                    <option value="America/Chicago">Waktu Tengah (UTC-6)</option>
                    <option value="America/Denver">Waktu Pegunungan (UTC-7)</option>
                    <option value="America/Los_Angeles">Waktu Pasifik (UTC-8)</option>
                    <option value="UTC" selected>UTC (Waktu Universal)</option>
                    <option value="Europe/London">London (UTC+0)</option>
                    <option value="Asia/Tokyo">Tokyo (UTC+9)</option>
                </select>
            </div>
        </div>
        
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
                <label for="date_format" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Format Tanggal</label>
                <select name="date_format" id="date_format" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="Y-m-d" selected>2024-01-15 (YYYY-MM-DD)</option>
                    <option value="m/d/Y">01/15/2024 (MM/DD/YYYY)</option>
                    <option value="d/m/Y">15/01/2024 (DD/MM/YYYY)</option>
                    <option value="F j, Y">Januari 15, 2024</option>
                </select>
            </div>
            
            <div>
                <label for="time_format" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Format Waktu</label>
                <select name="time_format" id="time_format" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="H:i" selected>14:30 (24 Jam)</option>
                    <option value="g:i A">2:30 PM (12 Jam)</option>
                </select>
            </div>
        </div>
    </div>
</x-ui.card>
                    
<!-- Attendance Settings -->
<x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-8">
    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Pengaturan Absensi</h3>
        <p class="text-gray-600 dark:text-gray-400">Konfigurasi aturan pelacakan absensi</p>
    </div>
    <div class="p-6">
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
                <label for="default_work_start" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Waktu Mulai Kerja Default</label>
                <input type="time" name="default_work_start" id="default_work_start" value="09:00" 
                       class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Waktu mulai hari kerja standar</p>
            </div>
            
            <div>
                <label for="default_work_end" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Waktu Selesai Kerja Default</label>
                <input type="time" name="default_work_end" id="default_work_end" value="17:00" 
                       class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Waktu selesai hari kerja standar</p>
            </div>
        </div>
        
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
                <label for="late_threshold" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Ambang Batas Terlambat (menit)</label>
                <input type="number" name="late_threshold" id="late_threshold" value="15" min="1" max="60" 
                       class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Menit setelah waktu mulai untuk ditandai sebagai terlambat</p>
            </div>
            
            <div>
                <label for="break_duration" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Durasi Istirahat (menit)</label>
                <input type="number" name="break_duration" id="break_duration" value="60" min="30" max="120" 
                       class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Durasi istirahat makan siang standar</p>
            </div>
        </div>
        
        <div class="flex items-center space-x-6">
            <label class="flex items-center">
                <input type="checkbox" name="require_face_recognition" value="1" checked class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600" />
                <span class="ml-2 text-sm text-gray-900 dark:text-white">Wajibkan pengenalan wajah untuk check-in</span>
            </label>
            
            <label class="flex items-center">
                <input type="checkbox" name="require_gps_verification" value="1" checked class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600" />
                <span class="ml-2 text-sm text-gray-900 dark:text-white">Wajibkan verifikasi GPS</span>
            </label>
        </div>
    </div>
</x-ui.card>
<!-- Face Recognition Settings -->
<x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-8">
    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Pengaturan Pengenalan Wajah</h3>
        <p class="text-gray-600 dark:text-gray-400">Konfigurasi parameter deteksi wajah</p>
    </div>
    <div class="p-6">
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
                <label for="face_confidence_threshold" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Ambang Batas Kepercayaan Wajah</label>
                <input
                    type="number"
                    name="face_confidence_threshold"
                    id="face_confidence_threshold"
                    value="0.85"
                    step="0.01"
                    min="0.5"
                    max="1.0"
                    class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                />
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Tingkat kepercayaan minimum untuk pengenalan wajah (0.5-1.0)</p>
            </div>
                        
                        <div>
                            <x-ui.label for="max_face_distance" value="Jarak Wajah Maksimum" class="text-slate-700 dark:text-slate-300" />
                            <x-ui.input
                                type="number"
                                name="max_face_distance"
                                id="max_face_distance"
                                value="0.6"
                                step="0.01"
                                min="0.1"
                                max="1.0"
                                class="bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300"
                            />
                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Jarak maksimum antara wajah untuk pencocokan</p>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <x-ui.label for="required_gestures" value="Gerakan Anti-Spoofing yang Diperlukan" class="text-slate-700 dark:text-slate-300" />
                            <x-ui.select name="required_gestures" id="required_gestures" multiple 
                                       class="bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300">
                                <option value="blink">Kedipan Mata</option>
                                <option value="smile">Senyum</option>
                                <option value="head_nod">Anggukan Kepala</option>
                                <option value="head_shake">Goyangan Kepala</option>
                            </x-ui.select>
                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Pilih gerakan yang diperlukan untuk verifikasi</p>
                        </div>
                        
                        <div>
                            <x-ui.label for="gesture_timeout" value="Batas Waktu Gerakan (detik)" class="text-slate-700 dark:text-slate-300" />
                            <x-ui.input
                                type="number"
                                name="gesture_timeout"
                                id="gesture_timeout"
                                value="10"
                                min="5"
                                max="30"
                                class="bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300"
                            />
                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Batas waktu untuk menyelesaikan gerakan</p>
                        </div>
                    </div>
                </div>
                
                <!-- GPS & Location Settings -->
                <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                    <h3 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Pengaturan GPS & Lokasi</h3>
                    <p class="text-slate-600 dark:text-slate-400 mb-6">Konfigurasi verifikasi lokasi</p>
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <x-ui.label for="gps_accuracy_threshold" value="Ambang Batas Akurasi GPS (meter)" class="text-slate-700 dark:text-slate-300" />
                            <x-ui.input
                                type="number"
                                name="gps_accuracy_threshold"
                                id="gps_accuracy_threshold"
                                value="50"
                                min="10"
                                max="500"
                                class="bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300"
                            />
                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Akurasi GPS maksimum yang diperlukan untuk check-in</p>
                        </div>
                        
                        <div>
                            <x-ui.label for="location_radius" value="Radius Lokasi (meter)" class="text-slate-700 dark:text-slate-300" />
                            <x-ui.input
                                type="number"
                                name="location_radius"
                                id="location_radius"
                                value="100"
                                min="25"
                                max="1000"
                                class="bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300"
                            />
                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Jarak yang diizinkan dari lokasi kantor</p>
                        </div>
                    </div>
                    
                    <div class="flex items-center space-x-6">
                        <label class="flex items-center">
                            <x-ui.checkbox name="allow_remote_checkin" value="1" />
                            <span class="ml-2 text-sm text-slate-800 dark:text-white">Izinkan check-in jarak jauh</span>
                        </label>
                        
                        <label class="flex items-center">
                            <x-ui.checkbox name="log_gps_coordinates" value="1" checked />
                            <span class="ml-2 text-sm text-slate-800 dark:text-white">Catat koordinat GPS</span>
                        </label>
                    </div>
                </div>
                
                <!-- Notification Settings -->
                <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                    <h3 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Pengaturan Notifikasi</h3>
                    <p class="text-slate-600 dark:text-slate-400 mb-6">Konfigurasi notifikasi sistem</p>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between py-3 border-b border-white/20">
                            <div>
                                <h4 class="text-sm font-medium text-slate-800 dark:text-white">Notifikasi Kedatangan Terlambat</h4>
                                <p class="text-sm text-slate-600 dark:text-slate-400">Beritahu manajer ketika karyawan terlambat</p>
                            </div>
                            <label class="flex items-center">
                                <x-ui.checkbox name="notify_late_arrivals" value="1" checked />
                            </label>
                        </div>
                        
                        <div class="flex items-center justify-between py-3 border-b border-white/20">
                            <div>
                                <h4 class="text-sm font-medium text-slate-800 dark:text-white">Notifikasi Lupa Check-out</h4>
                                <p class="text-sm text-slate-600 dark:text-slate-400">Beritahu karyawan yang lupa check-out</p>
                            </div>
                            <label class="flex items-center">
                                <x-ui.checkbox name="notify_missed_checkout" value="1" checked />
                            </label>
                        </div>
                        
                        <div class="flex items-center justify-between py-3 border-b border-white/20">
                            <div>
                                <h4 class="text-sm font-medium text-slate-800 dark:text-white">Laporan Mingguan</h4>
                                <p class="text-sm text-slate-600 dark:text-slate-400">Kirim ringkasan absensi mingguan</p>
                            </div>
                            <label class="flex items-center">
                                <x-ui.checkbox name="send_weekly_reports" value="1" />
                            </label>
                        </div>
                        
                        <div class="flex items-center justify-between py-3">
                            <div>
                                <h4 class="text-sm font-medium text-slate-800 dark:text-white">Notifikasi Email</h4>
                                <p class="text-sm text-slate-600 dark:text-slate-400">Kirim notifikasi melalui email</p>
                            </div>
                            <label class="flex items-center">
                                <x-ui.checkbox name="email_notifications" value="1" checked />
                            </label>
                        </div>
                    </div>
                </div>
                
                <!-- Data Retention Settings -->
                <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                    <h3 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Pengaturan Retensi Data</h3>
                    <p class="text-slate-600 dark:text-slate-400 mb-6">Konfigurasi kebijakan pembersihan data</p>
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <x-ui.label for="attendance_retention_period" value="Retensi Data Absensi" class="text-slate-700 dark:text-slate-300" />
                            <x-ui.select name="attendance_retention_period" id="attendance_retention_period" class="bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300">
                                <option value="90">3 Bulan</option>
                                <option value="180">6 Bulan</option>
                                <option value="365" selected>1 Tahun</option>
                                <option value="730">2 Tahun</option>
                                <option value="0">Simpan Selamanya</option>
                            </x-ui.select>
                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Berapa lama catatan absensi disimpan</p>
                        </div>
                        
                        <div>
                            <x-ui.label for="face_data_retention_period" value="Retensi Data Wajah" class="text-slate-700 dark:text-slate-300" />
                            <x-ui.select name="face_data_retention_period" id="face_data_retention_period" class="bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300">
                                <option value="30">1 Bulan</option>
                                <option value="90" selected>3 Bulan</option>
                                <option value="180">6 Bulan</option>
                                <option value="365">1 Tahun</option>
                            </x-ui.select>
                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Berapa lama data pengenalan wajah disimpan</p>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <x-ui.label for="log_retention_period" value="Retensi Log Sistem" class="text-slate-700 dark:text-slate-300" />
                            <x-ui.select name="log_retention_period" id="log_retention_period" class="bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300">
                                <option value="30">1 Bulan</option>
                                <option value="90" selected>3 Bulan</option>
                                <option value="180">6 Bulan</option>
                                <option value="365">1 Tahun</option>
                            </x-ui.select>
                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Berapa lama log sistem disimpan</p>
                        </div>
                        
                        <div>
                            <x-ui.label for="cleanup_time" value="Waktu Pembersihan Harian" class="text-slate-700 dark:text-slate-300" />
                            <x-ui.input
                                type="time"
                                name="cleanup_time"
                                id="cleanup_time"
                                value="02:00"
                                class="bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300"
                            />
                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Kapan menjalankan tugas pembersihan harian</p>
                        </div>
                    </div>
                </div>
                
                <!-- Form Actions -->
                <div class="flex items-center justify-end space-x-3 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <a
                        href="{{ route('dashboard') }}"
                        class="px-6 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg font-medium transition-colors duration-200"
                    >
                        Batal
                    </a>
                    
                    <button
                        type="submit"
                        class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors duration-200"
                    >
                        <svg class="w-5 h-5 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Simpan Pengaturan
                    </button>
                </div>
            </form>
        @else
            <div class="bg-amber-100 dark:bg-amber-900/20 border border-amber-300 dark:border-amber-600 rounded-lg p-6">
                <div class="flex items-center">
                    <svg class="h-6 w-6 text-amber-600 dark:text-amber-400 mr-3" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                    <div>
                        <h3 class="text-xl font-semibold text-amber-800 dark:text-amber-200">Akses Dibatasi</h3>
                        <p class="mt-2 text-sm text-amber-700 dark:text-amber-300">Anda tidak memiliki izin untuk mengakses pengaturan sistem.</p>
                    </div>
                </div>
            </div>
        @endcan
@endsection

@push('scripts')
<script>
    // Settings form enhancements
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-save draft settings to localStorage
        const form = document.querySelector('form');
        const inputs = form.querySelectorAll('input, select, textarea');
        
        // Load saved settings
        inputs.forEach(input => {
            const savedValue = localStorage.getItem(`setting_${input.name}`);
            if (savedValue && input.type !== 'checkbox') {
                input.value = savedValue;
            } else if (savedValue && input.type === 'checkbox') {
                input.checked = savedValue === 'true';
            }
        });
        
        // Save on change
        inputs.forEach(input => {
            input.addEventListener('change', function() {
                const value = input.type === 'checkbox' ? input.checked : input.value;
                localStorage.setItem(`setting_${input.name}`, value);
            });
        });
        
        // Clear saved settings on successful submit
        form.addEventListener('submit', function() {
            inputs.forEach(input => {
                localStorage.removeItem(`setting_${input.name}`);
            });
        });
    });
</script>
@endpush
