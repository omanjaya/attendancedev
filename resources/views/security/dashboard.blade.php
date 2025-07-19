@extends('layouts.authenticated-unified')

@section('title', 'Dasbor Keamanan')

@section('page-content')
<div class="p-6 lg:p-8">
    <x-layouts.base-page
        title="Dasbor Keamanan"
        subtitle="Pantau keamanan akun dan akses perangkat Anda"
        :breadcrumbs="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Keamanan']
        ]">

        <!-- Security Metrics -->
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
            <x-ui.card variant="metric" title="Total Perangkat" :value="$metrics['total_devices']" color="info">
                <x-slot name="icon">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                </x-slot>
            </x-ui.card>
            <x-ui.card variant="metric" title="Perangkat Terpercaya" :value="$metrics['trusted_devices']" color="success">
                <x-slot name="icon">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                </x-slot>
            </x-ui.card>
            <x-ui.card variant="metric" title="Login Terbaru (30hr)" :value="$metrics['recent_logins']" color="warning">
                <x-slot name="icon">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m0 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                </x-slot>
            </x-ui.card>
            <x-ui.card variant="metric" title="Insiden Keamanan (7hr)" :value="$metrics['security_events']" color="destructive">
                <x-slot name="icon">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.314 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                </x-slot>
            </x-ui.card>
        </div>

        <!-- Quick Actions -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <x-ui.card class="hover:bg-muted/30 transition-colors">
                <a href="{{ route('security.devices') }}" class="block p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 p-3 bg-blue-100 rounded-lg">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-medium text-foreground">Kelola Perangkat</h3>
                            <p class="text-muted-foreground">Lihat dan kelola perangkat terpercaya</p>
                        </div>
                    </div>
                </a>
            </x-ui.card>
            <x-ui.card class="hover:bg-muted/30 transition-colors">
                <a href="{{ route('security.notifications') }}" class="block p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 p-3 bg-yellow-100 rounded-lg">
                            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM11 19l-7-7 7-7m0 14l7-7-7-7"/></svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-medium text-foreground">Notifikasi Keamanan</h3>
                            <p class="text-muted-foreground">Tinjau peringatan dan notifikasi keamanan</p>
                        </div>
                    </div>
                </a>
            </x-ui.card>
            <x-ui.card class="hover:bg-muted/30 transition-colors">
                <a href="{{ route('security.two-factor') }}" class="block p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 p-3 bg-green-100 rounded-lg">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-medium text-foreground">Autentikasi Dua Faktor</h3>
                            <p class="text-muted-foreground">Kelola pengaturan 2FA</p>
                        </div>
                    </div>
                </a>
            </x-ui.card>
        </div>

        <!-- Recent Security Events -->
        <x-ui.card title="Insiden Keamanan Terbaru">
            <div class="divide-y divide-border">
                @forelse($recentEvents as $event)
                    <div class="px-6 py-4 hover:bg-muted/30">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <x-ui.badge variant="{{ 
                                        match($event->risk_level) {
                                            'high' => 'destructive',
                                            'medium' => 'warning',
                                            default => 'success'
                                        }
                                    }}" class="w-3 h-3 p-0 rounded-full"></x-ui.badge>
                                </div>
                                <div class="ml-4">
                                    <h4 class="text-sm font-medium text-foreground">{{ $event->event_name }}</h4>
                                    <p class="text-sm text-muted-foreground">{{ $event->description }}</p>
                                </div>
                            </div>
                            <div class="text-sm text-muted-foreground">
                                {{ $event->created_at->diffForHumans() }}
                            </div>
                        </div>
                    </div>
                @empty
                    <x-ui.empty-state
                        title="Tidak Ada Insiden Keamanan"
                        description="Akun Anda aman tanpa ada insiden keamanan terbaru."
                        icon="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                @endforelse
            </div>
            @if($recentEvents->count() > 0)
                <div class="p-4 border-t border-border">
                    <x-ui.button variant="link" href="{{ route('security.events') }}">
                        Lihat semua insiden keamanan →
                    </x-ui.button>
                </div>
            @endif
        </x-ui.card>

    </x-layouts.base-page>
</div>
@endsection
