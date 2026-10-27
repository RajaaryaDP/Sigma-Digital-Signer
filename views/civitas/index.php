<?php
require_once __DIR__ . '/../layout/header.php';
?>

<!-- Title & Action Button -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Master Data Civitas Kampus</h4>
        <p class="text-muted small mb-0">Direktori profil resmi Dosen, Mahasiswa, dan Tenaga Kependidikan untuk validasi QR Code & Tanda Tangan.</p>
    </div>
    <div>
        <a href="<?= BASE_URL ?>/civitas/create" class="btn btn-pnj-primary d-inline-flex align-items-center gap-2">
            <i class="bi bi-person-plus-fill"></i>
            <span>Tambah Data Civitas</span>
        </a>
    </div>
</div>

<!-- Category Tabs & Search Bar -->
<div class="card-clean mb-4">
    <div class="card-clean-body p-3">
        <div class="row g-3 align-items-center justify-content-between">
            <!-- Filter Pills -->
            <div class="col-lg-7">
                <div class="d-flex flex-wrap gap-1.5">
                    <a href="<?= BASE_URL ?>/civitas<?= !empty($search) ? '?q=' . urlencode($search) : '' ?>" 
                       class="btn btn-sm <?= empty($currentType) ? 'btn-pnj-primary' : 'btn-subtle' ?>">
                        Semua <span class="badge bg-white text-dark ms-1"><?= $counts['total'] ?></span>
                    </a>
                    <a href="<?= BASE_URL ?>/civitas?type=dosen<?= !empty($search) ? '&q=' . urlencode($search) : '' ?>" 
                       class="btn btn-sm <?= ($currentType === 'dosen') ? 'btn-pnj-primary' : 'btn-subtle' ?>">
                        Dosen <span class="badge bg-white text-dark ms-1"><?= $counts['dosen'] ?></span>
                    </a>
                    <a href="<?= BASE_URL ?>/civitas?type=mahasiswa<?= !empty($search) ? '&q=' . urlencode($search) : '' ?>" 
                       class="btn btn-sm <?= ($currentType === 'mahasiswa') ? 'btn-pnj-primary' : 'btn-subtle' ?>">
                        Mahasiswa <span class="badge bg-white text-dark ms-1"><?= $counts['mahasiswa'] ?></span>
                    </a>
                    <a href="<?= BASE_URL ?>/civitas?type=tendik<?= !empty($search) ? '&q=' . urlencode($search) : '' ?>" 
                       class="btn btn-sm <?= ($currentType === 'tendik') ? 'btn-pnj-primary' : 'btn-subtle' ?>">
                        Tendik <span class="badge bg-white text-dark ms-1"><?= $counts['tendik'] ?></span>
                    </a>
                </div>
            </div>

            <!-- Search Form -->
            <div class="col-lg-5">
                <form method="GET" action="<?= BASE_URL ?>/civitas" class="d-flex gap-2">
                    <?php if (!empty($currentType)): ?>
                        <input type="hidden" name="type" value="<?= htmlspecialchars($currentType) ?>">
                    <?php endif; ?>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted border-end-0">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" name="q" class="form-control border-start-0" 
                               placeholder="Cari NIM/NIP, nama, atau jurusan..." 
                               value="<?= htmlspecialchars($search ?? '') ?>">
                    </div>
                    <button type="submit" class="btn btn-subtle px-3">Cari</button>
                    <?php if (!empty($search)): ?>
                        <a href="<?= BASE_URL ?>/civitas<?= !empty($currentType) ? '?type=' . urlencode($currentType) : '' ?>" 
                           class="btn btn-outline-secondary" title="Hapus pencarian">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Civitas Table Card -->
