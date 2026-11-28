<?php
require_once __DIR__ . '/../layout/header.php';

$isAdmin = (($user['role'] ?? '') === 'admin');
$isKajur = $isKajur ?? UsersModel::isKajur($user);
$activeScope = $activeScope ?? ($isAdmin ? 'campus' : ((($_GET['view'] ?? '') === 'all' || ($_GET['scope'] ?? '') === 'campus') ? 'campus' : 'mine'));
$isCampusScope = ($isAdmin || ($isKajur && $activeScope === 'campus'));
$statusFilter = $statusFilter ?? null;
$focusQueue = (!$isAdmin && (($_GET['view'] ?? '') === 'queue'));

$docCounts = $docCounts ?? [
    'draft' => 0,
    'pending_review' => 0,
    'approved' => 0,
    'signing' => 0,
    'completed' => 0,
    'rejected' => 0,
    'total' => 0,
];

$renderDocStatusBadge = static function (string $status, int $currentStep = 1): string {
    switch ($status) {
        case 'draft':
            return '<span class="badge-sigma badge-sigma-neutral"><i class="bi bi-pencil-square"></i> Draft</span>';
        case 'pending_review':
            return '<span class="badge-sigma badge-sigma-warning"><i class="bi bi-hourglass-split"></i> Menunggu Review</span>';
        case 'approved':
            return '<span class="badge-sigma badge-sigma-violet"><i class="bi bi-check2-circle"></i> Disetujui Admin</span>';
        case 'signing':
            return '<span class="badge-sigma badge-sigma-purple"><i class="bi bi-pen"></i> Proses TTD (Tahap ' . $currentStep . ')</span>';
        case 'completed':
            return '<span class="badge-sigma badge-sigma-success"><i class="bi bi-patch-check-fill"></i> Selesai (Sah)</span>';
        case 'rejected':
            return '<span class="badge-sigma badge-sigma-danger"><i class="bi bi-x-octagon"></i> Ditolak / Batal</span>';
        default:
            return '<span class="badge-sigma badge-sigma-neutral">' . htmlspecialchars($status, ENT_QUOTES, 'UTF-8') . '</span>';
    }
};

