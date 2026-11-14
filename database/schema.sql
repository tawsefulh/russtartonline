-- RusStart database schema + default data.
-- Import this once into an empty database (phpMyAdmin > Import).

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS cities (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(80)  NOT NULL,
    name_ru    VARCHAR(80)  NULL,
    slug       VARCHAR(100) NOT NULL UNIQUE,
    is_default TINYINT(1)   NOT NULL DEFAULT 0,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS categories (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    type       ENUM('article','ad') NOT NULL,
    name       VARCHAR(80)  NOT NULL,
    name_ru    VARCHAR(80)  NULL,
    slug       VARCHAR(100) NOT NULL,
    is_default TINYINT(1)   NOT NULL DEFAULT 0,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_type_slug (type, slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(100) NOT NULL,
    username      VARCHAR(30)  NOT NULL UNIQUE,
    email         VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    city_id       INT UNSIGNED NULL,
    university    VARCHAR(160) NULL,
    avatar        VARCHAR(120) NULL,
    totp_secret     VARCHAR(64) NULL,
    totp_enabled_at DATETIME    NULL,
    totp_last_step  BIGINT      NULL,
    deactivated_at  DATETIME    NULL,
    created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_users_city FOREIGN KEY (city_id) REFERENCES cities(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS articles (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    city_id     INT UNSIGNED NOT NULL,
    category_id INT UNSIGNED NOT NULL,
    title       VARCHAR(160) NOT NULL,
    slug        VARCHAR(190) NOT NULL UNIQUE,
    excerpt     VARCHAR(300) NOT NULL,
    body        MEDIUMTEXT   NOT NULL,
    cover       VARCHAR(120) NULL,
    views       INT UNSIGNED NOT NULL DEFAULT 0,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP    NULL,
    KEY idx_articles_city (city_id, created_at),
    KEY idx_articles_category (category_id, created_at),
    CONSTRAINT fk_articles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_articles_city FOREIGN KEY (city_id) REFERENCES cities(id),
    CONSTRAINT fk_articles_category FOREIGN KEY (category_id) REFERENCES categories(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ads (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    city_id     INT UNSIGNED NOT NULL,
    category_id INT UNSIGNED NOT NULL,
    title       VARCHAR(140) NOT NULL,
    slug        VARCHAR(190) NOT NULL UNIQUE,
    description TEXT         NOT NULL,
    price       DECIMAL(12,2) NULL,
    address     VARCHAR(255) NOT NULL,
    phone       VARCHAR(20)  NULL,
    whatsapp    VARCHAR(20)  NULL,
    telegram    VARCHAR(40)  NULL,
    image       VARCHAR(120) NULL,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP    NULL,
    KEY idx_ads_city (city_id, created_at),
    KEY idx_ads_category (category_id, created_at),
    CONSTRAINT fk_ads_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_ads_city FOREIGN KEY (city_id) REFERENCES cities(id),
    CONSTRAINT fk_ads_category FOREIGN KEY (category_id) REFERENCES categories(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS password_resets (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED NOT NULL,
    token_hash CHAR(64)     NOT NULL UNIQUE,
    expires_at DATETIME     NOT NULL,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_resets_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE IF NOT EXISTS remember_tokens (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id        INT UNSIGNED NOT NULL,
    selector       CHAR(24)     NOT NULL UNIQUE,
    validator_hash CHAR(64)     NOT NULL,
    expires_at     DATETIME     NOT NULL,
    created_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_remember_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default cities
INSERT IGNORE INTO cities (name, name_ru, slug, is_default) VALUES
('Moscow', 'Москва', 'moscow', 1),
('Saint Petersburg', 'Санкт-Петербург', 'saint-petersburg', 1),
('Kazan', 'Казань', 'kazan', 1),
('Novosibirsk', 'Новосибирск', 'novosibirsk', 1),
('Yekaterinburg', 'Екатеринбург', 'yekaterinburg', 1),
('Tomsk', 'Томск', 'tomsk', 1),
('Nizhny Novgorod', 'Нижний Новгород', 'nizhny-novgorod', 1),
('Samara', 'Самара', 'samara', 1),
('Rostov-on-Don', 'Ростов-на-Дону', 'rostov-on-don', 1),
('Ufa', 'Уфа', 'ufa', 1),
('Krasnoyarsk', 'Красноярск', 'krasnoyarsk', 1),
('Voronezh', 'Воронеж', 'voronezh', 1),
('Perm', 'Пермь', 'perm', 1),
('Volgograd', 'Волгоград', 'volgograd', 1),
('Kursk', 'Курск', 'kursk', 1),
('Belgorod', 'Белгород', 'belgorod', 1),
('Tula', 'Тула', 'tula', 1),
('Kaliningrad', 'Калининград', 'kaliningrad', 1),
('Vladivostok', 'Владивосток', 'vladivostok', 1),
('Krasnodar', 'Краснодар', 'krasnodar', 1);

-- Default article categories
INSERT IGNORE INTO categories (type, name, name_ru, slug, is_default) VALUES
('article', 'Visa & documents', 'Виза и документы', 'visa-documents', 1),
('article', 'Arrival & registration', 'Приезд и регистрация', 'arrival-registration', 1),
('article', 'Housing & dormitory', 'Жильё и общежитие', 'housing-dormitory', 1),
('article', 'University life', 'Учёба в университете', 'university-life', 1),
('article', 'Money & banking', 'Деньги и банки', 'money-banking', 1),
('article', 'SIM & internet', 'SIM-карта и интернет', 'sim-internet', 1),
('article', 'Transport', 'Транспорт', 'transport', 1),
('article', 'Food & groceries', 'Еда и продукты', 'food-groceries', 1),
('article', 'Health & insurance', 'Здоровье и страховка', 'health-insurance', 1),
('article', 'Russian language', 'Русский язык', 'russian-language', 1),
('article', 'Part-time work', 'Подработка', 'part-time-work', 1),
('article', 'Winter', 'Зима', 'winter', 1),
('article', 'Culture & daily life', 'Культура и быт', 'culture-daily-life', 1);

-- Default advertisement categories
INSERT IGNORE INTO categories (type, name, name_ru, slug, is_default) VALUES
('ad', 'Electronics & gadgets', 'Электроника и гаджеты', 'electronics-gadgets', 1),
('ad', 'Furniture & home', 'Мебель и товары для дома', 'furniture-home', 1),
('ad', 'Winter clothing', 'Зимняя одежда', 'winter-clothing', 1),
('ad', 'Books & study materials', 'Книги и учебные материалы', 'books-study-materials', 1),
('ad', 'Kitchen & appliances', 'Кухня и бытовая техника', 'kitchen-appliances', 1),
('ad', 'Home food', 'Домашняя еда', 'home-food', 1),
('ad', 'Rooms & flatmates', 'Комнаты и соседи', 'rooms-flatmates', 1),
('ad', 'Services', 'Услуги', 'services', 1),
('ad', 'Bicycles & transport', 'Велосипеды и транспорт', 'bicycles-transport', 1);
