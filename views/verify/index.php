<?php
/**
 * View: Halaman Verifikasi Publik Scan QR (Task 8 — Migrasi Figma Design System)
 * Path: views/verify/index.php
 *
 * Mendukung 3 state:
 *   1. 'valid'   - Token QR terdaftar (profil civitas, visual tanda tangan, hash SHA-256, status berkas & rantai jenjang)
 *   2. 'invalid' - Token tidak terdaftar atau telah dicabut (pesan peringatan + form cari ulang)
 *   3. 'empty'   - Tampilan awal pencarian manual token verifikasi (ADR-08)
 */
require_once __DIR__ . '/../layout/header.php';
?>

<style>
.verify-container {
    max-width: 1080px;
    margin: 0 auto;
}

.verify-hero-badge {
    border-radius: 16px;
    padding: 1.75rem 2rem;
    position: relative;
    overflow: hidden;
    box-shadow: 0 6px 20px rgba(29, 39, 72, 0.14);
}

.verify-hero-valid {
    background: linear-gradient(135deg, #1d2748 0%, #2d2b4b 60%, #3d3464 100%);
    color: #ffffff;
    border: 1px solid rgba(180, 164, 202, 0.35);
}

.verify-hero-signing {
    background: linear-gradient(135deg, #2d2b4b 0%, #483b6e 55%, #78629e 100%);
    color: #ffffff;
    border: 1px solid rgba(180, 164, 202, 0.35);
}

.verify-hero-invalid,
.verify-hero-rejected {
    background: linear-gradient(135deg, #7f1d1d 0%, #991b1b 55%, #b22929 100%);
    color: #ffffff;
    border: 1px solid rgba(254, 202, 202, 0.3);
}

.verify-hero-empty {
    background: linear-gradient(135deg, #1d2748 0%, #2d2b4b 60%, #4d3e72 100%);
    color: #ffffff;
    border: 1px solid rgba(180, 164, 202, 0.3);
}

.verify-card {
    background: var(--surface);
    border-radius: 12px;
    border: 1px solid var(--border-subtle);
    box-shadow: var(--shadow-card);
}

.verify-card-header {
    background-color: var(--sigma-tint-50);
    border-bottom: 1px solid var(--border-subtle);
    padding: 0.9rem 1.25rem;
    font-weight: 700;
    font-size: 0.92rem;
    color: var(--text-heading);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
}

.civitas-avatar-circle {
    width: 68px;
    height: 68px;
    border-radius: 50%;
    background: var(--sigma-tint-100);
    border: 3px solid var(--sigma-lavender);
    color: var(--sigma-navy);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.65rem;
    font-weight: 700;
    flex-shrink: 0;
}

.signature-stamp-box {
    background: repeating-conic-gradient(#f8f7fc 0% 25%, #ffffff 0% 50%) 50% / 16px 16px;
    border: 1.5px dashed var(--border-strong);
    border-radius: 10px;
    padding: 1.25rem;
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 140px;
    position: relative;
}

.signature-stamp-img {
    max-height: 120px;
    max-width: 100%;
    object-fit: contain;
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.08));
}

.crypto-box {
    background-color: var(--sigma-tint-50);
    border: 1px solid var(--border-subtle);
    border-radius: 8px;
    padding: 0.65rem 0.85rem;
    font-family: var(--font-mono);
    font-size: 0.76rem;
    word-break: break-all;
    color: var(--sigma-navy);
}

.chain-item {
    padding: 0.85rem 1rem;
    border-radius: 10px;
    border: 1px solid var(--border-subtle);
    margin-bottom: 0.6rem;
    background: #ffffff;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
}

.chain-item.current-verified {
    border-color: var(--sigma-purple-600);
    background-color: var(--sigma-tint-50);
    box-shadow: 0 0 0 1px var(--sigma-purple-600);
}

@media print {
    * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    .pnj-navbar,
    .sigma-sidebar,
    .sigma-workspace-header,
    .sigma-workspace-footer,
    .sigma-sidebar-backdrop,
    .no-print,
    form,
    .btn-action-group {
        display: none !important;
    }
    body {
        background-color: #ffffff !important;
    }
    .verify-container {
        max-width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    .verify-card {
        box-shadow: none !important;
        border: 1px solid #cbd5e1 !important;
        break-inside: avoid;
        page-break-inside: avoid;
    }
}
</style>

<div class="verify-container py-2 mb-5">

    <?php if ($status === 'valid' && !empty($verified)): ?>
        <?php
        $docStatus      = $verified['document_status'] ?? 'completed';
        $isDocCompleted = ($docStatus === 'completed');
        $isDocSigning   = ($docStatus === 'signing');
        $isDocRejected  = ($docStatus === 'rejected');

        if ($isDocCompleted) {
            $heroClass       = 'verify-hero-valid';
            $iconClass       = 'bi-patch-check-fill';
            $iconStyle       = 'background: var(--sigma-success-bg); color: var(--sigma-success-text);';
            $badgeStatusText = 'RESMI & SAH PENUH';
            $badgeClass      = 'badge-sigma-success';
            $heroTitle       = 'Dokumen Resmi Terverifikasi';
            $heroSubtitle    = 'Tanda tangan digital ini tercatat sah, tervalidasi hash SHA-256, dan seluruh tahapan pengesahan dokumen telah selesai di sistem SIGMA PNJ.';
            $stampStatusText = 'Status: Sah / Selesai';
            $stampBadgeClass = 'badge-sigma-success';
        } elseif ($isDocSigning) {
            $heroClass       = 'verify-hero-signing';
            $iconClass       = 'bi-hourglass-split';
            $iconStyle       = 'background: var(--sigma-tint-100); color: var(--sigma-purple-700);';
            $badgeStatusText = 'DALAM PROSES BERJENJANG';
            $badgeClass      = 'badge-sigma-purple';
            $heroTitle       = 'Tanda Tangan Sah — Dokumen Dalam Proses';
            $heroSubtitle    = 'Tanda tangan civitas ini sah dan terdaftar resmi di SIGMA PNJ. Dokumen masih menunggu penyelesaian penandatangan berikutnya.';
            $stampStatusText = 'Status: Sah (Menunggu Pihak Lain)';
            $stampBadgeClass = 'badge-sigma-warning';
        } elseif ($isDocRejected) {
            $heroClass       = 'verify-hero-rejected';
            $iconClass       = 'bi-x-octagon-fill';
            $iconStyle       = 'background: var(--sigma-danger-bg); color: var(--sigma-danger-text);';
            $badgeStatusText = 'DOKUMEN DIBATALKAN / DITOLAK';
            $badgeClass      = 'badge-sigma-danger';
            $heroTitle       = 'Peringatan: Dokumen Telah Dibatalkan';
            $heroSubtitle    = 'Dokumen ini berstatus batal/ditolak pada alur pengesahan SIGMA (Arsip Batal Permanen), sehingga tanda tangan tidak lagi berlaku.';
            $stampStatusText = 'Status: Batal / Gugur';
            $stampBadgeClass = 'badge-sigma-danger';
        } else {
            $heroClass       = 'verify-hero-signing';
            $iconClass       = 'bi-shield-check';
            $iconStyle       = 'background: var(--sigma-tint-100); color: var(--sigma-purple-700);';
            $badgeStatusText = strtoupper((string) $docStatus);
            $badgeClass      = 'badge-sigma-purple';
            $heroTitle       = 'Dokumen Dalam Tinjauan';
            $heroSubtitle    = 'Tanda tangan tercatat resmi dalam basis data SIGMA Politeknik Negeri Jakarta.';
            $stampStatusText = 'Status: Tercatat';
            $stampBadgeClass = 'badge-sigma-neutral';
        }
        ?>

        <!-- Banner Validitas Utama -->
        <div class="verify-hero-badge <?= $heroClass ?> mb-4">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center shadow" style="width: 58px; height: 58px; font-size: 1.9rem; flex-shrink: 0; <?= $iconStyle ?>">
                        <i class="bi <?= $iconClass ?>"></i>
                    </div>
                    <div>
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                            <span class="badge px-2 py-1" style="background: rgba(255,255,255,0.16); color: #ffffff; font-size: 0.72rem; letter-spacing: 0.4px;">
                                <i class="bi bi-mortarboard-fill me-1"></i> POLITEKNIK NEGERI JAKARTA
                            </span>
                            <span class="badge-sigma <?= $badgeClass ?>"><?= htmlspecialchars($badgeStatusText) ?></span>
                        </div>
                        <h3 class="fw-bold mb-1 text-white" style="letter-spacing: -0.3px;"><?= htmlspecialchars($heroTitle) ?></h3>
                        <p class="mb-0 small" style="color: rgba(255,255,255,0.82);">
                            <?= htmlspecialchars($heroSubtitle) ?>
                        </p>
                    </div>
                </div>
                <div class="text-md-end small border-top border-md-top-0 pt-2 pt-md-0" style="border-color: rgba(255,255,255,0.15) !important; color: rgba(255,255,255,0.78); flex-shrink: 0;">
                    <div>Waktu Verifikasi Publik:</div>
                    <div class="fw-semibold text-white">
                        <i class="bi bi-clock me-1"></i><?= date('d M Y, H:i:s') ?> WIB
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- KOLOM KIRI: BIODATA RESMI PENANDATANGAN & STEMPEL TTD -->
            <div class="col-lg-6">

                <div class="verify-card mb-4">
                    <div class="verify-card-header">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-person-vcard text-sigma-purple fs-5"></i>
                            <span>Profil Resmi Penandatangan</span>
                        </div>
                        <span class="badge-sigma badge-sigma-success">Civitas Terotentikasi</span>
                    </div>
                    <div class="p-3 p-md-4">
                        <div class="d-flex align-items-center gap-3 pb-3 mb-3 border-bottom" style="border-color: var(--border-subtle) !important;">
                            <div class="civitas-avatar-circle">
                                <?= htmlspecialchars(strtoupper(mb_substr($verified['signer_name_snapshot'] ?? 'C', 0, 1, 'UTF-8'))) ?>
                            </div>
                            <div class="overflow-hidden">
                                <h5 class="fw-bold mb-1 text-truncate" style="color: var(--text-heading);">
                                    <?= htmlspecialchars($verified['signer_name_snapshot']) ?>
                                </h5>
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <span class="badge-sigma badge-sigma-purple text-capitalize">
                                        <?= htmlspecialchars($verified['signer_type_snapshot']) ?>
                                    </span>
                                    <?php if (!empty($verified['role_title'])): ?>
                                        <span class="badge-sigma badge-sigma-neutral">
                                            <?= htmlspecialchars($verified['role_title']) ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if (($verified['signature_format'] ?? '') === 'qr_only'): ?>
                                        <span class="badge-sigma badge-sigma-violet">
                                            <i class="bi bi-qr-code"></i> Hanya Kode QR
                                        </span>
                                    <?php else: ?>
                                        <span class="badge-sigma badge-sigma-success">
                                            <i class="bi bi-pen"></i> QR + Tanda Tangan
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="row g-2 small">
                            <div class="col-sm-5 text-muted fw-semibold">
                                <i class="bi bi-card-heading me-1"></i>Nomor Identitas
                            </div>
                            <div class="col-sm-7 fw-bold font-mono-hash" style="color: var(--text-heading);">
                                <?= htmlspecialchars($verified['signer_identifier_snapshot'] ?: '-') ?>
                                <span class="badge-sigma badge-sigma-neutral ms-1" style="font-size: 0.68rem;">
                                    <?= strtoupper($verified['signer_type_snapshot'] === 'mahasiswa' ? 'NIM' : 'NIP') ?>
                                </span>
                            </div>

                            <div class="col-sm-5 text-muted fw-semibold">
                                <i class="bi bi-briefcase me-1"></i>Jabatan / Peran
                            </div>
                            <div class="col-sm-7 fw-semibold" style="color: var(--text-heading);">
                                <?= htmlspecialchars($verified['signer_position_snapshot'] ?: ($verified['role_title'] ?: '-')) ?>
                            </div>

                            <div class="col-sm-5 text-muted fw-semibold">
                                <i class="bi bi-diagram-3 me-1"></i>Unit / Jurusan
                            </div>
                            <div class="col-sm-7">
                                <?= htmlspecialchars($verified['signer_department_snapshot'] ?: 'Politeknik Negeri Jakarta') ?>
                            </div>

                            <div class="col-sm-5 text-muted fw-semibold">
                                <i class="bi bi-envelope me-1"></i>Email Resmi
                            </div>
                            <div class="col-sm-7 text-break">
                                <a href="mailto:<?= htmlspecialchars($verified['signer_email_snapshot']) ?>" class="text-decoration-none fw-semibold text-sigma-purple">
                                    <?= htmlspecialchars($verified['signer_email_snapshot'] ?: '-') ?>
                                </a>
                            </div>

                            <div class="col-sm-5 text-muted fw-semibold">
                                <i class="bi bi-calendar-check me-1"></i>Waktu Penandatanganan
                            </div>
                            <div class="col-sm-7 fw-bold" style="color: var(--text-heading);">
                                <?= date('d F Y, H:i:s', strtotime($verified['signed_at'] ?? $verified['created_at'])) ?> WIB
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Kartu Stempel Visual Tanda Tangan -->
                <div class="verify-card mb-4">
                    <div class="verify-card-header">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-pen text-sigma-purple fs-5"></i>
                            <span>Stempel Tanda Tangan Digital</span>
                        </div>
                        <span class="badge-sigma badge-sigma-neutral">
                            Halaman <?= htmlspecialchars((string) ($verified['page_number'] ?? '1')) ?>
                        </span>
                    </div>
                    <div class="p-3 p-md-4 text-center">
                        <div class="signature-stamp-box mb-3 d-flex flex-column align-items-center justify-content-center">
                            <?php if (!empty($sigImageSrc)): ?>
                                <img src="<?= $sigImageSrc ?>" alt="Tanda Tangan Digital" class="signature-stamp-img">
                            <?php elseif (($verified['signature_format'] ?? '') === 'qr_only'): ?>
                                <div class="small py-3 text-center">
                                    <i class="bi bi-qr-code fs-1 d-block mb-1 text-sigma-purple"></i>
                                    <span class="badge-sigma badge-sigma-purple">Format: Hanya Kode QR</span>
                                    <div class="text-muted mt-2" style="font-size: 0.76rem;">Disahkan secara elektronik dengan stempel QR verifikasi resmi SIGMA PNJ.</div>
                                </div>
                            <?php else: ?>
                                <div class="text-muted small py-3">
                                    <i class="bi bi-file-earmark-check fs-2 d-block mb-1" style="color: var(--sigma-success-text);"></i>
                                    <span>Tanda tangan tercatat secara digital dalam basis data SIGMA.</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="d-flex align-items-center justify-content-between gap-2 p-3 rounded border" style="background: var(--sigma-tint-50); border-color: var(--border-subtle) !important;">
                            <div class="text-start">
                                <div class="fw-semibold" style="font-size: 0.8rem; color: var(--text-heading);">
                                    <i class="bi bi-shield-check me-1" style="color: var(--sigma-success-text);"></i>Verifikasi QR Token SIGMA
                                </div>
                                <div class="text-muted" style="font-size: 0.72rem;">Koordinat Slot: (<?= round($verified['x_percent'] ?? 0) ?>%, <?= round($verified['y_percent'] ?? 0) ?>%)</div>
                            </div>
                            <span class="badge-sigma <?= $stampBadgeClass ?>"><?= htmlspecialchars($stampStatusText) ?></span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- KOLOM KANAN: INTEGRITAS DOKUMEN & RANTAI JENJANG -->
            <div class="col-lg-6">

                <div class="verify-card mb-4">
                    <div class="verify-card-header">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-file-earmark-lock2 text-sigma-purple fs-5"></i>
                            <span>Integritas Berkas &amp; Metadata</span>
                        </div>
                        <?php
                        $statusBadgeClass = 'badge-sigma-success';
                        $statusLabel = 'Selesai (Completed)';
                        if ($docStatus === 'signing') {
                            $statusBadgeClass = 'badge-sigma-warning';
                            $statusLabel = 'Sedang Berjalan (Signing)';
                        } elseif ($docStatus === 'approved') {
                            $statusBadgeClass = 'badge-sigma-purple';
                            $statusLabel = 'Disetujui Admin';
                        } elseif ($docStatus === 'rejected') {
                            $statusBadgeClass = 'badge-sigma-danger';
                            $statusLabel = 'Dibatalkan (Rejected)';
                        }
                        ?>
                        <span class="badge-sigma <?= $statusBadgeClass ?>"><?= htmlspecialchars($statusLabel) ?></span>
                    </div>
                    <div class="p-3 p-md-4">
                        <h5 class="fw-bold mb-1" style="color: var(--text-heading);">
                            <?= htmlspecialchars($verified['document_title']) ?>
                        </h5>
                        <?php if (!empty($verified['document_description'])): ?>
                            <p class="text-muted small mb-3">
                                <?= nl2br(htmlspecialchars($verified['document_description'])) ?>
                            </p>
                        <?php else: ?>
                            <p class="text-muted small fst-italic mb-3">Tidak ada deskripsi tambahan.</p>
                        <?php endif; ?>

                        <div class="row g-2 small border-top pt-3 mb-3" style="border-color: var(--border-subtle) !important;">
                            <div class="col-sm-5 text-muted fw-semibold">Pengaju Dokumen</div>
                            <div class="col-sm-7 fw-semibold" style="color: var(--text-heading);">
                                <?= htmlspecialchars($verified['uploader_name']) ?>
                            </div>

                            <div class="col-sm-5 text-muted fw-semibold">Waktu Pengajuan</div>
                            <div class="col-sm-7">
                                <?= date('d M Y, H:i', strtotime($verified['document_created_at'])) ?> WIB
                            </div>

                            <div class="col-sm-5 text-muted fw-semibold">Nama Berkas Asli</div>
                            <div class="col-sm-7 font-mono-hash text-truncate" title="<?= htmlspecialchars($verified['document_file_name']) ?>">
                                <i class="bi bi-file-pdf text-danger me-1"></i><?= htmlspecialchars($verified['document_file_name']) ?>
                            </div>

                            <div class="col-sm-5 text-muted fw-semibold">Alur Tanda Tangan</div>
                            <div class="col-sm-7">
                                <?= ($verified['document_signature_type'] === 'single') ? 'Mandiri (Single Sign)' : 'Berjenjang (Sequential Workflow)' ?>
                            </div>
                        </div>

                        <!-- Hash Integritas SHA-256 -->
                        <div class="mb-3">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <label class="small fw-bold mb-0" style="color: var(--text-muted);">
                                    <i class="bi bi-hash text-sigma-purple"></i> Hash Integritas SHA-256 Berkas Awal
                                </label>
                                <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none text-sigma-purple fw-semibold no-print" style="font-size: 0.75rem;" onclick="copyToClipboard('<?= htmlspecialchars($verified['document_file_hash'], ENT_QUOTES) ?>', this)">
                                    <i class="bi bi-clipboard me-1"></i>Salin Hash
                                </button>
                            </div>
                            <div class="crypto-box">
                                <?= htmlspecialchars($verified['document_file_hash'] ?: 'Belum dihitung') ?>
                            </div>
                            <div class="text-muted mt-1" style="font-size: 0.72rem; line-height: 1.35;">
                                <i class="bi bi-shield-check text-sigma-purple me-1"></i>
                                Hash SHA-256 membuktikan integritas berkas PDF sejak pertama kali diunggah.
                            </div>
                        </div>

                        <?php if (!empty($verified['document_final_file_hash'])): ?>
                            <div class="mb-3">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <label class="small fw-bold mb-0" style="color: var(--sigma-success-text);">
                                        <i class="bi bi-patch-check-fill"></i> Hash Integritas SHA-256 Final (Stamping)
                                    </label>
                                    <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none fw-semibold no-print" style="font-size: 0.75rem; color: var(--sigma-success-text);" onclick="copyToClipboard('<?= htmlspecialchars($verified['document_final_file_hash'], ENT_QUOTES) ?>', this)">
                                        <i class="bi bi-clipboard me-1"></i>Salin Hash Final
                                    </button>
                                </div>
                                <div class="crypto-box" style="background: var(--sigma-success-bg); border-color: #a7dfc0; color: var(--sigma-success-text);">
                                    <?= htmlspecialchars($verified['document_final_file_hash']) ?>
                                </div>
                                <div class="text-muted mt-1" style="font-size: 0.72rem; line-height: 1.35;">
                                    Hash SHA-256 final membuktikan keutuhan berkas PDF setelah pembubuhan tanda tangan &amp; QR verifikasi.
                                </div>
                            </div>
                        <?php endif; ?>

                        <div>
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <label class="small fw-bold mb-0" style="color: var(--text-muted);">
                                    <i class="bi bi-qr-code text-sigma-purple"></i> Token Verifikasi Unik (UUID)
                                </label>
                                <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none text-sigma-purple fw-semibold no-print" style="font-size: 0.75rem;" onclick="copyToClipboard('<?= htmlspecialchars($verified['verification_token'], ENT_QUOTES) ?>', this)">
                                    <i class="bi bi-clipboard me-1"></i>Salin Token
                                </button>
                            </div>
                            <div class="crypto-box fw-semibold">
                                <?= htmlspecialchars($verified['verification_token']) ?>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($verified['document_signature_type'] === 'sequential' && !empty($allSigners) && count($allSigners) > 1): ?>
                    <div class="verify-card mb-4">
                        <div class="verify-card-header">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-diagram-3-fill text-sigma-purple fs-5"></i>
                                <span>Rantai Penandatanganan Berjenjang</span>
                            </div>
                            <span class="badge-sigma badge-sigma-purple">
                                <?= count($allSigners) ?> Penandatangan
                            </span>
                        </div>
                        <div class="p-3 p-md-4">
                            <p class="text-muted small mb-3">
                                Status seluruh pihak penandatangan yang berwenang atas dokumen ini:
                            </p>

                            <?php foreach ($allSigners as $idx => $signer):
                                $isCurrentVerified = ($signer['id'] === $verified['document_signer_id']);
                                $signerStatus = $signer['status'];

                                $matchedSig = null;
                                if (!empty($allSignatures)) {
                                    foreach ($allSignatures as $as) {
                                        if ($as['document_signer_id'] === $signer['id']) {
                                            $matchedSig = $as;
                                            break;
                                        }
                                    }
                                }

                                $stepCircleStyle = 'background: #f1f0f7; color: var(--text-muted); border: 1px solid var(--border-strong);';
                                if ($signerStatus === 'signed') {
                                    $stepCircleStyle = 'background: var(--sigma-success-bg); color: var(--sigma-success-text); border: 1px solid #a7dfc0;';
                                } elseif ($signerStatus === 'pending') {
                                    $stepCircleStyle = 'background: var(--sigma-purple-700); color: #ffffff;';
                                } elseif ($signerStatus === 'declined') {
                                    $stepCircleStyle = 'background: var(--sigma-danger-bg); color: var(--sigma-danger-text);';
                                }
                            ?>
                                <div class="chain-item <?= $isCurrentVerified ? 'current-verified' : '' ?>">
                                    <div class="d-flex align-items-center gap-2 overflow-hidden">
                                        <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold small flex-shrink-0" style="width: 32px; height: 32px; font-size: 0.8rem; <?= $stepCircleStyle ?>">
                                            <?php if ($signerStatus === 'signed'): ?>
                                                <i class="bi bi-check-lg"></i>
                                            <?php else: ?>
                                                <?= (int) $signer['step_order'] ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="overflow-hidden">
                                            <div class="fw-bold text-truncate small" style="color: var(--text-heading);">
                                                <?= htmlspecialchars($signer['signer_name']) ?>
                                                <?php if ($isCurrentVerified): ?>
                                                    <span class="badge-sigma badge-sigma-purple ms-1" style="font-size: 0.65rem;">
                                                        Sedang Diverifikasi
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="text-muted" style="font-size: 0.72rem;">
                                                <?= htmlspecialchars($signer['role_title'] ?: ($signer['signer_position'] ?: 'Penandatangan')) ?>
                                                <?php if ($signerStatus === 'signed' && !empty($signer['signed_at'])): ?>
                                                    &bull; <?= date('d M Y, H:i', strtotime($signer['signed_at'])) ?>
                                                <?php elseif ($signerStatus === 'declined' && !empty($signer['decline_reason'])): ?>
                                                    &bull; <span style="color: var(--sigma-danger-text);">Alasan: <?= htmlspecialchars($signer['decline_reason']) ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex-shrink-0 text-end">
                                        <?php if ($signerStatus === 'signed'): ?>
                                            <?php if ($matchedSig && !empty($matchedSig['verification_token']) && !$isCurrentVerified): ?>
                                                <a href="<?= BASE_URL ?>/verify?token=<?= urlencode($matchedSig['verification_token']) ?>" class="btn-sigma-outline btn-sigma-sm no-print" title="Lihat bukti verifikasi tanda tangan ini">
                                                    <i class="bi bi-shield-check"></i> Cek Token
                                                </a>
                                                <span class="badge-sigma badge-sigma-success d-none d-print-inline-flex">
                                                    <i class="bi bi-check-circle"></i> Sudah TTD
                                                </span>
                                            <?php else: ?>
                                                <span class="badge-sigma badge-sigma-success">
                                                    <i class="bi bi-check-circle"></i> Sudah TTD
                                                </span>
                                            <?php endif; ?>
                                        <?php elseif ($signerStatus === 'pending'): ?>
                                            <span class="badge-sigma badge-sigma-warning">Giliran Aktif</span>
                                        <?php elseif ($signerStatus === 'declined'): ?>
                                            <span class="badge-sigma badge-sigma-danger">Menolak</span>
                                        <?php else: ?>
                                            <span class="badge-sigma badge-sigma-neutral">Menunggu</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </div>

        <!-- Aksi Bawah & Catatan Keabsahan -->
        <div class="verify-card p-3 p-md-4 mt-2">
            <div class="d-flex flex-column flex-sm-row align-items-center justify-content-between gap-3 no-print">
                <a href="<?= BASE_URL ?>/verify" class="btn-sigma-outline btn-sigma-sm">
                    <i class="bi bi-search"></i>
                    <span>Periksa Dokumen / Token Lain</span>
                </a>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn-sigma-navy btn-sigma-sm" onclick="window.print()">
                        <i class="bi bi-printer"></i>
                        <span>Cetak Bukti Verifikasi</span>
                    </button>
                    <?php if (isset($_SESSION['user'])): ?>
                        <a href="<?= BASE_URL ?>/documents" class="btn-sigma-outline btn-sigma-sm">
                            <i class="bi bi-arrow-left"></i>
                            <span>Kembali ke Dokumen</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="text-center text-muted border-top mt-3 pt-3" style="font-size: 0.76rem; line-height: 1.5; border-color: var(--border-subtle) !important;">
                <img src="<?= BASE_URL ?>/assets/img/logo.png" alt="SIGMA" width="18" height="18" style="object-fit: contain; vertical-align: -3px; margin-right: 4px;">
                Halaman verifikasi publik ini diterbitkan otomatis oleh <strong>SIGMA (Sistem Tanda Tangan Digital) Politeknik Negeri Jakarta</strong> berdasarkan snapshot identitas penandatangan, token QR UUID unik, dan hash integritas SHA-256 berkas.
            </div>
        </div>

    <?php elseif ($status === 'invalid'): ?>
        <!-- ================================================================= -->
        <!-- STATE 2: DOKUMEN TIDAK TERDAFTAR / TOKEN TIDAK VALID               -->
        <!-- ================================================================= -->
        <div class="verify-hero-badge verify-hero-invalid mb-4 text-center">
            <div class="rounded-circle bg-white d-inline-flex align-items-center justify-content-center shadow mb-2" style="width: 64px; height: 64px; font-size: 2rem; color: var(--sigma-danger-text);">
                <i class="bi bi-shield-x"></i>
            </div>
            <h3 class="fw-bold mb-1 text-white">Dokumen Tidak Terdaftar / Tidak Valid</h3>
            <p class="mb-0 small" style="max-width: 580px; margin: 0 auto; color: rgba(255,255,255,0.85);">
                Token yang Anda periksa tidak ditemukan dalam pangkalan data resmi SIGMA Politeknik Negeri Jakarta, atau dokumen tersebut telah dibatalkan.
            </p>
        </div>

        <div class="verify-card p-4 p-md-5 mx-auto" style="max-width: 680px;">
            <div class="alert alert-danger alert-sigma small p-3 mb-4">
                <div class="d-flex align-items-start gap-2">
                    <i class="bi bi-exclamation-triangle-fill fs-5 flex-shrink-0"></i>
                    <div>
                        <div class="fw-bold mb-1">Perhatian: Integritas Dokumen Tidak Terverifikasi</div>
                        <div>
                            Jika dokumen fisik atau PDF mencantumkan cap resmi Politeknik Negeri Jakarta, mohon waspada terhadap kemungkinan token keliru atau pemalsuan dokumen.
                        </div>
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <label class="sigma-form-label">Token yang Anda Masukkan:</label>
                <div class="crypto-box" style="color: var(--sigma-danger-text); border-color: #fecaca; background: #fff5f5;">
                    <?= htmlspecialchars($token) ?>
                </div>
            </div>

            <form action="<?= BASE_URL ?>/verify" method="GET">
                <div class="mb-3">
                    <label class="sigma-form-label">Koreksi Token Verifikasi (UUID 36-Karakter)</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted"><i class="bi bi-key-fill"></i></span>
                        <input type="text" name="token" class="form-control sigma-input font-mono-hash" value="<?= htmlspecialchars($token) ?>" placeholder="Contoh: 7d6c5b4a-3f2e-1d0c-8e4a-5b6c7d8e9f0a" required autofocus>
                    </div>
                    <div class="form-text text-muted small">
                        Pastikan seluruh 36 karakter UUID (termasuk tanda strip <code>-</code>) terketik secara lengkap tanpa spasi tambahan.
                    </div>
                </div>
                <div class="d-flex flex-column flex-sm-row gap-2 mt-4">
                    <button type="submit" class="btn-sigma-navy flex-grow-1">
                        <i class="bi bi-search"></i>
                        <span>Periksa Ulang</span>
                    </button>
                    <a href="<?= BASE_URL ?>/verify" class="btn-sigma-outline">
                        <i class="bi bi-arrow-clockwise"></i>
                        <span>Reset Form</span>
                    </a>
                </div>
            </form>

            <div class="border-top mt-4 pt-3 text-center text-muted small" style="border-color: var(--border-subtle) !important;">
                Butuh bantuan konfirmasi berkas? Hubungi Sekretariat Jurusan Teknik Informatika dan Komputer PNJ.
            </div>
        </div>

    <?php else: ?>
        <!-- ================================================================= -->
        <!-- STATE 3: PENCARIAN MANUAL TOKEN VERIFIKASI (ADR-08)                 -->
        <!-- ================================================================= -->
        <div class="verify-hero-badge verify-hero-empty mb-4 text-center">
            <div class="badge px-3 py-2 mb-3" style="background: rgba(255,255,255,0.14); border: 1px solid rgba(255,255,255,0.22); color: #ffffff; font-size: 0.78rem;">
                <i class="bi bi-mortarboard-fill me-1"></i> SIGMA - Tanda Tangan Digital
            </div>
            <h2 class="fw-bold mb-2 text-white">Verifikasi Keaslian Dokumen Resmi</h2>
            <p class="mb-0 small" style="max-width: 620px; margin: 0 auto; line-height: 1.55; color: rgba(255,255,255,0.82);">
                Layanan Publik SIGMA untuk memeriksa keabsahan tanda tangan digital civitas akademika, status persetujuan berjenjang, dan hash integritas SHA-256 dokumen.
            </p>
        </div>

        <div class="row g-4 justify-content-center">
            <div class="col-lg-7">
                <div class="verify-card p-3 p-md-4 mb-4">
                    <div class="d-flex align-items-center gap-3 mb-3 pb-3 border-bottom" style="border-color: var(--border-subtle) !important;">
                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 42px; height: 42px; font-size: 1.2rem; background: var(--sigma-tint-100); color: var(--sigma-purple-700);">
                            <i class="bi bi-qr-code-scan"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-0" style="color: var(--text-heading);">Cek Keabsahan Token QR</h5>
                            <div class="text-muted small">Masukkan 36 karakter kode unik token dokumen</div>
                        </div>
                    </div>

                    <form action="<?= BASE_URL ?>/verify" method="GET">
                        <div class="mb-3">
                            <label class="sigma-form-label">
                                Token Verifikasi Dokumen (UUID) <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text" style="background: var(--sigma-tint-50); color: var(--sigma-purple-700); border-color: var(--border-strong);"><i class="bi bi-key-fill"></i></span>
                                <input type="text" name="token" class="form-control sigma-input font-mono-hash" placeholder="Contoh: fb7897b6-ab8a-47aa-8659-96146c826f15" required autofocus style="font-size: 0.9rem;">
                            </div>
                            <div class="form-text text-muted small mt-2">
                                <i class="bi bi-info-circle me-1"></i>
                                Token dapat ditemukan tercetak di dekat stempel QR Code pada dokumen, atau tempel tautan lengkap hasil scan QR.
                            </div>
                        </div>

                        <button type="submit" class="btn-sigma-navy w-100 py-2">
                            <i class="bi bi-shield-check"></i>
                            <span>Periksa Keaslian Dokumen Sekarang</span>
                        </button>
                    </form>
                </div>

                <!-- Petunjuk Alur Verifikasi -->
                <div class="verify-card p-3 p-md-4">
                    <h6 class="fw-bold mb-3" style="color: var(--text-heading);">
                        Bagaimana Cara Kerja Verifikasi SIGMA?
                    </h6>
                    <div class="row g-3 small">
                        <div class="col-md-4">
                            <div class="p-3 rounded-3 h-100 border" style="background: var(--sigma-tint-50); border-color: var(--border-subtle) !important;">
                                <div class="fw-bold text-sigma-navy mb-1">
                                    1. Scan QR Code
                                </div>
                                <div class="text-muted" style="font-size: 0.78rem;">
                                    Arahkan kamera smartphone ke stempel QR Code untuk membuka tautan verifikasi otomatis.
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 rounded-3 h-100 border" style="background: var(--sigma-tint-50); border-color: var(--border-subtle) !important;">
                                <div class="fw-bold text-sigma-navy mb-1">
                                    2. Hash SHA-256
                                </div>
                                <div class="text-muted" style="font-size: 0.78rem;">
                                    Sistem mencocokkan integritas berkas PDF lewat hash SHA-256 dan token UUID unik penerbitan.
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 rounded-3 h-100 border" style="background: var(--sigma-tint-50); border-color: var(--border-subtle) !important;">
                                <div class="fw-bold text-sigma-navy mb-1">
                                    3. Bukti Otentik
                                </div>
                                <div class="text-muted" style="font-size: 0.78rem;">
                                    Muncul snapshot identitas civitas, stempel tanda tangan, dan rantai pengesahan berjenjang.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="verify-card p-3 p-md-4 mb-4">
                    <h6 class="fw-bold mb-2" style="color: var(--text-heading);">
                        <i class="bi bi-mortarboard text-sigma-purple me-2"></i>Otoritas Penerbit
                    </h6>
                    <p class="text-muted small mb-3">
                        Sistem Informasi Tanda Tangan Digital (SIGMA) dikelola oleh Jurusan Teknik Informatika dan Komputer, Politeknik Negeri Jakarta untuk menjaga keterlacakan dokumen akademik.
                    </p>
                    <div class="p-3 rounded-3 border small" style="background: var(--sigma-tint-50); border-color: var(--border-subtle) !important;">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-geo-alt-fill text-sigma-purple"></i>
                            <span class="fw-semibold">Kampus UI Depok, Jawa Barat 16425</span>
                        </div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-globe text-sigma-purple"></i>
                            <span>https://tik.pnj.ac.id</span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-shield-lock-fill text-sigma-purple"></i>
                            <span class="fw-semibold text-sigma-navy">Hash Integritas SHA-256 &amp; Verifikasi QR Token</span>
                        </div>
                    </div>
                </div>

                <?php if (!isset($_SESSION['user'])): ?>
                    <div class="verify-card p-3 p-md-4 text-center">
                        <div class="text-muted small mb-2">Civitas Akademika PNJ?</div>
                        <a href="<?= BASE_URL ?>/auth" class="btn-sigma-navy w-100">
                            <i class="bi bi-box-arrow-in-right"></i>
                            <span>Masuk ke Akun SIGMA</span>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    <?php endif; ?>

</div>

<script>
function copyToClipboard(text, btnElement) {
    if (!navigator.clipboard) {
        const textArea = document.createElement("textarea");
        textArea.value = text;
        textArea.style.position = "fixed";
        textArea.style.top = "0";
        textArea.style.left = "0";
        textArea.style.opacity = "0";
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        try {
            document.execCommand('copy');
            showCopiedFeedback(btnElement);
        } catch (err) {
            console.error('Gagal menyalin:', err);
        }
        document.body.removeChild(textArea);
        return;
    }

    navigator.clipboard.writeText(text).then(() => {
        showCopiedFeedback(btnElement);
    }).catch(err => {
        console.error('Gagal menyalin clipboard:', err);
    });
}

function showCopiedFeedback(btnElement) {
    const originalHtml = btnElement.innerHTML;
    btnElement.innerHTML = '<i class="bi bi-check2 me-1"></i>Tersalin!';
    setTimeout(() => {
        btnElement.innerHTML = originalHtml;
    }, 2000);
}
</script>

<?php
require_once __DIR__ . '/../layout/footer.php';
?>
