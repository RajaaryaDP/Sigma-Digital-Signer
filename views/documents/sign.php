<?php
$title = 'Tanda Tangani Dokumen — ' . htmlspecialchars($doc['title']) . ' — SIGMA';
require_once __DIR__ . '/../layout/header.php';

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
?>

<!-- Back Link -->
<div class="mb-3">
    <a href="<?= BASE_URL ?>/documents/<?= htmlspecialchars($doc['id']) ?>" class="text-decoration-none text-muted small d-inline-flex align-items-center gap-1.5">
        <i class="bi bi-arrow-left"></i>
        <span>Kembali ke Detail Dokumen</span>
    </a>
</div>

<!-- Header Penandatanganan -->
<div class="card-clean mb-4">
    <div class="card-clean-body p-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3">
            <div>
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                    <span class="badge bg-warning text-dark py-1.5 px-2.5 fs-7 d-inline-flex align-items-center gap-1">
                        <i class="bi bi-pen-fill"></i>
                        Giliran Anda — Step <?= (int) $signer['step_order'] ?> dari <?= (int) $totalSteps ?>
                    </span>
                    <span class="badge bg-light text-dark border">
                        <i class="bi <?= $isSingle ? 'bi-person-check' : 'bi-diagram-3' ?> me-1"></i>
                        <?= $isSingle ? 'Tanda Tangan Mandiri' : 'Tanda Tangan Berjenjang' ?>
                    </span>
                    <?php if (!empty($signer['role_title'])): ?>
                        <span class="badge bg-light text-dark border">
                            <i class="bi bi-briefcase me-1"></i><?= htmlspecialchars($signer['role_title']) ?>
                        </span>
                    <?php endif; ?>
                </div>
                <h4 class="fw-bold text-dark mb-1"><?= htmlspecialchars($doc['title']) ?></h4>
                <p class="text-muted small mb-0">
                    Diajukan oleh: <strong><?= htmlspecialchars($doc['uploader_name']) ?></strong>
                    &bull; Berkas: <?= htmlspecialchars($doc['file_name_original']) ?>
                </p>
            </div>
            <div class="flex-shrink-0 border rounded-3 p-3 bg-light" style="min-width: 250px;">
                <div class="text-muted small text-uppercase mb-1" style="font-size: 0.7rem; letter-spacing: 0.5px;">
                    Identitas Penandatangan (Biodata Resmi)
                </div>
                <div class="fw-bold text-dark small"><?= htmlspecialchars($profileName) ?></div>
                <div class="text-muted small"><?= htmlspecialchars($profileIdent) ?></div>
                <div class="text-muted small"><?= htmlspecialchars($profilePos) ?></div>
                <div class="text-muted small"><?= htmlspecialchars($profileDept) ?></div>
            </div>
        </div>
    </div>
</div>

<?php if (isset($errors['signature'])): ?>
    <div class="alert alert-danger d-flex align-items-center gap-2 py-2.5 px-3 mb-4 small" role="alert">
        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
        <div><?= htmlspecialchars($errors['signature']) ?></div>
    </div>
<?php endif; ?>

<!-- Alert Petunjuk Slot Terkunci -->
<div class="alert alert-info py-2.5 px-3 mb-4 d-flex align-items-start gap-2.5" style="border-radius: var(--radius-md);">
    <i class="bi bi-lock-fill text-info mt-0.5 fs-5"></i>
    <div class="small">
        <strong>Slot Tanda Tangan Terkunci (ADR-08):</strong> Posisi slot telah ditentukan oleh pengaju.
        Cukup pilih salah satu metode tanda tangan di panel kanan — pratinjau langsung tampil di dalam kotak slot Anda.
        Setelah dikonfirmasi, tindakan <strong>tidak dapat dibatalkan</strong> dan stempel QR verifikasi akan dibubuhkan otomatis.
    </div>
</div>

