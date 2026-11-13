<?php require_once __DIR__ . '/../layout/header.php'; ?>

<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="mb-3">
            <a href="<?= BASE_URL ?>/departments" class="text-decoration-none text-muted small d-inline-flex align-items-center gap-1">
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

        <div class="card-clean">
            <div class="card-clean-header py-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle d-flex align-items-center justify-content-center"
                         style="width: 32px; height: 32px; background: var(--pnj-green-50); color: var(--pnj-green-800);">
                        <i class="bi bi-building"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold text-dark mb-0"><?= $isEdit ? 'Ubah Data Jurusan' : 'Tambah Jurusan Baru' ?></h5>
                        <small class="text-muted">Jurusan menjadi unit induk Program Studi serta acuan filter penandatangan</small>
                    </div>
                </div>
            </div>

            <div class="card-clean-body p-4">
                <form action="<?= $isEdit ? BASE_URL . '/departments/' . htmlspecialchars($department['id']) . '/update' : BASE_URL . '/departments/store' ?>"
                      method="POST">

                    <div class="mb-3">
                        <label for="code" class="form-label fw-semibold">
                            Kode Jurusan <span class="text-muted fw-normal">(Opsional)</span>
                        </label>
                        <input type="text"
                               id="code"
                               name="code"
                               class="form-control text-uppercase"
                               maxlength="30"
                               placeholder="Contoh: TIK, TE, TM"
                               value="<?= htmlspecialchars($old['code'] ?? '') ?>">
                        <div class="form-text">Huruf kapital, angka, strip (-), atau underscore (_). Maksimal 30 karakter.</div>
                    </div>

                    <div class="mb-4">
                        <label for="name" class="form-label fw-semibold">
                            Nama Jurusan <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                               id="name"
                               name="name"
                               class="form-control"
                               maxlength="150"
                               required
                               placeholder="Contoh: Teknik Informatika dan Komputer"
                               value="<?= htmlspecialchars($old['name'] ?? '') ?>">
                        <?php if ($isEdit): ?>
                            <div class="form-text text-info-emphasis">
                                <i class="bi bi-info-circle me-1"></i>
                                Mengubah nama jurusan juga memperbarui nama jurusan pada data civitas yang terhubung.
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                        <a href="<?= BASE_URL ?>/departments" class="btn btn-subtle px-4">Batal</a>
                        <button type="submit" class="btn btn-pnj-primary px-4 d-inline-flex align-items-center gap-2">
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
