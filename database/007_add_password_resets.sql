-- ─────────────────────────────────────────────────────────────────────────────
-- Migración 007: Tabla de recuperación de contraseña
-- ─────────────────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS tbl_password_resets (
    id          INT              AUTO_INCREMENT PRIMARY KEY,
    user_id     INT              NOT NULL,
    email       VARCHAR(150)     NOT NULL,
    token_hash  VARCHAR(64)      NOT NULL,
    expires_at  DATETIME         NOT NULL,
    used_at     DATETIME         NULL,
    ip_address  VARCHAR(45)      NULL,
    user_agent  VARCHAR(255)     NULL,
    created_at  TIMESTAMP        DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_pr_email      (email),
    INDEX idx_pr_token_hash (token_hash),
    INDEX idx_pr_expires_at (expires_at),
    INDEX idx_pr_used_at    (used_at),

    CONSTRAINT fk_pr_user FOREIGN KEY (user_id)
        REFERENCES tbl_users(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
