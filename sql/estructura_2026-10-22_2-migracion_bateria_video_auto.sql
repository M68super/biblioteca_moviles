-- ========================================================
-- MIGRACIÓN 2026-10-22 (B): CICLOS DE BATERÍA, ANDROID AUTO,
-- SEGUNDA OPCIÓN DE VÍDEO Y DETALLES DE OPERADORA LARGOS
--
-- NO borra datos. Ejecútalo UNA sola vez en phpMyAdmin (base phone_catalog).
-- Si alguna columna ya existe dará "Duplicate column name": en ese caso
-- ejecuta solo las sentencias que falten.
-- ========================================================

-- Ciclos de carga que asegura el fabricante hasta el 80 % de salud de la batería
ALTER TABLE characteristics_battery
    ADD COLUMN assured_cycles_80_health INT NULL AFTER reverse_wireless_charging_w;

-- Compatibilidad con Android Auto
ALTER TABLE characteristics_connectivity
    ADD COLUMN android_auto TINYINT(1) DEFAULT 0 AFTER esim_support;

-- Segunda opción de resolución y fps en cada tipo de vídeo (ej: 4K 120fps y 8K 30fps)
ALTER TABLE camera_modules
    ADD COLUMN normal_video_max_2 VARCHAR(50) NULL AFTER normal_video_max,
    ADD COLUMN log_resolution_2 VARCHAR(50) NULL AFTER log_fps,
    ADD COLUMN log_fps_2 INT NULL AFTER log_resolution_2,
    ADD COLUMN opengate_resolution_2 VARCHAR(50) NULL AFTER opengate_fps,
    ADD COLUMN opengate_fps_2 INT NULL AFTER opengate_resolution_2,
    ADD COLUMN opengate_log_resolution_2 VARCHAR(50) NULL AFTER opengate_log_fps,
    ADD COLUMN opengate_log_fps_2 INT NULL AFTER opengate_log_resolution_2;

-- Detalles de la operadora sin límite de 255 caracteres
ALTER TABLE phone_metadata
    MODIFY COLUMN carrier_details TEXT NULL;
