@extends('layouts.authenticated')

@section('title', 'Calculate Payroll')

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
                <span class="text-foreground font-medium">Calculate Payroll</span>
            </li>
        </ol>
    </nav>
    
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-foreground">Calculate Payroll</h1>
                <p class="text-muted-foreground mt-1">Create new payroll calculation for an employee</p>
            </div>
            
            <x-ui.button variant="outline" href="{{ route('payroll.index') }}">
                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to List
            </x-ui.button>
        </div>
    </div>
    
    <!-- Main Layout Grid -->
    <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
        <!-- Main Form (2/3 width on large screens) -->
        <div class="lg:col-span-2 space-y-8">
            <!-- Form Card -->
            <x-ui.card>
                <x-slot name="title">Payroll Calculation</x-slot>
                <x-slot name="subtitle">Calculate employee payroll for the selected period</x-slot>
                
                <form action="{{ route('payroll.store') }}" method="POST" id="payrollForm" class="space-y-6">
                    @csrf
                    
                    <!-- Employee Selection Section -->
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div class="space-y-2">
                            <x-ui.label for="employeeSelect" value="Employee" required />
                            <x-ui.select name="employee_id" id="employeeSelect" required>
                                <option value="">Select Employee</option>
                                <!-- Options will be populated from backend -->
                            </x-ui.select>
                        </div>
                        
                        <div class="space-y-2">
                            <x-ui.label for="payroll_period" value="Payroll Period" required />
                            <x-ui.select name="payroll_period" id="payroll_period" required onchange="toggleCustomPeriod(this.value)">
                                <option value="current_month">Current Month</option>
                                <option value="last_month">Last Month</option>
                                <option value="custom">Custom Period</option>
                            </x-ui.select>
                        </div>
                    </div>
                        
                        <!-- Custom Period (Hidden by default) -->
                    <div id="customPeriodSection" class="hidden">
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div class="space-y-2">
                                <x-ui.label for="periodStart" value="Period Start Date" />
                                <x-ui.input type="date" name="period_start" id="periodStart" />
                            </div>
                            
                            <div class="space-y-2">
                                <x-ui.label for="periodEnd" value="Period End Date" />
                                <x-ui.input type="date" name="period_end" id="periodEnd" />
                            </div>
                        </div>
                    </div>
                        
                    <!-- Employee Information Display -->
                    <div id="employeeInfo" class="hidden p-6 bg-muted/50 rounded-lg border border-border">
                        <h4 class="text-sm font-semibold text-foreground mb-4">Employee Information</h4>
                        <div class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
                            <div>
                                <span class="text-muted-foreground">Salary Type:</span>
                                <span id="salaryType" class="ml-2 font-medium text-foreground">-</span>
                            </div>
                            <div>
                                <span class="text-muted-foreground">Base Salary:</span>
                                <span id="baseSalary" class="ml-2 font-medium text-foreground">-</span>
                            </div>
                            <div>
                                <span class="text-muted-foreground">Hourly Rate:</span>
                                <span id="hourlyRate" class="ml-2 font-medium text-foreground">-</span>
                            </div>
                            <div>
                                <span class="text-muted-foreground">Department:</span>
                                <span id="department" class="ml-2 font-medium text-foreground">-</span>
                            </div>
                        </div>
                    </div>
                        
                    <!-- Salary Calculation -->
                    <div class="space-y-4">
                        <h3 class="text-base font-semibold text-foreground">Hours & Overtime</h3>
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                            <div class="space-y-2">
                                <x-ui.label for="workedHours" value="Hours Worked" />
                                <x-ui.input
                                    name="worked_hours"
                                    id="workedHours"
                                    type="number"
                                    step="0.01"
                                    placeholder="0.00"
                                    onchange="calculateSalary()"
                                />
                            </div>
                            
                            <div class="space-y-2">
                                <x-ui.label for="overtimeHours" value="Overtime Hours" />
                                <x-ui.input
                                    name="overtime_hours"
                                    id="overtimeHours"
                                    type="number"
                                    step="0.01"
                                    placeholder="0.00"
                                    onchange="calculateSalary()"
                                />
                            </div>
                            
                            <div class="space-y-2">
                                <x-ui.label for="overtimeRate" value="Overtime Rate" />
                                <x-ui.input
                                    name="overtime_rate"
                                    id="overtimeRate"
                                    type="number"
                                    step="0.01"
                                    placeholder="1.5"
                                    value="1.5"
                                    onchange="calculateSalary()"
                                />
                            </div>
                        </div>
                    </div>
                        
                    <!-- Allowances -->
                    <div class="space-y-4">
                        <h3 class="text-base font-semibold text-foreground">Allowances</h3>
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                            <div class="space-y-2">
                                <x-ui.label for="transport_allowance" value="Transport" />
                                <x-ui.input
                                    name="transport_allowance"
                                    id="transport_allowance"
                                    type="number"
                                    step="0.01"
                                    placeholder="0.00"
                                    onchange="calculateSalary()"
                                />
                            </div>
                            
                            <div class="space-y-2">
                                <x-ui.label for="meal_allowance" value="Meal" />
                                <x-ui.input
                                    name="meal_allowance"
                                    id="meal_allowance"
                                    type="number"
                                    step="0.01"
                                    placeholder="0.00"
                                    onchange="calculateSalary()"
                                />
                            </div>
                            
                            <div class="space-y-2">
                                <x-ui.label for="housing_allowance" value="Housing" />
                                <x-ui.input
                                    name="housing_allowance"
                                    id="housing_allowance"
                                    type="number"
                                    step="0.01"
                                    placeholder="0.00"
                                    onchange="calculateSalary()"
                                />
                            </div>
                            
                            <div class="space-y-2">
                                <x-ui.label for="other_allowances" value="Other" />
                                <x-ui.input
                                    name="other_allowances"
                                    id="other_allowances"
                                    type="number"
                                    step="0.01"
                                    placeholder="0.00"
                                    onchange="calculateSalary()"
                                />
                            </div>
                        </div>
                    </div>
                        
                    <!-- Deductions -->
                    <div class="space-y-4">
                        <h3 class="text-base font-semibold text-foreground">Deductions</h3>
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                            <div class="space-y-2">
                                <x-ui.label for="tax_deduction" value="Tax" />
                                <x-ui.input
                                    name="tax_deduction"
                                    id="tax_deduction"
                                    type="number"
                                    step="0.01"
                                    placeholder="0.00"
                                    onchange="calculateSalary()"
                                />
                            </div>
                            
                            <div class="space-y-2">
                                <x-ui.label for="insurance_deduction" value="Insurance" />
                                <x-ui.input
                                    name="insurance_deduction"
                                    id="insurance_deduction"
                                    type="number"
                                    step="0.01"
                                    placeholder="0.00"
                                    onchange="calculateSalary()"
                                />
                            </div>
                            
                            <div class="space-y-2">
                                <x-ui.label for="pension_deduction" value="Pension" />
                                <x-ui.input
                                    name="pension_deduction"
                                    id="pension_deduction"
                                    type="number"
                                    step="0.01"
                                    placeholder="0.00"
                                    onchange="calculateSalary()"
                                />
                            </div>
                            
                            <div class="space-y-2">
                                <x-ui.label for="other_deductions" value="Other" />
                                <x-ui.input
                                    name="other_deductions"
                                    id="other_deductions"
                                    type="number"
                                    step="0.01"
                                    placeholder="0.00"
                                    onchange="calculateSalary()"
                                />
                            </div>
                        </div>
                    </div>
                        
                    <!-- Notes -->
                    <div class="space-y-2">
                        <x-ui.label for="notes" value="Notes" />
                        <textarea
                            name="notes"
                            id="notes"
                            rows="3"
                            placeholder="Additional notes or comments about this payroll calculation..."
                            class="block w-full border-input bg-background px-3 py-2 text-sm text-foreground shadow-sm transition-colors placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50 rounded-md"
                        ></textarea>
                    </div>
                </form>
                
                <x-slot name="footer">
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                        <x-ui.button
                            variant="outline"
                            href="{{ route('payroll.index') }}"
                        >
                            Cancel
                        </x-ui.button>
                        
                        <div class="flex flex-col sm:flex-row items-center gap-3">
                            <x-ui.button
                                type="submit"
                                variant="outline"
                                form="payrollForm"
                                onclick="document.querySelector('input[name=status]').value='draft'"
                            >
                                Save as Draft
                            </x-ui.button>
                            
                            <x-ui.button
                                type="submit"
                                form="payrollForm"
                                onclick="document.querySelector('input[name=status]').value='pending'"
                            >
                                Calculate & Submit
                            </x-ui.button>
                        </div>
                    </div>
                    
                    <!-- Hidden status field -->
                    <input type="hidden" name="status" value="pending" form="payrollForm">
                </x-slot>
            </x-ui.card>
        </div>
        
        <!-- Calculation Summary -->
        <div class="space-y-6">
            <x-ui.card>
                <x-slot name="title">Calculation Summary</x-slot>
                <x-slot name="subtitle">Live calculation preview</x-slot>
                
                <div class="space-y-4">
                    <!-- Gross Salary -->
                    <div class="flex justify-between items-center py-3 border-b border-border">
                        <span class="text-sm text-muted-foreground">Base Salary</span>
                        <span id="displayBaseSalary" class="font-medium text-foreground">$0.00</span>
                    </div>
                    
                    <div class="flex justify-between items-center py-3 border-b border-border">
                        <span class="text-sm text-muted-foreground">Overtime Pay</span>
                        <span id="displayOvertimePay" class="font-medium text-foreground">$0.00</span>
                    </div>
                    
                    <div class="flex justify-between items-center py-3 border-b border-border">
                        <span class="text-sm text-muted-foreground">Total Allowances</span>
                        <span id="displayTotalAllowances" class="font-medium text-foreground">$0.00</span>
                    </div>
                    
                    <div class="flex justify-between items-center py-3 border-b-2 border-border font-semibold">
                        <span class="text-foreground">Gross Salary</span>
                        <span id="displayGrossSalary" class="text-success">$0.00</span>
                    </div>
                    
                    <!-- Deductions -->
                    <div class="flex justify-between items-center py-3 border-b border-border">
                        <span class="text-sm text-muted-foreground">Total Deductions</span>
                        <span id="displayTotalDeductions" class="font-medium text-destructive">-$0.00</span>
                    </div>
                    
                    <!-- Net Salary -->
                    <div class="flex justify-between items-center py-4 bg-primary/10 px-5 rounded-lg">
                        <span class="text-lg font-semibold text-foreground">Net Salary</span>
                        <span id="displayNetSalary" class="text-xl font-bold text-primary">$0.00</span>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="pt-4 space-y-3">
                        <x-ui.button
                            variant="outline"
                            size="sm"
                            class="w-full"
                            onclick="loadAttendanceData()"
                        >
                            <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Load Attendance Data
                        </x-ui.button>
                        
                        <x-ui.button
                            variant="outline"
                            size="sm"
                            class="w-full"
                            onclick="resetCalculation()"
                        >
                            <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            Reset Calculation
                        </x-ui.button>
                    </div>
                </div>
            </x-ui.card>
            
            <!-- Recent Payrolls -->
            <x-ui.card>
                <x-slot name="title">Recent Payrolls</x-slot>
                <x-slot name="subtitle">Latest payroll calculations</x-slot>
                
                <div class="space-y-3">
                    @php
                        $recentPayrolls = [
                            ['employee' => 'John Doe', 'period' => 'Dec 2023', 'amount' => '$4,250.00', 'status' => 'Paid'],
                            ['employee' => 'Jane Smith', 'period' => 'Dec 2023', 'amount' => '$3,825.00', 'status' => 'Paid'],
                            ['employee' => 'Mike Johnson', 'period' => 'Dec 2023', 'amount' => '$2,975.00', 'status' => 'Processed'],
                        ];
                    @endphp
                    
                    @foreach($recentPayrolls as $payroll)
                        <div class="flex items-center justify-between py-3 {{ !$loop->last ? 'border-b border-border' : '' }}">
                            <div>
                                <p class="text-sm font-medium text-foreground">{{ $payroll['employee'] }}</p>
                                <p class="text-xs text-muted-foreground">{{ $payroll['period'] }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium text-foreground">{{ $payroll['amount'] }}</p>
                                <x-ui.badge variant="{{ $payroll['status'] === 'Paid' ? 'success' : 'secondary' }}" size="sm">
                                    {{ $payroll['status'] }}
                                </x-ui.badge>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-ui.card>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    // Sample employee data (in a real app, this would come from an API)
    const employees = {
        1: {
            name: 'John Doe',
            salary_type: 'Monthly',
            salary_amount: 5000,
            hourly_rate: 30,
            department: 'Engineering'
        },
        2: {
            name: 'Jane Smith',
            salary_type: 'Hourly',
            salary_amount: 4500,
            hourly_rate: 28,
            department: 'Marketing'
        },
        3: {
            name: 'Mike Johnson',
            salary_type: 'Monthly',
            salary_amount: 3500,
            hourly_rate: 22,
            department: 'Sales'
        }
    };

    // Initialize page
    document.addEventListener('DOMContentLoaded', function() {
        populateEmployeeSelect();
        calculateSalary();
    });

    // Populate employee select
    function populateEmployeeSelect() {
        const select = document.getElementById('employeeSelect');
        Object.keys(employees).forEach(id => {
            const option = document.createElement('option');
            option.value = id;
            option.textContent = employees[id].name;
            select.appendChild(option);
        });

        select.addEventListener('change', function() {
            if (this.value) {
                showEmployeeInfo(this.value);
                calculateSalary();
            } else {
                hideEmployeeInfo();
            }
        });
    }

    // Show employee information
    function showEmployeeInfo(employeeId) {
        const employee = employees[employeeId];
        if (!employee) return;

        document.getElementById('employeeInfo').classList.remove('hidden');
        document.getElementById('salaryType').textContent = employee.salary_type;
        document.getElementById('baseSalary').textContent = '$' + employee.salary_amount.toLocaleString();
        document.getElementById('hourlyRate').textContent = '$' + employee.hourly_rate;
        document.getElementById('department').textContent = employee.department;
    }

    // Hide employee information
    function hideEmployeeInfo() {
        document.getElementById('employeeInfo').classList.add('hidden');
    }

    // Toggle custom period section
    function toggleCustomPeriod(value) {
        const section = document.getElementById('customPeriodSection');
        if (value === 'custom') {
            section.classList.remove('hidden');
        } else {
            section.classList.add('hidden');
            // Auto-set dates based on selection
            if (value === 'current_month') {
                setCurrentMonth();
            } else if (value === 'last_month') {
                setLastMonth();
            }
        }
    }

    // Set current month dates
    function setCurrentMonth() {
        const now = new Date();
        const start = new Date(now.getFullYear(), now.getMonth(), 1);
        const end = new Date(now.getFullYear(), now.getMonth() + 1, 0);
        
        document.getElementById('periodStart').value = start.toISOString().split('T')[0];
        document.getElementById('periodEnd').value = end.toISOString().split('T')[0];
    }

    // Set last month dates
    function setLastMonth() {
        const now = new Date();
        const start = new Date(now.getFullYear(), now.getMonth() - 1, 1);
        const end = new Date(now.getFullYear(), now.getMonth(), 0);
        
        document.getElementById('periodStart').value = start.toISOString().split('T')[0];
        document.getElementById('periodEnd').value = end.toISOString().split('T')[0];
    }

    // Calculate salary
    function calculateSalary() {
        const employeeId = document.getElementById('employeeSelect').value;
        if (!employeeId) {
            resetDisplayValues();
            return;
        }

        const employee = employees[employeeId];
        const workedHours = parseFloat(document.querySelector('input[name="worked_hours"]').value) || 0;
        const overtimeHours = parseFloat(document.querySelector('input[name="overtime_hours"]').value) || 0;
        const overtimeRate = parseFloat(document.querySelector('input[name="overtime_rate"]').value) || 1.5;
        
        // Calculate base salary
        let baseSalary = 0;
        if (employee.salary_type === 'Monthly') {
            baseSalary = employee.salary_amount;
        } else {
            baseSalary = workedHours * employee.hourly_rate;
        }
        
        // Calculate overtime pay
        const overtimePay = overtimeHours * employee.hourly_rate * overtimeRate;
        
        // Calculate allowances
        const transportAllowance = parseFloat(document.querySelector('input[name="transport_allowance"]').value) || 0;
        const mealAllowance = parseFloat(document.querySelector('input[name="meal_allowance"]').value) || 0;
        const housingAllowance = parseFloat(document.querySelector('input[name="housing_allowance"]').value) || 0;
        const otherAllowances = parseFloat(document.querySelector('input[name="other_allowances"]').value) || 0;
        const totalAllowances = transportAllowance + mealAllowance + housingAllowance + otherAllowances;
        
        // Calculate deductions
        const taxDeduction = parseFloat(document.querySelector('input[name="tax_deduction"]').value) || 0;
        const insuranceDeduction = parseFloat(document.querySelector('input[name="insurance_deduction"]').value) || 0;
        const pensionDeduction = parseFloat(document.querySelector('input[name="pension_deduction"]').value) || 0;
        const otherDeductions = parseFloat(document.querySelector('input[name="other_deductions"]').value) || 0;
        const totalDeductions = taxDeduction + insuranceDeduction + pensionDeduction + otherDeductions;
        
        // Calculate totals
        const grossSalary = baseSalary + overtimePay + totalAllowances;
        const netSalary = grossSalary - totalDeductions;
        
        // Update display
        document.getElementById('displayBaseSalary').textContent = '$' + baseSalary.toFixed(2);
        document.getElementById('displayOvertimePay').textContent = '$' + overtimePay.toFixed(2);
        document.getElementById('displayTotalAllowances').textContent = '$' + totalAllowances.toFixed(2);
        document.getElementById('displayGrossSalary').textContent = '$' + grossSalary.toFixed(2);
        document.getElementById('displayTotalDeductions').textContent = '-$' + totalDeductions.toFixed(2);
        document.getElementById('displayNetSalary').textContent = '$' + netSalary.toFixed(2);
    }

    // Reset display values
    function resetDisplayValues() {
        document.getElementById('displayBaseSalary').textContent = '$0.00';
        document.getElementById('displayOvertimePay').textContent = '$0.00';
        document.getElementById('displayTotalAllowances').textContent = '$0.00';
        document.getElementById('displayGrossSalary').textContent = '$0.00';
        document.getElementById('displayTotalDeductions').textContent = '-$0.00';
        document.getElementById('displayNetSalary').textContent = '$0.00';
    }

    // Load attendance data
    function loadAttendanceData() {
        const employeeId = document.getElementById('employeeSelect').value;
        if (!employeeId) {
            alert('Please select an employee first');
            return;
        }
        
        // In a real implementation, this would fetch attendance data from API
        document.querySelector('input[name="worked_hours"]').value = '160';
        document.querySelector('input[name="overtime_hours"]').value = '8';
        calculateSalary();
        
        showAlert('success', 'Attendance data loaded successfully');
    }

    // Reset calculation
    function resetCalculation() {
        document.getElementById('payrollForm').reset();
        resetDisplayValues();
        hideEmployeeInfo();
        showAlert('info', 'Calculation reset');
    }

    // Show alert
    function showAlert(type, message) {
        const alertColors = {
            success: 'bg-success/10 text-success border-success/20',
            error: 'bg-destructive/10 text-destructive border-destructive/20',
            info: 'bg-primary/10 text-primary border-primary/20'
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
        }, 3000);
    }
</script>
@endpush