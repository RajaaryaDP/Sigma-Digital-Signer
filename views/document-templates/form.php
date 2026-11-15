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
            <a href="<?= BASE_URL ?>/document-templates" class="text-decoration-none text-muted small d-inline-flex align-items-center gap-1">
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

        <div class="card-clean shadow-sm">
            <div class="card-clean-header py-3 px-4">
                <div class="d-flex align-items-center gap-2.5">
                    <div class="rounded-circle d-flex align-items-center justify-content-center"
                         style="width: 36px; height: 36px; background: var(--pnj-green-50); color: var(--pnj-green-800);">
                        <i class="bi bi-file-earmark-word-fill fs-5"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold text-dark mb-0">
                            <?= $isEdit ? 'Ubah Template Dokumen' : 'Tambah Template Dokumen Baru' ?>
                        </h5>
                        <small class="text-muted">
                            Unggah contoh berkas Word (.docx) agar civitas kampus dapat mengunduh format surat yang baku
                        </small>
                    </div>
                </div>
            </div>

            <div class="card-clean-body p-4">
                <form id="templateForm" action="<?= $actionUrl ?>" method="POST" enctype="multipart/form-data">

                    <div class="mb-3">
                        <label for="templateName" class="form-label fw-semibold">
                            Nama Template <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                               id="templateName"
                               name="name"
                               class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                               maxlength="200"
                               required
                               placeholder="Contoh: Template Surat Permohonan Izin Magang / PKL"
                               value="<?= htmlspecialchars($values['name'] ?? '') ?>">
                        <div class="form-text small">
                            Gunakan nama yang jelas dan spesifik agar mudah ditemukan pengguna melalui pencarian. Maksimal 200 karakter.
                        </div>
                        <?php if (isset($errors['name'])): ?>
                            <div class="invalid-feedback small"><?= htmlspecialchars($errors['name']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-4">
                        <label for="templateDesc" class="form-label fw-semibold">
                            Deskripsi <span class="text-muted fw-normal">(Opsional)</span>
                        </label>
                        <textarea id="templateDesc"
                                  name="description"
                                  rows="3"
                                  maxlength="2000"
                                  class="form-control <?= isset($errors['description']) ? 'is-invalid' : '' ?>"
                                  placeholder="Jelaskan kegunaan template, syarat penggunaan, atau pihak penandatangan yang disarankan..."><?= htmlspecialchars($values['description'] ?? '') ?></textarea>
                        <?php if (isset($errors['description'])): ?>
                            <div class="invalid-feedback small"><?= htmlspecialchars($errors['description']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-4">
                        <label for="docxFileInput" class="form-label fw-semibold">
                            Berkas Template Word (.docx)
                            <?php if (!$isEdit): ?>
                                <span class="text-danger">*</span>
                            <?php else: ?>
                                <span class="text-muted fw-normal">(Opsional saat ubah)</span>
                            <?php endif; ?>
                        </label>

                        <?php if ($isEdit && !empty($template)): ?>
                            <div class="p-3 rounded-3 border bg-light mb-2 d-flex flex-wrap align-items-center justify-content-between gap-2">
                                <div class="d-flex align-items-center gap-2 min-w-0">
                                    <i class="bi bi-file-earmark-word-fill text-primary fs-5"></i>
                                    <div class="min-w-0">
                                        <div class="small fw-semibold text-dark text-truncate">
                                            <?= htmlspecialchars($template['file_name_original']) ?>
                                        </div>
                                        <div class="text-muted" style="font-size: 0.74rem;">
                                            Berkas aktif saat ini &bull; <?= htmlspecialchars($formatBytes((int) ($template['file_size'] ?? 0))) ?>
                                        </div>
                                    </div>
                                </div>
                                <a href="<?= BASE_URL ?>/document-templates/<?= htmlspecialchars($template['id']) ?>/download"
                                   class="btn btn-subtle btn-sm">
                                    <i class="bi bi-download me-1"></i> Unduh Berkas Ini
                                </a>
                            </div>
                        <?php endif; ?>

                        <input type="file"
                               id="docxFileInput"
                               name="docx_file"
                               class="form-control <?= isset($errors['docx_file']) ? 'is-invalid' : '' ?>"
                               accept=".docx,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                               <?= !$isEdit ? 'required' : '' ?>>
                        <div class="form-text small" id="docxFileHelp">
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

                    <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                        <a href="<?= BASE_URL ?>/document-templates" class="btn btn-subtle px-4">Batal</a>
                        <button type="submit" id="btnSubmitTemplate" class="btn btn-pnj-primary px-4 d-inline-flex align-items-center gap-2">
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

