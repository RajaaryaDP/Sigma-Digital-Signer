<?php require_once __DIR__ . '/../layout/header.php'; ?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Master Jurusan &amp; Program Studi</h4>
        <p class="text-muted small mb-0">
            Kelola daftar Jurusan dan Program Studi (Prodi) sebagai dasar penugasan Dosen (multi-prodi) &amp; filter penandatangan dokumen.
        </p>
    </div>
    <div class="d-flex flex-wrap align-items-center gap-2">
        <a href="<?= BASE_URL ?>/civitas" class="btn btn-subtle d-inline-flex align-items-center gap-2">
            <i class="bi bi-person-vcard"></i>
            <span>Master Data Civitas</span>
        </a>
        <?php if (!empty($departments)): ?>
            <a href="<?= BASE_URL ?>/prodi/create" class="btn btn-subtle d-inline-flex align-items-center gap-2"
               style="border-color: #a7f3d0; background: #ecfdf5; color: #065f46;">
                <i class="bi bi-mortarboard"></i>
                <span>+ Tambah Prodi</span>
            </a>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>/departments/create" class="btn btn-pnj-primary d-inline-flex align-items-center gap-2">
            <i class="bi bi-plus-lg"></i>
            <span>Tambah Jurusan</span>
        </a>
    </div>
</div>

<!-- Ringkasan & Pencarian -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card-clean h-100">
            <div class="card-clean-body p-3 d-flex align-items-center gap-3">
                <div class="rounded-3 d-flex align-items-center justify-content-center"
                     style="width: 44px; height: 44px; background: var(--pnj-green-50); color: var(--pnj-green-800);">
                    <i class="bi bi-building fs-5"></i>
                </div>
                <div>
                    <div class="text-muted small">Total Jurusan</div>
                    <div class="fs-4 fw-bold text-dark"><?= count($departments) ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card-clean h-100">
            <div class="card-clean-body p-3 d-flex align-items-center gap-3">
                <div class="rounded-3 d-flex align-items-center justify-content-center"
                     style="width: 44px; height: 44px; background: #e0f2fe; color: #0369a1;">
                    <i class="bi bi-mortarboard fs-5"></i>
                </div>
                <div>
                    <div class="text-muted small">Total Program Studi</div>
                    <div class="fs-4 fw-bold text-dark"><?= (int) $totalProdi ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card-clean h-100">
            <div class="card-clean-body p-3 d-flex align-items-center">
                <form action="<?= BASE_URL ?>/departments" method="GET" class="d-flex gap-2 w-100">
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted border-end-0">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text"
                               name="q"
                               class="form-control border-start-0"
                               placeholder="Cari jurusan atau prodi..."
                               value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <button class="btn btn-subtle px-3" type="submit">Cari</button>
                    <?php if ($search !== ''): ?>
                        <a href="<?= BASE_URL ?>/departments" class="btn btn-outline-secondary" title="Reset pencarian">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</div>

