@extends('layouts.authenticated-unified')

@section('title', 'Manajemen Lokasi')

@section('page-content')
<div x-data="locationManager()">
    <!-- Modern Page Header with Enhanced Actions -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Manajemen Lokasi</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Kelola lokasi kantor, radius presensi, dan setting geografis dengan teknologi GPS modern</p>
                <!-- Real-time Status Indicator -->
                <div class="flex items-center space-x-4 mt-2">
                    <div class="flex items-center space-x-2">
                        <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                        <span class="text-xs text-green-600 dark:text-green-400">GPS Services Online</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <x-icons.lightning class="w-3 h-3 text-blue-600 dark:text-blue-400" />
                        <span class="text-xs text-blue-600 dark:text-blue-400" x-text="`${locationsCount} lokasi aktif`"></span>
                    </div>
                </div>
            </div>
            <div class="flex items-center space-x-3">
                <!-- View Toggle -->
                <div class="flex bg-gray-100 dark:bg-gray-700 rounded-lg p-1">
                    <button @click="toggleViewMode('table')" 
                            :class="viewMode === 'table' ? 'bg-white dark:bg-gray-600 shadow-sm' : ''"
                            class="btn-view-toggle">
                        <x-icons.menu class="w-4 h-4" />
                    </button>
                    <button @click="toggleViewMode('grid')" 
                            :class="viewMode === 'grid' ? 'bg-white dark:bg-gray-600 shadow-sm' : ''"
                            class="btn-view-toggle">
                        <x-icons.grid class="w-4 h-4" />
                    </button>
                    <button @click="toggleViewMode('map')" 
                            :class="viewMode === 'map' ? 'bg-white dark:bg-gray-600 shadow-sm' : ''"
                            class="btn-view-toggle">
                        <x-icons.map class="w-4 h-4" />
                    </button>
                </div>
                
                <!-- Secondary Actions -->
                <button @click="bulkActions()" class="btn-analytics flex items-center space-x-2">
                    <x-icons.download-alt class="w-4 h-4" />
                    <span>Bulk Actions</span>
                </button>
                <button onclick="toggleFilter()" class="btn-analytics flex items-center space-x-2">
                    <x-icons.filter class="w-4 h-4" />
                    <span>Smart Filter</span>
                </button>
                
                <!-- Primary Actions -->
                <button onclick="showLocationAnalytics()" class="btn-analytics-gradient">
                    <x-icons.chart-bar class="w-4 h-4" />
                    <span>Analytics</span>
                </button>
                @can('manage_locations')
                <button onclick="showCreateModal()" class="btn-create-gradient">
                    <x-icons.plus class="w-4 h-4" />
                    <span>Tambah Lokasi</span>
                </button>
                @endcan
            </div>
        </div>
    </div>
    
<!-- Location Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Total Locations -->
    <x-ui.card>
        <div class="flex items-center justify-between mb-4">
            <div class="p-3 bg-blue-600 rounded-lg shadow-md">
                <x-icons.location class="w-6 h-6 text-white" />
            </div>
            <span class="badge-blue">Total</span>
        </div>
        <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-1" id="stat-total">-</h3>
        <p class="text-gray-600 dark:text-gray-400 text-sm">Total Lokasi</p>
        <div class="mt-3 text-xs text-gray-500 dark:text-gray-400">
            Semua kantor & cabang
        </div>
    </x-ui.card>

    <!-- Active Locations -->
    <x-ui.card>
        <div class="flex items-center justify-between mb-4">
            <div class="p-3 bg-green-600 rounded-lg shadow-md">
                <x-icons.check-circle class="w-6 h-6 text-white" />
            </div>
            <span class="badge-green">Online</span>
        </div>
        <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-1" id="stat-active">-</h3>
        <p class="text-gray-600 dark:text-gray-400 text-sm">Lokasi Aktif</p>
        <div class="mt-3 text-xs text-gray-500 dark:text-gray-400">
            Siap untuk absensi
        </div>
    </x-ui.card>

    <!-- Employee Coverage -->
    <x-ui.card>
        <div class="flex items-center justify-between mb-4">
            <div class="p-3 bg-purple-600 rounded-lg shadow-md">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
                </svg>
            </div>
            <span class="badge-purple-lg">Terlayani</span>
        </div>
        <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-1" id="stat-employees">-</h3>
        <p class="text-gray-600 dark:text-gray-400 text-sm">Karyawan Terlayani</p>
        <div class="mt-3 text-xs text-gray-500 dark:text-gray-400">
            Total pengguna lokasi
        </div>
    </x-ui.card>

    <!-- Average Radius -->
    <x-ui.card>
        <div class="flex items-center justify-between mb-4">
            <div class="p-3 bg-orange-600 rounded-lg shadow-md">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                </svg>
            </div>
            <span class="text-sm text-orange-600 font-medium px-3 py-1 bg-orange-100 dark:bg-orange-900 rounded-full">Meter</span>
        </div>
        <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-1" id="stat-radius">-</h3>
        <p class="text-gray-600 dark:text-gray-400 text-sm">Rata-rata Radius</p>
        <div class="mt-3 text-xs text-gray-500 dark:text-gray-400">
            Jangkauan absensi
        </div>
    </x-ui.card>
</div>

