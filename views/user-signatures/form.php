<?php
$title = 'Buat Template Tanda Tangan — SIGMA';
require_once __DIR__ . '/../layout/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-9 col-xl-8">
        <!-- Back Link -->
        <div class="mb-3">
            <a href="<?= BASE_URL ?>/user-signatures" class="text-decoration-none text-muted small d-inline-flex align-items-center gap-1.5">
                <i class="bi bi-arrow-left"></i>
                <span>Kembali ke Template Tanda Tangan</span>
            </a>
        </div>

        <!-- Form Card -->
        <div class="card-clean mb-4 shadow-sm">
            <div class="card-clean-header py-3 px-4">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle d-flex align-items-center justify-content-center" 
                         style="width: 34px; height: 34px; background: var(--pnj-green-50); color: var(--pnj-green-800); font-size: 1.1rem;">
                        <i class="bi bi-pen-fill"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold text-dark mb-0">Buat Template Tanda Tangan Digital</h5>
                        <small class="text-muted">Siapkan tanda tangan digital pribadi untuk menandatangani dokumen akademik</small>
                    </div>
                </div>
            </div>

            <div class="card-clean-body p-4">
                <?php if (isset($errors['signature'])): ?>
                    <div class="alert alert-danger d-flex align-items-center gap-2 py-2.5 px-3 mb-4 small" role="alert">
                        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                        <div><?= htmlspecialchars($errors['signature']) ?></div>
                    </div>
                <?php endif; ?>

                <form id="sigForm" action="<?= BASE_URL ?>/user-signatures/store" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="input_method" id="inputMethod" value="<?= htmlspecialchars($values['input_method'] ?? 'canvas') ?>">
                    <input type="hidden" name="signature_canvas" id="signatureCanvasInput">

                    <!-- Section 1: Pengaturan & Label Template -->
                    <div class="mb-4">
                        <div class="fw-semibold text-dark small text-uppercase mb-3 pb-1 border-bottom" style="letter-spacing: 0.5px;">
                            1. Identitas Template
                        </div>

                        <div class="row g-3">
                            <div class="col-md-7">
                                <label class="form-label small fw-semibold">Nama / Label Template <span class="text-muted">(Opsional)</span></label>
                                <input type="text" name="label" class="form-control" 
                                       placeholder="Contoh: Tanda Tangan Utama, Paraf Resmi" 
                                       value="<?= htmlspecialchars($values['label'] ?? '') ?>" maxlength="100">
                                <small class="text-muted" style="font-size: 0.75rem;">Nama pengenal untuk memudahkan Anda memilih di antara beberapa template.</small>
                            </div>

                            <div class="col-md-5 d-flex align-items-center">
                                <div class="form-check mt-3 mt-md-0 p-3 bg-light rounded-3 border w-100">
                                    <input class="form-check-input" type="checkbox" name="is_default" value="1" id="isDefaultCheck" 
                                           <?= (!empty($values['is_default']) || $existingCount === 0) ? 'checked' : '' ?>>
                                    <label class="form-check-label small fw-semibold text-dark" for="isDefaultCheck">
                                        Jadikan Template Utama (Default)
                                    </label>
                                    <small class="text-muted d-block" style="font-size: 0.72rem;">
                                        Otomatis dipilih saat Anda membuka lembar penandatanganan berkas.
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section 2: Pilihan Metode Input Tanda Tangan -->
                    <div class="mb-4">
                        <div class="fw-semibold text-dark small text-uppercase mb-3 pb-1 border-bottom" style="letter-spacing: 0.5px;">
                            2. Metode Input Tanda Tangan
                        </div>

                        <!-- Method Tabs -->
                        <ul class="nav nav-pills mb-3" id="methodTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link <?= (($values['input_method'] ?? 'canvas') === 'canvas') ? 'active' : '' ?> d-inline-flex align-items-center gap-2 py-2 px-3 small fw-semibold" 
                                        id="tab-canvas-btn" data-bs-toggle="pill" data-bs-target="#tab-canvas" type="button" role="tab">
                                    <i class="bi bi-brush"></i> Gores di Kanvas Digital
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link <?= (($values['input_method'] ?? 'canvas') === 'upload') ? 'active' : '' ?> d-inline-flex align-items-center gap-2 py-2 px-3 small fw-semibold" 
                                        id="tab-upload-btn" data-bs-toggle="pill" data-bs-target="#tab-upload" type="button" role="tab">
                                    <i class="bi bi-cloud-arrow-up"></i> Upload Gambar PNG Transparan
                                </button>
                            </li>
                        </ul>

                        <!-- Tab Contents -->
                        <div class="tab-content" id="methodTabsContent">
                            
                            <!-- TAB 1: Canvas Drawing -->
                            <div class="tab-pane fade <?= (($values['input_method'] ?? 'canvas') === 'canvas') ? 'show active' : '' ?>" id="tab-canvas" role="tabpanel">
                                <!-- Canvas Controls Toolbar -->
                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 p-2 bg-light border rounded-top border-bottom-0 small">
                                    <div class="d-flex align-items-center gap-3">
                                        <!-- Color Picker -->
                                        <div class="d-flex align-items-center gap-1.5">
                                            <span class="text-muted" style="font-size: 0.75rem;">Warna Tinta:</span>
                                            <button type="button" class="btn btn-sm btn-color p-0 rounded-circle active" 
                                                    data-color="#0f172a" title="Hitam Klasik" 
                                                    style="width: 22px; height: 22px; background: #0f172a; border: 2px solid #fff; box-shadow: 0 0 0 1px #cbd5e1;"></button>
                                            <button type="button" class="btn btn-sm btn-color p-0 rounded-circle" 
                                                    data-color="#1e3a8a" title="Biru Resmi Dokumen" 
                                                    style="width: 22px; height: 22px; background: #1e3a8a; border: 2px solid #fff; box-shadow: 0 0 0 1px #cbd5e1;"></button>
                                            <button type="button" class="btn btn-sm btn-color p-0 rounded-circle" 
                                                    data-color="#064e3b" title="Hijau PNJ" 
                                                    style="width: 22px; height: 22px; background: #064e3b; border: 2px solid #fff; box-shadow: 0 0 0 1px #cbd5e1;"></button>
                                        </div>

                                        <!-- Stroke Width -->
                                        <div class="d-flex align-items-center gap-1.5 ms-2">
                                            <span class="text-muted" style="font-size: 0.75rem;">Ketebalan:</span>
                                            <button type="button" class="btn btn-sm btn-subtle btn-width py-0.5 px-2" data-width="1.8" style="font-size: 0.72rem;">Tipis</button>
                                            <button type="button" class="btn btn-sm btn-pnj-primary btn-width py-0.5 px-2 active" data-width="2.8" style="font-size: 0.72rem;">Sedang</button>
                                            <button type="button" class="btn btn-sm btn-subtle btn-width py-0.5 px-2" data-width="4.2" style="font-size: 0.72rem;">Tebal</button>
                                        </div>
                                    </div>

                                    <div>
                                        <button type="button" id="btnClearCanvas" class="btn btn-subtle btn-sm text-danger border-danger-subtle d-inline-flex align-items-center gap-1 py-1 px-2.5" style="font-size: 0.75rem;">
                                            <i class="bi bi-eraser-fill"></i> Bersihkan Kanvas
                                        </button>
                                    </div>
                                </div>

                                <!-- Canvas Area -->
                                <div class="sig-canvas-wrapper rounded-top-0">
                                    <canvas id="sigCanvas" class="sig-canvas"></canvas>
                                    <div class="sig-baseline-hint">
                                        <span><i class="bi bi-pencil me-1"></i>Goreskan tanda tangan Anda di area ini</span>
                                        <span>Garis Dasar</span>
                                    </div>
                                </div>
                                <div class="form-text mt-1 text-muted" style="font-size: 0.75rem;">
                                    <i class="bi bi-info-circle me-1"></i>Mendukung sentuhan layar HP/tablet serta mouse komputer. Goresan otomatis diproses dengan latar belakang transparan.
                                </div>
                            </div>

                            <!-- TAB 2: PNG Upload -->
                            <div class="tab-pane fade <?= (($values['input_method'] ?? 'canvas') === 'upload') ? 'show active' : '' ?>" id="tab-upload" role="tabpanel">
                                <div class="sig-upload-box" id="dropZone">
                                    <input type="file" name="signature_file" id="signatureFileInput" accept="image/png" class="d-none">
                                    
                                    <div id="uploadPrompt">
                                        <div class="text-success fs-1 mb-2">
                                            <i class="bi bi-cloud-arrow-up-fill"></i>
                                        </div>
                                        <div class="fw-bold text-dark mb-1">Klik untuk Memilih File atau Seret ke Sini</div>
                                        <div class="text-muted small mb-2">Hanya format <strong>PNG transparan (.png)</strong>, ukuran berkas maksimal 2 MB.</div>
                                        <span class="btn btn-subtle btn-sm px-3">
                                            <i class="bi bi-folder2-open me-1"></i> Pilih Berkas PNG
                                        </span>
                                    </div>

                                    <!-- Upload Preview Box -->
                                    <div id="uploadPreviewWrapper" class="d-none">
                                        <div class="checkerboard-bg p-3 rounded border mx-auto d-inline-flex align-items-center justify-content-center mb-3" 
                                             style="max-width: 340px; height: 160px; overflow: hidden;">
                                            <img id="uploadPreviewImg" src="" alt="Pratinjau Berkas" style="max-height: 100%; max-width: 100%; object-fit: contain;">
                                        </div>
                                        <div class="d-flex align-items-center justify-content-center gap-2">
                                            <span id="uploadFileName" class="small fw-semibold text-dark text-truncate" style="max-width: 250px;"></span>
                                            <button type="button" id="btnRemoveFile" class="btn btn-subtle btn-sm text-danger border-danger-subtle py-0.5 px-2" style="font-size: 0.75rem;">
                                                <i class="bi bi-x-circle me-1"></i> Ganti File
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-text mt-1 text-muted" style="font-size: 0.75rem;">
                                    <i class="bi bi-lightbulb me-1"></i>Gunakan gambar tanda tangan berlatar belakang transparan (alpha channel) agar tampak menyatu dan profesional di lembar PDF.
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="d-flex align-items-center justify-content-between pt-3 border-top">
                        <a href="<?= BASE_URL ?>/user-signatures" class="btn btn-subtle px-3">
                            Batal
                        </a>
                        <button type="submit" id="btnSubmit" class="btn btn-pnj-primary px-4 d-inline-flex align-items-center gap-2">
                            <span class="spinner-border spinner-border-sm d-none" id="submitSpinner" role="status" aria-hidden="true"></span>
                            <i class="bi bi-check2-circle" id="submitIcon"></i>
                            <span id="submitText">Simpan Template Tanda Tangan</span>
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

