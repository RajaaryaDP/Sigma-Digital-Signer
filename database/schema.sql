-- =============================================================================
-- SIGMA — Sistem Tanda Tangan Digital
-- Schema Database  :  PostgreSQL (14+ / 18.x)
-- Versi            :  3.0.0 (Migrasi PostgreSQL Resmi - Task 12 & ADR-10)
-- Tanggal          :  2026-11-09
-- =============================================================================
-- Konvensi:
--   • Primary Key  : CHAR(36) UUID v4, digenerate di layer PHP (Ramsey\Uuid)
--   • Soft Delete  : kolom deleted_at TIMESTAMP NULL (NULL = aktif)
--   • Timestamp    : created_at, updated_at wajib di setiap tabel entitas
--   • Auto-Update  : trigger PL/pgSQL update_timestamp_trigger() pada updated_at
--   • Keamanan SQL : gunakan Prepared Statements di semua query PHP
--   • Tipe Status  : VARCHAR(50) + CHECK Constraint (kompatibel penuh dengan PDO)
-- =============================================================================

-- ============================================================
-- 0. FUNGSI TRIGGER OTOMASI UPDATED_AT (PL/pgSQL)
-- ============================================================
CREATE OR REPLACE FUNCTION update_timestamp_trigger()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- ============================================================
-- 1. DEPARTMENTS (Master Data Jurusan)
-- ============================================================
CREATE TABLE IF NOT EXISTS departments (
    id             CHAR(36)      NOT NULL,
    code           VARCHAR(30)   DEFAULT NULL,
    name           VARCHAR(150)  NOT NULL,
    created_at     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at     TIMESTAMP     DEFAULT NULL,
    CONSTRAINT pk_departments PRIMARY KEY (id)
);

CREATE UNIQUE INDEX IF NOT EXISTS uq_departments_name_lower
    ON departments (LOWER(name)) WHERE deleted_at IS NULL;
CREATE UNIQUE INDEX IF NOT EXISTS uq_departments_code_lower
    ON departments (LOWER(code)) WHERE deleted_at IS NULL AND code IS NOT NULL AND code <> '';
CREATE INDEX IF NOT EXISTS idx_departments_deleted_at ON departments (deleted_at);

DROP TRIGGER IF EXISTS trg_departments_updated_at ON departments;
CREATE TRIGGER trg_departments_updated_at
BEFORE UPDATE ON departments
FOR EACH ROW EXECUTE FUNCTION update_timestamp_trigger();


-- ============================================================
-- 1b. STUDY_PROGRAMS (Master Data Program Studi / Prodi)
--     Setiap Prodi berada di bawah satu Jurusan (department_id).
-- ============================================================
CREATE TABLE IF NOT EXISTS study_programs (
    id             CHAR(36)      NOT NULL,
    department_id  CHAR(36)      NOT NULL,
    code           VARCHAR(30)   DEFAULT NULL,
    degree         VARCHAR(30)   DEFAULT NULL,
    name           VARCHAR(150)  NOT NULL,
    created_at     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at     TIMESTAMP     DEFAULT NULL,
    CONSTRAINT pk_study_programs PRIMARY KEY (id),
    CONSTRAINT fk_study_programs_department
        FOREIGN KEY (department_id) REFERENCES departments (id)
        ON DELETE RESTRICT ON UPDATE CASCADE
);

CREATE UNIQUE INDEX IF NOT EXISTS uq_study_programs_dept_name_lower
    ON study_programs (department_id, LOWER(name)) WHERE deleted_at IS NULL;
CREATE UNIQUE INDEX IF NOT EXISTS uq_study_programs_code_lower
    ON study_programs (LOWER(code)) WHERE deleted_at IS NULL AND code IS NOT NULL AND code <> '';
CREATE INDEX IF NOT EXISTS idx_study_programs_department ON study_programs (department_id);
CREATE INDEX IF NOT EXISTS idx_study_programs_deleted_at ON study_programs (deleted_at);

DROP TRIGGER IF EXISTS trg_study_programs_updated_at ON study_programs;
CREATE TRIGGER trg_study_programs_updated_at
BEFORE UPDATE ON study_programs
FOR EACH ROW EXECUTE FUNCTION update_timestamp_trigger();