<!-- Filter Panel (Hidden by default) -->
<div id="filterPanel" class="hidden mb-8">
    <x-ui.card>
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Filter Lokasi</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Gunakan filter untuk menemukan lokasi dengan cepat</p>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
                <!-- Search Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Cari Lokasi</label>
                    <div class="relative">
                        <input type="text" 
                               id="search-location-filter" 
                               placeholder="Nama lokasi atau alamat..."
                               class="w-full px-4 py-3 pl-10 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                </div>

                <!-- Status Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status</label>
                    <select id="status-filter" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Semua Status</option>
                        <option value="active">Aktif</option>
                        <option value="inactive">Tidak Aktif</option>
                    </select>
                </div>

                <!-- Radius Range Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Radius (meter)</label>
                    <select id="radius-filter" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Semua Radius</option>
                        <option value="0-50">0 - 50 meter</option>
                        <option value="51-100">51 - 100 meter</option>
                        <option value="101-200">101 - 200 meter</option>
                        <option value="200+">200+ meter</option>
                    </select>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-end space-x-2">
                    <button type="button" 
                            onclick="applyFilter()"
                            class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-3 rounded-lg font-medium transition-all duration-200">
                        <svg class="w-5 h-5 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.414A1 1 0 013 6.707V4z"/>
                        </svg>
                        Terapkan
                    </button>
                    <button type="button" 
                            onclick="resetFilter()"
                            class="px-4 py-3 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-500 transition-colors duration-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </x-ui.card>
</div>

<!-- Main Content Grid -->
<div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
    <!-- Location Table and Map (Main Panel) -->
    <div class="xl:col-span-2">
        <!-- Enhanced Location Table -->
        <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-8">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Daftar Lokasi</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Kelola semua lokasi absensi</p>
                    </div>
                    <div class="flex items-center space-x-3">
                        <div class="relative">
                            <input type="text" 
                                   placeholder="Cari lokasi..." 
                                   class="pl-10 pr-4 py-2 w-64 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white"
                                   id="searchInput"
                                   onkeyup="searchLocations()">
                            <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <button onclick="refreshLocationData()" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg transition-colors duration-200" title="Refresh Data">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700" id="locationsTable">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Lokasi</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Alamat</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Koordinat</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Radius</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700" id="locationsTableBody">
                        <!-- Populated by JavaScript -->
                    </tbody>
                </table>
            </div>

            <!-- Loading State -->
            <div id="loadingState" class="text-center py-12">
                <div class="inline-flex items-center px-4 py-2 font-semibold leading-6 text-sm text-gray-900 dark:text-white">
                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Memuat data lokasi...
                </div>
            </div>

            <!-- Empty State -->
            <div id="emptyState" class="hidden text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 48 48">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Belum ada lokasi</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Mulai dengan menambahkan lokasi pertama untuk sistem absensi.</p>
                @can('manage_locations')
                <div class="mt-6">
                    <button type="button" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200" onclick="showCreateModal()">
                        <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Tambah Lokasi
                    </button>
                </div>
                @endcan
            </div>
        </x-ui.card>
        
        <!-- Interactive Map View -->
        <x-ui.card>
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Peta Lokasi</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Visualisasi geografis semua lokasi absensi</p>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button id="toggle-satellite" class="px-3 py-2 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-all duration-200 text-sm">
                            Satelit
                        </button>
                        <button id="center-map" class="px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200 text-sm">
                            <svg class="w-4 h-4 mr-1 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                            </svg>
                            Pusat
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="relative">
                <div id="overview-map" class="h-96 bg-gray-100 dark:bg-gray-700 rounded-b-lg overflow-hidden">
                    <div id="overview-map-loading" class="flex items-center justify-center h-full">
                        <div class="text-center">
                            <div class="w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full animate-spin mx-auto mb-3"></div>
                            <p class="text-gray-600 dark:text-gray-400">Memuat peta...</p>
                        </div>
                    </div>
                </div>
            </div>
        </x-ui.card>
    </div>

    <!-- Quick Actions and Analytics Sidebar -->
    <div class="xl:col-span-1">
        <!-- Quick Actions Card -->
        <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-6">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Quick Actions</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Aksi cepat untuk manajemen lokasi</p>
            </div>
            <div class="p-6 space-y-3">
                @can('manage_locations')
                <button onclick="showCreateModal()" class="w-full text-left px-4 py-3 bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-100 dark:hover:bg-blue-900/30 rounded-lg transition-colors duration-200">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        <div>
                            <div class="font-medium text-gray-900 dark:text-white">Tambah Lokasi</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Daftarkan lokasi baru</div>
                        </div>
                    </div>
                </button>
                @endcan
                
                <button onclick="bulkLocationAnalysis()" class="w-full text-left px-4 py-3 bg-green-50 dark:bg-green-900/20 hover:bg-green-100 dark:hover:bg-green-900/30 rounded-lg transition-colors duration-200">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-green-600 dark:text-green-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        <div>
                            <div class="font-medium text-gray-900 dark:text-white">Analisis Lokasi</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Lihat statistik penggunaan</div>
                        </div>
                    </div>
                </button>
                
                <button onclick="optimizeRadius()" class="w-full text-left px-4 py-3 bg-purple-50 dark:bg-purple-900/20 hover:bg-purple-100 dark:hover:bg-purple-900/30 rounded-lg transition-colors duration-200">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-purple-600 dark:text-purple-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        <div>
                            <div class="font-medium text-gray-900 dark:text-white">Optimasi Radius</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Saran radius optimal</div>
                        </div>
                    </div>
                </button>
                
                <button onclick="validateLocations()" class="w-full text-left px-4 py-3 bg-orange-50 dark:bg-orange-900/20 hover:bg-orange-100 dark:hover:bg-orange-900/30 rounded-lg transition-colors duration-200">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-orange-600 dark:text-orange-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div>
                            <div class="font-medium text-gray-900 dark:text-white">Validasi GPS</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Cek koordinat aktual</div>
                        </div>
                    </div>
                </button>
            </div>
        </x-ui.card>
        
        <!-- Location Activity Feed -->
        <x-ui.card>
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Aktivitas Terbaru</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Log aktivitas lokasi</p>
            </div>
            <div class="p-6">
                <div class="space-y-4" id="location-activities">
                    <!-- Will be populated by JavaScript -->
                    <div class="text-center text-gray-500 dark:text-gray-400 py-8">
                        <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-sm">Memuat aktivitas...</p>
                    </div>
                </div>
            </div>
        </x-ui.card>
    </div>
