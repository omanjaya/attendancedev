@extends('layouts.guest')

@section('title', 'Security Lockdown')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-red-50 to-red-100 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <div class="bg-white py-8 px-4 shadow-lg sm:rounded-lg sm:px-10">
            <!-- Header -->
            <div class="text-center mb-6">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 mb-4">
                    <svg class="h-8 w-8 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-900">Account Locked</h2>
                <p class="mt-2 text-gray-600">
                    Your account has been temporarily locked for security reasons.
                </p>
            </div>

            <!-- Security Alert -->
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">Security Protection Active</h3>
                        <div class="mt-2 text-sm text-red-700">
                            <p>{{ $message ?? 'Account locked due to repeated security violations. Administrator assistance required.' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Information Section -->
            <div class="space-y-4">
                <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                    <div class="flex items-center">
                        <svg class="h-5 w-5 text-amber-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                        <span class="text-sm font-medium text-amber-800">
                            Administrator intervention required
                        </span>
                    </div>
                    <p class="text-sm text-amber-700 mt-2">
                        This lockdown requires manual review by a system administrator before access can be restored.
                    </p>
                </div>

                <!-- What to do next -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h4 class="text-sm font-medium text-blue-800 mb-2">What should I do?</h4>
                    <div class="text-sm text-blue-700 space-y-2">
                        <div class="flex items-start">
                            <span class="inline-block w-2 h-2 bg-blue-400 rounded-full mt-2 mr-3 flex-shrink-0"></span>
                            <p>Contact your system administrator or IT support team</p>
                        </div>
                        <div class="flex items-start">
                            <span class="inline-block w-2 h-2 bg-blue-400 rounded-full mt-2 mr-3 flex-shrink-0"></span>
                            <p>Provide them with your user ID and the time this occurred</p>
                        </div>
                        <div class="flex items-start">
                            <span class="inline-block w-2 h-2 bg-blue-400 rounded-full mt-2 mr-3 flex-shrink-0"></span>
                            <p>Be prepared to verify your identity through alternative means</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact Information -->
            <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                <h4 class="text-sm font-medium text-gray-900 mb-2">Need immediate assistance?</h4>
                <div class="text-sm text-gray-600 space-y-1">
                    <p><strong>IT Support:</strong> Contact your system administrator</p>
                    <p><strong>Security Team:</strong> Available during business hours</p>
                    <p><strong>Emergency:</strong> Contact your organization's emergency IT line</p>
                </div>
            </div>

            <!-- Actions -->
            <div class="mt-6 space-y-3">
                <a 
                    href="{{ route('login') }}" 
                    class="w-full bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200 inline-flex items-center justify-center"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12" />
                    </svg>
                    Return to Login
                </a>

                @if(Route::has('password.request'))
                <a 
                    href="{{ route('password.request') }}" 
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200 inline-flex items-center justify-center"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                    </svg>
                    Reset Password
                </a>
                @endif
            </div>

            <!-- Security Information -->
            <div class="mt-6 text-center">
                <details class="text-left">
                    <summary class="text-sm text-gray-600 cursor-pointer hover:text-gray-900">
                        Why was my account locked?
                    </summary>
                    <div class="mt-3 text-sm text-gray-600 space-y-2">
                        <p>Account lockdowns are triggered by security systems to protect against:</p>
                        <ul class="list-disc list-inside ml-4 space-y-1">
                            <li>Multiple failed two-factor authentication attempts</li>
                            <li>Suspicious login patterns</li>
                            <li>Potential brute force attacks</li>
                            <li>Repeated security violations</li>
                        </ul>
                        <div class="mt-3 p-3 bg-yellow-50 border border-yellow-200 rounded">
                            <p class="text-yellow-800">
                                <strong>Important:</strong> This is an automated security measure designed to protect your account and our systems. 
                                Your account will be reviewed and unlocked by an administrator once security concerns are addressed.
                            </p>
                        </div>
                    </div>
                </details>
            </div>

            <!-- Incident Reference -->
            <div class="mt-4 text-center text-xs text-gray-500">
                <p>Incident Time: {{ now()->format('Y-m-d H:i:s') }} UTC</p>
                <p>Reference ID: {{ substr(md5(request()->ip() . now()->timestamp), 0, 8) }}</p>
            </div>
        </div>
    </div>
</div>
@endsection