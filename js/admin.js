/**
 * ============================================================================
 * JAVASCRIPT DEL FORMULARIO DEL PANEL ADMIN (admin.php)
 * Los datos del móvil que se está editando llegan en la constante ADMIN_DATA,
 * que escribe admin.php justo antes de cargar este archivo.
 * ============================================================================
 */

// Escapa texto para meterlo de forma segura dentro de atributos HTML generados por JS
function esc(valor) {
    return String(valor === null || valor === undefined ? '' : valor)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}

/* ============================================================================
   VERSIONES DE MEMORIA: cada bloque es una combinación concreta de RAM + almacenamiento
   con sus precios por región. Los nombres son storage_versions[uid][campo].
   ============================================================================ */
let contadorVersiones = 0;

function agregarVersionMemoria(v) {
    const cont = document.getElementById('storage-versions-container');
    if (!cont) return;
    v = v || {};
    const uid = 'v' + (contadorVersiones++);
    const n = function (campo) { return 'storage_versions[' + uid + '][' + campo + ']'; };

    const precios = ADMIN_DATA.regiones.map(function (r) {
        return `
            <div>
                <label>${esc(r.etiqueta)}:</label>
                <input type="number" step="0.01" min="0" name="${n(r.col)}" value="${esc(v[r.col])}" placeholder="Precio en ${esc(r.moneda)}">
            </div>`;
    }).join('');
    const monedaActual = v.carrier_currency || 'EUR';
    const opcionesMoneda = ADMIN_DATA.monedas.map(function (m) {
        return '<option value="' + esc(m.codigo) + '"' + (m.codigo === monedaActual ? ' selected' : '') + '>' + esc(m.nombre) + '</option>';
    }).join('');
    const hayOperadora = (v.price_carrier !== undefined && v.price_carrier !== '') || (v.carrier_details !== undefined && v.carrier_details !== '');
    const hayPrecios = hayOperadora || ADMIN_DATA.regiones.some(function (r) { return v[r.col] !== undefined && v[r.col] !== ''; });

    const caja = document.createElement('div');
    caja.className = 'storage-version-row';
    caja.innerHTML = `
        <button type="button" class="btn-eliminar-aux" onclick="this.closest('.storage-version-row').remove()">❌ Eliminar</button>
        <div class="storage-version-campos">
            <div><label>RAM (GB):</label><input type="number" min="1" name="${n('ram_gb')}" value="${esc(v.ram_gb)}" placeholder="Ej: 12"></div>
            <div><label>Tecnología RAM:</label><input type="text" list="datalist_ram_tech" name="${n('ram_tech')}" value="${esc(v.ram_tech)}" placeholder="Ej: LPDDR5X"></div>
            <div><label>Almacenamiento (GB):</label><input type="number" min="1" name="${n('storage_gb')}" value="${esc(v.storage_gb)}" placeholder="Ej: 256"></div>
            <div><label>Tecnología almacenamiento:</label><input type="text" list="datalist_storage_tech" name="${n('storage_tech')}" value="${esc(v.storage_tech)}" placeholder="Ej: UFS 4.0"></div>
        </div>
        <details class="precios-version"${hayPrecios ? ' open' : ''}>
            <summary>💶 Precios de esta versión</summary>
            <div class="precios-version-grid">${precios}</div>
            <div class="precio-operadora-version">
                <h5 class="precio-operadora-titulo">📡 Exclusivo operadora</h5>
                <div class="precio-operadora-fila">
                    <div><label>Precio:</label><input type="number" step="0.01" min="0" name="${n('price_carrier')}" value="${esc(v.price_carrier)}" placeholder="Precio con operadora"></div>
                    <div><label>Moneda:</label><select name="${n('carrier_currency')}">${opcionesMoneda}</select></div>
                </div>
                <div>
                    <label>Comentario de esta versión de operadora:</label>
                    <textarea name="${n('carrier_details')}" rows="3" placeholder="Nombre de la operadora, condiciones, permanencia...">${esc(v.carrier_details)}</textarea>
                </div>
            </div>
        </details>
    `;
    cont.appendChild(caja);
}