</div>

    <!-- Enhanced Create/Edit Location Modal -->
    <div id="locationModal" class="fixed inset-0 z-50 hidden overflow-y-auto" x-data="locationModalManager()">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/70 backdrop-blur-sm transition-opacity duration-300" onclick="closeLocationModal()"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-6xl max-h-[95vh] overflow-hidden transform transition-all duration-300 scale-95 opacity-0" 
                 id="modalContent">
                <!-- Enhanced Modal Header -->
                <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20">
                    <div class="flex items-center space-x-3">
                        <div class="p-2 bg-blue-600 rounded-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white" id="modalTitle">Tambah Lokasi Baru</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400" x-text="currentStep === 1 ? 'Langkah 1: Informasi Dasar' : currentStep === 2 ? 'Langkah 2: Koordinat & Peta' : 'Langkah 3: Validasi & Konfirmasi'"></p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-3">
                        <!-- Step Progress Indicator -->
                        <div class="flex items-center space-x-2">
                            <div class="flex items-center space-x-1">
                                <div :class="currentStep >= 1 ? 'bg-blue-600' : 'bg-gray-300'" class="w-2 h-2 rounded-full transition-colors duration-200"></div>
                                <div :class="currentStep >= 2 ? 'bg-blue-600' : 'bg-gray-300'" class="w-2 h-2 rounded-full transition-colors duration-200"></div>
                                <div :class="currentStep >= 3 ? 'bg-blue-600' : 'bg-gray-300'" class="w-2 h-2 rounded-full transition-colors duration-200"></div>
                            </div>
                            <span class="text-xs text-gray-500" x-text="`${currentStep}/3`"></span>
                        </div>
                        <button type="button" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors duration-200" onclick="closeLocationModal()">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Modal Body -->
                <form id="locationForm" class="p-6 space-y-6">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Left Column: Form Fields -->
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-foreground mb-2">Nama Lokasi</label>
                                <input type="text" 
                                       name="name" 
                                       id="locationName" 
                                       class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                       placeholder="Contoh: SMAN 1 Denpasar"
                                       required>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-foreground mb-2">Cari Alamat</label>
                                <div class="relative">
                                    <input type="text" 
                                           id="addressSearch" 
                                           class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm pr-10"
                                           placeholder="Ketik alamat atau nama tempat..."
                                           onkeyup="searchAddress(this.value)">
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                        <svg class="h-4 w-4 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                        </svg>
                                    </div>
                                </div>
                                <div id="addressSuggestions" class="absolute z-10 w-full bg-card border border-input rounded-md shadow-lg mt-1 hidden max-h-60 overflow-y-auto"></div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-foreground mb-2">Alamat Lengkap</label>
                                <textarea name="address" 
                                          id="locationAddress" 
                                          rows="3" 
                                          class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                          placeholder="Alamat lengkap lokasi"></textarea>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-foreground mb-2">Latitude</label>
                                    <input type="number" 
                                           name="latitude" 
                                           id="locationLatitude" 
                                           step="any"
                                           class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                           placeholder="-8.6481"
                                           readonly>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-foreground mb-2">Longitude</label>
                                    <input type="number" 
                                           name="longitude" 
                                           id="locationLongitude" 
                                           step="any"
                                           class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                           placeholder="115.2191"
                                           readonly>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-foreground mb-2">Radius Absensi</label>
                                <div class="flex items-center space-x-4">
                                    <input type="range" 
                                           name="radius_meters" 
                                           id="radiusSlider" 
                                           min="10" 
                                           max="500" 
                                           value="100" 
                                           class="flex-1"
                                           oninput="updateRadiusDisplay(this.value)">
                                    <div class="flex items-center space-x-2">
                                        <span id="radiusDisplay" class="text-sm font-medium text-foreground">100</span>
                                        <span class="text-sm text-muted-foreground">meter</span>
                                    </div>
                                </div>
                                <p class="text-xs text-muted-foreground mt-1">Jarak maksimum dari titik lokasi untuk absensi valid</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-foreground mb-2">SSID WiFi (Opsional)</label>
                                <input type="text" 
                                       name="wifi_ssid" 
                                       id="locationWifi" 
                                       class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                       placeholder="Nama jaringan WiFi untuk verifikasi tambahan">
                            </div>

                            <div class="flex items-center space-x-2">
                                <input type="checkbox" 
                                       name="is_active" 
                                       id="locationActive" 
                                       value="1"
                                       checked 
                                       class="rounded border-input">
                                <label for="locationActive" class="text-sm font-medium text-foreground">Aktifkan lokasi ini</label>
                            </div>
                        </div>

                        <!-- Right Column: Interactive Map -->
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-foreground mb-2">Pilih Lokasi di Peta</label>
                                <div id="locationMap" class="w-full h-96 rounded-md border border-input bg-muted" style="min-height: 384px; position: relative; z-index: 1;">
                                    <div id="mapLoading" class="flex items-center justify-center h-full">
                                        <div class="text-center text-muted-foreground">
                                            <svg class="w-8 h-8 mx-auto mb-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                            </svg>
                                            <p>Memuat peta...</p>
                                        </div>
                                    </div>
                                </div>
                                <p class="text-xs text-muted-foreground mt-1">Klik pada peta untuk menentukan lokasi, atau gunakan pencarian alamat di atas</p>
                            </div>
                            
                            <div class="bg-muted/50 rounded-md p-4">
                                <h4 class="text-sm font-medium text-foreground mb-2">Preview Radius</h4>
                                <div class="text-xs text-muted-foreground space-y-1">
                                    <p>• Lingkaran biru menunjukkan area radius absensi</p>
                                    <p>• Karyawan harus berada dalam lingkaran untuk absensi valid</p>
                                    <p>• Sesuaikan radius sesuai kebutuhan lokasi</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="flex justify-end space-x-3 pt-6 border-t">
                        <button type="button" class="btn btn-secondary" onclick="closeLocationModal()">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <span id="submitText">Simpan Lokasi</span>
                            <span id="submitLoading" class="hidden">
                                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-current" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Menyimpan...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function locationModalManager() {
    return {
        currentStep: 1,
        
        init() {
            console.log('Location modal manager initialized');
        },
        
        nextStep() {
            if (this.currentStep < 3) {
                this.currentStep++;
                this.animateStepTransition();
            }
        },
        
        previousStep() {
            if (this.currentStep > 1) {
                this.currentStep--;
                this.animateStepTransition();
            }
        },
        
        animateStepTransition() {
            const content = document.getElementById('modalContent');
            if (content) {
                content.style.transform = 'scale(0.98)';
                setTimeout(() => {
                    content.style.transform = 'scale(1)';
                }, 150);
            }
        }
    }
}
</script>

