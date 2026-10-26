<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Keaslian Dokumen — SIGMA Politeknik Negeri Jakarta</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8fafc;
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, Roboto, sans-serif;
            color: #1e293b;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .verify-header {
            background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%);
            color: #fff;
            padding: 3rem 1.5rem 2.5rem;
            text-align: center;
        }
        .verify-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.06);
            border: 1px solid #e2e8f0;
            margin-top: -2rem;
            padding: 2.25rem 2rem;
        }
        .form-control:focus {
            border-color: #1d4ed8;
            box-shadow: 0 0 0 0.25rem rgba(29, 78, 216, 0.15);
        }
    </style>
</head>
<body>

    <header class="verify-header">
        <div class="container" style="max-width: 680px;">
            <div class="badge bg-primary bg-opacity-25 border border-primary-subtle text-light px-3 py-1.5 mb-2" style="font-size: 0.8rem;">
                <i class="bi bi-mortarboard-fill me-1"></i> Politeknik Negeri Jakarta
            </div>
            <h2 class="fw-bold mb-1">Verifikasi Dokumen Resmi</h2>
            <p class="text-white-50 small mb-0">Portal Publik Pemeriksaan Keabsahan Tanda Tangan & Integritas Dokumen Kampus (SIGMA)</p>
        </div>
    </header>

    <main class="container mb-5" style="max-width: 640px;">
        <div class="verify-card">
            <?php if (!empty($token)): ?>
                <!-- State Ada Token (Hasil Scan QR) -->
                <div class="text-center py-2">
                    <div class="rounded-circle bg-success-subtle text-success d-inline-flex p-3 mb-3">
                        <i class="bi bi-shield-check fs-1"></i>
                    </div>
                    <h4 class="fw-bold text-dark mb-1">Pemeriksaan Token QR</h4>
                    <p class="text-muted small mb-3">Token verifikasi berhasil diterima dari pemindaian dokumen fisik/digital:</p>
                    <div class="p-3 bg-light rounded-3 border text-break mb-4">
                        <code class="fw-bold text-primary fs-6"><?= htmlspecialchars($token) ?></code>
                    </div>
                    <div class="alert alert-info small py-2 d-flex align-items-center justify-content-center gap-2 mb-4" role="alert">
                        <i class="bi bi-info-circle-fill"></i>
                        <span>Modul parsing data keabsahan & biodata lengkap penandatangan akan diintegrasikan pada Task 8.</span>
                    </div>
                    <a href="<?= BASE_URL ?>/verify" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-left me-1"></i> Cek Token Lain
                    </a>
                </div>
            <?php else: ?>
                <!-- State Input Manual Token -->
                <div class="text-center mb-4">
                    <div class="rounded-circle bg-primary-subtle text-primary d-inline-flex p-3 mb-2">
                        <i class="bi bi-qr-code-scan fs-2"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">Masukkan Token Verifikasi</h5>
                    <p class="text-muted small mb-0">Ketikkan 36-karakter kode token yang tertera di samping QR Code dokumen resmi.</p>
                </div>

                <form action="<?= BASE_URL ?>/verify" method="GET">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Token Verifikasi Dokumen</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="bi bi-key-fill"></i></span>
                            <input type="text" name="token" class="form-control" placeholder="Contoh: 7d6c5b4a-3f2e-1d0c-..." required autofocus>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-2.5 fw-semibold d-flex align-items-center justify-content-center gap-2 shadow-sm" style="background-color: #1d4ed8; border-color: #1d4ed8; border-radius: 8px;">
                        <i class="bi bi-search"></i>
                        <span>Periksa Keaslian Dokumen</span>
                    </button>
                </form>
            <?php endif; ?>

            <div class="text-center mt-4 pt-3 border-top">
                <a href="<?= BASE_URL ?>/auth" class="text-decoration-none small text-secondary">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Masuk sebagai Civitas Kampus
                </a>
            </div>
        </div>
    </main>

    <footer class="mt-auto py-3 text-center text-muted small border-top bg-white">
        &copy; <?= date('Y') ?> SIGMA — Politeknik Negeri Jakarta. Seluruh hak cipta dilindungi.
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