function pintarVersiones() {
    const versiones = ADMIN_DATA.versiones || [];
    versiones.forEach(function (v) { agregarVersionMemoria(v); });
    if (versiones.length === 0) agregarVersionMemoria({}); // una fila vacía para empezar
}

/* ============================================================================
   CÁMARAS: la principal y las auxiliares se pintan con la misma plantilla.
   Los nombres son main[campo] y aux[uid][campo], así cada cámara va agrupada al enviar.
   Cada tipo de vídeo admite dos opciones de resolución + fps (ej: 4K 120fps y 8K 30fps).
   ============================================================================ */
let contadorAux = 0;

// Pasa un tipo guardado con otra forma de escribirlo ("ultra gran angular", "Infrarojos") al nombre oficial de la lista
function canonizarTipo(tipo) {
    const original = String(tipo === null || tipo === undefined ? '' : tipo);
    const clave = original.trim().toLowerCase();
    const lista = ADMIN_DATA.tiposCamaraAux;
    for (let i = 0; i < lista.length; i++) {
        if (lista[i].toLowerCase() === clave) return lista[i];
    }
    const alias = { 'infrarojos': 'Infrarrojos', 'microscopico': 'Microscópico' };
    return alias[clave] || original;
}

// Los sensores que no graban vídeo (ToF, LiDAR, infrarrojos...) no muestran el apartado de vídeo
function tipoTieneVideo(tipo) {
    if (!tipo) return true;
    return ADMIN_DATA.tiposSinVideo.indexOf(canonizarTipo(tipo)) === -1;
}

// Se ejecuta al cambiar el tipo de una cámara auxiliar: muestra u oculta su apartado de vídeo
function actualizarVideoCamara(select) {
    const caja = select.closest('.aux-camara-box');
    if (!caja) return;
    const bloque = caja.querySelector('.cam-video-bloque');
    if (bloque) bloque.classList.toggle('oculto', !tipoTieneVideo(select.value));
}

function filaVideo(n, titulo, campoHas, campoRes, campoFps, activo, res, fps, res2, fps2) {
    const cabecera = campoHas
        ? `<label class="checkbox-label"><input type="checkbox" name="${n(campoHas)}" value="1" ${activo ? 'checked' : ''}> ${titulo}</label>`
        : `<span class="cam-video-titulo">${titulo}</span>`;
    return `
        <div class="cam-video-fila con-opcion2">
            ${cabecera}
            <input type="text" name="${n(campoRes)}" value="${esc(res)}" placeholder="Opción 1: 4K">
            <input type="number" min="0" name="${n(campoFps)}" value="${esc(fps)}" placeholder="fps">
            <input type="text" name="${n(campoRes + '2')}" value="${esc(res2)}" placeholder="Opción 2: 8K">
            <input type="number" min="0" name="${n(campoFps + '2')}" value="${esc(fps2)}" placeholder="fps">
        </div>`;
}

