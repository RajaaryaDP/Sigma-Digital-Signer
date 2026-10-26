<?php

require_once __DIR__ . '/../config/database.php';

$schemaFile = dirname(__DIR__, 2) . '/database/schema.sql';

if (!file_exists($schemaFile)) {
    echo "Error: Schema file not found at {$schemaFile}\n";
    exit(1);
}

$isFresh = in_array('--fresh', $argv ?? [], true);

if ($isFresh) {
    echo "Dropping existing SIGMA tables (fresh migration)...\n";
    $conn->exec("
        SET FOREIGN_KEY_CHECKS = 0;
        DROP TABLE IF EXISTS e_stamps;
        DROP TABLE IF EXISTS signatures;
        DROP TABLE IF EXISTS document_signers;
        DROP TABLE IF EXISTS documents;
        DROP TABLE IF EXISTS audit_logs;
        DROP TABLE IF EXISTS user_signatures;
        DROP TABLE IF EXISTS users;
        DROP TABLE IF EXISTS civitas;
        SET FOREIGN_KEY_CHECKS = 1;
    ");
} else {
    // Pastikan e_stamps yang sudah dibatalkan tetap di-drop jika ada
    $conn->exec("
        SET FOREIGN_KEY_CHECKS = 0;
        DROP TABLE IF EXISTS e_stamps;
        SET FOREIGN_KEY_CHECKS = 1;
    ");
}

echo "Executing schema migration from: {$schemaFile}\n";

$sql = file_get_contents($schemaFile);

try {
    $conn->exec($sql);
    echo "Migration completed successfully!\n";

    echo "Tables in database:\n";
    $tables = $conn->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $t) {
        echo "  - {$t}\n";
    }
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
