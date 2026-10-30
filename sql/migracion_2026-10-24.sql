-- ==========================================================================
-- MIGRACIÓN 2026-10-24 (para la base de datos que YA tienes)
--
--   1. Precio "exclusivo operadora" en cada versión de memoria (precio, moneda y comentario)
--      y traslado de los precios que había en phone_metadata a la primera versión de cada móvil
--   2. Pantalla: brillo máximo (nits) y protección
--   3. Batería: tipo de batería
--   4. Conectividad: MicroSD y tarjeta SIM
--   5. Prioridad "Obtenido": colores que ya tengo comprados
--   6. Nombres oficiales de los tipos de cámara
--
-- NO borra datos. Ejecútalo UNA sola vez en phpMyAdmin (base phone_catalog), DESPUÉS de
-- migracion_notas.sql, migracion_bateria_video_auto.sql y migracion_2026-10-23.sql.
-- Haz antes una copia de seguridad (Exportar) de la base de datos.
--
-- Si prefieres empezar de cero con la base de datos completa, usa estructura_2026-10-24.sql
-- y NO ejecutes este archivo.
-- ==========================================================================

-- --------------------------------------------------------------------------
-- 1. Versiones de memoria: precio exclusivo de operadora
-- --------------------------------------------------------------------------
ALTER TABLE characteristics_storage_versions
    ADD COLUMN price_carrier DECIMAL(10,2) NULL,
    ADD COLUMN carrier_currency VARCHAR(10) NULL,
    ADD COLUMN carrier_details TEXT NULL;

-- Las columnas de texto antiguas de la memoria ya no se escriben: dejan de ser obligatorias
ALTER TABLE characteristics_storage_versions
    MODIFY COLUMN ram_capacities_gb VARCHAR(50) NULL,
    MODIFY COLUMN storage_capacities_gb VARCHAR(50) NULL;

-- Los precios que estaban en phone_metadata (que ya no se editan en el panel) pasan a la
-- primera versión de memoria de cada móvil, sin pisar los que esa versión ya tenga.
UPDATE characteristics_storage_versions sv
INNER JOIN (
    SELECT MIN(id) AS primera_version
    FROM characteristics_storage_versions
    GROUP BY phone_characteristic_id
) pv ON pv.primera_version = sv.id
INNER JOIN phone_characteristics pc ON pc.id = sv.phone_characteristic_id
INNER JOIN phone_metadata m ON m.phone_id = pc.phone_id
SET sv.price_global  = COALESCE(sv.price_global,  m.price_global),
    sv.price_ue      = COALESCE(sv.price_ue,      m.price_ue),
    sv.price_china   = COALESCE(sv.price_china,   m.price_china),
    sv.price_india   = COALESCE(sv.price_india,   m.price_india),
    sv.price_japan   = COALESCE(sv.price_japan,   m.price_japan),
    sv.price_korea   = COALESCE(sv.price_korea,   m.price_korea),
    sv.price_usa     = COALESCE(sv.price_usa,     m.price_usa),
    sv.price_latam   = COALESCE(sv.price_latam,   m.price_latam),
    sv.price_taiwan  = COALESCE(sv.price_taiwan,  m.price_taiwan),
    sv.price_russia  = COALESCE(sv.price_russia,  m.price_russia),
    sv.price_carrier = COALESCE(sv.price_carrier, m.price_carrier),
    sv.carrier_currency = COALESCE(sv.carrier_currency, CASE WHEN m.price_carrier IS NOT NULL THEN COALESCE(m.carrier_currency, 'EUR') END),
    sv.carrier_details  = COALESCE(sv.carrier_details, m.carrier_details);

-- --------------------------------------------------------------------------
-- 2. Pantalla: brillo máximo y protección
-- --------------------------------------------------------------------------
ALTER TABLE characteristics_screen
    ADD COLUMN max_brightness_nits INT NULL AFTER refresh_rate_hz,
    ADD COLUMN protection VARCHAR(150) NULL AFTER technology;

-- --------------------------------------------------------------------------
-- 3. Batería: tipo (Ion-Li, Si-C...)
-- --------------------------------------------------------------------------
ALTER TABLE characteristics_battery
    ADD COLUMN battery_type VARCHAR(50) NULL AFTER capacity_mah;

-- --------------------------------------------------------------------------
-- 4. Conectividad: MicroSD y tarjeta SIM
-- --------------------------------------------------------------------------
ALTER TABLE characteristics_connectivity
    ADD COLUMN microsd TINYINT(1) NOT NULL DEFAULT 0 AFTER android_auto,
    ADD COLUMN sim_config VARCHAR(40) NULL AFTER microsd;

-- --------------------------------------------------------------------------
-- 5. Prioridad "Obtenido": colores que ya tengo comprados
-- --------------------------------------------------------------------------
ALTER TABLE phone_metadata
    ADD COLUMN owned_colors VARCHAR(255) NULL AFTER priority;

-- --------------------------------------------------------------------------
-- 6. Tipos de cámara con el nombre oficial de la lista del panel admin
--    (la comparación de MySQL no distingue mayúsculas ni tildes, así que cubre "ultra gran angular", etc.)
-- --------------------------------------------------------------------------
UPDATE camera_modules SET camera_type = 'Principal'                 WHERE camera_type = 'principal';
UPDATE camera_modules SET camera_type = 'Ultra Gran Angular'        WHERE camera_type = 'ultra gran angular';
UPDATE camera_modules SET camera_type = 'Teleobjetivo Telescópico'  WHERE camera_type = 'teleobjetivo telescópico';
UPDATE camera_modules SET camera_type = 'Teleobjetivo Periscópico'  WHERE camera_type = 'teleobjetivo periscópico';
UPDATE camera_modules SET camera_type = 'Selfie Bajo Pantalla'      WHERE camera_type = 'selfie bajo pantalla';
UPDATE camera_modules SET camera_type = 'Selfie'                    WHERE camera_type = 'selfie';
UPDATE camera_modules SET camera_type = 'Macro'                     WHERE camera_type = 'macro';
UPDATE camera_modules SET camera_type = 'Infrarrojos'               WHERE camera_type IN ('infrarojos', 'infrarrojos');
UPDATE camera_modules SET camera_type = 'LiDAR'                     WHERE camera_type = 'lidar';
UPDATE camera_modules SET camera_type = 'ToF 3D'                    WHERE camera_type = 'tof 3d';
