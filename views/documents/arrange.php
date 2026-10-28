<?php
$title = 'Atur Slot Tanda Tangan — ' . htmlspecialchars($doc['title']) . ' — SIGMA';
require_once __DIR__ . '/../layout/header.php';
?>

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

<!-- Alert Petunjuk Penataan Slot -->
<div class="alert alert-info py-2.5 px-3 mb-4 d-flex align-items-start gap-2.5" style="border-radius: var(--radius-md);">
    <i class="bi bi-info-circle-fill text-info mt-0.5 fs-5"></i>
    <div class="small">
        <strong>Panduan Penataan Koordinat:</strong> Tentukan letak kotak tanda tangan untuk setiap penandatangan. 
        Anda dapat memilih penandatangan dari daftar di bawah, klik pada lembar bidang kanvas untuk memindahkan kotak secara visual, 
        atau menyesuaikan persentase koordinat (X%, Y%, Halaman) secara manual.
    </div>
</div>

<form id="arrangeForm" action="<?= BASE_URL ?>/documents/<?= htmlspecialchars($doc['id']) ?>/update" method="POST">
    <input type="hidden" name="action_type" id="actionTypeInput" value="save_slots">

    <div class="row g-4">
        <!-- Kolom Kiri: Visual Canvas & Embedded PDF Viewer -->
        <div class="col-lg-7">
            <div class="card-clean mb-4">
                <div class="card-clean-header py-2.5 px-3 d-flex justify-content-between align-items-center">
                    <span class="fw-semibold small text-dark d-flex align-items-center gap-1.5">
                        <i class="bi bi-aspect-ratio text-success"></i>
                        <span>Kanvas Penempatan Slot (Halaman Target)</span>
                    </span>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-light text-dark border">
                            <i class="bi bi-file-earmark-pdf me-1 text-danger"></i> Rasio Standar Dokumen A4
                        </span>
                    </div>
                </div>

                <div class="card-clean-body p-3 bg-light text-center">
                    <!-- Kanvas Representasi Lembar PDF (Rasio A4 = 1 : 1.414) -->
                    <div class="d-inline-block text-start" style="max-width: 100%;">
                        <div id="pdfCanvasContainer" 
                             class="position-relative bg-white shadow-sm border rounded mx-auto overflow-hidden" 
                             style="width: 480px; height: 678px; max-width: 100%; touch-action: none; cursor: crosshair;">
                            
                            <!-- Watermark latar lembar -->
                            <div class="position-absolute w-100 h-100 d-flex flex-column align-items-center justify-content-center pointer-events-none opacity-25" style="user-select: none;">
                                <i class="bi bi-file-earmark-text display-1 text-muted"></i>
                                <span class="fw-semibold text-muted small mt-2">Area Halaman PDF</span>
                                <span class="text-muted" style="font-size: 0.75rem;">Klik di area ini untuk menempatkan slot aktif</span>
                            </div>

                            <!-- Garis kisi bantu (Gridlines) -->
                            <div class="position-absolute w-100 h-100" style="pointer-events: none; opacity: 0.15; background-size: 20% 20%; background-image: linear-gradient(to right, #64748b 1px, transparent 1px), linear-gradient(to bottom, #64748b 1px, transparent 1px);"></div>

                            <!-- Kotak-kotak Slot Tanda Tangan untuk Setiap Signer -->
                            <?php 
                                $colors = [
                                    ['border' => '#059669', 'bg' => 'rgba(16, 185, 129, 0.15)', 'badge' => 'bg-success'],
                                    ['border' => '#2563eb', 'bg' => 'rgba(37, 99, 235, 0.15)', 'badge' => 'bg-primary'],
                                    ['border' => '#d97706', 'bg' => 'rgba(217, 119, 6, 0.15)', 'badge' => 'bg-warning text-dark'],
                                    ['border' => '#7c3aed', 'bg' => 'rgba(124, 58, 237, 0.15)', 'badge' => 'bg-purple text-white'],
                                ];
                            ?>
                            <?php foreach ($signers as $idx => $s): ?>
                                <?php 
                                    $c = $colors[$idx % count($colors)];
                                    $x = (float) $s['x_percent'];
                                    $y = (float) $s['y_percent'];
                                    $w = (float) $s['width_percent'] > 0 ? (float) $s['width_percent'] : 25.0;
                                    $h = (float) $s['height_percent'] > 0 ? (float) $s['height_percent'] : 10.0;
                                    // Posisi awal jika belum diatur: bariskan di bagian bawah
                                    if ($x == 0 && $y == 0) {
                                        $x = 10.0 + (($idx % 2) * 45.0);
                                        $y = 75.0 + (floor($idx / 2) * 12.0);
                                        if ($y > 88.0) $y = 85.0;
                                    }
                                ?>
                                <div id="slotBox_<?= htmlspecialchars($s['id']) ?>" 
                                     class="slot-draggable position-absolute rounded p-1 border text-center d-flex flex-column align-items-center justify-content-center <?= $idx === 0 ? 'active-slot' : '' ?>"
                                     data-signer-id="<?= htmlspecialchars($s['id']) ?>"
                                     style="left: <?= $x ?>%; top: <?= $y ?>%; width: <?= $w ?>%; height: <?= $h ?>%; 
                                            background-color: <?= $c['bg'] ?>; border-color: <?= $c['border'] ?> !important; border-width: 2px !important; 
                                            cursor: move; user-select: none; z-index: <?= 10 + $idx ?>; transition: box-shadow 0.15s ease;">
                                    
                                    <span class="badge <?= $c['badge'] ?> px-1.5 py-0.5 mb-0.5 text-truncate" style="font-size: 0.65rem; max-width: 95%;">
                                        Step <?= $s['step_order'] ?> &bull; <?= htmlspecialchars($s['role_title'] ?: $s['signer_name']) ?>
                                    </span>
                                    <div class="text-truncate text-dark fw-bold" style="font-size: 0.7rem; max-width: 95%;">
                                        <i class="bi bi-pen-fill me-0.5 text-muted"></i><?= htmlspecialchars($s['signer_name']) ?>
                                    </div>
                                    <div class="text-muted" style="font-size: 0.6rem;">
                                        [Slot TTD + QR Code]
                                    </div>
                                </div>
                            <?php endforeach; ?>

                        </div>
                    </div>

                    <div class="text-muted mt-2" style="font-size: 0.75rem;">
                        <i class="bi bi-hand-index-thumb me-1"></i>
                        Klik dan seret (drag) kotak slot pada kanvas di atas untuk menyesuaikan penempatan tanda tangan.
                    </div>
                </div>
            </div>

            <!-- Pratinjau PDF Tersemat (Accordion/Collapsible) -->
            <div class="card-clean mb-4">
                <div class="card-clean-header py-2 px-3">
                    <span class="fw-semibold small text-dark">
                        <i class="bi bi-file-earmark-pdf me-1 text-danger"></i> Pratinjau Halaman Berkas Fisik Asli
                    </span>
                    <button class="btn btn-sm btn-link text-decoration-none text-muted p-0" type="button" data-bs-toggle="collapse" data-bs-target="#pdfPreviewCollapse">
                        Tampilkan/Sembunyikan Viewer
                    </button>
                </div>
                <div class="collapse show" id="pdfPreviewCollapse">
                    <div class="card-clean-body p-0">
                        <iframe src="<?= BASE_URL ?>/documents/<?= htmlspecialchars($doc['id']) ?>/preview" 
                                style="width: 100%; height: 420px; border: none;" 
                                title="Pratinjau PDF"></iframe>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Pengaturan & Formulir Koordinat Penandatangan -->
        <div class="col-lg-5">
            <div class="card-clean mb-4 sticky-lg-top" style="top: 85px; z-index: 1000;">
                <div class="card-clean-header py-3 px-3">
                    <div>
                        <h6 class="fw-bold text-dark mb-0">Daftar Slot Penandatangan</h6>
                        <small class="text-muted">Koordinat relatif terhadap lebar & tinggi halaman (%)</small>
                    </div>
                </div>

                <div class="card-clean-body p-3">
                    <!-- Tab / List Signer -->
                    <div class="d-flex flex-column gap-3 mb-4">
                        <?php foreach ($signers as $idx => $s): ?>
                            <?php 
                                $c = $colors[$idx % count($colors)];
                                $x = (float) $s['x_percent'];
                                $y = (float) $s['y_percent'];
                                $w = (float) $s['width_percent'] > 0 ? (float) $s['width_percent'] : 25.0;
                                $h = (float) $s['height_percent'] > 0 ? (float) $s['height_percent'] : 10.0;
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
                                    <span class="badge bg-light text-muted border" style="font-size: 0.7rem;">
                                        <?= htmlspecialchars($s['signer_identifier'] ?: 'Civitas') ?>
                                    </span>
                                </div>

                                <div class="text-muted small mb-2.5">
                                    <i class="bi bi-briefcase me-1"></i> Peran: <strong><?= htmlspecialchars($s['role_title'] ?: 'Penandatangan') ?></strong>
                                </div>

                                <!-- Input Parameter Koordinat -->
                                <div class="row g-2">
                                    <div class="col-4">
                                        <label class="form-label mb-0" style="font-size: 0.75rem;">Halaman</label>
                                        <input type="number" 
                                               name="slots[<?= htmlspecialchars($s['id']) ?>][page_number]" 
                                               id="page_<?= htmlspecialchars($s['id']) ?>"
                                               class="form-control form-control-sm coord-input" 
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
    box-shadow: 0 0 0 3px rgba(10, 92, 71, 0.4) !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('pdfCanvasContainer');
    const slotBoxes = document.querySelectorAll('.slot-draggable');
    const signerCards = document.querySelectorAll('.signer-card-item');
    const arrangeForm = document.getElementById('arrangeForm');
    const actionTypeInput = document.getElementById('actionTypeInput');
    const btnSubmitToAdmin = document.getElementById('btnSubmitToAdmin');

    let activeSignerId = slotBoxes.length > 0 ? slotBoxes[0].getAttribute('data-signer-id') : null;

    // Fungsi memilih signer aktif
    function selectSigner(signerId) {
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
    }

    // Klik pada card signer di panel kanan
    signerCards.forEach(card => {
        card.addEventListener('click', function () {
            selectSigner(this.getAttribute('data-signer-id'));
        });
    });

    // Klik pada slot box di kanvas
    slotBoxes.forEach(box => {
        box.addEventListener('mousedown', function (e) {
            selectSigner(this.getAttribute('data-signer-id'));
        });
    });

    // Update posisi box dari input manual
    document.querySelectorAll('.coord-input').forEach(input => {
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
        });
    });

    // Drag and Drop interaktif pada Kanvas
    let isDragging = false;
    let dragBox = null;
    let dragSid = null;
    let startX, startY, initialLeft, initialTop;

    slotBoxes.forEach(box => {
        box.addEventListener('mousedown', function (e) {
            isDragging = true;
            dragBox = this;
            dragSid = this.getAttribute('data-signer-id');
            selectSigner(dragSid);

            const rect = container.getBoundingClientRect();
            startX = e.clientX;
            startY = e.clientY;

            initialLeft = parseFloat(dragBox.style.left) || 0;
            initialTop = parseFloat(dragBox.style.top) || 0;
            e.preventDefault();
        });
    });

    document.addEventListener('mousemove', function (e) {
        if (!isDragging || !dragBox) return;

        const rect = container.getBoundingClientRect();
        const deltaX = (e.clientX - startX) / rect.width * 100;
        const deltaY = (e.clientY - startY) / rect.height * 100;

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
    });

    document.addEventListener('mouseup', function () {
        isDragging = false;
        dragBox = null;
        dragSid = null;
    });

    // Klik langsung pada container kanvas untuk memindahkan active slot ke lokasi klik
    container.addEventListener('click', function (e) {
        if (e.target.closest('.slot-draggable')) return;
        if (!activeSignerId) return;

        const rect = container.getBoundingClientRect();
        const clickX = (e.clientX - rect.left) / rect.width * 100;
        const clickY = (e.clientY - rect.top) / rect.height * 100;

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
});
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>

