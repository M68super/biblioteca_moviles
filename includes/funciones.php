<?php
/**
 * ============================================================================
 * FUNCIONES COMPARTIDAS POR TODAS LAS PÁGINAS
 * (se incluye siempre después de config/conexion.php)
 * ============================================================================
 */

/* ---------------------------------------------------------------------------
   TEXTO Y CONVERSIÓN DE DATOS
   --------------------------------------------------------------------------- */

// Escape de seguridad para cualquier texto que se imprima en HTML
function escapar($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// Conversores de datos de formulario: devuelven null si el campo viene vacío
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

// Regiones de precio (columnas de phone_metadata y de characteristics_storage_versions)
function regiones_precio() {
    return [
        'global' => ['etiqueta' => '🌍 Global (precio en España)', 'col' => 'price_global', 'moneda' => '€',   'corto' => 'España / Global (€)', 'simbolo' => '€'],
        'ue'     => ['etiqueta' => '🇪🇺 UE (€)',                    'col' => 'price_ue',     'moneda' => '€',   'corto' => 'Unión Europea (€)',   'simbolo' => '€'],
        'china'  => ['etiqueta' => '🇨🇳 China',                    'col' => 'price_china',  'moneda' => 'CNY', 'corto' => 'China (CNY)',         'simbolo' => '¥'],
        'india'  => ['etiqueta' => '🇮🇳 India',                    'col' => 'price_india',  'moneda' => 'INR', 'corto' => 'India (INR)',         'simbolo' => '₹'],
        'japan'  => ['etiqueta' => '🇯🇵 Japón',                    'col' => 'price_japan',  'moneda' => 'JPY', 'corto' => 'Japón (JPY)',         'simbolo' => '¥'],
        'korea'  => ['etiqueta' => '🇰🇷 Corea',                    'col' => 'price_korea',  'moneda' => 'KRW', 'corto' => 'Corea (KRW)',         'simbolo' => '₩'],
        'usa'    => ['etiqueta' => '🇺🇸 EE. UU.',                  'col' => 'price_usa',    'moneda' => 'USD', 'corto' => 'EE. UU. (USD)',       'simbolo' => '$'],
        'latam'  => ['etiqueta' => '🇲🇽 Latam',                    'col' => 'price_latam',  'moneda' => 'MXN', 'corto' => 'Latam (MXN)',         'simbolo' => 'MXN'],
        'taiwan' => ['etiqueta' => '🇹🇼 Taiwán',                   'col' => 'price_taiwan', 'moneda' => 'TWD', 'corto' => 'Taiwán (TWD)',        'simbolo' => 'TWD'],
        'russia' => ['etiqueta' => '🇷🇺 Rusia',                    'col' => 'price_russia', 'moneda' => 'RUB', 'corto' => 'Rusia (RUB)',        'simbolo' => '₽'],
    ];
}

// Prioridades de compra (solo las ve el administrador). La clave es lo que se guarda en la base de datos.
function prioridades() {
    return [
        'ninguna'  => 'Ninguna',
        'baja'     => 'Baja',
        'media'    => 'Media',
        'alta'     => 'Alta',
        'obtenido' => 'Obtenido',
    ];
}

// Monedas entre las que se puede elegir en el precio "exclusivo operadora" de cada versión (las mismas de las regiones)
function monedas_disponibles() {
    return [
        'EUR' => ['simbolo' => '€',   'nombre' => 'EUR · Euro'],
        'CNY' => ['simbolo' => '¥',   'nombre' => 'CNY · Yuan chino'],
        'INR' => ['simbolo' => '₹',   'nombre' => 'INR · Rupia india'],
        'JPY' => ['simbolo' => '¥',   'nombre' => 'JPY · Yen japonés'],
        'KRW' => ['simbolo' => '₩',   'nombre' => 'KRW · Won coreano'],
        'USD' => ['simbolo' => '$',   'nombre' => 'USD · Dólar estadounidense'],
        'MXN' => ['simbolo' => 'MXN', 'nombre' => 'MXN · Peso mexicano'],
        'TWD' => ['simbolo' => 'TWD', 'nombre' => 'TWD · Nuevo dólar taiwanés'],
        'RUB' => ['simbolo' => '₽',   'nombre' => 'RUB · Rublo ruso'],
    ];
}

// Símbolo de una moneda a partir de su código (si no se conoce, se devuelve el propio código)
function simbolo_moneda($codigo) {
    $monedas = monedas_disponibles();
    return isset($monedas[$codigo]) ? $monedas[$codigo]['simbolo'] : (string)$codigo;
}

// Tipos de cámara en el orden en que aparecen en el panel admin
function tipos_camara_auxiliar() {
    return [
        'Ultra Gran Angular', 'Principal', 'Teleobjetivo Telescópico', 'Teleobjetivo Periscópico',
        'Selfie', 'Selfie Bajo Pantalla', 'Macro', 'Infrarrojos', 'Monocromo', 'Multiespectral',
        'RGB Ambiental', 'Biométrico (Face ID)', 'ToF 3D', 'LiDAR', 'Térmico', 'Microscópico',
        'Anti-Parpadeo (Flicker Sensor)',
    ];
}

// Tipos de cámara que son sensores y NO graban vídeo: en el panel admin y en la ficha no se muestra su apartado de vídeo
function tipos_camara_sin_video() {
    return [
        'Infrarrojos', 'Monocromo', 'Multiespectral', 'RGB Ambiental', 'Biométrico (Face ID)',
        'ToF 3D', 'LiDAR', 'Anti-Parpadeo (Flicker Sensor)',
    ];
}

// Convierte un tipo guardado con otra forma de escribirlo ("ultra gran angular", "Infrarojos", "Lidar") al nombre oficial de la lista
function canonizar_tipo_camara($tipo) {
    $tipo = trim((string)$tipo);
    $clave = mb_strtolower($tipo, 'UTF-8');
    foreach (tipos_camara_auxiliar() as $oficial) {
        if (mb_strtolower($oficial, 'UTF-8') === $clave) return $oficial;
    }
    $alias = ['infrarojos' => 'Infrarrojos', 'microscopico' => 'Microscópico', 'tof 3d' => 'ToF 3D'];
    return $alias[$clave] ?? $tipo;
}

// ¿Este tipo de cámara puede grabar vídeo? (los tipos desconocidos se consideran que sí, para no perder datos)
function tipo_camara_tiene_video($tipo) {
    return !in_array(canonizar_tipo_camara($tipo), tipos_camara_sin_video(), true);
}

// Opciones de la tarjeta SIM. Las que contienen "MicroSD" solo se ofrecen si el móvil tiene ranura MicroSD
function opciones_sim() {
    return [
        '1 SIM',
        'Dual SIM',
        'SIM + MicroSD',
        'Dual SIM + MicroSD',
        'Dual SIM híbrido (SIM 2 o MicroSD)',
        'Solo MicroSD (sin SIM)',
        'Sin SIM física (solo eSIM)',
    ];
}

function sim_requiere_microsd($opcion) {
    return stripos((string)$opcion, 'MicroSD') !== false;
}

// Dirección segura para un <img src>: solo rutas subidas (uploads/...) o enlaces http(s). Si no, vacío.
function url_imagen($v) {
    $v = trim((string)$v);
    if ($v === '') return '';
    if (preg_match('#^uploads/moviles/[A-Za-z0-9._-]+$#', $v)) return $v;
    if (preg_match('#^https?://#i', $v)) return $v;
    return '';
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

/* ---------------------------------------------------------------------------
   SESIÓN Y PERMISOS
   --------------------------------------------------------------------------- */
function usuario_logueado() { return isset($_SESSION['user_id']); }
function es_admin() { return usuario_logueado() && !empty($_SESSION['is_admin']); }

// Para páginas solo de administrador: si no lo es, se le manda al login
function exigir_admin() {
    if (!es_admin()) {
        header('Location: login.php');
        exit;
    }
}

/* ---------------------------------------------------------------------------
   PROTECCIÓN CSRF (todos los formularios POST llevan un token de la sesión)
   --------------------------------------------------------------------------- */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Campo oculto que se mete dentro de cada <form method="POST">
function csrf_campo() {
    return '<input type="hidden" name="csrf_token" value="' . escapar(csrf_token()) . '">';
}

function csrf_valido() {
    $enviado = $_POST['csrf_token'] ?? '';
    return is_string($enviado) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $enviado);
}

// Para páginas sencillas: corta con un error 403 si el token no es válido
function exigir_csrf() {
    if (!csrf_valido()) {
        http_response_code(403);
        exit('Petición no válida: el token de seguridad ha caducado o es incorrecto. Vuelve atrás, recarga la página e inténtalo de nuevo.');
    }
}

/* ---------------------------------------------------------------------------
   COMPROBACIÓN DE LA ESTRUCTURA DE LA BASE DE DATOS
   Si falta alguna columna por una migración sin ejecutar, se avisa claramente
   en vez de dejar que la página falle a medias.
   --------------------------------------------------------------------------- */
function esquema_requerido() {
    $columnas_precio = array_column(regiones_precio(), 'col');
    return [
        'phones' => ['operative_system_id', 'variant_group_id'],
        'phone_metadata' => ['score_1', 'score_2', 'score_3', 'score_4', 'score_5', 'score_6', 'personal_comment', 'priority', 'owned_colors'],
        'characteristics_storage_versions' => array_merge(['ram_gb', 'storage_gb', 'price_carrier', 'carrier_currency', 'carrier_details'], $columnas_precio),
        'characteristics_screen' => ['max_brightness_nits', 'protection'],
        'characteristics_battery' => ['battery_type', 'assured_cycles_80_health'],
        'characteristics_connectivity' => ['android_auto', 'microsd', 'sim_config'],
        'camera_modules' => ['normal_video_max_2', 'log_resolution_2', 'log_fps_2', 'opengate_resolution_2', 'opengate_fps_2', 'opengate_log_resolution_2', 'opengate_log_fps_2'],
    ];
}

function esquema_faltante(PDO $conexion) {
    $requerido = esquema_requerido();
    $tablas = array_keys($requerido);
    $marcas = implode(',', array_fill(0, count($tablas), '?'));
    $q = $conexion->prepare("SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ($marcas)");
    $q->execute($tablas);
    $existe = [];
    foreach ($q->fetchAll() as $fila) {
        $existe[$fila['TABLE_NAME']][$fila['COLUMN_NAME']] = true;
    }
    $falta = [];
    foreach ($requerido as $tabla => $columnas) {
        foreach ($columnas as $col) {
            if (empty($existe[$tabla][$col])) $falta[] = $tabla . '.' . $col;
        }
    }
    return $falta;
}

// Se llama al principio de cada página. Si todo está bien no hace nada (y recuerda el resultado 5 minutos).
function exigir_esquema(PDO $conexion) {
    if (!empty($_SESSION['esquema_ok_hasta']) && $_SESSION['esquema_ok_hasta'] > time()) return;
    try {
        $falta = esquema_faltante($conexion);
    } catch (PDOException $e) {
        $falta = ['(no se pudo comprobar la base de datos: ' . $e->getMessage() . ')'];
    }
    if (!$falta) {
        $_SESSION['esquema_ok_hasta'] = time() + 300;
        return;
    }
    http_response_code(500);
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Base de datos desactualizada</title></head>';
    echo '<body style="font-family: Segoe UI, Arial, sans-serif; max-width: 720px; margin: 60px auto; padding: 0 20px; line-height: 1.6;">';
    if (es_admin()) {
        echo '<h1>Base de datos desactualizada</h1>';
        echo '<p>Faltan estas columnas: <code>' . escapar(implode(', ', $falta)) . '</code></p>';
        echo '<p>Ejecuta en phpMyAdmin, por este orden y solo los que no hayas ejecutado ya: ';
        echo '<code>migracion_notas.sql</code>, <code>migracion_bateria_video_auto.sql</code>, <code>migracion_2026-10-23.sql</code> y <code>migracion_2026-10-24.sql</code>.</p>';
    } else {
        echo '<h1>Catálogo en mantenimiento</h1><p>Estamos actualizando la base de datos. Vuelve en unos minutos.</p>';
    }
    echo '</body></html>';
    exit;
}
