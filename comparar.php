<?php
/**
 * ============================================================================
 * COMPARAR.PHP · COMPARADOR AVANZADO DE SMARTPHONES
 * Adaptado a la estructura de base de datos: estructura_2026-10-20.sql
 * ============================================================================
 *  FASE 1: Configuración, sesión y conexión
 *  FASE 2: Carga de dispositivos y selección para la comparativa
 *  FASE 3: Funciones auxiliares del análisis comparativo
 *  FASE 4: Maquetación y script de filtrado de los selectores
 * ============================================================================
 */
require_once __DIR__ . '/config/conexion.php';

$sesion_iniciada = isset($_SESSION['user_id']);
$nombre_usuario = $sesion_iniciada ? (string)($_SESSION['username'] ?? '') : '';
$es_admin = $sesion_iniciada && !empty($_SESSION['is_admin']);

/**
 * ============================================================================
 * FASE 3 (se declaran antes de usarlas): FUNCIONES AUXILIARES
 * ============================================================================
 */
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

function valOrDefault($v) {
    if ($v === null || $v === '' || $v === 'N/D') return 'N/D';
    return $v;
}

// Comprueba si los valores entre los móviles difieren para resaltar la fila
function valoresDifieren($valores) {
    if (count($valores) <= 1) return false;
    $primer_valor = $valores[0];
    foreach ($valores as $v) {
        if ($v !== $primer_valor) return true;
    }
    return false;
}

// Mayor número de un texto tipo "8, 12, 16" (para colorear RAM y almacenamiento)
function max_csv($txt) {
    $max = null;
    foreach (explode(',', (string)$txt) as $parte) {
        $parte = trim($parte);
        if ($parte !== '' && is_numeric($parte) && ($max === null || (float)$parte > $max)) {
            $max = (float)$parte;
        }
    }
    return $max;
}

// Precio con símbolo: sin decimales si es redondo
function formato_precio($v, $simbolo) {
    $v = (float)$v;
    $dec = (floor($v) == $v) ? 0 : 2;
    return number_format($v, $dec, ',', '.') . ' ' . $simbolo;
}

// Consulta preparada: una fila (o [] si no hay) o todas las filas
function consulta(PDO $conexion, $sql, $id, $todas = false) {
    $q = $conexion->prepare($sql);
    $q->execute([$id]);
    if ($todas) return $q->fetchAll();
    $fila = $q->fetch();
    return $fila ? $fila : [];
}

/**
 * ============================================================================
 * FASE 2: CARGA DE DISPOSITIVOS Y SELECCIÓN PARA COMPARATIVA
 * ============================================================================
 */
