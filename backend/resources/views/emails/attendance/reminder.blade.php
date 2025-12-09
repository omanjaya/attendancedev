@extends('emails.layout')

@php
$colors = [
    'check_in' => ['from' => '#f59e0b', 'to' => '#d97706'],
    'check_out' => ['from' => '#3b82f6', 'to' => '#2563eb'],
    'absent' => ['from' => '#ef4444', 'to' => '#dc2626'],
];
$icons = [
    'check_in' => '⏰',
    'check_out' => '🔔',
    'absent' => '⚠️',
];
$titles = [
    'check_in' => 'Reminder Check-In',
    'check_out' => 'Reminder Check-Out',
    'absent' => 'Notifikasi Ketidakhadiran',
];
$color = $colors[$reminderType] ?? $colors['check_in'];
$icon = $icons[$reminderType] ?? '🔔';
$title = $titles[$reminderType] ?? 'Reminder';
@endphp

@section('header')
<div class="email-header" style="background: linear-gradient(135deg, {{ $color['from'] }} 0%, {{ $color['to'] }} 100%);">
    <h1>{{ $icon }} {{ $title }}</h1>
    <p>{{ $date }} - {{ $time }}</p>
</div>
@endsection

@section('content')
<p class="greeting">Halo, <strong>{{ $employeeName }}</strong>! 👋</p>

<div class="info-card" style="border-left-color: {{ $color['from'] }};">
    <p style="font-size: 15px; color: #374151; margin: 0;">
        {{ $message }}
    </p>
</div>

<div style="text-align: center; margin-top: 32px;">
    <a href="{{ config('app.frontend_url', config('app.url')) }}" class="btn">
        Buka Aplikasi Absensi
    </a>
</div>

@if($reminderType === 'check_in')
<p class="content" style="margin-top: 32px; font-size: 14px; color: #6b7280; text-align: center;">
    Waktu check-in standar adalah <strong>08:00</strong>. Mohon segera melakukan absensi.
</p>
@elseif($reminderType === 'check_out')
<p class="content" style="margin-top: 32px; font-size: 14px; color: #6b7280; text-align: center;">
    Jangan lupa check-out sebelum pulang untuk mencatat jam kerja Anda.
</p>
@elseif($reminderType === 'absent')
<p class="content" style="margin-top: 32px; font-size: 14px; color: #6b7280; text-align: center;">
    Jika Anda tidak hadir karena sakit atau izin, mohon ajukan permohonan cuti/izin melalui aplikasi.
</p>
@endif
@endsection
