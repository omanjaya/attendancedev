@extends('layouts.authenticated-unified')

@section('title', 'Dasbor Guru')

@push('head')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endpush

@section('page-content')
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100 dark:from-slate-900 dark:via-blue-900 dark:to-indigo-900">
        <div class="p-6 lg:p-8" x-data="guruDashboard()" x-init="initDashboard()">
            <x-layouts.base-page
                title="Dasbor Guru"
                subtitle="Pusat pengajaran pribadi Anda dan wawasan kinerja"
                :breadcrumbs="[
                    ['label' => 'Dashboard', 'url' => route('dashboard')],
                    ['label' => 'Dasbor Guru']
                ]">
            </x-layouts.base-page>

            <!-- Personal Status Overview -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out hover:bg-white/30 hover:scale-105">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-gradient-to-r from-emerald-500 to-emerald-600 rounded-2xl shadow-lg">
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
                        <p class="text-emerald-600 text-sm mb-2">Waktu Check-in</p>
                        <p class="text-xs text-emerald-700 capitalize">{{ $dashboardData['personal_status']['today_status']['status'] ?? 'hadir' }}</p>
                    @else
                        <h3 class="text-2xl font-bold text-slate-800 dark:text-white mb-1">Belum</h3>
                        <p class="text-emerald-600 text-sm mb-2">Status Check-in</p>
                        <p class="text-xs text-emerald-700">Mohon check in</p>
                    @endif
                </div>

                <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out hover:bg-white/30 hover:scale-105">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-gradient-to-r from-emerald-500 to-emerald-600 rounded-2xl shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <span class="text-sm text-emerald-600">Sangat Baik</span>
                    </div>
                    <h3 class="text-2xl font-bold text-slate-800 dark:text-white">{{ $dashboardData['personal_status']['monthly_summary']['attendance_rate'] ?? 0 }}%</h3>
                    <p class="text-slate-600 dark:text-slate-400 text-sm">Absensi Bulanan</p>
                </div>

                <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out hover:bg-white/30 hover:scale-105">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-gradient-to-r from-blue-500 to-cyan-500 rounded-2xl shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                            </svg>
                        </div>
                        <span class="text-sm text-blue-600">Bagus</span>
                    </div>
                    <h3 class="text-2xl font-bold text-slate-800 dark:text-white">{{ $dashboardData['personal_status']['punctuality_score'] ?? 0 }}%</h3>
                    <p class="text-slate-600 dark:text-slate-400 text-sm">Skor Ketepatan Waktu</p>
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
                    <h3 class="text-2xl font-bold text-slate-800 dark:text-white">{{ $dashboardData['leave_balance']['remaining_days'] ?? 0 }}</h3>
                    <p class="text-slate-600 dark:text-slate-400 text-sm">Sisa Hari Cuti</p>
                </div>
            </div>

            <!-- Main Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                <div class="lg:col-span-2 group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-xl font-bold text-slate-800 dark:text-white">Jadwal Mengajar Hari Ini</h3>
                            <p class="text-slate-600 dark:text-slate-400">{{ count($dashboardData['today_schedule']['classes_today'] ?? []) }} kelas terjadwal</p>
                        </div>
                        <div class="flex space-x-2">
                            <x-ui.button variant="secondary" size="sm">Hari Ini</x-ui.button>
                            <x-ui.button variant="secondary" size="sm">Minggu</x-ui.button>
                        </div>
                    </div>

                    <div class="space-y-4 max-h-96 overflow-y-auto">
                        @forelse($dashboardData['today_schedule']['classes_today'] ?? [] as $class)
                            <div class="flex items-center justify-between p-4 bg-white/10 rounded-2xl hover:bg-white/20 transition-colors">
                                <div class="flex items-center space-x-4">
                                    <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-emerald-500 to-emerald-600 flex items-center justify-center shadow-lg">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-slate-800 dark:text-white">{{ $class['subject'] ?? 'Matematika' }}</p>
                                        <p class="text-sm text-slate-600 dark:text-slate-400">{{ $class['class_name'] ?? 'Kelas XII-A' }} • {{ $class['room'] ?? 'Ruang 201' }}</p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ $class['time'] ?? '08:00 - 09:30' }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="px-3 py-1 text-xs rounded-full text-white 
                                        {{ $class['status'] === 'completed' ? 'bg-gradient-to-r from-emerald-500 to-green-500' : 
                                           ($class['status'] === 'ongoing' ? 'bg-gradient-to-r from-blue-500 to-cyan-500' : 'bg-gradient-to-r from-amber-500 to-orange-600') }}">
                                        {{ ucfirst($class['status'] ?? 'pending') }}
                                    </span>
                                    @if($class['students_present'] ?? false)
                                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ $class['students_present'] }}/{{ $class['total_students'] }} hadir</p>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-8 text-slate-500 dark:text-slate-400">
                                <svg class="w-12 h-12 mx-auto mb-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <p>Tidak ada kelas terjadwal untuk hari ini</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                    <h3 class="text-xl font-bold text-slate-800 dark:text-white mb-6">Aksi Cepat</h3>
                
                    <div class="space-y-4">
                        @if(!($dashboardData['personal_status']['today_status']['checked_in'] ?? false))
                            <a href="{{ route('attendance.check-in') }}" class="group block p-3 bg-emerald-500/10 hover:bg-emerald-500/20 rounded-2xl transition-colors">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl flex items-center justify-center shadow-lg">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                                    </div>
                                    <div>
                                        <p class="font-medium text-emerald-700 dark:text-emerald-300">Check In</p>
                                        <p class="text-xs text-emerald-600 dark:text-emerald-400">Mulai hari Anda</p>
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
                                        <p class="text-xs text-amber-600 dark:text-amber-400">Akhiri hari Anda</p>
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
                                        <p class="text-xs text-emerald-600 dark:text-emerald-400">{{ $dashboardData['personal_status']['today_status']['total_hours'] ?? 0 }} jam kerja</p>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <a href="{{ route('leave.index') }}#request" class="group block p-3 bg-blue-500/10 hover:bg-blue-500/20 rounded-2xl transition-colors">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-xl flex items-center justify-center shadow-lg">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </div>
                                <div>
                                    <p class="font-medium text-blue-700 dark:text-blue-300">Ajukan Cuti</p>
                                    <p class="text-xs text-blue-600 dark:text-blue-400">Kirim permintaan cuti</p>
                                </div>
                            </div>
                        </a>

                        <a href="{{ route('schedules.index') }}" class="group block p-3 bg-purple-500/10 hover:bg-purple-500/20 rounded-2xl transition-colors">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-pink-500 rounded-xl flex items-center justify-center shadow-lg">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                                <div>
                                    <p class="font-medium text-purple-700 dark:text-purple-300">Lihat Jadwal</p>
                                    <p class="text-xs text-purple-600 dark:text-purple-400">Jadwal mengajar mingguan</p>
                                </div>
                            </div>
                        </a>

                        <a href="{{ route('reports.employee') }}" class="group block p-3 bg-slate-500/10 hover:bg-slate-500/20 rounded-2xl transition-colors">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-gray-500 to-slate-600 rounded-xl flex items-center justify-center shadow-lg">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                                </div>
                                <div>
                                    <p class="font-medium text-slate-700 dark:text-slate-300">Kinerja Saya</p>
                                    <p class="text-xs text-slate-600 dark:text-slate-400">Lihat laporan detail</p>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Performance Summary -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-xl font-bold text-slate-800 dark:text-white">Kinerja Mengajar</h3>
                            <p class="text-slate-600 dark:text-slate-400">Ikhtisar 4 minggu</p>
                        </div>
                        <div class="flex space-x-2">
                            <x-ui.button variant="secondary" size="sm">4M</x-ui.button>
                            <x-ui.button variant="secondary" size="sm">3B</x-ui.button>
                        </div>
                    </div>
                    <div class="h-64">
                        <canvas id="performanceChart"></canvas>
                    </div>
                </div>

                <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                    <h3 class="text-xl font-bold text-slate-800 dark:text-white mb-6">Statistik Personal</h3>
                
                    <div class="space-y-4">
                        <div class="p-4 bg-emerald-500/10 rounded-lg border border-emerald-500/20">
                            <div class="flex justify-between items-center mb-2">
                                <span class="font-medium text-emerald-800 dark:text-emerald-200">Bulan Ini</span>
                            </div>
                            <div class="grid grid-cols-2 gap-4 text-sm text-slate-800 dark:text-white">
                                <div>
                                    <p class="text-emerald-600 dark:text-emerald-400">Hari Hadir</p>
                                    <p class="font-semibold">{{ $dashboardData['personal_status']['monthly_summary']['attended_days'] ?? 0 }}</p>
                                </div>
                                <div>
                                    <p class="text-emerald-600 dark:text-emerald-400">Hari Terlambat</p>
                                    <p class="font-semibold">{{ $dashboardData['personal_status']['monthly_summary']['late_days'] ?? 0 }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="p-4 bg-blue-500/10 rounded-lg border border-blue-500/20">
                            <div class="flex justify-between items-center mb-2">
                                <span class="font-medium text-blue-800 dark:text-blue-200">Jam Mengajar</span>
                            </div>
                            <div class="grid grid-cols-2 gap-4 text-sm text-slate-800 dark:text-white">
                                <div>
                                    <p class="text-blue-600 dark:text-blue-400">Minggu Ini</p>
                                    <p class="font-semibold">{{ $dashboardData['teaching_summary']['weekly_hours'] ?? 0 }}j</p>
                                </div>
                                <div>
                                    <p class="text-blue-600 dark:text-blue-400">Bulan Ini</p>
                                    <p class="font-semibold">{{ $dashboardData['teaching_summary']['monthly_hours'] ?? 0 }}j</p>
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
                                    <p class="font-semibold">{{ $dashboardData['leave_balance']['remaining_days'] ?? 0 }} hari</p>
                                </div>
                                <div>
                                    <p class="text-purple-600 dark:text-purple-400">Terpakai</p>
                                    <p class="font-semibold">{{ $dashboardData['leave_balance']['used_days'] ?? 0 }} hari</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function guruDashboard() {
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
                    
                    const chartElement = document.getElementById('performanceChart');
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
                                data: {!! json_encode($dashboardData['performance_summary']['weekly_attendance'] ?? [0, 0, 0, 0]) !!},
                                borderColor: 'rgb(16, 185, 129)',
                                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                                borderWidth: 3,
                                fill: true,
                                tension: 0.4
                            }, {
                                label: 'Skor Ketepatan Waktu',
                                data: {!! json_encode($dashboardData['performance_summary']['weekly_punctuality'] ?? [0, 0, 0, 0]) !!},
                                borderColor: 'rgb(59, 130, 246)',
                                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                borderWidth: 2,
                                fill: false,
                                tension: 0.4
                            }, {
                                label: 'Kualitas Mengajar',
                                data: {!! json_encode(array_map(function($val) { return $val + 5; }, $dashboardData['performance_summary']['weekly_attendance'] ?? [0, 0, 0, 0])) !!},
                                borderColor: 'rgb(168, 85, 247)',
                                backgroundColor: 'rgba(168, 85, 247, 0.1)',
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
