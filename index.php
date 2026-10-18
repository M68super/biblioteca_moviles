<?php
require_once __DIR__ . '/config/conexion.php';

$sesion_iniciada = isset($_SESSION['user_id']);
$nombre_usuario = $sesion_iniciada ? $_SESSION['username'] : '';
$es_admin = $sesion_iniciada && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;

$mensaje_exito = '';
$mensaje_error = '';

$modo_admin = $_GET['modo'] ?? 'crear';
$editar_id = isset($_GET['editar_id']) ? (int)$_GET['editar_id'] : 0;
$borrar_id = isset($_GET['borrar_id']) ? (int)$_GET['borrar_id'] : 0;

// DATOS PARA CARGAR EN MODO EDICIÓN
$edit_data = [];
$edit_cams = [];
$main_cam = null;
$aux_cams = [];

if ($es_admin && $modo_admin === 'editar' && $editar_id > 0) {
    try {
        $stmt_ed = $conexion->prepare("
            SELECT p.*, b.name AS marca_nombre, s.name AS familia_nombre, 
                   sw.*, meta.*, soc.*, stor.*, scr.*, bat.*, conn.*
            FROM phones p
            INNER JOIN series s ON s.id = p.series_id
            INNER JOIN brands b ON b.id = s.brand_id
            LEFT JOIN software_support sw ON sw.phone_id = p.id
            LEFT JOIN phone_metadata meta ON meta.phone_id = p.id
            LEFT JOIN phone_characteristics pc ON pc.phone_id = p.id
            LEFT JOIN characteristics_soc soc ON soc.phone_characteristic_id = pc.id
            LEFT JOIN characteristics_storage stor ON stor.phone_characteristic_id = pc.id
            LEFT JOIN characteristics_screen scr ON scr.phone_characteristic_id = pc.id
            LEFT JOIN characteristics_battery bat ON bat.phone_characteristic_id = pc.id
            LEFT JOIN characteristics_connectivity conn ON conn.phone_characteristic_id = pc.id
            WHERE p.id = ?
        ");
        $stmt_ed->execute([$editar_id]);
        $edit_data = $stmt_ed->fetch(PDO::FETCH_ASSOC) ?: [];

        $stmt_c = $conexion->prepare("SELECT id FROM phone_characteristics WHERE phone_id = ? LIMIT 1");
        $stmt_c->execute([$editar_id]);
        $char_row = $stmt_c->fetch();
        if ($char_row) {
            $char_id = $char_row['id'];
            $stmt_ec = $conexion->prepare("SELECT * FROM camera_modules WHERE phone_characteristic_id = ? ORDER BY CASE WHEN camera_type = 'Principal' THEN 1 ELSE 2 END, focal_length_mm ASC");
            $stmt_ec->execute([$char_id]);
            $edit_cams = $stmt_ec->fetchAll(PDO::FETCH_ASSOC);

            foreach ($edit_cams as $c) {
                if ($c['camera_type'] === 'Principal' && !$main_cam) {
                    $main_cam = $c;
                } else {
                    $aux_cams[] = $c;
                }
            }
        }
    } catch (Exception $e) {
        $mensaje_error = "Error al cargar datos para editar: " . $e->getMessage();
    }
}

// DATOS PARA CARGAR EN MODO BORRADO
$delete_data = [];
$delete_cams = [];
$del_main_cam = null;
$del_aux_cams = [];

if ($es_admin && $modo_admin === 'borrar' && $borrar_id > 0) {
    try {
        $stmt_del = $conexion->prepare("
            SELECT p.*, b.name AS marca_nombre, s.name AS familia_nombre, 
                   sw.*, meta.*, soc.*, stor.*, scr.*, bat.*, conn.*
            FROM phones p
            INNER JOIN series s ON s.id = p.series_id
            INNER JOIN brands b ON b.id = s.brand_id
            LEFT JOIN software_support sw ON sw.phone_id = p.id
            LEFT JOIN phone_metadata meta ON meta.phone_id = p.id
            LEFT JOIN phone_characteristics pc ON pc.phone_id = p.id
            LEFT JOIN characteristics_soc soc ON soc.phone_characteristic_id = pc.id
            LEFT JOIN characteristics_storage stor ON stor.phone_characteristic_id = pc.id
            LEFT JOIN characteristics_screen scr ON scr.phone_characteristic_id = pc.id
            LEFT JOIN characteristics_battery bat ON bat.phone_characteristic_id = pc.id
            LEFT JOIN characteristics_connectivity conn ON conn.phone_characteristic_id = pc.id
            WHERE p.id = ?
        ");
        $stmt_del->execute([$borrar_id]);
        $delete_data = $stmt_del->fetch(PDO::FETCH_ASSOC) ?: [];

        $stmt_c_del = $conexion->prepare("SELECT id FROM phone_characteristics WHERE phone_id = ? LIMIT 1");
        $stmt_c_del->execute([$borrar_id]);
        $char_row_del = $stmt_c_del->fetch();
        if ($char_row_del) {
            $char_id_del = $char_row_del['id'];
            $stmt_ec_del = $conexion->prepare("SELECT * FROM camera_modules WHERE phone_characteristic_id = ? ORDER BY CASE WHEN camera_type = 'Principal' THEN 1 ELSE 2 END, focal_length_mm ASC");
            $stmt_ec_del->execute([$char_id_del]);
            $delete_cams = $stmt_ec_del->fetchAll(PDO::FETCH_ASSOC);

            foreach ($delete_cams as $c) {
                if ($c['camera_type'] === 'Principal' && !$del_main_cam) {
                    $del_main_cam = $c;
                } else {
                    $del_aux_cams[] = $c;
                }
            }
        }
    } catch (Exception $e) {
        $mensaje_error = "Error al cargar datos para borrar: " . $e->getMessage();
    }
}

// FUNCIÓN AUXILIAR PARA GUARDAR CÁMARAS EN ADMIN
function gestionarCamaras($conexion, $char_id, $post_data) {
    $conexion->prepare("DELETE FROM camera_modules WHERE phone_characteristic_id = ?")->execute([$char_id]);

    if (!empty($post_data['main_sensor_name'])) {
        $stmt = $conexion->prepare("INSERT INTO camera_modules (phone_characteristic_id, camera_type, sensor_name, sensor_size, resolution_mp, aperture, focal_length_mm, normal_video_max, has_log, log_specs, has_opengate, opengate_specs) VALUES (?, 'Principal', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $char_id,
            trim($post_data['main_sensor_name']),
            trim($post_data['main_sensor_size'] ?? ''),
            !empty($post_data['main_resolution_mp']) ? (float)$post_data['main_resolution_mp'] : null,
            trim($post_data['main_aperture'] ?? ''),
            !empty($post_data['main_focal_length_mm']) ? (int)$post_data['main_focal_length_mm'] : null,
            trim($post_data['main_normal_video_max'] ?? '4K @ 60fps'),
            isset($post_data['main_has_log']) ? 1 : 0,
            trim($post_data['main_log_specs'] ?? 'No tiene'),
            isset($post_data['main_has_opengate']) ? 1 : 0,
            trim($post_data['main_opengate_specs'] ?? 'No tiene')
        ]);
    }

    if (!empty($post_data['aux_type']) && is_array($post_data['aux_type'])) {
        for ($i = 0; $i < count($post_data['aux_type']); $i++) {
            $type = $post_data['aux_type'][$i];
            $s_name = trim($post_data['aux_sensor_name'][$i] ?? '');
            
            // Si no se selecciona tipo, ignoramos
            if (empty($type)) continue;
            
            // Si el nombre del sensor está vacío, ponemos un valor por defecto para permitir guardarlo
            if (empty($s_name)) {
                $s_name = 'No especificado';
            }

            $stmt = $conexion->prepare("INSERT INTO camera_modules (phone_characteristic_id, camera_type, sensor_name, sensor_size, resolution_mp, aperture, focal_length_mm, normal_video_max, has_log, log_specs, has_opengate, opengate_specs) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $char_id,
                $type,
                $s_name,
                trim($post_data['aux_sensor_size'][$i] ?? ''),
                !empty($post_data['aux_resolution_mp'][$i]) ? (float)$post_data['aux_resolution_mp'][$i] : null,
                trim($post_data['aux_aperture'][$i] ?? ''),
                !empty($post_data['aux_focal_length_mm'][$i]) ? (int)$post_data['aux_focal_length_mm'][$i] : null,
                trim($post_data['aux_normal_video_max'][$i] ?? '4K @ 60fps'),
                isset($post_data['aux_has_log'][$i]) ? 1 : 0,
                trim($post_data['aux_log_specs'][$i] ?? 'No tiene'),
                isset($post_data['aux_has_opengate'][$i]) ? 1 : 0,
                trim($post_data['aux_opengate_specs'][$i] ?? 'No tiene')
            ]);
        }
    }
}

