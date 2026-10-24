<?php
/**
 * ============================================================================
 * DETALLES.PHP · FICHA TÉCNICA COMPLETA DE UN MÓVIL
 * Adaptado a la estructura de base de datos: estructura_2026-10-20.sql
 * ============================================================================
 *  FASE 1: Configuración, sesión y funciones auxiliares
 *  FASE 2: Consultas y carga de datos técnicos del terminal
 *  FASE 3: Maquetación de la ficha
 *  FASE 4: Script del gráfico radar (solo si hay notas por criterio)
 * ============================================================================
 */
require_once __DIR__ . '/config/conexion.php';

$sesion_iniciada = isset($_SESSION['user_id']);
$nombre_usuario = $sesion_iniciada ? (string)($_SESSION['username'] ?? '') : '';
$es_admin = $sesion_iniciada && !empty($_SESSION['is_admin']);

// Capturar y validar el ID del móvil por GET
$id_movil = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id_movil <= 0) {
    die('Móvil no válido o no especificado.');
}

/**
 * Funciones auxiliares de presentación
 */

// Función de escape de seguridad para textos
function escapar($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// Quita ceros sobrantes de un DECIMAL de MySQL: "6.70" -> "6.7", "50.00" -> "50"
function num_limpio($v) {
    if ($v === null || $v === '') return '';
    $s = (string)$v;
    if (strpos($s, '.') !== false) {
        $s = rtrim(rtrim($s, '0'), '.');
    }
    return $s;
}

// Texto o "N/D" si el dato no existe (se escapa solo)
function mostrar($v, $sufijo = '') {
    return ($v === null || $v === '') ? 'N/D' : escapar($v) . $sufijo;
}

// Igual que mostrar(), pero limpiando los ceros de los decimales (solo para columnas numéricas)
function mostrar_num($v, $sufijo = '') {
    return ($v === null || $v === '' || (float)$v == 0) ? 'N/D' : escapar(num_limpio($v)) . $sufijo;
}

// Potencia de carga: "65W" o "No" si no hay
function potencia($v) {
    return ($v !== null && (float)$v > 0) ? escapar($v) . 'W' : 'No';
}

// Precio con símbolo: sin decimales si es redondo (999 €), con dos si no (999,99 €)
function formato_precio($v, $simbolo) {
    $v = (float)$v;
    $dec = (floor($v) == $v) ? 0 : 2;
    return escapar(number_format($v, $dec, ',', '.') . ' ' . $simbolo);
}

// Texto de un modo de vídeo (LOG / OpenGate / OpenGate+LOG) con hasta dos opciones: "4K @ 120fps / 8K @ 30fps"
function formato_video($tiene, $resolucion, $fps, $resolucion2 = '', $fps2 = '') {
    if (empty($tiene)) return 'No tiene';
    $opciones = [];
    foreach ([[$resolucion, $fps], [$resolucion2, $fps2]] as $par) {
        $partes = [];
        if (!empty($par[0])) $partes[] = $par[0];
        if (!empty($par[1])) $partes[] = ($partes ? '@ ' : '') . $par[1] . 'fps';
        if ($partes) $opciones[] = implode(' ', $partes);
    }
    return $opciones ? escapar(implode(' / ', $opciones)) : 'Sí';
}

// Regiones de precio (columnas de phone_metadata)
$regiones_precio = [
    'Global / España' => ['price_global', '€'],
    'Unión Europea'   => ['price_ue', '€'],
    'China'           => ['price_china', '¥'],
    'India'           => ['price_india', '₹'],
    'Japón'           => ['price_japan', '¥'],
    'Corea'           => ['price_korea', '₩'],
    'EE. UU.'         => ['price_usa', '$'],
    'Latam'           => ['price_latam', 'MXN'],
    'Taiwán'          => ['price_taiwan', 'TWD'],
    'Rusia'           => ['price_russia', '₽'],
];

/**
 * ============================================================================
 * FASE 2: CONSULTAS Y CARGA DE DATOS TÉCNICOS DEL TERMINAL
 * ============================================================================
 */
try {
    // Consultas auxiliares: una fila / varias filas (devuelven [] si no hay datos)
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

    // 1. Datos base del móvil, marca, familia y sistema operativo (el SO pertenece a la marca)
    $movil = $una("
        SELECT p.*, b.name AS marca, s.name AS familia, os.name AS sistema_operativo
        FROM phones p
        INNER JOIN series s ON s.id = p.series_id
        INNER JOIN brands b ON b.id = s.brand_id
        LEFT JOIN operative_systems os ON os.id = b.operative_system_id
        WHERE p.id = ?
    ", $id_movil);

    if (empty($movil)) {
        die('El teléfono solicitado no existe.');
    }

    // 2. Metadata (dimensiones, IP, materiales, nota media, imagen y precios regionales)
    $meta = $una("SELECT * FROM phone_metadata WHERE phone_id = ? ORDER BY id LIMIT 1", $id_movil);

    // 3. Software y soporte de actualizaciones
    $sw = $una("SELECT * FROM software_support WHERE phone_id = ? ORDER BY id LIMIT 1", $id_movil);

    // 4. Características técnicas (SoC, memoria, pantalla, batería, conectividad y cámaras)
    $char = $una("SELECT id FROM phone_characteristics WHERE phone_id = ? ORDER BY id LIMIT 1", $id_movil);
    $char_id = $char ? (int)$char['id'] : 0;

    $soc = $scr = $bat = $conn = [];
    $storage_versions = [];
    $cams = [];

    if ($char_id) {
        $soc  = $una("SELECT * FROM characteristics_soc WHERE phone_characteristic_id = ? ORDER BY id LIMIT 1", $char_id);
        $scr  = $una("SELECT * FROM characteristics_screen WHERE phone_characteristic_id = ? ORDER BY id LIMIT 1", $char_id);
        $bat  = $una("SELECT * FROM characteristics_battery WHERE phone_characteristic_id = ? ORDER BY id LIMIT 1", $char_id);
        $conn = $una("SELECT * FROM characteristics_connectivity WHERE phone_characteristic_id = ? ORDER BY id LIMIT 1", $char_id);

        // Múltiples versiones de memoria RAM y almacenamiento
        $storage_versions = $varias("SELECT * FROM characteristics_storage_versions WHERE phone_characteristic_id = ? ORDER BY id", $char_id);

        // Módulos de cámaras ordenados (Principal primero)
        $cams = $varias("
            SELECT * FROM camera_modules
            WHERE phone_characteristic_id = ?
            ORDER BY CASE WHEN camera_type = 'Principal' THEN 0 ELSE 1 END, id
        ", $char_id);
    }

    // 5. Otros modelos de la misma familia (para navegar y cargar en el comparador)
    $stmt_familia = $conexion->prepare("
        SELECT p.id, p.name, b.name AS marca
        FROM phones p
        INNER JOIN series s ON s.id = p.series_id
        INNER JOIN brands b ON b.id = s.brand_id
        WHERE p.series_id = ? AND p.id <> ?
        ORDER BY p.launch_year DESC, p.name ASC
        LIMIT 5
    ");
    $stmt_familia->execute([(int)$movil['series_id'], $id_movil]);
    $familia_moviles = $stmt_familia->fetchAll();

    $ids_comparar = [$id_movil];
    foreach ($familia_moviles as $fm) { $ids_comparar[] = (int)$fm['id']; }
    $query_comparar = http_build_query(['ids' => $ids_comparar]);

    // 6. Valoración: la nota media está siempre en phone_metadata.score_avg.
    //    El hexágono solo se dibuja si la base de datos tiene las 6 notas por criterio (score_1..score_6).
    $vals = [];
    $tiene_notas = false;
    for ($i = 1; $i <= 6; $i++) {
        $nota = isset($meta['score_' . $i]) ? (float)$meta['score_' . $i] : 0;
        $vals[] = $nota;
        if ($nota > 0) $tiene_notas = true;
    }

    $score_avg = isset($meta['score_avg']) ? (float)$meta['score_avg'] : 0;
    $tiene_media = $score_avg > 0;
    $media = $tiene_media ? num_limpio($meta['score_avg']) : 'Sin nota';

} catch (PDOException $e) {
    die('Error al cargar la ficha técnica: ' . escapar($e->getMessage()));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= escapar($movil['name']) ?> | Ficha Técnica Completa</title>
    <link rel="stylesheet" href="css/estilos.css">
    <?php if ($tiene_notas): ?><script src="css/chart.js"></script><?php endif; ?>
</head>
<body>

    <header class="cabecera">
        <div class="contenedor">
            <div class="header-top-bar">
                <p class="sobrelinea">
                    <a href="index.php" class="enlace-volver">&larr; VOLVER AL CATÁLOGO</a>
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

            <div class="header-detalles-grid">
                <div>
                    <h1><?= escapar($movil['name']) ?></h1>
                    <p class="subtitulo">
                        Marca: <?= escapar($movil['marca']) ?> | Familia: <?= escapar($movil['familia']) ?> | Lanzamiento: <?= escapar($movil['launch_year']) ?> | Gama: <?= !empty($movil['tier']) ? escapar($movil['tier']) : 'No especificada' ?>
                    </p>
                </div>

                <div class="header-imagen-wrapper">
                    <?php if (!empty($meta['image_url'])): ?>
                        <img src="<?= escapar($meta['image_url']) ?>" alt="Foto de <?= escapar($movil['name']) ?>" class="detalles-imagen-movil">
                    <?php else: ?>
                        <span class="sin-imagen-texto">Sin imagen</span>
                    <?php endif; ?>
                </div>

                <div class="header-radar-wrapper">
                    <?php if ($tiene_notas): ?>
                        <div class="header-diagrama-caja">
                            <div class="contenedor-canvas">
                                <canvas id="radarHexagono"></canvas>
                            </div>
                        </div>
                    <?php endif; ?>
                    <p class="sobrelinea">
                        VALORACIÓN: <strong class="valoracion-texto"><?= escapar($media) ?><?php if ($tiene_media): ?>/10<?php endif; ?></strong>
                    </p>
                </div>
            </div>
        </div>
    </header>

    <main class="contenedor espacio-cuerpo">

        <!-- Comentario personal sobre el móvil (visible para todos los visitantes) -->
        <?php if (!empty($meta['personal_comment'])): ?>
            <section class="tarjeta tarjeta-comentario">
                <h2>💬 Mi opinión sobre este móvil</h2>
                <p class="comentario-texto"><?= nl2br(escapar($meta['personal_comment'])) ?></p>
            </section>
        <?php endif; ?>

        <!-- Otros modelos de la familia y acceso al comparador -->
        <section class="tarjeta tarjeta-variantes">
            <h3>📱 Otros modelos de la familia <?= escapar($movil['familia']) ?></h3>
            <?php if (!empty($familia_moviles)): ?>
                <div class="variantes-tags-row">
                    <?php foreach ($familia_moviles as $fm): ?>
                        <a href="detalles.php?id=<?= (int)$fm['id'] ?>" class="tag-variante">
                            <?= escapar($fm['marca'] . ' ' . $fm['name']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="sin-variantes-texto">No hay más modelos registrados en esta familia.</p>
            <?php endif; ?>
            <div class="comparar-variantes-btn-wrapper">
                <a href="comparar.php?<?= escapar($query_comparar) ?>" class="btn-cargar-comparador">
                    ⚖️ Cargar en el comparador
                </a>
            </div>
        </section>

        <!-- Cuadrícula de Especificaciones Técnicas Detalladas -->
        <div class="grid-detalles-completo">

            <!-- Diseño y Dimensiones -->
            <section class="tarjeta">
                <h2>📐 Diseño y Dimensiones</h2>
                <ul class="lista-specs-detallada">
                    <li><span>Alto:</span> <strong><?= mostrar_num($meta['height_mm'] ?? null, ' mm') ?></strong></li>
                    <li><span>Ancho:</span> <strong><?= mostrar_num($meta['width_mm'] ?? null, ' mm') ?></strong></li>
                    <li><span>Grosor:</span> <strong><?= mostrar_num($meta['thickness_mm'] ?? null, ' mm') ?></strong></li>
                    <li><span>Peso:</span> <strong><?= mostrar_num($meta['weight_g'] ?? null, ' g') ?></strong></li>
                    <li><span>Certificación IP:</span> <strong><?= mostrar($meta['ip_rating'] ?? null) ?></strong></li>
                    <li><span>Marco:</span> <strong><?= mostrar($meta['frame_material'] ?? null) ?></strong></li>
                    <li><span>Trasera:</span> <strong><?= mostrar($meta['back_material'] ?? null) ?></strong></li>
                </ul>
            </section>

            <!-- Software y Actualizaciones -->
            <section class="tarjeta">
                <h2>🤖 Software y Actualizaciones</h2>
                <ul class="lista-specs-detallada">
                    <li><span>Sistema Operativo:</span> <strong><?= mostrar($movil['sistema_operativo'] ?? null) ?></strong></li>
                    <li><span>Capa Personalización:</span> <strong><?= mostrar($sw['initial_skin'] ?? null) ?></strong></li>
                    <li><span>Años Act. Sistema:</span> <strong><?= mostrar_num($sw['os_and_skin_update_years'] ?? null, ' años') ?></strong></li>
                    <li><span>Parches Seguridad:</span> <strong><?= mostrar_num($sw['security_update_years'] ?? null, ' años') ?></strong></li>
                </ul>
            </section>

            <!-- Procesador (SoC) -->
            <section class="tarjeta">
                <h2>⚡ Procesador (SoC) y Benchmarks</h2>
                <ul class="lista-specs-detallada">
                    <?php $nombre_soc = trim(($soc['brand'] ?? '') . ' ' . ($soc['full_name'] ?? '')); ?>
                    <li><span>Procesador:</span> <strong><?= mostrar($nombre_soc !== '' ? $nombre_soc : null) ?></strong></li>
                    <li><span>Arquitectura:</span> <strong><?= mostrar_num($soc['architecture_nm'] ?? null, ' nm') ?></strong></li>
                    <li><span>AnTuTu:</span> <strong><?= !empty($soc['antutu_score']) ? escapar(number_format((float)$soc['antutu_score'], 0, ',', '.')) : 'N/D' ?></strong></li>
                    <li><span>Geekbench Mono:</span> <strong><?= mostrar_num($soc['geekbench_single'] ?? null) ?></strong></li>
                    <li><span>Geekbench Multi:</span> <strong><?= mostrar_num($soc['geekbench_multi'] ?? null) ?></strong></li>
                </ul>
            </section>

            <!-- Memoria RAM y Almacenamiento -->
            <section class="tarjeta">
                <h2>💾 Memoria y Almacenamiento</h2>
                <ul class="lista-specs-detallada">
                    <?php if (!empty($storage_versions)): ?>
                        <?php foreach ($storage_versions as $i => $sv): ?>
                            <li>
                                <span>Versión <?= $i + 1 ?>:</span>
                                <strong>
                                    <?= escapar($sv['ram_capacities_gb']) ?> GB<?= !empty($sv['ram_technology']) ? ' (' . escapar($sv['ram_technology']) . ')' : '' ?>
                                    / <?= escapar($sv['storage_capacities_gb']) ?> GB<?= !empty($sv['storage_technology']) ? ' (' . escapar($sv['storage_technology']) . ')' : '' ?>
                                </strong>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li><span>Versiones:</span> <strong>N/D</strong></li>
                    <?php endif; ?>
                </ul>
            </section>

            <!-- Pantalla -->
            <section class="tarjeta">
                <h2>📱 Pantalla y Visualización</h2>
                <ul class="lista-specs-detallada">
                    <li><span>Tamaño:</span> <strong><?= !empty($scr['inches']) ? escapar(num_limpio($scr['inches'])) . '"' : 'N/D' ?></strong></li>
                    <li><span>Tecnología:</span> <strong><?= mostrar($scr['technology'] ?? null) ?></strong></li>
                    <li><span>Resolución:</span> <strong><?= mostrar($scr['resolution'] ?? null) ?></strong></li>
                    <li><span>Refresco:</span> <strong><?= mostrar_num($scr['refresh_rate_hz'] ?? null, ' Hz') ?></strong></li>
                </ul>
            </section>

            <!-- Batería -->
            <section class="tarjeta">
                <h2>🔋 Batería y Autonomía</h2>
                <ul class="lista-specs-detallada">
                    <li><span>Capacidad:</span> <strong><?= mostrar_num($bat['capacity_mah'] ?? null, ' mAh') ?></strong></li>
                    <li><span>Carga Cable:</span> <strong><?= mostrar_num($bat['wired_charging_w'] ?? null, 'W') ?></strong></li>
                    <li><span>Carga Inalámbrica:</span> <strong><?= $bat ? potencia($bat['wireless_charging_w'] ?? null) : 'N/D' ?></strong></li>
                    <li><span>Ciclos (80% salud):</span> <strong><?= mostrar_num($bat['assured_cycles_80_health'] ?? null, ' ciclos') ?></strong></li>
                    <li><span>Inversa (Cable/Inál.):</span> <strong><?= $bat ? potencia($bat['reverse_wired_charging_w'] ?? null) . ' / ' . potencia($bat['reverse_wireless_charging_w'] ?? null) : 'N/D' ?></strong></li>
                </ul>
            </section>

            <!-- Conectividad -->
            <section class="tarjeta">
                <h2>📡 Conectividad y Redes</h2>
                <ul class="lista-specs-detallada">
                    <li><span>Red Móvil:</span> <strong><?= mostrar($conn['mobile_network'] ?? null) ?></strong></li>
                    <li><span>Wi-Fi:</span> <strong><?= mostrar($conn['wifi_version'] ?? null) ?></strong></li>
                    <li><span>Bluetooth:</span> <strong><?= mostrar($conn['bluetooth_version'] ?? null) ?></strong></li>
                    <li><span>Android Auto:</span> <strong><?= $conn ? (!empty($conn['android_auto']) ? 'Compatible' : 'No') : 'N/D' ?></strong></li>
                    <li><span>NFC / eSIM:</span> <strong><?= $conn ? (!empty($conn['nfc']) ? 'Sí' : 'No') . ' / ' . (!empty($conn['esim_support']) ? 'Compatible' : 'No') : 'N/D' ?></strong></li>
                </ul>
            </section>

            <!-- Precios Regionales (solo se listan las regiones con precio) -->
            <section class="tarjeta">
                <h2>💶 Precios Regionales</h2>
                <ul class="lista-specs-detallada">
                    <?php $hay_precios = false; ?>
                    <?php foreach ($regiones_precio as $nombre_region => $datos_region): ?>
                        <?php $precio = $meta[$datos_region[0]] ?? null; ?>
                        <?php if ($precio !== null && (float)$precio > 0): $hay_precios = true; ?>
                            <li><span><?= escapar($nombre_region) ?>:</span> <strong><?= formato_precio($precio, $datos_region[1]) ?></strong></li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <?php if (!empty($meta['price_carrier']) && (float)$meta['price_carrier'] > 0): $hay_precios = true; ?>
                        <li>
                            <span>Operadora:</span>
                            <strong><?= formato_precio($meta['price_carrier'], !empty($meta['carrier_currency']) ? $meta['carrier_currency'] : 'EUR') ?></strong>
                        </li>
                    <?php endif; ?>
                    <?php if (!$hay_precios): ?>
                        <li><span>Precios:</span> <strong>No especificados</strong></li>
                    <?php endif; ?>
                </ul>
                <?php if (!empty($meta['carrier_details'])): ?>
                    <p class="carrier-detalles"><strong>Detalles de la operadora:</strong><br><?= nl2br(escapar($meta['carrier_details'])) ?></p>
                <?php endif; ?>
            </section>
        </div>

        <!-- Sistema de Cámaras -->
        <section class="tarjeta mt-20">
            <h2>📸 Sistema de Cámaras y Capacidades de Vídeo</h2>
            <?php if (!empty($cams)): ?>
                <div class="cams-grid-container">
                    <?php foreach ($cams as $c): ?>
                        <div class="cam-module-card">
                            <h3><?= escapar($c['camera_type']) ?></h3>
                            <p><strong>Sensor:</strong> <?= mostrar($c['sensor_name'] ?? null) ?><?= !empty($c['sensor_size']) ? ' (' . escapar($c['sensor_size']) . ')' : '' ?></p>
                            <p><strong>Resolución:</strong> <?= mostrar_num($c['resolution_mp'] ?? null, ' MP') ?></p>
                            <p><strong>Apertura:</strong> <?= !empty($c['aperture']) ? 'f/' . escapar(num_limpio($c['aperture'])) : 'N/D' ?></p>
                            <hr class="cam-divider">
                            <p class="cam-spec-text"><strong>Vídeo Normal:</strong> <?php $video_normal = implode(' / ', array_filter([$c['normal_video_max'] ?? '', $c['normal_video_max_2'] ?? ''], function ($t) { return $t !== null && $t !== ''; })); ?><?= mostrar($video_normal !== '' ? $video_normal : null) ?></p>
                            <p class="cam-spec-text"><strong>LOG:</strong> <?= formato_video($c['has_log'] ?? 0, $c['log_resolution'] ?? '', $c['log_fps'] ?? '', $c['log_resolution_2'] ?? '', $c['log_fps_2'] ?? '') ?></p>
                            <p class="cam-spec-text"><strong>OpenGate:</strong> <?= formato_video($c['has_opengate'] ?? 0, $c['opengate_resolution'] ?? '', $c['opengate_fps'] ?? '', $c['opengate_resolution_2'] ?? '', $c['opengate_fps_2'] ?? '') ?></p>
                            <p class="cam-spec-text"><strong>OpenGate + LOG:</strong> <?= formato_video($c['has_opengate_log'] ?? 0, $c['opengate_log_resolution'] ?? '', $c['opengate_log_fps'] ?? '', $c['opengate_log_resolution_2'] ?? '', $c['opengate_log_fps_2'] ?? '') ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>No se han registrado módulos de cámara para este modelo.</p>
            <?php endif; ?>
        </section>

    </main>

    <footer class="pie">
        <div class="contenedor">
            <p>Catálogo de smartphones · Proyecto personal estilo Kimovil</p>
        </div>
    </footer>

    <?php if ($tiene_notas): ?>
    <!-- ============================================================================
         FASE 4: SCRIPT JAVASCRIPT (GRÁFICO RADAR HEXÁGONO)
         ============================================================================ -->
    <script>
        const ctx = document.getElementById('radarHexagono').getContext('2d');
        const etiquetas = <?= json_encode(['Diseño', 'Pantalla', 'Rendimiento', 'Cámaras', 'Batería', 'Software'], JSON_HEX_TAG | JSON_HEX_AMP) ?>;
        const valores = <?= json_encode($vals) ?>;

        new Chart(ctx, {
            type: 'radar',
            data: {
                labels: etiquetas,
                datasets: [{
                    data: valores,
                    backgroundColor: 'rgba(52, 152, 219, 0.15)',
                    borderColor: 'rgba(255, 255, 255, 0.4)',
                    borderWidth: 2
                }]
            },
            options: {
                scales: {
                    r: { min: 0, max: 10, ticks: { stepSize: 2, display: false } }
                }
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>