$renderSignerBadge = static function (string $myStatus, bool $isMyTurn): string {
    if ($isMyTurn) {
        return '<span class="badge-sigma badge-sigma-warning"><i class="bi bi-pen-fill"></i> Giliran Anda</span>';
    }
    switch ($myStatus) {
        case 'pending':
            return '<span class="badge-sigma badge-sigma-purple"><i class="bi bi-hourglass-top"></i> Siap Ditandatangani</span>';
        case 'waiting':
            return '<span class="badge-sigma badge-sigma-neutral"><i class="bi bi-clock"></i> Menunggu Urutan</span>';
        case 'signed':
            return '<span class="badge-sigma badge-sigma-success"><i class="bi bi-check-circle-fill"></i> Sudah TTD</span>';
        case 'declined':
            return '<span class="badge-sigma badge-sigma-danger"><i class="bi bi-x-circle-fill"></i> Ditolak</span>';
        default:
            return '<span class="badge-sigma badge-sigma-neutral">' . htmlspecialchars($myStatus, ENT_QUOTES, 'UTF-8') . '</span>';
    }
};
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h4 fw-bold mb-1" style="color: var(--text-heading);">
            <?php if ($isAdmin): ?>
                <?= $statusFilter === 'pending_review' ? 'Antrean Review Dokumen' : 'Dashboard Dokumen' ?>
            <?php elseif ($isKajur && $isCampusScope): ?>
                Daftar Seluruh Dokumen Kampus
            <?php else: ?>
                <?= $focusQueue ? 'Antrean Tanda Tangan Anda' : 'Dashboard' ?>
            <?php endif; ?>
        </h1>
        <p class="small mb-0" style="color: var(--text-muted);">
            <?php if ($isAdmin): ?>
                Pantau pengajuan dan antrean review.
            <?php elseif ($isKajur && $isCampusScope): ?>
                Memantau seluruh dokumen yang diajukan di lingkungan kampus.
            <?php else: ?>
                Halo, <strong style="color: var(--text-heading);"><?= htmlspecialchars($user['name'] ?? 'Pengguna', ENT_QUOTES, 'UTF-8') ?></strong>
            <?php endif; ?>
        </p>
    </div>

    <div class="d-flex flex-wrap align-items-center gap-2">
        <?php if ($isAdmin): ?>
            <?php if ($statusFilter !== 'pending_review'): ?>
                <a href="<?= BASE_URL ?>/documents?status=pending_review" class="btn-sigma-navy btn-sigma-sm">
                    <i class="bi bi-inboxes"></i> Antrean Review (<?= (int)($docCounts['pending_review'] ?? 0) ?>)
                </a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/documents" class="btn-sigma-outline btn-sigma-sm">
                    <i class="bi bi-grid"></i> Semua Dokumen
                </a>
            <?php endif; ?>
        <?php else: ?>
            <a href="<?= BASE_URL ?>/documents/create" class="btn-sigma-navy">
                <i class="bi bi-plus-lg"></i> Ajukan Dokumen Baru
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if ($isAdmin): ?>
    <!-- Stat Cards Admin -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <div class="stat-card-sigma">
                <div>
                    <div class="stat-val"><?= (int)($docCounts['pending_review'] ?? 0) ?></div>
                    <div class="stat-label">Menunggu Review</div>
                </div>
                <div class="stat-icon" style="background: var(--sigma-warning-bg); color: var(--sigma-warning-text);">
                    <i class="bi bi-hourglass-split"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card-sigma">
                <div>
                    <div class="stat-val"><?= (int)($docCounts['signing'] ?? 0) ?></div>
                    <div class="stat-label">Proses Tanda Tangan</div>
                </div>
                <div class="stat-icon" style="background: var(--sigma-tint-pill); color: var(--sigma-purple-700);">
                    <i class="bi bi-pen"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card-sigma">
                <div>
                    <div class="stat-val"><?= (int)($docCounts['completed'] ?? 0) ?></div>
                    <div class="stat-label">Dokumen Selesai</div>
                </div>
                <div class="stat-icon" style="background: var(--sigma-success-bg); color: var(--sigma-success-text);">
                    <i class="bi bi-patch-check-fill"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card-sigma">
                <div>
                    <div class="stat-val"><?= (int)($civitasCount ?? 0) ?></div>
                    <div class="stat-label">Total Biodata Civitas</div>
                </div>
                <div class="stat-icon" style="background: var(--sigma-tint-100); color: var(--sigma-navy);">
                    <i class="bi bi-people-fill"></i>
                </div>
            </div>
        </div>
    </div>

<?php elseif ($isKajur && $isCampusScope): ?>
    <!-- Stat Cards Kajur (Lingkup Seluruh Kampus) -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <div class="stat-card-sigma">
                <div>
                    <div class="stat-val"><?= (int)($docCounts['pending_review'] ?? 0) ?></div>
                    <div class="stat-label">Menunggu Review</div>
                </div>
                <div class="stat-icon" style="background: var(--sigma-warning-bg); color: var(--sigma-warning-text);">
                    <i class="bi bi-hourglass-split"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card-sigma">
                <div>
                    <div class="stat-val"><?= (int)($docCounts['signing'] ?? 0) ?></div>
                    <div class="stat-label">Proses Tanda Tangan</div>
                </div>
                <div class="stat-icon" style="background: var(--sigma-tint-pill); color: var(--sigma-purple-700);">
                    <i class="bi bi-pen"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card-sigma">
                <div>
                    <div class="stat-val"><?= (int)($docCounts['completed'] ?? 0) ?></div>
                    <div class="stat-label">Dokumen Selesai</div>
                </div>
                <div class="stat-icon" style="background: var(--sigma-success-bg); color: var(--sigma-success-text);">
                    <i class="bi bi-patch-check-fill"></i>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card-sigma">
                <div>
                    <div class="stat-val"><?= (int)($docCounts['total'] ?? 0) ?></div>
                    <div class="stat-label">Total Dokumen Kampus</div>
                </div>
                <div class="stat-icon" style="background: var(--sigma-tint-100); color: var(--sigma-navy);">
                    <i class="bi bi-files"></i>
                </div>
            </div>
        </div>
    </div>

    <?php if (!$focusQueue && !empty($pendingSignDocs)): ?>
        <div class="card-sigma mb-4" style="border-color: #d8cceb; background: var(--sigma-tint-50);">
            <div class="card-sigma-body py-3">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge-sigma badge-sigma-warning"><i class="bi bi-bell-fill"></i> Giliran Anda</span>
                        <span class="fw-bold small" style="color: var(--text-heading);">
                            <?= count($pendingSignDocs) ?> dokumen menunggu tanda tangan Anda
                        </span>
                    </div>
                    <?php if (!$focusQueue): ?>
                        <a href="<?= BASE_URL ?>/documents?view=queue" class="small fw-semibold text-decoration-none" style="color: var(--sigma-purple-700);">
                            Lihat semua antrean <i class="bi bi-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
                <div class="d-flex flex-wrap gap-2 mt-2">
                    <?php foreach (array_slice($pendingSignDocs, 0, 4) as $pDoc): ?>
                        <div class="d-flex align-items-center justify-content-between gap-3 bg-white border rounded-3 px-3 py-2 flex-grow-1" style="border-color: var(--border-subtle) !important; min-width: 240px;">
                            <div class="text-truncate small fw-semibold" style="color: var(--text-heading);" title="<?= htmlspecialchars($pDoc['title'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                <i class="bi bi-file-earmark-pdf text-danger me-1"></i>
                                <?= htmlspecialchars($pDoc['title'] ?? 'Dokumen', ENT_QUOTES, 'UTF-8') ?>
                            </div>
                            <a href="<?= BASE_URL ?>/documents/<?= htmlspecialchars($pDoc['id'], ENT_QUOTES, 'UTF-8') ?>/sign" class="btn-sigma-navy btn-sigma-sm flex-shrink-0">
                                <i class="bi bi-pen"></i> Tanda Tangani
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

