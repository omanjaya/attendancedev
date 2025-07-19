@extends('layouts.authenticated-unified')

@section('title', 'Otentikasi Dua Faktor')

@section('page-content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100 dark:from-slate-900 dark:via-blue-900 dark:to-indigo-900">
    <div class="p-6 lg:p-8">
        <x-layouts.base-page
            title="Otentikasi Dua Faktor"
            subtitle="Amankan akun Anda dengan otentikasi dua faktor"
            :breadcrumbs="[
                ['label' => 'Dashboard', 'url' => route('dashboard')],
                ['label' => 'Keamanan', 'url' => route('security.dashboard')],
                ['label' => '2FA']
            ]">
            <x-slot name="actions">
                <x-ui.button variant="secondary" href="{{ route('security.dashboard') }}">
                    <svg class="w-5 h-5 mr-2 group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Kembali ke Keamanan
                </x-ui.button>
            </x-slot>
        </x-layouts.base-page>

        <!-- 2FA Status -->
        <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out mb-8">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="flex-shrink-0 w-12 h-12 bg-gradient-to-br {{ $user->two_factor_enabled ? 'from-green-500 to-emerald-600' : 'from-red-500 to-rose-600' }} rounded-full flex items-center justify-center shadow-lg">
                        @if($user->two_factor_enabled)
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        @else
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.314 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                        @endif
                    </div>
                    <div class="ml-4">
                        <h3 class="text-xl font-semibold text-slate-800 dark:text-white">
                            Otentikasi Dua Faktor 
                            @if($user->two_factor_enabled)
                                <span class="text-green-500">Diaktifkan</span>
                            @else
                                <span class="text-red-500">Dinonaktifkan</span>
                            @endif
                        </h3>
                        <p class="text-sm text-slate-600 dark:text-slate-400">
                            @if($user->two_factor_enabled)
                                Akun Anda dilindungi dengan otentikasi dua faktor
                            @else
                                Aktifkan otentikasi dua faktor untuk keamanan yang ditingkatkan
                            @endif
                        </p>
                    </div>
                </div>
                <div>
                    @if($user->two_factor_enabled)
                        <x-ui.button href="{{ route('2fa.manage') }}" variant="primary">
                            Kelola 2FA
                        </x-ui.button>
                    @else
                        <x-ui.button href="{{ route('2fa.setup') }}" variant="primary">
                            Aktifkan 2FA
                        </x-ui.button>
                    @endif
                </div>
            </div>
        </div>

        <!-- 2FA Methods -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                <div class="flex items-center mb-4">
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center shadow-lg">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    </div>
                    <h3 class="text-xl font-semibold text-slate-800 dark:text-white ml-3">Aplikasi Authenticator</h3>
                </div>
                <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">
                    Gunakan aplikasi authenticator seperti Google Authenticator atau Authy untuk menghasilkan kode berbasis waktu.
                </p>
                <div class="space-y-2 text-sm text-slate-600 dark:text-slate-400">
                    <div class="flex items-center">
                        <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Bekerja offline
                    </div>
                    <div class="flex items-center">
                        <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Opsi paling aman
                    </div>
                    <div class="flex items-center">
                        <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Metode yang direkomendasikan
                    </div>
                </div>
            </div>

            <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out opacity-50 cursor-not-allowed">
                <div class="flex items-center mb-4">
                    <div class="w-10 h-10 bg-gradient-to-br from-gray-500 to-slate-600 rounded-full flex items-center justify-center shadow-lg">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    </div>
                    <h3 class="text-xl font-semibold text-slate-800 dark:text-white ml-3">Verifikasi SMS</h3>
                </div>
                <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">
                    Terima kode verifikasi melalui SMS. (Saat ini dinonaktifkan karena alasan keamanan)
                </p>
                <div class="space-y-2 text-sm text-slate-600 dark:text-slate-400">
                    <div class="flex items-center text-red-500">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        Kurang aman dari aplikasi
                    </div>
                    <div class="flex items-center text-red-500">
                        <svg class="w-4 h-4 text-red-500 mr-2" fill="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        Membutuhkan koneksi jaringan
                    </div>
                    <div class="flex items-center text-red-500">
                        <svg class="w-4 h-4 text-red-500 mr-2" fill="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        Saat ini dinonaktifkan
                    </div>
                </div>
            </div>
        </div>

        <!-- Recovery Codes -->
        @if($user->two_factor_enabled)
            <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out mb-8">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-semibold text-slate-800 dark:text-white">Kode Pemulihan</h3>
                    <x-ui.button href="{{ route('2fa.manage') }}" variant="secondary" size="sm">
                        Lihat/Hasilkan Kode
                    </x-ui.button>
                </div>
                <div class="bg-amber-500/20 border border-amber-500/30 rounded-lg p-4 shadow-lg">
                    <div class="flex items-start">
                        <svg class="w-6 h-6 text-amber-500 mt-0.5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.314 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                        <div class="ml-3">
                            <h4 class="text-lg font-semibold text-amber-800 dark:text-amber-200">Simpan kode pemulihan Anda dengan aman</h4>
                            <p class="text-sm text-amber-700 dark:text-amber-300 mt-1">
                                Simpan kode ini di lokasi yang aman. Anda dapat menggunakannya untuk mengakses akun Anda jika Anda kehilangan akses ke aplikasi authenticator Anda.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- 2FA Information -->
        <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
            <div class="flex items-start">
                <svg class="w-6 h-6 text-blue-500 mt-0.5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div class="ml-4">
                    <h4 class="text-xl font-semibold text-slate-800 dark:text-white">Tentang Otentikasi Dua Faktor</h4>
                    <div class="text-sm text-slate-600 dark:text-slate-400 mt-2 space-y-1">
                        <p>• Otentikasi dua faktor menambahkan lapisan keamanan ekstra ke akun Anda</p>
                        <p>• Bahkan jika seseorang mengetahui kata sandi Anda, mereka tidak dapat mengakses akun Anda tanpa faktor kedua</p>
                        <p>• Kami merekomendasikan penggunaan aplikasi authenticator untuk keamanan terbaik</p>
                        <p>• Simpan kode pemulihan Anda di tempat yang aman sebagai cadangan</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
