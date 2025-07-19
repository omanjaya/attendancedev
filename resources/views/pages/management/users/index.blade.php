@extends('layouts.authenticated-unified')

@section('title', 'Manajemen Pengguna')

@section('page-content')
<div class="p-6 lg:p-8">
    <x-layouts.base-page
        title="Manajemen Pengguna"
        subtitle="Kelola pengguna sistem, peran, dan izin"
        :breadcrumbs="[
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Manajemen'],
            ['label' => 'Pengguna']
        ]">
        <x-slot name="actions">
            <x-ui.button variant="outline" x-data @click="$dispatch('open-modal', 'filter-modal')">
                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                Filter
            </x-ui.button>
            @can('manage_system_settings')
                <x-ui.button x-data @click="$dispatch('open-modal', 'create-user-modal')">
                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    Buat Pengguna
                </x-ui.button>
            @endcan
        </x-slot>
    </x-layouts.base-page>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4 my-8">
        <x-ui.card variant="metric" title="Total Pengguna" :value="$stats['total_users'] ?? '0'" color="primary"><x-slot name="icon"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg></x-slot></x-ui.card>
        <x-ui.card variant="metric" title="Pengguna Aktif" :value="$stats['active_users'] ?? '0'" color="success"><x-slot name="icon"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></x-slot></x-ui.card>
        <x-ui.card variant="metric" title="Dengan Karyawan" :value="$stats['users_with_employees'] ?? '0'" color="info"><x-slot name="icon"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/></svg></x-slot></x-ui.card>
        <x-ui.card variant="metric" title="Pengguna Baru" :value="$stats['recent_registrations'] ?? '0'" color="warning"><x-slot name="icon"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg></x-slot></x-ui.card>
    </div>

    <!-- Users Table -->
    <x-ui.card title="Pengguna Sistem">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-border" id="users-table">
                <!-- Table content from original file -->
            </table>
        </div>
    </x-ui.card>

    <!-- Modals -->
    <!-- Modals from original file -->
</div>
@endsection

@push('scripts')
{{-- Scripts from original file --}}
@endpush
