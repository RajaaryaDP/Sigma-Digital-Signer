<?php
$title = 'Template Tanda Tangan Saya — SIGMA';
require_once __DIR__ . '/../layout/header.php';
?>

<!-- Page Header & Action Bar -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">
            <i class="bi bi-pen me-2 text-success"></i>Template Tanda Tangan Digital
        </h4>
        <p class="text-muted small mb-0">
            Kelola template tanda tangan pribadi Anda untuk menandatangani dokumen akademik secara cepat, resmi, dan terverifikasi.
        </p>
    </div>
    <div>
        <a href="<?= BASE_URL ?>/user-signatures/create" class="btn btn-pnj-primary d-inline-flex align-items-center gap-2 shadow-sm">
            <i class="bi bi-plus-circle"></i>
            <span>Buat Template Baru</span>
        </a>
    </div>
</div>

<!-- Info Alert Callout -->
<div class="card-clean mb-4 border-start border-4 border-success">
    <div class="card-clean-body py-3 px-4 d-flex align-items-start gap-3">
        <div class="text-success fs-4 mt-1">
            <i class="bi bi-shield-check"></i>
        </div>
        <div>
            <div class="fw-semibold text-dark small">Tanda Tangan Tersimpan & Keabsahan Digital</div>
            <div class="text-muted small">
                Template yang ditandai sebagai <strong>Utama / Default</strong> akan otomatis terpilih di panel penandatanganan dokumen. Seluruh tanda tangan yang dibubuhkan pada dokumen final akan dipadukan dengan QR Code verifikasi biodata resmi civitas Anda.
            </div>
        </div>
    </div>
</div>

<!-- Signature Cards Grid / Empty State -->
<?php if (empty($signatures)): ?>
    <!-- Empty State -->
    <div class="card-clean p-5 text-center my-4">
        <div class="rounded-circle d-inline-flex align-items-center justify-content-center mx-auto mb-3" 
             style="width: 72px; height: 72px; background: var(--pnj-green-50); color: var(--pnj-green-800); font-size: 2rem;">
            <i class="bi bi-pen"></i>
        </div>
        <h5 class="fw-bold text-dark mb-2">Belum Ada Template Tanda Tangan</h5>
        <p class="text-muted small mx-auto mb-4" style="max-width: 520px;">
            Anda belum memiliki template tanda tangan tersimpan. Buat sekarang dengan menggambar langsung di atas kanvas digital atau mengunggah gambar PNG transparan untuk mempermudah penandatanganan berkas.
        </p>
        <div>
            <a href="<?= BASE_URL ?>/user-signatures/create" class="btn btn-pnj-primary px-4 py-2">
                <i class="bi bi-plus-lg me-1"></i> Buat Template Sekarang
            </a>
        </div>
    </div>
