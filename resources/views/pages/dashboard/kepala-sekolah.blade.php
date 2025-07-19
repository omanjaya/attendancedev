@extends('layouts.authenticated-unified')

@section('title', 'Dasbor Eksekutif')

@push('head')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endpush

@section('page-content')
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100 dark:from-slate-900 dark:via-blue-900 dark:to-indigo-900">
        <div class="p-6 lg:p-8" x-data="kepalaSekolahDashboard()" x-init="initDashboard()">
            <x-layouts.base-page
                title="Dasbor Eksekutif"
                subtitle="Pengawasan strategis dan wawasan kinerja sekolah"
                :breadcrumbs="[
                    ['label' => 'Dashboard', 'url' => route('dashboard')],
                    ['label' => 'Dasbor Eksekutif']
                ]">
            </x-layouts.base-page>

            <!-- Executive KPIs -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out hover:bg-white/30 hover:scale-105">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-gradient-to-r from-emerald-500 to-emerald-600 rounded-2xl shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <span class="text-xs bg-white/20 text-white px-2 py-1 rounded-full">Bulan ini</span>
                    </div>
                    <h3 class="text-2xl font-bold text-slate-800 dark:text-white mb-1">{{ $dashboardData['school_performance']['average_attendance_rate'] ?? 92 }}%</h3>
                    <p class="text-slate-600 dark:text-slate-400 text-sm">Absensi Sekolah</p>
                    <p class="text-xs text-emerald-600">Di atas rata-rata nasional</p>
                </div>

                <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out hover:bg-white/30 hover:scale-105">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-gradient-to-r from-blue-500 to-cyan-500 rounded-2xl shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                        </div>
                        <span class="text-sm text-blue-600">Sangat Baik</span>
                    </div>
                    <h3 class="text-2xl font-bold text-slate-800 dark:text-white">{{ $dashboardData['teacher_performance']['average_punctuality'] ?? 94 }}%</h3>
                    <p class="text-slate-600 dark:text-slate-400 text-sm">Ketepatan Waktu Staf</p>
                </div>

                <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out hover:bg-white/30 hover:scale-105">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-gradient-to-r from-purple-500 to-pink-500 rounded-2xl shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                            </svg>
                        </div>
                        <span class="text-sm text-purple-600">Sesuai jalur</span>
                    </div>
                    <h3 class="text-2xl font-bold text-slate-800 dark:text-white">{{ $dashboardData['academic_status']['coverage_rate'] ?? 88 }}%</h3>
                    <p class="text-slate-600 dark:text-slate-400 text-sm">Cakupan Kurikulum</p>
                </div>

                <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out hover:bg-white/30 hover:scale-105">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-gradient-to-r from-amber-500 to-orange-600 rounded-2xl shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <span class="text-sm text-amber-600">Sesuai anggaran</span>
                    </div>
                    <h3 class="text-2xl font-bold text-slate-800 dark:text-white">{{ $dashboardData['budget_overview']['utilization_rate'] ?? 75 }}%</h3>
                    <p class="text-slate-600 dark:text-slate-400 text-sm">Pemanfaatan Anggaran</p>
                </div>
            </div>

            <!-- Strategic Analytics -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-xl font-bold text-slate-800 dark:text-white">Tren Kinerja Sekolah</h3>
                            <p class="text-slate-600 dark:text-slate-400">Ikhtisar strategis 3 bulan</p>
                        </div>
                        <div class="flex space-x-2">
                            <x-ui.button variant="secondary" size="sm">3B</x-ui.button>
                            <x-ui.button variant="secondary" size="sm">6B</x-ui.button>
                        </div>
                    </div>
                    <div class="h-64">
                        <canvas id="performanceChart"></canvas>
                    </div>
                </div>

                <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                    <h3 class="text-xl font-bold text-slate-800 dark:text-white mb-6">Distribusi Kinerja Staf</h3>
                    
                    <div class="space-y-4">
                        <div class="p-4 bg-emerald-500/10 rounded-lg border border-emerald-500/20">
                            <div class="flex items-center justify-between mb-2">
                                <h4 class="font-semibold text-emerald-800 dark:text-emerald-200">Kinerja Sangat Baik</h4>
                                <span class="text-sm text-emerald-600 dark:text-emerald-400">{{ $dashboardData['teacher_performance']['excellent_count'] ?? 12 }} staf</span>
                            </div>
                            <div class="w-full bg-white/20 rounded-full h-2">
                                <div class="bg-gradient-to-r from-emerald-500 to-green-500 h-2 rounded-full" style="width: {{ ($dashboardData['teacher_performance']['excellent_count'] ?? 12) / max(($dashboardData['school_overview']['total_teachers'] ?? 20), 1) * 100 }}%"></div>
                            </div>
                        </div>

                        <div class="p-4 bg-blue-500/10 rounded-lg border border-blue-500/20">
                            <div class="flex items-center justify-between mb-2">
                                <h4 class="font-semibold text-blue-800 dark:text-blue-200">Kinerja Baik</h4>
                                <span class="text-sm text-blue-600 dark:text-blue-400">{{ $dashboardData['teacher_performance']['good_count'] ?? 6 }} staf</span>
                            </div>
                            <div class="w-full bg-white/20 rounded-full h-2">
                                <div class="bg-gradient-to-r from-blue-500 to-cyan-500 h-2 rounded-full" style="width: {{ ($dashboardData['teacher_performance']['good_count'] ?? 6) / max(($dashboardData['school_overview']['total_teachers'] ?? 20), 1) * 100 }}%"></div>
                            </div>
                        </div>

                        <div class="p-4 bg-amber-500/10 rounded-lg border border-amber-500/20">
                            <div class="flex items-center justify-between mb-2">
                                <h4 class="font-semibold text-amber-800 dark:text-amber-200">Perlu Pengembangan</h4>
                                <span class="text-sm text-amber-600 dark:text-amber-400">{{ $dashboardData['teacher_performance']['improvement_count'] ?? 2 }} staf</span>
                            </div>
                            <div class="w-full bg-white/20 rounded-full h-2">
                                <div class="bg-gradient-to-r from-amber-500 to-orange-600 h-2 rounded-full" style="width: {{ ($dashboardData['teacher_performance']['improvement_count'] ?? 2) / max(($dashboardData['school_overview']['total_teachers'] ?? 20), 1) * 100 }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Strategic Overview Cards -->
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                    <h3 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Ikhtisar Sekolah</h3>
                    <div class="space-y-3 text-slate-700 dark:text-slate-300">
                        <div class="flex justify-between"><span class="font-medium">Total Guru</span><span class="font-semibold text-slate-800 dark:text-white">{{ $dashboardData['school_overview']['total_teachers'] ?? 25 }}</span></div>
                        <div class="flex justify-between"><span class="font-medium">Total Staf</span><span class="font-semibold text-slate-800 dark:text-white">{{ $dashboardData['school_overview']['total_staff'] ?? 35 }}</span></div>
                        <div class="flex justify-between"><span class="font-medium">Kelas Aktif</span><span class="font-semibold text-slate-800 dark:text-white">{{ $dashboardData['academic_status']['active_classes'] ?? 18 }}</span></div>
                        <div class="flex justify-between"><span class="font-medium">Total Siswa</span><span class="font-semibold text-slate-800 dark:text-white">{{ $dashboardData['academic_status']['total_students'] ?? 450 }}</span></div>
                    </div>
                </div>

                <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                    <h3 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Kemajuan Akademik</h3>
                    <div class="space-y-3 text-slate-700 dark:text-slate-300">
                        <div class="flex justify-between"><span class="font-medium">Progres Kurikulum</span><span class="font-semibold text-blue-600 dark:text-blue-400">{{ $dashboardData['academic_status']['curriculum_progress'] ?? 85 }}%</span></div>
                        <div class="flex justify-between"><span class="font-medium">Penyelesaian Penilaian</span><span class="font-semibold text-green-600 dark:text-green-400">{{ $dashboardData['academic_status']['assessment_completion'] ?? 92 }}%</span></div>
                        <div class="flex justify-between"><span class="font-medium">Ekstrakurikuler</span><span class="font-semibold text-purple-600 dark:text-purple-400">{{ $dashboardData['academic_status']['extracurricular_participation'] ?? 78 }}%</span></div>
                    </div>
                </div>

                <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                    <h3 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Metrik Kualitas</h3>
                    <div class="space-y-3 text-slate-700 dark:text-slate-300">
                        <div class="flex justify-between items-center"><span class="font-medium">Kualitas Mengajar</span><div class="flex items-center">@for($i = 0; $i < 5; $i++)<svg class="w-4 h-4 {{ $i < ($dashboardData['strategic_metrics']['teaching_quality_rating'] ?? 4) ? 'text-amber-400' : 'text-slate-300 dark:text-slate-600' }}" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>@endfor</div></div>
                        <div class="flex justify-between"><span class="font-medium">Kepuasan Orang Tua</span><span class="font-semibold text-green-600 dark:text-green-400">{{ $dashboardData['strategic_metrics']['parent_satisfaction'] ?? 89 }}%</span></div>
                        <div class="flex justify-between"><span class="font-medium">Integrasi Teknologi</span><span class="font-semibold text-blue-600 dark:text-blue-400">{{ $dashboardData['strategic_metrics']['tech_integration'] ?? 76 }}%</span></div>
                    </div>
                </div>

                <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                    <h3 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Tindakan Strategis</h3>
                    <div class="space-y-3">
                        <a href="{{ route('reports.index') }}" class="group block p-3 bg-emerald-500/10 hover:bg-emerald-500/20 rounded-2xl transition-colors">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl flex items-center justify-center shadow-lg">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                                </div>
                                <div>
                                    <p class="font-medium text-emerald-700 dark:text-emerald-300">Analitik</p>
                                    <p class="text-xs text-emerald-600 dark:text-emerald-400">Wawasan strategis</p>
                                </div>
                            </div>
                        </a>

                        <a href="{{ route('employees.index') }}" class="group block p-3 bg-blue-500/10 hover:bg-blue-500/20 rounded-2xl transition-colors">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-xl flex items-center justify-center shadow-lg">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                                </div>
                                <div>
                                    <p class="font-medium text-blue-700 dark:text-blue-300">Tinjauan Staf</p>
                                    <p class="text-xs text-blue-600 dark:text-blue-400">Evaluasi kinerja</p>
                                </div>
                            </div>
                        </a>

                        <a href="{{ route('attendance.check-in') }}" class="group block p-3 bg-green-500/10 hover:bg-green-500/20 rounded-2xl transition-colors">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center shadow-lg">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                                </div>
                                <div>
                                    <p class="font-medium text-green-700 dark:text-green-300">Check-in Saya</p>
                                    <p class="text-xs text-green-600 dark:text-green-400">Absensi pribadi</p>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function kepalaSekolahDashboard() {
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
                    const monthlyData = @json($dashboardData['monthly_trends']['attendance_rates'] ?? []);
                    
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun'],
                            datasets: [{
                                label: 'Tingkat Kehadiran',
                                data: monthlyData.length ? monthlyData : [85, 88, 92, 89, 94, 91],
                                borderColor: 'rgb(16, 185, 129)',
                                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                                borderWidth: 3,
                                fill: true,
                                tension: 0.4
                            }, {
                                label: 'Kinerja Staf',
                                data: [82, 85, 89, 91, 88, 93],
                                borderColor: 'rgb(59, 130, 246)',
                                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                borderWidth: 2,
                                fill: false,
                                tension: 0.4
                            }, {
                                label: 'Target',
                                data: [90, 90, 90, 90, 90, 90],
                                borderColor: 'rgb(239, 68, 68)',
                                borderDash: [5, 5],
                                borderWidth: 2,
                                fill: false
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
