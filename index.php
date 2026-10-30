<?php
/**
 * ============================================================================
 * INDEX.PHP · CATÁLOGO PÚBLICO: BUSCADOR AVANZADO + LISTADO DE MÓVILES
 * El panel de administración está ahora en admin.php.
 * La lógica del buscador está en includes/buscador.php.
 * ============================================================================
 */
require_once __DIR__ . '/config/conexion.php';
require_once __DIR__ . '/includes/funciones.php';
require_once __DIR__ . '/includes/listas.php';
exigir_esquema($conexion);
require_once __DIR__ . '/includes/buscador.php';

// Datos de la cabecera común
$pagina_actual    = 'index';
$pagina_titulo    = 'Catálogo de Smartphones · Estilo Kimovil';
$pagina_h1        = 'Biblioteca de smartphones';
$pagina_subtitulo = 'Especificaciones técnicas avanzadas y comparativa inteligente';
$pagina_css       = ['index'];

require __DIR__ . '/includes/header.php';
?>

    <main class="contenedor">

        <!-- ====================================================================
             BUSCADOR AVANZADO POR CATEGORÍAS
             ==================================================================== -->
        <section class="introduccion busqueda-avanzada-box">
            <h2>🔍 Buscador Avanzado por Categorías</h2>

            <?php if ($error_precio !== ''): ?>
                <div class="alerta-error"><?= escapar($error_precio) ?></div>
            <?php endif; ?>

            <form method="GET" action="index.php">
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
                            <label>RAM mínima de la versión:</label>
                            <select name="f_ram"><option value="">-- Cualquiera --</option><?= opciones($list_ram, $F['f_ram'], ' GB o más') ?></select>
                        </div>
                        <div>
                            <label>Tecnología RAM:</label>
                            <select name="f_ram_tech"><option value="">-- Todas --</option><?= opciones($list_ram_tech, $F['f_ram_tech']) ?></select>
                        </div>
                        <div>
                            <label>Almacenamiento mínimo de la versión:</label>
                            <select name="f_storage"><option value="">-- Cualquiera --</option><?= opciones($list_storage, $F['f_storage'], ' GB o más') ?></select>
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
                    <summary>💶 10. Precio por Región (de cualquier versión)</summary>
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
                <details class="categoria-filtro"<?= abierto($F, ['f_network', 'f_wifi', 'f_bt', 'f_nfc', 'f_esim', 'f_android_auto', 'f_microsd', 'f_sim']) ?>>
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
                        <div>
                            <label>Android Auto:</label>
                            <select name="f_android_auto">
                                <option value="">-- Indiferente --</option>
                                <option value="1"<?= $F['f_android_auto'] === '1' ? ' selected' : '' ?>>Sí</option>
                                <option value="0"<?= $F['f_android_auto'] === '0' ? ' selected' : '' ?>>No</option>
                            </select>
                        </div>
                        <div>
                            <label>MicroSD:</label>
                            <select name="f_microsd">
                                <option value="">-- Indiferente --</option>
                                <option value="1"<?= $F['f_microsd'] === '1' ? ' selected' : '' ?>>Sí</option>
                                <option value="0"<?= $F['f_microsd'] === '0' ? ' selected' : '' ?>>No</option>
                            </select>
                        </div>
                        <div>
                            <label>Tarjeta SIM:</label>
                            <select name="f_sim"><option value="">-- Cualquiera --</option><?= opciones(opciones_sim(), $F['f_sim']) ?></select>
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
                                <?php $imagen_tarjeta = url_imagen($m['image_url'] ?? ''); ?>
                                <?php if ($imagen_tarjeta !== ''): ?>
                                    <div class="card-imagen"><img src="<?= escapar($imagen_tarjeta) ?>" alt="Foto de <?= escapar($m['movil']) ?>" loading="lazy"></div>
                                <?php endif; ?>
                                <p class="marca"><?= escapar($m['marca']) ?></p>
                                <h3><?= escapar($m['movil']) ?></h3>
                                <p class="familia">Familia: <?= escapar($m['familia']) ?></p>
                                <p class="anio">Lanzamiento: <?= escapar($m['launch_year']) ?>
                                    <?php if (!empty($m['tier'])): ?><span class="tier-badge"><?= escapar($m['tier']) ?></span><?php endif; ?>
                                </p>
                                <?php $tiene_nota = (float)($m['score_avg'] ?? 0) > 0; $tiene_precio = (float)($m['precio_desde'] ?? 0) > 0; ?>
                                <?php if ($tiene_nota || $tiene_precio): ?>
                                    <div class="card-pie">
                                        <span class="card-nota"><?= $tiene_nota ? '★ ' . number_format((float)$m['score_avg'], 1) : '' ?></span>
                                        <span class="card-precio"><?= $tiene_precio ? 'Desde ' . number_format((float)$m['precio_desde'], (floor((float)$m['precio_desde']) == (float)$m['precio_desde']) ? 0 : 2, ',', '.') . ' €' : '' ?></span>
                                    </div>
                                <?php endif; ?>
                            </article>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

    </main>

<?php require __DIR__ . '/includes/footer.php'; ?>
