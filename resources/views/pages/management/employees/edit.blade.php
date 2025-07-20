@extends('layouts.authenticated-unified')

@section('title', 'Edit Pegawai')

@section('page-content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <div class="p-6 lg:p-8">
        <!-- Page Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Edit Pegawai</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $employee->full_name }} • {{ $employee->employee_id }}</p>
                </div>
                <div class="flex items-center space-x-3">
                    <div id="saveStatus" class="hidden">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400">
                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Tersimpan
                        </span>
                    </div>
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

        <!-- Form Progress Indicator -->
        <div class="mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">Progress Pengisian</h4>
                    <span id="progressPercentage" class="text-sm font-medium text-blue-600">0%</span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    <div id="progressBar" class="h-2 bg-blue-600 rounded-full transition-all duration-300" style="width: 0%"></div>
                </div>
            </div>
        </div>

        <form id="editEmployeeForm" action="{{ route('employees.update', $employee) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Information -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Basic Information -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Informasi Dasar</h3>
                            <div class="flex items-center space-x-2">
                                <div class="w-2 h-2 bg-green-500 rounded-full" id="basicInfoStatus"></div>
                                <span class="text-xs text-gray-500 dark:text-gray-400">Validasi otomatis</span>
                            </div>
                        </div>
                        
                        <!-- Employee ID (Read-only) -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ID Pegawai</label>
                            <div class="relative">
                                <input type="text" value="{{ $employee->employee_id }}" readonly 
                                       class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 px-4 py-3 text-gray-500 dark:text-gray-400 cursor-not-allowed">
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <!-- Full Name -->
                        <div class="mb-4">
                            <label for="full_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Nama Lengkap *
                                <span class="text-xs text-gray-500 ml-1">(Min. 3 karakter)</span>
                            </label>
                            <div class="relative">
                                <input type="text" name="full_name" id="full_name" 
                                       value="{{ old('full_name', $employee->full_name) }}" required 
                                       class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-3 pr-10 text-gray-900 dark:text-white focus:border-blue-500 dark:focus:border-blue-400 focus:ring-2 focus:ring-blue-500/20"
                                       minlength="3" maxlength="100">
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                    <div id="fullNameStatus" class="w-4 h-4"></div>
                                </div>
                            </div>
                            @error('full_name')
                                <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                            @enderror
                            <div id="fullNameError" class="text-sm text-red-500 mt-1 hidden"></div>
                        </div>

                        <!-- Email & Phone Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <!-- Email -->
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Email *
                                    <span class="text-xs text-gray-500 ml-1">(Format valid)</span>
                                </label>
                                <div class="relative">
                                    <input type="email" name="email" id="email" 
                                           value="{{ old('email', $employee->user->email ?? '') }}" required 
                                           class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-3 pr-10 text-gray-900 dark:text-white focus:border-blue-500 dark:focus:border-blue-400 focus:ring-2 focus:ring-blue-500/20">
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                        <div id="emailStatus" class="w-4 h-4"></div>
                                    </div>
                                </div>
                                @error('email')
                                    <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                                @enderror
                                <div id="emailError" class="text-sm text-red-500 mt-1 hidden"></div>
                            </div>

                            <!-- Phone -->
                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Nomor Telepon
                                    <span class="text-xs text-gray-500 ml-1">(Format: +628xxx)</span>
                                </label>
                                <div class="relative">
                                    <input type="text" name="phone" id="phone" 
                                           value="{{ old('phone', $employee->phone) }}" 
                                           class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-3 pr-10 text-gray-900 dark:text-white focus:border-blue-500 dark:focus:border-blue-400 focus:ring-2 focus:ring-blue-500/20"
                                           pattern="[\+]?[0-9\s\-\(\)]{10,15}">
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                        <div id="phoneStatus" class="w-4 h-4"></div>
                                    </div>
                                </div>
                                @error('phone')
                                    <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                                @enderror
                                <div id="phoneError" class="text-sm text-red-500 mt-1 hidden"></div>
                            </div>
                        </div>

                        <!-- Last Updated Info -->
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3 text-xs text-gray-600 dark:text-gray-400">
                            <div class="flex items-center justify-between">
                                <span>Terakhir diupdate: {{ $employee->updated_at->format('d/m/Y H:i') }}</span>
                                <span>Bergabung: {{ $employee->created_at->format('d/m/Y') }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Role & Status -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Jabatan & Status</h3>
                            <div class="flex items-center space-x-2">
                                <div class="w-2 h-2 bg-blue-500 rounded-full" id="roleInfoStatus"></div>
                                <span class="text-xs text-gray-500 dark:text-gray-400">Auto-sync</span>
                            </div>
                        </div>
                        
                        <!-- Employee Type & Role Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <!-- Employee Type -->
                            <div>
                                <label for="employee_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Jenis Pegawai *</label>
                                <select name="employee_type" id="employee_type" required 
                                        class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-3 text-gray-900 dark:text-white focus:border-blue-500 dark:focus:border-blue-400 focus:ring-2 focus:ring-blue-500/20">
                                    <option value="">Pilih Jenis</option>
                                    <option value="permanent" {{ old('employee_type', $employee->employee_type) == 'permanent' ? 'selected' : '' }}>Tetap</option>
                                    <option value="honorary" {{ old('employee_type', $employee->employee_type) == 'honorary' ? 'selected' : '' }}>Guru Honorer</option>
                                    <option value="staff" {{ old('employee_type', $employee->employee_type) == 'staff' ? 'selected' : '' }}>Staf</option>
                                </select>
                                @error('employee_type')
                                    <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Role -->
                            <div>
                                <label for="role" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Peran *</label>
                                <select name="role" id="role" required 
                                        class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-3 text-gray-900 dark:text-white focus:border-blue-500 dark:focus:border-blue-400 focus:ring-2 focus:ring-blue-500/20">
                                    <option value="">Pilih Peran</option>
                                    @foreach($roles as $role)
                                        <option value="{{ $role->name }}" {{ old('role', $employee->user->roles->first()->name ?? '') == $role->name ? 'selected' : '' }}>
                                            {{ ucfirst($role->name) }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('role')
                                    <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Status with Toggle -->
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <label for="is_active" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status Pegawai</label>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Mengatur hak akses dan visibilitas</p>
                                </div>
                                <div class="flex items-center space-x-3">
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Tidak Aktif</span>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="hidden" name="is_active" value="0">
                                        <input type="checkbox" name="is_active" value="1" id="is_active" 
                                               {{ old('is_active', $employee->is_active) ? 'checked' : '' }}
                                               class="sr-only peer">
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                                    </label>
                                    <span class="text-sm text-gray-900 dark:text-white font-medium">Aktif</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Information -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">Informasi Tambahan</h3>
                        
                        <!-- Hire Date & Department -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label for="hire_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tanggal Bergabung</label>
                                <input type="date" name="hire_date" id="hire_date" 
                                       value="{{ old('hire_date', $employee->hire_date ? $employee->hire_date->format('Y-m-d') : '') }}"
                                       class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-3 text-gray-900 dark:text-white focus:border-blue-500 dark:focus:border-blue-400 focus:ring-2 focus:ring-blue-500/20">
                                @error('hire_date')
                                    <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <div>
                                <label for="department" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Departemen</label>
                                <input type="text" name="department" id="department" 
                                       value="{{ old('department', $employee->department) }}"
                                       placeholder="Contoh: Pendidikan, Administrasi"
                                       class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-3 text-gray-900 dark:text-white focus:border-blue-500 dark:focus:border-blue-400 focus:ring-2 focus:ring-blue-500/20">
                                @error('department')
                                    <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Notes -->
                        <div>
                            <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Catatan</label>
                            <textarea name="notes" id="notes" rows="3"
                                      placeholder="Catatan khusus atau informasi tambahan tentang pegawai"
                                      class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-3 text-gray-900 dark:text-white focus:border-blue-500 dark:focus:border-blue-400 focus:ring-2 focus:ring-blue-500/20">{{ old('notes', $employee->notes) }}</textarea>
                            @error('notes')
                                <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Photo Card -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">Foto Profil</h3>
                        
                        <!-- Current Photo -->
                        <div class="text-center mb-4">
                            <div class="relative inline-block">
                                @if($employee->photo_path)
                                    <img src="{{ Storage::url($employee->photo_path) }}" alt="{{ $employee->full_name }}" 
                                         id="photoPreview"
                                         class="w-32 h-32 rounded-full mx-auto object-cover border-4 border-gray-200 dark:border-gray-600">
                                @else
                                    <div id="photoPreview" class="w-32 h-32 rounded-full mx-auto bg-gray-200 dark:bg-gray-600 flex items-center justify-center border-4 border-gray-200 dark:border-gray-600">
                                        <span class="text-2xl font-bold text-gray-500 dark:text-gray-400">
                                            {{ substr($employee->full_name, 0, 2) }}
                                        </span>
                                    </div>
                                @endif
                                <div class="absolute bottom-0 right-0 bg-blue-600 rounded-full p-2 cursor-pointer" onclick="document.getElementById('photo').click()">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <!-- Photo Upload -->
                        <div>
                            <input type="file" name="photo" id="photo" accept="image/*" class="hidden">
                            <div class="text-center">
                                <button type="button" onclick="document.getElementById('photo').click()" 
                                        class="w-full bg-gray-100 dark:bg-gray-700 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg px-4 py-3 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                                    <svg class="w-6 h-6 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                    </svg>
                                    Upload Foto Baru
                                </button>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2 text-center">JPG, PNG (Max: 2MB)</p>
                            @error('photo')
                                <p class="text-sm text-red-500 mt-1 text-center">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Location Card -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">Lokasi Kerja</h3>
                        
                        <div>
                            <label for="location_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Lokasi</label>
                            <select name="location_id" id="location_id" 
                                    class="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-4 py-3 text-gray-900 dark:text-white focus:border-blue-500 dark:focus:border-blue-400 focus:ring-2 focus:ring-blue-500/20">
                                <option value="">Pilih Lokasi</option>
                                @foreach($locations as $location)
                                    <option value="{{ $location['id'] }}" {{ old('location_id', $employee->location_id) == $location['id'] ? 'selected' : '' }}>
                                        {{ $location['name'] }}
                                        @if(isset($location['code']) && $location['code'])
                                            ({{ $location['code'] }})
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('location_id')
                                <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Quick Actions Card -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Quick Actions</h3>
                        <div class="space-y-2">
                            <button type="button" onclick="resetForm()" 
                                    class="w-full text-left px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg transition-colors">
                                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                                Reset ke Data Asli
                            </button>
                            <button type="button" onclick="validateForm()" 
                                    class="w-full text-left px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg transition-colors">
                                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Validasi Form
                            </button>
                        </div>
                    </div>

                    <!-- Actions Card -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                        <div class="space-y-3">
                            <button type="submit" id="submitBtn"
                                    class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-3 rounded-lg font-medium transition-colors duration-200 flex items-center justify-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span id="submitText">Simpan Perubahan</span>
                            </button>
                            <button type="button" onclick="window.location.href='{{ route('employees.index') }}'" 
                                    class="w-full bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 px-4 py-3 rounded-lg font-medium transition-colors duration-200">
                                Batal
                            </button>
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
// Prevent variable redeclaration
window.employeeEditVars = window.employeeEditVars || {};
if (!window.employeeEditVars.initialized) {
    window.employeeEditVars.hasChanges = false;
    window.employeeEditVars.originalData = {};
    window.employeeEditVars.initialized = true;
}

document.addEventListener('DOMContentLoaded', function() {
    // Store original form data
    storeOriginalData();
    
    // Check for saved draft
    checkForSavedDraft();
    
    // Initialize status change tracking
    initializeStatusTracking();
    
    // Initialize form validation
    initializeValidation();
    
    // Initialize progress tracking
    updateProgress();
    
    // Auto-save functionality
    initializeAutoSave();
    
    // Prevent accidental navigation
    window.addEventListener('beforeunload', function(e) {
        if (window.employeeEditVars.hasChanges) {
            e.preventDefault();
            e.returnValue = '';
        }
    });
});

function storeOriginalData() {
    const form = document.getElementById('editEmployeeForm');
    const formData = new FormData(form);
    originalData = {};
    for (let [key, value] of formData.entries()) {
        originalData[key] = value;
    }
}

function checkForSavedDraft() {
    const saveKey = `employee_draft_${window.location.pathname}`;
    const savedData = localStorage.getItem(saveKey);
    
    if (savedData) {
        try {
            const saveObject = JSON.parse(savedData);
            const timeDiff = new Date() - new Date(saveObject.timestamp);
            const hoursDiff = timeDiff / (1000 * 60 * 60);
            
            // Only restore if saved within last 24 hours
            if (hoursDiff < 24) {
                showDraftRestoreNotification(saveObject);
            } else {
                // Clean up old draft
                localStorage.removeItem(saveKey);
            }
        } catch (error) {
            localStorage.removeItem(saveKey);
        }
    }
}

function showDraftRestoreNotification(saveObject) {
    const notification = document.createElement('div');
    notification.className = 'fixed top-4 left-1/2 transform -translate-x-1/2 z-50 max-w-md w-full bg-white dark:bg-gray-800 border border-blue-200 dark:border-blue-700 rounded-xl shadow-lg';
    notification.innerHTML = `
        <div class="p-4">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <div class="p-1 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                </div>
                <div class="ml-3 w-0 flex-1">
                    <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">Draft Tersimpan Ditemukan</h3>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Tersimpan: ${new Date(saveObject.timestamp).toLocaleString('id-ID')} (${saveObject.progress} selesai)
                    </p>
                    <div class="mt-3 flex space-x-2">
                        <button onclick="restoreDraft('${saveObject.timestamp}')" class="text-xs bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded-md transition-colors">
                            Pulihkan Draft
                        </button>
                        <button onclick="dismissDraft()" class="text-xs bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 px-3 py-1 rounded-md transition-colors">
                            Abaikan
                        </button>
                    </div>
                </div>
                <div class="ml-4 flex-shrink-0">
                    <button onclick="dismissDraft()" class="inline-flex text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 focus:outline-none transition-colors">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(notification);
    window.draftNotification = notification;
}

function restoreDraft(timestamp) {
    const saveKey = `employee_draft_${window.location.pathname}`;
    const savedData = localStorage.getItem(saveKey);
    
    if (savedData) {
        try {
            const saveObject = JSON.parse(savedData);
            const form = document.getElementById('editEmployeeForm');
            
            // Restore form values
            Object.entries(saveObject.data).forEach(([key, value]) => {
                const field = form.querySelector(`[name="${key}"]`);
                if (field) {
                    if (field.type === 'checkbox') {
                        field.checked = value === '1';
                    } else {
                        field.value = value;
                    }
                }
            });
            
            // Update progress and validation
            updateProgress();
            validateForm();
            
            showNotification('Draft berhasil dipulihkan', 'success');
            dismissDraft();
            
        } catch (error) {
            showNotification('Gagal memulihkan draft', 'error');
            dismissDraft();
        }
    }
}

function dismissDraft() {
    const saveKey = `employee_draft_${window.location.pathname}`;
    localStorage.removeItem(saveKey);
    
    if (window.draftNotification) {
        window.draftNotification.remove();
        delete window.draftNotification;
    }
}

function initializeStatusTracking() {
    const statusFields = ['employee_type', 'role', 'is_active'];
    const statusHistory = [];
    
    statusFields.forEach(fieldName => {
        const field = document.querySelector(`[name="${fieldName}"]`);
        if (field) {
            const initialValue = field.type === 'checkbox' ? field.checked : field.value;
            
            field.addEventListener('change', function() {
                const newValue = this.type === 'checkbox' ? this.checked : this.value;
                const oldValue = statusHistory.find(h => h.field === fieldName)?.value || initialValue;
                
                if (newValue !== oldValue) {
                    statusHistory.push({
                        field: fieldName,
                        oldValue: oldValue,
                        newValue: newValue,
                        timestamp: new Date().toISOString()
                    });
                    
                    showStatusChangePreview(fieldName, oldValue, newValue);
                }
                
                // Update current value in history
                const existingIndex = statusHistory.findIndex(h => h.field === fieldName);
                if (existingIndex >= 0) {
                    statusHistory[existingIndex].value = newValue;
                } else {
                    statusHistory.push({ field: fieldName, value: newValue });
                }
            });
            
            // Store initial value
            statusHistory.push({ field: fieldName, value: initialValue });
        }
    });
    
    window.statusHistory = statusHistory;
}

function showStatusChangePreview(fieldName, oldValue, newValue) {
    const fieldLabels = {
        'employee_type': 'Jenis Pegawai',
        'role': 'Peran',
        'is_active': 'Status Aktif'
    };
    
    const valueLabels = {
        'permanent': 'Tetap',
        'honorary': 'Guru Honorer', 
        'staff': 'Staf',
        'true': 'Aktif',
        'false': 'Tidak Aktif'
    };
    
    const oldLabel = valueLabels[oldValue] || oldValue;
    const newLabel = valueLabels[newValue] || newValue;
    
    // Show temporary change notification
    const changeNotification = document.createElement('div');
    changeNotification.className = 'fixed bottom-4 right-4 z-40 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded-lg shadow-lg p-3 max-w-sm transform translate-y-full transition-all duration-300 ease-out';
    changeNotification.innerHTML = `
        <div class="flex items-start space-x-2">
            <div class="flex-shrink-0">
                <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                </svg>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-xs font-medium text-amber-800 dark:text-amber-200">${fieldLabels[fieldName]} Berubah</p>
                <p class="text-xs text-amber-700 dark:text-amber-300 mt-1">
                    ${oldLabel} → ${newLabel}
                </p>
            </div>
        </div>
    `;
    
    document.body.appendChild(changeNotification);
    
    setTimeout(() => {
        changeNotification.style.transform = 'translateY(0)';
    }, 100);
    
    setTimeout(() => {
        changeNotification.style.transform = 'translateY(100%)';
        setTimeout(() => {
            if (changeNotification.parentNode) {
                changeNotification.remove();
            }
        }, 300);
    }, 3000);
}

function initializeValidation() {
    // Real-time validation for full name
    const fullNameInput = document.getElementById('full_name');
    fullNameInput.addEventListener('input', function() {
        validateFullName();
        updateProgress();
        window.employeeEditVars.hasChanges = true;
    });
    
    // Real-time validation for email
    const emailInput = document.getElementById('email');
    emailInput.addEventListener('input', function() {
        validateEmail();
        updateProgress();
        window.employeeEditVars.hasChanges = true;
    });
    
    // Real-time validation for phone
    const phoneInput = document.getElementById('phone');
    phoneInput.addEventListener('input', function() {
        validatePhone();
        updateProgress();
        window.employeeEditVars.hasChanges = true;
    });
    
    // Track all form changes
    const formInputs = document.querySelectorAll('#editEmployeeForm input, #editEmployeeForm select, #editEmployeeForm textarea');
    formInputs.forEach(input => {
        input.addEventListener('change', function() {
            window.employeeEditVars.hasChanges = true;
            updateProgress();
        });
    });
}

function validateFullName() {
    const input = document.getElementById('full_name');
    const status = document.getElementById('fullNameStatus');
    const error = document.getElementById('fullNameError');
    
    const value = input.value.trim();
    
    if (value.length === 0) {
        setValidationState(status, error, 'empty', '');
    } else if (value.length < 3) {
        setValidationState(status, error, 'error', 'Nama harus minimal 3 karakter');
    } else if (value.length > 100) {
        setValidationState(status, error, 'error', 'Nama maksimal 100 karakter');
    } else {
        setValidationState(status, error, 'success', '');
    }
}

function validateEmail() {
    const input = document.getElementById('email');
    const status = document.getElementById('emailStatus');
    const error = document.getElementById('emailError');
    
    const value = input.value.trim();
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    
    if (value.length === 0) {
        setValidationState(status, error, 'empty', '');
    } else if (!emailRegex.test(value)) {
        setValidationState(status, error, 'error', 'Format email tidak valid');
    } else {
        setValidationState(status, error, 'success', '');
    }
}

function validatePhone() {
    const input = document.getElementById('phone');
    const status = document.getElementById('phoneStatus');
    const error = document.getElementById('phoneError');
    
    const value = input.value.trim();
    const phoneRegex = /^[+]?[0-9\s\-()]{10,15}$/;
    
    if (value.length === 0) {
        setValidationState(status, error, 'empty', '');
    } else if (!phoneRegex.test(value)) {
        setValidationState(status, error, 'error', 'Format nomor telepon tidak valid');
    } else {
        setValidationState(status, error, 'success', '');
    }
}

function setValidationState(statusElement, errorElement, state, message) {
    if (statusElement) statusElement.className = 'w-4 h-4';
    if (errorElement) errorElement.textContent = message;
    
    switch (state) {
        case 'success':
            if (statusElement) statusElement.innerHTML = '<svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>';
            if (errorElement) errorElement.classList.add('hidden');
            break;
        case 'error':
            if (statusElement) statusElement.innerHTML = '<svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>';
            if (errorElement) errorElement.classList.remove('hidden');
            break;
        case 'empty':
            if (statusElement) statusElement.innerHTML = '';
            if (errorElement) errorElement.classList.add('hidden');
            break;
    }
}

function updateProgress() {
    const requiredFields = ['full_name', 'email', 'employee_type', 'role'];
    const optionalFields = ['phone', 'hire_date', 'department', 'location_id'];
    
    let filledRequired = 0;
    let filledOptional = 0;
    
    requiredFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field && field.value.trim()) {
            filledRequired++;
        }
    });
    
    optionalFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field && field.value.trim()) {
            filledOptional++;
        }
    });
    
    const totalRequired = requiredFields.length;
    const totalOptional = optionalFields.length;
    
    // Calculate progress: Required fields worth 70%, optional fields worth 30%
    const requiredProgress = (filledRequired / totalRequired) * 70;
    const optionalProgress = (filledOptional / totalOptional) * 30;
    const totalProgress = Math.round(requiredProgress + optionalProgress);
    
    const progressBar = document.getElementById('progressBar');
    const progressPercentage = document.getElementById('progressPercentage');
    
    if (progressBar) progressBar.style.width = totalProgress + '%';
    if (progressPercentage) progressPercentage.textContent = totalProgress + '%';
    
    // Change color based on progress
    if (progressBar) {
        if (totalProgress >= 80) {
            progressBar.className = 'h-2 bg-green-600 rounded-full transition-all duration-300';
        } else if (totalProgress >= 50) {
            progressBar.className = 'h-2 bg-blue-600 rounded-full transition-all duration-300';
        } else {
            progressBar.className = 'h-2 bg-yellow-500 rounded-full transition-all duration-300';
        }
    }
}

function initializeAutoSave() {
    // Auto-save every 30 seconds if there are changes
    setInterval(function() {
        if (window.employeeEditVars.hasChanges) {
            saveData();
        }
    }, 30000);
}

function saveData() {
    const saveStatus = document.getElementById('saveStatus');
    
    // Show saving status
    saveStatus.innerHTML = `
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400">
            <svg class="w-3 h-3 mr-1 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            Menyimpan...
        </span>
    `;
    saveStatus.classList.remove('hidden');
    
    // Prepare form data for auto-save
    const form = document.getElementById('editEmployeeForm');
    const formData = new FormData(form);
    
    // Convert to JSON for localStorage (exclude file uploads and security tokens)
    const dataToSave = {};
    for (let [key, value] of formData.entries()) {
        if (key !== 'photo' && key !== '_token' && key !== '_method' && key !== 'password' && key !== 'password_confirmation') {
            dataToSave[key] = value;
        }
    }
    
    // Save to localStorage with timestamp
    const saveKey = `employee_draft_${window.location.pathname}`;
    const progressEl = document.getElementById('progressPercentage');
    const saveObject = {
        data: dataToSave,
        timestamp: new Date().toISOString(),
        progress: progressEl ? progressEl.textContent : '0%'
    };
    
    try {
        localStorage.setItem(saveKey, JSON.stringify(saveObject));
        
        setTimeout(function() {
            saveStatus.innerHTML = `
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400">
                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Tersimpan otomatis
                </span>
            `;
            window.employeeEditVars.hasChanges = false;
            
            // Hide status after 3 seconds
            setTimeout(() => {
                saveStatus.classList.add('hidden');
            }, 3000);
        }, 800);
        
    } catch (error) {
        setTimeout(function() {
            saveStatus.innerHTML = `
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400">
                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Gagal menyimpan
                </span>
            `;
            
            setTimeout(() => {
                saveStatus.classList.add('hidden');
            }, 3000);
        }, 800);
    }
}

function resetForm() {
    if (confirm('Apakah Anda yakin ingin mereset form ke data asli?')) {
        const form = document.getElementById('editEmployeeForm');
        form.reset();
        
        // Restore original values
        for (let [key, value] of Object.entries(originalData)) {
            const field = form.querySelector(`[name="${key}"]`);
            if (field) {
                field.value = value;
            }
        }
        
        window.employeeEditVars.hasChanges = false;
        updateProgress();
        
        // Clear validation states
        document.querySelectorAll('[id$="Status"]').forEach(el => el.innerHTML = '');
        document.querySelectorAll('[id$="Error"]').forEach(el => el.classList.add('hidden'));
    }
}

function validateForm() {
    validateFullName();
    validateEmail();
    validatePhone();
    updateProgress();
    
    showNotification('Form telah divalidasi', 'success');
}

// Photo preview functionality
document.getElementById('photo').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        // Validate file size
        if (file.size > 2 * 1024 * 1024) {
            showNotification('Ukuran file terlalu besar. Maksimal 2MB.', 'error');
            this.value = '';
            return;
        }
        
        // Validate file type
        if (!file.type.startsWith('image/')) {
            showNotification('File harus berupa gambar.', 'error');
            this.value = '';
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('photoPreview');
            preview.innerHTML = `<img src="${e.target.result}" alt="Preview" class="w-32 h-32 rounded-full mx-auto object-cover border-4 border-gray-200 dark:border-gray-600">`;
        };
        reader.readAsDataURL(file);
        window.employeeEditVars.hasChanges = true;
    }
});

// Auto-set employee type based on role
document.getElementById('role').addEventListener('change', function() {
    const role = this.value;
    const employeeTypeSelect = document.getElementById('employee_type');
    
    if (role === 'teacher' || role === 'guru') {
        employeeTypeSelect.value = 'honorary';
    } else if (role === 'admin' || role === 'super_admin') {
        employeeTypeSelect.value = 'staff';
    }
    window.employeeEditVars.hasChanges = true;
});

// Form submission with validation
document.getElementById('editEmployeeForm').addEventListener('submit', function(e) {
    const submitBtn = document.getElementById('submitBtn');
    const submitText = document.getElementById('submitText');
    
    // Show loading state with null checks
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = `
            <svg class="w-5 h-5 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            <span>Menyimpan...</span>
        `;
    }
    
    if (submitText) {
        submitText.textContent = 'Menyimpan...';
    }
    
    window.employeeEditVars.hasChanges = false;
});

// Notification system
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    
    const icons = {
        success: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
        error: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
        warning: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16c-.77.833.192 2.5 1.732 2.5z"/></svg>',
        info: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'
    };
    
    const styles = {
        success: 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-700 text-green-800 dark:text-green-200',
        error: 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-700 text-red-800 dark:text-red-200',
        warning: 'bg-amber-50 dark:bg-amber-900/20 border-amber-200 dark:border-amber-700 text-amber-800 dark:text-amber-200',
        info: 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-700 text-blue-800 dark:text-blue-200'
    };
    
    notification.className = `fixed top-4 right-4 z-50 max-w-sm w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-lg transform translate-x-full transition-all duration-300 ease-out`;
    notification.innerHTML = `
        <div class="p-4">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <div class="p-1 ${styles[type]} rounded-lg">
                        ${icons[type]}
                    </div>
                </div>
                <div class="ml-3 w-0 flex-1">
                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">${message}</p>
                </div>
                <div class="ml-4 flex-shrink-0 flex">
                    <button onclick="this.closest('.fixed').remove()" class="inline-flex text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 focus:outline-none transition-colors">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 100);
    
    const duration = type === 'error' ? 5000 : 3000;
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 300);
    }, duration);
}
</script>
@endpush