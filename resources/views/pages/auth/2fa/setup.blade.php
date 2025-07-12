@extends('layouts.app')

@section('title', 'Two-Factor Authentication Setup')

@section('content')
<div class="page-body">
    <div class="max-w-7xl mx-auto px-8">
        <div class="flex justify-center">
            <div class="w-full max-w-2xl">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon mr-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M12 3a12 12 0 0 0 8.5 3a12 12 0 0 1 -8.5 15a12 12 0 0 1 -8.5 -15a12 12 0 0 0 8.5 -3"/>
                                <path d="M8 11a1 1 0 0 1 2 0v5a1 1 0 0 1 -2 0v-5z"/>
                                <path d="M14 11a1 1 0 0 1 2 0v5a1 1 0 0 1 -2 0v-5z"/>
                            </svg>
                            Set Up Two-Factor Authentication
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="steps">
                            <div class="step-item active" id="step1">
                                <div class="step-marker">1</div>
                                <div class="step-content">
                                    <h4 class="step-title">Install Authenticator App</h4>
                                    <p class="text-gray-600">Install a compatible authenticator app on your phone:</p>
                                    <div class="grid grid-cols-12 gap-4 mb-3">
                                        <div class="md:col-span-6">
                                            <div class="bg-white border border-gray-200 rounded-lg p-4">
                                                <div class="text-center">
                                                    <div class="text-green-600 mb-2">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 mx-auto" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                            <rect x="4" y="4" width="16" height="16" rx="2"/>
                                                        </svg>
                                                    </div>
                                                    <h5 class="font-medium text-gray-900">Google Authenticator</h5>
                                                    <p class="text-sm text-gray-600">Free • Recommended</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="md:col-span-6">
                                            <div class="bg-white border border-gray-200 rounded-lg p-4">
                                                <div class="text-center">
                                                    <div class="text-blue-600 mb-2">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 mx-auto" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                            <rect x="4" y="4" width="16" height="16" rx="2"/>
                                                        </svg>
                                                    </div>
                                                    <h5 class="font-medium text-gray-900">Microsoft Authenticator</h5>
                                                    <p class="text-sm text-gray-600">Free • Alternative</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="button" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" onclick="nextStep(2)">
                                        I have installed an app
                                    </button>
                                </div>
                            </div>

                            <div class="step-item" id="step2">
                                <div class="step-marker">2</div>
                                <div class="step-content">
                                    <h4 class="step-title">Scan QR Code</h4>
                                    <p class="text-gray-600">Open your authenticator app and scan this QR code:</p>
                                    
                                    <div class="text-center mb-6">
                                        <div class="inline-block bg-white border border-gray-200 rounded-lg p-4">
                                            <div id="qrcode-container">
                                                {!! $qrCode !!}
                                            </div>
                                        </div>
                                    </div>

                                    <div class="bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded-md">
                                        <div class="flex">
                                            <div class="flex-shrink-0">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                    <circle cx="12" cy="12" r="9"/>
                                                    <line x1="12" y1="8" x2="12.01" y2="8"/>
                                                    <polyline points="11,12 12,12 12,16 13,16"/>
                                                </svg>
                                            </div>
                                            <div class="ml-3">
                                                <h4 class="font-medium">Can't scan the QR code?</h4>
                                                <p class="mb-3">Manually enter this secret key into your authenticator app:</p>
                                                <div class="flex">
                                                    <input type="text" class="flex-1 px-3 py-2 border border-gray-300 rounded-l-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm font-mono" value="{{ $secretKey }}" readonly id="secretKey">
                                                    <button class="inline-flex items-center px-4 py-2 border border-gray-300 border-l-0 rounded-r-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition duration-150" type="button" onclick="copySecret()">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                            <rect x="9" y="9" width="12" height="12" rx="2"/>
                                                            <path d="M5 15h4v4h-4z"/>
                                                            <path d="M5 11h2v2h-2z"/>
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex gap-2">
                                        <button type="button" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition duration-150" onclick="prevStep(1)">
                                            Back
                                        </button>
                                        <button type="button" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" onclick="nextStep(3)">
                                            I've scanned the code
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="step-item" id="step3">
                                <div class="step-marker">3</div>
                                <div class="step-content">
                                    <h4 class="step-title">Verify Setup</h4>
                                    <p class="text-gray-600">Enter the 6-digit code from your authenticator app to complete setup:</p>
                                    
                                    <form id="enableForm" action="{{ route('2fa.enable') }}" method="POST">
                                        @csrf
                                        <div class="mb-3">
                                            <label class="block text-sm font-medium text-gray-700 required">Verification Code</label>
                                            <input type="text" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm text-center" name="code" id="verificationCode" 
                                                   placeholder="000000" maxlength="6" autocomplete="one-time-code" 
                                                   style="font-size: 1.5rem; letter-spacing: 0.3rem;" required>
                                            <div class="hidden mt-2 text-sm text-red-600" id="code-error"></div>
                                        </div>

                                        <div class="flex gap-2">
                                            <button type="button" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition duration-150" onclick="prevStep(2)">
                                                Back
                                            </button>
                                            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition duration-150" id="enableBtn">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon mr-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                    <path d="M5 12l5 5l10 -10"/>
                                                </svg>
                                                Enable Two-Factor Authentication
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <div class="step-item" id="step4" style="display: none;">
                                <div class="step-marker">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon text-success" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                        <path d="M5 12l5 5l10 -10"/>
                                    </svg>
                                </div>
                                <div class="step-content">
                                    <h4 class="step-title text-success">Setup Complete!</h4>
                                    <p class="text-gray-600">Two-factor authentication has been successfully enabled for your account.</p>
                                    
                                    <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded-md">
                                        <div class="flex">
                                            <div class="flex-shrink-0">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                    <circle cx="12" cy="12" r="9"/>
                                                    <line x1="12" y1="8" x2="12.01" y2="8"/>
                                                    <polyline points="11,12 12,12 12,16 13,16"/>
                                                </svg>
                                            </div>
                                            <div class="ml-3">
                                                <h4 class="font-medium">Save Your Recovery Codes</h4>
                                                <p class="mb-3">Please save these recovery codes in a safe place. You can use them to access your account if you lose your phone:</p>
                                                <div id="recovery-codes" class="font-mono bg-gray-100 p-3 rounded text-sm">
                                                    <!-- Recovery codes will be populated here -->
                                                </div>
                                                <button type="button" class="inline-flex items-center px-3 py-1.5 text-xs font-medium border border-blue-300 rounded text-blue-700 bg-white hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 mt-3" onclick="downloadRecoveryCodes()">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                                        <path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2"/>
                                                        <polyline points="7,11 12,16 17,11"/>
                                                        <line x1="12" y1="4" x2="12" y2="16"/>
                                                    </svg>
                                                    Download Codes
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex gap-2">
                                        <a href="{{ route('2fa.manage') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                            Manage 2FA Settings
                                        </a>
                                        <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition duration-150">
                                            Continue to Dashboard
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let recoveryCodes = [];

