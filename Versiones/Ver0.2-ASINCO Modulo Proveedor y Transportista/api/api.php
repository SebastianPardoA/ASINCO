<?php
/**
 * api.php
 *
 * Punto de entrada general de las APIs del sistema ASINCO
 * Dirige las solicitudes de los módulos administrativos y responde en formato JSON
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../configuracion/base_datos.php';
require_once __DIR__ . '/../configuracion/sesion.php';
require_once __DIR__ . '/modulos/administracion_api.php';
require_once __DIR__ . '/modulos/proveedores_api.php';
require_once __DIR__ . '/modulos/metodo_despacho_api.php';
require_once __DIR__ . '/modulos/historial_compras_api.php';
require_once __DIR__ . '/modulos/inventario_api.php';

/* Devuelve una respuesta JSON y finaliza la solicitud */
function responderJson(array $respuesta, int $codigoHttp = 200)
{
    http_response_code($codigoHttp);
    echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
    exit;
}

/* Evita exponer datos o permitir operaciones de API sin una sesión activa */
if (!haySesionActiva()) {
    responderJson([
        'exito' => false,
        'mensaje' => 'La sesión no está activa.',
    ], 401);
}

$modulo = trim((string) ($_GET['modulo'] ?? ''));
$accion = trim((string) ($_GET['accion'] ?? ''));

try {
    $accionesAdministracion = ['crear_usuario', 'listar_usuarios', 'obtener_usuario', 'actualizar_usuario', 'eliminar_usuario'];
    $accionesCatalogos = ['listar_proveedores', 'obtener_proveedor', 'actualizar_proveedor', 'listar_transportistas', 'obtener_transportista', 'actualizar_transportista', 'listar_historial_compras', 'obtener_detalle_compra', 'actualizar_estado_compra', 'guardar_evaluacion_compra', 'guardar_comentario_compra', 'subir_documento_compra', 'eliminar_documento_compra', 'listar_inventario', 'obtener_detalle_producto', 'listar_usuarios_asignacion', 'asignar_unidad_usuario', 'listar_estados_unidad', 'actualizar_estado_unidad', 'subir_imagen_producto', 'eliminar_imagen_producto', 'marcar_imagen_principal_producto'];

    if (($modulo !== 'administracion' && $modulo !== 'proveedores' && $modulo !== 'transportistas' && $modulo !== 'historial_compras' && $modulo !== 'inventario')
        || !in_array($accion, array_merge($accionesAdministracion, $accionesCatalogos), true)) {
        responderJson([
            'exito' => false,
            'mensaje' => 'Solicitud de API no válida.',
        ], 400);
    }

    $conexion = obtenerConexionBaseDatos();

    if ($modulo === 'proveedores' && $accion === 'listar_proveedores' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $resultado = listarProveedores(
            $conexion,
            (int) ($_GET['pagina'] ?? 1),
            5,
            trim((string) ($_GET['busqueda'] ?? ''))
        );

        responderJson(['exito' => true, 'datos' => $resultado]);
    }

    if ($modulo === 'proveedores' && $accion === 'obtener_proveedor' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $resultado = obtenerProveedor($conexion, (int) ($_GET['id_proveedor'] ?? 0));
        responderJson(['exito' => true, 'datos' => $resultado]);
    }

    if ($modulo === 'proveedores' && $accion === 'actualizar_proveedor' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $resultado = actualizarProveedor($conexion);
        responderJson(['exito' => true, 'mensaje' => $resultado['mensaje']]);
    }

    if ($modulo === 'transportistas' && $accion === 'listar_transportistas' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $resultado = listarTransportistas(
            $conexion,
            (int) ($_GET['pagina'] ?? 1),
            5,
            trim((string) ($_GET['busqueda'] ?? ''))
        );

        responderJson(['exito' => true, 'datos' => $resultado]);
    }

    if ($modulo === 'transportistas' && $accion === 'obtener_transportista' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $resultado = obtenerTransportista($conexion, (int) ($_GET['id_transportista'] ?? 0));
        responderJson(['exito' => true, 'datos' => $resultado]);
    }

    if ($modulo === 'transportistas' && $accion === 'actualizar_transportista' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $resultado = actualizarTransportista($conexion);
        responderJson(['exito' => true, 'mensaje' => $resultado['mensaje']]);
    }

    if ($accion === 'listar_usuarios' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $pagina = (int) ($_GET['pagina'] ?? 1);
        $estado = trim((string) ($_GET['estado'] ?? ''));
        $busqueda = trim((string) ($_GET['busqueda'] ?? ''));
        $resultado = listarUsuarios($conexion, $pagina, 7, $estado, $busqueda);

        responderJson([
            'exito' => true,
            'datos' => $resultado,
        ]);
    }

    if ($accion === 'obtener_usuario' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $idUsuario = (int) ($_GET['id_usuario'] ?? 0);
        $usuario = obtenerUsuario($conexion, $idUsuario);

        responderJson([
            'exito' => true,
            'datos' => $usuario,
        ]);
    }

    if ($accion === 'actualizar_usuario' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $resultado = actualizarUsuario($conexion);

        responderJson([
            'exito' => true,
            'mensaje' => $resultado['mensaje'],
        ]);
    }

    if ($accion === 'eliminar_usuario' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $resultado = eliminarUsuario($conexion);

        responderJson([
            'exito' => true,
            'mensaje' => $resultado['mensaje'],
        ]);
    }

    if ($accion !== 'crear_usuario' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
        responderJson([
            'exito' => false,
            'mensaje' => 'Método HTTP no válido para la acción solicitada.',
        ], 405);
    }

    $resultado = crearUsuario($conexion);

    responderJson([
        'exito' => true,
        'mensaje' => $resultado['mensaje'],
        'datos' => $resultado,
    ]);
} catch (InvalidArgumentException $error) {
    responderJson([
        'exito' => false,
        'mensaje' => $error->getMessage(),
    ], 422);
} catch (PDOException $error) {
    $esDuplicado = $error->errorInfo[1] ?? null;

    responderJson([
        'exito' => false,
        'mensaje' => $esDuplicado === 1062
            ? 'El correo electrónico o nombre de usuario ya existe.'
            : 'No fue posible guardar los cambios en este momento.',
    ], 500);
}
