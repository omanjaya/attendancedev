@extends('layouts.authenticated-unified')

@section('title', 'Create Holiday')

@section('page-content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900">
    <!-- Modern Header with Glassmorphism -->
    <div class="relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-r from-amber-600/10 via-orange-600/10 to-red-600/10"></div>
        <div class="relative px-4 sm:px-6 lg:px-8 py-8">
            <div class="max-w-4xl mx-auto">
                <div class="flex items-center justify-between">
                    <div class="space-y-2">
                        <div class="flex items-center space-x-3">
                            <div class="p-3 bg-gradient-to-r from-amber-500 to-orange-600 rounded-2xl shadow-lg shadow-amber-500/25">
                                <x-icons.calendar class="w-8 h-8 text-white" />
                            </div>
                            <div>
                                <h1 class="text-3xl font-bold bg-gradient-to-r from-gray-900 to-gray-600 dark:from-white dark:to-gray-300 bg-clip-text text-transparent">
                                    Create New Holiday
                                </h1>
                                <p class="text-lg text-gray-600 dark:text-gray-400">
                                    Add a new holiday to the calendar system
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex items-center space-x-3">
                        <a href="{{ route('holidays.index') }}" 
                           class="modern-btn-secondary group">
                            <x-icons.arrow-left class="w-5 h-5 mr-2 group-hover:-translate-x-1 transition-transform" />
                            Back to Holidays
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="px-4 sm:px-6 lg:px-8 pb-12">
        <div class="max-w-4xl mx-auto -mt-4">
            <div class="modern-card">
                <div class="card-header">
                    <div class="flex items-center space-x-3">
                        <div class="p-2 bg-gradient-to-r from-amber-500 to-orange-600 rounded-lg">
                            <x-icons.document-text class="w-6 h-6 text-white" />
                        </div>
                        <div>
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Holiday Information</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Enter the details for this holiday</p>
                        </div>
                    </div>
                </div>
                
                <form id="holidayForm" class="card-body space-y-8">
                    @csrf
                    
                    <!-- Basic Information Section -->
                    <div class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="md:col-span-2">
                                <label for="name" class="form-label required">
                                    <x-icons.tag class="w-4 h-4 mr-2" />
                                    Holiday Name
                                </label>
                                <input type="text" id="name" name="name" required
                                       class="form-input"
                                       placeholder="e.g., Christmas Day, Independence Day">
                            </div>
                            
                            <div class="md:col-span-2">
                                <label for="description" class="form-label">
                                    <x-icons.clipboard class="w-4 h-4 mr-2" />
                                    Description
                                </label>
                                <textarea id="description" name="description" rows="3"
                                          class="form-textarea"
                                          placeholder="Optional description of the holiday"></textarea>
                                <p class="form-help">Provide additional details about this holiday</p>
                            </div>
                            
                            <div>
                                <label for="date" class="form-label required">
                                    <x-icons.calendar class="w-4 h-4 mr-2" />
                                    Start Date
                                </label>
                                <input type="date" id="date" name="date" required class="form-input">
                                <p class="form-help">When does this holiday start?</p>
                            </div>
                            
                            <div>
                                <label for="end_date" class="form-label">
                                    <x-icons.calendar class="w-4 h-4 mr-2" />
                                    End Date (Optional)
                                </label>
                                <input type="date" id="end_date" name="end_date" class="form-input">
                                <p class="form-help">Leave empty for single-day holiday</p>
                            </div>
                            
                            <div>
                                <label for="type" class="form-label required">
                                    <x-icons.clipboard class="w-4 h-4 mr-2" />
                                    Holiday Type
                                </label>
                                <select id="type" name="type" required class="form-select">
                                    <option value="">Select Type</option>
                                    @foreach($types as $typeKey => $typeLabel)
                                        <option value="{{ $typeKey }}">{{ $typeLabel }}</option>
                                    @endforeach
                                </select>
                                <p class="form-help">Categorize this holiday</p>
                            </div>
                            
                            <div>
                                <label for="status" class="form-label required">
                                    <x-icons.check-circle class="w-4 h-4 mr-2" />
                                    Status
                                </label>
                                <select id="status" name="status" required class="form-select">
                                    @foreach($statuses as $statusKey => $statusLabel)
                                        <option value="{{ $statusKey }}" {{ $statusKey === 'active' ? 'selected' : '' }}>
                                            {{ $statusLabel }}
                                        </option>
                                    @endforeach
                                </select>
                                <p class="form-help">Current status of this holiday</p>
                            </div>
                            
                            <div>
                                <label for="color" class="form-label">
                                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4 2a2 2 0 00-2 2v11a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0013.414 6L10 2.586A2 2 0 008.586 2H4zm5 5a1 1 0 10-2 0v2H5a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2H9V7z" clip-rule="evenodd"></path>
                                    </svg>
                                    Color
                                </label>
                                <div class="flex items-center space-x-4">
                                    <input type="color" id="color" name="color" value="#dc3545"
                                           class="h-12 w-20 rounded-lg border border-gray-300 dark:border-gray-600 cursor-pointer shadow-sm">
                                    <div class="flex-1">
                                        <p class="text-sm text-gray-600 dark:text-gray-400">Choose a color for calendar display</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-500">This color will be used in the calendar view</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="md:col-span-2">
                                <label class="toggle-option">
                                    <input type="checkbox" id="is_paid" name="is_paid" checked class="hidden">
                                    <div class="toggle-content">
                                        <div class="toggle-switch checked"></div>
                                        <div class="toggle-info">
                                            <span class="toggle-title">Paid Holiday</span>
                                            <span class="toggle-description">Employees receive pay for this holiday</span>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Recurring Settings Section -->
                    <div class="settings-section">
                        <h4 class="section-title">
                            <x-icons.refresh class="w-5 h-5 mr-2" />
                            Recurring Settings
                        </h4>
                        
                        <div class="space-y-6">
                            <label class="toggle-option">
                                <input type="checkbox" id="is_recurring" name="is_recurring" class="hidden">
                                <div class="toggle-content">
                                    <div class="toggle-switch"></div>
                                    <div class="toggle-info">
                                        <span class="toggle-title">Recurring Holiday</span>
                                        <span class="toggle-description">This holiday repeats annually</span>
                                    </div>
                                </div>
                            </label>
                            
                            <div id="recurringOptions" class="hidden space-y-4 pl-6 border-l-2 border-blue-200 dark:border-blue-700">
                                <div>
                                    <label for="recurring_type" class="form-label">
                                        <x-icons.cog class="w-4 h-4 mr-2" />
                                        Recurring Type
                                    </label>
                                    <select id="recurring_type" name="recurring_pattern[type]" class="form-select">
                                        <option value="yearly">Yearly (same date)</option>
                                        <option value="relative">Relative (e.g., 2nd Monday of March)</option>
                                    </select>
                                    <p class="form-help">How does this holiday repeat?</p>
                                </div>
                                
                                <div id="relativeOptions" class="hidden">
                                    <label class="form-label">
                                        <x-icons.calendar class="w-4 h-4 mr-2" />
                                        Relative Position
                                    </label>
                                    <div class="grid grid-cols-3 gap-3">
                                        <select name="recurring_pattern[week]" class="form-select">
                                            <option value="1">1st</option>
                                            <option value="2">2nd</option>
                                            <option value="3">3rd</option>
                                            <option value="4">4th</option>
                                            <option value="-1">Last</option>
                                        </select>
                                        <select name="recurring_pattern[day]" class="form-select">
                                            <option value="1">Monday</option>
                                            <option value="2">Tuesday</option>
                                            <option value="3">Wednesday</option>
                                            <option value="4">Thursday</option>
                                            <option value="5">Friday</option>
                                            <option value="6">Saturday</option>
                                            <option value="0">Sunday</option>
                                        </select>
                                        <select name="recurring_pattern[month]" class="form-select">
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
                                    <p class="form-help">For example: "2nd Monday of March"</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Role-specific Settings Section -->
                    <div class="settings-section">
                        <h4 class="section-title">
                            <x-icons.users class="w-5 h-5 mr-2" />
                            Affected Roles
                        </h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">Select which employee roles are affected by this holiday. Leave empty to apply to all roles.</p>
                        
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <label class="role-checkbox">
                                <input type="checkbox" name="affected_roles[]" value="guru" class="hidden">
                                <div class="role-option">
                                    <div class="role-icon">
                                        <x-icons.user class="w-5 h-5" />
                                    </div>
                                    <span class="role-label">Teachers (Guru)</span>
                                </div>
                            </label>
                            
                            <label class="role-checkbox">
                                <input type="checkbox" name="affected_roles[]" value="pegawai" class="hidden">
                                <div class="role-option">
                                    <div class="role-icon">
                                        <x-icons.briefcase class="w-5 h-5" />
                                    </div>
                                    <span class="role-label">Staff (Pegawai)</span>
                                </div>
                            </label>
                            
                            <label class="role-checkbox">
                                <input type="checkbox" name="affected_roles[]" value="kepala_sekolah" class="hidden">
                                <div class="role-option">
                                    <div class="role-icon">
                                        <x-icons.building class="w-5 h-5" />
                                    </div>
                                    <span class="role-label">Principal</span>
                                </div>
                            </label>
                            
                            <label class="role-checkbox">
                                <input type="checkbox" name="affected_roles[]" value="admin" class="hidden">
                                <div class="role-option">
                                    <div class="role-icon">
                                        <x-icons.cog class="w-5 h-5" />
                                    </div>
                                    <span class="role-label">Admin</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="form-navigation">
                        <div class="flex items-center justify-end space-x-4">
                            <a href="{{ route('holidays.index') }}" 
                               class="modern-btn-secondary">
                                <x-icons.x class="w-5 h-5 mr-2" />
                                Cancel
                            </a>
                            <button type="submit" 
                                    class="modern-btn-success">
                                <x-icons.check class="w-5 h-5 mr-2" />
                                Create Holiday
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
/* Holiday Form Specific Styles */
.settings-section {
    @apply bg-gray-50/50 dark:bg-gray-800/50 backdrop-blur-sm rounded-xl p-6 border border-gray-200/50 dark:border-gray-700/50;
}

.section-title {
    @apply flex items-center text-lg font-semibold text-gray-900 dark:text-white mb-4;
}

.role-checkbox {
    @apply cursor-pointer;
}

.role-option {
    @apply flex items-center space-x-3 p-4 bg-white dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 hover:border-blue-300 dark:hover:border-blue-500 transition-all duration-200;
}

.role-checkbox input:checked + .role-option {
    @apply border-blue-500 bg-blue-50 dark:bg-blue-900/20;
}

.role-icon {
    @apply p-2 bg-gray-100 dark:bg-gray-600 rounded-lg;
}

.role-checkbox input:checked + .role-option .role-icon {
    @apply bg-blue-500 text-white;
}

.role-label {
    @apply text-sm font-medium text-gray-700 dark:text-gray-300;
}

.role-checkbox input:checked + .role-option .role-label {
    @apply text-blue-700 dark:text-blue-300;
}

/* Form navigation */
.form-navigation {
    @apply border-t border-gray-200 dark:border-gray-700 pt-6 mt-8;
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    initializeForm();
    setupEventListeners();
});

