@extends('layouts.authenticated-unified')

@section('title', 'Manajemen Cuti')

@section('page-content')
<div x-data="{ 
    activeTab: 'history',
    init() {
        const hash = window.location.hash.substring(1);
        if (['history', 'request', 'balance', 'calendar'].includes(hash)) {
            this.activeTab = hash;
        }
        this.$watch('activeTab', (value) => {
            window.history.pushState(null, null, '#' + value);
        });
    }
}">
<!-- Page Header -->
<div class="mb-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Manajemen Cuti</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Lihat dan kelola pengajuan cuti Anda</p>
        </div>
        <div class="flex items-center space-x-3">
            @can('create_leave_requests')
            <button @click="activeTab = 'request'" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Ajukan Cuti
            </button>
            @endcan
        </div>
    </div>
</div>

<!-- Statistics Cards Section -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Saldo Cuti Tahunan -->
    <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between mb-4">
            <div class="p-3 bg-green-600 rounded-lg shadow-md">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a2 2 0 012-2h4a2 2 0 012 2v4m-6 0V6a2 2 0 012-2h4a2 2 0 012 2v1M8 7h8m-8 0a2 2 0 00-2 2v9a2 2 0 002 2h8a2 2 0 002-2V9a2 2 0 00-2-2m-8 0V7"/>
                </svg>
            </div>
            <span class="text-sm text-green-600">Tersisa</span>
        </div>
        <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-1">{{ $leaveBalance['annual'] ?? 12 }}</h3>
        <p class="text-gray-600 dark:text-gray-400 text-sm">Cuti Tahunan</p>
    </x-ui.card>

    <!-- Cuti Sakit -->
    <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between mb-4">
            <div class="p-3 bg-red-600 rounded-lg shadow-md">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                </svg>
            </div>
            <span class="text-sm text-red-600">Kesehatan</span>
        </div>
        <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-1">{{ $leaveBalance['sick'] ?? 12 }}</h3>
        <p class="text-gray-600 dark:text-gray-400 text-sm">Cuti Sakit</p>
    </x-ui.card>

    <!-- Cuti Pending -->
    <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between mb-4">
            <div class="p-3 bg-amber-600 rounded-lg shadow-md">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <span class="text-sm text-amber-600">Menunggu</span>
        </div>
        <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-1">{{ $pendingRequests ?? 2 }}</h3>
        <p class="text-gray-600 dark:text-gray-400 text-sm">Pengajuan Pending</p>
    </x-ui.card>

    <!-- Total Cuti Digunakan -->
    <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between mb-4">
            <div class="p-3 bg-blue-600 rounded-lg shadow-md">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                </svg>
            </div>
            <span class="text-sm text-blue-600">Digunakan</span>
        </div>
        <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-1">{{ $usedLeave ?? 8 }}</h3>
        <p class="text-gray-600 dark:text-gray-400 text-sm">Total Digunakan</p>
    </x-ui.card>
</div>

        <!-- Tab Navigation -->
        <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-8">
            <div class="p-2">
                <div class="flex space-x-2">
                    @can('view_own_leave')
                        <button @click="activeTab = 'history'" :class="activeTab === 'history' ? 'bg-blue-100 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400' : 'hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-400'" class="flex-1 px-4 py-3 font-medium rounded-lg transition-colors">
                            Riwayat Cuti
                        </button>
                    @endcan
                    @can('create_leave_requests')
                        <button @click="activeTab = 'request'" :class="activeTab === 'request' ? 'bg-blue-100 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400' : 'hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-400'" class="flex-1 px-4 py-3 font-medium rounded-lg transition-colors">
                            Ajukan Cuti
                        </button>
                    @endcan
                    @can('view_own_leave')
                        <button @click="activeTab = 'balance'" :class="activeTab === 'balance' ? 'bg-blue-100 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400' : 'hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-400'" class="flex-1 px-4 py-3 font-medium rounded-lg transition-colors">
                            Saldo Cuti
                        </button>
                    @endcan
                    @can('view_leave_all')
                        <button @click="activeTab = 'calendar'" :class="activeTab === 'calendar' ? 'bg-blue-100 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400' : 'hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-400'" class="flex-1 px-4 py-3 font-medium rounded-lg transition-colors">
                            Kalender
                        </button>
                    @endcan
                </div>
            </div>
        </x-ui.card>

        <!-- Tab Content -->
        <div>
            <div x-show="activeTab === 'history'" x-transition>
                <x-ui.card class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Riwayat Pengajuan Cuti</h3>
                                <p class="text-gray-600 dark:text-gray-400 text-sm mt-1">Riwayat dan status pengajuan cuti Anda</p>
                            </div>
                            <div class="flex items-center space-x-3">
                                <select class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Semua Status</option>
                                    <option value="pending">Pending</option>
                                    <option value="approved">Disetujui</option>
                                    <option value="rejected">Ditolak</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table id="leaveRequestsTable" class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Tipe Cuti</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Tanggal</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Durasi</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Disetujui Oleh</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Dikirim</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700"></tbody>
                        </table>
                    </div>
                </x-ui.card>
            </div>
            <div x-show="activeTab === 'request'" x-transition>
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    @include('pages.leave.partials.request-form')
                </div>
            </div>
            <div x-show="activeTab === 'balance'" x-transition>
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    @include('pages.leave.partials.balance')
                </div>
            </div>
            <div x-show="activeTab === 'calendar'" x-transition>
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    @include('pages.leave.partials.calendar')
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Same script as before
</script>
@endpush
