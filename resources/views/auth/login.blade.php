<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light dark">
    <title>Login | CSCI Support Portal</title>
    <link rel="icon" href="{{ asset('assets/images/favicon.ico') }}" type="image/png" />
    <script>
        (function () {
            const storageKey = 'support-portal-theme';
            const savedTheme = window.localStorage.getItem(storageKey);
            document.documentElement.classList.add(savedTheme === 'dark-theme' ? 'dark-theme' : 'light-theme');
        })();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/icons.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/notifications/css/lobibox.min.css') }}">
    <style>
        :root {
            --brand-900: #00491e;
            --brand-700: #02681e;
            --brand-500: #919f02;
            --accent: #ffc600;
            --surface: #ffffff;
            --surface-alt: #eef3ee;
            --text: #0d2516;
            --muted: #46604f;
        }

        html.dark-theme {
            --brand-900: #f3f4f6;
            --brand-700: #d1d5db;
            --brand-500: #10a37f;
            --accent: #10a37f;
            --surface: #2f2f2f;
            --surface-alt: #212121;
            --text: #ececec;
            --muted: #a3a3a3;
            color-scheme: dark;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: "Plus Jakarta Sans", "Segoe UI", Arial, sans-serif;
            color: var(--text);
            min-height: 100vh;
            background:
                radial-gradient(circle at 14% 8%, rgba(145, 159, 2, 0.25) 0, rgba(145, 159, 2, 0) 32%),
                radial-gradient(circle at 90% 10%, rgba(2, 104, 30, 0.2) 0, rgba(2, 104, 30, 0) 30%),
                linear-gradient(180deg, #f9fbf7 0%, var(--surface-alt) 100%);
        }

        .shell {
            width: min(1200px, 92vw);
            margin: 0 auto;
            padding: 1.25rem 0 2.5rem;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.95rem 1.1rem;
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.76);
            border: 1px solid rgba(0, 73, 30, 0.12);
            backdrop-filter: blur(10px);
            box-shadow: 0 12px 30px rgba(0, 73, 30, 0.1);
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 0.65rem;
            text-decoration: none;
            color: var(--brand-900);
            font-weight: 800;
            letter-spacing: 0.2px;
        }

        .brand img {
            width: 34px;
            height: 34px;
            object-fit: contain;
        }

        .top-actions {
            display: flex;
            gap: 0.65rem;
            align-items: center;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .theme-toggle {
            width: 44px;
            height: 44px;
            border: 1px solid rgba(0, 73, 30, 0.14);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.76);
            color: var(--brand-900);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 22px rgba(0, 73, 30, 0.12);
            transition: transform 0.18s ease, box-shadow 0.18s ease, background-color 0.18s ease;
        }

        .theme-toggle:hover {
            transform: translateY(-1px);
            box-shadow: 0 14px 28px rgba(0, 73, 30, 0.18);
        }

        .btn-landing {
            text-decoration: none;
            border-radius: 11px;
            padding: 0.68rem 1.15rem;
            font-size: 0.92rem;
            font-weight: 700;
            min-height: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.18s ease, box-shadow 0.18s ease;
            border: 1px solid transparent;
        }

        .btn-landing:hover {
            transform: translateY(-1px);
        }

        .btn-primary-landing {
            color: #fff;
            background: linear-gradient(135deg, var(--brand-700), var(--brand-900));
            box-shadow: 0 10px 22px rgba(2, 104, 30, 0.26);
        }

        .btn-ghost {
            color: var(--brand-900);
            background: rgba(255, 255, 255, 0.72);
            border-color: rgba(0, 73, 30, 0.18);
        }

        .hero {
            margin-top: 1.1rem;
            border-radius: 24px;
            padding: clamp(1.6rem, 3.2vw, 3rem);
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.82), rgba(255, 255, 255, 0.65));
            border: 1px solid rgba(0, 73, 30, 0.12);
            backdrop-filter: blur(8px);
            box-shadow: 0 20px 40px rgba(0, 73, 30, 0.1);
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            gap: 1.4rem;
            align-items: start;
        }

        .eyebrow {
            display: inline-block;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            font-weight: 800;
            color: var(--brand-700);
            background: rgba(145, 159, 2, 0.2);
            border: 1px solid rgba(145, 159, 2, 0.3);
            border-radius: 999px;
            padding: 0.38rem 0.7rem;
            margin-bottom: 0.8rem;
        }

        h1 {
            font-size: clamp(2rem, 4.5vw, 3.7rem);
            line-height: 1.03;
            color: var(--brand-900);
            max-width: 14ch;
        }

        h1 span {
            color: var(--brand-500);
        }

        .lead {
            margin-top: 0.9rem;
            color: var(--muted);
            font-size: clamp(0.96rem, 1.3vw, 1.1rem);
            line-height: 1.65;
            max-width: 50ch;
        }

        .hero-actions {
            margin-top: 1.2rem;
            display: flex;
            gap: 0.7rem;
            flex-wrap: wrap;
        }

        .metrics {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.7rem;
        }

        .metric {
            border-radius: 15px;
            padding: 0.95rem;
            border: 1px solid rgba(0, 73, 30, 0.1);
            background: rgba(255, 255, 255, 0.86);
            box-shadow: 0 10px 24px rgba(0, 73, 30, 0.08);
        }

        .metric strong {
            display: block;
            color: var(--brand-900);
            font-size: 1.05rem;
            margin-bottom: 0.2rem;
        }

        .metric span {
            font-size: 0.82rem;
            color: var(--muted);
        }

        .logo-panel {
            margin-top: 0.7rem;
            border-radius: 18px;
            padding: 1rem;
            border: 1px solid rgba(0, 73, 30, 0.12);
            background: linear-gradient(150deg, rgba(255, 255, 255, 0.9), rgba(233, 236, 239, 0.78));
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .logo-panel img {
            width: min(210px, 62%);
            animation: floaty 4s ease-in-out infinite;
        }

        .login-panel {
            margin-top: 0.9rem;
            border-radius: 14px;
            padding: 1rem;
            border: 1px solid rgba(0, 73, 30, 0.12);
            background: rgba(255, 255, 255, 0.92);
            box-shadow: 0 10px 24px rgba(0, 73, 30, 0.08);
        }

        .login-panel h3 {
            color: var(--brand-900);
            font-size: 0.95rem;
            font-weight: 800;
            margin-bottom: 0.7rem;
        }

        .form-label {
            font-size: 0.82rem;
            font-weight: 700;
            color: var(--brand-900);
        }

        .form-control {
            border-radius: 10px;
            border-color: rgba(0, 73, 30, 0.2);
            min-height: 42px;
            font-size: 0.9rem;
        }

        .form-control:focus {
            border-color: var(--brand-500);
            box-shadow: 0 0 0 0.2rem rgba(145, 159, 2, 0.18);
        }

        .btn-submit {
            width: 100%;
            border: 0;
            border-radius: 10px;
            min-height: 42px;
            font-size: 0.9rem;
            font-weight: 700;
            color: #fff;
            background: linear-gradient(135deg, var(--brand-700), var(--brand-900));
        }

        #togglePassword {
            border-left: 0;
            background: #fff;
            border-color: rgba(0, 73, 30, 0.2);
        }

        .feature-row {
            margin-top: 1rem;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.8rem;
        }

        .feature {
            border-radius: 14px;
            padding: 1rem;
            border: 1px solid rgba(0, 73, 30, 0.12);
            background: rgba(255, 255, 255, 0.86);
        }

        .feature h3 {
            font-size: 0.92rem;
            color: var(--brand-900);
            margin-bottom: 0.35rem;
        }

        .feature p {
            font-size: 0.83rem;
            color: var(--muted);
            line-height: 1.55;
        }

        html.dark-theme body {
            background:
                radial-gradient(circle at 14% 8%, rgba(16, 163, 127, 0.12) 0, rgba(16, 163, 127, 0) 32%),
                radial-gradient(circle at 90% 10%, rgba(255, 255, 255, 0.05) 0, rgba(255, 255, 255, 0) 30%),
                linear-gradient(180deg, #171717 0%, var(--surface-alt) 100%);
        }

        html.dark-theme .topbar,
        html.dark-theme .hero,
        html.dark-theme .metric,
        html.dark-theme .logo-panel,
        html.dark-theme .login-panel,
        html.dark-theme .feature,
        html.dark-theme .btn-ghost,
        html.dark-theme .theme-toggle {
            background: rgba(47, 47, 47, 0.82);
            border-color: rgba(255, 255, 255, 0.08);
            box-shadow: 0 18px 40px rgba(0, 0, 0, 0.28);
        }

        html.dark-theme .hero {
            background: linear-gradient(145deg, rgba(47, 47, 47, 0.95), rgba(33, 33, 33, 0.92));
        }

        html.dark-theme .logo-panel {
            background: linear-gradient(150deg, rgba(52, 52, 52, 0.94), rgba(38, 38, 38, 0.9));
        }

        html.dark-theme .eyebrow {
            color: #d6fff3;
            background: rgba(16, 163, 127, 0.14);
            border-color: rgba(16, 163, 127, 0.24);
        }

        html.dark-theme .btn-primary-landing,
        html.dark-theme .btn-submit {
            color: #f8fffc;
            background: linear-gradient(135deg, #10a37f, #0f8b6d);
            box-shadow: 0 14px 26px rgba(16, 163, 127, 0.24);
        }

        html.dark-theme .form-control,
        html.dark-theme #togglePassword {
            color: var(--text);
            background: #1f1f1f;
            border-color: rgba(255, 255, 255, 0.1);
        }

        html.dark-theme .form-control:focus {
            border-color: #10a37f;
            box-shadow: 0 0 0 0.2rem rgba(16, 163, 127, 0.16);
        }

        html.dark-theme .brand,
        html.dark-theme h1,
        html.dark-theme .login-panel h3,
        html.dark-theme .form-label,
        html.dark-theme .metric strong,
        html.dark-theme .feature h3 {
            color: var(--text);
        }

        @keyframes floaty {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-6px); }
        }

        @media (max-width: 980px) {
            .hero { grid-template-columns: 1fr; }
            .feature-row { grid-template-columns: 1fr; }
            .metrics { grid-template-columns: 1fr; }
        }

        @media (max-width: 640px) {
            .shell { width: min(1200px, 94vw); }
            .brand span { display: none; }
            .top-actions { width: 100%; justify-content: flex-start; margin-top: 0.55rem; }
            .top-actions .btn-landing { padding: 0.62rem 0.9rem; font-size: 0.84rem; flex: 1 1 140px; }
            .topbar { align-items: flex-start; flex-direction: column; }
            .hero { margin-top: 0.9rem; border-radius: 18px; padding: 1.1rem; gap: 0.95rem; }
            .hero-actions { width: 100%; }
            .hero-actions .btn-landing { flex: 1 1 100%; width: 100%; }
            .metric { padding: 0.85rem; }
            .feature { padding: 0.9rem; }
        }
    </style>
