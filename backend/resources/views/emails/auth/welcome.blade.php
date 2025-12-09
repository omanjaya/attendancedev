@extends('emails.layout')

@section('header')
<div class="email-header" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
    <h1>🎉 Selamat Datang!</h1>
    <p>Akun Anda di {{ $appName }} telah dibuat</p>
</div>
@endsection

@section('content')
<p class="greeting">Halo, <strong>{{ $employeeName }}</strong>! 👋</p>

<p class="content">
    Selamat datang di <strong>{{ $appName }}</strong>! 
    Akun Anda telah berhasil dibuat. Berikut adalah detail login Anda:
</p>

<div class="info-card" style="border-left-color: #10b981;">
    <div class="info-row">
        <span class="info-label">ID Karyawan</span>
        <span class="info-value">{{ $employeeId }}</span>
    </div>
    <div class="info-row">
        <span class="info-label">Email</span>
        <span class="info-value">{{ $userEmail }}</span>
    </div>
    <div class="info-row" style="background-color: #f0fdf4; margin: 8px -20px; padding: 12px 20px;">
        <span class="info-label">Password Awal</span>
        <span class="info-value" style="font-family: monospace; font-size: 16px; letter-spacing: 1px; color: #059669;">
            {{ $temporaryPassword }}
        </span>
    </div>
</div>

<div style="background-color: #dbeafe; border-radius: 8px; padding: 16px; margin: 24px 0; border: 1px solid #93c5fd;">
    <p style="color: #1e40af; font-size: 14px; margin: 0;">
        💡 <strong>Tips:</strong> Saat pertama kali login, Anda akan diminta untuk mengubah password. 
        Gunakan password yang kuat dengan kombinasi huruf, angka, dan simbol.
    </p>
</div>

<div style="text-align: center; margin-top: 32px;">
    <a href="{{ $loginUrl }}" class="btn" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
        Login Sekarang
    </a>
</div>

<div style="margin-top: 32px; padding: 20px; background-color: #f8fafc; border-radius: 8px;">
    <h3 style="font-size: 16px; color: #1a1a2e; margin-bottom: 12px;">📋 Langkah Selanjutnya:</h3>
    <ol style="color: #6b7280; font-size: 14px; padding-left: 20px; margin: 0;">
        <li style="margin-bottom: 8px;">Login menggunakan email dan password di atas</li>
        <li style="margin-bottom: 8px;">Ubah password Anda (wajib saat login pertama)</li>
        <li style="margin-bottom: 8px;">Lengkapi profil Anda</li>
        <li style="margin-bottom: 8px;">Daftarkan wajah untuk verifikasi absensi</li>
    </ol>
</div>

<p class="content" style="margin-top: 24px; font-size: 14px; color: #6b7280;">
    Jika ada pertanyaan, silakan hubungi HRD atau administrator sistem.
</p>
@endsection
