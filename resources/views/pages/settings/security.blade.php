@extends('layouts.app')

@section('title', 'Security Settings')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="max-w-4xl mx-auto">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Security Settings</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Manage your account security preferences and trusted devices.
            </p>
        </div>

        <div class="space-y-6">
            <!-- Two-Factor Authentication -->
            <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white">
                        Two-Factor Authentication
                    </h3>
                    <div class="mt-2 max-w-xl text-sm text-gray-500 dark:text-gray-400">
                        <p>
                            Add an extra layer of security to your account by requiring a verification code in addition to your password.
                        </p>
                    </div>
                    
                    <div class="mt-5">
                        @if(auth()->user()->hasTwoFactorEnabled())
                            <div class="flex items-center">
                                <svg class="h-5 w-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                <span class="ml-2 text-sm font-medium text-green-700 dark:text-green-400">
                                    Two-factor authentication is enabled
                                </span>
                            </div>
                            <div class="mt-4 flex space-x-3">
                                <a href="{{ route('2fa.show') }}" 
                                   class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    Manage 2FA
                                </a>
                            </div>
                        @else
                            <div class="flex items-center">
                                <svg class="h-5 w-5 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                </svg>
                                <span class="ml-2 text-sm font-medium text-yellow-700 dark:text-yellow-400">
                                    Two-factor authentication is not enabled
                                </span>
                            </div>
                            <div class="mt-4">
                                <a href="{{ route('2fa.setup') }}" 
                                   class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    Enable Two-Factor Authentication
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Device Management -->
            <div id="device-management">
                <device-management></device-management>
            </div>

            <!-- Notification Preferences -->
            <div id="notification-preferences">
                <notification-preferences></notification-preferences>
            </div>

            <!-- Login Activity -->
            <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white">
                        Recent Login Activity
                    </h3>
                    <div class="mt-2 max-w-xl text-sm text-gray-500 dark:text-gray-400">
                        <p>
                            Review your recent login activity and watch for any suspicious activity.
                        </p>
                    </div>
                    
                    <div class="mt-5">
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                                        Last login: {{ auth()->user()->last_login_at ? auth()->user()->last_login_at->format('M j, Y \a\t g:i A') : 'Never' }}
                                    </p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        IP: {{ auth()->user()->last_login_ip ?? 'Unknown' }}
                                    </p>
                                </div>
                                <div>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200">
                                        Current Session
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Password Change -->
            <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white">
                        Password
                    </h3>
                    <div class="mt-2 max-w-xl text-sm text-gray-500 dark:text-gray-400">
                        <p>
                            Ensure your account is using a long, random password to stay secure.
                        </p>
                    </div>
                    
                    <div class="mt-5">
                        <a href="{{ route('password.request') }}" 
                           class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Change Password
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script type="module">
import { createApp } from 'vue'
import DeviceManagement from '@/components/DeviceManagement.vue'
import NotificationPreferences from '@/components/NotificationPreferences.vue'

// Mount device management component
const deviceApp = createApp({
    components: {
        DeviceManagement
    }
})
deviceApp.mount('#device-management')

// Mount notification preferences component
const notificationApp = createApp({
    components: {
        NotificationPreferences
    }
})
notificationApp.mount('#notification-preferences')
</script>
@endpush