@extends('layouts.authenticated-unified')

@section('title', 'Kelola Otentikasi Dua Faktor')

@section('page-content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100 dark:from-slate-900 dark:via-blue-900 dark:to-indigo-900">
    <div class="p-6 lg:p-8">
        <x-layouts.base-page
            title="Kelola Otentikasi Dua Faktor"
            subtitle="Pengaturan keamanan - Tambah lapisan keamanan ke akun Anda"
            :breadcrumbs="[
                ['label' => 'Dashboard', 'url' => route('dashboard')],
                ['label' => 'Keamanan', 'url' => route('security.dashboard')],
                ['label' => '2FA']
            ]">
        </x-layouts.base-page>

        <!-- Vue.js 2FA Dashboard -->
        <div id="twofa-dashboard-app" class="mt-6">
            <two-factor-dashboard 
                :user="{{ json_encode(auth()->user()->only(['id', 'name', 'email', 'phone', 'two_factor_enabled_at'])) }}"
                :initial-status="{{ json_encode([
                    'enabled' => auth()->user()->two_factor_enabled,
                    'required' => $isRequired,
                    'hasRecoveryCodes' => !empty($recoveryCodes),
                    'recoveryCodesCount' => count($recoveryCodes)
                ]) }}"
            ></two-factor-dashboard>
        </div>

        <!-- Fallback for non-JS users -->
        <noscript>
            <div class="mt-6 group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-semibold text-slate-800 dark:text-white flex items-center">
                        <svg class="w-6 h-6 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3a12 12 0 0 0 8.5 3a12 12 0 0 1 -8.5 15a12 12 0 0 1 -8.5 -15a12 12 0 0 0 8.5 -3"/>
                            <circle cx="12" cy="11" r="1"/>
                            <path d="m12 12l0 2.5"/>
                        </svg>
                        Pengaturan Otentikasi Dua Faktor
                    </h3>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium text-white bg-gradient-to-r from-green-500 to-emerald-600 shadow-lg">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12l5 5l10 -10"/></svg>
                        Aktif
                    </span>
                </div>
                <div class="space-y-6">
                    <div class="bg-blue-500/20 border border-blue-500/30 text-blue-800 dark:text-blue-200 px-4 py-3 rounded-lg">
                        <p class="font-medium">JavaScript Diperlukan</p>
                        <p class="text-sm mt-1">Halaman ini memerlukan JavaScript agar berfungsi dengan baik. Mohon aktifkan JavaScript di browser Anda untuk mengelola pengaturan 2FA Anda.</p>
                    </div>
                    
                    <div class="space-y-4">
                        <h4 class="text-lg font-semibold text-slate-800 dark:text-white">Status Saat Ini</h4>
                        <div class="bg-green-500/20 border border-green-500/30 rounded-lg p-4">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-green-600 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                <span class="font-medium text-green-800 dark:text-green-200">Otentikasi Dua Faktor diaktifkan</span>
                            </div>
                            <p class="text-sm text-green-700 dark:text-green-300 mt-2">Akun Anda diamankan dengan 2FA. Anda memiliki {{ count($recoveryCodes) }} kode pemulihan tersedia.</p>
                        </div>
                    </div>
                    
                    <div class="space-y-3">
                        <h4 class="text-lg font-semibold text-slate-800 dark:text-white">Tindakan Tersedia</h4>
                        <a href="{{ route('2fa.recovery') }}" class="group block p-3 border border-white/20 rounded-lg hover:bg-white/10 transition-colors">
                            <span class="font-medium text-slate-800 dark:text-white">Lihat Kode Pemulihan</span>
                            <p class="text-sm text-slate-600 dark:text-slate-400">Akses kode cadangan Anda untuk pemulihan akun</p>
                        </a>
                        
                        @if(!$isRequired)
                        <form action="{{ route('2fa.disable') }}" method="POST" class="group block p-3 border border-red-500/30 rounded-lg bg-red-500/10">
                            @csrf
                            <div class="space-y-3">
                                <span class="font-medium text-red-600 dark:text-red-200">Nonaktifkan Otentikasi Dua Faktor</span>
                                <p class="text-sm text-slate-600 dark:text-slate-400">Matikan 2FA untuk akun Anda (memerlukan kata sandi)</p>
                                <div class="flex space-x-2">
                                    <x-ui.input type="password" name="password" placeholder="Kata sandi Anda" required class="flex-1 bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300" />
                                    <x-ui.button type="submit" variant="destructive">Nonaktifkan</x-ui.button>
                                </div>
                            </div>
                        </form>
                        @endif
                    </div>
                </div>
            </div>
        </noscript>
    </div>
</div>
@endsection

@push('scripts')
<script type="module">
import { createApp } from 'vue'
import TwoFactorDashboard from '@/components/Security/TwoFactorDashboard.vue'

// Create Vue app for 2FA dashboard
const app = createApp({
    components: {
        TwoFactorDashboard
    }
})

// Mount the app
app.mount('#twofa-dashboard-app')
</script>
@endpush
