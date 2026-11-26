<?php
$title = 'Tanda Tangani Dokumen — ' . htmlspecialchars($doc['title']) . ' — SIGMA';
require_once __DIR__ . '/../layout/header.php';
?>

<!-- Pustaka Mozilla PDF.js untuk rendering lembar dokumen langsung di kanvas interaktif (ADR-09) -->
<script src="<?= BASE_URL ?>/assets/js/pdf.min.js"></script>

<?php
// Slot milik penandatangan aktif (terkunci, ADR-08)
$slotX    = (float) $signer['x_percent'];
$slotY    = (float) $signer['y_percent'];
$slotW    = (float) $signer['width_percent'] > 0 ? (float) $signer['width_percent'] : 20.0;
$slotH    = (float) $signer['height_percent'] > 0 ? (float) $signer['height_percent'] : 10.0;
$pageNo   = (int) $signer['page_number'] > 0 ? (int) $signer['page_number'] : 1;
$totalSteps = count($allSigners);
$isSingle = ($doc['signature_type'] === 'single');

// Tab default: template jika tersedia default, selain itu kanvas (fallback BUG-08)
$defaultTemplateId = $defaultTemplate['id'] ?? null;
$defaultTab = $defaultTemplateId ? 'template' : 'canvas';
$restoreTab = $postedInput ?? $defaultTab;

// Data biodata resmi penandatangan (sumber snapshot)
$profileName   = $userProfile['civitas_name'] ?: $userProfile['name'];
$profileIdent  = $userProfile['civitas_identifier'] ?? '-';
$profileDept   = $userProfile['civitas_department'] ?? '-';
$profilePos    = $userProfile['civitas_position'] ?? '-';
// Label identitas pada kotak tanda tangan: NIM untuk mahasiswa, NIP untuk dosen/tendik
$profileIdLabel = (($userProfile['civitas_type'] ?? '') === 'mahasiswa') ? 'NIM' : 'NIP';
?>

<!-- Back Link -->
<div class="mb-3">
    <a href="<?= BASE_URL ?>/documents/<?= htmlspecialchars($doc['id'], ENT_QUOTES, 'UTF-8') ?>" class="text-decoration-none small d-inline-flex align-items-center gap-2" style="color: var(--text-muted);">
        <i class="bi bi-arrow-left"></i>
        <span>Kembali ke Detail Dokumen</span>
    </a>
</div>