function initializeForm() {
    // Set minimum date to today
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('date').min = today;
    document.getElementById('end_date').min = today;
    
    // Initialize toggle switches
    document.querySelectorAll('.toggle-option').forEach(toggle => {
        const checkbox = toggle.querySelector('input[type="checkbox"]');
        const toggleSwitch = toggle.querySelector('.toggle-switch');
        
        if (checkbox.checked) {
            toggleSwitch.classList.add('checked');
            toggle.classList.add('selected');
        }
    });
}

function setupEventListeners() {
    // Toggle options
    document.querySelectorAll('.toggle-option').forEach(toggle => {
        toggle.addEventListener('click', function() {
            const checkbox = this.querySelector('input[type="checkbox"]');
            const toggleSwitch = this.querySelector('.toggle-switch');
            checkbox.checked = !checkbox.checked;
            this.classList.toggle('selected', checkbox.checked);
            if (toggleSwitch) {
                toggleSwitch.classList.toggle('checked', checkbox.checked);
            }
            
            // Handle recurring options visibility
            if (checkbox.id === 'is_recurring') {
                const options = document.getElementById('recurringOptions');
                if (checkbox.checked) {
                    options.classList.remove('hidden');
                } else {
                    options.classList.add('hidden');
                }
            }
        });
    });
    
    // Role checkboxes
    document.querySelectorAll('.role-checkbox').forEach(roleOption => {
        roleOption.addEventListener('click', function() {
            const checkbox = this.querySelector('input[type="checkbox"]');
            checkbox.checked = !checkbox.checked;
        });
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

    // Ensure end date is after start date
    document.getElementById('date').addEventListener('change', function() {
        const endDateInput = document.getElementById('end_date');
        endDateInput.min = this.value;
        if (endDateInput.value && endDateInput.value < this.value) {
            endDateInput.value = this.value;
        }
    });
    
    // Handle form submission
    document.getElementById('holidayForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const submitBtn = e.target.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        try {
            // Show loading state
            submitBtn.innerHTML = '<div class="loading-spinner w-5 h-5 mr-2"></div>Creating...';
            submitBtn.disabled = true;
            
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
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Holiday created successfully!', 'success');
                    setTimeout(() => {
                        window.location.href = '{{ route('holidays.index') }}';
                    }, 1500);
                } else {
                    throw new Error(data.message || 'Failed to create holiday');
                }
            })
            .catch(error => {
                console.error('Error creating holiday:', error);
                showNotification('Failed to create holiday: ' + error.message, 'error');
                
                // Restore button state
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        } catch (error) {
            console.error('Error:', error);
            showNotification('An error occurred while creating the holiday.', 'error');
            
            // Restore button state
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    });
}

function showNotification(message, type = 'info') {
    // Modern notification system
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 z-50 p-4 rounded-2xl shadow-2xl transition-all transform max-w-md ${
        type === 'success' ? 'bg-gradient-to-r from-green-500 to-emerald-600 text-white' :
        type === 'error' ? 'bg-gradient-to-r from-red-500 to-rose-600 text-white' :
        'bg-gradient-to-r from-blue-500 to-indigo-600 text-white'
    }`;
    
    notification.innerHTML = `
        <div class="flex items-center space-x-3">
            <div class="flex-shrink-0">
                ${type === 'success' ? '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>' :
                  type === 'error' ? '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>' :
                  '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>'}
            </div>
            <div class="flex-1">
                <p class="font-medium">${message}</p>
            </div>
            <button onclick="this.parentNode.parentNode.remove()" class="flex-shrink-0 text-white/80 hover:text-white">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.style.transform = 'translateX(100%)';
            notification.style.opacity = '0';
            setTimeout(() => notification.remove(), 300);
        }
    }, 5000);
}
</script>
@endpush
@endsection