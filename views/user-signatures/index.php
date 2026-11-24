<?php
$title = 'Template Tanda Tangan Saya — SIGMA';
require_once __DIR__ . '/../layout/header.php';
?>

<!-- Page Header & Action Bar -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <div class="text-uppercase fw-bold mb-1" style="font-size: 0.72rem; letter-spacing: 0.06em; color: var(--sigma-purple-600);">
            Tanda Tangan Digital Pribadi
        </div>
        <h4 class="fw-bold mb-1" style="color: var(--text-heading);">
            <i class="bi bi-pen me-2 text-sigma-purple"></i>Template Tanda Tangan Digital
        </h4>
        <p class="small mb-0" style="color: var(--text-muted);">
            Template yang ditandai Default otomatis terpilih saat menandatangani.
        </p>
    </div>
    <div>
        <a href="<?= BASE_URL ?>/user-signatures/create" class="btn-sigma-navy text-decoration-none">
            <i class="bi bi-plus-circle"></i>
            <span>Buat Template Baru</span>
        </a>
    </div>
</div>

<!-- Signature Cards Grid / Empty State -->
<?php if (empty($signatures)): ?>
    <!-- Empty State -->
    <div class="card-sigma p-5 text-center my-4">
        <div class="rounded-circle d-inline-flex align-items-center justify-content-center mx-auto mb-3"
             style="width: 72px; height: 72px; background: var(--sigma-tint-100); color: var(--sigma-purple-700); font-size: 2rem;">
            <i class="bi bi-pen"></i>
        </div>
        <h5 class="fw-bold mb-2" style="color: var(--text-heading);">Belum Ada Template Tanda Tangan</h5>
        <p class="small mx-auto mb-4" style="max-width: 520px; color: var(--text-muted);">
            Gambar tanda tangan atau unggah PNG transparan.
        </p>
        <div>
            <a href="<?= BASE_URL ?>/user-signatures/create" class="btn-sigma-navy px-4 py-2 text-decoration-none">
                <i class="bi bi-plus-lg"></i> Buat Template Sekarang
            </a>
        </div>
    </div>
