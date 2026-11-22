<?php

if (PHP_SAPI !== 'cli') {
    if (!headers_sent()) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=UTF-8');
    }
    exit('Forbidden: CLI only.');
}

// Baca konfigurasi aplikasi (host, port, db_name, user, pass) tanpa memicu koneksi web runtime
define('SIGMA_DB_CONFIG_ONLY', true);
require_once __DIR__ . '/../config/database.php';

// Kredensial bootstrap superuser PostgreSQL hanya digunakan pada CLI migrasi (VULN-05)
$admin_user = getenv('PG_ADMIN_USER') ?: 'postgres';
$admin_pass = getenv('PG_ADMIN_PASS') !== false ? (string) getenv('PG_ADMIN_PASS') : '';

/**
 * Memastikan database target, role non-superuser (sigma_app), default privileges,
 * serta kepemilikan tabel/fungsi di skema public telah terkonfigurasi dengan benar (khusus CLI).
 */
function sigma_provision_postgres_role_and_db(
    string $host,
    int $port,
    string $dbName,
    string $appUser,
    string $appPass,
    string $adminUser,
    string $adminPass
): string {
    $safeDbName   = preg_replace('/[^a-zA-Z0-9_]/', '_', $dbName);
    $quotedDbName = '"' . str_replace('"', '""', $safeDbName) . '"';
    $safeAppUser  = preg_replace('/[^a-zA-Z0-9_]/', '_', $appUser);
    $quotedUser   = '"' . str_replace('"', '""', $safeAppUser) . '"';

    $adminConn = new PDO("pgsql:host={$host};port={$port};dbname=postgres", $adminUser, $adminPass);
    $adminConn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Pastikan role non-superuser sigma_app tersedia
    $stmtRole = $adminConn->prepare("SELECT 1 FROM pg_roles WHERE rolname = ?");
    $stmtRole->execute([$safeAppUser]);
    if (!$stmtRole->fetchColumn()) {
        $quotedPass = $adminConn->quote($appPass);
        $adminConn->exec("CREATE ROLE {$quotedUser} WITH LOGIN NOSUPERUSER NOCREATEDB NOCREATEROLE PASSWORD {$quotedPass}");
    }

    // 2. Pastikan database target tersedia (tanpa fallback ke database lain seperti tik_pbl)
    $stmtDb = $adminConn->prepare("SELECT 1 FROM pg_database WHERE datname = ?");
    $stmtDb->execute([$safeDbName]);
    if (!$stmtDb->fetchColumn()) {
        $adminConn->exec("CREATE DATABASE {$quotedDbName} OWNER {$quotedUser} ENCODING 'UTF8'");
    } else {
        $adminConn->exec("GRANT ALL PRIVILEGES ON DATABASE {$quotedDbName} TO {$quotedUser}");
    }
    $adminConn = null;

    // 3. Pastikan hak akses skema public, default privileges, & kepemilikan objek diberikan ke sigma_app
    $dbAdminConn = new PDO("pgsql:host={$host};port={$port};dbname={$safeDbName}", $adminUser, $adminPass);
    $dbAdminConn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $dbAdminConn->exec("GRANT ALL ON SCHEMA public TO {$quotedUser}");
    $dbAdminConn->exec("GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO {$quotedUser}");
    $dbAdminConn->exec("GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public TO {$quotedUser}");
    $dbAdminConn->exec("ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON TABLES TO {$quotedUser}");
    $dbAdminConn->exec("ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON SEQUENCES TO {$quotedUser}");

    $stmtTables = $dbAdminConn->prepare("SELECT tablename FROM pg_tables WHERE schemaname = 'public' AND tableowner != ?");
    $stmtTables->execute([$safeAppUser]);
    $tables = $stmtTables->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $tbl) {
        $qTbl = '"' . str_replace('"', '""', $tbl) . '"';
        $dbAdminConn->exec("ALTER TABLE public.{$qTbl} OWNER TO {$quotedUser}");
    }

    $dbAdminConn->exec("
        DO \$\$
        BEGIN
            IF EXISTS (
                SELECT 1 FROM pg_proc p
                JOIN pg_namespace n ON p.pronamespace = n.oid
                WHERE n.nspname = 'public' AND p.proname = 'update_timestamp_trigger'
            ) THEN
                ALTER FUNCTION public.update_timestamp_trigger() OWNER TO {$quotedUser};
            END IF;
        END
        \$\$;
    ");
    $dbAdminConn = null;

    return $safeDbName;
}

// Provisi role, database, dan sinkronisasi kepemilikan objek bila diperlukan (khusus CLI)
try {
    sigma_provision_postgres_role_and_db($host, (int) $port, $db_name, $user, $pass, $admin_user, $admin_pass);
} catch (PDOException $provEx) {
    // Lanjutkan mencoba koneksi langsung sebagai role aplikasi jika role/DB sudah terprovisi
}

try {
    $conn = new PDO("pgsql:host={$host};port={$port};dbname={$db_name}", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $conn->exec("SET TIME ZONE 'Asia/Jakarta'");
} catch (PDOException $e) {
    fwrite(STDERR, "Gagal terhubung ke PostgreSQL ({$db_name}) sebagai '{$user}': " . $e->getMessage() . "\n");
    exit(1);
}

$schemaFile = __DIR__ . '/../database/schema.sql';

if (!file_exists($schemaFile)) {
    echo "Error: Schema file not found at {$schemaFile}\n";
    exit(1);
}

$isFresh    = in_array('--fresh', $argv ?? [], true);
$activeDb   = (string) $conn->query("SELECT current_database()")->fetchColumn();
$activeRole = (string) $conn->query("SELECT current_user")->fetchColumn();

echo "Target PostgreSQL database: {$activeDb} (connected as role: {$activeRole})\n";

if (isset($db_name) && strtolower($activeDb) !== strtolower($db_name)) {
    fwrite(STDERR, "Error: Active database '{$activeDb}' does not match configured database '{$db_name}'. Aborting migration.\n");
    exit(1);
}

if ($isFresh) {
    echo "Dropping existing SIGMA tables in '{$activeDb}' (fresh migration on PostgreSQL)...\n";
    $conn->exec("
        DROP TABLE IF EXISTS e_stamps CASCADE;
        DROP TABLE IF EXISTS document_templates CASCADE;
        DROP TABLE IF EXISTS signatures CASCADE;
        DROP TABLE IF EXISTS document_signers CASCADE;
        DROP TABLE IF EXISTS documents CASCADE;
        DROP TABLE IF EXISTS audit_logs CASCADE;
        DROP TABLE IF EXISTS user_signatures CASCADE;
        DROP TABLE IF EXISTS users CASCADE;
        DROP TABLE IF EXISTS civitas_study_programs CASCADE;
        DROP TABLE IF EXISTS civitas CASCADE;
        DROP TABLE IF EXISTS study_programs CASCADE;
        DROP TABLE IF EXISTS departments CASCADE;
    ");
} else {
    // Pastikan e_stamps yang sudah dibatalkan tetap di-drop jika ada
    $conn->exec("DROP TABLE IF EXISTS e_stamps CASCADE;");
}

echo "Executing schema migration from: {$schemaFile}\n";

$sql = file_get_contents($schemaFile);

try {
    $conn->exec($sql);
    echo "Migration completed successfully!\n";

    echo "Tables in PostgreSQL database (schema public):\n";
    $tables = $conn->query("
        SELECT table_name
        FROM information_schema.tables
        WHERE table_schema = 'public'
          AND table_type = 'BASE TABLE'
        ORDER BY table_name
    ")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $t) {
        echo "  - {$t}\n";
    }
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
