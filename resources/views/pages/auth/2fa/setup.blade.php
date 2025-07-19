@extends('layouts.authenticated-unified')

@section('title', 'Pengaturan Otentikasi Dua Faktor')

@section('page-content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100 dark:from-slate-900 dark:via-blue-900 dark:to-indigo-900">
    <div class="p-6 lg:p-8">
        <x-layouts.base-page
            title="Pengaturan Otentikasi Dua Faktor"
            subtitle="Tambahkan lapisan keamanan ekstra ke akun Anda"
            :breadcrumbs="[
                ['label' => 'Dashboard', 'url' => route('dashboard')],
                ['label' => 'Keamanan', 'url' => route('security.dashboard')],
                ['label' => 'Pengaturan 2FA']
            ]">
        </x-layouts.base-page>

        <div class="flex justify-center">
            <div class="w-full max-w-2xl">
                <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                    <div class="steps" x-data="{ currentStep: 1 }">
                        <!-- Step Indicators -->
                        <div class="flex justify-between mb-8">
                            <template x-for="stepNum in 4" :key="stepNum">
                                <div class="flex flex-col items-center">
                                    <div class="w-10 h-10 rounded-full flex items-center justify-center text-lg font-bold transition-all duration-300"
                                         :class="currentStep >= stepNum ? 'bg-gradient-to-br from-blue-500 to-purple-600 text-white shadow-lg' : 'bg-white/20 backdrop-blur-sm border border-white/30 text-slate-600 dark:text-slate-300'">
                                        <span x-text="stepNum"></span>
                                    </div>
                                    <div class="text-sm text-slate-600 dark:text-slate-400 mt-2" x-text="['Instal Aplikasi', 'Pindai Kode QR', 'Verifikasi Pengaturan', 'Selesai!'][stepNum - 1]"></div>
                                </div>
                            </template>
                        </div>

                        <!-- Step 1: Install Authenticator App -->
                        <div class="step-item" x-show="currentStep === 1">
                            <h4 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">1. Instal Aplikasi Authenticator</h4>
                            <p class="text-slate-600 dark:text-slate-400 mb-6">Instal aplikasi authenticator yang kompatibel di ponsel Anda:</p>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                                <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-4 shadow-lg hover:shadow-xl transition-all duration-300 ease-out hover:bg-white/30 hover:scale-105">
                                    <div class="text-center">
                                        <div class="text-green-500 mb-2 w-12 h-12 mx-auto flex items-center justify-center rounded-full bg-green-500/20">
                                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 1.657 3.582 3 8 3s8-1.343 8-3 3.582-3 8-3 8 1.343 8 3v12c0 1.657-3.582 3-8 3s-8-1.343-8-3V6z"/></svg>
                                        </div>
                                        <h5 class="font-semibold text-slate-800 dark:text-white">Google Authenticator</h5>
                                        <p class="text-sm text-slate-600 dark:text-slate-400">Gratis • Direkomendasikan</p>
                                    </div>
                                </div>
                                <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-4 shadow-lg hover:shadow-xl transition-all duration-300 ease-out hover:bg-white/30 hover:scale-105">
                                    <div class="text-center">
                                        <div class="text-blue-500 mb-2 w-12 h-12 mx-auto flex items-center justify-center rounded-full bg-blue-500/20">
                                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 1.657 3.582 3 8 3s8-1.343 8-3 3.582-3 8-3 8 1.343 8 3v12c0 1.657-3.582 3-8 3s-8-1.343-8-3V6z"/></svg>
                                        </div>
                                        <h5 class="font-semibold text-slate-800 dark:text-white">Microsoft Authenticator</h5>
                                        <p class="text-sm text-slate-600 dark:text-slate-400">Gratis • Alternatif</p>
                                    </div>
                                </div>
                            </div>
                            <x-ui.button type="button" variant="primary" @click="currentStep = 2">
                                Saya sudah menginstal aplikasi
                            </x-ui.button>
                        </div>

                        <!-- Step 2: Scan QR Code -->
                        <div class="step-item" x-show="currentStep === 2">
                            <h4 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">2. Pindai Kode QR</h4>
                            <p class="text-slate-600 dark:text-slate-400 mb-6">Buka aplikasi authenticator Anda dan pindai kode QR ini:</p>
                            
                            <div class="text-center mb-6">
                                <div class="inline-block bg-white/30 backdrop-blur-sm border border-white/40 rounded-2xl p-4 shadow-lg">
                                    <div id="qrcode-container">
                                        {!! $qrCode !!}
                                    </div>
                                </div>
                            </div>

                            <div class="bg-blue-500/20 backdrop-blur-sm border border-blue-500/30 text-blue-800 dark:text-blue-200 px-4 py-3 rounded-lg mb-6">
                                <div class="flex items-start">
                                    <svg class="w-5 h-5 text-blue-500 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <div>
                                        <h4 class="font-medium">Tidak bisa memindai kode QR?</h4>
                                        <p class="mb-3">Masukkan kunci rahasia ini secara manual ke aplikasi authenticator Anda:</p>
                                        <div class="flex">
                                            <x-ui.input type="text" value="{{ $secretKey }}" readonly id="secretKey" class="flex-1 font-mono bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300" />
                                            <x-ui.button type="button" variant="secondary" onclick="copySecret()" class="rounded-l-none">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1.586M15 10l4-4m0 0l-4-4m4 4H9"/></svg>
                                            </x-ui.button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex gap-2">
                                <x-ui.button type="button" variant="secondary" @click="currentStep = 1">
                                    Kembali
                                </x-ui.button>
                                <x-ui.button type="button" variant="primary" @click="currentStep = 3">
                                    Saya sudah memindai kode
                                </x-ui.button>
                            </div>
                        </div>

                        <!-- Step 3: Verify Setup -->
                        <div class="step-item" x-show="currentStep === 3">
                            <h4 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">3. Verifikasi Pengaturan</h4>
                            <p class="text-slate-600 dark:text-slate-400 mb-6">Masukkan kode 6 digit dari aplikasi authenticator Anda untuk menyelesaikan pengaturan:</p>
                            
                            <form id="enableForm" action="{{ route('2fa.enable') }}" method="POST">
                                @csrf
                                <div class="mb-6">
                                    <x-ui.label for="verificationCode" class="text-slate-700 dark:text-slate-300">Kode Verifikasi</x-ui.label>
                                    <x-ui.input type="text" name="code" id="verificationCode" 
                                               placeholder="000000" maxlength="6" autocomplete="one-time-code" 
                                               class="w-full text-center text-4xl font-mono tracking-widest bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300" 
                                               style="font-size: 2.5rem; letter-spacing: 0.5rem;" required />
                                    <div class="hidden mt-2 text-sm text-red-500" id="code-error"></div>
                                </div>

                                <div class="flex gap-2">
                                    <x-ui.button type="button" variant="secondary" @click="currentStep = 2">
                                        Kembali
                                    </x-ui.button>
                                    <x-ui.button type="submit" variant="primary" id="enableBtn">
                                        <svg class="w-5 h-5 mr-2 group-hover:rotate-90 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12l5 5l10 -10"/></svg>
                                        Aktifkan Otentikasi Dua Faktor
                                    </x-ui.button>
                                </div>
                            </form>
                        </div>

                        <!-- Step 4: Setup Complete! -->
                        <div class="step-item" x-show="currentStep === 4">
                            <h4 class="text-xl font-semibold text-slate-800 dark:text-white mb-4 flex items-center">
                                <svg class="w-6 h-6 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12l5 5l10 -10"/></svg>
                                Pengaturan Selesai!
                            </h4>
                            <p class="text-slate-600 dark:text-slate-400 mb-6">Otentikasi dua faktor telah berhasil diaktifkan untuk akun Anda.</p>
                            
                            <div class="bg-amber-500/20 backdrop-blur-sm border border-amber-500/30 text-amber-800 dark:text-amber-200 px-4 py-3 rounded-lg mb-6">
                                <div class="flex items-start">
                                    <svg class="w-5 h-5 text-amber-500 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <div>
                                        <h4 class="font-medium">Simpan Kode Pemulihan Anda</h4>
                                        <p class="mb-3">Mohon simpan kode pemulihan ini di tempat yang aman. Anda dapat menggunakannya untuk mengakses akun Anda jika Anda kehilangan ponsel:</p>
                                        <div id="recovery-codes" class="font-mono bg-white/10 p-3 rounded-lg text-sm text-slate-800 dark:text-white"></div>
                                        <x-ui.button type="button" variant="secondary" onclick="downloadRecoveryCodes()" class="mt-3">
                                            <svg class="w-5 h-5 mr-2 group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 17v2a2 2 0 002 2h12a2 2 0 002 -2v-2"/><polyline points="7,11 12,16 17,11"/><line x1="12" y1="4" x2="12" y2="16"/></svg>
                                            Unduh Kode
                                        </x-ui.button>
                                    </div>
                                </div>
                            </div>

                            <div class="flex gap-2">
                                <x-ui.button href="{{ route('2fa.manage') }}" variant="primary">
                                    Kelola Pengaturan 2FA
                                </x-ui.button>
                                <x-ui.button href="{{ route('dashboard') }}" variant="secondary">
                                    Lanjutkan ke Dashboard
                                </x-ui.button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let recoveryCodes = [];

