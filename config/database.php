<?php

date_default_timezone_set('Asia/Jakarta');

$driver     = 'pgsql';
$host       = 'localhost';
$port       = 5432;
$db_name    = 'pbl_ti_2025_a_raja';

// Akun aplikasi non-superuser (Least Privilege — is_superuser = off)
$user       = 'sigma_app';
$pass       = 'sigma_local_pass';

// Akun bootstrap lokal (hanya dipakai bila database/role/kepemilikan tabel belum terprovisi)
$admin_user = 'postgres';
$admin_pass = '';

if (!function_exists('sigma_provision_postgres_role_and_db')) {
    /**
     * Memastikan database target, role non-superuser (sigma_app), default privileges,
     * serta kepemilikan tabel/fungsi di skema public telah terkonfigurasi dengan benar.
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
}

try {
    $conn = new PDO("pgsql:host={$host};port={$port};dbname={$db_name}", $user, $pass);
} catch (PDOException $e) {
    try {
        $safeDbName = sigma_provision_postgres_role_and_db($host, $port, $db_name, $user, $pass, $admin_user, $admin_pass);
        $conn = new PDO("pgsql:host={$host};port={$port};dbname={$safeDbName}", $user, $pass);
    } catch (PDOException $finalEx) {
        // Cegah kebocoran DSN/kredensial/stack trace PDO ke layar publik (Anti Information Disclosure)
        error_log('Database connection failed: ' . $finalEx->getMessage());
        if (PHP_SAPI === 'cli') {
            fwrite(STDERR, "Gagal terhubung ke layanan basis data PostgreSQL ({$db_name}). Pastikan layanan PostgreSQL berjalan pada port {$port}.\n");
        } else {
            http_response_code(500);
            echo 'Layanan basis data sedang tidak dapat dijangkau. Silakan hubungi Administrator sistem.';
        }
        exit(1);
    }
}

$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$conn->exec("SET TIME ZONE 'Asia/Jakarta'");


