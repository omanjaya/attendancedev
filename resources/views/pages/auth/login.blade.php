<x-guest-layout>
    <div class="space-y-6">
        <!-- Header -->
        <div class="text-center">
            <h2 class="text-xl font-semibold text-foreground">Welcome back</h2>
            <p class="text-sm text-muted-foreground mt-1">Sign in to your account</p>
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
                <x-ui.label for="email" value="{{ __('Email') }}" />
                <x-ui.input 
                    id="email" 
                    type="email" 
                    name="email" 
                    :value="old('email')" 
                    required 
                    autofocus 
                    autocomplete="username"
                    placeholder="Enter your email" />
                <x-ui.input-error :messages="$errors->get('email')" />
            </div>

            <!-- Password -->
            <div class="space-y-2">
                <x-ui.label for="password" value="{{ __('Password') }}" />
                <x-ui.input 
                    id="password" 
                    type="password" 
                    name="password" 
                    required 
                    autocomplete="current-password"
                    placeholder="Enter your password" />
                <x-ui.input-error :messages="$errors->get('password')" />
            </div>

            <!-- Remember Me -->
            <div class="flex items-center space-x-2">
                <x-ui.checkbox id="remember_me" name="remember" />
                <x-ui.label for="remember_me" value="{{ __('Remember me') }}" class="text-sm" />
            </div>

            <!-- Actions -->
            <div class="space-y-4">
                <x-ui.button type="submit" class="w-full">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m0 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    {{ __('Sign in') }}
                </x-ui.button>

                @if (Route::has('password.request'))
                    <div class="text-center">
                        <x-ui.button variant="link" href="{{ route('password.request') }}" class="text-sm">
                            {{ __('Forgot your password?') }}
                        </x-ui.button>
                    </div>
                @endif
            </div>
        </form>

        <!-- Register Link -->
        @if (Route::has('register'))
            <div class="text-center pt-6 border-t border-border">
                <p class="text-sm text-muted-foreground">
                    {{ __('Don\'t have an account?') }}
                    <x-ui.button variant="link" href="{{ route('register') }}" class="text-sm font-medium ml-1">
                        {{ __('Sign up') }}
                    </x-ui.button>
                </p>
            </div>
        @endif
    </div>
</x-guest-layout>
