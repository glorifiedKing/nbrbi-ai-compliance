<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>External Embed Test — {{ $website->name }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            margin: 0;
            padding: 40px 24px;
            background: #f8fafc;
            color: #0f172a;
        }
        .container {
            max-width: 760px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            padding: 32px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
        }
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
        }
        .badge-active {
            background: #dcfce7;
            color: #046938;
        }
        .badge-disabled {
            background: #fef3c7;
            color: #92400e;
        }
        code {
            background: #0f172a;
            color: #6ee7b7;
            padding: 12px 16px;
            border-radius: 10px;
            display: block;
            overflow-x: auto;
            font-size: 13px;
            margin-top: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>External Website Simulation: {{ $website->name }}</h1>
        <p>
            Status:
            @if($website->is_active)
                <span class="badge badge-active">ENABLED — Floating Copilot Should Appear Bottom-Right</span>
            @else
                <span class="badge badge-disabled">DISABLED — Floating Copilot Will NOT Load</span>
            @endif
        </p>
        <p>This standalone page has no Tailwind CSS or admin scripts. It only includes the external embed script below:</p>
        <code>&lt;script src="{{ route('copilot.embed.script', ['site' => $website->slug]) }}" defer&gt;&lt;/script&gt;</code>
    </div>

    <!-- Embedded NBRB Statutory AI Copilot Script -->
    <script src="{{ route('copilot.embed.script', ['site' => $website->slug]) }}" defer></script>
</body>
</html>

