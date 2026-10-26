<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';

use Ramsey\Uuid\Uuid;

echo "Seeding Master Data Civitas Kampus & Users untuk SIGMA...\n";

// 1. Data Civitas Akademika Kampus
$civitasList = [
    [
        'identifier' => 'ADM-001',
        'name'       => 'Administrator SIGMA',
        'type'       => 'tendik',
        'department' => 'UPT Komputer & Sistem Informasi',
        'position'   => 'Pranata Komputer Ahli / Pengelola Sistem',
        'email'      => 'admin@sigma.local',
        'phone'      => '081122334455',
    ],
    [
        'identifier' => '220101001',
        'name'       => 'Ahmad Fauzi',
        'type'       => 'mahasiswa',
        'department' => 'Teknik Informatika (D4)',
        'position'   => 'Mahasiswa Aktif (Semester 7)',
        'email'      => 'mahasiswa@sigma.local',
        'phone'      => '081234567890',
    ],
    [
        'identifier' => '198001012005011001',
        'name'       => 'Dr. Eng. Budi Santoso, M.T.',
        'type'       => 'dosen',
        'department' => 'Teknik Informatika',
        'position'   => 'Dosen Pembimbing Akademik / Lektor Kepala',
        'email'      => 'dosen@sigma.local',
        'phone'      => '081298765432',
    ],
    [
        'identifier' => '197503152000032001',
        'name'       => 'Prof. Dr. Ir. Siti Aminah, M.Sc.',
        'type'       => 'dosen',
        'department' => 'Teknik Informatika',
        'position'   => 'Ketua Jurusan Teknik Informatika & Komputer',
        'email'      => 'kajur@sigma.local',
        'phone'      => '081311223344',
    ],
    [
        'identifier' => '198805202010122002',
        'name'       => 'Dewi Lestari, S.Kom.',
        'type'       => 'tendik',
        'department' => 'Subbag Administrasi Akademik & Kemahasiswaan',
        'position'   => 'Staf Administrasi Akademik',
        'email'      => 'tendik@sigma.local',
        'phone'      => '081244556677',
    ],
];

$civitasMap = []; // email => civitas_id

$stmtCheckCivitas  = $conn->prepare("SELECT id FROM civitas WHERE identifier = ?");
$stmtInsertCivitas = $conn->prepare("
    INSERT INTO civitas (id, identifier, name, type, department, position, email, phone, created_at, updated_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
");

foreach ($civitasList as $c) {
    $stmtCheckCivitas->execute([$c['identifier']]);
    $existing = $stmtCheckCivitas->fetch();

    if ($existing) {
        $civitasMap[$c['email']] = $existing['id'];
        echo "  - Civitas {$c['identifier']} ({$c['name']}) already exists.\n";
    } else {
        $civitasId = Uuid::uuid4()->toString();
        $stmtInsertCivitas->execute([
            $civitasId,
            $c['identifier'],
            $c['name'],
            $c['type'],
            $c['department'],
            $c['position'],
            $c['email'],
            $c['phone'],
        ]);
        $civitasMap[$c['email']] = $civitasId;
        echo "  + Inserted Civitas [{$c['type']}]: {$c['identifier']} - {$c['name']}\n";
    }
}

// 2. Data Akun Login Pengguna (terhubung ke civitas_id)
$usersList = [
    [
        'name'       => 'Administrator SIGMA',
        'email'      => 'admin@sigma.local',
        'password'   => password_hash('password', PASSWORD_BCRYPT),
        'role'       => 'admin',
    ],
    [
        'name'       => 'Ahmad Fauzi (Mahasiswa)',
        'email'      => 'mahasiswa@sigma.local',
        'password'   => password_hash('password', PASSWORD_BCRYPT),
        'role'       => 'user',
    ],
    [
        'name'       => 'Dr. Eng. Budi Santoso, M.T. (Dosen)',
        'email'      => 'dosen@sigma.local',
        'password'   => password_hash('password', PASSWORD_BCRYPT),
        'role'       => 'user',
    ],
    [
        'name'       => 'Prof. Dr. Ir. Siti Aminah, M.Sc. (Kajur)',
        'email'      => 'kajur@sigma.local',
        'password'   => password_hash('password', PASSWORD_BCRYPT),
        'role'       => 'user',
    ],
    [
        'name'       => 'Dewi Lestari, S.Kom. (Tendik)',
        'email'      => 'tendik@sigma.local',
        'password'   => password_hash('password', PASSWORD_BCRYPT),
        'role'       => 'user',
    ],
];

$stmtCheckUser  = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmtInsertUser = $conn->prepare("
    INSERT INTO users (id, civitas_id, name, email, password, role, created_at, updated_at)
    VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
");

foreach ($usersList as $u) {
    $stmtCheckUser->execute([$u['email']]);
    if ($stmtCheckUser->fetch()) {
        echo "  - User {$u['email']} already exists, skipping.\n";
        continue;
    }

    $userId = Uuid::uuid4()->toString();
    $civitasId = $civitasMap[$u['email']] ?? null;

    $stmtInsertUser->execute([
        $userId,
        $civitasId,
        $u['name'],
        $u['email'],
        $u['password'],
        $u['role'],
    ]);

    echo "  + Inserted User [{$u['role']}]: {$u['email']} -> Civitas: {$civitasId}\n";
}

echo "Seeding completed successfully! Default password untuk semua akun: password\n";