<form id="signForm" action="<?= BASE_URL ?>/documents/<?= htmlspecialchars($doc['id']) ?>/sign" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="input_method" id="inputMethod" value="<?= htmlspecialchars($restoreTab === 'canvas' || $restoreTab === 'upload' ? $restoreTab : 'template') ?>">
    <input type="hidden" name="signature_canvas" id="signatureCanvasInput">

    <div class="row g-4">
        <!-- Kolom Kiri: Pratinjau Dokumen & Slot Terkunci -->
        <div class="col-lg-7">
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
                            style="width: 100%; height: 460px; border: none;" title="Pratinjau PDF"></iframe>
                </div>
            </div>

            <div class="card-clean mb-4">
                <div class="card-clean-header py-2.5 px-3 d-flex justify-content-between align-items-center">
                    <span class="fw-semibold text-dark small d-flex align-items-center gap-1.5">
                        <i class="bi bi-bounding-box text-success"></i>
                        <span>Slot Tanda Tangan Anda — Halaman <?= $pageNo ?> (Terkunci)</span>
                    </span>
                    <span class="badge bg-light text-dark border"><i class="bi bi-lock me-1"></i>Dikunci Pengaju</span>
                </div>
                <div class="card-clean-body p-3 bg-light text-center">
                    <!-- Kanvas Representasi Lembar PDF (Rasio A4 = 1 : 1.414) -->
                    <div class="d-inline-block text-start" style="max-width: 100%;">
                        <div id="slotCanvasContainer" class="position-relative bg-white shadow-sm border rounded mx-auto overflow-hidden"
                             style="width: 480px; height: 678px; max-width: 100%;">
                            <!-- Watermark latar lembar -->
                            <div class="position-absolute w-100 h-100 d-flex flex-column align-items-center justify-content-center pointer-events-none opacity-25" style="user-select: none;">
                                <i class="bi bi-file-earmark-text display-1 text-muted"></i>
                                <span class="fw-semibold text-muted small mt-2">Lembar Halaman <?= $pageNo ?></span>
                            </div>

                            <!-- Kotak slot milik penandatangan lain (teredam) -->
                            <?php foreach ($allSigners as $s): ?>
                                <?php if ($s['id'] === $signer['id']) continue; ?>
                                <?php
                                    $ox = (float) $s['x_percent'];
                                    $oy = (float) $s['y_percent'];
                                    $ow = (float) $s['width_percent'] > 0 ? (float) $s['width_percent'] : 20.0;
                                    $oh = (float) $s['height_percent'] > 0 ? (float) $s['height_percent'] : 10.0;
                                    if ($ox == 0 && $oy == 0) continue;
                                ?>
                                <div class="position-absolute rounded border bg-light d-flex flex-column align-items-center justify-content-center"
                                     style="left: <?= $ox ?>%; top: <?= $oy ?>%; width: <?= $ow ?>%; height: <?= $oh ?>%;
                                            border-color: #cbd5e1 !important; border-width: 1px !important; opacity: 0.55; z-index: 5;">
                                    <span class="text-muted text-truncate" style="font-size: 0.6rem; max-width: 95%;">
                                        <i class="bi bi-lock-fill me-0.5"></i><?= htmlspecialchars($s['role_title'] ?: $s['signer_name']) ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>

                            <!-- Slot aktif milik penandatangan saat ini -->
                            <div id="activeSlotBox" class="position-absolute rounded border text-center overflow-hidden"
                                 style="left: <?= $slotX ?>%; top: <?= $slotY ?>%; width: <?= $slotW ?>%; height: <?= $slotH ?>%;
                                        background-color: rgba(16, 185, 129, 0.08); border-color: #059669 !important; border-width: 2px !important;
                                        z-index: 10; animation: slotPulse 2.2s ease-in-out infinite;">
                                <span class="badge bg-success position-absolute top-0 start-0 m-1 px-1.5 py-0.5" style="font-size: 0.62rem;">
                                    Slot Anda &bull; Step <?= (int) $signer['step_order'] ?>
                                </span>
                                <!-- Pratinjau tanda tangan dengan fit-contain (BUG-07) -->
                                <div class="position-absolute" style="inset: 8% 8% 18% 8%;">
                                    <img id="slotPreviewImg" src="" alt="Pratinjau tanda tangan"
                                         class="d-none"
                                         style="width: 100%; height: 100%; object-fit: contain;"
                                         title="Pratinjau tanda tangan (rasio aspek dipertahankan)">
                                    <div id="slotPreviewEmpty" class="w-100 h-100 d-flex align-items-center justify-content-center">
                                        <span class="text-muted" style="font-size: 0.65rem;">
                                            <i class="bi bi-pen me-1"></i>Pilih metode tanda tangan untuk melihat pratinjau
                                        </span>
                                    </div>
                                </div>
                                <!-- Stempel QR placeholder -->
                                <div class="position-absolute bottom-0 end-0 m-1 d-flex flex-column align-items-center justify-content-center checkerboard-bg rounded border"
                                     style="width: 16%; height: 16%; min-width: 28px; min-height: 28px; font-size: 0.7rem;"
                                     title="QR Code verifikasi akan dibubuhkan otomatis di samping tanda tangan">
                                    <i class="bi bi-qr-code text-dark"></i>
                                    <span style="font-size: 0.45rem; line-height: 1;">QR</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="text-muted mt-2" style="font-size: 0.75rem;">
                        <i class="bi bi-hand-index-thumb me-1"></i>
                        Posisi slot dikunci oleh pengaju. Pratinjau tanda tangan tampil di tengah kotak dengan rasio aspek asli (fit-contain).
                    </div>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Metode Tanda Tangan & Konfirmasi -->
        <div class="col-lg-5">
            <div class="card-clean mb-4">
                <div class="card-clean-header py-3 px-3">
                    <div>
                        <h6 class="fw-bold text-dark mb-0">Metode Tanda Tangan</h6>
                        <small class="text-muted">Pilih template tersimpan, gores di kanvas, atau unggah PNG transparan</small>
                    </div>
                </div>
                <div class="card-clean-body p-3">

                    <!-- Tab Metode -->
                    <ul class="nav nav-pills nav-fill mb-3" id="methodTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?= $restoreTab === 'template' ? 'active' : '' ?> d-inline-flex align-items-center justify-content-center gap-2 py-2 px-2 small fw-semibold"
                                    id="tab-template-btn" data-bs-toggle="pill" data-bs-target="#tab-template" type="button" role="tab">
                                <i class="bi bi-person-badge"></i> Template Tersimpan
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?= ($restoreTab === 'canvas' || $restoreTab === null) ? 'active' : '' ?> d-inline-flex align-items-center justify-content-center gap-2 py-2 px-2 small fw-semibold"
                                    id="tab-canvas-btn" data-bs-toggle="pill" data-bs-target="#tab-canvas" type="button" role="tab">
                                <i class="bi bi-brush"></i> Gores Kanvas
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
                                <div class="alert alert-warning py-2.5 px-3 small d-flex align-items-start gap-2 mb-0" style="border-radius: var(--radius-md);">
                                    <i class="bi bi-exclamation-circle-fill mt-0.5"></i>
                                    <div>
                                        <strong>Anda belum memiliki template tanda tangan (BUG-08).</strong><br>
                                        Silakan gunakan tab <em>"Gores Kanvas"</em> atau <em>"Upload PNG"</em>, atau buat template terlebih dahulu di menu
                                        <a href="<?= BASE_URL ?>/user-signatures/create" target="_blank" class="fw-semibold">Template TTD</a>.
                                    </div>
                                </div>
                            <?php else: ?>
                                <?php if (!$defaultTemplateId): ?>
                                    <div class="alert alert-info py-2 px-3 small mb-2 d-flex align-items-center gap-2" style="border-radius: var(--radius-md);">
                                        <i class="bi bi-info-circle-fill"></i>
                                        <span>Tidak ada template default — silakan pilih salah satu di bawah atau gunakan metode lain.</span>
                                    </div>
                                <?php endif; ?>
                                <div class="row g-2">
                                    <?php foreach ($templates as $tpl): ?>
                                        <?php
                                            $tplId = $tpl['id'];
                                            $isChecked = ($tplId === $defaultTemplateId);
                                        ?>
                                        <div class="col-6">
                                            <label class="d-block border rounded-3 p-2 h-100 position-relative <?= $isChecked ? 'border-success bg-light' : '' ?>"
                                                   style="cursor: pointer; transition: all 0.15s ease;" id="tplLabel_<?= htmlspecialchars($tplId) ?>">
                                                <input type="radio" name="template_id" value="<?= htmlspecialchars($tplId) ?>"
                                                       class="form-check-input position-absolute top-0 end-0 mt-1.5 me-1.5 template-radio"
                                                       <?= $isChecked ? 'checked' : '' ?>>
                                                <div class="sig-card-preview checkerboard-bg mb-1" style="height: 100px;">
                                                    <img src="<?= BASE_URL ?>/user-signatures/<?= htmlspecialchars($tplId) ?>/preview"
                                                         alt="<?= htmlspecialchars($tpl['label'] ?: 'Template') ?>" loading="lazy">
                                                </div>
                                                <div class="text-truncate small fw-semibold text-dark" style="font-size: 0.72rem;">
                                                    <?= htmlspecialchars($tpl['label'] ?: 'Tanda Tangan') ?>
                                                    <?php if ((int) $tpl['is_default'] === 1): ?>
                                                        <span class="badge bg-success ms-1" style="font-size: 0.58rem;">Default</span>
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
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 p-2 bg-light border rounded-top border-bottom-0 small">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="text-muted" style="font-size: 0.72rem;">Warna:</span>
                                    <button type="button" class="btn btn-sm btn-color p-0 rounded-circle active" data-color="#0f172a" title="Hitam Klasik"
                                            style="width: 20px; height: 20px; background: #0f172a; border: 2px solid #fff; box-shadow: 0 0 0 1px #cbd5e1;"></button>
                                    <button type="button" class="btn btn-sm btn-color p-0 rounded-circle" data-color="#1e3a8a" title="Biru Resmi Dokumen"
                                            style="width: 20px; height: 20px; background: #1e3a8a; border: 2px solid #fff; box-shadow: 0 0 0 1px #cbd5e1;"></button>
                                    <span class="text-muted ms-1" style="font-size: 0.72rem;">Ketebalan:</span>
                                    <button type="button" class="btn btn-sm btn-subtle btn-width py-0.5 px-2" data-width="1.8" style="font-size: 0.7rem;">Tipis</button>
                                    <button type="button" class="btn btn-sm btn-pnj-primary btn-width py-0.5 px-2 active" data-width="2.8" style="font-size: 0.7rem;">Sedang</button>
                                    <button type="button" class="btn btn-sm btn-subtle btn-width py-0.5 px-2" data-width="4.2" style="font-size: 0.7rem;">Tebal</button>
                                </div>
                                <button type="button" id="btnClearCanvas" class="btn btn-subtle btn-sm text-danger border-danger-subtle d-inline-flex align-items-center gap-1 py-1 px-2.5" style="font-size: 0.72rem;">
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
                            <div class="form-text mt-1 text-muted" style="font-size: 0.72rem;">
                                <i class="bi bi-info-circle me-1"></i>Mendukung sentuhan layar HP/tablet. Goresan diproses dengan latar belakang transparan.
                            </div>
                        </div>

                        <!-- TAB 3: Upload PNG Transparan -->
                        <div class="tab-pane fade <?= $restoreTab === 'upload' ? 'show active' : '' ?>" id="tab-upload" role="tabpanel">
                            <div class="sig-upload-box" id="dropZone" style="padding: 1.5rem 1rem;">
                                <input type="file" name="signature_file" id="signatureFileInput" accept="image/png" class="d-none">
                                <div id="uploadPrompt">
                                    <div class="text-success fs-2 mb-1"><i class="bi bi-cloud-arrow-up-fill"></i></div>
                                    <div class="fw-bold text-dark small mb-1">Klik untuk Memilih atau Seret ke Sini</div>
                                    <div class="text-muted small mb-2">Hanya <strong>PNG transparan (.png)</strong>, maksimal 2 MB.</div>
                                    <span class="btn btn-subtle btn-sm px-3"><i class="bi bi-folder2-open me-1"></i> Pilih Berkas PNG</span>
                                </div>
                                <div id="uploadPreviewWrapper" class="d-none">
                                    <div class="checkerboard-bg p-2 rounded border mx-auto d-inline-flex align-items-center justify-content-center mb-2" style="max-width: 280px; height: 130px; overflow: hidden;">
                                        <img id="uploadPreviewImg" src="" alt="Pratinjau Berkas" style="max-height: 100%; max-width: 100%; object-fit: contain;">
                                    </div>
                                    <div class="d-flex align-items-center justify-content-center gap-2">
                                        <span id="uploadFileName" class="small fw-semibold text-dark text-truncate" style="max-width: 210px;"></span>
                                        <button type="button" id="btnRemoveFile" class="btn btn-subtle btn-sm text-danger border-danger-subtle py-0.5 px-2" style="font-size: 0.72rem;">
                                            <i class="bi bi-x-circle me-1"></i> Ganti File
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="form-text mt-1 text-muted" style="font-size: 0.72rem;">
                                <i class="bi bi-lightbulb me-1"></i>Gunakan gambar berlatar transparan agar menyatu dengan lembar PDF tanpa menutupi teks.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ringkasan Identitas & Tombol Aksi -->
            <div class="card-clean mb-4">
                <div class="card-clean-body p-3">
                    <div class="mb-3">
                        <div class="text-muted small text-uppercase mb-2" style="font-size: 0.7rem; letter-spacing: 0.5px;">
                            Ringkasan Konfirmasi
                        </div>
                        <ul class="list-unstyled small d-flex flex-column gap-1.5 mb-0">
                            <li class="d-flex justify-content-between">
                                <span class="text-muted">Penandatangan</span>
                                <span class="fw-semibold text-dark"><?= htmlspecialchars($profileName) ?></span>
                            </li>
                            <li class="d-flex justify-content-between">
                                <span class="text-muted">Jabatan / Peran</span>
                                <span class="fw-semibold text-dark"><?= htmlspecialchars($signer['role_title'] ?: $profilePos) ?></span>
                            </li>
                            <li class="d-flex justify-content-between">
                                <span class="text-muted">Posisi Slot</span>
                                <span class="fw-semibold text-dark">Hal. <?= $pageNo ?> (<?= number_format($slotX, 1) ?>%, <?= number_format($slotY, 1) ?>%)</span>
                            </li>
                            <li class="d-flex justify-content-between">
                                <span class="text-muted">Tanda Tangan Sebelumnya</span>
                                <span class="fw-semibold text-dark"><?= count($signaturesPlaced) ?> dari <?= (int) $totalSteps ?></span>
                            </li>
                        </ul>
                    </div>

                    <div class="d-flex flex-column gap-2">
                        <button type="button" id="btnOpenConfirm" class="btn btn-pnj-primary w-100 py-2 fw-semibold" disabled>
                            <i class="bi bi-pen-fill me-1.5"></i> Tanda Tangani &amp; Terbitkan
                        </button>
                        <button type="button" class="btn btn-outline-danger w-100 py-2" data-bs-toggle="modal" data-bs-target="#modalDecline">
                            <i class="bi bi-x-circle me-1.5"></i> Tolak Berkas
                        </button>
                    </div>
                    <div class="text-center mt-2.5">
                        <small class="text-muted" style="font-size: 0.72rem;">
                            <i class="bi bi-shield-lock me-1"></i>Setelah dikonfirmasi, tanda tangan bersifat permanen dan tidak dapat ditarik kembali.
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Konfirmasi Penandatanganan -->
    <div class="modal fade" id="modalConfirm" tabindex="-1" aria-labelledby="modalConfirmLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fs-6 fw-bold" id="modalConfirmLabel">
                        <i class="bi bi-pen-fill me-1.5"></i> Konfirmasi Penandatanganan Resmi
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="mb-3 text-dark small">
                        Apakah Anda yakin membubuhi tanda tangan resmi pada dokumen
                        <strong>"<?= htmlspecialchars($doc['title']) ?>"</strong>?
                    </p>
                    <div class="checkerboard-bg rounded border p-3 mb-3 d-flex align-items-center justify-content-center" style="height: 110px;">
                        <img id="modalPreviewImg" src="" alt="Pratinjau tanda tangan" class="d-none"
                             style="max-height: 100%; max-width: 100%; object-fit: contain;">
                        <span id="modalPreviewEmpty" class="text-muted small">Belum ada pratinjau tanda tangan.</span>
                    </div>
                    <div class="alert alert-warning py-2.5 px-3 small mb-0 d-flex align-items-start gap-2" style="border-radius: var(--radius-md);">
                        <i class="bi bi-exclamation-triangle-fill mt-0.5"></i>
                        <div>
                            Tindakan ini <strong>tidak dapat dibatalkan</strong>. Tanda tangan visual beserta stempel QR verifikasi
                            identitas Anda akan tercatat permanen pada jejak audit dokumen.
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2 px-3">
                    <button type="button" class="btn btn-subtle btn-sm" data-bs-dismiss="modal">Periksa Kembali</button>
                    <button type="button" id="btnConfirmSign" class="btn btn-success btn-sm px-3 fw-semibold">
                        <span class="spinner-border spinner-border-sm d-none me-1" id="confirmSpinner" role="status" aria-hidden="true"></span>
                        <i class="bi bi-check-lg me-1" id="confirmIcon"></i> Tanda Tangani &amp; Terbitkan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Penolakan Berkas oleh Penandatangan -->
    <div class="modal fade" id="modalDecline" tabindex="-1" aria-labelledby="modalDeclineLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title fs-6 fw-bold" id="modalDeclineLabel">
                        <i class="bi bi-x-octagon-fill me-1.5"></i> Tolak Berkas (Penolakan Penandatangan)
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="<?= BASE_URL ?>/documents/<?= htmlspecialchars($doc['id']) ?>/decline" method="POST" id="declineForm">
                    <div class="modal-body p-4">
                        <div class="alert alert-danger py-2.5 px-3 small mb-3 d-flex align-items-start gap-2" style="border-radius: var(--radius-md);">
                            <i class="bi bi-exclamation-triangle-fill mt-0.5"></i>
                            <div>
                                <strong>Peringatan Kebijakan Kampus (ADR-08):</strong> Penolakan satu pihak
                                <strong>membatalkan seluruh dokumen</strong> menjadi Arsip Batal Permanen. Proses tidak dapat diulang.
                            </div>
                        </div>
                        <div class="mb-2">
                            <label for="decline_reason" class="form-label small fw-semibold text-dark">
                                Alasan Penolakan <span class="text-danger">*</span> <span class="text-muted fw-normal">(minimal 10 karakter)</span>
                            </label>
                            <textarea class="form-control form-control-sm" id="decline_reason" name="decline_reason" rows="4"
                                      placeholder="Jelaskan alasan Anda menolak berkas ini, misalnya: Berkas lampiran tidak lengkap dan perlu direvisi..." required></textarea>
                            <div class="form-text text-end" id="declineCounter" style="font-size: 0.72rem;">0 / minimal 10 karakter</div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light py-2 px-3">
                        <button type="button" class="btn btn-subtle btn-sm" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" id="btnConfirmDecline" class="btn btn-danger btn-sm px-3 fw-semibold" disabled>
                            <i class="bi bi-x-lg me-1"></i> Konfirmasi Tolak Berkas
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</form>

