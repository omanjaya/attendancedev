@extends('layouts.authenticated-unified')

@section('title', 'Hitung Penggajian Massal')

@section('page-content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100 dark:from-slate-900 dark:via-blue-900 dark:to-indigo-900">
    <div class="p-6 lg:p-8">
        <x-layouts.base-page
            title="Hitung Penggajian Massal"
            subtitle="Hitung penggajian untuk beberapa karyawan sekaligus"
            :breadcrumbs="[
                ['label' => 'Dashboard', 'url' => route('dashboard')],
                ['label' => 'Penggajian', 'url' => route('payroll.index')],
                ['label' => 'Hitung Massal']
            ]">
            <x-slot name="actions">
                <x-ui.button variant="secondary" href="{{ route('payroll.index') }}">
                    <svg class="w-5 h-5 mr-2 group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Kembali ke Daftar
                </x-ui.button>
            </x-slot>
        </x-layouts.base-page>
        
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-4">
            <!-- Main Content -->
            <div class="lg:col-span-3 space-y-6">
                <!-- Period Selection -->
                <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                    <h3 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Periode Penggajian</h3>
                    <p class="text-slate-600 dark:text-slate-400 mb-6">Pilih periode untuk perhitungan massal</p>
                    <form action="{{ route('payroll.bulk-calculate') }}" method="POST" id="bulkPayrollForm">
                        @csrf
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                            <div>
                                <x-ui.label for="payroll_period" value="Tipe Periode" class="text-slate-700 dark:text-slate-300" />
                                <x-ui.select name="payroll_period" id="payroll_period" required onchange="toggleCustomPeriod(this.value)" 
                                        class="bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300">
                                    <option value="current_month">Bulan Ini</option>
                                    <option value="last_month">Bulan Lalu</option>
                                    <option value="custom">Periode Kustom</option>
                                </x-ui.select>
                            </div>
                            
                            <div>
                                <x-ui.label for="periodStart" value="Tanggal Mulai" class="text-slate-700 dark:text-slate-300" />
                                <x-ui.input type="date" name="period_start" id="periodStart" required
                                           class="bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300" />
                            </div>
                            
                            <div>
                                <x-ui.label for="periodEnd" value="Tanggal Berakhir" class="text-slate-700 dark:text-slate-300" />
                                <x-ui.input type="date" name="period_end" id="periodEnd" required
                                           class="bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300" />
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Employee Selection -->
                <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                    <h3 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Pemilihan Karyawan</h3>
                    <p class="text-slate-600 dark:text-slate-400 mb-6">Pilih karyawan untuk disertakan dalam perhitungan massal</p>
                    <div class="space-y-6">
                        <!-- Selection Actions -->
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <x-ui.button variant="secondary" size="sm" onclick="selectAllEmployees()">
                                    Pilih Semua
                                </x-ui.button>
                                
                                <x-ui.button variant="secondary" size="sm" onclick="deselectAllEmployees()">
                                    Batalkan Pilihan Semua
                                </x-ui.button>
                                
                                <x-ui.button variant="secondary" size="sm" onclick="selectByDepartment()">
                                    Pilih berdasarkan Departemen
                                </x-ui.button>
                            </div>
                            
                            <div class="text-sm text-slate-600 dark:text-slate-400">
                                <span id="selectedCount">0</span> dari <span id="totalCount">0</span> karyawan terpilih
                            </div>
                        </div>
                        
                        <!-- Employee List -->
                        <div class="border border-white/20 rounded-lg">
                            <div class="bg-white/10 px-6 py-3 border-b border-white/20">
                                <div class="grid grid-cols-12 gap-4 text-sm font-semibold text-slate-700 dark:text-slate-300">
                                    <div class="col-span-1">
                                        <input type="checkbox" id="selectAllCheckbox" onchange="toggleAllEmployees(this)" class="form-checkbox h-4 w-4 text-blue-600 rounded"/>
                                    </div>
                                    <div class="col-span-3">Karyawan</div>
                                    <div class="col-span-2">Departemen</div>
                                    <div class="col-span-2">Tipe Gaji</div>
                                    <div class="col-span-2">Gaji Pokok</div>
                                    <div class="col-span-2">Status</div>
                                </div>
                            </div>
                            
                            <div class="divide-y divide-white/10" id="employeeList">
                                @php
                                    $employees = [
                                        ['id' => 1, 'name' => 'John Doe', 'employee_id' => 'EMP001', 'department' => 'Engineering', 'salary_type' => 'Bulanan', 'base_salary' => 5000, 'status' => 'Aktif'],
                                        ['id' => 2, 'name' => 'Jane Smith', 'employee_id' => 'EMP002', 'department' => 'Marketing', 'salary_type' => 'Per Jam', 'base_salary' => 28, 'status' => 'Aktif'],
                                        ['id' => 3, 'name' => 'Mike Johnson', 'employee_id' => 'EMP003', 'department' => 'Sales', 'salary_type' => 'Bulanan', 'base_salary' => 3500, 'status' => 'Aktif'],
                                        ['id' => 4, 'name' => 'Sarah Williams', 'employee_id' => 'EMP004', 'department' => 'HR', 'salary_type' => 'Bulanan', 'base_salary' => 4000, 'status' => 'Aktif'],
                                        ['id' => 5, 'name' => 'David Brown', 'employee_id' => 'EMP005', 'department' => 'Engineering', 'salary_type' => 'Per Jam', 'base_salary' => 32, 'status' => 'Aktif'],
                                    ];
                                @endphp
                                
                                @foreach($employees as $employee)
                                    <div class="px-6 py-4 hover:bg-white/5">
                                        <div class="grid grid-cols-12 gap-4 items-center">
                                            <div class="col-span-1">
                                                <input 
                                                    type="checkbox" 
                                                    name="employee_ids[]" 
                                                    value="{{ $employee['id'] }}"
                                                    class="employee-checkbox form-checkbox h-4 w-4 text-blue-600 rounded"
                                                    data-department="{{ $employee['department'] }}"
                                                    onchange="updateSelectedCount()"
                                                    form="bulkPayrollForm"
                                                >
                                            </div>
                                            <div class="col-span-3">
                                                <div>
                                                    <div class="text-sm font-medium text-slate-800 dark:text-white">{{ $employee['name'] }}</div>
                                                    <div class="text-sm text-slate-500 dark:text-slate-400">{{ $employee['employee_id'] }}</div>
                                                </div>
                                            </div>
                                            <div class="col-span-2">
                                                <span class="text-sm text-slate-600 dark:text-slate-400">{{ $employee['department'] }}</span>
                                            </div>
                                            <div class="col-span-2">
                                                <x-ui.badge variant="info" size="sm">
                                                    {{ $employee['salary_type'] }}
                                                </x-ui.badge>
                                            </div>
                                            <div class="col-span-2">
                                                <span class="text-sm font-medium text-slate-800 dark:text-white">
                                                    ${{ number_format($employee['base_salary'], 2) }}{{ $employee['salary_type'] === 'Per Jam' ? '/jam' : '/bln' }}
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
                </div>
                
                <!-- Calculation Options -->
                <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                    <h3 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Opsi Perhitungan</h3>
                    <p class="text-slate-600 dark:text-slate-400 mb-6">Konfigurasi pengaturan perhitungan massal</p>
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div class="space-y-4">
                            <div class="flex items-center space-x-3">
                                <input 
                                    type="checkbox" 
                                    name="auto_load_attendance" 
                                    id="autoLoadAttendance" 
                                    checked 
                                    class="form-checkbox h-4 w-4 text-blue-600 rounded"
                                    form="bulkPayrollForm"
                                >
                                <label for="autoLoadAttendance" class="text-sm text-slate-700 dark:text-slate-300">
                                    Muat data absensi secara otomatis
                                </label>
                            </div>
                            
                            <div class="flex items-center space-x-3">
                                <input 
                                    type="checkbox" 
                                    name="calculate_overtime" 
                                    id="calculateOvertime" 
                                    checked 
                                    class="form-checkbox h-4 w-4 text-blue-600 rounded"
                                    form="bulkPayrollForm"
                                >
                                <label for="calculateOvertime" class="text-sm text-slate-700 dark:text-slate-300">
                                    Hitung lembur secara otomatis
                                </label>
                            </div>
                            
                            <div class="flex items-center space-x-3">
                                <input 
                                    type="checkbox" 
                                    name="apply_deductions" 
                                    id="applyDeductions" 
                                    checked 
                                    class="form-checkbox h-4 w-4 text-blue-600 rounded"
                                    form="bulkPayrollForm"
                                >
                                <label for="applyDeductions" class="text-sm text-slate-700 dark:text-slate-300">
                                    Terapkan potongan standar
                                </label>
                            </div>
                        </div>
                        
                        <div class="space-y-4">
                            <div>
                                <x-ui.label for="default_overtime_rate" class="text-slate-700 dark:text-slate-300">Tarif Lembur Default</x-ui.label>
                                <x-ui.input
                                    name="default_overtime_rate"
                                    id="default_overtime_rate"
                                    type="number"
                                    step="0.01"
                                    value="1.5"
                                    placeholder="1.5"
                                    form="bulkPayrollForm"
                                    class="bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300"
                                />
                            </div>
                            
                            <div>
                                <x-ui.label for="payroll_status" class="text-slate-700 dark:text-slate-300">Status Awal</x-ui.label>
                                <x-ui.select
                                    name="payroll_status"
                                    id="payroll_status"
                                    form="bulkPayrollForm"
                                    class="bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300"
                                >
                                    <option value="draft" selected>Draf</option>
                                    <option value="pending">Menunggu Peninjauan</option>
                                </x-ui.select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Summary Sidebar -->
            <div class="lg:col-span-1 space-y-6">
                <!-- Calculation Summary -->
                <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                    <h3 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Ringkasan Perhitungan</h3>
                    <p class="text-slate-600 dark:text-slate-400 mb-6">Total yang diharapkan</p>
                    <div class="space-y-4">
                        <div class="text-center">
                            <div class="text-3xl font-bold text-blue-600" id="estimatedTotal">$0.00</div>
                            <div class="text-sm text-slate-600 dark:text-slate-400">Total Estimasi</div>
                        </div>
                        
                        <div class="space-y-3 text-sm text-slate-600 dark:text-slate-400">
                            <div class="flex justify-between">
                                <span class="font-medium">Karyawan Terpilih:</span>
                                <span class="font-medium text-slate-800 dark:text-white" id="summaryEmployeeCount">0</span>
                            </div>
                            
                            <div class="flex justify-between">
                                <span class="font-medium">Gaji Kotor Estimasi:</span>
                                <span class="font-medium text-slate-800 dark:text-white" id="estimatedGross">$0.00</span>
                            </div>
                            
                            <div class="flex justify-between">
                                <span class="font-medium">Potongan Estimasi:</span>
                                <span class="font-medium text-slate-800 dark:text-white" id="estimatedDeductions">$0.00</span>
                            </div>
                            
                            <div class="flex justify-between border-t border-white/20 pt-3">
                                <span class="font-medium">Gaji Bersih Estimasi:</span>
                                <span class="font-semibold text-slate-800 dark:text-white" id="estimatedNet">$0.00</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                    <h3 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Aksi</h3>
                    <p class="text-slate-600 dark:text-slate-400 mb-6">Jalankan perhitungan massal</p>
                    <div class="space-y-3">
                        <x-ui.button
                            type="button"
                            variant="secondary"
                            class="w-full"
                            onclick="previewCalculation()"
                        >
                            <svg class="w-5 h-5 mr-2 group-hover:rotate-90 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            Pratinjau Perhitungan
                        </x-ui.button>
                        
                        <x-ui.button
                            type="submit"
                            variant="primary"
                            class="w-full"
                            form="bulkPayrollForm"
                            id="calculateButton"
                            disabled="true"
                        >
                            <svg class="w-5 h-5 mr-2 group-hover:rotate-90 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            Hitung Penggajian
                        </x-ui.button>
                        
                        <x-ui.button
                            variant="secondary"
                            class="w-full"
                            href="{{ route('payroll.index') }}"
                        >
                            Batal
                        </x-ui.button>
                    </div>
                </div>
                
                <!-- Recent Activity -->
                <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                    <h3 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Perhitungan Massal Terbaru</h3>
                    <p class="text-slate-600 dark:text-slate-400 mb-6">Operasi massal terbaru</p>
                    <div class="space-y-3 text-sm">
                        @php
                            $recentCalculations = [
                                ['period' => 'Des 2023', 'employees' => 15, 'total' => '$45,250.00', 'status' => 'Selesai'],
                                ['period' => 'Nov 2023', 'employees' => 14, 'total' => '$42,100.00', 'status' => 'Selesai'],
                                ['period' => 'Okt 2023', 'employees' => 16, 'total' => '$48,300.00', 'status' => 'Selesai'],
                            ];
                        @endphp
                        
                        @foreach($recentCalculations as $calc)
                            <div class="border-b border-white/20 pb-2 {{ $loop->last ? 'border-b-0' : '' }}">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <div class="font-medium text-slate-800 dark:text-white">{{ $calc['period'] }}</div>
                                        <div class="text-slate-600 dark:text-slate-400">{{ $calc['employees'] }} karyawan</div>
                                    </div>
                                    <div class="text-right">
                                        <div class="font-medium text-slate-800 dark:text-white">{{ $calc['total'] }}</div>
                                        <x-ui.badge variant="success" size="xs">
                                            {{ $calc['status'] }}
                                        </x-ui.badge>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
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
        const department = prompt('Masukkan nama departemen (Engineering, Marketing, Sales, HR):');
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
            alert('Mohon pilih setidaknya satu karyawan');
            return;
        }
        
        // In a real implementation, this would show a detailed preview modal
        alert(`Pratinjau: ${selectedCheckboxes.length} karyawan terpilih untuk perhitungan penggajian`);
    }
</script>
@endpush
