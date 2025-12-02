<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Email Address</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: white;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #1f2937;
        }
        .header {
            background-color: #8f5be8;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            background-color: #1f2937;
            padding: 30px;
            border: 1px solid #374151;
            border-top: none;
            color: white;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #8f5be8;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
            font-weight: 600;
        }
        .button:hover {
            background-color: #7c3aed;
        }
        .footer {
            text-align: center;
            color: #9ca3af;
            font-size: 12px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #374151;
        }
        .muted {
            color: #d1d5db;
            font-size: 14px;
        }
        .verification-info {
            background-color: #374151;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #8f5be8;
            color: white;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Verify Your Email Address</h1>
    </div>
    
    <div class="content">
        <p>Hello {{ $user->name }},</p>
        
        <p>Thank you for registering with {{ config('app.name') }}! Please verify your email address by clicking the button below.</p>
        
        <div style="text-align: center;">
            <a href="{{ $verificationUrl }}" class="button" style="color: #fff">Verify Email Address</a>
        </div>
        
        <div class="verification-info">
            <p style="margin: 0;"><strong>What happens next?</strong></p>
            <p style="margin: 10px 0 0 0;">Once you verify your email address, you'll have full access to your account and all features of {{ config('app.name') }}.</p>
        </div>
        
        <p class="muted" style="margin-top: 20px;">
            If the button above doesn't work, you can copy and paste the following link into your browser:
        </p>
        <p class="muted" style="word-break: break-all; background-color: #374151; padding: 10px; border-radius: 4px; font-size: 12px; color: #d1d5db;">
            {{ $verificationUrl }}
        </p>
        
        <p class="muted" style="margin-top: 20px;">
            This verification link will expire in 60 minutes. If you didn't create an account, please ignore this email.
        </p>
        
        <p style="margin-top: 30px;">
            Best regards,<br>
            <strong>{{ config('app.name') }} Team</strong>
        </p>
    </div>
    
    <div class="footer">
        <p>This is an automated email. Please do not reply to this email.</p>
        <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
    </div>
</body>
</html>

