START TRANSACTION;

INSERT INTO civitas (
    id, identifier, name, type, department, position, email, phone
)
SELECT gen_random_uuid()::text, 'ADM-001', 'Administrator SIGMA', 'tendik',
       'UPT Komputer & Sistem Informasi',
       'Pranata Komputer Ahli / Pengelola Sistem',
       'admin@sigma.local', '081122334455'
WHERE NOT EXISTS (
    SELECT 1 FROM civitas
    WHERE LOWER(identifier) = LOWER('ADM-001')
       OR LOWER(email) = LOWER('admin@sigma.local')
);

INSERT INTO civitas (
    id, identifier, name, type, department, position, email, phone
)
SELECT gen_random_uuid()::text, '220101001', 'Ahmad Fauzi', 'mahasiswa',
       'Teknik Informatika (D4)', 'Mahasiswa Aktif (Semester 7)',
       'mahasiswa@sigma.local', '081234567890'
WHERE NOT EXISTS (
    SELECT 1 FROM civitas
    WHERE LOWER(identifier) = LOWER('220101001')
       OR LOWER(email) = LOWER('mahasiswa@sigma.local')
);

INSERT INTO civitas (
    id, identifier, name, type, department, position, email, phone
)
SELECT gen_random_uuid()::text, '198001012005011001', 'Dr. Eng. Budi Santoso, M.T.', 'dosen',
       'Teknik Informatika', 'Dosen Pembimbing Akademik / Lektor Kepala',
       'dosen@sigma.local', '081298765432'
WHERE NOT EXISTS (
    SELECT 1 FROM civitas
    WHERE LOWER(identifier) = LOWER('198001012005011001')
       OR LOWER(email) = LOWER('dosen@sigma.local')
);

INSERT INTO civitas (
    id, identifier, name, type, department, position, email, phone
)
SELECT gen_random_uuid()::text, '197503152000032001',
       'Prof. Dr. Ir. Siti Aminah, M.Sc.', 'dosen',
       'Teknik Informatika',
       'Ketua Jurusan Teknik Informatika & Komputer',
       'kajur@sigma.local', '081311223344'
WHERE NOT EXISTS (
    SELECT 1 FROM civitas
    WHERE LOWER(identifier) = LOWER('197503152000032001')
       OR LOWER(email) = LOWER('kajur@sigma.local')
);

INSERT INTO civitas (
    id, identifier, name, type, department, position, email, phone
)
SELECT gen_random_uuid()::text, '198805202010122002', 'Dewi Lestari, S.Kom.', 'tendik',
       'Subbag Administrasi Akademik & Kemahasiswaan',
       'Staf Administrasi Akademik',
       'tendik@sigma.local', '081244556677'
WHERE NOT EXISTS (
    SELECT 1 FROM civitas
    WHERE LOWER(identifier) = LOWER('198805202010122002')
       OR LOWER(email) = LOWER('tendik@sigma.local')
);

INSERT INTO users (id, civitas_id, name, email, password, role)
SELECT gen_random_uuid()::text, c.id, 'Administrator SIGMA', 'admin@sigma.local',
       '$2y$12$FBbHiny52k60v9yqx8g4zOR8U/oWp7M6TlIt0xnGawuWPplEFKuYG',
       'admin'
FROM civitas c
WHERE c.email = 'admin@sigma.local'
ON CONFLICT (LOWER(email)) WHERE deleted_at IS NULL DO UPDATE SET
    civitas_id = EXCLUDED.civitas_id,
    name = EXCLUDED.name,
    password = EXCLUDED.password,
    role = EXCLUDED.role,
    deleted_at = NULL,
    updated_at = CURRENT_TIMESTAMP;

INSERT INTO users (id, civitas_id, name, email, password, role)
SELECT gen_random_uuid()::text, c.id, 'Ahmad Fauzi (Mahasiswa)', 'mahasiswa@sigma.local',
       '$2y$12$FBbHiny52k60v9yqx8g4zOR8U/oWp7M6TlIt0xnGawuWPplEFKuYG',
       'user'
FROM civitas c
WHERE c.email = 'mahasiswa@sigma.local'
ON CONFLICT (LOWER(email)) WHERE deleted_at IS NULL DO UPDATE SET
    civitas_id = EXCLUDED.civitas_id,
    name = EXCLUDED.name,
    password = EXCLUDED.password,
    role = EXCLUDED.role,
    deleted_at = NULL,
    updated_at = CURRENT_TIMESTAMP;

INSERT INTO users (id, civitas_id, name, email, password, role)
SELECT gen_random_uuid()::text, c.id, 'Dr. Eng. Budi Santoso, M.T. (Dosen)', 'dosen@sigma.local',
       '$2y$12$FBbHiny52k60v9yqx8g4zOR8U/oWp7M6TlIt0xnGawuWPplEFKuYG',
       'user'
FROM civitas c
WHERE c.email = 'dosen@sigma.local'
ON CONFLICT (LOWER(email)) WHERE deleted_at IS NULL DO UPDATE SET
    civitas_id = EXCLUDED.civitas_id,
    name = EXCLUDED.name,
    password = EXCLUDED.password,
    role = EXCLUDED.role,
    deleted_at = NULL,
    updated_at = CURRENT_TIMESTAMP;

INSERT INTO users (id, civitas_id, name, email, password, role)
SELECT gen_random_uuid()::text, c.id, 'Prof. Dr. Ir. Siti Aminah, M.Sc. (Kajur)', 'kajur@sigma.local',
       '$2y$12$FBbHiny52k60v9yqx8g4zOR8U/oWp7M6TlIt0xnGawuWPplEFKuYG',
       'user'
FROM civitas c
WHERE c.email = 'kajur@sigma.local'
ON CONFLICT (LOWER(email)) WHERE deleted_at IS NULL DO UPDATE SET
    civitas_id = EXCLUDED.civitas_id,
    name = EXCLUDED.name,
    password = EXCLUDED.password,
    role = EXCLUDED.role,
    deleted_at = NULL,
    updated_at = CURRENT_TIMESTAMP;

INSERT INTO users (id, civitas_id, name, email, password, role)
SELECT gen_random_uuid()::text, c.id, 'Dewi Lestari, S.Kom. (Tendik)', 'tendik@sigma.local',
       '$2y$12$FBbHiny52k60v9yqx8g4zOR8U/oWp7M6TlIt0xnGawuWPplEFKuYG',
       'user'
FROM civitas c
WHERE c.email = 'tendik@sigma.local'
ON CONFLICT (LOWER(email)) WHERE deleted_at IS NULL DO UPDATE SET
    civitas_id = EXCLUDED.civitas_id,
    name = EXCLUDED.name,
    password = EXCLUDED.password,
    role = EXCLUDED.role,
    deleted_at = NULL,
    updated_at = CURRENT_TIMESTAMP;

COMMIT;
