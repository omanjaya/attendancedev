@extends('layouts.authenticated')

@section('title', 'Edit Payroll Record')

@section('page-content')
    <!-- Page Header -->
    <div class="mb-8 mt-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-foreground">Edit Payroll Record</h1>
                <p class="text-sm text-muted-foreground mt-1">Modify payroll for {{ $payroll->employee->full_name ?? 'Employee' }} - {{ $payroll->period ?? 'Period' }}</p>
            </div>
            
            <div class="flex items-center space-x-3">
                <x-ui.button variant="outline" href="{{ route('payroll.show', $payroll) }}">
                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    View Details
                </x-ui.button>
                <x-ui.button variant="outline" href="{{ route('payroll.index') }}">
                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Back to Payroll
                </x-ui.button>
            </div>
        </div>
    </div>

    @if($payroll->status === 'processed' || $payroll->status === 'paid')
        <div class="mb-6 p-4 bg-warning/10 border border-warning/20 rounded-lg">
            <div class="flex items-center">
                <svg class="h-5 w-5 text-warning mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.314 18.5c-.77.833.192 2.5 1.732 2.5z"/>
                </svg>
                <div>
                    <h4 class="font-medium text-warning">Limited Editing</h4>
                    <p class="text-sm text-warning/80">This payroll has been {{ $payroll->status }}. Only notes and certain adjustments can be modified.</p>
                </div>
            </div>
        </div>
    @endif

    <form action="{{ route('payroll.update', $payroll) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Employee Information -->
            <x-ui.card>
                <x-slot name="title">Employee Information</x-slot>
                <x-slot name="subtitle">Basic employee and period details</x-slot>
                
                <div class="space-y-4">
                    <div>
                        <x-ui.label value="Employee" />
                        <div class="mt-1 flex items-center space-x-3">
                            <x-ui.avatar :name="$payroll->employee->full_name ?? 'Employee'" size="md" />
                            <div>
                                <div class="text-sm font-medium text-foreground">{{ $payroll->employee->full_name ?? 'Unknown Employee' }}</div>
                                <div class="text-sm text-muted-foreground">{{ $payroll->employee->employee_id ?? 'N/A' }}</div>
                            </div>
                        </div>
                        <p class="mt-1 text-sm text-muted-foreground">Employee cannot be changed</p>
                    </div>

                    <div>
                        <x-ui.label for="period" value="Pay Period" />
                        <x-ui.input type="text" name="period" id="period" value="{{ old('period', $payroll->period) }}" 
                                   placeholder="e.g., January 2024" {{ in_array($payroll->status, ['processed', 'paid']) ? 'disabled' : '' }} required />
                        @error('period')
                            <div class="mt-2 text-sm text-destructive">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <x-ui.label for="period_start" value="Period Start" />
                            <x-ui.input type="date" name="period_start" id="period_start" 
                                       value="{{ old('period_start', $payroll->period_start?->format('Y-m-d')) }}" 
                                       {{ in_array($payroll->status, ['processed', 'paid']) ? 'disabled' : '' }} required />
                            @error('period_start')
                                <div class="mt-2 text-sm text-destructive">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <x-ui.label for="period_end" value="Period End" />
                            <x-ui.input type="date" name="period_end" id="period_end" 
                                       value="{{ old('period_end', $payroll->period_end?->format('Y-m-d')) }}" 
                                       {{ in_array($payroll->status, ['processed', 'paid']) ? 'disabled' : '' }} required />
                            @error('period_end')
                                <div class="mt-2 text-sm text-destructive">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <x-ui.label for="status" value="Status" />
                        <select name="status" id="status" class="mt-1 block w-full border-input rounded-md shadow-sm focus:border-primary focus:ring-primary" required>
                            <option value="draft" {{ old('status', $payroll->status) == 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="pending" {{ old('status', $payroll->status) == 'pending' ? 'selected' : '' }}>Pending Approval</option>
                            <option value="approved" {{ old('status', $payroll->status) == 'approved' ? 'selected' : '' }}>Approved</option>
                            <option value="processed" {{ old('status', $payroll->status) == 'processed' ? 'selected' : '' }}>Processed</option>
                            <option value="paid" {{ old('status', $payroll->status) == 'paid' ? 'selected' : '' }}>Paid</option>
                            <option value="cancelled" {{ old('status', $payroll->status) == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                        @error('status')
                            <div class="mt-2 text-sm text-destructive">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </x-ui.card>

            <!-- Salary Calculation -->
            <x-ui.card>
                <x-slot name="title">Salary Calculation</x-slot>
                <x-slot name="subtitle">Hours worked and base salary</x-slot>
                
                <div class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <x-ui.label for="worked_hours" value="Hours Worked" />
                            <x-ui.input type="number" name="worked_hours" id="worked_hours" 
                                       value="{{ old('worked_hours', $payroll->worked_hours) }}" 
                                       step="0.5" min="0" max="744" 
                                       {{ in_array($payroll->status, ['processed', 'paid']) ? 'disabled' : '' }} />
                            @error('worked_hours')
                                <div class="mt-2 text-sm text-destructive">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <x-ui.label for="overtime_hours" value="Overtime Hours" />
                            <x-ui.input type="number" name="overtime_hours" id="overtime_hours" 
                                       value="{{ old('overtime_hours', $payroll->overtime_hours) }}" 
                                       step="0.5" min="0" max="200" 
                                       {{ in_array($payroll->status, ['processed', 'paid']) ? 'disabled' : '' }} />
                            @error('overtime_hours')
                                <div class="mt-2 text-sm text-destructive">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <x-ui.label for="base_salary" value="Base Salary" />
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-muted-foreground text-sm">$</span>
                            </div>
                            <x-ui.input type="number" name="base_salary" id="base_salary" 
                                       value="{{ old('base_salary', $payroll->base_salary) }}" 
                                       step="0.01" min="0" class="pl-7"
                                       {{ in_array($payroll->status, ['processed', 'paid']) ? 'disabled' : '' }} required />
                        </div>
                        @error('base_salary')
                            <div class="mt-2 text-sm text-destructive">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <x-ui.label for="hourly_rate" value="Hourly Rate" />
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-muted-foreground text-sm">$</span>
                            </div>
                            <x-ui.input type="number" name="hourly_rate" id="hourly_rate" 
                                       value="{{ old('hourly_rate', $payroll->hourly_rate) }}" 
                                       step="0.01" min="0" class="pl-7"
                                       {{ in_array($payroll->status, ['processed', 'paid']) ? 'disabled' : '' }} />
                        </div>
                        @error('hourly_rate')
                            <div class="mt-2 text-sm text-destructive">{{ $message }}</div>
                        @enderror
                        <p class="mt-1 text-sm text-muted-foreground">For hourly employees or overtime calculations</p>
                    </div>
                </div>
            </x-ui.card>
        </div>

        <!-- Earnings and Deductions -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Earnings -->
            <x-ui.card>
                <x-slot name="title">Earnings & Bonuses</x-slot>
                <x-slot name="subtitle">Additional compensation</x-slot>
                
                <div class="space-y-4">
                    <div>
                        <x-ui.label for="overtime_pay" value="Overtime Pay" />
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-muted-foreground text-sm">$</span>
                            </div>
                            <x-ui.input type="number" name="overtime_pay" id="overtime_pay" 
                                       value="{{ old('overtime_pay', $payroll->overtime_pay) }}" 
                                       step="0.01" min="0" class="pl-7"
                                       {{ in_array($payroll->status, ['processed', 'paid']) ? 'disabled' : '' }} />
                        </div>
                        @error('overtime_pay')
                            <div class="mt-2 text-sm text-destructive">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <x-ui.label for="bonus" value="Bonus" />
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-muted-foreground text-sm">$</span>
                            </div>
                            <x-ui.input type="number" name="bonus" id="bonus" 
                                       value="{{ old('bonus', $payroll->bonus) }}" 
                                       step="0.01" min="0" class="pl-7"
                                       {{ in_array($payroll->status, ['processed', 'paid']) ? 'disabled' : '' }} />
                        </div>
                        @error('bonus')
                            <div class="mt-2 text-sm text-destructive">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <x-ui.label for="allowances" value="Allowances" />
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-muted-foreground text-sm">$</span>
                            </div>
                            <x-ui.input type="number" name="allowances" id="allowances" 
                                       value="{{ old('allowances', $payroll->allowances) }}" 
                                       step="0.01" min="0" class="pl-7"
                                       {{ in_array($payroll->status, ['processed', 'paid']) ? 'disabled' : '' }} />
                        </div>
                        @error('allowances')
                            <div class="mt-2 text-sm text-destructive">{{ $message }}</div>
                        @enderror
                        <p class="mt-1 text-sm text-muted-foreground">Transport, meal, or other allowances</p>
                    </div>

                    <div class="pt-3 border-t border-border">
                        <div class="flex justify-between text-sm">
                            <span class="font-medium text-muted-foreground">Gross Salary:</span>
                            <span id="gross_salary_display" class="font-bold text-foreground">
                                ${{ number_format($payroll->gross_salary ?? 0, 2) }}
                            </span>
                        </div>
                    </div>
                </div>
            </x-ui.card>

            <!-- Deductions -->
            <x-ui.card>
                <x-slot name="title">Deductions</x-slot>
                <x-slot name="subtitle">Taxes and other deductions</x-slot>
                
                <div class="space-y-4">
                    <div>
                        <x-ui.label for="tax_deductions" value="Tax Deductions" />
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-muted-foreground text-sm">$</span>
                            </div>
                            <x-ui.input type="number" name="tax_deductions" id="tax_deductions" 
                                       value="{{ old('tax_deductions', $payroll->tax_deductions) }}" 
                                       step="0.01" min="0" class="pl-7"
                                       {{ in_array($payroll->status, ['processed', 'paid']) ? 'disabled' : '' }} />
                        </div>
                        @error('tax_deductions')
                            <div class="mt-2 text-sm text-destructive">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <x-ui.label for="insurance_deductions" value="Insurance Deductions" />
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-muted-foreground text-sm">$</span>
                            </div>
                            <x-ui.input type="number" name="insurance_deductions" id="insurance_deductions" 
                                       value="{{ old('insurance_deductions', $payroll->insurance_deductions) }}" 
                                       step="0.01" min="0" class="pl-7"
                                       {{ in_array($payroll->status, ['processed', 'paid']) ? 'disabled' : '' }} />
                        </div>
                        @error('insurance_deductions')
                            <div class="mt-2 text-sm text-destructive">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <x-ui.label for="other_deductions" value="Other Deductions" />
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-muted-foreground text-sm">$</span>
                            </div>
                            <x-ui.input type="number" name="other_deductions" id="other_deductions" 
                                       value="{{ old('other_deductions', $payroll->other_deductions) }}" 
                                       step="0.01" min="0" class="pl-7"
                                       {{ in_array($payroll->status, ['processed', 'paid']) ? 'disabled' : '' }} />
                        </div>
                        @error('other_deductions')
                            <div class="mt-2 text-sm text-destructive">{{ $message }}</div>
                        @enderror
                        <p class="mt-1 text-sm text-muted-foreground">Loans, advances, etc.</p>
                    </div>

                    <div class="pt-3 border-t border-border space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="font-medium text-muted-foreground">Total Deductions:</span>
                            <span id="total_deductions_display" class="font-bold text-destructive">
                                ${{ number_format($payroll->total_deductions ?? 0, 2) }}
                            </span>
                        </div>
                        <div class="flex justify-between text-base">
                            <span class="font-bold text-foreground">Net Salary:</span>
                            <span id="net_salary_display" class="font-bold text-success">
                                ${{ number_format($payroll->net_salary ?? 0, 2) }}
                            </span>
                        </div>
                    </div>
                </div>
            </x-ui.card>
        </div>

        <!-- Additional Information -->
        <x-ui.card>
            <x-slot name="title">Additional Information</x-slot>
            <x-slot name="subtitle">Payment details and notes</x-slot>
            
            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <x-ui.label for="payment_method" value="Payment Method" />
                        <select name="payment_method" id="payment_method" class="mt-1 block w-full border-input rounded-md shadow-sm focus:border-primary focus:ring-primary">
                            <option value="">Select Method</option>
                            <option value="bank_transfer" {{ old('payment_method', $payroll->payment_method) == 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                            <option value="cash" {{ old('payment_method', $payroll->payment_method) == 'cash' ? 'selected' : '' }}>Cash</option>
                            <option value="check" {{ old('payment_method', $payroll->payment_method) == 'check' ? 'selected' : '' }}>Check</option>
                            <option value="digital_wallet" {{ old('payment_method', $payroll->payment_method) == 'digital_wallet' ? 'selected' : '' }}>Digital Wallet</option>
                        </select>
                        @error('payment_method')
                            <div class="mt-2 text-sm text-destructive">{{ $message }}</div>
                        @enderror
                    </div>

                    @if($payroll->status === 'paid')
                    <div>
                        <x-ui.label for="payment_date" value="Payment Date" />
                        <x-ui.input type="date" name="payment_date" id="payment_date" 
                                   value="{{ old('payment_date', $payroll->payment_date?->format('Y-m-d')) }}" />
                        @error('payment_date')
                            <div class="mt-2 text-sm text-destructive">{{ $message }}</div>
                        @enderror
                    </div>
                    @endif
                </div>

                <div>
                    <x-ui.label for="notes" value="Notes" />
                    <textarea name="notes" id="notes" rows="3" 
                              class="mt-1 block w-full border-input rounded-md shadow-sm focus:border-primary focus:ring-primary" 
                              placeholder="Additional notes about this payroll record...">{{ old('notes', $payroll->notes) }}</textarea>
                    @error('notes')
                        <div class="mt-2 text-sm text-destructive">{{ $message }}</div>
                    @enderror
                </div>

                @if($payroll->created_at)
                <div class="pt-4 border-t border-border">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-muted-foreground">
                        <div>
                            <strong>Created:</strong> {{ $payroll->created_at->format('M j, Y g:i A') }}
                        </div>
                        <div>
                            <strong>Last Modified:</strong> {{ $payroll->updated_at->format('M j, Y g:i A') }}
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </x-ui.card>

        <!-- Actions -->
        <div class="flex items-center justify-end space-x-4">
            <x-ui.button type="button" variant="outline" onclick="history.back()">
                Cancel
            </x-ui.button>
            <x-ui.button type="submit" variant="warning">
                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Update Payroll Record
            </x-ui.button>
        </div>
    </form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-calculate totals
    function calculateTotals() {
        const baseSalary = parseFloat(document.getElementById('base_salary').value) || 0;
        const overtimePay = parseFloat(document.getElementById('overtime_pay').value) || 0;
        const bonus = parseFloat(document.getElementById('bonus').value) || 0;
        const allowances = parseFloat(document.getElementById('allowances').value) || 0;
        
        const taxDeductions = parseFloat(document.getElementById('tax_deductions').value) || 0;
        const insuranceDeductions = parseFloat(document.getElementById('insurance_deductions').value) || 0;
        const otherDeductions = parseFloat(document.getElementById('other_deductions').value) || 0;
        
        const grossSalary = baseSalary + overtimePay + bonus + allowances;
        const totalDeductions = taxDeductions + insuranceDeductions + otherDeductions;
        const netSalary = grossSalary - totalDeductions;
        
        document.getElementById('gross_salary_display').textContent = '$' + grossSalary.toFixed(2);
        document.getElementById('total_deductions_display').textContent = '$' + totalDeductions.toFixed(2);
        document.getElementById('net_salary_display').textContent = '$' + netSalary.toFixed(2);
    }

    // Auto-calculate overtime pay based on hours and rate
    function calculateOvertimePay() {
        const overtimeHours = parseFloat(document.getElementById('overtime_hours').value) || 0;
        const hourlyRate = parseFloat(document.getElementById('hourly_rate').value) || 0;
        const overtimeRate = hourlyRate * 1.5; // Assuming 1.5x rate for overtime
        
        if (overtimeHours > 0 && hourlyRate > 0) {
            const overtimePay = overtimeHours * overtimeRate;
            document.getElementById('overtime_pay').value = overtimePay.toFixed(2);
            calculateTotals();
        }
    }

    // Event listeners for all monetary inputs
    const monetaryInputs = [
        'base_salary', 'overtime_pay', 'bonus', 'allowances',
        'tax_deductions', 'insurance_deductions', 'other_deductions'
    ];
    
    monetaryInputs.forEach(function(inputId) {
        const input = document.getElementById(inputId);
        if (input) {
            input.addEventListener('input', calculateTotals);
        }
    });

    // Event listeners for overtime calculation
    const overtimeHoursInput = document.getElementById('overtime_hours');
    const hourlyRateInput = document.getElementById('hourly_rate');
    
    if (overtimeHoursInput && hourlyRateInput) {
        overtimeHoursInput.addEventListener('change', calculateOvertimePay);
        hourlyRateInput.addEventListener('change', calculateOvertimePay);
    }

    // Validate period dates
    const periodStartInput = document.getElementById('period_start');
    const periodEndInput = document.getElementById('period_end');
    
    if (periodStartInput && periodEndInput) {
        periodEndInput.addEventListener('change', function() {
            if (periodStartInput.value && periodEndInput.value) {
                if (periodEndInput.value <= periodStartInput.value) {
                    alert('Period end date must be after period start date');
                    periodEndInput.value = '';
                }
            }
        });
    }

    // Initial calculation
    calculateTotals();
});
</script>
@endpush