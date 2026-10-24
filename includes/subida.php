<?php
/**
 * ============================================================================
 * SUBIDA DE IMÁGENES DE LOS MÓVILES
 * Las fotos se guardan en uploads/moviles/ con un nombre aleatorio y en la
 * base de datos solo se guarda su ruta (ej: uploads/moviles/ab12...ef.jpg).
 * ============================================================================
 */

const SUBIDA_MAX_BYTES = 5 * 1024 * 1024; // 5 MB
const SUBIDA_MAX_LADO  = 8000;            // píxeles

function carpeta_subidas() {
    return dirname(__DIR__) . '/uploads/moviles';
}

// ¿Es una ruta que generó este mismo sistema? (evita borrar o servir archivos ajenos)
function ruta_subida_valida($ruta) {
    return is_string($ruta) && preg_match('#^uploads/moviles/[a-f0-9]{32}\.(jpg|png|webp)$#', $ruta) === 1;
}

/**
 * Valida y guarda la imagen subida. Devuelve la ruta relativa o null si no se subió nada.
 * Lanza una Exception con un mensaje claro si algo no es válido.
 */
function procesar_subida_imagen($fichero) {
    if (!is_array($fichero) || !isset($fichero['error']) || $fichero['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($fichero['error'] !== UPLOAD_ERR_OK) {
        if ($fichero['error'] === UPLOAD_ERR_INI_SIZE || $fichero['error'] === UPLOAD_ERR_FORM_SIZE) {
            throw new Exception('La imagen es demasiado grande para la configuración del servidor (revisa upload_max_filesize y post_max_size en php.ini).');
        }
        throw new Exception('No se ha podido subir la imagen (código de error ' . (int)$fichero['error'] . ').');
    }

    if ($fichero['size'] > SUBIDA_MAX_BYTES) {
        throw new Exception('La imagen pesa más de ' . (SUBIDA_MAX_BYTES / 1024 / 1024) . ' MB.');
    }
    if (!is_uploaded_file($fichero['tmp_name'])) {
        throw new Exception('El archivo subido no es válido.');
    }

    // Se comprueba el contenido real del archivo, no su nombre ni lo que diga el navegador
    $info = @getimagesize($fichero['tmp_name']);
    $permitidos = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if ($info === false || !isset($permitidos[$info['mime']])) {
        throw new Exception('Solo se admiten imágenes JPG, PNG o WebP.');
    }
    if ($info[0] > SUBIDA_MAX_LADO || $info[1] > SUBIDA_MAX_LADO) {
        throw new Exception('La imagen es demasiado grande (máximo ' . SUBIDA_MAX_LADO . ' píxeles por lado).');
    }

    $carpeta = carpeta_subidas();
    if (!is_dir($carpeta) && !mkdir($carpeta, 0755, true)) {
        throw new Exception('No se ha podido crear la carpeta uploads/moviles.');
    }

    $nombre = bin2hex(random_bytes(16)) . '.' . $permitidos[$info['mime']];
    if (!move_uploaded_file($fichero['tmp_name'], $carpeta . '/' . $nombre)) {
        throw new Exception('No se ha podido guardar la imagen en el servidor (revisa los permisos de la carpeta uploads).');
    }
    @chmod($carpeta . '/' . $nombre, 0644);

    return 'uploads/moviles/' . $nombre;
}

// Borra del disco una imagen subida por este sistema (las URLs externas antiguas se ignoran)
function borrar_archivo_subido($ruta) {
    if (!ruta_subida_valida($ruta)) return;
    $archivo = dirname(__DIR__) . '/' . $ruta;
    if (is_file($archivo)) {
        @unlink($archivo);
    }
}
