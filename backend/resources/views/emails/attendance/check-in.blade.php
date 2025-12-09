@extends('emails.layout')

@section('header')
<div class="email-header" style="background: linear-gradient(135deg, {{ $isLate ? '#f59e0b' : '#10b981' }} 0%, {{ $isLate ? '#d97706' : '#059669' }} 100%);">
    <h1>{{ $isLate ? '⏰ Keterlambatan Check-In' : '✅ Check-In Berhasil' }}</h1>
    <p>{{ $date }}</p>
</div>
@endsection

@section('content')
<p class="greeting">Halo, <strong>{{ $employeeName }}</strong>! 👋</p>

<p class="content">
    @if($isLate)
        Absensi masuk Anda hari ini tercatat <strong>terlambat</strong>. 
        Mohon untuk datang tepat waktu pada hari kerja berikutnya.
    @else
        Check-in Anda berhasil dicatat. Semoga hari Anda produktif!
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
        <span class="info-label">Waktu Check-In</span>
        <span class="info-value">{{ $checkInTime }}</span>
    </div>
    <div class="info-row">
        <span class="info-label">Tanggal</span>
        <span class="info-value">{{ $date }}</span>
    </div>
    <div class="info-row">
        <span class="info-label">Status</span>
        <span class="info-value">
            <span class="status-badge {{ $isLate ? 'status-warning' : 'status-success' }}">
                {{ $isLate ? 'Terlambat' : 'Tepat Waktu' }}
            </span>
        </span>
    </div>
</div>

<div class="verification-icons">
    <div class="verification-item {{ $locationVerified ? 'verified' : '' }}">
        @if($locationVerified)
            ✅ Lokasi Terverifikasi
        @else
            ⚠️ Lokasi Tidak Terverifikasi
        @endif
    </div>
    <div class="verification-item {{ $faceVerified ? 'verified' : '' }}">
        @if($faceVerified)
            ✅ Wajah Terverifikasi
        @else
            ⚠️ Wajah Tidak Terverifikasi
        @endif
    </div>
</div>

<p class="content" style="margin-top: 24px; font-size: 14px; color: #6b7280;">
    Jangan lupa untuk melakukan check-out sebelum pulang. Selamat bekerja! 💪
</p>
@endsection
