@extends('layouts.authenticated-unified')

@section('title', 'Pemantauan Kinerja')

@section('page-content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100 dark:from-slate-900 dark:via-blue-900 dark:to-indigo-900">
    <div class="p-6 lg:p-8">
        <x-layouts.base-page
            title="Pemantauan Kinerja"
            subtitle="Analitik kinerja sistem real-time dan alat optimasi"
            :breadcrumbs="[
                ['label' => 'Dashboard', 'url' => route('dashboard')],
                ['label' => 'Admin'],
                ['label' => 'Kinerja']
            ]">
            <x-slot name="actions">
                <div class="flex flex-col sm:flex-row sm:space-x-2 space-y-2 sm:space-y-0">
                    <x-ui.button variant="secondary" onclick="refreshData()">
                        <svg class="w-5 h-5 mr-2 group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4"/>
                        </svg>
                        Segarkan
                    </x-ui.button>
                    <div class="relative inline-block text-left" x-data="{ open: false }">
                        <x-ui.button type="button" variant="primary" @click="open = !open">
                            <svg class="w-5 h-5 mr-2 group-hover:rotate-90 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                            Optimasi
                            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </x-ui.button>
                        <div x-show="open" @click.away="open = false" class="origin-top-right absolute right-0 mt-2 w-56 rounded-md shadow-lg bg-white/80 backdrop-blur-sm ring-1 ring-white/30 focus:outline-none z-50" style="display: none;">
                            <div class="py-1">
                                <button class="flex items-center w-full px-4 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-white/50" onclick="clearCache()">
                                    <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4"/>
                                    </svg>
                                    Bersihkan Cache
                                </button>
                                <button class="flex items-center w-full px-4 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-white/50" onclick="optimizeDatabase()">
                                    <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6c0 1.657 3.582 3 8 3s8-1.343 8-3 3.582-3 8-3 8 1.343 8 3v12c0 1.657-3.582 3-8 3s-8-1.343-8-3V6z"/>
                                    </svg>
                                    Optimasi Database
                                </button>
                                <button class="flex items-center w-full px-4 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-white/50" onclick="clearApplicationCache()">
                                    <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 4.55a8 8 0 0 1 6 14.9m0 -4.45v5h5"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.63 7.16l-.01.01M4.06 11l-.01.01M4.63 15.1l-.01.01M7.16 18.37l-.01.01M11 19.94l-.01.01"/>
                                    </svg>
                                    Bersihkan Cache Aplikasi
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </x-slot>
        </x-layouts.base-page>

        <!-- Performance Alerts -->
        @if(!empty($alerts))
        <div class="mb-6 space-y-4">
            @foreach($alerts as $alert)
            <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-4 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                <div class="flex items-center">
                    <div class="flex-shrink-0 mr-3">
                        <svg class="h-6 w-6 text-{{ $alert['type'] === 'error' ? 'red' : 'amber' }}-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            @if($alert['type'] === 'error')
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            @else
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                            @endif
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg font-medium text-slate-800 dark:text-white">{{ $alert['title'] }}</h3>
                        <p class="text-slate-600 dark:text-slate-400 text-sm mt-1">{{ $alert['message'] }}</p>
                    </div>
                    <div class="ml-auto pl-3">
                        <button type="button" class="text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200" onclick="this.closest('.group').remove()">
                            <span class="sr-only">Tutup</span>
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif

        <!-- Performance Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out hover:bg-white/30 hover:scale-105">
                <div class="flex items-center justify-between mb-4">
                    <div class="text-sm font-medium text-slate-600 dark:text-slate-400">Waktu Respon Rata-rata</div>
                    <div class="relative inline-block text-left" x-data="{ open: false }">
                        <button type="button" class="text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200" @click="open = !open">
                            {{ $currentPeriod === 1 ? '1j' : ($currentPeriod === 24 ? '24j' : '7h') }}
                            <svg class="w-4 h-4 inline ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div x-show="open" @click.away="open = false" class="origin-top-right absolute right-0 mt-2 w-32 rounded-md shadow-lg bg-white/80 backdrop-blur-sm ring-1 ring-white/30 focus:outline-none z-50" style="display: none;">
                            <div class="py-1">
                                <button class="block w-full text-left px-4 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-white/50" onclick="changePeriod(1)">1 Jam</button>
                                <button class="block w-full text-left px-4 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-white/50" onclick="changePeriod(24)">24 Jam</button>
                                <button class="block w-full text-left px-4 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-white/50" onclick="changePeriod(168)">7 Hari</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="text-3xl font-bold mb-2 text-{{ $summary['avg_response_time'] > 1000 ? 'red' : ($summary['avg_response_time'] > 500 ? 'amber' : 'green') }}-500">
                    {{ $summary['avg_response_time'] }}ms
                </div>
                <div class="flex justify-between text-sm text-slate-600 dark:text-slate-400">
                    <div>Maks: {{ $summary['max_response_time'] }}ms</div>
                    <div>Min: {{ $summary['min_response_time'] }}ms</div>
                </div>
            </div>
            
            <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out hover:bg-white/30 hover:scale-105">
                <div class="text-sm font-medium text-slate-600 dark:text-slate-400 uppercase mb-4">Total Permintaan</div>
                <div class="text-3xl font-bold mb-2 text-slate-800 dark:text-white">{{ number_format($summary['requests_count']) }}</div>
                <div class="flex justify-between text-sm text-slate-600 dark:text-slate-400">
                    <div>Lambat: {{ $summary['slow_requests'] }}</div>
                    <div>Tingkat Error: {{ $summary['error_rate'] }}%</div>
                </div>
            </div>
            
            <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out hover:bg-white/30 hover:scale-105">
                <div class="text-sm font-medium text-slate-600 dark:text-slate-400 uppercase mb-4">Penggunaan Memori Rata-rata</div>
                <div class="text-3xl font-bold mb-2 text-slate-800 dark:text-white">{{ $summary['avg_memory_usage'] }}</div>
                <div class="flex justify-between text-sm text-slate-600 dark:text-slate-400">
                    <div>Saat Ini: {{ round(memory_get_usage(true) / 1024 / 1024, 2) }}MB</div>
                    <div>Puncak: {{ round(memory_get_peak_usage(true) / 1024 / 1024, 2) }}MB</div>
                </div>
            </div>
            
            <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out hover:bg-white/30 hover:scale-105">
                <div class="text-sm font-medium text-slate-600 dark:text-slate-400 uppercase mb-4">Rata-rata Query/Permintaan</div>
                <div class="text-3xl font-bold mb-2 text-{{ $summary['avg_queries_per_request'] > 20 ? 'red' : ($summary['avg_queries_per_request'] > 10 ? 'amber' : 'green') }}-500">
                    {{ $summary['avg_queries_per_request'] }}
                </div>
                <div class="flex justify-between text-sm text-slate-600 dark:text-slate-400">
                    <div>DB: {{ $systemInfo['database_driver'] }}</div>
                    <div>Cache: {{ $systemInfo['cache_driver'] }}</div>
                </div>
            </div>
        </div>

        <!-- Performance Charts -->
        <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out mb-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-semibold text-slate-800 dark:text-white">Tren Kinerja</h3>
                <x-ui.button variant="secondary" onclick="refreshCharts()">
                    <svg class="w-5 h-5 mr-2 group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4"/>
                    </svg>
                    Segarkan
                </x-ui.button>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <div>
                    <h4 class="text-lg font-semibold text-slate-800 dark:text-white mb-3">Waktu Respon (ms)</h4>
                    <div class="h-48"><canvas id="responseTimeChart"></canvas></div>
                </div>
                <div>
                    <h4 class="text-lg font-semibold text-slate-800 dark:text-white mb-3">Penggunaan Memori (MB)</h4>
                    <div class="h-48"><canvas id="memoryChart"></canvas></div>
                </div>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div>
                    <h4 class="text-lg font-semibold text-slate-800 dark:text-white mb-3">Query Database</h4>
                    <div class="h-48"><canvas id="queryChart"></canvas></div>
                </div>
                <div>
                    <h4 class="text-lg font-semibold text-slate-800 dark:text-white mb-3">Volume Permintaan</h4>
                    <div class="h-48"><canvas id="requestChart"></canvas></div>
                </div>
            </div>
        </div>

        <!-- System Information -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                <h3 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Informasi Sistem</h3>
                <dl class="space-y-4 text-slate-700 dark:text-slate-300">
                    <div class="flex justify-between"><dt class="font-medium">Versi PHP:</dt><dd>{{ $systemInfo['php_version'] }}</dd></div>
                    <div class="flex justify-between"><dt class="font-medium">Versi Laravel:</dt><dd>{{ $systemInfo['laravel_version'] }}</dd></div>
                    <div class="flex justify-between"><dt class="font-medium">Server:</dt><dd>{{ $systemInfo['server_software'] }}</dd></div>
                    <div class="flex justify-between"><dt class="font-medium">Batas Memori:</dt><dd>{{ $systemInfo['memory_limit'] }}</dd></div>
                    <div class="flex justify-between"><dt class="font-medium">Waktu Eksekusi Maks:</dt><dd>{{ $systemInfo['max_execution_time'] }}s</dd></div>
                    <div class="flex justify-between"><dt class="font-medium">OPcache:</dt><dd><span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium text-white bg-gradient-to-r {{ $systemInfo['opcache_enabled'] ? 'from-green-500 to-emerald-600' : 'from-red-500 to-rose-600' }} shadow-lg">{{ $systemInfo['opcache_enabled'] ? 'Aktif' : 'Nonaktif' }}</span></dd></div>
                    <div class="flex justify-between"><dt class="font-medium">Redis:</dt><dd><span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium text-white bg-gradient-to-r {{ $systemInfo['redis_connected'] ? 'from-green-500 to-emerald-600' : 'from-red-500 to-rose-600' }} shadow-lg">{{ $systemInfo['redis_connected'] ? 'Terhubung' : 'Terputus' }}</span></dd></div>
                </dl>
            </div>
            
            <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-semibold text-slate-800 dark:text-white">Statistik Database</h3>
                    <x-ui.button variant="secondary" onclick="refreshDatabaseStats()">
                        <svg class="w-5 h-5 mr-2 group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4"/>
                        </svg>
                        Segarkan
                    </x-ui.button>
                </div>
                <div id="databaseStats" class="text-slate-700 dark:text-slate-300">
                    <div class="text-center py-4">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto mb-2"></div>
                        <div class="text-sm">Memuat statistik database...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let responseTimeChart, memoryChart, queryChart, requestChart;
