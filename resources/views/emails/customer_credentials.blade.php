<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Your account credentials</title>
    <style>
        body { font-family: Arial, sans-serif; color: #111827; }
        .container { max-width: 640px; margin: 0 auto; padding: 16px; }
        .card { border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; }
        .muted { color: #6b7280; font-size: 12px; }
        .btn { display: inline-block; padding: 10px 16px; background: #111827; color: #ffffff; text-decoration: none; border-radius: 6px; }
        .mb-2 { margin-bottom: 8px; }
        .mb-4 { margin-bottom: 16px; }
        .bold { font-weight: 600; }
        .mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; }
    </style>
    </head>
<body>
    <div class="container">
        <h2 class="mb-2">Welcome, {{ $customerName }}!</h2>
        <div class="card mb-4">
            <p class="mb-2">Your account has been created. You can sign in using these credentials:</p>
            <p class="mb-2"><span class="bold">Email:</span> <span class="mono">{{ $email }}</span></p>
            <p class="mb-4"><span class="bold">Password:</span> <span class="mono">{{ $plainPassword }}</span></p>
            <a href="{{ url('/login') }}" class="btn">Sign in</a>
        </div>
        <p class="muted">For security, please sign in and change your password as soon as possible.</p>
    </div>
</body>
</html>