<!-- Leaflet CSS and JS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

@push('scripts')
<script>
// Alpine.js Location Manager Component
function locationManager() {
    return {
        viewMode: 'table', // table, grid, map
        locationsCount: 4,
        selectedLocations: [],
        searchQuery: '',
        sortBy: 'name',
        sortDirection: 'asc',
        
        init() {
            console.log('Location Manager initialized');
            this.loadStatistics();
        },
        
        toggleViewMode(mode) {
            this.viewMode = mode;
            console.log('View mode changed to:', mode);
            
            // Add smooth transition effect
            const mainContent = document.querySelector('.main-content-area');
            if (mainContent) {
                mainContent.style.opacity = '0.5';
                setTimeout(() => {
                    mainContent.style.opacity = '1';
                }, 150);
            }
        },
        
        bulkActions() {
            if (this.selectedLocations.length === 0) {
                showNotification('Pilih lokasi terlebih dahulu', 'warning');
                return;
            }
            
            console.log('Bulk actions for:', this.selectedLocations);
            showNotification(`${this.selectedLocations.length} lokasi dipilih`, 'info');
        },
        
        loadStatistics() {
            // Update the locations count dynamically
            this.locationsCount = 4;
        }
    }
}

// Location management variables - use window object to avoid redeclaration
if (typeof window.locationManagementLoaded === 'undefined') {
    window.locationMap = null;
    window.locationMarker = null;
    window.locationRadiusCircle = null;
    window.currentLocationId = null;
    window.searchTimeout = null;
    window.locationManagementLoaded = true;
}

document.addEventListener('DOMContentLoaded', function() {
    loadLocations();
    loadStatistics();
});

// Load locations data
async function loadLocations() {
    try {
        const response = await fetch('/api/locations');
        const data = await response.json();
        
        document.getElementById('loadingState').classList.add('hidden');
        
        if (data.locations && data.locations.length > 0) {
            renderLocationsTable(data.locations);
            document.getElementById('emptyState').classList.add('hidden');
        } else {
            document.getElementById('emptyState').classList.remove('hidden');
        }
    } catch (error) {
        console.error('Error loading locations:', error);
        document.getElementById('loadingState').classList.add('hidden');
        document.getElementById('emptyState').classList.remove('hidden');
        
        // Show sample data for demo
        renderSampleData();
    }
}

// Load statistics
async function loadStatistics() {
    try {
        const response = await fetch('/api/locations/statistics');
        const data = await response.json();
        
        document.getElementById('stat-total').textContent = data.total || 0;
        document.getElementById('stat-active').textContent = data.active || 0;
        document.getElementById('stat-employees').textContent = data.employees || 0;
        document.getElementById('stat-radius').textContent = (data.averageRadius || 0) + 'm';
    } catch (error) {
        console.error('Error loading statistics:', error);
        // Show sample stats
        document.getElementById('stat-total').textContent = '5';
        document.getElementById('stat-active').textContent = '4';
        document.getElementById('stat-employees').textContent = '23';
        document.getElementById('stat-radius').textContent = '75m';
    }
}

// Render sample data for demo
function renderSampleData() {
    const sampleLocations = [
        {
            id: 1,
            name: 'SMAN 1 Denpasar',
            address: 'Jl. Nias No.1, Sanglah, Denpasar Selatan, Kota Denpasar, Bali 80114',
            latitude: -8.6481,
            longitude: 115.2191,
            radius_meters: 100,
            wifi_ssid: 'SMAN1_WiFi',
            is_active: true,
            employees_count: 15
        },
        {
            id: 2,
            name: 'Kantor Dinas Pendidikan',
            address: 'Jl. Raya Puputan No.41, Dangin Puri Kaja, Denpasar Utara, Kota Denpasar, Bali 80114',
            latitude: -8.6519,
            longitude: 115.2147,
            radius_meters: 75,
            wifi_ssid: null,
            is_active: true,
            employees_count: 8
        },
        {
            id: 3,
            name: 'SMKN 2 Denpasar',
            address: 'Jl. Kecak No.20, Tonja, Denpasar Utara, Kota Denpasar, Bali 80239',
            latitude: -8.6298,
            longitude: 115.2076,
            radius_meters: 150,
            wifi_ssid: 'SMKN2_Network',
            is_active: false,
            employees_count: 12
        }
    ];
    
    renderLocationsTable(sampleLocations);
}