</head>
<body>
    <div class="shell">
        <div class="topbar">
            <a href="{{ url('/') }}" class="brand">
                <img src="{{ asset('assets/images/logo-icon.png') }}" alt="CSCI Logo">
                <span>CSCI Support Portal.</span>
            </a>
            <div class="top-actions">
                <button type="button" class="theme-toggle" id="themeToggle" aria-label="Toggle dark mode">
                    <i class="bx bx-moon" id="themeToggleIcon"></i>
                </button>
            </div>
        </div>

        <main class="hero">
            <section>
                <span class="eyebrow">CSCI Digital Support</span>
                <h1>Support with <span>speed</span>, visibility, and control.</h1>
                <p class="lead">
                    This portal is dedicated to concerns related to MIS systems and services.
                    Every issue you submit is routed and supported by the MIS team from intake to resolution.
                </p>
                <div class="login-panel">
                    <h3>Sign In to Continue</h3>
                    @if ($errors->any())
                        <div class="alert alert-danger" role="alert">
                            {{ $errors->first() }}
                        </div>
                    @endif
                    <form method="POST" action="{{ route('login') }}">
                        @csrf
                        <div class="mb-2">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" autocomplete="username">
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control border-end-0" id="password" name="password" autocomplete="current-password">
                                <button type="button" id="togglePassword" class="input-group-text">
                                    <i class="bx bx-hide"></i>
                                </button>
                            </div>
                        </div>
                        <button type="submit" class="btn-submit">Login</button>
                    </form>
                </div>
            </section>

            <section>
                <div class="metrics">
                    <article class="metric">
                        <strong>Requester to MIS Team</strong>
                        <span>Issues move directly from reporting users to MIS support personnel.</span>
                    </article>
                    <article class="metric">
                        <strong>Structured Ticket Updates</strong>
                        <span>Track progress from new, assigned, in-progress, to resolved.</span>
                    </article>
                    <article class="metric">
                        <strong>Issue Visibility</strong>
                        <span>Monitor open and resolved MIS-related reports in one place.</span>
                    </article>
                    <article class="metric">
                        <strong>Traceable Actions</strong>
                        <span>Maintain complete status history and accountability per report.</span>
                    </article>
                </div>

                <div class="logo-panel">
                    <img src="{{ asset('assets/images/logo-icon.png') }}" alt="CSCI Logo">
                </div>
            </section>
        </main>

        <section class="feature-row">
            <article class="feature">
                <h3>Clear MIS Intake</h3>
                <p>Capture and organize system issues with complete details for faster MIS action.</p>
            </article>
            <article class="feature">
                <h3>Faster Assignment</h3>
                <p>Prioritize reported concerns and route them quickly to the appropriate MIS staff.</p>
            </article>
            <article class="feature">
                <h3>Consistent Support Workflow</h3>
                <p>Standardize diagnostics, updates, and closure across the MIS support team.</p>
            </article>
        </section>
    </div>

    <script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/js/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/notifications/js/lobibox.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/notifications/js/notifications.min.js') }}"></script>
    <script>
        window.portalToastsDisabled = true;

        if (window.Lobibox) {
            window.Lobibox.notify = function () {};
        }
    </script>
    <script>
        $(document).ready(function () {
            const storageKey = 'support-portal-theme';
            const root = document.documentElement;
            const themeToggle = document.getElementById('themeToggle');
            const themeToggleIcon = document.getElementById('themeToggleIcon');

            function syncThemeIcon() {
                const isDark = root.classList.contains('dark-theme');
                themeToggleIcon.className = isDark ? 'bx bx-sun' : 'bx bx-moon';
            }

            syncThemeIcon();

            themeToggle.addEventListener('click', function () {
                const nextTheme = root.classList.contains('dark-theme') ? 'light-theme' : 'dark-theme';
                root.classList.remove('light-theme', 'dark-theme');
                root.classList.add(nextTheme);
                window.localStorage.setItem(storageKey, nextTheme);
                syncThemeIcon();
            });

            @if(session('error'))
                Lobibox.notify('error', {
                    size: 'mini',
                    rounded: true,
                    delayIndicator: true,
                    sound: false,
                    position: 'top right',
                    icon: 'bx bx-x-circle',
                    msg: "{{ session('error') }}"
                });
            @endif

            @if ($errors->any())
                @foreach ($errors->all() as $error)
                    Lobibox.notify('error', {
                        size: 'mini',
                        rounded: true,
                        delayIndicator: true,
                        sound: false,
                        position: 'top right',
                        icon: 'bx bx-error',
                        msg: "{{ $error }}"
                    });
                @endforeach
            @endif

            $('#togglePassword').on('click', function () {
                const passwordInput = $('#password');
                const icon = $(this).find('i');
                const isPassword = passwordInput.attr('type') === 'password';
                passwordInput.attr('type', isPassword ? 'text' : 'password');
                icon.toggleClass('bx-hide bx-show');
            });
        });
    </script>
</body>
</html>
