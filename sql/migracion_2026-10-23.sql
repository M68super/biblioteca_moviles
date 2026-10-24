-- ========================================================
-- MIGRACIÓN 2026-10-23
--   1. Sistema operativo propio de cada móvil (antes era de la marca)
--   2. Variantes regionales (grupo de móviles asociados)
--   3. Prioridad de compra (solo visible para el administrador)
--   4. Memoria RAM / almacenamiento como números + precio por versión
--
-- NO borra datos. Ejecútalo UNA sola vez en phpMyAdmin (base phone_catalog),
-- DESPUÉS de migracion_notas.sql y migracion_bateria_video_auto.sql.
-- Haz antes una copia de seguridad (Exportar) de la base de datos.
-- ========================================================

-- 1 y 2. Sistema operativo por móvil + grupo de variantes regionales
ALTER TABLE phones
    ADD COLUMN operative_system_id INT NULL AFTER series_id,
    ADD COLUMN variant_group_id INT NULL AFTER tier,
    ADD INDEX idx_phones_variant_group (variant_group_id),
    ADD CONSTRAINT fk_phones_os FOREIGN KEY (operative_system_id) REFERENCES operative_systems(id) ON DELETE SET NULL;

-- Cada móvil hereda ahora el sistema operativo que tenía su marca (después se puede cambiar móvil a móvil)
UPDATE phones p
INNER JOIN series s ON s.id = p.series_id
INNER JOIN brands b ON b.id = s.brand_id
SET p.operative_system_id = b.operative_system_id
WHERE p.operative_system_id IS NULL;

-- 3. Prioridad de compra: ninguna, baja, media, alta u obtenido
ALTER TABLE phone_metadata
    ADD COLUMN priority VARCHAR(20) NOT NULL DEFAULT 'ninguna' AFTER score_avg;

-- 4. Memoria como números y precio de cada versión en cada región
ALTER TABLE characteristics_storage_versions
    ADD COLUMN ram_gb INT NULL,
    ADD COLUMN storage_gb INT NULL,
    ADD COLUMN price_global DECIMAL(10,2) NULL,
    ADD COLUMN price_ue DECIMAL(10,2) NULL,
    ADD COLUMN price_china DECIMAL(10,2) NULL,
    ADD COLUMN price_india DECIMAL(10,2) NULL,
    ADD COLUMN price_japan DECIMAL(10,2) NULL,
    ADD COLUMN price_korea DECIMAL(10,2) NULL,
    ADD COLUMN price_usa DECIMAL(10,2) NULL,
    ADD COLUMN price_latam DECIMAL(10,2) NULL,
    ADD COLUMN price_taiwan DECIMAL(10,2) NULL,
    ADD COLUMN price_russia DECIMAL(10,2) NULL;

-- Las versiones con un solo número ("12" y "256") se convierten solas aquí.
-- Las que tienen varios ("8, 12, 16") se convierten después abriendo migrar_memoria.php en el navegador.
UPDATE characteristics_storage_versions
SET ram_gb = CAST(ram_capacities_gb AS UNSIGNED)
WHERE ram_gb IS NULL AND ram_capacities_gb REGEXP '^[0-9]+$';

UPDATE characteristics_storage_versions
SET storage_gb = CAST(storage_capacities_gb AS UNSIGNED)
WHERE storage_gb IS NULL AND storage_capacities_gb REGEXP '^[0-9]+$';
