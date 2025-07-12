@extends('layouts.authenticated')

@section('title', 'Edit Employee')

@section('page-content')
<div class="py-6">
    <div class="max-w-7xl mx-auto px-8">
        <!-- Page Header -->
        <div class="mb-8 mt-6">
            <h2 class="text-3xl font-bold text-gray-900">Edit Employee: {{ $employee->full_name }}</h2>
            <div class="text-gray-600 mt-1">Employees - Update employee profile information</div>
        </div>

        <form action="{{ route('employees.update', $employee) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900">Employee Information</h3>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Employee ID <span class="text-red-500">*</span></label>
                                    <input type="text" name="employee_id" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('employee_id') border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500 @enderror" 
                                           value="{{ old('employee_id', $employee->employee_id) }}" placeholder="EMP001" required>
                                    @error('employee_id')
                                        <div class="mt-2 text-sm text-red-600">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Employee Type <span class="text-red-500">*</span></label>
                                    <select name="employee_type" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('employee_type') border-red-300 text-red-900 focus:ring-red-500 focus:border-red-500 @enderror" required>
                                        <option value="">Select Type</option>
                                        <option value="permanent" {{ old('employee_type', $employee->employee_type) == 'permanent' ? 'selected' : '' }}>Permanent</option>
                                        <option value="honorary" {{ old('employee_type', $employee->employee_type) == 'honorary' ? 'selected' : '' }}>Honorary Teacher</option>
                                        <option value="staff" {{ old('employee_type', $employee->employee_type) == 'staff' ? 'selected' : '' }}>Staff</option>
                                    </select>
                                    @error('employee_type')
                                        <div class="mt-2 text-sm text-red-600">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                    
                    <div class="grid grid-cols-12 gap-4 mb-3">
                        <div class="md:col-span-6">
                            <div class="block text-sm font-medium text-gray-700 required">First Name</div>
                            <input type="text" name="first_name" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('first_name') border-red-300 text-red-900 focus:ring-red-500 focus:border-red-500 @enderror" 
                                   value="{{ old('first_name', $employee->first_name) }}" required>
                            @error('first_name')
                                <div class="mt-2 text-sm text-red-600">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="md:col-span-6">
                            <div class="block text-sm font-medium text-gray-700 required">Last Name</div>
                            <input type="text" name="last_name" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('last_name') border-red-300 text-red-900 focus:ring-red-500 focus:border-red-500 @enderror" 
                                   value="{{ old('last_name', $employee->last_name) }}" required>
                            @error('last_name')
                                <div class="mt-2 text-sm text-red-600">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-12 gap-4 mb-3">
                        <div class="md:col-span-6">
                            <div class="block text-sm font-medium text-gray-700 required">Email Address</div>
                            <input type="email" name="email" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('email') border-red-300 text-red-900 focus:ring-red-500 focus:border-red-500 @enderror" 
                                   value="{{ old('email', $employee->user->email) }}" required>
                            @error('email')
                                <div class="mt-2 text-sm text-red-600">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="md:col-span-6">
                            <div class="block text-sm font-medium text-gray-700">Phone Number</div>
                            <input type="text" name="phone" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('phone') border-red-300 text-red-900 focus:ring-red-500 focus:border-red-500 @enderror" 
                                   value="{{ old('phone', $employee->phone) }}" placeholder="+1234567890">
                            @error('phone')
                                <div class="mt-2 text-sm text-red-600">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-12 gap-4 mb-3">
                        <div class="md:col-span-6">
                            <div class="block text-sm font-medium text-gray-700">Employee Photo</div>
                            <input type="file" name="photo" id="photo" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('photo') border-red-300 text-red-900 focus:ring-red-500 focus:border-red-500 @enderror" 
                                   accept="image/jpeg,image/png,image/jpg">
                            @error('photo')
                                <div class="mt-2 text-sm text-red-600">{{ $message }}</div>
                            @enderror
                            <small class="mt-2 text-sm text-gray-500">Upload a new photo to replace current one. Max size: 2MB. Formats: JPEG, PNG, JPG</small>
                        </div>
                        
                        <div class="md:col-span-6">
                            <div class="block text-sm font-medium text-gray-700">Photo Preview</div>
                            <div class="photo-preview-max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                                <img id="photoPreview" src="{{ $employee->photo_url }}" 
                                     alt="{{ $employee->full_name }}" class="avatar avatar-xl rounded">
                                <div class="small text-gray-600 mt-2">
                                    Current photo used for face recognition
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-12 gap-4 mb-3">
                        <div class="md:col-span-6">
                            <div class="block text-sm font-medium text-gray-700 required">Hire Date</div>
                            <input type="date" name="hire_date" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('hire_date') border-red-300 text-red-900 focus:ring-red-500 focus:border-red-500 @enderror" 
                                   value="{{ old('hire_date', $employee->hire_date->format('Y-m-d')) }}" required>
                            @error('hire_date')
                                <div class="mt-2 text-sm text-red-600">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="md:col-span-6">
                            <div class="block text-sm font-medium text-gray-700 required">Role</div>
                            <select name="role" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('role') border-red-300 text-red-900 focus:ring-red-500 focus:border-red-500 @enderror" required>
                                <option value="">Select Role</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->name }}" {{ old('role', $employee->user->roles->first()->name ?? '') == $role->name ? 'selected' : '' }}>
                                        {{ ucfirst($role->name) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('role')
                                <div class="mt-2 text-sm text-red-600">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-12 gap-4 mb-3">
                        <div class="md:col-span-6">
                            <div class="block text-sm font-medium text-gray-700">Work Location</div>
                            <select name="location_id" id="location_id" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('location_id') border-red-300 text-red-900 focus:ring-red-500 focus:border-red-500 @enderror" 
                                    data-selected="{{ old('location_id', $employee->location_id) }}">
                                <option value="">Select Location</option>
                            </select>
                            @error('location_id')
                                <div class="mt-2 text-sm text-red-600">{{ $message }}</div>
                            @enderror
                            <small class="mt-2 text-sm text-gray-500">Primary work location for attendance verification</small>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-12 gap-4 mb-3">
                        <div class="md:col-span-6">
                            <div class="block text-sm font-medium text-gray-700">Status</div>
                            <div class="flex items-center form-switch">
                                <input type="hidden" name="is_active" value="0">
                                <input class="flex items-center-input" type="checkbox" name="is_active" value="1" 
                                       {{ old('is_active', $employee->is_active) ? 'checked' : '' }}>
                                <label class="flex items-center-label">Active Employee</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="lg:col-span-4">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Salary Information</h3>
                </div>
                <div class="p-6">
                    <div class="mb-3">
                        <div class="block text-sm font-medium text-gray-700 required">Salary Type</div>
                        <select name="salary_type" id="salary_type" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('salary_type') border-red-300 text-red-900 focus:ring-red-500 focus:border-red-500 @enderror" required>
                            <option value="">Select Type</option>
                            <option value="monthly" {{ old('salary_type', $employee->salary_type) == 'monthly' ? 'selected' : '' }}>Monthly</option>
                            <option value="hourly" {{ old('salary_type', $employee->salary_type) == 'hourly' ? 'selected' : '' }}>Hourly</option>
                            <option value="fixed" {{ old('salary_type', $employee->salary_type) == 'fixed' ? 'selected' : '' }}>Fixed</option>
                        </select>
                        @error('salary_type')
                            <div class="mt-2 text-sm text-red-600">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3" id="salary_amount_field" style="display: none;">
                        <div class="block text-sm font-medium text-gray-700">Monthly Salary</div>
                        <div class="flex rounded-md shadow-sm">
                            <span class="flex rounded-md shadow-sm-text">$</span>
                            <input type="number" name="salary_amount" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('salary_amount') border-red-300 text-red-900 focus:ring-red-500 focus:border-red-500 @enderror" 
                                   value="{{ old('salary_amount', $employee->salary_amount) }}" step="0.01" min="0">
                        </div>
                        @error('salary_amount')
                            <div class="mt-2 text-sm text-red-600">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3" id="hourly_rate_field" style="display: none;">
                        <div class="block text-sm font-medium text-gray-700">Hourly Rate</div>
                        <div class="flex rounded-md shadow-sm">
                            <span class="flex rounded-md shadow-sm-text">$</span>
                            <input type="number" name="hourly_rate" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('hourly_rate') border-red-300 text-red-900 focus:ring-red-500 focus:border-red-500 @enderror" 
                                   value="{{ old('hourly_rate', $employee->hourly_rate) }}" step="0.01" min="0">
                            <span class="flex rounded-md shadow-sm-text">/hour</span>
                        </div>
                        @error('hourly_rate')
                            <div class="mt-2 text-sm text-red-600">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 mt-4">
                <div class="p-6">
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 w-full">
                        <svg class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1"/>
                            <path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z"/>
                            <path d="M16 5l3 3"/>
                        </svg>
                        Update Employee
                    </button>
                    <a href="{{ route('employees.index') }}" class="inline-flex justify-center items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 w-full mt-2">
                        Cancel
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>
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
        const selectedLocationId = $('#location_id').data('selected');
        
        $.ajax({
            url: '/api/v1/locations/select',
            method: 'GET',
            headers: {
                'Authorization': 'Bearer ' + $('meta[name="api-token"]').attr('content'),
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(locations) {
                const select = $('#location_id');
                select.empty().append('<option value="">Select Location</option>');
                
                locations.forEach(function(location) {
                    const displayText = location.address 
                        ? `${location.name} (${location.address})`
                        : location.name;
                    const isSelected = location.id == selectedLocationId ? 'selected' : '';
                    select.append(`<option value="${location.id}" ${isSelected}>${displayText}</option>`);
                });
            },
            error: function(xhr) {
                console.error('Failed to load locations:', xhr);
            }
        });
    }
    
    loadLocations();
});
</script>
@endpush