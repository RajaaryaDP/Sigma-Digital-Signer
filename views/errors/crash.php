<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Kesalahan Internal Server</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body style="min-height: 100vh; background: #f4f6fb;" class="d-flex align-items-center justify-content-center">

    <div class="text-center p-4" style="max-width: 480px;">
        <div class="bg-white border rounded-3 shadow-sm p-4">
            <h4 class="mb-2 text-danger fw-bold">500 — Kesalahan Internal Sistem</h4>
            <p class="text-muted small mb-3">
                Terjadi kendala teknis saat memproses permintaan Anda. Detail kesalahan telah dicatat pada log server untuk ditindaklanjuti oleh Administrator.
            </p>
            <a href="<?= defined('BASE_URL') ? htmlspecialchars(BASE_URL . '/documents') : '/' ?>" class="btn btn-success btn-sm px-4">
                Kembali ke Beranda
            </a>
        </div>
    </div>

</body>
</html>
