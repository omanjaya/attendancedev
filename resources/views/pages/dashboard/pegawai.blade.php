@extends('layouts.authenticated-unified')

@section('title', 'Dasbor Staf')

@push('head')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endpush

@section('page-content')
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100 dark:from-slate-900 dark:via-blue-900 dark:to-indigo-900">
        <div class="p-6 lg:p-8" x-data="pegawaiDashboard()" x-init="initDashboard()">
            <x-layouts.base-page
                title="Dasbor Staf"
                subtitle="Pusat kerja Anda dan pelacakan absensi pribadi"
                :breadcrumbs="[
                    ['label' => 'Dashboard', 'url' => route('dashboard')],
                    ['label' => 'Dasbor Staf']
                ]">
            </x-layouts.base-page>

            <!-- Personal Work Status -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out hover:bg-white/30 hover:scale-105">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-gradient-to-r from-blue-500 to-cyan-500 rounded-2xl shadow-lg">
                            @if($dashboardData['personal_status']['today_status']['checked_in'] ?? false)
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            @else
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            @endif
                        </div>
                        <span class="text-xs bg-white/20 text-white px-2 py-1 rounded-full">
                            {{ ($dashboardData['personal_status']['today_status']['checked_in'] ?? false) ? 'Hadir' : 'Tertunda' }}
                        </span>
                    </div>
                    @if($dashboardData['personal_status']['today_status']['checked_in'] ?? false)
                        <h3 class="text-2xl font-bold text-slate-800 dark:text-white mb-1">
                            {{ \Carbon\Carbon::parse($dashboardData['personal_status']['today_status']['check_in_time'])->format('H:i') }}
                        </h3>
                        <p class="text-blue-600 text-sm mb-2">Waktu Check-in</p>
                        <p class="text-xs text-blue-700 capitalize">{{ $dashboardData['personal_status']['today_status']['status'] ?? 'hadir' }}</p>
                    @else
                        <h3 class="text-2xl font-bold text-slate-800 dark:text-white mb-1">Belum</h3>
                        <p class="text-blue-600 text-sm mb-2">Status Check-in</p>
                        <p class="text-xs text-blue-700">Mohon check in</p>
                    @endif
                </div>

                <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out hover:bg-white/30 hover:scale-105">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-gradient-to-r from-blue-500 to-cyan-500 rounded-2xl shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <span class="text-sm text-blue-600">Sangat Baik</span>
                    </div>
                    <h3 class="text-2xl font-bold text-slate-800 dark:text-white">{{ $dashboardData['personal_status']['monthly_summary']['attendance_rate'] ?? 97 }}%</h3>
                    <p class="text-slate-600 dark:text-slate-400 text-sm">Absensi Bulanan</p>
                </div>

                <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out hover:bg-white/30 hover:scale-105">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-gradient-to-r from-green-500 to-emerald-600 rounded-2xl shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <span class="text-sm text-emerald-600">Minggu ini</span>
                    </div>
                    <h3 class="text-2xl font-bold text-slate-800 dark:text-white">{{ $dashboardData['work_summary']['weekly_hours'] ?? 40 }}j</h3>
                    <p class="text-slate-600 dark:text-slate-400 text-sm">Jam Kerja</p>
                </div>

                <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out hover:bg-white/30 hover:scale-105">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-gradient-to-r from-purple-500 to-pink-500 rounded-2xl shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <span class="text-sm text-purple-600">Tersedia</span>
                    </div>
                    <h3 class="text-2xl font-bold text-slate-800 dark:text-white">{{ $dashboardData['leave_balance']['remaining_days'] ?? 15 }}</h3>
                    <p class="text-slate-600 dark:text-slate-400 text-sm">Sisa Hari Cuti</p>
                </div>
            </div>

            <!-- Main Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                <div class="lg:col-span-2 group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-xl font-bold text-slate-800 dark:text-white">Ringkasan Kerja Hari Ini</h3>
                            <p class="text-slate-600 dark:text-slate-400">Ikhtisar aktivitas harian Anda</p>
                        </div>
                        <div class="flex space-x-2">
                            <x-ui.button variant="secondary" size="sm">Hari Ini</x-ui.button>
                            <x-ui.button variant="secondary" size="sm">Minggu</x-ui.button>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="p-4 bg-blue-500/10 rounded-lg border-l-4 border-blue-500">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="font-semibold text-slate-800 dark:text-white">Tugas Administratif</h4>
                                    <p class="text-sm text-slate-600 dark:text-slate-400">Manajemen kantor dan dokumentasi</p>
                                </div>
                                <span class="px-3 py-1 text-xs bg-blue-500 text-white rounded-full">Dalam Proses</span>
                            </div>
                        </div>

                        <div class="p-4 bg-green-500/10 rounded-lg border-l-4 border-green-500">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="font-semibold text-slate-800 dark:text-white">Layanan Dukungan</h4>
                                    <p class="text-sm text-slate-600 dark:text-slate-400">Bantuan siswa dan fakultas</p>
                                </div>
                                <span class="px-3 py-1 text-xs bg-green-500 text-white rounded-full">Selesai</span>
                            </div>
                        </div>

                        <div class="p-4 bg-amber-500/10 rounded-lg border-l-4 border-amber-500">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="font-semibold text-slate-800 dark:text-white">Manajemen Fasilitas</h4>
                                    <p class="text-sm text-slate-600 dark:text-slate-400">Pemeliharaan dan logistik</p>
                                </div>
                                <span class="px-3 py-1 text-xs bg-amber-500 text-white rounded-full">Tertunda</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                    <h3 class="text-xl font-bold text-slate-800 dark:text-white mb-6">Aksi Cepat</h3>
                    
                    <div class="space-y-4">
                        @if(!($dashboardData['personal_status']['today_status']['checked_in'] ?? false))
                            <a href="{{ route('attendance.check-in') }}" class="group block p-3 bg-blue-500/10 hover:bg-blue-500/20 rounded-2xl transition-colors">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-xl flex items-center justify-center shadow-lg">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                                    </div>
                                    <div>
                                        <p class="font-medium text-blue-700 dark:text-blue-300">Check In</p>
                                        <p class="text-xs text-blue-600 dark:text-blue-400">Mulai hari kerja Anda</p>
                                    </div>
                                </div>
                            </a>
                        @elseif(!($dashboardData['personal_status']['today_status']['check_out_time'] ?? false))
                            <a href="{{ route('attendance.check-in') }}" class="group block p-3 bg-amber-500/10 hover:bg-amber-500/20 rounded-2xl transition-colors">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl flex items-center justify-center shadow-lg">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                    </div>
                                    <div>
                                        <p class="font-medium text-amber-700 dark:text-amber-300">Check Out</p>
                                        <p class="text-xs text-amber-600 dark:text-amber-400">Akhiri hari kerja Anda</p>
                                    </div>
                                </div>
                            </a>
                        @else
                            <div class="p-3 bg-emerald-500/10 rounded-2xl">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl flex items-center justify-center shadow-lg">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    </div>
                                    <div>
                                        <p class="font-medium text-emerald-700 dark:text-emerald-300">Hari Selesai</p>
                                        <p class="text-xs text-emerald-600 dark:text-emerald-400">{{ $dashboardData['personal_status']['today_status']['total_hours'] ?? 8 }} jam kerja</p>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <a href="{{ route('leave.index') }}" class="group block p-3 bg-purple-500/10 hover:bg-purple-500/20 rounded-2xl transition-colors">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-pink-500 rounded-xl flex items-center justify-center shadow-lg">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </div>
                                <div>
                                    <p class="font-medium text-purple-700 dark:text-purple-300">Ajukan Cuti</p>
                                    <p class="text-xs text-purple-600 dark:text-purple-400">Kirim permintaan cuti</p>
                                </div>
                            </div>
                        </a>

                        <a href="{{ route('attendance.index') }}" class="group block p-3 bg-emerald-500/10 hover:bg-emerald-500/20 rounded-2xl transition-colors">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center shadow-lg">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg>
                                </div>
                                <div>
                                    <p class="font-medium text-emerald-700 dark:text-emerald-300">Absensi Saya</p>
                                    <p class="text-xs text-emerald-600 dark:text-emerald-400">Lihat riwayat absensi</p>
                                </div>
                            </div>
                        </a>

                        <a href="{{ route('reports.employee') }}" class="group block p-3 bg-slate-500/10 hover:bg-slate-500/20 rounded-2xl transition-colors">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-gray-500 to-slate-600 rounded-xl flex items-center justify-center shadow-lg">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                                </div>
                                <div>
                                    <p class="font-medium text-slate-700 dark:text-slate-300">Laporan Saya</p>
                                    <p class="text-xs text-slate-600 dark:text-slate-400">Lihat laporan kinerja</p>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Work Summary Charts -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-xl font-bold text-slate-800 dark:text-white">Ikhtisar Absensi</h3>
                            <p class="text-slate-600 dark:text-slate-400">Pelacakan 4 minggu</p>
                        </div>
                        <div class="flex space-x-2">
                            <x-ui.button variant="secondary" size="sm">4M</x-ui.button>
                            <x-ui.button variant="secondary" size="sm">3B</x-ui.button>
                        </div>
                    </div>
                    <div class="h-64">
                        <canvas id="attendanceChart"></canvas>
                    </div>
                </div>

                <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                    <h3 class="text-xl font-bold text-slate-800 dark:text-white mb-6">Statistik Kerja</h3>
                    
                    <div class="space-y-4">
                        <div class="p-4 bg-blue-500/10 rounded-lg border border-blue-500/20">
                            <div class="flex justify-between items-center mb-2">
                                <span class="font-medium text-blue-800 dark:text-blue-200">Bulan Ini</span>
                            </div>
                            <div class="grid grid-cols-2 gap-4 text-sm text-slate-800 dark:text-white">
                                <div>
                                    <p class="text-blue-600 dark:text-blue-400">Hari Hadir</p>
                                    <p class="font-semibold">{{ $dashboardData['personal_status']['monthly_summary']['attended_days'] ?? 23 }}</p>
                                </div>
                                <div>
                                    <p class="text-blue-600 dark:text-blue-400">Hari Terlambat</p>
                                    <p class="font-semibold">{{ $dashboardData['personal_status']['monthly_summary']['late_days'] ?? 0 }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="p-4 bg-green-500/10 rounded-lg border border-green-500/20">
                            <div class="flex justify-between items-center mb-2">
                                <span class="font-medium text-green-800 dark:text-green-200">Jam Kerja</span>
                            </div>
                            <div class="grid grid-cols-2 gap-4 text-sm text-slate-800 dark:text-white">
                                <div>
                                    <p class="text-green-600 dark:text-green-400">Minggu Ini</p>
                                    <p class="font-semibold">{{ $dashboardData['work_summary']['weekly_hours'] ?? 40 }}j</p>
                                </div>
                                <div>
                                    <p class="text-green-600 dark:text-green-400">Bulan Ini</p>
                                    <p class="font-semibold">{{ $dashboardData['work_summary']['monthly_hours'] ?? 172 }}j</p>
                                </div>
                            </div>
                        </div>

                        <div class="p-4 bg-purple-500/10 rounded-lg border border-purple-500/20">
                            <div class="flex justify-between items-center mb-2">
                                <span class="font-medium text-purple-800 dark:text-purple-200">Saldo Cuti</span>
                            </div>
                            <div class="grid grid-cols-2 gap-4 text-sm text-slate-800 dark:text-white">
                                <div>
                                    <p class="text-purple-600 dark:text-purple-400">Sisa</p>
                                    <p class="font-semibold">{{ $dashboardData['leave_balance']['remaining_days'] ?? 15 }} hari</p>
                                </div>
                                <div>
                                    <p class="text-purple-600 dark:text-purple-400">Terpakai</p>
                                    <p class="font-semibold">{{ $dashboardData['leave_balance']['used_days'] ?? 3 }} hari</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function pegawaiDashboard() {
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
                    
                    const chartElement = document.getElementById('attendanceChart');
                    if (!chartElement) {
                        console.warn('Chart element not found');
                        return;
                    }
                    
                    const ctx = chartElement.getContext('2d');
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: ['Minggu 1', 'Minggu 2', 'Minggu 3', 'Minggu 4'],
                            datasets: [{
                                label: 'Tingkat Kehadiran',
                                data: [100, 95, 100, 100],
                                borderColor: 'rgb(59, 130, 246)',
                                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                borderWidth: 3,
                                fill: true,
                                tension: 0.4
                            }, {
                                label: 'Skor Ketepatan Waktu',
                                data: [95, 90, 100, 95],
                                borderColor: 'rgb(16, 185, 129)',
                                backgroundColor: 'rgba(16, 185, 129, 0.1)',
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
                                    max: 100,
                                    grid: {
                                        color: 'rgba(255, 255, 255, 0.1)'
                                    },
                                    ticks: {
                                        color: '#cbd5e0',
                                        callback: function(value) {
                                            return value + '%';
                                        }
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
