<?php
/**
 * ============================================================================
 * LISTAS PARA DESPLEGABLES Y AUTOCOMPLETADO
 * La usan el buscador (index.php) y el panel admin (admin.php).
 * Uso:  extract(cargar_listas($conexion), EXTR_PREFIX_ALL, 'list');
 *       -> crea $list_so, $list_marcas, $list_ram, $list_sug_marco...
 * ============================================================================
 */
function cargar_listas(PDO $conexion) {
    try {
        $col = function ($sql) use ($conexion) {
            return $conexion->query($sql)->fetchAll(PDO::FETCH_COLUMN);
        };

        $listas = [
            'so'           => $col("SELECT DISTINCT name FROM operative_systems ORDER BY name"),
            'marcas'       => $col("SELECT DISTINCT name FROM brands ORDER BY name"),
            'familias'     => $col("SELECT DISTINCT name FROM series ORDER BY name"),
            'anios'        => $col("SELECT DISTINCT launch_year FROM phones WHERE launch_year IS NOT NULL ORDER BY launch_year DESC"),
            'tiers'        => $col("SELECT DISTINCT tier FROM phones WHERE tier IS NOT NULL AND tier != '' ORDER BY tier"),
            'skins'        => $col("SELECT DISTINCT initial_skin FROM software_support WHERE initial_skin IS NOT NULL AND initial_skin != '' ORDER BY initial_skin"),
            'soc_brands'   => $col("SELECT DISTINCT brand FROM characteristics_soc WHERE brand IS NOT NULL AND brand != '' ORDER BY brand"),
            'soc_names'    => $col("SELECT DISTINCT full_name FROM characteristics_soc WHERE full_name IS NOT NULL AND full_name != '' ORDER BY full_name"),
            'soc_nm'       => $col("SELECT DISTINCT architecture_nm FROM characteristics_soc WHERE architecture_nm IS NOT NULL ORDER BY architecture_nm"),
            'ram'          => $col("SELECT DISTINCT ram_gb FROM characteristics_storage_versions WHERE ram_gb IS NOT NULL ORDER BY ram_gb"),
            'storage'      => $col("SELECT DISTINCT storage_gb FROM characteristics_storage_versions WHERE storage_gb IS NOT NULL ORDER BY storage_gb"),
            'ram_tech'     => $col("SELECT DISTINCT ram_technology FROM characteristics_storage_versions WHERE ram_technology != '' ORDER BY ram_technology"),
            'storage_tech' => $col("SELECT DISTINCT storage_technology FROM characteristics_storage_versions WHERE storage_technology != '' ORDER BY storage_technology"),
            'hz'           => $col("SELECT DISTINCT refresh_rate_hz FROM characteristics_screen WHERE refresh_rate_hz IS NOT NULL ORDER BY refresh_rate_hz"),
            'screen_tech'  => $col("SELECT DISTINCT technology FROM characteristics_screen WHERE technology IS NOT NULL AND technology != '' ORDER BY technology"),
            'wired'        => $col("SELECT DISTINCT wired_charging_w FROM characteristics_battery WHERE wired_charging_w IS NOT NULL ORDER BY wired_charging_w"),
            'cam_mp'       => array_map('num_limpio', $col("SELECT DISTINCT resolution_mp FROM camera_modules WHERE camera_type = 'Principal' AND resolution_mp IS NOT NULL ORDER BY resolution_mp")),
            'cam_size'     => $col("SELECT DISTINCT sensor_size FROM camera_modules WHERE camera_type = 'Principal' AND sensor_size IS NOT NULL AND sensor_size != '' ORDER BY sensor_size"),
            'ips'          => $col("SELECT DISTINCT ip_rating FROM phone_metadata WHERE ip_rating IS NOT NULL AND ip_rating != '' ORDER BY ip_rating"),
            'frame_mat'    => $col("SELECT DISTINCT frame_material FROM phone_metadata WHERE frame_material IS NOT NULL AND frame_material != '' ORDER BY frame_material"),
            'back_mat'     => $col("SELECT DISTINCT back_material FROM phone_metadata WHERE back_material IS NOT NULL AND back_material != '' ORDER BY back_material"),
            'network'      => $col("SELECT DISTINCT mobile_network FROM characteristics_connectivity WHERE mobile_network IS NOT NULL AND mobile_network != '' ORDER BY mobile_network"),
            'wifi'         => $col("SELECT DISTINCT wifi_version FROM characteristics_connectivity WHERE wifi_version IS NOT NULL AND wifi_version != '' ORDER BY wifi_version"),
            'bt'           => $col("SELECT DISTINCT bluetooth_version FROM characteristics_connectivity WHERE bluetooth_version IS NOT NULL AND bluetooth_version != '' ORDER BY bluetooth_version"),
        ];

        // Sugerencias fijas de materiales (se mezclan con los que ya existen en la base de datos)
        $listas['sug_marco']   = array_values(array_unique(array_merge(['Plástico', 'Metal', 'Aluminio', 'Aleación de titanio'], $listas['frame_mat'])));
        $listas['sug_trasera'] = array_values(array_unique(array_merge(['Plástico', 'Vidrio', 'Cerámica', 'Cuero sintético / vegano'], $listas['back_mat'])));

        return $listas;
    } catch (PDOException $e) {
        die('Error al cargar las listas: ' . escapar($e->getMessage()));
    }
}
