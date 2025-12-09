@extends('emails.layout')

@section('header')
<div class="email-header" style="background: linear-gradient(135deg, {{ $isOvertime ? '#8b5cf6' : '#6366f1' }} 0%, {{ $isOvertime ? '#7c3aed' : '#4f46e5' }} 100%);">
    <h1>{{ $isOvertime ? '💪 Check-Out Lembur' : '🏁 Check-Out Berhasil' }}</h1>
    <p>{{ $date }}</p>
</div>
@endsection

@section('content')
<p class="greeting">Halo, <strong>{{ $employeeName }}</strong>! 👋</p>

<p class="content">
    Check-out Anda berhasil dicatat.
    @if($isOvertime)
        Terima kasih atas kerja keras Anda hari ini dengan lembur! 🙏
    @else
        Terima kasih atas kerja keras Anda hari ini! 🎉
    @endif
</p>

<div class="info-card">
    <div class="info-row">
        <span class="info-label">Nama</span>
        <span class="info-value">{{ $employeeName }}</span>
    </div>
    <div class="info-row">
        <span class="info-label">ID Karyawan</span>
        <span class="info-value">{{ $employeeId }}</span>
    </div>
    <div class="info-row">
        <span class="info-label">Tanggal</span>
        <span class="info-value">{{ $date }}</span>
    </div>
    <div class="info-row">
        <span class="info-label">Waktu Masuk</span>
        <span class="info-value">{{ $checkInTime }}</span>
    </div>
    <div class="info-row">
        <span class="info-label">Waktu Keluar</span>
        <span class="info-value">{{ $checkOutTime }}</span>
    </div>
    <div class="info-row" style="background-color: {{ $isOvertime ? '#f3e8ff' : '#ecfdf5' }}; margin: 8px -20px; padding: 12px 20px;">
        <span class="info-label" style="font-weight: 600;">Total Jam Kerja</span>
        <span class="info-value" style="font-size: 18px; color: {{ $isOvertime ? '#7c3aed' : '#059669' }};">
            {{ $totalHours }} jam
        </span>
    </div>
</div>

@if($isOvertime)
<div style="background-color: #faf5ff; border-radius: 8px; padding: 16px; margin-top: 16px; border: 1px solid #e9d5ff;">
    <p style="color: #7c3aed; font-size: 14px; margin: 0;">
        💡 <strong>Info Lembur:</strong> Jam kerja melebihi 8 jam. Pastikan lembur Anda sudah disetujui oleh atasan.
    </p>
</div>
@endif

<p class="content" style="margin-top: 24px; font-size: 14px; color: #6b7280;">
    Istirahat yang cukup dan sampai jumpa besok! 🌙
</p>
@endsection