<!-- Interactive Signature Canvas & Upload JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const canvas = document.getElementById('sigCanvas');
    const ctx = canvas.getContext('2d');
    const inputMethod = document.getElementById('inputMethod');
    const sigInput = document.getElementById('signatureCanvasInput');
    const form = document.getElementById('sigForm');
    const btnClear = document.getElementById('btnClearCanvas');
    const btnSubmit = document.getElementById('btnSubmit');
    const submitSpinner = document.getElementById('submitSpinner');
    const submitIcon = document.getElementById('submitIcon');
    const submitText = document.getElementById('submitText');

    let isDrawing = false;
    let hasDrawn = false;
    let currentColor = '#0f172a';
    let currentWidth = 2.8;

    // HiDPI Scaling for Ultra-Sharp Canvas Strokes
    function resizeCanvas() {
        const rect = canvas.getBoundingClientRect();
        const dpr = window.devicePixelRatio || 1;
        
        // Save current canvas content if already drawn
        let tempCanvas = null;
        if (hasDrawn) {
            tempCanvas = document.createElement('canvas');
            tempCanvas.width = canvas.width;
            tempCanvas.height = canvas.height;
            const tempCtx = tempCanvas.getContext('2d');
            tempCtx.drawImage(canvas, 0, 0);
        }

        canvas.width = rect.width * dpr;
        canvas.height = rect.height * dpr;
        ctx.scale(dpr, dpr);

        if (tempCanvas) {
            ctx.drawImage(tempCanvas, 0, 0, rect.width, rect.height);
        }

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
            return {
                x: e.touches[0].clientX - rect.left,
                y: e.touches[0].clientY - rect.top
            };
        }
        return {
            x: e.clientX - rect.left,
            y: e.clientY - rect.top
        };
    }

    let lastX = 0;
    let lastY = 0;

    function startDrawing(e) {
        e.preventDefault();
        isDrawing = true;
        const coords = getCoords(e);
        lastX = coords.x;
        lastY = coords.y;

        // Draw a dot on initial tap
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
        // Smooth line midpoint interpolation
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
        }
    }

    // Mouse Events
    canvas.addEventListener('mousedown', startDrawing);
    canvas.addEventListener('mousemove', draw);
    canvas.addEventListener('mouseup', stopDrawing);
    canvas.addEventListener('mouseleave', stopDrawing);

    // Touch Events
    canvas.addEventListener('touchstart', startDrawing, { passive: false });
    canvas.addEventListener('touchmove', draw, { passive: false });
    canvas.addEventListener('touchend', stopDrawing);

    // Clear Canvas Action
    btnClear.addEventListener('click', function () {
        const rect = canvas.getBoundingClientRect();
        ctx.clearRect(0, 0, rect.width, rect.height);
        hasDrawn = false;
        sigInput.value = '';
    });

    // Ink Color Selection
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

    // Stroke Width Selection
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

    // Tabs toggle tracking
    const tabCanvasBtn = document.getElementById('tab-canvas-btn');
    const tabUploadBtn = document.getElementById('tab-upload-btn');

    tabCanvasBtn.addEventListener('shown.bs.tab', function () {
        inputMethod.value = 'canvas';
        resizeCanvas();
    });

    tabUploadBtn.addEventListener('shown.bs.tab', function () {
        inputMethod.value = 'upload';
    });

    // File Upload Drag & Drop & Preview
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
            uploadPreviewImg.src = e.target.result;
            uploadFileName.textContent = file.name;
            uploadPrompt.classList.add('d-none');
            uploadPreviewWrapper.classList.remove('d-none');
        };
        reader.readAsDataURL(file);
    }

    btnRemoveFile.addEventListener('click', function (e) {
        e.stopPropagation();
        fileInput.value = '';
        uploadPreviewImg.src = '';
        uploadFileName.textContent = '';
        uploadPreviewWrapper.classList.add('d-none');
        uploadPrompt.classList.remove('d-none');
    });

    // Helper: Trim transparent edges around signature for clean PDF stamping
    function getTrimmedCanvasData() {
        const dpr = window.devicePixelRatio || 1;
        const width = canvas.width;
        const height = canvas.height;
        const imgData = ctx.getImageData(0, 0, width, height);
        const data = imgData.data;

        let minX = width, minY = height, maxX = 0, maxY = 0;
        let found = false;

        for (let y = 0; y < height; y++) {
            for (let x = 0; x < width; x++) {
                const alpha = data[(y * width + x) * 4 + 3];
                if (alpha > 10) { // Non-transparent pixel
                    if (x < minX) minX = x;
                    if (x > maxX) maxX = x;
                    if (y < minY) minY = y;
                    if (y > maxY) maxY = y;
                    found = true;
                }
            }
        }

        if (!found) return null;

        // Add 15px padding
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

    // Form Submission Handler
    form.addEventListener('submit', function (e) {
        if (inputMethod.value === 'canvas') {
            if (!hasDrawn) {
                e.preventDefault();
                alert('Silakan goreskan tanda tangan Anda pada kanvas terlebih dahulu.');
                return false;
            }

            const trimmedData = getTrimmedCanvasData();
            if (!trimmedData) {
                e.preventDefault();
                alert('Kanvas tanda tangan masih kosong.');
                return false;
            }
            sigInput.value = trimmedData;
        } else if (inputMethod.value === 'upload') {
            if (!fileInput.files || fileInput.files.length === 0) {
                e.preventDefault();
                alert('Silakan pilih berkas gambar PNG tanda tangan untuk diunggah.');
                return false;
            }
        }

        // Prevent double submit with loading state
        btnSubmit.disabled = true;
        submitSpinner.classList.remove('d-none');
        submitIcon.classList.add('d-none');
        submitText.textContent = 'Menyimpan...';
    });
});
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
