<?php require_once __DIR__ . '/../layout/header.php'; ?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <div class="text-uppercase fw-bold mb-1" style="font-size: 0.72rem; letter-spacing: 0.08em; color: var(--sigma-purple-600);">
            Struktur Akademik Kampus
        </div>
        <h4 class="fw-bold mb-1" style="color: var(--text-heading);">Master Jurusan &amp; Program Studi</h4>
        <p class="small mb-0" style="color: var(--text-muted);">
            Kelola daftar Jurusan dan Program Studi (Prodi) sebagai dasar penugasan Dosen (multi-prodi) &amp; filter penandatangan dokumen.
        </p>
    </div>
    <div class="d-flex flex-wrap align-items-center gap-2">
        <a href="<?= BASE_URL ?>/civitas" class="btn-sigma-outline text-decoration-none">
            <i class="bi bi-person-vcard"></i>
            <span>Master Data Civitas</span>
        </a>
        <?php if (!empty($departments)): ?>
            <a href="<?= BASE_URL ?>/prodi/create" class="btn-sigma-purple text-decoration-none">
                <i class="bi bi-mortarboard"></i>
                <span>+ Tambah Prodi</span>
            </a>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>/departments/create" class="btn-sigma-navy text-decoration-none">
            <i class="bi bi-plus-lg"></i>
            <span>Tambah Jurusan</span>
        </a>
    </div>
</div>

<!-- Ringkasan & Pencarian -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card-sigma">
            <div>
                <div class="stat-label mb-1">Total Jurusan</div>
                <div class="stat-val"><?= count($departments) ?></div>
            </div>
            <div class="stat-icon" style="background: var(--sigma-tint-100); color: var(--sigma-purple-700);">
                <i class="bi bi-building"></i>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card-sigma">
            <div>
                <div class="stat-label mb-1">Total Program Studi</div>
                <div class="stat-val"><?= (int) $totalProdi ?></div>
            </div>
            <div class="stat-icon" style="background: #e0f2fe; color: #0369a1;">
                <i class="bi bi-mortarboard"></i>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card-sigma h-100 d-flex align-items-center">
            <div class="card-sigma-body p-3 w-100">
                <form action="<?= BASE_URL ?>/departments" method="GET" class="d-flex gap-2 w-100">
                    <div class="sigma-search-box flex-grow-1">
                        <i class="bi bi-search sigma-search-icon"></i>
                        <input type="text"
                               name="q"
                               class="form-control"
                               placeholder="Cari jurusan atau prodi..."
                               value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <button class="btn-sigma-navy btn-sigma-sm px-3" type="submit">Cari</button>
                    <?php if ($search !== ''): ?>
                        <a href="<?= BASE_URL ?>/departments" class="btn-sigma-outline btn-sigma-sm text-decoration-none" title="Reset pencarian">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</div>

