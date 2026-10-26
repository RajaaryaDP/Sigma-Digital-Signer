<?php
require_once __DIR__ . '/../layout/header.php';

$isAdmin = ($user['role'] ?? '') === 'admin';
$civitasType = $user['civitas_type'] ?? 'civitas';
$civitasBadgeColor = [
    'dosen'     => 'bg-primary-subtle text-primary border-primary-subtle',
    'mahasiswa' => 'bg-info-subtle text-info-emphasis border-info-subtle',
    'tendik'    => 'bg-secondary-subtle text-secondary border-secondary-subtle',
][$civitasType] ?? 'bg-light text-dark';
?>

<!-- Welcome Banner -->
<div class="card border-0 shadow-sm mb-4" style="background: linear-gradient(135deg, #1e3a8a 0%, #1d4ed8 100%); color: #fff; border-radius: 14px;">
    <div class="card-body p-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge <?= $isAdmin ? 'bg-warning text-dark' : 'bg-white text-primary' ?> px-2.5 py-1.5 fw-semibold text-uppercase" style="font-size: 0.75rem;">
                        <i class="bi <?= $isAdmin ? 'bi-shield-check' : 'bi-person-check-fill' ?> me-1"></i>
                        <?= $isAdmin ? 'Administrator' : ucfirst($civitasType) ?>
                    </span>
                    <?php if (!empty($user['identifier'])): ?>
                        <span class="badge bg-white bg-opacity-25 text-white px-2 py-1" style="font-size: 0.75rem;">
                            <?= htmlspecialchars($user['identifier']) ?>
                        </span>
                    <?php endif; ?>
                </div>
                <h3 class="fw-bold mb-1">Halo, <?= htmlspecialchars($user['name'] ?? 'Pengguna') ?>!</h3>
                <p class="mb-0 text-white-50" style="font-size: 0.95rem;">
                    <?= htmlspecialchars($user['position'] ?? ($isAdmin ? 'Pengelola Sistem SIGMA' : 'Civitas Politeknik Negeri Jakarta')) ?>
                    <?php if (!empty($user['department'])): ?>
                        &bull; <?= htmlspecialchars($user['department']) ?>
                    <?php endif; ?>
                </p>
            </div>
            <div class="d-flex gap-2">
                <?php if ($isAdmin): ?>
                    <a href="<?= BASE_URL ?>/civitas" class="btn btn-light text-primary fw-semibold d-inline-flex align-items-center gap-2 px-3 py-2 shadow-sm" style="border-radius: 8px;">
                        <i class="bi bi-people-fill"></i>
                        <span>Kelola Civitas</span>
                    </a>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/documents/create" class="btn btn-light text-primary fw-semibold d-inline-flex align-items-center gap-2 px-3 py-2 shadow-sm" style="border-radius: 8px;">
                        <i class="bi bi-cloud-arrow-up-fill"></i>
                        <span>Ajukan Dokumen</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Quick Statistics Cards -->
<div class="row g-3 mb-4">
    <?php if ($isAdmin): ?>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 12px;">
                <div class="card-body p-3.5">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-secondary small fw-medium">Antrean Review</span>
                        <div class="rounded-3 bg-warning-subtle text-warning p-2">
                            <i class="bi bi-hourglass-split fs-5"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold mb-1 text-dark">0</h3>
                    <div class="text-muted small">Dokumen menunggu review</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 12px;">
                <div class="card-body p-3.5">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-secondary small fw-medium">Master Civitas</span>
                        <div class="rounded-3 bg-primary-subtle text-primary p-2">
                            <i class="bi bi-person-badge-fill fs-5"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold mb-1 text-dark">5</h3>
                    <div class="text-muted small">Data civitas terdaftar</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 12px;">
                <div class="card-body p-3.5">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-secondary small fw-medium">Sedang Berjalan</span>
                        <div class="rounded-3 bg-info-subtle text-info p-2">
                            <i class="bi bi-arrow-repeat fs-5"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold mb-1 text-dark">0</h3>
                    <div class="text-muted small">Proses tanda tangan</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 12px;">
                <div class="card-body p-3.5">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-secondary small fw-medium">Dokumen Selesai</span>
                        <div class="rounded-3 bg-success-subtle text-success p-2">
                            <i class="bi bi-check-circle-fill fs-5"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold mb-1 text-dark">0</h3>
                    <div class="text-muted small">Telah ditandatangani final</div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 12px;">
                <div class="card-body p-3.5">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-secondary small fw-medium">Perlu TTD Anda</span>
                        <div class="rounded-3 bg-warning-subtle text-warning p-2">
                            <i class="bi bi-pen-fill fs-5"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold mb-1 text-dark">0</h3>
                    <div class="text-muted small">Giliran aktif saat ini</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 12px;">
                <div class="card-body p-3.5">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-secondary small fw-medium">Dokumen Saya</span>
                        <div class="rounded-3 bg-primary-subtle text-primary p-2">
                            <i class="bi bi-file-earmark-text-fill fs-5"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold mb-1 text-dark">0</h3>
                    <div class="text-muted small">Total pengajuan saya</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 12px;">
                <div class="card-body p-3.5">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-secondary small fw-medium">Dalam Proses</span>
                        <div class="rounded-3 bg-info-subtle text-info p-2">
                            <i class="bi bi-hourglass-split fs-5"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold mb-1 text-dark">0</h3>
                    <div class="text-muted small">Menunggu pihak lain</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 12px;">
                <div class="card-body p-3.5">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-secondary small fw-medium">Dokumen Selesai</span>
                        <div class="rounded-3 bg-success-subtle text-success p-2">
                            <i class="bi bi-file-earmark-check-fill fs-5"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold mb-1 text-dark">0</h3>
                    <div class="text-muted small">Siap diunduh / sah</div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Main Table Card / Empty State -->
<div class="card border-0 shadow-sm" style="border-radius: 12px;">
    <div class="card-header bg-white border-bottom border-light-subtle py-3 px-4 d-flex justify-content-between align-items-center">
        <h5 class="fw-bold mb-0 text-dark">
            <i class="bi bi-folder2-open me-2 text-primary"></i>
            <?= $isAdmin ? 'Antrean Dokumen Kampus' : 'Daftar Dokumen Anda' ?>
        </h5>
    </div>
    <div class="card-body p-5 text-center">
        <div class="py-4">
            <div class="mb-3">
                <i class="bi bi-inbox text-secondary text-opacity-50" style="font-size: 3.5rem;"></i>
            </div>
            <h5 class="fw-semibold text-dark mb-1">Belum Ada Dokumen</h5>
            <p class="text-muted small mb-3">
                <?= $isAdmin 
                    ? 'Belum ada pengajuan dokumen baru dari mahasiswa atau sivitas akademika.' 
                    : 'Anda belum memiliki dokumen yang sedang diproses atau perlu ditandatangani.' ?>
            </p>
            <?php if (!$isAdmin): ?>
                <a href="<?= BASE_URL ?>/documents/create" class="btn btn-primary d-inline-flex align-items-center gap-2 px-3.5 py-2 shadow-sm" style="border-radius: 8px;">
                    <i class="bi bi-plus-circle-fill"></i>
                    <span>Ajukan Dokumen Sekarang</span>
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
