-- ========================================================
-- ÍNDICES ÚNICOS: evitan duplicados en la base de datos
--   - una marca no se puede repetir
--   - una familia no se puede repetir dentro de la misma marca
--   - cada móvil tiene UNA sola fila de software, metadatos y características
--   - cada característica tiene UNA sola fila de procesador, pantalla, batería y conectividad
--
-- PASO 1: ejecuta primero estas consultas de diagnóstico. Cada una debe devolver 0 filas.
-- Si alguna devuelve filas, hay duplicados: bórralos (o fusiónalos) a mano antes de seguir,
-- porque MySQL no permite crear el índice mientras existan.
-- ========================================================
SELECT 'brands' AS tabla, name AS duplicado, COUNT(*) AS veces FROM brands GROUP BY name HAVING COUNT(*) > 1;
SELECT 'series' AS tabla, CONCAT(brand_id, ' / ', name) AS duplicado, COUNT(*) AS veces FROM series GROUP BY brand_id, name HAVING COUNT(*) > 1;
SELECT 'software_support' AS tabla, phone_id AS duplicado, COUNT(*) AS veces FROM software_support GROUP BY phone_id HAVING COUNT(*) > 1;
SELECT 'phone_metadata' AS tabla, phone_id AS duplicado, COUNT(*) AS veces FROM phone_metadata GROUP BY phone_id HAVING COUNT(*) > 1;
SELECT 'phone_characteristics' AS tabla, phone_id AS duplicado, COUNT(*) AS veces FROM phone_characteristics GROUP BY phone_id HAVING COUNT(*) > 1;
SELECT 'characteristics_soc' AS tabla, phone_characteristic_id AS duplicado, COUNT(*) AS veces FROM characteristics_soc GROUP BY phone_characteristic_id HAVING COUNT(*) > 1;
SELECT 'characteristics_screen' AS tabla, phone_characteristic_id AS duplicado, COUNT(*) AS veces FROM characteristics_screen GROUP BY phone_characteristic_id HAVING COUNT(*) > 1;
SELECT 'characteristics_battery' AS tabla, phone_characteristic_id AS duplicado, COUNT(*) AS veces FROM characteristics_battery GROUP BY phone_characteristic_id HAVING COUNT(*) > 1;
SELECT 'characteristics_connectivity' AS tabla, phone_characteristic_id AS duplicado, COUNT(*) AS veces FROM characteristics_connectivity GROUP BY phone_characteristic_id HAVING COUNT(*) > 1;

-- ========================================================
-- PASO 2: crear los índices (solo cuando el paso 1 no devuelva nada)
-- ========================================================
ALTER TABLE brands ADD UNIQUE KEY uq_brands_name (name);
ALTER TABLE series ADD UNIQUE KEY uq_series_brand_name (brand_id, name);
ALTER TABLE software_support ADD UNIQUE KEY uq_software_phone (phone_id);
ALTER TABLE phone_metadata ADD UNIQUE KEY uq_metadata_phone (phone_id);
ALTER TABLE phone_characteristics ADD UNIQUE KEY uq_characteristics_phone (phone_id);
ALTER TABLE characteristics_soc ADD UNIQUE KEY uq_soc_characteristic (phone_characteristic_id);
ALTER TABLE characteristics_screen ADD UNIQUE KEY uq_screen_characteristic (phone_characteristic_id);
ALTER TABLE characteristics_battery ADD UNIQUE KEY uq_battery_characteristic (phone_characteristic_id);
ALTER TABLE characteristics_connectivity ADD UNIQUE KEY uq_connectivity_characteristic (phone_characteristic_id);
