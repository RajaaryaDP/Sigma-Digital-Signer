<?php
require_once __DIR__ . '/../layout/header.php';

$formatBytes = static function (int $bytes): string {
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    }
    if ($bytes >= 1024) {
        return number_format($bytes / 1024, 1) . ' KB';
    }
    return $bytes . ' B';
};

$activeCategory = $activeCategory ?? 'all';
$categories     = $categories ?? DocumentTemplatesModel::getCategories();
$categoryCounts = $categoryCounts ?? ['all' => count($templates)];

$categoryMeta = [
    'surat' => [
        'label' => $categories['surat'] ?? 'Surat & Permohonan',
        'icon'  => 'bi-envelope-paper',
        'bg'    => '#f3f0ff',
        'color' => '#5b21b6',
    ],
    'akademik' => [
        'label' => $categories['akademik'] ?? 'Akademik & Tugas Akhir',
        'icon'  => 'bi-mortarboard',
        'bg'    => '#ecfdf5',
        'color' => '#047857',
    ],
    'lainnya' => [
        'label' => $categories['lainnya'] ?? 'Administrasi Lainnya',
        'icon'  => 'bi-folder2-open',
        'bg'    => '#f1f5f9',
        'color' => '#475569',
    ],
];

$buildFilterUrl = static function (string $catKey) use ($search): string {
    $params = [];
    if ($search !== '') {
        $params['q'] = $search;
    }
    if ($catKey !== 'all') {
        $params['category'] = $catKey;
    }
    $qs = http_build_query($params);
    return BASE_URL . '/document-templates' . ($qs !== '' ? '?' . $qs : '');
};
?>

<!-- Page Header & Action Bar -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <div class="text-uppercase fw-bold mb-1" style="font-size: 0.72rem; letter-spacing: 0.08em; color: var(--sigma-purple-600);">
            Pustaka Dokumen
        </div>
        <h4 class="fw-bold mb-1" style="color: var(--text-heading);">
            <i class="bi bi-file-earmark-word me-2 text-sigma-purple"></i>Template Dokumen Resmi
        </h4>
        <p class="small mb-0" style="color: var(--text-muted);">
            Unduh contoh format dokumen resmi kampus (<strong>.docx</strong>) untuk disesuaikan di perangkat Anda sebelum diajukan tanda tangan digital.
        </p>
    </div>
    <?php if (!empty($isAdmin)): ?>
        <div>
            <a href="<?= BASE_URL ?>/document-templates/create" class="btn-sigma-navy text-decoration-none">
                <i class="bi bi-plus-circle"></i>
                <span>Tambah Template</span>
            </a>
        </div>
    <?php endif; ?>
</div>

