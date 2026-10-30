<?php
/**
 * ============================================================================
 * ADMIN.PHP · PANEL DE ADMINISTRACIÓN (CREAR, EDITAR Y BORRAR MÓVILES)
 * Solo accesible para usuarios administradores.
 * La lógica de guardado está en includes/guardado.php y el formulario en
 * includes/admin_formulario.php; el JavaScript del formulario en js/admin.js.
 * ============================================================================
 *  FASE 1: Permisos y funciones del formulario
 *  FASE 2: Procesar formularios (crear / actualizar / borrar)
 *  FASE 3: Cargar la ficha del móvil a editar o borrar
 *  FASE 4: Datos para el JavaScript y maquetación
 * ============================================================================
 */
require_once __DIR__ . '/config/conexion.php';
require_once __DIR__ . '/includes/funciones.php';
require_once __DIR__ . '/includes/listas.php';
require_once __DIR__ . '/includes/guardado.php';
require_once __DIR__ . '/includes/subida.php';

exigir_admin();
exigir_esquema($conexion);

/**
 * ============================================================================
 * FASE 1: FUNCIONES DEL FORMULARIO
 * ============================================================================
 */

// Valor de un campo del formulario leído de la ficha cargada (editar / borrar)
function v($seccion, $campo, $defecto = '') {
    global $ficha, $modo_admin;
    if ($modo_admin === 'borrar') $defecto = '';
    $x = (isset($ficha[$seccion]) && is_array($ficha[$seccion])) ? ($ficha[$seccion][$campo] ?? null) : null;
    return ($x === null || $x === '') ? escapar($defecto) : escapar($x);
}

// Igual que v(), pero limpiando los ceros de un DECIMAL ("8.50" -> "8.5"). Solo para columnas numéricas.
function vn($seccion, $campo, $defecto = '') {
    global $ficha;
    $x = (isset($ficha[$seccion]) && is_array($ficha[$seccion])) ? ($ficha[$seccion][$campo] ?? null) : null;
    return ($x === null || $x === '') ? escapar($defecto) : escapar(num_limpio($x));
}

$regiones = regiones_precio();
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
 * FASE 2: PROCESAR FORMULARIOS DEL ADMIN (CREAR, ACTUALIZAR, BORRAR)
 * ============================================================================
 */
