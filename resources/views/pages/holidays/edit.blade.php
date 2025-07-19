@extends('layouts.app')

@section('title', 'Edit Holiday')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center space-x-4">
            <a href="{{ route('holidays.show', $holiday) }}" 
               class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Holiday
            </a>
        </div>
        <h1 class="text-3xl font-bold text-gray-900 mt-4">Edit Holiday</h1>
        <p class="mt-2 text-sm text-gray-600">Update holiday information and settings</p>
    </div>

    <!-- Form -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <form id="holidayForm" class="p-6 space-y-6">
            @csrf
            @method('PUT')
            
            <!-- Basic Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                        Holiday Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="name" name="name" required value="{{ $holiday->name }}"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                           placeholder="e.g., Christmas Day, Independence Day">
                </div>
                
                <div class="md:col-span-2">
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
                        Description
                    </label>
                    <textarea id="description" name="description" rows="3"
                              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                              placeholder="Optional description of the holiday">{{ $holiday->description }}</textarea>
                </div>
                
                <div>
                    <label for="date" class="block text-sm font-medium text-gray-700 mb-1">
                        Start Date <span class="text-red-500">*</span>
                    </label>
                    <input type="date" id="date" name="date" required value="{{ $holiday->date->format('Y-m-d') }}"
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                
                <div>
                    <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">
                        End Date (Optional)
                    </label>
                    <input type="date" id="end_date" name="end_date" 
                           value="{{ $holiday->end_date ? $holiday->end_date->format('Y-m-d') : '' }}"
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
                            <option value="{{ $typeKey }}" {{ $holiday->type === $typeKey ? 'selected' : '' }}>
                                {{ $typeLabel }}
                            </option>
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
                            <option value="{{ $statusKey }}" {{ $holiday->status === $statusKey ? 'selected' : '' }}>
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
                        <input type="color" id="color" name="color" value="{{ $holiday->color ?? '#dc3545' }}"
                               class="h-10 w-16 rounded border border-gray-300 cursor-pointer">
                        <span class="text-sm text-gray-500">Choose a color for calendar display</span>
                    </div>
                </div>
                
                <div class="flex items-center">
                    <div class="flex h-5 items-center">
                        <input id="is_paid" name="is_paid" type="checkbox" {{ $holiday->is_paid ? 'checked' : '' }}
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
                            <input id="is_recurring" name="is_recurring" type="checkbox" {{ $holiday->is_recurring ? 'checked' : '' }}
                                   class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="is_recurring" class="font-medium text-gray-700">Recurring Holiday</label>
                            <p class="text-gray-500">This holiday repeats annually</p>
                        </div>
                    </div>
                    
                    <div id="recurringOptions" class="{{ $holiday->is_recurring ? '' : 'hidden' }} grid grid-cols-1 md:grid-cols-2 gap-4 pl-7">
                        @php
                            $pattern = is_string($holiday->recurring_pattern) ? json_decode($holiday->recurring_pattern, true) : ($holiday->recurring_pattern ?? []);
                            $patternType = $pattern['type'] ?? 'yearly';
                        @endphp
                        
                        <div>
                            <label for="recurring_type" class="block text-sm font-medium text-gray-700 mb-1">
                                Recurring Type
                            </label>
                            <select id="recurring_type" name="recurring_pattern[type]"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="yearly" {{ $patternType === 'yearly' ? 'selected' : '' }}>Yearly (same date)</option>
                                <option value="relative" {{ $patternType === 'relative' ? 'selected' : '' }}>Relative (e.g., 2nd Monday of March)</option>
                            </select>
                        </div>
                        
                        <div id="relativeOptions" class="{{ $patternType === 'relative' ? '' : 'hidden' }}">
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Relative Position
                            </label>
                            <div class="grid grid-cols-3 gap-2">
                                <select name="recurring_pattern[week]" class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="1" {{ ($pattern['week'] ?? null) == 1 ? 'selected' : '' }}>1st</option>
                                    <option value="2" {{ ($pattern['week'] ?? null) == 2 ? 'selected' : '' }}>2nd</option>
                                    <option value="3" {{ ($pattern['week'] ?? null) == 3 ? 'selected' : '' }}>3rd</option>
                                    <option value="4" {{ ($pattern['week'] ?? null) == 4 ? 'selected' : '' }}>4th</option>
                                    <option value="-1" {{ ($pattern['week'] ?? null) == -1 ? 'selected' : '' }}>Last</option>
                                </select>
                                <select name="recurring_pattern[day]" class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="1" {{ ($pattern['day'] ?? null) == 1 ? 'selected' : '' }}>Monday</option>
                                    <option value="2" {{ ($pattern['day'] ?? null) == 2 ? 'selected' : '' }}>Tuesday</option>
                                    <option value="3" {{ ($pattern['day'] ?? null) == 3 ? 'selected' : '' }}>Wednesday</option>
                                    <option value="4" {{ ($pattern['day'] ?? null) == 4 ? 'selected' : '' }}>Thursday</option>
                                    <option value="5" {{ ($pattern['day'] ?? null) == 5 ? 'selected' : '' }}>Friday</option>
                                    <option value="6" {{ ($pattern['day'] ?? null) == 6 ? 'selected' : '' }}>Saturday</option>
                                    <option value="0" {{ ($pattern['day'] ?? null) == 0 ? 'selected' : '' }}>Sunday</option>
                                </select>
                                <select name="recurring_pattern[month]" class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="1" {{ ($pattern['month'] ?? null) == 1 ? 'selected' : '' }}>January</option>
                                    <option value="2" {{ ($pattern['month'] ?? null) == 2 ? 'selected' : '' }}>February</option>
                                    <option value="3" {{ ($pattern['month'] ?? null) == 3 ? 'selected' : '' }}>March</option>
                                    <option value="4" {{ ($pattern['month'] ?? null) == 4 ? 'selected' : '' }}>April</option>
                                    <option value="5" {{ ($pattern['month'] ?? null) == 5 ? 'selected' : '' }}>May</option>
                                    <option value="6" {{ ($pattern['month'] ?? null) == 6 ? 'selected' : '' }}>June</option>
                                    <option value="7" {{ ($pattern['month'] ?? null) == 7 ? 'selected' : '' }}>July</option>
                                    <option value="8" {{ ($pattern['month'] ?? null) == 8 ? 'selected' : '' }}>August</option>
                                    <option value="9" {{ ($pattern['month'] ?? null) == 9 ? 'selected' : '' }}>September</option>
                                    <option value="10" {{ ($pattern['month'] ?? null) == 10 ? 'selected' : '' }}>October</option>
                                    <option value="11" {{ ($pattern['month'] ?? null) == 11 ? 'selected' : '' }}>November</option>
                                    <option value="12" {{ ($pattern['month'] ?? null) == 12 ? 'selected' : '' }}>December</option>
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
                
                @php
                    $affectedRoles = $holiday->affected_roles ?? [];
                @endphp
                
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="affected_roles[]" value="guru" 
                               {{ in_array('guru', $affectedRoles) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <span class="ml-2 text-sm text-gray-700">Teachers (Guru)</span>
                    </label>
                    
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="affected_roles[]" value="pegawai" 
                               {{ in_array('pegawai', $affectedRoles) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <span class="ml-2 text-sm text-gray-700">Staff (Pegawai)</span>
                    </label>
                    
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="affected_roles[]" value="kepala_sekolah" 
                               {{ in_array('kepala_sekolah', $affectedRoles) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <span class="ml-2 text-sm text-gray-700">Principal</span>
                    </label>
                    
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="affected_roles[]" value="admin" 
                               {{ in_array('admin', $affectedRoles) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <span class="ml-2 text-sm text-gray-700">Admin</span>
                    </label>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                <a href="{{ route('holidays.show', $holiday) }}" 
                   class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Cancel
                </a>
                <button type="submit" 
                        class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Update Holiday
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
    fetch('{{ route('holidays.update', $holiday) }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Holiday updated successfully!');
            window.location.href = '{{ route('holidays.show', $holiday) }}';
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
        alert('An error occurred while updating the holiday.');
    });
});

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