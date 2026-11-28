<?php
global $page, $action, $conn;
$currentUser   = $_SESSION['user'] ?? null;
$isAdmin       = ($currentUser['role'] ?? '') === 'admin';
$isKajur       = UsersModel::isKajur($currentUser);
$civitasType   = $currentUser['civitas_type'] ?? 'civitas';
$currentAction = $action ?? 'index';
$isFocusMode   = ($currentUser && $page === 'documents' && in_array($currentAction, ['arrange', 'sign'], true));

// Hitung jumlah antrean aksi secara live (tanpa tabel notifikasi, ADR-02)
$pendingQueueCount = 0;
if ($currentUser && isset($conn) && $conn instanceof PDO) {
    try {
        if ($isAdmin) {
            $stmtCount = $conn->prepare("SELECT COUNT(*) FROM documents WHERE status = 'pending_review' AND deleted_at IS NULL");
            $stmtCount->execute();
            $pendingQueueCount = (int) $stmtCount->fetchColumn();
        } else {
            $stmtCount = $conn->prepare(
                "SELECT COUNT(*) FROM document_signers ds
                 JOIN documents d ON ds.document_id = d.id
                 WHERE ds.user_id = ? AND ds.status = 'pending'
                   AND d.status = 'signing' AND d.deleted_at IS NULL
                   AND ds.deleted_at IS NULL"
            );
            $stmtCount->execute([$currentUser['id']]);
            $pendingQueueCount = (int) $stmtCount->fetchColumn();
        }
    } catch (Throwable $e) {
        $pendingQueueCount = 0;
    }
}

// Susun label breadcrumb ruang kerja sesuai rute aktif
$breadcrumbMap = [
    'documents' => match ($currentAction) {
        'create'  => 'Ajukan Dokumen',
        'detail'  => 'Detail & Pratinjau Dokumen',
        'arrange' => 'Tata Letak Slot Tanda Tangan',
        'sign'    => 'Penandatanganan Dokumen',
        default   => (($_GET['status'] ?? '') === 'pending_review'
            ? 'Antrean Review Admin'
            : (($_GET['view'] ?? '') === 'queue'
                ? 'Antrean Tanda Tangan'
                : (($_GET['view'] ?? '') === 'all' || ($_GET['scope'] ?? '') === 'campus'
                    ? 'Daftar Seluruh Dokumen Kampus'
                    : ($isAdmin ? 'Ringkasan Dokumen' : 'Dashboard Dokumen')))),
    },
    'civitas' => match ($currentAction) {
        'create' => 'Tambah Data Civitas',
        'edit'   => 'Ubah Data Civitas',
        'import' => 'Impor CSV Civitas',
        default  => 'Master Data Civitas',
    },
    'departments' => match ($currentAction) {
        'create' => 'Tambah Jurusan',
        'edit'   => 'Ubah Jurusan',
        default  => 'Jurusan & Program Studi',
    },
    'prodi' => match ($currentAction) {
        'create' => 'Tambah Program Studi',
        'edit'   => 'Ubah Program Studi',
        default  => 'Program Studi',
    },
    'user-signatures' => match ($currentAction) {
        'create' => 'Buat Template TTD',
        default  => 'Template TTD Pribadi',
    },
    'document-templates' => match ($currentAction) {
        'create' => 'Tambah Template Dokumen',
        'edit'   => 'Edit Template Dokumen',
        default  => 'Template Dokumen (.docx)',
    },
    'verify' => 'Verifikasi Publik',
];
$breadcrumbCurrent = $breadcrumbMap[$page] ?? 'SIGMA PNJ';
$roleDisplay = $isAdmin
    ? 'Administrator'
    : (!empty($currentUser['position']) ? $currentUser['position'] : ucfirst((string) $civitasType));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'SIGMA | Politeknik Negeri Jakarta') ?></title>
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/img/logo.png">
    <!-- Google Fonts: Inter, Irish Grover (Wordmark), Roboto Mono (Hash SHA-256) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Irish+Grover&family=Roboto+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- SIGMA Design System Stylesheet -->
    <link href="<?= BASE_URL ?>/assets/css/style.css?v=<?= @filemtime(__DIR__ . '/../../assets/css/style.css') ?: '2' ?>" rel="stylesheet">
    <?php if (!$currentUser): ?>
        <link href="<?= BASE_URL ?>/assets/css/sigma-footer.css" rel="stylesheet">
    <?php endif; ?>
</head>
<body class="<?= $isFocusMode ? 'sigma-focus-mode' : '' ?>">

