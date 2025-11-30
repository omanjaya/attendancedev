<form action="{{ route('leave.store') }}" method="POST" class="space-y-6">
    @csrf
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <div class="backdrop-blur-xl bg-white/25 border border-white/20 rounded-3xl p-6 shadow-2xl">
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-900">Leave Request Details</h3>
                    <p class="text-gray-600 text-sm mt-1">Fill in your leave request information</p>
                </div>
                
                <div class="space-y-6">
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div class="space-y-2">
                            <label for="leave_type_id" class="block text-sm font-medium text-gray-700">Leave Type <span class="text-red-500">*</span></label>
                            <select name="leave_type_id" id="leave_type_id" required 
                                    class="w-full px-4 py-3 border border-gray-300/50 rounded-xl bg-white/50 text-gray-900 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all duration-300">
                                <option value="">Select Leave Type</option>
                                @if(isset($leaveTypes))
                                    @foreach($leaveTypes as $leaveType)
                                        <option value="{{ $leaveType->id }}" {{ old('leave_type_id') == $leaveType->id ? 'selected' : '' }}>
                                            {{ $leaveType->display_name ?? $leaveType->name }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                            @error('leave_type_id')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Current Balance</label>
                            <div id="leave-balance" class="p-3 bg-gray-50/50 rounded-xl border border-gray-200/50 text-sm text-gray-600">
                                Select a leave type to view balance
                            </div>
                        </div>
                    </div>
                
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div class="space-y-2">
                            <label for="start_date" class="block text-sm font-medium text-gray-700">Start Date <span class="text-red-500">*</span></label>
                            <input type="date" name="start_date" id="start_date" 
                                   value="{{ old('start_date') }}" min="{{ date('Y-m-d') }}" required 
                                   class="w-full px-4 py-3 border border-gray-300/50 rounded-xl bg-white/50 text-gray-900 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all duration-300">
                            @error('start_date')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <div class="space-y-2">
                            <label for="end_date" class="block text-sm font-medium text-gray-700">End Date <span class="text-red-500">*</span></label>
                            <input type="date" name="end_date" id="end_date" 
                                   value="{{ old('end_date') }}" min="{{ date('Y-m-d') }}" required 
                                   class="w-full px-4 py-3 border border-gray-300/50 rounded-xl bg-white/50 text-gray-900 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all duration-300">
                            @error('end_date')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Working Days</label>
                            <div id="working-days" class="p-3 bg-gray-50/50 rounded-xl border border-gray-200/50 text-sm text-gray-600">
                                Select dates to calculate
                            </div>
                        </div>
                        
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Emergency Leave</label>
                            <div class="flex items-center space-x-3">
                                <input type="hidden" name="is_emergency" value="0">
                                <input type="checkbox" name="is_emergency" value="1" {{ old('is_emergency') ? 'checked' : '' }}
                                       class="w-4 h-4 text-emerald-600 bg-gray-100 border-gray-300 rounded focus:ring-emerald-500 focus:ring-2">
                                <div>
                                    <span class="text-sm font-medium text-gray-700">Emergency Leave</span>
                                    <p class="text-xs text-gray-500">Check if this is an emergency leave request</p>
                                </div>
                            </div>
                        </div>
                    </div>
                
                    <div class="space-y-2">
                        <label for="reason" class="block text-sm font-medium text-gray-700">Reason for Leave</label>
                        <textarea name="reason" id="reason" rows="4" 
                                  placeholder="Please provide a reason for your leave request..."
                                  class="w-full px-4 py-3 border border-gray-300/50 rounded-xl bg-white/50 text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all duration-300">{{ old('reason') }}</textarea>
                        @error('reason')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                        <p class="text-xs text-gray-500">Optional: Provide additional details about your leave request</p>
                    </div>
                </div>
            </div>
        </div>
    
        <div class="space-y-6">
            <div class="backdrop-blur-xl bg-white/25 border border-white/20 rounded-3xl p-6 shadow-2xl">
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-900">Leave Balance Summary</h3>
                    <p class="text-gray-600 text-sm mt-1">Your available leave days</p>
                </div>
                
                <div id="balance-summary" class="space-y-4">
                    @if(isset($leaveBalances) && $leaveBalances->count() > 0)
                        @foreach($leaveBalances as $balance)
                        <div class="p-4 bg-white/20 rounded-xl border border-white/10">
                            <div class="flex justify-between items-center mb-3">
                                <span class="font-medium text-gray-900">{{ $balance->leaveType->name }}</span>
                                <span class="px-3 py-1 text-xs font-medium bg-emerald-100 text-emerald-800 rounded-full">{{ $balance->remaining_days }} days left</span>
                            </div>
                            <div class="w-full bg-gray-200/50 rounded-full h-2">
                                @php
                                    $percentage = $balance->allocated_days > 0 ? ($balance->remaining_days / $balance->allocated_days) * 100 : 0;
                                    $colorClass = $percentage > 50 ? 'bg-green-500' : ($percentage > 20 ? 'bg-yellow-500' : 'bg-red-500');
                                @endphp
                                <div class="{{ $colorClass }} h-2 rounded-full transition-all" style="width: {{ $percentage }}%"></div>
                            </div>
                            <p class="text-xs text-gray-500 mt-2">
                                {{ $balance->used_days }} used of {{ $balance->allocated_days }} allocated
                            </p>
                        </div>
                        @endforeach
                    @else
                        <div class="text-center py-8 text-gray-500">
                            <svg class="w-12 h-12 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="9"/>
                                <line x1="9" y1="9" x2="15" y2="15"/>
                                <line x1="15" y1="9" x2="9" y2="15"/>
                            </svg>
                            <p class="font-medium">No leave balances found</p>
                            <p class="text-sm">Contact HR to set up your leave entitlements</p>
                        </div>
                    @endif
                </div>
            </div>
        
            <div class="backdrop-blur-xl bg-white/25 border border-white/20 rounded-3xl p-6 shadow-2xl">
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-900">Important Notes</h3>
                    <p class="text-gray-600 text-sm mt-1">Please read before submitting</p>
                </div>
                
                <div class="space-y-3 text-sm text-gray-600">
                    <div class="flex items-start space-x-2">
                        <svg class="w-4 h-4 mt-0.5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span>Leave requests must be submitted at least 24 hours in advance</span>
                    </div>
                    <div class="flex items-start space-x-2">
                        <svg class="w-4 h-4 mt-0.5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span>Emergency leaves may be approved retroactively</span>
                    </div>
                    <div class="flex items-start space-x-2">
                        <svg class="w-4 h-4 mt-0.5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span>Weekend days are automatically excluded from leave calculation</span>
                    </div>
                    <div class="flex items-start space-x-2">
                        <svg class="w-4 h-4 mt-0.5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span>You can cancel pending or future approved leaves</span>
                    </div>
                    <div class="flex items-start space-x-2">
                        <svg class="w-4 h-4 mt-0.5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span>Approved leaves will be deducted from your leave balance</span>
                    </div>
                </div>
            </div>
        
            <div class="backdrop-blur-xl bg-white/25 border border-white/20 rounded-3xl p-6 shadow-2xl">
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-900">Submit Request</h3>
                </div>
                
                <div class="space-y-3">
                    <button type="submit" id="submit-btn" 
                            class="w-full px-6 py-3 text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl font-medium transition-all duration-300 hover:scale-105">
                        <svg class="h-4 w-4 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l7-7-7-7"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14"/>
                        </svg>
                        Submit Leave Request
                    </button>
                    <button type="button" onclick="document.querySelector('[x-data]').activeTab = 'history'" 
                            class="w-full px-6 py-3 text-gray-700 bg-white/50 hover:bg-white/70 border border-gray-300/50 rounded-xl font-medium transition-all duration-300">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
$(document).ready(function() {
    // Update leave balance when leave type changes
    $('#leave_type_id').on('change', function() {
        const leaveTypeId = $(this).val();
        const balanceDiv = $('#leave-balance');
        
        if (!leaveTypeId) {
            balanceDiv.html('<span class="text-gray-600">Select a leave type to view balance</span>');
            return;
        }
        
        balanceDiv.html('<span class="text-gray-600">Loading...</span>');
        
        // Simulate API call - replace with actual endpoint
        setTimeout(() => {
            balanceDiv.html(`
                <div class="flex justify-between items-center mb-1">
                    <span class="font-medium">15 days remaining</span>
                    <span class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded-full">75%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-green-500 h-2 rounded-full" style="width: 75%"></div>
                </div>
                <span class="text-xs text-gray-500 mt-1">5 used of 20 allocated</span>
            `);
        }, 1000);
    });
    
    // Calculate working days when dates change
    function calculateWorkingDays() {
        const startDate = $('#start_date').val();
        const endDate = $('#end_date').val();
        const workingDaysDiv = $('#working-days');
        
        if (!startDate || !endDate) {
            workingDaysDiv.html('<span class="text-gray-600">Select dates to calculate</span>');
            return;
        }
        
        if (new Date(endDate) < new Date(startDate)) {
            workingDaysDiv.html('<span class="text-red-500">End date must be after start date</span>');
            return;
        }
        
        // Simple calculation - replace with actual API
        const start = new Date(startDate);
        const end = new Date(endDate);
        const diffTime = Math.abs(end - start);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
        
        workingDaysDiv.html(`<strong class="text-emerald-600">${diffDays} working days</strong>`);
        
        // Update submit button text
        const dayText = diffDays === 1 ? 'day' : 'days';
        $('#submit-btn').html(`
            <svg class="h-4 w-4 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l7-7-7-7"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14"/>
            </svg>
            Submit ${diffDays} ${dayText} Leave Request
        `);
    }
    
    $('#start_date, #end_date').on('change', calculateWorkingDays);
    
    // Set end date minimum when start date changes
    $('#start_date').on('change', function() {
        $('#end_date').attr('min', $(this).val());
    });
    
    // Initialize if dates are pre-filled
    if ($('#start_date').val() && $('#end_date').val()) {
        calculateWorkingDays();
    }
    
    // Initialize if leave type is pre-selected
    if ($('#leave_type_id').val()) {
        $('#leave_type_id').trigger('change');
    }
});
</script>