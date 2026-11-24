<?php
require_once __DIR__ . '/../layout/header.php';
?>

<!-- Title & Action Button -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <div class="text-uppercase fw-bold mb-1" style="font-size: 0.72rem; letter-spacing: 0.08em; color: var(--sigma-purple-600);">
            Administrasi Biodata Kampus
        </div>
        <h4 class="fw-bold mb-1" style="color: var(--text-heading);">Master Data Civitas Kampus</h4>
        <p class="small mb-0" style="color: var(--text-muted);">Data Dosen, Mahasiswa, dan Tendik.</p>
    </div>
    <div class="d-flex flex-wrap align-items-center gap-2">
        <a href="<?= BASE_URL ?>/civitas/template" class="btn-sigma-outline text-decoration-none" title="Unduh template resmi CSV">
            <i class="bi bi-file-earmark-arrow-down"></i>
            <span>Template CSV</span>
        </a>
        <a href="<?= BASE_URL ?>/civitas/import" class="btn-sigma-purple text-decoration-none">
            <i class="bi bi-file-earmark-spreadsheet"></i>
            <span>Import CSV Massal</span>
        </a>
        <a href="<?= BASE_URL ?>/civitas/create" class="btn-sigma-navy text-decoration-none">
            <i class="bi bi-person-plus-fill"></i>
            <span>Tambah Data Civitas</span>
        </a>
    </div>
</div>

<!-- Stat Cards Row -->
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="stat-card-sigma">
            <div>
                <div class="stat-label mb-1">Total Civitas</div>
                <div class="stat-val"><?= (int) ($counts['total'] ?? 0) ?></div>
            </div>
            <div class="stat-icon" style="background: var(--sigma-tint-100); color: var(--sigma-purple-700);">
                <i class="bi bi-people-fill"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card-sigma">
            <div>
                <div class="stat-label mb-1">Dosen</div>
                <div class="stat-val"><?= (int) ($counts['dosen'] ?? 0) ?></div>
            </div>
            <div class="stat-icon" style="background: var(--sigma-success-bg); color: var(--sigma-success-text);">
                <i class="bi bi-mortarboard-fill"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card-sigma">
            <div>
                <div class="stat-label mb-1">Mahasiswa</div>
                <div class="stat-val"><?= (int) ($counts['mahasiswa'] ?? 0) ?></div>
            </div>
            <div class="stat-icon" style="background: #e0f2fe; color: #0369a1;">
                <i class="bi bi-person-badge-fill"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card-sigma">
            <div>
                <div class="stat-label mb-1">Tenaga Kependidikan</div>
                <div class="stat-val"><?= (int) ($counts['tendik'] ?? 0) ?></div>
            </div>
            <div class="stat-icon" style="background: var(--sigma-tint-pill); color: var(--text-body);">
                <i class="bi bi-person-workspace"></i>
            </div>
        </div>
    </div>
</div>