// PROCESAR REGISTRO, ACTUALIZACIÓN O BORRADO (ADMIN)
if ($es_admin && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    try {
        $conexion->beginTransaction();
        $accion = $_POST['accion'];

        if ($accion === 'borrar_movil_completo') {
            $phone_id = (int)$_POST['phone_id'];
            if ($phone_id <= 0) throw new Exception("ID de móvil inválido para borrar.");

            // Buscar characteristic_id
            $stmt_char_del = $conexion->prepare("SELECT id FROM phone_characteristics WHERE phone_id = ?");
            $stmt_char_del->execute([$phone_id]);
            $chars_to_del = $stmt_char_del->fetchAll(PDO::FETCH_COLUMN);

            foreach ($chars_to_del as $cid) {
                $conexion->prepare("DELETE FROM camera_modules WHERE phone_characteristic_id = ?")->execute([$cid]);
                $conexion->prepare("DELETE FROM characteristics_soc WHERE phone_characteristic_id = ?")->execute([$cid]);
                $conexion->prepare("DELETE FROM characteristics_storage WHERE phone_characteristic_id = ?")->execute([$cid]);
                $conexion->prepare("DELETE FROM characteristics_screen WHERE phone_characteristic_id = ?")->execute([$cid]);
                $conexion->prepare("DELETE FROM characteristics_battery WHERE phone_characteristic_id = ?")->execute([$cid]);
                $conexion->prepare("DELETE FROM characteristics_connectivity WHERE phone_characteristic_id = ?")->execute([$cid]);
            }

            $conexion->prepare("DELETE FROM phone_characteristics WHERE phone_id = ?")->execute([$phone_id]);
            $conexion->prepare("DELETE FROM software_support WHERE phone_id = ?")->execute([$phone_id]);
            $conexion->prepare("DELETE FROM phone_metadata WHERE phone_id = ?")->execute([$phone_id]);
            $conexion->prepare("DELETE FROM phones WHERE id = ?")->execute([$phone_id]);

            $conexion->commit();
            $mensaje_exito = "¡El terminal ha sido borrado permanentemente de la base de datos!";
        } else {
            $marca_nombre = trim($_POST['marca_nombre']);
            $familia_nombre = trim($_POST['familia_nombre']);
            $nombre = trim($_POST['nombre']);
            $launch_year = (int)$_POST['launch_year'];
            $tier = $_POST['tier'];

            if (empty($marca_nombre) || empty($familia_nombre) || empty($nombre)) {
                throw new Exception('La marca, la familia y el nombre del móvil son obligatorios.');
            }

            // Procesar notas y comentarios aquí
            $s1 = !empty($_POST['score_1']) ? (float)$_POST['score_1'] : null;
            $s2 = !empty($_POST['score_2']) ? (float)$_POST['score_2'] : null;
            $s3 = !empty($_POST['score_3']) ? (float)$_POST['score_3'] : null;
            $s4 = !empty($_POST['score_4']) ? (float)$_POST['score_4'] : null;
            $s5 = !empty($_POST['score_5']) ? (float)$_POST['score_5'] : null;
            $s6 = !empty($_POST['score_6']) ? (float)$_POST['score_6'] : null;

            $score_avg = null;
            if ($s1 !== null || $s2 !== null || $s3 !== null || $s4 !== null || $s5 !== null || $s6 !== null) {
                $score_avg = round((($s1 ?? 0) + ($s2 ?? 0) + ($s3 ?? 0) + ($s4 ?? 0) + ($s5 ?? 0) + ($s6 ?? 0)) / 6, 2);
            }
            $personal_comment = trim($_POST['personal_comment'] ?? '');
            $image_url = trim($_POST['image_url'] ?? '');

            $stmt_b = $conexion->prepare("SELECT id FROM brands WHERE name = ?");
            $stmt_b->execute([$marca_nombre]);
            $b_row = $stmt_b->fetch();
            if ($b_row) {
                $marca_id = $b_row['id'];
            } else {
                $stmt_ins_b = $conexion->prepare("INSERT INTO brands (name) VALUES (?)");
                $stmt_ins_b->execute([$marca_nombre]);
                $marca_id = $conexion->lastInsertId();
            }

            $stmt_s = $conexion->prepare("SELECT id FROM series WHERE brand_id = ? AND name = ?");
            $stmt_s->execute([$marca_id, $familia_nombre]);
            $s_row = $stmt_s->fetch();
            if ($s_row) {
                $serie_id = $s_row['id'];
            } else {
                $stmt_ins_s = $conexion->prepare("INSERT INTO series (brand_id, name) VALUES (?, ?)");
                $stmt_ins_s->execute([$marca_id, $familia_nombre]);
                $serie_id = $conexion->lastInsertId();
            }

            if ($accion === 'crear_movil_completo') {
                $stmt = $conexion->prepare("INSERT INTO phones (series_id, name, launch_year, tier) VALUES (?, ?, ?, ?)");
                $stmt->execute([$serie_id, $nombre, $launch_year, $tier]);
                $phone_id = $conexion->lastInsertId();

                $conexion->prepare("INSERT INTO software_support (phone_id, initial_os, initial_skin, os_and_skin_update_years, security_update_years) VALUES (?, ?, ?, ?, ?)")->execute([$phone_id, $_POST['initial_os'], $_POST['initial_skin'], (int)$_POST['os_updates'], (int)$_POST['sec_updates']]);

                $conexion->prepare("INSERT INTO phone_metadata (phone_id, height_mm, width_mm, thickness_body_mm, thickness_camera_extra_mm, ip_rating, frame_material, back_material, price_eur, price_cny, official_accessories, score_1, score_2, score_3, score_4, score_5, score_6, score_avg, personal_comment, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")->execute([
                    $phone_id, 
                    !empty($_POST['height']) ? (float)$_POST['height'] : null, 
                    !empty($_POST['width']) ? (float)$_POST['width'] : null, 
                    !empty($_POST['thickness']) ? (float)$_POST['thickness'] : null, 
                    !empty($_POST['cam_thickness']) ? (float)$_POST['cam_thickness'] : null, 
                    $_POST['ip_rating'], 
                    $_POST['frame_material'] ?? '', 
                    $_POST['back_material'] ?? '', 
                    !empty($_POST['price_eur']) ? (float)$_POST['price_eur'] : null, 
                    !empty($_POST['price_cny']) ? (float)$_POST['price_cny'] : null, 
                    $_POST['accessories'] ?? '',
                    $s1, $s2, $s3, $s4, $s5, $s6, $score_avg, $personal_comment, $image_url
                ]);

                $conexion->prepare("INSERT INTO phone_characteristics (phone_id, variant_name) VALUES (?, 'Versión Estándar')")->execute([$phone_id]);
                $char_id = $conexion->lastInsertId();

                $conexion->prepare("INSERT INTO characteristics_soc (phone_characteristic_id, brand, full_name, launch_year, architecture_nm, antutu_score, geekbench_single, geekbench_multi, gpu_tflops, npu_tops) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")->execute([
                    $char_id, $_POST['soc_brand'], $_POST['soc_name'], !empty($_POST['soc_year']) ? (int)$_POST['soc_year'] : null, !empty($_POST['soc_nm']) ? (int)$_POST['soc_nm'] : null, !empty($_POST['antutu']) ? (int)$_POST['antutu'] : null, !empty($_POST['gb_single']) ? (int)$_POST['gb_single'] : null, !empty($_POST['gb_multi']) ? (int)$_POST['gb_multi'] : null, !empty($_POST['tflops']) ? (float)$_POST['tflops'] : null, !empty($_POST['tops']) ? (float)$_POST['tops'] : null
                ]);

                $conexion->prepare("INSERT INTO characteristics_storage (phone_characteristic_id, ram_capacities_gb, ram_technology, storage_capacities_gb, storage_technology) VALUES (?, ?, ?, ?, ?)")->execute([$char_id, $_POST['ram_cap'], $_POST['ram_tech'], $_POST['storage_cap'], $_POST['storage_tech']]);

                $conexion->prepare("INSERT INTO characteristics_screen (phone_characteristic_id, inches, resolution, refresh_rate_hz, max_brightness_nits, technology, resistance_certificate, screen_to_body_ratio) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")->execute([
                    $char_id, !empty($_POST['inches']) ? (float)$_POST['inches'] : null, $_POST['resolution'], !empty($_POST['hz']) ? (int)$_POST['hz'] : null, !empty($_POST['nits']) ? (int)$_POST['nits'] : null, $_POST['screen_tech'], $_POST['screen_cert'], !empty($_POST['screen_ratio']) ? (float)$_POST['screen_ratio'] : null
                ]);

                $conexion->prepare("INSERT INTO characteristics_battery (phone_characteristic_id, capacity_mah, wired_charging_w, wireless_charging_w, reverse_wired_charging_w, reverse_wireless_charging_w, assured_cycles_80_health) VALUES (?, ?, ?, ?, ?, ?, ?)")->execute([
                    $char_id, !empty($_POST['mah']) ? (int)$_POST['mah'] : null, !empty($_POST['wired_w']) ? (int)$_POST['wired_w'] : null, !empty($_POST['wireless_w']) ? (int)$_POST['wireless_w'] : null, !empty($_POST['rev_wired_w']) ? (int)$_POST['rev_wired_w'] : null, !empty($_POST['rev_wireless_w']) ? (int)$_POST['rev_wireless_w'] : null, !empty($_POST['cycles']) ? (int)$_POST['cycles'] : null
                ]);

                $conexion->prepare("INSERT INTO characteristics_connectivity (phone_characteristic_id, mobile_network, wifi_version, bluetooth_version, nfc, esim_support) VALUES (?, ?, ?, ?, ?, ?)")->execute([
                    $char_id, $_POST['network'], $_POST['wifi'], $_POST['bluetooth'], isset($_POST['nfc']) ? 1 : 0, isset($_POST['esim']) ? 1 : 0
                ]);

                gestionarCamaras($conexion, $char_id, $_POST);
                $conexion->commit();
                $mensaje_exito = "¡Móvil '$nombre' registrado con éxito!";

            } elseif ($accion === 'actualizar_movil_completo') {
                $phone_id = (int)$_POST['phone_id'];

                $conexion->prepare("UPDATE phones SET series_id = ?, name = ?, launch_year = ?, tier = ? WHERE id = ?")->execute([$serie_id, $nombre, $launch_year, $tier, $phone_id]);

                $conexion->prepare("UPDATE software_support SET initial_os = ?, initial_skin = ?, os_and_skin_update_years = ?, security_update_years = ? WHERE phone_id = ?")->execute([$_POST['initial_os'], $_POST['initial_skin'], (int)$_POST['os_updates'], (int)$_POST['sec_updates'], $phone_id]);

                $conexion->prepare("UPDATE phone_metadata SET height_mm = ?, width_mm = ?, thickness_body_mm = ?, thickness_camera_extra_mm = ?, ip_rating = ?, frame_material = ?, back_material = ?, price_eur = ?, price_cny = ?, official_accessories = ?, score_1 = ?, score_2 = ?, score_3 = ?, score_4 = ?, score_5 = ?, score_6 = ?, score_avg = ?, personal_comment = ?, image_url = ? WHERE phone_id = ?")->execute([
                    !empty($_POST['height']) ? (float)$_POST['height'] : null, 
                    !empty($_POST['width']) ? (float)$_POST['width'] : null, 
                    !empty($_POST['thickness']) ? (float)$_POST['thickness'] : null, 
                    !empty($_POST['cam_thickness']) ? (float)$_POST['cam_thickness'] : null, 
                    $_POST['ip_rating'], 
                    $_POST['frame_material'] ?? '', 
                    $_POST['back_material'] ?? '', 
                    !empty($_POST['price_eur']) ? (float)$_POST['price_eur'] : null, 
                    !empty($_POST['price_cny']) ? (float)$_POST['price_cny'] : null, 
                    $_POST['accessories'] ?? '', 
                    $s1, $s2, $s3, $s4, $s5, $s6, $score_avg, $personal_comment, $image_url, 
                    $phone_id
                ]);

                $stmt_c = $conexion->prepare("SELECT id FROM phone_characteristics WHERE phone_id = ? LIMIT 1");
                $stmt_c->execute([$phone_id]);
                $char_row = $stmt_c->fetch();
                $char_id = $char_row ? $char_row['id'] : 0;

                if ($char_id) {
                    $conexion->prepare("UPDATE characteristics_soc SET brand = ?, full_name = ?, launch_year = ?, architecture_nm = ?, antutu_score = ?, geekbench_single = ?, geekbench_multi = ?, gpu_tflops = ?, npu_tops = ? WHERE phone_characteristic_id = ?")->execute([
                        $_POST['soc_brand'], $_POST['soc_name'], !empty($_POST['soc_year']) ? (int)$_POST['soc_year'] : null, !empty($_POST['soc_nm']) ? (int)$_POST['soc_nm'] : null, !empty($_POST['antutu']) ? (int)$_POST['antutu'] : null, !empty($_POST['gb_single']) ? (int)$_POST['gb_single'] : null, !empty($_POST['gb_multi']) ? (int)$_POST['gb_multi'] : null, !empty($_POST['tflops']) ? (float)$_POST['tflops'] : null, !empty($_POST['tops']) ? (float)$_POST['tops'] : null, $char_id
                    ]);

                    $conexion->prepare("UPDATE characteristics_storage SET ram_capacities_gb = ?, ram_technology = ?, storage_capacities_gb = ?, storage_technology = ? WHERE phone_characteristic_id = ?")->execute([$_POST['ram_cap'], $_POST['ram_tech'], $_POST['storage_cap'], $_POST['storage_tech'], $char_id]);

                    $conexion->prepare("UPDATE characteristics_screen SET inches = ?, resolution = ?, refresh_rate_hz = ?, max_brightness_nits = ?, technology = ?, resistance_certificate = ?, screen_to_body_ratio = ? WHERE phone_characteristic_id = ?")->execute([
                        !empty($_POST['inches']) ? (float)$_POST['inches'] : null, $_POST['resolution'], !empty($_POST['hz']) ? (int)$_POST['hz'] : null, !empty($_POST['nits']) ? (int)$_POST['nits'] : null, $_POST['screen_tech'], $_POST['screen_cert'], !empty($_POST['screen_ratio']) ? (float)$_POST['screen_ratio'] : null, $char_id
                    ]);

                    $conexion->prepare("UPDATE characteristics_battery SET capacity_mah = ?, wired_charging_w = ?, wireless_charging_w = ?, reverse_wired_charging_w = ?, reverse_wireless_charging_w = ?, assured_cycles_80_health = ? WHERE phone_characteristic_id = ?")->execute([
                        !empty($_POST['mah']) ? (int)$_POST['mah'] : null, !empty($_POST['wired_w']) ? (int)$_POST['wired_w'] : null, !empty($_POST['wireless_w']) ? (int)$_POST['wireless_w'] : null, !empty($_POST['rev_wired_w']) ? (int)$_POST['rev_wired_w'] : null, !empty($_POST['rev_wireless_w']) ? (int)$_POST['rev_wireless_w'] : null, !empty($_POST['cycles']) ? (int)$_POST['cycles'] : null, $char_id
                    ]);

                    $conexion->prepare("UPDATE characteristics_connectivity SET mobile_network = ?, wifi_version = ?, bluetooth_version = ?, nfc = ?, esim_support = ? WHERE phone_characteristic_id = ?")->execute([$_POST['network'], $_POST['wifi'], $_POST['bluetooth'], isset($_POST['nfc']) ? 1 : 0, isset($_POST['esim']) ? 1 : 0, $char_id]);

                    gestionarCamaras($conexion, $char_id, $_POST);
                }

                $conexion->commit();
                $mensaje_exito = "¡Móvil '$nombre' actualizado correctamente!";
            }
        }
    } catch (Exception $e) {
        if ($conexion->inTransaction()) {
            $conexion->rollBack();
        }
        $mensaje_error = 'Error en la operación: ' . $e->getMessage();
    }
}

