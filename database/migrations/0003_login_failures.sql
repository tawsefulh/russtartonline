-- Migration 0003: remember failed log-in attempts, so password guessing can be slowed down.
CREATE TABLE IF NOT EXISTS login_failures (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ip         VARCHAR(45)  NOT NULL,
    login      VARCHAR(190) NOT NULL,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_failures_ip (ip, created_at),
    KEY idx_failures_login (login, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
