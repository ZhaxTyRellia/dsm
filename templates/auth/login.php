<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <base href="<?= $e($baseUrl) ?>">
    <title>Sign In | Masjid Display</title>
    <link rel="icon" type="image/svg+xml" href="assets/img/favicon.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        :root {
            --emerald-950: #041d17;
            --emerald-900: #062c24;
            --emerald-800: #0c4438;
            --gold-500: #d4af37;
            --gold-400: #e7c865;
            --text-dark: #102a24;
            --text-muted: #5a7069;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            min-height: 100vh;
            font-family: "Plus Jakarta Sans", "Segoe UI", system-ui, -apple-system, sans-serif;
            background: radial-gradient(circle at 20% 18%, rgba(212, 175, 55, 0.16), transparent 42%),
                        radial-gradient(circle at 82% 82%, rgba(16, 185, 129, 0.18), transparent 42%),
                        linear-gradient(135deg, var(--emerald-950), var(--emerald-900) 58%, var(--emerald-800));
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-card {
            width: 100%;
            max-width: 410px;
            background: rgba(255, 255, 255, 0.97);
            border-radius: 22px;
            padding: 36px 32px;
            border: 1px solid rgba(212, 175, 55, 0.4);
            box-shadow: 0 28px 60px rgba(0, 0, 0, 0.42);
        }
        .login-brand {
            text-align: center;
            margin-bottom: 26px;
        }
        .login-logo-svg {
            width: 62px;
            height: 62px;
            margin-bottom: 12px;
        }
        .login-kicker {
            display: inline-block;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #8b6d11;
            background: rgba(212, 175, 55, 0.14);
            padding: 4px 12px;
            border-radius: 999px;
            margin-bottom: 10px;
        }
        .login-title {
            font-size: 22px;
            font-weight: 800;
            color: var(--emerald-900);
        }
        .login-sub {
            font-size: 13px;
            color: var(--text-muted);
            margin-top: 4px;
        }
        .form-group { margin-bottom: 16px; }
        .form-label {
            display: block;
            font-size: 12.5px;
            font-weight: 700;
            color: var(--emerald-900);
            margin-bottom: 7px;
        }
        .input-wrap {
            position: relative;
        }
        .input-icon {
            position: absolute;
            left: 13px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            pointer-events: none;
        }
        .form-control {
            width: 100%;
            padding: 12px 14px 12px 40px;
            border: 1.5px solid #d5e0db;
            border-radius: 12px;
            font-size: 14px;
            font-family: inherit;
            color: var(--text-dark);
            background: #ffffff;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-control:focus {
            border-color: var(--gold-500);
            box-shadow: 0 0 0 4px rgba(212, 175, 55, 0.16);
        }
        .toggle-pw {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: none;
            color: var(--text-muted);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 4px;
        }
        .btn-login {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            margin-top: 8px;
            padding: 13px 20px;
            border: none;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--emerald-900), var(--emerald-800));
            color: var(--gold-400);
            font-size: 14px;
            font-weight: 800;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            cursor: pointer;
            box-shadow: 0 12px 24px rgba(6, 44, 36, 0.25);
            transition: transform 0.15s ease;
        }
        .btn-login:hover { transform: translateY(-1px); }
        #toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 400;
            max-width: calc(100vw - 32px);
        }
        #toast {
            display: none;
            min-width: 280px;
            max-width: 400px;
            padding: 14px 16px;
            border-radius: 14px;
            font-size: 13px;
            font-weight: 600;
            position: relative;
            overflow: hidden;
            align-items: flex-start;
            gap: 12px;
            box-shadow: 0 20px 44px rgba(0, 0, 0, 0.35);
            color: #ffffff;
        }
        #toast.show { display: flex; }
        #toast.toast-error {
            background: #7f1d1d;
            border: 1px solid rgba(252, 165, 165, 0.45);
            border-left: 4px solid #f87171;
        }
        #toast.toast-success {
            background: #062c24;
            border: 1px solid rgba(212, 175, 55, 0.45);
            border-left: 4px solid #10b981;
        }
        .toast-icon {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        #toast.toast-error .toast-icon {
            background: rgba(248, 113, 113, 0.22);
            color: #fecaca;
        }
        #toast.toast-success .toast-icon {
            background: rgba(16, 185, 129, 0.22);
            color: #6ee7b7;
        }
        .toast-body { flex: 1; min-width: 0; }
        .toast-title {
            font-size: 11.5px;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            margin-bottom: 2px;
        }
        #toast.toast-error .toast-title { color: #fecaca; }
        #toast.toast-success .toast-title { color: #6ee7b7; }
        .toast-close {
            width: 24px;
            height: 24px;
            border-radius: 7px;
            border: none;
            background: rgba(255, 255, 255, 0.14);
            color: #ffffff;
            font-size: 15px;
            line-height: 1;
            cursor: pointer;
            flex-shrink: 0;
        }
        #toast-progress {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 3px;
            width: 100%;
        }
        #toast.toast-error #toast-progress { background: #f87171; }
        #toast.toast-success #toast-progress { background: #10b981; }
        .login-foot {
            margin-top: 20px;
            text-align: center;
            font-size: 12.5px;
            color: var(--text-muted);
        }
        .login-foot a {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: var(--emerald-800);
            font-weight: 700;
            text-decoration: none;
        }
    </style>
