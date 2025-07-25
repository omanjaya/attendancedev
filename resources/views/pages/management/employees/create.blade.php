@extends('layouts.authenticated-unified')

@section('title', 'Tambah Pegawai Baru')

@section('page-content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <div class="p-6 lg:p-8">
        <!-- Page Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Tambah Pegawai Baru</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Buat profil pegawai baru untuk sistem</p>
                </div>
                <div class="flex items-center space-x-3">
                    <button onclick="window.location.href='{{ route('employees.index') }}'" 
                            class="bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 px-4 py-2 rounded-lg font-medium transition-colors duration-200">
                        <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Kembali
                    </button>
                </div>
            </div>
        </div>

        <form action="{{ route('employees.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Informasi Pegawai</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <x-ui.label for="employee_type" value="Jenis Pegawai" required class="text-gray-700 dark:text-gray-300" />
                                <x-ui.select name="employee_type" id="employee_type"
                                           class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-3 text-gray-900 dark:text-white focus:border-blue-500 dark:focus:border-blue-400 focus:ring-2 focus:ring-blue-500/20" required>
                                    <option value="">Pilih Jenis</option>
                                    <option value="permanent" {{ old('employee_type') == 'permanent' ? 'selected' : '' }}>Tetap</option>
                                    <option value="honorary" {{ old('employee_type') == 'honorary' ? 'selected' : '' }}>Guru Honorer</option>
                                    <option value="staff" {{ old('employee_type') == 'staff' ? 'selected' : '' }}>Staf</option>
                                </x-ui.select>
                                @error('employee_type')
                                    <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <div>
                                <x-ui.label for="role" value="Peran" required class="text-gray-700 dark:text-gray-300" />
                                <x-ui.select name="role" id="role"
                                           class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-3 text-gray-900 dark:text-white focus:border-blue-500 dark:focus:border-blue-400 focus:ring-2 focus:ring-blue-500/20" required>
                                    <option value="">Pilih Peran</option>
                                    @foreach($roles as $role)
                                        <option value="{{ $role->name }}" {{ old('role') == $role->name ? 'selected' : '' }}>
                                            {{ ucfirst($role->name) }}
                                        </option>
                                    @endforeach
                                </x-ui.select>
                                @error('role')
                                    <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="mb-6">
                            <x-ui.label for="employee_id" value="ID Pegawai" class="text-gray-700 dark:text-gray-300" />
                            <div class="relative">
                                <x-ui.input type="text" name="employee_id" id="employee_id_preview"
                                           value="{{ old('employee_id') }}" placeholder="Auto-generated berdasarkan Role & Tipe" readonly
                                           class="bg-gray-100/30 backdrop-blur-sm border border-gray-300/40 text-slate-600 dark:text-gray-400 placeholder-slate-500 cursor-not-allowed" />
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">ID akan digenerate otomatis (contoh: GTTP001, PHNR001)</p>
                            </div>
                            @error('employee_id')
                                <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <div class="mb-6">
                            <x-ui.label for="full_name" value="Nama Lengkap" required class="text-gray-700 dark:text-gray-300" />
                            <x-ui.input type="text" name="full_name" 
                                       value="{{ old('full_name') }}" placeholder="I Made Ngurah Agung Wijaya" required 
                                       class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-3 text-gray-900 dark:text-white placeholder-gray-500 focus:border-blue-500 dark:focus:border-blue-400 focus:ring-2 focus:ring-blue-500/20" />
                            @error('full_name')
                                <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                            @enderror
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Masukkan nama lengkap pegawai sesuai dengan identitas resmi.</p>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <x-ui.label for="email" value="Alamat Email" required class="text-gray-700 dark:text-gray-300" />
                                <x-ui.input type="email" name="email" 
                                           value="{{ old('email') }}" required 
                                           class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-3 text-gray-900 dark:text-white placeholder-gray-500 focus:border-blue-500 dark:focus:border-blue-400 focus:ring-2 focus:ring-blue-500/20" />
                                @error('email')
                                    <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <div>
                                <x-ui.label for="phone" value="Nomor Telepon" class="text-gray-700 dark:text-gray-300" />
                                <x-ui.input type="text" name="phone" 
                                           value="{{ old('phone') }}" placeholder="+628123456789" 
                                           class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-3 text-gray-900 dark:text-white placeholder-gray-500 focus:border-blue-500 dark:focus:border-blue-400 focus:ring-2 focus:ring-blue-500/20" />
                                @error('phone')
                                    <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        
                        <!-- Photo Upload Component -->
                        <div class="mb-6">
                            <x-employee-photo-upload name="photo" :required="false" />
                        </div>
                        
                        <div class="mb-6">
                            <x-ui.label for="hire_date" value="Tanggal Bergabung" required class="text-gray-700 dark:text-gray-300" />
                            <x-ui.input type="date" name="hire_date" 
                                       value="{{ old('hire_date', date('Y-m-d')) }}" required 
                                       class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-3 text-gray-900 dark:text-white placeholder-gray-500 focus:border-blue-500 dark:focus:border-blue-400 focus:ring-2 focus:ring-blue-500/20" />
                            @error('hire_date')
                                <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <div class="mb-6">
                            <x-ui.label for="location_id" value="Lokasi Kerja" class="text-gray-700 dark:text-gray-300" />
                            <x-ui.select name="location_id" id="location_id" 
                                       class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-3 text-gray-900 dark:text-white focus:border-blue-500 dark:focus:border-blue-400 focus:ring-2 focus:ring-blue-500/20">
                                <option value="">Pilih Lokasi</option>
                            </x-ui.select>
                            @error('location_id')
                                <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Lokasi kerja utama untuk verifikasi kehadiran</p>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mt-6">
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Kredensial Login</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-ui.label for="password" value="Kata Sandi" required class="text-gray-700 dark:text-gray-300" />
                                <x-ui.input type="password" name="password" 
                                           class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-3 text-gray-900 dark:text-white placeholder-gray-500 focus:border-blue-500 dark:focus:border-blue-400 focus:ring-2 focus:ring-blue-500/20" required />
                                @error('password')
                                    <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                                @enderror
                                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Minimal 8 karakter</p>
                            </div>
                            
                            <div>
                                <x-ui.label for="password_confirmation" value="Konfirmasi Kata Sandi" required class="text-gray-700 dark:text-gray-300" />
                                <x-ui.input type="password" name="password_confirmation" 
                                           class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-3 text-gray-900 dark:text-white placeholder-gray-500 focus:border-blue-500 dark:focus:border-blue-400 focus:ring-2 focus:ring-blue-500/20" required />
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="lg:col-span-1 space-y-6">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Informasi Gaji</h3>
                        <div class="mb-6">
                            <x-ui.label for="salary_type" value="Jenis Gaji" required class="text-gray-700 dark:text-gray-300" />
                            <x-ui.select name="salary_type" id="salary_type" 
                                       class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-3 text-gray-900 dark:text-white focus:border-blue-500 dark:focus:border-blue-400 focus:ring-2 focus:ring-blue-500/20" required>
                                <option value="">Pilih Jenis</option>
                                <option value="monthly" {{ old('salary_type') == 'monthly' ? 'selected' : '' }}>Bulanan</option>
                                <option value="hourly" {{ old('salary_type') == 'hourly' ? 'selected' : '' }}>Per Jam</option>
                                <option value="fixed" {{ old('salary_type') == 'fixed' ? 'selected' : '' }}>Tetap</option>
                            </x-ui.select>
                            @error('salary_type')
                                <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <div class="mb-6" id="salary_amount_field" style="display: none;">
                            <x-ui.label for="salary_amount" value="Gaji Bulanan" class="text-gray-700 dark:text-gray-300" />
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 dark:text-gray-400 text-sm">Rp</span>
                                </div>
                                <x-ui.input type="number" name="salary_amount" 
                                           class="block w-full pl-7 px-3 py-2 border border-white/40 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 sm:text-sm bg-white/30 backdrop-blur-sm text-slate-800 dark:text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300" 
                                           value="{{ old('salary_amount') }}" step="0.01" min="0" />
                            </div>
                            @error('salary_amount')
                                <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <div class="mb-6" id="hourly_rate_field" style="display: none;">
                            <x-ui.label for="hourly_rate" value="Tarif Per Jam" class="text-gray-700 dark:text-gray-300" />
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 dark:text-gray-400 text-sm">Rp</span>
                                </div>
                                <x-ui.input type="number" name="hourly_rate" 
                                           class="block w-full pl-7 pr-16 px-3 py-2 border border-white/40 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 sm:text-sm bg-white/30 backdrop-blur-sm text-slate-800 dark:text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300" 
                                           value="{{ old('hourly_rate') }}" step="0.01" min="0" />
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 dark:text-gray-400 text-sm">/jam</span>
                                </div>
                            </div>
                            @error('hourly_rate')
                                <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mt-6">
                        <div class="p-6">
                            <div class="flex flex-col space-y-3">
                                <x-ui.button type="submit" variant="primary" class="w-full">
                                    <svg class="w-5 h-5 mr-2 group-hover:rotate-90 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5l0 14"/><path d="M5 12l14 0"/></svg>
                                    Buat Pegawai
                                </x-ui.button>
                                <x-ui.button type="button" variant="secondary" class="w-full" href="{{ route('employees.index') }}">
                                    Batal
                                </x-ui.button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Show/hide salary fields based on type
    function updateSalaryFields() {
        const salaryType = $('#salary_type').val();
        
        $('#salary_amount_field').hide();
        $('#hourly_rate_field').hide();
        
        if (salaryType === 'monthly' || salaryType === 'fixed') {
            $('#salary_amount_field').show();
        } else if (salaryType === 'hourly') {
            $('#hourly_rate_field').show();
        }
    }
    
    $('#salary_type').on('change', updateSalaryFields);
    updateSalaryFields(); // Initialize on page load
    
    // Auto-set employee type based on role
    $('[name="role"]').on('change', function() {
        const role = $(this).val();
        if (role === 'teacher') {
            $('[name="employee_type"]').val('honorary');
        }
    });
    
    // Photo preview functionality
    $('#photo').on('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#photoPreview').attr('src', e.target.result);
            };
            reader.readAsDataURL(file);
        }
    });
    
    // Load locations
    function loadLocations() {
        $.ajax({
            url: '/api/v1/locations/select',
            method: 'GET',
            headers: {
                'Authorization': 'Bearer ' + $('meta[name="api-token"]').attr('content'),
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(locations) {
                const select = $('#location_id');
                select.empty().append('<option value="">Pilih Lokasi</option>');
                
                locations.forEach(function(location) {
                    // Handle both object and array structures
                    const id = location.id || location['id'];
                    const name = location.name || location['name'];
                    const code = location.code || location['code'];
                    const address = location.address || location['address'];
                    
                    const displayText = address 
                        ? `${name} (${address})`
                        : code 
                            ? `${name} (${code})`
                            : name;
                    select.append(`<option value="${id}">${displayText}</option>`);
                });
            },
            error: function(xhr) {
                console.error('Gagal memuat lokasi:', xhr);
            }
        });
    }
    
    loadLocations();
    
    // Employee ID Preview based on role and type
    function updateEmployeeIdPreview() {
        const role = $('#role').val();
        const employeeType = $('#employee_type').val();
        
        if (role && employeeType) {
            let prefix = '';
            
            // Generate prefix based on role and employee type
            if (role === 'super_admin' || role === 'Super Admin') {
                prefix = 'SADM';
            } else if (role === 'admin' || role === 'Admin') {
                prefix = 'ADMN';
            } else if (role === 'kepala_sekolah') {
                prefix = 'KPS';
            } else if (role === 'guru') {
                if (employeeType === 'permanent') {
                    prefix = 'GTTP'; // Guru Tetap
                } else if (employeeType === 'honorary') {
                    prefix = 'GHNR'; // Guru Honor
                } else {
                    prefix = 'GURU';
                }
            } else if (role === 'pegawai') {
                if (employeeType === 'honorary') {
                    prefix = 'PHNR'; // Pegawai Honor
                } else {
                    prefix = 'PGWI'; // Pegawai
                }
            }
            
            if (prefix) {
                $('#employee_id_preview').attr('placeholder', `Will be generated: ${prefix}XXX`);
            }
        } else {
            $('#employee_id_preview').attr('placeholder', 'Auto-generated berdasarkan Role & Tipe');
        }
    }
    
    // Update preview when role or employee type changes
    $('#role, #employee_type').on('change', updateEmployeeIdPreview);
    
    // Initial preview update
    updateEmployeeIdPreview();
});
</script>
@endpush
