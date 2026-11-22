<?php
$title = 'Detail Dokumen — ' . htmlspecialchars($doc['title'], ENT_QUOTES, 'UTF-8') . ' — SIGMA';
require_once __DIR__ . '/../layout/header.php';

// Format status badge helper (Token SIGMA)
$statusBadges = [
    'draft'          => ['class' => 'badge-sigma-neutral', 'label' => 'Draft / Belum Diajukan', 'icon' => 'bi-pencil-square'],
    'pending_review' => ['class' => 'badge-sigma-warning', 'label' => 'Menunggu Review Admin', 'icon' => 'bi-hourglass-split'],
    'approved'       => ['class' => 'badge-sigma-violet',  'label' => 'Disetujui Admin', 'icon' => 'bi-check2-circle'],
    'signing'        => ['class' => 'badge-sigma-purple',  'label' => 'Proses Penandatanganan', 'icon' => 'bi-pen-fill'],
    'completed'      => ['class' => 'badge-sigma-success', 'label' => 'Dokumen Selesai (Sah)', 'icon' => 'bi-patch-check-fill'],
    'rejected'       => ['class' => 'badge-sigma-danger',  'label' => 'Dokumen Ditolak', 'icon' => 'bi-x-octagon-fill'],
];
$curBadge = $statusBadges[$doc['status']] ?? ['class' => 'badge-sigma-neutral', 'label' => ucfirst((string)$doc['status']), 'icon' => 'bi-circle'];

$signerBadges = [
    'waiting'  => ['class' => 'badge-sigma-neutral', 'label' => 'Menunggu Giliran', 'icon' => 'bi-clock'],
    'pending'  => ['class' => 'badge-sigma-warning', 'label' => 'Giliran Aktif', 'icon' => 'bi-pen'],
    'signed'   => ['class' => 'badge-sigma-success', 'label' => 'Sudah Ditandatangani', 'icon' => 'bi-check-circle-fill'],
    'declined' => ['class' => 'badge-sigma-danger',  'label' => 'Menolak Tanda Tangan', 'icon' => 'bi-x-circle-fill'],
];

// Deteksi jika ada signer yang menolak
$declinedSigner = null;
foreach ($signers as $s) {
    if (($s['status'] ?? '') === 'declined') {
        $declinedSigner = $s;
        break;
    }
}

// Indeks tanda tangan terpasang
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

// Halaman awal pratinjau PDF secara cerdas
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

$isCanSignNow = (!$isAdmin && $mySigner && $doc['status'] === 'signing' && $mySigner['status'] === 'pending' && (int)$mySigner['step_order'] === (int)$doc['current_step']);
?>

<!-- Pustaka Mozilla PDF.js untuk pratinjau lembar dokumen + overlay slot & tanda tangan (ADR-09) -->
<script src="<?= BASE_URL ?>/assets/js/pdf.min.js"></script>

<!-- Back Link -->
<div class="mb-3">
    <a href="<?= BASE_URL ?>/documents" class="text-decoration-none small d-inline-flex align-items-center gap-2" style="color: var(--text-muted);">
        <i class="bi bi-arrow-left"></i>
        <span>Kembali ke Dashboard Dokumen</span>
    </a>
</div>

