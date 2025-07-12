@extends('layouts.authenticated')

@section('title', 'Payroll Details')

@section('page-content')
    <!-- Breadcrumb -->
    <nav class="mb-8" aria-label="Breadcrumb">
        <ol class="flex items-center space-x-2 text-sm">
            <li>
                <a href="{{ route('dashboard') }}" class="text-muted-foreground hover:text-foreground transition-colors">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                </a>
            </li>
            <li class="flex items-center">
                <svg class="h-5 w-5 text-muted-foreground mx-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                </svg>
                <a href="{{ route('payroll.index') }}" class="text-muted-foreground hover:text-foreground transition-colors">Payroll</a>
            </li>
            <li class="flex items-center">
                <svg class="h-5 w-5 text-muted-foreground mx-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                </svg>
                <span class="text-foreground font-medium">Payroll Details</span>
            </li>
        </ol>
    </nav>
    
    <!-- Page Header -->
    <div class="mb-8">
        
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-foreground">Payroll Details</h1>
                <p class="text-muted-foreground mt-1">John Doe - January 2024</p>
            </div>
            
            <div class="flex flex-wrap items-center gap-3">
                <x-ui.button
                    variant="outline"
                    onclick="window.print()"
                >
                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    Print
                </x-ui.button>
                
                <x-ui.button
                    variant="default"
                    onclick="approvePayroll()"
                    class="bg-success hover:bg-success/90 text-success-foreground"
                >
                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Approve
                </x-ui.button>
                
                <x-ui.button
                    variant="outline"
                    href="{{ route('payroll.index') }}"
                >
                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Back to List
                </x-ui.button>
            </div>
        </div>
    </div>
    
    <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-8">
            <!-- Employee Information -->
            <x-ui.card>
                <x-slot name="title">Employee Information</x-slot>
                <x-slot name="subtitle">Basic employee details</x-slot>
                
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-muted-foreground mb-1">Full Name</label>
                            <p class="text-sm text-foreground font-medium">John Doe</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-muted-foreground mb-1">Employee ID</label>
                            <p class="text-sm text-foreground font-medium">EMP001</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-muted-foreground mb-1">Department</label>
                            <p class="text-sm text-foreground font-medium">Engineering</p>
                        </div>
                    </div>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-muted-foreground mb-1">Position</label>
                            <p class="text-sm text-foreground font-medium">Senior Developer</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-muted-foreground mb-1">Employment Type</label>
                            <p class="text-sm text-foreground font-medium">Permanent Staff</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-muted-foreground mb-1">Salary Type</label>
                            <x-ui.badge variant="secondary" size="sm">Monthly</x-ui.badge>
                        </div>
                    </div>
                </div>
            </x-ui.card>
            
            <!-- Payroll Period & Hours -->
            <x-ui.card>
                <x-slot name="title">Payroll Period & Hours</x-slot>
                <x-slot name="subtitle">Work period and time details</x-slot>
                
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                    <div class="text-center p-4 bg-muted/30 rounded-lg">
                        <label class="block text-sm font-medium text-muted-foreground mb-2">Pay Period</label>
                        <p class="text-lg font-semibold text-foreground">January 2024</p>
                        <p class="text-sm text-muted-foreground">Jan 1 - Jan 31, 2024</p>
                    </div>
                    
                    <div class="text-center p-4 bg-muted/30 rounded-lg">
                        <label class="block text-sm font-medium text-muted-foreground mb-2">Regular Hours</label>
                        <p class="text-lg font-semibold text-foreground">160 hours</p>
                        <p class="text-sm text-muted-foreground">Standard work hours</p>
                    </div>
                    
                    <div class="text-center p-4 bg-muted/30 rounded-lg">
                        <label class="block text-sm font-medium text-muted-foreground mb-2">Overtime Hours</label>
                        <p class="text-lg font-semibold text-success">8 hours</p>
                        <p class="text-sm text-muted-foreground">At 1.5x rate</p>
                    </div>
                </div>
            </x-ui.card>
            
            <!-- Salary Breakdown -->
            <x-ui.card>
                <x-slot name="title">Salary Breakdown</x-slot>
                <x-slot name="subtitle">Detailed calculation breakdown</x-slot>
                
                <div class="space-y-8">
                    <!-- Earnings -->
                    <div>
                        <h4 class="text-lg font-semibold text-foreground mb-6">Earnings</h4>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center py-3 border-b border-border">
                                <span class="text-sm text-muted-foreground">Base Salary</span>
                                <span class="font-medium text-foreground">$5,000.00</span>
                            </div>
                            
                            <div class="flex justify-between items-center py-3 border-b border-border">
                                <span class="text-sm text-muted-foreground">Overtime Pay (8h × $46.88)</span>
                                <span class="font-medium text-foreground">$375.00</span>
                            </div>
                            
                            <div class="flex justify-between items-center py-3 border-b border-border">
                                <span class="text-sm text-muted-foreground">Transport Allowance</span>
                                <span class="font-medium text-foreground">$200.00</span>
                            </div>
                            
                            <div class="flex justify-between items-center py-3 border-b border-border">
                                <span class="text-sm text-muted-foreground">Meal Allowance</span>
                                <span class="font-medium text-foreground">$150.00</span>
                            </div>
                            
                            <div class="flex justify-between items-center py-3 border-b-2 border-border font-semibold">
                                <span class="text-foreground">Total Earnings</span>
                                <span class="text-success">$5,725.00</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Deductions -->
                    <div>
                        <h4 class="text-lg font-semibold text-foreground mb-6">Deductions</h4>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center py-3 border-b border-border">
                                <span class="text-sm text-muted-foreground">Income Tax</span>
                                <span class="font-medium text-destructive">-$687.00</span>
                            </div>
                            
                            <div class="flex justify-between items-center py-3 border-b border-border">
                                <span class="text-sm text-muted-foreground">Social Security</span>
                                <span class="font-medium text-destructive">-$344.98</span>
                            </div>
                            
                            <div class="flex justify-between items-center py-3 border-b border-border">
                                <span class="text-sm text-muted-foreground">Health Insurance</span>
                                <span class="font-medium text-destructive">-$125.00</span>
                            </div>
                            
                            <div class="flex justify-between items-center py-3 border-b-2 border-border font-semibold">
                                <span class="text-foreground">Total Deductions</span>
                                <span class="text-destructive">-$1,156.98</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Net Pay -->
                    <div class="bg-primary/10 p-6 rounded-lg">
                        <div class="flex justify-between items-center">
                            <span class="text-xl font-semibold text-foreground">Net Pay</span>
                            <span class="text-2xl font-bold text-primary">$4,568.02</span>
                        </div>
                        <p class="text-sm text-muted-foreground mt-1">Amount to be paid to employee</p>
                    </div>
                </div>
            </x-ui.card>
            
            <!-- Additional Notes -->
            <x-ui.card>
                <x-slot name="title">Additional Information</x-slot>
                <x-slot name="subtitle">Notes and comments</x-slot>
                
                <div class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-muted-foreground mb-3">Payroll Notes</label>
                        <div class="bg-muted/50 p-4 rounded-lg border border-border">
                            <p class="text-sm text-foreground">Regular monthly payroll calculation. Employee worked additional 8 hours overtime during project deadline week.</p>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-muted-foreground mb-3">Approval Comments</label>
                        <div class="bg-muted/50 p-4 rounded-lg border border-border">
                            <p class="text-sm text-muted-foreground italic">No approval comments yet.</p>
                        </div>
                    </div>
                </div>
            </x-ui.card>
        </div>
        
        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Status Card -->
            <x-ui.card>
                <x-slot name="title">Payroll Status</x-slot>
                <x-slot name="subtitle">Current processing status</x-slot>
                
                <div class="text-center space-y-4">
                    <div>
                        <x-ui.badge variant="warning" class="text-sm px-3 py-1">Pending Approval</x-ui.badge>
                    </div>
                    
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Created:</span>
                            <span class="font-medium text-foreground">Jan 31, 2024</span>
                        </div>
                        
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Created By:</span>
                            <span class="font-medium text-foreground">HR Manager</span>
                        </div>
                        
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Last Updated:</span>
                            <span class="font-medium text-foreground">Feb 1, 2024</span>
                        </div>
                    </div>
                </div>
            </x-ui.card>
            
            <!-- Quick Actions -->
            <x-ui.card>
                <x-slot name="title">Quick Actions</x-slot>
                <x-slot name="subtitle">Available operations</x-slot>
                
                <div class="space-y-3">
                    <x-ui.button
                        variant="outline"
                        class="w-full"
                        onclick="editPayroll()"
                    >
                        <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Edit Payroll
                    </x-ui.button>
                    
                    <x-ui.button
                        variant="outline"
                        class="w-full"
                        onclick="duplicatePayroll()"
                    >
                        <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                        Duplicate for Next Month
                    </x-ui.button>
                    
                    <x-ui.button
                        variant="outline"
                        class="w-full"
                        onclick="exportPayslip()"
                    >
                        <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Export Payslip
                    </x-ui.button>
                    
                    <x-ui.button
                        variant="destructive"
                        class="w-full"
                        onclick="deletePayroll()"
                    >
                        <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Delete Payroll
                    </x-ui.button>
                </div>
            </x-ui.card>
            
            <!-- Payment History -->
            <x-ui.card>
                <x-slot name="title">Payment History</x-slot>
                <x-slot name="subtitle">Previous payments for this employee</x-slot>
                
                <div class="space-y-3">
                    @php
                        $paymentHistory = [
                            ['period' => 'Dec 2023', 'amount' => '$4,250.00', 'status' => 'Paid', 'date' => 'Dec 31, 2023'],
                            ['period' => 'Nov 2023', 'amount' => '$4,200.00', 'status' => 'Paid', 'date' => 'Nov 30, 2023'],
                            ['period' => 'Oct 2023', 'amount' => '$4,350.00', 'status' => 'Paid', 'date' => 'Oct 31, 2023'],
                        ];
                    @endphp
                    
                    @foreach($paymentHistory as $payment)
                        <div class="flex items-center justify-between py-3 {{ !$loop->last ? 'border-b border-border' : '' }}">
                            <div>
                                <p class="text-sm font-medium text-foreground">{{ $payment['period'] }}</p>
                                <p class="text-xs text-muted-foreground">{{ $payment['date'] }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium text-foreground">{{ $payment['amount'] }}</p>
                                <x-ui.badge variant="success" size="sm">
                                    {{ $payment['status'] }}
                                </x-ui.badge>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-ui.card>
            
            <!-- Attendance Summary -->
            <x-ui.card>
                <x-slot name="title">Attendance Summary</x-slot>
                <x-slot name="subtitle">Work hours for this period</x-slot>
                
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-muted-foreground">Total Days Worked</span>
                        <span class="font-medium text-foreground">22 days</span>
                    </div>
                    
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-muted-foreground">Regular Hours</span>
                        <span class="font-medium text-foreground">160 hours</span>
                    </div>
                    
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-muted-foreground">Overtime Hours</span>
                        <span class="font-medium text-success">8 hours</span>
                    </div>
                    
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-muted-foreground">Late Days</span>
                        <span class="font-medium text-foreground">1 day</span>
                    </div>
                    
                    <div class="flex justify-between items-center border-t border-border pt-3">
                        <span class="text-sm font-medium text-foreground">Total Hours</span>
                        <span class="font-semibold text-foreground">168 hours</span>
                    </div>
                </div>
            </x-ui.card>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    // Approve payroll
    function approvePayroll() {
        if (confirm('Are you sure you want to approve this payroll?')) {
            // In a real implementation, this would make an API call
            showAlert('success', 'Payroll approved successfully');
            // Update status display
            setTimeout(() => {
                location.reload();
            }, 1000);
        }
    }

    // Edit payroll
    function editPayroll() {
        window.location.href = '/payroll/1/edit';
    }

    // Duplicate payroll
    function duplicatePayroll() {
        if (confirm('Create a duplicate payroll for the next month?')) {
            // In a real implementation, this would make an API call
            showAlert('success', 'Payroll duplicated for next month');
        }
    }

    // Export payslip
    function exportPayslip() {
        // In a real implementation, this would generate and download a PDF
        showAlert('info', 'Payslip export started');
    }

    // Delete payroll
    function deletePayroll() {
        if (confirm('Are you sure you want to delete this payroll? This action cannot be undone.')) {
            // In a real implementation, this would make an API call
            showAlert('success', 'Payroll deleted successfully');
            setTimeout(() => {
                window.location.href = '/payroll';
            }, 1000);
        }
    }

    // Show alert function
    function showAlert(type, message) {
        const alertColors = {
            success: 'bg-success/10 text-success border-success/20',
            error: 'bg-destructive/10 text-destructive border-destructive/20',
            info: 'bg-primary/10 text-primary border-primary/20',
            warning: 'bg-warning/10 text-warning border-warning/20'
        };

        const alert = document.createElement('div');
        alert.className = `fixed top-4 right-4 p-4 rounded-lg border ${alertColors[type]} shadow-lg z-50`;
        alert.innerHTML = `
            <div class="flex items-center">
                <span>${message}</span>
                <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-lg hover:opacity-70">&times;</button>
            </div>
        `;

        document.body.appendChild(alert);

        setTimeout(() => {
            if (alert.parentElement) {
                alert.remove();
            }
        }, 5000);
    }
</script>
@endpush