function nextStep(step) {
    // Hide all steps
    document.querySelectorAll('.step-item').forEach(item => {
        item.style.display = 'none';
    });
    
    // Show target step
    const targetStep = document.querySelector(`.step-item:nth-child(${step})`);
    if (targetStep) {
        targetStep.style.display = 'block';
    }
    
    // Update step indicators
    document.querySelectorAll('.steps > div:first-child > div').forEach((indicator, index) => {
        if (index + 1 <= step) {
            indicator.classList.add('bg-gradient-to-br', 'from-blue-500', 'to-purple-600', 'text-white', 'shadow-lg');
            indicator.classList.remove('bg-white/20', 'backdrop-blur-sm', 'border', 'border-white/30', 'text-slate-600', 'dark:text-slate-300');
        } else {
            indicator.classList.remove('bg-gradient-to-br', 'from-blue-500', 'to-purple-600', 'text-white', 'shadow-lg');
            indicator.classList.add('bg-white/20', 'backdrop-blur-sm', 'border', 'border-white/30', 'text-slate-600', 'dark:text-slate-300');
        }
    });
    
    // Auto-focus relevant inputs
    if (step === 3) {
        document.getElementById('verificationCode').focus();
    }
}

function copySecret() {
    const secretInput = document.getElementById('secretKey');
    secretInput.select();
    secretInput.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(secretInput.value);
    
    // Show feedback
    const copyBtn = event.target.closest('button');
    const originalText = copyBtn.innerHTML;
    copyBtn.innerHTML = '<span class="text-green-600">Disalin!</span>';
    setTimeout(() => {
        copyBtn.innerHTML = originalText;
    }, 2000);
}

