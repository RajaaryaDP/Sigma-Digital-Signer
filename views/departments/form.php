<?php require_once __DIR__ . '/../layout/header.php'; ?>

<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="mb-3">
            <a href="<?= BASE_URL ?>/departments" class="text-decoration-none small d-inline-flex align-items-center gap-2" style="color: var(--text-muted);">
                <i class="bi bi-arrow-left"></i>
                <span>Kembali ke Master Jurusan &amp; Prodi</span>
            </a>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger border-0 shadow-sm mb-3">
                <div class="fw-semibold mb-1"><i class="bi bi-exclamation-triangle me-1"></i> Periksa kembali input Anda:</div>
                <ul class="mb-0 ps-3 small">
                    <?php foreach ($errors as $err): ?>
                        <li><?= htmlspecialchars($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="card-sigma">
            <div class="card-sigma-header py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width: 36px; height: 36px; background: var(--sigma-tint-100); color: var(--sigma-purple-700);">
                        <i class="bi bi-building"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-0" style="color: var(--text-heading);"><?= $isEdit ? 'Ubah Data Jurusan' : 'Tambah Jurusan Baru' ?></h5>
                        <small style="color: var(--text-muted);">Jurusan menjadi unit induk Program Studi serta acuan filter penandatangan</small>
                    </div>
                </div>
            </div>

            <div class="card-sigma-body p-4">
                <form action="<?= $isEdit ? BASE_URL . '/departments/' . htmlspecialchars($department['id']) . '/update' : BASE_URL . '/departments/store' ?>"
                      method="POST">

                    <div class="mb-3">
                        <label for="code" class="sigma-form-label">
                            Kode Jurusan <span class="fw-normal" style="color: var(--text-muted);">(Opsional)</span>
                        </label>
                        <input type="text"
                               id="code"
                               name="code"
                               class="form-control sigma-input text-uppercase"
                               maxlength="30"
                               placeholder="Contoh: TIK, TE, TM"
                               value="<?= htmlspecialchars($old['code'] ?? '') ?>">
                        <div class="form-text small" style="color: var(--text-muted);">Huruf kapital, angka, strip (-), atau underscore (_). Maksimal 30 karakter.</div>
                    </div>

                    <div class="mb-4">
                        <label for="name" class="sigma-form-label">
                            Nama Jurusan <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                               id="name"
                               name="name"
                               class="form-control sigma-input"
                               maxlength="150"
                               required
                               placeholder="Contoh: Teknik Informatika dan Komputer"
                               value="<?= htmlspecialchars($old['name'] ?? '') ?>">
                        <?php if ($isEdit): ?>
                            <div class="form-text small" style="color: var(--sigma-info-text);">
                                <i class="bi bi-info-circle me-1"></i>
                                Mengubah nama jurusan juga memperbarui nama jurusan pada data civitas yang terhubung.
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-3 border-top" style="border-color: var(--border-subtle) !important;">
                        <a href="<?= BASE_URL ?>/departments" class="btn-sigma-outline px-4 text-decoration-none">Batal</a>
                        <button type="submit" class="btn-sigma-navy px-4">
                            <i class="bi bi-check-lg"></i>
                            <span><?= $isEdit ? 'Simpan Perubahan' : 'Simpan Jurusan' ?></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
