-- Run once on a database created before the Profile / Security pages
-- (adds 2-step verification, sessions and deactivation).
ALTER TABLE users
    ADD COLUMN avatar          VARCHAR(120) NULL AFTER university,
    ADD COLUMN totp_secret     VARCHAR(64) NULL AFTER university,
    ADD COLUMN totp_enabled_at DATETIME    NULL AFTER totp_secret,
    ADD COLUMN totp_last_step  BIGINT      NULL AFTER totp_enabled_at,
    ADD COLUMN deactivated_at  DATETIME    NULL AFTER totp_last_step;

CREATE TABLE IF NOT EXISTS user_backup_codes (
    id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id   INT UNSIGNED NOT NULL,
    code_hash CHAR(64)     NOT NULL,
    used_at   DATETIME     NULL,
    KEY idx_backup_user (user_id),
    CONSTRAINT fk_backup_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_sessions (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id           INT UNSIGNED NOT NULL,
    session_key       CHAR(32)     NOT NULL UNIQUE,
    remember_selector CHAR(24)     NULL,
    ip                VARCHAR(45)  NOT NULL DEFAULT '',
    user_agent        VARCHAR(255) NOT NULL DEFAULT '',
    created_at        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_seen_at      DATETIME     NOT NULL,
    KEY idx_sessions_user (user_id),
    CONSTRAINT fk_sessions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