<?php else: ?>
    <!-- Grid of Templates -->
    <div class="row g-4">
        <?php foreach ($signatures as $sig): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card-sigma h-100 d-flex flex-column <?= ($sig['is_default'] == 1) ? 'sigma-card-active' : '' ?>" style="overflow: hidden;">

                    <!-- Top Preview with Checkerboard Grid for Transparency -->
                    <div class="sig-card-preview checkerboard-bg position-relative">
                        <img src="<?= BASE_URL ?>/user-signatures/<?= htmlspecialchars($sig['id']) ?>/preview"
                             alt="<?= htmlspecialchars($sig['label'] ?: 'Tanda Tangan') ?>"
                             loading="lazy">

                        <?php if ($sig['is_default'] == 1): ?>
                            <div class="position-absolute top-0 start-0 m-2">
                                <span class="badge-sigma badge-sigma-success shadow-sm">
                                    <i class="bi bi-star-fill"></i> UTAMA / DEFAULT
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Card Body -->
                    <div class="card-sigma-body p-3 flex-grow-1 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex align-items-center justify-content-between gap-2 mb-1">
                                <h6 class="fw-bold mb-0 text-truncate" style="color: var(--text-heading);" title="<?= htmlspecialchars($sig['label'] ?: 'Tanda Tangan Pribadi') ?>">
                                    <?= htmlspecialchars($sig['label'] ?: 'Tanda Tangan Pribadi') ?>
                                </h6>
                                <button type="button" class="btn btn-link p-0 text-decoration-none flex-shrink-0"
                                        style="color: var(--text-muted);"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editModal-<?= htmlspecialchars($sig['id']) ?>"
                                        title="Ubah Nama Label">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                            </div>
                            <div class="small" style="font-size: 0.75rem; color: var(--text-muted);">
                                <i class="bi bi-clock-history me-1"></i>Dibuat: <?= date('d M Y, H:i', strtotime($sig['created_at'])) ?> WIB
                            </div>
                        </div>

                        <!-- Card Actions Footer -->
                        <div class="pt-3 mt-3 border-top d-flex align-items-center justify-content-between gap-2" style="border-color: var(--border-subtle) !important;">
                            <?php if ($sig['is_default'] == 1): ?>
                                <span class="small fw-semibold d-inline-flex align-items-center gap-1" style="color: var(--sigma-success-text);">
                                    <i class="bi bi-check-circle-fill"></i> Terpilih Otomatis
                                </span>
                            <?php else: ?>
                                <form action="<?= BASE_URL ?>/user-signatures/<?= htmlspecialchars($sig['id']) ?>/update" method="POST" class="m-0">
                                    <?= Csrf::field() ?>
                                    <input type="hidden" name="action_type" value="set_default">
                                    <button type="submit" class="btn-sigma-outline btn-sigma-sm">
                                        <i class="bi bi-star"></i> Jadikan Utama
                                    </button>
                                </form>
                            <?php endif; ?>

                            <!-- Action Buttons -->
                            <div class="d-flex align-items-center gap-1">
                                <button type="button" class="btn-sigma-outline btn-sigma-sm text-danger"
                                        style="border-color: #fecaca;"
                                        data-bs-toggle="modal"
                                        data-bs-target="#deleteModal-<?= htmlspecialchars($sig['id']) ?>"
                                        title="Hapus Template">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Ubah Label -->
            <div class="modal fade modal-sigma" id="editModal-<?= htmlspecialchars($sig['id']) ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-sm">
                    <div class="modal-content">
                        <form action="<?= BASE_URL ?>/user-signatures/<?= htmlspecialchars($sig['id']) ?>/update" method="POST">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="action_type" value="update_label">
                            <div class="modal-header py-2 px-3">
                                <h6 class="modal-title fw-bold mb-0" style="color: var(--text-heading);">Ubah Label Template</h6>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                            </div>
                            <div class="modal-body p-3">
                                <label class="sigma-form-label">Nama / Label Template</label>
                                <input type="text" name="label" class="form-control form-control-sm sigma-input"
                                       value="<?= htmlspecialchars($sig['label'] ?: 'Tanda Tangan Pribadi') ?>" required maxlength="100">
                                
                            </div>
                            <div class="modal-footer py-2 px-3">
                                <button type="button" class="btn-sigma-outline btn-sigma-sm" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn-sigma-navy btn-sigma-sm">Simpan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Modal Konfirmasi Hapus -->
            <div class="modal fade modal-sigma" id="deleteModal-<?= htmlspecialchars($sig['id']) ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header py-3 px-4">
                            <h6 class="modal-title fw-bold text-danger d-flex align-items-center gap-2 mb-0">
                                <i class="bi bi-exclamation-triangle-fill"></i> Konfirmasi Hapus Template
                            </h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                        </div>
                        <div class="modal-body p-4">
                            <p class="mb-2" style="color: var(--text-heading);">
                                Hapus template <strong>"<?= htmlspecialchars($sig['label'] ?: 'Tanda Tangan Pribadi') ?>"</strong>?
                            </p>
                            <p class="small mb-0" style="color: var(--text-muted);">
                                Dokumen yang sudah ditandatangani tidak terpengaruh.
                            </p>
                        </div>
                        <div class="modal-footer py-2 px-4">
                            <button type="button" class="btn-sigma-outline btn-sigma-sm" data-bs-dismiss="modal">Batal</button>
                            <form action="<?= BASE_URL ?>/user-signatures/<?= htmlspecialchars($sig['id']) ?>/delete" method="POST" class="m-0">
                                <?= Csrf::field() ?>
                                <button type="submit" class="btn-sigma-danger btn-sigma-sm px-3">
                                    <i class="bi bi-trash3"></i> Ya, Hapus
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
