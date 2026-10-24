<?php
/**
 * ============================================================================
 * LÓGICA DE GUARDADO DEL PANEL ADMIN
 * Cargar la ficha de un móvil, crear / actualizar / borrar y asociar variantes.
 * Requiere includes/funciones.php.
 * ============================================================================
 */

// Convierte una fila de camera_modules en el formato que usa el formulario (JavaScript)
function camaraParaForm(array $c) {
    list($vres, $vfps) = partir_video($c['normal_video_max'] ?? '');
    list($vres2, $vfps2) = partir_video($c['normal_video_max_2'] ?? '');
    return [
        'type'          => (string)($c['camera_type'] ?? ''),
        'sensor_name'   => (string)($c['sensor_name'] ?? ''),
        'sensor_size'   => (string)($c['sensor_size'] ?? ''),
        'resolution_mp' => num_limpio($c['resolution_mp'] ?? ''),
        'aperture'      => num_limpio($c['aperture'] ?? ''),
        'video_res'     => $vres,
        'video_fps'     => $vfps,
        'video_res2'    => $vres2,
        'video_fps2'    => $vfps2,
        'has_log'       => !empty($c['has_log']),
        'log_res'       => (string)($c['log_resolution'] ?? ''),
        'log_fps'       => (string)($c['log_fps'] ?? ''),
        'log_res2'      => (string)($c['log_resolution_2'] ?? ''),
        'log_fps2'      => (string)($c['log_fps_2'] ?? ''),
        'has_og'        => !empty($c['has_opengate']),
        'og_res'        => (string)($c['opengate_resolution'] ?? ''),
        'og_fps'        => (string)($c['opengate_fps'] ?? ''),
        'og_res2'       => (string)($c['opengate_resolution_2'] ?? ''),
        'og_fps2'       => (string)($c['opengate_fps_2'] ?? ''),
        'has_ogl'       => !empty($c['has_opengate_log']),
        'ogl_res'       => (string)($c['opengate_log_resolution'] ?? ''),
        'ogl_fps'       => (string)($c['opengate_log_fps'] ?? ''),
        'ogl_res2'      => (string)($c['opengate_log_resolution_2'] ?? ''),
        'ogl_fps2'      => (string)($c['opengate_log_fps_2'] ?? ''),
    ];
}

// Convierte una versión de memoria (fila de la base de datos) en el formato del formulario (JavaScript)
function versionParaForm(array $sv) {
    $ram = $sv['ram_gb'] !== null ? (string)$sv['ram_gb'] : trim((string)($sv['ram_capacities_gb'] ?? ''));
    $almacenamiento = $sv['storage_gb'] !== null ? (string)$sv['storage_gb'] : trim((string)($sv['storage_capacities_gb'] ?? ''));
    $datos = [
        'ram_gb'       => $ram,
        'ram_tech'     => (string)($sv['ram_technology'] ?? ''),
        'storage_gb'   => $almacenamiento,
        'storage_tech' => (string)($sv['storage_technology'] ?? ''),
    ];
    foreach (regiones_precio() as $info) {
        $datos[$info['col']] = num_limpio($sv[$info['col']] ?? '');
    }
    return $datos;
}

/**
 * Carga todos los datos de un móvil en un array con una clave por tabla:
 * phone, sw, meta, soc, scr, bat, conn, storage (varias), main_cam, aux_cams, variantes
 */