<!-- 3-Step Guide Banner -->
<div class="sigma-guide-banner mb-4">
    <div class="d-flex align-items-center gap-2 mb-3">
        <i class="bi bi-lightbulb-fill text-sigma-purple"></i>
        <span class="fw-bold small" style="color: var(--text-heading);">
            <?= !empty($isAdmin) ? 'Panduan Pengelolaan & Alur Template Dokumen (.docx)' : '3 Langkah Menggunakan Template Dokumen (.docx)' ?>
        </span>
    </div>
    <div class="row g-3">
        <div class="col-md-4">
            <div class="d-flex align-items-start gap-2">
                <span class="badge-sigma badge-sigma-purple flex-shrink-0">1</span>
                <div class="small" style="color: var(--text-body); line-height: 1.5;">
                    <?php if (!empty($isAdmin)): ?>
                        <strong style="color: var(--text-heading);">Unggah .docx Baku</strong> — Tambahkan atau perbarui template format Microsoft Word resmi kampus.
                    <?php else: ?>
                        <strong style="color: var(--text-heading);">Unduh Format .docx</strong> — Pilih kartu template sesuai kebutuhan surat lalu klik tombol Unduh.
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="d-flex align-items-start gap-2">
                <span class="badge-sigma badge-sigma-purple flex-shrink-0">2</span>
                <div class="small" style="color: var(--text-body); line-height: 1.5;">
                    <strong style="color: var(--text-heading);">Lengkapi &amp; Ekspor PDF</strong> — Isi biodata dan konten surat secara lokal, lalu simpan/ekspor ke format <strong>PDF</strong>.
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="d-flex align-items-start gap-2">
                <span class="badge-sigma badge-sigma-purple flex-shrink-0">3</span>
                <div class="small" style="color: var(--text-body); line-height: 1.5;">
                    <strong style="color: var(--text-heading);">Ajukan Tanda Tangan</strong> — Unggah berkas PDF melalui menu
                    <a href="<?= BASE_URL ?>/documents/create" class="fw-semibold text-decoration-none" style="color: var(--sigma-violet-600);">Ajukan Dokumen</a>.
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Search, UI Filter Chips, & Sort Bar -->
<div class="card-sigma mb-4">
    <div class="card-sigma-body p-3">
        <form action="<?= BASE_URL ?>/document-templates" method="GET" class="row g-2 align-items-center mb-3">
            <?php if ($activeCategory !== 'all'): ?>
                <input type="hidden" name="category" value="<?= htmlspecialchars($activeCategory) ?>">
            <?php endif; ?>
            <div class="col-lg-6">
                <div class="sigma-search-box">
                    <i class="bi bi-search sigma-search-icon"></i>
                    <input type="text"
                           name="q"
                           class="form-control"
                           placeholder="Cari template berdasarkan nama..."
                           maxlength="200"
                           value="<?= htmlspecialchars($search) ?>">
                </div>
            </div>
            <div class="col-auto d-flex align-items-center gap-2">
                <button type="submit" class="btn-sigma-navy btn-sigma-sm px-3">Cari</button>
                <?php if ($search !== '' || $activeCategory !== 'all'): ?>
                    <a href="<?= BASE_URL ?>/document-templates" class="btn-sigma-outline btn-sigma-sm text-decoration-none" title="Reset pencarian dan kategori">
                        <i class="bi bi-x-lg"></i> Reset
                    </a>
                <?php endif; ?>
            </div>
            <div class="col-lg ms-lg-auto d-flex align-items-center justify-content-lg-end gap-2">
                <label for="templateSortSelect" class="small fw-semibold text-nowrap mb-0" style="color: var(--text-muted);">Urutkan:</label>
                <select id="templateSortSelect" class="form-select form-select-sm sigma-input" style="max-width: 210px;">
                    <option value="updated">Terbaru Diperbarui</option>
                    <option value="name_asc">Nama (A – Z)</option>
                    <option value="size_asc">Ukuran Terkecil</option>
                </select>
            </div>
        </form>

        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pt-2 border-top" style="border-color: var(--border-subtle) !important;">
            <div class="filter-chips-sigma" id="templateFilterChips" role="navigation" aria-label="Filter kategori template dokumen">
                <a href="<?= htmlspecialchars($buildFilterUrl('all')) ?>"
                   class="filter-chip-sigma text-decoration-none <?= $activeCategory === 'all' ? 'active' : '' ?>">
                    Semua Template <span class="ms-1 opacity-75">(<?= (int) ($categoryCounts['all'] ?? 0) ?>)</span>
                </a>
                <?php foreach ($categories as $catKey => $catLabel): ?>
                    <a href="<?= htmlspecialchars($buildFilterUrl($catKey)) ?>"
                       class="filter-chip-sigma text-decoration-none <?= $activeCategory === $catKey ? 'active' : '' ?>">
                        <?= htmlspecialchars($catLabel) ?> <span class="ms-1 opacity-75">(<?= (int) ($categoryCounts[$catKey] ?? 0) ?>)</span>
                    </a>
                <?php endforeach; ?>
            </div>

            <div class="d-flex align-items-center gap-2">
                <span class="badge-sigma badge-sigma-purple" id="templateCountBadge">
                    <i class="bi bi-collection"></i>
                    <span id="templateVisibleCount"><?= count($templates) ?></span> Template <?= ($search !== '' || $activeCategory !== 'all') ? 'Ditemukan' : 'Tersedia' ?>
                </span>
                <?php if ($search !== ''): ?>
                    <span class="small text-truncate" style="color: var(--text-muted); max-width: 220px;">
                        Kata kunci: <strong style="color: var(--text-heading);">"<?= htmlspecialchars($search) ?>"</strong>
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Templates Grid / Empty State -->
<?php if (empty($templates)): ?>
    <?php $hasActiveFilter = ($search !== '' || $activeCategory !== 'all'); ?>
    <div class="card-sigma p-5 text-center my-4">
        <div class="rounded-circle d-inline-flex align-items-center justify-content-center mx-auto mb-3"
             style="width: 72px; height: 72px; background: var(--sigma-tint-100); color: var(--sigma-purple-700); font-size: 2rem;">
            <i class="bi <?= $hasActiveFilter ? 'bi-search' : 'bi-file-earmark-word' ?>"></i>
        </div>
        <h5 class="fw-bold mb-2" style="color: var(--text-heading);">
            <?= $hasActiveFilter ? 'Template Dokumen Tidak Ditemukan' : 'Belum Ada Template Dokumen' ?>
        </h5>
        <p class="small mx-auto mb-4" style="max-width: 520px; color: var(--text-muted);">
            <?php if ($search !== '' && $activeCategory !== 'all'): ?>
                Tidak ada template pada kategori <strong><?= htmlspecialchars($categories[$activeCategory] ?? $activeCategory) ?></strong> dengan nama yang mengandung <strong>"<?= htmlspecialchars($search) ?>"</strong>.
            <?php elseif ($search !== ''): ?>
                Tidak ada template dokumen dengan nama yang mengandung <strong>"<?= htmlspecialchars($search) ?>"</strong>. Coba kata kunci lain atau tampilkan semua template.
            <?php elseif ($activeCategory !== 'all'): ?>
                Belum ada template dokumen pada kategori <strong><?= htmlspecialchars($categories[$activeCategory] ?? $activeCategory) ?></strong> saat ini.
            <?php elseif (!empty($isAdmin)): ?>
                Belum ada contoh template dokumen yang diunggah. Tambahkan berkas Word (.docx) pertama agar civitas kampus dapat mengunduh format surat resmi dengan mudah.
            <?php else: ?>
                Administrator belum menambahkan template dokumen resmi saat ini. Silakan periksa kembali nanti.
            <?php endif; ?>
        </p>
        <div>
            <?php if ($hasActiveFilter): ?>
                <a href="<?= BASE_URL ?>/document-templates" class="btn-sigma-outline px-4 py-2 text-decoration-none">
                    <i class="bi bi-arrow-counterclockwise"></i> Tampilkan Semua Template
                </a>
            <?php elseif (!empty($isAdmin)): ?>
                <a href="<?= BASE_URL ?>/document-templates/create" class="btn-sigma-navy px-4 py-2 text-decoration-none">
                    <i class="bi bi-plus-lg"></i> Tambah Template Pertama
                </a>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>
    <div class="row g-4" id="templateCardsGrid">
        <?php foreach ($templates as $idx => $tpl): ?>
            <?php
                $tplCat    = isset($tpl['category'], $categoryMeta[$tpl['category']]) ? $tpl['category'] : 'lainnya';
                $catStyle  = $categoryMeta[$tplCat];
                $updatedTs = strtotime($tpl['updated_at'] ?? 'now') ?: 0;
            ?>
            <div class="col-md-6 col-lg-4 template-grid-item"
                 data-category="<?= htmlspecialchars($tplCat) ?>"
                 data-name="<?= htmlspecialchars(mb_strtolower($tpl['name'] ?? '')) ?>"
                 data-updated="<?= (int) $updatedTs ?>"
                 data-size="<?= (int) ($tpl['file_size'] ?? 0) ?>">
                <div class="template-card-sigma h-100">
                    <!-- Top Card Badges -->
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0"
                                 style="width: 38px; height: 38px; background: #eff6ff; color: #1d4ed8; border: 1px solid #dbeafe;">
                                <i class="bi bi-file-earmark-word-fill fs-5"></i>
                            </div>
                            <span class="badge-sigma" style="background: #eff6ff; color: #1d4ed8;">DOCX</span>
                            <span class="badge-sigma"
                                  style="background: <?= $catStyle['bg'] ?>; color: <?= $catStyle['color'] ?>;">
                                <i class="bi <?= $catStyle['icon'] ?>"></i>
                                <?= htmlspecialchars($catStyle['label']) ?>
                            </span>
                        </div>
                        <span class="badge-sigma badge-sigma-neutral">
                            <?= htmlspecialchars($formatBytes((int) ($tpl['file_size'] ?? 0))) ?>
                        </span>
                    </div>

                    <!-- Title & Description -->
                    <div class="flex-grow-1">
                        <h6 class="fw-bold mb-2" style="color: var(--text-heading); line-height: 1.4;">
                            <?= htmlspecialchars($tpl['name']) ?>
                        </h6>
                        <?php if (!empty($tpl['description'])): ?>
                            <p class="small mb-3" style="color: var(--text-muted); line-height: 1.55;">
                                <?= nl2br(htmlspecialchars($tpl['description'])) ?>
                            </p>
                        <?php else: ?>
                            <p class="small mb-3 fst-italic" style="color: var(--text-muted);">
                                Format resmi siap pakai untuk disesuaikan sebelum ekspor PDF.
                            </p>
                        <?php endif; ?>
                    </div>

                    <!-- File Meta -->
                    <div class="pt-3 mt-2 border-top mb-3" style="border-color: var(--border-subtle) !important;">
                        <div class="small text-truncate mb-1" style="font-size: 0.76rem; color: var(--text-body);" title="<?= htmlspecialchars($tpl['file_name_original']) ?>">
                            <i class="bi bi-paperclip me-1 text-sigma-purple"></i><?= htmlspecialchars($tpl['file_name_original']) ?>
                        </div>
                        <div class="small" style="font-size: 0.74rem; color: var(--text-muted);">
                            <i class="bi bi-clock-history me-1"></i>Diperbarui: <?= date('d M Y, H:i', strtotime($tpl['updated_at'])) ?> WIB
                            <?php if (!empty($tpl['updated_by_name'])): ?>
                                &bull; <?= htmlspecialchars($tpl['updated_by_name']) ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Actions Footer (No Web Preview per Task 13) -->
                    <div class="d-flex align-items-center gap-2">
                        <a href="<?= BASE_URL ?>/document-templates/<?= htmlspecialchars($tpl['id']) ?>/download"
                           class="btn-sigma-navy btn-sigma-sm flex-grow-1 justify-content-center text-decoration-none"
                           title="Unduh berkas Word (.docx)">
                            <i class="bi bi-download"></i>
                            <span>Unduh Template</span>
                        </a>

                        <?php if (!empty($isAdmin)): ?>
                            <a href="<?= BASE_URL ?>/document-templates/<?= htmlspecialchars($tpl['id']) ?>/edit"
                               class="btn-sigma-outline btn-sigma-sm text-decoration-none"
                               title="Ubah Template">
                                <i class="bi bi-pencil-square"></i>
                            </a>
                            <button type="button"
                                    class="btn-sigma-outline btn-sigma-sm text-danger btn-delete-template"
                                    style="border-color: #fecaca;"
                                    data-bs-toggle="modal"
                                    data-bs-target="#deleteTemplateModal"
                                    data-template-name="<?= htmlspecialchars($tpl['name']) ?>"
                                    data-delete-url="<?= BASE_URL ?>/document-templates/<?= htmlspecialchars($tpl['id']) ?>/delete"
                                    title="Hapus Permanen Template">
                                <i class="bi bi-trash3"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const grid = document.getElementById('templateCardsGrid');
            const sortSelect = document.getElementById('templateSortSelect');

            function applySort() {
                if (!grid || !sortSelect) return;
                const items = Array.from(grid.querySelectorAll('.template-grid-item'));
                const sortVal = sortSelect.value || 'updated';

                items.sort(function (a, b) {
                    if (sortVal === 'name_asc') {
                        return (a.dataset.name || '').localeCompare(b.dataset.name || '');
                    }
                    if (sortVal === 'size_asc') {
                        return (parseInt(a.dataset.size || '0', 10)) - (parseInt(b.dataset.size || '0', 10));
                    }
                    return (parseInt(b.dataset.updated || '0', 10)) - (parseInt(a.dataset.updated || '0', 10));
                });

                items.forEach(function (item) {
                    grid.appendChild(item);
                });
            }

            if (sortSelect) {
                sortSelect.addEventListener('change', applySort);
            }
        });
    </script>
