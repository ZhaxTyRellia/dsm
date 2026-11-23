<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <base href="<?= $e($baseUrl) ?>">
    <title>Login | Digital Signage Masjid</title>
    <link rel="shortcut icon" type="image/png" href="assets/img/favicon.png">
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
        .login-logo {
            width: 58px;
            height: 58px;
            border-radius: 16px;
            object-fit: cover;
            border: 2px solid var(--gold-500);
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
        .form-control {
            width: 100%;
            padding: 12px 14px;
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
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
        }
        .btn-login {
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
        #toast {
            display: none;
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 11px 14px;
            border-radius: 11px;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 16px;
            position: relative;
            overflow: hidden;
        }
        #toast.show { display: block; }
        #toast-progress {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 3px;
            background: #dc2626;
            width: 100%;
        }
        .login-foot {
            margin-top: 20px;
            text-align: center;
            font-size: 12.5px;
            color: var(--text-muted);
        }
        .login-foot a {
            color: var(--emerald-800);
            font-weight: 700;
            text-decoration: none;
        }
    </style>
</head>

<body>
    <div class="login-card">
        <div class="login-brand">
            <img src="assets/img/favicon.png" alt="Logo" class="login-logo">
            <div><span class="login-kicker">Dashboard Panel</span></div>
            <h1 class="login-title">Masuk Pengelola</h1>
            <p class="login-sub">Digital Signage Masjid</p>
        </div>

        <div id="toast">
            <div id="toast-msg"><?= $e($error ?? '') ?></div>
            <div id="toast-progress"></div>
        </div>

        <form action="login" method="post">
            <div class="form-group">
                <label class="form-label" for="username">Username</label>
                <input id="username" class="form-control" type="text" name="username" placeholder="Masukkan username" required autocomplete="username">
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <div class="input-wrap">
                    <input id="password" class="form-control" type="password" name="password" placeholder="Masukkan password" required autocomplete="current-password" style="padding-right: 64px;">
                    <button type="button" class="toggle-pw" onclick="togglePassword()">Lihat</button>
                </div>
            </div>

            <button type="submit" class="btn-login">Sign In</button>
        </form>

        <div class="login-foot">
            <a href="potrait">Lihat Layar Display Signage &rarr;</a>
        </div>
    </div>

    <script>
        function togglePassword() {
            var input = document.getElementById('password');
            var btn = document.querySelector('.toggle-pw');
            if (input.type === 'password') {
                input.type = 'text';
                btn.textContent = 'Tutup';
            } else {
                input.type = 'password';
                btn.textContent = 'Lihat';
            }
        }
        <?php if (!empty($error)): ?>
            (function () {
                var toast = document.getElementById("toast");
                var progress = document.getElementById("toast-progress");
                progress.style.transition = "none";
                progress.style.width = "100%";
                toast.classList.add("show");
                setTimeout(function() {
                    progress.style.transition = "width 5s linear";
                    progress.style.width = "0%";
                }, 50);
                setTimeout(function() {
                    toast.classList.remove("show");
                }, 5000);
            })();
        <?php endif; ?>
    </script>
</body>

</html>
