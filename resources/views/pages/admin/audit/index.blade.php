@extends('layouts.authenticated-unified')

@section('title', 'Log Audit')

@section('page-content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100 dark:from-slate-900 dark:via-blue-900 dark:to-indigo-900">
    <div class="p-6 lg:p-8">
        <x-layouts.base-page
            title="Log Audit"
            subtitle="Administrasi Sistem - Pantau semua aktivitas pengguna"
            :breadcrumbs="[
                ['label' => 'Dashboard', 'url' => route('dashboard')],
                ['label' => 'Admin'],
                ['label' => 'Log Audit']
            ]">
            <x-slot name="actions">
                <div class="flex flex-col sm:flex-row gap-2">
                    <x-ui.select onchange="exportAuditLogs(this.value)" class="bg-white/20 backdrop-blur-sm border border-white/30 text-slate-700 dark:text-slate-300 rounded-xl shadow-lg hover:shadow-xl hover:bg-white/30 transition-all duration-300 ease-out">
                        <option value="">Opsi Ekspor</option>
                        <option value="csv">Ekspor sebagai CSV</option>
                        <option value="pdf">Ekspor sebagai PDF</option>
                    </x-ui.select>
                    
                    <x-ui.button 
                        variant="secondary" 
                        onclick="cleanupAuditLogs()">
                        <svg class="w-5 h-5 mr-2 group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Bersihkan
                    </x-ui.button>
                    
                    <x-ui.button 
                        variant="primary" 
                        onclick="refreshAuditLogs()">
                        <svg class="w-5 h-5 mr-2 group-hover:rotate-90 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Segarkan
                    </x-ui.button>
                </div>
            </x-slot>
        </x-layouts.base-page>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out hover:bg-white/30 hover:scale-105">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Total Kejadian</p>
                        <p class="text-3xl font-bold text-slate-800 dark:text-white mt-1" id="totalEvents">{{ $stats['total_events'] }}</p>
                    </div>
                    <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg group-hover:rotate-6 transition-transform duration-300">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                </div>
            </div>
            
            <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out hover:bg-white/30 hover:scale-105">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Pengguna Unik</p>
                        <p class="text-3xl font-bold text-slate-800 dark:text-white mt-1" id="uniqueUsers">{{ $stats['unique_users'] }}</p>
                    </div>
                    <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center shadow-lg group-hover:rotate-6 transition-transform duration-300">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
                        </svg>
                    </div>
                </div>
            </div>
            
            <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out hover:bg-white/30 hover:scale-105">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Kejadian Risiko Tinggi</p>
                        <p class="text-3xl font-bold text-slate-800 dark:text-white mt-1" id="highRiskEvents">{{ $stats['high_risk_events'] }}</p>
                    </div>
                    <div class="w-12 h-12 bg-gradient-to-br from-red-500 to-rose-600 rounded-xl flex items-center justify-center shadow-lg group-hover:rotate-6 transition-transform duration-300">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                        </svg>
                    </div>
                </div>
            </div>
            
            <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out hover:bg-white/30 hover:scale-105">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Kejadian Hari Ini</p>
                        <p class="text-3xl font-bold text-slate-800 dark:text-white mt-1" id="todayEvents">{{ $stats['today_events'] }}</p>
                    </div>
                    <div class="w-12 h-12 bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl flex items-center justify-center shadow-lg group-hover:rotate-6 transition-transform duration-300">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
            <x-slot name="title">
                <div class="flex justify-between items-center">
                    <span class="text-slate-800 dark:text-white">Filter</span>
                    <x-ui.button variant="secondary" size="sm" onclick="clearFilters()">
                        Bersihkan Semua
                    </x-ui.button>
                </div>
            </x-slot>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
                <div class="lg:col-span-2">
                    <x-ui.label for="date-range" class="text-slate-700 dark:text-slate-300">Rentang Tanggal</x-ui.label>
                    <div class="grid grid-cols-2 gap-2">
                        <x-ui.input type="date" id="startDate" value="{{ now()->subDays(30)->format('Y-m-d') }}" class="bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300" />
                        <x-ui.input type="date" id="endDate" value="{{ now()->format('Y-m-d') }}" class="bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300" />
                    </div>
                </div>
                
                <div>
                    <x-ui.label for="eventTypeFilter" class="text-slate-700 dark:text-slate-300">Tipe Kejadian</x-ui.label>
                    <x-ui.select id="eventTypeFilter" class="bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300">
                        <option value="">Semua Kejadian</option>
                        @foreach($eventTypes as $eventType)
                            <option value="{{ $eventType['value'] }}">{{ $eventType['label'] }}</option>
                        @endforeach
                    </x-ui.select>
                </div>
                
                <div>
                    <x-ui.label for="auditableTypeFilter" class="text-slate-700 dark:text-slate-300">Tipe Model</x-ui.label>
                    <x-ui.select id="auditableTypeFilter" class="bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300">
                        <option value="">Semua Model</option>
                        @foreach($auditableTypes as $type)
                            <option value="{{ $type['value'] }}">{{ $type['label'] }}</option>
                        @endforeach
                    </x-ui.select>
                </div>
                
                <div>
                    <x-ui.label for="riskLevelFilter" class="text-slate-700 dark:text-slate-300">Tingkat Risiko</x-ui.label>
                    <x-ui.select id="riskLevelFilter" class="bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300">
                        <option value="">Semua Tingkat</option>
                        <option value="high">Risiko Tinggi</option>
                        <option value="medium">Risiko Sedang</option>
                        <option value="low">Risiko Rendah</option>
                    </x-ui.select>
                </div>
                
                <div>
                    <x-ui.label for="apply-filter" class="text-slate-700 dark:text-slate-300">Terapkan Filter</x-ui.label>
                    <x-ui.button class="w-full group relative px-6 py-3 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-300 ease-out" onclick="applyFilters()">
                        <svg class="w-5 h-5 mr-2 group-hover:rotate-90 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        Terapkan
                    </x-ui.button>
                </div>
            </div>
        </div>

        <!-- Audit Logs Table -->
        <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
            <x-slot name="title">
                <div class="flex justify-between items-center">
                    <span class="text-slate-800 dark:text-white">Jejak Audit</span>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium text-white bg-gradient-to-r from-green-500 to-emerald-600 shadow-lg" id="liveIndicator">LIVE</span>
                </div>
            </x-slot>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-white/20" id="auditLogsTable">
                    <thead class="bg-white/10 backdrop-blur-sm">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Pengguna</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Kejadian</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Perubahan</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Konteks</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Waktu</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider w-8">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white/5 backdrop-blur-sm divide-y divide-white/10">
                        <!-- Data loaded via DataTables -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Audit Log Details Modal -->
