<?php
$title = 'Ajukan Dokumen Baru — SIGMA';
require_once __DIR__ . '/../layout/header.php';

$curType = $values['signature_type'] ?? 'sequential';
$signersData = !empty($postedSigners) ? $postedSigners : [
    ['user_id' => '', 'role_title' => '', 'step_order' => 1]
];
?>

<div class="row justify-content-center">
    <div class="col-lg-10 col-xl-9">
        <!-- Back Link -->
        <div class="mb-3">
            <a href="<?= BASE_URL ?>/documents" class="text-decoration-none text-muted small d-inline-flex align-items-center gap-1.5">
                <i class="bi bi-arrow-left"></i>
                <span>Kembali ke Dashboard Dokumen</span>
            </a>
        </div>

        <!-- Form Card -->
        <div class="card-clean mb-4">
            <div class="card-clean-header py-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle d-flex align-items-center justify-content-center" 
                         style="width: 34px; height: 34px; background: var(--pnj-green-50); color: var(--pnj-green-800);">
                        <i class="bi bi-cloud-arrow-up-fill fs-5"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold text-dark mb-0">Ajukan Dokumen Baru</h5>
                        <small class="text-muted">Unggah dokumen PDF dan tentukan alur penandatanganan berjenjang kampus</small>
                    </div>
                </div>
            </div>

            <div class="card-clean-body p-4">
                <form id="docForm" action="<?= BASE_URL ?>/documents/store" method="POST" enctype="multipart/form-data">

                    <!-- Section 1: Informasi Dokumen -->
                    <div class="mb-4">
                        <div class="fw-semibold text-dark small text-uppercase mb-3 pb-1 border-bottom" style="letter-spacing: 0.5px;">
                            1. Informasi & Berkas Dokumen
                        </div>

                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-medium">Judul Dokumen <span class="text-danger">*</span></label>
                                <input type="text" name="title" 
                                       class="form-control <?= isset($errors['title']) ? 'is-invalid' : '' ?>" 
                                       placeholder="Contoh: Lembar Pengesahan Tugas Akhir — Ahmad Fauzi"
                                       value="<?= htmlspecialchars($values['title'] ?? '') ?>" 
                                       maxlength="500" required>
                                <?php if (isset($errors['title'])): ?>
                                    <div class="invalid-feedback small"><?= htmlspecialchars($errors['title']) ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-medium">Deskripsi / Catatan Tambahan <span class="text-muted small">(Opsional)</span></label>
                                <textarea name="description" rows="2" 
                                          class="form-control" 
                                          placeholder="Keterangan singkat mengenai keperluan dokumen atau pesan pengantar"><?= htmlspecialchars($values['description'] ?? '') ?></textarea>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-medium">Tipe Alur Tanda Tangan <span class="text-danger">*</span></label>
                                <select name="signature_type" id="signatureType" 
                                        class="form-select <?= isset($errors['signature_type']) ? 'is-invalid' : '' ?>" required>
                                    <option value="sequential" <?= $curType === 'sequential' ? 'selected' : '' ?>>
                                        Tanda Tangan Berjenjang (Sequential)
                                    </option>
                                    <option value="single" <?= $curType === 'single' ? 'selected' : '' ?>>
                                        Tanda Tangan Mandiri (Single-Sign)
                                    </option>
                                </select>
                                <div class="form-text small" id="sigTypeHelp">
                                    Berjenjang: melalui verifikasi Admin lalu ditandatangani sesuai urutan jenjang jabatan.
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-medium">Berkas Dokumen PDF <span class="text-danger">*</span></label>
                                <input type="file" name="pdf_file" id="pdfFileInput"
                                       class="form-control <?= isset($errors['pdf_file']) ? 'is-invalid' : '' ?>" 
                                       accept=".pdf,application/pdf" required>
                                <div class="form-text small">
                                    Hanya berkas PDF (.pdf) dengan ukuran maksimum 10 MB.
                                </div>
                                <?php if (isset($errors['pdf_file'])): ?>
                                    <div class="invalid-feedback small d-block"><?= htmlspecialchars($errors['pdf_file']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Section 2: Penandatangan Berjenjang (Sequential only) -->
                    <div id="sequentialSection" class="mb-4" style="<?= $curType === 'single' ? 'display: none;' : '' ?>">
                        <div class="d-flex justify-content-between align-items-center mb-3 pb-1 border-bottom">
                            <div class="fw-semibold text-dark small text-uppercase" style="letter-spacing: 0.5px;">
                                2. Susunan Penandatangan Berjenjang
                            </div>
                            <button type="button" id="btnAddSigner" class="btn btn-subtle btn-sm">
                                <i class="bi bi-person-plus me-1 text-success"></i> Tambah Penandatangan
                            </button>
                        </div>

                        <p class="text-muted small mb-3">
                            Tentukan urutan hierarki penandatangan (dimulai dari urutan ke-1). Setelah disetujui Admin, 
                            dokumen akan diteruskan secara bergiliran.
                        </p>

                        <?php if (isset($errors['signers'])): ?>
                            <div class="alert alert-danger py-2 px-3 small mb-3" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= htmlspecialchars($errors['signers']) ?>
                            </div>
                        <?php endif; ?>

                        <!-- Table Daftar Penandatangan -->
                        <div class="table-responsive border rounded-3 mb-2">
                            <table class="table table-hover align-middle mb-0" id="signersTable">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 80px;" class="text-center">Urutan</th>
                                        <th>Penandatangan (Civitas) <span class="text-danger">*</span></th>
                                        <th>Peran / Jabatan Dokumen</th>
                                        <th style="width: 60px;" class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="signersTableBody">
                                    <?php foreach ($signersData as $idx => $s): ?>
                                    <tr class="signer-row">
                                        <td class="text-center">
                                            <span class="badge bg-secondary step-number-badge"><?= $idx + 1 ?></span>
                                            <input type="hidden" name="signer_step_order[]" class="step-order-input" value="<?= $idx + 1 ?>">
                                        </td>
                                        <td>
                                            <select name="signer_user_id[]" class="form-select form-select-sm signer-user-select" required>
                                                <option value="">-- Pilih Civitas Kampus --</option>
                                                <?php foreach ($availableSigners as $as): ?>
                                                    <?php 
                                                        $isSelected = ($s['user_id'] ?? '') === $as['id'];
                                                        $cType = ucfirst($as['civitas_type'] ?? 'Civitas');
                                                        $idNum = !empty($as['identifier']) ? " ({$as['identifier']})" : '';
                                                        $pos = !empty($as['position']) ? " — {$as['position']}" : '';
                                                    ?>
                                                    <option value="<?= htmlspecialchars($as['id']) ?>" 
                                                            data-position="<?= htmlspecialchars($as['position'] ?? '') ?>"
                                                            <?= $isSelected ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($as['name'] . $idNum . " [{$cType}]" . $pos) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="text" name="signer_role_title[]" 
                                                   class="form-control form-control-sm signer-role-input" 
                                                   placeholder="Contoh: Dosen Pembimbing I / Ketua Jurusan"
                                                   value="<?= htmlspecialchars($s['role_title'] ?? '') ?>">
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-outline-danger btn-sm border-0 btn-remove-signer" title="Hapus baris">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Template JSON Civitas Options untuk JS Clone -->
                    <template id="signerOptionsTemplate">
                        <option value="">-- Pilih Civitas Kampus --</option>
                        <?php foreach ($availableSigners as $as): ?>
                            <?php 
                                $cType = ucfirst($as['civitas_type'] ?? 'Civitas');
                                $idNum = !empty($as['identifier']) ? " ({$as['identifier']})" : '';
                                $pos = !empty($as['position']) ? " — {$as['position']}" : '';
                            ?>
                            <option value="<?= htmlspecialchars($as['id']) ?>" 
                                    data-position="<?= htmlspecialchars($as['position'] ?? '') ?>">
                                <?= htmlspecialchars($as['name'] . $idNum . " [{$cType}]" . $pos) ?>
                            </option>
                        <?php endforeach; ?>
                    </template>

                    <!-- Submit Actions -->
                    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                        <a href="<?= BASE_URL ?>/documents" class="btn btn-subtle">
                            <i class="bi bi-x-lg me-1"></i> Batal
                        </a>
                        <button type="submit" id="btnSubmitForm" class="btn btn-pnj-primary px-4">
                            <i class="bi bi-arrow-right-circle me-1.5"></i>
                            <span id="submitBtnText">Unggah & Atur Slot Tanda Tangan</span>
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const sigTypeSelect = document.getElementById('signatureType');
    const seqSection    = document.getElementById('sequentialSection');
    const sigTypeHelp   = document.getElementById('sigTypeHelp');
    const submitBtnText = document.getElementById('submitBtnText');
    const btnAddSigner  = document.getElementById('btnAddSigner');
    const tableBody     = document.getElementById('signersTableBody');
    const optionsTpl    = document.getElementById('signerOptionsTemplate');
    const fileInput     = document.getElementById('pdfFileInput');
    const docForm       = document.getElementById('docForm');

    // Toggle tipe alur tanda tangan
    function updateSigTypeUI() {
        if (sigTypeSelect.value === 'single') {
            seqSection.style.display = 'none';
            sigTypeHelp.textContent = 'Mandiri: Anda menandatangani sendiri berkas ini tanpa review Admin dan langsung selesai.';
            submitBtnText.textContent = 'Unggah & Selesaikan Dokumen';
            // Disable required pada select signer jika single
            tableBody.querySelectorAll('.signer-user-select').forEach(el => el.removeAttribute('required'));
        } else {
            seqSection.style.display = 'block';
            sigTypeHelp.textContent = 'Berjenjang: melalui verifikasi Admin lalu ditandatangani sesuai urutan jenjang jabatan.';
            submitBtnText.textContent = 'Unggah & Atur Slot Tanda Tangan';
            tableBody.querySelectorAll('.signer-user-select').forEach(el => el.setAttribute('required', 'required'));
        }
    }
    sigTypeSelect.addEventListener('change', updateSigTypeUI);
    updateSigTypeUI();

    // Re-index urutan penandatangan
    function reindexSigners() {
        const rows = tableBody.querySelectorAll('.signer-row');
        rows.forEach((row, idx) => {
            const stepNum = idx + 1;
            const badge = row.querySelector('.step-number-badge');
            const stepInput = row.querySelector('.step-order-input');
            if (badge) badge.textContent = stepNum;
            if (stepInput) stepInput.value = stepNum;

            const removeBtn = row.querySelector('.btn-remove-signer');
            if (removeBtn) {
                removeBtn.disabled = (rows.length <= 1);
            }
        });
    }

    // Auto-fill peran/jabatan dari data-position
    tableBody.addEventListener('change', function (e) {
        if (e.target.classList.contains('signer-user-select')) {
            const selectedOpt = e.target.options[e.target.selectedIndex];
            const pos = selectedOpt ? selectedOpt.getAttribute('data-position') : '';
            const row = e.target.closest('.signer-row');
            const roleInput = row.querySelector('.signer-role-input');
            if (roleInput && !roleInput.value && pos) {
                roleInput.value = pos;
            }
        }
    });

    // Tambah penandatangan
    btnAddSigner.addEventListener('click', function () {
        const tr = document.createElement('tr');
        tr.className = 'signer-row';
        tr.innerHTML = `
            <td class="text-center">
                <span class="badge bg-secondary step-number-badge">1</span>
                <input type="hidden" name="signer_step_order[]" class="step-order-input" value="1">
            </td>
            <td>
                <select name="signer_user_id[]" class="form-select form-select-sm signer-user-select" required>
                    ${optionsTpl.innerHTML}
                </select>
            </td>
            <td>
                <input type="text" name="signer_role_title[]" 
                       class="form-control form-control-sm signer-role-input" 
                       placeholder="Contoh: Dosen Pembimbing I / Ketua Jurusan">
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-outline-danger btn-sm border-0 btn-remove-signer" title="Hapus baris">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;
        tableBody.appendChild(tr);
        reindexSigners();
    });

    // Hapus penandatangan
    tableBody.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-remove-signer');
        if (btn) {
            const rows = tableBody.querySelectorAll('.signer-row');
            if (rows.length > 1) {
                btn.closest('.signer-row').remove();
                reindexSigners();
            }
        }
    });

    // Validasi ukuran PDF client-side
    fileInput.addEventListener('change', function () {
        if (this.files && this.files[0]) {
            const file = this.files[0];
            const maxBytes = 10 * 1024 * 1024; // 10MB
            if (file.size > maxBytes) {
                alert('Ukuran berkas PDF melebihi batas 10 MB (' + (file.size / (1024 * 1024)).toFixed(2) + ' MB). Silakan pilih berkas yang lebih kecil.');
                this.value = '';
            }
        }
    });

    // Validasi form submit: duplikasi penandatangan
    docForm.addEventListener('submit', function (e) {
        if (sigTypeSelect.value === 'sequential') {
            const selects = tableBody.querySelectorAll('.signer-user-select');
            const userIds = [];
            for (let sel of selects) {
                if (sel.value) {
                    if (userIds.includes(sel.value)) {
                        e.preventDefault();
                        alert('Terdapat penandatangan yang dipilih lebih dari satu kali. Mohon pastikan setiap penandatangan unik.');
                        sel.focus();
                        return;
                    }
                    userIds.push(sel.value);
                }
            }
            if (userIds.length === 0) {
                e.preventDefault();
                alert('Tanda tangan berjenjang memerlukan minimal 1 penandatangan.');
            }
        }
    });

    reindexSigners();
});
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>

