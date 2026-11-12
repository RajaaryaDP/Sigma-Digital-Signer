<?php
$title = 'Detail Dokumen — ' . htmlspecialchars($doc['title']) . ' — SIGMA';
require_once __DIR__ . '/../layout/header.php';

// Format status badge helper
$statusBadges = [
    'draft'          => ['class' => 'bg-secondary', 'label' => 'Draft / Belum Diajukan', 'icon' => 'bi-pencil'],
    'pending_review' => ['class' => 'bg-warning text-dark', 'label' => 'Menunggu Review Admin', 'icon' => 'bi-hourglass-split'],
    'approved'       => ['class' => 'bg-info text-dark', 'label' => 'Disetujui Admin', 'icon' => 'bi-check2-circle'],
    'signing'        => ['class' => 'bg-primary', 'label' => 'Proses Penandatanganan', 'icon' => 'bi-pen-fill'],
    'completed'      => ['class' => 'bg-success', 'label' => 'Dokumen Selesai', 'icon' => 'bi-patch-check-fill'],
    'rejected'       => ['class' => 'bg-danger', 'label' => 'Dokumen Ditolak', 'icon' => 'bi-x-octagon-fill'],
];
$curBadge = $statusBadges[$doc['status']] ?? ['class' => 'bg-secondary', 'label' => ucfirst($doc['status']), 'icon' => 'bi-circle'];

$signerBadges = [
    'waiting'  => ['class' => 'bg-secondary', 'label' => 'Menunggu Giliran', 'icon' => 'bi-clock'],
    'pending'  => ['class' => 'bg-warning text-dark', 'label' => 'Giliran Aktif', 'icon' => 'bi-pen'],
    'signed'   => ['class' => 'bg-success', 'label' => 'Sudah Ditandatangani', 'icon' => 'bi-check-circle-fill'],
    'declined' => ['class' => 'bg-danger', 'label' => 'Menolak Tanda Tangan', 'icon' => 'bi-x-circle-fill'],
];

// Deteksi jika ada signer yang menolak (untuk akurasi stepper & banner penolakan)
$declinedSigner = null;
foreach ($signers as $s) {
    if (($s['status'] ?? '') === 'declined') {
        $declinedSigner = $s;
        break;
    }
}

// Indeks tanda tangan yang sudah terpasang berdasarkan document_signer_id dan signer_id
$signaturesByDocSigner = [];
$signaturesByUser      = [];
if (!empty($signatures)) {
    foreach ($signatures as $sigItem) {
        if (!empty($sigItem['document_signer_id'])) {
            $signaturesByDocSigner[$sigItem['document_signer_id']] = $sigItem;
        }
        if (!empty($sigItem['signer_id'])) {
            $signaturesByUser[$sigItem['signer_id']] = $sigItem;
        }
    }
}

$currentUserId = $_SESSION['user']['id'] ?? '';

// Tentukan halaman awal pratinjau PDF secara cerdas:
// - Jika yang membuka adalah penandatangan ($mySigner), buka langsung halaman slot miliknya
// - Jika sedang proses signing, buka halaman slot giliran aktif (current_step)
// - Selain itu, buka halaman slot penandatangan pertama (atau halaman 1)
$initialPreviewPage = 1;
if (!empty($mySigner) && (int) ($mySigner['page_number'] ?? 0) > 0) {
    $initialPreviewPage = (int) $mySigner['page_number'];
} elseif ($doc['status'] === 'signing' && !empty($signers)) {
    foreach ($signers as $s) {
        if ((int) $s['step_order'] === (int) $doc['current_step'] && (int) ($s['page_number'] ?? 0) > 0) {
            $initialPreviewPage = (int) $s['page_number'];
            break;
        }
    }
} elseif (!empty($signers) && (int) ($signers[0]['page_number'] ?? 0) > 0) {
    $initialPreviewPage = (int) $signers[0]['page_number'];
}
?>

<!-- Pustaka Mozilla PDF.js untuk pratinjau lembar dokumen + overlay slot & tanda tangan (ADR-09) -->
<script src="<?= BASE_URL ?>/assets/js/pdf.min.js"></script>

<!-- Back Link -->
<div class="mb-3">
    <a href="<?= BASE_URL ?>/documents" class="text-decoration-none text-muted small d-inline-flex align-items-center gap-1.5">
        <i class="bi bi-arrow-left"></i>
        <span>Kembali ke Dashboard Dokumen</span>
    </a>
</div>

