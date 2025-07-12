@extends('layouts.authenticated')

@section('title', 'Add New Employee')

@section('page-content')
<div class="py-6">
    <div class="max-w-7xl mx-auto px-8">
        <!-- Page Header -->
        <div class="mb-8 mt-6">
            <h2 class="text-3xl font-bold text-gray-900">Add New Employee</h2>
            <div class="text-gray-600 mt-1">Employees - Create new employee profile</div>
        </div>

        <form action="{{ route('employees.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            
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
                                           value="{{ old('employee_id') }}" placeholder="EMP001" required>
                                    @error('employee_id')
                                        <div class="mt-2 text-sm text-red-600">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Employee Type <span class="text-red-500">*</span></label>
                                    <select name="employee_type" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('employee_type') border-red-300 text-red-900 focus:ring-red-500 focus:border-red-500 @enderror" required>
                                        <option value="">Select Type</option>
                                        <option value="permanent" {{ old('employee_type') == 'permanent' ? 'selected' : '' }}>Permanent</option>
                                        <option value="honorary" {{ old('employee_type') == 'honorary' ? 'selected' : '' }}>Honorary Teacher</option>
                                        <option value="staff" {{ old('employee_type') == 'staff' ? 'selected' : '' }}>Staff</option>
                                    </select>
                                    @error('employee_type')
                                        <div class="mt-2 text-sm text-red-600">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">First Name <span class="text-red-500">*</span></label>
                                    <input type="text" name="first_name" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('first_name') border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500 @enderror" 
                                           value="{{ old('first_name') }}" required>
                                    @error('first_name')
                                        <div class="mt-2 text-sm text-red-600">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Last Name <span class="text-red-500">*</span></label>
                                    <input type="text" name="last_name" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('last_name') border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500 @enderror" 
                                           value="{{ old('last_name') }}" required>
                                    @error('last_name')
                                        <div class="mt-2 text-sm text-red-600">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Email Address <span class="text-red-500">*</span></label>
                                    <input type="email" name="email" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('email') border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500 @enderror" 
                                           value="{{ old('email') }}" required>
                                    @error('email')
                                        <div class="mt-2 text-sm text-red-600">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                                    <input type="text" name="phone" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('phone') border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500 @enderror" 
                                           value="{{ old('phone') }}" placeholder="+1234567890">
                                    @error('phone')
                                        <div class="mt-2 text-sm text-red-600">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Employee Photo</label>
                                    <input type="file" name="photo" id="photo" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('photo') border-red-300 text-red-900 focus:ring-red-500 focus:border-red-500 @enderror" 
                                           accept="image/jpeg,image/png,image/jpg">
                                    @error('photo')
                                        <div class="mt-2 text-sm text-red-600">{{ $message }}</div>
                                    @enderror
                                    <p class="mt-2 text-sm text-gray-500">Upload a photo for face recognition. Max size: 2MB. Formats: JPEG, PNG, JPG</p>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Photo Preview</label>
                                    <div class="flex items-center justify-center">
                                        <img id="photoPreview" src="https://ui-avatars.com/api/?name=Employee&background=206bc4&color=fff&size=150" 
                                             alt="Employee Photo" class="w-24 h-24 rounded-full object-cover border-2 border-gray-200">
                                    </div>
                                    <p class="text-sm text-gray-500 text-center mt-2">
                                        This will be used for face recognition
                                    </p>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Hire Date <span class="text-red-500">*</span></label>
                                    <input type="date" name="hire_date" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('hire_date') border-red-300 text-red-900 focus:ring-red-500 focus:border-red-500 @enderror" 
                                           value="{{ old('hire_date', date('Y-m-d')) }}" required>
                                    @error('hire_date')
                                        <div class="mt-2 text-sm text-red-600">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Role <span class="text-red-500">*</span></label>
                                    <select name="role" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('role') border-red-300 text-red-900 focus:ring-red-500 focus:border-red-500 @enderror" required>
                                        <option value="">Select Role</option>
                                        @foreach($roles as $role)
                                            <option value="{{ $role->name }}" {{ old('role') == $role->name ? 'selected' : '' }}>
                                                {{ ucfirst($role->name) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('role')
                                        <div class="mt-2 text-sm text-red-600">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Work Location</label>
                                <select name="location_id" id="location_id" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('location_id') border-red-300 text-red-900 focus:ring-red-500 focus:border-red-500 @enderror">
                                    <option value="">Select Location</option>
                                </select>
                                @error('location_id')
                                    <div class="mt-2 text-sm text-red-600">{{ $message }}</div>
                                @enderror
                                <p class="mt-2 text-sm text-gray-500">Primary work location for attendance verification</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 mt-6">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900">Login Credentials</h3>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Password <span class="text-red-500">*</span></label>
                                    <input type="password" name="password" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('password') border-red-300 text-red-900 focus:ring-red-500 focus:border-red-500 @enderror" required>
                                    @error('password')
                                        <div class="mt-2 text-sm text-red-600">{{ $message }}</div>
                                    @enderror
                                    <p class="mt-2 text-sm text-gray-500">Minimum 8 characters</p>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Confirm Password <span class="text-red-500">*</span></label>
                                    <input type="password" name="password_confirmation" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" required>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900">Salary Information</h3>
                        </div>
                        <div class="p-6">
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Salary Type <span class="text-red-500">*</span></label>
                                <select name="salary_type" id="salary_type" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('salary_type') border-red-300 text-red-900 focus:ring-red-500 focus:border-red-500 @enderror" required>
                                    <option value="">Select Type</option>
                                    <option value="monthly" {{ old('salary_type') == 'monthly' ? 'selected' : '' }}>Monthly</option>
                                    <option value="hourly" {{ old('salary_type') == 'hourly' ? 'selected' : '' }}>Hourly</option>
                                    <option value="fixed" {{ old('salary_type') == 'fixed' ? 'selected' : '' }}>Fixed</option>
                                </select>
                                @error('salary_type')
                                    <div class="mt-2 text-sm text-red-600">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="mb-6" id="salary_amount_field" style="display: none;">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Monthly Salary</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 sm:text-sm">$</span>
                                    </div>
                                    <input type="number" name="salary_amount" class="block w-full pl-7 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('salary_amount') border-red-300 text-red-900 focus:ring-red-500 focus:border-red-500 @enderror" 
                                           value="{{ old('salary_amount') }}" step="0.01" min="0">
                                </div>
                                @error('salary_amount')
                                    <div class="mt-2 text-sm text-red-600">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="mb-6" id="hourly_rate_field" style="display: none;">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Hourly Rate</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 sm:text-sm">$</span>
                                    </div>
                                    <input type="number" name="hourly_rate" class="block w-full pl-7 pr-16 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('hourly_rate') border-red-300 text-red-900 focus:ring-red-500 focus:border-red-500 @enderror" 
                                           value="{{ old('hourly_rate') }}" step="0.01" min="0">
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 sm:text-sm">/hour</span>
                                    </div>
                                </div>
                                @error('hourly_rate')
                                    <div class="mt-2 text-sm text-red-600">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 mt-6">
                        <div class="p-6">
                            <div class="flex flex-col space-y-3">
                                <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    <svg class="w-4 h-4 mr-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                        <path d="M12 5l0 14"/>
                                        <path d="M5 12l14 0"/>
                                    </svg>
                                    Create Employee
                                </button>
                                <a href="{{ route('employees.index') }}" class="w-full inline-flex justify-center items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    Cancel
                                </a>
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
                select.empty().append('<option value="">Select Location</option>');
                
                locations.forEach(function(location) {
                    const displayText = location.address 
                        ? `${location.name} (${location.address})`
                        : location.name;
                    select.append(`<option value="${location.id}">${displayText}</option>`);
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