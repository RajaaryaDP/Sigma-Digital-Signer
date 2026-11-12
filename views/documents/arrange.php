<?php
$title = 'Atur Slot Tanda Tangan — ' . htmlspecialchars($doc['title']) . ' — SIGMA';
require_once __DIR__ . '/../layout/header.php';
?>

<!-- Pustaka Mozilla PDF.js untuk rendering lembar dokumen langsung di kanvas interaktif -->
<script src="<?= BASE_URL ?>/assets/js/pdf.min.js"></script>

<!-- Header Breadcrumb & Status -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
    <div>
        <div class="mb-1">
            <a href="<?= BASE_URL ?>/documents/<?= htmlspecialchars($doc['id']) ?>" class="text-decoration-none text-muted small d-inline-flex align-items-center gap-1.5">
                <i class="bi bi-arrow-left"></i>
                <span>Kembali ke Detail Dokumen</span>
            </a>
        </div>
        <h4 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
            <span>Atur Slot Tanda Tangan Berjenjang</span>
            <span class="badge bg-secondary fs-6 fw-normal">Draft</span>
        </h4>
        <div class="text-muted small">
            Dokumen: <strong><?= htmlspecialchars($doc['title']) ?></strong> &bull; <?= htmlspecialchars($doc['file_name_original']) ?>
        </div>
    </div>

    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>/documents/<?= htmlspecialchars($doc['id']) ?>/preview" target="_blank" class="btn btn-subtle btn-sm">
            <i class="bi bi-box-arrow-up-right me-1"></i> Buka PDF di Tab Baru
        </a>
    </div>
</div>

<!-- Alert Petunjuk Penataan Slot Terpadu -->
<div class="alert alert-info py-2.5 px-3 mb-4 d-flex align-items-start gap-2.5" style="border-radius: var(--radius-md);">
    <i class="bi bi-info-circle-fill text-info mt-0.5 fs-5"></i>
    <div class="small">
        <strong>Penataan Slot Langsung di Atas Lembar Dokumen:</strong> Anda dapat melihat halaman berkas PDF asli secara langsung di kanvas sebelah kiri.
        Klik dan seret (drag) kotak slot tanda tangan tepat ke area tanda tangan yang diinginkan. Anda juga dapat berpindah halaman jika dokumen memiliki lebih dari satu halaman.
    </div>
</div>

