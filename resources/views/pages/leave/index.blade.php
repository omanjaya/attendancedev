@extends('layouts.authenticated')

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
<!-- Page Header - macOS Style -->
<div class="page-header">
    <div class="page-header-flex">
        <div>
            <h1 class="page-title">Manajemen Cuti</h1>
            <p class="page-desc">Lihat dan kelola pengajuan cuti Anda</p>
        </div>
        <div class="page-actions">
            @can('create_leave_requests')
            <x-ui.button variant="primary" @click="activeTab = 'request'">
                <x-icons.plus class="w-4 h-4" />
                <span class="hidden sm:inline">Ajukan Cuti</span>
            </x-ui.button>
            @endcan
        </div>
    </div>
</div>

<!-- Statistics Cards Section - macOS Style (Responsive) -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4 mb-6">
    <!-- Saldo Cuti Tahunan -->
    <x-ui.card class="p-3 sm:p-4">
        <div class="flex items-center justify-between mb-2 sm:mb-3">
            <x-icons.calendar class="w-4 h-4 sm:w-5 sm:h-5 text-success" />
            <span class="badge badge-success text-[10px] sm:text-xs">Tersisa</span>
        </div>
        <p class="metric-label mb-0.5 sm:mb-1">Cuti Tahunan</p>
        <p class="metric-value">{{ $leaveBalance['annual'] ?? 12 }}</p>
    </x-ui.card>

    <!-- Cuti Sakit -->
    <x-ui.card class="p-3 sm:p-4">
        <div class="flex items-center justify-between mb-2 sm:mb-3">
            <x-icons.heart class="w-4 h-4 sm:w-5 sm:h-5 text-destructive" />
            <span class="badge badge-destructive text-[10px] sm:text-xs">Kesehatan</span>
        </div>
        <p class="metric-label mb-0.5 sm:mb-1">Cuti Sakit</p>
        <p class="metric-value">{{ $leaveBalance['sick'] ?? 12 }}</p>
    </x-ui.card>

    <!-- Cuti Pending -->
    <x-ui.card class="p-3 sm:p-4">
        <div class="flex items-center justify-between mb-2 sm:mb-3">
            <x-icons.clock class="w-4 h-4 sm:w-5 sm:h-5 text-warning" />
            <span class="badge badge-warning text-[10px] sm:text-xs">Menunggu</span>
        </div>
        <p class="metric-label mb-0.5 sm:mb-1">Pengajuan Pending</p>
        <p class="metric-value">{{ $pendingRequests ?? 2 }}</p>
    </x-ui.card>

    <!-- Total Cuti Digunakan -->
    <x-ui.card class="p-3 sm:p-4">
        <div class="flex items-center justify-between mb-2 sm:mb-3">
            <x-icons.check-circle class="w-4 h-4 sm:w-5 sm:h-5 text-primary" />
            <span class="badge badge-info text-[10px] sm:text-xs">Digunakan</span>
        </div>
        <p class="metric-label mb-0.5 sm:mb-1">Total Digunakan</p>
        <p class="metric-value">{{ $usedLeave ?? 8 }}</p>
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
