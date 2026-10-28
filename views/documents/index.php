<?php
require_once __DIR__ . '/../layout/header.php';

$isAdmin = ($user['role'] ?? '') === 'admin';
$civitasType = $user['civitas_type'] ?? 'civitas';

$statusBadges = [
    'draft'          => ['class' => 'bg-secondary', 'label' => 'Draft', 'icon' => 'bi-pencil'],
    'pending_review' => ['class' => 'bg-warning text-dark', 'label' => 'Menunggu Review', 'icon' => 'bi-hourglass-split'],
    'approved'       => ['class' => 'bg-info text-dark', 'label' => 'Disetujui', 'icon' => 'bi-check2-circle'],
    'signing'        => ['class' => 'bg-primary', 'label' => 'Proses TTD', 'icon' => 'bi-pen-fill'],
    'completed'      => ['class' => 'bg-success', 'label' => 'Selesai', 'icon' => 'bi-patch-check-fill'],
    'rejected'       => ['class' => 'bg-danger', 'label' => 'Ditolak', 'icon' => 'bi-x-octagon-fill'],
];
?>

<!-- Header Section (Clean & Minimalist) -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">
            <?= $isAdmin ? 'Dashboard Dokumen — Administrator' : 'Dokumen Digital Saya' ?>
        </h4>
        <p class="text-muted small mb-0">
            <?= $isAdmin 
                ? 'Kelola dan verifikasi seluruh pengajuan dokumen digital civitas Politeknik Negeri Jakarta.' 
                : 'Kelola pengajuan berkas akademik dan pembubuhan tanda tangan berjenjang kampus.' ?>
        </p>
    </div>

    <div>
        <?php if ($isAdmin): ?>
            <a href="<?= BASE_URL ?>/civitas" class="btn btn-subtle">
                <i class="bi bi-people-fill me-1 text-success"></i> Master Data Civitas
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
                    <div class="stat-val"><?= (int) ($docCounts['pending_review'] ?? 0) ?></div>
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
                    <div class="stat-val"><?= (int) ($docCounts['signing'] ?? 0) ?></div>
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
                    <div class="stat-val"><?= (int) ($docCounts['completed'] ?? 0) ?></div>
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
                    <div class="stat-val"><?= (int) ($pendingSignCount ?? 0) ?></div>
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
                    <div class="stat-val"><?= (int) ($docCounts['total'] ?? 0) ?></div>
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
                    <?php 
                        $inProgress = ($docCounts['pending_review'] ?? 0) + ($docCounts['approved'] ?? 0) + ($docCounts['signing'] ?? 0);
                    ?>
                    <div class="stat-val"><?= (int) $inProgress ?></div>
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
                    <div class="stat-val"><?= (int) ($docCounts['completed'] ?? 0) ?></div>
                    <div class="stat-label">Dokumen Selesai</div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Alert Tugas Penandatanganan Aktif (Jika Ada Dokumen Perlu TTD User) -->
