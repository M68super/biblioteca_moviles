-- Crear base de datos
CREATE DATABASE IF NOT EXISTS biblioteca_moviles CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE biblioteca_moviles;

-- 1. Sistemas Operativos
CREATE TABLE IF NOT EXISTS operative_systems (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- 2. Marcas
CREATE TABLE IF NOT EXISTS brands (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    operative_system_id INT NULL,
    CONSTRAINT fk_brand_os FOREIGN KEY (operative_system_id) REFERENCES operative_systems(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 3. Series / Familias
CREATE TABLE IF NOT EXISTS series (
    id INT AUTO_INCREMENT PRIMARY KEY,
    brand_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    CONSTRAINT fk_series_brand FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 4. Teléfonos (Tabla Principal)
CREATE TABLE IF NOT EXISTS phones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    series_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    launch_year INT NULL,
    tier VARCHAR(50) NULL, -- Entrada, Baja, Media, Alta, Premium, etc.
    CONSTRAINT fk_phone_series FOREIGN KEY (series_id) REFERENCES series(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 5. Soporte de Software
CREATE TABLE IF NOT EXISTS software_support (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone_id INT NOT NULL,
    initial_os VARCHAR(100) NULL,
    initial_skin VARCHAR(100) NULL,
    os_and_skin_update_years INT NULL,
    security_update_years INT NULL,
    CONSTRAINT fk_sw_phone FOREIGN KEY (phone_id) REFERENCES phones(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 6. Metadatos y Construcción (incluye materiales de marco y trasera)
CREATE TABLE IF NOT EXISTS phone_metadata (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone_id INT NOT NULL,
    height_mm DECIMAL(6,2) NULL,
    width_mm DECIMAL(6,2) NULL,
    thickness_body_mm DECIMAL(5,2) NULL,
    thickness_camera_extra_mm DECIMAL(5,2) NULL,
    ip_rating VARCHAR(50) NULL,
    frame_material VARCHAR(100) NULL,
    back_material VARCHAR(100) NULL,
    price_eur DECIMAL(10,2) NULL,
    price_cny DECIMAL(10,2) NULL,
    official_accessories TEXT NULL,
    CONSTRAINT fk_meta_phone FOREIGN KEY (phone_id) REFERENCES phones(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 7. Características Generales / Versiones del Móvil
CREATE TABLE IF NOT EXISTS phone_characteristics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone_id INT NOT NULL,
    variant_name VARCHAR(100) DEFAULT 'Versión Estándar',
    CONSTRAINT fk_char_phone FOREIGN KEY (phone_id) REFERENCES phones(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 8. Procesador (SoC)
CREATE TABLE IF NOT EXISTS characteristics_soc (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone_characteristic_id INT NOT NULL,
    brand VARCHAR(100) NULL,
    full_name VARCHAR(150) NULL,
    launch_year INT NULL,
    architecture_nm INT NULL,
    antutu_score INT NULL,
    geekbench_single INT NULL,
    geekbench_multi INT NULL,
    gpu_tflops DECIMAL(5,2) NULL,
    npu_tops DECIMAL(6,2) NULL,
    CONSTRAINT fk_soc_char FOREIGN KEY (phone_characteristic_id) REFERENCES phone_characteristics(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 9. Almacenamiento y RAM
CREATE TABLE IF NOT EXISTS characteristics_storage (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone_characteristic_id INT NOT NULL,
    ram_capacities_gb VARCHAR(100) NULL,
    ram_technology VARCHAR(50) NULL,
    storage_capacities_gb VARCHAR(100) NULL,
    storage_technology VARCHAR(50) NULL,
    CONSTRAINT fk_storage_char FOREIGN KEY (phone_characteristic_id) REFERENCES phone_characteristics(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 10. Pantalla
CREATE TABLE IF NOT EXISTS characteristics_screen (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone_characteristic_id INT NOT NULL,
    inches DECIMAL(4,2) NULL,
    resolution VARCHAR(50) NULL,
    refresh_rate_hz INT NULL,
    max_brightness_nits INT NULL,
    technology VARCHAR(100) NULL,
    resistance_certificate VARCHAR(150) NULL,
    screen_to_body_ratio DECIMAL(5,2) NULL,
    CONSTRAINT fk_screen_char FOREIGN KEY (phone_characteristic_id) REFERENCES phone_characteristics(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 11. Batería
CREATE TABLE IF NOT EXISTS characteristics_battery (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone_characteristic_id INT NOT NULL,
    capacity_mah INT NULL,
    wired_charging_w INT NULL,
    wireless_charging_w INT NULL,
    reverse_wired_charging_w INT NULL,
    reverse_wireless_charging_w INT NULL,
    assured_cycles_80_health INT NULL,
    CONSTRAINT fk_battery_char FOREIGN KEY (phone_characteristic_id) REFERENCES phone_characteristics(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 12. Conectividad
CREATE TABLE IF NOT EXISTS characteristics_connectivity (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone_characteristic_id INT NOT NULL,
    mobile_network VARCHAR(50) NULL,
    wifi_version VARCHAR(50) NULL,
    bluetooth_version VARCHAR(50) NULL,
    nfc TINYINT(1) DEFAULT 0,
    esim_support TINYINT(1) DEFAULT 0,
    CONSTRAINT fk_conn_char FOREIGN KEY (phone_characteristic_id) REFERENCES phone_characteristics(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 13. Módulos de Cámaras
CREATE TABLE IF NOT EXISTS camera_modules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone_characteristic_id INT NOT NULL,
    camera_type VARCHAR(50) NOT NULL, -- Principal, Ultra gran angular, Teleobjetivo, etc.
    sensor_name VARCHAR(150) NULL,
    sensor_size VARCHAR(50) NULL,
    resolution_mp DECIMAL(6,2) NULL,
    aperture VARCHAR(50) NULL,
    focal_length_mm INT NULL,
    normal_video_max VARCHAR(100) NULL,
    has_log TINYINT(1) DEFAULT 0,
    log_specs VARCHAR(255) NULL,
    has_opengate TINYINT(1) DEFAULT 0,
    opengate_specs VARCHAR(255) NULL,
    CONSTRAINT fk_cam_char FOREIGN KEY (phone_characteristic_id) REFERENCES phone_characteristics(id) ON DELETE CASCADE
) ENGINE=InnoDB;