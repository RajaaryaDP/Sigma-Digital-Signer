<?php
/**
 * View: Halaman Utama (Landing Page)
 * Path: views/home/index.php
 *
 * Halaman utama SIGMA (Sistem Informasi & Tanda Tangan Digital)
 * Politeknik Negeri Jakarta (PNJ).
 */
require_once __DIR__ . '/../layout/header.php';
$isLoggedIn = isset($_SESSION['user']);
$user = $_SESSION['user'] ?? null;
?>

<!-- Hero Section (Minimalist & Focused) -->
<section class="landing-hero mb-4 text-center py-5">
    <div style="max-width: 640px; margin: 0 auto;">
        <h1 class="landing-title mb-2">
            SIGMA
        </h1>
        <p class="fs-5 text-light opacity-90 fw-normal mb-3">
            Sistem Informasi & Tanda Tangan Digital PNJ
        </p>
        <p class="landing-desc mx-auto mb-4" style="color: #d1fae5; font-size: 0.95rem;">
            Platform pengesahan dokumen akademik dan administratif terpadu untuk Civitas Akademika Politeknik Negeri Jakarta.
        </p>

        <div class="d-flex flex-wrap justify-content-center gap-2">
            <?php if ($isLoggedIn): ?>
                <a href="<?= BASE_URL ?>/documents" class="btn-landing-primary">
                    <i class="bi bi-folder2-open me-1"></i> Dashboard Dokumen
                </a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/auth" class="btn-landing-primary">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Masuk / Login
                </a>
            <?php endif; ?>
            <a href="<?= BASE_URL ?>/verify" class="btn-landing-secondary">
                <i class="bi bi-qr-code-scan me-1"></i> Verifikasi Dokumen
            </a>
        </div>
    </div>
</section>

<!-- Verifikasi Cepat Dokumen Publik -->
<section class="card-clean p-4 mb-4">
    <div class="row align-items-center g-3">
        <div class="col-lg-5">
            <h6 class="fw-bold text-dark mb-1">
                <i class="bi bi-qr-code-scan text-success me-1.5"></i>
                Verifikasi Keaslian Dokumen
            </h6>
            <p class="text-muted small mb-0">
                Periksa keabsahan berkas dengan memasukkan token 36 karakter dari QR Code.
            </p>
        </div>
        <div class="col-lg-7">
            <form action="<?= BASE_URL ?>/verify" method="GET" class="d-flex gap-2">
                <input type="text" name="token" class="form-control" 
                       placeholder="Masukkan token UUID (contoh: 550e8400-e29b-41d4-a716-446655440000)" 
                       pattern="[a-fA-F0-9-]{36}" title="Masukkan 36 karakter UUID token" required>
                <button type="submit" class="btn btn-pnj-primary px-3 text-nowrap">
                    <i class="bi bi-search me-1"></i> Periksa
                </button>
            </form>
        </div>
    </div>
</section>

<!-- Alur Kerja 3 Langkah Singkat -->
<section id="alur" class="mb-4">
    <div class="row g-3">
        <div class="col-md-4">
            <div class="card-clean p-3 h-100">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-success-subtle text-success border border-success-subtle">1</span>
                    <span class="fw-semibold text-dark">Unggah & Atur Slot</span>
                </div>
                <p class="text-muted small mb-0">
                    Pengaju mengunggah berkas PDF dan menentukan posisi tanda tangan pada lembar dokumen.
                </p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-clean p-3 h-100">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">2</span>
                    <span class="fw-semibold text-dark">Persetujuan Berjenjang</span>
                </div>
                <p class="text-muted small mb-0">
                    Penandatanganan diproses secara bertahap sesuai urutan pejabat atau dosen yang ditentukan.
                </p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-clean p-3 h-100">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-info-subtle text-info border border-info-subtle">3</span>
                    <span class="fw-semibold text-dark">Penerbitan & QR Sah</span>
                </div>
                <p class="text-muted small mb-0">
                    Dokumen final diterbitkan otomatis dengan stempel QR verifikasi dan tanda tangan sah.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Akun Uji Coba Demo (Khusus Evaluasi) -->
<section class="card-clean p-3 mb-4">
    <div class="d-flex align-items-center justify-content-between mb-2">
        <span class="fw-semibold text-dark small">
            <i class="bi bi-person-check text-muted me-1"></i>
            Akun Pengujian Demo
        </span>
        <span class="text-muted" style="font-size: 0.75rem;">Password semua akun: <code>password</code></span>
    </div>
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0" style="font-size: 0.82rem;">
            <thead class="table-light">
                <tr style="font-size: 0.75rem;">
                    <th>Peran</th>
                    <th>Email Institusi</th>
                    <th>Identitas (NIM/NIP)</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><span class="badge bg-secondary-subtle text-secondary">Administrator</span></td>
                    <td><code>admin@sigma.local</code></td>
                    <td class="text-muted">198501012010121001</td>
                    <td class="text-end">
                        <a href="<?= BASE_URL ?>/auth?email=admin%40sigma.local" class="btn btn-subtle btn-sm py-0 px-2" style="font-size: 0.75rem;">Login</a>
                    </td>
                </tr>
                <tr>
                    <td><span class="badge bg-success-subtle text-success">Mahasiswa</span></td>
                    <td><code>mahasiswa@sigma.local</code></td>
                    <td class="text-muted">2107411001</td>
                    <td class="text-end">
                        <a href="<?= BASE_URL ?>/auth?email=mahasiswa%40sigma.local" class="btn btn-subtle btn-sm py-0 px-2" style="font-size: 0.75rem;">Login</a>
                    </td>
                </tr>
                <tr>
                    <td><span class="badge bg-primary-subtle text-primary">Dosen</span></td>
                    <td><code>dosen@sigma.local</code></td>
                    <td class="text-muted">197505102000031002</td>
                    <td class="text-end">
                        <a href="<?= BASE_URL ?>/auth?email=dosen%40sigma.local" class="btn btn-subtle btn-sm py-0 px-2" style="font-size: 0.75rem;">Login</a>
                    </td>
                </tr>
                <tr>
                    <td><span class="badge bg-warning-subtle text-warning-emphasis">Ketua Jurusan</span></td>
                    <td><code>kajur@sigma.local</code></td>
                    <td class="text-muted">196803151993031001</td>
                    <td class="text-end">
                        <a href="<?= BASE_URL ?>/auth?email=kajur%40sigma.local" class="btn btn-subtle btn-sm py-0 px-2" style="font-size: 0.75rem;">Login</a>
                    </td>
                </tr>
                <tr>
                    <td><span class="badge bg-info-subtle text-info-emphasis">Tendik</span></td>
                    <td><code>tendik@sigma.local</code></td>
                    <td class="text-muted">198002202005012003</td>
                    <td class="text-end">
                        <a href="<?= BASE_URL ?>/auth?email=tendik%40sigma.local" class="btn btn-subtle btn-sm py-0 px-2" style="font-size: 0.75rem;">Login</a>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<?php
require_once __DIR__ . '/../layout/footer.php';
?>