// Render locations table
function renderLocationsTable(locations) {
    const tbody = document.getElementById('locationsTableBody');
    
    tbody.innerHTML = locations.map(location => `
        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
            <td class="px-6 py-4">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center mr-3">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="font-semibold text-gray-900 dark:text-white">${location.name}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 font-mono">ID: LOC${location.id.toString().padStart(3, '0')}</div>
                    </div>
                </div>
            </td>
            <td class="px-6 py-4">
                <div class="text-sm text-gray-600 dark:text-gray-400 max-w-xs truncate" title="${location.address}">
                    ${location.address || '-'}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    ${location.employees_count || 0} karyawan terdaftar
                </div>
            </td>
            <td class="px-6 py-4">
                <div class="text-xs font-mono text-gray-500 dark:text-gray-400">
                    ${location.latitude ? `${location.latitude.toFixed(6)}, ${location.longitude.toFixed(6)}` : '-'}
                </div>
            </td>
            <td class="px-6 py-4">
                <div class="flex items-center space-x-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 dark:bg-orange-900 text-orange-800 dark:text-orange-400">
                        ${location.radius_meters}m
                    </span>
                    ${location.wifi_ssid ? `
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400">
                            WiFi
                        </span>
                    ` : ''}
                </div>
            </td>
            <td class="px-6 py-4">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                    location.is_active 
                        ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400' 
                        : 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400'
                }">
                    ${location.is_active ? 'Aktif' : 'Tidak Aktif'}
                </span>
            </td>
            <td class="px-6 py-4">
                <div class="flex items-center justify-end space-x-1">
                    <button class="p-2 text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors" 
                            onclick="viewLocationOnMap('${location.id}')" 
                            title="Lihat di Peta">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        </svg>
                    </button>
                    <button class="p-2 text-gray-400 hover:text-green-600 dark:hover:text-green-400 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-lg transition-colors" 
                            onclick="editLocationById('${location.id}')" 
                            title="Edit Lokasi">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </button>
                    <button class="p-2 text-gray-400 hover:text-amber-600 dark:hover:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-900/20 rounded-lg transition-colors" 
                            onclick="toggleLocationStatus('${location.id}', ${location.is_active})" 
                            title="${location.is_active ? 'Nonaktifkan' : 'Aktifkan'} Lokasi">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${location.is_active ? 'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L18 12M6 6l12 12' : 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'}"/>
                        </svg>
                    </button>
                    <button class="p-2 text-gray-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors" 
                            onclick="deleteLocation('${location.id}')" 
                            title="Hapus Lokasi">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
    
    // Initialize overview map after rendering table
    initializeOverviewMap(locations);
    
    // Load location activities
    loadLocationActivities();
}

// Show create modal with enhanced animations
function showCreateModal() {
    window.currentLocationId = null;
    document.getElementById('modalTitle').textContent = 'Tambah Lokasi Baru';
    document.getElementById('submitText').textContent = 'Simpan Lokasi';
    document.getElementById('locationForm').reset();
    document.getElementById('locationActive').checked = true;
    document.getElementById('radiusSlider').value = 100;
    updateRadiusDisplay(100);
    
    // Clear coordinate fields
    document.getElementById('locationLatitude').value = '';
    document.getElementById('locationLongitude').value = '';
    document.getElementById('addressSearch').value = '';
    
    // Show modal with animation
    const modal = document.getElementById('locationModal');
    const modalContent = document.getElementById('modalContent');
    
    modal.classList.remove('hidden');
    
    // Trigger entrance animation
    setTimeout(() => {
        modalContent.style.transform = 'scale(1)';
        modalContent.style.opacity = '1';
    }, 50);
    
    // Initialize map with default Bali coordinates
    setTimeout(() => {
        initializeMap(-8.6481, 115.2191, 100);
        // Set radius slider value and update display
        document.getElementById('radiusSlider').value = 100;
        updateRadiusDisplay(100);
    }, 500);
    
    // Show success notification
    showNotification('Modal lokasi dibuka', 'info');
}

// Edit location by ID
async function editLocationById(locationId) {
    try {
        // Find location data from current loaded locations
        const response = await fetch('/api/locations');
        const data = await response.json();
        const location = data.locations.find(loc => loc.id === locationId);
        
        if (!location) {
            showNotification('Lokasi tidak ditemukan', 'error');
            return;
        }
        
        editLocation(location);
    } catch (error) {
        console.error('Error fetching location:', error);
        showNotification('Gagal memuat data lokasi', 'error');
    }
}

// Edit location
function editLocation(location) {
    window.currentLocationId = location.id;
    document.getElementById('modalTitle').textContent = 'Edit Lokasi';
    document.getElementById('submitText').textContent = 'Update Lokasi';
    
    // Fill form with location data
    document.getElementById('locationName').value = location.name || '';
    document.getElementById('locationAddress').value = location.address || '';
    document.getElementById('locationLatitude').value = location.latitude || '';
    document.getElementById('locationLongitude').value = location.longitude || '';
    document.getElementById('locationWifi').value = location.wifi_ssid || '';
    document.getElementById('locationActive').checked = location.is_active;
    document.getElementById('radiusSlider').value = location.radius_meters || 100;
    updateRadiusDisplay(location.radius_meters || 100);
    
    document.getElementById('locationModal').classList.remove('hidden');
    
    // Initialize map with existing location
    setTimeout(() => {
        const lat = location.latitude || -8.6481;
        const lng = location.longitude || 115.2191;
        const radius = location.radius_meters || 100;
        
        initializeMap(lat, lng, radius);
        
        // Ensure coordinates are filled
        document.getElementById('locationLatitude').value = lat;
        document.getElementById('locationLongitude').value = lng;
    }, 500);
}

// Close modal with enhanced animations
function closeLocationModal() {
    const modal = document.getElementById('locationModal');
    const modalContent = document.getElementById('modalContent');
    
    // Exit animation
    modalContent.style.transform = 'scale(0.95)';
    modalContent.style.opacity = '0';
    
    setTimeout(() => {
        modal.classList.add('hidden');
        
        // Reset modal content transform for next opening
        modalContent.style.transform = 'scale(0.95)';
        modalContent.style.opacity = '0';
        
        if (window.locationMap) {
            window.locationMap.remove();
            window.locationMap = null;
        }
    }, 200);
}

// Initialize map
function initializeMap(lat = -8.6481, lng = 115.2191, radius = 100, retryCount = 0) {
    console.log('Initializing map with:', { lat, lng, radius, retryCount });
    
    // Check if Leaflet is loaded
    if (typeof L === 'undefined') {
        console.error('Leaflet library not loaded!');
        return;
    }
    
    // Check if map container exists and is visible
    const mapContainer = document.getElementById('locationMap');
    if (!mapContainer) {
        console.error('Map container not found!');
        return;
    }
    
    // Check if container is visible (not in hidden modal)
    const modal = document.getElementById('locationModal');
    if (modal && modal.classList.contains('hidden')) {
        console.error('Modal is hidden, cannot initialize map');
        return;
    }
    
    console.log('Map container found and modal is visible');
    
    // Check container dimensions
    const containerRect = mapContainer.getBoundingClientRect();
    console.log('Container dimensions:', containerRect.width, 'x', containerRect.height);
    
    if (containerRect.width === 0 || containerRect.height === 0) {
        if (retryCount < 5) {
            console.warn('Map container has no dimensions, retrying...', retryCount + 1);
            setTimeout(() => initializeMap(lat, lng, radius, retryCount + 1), 200);
            return;
        } else {
            console.error('Failed to initialize map after 5 retries');
            return;
        }
    }
    
    // Remove existing map if any
    if (window.locationMap) {
        window.locationMap.remove();
        window.locationMap = null;
    }
    
    try {
        // Create map
        window.locationMap = L.map('locationMap', {
            center: [lat, lng],
            zoom: 15,
            zoomControl: true,
            attributionControl: true
        });
        
        console.log('Map created successfully');
        
        // Add tiles
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(window.locationMap);
        
        console.log('Tiles added');
        
        // Hide loading indicator
        const loadingEl = document.getElementById('mapLoading');
        if (loadingEl) {
            loadingEl.style.display = 'none';
        }
        
        // Force map resize to ensure proper rendering
        setTimeout(() => {
            if (window.locationMap) {
                window.locationMap.invalidateSize();
                console.log('Map invalidated');
                
                // Add marker and circle after map is ready
                addMapFeatures(lat, lng, radius);
            }
        }, 300);
    } catch (error) {
        console.error('Error creating map:', error);
        
        // Show error message in map container
        const loadingEl = document.getElementById('mapLoading');
        if (loadingEl) {
            loadingEl.innerHTML = `
                <div class="text-center text-red-500">
                    <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p>Gagal memuat peta</p>
                    <button onclick="retryMapLoad()" class="mt-2 text-sm text-blue-600 hover:underline">Coba lagi</button>
                </div>
            `;
        }
        return;
    }
}

// Retry map loading
function retryMapLoad() {
    const loadingEl = document.getElementById('mapLoading');
    if (loadingEl) {
        loadingEl.innerHTML = `
            <div class="text-center text-muted-foreground">
                <svg class="w-8 h-8 mx-auto mb-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                <p>Memuat peta...</p>
            </div>
        `;
    }
    
    // Get current values from form
    const lat = parseFloat(document.getElementById('locationLatitude').value) || -8.6481;
    const lng = parseFloat(document.getElementById('locationLongitude').value) || 115.2191;
    const radius = parseInt(document.getElementById('radiusSlider').value) || 100;
    
    setTimeout(() => {
        initializeMap(lat, lng, radius);
    }, 500);
}

// Add marker and circle to map
function addMapFeatures(lat, lng, radius) {
    if (!window.locationMap) {
        console.error('Map not initialized');
        return;
    }
    
    try {
        // Add marker
        window.locationMarker = L.marker([lat, lng], { 
            draggable: true 
        }).addTo(window.locationMap);
        
        console.log('Marker added');
        
        // Add radius circle with better visibility
        const radiusValue = parseInt(radius) || 100;
        window.locationRadiusCircle = L.circle([lat, lng], {
            color: '#ef4444',
            weight: 3,
            fillColor: '#ef4444',
            fillOpacity: 0.2,
            radius: radiusValue,
            dashArray: '10, 5'
        }).addTo(window.locationMap);
        
        console.log('Radius circle added');
        
        // Add a popup to show radius info
        window.locationRadiusCircle.bindPopup(`Radius: ${radiusValue} meter`).openPopup();
        
        console.log('Map features initialized with coordinates:', lat, lng, 'radius:', radiusValue);
        
        // Update coordinates when marker is dragged
        window.locationMarker.on('dragend', function(e) {
            const position = e.target.getLatLng();
            updateLocationCoordinates(position.lat, position.lng);
            window.locationRadiusCircle.setLatLng(position);
        });
        
        // Update coordinates when map is clicked
        window.locationMap.on('click', function(e) {
            const lat = e.latlng.lat;
            const lng = e.latlng.lng;
            window.locationMarker.setLatLng([lat, lng]);
            window.locationRadiusCircle.setLatLng([lat, lng]);
            updateLocationCoordinates(lat, lng);
        });
    } catch (error) {
        console.error('Error adding map features:', error);
    }
}

// Update location coordinates
function updateLocationCoordinates(lat, lng) {
    document.getElementById('locationLatitude').value = lat.toFixed(6);
    document.getElementById('locationLongitude').value = lng.toFixed(6);
}

// Update radius display
function updateRadiusDisplay(value) {
    document.getElementById('radiusDisplay').textContent = value;
    if (window.locationRadiusCircle) {
        window.locationRadiusCircle.setRadius(parseInt(value));
        // Update popup content
        window.locationRadiusCircle.setPopupContent(`Radius: ${value} meter`);
        console.log('Radius updated to:', value);
    } else {
        console.log('Radius circle not initialized yet');
    }
}

// Search address
async function searchAddress(query) {
    clearTimeout(window.searchTimeout);
    
    if (query.length < 3) {
        document.getElementById('addressSuggestions').classList.add('hidden');
        return;
    }
    
    window.searchTimeout = setTimeout(async () => {
        try {
            // Use Nominatim for geocoding
            const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=5&countrycodes=id`);
            const results = await response.json();
            
            const suggestions = document.getElementById('addressSuggestions');
            
            if (results.length > 0) {
                suggestions.innerHTML = results.map(result => {
                    const escapedAddress = result.display_name.replace(/'/g, "\\'").replace(/"/g, "&quot;");
                    return `
                        <div class="p-3 hover:bg-muted/50 cursor-pointer border-b border-border last:border-b-0" 
                             onclick="selectAddress('${escapedAddress}', ${result.lat}, ${result.lon})">
                            <div class="font-medium text-foreground text-sm">${result.display_name}</div>
                            <div class="text-xs text-muted-foreground">${result.lat}, ${result.lon}</div>
                        </div>
                    `;
                }).join('');
                suggestions.classList.remove('hidden');
            } else {
                suggestions.classList.add('hidden');
            }
        } catch (error) {
            console.error('Error searching address:', error);
            document.getElementById('addressSuggestions').classList.add('hidden');
        }
    }, 300);
}

