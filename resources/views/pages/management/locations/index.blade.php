@extends('layouts.authenticated-unified')

@section('title', 'Manajemen Lokasi')

@section('page-content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100 dark:from-slate-900 dark:via-blue-900 dark:to-indigo-900">
    <div class="p-6 lg:p-8">
        <x-layouts.base-page
            title="Manajemen Lokasi"
            subtitle="Kelola lokasi verifikasi absensi dan pengaturan GPS"
            :breadcrumbs="[
                ['label' => 'Dashboard', 'url' => route('dashboard')],
                ['label' => 'Manajemen'],
                ['label' => 'Lokasi']
            ]">
            <x-slot name="actions">
                <x-ui.button variant="secondary">
                    <svg class="w-5 h-5 mr-2 group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                    Filter
                </x-ui.button>
                @can('manage_locations')
                    <x-ui.button href="{{ route('locations.create') }}">
                        <svg class="w-5 h-5 mr-2 group-hover:rotate-90 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                        Tambah Lokasi
                    </x-ui.button>
                @endcan
            </x-slot>
        </x-layouts.base-page>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out hover:bg-white/30 hover:scale-105">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Total Lokasi</p>
                        <p class="text-3xl font-bold text-slate-800 dark:text-white mt-1" id="stat-total">5</p>
                    </div>
                    <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg group-hover:rotate-6 transition-transform duration-300">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                </div>
            </div>

            <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out hover:bg-white/30 hover:scale-105">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Lokasi Aktif</p>
                        <p class="text-3xl font-bold text-slate-800 dark:text-white mt-1" id="stat-active">4</p>
                    </div>
                    <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center shadow-lg group-hover:rotate-6 transition-transform duration-300">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
            </div>

            <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out hover:bg-white/30 hover:scale-105">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Karyawan Ditugaskan</p>
                        <p class="text-3xl font-bold text-slate-800 dark:text-white mt-1" id="stat-employees">18</p>
                    </div>
                    <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-pink-500 rounded-xl flex items-center justify-center shadow-lg group-hover:rotate-6 transition-transform duration-300">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    </div>
                </div>
            </div>

            <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out hover:bg-white/30 hover:scale-105">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Radius Rata-rata</p>
                        <p class="text-3xl font-bold text-slate-800 dark:text-white mt-1" id="stat-radius">50m</p>
                    </div>
                    <div class="w-12 h-12 bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl flex items-center justify-center shadow-lg group-hover:rotate-6 transition-transform duration-300">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Locations Table -->
        <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
            <h3 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Semua Lokasi</h3>
            <p class="text-slate-600 dark:text-slate-400 mb-6">Kelola lokasi verifikasi absensi</p>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-white/20" id="locationsTable">
                    <thead class="bg-white/10 backdrop-blur-sm">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Lokasi</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Alamat</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Koordinat</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Radius</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Verifikasi</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Karyawan</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white/5 backdrop-blur-sm divide-y divide-white/10">
                        <!-- Populated by JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    initializeLocationsTable();
    
    document.addEventListener('click', function(e) {
        if (e.target.matches('.delete-location') || e.target.closest('.delete-location')) {
            const button = e.target.matches('.delete-location') ? e.target : e.target.closest('.delete-location');
            const locationId = button.dataset.id;
            deleteLocation(locationId);
        }
        
        if (e.target.matches('.toggle-status') || e.target.closest('.toggle-status')) {
            const button = e.target.matches('.toggle-status') ? e.target : e.target.closest('.toggle-status');
            const locationId = button.dataset.id;
            toggleLocationStatus(locationId);
        }
    });
});

