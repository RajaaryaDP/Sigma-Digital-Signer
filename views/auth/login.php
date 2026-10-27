<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk — SIGMA Politeknik Negeri Jakarta</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --pnj-green: #064e3b;
            --pnj-green-hover: #043e2e;
            --bg-canvas: #f1f5f9;
        }

        body {
            background-color: var(--bg-canvas);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, Roboto, sans-serif;
            color: #1e293b;
            padding: 1.5rem;
        }

        .login-box {
            width: 100%;
            max-width: 420px;
        }

        .login-card {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
            padding: 2.25rem 2rem 2rem;
        }

        .brand-logo-area {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }

        .brand-icon-box {
            width: 44px;
            height: 44px;
            background: var(--pnj-green);
            color: #ffffff;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
        }

        .form-label {
            font-size: 0.82rem;
            font-weight: 600;
            color: #475569;
            margin-bottom: 0.35rem;
        }

        .form-control {
            border-radius: 8px;
            padding: 0.65rem 0.85rem;
            border: 1px solid #cbd5e1;
            font-size: 0.9rem;
        }

        .form-control:focus {
            border-color: var(--pnj-green);
            box-shadow: 0 0 0 0.2rem rgba(6, 78, 59, 0.15);
        }

        .btn-submit {
            background-color: var(--pnj-green);
            border-color: var(--pnj-green);
            color: #ffffff;
            font-weight: 600;
            padding: 0.7rem;
            border-radius: 8px;
            font-size: 0.92rem;
            transition: background-color 0.15s ease;
        }

        .btn-submit:hover {
            background-color: var(--pnj-green-hover);
            border-color: var(--pnj-green-hover);
            color: #ffffff;
        }

        /* Clean Accordion for Test Accounts */
        .test-account-details {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 0.6rem 0.85rem;
            margin-top: 1.25rem;
            font-size: 0.8rem;
        }

        .test-account-details summary {
            cursor: pointer;
            font-weight: 600;
            color: #475569;
            list-style: none;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .test-account-details summary::-webkit-details-marker {
            display: none;
        }

        .account-chip {
            cursor: pointer;
            border: 1px solid #cbd5e1;
            background: #ffffff;
            padding: 0.3rem 0.6rem;
            border-radius: 6px;
            font-size: 0.75rem;
            transition: all 0.15s ease;
        }

        .account-chip:hover {
            border-color: var(--pnj-green);
            background: #ecfdf5;
            color: var(--pnj-green);
        }
    </style>
</head>
<body>

    <div class="login-box">
        <div class="login-card">
            <!-- Brand Identity -->
            <div class="brand-logo-area">
                <div class="brand-icon-box">
                    <i class="bi bi-shield-shaded"></i>
                </div>
                <div>
                    <h5 class="fw-bold text-dark mb-0">SIGMA</h5>
                    <small class="text-muted">Politeknik Negeri Jakarta (PNJ)</small>
                </div>
            </div>

            <!-- Flash Message Banner -->
            <?php if (!empty($_SESSION['flash'])): ?>
                <?php 
                    $flash = $_SESSION['flash'];
                    $flashType = htmlspecialchars($flash['type'] ?? 'info');
                    unset($_SESSION['flash']);
                ?>
                <div class="alert alert-<?= $flashType ?> alert-dismissible fade show small py-2 d-flex align-items-center gap-2 mb-3" role="alert">
                    <i class="bi bi-info-circle-fill"></i>
                    <div><?= htmlspecialchars($flash['message'] ?? '') ?></div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- General Error Banner -->
            <?php if (!empty($errors['general'])): ?>
                <div class="alert alert-danger alert-dismissible fade show small py-2 d-flex align-items-center gap-2 mb-3" role="alert">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <div><?= htmlspecialchars($errors['general']) ?></div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form action="<?= BASE_URL ?>/auth/login" method="POST">
                <div class="mb-3">
                    <label class="form-label">Email Institusi</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-envelope"></i></span>
                        <input type="email" id="inputEmail" name="email" 
                               class="form-control border-start-0 <?= isset($errors['email']) ? 'is-invalid' : '' ?>" 
                               placeholder="nama@sigma.local"
                               value="<?= htmlspecialchars($old['email'] ?? 'mahasiswa@sigma.local') ?>" required autofocus>
                    </div>
                    <?php if (isset($errors['email'])): ?>
                        <div class="invalid-feedback d-block small mt-1"><?= htmlspecialchars($errors['email']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-4">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-lock"></i></span>
                        <input type="password" id="inputPassword" name="password" 
                               class="form-control border-start-0 border-end-0 <?= isset($errors['password']) ? 'is-invalid' : '' ?>" 
                               placeholder="••••••••" value="password" required>
                        <button class="btn btn-outline-secondary border-start-0" type="button" id="togglePasswordBtn">
                            <i class="bi bi-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                    <?php if (isset($errors['password'])): ?>
                        <div class="invalid-feedback d-block small mt-1"><?= htmlspecialchars($errors['password']) ?></div>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn-submit w-100 shadow-sm d-flex align-items-center justify-content-center gap-2">
                    <i class="bi bi-box-arrow-in-right"></i>
                    <span>Masuk ke Akun</span>
                </button>
            </form>

            <!-- Test Accounts Drawer (Discreet & Minimalist) -->
            <details class="test-account-details">
                <summary>
                    <span><i class="bi bi-key-fill text-muted me-1"></i> Akun Pengujian (Seed)</span>
                    <i class="bi bi-chevron-down text-muted" style="font-size: 0.75rem;"></i>
                </summary>
                <div class="pt-2">
                    <div class="d-flex flex-wrap gap-1.5 mb-1.5">
                        <span class="account-chip" onclick="fillAccount('admin@sigma.local', 'password')">Admin</span>
                        <span class="account-chip" onclick="fillAccount('mahasiswa@sigma.local', 'password')">Mahasiswa</span>
                        <span class="account-chip" onclick="fillAccount('dosen@sigma.local', 'password')">Dosen</span>
                        <span class="account-chip" onclick="fillAccount('kajur@sigma.local', 'password')">Kajur</span>
                        <span class="account-chip" onclick="fillAccount('tendik@sigma.local', 'password')">Tendik</span>
                    </div>
                    <div class="text-muted" style="font-size: 0.72rem;">
                        Password: <code>password</code> (Klik chip untuk mengisi otomatis)
                    </div>
                </div>
            </details>

            <!-- Public Verification Link -->
            <div class="text-center mt-3 pt-2">
                <a href="<?= BASE_URL ?>/verify" target="_blank" class="text-decoration-none small text-muted d-inline-flex align-items-center gap-1">
                    <i class="bi bi-qr-code-scan"></i>
                    <span>Verifikasi Keaslian Dokumen</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Script toggle password & fill account -->
    <script>
        function fillAccount(email, password) {
            document.getElementById('inputEmail').value = email;
            document.getElementById('inputPassword').value = password;
        }

        const toggleBtn = document.getElementById('togglePasswordBtn');
        const passwordInput = document.getElementById('inputPassword');
        const toggleIcon = document.getElementById('toggleIcon');

        toggleBtn.addEventListener('click', function() {
            const isPassword = passwordInput.getAttribute('type') === 'password';
            passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
            toggleIcon.className = isPassword ? 'bi bi-eye-slash' : 'bi bi-eye';
        });
    </script>

    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
