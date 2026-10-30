<?php
/**
 * View: Halaman Verifikasi Publik Scan QR (Task 8)
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
/* Styling Khusus Halaman Verifikasi Publik SIGMA */
.verify-container {
    max-width: 1040px;
    margin: 0 auto;
}

/* Hero Badge */
.verify-hero-badge {
    border-radius: 14px;
    padding: 1.75rem 2rem;
    position: relative;
    overflow: hidden;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.08);
}

.verify-hero-valid {
    background: linear-gradient(135deg, #043e2e 0%, #064e3b 50%, #0a5c47 100%);
    color: #ffffff;
    border: 1px solid rgba(167, 243, 208, 0.3);
}

.verify-hero-signing {
    background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 50%, #0284c7 100%);
    color: #ffffff;
    border: 1px solid rgba(147, 197, 253, 0.3);
}

.verify-hero-invalid,
.verify-hero-rejected {
    background: linear-gradient(135deg, #7f1d1d 0%, #991b1b 50%, #b91c1c 100%);
    color: #ffffff;
    border: 1px solid rgba(254, 202, 202, 0.3);
}

.verify-hero-empty {
    background: linear-gradient(135deg, #043e2e 0%, #064e3b 100%);
    color: #ffffff;
}

/* Card Visual */
.verify-card {
    background: #ffffff;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}

.verify-card-header {
    background-color: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    padding: 0.85rem 1.25rem;
    font-weight: 700;
    font-size: 0.92rem;
    color: #1e293b;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

/* Avatar Civitas */
.civitas-avatar-circle {
    width: 68px;
    height: 68px;
    border-radius: 50%;
    background: #ecfdf5;
    border: 3px solid #10b981;
    color: #064e3b;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.75rem;
    font-weight: 700;
    box-shadow: 0 2px 6px rgba(16, 185, 129, 0.2);
    flex-shrink: 0;
}

/* Stamp Checkerboard Background */
.signature-stamp-box {
    background: repeating-conic-gradient(#f8fafc 0% 25%, #ffffff 0% 50%) 50% / 16px 16px;
    border: 1.5px dashed #cbd5e1;
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

/* Hash & Token Monospace Box */
.crypto-box {
    background-color: #f1f5f9;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 0.6rem 0.75rem;
    font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
    font-size: 0.78rem;
    word-break: break-all;
    color: #0f172a;
}

/* Step Chain List */
.chain-item {
    padding: 0.85rem 1rem;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    margin-bottom: 0.6rem;
    background: #ffffff;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
}

.chain-item.current-verified {
    border-color: #10b981;
    background-color: #f0fdf4;
    box-shadow: 0 0 0 1px #10b981;
}

/* Print Optimization */
@media print {
    * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    .pnj-navbar, .no-print, form, .btn-action-group {
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

<div class="verify-container py-3 mb-5">

    <?php if ($status === 'valid' && !empty($verified)): ?>
        <?php
        $docStatus = $verified['document_status'] ?? 'completed';
        $isDocCompleted = ($docStatus === 'completed');
        $isDocSigning   = ($docStatus === 'signing');
        $isDocRejected  = ($docStatus === 'rejected');

        if ($isDocCompleted) {
            $heroClass      = 'verify-hero-valid';
            $iconClass      = 'bi-patch-check-fill text-success';
            $iconBg         = 'bg-white text-success';
            $badgeStatusText= 'RESMI & SAH PENUH';
            $badgeStatusBg  = 'bg-success';
            $heroTitle      = 'Dokumen Resmi Terverifikasi';
            $heroSubtitle   = 'Tanda tangan digital ini sah, tersertifikasi internal, dan seluruh tahapan dokumen telah selesai secara resmi di basis data SIGMA PNJ.';
            $stampStatusText= 'Status: Sah / Selesai';
            $stampBadgeClass= 'bg-success-subtle text-success border-success-subtle';
        } elseif ($isDocSigning) {
            $heroClass      = 'verify-hero-signing';
            $iconClass      = 'bi-hourglass-split text-primary';
            $iconBg         = 'bg-white text-primary';
            $badgeStatusText= 'DALAM PROSES BERJENJANG';
            $badgeStatusBg  = 'bg-primary';
            $heroTitle      = 'Tanda Tangan Sah — Dokumen Dalam Proses';
            $heroSubtitle   = 'Tanda tangan civitas ini sah dan terdaftar resmi di SIGMA PNJ. Dokumen saat ini sedang dalam proses pemenuhan tanda tangan oleh pihak berikutnya.';
            $stampStatusText= 'Status: Sah (Menunggu Pihak Lain)';
            $stampBadgeClass= 'bg-primary-subtle text-primary border-primary-subtle';
        } elseif ($isDocRejected) {
            $heroClass      = 'verify-hero-rejected';
            $iconClass      = 'bi-x-octagon-fill text-danger';
            $iconBg         = 'bg-white text-danger';
            $badgeStatusText= 'DOKUMEN DIBATALKAN / DITOLAK';
            $badgeStatusBg  = 'bg-danger';
            $heroTitle      = 'Peringatan: Dokumen Telah Dibatalkan';
            $heroSubtitle   = 'Dokumen ini berstatus batal/ditolak pada alur pengesahan SIGMA (Arsip Batal Permanen), sehingga tanda tangan yang tercantum tidak lagi merepresentasikan pengesahan yang berlaku.';
            $stampStatusText= 'Status: Batal / Gugur';
            $stampBadgeClass= 'bg-danger-subtle text-danger border-danger-subtle';
        } else {
            $heroClass      = 'verify-hero-signing';
            $iconClass      = 'bi-shield-check text-info';
            $iconBg         = 'bg-white text-info';
            $badgeStatusText= strtoupper($docStatus);
            $badgeStatusBg  = 'bg-info text-dark';
            $heroTitle      = 'Dokumen Dalam Tinjauan';
            $heroSubtitle   = 'Tanda tangan tercatat resmi dalam sistem SIGMA Politeknik Negeri Jakarta.';
            $stampStatusText= 'Status: Tercatat';
            $stampBadgeClass= 'bg-secondary-subtle text-secondary border-secondary-subtle';
        }
        ?>

        <!-- Top Official Hero Banner -->
        <div class="verify-hero-badge <?= $heroClass ?> mb-4">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle <?= $iconBg ?> d-flex align-items-center justify-content-center shadow" style="width: 58px; height: 58px; font-size: 2rem; flex-shrink: 0;">
                        <i class="bi <?= $iconClass ?>"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="badge bg-white bg-opacity-20 text-white px-2.5 py-1" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                                <i class="bi bi-mortarboard-fill me-1"></i> POLITEKNIK NEGERI JAKARTA
                            </span>
                            <span class="badge <?= $badgeStatusBg ?> text-white px-2 py-0.5" style="font-size: 0.7rem;">
                                <?= $badgeStatusText ?>
                            </span>
                        </div>
                        <h3 class="fw-bold mb-1" style="letter-spacing: -0.3px;"><?= $heroTitle ?></h3>
                        <p class="text-white-50 mb-0 small">
                            <?= $heroSubtitle ?>
                        </p>
                    </div>
                </div>
                <div class="text-md-end text-white-50 small border-top border-md-top-0 pt-2 pt-md-0 border-white border-opacity-10">
                    <div>Waktu Verifikasi Publik:</div>
                    <div class="fw-semibold text-white">
                        <i class="bi bi-clock me-1"></i><?= date('d M Y, H:i:s') ?> WIB
                    </div>
                </div>
            </div>
        </div>

        <!-- 2 Kolom Konten Utama -->
        <div class="row g-4">

            <!-- ===================================================== -->
            <!-- KOLOM KIRI: BIODATA RESMI PENANDATANGAN & STEMPEL TTD -->
            <!-- ===================================================== -->
            <div class="col-lg-6">

                <!-- Kartu Biodata Resmi Civitas Penandatangan (Snapshot Hukum) -->
                <div class="verify-card mb-4">
                    <div class="verify-card-header">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-person-vcard text-success fs-5"></i>
                            <span>Profil Resmi Penandatangan</span>
                        </div>
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                            Civitas Terotentikasi
                        </span>
                    </div>
                    <div class="p-3.5 p-md-4">
                        <!-- Profil Singkat Atas -->
                        <div class="d-flex align-items-center gap-3 pb-3 mb-3 border-bottom">
                            <div class="civitas-avatar-circle">
                                <?= htmlspecialchars(strtoupper(substr($verified['signer_name_snapshot'] ?? 'C', 0, 1))) ?>
                            </div>
                            <div class="overflow-hidden">
                                <h5 class="fw-bold text-dark mb-1 text-truncate">
                                    <?= htmlspecialchars($verified['signer_name_snapshot']) ?>
                                </h5>
                                <div class="d-flex flex-wrap align-items-center gap-1.5">
                                    <span class="badge bg-success text-white text-capitalize" style="font-size: 0.75rem;">
                                        <?= htmlspecialchars($verified['signer_type_snapshot']) ?>
                                    </span>
                                    <?php if (!empty($verified['role_title'])): ?>
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border" style="font-size: 0.75rem;">
                                            <?= htmlspecialchars($verified['role_title']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Rincian Identitas Snapshot -->
                        <div class="row g-2.5 small">
                            <div class="col-sm-5 text-muted fw-semibold">
                                <i class="bi bi-card-heading me-1.5 text-secondary"></i>Nomor Identitas
                            </div>
                            <div class="col-sm-7 fw-bold text-dark font-monospace">
                                <?= htmlspecialchars($verified['signer_identifier_snapshot'] ?: '-') ?>
                                <span class="badge bg-light text-muted border ms-1" style="font-size: 0.68rem;">
                                    <?= strtoupper($verified['signer_type_snapshot'] === 'mahasiswa' ? 'NIM' : 'NIP') ?>
                                </span>
                            </div>

                            <div class="col-sm-5 text-muted fw-semibold">
                                <i class="bi bi-briefcase me-1.5 text-secondary"></i>Jabatan / Peran
                            </div>
                            <div class="col-sm-7 text-dark fw-semibold">
                                <?= htmlspecialchars($verified['signer_position_snapshot'] ?: ($verified['role_title'] ?: '-')) ?>
                            </div>

                            <div class="col-sm-5 text-muted fw-semibold">
                                <i class="bi bi-diagram-3 me-1.5 text-secondary"></i>Unit / Jurusan
                            </div>
                            <div class="col-sm-7 text-dark">
                                <?= htmlspecialchars($verified['signer_department_snapshot'] ?: 'Politeknik Negeri Jakarta') ?>
                            </div>

                            <div class="col-sm-5 text-muted fw-semibold">
                                <i class="bi bi-envelope me-1.5 text-secondary"></i>Email Resmi
                            </div>
                            <div class="col-sm-7 text-dark text-break">
                                <a href="mailto:<?= htmlspecialchars($verified['signer_email_snapshot']) ?>" class="text-decoration-none text-success fw-semibold">
                                    <?= htmlspecialchars($verified['signer_email_snapshot'] ?: '-') ?>
                                </a>
                            </div>

                            <div class="col-sm-5 text-muted fw-semibold">
                                <i class="bi bi-calendar-check me-1.5 text-secondary"></i>Waktu Penandatanganan
                            </div>
                            <div class="col-sm-7 text-dark fw-bold">
                                <?= date('d F Y, H:i:s', strtotime($verified['signed_at'] ?? $verified['created_at'])) ?> WIB
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Kartu Stempel Visual Tanda Tangan -->
                <div class="verify-card mb-4">
                    <div class="verify-card-header">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-pen text-primary fs-5"></i>
                            <span>Stempel Tanda Tangan Digital</span>
                        </div>
                        <span class="badge bg-light text-muted border" style="font-size: 0.72rem;">
                            Halaman <?= htmlspecialchars($verified['page_number'] ?? '1') ?>
                        </span>
                    </div>
                    <div class="p-3.5 p-md-4 text-center">
                        <div class="signature-stamp-box mb-3">
                            <?php if (!empty($sigImageSrc)): ?>
                                <img src="<?= $sigImageSrc ?>" alt="Tanda Tangan Digital" class="signature-stamp-img">
                            <?php else: ?>
                                <div class="text-muted small py-3">
                                    <i class="bi bi-file-earmark-check fs-2 text-success d-block mb-1"></i>
                                    <span>Tanda tangan tersertifikasi secara digital dalam basis data SIGMA.</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="d-flex align-items-center justify-content-between text-muted small bg-light p-2.5 rounded border">
                            <div class="text-start">
                                <div class="fw-semibold text-dark" style="font-size: 0.8rem;">
                                    <i class="bi bi-shield-check text-success me-1"></i>SIGMA Secure Stamp
                                </div>
                                <div style="font-size: 0.72rem;">Koordinat: (<?= round($verified['x_percent'] ?? 0) ?>%, <?= round($verified['y_percent'] ?? 0) ?>%)</div>
                            </div>
                            <div class="text-end">
                                <span class="badge <?= $stampBadgeClass ?> border">
                                    <?= $stampStatusText ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- ===================================================== -->
            <!-- KOLOM KANAN: INTEGRITAS DOKUMEN & RANTAI JENJANG      -->
            <!-- ===================================================== -->
            <div class="col-lg-6">

                <!-- Kartu Integritas Dokumen & Rekam Kriptografis -->
                <div class="verify-card mb-4">
                    <div class="verify-card-header">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-file-earmark-lock2 text-success fs-5"></i>
                            <span>Integritas Berkas & Metadata</span>
                        </div>
                        <?php
                        $statusBadgeClass = 'bg-success';
                        $statusLabel = 'Selesai (Completed)';
                        if ($docStatus === 'signing') {
                            $statusBadgeClass = 'bg-primary';
                            $statusLabel = 'Sedang Berjalan (Signing)';
                        } elseif ($docStatus === 'approved') {
                            $statusBadgeClass = 'bg-info text-dark';
                            $statusLabel = 'Disetujui Admin';
                        } elseif ($docStatus === 'rejected') {
                            $statusBadgeClass = 'bg-danger';
                            $statusLabel = 'Dibatalkan (Rejected)';
                        }
                        ?>
                        <span class="badge <?= $statusBadgeClass ?> px-2 py-1" style="font-size: 0.72rem;">
                            <?= $statusLabel ?>
                        </span>
                    </div>
                    <div class="p-3.5 p-md-4">
                        <h5 class="fw-bold text-dark mb-1">
                            <?= htmlspecialchars($verified['document_title']) ?>
                        </h5>
                        <?php if (!empty($verified['document_description'])): ?>
                            <p class="text-muted small mb-3">
                                <?= nl2br(htmlspecialchars($verified['document_description'])) ?>
                            </p>
                        <?php else: ?>
                            <p class="text-muted small fst-italic mb-3">Tidak ada deskripsi tambahan.</p>
                        <?php endif; ?>

                        <div class="row g-2 small border-top pt-3 mb-3">
                            <div class="col-sm-5 text-muted fw-semibold">Pengaju Dokumen</div>
                            <div class="col-sm-7 text-dark fw-semibold">
                                <?= htmlspecialchars($verified['uploader_name']) ?>
                            </div>

                            <div class="col-sm-5 text-muted fw-semibold">Waktu Pengajuan</div>
                            <div class="col-sm-7 text-dark">
                                <?= date('d M Y, H:i', strtotime($verified['document_created_at'])) ?> WIB
                            </div>

                            <div class="col-sm-5 text-muted fw-semibold">Nama Berkas Asli</div>
                            <div class="col-sm-7 text-dark font-monospace text-truncate" title="<?= htmlspecialchars($verified['document_file_name']) ?>">
                                <i class="bi bi-file-pdf text-danger me-1"></i><?= htmlspecialchars($verified['document_file_name']) ?>
                            </div>

                            <div class="col-sm-5 text-muted fw-semibold">Alur Tanda Tangan</div>
                            <div class="col-sm-7 text-dark">
                                <?= ($verified['document_signature_type'] === 'single') ? 'Mandiri (Single Sign)' : 'Berjenjang (Sequential Workflow)' ?>
                            </div>
                        </div>

                        <!-- Hash Kriptografis SHA-256 -->
                        <div class="mb-3">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <label class="form-label small fw-bold text-secondary mb-0">
                                    <i class="bi bi-hash text-success"></i> Nilai Hash SHA-256 Berkas:
                                </label>
                                <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none text-success fw-semibold no-print" style="font-size: 0.75rem;" onclick="copyToClipboard('<?= htmlspecialchars($verified['document_file_hash'], ENT_QUOTES) ?>', this)">
                                    <i class="bi bi-clipboard me-1"></i>Salin Hash
                                </button>
                            </div>
                            <div class="crypto-box">
                                <?= htmlspecialchars($verified['document_file_hash'] ?: 'Belum dihitung') ?>
                            </div>
                            <div class="text-muted mt-1" style="font-size: 0.72rem; line-height: 1.3;">
                                <i class="bi bi-shield-check text-success me-1"></i>
                                Hash SHA-256 membuktikan integritas berkas PDF tidak pernah dimodifikasi sejak pertama kali diunggah.
                            </div>
                        </div>

                        <!-- Token Verifikasi QR -->
                        <div>
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <label class="form-label small fw-bold text-secondary mb-0">
                                    <i class="bi bi-qr-code text-primary"></i> Token Verifikasi Unik (UUID):
                                </label>
                                <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none text-primary fw-semibold no-print" style="font-size: 0.75rem;" onclick="copyToClipboard('<?= htmlspecialchars($verified['verification_token'], ENT_QUOTES) ?>', this)">
                                    <i class="bi bi-clipboard me-1"></i>Salin Token
                                </button>
                            </div>
                            <div class="crypto-box font-monospace fw-bold text-success" style="background: #ecfdf5; border-color: #a7f3d0;">
                                <?= htmlspecialchars($verified['verification_token']) ?>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($verified['document_signature_type'] === 'sequential' && !empty($allSigners) && count($allSigners) > 1): ?>
                    <!-- Kartu Rantai Penandatanganan Berjenjang (Audit Chain) -->
                    <div class="verify-card mb-4">
                        <div class="verify-card-header">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-diagram-3-fill text-success fs-5"></i>
                                <span>Rantai Penandatanganan Berjenjang</span>
                            </div>
                            <span class="badge bg-light text-muted border" style="font-size: 0.72rem;">
                                <?= count($allSigners) ?> Penandatangan
                            </span>
                        </div>
                        <div class="p-3.5 p-md-4">
                            <p class="text-muted small mb-3">
                                Status seluruh pihak penandatangan yang berwenang atas dokumen ini:
                            </p>

                            <?php foreach ($allSigners as $idx => $signer): 
                                $isCurrentVerified = ($signer['id'] === $verified['document_signer_id']);
                                $signerStatus = $signer['status'];
                                
                                // Cari matching signature jika sudah ditandatangani
                                $matchedSig = null;
                                if (!empty($allSignatures)) {
                                    foreach ($allSignatures as $as) {
                                        if ($as['document_signer_id'] === $signer['id']) {
                                            $matchedSig = $as;
                                            break;
                                        }
                                    }
                                }
                            ?>
                                <div class="chain-item <?= $isCurrentVerified ? 'current-verified' : '' ?>">
                                    <div class="d-flex align-items-center gap-2.5 overflow-hidden">
                                        <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold small flex-shrink-0 <?= $signerStatus === 'signed' ? 'bg-success text-white' : ($signerStatus === 'pending' ? 'bg-primary text-white' : 'bg-light text-muted border') ?>" style="width: 32px; height: 32px; font-size: 0.8rem;">
                                            <?php if ($signerStatus === 'signed'): ?>
                                                <i class="bi bi-check"></i>
                                            <?php else: ?>
                                                <?= (int)$signer['step_order'] ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="overflow-hidden">
                                            <div class="fw-bold text-dark text-truncate small">
                                                <?= htmlspecialchars($signer['signer_name']) ?>
                                                <?php if ($isCurrentVerified): ?>
                                                    <span class="badge bg-success-subtle text-success border border-success ms-1" style="font-size: 0.65rem;">
                                                        Sedang Diverifikasi
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="text-muted" style="font-size: 0.72rem;">
                                                <?= htmlspecialchars($signer['role_title'] ?: ($signer['signer_position'] ?: 'Penandatangan')) ?>
                                                <?php if ($signerStatus === 'signed' && !empty($signer['signed_at'])): ?>
                                                    &bull; <?= date('d M Y, H:i', strtotime($signer['signed_at'])) ?>
                                                <?php elseif ($signerStatus === 'declined' && !empty($signer['decline_reason'])): ?>
                                                    &bull; <span class="text-danger">Alasan: <?= htmlspecialchars($signer['decline_reason']) ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex-shrink-0 text-end">
                                        <?php if ($signerStatus === 'signed'): ?>
                                            <?php if ($matchedSig && !empty($matchedSig['verification_token']) && !$isCurrentVerified): ?>
                                                <a href="<?= BASE_URL ?>/verify?token=<?= urlencode($matchedSig['verification_token']) ?>" class="btn btn-outline-success btn-sm py-0.5 px-2 no-print" style="font-size: 0.72rem;" title="Lihat bukti verifikasi tanda tangan ini">
                                                    <i class="bi bi-shield-check me-0.5"></i>Cek Token
                                                </a>
                                                <span class="badge bg-success d-none d-print-inline" style="font-size: 0.7rem;">
                                                    <i class="bi bi-check-circle me-1"></i>Sudah TTD
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-success" style="font-size: 0.7rem;">
                                                    <i class="bi bi-check-circle me-1"></i>Sudah TTD
                                                </span>
                                            <?php endif; ?>
                                        <?php elseif ($signerStatus === 'pending'): ?>
                                            <span class="badge bg-primary" style="font-size: 0.7rem;">Giliran Aktif</span>
                                        <?php elseif ($signerStatus === 'declined'): ?>
                                            <span class="badge bg-danger" style="font-size: 0.7rem;">Menolak</span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border" style="font-size: 0.7rem;">Menunggu</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </div>

        <!-- Tombol Aksi Bawah & Legal Disclaimer -->
        <div class="verify-card p-3.5 p-md-4 mt-2">
            <div class="d-flex flex-column flex-sm-row align-items-center justify-content-between gap-3 no-print">
                <a href="<?= BASE_URL ?>/verify" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1.5 px-3 py-2 fw-semibold">
                    <i class="bi bi-search"></i>
                    <span>Periksa Dokumen / Token Lain</span>
                </a>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-success btn-sm d-flex align-items-center gap-1.5 px-3.5 py-2 fw-semibold shadow-sm" onclick="window.print()">
                        <i class="bi bi-printer"></i>
                        <span>Cetak Bukti Verifikasi</span>
                    </button>
                    <?php if (isset($_SESSION['user'])): ?>
                        <a href="<?= BASE_URL ?>/documents" class="btn btn-outline-dark btn-sm d-flex align-items-center gap-1.5 px-3 py-2 fw-semibold">
                            <i class="bi bi-arrow-left"></i>
                            <span>Kembali ke Dokumen</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="text-center text-muted border-top mt-3 pt-3" style="font-size: 0.75rem; line-height: 1.5;">
                <i class="bi bi-shield-shaded text-success me-1"></i>
                Halaman verifikasi publik ini diterbitkan secara otomatis oleh <strong>SIGMA (Sistem Tanda Tangan Digital) Politeknik Negeri Jakarta</strong>.
                Setiap data snapshot penandatangan dan hash berkas dilindungi oleh mekanisme kriptografis internal kampus yang menjamin keaslian data.
            </div>
        </div>

    <?php elseif ($status === 'invalid'): ?>
        <!-- ================================================================= -->
        <!-- STATE 2: DOKUMEN TIDAK TERDAFTAR / TOKEN TIDAK VALID               -->
        <!-- ================================================================= -->

        <div class="verify-hero-badge verify-hero-invalid mb-4 text-center">
            <div class="rounded-circle bg-white text-danger d-inline-flex align-items-center justify-content-center shadow mb-2" style="width: 64px; height: 64px; font-size: 2.2rem;">
                <i class="bi bi-shield-x text-danger"></i>
            </div>
            <h3 class="fw-bold mb-1">Dokumen Tidak Terdaftar / Tidak Valid</h3>
            <p class="text-white-50 mb-0 small" style="max-width: 580px; margin: 0 auto;">
                Token yang Anda periksa tidak ditemukan dalam pangkalan data resmi SIGMA Politeknik Negeri Jakarta, atau dokumen tersebut telah dicabut/dibatalkan.
            </p>
        </div>

        <div class="verify-card p-4 p-md-5 mx-auto" style="max-width: 680px;">
            <div class="alert alert-danger bg-danger-subtle border-danger-subtle text-danger small p-3 rounded-3 mb-4">
                <div class="d-flex align-items-start gap-2">
                    <i class="bi bi-exclamation-triangle-fill fs-5 flex-shrink-0 mt-0.5"></i>
                    <div>
                        <div class="fw-bold mb-0.5">Perhatian: Integritas Dokumen Tidak Terverifikasi</div>
                        <div>
                            Jika dokumen fisik atau PDF ini mencantumkan cap resmi Politeknik Negeri Jakarta, mohon waspada terhadap kemungkinan pemalsuan tanda tangan atau token yang telah kedaluwarsa.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Token yang Dicari -->
            <div class="mb-4">
                <label class="form-label small fw-bold text-secondary">Token yang Anda Masukkan:</label>
                <div class="crypto-box font-monospace text-danger bg-light" style="font-size: 0.85rem;">
                    <?= htmlspecialchars($token) ?>
                </div>
            </div>

            <!-- Form Pencarian Ulang -->
            <form action="<?= BASE_URL ?>/verify" method="GET">
                <div class="mb-3">
                    <label class="form-label small fw-bold text-secondary">Koreksi Token Verifikasi (UUID 36-Karakter)</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted"><i class="bi bi-key-fill"></i></span>
                        <input type="text" name="token" class="form-control" value="<?= htmlspecialchars($token) ?>" placeholder="Contoh: 7d6c5b4a-3f2e-1d0c-8e4a-5b6c7d8e9f0a" required autofocus>
                    </div>
                    <div class="form-text text-muted small">
                        Pastikan seluruh 36 karakter UUID (termasuk tanda strip <code>-</code>) terketik secara lengkap tanpa spasi tambahan.
                    </div>
                </div>
                <div class="d-flex flex-column flex-sm-row gap-2 mt-4">
                    <button type="submit" class="btn btn-success fw-semibold px-4 py-2 flex-grow-1 shadow-sm">
                        <i class="bi bi-search me-1.5"></i>Periksa Ulang
                    </button>
                    <a href="<?= BASE_URL ?>/verify" class="btn btn-outline-secondary fw-semibold px-3 py-2">
                        <i class="bi bi-arrow-clockwise me-1.5"></i>Reset Form
                    </a>
                </div>
            </form>

            <div class="border-top mt-4 pt-3 text-center text-muted small">
                Butuh bantuan konfirmasi berkas? Hubungi Sekretariat Jurusan Teknik Informatika dan Komputer PNJ.
            </div>
        </div>

    <?php else: ?>
        <!-- ================================================================= -->
        <!-- STATE 3: PENCARIAN MANUAL TOKEN VERIFIKASI (ADR-08)                 -->
        <!-- ================================================================= -->

        <div class="verify-hero-badge verify-hero-empty mb-4 text-center">
            <div class="badge bg-white bg-opacity-15 border border-white border-opacity-25 text-light px-3 py-1.5 mb-2.5" style="font-size: 0.78rem;">
                <i class="bi bi-mortarboard-fill me-1"></i> POLITEKNIK NEGERI JAKARTA
            </div>
            <h2 class="fw-bold mb-2">Verifikasi Keaslian Dokumen Resmi</h2>
            <p class="text-white-50 mb-0 small" style="max-width: 620px; margin: 0 auto; line-height: 1.5;">
                Layanan Publik SIGMA untuk memeriksa keabsahan tanda tangan digital civitas akademika, status persetujuan, dan integritas dokumen secara kriptografis.
            </p>
        </div>

        <div class="row g-4 justify-content-center">
            <!-- Box Formulir Pencarian Manual -->
            <div class="col-lg-7">
                <div class="verify-card p-4 p-md-4 mb-4">
                    <div class="d-flex align-items-center gap-2.5 mb-3 pb-2 border-bottom">
                        <div class="rounded-circle bg-success-subtle text-success d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; font-size: 1.25rem;">
                            <i class="bi bi-qr-code-scan"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold text-dark mb-0">Cek Keabsahan Token QR</h5>
                            <div class="text-muted small">Masukkan 36 karakter kode unik dokumen</div>
                        </div>
                    </div>

                    <form action="<?= BASE_URL ?>/verify" method="GET">
                        <div class="mb-3.5">
                            <label class="form-label small fw-bold text-secondary">
                                Token Verifikasi Dokumen (UUID) <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="bi bi-key-fill"></i></span>
                                <input type="text" name="token" class="form-control font-monospace" placeholder="Contoh: fb7897b6-ab8a-47aa-8659-96146c826f15" required autofocus style="font-size: 0.9rem;">
                            </div>
                            <div class="form-text text-muted small mt-1.5">
                                <i class="bi bi-info-circle me-1"></i>
                                Token dapat ditemukan tercetak tepat di samping atau di bawah stempel QR Code pada dokumen fisik/digital. Anda juga dapat menempelkan tautan lengkap hasil scan.
                            </div>
                        </div>

                        <button type="submit" class="btn btn-success w-100 fw-bold py-2.5 shadow-sm d-flex align-items-center justify-content-center gap-2" style="border-radius: 8px;">
                            <i class="bi bi-shield-check fs-5"></i>
                            <span>Periksa Keaslian Dokumen Sekarang</span>
                        </button>
                    </form>
                </div>

                <!-- Petunjuk Alur Verifikasi -->
                <div class="verify-card p-4">
                    <h6 class="fw-bold text-dark mb-3">
                        <i class="bi bi-lightbulb text-warning me-1.5"></i>Bagaimana Cara Kerja Verifikasi SIGMA?
                    </h6>
                    <div class="row g-3 small">
                        <div class="col-md-4">
                            <div class="p-2.5 bg-light rounded-3 h-100 border">
                                <div class="fw-bold text-success mb-1">
                                    <i class="bi bi-camera-fill me-1"></i>1. Scan QR Code
                                </div>
                                <div class="text-muted" style="font-size: 0.78rem;">
                                    Arahkan kamera smartphone ke stempel QR Code untuk membuka tautan verifikasi otomatis.
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-2.5 bg-light rounded-3 h-100 border">
                                <div class="fw-bold text-success mb-1">
                                    <i class="bi bi-cpu-fill me-1"></i>2. Hash Matching
                                </div>
                                <div class="text-muted" style="font-size: 0.78rem;">
                                    Sistem memverifikasi integritas berkas via hash SHA-256 dan token UUID unik penerbitan.
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-2.5 bg-light rounded-3 h-100 border">
                                <div class="fw-bold text-success mb-1">
                                    <i class="bi bi-patch-check-fill me-1"></i>3. Bukti Otentik
                                </div>
                                <div class="text-muted" style="font-size: 0.78rem;">
                                    Muncul profil resmi civitas kampus, stempel tanda tangan, dan rekam audit berjenjang.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar Informasi Kampus -->
            <div class="col-lg-5">
                <div class="verify-card p-4 mb-4">
                    <h6 class="fw-bold text-dark mb-2.5">
                        <i class="bi bi-mortarboard text-success me-1.5"></i>Otoritas Penerbit
                    </h6>
                    <p class="text-muted small mb-3">
                        Sistem Informasi Tanda Tangan Digital (SIGMA) dikelola secara terpusat oleh Jurusan Teknik Informatika dan Komputer, Politeknik Negeri Jakarta untuk menjamin tata kelola dokumen bebas manipulasi.
                    </p>
                    <div class="p-3 bg-light rounded-3 border small">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-geo-alt-fill text-danger"></i>
                            <span class="fw-semibold">Kampus UI Depok, Jawa Barat 16425</span>
                        </div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-globe text-primary"></i>
                            <span>https://tik.pnj.ac.id</span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-shield-lock-fill text-success"></i>
                            <span class="text-success fw-semibold">256-Bit Cryptographic Assurance</span>
                        </div>
                    </div>
                </div>

                <div class="verify-card p-4 text-center">
                    <div class="text-muted small mb-2">Civitas Akademika PNJ?</div>
                    <a href="<?= BASE_URL ?>/auth" class="btn btn-outline-success btn-sm w-100 fw-semibold py-2">
                        <i class="bi bi-box-arrow-in-right me-1.5"></i>Masuk ke Akun SIGMA
                    </a>
                </div>
            </div>
        </div>

    <?php endif; ?>

</div>

<!-- JavaScript Helper Salin ke Clipboard -->
<script>
function copyToClipboard(text, btnElement) {
    if (!navigator.clipboard) {
        // Fallback untuk browser lama & non-secure context
        const textArea = document.createElement("textarea");
        textArea.value = text;
        textArea.style.position = "fixed";
        textArea.style.top = "0";
        textArea.style.left = "0";
        textArea.style.width = "2em";
        textArea.style.height = "2em";
        textArea.style.padding = "0";
        textArea.style.border = "none";
        textArea.style.outline = "none";
        textArea.style.boxShadow = "none";
        textArea.style.background = "transparent";
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
    btnElement.innerHTML = '<i class="bi bi-check2 text-success me-1"></i>Tersalin!';
    btnElement.classList.add('text-success');
    setTimeout(() => {
        btnElement.innerHTML = originalHtml;
        btnElement.classList.remove('text-success');
    }, 2000);
}
</script>

<?php
require_once __DIR__ . '/../layout/footer.php';
?>
