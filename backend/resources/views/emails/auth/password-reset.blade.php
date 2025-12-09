@extends('emails.layout')

@section('header')
<div class="email-header" style="background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);">
    <h1>🔐 Password Reset</h1>
    <p>Kredensial login Anda telah diperbarui</p>
</div>
@endsection

@section('content')
<p class="greeting">Halo, <strong>{{ $userName }}</strong>! 👋</p>

<p class="content">
    Password akun Anda telah direset oleh <strong>{{ $resetBy }}</strong>. 
    Berikut adalah kredensial login sementara Anda:
</p>

<div class="info-card" style="border-left-color: #6366f1;">
    <div class="info-row">
        <span class="info-label">Email</span>
        <span class="info-value">{{ $userEmail }}</span>
    </div>
    <div class="info-row" style="background-color: #f0fdf4; margin: 8px -20px; padding: 12px 20px;">
        <span class="info-label">Password Sementara</span>
        <span class="info-value" style="font-family: monospace; font-size: 16px; letter-spacing: 1px; color: #059669;">
            {{ $temporaryPassword }}
        </span>
    </div>
</div>

<div style="background-color: #fef3c7; border-radius: 8px; padding: 16px; margin: 24px 0; border: 1px solid #fcd34d;">
    <p style="color: #92400e; font-size: 14px; margin: 0;">
        ⚠️ <strong>Penting:</strong> Anda akan diminta untuk mengubah password ini saat login berikutnya. 
        Jangan bagikan password ini dengan siapapun.
    </p>
</div>

<div style="text-align: center; margin-top: 32px;">
    <a href="{{ $loginUrl }}" class="btn">
        Login Sekarang
    </a>
</div>

<p class="content" style="margin-top: 32px; font-size: 14px; color: #6b7280;">
    Jika Anda tidak meminta reset password ini, segera hubungi administrator sistem.
</p>
@endsection
