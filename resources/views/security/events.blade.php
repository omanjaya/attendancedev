@extends('layouts.authenticated-unified')

@section('title', 'Kejadian Keamanan')

@section('page-content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100 dark:from-slate-900 dark:via-blue-900 dark:to-indigo-900">
    <div class="p-6 lg:p-8">
        <x-layouts.base-page
            title="Kejadian Keamanan"
            subtitle="Lihat semua aktivitas terkait keamanan di akun Anda"
            :breadcrumbs="[
                ['label' => 'Dashboard', 'url' => route('dashboard')],
                ['label' => 'Keamanan', 'url' => route('security.dashboard')],
                ['label' => 'Kejadian']
            ]">
            <x-slot name="actions">
                <x-ui.button variant="secondary" href="{{ route('security.dashboard') }}">
                    <svg class="w-5 h-5 mr-2 group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Kembali ke Keamanan
                </x-ui.button>
            </x-slot>
        </x-layouts.base-page>

        <!-- Events List -->
        <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
            <h3 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Kejadian Anda</h3>
            <p class="text-slate-600 dark:text-slate-400 mb-6">Kejadian yang telah mengakses akun Anda</p>
            
            @if($events->count() > 0)
                <div class="divide-y divide-white/20">
                    @foreach($events as $event)
                        <div class="px-6 py-4 hover:bg-white/5 transition-colors">
                            <div class="flex items-start justify-between">
                                <div class="flex items-start">
                                    <div class="flex-shrink-0 w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg">
                                        @if($event->risk_level === 'high')
                                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 15.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                                        @elseif($event->risk_level === 'medium')
                                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        @else
                                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        @endif
                                    </div>
                                    
                                    <div class="ml-4">
                                        <div class="flex items-center">
                                            <h4 class="text-lg font-semibold text-slate-800 dark:text-white">
                                                {{ $event->event_name }}
                                            </h4>
                                            <span class="ml-2 inline-flex items-center px-3 py-1 rounded-full text-xs font-medium 
                                                @if($event->risk_level === 'high') text-white bg-gradient-to-r from-red-500 to-rose-600 shadow-lg
                                                @elseif($event->risk_level === 'medium') text-white bg-gradient-to-r from-amber-500 to-orange-600 shadow-lg
                                                @else text-white bg-gradient-to-r from-green-500 to-emerald-600 shadow-lg @endif">
                                                {{ ucfirst($event->risk_level) }} Risiko
                                            </span>
                                        </div>
                                        <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">
                                            {{ $event->description }}
                                        </p>
                                        
                                        <div class="mt-2 space-y-1 text-xs text-slate-500 dark:text-slate-400">
                                            <div>
                                                <span class="font-medium">Tipe Kejadian:</span> {{ ucfirst($event->event_type) }}
                                            </div>
                                            @if($event->ip_address)
                                                <div>
                                                    <span class="font-medium">Alamat IP:</span> {{ $event->ip_address }}
                                                </div>
                                            @endif
                                            @if($event->user_agent)
                                                <div>
                                                    <span class="font-medium">Agen Pengguna:</span> {{ Str::limit($event->user_agent, 80) }}
                                                </div>
                                            @endif
                                            @if($event->metadata)
                                                @php
                                                    $metadata = is_string($event->metadata) ? json_decode($event->metadata, true) : $event->metadata;
                                                @endphp
                                                @if(is_array($metadata) && count($metadata) > 0)
                                                    <div>
                                                        <span class="font-medium">Detail:</span>
                                                        @foreach($metadata as $key => $value)
                                                            {{ $key }}: {{ is_array($value) ? json_encode($value) : $value }}
                                                            @if(!$loop->last), @endif
                                                        @endforeach
                                                    </div>
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="text-sm text-slate-500 dark:text-slate-400">
                                    {{ $event->created_at->format('M j, Y') }}
                                    <div class="text-xs text-slate-400 dark:text-slate-500">
                                        {{ $event->created_at->format('H:i:s') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                
                <div class="px-6 py-4 border-t border-white/20">
                    {{ $events->links() }}
                </div>
            @else
                <div class="px-6 py-8 text-center text-slate-600 dark:text-slate-400">
                    <svg class="w-12 h-12 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    <h3 class="text-lg font-semibold text-slate-800 dark:text-white mb-2">Tidak ada kejadian keamanan</h3>
                    <p>Tidak ada kejadian keamanan yang tercatat untuk akun Anda.</p>
                </div>
            @endif
        </div>

        <!-- Event Types Legend -->
        <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out mt-8">
            <h3 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Tipe Kejadian</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-slate-600 dark:text-slate-400">
                <div class="flex items-center">
                    <div class="w-3 h-3 bg-green-500 rounded-full mr-3"></div>
                    <div>
                        <span class="text-sm font-medium text-slate-800 dark:text-white">Kejadian Otentikasi</span>
                        <p class="text-xs">Login, logout, dan aktivitas otentikasi</p>
                    </div>
                </div>
                <div class="flex items-center">
                    <div class="w-3 h-3 bg-amber-500 rounded-full mr-3"></div>
                    <div>
                        <span class="text-sm font-medium text-slate-800 dark:text-white">Kejadian Akses</span>
                        <p class="text-xs">Kunjungan halaman dan akses sumber daya</p>
                    </div>
                </div>
                <div class="flex items-center">
                    <div class="w-3 h-3 bg-red-500 rounded-full mr-3"></div>
                    <div>
                        <span class="text-sm font-medium text-slate-800 dark:text-white">Kejadian Keamanan</span>
                        <p class="text-xs">Aktivitas dan peringatan terkait keamanan</p>
                    </div>
                </div>
                <div class="flex items-center">
                    <div class="w-3 h-3 bg-blue-500 rounded-full mr-3"></div>
                    <div>
                        <span class="text-sm font-medium text-slate-800 dark:text-white">Kejadian Data</span>
                        <p class="text-xs">Pembuatan, modifikasi, dan penghapusan data</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
