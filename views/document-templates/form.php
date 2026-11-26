<?php
require_once __DIR__ . '/../layout/header.php';

$formatBytes = static function (int $bytes): string {
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    }
    if ($bytes >= 1024) {
        return number_format($bytes / 1024, 1) . ' KB';
    }
    return $bytes . ' B';
};

$actionUrl = $isEdit
    ? BASE_URL . '/document-templates/' . htmlspecialchars($template['id']) . '/update'
    : BASE_URL . '/document-templates/store';
?>

<div class="row justify-content-center">
    <div class="col-lg-8 col-xl-7">
        <!-- Back Link -->
        <div class="mb-3">
            <a href="<?= BASE_URL ?>/document-templates" class="text-decoration-none small d-inline-flex align-items-center gap-2" style="color: var(--text-muted);">
                <i class="bi bi-arrow-left"></i>
                <span>Kembali ke Template Dokumen</span>
            </a>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger border-0 shadow-sm mb-3">
                <div class="fw-semibold mb-1 small">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i> Periksa kembali input Anda:
                </div>
                <ul class="mb-0 ps-3 small">
                    <?php foreach ($errors as $err): ?>
                        <li><?= htmlspecialchars($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="card-sigma">
            <div class="card-sigma-header py-3 px-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width: 38px; height: 38px; background: var(--sigma-tint-100); color: var(--sigma-purple-700);">
                        <i class="bi bi-file-earmark-word-fill fs-5"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-0" style="color: var(--text-heading);">
                            <?= $isEdit ? 'Ubah Template Dokumen' : 'Tambah Template Dokumen Baru' ?>
                        </h5>
                        <small style="color: var(--text-muted);">
                            Unggah contoh berkas Word (.docx) agar civitas kampus dapat mengunduh format surat yang baku
                        </small>
                    </div>
                </div>
            </div>

            <div class="card-sigma-body p-4">
                <form id="templateForm" action="<?= $actionUrl ?>" method="POST" enctype="multipart/form-data">
                    <?= Csrf::field() ?>

                    <div class="mb-3">
                        <label for="templateName" class="sigma-form-label">
                            Nama Template <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                               id="templateName"
                               name="name"
                               class="form-control sigma-input <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                               maxlength="200"
                               required
                               placeholder="Contoh: Template Surat Permohonan Izin Magang / PKL"
                               value="<?= htmlspecialchars($values['name'] ?? '') ?>">
                        <div class="form-text small" style="color: var(--text-muted);">
                            Gunakan nama yang jelas dan spesifik agar mudah ditemukan pengguna melalui pencarian. Maksimal 200 karakter.
                        </div>
                        <?php if (isset($errors['name'])): ?>
                            <div class="invalid-feedback small"><?= htmlspecialchars($errors['name']) ?></div>
                        <?php endif; ?>
                    </div>

                    <?php
                        $categoryOptions  = $categories ?? DocumentTemplatesModel::getCategories();
                        $selectedCategory = (string) ($values['category'] ?? '');
                    ?>
                    <div class="mb-3">
                        <label for="templateCategory" class="sigma-form-label">
                            Kategori Template <span class="text-danger">*</span>
                        </label>
                        <select id="templateCategory"
                                name="category"
                                class="form-select sigma-input <?= isset($errors['category']) ? 'is-invalid' : '' ?>"
                                required>
                            <option value="" disabled <?= $selectedCategory === '' ? 'selected' : '' ?>>
                                — Pilih Kategori Template —
                            </option>
                            <?php foreach ($categoryOptions as $catKey => $catLabel): ?>
                                <option value="<?= htmlspecialchars($catKey) ?>" <?= $selectedCategory === $catKey ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($catLabel) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text small" style="color: var(--text-muted);">
                            Kategori digunakan untuk mengelompokkan dan memfilter kartu template di halaman Pustaka Dokumen.
                        </div>
                        <?php if (isset($errors['category'])): ?>
                            <div class="invalid-feedback small"><?= htmlspecialchars($errors['category']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-4">
                        <label for="templateDesc" class="sigma-form-label">
                            Deskripsi <span class="fw-normal" style="color: var(--text-muted);">(Opsional)</span>
                        </label>
                        <textarea id="templateDesc"
                                  name="description"
                                  rows="3"
                                  maxlength="2000"
                                  class="form-control sigma-input <?= isset($errors['description']) ? 'is-invalid' : '' ?>"
                                  placeholder="Jelaskan kegunaan template, syarat penggunaan, atau pihak penandatangan yang disarankan..."><?= htmlspecialchars($values['description'] ?? '') ?></textarea>
                        <?php if (isset($errors['description'])): ?>
                            <div class="invalid-feedback small"><?= htmlspecialchars($errors['description']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-4">
                        <label for="docxFileInput" class="sigma-form-label">
                            Berkas Template Word (.docx)
                            <?php if (!$isEdit): ?>
                                <span class="text-danger">*</span>
                            <?php else: ?>
                                <span class="fw-normal" style="color: var(--text-muted);">(Opsional saat ubah)</span>
                            <?php endif; ?>
                        </label>

                        <?php if ($isEdit && !empty($template)): ?>
                            <div class="p-3 rounded-3 border mb-2 d-flex flex-wrap align-items-center justify-content-between gap-2"
                                 style="background: var(--sigma-tint-50); border-color: var(--border-subtle) !important;">
                                <div class="d-flex align-items-center gap-2 min-w-0">
                                    <i class="bi bi-file-earmark-word-fill text-primary fs-5"></i>
                                    <div class="min-w-0">
                                        <div class="small fw-semibold text-truncate" style="color: var(--text-heading);">
                                            <?= htmlspecialchars($template['file_name_original']) ?>
                                        </div>
                                        <div style="font-size: 0.74rem; color: var(--text-muted);">
                                            Berkas aktif saat ini &bull; <?= htmlspecialchars($formatBytes((int) ($template['file_size'] ?? 0))) ?>
                                        </div>
                                    </div>
                                </div>
                                <a href="<?= BASE_URL ?>/document-templates/<?= htmlspecialchars($template['id']) ?>/download"
                                   class="btn-sigma-outline btn-sigma-sm text-decoration-none">
                                    <i class="bi bi-download"></i> Unduh Berkas Ini
                                </a>
                            </div>
                        <?php endif; ?>

                        <input type="file"
                               id="docxFileInput"
                               name="docx_file"
                               class="form-control sigma-input <?= isset($errors['docx_file']) ? 'is-invalid' : '' ?>"
                               accept=".docx,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                               <?= !$isEdit ? 'required' : '' ?>>
                        <div class="form-text small" id="docxFileHelp" style="color: var(--text-muted);">
                            <?php if ($isEdit): ?>
                                Biarkan kosong jika tidak ingin mengganti berkas <code>.docx</code>. Jika mengganti, berkas lama akan dihapus permanen. Maksimal 10 MB.
                            <?php else: ?>
                                Hanya menerima berkas Microsoft Word berformat <strong>.docx</strong> dengan ukuran maksimal <strong>10 MB</strong>.
                            <?php endif; ?>
                        </div>
                        <div class="text-danger small mt-1 d-none" id="docxClientError"></div>
                        <?php if (isset($errors['docx_file'])): ?>
                            <div class="invalid-feedback small d-block"><?= htmlspecialchars($errors['docx_file']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-3 border-top" style="border-color: var(--border-subtle) !important;">
                        <a href="<?= BASE_URL ?>/document-templates" class="btn-sigma-outline px-4 text-decoration-none">Batal</a>
                        <button type="submit" id="btnSubmitTemplate" class="btn-sigma-navy px-4">
                            <i class="bi bi-check-lg"></i>
                            <span><?= $isEdit ? 'Simpan Perubahan' : 'Simpan Template' ?></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('templateForm');
        const fileInput = document.getElementById('docxFileInput');
        const clientErr = document.getElementById('docxClientError');
        const submitBtn = document.getElementById('btnSubmitTemplate');
        const maxBytes = 10 * 1024 * 1024;

        function validateSelectedFile() {
            clientErr.classList.add('d-none');
            clientErr.textContent = '';

            if (!fileInput.files || fileInput.files.length === 0) {
                return true;
            }

            const file = fileInput.files[0];
            const lowerName = (file.name || '').toLowerCase();
            if (!lowerName.endsWith('.docx')) {
                clientErr.textContent = 'Format berkas tidak valid. Harap pilih berkas Microsoft Word (.docx).';
                clientErr.classList.remove('d-none');
                return false;
            }

            if (file.size <= 0) {
                clientErr.textContent = 'Berkas yang dipilih kosong (0 byte).';
                clientErr.classList.remove('d-none');
                return false;
            }

            if (file.size > maxBytes) {
                clientErr.textContent = 'Ukuran berkas melebihi batas maksimal 10 MB.';
                clientErr.classList.remove('d-none');
                return false;
            }

            return true;
        }

        if (fileInput) {
            fileInput.addEventListener('change', validateSelectedFile);
        }

        if (form && submitBtn) {
            form.addEventListener('submit', function (e) {
                if (!validateSelectedFile()) {
                    e.preventDefault();
                    fileInput.focus();
                    return;
                }
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span><span>Menyimpan...</span>';
            });
        }
    });
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
