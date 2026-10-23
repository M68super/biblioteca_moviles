-- ========================================================
-- MIGRACIÓN 2026-10-22: 6 NOTAS PERSONALES + COMENTARIO
-- Añade a phone_metadata las columnas que usa el hexágono de detalles.php
-- (score_1 = Diseño, score_2 = Pantalla, score_3 = Rendimiento,
--  score_4 = Cámaras, score_5 = Batería, score_6 = Software)
-- y el comentario personal del panel admin.
--
-- NO borra datos. Ejecútalo UNA sola vez en phpMyAdmin (base phone_catalog).
-- Si ya existen las columnas dará el error "Duplicate column name": en ese caso no hace falta repetirlo.
-- ========================================================
ALTER TABLE phone_metadata
    ADD COLUMN score_1 DECIMAL(4,2) NULL AFTER score_avg,
    ADD COLUMN score_2 DECIMAL(4,2) NULL AFTER score_1,
    ADD COLUMN score_3 DECIMAL(4,2) NULL AFTER score_2,
    ADD COLUMN score_4 DECIMAL(4,2) NULL AFTER score_3,
    ADD COLUMN score_5 DECIMAL(4,2) NULL AFTER score_4,
    ADD COLUMN score_6 DECIMAL(4,2) NULL AFTER score_5,
    ADD COLUMN personal_comment TEXT NULL AFTER score_6;