let currentPeriod = 24;

document.addEventListener('DOMContentLoaded', function() {
    initializeCharts();
    loadDatabaseStats();
    
    // Auto-refresh every 30 seconds
    setInterval(refreshData, 30000);
});

function initializeCharts() {
    const chartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                grid: { color: 'rgba(255,255,255,0.1)' },
                ticks: { color: '#cbd5e0' }
            },
            x: {
                grid: { display: false },
                ticks: { color: '#cbd5e0' }
            }
        },
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.dataset.label || '';
                        if (label) label += ': ';
                        if (context.parsed.y !== null) label += context.parsed.y + (context.dataset.label.includes('Time') ? 'ms' : (context.dataset.label.includes('Memory') ? 'MB' : ''));
                        return label;
                    }
                }
            }
        }
    };

    // Response Time Chart
    const responseTimeCtx = document.getElementById('responseTimeChart').getContext('2d');
    responseTimeChart = new Chart(responseTimeCtx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Waktu Respon (ms)',
                data: [],
                borderColor: 'rgb(96, 165, 250)',
                backgroundColor: 'rgba(96, 165, 250, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: chartOptions
    });

    // Memory Chart (convert bytes to MB)
    const memoryCtx = document.getElementById('memoryChart').getContext('2d');
    memoryChart = new Chart(memoryCtx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Penggunaan Memori (MB)',
                data: [],
                borderColor: 'rgb(52, 211, 153)',
                backgroundColor: 'rgba(52, 211, 153, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: chartOptions
    });

    // Query Chart
    const queryCtx = document.getElementById('queryChart').getContext('2d');
    queryChart = new Chart(queryCtx, {
        type: 'bar',
        data: {
            labels: [],
            datasets: [{
                label: 'Query',
                data: [],
                backgroundColor: 'rgba(251, 191, 36, 0.8)',
                borderColor: 'rgb(251, 191, 36)',
                borderWidth: 1
            }]
        },
        options: chartOptions
    });

    // Request Chart
    const requestCtx = document.getElementById('requestChart').getContext('2d');
    requestChart = new Chart(requestCtx, {
        type: 'bar',
        data: {
            labels: [],
            datasets: [{
                label: 'Permintaan',
                data: [],
                backgroundColor: 'rgba(239, 68, 68, 0.8)',
                borderColor: 'rgb(239, 68, 68)',
                borderWidth: 1
            }]
        },
        options: chartOptions
    });
    
    refreshData();
}