<?php if (empty($departments)): ?>
    <div class="card-clean">
        <div class="p-5 text-center">
            <i class="bi bi-building text-muted" style="font-size: 2.5rem; opacity: 0.4;"></i>
            <h6 class="fw-semibold text-dark mt-3 mb-1">
                <?= $search !== '' ? 'Jurusan atau Prodi tidak ditemukan' : 'Belum ada data Jurusan' ?>
            </h6>
            <p class="text-muted small mb-3">
                <?= $search !== ''
                    ? 'Coba ubah kata kunci pencarian atau reset filter untuk melihat seluruh data.'
                    : 'Tambahkan Jurusan dan Program Studi terlebih dahulu agar dapat dikaitkan dengan data Dosen & Mahasiswa.' ?>
            </p>
            <?php if ($search !== ''): ?>
                <a href="<?= BASE_URL ?>/departments" class="btn btn-subtle btn-sm">Tampilkan Semua</a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/departments/create" class="btn btn-pnj-primary btn-sm">
                    <i class="bi bi-plus-lg me-1"></i> Tambah Jurusan Pertama
                </a>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>
    <div class="d-flex flex-column gap-4">
        <?php foreach ($departments as $dept): ?>
            <?php $prodiList = $dept['study_programs'] ?? []; ?>
            <div class="card-clean overflow-hidden">
                <div class="card-clean-header py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <?php if (!empty($dept['code'])): ?>
                                <span class="badge fw-semibold"
                                      style="background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0;">
                                    <?= htmlspecialchars($dept['code']) ?>
                                </span>
                            <?php endif; ?>
                            <h5 class="mb-0 fw-bold text-dark"><?= htmlspecialchars($dept['name']) ?></h5>
                        </div>
                        <div class="text-muted small mt-1">
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
                           class="btn btn-sm btn-subtle"
                           style="border-color: #a7f3d0; background: #ecfdf5; color: #065f46;">
                            <i class="bi bi-plus-circle me-1"></i> Tambah Prodi
                        </a>
                        <a href="<?= BASE_URL ?>/departments/<?= htmlspecialchars($dept['id']) ?>/edit"
                           class="btn btn-sm btn-subtle">
                            <i class="bi bi-pencil-square me-1"></i> Ubah Jurusan
                        </a>
                        <form action="<?= BASE_URL ?>/departments/<?= htmlspecialchars($dept['id']) ?>/delete"
                              method="POST"
                              class="d-inline"
                              onsubmit="return confirm('Yakin ingin menghapus Jurusan ' + <?= htmlspecialchars(json_encode($dept['name'], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?> + '?');">
                            <button type="submit" class="btn btn-sm btn-subtle text-danger">
                                <i class="bi bi-trash3 me-1"></i> Hapus
                            </button>
                        </form>
                    </div>
                </div>

                <div class="p-0">
                    <?php if (empty($prodiList)): ?>
                        <div class="p-4 text-center text-muted small">
                            <i class="bi bi-info-circle me-1"></i>
                            Belum ada Program Studi di jurusan ini.
                            <a href="<?= BASE_URL ?>/prodi/create?department_id=<?= urlencode($dept['id']) ?>" class="fw-semibold text-success ms-1">
                                Tambah Prodi sekarang
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-minimal mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 110px;">Kode</th>
                                        <th style="width: 120px;">Jenjang</th>
                                        <th>Nama Program Studi</th>
                                        <th class="text-center" style="width: 150px;">Dosen Mengampu</th>
                                        <th class="text-center" style="width: 140px;">Total Civitas</th>
                                        <th class="text-end" style="width: 150px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($prodiList as $pr): ?>
                                        <tr>
                                            <td>
                                                <?php if (!empty($pr['code'])): ?>
                                                    <code class="fw-semibold px-2 py-1 rounded"
                                                          style="background: #f8fafc; color: #1e293b; border: 1px solid #e2e8f0; font-size: 0.8rem;">
                                                        <?= htmlspecialchars($pr['code']) ?>
                                                    </code>
                                                <?php else: ?>
                                                    <span class="text-muted small">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($pr['degree'])): ?>
                                                    <span class="badge px-2 py-1 fw-semibold"
                                                          style="background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd;">
                                                        <?= htmlspecialchars($pr['degree']) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted small">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="fw-medium text-dark"><?= htmlspecialchars($pr['name']) ?></td>
                                            <td class="text-center">
                                                <span class="badge rounded-pill px-2 py-1"
                                                      style="background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0;">
                                                    <?= (int) ($pr['dosen_count'] ?? 0) ?> Dosen
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge rounded-pill bg-light text-secondary border px-2 py-1">
                                                    <?= (int) ($pr['civitas_count'] ?? 0) ?> Civitas
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <div class="d-inline-flex gap-1">
                                                    <a href="<?= BASE_URL ?>/prodi/<?= htmlspecialchars($pr['id']) ?>/edit"
                                                       class="btn btn-subtle" style="padding: 0.25rem 0.55rem;"
                                                       title="Ubah Prodi">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </a>
                                                    <form action="<?= BASE_URL ?>/prodi/<?= htmlspecialchars($pr['id']) ?>/delete"
                                                          method="POST"
                                                          class="d-inline"
                                                          onsubmit="return confirm('Yakin ingin menghapus Program Studi ' + <?= htmlspecialchars(json_encode($pr['name'], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?> + '?');">
                                                        <button type="submit" class="btn btn-subtle text-danger" style="padding: 0.25rem 0.55rem;" title="Hapus Prodi">
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
