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
                        <i class="bi bi-mortarboard"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold text-dark mb-0"><?= $isEdit ? 'Ubah Program Studi' : 'Tambah Program Studi Baru' ?></h5>
                        <small class="text-muted">Program Studi berada di bawah Jurusan dan dapat diampu oleh lebih dari satu Dosen</small>
                    </div>
                </div>
            </div>

            <div class="card-clean-body p-4">
                <form action="<?= $isEdit ? BASE_URL . '/prodi/' . htmlspecialchars($prodi['id']) . '/update' : BASE_URL . '/prodi/store' ?>"
                      method="POST">

                    <div class="mb-3">
                        <label for="department_id" class="form-label fw-semibold">
                            Jurusan Induk <span class="text-danger">*</span>
                        </label>
                        <select id="department_id" name="department_id" class="form-select" required>
                            <option value="">-- Pilih Jurusan --</option>
                            <?php $selectedDeptId = $old['department_id'] ?? ''; ?>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?= htmlspecialchars($dept['id']) ?>"
                                    <?= ($selectedDeptId === $dept['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($dept['name']) ?><?= !empty($dept['code']) ? ' (' . htmlspecialchars($dept['code']) . ')' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="degree" class="form-label fw-semibold">
                                Jenjang <span class="text-muted fw-normal">(Opsional)</span>
                            </label>
                            <?php $selectedDegree = $old['degree'] ?? ''; ?>
                            <select id="degree" name="degree" class="form-select">
                                <option value="">-- Pilih Jenjang --</option>
                                <?php foreach ($degrees as $deg): ?>
                                    <option value="<?= htmlspecialchars($deg) ?>"
                                        <?= ($selectedDegree === $deg) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($deg) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="code" class="form-label fw-semibold">
                                Kode Prodi <span class="text-muted fw-normal">(Opsional)</span>
                            </label>
                            <input type="text"
                                   id="code"
                                   name="code"
                                   class="form-control text-uppercase"
                                   maxlength="30"
                                   placeholder="Contoh: TI, TMD, TMJ, RKS"
                                   value="<?= htmlspecialchars($old['code'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="name" class="form-label fw-semibold">
                            Nama Program Studi <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                               id="name"
                               name="name"
                               class="form-control"
                               maxlength="150"
                               required
                               placeholder="Contoh: Teknik Informatika"
                               value="<?= htmlspecialchars($old['name'] ?? '') ?>">
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                        <a href="<?= BASE_URL ?>/departments" class="btn btn-subtle px-4">Batal</a>
                        <button type="submit" class="btn btn-pnj-primary px-4 d-inline-flex align-items-center gap-2">
                            <i class="bi bi-check-lg"></i>
                            <span><?= $isEdit ? 'Simpan Perubahan' : 'Simpan Program Studi' ?></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
