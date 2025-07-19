@extends('layouts.authenticated-unified')

@section('title', 'Manajemen Perangkat')

@section('page-content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100 dark:from-slate-900 dark:via-blue-900 dark:to-indigo-900">
    <div class="p-6 lg:p-8">
        <x-layouts.base-page
            title="Manajemen Perangkat"
            subtitle="Kelola perangkat terpercaya dan pengaturan keamanan Anda"
            :breadcrumbs="[
                ['label' => 'Dashboard', 'url' => route('dashboard')],
                ['label' => 'Keamanan', 'url' => route('security.dashboard')],
                ['label' => 'Perangkat']
            ]">
            <x-slot name="actions">
                <x-ui.button variant="secondary" href="{{ route('security.dashboard') }}">
                    <svg class="w-5 h-5 mr-2 group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Kembali ke Keamanan
                </x-ui.button>
            </x-slot>
        </x-layouts.base-page>

        <!-- Device List -->
        <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
            <h3 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Perangkat Anda</h3>
            <p class="text-slate-600 dark:text-slate-400 mb-6">Perangkat yang telah mengakses akun Anda</p>
            
            @if($devices->count() > 0)
                <div class="divide-y divide-white/20">
                    @foreach($devices as $device)
                        <div class="px-6 py-4 hover:bg-white/5 transition-colors" data-device-id="{{ $device->id }}">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg">
                                        @if($device->device_type === 'mobile')
                                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                        @elseif($device->device_type === 'tablet')
                                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/></svg>
                                        @else
                                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                        @endif
                                    </div>
                                    
                                    <div class="ml-4">
                                        <div class="flex items-center">
                                            <h4 class="text-lg font-semibold text-slate-800 dark:text-white">
                                                {{ $device->device_name ?: 'Perangkat Tidak Dikenal' }}
                                            </h4>
                                            @if($device->is_trusted)
                                                <span class="ml-2 inline-flex items-center px-3 py-1 rounded-full text-xs font-medium text-white bg-gradient-to-r from-green-500 to-emerald-600 shadow-lg">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                                    Terpercaya
                                                </span>
                                            @endif
                                        </div>
                                        <div class="text-sm text-slate-600 dark:text-slate-400">
                                            {{ $device->browser_name }} {{ $device->browser_version }} • 
                                            {{ $device->os_name }} {{ $device->os_version }}
                                        </div>
                                        <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                            Terakhir terlihat: {{ $device->last_seen_at->diffForHumans() }} • 
                                            {{ $device->last_ip_address }}
                                            @if($device->last_location)
                                                • {{ $device->last_location }}
                                            @endif
                                        </div>
                                        <div class="text-xs text-slate-500 dark:text-slate-400">
                                            Jumlah login: {{ $device->login_count }}
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="flex items-center space-x-2">
                                    @if(!$device->is_trusted)
                                        <x-ui.button onclick="trustDevice('{{ $device->id }}')" variant="success" size="sm">
                                            Percayai Perangkat
                                        </x-ui.button>
                                    @endif
                                    <x-ui.button onclick="removeDevice('{{ $device->id }}')" variant="destructive" size="sm">
                                        Hapus
                                    </x-ui.button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="px-6 py-8 text-center text-slate-600 dark:text-slate-400">
                    <svg class="w-12 h-12 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-2">Tidak ada perangkat ditemukan</h3>
                    <p>Belum ada perangkat yang mengakses akun Anda.</p>
                </div>
            @endif
        </div>

        <!-- Device Security Tips -->
        <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out mt-8">
            <div class="flex items-start">
                <svg class="w-6 h-6 text-blue-500 mt-0.5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="ml-4">
                    <h4 class="text-xl font-semibold text-slate-800 dark:text-white">Tips Keamanan Perangkat</h4>
                    <ul class="text-sm text-slate-600 dark:text-slate-400 mt-2 space-y-1">
                        <li>• Hanya percayai perangkat yang Anda kenali dan miliki</li>
                        <li>• Hapus perangkat yang tidak lagi Anda gunakan atau tidak Anda kenali</li>
                        <li>• Jika Anda melihat aktivitas mencurigakan, segera ubah kata sandi Anda</li>
                        <li>• Aktifkan otentikasi dua faktor untuk keamanan tambahan</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function trustDevice(deviceId) {
    if (!confirm('Apakah Anda yakin ingin mempercayai perangkat ini?')) {
        return;
    }
    
    fetch(`/security/devices/${deviceId}/trust`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.message) {
            location.reload();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Gagal mempercayai perangkat. Mohon coba lagi.');
    });
}

function removeDevice(deviceId) {
    if (!confirm('Apakah Anda yakin ingin menghapus perangkat ini? Anda perlu memverifikasinya lagi pada login berikutnya.')) {
        return;
    }
    
    fetch(`/security/devices/${deviceId}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.message) {
            document.querySelector(`[data-device-id="${deviceId}"]`).remove();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Gagal menghapus perangkat. Mohon coba lagi.');
    });
}
</script>
@endpush
