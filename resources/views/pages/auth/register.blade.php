<x-guest-layout>
    <div class="space-y-6">
        <!-- Header -->
        <div class="text-center">
            <h2 class="text-2xl font-bold text-slate-800 dark:text-white">{{ __('auth.title.register') }}</h2>
            <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">{{ __('auth.messages.register_subtitle') }}</p>
        </div>

        <form method="POST" action="{{ route('register') }}" class="space-y-4">
            @csrf

            <!-- Name -->
            <div class="space-y-2">
                <x-ui.label for="name" value="{{ __('auth.labels.name') }}" class="text-slate-700 dark:text-slate-300" />
                <x-ui.input 
                    id="name" 
                    type="text" 
                    name="name" 
                    :value="old('name')" 
                    required 
                    autofocus 
                    autocomplete="name"
                    placeholder="{{ __('auth.placeholders.name') }}"
                    class="bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300" />
                @error('name')
                    <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Email Address -->
            <div class="space-y-2">
                <x-ui.label for="email" value="{{ __('auth.labels.email') }}" class="text-slate-700 dark:text-slate-300" />
                <x-ui.input 
                    id="email" 
                    type="email" 
                    name="email" 
                    :value="old('email')" 
                    required 
                    autocomplete="username"
                    placeholder="{{ __('auth.placeholders.email') }}"
                    class="bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300" />
                @error('email')
                    <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Password -->
            <div class="space-y-2">
                <x-ui.label for="password" value="{{ __('Kata Sandi') }}" class="text-slate-700 dark:text-slate-300" />
                <x-ui.input 
                    id="password" 
                    type="password" 
                    name="password" 
                    required 
                    autocomplete="new-password"
                    placeholder="Buat kata sandi yang aman"
                    class="bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300" />
                @error('password')
                    <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Confirm Password -->
            <div class="space-y-2">
                <x-ui.label for="password_confirmation" value="{{ __('Konfirmasi Kata Sandi') }}" class="text-slate-700 dark:text-slate-300" />
                <x-ui.input 
                    id="password_confirmation" 
                    type="password" 
                    name="password_confirmation" 
                    required 
                    autocomplete="new-password"
                    placeholder="Konfirmasi kata sandi Anda"
                    class="bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300" />
                @error('password_confirmation')
                    <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Actions -->
            <div class="space-y-4">
                <x-ui.button type="submit" class="w-full group relative px-6 py-3 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-300 ease-out">
                    <div class="flex items-center justify-center space-x-2">
                        <svg class="w-5 h-5 group-hover:rotate-90 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        <span class="font-medium">{{ __('Buat Akun') }}</span>
                    </div>
                </x-ui.button>
            </div>
        </form>

        <!-- Login Link -->
        <div class="text-center pt-6 border-t border-white/20">
            <p class="text-sm text-slate-600 dark:text-slate-400">
                {{ __('Sudah punya akun?') }}
                <x-ui.button variant="link" href="{{ route('login') }}" class="text-sm font-medium text-blue-500 dark:text-blue-400 hover:text-blue-600 dark:hover:text-blue-300 ml-1">
                    {{ __('Masuk') }}
                </x-ui.button>
            </p>
        </div>
    </div>
</x-guest-layout>