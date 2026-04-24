@php
    $support = config('cierra-auth-package.support_email', 'support@cierra.ai');
    $host = config('cierra-auth-package.host');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Subscription required</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f9fafb; color: #111827; margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .card { background: #fff; border-radius: 12px; padding: 3rem; max-width: 480px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); text-align: center; }
        h1 { margin: 0 0 1rem 0; font-size: 1.5rem; }
        p { color: #4b5563; line-height: 1.6; }
        .btn { display: inline-block; margin-top: 1.5rem; background: #6366f1; color: white; text-decoration: none; padding: 0.75rem 1.5rem; border-radius: 8px; font-weight: 500; }
        .btn:hover { background: #4f46e5; }
    </style>
</head>
<body>
    <div class="card">
        <h1>🔒 Subscription required</h1>
        <p>Access to this application requires an active subscription or seat.
           Please contact your team administrator or reach out to
           <a href="mailto:{{ $support }}">{{ $support }}</a> to get set up.</p>
        @if ($host)
            <a class="btn" href="{{ $host }}">Manage subscription</a>
        @endif
    </div>
</body>
</html>
