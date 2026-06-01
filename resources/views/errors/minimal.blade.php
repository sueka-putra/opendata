@php
    $statusCode = $code ?? ($statusCode ?? 500);
    $errorMessage = $message ?? 'Server Error';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Something Went Wrong</title>
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --primary-soft: #dbeafe;
            --text-dark: #1f2937;
            --text-muted: #6b7280;
            --bg: #eef5ff;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Inter", "Segoe UI", Arial, sans-serif;
            background:
                radial-gradient(circle at top left, rgba(37, 99, 235, 0.25), transparent 35%),
                radial-gradient(circle at bottom right, rgba(59, 130, 246, 0.25), transparent 35%),
                linear-gradient(135deg, #f8fbff 0%, var(--bg) 100%);
            color: var(--text-dark);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            overflow: hidden;
        }
        .error-wrapper { width: 100%; max-width: 760px; position: relative; z-index: 2; }
        .error-card {
            background: rgba(255, 255, 255, 0.92);
            border: 1px solid rgba(37, 99, 235, 0.15);
            border-radius: 28px;
            box-shadow: 0 24px 80px rgba(37, 99, 235, 0.18), 0 8px 24px rgba(15, 23, 42, 0.08);
            padding: 48px;
            text-align: center;
            backdrop-filter: blur(14px);
            animation: cardIn 0.65s ease-out;
        }
        .icon-circle {
            width: 96px; height: 96px; margin: 0 auto 24px; border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), #60a5fa);
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 16px 36px rgba(37, 99, 235, 0.35);
            animation: floatIcon 2.8s ease-in-out infinite;
        }
        .icon-circle svg { width: 46px; height: 46px; color: white; }
        .error-code {
            display: inline-flex; align-items: center; gap: 8px;
            background: var(--primary-soft); color: var(--primary-dark);
            font-weight: 700; font-size: 14px; letter-spacing: 0.08em; text-transform: uppercase;
            border-radius: 999px; padding: 8px 16px; margin-bottom: 18px;
        }
        h1 { margin: 0 0 14px; font-size: clamp(32px, 5vw, 48px); line-height: 1.12; font-weight: 800; color: #111827; }
        .message { margin: 0 auto 30px; max-width: 560px; color: var(--text-muted); font-size: 18px; line-height: 1.65; }
        .actions { display: flex; flex-wrap: wrap; justify-content: center; gap: 14px; margin-top: 28px; }
        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            min-height: 48px; padding: 12px 22px; border-radius: 14px; border: 1px solid transparent;
            font-size: 15px; font-weight: 700; text-decoration: none; transition: all 0.2s ease;
        }
        .btn-primary { background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: white; box-shadow: 0 10px 24px rgba(37, 99, 235, 0.28); }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 14px 30px rgba(37, 99, 235, 0.34); }
        .btn-light { background: #ffffff; color: var(--primary-dark); border-color: rgba(37, 99, 235, 0.22); }
        .btn-light:hover { background: #eff6ff; transform: translateY(-2px); }
        .support-box {
            margin-top: 34px; padding: 18px 20px; border-radius: 18px;
            background: #eff6ff; border: 1px solid #bfdbfe; color: #1e3a8a;
            font-size: 15px; line-height: 1.55;
        }
        .support-box a { color: var(--primary-dark); font-weight: 700; text-decoration: none; }
        .support-box a:hover { text-decoration: underline; }
        .bubble { position: fixed; border-radius: 50%; background: rgba(37, 99, 235, 0.12); animation: drift 8s ease-in-out infinite; z-index: 1; }
        .bubble.one { width: 160px; height: 160px; top: 10%; left: 8%; }
        .bubble.two { width: 110px; height: 110px; right: 10%; bottom: 14%; animation-delay: 1.2s; }
        .bubble.three { width: 70px; height: 70px; right: 22%; top: 18%; animation-delay: 0.6s; }
        @keyframes cardIn { from { opacity: 0; transform: translateY(22px) scale(0.98); } to { opacity: 1; transform: translateY(0) scale(1); } }
        @keyframes floatIcon { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-8px); } }
        @keyframes drift { 0%, 100% { transform: translate(0, 0); } 50% { transform: translate(18px, -22px); } }
        @media (max-width: 576px) {
            .error-card { padding: 34px 24px; border-radius: 22px; }
            .message { font-size: 16px; }
            .actions { flex-direction: column; }
            .btn { width: 100%; }
        }
    </style>
</head>
<body>
<div class="bubble one"></div>
<div class="bubble two"></div>
<div class="bubble three"></div>
<main class="error-wrapper">
    <section class="error-card">
        <div class="icon-circle" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none">
                <path d="M12 9v4" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/>
                <path d="M12 17h.01" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
            </svg>
        </div>
        <div class="error-code">Error {{ $statusCode }}</div>
        <h1>Something went wrong</h1>
        <p class="message">
            We are sorry, but the system could not process your request at the moment.
            Please try again or return to the dashboard.
        </p>
        <div class="actions">
            <a href="{{ url()->previous() }}" class="btn btn-light">&larr; Go Back</a>
            <a href="{{ route('dashboard') }}" class="btn btn-primary">Go to Dashboard</a>
        </div>
        <div class="support-box">
            If the problem continues, please contact
            <a href="mailto:stats@asean.org">stats@asean.org</a>.
        </div>
    </section>
</main>
</body>
</html>
