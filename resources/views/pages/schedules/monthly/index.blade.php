@extends('layouts.authenticated-unified')

@section('title', 'Monthly Schedules')

@section('page-content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <div class="p-6 lg:p-8">
        <!-- Modern Page Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Monthly Schedules</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Kelola template jadwal bulanan dan penugasan karyawan</p>
                </div>
                <div class="flex items-center space-x-3">
                    <button type="button" 
                            onclick="loadSchedules()"
                            class="bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 px-4 py-2 rounded-lg font-medium transition-all duration-200 hover:shadow-md">
                        <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Refresh
                    </button>
                    <a href="{{ route('schedule-management.monthly.create') }}" 
                       class="bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white px-6 py-2 rounded-lg font-medium transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                        <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Create Schedule
                    </a>
                </div>
            </div>
        </div>

        <!-- Modern Statistics Cards with Glassmorphism -->
        <div id="stats-container" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
            <!-- Enhanced Loading state - Total Schedules -->
            <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm border border-white/20 dark:border-gray-700/50 rounded-2xl shadow-xl p-6 hover:shadow-2xl transition-all duration-300">
                <div class="animate-pulse">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-gradient-to-br from-blue-400/20 to-blue-600/20 rounded-xl shadow-lg">
                            <div class="w-6 h-6 bg-blue-300 dark:bg-blue-500 rounded animate-pulse"></div>
                        </div>
                        <div class="w-16 h-4 bg-gradient-to-r from-blue-200 to-blue-300 dark:bg-gradient-to-r dark:from-blue-600 dark:to-blue-700 rounded-full"></div>
                    </div>
                    <div class="h-8 bg-gradient-to-r from-gray-200 to-gray-300 dark:from-gray-600 dark:to-gray-700 rounded w-16 mb-2"></div>
                    <div class="h-4 bg-gradient-to-r from-gray-200 to-gray-300 dark:from-gray-600 dark:to-gray-700 rounded w-24"></div>
                </div>
            </div>
            
            <!-- Enhanced Loading state - Active Schedules -->
            <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm border border-white/20 dark:border-gray-700/50 rounded-2xl shadow-xl p-6 hover:shadow-2xl transition-all duration-300">
                <div class="animate-pulse">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-gradient-to-br from-emerald-400/20 to-emerald-600/20 rounded-xl shadow-lg">
                            <div class="w-6 h-6 bg-emerald-300 dark:bg-emerald-500 rounded animate-pulse"></div>
                        </div>
                        <div class="w-16 h-4 bg-gradient-to-r from-emerald-200 to-emerald-300 dark:bg-gradient-to-r dark:from-emerald-600 dark:to-emerald-700 rounded-full"></div>
                    </div>
                    <div class="h-8 bg-gradient-to-r from-gray-200 to-gray-300 dark:from-gray-600 dark:to-gray-700 rounded w-16 mb-2"></div>
                    <div class="h-4 bg-gradient-to-r from-gray-200 to-gray-300 dark:from-gray-600 dark:to-gray-700 rounded w-24"></div>
                </div>
            </div>
            
            <!-- Enhanced Loading state - Assigned Employees -->
            <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm border border-white/20 dark:border-gray-700/50 rounded-2xl shadow-xl p-6 hover:shadow-2xl transition-all duration-300">
                <div class="animate-pulse">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-gradient-to-br from-amber-400/20 to-amber-600/20 rounded-xl shadow-lg">
                            <div class="w-6 h-6 bg-amber-300 dark:bg-amber-500 rounded animate-pulse"></div>
                        </div>
                        <div class="w-16 h-4 bg-gradient-to-r from-amber-200 to-amber-300 dark:bg-gradient-to-r dark:from-amber-600 dark:to-amber-700 rounded-full"></div>
                    </div>
                    <div class="h-8 bg-gradient-to-r from-gray-200 to-gray-300 dark:from-gray-600 dark:to-gray-700 rounded w-16 mb-2"></div>
                    <div class="h-4 bg-gradient-to-r from-gray-200 to-gray-300 dark:from-gray-600 dark:to-gray-700 rounded w-28"></div>
                </div>
            </div>
            
            <!-- Enhanced Loading state - This Month -->
            <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm border border-white/20 dark:border-gray-700/50 rounded-2xl shadow-xl p-6 hover:shadow-2xl transition-all duration-300">
                <div class="animate-pulse">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-gradient-to-br from-purple-400/20 to-purple-600/20 rounded-xl shadow-lg">
                            <div class="w-6 h-6 bg-purple-300 dark:bg-purple-500 rounded animate-pulse"></div>
                        </div>
                        <div class="w-16 h-4 bg-gradient-to-r from-purple-200 to-purple-300 dark:bg-gradient-to-r dark:from-purple-600 dark:to-purple-700 rounded-full"></div>
                    </div>
                    <div class="h-8 bg-gradient-to-r from-gray-200 to-gray-300 dark:from-gray-600 dark:to-gray-700 rounded w-16 mb-2"></div>
                    <div class="h-4 bg-gradient-to-r from-gray-200 to-gray-300 dark:from-gray-600 dark:to-gray-700 rounded w-20"></div>
                </div>
            </div>
        </div>

        <!-- Enhanced Modern Filters Section -->
        <div class="mb-8">
            <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm border border-white/20 dark:border-gray-700/50 rounded-2xl shadow-xl p-6 hover:shadow-2xl transition-all duration-300">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center space-x-3">
                        <div class="p-2 bg-gradient-to-br from-indigo-500/20 to-purple-600/20 rounded-xl">
                            <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.207A1 1 0 013 6.5V4z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Filter & Search</h3>
                    </div>
                    <button onclick="clearFilters()" 
                            class="px-4 py-2 text-sm font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-900/20 dark:hover:bg-indigo-900/40 rounded-lg transition-all duration-200 hover:shadow-md">
                        <svg class="w-4 h-4 mr-1 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        Clear All
                    </button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            Search
                        </label>
                        <input type="text" id="search" 
                               class="block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700/50 dark:text-white transition-all duration-200 hover:shadow-md backdrop-blur-sm" 
                               placeholder="Cari jadwal...">
                    </div>
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a1 1 0 011-1h6a1 1 0 011 1v4h3a1 1 0 011 1v3a1 1 0 01-.293.707L19 14.414V19a2 2 0 01-2 2H7a2 2 0 01-2-2v-4.586l-.707-.707A1 1 0 014 13v-3a1 1 0 011-1h3z"/>
                            </svg>
                            Bulan
                        </label>
                        <select id="month-filter" 
                                class="block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700/50 dark:text-white transition-all duration-200 hover:shadow-md backdrop-blur-sm">
                            <option value="">Semua Bulan</option>
                            <option value="1">Januari</option>
                            <option value="2">Februari</option>
                            <option value="3">Maret</option>
                            <option value="4">April</option>
                            <option value="5">Mei</option>
                            <option value="6">Juni</option>
                            <option value="7">Juli</option>
                            <option value="8">Agustus</option>
                            <option value="9">September</option>
                            <option value="10">Oktober</option>
                            <option value="11">November</option>
                            <option value="12">Desember</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a1 1 0 011-1h6a1 1 0 011 1v4h3a1 1 0 011 1v3a1 1 0 01-.293.707L19 14.414V19a2 2 0 01-2 2H7a2 2 0 01-2-2v-4.586l-.707-.707A1 1 0 014 13v-3a1 1 0 011-1h3z"/>
                            </svg>
                            Tahun
                        </label>
                        <select id="year-filter" 
                                class="block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700/50 dark:text-white transition-all duration-200 hover:shadow-md backdrop-blur-sm">
                            <option value="">Semua Tahun</option>
                            <option value="2024">2024</option>
                            <option value="2025">2025</option>
                            <option value="2026">2026</option>
                            <option value="2027">2027</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            Lokasi
                        </label>
                        <select id="location-filter" 
                                class="block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700/50 dark:text-white transition-all duration-200 hover:shadow-md backdrop-blur-sm">
                            <option value="">Semua Lokasi</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modern Schedule Cards Grid -->
        <div id="schedules-container" class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-8">
            <!-- Enhanced schedules will be loaded here with modern design -->
        </div>

        <!-- Enhanced Modern Loading State -->
        <div id="loading-state" class="flex flex-col items-center justify-center py-20">
            <div class="relative">
                <div class="animate-spin rounded-full h-16 w-16 border-4 border-gradient-to-r from-blue-200 to-indigo-200 border-t-gradient-to-r from-blue-600 to-indigo-600 mb-6"></div>
                <div class="absolute inset-0 rounded-full border-4 border-transparent bg-gradient-to-r from-blue-600 to-indigo-600 opacity-20 animate-pulse"></div>
            </div>
            <div class="text-center space-y-2">
                <p class="text-lg font-semibold text-gray-700 dark:text-gray-300">Memuat jadwal...</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">Mohon tunggu sebentar</p>
            </div>
        </div>

        <!-- Enhanced Modern Empty State -->
        <div id="empty-state" class="hidden">
            <div class="text-center py-20">
                <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm border border-white/20 dark:border-gray-700/50 rounded-3xl shadow-2xl max-w-md mx-auto p-12 hover:shadow-3xl transition-all duration-500">
                    <div class="relative mb-8">
                        <div class="p-6 bg-gradient-to-br from-blue-500/20 to-indigo-600/20 rounded-2xl mx-auto w-24 h-24 flex items-center justify-center backdrop-blur-sm">
                            <svg class="w-12 h-12 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a1 1 0 011-1h6a1 1 0 011 1v4h3a1 1 0 011 1v3a1 1 0 01-.293.707L19 14.414V19a2 2 0 01-2 2H7a2 2 0 01-2-2v-4.586l-.707-.707A1 1 0 014 13v-3a1 1 0 011-1h3z"></path>
                            </svg>
                        </div>
                        <div class="absolute -top-2 -right-2 w-6 h-6 bg-gradient-to-r from-amber-400 to-orange-500 rounded-full animate-bounce"></div>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">Belum ada jadwal</h3>
                    <p class="text-gray-500 dark:text-gray-400 mb-8 leading-relaxed">Mulai dengan membuat template jadwal bulanan pertama Anda untuk mengatur waktu kerja karyawan.</p>
                    <a href="{{ route('schedule-management.monthly.create') }}" 
                       class="bg-gradient-to-r from-blue-600 to-indigo-700 hover:from-blue-700 hover:to-indigo-800 text-white px-8 py-4 rounded-xl font-semibold transition-all duration-300 inline-flex items-center shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Buat Jadwal Pertama
                    </a>
                </div>
            </div>
        </div>

<!-- Enhanced Modern Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-black/60 backdrop-blur-sm">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="bg-white/90 dark:bg-gray-800/90 backdrop-blur-sm border border-white/20 dark:border-gray-700/50 rounded-3xl shadow-2xl max-w-md w-full p-8 transform transition-all duration-300 hover:shadow-3xl">
            <div class="flex items-center justify-center mb-6">
                <div class="relative">
                    <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-red-500/20 to-rose-600/20 flex items-center justify-center backdrop-blur-sm">
                        <svg class="w-8 h-8 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"></path>
                        </svg>
                    </div>
                    <div class="absolute -top-2 -right-2 w-6 h-6 bg-gradient-to-r from-red-400 to-rose-500 rounded-full animate-pulse"></div>
                </div>
            </div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-3 text-center">Delete Schedule</h3>
            <p class="text-gray-500 dark:text-gray-400 mb-8 text-center leading-relaxed">Are you sure you want to delete this schedule? This action cannot be undone and will affect all assigned employees.</p>
            <div class="flex space-x-4">
                <button onclick="closeDeleteModal()" class="flex-1 inline-flex items-center justify-center px-6 py-3 bg-gray-100 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition-all duration-200 hover:shadow-md">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Cancel
                </button>
                <button id="confirmDeleteBtn" class="flex-1 inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-red-600 to-rose-700 hover:from-red-700 hover:to-rose-800 border border-transparent rounded-xl text-sm font-semibold text-white focus:outline-none focus:ring-2 focus:ring-red-500 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Delete
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
window.schedules = [];
window.filteredSchedules = [];
window.scheduleToDelete = null;

// Load schedules on page load
document.addEventListener('DOMContentLoaded', function() {
    // Check for assignment success message
    const assignmentSuccess = sessionStorage.getItem('assignmentSuccess');
    if (assignmentSuccess) {
        const data = JSON.parse(assignmentSuccess);
        showNotification(data.message, 'success');
        sessionStorage.removeItem('assignmentSuccess');
    }
    
    loadSchedules();
    loadStats();
    setupFilters();
});

async function loadStats() {
    const container = document.getElementById('stats-container');
    
    try {
        const response = await fetch('/api/schedule-management/dashboard/stats');
        const stats = await response.json();
        
        container.innerHTML = `
            <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm border border-white/20 dark:border-gray-700/50 rounded-2xl shadow-xl p-6 hover:shadow-2xl transition-all duration-300 group">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-gradient-to-br from-blue-500/20 to-blue-600/20 rounded-xl shadow-lg group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a1 1 0 011-1h6a1 1 0 011 1v4h3a1 1 0 011 1v3a1 1 0 01-.293.707L19 14.414V19a2 2 0 01-2 2H7a2 2 0 01-2-2v-4.586l-.707-.707A1 1 0 014 13v-3a1 1 0 011-1h3z"></path>
                        </svg>
                    </div>
                    <span class="text-sm text-blue-600 dark:text-blue-400 font-semibold bg-blue-100 dark:bg-blue-900/30 px-3 py-1 rounded-full">Total</span>
                </div>
                <h3 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-1 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors duration-300">${stats.active_schedules || 0}</h3>
                <p class="text-gray-600 dark:text-gray-400 text-sm font-medium">Total Jadwal</p>
            </div>
            
            <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm border border-white/20 dark:border-gray-700/50 rounded-2xl shadow-xl p-6 hover:shadow-2xl transition-all duration-300 group">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-gradient-to-br from-emerald-500/20 to-emerald-600/20 rounded-xl shadow-lg group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <span class="text-sm text-emerald-600 dark:text-emerald-400 font-semibold bg-emerald-100 dark:bg-emerald-900/30 px-3 py-1 rounded-full">Aktif</span>
                </div>
                <h3 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-1 group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors duration-300">${stats.total_employees || 0}</h3>
                <p class="text-gray-600 dark:text-gray-400 text-sm font-medium">Karyawan Terdaftar</p>
            </div>
            
            <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm border border-white/20 dark:border-gray-700/50 rounded-2xl shadow-xl p-6 hover:shadow-2xl transition-all duration-300 group">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-gradient-to-br from-amber-500/20 to-amber-600/20 rounded-xl shadow-lg group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                    </div>
                    <span class="text-sm text-amber-600 dark:text-amber-400 font-semibold bg-amber-100 dark:bg-amber-900/30 px-3 py-1 rounded-full">Hari Ini</span>
                </div>
                <h3 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-1 group-hover:text-amber-600 dark:group-hover:text-amber-400 transition-colors duration-300">${stats.today_holidays || 0}</h3>
                <p class="text-gray-600 dark:text-gray-400 text-sm font-medium">Hari Libur</p>
            </div>
            
            <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm border border-white/20 dark:border-gray-700/50 rounded-2xl shadow-xl p-6 hover:shadow-2xl transition-all duration-300 group">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-gradient-to-br from-purple-500/20 to-purple-600/20 rounded-xl shadow-lg group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                        </svg>
                    </div>
                    <span class="text-sm text-purple-600 dark:text-purple-400 font-semibold bg-purple-100 dark:bg-purple-900/30 px-3 py-1 rounded-full">Mengajar</span>
                </div>
                <h3 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-1 group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors duration-300">${stats.teaching_schedules || 0}</h3>
                <p class="text-gray-600 dark:text-gray-400 text-sm font-medium">Jadwal Mengajar</p>
            </div>
        `;
    } catch (error) {
        console.error('Error loading stats:', error);
        // Keep skeleton loading if error
    }
}

async function loadSchedules() {
    const container = document.getElementById('schedules-container');
    const loadingState = document.getElementById('loading-state');
    const emptyState = document.getElementById('empty-state');
    
    try {
        loadingState.classList.remove('hidden');
        container.innerHTML = '';
        emptyState.classList.add('hidden');
        
        const response = await fetch('/api/schedule-management/monthly');
        const data = await response.json();
        
        if (data.success && data.data) {
            window.schedules = data.data;
            window.filteredSchedules = [...window.schedules];
            renderSchedules();
        } else {
            throw new Error('Failed to load schedules');
        }
        
    } catch (error) {
        console.error('Error loading schedules:', error);
        showError('Failed to load schedules. Please try again.');
    } finally {
        loadingState.classList.add('hidden');
    }
}

function renderSchedules() {
    const container = document.getElementById('schedules-container');
    const emptyState = document.getElementById('empty-state');
    
    if (window.filteredSchedules.length === 0) {
        container.innerHTML = '';
        emptyState.classList.remove('hidden');
        return;
    }
    
    emptyState.classList.add('hidden');
    
    container.innerHTML = window.filteredSchedules.map(schedule => `
        <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm border border-white/20 dark:border-gray-700/50 rounded-2xl shadow-xl p-6 hover:shadow-2xl transition-all duration-500 group transform hover:-translate-y-2">
            <div class="flex items-start justify-between mb-6">
                <div class="flex-1">
                    <div class="flex items-center space-x-3 mb-2">
                        <div class="p-2 bg-gradient-to-br from-blue-500/20 to-indigo-600/20 rounded-xl">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a1 1 0 011-1h6a1 1 0 011 1v4h3a1 1 0 011 1v3a1 1 0 01-.293.707L19 14.414V19a2 2 0 01-2 2H7a2 2 0 01-2-2v-4.586l-.707-.707A1 1 0 014 13v-3a1 1 0 011-1h3z"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors duration-300">
                            ${schedule.name}
                        </h3>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 flex items-center ml-11">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a1 1 0 011-1h6a1 1 0 011 1v4h3a1 1 0 011 1v3a1 1 0 01-.293.707L19 14.414V19a2 2 0 01-2 2H7a2 2 0 01-2-2v-4.586l-.707-.707A1 1 0 014 13v-3a1 1 0 011-1h3z"></path>
                        </svg>
                        ${getMonthName(schedule.month)} ${schedule.year}
                    </p>
                </div>
                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold ${schedule.is_active ? 'bg-gradient-to-r from-emerald-100 to-green-100 text-emerald-700 dark:from-emerald-900/40 dark:to-green-900/40 dark:text-emerald-300' : 'bg-gradient-to-r from-gray-100 to-slate-100 text-gray-600 dark:from-gray-800/40 dark:to-slate-800/40 dark:text-gray-400'}">
                    ${schedule.is_active ? '✓ Active' : '⊗ Inactive'}
                </span>
            </div>
            
            <div class="space-y-4 mb-8">
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl p-4 border border-blue-100 dark:border-blue-800/30">
                        <div class="flex items-center space-x-3 mb-2">
                            <div class="p-1.5 bg-blue-500/20 rounded-lg">
                                <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </div>
                            <span class="text-sm font-medium text-blue-700 dark:text-blue-300">Lokasi</span>
                        </div>
                        <p class="text-sm text-blue-800 dark:text-blue-200 font-semibold">${schedule.location?.name || 'Unknown Location'}</p>
                    </div>
                    <div class="bg-gradient-to-r from-emerald-50 to-green-50 dark:from-emerald-900/20 dark:to-green-900/20 rounded-xl p-4 border border-emerald-100 dark:border-emerald-800/30">
                        <div class="flex items-center space-x-3 mb-2">
                            <div class="p-1.5 bg-emerald-500/20 rounded-lg">
                                <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                            </div>
                            <span class="text-sm font-medium text-emerald-700 dark:text-emerald-300">Karyawan</span>
                        </div>
                        <p class="text-sm text-emerald-800 dark:text-emerald-200 font-semibold">${schedule.assigned_employees_count || 0} assigned</p>
                    </div>
                </div>
                <div class="bg-gradient-to-r from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20 rounded-xl p-4 border border-amber-100 dark:border-amber-800/30">
                    <div class="flex items-center space-x-3 mb-3">
                        <div class="p-1.5 bg-amber-500/20 rounded-lg">
                            <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-amber-700 dark:text-amber-300">Jadwal Kerja</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <p class="text-sm text-amber-800 dark:text-amber-200 font-semibold">${schedule.default_start_time} - ${schedule.default_end_time}</p>
                        <span class="text-xs bg-amber-200 dark:bg-amber-800/40 text-amber-700 dark:text-amber-300 px-2 py-1 rounded-full font-medium">${formatDate(schedule.start_date)} - ${formatDate(schedule.end_date)}</span>
                    </div>
                </div>
            </div>
            
            <div class="flex items-center justify-between pt-6 border-t border-gray-200/50 dark:border-gray-700/50">
                <a href="/schedule-management/monthly/${schedule.id}" class="inline-flex items-center px-4 py-2.5 text-sm font-semibold rounded-xl bg-gradient-to-r from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600 text-gray-700 dark:text-gray-200 hover:from-gray-200 hover:to-gray-300 dark:hover:from-gray-600 dark:hover:to-gray-500 transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    View
                </a>
                <div class="flex items-center space-x-2">
                    <a href="/schedule-management/monthly/${schedule.id}/edit" class="inline-flex items-center px-4 py-2.5 text-sm font-semibold rounded-xl bg-gradient-to-r from-emerald-600 to-green-600 text-white hover:from-emerald-700 hover:to-green-700 transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Edit
                    </a>
                    <button onclick="assignEmployees('${schedule.id}')" class="inline-flex items-center px-4 py-2.5 text-sm font-semibold rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 text-white hover:from-blue-700 hover:to-indigo-700 transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Assign
                    </button>
                    <button onclick="openDeleteModal('${schedule.id}', '${schedule.name}')" class="inline-flex items-center p-2.5 text-sm font-semibold rounded-xl bg-gradient-to-r from-red-500 to-rose-600 text-white hover:from-red-600 hover:to-rose-700 transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    `).join('');
}

function setupFilters() {
    const searchInput = document.getElementById('search');
    const monthFilter = document.getElementById('month-filter');
    const yearFilter = document.getElementById('year-filter');
    const locationFilter = document.getElementById('location-filter');
    
    // Set current month/year as default
    const now = new Date();
    monthFilter.value = now.getMonth() + 1;
    yearFilter.value = now.getFullYear();
    
    [searchInput, monthFilter, yearFilter, locationFilter].forEach(element => {
        element.addEventListener('change', applyFilters);
        element.addEventListener('input', applyFilters);
    });
}

function applyFilters() {
    const search = document.getElementById('search').value.toLowerCase();
    const month = document.getElementById('month-filter').value;
    const year = document.getElementById('year-filter').value;
    const location = document.getElementById('location-filter').value;
    
    window.filteredSchedules = window.schedules.filter(schedule => {
        const matchesSearch = !search || schedule.name.toLowerCase().includes(search);
        const matchesMonth = !month || schedule.month.toString() === month;
        const matchesYear = !year || schedule.year.toString() === year;
        const matchesLocation = !location || schedule.location_id === location;
        
        return matchesSearch && matchesMonth && matchesYear && matchesLocation;
    });
    
    renderSchedules();
}

function clearFilters() {
    document.getElementById('search').value = '';
    document.getElementById('month-filter').value = '';
    document.getElementById('year-filter').value = '';
    document.getElementById('location-filter').value = '';
    applyFilters();
}

function assignEmployees(scheduleId) {
    window.location.href = `/schedule-management/assign?schedule_id=${scheduleId}`;
}

function openDeleteModal(scheduleId, scheduleName) {
    window.scheduleToDelete = scheduleId;
    document.querySelector('#deleteModal p').textContent = `Are you sure you want to delete "${scheduleName}"? This action cannot be undone.`;
    document.getElementById('deleteModal').classList.remove('hidden');
}

function closeDeleteModal() {
    window.scheduleToDelete = null;
    document.getElementById('deleteModal').classList.add('hidden');
}

document.getElementById('confirmDeleteBtn').addEventListener('click', async function() {
    if (!window.scheduleToDelete) return;
    
    try {
        this.disabled = true;
        this.textContent = 'Deleting...';
        
        const response = await fetch(`/api/schedule-management/monthly/${window.scheduleToDelete}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json'
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            showSuccess('Schedule deleted successfully');
            closeDeleteModal();
            loadSchedules();
            loadStats();
        } else {
            throw new Error(result.message || 'Failed to delete schedule');
        }
        
    } catch (error) {
        console.error('Error deleting schedule:', error);
        showError('Failed to delete schedule. Please try again.');
    } finally {
        this.disabled = false;
        this.textContent = 'Delete';
    }
});

// Utility functions
function getMonthName(monthNumber) {
    const months = [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
    ];
    return months[monthNumber - 1] || 'Unknown';
}

function formatDate(dateString) {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

function showSuccess(message) {
    if (typeof showNotification === 'function') {
        showNotification(message, 'success');
    } else {
        alert(message);
    }
}

function showError(message) {
    if (typeof showNotification === 'function') {
        showNotification(message, 'error');
    } else {
        alert(message);
    }
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 z-50 px-6 py-4 rounded-lg shadow-lg transition-all transform ${
        type === 'success' ? 'bg-green-500 text-white' :
        type === 'error' ? 'bg-red-500 text-white' :
        type === 'warning' ? 'bg-yellow-500 text-white' :
        'bg-blue-500 text-white'
    }`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 5000);
}
</script>
@endpush

    </div>
</div>
@endsection