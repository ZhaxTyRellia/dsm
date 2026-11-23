<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Login | Digital Signage</title>
    <link rel="shortcut icon" type="image/png" href="<?= $e($basePath) ?>/assets/img/favicon.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="<?= $e($basePath) ?>/assets/css/login.css">
</head>

<body>
    <div class="limiter">
        <div class="container-login100">
            <div class="wrap-login100">
                <form class="login100-form" action="<?= $e($basePath) ?>/login" method="post">
                    <span class="login100-form-title">Dashboard Panel</span>

                    <hr>
                    <br>

                    <div class="wrap-input100">
                        <input class="input100" type="text" name="username" placeholder="Username" required autocomplete="username">
                        <span class="focus-input100-1"></span>
                        <span class="focus-input100-2"></span>
                    </div>

                    <div class="space"></div>

                    <div class="wrap-input100">
                        <input class="input100" type="password" name="password" placeholder="Password" required autocomplete="current-password">
                        <span class="focus-input100-1"></span>
                        <span class="focus-input100-2"></span>
                    </div>

                    <div id="toast">
                        <div id="toast-msg"><?= $e($error ?? '') ?></div>
                        <div id="toast-progress"></div>
                    </div>

                    <script>
                        <?php if (!empty($error)): ?>
                            var toast = document.getElementById("toast");
                            var progress = document.getElementById("toast-progress");

                            progress.style.transition = "none";
                            progress.style.width = "100%";

                            toast.className = "show";

                            setTimeout(function() {
                                progress.style.transition = "width 5s linear";
                                progress.style.width = "0%";
                            }, 50);

                            setTimeout(function() {
                                toast.className = toast.className.replace("show", "");
                            }, 5000);
                        <?php endif; ?>
                    </script>

                    <div class="container-login100-form-btn">
                        <button type="submit" class="login100-form-btn">Sign In</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>

</html>
