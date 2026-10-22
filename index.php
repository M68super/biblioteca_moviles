<?php
/**
 * ============================================================================
 * INDEX.PHP · CATÁLOGO + PANEL ADMIN + BUSCADOR AVANZADO + LISTADO
 * Adaptado a la estructura de base de datos: estructura_2026-10-20.sql
 * ============================================================================
 *  FASE 1: Conexión y funciones auxiliares
 *  FASE 2: Sesión, permisos y modo del panel admin
 *  FASE 3: Procesar formularios del admin (crear / actualizar / borrar)
 *  FASE 4: Cargar la ficha del móvil a editar o borrar
 *  FASE 5: Filtros del buscador y consulta del catálogo
 *  FASE 6: Listas para desplegables y autocompletado
 * ============================================================================
 */
require_once __DIR__ . '/config/conexion.php';

/**
 * ============================================================================
 * FASE 1: FUNCIONES AUXILIARES
 * ============================================================================
 */

// Escape de seguridad para cualquier texto que se imprima en HTML
function escapar($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// Conversores de datos del formulario: devuelven null si el campo viene vacío
function texto_o_null($v) {
    if (is_array($v)) return null;
    $v = trim((string)($v ?? ''));
    return $v === '' ? null : $v;
}
function int_o_null($v) {
    if (is_array($v)) return null;
    $v = trim((string)($v ?? ''));
    return is_numeric($v) ? (int)$v : null;
}
function dec_o_null($v) {
    if (is_array($v)) return null;
    $v = trim((string)($v ?? ''));
    return is_numeric($v) ? (float)$v : null;
}

// Quita ceros sobrantes de un DECIMAL de MySQL: "50.00" -> "50", "1.80" -> "1.8"
function num_limpio($v) {
    if ($v === null || $v === '') return '';
    $s = (string)$v;
    if (strpos($s, '.') !== false) {
        $s = rtrim(rtrim($s, '0'), '.');
    }
    return $s;
}

// Separa "4K @ 60fps" en ['4K', '60'] para rellenar el formulario de cámaras
function partir_video($txt) {
    $txt = trim((string)$txt);
    if ($txt === '') return ['', ''];
    if (preg_match('/^(.*?)\s*@\s*(\d+)\s*(?:fps)?$/i', $txt, $m)) {
        return [trim($m[1]), $m[2]];
    }
    return [$txt, ''];
}

// Regiones de precio (columnas de phone_metadata). Se usa en el admin y en el buscador.
function regiones_precio() {
    return [
        'global' => ['etiqueta' => '🌍 Global (precio en España)', 'col' => 'price_global', 'moneda' => '€',   'corto' => 'España / Global (€)'],
        'ue'     => ['etiqueta' => '🇪🇺 UE (€)',                    'col' => 'price_ue',     'moneda' => '€',   'corto' => 'Unión Europea (€)'],
        'china'  => ['etiqueta' => '🇨🇳 China',                    'col' => 'price_china',  'moneda' => 'CNY', 'corto' => 'China (CNY)'],
        'india'  => ['etiqueta' => '🇮🇳 India',                    'col' => 'price_india',  'moneda' => 'INR', 'corto' => 'India (INR)'],
        'japan'  => ['etiqueta' => '🇯🇵 Japón',                    'col' => 'price_japan',  'moneda' => 'JPY', 'corto' => 'Japón (JPY)'],
        'korea'  => ['etiqueta' => '🇰🇷 Corea',                    'col' => 'price_korea',  'moneda' => 'KRW', 'corto' => 'Corea (KRW)'],
        'usa'    => ['etiqueta' => '🇺🇸 EE. UU.',                  'col' => 'price_usa',    'moneda' => 'USD', 'corto' => 'EE. UU. (USD)'],
        'latam'  => ['etiqueta' => '🇲🇽 Latam',                    'col' => 'price_latam',  'moneda' => 'MXN', 'corto' => 'Latam (MXN)'],
        'taiwan' => ['etiqueta' => '🇹🇼 Taiwán',                   'col' => 'price_taiwan', 'moneda' => 'TWD', 'corto' => 'Taiwán (TWD)'],
        'russia' => ['etiqueta' => '🇷🇺 Rusia',                    'col' => 'price_russia', 'moneda' => 'RUB', 'corto' => 'Rusia (RUB)'],
    ];
}

// Genera las <option> de un desplegable marcando la seleccionada
function opciones(array $lista, $seleccionado, $sufijo = '') {
    $html = '';
    foreach ($lista as $item) {
        $item = (string)$item;
        $sel = ((string)$seleccionado === $item) ? ' selected' : '';
        $html .= '<option value="' . escapar($item) . '"' . $sel . '>' . escapar($item . $sufijo) . '</option>';
    }
    return $html;
}

// Genera un <datalist> de autocompletado
function datalist($id, array $lista) {
    $html = '<datalist id="' . escapar($id) . '">';
    foreach ($lista as $item) {
        $html .= '<option value="' . escapar($item) . '">';
    }
    return $html . '</datalist>';
}

// Devuelve ' open' si alguno de los filtros de la categoría está activo (para dejar el acordeón abierto)
function abierto(array $F, array $claves) {
    foreach ($claves as $k) {
        if (($F[$k] ?? '') !== '') return ' open';
    }
    return '';
}

// De textos tipo "8, 12, 16" saca la lista única ordenada [8, 12, 16]
function valores_unicos_csv(array $filas) {
    $out = [];
    foreach ($filas as $cadena) {
        foreach (explode(',', (string)$cadena) as $parte) {
            $parte = trim($parte);
            if ($parte !== '') $out[$parte] = true;
        }
    }
    $claves = array_keys($out);
    usort($claves, function ($a, $b) { return (float)$a <=> (float)$b; });
    return $claves;
}

// Valor de un campo del formulario admin leído de la ficha cargada (editar / borrar)
function v($seccion, $campo, $defecto = '') {
    global $ficha, $modo_admin;
    if ($modo_admin === 'borrar') $defecto = '';
    $x = (isset($ficha[$seccion]) && is_array($ficha[$seccion])) ? ($ficha[$seccion][$campo] ?? null) : null;
    return ($x === null || $x === '') ? escapar($defecto) : escapar($x);
}

// Convierte una fila de camera_modules en el formato que usa el formulario (JS)
function camaraParaForm(array $c) {
    list($vres, $vfps) = partir_video($c['normal_video_max'] ?? '');
    return [
        'type'          => (string)($c['camera_type'] ?? ''),
        'sensor_name'   => (string)($c['sensor_name'] ?? ''),
        'sensor_size'   => (string)($c['sensor_size'] ?? ''),
        'resolution_mp' => num_limpio($c['resolution_mp'] ?? ''),
        'aperture'      => num_limpio($c['aperture'] ?? ''),
        'video_res'     => $vres,
        'video_fps'     => $vfps,
        'has_log'       => !empty($c['has_log']),
        'log_res'       => (string)($c['log_resolution'] ?? ''),
        'log_fps'       => (string)($c['log_fps'] ?? ''),
        'has_og'        => !empty($c['has_opengate']),
        'og_res'        => (string)($c['opengate_resolution'] ?? ''),
        'og_fps'        => (string)($c['opengate_fps'] ?? ''),
        'has_ogl'       => !empty($c['has_opengate_log']),
        'ogl_res'       => (string)($c['opengate_log_resolution'] ?? ''),
        'ogl_fps'       => (string)($c['opengate_log_fps'] ?? ''),
    ];
}

/**
 * Carga todos los datos de un móvil en un array con una clave por tabla:
 * phone, sw, meta, soc, scr, bat, conn, storage (varias), main_cam, aux_cams
 */
function cargarFicha(PDO $conexion, $phone_id) {
    $phone_id = (int)$phone_id;
    if ($phone_id <= 0) return [];

    $st = $conexion->prepare("
        SELECT p.*, b.name AS marca_nombre, s.name AS familia_nombre, os.name AS so_nombre
        FROM phones p
        INNER JOIN series s ON s.id = p.series_id
        INNER JOIN brands b ON b.id = s.brand_id
        LEFT JOIN operative_systems os ON os.id = b.operative_system_id
        WHERE p.id = ?
    ");
    $st->execute([$phone_id]);
    $phone = $st->fetch();
    if (!$phone) return [];

    $una = function ($sql, $id) use ($conexion) {
        $q = $conexion->prepare($sql);
        $q->execute([$id]);
        $fila = $q->fetch();
        return $fila ? $fila : [];
    };
    $varias = function ($sql, $id) use ($conexion) {
        $q = $conexion->prepare($sql);
        $q->execute([$id]);
        return $q->fetchAll();
    };

    $ficha = [
        'phone'    => $phone,
        'sw'       => $una("SELECT * FROM software_support WHERE phone_id = ? ORDER BY id LIMIT 1", $phone_id),
        'meta'     => $una("SELECT * FROM phone_metadata WHERE phone_id = ? ORDER BY id LIMIT 1", $phone_id),
        'soc'      => [],
        'scr'      => [],
        'bat'      => [],
        'conn'     => [],
        'storage'  => [],
        'main_cam' => null,
        'aux_cams' => [],
    ];

    $pc = $una("SELECT id FROM phone_characteristics WHERE phone_id = ? ORDER BY id LIMIT 1", $phone_id);
    if ($pc) {
        $cid = (int)$pc['id'];
        $ficha['soc']     = $una("SELECT * FROM characteristics_soc WHERE phone_characteristic_id = ? ORDER BY id LIMIT 1", $cid);
        $ficha['scr']     = $una("SELECT * FROM characteristics_screen WHERE phone_characteristic_id = ? ORDER BY id LIMIT 1", $cid);
        $ficha['bat']     = $una("SELECT * FROM characteristics_battery WHERE phone_characteristic_id = ? ORDER BY id LIMIT 1", $cid);
        $ficha['conn']    = $una("SELECT * FROM characteristics_connectivity WHERE phone_characteristic_id = ? ORDER BY id LIMIT 1", $cid);
        $ficha['storage'] = $varias("SELECT * FROM characteristics_storage_versions WHERE phone_characteristic_id = ? ORDER BY id", $cid);

        $camaras = $varias("SELECT * FROM camera_modules WHERE phone_characteristic_id = ? ORDER BY CASE WHEN camera_type = 'Principal' THEN 0 ELSE 1 END, id", $cid);
        foreach ($camaras as $c) {
            if ($c['camera_type'] === 'Principal' && $ficha['main_cam'] === null) {
                $ficha['main_cam'] = $c;
            } else {
                $ficha['aux_cams'][] = $c;
            }
        }
    }
    return $ficha;
}

/**
 * Inserta o actualiza la fila hija de una tabla (1 fila por móvil / por característica).
 * Los nombres de tabla y columnas salen siempre del propio código, nunca del usuario.
 */
function guardarFila(PDO $conexion, $tabla, $col_fk, $id_fk, array $datos) {
    $st = $conexion->prepare("SELECT id FROM `$tabla` WHERE `$col_fk` = ? ORDER BY id ASC LIMIT 1");
    $st->execute([$id_fk]);
    $fila = $st->fetch();

    if ($fila) {
        $sets = [];
        foreach (array_keys($datos) as $c) { $sets[] = "`$c` = ?"; }
        $conexion->prepare("UPDATE `$tabla` SET " . implode(', ', $sets) . " WHERE id = ?")
                 ->execute(array_merge(array_values($datos), [$fila['id']]));
    } else {
        $cols = array_merge([$col_fk], array_keys($datos));
        $marcas = implode(', ', array_fill(0, count($cols), '?'));
        $conexion->prepare("INSERT INTO `$tabla` (`" . implode('`, `', $cols) . "`) VALUES ($marcas)")
                 ->execute(array_merge([$id_fk], array_values($datos)));
    }
}

/**
 * Inserta un módulo de cámara en camera_modules (principal o auxiliar)
 */
function insertarCamara(PDO $conexion, $char_id, $tipo, array $d) {
    $sensor = texto_o_null($d['sensor_name'] ?? '') ?? 'No especificado';

    $video_res = trim((string)($d['video_res'] ?? ''));
    $video_fps = int_o_null($d['video_fps'] ?? '');
    $normal_video = ($video_res !== '')
        ? $video_res . ($video_fps !== null ? ' @ ' . $video_fps . 'fps' : '')
        : null;

    $has_log = !empty($d['has_log']) ? 1 : 0;
    $has_og  = !empty($d['has_og']) ? 1 : 0;
    $has_ogl = !empty($d['has_ogl']) ? 1 : 0;

    $conexion->prepare("INSERT INTO camera_modules (
            phone_characteristic_id, camera_type, sensor_name, sensor_size, resolution_mp, aperture,
            normal_video_max, has_log, log_resolution, log_fps,
            has_opengate, opengate_resolution, opengate_fps,
            has_opengate_log, opengate_log_resolution, opengate_log_fps
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")->execute([
        $char_id,
        $tipo,
        $sensor,
        texto_o_null($d['sensor_size'] ?? ''),
        dec_o_null($d['resolution_mp'] ?? ''),
        dec_o_null($d['aperture'] ?? ''),
        $normal_video,
        $has_log,
        $has_log ? texto_o_null($d['log_res'] ?? '') : null,
        $has_log ? int_o_null($d['log_fps'] ?? '') : null,
        $has_og,
        $has_og ? texto_o_null($d['og_res'] ?? '') : null,
        $has_og ? int_o_null($d['og_fps'] ?? '') : null,
        $has_ogl,
        $has_ogl ? texto_o_null($d['ogl_res'] ?? '') : null,
        $has_ogl ? int_o_null($d['ogl_fps'] ?? '') : null,
    ]);
}

/**
 * Gestiona el guardado de los módulos de cámaras (Principal + Auxiliares).
 * Borra las anteriores y vuelve a insertar las que llegan del formulario.
 */
function gestionarCamaras(PDO $conexion, $char_id, array $post_data) {
    $conexion->prepare("DELETE FROM camera_modules WHERE phone_characteristic_id = ?")->execute([$char_id]);

    // Cámara principal: formulario main[campo]
    $main = $post_data['main'] ?? null;
    if (is_array($main)) {
        $hay_datos = false;
        foreach (['sensor_name', 'sensor_size', 'resolution_mp'] as $k) {
            if (trim((string)($main[$k] ?? '')) !== '') { $hay_datos = true; break; }
        }
        if ($hay_datos) insertarCamara($conexion, $char_id, 'Principal', $main);
    }

    // Cámaras auxiliares: formulario aux[uid][campo]
    $aux = $post_data['aux'] ?? null;
    if (is_array($aux)) {
        foreach ($aux as $cam) {
            if (!is_array($cam)) continue;
            $tipo = trim((string)($cam['type'] ?? ''));
            if ($tipo === '') continue;
            insertarCamara($conexion, $char_id, $tipo, $cam);
        }
    }
}

/**
 * Crea (phone_id = 0) o actualiza un móvil completo. Devuelve el id del móvil.
 */
function guardarMovil(PDO $conexion, $phone_id, array $p) {
    $phone_id = (int)$phone_id;

    $marca_nombre   = trim((string)($p['marca_nombre'] ?? ''));
    $familia_nombre = trim((string)($p['familia_nombre'] ?? ''));
    $nombre         = trim((string)($p['nombre'] ?? ''));
    if ($marca_nombre === '' || $familia_nombre === '' || $nombre === '') {
        throw new Exception('La marca, la familia y el nombre del móvil son obligatorios.');
    }

    $launch_year = int_o_null($p['launch_year'] ?? '') ?? 2026;
    $tier = trim((string)($p['tier'] ?? ''));
    if ($tier === 'Por defecto') $tier = '';

    // --- Sistema operativo (se asocia a la marca, como en la base de datos) ---
    $so_id = null;
    $so_nombre = trim((string)($p['so_nombre'] ?? ''));
    if ($so_nombre !== '') {
        $st = $conexion->prepare("SELECT id FROM operative_systems WHERE name = ? LIMIT 1");
        $st->execute([$so_nombre]);
        $fila = $st->fetch();
        if ($fila) {
            $so_id = (int)$fila['id'];
        } else {
            $conexion->prepare("INSERT INTO operative_systems (name) VALUES (?)")->execute([$so_nombre]);
            $so_id = (int)$conexion->lastInsertId();
        }
    }

    // --- Marca ---
    $st = $conexion->prepare("SELECT id FROM brands WHERE name = ? LIMIT 1");
    $st->execute([$marca_nombre]);
    $fila = $st->fetch();
    if ($fila) {
        $marca_id = (int)$fila['id'];
        if ($so_id !== null) {
            $conexion->prepare("UPDATE brands SET operative_system_id = ? WHERE id = ?")->execute([$so_id, $marca_id]);
        }
    } else {
        $conexion->prepare("INSERT INTO brands (name, operative_system_id) VALUES (?, ?)")->execute([$marca_nombre, $so_id]);
        $marca_id = (int)$conexion->lastInsertId();
    }

    // --- Serie / familia ---
    $st = $conexion->prepare("SELECT id FROM series WHERE brand_id = ? AND name = ? LIMIT 1");
    $st->execute([$marca_id, $familia_nombre]);
    $fila = $st->fetch();
    if ($fila) {
        $serie_id = (int)$fila['id'];
    } else {
        $conexion->prepare("INSERT INTO series (name, brand_id) VALUES (?, ?)")->execute([$familia_nombre, $marca_id]);
        $serie_id = (int)$conexion->lastInsertId();
    }

    // --- Teléfono (tabla principal) ---
    if ($phone_id > 0) {
        $st = $conexion->prepare("SELECT id FROM phones WHERE id = ?");
        $st->execute([$phone_id]);
        if (!$st->fetch()) throw new Exception('El móvil que intentas actualizar no existe.');

        $conexion->prepare("UPDATE phones SET series_id = ?, name = ?, launch_year = ?, tier = ? WHERE id = ?")
                 ->execute([$serie_id, $nombre, $launch_year, $tier, $phone_id]);
    } else {
        $conexion->prepare("INSERT INTO phones (series_id, name, launch_year, tier) VALUES (?, ?, ?, ?)")
                 ->execute([$serie_id, $nombre, $launch_year, $tier]);
        $phone_id = (int)$conexion->lastInsertId();
    }

    // --- Soporte de software ---
    guardarFila($conexion, 'software_support', 'phone_id', $phone_id, [
        'initial_skin'             => texto_o_null($p['initial_skin'] ?? ''),
        'os_and_skin_update_years' => int_o_null($p['os_updates'] ?? ''),
        'security_update_years'    => int_o_null($p['sec_updates'] ?? ''),
    ]);

    // --- Metadatos: dimensiones, IP, materiales, nota, imagen y precios regionales ---
    $score_avg = dec_o_null($p['score_avg'] ?? '');
    if ($score_avg !== null) $score_avg = max(0, min(10, round($score_avg, 2)));

    $datos_meta = [
        'image_url'      => texto_o_null($p['image_url'] ?? ''),
        'score_avg'      => $score_avg,
        'ip_rating'      => texto_o_null($p['ip_rating'] ?? ''),
        'frame_material' => texto_o_null($p['frame_material'] ?? ''),
        'back_material'  => texto_o_null($p['back_material'] ?? ''),
        'height_mm'      => dec_o_null($p['height_mm'] ?? ''),
        'width_mm'       => dec_o_null($p['width_mm'] ?? ''),
        'thickness_mm'   => dec_o_null($p['thickness_mm'] ?? ''),
        'weight_g'       => int_o_null($p['weight_g'] ?? ''),
    ];
    foreach (regiones_precio() as $info) {
        $datos_meta[$info['col']] = dec_o_null($p[$info['col']] ?? '');
    }
    $datos_meta['price_carrier']    = dec_o_null($p['price_carrier'] ?? '');
    $datos_meta['carrier_currency'] = texto_o_null($p['carrier_currency'] ?? '') ?? 'EUR';
    $datos_meta['carrier_details']  = texto_o_null($p['carrier_details'] ?? '');
    guardarFila($conexion, 'phone_metadata', 'phone_id', $phone_id, $datos_meta);

    // --- Fila "padre" de características técnicas ---
    $st = $conexion->prepare("SELECT id FROM phone_characteristics WHERE phone_id = ? ORDER BY id ASC LIMIT 1");
    $st->execute([$phone_id]);
    $fila = $st->fetch();
    if ($fila) {
        $char_id = (int)$fila['id'];
    } else {
        $conexion->prepare("INSERT INTO phone_characteristics (phone_id) VALUES (?)")->execute([$phone_id]);
        $char_id = (int)$conexion->lastInsertId();
    }

    // --- Procesador (SoC) ---
    guardarFila($conexion, 'characteristics_soc', 'phone_characteristic_id', $char_id, [
        'brand'            => texto_o_null($p['soc_brand'] ?? ''),
        'full_name'        => texto_o_null($p['soc_name'] ?? ''),
        'architecture_nm'  => int_o_null($p['soc_nm'] ?? ''),
        'antutu_score'     => int_o_null($p['antutu'] ?? ''),
        'geekbench_single' => int_o_null($p['gb_single'] ?? ''),
        'geekbench_multi'  => int_o_null($p['gb_multi'] ?? ''),
    ]);

    // --- Versiones de memoria RAM / almacenamiento (varias por móvil) ---
    $conexion->prepare("DELETE FROM characteristics_storage_versions WHERE phone_characteristic_id = ?")->execute([$char_id]);
    if (!empty($p['storage_versions']) && is_array($p['storage_versions'])) {
        $ins = $conexion->prepare("INSERT INTO characteristics_storage_versions (phone_characteristic_id, ram_capacities_gb, ram_technology, storage_capacities_gb, storage_technology) VALUES (?, ?, ?, ?, ?)");
        foreach ($p['storage_versions'] as $sv) {
            if (!is_array($sv)) continue;
            $ram_cap      = trim((string)($sv['ram_cap'] ?? ''));
            $ram_tech     = trim((string)($sv['ram_tech'] ?? ''));
            $storage_cap  = trim((string)($sv['storage_cap'] ?? ''));
            $storage_tech = trim((string)($sv['storage_tech'] ?? ''));
            if ($ram_cap !== '' && $storage_cap !== '') {
                $ins->execute([$char_id, $ram_cap, $ram_tech, $storage_cap, $storage_tech]);
            }
        }
    }

    // --- Pantalla ---
    guardarFila($conexion, 'characteristics_screen', 'phone_characteristic_id', $char_id, [
        'inches'          => dec_o_null($p['inches'] ?? ''),
        'resolution'      => texto_o_null($p['resolution'] ?? ''),
        'refresh_rate_hz' => int_o_null($p['hz'] ?? ''),
        'technology'      => texto_o_null($p['screen_tech'] ?? ''),
    ]);

    // --- Batería y carga ---
    guardarFila($conexion, 'characteristics_battery', 'phone_characteristic_id', $char_id, [
        'capacity_mah'                => int_o_null($p['mah'] ?? ''),
        'wired_charging_w'            => int_o_null($p['wired_w'] ?? ''),
        'wireless_charging_w'         => int_o_null($p['wireless_w'] ?? ''),
        'reverse_wired_charging_w'    => int_o_null($p['rev_wired_w'] ?? ''),
        'reverse_wireless_charging_w' => int_o_null($p['rev_wireless_w'] ?? ''),
    ]);

    // --- Conectividad ---
    guardarFila($conexion, 'characteristics_connectivity', 'phone_characteristic_id', $char_id, [
        'mobile_network'    => texto_o_null($p['network'] ?? ''),
        'wifi_version'      => texto_o_null($p['wifi'] ?? ''),
        'bluetooth_version' => texto_o_null($p['bluetooth'] ?? ''),
        'nfc'               => isset($p['nfc']) ? 1 : 0,
        'esim_support'      => isset($p['esim']) ? 1 : 0,
    ]);

    // --- Cámaras ---
    gestionarCamaras($conexion, $char_id, $p);

    return $phone_id;
}

/**
 * Borra un móvil y todos sus datos asociados
 */
function borrarMovil(PDO $conexion, $phone_id) {
    $st = $conexion->prepare("SELECT id FROM phone_characteristics WHERE phone_id = ?");
    $st->execute([$phone_id]);
    $chars = $st->fetchAll(PDO::FETCH_COLUMN);

    $tablas_hijas = [
        'camera_modules',
        'characteristics_soc',
        'characteristics_storage_versions',
        'characteristics_screen',
        'characteristics_battery',
        'characteristics_connectivity',
    ];
    foreach ($chars as $cid) {
        foreach ($tablas_hijas as $tabla) {
            $conexion->prepare("DELETE FROM `$tabla` WHERE phone_characteristic_id = ?")->execute([$cid]);
        }
    }

    $conexion->prepare("DELETE FROM phone_characteristics WHERE phone_id = ?")->execute([$phone_id]);
    $conexion->prepare("DELETE FROM software_support WHERE phone_id = ?")->execute([$phone_id]);
    $conexion->prepare("DELETE FROM phone_metadata WHERE phone_id = ?")->execute([$phone_id]);

    $del = $conexion->prepare("DELETE FROM phones WHERE id = ?");
    $del->execute([$phone_id]);
    if ($del->rowCount() === 0) throw new Exception('El móvil que intentas borrar no existe.');
}

/**
 * ============================================================================
 * FASE 2: SESIÓN, PERMISOS Y MODO DEL PANEL ADMIN
 * ============================================================================
 */
$sesion_iniciada = isset($_SESSION['user_id']);
$nombre_usuario  = $sesion_iniciada ? (string)($_SESSION['username'] ?? '') : '';
$es_admin        = $sesion_iniciada && !empty($_SESSION['is_admin']);

$mensaje_exito = '';
$mensaje_error = '';

// Mensaje pendiente tras guardar/borrar (patrón "post-redirect-get": evita duplicados al recargar)
if (!empty($_SESSION['flash_exito'])) {
    $mensaje_exito = (string)$_SESSION['flash_exito'];
    unset($_SESSION['flash_exito']);
}

$modo_admin = $_GET['modo'] ?? 'crear';
if (!in_array($modo_admin, ['crear', 'editar', 'borrar'], true)) $modo_admin = 'crear';
$editar_id = isset($_GET['editar_id']) ? (int)$_GET['editar_id'] : 0;
$borrar_id = isset($_GET['borrar_id']) ? (int)$_GET['borrar_id'] : 0;

/**
 * ============================================================================
 * FASE 3: PROCESAR FORMULARIOS DEL ADMIN (CREAR, ACTUALIZAR, BORRAR)
 * ============================================================================
 */
if ($es_admin && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    $redirigir = null;
    try {
        $conexion->beginTransaction();
        $accion = (string)$_POST['accion'];

        if ($accion === 'borrar_movil_completo') {
            $phone_id = (int)($_POST['phone_id'] ?? 0);
            if ($phone_id <= 0) throw new Exception('ID de móvil inválido para borrar.');
            borrarMovil($conexion, $phone_id);
            $conexion->commit();
            $_SESSION['flash_exito'] = '¡El terminal ha sido borrado permanentemente!';
            $redirigir = 'index.php?modo=borrar';

        } elseif ($accion === 'crear_movil_completo') {
            guardarMovil($conexion, 0, $_POST);
            $conexion->commit();
            $_SESSION['flash_exito'] = "¡Móvil '" . trim((string)$_POST['nombre']) . "' registrado con éxito!";
            $redirigir = 'index.php?modo=crear';

        } elseif ($accion === 'actualizar_movil_completo') {
            $phone_id = (int)($_POST['phone_id'] ?? 0);
            if ($phone_id <= 0) throw new Exception('ID de móvil inválido para actualizar.');
            guardarMovil($conexion, $phone_id, $_POST);
            $conexion->commit();
            $_SESSION['flash_exito'] = "¡Móvil '" . trim((string)$_POST['nombre']) . "' actualizado correctamente!";
            $redirigir = 'index.php?modo=editar&editar_id=' . $phone_id;

        } else {
            throw new Exception('Acción no reconocida.');
        }
    } catch (Throwable $e) {
        if ($conexion->inTransaction()) {
            $conexion->rollBack();
        }
        $mensaje_error = 'Error en la operación: ' . $e->getMessage();
    }

    if ($redirigir !== null) {
        header('Location: ' . $redirigir);
        exit;
    }
}

/**
 * ============================================================================
 * FASE 4: CARGAR LA FICHA DEL MÓVIL A EDITAR O BORRAR
 * ============================================================================
 */
$ficha = [];
if ($es_admin) {
    try {
        if ($modo_admin === 'editar' && $editar_id > 0) {
            $ficha = cargarFicha($conexion, $editar_id);
            if (empty($ficha) && $mensaje_error === '') $mensaje_error = 'No se ha encontrado el móvil seleccionado para editar.';
        } elseif ($modo_admin === 'borrar' && $borrar_id > 0) {
            $ficha = cargarFicha($conexion, $borrar_id);
            if (empty($ficha) && $mensaje_error === '') $mensaje_error = 'No se ha encontrado el móvil seleccionado para borrar.';
        }
    } catch (Throwable $e) {
        $mensaje_error = 'Error al cargar los datos del móvil: ' . $e->getMessage();
    }
}
$form_visible = ($modo_admin === 'crear') || !empty($ficha);

// Datos que recibe el JavaScript del formulario (versiones de memoria y cámaras)
$storage_js = [];
foreach (($ficha['storage'] ?? []) as $r) {
    $storage_js[] = [
        'ram_cap'      => (string)$r['ram_capacities_gb'],
        'ram_tech'     => (string)$r['ram_technology'],
        'storage_cap'  => (string)$r['storage_capacities_gb'],
        'storage_tech' => (string)$r['storage_technology'],
    ];
}
$cam_principal_js = !empty($ficha['main_cam']) ? camaraParaForm($ficha['main_cam']) : null;
$cams_aux_js = [];
foreach (($ficha['aux_cams'] ?? []) as $c) {
    $cams_aux_js[] = camaraParaForm($c);
}
$tipos_camara_aux = ['selfie', 'selfie bajo pantalla', 'ultra gran angular', 'teleobjetivo periscópico', 'teleobjetivo telescópico', 'Macro', 'Lidar', 'ToF 3d', 'Infrarojos'];
$flags_json = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;

/**
 * ============================================================================
 * FASE 5: FILTROS DEL BUSCADOR Y CONSULTA DEL CATÁLOGO
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
    'f_network', 'f_wifi', 'f_bt', 'f_nfc', 'f_esim',
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
        LEFT JOIN operative_systems os ON os.id = b.operative_system_id
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

    // 4. Memoria (las capacidades se guardan como texto "8, 12, 16")
    if ($F['f_ram'] !== '') {
        $sql .= " AND EXISTS (SELECT 1 FROM characteristics_storage_versions sv WHERE sv.phone_characteristic_id = pc.id AND FIND_IN_SET(:ram, REPLACE(sv.ram_capacities_gb, ' ', '')) > 0)";
        $params['ram'] = str_replace(' ', '', $F['f_ram']);
    }
    if ($F['f_ram_tech'] !== '') {
        $sql .= " AND EXISTS (SELECT 1 FROM characteristics_storage_versions sv WHERE sv.phone_characteristic_id = pc.id AND sv.ram_technology = :ram_tech)";
        $params['ram_tech'] = $F['f_ram_tech'];
    }
    if ($F['f_storage'] !== '') {
        $sql .= " AND EXISTS (SELECT 1 FROM characteristics_storage_versions sv WHERE sv.phone_characteristic_id = pc.id AND FIND_IN_SET(:storage, REPLACE(sv.storage_capacities_gb, ' ', '')) > 0)";
        $params['storage'] = str_replace(' ', '', $F['f_storage']);
    }
    if ($F['f_storage_tech'] !== '') {
        $sql .= " AND EXISTS (SELECT 1 FROM characteristics_storage_versions sv WHERE sv.phone_characteristic_id = pc.id AND sv.storage_technology = :storage_tech)";
        $params['storage_tech'] = $F['f_storage_tech'];
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

    $sql .= " ORDER BY marca ASC, movil ASC";
    $stmt = $conexion->prepare($sql);
    $stmt->execute($params);
    $moviles = $stmt->fetchAll();

    /**
     * ========================================================================
     * FASE 6: LISTAS PARA DESPLEGABLES Y AUTOCOMPLETADO
     * ========================================================================
     */
    $col = function ($sql) use ($conexion) {
        return $conexion->query($sql)->fetchAll(PDO::FETCH_COLUMN);
    };

    $list_so          = $col("SELECT DISTINCT name FROM operative_systems ORDER BY name");
    $list_marcas      = $col("SELECT DISTINCT name FROM brands ORDER BY name");
    $list_familias    = $col("SELECT DISTINCT name FROM series ORDER BY name");
    $list_anios       = $col("SELECT DISTINCT launch_year FROM phones WHERE launch_year IS NOT NULL ORDER BY launch_year DESC");
    $list_tiers       = $col("SELECT DISTINCT tier FROM phones WHERE tier IS NOT NULL AND tier != '' ORDER BY tier");
    $list_skins       = $col("SELECT DISTINCT initial_skin FROM software_support WHERE initial_skin IS NOT NULL AND initial_skin != '' ORDER BY initial_skin");
    $list_soc_brands  = $col("SELECT DISTINCT brand FROM characteristics_soc WHERE brand IS NOT NULL AND brand != '' ORDER BY brand");
    $list_soc_names   = $col("SELECT DISTINCT full_name FROM characteristics_soc WHERE full_name IS NOT NULL AND full_name != '' ORDER BY full_name");
    $list_soc_nm      = $col("SELECT DISTINCT architecture_nm FROM characteristics_soc WHERE architecture_nm IS NOT NULL ORDER BY architecture_nm");
    $list_ram         = valores_unicos_csv($col("SELECT ram_capacities_gb FROM characteristics_storage_versions"));
    $list_storage     = valores_unicos_csv($col("SELECT storage_capacities_gb FROM characteristics_storage_versions"));
    $list_ram_tech    = $col("SELECT DISTINCT ram_technology FROM characteristics_storage_versions WHERE ram_technology != '' ORDER BY ram_technology");
    $list_storage_tech = $col("SELECT DISTINCT storage_technology FROM characteristics_storage_versions WHERE storage_technology != '' ORDER BY storage_technology");
    $list_hz          = $col("SELECT DISTINCT refresh_rate_hz FROM characteristics_screen WHERE refresh_rate_hz IS NOT NULL ORDER BY refresh_rate_hz");
    $list_screen_tech = $col("SELECT DISTINCT technology FROM characteristics_screen WHERE technology IS NOT NULL AND technology != '' ORDER BY technology");
    $list_wired       = $col("SELECT DISTINCT wired_charging_w FROM characteristics_battery WHERE wired_charging_w IS NOT NULL ORDER BY wired_charging_w");
    $list_cam_mp      = array_map('num_limpio', $col("SELECT DISTINCT resolution_mp FROM camera_modules WHERE camera_type = 'Principal' AND resolution_mp IS NOT NULL ORDER BY resolution_mp"));
    $list_cam_size    = $col("SELECT DISTINCT sensor_size FROM camera_modules WHERE camera_type = 'Principal' AND sensor_size IS NOT NULL AND sensor_size != '' ORDER BY sensor_size");
    $list_ips         = $col("SELECT DISTINCT ip_rating FROM phone_metadata WHERE ip_rating IS NOT NULL AND ip_rating != '' ORDER BY ip_rating");
    $list_frame_mat   = $col("SELECT DISTINCT frame_material FROM phone_metadata WHERE frame_material IS NOT NULL AND frame_material != '' ORDER BY frame_material");
    $list_back_mat    = $col("SELECT DISTINCT back_material FROM phone_metadata WHERE back_material IS NOT NULL AND back_material != '' ORDER BY back_material");
    $list_network     = $col("SELECT DISTINCT mobile_network FROM characteristics_connectivity WHERE mobile_network IS NOT NULL AND mobile_network != '' ORDER BY mobile_network");
    $list_wifi        = $col("SELECT DISTINCT wifi_version FROM characteristics_connectivity WHERE wifi_version IS NOT NULL AND wifi_version != '' ORDER BY wifi_version");
    $list_bt          = $col("SELECT DISTINCT bluetooth_version FROM characteristics_connectivity WHERE bluetooth_version IS NOT NULL AND bluetooth_version != '' ORDER BY bluetooth_version");

    // Móviles existentes para los selectores de editar / borrar
    $todos_para_editar = $conexion->query("SELECT p.id, p.name, b.name AS marca FROM phones p INNER JOIN series s ON s.id = p.series_id INNER JOIN brands b ON b.id = s.brand_id ORDER BY b.name, p.name")->fetchAll();
} catch (PDOException $e) {
    die('Error al cargar datos: ' . escapar($e->getMessage()));
}