<x-ui.modal id="auditDetailsModal" title="Detail Log Audit" size="4xl">
    <div id="auditDetailsContent"></div>
    <x-slot name="footer">
        <x-ui.button variant="secondary" onclick="closeModal('auditDetailsModal')">Tutup</x-ui.button>
    </x-slot>
</x-ui.modal>

<!-- Export Modal -->
<x-ui.modal id="exportModal" title="Ekspor Log Audit">
    <form id="exportForm" class="space-y-4">
        @csrf
        <div>
            <x-ui.label for="exportFormat" class="text-slate-700 dark:text-slate-300">Format Ekspor</x-ui.label>
            <x-ui.select name="format" id="exportFormat" class="bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300">
                <option value="">Pilih format...</option>
                <option value="csv">File CSV</option>
                <option value="pdf">Laporan PDF</option>
            </x-ui.select>
        </div>
        <div>
            <x-ui.label for="exportStartDate" class="text-slate-700 dark:text-slate-300">Rentang Tanggal</x-ui.label>
            <div class="grid grid-cols-2 gap-2">
                <x-ui.input type="date" id="exportStartDate" name="start_date" required class="bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300" />
                <x-ui.input type="date" id="exportEndDate" name="end_date" required class="bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300" />
            </div>
        </div>
        <div class="flex justify-end space-x-3 pt-4">
            <x-ui.button type="button" variant="secondary" onclick="closeModal('exportModal')">Batal</x-ui.button>
            <x-ui.button type="submit" variant="primary">Ekspor</x-ui.button>
        </div>
    </form>
</x-ui.modal>

