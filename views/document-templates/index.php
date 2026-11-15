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
?>

<!-- Page Header & Action Bar -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">
            <i class="bi bi-file-earmark-word me-2 text-success"></i>Template Dokumen
        </h4>
        <p class="text-muted small mb-0">
            Unduh contoh format dokumen resmi kampus (.docx) untuk disesuaikan sebelum diajukan tanda tangan digital.
        </p>
    </div>
    <?php if (!empty($isAdmin)): ?>
        <div>
            <a href="<?= BASE_URL ?>/document-templates/create" class="btn btn-pnj-primary d-inline-flex align-items-center gap-2 shadow-sm">
                <i class="bi bi-plus-circle"></i>
                <span>Tambah Template</span>
            </a>
        </div>
    <?php endif; ?>
</div>

<!-- Info Callout -->
<div class="card-clean mb-4 border-start border-4 border-success">
    <div class="card-clean-body py-3 px-4 d-flex align-items-start gap-3">
        <div class="text-success fs-4 mt-1">
            <i class="bi bi-file-earmark-arrow-down"></i>
        </div>
        <div>
            <div class="fw-semibold text-dark small">Panduan Penggunaan Template Dokumen (.docx)</div>
            <div class="text-muted small">
                <?php if (!empty($isAdmin)): ?>
                    Unggah dan kelola template resmi berformat Microsoft Word (<strong>.docx</strong>). Setiap template dapat diunduh langsung oleh seluruh civitas kampus yang sudah masuk ke sistem.
                <?php else: ?>
                    Pilih dan unduh berkas template <strong>.docx</strong> di bawah ini, lengkapi isi dokumen di perangkat Anda, lalu ekspor ke format <strong>PDF</strong> sebelum diajukan pada menu
                    <a href="<?= BASE_URL ?>/documents/create" class="fw-semibold text-success text-decoration-none">Ajukan Dokumen</a>.
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Search & Summary Bar -->
<div class="card-clean mb-4">
    <div class="card-clean-body p-3">
        <div class="row g-3 align-items-center justify-content-between">
            <div class="col-md-5 d-flex align-items-center gap-2">
                <span class="badge rounded-pill px-3 py-2 fw-semibold"
                      style="background: var(--pnj-green-50); color: var(--pnj-green-800); border: 1px solid var(--pnj-green-100);">
                    <i class="bi bi-collection me-1"></i>
                    <?= count($templates) ?> Template <?= $search !== '' ? 'Ditemukan' : 'Tersedia' ?>
                </span>
                <?php if ($search !== ''): ?>
                    <span class="text-muted small text-truncate">
                        Kata kunci: <strong>"<?= htmlspecialchars($search) ?>"</strong>
                    </span>
                <?php endif; ?>
            </div>

            <div class="col-md-7">
                <form action="<?= BASE_URL ?>/document-templates" method="GET" class="d-flex gap-2">
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted border-end-0">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text"
                               name="q"
                               class="form-control border-start-0"
                               placeholder="Cari template berdasarkan nama..."
                               maxlength="200"
                               value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <button type="submit" class="btn btn-subtle px-3">Cari</button>
                    <?php if ($search !== ''): ?>
                        <a href="<?= BASE_URL ?>/document-templates" class="btn btn-outline-secondary" title="Reset pencarian">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Templates List / Empty State -->
