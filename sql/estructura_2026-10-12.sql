-- Guardado temporal

SET FOREIGN_KEY_CHECKS = 0;

-- 1. IDENTIDAD Y CLASIFICACIÓN
CREATE TABLE IF NOT EXISTS `corporate_groups` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `brands` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `corporate_group_id` INT DEFAULT NULL,
  `name` VARCHAR(150) NOT NULL,
  FOREIGN KEY (`corporate_group_id`) REFERENCES `corporate_groups`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `series` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `brand_id` INT NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  FOREIGN KEY (`brand_id`) REFERENCES `brands`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `phones` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `series_id` INT NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `launch_year` INT DEFAULT NULL,
  `tier` VARCHAR(50) DEFAULT NULL,
  FOREIGN KEY (`series_id`) REFERENCES `series`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `commercial_names` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `phone_id` INT NOT NULL,
  `region_name` VARCHAR(100) DEFAULT NULL,
  `official_name` VARCHAR(150) NOT NULL,
  FOREIGN KEY (`phone_id`) REFERENCES `phones`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 2. HARDWARE Y CHIPSET (SoC, Benchmarks, Memoria, Pantalla, Batería, Conectividad)
CREATE TABLE IF NOT EXISTS `chipset_vendors` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `chipsets` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `vendor_id` INT NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `tier` VARCHAR(50) DEFAULT NULL,
  `launch_year` INT DEFAULT NULL,
  FOREIGN KEY (`vendor_id`) REFERENCES `chipset_vendors`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `benchmark_results` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `chipset_id` INT NOT NULL,
  `antutu_score` INT DEFAULT NULL,
  `geekbench_single` INT DEFAULT NULL,
  `geekbench_multi` INT DEFAULT NULL,
  `gpu_tflops` DECIMAL(5,2) DEFAULT NULL,
  `npu_tops` DECIMAL(5,2) DEFAULT NULL,
  FOREIGN KEY (`chipset_id`) REFERENCES `chipsets`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `memory_configurations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `phone_id` INT NOT NULL,
  `ram_gb` INT DEFAULT NULL,
  `ram_type` VARCHAR(50) DEFAULT NULL,
  `storage_gb` INT DEFAULT NULL,
  `storage_type` VARCHAR(50) DEFAULT NULL,
  FOREIGN KEY (`phone_id`) REFERENCES `phones`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 3. CÁMARAS Y MÓDULOS AVANZADOS
CREATE TABLE IF NOT EXISTS `camera_sensors` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `sensor_size` VARCHAR(50) DEFAULT NULL,
  `megapixels` DECIMAL(5,2) DEFAULT NULL,
  `focal_length_mm` INT DEFAULT NULL,
  `aperture` VARCHAR(20) DEFAULT NULL,
  `stabilization` VARCHAR(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `camera_modules` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `phone_id` INT NOT NULL,
  `sensor_id` INT NOT NULL,
  `camera_type` VARCHAR(50) NOT NULL, -- Principal, Ultra gran angular, Teleobjetivo, Macro, etc.
  `notes` TEXT DEFAULT NULL,
  FOREIGN KEY (`phone_id`) REFERENCES `phones`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`sensor_id`) REFERENCES `camera_sensors`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 4. SOFTWARE Y SOPORTE
CREATE TABLE IF NOT EXISTS `software_support` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `phone_id` INT NOT NULL,
  `initial_os` VARCHAR(100) DEFAULT NULL,
  `skin` VARCHAR(100) DEFAULT NULL,
  `system_updates_years` INT DEFAULT NULL,
  `security_updates_years` INT DEFAULT NULL,
  FOREIGN KEY (`phone_id`) REFERENCES `phones`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 5. REGIONES, VARIANTES Y COLORES
CREATE TABLE IF NOT EXISTS `regions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `flag_emoji` VARCHAR(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `colors` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `is_special_edition` TINYINT(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `phone_variants` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `phone_id` INT NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  FOREIGN KEY (`phone_id`) REFERENCES `phones`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `variant_regions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `phone_variant_id` INT NOT NULL,
  `region_id` INT NOT NULL,
  FOREIGN KEY (`phone_variant_id`) REFERENCES `phone_variants`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`region_id`) REFERENCES `regions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `variant_specs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `phone_variant_id` INT NOT NULL,
  `battery_mah` INT DEFAULT NULL,
  `wired_charging_w` INT DEFAULT NULL,
  `wireless_charging_w` INT DEFAULT NULL,
  `screen_inches` DECIMAL(4,2) DEFAULT NULL,
  `screen_technology` VARCHAR(100) DEFAULT NULL,
  `screen_resolution` VARCHAR(100) DEFAULT NULL,
  `refresh_rate_hz` INT DEFAULT NULL,
  `peak_brightness_nits` INT DEFAULT NULL,
  FOREIGN KEY (`phone_variant_id`) REFERENCES `phone_variants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 6. USUARIOS, COLECCIÓN, PRIORIDADES Y OPINIONES
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(100) NOT NULL UNIQUE,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) DEFAULT NULL,
  `is_admin` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cuenta Admin de prueba (Usuario: admin | Contraseña: admin)
INSERT INTO `users` (`id`, `username`, `email`, `password`, `is_admin`) 
VALUES (1, 'admin', 'admin@biblioteca.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1)
ON DUPLICATE KEY UPDATE `username` = 'admin';

CREATE TABLE IF NOT EXISTS `rating_categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `display_order` INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `rating_categories` (`id`, `name`, `display_order`) VALUES
(1, 'Batería', 1),
(2, 'Sistema operativo', 2),
(3, 'Potencia', 3),
(4, 'Cámaras', 4),
(5, 'Pantalla', 5),
(6, 'Diseño', 6)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

CREATE TABLE IF NOT EXISTS `phone_ratings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `phone_id` INT NOT NULL,
  `category_id` INT NOT NULL,
  `score` DECIMAL(3,1) NOT NULL DEFAULT 1.0,
  FOREIGN KEY (`phone_id`) REFERENCES `phones`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`category_id`) REFERENCES `rating_categories`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `collection_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `phone_id` INT NOT NULL,
  `purchase_status` VARCHAR(50) DEFAULT 'Deseado', -- Adquirido, Alto, Medio, Bajo, Ninguno
  `priority` VARCHAR(50) DEFAULT 'Normal',
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`phone_id`) REFERENCES `phones`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `collection_color_preferences` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `collection_item_id` INT NOT NULL,
  `color_id` INT NOT NULL,
  `is_preferred` TINYINT(1) DEFAULT 0, -- Color "Normal" con tick o especial con estrella
  FOREIGN KEY (`collection_item_id`) REFERENCES `collection_items`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`color_id`) REFERENCES `colors`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_reviews` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `phone_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `comment` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`phone_id`) REFERENCES `phones`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_review_scores` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_review_id` INT NOT NULL,
  `category_id` INT NOT NULL,
  `score` DECIMAL(3,1) NOT NULL,
  FOREIGN KEY (`user_review_id`) REFERENCES `user_reviews`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`category_id`) REFERENCES `rating_categories`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 7. OTROS DATOS (Precios, Accesorios, Enlaces y Multimedia)
CREATE TABLE IF NOT EXISTS `launch_prices` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `phone_id` INT NOT NULL,
  `currency` VARCHAR(10) NOT NULL, -- EUR, CNY, USD, etc.
  `price_amount` DECIMAL(10,2) NOT NULL,
  `market_region` VARCHAR(100) DEFAULT NULL,
  FOREIGN KEY (`phone_id`) REFERENCES `phones`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `phone_resources` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `phone_id` INT NOT NULL,
  `dimensions` VARCHAR(100) DEFAULT NULL,
  `materials` VARCHAR(150) DEFAULT NULL,
  `certifications` VARCHAR(100) DEFAULT NULL, -- IP68, etc.
  `official_accessories` TEXT DEFAULT NULL, -- Kits fotográficos, etc.
  `review_links` TEXT DEFAULT NULL,
  `image_url` VARCHAR(255) DEFAULT NULL,
  `image_url_2` VARCHAR(255) DEFAULT NULL,
  FOREIGN KEY (`phone_id`) REFERENCES `phones`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;