@extends('layouts.app')

@section('title', 'Create Holiday')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center space-x-4">
            <a href="{{ route('holidays.index') }}" 
               class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Holidays
            </a>
        </div>
        <h1 class="text-3xl font-bold text-gray-900 mt-4">Create New Holiday</h1>
        <p class="mt-2 text-sm text-gray-600">Add a new holiday to the calendar system</p>
    </div>

    <!-- Form -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <form id="holidayForm" class="p-6 space-y-6">
            @csrf
            
            <!-- Basic Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                        Holiday Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="name" name="name" required
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                           placeholder="e.g., Christmas Day, Independence Day">
                </div>
                
                <div class="md:col-span-2">
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
                        Description
                    </label>
                    <textarea id="description" name="description" rows="3"
                              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                              placeholder="Optional description of the holiday"></textarea>
                </div>
                
                <div>
                    <label for="date" class="block text-sm font-medium text-gray-700 mb-1">
                        Start Date <span class="text-red-500">*</span>
                    </label>
                    <input type="date" id="date" name="date" required
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                
                <div>
                    <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">
                        End Date (Optional)
                    </label>
                    <input type="date" id="end_date" name="end_date"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <p class="text-xs text-gray-500 mt-1">Leave empty for single-day holiday</p>
                </div>
                
                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700 mb-1">
                        Holiday Type <span class="text-red-500">*</span>
                    </label>
                    <select id="type" name="type" required
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Select Type</option>
                        @foreach($types as $typeKey => $typeLabel)
                            <option value="{{ $typeKey }}">{{ $typeLabel }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">
                        Status <span class="text-red-500">*</span>
                    </label>
                    <select id="status" name="status" required
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @foreach($statuses as $statusKey => $statusLabel)
                            <option value="{{ $statusKey }}" {{ $statusKey === 'active' ? 'selected' : '' }}>
                                {{ $statusLabel }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div>
                    <label for="color" class="block text-sm font-medium text-gray-700 mb-1">
                        Color
                    </label>
                    <div class="flex items-center space-x-3">
                        <input type="color" id="color" name="color" value="#dc3545"
                               class="h-10 w-16 rounded border border-gray-300 cursor-pointer">
                        <span class="text-sm text-gray-500">Choose a color for calendar display</span>
                    </div>
                </div>
                
                <div class="flex items-center">
                    <div class="flex h-5 items-center">
                        <input id="is_paid" name="is_paid" type="checkbox" checked
                               class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    </div>
                    <div class="ml-3 text-sm">
                        <label for="is_paid" class="font-medium text-gray-700">Paid Holiday</label>
                        <p class="text-gray-500">Employees receive pay for this holiday</p>
                    </div>
                </div>
            </div>

            <!-- Recurring Settings -->
            <div class="border-t border-gray-200 pt-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Recurring Settings</h3>
                
                <div class="space-y-4">
                    <div class="flex items-center">
                        <div class="flex h-5 items-center">
                            <input id="is_recurring" name="is_recurring" type="checkbox" 
                                   class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="is_recurring" class="font-medium text-gray-700">Recurring Holiday</label>
                            <p class="text-gray-500">This holiday repeats annually</p>
                        </div>
                    </div>
                    
                    <div id="recurringOptions" class="hidden grid grid-cols-1 md:grid-cols-2 gap-4 pl-7">
                        <div>
                            <label for="recurring_type" class="block text-sm font-medium text-gray-700 mb-1">
                                Recurring Type
                            </label>
                            <select id="recurring_type" name="recurring_pattern[type]"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="yearly">Yearly (same date)</option>
                                <option value="relative">Relative (e.g., 2nd Monday of March)</option>
                            </select>
                        </div>
                        
                        <div id="relativeOptions" class="hidden">
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Relative Position
                            </label>
                            <div class="grid grid-cols-3 gap-2">
                                <select name="recurring_pattern[week]" class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="1">1st</option>
                                    <option value="2">2nd</option>
                                    <option value="3">3rd</option>
                                    <option value="4">4th</option>
                                    <option value="-1">Last</option>
                                </select>
                                <select name="recurring_pattern[day]" class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="1">Monday</option>
                                    <option value="2">Tuesday</option>
                                    <option value="3">Wednesday</option>
                                    <option value="4">Thursday</option>
                                    <option value="5">Friday</option>
                                    <option value="6">Saturday</option>
                                    <option value="0">Sunday</option>
                                </select>
                                <select name="recurring_pattern[month]" class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="1">January</option>
                                    <option value="2">February</option>
                                    <option value="3">March</option>
                                    <option value="4">April</option>
                                    <option value="5">May</option>
                                    <option value="6">June</option>
                                    <option value="7">July</option>
                                    <option value="8">August</option>
                                    <option value="9">September</option>
                                    <option value="10">October</option>
                                    <option value="11">November</option>
                                    <option value="12">December</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Role-specific Settings -->
            <div class="border-t border-gray-200 pt-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Affected Roles</h3>
                <p class="text-sm text-gray-600 mb-4">Select which employee roles are affected by this holiday. Leave empty to apply to all roles.</p>
                
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="affected_roles[]" value="guru" 
                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <span class="ml-2 text-sm text-gray-700">Teachers (Guru)</span>
                    </label>
                    
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="affected_roles[]" value="pegawai" 
                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <span class="ml-2 text-sm text-gray-700">Staff (Pegawai)</span>
                    </label>
                    
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="affected_roles[]" value="kepala_sekolah" 
                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <span class="ml-2 text-sm text-gray-700">Principal</span>
                    </label>
                    
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="affected_roles[]" value="admin" 
                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <span class="ml-2 text-sm text-gray-700">Admin</span>
                    </label>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                <a href="{{ route('holidays.index') }}" 
                   class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Cancel
                </a>
                <button type="submit" 
                        class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Create Holiday
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Toggle recurring options
document.getElementById('is_recurring').addEventListener('change', function() {
    const options = document.getElementById('recurringOptions');
    if (this.checked) {
        options.classList.remove('hidden');
    } else {
        options.classList.add('hidden');
    }
});

// Toggle relative options
document.getElementById('recurring_type').addEventListener('change', function() {
    const relativeOptions = document.getElementById('relativeOptions');
    if (this.value === 'relative') {
        relativeOptions.classList.remove('hidden');
    } else {
        relativeOptions.classList.add('hidden');
    }
});

// Handle form submission
document.getElementById('holidayForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Prepare form data
    const formData = new FormData(this);
    
    // Convert checkboxes to proper values
    formData.set('is_paid', document.getElementById('is_paid').checked ? '1' : '0');
    formData.set('is_recurring', document.getElementById('is_recurring').checked ? '1' : '0');
    
    // Handle affected roles
    const affectedRoles = [];
    document.querySelectorAll('input[name="affected_roles[]"]:checked').forEach(checkbox => {
        affectedRoles.push(checkbox.value);
    });
    formData.delete('affected_roles[]');
    affectedRoles.forEach(role => {
        formData.append('affected_roles[]', role);
    });
    
    // Submit form
    fetch('{{ route('holidays.store') }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Holiday created successfully!');
            window.location.href = '{{ route('holidays.index') }}';
        } else {
            // Display validation errors
            if (data.errors) {
                let errorMessage = 'Validation errors:\n';
                for (const field in data.errors) {
                    errorMessage += `- ${data.errors[field][0]}\n`;
                }
                alert(errorMessage);
            } else {
                alert('Error: ' + data.message);
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while creating the holiday.');
    });
});

// Set minimum date to today
document.getElementById('date').min = new Date().toISOString().split('T')[0];
document.getElementById('end_date').min = new Date().toISOString().split('T')[0];

// Ensure end date is after start date
document.getElementById('date').addEventListener('change', function() {
    const endDateInput = document.getElementById('end_date');
    endDateInput.min = this.value;
    if (endDateInput.value && endDateInput.value < this.value) {
        endDateInput.value = this.value;
    }
});
</script>
@endsection