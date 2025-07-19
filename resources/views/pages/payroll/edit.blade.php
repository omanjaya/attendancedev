@extends('layouts.authenticated-unified')

@section('title', 'Detail Penggajian')

@section('page-content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100 dark:from-slate-900 dark:via-blue-900 dark:to-indigo-900">
    <div class="p-6 lg:p-8">
        <x-layouts.base-page
            title="Detail Penggajian"
            subtitle="Detail lengkap catatan penggajian ini"
            :breadcrumbs="[
                ['label' => 'Dashboard', 'url' => route('dashboard')],
                ['label' => 'Penggajian', 'url' => route('payroll.index')],
                ['label' => 'Detail Penggajian']
            ]">
            <x-slot name="actions">
                <x-ui.button variant="secondary" onclick="window.print()">
                    <svg class="w-5 h-5 mr-2 group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    Cetak
                </x-ui.button>
                
                @if($payroll->status === 'pending')
                    <x-ui.button variant="success" onclick="approvePayroll()">
                        <svg class="w-5 h-5 mr-2 group-hover:rotate-90 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Setujui
                    </x-ui.button>
                @endif
                
                <x-ui.button variant="secondary" href="{{ route('payroll.index') }}">
                    <svg class="w-5 h-5 mr-2 group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Kembali ke Daftar
                </x-ui.button>
            </x-slot>
        </x-layouts.base-page>
        
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
            <div class="lg:col-span-2 space-y-8">
                <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                    <h3 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Informasi Karyawan</h3>
                    <p class="text-slate-600 dark:text-slate-400 mb-6">Detail dasar karyawan</p>
                    
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div class="space-y-4">
                            <div>
                                <x-ui.label value="Nama Lengkap" class="text-slate-700 dark:text-slate-300 mb-1" />
                                <p class="text-sm text-slate-800 dark:text-white font-medium">John Doe</p>
                            </div>
                            
                            <div>
                                <x-ui.label value="ID Karyawan" class="text-slate-700 dark:text-slate-300 mb-1" />
                                <p class="text-sm text-slate-800 dark:text-white font-medium">EMP001</p>
                            </div>
                            
                            <div>
                                <x-ui.label value="Departemen" class="text-slate-700 dark:text-slate-300 mb-1" />
                                <p class="text-sm text-slate-800 dark:text-white font-medium">Teknik</p>
                            </div>
                        </div>
                        
                        <div class="space-y-4">
                            <div>
                                <x-ui.label value="Posisi" class="text-slate-700 dark:text-slate-300 mb-1" />
                                <p class="text-sm text-slate-800 dark:text-white font-medium">Pengembang Senior</p>
                            </div>
                            
                            <div>
                                <x-ui.label value="Tipe Pekerjaan" class="text-slate-700 dark:text-slate-300 mb-1" />
                                <p class="text-sm text-slate-800 dark:text-white font-medium">Staf Tetap</p>
                            </div>
                            
                            <div>
                                <x-ui.label value="Tipe Gaji" class="text-slate-700 dark:text-slate-300 mb-1" />
                                <x-ui.badge variant="secondary" size="sm">Bulanan</x-ui.badge>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                    <h3 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Periode & Jam Penggajian</h3>
                    <p class="text-slate-600 dark:text-slate-400 mb-6">Periode kerja dan detail waktu</p>
                    
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                        <div class="text-center p-4 bg-white/10 rounded-lg">
                            <x-ui.label value="Periode Pembayaran" class="text-slate-700 dark:text-slate-300 mb-2" />
                            <p class="text-lg font-semibold text-slate-800 dark:text-white">Januari 2024</p>
                            <p class="text-sm text-slate-600 dark:text-slate-400">1 Jan - 31 Jan, 2024</p>
                        </div>
                        
                        <div class="text-center p-4 bg-white/10 rounded-lg">
                            <x-ui.label value="Jam Reguler" class="text-slate-700 dark:text-slate-300 mb-2" />
                            <p class="text-lg font-semibold text-slate-800 dark:text-white">160 jam</p>
                            <p class="text-sm text-slate-600 dark:text-slate-400">Jam kerja standar</p>
                        </div>
                        
                        <div class="text-center p-4 bg-white/10 rounded-lg">
                            <x-ui.label value="Jam Lembur" class="text-slate-700 dark:text-slate-300 mb-2" />
                            <p class="text-lg font-semibold text-green-500">8 jam</p>
                            <p class="text-sm text-slate-600 dark:text-slate-400">Dengan tarif 1.5x</p>
                        </div>
                    </div>
                </div>
                
                <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                    <h3 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Rincian Gaji</h3>
                    <p class="text-slate-600 dark:text-slate-400 mb-6">Rincian perhitungan detail</p>
                    
                    <div class="space-y-8">
                        <div>
                            <h4 class="text-lg font-semibold text-slate-800 dark:text-white mb-6">Penghasilan</h4>
                            <div class="space-y-3 text-slate-600 dark:text-slate-400">
                                <div class="flex justify-between items-center py-3 border-b border-white/20">
                                    <span class="text-sm">Gaji Pokok</span>
                                    <span class="font-medium text-slate-800 dark:text-white">$5,000.00</span>
                                </div>
                                
                                <div class="flex justify-between items-center py-3 border-b border-white/20">
                                    <span class="text-sm">Gaji Lembur (8j × $46.88)</span>
                                    <span class="font-medium text-slate-800 dark:text-white">$375.00</span>
                                </div>
                                
                                <div class="flex justify-between items-center py-3 border-b border-white/20">
                                    <span class="text-sm">Tunjangan Transportasi</span>
                                    <span class="font-medium text-slate-800 dark:text-white">$200.00</span>
                                </div>
                                
                                <div class="flex justify-between items-center py-3 border-b border-white/20">
                                    <span class="text-sm">Tunjangan Makan</span>
                                    <span class="font-medium text-slate-800 dark:text-white">$150.00</span>
                                </div>
                                
                                <div class="flex justify-between items-center py-3 border-b-2 border-white/20 font-semibold">
                                    <span class="text-slate-800 dark:text-white">Total Penghasilan</span>
                                    <span class="text-green-500">$5,725.00</span>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <h4 class="text-lg font-semibold text-slate-800 dark:text-white mb-6">Potongan</h4>
                            <div class="space-y-3 text-slate-600 dark:text-slate-400">
                                <div class="flex justify-between items-center py-3 border-b border-white/20">
                                    <span class="text-sm">Pajak Penghasilan</span>
                                    <span class="font-medium text-red-500">-$687.00</span>
                                </div>
                                
                                <div class="flex justify-between items-center py-3 border-b border-white/20">
                                    <span class="text-sm">Jaminan Sosial</span>
                                    <span class="font-medium text-red-500">-$344.98</span>
                                </div>
                                
                                <div class="flex justify-between items-center py-3 border-b border-white/20">
                                    <span class="text-sm">Asuransi Kesehatan</span>
                                    <span class="font-medium text-red-500">-$125.00</span>
                                </div>
                                
                                <div class="flex justify-between items-center py-3 border-b-2 border-white/20 font-semibold">
                                    <span class="text-slate-800 dark:text-white">Total Potongan</span>
                                    <span class="text-red-500">-$1,156.98</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-blue-500/10 p-6 rounded-lg">
                            <div class="flex justify-between items-center">
                                <span class="text-xl font-semibold text-slate-800 dark:text-white">Gaji Bersih</span>
                                <span class="text-2xl font-bold text-blue-500">$4,568.02</span>
                            </div>
                            <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">Jumlah yang akan dibayarkan kepada karyawan</p>
                        </div>
                    </div>
                </div>
                
                <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                    <h3 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Informasi Tambahan</h3>
                    <p class="text-slate-600 dark:text-slate-400 mb-6">Detail pembayaran dan catatan</p>
                    
                    <div class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <x-ui.label for="payment_method" value="Metode Pembayaran" class="text-slate-700 dark:text-slate-300" />
                                <x-ui.select name="payment_method" id="payment_method" 
                                           class="bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300">
                                    <option value="">Pilih Metode</option>
                                    <option value="bank_transfer" {{ old('payment_method', $payroll->payment_method) == 'bank_transfer' ? 'selected' : '' }}>Transfer Bank</option>
                                    <option value="cash" {{ old('payment_method', $payroll->payment_method) == 'cash' ? 'selected' : '' }}>Tunai</option>
                                    <option value="check" {{ old('payment_method', $payroll->payment_method) == 'check' ? 'selected' : '' }}>Cek</option>
                                    <option value="digital_wallet" {{ old('payment_method', $payroll->payment_method) == 'digital_wallet' ? 'selected' : '' }}>Dompet Digital</option>
                                </x-ui.select>
                                @error('payment_method')
                                    <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            @if($payroll->status === 'paid')
                            <div>
                                <x-ui.label for="payment_date" value="Tanggal Pembayaran" class="text-slate-700 dark:text-slate-300" />
                                <x-ui.input type="date" name="payment_date" id="payment_date" 
                                           value="{{ old('payment_date', $payroll->payment_date?->format('Y-m-d')) }}" 
                                           class="bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300" />
                                @error('payment_date')
                                    <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            @endif
                        </div>

                        <div>
                            <x-ui.label for="notes" value="Catatan" class="text-slate-700 dark:text-slate-300" />
                            <textarea name="notes" id="notes" rows="3" 
                                      class="flex min-h-[80px] w-full rounded-md border border-white/40 bg-white/30 backdrop-blur-sm px-3 py-2 text-sm ring-offset-background placeholder:text-slate-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/50 focus-visible:border-blue-500/50 disabled:cursor-not-allowed disabled:opacity-50 text-slate-800 dark:text-white transition-all duration-300" 
                                      placeholder="Catatan tambahan tentang catatan penggajian ini...">{{ old('notes', $payroll->notes) }}</textarea>
                            @error('notes')
                                <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        @if($payroll->created_at)
                        <div class="pt-4 border-t border-white/20">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-slate-600 dark:text-slate-400">
                                <div>
                                    <span class="font-medium">Dibuat:</span> <span class="text-slate-800 dark:text-white">{{ $payroll->created_at->format('M j, Y g:i A') }}</span>
                                </div>
                                <div>
                                    <span class="font-medium">Terakhir Diubah:</span> <span class="text-slate-800 dark:text-white">{{ $payroll->updated_at->format('M j, Y g:i A') }}</span>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-end space-x-4">
            <x-ui.button type="button" variant="secondary" onclick="history.back()">
                Batal
            </x-ui.button>
            <x-ui.button type="submit" variant="warning">
                <svg class="w-5 h-5 mr-2 group-hover:rotate-90 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                Perbarui Catatan Penggajian
            </x-ui.button>
        </div>
    </form>
</div>
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
                    alert('Tanggal akhir periode harus setelah tanggal mulai periode');
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