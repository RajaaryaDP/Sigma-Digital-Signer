<?php

require_once __DIR__ . '/../config/database.php';

$schemaFile = __DIR__ . '/../database/schema.sql';

if (!file_exists($schemaFile)) {
    echo "Error: Schema file not found at {$schemaFile}\n";
    exit(1);
}

$isFresh  = in_array('--fresh', $argv ?? [], true);
$activeDb = (string) $conn->query("SELECT current_database()")->fetchColumn();
$activeRole = (string) $conn->query("SELECT current_user")->fetchColumn();

echo "Target PostgreSQL database: {$activeDb} (connected as role: {$activeRole})\n";

if (isset($db_name) && strtolower($activeDb) !== strtolower($db_name)) {
    fwrite(STDERR, "Error: Active database '{$activeDb}' does not match configured database '{$db_name}'. Aborting migration.\n");
    exit(1);
}

// Sinkronisasi kepemilikan tabel/fungsi jika sebelumnya ada objek yang diimpor manual via akun postgres
if (function_exists('sigma_provision_postgres_role_and_db') && isset($host, $port, $db_name, $user, $pass, $admin_user, $admin_pass)) {
    try {
        sigma_provision_postgres_role_and_db($host, (int) $port, $db_name, $user, $pass, $admin_user, $admin_pass);
    } catch (PDOException $provEx) {
        // Lanjutkan jika akun aplikasi sudah memegang kepemilikan objek
    }
}

if ($isFresh) {
    echo "Dropping existing SIGMA tables in '{$activeDb}' (fresh migration on PostgreSQL)...\n";
    $conn->exec("
        DROP TABLE IF EXISTS e_stamps CASCADE;
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