<!-- Header Detail Dokumen -->
<div class="card-clean mb-4">
    <div class="card-clean-body p-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3">
            <div>
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                    <span class="badge <?= $curBadge['class'] ?> py-1.5 px-2.5 fs-7 d-inline-flex align-items-center gap-1">
                        <i class="bi <?= $curBadge['icon'] ?>"></i>
                        <?= htmlspecialchars($curBadge['label']) ?>
                    </span>
                    <span class="badge bg-light text-dark border">
                        <i class="bi <?= $doc['signature_type'] === 'single' ? 'bi-person-check' : 'bi-diagram-3' ?> me-1"></i>
                        <?= $doc['signature_type'] === 'single' ? 'Tanda Tangan Mandiri' : 'Tanda Tangan Berjenjang' ?>
                    </span>
                    <span class="text-muted small">
                        Diajukan oleh: <strong><?= htmlspecialchars($doc['uploader_name']) ?></strong>
                    </span>
                </div>
                <h3 class="fw-bold text-dark mb-2"><?= htmlspecialchars($doc['title']) ?></h3>
                <?php if (!empty($doc['description'])): ?>
                    <p class="text-muted mb-0 small"><?= nl2br(htmlspecialchars($doc['description'])) ?></p>
                <?php endif; ?>
            </div>

            <!-- Tombol Aksi Dokumen -->
            <div class="d-flex flex-wrap gap-2">
                <?php if ($doc['status'] === 'draft' && $isOwner): ?>
                    <?php if ($doc['signature_type'] === 'sequential'): ?>
                        <a href="<?= BASE_URL ?>/documents/<?= htmlspecialchars($doc['id']) ?>/arrange" class="btn btn-pnj-primary btn-sm">
                            <i class="bi bi-aspect-ratio me-1"></i> Atur Slot Tanda Tangan
                        </a>
                    <?php endif; ?>

                    <form action="<?= BASE_URL ?>/documents/<?= htmlspecialchars($doc['id']) ?>/delete" method="POST" 
                          onsubmit="return confirm('Apakah Anda yakin ingin menghapus draft dokumen ini? Berkas PDF akan dihapus secara permanen.');">
                        <button type="submit" class="btn btn-outline-danger btn-sm">
                            <i class="bi bi-trash me-1"></i> Hapus Draft
                        </button>
                    </form>
                <?php endif; ?>

                <?php if ($isAdmin && $doc['status'] === 'pending_review'): ?>
                    <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#modalReject">
                        <i class="bi bi-x-circle me-1"></i> Tolak Dokumen
                    </button>
                    <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalApprove">
                        <i class="bi bi-check-circle me-1"></i> Setujui Dokumen
                    </button>
                <?php endif; ?>

                <?php if (!$isAdmin && $mySigner): ?>
                    <?php if ($doc['status'] === 'signing' && $mySigner['status'] === 'pending' && (int) $mySigner['step_order'] === (int) $doc['current_step']): ?>
                        <a href="<?= BASE_URL ?>/documents/<?= htmlspecialchars($doc['id']) ?>/sign" class="btn btn-pnj-primary btn-sm">
                            <i class="bi bi-pen-fill me-1"></i> Tanda Tangani Sekarang
                        </a>
                    <?php elseif ($mySigner['status'] === 'waiting' && $doc['status'] === 'signing'): ?>
                        <button type="button" class="btn btn-secondary btn-sm" disabled
                                title="Menunggu tanda tangan penandatangan Step <?= (int) $doc['current_step'] ?>">
                            <i class="bi bi-lock-fill me-1"></i> Menunggu Giliran
                        </button>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if ($doc['status'] === 'completed'): ?>
                    <a href="<?= BASE_URL ?>/documents/<?= htmlspecialchars($doc['id']) ?>/download" class="btn btn-success btn-sm shadow-sm d-flex align-items-center gap-1.5 fw-semibold">
                        <i class="bi bi-file-earmark-check-fill"></i>
                        <span>Unduh Dokumen Final</span>
                    </a>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/documents/<?= htmlspecialchars($doc['id']) ?>/download" class="btn btn-subtle btn-sm d-flex align-items-center gap-1.5">
                        <i class="bi bi-download"></i>
                        <span>Unduh Berkas Asli</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Banner Penolakan (Jika status rejected) -->
        <?php if ($doc['status'] === 'rejected'): ?>
            <div class="alert alert-danger mt-4 mb-0 d-flex align-items-start gap-2.5" role="alert" style="border-radius: var(--radius-md);">
                <i class="bi bi-x-circle-fill fs-5 mt-0.5"></i>
                <div>
                    <h6 class="fw-bold mb-1">Pengajuan Dokumen Ini Ditolak (Arsip Batal)</h6>
                    <p class="mb-1 small">
                        <strong>Alasan Penolakan:</strong> <?= nl2br(htmlspecialchars($doc['rejection_reason'] ?: 'Tidak ada keterangan spesifik.')) ?>
                    </p>
                    <?php if ($declinedSigner): ?>
                        <small class="text-muted d-block mb-1">
                            Ditolak oleh penandatangan: <strong><?= htmlspecialchars($declinedSigner['signer_name']) ?></strong> (<?= htmlspecialchars($declinedSigner['role_title'] ?: ($declinedSigner['signer_position'] ?: 'Penandatangan')) ?>)
                        </small>
                    <?php elseif (!empty($doc['reviewed_at'])): ?>
                        <small class="text-muted d-block mb-1">
                            Ditolak saat review oleh: <strong><?= htmlspecialchars($doc['reviewer_name'] ?? 'Administrator') ?></strong> pada <?= date('d F Y, H:i', strtotime($doc['reviewed_at'])) ?> WIB
                        </small>
                    <?php endif; ?>
                    <small class="text-muted d-block mb-2">
                        Sesuai regulasi kampus, dokumen yang ditolak tidak dapat diedit kembali. Silakan ajukan berkas baru dengan perbaikan yang diperlukan.
                    </small>
                    <?php if ($isOwner): ?>
                        <a href="<?= BASE_URL ?>/documents/create" class="btn btn-danger btn-sm">
                            <i class="bi bi-plus-lg me-1"></i> Buat Pengajuan Baru
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Stepper Progres Alur Penandatanganan (Khusus Sequential) -->
    <?php if ($doc['signature_type'] === 'sequential'): ?>
    <div class="card-clean-header border-top py-3 px-4 bg-light">
        <div class="w-100">
            <div class="text-muted small fw-semibold text-uppercase mb-2.5" style="letter-spacing: 0.5px;">
                Alur Proses Berjenjang
            </div>
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 text-center">
                <!-- Tahap 1: Pengajuan -->
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle d-flex align-items-center justify-content-center bg-success text-white" style="width: 28px; height: 28px; font-size: 0.8rem;">
                        <i class="bi bi-check-lg"></i>
                    </div>
                    <span class="small fw-semibold text-dark">1. Upload & Atur Slot</span>
                </div>

                <i class="bi bi-chevron-right text-muted small d-none d-md-inline"></i>

                <!-- Tahap 2: Review Admin -->
                <div class="d-flex align-items-center gap-2">
                    <?php 
                        $step2Bg = 'bg-secondary text-white';
                        $step2Icon = 'bi-circle';
                        if ($doc['status'] === 'pending_review') {
                            $step2Bg = 'bg-warning text-dark';
                            $step2Icon = 'bi-hourglass-split';
                        } elseif (in_array($doc['status'], ['approved', 'signing', 'completed']) || ($doc['status'] === 'rejected' && $declinedSigner)) {
                            $step2Bg = 'bg-success text-white';
                            $step2Icon = 'bi-check-lg';
                        } elseif ($doc['status'] === 'rejected' && !$declinedSigner) {
                            $step2Bg = 'bg-danger text-white';
                            $step2Icon = 'bi-x-lg';
                        }
                    ?>
                    <div class="rounded-circle d-flex align-items-center justify-content-center <?= $step2Bg ?>" style="width: 28px; height: 28px; font-size: 0.8rem;">
                        <i class="bi <?= $step2Icon ?>"></i>
                    </div>
                    <span class="small fw-semibold text-dark">2. Peninjauan Admin</span>
                </div>

                <i class="bi bi-chevron-right text-muted small d-none d-md-inline"></i>

                <!-- Tahap 3: Penandatanganan -->
                <div class="d-flex align-items-center gap-2">
                    <?php 
                        $step3Bg = 'bg-secondary text-white';
                        $step3Icon = 'bi-circle';
                        if ($doc['status'] === 'signing') {
                            $step3Bg = 'bg-primary text-white';
                            $step3Icon = 'bi-pen-fill';
                        } elseif ($doc['status'] === 'completed') {
                            $step3Bg = 'bg-success text-white';
                            $step3Icon = 'bi-check-lg';
                        } elseif ($doc['status'] === 'rejected' && $declinedSigner) {
                            $step3Bg = 'bg-danger text-white';
                            $step3Icon = 'bi-x-lg';
                        }
                    ?>
                    <div class="rounded-circle d-flex align-items-center justify-content-center <?= $step3Bg ?>" style="width: 28px; height: 28px; font-size: 0.8rem;">
                        <i class="bi <?= $step3Icon ?>"></i>
                    </div>
                    <span class="small fw-semibold text-dark">
                        3. Penandatanganan (Step <?= (int) $doc['current_step'] ?>)
                    </span>
                </div>

                <i class="bi bi-chevron-right text-muted small d-none d-md-inline"></i>

                <!-- Tahap 4: Dokumen Selesai -->
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle d-flex align-items-center justify-content-center <?= $doc['status'] === 'completed' ? 'bg-success text-white' : 'bg-secondary text-white' ?>" style="width: 28px; height: 28px; font-size: 0.8rem;">
                        <i class="bi <?= $doc['status'] === 'completed' ? 'bi-check-lg' : 'bi-circle' ?>"></i>
                    </div>
                    <span class="small fw-semibold text-dark">4. Selesai</span>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Panel Verifikasi Dokumen oleh Admin (Task 6) -->
