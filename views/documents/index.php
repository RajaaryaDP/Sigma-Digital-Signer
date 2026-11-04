<?php
require_once __DIR__ . '/../layout/header.php';

$isAdmin = ($user['role'] ?? '') === 'admin';
$civitasType = $user['civitas_type'] ?? 'civitas';

$statusBadges = [
    'draft'          => ['class' => 'bg-secondary-subtle text-secondary border border-secondary-subtle', 'label' => 'Draft'],
    'pending_review' => ['class' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle', 'label' => 'Review'],
    'approved'       => ['class' => 'bg-info-subtle text-info-emphasis border border-info-subtle', 'label' => 'Disetujui'],
    'signing'        => ['class' => 'bg-primary-subtle text-primary border border-primary-subtle', 'label' => 'Proses'],
    'completed'      => ['class' => 'bg-success-subtle text-success border border-success-subtle', 'label' => 'Selesai'],
    'rejected'       => ['class' => 'bg-danger-subtle text-danger border border-danger-subtle', 'label' => 'Ditolak'],
];
?>

<!-- Header -->
<div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-0">
            <?= $isAdmin ? 'Dashboard Dokumen' : 'Dokumen Saya' ?>
        </h4>
    </div>
    <div class="d-flex align-items-center gap-2">
        <?php if ($isAdmin): ?>
            <a href="<?= BASE_URL ?>/civitas" class="btn btn-subtle btn-sm">
                <i class="bi bi-people me-1 text-success"></i> Master Data Civitas
            </a>
        <?php else: ?>
            <a href="<?= BASE_URL ?>/documents/create" class="btn btn-pnj-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i> Ajukan Dokumen
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
    <?php if ($isAdmin): ?>
        <div class="col-6 col-md-3">
            <div class="card-clean p-3">
                <div class="text-muted small mb-1">Menunggu Review</div>
                <div class="h3 fw-bold text-dark mb-0"><?= (int) ($docCounts['pending_review'] ?? 0) ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card-clean p-3">
                <div class="text-muted small mb-1">Dalam Proses</div>
                <div class="h3 fw-bold text-dark mb-0"><?= (int) (($docCounts['signing'] ?? 0) + ($docCounts['approved'] ?? 0)) ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card-clean p-3">
                <div class="text-muted small mb-1">Selesai</div>
                <div class="h3 fw-bold text-dark mb-0"><?= (int) ($docCounts['completed'] ?? 0) ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card-clean p-3">
                <div class="text-muted small mb-1">Total Civitas</div>
                <div class="h3 fw-bold text-dark mb-0"><?= (int) ($civitasCount ?? 0) ?></div>
            </div>
        </div>
    <?php else: ?>
        <div class="col-6 col-md-3">
            <div class="card-clean p-3">
                <div class="text-muted small mb-1">Perlu TTD</div>
                <div class="h3 fw-bold <?= ($pendingSignCount ?? 0) > 0 ? 'text-warning' : 'text-dark' ?> mb-0"><?= (int) ($pendingSignCount ?? 0) ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card-clean p-3">
                <div class="text-muted small mb-1">Pengajuan Saya</div>
                <div class="h3 fw-bold text-dark mb-0"><?= (int) ($docCounts['total'] ?? 0) ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card-clean p-3">
                <?php 
                    $inProgress = ($docCounts['pending_review'] ?? 0) + ($docCounts['approved'] ?? 0) + ($docCounts['signing'] ?? 0);
                ?>
                <div class="text-muted small mb-1">Dalam Proses</div>
                <div class="h3 fw-bold text-dark mb-0"><?= (int) $inProgress ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card-clean p-3">
                <div class="text-muted small mb-1">Selesai</div>
                <div class="h3 fw-bold text-dark mb-0"><?= (int) ($docCounts['completed'] ?? 0) ?></div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Admin Notification: Pending Review -->
<?php if ($isAdmin && ($docCounts['pending_review'] ?? 0) > 0 && ($statusFilter !== 'pending_review')): ?>
    <div class="alert alert-warning py-2 px-3 mb-4 d-flex align-items-center justify-content-between small" style="border-radius: var(--radius-sm);">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-shield-exclamation text-warning"></i>
            <span>Ada <strong><?= (int)$docCounts['pending_review'] ?></strong> dokumen menunggu review.</span>
        </div>
        <a href="<?= BASE_URL ?>/documents?status=pending_review" class="btn btn-warning btn-sm py-0.5 px-2.5 text-dark fw-semibold" style="font-size: 0.8rem;">
            Tinjau &rarr;
        </a>
    </div>
<?php endif; ?>

<!-- User Notification: Pending Signature -->
<?php if (!$isAdmin && !empty($pendingSignDocs)): ?>
    <div class="card-clean p-3 mb-4 border-warning" style="background: #fffdf5;">
        <div class="small fw-semibold text-dark mb-2 d-flex align-items-center gap-1.5">
            <i class="bi bi-pen text-warning"></i>
            <span>Menunggu Tanda Tangan Anda (<?= count($pendingSignDocs) ?>)</span>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <?php foreach ($pendingSignDocs as $psd): ?>
                <a href="<?= BASE_URL ?>/documents/<?= htmlspecialchars($psd['id']) ?>/sign" class="btn btn-sm btn-warning text-dark fw-medium py-1 px-2.5" style="font-size: 0.82rem;">
                    <?= htmlspecialchars($psd['title']) ?> &rarr;
                </a>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<!-- Main Documents Table -->
<div class="card-clean mb-4">
    <div class="card-clean-header flex-wrap gap-2 justify-content-between align-items-center">
        <div class="fw-semibold text-dark">
            <?= $isAdmin ? 'Antrean Dokumen' : 'Daftar Dokumen' ?>
            <span class="text-muted fw-normal small ms-1">(<?= count($documents) ?>)</span>
        </div>

        <!-- Filter Status Tab -->
        <div class="d-flex flex-wrap gap-1">
            <?php
            $filters = [
                ''               => 'Semua',
                'draft'          => 'Draft',
                'pending_review' => 'Review',
                'signing'        => 'Proses',
                'completed'      => 'Selesai',
                'rejected'       => 'Ditolak',
            ];
            foreach ($filters as $key => $lbl):
                $active = ($statusFilter === $key || ($key === '' && empty($statusFilter)));
            ?>
                <a href="<?= BASE_URL ?>/documents<?= $key ? '?status=' . $key : '' ?>" 
                   class="btn btn-sm <?= $active ? 'btn-pnj-primary' : 'btn-subtle' ?> py-1 px-2.5" 
                   style="font-size: 0.78rem;">
                    <?= $lbl ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if (empty($documents)): ?>
        <div class="py-5 text-center text-muted">
            <i class="bi bi-inbox fs-2 opacity-50 d-block mb-2"></i>
            <p class="small mb-2"><?= !empty($statusFilter) ? 'Tidak ada dokumen dengan status ini.' : 'Belum ada dokumen.' ?></p>
            <?php if (!$isAdmin && empty($statusFilter)): ?>
                <a href="<?= BASE_URL ?>/documents/create" class="btn btn-pnj-primary btn-sm">
                    <i class="bi bi-plus-lg me-1"></i> Ajukan Dokumen
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr style="font-size: 0.75rem;">
                        <th class="text-center" style="width: 40px;">#</th>
                        <th>Judul Dokumen</th>
                        <?php if ($isAdmin): ?>
                            <th>Pengaju</th>
                        <?php endif; ?>
                        <th>Tipe</th>
                        <th class="text-center">Status</th>
                        <th>Tanggal</th>
                        <th class="text-end" style="width: 120px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($documents as $idx => $doc): ?>
                        <?php 
                            $badge = $statusBadges[$doc['status']] ?? ['class' => 'bg-secondary-subtle text-secondary border border-secondary-subtle', 'label' => ucfirst($doc['status'])];
                            $isPendingReviewAdmin = ($isAdmin && $doc['status'] === 'pending_review');
                        ?>
                        <tr class="<?= $isPendingReviewAdmin ? 'table-warning' : '' ?>">
                            <td class="text-center text-muted small"><?= $idx + 1 ?></td>
                            <td>
                                <a href="<?= BASE_URL ?>/documents/<?= htmlspecialchars($doc['id']) ?>" class="fw-semibold text-dark text-decoration-none">
                                    <?= htmlspecialchars($doc['title']) ?>
                                </a>
                                <div class="text-muted small" style="font-size: 0.75rem;">
                                    <?= htmlspecialchars($doc['file_name_original']) ?>
                                </div>
                            </td>
                            <?php if ($isAdmin): ?>
                                <td class="small text-muted">
                                    <?= htmlspecialchars($doc['uploader_name']) ?>
                                </td>
                            <?php endif; ?>
                            <td class="small text-muted">
                                <?= $doc['signature_type'] === 'single' ? 'Mandiri' : 'Berjenjang (Step ' . (int)$doc['current_step'] . ')' ?>
                            </td>
                            <td class="text-center">
                                <span class="badge <?= $badge['class'] ?> fw-normal" style="font-size: 0.72rem; padding: 0.3em 0.6em;">
                                    <?= htmlspecialchars($badge['label']) ?>
                                </span>
                            </td>
                            <td class="small text-muted">
                                <?= date('d/m/Y', strtotime($doc['created_at'])) ?>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <?php if ($isPendingReviewAdmin): ?>
                                        <a href="<?= BASE_URL ?>/documents/<?= htmlspecialchars($doc['id']) ?>" 
                                           class="btn btn-warning btn-sm py-0.5 px-2 text-dark fw-medium" style="font-size: 0.78rem;">
                                            Tinjau
                                        </a>
                                    <?php elseif ($doc['status'] === 'draft' && !$isAdmin): ?>
                                        <a href="<?= BASE_URL ?>/documents/<?= htmlspecialchars($doc['id']) ?>/arrange" 
                                           class="btn btn-subtle btn-sm py-0.5 px-2" style="font-size: 0.78rem;">
                                            Atur Slot
                                        </a>
                                    <?php else: ?>
                                        <a href="<?= BASE_URL ?>/documents/<?= htmlspecialchars($doc['id']) ?>" 
                                           class="btn btn-subtle btn-sm py-0.5 px-2" style="font-size: 0.78rem;">
                                            Detail
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($doc['status'] === 'completed'): ?>
                                        <a href="<?= BASE_URL ?>/documents/<?= htmlspecialchars($doc['id']) ?>/download" 
                                           class="btn btn-subtle btn-sm py-0.5 px-2 text-success" style="font-size: 0.78rem;" title="Unduh PDF">
                                            <i class="bi bi-download"></i>
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
