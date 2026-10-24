/**
 * ============================================================================
 * JAVASCRIPT DEL COMPARADOR (comparar.php)
 * ============================================================================
 */

// Filtra dinámicamente las opciones del selector según el texto escrito en el buscador de cada slot
function filtrarSlot(index) {
    const input = document.getElementById('buscador-slot-' + index).value.toLowerCase();
    const select = document.getElementById('select_movil_' + index);
    const opciones = select.options;

    for (let i = 1; i < opciones.length; i++) {
        const texto = opciones[i].text.toLowerCase();
        opciones[i].style.display = texto.includes(input) ? "" : "none";
    }
}