// Sugerencias fijas de materiales (se mezclan con los que ya existen en la base de datos)
$sug_marco   = array_values(array_unique(array_merge(['Plástico', 'Metal', 'Aluminio', 'Aleación de titanio'], $list_frame_mat)));
$sug_trasera = array_values(array_unique(array_merge(['Plástico', 'Vidrio', 'Cerámica', 'Cuero sintético / vegano'], $list_back_mat)));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo de Smartphones · Estilo Kimovil</title>
    <!-- Se usa exclusivamente el archivo de estilos centralizado -->
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>

    <!-- Listas de autocompletado global (panel admin) -->
    <?= datalist('datalist_marcas', $list_marcas) ?>
    <?= datalist('datalist_familias', $list_familias) ?>
    <?= datalist('datalist_so', $list_so) ?>
    <?= datalist('datalist_skins', $list_skins) ?>
    <?= datalist('datalist_soc_brands', $list_soc_brands) ?>
    <?= datalist('datalist_soc_names', $list_soc_names) ?>
    <?= datalist('datalist_ram_tech', $list_ram_tech) ?>
    <?= datalist('datalist_storage_tech', $list_storage_tech) ?>
    <?= datalist('datalist_screen_tech', $list_screen_tech) ?>
    <?= datalist('datalist_network', $list_network) ?>
    <?= datalist('datalist_wifi', $list_wifi) ?>
    <?= datalist('datalist_bt', $list_bt) ?>
    <?= datalist('datalist_ips', $list_ips) ?>
    <?= datalist('datalist_marco', $sug_marco) ?>
    <?= datalist('datalist_trasera', $sug_trasera) ?>

    <header class="cabecera">
        <div class="contenedor">
            <div class="header-top-bar">
                <p class="sobrelinea">
                    CATÁLOGO TECNOLÓGICO ESTILO KIMOVIL
                    <span><a href="comparar.php">⚖️ Ir al Comparador &rarr;</a></span>
                </p>
                <div>
                    <?php if ($sesion_iniciada): ?>
                        <span class="usuario-logueado">Hola, <strong><?= escapar($nombre_usuario) ?></strong>
                            <?php if ($es_admin): ?><span class="badge-admin">ADMIN</span><?php endif; ?>
                        </span>
                        <a href="logout.php" class="btn-sesion">Cerrar sesión</a>
                    <?php else: ?>
                        <a href="login.php" class="btn-sesion">Iniciar sesión</a>
                        <a href="register.php" class="btn-sesion btn-registro">Registrarse</a>
                    <?php endif; ?>
                </div>
            </div>
            <h1>Biblioteca de smartphones</h1>
            <p class="subtitulo">Especificaciones técnicas avanzadas y comparativa inteligente</p>
        </div>
    </header>

    <main class="contenedor">

        <!-- ====================================================================
             PANEL DE ADMINISTRACIÓN (CREAR, EDITAR, BORRAR)
             ==================================================================== -->
        <?php if ($es_admin): ?>
            <section class="admin-box <?= $modo_admin === 'borrar' ? 'modo-borrado' : '' ?>">
                <h2>
                    <?= $modo_admin === 'borrar' ? '🗑️ Panel Admin: Modo Borrado de Registros' : '👑 Panel Admin: Registro Técnico Completo' ?>
                </h2>

                <!-- Botones de función del panel: CREAR / EDITAR / BORRAR (el activo se resalta) -->
                <div class="admin-modes-bar">
                    <a href="index.php?modo=crear" class="btn-admin-modo btn-crear<?= $modo_admin === 'crear' ? ' activo' : '' ?>">➕ Crear</a>
                    <a href="index.php?modo=editar" class="btn-admin-modo btn-editar<?= $modo_admin === 'editar' ? ' activo' : '' ?>">✏️ Editar</a>
                    <a href="index.php?modo=borrar" class="btn-admin-modo btn-borrar-modo<?= $modo_admin === 'borrar' ? ' activo' : '' ?>">🗑️ Borrar Móvil</a>
                </div>

                <!-- Selector para el modo edición -->
                <?php if ($modo_admin === 'editar'): ?>
                    <div class="admin-selector-box">
                        <form method="GET" action="index.php">
                            <input type="hidden" name="modo" value="editar">
                            <label>Selecciona un móvil existente para editar:</label>
                            <div class="admin-selector-row">
                                <select name="editar_id" required>
                                    <option value="">-- Elige un terminal --</option>
                                    <?php foreach ($todos_para_editar as $te): ?>
                                        <option value="<?= (int)$te['id'] ?>" <?= $editar_id == $te['id'] ? 'selected' : '' ?>>
                                            <?= escapar($te['marca'] . ' - ' . $te['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn-cargar">Cargar datos</button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>

                <!-- Selector para el modo borrado -->
                <?php if ($modo_admin === 'borrar'): ?>
                    <div class="admin-selector-box modo-borrado-box">
                        <form method="GET" action="index.php">
                            <input type="hidden" name="modo" value="borrar">
                            <label>Selecciona el móvil que deseas ELIMINAR:</label>
                            <div class="admin-selector-row">
                                <select name="borrar_id" required>
                                    <option value="">-- Elige terminal a borrar --</option>
                                    <?php foreach ($todos_para_editar as $te): ?>
                                        <option value="<?= (int)$te['id'] ?>" <?= $borrar_id == $te['id'] ? 'selected' : '' ?>>
                                            <?= escapar($te['marca'] . ' - ' . $te['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn-cargar-borrar">Cargar datos para verificar</button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>

                <?php if ($mensaje_exito): ?><div class="alerta-exito"><?= escapar($mensaje_exito) ?></div><?php endif; ?>
                <?php if ($mensaje_error): ?><div class="alerta-error"><?= escapar($mensaje_error) ?></div><?php endif; ?>

                <?php if ($modo_admin === 'editar' && empty($ficha)): ?>
                    <p class="aviso-editar-instruccion">Selecciona un móvil arriba y pulsa "Cargar datos" para editarlo.</p>
                <?php endif; ?>
                <?php if ($modo_admin === 'borrar' && empty($ficha)): ?>
                    <p class="aviso-borrar-instruccion">Selecciona un móvil arriba y pulsa "Cargar datos para verificar" para habilitar el borrado.</p>
                <?php endif; ?>

                <?php if ($form_visible): ?>
                <!-- Formulario maestro de administración -->
                <form method="POST" action="index.php?modo=<?= $modo_admin ?>&amp;editar_id=<?= $editar_id ?>&amp;borrar_id=<?= $borrar_id ?>">
                    <input type="hidden" name="accion" value="<?= $modo_admin === 'borrar' ? 'borrar_movil_completo' : (($modo_admin === 'editar') ? 'actualizar_movil_completo' : 'crear_movil_completo') ?>">
                    <?php if ($modo_admin !== 'crear' && !empty($ficha['phone']['id'])): ?>
                        <input type="hidden" name="phone_id" value="<?= (int)$ficha['phone']['id'] ?>">
                    <?php endif; ?>

                    <!-- En modo borrado todos los campos quedan bloqueados (solo para verificar) -->
                    <fieldset class="admin-fieldset"<?= $modo_admin === 'borrar' ? ' disabled' : '' ?>>

                        <!-- Sección 1: Identidad -->
                        <details class="categoria-filtro">
                            <summary>1. Identidad y Clasificación</summary>
                            <div class="categoria-contenido">
                                <div><label>Marca:</label><input type="text" name="marca_nombre" list="datalist_marcas" required value="<?= v('phone', 'marca_nombre') ?>"></div>
                                <div><label>Familia / Serie:</label><input type="text" name="familia_nombre" list="datalist_familias" required value="<?= v('phone', 'familia_nombre') ?>"></div>
                                <div><label>Nombre del Modelo:</label><input type="text" name="nombre" required value="<?= v('phone', 'name') ?>"></div>
                                <div><label>Año de Lanzamiento:</label><input type="number" name="launch_year" value="<?= v('phone', 'launch_year', 2026) ?>"></div>
                                <?php
                                $val_tier = ($modo_admin === 'crear') ? '' : (string)($ficha['phone']['tier'] ?? '');
                                $tiers_admin = ['Entrada', 'Baja', 'Media', 'Alta', 'Premium'];
                                if ($val_tier !== '' && !in_array($val_tier, $tiers_admin, true)) $tiers_admin[] = $val_tier;
                                ?>
                                <div>
                                    <label>Gama (Tier):</label>
                                    <select name="tier">
                                        <option value="Por defecto"<?= $val_tier === '' ? ' selected' : '' ?>>Por defecto</option>
                                        <?php foreach ($tiers_admin as $t): ?>
                                            <option value="<?= escapar($t) ?>"<?= $val_tier === $t ? ' selected' : '' ?>><?= escapar($t) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </details>

                        <!-- Sección 2: Software -->
                        <details class="categoria-filtro">
                            <summary>2. Software y Soporte</summary>
                            <div class="categoria-contenido">
                                <div><label>Sistema Operativo (se asocia a la marca):</label><input type="text" name="so_nombre" list="datalist_so" value="<?= v('phone', 'so_nombre') ?>"></div>
                                <div><label>Capa de Personalización:</label><input type="text" name="initial_skin" list="datalist_skins" value="<?= v('sw', 'initial_skin') ?>"></div>
                                <div><label>Años Act. Sistema / Capa:</label><input type="number" name="os_updates" value="<?= v('sw', 'os_and_skin_update_years', 4) ?>"></div>
                                <div><label>Años Parches de Seguridad:</label><input type="number" name="sec_updates" value="<?= v('sw', 'security_update_years', 5) ?>"></div>
                            </div>
                        </details>

                        <!-- Sección 3: Procesador -->
                        <details class="categoria-filtro">
                            <summary>3. Procesador (SoC) y Rendimiento</summary>
                            <div class="categoria-contenido">
                                <div><label>Marca del Procesador:</label><input type="text" name="soc_brand" list="datalist_soc_brands" value="<?= v('soc', 'brand') ?>"></div>
                                <div><label>Nombre Completo SoC:</label><input type="text" name="soc_name" list="datalist_soc_names" value="<?= v('soc', 'full_name') ?>"></div>
                                <div><label>Arquitectura (nm):</label><input type="number" name="soc_nm" value="<?= v('soc', 'architecture_nm') ?>"></div>
                                <div><label>Puntuación AnTuTu:</label><input type="number" name="antutu" value="<?= v('soc', 'antutu_score') ?>"></div>
                                <div><label>Geekbench Mononúcleo:</label><input type="number" name="gb_single" value="<?= v('soc', 'geekbench_single') ?>"></div>
                                <div><label>Geekbench Multinúcleo:</label><input type="number" name="gb_multi" value="<?= v('soc', 'geekbench_multi') ?>"></div>
                            </div>
                        </details>

                        <!-- Sección 4: Almacenamiento dinámico -->
                        <details class="categoria-filtro">
                            <summary>4. Almacenamiento y Memoria RAM</summary>
                            <div class="categoria-contenido block-layout storage-block-container">
                                <div class="storage-counter-row">
                                    <label for="num_storage_versions">Versiones de memoria (cantidad):</label>
                                    <input type="number" id="num_storage_versions" min="1" max="10" value="<?= !empty($storage_js) ? count($storage_js) : 1 ?>" onchange="generarVersionesMemoria()">
                                </div>
                                <div id="storage-versions-container"></div>
                            </div>
                        </details>

                        <!-- Sección 5: Pantalla -->
                        <details class="categoria-filtro">
                            <summary>5. Pantalla</summary>
                            <div class="categoria-contenido">
                                <div><label>Pulgadas:</label><input type="number" step="0.01" name="inches" value="<?= v('scr', 'inches') ?>"></div>
                                <div><label>Resolución:</label><input type="text" name="resolution" value="<?= v('scr', 'resolution') ?>"></div>
                                <div><label>Tasa de Refresco (Hz):</label><input type="number" name="hz" value="<?= v('scr', 'refresh_rate_hz', 120) ?>"></div>
                                <div><label>Tecnología de Pantalla:</label><input type="text" name="screen_tech" list="datalist_screen_tech" value="<?= v('scr', 'technology') ?>"></div>
                            </div>
                        </details>

                        <!-- Sección 6: Batería -->
                        <details class="categoria-filtro">
                            <summary>6. Batería y Tiempos de Carga</summary>
                            <div class="categoria-contenido">
                                <div><label>Capacidad (mAh):</label><input type="number" name="mah" value="<?= v('bat', 'capacity_mah') ?>"></div>
                                <div><label>Carga Cable (W):</label><input type="number" name="wired_w" value="<?= v('bat', 'wired_charging_w') ?>"></div>
                                <div><label>Carga Inalámbrica (W):</label><input type="number" name="wireless_w" value="<?= v('bat', 'wireless_charging_w') ?>"></div>
                                <div><label>Carga Inversa Cable (W):</label><input type="number" name="rev_wired_w" value="<?= v('bat', 'reverse_wired_charging_w') ?>"></div>
                                <div><label>Carga Inversa Inalámbrica (W):</label><input type="number" name="rev_wireless_w" value="<?= v('bat', 'reverse_wireless_charging_w') ?>"></div>
                            </div>
                        </details>

                        <!-- Sección 7: Cámaras (se pintan desde JavaScript con los datos guardados) -->
                        <details class="categoria-filtro">
                            <summary>7. Sistema de Cámaras (Principal + Auxiliares)</summary>
                            <div class="categoria-contenido block-layout">
                                <div class="cam-main-box" id="cam-principal"></div>
                                <div id="aux-cameras-container"></div>
                                <button type="button" class="btn-add-aux" onclick="agregarCamaraAuxiliar()">➕ Añadir Cámara Auxiliar</button>
                            </div>
                        </details>

                        <!-- Sección 8: Dimensiones -->
                        <details class="categoria-filtro">
                            <summary>8. Dimensiones y Construcción</summary>
                            <div class="categoria-contenido block-layout">
                                <div class="dim-grid">
                                    <div><label>Alto (mm):</label><input type="number" step="0.01" name="height_mm" value="<?= v('meta', 'height_mm') ?>"></div>
                                    <div><label>Ancho (mm):</label><input type="number" step="0.01" name="width_mm" value="<?= v('meta', 'width_mm') ?>"></div>
                                    <div><label>Grosor (mm):</label><input type="number" step="0.01" name="thickness_mm" value="<?= v('meta', 'thickness_mm') ?>"></div>
                                    <div><label>Peso (g):</label><input type="number" name="weight_g" value="<?= v('meta', 'weight_g') ?>"></div>
                                </div>
                                <div class="dim-subgrid">
                                    <div><label>Certificación IP:</label><input type="text" name="ip_rating" list="datalist_ips" value="<?= v('meta', 'ip_rating') ?>"></div>
                                    <div><label>Material del Marco:</label><input type="text" name="frame_material" list="datalist_marco" value="<?= v('meta', 'frame_material') ?>"></div>
                                    <div><label>Material Trasero:</label><input type="text" name="back_material" list="datalist_trasera" value="<?= v('meta', 'back_material') ?>"></div>
                                </div>
                            </div>
                        </details>

                        <!-- Sección 9: Precios Regionales -->
                        <details class="categoria-filtro">
                            <summary>9. Precios Regionales</summary>
                            <div class="categoria-contenido block-layout">
                                <div class="precios-container">
                                    <?php foreach ($regiones as $key => $info):
                                        $val = $ficha['meta'][$info['col']] ?? '';
                                        $activo = ($val !== '' && $val !== null && (float)$val > 0);
                                    ?>
                                    <div>
                                        <label class="checkbox-label">
                                            <input type="checkbox" name="reg_<?= $key ?>" value="1" onchange="togglePriceInput(this, 'box-<?= $key ?>')" <?= $activo ? 'checked' : '' ?>> <?= escapar($info['etiqueta']) ?>
                                        </label>
                                        <div id="box-<?= $key ?>" class="price-input-box<?= $activo ? '' : ' oculto' ?>">
                                            <input type="number" step="0.01" min="0" name="<?= $info['col'] ?>" value="<?= $activo ? escapar($val) : '' ?>" placeholder="Precio en <?= escapar($info['moneda']) ?>">
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <h4 class="subtitulo-admin">Precio con operadora</h4>
                                <div class="dim-subgrid">
                                    <div><label>Precio operadora:</label><input type="number" step="0.01" min="0" name="price_carrier" value="<?= v('meta', 'price_carrier') ?>"></div>
                                    <div><label>Moneda:</label><input type="text" name="carrier_currency" maxlength="10" value="<?= v('meta', 'carrier_currency', 'EUR') ?>"></div>
                                    <div><label>Operadora y detalles:</label><input type="text" name="carrier_details" maxlength="255" value="<?= v('meta', 'carrier_details') ?>"></div>
                                </div>
                            </div>
                        </details>

                        <!-- Sección 10: Conectividad -->
                        <details class="categoria-filtro">
                            <summary>10. Conectividad y Redes</summary>
                            <div class="categoria-contenido">
                                <div><label>Red Móvil:</label><input type="text" name="network" list="datalist_network" value="<?= v('conn', 'mobile_network') ?>"></div>
                                <div><label>Versión WiFi:</label><input type="text" name="wifi" list="datalist_wifi" value="<?= v('conn', 'wifi_version') ?>"></div>
                                <div><label>Bluetooth:</label><input type="text" name="bluetooth" list="datalist_bt" value="<?= v('conn', 'bluetooth_version') ?>"></div>
                                <div class="checkbox-wrapper">
                                    <label class="checkbox-label"><input type="checkbox" name="nfc" value="1" <?= !empty($ficha['conn']['nfc']) ? 'checked' : '' ?>> NFC</label>
                                </div>
                                <div class="checkbox-wrapper">
                                    <label class="checkbox-label"><input type="checkbox" name="esim" value="1" <?= !empty($ficha['conn']['esim_support']) ? 'checked' : '' ?>> eSIM</label>
                                </div>
                            </div>
                        </details>

                        <!-- Sección 11: Valoración e imagen -->
                        <details class="categoria-filtro">
                            <summary>11. Valoración y Foto</summary>
                            <div class="categoria-contenido">
                                <div>
                                    <label>Nota Media (0 - 10):</label>
                                    <input type="number" step="0.01" min="0" max="10" name="score_avg" value="<?= v('meta', 'score_avg') ?>" placeholder="Ej: 8.5">
                                </div>
                                <div class="full-width">
                                    <label>Enlace de la foto (URL):</label>
                                    <input type="text" name="image_url" value="<?= v('meta', 'image_url') ?>" placeholder="https://ejemplo.com/imagen.jpg">
                                </div>
                            </div>
                        </details>

                    </fieldset>

                    <!-- Botón de ejecución del formulario de administración -->
                    <?php if ($modo_admin === 'borrar'): ?>
                        <button type="submit" onclick="return confirm('¿Estás COMPLETAMENTE SEGURO de querer borrar este móvil? Esta acción no se puede deshacer.');" class="btn-submit-danger">
                            ⚠️ ELIMINAR ESTE MÓVIL PERMANENTEMENTE ⚠️
                        </button>
                    <?php else: ?>
                        <button type="submit" class="btn-submit-success <?= $modo_admin === 'editar' ? 'btn-submit-edit' : '' ?>">
                            <?= $modo_admin === 'editar' ? 'Actualizar Registro del Móvil' : 'Guardar Registro Completo' ?>
                        </button>
                    <?php endif; ?>
                </form>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <!-- ====================================================================
             BUSCADOR AVANZADO POR CATEGORÍAS
             ==================================================================== -->
        <section class="introduccion busqueda-avanzada-box">
            <h2>🔍 Buscador Avanzado por Categorías</h2>

            <?php if ($error_precio !== ''): ?>
                <div class="alerta-error"><?= escapar($error_precio) ?></div>
            <?php endif; ?>

            <form method="GET" action="index.php">
                <?php if ($es_admin): ?>
                    <!-- Mantiene el estado del panel admin al aplicar filtros -->
                    <input type="hidden" name="modo" value="<?= $modo_admin ?>">
                    <?php if ($editar_id > 0): ?><input type="hidden" name="editar_id" value="<?= $editar_id ?>"><?php endif; ?>
                    <?php if ($borrar_id > 0): ?><input type="hidden" name="borrar_id" value="<?= $borrar_id ?>"><?php endif; ?>
                <?php endif; ?>

                <div class="busqueda-nombre">
                    <label>Búsqueda por nombre (marca, familia o modelo):</label>
                    <input type="text" name="q" value="<?= escapar($F['q']) ?>" placeholder="Ej: Xiaomi 14 Ultra...">
                </div>

                <!-- 1. Clasificación -->
                <details class="categoria-filtro"<?= abierto($F, ['f_so', 'f_marca', 'f_familia', 'f_anio']) ?>>
                    <summary>📁 1. Clasificación y Marca</summary>
                    <div class="categoria-contenido">
                        <div>
                            <label>Sistema Operativo:</label>
                            <select name="f_so"><option value="">-- Todos --</option><?= opciones($list_so, $F['f_so']) ?></select>
                        </div>
                        <div>
                            <label>Marca:</label>
                            <select name="f_marca"><option value="">-- Todas --</option><?= opciones($list_marcas, $F['f_marca']) ?></select>
                        </div>
                        <div>
                            <label>Familia / Serie:</label>
                            <select name="f_familia"><option value="">-- Todas --</option><?= opciones($list_familias, $F['f_familia']) ?></select>
                        </div>
                        <div>
                            <label>Año de Lanzamiento:</label>
                            <select name="f_anio"><option value="">-- Todos --</option><?= opciones($list_anios, $F['f_anio']) ?></select>
                        </div>
                    </div>
                </details>

                <!-- 2. Software -->
                <details class="categoria-filtro"<?= abierto($F, ['f_skin', 'f_os_up', 'f_sec_up']) ?>>
                    <summary>🤖 2. Sistema Operativo y Soporte</summary>
                    <div class="categoria-contenido">
                        <div>
                            <label>Capa de Personalización:</label>
                            <select name="f_skin"><option value="">-- Todas --</option><?= opciones($list_skins, $F['f_skin']) ?></select>
                        </div>
                        <div>
                            <label>Años Act. Sistema (mínimo):</label>
                            <select name="f_os_up"><option value="">-- Cualquiera --</option><?= opciones([3, 4, 5, 6, 7], $F['f_os_up'], ' años o más') ?></select>
                        </div>
                        <div>
                            <label>Años Parches de Seguridad (mínimo):</label>
                            <select name="f_sec_up"><option value="">-- Cualquiera --</option><?= opciones([3, 4, 5, 6, 7], $F['f_sec_up'], ' años o más') ?></select>
                        </div>
                    </div>
                </details>

                <!-- 3. Procesador -->
                <details class="categoria-filtro"<?= abierto($F, ['f_soc_brand', 'f_soc_name', 'f_soc_nm', 'f_antutu']) ?>>
                    <summary>⚡ 3. Procesador (SoC)</summary>
                    <div class="categoria-contenido">
                        <div>
                            <label>Marca Procesador:</label>
                            <select name="f_soc_brand"><option value="">-- Todas --</option><?= opciones($list_soc_brands, $F['f_soc_brand']) ?></select>
                        </div>
                        <div>
                            <label>Nombre Completo:</label>
                            <select name="f_soc_name"><option value="">-- Todos --</option><?= opciones($list_soc_names, $F['f_soc_name']) ?></select>
                        </div>
                        <div>
                            <label>Nanómetros (nm):</label>
                            <select name="f_soc_nm"><option value="">-- Todos --</option><?= opciones($list_soc_nm, $F['f_soc_nm'], ' nm') ?></select>
                        </div>
                        <div>
                            <label>AnTuTu Mínimo:</label>
                            <input type="number" name="f_antutu" value="<?= escapar($F['f_antutu']) ?>" placeholder="Ej: 2000000">
                        </div>
                    </div>
                </details>

                <!-- 4. Memoria -->
                <details class="categoria-filtro"<?= abierto($F, ['f_ram', 'f_ram_tech', 'f_storage', 'f_storage_tech']) ?>>
                    <summary>💾 4. Memoria RAM y Almacenamiento</summary>
                    <div class="categoria-contenido">
                        <div>
                            <label>RAM disponible en alguna versión:</label>
                            <select name="f_ram"><option value="">-- Cualquiera --</option><?= opciones($list_ram, $F['f_ram'], ' GB') ?></select>
                        </div>
                        <div>
                            <label>Tecnología RAM:</label>
                            <select name="f_ram_tech"><option value="">-- Todas --</option><?= opciones($list_ram_tech, $F['f_ram_tech']) ?></select>
                        </div>
                        <div>
                            <label>Almacenamiento disponible en alguna versión:</label>
                            <select name="f_storage"><option value="">-- Cualquiera --</option><?= opciones($list_storage, $F['f_storage'], ' GB') ?></select>
                        </div>
                        <div>
                            <label>Tecnología Almacenamiento:</label>
                            <select name="f_storage_tech"><option value="">-- Todas --</option><?= opciones($list_storage_tech, $F['f_storage_tech']) ?></select>
                        </div>
                    </div>
                </details>

                <!-- 5. Pantalla -->
                <details class="categoria-filtro"<?= abierto($F, ['f_screen_size', 'f_hz', 'f_screen_tech']) ?>>
                    <summary>📱 5. Pantalla</summary>
                    <div class="categoria-contenido">
                        <div>
                            <label>Tamaño (Rangos):</label>
                            <?php $rangos_pantalla = ['mini' => 'Mini (< 6.0")', 'pequeno' => 'Pequeño (6.0" - 6.4")', 'medio' => 'Medio (6.41" - 6.65")', 'grande' => 'Grande (> 6.65")']; ?>
                            <select name="f_screen_size">
                                <option value="">-- Cualquiera --</option>
                                <?php foreach ($rangos_pantalla as $clave_rango => $etiqueta_rango): ?>
                                    <option value="<?= $clave_rango ?>"<?= $F['f_screen_size'] === $clave_rango ? ' selected' : '' ?>><?= escapar($etiqueta_rango) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label>Tasa de Refresco (Hz):</label>
                            <select name="f_hz"><option value="">-- Todos --</option><?= opciones($list_hz, $F['f_hz'], ' Hz') ?></select>
                        </div>
                        <div>
                            <label>Tecnología:</label>
                            <select name="f_screen_tech"><option value="">-- Todas --</option><?= opciones($list_screen_tech, $F['f_screen_tech']) ?></select>
                        </div>
                    </div>
                </details>

                <!-- 6. Batería -->
                <details class="categoria-filtro"<?= abierto($F, ['f_mah', 'f_wired', 'f_wireless']) ?>>
                    <summary>🔋 6. Batería</summary>
                    <div class="categoria-contenido">
                        <div>
                            <label>Miliamperios Mínimos (mAh):</label>
                            <input type="number" name="f_mah" value="<?= escapar($F['f_mah']) ?>" placeholder="Ej: 5000">
                        </div>
                        <div>
                            <label>Carga por Cable mínima (W):</label>
                            <select name="f_wired"><option value="">-- Cualquiera --</option><?= opciones($list_wired, $F['f_wired'], 'W') ?></select>
                        </div>
                        <div>
                            <label>Carga Inalámbrica:</label>
                            <select name="f_wireless">
                                <option value="">-- Indiferente --</option>
                                <option value="1"<?= $F['f_wireless'] === '1' ? ' selected' : '' ?>>Sí</option>
                                <option value="0"<?= $F['f_wireless'] === '0' ? ' selected' : '' ?>>No</option>
                            </select>
                        </div>
                    </div>
                </details>

                <!-- 7. Cámaras -->
                <details class="categoria-filtro"<?= abierto($F, ['f_cam_mp', 'f_cam_size']) ?>>
                    <summary>📸 7. Cámaras</summary>
                    <div class="categoria-contenido">
                        <div>
                            <label>Principal - Megapíxeles (MP):</label>
                            <select name="f_cam_mp"><option value="">-- Cualquiera --</option><?= opciones($list_cam_mp, $F['f_cam_mp'], ' MP') ?></select>
                        </div>
                        <div>
                            <label>Principal - Tamaño Sensor:</label>
                            <select name="f_cam_size"><option value="">-- Cualquiera --</option><?= opciones($list_cam_size, $F['f_cam_size']) ?></select>
                        </div>
                    </div>
                </details>

                <!-- 8. Vídeo -->
                <details class="categoria-filtro"<?= abierto($F, ['f_log', 'f_opengate', 'f_opengate_log']) ?>>
                    <summary>🎥 8. Vídeo (LOG / OpenGate)</summary>
                    <div class="categoria-contenido">
                        <div class="checkbox-wrapper">
                            <label class="checkbox-label"><input type="checkbox" name="f_log" value="1" <?= $F['f_log'] !== '' ? 'checked' : '' ?>> Compatible con Perfil LOG</label>
                        </div>
                        <div class="checkbox-wrapper">
                            <label class="checkbox-label"><input type="checkbox" name="f_opengate" value="1" <?= $F['f_opengate'] !== '' ? 'checked' : '' ?>> Compatible con OpenGate</label>
                        </div>
                        <div class="checkbox-wrapper">
                            <label class="checkbox-label"><input type="checkbox" name="f_opengate_log" value="1" <?= $F['f_opengate_log'] !== '' ? 'checked' : '' ?>> Compatible con OpenGate + LOG</label>
                        </div>
                    </div>
                </details>

                <!-- 9. Gama y construcción -->
                <details class="categoria-filtro"<?= abierto($F, ['f_tier', 'f_ip', 'f_frame_material', 'f_back_material', 'f_peso_max']) ?>>
                    <summary>🛡️ 9. Gama y Construcción</summary>
                    <div class="categoria-contenido">
                        <div>
                            <label>Gama:</label>
                            <select name="f_tier"><option value="">-- Todas --</option><?= opciones($list_tiers, $F['f_tier']) ?></select>
                        </div>
                        <div>
                            <label>Protección IP:</label>
                            <select name="f_ip"><option value="">-- Todas --</option><?= opciones($list_ips, $F['f_ip']) ?></select>
                        </div>
                        <div>
                            <label>Material Marco:</label>
                            <select name="f_frame_material"><option value="">-- Todos --</option><?= opciones($list_frame_mat, $F['f_frame_material']) ?></select>
                        </div>
                        <div>
                            <label>Material Trasera:</label>
                            <select name="f_back_material"><option value="">-- Todos --</option><?= opciones($list_back_mat, $F['f_back_material']) ?></select>
                        </div>
                        <div>
                            <label>Peso máximo (g):</label>
                            <input type="number" name="f_peso_max" value="<?= escapar($F['f_peso_max']) ?>" placeholder="Ej: 200">
                        </div>
                    </div>
                </details>

                <!-- 10. Precio por región -->
                <?php
                $claves_precio = [];
                foreach ($regiones as $clave => $info) { $claves_precio[] = 'p_' . $clave . '_min'; $claves_precio[] = 'p_' . $clave . '_max'; }
                ?>
                <details class="categoria-filtro"<?= abierto($F, $claves_precio) ?>>
                    <summary>💶 10. Precio por Región</summary>
                    <div class="categoria-contenido">
                        <?php foreach ($regiones as $clave => $info): ?>
                            <div>
                                <label><?= escapar($info['corto']) ?> (Mín - Máx):</label>
                                <div class="fila-rango">
                                    <input type="number" step="0.01" name="p_<?= $clave ?>_min" value="<?= escapar($F['p_' . $clave . '_min']) ?>" placeholder="Mín">
                                    <input type="number" step="0.01" name="p_<?= $clave ?>_max" value="<?= escapar($F['p_' . $clave . '_max']) ?>" placeholder="Máx">
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </details>

                <!-- 11. Conectividad -->
                <details class="categoria-filtro"<?= abierto($F, ['f_network', 'f_wifi', 'f_bt', 'f_nfc', 'f_esim']) ?>>
                    <summary>📡 11. Conectividad</summary>
                    <div class="categoria-contenido">
                        <div>
                            <label>Red Móvil:</label>
                            <select name="f_network"><option value="">-- Todas --</option><?= opciones($list_network, $F['f_network']) ?></select>
                        </div>
                        <div>
                            <label>Versión WiFi:</label>
                            <select name="f_wifi"><option value="">-- Todas --</option><?= opciones($list_wifi, $F['f_wifi']) ?></select>
                        </div>
                        <div>
                            <label>Bluetooth:</label>
                            <select name="f_bt"><option value="">-- Todas --</option><?= opciones($list_bt, $F['f_bt']) ?></select>
                        </div>
                        <div>
                            <label>NFC:</label>
                            <select name="f_nfc">
                                <option value="">-- Indiferente --</option>
                                <option value="1"<?= $F['f_nfc'] === '1' ? ' selected' : '' ?>>Sí</option>
                                <option value="0"<?= $F['f_nfc'] === '0' ? ' selected' : '' ?>>No</option>
                            </select>
                        </div>
                        <div>
                            <label>Soporte eSIM:</label>
                            <select name="f_esim">
                                <option value="">-- Indiferente --</option>
                                <option value="1"<?= $F['f_esim'] === '1' ? ' selected' : '' ?>>Sí</option>
                                <option value="0"<?= $F['f_esim'] === '0' ? ' selected' : '' ?>>No</option>
                            </select>
                        </div>
                    </div>
                </details>

                <div class="search-buttons-row">
                    <button type="submit" class="btn-aplicar-filtros">Aplicar Filtros</button>
                    <a href="index.php" class="btn-restablecer-filtros">Restablecer</a>
                </div>
            </form>
        </section>

        <!-- ====================================================================
             LISTADO DE MÓVILES DEL CATÁLOGO
             ==================================================================== -->
        <section class="catalogo">
            <div class="encabezado-catalogo">
                <h2>Modelos Registrados</h2>
                <span class="contador"><?= count($moviles) ?> <?= count($moviles) === 1 ? 'móvil encontrado' : 'móviles encontrados' ?></span>
            </div>

            <?php
            $hay_filtros = false;
            foreach ($F as $valor_filtro) { if ($valor_filtro !== '') { $hay_filtros = true; break; } }
            ?>

            <?php if (empty($moviles)): ?>
                <div class="estado-vacio">
                    <?php if ($hay_filtros): ?>
                        <h3>Sin resultados</h3>
                        <p>Ningún móvil cumple todos los filtros aplicados. Prueba a quitar alguno o pulsa "Restablecer".</p>
                    <?php else: ?>
                        <h3>Aún no hay móviles registrados</h3>
                        <p>Cuando el administrador añada terminales aparecerán aquí.</p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="grid-moviles">
                    <?php foreach ($moviles as $m): ?>
                        <a href="detalles.php?id=<?= (int)$m['id'] ?>" class="card-link-wrapper">
                            <article class="tarjeta">
                                <?php if (!empty($m['image_url'])): ?>
                                    <div class="card-imagen"><img src="<?= escapar($m['image_url']) ?>" alt="Foto de <?= escapar($m['movil']) ?>" loading="lazy"></div>
                                <?php endif; ?>
                                <p class="marca"><?= escapar($m['marca']) ?></p>
                                <h3><?= escapar($m['movil']) ?></h3>
                                <p class="familia">Familia: <?= escapar($m['familia']) ?></p>
                                <p class="anio">Lanzamiento: <?= escapar($m['launch_year']) ?>
                                    <?php if (!empty($m['tier'])): ?><span class="tier-badge"><?= escapar($m['tier']) ?></span><?php endif; ?>
                                </p>
                                <?php $tiene_nota = (float)($m['score_avg'] ?? 0) > 0; $tiene_precio = (float)($m['price_global'] ?? 0) > 0; ?>
                                <?php if ($tiene_nota || $tiene_precio): ?>
                                    <div class="card-pie">
                                        <span class="card-nota"><?= $tiene_nota ? '★ ' . number_format((float)$m['score_avg'], 1) : '' ?></span>
                                        <span class="card-precio"><?= $tiene_precio ? number_format((float)$m['price_global'], 2, ',', '.') . ' €' : '' ?></span>
                                    </div>
                                <?php endif; ?>
                            </article>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

    </main>

    <footer class="pie">
        <div class="contenedor">
            <p>Catálogo de smartphones · Proyecto personal estilo Kimovil</p>
        </div>
    </footer>

    <?php if ($es_admin && $form_visible): ?>
    <!-- ============================================================================
         SCRIPTS DEL PANEL ADMIN (versiones de memoria, cámaras y precios)
         ============================================================================ -->
    <script>
        // Escapa texto para meterlo de forma segura dentro de atributos HTML generados por JS
        function esc(valor) {
            return String(valor === null || valor === undefined ? '' : valor)
                .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
        }

        /**
         * VERSIONES DE MEMORIA (RAM + almacenamiento): genera una fila por versión
         * y conserva lo que ya se había escrito al cambiar la cantidad.
         */
        const versionesGuardadas = <?= json_encode($storage_js, $flags_json) ?>;

        function generarVersionesMemoria() {
            const input = document.getElementById('num_storage_versions');
            const cont = document.getElementById('storage-versions-container');
            if (!input || !cont) return;

            let cantidad = parseInt(input.value, 10);
            if (isNaN(cantidad) || cantidad < 1) cantidad = 1;
            if (cantidad > 10) cantidad = 10;
            input.value = cantidad;

            const actuales = [];
            cont.querySelectorAll('.storage-version-row').forEach(function (fila) {
                const leer = function (campo) {
                    const el = fila.querySelector('[data-campo="' + campo + '"]');
                    return el ? el.value : '';
                };
                actuales.push({ ram_cap: leer('ram_cap'), ram_tech: leer('ram_tech'), storage_cap: leer('storage_cap'), storage_tech: leer('storage_tech') });
            });

            cont.innerHTML = '';
            for (let i = 0; i < cantidad; i++) {
                const sv = actuales[i] || versionesGuardadas[i] || {};
                cont.insertAdjacentHTML('beforeend', `
                    <div class="storage-version-row">
                        <div><label>${i + 1}. Cantidad RAM (GB):</label><input type="text" data-campo="ram_cap" name="storage_versions[${i}][ram_cap]" value="${esc(sv.ram_cap)}" placeholder="Ej: 8, 12, 16"></div>
                        <div><label>${i + 1}. Tecnología RAM:</label><input type="text" list="datalist_ram_tech" data-campo="ram_tech" name="storage_versions[${i}][ram_tech]" value="${esc(sv.ram_tech)}" placeholder="Ej: LPDDR5X"></div>
                        <div><label>${i + 1}. Cantidad Almacenamiento:</label><input type="text" data-campo="storage_cap" name="storage_versions[${i}][storage_cap]" value="${esc(sv.storage_cap)}" placeholder="Ej: 256, 512"></div>
                        <div><label>${i + 1}. Tecnología Almacenamiento:</label><input type="text" list="datalist_storage_tech" data-campo="storage_tech" name="storage_versions[${i}][storage_tech]" value="${esc(sv.storage_tech)}" placeholder="Ej: UFS 4.0"></div>
                    </div>
                `);
            }
        }

        /**
         * CÁMARAS: la principal y las auxiliares se pintan con la misma plantilla.
         * Los nombres son main[campo] y aux[uid][campo], así cada cámara va agrupada al enviar.
         */
        const TIPOS_CAMARA_AUX = <?= json_encode($tipos_camara_aux, $flags_json) ?>;
        const camaraPrincipal = <?= json_encode($cam_principal_js, $flags_json) ?>;
        const camarasAuxiliares = <?= json_encode($cams_aux_js, $flags_json) ?>;
        let contadorAux = 0;

        function filaVideo(n, campoHas, campoRes, campoFps, titulo, activo, res, fps) {
            return `
                <div class="cam-video-fila">
                    <label class="checkbox-label"><input type="checkbox" name="${n(campoHas)}" value="1" ${activo ? 'checked' : ''}> ${titulo}</label>
                    <input type="text" name="${n(campoRes)}" value="${esc(res)}" placeholder="Resolución (ej: 4K)">
                    <input type="number" min="0" name="${n(campoFps)}" value="${esc(fps)}" placeholder="fps">
                </div>`;
        }

        function camposCamara(prefijo, c, conTipo) {
            c = c || {};
            const n = function (campo) { return prefijo + '[' + campo + ']'; };

            let selectorTipo = '';
            if (conTipo) {
                const tipos = TIPOS_CAMARA_AUX.slice();
                if (c.type && tipos.indexOf(c.type) === -1) tipos.push(c.type);
                const opciones = tipos.map(function (t) {
                    return '<option value="' + esc(t) + '"' + (t === c.type ? ' selected' : '') + '>' + esc(t.charAt(0).toUpperCase() + t.slice(1)) + '</option>';
                }).join('');
                selectorTipo = `
                    <div>
                        <label>Tipo de Cámara:</label>
                        <select name="${n('type')}" required>
                            ${c.type ? '' : '<option value="">-- Seleccionar Tipo --</option>'}
                            ${opciones}
                        </select>
                    </div>`;
            }

            return `
                <div class="cam-grid">
                    ${selectorTipo}
                    <div><label>Sensor:</label><input type="text" name="${n('sensor_name')}" value="${esc(c.sensor_name)}"></div>
                    <div><label>Tamaño Sensor:</label><input type="text" name="${n('sensor_size')}" value="${esc(c.sensor_size)}"></div>
                    <div><label>Megapíxeles (MP):</label><input type="number" step="0.01" name="${n('resolution_mp')}" value="${esc(c.resolution_mp)}"></div>
                    <div><label>Apertura Focal:</label><input type="number" step="0.01" name="${n('aperture')}" value="${esc(c.aperture)}" placeholder="Ej: 1.8"></div>
                </div>
                <div class="cam-video-bloque">
                    <div class="cam-video-fila">
                        <span class="cam-video-titulo">Vídeo normal máx.</span>
                        <input type="text" name="${n('video_res')}" value="${esc(c.video_res)}" placeholder="Resolución (ej: 4K)">
                        <input type="number" min="0" name="${n('video_fps')}" value="${esc(c.video_fps)}" placeholder="fps">
                    </div>
                    ${filaVideo(n, 'has_log', 'log_res', 'log_fps', 'LOG', c.has_log, c.log_res, c.log_fps)}
                    ${filaVideo(n, 'has_og', 'og_res', 'og_fps', 'OpenGate', c.has_og, c.og_res, c.og_fps)}
                    ${filaVideo(n, 'has_ogl', 'ogl_res', 'ogl_fps', 'OpenGate + LOG', c.has_ogl, c.ogl_res, c.ogl_fps)}
                </div>`;
        }

        function agregarCamaraAuxiliar(c) {
            const cont = document.getElementById('aux-cameras-container');
            if (!cont) return;
            const caja = document.createElement('div');
            caja.className = 'aux-camara-box';
            const prefijo = 'aux[a' + (contadorAux++) + ']';
            caja.innerHTML = `
                <button type="button" class="btn-eliminar-aux" onclick="this.parentElement.remove()">❌ Eliminar</button>
                <h4 class="aux-title">Cámara Auxiliar</h4>
                ${camposCamara(prefijo, c, true)}
            `;
            cont.appendChild(caja);
        }

        function pintarCamaras() {
            const principal = document.getElementById('cam-principal');
            if (principal) {
                principal.innerHTML = '<h4 class="aux-title">Cámara Principal</h4>' + camposCamara('main', camaraPrincipal, false);
            }
            camarasAuxiliares.forEach(function (c) { agregarCamaraAuxiliar(c); });
        }

        /**
         * PRECIOS REGIONALES: muestra u oculta el campo de precio según el checkbox
         */
        function togglePriceInput(checkbox, boxId) {
            const box = document.getElementById(boxId);
            if (!box) return;
            box.classList.toggle('oculto', !checkbox.checked);
            if (!checkbox.checked) {
                const input = box.querySelector('input');
                if (input) input.value = '';
            }
        }

        window.addEventListener('DOMContentLoaded', function () {
            generarVersionesMemoria();
            pintarCamaras();
        });
    </script>
    <?php endif; ?>
</body>
</html>
