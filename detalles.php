<?php
require_once __DIR__ . '/config/conexion.php';

$sesion_iniciada = isset($_SESSION['user_id']);
$nombre_usuario = $sesion_iniciada ? $_SESSION['username'] : '';
$es_admin = $sesion_iniciada && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;

$id_movil = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id_movil <= 0) {
    die('Móvil no válido o no especificado.');
}

try {
    // 1. Datos base del móvil, marca y familia
    $stmt = $conexion->prepare("
        SELECT p.*, b.name AS marca, s.name AS familia 
        FROM phones p
        INNER JOIN series s ON s.id = p.series_id
        INNER JOIN brands b ON b.id = s.brand_id
        WHERE p.id = ?
    ");
    $stmt->execute([$id_movil]);
    $movil = $stmt->fetch();

    if (!$movil) {
        die('El teléfono solicitado no existe.');
    }

    // 2. Metadata (Dimensiones, IP, Precios, Accesorios, Puntuaciones)
    $stmt_meta = $conexion->prepare("SELECT * FROM phone_metadata WHERE phone_id = ?");
    $stmt_meta->execute([$id_movil]);
    $meta = $stmt_meta->fetch();

    // 3. Software y Soporte
    $stmt_sw = $conexion->prepare("SELECT * FROM software_support WHERE phone_id = ?");
    $stmt_sw->execute([$id_movil]);
    $sw = $stmt_sw->fetch();

    // 4. Características técnicas (Variante)
    $stmt_char = $conexion->prepare("SELECT * FROM phone_characteristics WHERE phone_id = ? LIMIT 1");
    $stmt_char->execute([$id_movil]);
    $char = $stmt_char->fetch();
    $char_id = $char ? $char['id'] : 0;

    $soc = $storage = $scr = $bat = $conn = null;
    $cams = [];
    $colors = [];

    if ($char_id) {
        $soc = $conexion->prepare("SELECT * FROM characteristics_soc WHERE phone_characteristic_id = ?");
        $soc->execute([$char_id]); $soc = $soc->fetch();

        $storage = $conexion->prepare("SELECT * FROM characteristics_storage WHERE phone_characteristic_id = ?");
        $storage->execute([$char_id]); $storage = $storage->fetch();

        $scr = $conexion->prepare("SELECT * FROM characteristics_screen WHERE phone_characteristic_id = ?");
        $scr->execute([$char_id]); $scr = $scr->fetch();

        $bat = $conexion->prepare("SELECT * FROM characteristics_battery WHERE phone_characteristic_id = ?");
        $bat->execute([$char_id]); $bat = $bat->fetch();

        $conn = $conexion->prepare("SELECT * FROM characteristics_connectivity WHERE phone_characteristic_id = ?");
        $conn->execute([$char_id]); $conn = $conn->fetch();

        $cams = $conexion->prepare("
            SELECT * FROM camera_modules 
            WHERE phone_characteristic_id = ? 
            ORDER BY 
                CASE 
                    WHEN camera_type = 'Ultra gran angular' THEN 1
                    WHEN camera_type = 'Principal' THEN 2
                    WHEN camera_type LIKE '%Teleobjetivo%' THEN 3
                    ELSE 4
                END ASC,
                focal_length_mm ASC
        ");
        $cams->execute([$char_id]); 
        $cams = $cams->fetchAll();

        $colors = $conexion->prepare("SELECT * FROM colors WHERE phone_characteristic_id = ?");
        $colors->execute([$char_id]); $colors = $colors->fetchAll();
    }

    // 5. Puntuaciones del Hexágono desde phone_metadata
    $cats = ['Diseño', 'Pantalla', 'Rendimiento', 'Cámaras', 'Batería', 'Software'];
    $vals = [
        $meta && isset($meta['score_1']) ? (float)$meta['score_1'] : 0,
        $meta && isset($meta['score_2']) ? (float)$meta['score_2'] : 0,
        $meta && isset($meta['score_3']) ? (float)$meta['score_3'] : 0,
        $meta && isset($meta['score_4']) ? (float)$meta['score_4'] : 0,
        $meta && isset($meta['score_5']) ? (float)$meta['score_5'] : 0,
        $meta && isset($meta['score_6']) ? (float)$meta['score_6'] : 0,
    ];

    $tiene_notas = false;
    foreach ($vals as $v) {
        if ($v > 0) {
            $tiene_notas = true;
            break;
        }
    }

    $media = ($meta && isset($meta['score_avg']) && $meta['score_avg'] !== null && $meta['score_avg'] > 0) ? $meta['score_avg'] : "Sin nota";
    $es_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;

} catch (PDOException $e) {
    die('Error al cargar la ficha técnica: ' . $e->getMessage());
}

function escapar($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= escapar($movil['name']) ?> | Ficha Técnica Completa</title>
    <link rel="stylesheet" href="css/estilos.css">
    <script src="css/chart.js"></script>
    <style>
        .grid-detalles-completo { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px; margin-top: 20px; }
        .lista-specs-detallada { list-style: none; padding: 0; line-height: 2.2; }
        .lista-specs-detallada li { border-bottom: 1px dashed #e2e8f0; display: flex; justify-content: space-between; font-size: 0.95rem; }
        .contenedor-imagen-movil {
            width: 280px;         
            height: 320px;        
            background: #f8fafc;  
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            margin: 10px auto;
        }

        .contenedor-imagen-movil img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;  
        }
    </style>
</head>
<body>

    <header class="cabecera">
        <div class="contenedor">
            <!-- Fila superior idéntica a la página principal -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <p class="sobrelinea" style="margin: 0;">
                    <a href="index.php" style="color: #a9c5ff; text-decoration: none;">&larr; VOLVER AL CATÁLOGO</a>
                </p>
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

            <!-- Fila principal estructurada con los 3 bloques -->
            <div style="display: grid; grid-template-columns: 1.2fr 0.8fr 1fr; align-items: center; gap: 20px; margin-top: 10px;">
                
                <!-- 1. BLOQUE IZQUIERDO: Nombre y Subtítulo -->
                <div>
                    <h1 style="margin: 0 0 8px 0; font-size: 2.2rem; line-height: 1.1; color: #ffffff;"><?= escapar($movil['name']) ?></h1>
                    <p class="subtitulo" style="margin: 0; font-size: 0.85rem; color: #a9c5ff; opacity: 0.9;">
                        Familia: <?= escapar($movil['familia']) ?> | Lanzamiento: <?= escapar($movil['launch_year']) ?> | Gama: <?= !empty($movil['tier']) ? escapar($movil['tier']) : 'No especificada' ?>
                    </p>
                </div>

                <!-- 2. BLOQUE CENTRAL: Fotografía del Móvil -->
                <div style="display: flex; justify-content: center; align-items: center; height: 150px;">
                    <?php if (!empty($meta['image_url'])): ?>
                        <img src="<?= escapar($meta['image_url']) ?>" alt="Foto de <?= escapar($movil['name']) ?>" style="max-height: 145px; max-width: 130px; object-fit: contain; filter: drop-shadow(0 8px 16px rgba(0,0,0,0.4)); border-radius: 6px;">
                    <?php else: ?>
                        <span style="color: #64748b; font-size: 0.85rem; font-style: italic;">Sin imagen</span>
                    <?php endif; ?>
                </div>

                <!-- 3. BLOQUE DERECHO: Diagrama Hexagonal y Valoración -->
                <div style="display: flex; flex-direction: column; align-items: flex-end; text-align: right;">
                    <div class="header-diagrama-caja" style="margin: 0; width: 190px; height: 140px; position: relative;">
                        <div class="contenedor-canvas" style="width: 100%; height: 100%;">
                            <canvas id="radarHexagono"></canvas>
                        </div>
                    </div>
                    <p class="sobrelinea" style="color: #a9c5ff; margin: 2px 0 0 0; line-height: 1;">
                        VALORACIÓN: <strong style="color: #ffffff; font-size: inherit; font-weight: inherit;"><?= $media ?><?php if($tiene_notas): ?>/10<?php endif; ?></strong>
                    </p>
                </div>
            </div>
        </div>
    </header>

    <main class="contenedor espacio-cuerpo">
        <div class="grid-detalles-completo">

            <!-- 1. IDENTIDAD Y DISEÑO (METADATA) -->
            <section class="tarjeta">
                <h2>📐 Diseño y Dimensiones</h2>
                <ul class="lista-specs-detallada">
                    <li><span>Alto:</span> <strong><?= $meta ? escapar($meta['height_mm']) . ' mm' : 'N/D' ?></strong></li>
                    <li><span>Ancho:</span> <strong><?= $meta ? escapar($meta['width_mm']) . ' mm' : 'N/D' ?></strong></li>
                    <li><span>Grosor del cuerpo:</span> <strong><?= $meta ? escapar($meta['thickness_body_mm']) . ' mm' : 'N/D' ?></strong></li>
                    <li><span>Grosor con módulo cámara:</span> <strong><?= $meta ? escapar($meta['thickness_camera_extra_mm']) . ' mm' : 'N/D' ?></strong></li>
                    <li><span>Certificación IP:</span> <strong><?= $meta ? escapar($meta['ip_rating']) : 'N/D' ?></strong></li>
                </ul>
            </section>

            <!-- 2. SOFTWARE Y SOPORTE -->
            <section class="tarjeta">
                <h2>🤖 Software y Actualizaciones</h2>
                <ul class="lista-specs-detallada">
                    <li><span>Sistema Operativo Inicial:</span> <strong><?= $sw ? escapar($sw['initial_os']) : 'N/D' ?></strong></li>
                    <li><span>Capa de Personalización:</span> <strong><?= $sw ? escapar($sw['initial_skin']) : 'N/D' ?></strong></li>
                    <li><span>Años Act. Sistema / Capa:</span> <strong><?= $sw ? escapar($sw['os_and_skin_update_years']) . ' años' : 'N/D' ?></strong></li>
                    <li><span>Parches de Seguridad:</span> <strong><?= $sw ? escapar($sw['security_update_years']) . ' años' : 'N/D' ?></strong></li>
                </ul>
            </section>

            <!-- 3. PROCESADOR (SOC) Y RENDIMIENTO -->
            <section class="tarjeta">
                <h2>⚡ Procesador (SoC) y Benchmarks</h2>
                <ul class="lista-specs-detallada">
                    <li><span>Procesador:</span> <strong><?= $soc ? escapar($soc['brand']) . ' ' . escapar($soc['full_name']) : 'N/D' ?></strong></li>
                    <li><span>Arquitectura:</span> <strong><?= $soc ? escapar($soc['architecture_nm']) . ' nm' : 'N/D' ?></strong></li>
                    <li><span>AnTuTu:</span> <strong><?= $soc ? number_format($soc['antutu_score']) : 'N/D' ?></strong></li>
                    <li><span>Geekbench Mononúcleo:</span> <strong><?= $soc ? escapar($soc['geekbench_single']) : 'N/D' ?></strong></li>
                    <li><span>Geekbench Multinúcleo:</span> <strong><?= $soc ? escapar($soc['geekbench_multi']) : 'N/D' ?></strong></li>
                    <li><span>Potencia Gráfica (GPU):</span> <strong><?= $soc ? escapar($soc['gpu_tflops']) . ' TFLOPS' : 'N/D' ?></strong></li>
                    <li><span>Inteligencia Artificial (NPU):</span> <strong><?= $soc ? escapar($soc['npu_tops']) . ' TOPS' : 'N/D' ?></strong></li>
                </ul>
            </section>

            <!-- 4. ALMACENAMIENTO Y RAM -->
            <section class="tarjeta">
                <h2>💾 Memoria y Almacenamiento</h2>
                <ul class="lista-specs-detallada">
                    <li><span>Capacidades RAM:</span> <strong><?= $storage ? escapar($storage['ram_capacities_gb']) : 'N/D' ?></strong></li>
                    <li><span>Tecnología RAM:</span> <strong><?= $storage ? escapar($storage['ram_technology']) : 'N/D' ?></strong></li>
                    <li><span>Almacenamiento Interno:</span> <strong><?= $storage ? escapar($storage['storage_capacities_gb']) : 'N/D' ?></strong></li>
                    <li><span>Tecnología Almacenamiento:</span> <strong><?= $storage ? escapar($storage['storage_technology']) : 'N/D' ?></strong></li>
                </ul>
            </section>

            <!-- 5. PANTALLA -->
            <section class="tarjeta">
                <h2>📱 Pantalla y Visualización</h2>
                <ul class="lista-specs-detallada">
                    <li><span>Tamaño:</span> <strong><?= $scr ? escapar($scr['inches']) . '"' : 'N/D' ?></strong></li>
                    <li><span>Tecnología:</span> <strong><?= $scr ? escapar($scr['technology']) : 'N/D' ?></strong></li>
                    <li><span>Resolución:</span> <strong><?= $scr ? escapar($scr['resolution']) : 'N/D' ?></strong></li>
                    <li><span>Tasa de Refresco:</span> <strong><?= $scr ? escapar($scr['refresh_rate_hz']) . ' Hz' : 'N/D' ?></strong></li>
                    <li><span>Brillo Máximo:</span> <strong><?= $scr ? escapar($scr['max_brightness_nits']) . ' nits' : 'N/D' ?></strong></li>
                    <li><span>Protección Cristal:</span> <strong><?= $scr ? escapar($scr['resistance_certificate']) : 'N/D' ?></strong></li>
                    <li><span>Superficie Frontal Útil:</span> <strong><?= $scr ? escapar($scr['screen_to_body_ratio']) . '%' : 'N/D' ?></strong></li>
                </ul>
            </section>

            <!-- 6. BATERÍA Y CARGA -->
            <section class="tarjeta">
                <h2>🔋 Batería y Autonomía</h2>
                <ul class="lista-specs-detallada">
                    <li><span>Capacidad:</span> <strong><?= $bat ? escapar($bat['capacity_mah']) . ' mAh' : 'N/D' ?></strong></li>
                    <li><span>Carga por Cable:</span> <strong><?= $bat ? escapar($bat['wired_charging_w']) . 'W' : 'N/D' ?></strong></li>
                    <li><span>Carga Inalámbrica:</span> <strong><?= $bat ? escapar($bat['wireless_charging_w']) . 'W' : 'N/D' ?></strong></li>
                    <li><span>Carga Inversa (Cable/Inál.):</span> <strong><?= $bat ? escapar($bat['reverse_wired_charging_w']) . 'W / ' . escapar($bat['reverse_wireless_charging_w']) . 'W' : 'N/D' ?></strong></li>
                    <li><span>Ciclos Vida (80% salud):</span> <strong><?= $bat ? escapar($bat['assured_cycles_80_health']) . ' ciclos' : 'N/D' ?></strong></li>
                </ul>
            </section>

            <!-- 7. CONECTIVIDAD -->
            <section class="tarjeta">
                <h2>📡 Conectividad y Redes</h2>
                <ul class="lista-specs-detallada">
                    <li><span>Red Móvil:</span> <strong><?= $conn ? escapar($conn['mobile_network']) : 'N/D' ?></strong></li>
                    <li><span>Wi-Fi:</span> <strong><?= $conn ? escapar($conn['wifi_version']) : 'N/D' ?></strong></li>
                    <li><span>Bluetooth:</span> <strong><?= $conn ? escapar($conn['bluetooth_version']) : 'N/D' ?></strong></li>
                    <li><span>NFC / eSIM:</span> <strong><?= $conn ? ($conn['nfc'] ? 'Sí' : 'No') . ' / ' . ($conn['esim_support'] ? 'Compatible' : 'No') : 'N/D' ?></strong></li>
                </ul>
            </section>

            <!-- 8. PRECIOS Y COMERCIALIZACIÓN -->
            <section class="tarjeta">
                <h2>💶 Precios de Lanzamiento</h2>
                <ul class="lista-specs-detallada">
                    <li><span>Precio España (€):</span> <strong><?= $meta && $meta['price_eur'] ? number_format($meta['price_eur'], 2) . ' €' : 'No especificado' ?></strong></li>
                    <li><span>Precio China (¥):</span> <strong><?= $meta && $meta['price_cny'] ? number_format($meta['price_cny'], 2) . ' ¥' : 'No especificado' ?></strong></li>
                    <li><span>Accesorios Oficiales:</span> <strong><?= $meta ? escapar($meta['official_accessories']) : 'Ninguno' ?></strong></li>
                </ul>
            </section>

        </div>

        <!-- 9. CÁMARAS Y VÍDEO AVANZADO -->
        <section class="tarjeta" style="margin-top: 20px;">
            <h2>📸 Sistema de Cámaras y Capacidades de Vídeo</h2>
            <?php if (!empty($cams)): ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 15px; margin-top: 15px;">
                    <?php foreach ($cams as $c): ?>
                        <div style="background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0;">
                            <h3 style="margin-top: 0; color: #3264b3; font-size: 1.1rem;"><?= escapar($c['camera_type']) ?></h3>
                            <p style="margin: 5px 0;"><strong>Sensor:</strong> <?= escapar($c['sensor_name']) ?> (<?= escapar($c['sensor_size']) ?>)</p>
                            <p style="margin: 5px 0;"><strong>Resolución:</strong> <?= escapar($c['resolution_mp']) ?> MP</p>
                            <p style="margin: 5px 0;"><strong>Apertura / Focal:</strong> <?= escapar($c['aperture']) ?> | <?= escapar($c['focal_length_mm']) ?> mm</p>
                            <hr style="border: 0; border-top: 1px solid #cbd5e1; margin: 10px 0;">
                            <p style="margin: 5px 0; font-size: 0.9rem;"><strong>Vídeo Normal:</strong> <?= escapar($c['normal_video_max']) ?></p>
                            <p style="margin: 5px 0; font-size: 0.9rem;"><strong>LOG:</strong> <?= $c['has_log'] ? escapar($c['log_specs']) : 'No tiene' ?></p>
                            <p style="margin: 5px 0; font-size: 0.9rem;"><strong>OpenGate:</strong> <?= $c['has_opengate'] ? escapar($c['opengate_specs']) : 'No tiene' ?></p>
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

    <script>
        const ctx = document.getElementById('radarHexagono').getContext('2d');
        const etiquetas = <?= json_encode($cats) ?>;
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
                    r: {
                        min: 0,
                        max: 10,
                        ticks: {
                            stepSize: 2,
                            display: false
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>