function nextStep(step) {
    // Hide all steps
    document.querySelectorAll('.step-item').forEach(item => {
        item.classList.remove('active');
    });
    
    // Show target step
    const targetStep = document.getElementById(`step${step}`);
    targetStep.classList.add('active');
    
    // Auto-focus relevant inputs
    if (step === 3) {
        document.getElementById('verificationCode').focus();
    }
}

function prevStep(step) {
    nextStep(step);
}

function copySecret() {
    const secretInput = document.getElementById('secretKey');
    secretInput.select();
    secretInput.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(secretInput.value);
    
    // Show feedback
    const copyBtn = event.target.closest('button');
    const originalText = copyBtn.innerHTML;
    copyBtn.innerHTML = '<span class="text-green-600">Copied!</span>';
    setTimeout(() => {
        copyBtn.innerHTML = originalText;
    }, 2000);
}

function downloadRecoveryCodes() {
    const codesText = recoveryCodes.join('\n');
    const blob = new Blob([`Two-Factor Authentication Recovery Codes\n\nGenerated: ${new Date().toLocaleString()}\nAccount: {{ auth()->user()->email }}\n\n${codesText}\n\nKeep these codes safe! You can use them to access your account if you lose your phone.`], {
        type: 'text/plain'
    });
    
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = '2fa-recovery-codes.txt';
    a.click();
    window.URL.revokeObjectURL(url);
}

document.addEventListener('DOMContentLoaded', function() {
    const enableForm = document.getElementById('enableForm');
    const verificationCode = document.getElementById('verificationCode');

    // Auto-submit when 6 digits are entered
    verificationCode.addEventListener('input', function() {
        const value = this.value.replace(/\D/g, ''); // Remove non-digits
        this.value = value;
        
        if (value.length === 6) {
            enableForm.dispatchEvent(new Event('submit'));
        }
    });

    enableForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const enableBtn = document.getElementById('enableBtn');
        const originalText = enableBtn.innerHTML;
        
        // Show loading state
        enableBtn.disabled = true;
        enableBtn.innerHTML = '<div class="inline-block animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>Enabling...';
        
        try {
            const response = await fetch(this.action, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: new URLSearchParams(new FormData(this))
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Store recovery codes
                recoveryCodes = data.recovery_codes;
                
                // Display recovery codes
                const recoveryCodesDiv = document.getElementById('recovery-codes');
                recoveryCodesDiv.innerHTML = data.recovery_codes.map(code => `<div>${code}</div>`).join('');
                
                // Show success step
                nextStep(4);
            } else {
                throw new Error(data.message || 'Setup failed');
            }
        } catch (error) {
            // Show error
            verificationCode.classList.add('border-red-300', 'text-red-900', 'focus:ring-red-500', 'focus:border-red-500');
            verificationCode.classList.remove('border-gray-300', 'focus:ring-blue-500', 'focus:border-blue-500');
            const feedback = document.getElementById('code-error');
            feedback.classList.remove('hidden');
            feedback.textContent = error.message || 'Invalid code. Please try again.';
            
            // Reset button
            enableBtn.disabled = false;
            enableBtn.innerHTML = originalText;
            
            // Clear and focus input
            verificationCode.value = '';
            verificationCode.focus();
            
            // Reset error state after a delay
            setTimeout(() => {
                verificationCode.classList.remove('border-red-300', 'text-red-900', 'focus:ring-red-500', 'focus:border-red-500');
                verificationCode.classList.add('border-gray-300', 'focus:ring-blue-500', 'focus:border-blue-500');
                feedback.classList.add('hidden');
            }, 3000);
        }
    });
});
</script>

<style>
.steps {
    position: relative;
}

.step-item {
    display: none;
    margin-bottom: 2rem;
}

.step-item.active {
    display: block;
}

.step-marker {
    width: 2rem;
    height: 2rem;
    background: #e9ecef;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    margin-bottom: 1rem;
    color: #6c757d;
}

.step-item.active .step-marker {
    background: #206bc4;
    color: white;
}

.step-title {
    color: #1e293b;
    margin-bottom: 0.5rem;
}

#qrcode-container svg {
    width: 200px;
    height: 200px;
}
</style>
@endsection