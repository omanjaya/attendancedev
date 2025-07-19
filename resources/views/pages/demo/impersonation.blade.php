@extends('layouts.authenticated-unified')

@section('title', 'Demo Impersonation')

@section('page-content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100 dark:from-slate-900 dark:via-blue-900 dark:to-indigo-900">
    <div class="p-6 lg:p-8">
        <x-layouts.base-page
            title="Fitur Impersonation"
            subtitle="Demo fitur \"Login Sebagai\" untuk Admin dan Super Admin"
            :breadcrumbs="[
                ['label' => 'Dashboard', 'url' => route('dashboard')],
                ['label' => 'Demo'],
                ['label' => 'Impersonation']
            ]">
        </x-layouts.base-page>

        <!-- Current Status -->
        <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out mb-8">
            <h2 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Status Saat Ini</h2>
            
            @if(Session::has('impersonated_by'))
                @php
                    $originalUser = \App\Models\User::find(Session::get('impersonated_by'));
                @endphp
                <div class="bg-amber-500/20 backdrop-blur-sm border border-amber-500/30 rounded-lg p-4 shadow-lg">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <svg class="w-6 h-6 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            <div>
                                <p class="font-medium text-amber-800 dark:text-amber-200">Mode Impersonation Aktif</p>
                                <p class="text-sm text-amber-700 dark:text-amber-300">
                                    Anda sedang login sebagai <strong>{{ Auth::user()->name }}</strong> 
                                    ({{ Auth::user()->roles->first()->name ?? 'Staf' }})
                                </p>
                                <p class="text-xs text-amber-600 dark:text-amber-400">
                                    Pengguna asli: {{ $originalUser->name ?? 'Tidak Dikenal' }}
                                </p>
                            </div>
                        </div>
                        <form method="POST" action="{{ route('impersonate.stop') }}">
                            @csrf
                            <x-ui.button type="submit" variant="warning">Kembali ke Pengguna Asli</x-ui.button>
                        </form>
                    </div>
                </div>
            @else
                <div class="bg-blue-500/20 backdrop-blur-sm border border-blue-500/30 rounded-lg p-4 shadow-lg">
                    <div class="flex items-center space-x-3">
                        <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div>
                            <p class="font-medium text-blue-800 dark:text-blue-200">Mode Normal</p>
                            <p class="text-sm text-blue-700 dark:text-blue-300">
                                Anda login sebagai <strong>{{ Auth::user()->name }}</strong> 
                                ({{ Auth::user()->roles->first()->name ?? 'Staf' }})
                            </p>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Permissions Check -->
        <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out mb-8">
            <h2 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Izin</h2>
            
            <div class="space-y-3">
                <div class="flex items-center justify-between p-3 bg-white/10 rounded-lg">
                    <span class="font-medium text-slate-800 dark:text-white">Dapat Meniru Pengguna</span>
                    @if(Auth::user()->can('impersonate_users'))
                        <span class="px-3 py-1 bg-green-500/20 text-green-700 dark:text-green-300 text-sm rounded-full">✓ Diizinkan</span>
                    @else
                        <span class="px-3 py-1 bg-red-500/20 text-red-700 dark:text-red-300 text-sm rounded-full">✗ Ditolak</span>
                    @endif
                </div>
                
                <div class="flex items-center justify-between p-3 bg-white/10 rounded-lg">
                    <span class="font-medium text-slate-800 dark:text-white">Peran Saat Ini</span>
                    <span class="px-3 py-1 bg-blue-500/20 text-blue-700 dark:text-blue-300 text-sm rounded-full">
                        {{ Auth::user()->roles->first()->name ?? 'Tidak Ada Peran' }}
                    </span>
                </div>
                
                <div class="flex items-center justify-between p-3 bg-white/10 rounded-lg">
                    <span class="font-medium text-slate-800 dark:text-white">Akses Admin</span>
                    @if(Auth::user()->can('access_admin_panel'))
                        <span class="px-3 py-1 bg-green-500/20 text-green-700 dark:text-green-300 text-sm rounded-full">✓ Diizinkan</span>
                    @else
                        <span class="px-3 py-1 bg-red-500/20 text-red-700 dark:text-red-300 text-sm rounded-full">✗ Ditolak</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Available Actions -->
        @if(Auth::user()->can('impersonate_users') && !Session::has('impersonated_by'))
        <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out mb-8">
            <h2 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Tindakan Tersedia</h2>
            
            <div class="bg-emerald-500/20 backdrop-blur-sm border border-emerald-500/30 rounded-lg p-4 shadow-lg">
                <div class="flex items-center space-x-3">
                    <svg class="w-6 h-6 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                    </svg>
                    <div>
                        <p class="font-medium text-emerald-800 dark:text-emerald-200">Impersonation Tersedia</p>
                        <p class="text-sm text-emerald-700 dark:text-emerald-300">
                            Klik ikon "Login Sebagai" di header (kanan atas) untuk melihat daftar pengguna yang bisa ditiru.
                        </p>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Instructions -->
        <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out mb-8">
            <h2 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Cara Menggunakan</h2>
            
            <div class="space-y-4 text-slate-600 dark:text-slate-400">
                <div class="flex items-start space-x-3">
                    <span class="flex-shrink-0 w-6 h-6 bg-blue-500/20 text-blue-500 dark:text-blue-300 text-sm rounded-full flex items-center justify-center font-medium">1</span>
                    <div>
                        <p class="font-medium text-slate-800 dark:text-white">Login sebagai Admin atau Super Admin</p>
                        <p class="text-sm">Hanya peran dengan izin 'impersonate_users' yang bisa menggunakan fitur ini.</p>
                    </div>
                </div>
                
                <div class="flex items-start space-x-3">
                    <span class="flex-shrink-0 w-6 h-6 bg-blue-500/20 text-blue-500 dark:text-blue-300 text-sm rounded-full flex items-center justify-center font-medium">2</span>
                    <div>
                        <p class="font-medium text-slate-800 dark:text-white">Klik ikon "Login Sebagai" di header</p>
                        <p class="text-sm">Ikon pengalih pengguna akan muncul di sebelah kanan notifikasi.</p>
                    </div>
                </div>
                
                <div class="flex items-start space-x-3">
                    <span class="flex-shrink-0 w-6 h-6 bg-blue-500/20 text-blue-500 dark:text-blue-300 text-sm rounded-full flex items-center justify-center font-medium">3</span>
                    <div>
                        <p class="font-medium text-slate-800 dark:text-white">Pilih pengguna target</p>
                        <p class="text-sm">Gunakan fitur pencarian untuk mencari pengguna berdasarkan nama atau email.</p>
                    </div>
                </div>
                
                <div class="flex items-start space-x-3">
                    <span class="flex-shrink-0 w-6 h-6 bg-blue-500/20 text-blue-500 dark:text-blue-300 text-sm rounded-full flex items-center justify-center font-medium">4</span>
                    <div>
                        <p class="font-medium text-slate-800 dark:text-white">Klik "Login"</p>
                        <p class="text-sm">Sistem akan mengalihkan Anda ke dasbor pengguna tersebut.</p>
                    </div>
                </div>
                
                <div class="flex items-start space-x-3">
                    <span class="flex-shrink-0 w-6 h-6 bg-blue-500/20 text-blue-500 dark:text-blue-300 text-sm rounded-full flex items-center justify-center font-medium">5</span>
                    <div>
                        <p class="font-medium text-slate-800 dark:text-white">Klik "Kembali" untuk menghentikan impersonation</p>
                        <p class="text-sm">Banner kuning akan muncul di header saat mode impersonation aktif.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Security Features -->
        <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out mb-8">
            <h2 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">🔒 Fitur Keamanan</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-slate-600 dark:text-slate-400">
                <div class="bg-white/10 rounded-lg p-4">
                    <h3 class="font-medium text-slate-800 dark:text-white mb-2">Akses Berbasis Izin</h3>
                    <p class="text-sm">Hanya pengguna dengan izin khusus yang bisa meniru.</p>
                </div>
                
                <div class="bg-white/10 rounded-lg p-4">
                    <h3 class="font-medium text-slate-800 dark:text-white mb-2">Pencatatan Audit</h3>
                    <p class="text-sm">Semua aktivitas peniruan dicatat di log sistem.</p>
                </div>
                
                <div class="bg-white/10 rounded-lg p-4">
                    <h3 class="font-medium text-slate-800 dark:text-white mb-2">Pembatasan Peran</h3>
                    <p class="text-sm">Admin tidak bisa meniru Super Admin.</p>
                </div>
                
                <div class="bg-white/10 rounded-lg p-4">
                    <h3 class="font-medium text-slate-800 dark:text-white mb-2">Manajemen Sesi</h3>
                    <p class="text-sm">Sistem melacak pengguna asli dan bisa kembali kapan saja.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
