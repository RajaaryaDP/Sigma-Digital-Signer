<?php

date_default_timezone_set('Asia/Jakarta');

$driver  = 'pgsql';
$host    = 'localhost';
$port    = 5432;
$db_name = 'pbl_ti_2025_c_denidwiprasetya';

// Akun aplikasi non-superuser (Least Privilege — is_superuser = off, VULN-05).
// Kredensial superuser bootstrap & provisioning DDL dipisahkan khusus ke CLI (tools/migrate.php).
$user    = 'sigma_app';
$pass    = 'sigma_local_pass';

// Izinkan skrip CLI migrasi membaca parameter koneksi tanpa langsung membuka koneksi PDO
if (PHP_SAPI === 'cli' && defined('SIGMA_DB_CONFIG_ONLY') && SIGMA_DB_CONFIG_ONLY === true) {
    return;
}

try {
    $conn = new PDO("pgsql:host={$host};port={$port};dbname={$db_name}", $user, $pass);
} catch (PDOException $e) {
    // Cegah kebocoran DSN/kredensial/stack trace PDO ke layar publik (Anti Information Disclosure)
    error_log('Database connection failed: ' . $e->getMessage());
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, "Gagal terhubung ke layanan basis data PostgreSQL ({$db_name}) sebagai '{$user}'. Pastikan layanan PostgreSQL berjalan pada port {$port} atau jalankan 'composer migrate' untuk inisialisasi awal.\n");
    } else {
        if (!headers_sent()) {
            http_response_code(500);
        }
        echo 'Layanan basis data sedang tidak dapat dijangkau. Silakan hubungi Administrator sistem.';
    }
    exit(1);
}

$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$conn->exec("SET TIME ZONE 'Asia/Jakarta'");
