@extends('layouts.authenticated-unified')

@section('title', 'Ajukan Cuti')

@section('page-content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100 dark:from-slate-900 dark:via-blue-900 dark:to-indigo-900">
    <div class="p-6 lg:p-8">
        <x-layouts.base-page
            title="Ajukan Cuti"
            subtitle="Kirim permintaan cuti baru"
            :breadcrumbs="[
                ['label' => 'Dashboard', 'url' => route('dashboard')],
                ['label' => 'Cuti', 'url' => route('leave.index')],
                ['label' => 'Ajukan Cuti']
            ]">
            <x-slot name="actions">
                <x-ui.button variant="secondary" href="{{ route('leave.index') }}">
                    <svg class="w-5 h-5 mr-2 group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Kembali ke Daftar
                </x-ui.button>
            </x-slot>
        </x-layouts.base-page>

        <form action="{{ route('leave.store') }}" method="POST">
            @csrf
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-2 group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                    <h3 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Detail Permintaan Cuti</h3>
                    <p class="text-slate-600 dark:text-slate-400 mb-6">Isi informasi permintaan cuti Anda</p>
                    
                    <div class="space-y-6">
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div class="space-y-2">
                                <x-ui.label for="leave_type_id" value="Tipe Cuti" required class="text-slate-700 dark:text-slate-300" />
                                <x-ui.select name="leave_type_id" id="leave_type_id" required class="bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300">
                                    <option value="">Pilih Tipe Cuti</option>
                                    @foreach($leaveTypes as $leaveType)
                                        <option value="{{ $leaveType->id }}" {{ old('leave_type_id') == $leaveType->id ? 'selected' : '' }}>
                                            {{ $leaveType->display_name }}
                                        </option>
                                    @endforeach
                                </x-ui.select>
                                @error('leave_type_id')
                                    <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <div class="space-y-2">
                                <x-ui.label value="Saldo Saat Ini" class="text-slate-700 dark:text-slate-300" />
                                <div id="leave-balance" class="p-3 bg-white/10 rounded-lg border border-white/20 text-sm text-slate-600 dark:text-slate-400">
                                    Pilih tipe cuti untuk melihat saldo
                                </div>
                            </div>
                        </div>
                    
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div class="space-y-2">
                                <x-ui.label for="start_date" value="Tanggal Mulai" required class="text-slate-700 dark:text-slate-300" />
                                <x-ui.input type="date" name="start_date" id="start_date" 
                                           value="{{ old('start_date') }}" min="{{ date('Y-m-d') }}" required class="bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300" />
                                @error('start_date')
                                    <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            
                            <div class="space-y-2">
                                <x-ui.label for="end_date" value="Tanggal Berakhir" required class="text-slate-700 dark:text-slate-300" />
                                <x-ui.input type="date" name="end_date" id="end_date" 
                                           value="{{ old('end_date') }}" min="{{ date('Y-m-d') }}" required class="bg-white/30 backdrop-blur-sm border border-white/40 text-slate-800 dark:text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-300" />
                                @error('end_date')
                                    <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div class="space-y-2">
                                <x-ui.label value="Hari Kerja" class="text-slate-700 dark:text-slate-300" />
                                <div id="working-days" class="p-3 bg-white/10 rounded-lg border border-white/20 text-sm text-slate-600 dark:text-slate-400">
                                    Pilih tanggal untuk menghitung
                                </div>
                            </div>
                            
                            <div class="space-y-2">
                                <x-ui.label value="Cuti Darurat" class="text-slate-700 dark:text-slate-300" />
                                <div class="flex items-center space-x-3">
                                    <input type="hidden" name="is_emergency" value="0">
                                    <x-ui.checkbox name="is_emergency" value="1" {{ old('is_emergency') ? 'checked' : '' }} />
                                    <div>
                                        <span class="text-sm font-medium text-slate-800 dark:text-white">Cuti Darurat</span>
                                        <p class="text-xs text-slate-600 dark:text-slate-400">Centang jika ini adalah permintaan cuti darurat</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    
                        <div class="space-y-2">
                            <x-ui.label for="reason" value="Alasan Cuti" class="text-slate-700 dark:text-slate-300" />
                            <textarea name="reason" id="reason"
                                      class="flex min-h-[80px] w-full rounded-md border border-white/40 bg-white/30 backdrop-blur-sm px-3 py-2 text-sm ring-offset-background placeholder:text-slate-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/50 focus-visible:border-blue-500/50 disabled:cursor-not-allowed disabled:opacity-50 text-slate-800 dark:text-white transition-all duration-300" 
                                      rows="4" placeholder="Mohon berikan alasan untuk permintaan cuti Anda...">{{ old('reason') }}</textarea>
                            @error('reason')
                                <p class="text-sm text-red-500 mt-1">{{ $message }}</p>
                            @enderror
                            <p class="text-xs text-slate-600 dark:text-slate-400">Opsional: Berikan detail tambahan tentang permintaan cuti Anda</p>
                        </div>
                    </div>
                </div>
            
                <div class="space-y-6">
                    <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                        <h3 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Ringkasan Saldo Cuti</h3>
                        <p class="text-slate-600 dark:text-slate-400 mb-6">Hari cuti Anda yang tersedia</p>
                        
                        <div id="balance-summary" class="space-y-4">
                            @if($leaveBalances->count() > 0)
                                @foreach($leaveBalances as $balance)
                                <div class="p-4 bg-white/10 rounded-lg">
                                    <div class="flex justify-between items-center mb-3">
                                        <span class="font-medium text-slate-800 dark:text-white">{{ $balance->leaveType->name }}</span>
                                        <x-ui.badge variant="secondary">{{ $balance->remaining_days }} hari tersisa</x-ui.badge>
                                    </div>
                                    <div class="w-full bg-white/20 rounded-full h-2">
                                        @php
                                            $percentage = $balance->allocated_days > 0 ? ($balance->remaining_days / $balance->allocated_days) * 100 : 0;
                                            $colorClass = $percentage > 50 ? 'bg-green-500' : ($percentage > 20 ? 'bg-amber-500' : 'bg-red-500');
                                        @endphp
                                        <div class="{{ $colorClass }} h-2 rounded-full transition-all" style="width: {{ $percentage }}%"></div>
                                    </div>
                                    <p class="text-xs text-slate-600 dark:text-slate-400 mt-2">
                                        {{ $balance->used_days }} terpakai dari {{ $balance->allocated_days }} dialokasikan
                                    </p>
                                </div>
                                @endforeach
                            @else
                                <div class="text-center py-8 text-slate-600 dark:text-slate-400">
                                    <svg class="w-12 h-12 mx-auto mb-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <circle cx="12" cy="12" r="9"/>
                                        <line x1="9" y1="9" x2="15" y2="15"/>
                                        <line x1="15" y1="9" x2="9" y2="15"/>
                                    </svg>
                                    <p class="font-medium">Tidak ada saldo cuti ditemukan</p>
                                    <p class="text-sm">Hubungi HR untuk mengatur hak cuti Anda</p>
                                </div>
                            @endif
                        </div>
                    </div>
                
                    <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                        <h3 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Catatan Penting</h3>
                        <p class="text-slate-600 dark:text-slate-400 mb-6">Mohon baca sebelum mengirimkan</p>
                        
                        <div class="space-y-3 text-sm text-slate-600 dark:text-slate-400">
                            <div class="flex items-start space-x-2">
                                <svg class="w-5 h-5 mt-0.5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span>Permintaan cuti harus diajukan setidaknya 24 jam sebelumnya</span>
                            </div>
                            <div class="flex items-start space-x-2">
                                <svg class="w-5 h-5 mt-0.5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span>Cuti darurat dapat disetujui secara retroaktif</span>
                            </div>
                            <div class="flex items-start space-x-2">
                                <svg class="w-5 h-5 mt-0.5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span>Hari libur akhir pekan secara otomatis dikecualikan dari perhitungan cuti</span>
                            </div>
                            <div class="flex items-start space-x-2">
                                <svg class="w-5 h-5 mt-0.5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span>Cuti yang disetujui akan dipotong dari saldo cuti Anda</span>
                            </div>
                        </div>
                    </div>
                
                    <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                        <h3 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Kirim Permintaan</h3>
                        
                        <div class="space-y-3">
                            <x-ui.button type="submit" class="w-full group relative px-6 py-3 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-300 ease-out" id="submit-btn">
                                <div class="flex items-center justify-center space-x-2">
                                    <svg class="w-5 h-5 group-hover:rotate-90 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l7-7-7-7"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14"/>
                                    </svg>
                                    <span class="font-medium">Kirim Permintaan Cuti</span>
                                </div>
                            </x-ui.button>
                            <x-ui.button type="button" variant="secondary" class="w-full" onclick="window.location.href='{{ route('leave.index') }}'">
                                Batal
                            </x-ui.button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Update leave balance when leave type changes
    $('#leave_type_id').on('change', function() {
        const leaveTypeId = $(this).val();
        const balanceDiv = $('#leave-balance');
        
        if (!leaveTypeId) {
            balanceDiv.html('<span class="text-slate-600 dark:text-slate-400">Pilih tipe cuti untuk melihat saldo</span>');
            return;
        }
        
        balanceDiv.html('<span class="text-slate-600 dark:text-slate-400">Memuat...</span>');
        
        $.ajax({
            url: '/api/v1/leave/balance',
            method: 'GET',
            data: { leave_type_id: leaveTypeId },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success && response.balance) {
                    const balance = response.balance;
                    const percentage = balance.allocated_days > 0 ? (balance.remaining_days / balance.allocated_days) * 100 : 0;
                    const colorClass = percentage > 50 ? 'emerald' : (percentage > 20 ? 'amber' : 'red');
                    
                    balanceDiv.html(`
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-slate-800 dark:text-white">${balance.remaining_days} hari tersisa</span>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium text-white bg-gradient-to-r from-${colorClass}-500 to-${colorClass}-600 shadow-lg">${percentage.toFixed(0)}%</span>
                        </div>
                        <div class="w-full bg-white/20 rounded-full h-2">
                            <div class="bg-gradient-to-r from-${colorClass}-500 to-${colorClass}-600 h-2 rounded-full transition-all" style="width: ${percentage}%"></div>
                        </div>
                        <small class="text-slate-600 dark:text-slate-400">${balance.used_days} terpakai dari ${balance.allocated_days} dialokasikan</small>
                    `);
                } else {
                    balanceDiv.html('<span class="text-red-500">Tidak ada saldo ditemukan untuk tipe cuti ini</span>');
                }
            },
            error: function() {
                balanceDiv.html('<span class="text-red-500">Error memuat saldo</span>');
            }
        });
    });
    
    // Calculate working days when dates change
    function calculateWorkingDays() {
        const startDate = $('#start_date').val();
        const endDate = $('#end_date').val();
        const workingDaysDiv = $('#working-days');
        
        if (!startDate || !endDate) {
            workingDaysDiv.html('<span class="text-slate-600 dark:text-slate-400">Pilih tanggal untuk menghitung</span>');
            return;
        }
        
        if (new Date(endDate) < new Date(startDate)) {
            workingDaysDiv.html('<span class="text-red-500">Tanggal berakhir harus setelah tanggal mulai</span>');
            return;
        }
        
        workingDaysDiv.html('<span class="text-slate-600 dark:text-slate-400">Menghitung...</span>');
        
        $.ajax({
            url: '/api/v1/leave/calculate-days',
            method: 'POST',
            data: {
                start_date: startDate,
                end_date: endDate
            },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                const days = response.working_days;
                const dayText = days === 1 ? 'hari' : 'hari';
                workingDaysDiv.html(`<strong>${days} hari kerja</strong>`);
                
                // Update submit button text
                $('#submit-btn').html(`
                    <div class="flex items-center justify-center space-x-2">
                        <svg class="w-5 h-5 group-hover:rotate-90 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l7-7-7-7"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14"/></svg>
                        <span class="font-medium">Kirim Permintaan Cuti ${days} ${dayText}</span>
                    </div>
                `);
            },
            error: function() {
                workingDaysDiv.html('<span class="text-red-500">Error menghitung hari</span>');
            }
        });
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
@endpush