// CAPTURAR FILTROS DEL BUSCADOR AVANZADO
$q = trim($_GET['q'] ?? '');
$f_so = trim($_GET['f_so'] ?? '');
$f_marca = trim($_GET['f_marca'] ?? '');
$f_familia = trim($_GET['f_familia'] ?? '');
$f_anio = trim($_GET['f_anio'] ?? '');
$f_skin = trim($_GET['f_skin'] ?? '');
$f_os_up = trim($_GET['f_os_up'] ?? '');
$f_sec_up = trim($_GET['f_sec_up'] ?? '');
$f_soc_brand = trim($_GET['f_soc_brand'] ?? '');
$f_soc_name = trim($_GET['f_soc_name'] ?? '');
$f_soc_nm = trim($_GET['f_soc_nm'] ?? '');
$f_antutu = trim($_GET['f_antutu'] ?? '');
$f_screen_size = trim($_GET['f_screen_size'] ?? '');
$f_hz = trim($_GET['f_hz'] ?? '');
$f_screen_tech = trim($_GET['f_screen_tech'] ?? '');
$f_mah = trim($_GET['f_mah'] ?? '');
$f_wired = trim($_GET['f_wired'] ?? '');
$f_wireless = trim($_GET['f_wireless'] ?? '');
$f_cam_mp = trim($_GET['f_cam_mp'] ?? '');
$f_cam_size = trim($_GET['f_cam_size'] ?? '');
$f_log = trim($_GET['f_log'] ?? '');
$f_opengate = trim($_GET['f_opengate'] ?? '');
$f_tier = trim($_GET['f_tier'] ?? '');
$f_ip = trim($_GET['f_ip'] ?? '');
$f_frame_material = trim($_GET['f_frame_material'] ?? '');
$f_back_material = trim($_GET['f_back_material'] ?? '');
$p_eur_min = trim($_GET['p_eur_min'] ?? '');
$p_eur_max = trim($_GET['p_eur_max'] ?? '');
$p_cny_min = trim($_GET['p_cny_min'] ?? '');
$p_cny_max = trim($_GET['p_cny_max'] ?? '');
$f_network = trim($_GET['f_network'] ?? '');
$f_wifi = trim($_GET['f_wifi'] ?? '');
$f_bt = trim($_GET['f_bt'] ?? '');
$f_nfc = trim($_GET['f_nfc'] ?? '');
$f_esim = trim($_GET['f_esim'] ?? '');

$error_precio = '';

