@extends('layouts.guest')

@section('title', 'Two-Factor Authentication')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <div class="bg-white py-8 px-4 shadow-lg sm:rounded-lg sm:px-10">
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
                        <h2 class="text-2xl font-bold text-gray-900">Two-Factor Authentication</h2>
                        <p class="mt-2 text-gray-600">
                            Please enter the authentication code from your authenticator app.
                        </p>
                    </div>

                    @if ($errors->any())
                        <div class="p-4 bg-red-50 border border-red-200 rounded-md">
                            <div class="text-sm text-red-600">
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
                                <label for="code" class="block text-sm font-medium text-gray-700">Authentication Code</label>
                                <input id="code" 
                                       type="text" 
                                       name="code" 
                                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" 
                                       placeholder="123456"
                                       maxlength="6"
                                       pattern="[0-9]{6}"
                                       required 
                                       autofocus>
                                <p class="mt-1 text-xs text-gray-500">Enter the 6-digit code from your authenticator app</p>
                            </div>

                            <button type="submit" 
                                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg">
                                Verify & Continue
                            </button>
                        </div>
                    </form>

                    <div class="text-center">
                        <p class="text-sm text-gray-600">
                            Can't access your authenticator app?
                            <a href="{{ route('2fa.recovery') }}" class="font-medium text-blue-600 hover:text-blue-500">
                                Use recovery code
                            </a>
                        </p>
                    </div>
                </div>
            </noscript>
        </div>
    </div>

    <!-- Additional Security Info -->
    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center space-x-2 text-sm text-gray-600">
                <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path>
                </svg>
                <span>Your account is secured with two-factor authentication</span>
            </div>
            
            @if(config('app.env') === 'production')
            <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                <div class="flex items-start space-x-2">
                    <svg class="w-4 h-4 text-blue-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                    </svg>
                    <div class="text-sm text-blue-800">
                        <p class="font-medium">Security Notice</p>
                        <p class="mt-1">This verification is required to protect your account and school data.</p>
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
            this.showNotification('error', error.message || 'Verification failed. Please try again.')
        },
        
        showNotification(type, message) {
            // Simple notification system
            const notification = document.createElement('div')
            notification.className = `fixed top-4 right-4 z-50 max-w-sm bg-white border rounded-lg shadow-lg p-4 transition-all duration-300 ${
                type === 'error' ? 'border-red-200 text-red-800' : 
                type === 'warning' ? 'border-yellow-200 text-yellow-800' : 
                'border-green-200 text-green-800'
            }`
            
            notification.innerHTML = `
                <div class="flex items-center space-x-2">
                    <div class="w-4 h-4 flex-shrink-0">
                        ${type === 'error' ? '❌' : type === 'warning' ? '⚠️' : '✅'}
                    </div>
                    <span class="text-sm font-medium">${message}</span>
                    <button onclick="this.parentElement.parentElement.remove()" class="ml-auto text-gray-400 hover:text-gray-600">
                        ✕
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