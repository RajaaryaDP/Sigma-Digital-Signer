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
?>

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

                <a href="<?= BASE_URL ?>/documents/<?= htmlspecialchars($doc['id']) ?>/download" class="btn btn-subtle btn-sm">
                    <i class="bi bi-download me-1"></i> Unduh Berkas PDF
                </a>
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
                                        <span class="badge bg-light text-dark border" style="font-size: 0.75rem;">
                                            Hal. <?= $s['page_number'] ?> (X: <?= (float)$s['x_percent'] ?>%, Y: <?= (float)$s['y_percent'] ?>%)
                                        </span>
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
                        <div class="d-flex align-items-center gap-3 border rounded-3 p-2.5">
                            <div class="checkerboard-bg rounded border flex-shrink-0 d-flex align-items-center justify-content-center"
                                 style="width: 150px; height: 75px; overflow: hidden;">
                                <img src="<?= BASE_URL ?>/signatures/<?= htmlspecialchars($sig['id']) ?>/preview"
                                     alt="Tanda tangan <?= htmlspecialchars($sig['signer_name_snapshot']) ?>"
                                     style="max-width: 100%; max-height: 100%; object-fit: contain;">
                            </div>
                            <div class="flex-grow-1 min-width-0">
                                <div class="fw-bold text-dark small">
                                    <?= htmlspecialchars($sig['signer_name_snapshot']) ?>
                                    <span class="badge bg-success ms-1" style="font-size: 0.62rem;">Tertanda</span>
                                </div>
                                <div class="text-muted" style="font-size: 0.75rem;">
                                    <?= htmlspecialchars($sig['signer_position_snapshot'] ?: '-') ?>
                                    &bull; <?= date('d F Y, H:i', strtotime($sig['signed_at'])) ?> WIB
                                </div>
                                <div class="text-muted text-truncate" style="font-size: 0.7rem;">
                                    <i class="bi bi-qr-code me-1"></i>Token Verifikasi:
                                    <code><?= htmlspecialchars($sig['verification_token']) ?></code>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Pratinjau Dokumen PDF -->
        <div class="card-clean mb-4">
            <div class="card-clean-header py-2.5 px-3 d-flex justify-content-between align-items-center">
                <span class="fw-semibold text-dark small d-flex align-items-center gap-1.5">
                    <i class="bi bi-file-earmark-pdf text-danger"></i>
                    <span>Pratinjau Lembar Berkas Dokumen</span>
                </span>
                <a href="<?= BASE_URL ?>/documents/<?= htmlspecialchars($doc['id']) ?>/preview" target="_blank" class="btn btn-subtle btn-sm">
                    <i class="bi bi-arrows-fullscreen me-1"></i> Layar Penuh
                </a>
            </div>
            <div class="card-clean-body p-0">
                <iframe src="<?= BASE_URL ?>/documents/<?= htmlspecialchars($doc['id']) ?>/preview" 
                        style="width: 100%; height: 560px; border: none;" 
                        title="Pratinjau PDF"></iframe>
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
                        <span class="text-muted d-block" style="font-size: 0.75rem;">Nama Berkas Asli</span>
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
                        <span class="text-muted d-block" style="font-size: 0.75rem;">SHA-256 Hash Integritas</span>
                        <code class="d-block p-1.5 bg-light rounded text-break" style="font-size: 0.7rem; font-family: monospace;">
                            <?= htmlspecialchars($doc['file_hash']) ?>
                        </code>
                    </li>
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
                <span class="badge bg-light text-muted border"><?= count($auditLog) ?> Catatan</span>
            </div>
            <div class="card-clean-body p-3">
                <?php if (empty($auditLog)): ?>
                    <div class="text-center text-muted py-3 small">
                        Belum ada riwayat aktivitas tercatat.
                    </div>
                <?php else: ?>
                    <div class="timeline position-relative" style="padding-left: 1.25rem; border-left: 2px solid var(--border-subtle); margin-left: 0.5rem;">
                        <?php foreach ($auditLog as $log): ?>
                            <?php 
                                $details = !empty($log['details']) ? json_decode($log['details'], true) : [];
                            ?>
                            <div class="mb-3 position-relative">
                                <div class="position-absolute rounded-circle bg-success border border-white" 
                                     style="width: 10px; height: 10px; left: -1.6rem; top: 4px;"></div>
                                <div class="fw-bold text-dark small">
                                    <?= htmlspecialchars(str_replace(['document.', 'civitas.'], '', $log['action'])) ?>
                                </div>
                                <div class="text-muted" style="font-size: 0.75rem;">
                                    Oleh: <strong><?= htmlspecialchars($log['user_name'] ?? 'Sistem') ?></strong>
                                    &bull; <?= date('d/m/Y H:i', strtotime($log['created_at'])) ?>
                                </div>
                                <?php if (!empty($details)): ?>
                                    <div class="text-muted bg-light rounded p-1 mt-1 font-monospace" style="font-size: 0.7rem;">
                                        <?= htmlspecialchars(json_encode($details, JSON_UNESCAPED_UNICODE)) ?>
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

<?php require_once __DIR__ . '/../layout/footer.php'; ?>

