<x-guest-layout>
    <div class="space-y-6">
        <!-- Header -->
        <div class="text-center">
            <h2 class="text-xl font-semibold text-foreground">Confirm password</h2>
            <p class="text-sm text-muted-foreground mt-1">Please verify your identity</p>
        </div>

        <!-- Security Notice -->
        <div class="bg-warning/10 border border-warning/20 rounded-lg p-4">
            <div class="flex items-start">
                <svg class="w-5 h-5 text-warning mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                </svg>
                <div>
                    <h3 class="text-sm font-medium text-warning">Secure Area</h3>
                    <p class="text-sm text-warning/80 mt-1">
                        {{ __('This is a secure area of the application. Please confirm your password before continuing.') }}
                    </p>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('password.confirm') }}" class="space-y-4">
            @csrf

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

            <!-- Actions -->
            <div class="space-y-4">
                <x-ui.button type="submit" class="w-full">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    {{ __('Confirm Password') }}
                </x-ui.button>
            </div>
        </form>

        <!-- Back to Dashboard -->
        <div class="text-center pt-6 border-t border-border">
            <p class="text-sm text-muted-foreground">
                {{ __('Want to go back?') }}
                <x-ui.button variant="link" href="{{ route('dashboard') }}" class="text-sm font-medium ml-1">
                    {{ __('Return to dashboard') }}
                </x-ui.button>
            </p>
        </div>
    </div>
</x-guest-layout>