<?php if (!$isAdmin && !empty($pendingSignDocs)): ?>
<div class="alert alert-warning py-3 px-3 mb-4 d-flex align-items-start gap-2.5" style="border-radius: var(--radius-md);">
    <i class="bi bi-exclamation-circle-fill text-warning fs-5 mt-0.5"></i>
    <div class="flex-grow-1">
        <h6 class="fw-bold mb-1 text-dark">Tugas Tanda Tangan Menunggu Giliran Anda</h6>
        <p class="mb-2 small text-dark">
            Terdapat <strong><?= count($pendingSignDocs) ?> dokumen</strong> yang saat ini aktif menunggu tanda tangan Anda sebagai penandatangan resmi.
        </p>
        <div class="d-flex flex-wrap gap-2">
            <?php foreach ($pendingSignDocs as $psd): ?>
                <a href="<?= BASE_URL ?>/documents/<?= htmlspecialchars($psd['id']) ?>" class="btn btn-warning btn-sm py-1 px-2.5 text-dark fw-semibold">
                    <i class="bi bi-pen me-1"></i> Tanda Tangani: <?= htmlspecialchars($psd['title']) ?> (Step <?= $psd['step_order'] ?>)
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Main Table / Document Queue Card -->
<div class="card-clean mb-4">
    <div class="card-clean-header flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <span class="fw-semibold text-dark">
                <i class="bi bi-table me-1.5 text-muted"></i>
                <?= $isAdmin ? 'Antrean Dokumen Masuk' : 'Daftar Pengajuan Dokumen' ?>
            </span>
            <span class="badge bg-light text-muted border">
                <?= count($documents) ?> Dokumen
            </span>
        </div>

        <!-- Filter Status Tab -->
        <div class="d-flex flex-wrap gap-1">
            <a href="<?= BASE_URL ?>/documents" 
               class="btn btn-sm <?= empty($statusFilter) ? 'btn-pnj-primary' : 'btn-subtle' ?>">
                Semua
            </a>
            <a href="<?= BASE_URL ?>/documents?status=draft" 
               class="btn btn-sm <?= ($statusFilter === 'draft') ? 'btn-pnj-primary' : 'btn-subtle' ?>">
                Draft
            </a>
            <a href="<?= BASE_URL ?>/documents?status=pending_review" 
               class="btn btn-sm <?= ($statusFilter === 'pending_review') ? 'btn-pnj-primary' : 'btn-subtle' ?>">
                Review Admin
            </a>
            <a href="<?= BASE_URL ?>/documents?status=signing" 
               class="btn btn-sm <?= ($statusFilter === 'signing') ? 'btn-pnj-primary' : 'btn-subtle' ?>">
                Proses TTD
            </a>
            <a href="<?= BASE_URL ?>/documents?status=completed" 
               class="btn btn-sm <?= ($statusFilter === 'completed') ? 'btn-pnj-primary' : 'btn-subtle' ?>">
                Selesai
            </a>
            <a href="<?= BASE_URL ?>/documents?status=rejected" 
               class="btn btn-sm <?= ($statusFilter === 'rejected') ? 'btn-pnj-primary' : 'btn-subtle' ?>">
                Ditolak
            </a>
        </div>
    </div>

    <?php if (empty($documents)): ?>
        <!-- Empty State Content -->
        <div class="p-5 text-center">
            <div class="text-muted mb-2" style="font-size: 2.5rem; opacity: 0.4;">
                <i class="bi bi-inbox"></i>
            </div>
            <h6 class="fw-semibold text-dark mb-1">
                <?= !empty($statusFilter) ? 'Tidak Ada Dokumen dengan Status Tersebut' : 'Belum Ada Dokumen Aktif' ?>
            </h6>
            <p class="text-muted small mb-3" style="max-width: 480px; margin: 0 auto;">
                <?= $isAdmin 
                    ? 'Seluruh pengajuan berkas dari mahasiswa atau staf akan muncul di antrean ini untuk diverifikasi.' 
                    : 'Mulai dengan mengunggah berkas PDF untuk diajukan secara berjenjang atau ditandatangani mandiri.' ?>
            </p>
            <?php if (!$isAdmin): ?>
                <a href="<?= BASE_URL ?>/documents/create" class="btn btn-pnj-primary btn-sm">
                    <i class="bi bi-plus-lg me-1"></i> Buat Pengajuan Baru
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <!-- Data Table -->
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr style="font-size: 0.75rem;">
                        <th class="text-center" style="width: 50px;">#</th>
                        <th>Judul Dokumen</th>
                        <?php if ($isAdmin): ?>
                            <th>Pengaju</th>
                        <?php endif; ?>
                        <th>Tipe Alur</th>
                        <th class="text-center">Status</th>
                        <th>Tanggal Unggah</th>
                        <th class="text-end" style="width: 140px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($documents as $idx => $doc): ?>
                        <?php 
                            $badge = $statusBadges[$doc['status']] ?? ['class' => 'bg-secondary', 'label' => ucfirst($doc['status']), 'icon' => 'bi-circle'];
                        ?>
                        <tr>
                            <td class="text-center text-muted small"><?= $idx + 1 ?></td>
                            <td>
                                <a href="<?= BASE_URL ?>/documents/<?= htmlspecialchars($doc['id']) ?>" class="fw-bold text-dark text-decoration-none d-block">
                                    <?= htmlspecialchars($doc['title']) ?>
                                </a>
                                <small class="text-muted">
                                    <i class="bi bi-file-earmark-pdf me-1 text-danger"></i>
                                    <?= htmlspecialchars($doc['file_name_original']) ?>
                                    (<?= number_format($doc['file_size'] / 1024, 1) ?> KB)
                                </small>
                            </td>
                            <?php if ($isAdmin): ?>
                                <td class="small">
                                    <i class="bi bi-person me-1 text-muted"></i>
                                    <?= htmlspecialchars($doc['uploader_name']) ?>
                                </td>
                            <?php endif; ?>
                            <td class="small">
                                <?php if ($doc['signature_type'] === 'single'): ?>
                                    <span class="badge bg-light text-dark border">
                                        <i class="bi bi-person-check me-1"></i> Mandiri
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-light text-primary border">
                                        <i class="bi bi-diagram-3 me-1"></i> Berjenjang (Step <?= $doc['current_step'] ?>)
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <span class="badge <?= $badge['class'] ?>" style="font-size: 0.75rem;">
                                    <i class="bi <?= $badge['icon'] ?> me-1"></i>
                                    <?= htmlspecialchars($badge['label']) ?>
                                </span>
                            </td>
                            <td class="small text-muted">
                                <?= date('d M Y, H:i', strtotime($doc['created_at'])) ?>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <a href="<?= BASE_URL ?>/documents/<?= htmlspecialchars($doc['id']) ?>" 
                                       class="btn btn-subtle btn-sm py-1 px-2" title="Lihat detail dokumen">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <?php if ($doc['status'] === 'draft' && !$isAdmin): ?>
                                        <a href="<?= BASE_URL ?>/documents/<?= htmlspecialchars($doc['id']) ?>/arrange" 
                                           class="btn btn-pnj-primary btn-sm py-1 px-2" title="Atur slot tanda tangan">
                                            <i class="bi bi-aspect-ratio"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
