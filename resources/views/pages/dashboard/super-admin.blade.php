@extends('layouts.authenticated-unified')

@section('title', 'Pusat Komando Sistem')

@push('head')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endpush

@section('page-content')
<!-- Page Header -->
<div class="mb-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Pusat Komando Sistem</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Pemantauan dan administrasi sistem lengkap</p>
        </div>
        <x-ui.button variant="secondary" onclick="refreshData()"
            class="bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4"/><path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4"/></svg>
            Segarkan
        </x-ui.button>
    </div>
</div>

            <!-- System Health Status -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
                <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-green-600 rounded-lg shadow-md">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <span class="text-xs bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 px-2 py-1 rounded-full">ONLINE</span>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-1">Kesehatan Sistem</h3>
                    <p class="text-green-600 text-sm font-medium">Semua sistem beroperasi</p>
                </x-ui.card>

                <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-blue-600 rounded-lg shadow-md">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                        <span class="text-sm text-blue-600">{{ $dashboardData['system_health']['active_sessions'] ?? 0 }} aktif</span>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $dashboardData['realtime_status']['total_employees'] ?? 0 }}</h3>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">Total Pengguna</p>
                </x-ui.card>

                <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-emerald-600 rounded-lg shadow-md">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <span class="text-sm text-emerald-600">{{ $dashboardData['realtime_status']['attendance_rate'] ?? 0 }}%</span>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $dashboardData['realtime_status']['checked_in_today'] ?? 0 }}</h3>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">Hadir Hari Ini</p>
                </x-ui.card>

                <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-amber-600 rounded-lg shadow-md">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        @if(($dashboardData['realtime_status']['late_arrivals'] ?? 0) > 0)
                        <span class="text-xs bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200 px-2 py-1 rounded-full">Peringatan</span>
                        @endif
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $dashboardData['realtime_status']['late_arrivals'] ?? 0 }}</h3>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">Kedatangan Terlambat</p>
                </x-ui.card>

                <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-purple-600 rounded-lg shadow-md">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                        </div>
                        @if(($dashboardData['leave_management']['pending_requests'] ?? 0) > 0)
                        <span class="text-xs bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200 px-2 py-1 rounded-full">{{ $dashboardData['leave_management']['pending_requests'] }}</span>
                        @endif
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $dashboardData['leave_management']['pending_requests'] ?? 0 }}</h3>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">Permintaan Tertunda</p>
                </x-ui.card>
            </div>

            <!-- Live System Monitoring -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                <x-ui.card class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Aktivitas Karyawan Langsung</h3>
                            <p class="text-gray-600 dark:text-gray-400">Pemantauan absensi real-time</p>
                        </div>
                        <div class="flex items-center space-x-2">
                            <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                            <span class="text-sm text-green-600 dark:text-green-400 font-medium">Langsung</span>
                        </div>
                    </div>
                    
                    <div class="space-y-3">
                        @php
                        $recentCheckIns = [
                            ['name' => 'Dr. Sarah Ahmad', 'time' => '08:15', 'status' => 'check-in', 'method' => 'face', 'department' => 'Mathematics'],
                            ['name' => 'Prof. Ahmad Rahman', 'time' => '08:30', 'status' => 'check-in', 'method' => 'face', 'department' => 'Physics'],
                            ['name' => 'Ms. Fatimah Ali', 'time' => '09:45', 'status' => 'late', 'method' => 'manual', 'department' => 'English'],
                            ['name' => 'Mr. Hassan Omar', 'time' => '10:15', 'status' => 'check-out', 'method' => 'face', 'department' => 'Chemistry'],
                            ['name' => 'Dr. Amira Hassan', 'time' => '11:00', 'status' => 'check-in', 'method' => 'face', 'department' => 'Biology']
                        ];
                        @endphp
                        
                        @foreach($recentCheckIns as $checkIn)
                        <div class="flex items-center justify-between p-4 bg-gray-100 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                            <div class="flex items-center space-x-4">
                                <div class="w-12 h-12 bg-blue-600 rounded-full flex items-center justify-center text-white font-bold text-sm">
                                    {{ substr($checkIn['name'], 0, 2) }}
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $checkIn['name'] }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $checkIn['department'] }}</div>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ $checkIn['time'] }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ date('M d') }}</div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    
                    <div class="mt-4 text-center">
                        <x-ui.button variant="secondary" class="bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            Lihat Semua Aktivitas
                        </x-ui.button>
                    </div>
                </x-ui.card>

                <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-6">Peringatan Sistem</h3>
                    <div class="space-y-4">
                        <div class="flex items-start space-x-3 p-4 bg-red-100 rounded-lg border border-red-200 dark:bg-red-900 dark:border-red-700">
                            <div class="w-6 h-6 bg-red-600 rounded-full flex items-center justify-center shadow-md">
                                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01"/></svg>
                            </div>
                            <div>
                                <p class="font-semibold text-red-800 dark:text-red-200 text-sm">3 Gagal Check-in</p>
                                <p class="text-xs text-red-600 dark:text-red-400">Masalah pengenalan wajah • 5 menit yang lalu</p>
                            </div>
                        </div>

                        <div class="flex items-start space-x-3 p-4 bg-amber-100 rounded-lg border border-amber-200 dark:bg-amber-900 dark:border-amber-700">
                            <div class="w-6 h-6 bg-amber-600 rounded-full flex items-center justify-center shadow-md">
                                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <div>
                                <p class="font-semibold text-amber-800 dark:text-amber-200 text-sm">5 Kedatangan Terlambat</p>
                                <p class="text-xs text-amber-600 dark:text-amber-400">Di atas ambang normal • 15 menit yang lalu</p>
                            </div>
                        </div>

                        <div class="flex items-start space-x-3 p-4 bg-blue-100 rounded-lg border border-blue-200 dark:bg-blue-900 dark:border-blue-700">
                            <div class="w-6 h-6 bg-blue-600 rounded-full flex items-center justify-center shadow-md">
                                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                            </div>
                            <div>
                                <p class="font-semibold text-blue-800 dark:text-blue-200 text-sm">Impor Hari Libur Selesai</p>
                                <p class="text-xs text-blue-600 dark:text-blue-400">41 hari libur ditambahkan untuk 2025 • 1 jam yang lalu</p>
                            </div>
                        </div>

                        <div class="flex items-start space-x-3 p-4 bg-green-100 rounded-lg border border-green-200 dark:bg-green-900 dark:border-green-700">
                            <div class="w-6 h-6 bg-green-600 rounded-full flex items-center justify-center shadow-md">
                                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <div>
                                <p class="font-semibold text-green-800 dark:text-green-200 text-sm">Backup Selesai</p>
                                <p class="text-xs text-green-600 dark:text-green-400">Backup database berhasil • 2 jam yang lalu</p>
                            </div>
                        </div>

                        <div class="pt-2">
                            <x-ui.button variant="secondary" class="w-full bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600">
                                Lihat Semua Peringatan
                            </x-ui.button>
                        </div>
                    </div>
                </x-ui.card>
            </div>

            <!-- Enhanced Interactive Data Dashboard -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-8">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-xl font-semibold text-slate-800 dark:text-white">Ikhtisar Absensi Hari Ini</h3>
                        <p class="text-slate-600 dark:text-slate-400">Pelacakan absensi real-time dengan wawasan detail</p>
                    </div>
                    <div class="flex space-x-2">
                        <span class="px-3 py-1 text-xs text-white bg-gradient-to-r from-green-500 to-emerald-600 rounded-full shadow-lg">Langsung</span>
                        <x-ui.button variant="secondary" size="sm">Ekspor</x-ui.button>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <div class="p-4 bg-gradient-to-br from-green-500/10 to-emerald-500/10 rounded-lg border border-green-500/20">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $dashboardData['realtime_status']['checked_in_today'] ?? 24 }}</div>
                                <div class="text-sm text-green-500 dark:text-green-300">Hadir</div>
                            </div>
                            <div class="w-8 h-8 bg-gradient-to-br from-green-500 to-emerald-600 rounded-lg flex items-center justify-center shadow-lg">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            </div>
                        </div>
                    </div>
                    
                    <div class="p-4 bg-gradient-to-br from-amber-500/10 to-orange-500/10 rounded-lg border border-amber-500/20">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ $dashboardData['realtime_status']['late_arrivals'] ?? 3 }}</div>
                                <div class="text-sm text-amber-500 dark:text-amber-300">Terlambat</div>
                            </div>
                            <div class="w-8 h-8 bg-gradient-to-br from-amber-500 to-orange-600 rounded-lg flex items-center justify-center shadow-lg">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                        </div>
                    </div>
                    
                    <div class="p-4 bg-gradient-to-br from-red-500/10 to-rose-500/10 rounded-lg border border-red-500/20">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $dashboardData['realtime_status']['absent_today'] ?? 2 }}</div>
                                <div class="text-sm text-red-500 dark:text-red-300">Tidak Hadir</div>
                            </div>
                            <div class="w-8 h-8 bg-gradient-to-br from-red-500 to-rose-600 rounded-lg flex items-center justify-center shadow-lg">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </div>
                        </div>
                    </div>
                    
                    <div class="p-4 bg-gradient-to-br from-gray-500/10 to-slate-500/10 rounded-lg border border-gray-500/20">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-2xl font-bold text-slate-600 dark:text-slate-400">{{ ($dashboardData['realtime_status']['total_employees'] ?? 45) - ($dashboardData['realtime_status']['checked_in_today'] ?? 24) - ($dashboardData['realtime_status']['late_arrivals'] ?? 3) - ($dashboardData['realtime_status']['absent_today'] ?? 2) }}</div>
                                <div class="text-sm text-slate-500 dark:text-slate-300">Tertunda</div>
                            </div>
                            <div class="w-8 h-8 bg-gradient-to-br from-gray-500 to-slate-600 rounded-lg flex items-center justify-center shadow-lg">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="overflow-hidden border border-white/20 rounded-lg">
                    <table class="w-full">
                        <thead class="bg-white/10 backdrop-blur-sm">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Karyawan</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Check In</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Metode</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Lokasi</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white/5 backdrop-blur-sm divide-y divide-white/10">
                            @php
                            $attendanceData = [
                                ['name' => 'Dr. Sarah Ahmad', 'dept' => 'Matematika', 'checkin' => '08:15', 'status' => 'present', 'method' => 'face', 'location' => 'Gedung Utama', 'confidence' => '98%'],
                                ['name' => 'Prof. Ahmad Rahman', 'dept' => 'Fisika', 'checkin' => '08:30', 'status' => 'present', 'method' => 'face', 'location' => 'Lab Fisika', 'confidence' => '96%'],
                                ['name' => 'Ms. Fatimah Ali', 'dept' => 'Bahasa Inggris', 'checkin' => '09:45', 'status' => 'late', 'method' => 'manual', 'location' => 'Kantor Admin', 'confidence' => 'N/A'],
                                ['name' => 'Mr. Hassan Omar', 'dept' => 'Kimia', 'checkin' => '08:50', 'status' => 'present', 'method' => 'face', 'location' => 'Lab Kimia', 'confidence' => '97%'],
                                ['name' => 'Dr. Amira Hassan', 'dept' => 'Biologi', 'checkin' => '08:20', 'status' => 'present', 'method' => 'face', 'location' => 'Lab Biologi', 'confidence' => '99%'],
                                ['name' => 'Prof. Ali Mansour', 'dept' => 'Sejarah', 'checkin' => '09:15', 'status' => 'late', 'method' => 'face', 'location' => 'Sayap Humaniora', 'confidence' => '95%']
                            ];
                            @endphp
                            
                            @foreach($attendanceData as $record)
                            <tr class="hover:bg-white/5 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white text-sm font-bold">
                                            {{ substr($record['name'], 0, 2) }}
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-slate-800 dark:text-white">{{ $record['name'] }}</div>
                                            <div class="text-xs text-slate-500 dark:text-slate-400">{{ $record['dept'] }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-slate-800 dark:text-white">{{ $record['checkin'] }}</div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400">{{ now()->format('M d, Y') }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    @if($record['status'] === 'present')
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold text-white bg-gradient-to-r from-green-500 to-emerald-600 shadow-lg">
                                        Hadir
                                    </span>
                                    @else
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold text-white bg-gradient-to-r from-amber-500 to-orange-600 shadow-lg">
                                        Terlambat
                                    </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center text-sm">
                                        @if($record['method'] === 'face')
                                            <div class="flex items-center text-green-600 dark:text-green-400">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                <div>
                                                    <div class="text-xs font-medium text-slate-800 dark:text-white">Face ID</div>
                                                    <div class="text-xs text-slate-500 dark:text-slate-400">{{ $record['confidence'] }}</div>
                                                </div>
                                            </div>
                                        @else
                                            <div class="flex items-center text-amber-600 dark:text-amber-400">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                <span class="text-xs text-slate-800 dark:text-white">Manual</span>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-slate-600 dark:text-slate-400">{{ $record['location'] }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex space-x-2">
                                        <x-ui.button variant="secondary" size="sm" title="Lihat Detail">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </x-ui.button>
                                        <x-ui.button variant="secondary" size="sm" title="Edit Catatan">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </x-ui.button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-4 p-6 bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl shadow-lg">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4 text-sm text-slate-600 dark:text-slate-400">
                            <span>Menampilkan 6 dari {{ $dashboardData['realtime_status']['total_employees'] ?? 45 }} karyawan</span>
                            <span class="hidden sm:inline">•</span>
                            <span class="hidden sm:flex items-center">
                                <span class="w-2 h-2 bg-green-500 rounded-full mr-1"></span>
                                {{ $dashboardData['realtime_status']['checked_in_today'] ?? 24 }} Hadir
                            </span>
                            <span class="hidden md:flex items-center">
                                <span class="w-2 h-2 bg-amber-500 rounded-full mr-1"></span>
                                {{ $dashboardData['realtime_status']['late_arrivals'] ?? 3 }} Terlambat
                            </span>
                            <span class="hidden lg:flex items-center">
                                <span class="w-2 h-2 bg-red-500 rounded-full mr-1"></span>
                                {{ $dashboardData['realtime_status']['absent_today'] ?? 2 }} Tidak Hadir
                            </span>
                        </div>
                        <div class="flex space-x-2">
                            <x-ui.button variant="primary">Ekspor Laporan</x-ui.button>
                            <x-ui.button variant="secondary">Lihat Semua ({{ $dashboardData['realtime_status']['total_employees'] ?? 45 }})</x-ui.button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- System Management Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-8">
                <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                    <h3 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Kesehatan Sistem</h3>
                    <div class="space-y-3 text-slate-700 dark:text-slate-300">
                        <div class="flex justify-between items-center"><span class="font-medium">Database</span><span class="px-3 py-1 text-xs text-white bg-gradient-to-r from-green-500 to-emerald-600 shadow-lg">{{ $dashboardData['system_health']['database_status'] ?? 'sehat' }}</span></div>
                        <div class="flex justify-between items-center"><span class="font-medium">Pengenalan Wajah</span><span class="px-3 py-1 text-xs text-white bg-gradient-to-r from-green-500 to-emerald-600 shadow-lg">{{ $dashboardData['system_health']['face_recognition_status'] ?? 'sehat' }}</span></div>
                        <div class="flex justify-between items-center"><span class="font-medium">Integrasi Hari Libur</span>                        <span class="px-3 py-1 text-xs text-white bg-emerald-600">aktif</span></div>
                        <div class="flex justify-between items-center"><span class="font-medium">Sesi Aktif</span><span class="font-semibold text-slate-800 dark:text-white">{{ $dashboardData['system_health']['active_sessions'] ?? 0 }}</span></div>
                    </div>
                </div>

                <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                    <h3 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Ikhtisar Sekolah</h3>
                    <div class="space-y-3 text-slate-700 dark:text-slate-300">
                        <div class="flex justify-between"><span class="font-medium">Total Guru</span><span class="font-semibold text-slate-800 dark:text-white">{{ $dashboardData['school_overview']['total_teachers'] ?? 0 }}</span></div>
                        <div class="flex justify-between"><span class="font-medium">Total Staf</span><span class="font-semibold text-slate-800 dark:text-white">{{ $dashboardData['school_overview']['total_staff'] ?? 0 }}</span></div>
                        <div class="flex justify-between"><span class="font-medium">Cuti Hari Ini</span><span class="font-semibold text-amber-600 dark:text-amber-400">{{ $dashboardData['school_overview']['on_leave_today'] ?? 0 }}</span></div>
                        <div class="flex justify-between"><span class="font-medium">Perekrutan Baru</span><span class="font-semibold text-green-600 dark:text-green-400">{{ $dashboardData['school_overview']['new_hires_this_month'] ?? 0 }}</span></div>
                    </div>
                </div>

                <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                    <h3 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Analitik Data</h3>
                    <div class="space-y-3 text-slate-700 dark:text-slate-300">
                        <div class="flex justify-between"><span class="font-medium">Total Catatan</span><span class="font-semibold text-slate-800 dark:text-white">{{ number_format($dashboardData['analytics']['total_attendance_records'] ?? 1250) }}</span></div>
                        <div class="flex justify-between"><span class="font-medium">Hari Libur Tahun Ini</span><span class="font-semibold text-purple-600 dark:text-purple-400">{{ $dashboardData['analytics']['holidays_this_year'] ?? 41 }}</span></div>
                        <div class="flex justify-between"><span class="font-medium">Hari Kerja BTD</span><span class="font-semibold text-blue-600 dark:text-blue-400">{{ $dashboardData['analytics']['working_days_mtd'] ?? 23 }}</span></div>
                        <div class="flex justify-between"><span class="font-medium">Rata-rata Absensi</span><span class="font-semibold text-green-600 dark:text-green-400">{{ $dashboardData['analytics']['avg_attendance_rate'] ?? 85 }}%</span></div>
                    </div>
                </div>

                <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                    <h3 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Kinerja</h3>
                    <div class="space-y-3 text-slate-700 dark:text-slate-300">
                        <div class="flex justify-between items-center"><span class="font-medium">Uptime Sistem</span><div class="flex items-center"><div class="w-16 bg-white/20 rounded-full h-1.5"><div class="bg-gradient-to-r from-green-500 to-emerald-600 h-1.5 rounded-full" style="width: 99%"></div></div><span class="text-xs font-semibold text-green-600 dark:text-green-400">99%</span></div></div>
                        <div class="flex justify-between items-center"><span class="font-medium">Waktu Respon</span><div class="flex items-center"><div class="w-16 bg-white/20 rounded-full h-1.5"><div class="bg-gradient-to-r from-blue-500 to-cyan-500 h-1.5 rounded-full" style="width: 92%"></div></div><span class="text-xs font-semibold text-blue-600 dark:text-blue-400">92%</span></div></div>
                        <div class="flex justify-between items-center"><span class="font-medium">Keberhasilan Face API</span><div class="flex items-center"><div class="w-16 bg-gray-200 dark:bg-gray-700 rounded-full h-1.5"><div class="bg-purple-600 h-1.5 rounded-full" style="width: 96%"></div></div><span class="text-xs font-semibold text-purple-600 dark:text-purple-400">96%</span></div></div>
                        <div class="flex justify-between items-center"><span class="font-medium">Tingkat Error</span><span class="text-xs font-semibold text-red-600 dark:text-red-400">< 0.1%</span></div>
                    </div>
                </div>
            </div>

            <!-- Administration Panel -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                    <h3 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Administrasi Sistem</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <a href="{{ route('employees.index') }}" class="group p-4 bg-blue-500/10 hover:bg-blue-500/20 rounded-lg transition-colors text-center">
                            <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-xl mx-auto mb-2 flex items-center justify-center shadow-lg">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                            </div>
                            <p class="text-sm font-medium text-blue-700 dark:text-blue-300">Manajemen Karyawan</p>
                        </a>

                        <a href="{{ route('reports.attendance') }}" class="group p-4 bg-green-500/10 hover:bg-green-500/20 rounded-lg transition-colors text-center">
                            <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl mx-auto mb-2 flex items-center justify-center shadow-lg">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                            </div>
                            <p class="text-sm font-medium text-green-700 dark:text-green-300">Analitik & Laporan</p>
                        </a>

                        <a href="{{ route('holidays.index') }}" class="group p-4 bg-purple-500/10 hover:bg-purple-500/20 rounded-lg transition-colors text-center">
                            <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-pink-500 rounded-xl mx-auto mb-2 flex items-center justify-center shadow-lg">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                            </div>
                            <p class="text-sm font-medium text-purple-700 dark:text-purple-300">Manajemen Hari Libur</p>
                        </a>

                        <a href="{{ route('security.dashboard') }}" class="group p-4 bg-red-500/10 hover:bg-red-500/20 rounded-lg transition-colors text-center">
                            <div class="w-10 h-10 bg-gradient-to-br from-red-500 to-rose-600 rounded-xl mx-auto mb-2 flex items-center justify-center shadow-lg">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                            </div>
                            <p class="text-sm font-medium text-red-700 dark:text-red-300">Monitor Keamanan</p>
                        </a>
                    </div>
                </div>

                <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-semibold text-slate-800 dark:text-white">Manajemen Karyawan</h3>
                        <x-ui.button variant="primary" size="sm">
                            <svg class="w-5 h-5 mr-2 group-hover:rotate-90 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                            Tambah Karyawan
                        </x-ui.button>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-3 mb-4">
                        <div class="p-3 bg-gradient-to-br from-green-500/10 to-emerald-500/10 rounded-lg border border-green-500/20">
                            <div class="text-center">
                                <div class="text-2xl font-bold text-green-600 dark:text-green-400">24</div>
                                <div class="text-xs text-green-500 dark:text-green-300">Aktif Hari Ini</div>
                            </div>
                        </div>
                        <div class="p-3 bg-gradient-to-br from-blue-500/10 to-cyan-500/10 rounded-lg border border-blue-500/20">
                            <div class="text-center">
                                <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">45</div>
                                <div class="text-xs text-blue-500 dark:text-blue-300">Total Staf</div>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-3">
                        @php
                        $employeeActions = [
                            ['name' => 'Guru Baru Ditambahkan', 'user' => 'Dr. Fatimah Ali', 'action' => 'added', 'time' => '2 menit yang lalu'],
                            ['name' => 'Izin Diperbarui', 'user' => 'Prof. Ahmad Rahman', 'action' => 'modified', 'time' => '15 menit yang lalu'],
                            ['name' => 'Profil Diverifikasi', 'user' => 'Ms. Sarah Hassan', 'action' => 'verified', 'time' => '30 menit yang lalu'],
                            ['name' => 'Jadwal Ditugaskan', 'user' => 'Mr. Hassan Omar', 'action' => 'assigned', 'time' => '1 jam yang lalu']
                        ];
                        @endphp
                        
                        @foreach($employeeActions as $action)
                        <div class="flex items-center justify-between p-3 bg-white/10 rounded-lg hover:shadow-md transition-all duration-200">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 bg-gradient-to-br from-purple-500 to-pink-500 rounded-full flex items-center justify-center shadow-lg">
                                    {{ substr($action['user'], 0, 2) }}
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-slate-800 dark:text-white">{{ $action['name'] }}</div>
                                    <div class="text-xs text-slate-600 dark:text-slate-400">{{ $action['user'] }}</div>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                                    {{ $action['action'] === 'added' ? 'text-white bg-emerald-600' : 
                                       ($action['action'] === 'verified' ? 'text-white bg-blue-600' : 
                                        'text-white bg-gray-600') }}">
                                    {{ ucfirst($action['action']) }}
                                </span>
                                <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ $action['time'] }}</div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <div class="mt-4 text-center">
                        <x-ui.button variant="secondary">
                            <svg class="w-5 h-5 mr-2 group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197"/></svg>
                            Kelola Semua Karyawan
                        </x-ui.button>
                    </div>
                </div>

                <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                    <h3 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Informasi Sistem</h3>
                    <div class="space-y-3 text-slate-700 dark:text-slate-300">
                        <div class="flex justify-between"><span class="font-medium">Versi Laravel</span><span class="font-mono text-sm text-slate-800 dark:text-white">12.x</span></div>
                        <div class="flex justify-between"><span class="font-medium">Versi PHP</span><span class="font-mono text-sm text-slate-800 dark:text-white">8.3.6</span></div>
                        <div class="flex justify-between"><span class="font-medium">Ukuran Database</span><span class="font-mono text-sm text-slate-800 dark:text-white">{{ $dashboardData['system_info']['database_size'] ?? '125 MB' }}</span></div>
                        <div class="flex justify-between"><span class="font-medium">Penggunaan Disk</span><span class="font-mono text-sm text-slate-800 dark:text-white">{{ $dashboardData['system_info']['disk_usage'] ?? '2.4 GB' }}</span></div>
                        <div class="flex justify-between"><span class="font-medium">Backup Terakhir</span><span class="font-mono text-sm text-slate-800 dark:text-white">{{ $dashboardData['system_info']['last_backup'] ?? '3j yang lalu' }}</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function superAdminDashboard() {
            return {
                initDashboard() {
                    this.initChart();
                },

                initChart() {
                    if (typeof Chart === 'undefined') {
                        console.warn('Chart.js not loaded yet, retrying...');
                        setTimeout(() => this.initChart(), 500);
                        return;
                    }
                    
                    const chartElement = document.getElementById('systemPerformanceChart');
                    if (!chartElement) {
                        console.warn('Chart element not found');
                        return;
                    }
                    
                    const ctx = chartElement.getContext('2d');
                    const chartData = @json($dashboardData['attendance_trends']['daily_trends'] ?? []);
                    
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: chartData.map(d => d.date) || ['Minggu 1', 'Minggu 2', 'Minggu 3', 'Minggu 4'],
                            datasets: [{
                                label: 'Uptime Sistem',
                                data: [99.9, 99.8, 99.9, 100],
                                borderColor: 'rgb(16, 185, 129)',
                                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                                borderWidth: 2,
                                fill: true,
                                tension: 0.3
                            }, {
                                label: 'Aktivitas Pengguna',
                                data: chartData.map(d => d.present_count) || [85, 92, 78, 96],
                                borderColor: 'rgb(59, 130, 246)',
                                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                borderWidth: 2,
                                fill: false,
                                tension: 0.3
                            }, {
                                label: 'Beban Sistem',
                                data: [45, 52, 38, 41],
                                borderColor: 'rgb(245, 158, 11)',
                                backgroundColor: 'rgba(245, 158, 11, 0.1)',
                                borderWidth: 2,
                                fill: false,
                                tension: 0.3
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: true,
                                    position: 'top',
                                    labels: {
                                        boxWidth: 12,
                                        padding: 15,
                                        color: '#cbd5e0'
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    grid: {
                                        color: 'rgba(255, 255, 255, 0.1)'
                                    },
                                    ticks: {
                                        color: '#cbd5e0'
                                    }
                                },
                                x: {
                                    grid: {
                                        display: false
                                    },
                                    ticks: {
                                        color: '#cbd5e0'
                                    }
                                }
                            }
                        }
                    });
                }
            }
        }
    </script>
@endsection