<?php if (empty($templates)): ?>
    <div class="card-clean p-5 text-center my-4">
        <div class="rounded-circle d-inline-flex align-items-center justify-content-center mx-auto mb-3"
             style="width: 72px; height: 72px; background: var(--pnj-green-50); color: var(--pnj-green-800); font-size: 2rem;">
            <i class="bi <?= $search !== '' ? 'bi-search' : 'bi-file-earmark-word' ?>"></i>
        </div>
        <h5 class="fw-bold text-dark mb-2">
            <?= $search !== '' ? 'Template Dokumen Tidak Ditemukan' : 'Belum Ada Template Dokumen' ?>
        </h5>
        <p class="text-muted small mx-auto mb-4" style="max-width: 520px;">
            <?php if ($search !== ''): ?>
                Tidak ada template dokumen dengan nama yang mengandung <strong>"<?= htmlspecialchars($search) ?>"</strong>. Coba kata kunci lain atau tampilkan semua template.
            <?php elseif (!empty($isAdmin)): ?>
                Belum ada contoh template dokumen yang diunggah. Tambahkan berkas Word (.docx) pertama agar civitas kampus dapat mengunduh format surat resmi dengan mudah.
            <?php else: ?>
                Administrator belum menambahkan template dokumen resmi saat ini. Silakan periksa kembali nanti.
            <?php endif; ?>
        </p>
        <div>
            <?php if ($search !== ''): ?>
                <a href="<?= BASE_URL ?>/document-templates" class="btn btn-subtle px-4 py-2">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Tampilkan Semua Template
                </a>
            <?php elseif (!empty($isAdmin)): ?>
                <a href="<?= BASE_URL ?>/document-templates/create" class="btn btn-pnj-primary px-4 py-2">
                    <i class="bi bi-plus-lg me-1"></i> Tambah Template Pertama
                </a>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>
    <div class="card-clean overflow-hidden">
        <div class="table-responsive">
            <table class="table table-minimal table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 48px;">#</th>
                        <th>Nama Template &amp; Keterangan</th>
                        <th style="width: 120px;">Format</th>
                        <th style="width: 120px;">Ukuran</th>
                        <th style="width: 185px;">Diperbarui</th>
                        <th class="text-end" style="width: <?= !empty($isAdmin) ? '230px' : '150px' ?>;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($templates as $idx => $tpl): ?>
                        <tr>
                            <td class="text-center text-muted small"><?= $idx + 1 ?></td>
                            <td>
                                <div class="d-flex align-items-start gap-2.5">
                                    <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 mt-0.5"
                                         style="width: 36px; height: 36px; background: #eff6ff; color: #1d4ed8; border: 1px solid #dbeafe;">
                                        <i class="bi bi-file-earmark-word-fill fs-5"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="fw-bold text-dark">
                                            <?= htmlspecialchars($tpl['name']) ?>
                                        </div>
                                        <?php if (!empty($tpl['description'])): ?>
                                            <div class="text-muted small mt-0.5">
                                                <?= nl2br(htmlspecialchars($tpl['description'])) ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="text-muted mt-1" style="font-size: 0.74rem;">
                                            <i class="bi bi-paperclip me-1"></i><?= htmlspecialchars($tpl['file_name_original']) ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge fw-semibold px-2.5 py-1"
                                      style="background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe;">
                                    DOCX
                                </span>
                            </td>
                            <td class="small text-muted fw-medium">
                                <?= htmlspecialchars($formatBytes((int) ($tpl['file_size'] ?? 0))) ?>
                            </td>
                            <td class="small text-muted">
                                <div><?= date('d M Y, H:i', strtotime($tpl['updated_at'])) ?> WIB</div>
                                <?php if (!empty($tpl['updated_by_name'])): ?>
                                    <div style="font-size: 0.72rem;">oleh <?= htmlspecialchars($tpl['updated_by_name']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex align-items-center gap-1.5">
                                    <a href="<?= BASE_URL ?>/document-templates/<?= htmlspecialchars($tpl['id']) ?>/download"
                                       class="btn btn-pnj-primary btn-sm py-1 px-2.5 d-inline-flex align-items-center gap-1"
                                       title="Unduh berkas Word (.docx)">
                                        <i class="bi bi-download"></i>
                                        <span>Unduh</span>
                                    </a>

                                    <?php if (!empty($isAdmin)): ?>
                                        <a href="<?= BASE_URL ?>/document-templates/<?= htmlspecialchars($tpl['id']) ?>/edit"
                                           class="btn btn-subtle btn-sm py-1 px-2"
                                           title="Ubah Template">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <button type="button"
                                                class="btn btn-subtle btn-sm text-danger border-danger-subtle py-1 px-2 btn-delete-template"
                                                data-bs-toggle="modal"
                                                data-bs-target="#deleteTemplateModal"
                                                data-template-name="<?= htmlspecialchars($tpl['name']) ?>"
                                                data-delete-url="<?= BASE_URL ?>/document-templates/<?= htmlspecialchars($tpl['id']) ?>/delete"
                                                title="Hapus Permanen Template">
                                            <i class="bi bi-trash3"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($isAdmin)): ?>
    <!-- Modal Konfirmasi Hard Delete (Admin) -->
    <div class="modal fade" id="deleteTemplateModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header py-3 px-4 border-bottom">
                    <h6 class="modal-title fw-bold text-danger d-flex align-items-center gap-2 mb-0">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <span>Hapus Permanen Template</span>
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-dark mb-2">
                        Apakah Anda yakin ingin menghapus template dokumen <strong id="deleteTemplateNameText"></strong>?
                    </p>
                    <div class="alert alert-danger border-0 py-2 px-3 small mb-0">
                        <i class="bi bi-info-circle-fill me-1"></i>
                        Penghapusan bersifat <strong>permanen (hard delete)</strong>: data template serta berkas fisik <code>.docx</code> akan dihapus dari server dan tidak dapat dikembalikan.
                    </div>
                </div>
                <div class="modal-footer py-2.5 px-4 border-top">
                    <button type="button" class="btn btn-subtle btn-sm" data-bs-dismiss="modal">Batal</button>
                    <form id="deleteTemplateForm" action="" method="POST" class="m-0">
                        <button type="submit" id="btnConfirmHardDelete" class="btn btn-danger btn-sm px-3 d-inline-flex align-items-center gap-1.5">
                            <i class="bi bi-trash3"></i>
                            <span>Ya, Hapus Permanen</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const deleteForm = document.getElementById('deleteTemplateForm');
            const deleteNameEl = document.getElementById('deleteTemplateNameText');
            const confirmBtn = document.getElementById('btnConfirmHardDelete');

            document.querySelectorAll('.btn-delete-template').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const tplName = btn.getAttribute('data-template-name') || 'Template';
                    const deleteUrl = btn.getAttribute('data-delete-url') || '';
                    deleteNameEl.textContent = '"' + tplName + '"';
                    deleteForm.setAttribute('action', deleteUrl);
                });
            });

            if (deleteForm && confirmBtn) {
                deleteForm.addEventListener('submit', function () {
                    confirmBtn.disabled = true;
                    confirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span><span>Menghapus...</span>';
                });
            }
        });
    </script>
<?php endif; ?>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>

