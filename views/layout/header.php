<?php
global $page;
$currentUser = $_SESSION['user'] ?? null;
$isAdmin = ($currentUser['role'] ?? '') === 'admin';
$civitasType = $currentUser['civitas_type'] ?? 'civitas';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'SIGMA — Sistem Tanda Tangan Digital') ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Custom Design Tokens & Styles -->
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>

    <div class="app-shell" id="appShell">

        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <a href="<?= BASE_URL ?>/documents" class="sidebar-brand">
                <span class="brand-mark"><i class="bi bi-shield-shaded"></i></span>
                <div class="d-flex flex-column">
                    <span class="brand-title">SIGMA</span>
                    <span class="brand-subtitle">Digital Signer</span>
                </div>
            </a>

            <div class="sidebar-profile-card">
                <div class="avatar-circle">
                    <?= strtoupper(substr($currentUser['name'] ?? 'U', 0, 1)) ?>
                </div>
                <div class="user-meta overflow-hidden">
                    <div class="user-name text-truncate fw-semibold"><?= htmlspecialchars($currentUser['name'] ?? 'Pengguna') ?></div>
                    <div class="user-role-badge">
                        <span class="badge <?= $isAdmin ? 'bg-warning text-dark' : 'bg-primary-subtle text-primary' ?>">
                            <?= $isAdmin ? 'Admin' : ucfirst($civitasType) ?>
                        </span>
                        <?php if (!empty($currentUser['identifier'])): ?>
                            <small class="text-white-50 ms-1"><?= htmlspecialchars($currentUser['identifier']) ?></small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <ul class="sidebar-nav">
                <li class="nav-heading">MENU UTAMA</li>

                <li>
                    <a href="<?= BASE_URL ?>/documents" class="nav-link <?= ($page === 'documents') ? 'active' : '' ?>">
                        <i class="bi bi-file-earmark-text-fill"></i>
                        <span><?= $isAdmin ? 'Semua Dokumen' : 'Dokumen Saya' ?></span>
                    </a>
                </li>

                <?php if ($isAdmin): ?>
                    <li class="nav-heading">ADMINISTRASI</li>
                    <li>
                        <a href="<?= BASE_URL ?>/civitas" class="nav-link <?= ($page === 'civitas') ? 'active' : '' ?>">
                            <i class="bi bi-people-fill"></i>
                            <span>Master Data Civitas</span>
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-heading">PROFIL PENANDATANGAN</li>
                    <li>
                        <a href="<?= BASE_URL ?>/user-signatures" class="nav-link <?= ($page === 'user-signatures') ? 'active' : '' ?>">
                            <i class="bi bi-pen-fill"></i>
                            <span>Template TTD Saya</span>
                        </a>
                    </li>
                <?php endif; ?>

                <li class="nav-heading">VERIFIKASI & PUBLIK</li>
                <li>
                    <a href="<?= BASE_URL ?>/verify" class="nav-link <?= ($page === 'verify') ? 'active' : '' ?>" target="_blank">
                        <i class="bi bi-qr-code-scan"></i>
                        <span>Verifikasi Dokumen <i class="bi bi-box-arrow-up-right ms-1" style="font-size:0.75rem;"></i></span>
                    </a>
                </li>
            </ul>

            <div class="sidebar-footer">
                <a href="<?= BASE_URL ?>/auth/logout" class="btn btn-logout w-100 d-flex align-items-center justify-content-center gap-2">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Keluar (Logout)</span>
                </a>
            </div>
        </aside>

        <!-- Main Wrapper -->
        <div class="main">
            <!-- Topbar Header -->
            <header class="topbar">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1.5 fw-medium d-flex align-items-center gap-1.5" style="border-radius: 6px;">
                        <i class="bi bi-mortarboard-fill"></i>
                        <span>Politeknik Negeri Jakarta</span>
                    </span>
                    <span class="text-secondary small d-none d-md-inline">&bull; Sistem Penandatanganan Digital Resmi</span>
                </div>

                <div class="d-flex align-items-center gap-3">
                    <?php if ($currentUser): ?>
                        <div class="text-end d-none d-sm-block">
                            <div class="fw-semibold small text-dark"><?= htmlspecialchars($currentUser['name']) ?></div>
                            <div class="text-muted" style="font-size: 0.78rem;">
                                <?= htmlspecialchars($currentUser['email']) ?>
                            </div>
                        </div>
                        <a href="<?= BASE_URL ?>/auth/logout" class="btn btn-outline-danger btn-sm d-flex align-items-center gap-1.5" style="border-radius: 6px;" title="Keluar">
                            <i class="bi bi-box-arrow-right"></i>
                            <span class="d-none d-md-inline">Logout</span>
                        </a>
                    <?php endif; ?>
                </div>
            </header>

            <!-- Page Content -->
            <main class="content">

                <!-- Session Flash Message Notification Banner -->
                <?php if (!empty($_SESSION['flash'])): ?>
                    <?php 
                        $flash = $_SESSION['flash'];
                        $flashType = htmlspecialchars($flash['type'] ?? 'info');
                        $flashIcons = [
                            'success' => 'bi-check-circle-fill',
                            'danger'  => 'bi-exclamation-triangle-fill',
                            'warning' => 'bi-exclamation-circle-fill',
                            'info'    => 'bi-info-circle-fill',
                        ];
                        $flashIcon = $flashIcons[$flashType] ?? 'bi-info-circle-fill';
                        unset($_SESSION['flash']);
                    ?>
                    <div class="alert alert-<?= $flashType ?> alert-dismissible fade show d-flex align-items-center gap-2 shadow-sm mb-4" role="alert" style="border-radius: 8px;">
                        <i class="bi <?= $flashIcon ?> fs-5"></i>
                        <div class="flex-grow-1 small fw-medium"><?= htmlspecialchars($flash['message'] ?? '') ?></div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
