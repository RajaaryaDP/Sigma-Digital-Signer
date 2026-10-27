<?php
require_once __DIR__ . '/../layout/header.php';

$isAdmin = ($user['role'] ?? '') === 'admin';
$civitasType = $user['civitas_type'] ?? 'civitas';
?>

<!-- Header Section (Clean & Minimalist) -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">
            <?= $isAdmin ? 'Dashboard Dokumen — Administrator' : 'Dokumen Digital Saya' ?>
        </h4>
        <p class="text-muted small mb-0">
            Selamat datang di portal tanda tangan digital resmi Politeknik Negeri Jakarta.
        </p>
    </div>

    <div>
        <?php if ($isAdmin): ?>
            <a href="<?= BASE_URL ?>/civitas" class="btn btn-subtle">
                <i class="bi bi-people-fill me-1 text-success"></i> Kelola Civitas
            </a>
        <?php else: ?>
            <a href="<?= BASE_URL ?>/documents/create" class="btn btn-pnj-primary">
                <i class="bi bi-cloud-arrow-up me-1"></i> Ajukan Dokumen Baru
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Compact Stat Summary Cards -->
<div class="row g-3 mb-4">
    <?php if ($isAdmin): ?>
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: var(--status-amber-bg); color: var(--status-amber-text);">
                    <i class="bi bi-hourglass-split"></i>
                </div>
                <div>
                    <div class="stat-val">0</div>
                    <div class="stat-label">Menunggu Review</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: var(--pnj-green-50); color: var(--pnj-green-800);">
                    <i class="bi bi-person-vcard"></i>
                </div>
                <div>
                    <div class="stat-val"><?= (int) ($civitasCount ?? 0) ?></div>
                    <div class="stat-label">Civitas Terdaftar</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: var(--status-blue-bg); color: var(--status-blue-text);">
                    <i class="bi bi-arrow-repeat"></i>
                </div>
                <div>
                    <div class="stat-val">0</div>
                    <div class="stat-label">Proses Tanda Tangan</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: var(--status-green-bg); color: var(--status-green-text);">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div>
                    <div class="stat-val">0</div>
                    <div class="stat-label">Dokumen Selesai</div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: var(--status-amber-bg); color: var(--status-amber-text);">
                    <i class="bi bi-pen"></i>
                </div>
                <div>
                    <div class="stat-val">0</div>
                    <div class="stat-label">Perlu TTD Anda</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: var(--pnj-green-50); color: var(--pnj-green-800);">
                    <i class="bi bi-file-earmark-text"></i>
                </div>
                <div>
                    <div class="stat-val">0</div>
                    <div class="stat-label">Pengajuan Saya</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: var(--status-blue-bg); color: var(--status-blue-text);">
                    <i class="bi bi-hourglass"></i>
                </div>
                <div>
                    <div class="stat-val">0</div>
                    <div class="stat-label">Dalam Proses</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon" style="background: var(--status-green-bg); color: var(--status-green-text);">
                    <i class="bi bi-file-earmark-check"></i>
                </div>
                <div>
                    <div class="stat-val">0</div>
                    <div class="stat-label">Dokumen Selesai</div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Main Table / Document Queue Card -->
<div class="card-clean mb-4">
    <div class="card-clean-header">
        <span class="fw-semibold text-dark">
            <i class="bi bi-table me-1.5 text-muted"></i>
            <?= $isAdmin ? 'Antrean Dokumen Masuk' : 'Daftar Dokumen' ?>
        </span>
    </div>

    <!-- Empty State Content -->
    <div class="p-5 text-center">
        <div class="text-muted mb-2" style="font-size: 2.5rem; opacity: 0.4;">
            <i class="bi bi-inbox"></i>
        </div>
        <h6 class="fw-semibold text-dark mb-1">Belum Ada Dokumen Aktif</h6>
        <p class="text-muted small mb-3" style="max-width: 480px; margin: 0 auto;">
            <?= $isAdmin 
                ? 'Seluruh pengajuan berkas dari mahasiswa atau staf akan muncul di antrean ini untuk diverifikasi.' 
                : 'Mulai dengan mengunggah berkas PDF untuk ditandatangani mandiri atau diajukan secara berjenjang.' ?>
        </p>
        <?php if (!$isAdmin): ?>
            <a href="<?= BASE_URL ?>/documents/create" class="btn btn-pnj-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i> Buat Pengajuan Baru
            </a>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