-- ============================================================
-- 1c. CIVITAS (Master Data Biodata Civitas Kampus)
--    Menyimpan profil resmi civitas akademika kampus
--    (Mahasiswa, Dosen, Tenaga Kependidikan).
-- ============================================================
CREATE TABLE IF NOT EXISTS civitas (
    id             CHAR(36)      NOT NULL,
    department_id  CHAR(36)      DEFAULT NULL,
    identifier     VARCHAR(64)   NOT NULL,
    name           VARCHAR(255)  NOT NULL,
    type           VARCHAR(50)   NOT NULL DEFAULT 'mahasiswa'
                       CHECK (type IN ('dosen', 'mahasiswa', 'tendik')),
    department     VARCHAR(150)  NOT NULL,
    position       VARCHAR(150)  NOT NULL,
    email          VARCHAR(255)  NOT NULL,
    phone          VARCHAR(32)   DEFAULT NULL,
    avatar_path    VARCHAR(500)  DEFAULT NULL,
    created_at     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at     TIMESTAMP     DEFAULT NULL,
    CONSTRAINT pk_civitas PRIMARY KEY (id),
    CONSTRAINT fk_civitas_department
        FOREIGN KEY (department_id) REFERENCES departments (id)
        ON DELETE SET NULL ON UPDATE CASCADE
);

ALTER TABLE civitas ADD COLUMN IF NOT EXISTS department_id CHAR(36) DEFAULT NULL;
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint WHERE conname = 'fk_civitas_department'
    ) THEN
        ALTER TABLE civitas
            ADD CONSTRAINT fk_civitas_department
            FOREIGN KEY (department_id) REFERENCES departments (id)
            ON DELETE SET NULL ON UPDATE CASCADE;
    END IF;
END $$;

CREATE UNIQUE INDEX IF NOT EXISTS uq_civitas_identifier_lower ON civitas (LOWER(identifier)) WHERE deleted_at IS NULL;
CREATE UNIQUE INDEX IF NOT EXISTS uq_civitas_email_lower ON civitas (LOWER(email)) WHERE deleted_at IS NULL;
CREATE INDEX IF NOT EXISTS idx_civitas_type ON civitas (type);
CREATE INDEX IF NOT EXISTS idx_civitas_department ON civitas (department);
CREATE INDEX IF NOT EXISTS idx_civitas_department_id ON civitas (department_id);
CREATE INDEX IF NOT EXISTS idx_civitas_deleted_at ON civitas (deleted_at);

DROP TRIGGER IF EXISTS trg_civitas_updated_at ON civitas;
CREATE TRIGGER trg_civitas_updated_at
BEFORE UPDATE ON civitas
FOR EACH ROW EXECUTE FUNCTION update_timestamp_trigger();


