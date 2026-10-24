<?php
/**
 * ============================================================================
 * LÓGICA DEL BUSCADOR AVANZADO DEL CATÁLOGO (la usa index.php)
 * Lee los filtros de la URL ($_GET), consulta la base de datos y deja listos:
 *   $F (filtros), $moviles (resultados), $regiones, $error_precio y las listas $list_*
 * Requiere: $conexion, includes/funciones.php y includes/listas.php
 * ============================================================================
 */

$regiones = regiones_precio();

$claves_filtro = [
    'q', 'q_id',
    'f_so', 'f_marca', 'f_familia', 'f_anio',
    'f_skin', 'f_os_up', 'f_sec_up',
    'f_soc_brand', 'f_soc_name', 'f_soc_nm', 'f_antutu',
    'f_ram', 'f_ram_tech', 'f_storage', 'f_storage_tech',
    'f_screen_size', 'f_hz', 'f_screen_tech',
    'f_mah', 'f_wired', 'f_wireless',
    'f_cam_mp', 'f_cam_size',
    'f_log', 'f_opengate', 'f_opengate_log',
    'f_tier', 'f_ip', 'f_frame_material', 'f_back_material', 'f_peso_max',
    'f_network', 'f_wifi', 'f_bt', 'f_nfc', 'f_esim', 'f_android_auto',
];
foreach ($regiones as $clave => $info) {
    $claves_filtro[] = 'p_' . $clave . '_min';
    $claves_filtro[] = 'p_' . $clave . '_max';
}
$F = [];
foreach ($claves_filtro as $k) {
    $F[$k] = (isset($_GET[$k]) && is_scalar($_GET[$k])) ? trim((string)$_GET[$k]) : '';
}

$error_precio = '';
$moviles = [];

