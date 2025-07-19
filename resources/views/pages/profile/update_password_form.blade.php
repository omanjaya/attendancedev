<section>
    <header>
        <h2 class="text-xl font-semibold text-slate-800 dark:text-white">
            {{ __('Perbarui Kata Sandi') }}
        </h2>

        <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
            {{ __('Pastikan akun Anda menggunakan kata sandi yang panjang dan acak agar tetap aman.') }}
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('put')

        <div>
            <x-ui.label for="update_password_current_password" value="{{ __('Kata Sandi Saat Ini') }}" class="text-slate-700 dark:text-slate-300" />
            <x-ui.input id="update_password_current_password" name="current_password" type="password" 
                        class="mt-1 block w-full bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300" autocomplete="current-password" />
            @error('current_password', 'updatePassword')
                <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <x-ui.label for="update_password_password" value="{{ __('Kata Sandi Baru') }}" class="text-slate-700 dark:text-slate-300" />
            <x-ui.input id="update_password_password" name="password" type="password" 
                        class="mt-1 block w-full bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300" autocomplete="new-password" />
            @error('password', 'updatePassword')
                <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <x-ui.label for="update_password_password_confirmation" value="{{ __('Konfirmasi Kata Sandi') }}" class="text-slate-700 dark:text-slate-300" />
            <x-ui.input id="update_password_password_confirmation" name="password_confirmation" type="password" 
                        class="mt-1 block w-full bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300" autocomplete="new-password" />
            @error('password_confirmation', 'updatePassword')
                <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center gap-4">
            <x-ui.button type="submit" variant="primary">
                <svg class="w-5 h-5 mr-2 group-hover:rotate-90 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                {{ __('Simpan') }}
            </x-ui.button>

            @if (session('status') === 'password-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-slate-600 dark:text-slate-400"
                >{{ __('Tersimpan.') }}</p>
            @endif
        </div>
    </form>
</section>