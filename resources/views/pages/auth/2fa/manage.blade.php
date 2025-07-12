@extends('layouts.app')

@section('title', 'Manage Two-Factor Authentication')

@section('content')
<div class="page-body">
    <!-- Vue.js 2FA Dashboard -->
    <div id="twofa-dashboard-app">
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
        <div class="max-w-7xl mx-auto px-8">
            <div class="max-w-4xl mx-auto">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-medium text-gray-900 flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon mr-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M12 3a12 12 0 0 0 8.5 3a12 12 0 0 1 -8.5 15a12 12 0 0 1 -8.5 -15a12 12 0 0 0 8.5 -3"/>
                                    <circle cx="12" cy="11" r="1"/>
                                    <path d="m12 12l0 2.5"/>
                                </svg>
                                Two-Factor Authentication Settings
                            </h3>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M5 12l5 5l10 -10"/>
                                </svg>
                                Enabled
                            </span>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="space-y-6">
                            <div class="bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded-md">
                                <p class="font-medium">JavaScript Required</p>
                                <p class="text-sm mt-1">This page requires JavaScript to function properly. Please enable JavaScript in your browser to manage your 2FA settings.</p>
                            </div>
                            
                            <div class="space-y-4">
                                <h4 class="text-lg font-medium text-gray-900">Current Status</h4>
                                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                                    <div class="flex items-center">
                                        <svg class="w-5 h-5 text-green-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                        <span class="font-medium text-green-800">Two-Factor Authentication is enabled</span>
                                    </div>
                                    <p class="text-sm text-green-700 mt-2">Your account is secured with 2FA. You have {{ count($recoveryCodes) }} recovery codes available.</p>
                                </div>
                            </div>
                            
                            <div class="space-y-3">
                                <h4 class="text-lg font-medium text-gray-900">Available Actions</h4>
                                <div class="space-y-2">
                                    <a href="{{ route('2fa.recovery') }}" class="block p-3 border border-gray-200 rounded-lg hover:bg-gray-50">
                                        <span class="font-medium text-gray-900">View Recovery Codes</span>
                                        <p class="text-sm text-gray-600">Access your backup codes for account recovery</p>
                                    </a>
                                    
                                    @if(!$isRequired)
                                    <form action="{{ route('2fa.disable') }}" method="POST" class="block p-3 border border-red-200 rounded-lg">
                                        @csrf
                                        <div class="space-y-3">
                                            <span class="font-medium text-red-600">Disable Two-Factor Authentication</span>
                                            <p class="text-sm text-gray-600">Turn off 2FA for your account (requires password)</p>
                                            <div class="flex space-x-2">
                                                <input type="password" name="password" placeholder="Your password" required class="flex-1 px-3 py-2 border border-gray-300 rounded-md text-sm">
                                                <button type="submit" class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-md hover:bg-red-700">Disable</button>
                                            </div>
                                        </div>
                                    </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </noscript>
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