try {
    $sql = "
        SELECT DISTINCT p.id, p.name AS movil, p.launch_year, p.tier,
               b.name AS marca, s.name AS familia,
               meta.image_url, meta.score_avg, meta.price_global
        FROM phones p
        INNER JOIN series s ON s.id = p.series_id
        INNER JOIN brands b ON b.id = s.brand_id
        LEFT JOIN operative_systems os ON os.id = p.operative_system_id
        LEFT JOIN software_support sw ON sw.phone_id = p.id
        LEFT JOIN phone_metadata meta ON meta.phone_id = p.id
        LEFT JOIN phone_characteristics pc ON pc.phone_id = p.id
        LEFT JOIN characteristics_soc soc ON soc.phone_characteristic_id = pc.id
        LEFT JOIN characteristics_screen scr ON scr.phone_characteristic_id = pc.id
        LEFT JOIN characteristics_battery bat ON bat.phone_characteristic_id = pc.id
        LEFT JOIN characteristics_connectivity conn ON conn.phone_characteristic_id = pc.id
        WHERE 1=1
    ";
    $params = [];

    // Búsqueda por texto: cada palabra debe aparecer en marca, familia o nombre
    if ($F['q'] !== '') {
        $palabras = array_slice(preg_split('/\s+/', $F['q'], -1, PREG_SPLIT_NO_EMPTY), 0, 8);
        foreach ($palabras as $i => $palabra) {
            $sql .= " AND CONCAT_WS(' ', b.name, s.name, p.name) LIKE :q$i";
            $params["q$i"] = '%' . $palabra . '%';
        }
    }
    if ((int)$F['q_id'] > 0) { $sql .= " AND p.id = :q_id"; $params['q_id'] = (int)$F['q_id']; }

    // 1. Clasificación y marca
    if ($F['f_so'] !== '')      { $sql .= " AND os.name = :so";          $params['so'] = $F['f_so']; }
    if ($F['f_marca'] !== '')   { $sql .= " AND b.name = :marca";        $params['marca'] = $F['f_marca']; }
    if ($F['f_familia'] !== '') { $sql .= " AND s.name = :familia";      $params['familia'] = $F['f_familia']; }
    if ($F['f_anio'] !== '')    { $sql .= " AND p.launch_year = :anio";  $params['anio'] = (int)$F['f_anio']; }

    // 2. Software y soporte
    if ($F['f_skin'] !== '')   { $sql .= " AND sw.initial_skin = :skin";                   $params['skin'] = $F['f_skin']; }
    if ($F['f_os_up'] !== '')  { $sql .= " AND sw.os_and_skin_update_years >= :os_up";     $params['os_up'] = (int)$F['f_os_up']; }
    if ($F['f_sec_up'] !== '') { $sql .= " AND sw.security_update_years >= :sec_up";       $params['sec_up'] = (int)$F['f_sec_up']; }

    // 3. Procesador
    if ($F['f_soc_brand'] !== '') { $sql .= " AND soc.brand = :soc_brand";            $params['soc_brand'] = $F['f_soc_brand']; }
    if ($F['f_soc_name'] !== '')  { $sql .= " AND soc.full_name = :soc_name";         $params['soc_name'] = $F['f_soc_name']; }
    if ($F['f_soc_nm'] !== '')    { $sql .= " AND soc.architecture_nm = :soc_nm";     $params['soc_nm'] = (int)$F['f_soc_nm']; }
    if ($F['f_antutu'] !== '')    { $sql .= " AND soc.antutu_score >= :antutu";       $params['antutu'] = (int)$F['f_antutu']; }

    // 4. Memoria: una misma versión del móvil debe cumplir todas las condiciones marcadas
    $cond_memoria = [];
    if ($F['f_ram'] !== '')          { $cond_memoria[] = "sv.ram_gb >= :ram";                 $params['ram'] = (int)$F['f_ram']; }
    if ($F['f_storage'] !== '')      { $cond_memoria[] = "sv.storage_gb >= :storage";         $params['storage'] = (int)$F['f_storage']; }
    if ($F['f_ram_tech'] !== '')     { $cond_memoria[] = "sv.ram_technology = :ram_tech";     $params['ram_tech'] = $F['f_ram_tech']; }
    if ($F['f_storage_tech'] !== '') { $cond_memoria[] = "sv.storage_technology = :storage_tech"; $params['storage_tech'] = $F['f_storage_tech']; }
    if (!empty($cond_memoria)) {
        $sql .= " AND EXISTS (SELECT 1 FROM characteristics_storage_versions sv WHERE sv.phone_characteristic_id = pc.id AND " . implode(' AND ', $cond_memoria) . ")";
    }

    // 5. Pantalla
    if ($F['f_screen_size'] === 'mini')         { $sql .= " AND scr.inches < 6.0"; }
    elseif ($F['f_screen_size'] === 'pequeno')  { $sql .= " AND scr.inches >= 6.0 AND scr.inches <= 6.4"; }
    elseif ($F['f_screen_size'] === 'medio')    { $sql .= " AND scr.inches > 6.4 AND scr.inches <= 6.65"; }
    elseif ($F['f_screen_size'] === 'grande')   { $sql .= " AND scr.inches > 6.65"; }
    if ($F['f_hz'] !== '')          { $sql .= " AND scr.refresh_rate_hz = :hz";       $params['hz'] = (int)$F['f_hz']; }
    if ($F['f_screen_tech'] !== '') { $sql .= " AND scr.technology = :stech";         $params['stech'] = $F['f_screen_tech']; }

    // 6. Batería
    if ($F['f_mah'] !== '')   { $sql .= " AND bat.capacity_mah >= :mah";          $params['mah'] = (int)$F['f_mah']; }
    if ($F['f_wired'] !== '') { $sql .= " AND bat.wired_charging_w >= :wired";    $params['wired'] = (int)$F['f_wired']; }
    if ($F['f_wireless'] === '1') {
        $sql .= " AND bat.wireless_charging_w > 0";
    } elseif ($F['f_wireless'] === '0') {
        $sql .= " AND (bat.wireless_charging_w IS NULL OR bat.wireless_charging_w = 0)";
    }

    // 7. Cámara principal
    if ($F['f_cam_mp'] !== '') {
        $sql .= " AND EXISTS (SELECT 1 FROM camera_modules cm WHERE cm.phone_characteristic_id = pc.id AND cm.camera_type = 'Principal' AND cm.resolution_mp = :cam_mp)";
        $params['cam_mp'] = (float)$F['f_cam_mp'];
    }
    if ($F['f_cam_size'] !== '') {
        $sql .= " AND EXISTS (SELECT 1 FROM camera_modules cm WHERE cm.phone_characteristic_id = pc.id AND cm.camera_type = 'Principal' AND cm.sensor_size = :cam_size)";
        $params['cam_size'] = $F['f_cam_size'];
    }

    // 8. Vídeo profesional (cualquier cámara del móvil)
    if ($F['f_log'] !== '') {
        $sql .= " AND EXISTS (SELECT 1 FROM camera_modules cm WHERE cm.phone_characteristic_id = pc.id AND cm.has_log = 1)";
    }
    if ($F['f_opengate'] !== '') {
        $sql .= " AND EXISTS (SELECT 1 FROM camera_modules cm WHERE cm.phone_characteristic_id = pc.id AND cm.has_opengate = 1)";
    }
    if ($F['f_opengate_log'] !== '') {
        $sql .= " AND EXISTS (SELECT 1 FROM camera_modules cm WHERE cm.phone_characteristic_id = pc.id AND cm.has_opengate_log = 1)";
    }

    // 9. Gama y construcción
    if ($F['f_tier'] !== '')           { $sql .= " AND p.tier = :tier";                $params['tier'] = $F['f_tier']; }
    if ($F['f_ip'] !== '')             { $sql .= " AND meta.ip_rating = :ip";          $params['ip'] = $F['f_ip']; }
    if ($F['f_frame_material'] !== '') { $sql .= " AND meta.frame_material = :fmat";   $params['fmat'] = $F['f_frame_material']; }
    if ($F['f_back_material'] !== '')  { $sql .= " AND meta.back_material = :bmat";    $params['bmat'] = $F['f_back_material']; }
    if ($F['f_peso_max'] !== '')       { $sql .= " AND meta.weight_g <= :peso";        $params['peso'] = (int)$F['f_peso_max']; }

    // 10. Precios por región (las columnas salen de regiones_precio(), no del usuario)
    foreach ($regiones as $clave => $info) {
        $min = $F['p_' . $clave . '_min'];
        $max = $F['p_' . $clave . '_max'];
        if ($min !== '' && $max !== '' && is_numeric($min) && is_numeric($max) && (float)$min > (float)$max) {
            $error_precio = 'El precio mínimo en ' . $info['corto'] . ' no puede ser mayor que el máximo.';
            continue;
        }
        if ($min !== '' && is_numeric($min)) { $sql .= " AND meta.{$info['col']} >= :pmin_$clave"; $params["pmin_$clave"] = (float)$min; }
        if ($max !== '' && is_numeric($max)) { $sql .= " AND meta.{$info['col']} <= :pmax_$clave"; $params["pmax_$clave"] = (float)$max; }
    }

    // 11. Conectividad
    if ($F['f_network'] !== '') { $sql .= " AND conn.mobile_network = :net";        $params['net'] = $F['f_network']; }
    if ($F['f_wifi'] !== '')    { $sql .= " AND conn.wifi_version = :wifi";         $params['wifi'] = $F['f_wifi']; }
    if ($F['f_bt'] !== '')      { $sql .= " AND conn.bluetooth_version = :bt";      $params['bt'] = $F['f_bt']; }
    if ($F['f_nfc'] === '1' || $F['f_nfc'] === '0')   { $sql .= " AND conn.nfc = :nfc";           $params['nfc'] = (int)$F['f_nfc']; }
    if ($F['f_esim'] === '1' || $F['f_esim'] === '0') { $sql .= " AND conn.esim_support = :esim"; $params['esim'] = (int)$F['f_esim']; }
    if ($F['f_android_auto'] === '1' || $F['f_android_auto'] === '0') { $sql .= " AND conn.android_auto = :aauto"; $params['aauto'] = (int)$F['f_android_auto']; }

    $sql .= " ORDER BY marca ASC, movil ASC";
    $stmt = $conexion->prepare($sql);
    $stmt->execute($params);
    $moviles = $stmt->fetchAll();
} catch (PDOException $e) {
    die('Error al cargar datos: ' . escapar($e->getMessage()));
}

// Listas para los desplegables del buscador ($list_so, $list_marcas, $list_ram...)
extract(cargar_listas($conexion), EXTR_PREFIX_ALL, 'list');
