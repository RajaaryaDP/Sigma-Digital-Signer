<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Masuk — SIGMA Digital Signer</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #1d4ed8;
            --primary-dark: #1e3a8a;
            --surface: #ffffff;
            --bg-page: #f1f5f9;
        }

        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, Roboto, sans-serif;
            padding: 1.5rem;
            color: #334155;
        }

        .login-wrapper {
            width: 100%;
            max-width: 440px;
        }

        .brand-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            padding: 0.4rem 0.9rem;
            border-radius: 999px;
            color: #93c5fd;
            font-size: 0.8rem;
            font-weight: 500;
            margin-bottom: 1.25rem;
        }

        .login-card {
            background: var(--surface);
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.35);
            border: 1px solid rgba(255, 255, 255, 0.05);
            padding: 2.25rem 2rem;
        }

        .app-logo-box {
            width: 52px;
            height: 52px;
            border-radius: 12px;
            background: linear-gradient(135deg, #1d4ed8, #2563eb);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            box-shadow: 0 4px 12px rgba(29, 78, 216, 0.35);
        }

        .form-control {
            border-radius: 8px;
            padding: 0.65rem 0.9rem;
            border: 1px solid #cbd5e1;
            font-size: 0.92rem;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(29, 78, 216, 0.15);
        }

        .btn-primary-action {
            background-color: var(--primary);
            border-color: var(--primary);
            padding: 0.75rem;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        .btn-primary-action:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
        }

        .test-accounts-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 1rem;
            margin-top: 1.5rem;
        }

        .account-chip {
            cursor: pointer;
            border-radius: 6px;
            border: 1px solid #cbd5e1;
            background: #fff;
            padding: 0.35rem 0.6rem;
            font-size: 0.75rem;
            transition: all 0.15s;
        }

        .account-chip:hover {
            border-color: var(--primary);
            background: #eff6ff;
            color: var(--primary);
        }
    </style>
</head>
<body>

    <div class="login-wrapper">
        <!-- Header / Logo Institusi -->
        <div class="text-center">
            <div class="brand-badge">
                <i class="bi bi-shield-lock-fill"></i>
                <span>Politeknik Negeri Jakarta</span>
            </div>
        </div>

        <div class="login-card">
            <!-- App Identity -->
            <div class="d-flex align-items-center gap-3 mb-4">
                <div class="app-logo-box">
                    <i class="bi bi-shield-shaded"></i>
                </div>
                <div>
                    <h4 class="fw-bold mb-0 text-dark">SIGMA</h4>
                    <p class="text-muted small mb-0">Sistem Tanda Tangan Digital Resmi</p>
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

            <!-- Form Login -->
            <form action="<?= BASE_URL ?>/auth/login" method="POST">
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-secondary">Email Institusi</label>
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
                    <label class="form-label small fw-semibold text-secondary">Password</label>
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

                <button type="submit" class="btn btn-primary btn-primary-action w-100 text-white shadow-sm d-flex align-items-center justify-content-center gap-2">
                    <i class="bi bi-box-arrow-in-right"></i>
                    <span>Masuk ke Sistem</span>
                </button>
            </form>

            <!-- Quick Account Switcher (Khusus Pengujian / Seeder) -->
            <div class="test-accounts-box">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="fw-semibold text-dark" style="font-size: 0.8rem;">
                        <i class="bi bi-person-badge text-primary me-1"></i> Pilih Akun Pengujian:
                    </span>
                    <small class="text-muted" style="font-size: 0.72rem;">Klik untuk isi form</small>
                </div>
                <div class="d-flex flex-wrap gap-1.5">
                    <span class="account-chip" onclick="fillAccount('admin@sigma.local', 'password')">
                        <strong>Admin</strong>
                    </span>
                    <span class="account-chip" onclick="fillAccount('mahasiswa@sigma.local', 'password')">
                        <strong>Mahasiswa</strong> (Pengaju)
                    </span>
                    <span class="account-chip" onclick="fillAccount('dosen@sigma.local', 'password')">
                        <strong>Dosen</strong> (Step 1)
                    </span>
                    <span class="account-chip" onclick="fillAccount('kajur@sigma.local', 'password')">
                        <strong>Kajur</strong> (Step 2)
                    </span>
                </div>
                <div class="text-muted mt-2" style="font-size: 0.75rem;">
                    Default password: <code class="text-primary">password</code>
                </div>
            </div>

            <!-- Tautan Verifikasi Terbuka -->
            <div class="text-center mt-3 pt-2 border-top border-light-subtle">
                <a href="<?= BASE_URL ?>/verify" target="_blank" class="text-decoration-none small text-secondary d-inline-flex align-items-center gap-1">
                    <i class="bi bi-qr-code-scan text-primary"></i>
                    <span>Verifikasi Keaslian Dokumen Tanpa Login</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Script toggle password & quick fill -->
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
