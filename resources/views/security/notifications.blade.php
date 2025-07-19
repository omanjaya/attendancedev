@extends('layouts.authenticated-unified')

@section('title', 'Notifikasi Keamanan')

@section('page-content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100 dark:from-slate-900 dark:via-blue-900 dark:to-indigo-900">
    <div class="p-6 lg:p-8">
        <x-layouts.base-page
            title="Notifikasi Keamanan"
            subtitle="Tinjau peringatan keamanan dan notifikasi penting"
            :breadcrumbs="[
                ['label' => 'Dashboard', 'url' => route('dashboard')],
                ['label' => 'Keamanan', 'url' => route('security.dashboard')],
                ['label' => 'Notifikasi']
            ]">
            <x-slot name="actions">
                <x-ui.button variant="primary" onclick="markAllAsRead()">
                    <svg class="w-5 h-5 mr-2 group-hover:rotate-90 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12l5 5l10 -10"/></svg>
                    Tandai Semua Dibaca
                </x-ui.button>
                <x-ui.button variant="secondary" href="{{ route('security.dashboard') }}">
                    <svg class="w-5 h-5 mr-2 group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Kembali ke Keamanan
                </x-ui.button>
            </x-slot>
        </x-layouts.base-page>

        <!-- Notifications List -->
        <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
            <h3 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Notifikasi Anda</h3>
            <p class="text-slate-600 dark:text-slate-400 mb-6">Peringatan keamanan dan notifikasi penting</p>
            
            @if($notifications->count() > 0)
                <div class="divide-y divide-white/20">
                    @foreach($notifications as $notification)
                        <div class="px-6 py-4 {{ $notification->read_at ? 'bg-white/5' : 'bg-blue-500/10' }} hover:bg-white/10 transition-colors" 
                             data-notification-id="{{ $notification->id }}">
                            <div class="flex items-start justify-between">
                                <div class="flex items-start">
                                    <div class="flex-shrink-0 w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg">
                                        @if($notification->type === 'App\Notifications\NewDeviceLogin')
                                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                        @elseif($notification->type === 'App\Notifications\SecurityAlert')
                                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 15.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                                        @elseif($notification->type === 'App\Notifications\SuspiciousActivity')
                                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                        @else
                                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM11 19l-7-7 7-7m0 14l7-7-7-7"/></svg>
                                        @endif
                                    </div>
                                    
                                    <div class="ml-4">
                                        <div class="flex items-center">
                                            <h4 class="text-lg font-semibold text-slate-800 dark:text-white">
                                                @if($notification->type === 'App\Notifications\NewDeviceLogin')
                                                    Login Perangkat Baru
                                                @elseif($notification->type === 'App\Notifications\SecurityAlert')
                                                    Peringatan Keamanan
                                                @elseif($notification->type === 'App\Notifications\SuspiciousActivity')
                                                    Aktivitas Mencurigakan
                                                @else
                                                    Notifikasi Keamanan
                                                @endif
                                            </h4>
                                            @if(!$notification->read_at)
                                                <span class="ml-2 inline-flex items-center px-3 py-1 rounded-full text-xs font-medium text-white bg-gradient-to-r from-blue-500 to-cyan-500 shadow-lg">
                                                    Baru
                                                </span>
                                            @endif
                                        </div>
                                        <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">
                                            {{ $notification->data['message'] ?? 'Notifikasi keamanan' }}
                                        </p>
                                        @if(isset($notification->data['details']))
                                            <div class="text-xs text-slate-500 dark:text-slate-400 mt-2">
                                                {{ $notification->data['details'] }}
                                            </div>
                                        @endif
                                        <div class="text-xs text-slate-500 dark:text-slate-400 mt-2">
                                            {{ $notification->created_at->diffForHumans() }}
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="flex items-center space-x-2">
                                    @if(!$notification->read_at)
                                        <x-ui.button onclick="markAsRead('{{ $notification->id }}')" variant="secondary" size="sm">
                                            Tandai Dibaca
                                        </x-ui.button>
                                    @endif
                                    <x-ui.button onclick="deleteNotification('{{ $notification->id }}')" variant="destructive" size="sm">
                                        Hapus
                                    </x-ui.button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                
                <div class="px-6 py-4 border-t border-white/20">
                    {{ $notifications->links() }}
                </div>
            @else
                <div class="px-6 py-8 text-center text-slate-600 dark:text-slate-400">
                    <svg class="w-12 h-12 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM11 19l-7-7 7-7m0 14l7-7-7-7"/></svg>
                    <h3 class="text-xl font-semibold text-slate-800 dark:text-white mb-2">Tidak ada notifikasi</h3>
                    <p>Anda tidak memiliki notifikasi keamanan saat ini.</p>
                </div>
            @endif
        </div>

        <!-- Notification Settings -->
        <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out mt-8">
            <h3 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Pengaturan Notifikasi</h3>
            <div class="space-y-4">
                <div class="flex items-center justify-between py-3 border-b border-white/20">
                    <div>
                        <h4 class="text-lg font-semibold text-slate-800 dark:text-white">Login Perangkat Baru</h4>
                        <p class="text-sm text-slate-600 dark:text-slate-400">Dapatkan notifikasi ketika perangkat baru mengakses akun Anda</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" class="sr-only peer" checked>
                        <div class="w-11 h-6 bg-slate-500 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </div>
                <div class="flex items-center justify-between py-3 border-b border-white/20">
                    <div>
                        <h4 class="text-lg font-semibold text-slate-800 dark:text-white">Peringatan Keamanan</h4>
                        <p class="text-sm text-slate-600 dark:text-slate-400">Dapatkan notifikasi tentang ancaman keamanan dan aktivitas mencurigakan</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" class="sr-only peer" checked>
                        <div class="w-11 h-6 bg-slate-500 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </div>
                <div class="flex items-center justify-between py-3">
                    <div>
                        <h4 class="text-lg font-semibold text-slate-800 dark:text-white">Perubahan Akun</h4>
                        <p class="text-sm text-slate-600 dark:text-slate-400">Dapatkan notifikasi ketika pengaturan akun Anda dimodifikasi</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" class="sr-only peer" checked>
                        <div class="w-11 h-6 bg-slate-500 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function markAsRead(notificationId) {
    fetch('/security/notifications/mark-read', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.message) {
            const notificationEl = document.querySelector(`[data-notification-id="${notificationId}"]`);
            notificationEl.classList.remove('bg-blue-500/10');
            notificationEl.classList.add('bg-white/5');
            const newBadge = notificationEl.querySelector('.bg-gradient-to-r');
            if (newBadge) newBadge.remove();
            const markReadButton = notificationEl.querySelector('button[onclick*="markAsRead"]');
            if (markReadButton) markReadButton.remove();
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

function markAllAsRead() {
    fetch('/security/notifications/mark-read', {
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
    });
}

function deleteNotification(notificationId) {
    if (!confirm('Apakah Anda yakin ingin menghapus notifikasi ini?')) {
        return;
    }
    
    // This would need additional backend route to handle deletion
    console.log('Hapus notifikasi:', notificationId);
    alert('Fungsionalitas hapus akan diimplementasikan di sini');
}
</script>
@endpush