function initializeLocationsTable() {
    const tableBody = document.querySelector('#locationsTable tbody');
    const sampleLocations = [
        {
            id: 1,
            name: 'Kampus Utama',
            address: 'Jl. Pendidikan No. 123, Pusat Kota',
            coordinates: '-6.2088, 106.8456',
            radius: '100m',
            verification: ['GPS', 'Pengenalan Wajah'],
            employees: 15,
            status: 'Aktif'
        },
        {
            id: 2,
            name: 'Kantor Cabang',
            address: 'Jl. Pembelajaran No. 456, Distrik 2',
            coordinates: '-6.1751, 106.8650',
            radius: '50m',
            verification: ['GPS'],
            employees: 8,
            status: 'Aktif'
        },
        {
            id: 3,
            name: 'Situs Jarak Jauh',
            address: 'Jl. Pendidikan Raya, Pinggiran Kota',
            coordinates: '-6.2297, 106.6890',
            radius: '75m',
            verification: ['GPS', 'Pengenalan Wajah'],
            employees: 5,
            status: 'Tidak Aktif'
        }
    ];
    
    tableBody.innerHTML = sampleLocations.map(location => `
        <tr class="border-b border-white/10 hover:bg-white/5 transition-colors">
            <td class="py-3 px-4">
                <div class="flex items-center">
                    <div class="h-10 w-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center mr-3 shadow-lg">
                        <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <div>
                        <span class="font-medium text-slate-800 dark:text-white">${location.name}</span>
                        <div class="text-sm text-slate-500 dark:text-slate-400">ID: LOC${location.id.toString().padStart(3, '0')}</div>
                    </div>
                </div>
            </td>
            <td class="py-3 px-4 text-slate-700 dark:text-slate-300">${location.address}</td>
            <td class="py-3 px-4">
                <span class="font-mono text-sm text-slate-600 dark:text-slate-400">${location.coordinates}</span>
            </td>
            <td class="py-3 px-4">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium text-white bg-gradient-to-r from-blue-500 to-purple-600 shadow-lg">
                    ${location.radius}
                </span>
            </td>
            <td class="py-3 px-4">
                <div class="flex flex-wrap gap-1">
                    ${location.verification.map(method => `
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium text-white ${method === 'GPS' ? 'bg-gradient-to-r from-green-500 to-emerald-600' : 'bg-gradient-to-r from-purple-500 to-pink-500'} shadow-lg">
                            ${method}
                        </span>
                    `).join('')}
                </div>
            </td>
            <td class="py-3 px-4">
                <span class="font-medium text-slate-800 dark:text-white">${location.employees}</span>
                <span class="text-sm text-slate-600 dark:text-slate-400">ditugaskan</span>
            </td>
            <td class="py-3 px-4">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium text-white ${location.status === 'Aktif' ? 'bg-gradient-to-r from-green-500 to-emerald-600' : 'bg-gradient-to-r from-red-500 to-rose-600'} shadow-lg">
                    ${location.status}
                </span>
            </td>
            <td class="py-3 px-4">
                <div class="flex items-center space-x-2">
                    <a href="/locations/${location.id}" class="group inline-flex items-center justify-center w-10 h-10 rounded-xl bg-white/20 backdrop-blur-sm border border-white/30 text-slate-600 dark:text-slate-300 hover:bg-blue-500 hover:text-white transition-all duration-300 hover:scale-110 shadow-lg" title="Lihat Detail">
                        <svg class="h-5 w-5 group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </a>
                    <a href="/locations/${location.id}/edit" class="group inline-flex items-center justify-center w-10 h-10 rounded-xl bg-white/20 backdrop-blur-sm border border-white/30 text-slate-600 dark:text-slate-300 hover:bg-amber-500 hover:text-white transition-all duration-300 hover:scale-110 shadow-lg" title="Edit Lokasi">
                        <svg class="h-5 w-5 group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </a>
                    <button class="toggle-status group inline-flex items-center justify-center w-10 h-10 rounded-xl bg-white/20 backdrop-blur-sm border border-white/30 text-slate-600 dark:text-slate-300 hover:bg-amber-500 hover:text-white transition-all duration-300 hover:scale-110 shadow-lg" data-id="${location.id}" title="Alihkan Status">
                        <svg class="h-5 w-5 group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"/></svg>
                    </button>
                    <button class="delete-location group inline-flex items-center justify-center w-10 h-10 rounded-xl bg-white/20 backdrop-blur-sm border border-white/30 text-slate-600 dark:text-slate-300 hover:bg-red-500 hover:text-white transition-all duration-300 hover:scale-110 shadow-lg" data-id="${location.id}" title="Hapus Lokasi">
                        <svg class="h-5 w-5 group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

function deleteLocation(locationId) {
    if (confirm('Apakah Anda yakin ingin menghapus lokasi ini? Tindakan ini tidak dapat dibatalkan.')) {
        console.log('Menghapus lokasi:', locationId);
        showNotification('Lokasi berhasil dihapus', 'success');
        
        const rowToRemove = document.querySelector(`[data-backup-id="${locationId}"]`);
        if (rowToRemove) rowToRemove.remove();
    }
}

function toggleLocationStatus(locationId) {
    if (confirm('Apakah Anda yakin ingin mengubah status lokasi ini?')) {
        console.log('Mengubah status untuk lokasi:', locationId);
        showNotification('Status lokasi berhasil diperbarui', 'success');
    }
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 z-50 px-4 py-3 rounded-lg shadow-lg text-white transition-all duration-300 ${
        type === 'success' ? 'bg-green-500' :
        type === 'error' ? 'bg-red-500' :
        type === 'warning' ? 'bg-amber-500' : 'bg-blue-500'
    }`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}
</script>
@endpush
