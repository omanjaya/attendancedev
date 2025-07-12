<x-guest-layout>
    <div class="space-y-6">
        <!-- Header -->
        <div class="text-center">
            <h2 class="text-xl font-semibold text-foreground">Reset password</h2>
            <p class="text-sm text-muted-foreground mt-1">Enter your new password</p>
        </div>

        <form method="POST" action="{{ route('password.store') }}" class="space-y-4">
            @csrf

            <!-- Password Reset Token -->
            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <!-- Email Address -->
            <div class="space-y-2">
                <x-ui.label for="email" value="{{ __('Email') }}" />
                <x-ui.input 
                    id="email" 
                    type="email" 
                    name="email" 
                    :value="old('email', $request->email)" 
                    required 
                    autofocus 
                    autocomplete="username"
                    placeholder="Your email address" />
                <x-ui.input-error :messages="$errors->get('email')" />
            </div>

            <!-- Password -->
            <div class="space-y-2">
                <x-ui.label for="password" value="{{ __('New Password') }}" />
                <x-ui.input 
                    id="password" 
                    type="password" 
                    name="password" 
                    required 
                    autocomplete="new-password"
                    placeholder="Create a new password" />
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
                    placeholder="Confirm your new password" />
                <x-ui.input-error :messages="$errors->get('password_confirmation')" />
            </div>

            <!-- Actions -->
            <div class="space-y-4">
                <x-ui.button type="submit" class="w-full">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    {{ __('Reset Password') }}
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
