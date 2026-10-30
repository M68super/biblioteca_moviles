-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 30-10-2026 a las 08:05:31
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `phone_catalog`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `brands`
--

CREATE TABLE `brands` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `operative_system_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `brands`
--

INSERT INTO `brands` (`id`, `name`, `operative_system_id`) VALUES
(10, 'OPPO', 1),
(11, 'Xiaomi', 1),
(12, 'Nothing', NULL),
(13, 'VIVO', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `camera_modules`
--

CREATE TABLE `camera_modules` (
  `id` int(11) NOT NULL,
  `phone_characteristic_id` int(11) NOT NULL,
  `camera_type` varchar(50) NOT NULL,
  `sensor_name` varchar(100) DEFAULT NULL,
  `sensor_size` varchar(50) DEFAULT NULL,
  `resolution_mp` decimal(5,2) DEFAULT NULL,
  `aperture` decimal(4,2) DEFAULT NULL,
  `normal_video_max` varchar(50) DEFAULT NULL,
  `normal_video_max_2` varchar(50) DEFAULT NULL,
  `has_log` tinyint(1) DEFAULT 0,
  `log_resolution` varchar(50) DEFAULT NULL,
  `log_fps` int(11) DEFAULT NULL,
  `log_resolution_2` varchar(50) DEFAULT NULL,
  `log_fps_2` int(11) DEFAULT NULL,
  `has_opengate` tinyint(1) DEFAULT 0,
  `opengate_resolution` varchar(50) DEFAULT NULL,
  `opengate_fps` int(11) DEFAULT NULL,
  `opengate_resolution_2` varchar(50) DEFAULT NULL,
  `opengate_fps_2` int(11) DEFAULT NULL,
  `has_opengate_log` tinyint(1) DEFAULT 0,
  `opengate_log_resolution` varchar(50) DEFAULT NULL,
  `opengate_log_fps` int(11) DEFAULT NULL,
  `opengate_log_resolution_2` varchar(50) DEFAULT NULL,
  `opengate_log_fps_2` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `camera_modules`
--

INSERT INTO `camera_modules` (`id`, `phone_characteristic_id`, `camera_type`, `sensor_name`, `sensor_size`, `resolution_mp`, `aperture`, `normal_video_max`, `normal_video_max_2`, `has_log`, `log_resolution`, `log_fps`, `log_resolution_2`, `log_fps_2`, `has_opengate`, `opengate_resolution`, `opengate_fps`, `opengate_resolution_2`, `opengate_fps_2`, `has_opengate_log`, `opengate_log_resolution`, `opengate_log_fps`, `opengate_log_resolution_2`, `opengate_log_fps_2`) VALUES
(5, 4, 'Principal', 'Samsung ISOCELL HPC', '1/1.3', 200.00, 1.50, '4K @ 120fps', '8K @ 30fps', 1, '4K', 120, NULL, NULL, 1, '4K', 60, NULL, NULL, 1, '4K', 60, NULL, NULL),
(6, 4, 'Ultra Gran Angular', 'Samsung ISOCELL HP5', '1/1.56', 200.00, 2.20, '4K @ 120fps', '8K @ 30fps', 1, '4K', 60, NULL, NULL, 1, '4K', 60, NULL, NULL, 1, '4K', 60, NULL, NULL),
(7, 4, 'Teleobjetivo Periscópico', 'Samsung ISOCELL HP5', '1/1.56', 200.00, 2.10, '4K @ 120fps', '8K @ 30fps', 1, '4K', 60, NULL, NULL, 1, '4K', 60, NULL, NULL, 1, '4K', 60, NULL, NULL),
(8, 4, 'Selfie', 'No especificado', NULL, NULL, NULL, '4K @ 30fps', '4K @ 60fps', 1, '4K', 30, NULL, NULL, 1, '4K', 30, NULL, NULL, 1, '4K', 30, NULL, NULL),
(9, 3, 'Principal', 'Sony LYT-700C', '1/1.56', 50.00, 1.90, '4K', NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL),
(10, 3, 'Ultra Gran Angular', 'Sony IMX 355', '1/2.8', 8.00, 2.90, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL),
(11, 3, 'Teleobjetivo Telescópico', 'Samsung S5KJN5', '1/2.76', 50.00, 2.90, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL),
(12, 3, 'Selfie', 'No especificado', NULL, 32.00, 2.20, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL),
(17, 5, 'Principal', 'Sony LYT-828', '1/1.28', 50.00, 1.57, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL),
(18, 5, 'Ultra Gran Angular', 'Samsung ISOCELL S5KJN1', '1/2.76', 50.00, 2.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL),
(19, 5, 'Teleobjetivo Periscópico', 'Samsung ISOCELL HPB', '1/1.4', 200.00, 2.60, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL),
(20, 5, 'Selfie', 'Samsung ISOCELL S5KJN1', '1/2.76', 50.00, 2.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `characteristics_battery`
--

CREATE TABLE `characteristics_battery` (
  `id` int(11) NOT NULL,
  `phone_characteristic_id` int(11) NOT NULL,
  `capacity_mah` int(11) DEFAULT NULL,
  `battery_type` varchar(50) DEFAULT NULL,
  `wired_charging_w` int(11) DEFAULT NULL,
  `wireless_charging_w` int(11) DEFAULT NULL,
  `reverse_wired_charging_w` int(11) DEFAULT NULL,
  `reverse_wireless_charging_w` int(11) DEFAULT NULL,
  `assured_cycles_80_health` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `characteristics_battery`
--

INSERT INTO `characteristics_battery` (`id`, `phone_characteristic_id`, `capacity_mah`, `battery_type`, `wired_charging_w`, `wireless_charging_w`, `reverse_wired_charging_w`, `reverse_wireless_charging_w`, `assured_cycles_80_health`) VALUES
(1, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(2, 2, 1, NULL, NULL, NULL, NULL, NULL, NULL),
(3, 3, 5080, NULL, 50, NULL, NULL, NULL, NULL),
(4, 4, 8000, NULL, 80, 10, 50, 10, 1200),
(5, 5, 5500, 'Si-C', NULL, NULL, NULL, NULL, NULL),
(6, 6, 6510, 'Si-C', NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `characteristics_connectivity`
--

CREATE TABLE `characteristics_connectivity` (
  `id` int(11) NOT NULL,
  `phone_characteristic_id` int(11) NOT NULL,
  `mobile_network` varchar(100) DEFAULT NULL,
  `wifi_version` varchar(50) DEFAULT NULL,
  `bluetooth_version` varchar(50) DEFAULT NULL,
  `nfc` tinyint(1) DEFAULT 0,
  `esim_support` tinyint(1) DEFAULT 0,
  `android_auto` tinyint(1) DEFAULT 0,
  `microsd` tinyint(1) NOT NULL DEFAULT 0,
  `sim_config` varchar(40) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `characteristics_connectivity`
--

INSERT INTO `characteristics_connectivity` (`id`, `phone_characteristic_id`, `mobile_network`, `wifi_version`, `bluetooth_version`, `nfc`, `esim_support`, `android_auto`, `microsd`, `sim_config`) VALUES
(1, 1, NULL, NULL, NULL, 0, 0, 0, 0, NULL),
(2, 2, NULL, NULL, NULL, 0, 0, 0, 0, NULL),
(3, 3, '5G', '6E', '5.4', 1, 1, 1, 0, NULL),
(4, 4, '5G', '7', '6.2', 1, 1, 1, 0, NULL),
(5, 5, '5G', NULL, NULL, 1, 1, 1, 0, 'Dual SIM'),
(6, 6, '5G', NULL, NULL, 1, 1, 1, 0, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `characteristics_screen`
--

CREATE TABLE `characteristics_screen` (
  `id` int(11) NOT NULL,
  `phone_characteristic_id` int(11) NOT NULL,
  `inches` decimal(4,2) DEFAULT NULL,
  `resolution` varchar(50) DEFAULT NULL,
  `refresh_rate_hz` int(11) DEFAULT NULL,
  `max_brightness_nits` int(11) DEFAULT NULL,
  `technology` varchar(100) DEFAULT NULL,
  `protection` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `characteristics_screen`
--

INSERT INTO `characteristics_screen` (`id`, `phone_characteristic_id`, `inches`, `resolution`, `refresh_rate_hz`, `max_brightness_nits`, `technology`, `protection`) VALUES
(1, 1, NULL, NULL, 120, NULL, NULL, NULL),
(2, 2, 6.82, '1440 x 3220', 120, NULL, NULL, NULL),
(3, 3, 6.72, '1260 x 2800', 144, NULL, 'AMOLED', NULL),
(4, 4, 6.78, '1272 x 2772', 144, NULL, 'LTPO AMOLED', NULL),
(5, 5, NULL, '1220 x 3440', 120, NULL, NULL, NULL),
(6, 6, NULL, '1220 x 3440', 120, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `characteristics_soc`
--

CREATE TABLE `characteristics_soc` (
  `id` int(11) NOT NULL,
  `phone_characteristic_id` int(11) NOT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `full_name` varchar(150) DEFAULT NULL,
  `architecture_nm` int(11) DEFAULT NULL,
  `antutu_score` int(11) DEFAULT NULL,
  `geekbench_single` int(11) DEFAULT NULL,
  `geekbench_multi` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `characteristics_soc`
--

INSERT INTO `characteristics_soc` (`id`, `phone_characteristic_id`, `brand`, `full_name`, `architecture_nm`, `antutu_score`, `geekbench_single`, `geekbench_multi`) VALUES
(1, 1, NULL, NULL, NULL, NULL, NULL, NULL),
(2, 2, NULL, NULL, NULL, NULL, NULL, NULL),
(3, 3, 'Qualcomm', 'Snapdragon 7 Gen 4', 4, 1015000, NULL, NULL),
(4, 4, 'Mediatek', 'Dimensity 9600 Pro', 2, 4600000, 3450, 10600),
(5, 5, 'Mediatek', 'Dimensity 9500', 3, NULL, NULL, NULL),
(6, 6, 'Mediatek', 'Dimensity 9500', 3, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `characteristics_storage_versions`
--

CREATE TABLE `characteristics_storage_versions` (
  `id` int(11) NOT NULL,
  `phone_characteristic_id` int(11) NOT NULL,
  `ram_technology` varchar(50) NOT NULL,
  `storage_technology` varchar(50) NOT NULL,
  `ram_gb` int(11) DEFAULT NULL,
  `storage_gb` int(11) DEFAULT NULL,
  `price_global` decimal(10,2) DEFAULT NULL,
  `price_ue` decimal(10,2) DEFAULT NULL,
  `price_china` decimal(10,2) DEFAULT NULL,
  `price_india` decimal(10,2) DEFAULT NULL,
  `price_japan` decimal(10,2) DEFAULT NULL,
  `price_korea` decimal(10,2) DEFAULT NULL,
  `price_usa` decimal(10,2) DEFAULT NULL,
  `price_latam` decimal(10,2) DEFAULT NULL,
  `price_taiwan` decimal(10,2) DEFAULT NULL,
  `price_russia` decimal(10,2) DEFAULT NULL,
  `price_carrier` decimal(10,2) DEFAULT NULL,
  `carrier_currency` varchar(10) DEFAULT NULL,
  `carrier_details` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `characteristics_storage_versions`
--

INSERT INTO `characteristics_storage_versions` (`id`, `phone_characteristic_id`, `ram_technology`, `storage_technology`, `ram_gb`, `storage_gb`, `price_global`, `price_ue`, `price_china`, `price_india`, `price_japan`, `price_korea`, `price_usa`, `price_latam`, `price_taiwan`, `price_russia`, `price_carrier`, `carrier_currency`, `carrier_details`) VALUES
(2, 4, 'LPDDR5X', 'UFS 4.0', 12, 512, NULL, NULL, 6799.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(3, 3, '', 'UFS 3.1', 8, 128, 349.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(4, 3, '', 'UFS 3.1', 12, 256, 389.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(6, 6, 'LPDDR5X', 'UFS 4.0', 16, 512, NULL, NULL, 6499.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(7, 5, 'LPDDR5X', 'UFS 4.0', 16, 512, 1249.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `operative_systems`
--

CREATE TABLE `operative_systems` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `operative_systems`
--

INSERT INTO `operative_systems` (`id`, `name`) VALUES
(2, 'Android 15'),
(1, 'Android 16'),
(3, 'OriginOS 15');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `phones`
--

CREATE TABLE `phones` (
  `id` int(11) NOT NULL,
  `series_id` int(11) NOT NULL,
  `operative_system_id` int(11) DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `launch_year` int(11) NOT NULL,
  `tier` varchar(50) NOT NULL,
  `variant_group_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `phones`
