@extends('layouts.authenticated')

@section('title', 'Performance Monitoring')

@section('page-content')
<div class="py-6">
    <div class="max-w-7xl mx-auto px-8">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
            <div class="mb-4 sm:mb-0">
                <h2 class="text-3xl font-bold text-gray-900">Performance Monitoring</h2>
                <div class="text-gray-600 mt-1">Real-time system performance analytics and optimization tools</div>
            </div>
            <div class="flex-shrink-0">
                <div class="flex flex-col sm:flex-row sm:space-x-2 space-y-2 sm:space-y-0">
                    <button class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition duration-150" onclick="refreshData()">
                        <svg class="w-4 h-4 mr-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4"/>
                            <path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4"/>
                        </svg>
                        Refresh
                    </button>
                    <div class="relative inline-block text-left">
                        <div>
                            <button type="button" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" id="optimize-menu" onclick="toggleOptimizeMenu()">
                                <svg class="w-4 h-4 mr-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 0 0 -1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 0 0 -2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 0 0 -2.573 -1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 0 0 -1.065 -2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 0 0 1.066 -2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                                Optimize
                                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                        </div>
                        <div id="optimize-dropdown" class="hidden origin-top-right absolute right-0 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 focus:outline-none z-50">
                            <div class="py-1">
                                <button class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" onclick="clearCache()">
                                    <svg class="w-4 h-4 mr-3" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                        <path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4"/>
                                        <path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4"/>
                                    </svg>
                                    Clear Cache
                                </button>
                                <button class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" onclick="optimizeDatabase()">
                                    <svg class="w-4 h-4 mr-3" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                        <ellipse cx="12" cy="6" rx="8" ry="3"/>
                                        <path d="M4 6v6a8 3 0 0 0 16 0v-6"/>
                                        <path d="M4 12v6a8 3 0 0 0 16 0v-6"/>
                                    </svg>
                                    Optimize Database
                                </button>
                                <button class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" onclick="clearApplicationCache()">
                                    <svg class="w-4 h-4 mr-3" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                        <path d="M9 4.55a8 8 0 0 1 6 14.9m0 -4.45v5h5"/>
                                        <line x1="5.63" y1="7.16" x2="5.63" y2="7.17"/>
                                        <line x1="4.06" y1="11" x2="4.06" y2="11.01"/>
                                        <line x1="4.63" y1="15.1" x2="4.63" y2="15.11"/>
                                        <line x1="7.16" y1="18.37" x2="7.16" y2="18.38"/>
                                        <line x1="11" y1="19.94" x2="11" y2="19.95"/>
                                    </svg>
                                    Clear App Cache
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance Alerts -->
        @if(!empty($alerts))
        <div class="mb-6">
            @foreach($alerts as $alert)
            <div class="bg-{{ $alert['type'] === 'error' ? 'red' : 'yellow' }}-50 border border-{{ $alert['type'] === 'error' ? 'red' : 'yellow' }}-200 rounded-md p-4 mb-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-{{ $alert['type'] === 'error' ? 'red' : 'yellow' }}-400" viewBox="0 0 20 20" fill="currentColor">
                            @if($alert['type'] === 'error')
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            @else
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            @endif
                        </svg>
                    </div>
                    <div class="ml-3 flex-1">
                        <h3 class="text-sm font-medium text-{{ $alert['type'] === 'error' ? 'red' : 'yellow' }}-800">{{ $alert['title'] }}</h3>
                        <div class="mt-2 text-sm text-{{ $alert['type'] === 'error' ? 'red' : 'yellow' }}-700">
                            <p>{{ $alert['message'] }}</p>
                        </div>
                    </div>
                    <div class="ml-auto pl-3">
                        <div class="-mx-1.5 -my-1.5">
                            <button type="button" class="inline-flex bg-{{ $alert['type'] === 'error' ? 'red' : 'yellow' }}-50 rounded-md p-1.5 text-{{ $alert['type'] === 'error' ? 'red' : 'yellow' }}-500 hover:bg-{{ $alert['type'] === 'error' ? 'red' : 'yellow' }}-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-{{ $alert['type'] === 'error' ? 'red' : 'yellow' }}-50 focus:ring-{{ $alert['type'] === 'error' ? 'red' : 'yellow' }}-600" onclick="this.parentElement.parentElement.parentElement.parentElement.remove()">
                                <span class="sr-only">Dismiss</span>
                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif

        <!-- Performance Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="text-sm font-medium text-gray-500 uppercase tracking-wider">Avg Response Time</div>
                    <div class="relative">
                        <button type="button" class="text-gray-400 hover:text-gray-600 text-sm" onclick="togglePeriodMenu()" id="period-button">
                            24h
                            <svg class="w-4 h-4 inline ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div id="period-menu" class="hidden absolute right-0 mt-2 w-32 bg-white rounded-md shadow-lg z-10">
                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" onclick="changePeriod(1)">1 Hour</a>
                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 bg-gray-100" onclick="changePeriod(24)">24 Hours</a>
                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" onclick="changePeriod(168)">7 Days</a>
                        </div>
                    </div>
                </div>
                <div class="text-3xl font-bold mb-2 text-{{ $summary['avg_response_time'] > 1000 ? 'red-600' : ($summary['avg_response_time'] > 500 ? 'yellow-600' : 'green-600') }}">
                    {{ $summary['avg_response_time'] }}ms
                </div>
                <div class="flex justify-between text-sm text-gray-600">
                    <div>Max: {{ $summary['max_response_time'] }}ms</div>
                    <div>Min: {{ $summary['min_response_time'] }}ms</div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-4">Total Requests</div>
                <div class="text-3xl font-bold mb-2 text-gray-900">{{ number_format($summary['requests_count']) }}</div>
                <div class="flex justify-between text-sm text-gray-600">
                    <div>Slow: {{ $summary['slow_requests'] }}</div>
                    <div>Error Rate: {{ $summary['error_rate'] }}%</div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-4">Avg Memory Usage</div>
                <div class="text-3xl font-bold mb-2 text-gray-900">{{ $summary['avg_memory_usage'] }}</div>
                <div class="flex justify-between text-sm text-gray-600">
                    <div>Current: {{ round(memory_get_usage(true) / 1024 / 1024, 2) }}MB</div>
                    <div>Peak: {{ round(memory_get_peak_usage(true) / 1024 / 1024, 2) }}MB</div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-4">Avg Queries/Request</div>
                <div class="text-3xl font-bold mb-2 text-{{ $summary['avg_queries_per_request'] > 20 ? 'red-600' : ($summary['avg_queries_per_request'] > 10 ? 'yellow-600' : 'green-600') }}">
                    {{ $summary['avg_queries_per_request'] }}
                </div>
                <div class="flex justify-between text-sm text-gray-600">
                    <div>DB: {{ $systemInfo['database_driver'] }}</div>
                    <div>Cache: {{ $systemInfo['cache_driver'] }}</div>
                </div>
            </div>
        </div>

        <!-- Performance Charts -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-lg font-medium text-gray-900">Performance Trends</h3>
                <button class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500" onclick="refreshCharts()">
                    <svg class="w-4 h-4 mr-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4"/>
                        <path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4"/>
                    </svg>
                    Refresh
                </button>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    <div>
                        <h4 class="text-sm font-medium text-gray-900 mb-3">Response Time (ms)</h4>
                        <div class="h-48">
                            <canvas id="responseTimeChart"></canvas>
                        </div>
                    </div>
                    <div>
                        <h4 class="text-sm font-medium text-gray-900 mb-3">Memory Usage (MB)</h4>
                        <div class="h-48">
                            <canvas id="memoryChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div>
                        <h4 class="text-sm font-medium text-gray-900 mb-3">Database Queries</h4>
                        <div class="h-48">
                            <canvas id="queryChart"></canvas>
                        </div>
                    </div>
                    <div>
                        <h4 class="text-sm font-medium text-gray-900 mb-3">Request Volume</h4>
                        <div class="h-48">
                            <canvas id="requestChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Information -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">System Information</h3>
                </div>
                <div class="p-6">
                    <dl class="space-y-4">
                        <div class="flex justify-between">
                            <dt class="text-sm font-medium text-gray-500">PHP Version:</dt>
                            <dd class="text-sm text-gray-900">{{ $systemInfo['php_version'] }}</dd>
                        </div>
                        
                        <div class="flex justify-between">
                            <dt class="text-sm font-medium text-gray-500">Laravel Version:</dt>
                            <dd class="text-sm text-gray-900">{{ $systemInfo['laravel_version'] }}</dd>
                        </div>
                        
                        <div class="flex justify-between">
                            <dt class="text-sm font-medium text-gray-500">Server:</dt>
                            <dd class="text-sm text-gray-900">{{ $systemInfo['server_software'] }}</dd>
                        </div>
                        
                        <div class="flex justify-between">
                            <dt class="text-sm font-medium text-gray-500">Memory Limit:</dt>
                            <dd class="text-sm text-gray-900">{{ $systemInfo['memory_limit'] }}</dd>
                        </div>
                        
                        <div class="flex justify-between">
                            <dt class="text-sm font-medium text-gray-500">Max Execution Time:</dt>
                            <dd class="text-sm text-gray-900">{{ $systemInfo['max_execution_time'] }}s</dd>
                        </div>
                        
                        <div class="flex justify-between">
                            <dt class="text-sm font-medium text-gray-500">OPcache:</dt>
                            <dd class="text-sm">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $systemInfo['opcache_enabled'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $systemInfo['opcache_enabled'] ? 'Enabled' : 'Disabled' }}
                                </span>
                            </dd>
                        </div>
                        
                        <div class="flex justify-between">
                            <dt class="text-sm font-medium text-gray-500">Redis:</dt>
                            <dd class="text-sm">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $systemInfo['redis_connected'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $systemInfo['redis_connected'] ? 'Connected' : 'Disconnected' }}
                                </span>
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-900">Database Statistics</h3>
                    <button class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500" onclick="refreshDatabaseStats()">
                        <svg class="w-4 h-4 mr-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4"/>
                            <path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4"/>
                        </svg>
                        Refresh
                    </button>
                </div>
                <div class="p-6" id="databaseStats">
                    <div class="text-center text-gray-600">
                        <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600 mx-auto"></div>
                        <div class="mt-2 text-sm">Loading database statistics...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

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
                beginAtZero: true
            }
        },
        plugins: {
            legend: {
                display: false
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
                label: 'Response Time (ms)',
                data: [],
                borderColor: 'rgb(32, 107, 196)',
                backgroundColor: 'rgba(32, 107, 196, 0.1)',
                tension: 0.4
            }]
        },
        options: chartOptions
    });

    // Memory Chart
    const memoryCtx = document.getElementById('memoryChart').getContext('2d');
    memoryChart = new Chart(memoryCtx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Memory Usage (MB)',
                data: [],
                borderColor: 'rgb(25, 135, 84)',
                backgroundColor: 'rgba(25, 135, 84, 0.1)',
                tension: 0.4
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
                label: 'Queries',
                data: [],
                backgroundColor: 'rgba(255, 193, 7, 0.8)',
                borderColor: 'rgb(255, 193, 7)',
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
                label: 'Requests',
                data: [],
                backgroundColor: 'rgba(220, 53, 69, 0.8)',
                borderColor: 'rgb(220, 53, 69)',
                borderWidth: 1
            }]
        },
        options: chartOptions
    });
    
    refreshCharts();
}

