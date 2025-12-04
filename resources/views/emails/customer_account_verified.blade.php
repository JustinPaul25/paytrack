<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Your account has been verified</title>
    <style>
        body { font-family: Arial, sans-serif; color: #111827; }
        .container { max-width: 640px; margin: 0 auto; padding: 16px; }
        .card { border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; }
        .muted { color: #6b7280; font-size: 12px; }
        .btn { display: inline-block; padding: 10px 16px; background: #111827; color: #ffffff; text-decoration: none; border-radius: 6px; }
        .mb-2 { margin-bottom: 8px; }
        .mb-4 { margin-bottom: 16px; }
        .bold { font-weight: 600; }
    </style>
    </head>
<body>
    <div class="container">
        <h2 class="mb-2">Hello, {{ $customerName }}!</h2>
        <div class="card mb-4">
            <p class="mb-2">Your account has been verified.</p>
            <p class="mb-4">You can now access your PayTrack customer dashboard. Click the link below to log in:</p>
            <a href="{{ $loginUrl }}" class="btn">Log in to PayTrack</a>
        </div>
        <p class="muted">If you have any questions, please contact our support team.</p>
    </div>
</body>
</html>


