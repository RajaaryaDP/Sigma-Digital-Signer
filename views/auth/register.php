<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi Akun Civitas | SIGMA Politeknik Negeri Jakarta</title>
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/img/logo.png">
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        /* Design read: Selaras penuh dengan Split-Screen Navy & Lavender pada views/auth/login.php */
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
        button {
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
            padding: clamp(24px, 4vw, 56px) clamp(20px, 4vw, 48px) 76px;
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
            width: min(100%, 440px);
            padding: clamp(24px, 3vw, 34px);
            border-radius: 12px;
            background: var(--white);
            box-shadow: 0 12px 32px rgba(27, 21, 47, 0.08);
        }

        .login-title {
            margin: 0 0 14px;
            color: var(--ink);
            font-size: clamp(1.3rem, 2vw, 1.55rem);
            font-weight: 650;
            line-height: 1.25;
        }

        .login-subtitle {
            margin: 0 0 18px;
            color: var(--muted);
            font-size: 0.84rem;
            line-height: 1.5;
            text-align: justify;
        }

        .notice {
            margin: 0 0 16px;
            padding: 12px 14px;
            border: 1px solid transparent;
            border-radius: 7px;
            font-size: 0.85rem;
            line-height: 1.45;
        }

        .notice p {
            margin: 0;
        }

        .notice-danger {
            border-color: #e3b4ba;
            background: #fff1f2;
            color: #76232e;
        }

        .notice-info {
            border-color: #b8c5e0;
            background: #f0f4fc;
            color: #283f6c;
        }

        .field {
            margin-bottom: 14px;
        }

        .field label {
            display: block;
            margin-bottom: 6px;
            color: #302b36;
            font-size: 0.84rem;
            font-weight: 600;
        }

        .field-control {
            display: flex;
            min-height: 45px;
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
            min-height: 43px;
            padding: 0 13px;
            border: 0;
            background: transparent;
            color: var(--ink);
            font: inherit;
            font-size: 0.92rem;
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
            min-width: 86px;
            min-height: 43px;
            padding: 0 11px;
            border: 0;
            border-left: 1px solid var(--line);
            background: transparent;
            color: #40317d;
            font: inherit;
            font-size: 0.78rem;
            font-weight: 600;
        }

        .password-toggle:hover {
            background: #e2ddec;
            color: #2f225f;
        }

        .field-hint {
            margin: 5px 0 0;
            color: var(--muted);
            font-size: 0.76rem;
            line-height: 1.35;
        }

        .field-error {
            margin: 5px 0 0;
            color: #76232e;
            font-size: 0.78rem;
        }

        .submit-button {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            width: 100%;
            min-height: 46px;
            margin-top: 20px;
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

        .submit-button:disabled {
            border-color: #76699f;
            background: #76699f;
        }

        .auth-switch {
            margin: 16px 0 0;
            padding-top: 14px;
            border-top: 1px solid #e8e4eb;
            text-align: center;
            color: var(--muted);
            font-size: 0.84rem;
        }

        .auth-switch a {
            color: var(--accent);
            font-weight: 600;
            text-underline-offset: 3px;
        }

        .auth-switch a:hover {
            color: var(--accent-hover);
        }

        .login-footer {
            position: absolute;
            right: 20px;
            bottom: 14px;
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
            min-height: 40px;
            color: #342d43;
            font-size: 0.82rem;
            font-weight: 600;
            text-decoration-thickness: 1px;
            text-underline-offset: 3px;
        }

        @media (max-width: 760px) {
            .login-layout {
                grid-template-columns: minmax(0, 1fr);
                padding: 6px;
                gap: 2px;
            }

            .brand-panel {
                min-height: 148px;
                justify-content: flex-start;
            }

            .brand-lockup {
                width: auto;
                margin: 0 24px;
                gap: 14px;
            }

            .brand-logo {
                width: 104px;
                height: 106px;
            }

            .brand-name {
                font-size: 1.9rem;
            }

            .brand-tagline {
                font-size: 0.92rem;
            }

            .login-main {
                min-height: auto;
                padding: 24px 16px 76px;
            }

            .login-card {
                width: min(100%, 440px);
                padding: 24px 20px;
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
            <section class="login-card" aria-labelledby="register-title">
                <h1 class="login-title" id="register-title">Registrasi Akun Civitas</h1>

                <?php if (!empty($errors['general'])): ?>
                    <div class="notice notice-danger" role="alert">
                        <p><?= htmlspecialchars($errors['general']) ?></p>
                    </div>
                <?php endif; ?>

                <form id="registerForm" action="<?= BASE_URL ?>/auth/register" method="POST">
                    <div class="field">
                        <label for="inputIdentifier">NIM / NIP Resmi</label>
                        <div class="field-control <?= isset($errors['identifier']) ? 'has-error' : '' ?>">
                            <input
                                type="text"
                                id="inputIdentifier"
                                name="identifier"
                                placeholder="Contoh: 2207411001 atau 198503152010121002"
                                value="<?= htmlspecialchars($old['identifier'] ?? '') ?>"
                                maxlength="50"
                                aria-invalid="<?= isset($errors['identifier']) ? 'true' : 'false' ?>"
                                required
                                autofocus>
                        </div>
                        <?php if (isset($errors['identifier'])): ?>
                            <p class="field-error"><?= htmlspecialchars($errors['identifier']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="field">
                        <label for="inputName">Nama Lengkap</label>
                        <div class="field-control <?= isset($errors['name']) ? 'has-error' : '' ?>">
                            <input
                                type="text"
                                id="inputName"
                                name="name"
                                placeholder="Nama lengkap sesuai identitas kampus"
                                value="<?= htmlspecialchars($old['name'] ?? '') ?>"
                                maxlength="255"
                                aria-invalid="<?= isset($errors['name']) ? 'true' : 'false' ?>"
                                required>
                        </div>
                        <?php if (isset($errors['name'])): ?>
                            <p class="field-error"><?= htmlspecialchars($errors['name']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="field">
                        <label for="inputEmail">Email Resmi Kampus</label>
                        <div class="field-control <?= isset($errors['email']) ? 'has-error' : '' ?>">
                            <input
                                type="email"
                                id="inputEmail"
                                name="email"
                                placeholder="nama@stu.pnj.ac.id atau nama@pnj.ac.id"
                                value="<?= htmlspecialchars($old['email'] ?? '') ?>"
                                maxlength="255"
                                autocomplete="email"
                                aria-invalid="<?= isset($errors['email']) ? 'true' : 'false' ?>"
                                required>
                        </div>
                        <p class="field-hint"></p>
                        <?php if (isset($errors['email'])): ?>
                            <p class="field-error"><?= htmlspecialchars($errors['email']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="field">
                        <label for="inputPassword">Password Baru (Min. 8 Karakter)</label>
                        <div class="field-control <?= isset($errors['password']) ? 'has-error' : '' ?>">
                            <input
                                type="password"
                                id="inputPassword"
                                name="password"
                                minlength="8"
                                maxlength="72"
                                placeholder="Minimal 8 karakter"
                                autocomplete="new-password"
                                aria-invalid="<?= isset($errors['password']) ? 'true' : 'false' ?>"
                                required>
                            <button
                                class="password-toggle"
                                type="button"
                                id="togglePasswordBtn"
                                aria-controls="inputPassword"
                                aria-pressed="false">Tampilkan</button>
                        </div>
                        <?php if (isset($errors['password'])): ?>
                            <p class="field-error"><?= htmlspecialchars($errors['password']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="field">
                        <label for="inputPasswordConfirm">Konfirmasi Password</label>
                        <div class="field-control <?= isset($errors['password_confirm']) ? 'has-error' : '' ?>">
                            <input
                                type="password"
                                id="inputPasswordConfirm"
                                name="password_confirm"
                                minlength="8"
                                maxlength="72"
                                placeholder="Ketik ulang password baru"
                                autocomplete="new-password"
                                aria-invalid="<?= isset($errors['password_confirm']) ? 'true' : 'false' ?>"
                                required>
                            <button
                                class="password-toggle"
                                type="button"
                                id="toggleConfirmBtn"
                                aria-controls="inputPasswordConfirm"
                                aria-pressed="false">Tampilkan</button>
                        </div>
                        <?php if (isset($errors['password_confirm'])): ?>
                            <p class="field-error"><?= htmlspecialchars($errors['password_confirm']) ?></p>
                        <?php endif; ?>
                    </div>

                    <button class="submit-button" type="submit" id="submitRegisterBtn">
                        <span class="submit-label">Verifikasi &amp; Aktifkan Akun</span>
                        <span class="submit-arrow" aria-hidden="true">&rarr;</span>
                    </button>
                </form>

                <p class="auth-switch">
                    Sudah memiliki akun aktif? <a href="<?= BASE_URL ?>/auth">Masuk ke SIGMA</a>
                </p>
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
        function bindPasswordToggle(btnId, inputId) {
            const btn = document.getElementById(btnId);
            const inp = document.getElementById(inputId);
            if (!btn || !inp) return;
            btn.addEventListener('click', function () {
                const show = inp.type === 'password';
                inp.type = show ? 'text' : 'password';
                btn.textContent = show ? 'Sembunyikan' : 'Tampilkan';
                btn.setAttribute('aria-pressed', String(show));
            });
        }

        bindPasswordToggle('togglePasswordBtn', 'inputPassword');
        bindPasswordToggle('toggleConfirmBtn', 'inputPasswordConfirm');

        const registerForm = document.getElementById('registerForm');
        const submitRegisterBtn = document.getElementById('submitRegisterBtn');
        registerForm.addEventListener('submit', function () {
            submitRegisterBtn.disabled = true;
            submitRegisterBtn.querySelector('.submit-label').textContent = 'Memverifikasi...';
            registerForm.setAttribute('aria-busy', 'true');
        });
    </script>
</body>
</html>