function refreshData() {
    axios.get('/admin/performance/data', {
        params: { hours: currentPeriod }
    })
    .then(response => {
        const data = response.data;
        updateSummaryCards(data.summary);
        updateCharts(data.charts);
        updateAlerts(data.alerts);
    })
    .catch(error => {
        console.error('Failed to refresh data:', error);
        toastr.error('Failed to refresh performance data');
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
        console.error('Failed to refresh charts:', error);
    });
}

function updateCharts(chartData) {
    // Response Time Chart
    responseTimeChart.data.labels = chartData.timestamps;
    responseTimeChart.data.datasets[0].data = chartData.response_times;
    responseTimeChart.update();

    // Memory Chart (convert bytes to MB)
    memoryChart.data.labels = chartData.timestamps;
    memoryChart.data.datasets[0].data = chartData.memory_usage.map(bytes => Math.round(bytes / 1024 / 1024 * 100) / 100);
    memoryChart.update();

    // Query Chart
    queryChart.data.labels = chartData.timestamps;
    queryChart.data.datasets[0].data = chartData.query_counts;
    queryChart.update();

    // Request Chart (approximate from timestamp intervals)
    const requestCounts = new Array(chartData.timestamps.length).fill(1);
    requestChart.data.labels = chartData.timestamps;
    requestChart.data.datasets[0].data = requestCounts;
    requestChart.update();
}