try {
    $stmt_all = $conexion->query("
        SELECT p.id, p.name, b.name AS marca
        FROM phones p
        INNER JOIN series s ON s.id = p.series_id
        INNER JOIN brands b ON b.id = s.brand_id
        ORDER BY b.name ASC, p.name ASC
    ");
    $todos_los_moviles = $stmt_all->fetchAll();
} catch (PDOException $e) {
    die('Error al cargar la lista de móviles: ' . escapar($e->getMessage()));
}

// Capturar hasta 6 IDs por GET. Se aceptan los dos formatos: ids[]=1&ids[]=2 e ids=1,2,3
$ids_brutos = $_GET['ids'] ?? [];
if (!is_array($ids_brutos)) {
    $ids_brutos = explode(',', (string)$ids_brutos);
}
$ids_seleccionados = [];
foreach ($ids_brutos as $id) {
    if (!is_scalar($id)) continue;
    $id_int = (int)$id;
    if ($id_int > 0 && !in_array($id_int, $ids_seleccionados, true)) {
        $ids_seleccionados[] = $id_int;
    }
}
$ids_seleccionados = array_slice($ids_seleccionados, 0, 6);

// Carga detallada de especificaciones técnicas para cada móvil seleccionado
$moviles_comparacion = [];
try {
    foreach ($ids_seleccionados as $id) {
        $m = consulta($conexion, "
            SELECT p.*, b.name AS marca, s.name AS familia
            FROM phones p
            INNER JOIN series s ON s.id = p.series_id
            INNER JOIN brands b ON b.id = s.brand_id
            WHERE p.id = ?
        ", $id);
        if (empty($m)) continue;

        $m['meta'] = consulta($conexion, "SELECT * FROM phone_metadata WHERE phone_id = ? ORDER BY id LIMIT 1", $id);
        $m['sw']   = consulta($conexion, "SELECT * FROM software_support WHERE phone_id = ? ORDER BY id LIMIT 1", $id);

        $char = consulta($conexion, "SELECT id FROM phone_characteristics WHERE phone_id = ? ORDER BY id LIMIT 1", $id);
        $char_id = $char ? (int)$char['id'] : 0;

        $m['soc'] = $m['scr'] = $m['bat'] = $m['conn'] = [];
        $m['storage_versions'] = [];
        if ($char_id) {
            $m['soc']  = consulta($conexion, "SELECT * FROM characteristics_soc WHERE phone_characteristic_id = ? ORDER BY id LIMIT 1", $char_id);
            $m['scr']  = consulta($conexion, "SELECT * FROM characteristics_screen WHERE phone_characteristic_id = ? ORDER BY id LIMIT 1", $char_id);
            $m['bat']  = consulta($conexion, "SELECT * FROM characteristics_battery WHERE phone_characteristic_id = ? ORDER BY id LIMIT 1", $char_id);
            $m['conn'] = consulta($conexion, "SELECT * FROM characteristics_connectivity WHERE phone_characteristic_id = ? ORDER BY id LIMIT 1", $char_id);
            // Versiones múltiples de memoria RAM y almacenamiento
            $m['storage_versions'] = consulta($conexion, "SELECT * FROM characteristics_storage_versions WHERE phone_characteristic_id = ? ORDER BY id", $char_id, true);
        }

        $moviles_comparacion[] = $m;
    }
} catch (PDOException $e) {
    die('Error al cargar los datos de la comparativa: ' . escapar($e->getMessage()));
}

$num_activos = count($moviles_comparacion);
$usar_largo = ($num_activos <= 3);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comparador Avanzado de Smartphones</title>
    <link rel="stylesheet" href="css/estilos.css">
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
            <div>
                <h1>⚖️ Comparador de Smartphones</h1>
                <p class="subtitulo">Selecciona hasta 6 dispositivos para contrastar sus especificaciones técnicas cara a cara.</p>
            </div>
        </div>
    </header>

    <main class="contenedor espacio-cuerpo">

        <!-- Formulario de Selección de Dispositivos (Slots de Comparación) -->
        <form method="GET" action="comparar.php">
            <div class="selector-grid">
                <?php for ($i = 0; $i < 6; $i++):
                    $selected_id = $ids_seleccionados[$i] ?? 0;
                ?>
                    <div class="selector-slot">
                        <label>Dispositivo <?= $i + 1 ?></label>
                        <input type="text" placeholder="🔍 Filtrar..." id="buscador-slot-<?= $i ?>" onkeyup="filtrarSlot(<?= $i ?>)">
                        <select name="ids[]" id="select_movil_<?= $i ?>">
                            <option value="">-- Seleccionar --</option>
                            <?php foreach ($todos_los_moviles as $mov): ?>
                                <option value="<?= (int)$mov['id'] ?>" <?= $selected_id == $mov['id'] ? 'selected' : '' ?>>
                                    <?= escapar($mov['marca'] . ' ' . $mov['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endfor; ?>
            </div>
            <div class="comparar-acciones-row">
                <button type="submit" class="btn-accion">Comparar Dispositivos</button>
                <?php if (!empty($ids_seleccionados)): ?>
                    <a href="comparar.php" class="enlace-limpiar-comparacion">Limpiar comparación</a>
                <?php endif; ?>
            </div>
        </form>

        <!-- Tabla Comparativa Cara a Cara -->
        <?php if (!empty($moviles_comparacion)): ?>
            <div class="tabla-comparativa-wrapper">
                <table class="tabla-comparativa">
                    <thead>
                        <tr>
                            <th>Modelo</th>
                            <?php foreach ($moviles_comparacion as $m): ?>
                                <th title="<?= escapar($m['marca'] . ' ' . $m['name']) ?>">
                                    <div class="th-year"><?= escapar($m['launch_year']) ?></div>
                                    <div class="th-title"><?= escapar($m['marca'] . ' ' . $m['name']) ?></div>
                                    <a href="detalles.php?id=<?= (int)$m['id'] ?>" target="_blank" class="th-ficha-link">Ficha</a>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        /**
                         * Cada fila: [etiqueta larga, etiqueta corta, textos a mostrar, ¿es imagen?,
                         *             ¿lleva gradiente rojo→verde?, ¿excluir resalte ámbar?, valores numéricos]
                         * Los valores numéricos salen directamente de la base de datos (no del texto formateado).
                         */
                        $filas_datos = [];

                        // 1. Fotografía
                        $vals = [];
                        foreach ($moviles_comparacion as $m) $vals[] = !empty($m['meta']['image_url']) ? $m['meta']['image_url'] : '';
                        $filas_datos[] = ['Fotografía', 'Foto', $vals, true, false, true, []];

                        // 2. Nota Media
                        $vals = []; $nums = [];
                        foreach ($moviles_comparacion as $m) {
                            $nota = (float)($m['meta']['score_avg'] ?? 0);
                            $vals[] = $nota > 0 ? num_limpio($m['meta']['score_avg']) . '/10' : 'N/D';
                            $nums[] = $nota > 0 ? $nota : null;
                        }
                        $filas_datos[] = ['Nota Media', 'Media', $vals, false, true, false, $nums];

                        // 3. Gama
                        $mapa_gama = ['Entrada' => 1, 'Baja' => 2, 'Media' => 3, 'Alta' => 4, 'Premium' => 5];
                        $vals = []; $nums = [];
                        foreach ($moviles_comparacion as $m) {
                            $vals[] = valOrDefault(!empty($m['tier']) ? $m['tier'] : 'N/D');
                            $nums[] = !empty($m['tier']) ? ($mapa_gama[$m['tier']] ?? 3) : null;
                        }
                        $filas_datos[] = ['Gama', 'Gama', $vals, false, true, false, $nums];

                        // 4. Precio de Lanzamiento (Global/UE en €; si no hay, la primera región disponible)
                        $otras_regiones = [
                            'price_china'  => ['China', 'CNY'],
                            'price_usa'    => ['EE. UU.', 'USD'],
                            'price_india'  => ['India', 'INR'],
                            'price_japan'  => ['Japón', 'JPY'],
                            'price_korea'  => ['Corea', 'KRW'],
                            'price_latam'  => ['Latam', 'MXN'],
                            'price_taiwan' => ['Taiwán', 'TWD'],
                            'price_russia' => ['Rusia', 'RUB'],
                        ];
                        $vals = []; $nums = [];
                        foreach ($moviles_comparacion as $m) {
                            $meta = $m['meta'];
                            $texto = 'N/D'; $num = null;
                            if (!empty($meta['price_global']) && (float)$meta['price_global'] > 0) {
                                $texto = formato_precio($meta['price_global'], '€'); $num = (float)$meta['price_global'];
                            } elseif (!empty($meta['price_ue']) && (float)$meta['price_ue'] > 0) {
                                $texto = formato_precio($meta['price_ue'], '€'); $num = (float)$meta['price_ue'];
                            } else {
                                foreach ($otras_regiones as $col => $datos_region) {
                                    if (!empty($meta[$col]) && (float)$meta[$col] > 0) {
                                        $texto = formato_precio($meta[$col], $datos_region[1]) . ' (' . $datos_region[0] . ')';
                                        break; // otra moneda: se muestra pero no entra en el degradado
                                    }
                                }
                            }
                            $vals[] = $texto;
                            $nums[] = $num;
                        }
                        $filas_datos[] = ['Precio de Lanzamiento', 'Precio', $vals, false, true, false, $nums];

                        // 5. Certificación IP
                        $vals = []; $nums = [];
                        foreach ($moviles_comparacion as $m) {
                            $ip = $m['meta']['ip_rating'] ?? null;
                            $vals[] = valOrDefault($ip);
                            $nums[] = ($ip && preg_match('/[0-9]+/', $ip, $mm)) ? (int)$mm[0] : null;
                        }
                        $filas_datos[] = ['Certificación IP', 'IP', $vals, false, true, false, $nums];

                        // 6. Pantalla (Tamaño / Tipo)
                        $vals = [];
                        foreach ($moviles_comparacion as $m) {
                            $scr = $m['scr'];
                            $texto = !empty($scr['inches']) ? num_limpio($scr['inches']) . '"' : '';
                            if (!empty($scr['technology'])) $texto .= ($texto !== '' ? ' ' : '') . '(' . $scr['technology'] . ')';
                            $vals[] = valOrDefault($texto);
                        }
                        $filas_datos[] = ['Pantalla (Tamaño / Tipo)', 'Tamaño/Tipo', $vals, false, false, false, []];

                        // 7. Pantalla (Resolución / Refresco)
                        $vals = []; $nums = [];
                        foreach ($moviles_comparacion as $m) {
                            $scr = $m['scr'];
                            $texto = !empty($scr['resolution']) ? $scr['resolution'] : '';
                            if (!empty($scr['refresh_rate_hz'])) $texto .= ($texto !== '' ? ' ' : '') . '(' . $scr['refresh_rate_hz'] . 'Hz)';
                            $vals[] = valOrDefault($texto);
                            $nums[] = !empty($scr['refresh_rate_hz']) ? (int)$scr['refresh_rate_hz'] : null;
                        }
                        $filas_datos[] = ['Pantalla (Resolución / Refresco)', 'Resolución/Hz', $vals, false, true, false, $nums];

                        // 8. Procesador (SoC)
                        $vals = [];
                        foreach ($moviles_comparacion as $m) {
                            $vals[] = valOrDefault(trim(($m['soc']['brand'] ?? '') . ' ' . ($m['soc']['full_name'] ?? '')));
                        }
                        $filas_datos[] = ['Procesador (SoC)', 'Procesador', $vals, false, false, false, []];

                        // 9. Antutu
                        $vals = []; $nums = [];
                        foreach ($moviles_comparacion as $m) {
                            $a = (float)($m['soc']['antutu_score'] ?? 0);
                            $vals[] = $a > 0 ? number_format($a, 0, ',', '.') : 'N/D';
                            $nums[] = $a > 0 ? $a : null;
                        }
                        $filas_datos[] = ['Antutu', 'Antutu', $vals, false, true, false, $nums];

                        // 10. Geekbench
                        $vals = []; $nums = [];
                        foreach ($moviles_comparacion as $m) {
                            $gs = $m['soc']['geekbench_single'] ?? null;
                            $gm = $m['soc']['geekbench_multi'] ?? null;
                            if ($gs || $gm) {
                                $vals[] = ($gs ?: '—') . ' / ' . ($gm ?: '—');
                                $nums[] = $gm ? (float)$gm : (float)$gs;
                            } else {
                                $vals[] = 'N/D';
                                $nums[] = null;
                            }
                        }
                        $filas_datos[] = ['Geekbench (Mono / Multinúcleo)', 'Geekbench', $vals, false, true, false, $nums];

                        // 11. Memoria RAM
                        $vals = []; $nums = [];
                        foreach ($moviles_comparacion as $m) {
                            $textos = []; $mayor = null;
                            foreach ($m['storage_versions'] as $sv) {
                                $textos[] = $sv['ram_capacities_gb'] . ' GB' . (!empty($sv['ram_technology']) ? ' (' . $sv['ram_technology'] . ')' : '');
                                $mx = max_csv($sv['ram_capacities_gb']);
                                if ($mx !== null && ($mayor === null || $mx > $mayor)) $mayor = $mx;
                            }
                            $vals[] = $textos ? implode(' / ', $textos) : 'N/D';
                            $nums[] = $mayor;
                        }
                        $filas_datos[] = ['Memoria RAM', 'RAM', $vals, false, true, false, $nums];

                        // 12. Almacenamiento Interno
                        $vals = []; $nums = [];
                        foreach ($moviles_comparacion as $m) {
                            $textos = []; $mayor = null;
                            foreach ($m['storage_versions'] as $sv) {
                                $textos[] = $sv['storage_capacities_gb'] . ' GB' . (!empty($sv['storage_technology']) ? ' (' . $sv['storage_technology'] . ')' : '');
                                $mx = max_csv($sv['storage_capacities_gb']);
                                if ($mx !== null && ($mayor === null || $mx > $mayor)) $mayor = $mx;
                            }
                            $vals[] = $textos ? implode(' / ', $textos) : 'N/D';
                            $nums[] = $mayor;
                        }
                        $filas_datos[] = ['Almacenamiento Interno', 'Almacenamiento', $vals, false, true, false, $nums];

                        // 13. Capacidad de Batería
                        $vals = []; $nums = [];
                        foreach ($moviles_comparacion as $m) {
                            $mah = (int)($m['bat']['capacity_mah'] ?? 0);
                            $vals[] = $mah > 0 ? $mah . ' mAh' : 'N/D';
                            $nums[] = $mah > 0 ? $mah : null;
                        }
                        $filas_datos[] = ['Capacidad de Batería', 'Batería', $vals, false, true, false, $nums];

                        // 14. Potencia de Carga (cable / inalámbrica)
                        $vals = []; $nums = [];
                        foreach ($moviles_comparacion as $m) {
                            if (!empty($m['bat'])) {
                                $cable = (int)($m['bat']['wired_charging_w'] ?? 0);
                                $inalam = (int)($m['bat']['wireless_charging_w'] ?? 0);
                                $vals[] = $cable . 'W / ' . $inalam . 'W';
                                $nums[] = $cable > 0 ? $cable : null;
                            } else {
                                $vals[] = 'N/D';
                                $nums[] = null;
                            }
                        }
                        $filas_datos[] = ['Potencia de Carga', 'Carga', $vals, false, true, false, $nums];

                        // Renderizado dinámico de filas con sistema de gradientes
                        foreach ($filas_datos as $fila) {
                            $etiqueta_larga = $fila[0];
                            $etiqueta_cortada = $fila[1];
                            $valores = $fila[2];
                            $es_imagen = $fila[3];
                            $tiene_gradiente = $fila[4];
                            $excluir_ambar = $fila[5];
                            $numeros = $fila[6];

                            $etiqueta_actual = $usar_largo ? $etiqueta_larga : $etiqueta_cortada;
                            $es_diferente = valoresDifieren($valores);
                            $clase_fila = ($es_diferente && !$excluir_ambar) ? 'fila-diferente' : '';

                            $min_num = null;
                            $max_num = null;
                            if ($tiene_gradiente) {
                                $validos = array_filter($numeros, function ($n) { return $n !== null; });
                                if (!empty($validos)) {
                                    $min_num = min($validos);
                                    $max_num = max($validos);
                                }
                            }
                        ?>
                            <tr class="<?= $clase_fila ?>">
                                <td><?= escapar($etiqueta_actual) ?></td>
                                <?php foreach ($valores as $idx => $val):
                                    $cell_style = '';
                                    if ($tiene_gradiente && $min_num !== null && isset($numeros[$idx]) && $numeros[$idx] !== null) {
                                        $n_val = $numeros[$idx];
                                        $t = ($max_num == $min_num) ? 0.5 : (($n_val - $min_num) / ($max_num - $min_num));
                                        $hue = round($t * 120); // 0 = rojo, 120 = verde
                                        $cell_style = 'background-color: hsl(' . $hue . ', 80%, 88%); color: #1e293b;';
                                    }
                                ?>
                                    <td<?= $cell_style !== '' ? ' style="' . $cell_style . '"' : '' ?>>
                                        <?php if ($es_imagen): ?>
                                            <?php if (!empty($val)): ?>
                                                <img src="<?= escapar($val) ?>" alt="" class="comparar-img-celda">
                                            <?php else: ?>
                                                <span class="sin-dato-texto">N/D</span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <?= escapar($val) ?>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="tarjeta comparar-vacio-box">
                <h3>No hay dispositivos seleccionados</h3>
                <p>Utiliza los selectores superiores para escoger al menos dos smartphones y comenzar la comparativa técnica.</p>
            </div>
        <?php endif; ?>

    </main>

    <footer class="pie">
        <div class="contenedor">
            <p>Catálogo de smartphones · Proyecto personal estilo Kimovil</p>
        </div>
    </footer>

    <!-- ============================================================================
         FASE 4: SCRIPT JAVASCRIPT (FILTRADO EN TIEMPO REAL DE LOS SLOTS)
         ============================================================================ -->
    <script>
        /**
         * Filtra dinámicamente las opciones del selector según el texto introducido en el buscador de cada slot
         */
        function filtrarSlot(index) {
            const input = document.getElementById('buscador-slot-' + index).value.toLowerCase();
            const select = document.getElementById('select_movil_' + index);
            const opciones = select.options;

            for (let i = 1; i < opciones.length; i++) {
                const texto = opciones[i].text.toLowerCase();
                opciones[i].style.display = texto.includes(input) ? "" : "none";
            }
        }
    </script>
</body>
</html>
