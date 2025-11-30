<x-guest-layout>
    <div class="space-y-6">
        <!-- Header -->
        <div class="text-center">
            <h2 class="text-2xl font-bold text-slate-800 dark:text-white">Reset Kata Sandi</h2>
            <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">Masukkan email Anda untuk menerima link reset</p>
        </div>

        <!-- Description -->
        <div class="text-center">
            <p class="text-sm text-slate-600 dark:text-slate-400 leading-relaxed">
                {{ __('Lupa kata sandi? Tidak masalah. Cukup berikan alamat email Anda dan kami akan mengirimkan link reset kata sandi yang memungkinkan Anda memilih yang baru.') }}
            </p>
        </div>

        <!-- Session Status -->
        @if (session('status'))
            <x-ui.alert variant="success">
                {{ session('status') }}
            </x-ui.alert>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
            @csrf

            <!-- Email Address -->
            <div class="space-y-2">
                <x-ui.label for="email" value="{{ __('Email') }}" class="text-slate-700 dark:text-slate-300" />
                <x-ui.input 
                    id="email" 
                    type="email" 
                    name="email" 
                    :value="old('email')" 
                    required 
                    autofocus
                    placeholder="Masukkan alamat email Anda"
                    class="bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300" />
                @error('email')
                    <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Actions -->
            <div class="space-y-4">
                <x-ui.button type="submit" class="w-full group relative px-6 py-3 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-300 ease-out">
                    <div class="flex items-center justify-center space-x-2">
                        <svg class="w-5 h-5 group-hover:rotate-90 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 3.26a2 2 0 001.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <span class="font-medium">{{ __('Kirim Link Reset Kata Sandi') }}</span>
                    </div>
                </x-ui.button>
            </div>
        </form>

        <!-- Back to Login -->
        <div class="text-center pt-6 border-t border-white/20">
            <p class="text-sm text-slate-600 dark:text-slate-400">
                {{ __('Ingat kata sandi Anda?') }}
                <x-ui.button variant="link" href="{{ route('login') }}" class="text-sm font-medium text-blue-500 dark:text-blue-400 hover:text-blue-600 dark:hover:text-blue-300 ml-1">
                    {{ __('Kembali ke masuk') }}
                </x-ui.button>
            </p>
        </div>
    </div>
</x-guest-layout>