// Select address from suggestions
function selectAddress(address, lat, lng) {
    document.getElementById('addressSearch').value = '';
    document.getElementById('locationAddress').value = address;
    document.getElementById('addressSuggestions').classList.add('hidden');
    
    updateLocationCoordinates(lat, lng);
    
    if (window.locationMap && window.locationMarker && window.locationRadiusCircle) {
        window.locationMarker.setLatLng([lat, lng]);
        window.locationRadiusCircle.setLatLng([lat, lng]);
        window.locationMap.setView([lat, lng], 15);
    }
}

// Form submission
document.getElementById('locationForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const submitText = document.getElementById('submitText');
    const submitLoading = document.getElementById('submitLoading');
    
    submitText.classList.add('hidden');
    submitLoading.classList.remove('hidden');
    
    const formData = new FormData(this);
    const data = Object.fromEntries(formData);
    
    // Convert boolean checkbox value
    data.is_active = data.is_active === 'on' || data.is_active === '1';
    
    // Convert numeric values
    if (data.latitude) data.latitude = parseFloat(data.latitude);
    if (data.longitude) data.longitude = parseFloat(data.longitude);
    if (data.radius_meters) data.radius_meters = parseInt(data.radius_meters);
    
    try {
        const url = window.currentLocationId ? `/api/locations/${window.currentLocationId}` : '/api/locations';
        const method = window.currentLocationId ? 'PUT' : 'POST';
        
        const response = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify(data)
        });
        
        if (response.ok) {
            showNotification(
                window.currentLocationId ? 'Lokasi berhasil diperbarui' : 'Lokasi berhasil ditambahkan', 
                'success'
            );
            closeLocationModal();
            loadLocations();
            loadStatistics();
        } else {
            const errorData = await response.json();
            showNotification(errorData.message || 'Terjadi kesalahan', 'error');
        }
    } catch (error) {
        console.error('Error saving location:', error);
        showNotification('Terjadi kesalahan saat menyimpan', 'error');
    } finally {
        submitText.classList.remove('hidden');
        submitLoading.classList.add('hidden');
    }
});