try {
    $sql = "
        SELECT DISTINCT p.id, p.name AS movil, p.launch_year, p.tier, b.name AS marca, s.name AS familia
        FROM phones p
        INNER JOIN series s ON s.id = p.series_id
        INNER JOIN brands b ON b.id = s.brand_id
        LEFT JOIN operative_systems os ON os.id = b.operative_system_id
        LEFT JOIN software_support sw ON sw.phone_id = p.id
        LEFT JOIN phone_characteristics pc ON pc.phone_id = p.id
        LEFT JOIN characteristics_soc soc ON soc.phone_characteristic_id = pc.id
        LEFT JOIN characteristics_screen scr ON scr.phone_characteristic_id = pc.id
        LEFT JOIN characteristics_battery bat ON bat.phone_characteristic_id = pc.id
        LEFT JOIN characteristics_connectivity conn ON conn.phone_characteristic_id = pc.id
        LEFT JOIN camera_modules cam ON cam.phone_characteristic_id = pc.id
        LEFT JOIN phone_metadata meta ON meta.phone_id = p.id
        WHERE 1=1
    ";
    $params = [];

    if (!empty($q)) { $sql .= " AND p.name LIKE :q"; $params['q'] = "%$q%"; }
    if (!empty($f_so)) { $sql .= " AND os.name = :so"; $params['so'] = $f_so; }
    if (!empty($f_marca)) { $sql .= " AND b.name = :marca"; $params['marca'] = $f_marca; }
    if (!empty($f_familia)) { $sql .= " AND s.name = :familia"; $params['familia'] = $f_familia; }
    if (!empty($f_anio)) { $sql .= " AND p.launch_year = :anio"; $params['anio'] = (int)$f_anio; }
    if (!empty($f_skin)) { $sql .= " AND sw.initial_skin = :skin"; $params['skin'] = $f_skin; }
    if (!empty($f_os_up)) { $sql .= " AND sw.os_and_skin_update_years = :os_up"; $params['os_up'] = (int)$f_os_up; }
    if (!empty($f_sec_up)) { $sql .= " AND sw.security_update_years = :sec_up"; $params['sec_up'] = (int)$f_sec_up; }
    
    if (!empty($f_soc_brand)) { $sql .= " AND soc.brand = :soc_brand"; $params['soc_brand'] = $f_soc_brand; }
    if (!empty($f_soc_name)) { $sql .= " AND soc.full_name = :soc_name"; $params['soc_name'] = $f_soc_name; }
    if (!empty($f_soc_nm)) { $sql .= " AND soc.architecture_nm = :soc_nm"; $params['soc_nm'] = (int)$f_soc_nm; }
    if (!empty($f_antutu)) { $sql .= " AND soc.antutu_score >= :antutu"; $params['antutu'] = (int)$f_antutu; }

    if (!empty($f_screen_size)) {
        if ($f_screen_size === 'mini') { $sql .= " AND scr.inches < 6.0"; }
        elseif ($f_screen_size === 'pequeno') { $sql .= " AND scr.inches >= 6.0 AND scr.inches <= 6.4"; }
        elseif ($f_screen_size === 'medio') { $sql .= " AND scr.inches > 6.4 AND scr.inches <= 6.65"; }
        elseif ($f_screen_size === 'grande') { $sql .= " AND scr.inches > 6.65"; }
    }

    if (!empty($f_hz)) { $sql .= " AND scr.refresh_rate_hz = :hz"; $params['hz'] = (int)$f_hz; }
    if (!empty($f_screen_tech)) { $sql .= " AND scr.technology = :stech"; $params['stech'] = $f_screen_tech; }
    if (!empty($f_mah)) { $sql .= " AND bat.capacity_mah >= :mah"; $params['mah'] = (int)$f_mah; }
    if (!empty($f_wired)) { $sql .= " AND bat.wired_charging_w = :wired"; $params['wired'] = (int)$f_wired; }
    
    if ($f_wireless !== '') {
        if ($f_wireless === '1') { $sql .= " AND bat.wireless_charging_w > 0"; }
        else { $sql .= " AND (bat.wireless_charging_w IS NULL OR bat.wireless_charging_w = 0)"; }
    }

    if (!empty($f_cam_mp)) {
        $sql .= " AND cam.camera_type = 'Principal' AND cam.resolution_mp = :cmp";
        $params['cmp'] = (float)$f_cam_mp;
    }
    if (!empty($f_cam_size)) {
        $sql .= " AND cam.camera_type = 'Principal' AND cam.sensor_size = :csize";
        $params['csize'] = $f_cam_size;
    }
    if (!empty($f_log)) { $sql .= " AND cam.has_log = 1"; }
    if (!empty($f_opengate)) { $sql .= " AND cam.has_opengate = 1"; }

    if (!empty($f_tier)) { $sql .= " AND p.tier = :tier"; $params['tier'] = $f_tier; }
    if (!empty($f_ip)) { $sql .= " AND meta.ip_rating = :ip"; $params['ip'] = $f_ip; }
    if (!empty($f_frame_material)) { $sql .= " AND meta.frame_material = :fmat"; $params['fmat'] = $f_frame_material; }
    if (!empty($f_back_material)) { $sql .= " AND meta.back_material = :bmat"; $params['bmat'] = $f_back_material; }

    if ((is_numeric($p_eur_min) && is_numeric($p_eur_max)) && ($p_eur_min > $p_eur_max)) {
        $error_precio = "El precio mínimo en Euros no puede ser mayor que el máximo.";
    } elseif (!empty($p_eur_min) || !empty($p_eur_max)) {
        if (!empty($p_eur_min)) { $sql .= " AND meta.price_eur >= :p_eur_min"; $params['p_eur_min'] = (float)$p_eur_min; }
        if (!empty($p_eur_max)) { $sql .= " AND meta.price_eur <= :p_eur_max"; $params['p_eur_max'] = (float)$p_eur_max; }
    }

    if ((is_numeric($p_cny_min) && is_numeric($p_cny_max)) && ($p_cny_min > $p_cny_max)) {
        $error_precio = "El precio mínimo en Yuanes no puede ser mayor que el máximo.";
    } elseif (!empty($p_cny_min) || !empty($p_cny_max)) {
        if (!empty($p_cny_min)) { $sql .= " AND meta.price_cny >= :p_cny_min"; $params['p_cny_min'] = (float)$p_cny_min; }
        if (!empty($p_cny_max)) { $sql .= " AND meta.price_cny <= :p_cny_max"; $params['p_cny_max'] = (float)$p_cny_max; }
    }

    if (!empty($f_network)) { $sql .= " AND conn.mobile_network LIKE :net"; $params['net'] = "%$f_network%"; }
    if (!empty($f_wifi)) { $sql .= " AND conn.wifi_version = :wifi"; $params['wifi'] = $f_wifi; }
    if (!empty($f_bt)) { $sql .= " AND conn.bluetooth_version = :bt"; $params['bt'] = $f_bt; }
    if ($f_nfc !== '') { $sql .= " AND conn.nfc = :nfc"; $params['nfc'] = (int)$f_nfc; }
    if ($f_esim !== '') { $sql .= " AND conn.esim_support = :esim"; $params['esim'] = (int)$f_esim; }

    $sql .= " ORDER BY b.name ASC, p.name ASC";
    $stmt = $conexion->prepare($sql);
    $stmt->execute($params);
    $moviles = $stmt->fetchAll();

    $list_so = $conexion->query("SELECT DISTINCT name FROM operative_systems ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
    $list_marcas = $conexion->query("SELECT DISTINCT name FROM brands ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
    $list_familias = $conexion->query("SELECT DISTINCT name FROM series ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
    $list_anios = $conexion->query("SELECT DISTINCT launch_year FROM phones WHERE launch_year IS NOT NULL ORDER BY launch_year DESC")->fetchAll(PDO::FETCH_COLUMN);
    $list_skins = $conexion->query("SELECT DISTINCT initial_skin FROM software_support WHERE initial_skin IS NOT NULL ORDER BY initial_skin")->fetchAll(PDO::FETCH_COLUMN);
    $list_soc_brands = $conexion->query("SELECT DISTINCT brand FROM characteristics_soc WHERE brand IS NOT NULL ORDER BY brand")->fetchAll(PDO::FETCH_COLUMN);
    $list_soc_names = $conexion->query("SELECT DISTINCT full_name FROM characteristics_soc WHERE full_name IS NOT NULL AND full_name != '' ORDER BY full_name")->fetchAll(PDO::FETCH_COLUMN);
    $list_hz = $conexion->query("SELECT DISTINCT refresh_rate_hz FROM characteristics_screen WHERE refresh_rate_hz IS NOT NULL ORDER BY refresh_rate_hz")->fetchAll(PDO::FETCH_COLUMN);
    $list_screen_tech = $conexion->query("SELECT DISTINCT technology FROM characteristics_screen WHERE technology IS NOT NULL ORDER BY technology")->fetchAll(PDO::FETCH_COLUMN);
    $list_wired = $conexion->query("SELECT DISTINCT wired_charging_w FROM characteristics_battery WHERE wired_charging_w IS NOT NULL ORDER BY wired_charging_w")->fetchAll(PDO::FETCH_COLUMN);
    $list_wifi = $conexion->query("SELECT DISTINCT wifi_version FROM characteristics_connectivity WHERE wifi_version IS NOT NULL ORDER BY wifi_version")->fetchAll(PDO::FETCH_COLUMN);
    $list_bt = $conexion->query("SELECT DISTINCT bluetooth_version FROM characteristics_connectivity WHERE bluetooth_version IS NOT NULL ORDER BY bluetooth_version")->fetchAll(PDO::FETCH_COLUMN);
    $list_tiers = $conexion->query("SELECT DISTINCT tier FROM phones WHERE tier IS NOT NULL ORDER BY tier")->fetchAll(PDO::FETCH_COLUMN);
    $list_ips = $conexion->query("SELECT DISTINCT ip_rating FROM phone_metadata WHERE ip_rating IS NOT NULL ORDER BY ip_rating")->fetchAll(PDO::FETCH_COLUMN);
    $list_frame_mat = $conexion->query("SELECT DISTINCT frame_material FROM phone_metadata WHERE frame_material IS NOT NULL AND frame_material != '' ORDER BY frame_material")->fetchAll(PDO::FETCH_COLUMN);
    $list_back_mat = $conexion->query("SELECT DISTINCT back_material FROM phone_metadata WHERE back_material IS NOT NULL AND back_material != '' ORDER BY back_material")->fetchAll(PDO::FETCH_COLUMN);

    $todos_para_editar = $conexion->query("SELECT p.id, p.name, b.name AS marca FROM phones p INNER JOIN series s ON s.id = p.series_id INNER JOIN brands b ON b.id = s.brand_id ORDER BY b.name, p.name")->fetchAll();
} catch (PDOException $e) {
    die('Error al cargar datos: ' . $e->getMessage());
}

function escapar($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function val_edit($campo) { global $edit_data; return isset($edit_data[$campo]) ? escapar($edit_data[$campo]) : ''; }
function val_del($campo) { global $delete_data; return isset($delete_data[$campo]) ? escapar($delete_data[$campo]) : ''; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo de Smartphones · Estilo Kimovil</title>
    <link rel="stylesheet" href="css/estilos.css">
    <style>
        .categoria-filtro { background: #f8fafc; border: 1px solid #e0e5ed; border-radius: 8px; margin-bottom: 10px; padding: 12px 15px; }
        .categoria-filtro summary { font-weight: 700; color: #18263b; cursor: pointer; }
        .categoria-contenido { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; margin-top: 12px; padding-top: 10px; border-top: 1px dashed #cbd5e1; }
        .admin-box { background: #fffdfd; border: 2px dashed #e74c3c; padding: 25px; border-radius: 12px; margin-top: 25px; }
        
        /* Estilos específicos para modo borrado en rojo */
        .admin-box.modo-borrado { border: 2px dashed #c0392b; background: #fff5f5; }
        .modo-borrado .categoria-filtro { background: #fde8e8; border-color: #f8b4b4; }
        .modo-borrado summary { color: #9b2c2c; }
        .modo-borrado input, .modo-borrado select { background-color: #fff5f5; color: #7f1d1d; border-color: #f56565; }

        label { display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 4px; color: #334155; }
        select, input[type="text"], input[type="number"] { width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem; }
        .btn-admin-modo { display: inline-block; padding: 8px 16px; margin-right: 10px; border-radius: 6px; font-weight: bold; text-decoration: none; font-size: 0.9rem; }
        .btn-crear { background: <?= $modo_admin === 'crear' ? '#27ae60' : '#95a5a6' ?>; color: white; }
        .btn-editar { background: <?= $modo_admin === 'editar' ? '#e67e22' : '#95a5a6' ?>; color: white; }
        .btn-borrar-modo { background: <?= $modo_admin === 'borrar' ? '#c0392b' : '#95a5a6' ?>; color: white; }
        .aux-camara-box { background: #f1f5f9; border: 1px solid #cbd5e1; padding: 12px; border-radius: 6px; margin-bottom: 10px; position: relative; }
    </style>
</head>
<body>

    <datalist id="datalist_marcas"><?php foreach ($list_marcas as $m): ?><option value="<?= escapar($m) ?>"><?php endforeach; ?></datalist>
    <datalist id="datalist_familias"><?php foreach ($list_familias as $f): ?><option value="<?= escapar($f) ?>"><?php endforeach; ?></datalist>
    <datalist id="datalist_so"><?php foreach ($list_so as $so): ?><option value="<?= escapar($so) ?>"><?php endforeach; ?></datalist>
    <datalist id="datalist_skins"><?php foreach ($list_skins as $sk): ?><option value="<?= escapar($sk) ?>"><?php endforeach; ?></datalist>
    <datalist id="datalist_soc_brands"><?php foreach ($list_soc_brands as $sb): ?><option value="<?= escapar($sb) ?>"><?php endforeach; ?></datalist>
    <datalist id="datalist_soc_names"><?php foreach ($list_soc_names as $sn): ?><option value="<?= escapar($sn) ?>"><?php endforeach; ?></datalist>
    <datalist id="datalist_screen_tech"><?php foreach ($list_screen_tech as $st): ?><option value="<?= escapar($st) ?>"><?php endforeach; ?></datalist>
    <datalist id="datalist_wifi"><?php foreach ($list_wifi as $wf): ?><option value="<?= escapar($wf) ?>"><?php endforeach; ?></datalist>
    <datalist id="datalist_bt"><?php foreach ($list_bt as $bt): ?><option value="<?= escapar($bt) ?>"><?php endforeach; ?></datalist>
    <datalist id="datalist_ips"><?php foreach ($list_ips as $ip): ?><option value="<?= escapar($ip) ?>"><?php endforeach; ?></datalist>
    <datalist id="datalist_frame_mat"><option value="Plástico"><option value="Metal"><option value="Aleación de titanio"><option value="Aluminio"></datalist>
    <datalist id="datalist_back_mat"><option value="Plástico"><option value="Cuero sintético / vegano"><option value="Vidrio"><option value="Cerámica"><option value="Otro"></datalist>

    <header class="cabecera">
        <div class="contenedor">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <p class="sobrelinea" style="margin: 0;">CATÁLOGO TECNOLÓGICO ESTILO KIMOVIL</p>
                <div>
                    <?php if ($sesion_iniciada): ?>
                        <span style="color: #a9c5ff; margin-right: 15px; font-size: 0.9rem;">Hola, <strong><?= escapar($nombre_usuario) ?></strong>
                            <?php if ($es_admin): ?><span style="background: #e74c3c; color: white; padding: 2px 6px; border-radius: 4px; font-size: 0.75rem; margin-left: 5px;">ADMIN</span><?php endif; ?>
                        </span>
                        <a href="logout.php" style="color: #fff; text-decoration: none; font-size: 0.85rem; background: rgba(255,255,255,0.15); padding: 5px 10px; border-radius: 4px;">Cerrar sesión</a>
                    <?php else: ?>
                        <a href="login.php" style="color: #fff; text-decoration: none; font-size: 0.85rem; background: rgba(255,255,255,0.15); padding: 5px 10px; border-radius: 4px;">Iniciar sesión</a>
                        <a href="register.php" style="color: #18263b; text-decoration: none; font-size: 0.85rem; background: #a9c5ff; padding: 5px 10px; border-radius: 4px; font-weight: bold;">Registrarse</a>
                    <?php endif; ?>
                </div>
            </div>
            <h1>Biblioteca de smartphones</h1>
            <p class="subtitulo">Especificaciones técnicas avanzadas y comparativa inteligente</p>
        </div>
    </header>

    <main class="contenedor">

        <?php if ($es_admin): ?>
            <section class="admin-box <?= $modo_admin === 'borrar' ? 'modo-borrado' : '' ?>">
                <h2 style="margin-top: 0; color: <?= $modo_admin === 'borrar' ? '#c0392b' : '#c0392b' ?>; margin-bottom: 10px;">
                    <?= $modo_admin === 'borrar' ? '🗑️ Panel Admin: Modo Borrado de Registros' : '👑 Panel Admin: Registro Técnico Completo' ?>
                </h2>
                
                <!-- BOTONES DE MODO: CREAR / EDITAR / BORRAR -->
                <div style="margin-bottom: 20px;">
                    <a href="index.php?modo=crear" class="btn-admin-modo btn-crear">➕ Crear</a>
                    <a href="index.php?modo=editar" class="btn-admin-modo btn-editar">✏️ Editar</a>
                    <a href="index.php?modo=borrar" class="btn-admin-modo btn-borrar-modo">🗑️ Borrar Móvil</a>
                </div>

                <?php if ($modo_admin === 'editar'): ?>
                    <div style="background: #fdfefe; border: 1px solid #d5dbdb; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                        <form method="GET" action="index.php">
                            <input type="hidden" name="modo" value="editar">
                            <label style="font-weight: bold; color: #e67e22;">Selecciona un móvil existente para editar:</label>
                            <div style="display: flex; gap: 10px; margin-top: 5px;">
                                <select name="editar_id" required style="flex: 2;">
                                    <option value="">-- Elige un terminal --</option>
                                    <?php foreach ($todos_para_editar as $te): ?>
                                        <option value="<?= $te['id'] ?>" <?= $editar_id == $te['id'] ? 'selected' : '' ?>>
                                            <?= escapar($te['marca'] . ' - ' . $te['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" style="flex: 1; background: #e67e22; color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer;">Cargar datos</button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>

                <?php if ($modo_admin === 'borrar'): ?>
                    <div style="background: #fde8e8; border: 1px solid #f56565; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                        <form method="GET" action="index.php">
                            <input type="hidden" name="modo" value="borrar">
                            <label style="font-weight: bold; color: #c0392b;">Selecciona el móvil que deseas ELIMINAR:</label>
                            <div style="display: flex; gap: 10px; margin-top: 5px;">
                                <select name="borrar_id" required style="flex: 2; border-color: #f56565; background: #fff5f5;">
                                    <option value="">-- Elige terminal a borrar --</option>
                                    <?php foreach ($todos_para_editar as $te): ?>
                                        <option value="<?= $te['id'] ?>" <?= $borrar_id == $te['id'] ? 'selected' : '' ?>>
                                            <?= escapar($te['marca'] . ' - ' . $te['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" style="flex: 1; background: #c0392b; color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer;">Cargar datos para verificar</button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>

                <?php if ($mensaje_exito): ?><div style="background: #d4edda; color: #155724; padding: 10px; border-radius: 6px; margin-bottom: 15px;"><?= escapar($mensaje_exito) ?></div><?php endif; ?>
                <?php if ($mensaje_error): ?><div style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 6px; margin-bottom: 15px;"><?= escapar($mensaje_error) ?></div><?php endif; ?>

                <form method="POST" action="index.php?modo=<?= $modo_admin ?>&editar_id=<?= $editar_id ?>&borrar_id=<?= $borrar_id ?>">
                    <input type="hidden" name="accion" value="<?= $modo_admin === 'borrar' ? 'borrar_movil_completo' : (($modo_admin === 'editar' && $editar_id > 0) ? 'actualizar_movil_completo' : 'crear_movil_completo') ?>">
                    
                    <?php if ($modo_admin === 'editar' && $editar_id > 0): ?>
                        <input type="hidden" name="phone_id" value="<?= $editar_id ?>">
                    <?php endif; ?>
                    <?php if ($modo_admin === 'borrar' && $borrar_id > 0): ?>
                        <input type="hidden" name="phone_id" value="<?= $borrar_id ?>">
                    <?php endif; ?>

                    <details class="categoria-filtro">
                        <summary>1. Identidad y Clasificación</summary>
                        <div class="categoria-contenido">
                            <div><label>Marca:</label><input type="text" name="marca_nombre" list="datalist_marcas" required <?= $modo_admin==='borrar'?'readonly':'' ?> value="<?= $modo_admin==='borrar' ? val_del('marca_nombre') : val_edit('marca_nombre') ?>"></div>
                            <div><label>Familia / Serie:</label><input type="text" name="familia_nombre" list="datalist_familias" required <?= $modo_admin==='borrar'?'readonly':'' ?> value="<?= $modo_admin==='borrar' ? val_del('familia_nombre') : val_edit('familia_nombre') ?>"></div>
                            <div><label>Nombre del Modelo:</label><input type="text" name="nombre" required <?= $modo_admin==='borrar'?'readonly':'' ?> value="<?= $modo_admin==='borrar' ? val_del('name') : val_edit('name') ?>"></div>
                            <div><label>Año de Lanzamiento:</label><input type="number" name="launch_year" <?= $modo_admin==='borrar'?'readonly':'' ?> value="<?= $modo_admin==='borrar' ? val_del('launch_year') : (val_edit('launch_year') ?: 2026) ?>"></div>
                            
                            <?php 
                            $val_tier = $modo_admin === 'borrar' ? val_del('tier') : val_edit('tier');
                            $es_borrar = $modo_admin === 'borrar';
                            ?>
                            <div>
                                <label>Gama (Tier):</label>
                                <select name="tier" <?= $es_borrar ? 'disabled' : '' ?>>
                                    <option value="Entrada" <?= $val_tier === 'Entrada' ? 'selected' : '' ?>>Entrada</option>
                                    <option value="Baja" <?= $val_tier === 'Baja' ? 'selected' : '' ?>>Baja</option>
                                    <option value="Media" <?= $val_tier === 'Media' ? 'selected' : '' ?>>Media</option>
                                    <option value="Alta" <?= $val_tier === 'Alta' ? 'selected' : '' ?>>Alta</option>
                                    <option value="Premium" <?= $val_tier === 'Premium' ? 'selected' : '' ?>>Premium</option>
                                </select>
                                <?php if ($es_borrar): ?>
                                    <input type="hidden" name="tier" value="<?= $val_tier ?>">
                                <?php endif; ?>
                            </div>
                        </div>
                    </details>

                    <details class="categoria-filtro">
                        <summary>2. Software y Soporte</summary>
                        <div class="categoria-contenido">
                            <div><label>Sistema Operativo Inicial:</label><input type="text" name="initial_os" <?= $modo_admin==='borrar'?'readonly':'' ?> value="<?= $modo_admin==='borrar' ? val_del('initial_os') : val_edit('initial_os') ?>"></div>
                            <div><label>Capa de Personalización:</label><input type="text" name="initial_skin" <?= $modo_admin==='borrar'?'readonly':'' ?> value="<?= $modo_admin==='borrar' ? val_del('initial_skin') : val_edit('initial_skin') ?>"></div>
                            <div><label>Años Act. Sistema / Capa:</label><input type="number" name="os_updates" <?= $modo_admin==='borrar'?'readonly':'' ?> value="<?= $modo_admin==='borrar' ? val_del('os_and_skin_update_years') : (val_edit('os_and_skin_update_years') ?: 4) ?>"></div>
                            <div><label>Años Parches de Seguridad:</label><input type="number" name="sec_updates" <?= $modo_admin==='borrar'?'readonly':'' ?> value="<?= $modo_admin==='borrar' ? val_del('security_update_years') : (val_edit('security_update_years') ?: 5) ?>"></div>
                        </div>
                    </details>

                    <details class="categoria-filtro">
                        <summary>3. Procesador (SoC) y Rendimiento</summary>
                        <div class="categoria-contenido">
                            <div><label>Marca del Procesador:</label><input type="text" name="soc_brand" <?= $modo_admin==='borrar'?'readonly':'' ?> value="<?= $modo_admin==='borrar' ? val_del('brand') : val_edit('brand') ?>"></div>
                            <div><label>Nombre Completo SoC:</label><input type="text" name="soc_name" <?= $modo_admin==='borrar'?'readonly':'' ?> value="<?= $modo_admin==='borrar' ? val_del('full_name') : val_edit('full_name') ?>"></div>
                            <div><label>Año SoC:</label><input type="number" name="soc_year" <?= $modo_admin==='borrar'?'readonly':'' ?> value="<?= $modo_admin==='borrar' ? val_del('launch_year') : val_edit('launch_year') ?>"></div>
                            <div><label>Arquitectura (nm):</label><input type="number" name="soc_nm" <?= $modo_admin==='borrar'?'readonly':'' ?> value="<?= $modo_admin==='borrar' ? val_del('architecture_nm') : val_edit('architecture_nm') ?>"></div>
                            <div><label>Puntuación AnTuTu:</label><input type="number" name="antutu" <?= $modo_admin==='borrar'?'readonly':'' ?> value="<?= $modo_admin==='borrar' ? val_del('antutu_score') : val_edit('antutu_score') ?>"></div>
                            <div><label>Geekbench Mononúcleo:</label><input type="number" name="gb_single" <?= $modo_admin==='borrar'?'readonly':'' ?> value="<?= $modo_admin==='borrar' ? val_del('geekbench_single') : val_edit('geekbench_single') ?>"></div>
                            <div><label>Geekbench Multinúcleo:</label><input type="number" name="gb_multi" <?= $modo_admin==='borrar'?'readonly':'' ?> value="<?= $modo_admin==='borrar' ? val_del('geekbench_multi') : val_edit('geekbench_multi') ?>"></div>
                            <div><label>TFLOPS GPU:</label><input type="number" step="0.01" name="tflops" <?= $modo_admin==='borrar'?'readonly':'' ?> value="<?= $modo_admin==='borrar' ? val_del('gpu_tflops') : val_edit('gpu_tflops') ?>"></div>
                            <div><label>TOPS NPU (IA):</label><input type="number" step="0.01" name="tops" <?= $modo_admin==='borrar'?'readonly':'' ?> value="<?= $modo_admin==='borrar' ? val_del('npu_tops') : val_edit('npu_tops') ?>"></div>
                        </div>
                    </details>

                    <details class="categoria-filtro">
                        <summary>4. Almacenamiento y Memoria RAM</summary>
                        <div class="categoria-contenido">
                            <div><label>Cantidades RAM (GB):</label><input type="text" name="ram_cap" <?= $modo_admin==='borrar'?'readonly':'' ?> value="<?= $modo_admin==='borrar' ? val_del('ram_capacities_gb') : val_edit('ram_capacities_gb') ?>"></div>
                            <div><label>Tecnología RAM:</label><input type="text" name="ram_tech" <?= $modo_admin==='borrar'?'readonly':'' ?> value="<?= $modo_admin==='borrar' ? val_del('ram_technology') : val_edit('ram_technology') ?>"></div>
                            <div><label>Cantidades Almacenamiento:</label><input type="text" name="storage_cap" <?= $modo_admin==='borrar'?'readonly':'' ?> value="<?= $modo_admin==='borrar' ? val_del('storage_capacities_gb') : val_edit('storage_capacities_gb') ?>"></div>
                            <div><label>Tecnología Almacenamiento:</label><input type="text" name="storage_tech" <?= $modo_admin==='borrar'?'readonly':'' ?> value="<?= $modo_admin==='borrar' ? val_del('storage_technology') : val_edit('storage_technology') ?>"></div>
                        </div>
                    </details>

                    <details class="categoria-filtro">
                        <summary>5. Pantalla y Resistencia</summary>
                        <div class="categoria-contenido">
                            <div><label>Pulgadas:</label><input type="number" step="0.01" name="inches" <?= $modo_admin==='borrar'?'readonly':'' ?> value="<?= $modo_admin==='borrar' ? val_del('inches') : val_edit('inches') ?>"></div>
                            <div><label>Resolución:</label><input type="text" name="resolution" <?= $modo_admin==='borrar'?'readonly':'' ?> value="<?= $modo_admin==='borrar' ? val_del('resolution') : val_edit('resolution') ?>"></div>
                            <div><label>Tasa de Refresco (Hz):</label><input type="number" name="hz" <?= $modo_admin==='borrar'?'readonly':'' ?> value="<?= $modo_admin==='borrar' ? val_del('refresh_rate_hz') : (val_edit('refresh_rate_hz') ?: 120) ?>"></div>
                            <div><label>Brillo Máximo (Nits):</label><input type="number" name="nits" <?= $modo_admin==='borrar'?'readonly':'' ?> value="<?= $modo_admin==='borrar' ? val_del('max_brightness_nits') : val_edit('max_brightness_nits') ?>"></div>
                            <div><label>Tecnología de Pantalla:</label><input type="text" name="screen_tech" <?= $modo_admin==='borrar'?'readonly':'' ?> value="<?= $modo_admin==='borrar' ? val_del('technology') : val_edit('technology') ?>"></div>
                            <div><label>Certificado Resistencia:</label><input type="text" name="screen_cert" <?= $modo_admin==='borrar'?'readonly':'' ?> value="<?= $modo_admin==='borrar' ? val_del('resistance_certificate') : val_edit('resistance_certificate') ?>"></div>
                            <div><label>% Superficie Frontal Útil:</label><input type="number" step="0.1" name="screen_ratio" <?= $modo_admin==='borrar'?'readonly':'' ?> value="<?= $modo_admin==='borrar' ? val_del('screen_to_body_ratio') : val_edit('screen_to_body_ratio') ?>"></div>
                        </div>
                    </details>

                    <details class="categoria-filtro">
                        <summary>6. Batería y Tiempos de Carga</summary>
                        <div class="categoria-contenido">
                            <div><label>Capacidad (mAh):</label><input type="number" name="mah" <?= $modo_admin==='borrar'?'readonly':'' ?> value="<?= $modo_admin==='borrar' ? val_del('capacity_mah') : val_edit('capacity_mah') ?>"></div>
                            <div><label>Carga Cable (W):</label><input type="number" name="wired_w" <?= $modo_admin==='borrar'?'readonly':'' ?> value="<?= $modo_admin==='borrar' ? val_del('wired_charging_w') : val_edit('wired_charging_w') ?>"></div>
                            <div><label>Carga Inalámbrica (W):</label><input type="number" name="wireless_w" <?= $modo_admin==='borrar'?'readonly':'' ?> value="<?= $modo_admin==='borrar' ? val_del('wireless_charging_w') : val_edit('wireless_charging_w') ?>"></div>
                            <div><label>Carga Inversa Cable (W):</label><input type="number" name="rev_wired_w" <?= $modo_admin==='borrar'?'readonly':'' ?> value="<?= $modo_admin==='borrar' ? val_del('reverse_wired_charging_w') : val_edit('reverse_wired_charging_w') ?>"></div>
                            <div><label>Carga Inversa Inalámbrica (W):</label><input type="number" name="rev_wireless_w" <?= $modo_admin==='borrar'?'readonly':'' ?> value="<?= $modo_admin==='borrar' ? val_del('reverse_wireless_charging_w') : val_edit('reverse_wireless_charging_w') ?>"></div>
                            <div><label>Ciclos Salud 80%:</label><input type="number" name="cycles" <?= $modo_admin==='borrar'?'readonly':'' ?> value="<?= $modo_admin==='borrar' ? val_del('assured_cycles_80_health') : val_edit('assured_cycles_80_health') ?>"></div>
                        </div>
                    </details>

                    <details class="categoria-filtro">
                        <summary>7. Sistema de Cámaras (Principal + Auxiliares)</summary>
                        <div style="margin-top: 15px;">
                            <h3 style="color: #2c3e50; font-size: 1rem; border-bottom: 2px solid #3498db; padding-bottom: 5px;">📷 Cámara Principal</h3>
                            <div class="categoria-contenido" style="background: #eef2f7; padding: 12px; border-radius: 6px; margin-bottom: 15px;">
                                <div><label>Nombre del Sensor:</label><input type="text" name="main_sensor_name" <?= $modo_admin==='borrar'?'readonly':'' ?> value="<?= $modo_admin==='borrar' ? ($del_main_cam ? escapar($del_main_cam['sensor_name']) : '') : ($main_cam ? escapar($main_cam['sensor_name']) : '') ?>"></div>
                                <div><label>Tamaño Sensor:</label><input type="text" name="main_sensor_size" <?= $modo_admin==='borrar'?'readonly':'' ?> value="<?= $modo_admin==='borrar' ? ($del_main_cam ? escapar($del_main_cam['sensor_size']) : '') : ($main_cam ? escapar($main_cam['sensor_size']) : '') ?>"></div>
                                <div><label>Megapíxeles (MP):</label><input type="number" step="0.01" name="main_resolution_mp" <?= $modo_admin==='borrar'?'readonly':'' ?> value="<?= $modo_admin==='borrar' ? ($del_main_cam ? escapar($del_main_cam['resolution_mp']) : '') : ($main_cam ? escapar($main_cam['resolution_mp']) : '') ?>"></div>
                                <div><label>Apertura Focal:</label><input type="text" name="main_aperture" <?= $modo_admin==='borrar'?'readonly':'' ?> value="<?= $modo_admin==='borrar' ? ($del_main_cam ? escapar($del_main_cam['aperture']) : '') : ($main_cam ? escapar($main_cam['aperture']) : '') ?>"></div>
                                <div><label>Focal Equivalente (mm):</label><input type="number" name="main_focal_length_mm" <?= $modo_admin==='borrar'?'readonly':'' ?> value="<?= $modo_admin==='borrar' ? ($del_main_cam ? escapar($del_main_cam['focal_length_mm']) : '') : ($main_cam ? escapar($main_cam['focal_length_mm']) : '') ?>"></div>
                                <div><label>Vídeo Normal Máx:</label><input type="text" name="main_normal_video_max" <?= $modo_admin==='borrar'?'readonly':'' ?> value="<?= $modo_admin==='borrar' ? ($del_main_cam ? escapar($del_main_cam['normal_video_max']) : '') : ($main_cam ? escapar($main_cam['normal_video_max']) : '') ?>"></div>
                            </div>

                            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px; border-bottom: 2px solid #e67e22; padding-bottom: 5px;">
                                <h3 style="color: #2c3e50; font-size: 1rem; margin: 0;">📸 Cámaras Auxiliares</h3>
                                <?php if ($modo_admin !== 'borrar'): ?>
                                    <button type="button" onclick="agregarCamaraAuxiliar()" style="background: #3498db; color: white; border: none; padding: 5px 12px; border-radius: 4px; font-weight: bold; cursor: pointer; font-size: 0.85rem;">➕ Añadir Cámara Auxiliar</button>
                                <?php endif; ?>
                            </div>

                            <div id="contenedor-aux-cams" style="margin-top: 10px;">
                                <?php 
                                $cams_a_mostrar = $modo_admin === 'borrar' ? $del_aux_cams : $aux_cams;
                                if (!empty($cams_a_mostrar)): ?>
                                    <?php foreach ($cams_a_mostrar as $ac): ?>
                                        <div class="aux-camara-box">
                                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                                <strong>Sensor Auxiliar</strong>
                                                <?php if ($modo_admin !== 'borrar'): ?>
                                                    <button type="button" onclick="this.parentElement.parentElement.remove()" style="background: #e74c3c; color: white; border: none; padding: 3px 8px; border-radius: 4px; cursor: pointer; font-size: 0.8rem;">Eliminar</button>
                                                <?php endif; ?>
                                            </div>
                                            <div class="categoria-contenido" style="margin-top: 0; padding-top: 0; border: none;">
                                                <div>
                                                    <label>Tipo de Cámara:</label>
                                                    <select name="aux_type[]" <?= $modo_admin==='borrar'?'disabled':'' ?>>
                                                        <?php 
                                                        $tipos_cam = ['Ultra gran angular', 'Teleobjetivo telescópico', 'Teleobjetivo periscópico', 'Retrato', 'Macro', 'Multiespectral', 'Monocromo', 'Profundidad'];
                                                        foreach($tipos_cam as $tc): 
                                                        ?>
                                                            <option value="<?= $tc ?>" <?= $ac['camera_type'] === $tc ? 'selected' : '' ?>><?= $tc ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div><label>Nombre Sensor:</label><input type="text" name="aux_sensor_name[]" <?= $modo_admin==='borrar'?'readonly':'' ?> value="<?= escapar($ac['sensor_name']) ?>"></div>
                                                <div><label>Tamaño Sensor:</label><input type="text" name="aux_sensor_size[]" <?= $modo_admin==='borrar'?'readonly':'' ?> value="<?= escapar($ac['sensor_size']) ?>"></div>
                                                <div><label>Megapíxeles (MP):</label><input type="number" step="0.01" name="aux_resolution_mp[]" <?= $modo_admin==='borrar'?'readonly':'' ?> value="<?= escapar($ac['resolution_mp']) ?>"></div>
                                                <div><label>Apertura:</label><input type="text" name="aux_aperture[]" <?= $modo_admin==='borrar'?'readonly':'' ?> value="<?= escapar($ac['aperture']) ?>"></div>
                                                <div><label>Focal (mm):</label><input type="number" name="aux_focal_length_mm[]" <?= $modo_admin==='borrar'?'readonly':'' ?> value="<?= escapar($ac['focal_length_mm']) ?>"></div>
                                                <div><label>Vídeo Normal Máx:</label><input type="text" name="aux_normal_video_max[]" <?= $modo_admin==='borrar'?'readonly':'' ?> value="<?= escapar($ac['normal_video_max']) ?>"></div>
                                                <div>
                                                    <label><input type="checkbox" name="aux_has_log[]" value="1" <?= $ac['has_log'] ? 'checked' : '' ?> <?= $modo_admin==='borrar'?'disabled':'' ?>> LOG</label>
                                                    <input type="text" name="aux_log_specs[]" <?= $modo_admin==='borrar'?'readonly':'' ?> value="<?= escapar($ac['log_specs']) ?>">
                                                </div>
                                                <div>
                                                    <label><input type="checkbox" name="aux_has_opengate[]" value="1" <?= $ac['has_opengate'] ? 'checked' : '' ?> <?= $modo_admin==='borrar'?'disabled':'' ?>> OpenGate</label>
                                                    <input type="text" name="aux_opengate_specs[]" <?= $modo_admin==='borrar'?'readonly':'' ?> value="<?= escapar($ac['opengate_specs']) ?>">
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </details>

                    <details class="categoria-filtro">
                        <summary>8. Gama, Dimensiones y Construcción</summary>
                        <div class="categoria-contenido">
                            <div><label>Alto (mm):</label><input type="number" step="0.01" name="height" <?= $modo_admin==='borrar'?'readonly':'' ?> value="<?= $modo_admin==='borrar' ? val_del('height_mm') : val_edit('height_mm') ?>"></div>
                            <div><label>Ancho (mm):</label><input type="number" step="0.01" name="width" <?= $modo_admin==='borrar'?'readonly':'' ?> value="<?= $modo_admin==='borrar' ? val_del('width_mm') : val_edit('width_mm') ?>"></div>
                            <div><label>Grosor Cuerpo (mm):</label><input type="number" step="0.01" name="thickness" <?= $modo_admin==='borrar'?'readonly':'' ?> value="<?= $modo_admin==='borrar' ? val_del('thickness_body_mm') : val_edit('thickness_body_mm') ?>"></div>
                            <div><label>Material Marco:</label><input type="text" name="frame_material" <?= $modo_admin==='borrar'?'readonly':'' ?> value="<?= $modo_admin==='borrar' ? val_del('frame_material') : val_edit('frame_material') ?>"></div>
                            <div><label>Material Trasera:</label><input type="text" name="back_material" <?= $modo_admin==='borrar'?'readonly':'' ?> value="<?= $modo_admin==='borrar' ? val_del('back_material') : val_edit('back_material') ?>"></div>
                            <div><label>Protección IP:</label><input type="text" name="ip_rating" <?= $modo_admin==='borrar'?'readonly':'' ?> value="<?= $modo_admin==='borrar' ? val_del('ip_rating') : val_edit('ip_rating') ?>"></div>
                        </div>
                    </details>

                    <details class="categoria-filtro">
                        <summary>9. Precios de Lanzamiento y Accesorios</summary>
                        <div class="categoria-contenido">
                            <div><label>Precio España (€):</label><input type="number" step="0.01" name="price_eur" <?= $modo_admin==='borrar'?'readonly':'' ?> value="<?= $modo_admin==='borrar' ? val_del('price_eur') : val_edit('price_eur') ?>"></div>
                            <div><label>Precio China (¥):</label><input type="number" step="0.01" name="price_cny" <?= $modo_admin==='borrar'?'readonly':'' ?> value="<?= $modo_admin==='borrar' ? val_del('price_cny') : val_edit('price_cny') ?>"></div>
                        </div>
                    </details>

                    <details class="categoria-filtro">
                        <summary>10. Conectividad y Redes</summary>
                        <div class="categoria-contenido">
                            <div><label>Red Móvil:</label><input type="text" name="network" <?= $modo_admin==='borrar'?'readonly':'' ?> value="<?= $modo_admin==='borrar' ? val_del('mobile_network') : val_edit('mobile_network') ?>"></div>
                            <div><label>Versión WiFi:</label><input type="text" name="wifi" <?= $modo_admin==='borrar'?'readonly':'' ?> value="<?= $modo_admin==='borrar' ? val_del('wifi_version') : val_edit('wifi_version') ?>"></div>
                            <div><label>Bluetooth:</label><input type="text" name="bluetooth" <?= $modo_admin==='borrar'?'readonly':'' ?> value="<?= $modo_admin==='borrar' ? val_del('bluetooth_version') : val_edit('bluetooth_version') ?>"></div>
                        </div>
                    </details>

                    <details class="categoria-filtro">
                        <summary>11. Valoración Personal (6 Puntos) y Comentarios</summary>
                        <div class="categoria-contenido">
                            <div><label>Punto 1 (Diseño):</label><input type="number" step="0.1" max="10" min="0" name="score_1" id="score_1" oninput="calcularMedia()" <?= $modo_admin==='borrar'?'readonly':'' ?> value="<?= $modo_admin==='borrar' ? val_del('score_1') : val_edit('score_1') ?>" placeholder="Ej: 8.5"></div>
                            <div><label>Punto 2 (Pantalla):</label><input type="number" step="0.1" max="10" min="0" name="score_2" id="score_2" oninput="calcularMedia()" <?= $modo_admin==='borrar'?'readonly':'' ?> value="<?= $modo_admin==='borrar' ? val_del('score_2') : val_edit('score_2') ?>" placeholder="Ej: 9.0"></div>
                            <div><label>Punto 3 (Rendimiento):</label><input type="number" step="0.1" max="10" min="0" name="score_3" id="score_3" oninput="calcularMedia()" <?= $modo_admin==='borrar'?'readonly':'' ?> value="<?= $modo_admin==='borrar' ? val_del('score_3') : val_edit('score_3') ?>" placeholder="Ej: 9.5"></div>
                            <div><label>Punto 4 (Cámaras):</label><input type="number" step="0.1" max="10" min="0" name="score_4" id="score_4" oninput="calcularMedia()" <?= $modo_admin==='borrar'?'readonly':'' ?> value="<?= $modo_admin==='borrar' ? val_del('score_4') : val_edit('score_4') ?>" placeholder="Ej: 8.0"></div>
                            <div><label>Punto 5 (Batería):</label><input type="number" step="0.1" max="10" min="0" name="score_5" id="score_5" oninput="calcularMedia()" <?= $modo_admin==='borrar'?'readonly':'' ?> value="<?= $modo_admin==='borrar' ? val_del('score_5') : val_edit('score_5') ?>" placeholder="Ej: 8.5"></div>
                            <div><label>Punto 6 (Software):</label><input type="number" step="0.1" max="10" min="0" name="score_6" id="score_6" oninput="calcularMedia()" <?= $modo_admin==='borrar'?'readonly':'' ?> value="<?= $modo_admin==='borrar' ? val_del('score_6') : val_edit('score_6') ?>" placeholder="Ej: 8.0"></div>
                            
                            <!-- Nota media automática de solo lectura -->
                            <div>
                                <label>Nota Media (Automática):</label>
                                <input type="number" step="0.01" name="score_avg" id="score_avg" readonly style="background: #eef2f7; font-weight: bold; color: #1e293b;" value="<?= $modo_admin==='borrar' ? val_del('score_avg') : val_edit('score_avg') ?>" placeholder="Calculada automáticamente">
                            </div>

                            <div style="grid-column: span 2;">
                                <label>Comentario Personal:</label>
                                <textarea name="personal_comment" <?= $modo_admin==='borrar'?'readonly':'' ?> rows="3" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:6px; font-size:0.9rem;" placeholder="Tus impresiones y conclusiones sobre el terminal..."><?= $modo_admin==='borrar' ? val_del('personal_comment') : val_edit('personal_comment') ?></textarea>
                            </div>
                            <div style="grid-column: span 2; margin-top: 10px;">
                                <label>Enlace de la foto (URL):</label>
                                <input type="text" name="image_url" <?= $modo_admin==='borrar'?'readonly':'' ?> value="<?= $modo_admin==='borrar' ? val_del('image_url') : val_edit('image_url') ?>" placeholder="https://ejemplo.com/imagen.jpg">
                            </div>
                        </div>
                    </details>

                    <?php if ($modo_admin === 'borrar'): ?>
                        <?php if ($borrar_id > 0 && !empty($delete_data)): ?>
                            <button type="submit" onclick="return confirm('¿Estás COMPLETAMENTE SEGURO de querer borrar este móvil? Esta acción no se puede deshacer.');" style="width: 100%; margin-top: 15px; padding: 12px; background: #c0392b; color: white; border: none; border-radius: 8px; font-weight: bold; cursor: pointer;">
                                ⚠️ ELIMINAR ESTE MÓVIL PERMANENTEMENTE ⚠️
                            </button>
                        <?php else: ?>
                            <p style="text-align: center; color: #c0392b; font-weight: bold; margin-top: 15px;">Selecciona un móvil arriba y pulsa "Cargar datos para verificar" para habilitar el botón de borrado.</p>
                        <?php endif; ?>
                    <?php else: ?>
                        <button type="submit" style="width: 100%; margin-top: 15px; padding: 12px; background: <?= $modo_admin === 'editar' ? '#e67e22' : '#27ae60' ?>; color: white; border: none; border-radius: 8px; font-weight: bold; cursor: pointer;">
                            <?= $modo_admin === 'editar' ? 'Actualizar Registro del Móvil' : 'Guardar Registro Completo' ?>
                        </button>
                    <?php endif; ?>
                </form>
            </section>
        <?php endif; ?>

        <!-- BUSCADOR AVANZADO -->
        <section class="introduccion" style="background: white; padding: 25px; border-radius: 12px; border: 1px solid #e0e5ed; margin-top: 25px;">
            <h2 style="margin-top: 0;">🔍 Buscador Avanzado por Categorías</h2>
            
            <?php if (!empty($error_precio)): ?>
                <div style="background: #f8d7da; color: #721c24; padding: 12px; border-radius: 6px; margin-bottom: 15px; font-weight: bold;"><?= escapar($error_precio) ?></div>
            <?php endif; ?>

            <form method="GET" action="index.php">
                <div style="margin-bottom: 20px;">
                    <label>Búsqueda por Nombre Completo:</label>
                    <input type="text" name="q" value="<?= escapar($q) ?>" placeholder="Ej: Xiaomi 14 Ultra...">
                </div>

                <details class="categoria-filtro">
                    <summary>📁 1. Clasificación y Marca</summary>
                    <div class="categoria-contenido">
                        <div>
                            <label>Sistema Operativo:</label>
                            <select name="f_so">
                                <option value="">-- Todos --</option>
                                <?php foreach ($list_so as $so): ?><option value="<?= escapar($so) ?>" <?= $f_so === $so ? 'selected' : '' ?>><?= escapar($so) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label>Marca:</label>
                            <select name="f_marca">
                                <option value="">-- Todas --</option>
                                <?php foreach ($list_marcas as $m): ?><option value="<?= escapar($m) ?>" <?= $f_marca === $m ? 'selected' : '' ?>><?= escapar($m) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label>Familia / Serie:</label>
                            <select name="f_familia">
                                <option value="">-- Todas --</option>
                                <?php foreach ($list_familias as $fam): ?><option value="<?= escapar($fam) ?>" <?= $f_familia === $fam ? 'selected' : '' ?>><?= escapar($fam) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label>Año de Lanzamiento:</label>
                            <select name="f_anio">
                                <option value="">-- Todos --</option>
                                <?php foreach ($list_anios as $a): ?><option value="<?= escapar($a) ?>" <?= $f_anio == $a ? 'selected' : '' ?>><?= escapar($a) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </details>

                <details class="categoria-filtro">
                    <summary>🤖 2. Sistema Operativo y Soporte</summary>
                    <div class="categoria-contenido">
                        <div>
                            <label>Capa de Personalización:</label>
                            <select name="f_skin">
                                <option value="">-- Todas --</option>
                                <?php foreach ($list_skins as $sk): ?><option value="<?= escapar($sk) ?>" <?= $f_skin === $sk ? 'selected' : '' ?>><?= escapar($sk) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label>Años Act. Sistema:</label>
                            <select name="f_os_up">
                                <option value="">-- Cualquiera --</option>
                                <option value="3" <?= $f_os_up === '3' ? 'selected' : '' ?>>3 años</option>
                                <option value="4" <?= $f_os_up === '4' ? 'selected' : '' ?>>4 años</option>
                                <option value="5" <?= $f_os_up === '5' ? 'selected' : '' ?>>5+ años</option>
                            </select>
                        </div>
                        <div>
                            <label>Años Parches de Seguridad:</label>
                            <select name="f_sec_up">
                                <option value="">-- Cualquiera --</option>
                                <option value="4" <?= $f_sec_up === '4' ? 'selected' : '' ?>>4 años</option>
                                <option value="5" <?= $f_sec_up === '5' ? 'selected' : '' ?>>5 años</option>
                                <option value="7" <?= $f_sec_up === '7' ? 'selected' : '' ?>>7 años</option>
                            </select>
                        </div>
                    </div>
                </details>

                <details class="categoria-filtro">
                    <summary>⚡ 3. Procesador (SoC)</summary>
                    <div class="categoria-contenido">
                        <div>
                            <label>Marca Procesador:</label>
                            <select name="f_soc_brand">
                                <option value="">-- Todas --</option>
                                <?php foreach ($list_soc_brands as $sb): ?><option value="<?= escapar($sb) ?>" <?= $f_soc_brand === $sb ? 'selected' : '' ?>><?= escapar($sb) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label>Nombre Completo:</label>
                            <select name="f_soc_name">
                                <option value="">-- Todos --</option>
                                <?php foreach ($list_soc_names as $sn): ?><option value="<?= escapar($sn) ?>" <?= $f_soc_name === $sn ? 'selected' : '' ?>><?= escapar($sn) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label>Nanómetros (nm):</label>
                            <select name="f_soc_nm">
                                <option value="">-- Todos --</option>
                                <option value="3" <?= $f_soc_nm === '3' ? 'selected' : '' ?>>3 nm</option>
                                <option value="4" <?= $f_soc_nm === '4' ? 'selected' : '' ?>>4 nm</option>
                                <option value="5" <?= $f_soc_nm === '5' ? 'selected' : '' ?>>5 nm</option>
                            </select>
                        </div>
                        <div>
                            <label>AnTuTu Mínimo:</label>
                            <input type="number" name="f_antutu" value="<?= escapar($f_antutu) ?>" placeholder="Ej: 2000000">
                        </div>
                    </div>
                </details>

                <details class="categoria-filtro">
                    <summary>📱 4. Pantalla</summary>
                    <div class="categoria-contenido">
                        <div>
                            <label>Tamaño (Rangos):</label>
                            <select name="f_screen_size">
                                <option value="">-- Cualquiera --</option>
                                <option value="mini" <?= $f_screen_size === 'mini' ? 'selected' : '' ?>>Mini (&lt; 6.0")</option>
                                <option value="pequeno" <?= $f_screen_size === 'pequeno' ? 'selected' : '' ?>>Pequeño (6.0" - 6.4")</option>
                                <option value="medio" <?= $f_screen_size === 'medio' ? 'selected' : '' ?>>Medio (6.41" - 6.65")</option>
                                <option value="grande" <?= $f_screen_size === 'grande' ? 'selected' : '' ?>>Grande (&gt; 6.65")</option>
                            </select>
                        </div>
                        <div>
                            <label>Tasa de Refresco (Hz):</label>
                            <select name="f_hz">
                                <option value="">-- Todos --</option>
                                <?php foreach ($list_hz as $hz): ?><option value="<?= escapar($hz) ?>" <?= $f_hz == $hz ? 'selected' : '' ?>><?= escapar($hz) ?> Hz</option><?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label>Tecnología:</label>
                            <select name="f_screen_tech">
                                <option value="">-- Todas --</option>
                                <?php foreach ($list_screen_tech as $st): ?><option value="<?= escapar($st) ?>" <?= $f_screen_tech === $st ? 'selected' : '' ?>><?= escapar($st) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </details>

                <details class="categoria-filtro">
                    <summary>🔋 5. Batería</summary>
                    <div class="categoria-contenido">
                        <div>
                            <label>Miliamperios Mínimos (mAh):</label>
                            <input type="number" name="f_mah" value="<?= escapar($f_mah) ?>" placeholder="Ej: 5000">
                        </div>
                        <div>
                            <label>Carga por Cable (W):</label>
                            <select name="f_wired">
                                <option value="">-- Cualquiera --</option>
                                <?php foreach ($list_wired as $w): ?><option value="<?= escapar($w) ?>" <?= $f_wired == $w ? 'selected' : '' ?>><?= escapar($w) ?>W</option><?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label>Carga Inalámbrica:</label>
                            <select name="f_wireless">
                                <option value="">-- Indiferente --</option>
                                <option value="1" <?= $f_wireless === '1' ? 'selected' : '' ?>>Sí</option>
                                <option value="0" <?= $f_wireless === '0' ? 'selected' : '' ?>>No</option>
                            </select>
                        </div>
                    </div>
                </details>

                <details class="categoria-filtro">
                    <summary>📸 6. Cámaras</summary>
                    <div class="categoria-contenido">
                        <div>
                            <label>Principal - Megapíxeles (MP):</label>
                            <select name="f_cam_mp">
                                <option value="">-- Cualquiera --</option>
                                <option value="50" <?= $f_cam_mp === '50' ? 'selected' : '' ?>>50 MP</option>
                                <option value="200" <?= $f_cam_mp === '200' ? 'selected' : '' ?>>200 MP</option>
                            </select>
                        </div>
                        <div>
                            <label>Principal - Tamaño Sensor:</label>
                            <select name="f_cam_size">
                                <option value="">-- Cualquiera --</option>
                                <option value="1/0.98''" <?= $f_cam_size === "1/0.98''" ? 'selected' : '' ?>>1/0.98"</option>
                                <option value="1/1.12''" <?= $f_cam_size === "1/1.12''" ? 'selected' : '' ?>>1/1.12"</option>
                            </select>
                        </div>
                    </div>
                </details>

                <details class="categoria-filtro">
                    <summary>🎥 7. Vídeo (LOG / OpenGate)</summary>
                    <div class="categoria-contenido">
                        <div>
                            <label><input type="checkbox" name="f_log" value="1" <?= !empty($f_log) ? 'checked' : '' ?>> Compatible con Perfil LOG</label>
                        </div>
                        <div>
                            <label><input type="checkbox" name="f_opengate" value="1" <?= !empty($f_opengate) ? 'checked' : '' ?>> Compatible con OpenGate</label>
                        </div>
                    </div>
                </details>

                <details class="categoria-filtro">
                    <summary>🛡️ 8. Gama y Construcción</summary>
                    <div class="categoria-contenido">
                        <div>
                            <label>Material Marco:</label>
                            <select name="f_frame_material">
                                <option value="">-- Todos --</option>
                                <?php foreach ($list_frame_mat as $fm): ?><option value="<?= escapar($fm) ?>" <?= $f_frame_material === $fm ? 'selected' : '' ?>><?= escapar($fm) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label>Material Trasera:</label>
                            <select name="f_back_material">
                                <option value="">-- Todos --</option>
                                <?php foreach ($list_back_mat as $bm): ?><option value="<?= escapar($bm) ?>" <?= $f_back_material === $bm ? 'selected' : '' ?>><?= escapar($bm) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label>Gama:</label>
                            <select name="f_tier">
                                <option value="">-- Todas --</option>
                                <?php foreach ($list_tiers as $t): ?><option value="<?= escapar($t) ?>" <?= $f_tier === $t ? 'selected' : '' ?>><?= escapar($t) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label>Protección IP:</label>
                            <select name="f_ip">
                                <option value="">-- Todas --</option>
                                <?php foreach ($list_ips as $ip): ?><option value="<?= escapar($ip) ?>" <?= $f_ip === $ip ? 'selected' : '' ?>><?= escapar($ip) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </details>

                <details class="categoria-filtro">
                    <summary>💶 9. Precio</summary>
                    <div class="categoria-contenido">
                        <div>
                            <label>España € (Mín - Máx):</label>
                            <div style="display: flex; gap: 5px;">
                                <input type="number" name="p_eur_min" value="<?= escapar($p_eur_min) ?>" placeholder="Mín">
                                <input type="number" name="p_eur_max" value="<?= escapar($p_eur_max) ?>" placeholder="Máx">
                            </div>
                        </div>
                        <div>
                            <label>China ¥ (Mín - Máx):</label>
                            <div style="display: flex; gap: 5px;">
                                <input type="number" name="p_cny_min" value="<?= escapar($p_cny_min) ?>" placeholder="Mín">
                                <input type="number" name="p_cny_max" value="<?= escapar($p_cny_max) ?>" placeholder="Máx">
                            </div>
                        </div>
                    </div>
                </details>

                <details class="categoria-filtro">
                    <summary>📡 10. Conectividad</summary>
                    <div class="categoria-contenido">
                        <div>
                            <label>Red Móvil:</label>
                            <select name="f_network">
                                <option value="">-- Todas --</option>
                                <option value="5G" <?= $f_network === '5G' ? 'selected' : '' ?>>5G</option>
                            </select>
                        </div>
                        <div>
                            <label>Versión WiFi:</label>
                            <select name="f_wifi">
                                <option value="">-- Todas --</option>
                                <?php foreach ($list_wifi as $wf): ?><option value="<?= escapar($wf) ?>" <?= $f_wifi === $wf ? 'selected' : '' ?>><?= escapar($wf) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label>Bluetooth:</label>
                            <select name="f_bt">
                                <option value="">-- Todas --</option>
                                <?php foreach ($list_bt as $bt): ?><option value="<?= escapar($bt) ?>" <?= $f_bt === $bt ? 'selected' : '' ?>><?= escapar($bt) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label>NFC:</label>
                            <select name="f_nfc">
                                <option value="">-- Indiferente --</option>
                                <option value="1" <?= $f_nfc === '1' ? 'selected' : '' ?>>Sí</option>
                                <option value="0" <?= $f_nfc === '0' ? 'selected' : '' ?>>No</option>
                            </select>
                        </div>
                        <div>
                            <label>Soporte eSIM:</label>
                            <select name="f_esim">
                                <option value="">-- Indiferente --</option>
                                <option value="1" <?= $f_esim === '1' ? 'selected' : '' ?>>Sí</option>
                                <option value="0" <?= $f_esim === '0' ? 'selected' : '' ?>>No</option>
                            </select>
                        </div>
                    </div>
                </details>

                <div style="display: flex; gap: 15px; margin-top: 20px;">
                    <button type="submit" style="flex: 2; padding: 12px; background: #3264b3; color: white; border: none; border-radius: 8px; font-weight: bold; cursor: pointer;">Aplicar Filtros</button>
                    <a href="index.php" style="flex: 1; padding: 12px; background: #e4ebf7; color: #3264b3; text-decoration: none; border-radius: 8px; text-align: center; font-weight: bold;">Restablecer</a>
                </div>
            </form>
        </section>

        <!-- RESULTADOS DEL CATÁLOGO -->
        <section class="catalogo" style="margin-top: 30px;">
            <div class="encabezado-catalogo">
                <h2>Modelos Registrados</h2>
                <span class="contador"><?= count($moviles) ?> <?= count($moviles) === 1 ? 'móvil encontrado' : 'móviles encontrados' ?></span>
            </div>

            <div class="grid-moviles">
                <?php foreach ($moviles as $m): ?>
                    <a href="detalles.php?id=<?= $m['id'] ?>" style="text-decoration: none; color: inherit;">
                        <article class="tarjeta">
                            <p class="marca"><?= escapar($m['marca']) ?></p>
                            <h3><?= escapar($m['movil']) ?></h3>
                            <p class="familia">Familia: <?= escapar($m['familia']) ?></p>
                            <p class="anio">Lanzamiento: <?= $m['launch_year'] ?> <span style="float: right; background: #e4ebf7; padding: 2px 6px; border-radius: 4px; color: #3264b3; font-size: 0.8rem;"><?= escapar($m['tier']) ?></span></p>
                        </article>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>

    </main>

    <script>
        function agregarCamaraAuxiliar() {
            const container = document.getElementById('contenedor-aux-cams');
            const box = document.createElement('div');
            box.className = 'aux-camara-box';
            box.innerHTML = `
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                    <strong>Sensor Auxiliar</strong>
                    <button type="button" onclick="this.parentElement.parentElement.remove()" style="background: #e74c3c; color: white; border: none; padding: 3px 8px; border-radius: 4px; cursor: pointer; font-size: 0.8rem;">Eliminar</button>
                </div>
                <div class="categoria-contenido" style="margin-top: 0; padding-top: 0; border: none;">
                    <div>
                        <label>Tipo de Cámara:</label>
                        <select name="aux_type[]">
                            <option value="Ultra gran angular">Ultra gran angular</option>
                            <option value="Teleobjetivo telescópico">Teleobjetivo telescópico</option>
                            <option value="Teleobjetivo periscópico">Teleobjetivo periscópico</option>
                            <option value="Retrato">Retrato</option>
                            <option value="Macro">Macro</option>
                            <option value="Multiespectral">Multiespectral</option>
                            <option value="Monocromo">Monocromo</option>
                            <option value="Profundidad">Profundidad</option>
                        </select>
                    </div>
                    <div><label>Nombre Sensor:</label><input type="text" name="aux_sensor_name[]" placeholder="Ej: Sony IMX858"></div>
                    <div><label>Tamaño Sensor:</label><input type="text" name="aux_sensor_size[]" placeholder="1/2.51''"></div>
                    <div><label>Megapíxeles (MP):</label><input type="number" step="0.01" name="aux_resolution_mp[]" placeholder="50"></div>
                    <div><label>Apertura:</label><input type="text" name="aux_aperture[]" placeholder="f/1.8"></div>
                    <div><label>Focal (mm):</label><input type="number" name="aux_focal_length_mm[]" placeholder="12"></div>
                    <div><label>Vídeo Normal Máx:</label><input type="text" name="aux_normal_video_max[]" placeholder="4K @ 60fps"></div>
                    <div>
                        <label><input type="checkbox" name="aux_has_log[]" value="1"> LOG</label>
                        <input type="text" name="aux_log_specs[]" placeholder="LOG specs">
                    </div>
                    <div>
                        <label><input type="checkbox" name="aux_has_opengate[]" value="1"> OpenGate</label>
                        <input type="text" name="aux_opengate_specs[]" placeholder="OpenGate specs">
                    </div>
                </div>
            `;
            container.appendChild(box);
        }

        function calcularMedia() {
            const s1 = parseFloat(document.getElementById('score_1').value) || 0;
            const s2 = parseFloat(document.getElementById('score_2').value) || 0;
            const s3 = parseFloat(document.getElementById('score_3').value) || 0;
            const s4 = parseFloat(document.getElementById('score_4').value) || 0;
            const s5 = parseFloat(document.getElementById('score_5').value) || 0;
            const s6 = parseFloat(document.getElementById('score_6').value) || 0;

            const suma = s1 + s2 + s3 + s4 + s5 + s6;
            const media = (suma / 6).toFixed(2);

            const algunValor = (s1 > 0 || s2 > 0 || s3 > 0 || s4 > 0 || s5 > 0 || s6 > 0);
            document.getElementById('score_avg').value = algunValor ? media : '';
        }
    </script>
</body>
</html>