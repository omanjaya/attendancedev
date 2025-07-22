@extends('layouts.authenticated-unified')

@section('title', 'Pengaturan Sistem')

@section('page-content')
<div x-data="systemSettings()">
    <!-- Modern Page Header with Enhanced Actions -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Pengaturan Sistem</h1>
                <p class="settings-page-desc">Konfigurasi dan kelola pengaturan sistem manajemen absensi</p>
            </div>
            <div class="flex items-center space-x-3">
                <!-- Test Configuration Button -->
                <button @click="testConfiguration()" class="btn-test">
                    <x-icons.check class="w-5 h-5 mr-2 inline" />
                    Test Config
                </button>
                
                <!-- Reset to Defaults Button -->
                <button @click="resetToDefaults()" class="btn-reset">
                    <x-icons.refresh class="w-5 h-5 mr-2 inline" />
                    Reset
                </button>
                
                <!-- Save Settings Button -->
                <button type="submit" form="settings-form" class="btn-save-gradient">
                    <x-icons.check />
                    Simpan Pengaturan
                </button>
            </div>
        </div>
    </div>
        
    @can('manage_system_settings')
    <!-- Settings Navigation Tabs -->
    <div class="mb-8">
        <div class="settings-tab-container">
            <button @click="activeTab = 'general'" 
                    :class="activeTab === 'general' ? 'settings-tab-active' : ''" 
                    class="settings-tab-btn">
                Umum
            </button>
            <button @click="activeTab = 'attendance'" 
                    :class="activeTab === 'attendance' ? 'settings-tab-active' : ''" 
                    class="settings-tab-btn">
                Absensi
            </button>
            <button @click="activeTab = 'biometric'" 
                    :class="activeTab === 'biometric' ? 'settings-tab-active' : ''" 
                    class="settings-tab-btn">
                Biometrik
            </button>
            <button @click="activeTab = 'notifications'" 
                    :class="activeTab === 'notifications' ? 'settings-tab-active' : ''" 
                    class="settings-tab-btn">
                Notifikasi
            </button>
            <button @click="activeTab = 'security'" 
                    :class="activeTab === 'security' ? 'settings-tab-active' : ''" 
                    class="settings-tab-btn">
                Keamanan
            </button>
            <button @click="activeTab = 'maintenance'" 
                    :class="activeTab === 'maintenance' ? 'settings-tab-active' : ''" 
                    class="settings-tab-btn">
                Maintenance
            </button>
        </div>
    </div>

    <form id="settings-form" action="{{ route('system.settings.update') }}" method="POST" class="space-y-8">
        @csrf
        @method('PUT')
                
        <!-- General Settings Tab -->
        <div x-show="activeTab === 'general'" x-transition class="space-y-6">
            <x-ui.card class="settings-card">
                <div class="settings-section-header">
                    <h3 class="settings-section-title">
                        <x-icons.cog class="w-6 h-6 mr-3 text-blue-600" />
                        </svg>
                        Pengaturan Umum
                    </h3>
                    <p class="settings-section-desc">Konfigurasi sistem dasar dan preferensi regional</p>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- System Information -->
                        <div class="space-y-4">
                            <div>
                                <label for="system_name" class="settings-form-label">Nama Sistem</label>
                                <input 
                                    type="text"
                                    name="system_name"
                                    id="system_name"
                                    value="{{ config('app.name', 'AttendanceHub') }}"
                                    placeholder="AttendanceHub" 
                                    class="form-control" />
                                <p class="settings-help-text">Nama organisasi atau institusi Anda</p>
                            </div>
                            
                            <div>
                                <label for="organization_type" class="settings-form-label">Jenis Organisasi</label>
                                <select name="organization_type" id="organization_type" class="form-control">
                                    <option value="school">Sekolah</option>
                                    <option value="company">Perusahaan</option>
                                    <option value="government">Pemerintahan</option>
                                    <option value="nonprofit">Non-Profit</option>
                                    <option value="other">Lainnya</option>
                                </select>
                            </div>
                            
                            <div>
                                <label for="language" class="settings-form-label">Bahasa Sistem</label>
                                <select name="language" id="language" class="form-control">
                                    <option value="id" selected>Bahasa Indonesia</option>
                                    <option value="en">English</option>
                                    <option value="ms">Bahasa Malaysia</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Regional Settings -->
                        <div class="space-y-4">
                            <div>
                                <label for="timezone" class="settings-form-label">Zona Waktu Sistem</label>
                                <select name="timezone" id="timezone" class="form-control">
                                    <option value="Asia/Jakarta" selected>Jakarta (WIB, UTC+7)</option>
                                    <option value="Asia/Makassar">Makassar (WITA, UTC+8)</option>
                                    <option value="Asia/Jayapura">Jayapura (WIT, UTC+9)</option>
                                    <option value="UTC">UTC (Waktu Universal)</option>
                                    <option value="Asia/Kuala_Lumpur">Kuala Lumpur (UTC+8)</option>
                                    <option value="Asia/Singapore">Singapore (UTC+8)</option>
                                </select>
                            </div>
                            
                            <div>
                                <label for="date_format" class="settings-form-label">Format Tanggal</label>
                                <select name="date_format" id="date_format" class="form-control">
                                    <option value="d/m/Y" selected>15/01/2024 (DD/MM/YYYY)</option>
                                    <option value="Y-m-d">2024-01-15 (YYYY-MM-DD)</option>
                                    <option value="m/d/Y">01/15/2024 (MM/DD/YYYY)</option>
                                    <option value="j F Y">15 Januari 2024</option>
                                </select>
                            </div>
                            
                            <div>
                                <label for="time_format" class="settings-form-label">Format Waktu</label>
                                <select name="time_format" id="time_format" class="form-control">
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
            <x-ui.card class="settings-card">
                <div class="settings-section-header">
                    <h3 class="settings-section-title">
                        <x-icons.check-circle class="w-6 h-6 mr-3 text-green-600" />
                        Pengaturan Absensi
                    </h3>
                    <p class="settings-section-desc">Konfigurasi aturan dan kebijakan pelacakan absensi</p>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Working Hours -->
                        <div class="space-y-4">
                            <h4 class="settings-subsection-title">Jam Kerja</h4>
                            
                            <div>
                                <label for="default_work_start" class="settings-form-label">Waktu Mulai Kerja Default</label>
                                <input type="time" name="default_work_start" id="default_work_start" value="08:00" 
                                       class="form-control" />
                                <p class="settings-help-text">Waktu mulai hari kerja standar</p>
                            </div>
                            
                            <div>
                                <label for="default_work_end" class="settings-form-label">Waktu Selesai Kerja Default</label>
                                <input type="time" name="default_work_end" id="default_work_end" value="17:00" 
                                       class="form-control" />
                                <p class="settings-help-text">Waktu selesai hari kerja standar</p>
                            </div>
                            
                            <div>
                                <label for="break_duration" class="settings-form-label">Durasi Istirahat (menit)</label>
                                <input type="number" name="break_duration" id="break_duration" value="60" min="30" max="120" 
                                       class="form-control" />
                                <p class="settings-help-text">Durasi istirahat makan siang standar</p>
                            </div>
                        </div>
                        
                        <!-- Attendance Rules -->
                        <div class="space-y-4">
                            <h4 class="settings-subsection-title">Aturan Absensi</h4>
                            
                            <div>
                                <label for="late_threshold" class="settings-form-label">Ambang Batas Terlambat (menit)</label>
                                <input type="number" name="late_threshold" id="late_threshold" value="15" min="1" max="60" 
                                       class="form-control" />
                                <p class="settings-help-text">Menit setelah waktu mulai untuk ditandai sebagai terlambat</p>
                            </div>
                            
                            <div>
                                <label for="early_checkout_threshold" class="settings-form-label">Ambang Batas Pulang Awal (menit)</label>
                                <input type="number" name="early_checkout_threshold" id="early_checkout_threshold" value="30" min="1" max="120" 
                                       class="form-control" />
                                <p class="settings-help-text">Menit sebelum waktu selesai untuk ditandai sebagai pulang awal</p>
                            </div>
                            
                            <div>
                                <label for="overtime_threshold" class="settings-form-label">Minimum Lembur (menit)</label>
                                <input type="number" name="overtime_threshold" id="overtime_threshold" value="30" min="15" max="120" 
                                       class="form-control" />
                                <p class="settings-help-text">Minimum menit kerja ekstra untuk dihitung sebagai lembur</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Attendance Options -->
                    <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                        <h4 class="settings-subsection-title">Opsi Absensi</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <label class="flex items-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors duration-200">
                                <input type="checkbox" name="require_face_recognition" value="1" checked class="w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600" />
                                <div class="ml-3">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">Wajibkan Pengenalan Wajah</div>
                                    <div class="settings-info-text">Verifikasi identitas dengan deteksi wajah</div>
                                </div>
                            </label>
                            
                            <label class="flex items-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors duration-200">
                                <input type="checkbox" name="require_gps_verification" value="1" checked class="w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600" />
                                <div class="ml-3">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">Wajibkan Verifikasi GPS</div>
                                    <div class="settings-info-text">Pastikan absensi dilakukan di lokasi yang tepat</div>
                                </div>
                            </label>
                            
                            <label class="flex items-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors duration-200">
                                <input type="checkbox" name="allow_manual_attendance" value="1" class="w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600" />
                                <div class="ml-3">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">Izinkan Absensi Manual</div>
                                    <div class="settings-info-text">Admin dapat menambah absensi secara manual</div>
                                </div>
                            </label>
                            
                            <label class="flex items-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors duration-200">
                                <input type="checkbox" name="auto_checkout_enabled" value="1" class="w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600" />
                                <div class="ml-3">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">Auto Check-out</div>
                                    <div class="settings-info-text">Otomatis checkout di akhir jam kerja</div>
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
            <x-ui.card class="settings-card">
                <div class="settings-section-header">
                    <h3 class="settings-section-title">
                        <x-icons.user class="w-6 h-6 mr-3 text-purple-600" />
                        Pengaturan Pengenalan Wajah
                    </h3>
                    <p class="settings-section-desc">Konfigurasi parameter deteksi dan verifikasi wajah</p>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Detection Parameters -->
                        <div class="space-y-4">
                            <h4 class="settings-subsection-title">Parameter Deteksi</h4>
                            
                            <div>
                                <label for="face_confidence_threshold" class="settings-form-label">Ambang Batas Kepercayaan Wajah</label>
                                <input
                                    type="number"
                                    name="face_confidence_threshold"
                                    id="face_confidence_threshold"
                                    value="0.85"
                                    step="0.01"
                                    min="0.5"
                                    max="1.0"
                                    class="form-control"
                                />
                                <p class="settings-help-text">Tingkat kepercayaan minimum untuk pengenalan wajah (0.5-1.0)</p>
                            </div>
                            
                            <div>
                                <label for="max_face_distance" class="settings-form-label">Jarak Wajah Maksimum</label>
                                <input
                                    type="number"
                                    name="max_face_distance"
                                    id="max_face_distance"
                                    value="0.6"
                                    step="0.01"
                                    min="0.1"
                                    max="1.0"
                                    class="form-control"
                                />
                                <p class="settings-help-text">Jarak maksimum antara wajah untuk pencocokan</p>
                            </div>
                            
                            <div>
                                <label for="face_detection_model" class="settings-form-label">Model Deteksi Wajah</label>
                                <select name="face_detection_model" id="face_detection_model" class="form-control">
                                    <option value="face-api" selected>Face-API.js (Akurat)</option>
                                    <option value="mediapipe">MediaPipe (Cepat)</option>
                                    <option value="opencv">OpenCV (Kompatibel)</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Anti-Spoofing Settings -->
                        <div class="space-y-4">
                            <h4 class="settings-subsection-title">Anti-Spoofing</h4>
                            
                            <div>
                                <label for="required_gestures" class="settings-form-label">Gerakan yang Diperlukan</label>
                                <select name="required_gestures[]" id="required_gestures" multiple class="form-control">
                                    <option value="blink">Kedipan Mata</option>
                                    <option value="smile">Senyum</option>
                                    <option value="head_nod">Anggukan Kepala</option>
                                    <option value="head_shake">Goyangan Kepala</option>
                                </select>
                                <p class="settings-help-text">Pilih gerakan yang diperlukan untuk verifikasi (Ctrl+Click untuk multiple)</p>
                            </div>
                            
                            <div>
                                <label for="gesture_timeout" class="settings-form-label">Batas Waktu Gerakan (detik)</label>
                                <input
                                    type="number"
                                    name="gesture_timeout"
                                    id="gesture_timeout"
                                    value="10"
                                    min="5"
                                    max="30"
                                    class="form-control"
                                />
                                <p class="settings-help-text">Batas waktu untuk menyelesaikan gerakan</p>
                            </div>
                            
                            <div>
                                <label for="liveness_check_intensity" class="settings-form-label">Intensitas Liveness Check</label>
                                <select name="liveness_check_intensity" id="liveness_check_intensity" class="form-control">
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
            <x-ui.card class="settings-card">
                <div class="settings-section-header">
                    <h3 class="settings-section-title">
                        <x-icons.location class="w-6 h-6 mr-3 text-orange-600" />
                        Pengaturan GPS & Lokasi
                    </h3>
                    <p class="settings-section-desc">Konfigurasi verifikasi lokasi dan parameter GPS</p>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <div>
                                <label for="gps_accuracy_threshold" class="settings-form-label">Ambang Batas Akurasi GPS (meter)</label>
                                <input
                                    type="number"
                                    name="gps_accuracy_threshold"
                                    id="gps_accuracy_threshold"
                                    value="50"
                                    min="10"
                                    max="500"
                                    class="form-control"
                                />
                                <p class="settings-help-text">Akurasi GPS maksimum yang diperlukan untuk check-in</p>
                            </div>
                            
                            <div>
                                <label for="location_radius" class="settings-form-label">Radius Lokasi (meter)</label>
                                <input
                                    type="number"
                                    name="location_radius"
                                    id="location_radius"
                                    value="100"
                                    min="25"
                                    max="1000"
                                    class="form-control"
                                />
                                <p class="settings-help-text">Jarak yang diizinkan dari lokasi kantor</p>
                            </div>
                        </div>
                        
                        <div class="space-y-4">
                            <label class="flex items-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors duration-200">
                                <input type="checkbox" name="allow_remote_checkin" value="1" class="w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600" />
                                <div class="ml-3">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">Izinkan Check-in Jarak Jauh</div>
                                    <div class="settings-info-text">Memungkinkan absensi dari luar area kantor</div>
                                </div>
                            </label>
                            
                            <label class="flex items-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors duration-200">
                                <input type="checkbox" name="log_gps_coordinates" value="1" checked class="w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600" />
                                <div class="ml-3">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">Catat Koordinat GPS</div>
                                    <div class="settings-info-text">Simpan lokasi GPS untuk setiap absensi</div>
                                </div>
                            </label>
                            
                            <label class="flex items-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors duration-200">
                                <input type="checkbox" name="enable_geofencing" value="1" class="w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600" />
                                <div class="ml-3">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">Aktifkan Geofencing</div>
                                    <div class="settings-info-text">Deteksi otomatis saat masuk/keluar area kantor</div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
            </x-ui.card>
        </div>
                
                
        <!-- Notifications Tab -->
        <div x-show="activeTab === 'notifications'" x-transition class="space-y-6">
            <x-ui.card class="settings-card">
                <div class="settings-section-header">
                    <h3 class="settings-section-title">
                        <x-icons.chart class="w-6 h-6 mr-3 text-yellow-600" />
                        Pengaturan Notifikasi
                    </h3>
                    <p class="settings-section-desc">Konfigurasi sistem notifikasi dan peringatan</p>
                </div>
                <div class="p-6">
                    <div class="space-y-6">
                        <!-- Email Notifications -->
                        <div>
                            <h4 class="settings-subsection-title">Notifikasi Email</h4>
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
                            <h4 class="settings-subsection-title">Notifikasi Push</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="notification_sound" class="settings-form-label">Suara Notifikasi</label>
                                    <select name="notification_sound" id="notification_sound" class="form-control">
                                        <option value="default">Default</option>
                                        <option value="chime">Chime</option>
                                        <option value="bell">Bell</option>
                                        <option value="none">Tanpa Suara</option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label for="notification_frequency" class="settings-form-label">Frekuensi Notifikasi</label>
                                    <select name="notification_frequency" id="notification_frequency" class="form-control">
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
            <x-ui.card class="settings-card">
                <div class="settings-section-header">
                    <h3 class="settings-section-title">
                        <x-icons.shield class="w-6 h-6 mr-3 text-red-600" />
                        Pengaturan Keamanan
                    </h3>
                    <p class="settings-section-desc">Konfigurasi kebijakan keamanan dan akses sistem</p>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Security Policies -->
                        <div class="space-y-4">
                            <h4 class="settings-subsection-title">Kebijakan Keamanan</h4>
                            
                            <div>
                                <label for="session_timeout" class="settings-form-label">Session Timeout (menit)</label>
                                <input type="number" name="session_timeout" id="session_timeout" value="60" min="15" max="480" 
                                       class="form-control" />
                                <p class="settings-help-text">Waktu tidak aktif sebelum logout otomatis</p>
                            </div>
                            
                            <div>
                                <label for="max_login_attempts" class="settings-form-label">Maksimal Percobaan Login</label>
                                <input type="number" name="max_login_attempts" id="max_login_attempts" value="5" min="3" max="10" 
                                       class="form-control" />
                                <p class="settings-help-text">Jumlah percobaan login sebelum akun dikunci</p>
                            </div>
                            
                            <div>
                                <label for="lockout_duration" class="settings-form-label">Durasi Lockout (menit)</label>
                                <input type="number" name="lockout_duration" id="lockout_duration" value="30" min="5" max="1440" 
                                       class="form-control" />
                                <p class="settings-help-text">Durasi akun dikunci setelah percobaan login gagal</p>
                            </div>
                        </div>
                        
                        <!-- Security Features -->
                        <div class="space-y-4">
                            <h4 class="settings-subsection-title">Fitur Keamanan</h4>
                            
                            <label class="flex items-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors duration-200">
                                <input type="checkbox" name="require_2fa" value="1" class="w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600" />
                                <div class="ml-3">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">Wajibkan 2FA</div>
                                    <div class="settings-info-text">Autentikasi dua faktor untuk semua pengguna</div>
                                </div>
                            </label>
                            
                            <label class="flex items-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors duration-200">
                                <input type="checkbox" name="log_all_activities" value="1" checked class="w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600" />
                                <div class="ml-3">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">Log Semua Aktivitas</div>
                                    <div class="settings-info-text">Catat semua aktivitas pengguna untuk audit</div>
                                </div>
                            </label>
                            
                            <label class="flex items-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors duration-200">
                                <input type="checkbox" name="encrypt_sensitive_data" value="1" checked class="w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600" />
                                <div class="ml-3">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">Enkripsi Data Sensitif</div>
                                    <div class="settings-info-text">Enkripsi data biometrik dan personal</div>
                                </div>
                            </label>
                            
                            <label class="flex items-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors duration-200">
                                <input type="checkbox" name="enable_ip_whitelist" value="1" class="w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600" />
                                <div class="ml-3">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">IP Whitelist</div>
                                    <div class="settings-info-text">Batasi akses hanya dari IP tertentu</div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
            </x-ui.card>
        </div>

        <!-- Maintenance Tab -->
        <div x-show="activeTab === 'maintenance'" x-transition class="space-y-6">
            <x-ui.card class="settings-card">
                <div class="settings-section-header">
                    <h3 class="settings-section-title">
                        <x-icons.cog class="w-6 h-6 mr-3 text-gray-600" />
                        Pengaturan Maintenance
                    </h3>
                    <p class="settings-section-desc">Konfigurasi pemeliharaan sistem dan retensi data</p>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Data Retention -->
                        <div class="space-y-4">
                            <h4 class="settings-subsection-title">Retensi Data</h4>
                            
                            <div>
                                <label for="attendance_retention_period" class="settings-form-label">Retensi Data Absensi</label>
                                <select name="attendance_retention_period" id="attendance_retention_period" class="form-control">
                                    <option value="90">3 Bulan</option>
                                    <option value="180">6 Bulan</option>
                                    <option value="365" selected>1 Tahun</option>
                                    <option value="730">2 Tahun</option>
                                    <option value="0">Simpan Selamanya</option>
                                </select>
                                <p class="settings-help-text">Berapa lama catatan absensi disimpan</p>
                            </div>
                            
                            <div>
                                <label for="face_data_retention_period" class="settings-form-label">Retensi Data Wajah</label>
                                <select name="face_data_retention_period" id="face_data_retention_period" class="form-control">
                                    <option value="30">1 Bulan</option>
                                    <option value="90" selected>3 Bulan</option>
                                    <option value="180">6 Bulan</option>
                                    <option value="365">1 Tahun</option>
                                </select>
                                <p class="settings-help-text">Berapa lama data pengenalan wajah disimpan</p>
                            </div>
                            
                            <div>
                                <label for="log_retention_period" class="settings-form-label">Retensi Log Sistem</label>
                                <select name="log_retention_period" id="log_retention_period" class="form-control">
                                    <option value="30">1 Bulan</option>
                                    <option value="90" selected>3 Bulan</option>
                                    <option value="180">6 Bulan</option>
                                    <option value="365">1 Tahun</option>
                                </select>
                                <p class="settings-help-text">Berapa lama log sistem disimpan</p>
                            </div>
                        </div>
                        
                        <!-- Maintenance Schedule -->
                        <div class="space-y-4">
                            <h4 class="settings-subsection-title">Jadwal Maintenance</h4>
                            
                            <div>
                                <label for="cleanup_time" class="settings-form-label">Waktu Pembersihan Harian</label>
                                <input
                                    type="time"
                                    name="cleanup_time"
                                    id="cleanup_time"
                                    value="02:00"
                                    class="form-control"
                                />
                                <p class="settings-help-text">Kapan menjalankan tugas pembersihan harian</p>
                            </div>
                            
                            <div>
                                <label for="backup_frequency" class="settings-form-label">Frekuensi Backup</label>
                                <select name="backup_frequency" id="backup_frequency" class="form-control">
                                    <option value="daily" selected>Harian</option>
                                    <option value="weekly">Mingguan</option>
                                    <option value="monthly">Bulanan</option>
                                    <option value="manual">Manual</option>
                                </select>
                            </div>
                            
                            <div>
                                <label for="maintenance_window" class="settings-form-label">Jendela Maintenance</label>
                                <select name="maintenance_window" id="maintenance_window" class="form-control">
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
                class="btn-cancel px-6"
            >
                <x-icons.x class="w-5 h-5 mr-2 inline" />
                Batal
            </a>
            
            <button
                type="submit"
                class="btn-save-gradient px-6 py-3"
            >
                <x-icons.check class="w-5 h-5 mr-2 inline" />
                Simpan Pengaturan
            </button>
        </div>
    </form>
        @else
            <div class="settings-warning-container">
                <div class="flex items-center">
                    <svg class="settings-warning-icon" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                    <div>
                        <h3 class="text-xl font-semibold text-amber-800 dark:text-amber-200">Akses Dibatasi</h3>
                        <p class="settings-warning-text">Anda tidak memiliki izin untuk mengakses pengaturan sistem.</p>
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
