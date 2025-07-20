@extends('layouts.authenticated-unified')

@section('title', 'Check-in')

@section('page-content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6" x-data="enhancedAttendanceSystem()" x-init="initializeSystem()">
    
    <!-- Today's Schedule and Status Card -->
    <div class="mb-6">
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Jadwal & Status Hari Ini</h2>
                    <span class="text-sm text-slate-500 dark:text-slate-400" x-text="todayFormatted"></span>
                </div>
                
                <!-- Schedule Information -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Work Schedule -->
                    <div class="space-y-3">
                        <h3 class="text-sm font-medium text-slate-700 dark:text-slate-300 flex items-center">
                            <svg class="w-4 h-4 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Jadwal Kerja
                        </h3>
                        <div x-show="schedule" class="space-y-2">
                            <div class="flex justify-between items-center p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                <span class="text-sm text-slate-600 dark:text-slate-400">Jam Masuk:</span>
                                <span class="text-sm font-medium text-blue-600 dark:text-blue-400" x-text="schedule?.start_time_formatted || '-'"></span>
                            </div>
                            <div class="flex justify-between items-center p-3 bg-orange-50 dark:bg-orange-900/20 rounded-lg">
                                <span class="text-sm text-slate-600 dark:text-slate-400">Jam Keluar:</span>
                                <span class="text-sm font-medium text-orange-600 dark:text-orange-400" x-text="schedule?.end_time_formatted || '-'"></span>
                            </div>
                            <div class="flex justify-between items-center p-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                                <span class="text-sm text-slate-600 dark:text-slate-400">Total Periode:</span>
                                <span class="text-sm font-medium text-purple-600 dark:text-purple-400" x-text="schedule?.periods_count ? schedule.periods_count + ' periode' : '0 periode'"></span>
                            </div>
                        </div>
                        <div x-show="!schedule" class="p-3 bg-slate-100 dark:bg-slate-700 rounded-lg text-center">
                            <span class="text-sm text-slate-500 dark:text-slate-400">Tidak ada jadwal untuk hari ini</span>
                        </div>
                    </div>
                    
                    <!-- Attendance Status -->
                    <div class="space-y-3">
                        <h3 class="text-sm font-medium text-slate-700 dark:text-slate-300 flex items-center">
                            <svg class="w-4 h-4 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Status Kehadiran
                        </h3>
                        <div class="space-y-2">
                            <div class="flex justify-between items-center p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                                <span class="text-sm text-slate-600 dark:text-slate-400">Check-in:</span>
                                <span class="text-sm font-medium" 
                                      :class="attendance?.check_in_time ? 'text-green-600 dark:text-green-400' : 'text-slate-400'"
                                      x-text="attendance?.check_in_time || 'Belum check-in'"></span>
                            </div>
                            <div class="flex justify-between items-center p-3 bg-red-50 dark:bg-red-900/20 rounded-lg">
                                <span class="text-sm text-slate-600 dark:text-slate-400">Check-out:</span>
                                <span class="text-sm font-medium" 
                                      :class="attendance?.check_out_time ? 'text-red-600 dark:text-red-400' : 'text-slate-400'"
                                      x-text="attendance?.check_out_time || 'Belum check-out'"></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Action Buttons -->
                <div class="mt-4 pt-4 border-t border-slate-200 dark:border-slate-600 flex space-x-3">
                    <button @click="refreshScheduleStatus()" 
                            class="inline-flex items-center px-3 py-2 text-sm font-medium text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 transition-colors">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Refresh Status
                    </button>
                    <button x-show="schedule && schedule.periods_count > 0" 
                            @click="showScheduleDetailModal = true"
                            class="inline-flex items-center px-3 py-2 text-sm font-medium text-green-600 dark:text-green-400 hover:text-green-700 dark:hover:text-green-300 transition-colors">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                        Lihat Detail Jadwal
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Overview Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
            <x-ui.card variant="metric" 
                       title="Status Absensi"
                       class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-emerald-500 to-emerald-600 flex items-center justify-center shadow-sm">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium" 
                          x-bind:class="status.badge === 'Working' ? 'bg-success/10 text-success' : 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300'" 
                          x-text="status.badge"></span>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1" x-text="status.title"></h3>
                <p class="text-sm text-muted-foreground" x-text="status.subtitle"></p>
                <div class="mt-4">
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <div class="h-2 bg-gradient-to-r from-emerald-500 to-emerald-600 rounded-full transition-all duration-500"
                             x-bind:style="`width: ${status.progress}%`"></div>
                    </div>
                </div>
            </x-ui.card>

            <x-ui.card variant="metric" 
                       title="Lokasi GPS"
                       class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center shadow-sm">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium" 
                          x-bind:class="location.valid ? 'bg-success/10 text-success' : 'bg-destructive/10 text-destructive'" 
                          x-text="location.status"></span>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">Lokasi GPS</h3>
                <p class="text-sm text-muted-foreground" x-text="location.address"></p>
                <div class="mt-2 flex items-center space-x-2">
                    <span class="text-xs text-muted-foreground">Akurasi: <span x-text="location.accuracy"></span>m</span>
                </div>
            </x-ui.card>

            <x-ui.card variant="metric" 
                       title="Pengenalan Wajah"
                       class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center shadow-sm">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </div>
                    <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium" 
                          x-bind:class="faceRecognition.enrolled ? 'bg-success/10 text-success' : 'bg-warning/10 text-warning'" 
                          x-text="faceRecognition.status"></span>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">Pengenalan Wajah</h3>
                <p class="text-sm text-muted-foreground" x-text="faceRecognition.confidence"></p>
                <div class="mt-2 flex items-center space-x-1">
                    <div class="w-1 h-3 bg-gradient-to-t from-purple-400 to-purple-600 rounded-full animate-pulse" style="animation-delay: 0s;"></div>
                    <div class="w-1 h-4 bg-gradient-to-t from-purple-400 to-purple-600 rounded-full animate-pulse" style="animation-delay: 0.1s;"></div>
                    <div class="w-1 h-3 bg-gradient-to-t from-purple-400 to-purple-600 rounded-full animate-pulse" style="animation-delay: 0.2s;"></div>
                    <div class="w-1 h-4 bg-gradient-to-t from-purple-400 to-purple-600 rounded-full animate-pulse" style="animation-delay: 0.3s;"></div>
                    <span class="text-xs text-muted-foreground ml-2">Siap</span>
                </div>
            </x-ui.card>

            <x-ui.card variant="metric" 
                       title="Kinerja Sistem"
                       class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-indigo-500 to-indigo-600 flex items-center justify-center shadow-sm">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </div>
                    <x-ui.badge color="info">Real-time</x-ui.badge>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">Kinerja Sistem</h3>
                <p class="text-sm text-muted-foreground" x-text="performance.speed"></p>
                <div class="mt-3 grid grid-cols-2 gap-4">
                    <div class="text-center">
                        <div class="text-xl font-bold text-info" x-text="performance.accuracy"></div>
                        <div class="text-xs text-muted-foreground">Akurasi</div>
                    </div>
                    <div class="text-center">
                        <div class="text-xl font-bold text-success" x-text="performance.uptime"></div>
                        <div class="text-xs text-muted-foreground">Uptime</div>
                    </div>
                </div>
            </x-ui.card>
        </div>

        <!-- Camera Interface -->
        <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white flex items-center">
                        <svg class="w-6 h-6 mr-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/></svg>
                        Verifikasi Wajah & GPS
                    </h2>
                    <p class="text-muted-foreground">Aktifkan kamera untuk memulai proses check-in</p>
                </div>
                
                <div class="relative inline-block text-left" x-data="{ open: false }">
                    <x-ui.button type="button" variant="outline" @click="open = !open" class="text-sm">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        </svg>
                        <span x-text="detectionMethod.name"></span>
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </x-ui.button>
                    <div x-show="open" @click.away="open = false" 
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="absolute right-0 mt-2 w-56 bg-white dark:bg-gray-800 rounded-md shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none z-50">
                        <div class="py-1">
                            <button @click="switchDetectionMethod('face-api'); open = false" class="w-full flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                <svg class="w-4 h-4 mr-3 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Face-API.js
                            </button>
                            <button @click="switchDetectionMethod('mediapipe'); open = false" class="w-full flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                <svg class="w-4 h-4 mr-3 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                MediaPipe
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Camera Container -->
            <div class="relative">
                <div class="relative h-80 bg-gray-900 rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700">
                    <video id="video-stream" class="w-full h-full object-cover" x-show="camera.active" autoplay muted playsinline></video>
                    
                    <!-- Camera placeholder when inactive -->
                    <div x-show="!camera.active" class="absolute inset-0 flex items-center justify-center camera-placeholder">
                        <div class="text-center">
                            <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                            </svg>
                            <p class="text-muted-foreground font-medium">Klik "Aktifkan Kamera" untuk memulai</p>
                        </div>
                    </div>
                    
                    <!-- Camera overlay -->
                    <div class="absolute top-0 left-0 right-0 bottom-0 pointer-events-none" x-show="camera.active">
                        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-32 h-32 border-2 border-emerald-500 rounded-full opacity-80 pulse-border"></div>
                    </div>
                    <canvas id="face-overlay" class="absolute top-0 left-0 w-full h-full pointer-events-none" x-show="camera.active"></canvas>
                    <div x-show="camera.active" class="absolute top-4 left-4 right-4">
                        <x-ui.card class="bg-white/95 backdrop-blur-sm border-white/20">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium" 
                                          x-bind:class="detection.status === 'detected' ? 'bg-success/10 text-success' : 'bg-warning/10 text-warning'">
                                        <span x-text="detection.status === 'detected' ? 'Terdeteksi' : 'Mendeteksi'"></span>
                                    </span>
                                    <span class="font-medium text-gray-900" x-text="detection.message"></span>
                                </div>
                                <div class="flex items-center space-x-4 text-sm">
                                    <div>
                                        <span class="text-muted-foreground">Kepercayaan:</span>
                                        <span class="font-semibold text-gray-900" x-text="detection.confidence"></span>
                                    </div>
                                    <div>
                                        <span class="text-muted-foreground">Kehidupan:</span>
                                        <span class="font-semibold text-gray-900" x-text="detection.liveness"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3">
                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                    <div class="h-2 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full transition-all duration-300"
                                         x-bind:style="`width: ${detection.progress}%`"></div>
                                </div>
                            </div>
                        </x-ui.card>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="mt-6 flex flex-wrap gap-3 justify-center">
                    <x-ui.button 
                        @click="startCheckInWorkflow()" 
                        x-bind:disabled="workflow.currentStep > 1"
                        variant="success"
                        size="lg"
                        class="flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span x-text="status.badge === 'Working' ? 'Mulai Check-Out' : 'Mulai Check-In'"></span>
                    </x-ui.button>
                    
                    <button 
                        @click="toggleCamera()" 
                        class="inline-flex items-center justify-center px-6 py-3 text-sm font-medium rounded-md transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:opacity-50 disabled:pointer-events-none"
                        x-bind:class="camera.active ? 'bg-destructive text-destructive-foreground hover:bg-destructive/90' : 'bg-primary text-primary-foreground hover:bg-primary/90'">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                        <span x-text="camera.active ? 'Matikan Kamera' : 'Aktifkan Kamera'"></span>
                    </button>
                    
                    <x-ui.button 
                        @click="captureAttendance()" 
                        x-show="camera.active && detection.status === 'detected'"
                        variant="outline"
                        size="lg"
                        class="flex items-center border-purple-500 text-purple-600 hover:bg-purple-50">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/></svg>
                        <span>Proses Absensi</span>
                    </x-ui.button>
                    
                    <x-ui.button 
                        @click="simulateAttendance()" 
                        variant="warning"
                        size="lg"
                        class="flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        <span>Mode Demo</span>
                    </x-ui.button>
                </div>
        </x-ui.card>

        <!-- Workflow Progress Indicator -->
        <x-ui.card x-show="workflow.currentStep > 1" class="mb-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Progres Absensi</h3>
                <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300">
                    Langkah <span x-text="workflow.currentStep"></span> dari <span x-text="workflow.steps.length"></span>
                </span>
            </div>
            
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3 mb-6">
                <div class="h-3 bg-gradient-to-r from-emerald-500 to-emerald-600 rounded-full transition-all duration-500"
                     x-bind:style="`width: ${(workflow.currentStep - 1) / workflow.steps.length * 100}%`"></div>
            </div>
            
            <div class="flex justify-between items-center">
                <template x-for="step in workflow.steps" :key="step.id">
                    <div class="flex flex-col items-center">
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center text-sm font-medium transition-all"
                             x-bind:class="step.completed ? 'bg-gradient-to-br from-emerald-500 to-emerald-600 text-white shadow-sm' : 
                                    step.id === workflow.currentStep ? 'bg-gradient-to-br from-blue-500 to-blue-600 text-white shadow-sm' : 
                                    'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400'">
                            <span x-show="step.completed">✓</span>
                            <span x-show="!step.completed" x-text="step.id"></span>
                        </div>
                        <div class="mt-3 text-center max-w-20">
                            <div class="text-sm font-medium text-gray-900 dark:text-white" x-text="step.title"></div>
                            <div class="text-xs text-muted-foreground" x-text="step.description"></div>
                        </div>
                    </div>
                </template>
            </div>
        </x-ui.card>

        <!-- Enhanced Statistics Dashboard -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out hover:bg-white/30 hover:scale-105 text-center">
                <div class="text-3xl font-bold text-emerald-600" x-text="stats.facesDetected"></div>
                <div class="text-sm text-slate-600 dark:text-slate-400">Wajah Terdeteksi</div>
                <div class="mt-2 w-full bg-white/20 rounded-full h-1">
                    <div class="bg-gradient-to-r from-emerald-500 to-green-500 h-1 rounded-full" style="width: 75%"></div>
                </div>
            </div>
            
            <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out hover:bg-white/30 hover:scale-105 text-center">
                <div class="text-3xl font-bold text-blue-600" x-text="stats.averageConfidence"></div>
                <div class="text-sm text-slate-600 dark:text-slate-400">Kepercayaan Rata-rata</div>
                <div class="mt-2 w-full bg-white/20 rounded-full h-1">
                    <div class="bg-gradient-to-r from-blue-500 to-cyan-500 h-1 rounded-full" :style="`width: ${stats.averageConfidence}%`"></div>
                </div>
            </div>
            
            <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out hover:bg-white/30 hover:scale-105 text-center">
                <div class="text-3xl font-bold text-purple-600" x-text="stats.processingTime"></div>
                <div class="text-sm text-slate-600 dark:text-slate-400">Waktu Pemrosesan (ms)</div>
                <div class="mt-2 w-full bg-white/20 rounded-full h-1">
                    <div class="bg-gradient-to-r from-purple-500 to-pink-500 h-1 rounded-full" style="width: 90%"></div>
                </div>
            </div>
            
            <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out hover:bg-white/30 hover:scale-105 text-center">
                <div class="text-3xl font-bold text-amber-600" x-text="stats.successRate"></div>
                <div class="text-sm text-slate-600 dark:text-slate-400">Tingkat Keberhasilan</div>
                <div class="mt-2 w-full bg-white/20 rounded-full h-1">
                    <div class="bg-gradient-to-r from-amber-500 to-orange-600 h-1 rounded-full" :style="`width: ${stats.successRate}%`"></div>
                </div>
            </div>
        </div>

        <!-- Admin Override Section -->
        @can('manage_attendance_all')
        <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <h2 class="text-2xl font-bold text-slate-800 dark:text-white flex items-center mb-6">
                <svg class="w-8 h-8 mr-3 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                Admin Override
            </h2>
            
            <form @submit.prevent="submitManualEntry()" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <x-ui.label for="manualEmployee">Karyawan</x-ui.label>
                    <x-ui.select id="manualEmployee" x-model="manualEntry.employeeId">
                        <option value="">Pilih Karyawan</option>
                        <!-- Options will be populated via API -->
                    </x-ui.select>
                </div>
                
                <div>
                    <x-ui.label for="manualAction">Aksi</x-ui.label>
                    <x-ui.select id="manualAction" x-model="manualEntry.action">
                        <option value="check-in">Check In</option>
                        <option value="check-out">Check Out</option>
                    </x-ui.select>
                </div>
                
                <div class="md:col-span-2">
                    <x-ui.label for="manualNotes">Catatan</x-ui.label>
                    <textarea id="manualNotes" x-model="manualEntry.notes" rows="3" 
                             class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 shadow-sm" 
                             placeholder="Catatan opsional untuk entri manual"></textarea>
                </div>
                
                <div class="md:col-span-2">
                    <x-ui.button type="submit" variant="warning" class="w-full">
                        <svg class="w-5 h-5 mr-2 group-hover:rotate-90 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        <span>Kirim Entri Manual</span>
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>
        @endcan

        <!-- Enhanced Success Modal -->
<x-ui.modal id="successModal" title="Berhasil!" size="md">
    <div class="text-center">
        <div class="mx-auto w-20 h-20 rounded-full bg-gradient-to-r from-emerald-500 to-green-500 flex items-center justify-center mb-6 glow-success">
            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        
        <p class="text-slate-600 dark:text-slate-400 mb-6" x-text="successMessage"></p>
        
        <div class="mb-6">
            <div class="w-full bg-white/20 rounded-full h-3">
                <div class="progress-enhanced bg-gradient-to-r from-emerald-500 to-green-500 h-3 rounded-full" style="width: 100%"></div>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">Menutup otomatis dalam <span x-text="autoCloseTimer"></span> detik...</p>
        </div>
        
        <div class="flex items-center justify-center space-x-4 text-sm text-slate-600 dark:text-slate-400">
            <div class="flex items-center space-x-2">
                <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                <span>Terverifikasi</span>
            </div>
            <div class="flex items-center space-x-2">
                <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                <span>Tercatat</span>
            </div>
            <div class="flex items-center space-x-2">
                <div class="w-3 h-3 bg-purple-500 rounded-full"></div>
                <span>Diberitahukan</span>
            </div>
        </div>
    </div>
</x-ui.modal>

<!-- Location Validation Modal -->
<x-ui.modal id="locationValidationModal" title="Validasi Lokasi">
    <div class="text-center">
        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
            <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
        </div>
        <h3 class="text-lg leading-6 font-medium text-slate-800 dark:text-white mt-3">Validasi Lokasi</h3>
        <p class="text-sm text-slate-600 dark:text-slate-400 mt-2">Kami sedang memeriksa lokasi Anda untuk memastikan Anda berada di dalam area sekolah.</p>
        
        <div class="mt-4 p-4 bg-white/10 rounded-lg">
            <div class="flex items-center space-x-3">
                <div class="flex-shrink-0">
                    <div class="w-3 h-3 rounded-full" x-bind:class="location.valid ? 'bg-green-500' : 'bg-red-500'"></div>
                </div>
                <div class="flex-1 text-left">
                    <p class="text-sm font-medium text-slate-800 dark:text-white" x-text="location.status"></p>
                    <p class="text-sm text-slate-600 dark:text-slate-400" x-text="location.address"></p>
                    <p class="text-xs text-slate-500 dark:text-slate-400" x-show="location.accuracy > 0">
                        Akurasi: <span x-text="location.accuracy"></span>m
                    </p>
                </div>
            </div>
        </div>
    </div>
    <x-slot name="footer">
        <x-ui.button @click="nextStep()" 
                     x-bind:disabled="!location.valid"
                     x-bind:class="location.valid ? 'bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white' : 'bg-slate-300 cursor-not-allowed text-slate-500'">
            Lanjutkan
        </x-ui.button>
        <x-ui.button variant="secondary" @click="closeModal('locationValidationModal')">Batal</x-ui.button>
    </x-slot>
</x-ui.modal>

<!-- Face Recognition Modal -->
<x-ui.modal id="faceRecognitionModal" title="Verifikasi Pengenalan Wajah" size="2xl">
    <div class="text-center">
        <h3 class="text-lg leading-6 font-medium text-slate-800 dark:text-white mb-4">Verifikasi Pengenalan Wajah</h3>
        
        <div class="relative mx-auto w-full max-w-md">
            <video id="video-stream" class="w-full h-auto rounded-lg shadow-lg" autoplay muted playsinline></video>
            <canvas id="face-overlay" class="absolute inset-0 w-full h-full"></canvas>
            
            <div class="mt-4 p-3 bg-white/10 rounded-lg">
                <p class="text-sm font-medium text-slate-800 dark:text-white" x-text="detection.message"></p>
                <div class="mt-2 flex items-center space-x-4 text-xs text-slate-600 dark:text-slate-400">
                    <span>Kepercayaan: <span x-text="detection.confidence"></span>%</span>
                    <span>Kehidupan: <span x-text="livenessCheck.confidence"></span>%</span>
                </div>
            </div>
            
            <div x-show="livenessCheck.required" class="mt-4 p-3 bg-blue-500/20 rounded-lg">
                <p class="text-sm font-medium text-blue-800 dark:text-blue-200">
                    Mohon <span x-text="livenessCheck.requestedGesture"></span> untuk memverifikasi Anda adalah orang sungguhan
                </p>
                <div class="mt-2 w-full bg-blue-200 rounded-full h-2">
                    <div class="bg-gradient-to-r from-blue-500 to-cyan-500 h-2 rounded-full transition-all duration-300" 
                         :style="'width: ' + livenessCheck.progress + '%'"></div>
                </div>
            </div>
        </div>
    </div>
    <x-slot name="footer">
        <x-ui.button @click="nextStep()" 
                     x-bind:disabled="!livenessCheck.completed"
                     x-bind:class="livenessCheck.completed ? 'bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white' : 'bg-slate-300 cursor-not-allowed text-slate-500'">
            Verifikasi Identitas
        </x-ui.button>
        <x-ui.button variant="secondary" @click="stopCamera(); closeModal('faceRecognitionModal')">Batal</x-ui.button>
    </x-slot>
</x-ui.modal>

<!-- Attendance Confirmation Modal -->
<div x-show="showConfirmationModal" x-transition class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="fixed inset-0 bg-slate-500 bg-opacity-75 transition-opacity"></div>
        <div class="relative bg-white dark:bg-slate-800 rounded-lg shadow-xl max-w-md w-full">
            <div class="p-6">
                <div class="text-center">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-green-100 sm:mx-0 sm:h-10 sm:w-10">
                        <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <h3 class="text-lg leading-6 font-medium text-slate-800 dark:text-white mt-3">Konfirmasi Absensi</h3>
                    <p class="text-sm text-slate-600 dark:text-slate-400 mt-2">Mohon konfirmasi detail absensi Anda sebelum mengirimkan.</p>
                    
                    <div class="mt-4 p-4 bg-slate-50 dark:bg-slate-700 rounded-lg">
                        <div class="space-y-3 text-slate-800 dark:text-white">
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Waktu:</span>
                                <span class="text-sm" x-text="currentTime"></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Lokasi:</span>
                                <span class="text-sm" x-text="location.status"></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Wajah Terverifikasi:</span>
                                <span class="text-sm text-green-600">✓ Terverifikasi</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Kehidupan:</span>
                                <span class="text-sm text-green-600">✓ Terkonfirmasi</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-6 flex space-x-3">
                    <button @click="submitAttendance()" 
                           class="flex-1 bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white py-3 px-4 rounded-lg font-medium transition-all duration-200">
                        Kirim Absensi
                    </button>
                    <button @click="showConfirmationModal = false" 
                           class="flex-1 bg-slate-200 hover:bg-slate-300 dark:bg-slate-600 dark:hover:bg-slate-500 text-slate-800 dark:text-white py-3 px-4 rounded-lg font-medium transition-all duration-200">
                        Batal
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Schedule Detail Modal -->
<div x-show="showScheduleDetailModal" x-transition class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="fixed inset-0 bg-slate-500 bg-opacity-75 transition-opacity"></div>
        <div class="relative bg-white dark:bg-slate-800 rounded-lg shadow-xl max-w-lg w-full">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Detail Jadwal Hari Ini</h3>
                    <button @click="showScheduleDetailModal = false" class="text-slate-400 hover:text-slate-500">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <div class="space-y-4" x-show="schedule && schedule.periods">
                    <div class="text-sm text-slate-600 dark:text-slate-400 mb-3">
                        <span x-text="todayFormatted"></span> • 
                        <span x-text="schedule?.periods_count || 0"></span> periode mengajar
                    </div>
                    
                    <div class="space-y-3 max-h-80 overflow-y-auto">
                        <template x-for="(period, index) in schedule?.periods || []" :key="index">
                            <div class="border border-slate-200 dark:border-slate-600 rounded-lg p-4">
                                <div class="flex items-center justify-between mb-2">
                                    <h4 class="font-medium text-slate-900 dark:text-white" x-text="period.name"></h4>
                                    <span class="text-sm text-slate-500 dark:text-slate-400" 
                                          x-text="period.start_time + ' - ' + period.end_time"></span>
                                </div>
                                <div class="text-sm text-slate-600 dark:text-slate-400">
                                    <div class="flex items-center mb-1">
                                        <svg class="w-4 h-4 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                        </svg>
                                        <span x-text="period.subject"></span>
                                    </div>
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        </svg>
                                        <span x-text="period.room"></span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
                
                <div x-show="!schedule || !schedule.periods || schedule.periods.length === 0" 
                     class="text-center py-8 text-slate-500 dark:text-slate-400">
                    Tidak ada jadwal untuk hari ini
                </div>
                
                <div class="mt-6 flex justify-end">
                    <button @click="showScheduleDetailModal = false" 
                           class="px-4 py-2 bg-slate-200 hover:bg-slate-300 dark:bg-slate-600 dark:hover:bg-slate-500 text-slate-800 dark:text-white rounded-lg font-medium transition-all duration-200">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

</div>

@endsection

@push('head')
<!-- Use specific compatible versions -->
<script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs@1.7.4/dist/tf.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
@endpush

@push('scripts')
<script>
// Simple TensorFlow.js and Face-API.js initialization
(async function() {
    // Wait for libraries to load
    await new Promise(resolve => {
        if (typeof tf !== 'undefined' && typeof faceapi !== 'undefined') {
            resolve();
        } else {
            const checkLibs = setInterval(() => {
                if (typeof tf !== 'undefined' && typeof faceapi !== 'undefined') {
                    clearInterval(checkLibs);
                    resolve();
                }
            }, 100);
        }
    });
    
    console.log('🔧 Initializing TensorFlow.js and Face-API.js...');
    
    // Wait for TensorFlow to be ready
    await tf.ready();
    console.log('✅ TensorFlow.js initialized with backend:', tf.getBackend());
    
    console.log('🔧 Loading Face-API.js models...');
    
    // Wait a bit for TensorFlow to fully stabilize
    await new Promise(resolve => setTimeout(resolve, 500));
    
    const MODEL_URL = '/models';
    console.log('Loading models from:', MODEL_URL);
    
    Promise.all([
        faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL).catch(e => {
            console.error('Failed to load TinyFaceDetector:', e);
            throw e;
        }),
        faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL).catch(e => {
            console.error('Failed to load FaceLandmark68Net:', e);
            throw e;
        }),
        faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL).catch(e => {
            console.error('Failed to load FaceRecognitionNet:', e);
            throw e;
        }),
        faceapi.nets.faceExpressionNet.loadFromUri(MODEL_URL).catch(e => {
            console.error('Failed to load FaceExpressionNet:', e);
            throw e;
        }),
        faceapi.nets.ssdMobilenetv1.loadFromUri(MODEL_URL).catch(e => {
            console.error('Failed to load SsdMobilenetv1:', e);
            throw e;
        })
    ]).then(() => {
        console.log('✅ Face-API.js models loaded successfully from local storage');
        
        // Verify all models are actually loaded
        const allModelsLoaded = 
            faceapi.nets.tinyFaceDetector.isLoaded &&
            faceapi.nets.faceLandmark68Net.isLoaded &&
            faceapi.nets.faceRecognitionNet.isLoaded &&
            faceapi.nets.faceExpressionNet.isLoaded &&
            faceapi.nets.ssdMobilenetv1.isLoaded;
            
        if (allModelsLoaded) {
            window.faceApiModelsReady = true;
            console.log('✅ All face detection models verified as loaded');
            // Dispatch custom event to notify when models are ready
            window.dispatchEvent(new CustomEvent('faceapi-models-loaded'));
        } else {
            console.error('❌ Some models failed to load properly');
            throw new Error('Model verification failed');
        }
    }).catch(err => {
        console.error('❌ Failed to load Face-API.js models from local storage:', err);
        // Fallback to CDN if local models fail
        console.log('🔄 Attempting to load from CDN fallback...');
        const CDN_URL = 'https://justadudewhohacks.github.io/face-api.js/models';
        return Promise.all([
            faceapi.nets.tinyFaceDetector.loadFromUri(CDN_URL),
            faceapi.nets.faceLandmark68Net.loadFromUri(CDN_URL),
            faceapi.nets.faceRecognitionNet.loadFromUri(CDN_URL),
            faceapi.nets.faceExpressionNet.loadFromUri(CDN_URL),
            faceapi.nets.ssdMobilenetv1.loadFromUri(CDN_URL)
        ]).then(() => {
            console.log('✅ Face-API.js models loaded successfully from CDN fallback');
            window.faceApiModelsReady = true;
            window.dispatchEvent(new CustomEvent('faceapi-models-loaded'));
        }).catch(cdnErr => {
            console.error('❌ Failed to load models from CDN as well:', cdnErr);
            window.faceApiModelsFailed = true;
        });
    });
})();

