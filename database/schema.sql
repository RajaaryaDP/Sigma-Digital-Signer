CREATE TABLE IF NOT EXISTS civitas (
    id CHAR(36) NOT NULL,
    identifier VARCHAR(64) NOT NULL,
    name VARCHAR(150) NOT NULL,
    type ENUM('dosen', 'mahasiswa', 'tendik') NOT NULL,
    department VARCHAR(150) NOT NULL,
    position VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL,
    phone VARCHAR(32) DEFAULT NULL,
    avatar_path VARCHAR(255) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_civitas_identifier (identifier),
    UNIQUE KEY uq_civitas_email (email),
    KEY idx_civitas_type (type),
    KEY idx_civitas_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
    id CHAR(36) NOT NULL,
    civitas_id CHAR(36) DEFAULT NULL,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_civitas_id (civitas_id),
    KEY idx_users_deleted_at (deleted_at),
    CONSTRAINT fk_users_civitas
        FOREIGN KEY (civitas_id) REFERENCES civitas (id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_signatures (
    id CHAR(36) NOT NULL,
    user_id CHAR(36) NOT NULL,
    label VARCHAR(100) DEFAULT NULL,
    image_path VARCHAR(500) NOT NULL,
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_user_signatures_user_active (user_id, deleted_at, is_default),
    CONSTRAINT fk_user_signatures_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS documents (
    id CHAR(36) NOT NULL,
    uploader_id CHAR(36) NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_name_original VARCHAR(255) NOT NULL,
    file_size BIGINT UNSIGNED NOT NULL,
    file_hash CHAR(64) NOT NULL,
    signature_type ENUM('single', 'sequential') NOT NULL DEFAULT 'sequential',
    status ENUM('draft', 'pending_review', 'approved', 'signing', 'completed', 'rejected') NOT NULL DEFAULT 'draft',
    current_step INT UNSIGNED NOT NULL DEFAULT 0,
    reviewed_by CHAR(36) DEFAULT NULL,
    reviewed_at DATETIME DEFAULT NULL,
    rejection_reason TEXT DEFAULT NULL,
    completed_at DATETIME DEFAULT NULL,
    final_file_path VARCHAR(500) DEFAULT NULL,
    final_file_hash CHAR(64) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_documents_uploader_status (uploader_id, status, deleted_at),
    KEY idx_documents_review_queue (status, deleted_at, created_at),
    KEY idx_documents_reviewed_by (reviewed_by),
    CONSTRAINT fk_documents_uploader
        FOREIGN KEY (uploader_id) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_documents_reviewer
        FOREIGN KEY (reviewed_by) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS document_signers (
    id CHAR(36) NOT NULL,
    document_id CHAR(36) NOT NULL,
    user_id CHAR(36) NOT NULL,
    invited_by CHAR(36) DEFAULT NULL,
    step_order INT UNSIGNED NOT NULL,
    role_title VARCHAR(150) DEFAULT NULL,
    status ENUM('waiting', 'pending', 'signed', 'declined') NOT NULL DEFAULT 'waiting',
    decline_reason TEXT DEFAULT NULL,
    signed_at DATETIME DEFAULT NULL,
    read_at DATETIME DEFAULT NULL,
    page_number INT UNSIGNED NOT NULL DEFAULT 1,
    x_percent DECIMAL(6,2) NOT NULL DEFAULT 0,
    y_percent DECIMAL(6,2) NOT NULL DEFAULT 0,
    width_percent DECIMAL(6,2) NOT NULL DEFAULT 20,
    height_percent DECIMAL(6,2) NOT NULL DEFAULT 10,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_document_signers_document_step (document_id, step_order, status, deleted_at),
    KEY idx_document_signers_user_status (user_id, status, deleted_at),
    KEY idx_document_signers_invited_by (invited_by),
    CONSTRAINT fk_document_signers_document
        FOREIGN KEY (document_id) REFERENCES documents (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_document_signers_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_document_signers_inviter
        FOREIGN KEY (invited_by) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS signatures (
    id CHAR(36) NOT NULL,
    document_id CHAR(36) NOT NULL,
    signer_id CHAR(36) NOT NULL,
    civitas_id CHAR(36) DEFAULT NULL,
    document_signer_id CHAR(36) NOT NULL,
    signature_image_path VARCHAR(500) NOT NULL,
    verification_token VARCHAR(128) NOT NULL,
    qr_code_path VARCHAR(500) DEFAULT NULL,
    page_number INT UNSIGNED NOT NULL,
    x_percent DECIMAL(6,2) NOT NULL,
    y_percent DECIMAL(6,2) NOT NULL,
    width_percent DECIMAL(6,2) NOT NULL,
    height_percent DECIMAL(6,2) NOT NULL,
    signer_name_snapshot VARCHAR(150) NOT NULL,
    signer_identifier_snapshot VARCHAR(64) DEFAULT NULL,
    signer_type_snapshot VARCHAR(32) DEFAULT NULL,
    signer_department_snapshot VARCHAR(150) DEFAULT NULL,
    signer_position_snapshot VARCHAR(150) DEFAULT NULL,
    signer_email_snapshot VARCHAR(190) NOT NULL,
    signed_at DATETIME NOT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_signatures_verification_token (verification_token),
    KEY idx_signatures_document (document_id, deleted_at, signed_at),
    KEY idx_signatures_signer (signer_id, deleted_at),
    KEY idx_signatures_civitas (civitas_id),
    KEY idx_signatures_document_signer (document_signer_id),
    CONSTRAINT fk_signatures_document
        FOREIGN KEY (document_id) REFERENCES documents (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_signatures_signer
        FOREIGN KEY (signer_id) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_signatures_civitas
        FOREIGN KEY (civitas_id) REFERENCES civitas (id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_signatures_document_signer
        FOREIGN KEY (document_signer_id) REFERENCES document_signers (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_logs (
    id CHAR(36) NOT NULL,
    user_id CHAR(36) DEFAULT NULL,
    document_id CHAR(36) DEFAULT NULL,
    action VARCHAR(100) NOT NULL,
    details JSON DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_audit_logs_user_created (user_id, created_at),
    KEY idx_audit_logs_document_created (document_id, created_at),
    CONSTRAINT fk_audit_logs_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_audit_logs_document
        FOREIGN KEY (document_id) REFERENCES documents (id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