function updateSummaryCards(summary) {
    // Update summary cards dynamically if needed
    console.log('Summary updated:', summary);
}

function updateAlerts(alerts) {
    // Update alerts dynamically if needed
    console.log('Alerts updated:', alerts);
}

function changePeriod(hours) {
    currentPeriod = hours;
    
    // Update button text
    const button = document.getElementById('period-button');
    if (hours === 1) {
        button.innerHTML = '1h <svg class="w-4 h-4 inline ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>';
    } else if (hours === 24) {
        button.innerHTML = '24h <svg class="w-4 h-4 inline ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>';
    } else if (hours === 168) {
        button.innerHTML = '7d <svg class="w-4 h-4 inline ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>';
    }
    
    // Hide dropdown
    document.getElementById('period-menu').classList.add('hidden');
    
    refreshData();
}

function loadDatabaseStats() {
    axios.get('/admin/performance/database-stats')
        .then(response => {
            const stats = response.data;
            let html = '<dl class="space-y-4">';
            
            if (stats.connections !== undefined) {
                html += `<div class="flex justify-between"><dt class="text-sm font-medium text-gray-500">Connections:</dt><dd class="text-sm text-gray-900">${stats.connections}</dd></div>`;
            }
            
            if (stats.database_size) {
                html += `<div class="flex justify-between"><dt class="text-sm font-medium text-gray-500">Database Size:</dt><dd class="text-sm text-gray-900">${stats.database_size}</dd></div>`;
            }
            
            if (stats.slow_queries && stats.slow_queries.length > 0) {
                html += `<div class="flex justify-between"><dt class="text-sm font-medium text-gray-500">Slow Queries:</dt><dd class="text-sm text-gray-900">${stats.slow_queries.length}</dd></div>`;
            }
            
            html += '</dl>';
            
            document.getElementById('databaseStats').innerHTML = html;
        })
        .catch(error => {
            document.getElementById('databaseStats').innerHTML = 
                '<div class="text-red-600 text-sm">Failed to load database statistics</div>';
        });
}

