<x-guest-layout>
    <div class="space-y-6">
        <!-- Header -->
        <div class="text-center">
            <h2 class="text-xl font-semibold text-foreground">Create account</h2>
            <p class="text-sm text-muted-foreground mt-1">Get started with your attendance account</p>
        </div>

        <form method="POST" action="{{ route('register') }}" class="space-y-4">
            @csrf

            <!-- Name -->
            <div class="space-y-2">
                <x-ui.label for="name" value="{{ __('Full Name') }}" />
                <x-ui.input 
                    id="name" 
                    type="text" 
                    name="name" 
                    :value="old('name')" 
                    required 
                    autofocus 
                    autocomplete="name"
                    placeholder="Enter your full name" />
                <x-ui.input-error :messages="$errors->get('name')" />
            </div>

            <!-- Email Address -->
            <div class="space-y-2">
                <x-ui.label for="email" value="{{ __('Email') }}" />
                <x-ui.input 
                    id="email" 
                    type="email" 
                    name="email" 
                    :value="old('email')" 
                    required 
                    autocomplete="username"
                    placeholder="Enter your email address" />
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
                    autocomplete="new-password"
                    placeholder="Create a secure password" />
                <x-ui.input-error :messages="$errors->get('password')" />
            </div>

            <!-- Confirm Password -->
            <div class="space-y-2">
                <x-ui.label for="password_confirmation" value="{{ __('Confirm Password') }}" />
                <x-ui.input 
                    id="password_confirmation" 
                    type="password" 
                    name="password_confirmation" 
                    required 
                    autocomplete="new-password"
                    placeholder="Confirm your password" />
                <x-ui.input-error :messages="$errors->get('password_confirmation')" />
            </div>

            <!-- Actions -->
            <div class="space-y-4">
                <x-ui.button type="submit" class="w-full">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    {{ __('Create account') }}
                </x-ui.button>
            </div>
        </form>

        <!-- Login Link -->
        <div class="text-center pt-6 border-t border-border">
            <p class="text-sm text-muted-foreground">
                {{ __('Already have an account?') }}
                <x-ui.button variant="link" href="{{ route('login') }}" class="text-sm font-medium ml-1">
                    {{ __('Sign in') }}
                </x-ui.button>
            </p>
        </div>
    </div>
</x-guest-layout>