<?php if ($currentUser): ?>
    <div class="sigma-app-shell">
        <?php require __DIR__ . '/sidebar.php'; ?>
        <div class="sigma-sidebar-backdrop" id="sigmaSidebarBackdrop"></div>

        <div class="sigma-main-shell">
            <header class="sigma-workspace-header">
                <div class="d-flex align-items-center gap-2 min-w-0">
                    <button type="button"
                            id="sigmaSidebarToggle"
                            class="btn btn-sigma-outline btn-sigma-sm <?= $isFocusMode ? '' : 'd-lg-none' ?>"
                            aria-label="Buka atau tutup menu samping"
                            title="<?= $isFocusMode ? 'Tampilkan/Sembunyikan Sidebar' : 'Menu Navigasi' ?>">
                        <i class="bi bi-list fs-6"></i>
                    </button>
                    <nav class="sigma-breadcrumb text-truncate" aria-label="Breadcrumb">
                        <span>Ruang kerja</span>
                        <i class="bi bi-chevron-right" style="font-size: 0.72rem;"></i>
                        <strong class="text-truncate"><?= htmlspecialchars($breadcrumbCurrent) ?></strong>
                    </nav>
                </div>

                <div class="sigma-header-actions">
                    <?php
                    $queueHref  = $isAdmin ? BASE_URL . '/documents?status=pending_review' : BASE_URL . '/documents?view=queue';
                    $queueTitle = $isAdmin
                        ? ($pendingQueueCount > 0 ? $pendingQueueCount . ' dokumen menunggu review Admin' : 'Tidak ada antrean review')
                        : ($pendingQueueCount > 0 ? $pendingQueueCount . ' dokumen menunggu tanda tangan Anda' : 'Tidak ada antrean tanda tangan');
                    ?>
                    <a href="<?= $queueHref ?>" class="sigma-bell-pill" title="<?= htmlspecialchars($queueTitle) ?>">
                        <i class="bi bi-bell"></i>
                        <?php if ($pendingQueueCount > 0): ?>
                            <span class="sigma-bell-dot"><?= $pendingQueueCount ?></span>
                        <?php endif; ?>
                    </a>

                    <div class="d-none d-sm-flex align-items-center gap-2 ps-2 border-start" style="border-color: var(--border-subtle) !important;">
                        <div class="text-end" style="line-height: 1.2;">
                            <div class="fw-semibold text-truncate" style="font-size: 0.82rem; color: var(--text-heading); max-width: 170px;">
                                <?= htmlspecialchars($currentUser['name'] ?? '') ?>
                            </div>
                            <div style="font-size: 0.7rem; color: var(--text-muted);">
                                <?= htmlspecialchars($roleDisplay) ?>
                                <?php if (!empty($currentUser['identifier'])): ?>
                                    &bull; <?= htmlspecialchars($currentUser['identifier']) ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <main class="sigma-workspace-content">
<?php else: ?>
    <!-- Mode Publik / Tamu (mis. hasil /verify?token=... tanpa login) -->
    <header class="pnj-navbar">
        <div class="pnj-navbar-inner">
            <a href="<?= BASE_URL ?>/" class="pnj-brand" title="Halaman Utama SIGMA PNJ">
                <img src="<?= BASE_URL ?>/assets/img/logo.png" alt="SIGMA" class="pnj-brand-logo" width="38" height="38">
                <div class="pnj-brand-text">
                    <span class="pnj-brand-title">SIGMA</span>
                    <span class="pnj-brand-sub">Tanda Tangan Digital</span>
                </div>
            </a>

            <nav aria-label="Navigasi halaman utama">
                <ul class="pnj-nav-menu">
                    <li>
                        <a href="<?= BASE_URL ?>/#beranda" class="pnj-nav-link">
                            <span>Beranda</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?= BASE_URL ?>/#verifikasi" class="pnj-nav-link <?= ($page === 'verify') ? 'active' : '' ?>">
                            <span>Verifikasi</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?= BASE_URL ?>/#guide" class="pnj-nav-link">
                            <span>Guide</span>
                        </a>
                    </li>
                </ul>
            </nav>

            <div class="pnj-user-widget">
                <a href="<?= BASE_URL ?>/auth" class="btn-pnj-login-taskbar" title="Masuk ke Akun Civitas SIGMA PNJ">
                    <span>Login</span>
                </a>
            </div>
        </div>
    </header>

    <main class="main-container">
<?php endif; ?>

<?php if (!empty($_SESSION['flash'])): ?>
    <?php
        $flash     = $_SESSION['flash'];
        $rawType   = (string) ($flash['type'] ?? 'info');
        $flashType = in_array($rawType, ['success', 'danger', 'warning', 'info'], true) ? $rawType : 'info';
        $flashIcons = [
            'success' => 'bi-check-circle-fill',
            'danger'  => 'bi-exclamation-triangle-fill',
            'warning' => 'bi-exclamation-circle-fill',
            'info'    => 'bi-info-circle-fill',
        ];
        $flashIcon = $flashIcons[$flashType] ?? 'bi-info-circle-fill';
        unset($_SESSION['flash']);
    ?>
    <div class="alert alert-<?= $flashType ?> alert-sigma alert-dismissible fade show d-flex align-items-center gap-2 mb-4" role="alert">
        <i class="bi <?= $flashIcon ?>"></i>
        <div class="small fw-medium flex-grow-1"><?= htmlspecialchars((string) ($flash['message'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
<?php endif; ?>
