<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Notification</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            line-height: 1.6;
            color: #1a1a2e;
            background-color: #f8fafc;
        }
        .email-wrapper {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
        }
        .email-header {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            padding: 32px 24px;
            text-align: center;
        }
        .email-header h1 {
            color: #ffffff;
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .email-header p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 14px;
        }
        .email-body {
            padding: 32px 24px;
        }
        .greeting {
            font-size: 18px;
            color: #1a1a2e;
            margin-bottom: 16px;
        }
        .content {
            color: #4b5563;
            margin-bottom: 24px;
        }
        .info-card {
            background-color: #f8fafc;
            border-radius: 12px;
            padding: 20px;
            margin: 24px 0;
            border-left: 4px solid #6366f1;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            color: #6b7280;
            font-size: 14px;
        }
        .info-value {
            color: #1a1a2e;
            font-weight: 500;
            font-size: 14px;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 500;
        }
        .status-success {
            background-color: #dcfce7;
            color: #166534;
        }
        .status-warning {
            background-color: #fef3c7;
            color: #92400e;
        }
        .status-error {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .status-info {
            background-color: #dbeafe;
            color: #1e40af;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: #ffffff;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            margin-top: 16px;
        }
        .btn:hover {
            opacity: 0.9;
        }
        .email-footer {
            background-color: #f8fafc;
            padding: 24px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }
        .email-footer p {
            color: #6b7280;
            font-size: 12px;
            margin-bottom: 8px;
        }
        .email-footer a {
            color: #6366f1;
            text-decoration: none;
        }
        .verification-icons {
            display: flex;
            gap: 16px;
            margin-top: 16px;
        }
        .verification-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #6b7280;
        }
        .verification-item.verified {
            color: #16a34a;
        }
        .icon {
            width: 20px;
            height: 20px;
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        @yield('header')
        
        <div class="email-body">
            @yield('content')
        </div>
        
        <div class="email-footer">
            <p>Email ini dikirim secara otomatis dari {{ config('app.name') }}.</p>
            <p>Jangan balas email ini. Untuk bantuan, hubungi <a href="mailto:support@example.com">support@example.com</a></p>
            <p style="margin-top: 16px; color: #9ca3af; font-size: 11px;">
                © {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>
