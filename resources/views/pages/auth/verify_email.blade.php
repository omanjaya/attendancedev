<x-guest-layout>
    <div class="space-y-6">
        <!-- Header -->
        <div class="text-center">
            <h2 class="text-xl font-semibold text-foreground">Verify email</h2>
            <p class="text-sm text-muted-foreground mt-1">Check your inbox for verification</p>
        </div>

        <!-- Email Verification Notice -->
        <div class="bg-info/10 border border-info/20 rounded-lg p-4">
            <div class="flex items-start">
                <svg class="w-5 h-5 text-info mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 3.26a2 2 0 001.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                <div>
                    <h3 class="text-sm font-medium text-info">Email Verification Required</h3>
                    <p class="text-sm text-info/80 mt-1">
                        {{ __('Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.') }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Status Message -->
        @if (session('status') == 'verification-link-sent')
            <x-ui.alert variant="success">
                {{ __('A new verification link has been sent to the email address you provided during registration.') }}
            </x-ui.alert>
        @endif

        <!-- Actions -->
        <div class="space-y-4">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <x-ui.button type="submit" class="w-full">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    {{ __('Resend Verification Email') }}
                </x-ui.button>
            </form>
        </div>

        <!-- Sign Out -->
        <div class="text-center pt-6 border-t border-border">
            <p class="text-sm text-muted-foreground">
                {{ __('Want to use a different account?') }}
                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <x-ui.button variant="link" type="submit" class="text-sm font-medium ml-1">
                        {{ __('Sign out') }}
                    </x-ui.button>
                </form>
            </p>
        </div>
    </div>
</x-guest-layout>