<?php if (empty($departments)): ?>
    <div class="card-sigma">
        <div class="p-5 text-center">
            <i class="bi bi-building" style="font-size: 2.5rem; color: var(--sigma-lavender);"></i>
            <h6 class="fw-semibold mt-3 mb-1" style="color: var(--text-heading);">
                <?= $search !== '' ? 'Jurusan atau Prodi tidak ditemukan' : 'Belum ada data Jurusan' ?>
            </h6>
            <p class="small mb-3" style="color: var(--text-muted);">
                <?= $search !== ''
                    ? 'Coba ubah kata kunci pencarian atau reset filter untuk melihat seluruh data.'
                    : 'Tambahkan Jurusan dan Program Studi terlebih dahulu agar dapat dikaitkan dengan data Dosen & Mahasiswa.' ?>
            </p>
            <?php if ($search !== ''): ?>
                <a href="<?= BASE_URL ?>/departments" class="btn-sigma-outline btn-sigma-sm text-decoration-none">Tampilkan Semua</a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/departments/create" class="btn-sigma-navy btn-sigma-sm text-decoration-none">
                    <i class="bi bi-plus-lg"></i> Tambah Jurusan Pertama
                </a>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>
    <div class="d-flex flex-column gap-4">
        <?php foreach ($departments as $dept): ?>
            <?php $prodiList = $dept['study_programs'] ?? []; ?>
            <div class="card-sigma overflow-hidden">
                <div class="card-sigma-header py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <?php if (!empty($dept['code'])): ?>
                                <span class="badge-sigma badge-sigma-purple">
                                    <?= htmlspecialchars($dept['code']) ?>
                                </span>
                            <?php endif; ?>
                            <h5 class="mb-0 fw-bold" style="color: var(--text-heading);"><?= htmlspecialchars($dept['name']) ?></h5>
                        </div>
                        <div class="small mt-1" style="color: var(--text-muted);">
                            <span class="me-3">
                                <i class="bi bi-mortarboard me-1"></i><?= (int) ($dept['prodi_count'] ?? count($prodiList)) ?> Prodi
                            </span>
                            <span>
                                <i class="bi bi-people me-1"></i><?= (int) ($dept['civitas_count'] ?? 0) ?> Civitas Terdaftar
                            </span>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="<?= BASE_URL ?>/prodi/create?department_id=<?= urlencode($dept['id']) ?>"
                           class="btn-sigma-purple btn-sigma-sm text-decoration-none">
                            <i class="bi bi-plus-circle"></i> Tambah Prodi
                        </a>
                        <a href="<?= BASE_URL ?>/departments/<?= htmlspecialchars($dept['id']) ?>/edit"
                           class="btn-sigma-outline btn-sigma-sm text-decoration-none">
                            <i class="bi bi-pencil-square"></i> Ubah Jurusan
                        </a>
                        <form action="<?= BASE_URL ?>/departments/<?= htmlspecialchars($dept['id']) ?>/delete"
                              method="POST"
                              class="d-inline"
                              onsubmit="return confirm('Yakin ingin menghapus Jurusan ' + <?= htmlspecialchars(json_encode($dept['name'], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?> + '?');">
                            <?= Csrf::field() ?>
                            <button type="submit" class="btn-sigma-outline btn-sigma-sm text-danger" style="border-color: #fecaca;">
                                <i class="bi bi-trash3"></i> Hapus
                            </button>
                        </form>
                    </div>
                </div>

                <div class="p-0">
                    <?php if (empty($prodiList)): ?>
                        <div class="p-4 text-center small" style="color: var(--text-muted);">
                            <i class="bi bi-info-circle me-1"></i>
                            Belum ada Program Studi di jurusan ini.
                            <a href="<?= BASE_URL ?>/prodi/create?department_id=<?= urlencode($dept['id']) ?>" class="fw-semibold text-decoration-none ms-1" style="color: var(--sigma-violet-600);">
                                Tambah Prodi sekarang
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sigma mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 110px;">Kode</th>
                                        <th style="width: 120px;">Jenjang</th>
                                        <th>Nama Program Studi</th>
                                        <th class="text-center" style="width: 150px;">Dosen Mengampu</th>
                                        <th class="text-center" style="width: 140px;">Total Civitas</th>
                                        <th class="text-end" style="width: 130px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($prodiList as $pr): ?>
                                        <tr>
                                            <td>
                                                <?php if (!empty($pr['code'])): ?>
                                                    <code class="font-mono-hash fw-semibold px-2 py-1 rounded"
                                                          style="background: var(--sigma-tint-50); color: var(--text-heading); border: 1px solid var(--border-subtle); font-size: 0.8rem;">
                                                        <?= htmlspecialchars($pr['code']) ?>
                                                    </code>
                                                <?php else: ?>
                                                    <span class="small" style="color: var(--text-muted);">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($pr['degree'])): ?>
                                                    <span class="badge-sigma"
                                                          style="background: #e0f2fe; color: #0369a1;">
                                                        <?= htmlspecialchars($pr['degree']) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="small" style="color: var(--text-muted);">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="fw-medium" style="color: var(--text-heading);"><?= htmlspecialchars($pr['name']) ?></td>
                                            <td class="text-center">
                                                <span class="badge-sigma badge-sigma-success">
                                                    <?= (int) ($pr['dosen_count'] ?? 0) ?> Dosen
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge-sigma badge-sigma-neutral">
                                                    <?= (int) ($pr['civitas_count'] ?? 0) ?> Civitas
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <div class="d-inline-flex gap-1">
                                                    <a href="<?= BASE_URL ?>/prodi/<?= htmlspecialchars($pr['id']) ?>/edit"
                                                       class="btn-sigma-outline btn-sigma-sm text-decoration-none"
                                                       title="Ubah Prodi">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </a>
                                                    <form action="<?= BASE_URL ?>/prodi/<?= htmlspecialchars($pr['id']) ?>/delete"
                                                          method="POST"
                                                          class="d-inline"
                                                          onsubmit="return confirm('Yakin ingin menghapus Program Studi ' + <?= htmlspecialchars(json_encode($pr['name'], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?> + '?');">
                                                        <?= Csrf::field() ?>
                                                        <button type="submit" class="btn-sigma-outline btn-sigma-sm text-danger" style="border-color: #fecaca;" title="Hapus Prodi">
                                                            <i class="bi bi-trash3"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