<!-- Category Tabs & Search Bar -->
<div class="card-sigma mb-4">
    <div class="card-sigma-body p-3">
        <div class="row g-3 align-items-center justify-content-between">
            <!-- Filter Tabs -->
            <div class="col-lg-7">
                <div class="nav-tabs-sigma">
                    <a href="<?= BASE_URL ?>/civitas<?= !empty($search) ? '?q=' . urlencode($search) : '' ?>"
                       class="nav-tab-item <?= empty($currentType) ? 'active' : '' ?>">
                        Semua <span class="badge-sigma badge-sigma-neutral ms-1"><?= (int) ($counts['total'] ?? 0) ?></span>
                    </a>
                    <a href="<?= BASE_URL ?>/civitas?type=dosen<?= !empty($search) ? '&q=' . urlencode($search) : '' ?>"
                       class="nav-tab-item <?= ($currentType === 'dosen') ? 'active' : '' ?>">
                        Dosen <span class="badge-sigma badge-sigma-neutral ms-1"><?= (int) ($counts['dosen'] ?? 0) ?></span>
                    </a>
                    <a href="<?= BASE_URL ?>/civitas?type=mahasiswa<?= !empty($search) ? '&q=' . urlencode($search) : '' ?>"
                       class="nav-tab-item <?= ($currentType === 'mahasiswa') ? 'active' : '' ?>">
                        Mahasiswa <span class="badge-sigma badge-sigma-neutral ms-1"><?= (int) ($counts['mahasiswa'] ?? 0) ?></span>
                    </a>
                    <a href="<?= BASE_URL ?>/civitas?type=tendik<?= !empty($search) ? '&q=' . urlencode($search) : '' ?>"
                       class="nav-tab-item <?= ($currentType === 'tendik') ? 'active' : '' ?>">
                        Tendik <span class="badge-sigma badge-sigma-neutral ms-1"><?= (int) ($counts['tendik'] ?? 0) ?></span>
                    </a>
                </div>
            </div>

            <!-- Search Form -->
            <div class="col-lg-5">
                <form method="GET" action="<?= BASE_URL ?>/civitas" class="d-flex gap-2">
                    <?php if (!empty($currentType)): ?>
                        <input type="hidden" name="type" value="<?= htmlspecialchars($currentType) ?>">
                    <?php endif; ?>
                    <div class="sigma-search-box flex-grow-1">
                        <i class="bi bi-search sigma-search-icon"></i>
                        <input type="text" name="q" class="form-control"
                               placeholder="Cari NIM/NIP, nama, jurusan, atau prodi..."
                               value="<?= htmlspecialchars($search ?? '') ?>">
                    </div>
                    <button type="submit" class="btn-sigma-navy btn-sigma-sm px-3">Cari</button>
                    <?php if (!empty($search)): ?>
                        <a href="<?= BASE_URL ?>/civitas<?= !empty($currentType) ? '?type=' . urlencode($currentType) : '' ?>"
                           class="btn-sigma-outline btn-sigma-sm text-decoration-none" title="Hapus pencarian">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Civitas Table Card -->