function camposCamara(prefijo, c, conTipo) {
    c = c || {};
    const n = function (campo) { return prefijo + '[' + campo + ']'; };

    let selectorTipo = '';
    let conVideo = true;
    if (conTipo) {
        const tipoActual = canonizarTipo(c.type);
        const tipos = ADMIN_DATA.tiposCamaraAux.slice();
        if (tipoActual && tipos.indexOf(tipoActual) === -1) tipos.push(tipoActual);
        const opciones = tipos.map(function (t) {
            return '<option value="' + esc(t) + '"' + (t === tipoActual ? ' selected' : '') + '>' + esc(t) + '</option>';
        }).join('');
        conVideo = tipoTieneVideo(tipoActual);
        selectorTipo = `
            <div>
                <label>Tipo de Cámara:</label>
                <select name="${n('type')}" required onchange="actualizarVideoCamara(this)">
                    ${tipoActual ? '' : '<option value="">-- Seleccionar Tipo --</option>'}
                    ${opciones}
                </select>
            </div>`;
    }

    return `
        <div class="cam-grid">
            ${selectorTipo}
            <div><label>Sensor:</label><input type="text" name="${n('sensor_name')}" value="${esc(c.sensor_name)}"></div>
            <div><label>Tamaño Sensor:</label><input type="text" name="${n('sensor_size')}" value="${esc(c.sensor_size)}"></div>
            <div><label>Megapíxeles (MP):</label><input type="number" step="0.01" name="${n('resolution_mp')}" value="${esc(c.resolution_mp)}"></div>
            <div><label>Apertura Focal:</label><input type="number" step="0.01" name="${n('aperture')}" value="${esc(c.aperture)}" placeholder="Ej: 1.8"></div>
        </div>
        <div class="cam-video-bloque${conVideo ? '' : ' oculto'}">
            ${filaVideo(n, 'Vídeo normal máx.', null, 'video_res', 'video_fps', false, c.video_res, c.video_fps, c.video_res2, c.video_fps2)}
            ${filaVideo(n, 'LOG', 'has_log', 'log_res', 'log_fps', c.has_log, c.log_res, c.log_fps, c.log_res2, c.log_fps2)}
            ${filaVideo(n, 'OpenGate', 'has_og', 'og_res', 'og_fps', c.has_og, c.og_res, c.og_fps, c.og_res2, c.og_fps2)}
            ${filaVideo(n, 'OpenGate + LOG', 'has_ogl', 'ogl_res', 'ogl_fps', c.has_ogl, c.ogl_res, c.ogl_fps, c.ogl_res2, c.ogl_fps2)}
        </div>`;
}

function agregarCamaraAuxiliar(c) {
    const cont = document.getElementById('aux-cameras-container');
    if (!cont) return;
    const caja = document.createElement('div');
    caja.className = 'aux-camara-box';
    const prefijo = 'aux[a' + (contadorAux++) + ']';
    caja.innerHTML = `
        <button type="button" class="btn-eliminar-aux" onclick="this.parentElement.remove()">❌ Eliminar</button>
        <h4 class="aux-title">Cámara Auxiliar</h4>
        ${camposCamara(prefijo, c, true)}
    `;
    cont.appendChild(caja);
}

function pintarCamaras() {
    const principal = document.getElementById('cam-principal');
    if (principal) {
        principal.innerHTML = '<h4 class="aux-title">Cámara Principal</h4>' + camposCamara('main', ADMIN_DATA.camaraPrincipal, false);
    }
    (ADMIN_DATA.camarasAux || []).forEach(function (c) { agregarCamaraAuxiliar(c); });
}

/* ============================================================================
   NOTA MEDIA AUTOMÁTICA: suma de los 6 puntos entre 6 (igual que al guardar en el servidor)
   ============================================================================ */
function calcularMedia() {
    let suma = 0;
    let hayNota = false;
    for (let i = 1; i <= 6; i++) {
        const campo = document.getElementById('score_' + i);
        if (!campo) return;
        const n = parseFloat(campo.value);
        if (!isNaN(n)) { suma += n; hayNota = true; }
    }
    const salida = document.getElementById('score_avg');
    if (salida) salida.value = hayNota ? (Math.round(suma / 6 * 100) / 100).toFixed(2).replace(/\.?0+$/, '') : '';
}

/* ============================================================================
   PRIORIDAD "OBTENIDO": muestra la caja de texto de los colores que ya tengo comprados
   ============================================================================ */
function mostrarColoresObtenidos() {
    const select = document.getElementById('select-prioridad');
    const caja = document.getElementById('caja-colores-obtenidos');
    if (!select || !caja) return;
    caja.classList.toggle('oculto', select.value !== 'obtenido');
}