function refreshDatabaseStats() {
    document.getElementById('databaseStats').innerHTML = 
        '<div class="text-center text-gray-600"><div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600 mx-auto"></div><div class="mt-2 text-sm">Loading...</div></div>';
    loadDatabaseStats();
}

function toggleOptimizeMenu() {
    const dropdown = document.getElementById('optimize-dropdown');
    dropdown.classList.toggle('hidden');
}

function togglePeriodMenu() {
    const dropdown = document.getElementById('period-menu');
    dropdown.classList.toggle('hidden');
}

// Close dropdowns when clicking outside
document.addEventListener('click', function(event) {
    const optimizeButton = document.getElementById('optimize-menu');
    const optimizeDropdown = document.getElementById('optimize-dropdown');
    const periodButton = document.getElementById('period-button');
    const periodDropdown = document.getElementById('period-menu');
    
    if (!optimizeButton.contains(event.target) && !optimizeDropdown.contains(event.target)) {
        optimizeDropdown.classList.add('hidden');
    }
    
    if (!periodButton.contains(event.target) && !periodDropdown.contains(event.target)) {
        periodDropdown.classList.add('hidden');
    }
});

function clearCache() {
    axios.post('/admin/performance/clear-cache')
        .then(response => {
            toastr.success(response.data.message);
            refreshData();
        })
        .catch(error => {
            toastr.error(error.response?.data?.error || 'Failed to clear cache');
        });
}

function optimizeDatabase() {
    axios.post('/admin/performance/optimize-database')
        .then(response => {
            toastr.success(response.data.message);
            refreshDatabaseStats();
        })
        .catch(error => {
            toastr.error(error.response?.data?.error || 'Failed to optimize database');
        });
}

function clearApplicationCache() {
    axios.post('/admin/performance/clear-app-cache')
        .then(response => {
            toastr.success(response.data.message);
        })
        .catch(error => {
            toastr.error(error.response?.data?.error || 'Failed to clear application cache');
        });
}
</script>
@endsection