</head>

<body>
    <?php
    $toastMessage = '';
    $toastType = 'success';
    if (!empty($error)) {
        $toastMessage = (string) $error;
        $toastType = 'error';
    } elseif (!empty($flash['message'])) {
        $toastMessage = (string) $flash['message'];
        $toastType = ($flash['type'] ?? 'success') === 'error' ? 'error' : 'success';
    }
    $toastTitle = $toastType === 'error' ? 'Error' : 'Success';
    ?>
    <div id="toast-container" aria-live="polite">
        <div id="toast" class="toast-<?= $toastType ?>" role="status">
            <div class="toast-icon">
                <?php if ($toastType === 'error'): ?>
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                <?php else: ?>
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                <?php endif; ?>
            </div>
            <div class="toast-body">
                <div class="toast-title"><?= $toastTitle ?></div>
                <div id="toast-msg"><?= $e($toastMessage) ?></div>
            </div>
            <button type="button" class="toast-close" onclick="document.getElementById('toast').classList.remove('show')" aria-label="Close notification">&times;</button>
            <div id="toast-progress"></div>
        </div>
    </div>

    <div class="login-card">
        <div class="login-brand">
            <svg class="login-logo-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64">
                <rect width="64" height="64" rx="16" fill="#062c24"/>
                <rect x="3" y="3" width="58" height="58" rx="14" fill="none" stroke="#d4af37" stroke-width="2" opacity="0.65"/>
                <path d="M34.5 11.5a6 6 0 1 0 5.7 7.9 4.7 4.7 0 1 1-5.7-7.9z" fill="#d4af37"/>
                <path d="M32 20c-6.5 4.2-10 8.8-10 14v2h20v-2c0-5.2-3.5-9.8-10-14z" fill="#d4af37"/>
                <rect x="14" y="26" width="5" height="24" rx="1.5" fill="#d4af37"/>
                <rect x="45" y="26" width="5" height="24" rx="1.5" fill="#d4af37"/>
                <rect x="19" y="36" width="26" height="14" fill="#d4af37"/>
                <path d="M28 50v-6.5a4 4 0 0 1 8 0V50z" fill="#062c24"/>
                <rect x="11" y="49" width="42" height="3.5" rx="1.75" fill="#d4af37"/>
            </svg>
            <div><span class="login-kicker">Management Panel</span></div>
            <h1 class="login-title">Welcome Back</h1>
            <p class="login-sub">Masjid Display</p>
        </div>

        <form action="login" method="post">
            <div class="form-group">
                <label class="form-label" for="username">Username</label>
                <div class="input-wrap">
                    <svg class="input-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    <input id="username" class="form-control" type="text" name="username" placeholder="Enter your username" required autocomplete="username">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <div class="input-wrap">
                    <svg class="input-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    <input id="password" class="form-control" type="password" name="password" placeholder="Enter your password" required autocomplete="current-password" style="padding-right: 44px;">
                    <button type="button" class="toggle-pw" onclick="togglePassword()" aria-label="Show Password">
                        <svg id="eyeIcon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-login">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                <span>Sign In</span>
            </button>
        </form>

        <div class="login-foot">
            <a href="potrait">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="6" y="2" width="12" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
                <span>Open Live Display</span>
            </a>
        </div>
    </div>

    <script>
        function togglePassword() {
            var input = document.getElementById('password');
            input.type = input.type === 'password' ? 'text' : 'password';
        }
        <?php if ($toastMessage !== ''): ?>
            (function () {
                var toast = document.getElementById("toast");
                var progress = document.getElementById("toast-progress");
                progress.style.transition = "none";
                progress.style.width = "100%";
                toast.classList.add("show");
                setTimeout(function() {
                    progress.style.transition = "width 4.5s linear";
                    progress.style.width = "0%";
                }, 50);
                setTimeout(function() {
                    toast.classList.remove("show");
                }, 4500);
            })();
        <?php endif; ?>
    </script>
</body>

</html>
