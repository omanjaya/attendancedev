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
            <p class="dashboard-page-desc">Pemantauan dan administrasi sistem lengkap</p>
        </div>
        <x-ui.button variant="secondary" onclick="refreshData()" class="btn-analytics">
            <x-icons.refresh class="w-5 h-5 mr-2" />
            Segarkan
        </x-ui.button>
    </div>
</div>

            <!-- System Health Status -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
                <x-ui.card>
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-green-600 rounded-lg shadow-md">
                            <x-icons.shield class="w-6 h-6 text-white" />
                        </div>
                        <span class="badge-online">ONLINE</span>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-1">Kesehatan Sistem</h3>
                    <p class="text-green-600 text-sm font-medium">Semua sistem beroperasi</p>
                </x-ui.card>

                <x-ui.card>
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-blue-600 rounded-lg shadow-md">
                            <x-icons.users class="w-6 h-6 text-white" />
                        </div>
                        <span class="dashboard-status-text-blue">{{ $dashboardData['system_health']['active_sessions'] ?? 0 }} aktif</span>
                    </div>
                    <h3 class="metric-heading">{{ $dashboardData['realtime_status']['total_employees'] ?? 0 }}</h3>
                    <p class="dashboard-metric-desc">Total Pengguna</p>
                </x-ui.card>

                <x-ui.card>
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-emerald-600 rounded-lg shadow-md">
                            <x-icons.check-circle class="w-6 h-6 text-white" />
                        </div>
                        <span class="dashboard-status-text-emerald">{{ $dashboardData['realtime_status']['attendance_rate'] ?? 0 }}%</span>
                    </div>
                    <h3 class="metric-heading">{{ $dashboardData['realtime_status']['checked_in_today'] ?? 0 }}</h3>
                    <p class="dashboard-metric-desc">Hadir Hari Ini</p>
                </x-ui.card>

                <x-ui.card>
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-amber-600 rounded-lg shadow-md">
                            <x-icons.clock class="w-6 h-6 text-white" />
                        </div>
                        @if(($dashboardData['realtime_status']['late_arrivals'] ?? 0) > 0)
                        <span class="badge-warning">Peringatan</span>
                        @endif
                    </div>
                    <h3 class="metric-heading">{{ $dashboardData['realtime_status']['late_arrivals'] ?? 0 }}</h3>
                    <p class="dashboard-metric-desc">Kedatangan Terlambat</p>
                </x-ui.card>

                <x-ui.card>
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-purple-600 rounded-lg shadow-md">
                            <x-icons.calendar class="w-6 h-6 text-white" />
                        </div>
                        @if(($dashboardData['leave_management']['pending_requests'] ?? 0) > 0)
                        <span class="badge-purple">{{ $dashboardData['leave_management']['pending_requests'] }}</span>
                        @endif
                    </div>
                    <h3 class="metric-heading">{{ $dashboardData['leave_management']['pending_requests'] ?? 0 }}</h3>
                    <p class="dashboard-metric-desc">Permintaan Tertunda</p>
                </x-ui.card>
            </div>

            <!-- Live System Monitoring -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                <x-ui.card class="lg:col-span-2">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="dashboard-section-title">Aktivitas Karyawan Langsung</h3>
                            <p class="dashboard-section-desc">Pemantauan absensi real-time</p>
                        </div>
                        <div class="flex items-center space-x-2">
                            <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                            <span class="text-sm text-green-600 dark:text-green-400 font-medium">Langsung</span>
                        </div>
                    </div>
                    
                    <div class="space-y-3">
                        @php
                        // Get real recent check-ins from database
                        $recentCheckIns = \App\Models\Attendance::with('employee')
                            ->whereDate('date', today())
                            ->whereNotNull('check_in_time')
                            ->orderBy('check_in_time', 'desc')
                            ->limit(5)
                            ->get()
                            ->map(function($attendance) {
                                return [
                                    'name' => $attendance->employee->first_name . ' ' . $attendance->employee->last_name,
                                    'time' => $attendance->check_in_time ? $attendance->check_in_time->format('H:i') : 'N/A',
                                    'status' => $attendance->status === 'late' ? 'late' : 'check-in',
                                    'method' => $attendance->metadata && isset(json_decode($attendance->metadata, true)['method']) ? 
                                               json_decode($attendance->metadata, true)['method'] : 'manual',
                                    'department' => $attendance->employee->department ?? 'General'
                                ];
                            })->toArray();
                        
                        // If no data available, show empty state instead of fake data
                        if (empty($recentCheckIns)) {
                            $recentCheckIns = [];
                        }
                        @endphp
                        
                        @forelse($recentCheckIns as $checkIn)
                        <div class="dashboard-activity-item">
                            <div class="flex items-center space-x-4">
                                <div class="dashboard-avatar bg-blue-600">
                                    {{ substr($checkIn['name'], 0, 2) }}
                                </div>
                                <div class="ml-4">
                                    <div class="dashboard-employee-name">{{ $checkIn['name'] }}</div>
                                    <div class="dashboard-employee-dept">{{ $checkIn['department'] }}</div>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="dashboard-time-display">{{ $checkIn['time'] }}</div>
                                <div class="dashboard-date-display">{{ date('M d') }}</div>
                            </div>
                        </div>
                        @empty
                        <div class="text-center py-8">
                            <div class="text-gray-400 dark:text-gray-500">
                                <svg class="w-12 h-12 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <p class="text-sm">Tidak ada aktivitas check-in hari ini</p>
                            </div>
                        </div>
                        @endforelse
                    </div>
                    
                    <div class="mt-4 text-center">
                        <x-ui.button variant="secondary" class="dashboard-view-all-btn">
                            <x-icons.refresh class="w-5 h-5 mr-2" />
                            Lihat Semua Aktivitas
                        </x-ui.button>
                    </div>
                </x-ui.card>

                <x-ui.card>
                    <h3 class="dashboard-section-title mb-6">Peringatan Sistem</h3>
                    <div class="space-y-4">
                        @php
                        // Get real system alerts from dashboard service
                        $systemAlerts = app(\App\Services\DashboardService::class)->getSystemAlerts();
                        @endphp
                        
                        @forelse($systemAlerts as $alert)
                        <div class="dashboard-alert-{{ $alert['color'] }}">
                            <div class="dashboard-alert-icon dashboard-alert-icon-{{ $alert['color'] }}">
                                @switch($alert['icon'])
                                    @case('info')
                                        <x-icons.info class="w-3 h-3 text-white" />
                                        @break
                                    @case('clock')
                                        <x-icons.clock class="w-3 h-3 text-white" />
                                        @break
                                    @case('check')
                                        <x-icons.check class="w-3 h-3 text-white" />
                                        @break
                                    @case('calendar')
                                        <x-icons.calendar class="w-3 h-3 text-white" />
                                        @break
                                    @case('shield')
                                        <x-icons.shield class="w-3 h-3 text-white" />
                                        @break
                                    @default
                                        <x-icons.info class="w-3 h-3 text-white" />
                                @endswitch
                            </div>
                            <div>
                                <p class="dashboard-alert-title-{{ $alert['color'] }}">{{ $alert['title'] }}</p>
                                <p class="dashboard-alert-desc-{{ $alert['color'] }}">{{ $alert['description'] }}</p>
                            </div>
                        </div>
                        @empty
                        <div class="text-center py-8">
                            <div class="text-gray-400 dark:text-gray-500">
                                <svg class="w-12 h-12 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <p class="text-sm">Tidak ada peringatan sistem saat ini</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Semua sistem beroperasi normal</p>
                            </div>
                        </div>
                        @endforelse

                        <div class="pt-2">
                            <x-ui.button variant="secondary" class="w-full dashboard-view-all-btn">
                                Lihat Semua Peringatan
                            </x-ui.button>
                        </div>
                    </div>
                </x-ui.card>
            </div>

            <!-- Enhanced Interactive Data Dashboard -->
            <div class="dashboard-enhanced-container">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="dashboard-enhanced-title">Ikhtisar Absensi Hari Ini</h3>
                        <p class="dashboard-enhanced-desc">Pelacakan absensi real-time dengan wawasan detail</p>
                    </div>
                    <div class="flex space-x-2">
                        <span class="dashboard-live-badge">Langsung</span>
                        <x-ui.button variant="secondary" size="sm">Ekspor</x-ui.button>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <div class="p-4 bg-gradient-to-br from-green-500/10 to-emerald-500/10 rounded-lg border border-green-500/20">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $dashboardData['realtime_status']['checked_in_today'] ?? 0 }}</div>
                                <div class="text-sm text-green-500 dark:text-green-300">Hadir</div>
                            </div>
                            <div class="w-8 h-8 bg-gradient-to-br from-green-500 to-emerald-600 rounded-lg flex items-center justify-center shadow-lg">
                                <x-icons.check class="w-4 h-4 text-white" />
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
                                <x-icons.clock class="w-4 h-4 text-white" />
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
                                <x-icons.x class="w-4 h-4 text-white" />
                            </div>
                        </div>
                    </div>
                    
                    <div class="p-4 bg-gradient-to-br from-gray-500/10 to-slate-500/10 rounded-lg border border-gray-500/20">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-2xl font-bold text-slate-600 dark:text-slate-400">{{ ($dashboardData['realtime_status']['total_employees'] ?? 0) - ($dashboardData['realtime_status']['checked_in_today'] ?? 0) - ($dashboardData['realtime_status']['late_arrivals'] ?? 0) }}</div>
                                <div class="text-sm text-slate-500 dark:text-slate-300">Tertunda</div>
                            </div>
                            <div class="w-8 h-8 bg-gradient-to-br from-gray-500 to-slate-600 rounded-lg flex items-center justify-center shadow-lg">
                                <x-icons.clock class="w-4 h-4 text-white" />
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
                            // Get real attendance data from database
                            $attendanceData = \App\Models\Attendance::with('employee')
                                ->whereDate('date', today())
                                ->whereNotNull('check_in_time')
                                ->orderBy('check_in_time', 'desc')
                                ->limit(6)
                                ->get()
                                ->map(function($attendance) {
                                    $metadata = $attendance->metadata ? json_decode($attendance->metadata, true) : [];
                                    
                                    return [
                                        'name' => $attendance->employee->first_name . ' ' . $attendance->employee->last_name,
                                        'dept' => $attendance->employee->department ?? 'General',
                                        'checkin' => $attendance->check_in_time ? $attendance->check_in_time->format('H:i') : 'N/A',
                                        'status' => $attendance->status,
                                        'method' => $metadata['method'] ?? 'manual',
                                        'location' => $attendance->location ?? 'Unknown',
                                        'confidence' => isset($metadata['face_confidence']) ? 
                                                       round($metadata['face_confidence'] * 100) . '%' : 'N/A'
                                    ];
                                })->toArray();
                            
                            // If no real data, show empty state
                            if (empty($attendanceData)) {
                                $attendanceData = [];
                            }
                            @endphp
                            
                            @forelse($attendanceData as $record)
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
                                                <x-icons.eye class="w-4 h-4 mr-1" />
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
                            @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <div class="text-gray-400 dark:text-gray-500">
                                        <svg class="w-12 h-12 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <p class="text-sm">Tidak ada data absensi hari ini</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
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
                                {{ $dashboardData['realtime_status']['checked_in_today'] ?? 0 }} Hadir
                            </span>
                            <span class="hidden md:flex items-center">
                                <span class="w-2 h-2 bg-amber-500 rounded-full mr-1"></span>
                                {{ $dashboardData['realtime_status']['late_arrivals'] ?? 0 }} Terlambat
                            </span>
                            <span class="hidden lg:flex items-center">
                                <span class="w-2 h-2 bg-red-500 rounded-full mr-1"></span>
                                {{ ($dashboardData['realtime_status']['total_employees'] ?? 0) - ($dashboardData['realtime_status']['checked_in_today'] ?? 0) }} Tidak Hadir
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
                        <div class="flex justify-between"><span class="font-medium">Total Catatan</span><span class="font-semibold text-slate-800 dark:text-white">{{ number_format($dashboardData['realtime_status']['total_records'] ?? \App\Models\Attendance::count()) }}</span></div>
                        <div class="flex justify-between"><span class="font-medium">Hari Libur Tahun Ini</span><span class="font-semibold text-purple-600 dark:text-purple-400">{{ $dashboardData['realtime_status']['holidays_this_month'] * 12 ?? 0 }}</span></div>
                        <div class="flex justify-between"><span class="font-medium">Hari Kerja BTD</span><span class="font-semibold text-blue-600 dark:text-blue-400">{{ \Carbon\Carbon::now()->diffInDaysFiltered(function(\Carbon\Carbon $date) { return $date->isWeekday(); }, \Carbon\Carbon::now()->startOfMonth()) }}</span></div>
                        <div class="flex justify-between"><span class="font-medium">Rata-rata Absensi</span><span class="font-semibold text-green-600 dark:text-green-400">{{ $dashboardData['realtime_status']['attendance_rate'] ?? 0 }}%</span></div>
                    </div>
                </div>

                <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                    <h3 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Kinerja</h3>
                    <div class="space-y-3 text-slate-700 dark:text-slate-300">
                        @php
                            $systemHealth = $dashboardData['system_health'] ?? [];
                            $databaseStatus = $systemHealth['database_status'] ?? 'healthy';
                            $uptimePercentage = $databaseStatus === 'healthy' ? 99 : 50;
                            $responsePercentage = 92; // Could be calculated from actual response times
                            $faceApiPercentage = $dashboardData['system_health']['face_recognition_status'] === 'healthy' ? 96 : 30;
                        @endphp
                        <div class="flex justify-between items-center"><span class="font-medium">Uptime Sistem</span><div class="flex items-center"><div class="w-16 bg-white/20 rounded-full h-1.5"><div class="bg-gradient-to-r from-green-500 to-emerald-600 h-1.5 rounded-full" style="width: {{ $uptimePercentage }}%"></div></div><span class="text-xs font-semibold text-green-600 dark:text-green-400">{{ $uptimePercentage }}%</span></div></div>
                        <div class="flex justify-between items-center"><span class="font-medium">Waktu Respon</span><div class="flex items-center"><div class="w-16 bg-white/20 rounded-full h-1.5"><div class="bg-gradient-to-r from-blue-500 to-cyan-500 h-1.5 rounded-full" style="width: {{ $responsePercentage }}%"></div></div><span class="text-xs font-semibold text-blue-600 dark:text-blue-400">{{ $responsePercentage }}%</span></div></div>
                        <div class="flex justify-between items-center"><span class="font-medium">Keberhasilan Face API</span><div class="flex items-center"><div class="w-16 bg-gray-200 dark:bg-gray-700 rounded-full h-1.5"><div class="bg-purple-600 h-1.5 rounded-full" style="width: {{ $faceApiPercentage }}%"></div></div><span class="text-xs font-semibold text-purple-600 dark:text-purple-400">{{ $faceApiPercentage }}%</span></div></div>
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