@extends('layouts.authenticated-unified')

@section('title', 'Edit Pegawai')

@section('page-content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100 dark:from-slate-900 dark:via-blue-900 dark:to-indigo-900">
    <div class="p-6 lg:p-8">
        <x-layouts.base-page
            title="Edit Pegawai: {{ $employee->full_name }}"
            subtitle="Perbarui informasi profil {{ $employee->full_name }}"
            :breadcrumbs="[
                ['label' => 'Dashboard', 'url' => route('dashboard')],
                ['label' => 'Pegawai', 'url' => route('employees.index')],
                ['label' => 'Edit Pegawai']
            ]">
            <x-slot name="actions">
                <x-ui.button variant="secondary" href="{{ route('employees.show', $employee) }}">
                    <svg class="w-5 h-5 mr-2 group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    Lihat Profil
                </x-ui.button>
                <x-ui.button variant="secondary" href="{{ route('employees.index') }}">
                    <svg class="w-5 h-5 mr-2 group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Kembali ke Daftar
                </x-ui.button>
            </x-slot>
        </x-layouts.base-page>

        <form action="{{ route('employees.update', $employee) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')
            
            <div class="group relative glassmorphism-green-card">
                <h3 class="text-xl font-semibold text-slate-800 dark:text-white mb-4 flex items-center">
                    <svg class="w-6 h-6 mr-2 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    Informasi Pribadi
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <x-ui.label for="employee_id" value="ID Pegawai" required class="text-slate-700 dark:text-slate-300" />
                        <x-ui.input type="text" name="employee_id" 
                                   value="{{ old('employee_id', $employee->employee_id) }}" placeholder="EMP001" required 
                                   class="glassmorphism-green-input" />
                        @error('employee_id')
                            <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <x-ui.label for="employee_type" value="Jenis Pegawai" required class="text-slate-700 dark:text-slate-300" />
                        <x-ui.select name="employee_type" 
                                   class="glassmorphism-green-select" required>
                            <option value="">Pilih Jenis</option>
                            <option value="permanent" {{ old('employee_type', $employee->employee_type) == 'permanent' ? 'selected' : '' }}>Tetap</option>
                            <option value="honorary" {{ old('employee_type', $employee->employee_type) == 'honorary' ? 'selected' : '' }}>Guru Honorer</option>
                            <option value="staff" {{ old('employee_type', $employee->employee_type) == 'staff' ? 'selected' : '' }}>Staf</option>
                        </x-ui.select>
                        @error('employee_type')
                            <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="md:col-span-2">
                        <x-ui.label for="full_name" value="Nama Lengkap" required class="text-slate-700 dark:text-slate-300" />
                        <x-ui.input type="text" name="full_name" 
                                   value="{{ old('full_name', $employee->full_name) }}" placeholder="I Made Ngurah Agung Wijaya" required 
                                   class="glassmorphism-green-input" />
                        @error('full_name')
                            <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                        <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">Masukkan nama lengkap pegawai sesuai dengan identitas resmi.</p>
                    </div>

                    <div>
                        <x-ui.label for="email" value="Alamat Email" required class="text-slate-700 dark:text-slate-300" />
                        <x-ui.input type="email" name="email" 
                                   value="{{ old('email', $employee->email) }}" 
                                   class="glassmorphism-green-input" />
                        @error('email')
                            <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <x-ui.label for="phone" value="Nomor Telepon" class="text-slate-700 dark:text-slate-300" />
                        <x-ui.input type="tel" name="phone" 
                                   value="{{ old('phone', $employee->phone) }}" placeholder="+1 (555) 123-4567" 
                                   class="glassmorphism-green-input" />
                        @error('phone')
                            <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <x-ui.label for="date_of_birth" value="Tanggal Lahir" class="text-slate-700 dark:text-slate-300" />
                        <x-ui.input type="date" name="date_of_birth" 
                                   value="{{ old('date_of_birth', $employee->date_of_birth?->format('Y-m-d')) }}" 
                                   class="glassmorphism-green-input" />
                        @error('date_of_birth')
                            <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <x-ui.label for="gender" value="Jenis Kelamin" class="text-slate-700 dark:text-slate-300" />
                        <x-ui.select name="gender" 
                                   class="glassmorphism-green-select">
                            <option value="">Pilih Jenis Kelamin</option>
                            <option value="male" {{ old('gender', $employee->gender) == 'male' ? 'selected' : '' }}>Laki-laki</option>
                            <option value="female" {{ old('gender', $employee->gender) == 'female' ? 'selected' : '' }}>Perempuan</option>
                            <option value="other" {{ old('gender', $employee->gender) == 'other' ? 'selected' : '' }}>Lainnya</option>
                        </x-ui.select>
                        @error('gender')
                            <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="group relative glassmorphism-green-card">
                <h3 class="text-xl font-semibold text-slate-800 dark:text-white mb-4 flex items-center">
                    <svg class="w-6 h-6 mr-2 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    Akun & Lokasi
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <x-ui.label for="role" value="Peran" required class="text-slate-700 dark:text-slate-300" />
                        <x-ui.select name="role" 
                                   class="glassmorphism-green-select" required>
                            <option value="">Pilih Peran</option>
                            @if($roles)
                                @foreach($roles as $role)
                                    <option value="{{ is_object($role) ? $role->name : $role }}" 
                                            {{ old('role', $employee->user?->roles?->first()?->name ?? '') == (is_object($role) ? $role->name : $role) ? 'selected' : '' }}>
                                        {{ ucfirst(is_object($role) ? $role->name : $role) }}
                                    </option>
                                @endforeach
                            @endif
                        </x-ui.select>
                        @error('role')
                            <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <x-ui.label for="location_id" value="Lokasi" required class="text-slate-700 dark:text-slate-300" />
                        <x-ui.select name="location_id" 
                                   class="glassmorphism-green-select" required>
                            <option value="">Pilih Lokasi</option>
                            @if($locations)
                                @foreach($locations as $id => $name)
                                    <option value="{{ $id }}" {{ old('location_id', $employee->location_id) == $id ? 'selected' : '' }}>
                                        {{ $name }}
                                    </option>
                                @endforeach
                            @endif
                        </x-ui.select>
                        @error('location_id')
                            <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <x-ui.label for="is_active" value="Status" required class="text-slate-700 dark:text-slate-300" />
                        <x-ui.select name="is_active" 
                                   class="glassmorphism-green-select" required>
                            <option value="1" {{ old('is_active', $employee->is_active) ? 'selected' : '' }}>Aktif</option>
                            <option value="0" {{ old('is_active', $employee->is_active) ? '' : 'selected' }}>Tidak Aktif</option>
                        </x-ui.select>
                        @error('is_active')
                            <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <x-ui.label for="hire_date" value="Tanggal Bergabung" class="text-slate-700 dark:text-slate-300" />
                        <x-ui.input type="date" name="hire_date" 
                                   value="{{ old('hire_date', $employee->hire_date?->format('Y-m-d')) }}" 
                                   class="glassmorphism-green-input" />
                        @error('hire_date')
                            <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="group relative glassmorphism-green-card">
                <h3 class="text-xl font-semibold text-slate-800 dark:text-white mb-4 flex items-center">
                    <svg class="w-6 h-6 mr-2 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    Informasi Alamat
                </h3>
                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <x-ui.label for="address" value="Alamat" class="text-slate-700 dark:text-slate-300" />
                        <textarea name="address" rows="3" 
                                  class="glassmorphism-green-textarea" 
                                  placeholder="Masukkan alamat lengkap...">{{ old('address', $employee->address) }}</textarea>
                        @error('address')
                            <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="group relative glassmorphism-green-card">
                <h3 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Tindakan Formulir</h3>
                <div class="flex items-center justify-end space-x-4">
                    <x-ui.button variant="secondary" href="{{ route('employees.index') }}">
                        Batal
                    </x-ui.button>
                    <x-ui.button type="submit" variant="primary">
                        <svg class="w-5 h-5 mr-2 group-hover:rotate-90 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Perbarui Pegawai
                    </x-ui.button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Form validation and enhancement
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form');
        const submitButton = form.querySelector('button[type="submit"]');
        
        // Add loading state to submit button
        form.addEventListener('submit', function() {
            submitButton.disabled = true;
            submitButton.innerHTML = `
                <span class="inline-block animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></span>
                Memperbarui...
            `;
        });
        
        // Auto-format phone number
        const phoneInput = form.querySelector('input[name="phone"]');
        if (phoneInput) {
            phoneInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length >= 10) {
                    value = value.replace(/(\d{3})(\d{3})(\d{4})/, '($1) $2-$3');
                }
                e.target.value = value;
            });
        }
        
        // Employee ID validation
        const employeeIdInput = form.querySelector('input[name="employee_id"]');
        if (employeeIdInput) {
            employeeIdInput.addEventListener('input', function(e) {
                e.target.value = e.target.value.toUpperCase();
            });
        }
    });
</script>
@endpush