<!-- Cleanup Modal -->
<x-ui.modal id="cleanupModal" title="Bersihkan Log Audit Lama">
    <form id="cleanupForm" class="space-y-4">
        @csrf
        <div class="bg-amber-50/20 border border-amber-500/30 text-amber-800 dark:text-amber-200 px-4 py-3 rounded-lg mb-4">
            <h4 class="font-medium text-base">⚠️ Peringatan</h4>
            <p class="text-sm">Ini akan menghapus entri log audit lama secara permanen. Tindakan ini tidak dapat dibatalkan.</p>
        </div>
        
        <div>
            <x-ui.label for="olderThanDays" class="text-slate-700 dark:text-slate-300">Hapus log yang lebih lama dari</x-ui.label>
            <div class="flex">
                <x-ui.input type="number" id="olderThanDays" name="older_than_days" value="90" min="1" required class="rounded-r-none bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300" />
                <span class="inline-flex items-center px-3 py-2 rounded-r-xl border border-l-0 border-white/40 bg-white/30 text-slate-700 dark:text-slate-300 text-sm">hari</span>
            </div>
        </div>
        
        <div class="flex items-center">
            <x-ui.checkbox name="keep_critical" value="1" checked />
            <label for="keep_critical" class="ml-2 text-sm text-slate-700 dark:text-slate-300">
                Simpan kejadian keamanan kritis (kegagalan login, penghapusan, perubahan izin)
            </label>
        </div>
        <div class="flex justify-end space-x-3 pt-4">
            <x-ui.button type="button" variant="secondary" onclick="closeModal('cleanupModal')">Batal</x-ui.button>
            <x-ui.button type="submit" variant="destructive">
                <svg class="w-5 h-5 mr-2 group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                Hapus Log Lama
            </x-ui.button>
        </div>
    </form>
</x-ui.modal>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    let auditTable;
    
    // Initialize DataTable
    if (!$.fn.dataTable.isDataTable('#auditLogsTable')) {
        auditTable = $('#auditLogsTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ route("audit.data") }}',
                data: function(d) {
                    d.start_date = $('#startDate').val();
                    d.end_date = $('#endDate').val();
                    d.event_type = $('#eventTypeFilter').val();
                    d.auditable_type = $('#auditableTypeFilter').val();
                    d.risk_level = $('#riskLevelFilter').val();
                    d.user_id = $('#userFilter').val();
                }
            },
            columns: [
                { data: 'user_info', name: 'user_name', orderable: false },
                { data: 'event_info', name: 'event_type', orderable: false },
                { data: 'changes', name: 'changes', orderable: false },
                { data: 'context', name: 'context', orderable: false },
                { data: 'timestamp', name: 'created_at' },
                {
                    data: 'actions',
                    name: 'actions',
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row) {
                        return `
                            <button onclick="viewAuditDetails('${row.id}')" class="group inline-flex items-center justify-center w-10 h-10 rounded-xl bg-white/20 backdrop-blur-sm border border-white/30 text-slate-600 dark:text-slate-300 hover:bg-blue-500 hover:text-white transition-all duration-300 hover:scale-110 shadow-lg">
                                <svg class="w-4 h-4 group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </button>
                        `;
                    }
                }
            ],
            order: [[4, 'desc']],
            pageLength: 25,
            responsive: true,
            dom: '<"flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4"<"mb-2 sm:mb-0"l><"sm:ml-auto"f>>rtip',
            language: { url: '{{ asset("js/dataTables/i18n/Indonesian.json") }}' }
        });
    }
    
    // Auto-refresh every 30 seconds
    setInterval(function() {
        if (auditTable) {
            auditTable.ajax.reload(null, false);
            updateStatistics();
        }
    }, 30000);
    
    // Form submissions
    $('#exportForm').on('submit', function(e) {
        e.preventDefault();
        performExport();
    });
    
    $('#cleanupForm').on('submit', function(e) {
        e.preventDefault();
        performCleanup();
    });
});

function applyFilters() {
    if (auditTable) {
        auditTable.ajax.reload();
    }
}

