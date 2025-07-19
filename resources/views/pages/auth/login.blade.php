<x-guest-layout>
    <div class="space-y-6">
        <!-- Header -->
        <div class="text-center">
            <h2 class="text-2xl font-bold text-slate-800 dark:text-white">{{ __('auth.messages.welcome_back') }}</h2>
            <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">{{ __('auth.messages.login_subtitle') }}</p>
        </div>

        <!-- Session Status -->
        @if (session('status'))
            <x-ui.alert variant="success">
                {{ session('status') }}
            </x-ui.alert>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf

            <!-- Email Address -->
            <div class="space-y-2">
                <x-ui.label for="email" value="{{ __('auth.labels.email') }}" class="text-slate-700 dark:text-slate-300" />
                <x-ui.input 
                    id="email" 
                    type="email" 
                    name="email" 
                    :value="old('email')" 
                    required 
                    autofocus 
                    autocomplete="username"
                    placeholder="{{ __('auth.placeholders.email') }}"
                    class="bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300" />
                @error('email')
                    <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Password -->
            <div class="space-y-2">
                <x-ui.label for="password" value="{{ __('auth.labels.password') }}" class="text-slate-700 dark:text-slate-300" />
                <x-ui.input 
                    id="password" 
                    type="password" 
                    name="password" 
                    required 
                    autocomplete="current-password"
                    placeholder="{{ __('auth.placeholders.password') }}"
                    class="bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300" />
                @error('password')
                    <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Remember Me -->
            <div class="flex items-center space-x-2">
                <x-ui.checkbox id="remember_me" name="remember" :checked="true" />
                <x-ui.label for="remember_me" value="{{ __('auth.labels.remember_me') }}" class="text-sm text-slate-700 dark:text-slate-300" />
            </div>

            <!-- Actions -->
            <div class="space-y-4">
                <x-ui.button type="submit" class="w-full group relative px-6 py-3 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-300 ease-out">
                    <div class="flex items-center justify-center space-x-2">
                        <svg class="w-5 h-5 group-hover:rotate-90 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m0 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        <span class="font-medium">{{ __('auth.buttons.login') }}</span>
                    </div>
                </x-ui.button>

                @if (Route::has('password.request'))
                    <div class="text-center">
                        <x-ui.button variant="link" href="{{ route('password.request') }}" class="text-sm text-slate-600 dark:text-slate-400 hover:text-blue-500 dark:hover:text-blue-400">
                            {{ __('auth.labels.forgot_password') }}
                        </x-ui.button>
                    </div>
                @endif
            </div>
        </form>

        <!-- Register Link -->
        @if (Route::has('register'))
            <div class="text-center pt-6 border-t border-white/20">
                <p class="text-sm text-slate-600 dark:text-slate-400">
                    {{ __('auth.labels.not_registered') }}
                    <x-ui.button variant="link" href="{{ route('register') }}" class="text-sm font-medium text-blue-500 dark:text-blue-400 hover:text-blue-600 dark:hover:text-blue-300 ml-1">
                        {{ __('auth.buttons.register') }}
                    </x-ui.button>
                </p>
            </div>
        @endif
    </div>
</x-guest-layout>