--

INSERT INTO `phones` (`id`, `series_id`, `operative_system_id`, `name`, `launch_year`, `tier`, `variant_group_id`) VALUES
(10, 10, 1, 'OPPO Find X9 Pro', 2025, 'Alta', NULL),
(11, 11, 1, 'Xiaomi 17 Ultra', 2026, 'Premium', NULL),
(12, 12, 1, 'Nothing Phone 4a Pro', 2026, 'Media', NULL),
(13, 10, 1, 'OPPO Find X10 Pro Max', 2026, 'Alta', NULL),
(14, 13, 2, 'VIVO X300 Pro', 2025, 'Alta', 1),
(15, 13, 3, 'VIVO X300 Pro 🇨🇳', 2025, 'Alta', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `phone_characteristics`
--

CREATE TABLE `phone_characteristics` (
  `id` int(11) NOT NULL,
  `phone_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `phone_characteristics`
--

INSERT INTO `phone_characteristics` (`id`, `phone_id`) VALUES
(1, 10),
(2, 11),
(3, 12),
(4, 13),
(5, 14),
(6, 15);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `phone_metadata`
--

CREATE TABLE `phone_metadata` (
  `id` int(11) NOT NULL,
  `phone_id` int(11) NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `score_avg` decimal(4,2) DEFAULT 0.00,
  `priority` varchar(20) NOT NULL DEFAULT 'ninguna',
  `owned_colors` varchar(255) DEFAULT NULL,
  `score_1` decimal(4,2) DEFAULT NULL,
  `score_2` decimal(4,2) DEFAULT NULL,
  `score_3` decimal(4,2) DEFAULT NULL,
  `score_4` decimal(4,2) DEFAULT NULL,
  `score_5` decimal(4,2) DEFAULT NULL,
  `score_6` decimal(4,2) DEFAULT NULL,
  `personal_comment` text DEFAULT NULL,
  `ip_rating` varchar(20) DEFAULT NULL,
  `frame_material` varchar(100) DEFAULT NULL,
  `back_material` varchar(100) DEFAULT NULL,
  `price_eur` decimal(10,2) DEFAULT NULL,
  `price_cny` decimal(10,2) DEFAULT NULL,
  `regional_prices` text DEFAULT NULL,
  `height_mm` decimal(5,2) DEFAULT NULL,
  `width_mm` decimal(5,2) DEFAULT NULL,
  `thickness_mm` decimal(5,2) DEFAULT NULL,
  `weight_g` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `phone_metadata`
--

INSERT INTO `phone_metadata` (`id`, `phone_id`, `image_url`, `score_avg`, `priority`, `owned_colors`, `score_1`, `score_2`, `score_3`, `score_4`, `score_5`, `score_6`, `personal_comment`, `ip_rating`, `frame_material`, `back_material`, `price_eur`, `price_cny`, `regional_prices`, `height_mm`, `width_mm`, `thickness_mm`, `weight_g`) VALUES
(1, 10, NULL, 8.58, 'ninguna', NULL, 9.00, 8.50, 8.00, 8.00, 9.00, 9.00, 'El gama alta mas equilibrado de su generación, no es el mejor en nada, pero siempre está entre los 5-10 mejores en todo, compitiendo y ganando a algunos \"Ultra\" en ciertos apartados.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(2, 11, NULL, 8.37, 'ninguna', NULL, 8.00, 8.50, 8.00, 9.20, 8.00, 8.50, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(3, 12, 'uploads/moviles/4da6c0938f3a15f81440cafc051bc931.jpg', 7.90, 'alta', NULL, 9.40, 7.50, 6.50, 6.50, 7.50, 10.00, 'Movil gama media muy equilibrado, con excelente y muy único Sistema Operativo y diseño peculiar de muy alta calidad.', 'IP65', 'Aluminio', 'Cristal', NULL, NULL, NULL, 163.70, 76.60, 8.00, 210),
(4, 13, 'https://fanaticosdelhardware.com/wp-content/uploads/2026/09/Oppo-X10-Pro-Max-colors-captura.jpg', 9.48, 'ninguna', NULL, 9.80, 9.70, 9.00, 9.20, 9.20, 10.00, 'Movil super equilibrado, muy buena calibración entre sus 3 cámaras, mejor capa de software de salida, y un diseño para mi gusto precioso y de alta calidad, y una batería monstruosa aunque inferior a otros.', 'IP69', 'Aluminio', 'Fibra de Vidrio', NULL, NULL, NULL, 161.30, 76.60, 8.40, 233),
(5, 14, NULL, 8.08, 'baja', NULL, 8.00, 8.50, 8.50, 8.00, 7.00, 8.50, 'Movil pensado casi exclusivamente en fotografía, un buen gama alta pero con muchos defectos frente a sus competidores, peor pantalla, altavoces, micrófonos, gestión térmica, y mala batería debido al gigantesco recorte de batería en comparación a la versión china, tambien tuvo problemas con algunos ejemplares con su calidad de construcción.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(6, 15, NULL, NULL, 'ninguna', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `series`
--

CREATE TABLE `series` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `brand_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `series`
--

INSERT INTO `series` (`id`, `name`, `brand_id`) VALUES
(10, 'Find X', 10),
(11, 'Number', 11),
(12, 'Phone a', 12),
(13, 'X', 13);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `software_support`
--

CREATE TABLE `software_support` (
  `id` int(11) NOT NULL,
  `phone_id` int(11) NOT NULL,
  `initial_os` varchar(100) DEFAULT NULL,
  `initial_skin` varchar(100) DEFAULT NULL,
  `os_and_skin_update_years` int(11) DEFAULT NULL,
  `security_update_years` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `software_support`
--

INSERT INTO `software_support` (`id`, `phone_id`, `initial_os`, `initial_skin`, `os_and_skin_update_years`, `security_update_years`) VALUES
(7, 10, NULL, NULL, 4, 5),
(8, 11, NULL, NULL, 4, 5),
(9, 12, NULL, 'NothingOS 4.1', 3, 6),
(10, 13, NULL, 'ColorOS 17', 5, 5),
(11, 14, NULL, 'FuntouchOS 15', 4, 5),
(12, 15, NULL, 'Android 15', 4, 5);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `is_admin` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `is_admin`, `created_at`) VALUES
(1, 'admin', '$2y$10$.7roCw6WaI8dh078cpYkVuoqhCSzQox3BefSwlSjdmVjBriLbfjuG', 1, '2026-10-24 11:02:41');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `brands`
--
ALTER TABLE `brands`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_brands_name` (`name`),
  ADD KEY `operative_system_id` (`operative_system_id`);

--
-- Indices de la tabla `camera_modules`
--
ALTER TABLE `camera_modules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `phone_characteristic_id` (`phone_characteristic_id`);

--
-- Indices de la tabla `characteristics_battery`
--
ALTER TABLE `characteristics_battery`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_battery_characteristic` (`phone_characteristic_id`);

--
-- Indices de la tabla `characteristics_connectivity`
--
ALTER TABLE `characteristics_connectivity`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_connectivity_characteristic` (`phone_characteristic_id`);

--
-- Indices de la tabla `characteristics_screen`
--
ALTER TABLE `characteristics_screen`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_screen_characteristic` (`phone_characteristic_id`);

--
-- Indices de la tabla `characteristics_soc`
--
ALTER TABLE `characteristics_soc`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_soc_characteristic` (`phone_characteristic_id`);

--
-- Indices de la tabla `characteristics_storage_versions`
--
ALTER TABLE `characteristics_storage_versions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `phone_characteristic_id` (`phone_characteristic_id`);

--
-- Indices de la tabla `operative_systems`
--
ALTER TABLE `operative_systems`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indices de la tabla `phones`
--
ALTER TABLE `phones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `series_id` (`series_id`),
  ADD KEY `idx_phones_variant_group` (`variant_group_id`),
  ADD KEY `fk_phones_os` (`operative_system_id`);

--
-- Indices de la tabla `phone_characteristics`
--
ALTER TABLE `phone_characteristics`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_characteristics_phone` (`phone_id`);

--
-- Indices de la tabla `phone_metadata`
--
ALTER TABLE `phone_metadata`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_metadata_phone` (`phone_id`);

--
-- Indices de la tabla `series`
--
ALTER TABLE `series`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_series_brand_name` (`brand_id`,`name`);

--
-- Indices de la tabla `software_support`
--
ALTER TABLE `software_support`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_software_phone` (`phone_id`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `brands`
--
ALTER TABLE `brands`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `camera_modules`
--
ALTER TABLE `camera_modules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de la tabla `characteristics_battery`
--
ALTER TABLE `characteristics_battery`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `characteristics_connectivity`
--
ALTER TABLE `characteristics_connectivity`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `characteristics_screen`
--
ALTER TABLE `characteristics_screen`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `characteristics_soc`
--
ALTER TABLE `characteristics_soc`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `characteristics_storage_versions`
--
ALTER TABLE `characteristics_storage_versions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `operative_systems`
--
ALTER TABLE `operative_systems`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `phones`
--
ALTER TABLE `phones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `phone_characteristics`
--
ALTER TABLE `phone_characteristics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `phone_metadata`
--
ALTER TABLE `phone_metadata`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `series`
--
ALTER TABLE `series`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `software_support`
--
ALTER TABLE `software_support`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `brands`
--
ALTER TABLE `brands`
  ADD CONSTRAINT `brands_ibfk_1` FOREIGN KEY (`operative_system_id`) REFERENCES `operative_systems` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `camera_modules`
--
ALTER TABLE `camera_modules`
  ADD CONSTRAINT `camera_modules_ibfk_1` FOREIGN KEY (`phone_characteristic_id`) REFERENCES `phone_characteristics` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `characteristics_battery`
--
ALTER TABLE `characteristics_battery`
  ADD CONSTRAINT `characteristics_battery_ibfk_1` FOREIGN KEY (`phone_characteristic_id`) REFERENCES `phone_characteristics` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `characteristics_connectivity`
--
ALTER TABLE `characteristics_connectivity`
  ADD CONSTRAINT `characteristics_connectivity_ibfk_1` FOREIGN KEY (`phone_characteristic_id`) REFERENCES `phone_characteristics` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `characteristics_screen`
--
ALTER TABLE `characteristics_screen`
  ADD CONSTRAINT `characteristics_screen_ibfk_1` FOREIGN KEY (`phone_characteristic_id`) REFERENCES `phone_characteristics` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `characteristics_soc`
--
ALTER TABLE `characteristics_soc`
  ADD CONSTRAINT `characteristics_soc_ibfk_1` FOREIGN KEY (`phone_characteristic_id`) REFERENCES `phone_characteristics` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `characteristics_storage_versions`
--
ALTER TABLE `characteristics_storage_versions`
  ADD CONSTRAINT `characteristics_storage_versions_ibfk_1` FOREIGN KEY (`phone_characteristic_id`) REFERENCES `phone_characteristics` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `phones`
--
ALTER TABLE `phones`
  ADD CONSTRAINT `fk_phones_os` FOREIGN KEY (`operative_system_id`) REFERENCES `operative_systems` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `phones_ibfk_1` FOREIGN KEY (`series_id`) REFERENCES `series` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `phone_characteristics`
--
ALTER TABLE `phone_characteristics`
  ADD CONSTRAINT `phone_characteristics_ibfk_1` FOREIGN KEY (`phone_id`) REFERENCES `phones` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `phone_metadata`
--
ALTER TABLE `phone_metadata`
  ADD CONSTRAINT `phone_metadata_ibfk_1` FOREIGN KEY (`phone_id`) REFERENCES `phones` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `series`
--
ALTER TABLE `series`
  ADD CONSTRAINT `series_ibfk_1` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `software_support`
--
ALTER TABLE `software_support`
  ADD CONSTRAINT `software_support_ibfk_1` FOREIGN KEY (`phone_id`) REFERENCES `phones` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
