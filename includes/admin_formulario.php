<?php
/**
 * Formulario maestro del panel admin (crear / editar / borrar).
 * Lo incluye admin.php. Variables que usa: $modo_admin, $ficha, $editar_id, $borrar_id, $regiones.
 */
?>
                <form method="POST" enctype="multipart/form-data" action="admin.php?modo=<?= $modo_admin ?>&amp;editar_id=<?= $editar_id ?>&amp;borrar_id=<?= $borrar_id ?>">
                    <?= csrf_campo() ?>
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
                                <div><label>Sistema Operativo:</label><input type="text" name="so_nombre" list="datalist_so" value="<?= v('phone', 'so_nombre') ?>"></div>
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

                        <!-- Sección 4: Versiones de memoria (RAM + almacenamiento + precio de cada versión) -->
                        <details class="categoria-filtro">
                            <summary>4. Memoria RAM, Almacenamiento y Precios por Versión</summary>
                            <div class="categoria-contenido block-layout storage-block-container">
                                <p class="ayuda-notas">Cada bloque es una versión concreta del móvil (por ejemplo 12 GB de RAM + 256 GB). Dentro de cada versión puedes poner su precio en cada región y, si existe, su precio exclusivo de operadora.</p>
                                <div id="storage-versions-container"></div>
                                <button type="button" class="btn-add-aux" onclick="agregarVersionMemoria()">➕ Añadir versión de memoria</button>
                            </div>
                        </details>

                        <!-- Sección 5: Pantalla -->
                        <details class="categoria-filtro">
                            <summary>5. Pantalla</summary>
                            <div class="categoria-contenido">
                                <div><label>Pulgadas:</label><input type="number" step="0.01" name="inches" value="<?= v('scr', 'inches') ?>"></div>
                                <div><label>Resolución:</label><input type="text" name="resolution" value="<?= v('scr', 'resolution') ?>"></div>
                                <div><label>Tasa de Refresco (Hz):</label><input type="number" name="hz" value="<?= v('scr', 'refresh_rate_hz', 120) ?>"></div>
                                <div><label>Brillo máximo (nits):</label><input type="number" min="0" name="brightness" value="<?= v('scr', 'max_brightness_nits') ?>" placeholder="Ej: 3000"></div>
                                <div><label>Tecnología de Pantalla:</label><input type="text" name="screen_tech" list="datalist_screen_tech" value="<?= v('scr', 'technology') ?>"></div>
                                <div><label>Protección:</label><input type="text" name="protection" list="datalist_protection" value="<?= v('scr', 'protection') ?>" placeholder="Ej: Corning Gorilla Glass Victus"></div>
                            </div>
                        </details>

                        <!-- Sección 6: Batería -->
                        <details class="categoria-filtro">
                            <summary>6. Batería y Tiempos de Carga</summary>
                            <div class="categoria-contenido">
                                <div><label>Capacidad (mAh):</label><input type="number" name="mah" value="<?= v('bat', 'capacity_mah') ?>"></div>
                                <div><label>Batería:</label><input type="text" name="battery_type" list="datalist_battery_type" value="<?= v('bat', 'battery_type') ?>" placeholder="Ej: Ion-Li, Si-C"></div>
                                <div><label>Carga Cable (W):</label><input type="number" name="wired_w" value="<?= v('bat', 'wired_charging_w') ?>"></div>
                                <div><label>Carga Inalámbrica (W):</label><input type="number" name="wireless_w" value="<?= v('bat', 'wireless_charging_w') ?>"></div>
                                <div><label>Carga Inversa Cable (W):</label><input type="number" name="rev_wired_w" value="<?= v('bat', 'reverse_wired_charging_w') ?>"></div>
                                <div><label>Carga Inversa Inalámbrica (W):</label><input type="number" name="rev_wireless_w" value="<?= v('bat', 'reverse_wireless_charging_w') ?>"></div>
                                <div><label>Ciclos 80% salud:</label><input type="number" min="0" name="cycles_80" value="<?= v('bat', 'assured_cycles_80_health') ?>" placeholder="Ej: 1200" title="Ciclos de carga que asegura el fabricante antes de que la batería baje al 80 % de su capacidad"></div>
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

                        <!-- Sección 9: Conectividad -->
                        <details class="categoria-filtro">
                            <summary>9. Conectividad y Redes</summary>
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
                                <div class="checkbox-wrapper">
                                    <label class="checkbox-label"><input type="checkbox" name="android_auto" value="1" <?= !empty($ficha['conn']['android_auto']) ? 'checked' : '' ?>> Android Auto</label>
                                </div>
                                <div class="checkbox-wrapper">
                                    <label class="checkbox-label"><input type="checkbox" name="microsd" id="check-microsd" value="1" onchange="actualizarOpcionesSim()" <?= !empty($ficha['conn']['microsd']) ? 'checked' : '' ?>> MicroSD</label>
                                </div>
                                <div>
                                    <label>Tarjeta SIM:</label>
                                    <select name="sim_config" id="select-sim"></select>
                                </div>
                            </div>
                        </details>

                        <!-- Sección 10: Valoración personal (6 puntos del hexágono), prioridad, foto y comentario -->
                        <details class="categoria-filtro">
                            <summary>10. Valoración Personal (6 Puntos), Prioridad y Comentarios</summary>
                            <div class="categoria-contenido">
                                <p class="full-width ayuda-notas">Estos 6 valores son los que dibuja el hexágono de la ficha del móvil. La nota media se calcula sola.</p>
                                <?php
                                $criterios_nota = [1 => 'Diseño', 2 => 'Pantalla', 3 => 'Rendimiento', 4 => 'Cámaras', 5 => 'Batería', 6 => 'Software'];
                                foreach ($criterios_nota as $num_nota => $nombre_nota): ?>
                                    <div>
                                        <label>Punto <?= $num_nota ?> (<?= $nombre_nota ?>):</label>
                                        <input type="number" step="0.01" min="0" max="10" name="score_<?= $num_nota ?>" id="score_<?= $num_nota ?>" oninput="calcularMedia()" value="<?= vn('meta', 'score_' . $num_nota) ?>" placeholder="0 - 10">
                                    </div>
                                <?php endforeach; ?>
                                <div>
                                    <label>Nota Media (automática):</label>
                                    <input type="number" step="0.01" id="score_avg" class="campo-calculado" readonly value="<?= vn('meta', 'score_avg') ?>" placeholder="Se calcula sola">
                                </div>
                                <?php $prioridad_actual = ($modo_admin === 'crear') ? 'ninguna' : (string)($ficha['meta']['priority'] ?? 'ninguna'); ?>
                                <div>
                                    <label>Prioridad de compra (solo la ves tú):</label>
                                    <select name="priority" id="select-prioridad" onchange="mostrarColoresObtenidos()">
                                        <?php foreach (prioridades() as $clave_prioridad => $texto_prioridad): ?>
                                            <option value="<?= escapar($clave_prioridad) ?>"<?= $prioridad_actual === $clave_prioridad ? ' selected' : '' ?>><?= escapar($texto_prioridad) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="full-width<?= $prioridad_actual === 'obtenido' ? '' : ' oculto' ?>" id="caja-colores-obtenidos">
                                    <label>Colores que ya tengo comprados:</label>
                                    <input type="text" name="owned_colors" value="<?= v('meta', 'owned_colors') ?>" placeholder="Ej: Negro, Azul noche">
                                </div>
                                <div class="full-width">
                                    <label>Foto del móvil:</label>
                                    <?php $imagen_actual = url_imagen($ficha['meta']['image_url'] ?? ''); ?>
                                    <?php if ($imagen_actual !== ''): ?>
                                        <div class="admin-imagen-actual">
                                            <img src="<?= escapar($imagen_actual) ?>" alt="Foto actual" class="admin-preview-imagen">
                                            <label class="checkbox-label"><input type="checkbox" name="quitar_imagen" value="1"> Quitar la foto actual</label>
                                        </div>
                                    <?php endif; ?>
                                    <input type="file" name="imagen_movil" accept="image/jpeg,image/png,image/webp">
                                    <p class="ayuda-notas">JPG, PNG o WebP de hasta 5 MB. Si subes una foto nueva sustituye a la actual.</p>
                                </div>
                                <div class="full-width">
                                    <label>Comentario personal (visible para todos en la ficha):</label>
                                    <textarea name="personal_comment" rows="4" placeholder="Tus impresiones y conclusiones sobre el terminal..."><?= v('meta', 'personal_comment') ?></textarea>
                                </div>
                            </div>
                        </details>

                        <!-- Sección 11: Variantes regionales (móviles asociados) -->
                        <details class="categoria-filtro">
                            <summary>11. Variantes Regionales</summary>
                            <div class="categoria-contenido block-layout">
                                <p class="ayuda-notas">Asocia este móvil con otros que son el mismo dispositivo con mínimos cambios de hardware (versión global, China, India...). Todos los móviles asociados se verán entre sí en su ficha.</p>
                                <div class="variantes-selector">
                                    <input type="text" id="buscador-variante" placeholder="🔍 Filtrar móviles..." onkeyup="filtrarVariantes()">
                                    <select id="select-variante"></select>
                                    <button type="button" class="btn-add-aux" onclick="agregarVariante()">➕ Asociar</button>
                                </div>
                                <div id="variantes-asociadas" class="variantes-tags-row"></div>
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