<div class="card-sigma mb-4">
    <?php if (empty($civitasList)): ?>
        <div class="p-5 text-center">
            <div class="mb-2" style="font-size: 2.5rem; color: var(--sigma-lavender);">
                <i class="bi bi-person-x"></i>
            </div>
            <h6 class="fw-semibold mb-1" style="color: var(--text-heading);">Tidak Ada Data Civitas yang Ditemukan</h6>
            <p class="small mb-3" style="color: var(--text-muted);">
                <?= !empty($search) || !empty($currentType)
                    ? 'Tidak ada data yang cocok.'
                    : 'Belum ada data civitas kampus yang tersimpan di sistem.' ?>
            </p>
            <?php if (!empty($search) || !empty($currentType)): ?>
                <a href="<?= BASE_URL ?>/civitas" class="btn-sigma-outline btn-sigma-sm text-decoration-none">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset Filter
                </a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/civitas/create" class="btn-sigma-navy btn-sigma-sm text-decoration-none">
                    <i class="bi bi-plus-lg"></i> Tambah Data Sekarang
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sigma mb-0">
                <thead>
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th>NIM / NIP</th>
                        <th>Nama &amp; Jabatan</th>
                        <th>Kategori</th>
                        <th>Jurusan / Unit</th>
                        <th>Kontak</th>
                        <th style="width: 120px; text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = 1;
                    $typeClasses = [
                        'dosen'     => 'badge-sigma-success',
                        'mahasiswa' => 'badge-sigma-purple',
                        'tendik'    => 'badge-sigma-neutral',
                    ];
                    foreach ($civitasList as $c):
                        $badgeClass = $typeClasses[$c['type']] ?? 'badge-sigma-neutral';
                    ?>
                        <tr>
                            <td class="small" style="color: var(--text-muted);"><?= $no++ ?></td>
                            <td>
                                <code class="font-mono-hash fw-semibold px-2 py-1 rounded" style="background: var(--sigma-tint-50); color: var(--text-heading); border: 1px solid var(--border-subtle); font-size: 0.8rem;">
                                    <?= htmlspecialchars($c['identifier']) ?>
                                </code>
                            </td>
                            <td>
                                <div class="fw-semibold" style="color: var(--text-heading);"><?= htmlspecialchars($c['name']) ?></div>
                                <div class="small" style="font-size: 0.78rem; color: var(--text-muted);">
                                    <?= htmlspecialchars($c['position']) ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge-sigma <?= $badgeClass ?> text-uppercase">
                                    <?= htmlspecialchars($c['type']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="small fw-medium" style="color: var(--text-heading);"><?= htmlspecialchars($c['department']) ?></div>
                                <?php if (!empty($c['study_programs'])): ?>
                                    <div class="d-flex flex-wrap gap-1 mt-1">
                                        <?php foreach ($c['study_programs'] as $sp): ?>
                                            <span class="badge-sigma badge-sigma-neutral" style="font-size: 0.68rem;"
                                                  title="<?= htmlspecialchars($sp['department_name']) ?>">
                                                <?= htmlspecialchars($sp['name']) ?>
                                                <?= !empty($sp['degree']) ? '(' . htmlspecialchars($sp['degree']) . ')' : '' ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="small" style="color: var(--text-body);"><?= htmlspecialchars($c['email']) ?></div>
                                <?php if (!empty($c['phone'])): ?>
                                    <div class="small" style="font-size: 0.75rem; color: var(--text-muted);">
                                        <i class="bi bi-telephone me-1"></i><?= htmlspecialchars($c['phone']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right;">
                                <div class="d-inline-flex gap-1">
                                    <a href="<?= BASE_URL ?>/civitas/<?= htmlspecialchars($c['id']) ?>/edit"
                                       class="btn-sigma-outline btn-sigma-sm text-decoration-none" title="Ubah Data">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <button type="button" class="btn-sigma-outline btn-sigma-sm text-danger"
                                            style="border-color: #fecaca;"
                                            onclick="confirmDelete('<?= htmlspecialchars($c['id']) ?>', '<?= htmlspecialchars(addslashes($c['name'])) ?>', '<?= htmlspecialchars($c['identifier']) ?>')"
                                            title="Hapus Data">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Modal Konfirmasi Hapus (Error Prevention Pattern) -->
<div class="modal fade modal-sigma" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <div class="modal-content">
            <div class="modal-body p-4 text-center">
                <div class="rounded-circle d-inline-flex p-3 mb-3" style="background: var(--sigma-danger-bg); color: var(--sigma-danger-text);">
                    <i class="bi bi-exclamation-triangle fs-2"></i>
                </div>
                <h5 class="fw-bold mb-1" style="color: var(--text-heading);">Konfirmasi Hapus Civitas</h5>
                <p class="small mb-3" style="color: var(--text-muted);">
                    Hapus data civitas berikut?
                </p>

                <div class="p-3 rounded-3 border text-start mb-3" style="background: var(--sigma-tint-50); border-color: var(--border-subtle) !important;">
                    <div class="small mb-1" style="color: var(--text-muted);">Nama Civitas:</div>
                    <div class="fw-bold" style="color: var(--text-heading);" id="modalDeleteName">-</div>
                    <div class="small mt-2 mb-1" style="color: var(--text-muted);">NIM / NIP:</div>
                    <div class="fw-bold font-mono-hash" style="color: var(--text-heading);" id="modalDeleteIdentifier">-</div>
                </div>
                <p style="font-size: 0.78rem; color: var(--text-muted);">
                    * Civitas ini tidak akan muncul lagi di daftar penandatangan.
                </p>

                <form id="deleteForm" method="POST" action="">
                    <?= Csrf::field() ?>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn-sigma-outline w-50 justify-content-center" data-bs-dismiss="modal">
                            Batal
                        </button>
                        <button type="submit" class="btn-sigma-danger w-50 justify-content-center">
                            Ya, Hapus Data
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(id, name, identifier) {
    document.getElementById('modalDeleteName').textContent = name;
    document.getElementById('modalDeleteIdentifier').textContent = identifier;
    document.getElementById('deleteForm').action = '<?= BASE_URL ?>/civitas/' + id + '/delete';

    const deleteModal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
    deleteModal.show();
}
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
