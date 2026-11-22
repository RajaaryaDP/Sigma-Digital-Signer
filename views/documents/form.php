<?php
$title = 'Ajukan Dokumen Baru — SIGMA';
require_once __DIR__ . '/../layout/header.php';

$curType = $values['signature_type'] ?? 'sequential';
$signersData = !empty($postedSigners) ? $postedSigners : [
    ['user_id' => '', 'role_title' => '', 'step_order' => 1]
];
?>

<div class="row justify-content-center">
    <div class="col-lg-10 col-xl-9">
        <!-- Back Link -->
        <div class="mb-3">
            <a href="<?= BASE_URL ?>/documents" class="text-decoration-none small d-inline-flex align-items-center gap-2" style="color: var(--text-muted);">
                <i class="bi bi-arrow-left"></i>
                <span>Kembali ke Dashboard Dokumen</span>
            </a>
        </div>

        <!-- Form Card -->
        <div class="card-sigma mb-4">
            <div class="card-sigma-header py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center"
                         style="width: 40px; height: 40px; background: var(--sigma-tint-100); color: var(--sigma-purple-700);">
                        <i class="bi bi-cloud-arrow-up-fill fs-5"></i>
                    </div>
                    <div>
                        <h1 class="h5 fw-bold mb-0" style="color: var(--text-heading);">Ajukan Dokumen Baru</h1>
                        <small style="color: var(--text-muted);">Unggah dokumen PDF dan tentukan alur penandatanganan berjenjang kampus</small>
                    </div>
                </div>
            </div>

            <div class="card-sigma-body p-4">
                <form id="docForm" action="<?= BASE_URL ?>/documents/store" method="POST" enctype="multipart/form-data">
                    <?= Csrf::field() ?>

                    <!-- Section 1: Informasi Dokumen -->
                    <div class="mb-4">
                        <div class="fw-bold small text-uppercase mb-3 pb-2 border-bottom" style="letter-spacing: 0.05em; color: var(--sigma-navy); border-color: var(--border-subtle) !important;">
                            1. Informasi & Berkas Dokumen
                        </div>

                        <div class="row g-3">
                            <div class="col-12">
                                <label class="sigma-form-label d-block">Judul Dokumen <span class="text-danger">*</span></label>
                                <input type="text" name="title"
                                       class="form-control sigma-input <?= isset($errors['title']) ? 'is-invalid' : '' ?>"
                                       placeholder="Contoh: Lembar Pengesahan Tugas Akhir — Ahmad Fauzi"
                                       value="<?= htmlspecialchars($values['title'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                       maxlength="500" required>
                                <?php if (isset($errors['title'])): ?>
                                    <div class="invalid-feedback small"><?= htmlspecialchars($errors['title'], ENT_QUOTES, 'UTF-8') ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="col-12">
                                <label class="sigma-form-label d-block">Deskripsi / Catatan Tambahan <span class="fw-normal" style="color: var(--text-muted);">(Opsional)</span></label>
                                <textarea name="description" rows="2"
                                          class="form-control sigma-input"
                                          placeholder="Keterangan singkat mengenai keperluan dokumen atau pesan pengantar"><?= htmlspecialchars($values['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                            </div>

                            <div class="col-md-6">
                                <label class="sigma-form-label d-block">Tipe Alur Tanda Tangan <span class="text-danger">*</span></label>
                                <select name="signature_type" id="signatureType"
                                        class="form-select sigma-input <?= isset($errors['signature_type']) ? 'is-invalid' : '' ?>" required>
                                    <option value="sequential" <?= $curType === 'sequential' ? 'selected' : '' ?>>
                                        Tanda Tangan Berjenjang (Sequential)
                                    </option>
                                    <option value="single" <?= $curType === 'single' ? 'selected' : '' ?>>
                                        Tanda Tangan Mandiri (Single-Sign)
                                    </option>
                                </select>
                                <div class="form-text small" id="sigTypeHelp" style="color: var(--text-muted);">
                                    Berjenjang: melalui verifikasi Admin lalu ditandatangani sesuai urutan jenjang jabatan.
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="sigma-form-label d-block">Berkas Dokumen PDF <span class="text-danger">*</span></label>
                                <input type="file" name="pdf_file" id="pdfFileInput"
                                       class="form-control sigma-input <?= isset($errors['pdf_file']) ? 'is-invalid' : '' ?>"
                                       accept=".pdf,application/pdf" required>
                                <div class="form-text small" style="color: var(--text-muted);">
                                    Hanya berkas PDF (.pdf) dengan ukuran maksimum 10 MB.
                                </div>
                                <?php if (isset($errors['pdf_file'])): ?>
                                    <div class="invalid-feedback small d-block"><?= htmlspecialchars($errors['pdf_file'], ENT_QUOTES, 'UTF-8') ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Section 2: Penandatangan Berjenjang (Sequential only) -->
                    <div id="sequentialSection" class="mb-4 <?= $curType === 'single' ? 'd-none' : '' ?>" style="<?= $curType === 'single' ? 'display: none;' : '' ?>">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 pb-2 border-bottom" style="border-color: var(--border-subtle) !important;">
                            <div class="fw-bold small text-uppercase" style="letter-spacing: 0.05em; color: var(--sigma-navy);">
                                2. Susunan Penandatangan Berjenjang
                            </div>
                            <button type="button" id="btnAddSigner" class="btn-sigma-outline btn-sigma-sm">
                                <i class="bi bi-person-plus text-sigma-purple"></i> Tambah Penandatangan
                            </button>
                        </div>

                        <p class="small mb-3" style="color: var(--text-muted);">
                            Tentukan urutan hierarki penandatangan (dimulai dari urutan ke-1). Setelah disetujui Admin,
                            dokumen akan diteruskan secara bergiliran.
                        </p>

                        <!-- Filter Daftar Penandatangan per Jurusan / Prodi -->
                        <?php
                            $deptList  = $departments ?? [];
                            $prodiList = $studyPrograms ?? [];
                        ?>
                        <div class="rounded-3 border p-3 mb-3" style="background: var(--sigma-tint-50); border-color: var(--border-subtle) !important;">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                                <span class="small fw-semibold" style="color: var(--text-heading);">
                                    <i class="bi bi-funnel me-1 text-sigma-purple"></i> Filter Nama Penandatangan / Dosen
                                </span>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge-sigma badge-sigma-purple" id="signerFilterCountBadge">
                                        <?= count($availableSigners) ?> tersedia
                                    </span>
                                    <button type="button" id="btnResetSignerFilter" class="btn btn-link btn-sm text-decoration-none p-0" style="font-size: 0.78rem; color: var(--sigma-purple-700);">
                                        <i class="bi bi-arrow-counterclockwise"></i> Reset
                                    </button>
                                </div>
                            </div>
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <label for="filterDepartment" class="form-label small mb-1" style="color: var(--text-muted);">Jurusan</label>
                                    <select id="filterDepartment" class="form-select form-select-sm sigma-input">
                                        <option value="">Semua Jurusan</option>
                                        <?php foreach ($deptList as $dept): ?>
                                            <option value="<?= htmlspecialchars($dept['id'], ENT_QUOTES, 'UTF-8') ?>"
                                                    data-name="<?= htmlspecialchars($dept['name'], ENT_QUOTES, 'UTF-8') ?>">
                                                <?= htmlspecialchars($dept['name'], ENT_QUOTES, 'UTF-8') ?><?= !empty($dept['code']) ? ' (' . htmlspecialchars($dept['code'], ENT_QUOTES, 'UTF-8') . ')' : '' ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="filterStudyProgram" class="form-label small mb-1" style="color: var(--text-muted);">Program Studi (Prodi)</label>
                                    <select id="filterStudyProgram" class="form-select form-select-sm sigma-input">
                                        <option value="">Semua Prodi</option>
                                        <?php foreach ($prodiList as $pr): ?>
                                            <option value="<?= htmlspecialchars($pr['id'], ENT_QUOTES, 'UTF-8') ?>"
                                                    data-dept-id="<?= htmlspecialchars($pr['department_id'], ENT_QUOTES, 'UTF-8') ?>">
                                                <?= htmlspecialchars($pr['name'], ENT_QUOTES, 'UTF-8') ?><?= !empty($pr['degree']) ? ' (' . htmlspecialchars($pr['degree'], ENT_QUOTES, 'UTF-8') . ')' : '' ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label for="filterCivitasType" class="form-label small mb-1" style="color: var(--text-muted);">Kategori</label>
                                    <select id="filterCivitasType" class="form-select form-select-sm sigma-input">
                                        <option value="">Semua</option>
                                        <option value="dosen">Dosen</option>
                                        <option value="mahasiswa">Mahasiswa</option>
                                        <option value="tendik">Tendik</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="filterSignerSearch" class="form-label small mb-1" style="color: var(--text-muted);">Cari Nama / NIP</label>
                                    <input type="text" id="filterSignerSearch" class="form-control form-control-sm sigma-input"
                                           placeholder="Ketik nama atau NIP...">
                                </div>
                            </div>
                            <div class="form-text small mt-2 mb-0" style="color: var(--text-muted);">
                                Dosen yang mengampu lebih dari 1 prodi otomatis tampil di masing-masing prodi maupun jurusan terkait. Pilihan yang sudah dipilih di baris tabel tidak akan terhapus saat filter diganti.
                            </div>
                        </div>

                        <?php if (isset($errors['signers'])): ?>
                            <div class="alert alert-danger alert-sigma py-2 px-3 small mb-3" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= htmlspecialchars($errors['signers'], ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        <?php endif; ?>

                        <!-- Table Daftar Penandatangan -->
                        <div class="table-responsive border rounded-3 mb-2" style="border-color: var(--border-subtle) !important;">
                            <table class="table-sigma" id="signersTable">
                                <thead>
                                    <tr>
                                        <th style="width: 84px;" class="text-center">Urutan</th>
                                        <th>Penandatangan (Civitas) <span class="text-danger">*</span></th>
                                        <th>Peran / Jabatan Dokumen</th>
                                        <th style="width: 64px;" class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="signersTableBody">
                                    <?php foreach ($signersData as $idx => $s): ?>
                                    <tr class="signer-row">
                                        <td class="text-center">
                                            <span class="badge-sigma badge-sigma-purple step-number-badge"><?= $idx + 1 ?></span>
                                            <input type="hidden" name="signer_step_order[]" class="step-order-input" value="<?= $idx + 1 ?>" <?= $curType === 'single' ? 'disabled' : '' ?>>
                                        </td>
                                        <td>
                                            <select name="signer_user_id[]" class="form-select form-select-sm sigma-input signer-user-select" <?= $curType === 'single' ? 'disabled' : 'required' ?>>
                                                <option value="">-- Pilih Civitas Kampus --</option>
                                                <?php foreach ($availableSigners as $as): ?>
                                                    <?php
                                                        $isSelected = ($s['user_id'] ?? '') === $as['id'];
                                                        $cType = ucfirst($as['civitas_type'] ?? 'Civitas');
                                                        $idNum = !empty($as['identifier']) ? " ({$as['identifier']})" : '';
                                                        $pos = !empty($as['position']) ? " — {$as['position']}" : '';
                                                        $prodiSuffix = !empty($as['study_program_codes'])
                                                            ? ' [' . implode(', ', $as['study_program_codes']) . ']'
                                                            : (!empty($as['department']) ? ' [' . $as['department'] . ']' : '');
                                                    ?>
                                                    <option value="<?= htmlspecialchars($as['id'], ENT_QUOTES, 'UTF-8') ?>"
                                                            data-position="<?= htmlspecialchars($as['position'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                            <?= $isSelected ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($as['name'] . $idNum . " [{$cType}]" . $pos . $prodiSuffix, ENT_QUOTES, 'UTF-8') ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="text" name="signer_role_title[]"
                                                   class="form-control form-control-sm sigma-input signer-role-input"
                                                   placeholder="Contoh: Dosen Pembimbing I / Ketua Jurusan"
                                                   maxlength="150"
                                                   value="<?= htmlspecialchars($s['role_title'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                   <?= $curType === 'single' ? 'disabled' : '' ?>>
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-outline-danger btn-sm border-0 btn-remove-signer" title="Hapus baris">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <?php
                        $signerCatalogPayload = array_map(function ($as) {
                            $cType = ucfirst($as['civitas_type'] ?? 'Civitas');
                            $idNum = !empty($as['identifier']) ? " ({$as['identifier']})" : '';
                            $pos   = !empty($as['position']) ? " — {$as['position']}" : '';
                            $prodiSuffix = !empty($as['study_program_codes'])
                                ? ' [' . implode(', ', $as['study_program_codes']) . ']'
                                : (!empty($as['department']) ? ' [' . $as['department'] . ']' : '');
                            return [
                                'id'                  => (string) $as['id'],
                                'name'                => (string) $as['name'],
                                'identifier'          => (string) ($as['identifier'] ?? ''),
                                'civitas_type'        => strtolower((string) ($as['civitas_type'] ?? '')),
                                'position'            => (string) ($as['position'] ?? ''),
                                'department_text'     => (string) ($as['department'] ?? ''),
                                'department_ids'      => array_values($as['department_ids'] ?? []),
                                'department_names'    => array_values($as['department_names'] ?? []),
                                'study_program_ids'   => array_values($as['study_program_ids'] ?? []),
                                'study_program_names' => array_values($as['study_program_names'] ?? []),
                                'label'               => $as['name'] . $idNum . " [{$cType}]" . $pos . $prodiSuffix,
                            ];
                        }, $availableSigners);
                        $prodiCatalogPayload = array_map(function ($pr) {
                            return [
                                'id'            => (string) $pr['id'],
                                'department_id' => (string) $pr['department_id'],
                                'name'          => (string) $pr['name'],
                                'degree'        => (string) ($pr['degree'] ?? ''),
                            ];
                        }, $prodiList);
                    ?>
                    <script type="application/json" id="signerCatalogJson"><?= json_encode($signerCatalogPayload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?></script>
                    <script type="application/json" id="prodiCatalogJson"><?= json_encode($prodiCatalogPayload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?></script>

                    <!-- Submit Actions -->
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 pt-3 border-top" style="border-color: var(--border-subtle) !important;">
                        <a href="<?= BASE_URL ?>/documents" class="btn-sigma-outline">
                            <i class="bi bi-x-lg"></i> Batal
                        </a>
                        <button type="submit" id="btnSubmitForm" class="btn-sigma-navy px-4">
                            <i class="bi bi-arrow-right-circle"></i>
                            <span id="submitBtnText">Unggah & Atur Slot Tanda Tangan</span>
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const sigTypeSelect = document.getElementById('signatureType');
    const seqSection    = document.getElementById('sequentialSection');
    const sigTypeHelp   = document.getElementById('sigTypeHelp');
    const submitBtnText = document.getElementById('submitBtnText');
    const btnAddSigner        = document.getElementById('btnAddSigner');
    const tableBody           = document.getElementById('signersTableBody');
    const fileInput           = document.getElementById('pdfFileInput');
    const docForm             = document.getElementById('docForm');
    const filterDepartment    = document.getElementById('filterDepartment');
    const filterStudyProgram  = document.getElementById('filterStudyProgram');
    const filterCivitasType   = document.getElementById('filterCivitasType');
    const filterSignerSearch  = document.getElementById('filterSignerSearch');
    const btnResetFilter      = document.getElementById('btnResetSignerFilter');
    const filterCountBadge    = document.getElementById('signerFilterCountBadge');

    const signerCatalogEl = document.getElementById('signerCatalogJson');
    const prodiCatalogEl  = document.getElementById('prodiCatalogJson');
    const SIGNER_CATALOG  = signerCatalogEl ? JSON.parse(signerCatalogEl.textContent) : [];
    const PRODI_CATALOG   = prodiCatalogEl ? JSON.parse(prodiCatalogEl.textContent) : [];

    function getFilteredSigners() {
        const deptId   = filterDepartment ? filterDepartment.value : '';
        const deptOpt  = (filterDepartment && filterDepartment.selectedIndex >= 0) ? filterDepartment.options[filterDepartment.selectedIndex] : null;
        const deptName = deptOpt ? (deptOpt.getAttribute('data-name') || '').toLowerCase() : '';
        const prodiId  = filterStudyProgram ? filterStudyProgram.value : '';
        const cType    = filterCivitasType ? filterCivitasType.value.toLowerCase() : '';
        const query    = filterSignerSearch ? filterSignerSearch.value.trim().toLowerCase() : '';

        return SIGNER_CATALOG.filter(item => {
            if (deptId) {
                const hasDeptId   = Array.isArray(item.department_ids) && item.department_ids.includes(deptId);
                const matchesName = deptName !== '' && (
                    (item.department_text || '').toLowerCase() === deptName ||
                    (Array.isArray(item.department_names) && item.department_names.some(n => n.toLowerCase() === deptName))
                );
                if (!hasDeptId && !matchesName) {
                    return false;
                }
            }

            if (prodiId) {
                if (!Array.isArray(item.study_program_ids) || !item.study_program_ids.includes(prodiId)) {
                    return false;
                }
            }

            if (cType && item.civitas_type !== cType) {
                return false;
            }

            if (query) {
                const haystack = [
                    item.name || '',
                    item.identifier || '',
                    item.position || '',
                    item.department_text || '',
                    ...(item.department_names || []),
                    ...(item.study_program_names || [])
                ].join(' ').toLowerCase();
                if (!haystack.includes(query)) {
                    return false;
                }
            }

            return true;
        });
    }

    function populateSelectWithSigners(selectEl, filteredSigners, preservedValue) {
        const currentVal = preservedValue !== undefined ? preservedValue : selectEl.value;
        selectEl.textContent = '';

        const defaultOpt = document.createElement('option');
        defaultOpt.value = '';
        defaultOpt.textContent = filteredSigners.length > 0
            ? '-- Pilih Civitas Kampus --'
            : '-- Tidak ada civitas yang cocok dengan filter --';
        selectEl.appendChild(defaultOpt);

        let preservedIncluded = (currentVal === '');

        filteredSigners.forEach(item => {
            const opt = document.createElement('option');
            opt.value = item.id;
            opt.textContent = item.label;
            opt.setAttribute('data-position', item.position || '');
            if (item.id === currentVal) {
                opt.selected = true;
                preservedIncluded = true;
            }
            selectEl.appendChild(opt);
        });

        // Jaga agar civitas yang sudah terpilih pada baris ini tidak hilang saat filter diubah
        if (currentVal !== '' && !preservedIncluded) {
            const existingItem = SIGNER_CATALOG.find(item => item.id === currentVal);
            if (existingItem) {
                const opt = document.createElement('option');
                opt.value = existingItem.id;
                opt.textContent = existingItem.label + ' (Terpilih di luar filter)';
                opt.setAttribute('data-position', existingItem.position || '');
                opt.selected = true;
                selectEl.insertBefore(opt, selectEl.children[1] || null);
            }
        }
    }

    function syncProdiFilterOptions() {
        if (!filterStudyProgram) return;
        const selectedDeptId = filterDepartment ? filterDepartment.value : '';
        const prevProdiId    = filterStudyProgram.value;

        filterStudyProgram.textContent = '';
        const allOpt = document.createElement('option');
        allOpt.value = '';
        allOpt.textContent = selectedDeptId ? 'Semua Prodi di Jurusan Ini' : 'Semua Prodi';
        filterStudyProgram.appendChild(allOpt);

        let keepPrev = false;
        PRODI_CATALOG.forEach(pr => {
            if (selectedDeptId && pr.department_id !== selectedDeptId) {
                return;
            }
            const opt = document.createElement('option');
            opt.value = pr.id;
            opt.textContent = pr.name + (pr.degree ? ' (' + pr.degree + ')' : '');
            if (pr.id === prevProdiId) {
                opt.selected = true;
                keepPrev = true;
            }
            filterStudyProgram.appendChild(opt);
        });

        if (!keepPrev) {
            filterStudyProgram.value = '';
        }
    }

    function refreshAllSignerSelects() {
        const filtered = getFilteredSigners();
        if (filterCountBadge) {
            filterCountBadge.textContent = filtered.length + ' / ' + SIGNER_CATALOG.length + ' cocok';
        }
        tableBody.querySelectorAll('.signer-user-select').forEach(sel => {
            populateSelectWithSigners(sel, filtered, sel.value);
        });
    }

    if (filterDepartment) {
        filterDepartment.addEventListener('change', function () {
            syncProdiFilterOptions();
            refreshAllSignerSelects();
        });
    }
    if (filterStudyProgram) {
        filterStudyProgram.addEventListener('change', refreshAllSignerSelects);
    }
    if (filterCivitasType) {
        filterCivitasType.addEventListener('change', refreshAllSignerSelects);
    }
    if (filterSignerSearch) {
        filterSignerSearch.addEventListener('input', refreshAllSignerSelects);
    }
    if (btnResetFilter) {
        btnResetFilter.addEventListener('click', function () {
            if (filterDepartment) filterDepartment.value = '';
            syncProdiFilterOptions();
            if (filterStudyProgram) filterStudyProgram.value = '';
            if (filterCivitasType) filterCivitasType.value = '';
            if (filterSignerSearch) filterSignerSearch.value = '';
            refreshAllSignerSelects();
        });
    }

    function resetSequentialSigners() {
        if (filterDepartment) filterDepartment.value = '';
        syncProdiFilterOptions();
        if (filterStudyProgram) filterStudyProgram.value = '';
        if (filterCivitasType) filterCivitasType.value = '';
        if (filterSignerSearch) filterSignerSearch.value = '';

        const rows = Array.from(tableBody.querySelectorAll('.signer-row'));
        rows.forEach((row, idx) => {
            if (idx > 0) {
                row.remove();
            }
        });

        const firstRow = tableBody.querySelector('.signer-row');
        if (firstRow) {
            const sel = firstRow.querySelector('.signer-user-select');
            const roleInput = firstRow.querySelector('.signer-role-input');
            if (sel) {
                sel.value = '';
                sel.selectedIndex = 0;
            }
            if (roleInput) {
                roleInput.value = '';
            }
        }

        refreshAllSignerSelects();
        reindexSigners();
    }

    // Toggle tipe alur tanda tangan
    function updateSigTypeUI() {
        const isSingle = (sigTypeSelect.value === 'single');
        if (isSingle) {
            seqSection.style.display = 'none';
            seqSection.classList.add('d-none');
            sigTypeHelp.textContent = 'Mandiri: Anda menandatangani sendiri berkas ini tanpa review Admin dan langsung selesai.';
            submitBtnText.textContent = 'Unggah & Atur Slot Mandiri';
            // Reset pilihan civitas/baris penandatangan dan nonaktifkan input sequential
            // agar tidak ikut terkirim maupun memicu validasi saat mode mandiri dipilih
            resetSequentialSigners();
            seqSection.querySelectorAll('input, select, button').forEach(el => {
                el.removeAttribute('required');
                el.disabled = true;
            });
        } else {
            seqSection.style.display = 'block';
            seqSection.classList.remove('d-none');
            sigTypeHelp.textContent = 'Berjenjang: melalui verifikasi Admin lalu ditandatangani sesuai urutan jenjang jabatan.';
            submitBtnText.textContent = 'Unggah & Atur Slot Tanda Tangan';
            seqSection.querySelectorAll('input, select, button').forEach(el => {
                el.disabled = false;
            });
            tableBody.querySelectorAll('.signer-user-select').forEach(el => el.setAttribute('required', 'required'));
            reindexSigners();
        }
    }

    function syncFormUIState() {
        updateSigTypeUI();
        if (sigTypeSelect.value !== 'single') {
            syncProdiFilterOptions();
            refreshAllSignerSelects();
            reindexSigners();
        }
    }

    sigTypeSelect.addEventListener('change', updateSigTypeUI);
    sigTypeSelect.addEventListener('input', updateSigTypeUI);

    // Re-index urutan penandatangan
    function reindexSigners() {
        const rows = tableBody.querySelectorAll('.signer-row');
        rows.forEach((row, idx) => {
            const stepNum = idx + 1;
            const badge = row.querySelector('.step-number-badge');
            const stepInput = row.querySelector('.step-order-input');
            if (badge) badge.textContent = stepNum;
            if (stepInput) stepInput.value = stepNum;

            const removeBtn = row.querySelector('.btn-remove-signer');
            if (removeBtn) {
                removeBtn.disabled = (rows.length <= 1);
            }
        });
        if (btnAddSigner && sigTypeSelect.value !== 'single') {
            btnAddSigner.disabled = (rows.length >= 20);
        }
    }

    // Auto-fill peran/jabatan dari data-position
    tableBody.addEventListener('change', function (e) {
        if (e.target.classList.contains('signer-user-select')) {
            const selectedOpt = e.target.options[e.target.selectedIndex];
            const pos = selectedOpt ? selectedOpt.getAttribute('data-position') : '';
            const row = e.target.closest('.signer-row');
            const roleInput = row.querySelector('.signer-role-input');
            if (roleInput && !roleInput.value && pos) {
                roleInput.value = pos;
            }
        }
    });

    // Tambah penandatangan
    btnAddSigner.addEventListener('click', function () {
        const tr = document.createElement('tr');
        tr.className = 'signer-row';

        const tdOrder = document.createElement('td');
        tdOrder.className = 'text-center';
        const badge = document.createElement('span');
        badge.className = 'badge-sigma badge-sigma-purple step-number-badge';
        badge.textContent = '1';
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'signer_step_order[]';
        hiddenInput.className = 'step-order-input';
        hiddenInput.value = '1';
        tdOrder.appendChild(badge);
        tdOrder.appendChild(hiddenInput);

        const tdSelect = document.createElement('td');
        const selectEl = document.createElement('select');
        selectEl.name = 'signer_user_id[]';
        selectEl.className = 'form-select form-select-sm sigma-input signer-user-select';
        if (sigTypeSelect.value !== 'single') {
            selectEl.setAttribute('required', 'required');
        }
        populateSelectWithSigners(selectEl, getFilteredSigners(), '');
        tdSelect.appendChild(selectEl);

        const tdRole = document.createElement('td');
        const roleInput = document.createElement('input');
        roleInput.type = 'text';
        roleInput.name = 'signer_role_title[]';
        roleInput.className = 'form-control form-control-sm sigma-input signer-role-input';
        roleInput.placeholder = 'Contoh: Dosen Pembimbing I / Ketua Jurusan';
        roleInput.maxLength = 150;
        tdRole.appendChild(roleInput);

        const tdAct = document.createElement('td');
        tdAct.className = 'text-center';
        const delBtn = document.createElement('button');
        delBtn.type = 'button';
        delBtn.className = 'btn btn-outline-danger btn-sm border-0 btn-remove-signer';
        delBtn.title = 'Hapus baris';
        delBtn.innerHTML = '<i class="bi bi-trash"></i>';
        tdAct.appendChild(delBtn);

        tr.appendChild(tdOrder);
        tr.appendChild(tdSelect);
        tr.appendChild(tdRole);
        tr.appendChild(tdAct);

        tableBody.appendChild(tr);
        reindexSigners();
    });

    // Hapus penandatangan
    tableBody.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-remove-signer');
        if (btn) {
            const rows = tableBody.querySelectorAll('.signer-row');
            if (rows.length > 1) {
                btn.closest('.signer-row').remove();
                reindexSigners();
            }
        }
    });

    // Validasi ukuran PDF client-side
    fileInput.addEventListener('change', function () {
        if (this.files && this.files[0]) {
            const file = this.files[0];
            const maxBytes = 10 * 1024 * 1024; // 10MB
            if (file.size > maxBytes) {
                alert('Ukuran berkas PDF melebihi batas 10 MB (' + (file.size / (1024 * 1024)).toFixed(2) + ' MB). Silakan pilih berkas yang lebih kecil.');
                this.value = '';
            }
        }
    });

    // Validasi form submit: pastikan data sequential bersih pada mode single & cek duplikasi pada mode sequential
    docForm.addEventListener('submit', function (e) {
        if (sigTypeSelect.value === 'single') {
            updateSigTypeUI();
            return;
        }
        if (sigTypeSelect.value === 'sequential') {
            const selects = tableBody.querySelectorAll('.signer-user-select');
            const userIds = [];
            for (let sel of selects) {
                if (sel.value) {
                    if (userIds.includes(sel.value)) {
                        e.preventDefault();
                        alert('Terdapat penandatangan yang dipilih lebih dari satu kali. Mohon pastikan setiap penandatangan unik.');
                        sel.focus();
                        return;
                    }
                    userIds.push(sel.value);
                }
            }
            if (userIds.length === 0) {
                e.preventDefault();
                alert('Tanda tangan berjenjang memerlukan minimal 1 penandatangan.');
            }
        }
    });

    syncFormUIState();
    // Sinkronkan ulang setelah browser memulihkan state form (tombol Back / BFCache)
    requestAnimationFrame(syncFormUIState);
    setTimeout(syncFormUIState, 0);
    window.addEventListener('load', syncFormUIState);
    window.addEventListener('pageshow', syncFormUIState);
});
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
