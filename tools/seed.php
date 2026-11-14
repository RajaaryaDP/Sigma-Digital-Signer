<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';

use Ramsey\Uuid\Uuid;

echo "Seeding Master Jurusan, Prodi, Civitas Kampus & Users untuk SIGMA...\n";

// 0. Data Master Jurusan & Program Studi (Prodi)
$departmentsSeed = [
    [
        'code' => 'TIK',
        'name' => 'Teknik Informatika dan Komputer',
        'prodi' => [
            ['code' => 'TI',  'degree' => 'D4', 'name' => 'Teknik Informatika'],
            ['code' => 'TMD', 'degree' => 'D4', 'name' => 'Teknik Multimedia dan Digital'],
            ['code' => 'TMJ', 'degree' => 'D4', 'name' => 'Teknik Multimedia dan Jaringan'],
            ['code' => 'RKS', 'degree' => 'D4', 'name' => 'Rekayasa Keamanan Siber'],
        ],
    ],
];

$departmentMap = []; // code => department_id
$prodiMap = [];      // code => ['id' => ..., 'department_id' => ..., 'name' => ...]

$stmtCheckDept = $conn->prepare("
    SELECT id, deleted_at FROM departments
    WHERE LOWER(name) = LOWER(?) OR (code IS NOT NULL AND code <> '' AND LOWER(code) = LOWER(?))
    ORDER BY deleted_at IS NULL DESC LIMIT 1
");
$stmtInsertDept = $conn->prepare("
    INSERT INTO departments (id, code, name, created_at, updated_at)
    VALUES (?, ?, ?, NOW(), NOW())
");
$stmtRestoreDept = $conn->prepare("
    UPDATE departments
    SET code = ?, name = ?, deleted_at = NULL, updated_at = NOW()
    WHERE id = ?
");

$stmtCheckProdi = $conn->prepare("
    SELECT id, deleted_at FROM study_programs
    WHERE department_id = ? AND (LOWER(name) = LOWER(?) OR (code IS NOT NULL AND code <> '' AND LOWER(code) = LOWER(?)))
    ORDER BY deleted_at IS NULL DESC LIMIT 1
");
$stmtInsertProdi = $conn->prepare("
    INSERT INTO study_programs (id, department_id, code, degree, name, created_at, updated_at)
    VALUES (?, ?, ?, ?, ?, NOW(), NOW())
");
$stmtRestoreProdi = $conn->prepare("
    UPDATE study_programs
    SET code = ?, degree = ?, name = ?, deleted_at = NULL, updated_at = NOW()
    WHERE id = ?
");

foreach ($departmentsSeed as $dept) {
    $stmtCheckDept->execute([$dept['name'], $dept['code']]);
    $existingDept = $stmtCheckDept->fetch();
    if ($existingDept) {
        $deptId = $existingDept['id'];
        if ($existingDept['deleted_at'] !== null) {
            $stmtRestoreDept->execute([$dept['code'], $dept['name'], $deptId]);
            echo "  ~ Restored Jurusan: {$dept['name']} ({$dept['code']})\n";
        } else {
            echo "  - Jurusan {$dept['name']} sudah ada.\n";
        }
    } else {
        $deptId = Uuid::uuid4()->toString();
        $stmtInsertDept->execute([$deptId, $dept['code'], $dept['name']]);
        echo "  + Inserted Jurusan: {$dept['name']} ({$dept['code']})\n";
    }
    $departmentMap[$dept['code']] = $deptId;

    foreach ($dept['prodi'] as $pr) {
        $stmtCheckProdi->execute([$deptId, $pr['name'], $pr['code']]);
        $existingProdi = $stmtCheckProdi->fetch();
        if ($existingProdi) {
            $prodiId = $existingProdi['id'];
            if ($existingProdi['deleted_at'] !== null) {
                $stmtRestoreProdi->execute([$pr['code'], $pr['degree'], $pr['name'], $prodiId]);
                echo "    ~ Restored Prodi: {$pr['name']}\n";
            } else {
                echo "    - Prodi {$pr['name']} sudah ada.\n";
            }
        } else {
            $prodiId = Uuid::uuid4()->toString();
            $stmtInsertProdi->execute([$prodiId, $deptId, $pr['code'], $pr['degree'], $pr['name']]);
            echo "    + Inserted Prodi: [{$pr['degree']}] {$pr['name']} ({$pr['code']})\n";
        }
        $prodiMap[$pr['code']] = [
            'id' => $prodiId,
            'department_id' => $deptId,
            'name' => $pr['name'],
        ];
    }
}

$tikDeptId = $departmentMap['TIK'] ?? null;

// 1. Data Civitas Akademika Kampus
$civitasList = [
    [
        'identifier'    => 'ADM-001',
        'name'          => 'Administrator SIGMA',
        'type'          => 'tendik',
        'department_id' => null,
        'department'    => 'UPT Komputer & Sistem Informasi',
        'position'      => 'Pranata Komputer Ahli / Pengelola Sistem',
        'email'         => 'admin@sigma.local',
        'phone'         => '081122334455',
        'prodi_codes'   => [],
    ],
    [
        'identifier'    => '220101001',
        'name'          => 'Ahmad Fauzi',
        'type'          => 'mahasiswa',
        'department_id' => $tikDeptId,
        'department'    => 'Teknik Informatika dan Komputer',
        'position'      => 'Mahasiswa Aktif (Semester 7)',
        'email'         => 'mahasiswa@sigma.local',
        'phone'         => '081234567890',
        'prodi_codes'   => ['TI'],
    ],
    [
        'identifier'    => '198001012005011001',
        'name'          => 'Dr. Eng. Budi Santoso, M.T.',
        'type'          => 'dosen',
        'department_id' => $tikDeptId,
        'department'    => 'Teknik Informatika dan Komputer',
        'position'      => 'Dosen Pembimbing Akademik / Lektor Kepala',
        'email'         => 'dosen@sigma.local',
        'phone'         => '081298765432',
        'prodi_codes'   => ['TI', 'TMJ'], // Contoh 1 dosen mengajar >1 prodi
    ],
    [
        'identifier'    => '197503152000032001',
        'name'          => 'Prof. Dr. Ir. Siti Aminah, M.Sc.',
        'type'          => 'dosen',
        'department_id' => $tikDeptId,
        'department'    => 'Teknik Informatika dan Komputer',
        'position'      => 'Ketua Jurusan Teknik Informatika dan Komputer',
        'email'         => 'kajur@sigma.local',
        'phone'         => '081311223344',
        'prodi_codes'   => ['TI', 'TMD', 'TMJ', 'RKS'],
    ],
    [
        'identifier'    => '198805202010122002',
        'name'          => 'Dewi Lestari, S.Kom.',
        'type'          => 'tendik',
        'department_id' => null,
        'department'    => 'Subbag Administrasi Akademik & Kemahasiswaan',
        'position'      => 'Staf Administrasi Akademik',
        'email'         => 'tendik@sigma.local',
        'phone'         => '081244556677',
        'prodi_codes'   => [],
    ],
];

$civitasMap = []; // email => civitas_id

$stmtCheckCivitas = $conn->prepare("
    SELECT id, identifier, email, department_id, deleted_at
    FROM civitas
    WHERE LOWER(identifier) = LOWER(?) OR LOWER(email) = LOWER(?)
    ORDER BY (LOWER(identifier) = LOWER(?)) DESC, deleted_at IS NULL DESC
    LIMIT 1
");
$stmtResolveCivitasConflict = $conn->prepare("
    UPDATE civitas
    SET deleted_at = NOW(), updated_at = NOW()
    WHERE id != ?
      AND deleted_at IS NULL
      AND (LOWER(identifier) = LOWER(?) OR LOWER(email) = LOWER(?))
");
$stmtRestoreCivitas = $conn->prepare("
    UPDATE civitas
    SET identifier    = ?,
        name          = ?,
        type          = ?,
        department_id = ?,
        department    = ?,
        position      = ?,
        email         = ?,
        phone         = ?,
        deleted_at    = NULL,
        updated_at    = NOW()
    WHERE id = ?
");
$stmtSyncCivitasDept = $conn->prepare("
    UPDATE civitas
    SET department_id = ?, department = ?, updated_at = NOW()
    WHERE id = ?
");
$stmtInsertCivitas = $conn->prepare("
    INSERT INTO civitas (id, department_id, identifier, name, type, department, position, email, phone, created_at, updated_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
");
$stmtCountCsp = $conn->prepare("SELECT COUNT(*) FROM civitas_study_programs WHERE civitas_id = ?");
$stmtInsertCsp = $conn->prepare("
    INSERT INTO civitas_study_programs (id, civitas_id, study_program_id, created_at)
    VALUES (?, ?, ?, NOW())
    ON CONFLICT (civitas_id, study_program_id) DO NOTHING
");

foreach ($civitasList as $c) {
    $normEmail = strtolower(trim($c['email']));
    $stmtCheckCivitas->execute([$c['identifier'], $normEmail, $c['identifier']]);
    $existing = $stmtCheckCivitas->fetch();

    if ($existing) {
        // Pastikan tidak ada baris aktif lain yang menahan identifier/email yang sama sebelum restore/sinkronisasi
        $stmtResolveCivitasConflict->execute([$existing['id'], $c['identifier'], $normEmail]);

        if ($existing['deleted_at'] !== null || strtolower($existing['identifier']) !== strtolower($c['identifier']) || strtolower($existing['email']) !== $normEmail) {
            $stmtRestoreCivitas->execute([
                $c['identifier'],
                $c['name'],
                $c['type'],
                $c['department_id'],
                $c['department'],
                $c['position'],
                $normEmail,
                $c['phone'],
                $existing['id'],
            ]);
            echo "  ~ Restored/synchronized Civitas {$c['identifier']} ({$c['name']}).\n";
        } else {
            if (($existing['department_id'] ?? null) === null && !empty($c['department_id'])) {
                $stmtSyncCivitasDept->execute([$c['department_id'], $c['department'], $existing['id']]);
                echo "  ~ Linked Jurusan ke Civitas {$c['identifier']} ({$c['name']}).\n";
            } else {
                echo "  - Civitas {$c['identifier']} ({$c['name']}) already exists.\n";
            }
        }
        $civitasId = $existing['id'];
        $civitasMap[$c['email']] = $civitasId;
    } else {
        $civitasId = Uuid::uuid4()->toString();
        $stmtInsertCivitas->execute([
            $civitasId,
            $c['department_id'],
            $c['identifier'],
            $c['name'],
            $c['type'],
            $c['department'],
            $c['position'],
            $normEmail,
            $c['phone'],
        ]);
        $civitasMap[$c['email']] = $civitasId;
        echo "  + Inserted Civitas [{$c['type']}]: {$c['identifier']} - {$c['name']}\n";
    }

    if (!empty($c['prodi_codes'])) {
        $stmtCountCsp->execute([$civitasId]);
        $existingProdiCount = (int) $stmtCountCsp->fetchColumn();
        if ($existingProdiCount === 0) {
            foreach ($c['prodi_codes'] as $pCode) {
                if (!empty($prodiMap[$pCode]['id'])) {
                    $stmtInsertCsp->execute([Uuid::uuid4()->toString(), $civitasId, $prodiMap[$pCode]['id']]);
                }
            }
            echo "    + Linked Prodi (" . implode(', ', $c['prodi_codes']) . ") -> {$c['name']}\n";
        }
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

$stmtCheckUser = $conn->prepare("
    SELECT id, civitas_id, role, deleted_at FROM users WHERE LOWER(email) = LOWER(?) ORDER BY deleted_at IS NULL DESC LIMIT 1
");
$stmtRestoreUser = $conn->prepare("
    UPDATE users
    SET civitas_id = ?,
        name       = ?,
        email      = ?,
        password   = ?,
        role       = ?,
        deleted_at = NULL,
        updated_at = NOW()
    WHERE id = ?
");
$stmtSyncActiveUser = $conn->prepare("
    UPDATE users
    SET civitas_id = ?,
        role       = ?,
        updated_at = NOW()
    WHERE id = ?
");
$stmtInsertUser = $conn->prepare("
    INSERT INTO users (id, civitas_id, name, email, password, role, created_at, updated_at)
    VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
");

foreach ($usersList as $u) {
    $civitasId = $civitasMap[$u['email']] ?? null;
    $normEmail = strtolower(trim($u['email']));
    $stmtCheckUser->execute([$normEmail]);
    $existingUser = $stmtCheckUser->fetch();

    if ($existingUser) {
        if ($existingUser['deleted_at'] !== null) {
            $stmtRestoreUser->execute([
                $civitasId,
                $u['name'],
                $normEmail,
                $u['password'],
                $u['role'],
                $existingUser['id'],
            ]);
            echo "  ~ Restored soft-deleted User {$u['email']} (password & role reset to default).\n";
        } else {
            if (($existingUser['civitas_id'] ?? null) !== $civitasId || ($existingUser['role'] ?? '') !== $u['role']) {
                $stmtSyncActiveUser->execute([$civitasId, $u['role'], $existingUser['id']]);
                echo "  ~ Synchronized active User {$u['email']} -> Civitas: {$civitasId}\n";
            } else {
                echo "  - User {$u['email']} already exists, skipping.\n";
            }
        }
        continue;
    }

    $userId = Uuid::uuid4()->toString();

    $stmtInsertUser->execute([
        $userId,
        $civitasId,
        $u['name'],
        strtolower($u['email']),
        $u['password'],
        $u['role'],
    ]);

    echo "  + Inserted User [{$u['role']}]: {$u['email']} -> Civitas: {$civitasId}\n";
}

echo "Seeding completed successfully! Default password untuk semua akun: password\n";
