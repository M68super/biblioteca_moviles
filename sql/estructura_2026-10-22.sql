-- ========================================================
-- ELIMINAR TABLAS ANTIGUAS SI EXISTEN
-- ========================================================
DROP TABLE IF EXISTS characteristics_storage_versions;
DROP TABLE IF EXISTS camera_modules;
DROP TABLE IF EXISTS characteristics_connectivity;
DROP TABLE IF EXISTS characteristics_battery;
DROP TABLE IF EXISTS characteristics_screen;
DROP TABLE IF EXISTS characteristics_storage;
DROP TABLE IF EXISTS characteristics_soc;
DROP TABLE IF EXISTS phone_characteristics;
DROP TABLE IF EXISTS software_support;
DROP TABLE IF EXISTS phone_metadata;
DROP TABLE IF EXISTS phones;
DROP TABLE IF EXISTS series;
DROP TABLE IF EXISTS brands;
DROP TABLE IF EXISTS operative_systems;
DROP TABLE IF EXISTS users;

-- ========================================================
-- 1. TABLA DE USUARIOS
-- ========================================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    is_admin TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================================
-- 2. SISTEMAS OPERATIVOS Y MARCAS
-- ========================================================
CREATE TABLE operative_systems (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE brands (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    operative_system_id INT,
    FOREIGN KEY (operative_system_id) REFERENCES operative_systems(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE series (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    brand_id INT NOT NULL,
    FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================================
-- 3. TELÉFONOS (PHONES)
-- ========================================================
CREATE TABLE phones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    series_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    launch_year INT NOT NULL,
    tier VARCHAR(50) NOT NULL,
    FOREIGN KEY (series_id) REFERENCES series(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================================
-- 4. SOPORTE DE SOFTWARE
-- ========================================================
CREATE TABLE software_support (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone_id INT NOT NULL,
    initial_skin VARCHAR(100),
    os_and_skin_update_years INT,
    security_update_years INT,
    FOREIGN KEY (phone_id) REFERENCES phones(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================================
-- 5. METADATOS, DIMENSIONES, PESO Y PRECIOS REGIONALES
-- ========================================================
CREATE TABLE phone_metadata (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone_id INT NOT NULL,
    image_url VARCHAR(255),
    score_avg DECIMAL(4,2) DEFAULT 0.00,
    -- Las 6 notas personales que dibuja el hexágono de detalles.php (0 - 10) y el comentario
    score_1 DECIMAL(4,2) NULL, -- Diseño
    score_2 DECIMAL(4,2) NULL, -- Pantalla
    score_3 DECIMAL(4,2) NULL, -- Rendimiento
    score_4 DECIMAL(4,2) NULL, -- Cámaras
    score_5 DECIMAL(4,2) NULL, -- Batería
    score_6 DECIMAL(4,2) NULL, -- Software
    personal_comment TEXT NULL,
    ip_rating VARCHAR(20),
    frame_material VARCHAR(100),
    back_material VARCHAR(100),
    height_mm DECIMAL(5,2),
    width_mm DECIMAL(5,2),
    thickness_mm DECIMAL(5,2),
    weight_g INT,
    
    -- Precios individuales por región
    price_global DECIMAL(10,2),
    price_ue DECIMAL(10,2),
    price_china DECIMAL(10,2),
    price_india DECIMAL(10,2),
    price_japan DECIMAL(10,2),
    price_korea DECIMAL(10,2),
    price_usa DECIMAL(10,2),
    price_latam DECIMAL(10,2),
    price_taiwan DECIMAL(10,2),
    price_russia DECIMAL(10,2),
    price_carrier DECIMAL(10,2),
    carrier_currency VARCHAR(10) DEFAULT 'EUR',
    carrier_details TEXT, -- Nombre de la operadora y detalles

    FOREIGN KEY (phone_id) REFERENCES phones(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================================
-- 6. CARACTERÍSTICAS TÉCNICAS (PADRE)
-- ========================================================
CREATE TABLE phone_characteristics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone_id INT NOT NULL,
    FOREIGN KEY (phone_id) REFERENCES phones(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================================
-- 7. SUBMÓDULOS DE CARACTERÍSTICAS TÉCNICAS
-- ========================================================

-- Procesador (SoC)
CREATE TABLE characteristics_soc (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone_characteristic_id INT NOT NULL,
    brand VARCHAR(100),
    full_name VARCHAR(150),
    architecture_nm INT,
    antutu_score INT,
    geekbench_single INT,
    geekbench_multi INT,
    FOREIGN KEY (phone_characteristic_id) REFERENCES phone_characteristics(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Almacenamiento y Versiones de Memoria Múltiples
CREATE TABLE characteristics_storage_versions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone_characteristic_id INT NOT NULL,
    ram_capacities_gb VARCHAR(50) NOT NULL,
    ram_technology VARCHAR(50) NOT NULL,
    storage_capacities_gb VARCHAR(50) NOT NULL,
    storage_technology VARCHAR(50) NOT NULL,
    FOREIGN KEY (phone_characteristic_id) REFERENCES phone_characteristics(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pantalla
CREATE TABLE characteristics_screen (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone_characteristic_id INT NOT NULL,
    inches DECIMAL(4,2),
    resolution VARCHAR(50),
    refresh_rate_hz INT,
    technology VARCHAR(100),
    FOREIGN KEY (phone_characteristic_id) REFERENCES phone_characteristics(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Batería y Carga
CREATE TABLE characteristics_battery (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone_characteristic_id INT NOT NULL,
    capacity_mah INT,
    wired_charging_w INT,
    wireless_charging_w INT,
    reverse_wired_charging_w INT,
    reverse_wireless_charging_w INT,
    assured_cycles_80_health INT NULL, -- Ciclos hasta el 80 % de salud
    FOREIGN KEY (phone_characteristic_id) REFERENCES phone_characteristics(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Conectividad y Redes (Con NFC)
CREATE TABLE characteristics_connectivity (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone_characteristic_id INT NOT NULL,
    mobile_network VARCHAR(100),
    wifi_version VARCHAR(50),
    bluetooth_version VARCHAR(50),
    nfc TINYINT(1) DEFAULT 0,
    esim_support TINYINT(1) DEFAULT 0,
    android_auto TINYINT(1) DEFAULT 0,
    FOREIGN KEY (phone_characteristic_id) REFERENCES phone_characteristics(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sistema de Cámaras (Principal + Auxiliares con Apertura Decimal y Vídeo Avanzado)
CREATE TABLE camera_modules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone_characteristic_id INT NOT NULL,
    camera_type VARCHAR(50) NOT NULL,
    sensor_name VARCHAR(100),
    sensor_size VARCHAR(50),
    resolution_mp DECIMAL(5,2),
    aperture DECIMAL(4,2),
    normal_video_max VARCHAR(50),
    normal_video_max_2 VARCHAR(50) NULL, -- Segunda opción (ej: 8K @ 30fps)
    has_log TINYINT(1) DEFAULT 0,
    log_resolution VARCHAR(50),
    log_fps INT,
    log_resolution_2 VARCHAR(50) NULL,
    log_fps_2 INT NULL,
    has_opengate TINYINT(1) DEFAULT 0,
    opengate_resolution VARCHAR(50),
    opengate_fps INT,
    opengate_resolution_2 VARCHAR(50) NULL,
    opengate_fps_2 INT NULL,
    has_opengate_log TINYINT(1) DEFAULT 0,
    opengate_log_resolution VARCHAR(50),
    opengate_log_fps INT,
    opengate_log_resolution_2 VARCHAR(50) NULL,
    opengate_log_fps_2 INT NULL,
    FOREIGN KEY (phone_characteristic_id) REFERENCES phone_characteristics(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;