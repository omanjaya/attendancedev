<x-guest-layout>
    <div class="space-y-6">
        <!-- Header -->
        <div class="text-center">
            <h2 class="text-2xl font-bold text-slate-800 dark:text-white">Konfirmasi Kata Sandi</h2>
            <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">Mohon verifikasi identitas Anda</p>
        </div>

        <!-- Security Notice -->
        <div class="bg-amber-50/20 backdrop-blur-sm border border-amber-500/30 rounded-xl p-4 shadow-lg">
            <div class="flex items-start">
                <svg class="w-5 h-5 text-amber-500 mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                </svg>
                <div>
                    <h3 class="text-sm font-medium text-amber-800 dark:text-amber-200">Area Aman</h3>
                    <p class="text-sm text-amber-700 dark:text-amber-300 mt-1">
                        {{ __('This is a secure area of the application. Please confirm your password before continuing.') }}
                    </p>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('password.confirm') }}" class="space-y-4">
            @csrf

            <!-- Password -->
            <div class="space-y-2">
                <x-ui.label for="password" value="{{ __('Kata Sandi') }}" class="text-slate-700 dark:text-slate-300" />
                <x-ui.input 
                    id="password" 
                    type="password" 
                    name="password" 
                    required 
                    autocomplete="current-password"
                    placeholder="Masukkan kata sandi Anda"
                    class="bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300" />
                @error('password')
                    <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Actions -->
            <div class="space-y-4">
                <x-ui.button type="submit" class="w-full group relative px-6 py-3 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-300 ease-out">
                    <div class="flex items-center justify-center space-x-2">
                        <svg class="w-5 h-5 group-hover:rotate-90 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="font-medium">{{ __('Konfirmasi Kata Sandi') }}</span>
                    </div>
                </x-ui.button>
            </div>
        </form>

        <!-- Back to Dashboard -->
        <div class="text-center pt-6 border-t border-white/20">
            <p class="text-sm text-slate-600 dark:text-slate-400">
                {{ __('Ingin kembali?') }}
                <x-ui.button variant="link" href="{{ route('dashboard') }}" class="text-sm font-medium text-blue-500 dark:text-blue-400 hover:text-blue-600 dark:hover:text-blue-300 ml-1">
                    {{ __('Kembali ke dashboard') }}
                </x-ui.button>
            </p>
        </div>
    </div>
</x-guest-layout>