/* ============================================================================
   TARJETA SIM: sin la casilla MicroSD marcada se ocultan las opciones que contienen "MicroSD"
   ============================================================================ */
function actualizarOpcionesSim() {
    const select = document.getElementById('select-sim');
    const casilla = document.getElementById('check-microsd');
    if (!select || !casilla) return;

    // La primera vez se usa el valor guardado; después, lo que esté elegido en ese momento
    const actual = (select.dataset.iniciado === '1') ? select.value : (ADMIN_DATA.simActual || '');
    select.dataset.iniciado = '1';

    select.innerHTML = '<option value="">-- Sin indicar --</option>';
    ADMIN_DATA.opcionesSim.forEach(function (opcion) {
        if (!casilla.checked && opcion.toLowerCase().indexOf('microsd') !== -1) return;
        const o = document.createElement('option');
        o.value = opcion;
        o.textContent = opcion;
        if (opcion === actual) o.selected = true;
        select.appendChild(o);
    });
}

/* ============================================================================
   VARIANTES REGIONALES: selector con búsqueda (igual que los del comparador).
   Cada móvil asociado se guarda como un campo oculto variantes[].
   ============================================================================ */
let variantesElegidas = [];

function rellenarSelectVariantes() {
    const select = document.getElementById('select-variante');
    const buscador = document.getElementById('buscador-variante');
    if (!select || !buscador) return;

    const filtro = buscador.value.toLowerCase();
    const yaElegidos = variantesElegidas.map(function (v) { return v.id; });

    select.innerHTML = '<option value="">-- Seleccionar móvil --</option>';
    ADMIN_DATA.moviles.forEach(function (m) {
        if (yaElegidos.indexOf(m.id) !== -1) return;
        if (filtro !== '' && m.texto.toLowerCase().indexOf(filtro) === -1) return;
        const opcion = document.createElement('option');
        opcion.value = m.id;
        opcion.textContent = m.texto;
        select.appendChild(opcion);
    });
    // Al escribir en el buscador se deja marcado el primer resultado
    if (filtro !== '' && select.options.length > 1) select.selectedIndex = 1;
}

function filtrarVariantes() {
    rellenarSelectVariantes();
}

function pintarVariantes() {
    const cont = document.getElementById('variantes-asociadas');
    if (!cont) return;
    cont.innerHTML = '';

    if (variantesElegidas.length === 0) {
        cont.innerHTML = '<p class="sin-variantes-texto">Este móvil no tiene variantes asociadas.</p>';
    }
    variantesElegidas.forEach(function (v) {
        const chip = document.createElement('span');
        chip.className = 'tag-variante tag-variante-editable';
        chip.innerHTML = '<input type="hidden" name="variantes[]" value="' + esc(v.id) + '">' + esc(v.texto) +
            ' <button type="button" class="btn-quitar-variante" title="Quitar" onclick="quitarVariante(' + Number(v.id) + ')">✕</button>';
        cont.appendChild(chip);
    });
    rellenarSelectVariantes();
}

function agregarVariante() {
    const select = document.getElementById('select-variante');
    if (!select) return;
    const id = parseInt(select.value, 10);
    if (!id) return;
    const movil = ADMIN_DATA.moviles.find(function (m) { return m.id === id; });
    if (!movil) return;
    variantesElegidas.push({ id: movil.id, texto: movil.texto });
    document.getElementById('buscador-variante').value = '';
    pintarVariantes();
}

function quitarVariante(id) {
    variantesElegidas = variantesElegidas.filter(function (v) { return v.id !== id; });
    pintarVariantes();
}

/* ============================================================================
   ARRANQUE
   ============================================================================ */
window.addEventListener('DOMContentLoaded', function () {
    pintarVersiones();
    pintarCamaras();
    calcularMedia();
    actualizarOpcionesSim();
    mostrarColoresObtenidos();
    variantesElegidas = (ADMIN_DATA.variantes || []).slice();
    pintarVariantes();
});