<div class="card-clean mb-4">
    <?php if (empty($civitasList)): ?>
        <div class="p-5 text-center">
            <div class="text-muted mb-2" style="font-size: 2.5rem; opacity: 0.4;">
                <i class="bi bi-person-x"></i>
            </div>
            <h6 class="fw-semibold text-dark mb-1">Tidak Ada Data Civitas yang Ditemukan</h6>
            <p class="text-muted small mb-3">
                <?= !empty($search) || !empty($currentType) 
                    ? 'Tidak ada data civitas yang cocok dengan filter atau kata kunci pencarian Anda.' 
                    : 'Belum ada data civitas kampus yang tersimpan di sistem.' ?>
            </p>
            <?php if (!empty($search) || !empty($currentType)): ?>
                <a href="<?= BASE_URL ?>/civitas" class="btn btn-subtle btn-sm">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Filter
                </a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/civitas/create" class="btn btn-pnj-primary btn-sm">
                    <i class="bi bi-plus-lg me-1"></i> Tambah Data Sekarang
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-minimal mb-0">
                <thead>
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th>NIM / NIP</th>
                        <th>Nama & Jabatan</th>
                        <th>Kategori</th>
                        <th>Jurusan / Unit</th>
                        <th>Kontak</th>
                        <th style="width: 140px; text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    $typeBadges = [
                        'dosen'     => 'background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0;',
                        'mahasiswa' => 'background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd;',
                        'tendik'    => 'background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1;',
                    ];
                    foreach ($civitasList as $c): 
                        $badgeStyle = $typeBadges[$c['type']] ?? 'background: #f1f5f9; color: #475569;';
                    ?>
                        <tr>
                            <td class="text-muted small"><?= $no++ ?></td>
                            <td>
                                <code class="fw-semibold px-2 py-1 rounded" style="background: #f8fafc; color: #1e293b; border: 1px solid #e2e8f0; font-size: 0.82rem;">
                                    <?= htmlspecialchars($c['identifier']) ?>
                                </code>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark"><?= htmlspecialchars($c['name']) ?></div>
                                <div class="text-muted small" style="font-size: 0.78rem;">
                                    <?= htmlspecialchars($c['position']) ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge px-2 py-1 text-uppercase fw-semibold" style="<?= $badgeStyle ?>; font-size: 0.7rem;">
                                    <?= htmlspecialchars($c['type']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="small text-secondary"><?= htmlspecialchars($c['department']) ?></span>
                            </td>
                            <td>
                                <div class="small text-dark"><?= htmlspecialchars($c['email']) ?></div>
                                <?php if (!empty($c['phone'])): ?>
                                    <div class="text-muted small" style="font-size: 0.75rem;">
                                        <i class="bi bi-telephone me-1"></i><?= htmlspecialchars($c['phone']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right;">
                                <div class="d-inline-flex gap-1.5">
                                    <a href="<?= BASE_URL ?>/civitas/<?= htmlspecialchars($c['id']) ?>/edit" 
                                       class="btn btn-subtle" style="padding: 0.25rem 0.55rem;" title="Ubah Data">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <button type="button" class="btn btn-subtle text-danger" style="padding: 0.25rem 0.55rem;" 
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
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <div class="modal-content border-0 shadow" style="border-radius: var(--radius-md);">
            <div class="modal-body p-4 text-center">
                <div class="rounded-circle d-inline-flex p-3 mb-3" style="background: #fee2e2; color: #dc2626;">
                    <i class="bi bi-exclamation-triangle fs-2"></i>
                </div>
                <h5 class="fw-bold text-dark mb-1">Konfirmasi Hapus Civitas</h5>
                <p class="text-muted small mb-3">
                    Apakah Anda yakin ingin menghapus data civitas berikut?
                </p>
                <div class="p-3 bg-light rounded-3 border text-start mb-4">
                    <div class="small text-muted mb-0.5">Nama Civitas:</div>
                    <div class="fw-bold text-dark" id="modalDeleteName">-</div>
                    <div class="small text-muted mt-2 mb-0.5">NIM / NIP:</div>
                    <div class="fw-bold text-dark" id="modalDeleteIdentifier">-</div>
                </div>
                <p class="text-muted" style="font-size: 0.78rem;">
                    * Data akan dihapus secara soft-delete dan tidak lagi muncul pada daftar pemilih tanda tangan.
                </p>

                <form id="deleteForm" method="POST" action="">
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-subtle w-50 py-2" data-bs-dismiss="modal">
                            Batal
                        </button>
                        <button type="submit" class="btn btn-danger w-50 py-2 fw-semibold">
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