<?php endif; ?>

<?php if (!empty($isAdmin)): ?>
    <!-- Modal Konfirmasi Hard Delete (Admin) -->
    <div class="modal fade modal-sigma" id="deleteTemplateModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header py-3 px-4">
                    <h6 class="modal-title fw-bold text-danger d-flex align-items-center gap-2 mb-0">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <span>Hapus Permanen Template</span>
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="mb-2" style="color: var(--text-heading);">
                        Apakah Anda yakin ingin menghapus template dokumen <strong id="deleteTemplateNameText"></strong>?
                    </p>
                    <div class="alert alert-danger border-0 py-2 px-3 small mb-0">
                        <i class="bi bi-info-circle-fill me-1"></i>
                        Penghapusan bersifat <strong>permanen (hard delete)</strong>: data template serta berkas fisik <code>.docx</code> akan dihapus dari server dan tidak dapat dikembalikan.
                    </div>
                </div>
                <div class="modal-footer py-2 px-4">
                    <button type="button" class="btn-sigma-outline btn-sigma-sm" data-bs-dismiss="modal">Batal</button>
                    <form id="deleteTemplateForm" action="" method="POST" class="m-0">
                        <?= Csrf::field() ?>
                        <button type="submit" id="btnConfirmHardDelete" class="btn-sigma-danger btn-sigma-sm px-3">
                            <i class="bi bi-trash3"></i>
                            <span>Ya, Hapus Permanen</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const deleteForm = document.getElementById('deleteTemplateForm');
            const deleteNameEl = document.getElementById('deleteTemplateNameText');
            const confirmBtn = document.getElementById('btnConfirmHardDelete');

            document.querySelectorAll('.btn-delete-template').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const tplName = btn.getAttribute('data-template-name') || 'Template';
                    const deleteUrl = btn.getAttribute('data-delete-url') || '';
                    deleteNameEl.textContent = '"' + tplName + '"';
                    deleteForm.setAttribute('action', deleteUrl);
                });
            });

            if (deleteForm && confirmBtn) {
                deleteForm.addEventListener('submit', function () {
                    confirmBtn.disabled = true;
                    confirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span><span>Menghapus...</span>';
                });
            }
        });
    </script>
<?php endif; ?>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
