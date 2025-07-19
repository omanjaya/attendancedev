<section class="space-y-6">
    <header>
        <h2 class="text-xl font-semibold text-slate-800 dark:text-white">
            {{ __('Hapus Akun') }}
        </h2>

        <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
            {{ __('Setelah akun Anda dihapus, semua sumber daya dan datanya akan dihapus secara permanen. Sebelum menghapus akun Anda, mohon unduh data atau informasi apa pun yang ingin Anda simpan.') }}
        </p>
    </header>

    <x-ui.button
        variant="destructive"
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >
        <svg class="w-5 h-5 mr-2 group-hover:rotate-90 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
        {{ __('Hapus Akun') }}
    </x-ui.button>

    <x-ui.modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6 space-y-6">
            @csrf
            @method('delete')

            <h2 class="text-lg font-semibold text-slate-800 dark:text-white">
                {{ __('Apakah Anda yakin ingin menghapus akun Anda?') }}
            </h2>

            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                {{ __('Setelah akun Anda dihapus, semua sumber daya dan datanya akan dihapus secara permanen. Mohon masukkan kata sandi Anda untuk mengonfirmasi bahwa Anda ingin menghapus akun Anda secara permanen.') }}
            </p>

            <div class="mt-6">
                <x-ui.label for="password" value="{{ __('Kata Sandi') }}" class="sr-only" />

                <x-ui.input
                    id="password"
                    name="password"
                    type="password"
                    class="mt-1 block w-full bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300"
                    placeholder="{{ __('Kata Sandi') }}"
                />

                @error('password', 'userDeletion')
                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <div class="mt-6 flex justify-end">
                <x-ui.button variant="secondary" x-on:click="$dispatch('close')">
                    Batal
                </x-ui.button>

                <x-ui.button variant="destructive" class="ms-3" type="submit">
                    {{ __('Hapus Akun') }}
                </x-ui.button>
            </div>
        </form>
    </x-ui.modal>
</section>