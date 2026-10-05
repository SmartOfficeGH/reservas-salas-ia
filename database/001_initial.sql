-- Importar en una base vacía. No crea ni elimina la base de datos.
SET NAMES utf8mb4;
CREATE TABLE users (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 email VARCHAR(254) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL UNIQUE,
 password_hash VARCHAR(255) NOT NULL,
 verified_at DATETIME NULL,
 auth_version INT UNSIGNED NOT NULL DEFAULT 1,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE email_tokens (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 user_id BIGINT UNSIGNED NOT NULL,
 kind VARCHAR(12) NOT NULL,
 token_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL UNIQUE,
 expires_at DATETIME NOT NULL,
 UNIQUE KEY one_active_token (user_id,kind),
 FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE rooms (
 id INT UNSIGNED NOT NULL PRIMARY KEY,
 name VARCHAR(100) NOT NULL,
 building VARCHAR(200) NOT NULL,
 capacity SMALLINT UNSIGNED NOT NULL,
 description VARCHAR(255) NOT NULL,
 is_reservable TINYINT UNSIGNED NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE reservations (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 user_id BIGINT UNSIGNED NOT NULL,
 room_id INT UNSIGNED NOT NULL,
 concept VARCHAR(255) NOT NULL,
 day DATE NOT NULL,
 starts_at TIME NOT NULL,
 ends_at TIME NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY room_day (room_id,day,starts_at,ends_at),
 KEY owner_day (user_id,day),
 FOREIGN KEY (user_id) REFERENCES users(id),
 FOREIGN KEY (room_id) REFERENCES rooms(id),
 CONSTRAINT valid_times CHECK (ends_at > starts_at AND starts_at >= '07:00:00' AND ends_at <= '16:00:00')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE rate_limits (
 bucket CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL PRIMARY KEY,
 hits INT UNSIGNED NOT NULL,
 expires_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT INTO rooms (id,name,building,capacity,description) VALUES
(1,'SALA GRANDE INNOVACIÓN','Departamento Innovación (Parque Pocoyó)',18,'Espacio amplio para reuniones plenarias y presentaciones'),
(3,'SALA OTAE','Edificio Urbanismo (Avenidas)',15,'Ideal para reuniones de trabajo y presentaciones');
