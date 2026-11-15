<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Kesalahan Internal Server | SIGMA</title>
    <link rel="icon" type="image/png" href="<?= defined('BASE_URL') ? htmlspecialchars(BASE_URL) : '' ?>/assets/img/logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= defined('BASE_URL') ? htmlspecialchars(BASE_URL) : '' ?>/assets/css/sigma-footer.css" rel="stylesheet">
</head>
<body style="min-height: 100vh; background: #f8f8fc; font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; display: flex; flex-direction: column; align-items: center;">

    <main class="text-center p-4 my-auto" style="max-width: 500px; width: 100%;">
        <div class="bg-white p-4 p-md-5" style="border: 1px solid #e6e2f0; border-radius: 16px; box-shadow: 0 4px 16px rgba(45, 43, 75, 0.08);">
            <div class="d-inline-flex align-items-center justify-content-center mb-3 rounded-circle" style="width: 56px; height: 56px; background: #fee2e2; color: #b22929; font-size: 1.5rem; font-weight: 700;">
                500
            </div>
            <h4 class="mb-2 fw-bold" style="color: #1e1b33;">500 — Kesalahan Internal Sistem</h4>
            <p class="small mb-4" style="color: #69647d;">
                Terjadi kendala teknis saat memproses permintaan Anda. Detail kesalahan telah dicatat pada log server untuk ditindaklanjuti oleh Administrator.
            </p>
            <a href="<?= defined('BASE_URL') ? htmlspecialchars(BASE_URL . '/documents') : '/' ?>"
               class="btn btn-sm px-4 py-2 fw-semibold text-white"
               style="background-color: #2d2b4b; border-color: #2d2b4b; border-radius: 8px;">
                Kembali ke Ruang Kerja
            </a>
        </div>
    </main>

    <?php require __DIR__ . '/../layout/sigma-footer.php'; ?>

</body>
</html>
