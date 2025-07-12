<x-guest-layout>
    <div class="space-y-6">
        <!-- Header -->
        <div class="text-center">
            <h2 class="text-xl font-semibold text-foreground">Reset password</h2>
            <p class="text-sm text-muted-foreground mt-1">Enter your email to receive a reset link</p>
        </div>

        <!-- Description -->
        <div class="text-center">
            <p class="text-sm text-muted-foreground leading-relaxed">
                {{ __('Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.') }}
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
                <x-ui.label for="email" value="{{ __('Email') }}" />
                <x-ui.input 
                    id="email" 
                    type="email" 
                    name="email" 
                    :value="old('email')" 
                    required 
                    autofocus
                    placeholder="Enter your email address" />
                <x-ui.input-error :messages="$errors->get('email')" />
            </div>

            <!-- Actions -->
            <div class="space-y-4">
                <x-ui.button type="submit" class="w-full">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 3.26a2 2 0 001.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    {{ __('Email Password Reset Link') }}
                </x-ui.button>
            </div>
        </form>

        <!-- Back to Login -->
        <div class="text-center pt-6 border-t border-border">
            <p class="text-sm text-muted-foreground">
                {{ __('Remember your password?') }}
                <x-ui.button variant="link" href="{{ route('login') }}" class="text-sm font-medium ml-1">
                    {{ __('Back to sign in') }}
                </x-ui.button>
            </p>
        </div>
    </div>
</x-guest-layout>