function downloadRecoveryCodes() {
    const codesText = recoveryCodes.join('\n');
    const blob = new Blob([`Kode Pemulihan Otentikasi Dua Faktor\n\nDibuat: ${new Date().toLocaleString()}\nAkun: {{ auth()->user()->email }}\n\n${codesText}\n\nSimpan kode ini di tempat yang aman! Anda dapat menggunakannya untuk mengakses akun Anda jika Anda kehilangan ponsel.`], {
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
        enableBtn.innerHTML = '<div class="inline-block animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>Mengaktifkan...';
        
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
                throw new Error(data.message || 'Pengaturan gagal');
            }
        } catch (error) {
            // Show error
            verificationCode.classList.add('border-red-500', 'text-red-500', 'focus:ring-red-500', 'focus:border-red-500');
            verificationCode.classList.remove('border-white/40', 'focus:ring-blue-500/50', 'focus:border-blue-500/50');
            const feedback = document.getElementById('code-error');
            feedback.classList.remove('hidden');
            feedback.textContent = error.message || 'Kode tidak valid. Mohon coba lagi.';
            
            // Reset button
            enableBtn.disabled = false;
            enableBtn.innerHTML = originalText;
            
            // Clear and focus input
            verificationCode.value = '';
            verificationCode.focus();
            
            // Reset error state after a delay
            setTimeout(() => {
                verificationCode.classList.remove('border-red-500', 'text-red-500', 'focus:ring-red-500', 'focus:border-red-500');
                verificationCode.classList.add('border-white/40', 'focus:ring-blue-500/50', 'focus:border-blue-500/50');
                feedback.classList.add('hidden');
            }, 3000);
        }
    });
});
</script>
@endpush
