<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk | SIGMA Politeknik Negeri Jakarta</title>
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/img/logo.png">
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        /* Design read: the supplied navy/lavender split separates SIGMA identity from the sign-in task. ENERGY 2 / RHYTHM 2 / MOTION 1; the existing system sans and logo keep the experience familiar. */
        :root {
            color-scheme: light;
            --navy: #1d2748;
            --navy-deep: #151d39;
            --lavender: #b6a8c9;
            --lavender-light: #d3c9df;
            --accent: #6246d0;
            --accent-hover: #4f36b2;
            --ink: #201d29;
            --muted: #514b5d;
            --field: #eceaec;
            --line: #d5d0d8;
            --white: #ffffff;
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        body {
            min-width: 320px;
            min-height: 100vh;
            min-height: 100svh;
            margin: 0;
            color: var(--ink);
            background: var(--lavender);
            font-family: "Segoe UI", -apple-system, BlinkMacSystemFont, Roboto, sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        a[href],
        button,
        summary {
            cursor: pointer;
        }

        button:disabled {
            cursor: not-allowed;
        }

        a {
            color: inherit;
        }

        a:focus-visible,
        button:focus-visible,
        summary:focus-visible,
        input:focus-visible {
            outline: 3px solid var(--accent);
            outline-offset: 3px;
        }

        .login-layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
            min-height: 100vh;
            min-height: 100svh;
            padding: 8px;
            gap: 2px;
            background: var(--navy);
        }

        .brand-panel,
        .login-main {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 0;
            min-height: calc(100vh - 16px);
            min-height: calc(100svh - 16px);
            overflow: hidden;
        }

        .brand-panel {
            isolation: isolate;
            background:
                radial-gradient(ellipse at 18% 82%, rgba(84, 105, 151, 0.18), transparent 38%),
                radial-gradient(ellipse at 76% 18%, rgba(97, 112, 153, 0.13), transparent 34%),
                var(--navy);
            color: var(--white);
        }

        .brand-panel::before {
            position: absolute;
            z-index: -1;
            width: min(72vw, 680px);
            aspect-ratio: 1;
            border: 1px solid rgba(226, 231, 248, 0.07);
            border-radius: 50%;
            content: "";
            transform: translate(-18%, 35%);
        }

        .brand-panel::after {
            position: absolute;
            z-index: -1;
            width: min(56vw, 520px);
            aspect-ratio: 1;
            border: 1px solid rgba(226, 231, 248, 0.06);
            border-radius: 50%;
            content: "";
            transform: translate(20%, 47%);
        }

        .brand-lockup {
            display: flex;
            align-items: center;
            gap: 22px;
            width: min(100% - 48px, 500px);
        }

        .brand-logo {
            display: block;
            width: 215px;
            height: 217px;
            flex: 0 0 auto;
            object-fit: contain;
        }

        .brand-name {
            margin: 0;
            color: var(--white);
            font-family: Georgia, "Times New Roman", serif;
            font-size: clamp(2.4rem, 4vw, 3.2rem);
            font-weight: 700;
            line-height: 1.1;
        }

        .brand-tagline {
            margin: 5px 0 0;
            color: #e3e1ee;
            font-size: clamp(1rem, 1.5vw, 1.2rem);
        }

        .login-main {
            isolation: isolate;
            padding: clamp(24px, 5vw, 72px);
            background: var(--lavender);
        }

        .login-main::after {
            position: absolute;
            z-index: -1;
            right: -112px;
            bottom: -156px;
            width: 300px;
            aspect-ratio: 1;
            border-radius: 50%;
            background: var(--lavender-light);
            content: "";
        }

        .login-card {
            width: min(100%, 390px);
            padding: clamp(24px, 3.2vw, 38px);
            border-radius: 12px;
            background: var(--white);
            box-shadow: 0 12px 32px rgba(27, 21, 47, 0.08);
        }

        .login-title {
            margin: 0 0 24px;
            color: var(--ink);
            font-size: clamp(1.35rem, 2vw, 1.6rem);
            font-weight: 650;
            line-height: 1.2;
        }

        .notice {
            margin: 0 0 18px;
            padding: 8px 14px;
            border: 1px solid transparent;
            border-radius: 999px;
            font-size: 0.84rem;
            line-height: 1.4;
            display: inline-flex;
            align-items: center;
            width: fit-content;
            max-width: 100%;
        }

        .notice p {
            margin: 0;
        }

        .notice-danger {
            border-color: #e3b4ba;
            background: #fff1f2;
            color: #76232e;
        }

        .notice-success {
            border-color: #a7ccb5;
            background: #edf8f0;
            color: #205a34;
        }

        .notice-warning {
            border-color: #e5d09c;
            background: #fff8e6;
            color: #684f16;
        }

        .notice-info {
            border-color: #b8c5e0;
            background: #f0f4fc;
            color: #283f6c;
        }

        .field {
            margin-bottom: 18px;
        }

        .field label {
            display: block;
            margin-bottom: 7px;
            color: #302b36;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .field-control {
            display: flex;
            min-height: 48px;
            overflow: hidden;
            border: 1px solid var(--line);
            border-radius: 7px;
            background: var(--field);
            transition: border-color 140ms ease, background-color 140ms ease;
        }

        .field-control:focus-within {
            border-color: var(--accent);
            background: var(--white);
        }

        .field-control input {
            width: 100%;
            min-width: 0;
            min-height: 46px;
            padding: 0 13px;
            border: 0;
            background: transparent;
            color: var(--ink);
            font: inherit;
            font-size: 0.95rem;
        }

        .field-control input:focus-visible {
            outline: 0;
        }

        .field-control input::placeholder {
            color: #625c68;
            opacity: 1;
        }

        .field-control:focus-within:has(input:focus-visible) {
            outline: 3px solid var(--accent);
            outline-offset: 2px;
        }

        .field-control.has-error {
            border-color: #a32d3b;
        }

        .password-toggle {
            flex: 0 0 auto;
            min-width: 88px;
            min-height: 46px;
            padding: 0 12px;
            border: 0;
            border-left: 1px solid var(--line);
            background: transparent;
            color: #40317d;
            font: inherit;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .password-toggle:hover {
            background: #e2ddec;
            color: #2f225f;
        }

        .password-toggle:active {
            background: #d9d0e6;
        }

        .field-error {
            margin: 6px 0 0;
            color: #76232e;
            font-size: 0.8rem;
        }

        .captcha-box {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
        }

        .captcha-img {
            display: block;
            border-radius: 7px;
            border: 1px solid var(--line);
            height: 46px;
            width: 140px;
            background: #f6f5fb;
            object-fit: contain;
        }

        .captcha-refresh {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 44px;
            height: 44px;
            border-radius: 7px;
            border: 1px solid var(--line);
            background: var(--field);
            color: var(--ink);
            font-size: 1.15rem;
            transition: background-color 140ms ease, border-color 140ms ease;
        }

        .captcha-refresh:hover {
            background: #e2ddec;
            border-color: var(--accent);
            color: var(--accent);
        }

        .captcha-refresh:active {
            background: #d9d0e6;
        }

        .submit-button {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            width: 100%;
            min-height: 48px;
            margin-top: 26px;
            padding: 10px 16px;
            border: 1px solid var(--accent);
            border-radius: 7px;
            background: var(--accent);
            color: var(--white);
            font: inherit;
            font-size: 0.9rem;
            font-weight: 600;
            transition: background-color 140ms ease, border-color 140ms ease;
        }

        .submit-button:hover:not(:disabled) {
            border-color: var(--accent-hover);
            background: var(--accent-hover);
        }

        .submit-button:active:not(:disabled) {
            background: #432b9c;
        }

        .submit-button:disabled {
            border-color: #76699f;
            background: #76699f;
        }

        .submit-arrow {
            font-size: 1rem;
            line-height: 1;
        }

        .test-account-details {
            margin-top: 20px;
            padding-top: 16px;
            border-top: 1px solid #e8e4eb;
            color: var(--muted);
            font-size: 0.82rem;
        }

        .test-account-details summary {
            width: fit-content;
            min-height: 44px;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #35303b;
            font-weight: 600;
        }

        .test-account-details summary::after {
            width: 8px;
            height: 8px;
            border-right: 1.5px solid currentColor;
            border-bottom: 1.5px solid currentColor;
            content: "";
            transform: translateY(-2px) rotate(45deg);
            transition: transform 140ms ease;
        }

        .test-account-details[open] summary::after {
            transform: translateY(2px) rotate(225deg);
        }

        .account-list {
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
            margin: 5px 0 10px;
        }

        .account-chip {
            min-height: 40px;
            padding: 7px 11px;
            border: 1px solid #c9c2d0;
            border-radius: 5px;
            background: var(--white);
            color: #302b36;
            font: inherit;
            font-size: 0.8rem;
            transition: border-color 140ms ease, background-color 140ms ease;
        }

        .account-chip:hover {
            border-color: var(--accent);
            background: #f3f0fc;
        }

        .account-chip:active {
            background: #e8e2f7;
        }

        .account-hint {
            margin: 0;
            color: var(--muted);
            font-size: 0.78rem;
        }

        .account-hint code {
            color: #392b76;
            font-family: inherit;
            font-weight: 700;
        }

        .login-footer {
            position: absolute;
            right: 20px;
            bottom: 17px;
            left: 20px;
            z-index: 1;
        }

        .public-links {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 8px 22px;
        }

        .public-links a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 44px;
            color: #342d43;
            font-size: 0.82rem;
            font-weight: 600;
            text-decoration-thickness: 1px;
            text-underline-offset: 3px;
        }

        .public-links a:hover {
            color: #241a45;
            text-decoration-thickness: 2px;
        }

        @media (max-width: 760px) {
            .login-layout {
                grid-template-columns: minmax(0, 1fr);
                padding: 6px;
                gap: 2px;
            }

            .brand-panel {
                min-height: 156px;
                justify-content: flex-start;
            }

            .brand-lockup {
                width: auto;
                margin: 0 24px;
                gap: 14px;
            }

            .brand-logo {
                width: 112px;
                height: 114px;
            }

            .brand-name {
                font-size: 2rem;
            }

            .brand-tagline {
                font-size: 0.95rem;
            }

            .login-main {
                min-height: auto;
                padding: 28px 18px 78px;
            }

            .login-main::after {
                right: -176px;
                bottom: -204px;
                width: 310px;
            }

            .login-card {
                width: min(100%, 430px);
                padding: 26px 22px;
            }

            .login-footer {
                bottom: 7px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                scroll-behavior: auto !important;
                transition-duration: 0.01ms !important;
            }
        }
    </style>
</head>
<body>
    <div class="login-layout">
        <header class="brand-panel">
            <div class="brand-lockup">
                <img
                    class="brand-logo"
                    src="<?= BASE_URL ?>/assets/img/logo.png"
                    alt="Logo SIGMA"
                    width="215"
                    height="217">
                <div>
                    <p class="brand-name">SIGMA</p>
                    <p class="brand-tagline">Tanda Tangan Digital</p>
                </div>
            </div>
        </header>

        <main class="login-main">
            <section class="login-card" aria-labelledby="login-title">
                <h1 class="login-title" id="login-title">Masuk ke Sigma</h1>

                <?php if (!empty($_SESSION['flash'])): ?>
                    <?php
                        $flash = $_SESSION['flash'];
                        $flashType = $flash['type'] ?? 'info';
                        $noticeType = in_array($flashType, ['success', 'danger', 'warning', 'info'], true)
                            ? $flashType
                            : 'info';
                        unset($_SESSION['flash']);
                    ?>
                    <div class="notice notice-<?= htmlspecialchars($noticeType) ?>" role="status">
                        <p><?= htmlspecialchars($flash['message'] ?? '') ?></p>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errors['general'])): ?>
                    <div class="notice notice-danger" role="alert">
                        <p><?= htmlspecialchars($errors['general']) ?></p>
                    </div>
                <?php endif; ?>

                <form id="loginForm" action="<?= BASE_URL ?>/auth/login" method="POST">
                    <div class="field">
                        <label for="inputEmail">Email</label>
                        <div class="field-control <?= isset($errors['email']) ? 'has-error' : '' ?>">
                            <input
                                type="email"
                                id="inputEmail"
                                name="email"
                                placeholder="nama@sigma.local"
                                value="<?= htmlspecialchars($old['email'] ?? ($_GET['email'] ?? '')) ?>"
                                autocomplete="username"
                                aria-invalid="<?= isset($errors['email']) ? 'true' : 'false' ?>"
                                <?= isset($errors['email']) ? 'aria-describedby="emailError"' : '' ?>
                                required
                                autofocus>
                        </div>
                        <?php if (isset($errors['email'])): ?>
                            <p class="field-error" id="emailError"><?= htmlspecialchars($errors['email']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="field">
                        <label for="inputPassword">Password</label>
                        <div class="field-control <?= isset($errors['password']) ? 'has-error' : '' ?>">
                            <input
                                type="password"
                                id="inputPassword"
                                name="password"
                                autocomplete="current-password"
                                aria-invalid="<?= isset($errors['password']) ? 'true' : 'false' ?>"
                                <?= isset($errors['password']) ? 'aria-describedby="passwordError"' : '' ?>
                                required>
                            <button
                                class="password-toggle"
                                type="button"
                                id="togglePasswordBtn"
                                aria-controls="inputPassword"
                                aria-pressed="false">Tampilkan</button>
                        </div>
                        <?php if (isset($errors['password'])): ?>
                            <p class="field-error" id="passwordError"><?= htmlspecialchars($errors['password']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="field">
                        <label for="inputCaptcha">Kode Captcha</label>
                        <div class="captcha-box">
                            <img
                                src="<?= BASE_URL ?>/auth/captcha?t=<?= time() ?>"
                                alt="Kode Captcha"
                                id="captchaImg"
                                class="captcha-img"
                                width="140"
                                height="46">
                            <button
                                type="button"
                                id="btnRefreshCaptcha"
                                class="captcha-refresh"
                                aria-label="Muat ulang kode captcha"
                                title="Muat ulang captcha">
                                <i class="bi bi-arrow-clockwise" aria-hidden="true"></i>
                            </button>
                        </div>
                        <div class="field-control <?= isset($errors['captcha']) ? 'has-error' : '' ?>">
                            <input
                                type="text"
                                id="inputCaptcha"
                                name="captcha"
                                placeholder="Masukkan 5 karakter di atas"
                                maxlength="5"
                                autocomplete="off"
                                autocapitalize="characters"
                                aria-invalid="<?= isset($errors['captcha']) ? 'true' : 'false' ?>"
                                <?= isset($errors['captcha']) ? 'aria-describedby="captchaError"' : '' ?>
                                required>
                        </div>
                        <?php if (isset($errors['captcha'])): ?>
                            <p class="field-error" id="captchaError"><?= htmlspecialchars($errors['captcha']) ?></p>
                        <?php endif; ?>
                    </div>

                    <button class="submit-button" type="submit" id="submitButton">
                        <span class="submit-label">Masuk</span>
                        <span class="submit-arrow" aria-hidden="true">&rarr;</span>
                    </button>
                </form>

                <details class="test-account-details">
                    <summary>Akun Pengujian (Seed)</summary>
                    <div class="account-list">
                        <button class="account-chip" type="button" data-email="admin@sigma.local">Admin</button>
                        <button class="account-chip" type="button" data-email="mahasiswa@sigma.local">Mahasiswa</button>
                        <button class="account-chip" type="button" data-email="dosen@sigma.local">Dosen</button>
                        <button class="account-chip" type="button" data-email="kajur@sigma.local">Kajur</button>
                        <button class="account-chip" type="button" data-email="tendik@sigma.local">Tendik</button>
                    </div>
                    <p class="account-hint">Password akun pengujian: <code>password</code></p>
                </details>
            </section>

            <footer class="login-footer">
                <nav class="public-links" aria-label="Navigasi publik">
                    <a href="<?= BASE_URL ?>/">Kembali ke Beranda</a>
                    <a href="<?= BASE_URL ?>/verify">Verifikasi Keaslian Dokumen</a>
                </nav>
            </footer>
        </main>
    </div>

    <script>
        const loginForm = document.getElementById('loginForm');
        const submitButton = document.getElementById('submitButton');
        const passwordInput = document.getElementById('inputPassword');
        const togglePasswordButton = document.getElementById('togglePasswordBtn');
        const captchaImg = document.getElementById('captchaImg');
        const btnRefreshCaptcha = document.getElementById('btnRefreshCaptcha');

        if (btnRefreshCaptcha && captchaImg) {
            btnRefreshCaptcha.addEventListener('click', function () {
                captchaImg.src = '<?= BASE_URL ?>/auth/captcha?t=' + new Date().getTime();
            });
        }

        togglePasswordButton.addEventListener('click', function () {
            const showPassword = passwordInput.type === 'password';
            passwordInput.type = showPassword ? 'text' : 'password';
            togglePasswordButton.textContent = showPassword ? 'Sembunyikan' : 'Tampilkan';
            togglePasswordButton.setAttribute('aria-pressed', String(showPassword));
        });

        document.querySelectorAll('.account-chip').forEach(function (button) {
            button.addEventListener('click', function () {
                document.getElementById('inputEmail').value = button.dataset.email;
                passwordInput.value = 'password';
                document.getElementById('inputEmail').focus();
            });
        });

        loginForm.addEventListener('submit', function () {
            submitButton.disabled = true;
            submitButton.querySelector('.submit-label').textContent = 'Memproses...';
            loginForm.setAttribute('aria-busy', 'true');
        });
    </script>
</body>
</html>