<?php if ($isAdmin && $doc['status'] === 'pending_review'): ?>
<div class="card-clean mb-4" style="border: 1px solid #f59e0b; background: #fffdf5;">
    <div class="card-clean-body p-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge bg-warning text-dark py-1 px-2.5 fs-7 fw-semibold d-inline-flex align-items-center gap-1">
                        <i class="bi bi-shield-check"></i>
                        Verifikasi Administrator Diperlukan
                    </span>
                    <span class="text-muted small">&bull; Menunggu Keputusan Anda</span>
                </div>
                <h5 class="fw-bold text-dark mb-1">Persetujuan Kelayakan Dokumen</h5>
                <p class="text-muted small mb-0" style="max-width: 650px;">
                    Silakan telaah lembar berkas PDF dan tata letak slot tanda tangan penandatangan berjenjang di bawah. Setelah disetujui, berkas akan diteruskan ke penandatangan urutan pertama (Step 1).
                </p>
            </div>
            <div class="d-flex gap-2 flex-shrink-0">
                <button type="button" class="btn btn-outline-danger px-3 py-2 btn-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#modalReject">
                    <i class="bi bi-x-circle me-1"></i> Tolak Berkas
                </button>
                <button type="button" class="btn btn-success px-3 py-2 btn-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#modalApprove">
                    <i class="bi bi-check-circle me-1"></i> Setujui Berkas
                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row g-4">
    <!-- Kolom Kiri: Penandatangan & Pratinjau Dokumen -->
    <div class="col-lg-8">
        <!-- Daftar Penandatangan Berjenjang -->
        <?php if ($doc['signature_type'] === 'sequential'): ?>
        <div class="card-clean mb-4">
            <div class="card-clean-header py-3 px-3">
                <span class="fw-semibold text-dark small d-flex align-items-center gap-1.5">
                    <i class="bi bi-people-fill text-success"></i>
                    <span>Daftar Penandatangan Berjenjang (Urutan Jabatan)</span>
                </span>
                <span class="badge bg-light text-dark border">
                    <?= count($signers) ?> Penandatangan
                </span>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr style="font-size: 0.75rem;">
                            <th class="text-center" style="width: 70px;">Urutan</th>
                            <th>Nama Penandatangan</th>
                            <th>Jabatan / Peran</th>
                            <th>Posisi Slot (Hal & %)</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($signers as $s): ?>
                            <?php 
                                $sBadge = $signerBadges[$s['status']] ?? ['class' => 'bg-secondary', 'label' => ucfirst($s['status']), 'icon' => 'bi-circle'];
                                $isCurrentStep = ($doc['status'] === 'signing' && (int)$s['step_order'] === (int)$doc['current_step']);
                            ?>
                            <tr class="<?= $isCurrentStep ? 'table-warning' : '' ?>">
                                <td class="text-center">
                                    <span class="badge <?= $isCurrentStep ? 'bg-warning text-dark' : 'bg-secondary' ?> rounded-pill">
                                        Step <?= $s['step_order'] ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark small"><?= htmlspecialchars($s['signer_name']) ?></div>
                                    <div class="text-muted" style="font-size: 0.75rem;">
                                        <?= htmlspecialchars($s['signer_identifier'] ?: '-') ?> &bull; <?= ucfirst($s['signer_type'] ?? '') ?>
                                    </div>
                                </td>
                                <td class="small text-muted">
                                    <?= htmlspecialchars($s['role_title'] ?: ($s['signer_position'] ?: '-')) ?>
                                </td>
                                <td class="small">
                                    <?php if ((float)$s['x_percent'] > 0 || (float)$s['y_percent'] > 0): ?>
                                        <button type="button"
                                                class="badge bg-light text-dark border btn btn-sm py-1 px-2 text-decoration-none"
                                                style="font-size: 0.74rem;"
                                                onclick="jumpToPreviewPage(<?= (int) ($s['page_number'] ?: 1) ?>, 'detailSlot_<?= htmlspecialchars($s['id']) ?>')"
                                                title="Klik untuk melihat posisi kotak slot pada pratinjau PDF di bawah">
                                            <i class="bi bi-geo-alt-fill text-success me-0.5"></i>Hal. <?= (int) $s['page_number'] ?> (X: <?= (float)$s['x_percent'] ?>%, Y: <?= (float)$s['y_percent'] ?>%)
                                        </button>
                                    <?php else: ?>
                                        <span class="badge bg-light text-danger border" style="font-size: 0.75rem;">
                                            <i class="bi bi-exclamation-circle me-1"></i> Belum diatur
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge <?= $sBadge['class'] ?>" style="font-size: 0.75rem;">
                                        <i class="bi <?= $sBadge['icon'] ?> me-1"></i>
                                        <?= htmlspecialchars($sBadge['label']) ?>
                                    </span>
                                    <?php if (!empty($s['signed_at'])): ?>
                                        <div class="text-muted mt-0.5" style="font-size: 0.65rem;">
                                            <?= date('d M Y, H:i', strtotime($s['signed_at'])) ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($s['decline_reason'])): ?>
                                        <div class="text-danger mt-1" style="font-size: 0.68rem;">
                                            <i class="bi bi-chat-left-text me-0.5"></i><?= nl2br(htmlspecialchars($s['decline_reason'])) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Rekam Tanda Tangan Terpasang (Task 7) -->
        <?php if (!empty($signatures)): ?>
        <div class="card-clean mb-4">
            <div class="card-clean-header py-3 px-3 d-flex justify-content-between align-items-center">
                <span class="fw-semibold text-dark small d-flex align-items-center gap-1.5">
                    <i class="bi bi-pen-fill text-success"></i>
                    <span>Rekam Tanda Tangan Terpasang</span>
                </span>
                <span class="badge bg-success"><?= count($signatures) ?> Tanda Tangan</span>
            </div>
            <div class="card-clean-body p-3">
                <div class="d-flex flex-column gap-3">
                    <?php foreach ($signatures as $sig): ?>
                        <?php
                            $isQrOnly = ($sig['signature_format'] ?? '') === 'qr_only' || empty($sig['signature_image_path']);
                            $sigIdent = trim((string) ($sig['signer_identifier_snapshot'] ?? ''));
                            $sigTypeSnap = strtolower(trim((string) ($sig['signer_type_snapshot'] ?? $sig['signer_category_snapshot'] ?? '')));
                            $sigIdLabel = $sigTypeSnap === 'mahasiswa' ? 'NIM' : 'NIP';
                            $sigPageNo = (int) ($sig['page_number'] ?? 1) > 0 ? (int) $sig['page_number'] : 1;
                            $sigTargetSlotId = !empty($sig['document_signer_id'])
                                ? ('detailSlot_' . $sig['document_signer_id'])
                                : ('detailSig_' . $sig['id']);
                        ?>
                        <div class="d-flex align-items-center gap-3 border rounded-3 p-2.5">
                            <div class="checkerboard-bg rounded border flex-shrink-0 d-flex align-items-center justify-content-center"
                                 style="width: 165px; height: 80px; overflow: hidden;">
                                <div class="d-flex align-items-stretch w-100 h-100 px-1.5 py-1">
                                    <div class="d-flex flex-column <?= $isQrOnly ? 'justify-content-center' : 'justify-content-end' ?> text-center" style="width: 58%; min-width: 0; line-height: 1.15; padding-right: 3px;">
                                        <?php if (!$isQrOnly): ?>
                                            <div class="position-relative flex-grow-1" style="min-height: 0;">
                                                <img src="<?= BASE_URL ?>/signatures/<?= htmlspecialchars($sig['id']) ?>/preview"
                                                     alt="Tanda tangan <?= htmlspecialchars($sig['signer_name_snapshot']) ?>"
                                                     style="position: absolute; inset: 0; width: 100%; height: 100%; object-fit: contain;">
                                            </div>
                                        <?php endif; ?>
                                        <div class="fw-bold text-dark text-truncate" style="font-size: 0.56rem;">
                                            <?= htmlspecialchars($sig['signer_name_snapshot']) ?>
                                        </div>
                                        <div style="border-top: 1px solid #0f172a; margin: 1px 0;"></div>
                                        <?php if ($sigIdent !== '' && $sigIdent !== '-'): ?>
                                            <div class="text-muted text-truncate" style="font-size: 0.48rem;">
                                                <?= htmlspecialchars($sigIdLabel) ?>: <?= htmlspecialchars($sigIdent) ?>
                                            </div>
                                        <?php elseif (!empty($sig['signer_position_snapshot'])): ?>
                                            <div class="text-muted text-truncate" style="font-size: 0.48rem;">
                                                <?= htmlspecialchars($sig['signer_position_snapshot']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-center" style="width: 42%;">
                                        <div class="d-flex align-items-center justify-content-center rounded border border-success-subtle bg-white text-success" style="width: 42px; height: 42px;">
                                            <i class="bi bi-qr-code fs-5"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex-grow-1 min-width-0">
                                <div class="fw-bold text-dark small d-flex flex-wrap align-items-center gap-1">
                                    <span><?= htmlspecialchars($sig['signer_name_snapshot']) ?></span>
                                    <?php if (!empty($sig['step_order'])): ?>
                                        <span class="badge bg-light text-dark border" style="font-size: 0.60rem;">Step <?= (int) $sig['step_order'] ?></span>
                                    <?php endif; ?>
                                    <span class="badge bg-success" style="font-size: 0.62rem;">Tertanda</span>
                                    <?php if ($isQrOnly): ?>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 0.62rem;">
                                            <i class="bi bi-qr-code me-0.5"></i>Hanya QR
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.62rem;">
                                            <i class="bi bi-pen me-0.5"></i>QR + TTD
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-muted" style="font-size: 0.75rem;">
                                    <?= htmlspecialchars($sig['signer_position_snapshot'] ?: '-') ?>
                                    &bull; <?= date('d F Y, H:i', strtotime($sig['signed_at'])) ?> WIB
                                </div>
                                <div class="text-muted d-flex flex-wrap align-items-center gap-2 mt-0.5" style="font-size: 0.7rem;">
                                    <span class="text-truncate">
                                        <i class="bi bi-qr-code me-1"></i>Token:
                                        <code><?= htmlspecialchars($sig['verification_token']) ?></code>
                                    </span>
                                    <a href="<?= BASE_URL ?>/verify?token=<?= urlencode($sig['verification_token']) ?>" target="_blank" class="text-decoration-none text-success fw-bold" title="Buka verifikasi publik tanda tangan ini">
                                        <i class="bi bi-box-arrow-up-right me-0.5"></i>Verifikasi
                                    </a>
                                    <button type="button"
                                            class="btn btn-link btn-sm p-0 text-decoration-none text-primary fw-semibold"
                                            style="font-size: 0.7rem;"
                                            onclick="jumpToPreviewPage(<?= $sigPageNo ?>, '<?= htmlspecialchars($sigTargetSlotId) ?>')">
                                        <i class="bi bi-eye-fill me-0.5"></i>Lihat di PDF (Hal. <?= $sigPageNo ?>)
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Pratinjau Dokumen PDF Interaktif + Overlay Kotak Slot & Tanda Tangan -->
        <?php $isCompletedDoc = ($doc['status'] === 'completed'); ?>
        <div class="card-clean mb-4" id="pdfPreviewSection">
            <div class="card-clean-header py-2.5 px-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="fw-semibold text-dark small d-flex align-items-center gap-1.5">
                        <i class="bi bi-file-earmark-pdf text-danger"></i>
                        <span>Pratinjau Lembar Berkas & Slot Tanda Tangan</span>
                    </span>
                    <?php if ($isCompletedDoc): ?>
                        <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.68rem;">
                            <i class="bi bi-patch-check-fill me-0.5"></i>PDF Final Berstempel
                        </span>
                    <?php else: ?>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 0.68rem;">
                            <i class="bi bi-bounding-box-circles me-0.5"></i>Pratinjau Slot & TTD Aktif
                        </span>
                    <?php endif; ?>
                </div>

                <!-- Navigasi Halaman & Kontrol Overlay Slot -->
                <div class="d-flex align-items-center gap-1.5 flex-wrap">
                    <div class="btn-group btn-group-sm" role="group" aria-label="Navigasi Halaman PDF">
                        <button type="button" class="btn btn-subtle px-2 py-1" id="btnDetailPrevPage" disabled title="Halaman Sebelumnya">
                            <i class="bi bi-chevron-left"></i>
                        </button>
                        <span class="btn btn-subtle disabled fw-semibold text-dark px-2.5 py-1" style="font-size: 0.76rem; opacity: 1;">
                            Hal <span id="detailPageNumDisplay"><?= (int) $initialPreviewPage ?></span> / <span id="detailPageTotalDisplay">1</span>
                        </span>
                        <button type="button" class="btn btn-subtle px-2 py-1" id="btnDetailNextPage" disabled title="Halaman Berikutnya">
                            <i class="bi bi-chevron-right"></i>
                        </button>
                    </div>

                    <button type="button" class="btn btn-subtle btn-sm py-1 px-2" id="btnToggleSlotOverlay" title="Tampilkan atau sembunyikan kotak batas slot tanda tangan">
                        <i class="bi bi-bounding-box me-1 text-success"></i><span id="toggleSlotOverlayLabel">Sembunyikan Slot</span>
                    </button>

                    <a href="<?= BASE_URL ?>/documents/<?= htmlspecialchars($doc['id']) ?>/preview" target="_blank" class="btn btn-subtle btn-sm py-1 px-2" title="Buka berkas PDF di tab baru">
                        <i class="bi bi-arrows-fullscreen me-1"></i>Layar Penuh
                    </a>
                </div>
            </div>

            <!-- Bar Pintasan Lompat ke Slot Penandatangan -->
            <?php if (!empty($signers)): ?>
            <div class="px-3 py-2 bg-light border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center flex-wrap gap-1.5">
                    <span class="text-muted fw-semibold me-1" style="font-size: 0.72rem;">
                        <i class="bi bi-layers me-1"></i>Lokasi Slot:
                    </span>
                    <?php foreach ($signers as $s): ?>
                        <?php
                            $sPage = (int) ($s['page_number'] ?: 1);
                            $hasCoord = ((float) $s['x_percent'] > 0 || (float) $s['y_percent'] > 0);
                            $isSignedSlot = ($s['status'] === 'signed') || isset($signaturesByDocSigner[$s['id']]);
                            $isActiveTurn = ($doc['status'] === 'signing' && (int) $s['step_order'] === (int) $doc['current_step']);
                            $isMine = (!empty($mySigner) && $mySigner['id'] === $s['id']);

                            if ($isSignedSlot) {
                                $pillClass = 'btn-success-subtle text-success border border-success-subtle';
                                $pillIcon  = 'bi-check-circle-fill';
                            } elseif ($isActiveTurn) {
                                $pillClass = 'btn-warning text-dark border border-warning';
                                $pillIcon  = 'bi-pen-fill';
                            } elseif (!$hasCoord) {
                                $pillClass = 'btn-outline-danger';
                                $pillIcon  = 'bi-exclamation-circle';
                            } else {
                                $pillClass = 'btn-white border text-dark bg-white';
                                $pillIcon  = 'bi-bounding-box';
                            }
                        ?>
                        <button type="button"
                                class="btn btn-sm py-0.5 px-2 fw-medium <?= $pillClass ?>"
                                style="font-size: 0.70rem;"
                                onclick="jumpToPreviewPage(<?= $sPage ?>, 'detailSlot_<?= htmlspecialchars($s['id']) ?>')"
                                title="Klik untuk melihat slot Step <?= (int) $s['step_order'] ?> pada Halaman <?= $sPage ?>">
                            <i class="bi <?= $pillIcon ?> me-0.5"></i>
                            Step <?= (int) $s['step_order'] ?>: <?= htmlspecialchars($s['signer_name']) ?>
                            <?= $isMine ? ' (Anda)' : '' ?>
                            &bull; Hal. <?= $sPage ?>
                        </button>
                    <?php endforeach; ?>
                </div>
                <span class="text-muted" style="font-size: 0.68rem;">
                    <i class="bi bi-info-circle me-0.5"></i>Klik tombol penandatangan untuk melompat ke halaman slotnya
                </span>
            </div>
            <?php endif; ?>

            <div class="card-clean-body p-3 bg-secondary bg-opacity-10 text-center">
                <!-- Indikator Memuat PDF -->
                <div id="detailPdfLoadingSpinner" class="py-5 text-muted">
                    <div class="spinner-border spinner-border-sm text-success me-2" role="status"></div>
                    <span class="small">Memuat lembar dokumen & pratinjau slot tanda tangan...</span>
                </div>

                <!-- Alert Fallback jika PDF.js gagal -->
                <div id="detailPdfFallbackAlert" class="alert alert-warning py-2 px-3 small d-none text-start mb-3" style="border-radius: var(--radius-md);">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    Pratinjau kanvas tidak dapat dimuat otomatis. Menampilkan representasi lembar halaman beserta posisi kotak slot & tanda tangan di bawah ini.
                </div>

                <!-- Wadah Kanvas PDF + Lapisan Overlay Kotak Slot & Tanda Tangan -->
                <div id="detailPdfStageContainer" class="d-inline-block text-start position-relative d-none" style="max-width: 100%;">
                    <div id="detailSlotCanvasContainer" class="position-relative bg-white shadow-sm border rounded mx-auto overflow-hidden">
                        <!-- Canvas PDF Asli -->
                        <canvas id="pdfDetailRenderCanvas" style="display: block; width: 100%; height: auto;"></canvas>

                        <!-- Watermark Latar Fallback -->
                        <div id="detailFallbackWatermark" class="position-absolute top-0 start-0 w-100 h-100 d-none flex-column align-items-center justify-content-center pointer-events-none opacity-25" style="user-select: none;">
                            <i class="bi bi-file-earmark-text display-1 text-muted"></i>
                            <span class="fw-semibold text-muted small mt-2">Representasi Lembar Halaman</span>
                        </div>

                        <!-- Lapisan Overlay Kotak Slot & Tanda Tangan -->
                        <div id="detailSlotOverlayContainer" class="position-absolute top-0 start-0 w-100 h-100" style="pointer-events: none;">
                            <?php
                                $renderedSignatureIds = [];
                                $slotColors = [
                                    ['border' => '#059669', 'bg' => 'rgba(16, 185, 129, 0.12)', 'badge' => 'bg-success'],
                                    ['border' => '#2563eb', 'bg' => 'rgba(37, 99, 235, 0.12)', 'badge' => 'bg-primary'],
                                    ['border' => '#d97706', 'bg' => 'rgba(217, 119, 6, 0.14)', 'badge' => 'bg-warning text-dark'],
                                    ['border' => '#7c3aed', 'bg' => 'rgba(124, 58, 237, 0.12)', 'badge' => 'bg-secondary'],
                                ];
                            ?>
                            <?php foreach ($signers as $idx => $s): ?>
                                <?php
                                    $matchedSig = $signaturesByDocSigner[$s['id']] ?? (($s['status'] === 'signed') ? ($signaturesByUser[$s['user_id']] ?? null) : null);
                                    if ($matchedSig) {
                                        $renderedSignatureIds[$matchedSig['id']] = true;
                                        $sx = (float) $matchedSig['x_percent'];
                                        $sy = (float) $matchedSig['y_percent'];
                                        $sw = (float) $matchedSig['width_percent'] > 0 ? (float) $matchedSig['width_percent'] : 20.0;
                                        $sh = (float) $matchedSig['height_percent'] > 0 ? (float) $matchedSig['height_percent'] : 10.0;
                                        $sp = (int) $matchedSig['page_number'] > 0 ? (int) $matchedSig['page_number'] : 1;
                                    } else {
                                        $sx = (float) $s['x_percent'];
                                        $sy = (float) $s['y_percent'];
                                        $sw = (float) $s['width_percent'] > 0 ? (float) $s['width_percent'] : 25.0;
                                        $sh = (float) $s['height_percent'] > 0 ? (float) $s['height_percent'] : 10.0;
                                        $sp = (int) $s['page_number'] > 0 ? (int) $s['page_number'] : 1;
                                    }

                                    // Jika belum diatur sama sekali (0,0) saat draft, tampilkan di posisi default pratinjau
                                    $isUnconfiguredSlot = (!$matchedSig && $sx == 0 && $sy == 0);
                                    if ($isUnconfiguredSlot) {
                                        $sx = 10.0 + (($idx % 2) * 45.0);
                                        $sy = 75.0 + (floor($idx / 2) * 12.0);
                                        if ($sy > 88.0) $sy = 85.0;
                                    }

                                    $isMineSlot = ($s['user_id'] === $currentUserId);
                                    $isCurrentTurnSlot = ($doc['status'] === 'signing' && (int) $s['step_order'] === (int) $doc['current_step']);
                                ?>

                                <?php if ($matchedSig): ?>
                                    <?php
                                        $isSigQrOnly = ($matchedSig['signature_format'] ?? '') === 'qr_only' || empty($matchedSig['signature_image_path']);
                                        $sigTypeSnap = strtolower(trim((string) ($matchedSig['signer_type_snapshot'] ?? $matchedSig['signer_category_snapshot'] ?? '')));
                                        $sigIdLbl    = ($sigTypeSnap === 'mahasiswa') ? 'NIM' : 'NIP';
                                        $sigIdentTxt = !empty($matchedSig['signer_identifier_snapshot'])
                                            ? ($sigIdLbl . ': ' . $matchedSig['signer_identifier_snapshot'])
                                            : ($matchedSig['signer_position_snapshot'] ?? '');
                                    ?>
                                    <div id="detailSlot_<?= htmlspecialchars($s['id']) ?>"
                                         class="position-absolute rounded text-center overflow-hidden <?= ($sp !== $initialPreviewPage) ? 'd-none' : '' ?> <?= $isCompletedDoc ? '' : 'bg-white shadow-xs' ?>"
                                         data-page="<?= $sp ?>"
                                         style="left: <?= $sx ?>%; top: <?= $sy ?>%; width: <?= $sw ?>%; height: <?= $sh ?>%;
                                                border: <?= $isCompletedDoc ? '1.5px dashed #10b981' : '2px solid #059669' ?> !important;
                                                background-color: <?= $isCompletedDoc ? 'rgba(16, 185, 129, 0.04)' : '#ffffff' ?>;
                                                z-index: <?= $isMineSlot ? 22 : 16 ?>; pointer-events: auto; transition: box-shadow 0.25s ease;"
                                         title="Step <?= (int) $s['step_order'] ?>: Telah ditandatangani oleh <?= htmlspecialchars($matchedSig['signer_name_snapshot']) ?>">
                                        <span class="badge <?= $isMineSlot ? 'bg-success text-white' : 'bg-success-subtle text-success border border-success-subtle' ?> position-absolute top-0 start-0 m-0.5 px-1 py-0 text-truncate"
                                              style="font-size: 0.52rem; max-width: 94%; z-index: 5;">
                                            <i class="bi bi-check-circle-fill me-0.5"></i>Step <?= (int) $s['step_order'] ?> &bull; <?= $isMineSlot ? 'Tanda Tangan Anda' : 'Tertanda' ?>
                                        </span>

                                        <!-- Isi visual 2 kolom: selalu tampil saat dokumen belum completed, atau saat fallback offline -->
                                        <div class="w-100 h-100 position-relative <?= $isCompletedDoc ? 'd-none completed-fallback-inner' : 'd-flex' ?> align-items-stretch">
                                            <div class="d-flex flex-column <?= $isSigQrOnly ? 'justify-content-center' : 'justify-content-end' ?>" style="width: 58%; padding: 14px 3px 3px 4px; min-width: 0;">
                                                <?php if (!$isSigQrOnly): ?>
                                                    <div class="position-relative flex-grow-1" style="min-height: 0;">
                                                        <img src="<?= BASE_URL ?>/signatures/<?= htmlspecialchars($matchedSig['id']) ?>/preview"
                                                             alt="Tanda tangan <?= htmlspecialchars($matchedSig['signer_name_snapshot']) ?>"
                                                             style="position: absolute; inset: 0; width: 100%; height: 100%; object-fit: contain;">
                                                    </div>
                                                <?php endif; ?>
                                                <div class="text-center" style="line-height: 1.15; min-width: 0;">
                                                    <div class="text-dark fw-bold text-truncate" style="font-size: 0.58rem;">
                                                        <?= htmlspecialchars($matchedSig['signer_name_snapshot']) ?>
                                                    </div>
                                                    <div style="border-top: 1px solid #0f172a; margin: 1px 0;"></div>
                                                    <?php if (!empty($sigIdentTxt)): ?>
                                                        <div class="text-muted text-truncate" style="font-size: 0.48rem;">
                                                            <?= htmlspecialchars($sigIdentTxt) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-center" style="width: 42%; padding: 3px;">
                                                <div class="d-flex flex-column align-items-center justify-content-center bg-white shadow-xs rounded border border-success-subtle text-success"
                                                     style="aspect-ratio: 1 / 1; height: 78%; max-width: 100%; font-size: 0.95rem;"
                                                     title="QR Verifikasi Resmi">
                                                    <i class="bi bi-qr-code"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <?php
                                        $sc = $slotColors[$idx % count($slotColors)];
                                        $sIdLbl = (($s['signer_type'] ?? '') === 'mahasiswa') ? 'NIM' : 'NIP';
                                        $sIdentTxt = !empty($s['signer_identifier'])
                                            ? ($sIdLbl . ': ' . $s['signer_identifier'])
                                            : ($s['role_title'] ?: ($s['signer_position'] ?? ''));

                                        if ($s['status'] === 'declined') {
                                            $boxBorder = '2px dashed #dc2626';
                                            $boxBg     = 'rgba(220, 38, 38, 0.10)';
                                            $badgeCls  = 'bg-danger text-white';
                                            $statusTxt = 'Menolak TTD';
                                        } elseif ($isCurrentTurnSlot) {
                                            $boxBorder = '2px solid #d97706';
                                            $boxBg     = 'rgba(245, 158, 11, 0.14)';
                                            $badgeCls  = 'bg-warning text-dark';
                                            $statusTxt = $isMineSlot ? 'Giliran Anda' : 'Giliran Aktif';
                                        } elseif ($isMineSlot) {
                                            $boxBorder = '2px dashed #059669';
                                            $boxBg     = 'rgba(16, 185, 129, 0.10)';
                                            $badgeCls  = 'bg-success text-white';
                                            $statusTxt = 'Slot Anda';
                                        } else {
                                            $boxBorder = '1.5px dashed ' . $sc['border'];
                                            $boxBg     = $sc['bg'];
                                            $badgeCls  = $sc['badge'];
                                            $statusTxt = $isUnconfiguredSlot ? 'Belum Diatur' : 'Slot TTD';
                                        }
                                    ?>
                                    <div id="detailSlot_<?= htmlspecialchars($s['id']) ?>"
                                         class="position-absolute rounded text-center overflow-hidden <?= ($sp !== $initialPreviewPage) ? 'd-none' : '' ?>"
                                         data-page="<?= $sp ?>"
                                         style="left: <?= $sx ?>%; top: <?= $sy ?>%; width: <?= $sw ?>%; height: <?= $sh ?>%;
                                                border: <?= $boxBorder ?> !important; background-color: <?= $boxBg ?>;
                                                z-index: <?= $isCurrentTurnSlot ? 20 : 12 ?>; pointer-events: auto; transition: box-shadow 0.25s ease;"
                                         title="Slot Step <?= (int) $s['step_order'] ?>: <?= htmlspecialchars($s['signer_name']) ?> (<?= htmlspecialchars($statusTxt) ?>)">
                                        <span class="badge <?= $badgeCls ?> position-absolute top-0 start-0 m-0.5 px-1 py-0 text-truncate"
                                              style="font-size: 0.52rem; max-width: 94%; z-index: 5;">
                                            Step <?= (int) $s['step_order'] ?> &bull; <?= htmlspecialchars($statusTxt) ?>
                                        </span>
                                        <div class="w-100 h-100 position-relative d-flex align-items-stretch">
                                            <div class="d-flex flex-column justify-content-end" style="width: 58%; padding: 13px 3px 3px 4px; min-width: 0;">
                                                <div class="flex-grow-1 d-flex align-items-center justify-content-center text-muted" style="font-size: 0.52rem; min-height: 0;">
                                                    <span><i class="bi bi-pen me-0.5"></i>[Area TTD]</span>
                                                </div>
                                                <div class="text-center" style="line-height: 1.15; min-width: 0;">
                                                    <div class="text-dark fw-bold text-truncate" style="font-size: 0.56rem;">
                                                        <?= htmlspecialchars($s['signer_name']) ?>
                                                    </div>
                                                    <div style="border-top: 1px solid #0f172a; margin: 1px 0;"></div>
                                                    <?php if (!empty($sIdentTxt)): ?>
                                                        <div class="text-muted text-truncate" style="font-size: 0.48rem;">
                                                            <?= htmlspecialchars($sIdentTxt) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-center" style="width: 42%; padding: 3px;">
                                                <div class="d-flex flex-column align-items-center justify-content-center bg-white bg-opacity-75 rounded border text-muted"
                                                     style="aspect-ratio: 1 / 1; height: 74%; max-width: 100%; font-size: 0.85rem;">
                                                    <i class="bi bi-qr-code"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>

                            <!-- Fallback untuk dokumen Single-Sign atau tanda tangan tanpa baris document_signers -->
                            <?php if (!empty($signatures)): ?>
                                <?php foreach ($signatures as $sig): ?>
                                    <?php
                                        if (!empty($renderedSignatureIds[$sig['id']])) continue;
                                        $sx = (float) $sig['x_percent'];
                                        $sy = (float) $sig['y_percent'];
                                        $sw = (float) $sig['width_percent'] > 0 ? (float) $sig['width_percent'] : 20.0;
                                        $sh = (float) $sig['height_percent'] > 0 ? (float) $sig['height_percent'] : 10.0;
                                        $sp = (int) $sig['page_number'] > 0 ? (int) $sig['page_number'] : 1;
                                        $isSigQrOnly = ($sig['signature_format'] ?? '') === 'qr_only' || empty($sig['signature_image_path']);
                                        $sigTypeSnap = strtolower(trim((string) ($sig['signer_type_snapshot'] ?? $sig['signer_category_snapshot'] ?? '')));
                                        $sigIdLbl    = ($sigTypeSnap === 'mahasiswa') ? 'NIM' : 'NIP';
                                        $sigIdentTxt = !empty($sig['signer_identifier_snapshot'])
                                            ? ($sigIdLbl . ': ' . $sig['signer_identifier_snapshot'])
                                            : ($sig['signer_position_snapshot'] ?? '');
                                    ?>
                                    <div id="detailSig_<?= htmlspecialchars($sig['id']) ?>"
                                         class="position-absolute rounded text-center overflow-hidden <?= ($sp !== $initialPreviewPage) ? 'd-none' : '' ?> <?= $isCompletedDoc ? '' : 'bg-white shadow-xs' ?>"
                                         data-page="<?= $sp ?>"
                                         style="left: <?= $sx ?>%; top: <?= $sy ?>%; width: <?= $sw ?>%; height: <?= $sh ?>%;
                                                border: <?= $isCompletedDoc ? '1.5px dashed #10b981' : '2px solid #059669' ?> !important;
                                                background-color: <?= $isCompletedDoc ? 'rgba(16, 185, 129, 0.04)' : '#ffffff' ?>;
                                                z-index: 16; pointer-events: auto;">
                                        <span class="badge bg-success-subtle text-success border border-success-subtle position-absolute top-0 start-0 m-0.5 px-1 py-0"
                                              style="font-size: 0.52rem; z-index: 5;">
                                            <i class="bi bi-check-circle-fill me-0.5"></i>Tertanda
                                        </span>
                                        <div class="w-100 h-100 position-relative <?= $isCompletedDoc ? 'd-none completed-fallback-inner' : 'd-flex' ?> align-items-stretch">
                                            <div class="d-flex flex-column <?= $isSigQrOnly ? 'justify-content-center' : 'justify-content-end' ?>" style="width: 58%; padding: 14px 3px 3px 4px; min-width: 0;">
                                                <?php if (!$isSigQrOnly): ?>
                                                    <div class="position-relative flex-grow-1" style="min-height: 0;">
                                                        <img src="<?= BASE_URL ?>/signatures/<?= htmlspecialchars($sig['id']) ?>/preview"
                                                             alt="Tanda tangan <?= htmlspecialchars($sig['signer_name_snapshot']) ?>"
                                                             style="position: absolute; inset: 0; width: 100%; height: 100%; object-fit: contain;">
                                                    </div>
                                                <?php endif; ?>
                                                <div class="text-center" style="line-height: 1.15; min-width: 0;">
                                                    <div class="text-dark fw-bold text-truncate" style="font-size: 0.58rem;">
                                                        <?= htmlspecialchars($sig['signer_name_snapshot']) ?>
                                                    </div>
                                                    <div style="border-top: 1px solid #0f172a; margin: 1px 0;"></div>
                                                    <?php if (!empty($sigIdentTxt)): ?>
                                                        <div class="text-muted text-truncate" style="font-size: 0.48rem;">
                                                            <?= htmlspecialchars($sigIdentTxt) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-center" style="width: 42%; padding: 3px;">
                                                <div class="d-flex flex-column align-items-center justify-content-center bg-white shadow-xs rounded border border-success-subtle text-success"
                                                     style="aspect-ratio: 1 / 1; height: 78%; max-width: 100%; font-size: 0.95rem;">
                                                    <i class="bi bi-qr-code"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Kolom Kanan: Informasi Berkas & Riwayat Audit -->
    <div class="col-lg-4">
        <!-- Rincian Integritas Berkas -->
        <div class="card-clean mb-4">
            <div class="card-clean-header py-2.5 px-3">
                <span class="fw-semibold text-dark small">
                    <i class="bi bi-shield-check text-success me-1"></i> Integritas & Info Berkas
                </span>
            </div>
            <div class="card-clean-body p-3">
                <ul class="list-unstyled mb-0 d-flex flex-column gap-2.5 small">
                    <li>
                        <div class="d-flex align-items-center justify-content-between">
                            <span class="text-muted d-block" style="font-size: 0.75rem;">Nama Berkas Asli</span>
                            <?php if ($doc['status'] === 'completed'): ?>
                                <a href="<?= BASE_URL ?>/documents/<?= htmlspecialchars($doc['id']) ?>/download?type=original" 
                                   class="btn btn-link btn-sm p-0 text-decoration-none text-muted" style="font-size: 0.7rem;" 
                                   title="Unduh berkas PDF asli sebelum dibubuhi tanda tangan">
                                    <i class="bi bi-download me-0.5"></i>Unduh Asli
                                </a>
                            <?php endif; ?>
                        </div>
                        <span class="fw-medium text-dark text-break"><?= htmlspecialchars($doc['file_name_original']) ?></span>
                    </li>
                    <li>
                        <span class="text-muted d-block" style="font-size: 0.75rem;">Ukuran Berkas</span>
                        <span class="fw-medium text-dark"><?= number_format($doc['file_size'] / 1024, 1) ?> KB</span>
                    </li>
                    <li>
                        <span class="text-muted d-block" style="font-size: 0.75rem;">Waktu Unggah</span>
                        <span class="fw-medium text-dark"><?= date('d F Y, H:i:s', strtotime($doc['created_at'])) ?> WIB</span>
                    </li>
                    <li>
                        <div class="d-flex align-items-center justify-content-between">
                            <span class="text-muted d-block" style="font-size: 0.75rem;">SHA-256 Hash Dokumen Asli</span>
                            <button type="button" class="btn btn-link btn-sm p-0 text-secondary text-decoration-none fw-semibold" style="font-size: 0.7rem;" onclick="copyHash('<?= htmlspecialchars($doc['file_hash']) ?>', this)">
                                Salin
                            </button>
                        </div>
                        <code class="d-block p-1.5 bg-light rounded text-break mt-1" style="font-size: 0.7rem; font-family: monospace;">
                            <?= htmlspecialchars($doc['file_hash']) ?>
                        </code>
                    </li>
                    <?php if (!empty($doc['final_file_hash'])): ?>
                    <li class="border-top pt-2">
                        <div class="d-flex align-items-center justify-content-between">
                            <span class="text-success fw-bold d-block" style="font-size: 0.75rem;">
                                <i class="bi bi-patch-check-fill me-1"></i>SHA-256 Hash Final (Stamping)
                            </span>
                            <button type="button" class="btn btn-link btn-sm p-0 text-success text-decoration-none fw-semibold" style="font-size: 0.7rem;" onclick="copyHash('<?= htmlspecialchars($doc['final_file_hash']) ?>', this)">
                                Salin
                            </button>
                        </div>
                        <code class="d-block p-1.5 bg-success-subtle text-success rounded text-break mt-1" style="font-size: 0.7rem; font-family: monospace;">
                            <?= htmlspecialchars($doc['final_file_hash']) ?>
                        </code>
                        <span class="text-muted d-block mt-0.5" style="font-size: 0.68rem;">
                            Nilai integritas kriptografis berkas PDF setelah dibubuhi tanda tangan fisik & stempel QR Code.
                        </span>
                    </li>
                    <?php endif; ?>
                    <?php if (!empty($doc['reviewed_at'])): ?>
                    <li class="border-top pt-2">
                        <span class="text-muted d-block" style="font-size: 0.75rem;">Status Review Admin</span>
                        <?php $isAdminRejected = ($doc['status'] === 'rejected' && !$declinedSigner); ?>
                        <span class="fw-semibold <?= $isAdminRejected ? 'text-danger' : 'text-success' ?>">
                            <i class="bi <?= $isAdminRejected ? 'bi-x-circle' : 'bi-check2-circle' ?> me-1"></i>
                            <?= $isAdminRejected ? 'Ditolak' : 'Disetujui' ?>
                        </span>
                        <div class="text-muted mt-0.5" style="font-size: 0.7rem;">
                            Oleh: <strong><?= htmlspecialchars($doc['reviewer_name'] ?? 'Administrator') ?></strong>
                            <br><?= date('d F Y, H:i', strtotime($doc['reviewed_at'])) ?> WIB
                        </div>
                    </li>
                    <?php elseif ($doc['status'] === 'pending_review'): ?>
                    <li class="border-top pt-2">
                        <span class="text-muted d-block" style="font-size: 0.75rem;">Status Review Admin</span>
                        <span class="fw-semibold text-warning">
                            <i class="bi bi-hourglass-split me-1"></i> Menunggu Peninjauan Admin
                        </span>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <!-- Jejak Audit Trail Dokumen -->
        <div class="card-clean mb-4">
            <div class="card-clean-header py-2.5 px-3 d-flex justify-content-between align-items-center">
                <span class="fw-semibold text-dark small">
                    <i class="bi bi-clock-history text-muted me-1"></i> Jejak Audit Dokumen
                </span>
                <span class="badge bg-light text-muted border"><?= !empty($auditLog) ? count($auditLog) : 0 ?> Catatan</span>
            </div>
            <div class="card-clean-body p-3">
                <?php if (empty($auditLog)): ?>
                    <div class="text-center text-muted py-3 small">
                        Belum ada riwayat aktivitas tercatat.
                    </div>
                <?php else: ?>
                    <div class="timeline position-relative" style="padding-left: 1.25rem; border-left: 2px solid var(--border-subtle); margin-left: 0.5rem;">
                        <?php 
                            $actionMap = [
                                'document.create'          => ['label' => 'Dokumen Dibuat', 'badge' => 'bg-secondary', 'icon' => 'bi-file-earmark-plus'],
                                'document.submit'          => ['label' => 'Diajukan ke Admin', 'badge' => 'bg-info text-dark', 'icon' => 'bi-send'],
                                'document.approve'         => ['label' => 'Disetujui Administrator', 'badge' => 'bg-success', 'icon' => 'bi-check2-circle'],
                                'document.reject'          => ['label' => 'Ditolak Administrator', 'badge' => 'bg-danger', 'icon' => 'bi-x-circle'],
                                'document.signed'          => ['label' => 'Tanda Tangan Dibubuhkan', 'badge' => 'bg-primary', 'icon' => 'bi-pen-fill'],
                                'document.step_advanced'   => ['label' => 'Giliran Jenjang Dialihkan', 'badge' => 'bg-info text-dark', 'icon' => 'bi-arrow-right-circle'],
                                'document.completed'       => ['label' => 'Alur Pengesahan Selesai', 'badge' => 'bg-success', 'icon' => 'bi-patch-check-fill'],
                                'document.declined'        => ['label' => 'Tanda Tangan Ditolak Signer', 'badge' => 'bg-danger', 'icon' => 'bi-shield-x'],
                                'document.final_generated' => ['label' => 'PDF Final Berstempel Diterbitkan', 'badge' => 'bg-success', 'icon' => 'bi-file-earmark-check-fill'],
                                'document.download'        => ['label' => 'Berkas Diunduh', 'badge' => 'bg-secondary', 'icon' => 'bi-download'],
                                'document.delete'          => ['label' => 'Dokumen Dihapus', 'badge' => 'bg-danger', 'icon' => 'bi-trash'],
                            ];
                        ?>
                        <?php foreach ($auditLog as $log): ?>
                            <?php 
                                $details = !empty($log['details']) ? json_decode($log['details'], true) : [];
                                $actionConfig = $actionMap[$log['action']] ?? [
                                    'label' => ucwords(str_replace(['document.', 'civitas.', '.'], ['', '', ' '], $log['action'])),
                                    'badge' => 'bg-secondary',
                                    'icon'  => 'bi-circle-fill'
                                ];
                                $dotColor = strpos($actionConfig['badge'], 'danger') !== false ? 'bg-danger' : 
                                           (strpos($actionConfig['badge'], 'primary') !== false ? 'bg-primary' : 
                                           (strpos($actionConfig['badge'], 'info') !== false ? 'bg-info' : 'bg-success'));
                            ?>
                            <div class="mb-3 position-relative">
                                <div class="position-absolute rounded-circle <?= $dotColor ?> border border-white" 
                                     style="width: 10px; height: 10px; left: -1.6rem; top: 4px;"></div>
                                <div class="fw-bold text-dark small d-flex align-items-center gap-1">
                                    <i class="bi <?= $actionConfig['icon'] ?> text-muted" style="font-size: 0.8rem;"></i>
                                    <span><?= htmlspecialchars($actionConfig['label']) ?></span>
                                </div>
                                <div class="text-muted" style="font-size: 0.72rem;">
                                    Oleh: <strong><?= htmlspecialchars($log['user_name'] ?? 'Sistem') ?></strong>
                                    &bull; <?= date('d/m/Y H:i', strtotime($log['created_at'])) ?>
                                </div>
                                <?php if (!empty($details)): ?>
                                    <div class="mt-1" style="font-size: 0.7rem;">
                                        <?php if (!empty($details['reason'])): ?>
                                            <div class="text-danger bg-danger-subtle rounded p-1.5 border border-danger-subtle">
                                                <i class="bi bi-chat-left-text me-1"></i><strong>Alasan:</strong> <?= htmlspecialchars($details['reason']) ?>
                                            </div>
                                        <?php elseif (isset($details['step_from'], $details['step_to'])): ?>
                                            <span class="badge bg-light text-dark border">
                                                Step <?= (int)$details['step_from'] ?> &rarr; Step <?= (int)$details['step_to'] ?>
                                            </span>
                                        <?php elseif (!empty($details['signer_name'])): ?>
                                            <?php $methodBadge = !empty($details['method']) ? ' (' . ucfirst($details['method']) . ')' : ''; ?>
                                            <span class="badge bg-light text-dark border">
                                                Step <?= (int)($details['step_order'] ?? 1) ?> &bull; <?= htmlspecialchars($details['signer_name']) . $methodBadge ?>
                                            </span>
                                        <?php elseif (!empty($details['final_file_hash'])): ?>
                                            <div class="text-success bg-light rounded p-1 font-monospace text-truncate" title="SHA-256 Hash Final">
                                                <i class="bi bi-patch-check-fill me-1"></i>Hash: <?= substr($details['final_file_hash'], 0, 16) ?>...
                                            </div>
                                        <?php elseif (isset($details['is_final'])): ?>
                                            <span class="badge <?= !empty($details['is_final']) ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-light text-muted border' ?>">
                                                <i class="bi <?= !empty($details['is_final']) ? 'bi-file-earmark-check' : 'bi-file-earmark-pdf' ?> me-0.5"></i>
                                                <?= !empty($details['is_final']) ? 'Dokumen Final Berstempel' : 'Berkas Asli' ?>
                                            </span>
                                        <?php elseif (!empty($details['final_step'])): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">
                                                <i class="bi bi-check-all me-1"></i>Semua <?= (int)$details['final_step'] ?> Tahap Lengkap
                                            </span>
                                        <?php elseif (isset($details['status_from'], $details['status_to'])): ?>
                                            <span class="badge bg-light text-muted border">
                                                <?= htmlspecialchars($details['status_from']) ?> &rarr; <?= htmlspecialchars($details['status_to']) ?>
                                            </span>
                                        <?php elseif (!empty($details['file_name'])): ?>
                                            <span class="text-muted">
                                                <i class="bi bi-file-earmark-pdf me-0.5"></i><?= htmlspecialchars($details['file_name']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if ($isAdmin && $doc['status'] === 'pending_review'): ?>
<!-- Modal Konfirmasi Persetujuan Dokumen -->
<div class="modal fade" id="modalApprove" tabindex="-1" aria-labelledby="modalApproveLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fs-6 fw-bold" id="modalApproveLabel">
                    <i class="bi bi-check2-circle me-1.5"></i> Konfirmasi Persetujuan Dokumen
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= BASE_URL ?>/documents/<?= htmlspecialchars($doc['id']) ?>/approve" method="POST">
                <div class="modal-body p-4">
                    <p class="mb-3 text-dark">
                        Apakah Anda yakin ingin menyetujui dokumen <strong>"<?= htmlspecialchars($doc['title']) ?>"</strong>?
                    </p>
                    <div class="alert alert-info py-2.5 px-3 small mb-0 d-flex align-items-start gap-2" style="border-radius: var(--radius-md);">
                        <i class="bi bi-info-circle-fill mt-0.5 text-primary"></i>
                        <div>
                            Setelah disetujui, alur penandatanganan berjenjang akan segera dimulai. Berkas akan dialihkan ke status <strong>Proses Tanda Tangan</strong> dan penandatangan pada <strong>Step 1</strong> akan menerima giliran aktif di dashboard mereka.
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2 px-3">
                    <button type="button" class="btn btn-subtle btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success btn-sm px-3 fw-semibold">
                        <i class="bi bi-check-lg me-1"></i> Ya, Setujui Dokumen
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Tolak Dokumen -->
<div class="modal fade" id="modalReject" tabindex="-1" aria-labelledby="modalRejectLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fs-6 fw-bold" id="modalRejectLabel">
                    <i class="bi bi-x-octagon-fill me-1.5"></i> Tolak Pengajuan Dokumen
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= BASE_URL ?>/documents/<?= htmlspecialchars($doc['id']) ?>/reject" method="POST">
                <div class="modal-body p-4">
                    <div class="alert alert-danger py-2.5 px-3 small mb-3 d-flex align-items-start gap-2" style="border-radius: var(--radius-md);">
                        <i class="bi bi-exclamation-triangle-fill mt-0.5"></i>
                        <div>
                            <strong>Peringatan Kebijakan Kampus (ADR-08):</strong> Dokumen yang ditolak berstatus <em>Arsip Batal Permanen</em> dan tidak dapat diedit kembali. Pengaju harus membuat pengajuan baru.
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="rejection_reason" class="form-label small fw-semibold text-dark">
                            Alasan Penolakan <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control form-control-sm" id="rejection_reason" name="rejection_reason" rows="3" 
                                  placeholder="Tuliskan alasan penolakan secara jelas (contoh: Posisi slot tanda tangan menimpa teks surat, lampiran belum lengkap, dll)..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2 px-3">
                    <button type="button" class="btn btn-subtle btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger btn-sm px-3 fw-semibold">
                        <i class="bi bi-x-lg me-1"></i> Konfirmasi Tolak Dokumen
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
function copyHash(text, btn) {
    if (!navigator.clipboard) {
        const t = document.createElement("textarea");
        t.value = text;
        t.style.position = "fixed";
        t.style.opacity = "0";
        document.body.appendChild(t);
        t.select();
        document.execCommand('copy');
        document.body.removeChild(t);
    } else {
        navigator.clipboard.writeText(text);
    }
    const orig = btn.innerText;
    btn.innerText = 'Tersalin!';
    setTimeout(() => { btn.innerText = orig; }, 2000);
}

// ─────────────────────────────────────────────────────────────
// Integrasi Mozilla PDF.js + Overlay Slot & Pratinjau Tanda Tangan
// ─────────────────────────────────────────────────────────────
(function () {
    const pdfUrl = <?= json_encode(BASE_URL . '/documents/' . $doc['id'] . '/preview') ?>;
    const initialPage = <?= (int) $initialPreviewPage ?>;

    const loadingSpinner = document.getElementById('detailPdfLoadingSpinner');
    const fallbackAlert = document.getElementById('detailPdfFallbackAlert');
    const stageContainer = document.getElementById('detailPdfStageContainer');
    const slotCanvasContainer = document.getElementById('detailSlotCanvasContainer');
    const renderCanvas = document.getElementById('pdfDetailRenderCanvas');
    const fallbackWatermark = document.getElementById('detailFallbackWatermark');
    const overlayContainer = document.getElementById('detailSlotOverlayContainer');
    const btnPrevPage = document.getElementById('btnDetailPrevPage');
    const btnNextPage = document.getElementById('btnDetailNextPage');
    const pageNumDisplay = document.getElementById('detailPageNumDisplay');
    const pageTotalDisplay = document.getElementById('detailPageTotalDisplay');
    const btnToggleOverlay = document.getElementById('btnToggleSlotOverlay');
    const toggleOverlayLabel = document.getElementById('toggleSlotOverlayLabel');

    let pdfDoc = null;
    let currentPage = Math.max(1, initialPage);
    let totalPages = 1;
    let isRendering = false;
    let pageRenderingQueue = null;
    let overlayVisible = true;
    let pendingHighlightId = null;

    function updateSlotVisibilityForPage(page) {
        if (!overlayContainer) return;
        const items = overlayContainer.querySelectorAll('[data-page]');
        items.forEach(function (el) {
            const p = parseInt(el.getAttribute('data-page'), 10) || 1;
            if (p === page) {
                el.classList.remove('d-none');
                el.style.display = '';
            } else {
                el.classList.add('d-none');
                el.style.display = 'none';
            }
        });

        if (pendingHighlightId) {
            const targetEl = document.getElementById(pendingHighlightId);
            pendingHighlightId = null;
            if (targetEl && !targetEl.classList.contains('d-none')) {
                targetEl.style.boxShadow = '0 0 0 4px rgba(16, 185, 129, 0.55), 0 6px 16px rgba(0, 0, 0, 0.2)';
                setTimeout(function () {
                    targetEl.style.boxShadow = '';
                }, 2200);
            }
        }
    }

    function initFallbackMode() {
        if (loadingSpinner) loadingSpinner.classList.add('d-none');
        if (fallbackAlert) fallbackAlert.classList.remove('d-none');
        if (stageContainer) {
            stageContainer.classList.remove('d-none');
            if (slotCanvasContainer) {
                slotCanvasContainer.style.width = '500px';
                slotCanvasContainer.style.height = '707px';
            }
            if (renderCanvas) renderCanvas.style.display = 'none';
            if (fallbackWatermark) {
                fallbackWatermark.classList.remove('d-none');
                fallbackWatermark.classList.add('d-flex');
            }
        }
        // Jika dokumen completed namun fallback aktif, tampilkan isi 2 kolom pada slot tertanda
        document.querySelectorAll('.completed-fallback-inner').forEach(function (el) {
            el.classList.remove('d-none');
            el.classList.add('d-flex');
            if (el.parentElement) {
                el.parentElement.classList.add('bg-white', 'shadow-xs');
            }
        });

        let maxP = currentPage;
        if (overlayContainer) {
            overlayContainer.querySelectorAll('[data-page]').forEach(function (el) {
                const p = parseInt(el.getAttribute('data-page'), 10) || 1;
                if (p > maxP) maxP = p;
            });
        }
        totalPages = Math.max(1, maxP);
        if (pageTotalDisplay) pageTotalDisplay.textContent = totalPages;
        if (pageNumDisplay) pageNumDisplay.textContent = currentPage;
        if (btnPrevPage) btnPrevPage.disabled = (currentPage <= 1);
        if (btnNextPage) btnNextPage.disabled = (currentPage >= totalPages);
        updateSlotVisibilityForPage(currentPage);
    }

    function renderPage(num) {
        if (!pdfDoc) {
            currentPage = Math.max(1, Math.min(totalPages, num));
            if (pageNumDisplay) pageNumDisplay.textContent = currentPage;
            if (btnPrevPage) btnPrevPage.disabled = (currentPage <= 1);
            if (btnNextPage) btnNextPage.disabled = (currentPage >= totalPages);
            updateSlotVisibilityForPage(currentPage);
            return;
        }

        if (isRendering) {
            pageRenderingQueue = num;
            return;
        }
        isRendering = true;
        currentPage = num;

        if (pageNumDisplay) pageNumDisplay.textContent = num;
        if (btnPrevPage) btnPrevPage.disabled = (num <= 1);
        if (btnNextPage) btnNextPage.disabled = (num >= totalPages);

        pdfDoc.getPage(num).then(function (page) {
            const parentWidth = (stageContainer && stageContainer.parentElement)
                ? (stageContainer.parentElement.clientWidth || 620)
                : 620;
            const targetWidth = Math.min(680, Math.max(320, parentWidth - 24));
            const unscaledViewport = page.getViewport({ scale: 1.0 });
            const scale = targetWidth / unscaledViewport.width;
            const viewport = page.getViewport({ scale: scale });

            const rCtx = renderCanvas.getContext('2d');
            const dpr = window.devicePixelRatio || 1;

            renderCanvas.width = Math.floor(viewport.width * dpr);
            renderCanvas.height = Math.floor(viewport.height * dpr);
            renderCanvas.style.width = Math.floor(viewport.width) + 'px';
            renderCanvas.style.height = Math.floor(viewport.height) + 'px';

            slotCanvasContainer.style.width = Math.floor(viewport.width) + 'px';
            slotCanvasContainer.style.height = Math.floor(viewport.height) + 'px';

            const renderContext = {
                canvasContext: rCtx,
                viewport: viewport,
                transform: dpr !== 1 ? [dpr, 0, 0, dpr, 0, 0] : null
            };

            page.render(renderContext).promise.then(function () {
                isRendering = false;
                if (pageRenderingQueue !== null) {
                    const nextNum = pageRenderingQueue;
                    pageRenderingQueue = null;
                    renderPage(nextNum);
                } else {
                    updateSlotVisibilityForPage(currentPage);
                }
            }).catch(function (err) {
                if (err.name !== 'RenderingCancelledException') {
                    console.error('Detail PDF render error:', err);
                }
                isRendering = false;
            });
        });
    }

    if (window.pdfjsLib) {
        try {
            pdfjsLib.GlobalWorkerOptions.workerSrc = <?= json_encode(BASE_URL . '/assets/js/pdf.worker.min.js') ?>;
        } catch (e) {
            console.warn('Worker configuration notice:', e);
        }

        pdfjsLib.getDocument({ url: pdfUrl, withCredentials: true }).promise.then(function (loadedPdf) {
            pdfDoc = loadedPdf;
            totalPages = loadedPdf.numPages;
            if (pageTotalDisplay) pageTotalDisplay.textContent = totalPages;

            currentPage = Math.min(totalPages, Math.max(1, initialPage));

            if (loadingSpinner) loadingSpinner.classList.add('d-none');
            if (stageContainer) stageContainer.classList.remove('d-none');

            renderPage(currentPage);
        }).catch(function (error) {
            console.warn('PDF.js gagal memuat berkas di halaman detail:', error);
            initFallbackMode();
        });
    } else {
        initFallbackMode();
    }

    if (btnPrevPage) {
        btnPrevPage.addEventListener('click', function () {
            if (currentPage > 1) renderPage(currentPage - 1);
        });
    }

    if (btnNextPage) {
        btnNextPage.addEventListener('click', function () {
            if (currentPage < totalPages) renderPage(currentPage + 1);
        });
    }

    if (btnToggleOverlay && overlayContainer) {
        btnToggleOverlay.addEventListener('click', function () {
            overlayVisible = !overlayVisible;
            overlayContainer.classList.toggle('d-none', !overlayVisible);
            if (toggleOverlayLabel) {
                toggleOverlayLabel.textContent = overlayVisible ? 'Sembunyikan Slot' : 'Tampilkan Slot';
            }
        });
    }

    window.addEventListener('resize', function () {
        if (pdfDoc && !isRendering) {
            renderPage(currentPage);
        }
    });

    window.jumpToPreviewPage = function (pageNumber, slotElementId) {
        const targetP = Math.max(1, Math.min(totalPages || 999, parseInt(pageNumber, 10) || 1));
        if (!overlayVisible && overlayContainer) {
            overlayVisible = true;
            overlayContainer.classList.remove('d-none');
            if (toggleOverlayLabel) toggleOverlayLabel.textContent = 'Sembunyikan Slot';
        }
        pendingHighlightId = slotElementId || null;
        const previewSection = document.getElementById('pdfPreviewSection');
        if (previewSection) {
            previewSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
        renderPage(targetP);
    };
})();
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>

