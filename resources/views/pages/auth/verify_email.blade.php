<x-guest-layout>
    <div class="space-y-6">
        <!-- Header -->
        <div class="text-center">
            <h2 class="text-2xl font-bold text-slate-800 dark:text-white">Verifikasi Email</h2>
            <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">Periksa kotak masuk Anda untuk verifikasi</p>
        </div>

        <!-- Email Verification Notice -->
        <div class="bg-blue-50/20 backdrop-blur-sm border border-blue-500/30 rounded-xl p-4 shadow-lg">
            <div class="flex items-start">
                <svg class="w-5 h-5 text-blue-500 mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 3.26a2 2 0 001.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                <div>
                    <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">Verifikasi Email Diperlukan</h3>
                    <p class="text-sm text-blue-700 dark:text-blue-300 mt-1">
                        {{ __('Terima kasih telah mendaftar! Sebelum memulai, bisakah Anda memverifikasi alamat email Anda dengan mengklik tautan yang baru saja kami kirimkan? Jika Anda tidak menerima email, kami akan dengan senang hati mengirimkan yang lain.') }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Status Message -->
        @if (session('status') == 'verification-link-sent')
            <x-ui.alert variant="success">
                {{ __('Link verifikasi baru telah dikirim ke alamat email yang Anda berikan saat pendaftaran.') }}
            </x-ui.alert>
        @endif

        <!-- Actions -->
        <div class="space-y-4">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <x-ui.button type="submit" class="w-full group relative px-6 py-3 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-300 ease-out">
                    <div class="flex items-center justify-center space-x-2">
                        <svg class="w-5 h-5 group-hover:rotate-90 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        <span class="font-medium">{{ __('Kirim Ulang Email Verifikasi') }}</span>
                    </div>
                </x-ui.button>
            </form>
        </div>

        <!-- Sign Out -->
        <div class="text-center pt-6 border-t border-white/20">
            <p class="text-sm text-slate-600 dark:text-slate-400">
                {{ __('Ingin menggunakan akun yang berbeda?') }}
                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <x-ui.button variant="link" type="submit" class="text-sm font-medium text-blue-500 dark:text-blue-400 hover:text-blue-600 dark:hover:text-blue-300 ml-1">
                        {{ __('Keluar') }}
                    </x-ui.button>
                </form>
            </p>
        </div>
    </div>
</x-guest-layout>