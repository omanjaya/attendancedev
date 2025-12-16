@extends('emails.layout')

@section('header')
<div class="email-header" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
    <h1>🔑 Reset Password</h1>
    <p>Permintaan reset password untuk akun Anda</p>
</div>
@endsection

@section('content')
<p class="greeting">Halo! 👋</p>

<p class="content">
    Kami menerima permintaan untuk mereset password akun Anda di <strong>{{ config('app.name') }}</strong>.
    Klik tombol di bawah ini untuk membuat password baru:
</p>

<div style="text-align: center; margin: 32px 0;">
    <a href="{{ $resetUrl }}" class="btn" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); padding: 16px 32px; font-size: 16px;">
        Reset Password Saya
    </a>
</div>

<div class="info-card" style="border-left-color: #10b981;">
    <div class="info-row">
        <span class="info-label">⏰ Link Kadaluarsa</span>
        <span class="info-value">60 menit</span>
    </div>
    <div class="info-row">
        <span class="info-label">📧 Email</span>
        <span class="info-value">{{ $email }}</span>
    </div>
    <div class="info-row">
        <span class="info-label">📅 Waktu Permintaan</span>
        <span class="info-value">{{ now()->setTimezone('Asia/Jakarta')->format('d M Y, H:i') }} WIB</span>
    </div>
</div>

<div style="background-color: #fef3c7; border-radius: 8px; padding: 16px; margin: 24px 0; border: 1px solid #fcd34d;">
    <p style="color: #92400e; font-size: 14px; margin: 0;">
        ⚠️ <strong>Penting:</strong> Jika Anda tidak meminta reset password ini, abaikan email ini.
        Password Anda akan tetap aman dan tidak berubah.
    </p>
</div>

<p class="content" style="font-size: 14px; color: #6b7280; margin-top: 24px;">
    Jika tombol di atas tidak berfungsi, salin dan tempel URL berikut ke browser Anda:
</p>

<div style="background-color: #f3f4f6; padding: 12px; border-radius: 6px; word-break: break-all; font-size: 12px; color: #4b5563; font-family: monospace;">
    {{ $resetUrl }}
</div>

<hr style="border: none; border-top: 1px solid #e5e7eb; margin: 32px 0;">

<p class="content" style="font-size: 13px; color: #9ca3af;">
    Email ini dikirim ke {{ $email }} karena ada permintaan reset password dari alamat IP {{ $ipAddress ?? 'tidak diketahui' }}.
</p>
@endsection
