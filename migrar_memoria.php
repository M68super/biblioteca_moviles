<?php
/**
 * ============================================================================
 * MIGRAR_MEMORIA.PHP · CONVERSIÓN ÚNICA DE LA MEMORIA A NÚMEROS
 *
 * Antes cada versión guardaba la RAM y el almacenamiento como texto ("8, 12, 16").
 * Esta página las convierte en una fila por combinación concreta con números
 * (ej: 8/256, 12/256, 12/512), que es lo que permite filtrar de verdad y poner
 * un precio a cada versión.
 *
 * Cómo combina los textos:
 *   - Si una de las dos listas tiene un solo valor -> se combina con todos los de la otra.
 *   - Si ambas listas tienen la misma cantidad     -> se emparejan por posición (8 con 256, 12 con 512).
 *   - En cualquier otro caso                       -> se crean todas las combinaciones posibles.
 * Revisa el resultado en el panel admin y borra las combinaciones que no existan.
 *
 * Solo para administradores. Borra este archivo cuando termines.
 * ============================================================================
 */
require_once __DIR__ . '/config/conexion.php';
require_once __DIR__ . '/includes/funciones.php';

exigir_admin();
exigir_esquema($conexion);

// Saca los números de un texto como "8, 12, 16" o "256 GB, 1 TB" (1 TB = 1024 GB)
function numeros_memoria($texto) {
    $resultado = [];
    if (preg_match_all('/(\d+(?:[.,]\d+)?)\s*(TB|GB)?/i', (string)$texto, $coincidencias, PREG_SET_ORDER)) {
        foreach ($coincidencias as $t) {
            $n = (float)str_replace(',', '.', $t[1]);
            if (strtoupper($t[2] ?? '') === 'TB') $n *= 1024;
            $n = (int)round($n);
            if ($n > 0 && !in_array($n, $resultado, true)) $resultado[] = $n;
        }
    }
    return $resultado;
}

// Combina las listas de RAM y almacenamiento según las reglas de arriba
function combinar_memoria(array $ram, array $almacenamiento) {
    if (empty($ram) || empty($almacenamiento)) return [];
    $combos = [];
    if (count($ram) === count($almacenamiento) && count($ram) > 1) {
        foreach ($ram as $i => $r) { $combos[] = [$r, $almacenamiento[$i]]; }
    } else {
        foreach ($ram as $r) {
            foreach ($almacenamiento as $a) { $combos[] = [$r, $a]; }
        }
    }
    return $combos;
}

$error = '';
$ejecutado = false;
$informe = [];

try {
    $filas = $conexion->query("
        SELECT sv.*, p.name AS movil, b.name AS marca
        FROM characteristics_storage_versions sv
        INNER JOIN phone_characteristics pc ON pc.id = sv.phone_characteristic_id
        INNER JOIN phones p ON p.id = pc.phone_id
        INNER JOIN series s ON s.id = p.series_id
        INNER JOIN brands b ON b.id = s.brand_id
        WHERE sv.ram_gb IS NULL OR sv.storage_gb IS NULL
        ORDER BY b.name, p.name, sv.id
    ")->fetchAll();

    // Se calcula la propuesta de cada fila pendiente
    foreach ($filas as $sv) {
        $combos = combinar_memoria(numeros_memoria($sv['ram_capacities_gb']), numeros_memoria($sv['storage_capacities_gb']));
        $informe[] = ['fila' => $sv, 'combos' => $combos];
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!csrf_valido()) {
            $error = 'El formulario ha caducado. Recarga la página e inténtalo de nuevo.';
        } else {
            $conexion->beginTransaction();
            $insertar = $conexion->prepare("INSERT INTO characteristics_storage_versions
                (phone_characteristic_id, ram_capacities_gb, ram_technology, storage_capacities_gb, storage_technology, ram_gb, storage_gb)
                VALUES (?, ?, ?, ?, ?, ?, ?)");
            $borrar = $conexion->prepare("DELETE FROM characteristics_storage_versions WHERE id = ?");

            foreach ($informe as $item) {
                if (empty($item['combos'])) continue; // no se puede interpretar: se deja como está
                $sv = $item['fila'];
                foreach ($item['combos'] as $combo) {
                    $insertar->execute([
                        $sv['phone_characteristic_id'], (string)$combo[0], (string)$sv['ram_technology'],
                        (string)$combo[1], (string)$sv['storage_technology'], $combo[0], $combo[1],
                    ]);
                }
                $borrar->execute([$sv['id']]);
            }
            $conexion->commit();
            $ejecutado = true;
        }
    }
} catch (Throwable $e) {
    if ($conexion->inTransaction()) $conexion->rollBack();
    $error = 'Error durante la conversión: ' . $e->getMessage();
}

// Datos de la cabecera común
$pagina_actual    = 'migrar_memoria';
$pagina_titulo    = 'Convertir memoria a números | Panel Admin';
$pagina_h1        = 'Convertir la memoria a números';
$pagina_subtitulo = 'Conversión única de las versiones de RAM y almacenamiento';
$pagina_css       = ['admin'];

require __DIR__ . '/includes/header.php';
?>

    <main class="contenedor espacio-cuerpo">
        <section class="admin-box">
            <?php if ($error !== ''): ?><div class="alerta-error"><?= escapar($error) ?></div><?php endif; ?>

            <?php if ($ejecutado): ?>
                <div class="alerta-exito">Conversión terminada. Revisa los móviles marcados en la tabla desde el <a href="admin.php?modo=editar">panel admin</a> y añade los precios por versión. Después puedes borrar este archivo.</div>
            <?php elseif (empty($informe)): ?>
                <div class="alerta-exito">No queda nada por convertir: todas las versiones de memoria ya usan números. Puedes borrar este archivo.</div>
            <?php else: ?>
                <p>Estas versiones de memoria todavía están guardadas como texto. Así quedarían tras la conversión:</p>
            <?php endif; ?>

            <?php if (!empty($informe)): ?>
                <div class="tabla-wrapper">
                    <table class="tabla-simple">
                        <thead>
                            <tr><th>Móvil</th><th>Texto actual (RAM / almacenamiento)</th><th>Resultado</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($informe as $item): ?>
                                <?php $sv = $item['fila']; ?>
                                <tr>
                                    <td><?= escapar($sv['marca'] . ' ' . $sv['movil']) ?></td>
                                    <td><?= escapar($sv['ram_capacities_gb'] . ' / ' . $sv['storage_capacities_gb']) ?></td>
                                    <td>
                                        <?php if (empty($item['combos'])): ?>
                                            <em>No se puede interpretar: edítalo a mano en el panel admin.</em>
                                        <?php else: ?>
                                            <?php
                                            $textos = [];
                                            foreach ($item['combos'] as $combo) { $textos[] = $combo[0] . ' GB + ' . $combo[1] . ' GB'; }
                                            echo escapar(implode('  ·  ', $textos));
                                            ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if (!$ejecutado): ?>
                    <form method="POST" action="migrar_memoria.php" onsubmit="return confirm('¿Convertir estas versiones? Haz antes una copia de seguridad de la base de datos.');">
                        <?= csrf_campo() ?>
                        <button type="submit" class="btn-submit-success">Convertir ahora</button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    </main>

<?php require __DIR__ . '/includes/footer.php'; ?>