function refreshData() {
    axios.get('/admin/performance/data', {
        params: { hours: currentPeriod }
    })
    .then(response => {
        const data = response.data;
        updateSummaryCards(data.summary);
        updateCharts(data.charts);
        // updateAlerts(data.alerts); // Alerts are handled by Blade now
    })
    .catch(error => {
        console.error('Gagal menyegarkan data:', error);
        toastr.error('Gagal menyegarkan data kinerja');
    });
}

function refreshCharts() {
    axios.get('/admin/performance/data', {
        params: { hours: currentPeriod }
    })
    .then(response => {
        updateCharts(response.data.charts);
    })
    .catch(error => {
        console.error('Gagal menyegarkan grafik:', error);
    });
}

function updateSummaryCards(summary) {
    $('#totalRequests').text(summary.requests_count);
    $('#avgResponseTime').text(summary.avg_response_time + 'ms');
    $('#avgMemoryUsage').text(summary.avg_memory_usage);
    $('#avgQueriesPerRequest').text(summary.avg_queries_per_request);
}

function changePeriod(hours) {
    currentPeriod = hours;
    refreshData();
}

function loadDatabaseStats() {
    axios.get('/admin/performance/database-stats')
        .then(response => {
            const stats = response.data;
            let html = '<dl class="space-y-4 text-slate-700 dark:text-slate-300">';
            
            if (stats.connections !== undefined) {
                html += `<div class="flex justify-between"><dt class="font-medium">Koneksi:</dt><dd>${stats.connections}</dd></div>`;
            }
            
            if (stats.database_size) {
                html += `<div class="flex justify-between"><dt class="font-medium">Ukuran Database:</dt><dd>${stats.database_size}</dd></div>`;
            }
            
            if (stats.slow_queries && stats.slow_queries.length > 0) {
                html += `<div class="flex justify-between"><dt class="font-medium">Query Lambat:</dt><dd>${stats.slow_queries.length}</dd></div>`;
            }
            
            html += '</dl>';
            
            $('#databaseStats').html(html);
        })
        .catch(error => {
            $('#databaseStats').html(
                '<div class="text-red-500 text-sm">Gagal memuat statistik database</div>'
            );
        });
}

function refreshDatabaseStats() {
    $('#databaseStats').html(
        '<div class="text-center py-4"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto mb-2"></div><div class="text-sm">Memuat statistik database...</div></div>'
    );
    loadDatabaseStats();
}

function clearCache() {
    axios.post('/admin/performance/clear-cache')
        .then(response => {
            toastr.success(response.data.message);
            refreshData();
        })
        .catch(error => {
            toastr.error(error.response?.data?.error || 'Gagal membersihkan cache');
        });
}

function optimizeDatabase() {
    axios.post('/admin/performance/optimize-database')
        .then(response => {
            toastr.success(response.data.message);
            refreshDatabaseStats();
        })
        .catch(error => {
            toastr.error(error.response?.data?.error || 'Gagal mengoptimalkan database');
        });
}

function clearApplicationCache() {
    axios.post('/admin/performance/clear-app-cache')
        .then(response => {
            toastr.success(response.data.message);
        })
        .catch(error => {
            toastr.error(error.response?.data?.error || 'Gagal membersihkan cache aplikasi');
        });
}
</script>
@endpush
