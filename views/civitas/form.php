<?php
$title = ($isEdit ? 'Ubah' : 'Tambah') . ' Data Civitas — SIGMA';
require_once __DIR__ . '/../layout/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-9 col-xl-8">
        <!-- Back Link -->
        <div class="mb-3">
            <a href="<?= BASE_URL ?>/civitas" class="text-decoration-none text-muted small d-inline-flex align-items-center gap-1.5">
                <i class="bi bi-arrow-left"></i>
                <span>Kembali ke Master Data Civitas</span>
            </a>
        </div>

        <!-- Form Card -->
        <div class="card-clean mb-4">
            <div class="card-clean-header py-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle d-flex align-items-center justify-content-center" 
                         style="width: 32px; height: 32px; background: var(--pnj-green-50); color: var(--pnj-green-800);">
                        <i class="bi <?= $isEdit ? 'bi-pencil-square' : 'bi-person-plus-fill' ?>"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold text-dark mb-0"><?= $isEdit ? 'Ubah Data Civitas' : 'Tambah Civitas Kampus' ?></h5>
                        <small class="text-muted">Isi informasi identitas resmi civitas untuk keperluan tanda tangan digital</small>
                    </div>
                </div>
            </div>

            <div class="card-clean-body p-4">
                <form action="<?= $isEdit ? BASE_URL . '/civitas/' . htmlspecialchars($id) . '/update' : BASE_URL . '/civitas/store' ?>" method="POST">

                    <!-- Section 1: Kategori & Identitas Utama -->
                    <div class="mb-4">
                        <div class="fw-semibold text-dark small text-uppercase mb-3 pb-1 border-bottom" style="letter-spacing: 0.5px;">
                            1. Kategori & Identitas Utama
                        </div>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Kategori Civitas <span class="text-danger">*</span></label>
                                <select name="type" class="form-select <?= isset($errors['type']) ? 'is-invalid' : '' ?>" required>
                                    <option value="mahasiswa" <?= ($values['type'] ?? '') === 'mahasiswa' ? 'selected' : '' ?>>Mahasiswa</option>
                                    <option value="dosen" <?= ($values['type'] ?? '') === 'dosen' ? 'selected' : '' ?>>Dosen</option>
                                    <option value="tendik" <?= ($values['type'] ?? '') === 'tendik' ? 'selected' : '' ?>>Tenaga Kependidikan</option>
                                </select>
                                <?php if (isset($errors['type'])): ?>
                                    <div class="invalid-feedback small"><?= htmlspecialchars($errors['type']) ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-8">
                                <label class="form-label">Nomor Identitas (NIM / NIP) <span class="text-danger">*</span></label>
                                <input type="text" name="identifier" 
                                       class="form-control <?= isset($errors['identifier']) ? 'is-invalid' : '' ?>" 
                                       placeholder="Contoh: 220101001 (NIM) atau 19800101... (NIP)"
                                       value="<?= htmlspecialchars($values['identifier'] ?? '') ?>" required>
                                <?php if (isset($errors['identifier'])): ?>
                                    <div class="invalid-feedback small"><?= htmlspecialchars($errors['identifier']) ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Nama Lengkap & Gelar Resmi <span class="text-danger">*</span></label>
                                <input type="text" name="name" 
                                       class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" 
                                       placeholder="Contoh: Prof. Dr. Ir. Siti Aminah, M.Sc. atau Ahmad Fauzi"
                                       value="<?= htmlspecialchars($values['name'] ?? '') ?>" required>
                                <?php if (isset($errors['name'])): ?>
                                    <div class="invalid-feedback small"><?= htmlspecialchars($errors['name']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Section 2: Penugasan & Unit Kerja -->
                    <div class="mb-4">
                        <div class="fw-semibold text-dark small text-uppercase mb-3 pb-1 border-bottom" style="letter-spacing: 0.5px;">
                            2. Penugasan & Unit Kerja
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Jurusan / Program Studi / Unit <span class="text-danger">*</span></label>
                                <input type="text" name="department" 
                                       class="form-control <?= isset($errors['department']) ? 'is-invalid' : '' ?>" 
                                       placeholder="Contoh: Teknik Informatika (D4)"
                                       value="<?= htmlspecialchars($values['department'] ?? '') ?>" required>
                                <?php if (isset($errors['department'])): ?>
                                    <div class="invalid-feedback small"><?= htmlspecialchars($errors['department']) ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Jabatan / Peran Resmi <span class="text-danger">*</span></label>
                                <input type="text" name="position" 
                                       class="form-control <?= isset($errors['position']) ? 'is-invalid' : '' ?>" 
                                       placeholder="Contoh: Ketua Jurusan, Dosen Pembimbing, Mahasiswa Aktif"
                                       value="<?= htmlspecialchars($values['position'] ?? '') ?>" required>
                                <?php if (isset($errors['position'])): ?>
                                    <div class="invalid-feedback small"><?= htmlspecialchars($errors['position']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Section 3: Kontak Institusi -->
                    <div class="mb-4">
                        <div class="fw-semibold text-dark small text-uppercase mb-3 pb-1 border-bottom" style="letter-spacing: 0.5px;">
                            3. Kontak Resmi
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Alamat Email Institusi <span class="text-danger">*</span></label>
                                <input type="email" name="email" 
                                       class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" 
                                       placeholder="nama@sigma.local atau nama@pnj.ac.id"
                                       value="<?= htmlspecialchars($values['email'] ?? '') ?>" required>
                                <?php if (isset($errors['email'])): ?>
                                    <div class="invalid-feedback small"><?= htmlspecialchars($errors['email']) ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Nomor Kontak / WhatsApp <small class="text-muted">(Opsional)</small></label>
                                <input type="text" name="phone" 
                                       class="form-control <?= isset($errors['phone']) ? 'is-invalid' : '' ?>" 
                                       placeholder="Contoh: 081234567890"
                                       value="<?= htmlspecialchars($values['phone'] ?? '') ?>">
                                <?php if (isset($errors['phone'])): ?>
                                    <div class="invalid-feedback small"><?= htmlspecialchars($errors['phone']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                        <a href="<?= BASE_URL ?>/civitas" class="btn btn-subtle px-4">
                            Batal
                        </a>
                        <button type="submit" class="btn btn-pnj-primary px-4 d-inline-flex align-items-center gap-2">
                            <i class="bi <?= $isEdit ? 'bi-check-lg' : 'bi-plus-circle' ?>"></i>
                            <span><?= $isEdit ? 'Perbarui Data' : 'Simpan Data Civitas' ?></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