<style>
@keyframes slotPulse {
    0%, 100% { box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.35); }
    50%      { box-shadow: 0 0 0 6px rgba(5, 150, 105, 0.15); }
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

    // ── Status input ──
    let hasDrawn = false;
    let hasTemplate = <?= json_encode(!empty($templates)) ?>;
    let currentColor = '#0f172a';
    let currentWidth = 2.8;
    let selectedTemplateId = <?= json_encode($defaultTemplateId) ?>;
    let uploadDataUrl = null;

    // ── Pratinjau slot (fit-contain via CSS object-fit) ──
    function showSlotPreview(src) {
        if (!src) {
            slotPreviewImg.classList.add('d-none');
            slotPreviewImg.removeAttribute('src');
            slotPreviewEmpty.classList.remove('d-none');
            modalPreviewImg.classList.add('d-none');
            modalPreviewEmpty.classList.remove('d-none');
            btnOpenConfirm.disabled = true;
            return;
        }
        slotPreviewImg.src = src;
        slotPreviewImg.classList.remove('d-none');
        slotPreviewEmpty.classList.add('d-none');
        modalPreviewImg.src = src;
        modalPreviewImg.classList.remove('d-none');
        modalPreviewEmpty.classList.add('d-none');
        btnOpenConfirm.disabled = false;
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
    window.addEventListener('resize', resizeCanvas);

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
            this.style.boxShadow = '0 0 0 2px var(--pnj-green-800)';
            this.classList.add('active');
            currentColor = this.getAttribute('data-color');
            ctx.strokeStyle = currentColor;
        });
    });

    document.querySelectorAll('.btn-width').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.btn-width').forEach(b => {
                b.classList.remove('btn-pnj-primary', 'active');
                b.classList.add('btn-subtle');
            });
            this.classList.remove('btn-subtle');
            this.classList.add('btn-pnj-primary', 'active');
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
            if (data) showSlotPreview(data);
        } else if (!hasTemplate && !uploadDataUrl) {
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
                l.classList.remove('border-success', 'bg-light');
            });
            document.getElementById('tplLabel_' + this.value).classList.add('border-success', 'bg-light');
        });
    });

    document.getElementById('tab-template-btn').addEventListener('shown.bs.tab', function () {
        if (hasTemplate) {
            const checked = document.querySelector('.template-radio:checked');
            if (checked) {
                selectedTemplateId = checked.value;
                inputMethod.value = 'template';
                showSlotPreview('<?= BASE_URL ?>/user-signatures/' + checked.value + '/preview');
            }
        } else {
            inputMethod.value = 'canvas';
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
            alert('Peringatan: Berkas harus berformat gambar PNG (.png) dengan latar belakang transparan.');
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

    // ── Konfirmasi final (validasi + submit) ──
    btnConfirmSign.addEventListener('click', function () {
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
