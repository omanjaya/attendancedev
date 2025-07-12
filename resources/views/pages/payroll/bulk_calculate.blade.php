@extends('layouts.authenticated')

@section('title', 'Bulk Calculate Payroll')

@section('page-content')
    <!-- Page Header -->
    <div class="mb-6">
        <nav class="flex mb-6" aria-label="Breadcrumb">
            <ol class="flex items-center space-x-2 text-sm">
                <li>
                    <a href="{{ route('payroll.index') }}" class="text-gray-500 hover:text-gray-700">Payroll</a>
                </li>
                <li class="flex items-center">
                    <svg class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                    </svg>
                    <span class="ml-2 text-gray-700">Bulk Calculate</span>
                </li>
            </ol>
        </nav>
        
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Bulk Calculate Payroll</h1>
                <p class="mt-1 text-sm text-gray-600">Calculate payroll for multiple employees at once</p>
            </div>
            
            <a href="{{ route('payroll.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 ease-in-out">
                <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to List
            </a>
        </div>
    </div>
    
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-4">
        <!-- Main Content -->
        <div class="lg:col-span-3">
            <!-- Period Selection -->
            <x-ui.card title="Payroll Period" subtitle="Select the period for bulk calculation">
                <form action="{{ route('payroll.bulk-calculate') }}" method="POST" id="bulkPayrollForm">
                    @csrf
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                        <div>
                            <label for="payroll_period" class="block text-sm font-medium text-gray-700">Period Type</label>
                            <select name="payroll_period" id="payroll_period" required onchange="toggleCustomPeriod(this.value)" 
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="current_month">Current Month</option>
                                <option value="last_month">Last Month</option>
                                <option value="custom">Custom Period</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="period_start" class="block text-sm font-medium text-gray-700">Start Date</label>
                            <input type="date" name="period_start" id="periodStart" required
                                   class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        
                        <div>
                            <label for="period_end" class="block text-sm font-medium text-gray-700">End Date</label>
                            <input type="date" name="period_end" id="periodEnd" required
                                   class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                    </div>
                </form>
            </x-ui.card>
            
            <!-- Employee Selection -->
            <x-ui.card title="Employee Selection" subtitle="Choose employees to include in bulk calculation">
                <div class="space-y-6">
                    <!-- Selection Actions -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <x-ui.button
                                variant="outline"
                                size="sm"
                                onclick="selectAllEmployees()"
                            >
                                Select All
                            </x-ui.button>
                            
                            <x-ui.button
                                variant="outline"
                                size="sm"
                                onclick="deselectAllEmployees()"
                            >
                                Deselect All
                            </x-ui.button>
                            
                            <x-ui.button
                                variant="outline"
                                size="sm"
                                onclick="selectByDepartment()"
                            >
                                Select by Department
                            </x-ui.button>
                        </div>
                        
                        <div class="text-sm text-gray-600">
                            <span id="selectedCount">0</span> of <span id="totalCount">0</span> employees selected
                        </div>
                    </div>
                    
                    <!-- Employee List -->
                    <div class="border border-gray-200 rounded-lg">
                        <div class="bg-gray-50 px-6 py-3 border-b border-gray-200">
                            <div class="grid grid-cols-12 gap-4 text-sm font-medium text-gray-700">
                                <div class="col-span-1">
                                    <input type="checkbox" id="selectAllCheckbox" onchange="toggleAllEmployees(this)">
                                </div>
                                <div class="col-span-3">Employee</div>
                                <div class="col-span-2">Department</div>
                                <div class="col-span-2">Salary Type</div>
                                <div class="col-span-2">Base Salary</div>
                                <div class="col-span-2">Status</div>
                            </div>
                        </div>
                        
                        <div class="divide-y divide-gray-200" id="employeeList">
                            @php
                                $employees = [
                                    ['id' => 1, 'name' => 'John Doe', 'employee_id' => 'EMP001', 'department' => 'Engineering', 'salary_type' => 'Monthly', 'base_salary' => 5000, 'status' => 'Active'],
                                    ['id' => 2, 'name' => 'Jane Smith', 'employee_id' => 'EMP002', 'department' => 'Marketing', 'salary_type' => 'Hourly', 'base_salary' => 28, 'status' => 'Active'],
                                    ['id' => 3, 'name' => 'Mike Johnson', 'employee_id' => 'EMP003', 'department' => 'Sales', 'salary_type' => 'Monthly', 'base_salary' => 3500, 'status' => 'Active'],
                                    ['id' => 4, 'name' => 'Sarah Williams', 'employee_id' => 'EMP004', 'department' => 'HR', 'salary_type' => 'Monthly', 'base_salary' => 4000, 'status' => 'Active'],
                                    ['id' => 5, 'name' => 'David Brown', 'employee_id' => 'EMP005', 'department' => 'Engineering', 'salary_type' => 'Hourly', 'base_salary' => 32, 'status' => 'Active'],
                                ];
                            @endphp
                            
                            @foreach($employees as $employee)
                                <div class="px-6 py-4 hover:bg-gray-50">
                                    <div class="grid grid-cols-12 gap-4 items-center">
                                        <div class="col-span-1">
                                            <input 
                                                type="checkbox" 
                                                name="employee_ids[]" 
                                                value="{{ $employee['id'] }}"
                                                class="employee-checkbox"
                                                data-department="{{ $employee['department'] }}"
                                                onchange="updateSelectedCount()"
                                                form="bulkPayrollForm"
                                            >
                                        </div>
                                        <div class="col-span-3">
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">{{ $employee['name'] }}</div>
                                                <div class="text-sm text-gray-500">{{ $employee['employee_id'] }}</div>
                                            </div>
                                        </div>
                                        <div class="col-span-2">
                                            <span class="text-sm text-gray-600">{{ $employee['department'] }}</span>
                                        </div>
                                        <div class="col-span-2">
                                            <x-ui.badge variant="info" size="sm">
                                                {{ $employee['salary_type'] }}
                                            </x-ui.badge>
                                        </div>
                                        <div class="col-span-2">
                                            <span class="text-sm font-medium">
                                                ${{ number_format($employee['base_salary'], 2) }}{{ $employee['salary_type'] === 'Hourly' ? '/hr' : '/mo' }}
                                            </span>
                                        </div>
                                        <div class="col-span-2">
                                            <x-ui.badge variant="success" size="sm">
                                                {{ $employee['status'] }}
                                            </x-ui.badge>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </x-ui.card>
            
            <!-- Calculation Options -->
            <x-ui.card>
                <x-slot name="title">Calculation Options</x-slot>
                <x-slot name="subtitle">Configure bulk calculation settings</x-slot>
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div class="space-y-4">
                        <div class="flex items-center">
                            <input 
                                type="checkbox" 
                                name="auto_load_attendance" 
                                id="autoLoadAttendance" 
                                checked 
                                class="rounded border-gray-300 text-indigo-600"
                                form="bulkPayrollForm"
                            >
                            <label for="autoLoadAttendance" class="ml-2 text-sm text-gray-700">
                                Automatically load attendance data
                            </label>
                        </div>
                        
                        <div class="flex items-center">
                            <input 
                                type="checkbox" 
                                name="calculate_overtime" 
                                id="calculateOvertime" 
                                checked 
                                class="rounded border-gray-300 text-indigo-600"
                                form="bulkPayrollForm"
                            >
                            <label for="calculateOvertime" class="ml-2 text-sm text-gray-700">
                                Calculate overtime automatically
                            </label>
                        </div>
                        
                        <div class="flex items-center">
                            <input 
                                type="checkbox" 
                                name="apply_deductions" 
                                id="applyDeductions" 
                                checked 
                                class="rounded border-gray-300 text-indigo-600"
                                form="bulkPayrollForm"
                            >
                            <label for="applyDeductions" class="ml-2 text-sm text-gray-700">
                                Apply standard deductions
                            </label>
                        </div>
                    </div>
                    
                    <div class="space-y-4">
                        <div>
                            <x-ui.label for="default_overtime_rate">Default Overtime Rate</x-ui.label>
                            <x-ui.input
                                name="default_overtime_rate"
                                id="default_overtime_rate"
                                type="number"
                                step="0.01"
                                value="1.5"
                                placeholder="1.5"
                                form="bulkPayrollForm"
                            />
                        </div>
                        
                        <div>
                            <x-ui.label for="payroll_status">Initial Status</x-ui.label>
                            <x-ui.select
                                name="payroll_status"
                                id="payroll_status"
                                form="bulkPayrollForm"
                            >
                                <option value="draft" selected>Draft</option>
                                <option value="pending">Pending Review</option>
                            </x-ui.select>
                        </div>
                    </div>
                </div>
            </x-ui.card>
        </div>
        
        <!-- Summary Sidebar -->
        <div class="lg:col-span-1">
            <!-- Calculation Summary -->
            <x-ui.card>
                <x-slot name="title">Calculation Summary</x-slot>
                <x-slot name="subtitle">Expected totals</x-slot>
                <div class="space-y-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-600" id="estimatedTotal">$0.00</div>
                        <div class="text-sm text-gray-600">Estimated Total</div>
                    </div>
                    
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Selected Employees:</span>
                            <span class="font-medium" id="summaryEmployeeCount">0</span>
                        </div>
                        
                        <div class="flex justify-between">
                            <span class="text-gray-600">Estimated Gross:</span>
                            <span class="font-medium" id="estimatedGross">$0.00</span>
                        </div>
                        
                        <div class="flex justify-between">
                            <span class="text-gray-600">Estimated Deductions:</span>
                            <span class="font-medium" id="estimatedDeductions">$0.00</span>
                        </div>
                        
                        <div class="flex justify-between border-t pt-2">
                            <span class="text-gray-600">Estimated Net:</span>
                            <span class="font-semibold" id="estimatedNet">$0.00</span>
                        </div>
                    </div>
                </div>
            </x-ui.card>
            
            <!-- Action Buttons -->
            <x-ui.card>
                <x-slot name="title">Actions</x-slot>
                <x-slot name="subtitle">Execute bulk calculation</x-slot>
                <div class="space-y-3">
                    <x-ui.button
                        type="button"
                        variant="outline"
                        class="w-full"
                        onclick="previewCalculation()"
                    >
                        <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        Preview Calculation
                    </x-ui.button>
                    
                    <x-ui.button
                        type="submit"
                        variant="primary"
                        class="w-full"
                        form="bulkPayrollForm"
                        id="calculateButton"
                        disabled="true"
                    >
                        <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                        Calculate Payroll
                    </x-ui.button>
                    
                    <x-ui.button
                        variant="outline"
                        class="w-full"
                        href="{{ route('payroll.index') }}"
                    >
                        Cancel
                    </x-ui.button>
                </div>
            </x-ui.card>
            
            <!-- Recent Activity -->
            <x-ui.card>
                <x-slot name="title">Recent Bulk Calculations</x-slot>
                <x-slot name="subtitle">Latest bulk operations</x-slot>
                <div class="space-y-3 text-sm">
                    @php
                        $recentCalculations = [
                            ['period' => 'Dec 2023', 'employees' => 15, 'total' => '$45,250.00', 'status' => 'Completed'],
                            ['period' => 'Nov 2023', 'employees' => 14, 'total' => '$42,100.00', 'status' => 'Completed'],
                            ['period' => 'Oct 2023', 'employees' => 16, 'total' => '$48,300.00', 'status' => 'Completed'],
                        ];
                    @endphp
                    
                    @foreach($recentCalculations as $calc)
                        <div class="border-b border-gray-100 pb-2 {{ $loop->last ? 'border-b-0' : '' }}">
                            <div class="flex justify-between items-center">
                                <div>
                                    <div class="font-medium">{{ $calc['period'] }}</div>
                                    <div class="text-gray-500">{{ $calc['employees'] }} employees</div>
                                </div>
                                <div class="text-right">
                                    <div class="font-medium">{{ $calc['total'] }}</div>
                                    <x-ui.badge variant="success" size="xs">
                                        {{ $calc['status'] }}
                                    </x-ui.badge>
                                </div>
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
    // Initialize page
    document.addEventListener('DOMContentLoaded', function() {
        setCurrentMonth();
        updateSelectedCount();
        updateTotalCount();
    });

    // Set current month dates
    function setCurrentMonth() {
        const now = new Date();
        const start = new Date(now.getFullYear(), now.getMonth(), 1);
        const end = new Date(now.getFullYear(), now.getMonth() + 1, 0);
        
        document.getElementById('periodStart').value = start.toISOString().split('T')[0];
        document.getElementById('periodEnd').value = end.toISOString().split('T')[0];
    }

    // Toggle custom period
    function toggleCustomPeriod(value) {
        if (value === 'current_month') {
            setCurrentMonth();
        } else if (value === 'last_month') {
            const now = new Date();
            const start = new Date(now.getFullYear(), now.getMonth() - 1, 1);
            const end = new Date(now.getFullYear(), now.getMonth(), 0);
            
            document.getElementById('periodStart').value = start.toISOString().split('T')[0];
            document.getElementById('periodEnd').value = end.toISOString().split('T')[0];
        }
    }

    // Select all employees
    function selectAllEmployees() {
        const checkboxes = document.querySelectorAll('.employee-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = true;
        });
        document.getElementById('selectAllCheckbox').checked = true;
        updateSelectedCount();
    }

    // Deselect all employees
    function deselectAllEmployees() {
        const checkboxes = document.querySelectorAll('.employee-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = false;
        });
        document.getElementById('selectAllCheckbox').checked = false;
        updateSelectedCount();
    }

    // Select by department
    function selectByDepartment() {
        const department = prompt('Enter department name (Engineering, Marketing, Sales, HR):');
        if (!department) return;
        
        const checkboxes = document.querySelectorAll('.employee-checkbox');
        checkboxes.forEach(checkbox => {
            if (checkbox.dataset.department === department) {
                checkbox.checked = true;
            }
        });
        updateSelectedCount();
    }

    // Toggle all employees
    function toggleAllEmployees(selectAllCheckbox) {
        const checkboxes = document.querySelectorAll('.employee-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = selectAllCheckbox.checked;
        });
        updateSelectedCount();
    }

    // Update selected count
    function updateSelectedCount() {
        const selectedCheckboxes = document.querySelectorAll('.employee-checkbox:checked');
        const selectedCount = selectedCheckboxes.length;
        
        document.getElementById('selectedCount').textContent = selectedCount;
        document.getElementById('summaryEmployeeCount').textContent = selectedCount;
        
        // Enable/disable calculate button
        const calculateButton = document.getElementById('calculateButton');
        if (selectedCount > 0) {
            calculateButton.disabled = false;
            calculateButton.classList.remove('opacity-50', 'cursor-not-allowed');
        } else {
            calculateButton.disabled = true;
            calculateButton.classList.add('opacity-50', 'cursor-not-allowed');
        }
        
        // Update estimates
        updateEstimates(selectedCount);
    }

    // Update total count
    function updateTotalCount() {
        const totalCheckboxes = document.querySelectorAll('.employee-checkbox');
        document.getElementById('totalCount').textContent = totalCheckboxes.length;
    }

    // Update estimates
    function updateEstimates(selectedCount) {
        // Simple estimation based on average salary
        const avgSalary = 4000;
        const grossTotal = selectedCount * avgSalary;
        const deductionsTotal = grossTotal * 0.15; // 15% average deduction
        const netTotal = grossTotal - deductionsTotal;
        
        document.getElementById('estimatedGross').textContent = '$' + grossTotal.toLocaleString();
        document.getElementById('estimatedDeductions').textContent = '$' + deductionsTotal.toLocaleString();
        document.getElementById('estimatedNet').textContent = '$' + netTotal.toLocaleString();
        document.getElementById('estimatedTotal').textContent = '$' + netTotal.toLocaleString();
    }

    // Preview calculation
    function previewCalculation() {
        const selectedCheckboxes = document.querySelectorAll('.employee-checkbox:checked');
        if (selectedCheckboxes.length === 0) {
            alert('Please select at least one employee');
            return;
        }
        
        // In a real implementation, this would show a detailed preview modal
        alert(`Preview: ${selectedCheckboxes.length} employees selected for payroll calculation`);
    }
</script>
@endpush