<?php else: ?>
    <!-- Grid of Templates -->
    <div class="row g-4">
        <?php foreach ($signatures as $sig): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card-clean h-100 d-flex flex-column <?= ($sig['is_default'] == 1) ? 'border-success shadow-sm' : '' ?>" style="overflow: hidden;">
                    
                    <!-- Top Preview with Checkerboard Grid for Transparency -->
                    <div class="sig-card-preview checkerboard-bg position-relative">
                        <img src="<?= BASE_URL ?>/user-signatures/<?= htmlspecialchars($sig['id']) ?>/preview" 
                             alt="<?= htmlspecialchars($sig['label'] ?: 'Tanda Tangan') ?>" 
                             loading="lazy">

                        <?php if ($sig['is_default'] == 1): ?>
                            <div class="position-absolute top-0 start-0 m-2">
                                <span class="badge bg-success shadow-sm d-inline-flex align-items-center gap-1 px-2.5 py-1.5" style="font-size: 0.72rem; font-weight: 600;">
                                    <i class="bi bi-star-fill"></i> UTAMA / DEFAULT
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Card Body -->
                    <div class="card-clean-body p-3 flex-grow-1 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <h6 class="fw-bold text-dark mb-0 text-truncate" title="<?= htmlspecialchars($sig['label'] ?: 'Tanda Tangan Pribadi') ?>">
                                    <?= htmlspecialchars($sig['label'] ?: 'Tanda Tangan Pribadi') ?>
                                </h6>
                                <button type="button" class="btn btn-link text-muted p-0 text-decoration-none" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#editModal-<?= htmlspecialchars($sig['id']) ?>" 
                                        title="Ubah Nama Label">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                            </div>
                            <div class="text-muted small" style="font-size: 0.75rem;">
                                <i class="bi bi-clock-history me-1"></i>Dibuat: <?= date('d M Y, H:i', strtotime($sig['created_at'])) ?> WIB
                            </div>
                        </div>

                        <!-- Card Actions Footer -->
                        <div class="pt-3 mt-3 border-top d-flex align-items-center justify-content-between gap-2">
                            <?php if ($sig['is_default'] == 1): ?>
                                <span class="text-success small fw-semibold d-inline-flex align-items-center gap-1">
                                    <i class="bi bi-check-circle-fill"></i> Terpilih Otomatis
                                </span>
                            <?php else: ?>
                                <form action="<?= BASE_URL ?>/user-signatures/<?= htmlspecialchars($sig['id']) ?>/update" method="POST" class="m-0">
                                    <input type="hidden" name="action_type" value="set_default">
                                    <button type="submit" class="btn btn-outline-success btn-sm d-inline-flex align-items-center gap-1 py-1 px-2.5" style="font-size: 0.78rem;">
                                        <i class="bi bi-star"></i> Jadikan Utama
                                    </button>
                                </form>
                            <?php endif; ?>

                            <!-- Action Buttons -->
                            <div class="d-flex align-items-center gap-1">
                                <button type="button" class="btn btn-subtle btn-sm text-danger border-danger-subtle py-1 px-2" 
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
            <div class="modal fade" id="editModal-<?= htmlspecialchars($sig['id']) ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-sm">
                    <div class="modal-content border-0 shadow">
                        <form action="<?= BASE_URL ?>/user-signatures/<?= htmlspecialchars($sig['id']) ?>/update" method="POST">
                            <input type="hidden" name="action_type" value="update_label">
                            <div class="modal-header py-2.5 px-3 border-bottom">
                                <h6 class="modal-title fw-bold text-dark">Ubah Label Template</h6>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                            </div>
                            <div class="modal-body p-3">
                                <label class="form-label small fw-semibold">Nama / Label Template</label>
                                <input type="text" name="label" class="form-control form-control-sm" 
                                       value="<?= htmlspecialchars($sig['label'] ?: 'Tanda Tangan Pribadi') ?>" required maxlength="100">
                                <small class="text-muted d-block mt-1" style="font-size: 0.72rem;">Beri nama pengenal seperti "Tanda Tangan Utama" atau "Paraf Singkat".</small>
                            </div>
                            <div class="modal-footer py-2 px-3 border-top">
                                <button type="button" class="btn btn-subtle btn-sm" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-pnj-primary btn-sm">Simpan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Modal Konfirmasi Hapus -->
            <div class="modal fade" id="deleteModal-<?= htmlspecialchars($sig['id']) ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow">
                        <div class="modal-header py-3 px-4 border-bottom">
                            <h6 class="modal-title fw-bold text-danger d-flex align-items-center gap-2">
                                <i class="bi bi-exclamation-triangle-fill"></i> Konfirmasi Hapus Template
                            </h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                        </div>
                        <div class="modal-body p-4">
                            <p class="text-dark mb-2">
                                Apakah Anda yakin ingin menghapus template tanda tangan <strong>"<?= htmlspecialchars($sig['label'] ?: 'Tanda Tangan Pribadi') ?>"</strong>?
                            </p>
                            <p class="text-muted small mb-0">
                                Template tanda tangan ini tidak akan dapat dipilih kembali untuk dokumen baru. Dokumen lama yang sudah ditandatangani tidak akan terpengaruh.
                            </p>
                        </div>
                        <div class="modal-footer py-2.5 px-4 border-top">
                            <button type="button" class="btn btn-subtle btn-sm" data-bs-dismiss="modal">Batal</button>
                            <form action="<?= BASE_URL ?>/user-signatures/<?= htmlspecialchars($sig['id']) ?>/delete" method="POST" class="m-0">
                                <button type="submit" class="btn btn-danger btn-sm px-3">
                                    <i class="bi bi-trash3 me-1"></i> Ya, Hapus
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