function clearFilters() {
    $('#startDate').val('{{ now()->subDays(30)->format("Y-m-d") }}');
    $('#endDate').val('{{ now()->format("Y-m-d") }}');
    $('#eventTypeFilter').val('');
    $('#auditableTypeFilter').val('');
    $('#riskLevelFilter').val('');
    $('#userFilter').val('');
    applyFilters();
}

function refreshAuditLogs() {
    if (auditTable) {
        auditTable.ajax.reload(null, false);
    }
    updateStatistics();
    toastr.success('Log audit disegarkan');
}

function viewAuditDetails(auditId) {
    $.ajax({
        url: `/admin/audit/${auditId}`,
        method: 'GET',
        success: function(response) {
            if (response.success) {
                displayAuditDetails(response.data);
                openModal('auditDetailsModal');
            } else {
                toastr.error('Gagal memuat detail audit');
            }
        },
        error: function() {
            toastr.error('Gagal memuat detail audit');
        }
    });
}

function displayAuditDetails(audit) {
    let oldValues = '';
    let newValues = '';
    
    if (audit.old_values && Object.keys(audit.old_values).length > 0) {
        oldValues = '<pre class="bg-slate-700/50 p-3 rounded text-sm overflow-x-auto text-white">' + JSON.stringify(audit.old_values, null, 2) + '</pre>';
    } else {
        oldValues = '<em class="text-slate-400">Tidak ada nilai sebelumnya</em>';
    }
    
    if (audit.new_values && Object.keys(audit.new_values).length > 0) {
        newValues = '<pre class="bg-slate-700/50 p-3 rounded text-sm overflow-x-auto text-white">' + JSON.stringify(audit.new_values, null, 2) + '</pre>';
    } else {
        newValues = '<em class="text-slate-400">Tidak ada nilai baru</em>';
    }
    
    const riskBadge = `<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium text-white bg-gradient-to-r ${getRiskColorClass(audit.risk_level)} shadow-lg">${audit.risk_level.toUpperCase()}</span>`;
    const significantBadge = audit.has_significant_changes ? '<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium text-white bg-gradient-to-r from-amber-500 to-orange-600 shadow-lg ml-2">SENSITIF</span>' : '';
    
    const html = `
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h6 class="text-lg font-semibold text-slate-800 dark:text-white mb-3">Informasi Dasar</h6>
                <table class="min-w-full divide-y divide-white/20">
                    <tbody class="bg-white/5 backdrop-blur-sm divide-y divide-white/10">
                        <tr><td class="px-3 py-2 text-sm font-medium text-slate-600 dark:text-slate-400">Tipe Kejadian:</td><td class="px-3 py-2 text-sm text-slate-800 dark:text-white">${audit.event_type}</td></tr>
                        <tr><td class="px-3 py-2 text-sm font-medium text-slate-600 dark:text-slate-400">Model:</td><td class="px-3 py-2 text-sm text-slate-800 dark:text-white">${audit.model_name}</td></tr>
                        <tr><td class="px-3 py-2 text-sm font-medium text-slate-600 dark:text-slate-400">ID Model:</td><td class="px-3 py-2 text-sm text-slate-800 dark:text-white">${audit.auditable_id || 'N/A'}</td></tr>
                        <tr><td class="px-3 py-2 text-sm font-medium text-slate-600 dark:text-slate-400">Pengguna:</td><td class="px-3 py-2 text-sm text-slate-800 dark:text-white">${audit.user ? audit.user.name + ' (' + audit.user.email + ')' : 'Sistem'}</td></tr>
                        <tr><td class="px-3 py-2 text-sm font-medium text-slate-600 dark:text-slate-400">Tingkat Risiko:</td><td class="px-3 py-2 text-sm text-slate-800 dark:text-white">${riskBadge}${significantBadge}</td></tr>
                        <tr><td class="px-3 py-2 text-sm font-medium text-slate-600 dark:text-slate-400">Waktu:</td><td class="px-3 py-2 text-sm text-slate-800 dark:text-white">${audit.created_at}<br><small class="text-slate-500 dark:text-slate-400">${audit.created_at_human}</small></td></tr>
                    </tbody>
                </table>
            </div>
            <div>
                <h6 class="text-lg font-semibold text-slate-800 dark:text-white mb-3">Informasi Konteks</h6>
                <table class="min-w-full divide-y divide-white/20">
                    <tbody class="bg-white/5 backdrop-blur-sm divide-y divide-white/10">
                        <tr><td class="px-3 py-2 text-sm font-medium text-slate-600 dark:text-slate-400">Alamat IP:</td><td class="px-3 py-2 text-sm text-slate-800 dark:text-white">${audit.ip_address || 'N/A'}</td></tr>
                        <tr><td class="px-3 py-2 text-sm font-medium text-slate-600 dark:text-slate-400">URL:</td><td class="px-3 py-2 text-sm text-slate-800 dark:text-white">${audit.url ? '<small>' + audit.url + '</small>' : 'N/A'}</td></tr>
                        <tr><td class="px-3 py-2 text-sm font-medium text-slate-600 dark:text-slate-400">User Agent:</td><td class="px-3 py-2 text-sm text-slate-800 dark:text-white">${audit.user_agent ? '<small>' + audit.user_agent + '</small>' : 'N/A'}</td></tr>
                        <tr><td class="px-3 py-2 text-sm font-medium text-slate-600 dark:text-slate-400">Tag:</td><td class="px-3 py-2 text-sm text-slate-800 dark:text-white">${audit.tags ? audit.tags.map(tag => '<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium text-white bg-gradient-to-r from-gray-500 to-slate-600 shadow-lg">' + tag + '</span>').join(' ') : 'Tidak ada'}</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="mt-6 pt-6 border-t border-white/20">
            <h6 class="text-lg font-semibold text-slate-800 dark:text-white mb-3">Ringkasan Perubahan</h6>
            <div class="bg-blue-500/20 border border-blue-500/30 text-blue-800 dark:text-blue-200 px-4 py-3 rounded-lg">
                ${audit.changes_summary}
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
            <div>
                <h6 class="text-lg font-semibold text-slate-800 dark:text-white mb-3">Nilai Sebelumnya</h6>
                <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">${oldValues}</div>
            </div>
            <div>
                <h6 class="text-lg font-semibold text-slate-800 dark:text-white mb-3">Nilai Baru</h6>
                <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">${newValues}</div>
            </div>
        </div>
    `;
    
    $('#auditDetailsContent').html(html);
}

