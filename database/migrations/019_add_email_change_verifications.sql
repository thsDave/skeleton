-- Ejecutar esta migracion solo si la tabla tbl_email_change_verifications no existe.
-- No elimina ni modifica datos existentes.

CREATE TABLE tbl_email_change_verifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  current_email VARCHAR(150) NOT NULL,
  new_email VARCHAR(150) NOT NULL,
  code_hash VARCHAR(255) NOT NULL,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  attempts INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_email_change_user_id (user_id),
  INDEX idx_email_change_new_email (new_email),
  INDEX idx_email_change_expires_at (expires_at),
  INDEX idx_email_change_used_at (used_at),
  CONSTRAINT fk_email_change_user
    FOREIGN KEY (user_id) REFERENCES tbl_users(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