<form id="arrangeForm" action="<?= BASE_URL ?>/documents/<?= htmlspecialchars($doc['id']) ?>/update" method="POST">
    <input type="hidden" name="action_type" id="actionTypeInput" value="save_slots">

    <div class="row g-4">
        <!-- Kolom Kiri: Unified Interactive PDF Canvas -->
        <div class="col-lg-7">
            <div class="card-clean mb-4">
                <div class="card-clean-header py-2.5 px-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <span class="fw-semibold small text-dark d-flex align-items-center gap-1.5">
                        <i class="bi bi-file-earmark-check text-success"></i>
                        <span>Lembar Kerja Penempatan Slot Tanda Tangan</span>
                    </span>

                    <!-- Toolbar Navigasi Halaman PDF -->
                    <div class="d-flex align-items-center gap-2" id="pdfNavToolbar">
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-secondary" id="btnPrevPage" title="Halaman Sebelumnya" disabled>
                                <i class="bi bi-chevron-left"></i>
                            </button>
                            <span class="btn btn-outline-secondary disabled text-dark fw-medium px-2.5" id="pageIndicator" style="font-size: 0.75rem;">
                                Hal <span id="pageNumDisplay">1</span> dari <span id="pageTotalDisplay">1</span>
                            </span>
                            <button type="button" class="btn btn-outline-secondary" id="btnNextPage" title="Halaman Berikutnya" disabled>
                                <i class="bi bi-chevron-right"></i>
                            </button>
                        </div>
                        <button type="button" class="btn btn-outline-success btn-sm d-none" id="btnPlaceOnCurrentPage" title="Pindahkan slot penandatangan aktif ke halaman yang sedang dibuka">
                            <i class="bi bi-geo-alt me-1"></i> Tempatkan Slot di Hal <span id="toolbarCurrentPageNum">1</span>
                        </button>
                    </div>
                </div>

                <div class="card-clean-body p-3 bg-light text-center position-relative" style="min-height: 480px;">
                    <!-- Loading Spinner saat PDF.js memuat lembar berkas -->
                    <div id="pdfLoadingSpinner" class="py-5 text-center">
                        <div class="spinner-border text-success" role="status" style="width: 2.5rem; height: 2.5rem;">
                            <span class="visually-hidden">Memuat PDF...</span>
                        </div>
                        <div class="text-muted small mt-2 fw-medium">Memuat halaman dokumen PDF asli...</div>
                    </div>

                    <!-- Alert Fallback jika PDF.js gagal atau offline -->
                    <div id="pdfFallbackAlert" class="alert alert-warning py-2 px-3 small d-none text-start mb-3" style="border-radius: var(--radius-md);">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                        Pratinjau PDF langsung tidak dapat dimuat melalui peramban. Anda tetap dapat mengatur koordinat pada kanvas representasi rasio A4 di bawah ini.
                    </div>

                    <!-- Alert Peringatan Perbedaan Halaman Slot Aktif vs Halaman Tampil -->
                    <div id="slotPageDiffAlert" class="alert alert-warning py-1.5 px-2.5 small d-none align-items-center justify-content-between mb-2 text-start flex-wrap gap-2" style="border-radius: var(--radius-sm);">
                        <span>
                            <i class="bi bi-info-circle-fill text-warning me-1"></i>
                            Slot <strong id="diffSignerName">Penandatangan</strong> saat ini berada di <strong>Halaman <span id="diffSignerPageNum">1</span></strong>.
                        </span>
                        <button type="button" class="btn btn-warning btn-sm py-0.5 px-2 text-dark fw-semibold" id="btnDiffMoveSlot">
                            <i class="bi bi-geo-alt-fill me-1"></i> Pindahkan Slot ke Halaman <span id="diffCurrentPageNum">1</span>
                        </button>
                    </div>

                    <!-- Wadah Kanvas Interaktif Terpadu -->
                    <div id="pdfStageContainer" class="d-inline-block text-start position-relative d-none" style="max-width: 100%;">
                        <div id="pdfCanvasContainer" 
                             class="position-relative bg-white shadow-sm border rounded mx-auto overflow-hidden" 
                             style="touch-action: none; cursor: crosshair;">
                            
                            <!-- Canvas Elemen PDF Asli (dirender oleh PDF.js) -->
                            <canvas id="pdfRenderCanvas" style="display: block; width: 100%; height: auto;"></canvas>

                            <!-- Lapisan Gridlines Bantuan Tipis -->
                            <div class="position-absolute w-100 h-100 top-0 start-0 pointer-events-none" 
                                 style="pointer-events: none; opacity: 0.08; background-size: 20% 20%; background-image: linear-gradient(to right, #000 1px, transparent 1px), linear-gradient(to bottom, #000 1px, transparent 1px);"></div>

                            <!-- Overlay Lapisan Kotak Slot Tanda Tangan (Berada persis di atas PDF) -->
                            <div id="slotOverlayContainer" class="position-absolute top-0 start-0 w-100 h-100" style="pointer-events: none;">
                                <?php 
                                    $colors = [
                                        ['border' => '#059669', 'bg' => 'rgba(16, 185, 129, 0.22)', 'badge' => 'bg-success'],
                                        ['border' => '#2563eb', 'bg' => 'rgba(37, 99, 235, 0.22)', 'badge' => 'bg-primary'],
                                        ['border' => '#d97706', 'bg' => 'rgba(217, 119, 6, 0.22)', 'badge' => 'bg-warning text-dark'],
                                        ['border' => '#7c3aed', 'bg' => 'rgba(124, 58, 237, 0.22)', 'badge' => 'bg-purple text-white'],
                                    ];
                                ?>
                                <?php foreach ($signers as $idx => $s): ?>
                                    <?php 
                                        $c = $colors[$idx % count($colors)];
                                        $x = (float) $s['x_percent'];
                                        $y = (float) $s['y_percent'];
                                        $w = (float) $s['width_percent'] > 0 ? (float) $s['width_percent'] : 25.0;
                                        $h = (float) $s['height_percent'] > 0 ? (float) $s['height_percent'] : 10.0;
                                        $p = (int) $s['page_number'] > 0 ? (int) $s['page_number'] : 1;
                                        $isConfigured = ($x != 0 || $y != 0);
                                        // Posisi awal jika belum diatur: bariskan di bagian bawah
                                        if ($x == 0 && $y == 0) {
                                            $x = 10.0 + (($idx % 2) * 45.0);
                                            $y = 75.0 + (floor($idx / 2) * 12.0);
                                            if ($y > 88.0) $y = 85.0;
                                        }
                                        $sIdLabel = (($s['signer_type'] ?? '') === 'mahasiswa') ? 'NIM' : 'NIP';
                                        $sIdentText = !empty($s['signer_identifier'])
                                            ? ($sIdLabel . ': ' . $s['signer_identifier'])
                                            : ($s['role_title'] ?: ($s['signer_position'] ?? ''));
                                    ?>
                                    <div id="slotBox_<?= htmlspecialchars($s['id']) ?>" 
                                         class="slot-draggable position-absolute rounded border text-center overflow-hidden <?= ($p !== 1) ? 'd-none' : '' ?> <?= $idx === 0 ? 'active-slot' : '' ?>"
                                         data-signer-id="<?= htmlspecialchars($s['id']) ?>"
                                         data-signer-page="<?= $p ?>"
                                         data-slot-configured="<?= $isConfigured ? '1' : '0' ?>"
                                         style="left: <?= $x ?>%; top: <?= $y ?>%; width: <?= $w ?>%; height: <?= $h ?>%; 
                                                background-color: <?= $c['bg'] ?>; border-color: <?= $c['border'] ?> !important; border-width: 2px !important; 
                                                cursor: move; user-select: none; pointer-events: auto; z-index: <?= 10 + $idx ?>; transition: box-shadow 0.15s ease;">
                                        <span class="badge <?= $c['badge'] ?> position-absolute top-0 start-0 m-0.5 px-1 py-0 text-truncate" style="font-size: 0.54rem; max-width: 92%; z-index: 5;">
                                            Step <?= (int) $s['step_order'] ?> &bull; <?= htmlspecialchars($s['role_title'] ?: $s['signer_name']) ?>
                                        </span>
                                        <div class="w-100 h-100 position-relative d-flex align-items-stretch" style="pointer-events: none;">
                                            <div class="d-flex flex-column justify-content-end" style="width: 58%; padding: 13px 3px 3px 4px; min-width: 0;">
                                                <div class="flex-grow-1 d-flex align-items-center justify-content-center text-muted" style="font-size: 0.54rem; min-height: 0;">
                                                    <span><i class="bi bi-pen me-0.5"></i>[Area TTD]</span>
                                                </div>
                                                <div class="text-center" style="line-height: 1.15; min-width: 0;">
                                                    <div class="text-dark fw-bold text-truncate" style="font-size: 0.58rem;">
                                                        <?= htmlspecialchars($s['signer_name']) ?>
                                                    </div>
                                                    <div style="border-top: 1px solid #0f172a; margin: 1px 0;"></div>
                                                    <?php if (!empty($sIdentText)): ?>
                                                        <div class="text-muted text-truncate" style="font-size: 0.48rem;">
                                                            <?= htmlspecialchars($sIdentText) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-center" style="width: 42%; padding: 3px;">
                                                <div class="d-flex flex-column align-items-center justify-content-center bg-white bg-opacity-75 rounded border text-dark"
                                                     style="aspect-ratio: 1 / 1; height: 76%; max-width: 100%; font-size: 0.9rem;">
                                                    <i class="bi bi-qr-code"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Petunjuk Bawah Kanvas -->
                    <div class="text-muted mt-2 d-flex justify-content-between align-items-center flex-wrap gap-2 px-1" style="font-size: 0.75rem;">
                        <span>
                            <i class="bi bi-hand-index-thumb me-1"></i>
                            Klik & seret kotak slot langsung di atas lembar dokumen, atau klik di area dokumen untuk memindahkan slot aktif.
                        </span>
                        <span class="badge bg-light text-muted border">
                            <i class="bi bi-layers me-1"></i> Menampilkan Halaman: <strong id="activePageBadge" class="text-dark">1</strong>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Pengaturan & Formulir Koordinat Penandatangan -->
        <div class="col-lg-5">
            <div class="card-clean mb-4 sticky-lg-top" style="top: 85px; z-index: 1000;">
                <div class="card-clean-header py-3 px-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-bold text-dark mb-0">Daftar Slot Penandatangan</h6>
                        <small class="text-muted">Koordinat relatif terhadap lembar halaman (%)</small>
                    </div>
                    <span class="badge bg-light text-dark border">
                        <?= count($signers) ?> Penandatangan
                    </span>
                </div>

                <div class="card-clean-body p-3">
                    <!-- List Kartu Signer -->
                    <div class="d-flex flex-column gap-3 mb-4" id="signerCardsList">
                        <?php foreach ($signers as $idx => $s): ?>
                            <?php 
                                $c = $colors[$idx % count($colors)];
                                $x = (float) $s['x_percent'];
                                $y = (float) $s['y_percent'];
                                $w = (float) $s['width_percent'] > 0 ? (float) $s['width_percent'] : 25.0;
                                $h = (float) $s['height_percent'] > 0 ? (float) $s['height_percent'] : 10.0;
                                $isConfigured = ($x != 0 || $y != 0);
                                if ($x == 0 && $y == 0) {
                                    $x = 10.0 + (($idx % 2) * 45.0);
                                    $y = 75.0 + (floor($idx / 2) * 12.0);
                                    if ($y > 88.0) $y = 85.0;
                                }
                                $pageNo = (int) $s['page_number'] > 0 ? (int) $s['page_number'] : 1;
                            ?>
                            <div class="border rounded-3 p-3 signer-card-item <?= $idx === 0 ? 'border-primary bg-light' : '' ?>" 
                                 id="cardSigner_<?= htmlspecialchars($s['id']) ?>"
                                 data-signer-id="<?= htmlspecialchars($s['id']) ?>"
                                 data-signer-page="<?= $pageNo ?>"
                                 data-slot-configured="<?= $isConfigured ? '1' : '0' ?>"
                                 style="cursor: pointer; transition: all 0.15s ease;">
                                
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge <?= $c['badge'] ?>">
                                            Step <?= $s['step_order'] ?>
                                        </span>
                                        <span class="fw-bold text-dark small">
                                            <?= htmlspecialchars($s['signer_name']) ?>
                                        </span>
                                    </div>
                                    <span class="badge bg-light text-muted border page-status-badge" id="pageBadge_<?= htmlspecialchars($s['id']) ?>" style="font-size: 0.7rem;">
                                        Hal <?= $pageNo ?>
                                    </span>
                                </div>

                                <div class="text-muted small mb-2.5">
                                    <i class="bi bi-briefcase me-1"></i> Peran: <strong><?= htmlspecialchars($s['role_title'] ?: 'Penandatangan') ?></strong>
                                    <?php if (!empty($s['signer_identifier'])): ?>
                                        &bull; <span class="text-secondary"><?= htmlspecialchars($s['signer_identifier']) ?></span>
                                    <?php endif; ?>
                                </div>

                                <!-- Penanda slot sudah benar-benar ditempatkan pengguna -->
                                <input type="hidden"
                                       name="slots[<?= htmlspecialchars($s['id']) ?>][slot_configured]"
                                       id="configured_<?= htmlspecialchars($s['id']) ?>"
                                       value="<?= $isConfigured ? '1' : '0' ?>">

                                <!-- Input Parameter Koordinat -->
                                <div class="row g-2">
                                    <div class="col-4">
                                        <label class="form-label mb-0" style="font-size: 0.75rem;">Halaman</label>
                                        <input type="number" 
                                               name="slots[<?= htmlspecialchars($s['id']) ?>][page_number]" 
                                               id="page_<?= htmlspecialchars($s['id']) ?>"
                                               class="form-control form-control-sm coord-input page-input" 
                                               data-signer-id="<?= htmlspecialchars($s['id']) ?>"
                                               min="1" max="100" value="<?= $pageNo ?>" required>
                                    </div>
                                    <div class="col-4">
                                        <label class="form-label mb-0" style="font-size: 0.75rem;">X (%)</label>
                                        <input type="number" step="0.1" 
                                               name="slots[<?= htmlspecialchars($s['id']) ?>][x_percent]" 
                                               id="x_<?= htmlspecialchars($s['id']) ?>"
                                               class="form-control form-control-sm coord-input" 
                                               min="0" max="100" value="<?= number_format($x, 1, '.', '') ?>" required>
                                    </div>
                                    <div class="col-4">
                                        <label class="form-label mb-0" style="font-size: 0.75rem;">Y (%)</label>
                                        <input type="number" step="0.1" 
                                               name="slots[<?= htmlspecialchars($s['id']) ?>][y_percent]" 
                                               id="y_<?= htmlspecialchars($s['id']) ?>"
                                               class="form-control form-control-sm coord-input" 
                                               min="0" max="100" value="<?= number_format($y, 1, '.', '') ?>" required>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label mb-0" style="font-size: 0.75rem;">Lebar Slot (%)</label>
                                        <input type="number" step="0.1" 
                                               name="slots[<?= htmlspecialchars($s['id']) ?>][width_percent]" 
                                               id="w_<?= htmlspecialchars($s['id']) ?>"
                                               class="form-control form-control-sm coord-input" 
                                               min="5" max="50" value="<?= number_format($w, 1, '.', '') ?>" required>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label mb-0" style="font-size: 0.75rem;">Tinggi Slot (%)</label>
                                        <input type="number" step="0.1" 
                                               name="slots[<?= htmlspecialchars($s['id']) ?>][height_percent]" 
                                               id="h_<?= htmlspecialchars($s['id']) ?>"
                                               class="form-control form-control-sm coord-input" 
                                               min="3" max="30" value="<?= number_format($h, 1, '.', '') ?>" required>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Tombol Aksi Simpan & Submit -->
                    <?php $isSingle = ($doc['signature_type'] === 'single'); ?>
                    <div class="d-flex flex-column gap-2 pt-3 border-top">
                        <button type="submit" id="btnSaveOnly" class="btn btn-subtle w-100 py-2">
                            <i class="bi bi-save me-1"></i> Simpan Posisi Slot Saja
                        </button>
                        <button type="button" id="btnSubmitToAdmin" class="btn btn-pnj-primary w-100 py-2 fw-semibold">
                            <i class="bi <?= $isSingle ? 'bi-patch-check' : 'bi-send-check' ?> me-1.5"></i>
                            <?= $isSingle ? 'Simpan & Selesaikan Dokumen Mandiri' : 'Simpan & Ajukan ke Administrator' ?>
                        </button>
                    </div>

                    <div class="text-center mt-2.5">
                        <small class="text-muted" style="font-size: 0.75rem;">
                            <?= $isSingle 
                                ? 'Setelah diselesaikan, dokumen mandiri akan langsung berstatus final.' 
                                : 'Setelah diajukan ke Administrator, posisi slot tidak dapat diubah lagi.' ?>
                        </small>
                    </div>

                </div>
            </div>
        </div>
    </div>