<?php else: ?>
    <?php
    $pendingSignCountVal = (int)($pendingSignCount ?? 0);
    $waitingCountVal = count($waitingSignDocs ?? []);
    $ongoingMyDocsCount = (int)($docCounts['draft'] ?? 0) + (int)($docCounts['pending_review'] ?? 0) + (int)($docCounts['signing'] ?? 0);
    ?>
    <?php if (!$focusQueue): ?>
        <!-- Stat Cards User / Civitas -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-xl-3">
                <div class="stat-card-sigma" <?= $pendingSignCountVal > 0 ? 'style="border-color: #f59e0b;"' : '' ?>>
                    <div>
                        <div class="stat-val"><?= $pendingSignCountVal ?></div>
                        <div class="stat-label">Perlu TTD Saya</div>
                    </div>
                    <div class="stat-icon" style="background: var(--sigma-warning-bg); color: var(--sigma-warning-text);">
                        <i class="bi bi-pen-fill"></i>
                    </div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="stat-card-sigma">
                    <div>
                        <div class="stat-val"><?= $waitingCountVal ?></div>
                        <div class="stat-label">Menunggu Giliran</div>
                    </div>
                    <div class="stat-icon" style="background: var(--sigma-tint-pill); color: var(--sigma-purple-700);">
                        <i class="bi bi-clock-history"></i>
                    </div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="stat-card-sigma">
                    <div>
                        <div class="stat-val"><?= $ongoingMyDocsCount ?></div>
                        <div class="stat-label">Pengajuan Berjalan</div>
                    </div>
                    <div class="stat-icon" style="background: #ede9fe; color: var(--sigma-info-text);">
                        <i class="bi bi-send-check"></i>
                    </div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="stat-card-sigma">
                    <div>
                        <div class="stat-val"><?= (int)($docCounts['completed'] ?? 0) ?></div>
                        <div class="stat-label">Dokumen Selesai</div>
                    </div>
                    <div class="stat-icon" style="background: var(--sigma-success-bg); color: var(--sigma-success-text);">
                        <i class="bi bi-patch-check-fill"></i>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!$focusQueue && !empty($pendingSignDocs)): ?>
        <div class="card-sigma mb-4" style="border-color: #d8cceb; background: var(--sigma-tint-50);">
            <div class="card-sigma-body py-3">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge-sigma badge-sigma-warning"><i class="bi bi-bell-fill"></i> Giliran Anda</span>
                        <span class="fw-bold small" style="color: var(--text-heading);">
                            <?= count($pendingSignDocs) ?> dokumen menunggu tanda tangan Anda
                        </span>
                    </div>
                    <?php if (!$focusQueue): ?>
                        <a href="<?= BASE_URL ?>/documents?view=queue" class="small fw-semibold text-decoration-none" style="color: var(--sigma-purple-700);">
                            Lihat semua antrean <i class="bi bi-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
                <div class="d-flex flex-wrap gap-2 mt-2">
                    <?php foreach (array_slice($pendingSignDocs, 0, 4) as $pDoc): ?>
                        <div class="d-flex align-items-center justify-content-between gap-3 bg-white border rounded-3 px-3 py-2 flex-grow-1" style="border-color: var(--border-subtle) !important; min-width: 240px;">
                            <div class="text-truncate small fw-semibold" style="color: var(--text-heading);" title="<?= htmlspecialchars($pDoc['title'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                <i class="bi bi-file-earmark-pdf text-danger me-1"></i>
                                <?= htmlspecialchars($pDoc['title'] ?? 'Dokumen', ENT_QUOTES, 'UTF-8') ?>
                            </div>
                            <a href="<?= BASE_URL ?>/documents/<?= htmlspecialchars($pDoc['id'], ENT_QUOTES, 'UTF-8') ?>/sign" class="btn-sigma-navy btn-sigma-sm flex-shrink-0">
                                <i class="bi bi-pen"></i> Tanda Tangani
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