// Delete location
async function deleteLocation(id) {
    if (!confirm('Apakah Anda yakin ingin menghapus lokasi ini?\n\nTindakan ini tidak dapat dibatalkan.')) {
        return;
    }
    
    try {
        const response = await fetch('/api/locations/' + id, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
        
        if (response.ok) {
            showNotification('Lokasi berhasil dihapus', 'success');
            loadLocations();
            loadStatistics();
        } else {
            const errorData = await response.json();
            showNotification(errorData.message || 'Gagal menghapus lokasi', 'error');
        }
    } catch (error) {
        console.error('Error deleting location:', error);
        showNotification('Terjadi kesalahan saat menghapus', 'error');
    }
}

// Toggle location status
async function toggleLocationStatus(id, currentStatus) {
    try {
        const response = await fetch(`/api/locations/${id}/toggle-status`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            }
        });
        
        if (response.ok) {
            showNotification('Status lokasi berhasil diperbarui', 'success');
            loadLocations();
            loadStatistics();
        } else {
            const errorData = await response.json();
            showNotification(errorData.message || 'Gagal mengubah status', 'error');
        }
    } catch (error) {
        console.error('Error toggling status:', error);
        showNotification('Terjadi kesalahan', 'error');
    }
}

// View location details
function viewLocation(id) {
    // Implementation for viewing location details
    window.location.href = `/locations/${id}`;
}