function getRiskColorClass(riskLevel) {
    switch(riskLevel) {
        case 'high': return 'from-red-500 to-rose-600';
        case 'medium': return 'from-amber-500 to-orange-600';
        case 'low': return 'from-green-500 to-emerald-600';
        default: return 'from-gray-500 to-slate-600';
    }
}

function exportAuditLogs(format) {
    if (format) {
        $('#exportFormat').val(format);
        $('#exportStartDate').val($('#startDate').val());
        $('#exportEndDate').val($('#endDate').val());
        openModal('exportModal');
    }
}

function performExport() {
    const formData = new FormData($('#exportForm')[0]);
    
    // Create download link
    const url = '{{ route("audit.export") }}?' + new URLSearchParams(formData).toString();
    window.open(url, '_blank');
    
    closeModal('exportModal');
    toastr.success('Ekspor dimulai. Unduhan akan segera dimulai.');
}

function cleanupAuditLogs() {
    openModal('cleanupModal');
}

function performCleanup() {
    const formData = new FormData($('#cleanupForm')[0]);
    
    $.ajax({
        url: '{{ route("audit.cleanup") }}',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                toastr.success(response.message);
                closeModal('cleanupModal');
                refreshAuditLogs();
            } else {
                toastr.error(response.message);
            }
        },
        error: function(xhr) {
            toastr.error(xhr.responseJSON?.message || 'Pembersihan gagal');
        }
    });
}

function updateStatistics() {
    $.ajax({
        url: '{{ route("audit.stats") }}',
        method: 'GET',
        data: {
            start_date: $('#startDate').val(),
            end_date: $('#endDate').val()
        },
        success: function(response) {
            if (response.success) {
                const stats = response.data;
                $('#totalEvents').text(stats.total_events);
                $('#uniqueUsers').text(stats.unique_users);
                $('#highRiskEvents').text(stats.high_risk_events);
                $('#todayEvents').text(stats.today_events);
            }
        }
    });
}
</script>
@endpush