<!-- Header Detail Dokumen + Stepper 4 Langkah -->
<div class="card-sigma mb-4">
    <div class="card-sigma-body p-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3">
            <div>
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                    <span class="badge-sigma <?= $curBadge['class'] ?>">
                        <i class="bi <?= $curBadge['icon'] ?>"></i>
                        <?= htmlspecialchars($curBadge['label'], ENT_QUOTES, 'UTF-8') ?>
                    </span>
                    <span class="badge-sigma badge-sigma-purple">
                        <i class="bi <?= $doc['signature_type'] === 'single' ? 'bi-person-check' : 'bi-diagram-3' ?>"></i>
                        <?= $doc['signature_type'] === 'single' ? 'Tanda Tangan Mandiri' : 'Tanda Tangan Berjenjang' ?>
                    </span>
                    <span class="small" style="color: var(--text-muted);">
                        Diajukan oleh: <strong style="color: var(--text-heading);"><?= htmlspecialchars($doc['uploader_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                    </span>
                </div>
                <h1 class="h4 fw-bold mb-1" style="color: var(--text-heading);"><?= htmlspecialchars($doc['title'], ENT_QUOTES, 'UTF-8') ?></h1>
                <?php if (!empty($doc['description'])): ?>
                    <p class="mb-0 small" style="color: var(--text-muted);"><?= nl2br(htmlspecialchars($doc['description'], ENT_QUOTES, 'UTF-8')) ?></p>
                <?php endif; ?>
            </div>

            <!-- Aksi Ringkas Header -->
            <div class="d-flex flex-wrap gap-2 flex-shrink-0">
                <?php if ($doc['status'] === 'completed'): ?>
                    <a href="<?= BASE_URL ?>/documents/<?= htmlspecialchars($doc['id'], ENT_QUOTES, 'UTF-8') ?>/download" class="btn-sigma-navy btn-sigma-sm">
                        <i class="bi bi-file-earmark-check-fill"></i> Unduh PDF Final
                    </a>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/documents/<?= htmlspecialchars($doc['id'], ENT_QUOTES, 'UTF-8') ?>/download" class="btn-sigma-outline btn-sigma-sm">
                        <i class="bi bi-download"></i> Unduh Berkas Asli
                    </a>
                <?php endif; ?>

                <?php if ($isCanSignNow): ?>
                    <a href="<?= BASE_URL ?>/documents/<?= htmlspecialchars($doc['id'], ENT_QUOTES, 'UTF-8') ?>/sign" class="btn-sigma-purple btn-sigma-sm">
                        <i class="bi bi-pen-fill"></i> Tanda Tangani Sekarang
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Banner Penolakan (Jika status rejected) -->
        <?php if ($doc['status'] === 'rejected'): ?>
            <div class="alert alert-danger alert-sigma mt-4 mb-0 d-flex align-items-start gap-3" role="alert"
                 style="background: var(--sigma-danger-bg); border-color: #fecaca; color: var(--sigma-danger-text-dark);">
                <i class="bi bi-x-circle-fill fs-5 mt-1"></i>
                <div>
                    <div class="fw-bold mb-1">Pengajuan Dokumen Ini Ditolak (Arsip Batal)</div>
                    <p class="mb-1 small">
                        <strong>Alasan Penolakan:</strong> <?= nl2br(htmlspecialchars($doc['rejection_reason'] ?: 'Tidak ada keterangan spesifik.', ENT_QUOTES, 'UTF-8')) ?>
                    </p>
                    <?php if ($declinedSigner): ?>
                        <small class="d-block mb-1 opacity-75">
                            Ditolak oleh penandatangan: <strong><?= htmlspecialchars($declinedSigner['signer_name'], ENT_QUOTES, 'UTF-8') ?></strong> (<?= htmlspecialchars($declinedSigner['role_title'] ?: ($declinedSigner['signer_position'] ?: 'Penandatangan'), ENT_QUOTES, 'UTF-8') ?>)
                        </small>
                    <?php elseif (!empty($doc['reviewed_at'])): ?>
                        <small class="d-block mb-1 opacity-75">
                            Ditolak saat review oleh: <strong><?= htmlspecialchars($doc['reviewer_name'] ?? 'Administrator', ENT_QUOTES, 'UTF-8') ?></strong> pada <?= date('d F Y, H:i', strtotime($doc['reviewed_at'])) ?> WIB
                        </small>
                    <?php endif; ?>
                    <small class="d-block mb-2 opacity-75">
                        Sesuai kebijakan SIGMA (ADR-08), dokumen yang ditolak bersifat arsip batal permanen dan tidak dapat diedit kembali. Silakan ajukan berkas baru.
                    </small>
                    <?php if ($isOwner): ?>
                        <a href="<?= BASE_URL ?>/documents/create" class="btn-sigma-danger btn-sigma-sm">
                            <i class="bi bi-plus-lg"></i> Buat Pengajuan Baru
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Stepper Progres 4 Langkah (Figma 230:682) -->
        <?php if ($doc['signature_type'] === 'sequential'): ?>
            <?php
                $s1Class = 'done';
                $s1Icon  = 'bi-check-lg';

                $s2Class = '';
                $s2Icon  = '2';
                if ($doc['status'] === 'pending_review') {
                    $s2Class = 'active';
                    $s2Icon  = '<i class="bi bi-hourglass-split"></i>';
                } elseif (in_array($doc['status'], ['approved', 'signing', 'completed']) || ($doc['status'] === 'rejected' && $declinedSigner)) {
                    $s2Class = 'done';
                    $s2Icon  = '<i class="bi bi-check-lg"></i>';
                } elseif ($doc['status'] === 'rejected' && !$declinedSigner) {
                    $s2Class = 'danger';
                    $s2Icon  = '<i class="bi bi-x-lg"></i>';
                }

                $s3Class = '';
                $s3Icon  = '3';
                if ($doc['status'] === 'signing') {
                    $s3Class = 'active';
                    $s3Icon  = '<i class="bi bi-pen-fill"></i>';
                } elseif ($doc['status'] === 'completed') {
                    $s3Class = 'done';
                    $s3Icon  = '<i class="bi bi-check-lg"></i>';
                } elseif ($doc['status'] === 'rejected' && $declinedSigner) {
                    $s3Class = 'danger';
                    $s3Icon  = '<i class="bi bi-x-lg"></i>';
                }

                $s4Class = ($doc['status'] === 'completed') ? 'done' : '';
                $s4Icon  = ($doc['status'] === 'completed') ? '<i class="bi bi-patch-check-fill"></i>' : '4';
            ?>
            <div class="sigma-stepper-bar mt-4">
                <div class="small fw-bold text-uppercase mb-3" style="font-size: 0.7rem; letter-spacing: 0.06em; color: var(--sigma-purple-700);">
                    Alur Proses Berjenjang (4 Tahap)
                </div>
                <div class="sigma-stepper-track">
                    <div class="sigma-step-item <?= $s1Class ?>">
                        <div class="sigma-step-num"><i class="bi <?= $s1Icon ?>"></i></div>
                        <div>
                            <div class="small fw-bold" style="color: var(--text-heading);">1. Upload & Atur Slot</div>
                            <div style="font-size: 0.72rem; color: var(--text-muted);">Penetapan posisi koordinat %</div>
                        </div>
                    </div>
                    <div class="sigma-step-item <?= $s2Class ?>">
                        <div class="sigma-step-num"><?= $s2Icon ?></div>
                        <div>
                            <div class="small fw-bold" style="color: var(--text-heading);">2. Peninjauan Admin</div>
                            <div style="font-size: 0.72rem; color: var(--text-muted);">Verifikasi kelayakan berkas</div>
                        </div>
                    </div>
                    <div class="sigma-step-item <?= $s3Class ?>">
                        <div class="sigma-step-num"><?= $s3Icon ?></div>
                        <div>
                            <div class="small fw-bold" style="color: var(--text-heading);">3. Penandatanganan</div>
                            <div style="font-size: 0.72rem; color: var(--text-muted);">Giliran aktif: Tahap <?= (int)$doc['current_step'] ?></div>
                        </div>
                    </div>
                    <div class="sigma-step-item <?= $s4Class ?>">
                        <div class="sigma-step-num"><?= $s4Icon ?></div>
                        <div>
                            <div class="small fw-bold" style="color: var(--text-heading);">4. Dokumen Selesai</div>
                            <div style="font-size: 0.72rem; color: var(--text-muted);">PDF final berstempel & QR sah</div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Layout 2 Kolom Figma (230:286) -->
<div class="row g-4">
    <!-- Kolom Kiri (8/12): Penandatangan, Rekam TTD, & Pratinjau PDF -->
    <div class="col-lg-8">
        <!-- Daftar Penandatangan Berjenjang -->
        <?php if ($doc['signature_type'] === 'sequential'): ?>
        <div class="card-sigma mb-4">
            <div class="card-sigma-header">
                <span class="fw-bold small d-flex align-items-center gap-2" style="color: var(--text-heading);">
                    <i class="bi bi-people-fill text-sigma-purple"></i>
                    <span>Daftar Penandatangan Berjenjang</span>
                </span>
                <span class="badge-sigma badge-sigma-purple">
                    <?= count($signers) ?> Penandatangan
                </span>
            </div>

            <div class="table-responsive">
                <table class="table-sigma">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 82px;">Urutan</th>
                            <th>Nama Penandatangan</th>
                            <th>Jabatan / Peran</th>
                            <th>Posisi Slot</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($signers as $s): ?>
                            <?php
                                $sBadge = $signerBadges[$s['status']] ?? ['class' => 'badge-sigma-neutral', 'label' => ucfirst((string)$s['status']), 'icon' => 'bi-circle'];
                                $isCurrentStep = ($doc['status'] === 'signing' && (int)$s['step_order'] === (int)$doc['current_step']);
                            ?>
                            <tr <?= $isCurrentStep ? 'style="background-color: #fffbeb;"' : '' ?>>
                                <td class="text-center">
                                    <span class="badge-sigma <?= $isCurrentStep ? 'badge-sigma-warning' : 'badge-sigma-neutral' ?>">
                                        Tahap <?= (int)$s['step_order'] ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-bold small" style="color: var(--text-heading);"><?= htmlspecialchars($s['signer_name'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <div style="font-size: 0.74rem; color: var(--text-muted);">
                                        <?= htmlspecialchars($s['signer_identifier'] ?: '-', ENT_QUOTES, 'UTF-8') ?> &bull; <?= ucfirst((string)($s['signer_type'] ?? '')) ?>
                                    </div>
                                </td>
                                <td class="small" style="color: var(--text-muted);">
                                    <?= htmlspecialchars($s['role_title'] ?: ($s['signer_position'] ?: '-'), ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td class="small">
                                    <?php if ((float)$s['x_percent'] > 0 || (float)$s['y_percent'] > 0): ?>
                                        <button type="button"
                                                class="btn-sigma-outline btn-sigma-sm py-1 px-2"
                                                style="font-size: 0.72rem; min-height: 28px;"
                                                onclick="jumpToPreviewPage(<?= (int) ($s['page_number'] ?: 1) ?>, 'detailSlot_<?= htmlspecialchars($s['id'], ENT_QUOTES, 'UTF-8') ?>')"
                                                title="Klik untuk melihat posisi kotak slot pada pratinjau PDF di bawah">
                                            <i class="bi bi-geo-alt-fill text-sigma-purple"></i> Hal. <?= (int) $s['page_number'] ?> (<?= (float)$s['x_percent'] ?>%, <?= (float)$s['y_percent'] ?>%)
                                        </button>
                                    <?php else: ?>
                                        <span class="badge-sigma badge-sigma-danger">
                                            <i class="bi bi-exclamation-circle"></i> Belum diatur
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge-sigma <?= $sBadge['class'] ?>">
                                        <i class="bi <?= $sBadge['icon'] ?>"></i>
                                        <?= htmlspecialchars($sBadge['label'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                    <?php if (!empty($s['signed_at'])): ?>
                                        <div class="mt-1" style="font-size: 0.68rem; color: var(--text-muted);">
                                            <?= date('d M Y, H:i', strtotime($s['signed_at'])) ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($s['decline_reason'])): ?>
                                        <div class="text-danger mt-1" style="font-size: 0.7rem;">
                                            <i class="bi bi-chat-left-text me-1"></i><?= nl2br(htmlspecialchars($s['decline_reason'], ENT_QUOTES, 'UTF-8')) ?>
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
        <div class="card-sigma mb-4">
            <div class="card-sigma-header">
                <span class="fw-bold small d-flex align-items-center gap-2" style="color: var(--text-heading);">
                    <i class="bi bi-pen-fill text-sigma-purple"></i>
                    <span>Rekam Tanda Tangan Terpasang</span>
                </span>
                <span class="badge-sigma badge-sigma-success"><?= count($signatures) ?> Tanda Tangan</span>
            </div>
            <div class="card-sigma-body p-3">
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
                        <div class="d-flex flex-wrap flex-sm-nowrap align-items-center gap-3 border rounded-3 p-3" style="border-color: var(--border-subtle) !important;">
                            <div class="checkerboard-bg rounded border flex-shrink-0 d-flex align-items-center justify-content-center"
                                 style="width: 165px; height: 80px; overflow: hidden;">
                                <div class="d-flex align-items-stretch w-100 h-100 px-2 py-1">
                                    <div class="d-flex flex-column <?= $isQrOnly ? 'justify-content-center' : 'justify-content-end' ?> text-center" style="width: 58%; min-width: 0; line-height: 1.15; padding-right: 3px;">
                                        <?php if (!$isQrOnly): ?>
                                            <div class="position-relative flex-grow-1" style="min-height: 0;">
                                                <img src="<?= BASE_URL ?>/signatures/<?= htmlspecialchars($sig['id'], ENT_QUOTES, 'UTF-8') ?>/preview"
                                                     alt="Tanda tangan <?= htmlspecialchars($sig['signer_name_snapshot'], ENT_QUOTES, 'UTF-8') ?>"
                                                     style="position: absolute; inset: 0; width: 100%; height: 100%; object-fit: contain;">
                                            </div>
                                        <?php endif; ?>
                                        <div class="fw-bold text-truncate" style="font-size: 0.56rem; color: var(--text-heading);">
                                            <?= htmlspecialchars($sig['signer_name_snapshot'], ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                        <div style="border-top: 1px solid #0f172a; margin: 1px 0;"></div>
                                        <?php if ($sigIdent !== '' && $sigIdent !== '-'): ?>
                                            <div class="text-truncate" style="font-size: 0.48rem; color: var(--text-muted);">
                                                <?= htmlspecialchars($sigIdLabel, ENT_QUOTES, 'UTF-8') ?>: <?= htmlspecialchars($sigIdent, ENT_QUOTES, 'UTF-8') ?>
                                            </div>
                                        <?php elseif (!empty($sig['signer_position_snapshot'])): ?>
                                            <div class="text-truncate" style="font-size: 0.48rem; color: var(--text-muted);">
                                                <?= htmlspecialchars($sig['signer_position_snapshot'], ENT_QUOTES, 'UTF-8') ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-center" style="width: 42%;">
                                        <div class="d-flex align-items-center justify-content-center rounded border bg-white"
                                             style="width: 42px; height: 42px; border-color: #bbf7d0 !important; color: var(--sigma-success-text);">
                                            <i class="bi bi-qr-code fs-5"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex-grow-1 min-width-0">
                                <div class="fw-bold small d-flex flex-wrap align-items-center gap-1 mb-1" style="color: var(--text-heading);">
                                    <span><?= htmlspecialchars($sig['signer_name_snapshot'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php if (!empty($sig['step_order'])): ?>
                                        <span class="badge-sigma badge-sigma-neutral py-0 px-2" style="font-size: 0.65rem;">Tahap <?= (int) $sig['step_order'] ?></span>
                                    <?php endif; ?>
                                    <span class="badge-sigma badge-sigma-success py-0 px-2" style="font-size: 0.65rem;">Tertanda</span>
                                    <?php if ($isQrOnly): ?>
                                        <span class="badge-sigma badge-sigma-purple py-0 px-2" style="font-size: 0.65rem;">
                                            <i class="bi bi-qr-code"></i> Hanya QR
                                        </span>
                                    <?php else: ?>
                                        <span class="badge-sigma badge-sigma-violet py-0 px-2" style="font-size: 0.65rem;">
                                            <i class="bi bi-pen"></i> QR + TTD
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div style="font-size: 0.76rem; color: var(--text-muted);">
                                    <?= htmlspecialchars($sig['signer_position_snapshot'] ?: '-', ENT_QUOTES, 'UTF-8') ?>
                                    &bull; <?= date('d F Y, H:i', strtotime($sig['signed_at'])) ?> WIB
                                </div>
                                <div class="d-flex flex-wrap align-items-center gap-2 mt-1" style="font-size: 0.72rem; color: var(--text-muted);">
                                    <span class="text-truncate">
                                        <i class="bi bi-qr-code me-1"></i>Token:
                                        <code class="font-mono-hash px-1 py-0 rounded" style="background: var(--sigma-tint-50); color: var(--sigma-navy);"><?= htmlspecialchars($sig['verification_token'], ENT_QUOTES, 'UTF-8') ?></code>
                                    </span>
                                    <a href="<?= BASE_URL ?>/verify?token=<?= urlencode($sig['verification_token']) ?>" target="_blank"
                                       class="text-decoration-none fw-bold" style="color: var(--sigma-success-text);" title="Buka verifikasi publik tanda tangan ini">
                                        <i class="bi bi-box-arrow-up-right me-1"></i>Verifikasi
                                    </a>
                                    <button type="button"
                                            class="btn btn-link btn-sm p-0 text-decoration-none fw-semibold"
                                            style="font-size: 0.72rem; color: var(--sigma-purple-700);"
                                            onclick="jumpToPreviewPage(<?= $sigPageNo ?>, '<?= htmlspecialchars($sigTargetSlotId, ENT_QUOTES, 'UTF-8') ?>')">
                                        <i class="bi bi-eye-fill me-1"></i>Lihat di PDF (Hal. <?= $sigPageNo ?>)
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
        <div class="card-sigma mb-4" id="pdfPreviewSection">
            <div class="card-sigma-header flex-wrap py-2 px-3">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="fw-bold small d-flex align-items-center gap-2" style="color: var(--text-heading);">
                        <i class="bi bi-file-earmark-pdf text-danger"></i>
                        <span>Pratinjau Lembar Berkas & Slot</span>
                    </span>
                    <?php if ($isCompletedDoc): ?>
                        <span class="badge-sigma badge-sigma-success" style="font-size: 0.7rem;">
                            <i class="bi bi-patch-check-fill"></i> PDF Final Berstempel
                        </span>
                    <?php else: ?>
                        <span class="badge-sigma badge-sigma-purple" style="font-size: 0.7rem;">
                            <i class="bi bi-bounding-box-circles"></i> Pratinjau Slot & TTD Aktif
                        </span>
                    <?php endif; ?>
                </div>

                <!-- Navigasi Halaman & Kontrol Overlay Slot -->
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <div class="btn-group btn-group-sm" role="group" aria-label="Navigasi Halaman PDF">
                        <button type="button" class="btn-sigma-outline px-2 py-1" id="btnDetailPrevPage" disabled title="Halaman Sebelumnya">
                            <i class="bi bi-chevron-left"></i>
                        </button>
                        <span class="btn-sigma-outline px-3 py-1 fw-semibold" style="font-size: 0.76rem; pointer-events: none; background: var(--sigma-tint-50);">
                            Hal <span id="detailPageNumDisplay"><?= (int) $initialPreviewPage ?></span> / <span id="detailPageTotalDisplay">1</span>
                        </span>
                        <button type="button" class="btn-sigma-outline px-2 py-1" id="btnDetailNextPage" disabled title="Halaman Berikutnya">
                            <i class="bi bi-chevron-right"></i>
                        </button>
                    </div>

                    <button type="button" class="btn-sigma-outline btn-sigma-sm py-1 px-2" id="btnToggleSlotOverlay" title="Tampilkan atau sembunyikan kotak batas slot tanda tangan">
                        <i class="bi bi-bounding-box text-sigma-purple"></i> <span id="toggleSlotOverlayLabel">Sembunyikan Slot</span>
                    </button>

                    <a href="<?= BASE_URL ?>/documents/<?= htmlspecialchars($doc['id'], ENT_QUOTES, 'UTF-8') ?>/preview" target="_blank" class="btn-sigma-outline btn-sigma-sm py-1 px-2" title="Buka berkas PDF di tab baru">
                        <i class="bi bi-arrows-fullscreen"></i> Layar Penuh
                    </a>
                </div>
            </div>

            <!-- Bar Pintasan Lompat ke Slot Penandatangan -->
            <?php if (!empty($signers)): ?>
            <div class="px-3 py-2 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2" style="background: var(--sigma-tint-50); border-color: var(--border-subtle) !important;">
                <div class="d-flex align-items-center flex-wrap gap-2">
                    <span class="fw-semibold" style="font-size: 0.74rem; color: var(--text-muted);">
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
                                $pillStyle = 'background: var(--sigma-success-bg); color: var(--sigma-success-text); border: 1px solid #bbf7d0;';
                                $pillIcon  = 'bi-check-circle-fill';
                            } elseif ($isActiveTurn) {
                                $pillStyle = 'background: var(--sigma-warning-bg); color: var(--sigma-warning-text); border: 1px solid #fde68a;';
                                $pillIcon  = 'bi-pen-fill';
                            } elseif (!$hasCoord) {
                                $pillStyle = 'background: var(--sigma-danger-bg); color: var(--sigma-danger-text); border: 1px solid #fecaca;';
                                $pillIcon  = 'bi-exclamation-circle';
                            } else {
                                $pillStyle = 'background: #ffffff; color: var(--sigma-navy); border: 1px solid var(--border-strong);';
                                $pillIcon  = 'bi-bounding-box';
                            }
                        ?>
                        <button type="button"
                                class="btn btn-sm py-1 px-2 fw-medium rounded-pill"
                                style="font-size: 0.72rem; <?= $pillStyle ?>"
                                onclick="jumpToPreviewPage(<?= $sPage ?>, 'detailSlot_<?= htmlspecialchars($s['id'], ENT_QUOTES, 'UTF-8') ?>')"
                                title="Klik untuk melihat slot Step <?= (int) $s['step_order'] ?> pada Halaman <?= $sPage ?>">
                            <i class="bi <?= $pillIcon ?> me-1"></i>
                            Tahap <?= (int) $s['step_order'] ?>: <?= htmlspecialchars($s['signer_name'], ENT_QUOTES, 'UTF-8') ?>
                            <?= $isMine ? ' (Anda)' : '' ?>
                            &bull; Hal. <?= $sPage ?>
                        </button>
                    <?php endforeach; ?>
                </div>
                <span style="font-size: 0.7rem; color: var(--text-muted);">
                    <i class="bi bi-info-circle me-1"></i>Klik slot untuk melompat ke halaman terkait
                </span>
            </div>
            <?php endif; ?>

            <div class="card-sigma-body p-3 text-center" style="background: #f1eff7;">
                <!-- Indikator Memuat PDF -->
                <div id="detailPdfLoadingSpinner" class="py-5" style="color: var(--text-muted);">
                    <div class="spinner-border spinner-border-sm text-sigma-purple me-2" role="status"></div>
                    <span class="small">Memuat lembar dokumen & pratinjau slot tanda tangan...</span>
                </div>

                <!-- Alert Fallback jika PDF.js gagal -->
                <div id="detailPdfFallbackAlert" class="alert alert-warning alert-sigma py-2 px-3 small d-none text-start mb-3">
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
                                    ['border' => '#78629e', 'bg' => 'rgba(120, 98, 158, 0.12)', 'badgeStyle' => 'background: var(--sigma-purple-700); color: #ffffff;'],
                                    ['border' => '#2d2b4b', 'bg' => 'rgba(45, 43, 75, 0.10)',  'badgeStyle' => 'background: var(--sigma-navy); color: #ffffff;'],
                                    ['border' => '#6246d0', 'bg' => 'rgba(98, 70, 208, 0.12)', 'badgeStyle' => 'background: var(--sigma-violet-600); color: #ffffff;'],
                                    ['border' => '#d97706', 'bg' => 'rgba(217, 119, 6, 0.14)', 'badgeStyle' => 'background: #f59e0b; color: #1e1b33;'],
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
                                    <div id="detailSlot_<?= htmlspecialchars($s['id'], ENT_QUOTES, 'UTF-8') ?>"
                                         class="position-absolute rounded text-center overflow-hidden <?= ($sp !== $initialPreviewPage) ? 'd-none' : '' ?> <?= $isCompletedDoc ? '' : 'bg-white shadow-sm' ?>"
                                         data-page="<?= $sp ?>"
                                         style="left: <?= $sx ?>%; top: <?= $sy ?>%; width: <?= $sw ?>%; height: <?= $sh ?>%;
                                                border: <?= $isCompletedDoc ? '1.5px dashed #166534' : '2px solid #166534' ?> !important;
                                                background-color: <?= $isCompletedDoc ? 'rgba(220, 252, 231, 0.14)' : '#ffffff' ?>;
                                                z-index: <?= $isMineSlot ? 22 : 16 ?>; pointer-events: auto; transition: box-shadow 0.25s ease;"
                                         title="Tahap <?= (int) $s['step_order'] ?>: Telah ditandatangani oleh <?= htmlspecialchars($matchedSig['signer_name_snapshot'], ENT_QUOTES, 'UTF-8') ?>">
                                        <span class="position-absolute top-0 start-0 m-1 px-1 py-0 rounded-pill fw-semibold text-truncate"
                                              style="font-size: 0.52rem; max-width: 94%; z-index: 5; <?= $isMineSlot ? 'background: #166534; color: #fff;' : 'background: var(--sigma-success-bg); color: var(--sigma-success-text); border: 1px solid #bbf7d0;' ?>">
                                            <i class="bi bi-check-circle-fill me-1"></i>Tahap <?= (int) $s['step_order'] ?> &bull; <?= $isMineSlot ? 'Tanda Tangan Anda' : 'Tertanda' ?>
                                        </span>

                                        <div class="w-100 h-100 position-relative <?= $isCompletedDoc ? 'd-none completed-fallback-inner' : 'd-flex' ?> align-items-stretch">
                                            <div class="d-flex flex-column <?= $isSigQrOnly ? 'justify-content-center' : 'justify-content-end' ?>" style="width: 58%; padding: 14px 3px 3px 4px; min-width: 0;">
                                                <?php if (!$isSigQrOnly): ?>
                                                    <div class="position-relative flex-grow-1" style="min-height: 0;">
                                                        <img src="<?= BASE_URL ?>/signatures/<?= htmlspecialchars($matchedSig['id'], ENT_QUOTES, 'UTF-8') ?>/preview"
                                                             alt="Tanda tangan <?= htmlspecialchars($matchedSig['signer_name_snapshot'], ENT_QUOTES, 'UTF-8') ?>"
                                                             style="position: absolute; inset: 0; width: 100%; height: 100%; object-fit: contain;">
                                                    </div>
                                                <?php endif; ?>
                                                <div class="text-center" style="line-height: 1.15; min-width: 0;">
                                                    <div class="fw-bold text-truncate" style="font-size: 0.58rem; color: var(--text-heading);">
                                                        <?= htmlspecialchars($matchedSig['signer_name_snapshot'], ENT_QUOTES, 'UTF-8') ?>
                                                    </div>
                                                    <div style="border-top: 1px solid #0f172a; margin: 1px 0;"></div>
                                                    <?php if (!empty($sigIdentTxt)): ?>
                                                        <div class="text-truncate" style="font-size: 0.48rem; color: var(--text-muted);">
                                                            <?= htmlspecialchars($sigIdentTxt, ENT_QUOTES, 'UTF-8') ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-center" style="width: 42%; padding: 3px;">
                                                <div class="d-flex flex-column align-items-center justify-content-center bg-white rounded border"
                                                     style="aspect-ratio: 1 / 1; height: 78%; max-width: 100%; font-size: 0.95rem; border-color: #bbf7d0 !important; color: var(--sigma-success-text);"
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
                                            $boxBorder = '2px dashed #b22929';
                                            $boxBg     = 'rgba(254, 226, 226, 0.85)';
                                            $badgeStyle = 'background: #b22929; color: #fff;';
                                            $statusTxt = 'Menolak TTD';
                                        } elseif ($isCurrentTurnSlot) {
                                            $boxBorder = '2px dashed #78629e';
                                            $boxBg     = 'rgba(248, 245, 252, 0.90)';
                                            $badgeStyle = 'background: #f59e0b; color: #1e1b33;';
                                            $statusTxt = $isMineSlot ? 'Giliran Anda' : 'Giliran Aktif';
                                        } elseif ($isMineSlot) {
                                            $boxBorder = '2px dashed #78629e';
                                            $boxBg     = 'rgba(248, 245, 252, 0.90)';
                                            $badgeStyle = 'background: var(--sigma-purple-700); color: #fff;';
                                            $statusTxt = 'Slot Anda';
                                        } else {
                                            $boxBorder = '1.5px dashed ' . $sc['border'];
                                            $boxBg     = $sc['bg'];
                                            $badgeStyle = $sc['badgeStyle'];
                                            $statusTxt = $isUnconfiguredSlot ? 'Belum Diatur' : 'Slot TTD';
                                        }
                                    ?>
                                    <div id="detailSlot_<?= htmlspecialchars($s['id'], ENT_QUOTES, 'UTF-8') ?>"
                                         class="position-absolute rounded text-center overflow-hidden <?= ($sp !== $initialPreviewPage) ? 'd-none' : '' ?>"
                                         data-page="<?= $sp ?>"
                                         style="left: <?= $sx ?>%; top: <?= $sy ?>%; width: <?= $sw ?>%; height: <?= $sh ?>%;
                                                border: <?= $boxBorder ?> !important; background-color: <?= $boxBg ?>;
                                                z-index: <?= $isCurrentTurnSlot ? 20 : 12 ?>; pointer-events: auto; transition: box-shadow 0.25s ease;"
                                         title="Slot Tahap <?= (int) $s['step_order'] ?>: <?= htmlspecialchars($s['signer_name'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($statusTxt, ENT_QUOTES, 'UTF-8') ?>)">
                                        <span class="position-absolute top-0 start-0 m-1 px-1 py-0 rounded-pill fw-semibold text-truncate"
                                              style="font-size: 0.52rem; max-width: 94%; z-index: 5; <?= $badgeStyle ?>">
                                            Tahap <?= (int) $s['step_order'] ?> &bull; <?= htmlspecialchars($statusTxt, ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                        <div class="w-100 h-100 position-relative d-flex align-items-stretch">
                                            <div class="d-flex flex-column justify-content-end" style="width: 58%; padding: 13px 3px 3px 4px; min-width: 0;">
                                                <div class="flex-grow-1 d-flex align-items-center justify-content-center" style="font-size: 0.52rem; min-height: 0; color: var(--text-muted);">
                                                    <span><i class="bi bi-pen me-1"></i>[Area TTD]</span>
                                                </div>
                                                <div class="text-center" style="line-height: 1.15; min-width: 0;">
                                                    <div class="fw-bold text-truncate" style="font-size: 0.56rem; color: var(--text-heading);">
                                                        <?= htmlspecialchars($s['signer_name'], ENT_QUOTES, 'UTF-8') ?>
                                                    </div>
                                                    <div style="border-top: 1px solid #0f172a; margin: 1px 0;"></div>
                                                    <?php if (!empty($sIdentTxt)): ?>
                                                        <div class="text-truncate" style="font-size: 0.48rem; color: var(--text-muted);">
                                                            <?= htmlspecialchars($sIdentTxt, ENT_QUOTES, 'UTF-8') ?>
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

                            <!-- Fallback untuk Single-Sign / tanda tangan tanpa baris document_signers -->
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
                                    <div id="detailSig_<?= htmlspecialchars($sig['id'], ENT_QUOTES, 'UTF-8') ?>"
                                         class="position-absolute rounded text-center overflow-hidden <?= ($sp !== $initialPreviewPage) ? 'd-none' : '' ?> <?= $isCompletedDoc ? '' : 'bg-white shadow-sm' ?>"
                                         data-page="<?= $sp ?>"
                                         style="left: <?= $sx ?>%; top: <?= $sy ?>%; width: <?= $sw ?>%; height: <?= $sh ?>%;
                                                border: <?= $isCompletedDoc ? '1.5px dashed #166534' : '2px solid #166534' ?> !important;
                                                background-color: <?= $isCompletedDoc ? 'rgba(220, 252, 231, 0.14)' : '#ffffff' ?>;
                                                z-index: 16; pointer-events: auto;">
                                        <span class="position-absolute top-0 start-0 m-1 px-1 py-0 rounded-pill fw-semibold"
                                              style="font-size: 0.52rem; z-index: 5; background: var(--sigma-success-bg); color: var(--sigma-success-text); border: 1px solid #bbf7d0;">
                                            <i class="bi bi-check-circle-fill me-1"></i>Tertanda
                                        </span>
                                        <div class="w-100 h-100 position-relative <?= $isCompletedDoc ? 'd-none completed-fallback-inner' : 'd-flex' ?> align-items-stretch">
                                            <div class="d-flex flex-column <?= $isSigQrOnly ? 'justify-content-center' : 'justify-content-end' ?>" style="width: 58%; padding: 14px 3px 3px 4px; min-width: 0;">
                                                <?php if (!$isSigQrOnly): ?>
                                                    <div class="position-relative flex-grow-1" style="min-height: 0;">
                                                        <img src="<?= BASE_URL ?>/signatures/<?= htmlspecialchars($sig['id'], ENT_QUOTES, 'UTF-8') ?>/preview"
                                                             alt="Tanda tangan <?= htmlspecialchars($sig['signer_name_snapshot'], ENT_QUOTES, 'UTF-8') ?>"
                                                             style="position: absolute; inset: 0; width: 100%; height: 100%; object-fit: contain;">
                                                    </div>
                                                <?php endif; ?>
                                                <div class="text-center" style="line-height: 1.15; min-width: 0;">
                                                    <div class="fw-bold text-truncate" style="font-size: 0.58rem; color: var(--text-heading);">
                                                        <?= htmlspecialchars($sig['signer_name_snapshot'], ENT_QUOTES, 'UTF-8') ?>
                                                    </div>
                                                    <div style="border-top: 1px solid #0f172a; margin: 1px 0;"></div>
                                                    <?php if (!empty($sigIdentTxt)): ?>
                                                        <div class="text-truncate" style="font-size: 0.48rem; color: var(--text-muted);">
                                                            <?= htmlspecialchars($sigIdentTxt, ENT_QUOTES, 'UTF-8') ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-center" style="width: 42%; padding: 3px;">
                                                <div class="d-flex flex-column align-items-center justify-content-center bg-white rounded border"
                                                     style="aspect-ratio: 1 / 1; height: 78%; max-width: 100%; font-size: 0.95rem; border-color: #bbf7d0 !important; color: var(--sigma-success-text);">
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

    <!-- Kolom Kanan (4/12): Panel Tindakan Anda (Tanpa Minta Revisi), Integritas SHA-256, & Jejak Audit -->
    <div class="col-lg-4">
        <?php
            $hasActionPanel = ($doc['status'] === 'draft' && $isOwner)
                || ($doc['status'] === 'pending_review' && $isOwner)
                || ($isAdmin && $doc['status'] === 'pending_review')
                || (!$isAdmin && $mySigner && in_array($doc['status'], ['signing', 'completed']))
                || ($doc['status'] === 'completed');
        ?>
        <?php if ($hasActionPanel): ?>
        <div class="card-sigma mb-4" <?= ($isAdmin && $doc['status'] === 'pending_review') || $isCanSignNow ? 'style="border-color: var(--sigma-purple-600);"' : '' ?>>
            <div class="card-sigma-header">
                <span class="fw-bold small d-flex align-items-center gap-2" style="color: var(--text-heading);">
                    <i class="bi bi-lightning-charge-fill text-sigma-purple"></i>
                    <span>Tindakan Anda</span>
                </span>
                <?php if ($isAdmin && $doc['status'] === 'pending_review'): ?>
                    <span class="badge-sigma badge-sigma-warning">Review Admin</span>
                <?php elseif ($isCanSignNow): ?>
                    <span class="badge-sigma badge-sigma-warning">Giliran TTD</span>
                <?php endif; ?>
            </div>
            <div class="card-sigma-body p-3 d-grid gap-2">
                <?php if ($isAdmin && $doc['status'] === 'pending_review'): ?>
                    <p class="small mb-2" style="color: var(--text-muted);">
                        Telaah lembar PDF dan posisi slot penandatangan sebelum mengizinkan alur berjenjang dimulai (Tahap 1).
                    </p>
                    <button type="button" class="btn-sigma-navy w-100" data-bs-toggle="modal" data-bs-target="#modalApprove">
                        <i class="bi bi-check-circle"></i> Setujui Berkas
                    </button>
                    <button type="button" class="btn-sigma-outline w-100 text-danger" data-bs-toggle="modal" data-bs-target="#modalReject">
                        <i class="bi bi-x-circle"></i> Tolak Berkas
                    </button>
                <?php endif; ?>

                <?php if ($isCanSignNow): ?>
                    <p class="small mb-2" style="color: var(--text-muted);">
                        Saat ini adalah giliran tanda tangan Anda (Tahap <?= (int)$mySigner['step_order'] ?>).
                    </p>
                    <a href="<?= BASE_URL ?>/documents/<?= htmlspecialchars($doc['id'], ENT_QUOTES, 'UTF-8') ?>/sign" class="btn-sigma-navy w-100">
                        <i class="bi bi-pen-fill"></i> Tanda Tangani Sekarang
                    </a>
                <?php elseif (!$isAdmin && $mySigner && $mySigner['status'] === 'waiting' && $doc['status'] === 'signing'): ?>
                    <button type="button" class="btn-sigma-outline w-100" disabled style="opacity: 0.7;">
                        <i class="bi bi-lock-fill"></i> Menunggu Giliran (Tahap <?= (int) $doc['current_step'] ?>)
                    </button>
                <?php endif; ?>

                <?php if ($doc['status'] === 'draft' && $isOwner): ?>
                    <a href="<?= BASE_URL ?>/documents/<?= htmlspecialchars($doc['id'], ENT_QUOTES, 'UTF-8') ?>/arrange" class="btn-sigma-navy w-100">
                        <i class="bi bi-bounding-box"></i> Atur Slot Tanda Tangan
                    </a>
                    <form action="<?= BASE_URL ?>/documents/<?= htmlspecialchars($doc['id'], ENT_QUOTES, 'UTF-8') ?>/delete" method="POST"
                          onsubmit="return confirm('Apakah Anda yakin ingin menghapus draft dokumen ini? Berkas PDF akan dihapus secara permanen.');">
                        <?= Csrf::field() ?>
                        <button type="submit" class="btn-sigma-outline w-100 text-danger">
                            <i class="bi bi-trash"></i> Hapus Draft
                        </button>
                    </form>
                <?php endif; ?>

                <?php if ($doc['status'] === 'pending_review' && $isOwner): ?>
                    <button type="button" class="btn-sigma-outline w-100" data-bs-toggle="modal" data-bs-target="#modalWithdraw">
                        <i class="bi bi-arrow-counterclockwise"></i> Tarik Pengajuan ke Draft
                    </button>
                <?php endif; ?>

                <?php if ($doc['status'] === 'completed'): ?>
                    <a href="<?= BASE_URL ?>/documents/<?= htmlspecialchars($doc['id'], ENT_QUOTES, 'UTF-8') ?>/download" class="btn-sigma-purple w-100">
                        <i class="bi bi-file-earmark-check-fill"></i> Unduh PDF Final Berstempel
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Rincian Integritas Berkas -->
        <div class="card-sigma mb-4">
            <div class="card-sigma-header">
                <span class="fw-bold small d-flex align-items-center gap-2" style="color: var(--text-heading);">
                    <i class="bi bi-shield-check text-sigma-purple"></i>
                    <span>Integritas & Info Berkas</span>
                </span>
            </div>
            <div class="card-sigma-body p-3">
                <ul class="list-unstyled mb-0 d-flex flex-column gap-3 small">
                    <li>
                        <div class="d-flex align-items-center justify-content-between">
                            <span style="font-size: 0.75rem; color: var(--text-muted);">Nama Berkas Asli</span>
                            <?php if ($doc['status'] === 'completed'): ?>
                                <a href="<?= BASE_URL ?>/documents/<?= htmlspecialchars($doc['id'], ENT_QUOTES, 'UTF-8') ?>/download?type=original"
                                   class="text-decoration-none fw-semibold" style="font-size: 0.72rem; color: var(--sigma-purple-700);"
                                   title="Unduh berkas PDF asli sebelum dibubuhi tanda tangan">
                                    <i class="bi bi-download me-1"></i>Unduh Asli
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="fw-semibold text-break mt-1" style="color: var(--text-heading);"><?= htmlspecialchars($doc['file_name_original'], ENT_QUOTES, 'UTF-8') ?></div>
                    </li>
                    <li class="d-flex justify-content-between gap-2">
                        <div>
                            <span class="d-block" style="font-size: 0.75rem; color: var(--text-muted);">Ukuran Berkas</span>
                            <span class="fw-medium" style="color: var(--text-heading);"><?= number_format($doc['file_size'] / 1024, 1) ?> KB</span>
                        </div>
                        <div class="text-end">
                            <span class="d-block" style="font-size: 0.75rem; color: var(--text-muted);">Waktu Unggah</span>
                            <span class="fw-medium" style="color: var(--text-heading);"><?= date('d M Y, H:i', strtotime($doc['created_at'])) ?> WIB</span>
                        </div>
                    </li>
                    <li>
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <span style="font-size: 0.75rem; color: var(--text-muted);">Hash Integritas SHA-256 (Asli)</span>
                            <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none fw-semibold"
                                    style="font-size: 0.72rem; color: var(--sigma-purple-700);"
                                    onclick="copyHash('<?= htmlspecialchars($doc['file_hash'], ENT_QUOTES, 'UTF-8') ?>', this)">
                                <i class="bi bi-copy me-1"></i>Salin
                            </button>
                        </div>
                        <div class="sigma-hash-box"><?= htmlspecialchars($doc['file_hash'], ENT_QUOTES, 'UTF-8') ?></div>
                    </li>
                    <?php if (!empty($doc['final_file_hash'])): ?>
                    <li class="border-top pt-3" style="border-color: var(--border-subtle) !important;">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <span class="fw-bold d-flex align-items-center gap-1" style="font-size: 0.75rem; color: var(--sigma-success-text);">
                                <i class="bi bi-patch-check-fill"></i> Hash Integritas SHA-256 (Final)
                            </span>
                            <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none fw-semibold"
                                    style="font-size: 0.72rem; color: var(--sigma-success-text);"
                                    onclick="copyHash('<?= htmlspecialchars($doc['final_file_hash'], ENT_QUOTES, 'UTF-8') ?>', this)">
                                <i class="bi bi-copy me-1"></i>Salin
                            </button>
                        </div>
                        <div class="sigma-hash-box" style="background: var(--sigma-success-bg); border-color: #bbf7d0; color: var(--sigma-success-text);">
                            <?= htmlspecialchars($doc['final_file_hash'], ENT_QUOTES, 'UTF-8') ?>
                        </div>
                        <span class="d-block mt-1" style="font-size: 0.7rem; color: var(--text-muted);">
                            Sidik jari kriptografis SHA-256 berkas PDF setelah pembubuhan tanda tangan dan QR Token SIGMA.
                        </span>
                    </li>
                    <?php endif; ?>
                    <?php if (!empty($doc['reviewed_at'])): ?>
                    <li class="border-top pt-3" style="border-color: var(--border-subtle) !important;">
                        <span class="d-block mb-1" style="font-size: 0.75rem; color: var(--text-muted);">Status Review Admin</span>
                        <?php $isAdminRejected = ($doc['status'] === 'rejected' && !$declinedSigner); ?>
                        <span class="badge-sigma <?= $isAdminRejected ? 'badge-sigma-danger' : 'badge-sigma-success' ?>">
                            <i class="bi <?= $isAdminRejected ? 'bi-x-circle' : 'bi-check2-circle' ?>"></i>
                            <?= $isAdminRejected ? 'Ditolak' : 'Disetujui' ?>
                        </span>
                        <div class="mt-1" style="font-size: 0.74rem; color: var(--text-muted);">
                            Oleh: <strong style="color: var(--text-heading);"><?= htmlspecialchars($doc['reviewer_name'] ?? 'Administrator', ENT_QUOTES, 'UTF-8') ?></strong>
                            &bull; <?= date('d M Y, H:i', strtotime($doc['reviewed_at'])) ?> WIB
                        </div>
                    </li>
                    <?php elseif ($doc['status'] === 'pending_review'): ?>
                    <li class="border-top pt-3" style="border-color: var(--border-subtle) !important;">
                        <span class="d-block mb-1" style="font-size: 0.75rem; color: var(--text-muted);">Status Review Admin</span>
                        <span class="badge-sigma badge-sigma-warning">
                            <i class="bi bi-hourglass-split"></i> Menunggu Peninjauan Admin
                        </span>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <!-- Jejak Audit Dokumen -->
        <div class="card-sigma mb-4">
            <div class="card-sigma-header">
                <span class="fw-bold small d-flex align-items-center gap-2" style="color: var(--text-heading);">
                    <i class="bi bi-clock-history text-sigma-purple"></i>
                    <span>Jejak Audit Dokumen</span>
                </span>
                <span class="badge-sigma badge-sigma-neutral"><?= !empty($auditLog) ? count($auditLog) : 0 ?> Catatan</span>
            </div>
            <div class="card-sigma-body p-3">
                <?php if (empty($auditLog)): ?>
                    <div class="text-center py-3 small" style="color: var(--text-muted);">
                        Belum ada riwayat aktivitas tercatat.
                    </div>
                <?php else: ?>
                    <div class="sigma-audit-timeline">
                        <?php
                            $actionMap = [
                                'document.create'          => ['label' => 'Dokumen Dibuat', 'tone' => 'neutral', 'icon' => 'bi-file-earmark-plus'],
                                'document.submit'          => ['label' => 'Diajukan ke Admin', 'tone' => 'purple', 'icon' => 'bi-send'],
                                'document.withdraw'        => ['label' => 'Pengajuan Ditarik ke Draft', 'tone' => 'warning', 'icon' => 'bi-arrow-counterclockwise'],
                                'document.approve'         => ['label' => 'Disetujui Administrator', 'tone' => 'success', 'icon' => 'bi-check2-circle'],
                                'document.reject'          => ['label' => 'Ditolak Administrator', 'tone' => 'danger', 'icon' => 'bi-x-circle'],
                                'document.signed'          => ['label' => 'Tanda Tangan Dibubuhkan', 'tone' => 'purple', 'icon' => 'bi-pen-fill'],
                                'document.step_advanced'   => ['label' => 'Giliran Jenjang Dialihkan', 'tone' => 'purple', 'icon' => 'bi-arrow-right-circle'],
                                'document.completed'       => ['label' => 'Alur Pengesahan Selesai', 'tone' => 'success', 'icon' => 'bi-patch-check-fill'],
                                'document.declined'        => ['label' => 'Tanda Tangan Ditolak Signer', 'tone' => 'danger', 'icon' => 'bi-shield-x'],
                                'document.final_generated' => ['label' => 'PDF Final Berstempel Diterbitkan', 'tone' => 'success', 'icon' => 'bi-file-earmark-check-fill'],
                                'document.download'        => ['label' => 'Berkas Diunduh', 'tone' => 'neutral', 'icon' => 'bi-download'],
                                'document.delete'          => ['label' => 'Dokumen Dihapus', 'tone' => 'danger', 'icon' => 'bi-trash'],
                            ];
                            $toneDotColors = [
                                'success' => 'var(--sigma-success-text)',
                                'warning' => '#d97706',
                                'danger'  => 'var(--sigma-danger-text)',
                                'purple'  => 'var(--sigma-purple-600)',
                                'neutral' => 'var(--sigma-navy)',
                            ];
                        ?>
                        <?php foreach ($auditLog as $log): ?>
                            <?php
                                $details = !empty($log['details']) ? json_decode($log['details'], true) : [];
                                $actionConfig = $actionMap[$log['action']] ?? [
                                    'label' => ucwords(str_replace(['document.', 'civitas.', '.'], ['', '', ' '], (string)$log['action'])),
                                    'tone'  => 'purple',
                                    'icon'  => 'bi-circle-fill'
                                ];
                                $dotStyle = 'background: ' . ($toneDotColors[$actionConfig['tone']] ?? 'var(--sigma-purple-600)') . ';';
                            ?>
                            <div class="sigma-audit-item">
                                <div class="sigma-audit-dot" style="<?= $dotStyle ?>"></div>
                                <div class="fw-bold small d-flex align-items-center gap-1" style="color: var(--text-heading);">
                                    <i class="bi <?= $actionConfig['icon'] ?> text-sigma-purple" style="font-size: 0.82rem;"></i>
                                    <span><?= htmlspecialchars($actionConfig['label'], ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                                <div style="font-size: 0.72rem; color: var(--text-muted);">
                                    Oleh: <strong style="color: var(--text-body);"><?= htmlspecialchars($log['user_name'] ?? 'Sistem', ENT_QUOTES, 'UTF-8') ?></strong>
                                    &bull; <?= date('d/m/Y H:i', strtotime($log['created_at'])) ?>
                                </div>
                                <?php if (!empty($details)): ?>
                                    <div class="mt-1" style="font-size: 0.72rem;">
                                        <?php if (!empty($details['reason'])): ?>
                                            <div class="rounded p-2 border mt-1" style="background: var(--sigma-danger-bg); border-color: #fecaca !important; color: var(--sigma-danger-text-dark);">
                                                <i class="bi bi-chat-left-text me-1"></i><strong>Alasan:</strong> <?= htmlspecialchars($details['reason'], ENT_QUOTES, 'UTF-8') ?>
                                            </div>
                                        <?php elseif (isset($details['step_from'], $details['step_to'])): ?>
                                            <span class="badge-sigma badge-sigma-purple py-0 px-2" style="font-size: 0.68rem;">
                                                Tahap <?= (int)$details['step_from'] ?> &rarr; Tahap <?= (int)$details['step_to'] ?>
                                            </span>
                                        <?php elseif (!empty($details['signer_name'])): ?>
                                            <?php $methodBadge = !empty($details['method']) ? ' (' . ucfirst($details['method']) . ')' : ''; ?>
                                            <span class="badge-sigma badge-sigma-purple py-0 px-2" style="font-size: 0.68rem;">
                                                Tahap <?= (int)($details['step_order'] ?? 1) ?> &bull; <?= htmlspecialchars($details['signer_name'] . $methodBadge, ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                        <?php elseif (!empty($details['final_file_hash'])): ?>
                                            <div class="sigma-hash-box py-1 px-2 mt-1 text-truncate" title="Hash SHA-256 Final">
                                                Hash: <?= substr((string)$details['final_file_hash'], 0, 18) ?>...
                                            </div>
                                        <?php elseif (isset($details['is_final'])): ?>
                                            <span class="badge-sigma <?= !empty($details['is_final']) ? 'badge-sigma-success' : 'badge-sigma-neutral' ?> py-0 px-2" style="font-size: 0.68rem;">
                                                <?= !empty($details['is_final']) ? 'Dokumen Final Berstempel' : 'Berkas Asli' ?>
                                            </span>
                                        <?php elseif (!empty($details['final_step'])): ?>
                                            <span class="badge-sigma badge-sigma-success py-0 px-2" style="font-size: 0.68rem;">
                                                Semua <?= (int)$details['final_step'] ?> Tahap Lengkap
                                            </span>
                                        <?php elseif (isset($details['status_from'], $details['status_to'])): ?>
                                            <span class="badge-sigma badge-sigma-neutral py-0 px-2" style="font-size: 0.68rem;">
                                                <?= htmlspecialchars($details['status_from'], ENT_QUOTES, 'UTF-8') ?> &rarr; <?= htmlspecialchars($details['status_to'], ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                        <?php elseif (!empty($details['file_name'])): ?>
                                            <span style="color: var(--text-muted);">
                                                <i class="bi bi-file-earmark-pdf me-1"></i><?= htmlspecialchars($details['file_name'], ENT_QUOTES, 'UTF-8') ?>
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
<div class="modal fade modal-sigma" id="modalApprove" tabindex="-1" aria-labelledby="modalApproveLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header text-white" style="background: var(--sigma-navy);">
                <h5 class="modal-title fs-6 fw-bold" id="modalApproveLabel">
                    <i class="bi bi-check2-circle me-2"></i> Konfirmasi Persetujuan Dokumen
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= BASE_URL ?>/documents/<?= htmlspecialchars($doc['id'], ENT_QUOTES, 'UTF-8') ?>/approve" method="POST">
                <?= Csrf::field() ?>
                <div class="modal-body p-4">
                    <p class="mb-3" style="color: var(--text-heading);">
                        Apakah Anda yakin ingin menyetujui dokumen <strong>"<?= htmlspecialchars($doc['title'], ENT_QUOTES, 'UTF-8') ?>"</strong>?
                    </p>
                    <div class="alert alert-sigma py-2 px-3 small mb-0 d-flex align-items-start gap-2"
                         style="background: var(--sigma-tint-50); border-color: #dfd7ee; color: var(--sigma-navy);">
                        <i class="bi bi-info-circle-fill mt-1 text-sigma-purple"></i>
                        <div>
                            Setelah disetujui, alur penandatanganan berjenjang akan segera dimulai. Berkas dialihkan ke status <strong>Proses Penandatanganan</strong> dan penandatangan pada <strong>Tahap 1</strong> menerima giliran aktif.
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top py-2 px-3" style="background: var(--sigma-tint-50); border-color: var(--border-subtle) !important;">
                    <button type="button" class="btn-sigma-outline btn-sigma-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn-sigma-navy btn-sigma-sm">
                        <i class="bi bi-check-lg"></i> Ya, Setujui Dokumen
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Tolak Dokumen -->
<div class="modal fade modal-sigma" id="modalReject" tabindex="-1" aria-labelledby="modalRejectLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header text-white" style="background: var(--sigma-danger-text);">
                <h5 class="modal-title fs-6 fw-bold" id="modalRejectLabel">
                    <i class="bi bi-x-octagon-fill me-2"></i> Tolak Pengajuan Dokumen
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= BASE_URL ?>/documents/<?= htmlspecialchars($doc['id'], ENT_QUOTES, 'UTF-8') ?>/reject" method="POST">
                <?= Csrf::field() ?>
                <div class="modal-body p-4">
                    <div class="alert alert-sigma py-2 px-3 small mb-3 d-flex align-items-start gap-2"
                         style="background: var(--sigma-danger-bg); border-color: #fecaca; color: var(--sigma-danger-text-dark);">
                        <i class="bi bi-exclamation-triangle-fill mt-1"></i>
                        <div>
                            <strong>Kebijakan Kampus (ADR-08):</strong> Dokumen yang ditolak berstatus <em>Arsip Batal Permanen</em> dan tidak dapat diedit kembali. Pengaju harus membuat pengajuan baru.
                        </div>
                    </div>
                    <div class="mb-2">
                        <label for="rejection_reason" class="sigma-form-label d-block">
                            Alasan Penolakan <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control sigma-input" id="rejection_reason" name="rejection_reason" rows="3"
                                  placeholder="Tuliskan alasan penolakan secara jelas (contoh: posisi slot menimpa teks surat, lampiran belum lengkap)..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top py-2 px-3" style="background: var(--sigma-tint-50); border-color: var(--border-subtle) !important;">
                    <button type="button" class="btn-sigma-outline btn-sigma-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn-sigma-danger btn-sigma-sm">
                        <i class="bi bi-x-lg"></i> Konfirmasi Tolak Dokumen
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($isOwner && $doc['status'] === 'pending_review'): ?>
<!-- Modal Konfirmasi Tarik Pengajuan ke Draft -->
<div class="modal fade modal-sigma" id="modalWithdraw" tabindex="-1" aria-labelledby="modalWithdrawLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background: var(--sigma-warning-bg); color: var(--sigma-warning-text);">
                <h5 class="modal-title fs-6 fw-bold" id="modalWithdrawLabel">
                    <i class="bi bi-arrow-counterclockwise me-2"></i> Tarik Pengajuan ke Draft?
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= BASE_URL ?>/documents/<?= htmlspecialchars($doc['id'], ENT_QUOTES, 'UTF-8') ?>/withdraw" method="POST">
                <?= Csrf::field() ?>
                <div class="modal-body p-4">
                    <p class="mb-3" style="color: var(--text-heading);">
                        Anda akan menarik pengajuan dokumen <strong>"<?= htmlspecialchars($doc['title'], ENT_QUOTES, 'UTF-8') ?>"</strong> dari antrean peninjauan Administrator kembali ke status <strong>Draft</strong>.
                    </p>
                    <div class="rounded-3 border p-3 small" style="background: var(--sigma-tint-50); border-color: var(--border-subtle) !important; color: var(--text-muted);">
                        Setelah ditarik ke Draft, Anda akan langsung diarahkan ke halaman <strong>Atur Slot</strong> untuk mengubah posisi tanda tangan atau membatalkan pengajuan.
                    </div>
                </div>
                <div class="modal-footer border-top py-2 px-3" style="background: var(--sigma-tint-50); border-color: var(--border-subtle) !important;">
                    <button type="button" class="btn-sigma-outline btn-sigma-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn-sigma-navy btn-sigma-sm">
                        <i class="bi bi-arrow-counterclockwise"></i> Ya, Tarik ke Draft
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
    const orig = btn.innerHTML;
    btn.innerText = 'Tersalin!';
    setTimeout(() => { btn.innerHTML = orig; }, 2000);
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
                targetEl.style.boxShadow = '0 0 0 4px rgba(120, 98, 158, 0.55), 0 6px 16px rgba(45, 43, 75, 0.2)';
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
        document.querySelectorAll('.completed-fallback-inner').forEach(function (el) {
            el.classList.remove('d-none');
            el.classList.add('d-flex');
            if (el.parentElement) {
                el.parentElement.classList.add('bg-white', 'shadow-sm');
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

// Muat ulang jika halaman dipulihkan dari BFCache
window.addEventListener('pageshow', function (event) {
    if (event.persisted) {
        window.location.reload();
    }
});
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