function cargarFicha(PDO $conexion, $phone_id) {
    $phone_id = (int)$phone_id;
    if ($phone_id <= 0) return [];

    $st = $conexion->prepare("
        SELECT p.*, b.name AS marca_nombre, s.name AS familia_nombre, os.name AS so_nombre
        FROM phones p
        INNER JOIN series s ON s.id = p.series_id
        INNER JOIN brands b ON b.id = s.brand_id
        LEFT JOIN operative_systems os ON os.id = p.operative_system_id
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
        'phone'     => $phone,
        'sw'        => $una("SELECT * FROM software_support WHERE phone_id = ? ORDER BY id LIMIT 1", $phone_id),
        'meta'      => $una("SELECT * FROM phone_metadata WHERE phone_id = ? ORDER BY id LIMIT 1", $phone_id),
        'soc'       => [],
        'scr'       => [],
        'bat'       => [],
        'conn'      => [],
        'storage'   => [],
        'main_cam'  => null,
        'aux_cams'  => [],
        'variantes' => [],
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

    // Variantes regionales: los móviles que comparten el mismo grupo
    if (!empty($phone['variant_group_id'])) {
        $q = $conexion->prepare("
            SELECT p.id, p.name, b.name AS marca
            FROM phones p
            INNER JOIN series s ON s.id = p.series_id
            INNER JOIN brands b ON b.id = s.brand_id
            WHERE p.variant_group_id = ? AND p.id <> ?
            ORDER BY b.name, p.name
        ");
        $q->execute([(int)$phone['variant_group_id'], $phone_id]);
        $ficha['variantes'] = $q->fetchAll();
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

// Texto de un modo de vídeo para guardar: "4K @ 120fps" (o solo "4K" si no hay fps). Null si no hay resolución.
function texto_video($resolucion, $fps) {
    $resolucion = is_array($resolucion) ? '' : trim((string)($resolucion ?? ''));
    if ($resolucion === '') return null;
    $fps = int_o_null($fps);
    return $resolucion . ($fps !== null ? ' @ ' . $fps . 'fps' : '');
}

/**
 * Inserta un módulo de cámara en camera_modules (principal o auxiliar).
 * Cada tipo de vídeo admite dos opciones de resolución / fps (ej: 4K 120fps y 8K 30fps).
 */
function insertarCamara(PDO $conexion, $char_id, $tipo, array $d) {
    $has_log = !empty($d['has_log']) ? 1 : 0;
    $has_og  = !empty($d['has_og']) ? 1 : 0;
    $has_ogl = !empty($d['has_ogl']) ? 1 : 0;

    $datos = [
        'phone_characteristic_id'   => $char_id,
        'camera_type'               => $tipo,
        'sensor_name'               => texto_o_null($d['sensor_name'] ?? '') ?? 'No especificado',
        'sensor_size'               => texto_o_null($d['sensor_size'] ?? ''),
        'resolution_mp'             => dec_o_null($d['resolution_mp'] ?? ''),
        'aperture'                  => dec_o_null($d['aperture'] ?? ''),
        'normal_video_max'          => texto_video($d['video_res'] ?? '', $d['video_fps'] ?? ''),
        'normal_video_max_2'        => texto_video($d['video_res2'] ?? '', $d['video_fps2'] ?? ''),
        'has_log'                   => $has_log,
        'log_resolution'            => $has_log ? texto_o_null($d['log_res'] ?? '') : null,
        'log_fps'                   => $has_log ? int_o_null($d['log_fps'] ?? '') : null,
        'log_resolution_2'          => $has_log ? texto_o_null($d['log_res2'] ?? '') : null,
        'log_fps_2'                 => $has_log ? int_o_null($d['log_fps2'] ?? '') : null,
        'has_opengate'              => $has_og,
        'opengate_resolution'       => $has_og ? texto_o_null($d['og_res'] ?? '') : null,
        'opengate_fps'              => $has_og ? int_o_null($d['og_fps'] ?? '') : null,
        'opengate_resolution_2'     => $has_og ? texto_o_null($d['og_res2'] ?? '') : null,
        'opengate_fps_2'            => $has_og ? int_o_null($d['og_fps2'] ?? '') : null,
        'has_opengate_log'          => $has_ogl,
        'opengate_log_resolution'   => $has_ogl ? texto_o_null($d['ogl_res'] ?? '') : null,
        'opengate_log_fps'          => $has_ogl ? int_o_null($d['ogl_fps'] ?? '') : null,
        'opengate_log_resolution_2' => $has_ogl ? texto_o_null($d['ogl_res2'] ?? '') : null,
        'opengate_log_fps_2'        => $has_ogl ? int_o_null($d['ogl_fps2'] ?? '') : null,
    ];

    $columnas = array_keys($datos);
    $marcas = implode(', ', array_fill(0, count($columnas), '?'));
    $conexion->prepare("INSERT INTO camera_modules (`" . implode('`, `', $columnas) . "`) VALUES ($marcas)")
             ->execute(array_values($datos));
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
 * Versiones de memoria: una fila por combinación concreta (ej: 12 GB + 256 GB), cada una con sus precios por región.
 * Se escriben también las columnas antiguas (ram_capacities_gb / storage_capacities_gb) por compatibilidad.
 */
function gestionarVersionesMemoria(PDO $conexion, $char_id, array $post_data) {
    $conexion->prepare("DELETE FROM characteristics_storage_versions WHERE phone_characteristic_id = ?")->execute([$char_id]);

    $versiones = $post_data['storage_versions'] ?? null;
    if (!is_array($versiones)) return;

    foreach ($versiones as $sv) {
        if (!is_array($sv)) continue;
        $ram = int_o_null($sv['ram_gb'] ?? '');
        $almacenamiento = int_o_null($sv['storage_gb'] ?? '');
        if ($ram === null || $almacenamiento === null || $ram <= 0 || $almacenamiento <= 0) continue;

        $datos = [
            'phone_characteristic_id' => $char_id,
            'ram_capacities_gb'       => (string)$ram,
            'ram_technology'          => trim((string)($sv['ram_tech'] ?? '')),
            'storage_capacities_gb'   => (string)$almacenamiento,
            'storage_technology'      => trim((string)($sv['storage_tech'] ?? '')),
            'ram_gb'                  => $ram,
            'storage_gb'              => $almacenamiento,
        ];
        foreach (regiones_precio() as $info) {
            $precio = dec_o_null($sv[$info['col']] ?? '');
            $datos[$info['col']] = ($precio !== null && $precio > 0) ? $precio : null;
        }

        $columnas = array_keys($datos);
        $marcas = implode(', ', array_fill(0, count($columnas), '?'));
        $conexion->prepare("INSERT INTO characteristics_storage_versions (`" . implode('`, `', $columnas) . "`) VALUES ($marcas)")
                 ->execute(array_values($datos));
    }
}

// Deja en NULL los grupos de variantes que se han quedado con un solo móvil
function limpiar_grupos_variantes(PDO $conexion) {
    $grupos = $conexion->query("SELECT variant_group_id FROM phones WHERE variant_group_id IS NOT NULL GROUP BY variant_group_id HAVING COUNT(*) = 1")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($grupos as $g) {
        $conexion->prepare("UPDATE phones SET variant_group_id = NULL WHERE variant_group_id = ?")->execute([$g]);
    }
}

/**
 * Asocia el móvil con sus variantes regionales. Todos los móviles de un mismo grupo son variantes entre sí:
 * si A se asocia con B y C, entonces B y C también se ven entre ellos.
 * $ids es la lista COMPLETA de variantes que debe tener el móvil (las que ya no estén se desasocian).
 */
function guardarVariantes(PDO $conexion, $phone_id, array $ids) {
    $phone_id = (int)$phone_id;
    $limpios = [];
    foreach ($ids as $id) {
        $id = (int)$id;
        if ($id > 0 && $id !== $phone_id && !in_array($id, $limpios, true)) $limpios[] = $id;
    }

    $st = $conexion->prepare("SELECT variant_group_id FROM phones WHERE id = ?");
    $st->execute([$phone_id]);
    $fila = $st->fetch();
    $grupo = ($fila && !empty($fila['variant_group_id'])) ? (int)$fila['variant_group_id'] : 0;

    // Los que estaban en el grupo y ya no están en la lista se sacan de él
    if ($grupo > 0) {
        $st = $conexion->prepare("SELECT id FROM phones WHERE variant_group_id = ? AND id <> ?");
        $st->execute([$grupo, $phone_id]);
        foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $actual) {
            if (!in_array((int)$actual, $limpios, true)) {
                $conexion->prepare("UPDATE phones SET variant_group_id = NULL WHERE id = ?")->execute([$actual]);
            }
        }
    }

    if (empty($limpios)) {
        $conexion->prepare("UPDATE phones SET variant_group_id = NULL WHERE id = ?")->execute([$phone_id]);
        limpiar_grupos_variantes($conexion);
        return;
    }

    if ($grupo <= 0) {
        $grupo = (int)$conexion->query("SELECT COALESCE(MAX(variant_group_id), 0) + 1 FROM phones")->fetchColumn();
    }

    $todos = array_merge([$phone_id], $limpios);
    $marcas = implode(', ', array_fill(0, count($todos), '?'));
    $conexion->prepare("UPDATE phones SET variant_group_id = ? WHERE id IN ($marcas)")
             ->execute(array_merge([$grupo], $todos));

    limpiar_grupos_variantes($conexion);
}

/**
 * Crea (phone_id = 0) o actualiza un móvil completo.
 * $imagen_nueva: ruta de una imagen recién subida (ya validada) o null.
 * Devuelve ['id' => id del móvil, 'imagen_a_borrar' => ruta de la imagen sustituida o null].
 */
function guardarMovil(PDO $conexion, $phone_id, array $p, $imagen_nueva = null) {
    $phone_id = (int)$phone_id;
    $es_nuevo = ($phone_id <= 0);

    $marca_nombre   = trim((string)($p['marca_nombre'] ?? ''));
    $familia_nombre = trim((string)($p['familia_nombre'] ?? ''));
    $nombre         = trim((string)($p['nombre'] ?? ''));
    if ($marca_nombre === '' || $familia_nombre === '' || $nombre === '') {
        throw new Exception('La marca, la familia y el nombre del móvil son obligatorios.');
    }

    $launch_year = int_o_null($p['launch_year'] ?? '') ?? 2026;
    $tier = trim((string)($p['tier'] ?? ''));
    if ($tier === 'Por defecto') $tier = '';

    // --- Sistema operativo (propio de cada móvil) ---
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
    } else {
        $conexion->prepare("INSERT INTO brands (name) VALUES (?)")->execute([$marca_nombre]);
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
    $imagen_anterior = null;
    if (!$es_nuevo) {
        $st = $conexion->prepare("SELECT id FROM phones WHERE id = ?");
        $st->execute([$phone_id]);
        if (!$st->fetch()) throw new Exception('El móvil que intentas actualizar no existe.');

        $st = $conexion->prepare("SELECT image_url FROM phone_metadata WHERE phone_id = ? ORDER BY id LIMIT 1");
        $st->execute([$phone_id]);
        $fila = $st->fetch();
        $imagen_anterior = $fila ? $fila['image_url'] : null;

        $conexion->prepare("UPDATE phones SET series_id = ?, operative_system_id = ?, name = ?, launch_year = ?, tier = ? WHERE id = ?")
                 ->execute([$serie_id, $so_id, $nombre, $launch_year, $tier, $phone_id]);
    } else {
        $conexion->prepare("INSERT INTO phones (series_id, operative_system_id, name, launch_year, tier) VALUES (?, ?, ?, ?, ?)")
                 ->execute([$serie_id, $so_id, $nombre, $launch_year, $tier]);
        $phone_id = (int)$conexion->lastInsertId();
    }

    // --- Soporte de software ---
    guardarFila($conexion, 'software_support', 'phone_id', $phone_id, [
        'initial_skin'             => texto_o_null($p['initial_skin'] ?? ''),
        'os_and_skin_update_years' => int_o_null($p['os_updates'] ?? ''),
        'security_update_years'    => int_o_null($p['sec_updates'] ?? ''),
    ]);

    // --- Valoración: 6 notas del hexágono, nota media automática (suma / 6) y prioridad ---
    $notas = [];
    $suma_notas = 0;
    $hay_nota = false;
    for ($i = 1; $i <= 6; $i++) {
        $n = dec_o_null($p['score_' . $i] ?? '');
        if ($n !== null) {
            $n = max(0, min(10, round($n, 2)));
            $suma_notas += $n;
            $hay_nota = true;
        }
        $notas['score_' . $i] = $n;
    }
    $score_avg = $hay_nota ? round($suma_notas / 6, 2) : null;

    $prioridad = (string)($p['priority'] ?? 'ninguna');
    if (!array_key_exists($prioridad, prioridades())) $prioridad = 'ninguna';

    // --- Metadatos: dimensiones, IP, materiales, valoración y precios regionales ---
    $datos_meta = array_merge([
        'score_avg'        => $score_avg,
    ], $notas, [
        'personal_comment' => texto_o_null($p['personal_comment'] ?? ''),
        'priority'         => $prioridad,
        'ip_rating'        => texto_o_null($p['ip_rating'] ?? ''),
        'frame_material'   => texto_o_null($p['frame_material'] ?? ''),
        'back_material'    => texto_o_null($p['back_material'] ?? ''),
        'height_mm'        => dec_o_null($p['height_mm'] ?? ''),
        'width_mm'         => dec_o_null($p['width_mm'] ?? ''),
        'thickness_mm'     => dec_o_null($p['thickness_mm'] ?? ''),
        'weight_g'         => int_o_null($p['weight_g'] ?? ''),
    ]);
    foreach (regiones_precio() as $info) {
        $datos_meta[$info['col']] = dec_o_null($p[$info['col']] ?? '');
    }
    $datos_meta['price_carrier']    = dec_o_null($p['price_carrier'] ?? '');
    $datos_meta['carrier_currency'] = texto_o_null($p['carrier_currency'] ?? '') ?? 'EUR';
    $datos_meta['carrier_details']  = texto_o_null($p['carrier_details'] ?? '');

    // Imagen: si se sube una nueva sustituye a la anterior; si se marca "quitar" se elimina; si no, se deja como está
    $imagen_a_borrar = null;
    if ($imagen_nueva !== null) {
        $datos_meta['image_url'] = $imagen_nueva;
        $imagen_a_borrar = $imagen_anterior;
    } elseif (!empty($p['quitar_imagen'])) {
        $datos_meta['image_url'] = null;
        $imagen_a_borrar = $imagen_anterior;
    }
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

    // --- Versiones de memoria (RAM + almacenamiento + precios) ---
    gestionarVersionesMemoria($conexion, $char_id, $p);

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
        'assured_cycles_80_health'    => int_o_null($p['cycles_80'] ?? ''),
    ]);

    // --- Conectividad ---
    guardarFila($conexion, 'characteristics_connectivity', 'phone_characteristic_id', $char_id, [
        'mobile_network'    => texto_o_null($p['network'] ?? ''),
        'wifi_version'      => texto_o_null($p['wifi'] ?? ''),
        'bluetooth_version' => texto_o_null($p['bluetooth'] ?? ''),
        'nfc'               => isset($p['nfc']) ? 1 : 0,
        'esim_support'      => isset($p['esim']) ? 1 : 0,
        'android_auto'      => isset($p['android_auto']) ? 1 : 0,
    ]);

    // --- Cámaras ---
    gestionarCamaras($conexion, $char_id, $p);

    // --- Variantes regionales ---
    $ids_variantes = (isset($p['variantes']) && is_array($p['variantes'])) ? $p['variantes'] : [];
    guardarVariantes($conexion, $phone_id, $ids_variantes);

    return ['id' => $phone_id, 'imagen_a_borrar' => $imagen_a_borrar];
}

/**
 * Borra un móvil y todos sus datos asociados. Devuelve la ruta de su imagen (para borrarla del disco tras confirmar).
 */
function borrarMovil(PDO $conexion, $phone_id) {
    $st = $conexion->prepare("SELECT image_url FROM phone_metadata WHERE phone_id = ? ORDER BY id LIMIT 1");
    $st->execute([$phone_id]);
    $fila = $st->fetch();
    $imagen = $fila ? $fila['image_url'] : null;

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

    limpiar_grupos_variantes($conexion);
    return $imagen;
}
