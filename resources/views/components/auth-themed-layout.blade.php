<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle ?? 'Authentication' }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=poppins:400,500,600,700&display=swap" rel="stylesheet" />
    <link rel="icon" type="image/x-icon" href="/img/opendata.png">
    <style>
        :root {
            --bg-start: #0f4fa8;
            --bg-end: #2f80e7;
            --panel-right: #f2f2f2;
            --text-dark: #1f2430;
            --text-soft: #7c8290;
            --border: #d6d8de;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Poppins", sans-serif;
            min-height: 100vh;
            background: linear-gradient(140deg, var(--bg-start), var(--bg-end));
            padding: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .auth-shell {
            width: min(1100px, 100%);
            min-height: min(660px, calc(100vh - 36px));
            display: grid;
            grid-template-columns: 1fr 1fr;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 26px 45px rgba(31, 36, 48, 0.28);
        }
        .brand-panel {
            background: linear-gradient(180deg, #1f6ed6, #0f4ca9);
            color: #fff;
            padding: 56px 52px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            position: relative;
        }
        .brand-panel::before {
            content: "";
            position: absolute;
            left: 12px;
            top: 18px;
            bottom: 18px;
            width: 4px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.75);
        }
        .brand-logo {
            width: 88px;
            margin-bottom: 22px;
        }
        .brand-title {
            margin: 0 0 14px;
            font-size: 46px;
            font-weight: 700;
            line-height: 1.1;
        }
        .brand-subtitle {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
            line-height: 1.2;
        }
        .brand-copy {
            margin: 24px 0 36px;
            max-width: 460px;
            color: rgba(255, 255, 255, 0.88);
            font-size: 20px;
            line-height: 1.55;
        }
        .benefits {
            margin: 0;
            padding: 0;
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 14px;
            text-align: left;
        }
        .benefits li {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 22px;
            font-weight: 500;
        }
        .check {
            width: 28px;
            height: 28px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.95);
            color: #1f64cf;
            font-size: 15px;
            font-weight: 700;
            flex: 0 0 28px;
        }
        .content-panel {
            background: var(--panel-right);
            padding: 64px 74px;
            display: flex;
            align-items: center;
        }
        .content-wrap {
            width: 100%;
            max-width: 470px;
            margin: 0 auto;
        }
        .content-wrap h1 {
            margin: 0;
            color: var(--text-dark);
            font-size: 42px;
            line-height: 1.15;
        }
        .lead {
            margin: 12px 0 28px;
            color: var(--text-soft);
            font-size: 25px;
            line-height: 1.3;
        }
        .status {
            margin-bottom: 18px;
            color: #127549;
            font-weight: 500;
        }
        .field {
            margin-bottom: 18px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-dark);
            font-size: 20px;
            font-weight: 600;
        }
        input[type="email"],
        input[type="password"] {
            width: 100%;
            border: 1px solid var(--border);
            background: #fff;
            border-radius: 8px;
            padding: 15px 16px;
            font-size: 20px;
            color: #20273a;
            outline: none;
        }
        input:focus {
            border-color: #4a8ee8;
            box-shadow: 0 0 0 3px rgba(74, 142, 232, 0.2);
        }
        .error {
            margin-top: 8px;
            color: #b4233d;
            font-size: 14px;
        }
        .row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin: 10px 0 24px;
        }
        .remember {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--text-dark);
            font-size: 20px;
            margin: 0;
        }
        .remember input {
            width: 16px;
            height: 16px;
            accent-color: #2f7be0;
        }
        .link {
            color: #2f73d7;
            font-weight: 600;
            text-decoration: none;
            font-size: 20px;
        }
        .submit-btn {
            width: 100%;
            border: 0;
            border-radius: 8px;
            background: linear-gradient(90deg, #2e79df, #1f5bc9);
            color: #fff;
            font-size: 28px;
            font-weight: 700;
            padding: 15px 16px;
            cursor: pointer;
        }
        .secondary-btn {
            width: 100%;
            border: 1px solid #2f79db;
            border-radius: 8px;
            background: transparent;
            color: #2f67ca;
            font-size: 18px;
            font-weight: 600;
            padding: 12px 16px;
            cursor: pointer;
        }
        .actions-stack {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-top: 22px;
        }
        @media (max-width: 1080px) {
            body { padding: 10px; }
            .auth-shell { grid-template-columns: 1fr; min-height: auto; }
            .brand-panel { padding: 42px 28px; }
            .brand-title { font-size: 34px; }
            .brand-subtitle { font-size: 20px; }
            .brand-copy { font-size: 16px; margin-bottom: 24px; }
            .benefits li { font-size: 17px; }
            .content-panel { padding: 34px 24px 42px; }
            .content-wrap h1 { font-size: 36px; }
            .lead { font-size: 19px; }
            label,
            input[type="email"],
            input[type="password"],
            .remember,
            .link { font-size: 16px; }
            .submit-btn { font-size: 23px; }
        }
    </style>
</head>
<body>
    <div class="auth-shell">
        <aside class="brand-panel">
            <img src="{{ asset('img/ASEAN.png') }}" alt="ASEAN" class="brand-logo">
            <h1 class="brand-title">Open Data</h1>
            <h3 class="brand-title" >Self-Assessment</h3>
            <p class="brand-copy">
                A dedicated platform for working groups to conduct the Open Data Self-Assessment and compute Open Data Scores across Coverage and Openness
            </p>
            <ul class="benefits">
                <li><span class="check">v</span> Guaranteed Security</li>
                <li><span class="check">v</span> Fast Access</li>
                <li><span class="check">v</span> 24/7 Support</li>
            </ul>
        </aside>
        <section class="content-panel">
            <div class="content-wrap">
                <h1>{{ $heading ?? 'Sign In' }}</h1>
                <p class="lead">{{ $description ?? 'Please enter your account details' }}</p>
                {{ $slot }}
            </div>
        </section>
    </div>
</body>
</html>