// Si el envío supera post_max_size de php.ini, PHP descarta todo el formulario sin avisar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST) && empty($_FILES) && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
    $mensaje_error = 'El envío es demasiado grande para la configuración del servidor (post_max_size en php.ini). Prueba con una imagen más pequeña.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    $redirigir = null;
    $imagen_nueva = null;
    try {
        if (!csrf_valido()) {
            throw new Exception('El token de seguridad ha caducado. Recarga la página e inténtalo de nuevo.');
        }
        $accion = (string)$_POST['accion'];

        if ($accion === 'borrar_movil_completo') {
            $phone_id = (int)($_POST['phone_id'] ?? 0);
            if ($phone_id <= 0) throw new Exception('ID de móvil inválido para borrar.');
            $conexion->beginTransaction();
            $imagen_borrada = borrarMovil($conexion, $phone_id);
            $conexion->commit();
            borrar_archivo_subido($imagen_borrada);
            $_SESSION['flash_exito'] = '¡El terminal ha sido borrado permanentemente!';
            $redirigir = 'admin.php?modo=borrar';

        } elseif ($accion === 'crear_movil_completo' || $accion === 'actualizar_movil_completo') {
            $phone_id = ($accion === 'actualizar_movil_completo') ? (int)($_POST['phone_id'] ?? 0) : 0;
            if ($accion === 'actualizar_movil_completo' && $phone_id <= 0) throw new Exception('ID de móvil inválido para actualizar.');

            // La foto se valida y se guarda antes de tocar la base de datos
            $imagen_nueva = procesar_subida_imagen($_FILES['imagen_movil'] ?? null);

            $conexion->beginTransaction();
            $resultado = guardarMovil($conexion, $phone_id, $_POST, $imagen_nueva);
            $conexion->commit();
            borrar_archivo_subido($resultado['imagen_a_borrar']);

            $nombre_movil = trim((string)($_POST['nombre'] ?? ''));
            if ($accion === 'crear_movil_completo') {
                $_SESSION['flash_exito'] = "¡Móvil '" . $nombre_movil . "' registrado con éxito!";
                $redirigir = 'admin.php?modo=crear';
            } else {
                $_SESSION['flash_exito'] = "¡Móvil '" . $nombre_movil . "' actualizado correctamente!";
                $redirigir = 'admin.php?modo=editar&editar_id=' . $resultado['id'];
            }

        } else {
            throw new Exception('Acción no reconocida.');
        }
    } catch (Throwable $e) {
        if ($conexion->inTransaction()) {
            $conexion->rollBack();
        }
        // Si la base de datos falló, la foto recién subida no debe quedarse huérfana
        if ($imagen_nueva !== null) {
            borrar_archivo_subido($imagen_nueva);
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
 * FASE 3: CARGAR LA FICHA DEL MÓVIL A EDITAR O BORRAR
 * ============================================================================
 */
$ficha = [];
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
$form_visible = ($modo_admin === 'crear') || !empty($ficha);

// Listas de autocompletado y móviles existentes (para los selectores de editar, borrar y variantes)
extract(cargar_listas($conexion), EXTR_PREFIX_ALL, 'list');
$todos_los_moviles = $conexion->query("SELECT p.id, p.name, b.name AS marca FROM phones p INNER JOIN series s ON s.id = p.series_id INNER JOIN brands b ON b.id = s.brand_id ORDER BY b.name, p.name")->fetchAll();

/**
 * ============================================================================
 * FASE 4: DATOS PARA EL JAVASCRIPT Y MAQUETACIÓN
 * ============================================================================
 */
$id_actual = !empty($ficha['phone']['id']) ? (int)$ficha['phone']['id'] : 0;

$moviles_js = [];
foreach ($todos_los_moviles as $tm) {
    if ((int)$tm['id'] === $id_actual) continue; // un móvil no puede ser variante de sí mismo
    $moviles_js[] = ['id' => (int)$tm['id'], 'texto' => $tm['marca'] . ' ' . $tm['name']];
}
$variantes_js = [];
foreach (($ficha['variantes'] ?? []) as $vr) {
    $variantes_js[] = ['id' => (int)$vr['id'], 'texto' => $vr['marca'] . ' ' . $vr['name']];
}
$regiones_js = [];
foreach ($regiones as $clave => $info) {
    $regiones_js[] = ['clave' => $clave, 'col' => $info['col'], 'etiqueta' => $info['etiqueta'], 'moneda' => $info['moneda']];
}

$monedas_js = [];
foreach (monedas_disponibles() as $codigo_moneda => $datos_moneda) {
    $monedas_js[] = ['codigo' => $codigo_moneda, 'nombre' => $datos_moneda['nombre']];
}

$datos_admin = [
    'versiones'       => array_map('versionParaForm', $ficha['storage'] ?? []),
    'camaraPrincipal' => !empty($ficha['main_cam']) ? camaraParaForm($ficha['main_cam']) : null,
    'camarasAux'      => array_map('camaraParaForm', $ficha['aux_cams'] ?? []),
    'tiposCamaraAux'  => tipos_camara_auxiliar(),
    'tiposSinVideo'   => tipos_camara_sin_video(),
    'monedas'         => $monedas_js,
    'opcionesSim'     => opciones_sim(),
    'simActual'       => ($modo_admin === 'crear') ? '' : (string)($ficha['conn']['sim_config'] ?? ''),
    'regiones'        => $regiones_js,
    'moviles'         => $moviles_js,
    'variantes'       => $variantes_js,
];

// Datos de la cabecera común
$pagina_actual    = 'admin';
$pagina_titulo    = 'Panel de Administración · Catálogo de Smartphones';
$pagina_h1        = ($modo_admin === 'borrar') ? '🗑️ Panel Admin: Modo Borrado' : '👑 Panel de Administración';
$pagina_subtitulo = 'Crear, editar y borrar móviles del catálogo';
$pagina_css       = ['admin'];

// Scripts del formulario (se imprimen en el pie)
if ($form_visible) {
    ob_start();
    ?>
    <script>
        const ADMIN_DATA = <?= json_encode($datos_admin, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    </script>
    <script src="js/admin.js"></script>
    <?php
    $pagina_scripts = ob_get_clean();
}

require __DIR__ . '/includes/header.php';
?>

    <!-- Listas de autocompletado del formulario -->
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
    <?= datalist('datalist_protection', $list_sug_proteccion) ?>
    <?= datalist('datalist_battery_type', $list_sug_bateria) ?>
    <?= datalist('datalist_marco', $list_sug_marco) ?>
    <?= datalist('datalist_trasera', $list_sug_trasera) ?>

    <main class="contenedor">

        <section class="admin-box <?= $modo_admin === 'borrar' ? 'modo-borrado' : '' ?>">
            <h2>
                <?= $modo_admin === 'borrar' ? '🗑️ Modo Borrado de Registros' : '📋 Registro Técnico Completo' ?>
            </h2>

            <!-- Botones de función del panel: CREAR / EDITAR / BORRAR (el activo se resalta) -->
            <div class="admin-modes-bar">
                <a href="admin.php?modo=crear" class="btn-admin-modo btn-crear<?= $modo_admin === 'crear' ? ' activo' : '' ?>">➕ Crear</a>
                <a href="admin.php?modo=editar" class="btn-admin-modo btn-editar<?= $modo_admin === 'editar' ? ' activo' : '' ?>">✏️ Editar</a>
                <a href="admin.php?modo=borrar" class="btn-admin-modo btn-borrar-modo<?= $modo_admin === 'borrar' ? ' activo' : '' ?>">🗑️ Borrar Móvil</a>
            </div>

            <!-- Selector para el modo edición -->
            <?php if ($modo_admin === 'editar'): ?>
                <div class="admin-selector-box">
                    <form method="GET" action="admin.php">
                        <input type="hidden" name="modo" value="editar">
                        <label>Selecciona un móvil existente para editar:</label>
                        <div class="admin-selector-row">
                            <select name="editar_id" required>
                                <option value="">-- Elige un terminal --</option>
                                <?php foreach ($todos_los_moviles as $te): ?>
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
                    <form method="GET" action="admin.php">
                        <input type="hidden" name="modo" value="borrar">
                        <label>Selecciona el móvil que deseas ELIMINAR:</label>
                        <div class="admin-selector-row">
                            <select name="borrar_id" required>
                                <option value="">-- Elige terminal a borrar --</option>
                                <?php foreach ($todos_los_moviles as $te): ?>
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
                <?php require __DIR__ . '/includes/admin_formulario.php'; ?>
            <?php endif; ?>
        </section>

    </main>

<?php require __DIR__ . '/includes/footer.php'; ?>
