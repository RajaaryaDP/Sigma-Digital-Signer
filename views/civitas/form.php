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
                            2. Penugasan, Jurusan &amp; Program Studi
                        </div>

                        <?php
                            $selectedDeptId   = $values['raw_department_id'] ?? ($values['department_id'] ?? '');
                            $selectedProdiIds = array_map('strval', $values['study_program_ids'] ?? []);
                            $deptList         = $departmentsWithProdi ?? [];
                        ?>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Pilih Master Jurusan <small class="text-muted">(Disarankan untuk Dosen/Mahasiswa)</small></label>
                                <select name="department_id" id="civitasDepartmentId"
                                        class="form-select <?= isset($errors['department_id']) ? 'is-invalid' : '' ?>">
                                    <option value="">-- Unit Kerja Non-Jurusan / Isi Manual --</option>
                                    <?php foreach ($deptList as $dept): ?>
                                        <option value="<?= htmlspecialchars($dept['id']) ?>"
                                                data-dept-name="<?= htmlspecialchars($dept['name']) ?>"
                                                <?= ($selectedDeptId === $dept['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($dept['name']) ?><?= !empty($dept['code']) ? ' (' . htmlspecialchars($dept['code']) . ')' : '' ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['department_id'])): ?>
                                    <div class="invalid-feedback small"><?= htmlspecialchars($errors['department_id']) ?></div>
                                <?php endif; ?>
                                <div class="form-text small">
                                    Kelola daftar jurusan &amp; prodi di menu <a href="<?= BASE_URL ?>/departments" class="text-success fw-medium">Jurusan &amp; Prodi</a>.
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Nama Jurusan / Unit Kerja <span class="text-danger">*</span></label>
                                <input type="text" name="department" id="civitasDepartmentText"
                                       class="form-control <?= isset($errors['department']) ? 'is-invalid' : '' ?>" 
                                       placeholder="Contoh: Teknik Informatika dan Komputer atau Subbag Akademik"
                                       value="<?= htmlspecialchars($values['department'] ?? '') ?>" required>
                                <?php if (isset($errors['department'])): ?>
                                    <div class="invalid-feedback small"><?= htmlspecialchars($errors['department']) ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="col-12">
                                <div class="border rounded-3 p-3 bg-light bg-opacity-50" id="prodiAssignmentCard">
                                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                                        <div>
                                            <label class="form-label mb-0 fw-semibold">
                                                Program Studi (Prodi) yang Diampu / Diikuti
                                            </label>
                                            <div class="text-muted small" id="prodiHelpText">
                                                Untuk <strong>Dosen</strong>, dapat mencentang <strong>lebih dari 1 Program Studi</strong> tempat mengajar.
                                            </div>
                                        </div>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle" id="selectedProdiCountBadge">
                                            <?= count($selectedProdiIds) ?> Prodi dipilih
                                        </span>
                                    </div>

                                    <?php if (empty($deptList)): ?>
                                        <div class="text-muted small py-2">
                                            Belum ada master Jurusan/Prodi. <a href="<?= BASE_URL ?>/departments/create" class="text-success">Tambah Jurusan &amp; Prodi</a> terlebih dahulu.
                                        </div>
                                    <?php else: ?>
                                        <div class="row g-2 mt-1">
                                            <?php foreach ($deptList as $dept): ?>
                                                <?php $prodiItems = $dept['study_programs'] ?? []; ?>
                                                <?php if (empty($prodiItems)) continue; ?>
                                                <div class="col-12 dept-prodi-group" data-dept-id="<?= htmlspecialchars($dept['id']) ?>">
                                                    <div class="border rounded-2 bg-white p-2 px-3">
                                                        <div class="fw-semibold small text-success mb-2 d-flex align-items-center gap-2">
                                                            <i class="bi bi-building"></i>
                                                            <span><?= htmlspecialchars($dept['name']) ?></span>
                                                            <?php if (!empty($dept['code'])): ?>
                                                                <span class="badge bg-light text-secondary border"><?= htmlspecialchars($dept['code']) ?></span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="row g-2">
                                                            <?php foreach ($prodiItems as $pr): ?>
                                                                <?php $isChecked = in_array((string) $pr['id'], $selectedProdiIds, true); ?>
                                                                <div class="col-md-6">
                                                                    <label class="d-flex align-items-start gap-2 p-2 rounded border prodi-check-item <?= $isChecked ? 'border-success bg-success bg-opacity-10' : 'bg-light bg-opacity-25' ?>" style="cursor: pointer;">
                                                                        <input type="checkbox"
                                                                               name="study_program_ids[]"
                                                                               value="<?= htmlspecialchars($pr['id']) ?>"
                                                                               data-dept-id="<?= htmlspecialchars($dept['id']) ?>"
                                                                               class="form-check-input mt-1 flex-shrink-0 prodi-checkbox"
                                                                               <?= $isChecked ? 'checked' : '' ?>>
                                                                        <span class="small lh-sm">
                                                                            <span class="fw-medium text-dark d-block">
                                                                                <?= htmlspecialchars($pr['name']) ?>
                                                                            </span>
                                                                            <span class="text-muted" style="font-size: 0.75rem;">
                                                                                <?= !empty($pr['degree']) ? htmlspecialchars($pr['degree']) : 'Prodi' ?><?= !empty($pr['code']) ? ' &bull; ' . htmlspecialchars($pr['code']) : '' ?>
                                                                            </span>
                                                                        </span>
                                                                    </label>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="col-12">
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    const typeSelect   = document.querySelector('select[name="type"]');
    const deptSelect   = document.getElementById('civitasDepartmentId');
    const deptText     = document.getElementById('civitasDepartmentText');
    const prodiHelp    = document.getElementById('prodiHelpText');
    const countBadge   = document.getElementById('selectedProdiCountBadge');
    const checkboxes   = document.querySelectorAll('.prodi-checkbox');
    const deptGroups   = document.querySelectorAll('.dept-prodi-group');

    function syncDepartmentText() {
        if (!deptSelect || !deptText) return;
        const opt = deptSelect.options[deptSelect.selectedIndex];
        const deptName = opt ? opt.getAttribute('data-dept-name') : '';
        if (deptName) {
            deptText.value = deptName;
        }
        highlightDeptGroups();
    }

    function highlightDeptGroups() {
        const activeDept = deptSelect ? deptSelect.value : '';
        deptGroups.forEach(group => {
            const box = group.firstElementChild;
            if (!box) return;
            if (activeDept && group.getAttribute('data-dept-id') === activeDept) {
                box.classList.add('border-success');
            } else {
                box.classList.remove('border-success');
            }
        });
    }

    function updateProdiStats() {
        let checkedCount = 0;
        checkboxes.forEach(cb => {
            const label = cb.closest('.prodi-check-item');
            if (cb.checked) {
                checkedCount++;
                if (label) {
                    label.classList.add('border-success', 'bg-success', 'bg-opacity-10');
                    label.classList.remove('bg-light', 'bg-opacity-25');
                }
            } else {
                if (label) {
                    label.classList.remove('border-success', 'bg-success', 'bg-opacity-10');
                    label.classList.add('bg-light', 'bg-opacity-25');
                }
            }
        });
        if (countBadge) {
            countBadge.textContent = checkedCount + ' Prodi dipilih';
        }
    }

    function updateProdiHintByType() {
        if (!typeSelect || !prodiHelp) return;
        if (typeSelect.value === 'dosen') {
            prodiHelp.innerHTML = 'Untuk <strong>Dosen</strong>, Anda dapat mencentang <strong>lebih dari 1 Program Studi</strong> tempat mengajar.';
        } else if (typeSelect.value === 'mahasiswa') {
            prodiHelp.innerHTML = 'Pilih Program Studi asal <strong>Mahasiswa</strong>.';
        } else {
            prodiHelp.innerHTML = 'Opsional untuk <strong>Tenaga Kependidikan</strong> jika ditugaskan pada program studi tertentu.';
        }
    }

    if (deptSelect) {
        deptSelect.addEventListener('change', syncDepartmentText);
    }
    if (typeSelect) {
        typeSelect.addEventListener('change', updateProdiHintByType);
    }
    checkboxes.forEach(cb => {
        cb.addEventListener('change', function () {
            updateProdiStats();
            // Jika master jurusan belum dipilih & user mencentang prodi, bantu pilih jurusan induknya otomatis
            if (cb.checked && deptSelect && !deptSelect.value) {
                const prodiDeptId = cb.getAttribute('data-dept-id');
                if (prodiDeptId) {
                    deptSelect.value = prodiDeptId;
                    syncDepartmentText();
                }
            }
        });
    });

    highlightDeptGroups();
    updateProdiStats();
    updateProdiHintByType();
});
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
