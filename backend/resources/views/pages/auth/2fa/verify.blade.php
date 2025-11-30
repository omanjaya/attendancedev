@extends('layouts.guest')

@section('title', 'Otentikasi Dua Faktor')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100 dark:from-slate-900 dark:via-blue-900 dark:to-indigo-900 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-8 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
            <!-- Vue.js 2FA Verification App -->
            <div id="twofa-verification-app">
                <two-factor-verification 
                    :user="{{ json_encode(auth()->user()->only(['id', 'name', 'email', 'phone'])) }}"
                    :intended-url="{{ json_encode(session('intended', route('dashboard'))) }}"
                    :allow-remember-device="true"
                    :sms-enabled="{{ json_encode(config('services.sms.enabled', false)) }}"
                    csrf-token="{{ csrf_token() }}"
                    @success="handleSuccess"
                    @logout="handleLogout"
                    @error="handleError"
                ></two-factor-verification>
            </div>

            <!-- Fallback for non-JS users -->
            <noscript>
                <div class="space-y-6">
                    <div class="text-center">
                        <h2 class="text-2xl font-bold text-slate-800 dark:text-white">Otentikasi Dua Faktor</h2>
                        <p class="mt-2 text-slate-600 dark:text-slate-400">
                            Mohon masukkan kode otentikasi dari aplikasi authenticator Anda.
                        </p>
                    </div>

                    @if ($errors->any())
                        <div class="bg-red-500/20 border border-red-500/30 rounded-lg p-4">
                            <div class="text-sm text-red-800 dark:text-red-200">
                                @foreach ($errors->all() as $error)
                                    <p>{{ $error }}</p>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('2fa.verify') }}">
                        @csrf
                        <input type="hidden" name="type" value="totp">

                        <div class="space-y-4">
                            <div>
                                <x-ui.label for="code" class="text-slate-700 dark:text-slate-300">Kode Otentikasi</x-ui.label>
                                <x-ui.input 
                                    id="code" 
                                    type="text" 
                                    name="code" 
                                    placeholder="123456"
                                    maxlength="6"
                                    pattern="[0-9]{6}"
                                    required 
                                    autofocus
                                    class="bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300" />
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Masukkan kode 6 digit dari aplikasi authenticator Anda</p>
                            </div>

                            <x-ui.button type="submit" variant="primary" class="w-full">
                                Verifikasi & Lanjutkan
                            </x-ui.button>
                        </div>
                    </form>

                    <div class="text-center">
                        <p class="text-sm text-slate-600 dark:text-slate-400">
                            Tidak dapat mengakses aplikasi authenticator Anda?
                            <x-ui.button variant="link" href="{{ route('2fa.recovery') }}" class="font-medium text-blue-500 dark:text-blue-400 hover:text-blue-600 dark:hover:text-blue-300">
                                Gunakan kode pemulihan
                            </x-ui.button>
                        </p>
                    </div>
                </div>
            </noscript>
        </div>
    </div>

    <!-- Additional Security Info -->
    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
        <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
            <div class="flex items-center space-x-2 text-sm text-slate-600 dark:text-slate-400">
                <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path>
                </svg>
                <span>Akun Anda diamankan dengan otentikasi dua faktor</span>
            </div>
            
            @if(config('app.env') === 'production')
            <div class="mt-4 bg-blue-500/20 border border-blue-500/30 rounded-lg p-3">
                <div class="flex items-start space-x-2">
                    <svg class="w-4 h-4 text-blue-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                    </svg>
                    <div class="text-sm text-blue-800 dark:text-blue-200">
                        <p class="font-medium">Pemberitahuan Keamanan</p>
                        <p class="mt-1">Verifikasi ini diperlukan untuk melindungi akun dan data sekolah Anda.</p>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script type="module">
import { createApp } from 'vue'
import TwoFactorVerification from '@/components/Auth/TwoFactorVerification.vue'

// Create Vue app for 2FA verification
const app = createApp({
    components: {
        TwoFactorVerification
    },
    methods: {
        handleSuccess(result) {
            // Show success message if there's a warning
            if (result.warning) {
                this.showNotification('warning', result.warning)
            }
            
            // Redirect to intended page
            setTimeout(() => {
                window.location.href = result.redirect
            }, result.warning ? 2000 : 500)
        },
        
        handleLogout() {
            // Create a form and submit to logout
            const form = document.createElement('form')
            form.method = 'POST'
            form.action = '/logout'
            
            const csrfInput = document.createElement('input')
            csrfInput.type = 'hidden'
            csrfInput.name = '_token'
            csrfInput.value = document.querySelector('meta[name="csrf-token"]')?.content || ''
            form.appendChild(csrfInput)
            
            document.body.appendChild(form)
            form.submit()
        },
        
        handleError(error) {
            console.error('2FA Verification Error:', error)
            this.showNotification('error', error.message || 'Verifikasi gagal. Mohon coba lagi.')
        },
        
        showNotification(type, message) {
            // Simple notification system
            const notification = document.createElement('div')
            notification.className = `fixed top-4 right-4 z-50 max-w-sm bg-white/80 backdrop-blur-sm border rounded-lg shadow-lg p-4 transition-all duration-300 ${
                type === 'error' ? 'border-red-500/30 text-red-800 dark:text-red-200' : 
                type === 'warning' ? 'border-amber-500/30 text-amber-800 dark:text-amber-200' : 
                'border-green-500/30 text-green-800 dark:text-green-200'
            }`
            
            notification.innerHTML = `
                <div class="flex items-center space-x-2">
                    <div class="w-4 h-4 flex-shrink-0">
                        ${type === 'error' ? '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>' : 
                          type === 'warning' ? '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>' : 
                          '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'}
                    </div>
                    <span class="text-sm font-medium">${message}</span>
                    <button onclick="this.closest('.notification').remove();" class="ml-auto text-slate-600 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            `
            
            document.body.appendChild(notification)
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.style.opacity = '0'
                    setTimeout(() => {
                        if (notification.parentNode) {
                            notification.parentNode.removeChild(notification)
                        }
                    }, 300)
                }
            }, 5000)
        }
    }
})

// Mount the app
app.mount('#twofa-verification-app')
</script>
@endpush
