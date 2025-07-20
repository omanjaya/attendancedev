@extends('layouts.authenticated-unified')

@section('title', 'Pengaturan Sistem')

@section('page-content')
<div x-data="systemSettings()">
    <!-- Modern Page Header with Enhanced Actions -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Pengaturan Sistem</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Konfigurasi dan kelola pengaturan sistem manajemen absensi</p>
            </div>
            <div class="flex items-center space-x-3">
                <!-- Test Configuration Button -->
                <button @click="testConfiguration()" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg font-medium transition-all duration-200 transform hover:scale-105 shadow-md">
                    <svg class="w-5 h-5 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Test Config
                </button>
                
                <!-- Reset to Defaults Button -->
                <button @click="resetToDefaults()" class="bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 px-4 py-2 rounded-lg font-medium transition-all duration-200 transform hover:scale-105">
                    <svg class="w-5 h-5 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Reset
                </button>
                
                <!-- Save Settings Button -->
                <button type="submit" form="settings-form" class="bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white px-4 py-2 rounded-lg font-medium transition-all duration-200 transform hover:scale-105 shadow-md">
                    <svg class="w-5 h-5 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Simpan Pengaturan
                </button>
            </div>
        </div>
    </div>
        
    @can('manage_system_settings')
    <!-- Settings Navigation Tabs -->
    <div class="mb-8">
        <div class="flex items-center space-x-1 bg-gray-100 dark:bg-gray-700 rounded-lg p-1">
            <button @click="activeTab = 'general'" :class="activeTab === 'general' ? 'bg-white dark:bg-gray-600 shadow-sm' : ''" class="px-4 py-2 rounded-md text-sm font-medium transition-all duration-200">
                Umum
            </button>
            <button @click="activeTab = 'attendance'" :class="activeTab === 'attendance' ? 'bg-white dark:bg-gray-600 shadow-sm' : ''" class="px-4 py-2 rounded-md text-sm font-medium transition-all duration-200">
                Absensi
            </button>
            <button @click="activeTab = 'biometric'" :class="activeTab === 'biometric' ? 'bg-white dark:bg-gray-600 shadow-sm' : ''" class="px-4 py-2 rounded-md text-sm font-medium transition-all duration-200">
                Biometrik
            </button>
            <button @click="activeTab = 'notifications'" :class="activeTab === 'notifications' ? 'bg-white dark:bg-gray-600 shadow-sm' : ''" class="px-4 py-2 rounded-md text-sm font-medium transition-all duration-200">
                Notifikasi
            </button>
            <button @click="activeTab = 'security'" :class="activeTab === 'security' ? 'bg-white dark:bg-gray-600 shadow-sm' : ''" class="px-4 py-2 rounded-md text-sm font-medium transition-all duration-200">
                Keamanan
            </button>
            <button @click="activeTab = 'maintenance'" :class="activeTab === 'maintenance' ? 'bg-white dark:bg-gray-600 shadow-sm' : ''" class="px-4 py-2 rounded-md text-sm font-medium transition-all duration-200">
                Maintenance
            </button>
        </div>
    </div>

    <form id="settings-form" action="{{ route('system.settings.update') }}" method="POST" class="space-y-8">
        @csrf
        @method('PUT')
                
        <!-- General Settings Tab -->
        <div x-show="activeTab === 'general'" x-transition class="space-y-6">
            <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2 flex items-center">
                        <svg class="w-6 h-6 mr-3 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Pengaturan Umum
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400">Konfigurasi sistem dasar dan preferensi regional</p>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- System Information -->
                        <div class="space-y-4">
                            <div>
                                <label for="system_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Nama Sistem</label>
                                <input 
                                    type="text"
                                    name="system_name"
                                    id="system_name"
                                    value="{{ config('app.name', 'AttendanceHub') }}"
                                    placeholder="AttendanceHub" 
                                    class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200" />
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Nama organisasi atau institusi Anda</p>
                            </div>
                            
                            <div>
                                <label for="organization_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Jenis Organisasi</label>
                                <select name="organization_type" id="organization_type" class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200">
                                    <option value="school">Sekolah</option>
                                    <option value="company">Perusahaan</option>
                                    <option value="government">Pemerintahan</option>
                                    <option value="nonprofit">Non-Profit</option>
                                    <option value="other">Lainnya</option>
                                </select>
                            </div>
                            
                            <div>
                                <label for="language" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Bahasa Sistem</label>
                                <select name="language" id="language" class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200">
                                    <option value="id" selected>Bahasa Indonesia</option>
                                    <option value="en">English</option>
                                    <option value="ms">Bahasa Malaysia</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Regional Settings -->
                        <div class="space-y-4">
                            <div>
                                <label for="timezone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Zona Waktu Sistem</label>
                                <select name="timezone" id="timezone" class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200">
                                    <option value="Asia/Jakarta" selected>Jakarta (WIB, UTC+7)</option>
                                    <option value="Asia/Makassar">Makassar (WITA, UTC+8)</option>
                                    <option value="Asia/Jayapura">Jayapura (WIT, UTC+9)</option>
                                    <option value="UTC">UTC (Waktu Universal)</option>
                                    <option value="Asia/Kuala_Lumpur">Kuala Lumpur (UTC+8)</option>
                                    <option value="Asia/Singapore">Singapore (UTC+8)</option>
                                </select>
                            </div>
                            
                            <div>
                                <label for="date_format" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Format Tanggal</label>
                                <select name="date_format" id="date_format" class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200">
                                    <option value="d/m/Y" selected>15/01/2024 (DD/MM/YYYY)</option>
                                    <option value="Y-m-d">2024-01-15 (YYYY-MM-DD)</option>
                                    <option value="m/d/Y">01/15/2024 (MM/DD/YYYY)</option>
                                    <option value="j F Y">15 Januari 2024</option>
                                </select>
                            </div>
                            
                            <div>
                                <label for="time_format" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Format Waktu</label>
                                <select name="time_format" id="time_format" class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200">
                                    <option value="H:i" selected>14:30 (24 Jam)</option>
                                    <option value="g:i A">2:30 PM (12 Jam)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </x-ui.card>
        </div>
                    
        <!-- Attendance Settings Tab -->
        <div x-show="activeTab === 'attendance'" x-transition class="space-y-6">
            <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2 flex items-center">
                        <svg class="w-6 h-6 mr-3 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Pengaturan Absensi
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400">Konfigurasi aturan dan kebijakan pelacakan absensi</p>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Working Hours -->
                        <div class="space-y-4">
                            <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Jam Kerja</h4>
                            
                            <div>
                                <label for="default_work_start" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Waktu Mulai Kerja Default</label>
                                <input type="time" name="default_work_start" id="default_work_start" value="08:00" 
                                       class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200" />
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Waktu mulai hari kerja standar</p>
                            </div>
                            
                            <div>
                                <label for="default_work_end" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Waktu Selesai Kerja Default</label>
                                <input type="time" name="default_work_end" id="default_work_end" value="17:00" 
                                       class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200" />
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Waktu selesai hari kerja standar</p>
                            </div>
                            
                            <div>
                                <label for="break_duration" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Durasi Istirahat (menit)</label>
                                <input type="number" name="break_duration" id="break_duration" value="60" min="30" max="120" 
                                       class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200" />
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Durasi istirahat makan siang standar</p>
                            </div>
                        </div>
                        
                        <!-- Attendance Rules -->
                        <div class="space-y-4">
                            <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Aturan Absensi</h4>
                            
                            <div>
                                <label for="late_threshold" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Ambang Batas Terlambat (menit)</label>
                                <input type="number" name="late_threshold" id="late_threshold" value="15" min="1" max="60" 
                                       class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200" />
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Menit setelah waktu mulai untuk ditandai sebagai terlambat</p>
                            </div>
                            
                            <div>
                                <label for="early_checkout_threshold" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Ambang Batas Pulang Awal (menit)</label>
                                <input type="number" name="early_checkout_threshold" id="early_checkout_threshold" value="30" min="1" max="120" 
                                       class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200" />
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Menit sebelum waktu selesai untuk ditandai sebagai pulang awal</p>
                            </div>
                            
                            <div>
                                <label for="overtime_threshold" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Minimum Lembur (menit)</label>
                                <input type="number" name="overtime_threshold" id="overtime_threshold" value="30" min="15" max="120" 
                                       class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200" />
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Minimum menit kerja ekstra untuk dihitung sebagai lembur</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Attendance Options -->
                    <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                        <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Opsi Absensi</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <label class="flex items-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors duration-200">
                                <input type="checkbox" name="require_face_recognition" value="1" checked class="w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600" />
                                <div class="ml-3">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">Wajibkan Pengenalan Wajah</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">Verifikasi identitas dengan deteksi wajah</div>
                                </div>
                            </label>
                            
                            <label class="flex items-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors duration-200">
                                <input type="checkbox" name="require_gps_verification" value="1" checked class="w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600" />
                                <div class="ml-3">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">Wajibkan Verifikasi GPS</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">Pastikan absensi dilakukan di lokasi yang tepat</div>
                                </div>
                            </label>
                            
                            <label class="flex items-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors duration-200">
                                <input type="checkbox" name="allow_manual_attendance" value="1" class="w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600" />
                                <div class="ml-3">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">Izinkan Absensi Manual</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">Admin dapat menambah absensi secara manual</div>
                                </div>
                            </label>
                            
                            <label class="flex items-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors duration-200">
                                <input type="checkbox" name="auto_checkout_enabled" value="1" class="w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600" />
                                <div class="ml-3">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">Auto Check-out</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">Otomatis checkout di akhir jam kerja</div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
            </x-ui.card>
        </div>
        <!-- Biometric Settings Tab -->
        <div x-show="activeTab === 'biometric'" x-transition class="space-y-6">
            <!-- Face Recognition Settings -->
            <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2 flex items-center">
                        <svg class="w-6 h-6 mr-3 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        Pengaturan Pengenalan Wajah
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400">Konfigurasi parameter deteksi dan verifikasi wajah</p>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Detection Parameters -->
                        <div class="space-y-4">
                            <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Parameter Deteksi</h4>
                            
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
                                    class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200"
                                />
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Tingkat kepercayaan minimum untuk pengenalan wajah (0.5-1.0)</p>
                            </div>
                            
                            <div>
                                <label for="max_face_distance" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Jarak Wajah Maksimum</label>
                                <input
                                    type="number"
                                    name="max_face_distance"
                                    id="max_face_distance"
                                    value="0.6"
                                    step="0.01"
                                    min="0.1"
                                    max="1.0"
                                    class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200"
                                />
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Jarak maksimum antara wajah untuk pencocokan</p>
                            </div>
                            
                            <div>
                                <label for="face_detection_model" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Model Deteksi Wajah</label>
                                <select name="face_detection_model" id="face_detection_model" class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200">
                                    <option value="face-api" selected>Face-API.js (Akurat)</option>
                                    <option value="mediapipe">MediaPipe (Cepat)</option>
                                    <option value="opencv">OpenCV (Kompatibel)</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Anti-Spoofing Settings -->
                        <div class="space-y-4">
                            <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Anti-Spoofing</h4>
                            
                            <div>
                                <label for="required_gestures" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Gerakan yang Diperlukan</label>
                                <select name="required_gestures[]" id="required_gestures" multiple class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200">
                                    <option value="blink">Kedipan Mata</option>
                                    <option value="smile">Senyum</option>
                                    <option value="head_nod">Anggukan Kepala</option>
                                    <option value="head_shake">Goyangan Kepala</option>
                                </select>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Pilih gerakan yang diperlukan untuk verifikasi (Ctrl+Click untuk multiple)</p>
                            </div>
                            
                            <div>
                                <label for="gesture_timeout" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Batas Waktu Gerakan (detik)</label>
                                <input
                                    type="number"
                                    name="gesture_timeout"
                                    id="gesture_timeout"
                                    value="10"
                                    min="5"
                                    max="30"
                                    class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200"
                                />
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Batas waktu untuk menyelesaikan gerakan</p>
                            </div>
                            
                            <div>
                                <label for="liveness_check_intensity" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Intensitas Liveness Check</label>
                                <select name="liveness_check_intensity" id="liveness_check_intensity" class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200">
                                    <option value="low">Rendah (User-friendly)</option>
                                    <option value="medium" selected>Sedang (Balanced)</option>
                                    <option value="high">Tinggi (Maximum Security)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </x-ui.card>
            
            <!-- GPS & Location Settings -->
            <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2 flex items-center">
                        <svg class="w-6 h-6 mr-3 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Pengaturan GPS & Lokasi
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400">Konfigurasi verifikasi lokasi dan parameter GPS</p>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <div>
                                <label for="gps_accuracy_threshold" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Ambang Batas Akurasi GPS (meter)</label>
                                <input
                                    type="number"
                                    name="gps_accuracy_threshold"
                                    id="gps_accuracy_threshold"
                                    value="50"
                                    min="10"
                                    max="500"
                                    class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200"
                                />
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Akurasi GPS maksimum yang diperlukan untuk check-in</p>
                            </div>
                            
                            <div>
                                <label for="location_radius" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Radius Lokasi (meter)</label>
                                <input
                                    type="number"
                                    name="location_radius"
                                    id="location_radius"
                                    value="100"
                                    min="25"
                                    max="1000"
                                    class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200"
                                />
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Jarak yang diizinkan dari lokasi kantor</p>
                            </div>
                        </div>
                        
                        <div class="space-y-4">
                            <label class="flex items-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors duration-200">
                                <input type="checkbox" name="allow_remote_checkin" value="1" class="w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600" />
                                <div class="ml-3">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">Izinkan Check-in Jarak Jauh</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">Memungkinkan absensi dari luar area kantor</div>
                                </div>
                            </label>
                            
                            <label class="flex items-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors duration-200">
                                <input type="checkbox" name="log_gps_coordinates" value="1" checked class="w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600" />
                                <div class="ml-3">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">Catat Koordinat GPS</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">Simpan lokasi GPS untuk setiap absensi</div>
                                </div>
                            </label>
                            
                            <label class="flex items-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors duration-200">
                                <input type="checkbox" name="enable_geofencing" value="1" class="w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600" />
                                <div class="ml-3">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">Aktifkan Geofencing</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">Deteksi otomatis saat masuk/keluar area kantor</div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
            </x-ui.card>
        </div>
                
                
        <!-- Notifications Tab -->
        <div x-show="activeTab === 'notifications'" x-transition class="space-y-6">
            <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2 flex items-center">
                        <svg class="w-6 h-6 mr-3 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/>
                        </svg>
                        Pengaturan Notifikasi
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400">Konfigurasi sistem notifikasi dan peringatan</p>
                </div>
                <div class="p-6">
                    <div class="space-y-6">
                        <!-- Email Notifications -->
                        <div>
                            <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Notifikasi Email</h4>
                            <div class="space-y-4">
                                <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-900 dark:text-white">Notifikasi Kedatangan Terlambat</h4>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Beritahu manajer ketika karyawan terlambat</p>
                                    </div>
                                    <label class="flex items-center">
                                        <input type="checkbox" name="notify_late_arrivals" value="1" checked class="w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600" />
                                    </label>
                                </div>
                                
                                <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-900 dark:text-white">Notifikasi Lupa Check-out</h4>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Beritahu karyawan yang lupa check-out</p>
                                    </div>
                                    <label class="flex items-center">
                                        <input type="checkbox" name="notify_missed_checkout" value="1" checked class="w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600" />
                                    </label>
                                </div>
                                
                                <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-900 dark:text-white">Laporan Mingguan</h4>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Kirim ringkasan absensi mingguan</p>
                                    </div>
                                    <label class="flex items-center">
                                        <input type="checkbox" name="send_weekly_reports" value="1" class="w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600" />
                                    </label>
                                </div>
                                
                                <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-900 dark:text-white">Notifikasi Email</h4>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Kirim notifikasi melalui email</p>
                                    </div>
                                    <label class="flex items-center">
                                        <input type="checkbox" name="email_notifications" value="1" checked class="w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600" />
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Push Notifications -->
                        <div>
                            <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Notifikasi Push</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="notification_sound" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Suara Notifikasi</label>
                                    <select name="notification_sound" id="notification_sound" class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200">
                                        <option value="default">Default</option>
                                        <option value="chime">Chime</option>
                                        <option value="bell">Bell</option>
                                        <option value="none">Tanpa Suara</option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label for="notification_frequency" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Frekuensi Notifikasi</label>
                                    <select name="notification_frequency" id="notification_frequency" class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200">
                                        <option value="immediately">Segera</option>
                                        <option value="hourly">Setiap Jam</option>
                                        <option value="daily">Harian</option>
                                        <option value="weekly">Mingguan</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </x-ui.card>
        </div>
                
        <!-- Security Tab -->
        <div x-show="activeTab === 'security'" x-transition class="space-y-6">
            <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2 flex items-center">
                        <svg class="w-6 h-6 mr-3 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                        Pengaturan Keamanan
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400">Konfigurasi kebijakan keamanan dan akses sistem</p>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Security Policies -->
                        <div class="space-y-4">
                            <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Kebijakan Keamanan</h4>
                            
                            <div>
                                <label for="session_timeout" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Session Timeout (menit)</label>
                                <input type="number" name="session_timeout" id="session_timeout" value="60" min="15" max="480" 
                                       class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200" />
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Waktu tidak aktif sebelum logout otomatis</p>
                            </div>
                            
                            <div>
                                <label for="max_login_attempts" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Maksimal Percobaan Login</label>
                                <input type="number" name="max_login_attempts" id="max_login_attempts" value="5" min="3" max="10" 
                                       class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200" />
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Jumlah percobaan login sebelum akun dikunci</p>
                            </div>
                            
                            <div>
                                <label for="lockout_duration" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Durasi Lockout (menit)</label>
                                <input type="number" name="lockout_duration" id="lockout_duration" value="30" min="5" max="1440" 
                                       class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200" />
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Durasi akun dikunci setelah percobaan login gagal</p>
                            </div>
                        </div>
                        
                        <!-- Security Features -->
                        <div class="space-y-4">
                            <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Fitur Keamanan</h4>
                            
                            <label class="flex items-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors duration-200">
                                <input type="checkbox" name="require_2fa" value="1" class="w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600" />
                                <div class="ml-3">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">Wajibkan 2FA</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">Autentikasi dua faktor untuk semua pengguna</div>
                                </div>
                            </label>
                            
                            <label class="flex items-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors duration-200">
                                <input type="checkbox" name="log_all_activities" value="1" checked class="w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600" />
                                <div class="ml-3">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">Log Semua Aktivitas</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">Catat semua aktivitas pengguna untuk audit</div>
                                </div>
                            </label>
                            
                            <label class="flex items-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors duration-200">
                                <input type="checkbox" name="encrypt_sensitive_data" value="1" checked class="w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600" />
                                <div class="ml-3">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">Enkripsi Data Sensitif</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">Enkripsi data biometrik dan personal</div>
                                </div>
                            </label>
                            
                            <label class="flex items-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors duration-200">
                                <input type="checkbox" name="enable_ip_whitelist" value="1" class="w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600" />
                                <div class="ml-3">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">IP Whitelist</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">Batasi akses hanya dari IP tertentu</div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
            </x-ui.card>
        </div>

        <!-- Maintenance Tab -->
        <div x-show="activeTab === 'maintenance'" x-transition class="space-y-6">
            <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2 flex items-center">
                        <svg class="w-6 h-6 mr-3 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Pengaturan Maintenance
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400">Konfigurasi pemeliharaan sistem dan retensi data</p>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Data Retention -->
                        <div class="space-y-4">
                            <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Retensi Data</h4>
                            
                            <div>
                                <label for="attendance_retention_period" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Retensi Data Absensi</label>
                                <select name="attendance_retention_period" id="attendance_retention_period" class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200">
                                    <option value="90">3 Bulan</option>
                                    <option value="180">6 Bulan</option>
                                    <option value="365" selected>1 Tahun</option>
                                    <option value="730">2 Tahun</option>
                                    <option value="0">Simpan Selamanya</option>
                                </select>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Berapa lama catatan absensi disimpan</p>
                            </div>
                            
                            <div>
                                <label for="face_data_retention_period" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Retensi Data Wajah</label>
                                <select name="face_data_retention_period" id="face_data_retention_period" class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200">
                                    <option value="30">1 Bulan</option>
                                    <option value="90" selected>3 Bulan</option>
                                    <option value="180">6 Bulan</option>
                                    <option value="365">1 Tahun</option>
                                </select>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Berapa lama data pengenalan wajah disimpan</p>
                            </div>
                            
                            <div>
                                <label for="log_retention_period" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Retensi Log Sistem</label>
                                <select name="log_retention_period" id="log_retention_period" class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200">
                                    <option value="30">1 Bulan</option>
                                    <option value="90" selected>3 Bulan</option>
                                    <option value="180">6 Bulan</option>
                                    <option value="365">1 Tahun</option>
                                </select>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Berapa lama log sistem disimpan</p>
                            </div>
                        </div>
                        
                        <!-- Maintenance Schedule -->
                        <div class="space-y-4">
                            <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Jadwal Maintenance</h4>
                            
                            <div>
                                <label for="cleanup_time" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Waktu Pembersihan Harian</label>
                                <input
                                    type="time"
                                    name="cleanup_time"
                                    id="cleanup_time"
                                    value="02:00"
                                    class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200"
                                />
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Kapan menjalankan tugas pembersihan harian</p>
                            </div>
                            
                            <div>
                                <label for="backup_frequency" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Frekuensi Backup</label>
                                <select name="backup_frequency" id="backup_frequency" class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200">
                                    <option value="daily" selected>Harian</option>
                                    <option value="weekly">Mingguan</option>
                                    <option value="monthly">Bulanan</option>
                                    <option value="manual">Manual</option>
                                </select>
                            </div>
                            
                            <div>
                                <label for="maintenance_window" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Jendela Maintenance</label>
                                <select name="maintenance_window" id="maintenance_window" class="w-full px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200">
                                    <option value="01:00-05:00" selected>01:00 - 05:00</option>
                                    <option value="22:00-02:00">22:00 - 02:00</option>
                                    <option value="weekend">Weekend Saja</option>
                                    <option value="custom">Kustom</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </x-ui.card>
        </div>
                
        <!-- Form Actions -->
        <div class="flex items-center justify-end space-x-3 pt-6 border-t border-gray-200 dark:border-gray-700">
            <a
                href="{{ route('dashboard') }}"
                class="px-6 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg font-medium transition-all duration-200 transform hover:scale-105"
            >
                <svg class="w-5 h-5 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                Batal
            </a>
            
            <button
                type="submit"
                class="px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white rounded-lg font-medium transition-all duration-200 transform hover:scale-105 shadow-md"
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
</div>
@endsection

@push('scripts')
<script>
function systemSettings() {
    return {
        activeTab: 'general',
        
        testConfiguration() {
            // Test current configuration
            alert('Testing system configuration...');
            // In real implementation, this would run configuration tests
        },
        
        resetToDefaults() {
            if (confirm('Apakah Anda yakin ingin mereset semua pengaturan ke nilai default?')) {
                // Reset form to default values
                const form = document.getElementById('settings-form');
                const inputs = form.querySelectorAll('input, select');
                
                inputs.forEach(input => {
                    if (input.type === 'checkbox') {
                        input.checked = input.hasAttribute('data-default-checked');
                    } else {
                        const defaultValue = input.getAttribute('data-default-value');
                        if (defaultValue) {
                            input.value = defaultValue;
                        }
                    }
                });
                
                alert('Pengaturan telah direset ke nilai default!');
            }
        }
    }
}

// Settings form enhancements
document.addEventListener('DOMContentLoaded', function() {
    // Auto-save draft settings to localStorage
    const form = document.querySelector('#settings-form');
    if (form) {
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
    }
});
</script>
@endpush
