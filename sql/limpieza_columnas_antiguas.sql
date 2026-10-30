-- ==========================================================================
-- LIMPIEZA OPCIONAL de columnas que ya no usa ninguna página
-- (solo para una base de datos migrada; en la creada con estructura_2026-10-24.sql no existen)
--
-- NO la ejecutes hasta haber comprobado que:
--   1. migracion_2026-10-24.sql se ejecutó sin errores,
--   2. migrar_memoria.php ya convirtió todas las versiones de memoria a números,
--   3. las fichas de tus móviles muestran bien sus precios en la tarjeta "Precios por versión".
-- Estas sentencias BORRAN datos de forma definitiva: haz antes una copia de seguridad (Exportar).
-- ==========================================================================

-- Capacidades de memoria en texto ("8, 12, 16"): sustituidas por ram_gb y storage_gb
ALTER TABLE characteristics_storage_versions
    DROP COLUMN ram_capacities_gb,
    DROP COLUMN storage_capacities_gb;

-- Precios por región que antes se guardaban a nivel de móvil: ahora viven en cada versión de memoria
ALTER TABLE phone_metadata
    DROP COLUMN price_global,
    DROP COLUMN price_ue,
    DROP COLUMN price_china,
    DROP COLUMN price_india,
    DROP COLUMN price_japan,
    DROP COLUMN price_korea,
    DROP COLUMN price_usa,
    DROP COLUMN price_latam,
    DROP COLUMN price_taiwan,
    DROP COLUMN price_russia,
    DROP COLUMN price_carrier,
    DROP COLUMN carrier_currency,
    DROP COLUMN carrier_details;
