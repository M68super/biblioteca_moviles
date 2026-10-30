-- ==========================================================================
-- BASE DE DATOS COMPLETA · CATÁLOGO DE MÓVILES · VERSIÓN 2026-10-24
--
-- Copia y pega TODO este archivo en phpMyAdmin (pestaña SQL) para crear la base de
-- datos desde cero con la estructura final. Incluye todas las modificaciones hechas
-- hasta hoy, no hace falta ejecutar ninguna migración antes ni después.
--
-- ATENCIÓN: si la base de datos "phone_catalog" ya existe, este script BORRA todas
-- sus tablas y todos sus datos. Haz antes una copia de seguridad (Exportar) si quieres
-- conservar algo.
--
-- Después de ejecutarlo, abre crear_admin.php en el navegador para crear el primer
-- administrador (y bórralo cuando termines).
-- ==========================================================================

CREATE DATABASE IF NOT EXISTS phone_catalog CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE phone_catalog;

-- ==========================================================================
-- BORRAR TABLAS ANTIGUAS (si existen). Se incluyen también tablas de versiones
-- anteriores del proyecto que ya no se usan.
-- ==========================================================================
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS camera_modules;
DROP TABLE IF EXISTS characteristics_storage_versions;
DROP TABLE IF EXISTS characteristics_storage;
DROP TABLE IF EXISTS characteristics_connectivity;
DROP TABLE IF EXISTS characteristics_battery;
DROP TABLE IF EXISTS characteristics_screen;
DROP TABLE IF EXISTS characteristics_soc;
DROP TABLE IF EXISTS phone_characteristics;
DROP TABLE IF EXISTS phone_variants;
DROP TABLE IF EXISTS software_support;
DROP TABLE IF EXISTS phone_metadata;
DROP TABLE IF EXISTS phones;
DROP TABLE IF EXISTS series;
DROP TABLE IF EXISTS brands;
DROP TABLE IF EXISTS operative_systems;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- ==========================================================================
-- 1. USUARIOS (la contraseña se guarda siempre como hash, nunca en texto)
-- ==========================================================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    is_admin TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================================================
-- 2. SISTEMAS OPERATIVOS, MARCAS Y SERIES
--    El sistema operativo es propio de cada móvil (ver tabla phones).
-- ==========================================================================
CREATE TABLE operative_systems (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE brands (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE series (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    brand_id INT NOT NULL,
    UNIQUE KEY uq_series_brand_name (brand_id, name),
    FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================================================
-- 3. TELÉFONOS
--    variant_group_id: los móviles con el mismo número son variantes regionales entre sí.
-- ==========================================================================
CREATE TABLE phones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    series_id INT NOT NULL,
    operative_system_id INT NULL,
    name VARCHAR(150) NOT NULL,
    launch_year INT NOT NULL,
    tier VARCHAR(50) NOT NULL DEFAULT '',
    variant_group_id INT NULL,
    INDEX idx_phones_variant_group (variant_group_id),
    FOREIGN KEY (series_id) REFERENCES series(id) ON DELETE CASCADE,
    FOREIGN KEY (operative_system_id) REFERENCES operative_systems(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================================================
-- 4. SOPORTE DE SOFTWARE (una fila por móvil)
-- ==========================================================================
CREATE TABLE software_support (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone_id INT NOT NULL,
    initial_skin VARCHAR(100),
    os_and_skin_update_years INT,
    security_update_years INT,
    UNIQUE KEY uq_software_phone (phone_id),
    FOREIGN KEY (phone_id) REFERENCES phones(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================================================
-- 5. METADATOS: foto, valoración personal, prioridad, dimensiones y materiales (una fila por móvil)
--    Los precios NO están aquí: cada versión de memoria tiene los suyos.
-- ==========================================================================
CREATE TABLE phone_metadata (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone_id INT NOT NULL,
    image_url VARCHAR(255),                       -- ruta de la foto subida (uploads/moviles/...) o enlace antiguo

    -- Valoración personal: las 6 notas del hexágono (0 - 10), su media automática y el comentario público
    score_avg DECIMAL(4,2) DEFAULT 0.00,
    score_1 DECIMAL(4,2) NULL,                    -- Diseño
    score_2 DECIMAL(4,2) NULL,                    -- Pantalla
    score_3 DECIMAL(4,2) NULL,                    -- Rendimiento
    score_4 DECIMAL(4,2) NULL,                    -- Cámaras
    score_5 DECIMAL(4,2) NULL,                    -- Batería
    score_6 DECIMAL(4,2) NULL,                    -- Software
    personal_comment TEXT NULL,

    -- Prioridad de compra (solo la ve el administrador): ninguna, baja, media, alta u obtenido
    priority VARCHAR(20) NOT NULL DEFAULT 'ninguna',
    owned_colors VARCHAR(255) NULL,               -- colores que ya tengo comprados (solo si priority = 'obtenido')

    -- Construcción
    ip_rating VARCHAR(20),
    frame_material VARCHAR(100),
    back_material VARCHAR(100),
    height_mm DECIMAL(5,2),
    width_mm DECIMAL(5,2),
    thickness_mm DECIMAL(5,2),
    weight_g INT,

    UNIQUE KEY uq_metadata_phone (phone_id),
    FOREIGN KEY (phone_id) REFERENCES phones(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================================================
-- 6. CARACTERÍSTICAS TÉCNICAS (fila "padre", una por móvil)
-- ==========================================================================
CREATE TABLE phone_characteristics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone_id INT NOT NULL,
    UNIQUE KEY uq_characteristics_phone (phone_id),
    FOREIGN KEY (phone_id) REFERENCES phones(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================================================
-- 7. SUBMÓDULOS DE CARACTERÍSTICAS TÉCNICAS
-- ==========================================================================

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
    UNIQUE KEY uq_soc_characteristic (phone_characteristic_id),
    FOREIGN KEY (phone_characteristic_id) REFERENCES phone_characteristics(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Versiones de memoria: una fila por combinación concreta (ej: 12 GB + 256 GB), con sus precios
-- por región y, opcionalmente, un precio exclusivo de operadora con su moneda y un comentario.
CREATE TABLE characteristics_storage_versions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone_characteristic_id INT NOT NULL,
    ram_gb INT NULL,
    ram_technology VARCHAR(50) NOT NULL DEFAULT '',
    storage_gb INT NULL,
    storage_technology VARCHAR(50) NOT NULL DEFAULT '',

    -- Precio de esta versión en cada región
    price_global DECIMAL(10,2) NULL,              -- España / global (€)
    price_ue DECIMAL(10,2) NULL,                  -- Unión Europea (€)
    price_china DECIMAL(10,2) NULL,               -- CNY
    price_india DECIMAL(10,2) NULL,               -- INR
    price_japan DECIMAL(10,2) NULL,               -- JPY
    price_korea DECIMAL(10,2) NULL,               -- KRW
    price_usa DECIMAL(10,2) NULL,                 -- USD
    price_latam DECIMAL(10,2) NULL,               -- MXN
    price_taiwan DECIMAL(10,2) NULL,              -- TWD
    price_russia DECIMAL(10,2) NULL,              -- RUB

    -- Exclusivo operadora: precio, moneda (EUR, CNY, INR, JPY, KRW, USD, MXN, TWD o RUB) y comentario explicativo
    price_carrier DECIMAL(10,2) NULL,
    carrier_currency VARCHAR(10) NULL,
    carrier_details TEXT NULL,

    FOREIGN KEY (phone_characteristic_id) REFERENCES phone_characteristics(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pantalla
CREATE TABLE characteristics_screen (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone_characteristic_id INT NOT NULL,
    inches DECIMAL(4,2),
    resolution VARCHAR(50),
    refresh_rate_hz INT,
    max_brightness_nits INT NULL,
    technology VARCHAR(100),
    protection VARCHAR(150) NULL,                 -- ej: Corning Gorilla Glass Victus
    UNIQUE KEY uq_screen_characteristic (phone_characteristic_id),
    FOREIGN KEY (phone_characteristic_id) REFERENCES phone_characteristics(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Batería y carga
CREATE TABLE characteristics_battery (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone_characteristic_id INT NOT NULL,
    capacity_mah INT,
    battery_type VARCHAR(50) NULL,                -- ej: Ion-Li, Si-C
    wired_charging_w INT,
    wireless_charging_w INT,
    reverse_wired_charging_w INT,
    reverse_wireless_charging_w INT,
    assured_cycles_80_health INT NULL,            -- ciclos hasta el 80 % de salud de la batería
    UNIQUE KEY uq_battery_characteristic (phone_characteristic_id),
    FOREIGN KEY (phone_characteristic_id) REFERENCES phone_characteristics(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Conectividad y redes
CREATE TABLE characteristics_connectivity (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone_characteristic_id INT NOT NULL,
    mobile_network VARCHAR(100),
    wifi_version VARCHAR(50),
    bluetooth_version VARCHAR(50),
    nfc TINYINT(1) DEFAULT 0,
    esim_support TINYINT(1) DEFAULT 0,
    android_auto TINYINT(1) DEFAULT 0,
    microsd TINYINT(1) NOT NULL DEFAULT 0,
    sim_config VARCHAR(40) NULL,                  -- 1 SIM, Dual SIM, SIM + MicroSD, Dual SIM + MicroSD, Dual SIM híbrido (SIM 2 o MicroSD), Solo MicroSD (sin SIM) o Sin SIM física (solo eSIM)
    UNIQUE KEY uq_connectivity_characteristic (phone_characteristic_id),
    FOREIGN KEY (phone_characteristic_id) REFERENCES phone_characteristics(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sistema de cámaras: principal + auxiliares. Cada tipo de vídeo admite dos opciones de resolución / fps
-- (ej: 4K a 120 fps y 8K a 30 fps). Los sensores que no graban vídeo no guardan datos de vídeo.
CREATE TABLE camera_modules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone_characteristic_id INT NOT NULL,
    camera_type VARCHAR(50) NOT NULL,
    sensor_name VARCHAR(100),
    sensor_size VARCHAR(50),
    resolution_mp DECIMAL(5,2),
    aperture DECIMAL(4,2),
    normal_video_max VARCHAR(50),
    normal_video_max_2 VARCHAR(50) NULL,
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