<!-- Header Penandatanganan -->
<div class="card-sigma mb-4">
    <div class="card-sigma-body p-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3">
            <div>
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                    <span class="badge-sigma badge-sigma-warning">
                        <i class="bi bi-pen-fill"></i>
                        Giliran Anda — Tahap <?= (int) $signer['step_order'] ?> dari <?= (int) $totalSteps ?>
                    </span>
                    <span class="badge-sigma badge-sigma-purple">
                        <i class="bi <?= $isSingle ? 'bi-person-check' : 'bi-diagram-3' ?>"></i>
                        <?= $isSingle ? 'Tanda Tangan Mandiri' : 'Tanda Tangan Berjenjang' ?>
                    </span>
                    <?php if (!empty($signer['role_title'])): ?>
                        <span class="badge-sigma badge-sigma-neutral">
                            <i class="bi bi-briefcase"></i> <?= htmlspecialchars($signer['role_title'], ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    <?php endif; ?>
                </div>
                <h1 class="h4 fw-bold mb-1" style="color: var(--text-heading);"><?= htmlspecialchars($doc['title'], ENT_QUOTES, 'UTF-8') ?></h1>
                <p class="small mb-0" style="color: var(--text-muted);">
                    Diajukan oleh: <strong style="color: var(--text-heading);"><?= htmlspecialchars($doc['uploader_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                    &bull; Berkas: <?= htmlspecialchars($doc['file_name_original'], ENT_QUOTES, 'UTF-8') ?>
                </p>
            </div>
            <div class="flex-shrink-0 border rounded-3 p-3" style="min-width: 260px; background: var(--sigma-tint-50); border-color: var(--border-subtle) !important;">
                <div class="small text-uppercase fw-bold mb-1" style="font-size: 0.68rem; letter-spacing: 0.05em; color: var(--sigma-purple-700);">
                    Penandatangan
                </div>
                <div class="fw-bold small" style="color: var(--text-heading);"><?= htmlspecialchars($profileName, ENT_QUOTES, 'UTF-8') ?></div>
                <div class="small" style="color: var(--text-muted);"><?= htmlspecialchars($profileIdLabel . ': ' . $profileIdent, ENT_QUOTES, 'UTF-8') ?></div>
                <div class="small" style="color: var(--text-muted);"><?= htmlspecialchars($profilePos, ENT_QUOTES, 'UTF-8') ?></div>
                <div class="small" style="color: var(--text-muted);"><?= htmlspecialchars($profileDept, ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        </div>
    </div>
</div>

<?php if (isset($errors['signature'])): ?>
    <div class="alert alert-danger alert-sigma d-flex align-items-center gap-2 py-2 px-3 mb-4 small" role="alert">
        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
        <div><?= htmlspecialchars($errors['signature'], ENT_QUOTES, 'UTF-8') ?></div>
    </div>
<?php endif; ?>

<!-- Banner Petunjuk Slot Terkunci -->
<div class="sigma-guide-banner py-2 px-3 mb-4 d-flex align-items-start gap-2">
    <i class="bi bi-lock-fill text-sigma-purple mt-1 fs-5 flex-shrink-0"></i>
    <div class="small" style="color: var(--sigma-navy);">
        Posisi slot sudah ditetapkan pengaju. Pilih format dan metode tanda tangan di panel kanan.
    </div>
</div>

<form id="signForm" action="<?= BASE_URL ?>/documents/<?= htmlspecialchars($doc['id'], ENT_QUOTES, 'UTF-8') ?>/sign" method="POST" enctype="multipart/form-data">
    <?= Csrf::field() ?>
    <input type="hidden" name="input_method" id="inputMethod" value="<?= htmlspecialchars($restoreTab === 'canvas' || $restoreTab === 'upload' ? $restoreTab : 'template', ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="signature_canvas" id="signatureCanvasInput">

    <div class="row g-4">
        <!-- Kolom Kiri: Pratinjau Dokumen Interaktif & Slot Terkunci (ADR-09) -->
        <div class="col-lg-7">
            <div class="card-sigma mb-4">
                <div class="card-sigma-header py-2 px-3 flex-wrap">
                    <span class="fw-bold small d-flex align-items-center gap-2" style="color: var(--text-heading);">
                        <i class="bi bi-file-earmark-check text-sigma-purple"></i>
                        <span>Pratinjau Dokumen &amp; Lokasi Slot (Terkunci)</span>
                    </span>

                    <div class="d-flex align-items-center gap-2">
                        <!-- Toolbar Navigasi Halaman PDF -->
                        <div class="btn-group btn-group-sm" role="group" id="pdfNavToolbar">
                            <button type="button" class="btn-sigma-outline px-2 py-1" id="btnPrevPage" title="Halaman Sebelumnya" disabled>
                                <i class="bi bi-chevron-left"></i>
                            </button>
                            <span class="btn-sigma-outline px-3 py-1 fw-semibold" id="pageIndicator" style="font-size: 0.76rem; pointer-events: none; background: var(--sigma-tint-50);">
                                Hal <span id="pageNumDisplay"><?= $pageNo ?></span> dari <span id="pageTotalDisplay">1</span>
                            </span>
                            <button type="button" class="btn-sigma-outline px-2 py-1" id="btnNextPage" title="Halaman Berikutnya" disabled>
                                <i class="bi bi-chevron-right"></i>
                            </button>
                        </div>

                        <!-- Buka Layar Penuh -->
                        <a href="<?= BASE_URL ?>/documents/<?= htmlspecialchars($doc['id'], ENT_QUOTES, 'UTF-8') ?>/preview" target="_blank" class="btn-sigma-outline btn-sigma-sm py-1 px-2">
                            <i class="bi bi-arrows-fullscreen"></i> Layar Penuh
                        </a>
                    </div>
                </div>

                <div class="card-sigma-body p-3 text-center position-relative" style="min-height: 480px; background: #f1eff7;">
                    <!-- Loading Spinner saat PDF.js memuat lembar berkas -->
                    <div id="pdfLoadingSpinner" class="py-5 text-center">
                        <div class="spinner-border text-sigma-purple" role="status" style="width: 2.5rem; height: 2.5rem;">
                            <span class="visually-hidden">Memuat PDF...</span>
                        </div>
                        <div class="small mt-2 fw-medium" style="color: var(--text-muted);">Memuat halaman dokumen PDF asli...</div>
                    </div>

                    <!-- Alert Fallback jika PDF.js gagal atau offline -->
                    <div id="pdfFallbackAlert" class="alert alert-warning alert-sigma py-2 px-3 small d-none text-start mb-3">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                        Pratinjau PDF gagal dimuat. Posisi slot tetap ditampilkan di bawah.
                    </div>

                    <!-- Notifikasi Cepat jika Penandatangan Sedang Membaca Halaman Lain -->
                    <div id="slotPageNotice" class="alert alert-warning alert-sigma border py-2 px-3 small d-none text-start mb-2 align-items-center justify-content-between flex-wrap gap-2">
                        <span>
                            <i class="bi bi-info-circle-fill text-warning me-1"></i>
                            Slot Anda ada di <strong>Halaman <span id="slotCurrentPageNotice"><?= $pageNo ?></span></strong>.
                        </span>
                        <div class="d-flex align-items-center gap-2">
                            <button type="button" class="btn-sigma-navy btn-sigma-sm" id="btnJumpToSlotPage">
                                <i class="bi bi-arrow-left-circle"></i> Kembali ke Hal Slot (<span id="jumpTargetPage"><?= $pageNo ?></span>)
                            </button>
                        </div>
                    </div>

                    <!-- Wadah Kanvas Interaktif Terpadu (Read-Only / Slot Terkunci) -->
                    <div id="pdfStageContainer" class="d-inline-block text-start position-relative d-none" style="max-width: 100%;">
                        <div id="slotCanvasContainer" class="position-relative bg-white shadow-sm border rounded mx-auto overflow-hidden" style="cursor: default;">
                            <!-- Canvas Elemen PDF Asli (dirender oleh PDF.js) -->
                            <canvas id="pdfSignRenderCanvas" style="display: block; width: 100%; height: auto;"></canvas>

                            <!-- Watermark Latar (Hanya aktif jika PDF.js fallback) -->
                            <div id="fallbackWatermark" class="position-absolute w-100 h-100 d-none flex-column align-items-center justify-content-center pointer-events-none opacity-25" style="user-select: none;">
                                <i class="bi bi-file-earmark-text display-1 text-muted"></i>
                                <span class="fw-semibold text-muted small mt-2">Lembar Halaman <?= $pageNo ?></span>
                            </div>

                            <!-- Overlay Lapisan Kotak Slot Tanda Tangan (Persis di atas PDF) -->
                            <div id="slotOverlayContainer" class="position-absolute top-0 start-0 w-100 h-100" style="pointer-events: none;">

                                <!-- 1. Tanda Tangan yang Sudah Ditempatkan oleh Pihak Sebelumnya ($signaturesPlaced) -->
                                <?php if (!empty($signaturesPlaced)): ?>
                                    <?php foreach ($signaturesPlaced as $sig): ?>
                                        <?php
                                            $sx = (float) $sig['x_percent'];
                                            $sy = (float) $sig['y_percent'];
                                            $sw = (float) $sig['width_percent'] > 0 ? (float) $sig['width_percent'] : 20.0;
                                            $sh = (float) $sig['height_percent'] > 0 ? (float) $sig['height_percent'] : 10.0;
                                            $sp = (int) $sig['page_number'] > 0 ? (int) $sig['page_number'] : 1;
                                            $isPlacedQrOnly  = ($sig['signature_format'] ?? '') === 'qr_only' || empty($sig['signature_image_path']);
                                            $placedIdLabel   = (($sig['signer_type_snapshot'] ?? '') === 'mahasiswa') ? 'NIM' : 'NIP';
                                            $placedIdentText = !empty($sig['signer_identifier_snapshot'])
                                                ? ($placedIdLabel . ': ' . $sig['signer_identifier_snapshot'])
                                                : ($sig['signer_position_snapshot'] ?? '');
                                        ?>
                                        <div class="position-absolute rounded text-center overflow-hidden bg-white shadow-sm sigma-slot-signed <?= ($sp !== $pageNo) ? 'd-none' : '' ?>"
                                             data-page="<?= $sp ?>"
                                             style="left: <?= $sx ?>%; top: <?= $sy ?>%; width: <?= $sw ?>%; height: <?= $sh ?>%;
                                                    background-color: rgba(255, 255, 255, 0.96) !important; z-index: 15; pointer-events: auto;"
                                             title="Telah ditandatangani oleh <?= htmlspecialchars($sig['signer_name_snapshot'], ENT_QUOTES, 'UTF-8') ?>">
                                            <span class="position-absolute top-0 start-0 m-1 px-1 py-0 rounded-pill fw-semibold"
                                                  style="font-size: 0.52rem; z-index: 5; background: var(--sigma-success-bg); color: var(--sigma-success-text); border: 1px solid #bbf7d0;">
                                                <i class="bi bi-check-circle-fill me-1"></i><?= !empty($sig['step_order']) ? ('Tahap ' . (int) $sig['step_order'] . ' &bull; ') : '' ?>Tertanda
                                            </span>
                                            <div class="w-100 h-100 position-relative d-flex align-items-stretch">
                                                <div class="d-flex flex-column <?= $isPlacedQrOnly ? 'justify-content-center' : 'justify-content-end' ?>" style="width: 58%; padding: 14px 3px 3px 4px; min-width: 0;">
                                                    <?php if (!$isPlacedQrOnly): ?>
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
                                                        <?php if (!empty($placedIdentText)): ?>
                                                            <div class="text-truncate" style="font-size: 0.50rem; color: var(--text-muted);">
                                                                <?= htmlspecialchars($placedIdentText, ENT_QUOTES, 'UTF-8') ?>
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
                                    <?php endforeach; ?>
                                <?php endif; ?>

                                <!-- 2. Kotak Slot Milik Penandatangan Lain yang Sedang Menunggu Giliran -->
                                <?php foreach ($allSigners as $s): ?>
                                    <?php
                                        if ($s['id'] === $signer['id'] || $s['status'] === 'signed') continue;
                                        $ox = (float) $s['x_percent'];
                                        $oy = (float) $s['y_percent'];
                                        $ow = (float) $s['width_percent'] > 0 ? (float) $s['width_percent'] : 20.0;
                                        $oh = (float) $s['height_percent'] > 0 ? (float) $s['height_percent'] : 10.0;
                                        $op = (int) $s['page_number'] > 0 ? (int) $s['page_number'] : 1;
                                        if ($ox == 0 && $oy == 0) continue;
                                        $waitIdLabel = (($s['signer_type'] ?? '') === 'mahasiswa') ? 'NIM' : 'NIP';
                                        $waitIdentText = !empty($s['signer_identifier'])
                                            ? ($waitIdLabel . ': ' . $s['signer_identifier'])
                                            : ($s['role_title'] ?: ($s['signer_position'] ?? ''));
                                    ?>
                                    <div class="position-absolute rounded text-center overflow-hidden <?= ($op !== $pageNo) ? 'd-none' : '' ?>"
                                         data-page="<?= $op ?>"
                                         style="left: <?= $ox ?>%; top: <?= $oy ?>%; width: <?= $ow ?>%; height: <?= $oh ?>%;
                                                background-color: rgba(248, 248, 252, 0.88); border: 1.5px dashed var(--border-strong) !important; opacity: 0.9; z-index: 10; pointer-events: auto;"
                                         title="Slot Tahap <?= (int) $s['step_order'] ?>: <?= htmlspecialchars($s['signer_name'], ENT_QUOTES, 'UTF-8') ?> (Menunggu Giliran)">
                                        <span class="position-absolute top-0 start-0 m-1 px-1 py-0 rounded-pill fw-semibold text-truncate"
                                              style="font-size: 0.50rem; max-width: 92%; z-index: 5; background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1;">
                                            <i class="bi bi-clock me-1"></i>Tahap <?= (int) $s['step_order'] ?> &bull; Menunggu
                                        </span>
                                        <div class="w-100 h-100 position-relative d-flex align-items-stretch">
                                            <div class="d-flex flex-column justify-content-end" style="width: 58%; padding: 13px 3px 3px 4px; min-width: 0;">
                                                <div class="flex-grow-1 d-flex align-items-center justify-content-center" style="font-size: 0.50rem; min-height: 0; color: var(--text-muted);">
                                                    <span>[Menunggu Giliran]</span>
                                                </div>
                                                <div class="text-center" style="line-height: 1.15; min-width: 0;">
                                                    <div class="fw-bold text-truncate" style="font-size: 0.56rem; color: var(--text-heading);">
                                                        <?= htmlspecialchars($s['signer_name'], ENT_QUOTES, 'UTF-8') ?>
                                                    </div>
                                                    <div style="border-top: 1px solid #475569; margin: 1px 0;"></div>
                                                    <?php if (!empty($waitIdentText)): ?>
                                                        <div class="text-truncate" style="font-size: 0.48rem; color: var(--text-muted);">
                                                            <?= htmlspecialchars($waitIdentText, ENT_QUOTES, 'UTF-8') ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-center" style="width: 42%; padding: 3px;">
                                                <div class="d-flex flex-column align-items-center justify-content-center bg-white rounded border text-muted"
                                                     style="aspect-ratio: 1 / 1; height: 74%; max-width: 100%; font-size: 0.85rem;">
                                                    <i class="bi bi-qr-code"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>

                                <!-- 3. Slot Aktif Milik Penandatangan Saat Ini (Terkunci & Live Preview Figma #f8f5fc/#78629e) -->
                                <div id="activeSlotBox" class="position-absolute rounded text-center overflow-hidden sigma-slot-active"
                                     data-page="<?= $pageNo ?>"
                                     style="left: <?= $slotX ?>%; top: <?= $slotY ?>%; width: <?= $slotW ?>%; height: <?= $slotH ?>%;
                                            z-index: 20; animation: slotPulse 2.2s ease-in-out infinite; pointer-events: auto;">
                                    <span class="position-absolute top-0 start-0 m-1 px-2 py-0 rounded-pill fw-semibold"
                                          style="font-size: 0.60rem; z-index: 5; background: var(--sigma-purple-700); color: #ffffff;">
                                        Slot Anda &bull; Tahap <?= (int) $signer['step_order'] ?>
                                    </span>

                                    <!-- Tampilan Preview: Format QR + Tanda Tangan + Nama -->
                                    <div id="slotViewQrAndSig" class="w-100 h-100 position-relative d-flex align-items-stretch">
                                        <div class="d-flex flex-column justify-content-end" style="width: 58%; padding: 14px 3px 3px 4px; min-width: 0;">
                                            <!-- Pratinjau tanda tangan dengan fit-contain (BUG-07) -->
                                            <div class="position-relative flex-grow-1" style="min-height: 0;">
                                                <img id="slotPreviewImg" src="" alt="Pratinjau tanda tangan"
                                                     class="d-none"
                                                     style="position: absolute; inset: 0; width: 100%; height: 100%; object-fit: contain;"
                                                     title="Pratinjau tanda tangan (rasio aspek dipertahankan)">
                                                <div id="slotPreviewEmpty" class="w-100 h-100 d-flex align-items-center justify-content-center">
                                                    <span style="font-size: 0.58rem; color: var(--sigma-purple-700);">
                                                        <i class="bi bi-pen me-1"></i>Pilih metode tanda tangan
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="text-center" style="line-height: 1.15; min-width: 0;">
                                                <div class="fw-bold text-truncate" style="font-size: 0.6rem; color: var(--text-heading);">
                                                    <?= htmlspecialchars($profileName, ENT_QUOTES, 'UTF-8') ?>
                                                </div>
                                                <div style="border-top: 1px solid #0f172a; margin: 1px 0;"></div>
                                                <?php if (!empty($profileIdent) && $profileIdent !== '-'): ?>
                                                    <div class="text-truncate" style="font-size: 0.52rem; color: var(--text-muted);">
                                                        <?= htmlspecialchars($profileIdLabel, ENT_QUOTES, 'UTF-8') ?>: <?= htmlspecialchars($profileIdent, ENT_QUOTES, 'UTF-8') ?>
                                                    </div>
                                                <?php elseif (!empty($profilePos) && $profilePos !== '-'): ?>
                                                    <div class="text-truncate" style="font-size: 0.52rem; color: var(--text-muted);">
                                                        <?= htmlspecialchars($profilePos, ENT_QUOTES, 'UTF-8') ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center justify-content-center" style="width: 42%; padding: 3px;">
                                            <div class="d-flex flex-column align-items-center justify-content-center checkerboard-bg rounded border"
                                                 style="aspect-ratio: 1 / 1; height: 78%; max-width: 100%; font-size: 1rem; color: var(--sigma-navy);"
                                                 title="QR Code verifikasi dibubuhkan otomatis di dalam kotak, di sebelah kanan identitas">
                                                <i class="bi bi-qr-code"></i>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Tampilan Preview: Format Hanya Kode QR -->
                                    <div id="slotViewQrOnly" class="w-100 h-100 position-relative d-none align-items-stretch">
                                        <div class="d-flex flex-column justify-content-center" style="width: 58%; padding: 14px 3px 3px 4px; min-width: 0;">
                                            <div class="text-center" style="line-height: 1.15; min-width: 0;">
                                                <div class="fw-bold text-truncate" style="font-size: 0.6rem; color: var(--text-heading);">
                                                    <?= htmlspecialchars($profileName, ENT_QUOTES, 'UTF-8') ?>
                                                </div>
                                                <div style="border-top: 1px solid #0f172a; margin: 1px 0;"></div>
                                                <?php if (!empty($profileIdent) && $profileIdent !== '-'): ?>
                                                    <div class="text-truncate" style="font-size: 0.52rem; color: var(--text-muted);">
                                                        <?= htmlspecialchars($profileIdLabel, ENT_QUOTES, 'UTF-8') ?>: <?= htmlspecialchars($profileIdent, ENT_QUOTES, 'UTF-8') ?>
                                                    </div>
                                                <?php elseif (!empty($profilePos) && $profilePos !== '-'): ?>
                                                    <div class="text-truncate" style="font-size: 0.52rem; color: var(--text-muted);">
                                                        <?= htmlspecialchars($profilePos, ENT_QUOTES, 'UTF-8') ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center justify-content-center" style="width: 42%; padding: 3px;">
                                            <div class="d-flex flex-column align-items-center justify-content-center checkerboard-bg rounded border"
                                                 style="aspect-ratio: 1 / 1; height: 78%; max-width: 100%; font-size: 1rem; border-color: var(--sigma-lavender) !important; color: var(--sigma-purple-700);"
                                                 title="QR Code verifikasi dibubuhkan otomatis di dalam kotak">
                                                <i class="bi bi-qr-code"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <!-- Petunjuk Bawah Kanvas -->
                    <div class="mt-2 d-flex justify-content-between align-items-center flex-wrap gap-2 px-1" style="font-size: 0.75rem; color: var(--text-muted);">
                        <span>
                            <i class="bi bi-lock-fill me-1 text-sigma-purple"></i>
                            Slot Anda ada di <strong>Halaman <?= $pageNo ?></strong>.
                        </span>
                        <span class="badge-sigma badge-sigma-purple">
                            <i class="bi bi-layers"></i> Halaman: <strong id="activePageBadge"><?= $pageNo ?></strong>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Metode Tanda Tangan & Konfirmasi -->
        <div class="col-lg-5">
            <div class="card-sigma mb-4">
                <div class="card-sigma-header">
                    <div>
                        <h2 class="h6 fw-bold mb-0" style="color: var(--text-heading);">Format &amp; Metode Tanda Tangan</h2>
                    </div>
                </div>
                <div class="card-sigma-body p-3">

                    <!-- Pilihan Format Tanda Tangan (QR + TTD + Nama vs Hanya QR) -->
                    <div class="mb-3">
                        <label class="sigma-form-label mb-2 d-flex align-items-center gap-2">
                            <i class="bi bi-ui-radios-grid text-sigma-purple"></i>
                            <span>Format Tampilan:</span>
                        </label>
                        <input type="hidden" name="signature_format" id="signatureFormatInput" value="qr_and_signature">
                        <div class="row g-2">
                            <div class="col-6">
                                <div class="border rounded-3 p-3 h-100 format-card active sigma-card-active text-center"
                                     id="btnFormatQrAndSig" role="button" style="cursor: pointer; transition: all 0.2s ease;">
                                    <div class="d-flex align-items-center justify-content-center gap-1 text-sigma-purple mb-1">
                                        <i class="bi bi-pen-fill fs-5"></i>
                                        <i class="bi bi-plus-lg small"></i>
                                        <i class="bi bi-qr-code fs-6"></i>
                                    </div>
                                    <div class="fw-bold small" style="font-size: 0.78rem; color: var(--text-heading);">QR + TTD + Nama</div>
                                    <div class="mt-1" style="font-size: 0.66rem; line-height: 1.25; color: var(--text-muted);">
                                        Coretan, nama, dan QR
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="border rounded-3 p-3 h-100 format-card text-center"
                                     id="btnFormatQrOnly" role="button" style="cursor: pointer; transition: all 0.2s ease;">
                                    <div class="d-flex align-items-center justify-content-center text-sigma-navy mb-1">
                                        <i class="bi bi-qr-code fs-4"></i>
                                    </div>
                                    <div class="fw-bold small" style="font-size: 0.78rem; color: var(--text-heading);">Hanya Kode QR</div>
                                    <div class="mt-1" style="font-size: 0.66rem; line-height: 1.25; color: var(--text-muted);">
                                        Tanpa coretan
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bagian Metode Goresan (Khusus Format QR + TTD + Nama) -->
                    <div id="scribbleMethodSection">
                        <div class="border-top pt-3 mb-2" style="border-color: var(--border-subtle) !important;">
                            <div class="small fw-bold mb-2 d-flex align-items-center gap-2" style="color: var(--text-heading);">
                                <i class="bi bi-palette text-sigma-purple"></i>
                                <span>Sumber Tanda Tangan:</span>
                            </div>
                        </div>

                        <!-- Tab Metode -->
                        <ul class="nav nav-pills nav-fill mb-3 sigma-pills-nav" id="methodTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link <?= $restoreTab === 'template' ? 'active' : '' ?> d-inline-flex align-items-center justify-content-center gap-2 py-2 px-2 small fw-semibold"
                                        id="tab-template-btn" data-bs-toggle="pill" data-bs-target="#tab-template" type="button" role="tab">
                                    <i class="bi bi-person-badge"></i> Template
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link <?= ($restoreTab === 'canvas' || $restoreTab === null) ? 'active' : '' ?> d-inline-flex align-items-center justify-content-center gap-2 py-2 px-2 small fw-semibold"
                                        id="tab-canvas-btn" data-bs-toggle="pill" data-bs-target="#tab-canvas" type="button" role="tab">
                                    <i class="bi bi-brush"></i> Kanvas
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link <?= $restoreTab === 'upload' ? 'active' : '' ?> d-inline-flex align-items-center justify-content-center gap-2 py-2 px-2 small fw-semibold"
                                        id="tab-upload-btn" data-bs-toggle="pill" data-bs-target="#tab-upload" type="button" role="tab">
                                    <i class="bi bi-cloud-arrow-up"></i> Upload PNG
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content" id="methodTabsContent">
                            <!-- TAB 1: Template Tersimpan -->
                            <div class="tab-pane fade <?= $restoreTab === 'template' ? 'show active' : '' ?>" id="tab-template" role="tabpanel">
                                <?php if (empty($templates)): ?>
                                    <div class="alert alert-warning alert-sigma py-2 px-3 small d-flex align-items-start gap-2 mb-0">
                                        <i class="bi bi-exclamation-circle-fill mt-1"></i>
                                        <div>
                                            Belum ada template. Gunakan Kanvas/Upload PNG, atau
                                            <a href="<?= BASE_URL ?>/user-signatures/create" target="_blank" class="fw-semibold" style="color: var(--sigma-purple-700);">buat template</a>.
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <?php if (!$defaultTemplateId): ?>
                                        <div class="sigma-guide-banner py-2 px-3 small mb-2 d-flex align-items-center gap-2">
                                            <i class="bi bi-info-circle-fill text-sigma-purple"></i>
                                            <span>Pilih salah satu template.</span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="row g-2">
                                        <?php foreach ($templates as $tpl): ?>
                                            <?php
                                                $tplId = $tpl['id'];
                                                $isChecked = ($tplId === $defaultTemplateId);
                                            ?>
                                            <div class="col-6">
                                                <label class="d-block border rounded-3 p-2 h-100 position-relative <?= $isChecked ? 'sigma-card-active' : '' ?>"
                                                       style="cursor: pointer; transition: all 0.15s ease;" id="tplLabel_<?= htmlspecialchars($tplId, ENT_QUOTES, 'UTF-8') ?>">
                                                    <input type="radio" name="template_id" value="<?= htmlspecialchars($tplId, ENT_QUOTES, 'UTF-8') ?>"
                                                           class="form-check-input position-absolute top-0 end-0 mt-2 me-2 template-radio"
                                                           <?= $isChecked ? 'checked' : '' ?>>
                                                    <div class="sig-card-preview checkerboard-bg mb-1" style="height: 100px;">
                                                        <img src="<?= BASE_URL ?>/user-signatures/<?= htmlspecialchars($tplId, ENT_QUOTES, 'UTF-8') ?>/preview"
                                                             alt="<?= htmlspecialchars($tpl['label'] ?: 'Template', ENT_QUOTES, 'UTF-8') ?>" loading="lazy">
                                                    </div>
                                                    <div class="text-truncate small fw-semibold" style="font-size: 0.72rem; color: var(--text-heading);">
                                                        <?= htmlspecialchars($tpl['label'] ?: 'Tanda Tangan', ENT_QUOTES, 'UTF-8') ?>
                                                        <?php if ((int) $tpl['is_default'] === 1): ?>
                                                            <span class="badge-sigma badge-sigma-success py-0 px-1 ms-1" style="font-size: 0.58rem;">Default</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- TAB 2: Gores Kanvas Digital -->
                            <div class="tab-pane fade <?= ($restoreTab === 'canvas' || $restoreTab === null) ? 'show active' : '' ?>" id="tab-canvas" role="tabpanel">
                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 p-2 border rounded-top border-bottom-0 small" style="background: var(--sigma-tint-50); border-color: var(--border-subtle) !important;">
                                    <div class="d-flex align-items-center gap-2">
                                        <span style="font-size: 0.72rem; color: var(--text-muted);">Warna:</span>
                                        <button type="button" class="btn btn-sm btn-color p-0 rounded-circle active" data-color="#0f172a" title="Hitam Klasik"
                                                style="width: 20px; height: 20px; background: #0f172a; border: 2px solid #fff; box-shadow: 0 0 0 1px #cbd5e1;"></button>
                                        <button type="button" class="btn btn-sm btn-color p-0 rounded-circle" data-color="#1e3a8a" title="Biru Resmi Dokumen"
                                                style="width: 20px; height: 20px; background: #1e3a8a; border: 2px solid #fff; box-shadow: 0 0 0 1px #cbd5e1;"></button>
                                        <span class="ms-1" style="font-size: 0.72rem; color: var(--text-muted);">Tebal:</span>
                                        <button type="button" class="btn-sigma-outline btn-width py-1 px-2" data-width="1.8" style="font-size: 0.7rem; min-height: 26px;">Tipis</button>
                                        <button type="button" class="btn-sigma-navy btn-width py-1 px-2 active" data-width="2.8" style="font-size: 0.7rem; min-height: 26px;">Sedang</button>
                                        <button type="button" class="btn-sigma-outline btn-width py-1 px-2" data-width="4.2" style="font-size: 0.7rem; min-height: 26px;">Tebal</button>
                                    </div>
                                    <button type="button" id="btnClearCanvas" class="btn-sigma-outline text-danger d-inline-flex align-items-center gap-1 py-1 px-2" style="font-size: 0.72rem; min-height: 26px;">
                                        <i class="bi bi-eraser-fill"></i> Bersihkan
                                    </button>
                                </div>
                                <div class="sig-canvas-wrapper rounded-top-0" style="border-radius: 0;">
                                    <canvas id="sigCanvas" class="sig-canvas" style="height: 170px;"></canvas>
                                    <div class="sig-baseline-hint">
                                        <span><i class="bi bi-pencil me-1"></i>Goreskan tanda tangan Anda di sini</span>
                                        <span>Garis Dasar</span>
                                    </div>
                                </div>
                            </div>

                            <!-- TAB 3: Upload PNG Transparan -->
                            <div class="tab-pane fade <?= $restoreTab === 'upload' ? 'show active' : '' ?>" id="tab-upload" role="tabpanel">
                                <div class="sig-upload-box" id="dropZone" style="padding: 1.5rem 1rem;">
                                    <input type="file" name="signature_file" id="signatureFileInput" accept="image/png" class="d-none">
                                    <div id="uploadPrompt">
                                        <div class="text-sigma-purple fs-2 mb-1"><i class="bi bi-cloud-arrow-up-fill"></i></div>
                                        <div class="fw-bold small mb-1" style="color: var(--text-heading);">Klik untuk Memilih atau Seret ke Sini</div>
                                        <div class="small mb-2" style="color: var(--text-muted);">Hanya <strong>PNG transparan (.png)</strong>, maksimal 2 MB.</div>
                                        <span class="btn-sigma-outline btn-sigma-sm"><i class="bi bi-folder2-open"></i> Pilih Berkas PNG</span>
                                    </div>
                                    <div id="uploadPreviewWrapper" class="d-none">
                                        <div class="checkerboard-bg p-2 rounded border mx-auto d-inline-flex align-items-center justify-content-center mb-2" style="max-width: 280px; height: 130px; overflow: hidden;">
                                            <img id="uploadPreviewImg" src="" alt="Pratinjau Berkas" style="max-height: 100%; max-width: 100%; object-fit: contain;">
                                        </div>
                                        <div class="d-flex align-items-center justify-content-center gap-2">
                                            <span id="uploadFileName" class="small fw-semibold text-truncate" style="max-width: 210px; color: var(--text-heading);"></span>
                                            <button type="button" id="btnRemoveFile" class="btn-sigma-outline text-danger py-1 px-2" style="font-size: 0.72rem; min-height: 26px;">
                                                <i class="bi bi-x-circle me-1"></i> Ganti File
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div> <!-- end scribbleMethodSection -->

                    <!-- Bagian Informasi Format Hanya Kode QR -->
                    <div id="qrOnlyInfoSection" class="sigma-guide-banner d-none mb-0">
                        <div class="d-flex align-items-start gap-2">
                            <i class="bi bi-info-circle-fill text-sigma-purple fs-5 mt-1"></i>
                            <div class="small" style="color: var(--sigma-navy);">
                                Slot akan berisi nama, <?= htmlspecialchars($profileIdLabel, ENT_QUOTES, 'UTF-8') ?>, dan kode QR verifikasi.
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Ringkasan Identitas & Tombol Aksi -->
            <div class="card-sigma mb-4">
                <div class="card-sigma-body p-3">
                    <div class="mb-3">
                        <div class="small text-uppercase fw-bold mb-2" style="font-size: 0.68rem; letter-spacing: 0.05em; color: var(--sigma-purple-700);">
                            Ringkasan Konfirmasi
                        </div>
                        <ul class="list-unstyled small d-flex flex-column gap-2 mb-0">
                            <li class="d-flex justify-content-between align-items-center">
                                <span style="color: var(--text-muted);">Format Pengesahan</span>
                                <span class="badge-sigma badge-sigma-purple" id="summaryFormatLabel">QR + TTD + Nama</span>
                            </li>
                            <li class="d-flex justify-content-between">
                                <span style="color: var(--text-muted);">Penandatangan</span>
                                <span class="fw-semibold" style="color: var(--text-heading);"><?= htmlspecialchars($profileName, ENT_QUOTES, 'UTF-8') ?></span>
                            </li>
                            <li class="d-flex justify-content-between">
                                <span style="color: var(--text-muted);">Jabatan / Peran</span>
                                <span class="fw-semibold" style="color: var(--text-heading);"><?= htmlspecialchars($signer['role_title'] ?: $profilePos, ENT_QUOTES, 'UTF-8') ?></span>
                            </li>
                            <li class="d-flex justify-content-between">
                                <span style="color: var(--text-muted);">Posisi Slot</span>
                                <span class="fw-semibold" style="color: var(--text-heading);">Hal. <?= $pageNo ?> (<?= number_format($slotX, 1) ?>%, <?= number_format($slotY, 1) ?>%)</span>
                            </li>
                            <li class="d-flex justify-content-between">
                                <span style="color: var(--text-muted);">Tanda Tangan Sebelumnya</span>
                                <span class="fw-semibold" style="color: var(--text-heading);"><?= count($signaturesPlaced) ?> dari <?= (int) $totalSteps ?></span>
                            </li>
                        </ul>
                    </div>

                    <div class="d-flex flex-column gap-2">
                        <button type="button" id="btnOpenConfirm" class="btn-sigma-navy w-100 py-2" data-bs-toggle="modal" data-bs-target="#modalConfirm" disabled>
                            <i class="bi bi-pen-fill"></i> Tanda Tangani &amp; Terbitkan
                        </button>
                        <button type="button" class="btn-sigma-outline w-100 py-2 text-danger" data-bs-toggle="modal" data-bs-target="#modalDecline">
                            <i class="bi bi-x-circle"></i> Tolak Berkas
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

    <!-- Modal Konfirmasi Penandatanganan -->
    <div class="modal fade modal-sigma" id="modalConfirm" tabindex="-1" aria-labelledby="modalConfirmLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header text-white" style="background: var(--sigma-navy);">
                    <h5 class="modal-title fs-6 fw-bold" id="modalConfirmLabel">
                        <i class="bi bi-pen-fill me-2"></i> Konfirmasi Penandatanganan Resmi
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="mb-3 small" style="color: var(--text-heading);">
                        Apakah Anda yakin membubuhi tanda tangan resmi pada dokumen
                        <strong>"<?= htmlspecialchars($doc['title'], ENT_QUOTES, 'UTF-8') ?>"</strong>?
                    </p>
                    <div class="d-flex align-items-center justify-content-between p-2 mb-2 rounded border small" style="background: var(--sigma-tint-50); border-color: var(--border-subtle) !important;">
                        <span style="color: var(--text-muted);"><i class="bi bi-ui-radios-grid me-1"></i>Format Tanda Tangan:</span>
                        <span class="badge-sigma badge-sigma-purple" id="modalConfirmFormatBadge">
                            QR + Tanda Tangan + Nama
                        </span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between p-2 mb-3 rounded border small" style="background: var(--sigma-tint-50); border-color: var(--border-subtle) !important;">
                        <span style="color: var(--text-muted);"><i class="bi bi-file-earmark-text me-1"></i>Halaman Penempatan:</span>
                        <span class="badge-sigma badge-sigma-success" id="modalConfirmPageBadge">
                            Halaman <?= $pageNo ?>
                        </span>
                    </div>
                    <div class="checkerboard-bg rounded border p-3 mb-3 d-flex align-items-center justify-content-center" style="height: 130px;">
                        <!-- Preview Format QR + TTD + Nama -->
                        <div id="modalPreviewQrAndSig" class="w-100 h-100 d-flex align-items-center justify-content-center gap-3">
                            <div class="d-flex flex-column align-items-center justify-content-end h-100" style="width: 58%; min-width: 0;">
                                <img id="modalPreviewImg" src="" alt="Pratinjau tanda tangan" class="d-none"
                                     style="max-height: 54px; max-width: 100%; object-fit: contain;">
                                <span id="modalPreviewEmpty" class="small" style="color: var(--text-muted);">Belum ada pratinjau tanda tangan.</span>
                                <div id="modalPreviewName" class="w-100 text-center mt-1 d-none" style="line-height: 1.2;">
                                    <div class="fw-bold text-truncate" style="font-size: 0.78rem; color: var(--text-heading);">
                                        <?= htmlspecialchars($profileName, ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                    <div style="border-top: 1px solid #0f172a; margin: 2px auto; width: 90%;"></div>
                                    <?php if (!empty($profileIdent) && $profileIdent !== '-'): ?>
                                        <div class="text-truncate" style="font-size: 0.7rem; color: var(--text-muted);">
                                            <?= htmlspecialchars($profileIdLabel, ENT_QUOTES, 'UTF-8') ?>: <?= htmlspecialchars($profileIdent, ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="d-flex flex-column align-items-center justify-content-center bg-white border rounded p-2" style="color: var(--sigma-navy);">
                                <i class="bi bi-qr-code fs-2"></i>
                                <span class="fw-semibold" style="font-size: 0.55rem; color: var(--sigma-success-text);">SIGMA Verified</span>
                            </div>
                        </div>
                        <!-- Preview Format Hanya Kode QR -->
                        <div id="modalPreviewQrOnly" class="w-100 h-100 d-none align-items-center justify-content-center gap-3">
                            <div class="d-flex flex-column align-items-center justify-content-center" style="width: 58%; min-width: 0; line-height: 1.25;">
                                <div class="fw-bold text-truncate w-100 text-center" style="font-size: 0.82rem; color: var(--text-heading);">
                                    <?= htmlspecialchars($profileName, ENT_QUOTES, 'UTF-8') ?>
                                </div>
                                <div style="border-top: 1px solid #0f172a; margin: 3px auto; width: 90%;"></div>
                                <?php if (!empty($profileIdent) && $profileIdent !== '-'): ?>
                                    <div class="text-truncate w-100 text-center" style="font-size: 0.72rem; color: var(--text-muted);">
                                        <?= htmlspecialchars($profileIdLabel, ENT_QUOTES, 'UTF-8') ?>: <?= htmlspecialchars($profileIdent, ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                <?php endif; ?>
                                <span class="badge-sigma badge-sigma-purple mt-2" style="font-size: 0.62rem;">
                                    Tanpa Coretan TTD
                                </span>
                            </div>
                            <div class="d-flex flex-column align-items-center justify-content-center bg-white border rounded p-2" style="color: var(--sigma-success-text); border-color: #bbf7d0 !important;">
                                <i class="bi bi-qr-code fs-1"></i>
                                <span class="fw-semibold" style="font-size: 0.55rem;">SIGMA Verified</span>
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-warning alert-sigma py-2 px-3 small mb-0 d-flex align-items-start gap-2">
                        <i class="bi bi-exclamation-triangle-fill mt-1"></i>
                        <div>
                            Tanda tangan bersifat permanen dan <strong>tidak dapat dibatalkan</strong>.
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top py-2 px-3" style="background: var(--sigma-tint-50); border-color: var(--border-subtle) !important;">
                    <button type="button" class="btn-sigma-outline btn-sigma-sm" data-bs-dismiss="modal">Periksa Kembali</button>
                    <button type="button" id="btnConfirmSign" class="btn-sigma-navy btn-sigma-sm px-3">
                        <span class="spinner-border spinner-border-sm d-none me-1" id="confirmSpinner" role="status" aria-hidden="true"></span>
                        <i class="bi bi-check-lg" id="confirmIcon"></i> Tanda Tangani &amp; Terbitkan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Penolakan Berkas oleh Penandatangan -->
    <div class="modal fade modal-sigma" id="modalDecline" tabindex="-1" aria-labelledby="modalDeclineLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header text-white" style="background: var(--sigma-danger-text);">
                    <h5 class="modal-title fs-6 fw-bold" id="modalDeclineLabel">
                        <i class="bi bi-x-octagon-fill me-2"></i> Tolak Berkas (Penolakan Penandatangan)
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="<?= BASE_URL ?>/documents/<?= htmlspecialchars($doc['id'], ENT_QUOTES, 'UTF-8') ?>/decline" method="POST" id="declineForm">
                    <?= Csrf::field() ?>
                    <div class="modal-body p-4">
                        <div class="alert alert-danger alert-sigma py-2 px-3 small mb-3 d-flex align-items-start gap-2"
                             style="background: var(--sigma-danger-bg); border-color: #fecaca; color: var(--sigma-danger-text-dark);">
                            <i class="bi bi-exclamation-triangle-fill mt-1"></i>
                            <div>
                                Penolakan akan <strong>membatalkan seluruh dokumen</strong> secara permanen.
                            </div>
                        </div>
                        <div class="mb-2">
                            <label for="decline_reason" class="sigma-form-label d-block">
                                Alasan Penolakan <span class="text-danger">*</span> <span class="fw-normal" style="color: var(--text-muted);">(minimal 10 karakter)</span>
                            </label>
                            <textarea class="form-control sigma-input" id="decline_reason" name="decline_reason" rows="4"
                                      placeholder="Contoh: lampiran belum lengkap" required></textarea>
                            <div class="form-text text-end" id="declineCounter" style="font-size: 0.72rem; color: var(--text-muted);">0 / minimal 10 karakter</div>
                        </div>
                    </div>
                    <div class="modal-footer border-top py-2 px-3" style="background: var(--sigma-tint-50); border-color: var(--border-subtle) !important;">
                        <button type="button" class="btn-sigma-outline btn-sigma-sm" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" id="btnConfirmDecline" class="btn-sigma-danger btn-sigma-sm px-3" disabled>
                            <i class="bi bi-x-lg"></i> Konfirmasi Tolak Berkas
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<style>
@keyframes slotPulse {
    0%, 100% { box-shadow: 0 0 0 3px rgba(120, 98, 158, 0.42); }
    50%      { box-shadow: 0 0 0 6px rgba(120, 98, 158, 0.16); }
}
.sigma-card-active {
    border-color: var(--sigma-purple-600) !important;
    background-color: var(--sigma-tint-50) !important;
    box-shadow: 0 2px 8px rgba(120, 98, 158, 0.14);
}
.sigma-pills-nav .nav-link {
    color: var(--text-muted);
    background: var(--sigma-tint-50);
    border: 1px solid var(--border-subtle);
    border-radius: 8px;
    margin: 0 2px;
}
.sigma-pills-nav .nav-link.active {
    background: var(--sigma-navy);
    color: #ffffff;
    border-color: var(--sigma-navy);
}
</style>

<!-- JavaScript Interaktif: Kanvas, Template, Upload, Konfirmasi -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const canvas = document.getElementById('sigCanvas');
    const ctx = canvas.getContext('2d');
    const inputMethod = document.getElementById('inputMethod');
    const sigInput = document.getElementById('signatureCanvasInput');
    const form = document.getElementById('signForm');
    const btnClear = document.getElementById('btnClearCanvas');
    const slotPreviewImg = document.getElementById('slotPreviewImg');
    const slotPreviewEmpty = document.getElementById('slotPreviewEmpty');
    const btnOpenConfirm = document.getElementById('btnOpenConfirm');
    const modalPreviewImg = document.getElementById('modalPreviewImg');
    const modalPreviewEmpty = document.getElementById('modalPreviewEmpty');
    const btnConfirmSign = document.getElementById('btnConfirmSign');

    // ── Referensi Elemen PDF.js Viewer & Stage ──
    const loadingSpinner = document.getElementById('pdfLoadingSpinner');
    const fallbackAlert = document.getElementById('pdfFallbackAlert');
    const stageContainer = document.getElementById('pdfStageContainer');
    const slotCanvasContainer = document.getElementById('slotCanvasContainer');
    const renderCanvas = document.getElementById('pdfSignRenderCanvas');
    const fallbackWatermark = document.getElementById('fallbackWatermark');
    const btnPrevPage = document.getElementById('btnPrevPage');
    const btnNextPage = document.getElementById('btnNextPage');
    const pageNumDisplay = document.getElementById('pageNumDisplay');
    const pageTotalDisplay = document.getElementById('pageTotalDisplay');
    const activePageBadge = document.getElementById('activePageBadge');
    const slotPageNotice = document.getElementById('slotPageNotice');
    const btnJumpToSlotPage = document.getElementById('btnJumpToSlotPage');
    const slotCurrentPageNotice = document.getElementById('slotCurrentPageNotice');
    const jumpTargetPage = document.getElementById('jumpTargetPage');
    const activeSlotBox = document.getElementById('activeSlotBox');
    const modalConfirmPageBadge = document.getElementById('modalConfirmPageBadge');

    // ── Referensi Format Tanda Tangan (QR Only vs QR + TTD + Nama) ──
    const signatureFormatInput = document.getElementById('signatureFormatInput');
    const btnFormatQrAndSig = document.getElementById('btnFormatQrAndSig');
    const btnFormatQrOnly = document.getElementById('btnFormatQrOnly');
    const scribbleMethodSection = document.getElementById('scribbleMethodSection');
    const qrOnlyInfoSection = document.getElementById('qrOnlyInfoSection');
    const summaryFormatLabel = document.getElementById('summaryFormatLabel');
    const slotViewQrAndSig = document.getElementById('slotViewQrAndSig');
    const slotViewQrOnly = document.getElementById('slotViewQrOnly');
    const modalConfirmFormatBadge = document.getElementById('modalConfirmFormatBadge');
    const modalPreviewQrAndSig = document.getElementById('modalPreviewQrAndSig');
    const modalPreviewQrOnly = document.getElementById('modalPreviewQrOnly');
    const modalPreviewName = document.getElementById('modalPreviewName');

    // ── Status input ──
    let hasDrawn = false;
    let hasTemplate = <?= json_encode(!empty($templates)) ?>;
    let currentColor = '#0f172a';
    let currentWidth = 2.8;
    let selectedTemplateId = <?= json_encode($defaultTemplateId) ?>;
    let uploadDataUrl = null;

    // ── Pengaturan Format Tanda Tangan ──
    function setSignatureFormat(format) {
        if (!signatureFormatInput) return;
        signatureFormatInput.value = format;

        if (format === 'qr_only') {
            if (btnFormatQrOnly) btnFormatQrOnly.classList.add('active', 'sigma-card-active');
            if (btnFormatQrAndSig) btnFormatQrAndSig.classList.remove('active', 'sigma-card-active');
            if (scribbleMethodSection) scribbleMethodSection.classList.add('d-none');
            if (qrOnlyInfoSection) qrOnlyInfoSection.classList.remove('d-none');

            if (summaryFormatLabel) {
                summaryFormatLabel.textContent = 'Hanya Kode QR';
                summaryFormatLabel.className = 'badge-sigma badge-sigma-violet';
            }
            if (slotViewQrAndSig) {
                slotViewQrAndSig.classList.add('d-none');
                slotViewQrAndSig.classList.remove('d-flex');
            }
            if (slotViewQrOnly) {
                slotViewQrOnly.classList.remove('d-none');
                slotViewQrOnly.classList.add('d-flex');
            }
            if (modalConfirmFormatBadge) {
                modalConfirmFormatBadge.textContent = 'Hanya Kode QR (+ Nama & <?= htmlspecialchars($profileIdLabel) ?>)';
                modalConfirmFormatBadge.className = 'badge-sigma badge-sigma-violet';
            }
            if (modalPreviewQrAndSig) {
                modalPreviewQrAndSig.classList.add('d-none');
                modalPreviewQrAndSig.classList.remove('d-flex');
            }
            if (modalPreviewQrOnly) {
                modalPreviewQrOnly.classList.remove('d-none');
                modalPreviewQrOnly.classList.add('d-flex');
            }

            // Format Hanya QR: langsung aktifkan tombol konfirmasi tanpa goresan kanvas
            if (btnOpenConfirm) btnOpenConfirm.disabled = false;
        } else {
            if (btnFormatQrAndSig) btnFormatQrAndSig.classList.add('active', 'sigma-card-active');
            if (btnFormatQrOnly) btnFormatQrOnly.classList.remove('active', 'sigma-card-active');
            if (scribbleMethodSection) scribbleMethodSection.classList.remove('d-none');
            if (qrOnlyInfoSection) qrOnlyInfoSection.classList.add('d-none');

            if (summaryFormatLabel) {
                summaryFormatLabel.textContent = 'QR + TTD + Nama';
                summaryFormatLabel.className = 'badge-sigma badge-sigma-purple';
            }
            if (slotViewQrAndSig) {
                slotViewQrAndSig.classList.remove('d-none');
                slotViewQrAndSig.classList.add('d-flex');
            }
            if (slotViewQrOnly) {
                slotViewQrOnly.classList.add('d-none');
                slotViewQrOnly.classList.remove('d-flex');
            }
            if (modalConfirmFormatBadge) {
                modalConfirmFormatBadge.textContent = 'QR + Tanda Tangan + Nama';
                modalConfirmFormatBadge.className = 'badge-sigma badge-sigma-purple';
            }
            if (modalPreviewQrAndSig) {
                modalPreviewQrAndSig.classList.remove('d-none');
                modalPreviewQrAndSig.classList.add('d-flex');
            }
            if (modalPreviewQrOnly) {
                modalPreviewQrOnly.classList.add('d-none');
                modalPreviewQrOnly.classList.remove('d-flex');
            }

            // Cek apakah coretan tanda tangan sudah tersedia
            checkScribbleValidity();
        }
    }

    function checkScribbleValidity() {
        if (!signatureFormatInput || signatureFormatInput.value === 'qr_only') {
            if (btnOpenConfirm) btnOpenConfirm.disabled = false;
            return;
        }

        let valid = false;
        if (inputMethod.value === 'canvas') {
            valid = hasDrawn;
        } else if (inputMethod.value === 'upload') {
            valid = (uploadDataUrl !== null);
        } else {
            const checked = document.querySelector('.template-radio:checked');
            valid = (checked !== null);
        }

        if (btnOpenConfirm) btnOpenConfirm.disabled = !valid;
    }

    if (btnFormatQrAndSig) {
        btnFormatQrAndSig.addEventListener('click', function () {
            setSignatureFormat('qr_and_signature');
        });
    }

    if (btnFormatQrOnly) {
        btnFormatQrOnly.addEventListener('click', function () {
            setSignatureFormat('qr_only');
        });
    }

    // ── Pratinjau slot (fit-contain via CSS object-fit) ──
    function showSlotPreview(src) {
        if (!src) {
            slotPreviewImg.classList.add('d-none');
            slotPreviewImg.removeAttribute('src');
            slotPreviewEmpty.classList.remove('d-none');
            modalPreviewImg.classList.add('d-none');
            modalPreviewEmpty.classList.remove('d-none');
            if (modalPreviewName) modalPreviewName.classList.add('d-none');

            if (signatureFormatInput && signatureFormatInput.value === 'qr_and_signature') {
                btnOpenConfirm.disabled = true;
            }
            return;
        }
        slotPreviewImg.src = src;
        slotPreviewImg.classList.remove('d-none');
        slotPreviewEmpty.classList.add('d-none');
        modalPreviewImg.src = src;
        modalPreviewImg.classList.remove('d-none');
        modalPreviewEmpty.classList.add('d-none');
        if (modalPreviewName) modalPreviewName.classList.remove('d-none');

        btnOpenConfirm.disabled = false;
    }

    // ─────────────────────────────────────────────────────────────
    // Integrasi Mozilla PDF.js: Render Lembar Berkas PDF Asli (ADR-09)
    // ─────────────────────────────────────────────────────────────
    const pdfUrl = <?= json_encode(BASE_URL . '/documents/' . $doc['id'] . '/preview') ?>;
    let currentSlotPage = <?= (int) $pageNo ?>;
    let pdfDoc = null;
    let currentPage = currentSlotPage;
    let totalPages = 1;
    let isRendering = false;
    let pageRenderingQueue = null;

    function updateSlotNotice(num) {
        if (!slotPageNotice) return;
        if (num !== currentSlotPage) {
            slotPageNotice.classList.remove('d-none');
            slotPageNotice.classList.add('d-flex');
            if (slotCurrentPageNotice) slotCurrentPageNotice.textContent = currentSlotPage;
            if (jumpTargetPage) jumpTargetPage.textContent = currentSlotPage;
        } else {
            slotPageNotice.classList.add('d-none');
            slotPageNotice.classList.remove('d-flex');
        }
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

            currentPage = Math.min(totalPages, Math.max(1, currentSlotPage));

            if (loadingSpinner) loadingSpinner.classList.add('d-none');
            if (stageContainer) stageContainer.classList.remove('d-none');

            renderPage(currentPage);
        }).catch(function (error) {
            console.warn('PDF.js gagal memuat berkas:', error);
            initFallbackMode();
        });
    } else {
        initFallbackMode();
    }

    function initFallbackMode() {
        if (loadingSpinner) loadingSpinner.classList.add('d-none');
        if (fallbackAlert) fallbackAlert.classList.remove('d-none');
        if (stageContainer) {
            stageContainer.classList.remove('d-none');
            if (slotCanvasContainer) {
                slotCanvasContainer.style.width = '480px';
                slotCanvasContainer.style.height = '678px';
            }
            if (renderCanvas) renderCanvas.style.display = 'none';
            if (fallbackWatermark) {
                fallbackWatermark.classList.remove('d-none');
                fallbackWatermark.classList.add('d-flex');
            }
        }
        updateSlotVisibilityForPage(currentSlotPage);
        updateSlotNotice(currentSlotPage);
    }

    function renderPage(num) {
        if (!pdfDoc) return;

        if (isRendering) {
            pageRenderingQueue = num;
            return;
        }
        isRendering = true;
        currentPage = num;

        if (pageNumDisplay) pageNumDisplay.textContent = num;
        if (activePageBadge) activePageBadge.textContent = num;
        if (btnPrevPage) btnPrevPage.disabled = (num <= 1);
        if (btnNextPage) btnNextPage.disabled = (num >= totalPages);

        updateSlotNotice(num);

        pdfDoc.getPage(num).then(function (page) {
            const parentWidth = stageContainer.parentElement.clientWidth || 560;
            const targetWidth = Math.min(620, Math.max(340, parentWidth - 32));
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

            const renderTask = page.render(renderContext);
            renderTask.promise.then(function () {
                isRendering = false;
                if (pageRenderingQueue !== null) {
                    const queuedPage = pageRenderingQueue;
                    pageRenderingQueue = null;
                    renderPage(queuedPage);
                } else {
                    updateSlotVisibilityForPage(currentPage);
                }
            }).catch(function (err) {
                if (err.name !== 'RenderingCancelledException') {
                    console.error('Render error:', err);
                }
                isRendering = false;
            });
        });
    }

    if (btnPrevPage) {
        btnPrevPage.addEventListener('click', function () {
            if (currentPage > 1) {
                renderPage(currentPage - 1);
            }
        });
    }

    if (btnNextPage) {
        btnNextPage.addEventListener('click', function () {
            if (currentPage < totalPages) {
                renderPage(currentPage + 1);
            }
        });
    }

    if (btnJumpToSlotPage) {
        btnJumpToSlotPage.addEventListener('click', function () {
            renderPage(currentSlotPage);
        });
    }

    function updateSlotVisibilityForPage(page) {
        const overlayItems = document.querySelectorAll('#slotOverlayContainer [data-page]');
        overlayItems.forEach(el => {
            const p = parseInt(el.getAttribute('data-page'), 10) || 1;
            if (p === page) {
                el.classList.remove('d-none');
                el.style.display = '';
            } else {
                el.classList.add('d-none');
                el.style.display = 'none';
            }
        });
    }

    // ── Kanvas: HiDPI-safe (replikasi mitigasi BUG-04) ──
    function resizeCanvas() {
        const rect = canvas.getBoundingClientRect();
        if (rect.width === 0 || rect.height === 0) return;

        const dpr = window.devicePixelRatio || 1;
        const targetWidth = Math.round(rect.width * dpr);
        const targetHeight = Math.round(rect.height * dpr);

        if (canvas.width === targetWidth && canvas.height === targetHeight) {
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
            ctx.strokeStyle = currentColor;
            ctx.lineWidth = currentWidth;
            return;
        }

        let tempCanvas = null;
        if (hasDrawn && canvas.width > 0 && canvas.height > 0) {
            tempCanvas = document.createElement('canvas');
            tempCanvas.width = canvas.width;
            tempCanvas.height = canvas.height;
            const tempCtx = tempCanvas.getContext('2d');
            tempCtx.drawImage(canvas, 0, 0);
        }

        canvas.width = targetWidth;
        canvas.height = targetHeight;

        if (tempCanvas) {
            ctx.drawImage(tempCanvas, 0, 0, targetWidth, targetHeight);
        }

        ctx.scale(dpr, dpr);
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
        ctx.strokeStyle = currentColor;
        ctx.lineWidth = currentWidth;
    }

    resizeCanvas();
    window.addEventListener('resize', function () {
        resizeCanvas();
        if (pdfDoc && !isRendering) {
            renderPage(currentPage);
        }
    });

    function getCoords(e) {
        const rect = canvas.getBoundingClientRect();
        if (e.touches && e.touches.length > 0) {
            return { x: e.touches[0].clientX - rect.left, y: e.touches[0].clientY - rect.top };
        }
        return { x: e.clientX - rect.left, y: e.clientY - rect.top };
    }

    let isDrawing = false;
    let lastX = 0;
    let lastY = 0;

    function startDrawing(e) {
        e.preventDefault();
        isDrawing = true;
        const coords = getCoords(e);
        lastX = coords.x;
        lastY = coords.y;
        ctx.beginPath();
        ctx.arc(lastX, lastY, currentWidth / 2, 0, Math.PI * 2);
        ctx.fillStyle = currentColor;
        ctx.fill();
        hasDrawn = true;
    }

    function draw(e) {
        if (!isDrawing) return;
        e.preventDefault();
        const coords = getCoords(e);
        ctx.beginPath();
        ctx.moveTo(lastX, lastY);
        const midX = (lastX + coords.x) / 2;
        const midY = (lastY + coords.y) / 2;
        ctx.quadraticCurveTo(lastX, lastY, midX, midY);
        ctx.strokeStyle = currentColor;
        ctx.lineWidth = currentWidth;
        ctx.stroke();
        lastX = coords.x;
        lastY = coords.y;
        hasDrawn = true;
    }

    function stopDrawing() {
        if (isDrawing) {
            isDrawing = false;
            if (hasDrawn) {
                const data = getTrimmedCanvasData();
                if (data) {
                    inputMethod.value = 'canvas';
                    showSlotPreview(data);
                }
            }
        }
    }

    canvas.addEventListener('mousedown', startDrawing);
    canvas.addEventListener('mousemove', draw);
    canvas.addEventListener('mouseup', stopDrawing);
    canvas.addEventListener('mouseleave', stopDrawing);
    canvas.addEventListener('touchstart', startDrawing, { passive: false });
    canvas.addEventListener('touchmove', draw, { passive: false });
    canvas.addEventListener('touchend', stopDrawing);

    btnClear.addEventListener('click', function () {
        ctx.save();
        ctx.setTransform(1, 0, 0, 1, 0, 0);
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        ctx.restore();
        hasDrawn = false;
        sigInput.value = '';
        // Jika sedang berada di tab kanvas, kosongkan pratinjau slot
        if (document.getElementById('tab-canvas').classList.contains('active')) {
            inputMethod.value = 'canvas';
            showSlotPreview(null);
        }
    });

    document.querySelectorAll('.btn-color').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.btn-color').forEach(b => {
                b.style.boxShadow = '0 0 0 1px #cbd5e1';
                b.classList.remove('active');
            });
            this.style.boxShadow = '0 0 0 2px var(--sigma-purple-700)';
            this.classList.add('active');
            currentColor = this.getAttribute('data-color');
            ctx.strokeStyle = currentColor;
        });
    });

    document.querySelectorAll('.btn-width').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.btn-width').forEach(b => {
                b.classList.remove('btn-sigma-navy', 'active');
                b.classList.add('btn-sigma-outline');
            });
            this.classList.remove('btn-sigma-outline');
            this.classList.add('btn-sigma-navy', 'active');
            currentWidth = parseFloat(this.getAttribute('data-width'));
            ctx.lineWidth = currentWidth;
        });
    });

    // Trim tepi transparan agar hasil bersih (sama seperti modul template)
    function getTrimmedCanvasData() {
        const width = canvas.width;
        const height = canvas.height;
        const imgData = ctx.getImageData(0, 0, width, height);
        const data = imgData.data;

        let minX = width, minY = height, maxX = 0, maxY = 0;
        let found = false;

        for (let y = 0; y < height; y++) {
            for (let x = 0; x < width; x++) {
                const alpha = data[(y * width + x) * 4 + 3];
                if (alpha > 10) {
                    if (x < minX) minX = x;
                    if (x > maxX) maxX = x;
                    if (y < minY) minY = y;
                    if (y > maxY) maxY = y;
                    found = true;
                }
            }
        }

        if (!found) return null;

        const dpr = window.devicePixelRatio || 1;
        const pad = Math.round(15 * dpr);
        minX = Math.max(0, minX - pad);
        minY = Math.max(0, minY - pad);
        maxX = Math.min(width, maxX + pad);
        maxY = Math.min(height, maxY + pad);

        const trimWidth = maxX - minX;
        const trimHeight = maxY - minY;

        const trimCanvas = document.createElement('canvas');
        trimCanvas.width = trimWidth;
        trimCanvas.height = trimHeight;
        const trimCtx = trimCanvas.getContext('2d');
        trimCtx.drawImage(canvas, minX, minY, trimWidth, trimHeight, 0, 0, trimWidth, trimHeight);
        return trimCanvas.toDataURL('image/png');
    }

    // ── Tab tracking ──
    document.getElementById('tab-canvas-btn').addEventListener('shown.bs.tab', function () {
        inputMethod.value = 'canvas';
        resizeCanvas();
        if (hasDrawn) {
            const data = getTrimmedCanvasData();
            if (data) {
                showSlotPreview(data);
            } else {
                showSlotPreview(null);
            }
        } else {
            showSlotPreview(null);
        }
    });

    document.getElementById('tab-upload-btn').addEventListener('shown.bs.tab', function () {
        inputMethod.value = 'upload';
        if (uploadDataUrl) {
            showSlotPreview(uploadDataUrl);
        } else {
            showSlotPreview(null);
        }
    });

    // ── Seleksi template ──
    document.querySelectorAll('.template-radio').forEach(radio => {
        radio.addEventListener('change', function () {
            selectedTemplateId = this.value;
            inputMethod.value = 'template';
            showSlotPreview('<?= BASE_URL ?>/user-signatures/' + this.value + '/preview');
            document.querySelectorAll('[id^="tplLabel_"]').forEach(l => {
                l.classList.remove('sigma-card-active');
            });
            document.getElementById('tplLabel_' + this.value).classList.add('sigma-card-active');
        });
    });

    document.getElementById('tab-template-btn').addEventListener('shown.bs.tab', function () {
        inputMethod.value = 'template';
        if (hasTemplate) {
            const checked = document.querySelector('.template-radio:checked');
            if (checked) {
                selectedTemplateId = checked.value;
                showSlotPreview('<?= BASE_URL ?>/user-signatures/' + checked.value + '/preview');
            } else {
                showSlotPreview(null);
            }
        } else {
            showSlotPreview(null);
        }
    });

    // ── Upload PNG (sekali pakai) ──
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('signatureFileInput');
    const uploadPrompt = document.getElementById('uploadPrompt');
    const uploadPreviewWrapper = document.getElementById('uploadPreviewWrapper');
    const uploadPreviewImg = document.getElementById('uploadPreviewImg');
    const uploadFileName = document.getElementById('uploadFileName');
    const btnRemoveFile = document.getElementById('btnRemoveFile');

    dropZone.addEventListener('click', function (e) {
        if (e.target !== btnRemoveFile && !btnRemoveFile.contains(e.target)) {
            fileInput.click();
        }
    });

    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, function (e) {
            e.preventDefault();
            e.stopPropagation();
            dropZone.classList.add('dragover');
        });
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, function (e) {
            e.preventDefault();
            e.stopPropagation();
            dropZone.classList.remove('dragover');
        });
    });

    dropZone.addEventListener('drop', function (e) {
        if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
            fileInput.files = e.dataTransfer.files;
            handleFileSelect(fileInput.files[0]);
        }
    });

    fileInput.addEventListener('change', function () {
        if (this.files && this.files.length > 0) {
            handleFileSelect(this.files[0]);
        }
    });

    function handleFileSelect(file) {
        if (!file) return;
        if (file.type !== 'image/png') {
            alert('Berkas harus berformat PNG.');
            fileInput.value = '';
            return;
        }
        if (file.size > 2 * 1024 * 1024) {
            alert('Ukuran berkas melebihi batas maksimal 2 MB.');
            fileInput.value = '';
            return;
        }
        const reader = new FileReader();
        reader.onload = function (e) {
            uploadDataUrl = e.target.result;
            uploadPreviewImg.src = uploadDataUrl;
            uploadFileName.textContent = file.name;
            uploadPrompt.classList.add('d-none');
            uploadPreviewWrapper.classList.remove('d-none');
            inputMethod.value = 'upload';
            showSlotPreview(uploadDataUrl);
        };
        reader.readAsDataURL(file);
    }

    btnRemoveFile.addEventListener('click', function (e) {
        e.stopPropagation();
        fileInput.value = '';
        uploadDataUrl = null;
        uploadPreviewImg.src = '';
        uploadFileName.textContent = '';
        uploadPreviewWrapper.classList.add('d-none');
        uploadPrompt.classList.remove('d-none');
        inputMethod.value = 'upload';
        showSlotPreview(null);
    });

    // ── Inisialisasi pratinjau awal ──
    if (selectedTemplateId && hasTemplate) {
        showSlotPreview('<?= BASE_URL ?>/user-signatures/' + selectedTemplateId + '/preview');
    }

    // ── Buka Modal Konfirmasi (Trigger Ganda Bootstrap + JS) ──
    btnOpenConfirm.addEventListener('click', function () {
        if (this.disabled) return;
        if (modalConfirmPageBadge) {
            modalConfirmPageBadge.textContent = 'Halaman ' + currentSlotPage + (totalPages > 1 ? (' dari ' + totalPages) : '');
        }
        const modalEl = document.getElementById('modalConfirm');
        if (modalEl && typeof bootstrap !== 'undefined') {
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        }
    });

    // ── Konfirmasi final (validasi + submit) ──
    btnConfirmSign.addEventListener('click', function () {
        const isQrOnly = (signatureFormatInput && signatureFormatInput.value === 'qr_only');

        if (!isQrOnly) {
            if (inputMethod.value === 'canvas') {
                if (!hasDrawn) {
                    alert('Silakan goreskan tanda tangan Anda pada kanvas terlebih dahulu.');
                    return;
                }
                const trimmedData = getTrimmedCanvasData();
                if (!trimmedData) {
                    alert('Kanvas tanda tangan masih kosong.');
                    return;
                }
                sigInput.value = trimmedData;
            } else if (inputMethod.value === 'upload') {
                if (!fileInput.files || fileInput.files.length === 0) {
                    alert('Silakan pilih berkas gambar PNG tanda tangan untuk diunggah.');
                    return;
                }
            } else {
                const checked = document.querySelector('.template-radio:checked');
                if (!checked) {
                    alert('Silakan pilih salah satu template tanda tangan tersimpan.');
                    return;
                }
            }
        }

        // Loading state anti double-submit (UX guideline)
        btnConfirmSign.disabled = true;
        document.getElementById('confirmSpinner').classList.remove('d-none');
        document.getElementById('confirmIcon').classList.add('d-none');
        form.submit();
    });

    // ── Modal penolakan: counter minimal 10 karakter ──
    const declineReason = document.getElementById('decline_reason');
    const declineCounter = document.getElementById('declineCounter');
    const btnConfirmDecline = document.getElementById('btnConfirmDecline');

    declineReason.addEventListener('input', function () {
        const len = this.value.trim().length;
        declineCounter.textContent = len + ' / minimal 10 karakter';
        declineCounter.classList.toggle('text-danger', len > 0 && len < 10);
        declineCounter.classList.toggle('text-success', len >= 10);
        btnConfirmDecline.disabled = (len < 10);
    });

    document.getElementById('declineForm').addEventListener('submit', function (e) {
        if (declineReason.value.trim().length < 10) {
            e.preventDefault();
            alert('Alasan penolakan wajib diisi minimal 10 karakter.');
            return false;
        }
        btnConfirmDecline.disabled = true;
    });
});
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