</form>

<style>
.active-slot {
    box-shadow: 0 0 0 3px rgba(10, 92, 71, 0.5), 0 4px 12px rgba(0, 0, 0, 0.15) !important;
}
.slot-draggable {
    box-sizing: border-box;
    backdrop-filter: blur(1px);
}
.slot-draggable:hover {
    filter: brightness(0.96);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('pdfCanvasContainer');
    const stageContainer = document.getElementById('pdfStageContainer');
    const loadingSpinner = document.getElementById('pdfLoadingSpinner');
    const fallbackAlert = document.getElementById('pdfFallbackAlert');
    const renderCanvas = document.getElementById('pdfRenderCanvas');
    const slotBoxes = document.querySelectorAll('.slot-draggable');
    const signerCards = document.querySelectorAll('.signer-card-item');
    const arrangeForm = document.getElementById('arrangeForm');
    const actionTypeInput = document.getElementById('actionTypeInput');
    const btnSubmitToAdmin = document.getElementById('btnSubmitToAdmin');

    const btnPrevPage = document.getElementById('btnPrevPage');
    const btnNextPage = document.getElementById('btnNextPage');
    const pageNumDisplay = document.getElementById('pageNumDisplay');
    const pageTotalDisplay = document.getElementById('pageTotalDisplay');
    const activePageBadge = document.getElementById('activePageBadge');
    const btnPlaceOnCurrentPage = document.getElementById('btnPlaceOnCurrentPage');
    const toolbarCurrentPageNum = document.getElementById('toolbarCurrentPageNum');
    const slotPageDiffAlert = document.getElementById('slotPageDiffAlert');
    const diffSignerName = document.getElementById('diffSignerName');
    const diffSignerPageNum = document.getElementById('diffSignerPageNum');
    const diffCurrentPageNum = document.getElementById('diffCurrentPageNum');
    const btnDiffMoveSlot = document.getElementById('btnDiffMoveSlot');

    let activeSignerId = slotBoxes.length > 0 ? slotBoxes[0].getAttribute('data-signer-id') : null;

    // ─────────────────────────────────────────────────────────────
    // Integrasi PDF.js: Render Berkas PDF Asli Langsung di Kanvas
    // ─────────────────────────────────────────────────────────────
    const pdfUrl = <?= json_encode(BASE_URL . '/documents/' . $doc['id'] . '/preview') ?>;
    let pdfDoc = null;
    let currentPage = 1;
    let totalPages = 1;
    let isRendering = false;
    let pageRenderingQueue = null;

    if (window.pdfjsLib) {
        try {
            pdfjsLib.GlobalWorkerOptions.workerSrc = <?= json_encode(BASE_URL . '/assets/js/pdf.worker.min.js') ?>;
        } catch (e) {
            console.warn('Worker configuration notice:', e);
        }
        
        pdfjsLib.getDocument({ url: pdfUrl, withCredentials: true }).promise.then(function (loadedPdf) {
            pdfDoc = loadedPdf;
            totalPages = loadedPdf.numPages;
            pageTotalDisplay.textContent = totalPages;

            // Sesuaikan batas maksimal input halaman
            document.querySelectorAll('.page-input').forEach(input => {
                input.max = totalPages;
            });

            // Cari halaman dari signer aktif pertama kali
            const initialSignerPage = getSignerPage(activeSignerId);
            currentPage = Math.min(totalPages, Math.max(1, initialSignerPage));

            loadingSpinner.classList.add('d-none');
            stageContainer.classList.remove('d-none');

            renderPage(currentPage);
        }).catch(function (error) {
            console.warn('PDF.js gagal memuat berkas:', error);
            initFallbackMode();
        });
    } else {
        initFallbackMode();
    }

    // Fallback mode jika PDF.js offline/gagal
    function initFallbackMode() {
        if (loadingSpinner) loadingSpinner.classList.add('d-none');
        if (fallbackAlert) fallbackAlert.classList.remove('d-none');
        if (stageContainer) {
            stageContainer.classList.remove('d-none');
            container.style.width = '480px';
            container.style.height = '678px';
            if (renderCanvas) renderCanvas.style.display = 'none';
        }
        updateSlotVisibilityForPage(1);
    }

    // Fungsi Render Halaman dengan Penanganan Resolusi Tajam (HiDPI)
    function renderPage(num) {
        if (!pdfDoc) return;

        if (isRendering) {
            pageRenderingQueue = num;
            return;
        }
        isRendering = true;
        currentPage = num;

        pageNumDisplay.textContent = num;
        activePageBadge.textContent = num;
        btnPrevPage.disabled = (num <= 1);
        btnNextPage.disabled = (num >= totalPages);

        pdfDoc.getPage(num).then(function (page) {
            // Hitung lebar optimal agar sesuai kontainer kolom
            const parentWidth = stageContainer.parentElement.clientWidth || 560;
            const targetWidth = Math.min(620, Math.max(340, parentWidth - 32));
            const unscaledViewport = page.getViewport({ scale: 1.0 });
            const scale = targetWidth / unscaledViewport.width;
            const viewport = page.getViewport({ scale: scale });

            const ctx = renderCanvas.getContext('2d');
            const dpr = window.devicePixelRatio || 1;

            renderCanvas.width = Math.floor(viewport.width * dpr);
            renderCanvas.height = Math.floor(viewport.height * dpr);
            renderCanvas.style.width = Math.floor(viewport.width) + 'px';
            renderCanvas.style.height = Math.floor(viewport.height) + 'px';

            container.style.width = Math.floor(viewport.width) + 'px';
            container.style.height = Math.floor(viewport.height) + 'px';

            const renderContext = {
                canvasContext: ctx,
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

    // Tombol Navigasi Halaman
    btnPrevPage.addEventListener('click', function () {
        if (currentPage > 1) {
            renderPage(currentPage - 1);
        }
    });

    btnNextPage.addEventListener('click', function () {
        if (currentPage < totalPages) {
            renderPage(currentPage + 1);
        }
    });

    // Helper: Mendapatkan nomor halaman suatu signer dari input form
    function getSignerPage(sid) {
        const input = document.getElementById('page_' + sid);
        return input ? (parseInt(input.value, 10) || 1) : 1;
    }

    // Helper: Menyinkronkan halaman pada input, kartu, dan kotak slot
    function setSignerPage(sid, page) {
        const pageInput = document.getElementById('page_' + sid);
        if (pageInput) pageInput.value = page;

        const box = document.getElementById('slotBox_' + sid);
        if (box) box.setAttribute('data-signer-page', page);

        const card = document.getElementById('cardSigner_' + sid);
        if (card) card.setAttribute('data-signer-page', page);
    }

    // Helper: Status slot pernah ditempatkan pengguna (bukan sekadar posisi pratinjau)
    function isSignerSlotConfigured(sid) {
        const card = document.getElementById('cardSigner_' + sid);
        if (card && card.getAttribute('data-slot-configured') === '1') return true;

        const box = document.getElementById('slotBox_' + sid);
        if (box && box.getAttribute('data-slot-configured') === '1') return true;

        return false;
    }

    // Helper: Menandai slot sudah ditempatkan agar validasi backend tidak memakai posisi pratinjau
    function setSignerSlotConfigured(sid, configured) {
        const value = configured ? '1' : '0';

        const card = document.getElementById('cardSigner_' + sid);
        if (card) card.setAttribute('data-slot-configured', value);

        const box = document.getElementById('slotBox_' + sid);
        if (box) box.setAttribute('data-slot-configured', value);

        const hidden = document.getElementById('configured_' + sid);
        if (hidden) hidden.value = value;
    }

    // Pembaruan Alert & Tombol Penempatan Halaman Slot Aktif
    function updateActiveSlotPageAlert() {
        if (!activeSignerId) return;
        const signerPage = getSignerPage(activeSignerId);
        const card = document.getElementById('cardSigner_' + activeSignerId);
        const signerName = card ? (card.querySelector('.fw-bold.text-dark.small')?.textContent?.trim() || 'Penandatangan') : 'Penandatangan';

        if (signerPage !== currentPage) {
            if (slotPageDiffAlert) {
                slotPageDiffAlert.classList.remove('d-none');
                slotPageDiffAlert.classList.add('d-flex');
                if (diffSignerName) diffSignerName.textContent = signerName;
                if (diffSignerPageNum) diffSignerPageNum.textContent = signerPage;
                if (diffCurrentPageNum) diffCurrentPageNum.textContent = currentPage;
            }
            if (btnPlaceOnCurrentPage) {
                btnPlaceOnCurrentPage.classList.remove('d-none');
                if (toolbarCurrentPageNum) toolbarCurrentPageNum.textContent = currentPage;
            }
        } else {
            if (slotPageDiffAlert) {
                slotPageDiffAlert.classList.add('d-none');
                slotPageDiffAlert.classList.remove('d-flex');
            }
            if (btnPlaceOnCurrentPage) {
                btnPlaceOnCurrentPage.classList.add('d-none');
            }
        }
    }

    function moveActiveSlotToCurrentPage() {
        if (!activeSignerId) return;

        const box = document.getElementById('slotBox_' + activeSignerId);
        if (box && !isSignerSlotConfigured(activeSignerId)) {
            const xInput = document.getElementById('x_' + activeSignerId);
            const yInput = document.getElementById('y_' + activeSignerId);
            if (xInput) xInput.value = (parseFloat(box.style.left) || 0).toFixed(1);
            if (yInput) yInput.value = (parseFloat(box.style.top) || 0).toFixed(1);
            setSignerSlotConfigured(activeSignerId, true);
        }

        setSignerPage(activeSignerId, currentPage);
        updateSlotVisibilityForPage(currentPage);
        updateActiveSlotPageAlert();
    }

    if (btnPlaceOnCurrentPage) {
        btnPlaceOnCurrentPage.addEventListener('click', moveActiveSlotToCurrentPage);
    }
    if (btnDiffMoveSlot) {
        btnDiffMoveSlot.addEventListener('click', moveActiveSlotToCurrentPage);
    }

    // Filter Slot: Hanya tampilkan slot yang berada pada halaman yang sedang aktif dirender
    function updateSlotVisibilityForPage(page) {
        slotBoxes.forEach(box => {
            const sid = box.getAttribute('data-signer-id');
            const signerPage = getSignerPage(sid);
            box.setAttribute('data-signer-page', signerPage);

            if (signerPage === page) {
                box.classList.remove('d-none');
                box.style.display = '';
            } else {
                box.classList.add('d-none');
                box.style.display = 'none';
            }
        });

        // Perbarui badge indikator di daftar kartu penandatangan
        signerCards.forEach(card => {
            const sid = card.getAttribute('data-signer-id');
            const signerPage = getSignerPage(sid);
            const badge = document.getElementById('pageBadge_' + sid);
            if (badge) {
                if (signerPage === page) {
                    badge.className = 'badge bg-success-subtle text-success border border-success page-status-badge';
                    badge.innerHTML = '<i class="bi bi-eye me-0.5"></i> Hal ' + signerPage;
                } else {
                    badge.className = 'badge bg-light text-muted border page-status-badge';
                    badge.textContent = 'Hal ' + signerPage;
                }
            }
        });

        updateActiveSlotPageAlert();
    }

    // Fungsi memilih signer aktif
    function selectSigner(signerId, autoSwitchPage = true, adoptCurrentPage = true) {
        activeSignerId = signerId;

        slotBoxes.forEach(box => {
            if (box.getAttribute('data-signer-id') === signerId) {
                box.classList.add('active-slot');
                box.style.zIndex = '50';
            } else {
                box.classList.remove('active-slot');
                box.style.zIndex = '10';
            }
        });

        signerCards.forEach(card => {
            if (card.getAttribute('data-signer-id') === signerId) {
                card.classList.add('border-primary', 'bg-light');
            } else {
                card.classList.remove('border-primary', 'bg-light');
            }
        });

        // Signer yang belum pernah ditempatkan mengikuti halaman yang sedang dibuka.
        // Ini mencegah slot penandatangan kedua dan seterusnya tersimpan di halaman 1
        // hanya karena halaman default formulir masih bernilai 1 (BUG-28).
        let targetPage = getSignerPage(signerId);
        let adoptedCurrentPage = false;
        if (adoptCurrentPage && !isSignerSlotConfigured(signerId) && targetPage <= 1 && currentPage > 1) {
            targetPage = currentPage;
            setSignerPage(signerId, targetPage);
            adoptedCurrentPage = true;
        }

        if (adoptedCurrentPage) {
            updateSlotVisibilityForPage(currentPage);
        }

        // Jika signer sudah memiliki slot di halaman lain, tampilkan halaman tersebut (ADR-09).
        if (autoSwitchPage && pdfDoc && targetPage !== currentPage
            && targetPage >= 1 && targetPage <= totalPages) {
            renderPage(targetPage);
        } else {
            updateActiveSlotPageAlert();
        }
    }

    // Klik pada card signer di panel kanan
    signerCards.forEach(card => {
        card.addEventListener('click', function (e) {
            const sid = this.getAttribute('data-signer-id');

            if (e.target.closest('input')) {
                // Fokus ke input tetap memilih signer yang sedang diedit,
                // tetapi tidak boleh mengubah nomor halaman secara diam-diam.
                selectSigner(sid, false, false);
                return;
            }

            selectSigner(sid, true);
        });
    });

    // Klik pada slot box di kanvas
    slotBoxes.forEach(box => {
        box.addEventListener('mousedown', function () {
            selectSigner(this.getAttribute('data-signer-id'), false);
        });
        box.addEventListener('touchstart', function () {
            selectSigner(this.getAttribute('data-signer-id'), false);
        }, { passive: true });
    });

    // Sinkronisasi perubahan input nomor halaman
    document.querySelectorAll('.page-input').forEach(input => {
        input.addEventListener('change', function () {
            const sid = this.getAttribute('data-signer-id');
            let pVal = parseInt(this.value, 10) || 1;
            if (totalPages >= 1 && pVal > totalPages) {
                pVal = totalPages;
                this.value = pVal;
            }
            if (pVal < 1) {
                pVal = 1;
                this.value = 1;
            }
            const box = document.getElementById('slotBox_' + sid);
            if (box) box.setAttribute('data-signer-page', pVal);

            // Jika signer ini adalah signer aktif, buka halaman tersebut
            if (sid === activeSignerId) {
                renderPage(pVal);
            } else {
                updateSlotVisibilityForPage(currentPage);
                updateActiveSlotPageAlert();
            }
        });
    });

    // Update posisi box dari input koordinat manual (nomor halaman ditangani terpisah)
    document.querySelectorAll('.coord-input:not(.page-input)').forEach(input => {
        input.addEventListener('input', function () {
            const card = this.closest('.signer-card-item');
            const sid = card.getAttribute('data-signer-id');
            const box = document.getElementById('slotBox_' + sid);
            if (!box) return;

            const x = parseFloat(document.getElementById('x_' + sid).value) || 0;
            const y = parseFloat(document.getElementById('y_' + sid).value) || 0;
            const w = parseFloat(document.getElementById('w_' + sid).value) || 20;
            const h = parseFloat(document.getElementById('h_' + sid).value) || 10;

            box.style.left = Math.max(0, Math.min(100 - w, x)) + '%';
            box.style.top = Math.max(0, Math.min(100 - h, y)) + '%';
            box.style.width = Math.max(5, Math.min(50, w)) + '%';
            box.style.height = Math.max(3, Math.min(30, h)) + '%';

            setSignerSlotConfigured(sid, x !== 0 || y !== 0);
        });
    });

    // ─────────────────────────────────────────────────────────────
    // Drag and Drop Interaktif Langsung di Atas Lembar Dokumen PDF
    // ─────────────────────────────────────────────────────────────
    let isDragging = false;
    let dragBox = null;
    let dragSid = null;
    let dragMoved = false;
    let startX, startY, initialLeft, initialTop;

    function startDrag(e, targetBox) {
        isDragging = true;
        dragBox = targetBox;
        dragSid = targetBox.getAttribute('data-signer-id');
        selectSigner(dragSid, false);

        // Kotak yang terlihat di kanvas selalu berada di halaman yang sedang dibuka
        setSignerPage(dragSid, currentPage);

        const clientX = e.touches ? e.touches[0].clientX : e.clientX;
        const clientY = e.touches ? e.touches[0].clientY : e.clientY;

        startX = clientX;
        startY = clientY;
        dragMoved = false;

        initialLeft = parseFloat(dragBox.style.left) || 0;
        initialTop = parseFloat(dragBox.style.top) || 0;
    }

    slotBoxes.forEach(box => {
        box.addEventListener('mousedown', function (e) {
            startDrag(e, this);
            e.preventDefault();
        });
        box.addEventListener('touchstart', function (e) {
            startDrag(e, this);
        }, { passive: true });
    });

    function moveDrag(clientX, clientY) {
        if (!isDragging || !dragBox) return;

        const rect = container.getBoundingClientRect();
        if (rect.width === 0 || rect.height === 0) return;

        const deltaX = ((clientX - startX) / rect.width) * 100;
        const deltaY = ((clientY - startY) / rect.height) * 100;

        if (Math.abs(clientX - startX) + Math.abs(clientY - startY) > 2) {
            dragMoved = true;
        }

        const w = parseFloat(dragBox.style.width) || 20;
        const h = parseFloat(dragBox.style.height) || 10;

        let newX = initialLeft + deltaX;
        let newY = initialTop + deltaY;

        newX = Math.max(0, Math.min(100 - w, newX));
        newY = Math.max(0, Math.min(100 - h, newY));

        dragBox.style.left = newX.toFixed(1) + '%';
        dragBox.style.top = newY.toFixed(1) + '%';

        // Update nilai pada form input
        const xInput = document.getElementById('x_' + dragSid);
        const yInput = document.getElementById('y_' + dragSid);
        if (xInput) xInput.value = newX.toFixed(1);
        if (yInput) yInput.value = newY.toFixed(1);
        if (dragMoved && dragSid) setSignerSlotConfigured(dragSid, true);
    }

    document.addEventListener('mousemove', function (e) {
        if (isDragging) {
            moveDrag(e.clientX, e.clientY);
        }
    });

    document.addEventListener('touchmove', function (e) {
        if (isDragging && e.touches.length > 0) {
            moveDrag(e.touches[0].clientX, e.touches[0].clientY);
        }
    }, { passive: true });

    function stopDrag() {
        isDragging = false;
        dragBox = null;
        dragSid = null;
        dragMoved = false;
    }

    document.addEventListener('mouseup', stopDrag);
    document.addEventListener('touchend', stopDrag);

    // Klik langsung pada container kanvas untuk memindahkan active slot ke titik kursor
    container.addEventListener('click', function (e) {
        if (e.target.closest('.slot-draggable')) return;
        if (!activeSignerId) return;

        const rect = container.getBoundingClientRect();
        const clickX = ((e.clientX - rect.left) / rect.width) * 100;
        const clickY = ((e.clientY - rect.top) / rect.height) * 100;

        const box = document.getElementById('slotBox_' + activeSignerId);
        if (!box) return;

        const w = parseFloat(box.style.width) || 20;
        const h = parseFloat(box.style.height) || 10;

        let newX = clickX - (w / 2);
        let newY = clickY - (h / 2);

        newX = Math.max(0, Math.min(100 - w, newX));
        newY = Math.max(0, Math.min(100 - h, newY));

        box.style.left = newX.toFixed(1) + '%';
        box.style.top = newY.toFixed(1) + '%';

        const xInput = document.getElementById('x_' + activeSignerId);
        const yInput = document.getElementById('y_' + activeSignerId);

        if (xInput) xInput.value = newX.toFixed(1);
        if (yInput) yInput.value = newY.toFixed(1);

        setSignerSlotConfigured(activeSignerId, true);
        setSignerPage(activeSignerId, currentPage);
        updateSlotVisibilityForPage(currentPage);
        updateActiveSlotPageAlert();
    });

    // Handle Submit to Admin / Finalize Single
    btnSubmitToAdmin.addEventListener('click', function () {
        const isSingle = <?= json_encode($doc['signature_type'] === 'single') ?>;
        const confirmMsg = isSingle 
            ? 'Apakah Anda yakin posisi tanda tangan sudah sesuai? Dokumen mandiri akan difinalisasi.'
            : 'Apakah Anda yakin seluruh slot tanda tangan sudah sesuai? Dokumen akan diajukan ke Administrator untuk peninjauan resmi.';
        if (confirm(confirmMsg)) {
            actionTypeInput.value = 'submit';
            arrangeForm.submit();
        }
    });
    // Penyesuaian responsif saat orientasi layar atau ukuran peramban berubah
    let resizeTimer = null;
    window.addEventListener('resize', function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function () {
            if (pdfDoc && !isRendering) {
                renderPage(currentPage);
            }
        }, 200);
    });
});
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
