@extends('layouts.authenticated-unified')

@section('title', 'Tinjau Permintaan Cuti')

@section('page-content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100 dark:from-slate-900 dark:via-blue-900 dark:to-indigo-900">
    <div class="p-6 lg:p-8">
        <x-layouts.base-page
            title="Tinjau Permintaan Cuti #{{ substr($leave->id, 0, 8) }}"
            subtitle="Detail permintaan cuti untuk persetujuan"
            :breadcrumbs="[
                ['label' => 'Dashboard', 'url' => route('dashboard')],
                ['label' => 'Cuti', 'url' => route('leave.index')],
                ['label' => 'Persetujuan', 'url' => route('leave.approvals.index')],
                ['label' => 'Tinjau Permintaan']
            ]">
            <x-slot name="actions">
                @if($leave->isPending())
                    <x-ui.button variant="success" id="approve-request" data-id="{{ $leave->id }}">
                        <svg class="w-5 h-5 mr-2 group-hover:rotate-90 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12l5 5l10 -10"/></svg>
                        Setujui Permintaan
                    </x-ui.button>
                    <x-ui.button variant="destructive" id="reject-request" data-id="{{ $leave->id }}">
                        <svg class="w-5 h-5 mr-2 group-hover:rotate-90 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        Tolak Permintaan
                    </x-ui.button>
                @endif
                <x-ui.button variant="secondary" href="{{ route('leave.approvals.index') }}">
                    <svg class="w-5 h-5 mr-2 group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 6l-6 6l6 6"/></svg>
                    Kembali ke Persetujuan
                </x-ui.button>
            </x-slot>
        </x-layouts.base-page>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2 group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                <h3 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Detail Permintaan Cuti</h3>
                <p class="text-slate-600 dark:text-slate-400 mb-6">Detail lengkap permintaan cuti</p>
                
                <div class="space-y-6">
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div class="space-y-4">
                            <div>
                                <x-ui.label value="Karyawan" class="text-slate-700 dark:text-slate-300 mb-2" />
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white text-sm font-bold">
                                        {{ substr($leave->employee->full_name, 0, 2) }}
                                    </div>
                                    <div>
                                        <div class="font-medium text-slate-800 dark:text-white">{{ $leave->employee->full_name }}</div>
                                        <div class="text-sm text-slate-600 dark:text-slate-400">{{ $leave->employee->employee_id }}</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div>
                                <x-ui.label value="Tipe Cuti" class="text-slate-700 dark:text-slate-300 mb-2" />
                                <div class="flex items-center space-x-2">
                                    <x-ui.badge variant="secondary">{{ $leave->leaveType->name }}</x-ui.badge>
                                    @if($leave->leaveType->is_paid)
                                        <x-ui.badge variant="success" size="sm">Berbayar</x-ui.badge>
                                    @else
                                        <x-ui.badge variant="warning" size="sm">Tidak Berbayar</x-ui.badge>
                                    @endif
                                </div>
                            </div>
                            
                            <div>
                                <x-ui.label value="Durasi" class="text-slate-700 dark:text-slate-300 mb-2" />
                                <div class="flex items-center space-x-2">
                                    <span class="font-medium text-slate-800 dark:text-white">{{ $leave->duration }}</span>
                                    @if($leave->is_emergency)
                                        <x-ui.badge variant="destructive" size="sm">Darurat</x-ui.badge>
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                        <div class="space-y-4">
                            <div>
                                <x-ui.label value="Tanggal Cuti" class="text-slate-700 dark:text-slate-300 mb-2" />
                                <div class="flex items-center space-x-2">
                                    <svg class="w-5 h-5 text-slate-600 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <rect x="4" y="5" width="16" height="16" rx="2"/>
                                        <line x1="16" y1="3" x2="16" y2="7"/>
                                        <line x1="8" y1="3" x2="8" y2="7"/>
                                        <line x1="4" y1="11" x2="20" y2="11"/>
                                    </svg>
                                    <span class="text-slate-800 dark:text-white font-medium">{{ $leave->date_range }}</span>
                                </div>
                                @if($leave->isActive())
                                    <div class="text-sm text-emerald-600 mt-1">Sedang cuti</div>
                                @elseif($leave->start_date > now())
                                    <div class="text-sm text-blue-600 mt-1">Cuti mendatang</div>
                                @endif
                            </div>
                            
                            <div>
                                <x-ui.label value="Permintaan Dikirim" class="text-slate-700 dark:text-slate-300 mb-2" />
                                <div class="text-slate-800 dark:text-white font-medium">{{ $leave->created_at->format('M j, Y g:i A') }}</div>
                                <div class="text-sm text-slate-600 dark:text-slate-400">{{ $leave->created_at->diffForHumans() }}</div>
                            </div>
                            
                            @if($leave->approved_by)
                            <div>
                                <x-ui.label value="@if($leave->isApproved()) Disetujui Oleh @elseif($leave->isRejected()) Ditolak Oleh @endif" class="text-slate-700 dark:text-slate-300 mb-2" />
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white text-sm font-bold">
                                        {{ substr($leave->approver->full_name, 0, 2) }}
                                    </div>
                                    <div>
                                        <div class="font-medium text-slate-800 dark:text-white">{{ $leave->approver->full_name }}</div>
                                        @if($leave->approved_at)
                                            <div class="text-sm text-slate-600 dark:text-slate-400">{{ $leave->approved_at->format('M j, Y g:i A') }}</div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                    
                    @if($leave->reason)
                    <div>
                        <x-ui.label value="Alasan Cuti" class="text-slate-700 dark:text-slate-300 mb-2" />
                        <div class="p-4 bg-white/10 rounded-lg border border-white/20">
                            <p class="text-slate-800 dark:text-white">{{ $leave->reason }}</p>
                        </div>
                    </div>
                    @endif
                    
                    @if($leave->approval_notes && $leave->isApproved())
                    <div>
                        <x-ui.label value="Catatan Persetujuan" class="text-slate-700 dark:text-slate-300 mb-2" />
                        <div class="bg-green-500/20 border border-green-500/30 rounded-lg p-4 shadow-lg">
                            <p class="text-green-800 dark:text-green-200">{{ $leave->approval_notes }}</p>
                        </div>
                    </div>
                    @endif
                    
                    @if($leave->rejection_reason && $leave->isRejected())
                    <div>
                        <x-ui.label value="Alasan Penolakan" class="text-slate-700 dark:text-slate-300 mb-2" />
                        <div class="bg-red-500/20 border border-red-500/30 rounded-lg p-4 shadow-lg">
                            <p class="text-red-800 dark:text-red-200">{{ $leave->rejection_reason }}</p>
                        </div>
                    </div>
                    @endif
                </div>
            
                <div class="lg:col-span-1 space-y-6">
                    <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                        <h3 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Linimasa Permintaan</h3>
                        <p class="text-slate-600 dark:text-slate-400 mb-6">Lacak kemajuan permintaan</p>
                        
                        <div class="space-y-6">
                            <div class="relative pl-6">
                                <div class="absolute left-0 top-0 w-3 h-3 bg-green-500 rounded-full"></div>
                                <div class="text-sm text-slate-600 dark:text-slate-400">{{ $leave->created_at->format('M j, g:i A') }}</div>
                                <div class="mt-1">
                                    <div class="font-medium text-slate-800 dark:text-white">Permintaan Dikirim</div>
                                    <div class="text-sm text-slate-600 dark:text-slate-400">Permintaan cuti dibuat dan diajukan untuk persetujuan</div>
                                </div>
                            </div>
                            
                            @if($leave->approved_at)
                            <div class="relative pl-6">
                                <div class="absolute left-0 top-0 w-3 h-3 {{ $leave->isApproved() ? 'bg-green-500' : 'bg-red-500' }} rounded-full"></div>
                                <div class="text-sm text-slate-600 dark:text-slate-400">{{ $leave->approved_at->format('M j, g:i A') }}</div>
                                <div class="mt-1">
                                    <div class="font-medium text-slate-800 dark:text-white">
                                        @if($leave->isApproved())
                                            Permintaan Disetujui
                                        @elseif($leave->isRejected())
                                            Permintaan Ditolak
                                        @endif
                                    </div>
                                    <div class="text-sm text-slate-600 dark:text-slate-400">
                                        @if($leave->approver)
                                            oleh {{ $leave->approver->full_name }}
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @elseif($leave->isPending())
                            <div class="relative pl-6">
                                <div class="absolute left-0 top-0 w-3 h-3 bg-amber-500 rounded-full"></div>
                                <div class="text-sm text-slate-600 dark:text-slate-400">Tertunda</div>
                                <div class="mt-1">
                                    <div class="font-medium text-slate-800 dark:text-white">Menunggu Persetujuan</div>
                                    <div class="text-sm text-slate-600 dark:text-slate-400">Permintaan sedang ditinjau</div>
                                </div>
                            </div>
                            @endif
                            
                            @if($leave->isCancelled())
                            <div class="relative pl-6">
                                <div class="absolute left-0 top-0 w-3 h-3 bg-slate-500 rounded-full"></div>
                                <div class="text-sm text-slate-600 dark:text-slate-400">{{ $leave->updated_at->format('M j, g:i A') }}</div>
                                <div class="mt-1">
                                    <div class="font-medium text-slate-800 dark:text-white">Permintaan Dibatalkan</div>
                                    <div class="text-sm text-slate-600 dark:text-slate-400">Permintaan cuti dibatalkan</div>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                    
                    <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                        <h3 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Dampak Saldo</h3>
                        <p class="text-slate-600 dark:text-slate-400 mb-6">Efek pada saldo cuti</p>
                        
                        <div class="space-y-3 text-slate-600 dark:text-slate-400">
                            <div class="flex justify-between items-center">
                                <span class="font-medium">Tipe Cuti:</span>
                                <span class="font-medium text-slate-800 dark:text-white">{{ $leave->leaveType->name }}</span>
                            </div>
                            
                            <div class="flex justify-between items-center">
                                <span class="font-medium">Hari Diminta:</span>
                                <span class="font-medium text-slate-800 dark:text-white">{{ $leave->days_requested }}</span>
                            </div>
                            
                            @php
                                $balance = $leave->employee->getLeaveBalance($leave->leaveType->id);
                            @endphp
                            
                            @if($balance)
                            <div class="flex justify-between items-center">
                                <span class="font-medium">Saldo Saat Ini:</span>
                                <span class="font-medium text-slate-800 dark:text-white">{{ $balance->remaining_days }} hari</span>
                            </div>
                            
                            @if($leave->isPending() || $leave->isApproved())
                            <div class="flex justify-between items-center border-t border-white/20 pt-3">
                                <span class="font-medium">Setelah Permintaan Ini:</span>
                                <span class="font-semibold {{ $balance->remaining_days >= 0 ? 'text-green-500' : 'text-red-500' }}">
                                    {{ $balance->remaining_days }} hari
                                </span>
                            </div>
                            @endif
                            @else
                            <div class="text-center py-6 text-slate-600 dark:text-slate-400">
                                <p class="text-sm">Tidak ada informasi saldo tersedia</p>
                            </div>
                            @endif
                        </div>
                    </div>
                    
                    @if($leave->employee_id === auth()->user()->employee?->id)
                    <div class="group relative bg-white/20 backdrop-blur-sm border border-white/30 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 ease-out">
                        <h3 class="text-xl font-semibold text-slate-800 dark:text-white mb-4">Aksi Cepat</h3>
                        <p class="text-slate-600 dark:text-slate-400 mb-6">Tindakan yang tersedia</p>
                        
                        <div class="space-y-3">
                            @if($leave->canBeCancelled())
                                <x-ui.button variant="destructive" class="w-full" id="cancel-leave-btn" data-id="{{ $leave->id }}">
                                    <svg class="w-5 h-5 mr-2 group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    Batalkan Permintaan
                                </x-ui.button>
                            @endif
                            
                            <x-ui.button variant="secondary" class="w-full" href="{{ route('leave.create') }}">
                                <svg class="w-5 h-5 mr-2 group-hover:rotate-90 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                Permintaan Baru
                            </x-ui.button>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Cancel leave request
    $('#cancel-leave, #cancel-leave-btn').on('click', function() {
        const leaveId = $(this).data('id');
        
        if (confirm('Apakah Anda yakin ingin membatalkan permintaan cuti ini? Tindakan ini tidak dapat dibatalkan.')) {
            $.ajax({
                url: `/leave/${leaveId}/cancel`,
                method: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        // Reload page to show updated status
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function(xhr) {
                    toastr.error(xhr.responseJSON?.message || 'Terjadi kesalahan');
                }
            });
        }
    });
});
</script>
@endpush