function enhancedAttendanceSystem() {
    return {
        // Core State
        currentTime: new Date().toLocaleTimeString(),
        showSuccessModal: false,
        showConfirmationModal: false,
        showScheduleDetailModal: false,
        successMessage: '',
        
        // Schedule and Attendance Data
        schedule: null,
        attendance: null,
        todayFormatted: new Date().toLocaleDateString('id-ID', { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        }),
        autoCloseTimer: 5,
        
        // Workflow State
        workflow: {
            currentStep: 1,
            steps: [
                { id: 1, title: 'Periksa Lokasi', description: 'Memvalidasi lokasi Anda', completed: false },
                { id: 2, title: 'Pengenalan Wajah', description: 'Verifikasi identitas Anda', completed: false },
                { id: 3, title: 'Pemeriksaan Kehidupan', description: 'Selesaikan verifikasi keamanan', completed: false },
                { id: 4, title: 'Konfirmasi Absensi', description: 'Catat absensi Anda', completed: false }
            ],
            canProceed: false
        },
        
        // Modals State
        modals: {
            locationValidation: false,
            faceRecognition: false,
            attendanceConfirmation: false
        },
        
        // Weather Data
        weather: {
            temperature: '24°C',
            condition: 'Cerah',
            location: 'Jakarta, ID'
        },
        
        // Status Data
        status: {
            title: 'Siap Check In',
            subtitle: 'Mohon gunakan pengenalan wajah',
            badge: 'Siap',
            progress: 0
        },
        
        // Location Data
        location: {
            valid: false,
            status: 'Mendeteksi...',
            address: 'Mencari lokasi Anda...',
            accuracy: 0,
            latitude: null,
            longitude: null
        },
        
        // Face Recognition Data
        faceRecognition: {
            enrolled: true,
            status: 'Siap',
            confidence: 'Pengenalan wajah aktif'
        },
        
        // Performance Data
        performance: {
            speed: 'Pemrosesan real-time',
            accuracy: '95%',
            uptime: '99.9%'
        },
        
        // Camera State
        camera: {
            active: false,
            stream: null
        },
        
        // Detection State
        detection: {
            status: 'idle',
            message: 'Mulai kamera untuk memulai deteksi',
            confidence: '0%',
            liveness: '0%',
            progress: 0,
            currentGesture: null,
            gestureCompleted: false
        },
        
        // Timers
        noFaceTimer: null,
        
        // Detection Method
        detectionMethod: {
            current: 'face-api',
            name: 'Face-API.js'
        },
        
        
        // Manual Entry
        manualEntry: {
            employeeId: '',
            action: 'check-in',
            notes: ''
        },
        
        // Audio Context (initialized on user interaction)
        audioContext: null,
        
        // Face Recognition Data
        profileData: null,
        referenceFaceDescriptor: null,
        currentFaceDescriptor: null,
        faceDetector: null,
        
        // Liveness Detection
        livenessCheck: {
            gestures: ['smile', 'shake_head', 'nod'],
            currentGestureIndex: 0,
            gestureDetected: false,
            attempts: 0,
            maxAttempts: 3,
            confidence: 0,
            required: false,
            requestedGesture: '',
            progress: 0,
            completed: false
        },
        
        // Face position tracking
        currentFacePosition: null,
        lastFacePosition: null,
        stats: {
            facesDetected: 0,
            averageConfidence: 0,
            processingTime: 0,
            successRate: 98,
            lastProcessTime: null
        },
        
        // Initialize System
        initializeSystem() {
            this.startClock();
            // this.initializeParticles(); // Particles are heavy, disable for now
            this.loadCurrentStatus();
            this.loadProfileData();
            this.loadTodayScheduleAndStatus();
            this.showNotification('Sistem berhasil diinisialisasi', 'success');
            
            // Initialize audio context and location on first user interaction
            document.addEventListener('click', () => {
                this.initializeAudioContext();
                if (!this.location.valid && this.location.status === 'Mendeteksi...') {
                    this.getLocation();
                }
            }, { once: true });
            
            document.addEventListener('touchstart', () => {
                this.initializeAudioContext();
                if (!this.location.valid && this.location.status === 'Mendeteksi...') {
                    this.getLocation();
                }
            }, { once: true });
        },
        
        // Load Profile Data
        async loadProfileData() {
            try {
                // Test authentication first
                const testResponse = await fetch('/api/face-verification/test', {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });
                
                const testData = await testResponse.json();
                console.log('Auth test result:', testData);
                
                if (!testData.authenticated) {
                    this.showNotification('Pengguna tidak terautentikasi', 'error');
                    return;
                }
                
                const response = await fetch('/api/face-verification/profile-data', {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });
                
                const data = await response.json();
                
                // Debug logging
                console.log('Face verification profile data received:', data);
                console.log('Face registered status:', data.data?.face_registered);
                console.log('Face descriptor exists:', !!data.data?.face_descriptor);
                console.log('Face descriptor type:', typeof data.data?.face_descriptor);
                
                if (data.success) {
                    this.profileData = data.data;
                    
                    if (data.data.face_registered) {
                        this.referenceFaceDescriptor = new Float32Array(data.data.face_descriptor);
                        this.faceRecognition.enrolled = true;
                        this.faceRecognition.status = 'Profil Dimuat';
                        this.faceRecognition.confidence = 'Data wajah tersedia';
                    } else {
                        this.faceRecognition.enrolled = false;
                        this.faceRecognition.status = 'Belum Terdaftar';
                        this.faceRecognition.confidence = 'Mohon daftar wajah terlebih dahulu';
                        
                        // Register face from profile photo if available
                        if (data.data.profile_photo_url) {
                            await this.registerFaceFromPhoto(data.data.profile_photo_url);
                        }
                    }
                } else {
                    this.showNotification(data.message || 'Gagal memuat data profil', 'error');
                }
            } catch (error) {
                console.error('Load profile data error:', error);
                this.showNotification('Gagal memuat data profil', 'error');
            }
        },
        
        // Register Face from Profile Photo
        async registerFaceFromPhoto(photoUrl) {
            try {
                this.showNotification('Menganalisis foto profil...', 'info');
                
                const img = await faceapi.fetchImage(photoUrl);
                const detection = await faceapi
                    .detectSingleFace(img, new faceapi.TinyFaceDetectorOptions({
                        inputSize: 512,
                        scoreThreshold: 0.3
                    }))
                    .withFaceLandmarks()
                    .withFaceDescriptor();
                
                if (detection) {
                    // Save face descriptor
                    const response = await fetch('/api/face-verification/save-descriptor', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            face_descriptor: Array.from(detection.descriptor),
                            confidence: detection.detection.score
                        })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        this.referenceFaceDescriptor = detection.descriptor;
                        this.faceRecognition.enrolled = true;
                        this.faceRecognition.status = 'Wajah Terdaftar';
                        this.faceRecognition.confidence = 'Siap untuk verifikasi';
                        this.showNotification('Wajah berhasil didaftarkan dari foto profil', 'success');
                    } else {
                        this.showNotification('Gagal mendaftarkan wajah', 'error');
                    }
                } else {
                    this.showNotification('Tidak ada wajah terdeteksi di foto profil', 'warning');
                }
            } catch (error) {
                console.error('Face registration error:', error);
                this.showNotification('Gagal menganalisis foto profil', 'error');
            }
        },
        
        // Clock Updates
        startClock() {
            setInterval(() => {
                this.currentTime = new Date().toLocaleTimeString();
            }, 1000);
        },
        
        // Initialize Particle Background
        initializeParticles() {
            const canvas = document.getElementById('particles-canvas');
            const ctx = canvas.getContext('2d');
            
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
            
            const particles = [];
            const particleCount = 50;
            
            for (let i = 0; i < particleCount; i++) {
                particles.push({
                    x: Math.random() * canvas.width,
                    y: Math.random() * canvas.height,
                    vx: (Math.random() - 0.5) * 0.5,
                    vy: (Math.random() - 0.5) * 0.5,
                    radius: Math.random() * 2 + 1,
                    opacity: Math.random() * 0.5 + 0.2
                });
            }
            
            function animate() {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                
                particles.forEach(particle => {
                    particle.x += particle.vx;
                    particle.y += particle.vy;
                    
                    if (particle.x < 0 || particle.x > canvas.width) particle.vx *= -1;
                    if (particle.y < 0 || particle.y > canvas.height) particle.vy *= -1;
                    
                    ctx.beginPath();
                    ctx.arc(particle.x, particle.y, particle.radius, 0, Math.PI * 2);
                    ctx.fillStyle = `rgba(16, 185, 129, ${particle.opacity})`;
                    ctx.fill();
                });
                
                requestAnimationFrame(animate);
            }
            
            animate();
        },
        
        // Load Current Attendance Status
        async loadCurrentStatus() {
            try {
                const response = await fetch('/attendance/api/v1/attendance/status');
                const data = await response.json();
                
                if (data.success) {
                    this.updateStatus(data.data);
                }
            } catch (error) {
                this.showNotification('Gagal memuat status absensi', 'error');
            }
        },
        
        // Update Status Display
        updateStatus(data) {
            if (data.status === 'not_checked_in') {
                this.status = {
                    title: 'Siap Check In',
                    subtitle: 'Mohon gunakan pengenalan wajah',
                    badge: 'Siap',
                    progress: 0
                };
            } else if (data.status === 'checked_in') {
                this.status = {
                    title: 'Sudah Check In',
                    subtitle: `Sejak ${new Date(data.check_in_time).toLocaleTimeString()}`,
                    badge: 'Bekerja',
                    progress: 100
                };
            } else if (data.status === 'checked_out') {
                this.status = {
                    title: 'Hari Selesai',
                    subtitle: `Total: ${data.working_hours_formatted}`,
                    badge: 'Selesai',
                    progress: 100
                };
            }
        },
        
        // Get User Location
        async getLocation() {
            if (!navigator.geolocation) {
                this.location = {
                    valid: false,
                    status: 'Tidak Didukung',
                    address: 'Geolocation tidak tersedia',
                    accuracy: 0
                };
                return;
            }
            
            this.location.status = 'Mendeteksi...';
            this.location.address = 'Mendapatkan lokasi Anda...';
            
            navigator.geolocation.getCurrentPosition(
                async (position) => {
                    const userLat = position.coords.latitude;
                    const userLng = position.coords.longitude;
                    const accuracy = Math.round(position.coords.accuracy);
                    
                    // Validate location with school radius
                    const isValid = await this.validateLocationRadius(userLat, userLng);
                    
                    this.location = {
                        valid: isValid,
                        status: isValid ? 'Lokasi Valid' : 'Di Luar Radius',
                        address: isValid ? 'Di dalam area sekolah' : 'Anda berada di luar radius yang diizinkan',
                        accuracy: accuracy,
                        latitude: userLat,
                        longitude: userLng
                    };
                    
                    if (isValid) {
                        this.completeStep(1);
                        this.showNotification('Lokasi berhasil diverifikasi', 'success');
                    } else {
                        this.showNotification('Anda berada di luar radius yang diizinkan', 'warning');
                    }
                },
                (error) => {
                    this.location = {
                        valid: false,
                        status: 'Error',
                        address: 'Tidak dapat memperoleh lokasi: ' + error.message,
                        accuracy: 0,
                        latitude: null,
                        longitude: null
                    };
                    this.showNotification('Akses lokasi ditolak', 'error');
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 300000
                }
            );
        },
        
        // Validate Location Radius
        async validateLocationRadius(userLat, userLng) {
            // School coordinates (example - should be configurable)
            const schoolLat = -6.2088;  // Jakarta coordinates as example
            const schoolLng = 106.8456;
            const allowedRadius = 100; // meters
            
            // Calculate distance using Haversine formula
            const R = 6371e3; // Earth's radius in meters
            const φ1 = userLat * Math.PI/180;
            const φ2 = schoolLat * Math.PI/180;
            const Δφ = (schoolLat - userLat) * Math.PI/180;
            const Δλ = (schoolLng - userLng) * Math.PI/180;
            
            const a = Math.sin(Δφ/2) * Math.sin(Δφ/2) +
                     Math.cos(φ1) * Math.cos(φ2) *
                     Math.sin(Δλ/2) * Math.sin(Δλ/2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
            
            const distance = R * c;
            
            return distance <= allowedRadius;
        },
        
        // Workflow Management
        startCheckInWorkflow() {
            this.workflow.currentStep = 1;
            this.workflow.canProceed = false;
            openModal('locationValidationModal');
            this.getLocation();
        },
        
        nextStep() {
            if (this.workflow.currentStep < this.workflow.steps.length) {
                this.workflow.steps[this.workflow.currentStep - 1].completed = true;
                this.workflow.currentStep++;
                this.workflow.canProceed = false;
                
                // Execute step-specific logic
                switch (this.workflow.currentStep) {
                    case 2:
                        closeModal('locationValidationModal');
                        openModal('faceRecognitionModal');
                        this.startCamera();
                        break;
                    case 3:
                        // Liveness check will be handled in face detection
                        break;
                    case 4:
                        console.log('NextStep case 4: Closing face modal and opening confirmation modal');
                        closeModal('faceRecognitionModal');
                        this.stopCamera();
                        setTimeout(() => {
                            console.log('Opening attendanceConfirmationModal from nextStep case 4');
                            this.showConfirmationModal = true;
                            console.log('Confirmation modal set to true');
                        }, 300);
                        break;
                }
            }
        },
        
        previousStep() {
            if (this.workflow.currentStep > 1) {
                this.workflow.currentStep--;
                this.workflow.steps[this.workflow.currentStep].completed = false;
                this.workflow.canProceed = true;
            }
        },
        
        completeStep(stepId) {
            const step = this.workflow.steps.find(s => s.id === stepId);
            if (step) {
                step.completed = true;
                this.workflow.canProceed = true;
            }
        },
        
        // Switch Detection Method
        switchDetectionMethod(method) {
            this.detectionMethod.current = method;
            this.detectionMethod.name = method === 'face-api' ? 'Face-API.js' : 'MediaPipe';
            this.showNotification(`Beralih ke ${this.detectionMethod.name}`, 'info');
        },
        
        // Toggle Camera
        async toggleCamera() {
            if (this.camera.active) {
                await this.stopCamera();
            } else {
                await this.startCamera();
            }
        },
        
        // Start Camera
        async startCamera() {
            try {
                this.camera.stream = await navigator.mediaDevices.getUserMedia({
                    video: {
                        width: { ideal: 1280 },
                        height: { ideal: 720 },
                        facingMode: 'user'
                    },
                    audio: false
                });
                
                const video = document.getElementById('video-stream');
                video.srcObject = this.camera.stream;
                
                this.camera.active = true;
                this.detection.status = 'mencari';
                this.detection.message = 'Mencari wajah...';
                
                this.showNotification('Kamera berhasil diaktifkan', 'success');
                this.startFaceDetection();
                
            } catch (error) {
                this.showNotification('Gagal mengakses kamera', 'error');
                console.error('Camera error:', error);
            }
        },
        
        // Stop Camera
        async stopCamera() {
            if (this.camera.stream) {
                this.camera.stream.getTracks().forEach(track => track.stop());
                this.camera.stream = null;
            }
            
            this.camera.active = false;
            this.detection.status = 'idle';
            this.detection.message = 'Kamera dihentikan';
            this.detection.confidence = '0%';
            this.detection.liveness = '0%';
            this.detection.progress = 0;
            
            this.showNotification('Kamera dihentikan', 'info');
        },
        
        // Start Face Detection
        async startFaceDetection() {
            if (!this.camera.active) return;
            
            const video = document.getElementById('video-stream');
            const canvas = document.getElementById('face-overlay');
            
            // Check if video is ready
            if (!video.videoWidth || !video.videoHeight) {
                console.log('Video tidak siap, menunggu...');
                setTimeout(() => this.startFaceDetection(), 500);
                return;
            }
            
            const displaySize = { width: video.videoWidth, height: video.videoHeight };
            console.log('Dimensi video:', displaySize);
            
            // Wait for Face-API.js models to be ready
            if (!window.faceApiModelsReady) {
                this.detection.message = 'Memuat model deteksi wajah...';
                this.showNotification('Memuat model deteksi wajah...', 'info');
                
                // Wait maximum 10 seconds for models
                const modelTimeout = setTimeout(() => {
                    this.detection.message = 'Waktu habis memuat model - mohon segarkan';
                    this.showNotification('Waktu habis memuat model. Mohon segarkan halaman.', 'error');
                }, 10000);
                
                await new Promise(resolve => {
                    if (window.faceApiModelsReady) {
                        clearTimeout(modelTimeout);
                        resolve();
                    } else {
                        window.addEventListener('faceapi-models-loaded', () => {
                            clearTimeout(modelTimeout);
                            resolve();
                        }, { once: true });
                    }
                });
                this.detection.message = 'Model deteksi wajah dimuat';
                this.showNotification('Model deteksi wajah dimuat', 'success');
            }
            
            faceapi.matchDimensions(canvas, displaySize);
            
            this.faceDetector = setInterval(async () => {
                if (!this.camera.active) {
                    clearInterval(this.faceDetector);
                    return;
                }
                
                let detections = [];
                
                try {
                    // First try simple face detection with lower threshold
                    detections = await faceapi
                        .detectAllFaces(video, new faceapi.TinyFaceDetectorOptions({
                            inputSize: 512,
                            scoreThreshold: 0.3
                        }))
                        .withFaceLandmarks()
                        .withFaceExpressions()
                        .withFaceDescriptors();
                    
                    console.log(`Deteksi wajah: ${detections.length} wajah ditemukan`);
                    
                } catch (error) {
                    console.error('Kesalahan deteksi wajah:', error);
                    
                    // Try fallback detection with SSD MobileNet
                    try {
                        console.log('🔄 Mencoba deteksi fallback SSD MobileNet...');
                        const fallbackDetections = await faceapi
                            .detectAllFaces(video, new faceapi.SsdMobilenetv1Options({
                                minConfidence: 0.4
                            }))
                            .withFaceLandmarks()
                            .withFaceExpressions()
                            .withFaceDescriptors();
                        
                        if (fallbackDetections.length > 0) {
                            detections = fallbackDetections;
                            console.log(`✅ Deteksi fallback berhasil: ${detections.length} wajah ditemukan`);
                        } else {
                            this.detection.message = 'Tidak ada wajah terdeteksi - pastikan pencahayaan baik';
                            return;
                        }
                    } catch (fallbackError) {
                        console.error('Deteksi fallback juga gagal:', fallbackError);
                        this.detection.message = 'Kesalahan deteksi - mohon segarkan halaman';
                        return;
                    }
                }
                
                // Clear previous drawings
                const ctx = canvas.getContext('2d');
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                
                // Check if we have detections to work with
                if (detections.length === 0) {
                    this.detection.message = 'Tidak ada wajah terdeteksi - dekati kamera';
                    this.detection.confidence = '0%';
                    this.detection.liveness = '0%';
                    this.detection.progress = 0;
                    
                    // Add helpful tips after some time without detection
                    if (!this.noFaceTimer) {
                        this.noFaceTimer = setTimeout(() => {
                            this.detection.message = 'Tips: Dekati kamera, tingkatkan pencahayaan, singkirkan penghalang dari wajah';
                        }, 5000);
                    }
                    return;
                }
                
                const resizedDetections = faceapi.resizeResults(detections, displaySize);
                
                if (detections.length > 0) {
                    const detection = detections[0];
                    
                    // Clear no-face timer since we found a face
                    if (this.noFaceTimer) {
                        clearTimeout(this.noFaceTimer);
                        this.noFaceTimer = null;
                    }
                    
                    // Draw face detection box
                    faceapi.draw.drawDetections(canvas, resizedDetections);
                    faceapi.draw.drawFaceLandmarks(canvas, resizedDetections);
                    
                    // Track face position
                    const box = detection.detection.box;
                    this.currentFacePosition = {
                        x: box.x + box.width / 2,
                        y: box.y + box.height / 2
                    };
                    
                    // Update detection status
                    this.currentFaceDescriptor = detection.descriptor;
                    const confidence = Math.round(detection.detection.score * 100);
                    
                    this.detection.status = 'detected';
                    this.detection.confidence = `${confidence}%`;
                    this.detection.progress = confidence;
                    
                    // If no reference face, automatically register the first good detection
                    if (!this.referenceFaceDescriptor && confidence >= 80) {
                        this.detection.message = 'Mendaftarkan wajah Anda...';
                        this.registerFaceFromDetection(detection);
                        return;
                    }
                    
                    // Check if we need to verify face
                    if (this.referenceFaceDescriptor && !this.livenessCheck.gestureDetected) {
                        const distance = faceapi.euclideanDistance(
                            this.referenceFaceDescriptor,
                            this.currentFaceDescriptor
                        );
                        const similarity = Math.round((1 - Math.min(distance, 1)) * 100);
                        
                        if (similarity >= 70) {
                            this.detection.message = `Wajah cocok! (${similarity}% kemiripan)`;
                            this.completeStep(2); // Complete face recognition step
                            this.startLivenessCheck(detection);
                        } else {
                            this.detection.message = `Wajah tidak cocok (${similarity}% kemiripan)`;
                        }
                    } else if (this.livenessCheck.gestureDetected) {
                        this.checkGesture(detection);
                    } else {
                        this.detection.message = `Wajah terdeteksi (${confidence}% kepercayaan)`;
                    }
                    
                    // Update stats
                    this.stats.facesDetected++;
                    this.stats.averageConfidence = Math.round((this.stats.averageConfidence + confidence) / 2);
                    this.stats.processingTime = Date.now() - this.stats.lastProcessTime || 0;
                    this.stats.lastProcessTime = Date.now();
                    
                } else {
                    this.detection.status = 'mencari';
                    this.detection.message = 'Mencari wajah... Pastikan Anda cukup terang dan menghadap kamera.';
                    this.detection.confidence = '0%';
                    this.detection.liveness = '0%';
                    this.detection.progress = 0;
                    
                    // Add helpful tips after some time without detection
                    if (!this.noFaceTimer) {
                        this.noFaceTimer = setTimeout(() => {
                            this.detection.message = 'Tips: Dekati kamera, tingkatkan pencahayaan, singkirkan penghalang dari wajah';
                        }, 5000);
                    }
                }
            }, 100);
        },
        
        // Register Face from Detection (Auto-registration for first-time users)
        async registerFaceFromDetection(detection) {
            try {
                const faceData = Array.from(detection.descriptor);
                
                const response = await fetch('/api/face-verification/save-descriptor', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        face_descriptor: faceData
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    this.referenceFaceDescriptor = detection.descriptor;
                    this.faceRecognition.enrolled = true;
                    this.faceRecognition.status = 'Wajah Terdaftar';
                    this.faceRecognition.confidence = 'Berhasil didaftarkan secara otomatis';
                    this.detection.message = 'Wajah terdaftar! Sekarang memverifikasi...';
                    this.showNotification('Wajah berhasil didaftarkan! Anda sekarang dapat melanjutkan absensi.', 'success');
                } else {
                    this.detection.message = 'Gagal mendaftarkan wajah';
                    this.showNotification('Gagal mendaftarkan wajah. Mohon coba lagi.', 'error');
                }
            } catch (error) {
                console.error('Kesalahan pendaftaran wajah:', error);
                this.detection.message = 'Terjadi kesalahan pendaftaran';
                this.showNotification('Terjadi kesalahan selama pendaftaran wajah', 'error');
            }
        },
        
        // Start Liveness Check
        startLivenessCheck(detection) {
            if (this.livenessCheck.gestureDetected) return;
            
            this.livenessCheck.gestureDetected = true;
            this.livenessCheck.currentGestureIndex = 0;
            this.nextGesture();
        },
        
        // Get Next Gesture
        nextGesture() {
            const gestures = this.livenessCheck.gestures;
            const currentGesture = gestures[this.livenessCheck.currentGestureIndex];
            
            this.detection.currentGesture = currentGesture;
            
            switch (currentGesture) {
                case 'smile':
                    this.detection.message = 'Mohon SENYUM 😊';
                    break;
                case 'shake_head':
                    this.detection.message = 'Mohon GELENGKAN KEPALA ke kiri dan kanan ↔️';
                    break;
                case 'nod':
                    this.detection.message = 'Mohon ANGGUKKAN KEPALA ke atas dan bawah ↕️';
                    break;
            }
            
            this.showNotification(this.detection.message, 'info');
            this.detection.gestureCompleted = false;
        },
        
        // Check Gesture
        checkGesture(detection) {
            const currentGesture = this.detection.currentGesture;
            
            switch (currentGesture) {
                case 'smile':
                    if (detection.expressions.happy > 0.7) {
                        this.gestureCompleted();
                    }
                    this.detection.liveness = `${Math.round(detection.expressions.happy * 100)}%`;
                    break;
                    
                case 'shake_head':
                    // Check head rotation (would need pose estimation)
                    // For now, check if face moves horizontally
                    if (this.checkHeadMovement('horizontal')) {
                        this.gestureCompleted();
                    }
                    break;
                    
                case 'nod':
                    // Check head nodding (would need pose estimation)
                    // For now, check if face moves vertically
                    if (this.checkHeadMovement('vertical')) {
                        this.gestureCompleted();
                    }
                    break;
            }
        },
        
        // Check Head Movement
        checkHeadMovement(direction) {
            // This is a simplified check - in production you'd use pose estimation
            // or track landmark movements over time
            if (!this.lastFacePosition) {
                this.lastFacePosition = this.currentFacePosition;
                return false;
            }
            
            const threshold = 20; // pixels
            const moved = direction === 'horizontal' 
                ? Math.abs(this.currentFacePosition.x - this.lastFacePosition.x) > threshold
                : Math.abs(this.currentFacePosition.y - this.lastFacePosition.y) > threshold;
                
            this.lastFacePosition = this.currentFacePosition;
            return moved;
        },
        
        // Gesture Completed
        gestureCompleted() {
            this.detection.gestureCompleted = true;
            this.showNotification('Gerakan terdeteksi! ✅', 'success');
            
            this.livenessCheck.currentGestureIndex++;
            
            if (this.livenessCheck.currentGestureIndex < this.livenessCheck.gestures.length) {
                setTimeout(() => this.nextGesture(), 1500);
            } else {
                // All gestures completed
                this.livenessCheck.completed = true;
                this.detection.message = 'Pemeriksaan kehidupan berhasil! ✅';
                this.detection.liveness = '100%';
                this.showNotification('Verifikasi wajah berhasil diselesaikan!', 'success');
                
                // Complete step 3 and trigger step 4 which shows confirmation modal
                this.completeStep(3);
                console.log('Step 3 completed, triggering next step...');
                
                // Trigger step 4 through nextStep() which will show confirmation modal
                setTimeout(() => {
                    this.nextStep();
                }, 500);
            }
        },
        
        // Submit Attendance (Final Step)
        async submitAttendance() {
            try {
                this.showNotification('Mengirim absensi...', 'info');
                
                // Prepare attendance data (employee_id will be determined by backend from auth user)
                const attendanceData = {
                    face_verification_passed: true,
                    face_confidence: parseFloat(this.detection.confidence) / 100,
                    liveness_passed: this.livenessCheck.completed,
                    latitude: this.location.latitude || null,
                    longitude: this.location.longitude || null,
                    location_verified: this.location.valid,
                    metadata: {
                        workflow_completed: true,
                        steps_completed: this.workflow.steps.filter(s => s.completed).length,
                        detection_method: this.detectionMethod.current,
                        timestamp: new Date().toISOString()
                    }
                };
                
                // Determine if this is check-in or check-out
                const isCheckedIn = this.status.badge === 'Bekerja';
                const endpoint = isCheckedIn ? '/attendance/api/check-out' : '/attendance/api/check-in';
                
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(attendanceData)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Complete workflow
                    this.completeStep(4);
                    this.showConfirmationModal = false;
                    this.stopCamera();
                    
                    // Show success modal
                    this.showSuccessModal = true;
                    this.successMessage = `${isCheckedIn ? 'Check-out' : 'Check-in'} berhasil! Waktu tercatat pada ${new Date().toLocaleTimeString()}`;
                    
                    // Refresh schedule and attendance status
                    this.loadTodayScheduleAndStatus();
                    
                    // Reset workflow
                    this.resetWorkflow();
                    
                    // Update status
                    this.loadCurrentStatus();
                    
                    this.showNotification('Absensi berhasil dicatat!', 'success');
                } else {
                    this.showNotification(result.message || 'Gagal mencatat absensi', 'error');
                }
                
            } catch (error) {
                console.error('Kesalahan pengiriman absensi:', error);
                this.showNotification('Terjadi kesalahan saat mengirim absensi', 'error');
            }
        },
        
        // Reset Workflow
        resetWorkflow() {
            this.workflow.currentStep = 1;
            this.workflow.canProceed = false;
            this.workflow.steps.forEach(step => step.completed = false);
            
            // Reset all modals
            closeModal('locationValidationModal');
            closeModal('faceRecognitionModal');
            this.showConfirmationModal = false;
            this.showScheduleDetailModal = false;
            
            // Reset detection states
            this.detection.status = 'idle';
            this.detection.message = 'Siap untuk memulai';
            this.detection.confidence = '0%';
            this.detection.liveness = '0%';
            this.detection.progress = 0;
            
            // Reset liveness check
            this.livenessCheck.gestureDetected = false;
            this.livenessCheck.completed = false;
            this.livenessCheck.currentGestureIndex = 0;
            this.livenessCheck.progress = 0;
        },
        
        // Capture Attendance
        async captureAttendance() {
            if (this.detection.status !== 'detected' || !this.currentFaceDescriptor) {
                this.showNotification('Tidak ada wajah terdeteksi', 'warning');
                return;
            }
            
            if (!this.livenessCheck.gestureDetected || this.livenessCheck.currentGestureIndex < this.livenessCheck.gestures.length) {
                this.showNotification('Mohon selesaikan pemeriksaan kehidupan terlebih dahulu', 'warning');
                return;
            }
            
            this.showNotification('Memverifikasi wajah dan memproses absensi...', 'info', true);
            
            try {
                // First verify the face
                const verifyResponse = await fetch('/api/face-verification/verify', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        face_descriptor: Array.from(this.currentFaceDescriptor),
                        liveness_check: {
                            gesture: this.livenessCheck.gestures[this.livenessCheck.gestures.length - 1],
                            completed: true,
                            confidence: parseFloat(this.detection.confidence) / 100
                        }
                    })
                });
                
                const verifyData = await verifyResponse.json();
                
                if (verifyData.success) {
                    this.showNotification('Verifikasi wajah berhasil!', 'success');
                    // Proceed to submit attendance
                    this.submitAttendance();
                } else {
                    this.showNotification(verifyData.message || 'Verifikasi wajah gagal', 'error');
                }
            } catch (error) {
                console.error('Kesalahan verifikasi wajah:', error);
                this.showNotification('Terjadi kesalahan selama verifikasi wajah', 'error');
            }
        },
        
        // Simulate Attendance (for demo/testing)
        simulateAttendance() {
            this.showNotification('Mensimulasikan absensi...', 'info');
            
            // Simulate successful workflow
            this.workflow.currentStep = 1;
            this.completeStep(1);
            this.location.valid = true;
            this.location.status = 'Lokasi Valid';
            this.location.address = 'Simulasi lokasi sekolah';
            this.location.accuracy = 5;
            
            setTimeout(() => {
                this.completeStep(2);
                this.faceRecognition.enrolled = true;
                this.faceRecognition.status = 'Wajah Terdaftar';
                this.faceRecognition.confidence = 'Simulasi kepercayaan 99%';
                
                setTimeout(() => {
                    this.completeStep(3);
                    this.detection.liveness = '100%';
                    
                    // Show confirmation modal after step 3 completion
                    console.log('Simulation step 3 completed, triggering next step...');
                    setTimeout(() => {
                        this.nextStep();
                    }, 500);
                }, 1000);
            }, 1000);
        },
        
        // Improved Notification System - less intrusive
        showNotification(message, type = 'info', autoHide = true) {
            // Skip certain non-essential notifications
            const skipMessages = [
                'Sistem berhasil diinisialisasi',
                'Menganalisis foto profil...',
                'AudioContext initialized'
            ];
            
            if (skipMessages.some(msg => message.includes(msg))) {
                console.log(`[${type.toUpperCase()}] ${message}`);
                return;
            }

            const container = document.getElementById('notification-container');
            if (!container) return;

            // Remove existing notifications of same type to avoid spam
            container.querySelectorAll('.notification').forEach(notif => {
                if (notif.dataset.type === type) {
                    notif.remove();
                }
            });

            const notification = document.createElement('div');
            notification.dataset.type = type;
            notification.className = `notification pointer-events-auto transform translate-x-full transition-all duration-300 ease-out
                ${type === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-800 dark:bg-emerald-900/20 dark:border-emerald-700 dark:text-emerald-300' : 
                  type === 'error' ? 'bg-red-50 border-red-200 text-red-800 dark:bg-red-900/20 dark:border-red-700 dark:text-red-300' : 
                  type === 'warning' ? 'bg-amber-50 border-amber-200 text-amber-800 dark:bg-amber-900/20 dark:border-amber-700 dark:text-amber-300' : 
                  'bg-blue-50 border-blue-200 text-blue-800 dark:bg-blue-900/20 dark:border-blue-700 dark:text-blue-300'}
                px-3 py-2 rounded-lg border shadow-sm mb-2 flex items-center space-x-2 text-sm max-w-xs`;
            
            notification.innerHTML = `
                <div class="flex-shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        ${type === 'success' ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>' : 
                          type === 'error' ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>' : 
                          type === 'warning' ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01"/>' : 
                          '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01"/>'}
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="truncate">${message}</p>
                </div>
            `;
            
            container.appendChild(notification);
            
            // Animate in
            setTimeout(() => {
                notification.classList.remove('translate-x-full');
            }, 10);

            if (autoHide) {
                const duration = type === 'error' ? 8000 : 3000;
                setTimeout(() => {
                    notification.classList.add('translate-x-full', 'opacity-0');
                    setTimeout(() => notification.remove(), 300);
                }, duration);
            }
        },
        
        // Manual Entry Submission
        async submitManualEntry() {
            if (!this.manualEntry.employeeId) {
                this.showNotification('Mohon pilih karyawan', 'warning');
                return;
            }
            
            this.showNotification('Mengirim entri manual...', 'info');
            
            try {
                const response = await fetch('/attendance/api/manual-entry', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.manualEntry)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    this.showNotification(result.message, 'success');
                    this.manualEntry.employeeId = '';
                    this.manualEntry.notes = '';
                    this.loadCurrentStatus(); // Refresh status after manual entry
                } else {
                    this.showNotification(result.message || 'Gagal mengirim entri manual', 'error');
                }
            } catch (error) {
                console.error('Kesalahan entri manual:', error);
                this.showNotification('Terjadi kesalahan saat mengirim entri manual', 'error');
            }
        },
        
        // Initialize Audio Context
        initializeAudioContext() {
            if (!this.audioContext) {
                this.audioContext = new (window.AudioContext || window.webkitAudioContext)();
                console.log('AudioContext initialized');
            }
        },
        
        // Play Sound (example)
        playSound(frequency, duration) {
            if (!this.audioContext) return;
            
            const oscillator = this.audioContext.createOscillator();
            const gainNode = this.audioContext.createGain();
            
            oscillator.connect(gainNode);
            gainNode.connect(this.audioContext.destination);
            
            oscillator.type = 'sine';
            oscillator.frequency.value = frequency;
            gainNode.gain.setValueAtTime(0, this.audioContext.currentTime);
            gainNode.gain.linearRampToValueAtTime(0.5, this.audioContext.currentTime + 0.01);
            gainNode.gain.exponentialRampToValueAtTime(0.001, this.audioContext.currentTime + duration / 1000);
            
            oscillator.start(this.audioContext.currentTime);
            oscillator.stop(this.audioContext.currentTime + duration / 1000);
        },
        
        // Load Today's Schedule and Attendance Status
        async loadTodayScheduleAndStatus() {
            try {
                const response = await fetch('/attendance/api/today-schedule', {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });
                
                const result = await response.json();
                
                if (result.success) {
                    this.schedule = result.data.schedule;
                    this.attendance = result.data.attendance;
                    this.todayFormatted = result.data.today_formatted;
                } else {
                    console.error('Failed to load schedule:', result.message);
                    this.showNotification('Gagal memuat jadwal kerja', 'warning');
                }
            } catch (error) {
                console.error('Load schedule error:', error);
                this.showNotification('Terjadi kesalahan saat memuat jadwal', 'error');
            }
        },
        
        // Refresh Schedule and Status
        async refreshScheduleStatus() {
            this.showNotification('Memperbarui status...', 'info');
            await this.loadTodayScheduleAndStatus();
            this.showNotification('Status berhasil diperbarui!', 'success');
        },
        
    };
}
</script>
@endpush

@push('styles')
<style>
/* Camera overlay pulse animation */
.pulse-border {
    animation: pulse-border 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse-border {
    0%, 100% { border-color: rgba(34, 197, 94, 0.5); }
    50% { border-color: rgba(34, 197, 94, 1); }
}

/* Enhanced camera placeholder */
.camera-placeholder {
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
}

/* Dark mode camera placeholder */
@media (prefers-color-scheme: dark) {
    .camera-placeholder {
        background: linear-gradient(135deg, #334155 0%, #1e293b 100%);
    }
}
</style>
@endpush
