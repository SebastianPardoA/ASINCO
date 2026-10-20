<?php
/**
 * imágenes.php
 *
 * Contiene utilidades para convertir imágenes almacenadas como BLOB
 * en valores seguros que puedan ser consumidos por las vistas mediante la API
 */

/* Convierte una imagen BLOB en una URI de datos para mostrarla en el navegador */
function convertirImagenBlobADataUri(?string $imagen, ?string $tipoImagen = null): ?string
{
    if ($imagen === null || $imagen === '') {
        return null;
    }

    $tipoMime = trim((string) $tipoImagen);
    if ($tipoMime === '' && function_exists('finfo_open')) {
        $informacionArchivo = finfo_open(FILEINFO_MIME_TYPE);
        $tipoDetectado = finfo_buffer($informacionArchivo, $imagen);
        finfo_close($informacionArchivo);
        $tipoMime = is_string($tipoDetectado) ? $tipoDetectado : '';
    }

    if (!preg_match('/^image\/[a-z0-9.+-]+$/i', $tipoMime)) {
        $tipoMime = 'image/png';
    }

    return 'data:' . $tipoMime . ';base64,' . base64_encode($imagen);
}