-- ============================================================
-- 1d. CIVITAS_STUDY_PROGRAMS (Pivot Civitas/Dosen <-> Prodi)
--     Mendukung satu dosen mengajar di lebih dari 1 prodi.
-- ============================================================
CREATE TABLE IF NOT EXISTS civitas_study_programs (
    id                CHAR(36)   NOT NULL,
    civitas_id        CHAR(36)   NOT NULL,
    study_program_id  CHAR(36)   NOT NULL,
    created_at        TIMESTAMP  NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_civitas_study_programs PRIMARY KEY (id),
    CONSTRAINT fk_csp_civitas
        FOREIGN KEY (civitas_id) REFERENCES civitas (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_csp_study_program
        FOREIGN KEY (study_program_id) REFERENCES study_programs (id)
        ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE UNIQUE INDEX IF NOT EXISTS uq_civitas_study_program
    ON civitas_study_programs (civitas_id, study_program_id);
CREATE INDEX IF NOT EXISTS idx_csp_civitas ON civitas_study_programs (civitas_id);
CREATE INDEX IF NOT EXISTS idx_csp_study_program ON civitas_study_programs (study_program_id);


-- ============================================================
-- 2. USERS
--    Akun login pengguna sistem (Admin & User/Pengaju/Penandatangan).
--    Terhubung ke tabel civitas melalui civitas_id.
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id             CHAR(36)      NOT NULL,
    civitas_id     CHAR(36)      DEFAULT NULL,
    name           VARCHAR(255)  NOT NULL,
    email          VARCHAR(255)  NOT NULL,
    password       VARCHAR(255)  NOT NULL,
    role           VARCHAR(50)   NOT NULL DEFAULT 'user'
                       CHECK (role IN ('admin', 'user')),
    avatar_path    VARCHAR(500)  DEFAULT NULL,
    created_at     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at     TIMESTAMP     DEFAULT NULL,
    CONSTRAINT pk_users PRIMARY KEY (id),
    CONSTRAINT fk_users_civitas
        FOREIGN KEY (civitas_id) REFERENCES civitas (id)
        ON DELETE SET NULL ON UPDATE CASCADE
);

CREATE UNIQUE INDEX IF NOT EXISTS uq_users_email_lower ON users (LOWER(email)) WHERE deleted_at IS NULL;
CREATE INDEX IF NOT EXISTS idx_users_role ON users (role);
CREATE INDEX IF NOT EXISTS idx_users_civitas_id ON users (civitas_id);
CREATE INDEX IF NOT EXISTS idx_users_deleted_at ON users (deleted_at);

DROP TRIGGER IF EXISTS trg_users_updated_at ON users;
CREATE TRIGGER trg_users_updated_at
BEFORE UPDATE ON users
FOR EACH ROW EXECUTE FUNCTION update_timestamp_trigger();


-- ============================================================
-- 3. DOCUMENTS
--    Metadata dokumen PDF yang diunggah pengaju.
-- ============================================================
CREATE TABLE IF NOT EXISTS documents (
    id                   CHAR(36)      NOT NULL,
    uploader_id          CHAR(36)      NOT NULL,
    title                VARCHAR(500)  NOT NULL,
    description          TEXT          DEFAULT NULL,
    file_path            VARCHAR(500)  NOT NULL,
    file_name_original   VARCHAR(255)  NOT NULL,
    file_size            BIGINT        NOT NULL CHECK (file_size >= 0),
    file_hash            CHAR(64)      NOT NULL,
    signature_type       VARCHAR(50)   NOT NULL DEFAULT 'sequential'
                             CHECK (signature_type IN ('single', 'sequential')),
    current_step         INTEGER       NOT NULL DEFAULT 1 CHECK (current_step >= 0),
    status               VARCHAR(50)   NOT NULL DEFAULT 'draft'
                             CHECK (status IN ('draft', 'pending_review', 'approved', 'rejected', 'signing', 'completed')),
    rejection_reason     TEXT          DEFAULT NULL,
    final_file_path      VARCHAR(500)  DEFAULT NULL,
    final_file_hash      CHAR(64)      DEFAULT NULL,
    reviewed_by          CHAR(36)      DEFAULT NULL,
    reviewed_at          TIMESTAMP     DEFAULT NULL,
    completed_at         TIMESTAMP     DEFAULT NULL,
    created_at           TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at           TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at           TIMESTAMP     DEFAULT NULL,
    CONSTRAINT pk_documents PRIMARY KEY (id),
    CONSTRAINT fk_documents_uploader
        FOREIGN KEY (uploader_id) REFERENCES users (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_documents_reviewer
        FOREIGN KEY (reviewed_by) REFERENCES users (id)
        ON DELETE SET NULL ON UPDATE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_documents_uploader ON documents (uploader_id);
CREATE INDEX IF NOT EXISTS idx_documents_status ON documents (status);
CREATE INDEX IF NOT EXISTS idx_documents_sig_type ON documents (signature_type);
CREATE INDEX IF NOT EXISTS idx_documents_reviewed_by ON documents (reviewed_by);
CREATE INDEX IF NOT EXISTS idx_documents_deleted_at ON documents (deleted_at);

DROP TRIGGER IF EXISTS trg_documents_updated_at ON documents;
CREATE TRIGGER trg_documents_updated_at
BEFORE UPDATE ON documents
FOR EACH ROW EXECUTE FUNCTION update_timestamp_trigger();


-- ============================================================
-- 4. DOCUMENT_SIGNERS
--    Tabel pivot antrean jenjang dan slot tanda tangan per-dokumen.
-- ============================================================
CREATE TABLE IF NOT EXISTS document_signers (
    id             CHAR(36)      NOT NULL,
    document_id    CHAR(36)      NOT NULL,
    user_id        CHAR(36)      NOT NULL,
    invited_by     CHAR(36)      DEFAULT NULL,
    step_order     INTEGER       NOT NULL DEFAULT 1 CHECK (step_order >= 0),
    role_title     VARCHAR(150)  DEFAULT NULL,
    status         VARCHAR(50)   NOT NULL DEFAULT 'waiting'
                       CHECK (status IN ('waiting', 'pending', 'signed', 'declined')),
    decline_reason TEXT          DEFAULT NULL,
    page_number    INTEGER       NOT NULL DEFAULT 1 CHECK (page_number >= 1),
    x_percent      DECIMAL(6,3)  NOT NULL DEFAULT 0.000,
    y_percent      DECIMAL(6,3)  NOT NULL DEFAULT 0.000,
    width_percent  DECIMAL(6,3)  NOT NULL DEFAULT 20.000,
    height_percent DECIMAL(6,3)  NOT NULL DEFAULT 10.000,
    read_at        TIMESTAMP     DEFAULT NULL,
    signed_at      TIMESTAMP     DEFAULT NULL,
    created_at     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at     TIMESTAMP     DEFAULT NULL,
    CONSTRAINT pk_document_signers PRIMARY KEY (id),
    CONSTRAINT fk_doc_signers_document
        FOREIGN KEY (document_id) REFERENCES documents (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_doc_signers_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_doc_signers_inviter
        FOREIGN KEY (invited_by) REFERENCES users (id)
        ON DELETE SET NULL ON UPDATE CASCADE
);

CREATE UNIQUE INDEX IF NOT EXISTS uq_doc_signer ON document_signers (document_id, user_id) WHERE deleted_at IS NULL;
CREATE INDEX IF NOT EXISTS idx_doc_signers_document ON document_signers (document_id);
CREATE INDEX IF NOT EXISTS idx_doc_signers_user ON document_signers (user_id);
CREATE INDEX IF NOT EXISTS idx_doc_signers_status ON document_signers (status);
CREATE INDEX IF NOT EXISTS idx_doc_signers_step ON document_signers (document_id, step_order);
CREATE INDEX IF NOT EXISTS idx_doc_signers_deleted_at ON document_signers (deleted_at);

DROP TRIGGER IF EXISTS trg_document_signers_updated_at ON document_signers;
CREATE TRIGGER trg_document_signers_updated_at
BEFORE UPDATE ON document_signers
FOR EACH ROW EXECUTE FUNCTION update_timestamp_trigger();


-- ============================================================
-- 5. SIGNATURES
--    Tanda tangan visual yang sudah dikonfirmasi dan ditempatkan
--    di halaman PDF beserta QR Code verifikasi biodata (ADR-03 & ADR-10).
-- ============================================================
CREATE TABLE IF NOT EXISTS signatures (
    id                         CHAR(36)      NOT NULL,
    document_id                CHAR(36)      NOT NULL,
    signer_id                  CHAR(36)      NOT NULL,
    civitas_id                 CHAR(36)      DEFAULT NULL,
    document_signer_id         CHAR(36)      NOT NULL,
    signature_format           VARCHAR(50)   NOT NULL DEFAULT 'qr_and_signature'
                                   CHECK (signature_format IN ('qr_and_signature', 'qr_only')),
    signature_image_path       VARCHAR(500)  DEFAULT NULL,
    verification_token         VARCHAR(128)  NOT NULL,
    qr_code_path               VARCHAR(500)  DEFAULT NULL,
    page_number                INTEGER       NOT NULL CHECK (page_number >= 1),
    x_percent                  DECIMAL(6,3)  NOT NULL,
    y_percent                  DECIMAL(6,3)  NOT NULL,
    width_percent              DECIMAL(6,3)  NOT NULL,
    height_percent             DECIMAL(6,3)  NOT NULL,
    signer_name_snapshot       VARCHAR(255)  NOT NULL,
    signer_identifier_snapshot VARCHAR(64)   DEFAULT NULL,
    signer_type_snapshot       VARCHAR(50)   DEFAULT NULL,
    signer_department_snapshot VARCHAR(150)  DEFAULT NULL,
    signer_position_snapshot   VARCHAR(150)  DEFAULT NULL,
    signer_email_snapshot      VARCHAR(255)  NOT NULL,
    signed_at                  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ip_address                 VARCHAR(45)   DEFAULT NULL,
    created_at                 TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at                 TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at                 TIMESTAMP     DEFAULT NULL,
    CONSTRAINT pk_signatures PRIMARY KEY (id),
    CONSTRAINT uq_signatures_token UNIQUE (verification_token),
    CONSTRAINT fk_signatures_document
        FOREIGN KEY (document_id) REFERENCES documents (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_signatures_signer
        FOREIGN KEY (signer_id) REFERENCES users (id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_signatures_civitas
        FOREIGN KEY (civitas_id) REFERENCES civitas (id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_signatures_doc_signer
        FOREIGN KEY (document_signer_id) REFERENCES document_signers (id)
        ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE UNIQUE INDEX IF NOT EXISTS uq_signatures_doc_signer ON signatures (document_id, signer_id) WHERE deleted_at IS NULL;
CREATE INDEX IF NOT EXISTS idx_signatures_document ON signatures (document_id);
CREATE INDEX IF NOT EXISTS idx_signatures_signer ON signatures (signer_id);
CREATE INDEX IF NOT EXISTS idx_signatures_civitas ON signatures (civitas_id);
CREATE INDEX IF NOT EXISTS idx_signatures_doc_signer_id ON signatures (document_signer_id);
CREATE INDEX IF NOT EXISTS idx_signatures_deleted_at ON signatures (deleted_at);

DROP TRIGGER IF EXISTS trg_signatures_updated_at ON signatures;
CREATE TRIGGER trg_signatures_updated_at
BEFORE UPDATE ON signatures
FOR EACH ROW EXECUTE FUNCTION update_timestamp_trigger();


-- ============================================================
-- 6. USER_SIGNATURES (Template Tanda Tangan Pribadi)
--    Menyimpan template tanda tangan milik user.
--    is_default bertipe SMALLINT (0 atau 1) agar kompatibel penuh
--    dengan kueri integer pada UserSignaturesModel.
-- ============================================================
CREATE TABLE IF NOT EXISTS user_signatures (
    id           CHAR(36)     NOT NULL,
    user_id      CHAR(36)     NOT NULL,
    label        VARCHAR(100) DEFAULT NULL,
    image_path   VARCHAR(500) NOT NULL,
    is_default   SMALLINT     NOT NULL DEFAULT 0 CHECK (is_default IN (0, 1)),
    created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at   TIMESTAMP    DEFAULT NULL,
    CONSTRAINT pk_user_signatures PRIMARY KEY (id),
    CONSTRAINT fk_user_signatures_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_user_signatures_user ON user_signatures (user_id);
CREATE INDEX IF NOT EXISTS idx_user_signatures_deleted_at ON user_signatures (deleted_at);

DROP TRIGGER IF EXISTS trg_user_signatures_updated_at ON user_signatures;
CREATE TRIGGER trg_user_signatures_updated_at
BEFORE UPDATE ON user_signatures
FOR EACH ROW EXECUTE FUNCTION update_timestamp_trigger();


-- ============================================================
-- 7. AUDIT_LOGS
--    Jejak audit dan riwayat seluruh aktivitas penting di sistem
--    (append-only).
-- ============================================================
CREATE TABLE IF NOT EXISTS audit_logs (
    id           CHAR(36)     NOT NULL,
    user_id      CHAR(36)     DEFAULT NULL,
    document_id  CHAR(36)     DEFAULT NULL,
    action       VARCHAR(100) NOT NULL,
    details      JSONB        DEFAULT NULL,
    ip_address   VARCHAR(45)  DEFAULT NULL,
    user_agent   TEXT         DEFAULT NULL,
    created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_audit_logs PRIMARY KEY (id),
    CONSTRAINT fk_audit_logs_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_audit_logs_document
        FOREIGN KEY (document_id) REFERENCES documents (id)
        ON DELETE SET NULL ON UPDATE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_audit_logs_user ON audit_logs (user_id);
CREATE INDEX IF NOT EXISTS idx_audit_logs_document ON audit_logs (document_id);
CREATE INDEX IF NOT EXISTS idx_audit_logs_action ON audit_logs (action);
CREATE INDEX IF NOT EXISTS idx_audit_logs_created_at ON audit_logs (created_at);