<?php endif; ?>

<?php
// Fungsi render blok Penugasan & Antrean TTD (untuk User Non-Admin)
$assignedDocs = $assignedDocs ?? [];
$renderAssignedQueueBlock = function () use ($user, $assignedDocs, $renderDocStatusBadge, $renderSignerBadge): void {
    $assignedList = $assignedDocs;
    $currentUserId = (string)($user['id'] ?? '');
    ?>
    <div class="card-sigma mb-4" id="assignedQueueSection">
        <div class="card-sigma-header flex-wrap">
            <div>
                <h2 class="h6 fw-bold mb-0" style="color: var(--text-heading);">
                    <i class="bi bi-vector-pen me-1 text-sigma-purple"></i> Antrean Tanda Tangan Saya
                </h2>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <div class="filter-chips-sigma" id="queueFilterChips">
                    <button type="button" class="filter-chip-sigma active" data-queue-filter="all">Semua</button>
                    <button type="button" class="filter-chip-sigma" data-queue-filter="turn">Giliran Saya</button>
                    <button type="button" class="filter-chip-sigma" data-queue-filter="waiting">Menunggu</button>
                    <button type="button" class="filter-chip-sigma" data-queue-filter="signed">Selesai TTD</button>
                </div>
            </div>
        </div>

        <?php if (empty($assignedList)): ?>
            <div class="card-sigma-body text-center py-5">
                <div class="mx-auto mb-3 d-flex align-items-center justify-content-center rounded-circle"
                     style="width: 56px; height: 56px; background: var(--sigma-tint-100); color: var(--sigma-purple-600); font-size: 1.4rem;">
                    <i class="bi bi-inbox"></i>
                </div>
                <h3 class="h6 fw-bold mb-1" style="color: var(--text-heading);">Belum Ada Tugas Tanda Tangan</h3>
                <p class="small mb-0" style="color: var(--text-muted);">
                    Belum ada dokumen yang perlu Anda tanda tangani.
                </p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table-sigma" id="assignedQueueTable">
                    <thead>
                        <tr>
                            <th>Dokumen PDF</th>
                            <th>Pengaju</th>
                            <th>Peran & Urutan</th>
                            <th>Status Anda</th>
                            <th>Status Dokumen</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($assignedList as $aDoc):
                            $mySigStat = (string)($aDoc['my_signer_status'] ?? 'waiting');
                            $docStat = (string)($aDoc['status'] ?? '');
                            $stepOrd = (int)($aDoc['step_order'] ?? 0);
                            $curStep = (int)($aDoc['current_step'] ?? 0);
                            $isMyTurn = ($docStat === 'signing' && $mySigStat === 'pending' && $stepOrd === $curStep);

                            $rowCat = 'waiting';
                            if ($isMyTurn) {
                                $rowCat = 'turn';
                            } elseif ($mySigStat === 'signed') {
                                $rowCat = 'signed';
                            } elseif ($docStat === 'rejected' || $mySigStat === 'declined') {
                                $rowCat = 'rejected';
                            }
                            ?>
                            <tr data-queue-cat="<?= htmlspecialchars($rowCat, ENT_QUOTES, 'UTF-8') ?>">
                                <td>
                                    <div class="fw-semibold" style="color: var(--text-heading);">
                                        <?= htmlspecialchars($aDoc['title'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                    <div class="small text-truncate" style="max-width: 260px; color: var(--text-muted);">
                                        <i class="bi bi-file-earmark-pdf text-danger me-1"></i>
                                        <?= htmlspecialchars($aDoc['file_name_original'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-medium"><?= htmlspecialchars($aDoc['uploader_name'] ?? '-', ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php if (($aDoc['uploader_id'] ?? '') === $currentUserId && ($aDoc['signature_type'] ?? '') === 'single'): ?>
                                        <span class="badge-sigma badge-sigma-neutral mt-1" style="font-size: 0.68rem;">Dokumen Mandiri</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="small fw-semibold" style="color: var(--text-heading);">
                                        <?= htmlspecialchars($aDoc['role_title'] ?? 'Penandatangan', ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                    <div class="small" style="color: var(--text-muted);">
                                        <?php if (($aDoc['signature_type'] ?? '') === 'single'): ?>
                                            Tanda tangan mandiri
                                        <?php else: ?>
                                            Tahap <?= $stepOrd ?> · aktif: <?= $curStep ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <?= $renderSignerBadge($mySigStat, $isMyTurn) ?>
                                    <?php if (!empty($aDoc['my_signed_at'])): ?>
                                        <div class="small mt-1" style="color: var(--text-muted); font-size: 0.72rem;">
                                            <?= htmlspecialchars(date('d M Y H:i', strtotime((string)$aDoc['my_signed_at'])), ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= $renderDocStatusBadge($docStat, $curStep) ?>
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex flex-wrap justify-content-end gap-1">
                                        <?php if ($isMyTurn): ?>
                                            <a href="<?= BASE_URL ?>/documents/<?= htmlspecialchars($aDoc['id'], ENT_QUOTES, 'UTF-8') ?>/sign" class="btn-sigma-navy btn-sigma-sm">
                                                <i class="bi bi-pen"></i> Tanda Tangani
                                            </a>
                                        <?php endif; ?>
                                        <a href="<?= BASE_URL ?>/documents/<?= htmlspecialchars($aDoc['id'], ENT_QUOTES, 'UTF-8') ?>#pdfPreviewSection" class="btn-sigma-outline btn-sigma-sm">
                                            <?= $mySigStat === 'signed' ? 'Pratinjau TTD' : 'Detail & Slot' ?>
                                        </a>
                                        <?php if ($docStat === 'completed'): ?>
                                            <a href="<?= BASE_URL ?>/documents/<?= htmlspecialchars($aDoc['id'], ENT_QUOTES, 'UTF-8') ?>/download" class="btn-sigma-purple btn-sigma-sm" title="Unduh PDF Final">
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
    <?php
};

// Fungsi render tabel utama Dokumen (Pengajuan User / Manajemen Admin / Kajur)
$renderMainDocumentsBlock = function () use ($isAdmin, $isKajur, $isCampusScope, $activeScope, $documents, $docCounts, $statusFilter, $renderDocStatusBadge, $user): void {
    $docList = $documents ?? [];
    $buildTabUrl = function (?string $stKey) use ($isAdmin, $isKajur, $isCampusScope, $activeScope): string {
        $params = [];
        if (!$isAdmin && $isKajur && $isCampusScope) {
            $params['view'] = 'all';
        } elseif (!$isAdmin && $isKajur && $activeScope === 'mine') {
            $params['scope'] = 'mine';
        }
        if ($stKey !== null) {
            $params['status'] = $stKey;
        }
        $qs = http_build_query($params);
        return BASE_URL . '/documents' . ($qs !== '' ? '?' . $qs : '');
    };
    $tabs = [
        ['key' => null, 'label' => 'Semua', 'count' => (int)($docCounts['total'] ?? 0), 'url' => $buildTabUrl(null)],
        ['key' => 'pending_review', 'label' => 'Menunggu Review', 'count' => (int)($docCounts['pending_review'] ?? 0), 'url' => $buildTabUrl('pending_review')],
        ['key' => 'signing', 'label' => 'Sedang TTD', 'count' => (int)($docCounts['signing'] ?? 0), 'url' => $buildTabUrl('signing')],
        ['key' => 'completed', 'label' => 'Selesai', 'count' => (int)($docCounts['completed'] ?? 0), 'url' => $buildTabUrl('completed')],
        ['key' => 'draft', 'label' => 'Draft', 'count' => (int)($docCounts['draft'] ?? 0), 'url' => $buildTabUrl('draft')],
        ['key' => 'rejected', 'label' => 'Ditolak', 'count' => (int)($docCounts['rejected'] ?? 0), 'url' => $buildTabUrl('rejected')],
    ];
    ?>
    <div class="card-sigma mb-4">
        <div class="card-sigma-header flex-wrap gap-2">
            <div>
                <h2 class="h6 fw-bold mb-0" style="color: var(--text-heading);">
                    <i class="bi bi-files me-1 text-sigma-purple"></i>
                    <?= $isCampusScope ? 'Daftar Seluruh Dokumen Kampus' : 'Dokumen Pengajuan Saya' ?>
                </h2>
            </div>

            <?php if ($isKajur): ?>
                <div class="d-flex align-items-center gap-1 bg-light p-1 rounded-2 border" style="border-color: var(--border-subtle) !important;">
                    <a href="<?= BASE_URL ?>/documents?scope=mine<?= $statusFilter ? '&status=' . urlencode($statusFilter) : '' ?>"
                       class="btn-sigma-sm text-decoration-none px-3 py-1 rounded-2 <?= !$isCampusScope ? 'btn-sigma-navy' : 'text-muted' ?>"
                       style="font-size: 0.78rem;">
                        <i class="bi bi-person me-1"></i> Pengajuan Saya
                    </a>
                    <a href="<?= BASE_URL ?>/documents?view=all<?= $statusFilter ? '&status=' . urlencode($statusFilter) : '' ?>"
                       class="btn-sigma-sm text-decoration-none px-3 py-1 rounded-2 <?= $isCampusScope ? 'btn-sigma-navy' : 'text-muted' ?>"
                       style="font-size: 0.78rem;">
                        <i class="bi bi-buildings me-1"></i> Seluruh Kampus
                    </a>
                </div>
            <?php endif; ?>

            <div class="sigma-search-box ms-md-auto" style="min-width: 240px;">
                <i class="bi bi-search sigma-search-icon"></i>
                <input type="search" id="docTableSearchInput" class="form-control form-control-sm sigma-input" placeholder="Cari judul, file, atau pengaju..." aria-label="Cari dokumen">
            </div>
        </div>

        <div class="px-3 pt-1" style="background: var(--surface);">
            <ul class="nav-tabs-sigma">
                <?php foreach ($tabs as $tab):
                    $active = ($statusFilter === $tab['key']);
                    ?>
                    <li>
                        <a href="<?= htmlspecialchars($tab['url'], ENT_QUOTES, 'UTF-8') ?>"
                           class="nav-tab-item <?= $active ? 'active' : '' ?>">
                            <span><?= htmlspecialchars($tab['label'], ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="badge-sigma <?= $active ? 'badge-sigma-purple' : 'badge-sigma-neutral' ?> py-0 px-2" style="font-size: 0.68rem;">
                                <?= $tab['count'] ?>
                            </span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <?php if (empty($docList)): ?>
            <div class="card-sigma-body text-center py-5">
                <div class="mx-auto mb-3 d-flex align-items-center justify-content-center rounded-circle"
                     style="width: 56px; height: 56px; background: var(--sigma-tint-100); color: var(--sigma-purple-600); font-size: 1.4rem;">
                    <i class="bi bi-file-earmark-text"></i>
                </div>
                <h3 class="h6 fw-bold mb-1" style="color: var(--text-heading);">Tidak Ada Dokumen</h3>
                <p class="small mb-3" style="color: var(--text-muted);">
                    <?= $statusFilter ? 'Belum ada dokumen dengan status filter ini.' : ($isCampusScope ? 'Belum ada pengajuan dokumen yang masuk ke sistem.' : 'Mulai dengan mengunggah berkas PDF pertama Anda.') ?>
                </p>
                <?php if (!$isAdmin): ?>
                    <a href="<?= BASE_URL ?>/documents/create" class="btn-sigma-navy btn-sigma-sm">
                        <i class="bi bi-plus-lg"></i> Ajukan Dokumen Baru
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table-sigma" id="mainDocumentsTable">
                    <thead>
                        <tr>
                            <th>Judul & Berkas PDF</th>
                            <?php if ($isAdmin || $isCampusScope): ?>
                                <th>Pengaju</th>
                            <?php endif; ?>
                            <th>Jenis Alur</th>
                            <th>Status</th>
                            <th>Tanggal Diajukan</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($docList as $doc):
                            $docId = (string)($doc['id'] ?? '');
                            $docStatus = (string)($doc['status'] ?? 'draft');
                            $curStep = (int)($doc['current_step'] ?? 1);
                            $sigType = (string)($doc['signature_type'] ?? 'sequential');
                            $searchHaystack = mb_strtolower(($doc['title'] ?? '') . ' ' . ($doc['file_name_original'] ?? '') . ' ' . ($doc['uploader_name'] ?? ''));
                            ?>
                            <tr data-search="<?= htmlspecialchars($searchHaystack, ENT_QUOTES, 'UTF-8') ?>">
                                <td>
                                    <a href="<?= BASE_URL ?>/documents/<?= htmlspecialchars($docId, ENT_QUOTES, 'UTF-8') ?>"
                                       class="fw-semibold text-decoration-none d-block" style="color: var(--text-heading);">
                                        <?= htmlspecialchars($doc['title'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                    <div class="small text-truncate" style="max-width: 280px; color: var(--text-muted);">
                                        <i class="bi bi-file-earmark-pdf text-danger me-1"></i>
                                        <?= htmlspecialchars($doc['file_name_original'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                </td>
                                <?php if ($isAdmin || $isCampusScope): ?>
                                    <td>
                                        <div class="fw-medium"><?= htmlspecialchars($doc['uploader_name'] ?? '-', ENT_QUOTES, 'UTF-8') ?></div>
                                    </td>
                                <?php endif; ?>
                                <td>
                                    <?php if ($sigType === 'single'): ?>
                                        <span class="badge-sigma badge-sigma-neutral"><i class="bi bi-person"></i> Tunggal</span>
                                    <?php else: ?>
                                        <span class="badge-sigma badge-sigma-purple"><i class="bi bi-diagram-3"></i> Berjenjang</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= $renderDocStatusBadge($docStatus, $curStep) ?>
                                </td>
                                <td>
                                    <span class="small" style="color: var(--text-muted);">
                                        <?= !empty($doc['created_at']) ? htmlspecialchars(date('d M Y, H:i', strtotime((string)$doc['created_at'])), ENT_QUOTES, 'UTF-8') : '-' ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex flex-wrap justify-content-end gap-1">
                                        <?php if ($isAdmin): ?>
                                            <?php if ($docStatus === 'pending_review'): ?>
                                                <a href="<?= BASE_URL ?>/documents/<?= htmlspecialchars($docId, ENT_QUOTES, 'UTF-8') ?>" class="btn-sigma-navy btn-sigma-sm">
                                                    <i class="bi bi-clipboard-check"></i> Tinjau
                                                </a>
                                            <?php else: ?>
                                                <a href="<?= BASE_URL ?>/documents/<?= htmlspecialchars($docId, ENT_QUOTES, 'UTF-8') ?>" class="btn-sigma-outline btn-sigma-sm">
                                                    Detail
                                                </a>
                                            <?php endif; ?>
                                        <?php elseif ($isCampusScope): ?>
                                            <a href="<?= BASE_URL ?>/documents/<?= htmlspecialchars($docId, ENT_QUOTES, 'UTF-8') ?>" class="btn-sigma-outline btn-sigma-sm">
                                                Detail
                                            </a>
                                            <?php if (($doc['uploader_id'] ?? '') === ($user['id'] ?? '') && $docStatus === 'draft'): ?>
                                                <a href="<?= BASE_URL ?>/documents/<?= htmlspecialchars($docId, ENT_QUOTES, 'UTF-8') ?>/arrange" class="btn-sigma-navy btn-sigma-sm">
                                                    <i class="bi bi-bounding-box"></i> Atur Slot
                                                </a>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <?php if ($docStatus === 'draft'): ?>
                                                <a href="<?= BASE_URL ?>/documents/<?= htmlspecialchars($docId, ENT_QUOTES, 'UTF-8') ?>/arrange" class="btn-sigma-navy btn-sigma-sm">
                                                    <i class="bi bi-bounding-box"></i> Atur Slot
                                                </a>
                                                <form action="<?= BASE_URL ?>/documents/<?= htmlspecialchars($docId, ENT_QUOTES, 'UTF-8') ?>/delete" method="POST" class="d-inline"
                                                      onsubmit="return confirm('Batalkan dan hapus draft dokumen ini?');">
                                                    <?= Csrf::field() ?>
                                                    <input type="hidden" name="status_filter" value="<?= htmlspecialchars((string)($statusFilter ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                                    <button type="submit" class="btn-sigma-outline btn-sigma-sm text-danger" title="Hapus Draft">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <?php if ($sigType === 'single' && $docStatus === 'signing'): ?>
                                                    <a href="<?= BASE_URL ?>/documents/<?= htmlspecialchars($docId, ENT_QUOTES, 'UTF-8') ?>/sign" class="btn-sigma-navy btn-sigma-sm">
                                                        <i class="bi bi-pen"></i> Tanda Tangani
                                                    </a>
                                                <?php endif; ?>
                                                <a href="<?= BASE_URL ?>/documents/<?= htmlspecialchars($docId, ENT_QUOTES, 'UTF-8') ?>" class="btn-sigma-outline btn-sigma-sm">
                                                    Detail
                                                </a>
                                                <?php if ($docStatus === 'pending_review'): ?>
                                                    <form action="<?= BASE_URL ?>/documents/<?= htmlspecialchars($docId, ENT_QUOTES, 'UTF-8') ?>/withdraw" method="POST" class="d-inline"
                                                          onsubmit="return confirm('Tarik dokumen ini ke Draft?');">
                                                        <?= Csrf::field() ?>
                                                        <button type="submit" class="btn-sigma-outline btn-sigma-sm" title="Tarik ke Draft">
                                                            <i class="bi bi-arrow-counterclockwise"></i> Tarik
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        <?php endif; ?>

                                        <?php if ($docStatus === 'completed'): ?>
                                            <a href="<?= BASE_URL ?>/documents/<?= htmlspecialchars($docId, ENT_QUOTES, 'UTF-8') ?>/download" class="btn-sigma-purple btn-sigma-sm" title="Unduh PDF Final">
                                                <i class="bi bi-download"></i> Unduh
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
    <?php
};
?>

<?php if ($isAdmin): ?>
    <?php $renderMainDocumentsBlock(); ?>
<?php else: ?>
    <?php if ($focusQueue): ?>
        <?php $renderAssignedQueueBlock(); ?>
        <?php $renderMainDocumentsBlock(); ?>
    <?php else: ?>
        <?php $renderMainDocumentsBlock(); ?>
        <?php $renderAssignedQueueBlock(); ?>
    <?php endif; ?>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('docTableSearchInput');
    const docTable = document.getElementById('mainDocumentsTable');
    if (searchInput && docTable) {
        const rows = docTable.querySelectorAll('tbody tr[data-search]');
        searchInput.addEventListener('input', function () {
            const q = this.value.trim().toLowerCase();
            rows.forEach(function (row) {
                const hay = row.getAttribute('data-search') || '';
                row.style.display = (!q || hay.indexOf(q) !== -1) ? '' : 'none';
            });
        });
    }

    const queueChips = document.getElementById('queueFilterChips');
    const queueTable = document.getElementById('assignedQueueTable');
    if (queueChips && queueTable) {
        const buttons = queueChips.querySelectorAll('button[data-queue-filter]');
        const qRows = queueTable.querySelectorAll('tbody tr[data-queue-cat]');
        buttons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                const target = this.getAttribute('data-queue-filter') || 'all';
                buttons.forEach(function (b) { b.classList.remove('active'); });
                this.classList.add('active');
                qRows.forEach(function (r) {
                    const cat = r.getAttribute('data-queue-cat');
                    r.style.display = (target === 'all' || cat === target) ? '' : 'none';
                });
            });
        });
    }
});
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
