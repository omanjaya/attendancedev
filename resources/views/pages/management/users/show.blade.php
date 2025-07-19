@extends('layouts.authenticated-unified')

@section('title', 'Detail Pengguna - ' . $user->name)

@section('page-content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100 dark:from-slate-900 dark:via-blue-900 dark:to-indigo-900">
    <div class="p-6 lg:p-8">
        <x-layouts.base-page
            title="Detail Pengguna: {{ $user->name }}"
            subtitle="Manajemen Pengguna - Informasi lengkap tentang pengguna ini"
            :breadcrumbs="[
                ['label' => 'Dashboard', 'url' => route('dashboard')],
                ['label' => 'Manajemen'],
                ['label' => 'Pengguna', 'url' => route('users.index')],
                ['label' => 'Detail Pengguna']
            ]">
            <x-slot name="actions">
                <x-ui.button variant="secondary" href="{{ route('users.index') }}">
                    <svg class="w-5 h-5 mr-2 group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Kembali ke Pengguna
                </x-ui.button>
                @can('manage_users')
                    <x-ui.button href="{{ route('users.edit', $user) }}">
                        <svg class="w-5 h-5 mr-2 group-hover:rotate-90 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h1a2 2 0 0 1 2 2v9a2 2 0 0 1 -2 2h-9a2 2 0 0 1 -2 -2v-1"/><path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z"/><path d="M16 5l3 3"/></svg>
                        Edit Pengguna
                    </x-ui.button>
                @endcan
            </x-slot>
        </x-layouts.base-page>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                <h3 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Informasi Pengguna</h3>
                <div class="space-y-4 text-slate-600 dark:text-slate-400">
                    <div class="flex justify-between"><span class="font-medium">Nama:</span><span class="text-slate-800 dark:text-white">{{ $user->name }}</span></div>
                    <div class="flex justify-between"><span class="font-medium">Email:</span><span class="text-slate-800 dark:text-white">{{ $user->email }}</span></div>
                    <div class="flex justify-between"><span class="font-medium">Status:</span>
                        @if($user->is_active)
                            <x-ui.badge variant="success">Aktif</x-ui.badge>
                        @else
                            <x-ui.badge variant="destructive">Tidak Aktif</x-ui.badge>
                        @endif
                    </div>
                    <div class="flex justify-between"><span class="font-medium">Login Terakhir:</span><span class="text-slate-800 dark:text-white">{{ $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Tidak Pernah' }}</span></div>
                    <div class="flex justify-between"><span class="font-medium">Dibuat:</span><span class="text-slate-800 dark:text-white">{{ $user->created_at->format('M j, Y g:i A') }}</span></div>
                    <div class="flex justify-between"><span class="font-medium">Diperbarui:</span><span class="text-slate-800 dark:text-white">{{ $user->updated_at->format('M j, Y g:i A') }}</span></div>
                </div>
            </div>
            
            <div class="lg:col-span-1 space-y-6">
                <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                    <h3 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Peran & Izin</h3>
                    <div class="space-y-3 text-slate-600 dark:text-slate-400">
                        <div class="flex justify-between"><span class="font-medium">Peran yang Ditugaskan:</span>
                            <div class="flex flex-wrap gap-1">
                                @forelse($user->roles as $role)
                                    <x-ui.badge variant="info">{{ ucfirst($role->name) }}</x-ui.badge>
                                @empty
                                    <span class="text-slate-600 dark:text-slate-400">Tidak ada peran yang ditugaskan</span>
                                @endforelse
                            </div>
                        </div>
                        @if($user->employee)
                            <div class="flex justify-between"><span class="font-medium">Catatan Karyawan:</span>
                                <div class="text-emerald-600 dark:text-emerald-400">
                                    <strong class="text-slate-800 dark:text-white">{{ $user->employee->employee_id }}</strong><br>
                                    <small>{{ ucfirst($user->employee->employee_type) }}</small>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
                
                <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                    <h3 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Tindakan</h3>
                    <div class="space-y-3">
                        @can('impersonate_users')
                            @if(!Session::has('impersonated_by') || Session::get('impersonated_by') !== $user->id)
                                <form method="POST" action="{{ route('impersonate.start', $user) }}">
                                    @csrf
                                    <x-ui.button type="submit" variant="primary" class="w-full">
                                        <svg class="w-5 h-5 mr-2 group-hover:rotate-90 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m0 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                        Login Sebagai
                                    </x-ui.button>
                                </form>
                            @endif
                        @endcan
                        @can('manage_users')
                            <x-ui.button variant="secondary" href="{{ route('users.edit', $user) }}" class="w-full">
                                <svg class="w-5 h-5 mr-2 group-hover:rotate-90 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                Edit Pengguna
                            </x-ui.button>
                            <x-ui.button variant="destructive" class="w-full" onclick="confirmDelete('{{ $user->id }}')">
                                <svg class="w-5 h-5 mr-2 group-hover:rotate-90 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                Hapus Pengguna
                            </x-ui.button>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function confirmDelete(userId) {
    if (confirm('Apakah Anda yakin ingin menghapus pengguna ini? Tindakan ini tidak dapat dibatalkan.')) {
        fetch(`/users/${userId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                toastr.success(data.message);
                window.location.href = '{{ route('users.index') }}';
            } else {
                toastr.error(data.message || 'Gagal menghapus pengguna.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            toastr.error('Terjadi kesalahan saat menghapus pengguna.');
        });
    }
}
</script>
@endpush
