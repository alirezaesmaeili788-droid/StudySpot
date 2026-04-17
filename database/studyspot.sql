CREATE DATABASE IF NOT EXISTS studyspot CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE studyspot;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('student', 'owner', 'admin') NOT NULL DEFAULT 'student',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS spots (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    type ENUM('Cafe', 'Bibliothek', 'Uni', 'CoWorking', 'Sonstiges') NOT NULL DEFAULT 'Sonstiges',
    address VARCHAR(255) NOT NULL,
    zip VARCHAR(20) NOT NULL,
    city VARCHAR(100) NOT NULL,
    opening_hours TEXT NULL,
    description TEXT NULL,
    image_url VARCHAR(255) NULL,
    wifi TINYINT(1) NOT NULL DEFAULT 0,
    power_outlets TINYINT(1) NOT NULL DEFAULT 0,
    quiet_level ENUM('quiet', 'medium', 'loud') NOT NULL DEFAULT 'medium',
    group_friendly TINYINT(1) NOT NULL DEFAULT 0,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_spots_created_by FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS reviews (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    spot_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    rating TINYINT UNSIGNED NOT NULL,
    comment TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_reviews_rating CHECK (rating BETWEEN 1 AND 5),
    CONSTRAINT uq_reviews_spot_user UNIQUE (spot_id, user_id),
    CONSTRAINT fk_reviews_spot FOREIGN KEY (spot_id) REFERENCES spots (id) ON DELETE CASCADE,
    CONSTRAINT fk_reviews_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS place_requests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    place_type ENUM('cafe', 'bibliothek', 'coworking', 'sonstiges') NOT NULL,
    place_name VARCHAR(150) NOT NULL,
    contact_person VARCHAR(150) NULL,
    email VARCHAR(190) NOT NULL,
    phone VARCHAR(50) NULL,
    street VARCHAR(255) NOT NULL,
    zip VARCHAR(20) NOT NULL,
    city VARCHAR(100) NOT NULL,
    district TINYINT UNSIGNED NULL,
    website VARCHAR(255) NULL,
    hours TEXT NOT NULL,
    suitable JSON NULL,
    features JSON NULL,
    description TEXT NOT NULL,
    notes TEXT NULL,
    photo_url VARCHAR(255) NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    created_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_place_requests_created_by FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS contact_messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL,
    subject VARCHAR(120) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('new', 'read', 'archived') NOT NULL DEFAULT 'new',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_spots_city ON spots (city);
CREATE INDEX idx_spots_type ON spots (type);
CREATE INDEX idx_place_requests_status ON place_requests (status);
CREATE INDEX idx_contact_messages_status ON contact_messages (status);