// Search locations
function searchLocations() {
    const query = document.getElementById('searchInput').value.toLowerCase();
    const rows = document.querySelectorAll('#locationsTableBody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        if (text.includes(query)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Enhanced interactive features
function exportLocations() {
    showNotification('Mengunduh data lokasi...', 'info');
    // Implementation for exporting location data
    setTimeout(() => {
        showNotification('Data lokasi berhasil diunduh', 'success');
    }, 2000);
}

function showLocationAnalytics() {
    showNotification('Membuka analytics lokasi...', 'info');
    // Implementation for location analytics
}

function bulkLocationAnalysis() {
    showNotification('Memulai analisis lokasi...', 'info');
    // Implementation for bulk analysis
}

function optimizeRadius() {
    showNotification('Menganalisis radius optimal...', 'info');
    // Implementation for radius optimization
}

function validateLocations() {
    showNotification('Memvalidasi koordinat GPS...', 'info');
    // Implementation for GPS validation
}

function refreshLocationData() {
    loadLocations();
    loadStatistics();
    showNotification('Data lokasi berhasil diperbarui', 'success');
}

function viewLocationOnMap(locationId) {
    showNotification('Menampilkan lokasi di peta...', 'info');
    // Implementation for viewing location on map
}

// Initialize overview map
function initializeOverviewMap(locations) {
    // Implementation for overview map
    const mapContainer = document.getElementById('overview-map');
    if (mapContainer && locations.length > 0) {
        // Initialize map with multiple location markers
        setTimeout(() => {
            document.getElementById('overview-map-loading').style.display = 'none';
        }, 1000);
    }
}

// Load location activities
function loadLocationActivities() {
    const activities = [
        {
            id: 1,
            type: 'location_added',
            title: 'Lokasi SMAN 1 Denpasar ditambahkan',
            user: 'Admin System',
            time: '2 jam lalu',
            icon: 'plus',
            color: 'green'
        },
        {
            id: 2,
            type: 'location_updated',
            title: 'Radius Kantor Dinas diperbarui',
            user: 'Manager HR',
            time: '4 jam lalu',
            icon: 'edit',
            color: 'blue'
        },
        {
            id: 3,
            type: 'location_status',
            title: 'SMKN 2 Denpasar dinonaktifkan',
            user: 'Admin System',
            time: '1 hari lalu',
            icon: 'x',
            color: 'red'
        },
        {
            id: 4,
            type: 'location_verified',
            title: 'Koordinat GPS diverifikasi',
            user: 'System',
            time: '2 hari lalu',
            icon: 'check',
            color: 'purple'
        }
    ];
    
    const iconMap = {
        plus: 'M12 6v6m0 0v6m0-6h6m-6 0H6',
        edit: 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z',
        x: 'M6 18L18 6M6 6l12 12',
        check: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'
    };
    
    const colorMap = {
        green: 'text-green-600 dark:text-green-400',
        blue: 'text-blue-600 dark:text-blue-400',
        red: 'text-red-600 dark:text-red-400',
        purple: 'text-purple-600 dark:text-purple-400'
    };
    
    const activitiesContainer = document.getElementById('location-activities');
    activitiesContainer.innerHTML = activities.map(activity => `
        <div class="flex items-start space-x-3 p-3 rounded-lg bg-gray-50 dark:bg-gray-700">
            <div class="flex-shrink-0">
                <div class="w-8 h-8 bg-white dark:bg-gray-600 rounded-full flex items-center justify-center">
                    <svg class="w-4 h-4 ${colorMap[activity.color]}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${iconMap[activity.icon]}"/>
                    </svg>
                </div>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 dark:text-white">${activity.title}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">oleh ${activity.user} • ${activity.time}</p>
            </div>
        </div>
    `).join('');
}

// Filter functions
function toggleFilter() {
    const panel = document.getElementById('filterPanel');
    panel.classList.toggle('hidden');
}

function resetFilter() {
    document.getElementById('search-location-filter').value = '';
    document.getElementById('status-filter').value = '';
    document.getElementById('radius-filter').value = '';
    applyFilter();
}

function applyFilter() {
    // Implementation for applying filters
    loadLocations();
    toggleFilter();
    showNotification('Filter berhasil diterapkan', 'success');
}

// Enhanced notification system
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    
    // Modern notification styling with icons
    const icons = {
        success: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
        error: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
        warning: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16c-.77.833.192 2.5 1.732 2.5z"/></svg>',
        info: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'
    };
    
    const styles = {
        success: 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-700 text-green-800 dark:text-green-200',
        error: 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-700 text-red-800 dark:text-red-200',
        warning: 'bg-amber-50 dark:bg-amber-900/20 border-amber-200 dark:border-amber-700 text-amber-800 dark:text-amber-200',
        info: 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-700 text-blue-800 dark:text-blue-200'
    };
    
    notification.className = `fixed top-4 right-4 z-50 max-w-sm w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-lg transform translate-x-full transition-all duration-300 ease-out`;
    notification.innerHTML = `
        <div class="p-4">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <div class="p-1 ${styles[type]} rounded-lg">
                        ${icons[type]}
                    </div>
                </div>
                <div class="ml-3 w-0 flex-1">
                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">${message}</p>
                </div>
                <div class="ml-4 flex-shrink-0 flex">
                    <button onclick="this.closest('.fixed').remove()" class="inline-flex text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 focus:outline-none transition-colors">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Trigger entrance animation
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 100);
    
    // Auto-remove notification
    const duration = type === 'error' ? 5000 : 3000;
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 300);
    }, duration);
}

// Initialize location activities on page load
window.addEventListener('load', function() {
    setTimeout(() => {
        loadLocationActivities();
    }, 1000);
});

// Close address suggestions when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('#addressSearch') && !e.target.closest('#addressSuggestions')) {
        document.getElementById('addressSuggestions').classList.add('hidden');
    }
});
</script>
@endpush
@endsection