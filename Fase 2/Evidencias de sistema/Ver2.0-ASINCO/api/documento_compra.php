<?php
/**
 * documento_compra.php
 *
 * Entrega un documento binario almacenado en la base de datos para la previsualización en el navegador o descarga directa
 */

require_once __DIR__ . '/../configuracion/base_datos.php';
require_once __DIR__ . '/../configuracion/sesion.php';
require_once __DIR__ . '/modulos/historial_compras_api.php';

/* Responde errores del archivo binario usando JSON */
function responderErrorDocumento(string $mensaje, int $codigoHttp): void
{
    http_response_code($codigoHttp);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['exito' => false, 'mensaje' => $mensaje], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!haySesionActiva()) {
    responderErrorDocumento('La sesión no está activa.', 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    responderErrorDocumento('Método HTTP no válido.', 405);
}

try {
    $documento = obtenerDocumentoCompra(obtenerConexionBaseDatos(), (int) ($_GET['id_documento'] ?? 0));
    $modo = trim((string) ($_GET['modo'] ?? 'previsualizar'));
    $disposicion = $modo === 'descargar' ? 'attachment' : 'inline';
    $nombreArchivo = str_replace(["\r", "\n", '"'], '', $documento['nombre_archivo']);

    header('Content-Type: ' . $documento['tipo_archivo']);
    header('Content-Length: ' . strlen($documento['archivo']));
    header('Content-Disposition: ' . $disposicion . '; filename="' . $nombreArchivo . '"');
    header('X-Content-Type-Options: nosniff');
    echo $documento['archivo'];
} catch (InvalidArgumentException $error) {
    responderErrorDocumento($error->getMessage(), 422);
} catch (PDOException $error) {
    responderErrorDocumento('No fue posible consultar el documento.', 500);
}
